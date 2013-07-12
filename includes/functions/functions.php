<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset;
use ErrorException,
	Exception,
	GWToolset\MediaWiki\Api\Client,
	SpecialPage,
	RecursiveArrayIterator,
	RecursiveIteratorIterator;

/**
 * @param {array} $array
 *
 * @return {array}
 * the array keys and values are not filtered
 */
function getArraySecondLevelValues( array $array = array() ) {
	if ( empty( $array ) ) {
		return;
	}

	$values = array();

	foreach( $array as $keys ) {
		foreach ( $keys as $key => $value ) {
			$values[] = $value;
		}
	}

	return $values;
}

/**
 * @see http://www.shawnstratton.info/in_array-not-recursive/
 * @param {mixed} $needle
 * @param {array} $haystack
 * @param {bool} $strict
 */
function in_array_r( $needle, $haystack, $strict = false ) {
	$array = new RecursiveIteratorIterator( new RecursiveArrayIterator( $haystack ) );

	foreach( $array as $element ) {
		if ( $strict == true ) {
			if ( $element === $needle ) {
				return true;
			}
		} else {
			if ( $element == $needle ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * wfSuppressWarnings() lowers the error_reporting threshold because the
 * script that follows it is “allowed” to produce warnings,	thus, only
 * handle errors this way when error_reporting is set to >= E_ALL
 *
 * @param {int} $errno
 * @param {string} $errstr
 * @param {string} $errfile
 * @param {int} $errline
 * @param {array} $errcontext
 */
function handleError( $errno, $errstr, $errfile, $errline, array $errcontext ) {
	if ( ini_get('display_errors') && error_reporting() >= E_ALL ) {
		$errormsg =
			'<pre style="overflow:auto;">' .
				$errstr . "\n" .
				print_r( debug_backtrace(), true ) .
			'</pre>';

		if ( $errno > E_WARNING ) {
			error_log( $errstr . ' in ' . $errfile . ' on line nr ' . $errline );
			throw new ErrorException( $errormsg, 0, $errno, $errfile, $errline );
		} else {
			echo $errormsg;
		}
	} elseif ( error_reporting() >= E_ALL ) {
		error_log( $errstr . ' in ' . $errfile . ' on line nr ' . $errline );
	}
}

// created to deal with an issue within
// GWToolset/includes/Adapters/Api/MappingPhpAdapter.php->saveMapping()
function swallowErrors() {}
