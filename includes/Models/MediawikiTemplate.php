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
use	Exception,
	GWToolset\Adapters\DataAdapterInterface,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	Php\Curl,
	Php\Filter,
	ReflectionClass,
	ReflectionProperty,
	ResultWrapper;


class MediawikiTemplate extends Model {


	/**
	 * @var string
	 * a raw representation of the original metadata
	 */
	public $metadata_raw;


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


	/**
	 * @var GWToolset\Adapters\DataAdapterInterface
	 */
	protected $_DataAdapater;


	/**
	 * @var array
	 */
	protected $_sub_templates = array(
		'language' => '{{%s|%s}}',
		'institution' => '{{Institution:%s}}',
		'creator' => '{{Creator:%s}}'
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

		return $this->_DataAdapater->getKeys();

	}


	/**
	 * creates a title for a media file based on
	 *
	 *   - title
	 *   - title identifier
	 *   - url to the media file’s extension
	 *
	 * @todo: what if url is not to a file but a re-direct to the file
	 * @todo: eliminate any "safe-guarded characters", e.g. : seems to tell the api
	 * that the file does not exist so it uploads it aknew each time instead of editing it
	 * @todo investigate using Title::makeTitleSafe
	 */
	public function getTitle( array &$options ) {

		$result = null;

			if ( empty( $this->mediawiki_template_array['title_identifier'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-title-identifier') );

			}

			if ( empty( $options['evaluated_media_file_extension'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-media-file-url-extension-bad') );

			}

			$result = $this->mediawiki_template_array['title'];
			if ( !empty( $result ) ) { $result .= Config::$title_separator; }
			$result = FileChecks::getValidTitle( $result . $this->mediawiki_template_array['title_identifier'] );
			$result .= '.' . $options['evaluated_media_file_extension'];

		return $result;

	}


	/**
	 * @param array $user_options
	 *
	 * @todo: make sure it only picks-up original template fields and not the ones
	 * we've inserted, e.g. description_lang
	 */
	public function getTemplate( array $user_options ) {

		$result = '<!-- Mediawiki Template -->' . PHP_EOL;
		$sections = null;
		$template = '{{' . $this->mediawiki_template_name . PHP_EOL . '%s}}';

			foreach( $this->mediawiki_template_array as $parameter => $content ) {

				if ( empty( $content ) ) { continue; }
				$sections .= ' | ' . $parameter . ' = ';

				if ( is_array( $content ) ) {

					foreach ( $content as $sub_template_name => $sub_template_content ) {

						if ( 'language' == $sub_template_name ) {

							foreach( $sub_template_content as $language => $language_content ) {

								$sections .= sprintf(
									$this->_sub_templates['language'],
									Filter::evaluate( $language ),
									Filter::evaluate( $language_content )
								) . PHP_EOL;

							}

						}

					}

				} else {

					if ( 'institution' == $parameter ) {

						$sections .= sprintf(
							$this->_sub_templates['institution'],
							Filter::evaluate( $content )
						) . PHP_EOL;

					} else if ( 'artist' == $parameter ) {

						// assumes that there could be more than one creator and uses the
						// configured metadata separator to determine that
						$creators = explode( Config::$metadata_separator, $content );

						foreach( $creators as $creator ) {

							// assumes that a creator entry could be last name, first
							// no other assumptions are made other than this one
							$creator = explode( ',', $creator, 2 );

							if ( 2 == count( $creator ) ) {

								$creator = trim( $creator[1] ) . ' ' . $creator[0];

							} else {

								$creator = trim( $creator[0] );

							}

							$sections .= sprintf(
								$this->_sub_templates['creator'],
								Filter::evaluate( $creator )
							) . PHP_EOL;

						}

					} else if ( 'permission' == $parameter ) {

						// http://commons.wikimedia.org/wiki/Category:Creative_Commons_licenses
						$sections .= Filter::evaluate(
							str_replace(
								array_keys( Config::$mediawiki_licensing_templates ),
								array_values( Config::$mediawiki_licensing_templates ),
								$content
							)
						) . PHP_EOL;

					} else if ( 'source' == $parameter ) {

						if ( !empty( $user_options['partner-template-name'] ) ) {

							$sections .= Filter::evaluate( $content ) . '{{' . $user_options['partner-template-name'] . '}}' . PHP_EOL;

						} else {

							$sections .= Filter::evaluate( $content ) . PHP_EOL;

						}

					} else {

						$sections .= Filter::evaluate( $content ) . PHP_EOL;

					}

				}

			}

			$result .= sprintf( $template, $sections );

		return $result;

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


	public function create( array $options = array() ) {}


	public function retrieve( array $options = array() ) {

		$result = $this->_DataAdapater->retrieve( array( 'mediawiki_template_name' => $this->mediawiki_template_name ) );

		if ( empty( $result ) || $result->numRows() != 1 ) {

			throw new Exception( wfMessage('gwtoolset-mediawiki-template-not-found')->rawParams( $this->mediawiki_template_name ) );

		}

		$this->mediawiki_template_json = $result->current()->mediawiki_template_json;
		$this->mediawiki_template_array = json_decode( $this->mediawiki_template_json, true );

		$this->mediawiki_template_array['title_identifier'] = null;
		$this->mediawiki_template_array['url_to_the_media_file'] = null;

		ksort( $this->mediawiki_template_array );

	}


	public function update( array $options = array() ) {}
	public function delete( array $options = array() ) {}


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


	public function __construct( DataAdapterInterface $DataAdapter ) {

		$this->_DataAdapater = $DataAdapter;

	}


}