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
			GWToolset\Config,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplate,
			Php\Filter,
			SpecialPage,
			XMLReader;


class XmlHandler {


	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $MediawikiTemplate;


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $MWApiClient;


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
	protected function displayCurrentNodeProperties( XMLReader $reader ) {

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
	 * @param array $element_mapped
	 * @param string $key
	 * @param DOMElement $DOMNodeElement
	 * @param boolean $is_url
	 */
	protected function filterNodeValue( array &$elements_mapped, &$key, DOMElement &$DOMNodeElement, $is_url = false ) {
		
		if ( !isset( $elements_mapped[ $key ] ) ) {

			if ( $is_url ) {

				$elements_mapped[ $key ] = Filter::evaluate( array( 'source' => $DOMNodeElement->nodeValue, 'filter-sanitize' => FILTER_SANITIZE_URL ) );

			} else {

				$elements_mapped[ $key ] = Filter::evaluate( $DOMNodeElement->nodeValue );

			}

		} else {

			if ( $is_url ) {

				$elements_mapped[ $key ] .= Config::$metadata_separator . Filter::evaluate( array( 'source' => $DOMNodeElement->nodeValue, 'filter-sanitize' => FILTER_SANITIZE_URL ) );

			} else {

				$elements_mapped[ $key ] .= Config::$metadata_separator . Filter::evaluate( $DOMNodeElement->nodeValue );

			}

		}

	}


	/**
	 * @param DOMELement $DOMElement
	 * @param array $mapping
	 */
	protected function getElementMapped( DOMElement &$DOMElement, array &$mapping ) {

		$elements_mapped = array();
		// avoiding getElementsByTagNameNS
		// as logic for getting the NS is not always straightforward
		$DOMNodeList = $DOMElement->getElementsByTagName( '*' );


		foreach( $DOMNodeList as $DOMNodeElement ) {

			$key = array_search( $DOMNodeElement->tagName, $mapping );
			if ( !in_array( $DOMNodeElement->tagName, $mapping ) ) { continue; }

			if ( strpos( $key, 'url' ) !== false ) {

				$this->filterNodeValue( $elements_mapped, $key, $DOMNodeElement, true );

			} else {

				$this->filterNodeValue( $elements_mapped, $key, $DOMNodeElement );

			}

		}

		return $elements_mapped;

	}


	/**
	 * using the api save the matched record as a new wiki page or update an
	 * existing wiki page
	 *
	 * @todo how to deal with url_to_media_file when it is redirected to a file
	 * @todo a. create filename - need to figure a better way to do it, possibly
	 * put it in the MediawikiTemplate instead of the UploadHandler
	 *
	 * @todo: have the api replace/update the template when page already exists
	 * @todo b. tell api to follow the redirect to get the file
	 * 
	 * @param DOMElement $matching_element
	 * @param array $user_options
	 */
	protected function processMatchingElement( DOMElement &$DOMElement, array &$user_options, array &$mapping ) {

		$result = null;
		$api_result = null;
		$page_id = -1;
		global $wgArticlePath;

		try {

			$this->MWApiClient = \GWToolset\getMWApiClient( $this->SpecialPage );

			$element_mapped = $this->getElementMapped( $DOMElement, $mapping );
			$this->MediawikiTemplate->populateFromArray( $element_mapped );

			$filename = $this->MediawikiTemplate->getFilename( $this->MediawikiTemplate->template_parameters['url_to_the_media_file'] );

			$api_result = $this->MWApiClient->query( array( 'titles' => 'File:' . $filename, 'indexpageids' => '' ) );
			$pageid = (int)$api_result['query']['pageids'][0];

			if ( $pageid > -1 ) { // page already exists only change text

				$api_result = $this->MWApiClient->edit(
					array(
						'pageid' => $pageid,
						'text' => $this->MediawikiTemplate->getTemplate(),
						'token' => $this->MWApiClient->getEditToken()
					)
				);

				if ( empty( $api_result['edit']['result'] ) && $api_result['upload']['result'] !== 'Success' ) {
					
					$result .= '<h1>' . wfMessage( 'mw-api-client-unknown-error' ) . '</h1>' .
						'<span class="error">' . $filename . '</span><br/>' .
						'<span class="error">' . $e->getMessage() . '</span><br/>';

				}

				$result .=
					'<li>' .
						'<a href="' . str_replace( '$1', $api_result['edit']['title'], $wgArticlePath ) . '">' .
							$api_result['edit']['title'] .
							( isset($api_result['edit']['oldrevid']) ? ' ( revised )' : ' ( no change )' ) .
						'</a>' .
					'</li>';

			} else { // page does not yet exist upload image and template text

				$api_result = $this->MWApiClient->upload(
					array(
						'filename' => $filename,
						'text' => $this->MediawikiTemplate->getTemplate(),
						'token' => $this->MWApiClient->getEditToken(),
						'ignorewarnings' => true,
						'url' => $this->MediawikiTemplate->template_parameters['url_to_the_media_file']
					)
				);

				if ( empty( $api_result['upload']['result'] ) && $api_result['upload']['result'] !== 'Success' ) {

					$result .= '<h1>' . wfMessage( 'mw-api-client-unknown-error' ) . '</h1>' .
						'<span class="error">' . $filename . '</span><br/>' .
						'<span class="error">' . $e->getMessage() . '</span><br/>';

				}

				$result .=
					'<li>' .
						'<a href="' . $api_result['upload']['imageinfo']['descriptionurl'] . '">' .
							$api_result['upload']['filename'] .
						'</a>' .
					'</li>';

			}

		} catch( Exception $e ) {

			$result .= '<h1>' . wfMessage( 'gwtoolset-api-error' ) . '</h1>' .
				'<span class="error">' . $filename . '</span><br/>' .
				'<span class="error">' . $e->getMessage() . '</span><br/>';

		}

		if ( Config::$display_debug_output
			&& $this->SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' )
			&& isset( $this->MWApiClient )
		) {

			$result .= $this->MWApiClient->debug_html;

		}

		return $result;

	}


	/**
	 * finds an xml element to be used as a basis for mapping metadata elements
	 * uses the $options settings to determine if the element in the metadata
	 * has the correct element name
	 *
	 * @param XMLReader $reader
	 * @param array $user_options
	 * @return DOMElement|null
	 */
	protected function findMatchingDOMElement( XMLReader &$reader, array &$user_options = array() ) {

		$DOMElement = null;

		switch ( $reader->nodeType ) {

			case ( XMLReader::ELEMENT ):

				if ( $reader->name == $user_options['record-element-name'] ) {

					$user_options['record-count'] += 1;
					$DOMElement = $reader->expand();

				}

				break;

		}

		return $DOMElement;

	}


	/**
	 * using an xml reader, for stream reading of the xml file, cycle through the
	 * elements, processing each one that matches the metadata record indicated in
	 * the user form that will be used for mapping the metadata to wiki pages.
	 *
	 * each matched element is sent to processMatchingElement() to be saved as a
	 * new wiki page or to update an existing wiki page for the record
	 *
	 * @param {string} $file_path_local
	 * @param {array} $user_options
	 * @param {array} $mapping
	 * @param {MediawujuTemplate} $MediawikiTemplate
	 *
	 * @throws Exception
	 * @return string|null
	 *
	 * @todo: figure out a batch job processing method
	 * @todo: handle mal-formed xml (future)
	 * @todo: handle an xml schema if present (future)
	 * @todo: handle incomplete/partial uploads (future)
	 */
	public function processDOMElements( $file_path_local, array &$user_options, array $mapping, MediawikiTemplate &$MediawikiTemplate ) {

		$result = null;
		$DOMElement = null;
		$this->MediawikiTemplate = $MediawikiTemplate;
		$reader = new XMLReader();

			if ( empty( $file_path_local ) ) {
	
				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('local file path is empty') );
	
			}
	
			if ( !$reader->open( $file_path_local ) ) {
	
				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('could not open the XML File for reading') );
	
			}
	
			while ( $reader->read() ) {
	
				$DOMElement = $this->findMatchingDOMElement( $reader, $user_options );
	
				if ( !is_null( $DOMElement ) ) {
	
					$result .= $this->processMatchingElement( $DOMElement, $user_options, $mapping );
	
				}
	
				$DOMElement = null;
	
			}
	
	
			if ( !$reader->close() ) {
	
				throw new Exception( wfMessage('gwtoolset-xmlreader-close-error') );
	
			}
	
			$result =
				'<h2>Results</h2>' .
				'<p>' . $user_options['record-count'] . ' record(s) uploaded. Links to the uploaded file(s)</p>' .
				'<ul>' .
					$result .
				'</ul>';

		return $result;

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