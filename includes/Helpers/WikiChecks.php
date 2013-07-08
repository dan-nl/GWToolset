<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Helpers;
use ErrorPageError,
	Exception,
	GWToolset\Config,
	PermissionsError,
	SpecialPage,
	User,
	UserBlockedError;

/**
 * provides several methods for verifying :
 * - php version & settings
 * - the wiki environment
 * - user access to the wiki
 */
class WikiChecks {

	/**
	 * @var {int}
	 */
	public static $wgMaxImageArea;

	/**
	 * @var {string}
	 */
	public static $memory_limit;

	/**
	 * @var {int}
	 */
	public static $wgHTTPTimeout;

	/**
	 * attempts to make sure certain wiki settings are in place for handling
	 * large media uploads
	 */
	public static function adjustMediaUploadSettings() {
		self::increaseMaxImageArea();
		self::increaseMemoryLimit();
	}

	/**
	 * Checks if the given user (identified by an object) can execute this
	 * special page (as defined by $mRestriction) sent to the SpecialPage
	 * constructor
	 *
	 * @see SpecialPage::checkPermissions()
	 * @param SpecialPage $SpecialPage
	 * @throws Exception
	 * @return boolean
	 */
	public static function canUserViewPage( SpecialPage $SpecialPage ) {
		try {
			$SpecialPage->checkPermissions();
		} catch( PermissionsError $e ) {
			throw new Exception( $e->getMessage(), 1000 );
		}

		return true;
	}

	/**
	 * Make sure the user is a member of a group that can access this extension
	 *
	 * @param SpecialPage $SpecialPage
	 * @throws Exception
	 * @return boolean
	 */
	public static function checkUserWikiGroups( SpecialPage $SpecialPage ) {
		if ( !in_array( Config::$user_group, $SpecialPage->getUser()->getEffectiveGroups() ) ) {
			throw new Exception( wfMessage( 'exception-nologin-text' )->escaped(), 1000 );
		}

		return true;
	}

	/**
	 * Make sure the user has all required permissions. It appears that
	 * SpecialPage $restriction must be a string, thus it does not check a
	 * group of permissions.
	 *
	 * @param SpecialPage $SpecialPage
	 * @throws Exception
	 * @return boolean
	 */
	public static function checkUserWikiPermissions( SpecialPage $SpecialPage ) {
		foreach ( Config::$user_permissions as $permission ) {
			if ( !$SpecialPage->getUser()->isAllowed( $permission ) ) {
				throw new Exception( $permission, 1000 );
			}
		}

		return true;
	}

	/**
	 * For a submitted form, is the edit token present and valid
	 *
	 * @param SpecialPage $SpecialPage
	 * @throws PermissionsError
	 * @return boolean
	 */
	public static function doesEditTokenMatch( SpecialPage $SpecialPage ) {
		if ( !$SpecialPage->getUser()->matchEditToken( $SpecialPage->getRequest()->getVal( 'wpEditToken' ) ) ) {
			throw new Exception( wfMessage( 'exception-nologin-text' )->escaped(), 1000 );
		}

		return true;
	}

	/**
	 * UploadFromUrl & Api->upload timeout on large files that take a long time
	 * to upload without this setting
	 *
	 * wiki default is 25 seconds
	 * e.g., http://academia.lndb.lv/xmlui/bitstream/handle/1/231/k_001_ktl1-1-27.jpg
	 * @todo: what is this limit set to on production?
	 * @todo: does ui need a notice to user about this limitation?
	 */
	public static function increaseHTTPTimeout( $timeout = 0 ) {
		global $wgHTTPTimeout;

		if ( empty( $timeout ) ) {
			$timeout = Config::$http_timeout;
		}

		if ( $wgHTTPTimeout < $timeout ) {
			self::$wgHTTPTimeout = $wgHTTPTimeout;
			$wgHTTPTimeout = $timeout;
		}
	}

	/**
	 * the following settings need to be checked in order to handle large images
	 */
	public static function increaseMaxImageArea( $area = 0 ) {
		global $wgMaxImageArea;

		if ( empty( $area ) ) {
			$area = Config::$max_image_area;
		}

		if ( (int) $wgMaxImageArea < (int) $area ) {
			self::$wgMaxImageArea = (int) $wgMaxImageArea;
			$wgMaxImageArea = (int) $area;
		}
	}

	/**
	 * the following settings need to be checked in order to handle large images
	 */
	public static function increaseMemoryLimit( $limit = null ) {
		global $wgMemoryLimit;

		if ( empty( $limit ) ) {
			$limit = Config::$memory_limit;
		}

		if ( (int) ini_get( 'memory_limit' ) < (int) $limit ) {
			self::$memory_limit = ini_get('memory_limit');
			ini_set( 'memory_limit', $limit );
		}
	}

