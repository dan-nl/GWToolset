<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Xml;
use DOMElement,
	Exception,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	XMLReader;

/**
 * @todo pull out the decorator methods and place them in the appropriate form handler
 */
class XmlDetectHandler extends XmlHandler {

	/**
	 * @var array
	 * an array collection of nodeName => nodeValues[] that are taken from the
	 * first matched dom element and will be used during the metadata mapping step
	 * of the upload process
	 */
	protected $_metadata_example_dom_element;

	/**
	 * @var array
	 * an array collection of nodeName => nodeValue matches
	 */
	protected $_metadata_example_dom_nodes = array();

	/**
	 * @var string
	 * an html string representing the XML metadata as options that can be placed
	 * in an html select element. none of the options has selected=selected
	 */
	protected $_metadata_as_options;

	public function __construct( array $options = array() ) {
		$this->reset();
		if ( isset( $options['SpecialPage'] ) ) {
			$this->_SpecialPage = $options['SpecialPage'];
		}
	}

	public function reset() {
		$this->_metadata_as_options = null;
		$this->_metadata_example_dom_element = array();
		$this->_metadata_example_dom_nodes = array();
		$this->_SpecialPage = null;
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

		foreach( $this->_metadata_example_dom_element as $nodeName => $nodeValues ) {
			foreach( $nodeValues as $nodeValue ) {
				$result .=
					'<tr>' .
						'<td>' . $nodeName . '</td>' .
						'<td>' . $nodeValue . '</td>' .
					'</tr>';
			}
		}

		return $result;
	}

