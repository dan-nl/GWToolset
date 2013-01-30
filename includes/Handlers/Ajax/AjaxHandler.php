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
namespace	GWToolset\Handlers\Ajax;
use			Exception,
			GWToolset\Config,
			GWToolset\Handlers\HandlerInterface,
			GWToolset\Helpers\FileChecks,
			GWToolset\Helpers\WikiChecks,
			Php\File,
			SpecialPage;


abstract class AjaxHandler implements HandlerInterface {


	/**
	 * implemented in child definition
	 */
	abstract protected function processAjax();


	public function execute() {

		$result = null;

			try {
	
				WikiChecks::doesEditTokenMatch( $this->SpecialPage );
				$result = $this->processAjax();
	
			} catch ( Exception $e ) {
	
				$result .=  '{ "status" : "error", "message" : "' . $e->getMessage() . '" }';
	
			}

		header('Content-Type: application/json; charset=utf-8');
		echo $result;
		exit();

	}


	public function __construct( SpecialPage &$SpecialPage ) {

		$this->SpecialPage = $SpecialPage;

	}


}