<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Ajax;
use Exception,
	GWToolset\Handlers\SpecialPageHandler,
	GWToolset\Helpers\WikiChecks;

abstract class AjaxHandler extends SpecialPageHandler {

	public function getHtmlForm( $module_name = null ) {
		header('Content-Type: application/json; charset=utf-8');
		echo '{}';
		exit();
	}

	public function execute() {
		$result = null;

		try {
			WikiChecks::doesEditTokenMatch( $this->_SpecialPage );
			$result .= $this->processRequest();
		} catch ( Exception $e ) {
			$result .=  '{ "status" : "error", "message" : "' . $e->getMessage() . '" }';
		}

		header('Content-Type: application/json; charset=utf-8');
		echo $result;
		exit();
	}

}
