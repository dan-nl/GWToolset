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
namespace	GWToolset\Handlers\Xml;
use			DOMElement,
			Exception,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplate,
			XMLReader;

class XmlDetectHandler extends XmlHandler {


	/**
	 * @var DOMElement
	 * a place to store the example DOMElement at the class scope level. if the
	 * DOMElement is placed in a function scope variable, it will be losed if passed
	 * to another function because the DOMElement will no longer exist outside of the
	 * function scope
	 *
	 * @see https://bugs.php.net/bug.php?id=39593
	 */
	protected $_metadata_example_dom_element;


	/**
	 * @var array
	 * an array collection of DOMNodes found in $this->_metadata_example_dom_element
	 */
	protected $_metadata_example_dom_nodes = array();

	/**
	 * @var string
	 * an html string representing the XML metadata as options that can be placed
	 * in an html select element. none of the options has selected=selected
	 */
	protected $_metadata_as_options;
	
	
	/**
	 * adds an option to $this->metadata_as_html_options based on the DOMNode given
	 * only adds unique nodeNames
	 *
	 * @param DOMNode $DOMNode
	 * @return void
	 */
	public function getMetadataAsHtmlTableRows() {
	
		$result = null;
	
		foreach( $this->_metadata_example_dom_nodes as $DOMNode ) {
	
			$result .=
				'<tr>' .
					'<td>' . $DOMNode->nodeName . '</td>' .
					'<td>' . $DOMNode->nodeValue . '</td>' .
				'</tr>';
	
		}
		
		return $result;
	
	}


	public function getMetadataAsOptions( $selected_option = null ) {
	
		$result = null;
	
		$result .= '<option></option>';
	
		foreach ( $this->_metadata_example_dom_nodes as $DOMNode ) {
	
			$result .= '<option';
	
			if ( !empty( $selected_option ) && $DOMNode->nodeName == $selected_option ) {
	
				$result .= ' selected="selected"';
	
			}
	
			$result .= '>' . $DOMNode->nodeName . '</option>';
	
		}
		
		return $result;
		
	}


	/**
	 * returns an html string made up of html option elements that can be placed
	 * in an html select element. if a mapping between a mediawiki template and
	 * a metadata form is provided it will return the options with a selected
	 * option that meets the mapping criteria
	 *
	 * @param {string} $parameter a mediawiki template parameter
	 * @param {MediawikiTemplate} $MediawikiTemplate
	 * @param {Mapping} $Mapping
	 *
	 * @return {string} an html string of select options
	 */
	public function getMetadataAsTableCells( $parameter, MediawikiTemplate $MediawikiTemplate, Mapping $Mapping ) {

		$result = null;
		$selected_options = array();
		$parameter_as_id = $MediawikiTemplate->getParameterAsId( $parameter );
		$first_row_placed = false;

		$first_row =
			'<tr>' .
				'<td><label for="%s">%s :</label></td>' .
				//'<td width="16"><img src="/extensions/GWToolset/resources/images/b_snewtbl.png"/></td>' .
				'<td class="metadata-add"></td>' .
				'<td><select name="%s[]" id="%s">%s</select></td>' .
			'</tr>';

		$following_row =
			'<tr>' .
				'<td>&nbsp;</td>' .
				//'<td><img src="/extensions/GWToolset/resources/images/b_drop.png"/></td>' .
				'<td class="metadata-subtract"></td>' .
				'<td><select name="%s[]">%s</select></td>' .
			'</tr>';

		if ( isset( $Mapping->mapping_array[ $parameter ] ) ) {

			$selected_options = $Mapping->mapping_array[ $parameter ];

		}

		if ( empty( $this->_metadata_as_options ) ) {	
	
			$this->_metadata_as_options = '<option></option>';

			foreach ( $this->_metadata_example_dom_nodes as $DOMNode ) {
	
				$this->_metadata_as_options .= '<option>' . $DOMNode->nodeName . '</option>';
	
			}
	
		}

		if ( count( $selected_options ) == 1 ) {
	
			$result .= sprintf(
				$first_row,
				$parameter_as_id,
				$parameter,
				$parameter,
				$parameter_as_id,
				$this->getMetadataAsOptions( $selected_options[0] )
			);

		} else if ( count( $selected_options ) > 1 ) {

			foreach( $selected_options as $option ) {

				if ( key_exists( $option, $this->_metadata_example_dom_nodes ) ) {

					if ( !$first_row_placed ) {

						$result .= sprintf(
							$first_row,
							$parameter_as_id,
							$parameter,
							$parameter,
							$parameter_as_id,
							$this->getMetadataAsOptions( $option )
						);

						$first_row_placed = true;

					} else {

						$result .= sprintf(
							$following_row,
							$parameter,
							$this->getMetadataAsOptions( $option )
						);

					}

				}

			}
	
		} else {

			$result .= sprintf(
				$first_row,
				$parameter_as_id,
				$parameter,
				$parameter_as_id,
				$parameter_as_id,
				$this->_metadata_as_options
			);

		}

		return $result;
	
	}


