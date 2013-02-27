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
			GWToolset\Config,
			GWToolset\MediaWiki\Api\Client,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplate,
			Php\Filter,
			SpecialPage,
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


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $_MWApiClient;


	/**
	 * @var SpecialPage
	 */
	protected $_SpecialPage;
	
	

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


	/**
	 * @param {string} $filename
	 *
	 * @throws Exception
	 *
	 * @return {string}
	 * an html <li> element that conatins an api error message or link to the
	 * created wiki page
	 */
	protected function createPage( $filename = null ) {

		$result = null;
		$api_result = array();

			if ( empty( $filename ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no filename provided' ) );

			}

			$api_result = $this->_MWApiClient->upload(
				array(
					'filename' => $filename,
					'text' => $this->_MediawikiTemplate->getTemplate(),
					'token' => $this->_MWApiClient->getEditToken(),
					'ignorewarnings' => true,
					'url' => $this->_MediawikiTemplate->template_parameters['url_to_the_media_file']
				)
			);

			if ( empty( $api_result['upload']['result'] )
				|| $api_result['upload']['result'] !== 'Success'
				|| empty( $api_result['upload']['imageinfo']['descriptionurl'] )
				|| empty( $api_result['upload']['filename'] )
			) {

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

		return $result;

	}


	/**
	 * @param {int} $page_id
	 * @param {string} $filename
	 *
	 * @throws Exception
	 *
	 * @return {string}
	 * an html <li> element that conatins an api error message or link to the
	 * created wiki page
	 */
	protected function updatePage( $page_id = -1, $filename = null ) {

		$result = null;
		$api_result = array();
		global $wgArticlePath;

			if ( $page_id < 0 ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'invalid page-id provided' ) );

			}

			if ( empty( $filename ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no filename provided' ) );

			}

			$api_result = $this->_MWApiClient->edit(
				array(
					'pageid' => $page_id,
					'text' => $this->_MediawikiTemplate->getTemplate(),
					'token' => $this->_MWApiClient->getEditToken()
				)
			);

			if ( empty( $api_result['edit']['result'] )
				|| $api_result['edit']['result'] !== 'Success'
				|| empty( $api_result['edit']['title'] )
			) {

				$result .= '<h1>' . wfMessage( 'mw-api-client-unknown-error' ) . '</h1>' .
					'<span class="error">' . $filename . '</span><br/>' .
					'<span class="error">' . $e->getMessage() . '</span><br/>';

			}

			$result .=
				'<li>' .
					'<a href="' . str_replace( '$1', $api_result['edit']['title'], $wgArticlePath ) . '">' .
						$api_result['edit']['title'] .
						( isset( $api_result['edit']['oldrevid'] ) ? ' ( revised )' : ' ( no change )' ) .
					'</a>' .
				'</li>';

		return $result;

	}


	/**
	 * assumes that $this->_MediawikiTemplate has been populated with metadata
	 * from a DOMElement and queries the wiki for a page title based on that
	 * information
	 *
	 * @param {string} $filename
	 *
	 * @return int
	 * a matching page id in the wiki or -1 if no match found
	 */
	protected function getTitlePageId( $filename = null ) {

		$page_id = -1;
		$api_result = array();

			if ( empty( $filename ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no filename provided' ) );

			}

			$api_result = $this->_MWApiClient->query( array( 'titles' => 'File:' . $filename, 'indexpageids' => '' ) );

			if ( empty( $api_result['query']['pageids'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'api-result does not contain expected keys [query] and/or [query][pageids]' ) );

			}

		return (int)$api_result['query']['pageids'][0];

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

				return Filter::evaluate(
					array(
						'source' => $DOMNodeElement->nodeValue,
						'filter-sanitize' => FILTER_SANITIZE_URL
					)
				);

			} else {

				return Filter::evaluate( $DOMNodeElement->nodeValue );

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
	protected function getDOMElementMapped( DOMElement &$DOMElement ) {

		$elements_mapped = array();
		$is_url = false;
		$DOMNodeList = $DOMElement->getElementsByTagName( '*' );

			foreach( $DOMNodeList as $DOMNodeElement ) {

				if ( !key_exists( $DOMNodeElement->tagName, $this->_Mapping->target_dom_elements_mapped ) ) { continue; }
				$template_parameters = $this->_Mapping->target_dom_elements_mapped[ $DOMNodeElement->tagName ];

				foreach( $template_parameters as $template_parameter ) {

					if ( strpos( $template_parameter, 'url' ) !== false ) { $is_url = true; } else { $is_url = false; }

					if ( !isset( $elements_mapped[ $template_parameter ] ) ) {

						$elements_mapped[ $template_parameter ] = $this->getFilteredNodeValue( $DOMNodeElement, $is_url );

					} else {

						$elements_mapped[ $template_parameter ] .=
							Config::$metadata_separator .
							$this->getFilteredNodeValue( $DOMNodeElement, $is_url );

					}

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
	 *
	 * @return {string}
	 * an html string with the <li> element results from the api createPage(),
	 * updatePage() calls plus $this->_MWApiClient->debug_html if gwtoolset-debuging
	 * is on and the user is a gwtoolset-debug user
	 */
	protected function processMatchingElement( DOMElement &$DOMElement, array &$user_options ) {

		$result = null;
		$element_mapped_to_mediawiki_template = array();
		$page_id = -1;
		$filename = null;

			$element_mapped_to_mediawiki_template = $this->getDOMElementMapped( $DOMElement );
			$this->_MediawikiTemplate->populateFromArray( $element_mapped_to_mediawiki_template );
			$filename = $this->_MediawikiTemplate->getTitle();
			$page_id = $this->getTitlePageId( $filename );

			if ( $page_id > -1 ) { // page already exists only change text

				$result .= $this->updatePage( $page_id, $filename );

			} else { // page does not yet exist upload image and template text
		
				$result .= $this->createPage( $filename );

			}

			if ( Config::$display_debug_output
				&& $this->_SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' )
			) {

				$result .= $this->_MWApiClient->debug_html;

			}

		return $result;

	}


	/**
	 * using an xml reader, for stream reading of the xml file, find dom elements
	 * that match the metadata record, indicated by the user form,
	 * $user_options['record-element-name']
	 *
	 * each matched metadata record, is sent to processMatchingElement() to be
	 * saved as a new wiki page or to update an existing wiki page for the record
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
	public function processDOMElements( XMLReader &$xml_reader, array &$user_options ) {

		$result = array( 'msg' => null, 'stop-reading' => false );
		$DOMElement = null;

			if ( empty( $xml_reader ) ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('no XMLReader provided' ) );

			}

			if ( !isset( $user_options['record-element-name'] ) || !isset( $user_options['record-count'] ) ) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('record-element-name, record-count not provided' ) );

			}
			
			switch ( $xml_reader->nodeType ) {

				case ( XMLReader::ELEMENT ):

					if ( $xml_reader->name == $user_options['record-element-name'] ) {

						$user_options['record-count'] += 1;
						$DOMElement = $xml_reader->expand();
						
						if ( !empty( $DOMElement ) && $DOMElement instanceof DOMElement ) {

							$result['msg'] = $this->processMatchingElement( $DOMElement, $user_options );

							$result['msg'] =
								'<p>' . $user_options['record-count'] . ' record(s) uploaded. Links to the uploaded file(s)</p>' .
								'<ul>' .
									$result['msg'] .
								'</ul>';

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
	 *
	 * @return
	 * 
	 */
	public function processXml( array &$user_options, $file_path_local = null ) {

		$result = null;

			$result .= '<h2>Results</h2>';
			$result .= $this->readXml( $user_options, $file_path_local, 'processDOMElements' );

		return $result;

	}


	public function __construct( SpecialPage $SpecialPage, Mapping $Mapping, MediawikiTemplate $MediawikiTemplate ) {

		$this->_SpecialPage = $SpecialPage;
		$this->_Mapping = $Mapping;
		$this->_MediawikiTemplate = $MediawikiTemplate;
		$this->_MWApiClient = \GWToolset\getMWApiClient( $this->_SpecialPage );

	}


}