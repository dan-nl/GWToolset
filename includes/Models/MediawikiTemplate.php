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


class MediawikiTemplate extends Model {


	protected function getKeys() {

		return $this->dbr->select( 'gwtoolset_mediawiki_templates', 'name AS key_name', null, null, array( 'ORDER BY' => 'name ASC' ) );

	}


	protected function populate( ResultWrapper &$result ) {}


	public function create() {}
	public function retrieve() {}
	public function update() {}
	public function delete() {}


	public function __construct( $table_name = 'gwtoolset_mediawiki_templates' ) {

		parent::__construct( $table_name );

	}


}

