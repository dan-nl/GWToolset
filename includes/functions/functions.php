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
use			ErrorException;


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

function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {

    if ( 0 === error_reporting() ) {
        return false;	
    }

    throw new ErrorException( $errstr, 0, $errno, $errfile, $errline );

}

set_error_handler('\GWToolset\handleError');

