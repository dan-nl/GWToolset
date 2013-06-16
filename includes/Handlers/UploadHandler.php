<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers;
use Exception,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	GWToolset\Helpers\WikiPages,
	GWToolset\Jobs\UploadMediafileJob,
	GWToolset\Jobs\UploadFromUrlJob,
	JobQueueGroup,
	Linker,
	Php\Filter,
	Php\Curl,
	Title,
	UploadBase,
	UploadFromUrl,
	User;

class UploadHandler {

	public $add_as_batch_job = false;

	/**
	 * @var Php\File
	 */
	protected $_File;

	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;

	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;

	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $_MWApiClient;

	/**
	 * @var SpecialPage
	 */
	protected $_SpecialPage;

	/**
	 * @var UploadBase
	 */
	protected $_UploadBase;

	/**
	 * @var User
	 */
	protected $_User;

	/**
	 * @var array
	 */
	public $user_options;

	/**
	 * @var int
	 */
	public $jobs_added;

	/**
	 * @var int
	 */
	public $jobs_not_added;

	public function __construct( array $options = array() ) {
		$this->reset();

		if ( isset( $options['File'] ) ) {
			$this->_File = $options['File'];
		}

		if ( isset( $options['Mapping'] ) ) {
			$this->_Mapping = $options['Mapping'];
		}

		if ( isset( $options['MediawikiTemplate'] ) ) {
			$this->_MediawikiTemplate = $options['MediawikiTemplate'];
		}

		if ( isset( $options['MWApiClient'] ) ) {
			$this->_MWApiClient = $options['MWApiClient'];
		}

		if ( isset( $options['SpecialPage'] ) ) {
			$this->_SpecialPage = $options['SpecialPage'];
		}

		if ( isset( $options['UploadBase'] ) ) {
			$this->_UploadBase = $options['UploadBase'];
		}

		if ( isset( $options['User'] ) ) {
			$this->_User = $options['User'];
		}
	}

	public function reset() {
		$this->_File = null;
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_MWApiClient = null;
		$this->_SpecialPage = null;
		$this->_UploadBase = null;

		$this->jobs_added = 0;
		$this->jobs_not_added = 0;
		$this->user_options = array();
	}

	/**
	 * follows a url testing the headers to determine the final url and
	 * extension
	 *
	 * @throws Exception
	 * @return array $result['url'], $result['extension']
	 */
	protected function evaluateMediafileUrl( &$url ) {
		// text/html example - has a js redirect in it
		//$url = 'http://aleph500.biblacad.ro:8991/F?func=service&doc_library=RAL01&doc_number=000245208&line_number=0001&func_code=DB_RECORDS&service_type=MEDIA';

		// url is to a script that returns the media file
		//$url = https://www.rijksmuseum.nl/mediabin.jsp?id=RP-P-1956-764
		//$url = 'http://europeanastatic.eu/api/image?uri=http%3A%2F%2Fcollections.smvk.se%3A8080%2Fcarlotta-em%2Fguest%2F1422401%2F13%2Fbild.jpg&size=LARGE&type=IMAGE';

		// url is redirected to another url that actually serves the media file
		//$url = 'http://www.rijksmuseum.nl/media/assets/AK-RAK-1978-3';
		//$url = 'http://www.rijksmuseum.nl/media/assets/RP-P-1956-764';

		// forced downloads with Content-Disposition
			// Content-Disposition: attachment;
			// times out after 25000 milliseconds - how can we set api upload curl to > timeout
			//$url = 'http://academia.lndb.lv/xmlui/bitstream/handle/1/231/k_001_ktl1-1-27.jpg';

			// Content-Disposition: inline;
			//$url = 'http://images.memorix.nl/gam/thumb/150x150/115165d2-1267-7db5-4abb-54d273c47a81.jpg';

		$result = array( 'extension' => null, 'url' => null );
		$pathinfo = array();

		$Curl = new Curl( array( 'useragent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1', 'debug-on' => true ) );
		$curl_result = $Curl->getHeadersOnly( $url );
		$curl_info = $Curl->getCurlInfo();

		// final url resolved by curl
		if ( empty( $curl_info['url'] ) ) {
			throw new Exception( wfMessage('gwtoolset-mapping-media-file-url-bad')->rawParams( Filter::evaluate( $url ) )->escaped() );
		}

		$result['url'] = $curl_info['url'];
		$pathinfo = pathinfo( $curl_info['url'] );

		if ( !empty( $pathinfo['extension'] )
			&& in_array( $pathinfo['extension'], Config::$accepted_media_types )
			&& in_array( $curl_info['content_type'], Config::$accepted_media_types[ $pathinfo['extension'] ] )
		) {
			$result['extension'] = $pathinfo['extension'];
		} else {
			if ( empty( $curl_info['content_type'] ) ) {
				throw new Exception( wfMessage('gwtoolset-mapping-media-file-no-content-type')->rawParams( Filter::evaluate( $url ) )->escaped() );
			}

			foreach( Config::$accepted_media_types as $extension => $mime_types ) {
				foreach( $mime_types as $mime_type ) {
					if ( $curl_info['content_type'] == $mime_type ) {
						$result['extension'] = $extension;
						break;
					}
				}

				if ( !empty( $result['extension'] ) ) {
					break;
				}
			}
		}

		if ( empty( $result['extension'] ) ) {
			throw new Exception( wfMessage('gwtoolset-mapping-media-file-url-extension-bad')->rawParams( Filter::evaluate( $url ) )->escaped() );
		}

		return $result;
	}

