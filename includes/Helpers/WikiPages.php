<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Helpers;
use Exception,
	GWToolset\Config,
	GWToolset\MediaWiki\Api\Client,
	Php\Filter,
	Title;

class WikiPages {

	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	public static $MWApiClient;

	/**
	 * @return {void}
	 */
	public static function checkforMWApiClient() {
		if ( !( self::$MWApiClient instanceof Client ) ) {
			self::$MWApiClient = \GWToolset\getMWApiClient();
		}
	}

	/**
	 * parses a url to get at the core filename
	 *
	 * @param {string} $file_url
	 * @param {string} $ns
	 */
	public static function getFilenameFromUrl( $file_url = null, $ns = 'File:' ) {
		$result = null;
		global $wgServer;

		if ( empty( $file_url ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-file-url' )->escaped() )->parse() );
		}

		FileChecks::isAcceptedFileExtension(
			$file_url,
			FileChecks::getAcceptedExtensions( Config::$accepted_metadata_types )
		);

		$result = str_replace(
			array( $wgServer, 'index.php', '/', $ns ),
			'',
			$file_url
		);

		return $result;
	}

	/**
	 * parses a url to get the template name
	 */
	public static function getTemplateNameFromUrl( $template_url = null ) {
		$result = null;
		global $wgServer;

		if ( empty( $template_url ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-template-url' )->escaped() )->parse() );
		}

		$result = str_replace(
			array( $wgServer, 'index.php', '//', 'Template:' ),
			'',
			$template_url
		);

		$result = str_replace( ' ', '_', $result );

		return $result;
	}

	/**
	 * parses a url to determine if the url is a valid wiki Title
	 * method that will possibly replace getFilenameFromUrl & getTemplateNameFromUrl
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
	 * assumes that $this->_MediawikiTemplate has been populated with metadata
	 * from a DOMElement and queries the wiki for a page title based on that
	 * information
	 *
	 * @param {string} $filename
	 *
	 * @return int
	 * a matching page id in the wiki or -1 if no match found
	 */
	public static function getTitlePageId( $filename ) {
		$page_id = -1;
		$api_result = array();

		self::checkforMWApiClient();
		$api_result = self::$MWApiClient->query( array( 'titles' => Filter::evaluate( $filename ), 'indexpageids' => '' ) );

		if ( empty( $api_result['query']['pageids'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-result-missing-pageids' )->escaped() )->parse() );
		}

		return (int)$api_result['query']['pageids'][0];
	}

	/**
	 * returns the local hard drive path to the file stored in the wiki
	 *
	 * @param {string} $file_url
	 *
	 * @return {string} a reference to the local file path
	 */
	public static function retrieveWikiFilePath( $file_url ) {
		global $wgServer, $IP;
		$result = null;
		$file_name = null;
		$api_result = array();

		$Title = Title::newFromText( $file_url );
		self::checkforMWApiClient();

		$api_result = self::$MWApiClient->query(
			array(
				'titles' => $Title,
				'prop' => 'imageinfo',
				'iiprop' => 'url'
			)
		);

		if ( empty( $api_result['query']['pages'] ) || isset( $api_result['query']['pages'][-1] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-file-url-invalid' )->escaped() );
		}

		foreach( $api_result['query']['pages'] as $page ) {
			if ( empty( $page['imageinfo'] )
				|| empty( $page['imageinfo'][0] )
				|| empty( $page['imageinfo'][0]['url'] )
			) {
				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-returned-no-imageinfo' )->escaped() )->parse() );
			}

			$result = $IP . str_replace( $wgServer, '', urldecode( $page['imageinfo'][0]['url'] ) );
			break; // should only need to run through this once
		}

		if ( !file_exists( $result ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-no-resolved-path' )->escaped() )->parse() );
		}

		return $result;
	}

	/**
	 * retrieves and returns the contents of a wikipage
	 *
	 * @param {Title} $Title
	 * @return {string} the wiki page contents
	 */
	public static function retrieveWikiPageContentsViaApi( Title $Title ) {
		$result = null;
		self::checkforMWApiClient();

		if ( empty( $Title ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-title' )->escaped() )->parse() );
		}

		$api_result = self::$MWApiClient->query(
			array(
				'titles' => $Title,
				'prop' => 'revisions',
				'rvprop' => 'content'
			)
		);

		if ( empty( $api_result['query']['pages'] ) || isset( $api_result['query']['pages'][-1] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-file-url-invalid' )->escaped() );
		}

		foreach( $api_result['query']['pages'] as $page ) {
			if ( empty( $page['revisions'] )
				|| empty( $page['revisions'][0]['*'] )
			) {
				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-returned-no-content' )->escaped() )->parse() );
			}

			$result = $page['revisions'][0]['*'];
			break; // should only need to run through this once, page id is unknown, thus the foreach
		}

		return $result;
	}

	/**
	 * @param {array} $options
	 * @return {boolean}
	 */
	public static function saveWikiPageContentsViaApi( array &$options ) {
		$result = false;
		$api_result = null;
		self::checkforMWApiClient();

		if ( empty( $options['summary'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-summary' )->escaped() )->parse() );
		}

		if ( empty( $options['text'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-text' )->escaped() )->parse() );
		}

		if ( empty( $options['title'] ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-title' )->escaped() )->parse() );
		}

		$api_result = self::$MWApiClient->edit(
			array(
				'summary' => $options['summary'],
				'title' => $options['title'],
				'text' => $options['text'],
				'token' => self::$MWApiClient->getEditToken()
			)
		);

		if ( empty( $api_result['edit'] )
			|| $api_result['edit']['result'] !== 'Success'
		) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-unexpected-api-result' )->escaped() )->parse() );
		}

		if ( $api_result['edit']['result'] == 'Success' ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * replacing : and / so that metadata titles doe not accidentatlly contain
	 * namespaces or paths
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
