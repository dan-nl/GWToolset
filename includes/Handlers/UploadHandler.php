<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @author dan entous pennlinepublishing.com
 * @copyright © 2012 dan entous
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers;
use Exception,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	GWToolset\Helpers\WikiPages,
	GWToolset\Jobs\UploadMediafileJob,
	JobQueueGroup,
	Php\Filter,
	Php\Curl,
	Title,
	UploadBase,
	User;


class UploadHandler {


	public $add_as_batch_job = false;


	/**
	 * @var Php\File
	 */
	protected $_File;


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


	public $user_options;
	public $jobs_added;
	public $jobs_not_added;


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

				throw new Exception( wfMessage('gwtoolset-mapping-media-file-url-bad')->rawParams( Filter::evaluate( $url ) ) );

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

					throw new Exception( wfMessage('gwtoolset-mapping-media-file-no-content-type')->rawParams( Filter::evaluate( $url ) ) );

				}

				foreach( Config::$accepted_media_types as $extension => $mime_types ) {

					foreach( $mime_types as $mime_type ) {

						if ( $curl_info['content_type'] == $mime_type ) {

							$result['extension'] = $extension;
							break;

						}

					}

					if ( !empty( $result['extension'] ) ) { break; }

				}

			}

			if ( empty( $result['extension'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-media-file-url-extension-bad')->rawParams( Filter::evaluate( $url ) ) );

			}

		return $result;

	}

	/**
	 *
	 * @return string
	 * the text 
	 */	
	protected function getText() {

		$result = null;

			$result = $this->_MediawikiTemplate->getTemplate();

			if ( !empty( $this->user_options['categories'] ) ) {

				$categories = explode( Config::$category_separator, $this->user_options['categories'] );

				foreach( $categories as $category ) {

					$result .= '[[Category:' . Filter::evaluate( $category ) . ']]';

				}

			}

		return $result;

	}


	/**
	 * @param {array} $options
	 * @param {boolean} $result_as_boolean
	 *
	 * @throws Exception
	 * @return {string|boolean}
	 * an html <li> element that conatins an api error message or link to the
	 * created wiki page
	 */
	protected function updatePage( array &$options, $result_as_boolean = false ) {

		$result = null;
		$api_result = array();
		global $wgArticlePath;

			if ( empty( $options['title'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no title provided' ) );

			}

			if ( !isset( $options['ignorewarnings'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'ignorewarnings not set' ) );

			}

			if ( empty( $options['text'] ) ) { // assumes that text must be something

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'text not provided' ) );

			}

			if ( empty( $options['url_to_the_media_file'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'url_to_the_media_file not set' ) );

			}

			if ( empty( $options['pageid'] ) ) { // assumes that pageid is a positive int

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'pageid not set' ) );

			}

			if ( $this->user_options['upload-media'] ) { // upload another version of the media

				$api_result = $this->_MWApiClient->upload(
					array(
						'filename' => $options['title'],
						'comment' => $options['comment'],
						'token' => $this->_MWApiClient->getEditToken(),
						'ignorewarnings' => $options['ignorewarnings'],
						'url' => $options['url_to_the_media_file']
					)
				);

			}

			// creating a new page a comment is used
			// updating a page summary is used
			$api_result = $this->_MWApiClient->edit(
				array(
					'pageid' => $options['pageid'],
					'summary' => $options['comment'],
					'text' => $options['text'],
					'token' => $this->_MWApiClient->getEditToken()
				)
			);

			if ( empty( $api_result['edit']['result'] )
				|| $api_result['edit']['result'] !== 'Success'
				|| empty( $api_result['edit']['title'] )
			) {
	
				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'api result was not in the expected format' ) );
	
			} else {

				if ( $result_as_boolean ) {

					$result = true;

				} else {

					$result .=
						'<li>' .
							'<a href="' . str_replace( '$1', $api_result['edit']['title'], $wgArticlePath ) . '">' .
								$api_result['edit']['title'] .
								( isset( $api_result['edit']['oldrevid'] ) ? ' ( revised )' : ' ( no change )' ) .
							'</a>' .
						'</li>';

				}

			}

		return $result;

	}


	/**
	 * @param {array} $options
	 * @param {boolean} $result_as_boolean
	 *
	 * @throws Exception
	 * @return {string|boolean}
	 * an html <li> element that conatins an api error message or link to the
	 * created wiki page
	 */
	protected function createPage( array &$options, $result_as_boolean = false ) {

		$result = null;
		$api_result = array();

			if ( empty( $options['title'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no title provided' ) );

			}

			if ( !isset( $options['ignorewarnings'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'ignorewarnings not set' ) );

			}

			if ( empty( $options['text'] ) ) { // assumes that text must be something

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'text not provided' ) );

			}

			if ( empty( $options['url_to_the_media_file'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'url_to_the_media_file not set' ) );

			}

			// upload media no matter the user_option['upload-media'] value
			// this is done because the page will be new and needs the media file
			$api_result = $this->_MWApiClient->upload(
				array(
					'filename' => $options['title'],
					'comment' => $options['comment'],
					'ignorewarnings' => $options['ignorewarnings'],
					'text' => $options['text'],
					'token' => $this->_MWApiClient->getEditToken(),
					'url' => $options['url_to_the_media_file']
				)
			);

			if ( empty( $api_result['upload']['result'] )
				|| $api_result['upload']['result'] !== 'Success'
				|| empty( $api_result['upload']['imageinfo']['descriptionurl'] )
				|| empty( $api_result['upload']['filename'] )
			) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'api result was not in the expected format' ) );

			} else {

				if ( $result_as_boolean ) {

					$result = true;

				} else {

					$result =
						'<li>' .
							'<a href="' . $api_result['upload']['imageinfo']['descriptionurl'] . '">' .
								$api_result['upload']['filename'] .
							'</a>' .
						'</li>';

				}

			}

		return $result;

	}


	public function savePageViaApiUpload( array &$options, $result_as_boolean = false ) {

		$result = null;

			$options['pageid'] = WikiPages::getTitlePageId( 'File:' . $options['title'] );

			if ( $options['pageid'] > -1 ) { // page already exists

				$result = $this->updatePage( $options, $result_as_boolean );

			} else { // page does not yet exist

				$result = $this->createPage( $options, $result_as_boolean );

			}

		return $result;

	}


	protected function savePageViaJob( array &$options ) {

		$result = false;
		$job = null;

			if ( $this->jobs_added > 10 ) { return; }

			$job = new UploadMediafileJob(
				Title::newFromText( 'User:' . $this->_User->getName() ),
				array(
					'comment' => $options['comment'],
					'title' => $options['title'], // the page title to create/update
					'ignorewarnings' => $options['ignorewarnings'],
					'text' => $options['text'],
					'url_to_the_media_file' => $options['url_to_the_media_file'],
					'user' => $this->_User->getName(),
					'user_options' => $this->user_options
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


	public function saveMediawikiTemplateAsPage( array &$user_options ) {

		$result = null;
		$this->user_options = $user_options;

			if ( !isset( $this->user_options['comment'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'user_options[\'comment\'] not set' ) );

			}

			if ( !isset( $this->user_options['save-as-batch-job'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'user_options[\'save-as-batch-job\'] not set' ) );

			}

			if ( !isset( $this->user_options['upload-media'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'user_options[\'upload-media\'] not set' ) );

			}

			$options = array();

			$options['url_to_the_media_file'] = $this->_MediawikiTemplate->mediawiki_template_array['url_to_the_media_file'];
			$evaluated_url = $this->evaluateMediafileUrl( $options['url_to_the_media_file'] );
			$options['url_to_the_media_file'] = $evaluated_url['url'];
			$options['evaluated_media_file_extension'] = $evaluated_url['extension'];

			$options['title'] = $this->_MediawikiTemplate->getTitle( $options );
			$options['ignorewarnings'] = true;
			$options['comment'] = $this->user_options['comment'];
			$options['text'] = $this->getText();

			if ( $this->user_options['save-as-batch-job'] ) {

				$result = $this->savePageViaJob( $options );

			} else {

				$result = $this->savePageViaApiUpload( $options, false );

			}

		return $result;

	}


	/**
	 * assumes that this title will be passed to UploadBase, which will validate
	 * the title and append the file extension for us
	 */
	protected function getTitle() {

		$title = FileChecks::getValidTitle( $this->_File->pathinfo['filename'] );
		$title .= '-' . $this->_User->getName();
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

			$comment = 'GWToolset uploading metdata file on behalf of User:' . $this->_User->getName();
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

				$result['msg'] = sprintf(
					wfMessage( 'gwtoolset-metadata-upload-successful' )->plain(),
					$this->_UploadBase->getTitle()->escapeFullURL(),
					$this->_UploadBase->getTitle()
				);

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


	public function reset() {

		$this->_File = null;
		$this->_MediawikiTemplate = null;
		$this->_MWApiClient = null;
		$this->_SpecialPage = null;
		$this->_UploadBase = null;

		$this->jobs_added = 0;
		$this->jobs_not_added = 0;
		$this->user_options = array();

	}


	public function __construct( array $options = array() ) {

		if ( isset( $options['File'] ) ) { $this->_File = $options['File']; }
		if ( isset( $options['MediawikiTemplate'] ) ) { $this->_MediawikiTemplate = $options['MediawikiTemplate']; }
		if ( isset( $options['MWApiClient'] ) ) { $this->_MWApiClient = $options['MWApiClient']; }
		if ( isset( $options['SpecialPage'] ) ) { $this->_SpecialPage = $options['SpecialPage']; }
		if ( isset( $options['UploadBase'] ) ) { $this->_UploadBase = $options['UploadBase']; }
		if ( isset( $options['User'] ) ) { $this->_User = $options['User']; }

	}


}