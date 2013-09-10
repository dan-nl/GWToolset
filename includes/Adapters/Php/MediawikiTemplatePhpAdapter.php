<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Adapters\Php;
use Exception,
	GWToolset\Adapters\DataAdapterInterface,
	GWToolset\Config,
	Php\Filter;

class MediawikiTemplatePhpAdapter implements DataAdapterInterface {

	/**
	 * @param {string} $table_name
	 * @return {void}
	 */
	public function __construct() {
	}

	/**
	 * returns an indexed array of key values from the Config::$mediawiki_templates
	 * array, which represents the mediawiki templates handled by the extension
	 *
	 * @return {array}
	 */
	public function getKeys() {
		return array_keys( Config::$mediawiki_templates );
	}

	public function create( array $options = array() ) {
	}

	/**
	 * @param {array} $options
	 * @return {array}
	 */
	public function retrieve( array $options = array() ) {
		if ( !isset( Config::$mediawiki_templates[Filter::evaluate( $options['mediawiki_template_name'] )] ) ) {
			throw new Exception(
				wfMessage( 'gwtoolset-mediawiki-template-not-found' )
					->rawParams( Filter::evaluate( $options['mediawiki_template_name'] ) )
						->escaped()
				);
		}

		return array(
			'mediawiki_template_json' => Config::$mediawiki_templates[Filter::evaluate( $options['mediawiki_template_name'] )]
		);
	}

	public function update( array $options = array() ) {
	}

	public function delete( array $options = array() ) {
	}
}
