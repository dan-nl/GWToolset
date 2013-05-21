<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace Php;
use Exception;


class Filter {


	/**
	 * @var string
	 * the key name of the value in the source array
	 */
	protected static $key_name;


	/**
	 * @var string
	 * [default=string] the expected value type
	 */
	protected static $type;


	/**
	 * @var array
	 * the source array
	 */
	protected static $source;


	/**
	 * @var boolean
	 * whether or not the value is required
	 */
	protected static $required;


	/**
	 *
	 */
	protected static $valid;

	protected static $filter_sanitize;
	protected static $filter_sanitize_options;

	protected static $filter_validate;
	protected static $filter_validate_options;

	protected static $value_raw;
	protected static $value_raw_is_array;

	protected static $value_result;
	protected static $value_result_array;


	protected static function validate() {

		if ( self::$filter_validate === null ) {
			return true;
		}

		if ( self::$value_raw_is_array ) {

				foreach( self::$value_result_array as $key => $value ) {

					$result = filter_var( $value, self::$filter_validate, self::$filter_validate_options );
					if ( $result === false ) { return false; }
					self::$value_result_array[$key] = $result;

				}

			} else {

				$result = filter_var( self::$value_result, self::$filter_validate, self::$filter_validate_options );
				if ( $result === false ) { return false; }
				self::$value_result = $result;

			}

		return true;

	}


	protected static function sanitize() {

		if ( self::$filter_sanitize === null ) {
			return true;
		}

			if ( self::$value_raw_is_array ) {

				foreach( self::$value_result_array as $key => $value ) {

					$result = filter_var( $value, self::$filter_sanitize, self::$filter_sanitize_options );
					if ( $result === false ) { return false; }
					self::$value_result_array[$key] = $result;

				}

			} else {

				$result = filter_var( self::$value_result, self::$filter_sanitize, self::$filter_sanitize_options );
				if ( $result === false ) { return false; }
				self::$value_result = $result;

			}

		return true;

	}


	protected static function required() {

		if ( !self::$required ) { return true; }

		if ( self::$value_raw_is_array ) {

			if ( empty( self::$value_raw ) ) {
				return false;
			}

			foreach( self::$value_result_array as $key => $value ) {

				if ( $value === null || strlen( $value ) < 1  ) {
					return false;
				}

			}

		} else {

			if ( self::$value_result === null || strlen( self::$value_result ) < 1  ) {
				return false;
			}

		}

		return true;

	}


	protected static function trim() {

		if ( self::$value_raw_is_array ) {

			foreach( self::$value_raw as $key => $value ) {

				self::$value_result_array[ $key ] = trim( $value );

			}

		} else {

			self::$value_result = trim( self::$value_raw );

		}

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

		if ( isset( $options['source'] ) ) {

			self::$source = $options['source'];

		} else {

			throw new FilterException( '$options provided as an array, but no $options[source] provided [' . print_r( $options, true ) . ']' );

		}

		if ( is_array( self::$source ) ) {

			if ( isset( $options['key-name'] ) ) {

				self::$key_name = $options['key-name'];

			} else {

				throw new FilterException( '$options provided as an array, but no $options[key-name] provided [' . print_r( $options, true ) . ']' );

			}

		}

		if ( isset( $options['type'] ) ) { self::$type = $options['type']; }
		if ( isset( $options['required'] ) ) { self::$required = $options['required']; }
		if ( isset( $options['filter-sanitize'] ) ) { self::$filter_sanitize = $options['filter-sanitize']; }

		if ( is_array( self::$source ) ) {

			if ( isset( self::$source[self::$key_name] ) ) {

				self::$value_raw = self::$source[self::$key_name];

				if ( is_array( self::$value_raw ) ) {

					self::$value_raw_is_array = true;

				}

			} else {

				self::$value_raw = null;

			}

		} else {

			self::$value_raw = self::$source;

		}

	}


	protected static function reset() {

		self::$key_name = null;
		self::$type = 'string';
		self::$source = null;

		self::$required = true;
		self::$valid = false;

		self::$filter_sanitize = FILTER_SANITIZE_FULL_SPECIAL_CHARS;
		self::$filter_sanitize_options = array();

		self::$filter_validate = null;
		self::$filter_validate_options = array();

		self::$value_raw = null;
		self::$value_raw_is_array = false;

		self::$value_result = null;
		self::$value_result_array = array();

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

			if ( self::$value_raw_is_array ) {

				return self::$value_result_array;

			} else {

				return self::$value_result;

			}

		} else {

			return null;

		}

	}


}