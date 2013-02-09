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
			GWToolset\Handlers\XmlHandler,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplate,
			Php\Filter;


class MetadataDetectHandler extends FormHandler {


	/**
	 * GWToolset\Handlers\FileHandler
	 */
	protected $FileHandler;


	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $Mapping;


	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $MediawikiTemplate;


	/**
	 * @var GWToolset\Handlers\XmlHandler
	 */
	protected $XmlHandler;


	/**
	 * @param {array} $user_options
	 * @throws Exception
	 * @return string
	 * an html select element representing the nodes in the xml file that will
	 * be used to match the attributes/properties in the wiki template
	 */
	protected function getMetadataAsHtmlSelectsInTableRows( array &$user_options ) {

		$result = null;
		$mapping_result = false;

		$this->MediawikiTemplate = new MediawikiTemplate();
		$this->MediawikiTemplate->getValidMediaWikiTemplate( $user_options['mediawiki-template'] );

		$this->Mapping = new Mapping();
		$mapping_result = $this->Mapping->retrieve( $user_options );
		
		if ( !$mapping_result && !empty( $mapping_name['mapping-name'] ) ) {

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-not-found')->rawParams( $params['metadata-mapping'] ) );

		}

		foreach( $this->MediawikiTemplate->template_parameters as $parameter => $value ) {

			$parameter_as_id = $this->MediawikiTemplate->getParameterAsId( $parameter );

			$result .= sprintf(
				'<tr>' .
					'<td><label for="%s">%s :</label></td>' .
					'<td><select name="%s" id="%s">%s</select></td>' .
				'</tr>',
				$parameter_as_id,
				$parameter,
				$parameter_as_id,
				$parameter_as_id,
				$this->XmlHandler->getMetadataAsOptions( $parameter_as_id, $this->Mapping )
			);

		}

		return $result;

	}


	/**
	 * @todo save mapping and use it to verify posted variables when processing the mapping
	 *
	 * @return {string} an html string
	 */
	protected function processRequest() {

		$result = array( 'msg' => null, 'uploaded' => false );
		$metadata_dom_element = null;
		$metadata_selects = null;
		$metadata_as_html_table_rows = null;
		$this->FileHandler = new FileHandler( $this->SpecialPage );
		$this->XmlHandler = new XmlHandler( $this->SpecialPage );

		$user_options = array(
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
			'mediawiki-template' => !empty( $_POST['mediawiki-template'] ) ? Filter::evaluate( $_POST['mediawiki-template'] ) : null,
			'metadata-mapping' => !empty( $_POST['metadata-mapping'] ) ? Filter::evaluate( $_POST['metadata-mapping'] ) : '',
			'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
			'record-number-for-mapping' => 1,
			'record-count' => 0
		);

			if ( empty( $user_options['metadata-file-url'] ) ) {

				$this->FileHandler->getUploadedFileFromForm( 'uploaded-metadata' );
				$result = $this->FileHandler->saveFile();

				if ( !$result['uploaded'] ) {

					throw Exception( $result['msg'] );
	
				}

				$user_options['metadata-file-url'] = $this->FileHandler->getSavedFileName();

			}

			$file_path_local = $this->FileHandler->retrieveLocalFilePath( $user_options, 'metadata-file-url' );

			$this->checkForRequiredFormFields(
				array(
					'record-element-name',
					'mediawiki-template',
					'metadata-mapping',
					'record-number-for-mapping'
				),
				$user_options
			);

			$this->XmlHandler->processXml( $file_path_local, $user_options );

			$result['msg'] .= MetadataMappingForm::getForm(
				$this->SpecialPage->getContext(),
				$user_options,
				$this->getMetadataAsHtmlSelectsInTableRows( $user_options ),
				$this->XmlHandler->getMetadataAsHtmlTableRows( $user_options )
			);

		return $result['msg'];

	}


}