	/**
	 * 1. takes in a DOMElement that will be used as the basis for the metadata mapping
	 * 2. adds the first level children of the DOMElement as an array collection
	 * of DOMNodes in $this->_metadata_example_dom_nodes
	 *
	 * @param {DOMElement} $DOMElement
	 * @return void
	 */
	protected function findExampleDOMNodes( DOMElement $DOMElement ) {

		foreach( $DOMElement->childNodes as $DOMNode ) {

			if ( $DOMNode->nodeType == XML_ELEMENT_NODE
				&& !array_key_exists( $DOMNode->nodeName, $this->_metadata_example_dom_nodes )
			) {

				$this->_metadata_example_dom_nodes[ $DOMNode->nodeName ] = $DOMNode;

			}

		}

	}


	/**
	 * attempts to find an example dom element in the metadata xml file that will
	 * be used for mapping the metadata to the mediawiki template. the search is
	 * based on hard-coded keys in the $user_options array
	 *
	 * - $user_options['record-element-name']
	 * - $user_options['record-count']
	 * - $user_options['record-number-for-mapping']
	 *
	 * if a matching dom element is found it is placed in
	 * $this->_metadata_example_dom_element
	 *
	 * @param {XMLReader} $xml_reader
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @return {array}
	 * - $result['msg'] an html string with the <li> results from the api createPage(), updatePage() calls
	 * - $result['stop-reading'] boolean stating whether or not to conitnue reading the XML document
	 */
	protected function findExampleDOMElement( XMLReader &$xml_reader, array &$user_options ) {

		$result = array( 'msg' => null, 'stop-reading' => false );

			if ( empty( $xml_reader ) ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('no XMLReader provided' ) );

			}

			if ( !isset( $user_options['record-element-name'] ) || !isset( $user_options['record-count'] ) || !isset( $user_options['record-number-for-mapping'] ) ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('record-element-name, record-count or record-number-for-mapping not provided' ) );

			}

			switch ( $xml_reader->nodeType ) {

				case ( XMLReader::ELEMENT ):

					if ( $xml_reader->name == $user_options['record-element-name'] ) {

						$user_options['record-count'] += 1;

						if ( $user_options['record-count'] == $user_options['record-number-for-mapping'] ) {

							$this->_metadata_example_dom_element = $xml_reader->expand();
							$result['stop-reading'] = true;

						}

					}

					break;

			}

		return $result;

	}


	/**
	 * acts as a control function for retrieving one metadata dom element from
	 * the xml to be used for mapping the mediawiki template to the xml metadata elements
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $file_path_local
	 * a local wiki path to the xml metadata file. the assumption is that it
	 * has been uploaded to the wiki earlier and is ready for use
	 */
	public function processXml( array &$user_options, $file_path_local = null ) {

		$this->readXml( $user_options, $file_path_local, 'findExampleDOMElement' );

		if ( !( $this->_metadata_example_dom_element instanceof DOMElement ) ) {

			throw new Exception( wfMessage('gwtoolset-no-example-dom-element') );

		}

		$this->findExampleDOMNodes( $this->_metadata_example_dom_element );

	}


	public function __construct() {}


}