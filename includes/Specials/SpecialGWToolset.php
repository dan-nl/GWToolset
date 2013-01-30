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
namespace	GWToolset;
use			Exception,
			GWToolset\Menu,
			GWToolset\Helpers\WikiChecks,
			PermissionsError,
			Php\Filter,
			SpecialPage;


class SpecialGWToolset extends SpecialPage {


	/**
	 * @var string
	 */
	public $module_key;


	/**
	 * @var GWToolset\FormHandlers\FormHandler
	 */
	protected $FormHandler;


	protected $registered_modules = array(
		'base-upload' => array( 'handler' => '\GWToolset\Handlers\Forms\BaseUploadHandler', 'form' => '\GWToolset\Forms\BaseUploadForm' ),
		'metadata-detect' => array( 'handler' => '\GWToolset\Handlers\Forms\MetadataDetectHandler', 'form' => '\GWToolset\Forms\MetadataDetectForm' ),
		'metadata-mapping' => array( 'handler' => '\GWToolset\Handlers\Forms\MetadataMappingHandler', 'form' => '\GWToolset\Forms\MetadataMappingForm' ),
		'metadata-mapping-save' => array( 'handler' => '\GWToolset\Handlers\Ajax\MetadataMappingSaveHandler' ),
		'prototype-api' => array( 'handler' => '\GWToolset\Handlers\Forms\PrototypeApiHandler', 'form' => '\GWToolset\Forms\PrototypeApiForm' )
	);


	/**
	 * @throws PermissionsError
	 * @return void
	 */
	protected function processRequest() {

		$html = null;

		if ( !$this->getRequest()->wasPosted() ) {

			if ( is_null( $this->module_key ) ) {

				$html .=  wfMessage('gwtoolset-intro')->plain();

			} else {

				try {

					$html .= $this->FormHandler->getHtmlForm( $this->registered_modules[$this->module_key] );

				} catch( Exception $e ) {

					$html .=
						'<h2>' . wfMessage( 'gwtoolset-technical-error' ) . '</h2>' .
						'<p class="error">' . $e->getMessage() . '</p>';

				}

			}

		} else {
	
			try {

				if ( !( $this->FormHandler instanceof \GWToolset\Handlers\HandlerInterface ) ) {

					$msg = error_get_last();
					
					if ( Config::$display_debug_output && $this->SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' ) ) {

						$msg = wfMessage('gwtoolset-no-upload-handler') . print_r( $msg, true );

					} else {

						$msg = wfMessage('gwtoolset-no-upload-handler') . '<br/>' . $msg['message'];

					}

					throw new Exception( $msg );

				}

				$html .= $this->FormHandler->execute();
	
			} catch ( Exception $e ) {

				if ( $e->getCode() == 1000 ) {

					throw new PermissionsError( $e->getMessage() );

				} else {

					$html .=
						'<h2>' . wfMessage( 'gwtoolset-file-interpretation-error' ) . '</h2>' .
						'<p class="error">' . $e->getMessage() . '</p>' .
						'<a href="/Special:GWToolset?gwtoolset-form=' . $this->module_key . '">back to form</a>';

				}

			}

		}

		$this->setHeaders();
		$this->getOutput()->addModules( 'ext.GWToolset' );
		$this->getOutput()->addHtml( Menu::getMenu() );
		$this->getOutput()->addHTML( $html );

	}


	protected function setModuleAndFormHandler() {

		$this->module_key = null;

		if ( $this->getRequest()->wasPosted() ) {

			if ( isset( $_POST['gwtoolset-form'] ) && key_exists( $_POST['gwtoolset-form'], $this->registered_modules ) ) {

				$this->module_key = Filter::evaluate( $_POST['gwtoolset-form'] );

			}

		} else {

			if ( isset( $_GET['gwtoolset-form'] ) && key_exists( $_GET['gwtoolset-form'], $this->registered_modules ) ) {

				$this->module_key = Filter::evaluate( $_GET['gwtoolset-form'] );

			}

		}

		if ( !is_null( $this->module_key ) ) {

			$handler = $this->registered_modules[ $this->module_key ]['handler'];
			$this->FormHandler = new $handler( $this );

		}

	}


	/**
	 * @return boolean
	 */
	protected function wikiChecks() {

		try {

			if ( !WikiChecks::pageIsReadyForThisUser( $this ) ) { return; }

		} catch ( Exception $e ) {

			if ( $e->getCode() == 1000 ) {

				throw new PermissionsError( $e->getMessage() );

			} else {

				$this->getOutput()->addHTML(
					'<h2>' . wfMessage( 'gwtoolset-wiki-checks-not-passed' ) . '</h2>' .
					$e->getMessage() . '<br/>'
				);

			}

			return false;

		}

		return true;

	}


	/**
	 * SpecialPage entry point
	 */
	public function execute( $par ) {

		if ( !$this->wikiChecks() ) { return; }
		$this->setModuleAndFormHandler();
		$this->processRequest();

	}


	/**
	 * Default constructor for special pages
	 * Derivative classes should call this from their constructor
	 *     Note that if the user does not have the required level, an error message will
	 *     be displayed by the default execute() method, without the global function ever
	 *     being called.
	 *
	 *     If you override execute(), you can recover the default behaviour with userCanExecute()
	 *     and displayRestrictionError()
	 *
	 * @param $name String: name of the special page, as seen in links and URLs
	 * @param $restriction String: user right required, e.g. "block" or "delete"
	 * @param $listed Bool: whether the page is listed in Special:Specialpages
	 * @param $function Callback|Bool: function called by execute(). By default it is constructed from $name
	 * @param $file String: file which is included by execute(). It is also constructed from $name by default
	 * @param $includable Bool: whether the page can be included in normal pages
	 */
	public function __construct() {

		parent::__construct( Config::$special_page_name, Config::$restriction, Config::$listed );

	}


}

