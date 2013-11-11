<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */

namespace GWToolset;
use GWToolset\Config,
	GWToolset\Helpers\GWTFileBackend,
	Maintenance,
	RepoGroup;

chdir( '../../../maintenance' );
require_once './Maintenance.php';

/**
 * Maintenance script to remove abandoned or outdated metadata files from the temporary
 * gwtoolset file storage. These files are normally removed by GWToolset\Jobs\GWTFileBackendCleanupJob,
 * however if a user stops the GWToolset upload process or the clean-up job fails to run,
 * some files may become orphaned.
 *
 * @ingroup Maintenance
 */
class GWTFileBackendCleanup extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Clean up abandoned files in the GWTFileBackend.';
	}

	public function execute() {
		$GWTFileBackend = new GWTFileBackend(
			array(
				'container' => Config::$fsbackend_container,
				'directory' => Config::$fsbackend_directory,
				'lockmanager' => Config::$fsbackend_lockmanager,
				'name' => Config::$fsbackend_name,
				'no_access' => Config::$fsbackend_no_access,
				'no_listing' => Config::$fsbackend_no_listing
			)
		);

		// how far back should the script look for files to delete?
		// expects an unsigned relative time, e.g., 1 day, 1 week
		$cutoff = strtotime( '-' . Config::$fsbackend_max_age );

		$this->output(
			'Getting list of files to clean up' . PHP_EOL .
			'...' . PHP_EOL
		);

		$mwstore_path = $GWTFileBackend->getMWStorePath();
		$FSFileBackendFileList = $GWTFileBackend->FileBackend->getFileList(
			array( 'dir' => $mwstore_path )
		);

		$this->output(
			'Removing any files older than (' . Config::$fsbackend_max_age . ')' . PHP_EOL .
			'...' . PHP_EOL
		);

		$file_count = 0;

		foreach ( $FSFileBackendFileList as $file ) {
			$mwstore_file_path = $mwstore_path . $file;

			$FSFile = $GWTFileBackend
				->FileBackend
				->getLocalReference(
					array( 'src' => $mwstore_file_path )
				);

			if (
				key_exists(
					$FSFile->extensionFromPath( $FSFile->getPath() ),
					Config::$accepted_metadata_types
				)
				&& wfTimestamp( TS_UNIX, $FSFile->getTimestamp() ) < $cutoff
			) {
				$Status = $GWTFileBackend->deleteFile( $mwstore_path . $file );

				if ( !$Status->isOK() ) {
					$this->error( print_r( $Status->getErrorsArray(), true ) );
				}

				$this->output( 'Removed file (' . $mwstore_file_path . ')' .PHP_EOL );
				$file_count++;
			}
		}

		$this->output(
			'...' . PHP_EOL .
			'(' . $file_count . ') files deleted' . PHP_EOL
		);
	}
}

$maintClass = 'GWToolset\GWTFileBackendCleanup';
require_once RUN_MAINTENANCE_IF_MAIN;
