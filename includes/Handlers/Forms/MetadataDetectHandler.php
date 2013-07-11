<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Forms;
use ContentHandler,
	Exception,
	GWToolset\Adapters\Php\MappingPhpAdapter,
	GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
	GWToolset\Config,
	GWToolset\Forms\MetadataMappingForm,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlDetectHandler,
	GWToolset\Helpers\WikiPages,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	Php\File,
	Php\Filter,
	Revision,
	WikiPage;

class MetadataDetectHandler extends FormHandler {

	/**
	 * @var Php\File
	 */
	protected $_File;

	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;

	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;

	/**
	 * GWToolset\Handlers\UploadHandler
	 */
	protected $_UploadHandler;

	/**
	 * @var GWToolset\Handlers\XmlDetectHandler
	 */
	public $XmlDetectHandler;

	/**
	 * returns an html string that is comprosed of table rows
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @throws Exception
	 *
	 * @return string
	 * an html select element representing the nodes in the xml file that will
	 * be used to match the attributes/properties in the wiki template
	 */
	public function getMetadataAsHtmlSelectsInTableRows( array &$user_options ) {
		$result = null;

		foreach( $this->_MediawikiTemplate->mediawiki_template_array as $parameter => $value ) {
			$result .= $this->XmlDetectHandler->getMetadataAsTableCells( $parameter, $this->_MediawikiTemplate, $this->_Mapping );
		}

		return $result;
	}

	/**
	 * grabs various user options set in an html form, filters them and sets
	 * default values where appropriate
	 *
	 * @return array
	 */
	protected function getUserOptions() {
		$result = array(
			'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] ) ? Filter::evaluate( $_POST['mediawiki-template-name'] ) : null,
			'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( urldecode( $_POST['metadata-file-url'] ) ) : null,
			'metadata-mapping-url' => !empty( $_POST['metadata-mapping-url'] ) ? Filter::evaluate( urldecode( $_POST['metadata-mapping-url'] ) ) : null,
			'record-count' => 0,
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
		);

		return $result;
	}

	/**
	 * a control function that steps through the methods necessary for processing the request
	 *
	 * 	- uploads a metadata file if provided and stores it in the wiki
	 * 	- retrieves the metadata file from the wiki
	 * 	- retrieves a metadata mapping if a url to it in the wiki is given
	 * 	- adds this information to the metadata mapping form and presents it to the user
	 *
	 * @todo possibly refactor form creation by sending it only the $options array
	 * @return {string} html content including the metadata mapping form
	 */
	protected function processRequest() {
		$result = null;
		$user_options = array();
		$this->_File = new File();
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_UploadHandler = null;
		$this->XmlDetectHandler = null;

		$user_options = $this->getUserOptions();

		$this->checkForRequiredFormFields(
			$user_options,
			array(
				'record-element-name',
				'mediawiki-template-name',
				'record-count'
			)
		);

		$this->_UploadHandler = new UploadHandler(
			array(
				'File' => new File,
				'SpecialPage' => $this->SpecialPage,
				'User' => $this->User
			)
		);

		$user_options['Metadata-Title'] = $this->_UploadHandler->getTitleFromUploadedFile( $user_options );
		$Metadata_Page = new WikiPage( $user_options['Metadata-Title'] );
		$Metadata_Content = $Metadata_Page->getContent( Revision::RAW );

		$this->XmlDetectHandler = new XmlDetectHandler(
			array(
				'SpecialPage' => $this->SpecialPage
			)
		);

		$this->XmlDetectHandler->processXml( $user_options, $Metadata_Content );

		$this->_MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );
		$this->_MediawikiTemplate->getValidMediaWikiTemplate( $user_options );

		$this->_Mapping = new Mapping( new MappingPhpAdapter() );
		$this->_Mapping->retrieve( $user_options );

		$result = MetadataMappingForm::getForm(
			$this,
			$user_options
		);

		return $result;
	}

}
