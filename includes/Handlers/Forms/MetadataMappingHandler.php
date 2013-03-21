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
use	GWToolset\Config,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlMappingHandler,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	Php\Filter;


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

			$this->result = 'Batch jobs for (' . $this->_UploadHandler->jobs_added . ') item(s) have been created; these will process one at a time and a message will be added to your User page upon completeion (tbd).';

		} else {

			$this->result = 'Unfortunately not all items were added as batch jobs. Batch jobs were created for (' . $this->_UploadHandler->job_count . ') item(s); with ('. $this->_UploadHandler->jobs_not_added .') items having an issue. A developer will have to look into this issue for you.';

		}

	}


	/**
	 * @return {string} $result an html string
	 */
	protected function processRequest() {

		$this->result = null;
		$file_path_local = null;
		$this->_UploadHandler = null;
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_XmlMappingHandler = null;

			$this->_user_options = array(
				'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
				'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] ) ? Filter::evaluate( $_POST['mediawiki-template-name'] ) : null,
				'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
				'record-count' => 0,
				'save-as-batch-job' => !empty( $_POST['save-as-batch-job'] ) ? (bool)Filter::evaluate( $_POST['save-as-batch-job'] ) : false,
				'comment' => !empty( $_POST['wpSummary'] ) ? Filter::evaluate( $_POST['wpSummary'] ) : '',
				'title_identifier' => !empty( $_POST['title_identifier'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'title_identifier' ) ) : null,
				'upload-media' => !empty( $_POST['upload-media'] ) ? (bool)Filter::evaluate( $_POST['upload-media'] ) : false,
				'url_to_the_media_file' => !empty( $_POST['url_to_the_media_file'] ) ? Filter::evaluate( array( 'source' => $_POST, 'key-name' => 'url_to_the_media_file' ) ) : null
			);

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

			$this->_MediawikiTemplate = new MediawikiTemplate();
			$this->_MediawikiTemplate->getValidMediaWikiTemplate( $this->_user_options );

			$this->_MWApiClient = \GWToolset\getMWApiClient(
				$this->_SpecialPage->getUser()->getName(),
				( Config::$display_debug_output && $this->_SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' ) )
			);	

			$this->_UploadHandler = new UploadHandler(
				array(
					'MediawikiTemplate' => $this->_MediawikiTemplate,
					'MWApiClient' => $this->_MWApiClient,
					'SpecialPage' => $this->_SpecialPage
				)
			);

			$file_path_local = $this->_UploadHandler->retrieveLocalFilePath( $this->_user_options );

			$this->_Mapping = new Mapping();
			$this->_Mapping->mapping_array = $this->_MediawikiTemplate->getMappingFromArray();
			$this->_Mapping->setTargetElements();
			$this->_Mapping->reverseMap();

			$this->_XmlMappingHandler = new XmlMappingHandler( $this->_Mapping, $this->_MediawikiTemplate, $this );
			$this->_XmlMappingHandler->processXml( $this->_user_options, $file_path_local );

		return $this->result;

	}


}