<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */

namespace GWToolset\Jobs;
use Job,
	GWToolset\Config,
	GWToolset\Helpers\GWTFileBackend,
	GWToolset\GWTException,
	User;

class GWTFileBackendCleanupJob extends Job {

	/**
	 * @param {Title} $title
	 * @param {bool|array} $params
	 * @param {int} $id
	 */
	public function __construct( $title, $params, $id = 0 ) {
		parent::__construct( 'gwtoolsetGWTFileBackendCleanupJob', $title, $params, $id );
	}

	/**
	 * @return {bool}
	 */
	protected function processJob() {
		$result = true;

		$GWTFileBackend = new GWTFileBackend();
		$Status = $GWTFileBackend->deleteFile( $this->params['metadata-file-mwstore'] );

		if ( !$Status->ok ) {
			$this->setLastError( __METHOD__ . ': ' . $Status->getMessage() );
			$result = false;
		}

		return $result;
	}

	/**
	 * entry point
	 * @return {bool}
	 */
	public function run() {
		$result = false;

		if ( !$this->validateParams() ) {
			return $result;
		}

		try {
			$result = $this->processJob();
		} catch ( GWTException $e ) {
			$this->setLastError( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $result;
	}

	/**
	 * @return {bool}
	 */
	protected function validateParams() {
		$result = true;

		if ( empty( $this->params['metadata-file-mwstore'] ) ) {
			$this->setLastError( __METHOD__ . ': no $this->params[\'metadata-file-mwstore\'] provided' );
			$result = false;
		}

		return $result;
	}
}
