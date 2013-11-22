<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */

namespace GWToolset;
use MWException;

class Utils {

	/**
	 * @param {string} $string
	 * @return {string|null}
	 */
	static public function sanitizeString( $string ) {
		// is_string thought some form fields were booleans instead of strings
		if ( !gettype( $string ) === 'string' ) {
			throw new MWException(
				__METHOD__ . ': ' .
				wfMessage( 'gwtoolset-developer-issue' )
					->params( wfMessage( 'gwtoolset-not-string' )->params( gettype( $string ) ) )
					->escaped()
			);
		}

		$result = trim( $string );
		$result = filter_var( $string, FILTER_SANITIZE_STRING );

		if ( !$result ) {
			$result = null;
		}

		return $result;
	}

	/**
	 * FILTER_SANITIZE_URL removes a space rather than encode it as %20 or replace it with +
	 * @param {string} $string
	 * @return {string|null}
	 */
	static public function sanitizeUrl( $url ) {
		$result = urlencode( $url );
		$result = self::sanitizeString( $url );
		return $result;
	}

}
