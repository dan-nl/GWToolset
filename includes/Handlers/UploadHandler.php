<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers;
use ContentHandler,
	GWToolset\Config,
	GWToolset\Exception,
	GWToolset\Helpers\FileChecks,
	GWToolset\Helpers\WikiChecks,
	GWToolset\Helpers\WikiPages,
	GWToolset\Jobs\UploadMediafileJob,
	GWToolset\Jobs\UploadFromUrlJob,
	JobQueueGroup,
	Linker,
	MWHttpRequest,
	Php\Filter,
	Title,
	UploadBase,
	UploadFromUrl,
	User,
	WikiPage;

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

	/**
	 * adds to the wiki’s allowed extensions array, $wgFileExtensions so that
	 * UploadBase will accept them
	 *
	 * @param {array} $accepted_types
	 */
	protected function addAllowedExtensions( array $accepted_types = array() ) {
		global $wgFileExtensions;

		if ( empty( $accepted_types ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-accepted-types' )->escaped( 'gwtoolset-no-accepted-types-provided' ) )->parse() );
		}

		foreach( array_keys( Config::$accepted_metadata_types ) as $accepted_extension ) {
			if ( !in_array( $accepted_extension, $wgFileExtensions ) ) {
				$wgFileExtensions[] = Filter::evaluate( $accepted_extension );
			}
		}
	}

	protected function addMetadata() {
		$result = null;

		$result .= '<!-- Metadata Mapped -->' . PHP_EOL;
		$result .= '<!-- <metadata_mapped_json>' . json_encode( $this->_MediawikiTemplate->mediawiki_template_array ) . '</metadata_mapped_json> -->' . PHP_EOL . PHP_EOL;

		$result .= '<!-- Metadata Raw -->' . PHP_EOL;
		$result .= '<!-- <metadata_raw>' . PHP_EOL . $this->_MediawikiTemplate->metadata_raw . PHP_EOL . '</metadata_raw> -->' . PHP_EOL;

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

	/**
	 * follows a url testing the headers to determine the final url and
	 * extension
	 *
	 * text/html example - has a js redirect in it
	 *   $url = 'http://aleph500.biblacad.ro:8991/F?func=service&doc_library=RAL01&doc_number=000245208&line_number=0001&func_code=DB_RECORDS&service_type=MEDIA';
	 *
	 * url is to a script that returns the media file
	 *   $url = https://www.rijksmuseum.nl/mediabin.jsp?id=RP-P-1956-764
	 *   $url = 'http://europeanastatic.eu/api/image?uri=http%3A%2F%2Fcollections.smvk.se%3A8080%2Fcarlotta-em%2Fguest%2F1422401%2F13%2Fbild.jpg&size=LARGE&type=IMAGE';
	 *
	 * url is redirected to another url that actually serves the media file
	 *   $url = 'http://www.rijksmuseum.nl/media/assets/AK-RAK-1978-3';
	 *   $url = 'http://www.rijksmuseum.nl/media/assets/RP-P-1956-764';
	 *
	 * forced downloads with Content-Disposition
	 *   Content-Disposition: attachment;
	 *   times out after 25000 milliseconds - how can we set api upload curl to > timeout
	 *   $url = 'http://academia.lndb.lv/xmlui/bitstream/handle/1/231/k_001_ktl1-1-27.jpg';
	 *
	 * Content-Disposition: inline;
	 *   $url = 'http://images.memorix.nl/gam/thumb/150x150/115165d2-1267-7db5-4abb-54d273c47a81.jpg';
	 *
	 * @param {string} $url
	 * @throws Exception
	 * @return {array}
	 *   $result['content-type']
	 *   $result['extension']
	 *   $result['url']
	 */
	protected function evaluateMediafileUrl( &$url ) {

		$result = array( 'extension' => null, 'url' => null );
		$pathinfo = array();

		$Http = MWHttpRequest::factory(
			$url,
			array(
				'method' => 'HEAD',
				'followRedirects' => true,
				'userAgent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1'
			)
		);

		$Status = $Http->execute();

		if ( !$Status->ok ) {
			throw new Exception( wfMessage('gwtoolset-mapping-media-file-url-bad')->rawParams( Filter::evaluate( $url ) )->escaped() );
		}

		$result['url'] = $Http->getFinalUrl();
		$result['content-type'] = $Http->getResponseHeader('content-type');
		$pathinfo = pathinfo( $result['url'] );

		if ( !empty( $pathinfo['extension'] )
			&& in_array( $pathinfo['extension'], Config::$accepted_media_types )
			&& in_array( $result['content-type'], Config::$accepted_media_types[ $pathinfo['extension'] ] )
		) {
			$result['extension'] = $pathinfo['extension'];
		} else {
			if ( empty( $result['content-type'] ) ) {
				throw new Exception( wfMessage('gwtoolset-mapping-media-file-no-content-type')->rawParams( Filter::evaluate( $url ) )->escaped() );
			}

			foreach( Config::$accepted_media_types as $extension => $mime_types ) {
				foreach( $mime_types as $mime_type ) {
					if ( $result['content-type'] == $mime_type ) {
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

	/**
	 * concatenates several pieces of information in order to create the wiki
	 * text for the mediafile page
	 *
	 * @return {string}
	 */
	protected function getText() {
		return
			$this->_MediawikiTemplate->getTemplate( $this->user_options ) . PHP_EOL . PHP_EOL .
			$this->addGlobalCategories() .
			$this->addItemSpecificCategories() . PHP_EOL . PHP_EOL .
			$this->addMetadata();
	}

	/**
	 * retrieves the metadata file url, to the local wiki, or the uploaded file
	 * given in the $_POST'ed form
	 *
	 * if the form contains both a url and uploaded file, the script will prefer
	 * the url and ignore the uploaded file
	 *
	 * if a file is uploaded, a local wiki url to the newly uploaded file will be
	 * added to $user_options[$metadata_file_url]
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $metadata_file_url
	 * the key within $user_options that holds the url to the metadata file
	 * stored in the local wiki
	 *
	 * @param {string} $metadata_file_upload
	 * the key within the $user_options that holds the key-name expected in
	 * $_FILES[] when the metadata file has been uploaded via an html form
	 *
	 * @return {null|Title}
	 */
	public function getTitleFromUploadedFile( array &$user_options, $metadata_file_url = 'metadata-file-url', $metadata_file_upload = 'metadata-file-upload' ) {
		$result = null;

		if ( !empty( $user_options[ $metadata_file_url ] ) ) {
			$result = WikiPages::getTitleFromUrl(
				$user_options[ $metadata_file_url ],
				FileChecks::getAcceptedExtensions( Config::$accepted_metadata_types )
			);
		} elseif ( !empty( $_FILES[ $metadata_file_upload ]['name'] ) ) {
			$this->_File->populate( $metadata_file_upload );
			FileChecks::isUploadedFileValid( $this->_File, Config::$accepted_metadata_types );
			$this->addAllowedExtensions( Config::$accepted_metadata_types );
			$result = $this->saveMetadataFileAsContent();
			$user_options['metadata-file-url'] = $result;
		} else {
			throw new Exception( wfMessage( 'gwtoolset-metadata-file-url-not-present' )->escaped() );
		}

		return $result;
	}

	/**
	 * @return {void}
	 */
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
	 * attempts to save the uploaded metadata file to the wiki as a file
	 * using UploadBase
	 * @return {null|string|Title}
	 */
	public function saveMetadataFile() {
		$result = null;

		// UploadBase requires that $_FILES array contains the uploaded file in the key wpUploadFile
		$_FILES['wpUploadFile'] = $this->_File->original_file_array;

		// UploadBase requires that the WebRequest is passed as variable
		$WebRequest = $this->_SpecialPage->getRequest();

		// UploadBase uses the $_POST['wpDestFile'] value as a proposed filename
		$WebRequest->setVal( 'wpDestFile', $this->_File->pathinfo['filename'] . '-' . $this->_User->getName() );

		$this->_UploadBase = UploadBase::createFromRequest( $WebRequest );
		$Status = $this->uploadMetadataFile();

		if ( !$Status->isGood() ) {
			$this->_SpecialPage->getOutput()->parse( $Status->getWikiText() );
		} else {
			$result = $this->_UploadBase->getTitle();
		}

		return $result;
	}

	/**
	 * attempts to save the uploaded metadata file to the wiki as content
	 * @return {null|Title}
	 */
	public function saveMetadataFileAsContent() {
		$result = null;
		WikiChecks::increaseHTTPTimeout( 120 );

		$Metadata_Title = Title::newFromText(
			Config::$metadata_namespace .
			Config::$metadata_sets_subdirectory . '/' .
			$this->_User->getName() . '/' .
			WikiPages::titleCheck( $this->_File->pathinfo['filename'] ) .
			'.' . $this->_File->pathinfo['extension']
		);

		$text = file_get_contents( $this->_File->tmp_name );
		$Metadata_Content = ContentHandler::makeContent( $text, $Metadata_Title );
		$summary = wfMessage( 'gwtoolset-create-metadata' )->params( Config::$name, $this->_User->getName() )->escaped();

		$Metadata_Page = new WikiPage( $Metadata_Title );
		$Metadata_Page->doEditContent( $Metadata_Content, $summary, 0, false, $this->_User );

		$result = $Metadata_Title;
		return $result;
	}

	/**
	 * controls the workflow for saving media files
	 *
	 * @param {array} $user_options
	 * @return {null|Title}
	 */
	public function saveMediaFile( array &$user_options ) {
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
		$options['comment'] = wfMessage( 'gwtoolset-create-mediafile' )->params( Config::$name, $this->_User->getName() )->escaped() . PHP_EOL . trim( $this->user_options['comment'] );
		$options['text'] = $this->getText();

		if ( $this->user_options['save-as-batch-job'] ) {
			$Status = $this->saveMediafileViaJob( $options );
		} else {
			$result = $this->saveMediafileAsContent( $options );
		}

		return $result;
	}

	/**
	 * @param {array} $options
	 * @throws Exception
	 * @return {Title}
	 */
	public function saveMediafileAsContent( array &$options ) {
		$this->validatePageOptions( $options );
		$Status = null;
		WikiChecks::increaseHTTPTimeout();
		$Mediafile_Title = Title::newFromText( Config::$mediafile_namespace . WikiPages::titleCheck( $options['title'] ) );

		if ( !$Mediafile_Title->isKnown() ) {
			$Status = $this->uploadMediaFileViaUploadFromUrl( $options );
		} else {
			if ( $this->user_options['upload-media'] ) {
				// this will re-upload the mediafile, but will not change the page contents
				$Status = $this->uploadMediaFileViaUploadFromUrl( $options );
			}

			if ( $Status === null || $Status->isOk() ) {
				$Mediafile_Content = ContentHandler::makeContent( $options['text'], $Mediafile_Title );
				$Mediafile_Page = new WikiPage( $Mediafile_Title );
				$Status = $Mediafile_Page->doEditContent( $Mediafile_Content, $options['comment'], 0, false, $this->_User );
			}
		}

		if ( !$Status->isOK() ) {
			throw new Exception( $Status->getWikiText() );
		}

		return $Mediafile_Title;
	}

	/**
	 * @param {array} $options
	 * @return {boolean}
	 */
	protected function saveMediafileViaJob( array &$options ) {
		$result = false;
		$job = null;
		$sessionKey = null;

		if ( $this->jobs_added > Config::$job_throttle ) {
			return;
		}

		$job = new UploadMediafileJob(
			Title::newFromText( Config::$mediafile_namespace . WikiPages::titleCheck( $options['title'] ) ),
			array(
				'comment' => $options['comment'],
				'ignorewarnings' => $options['ignorewarnings'],
				'text' => $options['text'],
				'title' => $options['title'],
				'url_to_the_media_file' => $options['url_to_the_media_file'],
				'username' => $this->_User->getName(),
				'user_options' => $this->user_options,
				'watch' => $options['watch']
			)
		);

		$result = JobQueueGroup::singleton()->push( $job );

		if ( $result ) {
			$this->jobs_added += 1;
		} else {
			$this->jobs_not_added += 1;
		}

		return $result;
	}

	/**
	 * @param {array} $options
	 * @return {Status}
	 */
	protected function uploadMediaFileViaUploadFromUrl( array &$options ) {
		// Initialize this object and the upload object
		$Upload = new UploadFromUrl();
		$Upload->initialize(
			WikiPages::titleCheck( $options['title'] ),
			$options['url_to_the_media_file'],
			false
		);

		// Fetch the file - returns a Status Object
		$Status = $Upload->fetchFile();
		if ( !$Status->isOk() ) {
			return $Status;
		}

		// Verify upload - returns a status value via an array
		$status = $Upload->verifyUpload();
		if ( $status['status'] != UploadBase::OK ) {
			return $Upload->convertVerifyErrorToStatus( $status );
		}

		// Perform the upload - returns FileRepoStatus Object
		$Status = $Upload->performUpload(
			$options['comment'],
			$options['text'],
			$options['watch'],
			$this->_User
		);

		return $Status;
	}

	/**
	 * @return {null|Status}
	 */
	protected function uploadMetadataFile() {
		$Status = null;

		$comment = wfMessage( 'gwtoolset-create-metadata' )->params( Config::$name, $this->_User->getName() )->escaped();
		$pagetext = '[[Category:' . Config::$metadata_file_category. ']]';
		$Status = $this->_UploadBase->performUpload( $comment, $comment . $pagetext, null, $this->_User );

		return $Status;
	}

	/**
	 * @param {array} $options
	 * @throws {Exception}
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
	 * @param {array} $options
	 * @throws {Exception}
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

}
