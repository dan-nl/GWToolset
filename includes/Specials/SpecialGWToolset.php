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
			GWToolset\Models\Menu,
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
	 * @var GWToolset\HandlerInterface
	 */
	protected $Handler;


	protected $registered_modules = array(
		'metadata-upload' => array( 'handler' => '\GWToolset\Handlers\Forms\MetadataUploadHandler', 'form' => '\GWToolset\Forms\MetadataUploadForm' ),
		'metadata-detect' => array( 'handler' => '\GWToolset\Handlers\Forms\MetadataDetectHandler', 'form' => '\GWToolset\Forms\MetadataDetectForm' ),
		'metadata-mapping' => array( 'handler' => '\GWToolset\Handlers\Forms\MetadataMappingHandler', 'form' => '\GWToolset\Forms\MetadataMappingForm' ),
		'metadata-mapping-save' => array( 'handler' => '\GWToolset\Handlers\Ajax\MetadataMappingSaveHandler' )
	);


	public function getBackToFormLink() {

		return
			'<a href="' .
				$this->getContext()->getTitle()->getFullURL() .
				'?gwtoolset-form=' .
				$this->module_key . '"' .
				' onclick="history.back();return false;"' .
			'>' .
				wfMessage('gwtoolset-back-to-form') .
			'</a>';
		
	}


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

					$html .= $this->Handler->getHtmlForm( $this->registered_modules[$this->module_key] );

				} catch( Exception $e ) {

					$html .=
						'<h2>' . wfMessage( 'gwtoolset-technical-error' ) . '</h2>' .
						'<p class="error">' . $e->getMessage() . '</p>';

				}

			}

		} else {

			try {

				if ( !( $this->Handler instanceof \GWToolset\Handlers\SpecialPageHandler ) ) {

					$msg = wfMessage('gwtoolset-developer-issue')->params('no upload handler was created');

					if ( Config::$display_debug_output && $this->getUser()->isAllowed( 'gwtoolset-debug' ) ) {

						$msg .= '<br/><pre>' . print_r( error_get_last(), true ) . '</pre>';

					} else {

						$msg = wfMessage('gwtoolset-no-upload-handler');

					}

					throw new Exception( $msg );

				}

				$html .= $this->Handler->execute();
	
			} catch ( Exception $e ) {

				if ( $e->getCode() == 1000 ) {

					throw new PermissionsError( $e->getMessage() );

				} else {

					$html .=
						'<h2>' . wfMessage( 'gwtoolset-file-interpretation-error' ) . '</h2>' .
						'<p class="error">' . $e->getMessage() . '</p>';

				}

			}

		}

		$this->setHeaders();
		$this->getOutput()->addModules( 'ext.GWToolset' );
		$this->getOutput()->addHtml( Menu::getMenu() );
		$this->getOutput()->addHTML( $html );

	}


	protected function setModuleAndHandler() {

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
			$this->Handler = new $handler( $this );

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
		$this->setModuleAndHandler();
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

