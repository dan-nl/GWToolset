<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */

namespace GWToolset\Handlers\Xml;
use Content,
	DOMElement,
	GWToolset\Config,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	MWException,
	Php\Filter,
	XMLReader;

class XmlMappingHandler extends XmlHandler {

	/**
	 * @var {GWToolset\Models\Mapping}
	 */
	protected $_Mapping;

	/**
	 * @var {GWToolset\Handlers\Forms\MetadataMappingHandler}
	 */
	protected $_MappingHandler;

	/**
	 * @var {GWToolset\Models\MediawikiTemplate}
	 */
	protected $_MediawikiTemplate;

	/**
	 * @var {SpecialPage}
	 */
	protected $_SpecialPage;

	/**
	 * @param {array} $options
	 */
	public function __construct( array $options = array() ) {
		$this->reset();

		if ( isset( $options['Mapping'] ) ) {
			$this->_Mapping = $options['Mapping'];
		}

		if ( isset( $options['MediawikiTemplate'] ) ) {
			$this->_MediawikiTemplate = $options['MediawikiTemplate'];
		}

		if ( isset( $options['MappingHandler'] ) ) {
			$this->_MappingHandler = $options['MappingHandler'];
		}

		if ( isset( $options['SpecialPage'] ) ) {
			$this->_SpecialPage = $options['SpecialPage'];
		}
	}

	/**
	 * helper method for getDOMElementAsArray()
	 *
	 * @param {array} $array
	 * @param {DOMElement} $DOMElement
	 */
	protected function addDOMElementToArray( array &$array, DOMElement $DOMElement ) {
		$is_url = strpos( $DOMElement->nodeValue, '://' ) !== false;
		$array[] = $this->getFilteredNodeValue( $DOMElement, $is_url );

		if ( $DOMElement->hasAttributes() ) {
			foreach ( $DOMElement->attributes as $attribute ) {
				$array['@attributes'][$attribute->name] = $attribute->value;
			}
		}
	}

	/**
	 * creates an array based on the dom element provided
	 *   - only goes 1 level down
	 *   - assigns the element name as the array index
	 *   - adds values as an array to the element name index
	 *
	 * we’re using this method instead of a SimpleXMLElement object because we have found that some
	 * metadata sources use XML namespaces without declaring them within the XML document, which
	 * requires additional logic to sort out the namespaced elements that may not be reliable
	 *
	 * @param {DOMElement} $DOMElement
	 * @return {array}
	 */
	protected function getDOMElementAsArray( DOMElement $DOMElement ) {
		$result = array();

		foreach ( $DOMElement->childNodes as $childNode ) {
			if ( $childNode->nodeType === XML_ELEMENT_NODE ) {
				if ( !isset( $result[$childNode->tagName] ) ) {
					$result[$childNode->tagName] = array();
					$this->addDOMElementToArray( $result[$childNode->tagName], $childNode );
				} else {
					$this->addDOMElementToArray( $result[$childNode->tagName], $childNode );
				}
			}
		}

		return $result;
	}

