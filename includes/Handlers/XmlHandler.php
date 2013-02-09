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
namespace	GWToolset\Handlers;
use			DOMElement,
			GWToolset\Models\Mapping,
			SpecialPage,
			XMLReader;


class XmlHandler {


	/**
	 * @var SpecialPage
	 */
	protected $SpecialPage;


	/**
	 * @var DOMElement
	 */
	protected $_metadata_dom_element;


	/**
	 * @var array
	 * an array collection of DOMNodes
	 */
	protected $_metadata_dom_nodes = array();


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
	public function getMetadataAsHtmlTableRows() {

		$result = null;

		foreach( $this->_metadata_dom_nodes as $DOMNode ) {

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
	public function getMetadataAsOptions( $template_parameter, Mapping $Mapping ) {

		$result = '<option></option>';
		$target_option = null;

		if ( isset( $Mapping->mapping_array[$template_parameter] ) ) {

			$target_option = $Mapping->mapping_array[$template_parameter];

		}

		foreach ( $this->_metadata_dom_nodes as $DOMNode ) {

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
	 * takes in a DOMElement that will be used as the basis for metadata mapping
	 * it adds the first level children of the DOMElement as an array collection
	 * of source nodes to be used in the metadata mapping
	 *
	 * @param {DOMElement} $DOMElement
	 * @return void
	 */
	protected function detectNodes( DOMElement $DOMElement ) {

		foreach( $DOMElement->childNodes as $DOMNode ) {

			if ( $DOMNode->nodeType == XML_ELEMENT_NODE && !array_key_exists( $DOMNode->nodeName, $this->_metadata_dom_nodes ) ) {

				$this->_metadata_dom_nodes[$DOMNode->nodeName] = $DOMNode;

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
	 * Finds one xml element for metadata mapping
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
	protected function getDOMElement( $file_path_local = null, array &$user_options = array() ) {

		$result = null;
		$reader = new XMLReader();

			if ( empty( $file_path_local ) ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('local file path is empty') );

			}

			if ( !$reader->open( $file_path_local ) ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('could not open the XML File for reading') );

			}

			while ( $reader->read() ) {

				$result = $this->findDOMElement( $reader, $user_options );
				if ( !is_null( $result ) ) { break; }

			}

			if ( !$reader->close() ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('could not close the XMLReader') );

			}

		return $result;

	}


	/**
	 * @param {string} $file_path_local
	 * @param {array} $user_options
	 */
	public function processXml( $file_path_local = null, array $user_options = array() ) {

		$this->_metadata_dom_element = $this->getDOMElement( $file_path_local, $user_options );
		$this->detectNodes( $this->_metadata_dom_element );

	}


	public function __construct( SpecialPage &$SpecialPage ) {

		$this->SpecialPage = $SpecialPage;

	}


}