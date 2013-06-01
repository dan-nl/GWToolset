<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Forms;
use GWToolset\Adapters\Api\MappingApiAdapter,
	GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
	GWToolset\Config,
	GWToolset\Jobs\UploadMetadataJob,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlMappingHandler,
	GWToolset\Helpers\WikiPages,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	JobQueueGroup,
	Php\Filter,
	SpecialPage,
	Title,
	User;

class MetadataMappingHandler extends FormHandler {

	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;

	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;

	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $_MWApiClient;

	/**
	 * @var GWToolset\Handlers\UploadHandler
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
	 * allow parent constructor to be overridden so that this class can be used
	 * from a Job Queue job without a special page
	 */
	public function __construct( SpecialPage $SpecialPage = null, User $User = null ) {
		if ( !empty( $SpecialPage ) ) {
			parent::__construct( $SpecialPage, $User );
		} elseif ( !empty( $User ) ) {
			$this->_User = $User;
		}
	}

	/**
	 * @return void
	 */
	protected function processMetadata() {
		$result = null;
		$wiki_file_path = null;
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_UploadHandler = null;
		$this->_XmlMappingHandler = null;

		$this->_MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );
		$this->_MediawikiTemplate->getValidMediaWikiTemplate( $this->_user_options );

		$this->_MWApiClient = \GWToolset\getMWApiClient(
			array( 'debug-on' => ( ini_get('display_errors') && $this->_User->isAllowed( 'gwtoolset-debug' ) ) )
		);

		WikiPages::$MWApiClient = $this->_MWApiClient;
		$wiki_file_path = WikiPages::retrieveWikiFilePath( $this->_user_options['metadata-file-url'] );

		$this->_Mapping = new Mapping( new MappingApiAdapter( $this->_MWApiClient ) );
		$this->_Mapping->mapping_array = $this->_MediawikiTemplate->getMappingFromArray( $_POST );
		$this->_Mapping->setTargetElements();
		$this->_Mapping->reverseMap();

		$this->_UploadHandler = new UploadHandler(
			array(
				'Mapping' => $this->_Mapping,
				'MediawikiTemplate' => $this->_MediawikiTemplate,
				'MWApiClient' => $this->_MWApiClient,
				'User' => $this->_User
			)
		);

		$this->_XmlMappingHandler = new XmlMappingHandler( $this->_Mapping, $this->_MediawikiTemplate, $this );
		$result = $this->_XmlMappingHandler->processXml( $this->_user_options, $wiki_file_path );

		if ( empty( $this->_SpecialPage )
				&& $this->_user_options['record-count'] > ( $this->_user_options['record-begin'] + Config::$job_throttle )
		) {
			$_POST['record-begin'] = (int) $this->_user_options['record-count'];
			$this->createMetadataBatchJob();
		}

