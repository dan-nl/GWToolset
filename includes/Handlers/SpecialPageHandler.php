<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers;
use SpecialPage;


abstract class SpecialPageHandler {


	/**
	 * @var SpecialPage
	 */
	protected $_SpecialPage;


	/**
	 * @var User
	 */
	protected $_User;


	abstract public function execute();
	abstract protected function processRequest();
	abstract public function getHtmlForm();


	public function __construct( SpecialPage $SpecialPage = null, User $User = null ) {

		$this->_SpecialPage = $SpecialPage;

		if ( !empty( $User ) ) {

			$this->_User = $User;

		} else {

			$this->_User = $this->_SpecialPage->getUser();

		}

	}


}