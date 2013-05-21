<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Models;
use Exception,
	GWToolset\Adapters\DataAdapterInterface,
	GWToolset\Helpers\WikiPages,
	Php\Filter,
	ResultWrapper;


class Mapping extends Model {


	public $user_name;
	public $mapping_name;
	public $mediawiki_template_name;
	public $mapping_json;
	public $created;


	/**
	 * @var array
	 */
	public $mapping_array = array();


	/**
	 * @var array
	 * an array to be used for quick look-up of target dom elements to be
	 * used in the metadata for mapping to the mediawiki template; avoids
	 * the necessity of recursive look-up in the mapping array
	 */
	public $target_dom_elements = array();


	/**
	 * @var array
	 * holds an array of metadata dom elements mapped to their corresponding
	 * mediawiki template parameters
	 */
	public $target_dom_elements_mapped = array();


	/**
	 * @var GWToolset\Adapters\DataAdapterInterface
	 */
	protected $_DataAdapater;


	/**
	 * assumes that the details for the metadata-mapping are in
	 * $options['metadata-mapping'] - when retrieving from the db
	 * $options['metadata-mapping-url'] - when retrieiving from a wiki page
	 *
	 * @param array $options
	 * @throws Exception
	 * @return array should contain the :
	 *   - user-name for both db & wiki retrieval, used to set User:name namespace
	 *   - mapping-name = column in db or
	 *   - mapping-name = page name in wiki
	 */
	protected function getMappingDetails( array &$options ) {

		$result = array();

		if ( isset( $options['metadata-mapping'] ) ) {

			$result = json_decode( str_replace( "`", '"', $options['metadata-mapping'] ), true );

		}

		if ( isset( $options['metadata-mapping-url'] ) ) {

			$result = WikiPages::getUsernameAndPageFromUrl( $options['metadata-mapping-url'] );

		}

		if ( !empty( $result ) && ( !isset( $result['user-name'] ) || !isset( $result['mapping-name'] ) ) ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-mapping-info-missing' )->plain() )->parse() );

		}

		return $result;

	}


	public function reverseMap() {

		foreach( $this->target_dom_elements as $element ) {

			foreach( $this->mapping_array as $mediawiki_parameter => $target_dom_elements ) {

				if ( in_array( $element, $target_dom_elements ) ) {

					$this->target_dom_elements_mapped[ $element ][] = $mediawiki_parameter;

				}

			}

		}

	}


	public function setTargetElements() {

		foreach( $this->mapping_array as $key => $value ) {

			foreach( $value as $item ) {

				if ( !in_array( $item, $this->target_dom_elements ) && !empty( $item ) ) {

					$this->target_dom_elements[] = $item;

				}

			}

		}

	}


	protected function getKeys() {

		return $this->_DataAdapater->getKeys();

	}


	/**
	 * expects a properites array containing
	 *
	 * $properties['user_name']
	 * $properties['mapping_name']
	 * $properties['mediawiki_template_name']
	 * $properties['mapping_json']
	 * $properties['created']
	 *
	 * @param array $properties
	 * @throws Exception
	 * @return void
	 *
	 * @todo filter/sanitize the mapping
	 * @todo filter/sanitize created
	 */
	protected function populate( array &$properties ) {

		global $wgArticlePath;
		$error_msg = null;
		$mapping_template = null;
		$json_error = JSON_ERROR_NONE;

		if ( empty( $properties ) ) { return; }

		$this->user_name = Filter::evaluate( $properties['user_name'] );
		$this->mapping_name = Filter::evaluate( $properties['mapping_name'] );
		$this->mediawiki_template_name = Filter::evaluate( $properties['mediawiki_template_name'] );
		$this->mapping_json = $properties['mapping_json'];
		$this->mapping_array = json_decode( $this->mapping_json, true );
		$this->created = $properties['created'];

		$json_error = json_last_error();

		if ( $json_error != JSON_ERROR_NONE ) {

			switch ( json_last_error() ) {

				case JSON_ERROR_NONE:
					$error_msg = 'No errors';
					break;

				case JSON_ERROR_DEPTH:
					$error_msg = 'Maximum stack depth exceeded';
					break;

				case JSON_ERROR_STATE_MISMATCH:
					$error_msg = 'Underflow or the modes mismatch';
					break;

				case JSON_ERROR_CTRL_CHAR:
					$error_msg = 'Unexpected control character found';
					break;

				case JSON_ERROR_SYNTAX:
					$error_msg = 'Syntax error, malformed JSON';
					break;

				case JSON_ERROR_UTF8:
					$error_msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
					break;

				default:
					$error_msg = 'Unknown error';
					break;

			}

			$mapping_template = 'User:' . Filter::evaluate( $properties['user_name'] ) . '/' . Filter::evaluate( $properties['mapping_name'] );

			$error_msg .=
				' ' .
				'<a href="' . str_replace( '$1', $mapping_template, $wgArticlePath ) . '">' .
					$mapping_template .
				'</a>';

			throw new Exception( wfMessage( 'gwtoolset-metadata-mapping-bad' )->rawParams( $error_msg )->plain() );

		}

		$this->setTargetElements();
		$this->reverseMap();

	}


	/**
	 * @todo validate the array
	 */
	public function create( array $options = array() ) {

		return $this->_DataAdapater->create( $options );

	}


	/**
	 * relies on hard coded keys in the $user_options to retrieve a metadata
	 * mapping stored in the wiki db
	 *
	 * - $user_options['metadata-mapping']
	 * - $user_options['mediawiki-template-name']
	 *
	 * the expected $mapping_details should evaluate to the following hard-coded keys
	 *
	 * - $mapping_details['user-name']
	 * - $mapping_details['mapping-name']
	 *
	 * the result that populates the model should be an array that contains
	 *
	 *   $result['user_name']
	 *   $result['mapping_name']
	 *   $result['mediawiki_template_name']
	 *   $result['mapping_json']
	 *   $result['created']
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @throws Exception
	 * @return void
	 */
	public function retrieve( array $options = array() ) {

		$result = array();

		$mapping_details = $this->getMappingDetails( $options );

		if ( empty( $options['mediawiki-template-name'] ) ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-cannot-retrieve-mapping' )->plain() )->parse() );

		}

		if ( !empty( $mapping_details ) ) {

			$result = $this->_DataAdapater->retrieve(
				array(
					'user-name' => $mapping_details['user-name'],
					'mapping-name' => $mapping_details['mapping-name'],
					'mediawiki-template-name' => $options['mediawiki-template-name']
				)
			);

		}

		$this->populate( $result );

	}


	public function update( array $options = array() ) {}
	public function delete( array $options = array() ) {}


	public function __construct( DataAdapterInterface $DataAdapter ) {

		$this->_DataAdapater = $DataAdapter;

	}


}