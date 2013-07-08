<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Forms;
use Exception,
	GWToolset\Handlers\SpecialPageHandler,
	GWToolset\Helpers\WikiChecks;

abstract class FormHandler extends SpecialPageHandler {

	/**
	 * make sure the expected options :
	 * 1. exist
	 * 2. and have a value with strlen > 0
	 *
	 * @param {array} $user_options
	 * @param {array} $expected_options
	 * @return {void}
	 * @throws Exception
	 */
	protected function checkForRequiredFormFields( array &$user_options, array $expected_options ) {
		$msg = null;

		foreach( $expected_options as $option ) {
			if ( !array_key_exists( $option, $user_options ) ) {
				$msg .= '<li>' . $option . '</li>';
			}

			if ( is_array( $user_options[ $option ] ) ) {
				if ( strlen( reset( $user_options[ $option ] ) ) < 1 ) {
					$msg .= '<li>' . $option . '</li>';
				}
			} else {
				if ( strlen( $user_options[ $option ] ) < 1 ) {
					$msg .= '<li>' . $option . '</li>';
				}
			}
		}

		if ( $msg !== null ) {
			$msg =
				'<p class="error">' . wfMessage( 'gwtoolset-metadata-user-options-error' )->escaped() . '</p>' .
				'<ul>' . $msg . '</ul>' .
				'<p>' . $this->SpecialPage->getBackToFormLink() . '</p>';

			throw new Exception( $msg );
		}
	}

	protected function getFormClass( $module_name ) {
		if ( $module_name === null ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-module' )->escaped() )->parse() );
		}

		return $module_name['form'];
	}

	/**
	 * @return string an html form
	 */
	public function getHtmlForm( $module_name = null ) {
		$form_class = $this->getFormClass( $module_name );

		if ( !class_exists( $form_class ) ) {
			throw new Exception( wfMessage( 'gwtoolset-no-form' )->escaped() );
		}

		return $form_class::getForm( $this->SpecialPage );
	}

	public function execute() {
		$result = null;

		WikiChecks::doesEditTokenMatch( $this->SpecialPage );
		$result .= $this->processRequest();

		return $result;
	}

}
