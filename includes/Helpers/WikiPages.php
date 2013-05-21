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
	Php\Filter;


class WikiPages {


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	public static $MWApiClient;


	public static function checkforMWApiClient() {

		if ( !( self::$MWApiClient instanceof Client ) ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-mwapiclient-creation-failed' )->plain() )->parse() );

		}

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

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-result-missing-pageids' )->plain() )->parse() );

		}

		return (int)$api_result['query']['pageids'][0];

	}


	/**
	 * parses a url to get the template name
	 */
	public static function getTemplateNameFromUrl( $template_url ) {

		$result = null;
		global $wgServer;

		if ( empty( $template_url ) ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-template-url' )->plain() )->parse() );

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
	 * parses a url to extract the user name and mapping template path & name
	 */
	public static function getUsernameAndPageFromUrl( $file_url ) {

		$result = null;
		global $wgServer;

		if ( empty( $file_url ) ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-file-url' )->plain() )->parse() );

		}

		$result = str_replace(
			array( $wgServer, 'index.php', '//', 'User:' ),
			'',
			$file_url
		);

		$result = explode( '/', $result, 2 );

		if ( count( $result ) != 2 ) {

			throw new Exception( wfMessage( 'gwtoolset-mapping-url-invalid' )->plain() );

		}

		$result['user-name'] = $result[0];
		$result['mapping-name'] = $result[1];

		return $result;

	}


	/**
	 * parses a url to get at the core filename
	 */
	public static function getFilenameFromUrl( $file_url ) {

		$result = null;
		global $wgServer;

		if ( empty( $file_url ) ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-file-url' )->plain() )->parse() );

		}

		FileChecks::isAcceptedFileExtension(
			$file_url,
			FileChecks::getAcceptedExtensions( Config::$accepted_types )
		);

		$result = str_replace(
			array( $wgServer, 'index.php', '/', 'File:' ),
			'',
			$file_url
		);

		return $result;

	}


	/**
	 * returns the local hard drive path to the file stored in the wiki
	 *
	 * @param {string} $file_url
	 * the url to the File: page
	 *
	 * @return {string} a reference to the local file path
	 */
	public static function retrieveWikiFilePath( $file_url ) {

		global $wgServer, $IP;
		$result = null;
		$file_name = null;
		$api_result = array();

		$file_name = self::getFilenameFromUrl( $file_url );
		$file_name = 'File:' . Filter::evaluate( $file_name );

		self::checkforMWApiClient();

		$api_result = self::$MWApiClient->query(
			array(
				'titles' => $file_name,
				'prop' => 'imageinfo',
				'iiprop' => 'url'
			)
		);

		if ( empty( $api_result['query']['pages'] ) || isset( $api_result['query']['pages'][-1] ) ) {

			throw new Exception( wfMessage( 'gwtoolset-file-url-invalid' )->plain() );

		}

		foreach( $api_result['query']['pages'] as $page ) {

			if ( empty( $page['imageinfo'] )
				|| empty( $page['imageinfo'][0] )
				|| empty( $page['imageinfo'][0]['url'] )
			) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-returned-no-imageinfo' )->plain() )->parse() );

			}

			$result = $IP . str_replace( $wgServer, '', $page['imageinfo'][0]['url'] );
			break; // should only need to run through this once

		}

		if ( !file_exists( $result ) ) {

			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-no-resolved-path' )->plain() )->parse() );

		}

		return $result;

	}


	/**
	 * retrieves and returns the contents of a wikipage
	 *
	 * @param {array} $options
	 * the url to the User: page
	 *
	 * $options[user-name]
	 * $options[mapping-name] = the path to the page
	 *
	 * @return {string} the wiki page contents
	 */
	public static function retrieveWikiPageContents( array &$options ) {

		self::checkforMWApiClient();

		$api_result = self::$MWApiClient->query(
			array(
				'titles' => 'User:' . Filter::evaluate( $options['user-name'] ) . '/' . Filter::evaluate( $options['mapping-name'] ),
				'prop' => 'revisions',
				'rvprop' => 'content'
			)
		);

		if ( empty( $api_result['query']['pages'] ) || isset( $api_result['query']['pages'][-1] ) ) {

			throw new Exception( wfMessage( 'gwtoolset-file-url-invalid' )->plain() );

		}

		foreach( $api_result['query']['pages'] as $page ) {

			if ( empty( $page['revisions'] )
				|| empty( $page['revisions'][0]['*'] )
			) {

				throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-api-returned-no-content' )->plain() )->parse() );

			}

			$result = $page['revisions'][0]['*'];
			break; // should only need to run through this once, page id is unknown, thus the foreach

		}

		return $result;

	}


}