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
namespace GWToolset\Adapters\Api;
use Exception,
	GWToolset\Config,
	GWToolset\MediaWiki\Api\Client,
	GWToolset\Helpers\WikiPages,
	Php\Filter;


class MappingApiAdapter extends ApiAdapterAbstract {


	protected function savePage( array &$options ) {

		return $this->_MWApiClient->edit(
			array(
				'title' => $options['title'],
				'summary' => $options['summary'],
				'text' => $options['text'],
				'token' => $this->_MWApiClient->getEditToken()
			)
		);

	}


	/**
	 * @param array $options
	 *
	 *   $options['user_name']
	 *   $options['mapping_name']
	 *   $options['mediawiki_template_name']
	 *   $options['mapping_json']
	 *   $options['created']
	 */
	public function create( array $options = array() ) {

		$pageid = -1;
		$title = null;
		$result = false;

			if ( empty( $options['mapping_json'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no mapping_json provided' ) );

			}

			if ( empty( $options['user_name'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no user_name provided' ) );

			}

			if ( empty( $options['mapping_name'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'no mapping_name provided' ) );

			}

			// nb: cannot filter the json - maybe need to test it as valid by converting it back and forth with json_decode/encode
			$options['text'] = Config::$metadata_mapping_open_tag . $options['mapping_json'] . Config::$metadata_mapping_close_tag . '[[Category:' . Config::$metadata_mapping_category . ']]';
			// yes this is a strange concatenation with the / but for now it's needed so that when the save mapping in step 2 happens the corret mapping name comes up
			$options['title'] = 'User:' . $options['user_name'] . '/' . Config::$metadata_mapping_subdirectory . $options['mapping_name'];
			$pageid = WikiPages::getTitlePageId( $options['title'] );

			if ( $pageid > -1 ) { // page already exists

				$options['summary'] = 'updating metadata mapping for User:' . $options['user_name'];

			} else { // page does not yet exist

				$options['summary'] = 'creating metadata mapping for User:' . $options['user_name'];

			}

			$api_result = $this->savePage( $options );

			if ( empty( $api_result['edit'] )
				|| $api_result['edit']['result'] !== 'Success'
			) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( 'api result was not in the expected format' ) );

			}

			if ( $api_result['edit']['result'] == 'Success' ) {	$result = true; }

		return $result;

	}


	/**
	 * @param {array} $options
	 *   $options[user-name]
	 *   $options[mapping-name] = the path to the page
	 */
	public function retrieve( array $options = array() ) {

		global $wgArticlePath;
		$result = array();
		$api_result = null;
		$matches = array();
		$mapping_template = null;
		$error_msg = null;

			$api_result = WikiPages::retrieveWikiPageContents( $options );

			// need to remove line breaks from the mapping otherwise the json_decode will error out
			$api_result = str_replace( PHP_EOL, '', $api_result );

			//http://stackoverflow.com/questions/6109882/regex-match-all-characters-between-two-strings#answer-6110113
			$api_result = preg_match('/(?<=<mapping_json>)(.*)(?=<\/mapping_json>)/', $api_result, $matches );

			if ( !isset( $matches[0] ) ) {

				$mapping_template = 'User:' . $options['user-name'] . '/' . $options['mapping-name'];

				$error_msg .=
					' ' .
					'<a href="' . str_replace( '$1', $mapping_template, $wgArticlePath ) . '">' .
						$mapping_template .
					'</a>';

				throw new Exception( wfMessage('gwtoolset-metadata-mapping-wikitext-bad')->rawParams( $error_msg ) );

			}

			$result['user_name'] = $options['user-name'];
			$result['mapping_name'] = $options['mapping-name'];
			$result['mediawiki_template_name'] = $options['mediawiki-template-name'];
			$result['mapping_json'] = $matches[0];
			$result['created'] = null;

		return $result;

	}


	public function update( array $options = array() ) {}


	public function delete( array $options = array() ) {}


	public function __construct( Client $MWApiClient ) {

		parent::__construct( $MWApiClient );

	}


}