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
namespace	GWToolset\Handlers\Forms;
use			Exception,
			LocalFile,
			Php\File;


class MetadataUploadHandler extends UploadHandler {


	/**
	 * @todo: how to deal with the security risk of opening up the app to other
	 * extension formats and no currently allowed and bypassing uploadbase's
	 * check for that
	 *
	 * @return {string}
	 */
	protected function processUpload() {

		$result = array( 'msg' => null, 'uploaded' => false );

			$this->getUploadedFormFile( 'uploaded-metadata' );
			$result = $this->saveFile();

		return $result['msg'];

	}


}