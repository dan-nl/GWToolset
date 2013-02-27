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
namespace	GWToolset;
use			ErrorException,
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
 * @param string $delimiter
 * @return string
 */
function getArrayAsList( array $array, $delimiter = ', ' ) {

	if ( empty( $array ) ) { return null; }
	return implode( $delimiter, $array );

}


/**
 * @param array $array
 * @return array
 */
function getArraySecondLevelValues( array $array = array() ) {

	if ( empty( $array ) ) { return; }

	$values = array();

	foreach( $array as $keys ) {
		foreach ( $keys as $key => $value ) {
			$values[] = $value;
		}
	}

	return $values;

}


function getMWApiClient( SpecialPage &$SpecialPage ) {

	$MWApiClient = new Client( Config::$api_internal_endpoint, $SpecialPage );
	$MWApiClient->login( Config::$api_internal_lgname, Config::$api_internal_lgpassword );
	$MWApiClient->debug_html .= '<b>API Client - Logged in</b><br/>' . '<pre>' . print_r( $MWApiClient->Login, true ) . '</pre>';
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

	if ( error_reporting() >= E_ALL ) {

		$errormsg =
			$errstr . '<br/>' .
			'<pre>' . print_r( debug_backtrace(), true ) . '</pre>';

		throw new ErrorException( $errormsg, 0, $errno, $errfile, $errline );

	}
	
	throw new Exception( wfMessage('gwtoolset-developer-issue')->params('ErrorException') );

}


if ( isset( $_SERVER['APPLICATION_ENV'] ) &&  $_SERVER['APPLICATION_ENV'] == 'development' ) {

	set_error_handler('\GWToolset\handleError');

}