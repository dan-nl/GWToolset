<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Helpers;
use Exception,
	Php\Filter,
	Title;

class WikiPages {

	/**
	 * parses a url to determine if the url is a valid wiki Title
	 *
	 * if $accepted_extensions are passed in the method will first check to see
	 * if the url contains one of the $accepted_extensions
	 *
	 * @param {string} $url url to be interpreted
	 * @param {array} $accepted_extensions
	 *
	 * @return {boolean|Title}
	 */
	public static function getTitleFromUrl( $url = null, $accepted_extensions = array() ) {
		global $wgServer;
		$result = false;

		if ( empty( $url ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-file-url' )->escaped() )->parse() );
		}

		if ( count( $accepted_extensions ) > 0 &&
			!FileChecks::isAcceptedFileExtension( $url, $accepted_extensions )
		) {
			return $result;
		}

		$result = str_replace(
			array( $wgServer, 'index.php', '//' ),
			'',
			$url
		);

		$result = Title::newFromText( $result );
		return $result;
	}

	/**
	 * replacing : and / so that metadata and mediafile titles do not accidentatlly
	 * contain namespaces or paths
	 *
	 * @param {string} $title
	 * @param {string} $replacement
	 * @return {string}
	 *
	 * @see https://commons.wikimedia.org/wiki/Commons:File_naming
	 * @see http://en.wikipedia.org/wiki/Wikipedia:Naming_conventions_(technical_restrictions)
	 * @see http://www.mediawiki.org/wiki/Help:Bad_title
	 */
	public static function titleCheck( $title, $replacement = '-' ) {
		//return str_replace( array( '#','<','>','[',']','|','{','}',':','¬','`','!','"','£','$','^','&','*','(',')','+','=','~','?','/',',',Config::$metadata_separator,';',"'",'@' ), $replacement, $title );
		return str_replace( array( ':', '/' ), $replacement, $title );
	}

}
