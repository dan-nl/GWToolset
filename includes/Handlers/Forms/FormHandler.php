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
use			Exception,
			GWToolset\Config,
			GWToolset\Handlers\HandlerInterface,
			GWToolset\Helpers\FileChecks,
			GWToolset\Helpers\WikiChecks,
			Php\File,
			SpecialPage;


abstract class FormHandler implements HandlerInterface {


	/**
	 * @var SpecialPage
	 */
	protected $SpecialPage;


	/**
	 * @param string $mediawiki_template
	 * @throws Exception
	 * @return string
	 */
	protected function getValidMediaWikiTemplate( &$mediawiki_template = null ) {

		$template = null;

		if ( array_key_exists( $mediawiki_template, Config::$allowed_templates ) ) {

			$template = Config::$allowed_templates[$mediawiki_template];

		} else {

			throw new Exception( wfMessage('gwtoolset-metadata-invalid-template') );

		}

		return $template;

	}


	protected function validateUserOptions( array $expected_options = array(), array &$user_options = array() ) {

		$msg = null;

		foreach( $expected_options as $option ) {

			if ( !isset( $user_options[ $option ] ) ) {

				$msg .= '<li>' . $option . '</li>';

			}

		}

		if ( !is_null( $msg ) ) {

			$msg =
				'<p class="error">' .
					wfMessage('gwtoolset-metadata-user-options-error') .
				'</p>' .
				'<ul>' . $msg . '</ul>' .
				'<p><a href="/Special:GWToolset?gwtoolset-form=' . $this->SpecialPage->module_key . '">' . wfMessage( 'gwtoolset-back-to-form' ) . '</a></p>';

			throw new Exception( $msg );

		}

	}


	protected function getFormClass( $module_name ) {

		if ( is_null( $module_name ) ) {

			throw new Exception( wfMessage('gwtoolset-no-module-name') );

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

		return $form_class::getForm( $this->SpecialPage->getContext() );

	}


	public function __construct( SpecialPage &$SpecialPage ) {

		$this->SpecialPage = $SpecialPage;

	}


}