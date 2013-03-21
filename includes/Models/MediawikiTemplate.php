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
use	Exception,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	Php\Filter,
	ReflectionClass,
	ReflectionProperty,
	ResultWrapper;


class MediawikiTemplate extends Model {


	/**
	 * @var string
	 * the mediawiki template name
	 */
	public $mediawiki_template_name;


	/**
	 * @var string
	 * a json representation of the mediawiki template parameters
	 */
	public $mediawiki_template_json;


	/**
	 * @var array
	 * the $mediawiki_template_json converted to a php array
	 */
	public $mediawiki_template_array = array();


	protected $_sub_templates = array(
		'language' => '{{%s|%s}}'
	);


	/**
	 * create an array that represents the mapping of mediawiki template
	 * attributes to metadata elements based on the given array; defaults to
	 * the $_POST array anticipated to come from an html form.
	 *
	 * the array is expected to be in an array format for each mediawiki
	 * parameter e.g. accession-number[], artist[]
	 *
	 * @return array
	 *
	 * @todo: how are we using $array - it's ignored at the moment?
	 */
	public function getMappingFromArray( array $array = array() ) {

		$result = array();
		$parameter_as_id = null;
		$metadata_element = null;

			if ( empty( $array ) ) { $array = $_POST; }

			foreach( $this->mediawiki_template_array as $parameter => $value ) {

				$parameter_as_id = $this->getParameterAsId( $parameter );

				if ( isset( $array[ $parameter_as_id ] ) ) {

					foreach( $array[ $parameter_as_id ] as $metadata_element ) {

						$result[ $parameter_as_id ][] = Filter::evaluate( $metadata_element );

					}

				}

			}

		return $result;

	}


	public function getParameterAsId( $parameter ) {

		return str_replace( ' ', '_', $parameter );

	}


	protected function getKeys() {

		return $this->dbr->select(
			'gwtoolset_mediawiki_templates',
			'mediawiki_template_name AS key_name',
			null,
			null,
			array( 'ORDER BY' => 'mediawiki_template_name ASC' )
		);

	}


	/**
	 * creates a title based on
	 *   - title
	 *   - title identifier
	 *
	 * @todo: what if url is not to a file but a re-direct to the file
	 * @todo: eliminate any "safe-guarded characters", e.g. : seems to tell the api
	 * that the file does not exist so it uploads it aknew each time instead of editing it
	 * @todo investigate using Title::makeTitleSafe
	 */
	public function getTitle() {

		$result = null;
		$pathinfo = array();

			//if ( empty( $this->mediawiki_template_array['title'] ) ) {
			//
			//	throw new Exception( wfMessage('gwtoolset-mapping-no-title') );
			//
			//}

			if ( empty( $this->mediawiki_template_array['title_identifier'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-title-identifier') );

			}

			if ( empty( $this->mediawiki_template_array['url_to_the_media_file'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-media-file-url') );

			}

			$result = $this->mediawiki_template_array['title'];
			if ( !empty( $result ) ) { $result .= Config::$title_separator; }
			$result = FileChecks::getValidTitle( $result . $this->mediawiki_template_array['title_identifier'] );
			$pathinfo = pathinfo( $this->mediawiki_template_array['url_to_the_media_file'] );

			if ( empty( $pathinfo['extension'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-media-file-url-extension') );

			}

			$result .= '.' . $pathinfo['extension'];

		return $result;

	}


	/**
	 * @todo: make sure it only picks-up original tempalte fields and not the ones
	 * we've inserted, e.g. description_lang
	 */
	public function getTemplate() {

		$result = null;
		$sections = null;
		$template = '{{' . $this->mediawiki_template_name . "\n" . '%s}}';

		foreach( $this->mediawiki_template_array as $parameter => $content ) {

			if ( is_array( $content ) ) {

				$sections .= '|' . $parameter . '=';

				foreach ( $content as $sub_template_name => $sub_template_content ) {

					if ( 'language' == $sub_template_name ) {

						foreach( $sub_template_content as $language => $language_content ) {

							$sections .= sprintf(
								$this->_sub_templates['language'],
								Filter::evaluate( $language ),
								Filter::evaluate( $language_content )
							) . "\n";

						}

					}

				}

			} else {

				$sections .= '|' . $parameter . '=' . Filter::evaluate( $content )  . "\n";

			}

		}

		return sprintf( $template, $sections );

	}


	public function populateFromArray( array &$metadata = array() ) {

		foreach( $this->mediawiki_template_array as $parameter => $value ) {

			$this->mediawiki_template_array[ $parameter ] = null;
			$parameter_as_id = $this->getParameterAsId( $parameter );

			if ( isset( $metadata[ $parameter_as_id ] ) ) {

				$this->mediawiki_template_array[ $parameter ] = $metadata[ $parameter_as_id ];

			}

		}

	}


	public function create() {}


	public function retrieve() {

		$result = null;

		$result = $this->dbr->select(
			Filter::evaluate( $this->table_name ),
			'mediawiki_template_name, mediawiki_template_json',
			"mediawiki_template_name = '" . Filter::evaluate( $this->mediawiki_template_name ) . "'"
		);

		if ( empty( $result ) || $result->numRows() != 1 ) {

			throw new Exception( wfMessage('gwtoolset-mediawiki-template-not-found')->rawParams( $params['mediawiki-template-name'] ) );

		}

		$this->mediawiki_template_json = $result->current()->mediawiki_template_json;
		$this->mediawiki_template_array = json_decode( $this->mediawiki_template_json, true );

		$this->mediawiki_template_array['title_identifier'] = null;
		$this->mediawiki_template_array['url_to_the_media_file'] = null;

		ksort( $this->mediawiki_template_array );

	}


	public function update() {}
	public function delete() {}


	/**
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $mediawiki_template_name
	 * the key within $user_options that holds the name of the mediawiki template
	 *
	 * @throws Exception
	 * @return string
	 */
	public function getValidMediaWikiTemplate( array &$user_options, $mediawiki_template_name = 'mediawiki-template-name' ) {

		$template = null;

			if ( !isset( $user_options[ $mediawiki_template_name ] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->param('no mediawiki-template-name provided') );

			}

			if ( in_array( $user_options[ $mediawiki_template_name ], Config::$allowed_templates ) ) {

				$this->mediawiki_template_name = $user_options[ $mediawiki_template_name ];
				$this->retrieve();

			} else {

				throw new Exception( wfMessage('gwtoolset-metadata-invalid-template') );

			}

		return $template;

	}


	public function __construct( $table_name = 'gwtoolset_mediawiki_templates' ) {

		parent::__construct( $table_name );

	}


}