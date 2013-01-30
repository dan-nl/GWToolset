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
namespace	GWToolset\Templates;
use			ReflectionClass,
			ReflectionProperty,
			SimpleXMLElement;


class Artwork {


	public $artist;
	public $title;
	public $object_type;
	public $description;
	public $date;
	public $medium;
	public $dimensions;
	public $institution;
	public $location;
	public $accession_number;
	public $object_history;
	public $exhibition_history;
	public $credit_line;
	public $inscriptions;
	public $notes;
	public $references;
	public $source;
	public $permission;
	public $other_versions;
	public $url_to_the_media_file;
	
	protected $_property_reflection;


	public function getPropertyReflection() {

		return $this->_property_reflection;

	}


	public function getTemplate() {

		$result = null;
		$sections = null;
		$template = '{{Artwork%s}}';

		foreach( $this->_property_reflection as $property ) {

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

		foreach( $this->_property_reflection as $property ) {

			if ( isset( $metadata[$property] ) && $metadata[$property] !== '' ) {

				$this->$property = $metadata[$property];

			}

		}

	}


	public function populate( SimpleXMLElement $metadata ) {

		foreach( $this->_property_reflection as $property ) {

			if ( isset( $metadata->$property ) && $metadata->$property !== '' ) {

				$this->$property = $metadata->$property;

			}

		}

	}


	private function propertyReflection() {

		$result = array();
		$reflect = new ReflectionClass( $this );
		$reflect_properties = $reflect->getProperties( ReflectionProperty::IS_PUBLIC );

		foreach( $reflect_properties as $property ) {

			$result[] = $property->name;

		}

		return $result;

	}


	public function reset() {

		$this->_property_reflection = $this->propertyReflection();

	}


	public function __construct() {

		$this->reset();

	}


}