	protected function getMappedField( $field ) {
		$result = null;

		foreach( $this->_Mapping->target_dom_elements_mapped[ $field ] as $targeted_field ) {
			$parameter_as_id = $this->_MediawikiTemplate->getParameterAsId( $targeted_field );

			if ( array_key_exists( $targeted_field, $this->_MediawikiTemplate->mediawiki_template_array ) ) {
				$result .= $this->_MediawikiTemplate->mediawiki_template_array[ $targeted_field ] . ' ';
			} elseif( array_key_exists( $parameter_as_id, $this->_MediawikiTemplate->mediawiki_template_array ) ) {
				$result .= $this->_MediawikiTemplate->mediawiki_template_array[ $parameter_as_id ] . ' ';
			}
		}

		return $result;
	}

	protected function addMetadata() {
		$result = null;

		$result .= '<!-- Metadata Mapped -->' . PHP_EOL;
		$result .= '<!-- <metadata_mapped_json>' . json_encode( $this->_MediawikiTemplate->mediawiki_template_array ) . '</metadata_mapped_json> -->' . PHP_EOL . PHP_EOL;

		$result .= '<!-- Metadata Raw -->' . PHP_EOL;
		$result .= '<!-- <metadata_raw>' . PHP_EOL . $this->_MediawikiTemplate->metadata_raw . PHP_EOL . '</metadata_raw> -->' . PHP_EOL;
		//$result .= json_encode( simplexml_load_string( $xml_reader->readOuterXml() ) );

		return $result;
	}

	protected function addItemSpecificCategories() {
		$category_count = 0;
		$phrase = null;
		$metadata = null;
		$result = null;

		if ( !empty( $this->user_options['category-metadata'] ) ) {
			$category_count = count( $this->user_options['category-metadata'] );

			for ( $i = 0; $i < $category_count; $i += 1 ) {
				$phrase = null;
				$metadata = null;

				if ( !empty( $this->user_options['category-phrase'][$i] ) ) {
					$phrase = Filter::evaluate( $this->user_options['category-phrase'][$i] ) . ' ';
				}

				if ( !empty( $this->user_options['category-metadata'][$i] ) ) {
					$metadata = Filter::evaluate( $this->getMappedField( $this->user_options['category-metadata'][$i] ) );
				}

				if ( !empty( $metadata ) ) {
					$result .= '[[Category:' . $phrase . $metadata . ']]';
				}
			}
		}

		return $result;
	}

	protected function addGlobalCategories() {
		$result = null;

		if ( !empty( $this->user_options['categories'] ) ) {
			$result .= '<!-- Categories -->' . PHP_EOL;
			$categories = explode( Config::$category_separator, $this->user_options['categories'] );

			foreach( $categories as $category ) {
				$result .= '[[Category:' . Filter::evaluate( $category ) . ']]';
			}
		}

		return $result;
	}

