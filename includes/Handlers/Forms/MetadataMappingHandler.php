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
use	GWToolset\Adapters\Db\MappingDbAdapter,
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
	 * GWToolset\Handlers\UploadHandler
	 */
	protected $_UploadHandler;


	protected $_user_options;


	/**
	 * @var GWToolset\Handlers\Xml\XmlMappingHandler
	 */
	protected $_XmlMappingHandler;

	protected function processMetadata() {

		$wiki_file_path = null;
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_UploadHandler = null;
		$this->_XmlMappingHandler = null;

		$this->_MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );
		$this->_MediawikiTemplate->getValidMediaWikiTemplate( $this->_user_options );

		$this->_MWApiClient = \GWToolset\getMWApiClient(
			$this->_User->getName(),
			( Config::$display_debug_output && $this->_User->isAllowed( 'gwtoolset-debug' ) )
		);

		$this->_UploadHandler = new UploadHandler(
			array(
				'MediawikiTemplate' => $this->_MediawikiTemplate,
				'MWApiClient' => $this->_MWApiClient,
				'User' => $this->_User
			)
		);

		WikiPages::$MWApiClient = $this->_MWApiClient;
		$wiki_file_path = WikiPages::retrieveWikiFilePath( $this->_user_options['metadata-file-url'] );

		$this->_Mapping = new Mapping( new MappingDbAdapter() );
		$this->_Mapping->mapping_array = $this->_MediawikiTemplate->getMappingFromArray( $_POST );
		$this->_Mapping->setTargetElements();
		$this->_Mapping->reverseMap();

		$this->_XmlMappingHandler = new XmlMappingHandler( $this->_Mapping, $this->_MediawikiTemplate, $this );
		$this->_XmlMappingHandler->processXml( $this->_user_options, $wiki_file_path );

	}


	protected function createMetadataBatchJob() {

		global $wgArticlePath;
		$job = null;
		$job_result = false;
		$view_uploads = null;
		$result = null;

			$job = new UploadMetadataJob(
				Title::newFromText( 'User:' . $this->_SpecialPage->getUser()->getName() ),
				array(
					'user' => $this->_SpecialPage->getUser()->getName(),
					'user_options' => $this->_user_options,
					'post' => $_POST
				)
			);

			$job_result = JobQueueGroup::singleton()->push( $job );

			if ( $job_result ) {

				$view_uploads = '<a href="' . str_replace( '$1', 'Special:NewFiles', $wgArticlePath ) . '" target="_blank">Special:NewFiles</a>';
				$result = wfMessage('gwtoolset-batchjob-metadata-created')->rawParams( $view_uploads );

			} else {

				$result =  wfMessage('gwtoolset-developer-issue')->params('could not create batchjob for the metadata file') ;

			}

		return $result;

	}

	/**
	 * using the api save the matched record as a new wiki page or update an
	 * existing wiki page
	 *
	 * @todo how to deal with url_to_media_file when it is redirected to a file
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
	public function processMatchingElement( $element_mapped_to_mediawiki_template ) {

		$result = null;

			$this->_MediawikiTemplate->populateFromArray( $element_mapped_to_mediawiki_template );
			$result = $this->_UploadHandler->saveMediawikiTemplateAsPage( $this->_user_options );

		if ( is_string( $result ) ) {

			$this->result .= $result;

		} else if ( $result ) {

			$this->result = wfMessage('gwtoolset-batchjobs-item-created')->params( $this->_UploadHandler->jobs_added );

		} else {

			$this->result = wfMessage('gwtoolset-batchjobs-item-created-some')->params( $this->_UploadHandler->job_count, $this->_UploadHandler->jobs_not_added );

		}

	}


	/**
	 * grabs various user options set in an html form, filters them and sets
	 * default values where appropriate
	 *
	 * @return array
	 */
	protected function getUserOptions() {

		return array(
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
			'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] ) ? Filter::evaluate( $_POST['mediawiki-template-name'] ) : null,
			'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
			'record-count' => 0,
			'save-as-batch-job' => !empty( $_POST['save-as-batch-job'] ) ? (bool)Filter::evaluate( $_POST['save-as-batch-job'] ) : false,
			'comment' => !empty( $_POST['wpSummary'] ) ? Filter::evaluate( $_POST['wpSummary'] ) : '',
			'title_identifier' => !empty( $_POST['title_identifier'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'title_identifier' ) ) : null,
			'upload-media' => !empty( $_POST['upload-media'] ) ? (bool)Filter::evaluate( $_POST['upload-media'] ) : false,
			'url_to_the_media_file' => !empty( $_POST['url_to_the_media_file'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'url_to_the_media_file' ) ) : null,
			'categories' => !empty( $_POST['categories'] ) ? Filter::evaluate( $_POST['categories'] ) : null,
		);

	}


	/**
	 * @return {string} $result an html string
	 */
	public function processRequest() {

		$this->result = null;

			$this->_user_options = $this->getUserOptions();

			if ( !empty( $this->_user_options['categories'] ) ) {

				$this->_user_options['categories'] .= Config::$category_separator . Config::$mediawiki_template_default_category;

			} else {

				$this->_user_options['categories'] = Config::$mediawiki_template_default_category;

			}

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

			/**
			 * @todo: this process needs further review. feel that it could
			 * be better handled
			 */
			if ( $this->_user_options['save-as-batch-job'] ) {

				// assumption is that if SpecialPage is not empty then this is
				// the creation of the initial job queue job
				if ( !empty( $this->_SpecialPage ) ) {

					$this->result = $this->createMetadataBatchJob();

				// assumption is that this is a job queue job that will create the
				// mediafile upload jobs
				} else {

					$this->processMetadata();

				}

			} else {

				$this->processMetadata();

			}

		return $this->result;

	}


	/**
	 * allow parent constructor to be overridden so that this class can be used
	 * from a Job Queue job without a special page
	 */
	public function __construct( SpecialPage $SpecialPage = null, User $User = null ) {

		if ( !empty( $SpecialPage ) ) {

			parent::__construct( $SpecialPage, $User );

		} else if ( !empty( $User ) ) {

			$this->_User = $User;

		}

	}


}