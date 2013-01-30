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
namespace	GWToolset\Models;
use			Exception,
			Php\Filter,
			ResultWrapper;


class Mapping extends Model {


	public $user_name;
	public $mapping_name;
	public $mediawiki_template;
	public $mapping;
	public $mapping_array;
	public $created;


	public function flattenFormFieldArray( $array = array() ) {

		$result = array();

		foreach( $array as $item ) {

			$result[$item['name']] = $item['value'];

		}

		return $result;

	}


	protected function getKeys() {

		return $this->dbr->select( 'gwtoolset_mappings', 'user_name AS key_group, mapping_name AS key_name', null, null, array( 'ORDER BY' => 'created ASC', 'GROUP BY' => 'key_group, key_name' ) );

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
		$this->mediawiki_template = Filter::evaluate( $result->current()->mediawiki_template );
		$this->mapping = $result->current()->mapping;
		$this->mapping_array = json_decode( $this->mapping, true );
		$this->created = $result->current()->created;

		if ( json_last_error() != JSON_ERROR_NONE ) {

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-bad')->rawParams( $mapping_name ) );

		}

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
	 * @param {string} $mapping_name
	 * @param {string} $mediawiki_template
	 */
	public function retrieve( $params = array() ) {

		$result = null;
		$mapping_name = json_decode( str_replace( "`", '"', $params['metadata-mapping'] ), true );

		$result = $this->dbr->select(
			$this->table_name,
			'user_name, mapping_name, mediawiki_template, mapping, created',
			"user_name = '" . Filter::evaluate( $mapping_name['user-name'] ) . "' AND " .
			"mapping_name = '" . Filter::evaluate( $mapping_name['mapping-name'] ) . "' AND " .
			"mediawiki_template = '" . Filter::evaluate( $params['mediawiki-template'] ) . "'",
			null,
			array('ORDER BY' => 'created DESC', 'LIMIT' => 1)
		);

		if ( empty( $result ) || $result->numRows() != 1 ) {

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-not-found')->rawParams( $params['metadata-mapping'] ) );

		}

		$this->populate( $result );

	}


	public function update() {}
	public function delete() {}


	public function __construct( $table_name = 'gwtoolset_mappings' ) {

		parent::__construct( $table_name );

	}


}

