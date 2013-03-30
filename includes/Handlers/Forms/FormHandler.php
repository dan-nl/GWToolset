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
use	Exception,
	GWToolset\Handlers\SpecialPageHandler,
	GWToolset\Helpers\WikiChecks;


abstract class FormHandler extends SpecialPageHandler {


	protected $_user_options;


	protected function checkForRequiredFormFields( array $expected_options = array() ) {

		$msg = null;

		foreach( $expected_options as $option ) {

			if ( !isset( $this->_user_options[ $option ] ) ) {

				$msg .= '<li>' . $option . '</li>';

			}

		}

		if ( !is_null( $msg ) ) {

			$msg =
				'<p class="error">' . wfMessage('gwtoolset-metadata-user-options-error') . '</p>' .
				'<ul>' . $msg . '</ul>' .
				'<p>' . $this->_SpecialPage->getBackToFormLink() . '</p>';

			throw new Exception( $msg );

		}

	}


	protected function getFormClass( $module_name ) {

		if ( is_null( $module_name ) ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('no module name was specified') );

		}

		return $module_name['form'];

	}


	/**
	 * @return string an html form
	 */
	public function getHtmlForm( $module_name = null ) {

		$form_class = $this->getFormClass( $module_name );

		if ( !class_exists( $form_class ) ) {

			throw new Exception( wfMessage('gwtoolset-no-form') );

		}

		return $form_class::getForm( $this->_SpecialPage->getContext() );

	}


	public function execute() {

		$result = null;

			WikiChecks::doesEditTokenMatch( $this->_SpecialPage );
			$result .= $this->processRequest();

		return $result;

	}


}