<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Jobs;
use Exception,
	GWToolset\Handlers\UploadHandler,
	Job,
	User;

class UploadMediafileJob extends Job {

	/**
	 * GWToolset\Handlers\UploadHandler
	 */
	protected $_UploadHandler;

	/**
	 * @var User
	 */
	protected $_User;

	/**
	 * @var string
	 */
	public $filename_metadata;

	public function __construct( $title, $params, $id = 0 ) {
		parent::__construct( 'gwtoolsetUploadMediafileJob', $title, $params, $id );
	}

	protected function processMetadata() {
		$result = false;

		$this->_UploadHandler = new UploadHandler(
			array(
				'User' => $this->_User
			)
		);

		$this->_UploadHandler->user_options = $this->params['user_options'];
		$result = $this->_UploadHandler->saveMediafileAsContent( $this->params );
		return $result;
	}

	protected function validateParams() {
		$result = true;

		if ( empty( $this->params['username'] ) ) {
			error_log( __METHOD__ . ' : no $this->params[\'username\'] provided' . PHP_EOL );
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

		if ( !$this->validateParams() ) {
			return $result;
		}

		$this->_User = User::newFromName( $this->params['username'] );

		try {
			$result = $this->processMetadata();
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
		}

		if ( !$result ) {
			error_log( "Could not save [ {$this->params['title']} ] to the wiki." );
		}

		return $result;
	}

}
