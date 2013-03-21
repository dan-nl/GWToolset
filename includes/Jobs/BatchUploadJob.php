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
namespace GWToolset\Jobs;
use Job,
	GWToolset\Handlers\UploadHandler,
	GWToolset\MediaWiki\Api\Client,
	User;


class BatchUploadJob extends Job {


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $_MWApiClient;


	/**
	 * GWToolset\Handlers\UploadHandler
	 */
	protected $_UploadHandler;


	/**
	 * @var User
	 */
	protected $_User;


	protected function validateParams() {

		$result = true;

			if ( !isset( $this->params['comment'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'comment\'] provided' . PHP_EOL );
				$result = false;

			}

			if ( empty( $this->params['filename-page-title'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'filename-page-title\'] provided' . PHP_EOL );
				$result = false;

			}

			if ( !isset( $this->params['ignorewarnings'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'ignorewarnings\'] provided' . PHP_EOL );
				$result = false;

			}

			if ( empty( $this->params['user'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'user\'] provided' . PHP_EOL );
				$result = false;

			}

			if ( empty( $this->params['url_to_the_media_file'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'url_to_the_media_file\'] provided' .PHP_EOL );
				$result = false;

			}

			if ( empty( $this->params['user_options'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'user_options\'] provided' . PHP_EOL );
				$result = false;

			}

		return $result;

	}

	/**
	 * die() seems to be the only way to stop the run from being eliminated from the job queue
	 * return false seems to do nothing
	 */
	public function run() {

		$result = false;
		$time_start = microtime(true);

			if ( !$this->validateParams() ) { die(); return false; }
	
			$this->_User = User::newFromName( $this->params['user'] );
			$this->_MWApiClient = \GWToolset\getMWApiClient( $this->_User->getName() ); // should we turn debugging on?
			$this->_UploadHandler = new UploadHandler( array( 'MWApiClient' => $this->_MWApiClient ) );
			$this->_UploadHandler->user_options = $this->params['user_options'];

			$filename_metadata = $this->_UploadHandler->getFilenameFromUserOptions( $this->params['user_options'] );
			$result = $this->_UploadHandler->savePageViaApiUpload( $this->params, true );

			$time_end = microtime(true);
			$time = $time_end - $time_start;

			if ( $result ) {

				error_log( "Saved {$this->params['filename-page-title']} to the wiki in $time seconds. Used the $filename_metadata as the metadata source" );
				//$this->_User->leaveUserMessage(
				//	wfMessage( 'upload-success-subj' )->text(),
				//	wfMessage( 'upload-success-msg',
				//		$this->upload->getTitle()->getText(),
				//		$this->params['url']
				//	)->text() );

			} else {

				error_log( "Could not save {$this->params['filename-page-title']} to the wiki. Used the $filename_metadata as the metadata source" );
				//$this->user->leaveUserMessage(
				//	wfMessage( 'upload-failure-subj' )->text(),
				//	wfMessage( 'upload-failure-msg',
				//		$status->getWikiText(),
				//		$this->params['url']
				//	)->text() );
			}

		return $result;

	}


	public function __construct( $title, $params, $id = 0 ) {

		parent::__construct( 'gwtoolsetBatchUpload', $title, $params, $id );

	}


}