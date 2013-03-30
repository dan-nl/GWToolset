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
	GWToolset\Jobs\UploadMediafileJob,
	JobQueueGroup,
	Php\Filter,
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

			if ( empty( $options['filename-page-title'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no filename-page-title provided' ) );

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
						'filename' => $options['filename-page-title'],
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

		
			if ( empty( $options['filename-page-title'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no filename-page-title provided' ) );

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
					'filename' => $options['filename-page-title'],
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


	/**
	 * assumes that $this->_MediawikiTemplate has been populated with metadata
	 * from a DOMElement and queries the wiki for a page title based on that
	 * information
	 *
	 * @param {string} $filename
	 *
	 * @return int
	 * a matching page id in the wiki or -1 if no match found
	 */
	private function getTitlePageId( $filename ) {

		$page_id = -1;
		$api_result = array();

			$api_result = $this->_MWApiClient->query( array( 'titles' => 'File:' . $filename, 'indexpageids' => '' ) );

			if ( empty( $api_result['query']['pageids'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'api-result does not contain expected keys [query] and/or [query][pageids]' ) );

			}

		return (int)$api_result['query']['pageids'][0];

	}


	public function savePageViaApiUpload( array &$options, $result_as_boolean = false ) {

		$result = null;

			if ( empty( $options['filename-page-title'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no filename-page-title provided' ) );

			}

			$options['pageid'] = $this->getTitlePageId( $options['filename-page-title'] );

			if ( $options['pageid'] > -1 ) { // page already exists

				$result = $this->updatePage( $options, $result_as_boolean );

			} else { // page does not yet exist

				$result = $this->createPage( $options, $result_as_boolean );

			}

		return $result;

	}


	protected function savePageViaJob( array $options ) {

		$result = false;
		$job = null;

			$job = new UploadMediafileJob(
				Title::newFromText( 'User:' . $this->_User->getName() ),
				array(
					'comment' => $options['comment'],
					'filename-page-title' => $options['filename-page-title'], // the page title to create/update
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
		$options = array();
		$this->user_options = $user_options;

			if ( !isset( $this->user_options['comment'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'user_options[\'comment\'] not set' ) );

			}

			$options['filename-page-title'] = $this->_MediawikiTemplate->getTitle();

			if ( empty( $options['filename-page-title'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'could not get a valid title from the mediawiki template class' ) );

			}

			if ( !isset( $this->user_options['save-as-batch-job'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'user_options[\'save-as-batch-job\'] not set' ) );

			}

			if ( !isset( $this->user_options['upload-media'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'user_options[\'upload-media\'] not set' ) );

			}

			$options['ignorewarnings'] = true;
			$options['comment'] = $this->user_options['comment'];
			$options['text'] = $this->_MediawikiTemplate->getTemplate();
			$options['url_to_the_media_file'] = $this->_MediawikiTemplate->mediawiki_template_array['url_to_the_media_file'];

			if ( $this->user_options['save-as-batch-job'] ) {

				$result = $this->savePageViaJob( $options );

			} else {

				$result = $this->savePageViaApiUpload( $options );

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


	public function getFilenameFromUserOptions( array &$user_options, $metadata_file_url = 'metadata-file-url' ) {

		$result = null;
		global $wgServer;

			if ( !isset( $user_options[ $metadata_file_url ] ) ) {

				throw new Exception( wfMessage('gwtoolset-metadata-file-url-not-present') );

			}

			FileChecks::isAcceptedFileExtension(
				$user_options[ $metadata_file_url ],
				FileChecks::getAcceptedExtensions( Config::$accepted_types )
			);

			$result = str_replace(
				array( $wgServer, 'index.php', '/', 'File:' ),
				'',
				$user_options[ $metadata_file_url ]
			);

		return $result;

	}


	/**
	 * 
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $metadata_file_url
	 * the key within $user_options that holds the url to the metadata file
	 * stored in the local wiki
	 *
	 * @return {string} a reference to the local file path
	 */
	public function retrieveLocalFilePath( array &$user_options ) {

		global $wgServer, $IP;
		$result = null;
		$file_name = null;

			$file_name = $this->getFilenameFromUserOptions( $user_options );
			$file_name = 'File:' . Filter::evaluate( $file_name );

			$api_result = $this->_MWApiClient->query(
				array(
					'titles' => $file_name,
					'prop' => 'imageinfo',
					'iiprop' => 'url'
				)
			);

			if ( empty( $api_result['query']['pages'] ) || isset( $api_result['query']['pages'][-1] ) ) {

				throw new Exception( wfMessage('gwtoolset-metadata-file-url-invalid') );

			}

			foreach( $api_result['query']['pages'] as $page ) {

				if ( empty( $page['imageinfo'] )
					|| empty( $page['imageinfo'][0] )
					|| empty( $page['imageinfo'][0]['url'] )
				) {

					throw new Exception( wfMessage('gwtoolset-developer-issue')->params('api returned no imageinfo url') );

				}

				$result = $IP . str_replace( $wgServer, '', $page['imageinfo'][0]['url'] );
				break; // should only need to run through this once

			}

			if ( !file_exists( $result ) ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('api resolved file path does not exist') );

			}

		return $result;

	}


	public function getSavedFileName() {

		return $this->_UploadBase->getTitle();

	}


	/**
	 * upload the file
	 */
	protected function uploadFile() {

		$result = true;

			$status = $this->_UploadBase->performUpload( null, null, null, $this->_User );
			if ( !$status->isGood() ) { $result = $status->getWikiText(); }

		return $result;

	}


	/**
	 * attempts to save the uploaded file to the wiki
	 *
	 * @return {array}
	 *   $result['msg'] {string}
	 *   $result['uploaded'] {boolean}
	 */
	public function saveFile() {

		$result = array( 'msg' => null, 'uploaded' => false );

			// UploadBase requires that $_FILES array contains the uploaded file in the key wpUploadFile
			$_FILES['wpUploadFile'] = $this->_File->original_file_array;

			// UploadBase requires that the WebRequest is passed as variable
			$WebRequest = $this->_SpecialPage->getRequest();

			// UploadBase uses the $_POST['wpDestFile'] value as a proposed filename
			$WebRequest->setVal( 'wpDestFile', $this->getTitle() );

			$this->_UploadBase = UploadBase::createFromRequest( $WebRequest );
			$status = $this->uploadFile();

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