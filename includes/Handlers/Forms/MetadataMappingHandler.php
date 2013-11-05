<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */

namespace GWToolset\Handlers\Forms;
use GWToolset\Adapters\Php\MappingPhpAdapter,
	GWToolset\Adapters\Php\MediawikiTemplatePhpAdapter,
	GWToolset\Adapters\Php\MetadataPhpAdapter,
	GWToolset\Config,
	GWToolset\Forms\PreviewForm,
	GWToolset\GWTException,
	GWToolset\Jobs\UploadMetadataJob,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlMappingHandler,
	GWToolset\Helpers\WikiPages,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	GWToolset\Models\Metadata,
	Html,
	JobQueueGroup,
	Linker,
	Php\Filter,
	Revision,
	SpecialPage,
	Title,
	UploadStashFile,
	User,
	WikiPage;

class MetadataMappingHandler extends FormHandler {

	/**
	 * @var {Mapping}
	 */
	protected $_Mapping;

	/**
	 * @var {MediawikiTemplate}
	 */
	protected $_MediawikiTemplate;

	/**
	 * @var {Metadata}
	 */
	protected $_Metadata;

	/**
	 * @var {UploadHandler}
	 */
	protected $_UploadHandler;

	/**
	 * @var {XmlMappingHandler}
	 */
	protected $_XmlMappingHandler;

	/**
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @return {string}
	 * the html string has been escaped and parsed by wfMessage
	 */
	protected function createMetadataBatchJob( array &$user_options ) {
		$result = false;

		$job = new UploadMetadataJob(
			Title::newFromText(
				$this->User->getName() . '/' .
				Config::$name . '/' .
				'Metadata Batch Job/' .
				uniqid(),
				NS_USER
			),
			array(
				'post' => $_POST,
				'user-name' => $this->User->getName(),
				'user-options' => $user_options
			)
		);

		$result = JobQueueGroup::singleton()->push( $job );

		if ( $result ) {
			$newFilesLink = Linker::link(
				Title::newFromText( 'Special:NewFiles' ),
				null,
				array( 'target' => '_blank' )
			);

			$result = wfMessage( 'gwtoolset-batchjob-metadata-created' )
				->rawParams( $newFilesLink )
				->parse();
		} else {
			$result = Html::rawElement(
				'span',
				array( 'class' => 'error' ),
				wfMessage( 'gwtoolset-developer-issue' )->params(
					wfMessage( 'gwtoolset-batchjob-metadata-creation-failure' )->escaped()
				)->parse()
			);
		}

		return $result;
	}

	/**
	 * sets the $user_options['categories'] index as appropriate.
	 * this value is used by UploadHandler to add global categories
	 * to a medifile wiki page; global meaning that these categories
	 * are added to all mediafiles that are being uploaded
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 */
	protected function getGlobalCategories( array &$user_options ) {
		$user_options['categories'] = Config::$mediawiki_template_default_category;

		if ( isset( $_POST['category'] ) ) {
			foreach ( $_POST['category'] as $category ) {
				if ( !empty( $category ) ) {
					$user_options['categories'] .= Config::$category_separator . $category;
				}
			}
		}
	}

