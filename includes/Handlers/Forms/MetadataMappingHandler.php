<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Forms;
use GWToolset\Adapters\Php\MappingPhpAdapter,
	GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
	GWToolset\Config,
	GWToolset\Forms\PreviewForm,
	GWToolset\Jobs\UploadMetadataJob,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlMappingHandler,
	GWToolset\Helpers\WikiPages,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	JobQueueGroup,
	Linker,
	Php\Filter,
	Revision,
	SpecialPage,
	Title,
	User,
	WikiPage;

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
	 * @var GWToolset\Handlers\Xml\XmlMappingHandler
	 */
	protected $_XmlMappingHandler;

	/**
	 * @return void
	 */
	protected function processMetadata( array &$user_options ) {
		$result = null;
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_UploadHandler = null;
		$this->_XmlMappingHandler = null;

		$this->_MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );
		$this->_MediawikiTemplate->getValidMediaWikiTemplate( $user_options );

		$this->_Mapping = new Mapping( new MappingPhpAdapter() );
		$this->_Mapping->mapping_array = $this->_MediawikiTemplate->getMappingFromArray( $_POST );
		$this->_Mapping->setTargetElements();
		$this->_Mapping->reverseMap();

		$this->_UploadHandler = new UploadHandler(
			array(
				'Mapping' => $this->_Mapping,
				'MediawikiTemplate' => $this->_MediawikiTemplate,
				'User' => $this->User,
			)
		);

		$Metadata_Title = WikiPages::getTitleFromUrl( $user_options['metadata-file-url'] );
		$Metadata_Page = new WikiPage( $Metadata_Title );
		$Metadata_Content = $Metadata_Page->getContent( Revision::RAW );

		$this->_XmlMappingHandler = new XmlMappingHandler(
			array(
				'Mapping' => $this->_Mapping,
				'MediawikiTemplate' => $this->_MediawikiTemplate,
				'MappingHandler' => $this
			)
		);

		$result = $this->_XmlMappingHandler->processXml( $user_options, $Metadata_Content );

		if ( empty( $this->SpecialPage )
				&& $user_options['record-count'] > ( $user_options['record-begin'] + Config::$job_throttle )
		) {
			// when $this->SpecialPage is empty this method is being run by a wiki job
			// if more metadata records exist in the metadata file, create another UploadMetadataJob
			$_POST['record-begin'] = (int) $user_options['record-count'];
			$this->createMetadataBatchJob( $user_options );
		}

		return $result;

	}

	protected function createMetadataBatchJob( array &$user_options ) {
		$result = false;

		$job = new UploadMetadataJob(
			Title::newFromText( 'User:' . $this->User->getName() . '/' . Config::$name . ' Metadata Batch Job' ),
			array(
				'username' => $this->User->getName(),
				'user_options' => $user_options,
				'post' => $_POST
			)
		);

		$result = JobQueueGroup::singleton()->push( $job );

		if ( $result ) {
			$result = wfMessage( 'gwtoolset-batchjob-metadata-created' )->rawParams(
				Linker::link( Title::newFromText('Special:NewFiles'), null, array( 'target' => '_blank' ) )
			)->parse();
		} else {
			$result =  '<span class="error">' . wfMessage( 'gwtoolset-developer-issue' )->params(
				wfMessage( 'gwtoolset-batchjob-metadata-creation-failure' )->escaped()
			)->parse() . '</span>';
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
	public function processMatchingElement( array &$user_options, $element_mapped_to_mediawiki_template, $metadata_raw ) {
		$result = null;

		$this->_MediawikiTemplate->metadata_raw = $metadata_raw;
		$this->_MediawikiTemplate->populateFromArray( $element_mapped_to_mediawiki_template );
		$result = $this->_UploadHandler->saveMediaFile( $user_options );

		if ( $user_options['preview'] && ( $result instanceof Title ) ) {
			$result = '<li>' . Linker::link( $result, null, array( 'target' => '_blank' ) ) . '</li>';
		}

		return $result;
	}

	protected function getGlobalCategories( array &$user_options ) {
		$user_options['categories'] = Config::$mediawiki_template_default_category;

		if ( isset( $_POST['category'] ) ) {
			foreach( $_POST['category'] as $category ) {
				$category = Filter::evaluate( $category );
				if ( !empty( $category ) ) {
					$user_options['categories'] .= Config::$category_separator . $category;
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
			'categories' => null,
			'category-phrase' => !empty( $_POST['category-phrase'] ) ? $_POST['category-phrase'] : array(),
			'category-metadata' => !empty( $_POST['category-metadata'] ) ? $_POST['category-metadata'] : array(),
			'comment' => !empty( $_POST['wpSummary'] ) ? Filter::evaluate( $_POST['wpSummary'] ) : '',
			'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] ) ? Filter::evaluate( $_POST['mediawiki-template-name'] ) : null,
			'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? urldecode( Filter::evaluate( $_POST['metadata-file-url'] ) ) : null,
			'partner-template-url' => !empty( $_POST['partner-template-url'] ) ? urldecode( Filter::evaluate( $_POST['partner-template-url'] ) ) : null,
			'preview' => !empty( $_POST['gwtoolset-preview'] ) ? true : false,
			'record-count' => 0,
			'record-begin' => !empty( $_POST['record-begin'] ) ? (int) $_POST['record-begin'] : 0,
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
			'save-as-batch-job' => !empty( $_POST['save-as-batch-job'] ) ? (bool)Filter::evaluate( $_POST['save-as-batch-job'] ) : false,
			'title_identifier' => !empty( $_POST['title_identifier'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'title_identifier' ) ) : null,
			'upload-media' => !empty( $_POST['upload-media'] ) ? (bool)Filter::evaluate( $_POST['upload-media'] ) : false,
			'url_to_the_media_file' => !empty( $_POST['url_to_the_media_file'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'url_to_the_media_file' ) ) : null
		);

		if ( !empty( $result['partner-template-url'] ) ) {
			$result['partner-template-name'] = WikiPages::getTitleFromUrl( $result['partner-template-url'] );
		}

		return $result;
	}

	/**
	 * entry point
	 * @return string
	 */
	public function processRequest() {
		$result = null;
		$user_options = $this->getUserOptions();
		$this->getGlobalCategories( $user_options );

		$this->checkForRequiredFormFields(
			$user_options,
			array(
				'mediawiki-template-name',
				'metadata-file-url',
				'record-count',
				'record-element-name',
				'title_identifier',
				'url_to_the_media_file'
			)
		);

		if ( $user_options['preview'] ) {
			Config::$job_throttle = Config::$preview_throttle;
			$result = $this->processMetadata( $user_options );

			$result = PreviewForm::getForm(
				$this->SpecialPage->getContext(),
				$user_options,
				$result
			);
		} else {
			$user_options['save-as-batch-job'] = true;

			if ( !empty( $this->SpecialPage ) ) {
				// this is the creation of the initial uploadMetadataJob
				// subsequent uploadMetadataJobs are created in $this->processMetadata() if necessary
				$result = wfMessage('gwtoolset-step-4-heading')->parse() . $this->createMetadataBatchJob( $user_options );
			} else {
				// when $this->SpecialPage is empty this method is being run by a wiki job
				// assumption is that this is an uploadMediafileJob
				$result = $this->processMetadata( $user_options );
			}
		}

		return $result;
	}

}
