<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Helpers;
use Exception,
	FSFileBackend,
	GWToolset\Config;

class FileSystem {

	/**
	 * @var {string}
	 * the target storage are in the file system
	 */
	protected $_target_fs_directory;

	/**
	 * @var {string}
	 * the target url to the file
	 */
	protected $_target_url;

	public function __construct() {
		$this->reset();
	}

	protected function reset() {
		global $wgScriptPath, $wgUploadDirectory, $wgUploadPath;
		$this->_target_fs_directory = $wgUploadDirectory . DIRECTORY_SEPARATOR . Config::$metadata_directory;
		$this->_target_url = $wgScriptPath . $wgUploadPath . DIRECTORY_SEPARATOR . Config::$metadata_directory;
	}

}
