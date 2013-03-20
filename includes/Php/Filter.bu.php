<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @datetime 2012-12-29 09:30 gmt +1
 * @author dan entous pennlinepublishing.com
 * @copyright © 2012 dan entous
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace	Php;
use			Exception;


class Filter {


	protected static $name;
	protected static $type;
	protected static $source;

	protected static $required;
	protected static $valid;

	protected static $filter_sanitize;
	protected static $filter_sanitize_options;

	protected static $filter_validate;
	protected static $filter_validate_options;

	protected static $value_raw;	
	protected static $value_result;


	protected static function validate() {

		if ( is_null( self::$filter_validate ) ) { return true; }

		$result = filter_var( self::$value_result, self::$filter_validate, self::$filter_validate_options );
		if ( $result === false ) { return false; }

		self::$value_result = $result;
		return true;

	}
	
	
	protected static function sanitize() {

		if ( is_null( self::$filter_sanitize ) ) { return true; }

		$result = filter_var( self::$value_result, self::$filter_sanitize, self::$filter_sanitize_options );
		if ( $result === false ) { return false; }

		self::$value_result = $result;
		return true;

	}
	
	
	protected static function required() {

		if ( !self::$required ) { return true; }
		if ( is_null( self::$value_result ) || strlen( self::$value_result ) < 1  ) { return false; }
		return true;

	}
	
	
	protected static function trim() {

		self::$value_result = trim( self::$value_raw );

	}
	
	
	protected static function processElement() {

		self::trim();
		if ( !self::required() ) { return; }
		if ( !self::sanitize() ) { return; }
		if ( !self::validate() ) { return; }

		self::$valid = true;

	}


	/**
	 * @param array|string $options
	 * @return void
	 */
	protected static function populate( &$options = null ) {

		if ( !is_array( $options ) ) {

			self::$value_raw = $options;
			return;

		}

		if ( isset( $options['name'] ) ) { self::$name = $options['name']; }
		if ( isset( $options['type'] ) ) { self::$type = $options['type']; }
		if ( isset( $options['required'] ) ) { self::$required = $options['required']; }
		if ( !isset( $options['source'] ) ) { throw new FilterException('Php Filter Error : no source set; most likely the options passed to the filter are a regular array rather than an options array'); }
		
		if ( isset( $options['filter-sanitize'] ) ) { self::$filter_sanitize = $options['filter-sanitize']; }

		self::$source = $options['source'];

		if ( is_array( self::$source ) && isset( self::$source[self::$name] ) ) {

			self::$value_raw = self::$source[self::$name];

		} else {

			self::$value_raw = self::$source;

		}

	}
	
	
	protected static function reset() {

		self::$name = null;
		self::$type = 'string';
		self::$source = null;

		self::$required = true;
		self::$valid = false;

		self::$filter_sanitize = FILTER_SANITIZE_FULL_SPECIAL_CHARS;
		self::$filter_sanitize_options = array();

		self::$filter_validate = null;
		self::$filter_validate_options = array();

		self::$value_raw = null;		
		self::$value_result = null;

	}


	/**
	 * @param array|string $options
	 * @return string|null
	 */
	public static function evaluate( $options = null ) {

		self::reset();
		self::populate( $options );
		self::processElement();

		if ( self::$valid ) {

			return self::$value_result;

		} else {

			return null;

		}

	}
	
	
}

