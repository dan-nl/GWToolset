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
	Html,
	SpecialPage,
	Status,
	RecursiveArrayIterator,
	RecursiveIteratorIterator;


function checkStatus( Status $Status ) {
	if ( !$Status->ok ) {
		throw new Exception( $Status->getMessage() );
	}
	unset( $Status );
}

/**
 * @param {array} $array
 *
 * @return {array}
 * the array keys and values are not filtered
 */
function getArraySecondLevelValues( array $array ) {
	$values = array();

	foreach ( $array as $keys ) {
		foreach ( $keys as $key => $value ) {
			$values[] = $value;
		}
	}

	return $values;
}

/**
 * takes a php ini value that contains a letter for Kilobytes, Megabytes, etc.
 * and converts it to bytes
 *
 * @see http://www.php.net/manual/en/function.ini-get.php#96996
 *
 * @param {string} $val
 * @return {int}
 */
function getBytes( $val ) {
	switch ( substr ( $val, -1 ) ) {
		case 'M': case 'm': return (int)$val * 1048576;
		case 'K': case 'k': return (int)$val * 1024;
		case 'G': case 'g': return (int)$val * 1073741824;
		default:
	}

	return $val;
}

/**
 * wfSuppressWarnings() lowers the error_reporting threshold because the
 * script that follows it is “allowed” to produce warnings,    thus, only
 * handle errors this way when error_reporting is set to >= E_ALL
 *
 * @param {int} $errno
 * @param {string} $errstr
 * @param {string} $errfile
 * @param {int} $errline
 * @param {array} $errcontext
 */
function handleError( $errno, $errstr, $errfile, $errline, array $errcontext ) {
	if ( ini_get( 'display_errors' ) && error_reporting() >= E_ALL ) {
		$errormsg = Html::rawElement(
			'pre',
			array( 'style' => 'overflow:auto;' ),
			$errstr . "\n" . print_r( debug_backtrace(), true )
		);

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

/**
 * @see http://www.shawnstratton.info/in_array-not-recursive/
 * @param {mixed} $needle
 * @param {array} $haystack
 * @param {bool} $strict
 */
function in_array_r( $needle, $haystack, $strict = false ) {
	$array = new RecursiveIteratorIterator( new RecursiveArrayIterator( $haystack ) );

	foreach ( $array as $element ) {
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
 * @throws {Exception}
 */
function jsonCheckForError() {
	$error_msg = null;

	switch ( json_last_error() ) {
		case JSON_ERROR_NONE:
			break;

		case JSON_ERROR_DEPTH:
			$error_msg = wfMessage( 'gwtoolset-json-error-depth' )->escaped();
			break;

		case JSON_ERROR_STATE_MISMATCH:
			$error_msg = wfMessage( 'gwtoolset-json-error-state-mismatch' )->escaped();
			break;

		case JSON_ERROR_CTRL_CHAR:
			$error_msg = wfMessage( 'gwtoolset-json-error-ctrl-char' )->escaped();
			break;

		case JSON_ERROR_SYNTAX:
			$error_msg = wfMessage( 'gwtoolset-json-error-syntax' )->escaped();
			break;

		case JSON_ERROR_UTF8:
			$error_msg = wfMessage( 'gwtoolset-json-error-utf8' )->escaped();
			break;

		default:
			$error_msg = wfMessage( 'gwtoolset-json-error-unknown' )->escaped();
			break;
	}

	if ( !empty( $error_msg ) ) {
		throw new Exception( $error_msg );
	}
}

// created to deal with an issue within
// GWToolset/includes/Adapters/Api/MappingPhpAdapter.php->saveMapping()
function swallowErrors() {
}
