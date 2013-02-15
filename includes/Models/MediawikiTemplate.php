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
use			Exception,
			GWToolset\Config,
			Php\Filter,
			ReflectionClass,
			ReflectionProperty,
			ResultWrapper;


class MediawikiTemplate extends Model {


	/**
	 * @var string
	 * a string representation of the mediawiki template name
	 */
	public $template_name;


	/**
	 * @var array
	 * an array that represents the mediawiki template parameters for this template
	 */
	public $template_parameters;


	public function getParameterAsId( $parameter ) {

		return str_replace( ' ', '-', $parameter );

	}


	protected function getKeys() {

		return $this->dbr->select(
			'gwtoolset_mediawiki_templates',
			'template_name AS key_name',
			null,
			null,
			array( 'ORDER BY' => 'template_name ASC' )
		);

	}


	/**
	 * creates a title based on
	 *   - title
	 *   - title identifier
	 *
	 * @todo: what if url is not to a file but a re-direct to the file
	 */
	public function getTitle() {

		$result = null;

			if ( empty( $this->template_parameters['title'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-title') );

			}

			if ( empty( $this->template_parameters['title_identifier'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-title-identifier') );

			}

			if ( empty( $this->template_parameters['url_to_the_media_file'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-media-file-url') );

			}

			$result = str_replace( Config::$metadata_separator, ' ', $this->template_parameters['title'] );
			$result .= '-' . $this->template_parameters['title_identifier'];

			$pathinfo = pathinfo( $this->template_parameters['url_to_the_media_file'] );
			
			if ( empty( $pathinfo['extension'] ) ) {

				throw new Exception( wfMessage('gwtoolset-mapping-no-media-file-url-extension') );

			}

			$result .= '.' . $pathinfo['extension'];

		return $result;

	}


	public function getTemplate() {

		$result = null;
		$sections = null;
		$template = '{{' . $this->template_name . "\n" . '%s}}';

		foreach( $this->template_parameters as $parameter => $value ) {

			if ( $parameter == 'description' ) {

				$sections .=
					'|' . $parameter . '=' .
					'{{' .
						$this->template_parameters['description_lang'] .
						'|1=' .
						Filter::evaluate( $value )  .
					"}}\n";

			} else {

				$sections .= '|' . $parameter . '=' . Filter::evaluate( $value )  . "\n";

			}

		}

		return sprintf( $template, $sections );

	}


	public function populateFromArray( array &$metadata = array() ) {

		foreach( $this->template_parameters as $parameter => $value ) {

			$this->template_parameters[ $parameter ] = null;
			$parameter_as_id = $this->getParameterAsId( $parameter );

			if ( isset( $metadata[ $parameter_as_id ] ) ) {

				$this->template_parameters[ $parameter ] = $metadata[ $parameter_as_id ];

			}

		}

	}


	public function create() {}


	public function retrieve() {

		$result = null;

		$result = $this->dbr->select(
			Filter::evaluate( $this->table_name ),
			'template_name, template_parameters',
			"template_name = '" . Filter::evaluate( $this->template_name ) . "'"
		);

		if ( empty( $result ) || $result->numRows() != 1 ) {

			throw new Exception( wfMessage('gwtoolset-mediawiki-template-not-found')->rawParams( $params['metadata-mapping'] ) );

		}

		$this->template_parameters = json_decode( $result->current()->template_parameters, true );
		$this->template_parameters['description_lang'] = null;
		$this->template_parameters['title_identifier'] = null;
		$this->template_parameters['url_to_the_media_file'] = null;
		ksort( $this->template_parameters );

	}


	public function update() {}
	public function delete() {}


	/**
	 * @param string $mediawiki_template
	 * @throws Exception
	 * @return string
	 */
	public function getValidMediaWikiTemplate( &$mediawiki_template = null ) {

		$template = null;

		if ( in_array( $mediawiki_template, Config::$allowed_templates ) ) {

			$this->template_name = $mediawiki_template;
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