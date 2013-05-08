<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Models;
use DatabaseUpdater,
	Php\Filter,
	ResultWrapper;


abstract class Model implements ModelInterface {


	/**
	 * @var DatabaseBase
	 */
	protected $dbr;


	/**
	 * @var DatabaseBase
	 */
	protected $dbw;


	/**
	 * @var string table associated with this object
	 */
	public $table_name;


	/**
	 * @var string path to the table create sql file
	 */
	protected $table_create_sql;


	/**
	 * this query should return a result set that contians a key_name and optionally
	 * a key_group value
	 *
	 * @return {ResultWrapper} sql query result
	 */
	abstract protected function getKeys();


	/**
	 *
	 */
	public function getModelKeysAsOptions( &$options = array() ) {

		$result = '<option></option>';

		foreach( $options as $option ) {

			$result .= sprintf( '<option>%s</option>', $option->key_name );

		}

		return $result;

	}


	/**
	 * creates an html select element that allows the user to select a row from
	 * the model. the rows that become part of the select are based on the query
	 * carried out by the getKeys() method, which is set in each model inheriting
	 * this class.
	 *
	 * @param string $name form name that should be given to the select
	 * @param string $id form id that should be given to the select
	 * @return string an html select element
	 */
	public function getModelKeysAsSelect( $name = null, $id = null ) {

		if ( !empty( $name ) ) { $name = sprintf( ' name="%s"', Filter::evaluate( $name ) ); }
		if ( !empty( $id ) ) { $id = sprintf( ' id="%s"', Filter::evaluate( $id ) ); }

		$options = $this->getKeys();

		$result =
			sprintf( '<select%s%s>', $name, $id ) .
			$this->getModelKeysAsOptions( $options ) .
			'</select>';

		return $result;

	}


	/**
	 * @param string $table_name
	 * @param string $table_create_sql filename containing the create table sql statements
	 */
	public function __construct( $table_name = null ) {

		$this->dbr = \wfGetDB( DB_SLAVE );
		$this->dbw = \wfGetDB( DB_MASTER );
		$this->table_name = $table_name;
		$this->setTableCreateSql();

	}


}