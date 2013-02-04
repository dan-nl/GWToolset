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
	 * creates a filename based on the given url
	 *   - reverses the domain name
	 *     e.g. www.wikimedia.org becomes org.wikimedia.www.
	 *   - ignores any path information
	 *   - appends the file name
	 */
	public function getFilename( $url = null ) {

		$result = null;


		$parsed_url = parse_url( $url );
		$host = explode( '.', $parsed_url['host'] );

		for ( $i = count( $host ) - 1; $i >= 0; $i -= 1 ) {

			$result .= strtolower( $host[$i] ) . '.';

		}
		
		$path = explode( '/', $parsed_url['path'] );
		$result .= $path[ count( $path ) - 1 ];


		return $result;

	}


	public function getTemplate() {

		$result = null;
		$sections = null;
		$template = '{{' . $this->template_name . '%s}}';

		foreach( $this->template_parameters as $parameter => $value ) {

			$sections .= '|' . $parameter . '=' . Filter::evaluate( $value );

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

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-not-found')->rawParams( $params['metadata-mapping'] ) );

		}

		$this->template_parameters = json_decode( $result->current()->template_parameters, true );
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

		if ( array_key_exists( $mediawiki_template, Config::$allowed_templates ) ) {

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

