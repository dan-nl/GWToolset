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
	public $SpecialPage;

	/**
	 * @var User
	 */
	public $User;

	abstract public function execute();
	abstract protected function processRequest();
	abstract public function getHtmlForm();

	public function __construct( array $options = array() ) {
		if ( isset( $options['SpecialPage'] ) ) {
			$this->SpecialPage = $options['SpecialPage'];
		}

		if ( isset( $options['User'] ) ) {
			$this->User = $options['User'];
		} elseif ( isset( $this->SpecialPage ) ) {
			$this->User = $this->SpecialPage->getUser();
		}
	}

}