	/**
	 *
	 * @return string
	 * the text
	 */
	protected function getText() {
		$result =
			$this->_MediawikiTemplate->getTemplate( $this->user_options ) . PHP_EOL . PHP_EOL .
			$this->addGlobalCategories() .
			$this->addItemSpecificCategories() . PHP_EOL . PHP_EOL .
			$this->addMetadata();

		return $result;
	}

	/**
	 * @throws Exception
	 * @return boolean|string
	 */
	protected function editPageViaApi( array &$options, $result_as_boolean = false ) {
		global $wgArticlePath;
		$result = true;

		// creating a new page, a comment is used
		// updating a page, summary is used
		$result = $this->_MWApiClient->edit(
			array(
				'pageid' => $options['pageid'],
				'summary' => $options['comment'],
				'text' => $options['text'],
				'token' => $this->_MWApiClient->getEditToken(),
				'watch' => $options['watch']
			)
		);

		if ( empty( $result['edit']['result'] )
			|| $result['edit']['result'] !== 'Success'
			|| empty( $result['edit']['title'] )
		) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-unexpected-api-format' )->escaped() )->parse() );
		}

		if ( !$result_as_boolean ) {
			$result =
				'<li>' .
					Linker::link(
						Title::newFromText( 'File:' . Filter::evaluate( $options['title'] ) ),
						Filter::evaluate( $options['title'] ) .
						( isset( $result['edit']['oldrevid'] )
							? wfMessage( 'gwtoolset-revised' )->escaped()
							: wfMessage( 'gwtoolset-no-change' )->escaped() )
					);
				'</li>';
		}