	/**
	 * @param SpecialPage $SpecialPage
	 * @throw UserBlockedError
	 * #return boolean
	 */
	public static function isUserBlocked( SpecialPage $SpecialPage ) {
		if ( $SpecialPage->getUser()->isBlocked() ) {
			throw new UserBlockedError( $SpecialPage->getUser()->getBlock() );
		}

		return true;
	}

	/**
	 * @see SpecialPage::checkReadOnly()
	 * @param SpecialPage $SpecialPage
	 * @return boolean
	 */
	public static function isWikiWriteable( SpecialPage $SpecialPage ) {
		$SpecialPage->checkReadOnly();
		return true;
	}

	/**
	 * Run through a series of checks to make sure the wiki environment is properly
	 * setup for this extension and that the user has permission to use it
	 *
	 * @param SpecialPage $SpecialPage
	 * @return boolean
	 */
	public static function pageIsReadyForThisUser( SpecialPage $SpecialPage ) {
		if ( !self::verifyPHPVersion() ) {
			return false;
		}

		if ( !self::verifyCurlExists() ) {
			return false;
		}

		if ( !self::verifyXMLReaderExists() ) {
			return false;
		}

		if ( !self::verifyFinfoExists() ) {
			return false;
		}

		if ( !self::verifyAPIEnabled() ) {
			return false;
		}

		if ( !self::verifyAPIWritable() ) {
			return false;
		}

		if ( !self::uploadsEnabled() ) {
			return false;
		}

		if ( !self::isWikiWriteable( $SpecialPage ) ) {
			return false;
		}

		if ( !self::canUserViewPage( $SpecialPage ) ) {
			return false;
		}

		if ( !self::checkUserWikiGroups( $SpecialPage ) ) {
			return false;
		}

		if ( !self::checkUserWikiPermissions( $SpecialPage ) ) {
			return false;
		}

		if ( !self::isUserBlocked( $SpecialPage ) ) {
			return false;
		}

		return true;
	}

	public static function restoreHTTPTimeout() {
		global $wgHTTPTimeout;

		if ( !empty( self::$wgHTTPTimeout )
			&& $wgHTTPTimeout != self::$wgHTTPTimeout
		) {
			$wgHTTPTimeout = self::$wgHTTPTimeout; // 20 minutes, 25 seconds default
		}
	}

	public static function restoreMaxImageArea() {
		global $wgMaxImageArea;

		if ( !empty( self::$wgMaxImageArea )
			&& $wgMaxImageArea != self::$wgMaxImageArea
		) {
			$wgMaxImageArea = self::$wgMaxImageArea; // 12500000 default
		}
	}

	public static function restoreMemoryLimit() {
		if ( !empty( self::$memory_limit )
			&& (int) ini_get( 'memory_limit' ) != (int) self::$memory_limit
		) {
			ini_set( 'memory_limit', self::$memory_limit ); // 128M default
		}
	}

	/**
	 * @throws ErrorPageError
	 * @return boolean
	 */
	public static function uploadsEnabled() {
		global $wgEnableUploads;

		if ( !$wgEnableUploads || ( !wfIsHipHop() && !wfIniGetBool( 'file_uploads' ) ) ) {
			throw new ErrorPageError( 'uploaddisabled', 'uploaddisabledtext' );
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return boolean
	 */
	public static function verifyAPIEnabled() {
		global $wgEnableAPI;

		if ( !$wgEnableAPI ) {
			$msg = '<span class="error">' . wfMessage( 'gwtoolset-verify-api-enabled' )->parse() . '</span>';
			throw new Exception( $msg );
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return boolean
	 */
	public static function verifyAPIWritable() {
		global $wgEnableWriteAPI;

		if ( !$wgEnableWriteAPI ) {
			$msg = '<span class="error">' . wfMessage( 'gwtoolset-verify-api-writeable' )->parse() . '</span>';
			throw new Exception( $msg );
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return boolean
	 */
	public static function verifyCurlExists() {
		if ( !function_exists('curl_init') ) {
			$msg = '<span class="error">' . wfMessage( 'gwtoolset-verify-curl' )->parse() . '</span>';
			throw new Exception( $msg );
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return boolean
	 */
	public static function verifyFinfoExists() {
		if ( !class_exists('finfo') ) {
			$msg = '<span class="error">' . wfMessage( 'gwtoolset-verify-finfo' )->parse() . '</span>';
			throw new Exception( $msg );
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return boolean
	 */
	public static function verifyPHPVersion() {
		if ( !defined( 'PHP_VERSION' )
			|| version_compare( PHP_VERSION, '5.3.3', '<' )
		) {
			$msg = '<span class="error">' . wfMessage( 'gwtoolset-verify-php-version' )->parse() . '</span>';
			throw new Exception( $msg );
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return boolean
	 */
	public static function verifyXMLReaderExists() {
		if ( !class_exists('XMLReader') ) {
			$msg = '<span class="error">' . wfMessage( 'gwtoolset-verify-xmlreader' )->parse() . '</span>';
			throw new Exception( $msg );
		}

		return true;
	}

}
