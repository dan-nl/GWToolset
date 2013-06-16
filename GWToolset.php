<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset;

/**
 * If a user tries to access this extension directly,alert the user that this is
 * not a valid entry point to the wiki.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	echo 'This file is part of a MediaWiki extension; it is not a valid entry point. To install this extension, follow the directions in the INSTALL file.';
	exit();
}

/**
 * set extension directory reference to this directory
 */
$wgGWToolsetDir = realpath( __DIR__ );

/*
 * load extension configuration
 */
require_once $wgGWToolsetDir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'Config.php';

/*
 * load extension functions
 */
require_once $wgGWToolsetDir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.php';

/**
 * registering extension metadata with MediaWiki
 */
$wgExtensionCredits['media'][] = array(
	'path' => __FILE__,
	'name' => Config::$name,
	'author' => Config::$author,
	'url' => Config::$url,
	'descriptionmsg' => Config::$descriptionmsg,
	'version' => Config::$version
);

/**
 * add user permissions
 */
$wgGroupPermissions["gwtoolset"] = $wgGroupPermissions["user"];
$wgGroupPermissions["gwtoolset"]["upload_by_url"] = true;
$wgGroupPermissions['sysop']['gwtoolset-debug'] = true;

/**
 * add autoloader classes
 */
foreach ( Config::$autoloader_classes as $class => $file ) {
	$wgAutoloadClasses[$class] = $wgGWToolsetDir . str_replace( '/', DIRECTORY_SEPARATOR, $file );
}

/**
 * add internationalization message file references
 */
foreach ( Config::$messages as $message => $file ) {
	$wgExtensionMessagesFiles[$message] = $wgGWToolsetDir . str_replace( '/', DIRECTORY_SEPARATOR, $file );
}

/**
 * setup special page references
 */
foreach ( Config::$special_pages as $page => $values ) {
	$wgSpecialPages[$page] = $values['class_name'];

	if ( !empty( $values['group'] ) ) {
		$wgSpecialPageGroups[$page] = $values['group'];
	}
}

/**
 * add hooks
 * not yet used
 */
foreach ( Config::$hooks as $hook => $method ) {
	$wgHooks[$hook][] = $method;
}

/**
 * add jobs
 */
foreach( Config::$jobs as $job => $method ) {
	$wgJobClasses[$job] = $method;
}

/**
 * register resources with ResourceLoader
 */
Config::$resources['localBasePath'] = $wgGWToolsetDir;
$wgResourceModules['ext.GWToolset'] = Config::$resources;

/**
 * check environment variables
 */
// needed to allow for creation of thumbnails for large images
if ( (int)ini_get('memory_limit') < 256) {
	ini_set('memory_limit', '256M'); // 128M default
}

// needed to allow for creation of thumbnails for large images
if ( $wgMaxImageArea < 64000000 ) {
	$wgMaxImageArea = 64000000; // 12500000 default
}

//$wgMaxShellMemory = 102400; // 102400 default

// UploadFromUrl & Api->upload timeout on large files that take a long time
// to upload without this setting
// e.g., http://academia.lndb.lv/xmlui/bitstream/handle/1/231/k_001_ktl1-1-27.jpg
// @todo: what is this limit set to on production?
// @todo: does ui need a notice to user about this limitation?
if ( $wgHTTPTimeout < 1200 ) {
	$wgHTTPTimeout = 1200; // 20 minutes, 25 seconds default
}
