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
	 * an array that represents the properties for the object
	 */
	public $properties;


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

		foreach( $this->properties as $property ) {

			if ( !empty( $this->$property ) ) {

				$sections .= '|' . ucfirst( $property ) . '=' . $this->$property;

			}

		}

		if ( !empty( $sections ) ) {

			$result = sprintf( $template, $sections );

		}

		return $result;

	}


	public function populateFromArray( array &$metadata = array() ) {

		foreach( $this->properties as $property ) {

			$this->$property = null;

			if ( isset( $metadata[ $property ] ) ) {

				$this->$property = $metadata[ $property ];

			}

		}

	}


	protected function createObjectProperties( ResultWrapper &$result ) {

		$this->properties = json_decode( $result->current()->properties );
		sort( $this->properties );

		foreach( $this->properties as $property ) {

			$this->$property = null;

		}

	}


	public function create() {}


	public function retrieve() {

		$result = null;

		$result = $this->dbr->select(
			Filter::evaluate( $this->table_name ),
			'template_name, properties',
			"template_name = '" . Filter::evaluate( $this->template_name ) . "'"
		);

		if ( empty( $result ) || $result->numRows() != 1 ) {

			throw new Exception( wfMessage('gwtoolset-metadata-mapping-not-found')->rawParams( $params['metadata-mapping'] ) );

		}

		$this->createObjectProperties( $result );

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

