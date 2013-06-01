<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Adapters\Db;
use Php\Filter;

class MediawikiTemplateDbAdapter extends DbAdapterAbstract {

	public function __construct( $table_name = 'gwtoolset_mediawiki_templates' ) {
		parent::__construct( $table_name );
	}

	public function getKeys() {
		return $this->dbr->select(
			'gwtoolset_mediawiki_templates',
			array( 'key_name' => 'mediawiki_template_name' ),
			null,
			null,
			array( 'ORDER BY' => 'mediawiki_template_name ASC' )
		);
	}

	public function create( array $options = array() ) {}

	public function retrieve( array $options = array() ) {
		return $this->dbr->select(
			Filter::evaluate( $this->table_name ),
			'mediawiki_template_name, mediawiki_template_json',
			'mediawiki_template_name = ' . $this->dbr->addQuotes( Filter::evaluate( $options['mediawiki_template_name'] ) )
		);
	}

	public function update( array $options = array() ) {}

	public function delete( array $options = array() ) {}

}
