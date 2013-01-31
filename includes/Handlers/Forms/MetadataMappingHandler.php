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
			Exception,
			GWToolset\MediaWiki\Api\Client,
			GWToolset\Config,
			GWToolset\Helpers\FileChecks,
			GWToolset\Menu,
			GWToolset\Handlers\Forms\UploadHandler,
			Php\File,
			Php\Filter,
			SpecialPage,
			XMLReader;


class MetadataMappingHandler extends UploadHandler {


	/**
	 * @var Template Object
	 */
	protected $Template;


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


	protected function getMWClient() {

		$MWApiClient = new Client( Config::$api_internal_endpoint, $this->SpecialPage );
		$MWApiClient->login( Config::$api_internal_lgname, Config::$api_internal_lgpassword );
		$MWApiClient->debug_html .= '<b>API Client - Logged in</b><br/>' . '<pre>' . print_r( $MWApiClient->Login, true ) . '</pre>';
		return $MWApiClient;

	}


	/**
	 * @todo how to deal with url_to_media_file when it is redirected to a file
	 * @todo a. create filename
	 * @todo b. tell api to follow the redirect to get the file
	 * 
	 * @param DOMElement $matching_element
	 * @param array $user_options
	 */
	protected function processMatchingElement( DOMElement &$DOMElement, array &$user_options, array &$mapping ) {

		// use api to get the media via the url
		// use the api to create the description based on the matching element metadata and the mediawiki-template specified in the user_options
		$result = null;


		try {

			$MWApiClient = $this->getMWClient();
			$element_mapped = $this->getElementMapped( $DOMElement, $mapping );

			$this->Template->reset();
			$this->Template->populateFromArray( $element_mapped );
			$filename = $this->getFilename( $this->Template->url_to_the_media_file );

			$api_result = $MWApiClient->upload(
				array(
					'filename' => $filename,
					'text' => $this->Template->getTemplate(),
					'token' => $MWApiClient->getEditToken(),
					'ignorewarnings' => true,
					'url' => $this->Template->url_to_the_media_file
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

		} catch( Exception $e ) {

			$result .= '<h1>' . wfMessage( 'gwtoolset-api-error' ) . '</h1>' .
				'<span class="error">' . $filename . '</span><br/>' .
				'<span class="error">' . $e->getMessage() . '</span><br/>';

		}

		if ( Config::$display_debug_output
			&& $this->SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' )
			&& isset( $MWApiClient )
		) {
		
			$result .= $MWApiClient->debug_html;
		
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
	protected function findMatchingDOMElement( XMLReader &$reader, array &$user_options ) {

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
	 * @param Php\File $File
	 * @param array $user_options
	 * @param array $mapping
	 * 
	 * @throws Exception
	 * @return DOMElement|null
	 *
	 * @todo: handle invalid xml
	 * @todo: incomplete/partial uploads
	 */
	protected function processDOMElements( array &$user_options, array $mapping ) {

		$result = null;
		$DOMElement = null;
		$reader = new XMLReader();
		if ( !$reader->open( $this->File->tmp_name ) ) { throw new Exception( wfMessage('gwtoolset-xmlreader-open-error') ); }


		while ( $reader->read() ) {

			$DOMElement = $this->findMatchingDOMElement( $reader, $user_options );

			if ( !is_null( $DOMElement ) ) {

				$result .= $this->processMatchingElement( $DOMElement, $user_options, $mapping );

			}

			$DOMElement = null;

		}


		if ( !$reader->close() ) { throw new Exception( wfMessage('gwtoolset-xmlreader-close-error') ); }

		$result =
			'<h2>Results</h2>' .
			'<p>' . $user_options['record-count'] . ' record(s) uploaded. Links to the uploaded file(s)</p>' .
			'<ul>' .
				$result .
			'</ul>';

		return $result;

	}


	protected function getMapping( array &$user_options ) {

		$result = array();
		$template = $this->getValidMediaWikiTemplate( $user_options['mediawiki-template'] );
		$this->Template = new $template();

		foreach( $this->Template->getPropertyReflection() as $property ) {

			if ( isset( $_POST[ $property ] ) && trim( $_POST[ $property ] ) !== '' ) {

				$result[ $property ] = Filter::evaluate( array( 'source' => $_POST, 'name' => $property ) );

			}

		}

		return $result;
	
	}


	/**
	 * @return {string} $result an html string
	 */
	protected function processUpload() {

		$result = null;
		$mapping = null;
		$user_options = array(
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
			'mediawiki-template' => !empty( $_POST['mediawiki-template'] ) ? Filter::evaluate( $_POST['mediawiki-template'] ) : 'Artwork',
			'record-count' => 0
		);

		try {

			$this->validateUserOptions(
				array(
					'record-element-name',
					'mediawiki-template'
				),
				$user_options
			);

			$this->File = new File( $_FILES['uploaded-metadata'] );
			FileChecks::isUploadedFileValid( $this->File );

			$mapping = $this->getMapping( $user_options );
			$result .= $this->processDOMElements( $user_options, $mapping );

			// html should be a sumary of records processed : total count, links to the files created
			//$html .= '<pre>' . print_r( $_POST, true ) .  '</pre>';
			//$html .= '<pre>' . print_r( $mapping, true ) .  '</pre>';
			//$html .= '<pre>' . print_r( $_FILES, true ) .  '</pre>';

		} catch( Exception $e ) {

			$result .= '<h2>' . wfMessage( 'gwtoolset-metadata-mapping-error' ) . '</h2>' .
				'<span class="error">' . $e->getMessage() . '</span><br/>';

		}

		return $result;

	}


}