	public function getMetadataAsOptions( $selected_option = null ) {
		$result = '<option></option>';

		foreach ( $this->_metadata_example_dom_nodes as $nodeName => $nodeValue ) {
			$result .= '<option';

			if ( !empty( $selected_option ) && $nodeName == $selected_option ) {
				$result .= ' selected="selected"';
			}

			$result .= '>' . $nodeName . '</option>';
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
	 * @todo refactor this method
	 * @return {string} an html string of select options
	 */
	public function getMetadataAsTableCells( $parameter, MediawikiTemplate $MediawikiTemplate, Mapping $Mapping ) {
		$result = null;
		$selected_options = array();
		$parameter_as_id = $MediawikiTemplate->getParameterAsId( $parameter );
		$first_row_placed = false;
		$required = null;
		$required_fields = array('title_identifier', 'url_to_the_media_file');

		$no_metadata_button_row =
			'<tr>' .
				'<td><label for="%s">%s%s :</label></td>' .
				'<td>&nbsp;</td>' .
				'<td><select name="%s[]" id="%s">%s</select></td>' .
			'</tr>';

		$first_row =
			'<tr>' .
				'<td><label for="%s">%s%s :</label></td>' .
				'<td class="metadata-add"></td>' .
				'<td><select name="%s[]" id="%s">%s</select></td>' .
			'</tr>';

		$following_row =
			'<tr>' .
				'<td>&nbsp;</td>' .
				'<td class="metadata-subtract"></td>' .
				'<td><select name="%s[]">%s</select></td>' .
			'</tr>';

		if ( isset( $Mapping->mapping_array[ $parameter ] ) ) {
			$selected_options = $Mapping->mapping_array[ $parameter ];
		}

		if ( empty( $this->_metadata_as_options ) ) {
			$this->_metadata_as_options = '<option></option>';

			foreach ( $this->_metadata_example_dom_nodes as $nodeName => $nodeValue ) {
				$this->_metadata_as_options .= '<option>' . $nodeName . '</option>';
			}
		}

		if ( in_array( $parameter_as_id, $required_fields ) ) {
			$required = ' <span class="required">*</span>';
		}

		if ( 'url_to_the_media_file' == $parameter_as_id ) {
			if ( isset( $selected_options[0] ) ) {
				$result .= sprintf(
					$no_metadata_button_row,
					$parameter_as_id,
					$parameter,
					$required,
					$parameter,
					$parameter_as_id,
					$this->getMetadataAsOptions( $selected_options[0] )
				);
			} else {
				$result .= sprintf(
					$no_metadata_button_row,
					$parameter_as_id,
					$parameter,
					$required,
					$parameter,
					$parameter_as_id,
					$this->getMetadataAsOptions()
				);
			}
		} elseif ( count( $selected_options ) == 1 ) {
			$result .= sprintf(
				$first_row,
				$parameter_as_id,
				$parameter,
				$required,
				$parameter,
				$parameter_as_id,
				$this->getMetadataAsOptions( $selected_options[0] )
			);
		} elseif ( count( $selected_options ) > 1 ) {
			foreach( $selected_options as $option ) {
				if ( key_exists( $option, $this->_metadata_example_dom_nodes ) ) {
					if ( !$first_row_placed ) {
						$result .= sprintf(
							$first_row,
							$parameter_as_id,
							$parameter,
							$required,
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
				$required,
				$parameter,
				$parameter_as_id,
				$this->_metadata_as_options
			);
		}
		return $result;
	}

	/**
	 * @param {DOMElement} $DOMElement
	 * @return void
	 */
	protected function createExampleDOMElement( DOMElement $DOMElement ) {
		foreach( $DOMElement->childNodes as $DOMNode ) {
			if ( $DOMNode->nodeType == XML_ELEMENT_NODE ) {
				if ( isset( $this->_metadata_example_dom_element[ $DOMNode->nodeName ] ) ) {
					$this->_metadata_example_dom_element[ $DOMNode->nodeName ][] = $DOMNode->nodeValue;
				} else {
					$this->_metadata_example_dom_element[ $DOMNode->nodeName ][0] = $DOMNode->nodeValue;
				}
			}
		}
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
				$this->_metadata_example_dom_nodes[ $DOMNode->nodeName ] = $DOMNode->nodeValue;
			}
		}

		ksort( $this->_metadata_example_dom_nodes );
	}

	/**
	 * attempts to find an example dom element in the metadata xml file that will
	 * be used for mapping the metadata to the mediawiki template. the search is
	 * based on hard-coded keys in the $user_options array
	 *
	 * - $user_options['record-element-name']
	 * - $user_options['record-count']
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
		$record = null;

		if ( empty( $xml_reader ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-xmlreader' )->plain() )->parse() );
		}

		if ( !isset( $user_options['record-element-name'] )
				|| !isset( $user_options['record-count'] )
		) {
			throw new Exception( wfMessage('gwtoolset-developer-issue')->params( wfMessage( 'gwtoolset-dom-record-issue' )->plain() )->parse() );
		}

		switch ( $xml_reader->nodeType ) {
			case ( XMLReader::ELEMENT ):
				if ( $xml_reader->name == $user_options['record-element-name'] ) {
					$user_options['record-count'] += 1;
					$record = $xml_reader->expand();

					if ( $user_options['record-count'] == 1 ) {
						$this->createExampleDOMElement( $record );
					}

					$this->findExampleDOMNodes( $record );
				}
				break;
		}

		return $result;
	}

	/**
	 * acts as a control method for retrieving dom elements from the
	 * metadata xml to be used for creating option menus and an example xml record
	 * that will be used for mapping the mediawiki template attributes to the
	 * xml metadata elements
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $file_path_local
	 * a local wiki path to the xml metadata file. the assumption is that it
	 * has been uploaded to the wiki earlier and is ready for use
	 *
	 * @throws Exception
	 * @return void
	 */
	public function processXml( array &$user_options, $file_path_local = null ) {
		$this->readXml( $user_options, $file_path_local, 'findExampleDOMElement' );

		if ( empty( $this->_metadata_example_dom_element ) ) {
			$msg =
				'<span class="error">' . wfMessage('gwtoolset-no-xml-element')->plain() . '</span>' . PHP_EOL .
				wfMessage('gwtoolset-no-example-dom-element')->parse() . '<br />' . PHP_EOL .
				$this->_SpecialPage->getBackToFormLink();

				throw new Exception( $msg );
		}
	}

}
