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
	protected $table_name;


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
	public function getModelKeysAsOptionGroup( &$options = array() ) {

		$result = '<option></option>';
		$value = null;
		$option_group = null;
		$key_group = null;
		$key_name = null;

		foreach( $options as $option ) {

			if ( $option_group != $option->key_group ) {

				if ( !empty( $option_group ) ) { $result .= '</optgroup>'; }

				$key_group = Filter::evaluate( $option->key_group );
				$result .= sprintf( '<optgroup label="%s">', $key_group );
				$option_group = $option->key_group;

			}

			$key_name = Filter::evaluate( $option->key_name );
			$value = "{`user-name`:`" . $key_group . "`,`mapping-name`:`" . $key_name . "`}";
			$result .= sprintf( '<option value="%s">%s</option>', $value, $key_name );

		}

		return $result;

	}
	
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
	public function getModelKeysAsSelect( $name = null, $id = null, $option_group = false ) {

		if ( !empty( $name ) ) { $name = sprintf( ' name="%s"', Filter::evaluate( $name ) ); }
		if ( !empty( $id ) ) { $id = sprintf( ' id="%s"', Filter::evaluate( $id ) ); }

		$options = $this->getKeys();

		$result = sprintf( '<select%s%s>', $name, $id );

			if ( $option_group ) {

				$result .= $this->getModelKeysAsOptionGroup( $options );

			} else {

				$result .= $this->getModelKeysAsOptions( $options );

			}

		$result .= '</select>';

		return $result;

	}


	/**
	 * @todo: implement this method so that it checks to see if the defaults exist
	 * and if not adds them
	 * @todo: if the defaults exist, determine if their is an update script for them
	 * and run that if necessary
	 *
	 * based on core/includes/installer/MysqlUpdater.php::doUserGroupsUpdate
	 */
	public function insertDefaultMappings() {

		//global $wgGWToolsetDir;
		//
		//if ( !$updater->tableExists( 'gwtoolset_mappings' ) ) { return; }
		//
		//$updater->getDB()->
		//	array(
		//		'addField',
		//		'uw_campaigns',
		//		'uw_campaigns_name',
		//		$wgGWToolsetDir . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'default-mappings-insert.sql',
		//		true
		//	)
		//);

	}


	public function createTable( DatabaseUpdater &$updater ) {

		$updater->addExtensionTable(
			$this->table_name,
			$this->table_create_sql
		);

	}


	protected function setTableCreateSql() {

		global $wgGWToolsetDir;

		$this->table_create_sql =
			$wgGWToolsetDir . DIRECTORY_SEPARATOR .
			'sql' . DIRECTORY_SEPARATOR .
			'table-create-' . str_replace( '_', '-', $this->table_name ) . '.sql';

	}


	public function reset() {

		$this->dbr = \wfGetDB( DB_SLAVE );
		$this->dbw = \wfGetDB( DB_MASTER );
		$this->table_name = null;

	}


	/**
	 * @param string $table_name
	 * @param string $table_create_sql filename containing the create table sql statements
	 */
	public function __construct( $table_name ) {

		$this->reset();
		$this->table_name = $table_name;
		$this->setTableCreateSql();

	}


}