		return $result;
	}

	protected function createMetadataBatchJob() {
		global $wgArticlePath;
		$job = null;
		$job_result = false;
		$view_uploads = null;
		$result = null;

		$job = new UploadMetadataJob(
			Title::newFromText( 'User:' . $this->_User->getName() . '/GWToolset Batch Upload' ),
			array(
				'username' => $this->_User->getName(),
				'user_options' => $this->_user_options,
				'post' => $_POST
			)
		);

		$job_result = JobQueueGroup::singleton()->push( $job );

		if ( $job_result ) {
			$view_uploads = '<a href="' . str_replace( '$1', 'Special:NewFiles', $wgArticlePath ) . '" target="_blank">Special:NewFiles</a>';
			$result = wfMessage( 'gwtoolset-batchjob-metadata-created' )->rawParams( $view_uploads )->parse();
		} else {
			$result =  wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-batchjob-creation-failure' )->plain() )->parse();
		}

		return $result;
	}

	/**
	 * using the api save the matched record as a new wiki page or update an
	 * existing wiki page
	 *
	 * @todo a. create filename - need to figure a better way to do it, possibly
	 * put it in the MediawikiTemplate instead of the UploadHandler
	 *
	 * @todo: have the api replace/update the template when page already exists
	 * @todo b. tell api to follow the redirect to get the file
	 *
	 * @param DOMElement $matching_element
	 * @param array $user_options
	 *
	 * @return {string}
	 * an html string with the <li> element results from the api createPage(),
	 * updatePage() calls plus $this->_MWApiClient->debug_html if gwtoolset-debuging
	 * is on and the user is a gwtoolset-debug user
	 *
	 * @todo run a try catch on the create/update page so that if there’s an api issue the script can continue
	 */
	public function processMatchingElement( $element_mapped_to_mediawiki_template, $metadata_raw ) {
		$result = null;

		$this->_MediawikiTemplate->metadata_raw = $metadata_raw;
		$this->_MediawikiTemplate->populateFromArray( $element_mapped_to_mediawiki_template );
		$result = $this->_UploadHandler->saveMediawikiTemplateAsPage( $this->_user_options );

		if ( is_string( $result ) ) {
			$this->result .= $result;
		} elseif ( $result ) {
			$this->result = wfMessage('gwtoolset-batchjobs-item-created')->params( $this->_UploadHandler->jobs_added )->plain();
		} else {
			$this->result = wfMessage('gwtoolset-batchjobs-item-created-some')->params( $this->_UploadHandler->job_count, $this->_UploadHandler->jobs_not_added )->plain();
		}

		return $result;
	}

	protected function getGlobalCategories() {
		$this->_user_options['categories'] = Config::$mediawiki_template_default_category;

		if ( isset( $_POST['category'] ) ) {
			foreach( $_POST['category'] as $category ) {
				$category = Filter::evaluate( $category );
				if ( !empty( $category ) ) {
					$this->_user_options['categories'] .= Config::$category_separator . $category;
				}
			}
		}
	}

	/**
	 * grabs various user options set in an html form, filters them and sets
	 * default values where appropriate
	 *
	 * @return array
	 */
	protected function getUserOptions() {
		$result = array(
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
			'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] ) ? Filter::evaluate( $_POST['mediawiki-template-name'] ) : null,
			'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
			'record-count' => 0,
			'record-begin' => isset( $_POST['record-begin'] ) ? (int) $_POST['record-begin'] : 0,
			'save-as-batch-job' => !empty( $_POST['save-as-batch-job'] ) ? (bool)Filter::evaluate( $_POST['save-as-batch-job'] ) : false,
			'comment' => !empty( $_POST['wpSummary'] ) ? Filter::evaluate( $_POST['wpSummary'] ) : '',
			'title_identifier' => !empty( $_POST['title_identifier'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'title_identifier' ) ) : null,
			'upload-media' => !empty( $_POST['upload-media'] ) ? (bool)Filter::evaluate( $_POST['upload-media'] ) : false,
			'url_to_the_media_file' => !empty( $_POST['url_to_the_media_file'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'url_to_the_media_file' ) ) : null,
			'categories' => null,
			'category-phrase' => !empty( $_POST['category-phrase'] ) ? $_POST['category-phrase'] : array(),
			'category-metadata' => !empty( $_POST['category-metadata'] ) ? $_POST['category-metadata'] : array(),
			'partner-template-url' => !empty( $_POST['partner-template-url'] ) ? Filter::evaluate( $_POST['partner-template-url'] ) : null,
		);

		if ( !empty( $result['partner-template-url'] ) ) {
			$result['partner-template-name'] = WikiPages::getTemplateNameFromUrl( $result['partner-template-url'] );
		}

		return $result;
	}

	/**
	 * entry point
	 * @return string
	 */
	public function processRequest() {
		$this->result = null;
		$this->_user_options = $this->getUserOptions();
		$this->getGlobalCategories();

		$this->checkForRequiredFormFields(
			array(
				'mediawiki-template-name',
				'metadata-file-url',
				'record-count',
				'record-element-name',
				'title_identifier',
				'url_to_the_media_file'
			)
		);

		if ( $this->_user_options['save-as-batch-job'] ) {
			// assumption is that if SpecialPage is not empty then this is
			// the creation of the initial job queue job
			if ( !empty( $this->_SpecialPage ) ) {
				$this->result = $this->createMetadataBatchJob();
			// assumption is that this is a job queue job that will create the
			// mediafile upload jobs
			} else {
				$this->result = $this->processMetadata();
			}
		} else {
			$this->result = $this->processMetadata();
		}

		return $this->result;
	}

}
