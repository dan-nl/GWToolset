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
	'gwtoolset-intro' => 'GWToolset is a MediaWiki extension that allows GLAMs the ability to mass upload content based on an xml file containing respective metadata about the content. The intent is to allow for a variety of xml schemas. Further information about the project can be found on its [https://commons.wikimedia.org/wiki/Commons:GLAMToolset_project project page]. Feel free to contacts us on that page as well. Select one of the menu items above to begin the upload process.',

	/**
	 * wiki checks
	 */
	'gwtoolset-wiki-checks-not-passed' => 'Wiki checks did not pass',
	'gwtoolset-verify-php-version' => 'The GWToolset Extension requires PHP >= 5.3.3',
	'gwtoolset-verify-curl' => 'The GWToolset Extension requires that PHP [http://www.php.net/manual/en/curl.setup.php cURL functions] be installed.',
	'gwtoolset-verify-xmlreader' => 'The GWToolset Extension requires that PHP [http://www.php.net/manual/en/xmlreader.setup.php XMLReader] be installed.',
	'gwtoolset-verify-finfo' => 'The GWToolset Extension requires that the PHP [http://www.php.net/manual/en/fileinfo.setup.php finfo] extension be installed.',
	'gwtoolset-verify-api-enabled' => 'The GWToolset Extension requires that the wiki API is enabled.<br />Please make sure <tt>$wgEnableAPI</tt> is set to <tt>true</tt> in the <tt>DefaultSettings.php</tt> file or is overridden to <tt>true</tt> in the <tt>LocalSettings.php</tt> file.',
	'gwtoolset-verify-api-writeable' => 'The GWToolset Extension requires that the wiki API can perform write actions for authorized users.<br />Please make sure <tt>$wgEnableWriteAPI</tt> is set to <tt>true</tt> in the <tt>DefaultSettings.php</tt> file or is overridden to <tt>true</tt> in the <tt>LocalSettings.php</tt> file.',

	/**
	 * developer issues
	 */
	'gwtoolset-developer-issue' => "Please contact a developer; they will need to address this issue before you can continue [ '''$1''' ].",
	'gwtoolset-no-mapping-json' => 'no mapping_json provided',
	'gwtoolset-no-username' => 'no user_name provided',
	'gwtoolset-no-mapping' => 'no mapping_name provided',
	'gwtoolset-unexpected-api-format' => 'api result was not in the expected format',
	'gwtoolset-no-title' => 'no title provided',
	'gwtoolset-ignorewarnings' => 'ignorewarnings not set',
	'gwtoolset-no-text' => 'text not provided',
	'gwtoolset-no-url-to-media' => 'url_to_the_media_file not set',
	'gwtoolset-no-pageid' => 'pageid not set',
	'gwtoolset-no-comment' => "user_options['comment'] not set",
	'gwtoolset-no-save-as-batch' => "user_options['save-as-batch-job'] not set",
	'gwtoolset-no-upload-media' => "user_options['upload-media'] not set",
	'gwtoolset-no-module' => 'no module name was specified',
	'gwtoolset-no-mediawiki-template' => 'no mediawiki-template-name provided',
	'gwtoolset-batchjob-creation-failure' => 'could not create batchjob for the metadata file',
	'gwtoolset-no-xmlreader' => 'no XMLReader provided',
	'gwtoolset-dom-record-issue' => 'record-element-name, or record-count not provided',
	'gwtoolset-no-local-path' => 'local file path has not been specified',
	'gwtoolset-no-callback' => 'no callback passed to this method',
	'gwtoolset-could-not-open-xml' => 'could not open the XML File for reading',
	'gwtoolset-could-not-close-xml' => 'could not close the XMLReader',
	'gwtoolset-mwapiclient-creation-failed' => 'could not create a MWApiClient',
	'gwtoolset-api-result-missing-pageids' => 'api-result does not contain expected keys [query] and/or [query][pageids]',
	'gwtoolset-no-template-url' => 'no template url provided to parse',
	'gwtoolset-no-file-url' => 'no file_url provided to parse',
	'gwtoolset-api-returned-no-imageinfo' => 'api returned no imageinfo url',
	'gwtoolset-api-no-resolved-path' => 'api resolved file path does not exist',
	'gwtoolset-api-returned-no-content' => 'api returned no content for the mapping page',
	'gwtoolset-mapping-info-missing' => 'mapping : user-name and/or mapping-name not set',
	'gwtoolset-cannot-retrieve-mapping' => 'cannot retrieve mapping, no mediawiki-tepmplate-name provided',
	'gwtoolset-no-upload-handler' => 'no upload handler was created',

	/**
	 * general form
	 */
	'gwtoolset-metadata-user-options-error' => 'The following form field(s) must be filled in:',
	'gwtoolset-metadata-intermediate-form' => 'This is an intermediate form and cannot be displayed on its own. select a starting point from the menu above.',
	'gwtoolset-metadata-invalid-template' => 'No valid WikiMedia Template found',
	'gwtoolset-mediawiki-template' => '$1 Template',
	'gwtoolset-technical-error' => 'There was a Technical Error',
	'gwtoolset-back-to-form' => 'back to form',
	'gwtoolset-no-form' => 'There is no form for this module',
	'gwtoolset-required-field' => ' denotes required field',
	'gwtoolset-revised' => '( revised )',
	'gwtoolset-no-change' => '( no change )',

	/**
	 * js
	 */
	'gwtoolset-loading' => 'Please be patient, this may take awhile',
	'gwtoolset-save-mapping' => 'save mapping',
	'gwtoolset-save-mapping-failed' => 'We apologize, there was a problem processing your request; please try again later.',
	'gwtoolset-save-mapping-succeeded' => 'Your mapping has been successfully saved.',
	'gwtoolset-save-mapping-name' => 'How would you like to name this mapping?',

	/**
	 * xmlreader
	 */
	'gwtoolset-xmlreader-open-error' => 'could not open the XML File for reading by XMLReader',
	'gwtoolset-xmlreader-close-error' => 'could not close the XMLReader',

	/**
	 * prototype api
	 */
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
	'gwtoolset-unaccepted-mime-type-for-xml' => 'The uploaded file is interpreted as having the mime-type [$1], which is not an accepted mime-type.<br />Does the xml file have an xml declaration at the top of the file?<br /><tt>&lt;?xml version="1.0" encoding="UTF-8"?></tt>',
	'gwtoolset-file-is-empty' => 'The uploaded file is empty.',
	'gwtoolset-mime-type-mismatch' => 'The uploaded file’s extension [$1] and mime-type [$2] do not match.',
	'gwtoolset-improper-data-format' => 'The uploaded file is not in the correct data format. See the $1 for accepted formats.',
	'gwtoolset-file-interpretation-error' => 'There was a problem interpretting the metadata file',
	'gwtoolset-api-error' => 'An api error occurred',

	/**
	 * metadata upload
	 */
	'gwtoolset-metadata-upload-successful' => '==== Your metadata file upload was successful ====' . PHP_EOL . 'The uploaded file can be found here : [$1 $2]',
	'gwtoolset-upload-on-behalf-of' => 'Uploaded using the GWToolset Extension.',

	/**
	 * metadata detect - step 1
	 */
	'gwtoolset-step-1' => 'Step 1 : Metadata Detect',
	'gwtoolset-step-1-instructions' => '== Step 1 : Metadata Detect ==' . PHP_EOL . 'The Metadata Upload process consists of 4 steps:' . PHP_EOL . '# Metadata Detection' . PHP_EOL . '# Metadata Mapping' . PHP_EOL . '# Batch Preview' . PHP_EOL . '# Batch Upload' . PHP_EOL . 'In this step, you either upload a new metadata file, or select a metadata file that has already been uploaded to the wiki for evaluation. The toolset will attempt to extract the metadata fields available in the metadata file, which you will then map to a MediaWiki Template in Step 2 : Metadata Mapping.',
	'gwtoolset-record-element-name' => 'What is the xml element that contains each metadata record : ',
	'gwtoolset-which-mediawiki-template' => 'Which MediaWiki Template : ',
	'gwtoolset-which-metadata-mapping' => 'Which Metadata Mapping : ',
	'gwtoolset-metadata-detect-error' => 'Metadata Detect Error',
	'gwtoolset-metadata-file-source-info' => '... either a file that has been previously uploaded or a file you wish to upload from your computer',
	'gwtoolset-metadata-file-source' => 'Select the metadata file source',
	'gwtoolset-metadata-file-url' => 'Metadata file wiki url : ',
	'gwtoolset-metadata-file' => 'Metadata file upload : ',
	'gwtoolset-mediawiki-template-not-found' => 'No mediawiki template found for [$1]',
	'gwtoolset-metadata-mapping-not-found' => 'No metadata mapping found for [$1]',
	'gwtoolset-file-url-invalid' => 'The file url was invalid; the file does not yet exist in the wiki. You need to first upload the file from your computer if you want to use the file url reference in the form.',
	'gwtoolset-no-xml-element' => 'No XML element found for mapping.',
	'gwtoolset-no-example-dom-element' => '* Did you enter a value in the form for “What is the xml element that contains each metadata record”?' . PHP_EOL . '* Is the XML file well-formed? Try this [http://www.w3schools.com/xml/xml_validator.asp XML Validator]',
	'gwtoolset-mapping-url-invalid' => 'the metadata mapping url provided is invalid; please check it and submit again',
	'gwtoolset-metadata-mapping-bad' => 'There’s a problem with the metadata mapping; most likely the json format is invalid. please try and correct it and then submit again [$1].',
	'gwtoolset-metadata-mapping-wikitext-bad' => 'There’s a problem with the metadata mapping; most likely the json is not contained within a <tt>&lt;mapping_json>&lt;/mapping_json></tt> element. please try and correct it and then submit again [ $1 ].',
	'gwtoolset-title-too-long' => 'The title for this media file, [$1], is too long.',
	'gwtoolset-ensure-well-formed-xml' => 'Make sure the XML File is well-formed with this [http://www.w3schools.com/xml/xml_validator.asp XML Validator]',

	/**
	 * metadata mapping - step 2
	 */
	'gwtoolset-step-2' => 'Step 2 : Metadata Mapping',
	'gwtoolset-step-2-instructions' => '== Step 2 : Metadata Mapping ==' . PHP_EOL . 'Below is a list of the fields in the MediaWiki $1, and drop-downs with the metadata fields found in your metadata file. In this step you need to map those fields with the MediaWiki Template fields.' . PHP_EOL . '* Left column contains a list of all fields in the MediaWiki Template.' . PHP_EOL . '* Right column contains drop-downs with the metadata fields found in your metadata file.' . PHP_EOL . '====For each Mediawiki Template field :====' . PHP_EOL . '# select a metadata value from your uploaded metadata file that corresponds with the Mediwiki Template field' . PHP_EOL . '# you do not need to provide a match for every Mediawiki Template field.',
	'gwtoolset-metadata-mapping-error' => 'Metadata Mapping Error',
	'gwtoolset-metadata-mapping-legend' => 'Map your metadata',
	'gwtoolset-template-field' => 'template field',
	'gwtoolset-maps-to' => 'maps to',
	'gwtoolset-results' => 'Results',
	'gwtoolset-example-record' => 'metadata’s example record’s contents',
	'gwtoolset-painted-by' => 'Painted by',
	'gwtoolset-mapping-no-title' => 'The metadata contains no title, which is needed in order to create the page',
	'gwtoolset-mapping-no-title-identifier' => 'The metadata contains no title identifier, which is used to create a unique page title; make sure you map a metadata field to the mediawiki template parameter title identifier',
	'gwtoolset-mapping-no-media-file-url' => 'No evaluated media file url was provided',
	'gwtoolset-mapping-media-file-url-extension-bad' => 'Could not evaluate the media file url in order to determine the file extension [$1]',
	'gwtoolset-mapping-media-file-url-bad' => 'Could not evaluate the media file url provided [$1]. The url delivers the content in a way that is not yet handled by this extension.',
	'gwtoolset-mapping-media-file-media-file-no-content-type' => 'Could not evaluate the media file content type for the url provided [$1]',
	'gwtoolset-reupload-media' => 're-upload media from url',
	'gwtoolset-reupload-media-explanation' => 'this check box allows you to re-upload media for an item that has already been uploaded to the wiki. if the item already exists, an additional media file will be added to the wiki. if the media file does not yet exist, it will be uploaded whether this checkbox is checked or not.',
	'gwtoolset-add-as-a-job' => 'process as a background job',
	'gwtoolset-add-as-a-job-description' => 'the metadata is processsed in the background; this is the recommended method. if you uncheck this option your request may not complete properly.',
	'gwtoolset-category' => 'category',
	'gwtoolset-categories' => 'Enter categories separated by a pipe |',
	'gwtoolset-categories-tooltip' => 'These categories will be applied to every item in the batch upload.',
	'gwtoolset-global-categories' => 'Global Categories',
	'gwtoolset-global-tooltip' => 'These category entries will be applied globally to all uploaded items',
	'gwtoolset-specific-categories' => 'Item Specific Categories',
	'gwtoolset-specific-tooltip' => 'Using the following fields you can apply a phrase (optional) plus a metadata field as the category entry for each individual uploaded item. For example, if the metadata file contains an element for the artist of each record, you could add that as a category entry for each record that would change to the value specific to each record. You could also add a phrase such as “<i>Painted by</i>” and then the artist metadata field, which would yield “<i>Painted by &lt;artist name></i>” as the category for each record.',
	'gwtoolset-phrasing' => 'phrasing',
	'gwtoolset-metadata-field' => 'metadata field',
	'gwtoolset-partner' => 'Partner',
	'gwtoolset-partner-template' => 'partner template : ',
	'gwtoolset-partner-explanation' => 'Partner templates are pulled into the source field of the mediawiki template when provided. You can find a list of current Partner Templates on the Category:Source templates page; see link below. Once you’ve found the Partner Template you wish to use place the url to it in this field. You can also create a new Partner Template if necessary.',
	'gwtoolset-record-count' => 'total number of records found in this metadata file [$1]',
	'gwtoolset-preview' => 'preview batch',
	'gwtoolset-process-batch' => 'process the batch',
	'gwtoolset-no-more-records' => "'''no more records to process'''",

	/**
	 * batch preview - step 3
	 */
	'gwtoolset-step-3' => 'Step 3 : Batch Preview',
	'gwtoolset-step-3-instructions' => '== Step 3 : Batch Preview ==' . PHP_EOL . 'Below are the results of uploading the first $1 records from the metadata file you selected and mapping them to the mediawiki template you selected in Step 2 : Metadata Mapping.',
	'gwtoolset-step-3-instructions-2' => 'Review these pages and if the results meet your expectations, and there are additional records waiting to be uploaded, continue the batch upload process by clicking on the “process the batch” button below.',
	'gwtoolset-step-3-instructions-3' => 'If you’re not happy with the results, go back to Step 2 : Metadata Mapping and adjust the mapping as necessary.' . PHP_EOL . 'If you need to make adjustments to the metadata file itself, go ahead and do so and re-upload it by beginning the process again with Step 1 : Metadata Detect',

	/**
	 * mw api client
	 */
	'mw-api-client-curl-no-cookie-directory' => 'No cookie directory for cURL ( default directory is set to <tt>/tmp/</tt> )',
	'mw-api-client-curl-no-cookie-create' => 'Could not create a cookie for cURL.',
	'mw-api-client-curl-no-handle-create' => 'Could not create a cURL handle',
	'mw-api-client-no-edit-token' => 'Could not get an Edit Token for this user.',
	'mw-api-client-unknown-error' => 'An unknown error occurred.',
	'mw-api-client-invalid-module' => "The module '''<tt>[$1]</tt>''' is not a valid API module.",
	'mw-api-client-internal-error' => 'Most likely a problem with creating a directory for the upload. Check file permissions on the public image directory.',
	'mw-api-client-permissiondenied' => 'Most likely the api user utilized by GWToolset does not belong to a group that has <tt>upload</tt> or <tt>upload_by_url</tt> rights. Make sure the api user utilized by GWToolset, specified in <tt>LocalSettings.php</tt> as <tt>$wgGWToolsetApiUser</tt>, belongs to a group that has the <tt>upload</tt> and <tt>upload_by_url</tt> rights and that the api user belongs to the <tt>gwtoolset</tt> group.',
	'mw-api-client-no-logout' => 'Logut Error : Could not properly log out.',
	'mw-api-client-NoName' => 'You didn’t set the api lgname parameter. Is the <tt>$wgGWToolsetApiUser</tt> setting correct in the <tt>LocalSettings.php</tt> file and before the include of the extension?',
	'mw-api-client-Illegal' => 'You provided an illegal username; check the <tt>config-custom.php</tt> file.',
	'mw-api-client-NotExists' => 'The username you provided does not exist. Is the <tt>$wgGWToolsetApiUser</tt> setting correct in the <tt>LocalSettings.php</tt> file and before the include of the extension?',
	'mw-api-client-EmptyPass' => 'You didn’t set the api lgpassword parameter or you left it empty. Is the <tt>$wgGWToolsetApiUserPassword</tt> setting correct in the <tt>LocalSettings.php</tt> file and before the include of the extension?',
	'mw-api-client-WrongPass' => 'The password you provided is incorrect. Is the <tt>$wgGWToolsetApiUserPassword</tt> setting correct in the <tt>LocalSettings.php</tt> file and before the include of the extension?',
	'mw-api-client-WrongPluginPass' => 'Same as WrongPass, returned when an authentication plugin rather than MediaWiki itself rejected the password.',
	'mw-api-client-CreateBlocked' => 'The wiki tried to automatically create a new account for you, but your IP address has been blocked from account creation.',
	'mw-api-client-Throttled' => 'You’ve logged in too many times in a short time. See  [https://www.mediawiki.org/wiki/API:Login#Throttling throttling] for more details.',
	'mw-api-client-Blocked' => 'User is blocked.',
	'mw-api-client-mustbeposted' => 'The login module requires a [https://en.wikipedia.org/wiki/en:POST_(HTTP) POST] request.',
	'mw-api-client-NeedToken' => 'Either you did not provide the login token or the sessionid cookie. Request again with the <tt>token</tt> and cookie givien in this response.',
	'mw-api-client-already-logged-in' => 'The application has already logged in the api client. Are you trying to login more than once?',
	'mw-api-client-could-not-log-in' => 'Could not login with the api client; no response from the application.',
	'mw-api-client-no login-token-received' => 'Did not receive a login token from the application.',
	'mw-api-client-api-response-is-not-serializable' => 'Result returned is not unserializable; make sure:',
	'mw-api-client-troubleshooting-tips' => '* the api endpoint is set properly' . PHP_EOL . '* the application is up and running' . PHP_EOL . '* the api call is correctly formed' . PHP_EOL . '* the wiki can properly use curl to connect with the api',
	'mw-api-client-no-params' => 'No Upload Parameters specified.',
	'mw-api-client-not-valid-param' => "The Upload Parameter '''<tt>[$1]</tt>''' is not a valid upload parameter.",

	/**
	 * db client
	 */
	'gwtoolset-db-client-support' => 'The GWToolset extension currently supports only MySQL',

	/**
	 * jobs
	 */
	'gwtoolset-batchjob-metadata-created' => '== Step 4 : Batch Upload ==' . PHP_EOL . 'Batch job added. Your metadata file will be analyzed shortly and each item will be uploaded to the wiki in a background process. You can check the [$1] page to see when they have been uploaded.',
	'gwtoolset-batchjobs-item-created' => 'Batch jobs for ($1) item(s) have been created; these will process one at a time via a background job.',
	'gwtoolset-batchjobs-item-created-some' => 'Unfortunately not all items were added as batch jobs. Batch jobs were created for ($1) item(s); with ($2) items having an issue. Please contact a developer if this is a problem.'
);

/**
 * Message documentation
 * @author dan-nl
 */
$messages['qqq'] = array(
	'gwtoolset-desc' => '{{desc|name=GWToolset|url=https://www.mediawiki.org/wiki/Extension:GWToolset}}'
);
