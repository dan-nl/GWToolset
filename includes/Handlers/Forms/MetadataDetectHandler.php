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
use Exception,
	GWToolset\Adapters\Api\MappingApiAdapter,
	GWToolset\Adapters\Db\MappingDbAdapter,
	GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
	GWToolset\Config,
	GWToolset\Forms\MetadataMappingForm,
	GWToolset\Handlers\UploadHandler,
	GWToolset\Handlers\Xml\XmlDetectHandler,
	GWToolset\Helpers\WikiPages,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	Php\File,
	Php\Filter;


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
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $_MWApiClient;


	/**
	 * GWToolset\Handlers\UploadHandler
	 */
	protected $_UploadHandler;


	/**
	 * @var GWToolset\Handlers\XmlDetectHandler
	 */
	protected $_XmlDetectHandler;


	/**
	 *	returns an html string that is comprosed of table rows
	 *
	 * @param {array} $this->_user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @throws Exception
	 *
	 * @return string
	 * an html select element representing the nodes in the xml file that will
	 * be used to match the attributes/properties in the wiki template
	 */
	protected function getMetadataAsHtmlSelectsInTableRows( array &$user_options ) {

		$result = null;

		if ( !isset( $user_options['mediawiki-template-name'] ) ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('no mediawiki-template-name provided') );

		}

		$this->_MediawikiTemplate->getValidMediaWikiTemplate( $user_options );
		$this->_Mapping->retrieve( $user_options );

		if ( !empty( $user_options['metadata-mapping'] ) && empty( $this->_Mapping->mapping_array ) ) {

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-not-found')->rawParams( Filter::evaluate( $user_options['metadata-mapping'] ) ) );

		}

		foreach( $this->_MediawikiTemplate->mediawiki_template_array as $parameter => $value ) {

			$result .= $this->_XmlDetectHandler->getMetadataAsTableCells( $parameter, $this->_MediawikiTemplate, $this->_Mapping );

		}

		return $result;

	}


	/**
	 * if the html form does not contain a url to the metadata file in the local
	 * wiki, the script assumes that a metadata file was uploaded via $_FILES[]
	 *
	 * if the form contains both a url to the metadata file in the local wiki
	 * and a reference to a local file being uploaded, the script will prefer
	 * the local wiki file and ignore the upload
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $metadata_file_url
	 * the key within $user_options that holds the url to the metadata file
	 * stored in the local wiki
	 *
	 * @param {string} $metadata_file_upload
	 * the key within the $user_options that holds the key-name expected in
	 * $_FILES[] when the metadata file has been uploaded via an html form
	 *
	 * @param {array} $user_options
	 * @return {string}
	 */
	protected function getUploadedFile( array &$user_options, $metadata_file_url = 'metadata-file-url', $metadata_file_upload = 'metadata-file-upload' ) {

		$result = array( 'msg' => null, 'uploaded' => false );

			if ( !isset( $user_options[ $metadata_file_url ] ) && !isset( $_FILES[ $metadata_file_upload ] ) ) {

				throw new Exception( wfMessage('gwtoolset-metadata-file-url-not-present') );

			}

			if ( !empty( $user_options[ $metadata_file_url ] ) ) {

				return $result['msg'];

			}

			$this->_UploadHandler->getUploadedFileFromForm( $metadata_file_upload );
			$result = $this->_UploadHandler->saveMetadataFile();

			if ( !$result['uploaded'] ) {

				throw new Exception( $result['msg'] );

			}

			$user_options[ $metadata_file_url ] = $this->_UploadHandler->getSavedFileName();

		return $result['msg'];

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
			'metadata-mapping' => !empty( $_POST['metadata-mapping'] ) ? Filter::evaluate( $_POST['metadata-mapping'] ) : null,
			'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
			'metadata-mapping-url' => !empty( $_POST['metadata-mapping-url'] ) ? Filter::evaluate( $_POST['metadata-mapping-url'] ) : null,
			'record-number-for-mapping' => 1,
			'record-count' => 0
		);

		if ( !empty( $result['metadata-mapping-url'] ) ) {

			$mapping_details = WikiPages::getUsernameAndPageFromUrl( $result['metadata-mapping-url'] );
			$result['metadata-mapping'] = str_replace( array( Config::$metadata_mapping_subdirectory, str_replace( ' ', '_', Config::$metadata_mapping_subdirectory ) ), '', $mapping_details[1] );

		}

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
	 * @return {string} html content including the metadata mapping form
	 */
	protected function processRequest() {

		$result = null;
		$wiki_file_path = null;
		$user_options = array();
		$this->_UploadHandler = null;
		$this->_XmlDetectHandler = null;
		$this->_MediawikiTemplate = null;
		$this->_Mapping = null;

			$this->_user_options = $this->getUserOptions();

			$this->checkForRequiredFormFields(
				array(
					'record-element-name',
					'mediawiki-template-name',
					'record-number-for-mapping',
					'record-count'
				)
			);

			$this->_File = new File();

			$this->_MWApiClient = \GWToolset\getMWApiClient(
				$this->_SpecialPage->getUser()->getName(),
				( Config::$display_debug_output && $this->_SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' ) )
			);

			$this->_UploadHandler = new UploadHandler(
				array(
					'File' => new File,
					'MWApiClient' => $this->_MWApiClient,
					'SpecialPage' => $this->_SpecialPage,
					'User' => $this->_SpecialPage->getUser()
				)
			);

			$result .= $this->getUploadedFile( $this->_user_options );

			WikiPages::$MWApiClient = $this->_MWApiClient;
			$wiki_file_path = WikiPages::retrieveWikiFilePath( $this->_user_options['metadata-file-url'] );

			$this->_XmlDetectHandler = new XmlDetectHandler();
			$this->_XmlDetectHandler->processXml( $this->_user_options, $wiki_file_path );

			$this->_MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );
			$this->_Mapping = new Mapping( new MappingApiAdapter( $this->_MWApiClient ) );

			$result .= MetadataMappingForm::getForm(
				$this->_SpecialPage->getContext(),
				$this->_user_options,
				$this->getMetadataAsHtmlSelectsInTableRows( $this->_user_options ),
				$this->_XmlDetectHandler->getMetadataAsHtmlTableRows( $this->_user_options )
			);

		return $result;

	}


}