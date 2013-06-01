<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Jobs;
use Job,
	GWToolset\Handlers\Forms\MetadataMappingHandler,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlMappingHandler,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	GWToolset\MediaWiki\Api\Client,
	SpecialPage,
	User;

class UploadMetadataJob extends Job {

	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;

	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;

	/**
	 * @var GWToolset\Handlers\Forms\MetadataMappingHandler
	 */
	protected $__MetadataMappingHandler;

	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $_MWApiClient;

	/**
	 * GWToolset\Handlers\UploadHandler
	 */
	protected $_UploadHandler;

	/**
	 * @var array
	 */
	protected $_user_options;

	/**
	 * @var GWToolset\Handlers\Xml\XmlMappingHandler
	 */
	protected $_XmlMappingHandler;

	/**
	 * @var User
	 */
	protected $_User;

	public function __construct( $title, $params, $id = 0 ) {
		parent::__construct( 'gwtoolsetUploadMetadataJob', $title, $params, $id );
	}

	protected function validateParams() {
		$result = true;

		if ( empty( $this->params['username'] ) ) {
			error_log( __METHOD__ . ' : no $this->params[\'user\'] provided' . PHP_EOL );
			$result = false;
		}

		if ( empty( $this->params['post'] ) ) {
			error_log( __METHOD__ . ' : no $this->params[\'post\'] provided' . PHP_EOL );
			$result = false;
		}

		return $result;
	}

	/**
	 * die() seems to be the only way to stop the run from being eliminated from the job queue
	 * return false seems to do nothing
	 */
	public function run() {
		$result = false;

		if ( !$this->validateParams() ) {
			return $result;
		}

		$_POST = $this->params['post'];
		$this->_User = User::newFromName( $this->params['username'] );
		$this->_MetadataMappingHandler = new MetadataMappingHandler( null, $this->_User );

		try {
			$result = $this->_MetadataMappingHandler->processRequest();
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $result;
	}

}
