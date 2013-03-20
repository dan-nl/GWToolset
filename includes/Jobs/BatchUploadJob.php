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

			if ( empty( $this->params['user'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'user\'] provided' . PHP_EOL );
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

			if ( empty( $this->params['url-to-the-media-file'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'url-to-the-media-file\'] provided' .PHP_EOL );
				$result = false;

			}

			if ( empty( $this->params['user-options'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'user-options\'] provided' . PHP_EOL );
				$result = false;

			}

		return $result;

	}

	/**
	 * die() seems to be the only way to stop the run from being eliminated from the job queue
	 * return false seems to do nothing
	 */
	public function run() {

		$time_start = microtime(true);

		if ( !$this->validateParams() ) { die(); return false; }

		$this->_User = User::newFromName( $this->params['user'] );
		$this->_MWApiClient = \GWToolset\getMWApiClient( $this->_User->getName() ); // should we turn debugging on?
		$this->_UploadHandler = new UploadHandler( array( 'MWApiClient' => $this->_MWApiClient ) );
		
		$result = $this->_UploadHandler->savePageViaApiUpload( $this->params );

		//'filename-metadata' => $this->getFilenameFromUserOptions( $this->_user_options ),
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		error_log( "Saved {$this->params['filename-page-title']} to the wiki in $time seconds with result = $result" . PHP_EOL );
		return $result;

	}


	public function __construct( $title, $params, $id = 0 ) {

		parent::__construct( 'gwtoolsetBatchUpload', $title, $params, $id );

	}


}