	/**
	 * takes in a metadata dom element that represents a targeted
	 * record within the metadata that will be saved/updated in the
	 * wiki as a wiki page and maps it to the mediawiki template
	 * using $this->_Mapping provided to the class
	 *
	 * allows for a one -> many relationship
	 * one mediawiki template parameter -> many dom elements
	 * uses the Config::$metadata_separator to concatenate multiple values
	 *
	 * values are filtered
	 *
	 * uses getElementsByTagName to avoid getElementsByTagNameNS as logic for
	 * getting the NS is not always straightforward
	 *
	 * @todo possibly filter keys and values
	 * @todo possibly refactor so that it works with getDOMElementAsArray
	 *
	 * @param {DOMELement} $DOMElement
	 *
	 * @return {array}
	 * the keys and values in the array have not been filtered
	 * an array that maps mediawiki template parameters to the metadata record
	 * values provided by the DOMElement
	 */
	protected function getDOMElementMapped( DOMElement $DOMElement ) {

		$elements_mapped = array();
		$is_url = false;
		$DOMNodeList = $DOMElement->getElementsByTagName( '*' );

		// cycle over all of the elements in the record element provided
		foreach ( $DOMNodeList as $DOMNodeElement ) {

			// if the current element is not one that was mapped, skip it
			if ( !key_exists(
					$DOMNodeElement->tagName,
					$this->_Mapping->target_dom_elements_mapped
			) ) {
				continue;
			}

			// an array of mediawiki parameters that should be populatated by this DOMNodeElement’s value
			$template_parameters = $this->_Mapping->target_dom_elements_mapped[$DOMNodeElement->tagName];
			$lang = null;

			// set the lang attribute if found
			if ( $DOMNodeElement->hasAttributes() ) {
				foreach ( $DOMNodeElement->attributes as $DOMAttribute ) {
					if ( $DOMAttribute->name === 'lang' ) {
						$lang = Filter::evaluate( $DOMAttribute->value );
						break;
					}
				}
			}

			foreach ( $template_parameters as $template_parameter ) {
				$is_url = strpos( $template_parameter, 'url' ) !== false;

				if ( !empty( $lang ) ) {
					/**
					 * within a record, multimple elements with the same element name, e.g., description
					 * can exist. some may have a lang attribute and some may not. if the first element
					 * found does not have a lang attribute it is stored as a value in
					 * $elements_mapped[$template_parameter] and consequtive elements are concatenate on it.
					 *
					 * however, if one of those similar elements has a lang attribute,
					 * $elements_mapped[$template_parameter] needs to become an array with index [0]
					 * containing the values that do not have a lang attribute and index['language']
					 * containing the languages provided as sub indexes, e.g., ['language']['en']
					 */
					if ( isset( $elements_mapped[$template_parameter] )
						&& !is_array( $elements_mapped[$template_parameter] )
					) {
						$tmp_string = $elements_mapped[$template_parameter];
						$elements_mapped[$template_parameter] = array();
						$elements_mapped[$template_parameter][0] = $tmp_string;
					}

					if ( !isset( $elements_mapped[$template_parameter]['language'] ) ) {
						$elements_mapped[$template_parameter]['language'] = array();
					}

					if ( !isset( $elements_mapped[$template_parameter]['language'][$lang] ) ) {
						$elements_mapped[$template_parameter]['language'][$lang] =
							$this->getFilteredNodeValue( $DOMNodeElement, $is_url );
					} else {
						$elements_mapped[$template_parameter]['language'][$lang] .=
							Config::$metadata_separator .
							$this->getFilteredNodeValue( $DOMNodeElement, $is_url );
					}
				} else {
					if ( !isset( $elements_mapped[$template_parameter] ) ) {
						$elements_mapped[$template_parameter] =
							$this->getFilteredNodeValue( $DOMNodeElement, $is_url );
					} else {
						if ( $template_parameter === 'title-identifier' ) {
							$elements_mapped[$template_parameter] .=
								Config::$title_separator .
								$this->getFilteredNodeValue( $DOMNodeElement, $is_url );

						/**
						 * url-to-the-media-file should only be evaluated once
						 * when $elements_mapped['url-to-the-media-file'] is not set
						 */
						} elseif ( $template_parameter !== 'url-to-the-media-file' ) {

							/**
							 * if a template_parameter has some elements with a lang attribute
							 * and some not, the non lang attribute versions need their own
							 * array element
							 *
							 * isset( $elements_mapped[ $template_parameter ][ 'language ] )
							 * doesn't work here
							 */
							if ( is_array( $elements_mapped[$template_parameter] )
								&& array_key_exists( 'language', $elements_mapped[$template_parameter] )
							) {
								if ( !isset( $elements_mapped[$template_parameter][0] ) ) {
									$elements_mapped[$template_parameter][0] =
										$this->getFilteredNodeValue( $DOMNodeElement, $is_url );
								} else {
									/**
									 * .= produces PHP Fatal error: Cannot use assign-op operators with overloaded
									 * objects nor string offsets
									 */
									$elements_mapped[$template_parameter][0] =
										$elements_mapped[$template_parameter][0] .
										Config::$metadata_separator .
										$this->getFilteredNodeValue( $DOMNodeElement, $is_url );
								}
							} else {
								$elements_mapped[$template_parameter] .=
									Config::$metadata_separator .
									$this->getFilteredNodeValue( $DOMNodeElement, $is_url );
							}
						}
					}
				}
			}
		}

		return $elements_mapped;
	}

