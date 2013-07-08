<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset;
use ErrorException,
	Exception,
	GWToolset\MediaWiki\Api\Client,
	SpecialPage,
	RecursiveArrayIterator,
	RecursiveIteratorIterator;

/**
 * @param array|object $var
 * @return string
 */
function debugArray( $var ) {
	return '<pre>' . print_r( $var, true ) . '</pre>';
}


/**
 * @param array $array
 * @return array
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

// array( 'debug-on' => ( ini_get('display_errors') && $this->_User->isAllowed( 'gwtoolset-debug' ) ) )
// don't use this method right now, it's needs some re-work so that it uses
// MWHttpRequest instead of Php\Curl
function getMWApiClient( array $curl_options = array() ) {
	echo'<pre>do not use this method until it has been re-factored to use MWHttpRequest instead of Php\Curl';
	print_r( debug_backtrace() );
	die();
	global $wgGWToolsetApiEndpoint, $wgGWToolsetApiUser, $wgGWToolsetApiUserPassword;
	$MWApiClient = new Client( $wgGWToolsetApiEndpoint, $wgGWToolsetApiUser, $curl_options );
	$MWApiClient->login( $wgGWToolsetApiUser, $wgGWToolsetApiUserPassword );
	$MWApiClient->debug_html .= '<b>API Client - Logged in</b><br />' . '<pre>' . print_r( $MWApiClient->Login, true ) . '</pre>';
	return $MWApiClient;
}

// @see http://www.shawnstratton.info/in_array-not-recursive/
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

function handleError( $errno, $errstr, $errfile, $errline, array $errcontext ) {
	// wfSuppressWarnings() lowers the error_reporting threshold because the
	// script that follows it is “allowed” to produce warnings,	thus, only
	// handle errors this way when error_reporting is set to >= E_ALL
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

set_error_handler('\GWToolset\handleError');
