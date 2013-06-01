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
	GWToolset\Config,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	Php\Filter,
	XMLReader;

class XmlMappingHandler extends XmlHandler {

	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;

	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;

	public function __construct( Mapping $Mapping, MediawikiTemplate $MediawikiTemplate, $MappingHandler ) {
		$this->_Mapping = $Mapping;
		$this->_MediawikiTemplate = $MediawikiTemplate;
		$this->_MappingHandler = $MappingHandler;
	}

	/**
	 * @param DOMElement $DOMNodeElement
	 * @param boolean $is_url
	 *
	 * @return string
	 * a filtered DOMNodeElementValue
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
	 * takes in a metadata dom element that represents a targeted record within the
	 * metadata that will be saved/updated in the wiki as a wiki page and maps
	 * it to the mediawiki template using $this->_Mapping provided to the class
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
	 * @param DOMELement $DOMElement
	 *
	 * @return array
	 * an array that maps mediawiki template parameters to the metadata record
	 * values provided by the DOMElement
	 */
	protected function getDOMElementMapped( DOMElement $DOMElement ) {
		$elements_mapped = array();
		$is_url = false;
		$DOMNodeList = $DOMElement->getElementsByTagName( '*' );

		foreach( $DOMNodeList as $DOMNodeElement ) {
			if ( !key_exists( $DOMNodeElement->tagName, $this->_Mapping->target_dom_elements_mapped ) ) {
				continue;
			}

			// an array of mediawiki parameters that should be populatated by this DOMNodeElement’s value
			$template_parameters = $this->_Mapping->target_dom_elements_mapped[ $DOMNodeElement->tagName ];
			$lang = null;

			if ( $DOMNodeElement->hasAttributes() ) {
				foreach( $DOMNodeElement->attributes as $DOMAttribute ) {
					if ( 'lang' == $DOMAttribute->name ) {
						$lang = Filter::evaluate( $DOMAttribute->value );
						break;
					}
				}
			}

			foreach( $template_parameters as $template_parameter ) {
				if ( strpos( $template_parameter, 'url' ) !== false ) {
					$is_url = true;
				} else {
					$is_url = false;
				}

				if ( !empty( $lang ) ) {
					if ( !isset( $elements_mapped[ $template_parameter ]['language'] ) ) {
						$elements_mapped[ $template_parameter ]['language'] = array();
					}

					if ( !isset( $elements_mapped[ $template_parameter ]['language'][ $lang ] ) ) {
						$elements_mapped[ $template_parameter ]['language'][ $lang ] = $this->getFilteredNodeValue( $DOMNodeElement, $is_url );
					} else {
						$elements_mapped[ $template_parameter ]['language'][ $lang ] .= Config::$metadata_separator . $this->getFilteredNodeValue( $DOMNodeElement, $is_url );
					}
				} else {
					if ( !isset( $elements_mapped[ $template_parameter ] ) ) {
						$elements_mapped[ $template_parameter ] = $this->getFilteredNodeValue( $DOMNodeElement, $is_url );
					} else {
						if ( 'title_identifier' == $template_parameter ) {
							$elements_mapped[ $template_parameter ] .= Config::$title_separator . $this->getFilteredNodeValue( $DOMNodeElement, $is_url );

						// url_to_the_media_file should only be evaluated once when $elements_mapped['url_to_the_media_file'] is not set
						} elseif ( 'url_to_the_media_file' != $template_parameter )  {

							// if a template_parameter has some elements with a lang attribute and some not, the non
							// lang attribute versions need their own array element
							// isset( $elements_mapped[ $template_parameter ][ 'language ] ) doesn't work here
							if ( is_array( $elements_mapped[ $template_parameter ] )
								&& array_key_exists( 'language', $elements_mapped[ $template_parameter ] )
							) {								
								if ( !isset( $elements_mapped[ $template_parameter ][0] ) ) {
									$elements_mapped[ $template_parameter ][0] = $this->getFilteredNodeValue( $DOMNodeElement, $is_url );
								} else {
									// .= produces PHP Fatal error:  Cannot use assign-op operators with overloaded objects nor string offsets
									$elements_mapped[ $template_parameter ][0] = $elements_mapped[ $template_parameter ][0] . Config::$metadata_separator . $this->getFilteredNodeValue( $DOMNodeElement, $is_url );
								}
							} else {
								$elements_mapped[ $template_parameter ] .= Config::$metadata_separator . $this->getFilteredNodeValue( $DOMNodeElement, $is_url );
							}
						}
					}
				}
			}
		}

		return $elements_mapped;
	}

	/**
	 * using an xml reader, for stream reading of the xml file, find dom elements
	 * that match the metadata record element indicated by the user form,
	 * $user_options['record-element-name']
	 *
	 * each matched metadata record, is sent to
	 * $this->_MappingHandler->processMatchingElement() to be saved as a new
	 * wiki page or to update an existing wiki page for the record
	 *
	 * @param {XMLReader} $xml_reader
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @throws Exception
	 *
	 * @return {array}
	 * - $result['msg'] an html string with the <li> results from the api createPage(), updatePage() calls
	 * - $result['stop-reading'] boolean stating whether or not to conitnue reading the XML document
	 */
	public function processDOMElements( XMLReader $xml_reader, array &$user_options ) {
		$result = array( 'msg' => null, 'stop-reading' => false );
		$DOMElement = null;

		if ( empty( $xml_reader ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-xmlreader' )->plain() )->parse() );
		}

		if ( !isset( $user_options['record-element-name'] )
			|| !isset( $user_options['record-count'] )
		) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-dom-record-issue' )->plain() )->parse() );
		}

		switch ( $xml_reader->nodeType ) {
			case ( XMLReader::ELEMENT ):
				if ( $xml_reader->name == $user_options['record-element-name'] ) {
					$user_options['record-count'] += 1;

					if ( empty( $this->_MappingHandler->_SpecialPage ) ) {
						if ( $user_options['record-count'] < $user_options['record-begin'] ) {
							break;
						}

						if ( $user_options['record-count'] > $user_options['record-begin'] + Config::$job_throttle ) {
							$result['stop-reading'] = true;
							break;
						}
					}

					$DOMElement = $xml_reader->expand();

					if ( !empty( $DOMElement ) && $DOMElement instanceof DOMElement ) {
						$result['msg'] = $this->_MappingHandler->processMatchingElement( $this->getDOMElementMapped( $DOMElement ), $xml_reader->readOuterXml() );
						$result['msg'] = '<ul>' . $result['msg'] . '</ul>';
					}
				}
				break;
		}

		return $result;
	}

	/**
	 * acts as a control function for retrieving & processing metadata elements
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $file_path_local
	 * a local wiki path to the xml metadata file. the assumption is that it
	 * has been uploaded to the wiki earlier and is ready for use
	 *
	 * @return {string}
	 */
	public function processXml( array &$user_options, $file_path_local = null ) {
		$result =
			'<h2>' . wfMessage('gwtoolset-results')->plain() . '</h2>' .
			$this->readXml( $user_options, $file_path_local, 'processDOMElements' );

		return $result;
	}

	//	//if ( $DOMNode->hasAttributes() ) {
	//	//
	//	//	foreach( $DOMNode->attributes as $DOMAttr ) {
	//	//
	//	//		$this->metadata_as_html_table_rows .=
	//	//			'<tr>' .
	//	//				'<td>' . $DOMNode->nodeName . '=>' . $DOMAttr->name . '</td>' .
	//	//				'<td>' . $DOMAttr->value . '</td>' .
	//	//			'</tr>';
	//	//
	//	//	}
	//	//
	//	//}

}
