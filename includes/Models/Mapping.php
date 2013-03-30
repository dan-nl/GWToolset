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
namespace GWToolset\Models;
use	Exception,
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
	 * 
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

		return $this->dbr->select( 'gwtoolset_mappings', 'user_name AS key_group, mapping_name AS key_name', null, null, array( 'ORDER BY' => 'key_group, key_name DESC', 'GROUP BY' => 'key_group, key_name' ) );

	}


	/**
	 * @param ResultWrapper $result
	 * @throws Exception
	 * @return boolean|null
	 *
	 * @todo filter/sanitize the mapping
	 * @todo filter/sanitize created
	 */
	protected function populate( ResultWrapper &$result ) {

		$this->user_name = Filter::evaluate( $result->current()->user_name );
		$this->mapping_name = Filter::evaluate( $result->current()->mapping_name );
		$this->mediawiki_template_name = Filter::evaluate( $result->current()->mediawiki_template_name );
		$this->mapping_json = $result->current()->mapping_json;
		$this->mapping_array = json_decode( $this->mapping_json, true );
		$this->created = $result->current()->created;

		if ( json_last_error() != JSON_ERROR_NONE ) {

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-bad')->rawParams( $result->current()->mapping_name ) );

		}

		$this->setTargetElements();
		$this->reverseMap();

		return true;

	}


	/**
	 * @todo validate the array
	 */
	public function create( array $values = array() ) {

		$result = $this->dbw->insert( $this->table_name, $values );
	
		if ( $result ) {

			$result = $this->dbw->commit();

		}

		return $result;

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
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @throws Exception
	 * @return void
	 */
	public function retrieve( array &$user_options = array() ) {

		$sql_result = null;
		$mapping_details = array();

		if ( !isset( $user_options['metadata-mapping'] ) ) { return; }
		$mapping_details = json_decode( str_replace( "`", '"', $user_options['metadata-mapping'] ), true );

		if ( !isset( $mapping_details['user-name'] ) || !isset( $mapping_details['mapping-name'] )  ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'mapping user-name and/or mapping-name not set' ) );

		}

		$sql_result = $this->dbr->select(
			$this->table_name,
			'user_name, mapping_name, mediawiki_template_name, mapping_json, created',
			"user_name = '" . Filter::evaluate( $mapping_details['user-name'] ) . "' AND " .
			"mapping_name = '" . Filter::evaluate( $mapping_details['mapping-name'] ) . "' AND " .
			"mediawiki_template_name = '" . Filter::evaluate( $user_options['mediawiki-template-name'] ) . "'",
			null,
			array('ORDER BY' => 'created DESC', 'LIMIT' => 1)
		);

		if ( empty( $sql_result ) || $sql_result->numRows() != 1 ) { return; }

		$this->populate( $sql_result );

	}


	public function update() {}
	public function delete() {}


	public function __construct( $table_name = 'gwtoolset_mappings' ) {
	
		parent::__construct( $table_name );
	
	}

}

