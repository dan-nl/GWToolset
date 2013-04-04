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
namespace GWToolset\Adapters\Db;
use Php\Filter;


class MappingDbAdapter extends DbAdapterAbstract {


	public function getKeys() {

		return $this->dbr->select(
			'gwtoolset_mappings',
			'user_name AS key_group, mapping_name AS key_name',
			null,
			null,
			array(
				'ORDER BY' => 'key_group, key_name DESC',
				'GROUP BY' => 'key_group, key_name'
			)
		);

	}


	public function create( array $options = array() ) {

		$result = null;

			$result = $this->dbw->insert( $this->table_name, $options );

			if ( $result ) {

				$result = $this->dbw->commit();

			}

		return $result;

	}


	public function retrieve( array $options = array() ) {

		$result = array();

			$sql_result = $this->dbr->select(
				$this->table_name,
				'user_name, mapping_name, mediawiki_template_name, mapping_json, created',
				"user_name = '" . Filter::evaluate( $options['user-name'] ) . "' AND " .
				"mapping_name = '" . Filter::evaluate( $options['mapping-name'] ) . "' AND " .
				"mediawiki_template_name = '" . Filter::evaluate( $options['mediawiki-template-name'] ) . "'",
				null,
				array( 'ORDER BY' => 'created DESC', 'LIMIT' => 1 )
			);

			if ( !empty( $sql_result ) && $sql_result->numRows() == 1 ) {

				$result['user_name'] = $sql_result->current()->user_name ;
				$result['mapping_name'] = $sql_result->current()->mapping_name;
				$result['mediawiki_template_name'] = $sql_result->current()->mediawiki_template_name;
				$result['mapping_json'] = $sql_result->current()->mapping_json;
				$result['created'] = $sql_result->current()->created;

			}

		return $result;

	}


	public function update( array $options = array() ) {}


	public function delete( array $options = array() ) {}


	public function __construct( $table_name = 'gwtoolset_mappings' ) {

		parent::__construct( $table_name );

	}


}