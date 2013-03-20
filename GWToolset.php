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
$wgGWToolsetDir = realpath( dirname( __FILE__ ) );


/*
 * load extension configuration
 */
require_once $wgGWToolsetDir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'Config.php';


/*
 * load extension custom configuraton
 */
if ( file_exists( $wgGWToolsetDir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config-custom.php' ) ) {

	require_once $wgGWToolsetDir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config-custom.php';

}


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
//foreach ( Config::$hooks as $hook => $method ) {
//
//	$wgHooks[$hook][] = $method;
//
//}


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