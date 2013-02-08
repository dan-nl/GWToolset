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
use			DOMElement,
			DOMNode,
			DOMDocument,
			Exception,
			GWToolset\Config,
			GWToolset\Forms\MetadataDetectForm,
			GWToolset\Forms\MetadataMappingForm,
			GWToolset\Menu,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplate,
			GWToolset\Handlers\Forms\UploadHandler,
			Php\File,
			Php\Filter,
			XMLReader;


class MetadataDetectHandler extends UploadHandler {


	protected $nodes_for_evaluation = array();


	/**
	 * a debug method for testing the reader
	 *
	 * @param XMLReader $reader
	 */
	protected function displayCurrentNodeProperties( $reader ) {

		echo 'attributeCount : ' . $reader->attributeCount . '<br/>';
		echo 'baseURI : ' .$reader->baseURI . '<br/>';
		echo 'depth : ' .$reader->depth . '<br/>';
		echo 'hasAttributes : ' .$reader->hasAttributes . '<br/>';
		echo 'hasValue : ' .$reader->hasValue . '<br/>';
		echo 'isDefault : ' .$reader->isDefault . '<br/>';
		echo 'isEmptyElemet : ' .$reader->isEmptyElement . '<br/>';
		echo 'localName : ' .$reader->localName . '<br/>';
		echo 'name : ' .$reader->name . '<br/>';
		echo 'namespaceURI : ' .$reader->namespaceURI . '<br/>';
		echo 'nodeType : ' .$reader->nodeType . '<br/>';
		echo 'prefix : ' .$reader->prefix . '<br/>';
		echo 'value : ' .$reader->value . '<br/>';
		echo 'xmlLang : ' .$reader->xmlLang . '<br/>';
		echo '<br/>';

	}


	/**
	 * a debug method
	 */
	protected function getNodesInfo( $node ) {

		if ($node->hasChildNodes() ) {

			$subNodes = $node->childNodes;

			foreach ($subNodes as $subNode) {

				if (($subNode->nodeType != 3) || 
					(($subNode->nodeType == 3) &&
					(strlen(trim($subNode->wholeText))>=1))
				) {
					echo "Node name: ".$subNode->nodeName."<br/>";
					echo "Node value: ".$subNode->nodeValue."<br/>";
					echo '<br/>';
				}

				$this->getNodesInfo($subNode);

			}

		}

	}




	protected function processChildNodes( DOMNode &$DOMNode ) {

		if ( $DOMNode->hasChildNodes() ) {

			foreach( $DOMNode->childNodes as $DOMChildNode ) {

				if ( $DOMChildNode->nodeType == XML_ELEMENT_NODE ) {

					self::processDOMElement( $DOMChildNode );

				}

			}

		}

	}


	/**
	 * takes a dom element and creates html options and table rows from the sub-elements
	 * contained therin
	 * 
	 * @param DOMElement $DOMElement
	 * @return void
	 */
	protected function processDOMElement( DOMElement $DOMElement ) {

		foreach( $DOMElement->childNodes as $DOMNode ) {

			if ( $DOMNode->nodeType == XML_ELEMENT_NODE ) {

				self::addMetadataToHtmlOptions( $DOMNode );
				self::addMetadataToHtmlTableRows( $DOMNode );

			}

		}

	}
	
	
	/**
	 * adds an option to $this->metadata_as_html_options based on the DOMNode given
	 * only adds unique nodeNames
	 *
	 * @param DOMNode $DOMNode
	 * @return void
	 */
	protected function getMetadataAsHtmlTableRows() {

		$result = null;

		foreach( $this->nodes_for_evaluation as $DOMNode ) {

			$result .=
				'<tr>' .
					'<td>' . $DOMNode->nodeName . '</td>' .
					'<td>' . $DOMNode->nodeValue . '</td>' .
				'</tr>';

		}


		//if ( $DOMNode->hasAttributes() ) {
		//
		//	foreach( $DOMNode->attributes as $DOMAttr ) {
		//
		//		$this->metadata_as_html_table_rows .=
		//			'<tr>' .
		//				'<td>' . $DOMNode->nodeName . '=>' . $DOMAttr->name . '</td>' .
		//				'<td>' . $DOMAttr->value . '</td>' .
		//			'</tr>';
		//
		//	}
		//
		//}
		//
		//self::processChildNodes( $DOMNode );
		
		return $result;
	
	}


