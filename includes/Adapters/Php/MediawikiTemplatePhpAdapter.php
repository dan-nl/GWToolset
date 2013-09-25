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
	MWHttpRequest,
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
	 * retrieves a json representation of the Mediawiki Template
	 * - attempts to retrieve a TemplateData version of the template
	 * - falls back to a Config::$mediawiki_templates version if not found
	 *
	 * @param {array} $options
	 * @return {array}
	 */
	public function retrieve( array $options = array() ) {
		$result = array( 'mediawiki_template_json' => '' );
		$template_data = null;

		if ( !isset( Config::$mediawiki_templates[Filter::evaluate( $options['mediawiki_template_name'] )] ) ) {
			throw new Exception(
				wfMessage( 'gwtoolset-mediawiki-template-not-found' )
					->rawParams( Filter::evaluate( $options['mediawiki_template_name'] ) )
						->escaped()
				);
		}

		$template_data = $this->retrieveTemplateData( $options );

		if ( empty ( $template_data ) ) {
			$result['mediawiki_template_json'] = Config::$mediawiki_templates[Filter::evaluate( $options['mediawiki_template_name'] )];
		} else {
			$result['mediawiki_template_json'] = $template_data;
		}

		return $result;
	}

	/**
	 * attempts to retrieve a TemplateData version of the Mediawiki Template
	 * if TemplateData isfound, it is prepared as a JSON string in an expected
	 * format -- {"parameter name":""}
	 *
	 * @param {array} $options
	 * @return {string|null}
	 */
	protected function retrieveTemplateData( array $options ) {
		$result = null;
		$api_result = array();

		$Http = MWHttpRequest::factory(
			'/api.php?action=templatedata&titles=Template:' . Filter::evaluate( $options['mediawiki_template_name'] )
		);

		$Status = $Http->execute();

		if ( !$Status->ok ) {
			throw new Exception(
				wfMessage( 'gwtoolset-developer-issue' )->params(
					wfMessage( 'gwtoolset-api-call-unsuccessful' )->params(
						__METHOD__
					)->escaped()
				)->escaped()
			);
		}

		$api_result = json_decode( $Http->getContent(), true );

		try {
			\GWToolset\jsonCheckForError();
		} catch ( Exception $e ) {
			throw new Exception( wfMessage( 'gwtoolset-json-error' )->rawParams( $e->getMessage() )->parse() );
		}

		if ( isset ( $api_result['pages'] ) && count ( $api_result['pages'] ) === 1 ) {
			$api_result = array_shift( $api_result['pages'] );

			if ( count ( $api_result['params'] ) > 0 ) {
				foreach ( $api_result['params'] as $key => $value ) {
					if ( !$value['deprecated'] ) {
						$result[$key] = '';
					}
				}

				$result = json_encode( $result );
			}
		}

		return $result;
	}

	public function update( array $options = array() ) {
	}

	public function delete( array $options = array() ) {
	}
}
