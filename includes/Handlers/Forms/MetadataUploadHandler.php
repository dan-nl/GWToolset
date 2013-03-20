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
namespace GWToolset\Handlers\Forms;
use	GWToolset\Handlers\UploadHandler;


class MetadataUploadHandler extends FormHandler {


	/**
	 * GWToolset\Handlers\UploadHandler
	 */
	protected $_UploadHandler;


	/**
	 * @todo: how to deal with the security risk of opening up the app to other
	 * extension formats and no currently allowed and bypassing uploadbase's
	 * check for that
	 *
	 * @return {string}
	 */
	public function processRequest() {

		$result = array( 'msg' => null, 'uploaded' => false );

			$this->_UploadHandler = new UploadHandler( $this->_SpecialPage );
			$this->_UploadHandler->getUploadedFileFromForm( 'metadata-file-upload' );
			$result = $this->_UploadHandler->saveFile();

		return $result['msg'];

	}


}