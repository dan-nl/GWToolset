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
use Exception,
	MWException,
	Job,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Helpers\WikiPages,
	GWToolset\MediaWiki\Api\Client,
	User;


class UploadMediafileJob extends Job {


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
	
	public $filename_metadata;

	protected function processMetadata() {

		$result = false;

			$this->_MWApiClient = \GWToolset\getMWApiClient( $this->_User->getName() );

			$this->_UploadHandler = new UploadHandler(
				array(
					'MWApiClient' => $this->_MWApiClient,
					'User' => $this->_User
				)
			);

			$this->_UploadHandler->user_options = $this->params['user_options'];

			WikiPages::$MWApiClient = $this->_MWApiClient;
			$this->filename_metadata = WikiPages::retrieveWikiFilePath( $this->params['user_options']['metadata-file-url'] );
			$result = $this->_UploadHandler->savePageViaApiUpload( $this->params, true );

		return $result;

	}


	protected function validateParams() {

		$result = true;

			if ( !isset( $this->params['comment'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'comment\'] provided' . PHP_EOL );
				$result = false;

			}

			if ( empty( $this->params['title'] ) ) {

				error_log( __METHOD__ . ' : no $this->params[\'title\'] provided' . PHP_EOL );
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

			if ( !$this->validateParams() ) { die(); return false; }

			$time_start = microtime( true );
			$this->_User = User::newFromName( $this->params['user'] );

			try {

				$result = $this->processMetadata();

			} catch( Exception $e ) {

				error_log( $e->getMessage() );

			}

			$time_end = microtime( true );
			$time = $time_end - $time_start;

			if ( $result ) {

				error_log( "Saved {$this->params['title']} to the wiki. Used the $this->filename_metadata as the metadata source. Job took $time seconds to complete." );

			} else {

				error_log( "Could not save {$this->params['title']} to the wiki. Used the $this->filename_metadata as the metadata source. Job took $time seconds to complete." );

			}

		return $result;

	}


	public function __construct( $title, $params, $id = 0 ) {

		parent::__construct( 'gwtoolsetUploadMediafileJob', $title, $params, $id );

	}


}