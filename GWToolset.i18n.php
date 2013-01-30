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
/**
 * If a user tries to access this extension directly,
 * alert the user that this is not a valid entry point to MediaWiki.
 */
if ( !defined( 'MEDIAWIKI' ) ) {

	echo 'This file is part of a MediaWiki extension; it is not a valid entry point. To install this extension, follow the directions in the INSTALL file.';
	exit();

}


/**
 * initialize the messages array
 */
$messages = array();


/**
 * English
 * @author dan-nl
 */
$messages['en'] = array(

	'gwtoolset' => 'GWToolset',
	'gwtoolset-desc' => 'GWToolset, a mass upload tool for GLAM’s',
	'gwtoolset-intro' => '<p>GWToolset is a MediaWiki extension that allows GLAMs the ability to mass upload content based on an xml file containing respective metadata about the content. The intent is to allow for a variety of xml schemas. Further information about the project can be found on its <a href="http://commons.wikimedia.org/wiki/Commons:GLAMToolset_project">project page</a>. Feel free to contacts us on that page as well. Select one of the menu items above to begin the upload process.</p>',


	# wiki checks
	'gwtoolset-wiki-checks-not-passed' => 'Wiki checks did not pass',
	'gwtoolset-verify-php-version' => 'The GWToolset Extension requires PHP >= 5.3.3',
	'gwtoolset-verify-curl' => 'The GWToolset Extension requires that PHP <a href="http://www.php.net/manual/en/curl.setup.php" rel="external">cURL functions</a> be installed.',
	'gwtoolset-verify-xmlreader' => 'The GWToolset Extension requires that PHP <a href="http://www.php.net/manual/en/xmlreader.setup.php" rel="external">XMLReader</a> be installed.',
	'gwtoolset-verify-finfo' => 'The GWToolset Extension requires that the PHP <a href="http://www.php.net/manual/en/fileinfo.setup.php" rel="external">finfo</a> extension be installed.',
	'gwtoolset-verify-api-enabled' => 'The GWToolset Extension requires that the wiki API is enabled.<br/>Please make sure <code>$wgEnableAPI</code> is set to true in the <code>DefaultSettings.php</code> file',
	'gwtoolset-verify-api-writeable' => 'The GWToolset Extension requires that the wiki API can perform write actions for authorized users.<br/>Please make sure <code>$wgEnableWriteAPI</code> is set to true in the <code>DefaultSettings.php</code> file',


	# general form
	'gwtoolset-metadata-user-options-error' => 'The following form field(s) must be filled in:',
	'gwtoolset-metadata-intermediate-form' => 'This is an intermediate form and cannot be displayed on its own. select a starting point from the menu above.',
	'gwtoolset-metadata-invalid-template' => 'No valid WikiMedia Template found',
	'gwtoolset-mediawiki-template' => '$1 Template',
	'gwtoolset-technical-error' => 'There was a Technical Error',
	'gwtoolset-no-module-name' => 'Unfortunately a developer will need to address this issue before you can continue ( no module name was specified ).',
	'gwtoolset-no-upload-handler' => 'Unfortunately a developer will need to address this issue before you can continue ( no upload handler was created ).',
	'gwtoolset-back-to-form' => 'back to form',
	'gwtoolset-no-form' => 'There is no form for this module',


	#js
	'gwtoolset-loading' => 'Please be patient, this may take awhile',


	#xmlreader
	'gwtoolset-xmlreader-open-error' => 'could not open the XML File for reading by XMLReader',
	'gwtoolset-xmlreader-close-error' => 'could not close the XMLReader',


	# prototype api
	'gwtoolset-upload-legend' => 'Upload your metadata file',
	'gwtoolset-accepted-file-types' => 'Accepted file types:',
	'gwtoolset-unaccepted-extension' => 'The uploaded file does not contain an accepted file extension.',
	'gwtoolset-unaccepted-extension-specific' => "The uploaded file has an unaccepted file extention [.$1].",
	'gwtoolset-improper-upload' => 'File was not uploaded properly.',
	'gwtoolset-file-not-valid' => 'File not valid',
	'gwtoolset-over-max-ini' => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
	'gwtoolset-over-max-file-size' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
	'gwtoolset-partial-upload' => 'The uploaded file was only partially uploaded.',
	'gwtoolset-no-file' => 'No file received.',
	'gwtoolset-missing-temp-folder' => 'Missing a temporary folder.',
	'gwtoolset-disk-write-failure' => 'Failed to write file to disk.',
	'gwtoolset-php-extension-error' => 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.',
	'gwtoolset-unaccepted-mime-type' => 'The uploaded file with the mime-type [$1] is not an accepted mime-type.',
	'gwtoolset-file-is-empty' => 'The uploaded file is empty.',
	'gwtoolset-mime-type-mismatch' => 'The uploaded file’s extension [$1] and mime-type [$2] do not match.',
	'gwtoolset-improper-data-format' => 'The uploaded file is not in the correct data format. See the $1 for accepted formats.',
	'gwtoolset-file-interpretation-error' => 'There was a problem interpretting the uploaded file',
	'gwtoolset-api-error' => 'An api error occurred',


	# metadata detect
	'gwtoolset-metadata-detect-step-1' => 'Step 1 : Metadata Detect',
	'gwtoolset-metadata-detect-step-1-instructions' => '<p>The Metadata Upload process consists of 3 steps:</p><ol><li>Metadata Detection</li><li>Metadata Mapping</li><li>Metadata Uploading</li></ol><p>In this step you upload your metadata file for evaluation. The application will attempt to extract the metadata fields available in the file, which you will map to the MediaWiki Template selected below in Step 2.</p>',
	'gwtoolset-record-element-name' => 'What is the xml element that contains each metadata record',
	'gwtoolset-which-mediawiki-template' => 'Which MediaWiki Template',
	'gwtoolset-which-metadata-mapping' => 'Which Metadata Mapping',
	'gwtoolset-metadata-detect-error' => 'Metadata Detect Error',
	//'gwtoolset-metadata-file-source' => 'Select the metadata file source; either a file that has been previously uploaded or a file you wish to upload',
	'gwtoolset-metadata-file-source' => 'Select the metadata file source',
	'gwtoolset-metadata-file-url' => 'Metadata file url',
	'gwtoolset-metadata-file' => 'Metadata file upload',
	'gwtoolset-metadata-mapping-not-found' => 'No metadata mapping found for [$1]',
	'gwtoolset-metadata-mapping-bad' => 'There’s a problem with the metadata mapping; unfortunately a developer will need to address this issue before you can continue ( [$1] )',
	
	# metadata mapping
	'gwtoolset-metadata-detect-step-2' => 'Step 2 : Metadata Mapping',
	'gwtoolset-metadata-detect-step-2-instructions' => '<p>Below is a list of the fields in the MediaWiki Template, $1, and drop-downs with the metadata fields found in your metadata file. In this step you need to map those fields with the MediaWiki Template fields.</p><ul><li>Left column contains a list of all fields in the MediaWiki Template.</li><li>Right column contains drop-downs with the metadata fields found in your metadata file.</li><li>For each MediaWiki Template field : <ol><li>select a metadata value from your uploaded metadata file that corresponds with the MediWiki Template field; you do not need to provide a match for every MediaWiki Template field.</li><li>At the bottom of the form you will need to select the metadata file again as we have not yet programmed functionality for storing your original metadata file.</li></ol></li></ul>',
	'gwtoolset-metadata-mapping-error' => 'Metadata Mapping Error',
	'gwtoolset-metadata-mapping-legend' => 'Map your metadata',


	# mw api client
	'mw-api-client-internal-endpoint-not-set' => 'The internal api endpoint has not been set; check the <code>config-custom.php</code> file.',
	'mw-api-client-endpoint-not-set' => 'The api endpoint was not set when instantiating <code>GWToolset\MediaWiki\Api\Client</code>.',
	'mw-api-client-curl-no-cookie-directory' => 'No cookie directory for cURL ( default directory is set to <code>/tmp/</code> )',
	'mw-api-client-curl-no-cookie-create' => 'Could not create a cookie for cURL.',
	'mw-api-client-curl-no-handle-create' => 'Could not create a cURL handle',
	'mw-api-client-no-edit-token' => 'Could not get an Edit Token for this user.',
	'mw-api-client-unknown-error' => 'An unknown error occurred.',
	'mw-api-client-invalid-module' => 'The module <b><code>[$1]</code></b> is not a valid API module.',
	'mw-api-client-internal-error' => 'Most likely a problem with creating a directory for the upload.<br/>Check file permissions on the public image directory.',
	'mw-api-client-permissiondenied' => '<small>Most likely the api user does not belong to a group that has upload or upload_by_url rights. Make sure the api user belongs to the <code>gwtoolset</code> group and that the <code>gwtoolset</code> group has the <code>upload</code> and <code>upload_by_url</code> right. This can usually be done in the <code>LocalSettings.php</code> file with the following statements and then assigning the api user to the gwtoolset group.<pre>$wgGroupPermissions["gwtoolset"] = $wgGroupPermissions["user"];<br/>$wgGroupPermissions["gwtoolset"]["upload_by_url"] = true;</pre></small>',
	'mw-api-client-api-response-is-not-serializable' => 'Result returned is not unserializable.',
	'mw-api-client-no-logout' => 'Logut Error : Could not properly log out.',
	'mw-api-client-NoName' => 'You didn’t set the api lgname parameter; check the <code>config-custom.php</code> file.',
	'mw-api-client-Illegal' => 'You provided an illegal username; check the <code>config-custom.php</code> file.',
	'mw-api-client-NotExists' => 'The username you provided doesn’t exist; check the <code>config-custom.php</code> file.',
	'mw-api-client-EmptyPass' => 'You didn’t set the api lgpassword parameter or you left it empty; check the <code>config-custom.php</code> file.',
	'mw-api-client-WrongPass' => 'The password you provided is incorrect; check the <code>config-custom.php</code> file',
	'mw-api-client-WrongPluginPass' => 'Same as WrongPass, returned when an authentication plugin rather than MediaWiki itself rejected the password.',
	'mw-api-client-CreateBlocked' => 'The wiki tried to automatically create a new account for you, but your IP address has been blocked from account creation.',
	'mw-api-client-Throttled' => 'You’ve logged in too many times in a short time. See also <a href="https://www.mediawiki.org/wiki/API:Login#Throttling" target="_blank">throttling</a>.',
	'mw-api-client-Blocked' => 'User is blocked.',
	'mw-api-client-mustbeposted' => 'The login module requires a <a href="https://en.wikipedia.org/wiki/en:POST_(HTTP)" target="_blank">POST</a> request.',
	'mw-api-client-NeedToken' => 'Either you did not provide the login token or the sessionid cookie. Request again with the <code>token</code> and cookie givien in this response.',
	'mw-api-client-already-logged-in' => 'The application has already logged in the api client. Are you trying to login more than once?',
	'mw-api-client-could-not-log-in' => 'Could not login with the api client; no response from the application.',
	'mw-api-client-no login-token-received' => 'Did not receive a login token from the application.',
	'mw-api-client-troubleshooting-tips' => 'Make sure: <ul><li>the api endpoint is set properly</li><li>the application is up and running</li><li>the api call is correctly formed</li><li>the wiki can properly use curl to connect with the api</li></ul>',

	# db client
	'gwtoolset-db-client-support' => 'The GWToolset extension currently supports only MySQL'
	
);


/**
 * Message documentation
 * @author dan-nl
 */
$messages['qqq'] = array(

	'gwtoolset-desc' => '{{desc}}'

);

