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
namespace	GWToolset\Handlers\Forms;
use 		Exception,
			GWToolset\Forms\MetadataMappingForm,
			GWToolset\Handlers\FileHandler,
			GWToolset\Handlers\Xml\XmlDetectHandler,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplate,
			Php\Filter;


class MetadataDetectHandler extends FormHandler {


	/**
	 * GWToolset\Handlers\FileHandler
	 */
	protected $_FileHandler;


	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;


	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;


	/**
	 * @var GWToolset\Handlers\XmlDetectHandler
	 */
	protected $_XmlDetectHandler;


	/**
	 *	returns an html string that is comprosed of table rows 
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

			$this->_FileHandler->getUploadedFileFromForm( $metadata_file_upload );
			$result = $this->_FileHandler->saveFile();
	
			if ( !$result['uploaded'] ) {
	
				throw Exception( $result['msg'] );
	
			}
	
			$user_options[ $metadata_file_url ] = $this->_FileHandler->getSavedFileName();

		return $result['msg'];

	}


	/**
	 * a control function that steps the methods necessary for processing the request
	 *
	 * @return {string} an html string
	 */
	protected function processRequest() {

		$result = null;
		$file_path_local = null;
		$user_options = array();
		$this->_FileHandler = null;
		$this->_XmlDetectHandler = null;
		$this->_MediawikiTemplate = null;
		$this->_Mapping = null;

			$user_options = array(
				'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
				'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] ) ? Filter::evaluate( $_POST['mediawiki-template-name'] ) : null,
				'metadata-mapping' => !empty( $_POST['metadata-mapping'] ) ? Filter::evaluate( $_POST['metadata-mapping'] ) : null,
				'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
				'record-number-for-mapping' => 1,
				'record-count' => 0
			);

			$this->checkForRequiredFormFields(
				array(
					'record-element-name',
					'mediawiki-template-name',
					'record-number-for-mapping',
					'record-count'
				),
				$user_options
			);

			$this->_FileHandler = new FileHandler( $this->SpecialPage );
			$result .= $this->getUploadedFile( $user_options );
			$file_path_local = $this->_FileHandler->retrieveLocalFilePath( $user_options );

			$this->_XmlDetectHandler = new XmlDetectHandler();
			//$this->XmlDetectHandler = new XmlDetectHandler( $this->SpecialPage, $this->MediawikiTemplate );
			$this->_XmlDetectHandler->processXml( $user_options, $file_path_local );

			$this->_MediawikiTemplate = new MediawikiTemplate();
			$this->_Mapping = new Mapping();

			$result .= MetadataMappingForm::getForm(
				$this->SpecialPage->getContext(),
				$user_options,
				$this->getMetadataAsHtmlSelectsInTableRows( $user_options ),
				$this->_XmlDetectHandler->getMetadataAsHtmlTableRows( $user_options )
			);

		return $result;

	}


}