	/**
	 * adds an option to $this->metadata_as_html_options based on the DOMNode given
	 * only adds unique nodeNames
	 *
	 * @param {Mapping} $Mapping
	 * @param {string} $template_parameter
	 * 
	 * @return {string} an html string of select options
	 */
	protected function getMetadataAsOptions( $template_parameter, Mapping $Mapping ) {

		$result = '<option></option>';
		$target_option = null;

		if ( isset( $Mapping->mapping_array[$template_parameter] ) ) {

			$target_option = $Mapping->mapping_array[$template_parameter];

		}

		foreach ( $this->nodes_for_evaluation as $DOMNode ) {

			$result .= '<option';

			if ( $DOMNode->nodeName == $target_option ) {

				$result .= ' selected="selected"';

			}

			$result .= '>' . $DOMNode->nodeName . '</option>';

			//if ( $DOMNode->hasAttributes() ) {
			//
			//	foreach( $DOMNode->attributes as $attribute => $value ) {
			//
			//		$this->metadata_as_html_options .=
			//			'<option>' .
			//				$DOMNode->nodeName . '=>' . $attribute .
			//			'</option>';
			//
			//	}
			//
			//}

			//self::processChildNodes( $DOMNode );

		}

		return $result;

	}


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

		$MediawikiTemplate = new MediawikiTemplate();
		$MediawikiTemplate->getValidMediaWikiTemplate( $user_options['mediawiki-template'] );

		$Mapping = new Mapping();
		$mapping_result = $Mapping->retrieve( $user_options );
		
		if ( !$mapping_result && !empty( $mapping_name['mapping-name'] ) ) {

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-not-found')->rawParams( $params['metadata-mapping'] ) );

		}

		foreach( $MediawikiTemplate->template_parameters as $parameter => $value ) {

			$parameter_as_id = $MediawikiTemplate->getParameterAsId( $parameter );

			$result .= sprintf(
				'<tr>' .
					'<td><label for="%s">%s :</label></td>' .
					'<td><select name="%s" id="%s">%s</select></td>' .
				'</tr>',
				$parameter_as_id,
				$parameter,
				$parameter_as_id,
				$parameter_as_id,
				$this->getMetadataAsOptions( $parameter_as_id, $Mapping )
			);

		}

