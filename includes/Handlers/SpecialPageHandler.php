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
namespace	GWToolset\Handlers;
use			SpecialPage;


abstract class SpecialPageHandler {


	/**
	 * @var SpecialPage
	 */
	protected $SpecialPage;


	abstract public function execute();
	abstract protected function processRequest();


	public function __construct( SpecialPage &$SpecialPage ) {

		$this->SpecialPage = $SpecialPage;

	}


}