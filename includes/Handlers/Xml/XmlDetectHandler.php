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
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	//protected $MediawikiTemplate;


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	//protected $MWApiClient;


	/**
	 * @var SpecialPage
	 */
	//protected $SpecialPage;


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
	//protected function processMatchingElement( DOMElement &$DOMElement, array &$user_options, array &$mapping ) {
	//
	//	$result = null;
	//	$api_result = null;
	//	$page_id = -1;
	//	global $wgArticlePath;
	//
	//	$this->MWApiClient = \GWToolset\getMWApiClient( $this->SpecialPage );
	//	$element_mapped = $this->getElementMapped( $DOMElement, $mapping );
	//
	//	$this->MediawikiTemplate->populateFromArray( $element_mapped );
	//	$filename = $this->MediawikiTemplate->getTitle();
	//
	//	$api_result = $this->MWApiClient->query( array( 'titles' => 'File:' . $filename, 'indexpageids' => '' ) );
	//	$pageid = (int)$api_result['query']['pageids'][0];
	//
	//	if ( $pageid > -1 ) { // page already exists only change text
	//
	//		$api_result = $this->MWApiClient->edit(
	//			array(
	//				'pageid' => $pageid,
	//				'text' => $this->MediawikiTemplate->getTemplate(),
	//				'token' => $this->MWApiClient->getEditToken()
	//			)
	//		);
	//
	//		if ( empty( $api_result['edit']['result'] ) && $api_result['upload']['result'] !== 'Success' ) {
	//			
	//			$result .= '<h1>' . wfMessage( 'mw-api-client-unknown-error' ) . '</h1>' .
	//				'<span class="error">' . $filename . '</span><br/>' .
	//				'<span class="error">' . $e->getMessage() . '</span><br/>';
	//
	//		}
	//
	//		$result .=
	//			'<li>' .
	//				'<a href="' . str_replace( '$1', $api_result['edit']['title'], $wgArticlePath ) . '">' .
	//					$api_result['edit']['title'] .
	//					( isset($api_result['edit']['oldrevid']) ? ' ( revised )' : ' ( no change )' ) .
	//				'</a>' .
	//			'</li>';
	//
	//	} else { // page does not yet exist upload image and template text
	//
	//		$api_result = $this->MWApiClient->upload(
	//			array(
	//				'filename' => $filename,
	//				'text' => $this->MediawikiTemplate->getTemplate(),
	//				'token' => $this->MWApiClient->getEditToken(),
	//				'ignorewarnings' => true,
	//				'url' => $this->MediawikiTemplate->template_parameters['url_to_the_media_file']
	//			)
	//		);
	//
	//		if ( empty( $api_result['upload']['result'] ) && $api_result['upload']['result'] !== 'Success' ) {
	//
	//			$result .= '<h1>' . wfMessage( 'mw-api-client-unknown-error' ) . '</h1>' .
	//				'<span class="error">' . $filename . '</span><br/>' .
	//				'<span class="error">' . $e->getMessage() . '</span><br/>';
	//
	//		}
	//
	//		$result .=
	//			'<li>' .
	//				'<a href="' . $api_result['upload']['imageinfo']['descriptionurl'] . '">' .
	//					$api_result['upload']['filename'] .
	//				'</a>' .
	//			'</li>';
	//
	//	}
	//
	//	if ( Config::$display_debug_output
	//		&& $this->SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' )
	//		&& isset( $this->MWApiClient )
	//	) {
	//
	//		$result .= $this->MWApiClient->debug_html;
	//
	//	}
	//
	//	return $result;
	//
	//}


	/**
	 * finds an xml element to be used as a basis for mapping metadata elements
	 * uses the $options settings to determine if the element in the metadata
	 * has the correct element name
	 *
	 * @param XMLReader $reader
	 * @param array $user_options
	 * @return DOMElement|null
	 */
	//protected function findMatchingDOMElement( XMLReader &$reader, array &$user_options = array() ) {
	//
	//	$DOMElement = null;
	//
	//	switch ( $reader->nodeType ) {
	//
	//		case ( XMLReader::ELEMENT ):
	//
	//			if ( $reader->name == $user_options['record-element-name'] ) {
	//
	//				$user_options['record-count'] += 1;
	//				$DOMElement = $reader->expand();
	//
	//			}
	//
	//			break;
	//
	//	}
	//
	//	return $DOMElement;
	//
	//}


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
	//public function processDOMElements( $file_path_local, array &$user_options, array $mapping ) {
	//
	//	$result = null;
	//	$DOMElement = null;
	//	$reader = new XMLReader();
	//
	//		if ( empty( $file_path_local ) ) {
	//
	//			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('local file path is empty') );
	//
	//		}
	//
	//		if ( !$reader->open( $file_path_local ) ) {
	//
	//			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('could not open the XML File for reading') );
	//
	//		}
	//
	//		while ( $reader->read() ) {
	//
	//			$DOMElement = $this->findMatchingDOMElement( $reader, $user_options );
	//
	//			if ( !is_null( $DOMElement ) ) {
	//
	//				$result .= $this->processMatchingElement( $DOMElement, $user_options, $mapping );
	//
	//			}
	//
	//			$DOMElement = null;
	//
	//		}
	//
	//		if ( !$reader->close() ) {
	//
	//			throw new Exception( wfMessage('gwtoolset-xmlreader-close-error') );
	//
	//		}
	//
	//		$result =
	//			'<h2>Results</h2>' .
	//			'<p>' . $user_options['record-count'] . ' record(s) uploaded. Links to the uploaded file(s)</p>' .
	//			'<ul>' .
	//				$result .
	//			'</ul>';
	//
	//	return $result;
	//
	//}


	//protected function processChildNodes( DOMNode &$DOMNode ) {
	//
	//	if ( $DOMNode->hasChildNodes() ) {
	//
	//		foreach( $DOMNode->childNodes as $DOMChildNode ) {
	//
	//			if ( $DOMChildNode->nodeType == XML_ELEMENT_NODE ) {
	//
	//				self::processDOMElement( $DOMChildNode );
	//
	//			}
	//
	//		}
	//
	//	}
	//
	//}


	/**
	 * takes a dom element and creates html options and table rows from the sub-elements
	 * contained therin
	 * 
	 * @param DOMElement $DOMElement
	 * @return void
	 */
	//protected function processDOMElement( DOMElement $DOMElement ) {
	//
	//	foreach( $DOMElement->childNodes as $DOMNode ) {
	//
	//		if ( $DOMNode->nodeType == XML_ELEMENT_NODE ) {
	//
	//			self::addMetadataToHtmlOptions( $DOMNode );
	//			self::addMetadataToHtmlTableRows( $DOMNode );
	//
	//		}
	//
	//	}
	//
	//}
	
	
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
				'<td width="16" class="add-metadata"></td>' .
				'<td><select name="%s[]" id="%s">%s</select></td>' .
			'</tr>';
	
		$following_row =
			'<tr>' .
				'<td>&nbsp;</td>' .
				//'<td><img src="/extensions/GWToolset/resources/images/b_drop.png"/></td>' .
				'<td width="16" class="subtract-metadata"></td>' .
				'<td><select name="%s[]" id="%s">%s</select></td>' .
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
				$parameter_as_id,
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
							$parameter_as_id,
							$parameter_as_id,
							$this->getMetadataAsOptions( $option )
						);

						$first_row_placed = true;

					} else {

						$result .= sprintf(
							$following_row,
							$parameter_as_id,
							$parameter_as_id,
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