		return $result;

	}


	/**
	 * using the xml element to be used for evaluation, adds its xml first level
	 * children them to the local $nodes_for_evaluation array
	 *
	 * @param {DOMElement} $DOMElement
	 * @return {null}
	 */
	protected function detectNodes( DOMElement $DOMElement ) {

		foreach( $DOMElement->childNodes as $DOMNode ) {

			if ( $DOMNode->nodeType == XML_ELEMENT_NODE && !array_key_exists( $DOMNode->nodeName, $this->nodes_for_evaluation ) ) {

				$this->nodes_for_evaluation[$DOMNode->nodeName] = $DOMNode;

			}

		}

	}


	/**
	 * verifies that the reader is :
	 *
	 * - on an xml element
	 * - the element name matches $options['record-element-name']
	 * - the current matched element matches the sequence mentioned in
	 *   $options['record-number-for-mapping']
	 *
	 * @param {XMLReader} $reader
	 * @param {array} $options
	 * @return {DOMElement|null}
	 */
	protected function findDOMElement( &$reader, &$options ) {

		$result = null;
		
		switch ( $reader->nodeType ) {

			case ( XMLReader::ELEMENT ):

				if ( $reader->name == $options['record-element-name'] ) {

					$options['record-count'] += 1;

					if ( $options['record-count'] == $options['record-number-for-mapping'] ) {

						$result = $reader->expand();

					}

				}

				break;

		}

		return $result;

	}


	/**
	 * - opens the xml file as a stream
	 * - finds an xml element
	 * - sends it to findDOMElement to determine if it's the correct xml element
	 *   for metadata evaluation.
	 * - closes the stream if the element is the one to be used for evaluation
	 * 
	 * 
	 * @param {string} $file_path_local
	 * @param {array} $user_options
	 *
	 * @todo: handle invalid xml
	 * @todo: handle no record-element-name found, specified element does not exist
	 * @todo: how to handle attributes and children nodes
	 * @todo: how to store entire file while only reading first node and preparing for element to template matching
	 * @todo: upload by url use internal upload process rather than the api
	 * @todo: parse the actual Artwork template for attributes rather than rely on a hard-coded class
	 * @todo: setup so that record x can be used for mapping rather than only the first record, which is the current default
	 * 
	 * @throws Exception
	 * @return {DOMElement|null}
	 */
	protected function getDOMElement( $file_path_local = null, array &$user_options ) {

		$result = null;
		$reader = new XMLReader();

		if ( empty( $file_path_local ) ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('local file path is empty') );

		}

		if ( !$reader->open( $file_path_local ) ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('could not open the XML File for reading') );

		}

		try {

			while ( $reader->read() ) {
	
				$result = $this->findDOMElement( $reader, $user_options );
				if ( !is_null( $result ) ) { break; }
	
			}

		} catch( Exception $e ) {

			$msg = wfMessage('gwtoolset-developer-issue')->params('not read the xml file');

			if ( Config::$display_debug_output && $this->SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' ) ) {

				$msg .= '<br/>' . $e->getMessage();

			}

			throw new Exception( $msg );

		}

		if ( !$reader->close() ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('could not close the XMLReader') );

		}

		return $result;

	}


	/**
	 * @todo save mapping and use it to verify posted variables when processing the mapping
	 *
	 * @return {string} an html string
	 */
	protected function processUpload() {

		$result = array( 'msg' => null, 'uploaded' => false );
		$metadata_dom_element = null;
		$metadata_options = null;
		$metadata_selects = null;

		$user_options = array(
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
			'record-number-for-mapping' => 1,
			//'uploaded-metadata' => !empty( $_FILES['uploaded-metadata']['name'] ) ? : null,
			'metadata-file-url' => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
			'mediawiki-template' => !empty( $_POST['mediawiki-template'] ) ? Filter::evaluate( $_POST['mediawiki-template'] ) : null,
			'metadata-mapping' => !empty( $_POST['metadata-mapping'] ) ? Filter::evaluate( $_POST['metadata-mapping'] ) : '',
			'record-count' => 0
		);

		try {

			if ( empty( $user_options['metadata-file-url'] ) ) {

				$this->getUploadedFormFile( 'uploaded-metadata' );
				$result = $this->saveFile();

				if ( !$result['uploaded'] ) {

					throw Exception( $result['msg'] );

				}

				$user_options['metadata-file-url'] = $this->UploadBase->getTitle();

			}

			$file_path_local = $this->retrieveLocalFilePath( $user_options, 'metadata-file-url' );

			$this->checkForRequiredFormFields(
				array(
					'record-element-name',
					'record-number-for-mapping',
					'mediawiki-template',
					'metadata-mapping'
				),
				$user_options
			);

			$metadata_dom_element = $this->getDOMElement( $file_path_local, $user_options );
			$this->detectNodes( $metadata_dom_element );
			
			//self::processDOMElement( $metadata_dom_element );
			//
			//$metadata_selects = self::getMetadataAsHtmlSelects(
			//	$user_options['mediawiki-template'],
			//	$user_options['metadata-mapping'],
			//	$this->metadata_as_html_options
			//);

			$metadata_selects = $this->getMetadataAsHtmlSelectsInTableRows( $user_options );
			$metadata_as_html_table_rows = $this->getMetadataAsHtmlTableRows( $user_options );

			$result['msg'] .= MetadataMappingForm::getForm(
				$this->SpecialPage->getContext(),
				$user_options,
				$metadata_selects,
				$metadata_as_html_table_rows
			);

		} catch( Exception $e ) {

			$result['msg'] .= '<h1>' . wfMessage( 'gwtoolset-metadata-detect-error' ) . '</h1>' .
				'<span class="error">' . $e->getMessage() . '</span><br/>';

		}

		return $result['msg'];

	}


}

