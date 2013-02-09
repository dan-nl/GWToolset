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
use			GWToolset\Handlers\FileHandler;


class MetadataUploadHandler extends FormHandler {


	/**
	 * GWToolset\Handlers\FileHandler
	 */
	protected $FileHandler;


	/**
	 * @todo: how to deal with the security risk of opening up the app to other
	 * extension formats and no currently allowed and bypassing uploadbase's
	 * check for that
	 *
	 * @return {string}
	 */
	public function processRequest() {

		$result = array( 'msg' => null, 'uploaded' => false );

			$this->FileHandler = new FileHandler( $this->SpecialPage );
			$this->FileHandler->getUploadedFileFromForm( 'uploaded-metadata' );
			$result = $this->FileHandler->saveFile();

		return $result['msg'];

	}


}