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


class Config {


	public static $name = 'GWToolset';
	public static $author =  array( 'dan entous' );
	public static $url = 'https://www.mediawiki.org/wiki/Extension:GWToolset';
	public static $descriptionmsg = 'gwtoolset-desc';
	public static $version = '0.0.1';


	public static $autoloader_classes = array(

		'GWToolset\Forms\MetadataDetectForm' => '/includes/Forms/MetadataDetectForm.php',
		'GWToolset\Forms\MetadataMappingForm' => '/includes/Forms/MetadataMappingForm.php',
		'GWToolset\Forms\MetadataUploadForm' => '/includes/Forms/MetadataUploadForm.php',

		'GWToolset\Handlers\Ajax\AjaxHandler' => '/includes/Handlers/Ajax/AjaxHandler.php',
		'GWToolset\Handlers\Ajax\MetadataMappingSaveHandler' => '/includes/Handlers/Ajax/MetadataMappingSaveHandler.php',		

		'GWToolset\Handlers\Forms\FormHandler' => '/includes/Handlers/Forms/FormHandler.php',
		'GWToolset\Handlers\Forms\MetadataDetectHandler' => '/includes/Handlers/Forms/MetadataDetectHandler.php',
		'GWToolset\Handlers\Forms\MetadataMappingHandler' => '/includes/Handlers/Forms/MetadataMappingHandler.php',
		'GWToolset\Handlers\Forms\MetadataUploadHandler' => '/includes/Handlers/Forms/MetadataUploadHandler.php',

		'GWToolset\Handlers\SpecialPageHandler' => '/includes/Handlers/SpecialPageHandler.php',
		'GWToolset\Handlers\UploadHandler' => '/includes/Handlers/UploadHandler.php',
		'GWToolset\Handlers\Xml\XmlDetectHandler' => '/includes/Handlers/Xml/XmlDetectHandler.php',
		'GWToolset\Handlers\Xml\XmlHandler' => '/includes/Handlers/Xml/XmlHandler.php',
		'GWToolset\Handlers\Xml\XmlMappingHandler' => '/includes/Handlers/Xml/XmlMappingHandler.php',

		'GWToolset\Helpers\WikiChecks' => '/includes/Helpers/WikiChecks.php',
		'GWToolset\Helpers\FileChecks' => '/includes/Helpers/FileChecks.php',

		'GWToolset\Hooks' => '/includes/Hooks/Hooks.php',

		'GWToolset\Jobs\BatchUploadJob' => '/includes/Jobs/BatchUploadJob.php',

		'GWToolset\MediaWiki\Api\Client' => '/includes/MediaWiki/Api/Client.php',
		'GWToolset\MediaWiki\Api\ClientInterface' => '/includes/MediaWiki/Api/ClientInterface.php',
		'GWToolset\MediaWiki\Api\Login' => '/includes/MediaWiki/Api/Login.php',

		'GWToolset\Models\Mapping' => '/includes/Models/Mapping.php',
		'GWToolset\Models\MediawikiTemplate' => '/includes/Models/MediawikiTemplate.php',
		'GWToolset\Models\Menu' => '/includes/Models/Menu.php',
		'GWToolset\Models\Model' => '/includes/Models/Model.php',
		'GWToolset\Models\ModelInterface' => '/includes/Models/ModelInterface.php',

		'GWToolset\SpecialGWToolset' => '/includes/Specials/SpecialGWToolset.php',

		'Php\Curl' => '/includes/Php/Curl.php',
		'Php\File' => '/includes/Php/File.php',
		'Php\FileException' => '/includes/Php/FileException.php',
		'Php\Filter' => '/includes/Php/Filter.php',
		'Php\FilterException' => '/includes/Php/FilterException.php',

	);


	public static $messages = array(

		'GWToolset' => '/GWToolset.i18n.php',
		'GWToolsetAlias' => '/GWToolset.alias.php'

	);


	public static $special_pages = array(

		'GWToolset' => array(
			'class_name' => 'GWToolset\SpecialGWToolset',
			'group' => 'media'
		)

	);


	public static $resources = array(

		'scripts' => 'resources/js/ext.gwtoolset.js',
		'styles' => 'resources/css/ext.gwtoolset.css',
		'messages' => array(
			'gwtoolset-loading',
			'gwtoolset-save-mapping',
			'gwtoolset-save-mapping-name',
			'gwtoolset-save-mapping-error',
			'gwtoolset-save-mapping-success'
		)

	);


	public static $hooks = array(

		// placeholder 'LoadExtensionSchemaUpdates' => 'GWToolset\Hooks::onLoadExtensionSchemaUpdates'

	);


	public static $jobs = array(

		'gwtoolsetBatchUpload' => 'GWToolset\Jobs\BatchUploadJob'

	);


	/**
	 * api settings
	 *
	 * depending on your server set-up you may need to specify http://127.0.0.1/api.php
	 * instead of http://yourdomain.com/api.php
	 *
	 * these values should be set in the includes/ConfigCustom.php file
	 * @see includes/ConfigCustom.example.php
	 *
	 * @todo: find out if it's possible to use the logged in user’s credentials
	 * with the api; using $User->getEditToken() does not work
	 */
	public static $api_internal_endpoint = null;
	public static $api_internal_lgname = null;
	public static $api_internal_lgpassword = null;

	public static $api_external_endpoint = null;
	public static $api_external_lgname = null;
	public static $api_external_lgpassword = null;


	/**
	 * @see SpecialPage __constructor
	 *
	 * $name string
	 * name of the special page, as seen in links and URLs
	 *
	 * $restriction string
	 * user right required, e.g. "block" or "delete"
	 *
	 * $listed boolean
	 * whether the page is listed in Special:Specialpages
	 */
	public static $special_page_name = 'GWToolset';
	public static $restriction = 'upload_by_url';
	public static $listed = true;


	/**
	 * set to null or 0 to use the wiki’s value
	 * value must be a positive integer
	 */
	public static $max_file_upload = null;


	/**
	 * the user group the user musr be a member of in order to be able to use this extension
	 * @see GWToolset\Helpers\WikiChecks\checkUserWikiGroups
	 */
	public static $user_group = 'gwtoolset';
	
	/**
	 * user permissions required in order to be able to use this extension
	 * @see GWToolset\Helpers\WikiChecks\checkUserPermissions
	 */
	public static $user_permissions = array( 'upload', 'upload_by_url', 'edit' );


	/**
	 * tells the upload form to place the $accepted_mime_types in a comma
	 * delimited list in the input file’s accept attribute
	 */
	public static $use_file_accept_attribute = true;


	/**
	 * which extension/mimetype combinations should the extension accept
	 */
	public static $accepted_types = array(

		'xml' => array( 'text/xml', 'application/xml' )

	);


	/**
	 * which MediaWiki Templates are allowed for mapping
	 */
	public static $allowed_templates = array(

		'Artwork',
		'Book',
		'Musical work',
		'Photograph'

	);


	public static $metadata_separator = '; ';


	/**
	 * a flag to indicate whether or not to display debug information when available.
	 * in order to see this output, the user must also be a member of a permissions
	 * group that has the right, gwtoolset-debug
	 */
	public static $display_debug_output = false;


}