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
	GWToolset\Config,
	GWToolset\Forms\MetadataMappingForm,
	GWToolset\GWTException,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlDetectHandler,
	GWToolset\Helpers\GWTFileBackend,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	FSFile,
	Php\File;

class MetadataDetectHandler extends FormHandler {

	/**
	 * @var {GWToolset\Helpers\GWTFileBackend}
	 */
	protected $_GWTFileBackend;

	/**
	 * @var {GWToolset\Models\Mapping}
	 */
	protected $_Mapping;

	/**
	 * @var {GWToolset\Models\MediawikiTemplate}
	 */
	protected $_MediawikiTemplate;

	/**
	 * @var {GWToolset\Handlers\UploadHandler}
	 */
	protected $_UploadHandler;

	/**
	 * @var {GWToolset\Handlers\Xml\XmlDetectHandler}
	 */
	public $XmlDetectHandler;

	/**
	 * a control method that returns an html string that is comprised
	 * of table rows.
	 *
	 * each table row consists of a mediawiki template parameter,
	 * based on the mediawiki template selected in step 1, and an
	 * html <select> that contains <option>s derived from
	 * evaluating a metadata file provided in step 1. the options
	 * represent elements found within the metadata file and will
	 * be used by the user to map mediawiki template parameters to
	 * the metadata elements within the metadata file. if a pre-defined
	 * Mapping is provided, it will be used to pre-select matching
	 * mediawiki template parameters with metadata elements in the
	 * <select>s
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @return {string}
	 * the values within the table rows have been filtered
	 */
	public function getMetadataAsHtmlSelectsInTableRows( array &$user_options ) {
		$result = null;

		foreach ( $this->_MediawikiTemplate->mediawiki_template_array as $parameter => $value ) {
			$result .= $this->XmlDetectHandler->getMetadataAsTableCells(
				$parameter,
				$this->_MediawikiTemplate,
				$this->_Mapping
			);
		}

		return $result;
	}

	/**
	 * gets various user options from $_POST and sets default values
	 * if no user value is supplied
	 *
	 * @return {array}
	 * the values within the array have not been filtered
	 */
	protected function getUserOptions() {
		return array(
			'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] )
				? $_POST['mediawiki-template-name']
				: null,

			'metadata-file-url' => !empty( $_POST['metadata-file-url'] )
				? urldecode( $_POST['metadata-file-url'] )
				: null,

			'metadata-mapping-url' => !empty( $_POST['metadata-mapping-url'] )
				? urldecode( $_POST['metadata-mapping-url'] )
				: null,

			'metadata-stash-key' => null,

			'Metadata-Title' => null,

			'record-count' => 0,

			'record-element-name' => !empty( $_POST['record-element-name'] )
				? $_POST['record-element-name']
				: 'record',
		);
	}

	/**
	 * a control method that processes a SpecialPage request
	 * and returns a response, typically an html form
	 *
	 * - uploads a metadata file if provided and stores it in the wiki
	 * - retrieves the metadata file from the wiki
	 * - retrieves a metadata mapping if a url to it in the wiki is given
	 * - adds this information to the metadata mapping form and presents it to the user
	 *
	 * @throws {GWTException}
	 * @return {string}
	 * the html form string has not been filtered in this method,
	 * but within the getForm method
	 */
	protected function processRequest() {
		$result = null;
		$user_options = $this->getUserOptions();

		$this->checkForRequiredFormFields(
			$user_options,
			array(
				'record-element-name',
				'mediawiki-template-name',
				'record-count'
			)
		);

		$this->_GWTFileBackend = new GWTFileBackend( array( 'User' => $this->User ) );

		$this->_UploadHandler = new UploadHandler(
			array(
				'File' => new File(),
				'GWTFileBackend' => $this->_GWTFileBackend,
				'SpecialPage' => $this->SpecialPage,
				'User' => $this->User
			)
		);

		$this->XmlDetectHandler = new XmlDetectHandler(
			array(
				'SpecialPage' => $this->SpecialPage
			)
		);

		// upload the metadata file and get an mwstore reference to it
		$user_options['metadata-file-mwstore'] = $this->_UploadHandler->saveMetadataToFileBackend();

		// retrieve the metadata file, the FileBackend will return an FSFile object
		$FSFile = $this->_GWTFileBackend->retrieveFile( $user_options['metadata-file-mwstore'] );

		if ( !( $FSFile instanceof FSFile ) ) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params(
						__METHOD__ . ': ' .
						wfMessage( 'gwtoolset-fsfile-retrieval-failure' )
							->params( $user_options['metadata-file-mwstore'] )
							->parse()
					)
					->parse()
			);
		}

		$user_options['metadata-file-sha1'] = $FSFile->getSha1Base36();
		$this->XmlDetectHandler->processXml( $user_options, $FSFile->getPath() );

		$this->_MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplatePhpAdapter() );
		$this->_MediawikiTemplate->getMediaWikiTemplate( $user_options );

		$this->_Mapping = new Mapping( new MappingPhpAdapter() );
		$this->_Mapping->retrieve( $user_options );

		$result = MetadataMappingForm::getForm(
			$this,
			$user_options
		);

		return $result;
	}
}
