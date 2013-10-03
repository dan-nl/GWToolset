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
	GWToolset\MediaWiki\Api\Client,
	Html,
	Language,
	MWException,
	SpecialPage,
	Status,
	Title,
	RecursiveArrayIterator,
	RecursiveIteratorIterator;

/**
 * @param {Status} $Status
 * @throws {MWException}
 */
function checkStatus( Status $Status ) {
	if ( !$Status->ok ) {
		throw new MWException( $Status->getMessage() );
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
 * attempts to retrieve a wiki title based on a given page title, an
 * optional namespace requirement and whether or not the title must be known
 *
 * @param {string} $page_title
 * @param {int} $required_namespace
 * @param {boolean} $must_be_known
 *
 * @throws {MWException}
 * @return {null|Title}
 */
function getTitle( $page_title = null, $required_namespace = 0, $must_be_known = true ) {
	global $wgServer;
	$result = null;

	if ( empty( $page_title ) ) {
		throw new MWException(
			wfMessage( 'gwtoolset-developer-issue' )
				->params( wfMessage( 'gwtoolset-no-page-title' )->escaped() )
				->parse()
		);
	}

	if ( strpos( $page_title, $wgServer ) !== false ) {
		throw new MWException(
			wfMessage( 'gwtoolset-page-title-contains-url' )
				->params( $page_title )
				->parse()
		);
	}

	$Title = Title::newFromText( $page_title, $required_namespace );

	if ( !empty( $required_namespace )
			&& $required_namespace !== $Title->getNamespace()
	) {
		$Language = new Language();
		throw new MWException(
			wfMessage( 'gwtoolset-namespace-mismatch' )
				->params( $page_title, $Language->getNsText( $Title->getNamespace() ), $Language->getNsText( $required_namespace ) )
				->parse()
		);
	}

	if ( !$must_be_known ) {
		$result = $Title;
	} elseif ( $Title->isKnown() ) {
		$result = $Title;
	}

	return $result;
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
 * @throws {MWException}
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
		throw new MWException( $error_msg );
	}
}

// created to deal with an issue within
// GWToolset/includes/Adapters/Api/MappingPhpAdapter.php->saveMapping()
function swallowErrors() {
}
