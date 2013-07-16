<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset;
use Exception,
	GWToolset\Handlers\SpecialPageHandler,
	GWToolset\Models\Menu,
	GWToolset\Helpers\WikiChecks,
	Linker,
	PermissionsError,
	Php\Filter,
	SpecialPage,
	Title,
	JobQueueGroup;

class SpecialGWToolset extends SpecialPage {

	/**
	 * @var {string}
	 */
	public $module_key;

	/**
	 * @var {SpecialPageHandler}
	 */
	protected $_Handler;

	/**
	 * @var {array}
	 */
	protected $_registered_modules = array(
		'metadata-detect' => array(
			'handler' => '\GWToolset\Handlers\Forms\MetadataDetectHandler',
			'form' => '\GWToolset\Forms\MetadataDetectForm'
		),
		'metadata-mapping' => array(
			'handler' => '\GWToolset\Handlers\Forms\MetadataMappingHandler',
			'form' => '\GWToolset\Forms\MetadataMappingForm'
		),
		'metadata-mapping-save' => array(
			'handler' => '\GWToolset\Handlers\Ajax\MetadataMappingSaveHandler'
		)
	);

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

	/**
	 * @return {string}
	 */
	public function getBackToFormLink() {
		return '<span id="back-text"><noscript>' . wfMessage( 'gwtoolset-back-text' )->escaped() . '</noscript>&nbsp;</span>';
	}

	/**
	 * a control method that processes a SpecialPage request
	 * and returns a response, typically an html form
	 *
	 * @throws {PermissionsError|Exception}
	 *
	 * @return {void}
	 * SpecialPage>Output is used to take care of the response
	 */
	protected function processRequest() {
		$html = null;

		if ( !$this->getRequest()->wasPosted() ) {
			if ( $this->module_key === null ) {
				$html .= wfMessage( 'gwtoolset-intro' )->parseAsBlock();
			} else {
				try {
					$html .= $this->_Handler->getHtmlForm( $this->_registered_modules[$this->module_key] );
				} catch ( Exception $e ) {
					$html .=
						wfMessage( 'gwtoolset-technical-error' )->parse() .
						'<p class="error">' . Filter::evaluate( $e->getMessage() ) . '</p>';
				}
			}
		} else {
			try {
				if ( !( $this->_Handler instanceof \GWToolset\Handlers\SpecialPageHandler ) ) {
					$msg = wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-upload-handler' )->escaped() )->parse();
					if ( ini_get( 'display_errors' ) && $this->getUser()->isAllowed( 'gwtoolset-debug' ) ) {
						$msg .= '<br /><pre>' . print_r( error_get_last(), true ) . '</pre>';
					} else {
						$msg = wfMessage( 'gwtoolset-no-upload-handler' )->escaped();
					}

					throw new Exception( $msg );
				}

				$html .= $this->_Handler->execute();
			} catch ( Exception $e ) {
				if ( $e->getCode() == 1000 ) {
					throw new PermissionsError( $e->getMessage() );
				} else {
					$html .=
						wfMessage( 'gwtoolset-file-interpretation-error' )->parse() .
						'<p class="error">' . $e->getMessage() . '</p>';
				}
			}
		}

		$this->setHeaders();
		$this->getOutput()->addModules( 'ext.GWToolset' );
		$this->getOutput()->addHtml(
			wfMessage( 'gwtoolset-menu' )->rawParams(
				Linker::link(
					Title::newFromText( 'Special:' . Config::$name ),
					wfMessage( 'gwtoolset-menu-1' )->escaped(),
					array(),
					array( 'gwtoolset-form' => 'metadata-detect' )
				)
			)->parse()
		);
		$this->getOutput()->addHtml( $html );
	}

	/**
	 * @return {void}
	 */
	protected function setModuleAndHandler() {
		$this->module_key = null;
		$gwtoolset_form = $this->getRequest()->getVal( 'gwtoolset-form' );

		if ( key_exists( $gwtoolset_form, $this->_registered_modules ) ) {
			$this->module_key = $gwtoolset_form;
		}

		if ( $this->module_key !== null ) {
			$handler = $this->_registered_modules[$this->module_key]['handler'];
			$this->_Handler = new $handler( array( 'SpecialPage' => $this ) );
		}
	}

	/**
	 * @return {bool}
	 */
	protected function wikiChecks() {
		try {
			if ( !WikiChecks::pageIsReadyForThisUser( $this ) ) {
				return false;
			}
		} catch ( Exception $e ) {
			if ( $e->getCode() == 1000 ) {
				throw new PermissionsError( $e->getMessage() );
			} else {
				$this->getOutput()->addHTML(
					wfMessage( 'gwtoolset-wiki-checks-not-passed' )->parse() .
					$e->getMessage() . '<br />'
				);
			}

			return false;
		}

		return true;
	}

	/**
	 * entry point
	 * a control method that acts as an entry point for the
	 * SpecialPage and handles execution of the class methods
	 *
	 * @return {void}
	 */
	public function execute( $par ) {
		set_error_handler( '\GWToolset\handleError' );

		if ( !$this->wikiChecks() ) {
			return;
		}

		$this->setModuleAndHandler();
		$this->processRequest();
	}
}