	/**
	 * gets various user options from $_POST and sets default values
	 * if no user value is supplied
	 *
	 * @return {array}
	 * the values within the array have not been filtered
	 */
	protected function getUserOptions() {
		$result = array(
			'categories' => null,

			'category-phrase' => !empty( $_POST['category-phrase'] )
				? $_POST['category-phrase']
				: array(),

			'category-metadata' => !empty( $_POST['category-metadata'] )
				? $_POST['category-metadata']
				: array(),

			'comment' => !empty( $_POST['wpSummary'] )
				? $_POST['wpSummary']
				: '',

			'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] )
				? $_POST['mediawiki-template-name']
				: null,

			'metadata-file-url' => !empty( $_POST['metadata-file-url'] )
				? urldecode( $_POST['metadata-file-url'] )
				: null,

			'metadata-stash-key' => !empty( $_POST['metadata-stash-key'] )
				? urldecode( $_POST['metadata-stash-key'] )
				: null,

			'partner-template-url' => !empty( $_POST['partner-template-url'] )
				? urldecode( $_POST['partner-template-url'] )
				: null,

			'preview' => !empty( $_POST['gwtoolset-preview'] )
				? true
				: false,

			'record-begin' => !empty( $_POST['record-begin'] )
				? (int)$_POST['record-begin']
				: 1,

			'record-count' => !empty( $_POST['record-count'] )
				? (int)$_POST['record-count']
				: 0,

			'record-current' => 0,

			'record-element-name' => !empty( $_POST['record-element-name'] )
				? $_POST['record-element-name']
				: 'record',

			'save-as-batch-job' => !empty( $_POST['save-as-batch-job'] )
				? (bool)$_POST['save-as-batch-job']
				: false,

			// Filter::evaluate is used here to extract the 'title-identifier' array
			'title-identifier' => !empty( $_POST['title-identifier'] )
				? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'title-identifier' ) )
				: null,

			'upload-media' => !empty( $_POST['upload-media'] )
				? (bool)$_POST['upload-media']
				: false,

