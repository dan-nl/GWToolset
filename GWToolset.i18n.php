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
	'gwtoolset-back-to-form' => 'back to form',
	'gwtoolset-no-form' => 'There is no form for this module',
	'gwtoolset-developer-issue' => 'Please contact a developer; they will need to address this issue before you can continue [ $1 ].',
	'gwtoolset-required-field' => '<span class="required">*</span> denotes required field',


	#js
	'gwtoolset-loading' => 'Please be patient, this may take awhile',
	'gwtoolset-save-mapping' => 'save mapping',
	'gwtoolset-save-mapping-failed' => 'We apologize, there was a problem processing your request; please try again later.',
	'gwtoolset-save-mapping-succeeded' => 'Your mapping has been successfully saved.',
	'gwtoolset-save-mapping-name' => 'How would you like to name this mapping?',


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
	'gwtoolset-unaccepted-mime-type' => 'The uploaded file is interpreted as having the mime-type [$1], which is not an accepted mime-type.',
	'gwtoolset-unaccepted-mime-type-for-xml' => 'The uploaded file is interpreted as having the mime-type [$1], which is not an accepted mime-type.<br/>Does the xml file have an xml declaration at the top of the file?<br/><code>&lt;?xml version="1.0" encoding="UTF-8"?></code>',
	'gwtoolset-file-is-empty' => 'The uploaded file is empty.',
	'gwtoolset-mime-type-mismatch' => 'The uploaded file’s extension [$1] and mime-type [$2] do not match.',
	'gwtoolset-improper-data-format' => 'The uploaded file is not in the correct data format. See the $1 for accepted formats.',
	'gwtoolset-file-interpretation-error' => 'There was a problem interpretting the metadata file',
	'gwtoolset-api-error' => 'An api error occurred',


	#metadata upload
	'gwtoolset-metadata-upload-successful' => '<h3>your upload was successful</h3>the upload can be found here : <a href="%s">%s</a>',


	# metadata detect
	'gwtoolset-metadata-detect-step-1' => 'Step 1 : Metadata Detect',
	'gwtoolset-metadata-detect-step-1-instructions' => '<p>The Metadata Upload process consists of 3 steps:</p><ol><li>Metadata Detection</li><li>Metadata Mapping</li><li>Metadata Uploading</li></ol><p>In this step you upload your metadata file for evaluation. The toolset will attempt to extract the metadata fields available in the metadata file, which you will then map to a MediaWiki Template in Step 2 : Metadata Mapping.</p>',
	'gwtoolset-record-element-name' => 'What is the xml element that contains each metadata record',
	'gwtoolset-which-mediawiki-template' => 'Which MediaWiki Template',
	'gwtoolset-which-metadata-mapping' => 'Which Metadata Mapping',
	'gwtoolset-metadata-detect-error' => 'Metadata Detect Error',
	'gwtoolset-metadata-file-source-info' => '... either a file that has been previously uploaded or a file you wish to upload from your computer',
	'gwtoolset-metadata-file-source' => 'Select the metadata file source',
	'gwtoolset-metadata-file-url' => 'Metadata file wiki url',
	'gwtoolset-metadata-file' => 'Metadata file upload',
	'gwtoolset-mediawiki-template-not-found' => 'No mediawiki template found for [$1]',
	'gwtoolset-metadata-mapping-not-found' => 'No metadata mapping found for [$1]',
	//'gwtoolset-metadata-file-url-not-present' => 'No metadata file url was given and no metadata file was uploaded so there’s no metadata to analyze. If you did include a metadata file url or uploaded a metadata file then please contact a developer; they will need to look further into this issue.',
	//'gwtoolset-metadata-file-url-invalid' => 'The metadata file url was invalid; the file does not yet exist in the wiki. You need to first upload the metadata file from your computer if you want to use the file url reference in the metadata mapping form.',
	'gwtoolset-file-url-invalid' => 'The file url was invalid; the file does not yet exist in the wiki. You need to first upload the file from your computer if you want to use the file url reference in the form.',
	'gwtoolset-no-example-dom-element' => 'No XML element found for mapping.<br/>Did you enter a value in the form for “What is the xml element that contains each metadata record”?<br/>Is the XML file well-formed? Try this <a href="http://www.w3schools.com/xml/xml_validator.asp" target="_blank">XML Validator</a>',
	'gwtoolset-mapping-url-invalid' => 'the metadata mapping url provided is invalid; please check it and submit again',
	'gwtoolset-metadata-mapping-bad' => 'There’s a problem with the metadata mapping; most likely the json format is invalid. please try and correct it and then submit again [$1].',
	'gwtoolset-metadata-mapping-wikitext-bad' => 'There’s a problem with the metadata mapping; most likely the json is not contained within a <mapping_json></mapping_json> element. please try and correct it and then submit again [$1].',
	'gwtoolset-title-too-long' => 'The title for this media file, [$1], is too long.',
	'gwtoolset-ensure-well-formed-xml' => 'Make sure the XML File is well-formed with this <a href="http://www.w3schools.com/xml/xml_validator.asp" target="_blank">XML Validator</a>',


	# metadata mapping
	'gwtoolset-metadata-detect-step-2' => 'Step 2 : Metadata Mapping',
	'gwtoolset-metadata-detect-step-2-instructions' => '<p>Below is a list of the fields in the MediaWiki Template, $1, and drop-downs with the metadata fields found in your metadata file. In this step you need to map those fields with the MediaWiki Template fields.</p><ul><li>Left column contains a list of all fields in the MediaWiki Template.</li><li>Right column contains drop-downs with the metadata fields found in your metadata file.</li><li><h4>For each Mediawiki Template field : </h4><ol><li>select a metadata value from your uploaded metadata file that corresponds with the Mediwiki Template field</li><li>you do not need to provide a match for every Mediawiki Template field.</li></ol></li></ul>',
	'gwtoolset-metadata-mapping-error' => 'Metadata Mapping Error',
	'gwtoolset-metadata-mapping-legend' => 'Map your metadata',
	'gwtoolset-mapping-no-title' => 'The metadata contains no title, which is needed in order to create the page',
	'gwtoolset-mapping-no-title-identifier' => 'The metadata contains no title identifier, which is used to create a unique page title; make sure you map a metadata field to the mediawiki template parameter title identifier',
	'gwtoolset-mapping-no-media-file-url' => 'No evaluated media file url was provided',
	'gwtoolset-mapping-media-file-url-extension-bad' => 'Could not evaluate the media file url in order to determine the file extension [$1]',
	'gwtoolset-mapping-media-file-url-bad' => 'Could not evaluate the media file url provided [$1]',
	'gwtoolset-mapping-media-file-media-file-no-content-type' => 'Could not evaluate the media file content type for the url provided [$1]',
	'gwtoolset-retrieve-media' => 'retrieve media from url',
	'gwtoolset-retrieve-media-explanation' => 'the media for each item will be retrieved from the url_to_the_media_file provided and uploaded to the wiki. if a page for the item does not yet exist, the media file will be uploaded even if this checkbox has not been checked.',
	'gwtoolset-add-as-a-job' => 'process as a background job',
	'gwtoolset-add-as-a-job-description' => 'the metadata is processsed in the background; this is the recommended method. if you uncheck this option your request may not complete properly.',
	'gwtoolset-categories' => 'Enter categories separated by a comma ,',


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
	'gwtoolset-db-client-support' => 'The GWToolset extension currently supports only MySQL',
	
	# jobs
	'gwtoolset-batchjob-metadata-created' => 'batch job added. your metadata file will be analyzed shortly and each item will be uploaded to the wiki in a background process.',
	'gwtoolset-batchjobs-item-created' => 'Batch jobs for ($1) item(s) have been created; these will process one at a time via a background job.',
	'gwtoolset-batchjobs-item-created-some' => 'Unfortunately not all items were added as batch jobs. Batch jobs were created for ($1) item(s); with ($2) items having an issue. Please contact a developer if this is a problem.'
	
);


/**
 * Message documentation
 * @author dan-nl
 */
$messages['qqq'] = array(

	'gwtoolset-desc' => '{{desc}}'

);

