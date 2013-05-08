<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
/**
 * If a user tries to access this extension directly,
 * alert the user that this is not a valid entry point to MediaWiki.
 */
if ( !defined( 'MEDIAWIKI' ) ) {

	echo 'This file is part of a MediaWiki extension; it is not a valid entry point. To install this extension, follow the directions in the INSTALL file.';
	exit();

}


/**
 * initialize the aliases array
 */
$specialPageAliases = array();


/**
 * English
 * @author dan-nl
 */
$specialPageAliases['en'] = array(

	'MyExtension' => array( 'GWToolset', 'GWToolset' )

);