		return $result;
	}

	/**
	 * @throws Exception
	 * @return boolean|string
	 */
	protected function uploadMediaFileViaApi( array &$options, $result_as_boolean = false ) {
		$result = true;

		$result = $this->_MWApiClient->upload(
			array(
				'filename' => $options['title'],
				'comment' => $options['comment'],
				'ignorewarnings' => $options['ignorewarnings'],
				'text' => $options['text'],
				'token' => $this->_MWApiClient->getEditToken(),
				'url' => $options['url_to_the_media_file'],
				'watch' => $options['watch']
			)
		);

		if ( empty( $result['upload']['result'] )
			|| $result['upload']['result'] !== 'Success'
			|| empty( $result['upload']['imageinfo']['descriptionurl'] )
			|| empty( $result['upload']['filename'] )
		) {
			$msg = wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-unexpected-api-format' )->escaped() )->parse();

			if ( ini_get('display_errors') ) {
				$msg .= '<pre>' . print_r( $result, true ) . '</pre>';
			}

			throw new Exception( $msg );
		}

		if ( !$result_as_boolean ) {
			$result =
				'<li>' .
					Linker::link(
						Title::newFromText( 'File:' . $options['title'] ),
						$options['title']
					);
				'</li>';
		}

		return $result;
	}

	/**
	 * @throws Exception
	 * @return boolean|string
	 */
	protected function uploadMediaFileViaUploadFromUrl( array &$options, $result_as_boolean = false ) {
		global $wgArticlePath;

		// Initialize this object and the upload object
		$Upload = new UploadFromUrl();
		$Upload->initialize(
			$options['title'],
			$options['url_to_the_media_file'],
			false
		);

		// Fetch the file - returns a Status Object
		$status = $Upload->fetchFile();
		if ( !$status->isOk() ) {
			if ( !$result_as_boolean ) {
				return $status->getWikiText();
			}
			return false;
		}

		// Verify upload - returns a Status value
		$result = $Upload->verifyUpload();
		if ( $result['status'] != UploadBase::OK ) {
			$status = $Upload->convertVerifyErrorToStatus( $result );

			if ( !$result_as_boolean ) {
				return $status->getWikiText();
			}

			return false;
		}

		// Perform the upload - returns FileRepoStatus Object
		$status = $Upload->performUpload(
			$options['comment'],
			$options['text'],
			$options['watch'],
			$this->_User
		);

		if ( !$status->isOk() ) {
			if ( !$result_as_boolean ) {
				return $status->getWikiText();
			}
			return false;
		}

		if ( !$result_as_boolean ) {
			return '<li>' .
				Linker::link(
					Title::newFromText( 'File:' . Filter::evaluate( $options['title'] ) ),
					Filter::evaluate( $options['title'] )
				);
			'</li>';
		}

		return true;
	}

	/**
	 * @throws Exception
	 */
	protected function validatePageOptions( array &$options ) {
		if ( empty( $options['title'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-title' )->escaped() )->parse() );
		}

		if ( !isset( $options['ignorewarnings'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-ignorewarnings' )->escaped() )->parse() );
		}

		// assumes that text must be something
		if ( empty( $options['text'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-text' )->escaped() )->parse() );
		}

		if ( empty( $options['url_to_the_media_file'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-url-to-media' )->escaped() )->parse() );
		}
	}

	/**
	 * @param array $options
	 * @param boolean $result_as_boolean
	 *
	 * @throws Exception
	 * @return boolean|string
	 */
	protected function updatePage( array &$options, $result_as_boolean = false ) {
		$result = true;

		$this->validatePageOptions( $options );

		// assumes that pageid is a positive int
		if ( empty( $options['pageid'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-pageid' )->escaped() )->parse() );
		}

		// upload another version of the media
		if ( $this->user_options['upload-media'] ) {
			// $this->uploadMediaFileViaApi( $options, $result_as_boolean );
			$this->uploadMediaFileViaUploadFromUrl( $options, $result_as_boolean );
		}

		$result = $this->editPageViaApi( $options, $result_as_boolean );

		return $result;
	}

	/**
	 * @param array $options
	 * @param boolean $result_as_boolean
	 *
	 * @throws Exception
	 * @return boolean|string
	 */
	protected function createPage( array &$options, $result_as_boolean = false ) {
		$result = true;

		$this->validatePageOptions( $options );

		// upload media no matter the user_option['upload-media'] value
		// this is done because the page will be new and needs the media file
		//$result = $this->uploadMediaFileViaApi( $options, $result_as_boolean );
		$result = $this->uploadMediaFileViaUploadFromUrl( $options, $result_as_boolean );

		return $result;
	}

	public function savePageNow( array &$options, $result_as_boolean = false ) {
		$result = null;

		$options['pageid'] = WikiPages::getTitlePageId( 'File:' . $options['title'] );

		// page already exists
		if ( $options['pageid'] > -1 ) {
			$result = $this->updatePage( $options, $result_as_boolean );
		// page does not yet exist
		} else {
			$result = $this->createPage( $options, $result_as_boolean );
		}

		return $result;
	}

	/**
	 * @return bool result of the JobQueueGroup::singleton()->push()
	 */
	protected function savePageViaJob( array &$options ) {
		$result = false;
		$job = null;
		$sessionKey = null;

		if ( $this->jobs_added > Config::$job_throttle ) {
			return;
		}

		$job = new UploadMediafileJob(
			Title::newFromText( 'File:' . $options['title'] ),
			array(
				'comment' => $options['comment'],
				'ignorewarnings' => $options['ignorewarnings'],
				'text' => $options['text'],
				'title' => $options['title'], // the page title to create/update
				'url_to_the_media_file' => $options['url_to_the_media_file'],
				'username' => $this->_User->getName(),
				'user_options' => $this->user_options,
				'watch' => $options['watch']
			)
		);
		//$job = new UploadFromUrlJob(
		//	Title::newFromText( 'File:' . $options['title'] ),
		//	array(
		//		'comment' => $options['comment'],
		//		'ignorewarnings' => $options['ignorewarnings'],
		//		'text' => $options['text'],
		//		'url_to_the_media_file' => $options['url_to_the_media_file'],
		//		'username' => $this->_User->getName(),
		//		'watch' => $options['watch']
		//	)
		//);

		$result = JobQueueGroup::singleton()->push( $job );

		if ( $result ) {
			$this->jobs_added += 1;
		} else {
			$this->jobs_not_added += 1;
		}

		return $result;
	}

	/**
	 * @param array $options
	 */
	protected function validateUserOptions( array &$user_options ) {
		if ( !isset( $user_options['comment'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-comment' )->escaped() )->parse() );
		}

		if ( !isset( $user_options['save-as-batch-job'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-save-as-batch' )->escaped() )->parse() );
		}

		if ( !isset( $user_options['upload-media'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-upload-media' )->escaped() )->parse() );
		}
	}

	public function saveMediawikiTemplateAsPage( array &$user_options ) {
		$result = null;
		$options = array();

		$this->validateUserOptions( $user_options );
		$this->user_options = $user_options;

		$options['url_to_the_media_file'] = $this->_MediawikiTemplate->mediawiki_template_array['url_to_the_media_file'];
		$evaluated_url = $this->evaluateMediafileUrl( $options['url_to_the_media_file'] );
		$options['url_to_the_media_file'] = $evaluated_url['url'];
		$options['evaluated_media_file_extension'] = $evaluated_url['extension'];

		$options['title'] = $this->_MediawikiTemplate->getTitle( $options );
		$options['ignorewarnings'] = true;
		$options['watch'] = true;
		$options['comment'] = wfMessage( 'gwtoolset-upload-on-behalf-of' )->escaped() . PHP_EOL . trim( $this->user_options['comment'] );
		$options['text'] = $this->getText();

		if ( $this->user_options['save-as-batch-job'] ) {
			$result = $this->savePageViaJob( $options );
		} else {
			$result = $this->savePageNow( $options, false );
		}

		return $result;
	}

	/**
	 * assumes that this title will be passed to UploadBase, which will validate
	 * the title and append the file extension for us
	 */
	protected function getTitle() {
		$title =
			FileChecks::getValidTitle( $this->_File->pathinfo['filename'] ) .
			'-' . $this->_User->getName();

		return $title;
	}

	public function getSavedFileName() {
		return $this->_UploadBase->getTitle();
	}

	/**
	 * upload the file
	 */
	protected function uploadMetadataFile() {
		$result = true;

		$comment = wfMessage( 'gwtoolset-upload-on-behalf-of' )->escaped();
		$pagetext = '[[Category:' . Config::$metadata_file_category. ']]';
		$status = $this->_UploadBase->performUpload( $comment, $comment . $pagetext, null, $this->_User );
		if ( !$status->isGood() ) { $result = $status->getWikiText(); }

		return $result;
	}

	/**
	 * attempts to save the uploaded metadata file to the wiki
	 *
	 * @return {array}
	 *   $result['msg'] {string}
	 *   $result['uploaded'] {boolean}
	 */
	public function saveMetadataFile() {
		$result = array( 'msg' => null, 'uploaded' => false );

		// UploadBase requires that $_FILES array contains the uploaded file in the key wpUploadFile
		$_FILES['wpUploadFile'] = $this->_File->original_file_array;

		// UploadBase requires that the WebRequest is passed as variable
		$WebRequest = $this->_SpecialPage->getRequest();

		// UploadBase uses the $_POST['wpDestFile'] value as a proposed filename
		$WebRequest->setVal( 'wpDestFile', $this->getTitle() );

		$this->_UploadBase = UploadBase::createFromRequest( $WebRequest );
		$status = $this->uploadMetadataFile();

		if ( $status !== true ) {
			$result['msg'] = $this->_SpecialPage->getOutput()->parse( $status );
		} else {
			$result['msg'] =
				wfMessage( 'gwtoolset-metadata-upload-successful' )
					->params(
						$this->_UploadBase->getTitle()->escapeFullURL(),
						$this->_UploadBase->getTitle()
					)
					->parse();

			$result['uploaded'] = true;
		}

		return $result;
	}

	/**
	 * adds to the wiki’s allowed extensions array, $wgFileExtensions so that
	 * UploadBase will accept them
	 */
	protected function addAllowedExtensions() {
		global $wgFileExtensions;

		foreach( array_keys( Config::$accepted_types ) as $accepted_extension ) {
			if ( !in_array( $accepted_extension, $wgFileExtensions ) ) {
				$wgFileExtensions[] = Filter::evaluate( $accepted_extension );
			}
		}
	}

	/**
	 * attempts to retrieve the filename given from the $_POST'ed form data and
	 * store it as an object in $this->_File
	 *
	 * @param {string} $filename
	 */
	public function getUploadedFileFromForm( $filename = null ) {
		$this->_File->populate( $filename );
		FileChecks::isUploadedFileValid( $this->_File );
		$this->addAllowedExtensions();
	}

}
