<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Ajax;
use Exception,
	GWToolset\Handlers\SpecialPageHandler,
	GWToolset\Helpers\WikiChecks,
	Status;

abstract class AjaxHandler extends SpecialPageHandler {

	/**
	 * gets an html form.
	 * not needed in this class
	 *
	 * @return {void}
	 */
	public function getHtmlForm() {}

	/**
	 * entry point
	 * a control method that acts as an entry point for the
	 * SpecialPageHandler and handles execution of the class methods
	 *
	 * @return {void}
	 */
	public function execute() {
		$result = '{}';

		try {
			WikiChecks::doesEditTokenMatch( $this->SpecialPage );
			$result = $this->processRequest();
		} catch ( Exception $e ) {
			$result = Status::newFatal( $e->getMessage() );
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode( $result );
		exit();
	}

}