	/**
	 * @param {DOMElement} $DOMNodeElement
	 * @param {bool} $is_url
	 *
	 * @return {string}
	 * the string has been filtered
	 */
	protected function getFilteredNodeValue( DOMElement &$DOMNodeElement, $is_url = false ) {
		$result = null;

		if ( $is_url ) {
			$result = Filter::evaluate(
				array(
					'source' => $DOMNodeElement->nodeValue,
					'filter-sanitize' => FILTER_SANITIZE_URL
				)
			);
		} else {
			$result = Filter::evaluate( $DOMNodeElement->nodeValue );
		}

		return $result;
	}

	/**
	 * find dom elements in the $XMLElement provided that match the
	 * metadata record element indicated by original $_POST,
	 * $user_options['record-element-name']
	 *
	 * each matched metadata record, is sent to
	 * $this->_MappingHandler->processMatchingElement()
	 * to be saved as a new mediafile in the wiki or to update
	 * an existing mediafile in the wiki
	 *
	 * @param {XMLReader|DOMElement} $xml_source
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @throws {MWException}
	 *
	 * @return {array}
	 * - $result['Title'] {Title}
	 * - $result['stop-reading'] {bool}
	 */
	protected function processDOMElements( $XMLElement, array &$user_options ) {
		$result = array( 'Title' => null, 'stop-reading' => false );
		$record = null;
		$outer_xml = null;

		if ( !( $XMLElement instanceof XMLReader )
			&& !( $XMLElement instanceof DOMElement )
		) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( wfMessage( 'gwtoolset-no-xml-element' )->escaped() )
					->parse()
			);
		}

		if ( !isset( $user_options['record-element-name'] )
			|| !isset( $user_options['record-count'] )
			|| !isset( $user_options['record-current'] )
		) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( wfMessage( 'gwtoolset-dom-record-issue' )->parse() )
					->parse()
			);
		}

		switch ( $XMLElement->nodeType ) {
			case ( XMLReader::ELEMENT ):
				if ( $XMLElement instanceof XMLReader ) {
					if ( $XMLElement->name === $user_options['record-element-name'] ) {
						$record = $XMLElement->expand();
						$outer_xml = $XMLElement->readOuterXml();
					}
				} elseif ( $XMLElement instanceof DOMElement ) {
					if ( $XMLElement->nodeName === $user_options['record-element-name'] ) {
						$record = $XMLElement;
						$outer_xml = $record->ownerDocument->saveXml( $record );
					}
				}

				if ( !empty( $record ) ) {
					$user_options['record-current'] += 1;

					// don’t process the element if the current record nr is <
					// the record nr we should begin processing on
					if ( $user_options['record-current'] < $user_options['record-begin'] ) {
						break;
					}

					// stop processing if the current record nr is >=
					// the record nr we should begin processing on plus the job throttle
					if ( $user_options['record-current']
						>= $user_options['record-begin'] + Config::$job_throttle
					) {
						$result['stop-reading'] = true;
						break;
					}

					$result['Title'] = $this->_MappingHandler->processMatchingElement(
						$user_options,
						array(
							'metadata-as-array' => $this->getDOMElementAsArray( $record ),
							'metadata-mapped-to-mediawiki-template' => $this->getDOMElementMapped( $record ),
							'metadata-raw' => $outer_xml
						)
					);
				}

				break;
		}

		return $result;
	}

	/**
	 * a control method for retrieving dom elements from an xml metadata
	 * source. the dom elements will be used for creating mediafile
	 * Titles in the wiki.
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the original $_POST
	 *
	 * @param {string|Content} $xml_source
	 * a local wiki path to the xml metadata file or a local wiki Content source.
	 * the assumption is that it has already been uploaded to the wiki earlier and
	 * is ready for use
	 *
	 * @throws {MWException}
	 * @return {array}
	 * an array of mediafile Title(s)
	 */
	public function processXml( array &$user_options, $xml_source = null ) {
		$callback = 'processDOMElements';

		if ( is_string( $xml_source ) && !empty( $xml_source ) ) {
			return $this->readXmlAsFile( $user_options, $xml_source, $callback );
		} elseif ( $xml_source instanceof Content ) {
			return $this->readXmlAsString( $user_options, $xml_source->getNativeData(), $callback );
		} else {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( wfMessage( 'gwtoolset-no-xml-source' )->escaped() )
					->parse()
			);
		}
	}

	public function reset() {
		$this->_Mapping = null;
		$this->_MappingHandler = null;
		$this->_MediawikiTemplate = null;
		$this->_SpecialPage = null;
	}
}
