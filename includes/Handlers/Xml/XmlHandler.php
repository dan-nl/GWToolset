<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Xml;
use Exception,
	XMLReader;

abstract class XmlHandler {

	public abstract function __construct();

	/**
	 * a debug method for testing the reader
	 * @param XMLReader $reader
	 */
	protected function displayCurrentNodeProperties( XMLReader $reader ) {
		echo 'attributeCount : ' . $reader->attributeCount . '<br />';
		echo 'baseURI : ' .$reader->baseURI . '<br />';
		echo 'depth : ' .$reader->depth . '<br />';
		echo 'hasAttributes : ' .$reader->hasAttributes . '<br />';
		echo 'hasValue : ' .$reader->hasValue . '<br />';
		echo 'isDefault : ' .$reader->isDefault . '<br />';
		echo 'isEmptyElemet : ' .$reader->isEmptyElement . '<br />';
		echo 'localName : ' .$reader->localName . '<br />';
		echo 'name : ' .$reader->name . '<br />';
		echo 'namespaceURI : ' .$reader->namespaceURI . '<br />';
		echo 'nodeType : ' .$reader->nodeType . '<br />';
		echo 'prefix : ' .$reader->prefix . '<br />';
		echo 'value : ' .$reader->value . '<br />';
		echo 'xmlLang : ' .$reader->xmlLang . '<br />';
		echo '<br />';
	}

	/**
	 * a debug method
	 */
	protected function getNodesInfo( $node ) {
		if ($node->hasChildNodes() ) {
			$subNodes = $node->childNodes;

			foreach ($subNodes as $subNode) {
				if ( ( $subNode->nodeType != 3 ) ||
					( ( $subNode->nodeType == 3 ) &&
					( strlen( trim( $subNode->wholeText ) ) >= 1 ) )
				) {
					echo "Node name: ".$subNode->nodeName."<br />";
					echo "Node value: ".$subNode->nodeValue."<br />";
					echo '<br />';
				}
				$this->getNodesInfo($subNode);
			}
		}
	}

	/**
	 * opens the xml file as a stream and sends the stream to other methods in
	 * via the $callback to process the file. allows for the reader to be stopped
	 * if the $callback method returns true to the $stop_reading variable
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $file_path_local
	 * a local wiki path to the xml metadata file. the assumption is that it
	 * has been uploaded to the wiki earlier and is ready for use
	 *
	 * @param {string} $callback
	 * the method that will be used to process the read xml file
	 *
	 * @todo: handle invalid xml
	 * @todo: handle no record-element-name found, specified element does not exist
	 * @todo: how to handle attributes and children nodes
	 * @todo: how to store entire file while only reading first node and preparing for element to template matching
	 * @todo: upload by url use internal upload process rather than the api
	 * @todo: parse the actual Artwork template for attributes rather than rely on a hard-coded class
	 * @todo: setup so that record x can be used for mapping rather than only the first record, which is the current default
	 * @todo: figure out a batch job processing method
	 * @todo: handle mal-formed xml (future)
	 * @todo: handle an xml schema if present (future)
	 * @todo: handle incomplete/partial uploads (future)
	 *
	 * @throws Exception
	 *
	 * @return {string}
	 */
	protected function readXml( array &$user_options, $file_path_local = null, $callback = null ) {
		$result = null;
		$read_result = array( 'msg' => null, 'stop-reading' => false );
		$xml_reader = null;

		if ( empty( $file_path_local ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-local-path' )->plain() )->parse() );
		}

		if ( empty( $callback ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-callback' )->plain() )->parse() );
		}

		$xml_reader = new XMLReader();

		if ( !$xml_reader->open( $file_path_local ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-could-not-open-xml' )->plain() )->parse() );
		}

		while ( $xml_reader->read() ) {
			$read_result = $this->$callback( $xml_reader, $user_options );
			$result .= $read_result['msg'];

			if ( $read_result['stop-reading'] ) {
				break;
			}
		}

		if ( !$xml_reader->close() ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-could-not-close-xml' )->plain() )->parse() );
		}

		return $result;
	}

	public abstract function processXml( array &$user_options, $file_path_local = null );

}