			// Filter::evaluate is used here to extract the 'url-to-the-media-file' array
			'url-to-the-media-file' => !empty( $_POST['url-to-the-media-file'] )
				? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'url-to-the-media-file' ) )
				: null
		);

		if ( !empty( $result['partner-template-url'] ) ) {
			$result['partner-template-name'] = \GWToolset\getTitle(
				$result['partner-template-url'],
				NS_TEMPLATE,
				array( 'must-be-known' => false )
			);
		}

		return $result;
	}

	/**
	 * save a metadata record as a new/updated wiki page
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {array} $options
	 *   {array} $options['metadata-as-array']
	 *   {array} $options['metadata-mapped-to-mediawiki-template']
	 *   {string} $options['metadata-raw']
	 *
	 * @return {null|Title|bool}
	 */
	public function processMatchingElement( array &$user_options, array $options ) {
		$result = null;

		$this->_MediawikiTemplate->metadata_raw = $options['metadata-raw'];
		$this->_MediawikiTemplate->populateFromArray(
			$options['metadata-mapped-to-mediawiki-template']
		);

		$this->_Metadata->metadata_raw = $options['metadata-raw'];
		$this->_Metadata->metadata_as_array = $options['metadata-as-array'];

		if ( $user_options['save-as-batch-job'] ) {
			$result = $this->_UploadHandler->saveMediafileViaJob( $user_options, $options );
		} else {
			$result = $this->_UploadHandler->saveMediafileAsContent( $user_options );
		}

		return $result;
	}

	/**
	 * a control method that steps through the methods necessary
	 * for processing the metadata and mapping in order to create
	 * mediafile wiki pages
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @throws {GWTException}
	 * @return {array}
	 * an array of mediafile Title(s)
	 */
	protected function processMetadata( array &$user_options ) {
		$mediafile_titles = array();
		$UploadStashFile = null;
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_Metadata = null;
		$this->_UploadHandler = null;
		$this->_XmlMappingHandler = null;

		$this->_MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplatePhpAdapter() );
		$this->_MediawikiTemplate->getMediaWikiTemplate( $user_options );

		$this->_Mapping = new Mapping( new MappingPhpAdapter() );
		$this->_Mapping->mapping_array = $this->_MediawikiTemplate->getMappingFromArray( $_POST );
		$this->_Mapping->setTargetElements();
		$this->_Mapping->reverseMap();

		$this->_Metadata = new Metadata( new MetadataPhpAdapter() );

		$this->_UploadHandler = new UploadHandler(
			array(
				'Mapping' => $this->_Mapping,
				'MediawikiTemplate' => $this->_MediawikiTemplate,
				'Metadata' => $this->_Metadata,
				'User' => $this->User,
			)
		);

		$this->_XmlMappingHandler = new XmlMappingHandler(
			array(
				'Mapping' => $this->_Mapping,
				'MediawikiTemplate' => $this->_MediawikiTemplate,
				'MappingHandler' => $this
			)
		);

		if ( is_string( $user_options['metadata-stash-key'] ) ) {
			$UploadStashFile = $this->_UploadHandler->getMetadataFromStash( $user_options );
		} elseif ( is_string( $user_options['metadata-file-url'] ) ) {
			$Metadata_Title = \GWToolset\getTitle(
				$user_options['metadata-file-url'],
				Config::$metadata_namespace
			);
		}

		if ( $UploadStashFile instanceof UploadStashFile ) {
			$mediafile_titles = $this->_XmlMappingHandler->processXml(
				$user_options,
				$UploadStashFile->getLocalRefPath()
			);
		} elseif ( $Metadata_Title instanceof Title ) {
			$Metadata_Page = new WikiPage( $Metadata_Title );
			$Metadata_Content = $Metadata_Page->getContent( Revision::RAW );
			$mediafile_titles = $this->_XmlMappingHandler->processXml(
				$user_options,
				$Metadata_Content
			);
		} else {
			throw new GWTException(
				wfMessage( 'gwtoolset-metadata-file-url-not-present' )
					->params( $user_options['metadata-file-url'])
					->escaped()
			);
		}

		/**
		 * when $this->SpecialPage is empty this method is being run by a wiki job
		 * if more metadata records exist in the metadata file, create another
		 * UploadMetadataJob
		 */
		if ( empty( $this->SpecialPage )
			&& (int)$user_options['record-count']
			>= ( (int)$user_options['record-begin'] + (int)Config::$job_throttle )
		) {
			$_POST['record-begin'] = (int)$user_options['record-current'];
			$this->createMetadataBatchJob( $user_options );
		}

		return $mediafile_titles;
	}

	/**
	 * a control method that processes a SpecialPage request
	 * and returns a response, typically an html form
	 *
	 * @return {string|array}
	 * - an html form, which is filtered in the getForm method
	 * - an html response, which has been escaped and parsed by wfMessage
	 * - an array of mediafile Title(s)
	 */
	public function processRequest() {
		$result = null;
		$mediafile_titles = array();
		$user_options = $this->getUserOptions();
		$this->getGlobalCategories( $user_options );

		$this->checkForRequiredFormFields(
			$user_options,
			array(
				'mediawiki-template-name',
				'record-count',
				'record-element-name',
				'title-identifier',
				'url-to-the-media-file'
			)
		);

		if ( $user_options['preview'] === true ) {
			Config::$job_throttle = Config::$preview_throttle;
			$mediafile_titles = $this->processMetadata( $user_options );

			$result = PreviewForm::getForm(
				$this->SpecialPage->getContext(),
				$user_options,
				$mediafile_titles
			);
		} else {
			$user_options['save-as-batch-job'] = true;

			/**
			 * when $this->SpecialPage is not empty, this method is being run
			 * by a user as a SpecialPage, thus this is the creation of the
			 * initial uploadMetadataJob. subsequent uploadMetadataJobs are
			 * created in $this->processMetadata() when necessary.
			 */
			if ( !empty( $this->SpecialPage ) ) {
				$result =
					Html::rawElement(
						'h2',
						array(),
						wfMessage( 'gwtoolset-step-4-heading' )->escaped()
					) .
					$this->createMetadataBatchJob( $user_options );

			/**
			 * when $this->SpecialPage is empty, this method is being run
			 * by a wiki job; typically, uploadMediafileJob.
			 */
			} else {
				$result = $this->processMetadata( $user_options );
			}
		}

		return $result;
	}
}
