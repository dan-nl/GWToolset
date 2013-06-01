<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
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
		'GWToolset\Adapters\DataAdapterInterface' => '/includes/Adapters/DataAdapterInterface.php',

		'GWToolset\Adapters\Api\ApiAdapterAbstract' => '/includes/Adapters/Api/ApiAdapterAbstract.php',
		'GWToolset\Adapters\Api\MappingApiAdapter' => '/includes/Adapters/Api/MappingApiAdapter.php',

		'GWToolset\Adapters\Db\DbAdapterAbstract' => '/includes/Adapters/Db/DbAdapterAbstract.php',
		'GWToolset\Adapters\Db\MediawikiTemplateDbAdapter' => '/includes/Adapters/Db/MediawikiTemplateDbAdapter.php',

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

		'GWToolset\Helpers\FileChecks' => '/includes/Helpers/FileChecks.php',
		'GWToolset\Helpers\WikiChecks' => '/includes/Helpers/WikiChecks.php',
		'GWToolset\Helpers\WikiPages' => '/includes/Helpers/WikiPages.php',

		'GWToolset\Hooks' => '/includes/Hooks/Hooks.php',

		'GWToolset\Jobs\UploadFromUrlJob' => '/includes/Jobs/UploadFromUrlJob.php',
		'GWToolset\Jobs\UploadMediafileJob' => '/includes/Jobs/UploadMediafileJob.php',
		'GWToolset\Jobs\UploadMetadataJob' => '/includes/Jobs/UploadMetadataJob.php',

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
		'Php\FilterException' => '/includes/Php/FilterException.php'
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
			'gwtoolset-save-mapping-failed',
			'gwtoolset-save-mapping-succeeded'
		)
	);

	public static $hooks = array(
		'LoadExtensionSchemaUpdates' => 'GWToolset\Hooks::onLoadExtensionSchemaUpdates'
	);

	public static $jobs = array(
		'gwtoolsetUploadFromUrlJob' => 'GWToolset\Jobs\UploadFromUrlJob',
		'gwtoolsetUploadMediafileJob' => 'GWToolset\Jobs\UploadMediafileJob',
		'gwtoolsetUploadMetadataJob' => 'GWToolset\Jobs\UploadMetadataJob'
	);

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
	 * for metadata files
	 */
	public static $accepted_types = array(
		'xml' => array( 'text/xml', 'application/xml' )
	);

	/**
	 * which extension/mimetype combinations should the extension accept
	 * for media files
	 */
	public static $accepted_media_types = array(
		'jpg' => array( 'image/jpeg' )
	);

	/**
	 * which MediaWiki Templates are allowed for mapping
	 */
	public static $allowed_templates = array(
		'Artwork',
		'Book',
		'Musical work',
		'Photograph',
		'Specimen'
	);

	public static $category_separator = '|';
	public static $metadata_separator = '; ';
	public static $title_separator = '-';

	/**
	 * note that this mapping tag has a hardcoded pregmatch in
	 * GWToolset/includes/Adapters/Api/MappingApiAdapter.php
	 * that needs to change if this value changes
	 */
	public static $metadata_mapping_open_tag = '<mapping_json>';
	public static $metadata_mapping_close_tag = '</mapping_json>';

	// category automatically assigned to saved metadata mappings
	public static $metadata_mapping_category = 'GWToolset Metadata Mappings';

	// sub directory used to place a saved metadata mapping within a user’s namespace
	public static $metadata_mapping_subdirectory = 'Metadata Mappings/';

	// category automatically added to items uploaded by GWToolset
	public static $mediawiki_template_default_category = 'GWToolset Batch Upload';

	// category automatically assigned to metadata files uploaded by GWToolset
	public static $metadata_file_category = 'GWToolset Metadata Sets';

	// Category:Source_templates is the category on commons for partner templates
	public static $source_templates = 'Source templates';

	public static $mediawiki_licensing_templates = array(
		'http://creativecommons.org/publicdomain/mark/1.0/' => '{{PD-US}}{{PD-old}}', // Public Domain Mark 1.0
		'http://creativecommons.org/publicdomain/zero/1.0/' => '{{Cc-zero}}', // CC0 1.0 Universal (CC0 1.0) Public Domain Dedication
		'http://creativecommons.org/licenses/by/3.0/' => '{{Cc-by-3.0}}', // Attribution 3.0 Unported (CC BY 3.0)
		'http://creativecommons.org/licenses/by-sa/3.0/' => '{{Cc-by-sa-3.0}}', // Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)

		'http://creativecommons.org/licenses/by-nd/3.0/' => '{{Cc-by-nd-3.0}}', // Attribution-NoDerivs 3.0 Unported (CC BY-ND 3.0)
		'http://creativecommons.org/licenses/by-nc/3.0/' => '{{Cc-by-nc-3.0}}', // Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
		'http://creativecommons.org/licenses/by-nc-sa/3.0/' => '{{Cc-by-nc-sa-3.0}}', // Attribution-NonCommercial-ShareAlike 3.0 Unported (CC BY-NC-SA 3.0
		'http://creativecommons.org/licenses/by-nc-nd/3.0/' => '{{Cc-by-nc-nd-3.0}}' // Attribution-NonCommercial-NoDerivs 3.0 Unported (CC BY-NC-ND 3.0)
	);

	public static $job_throttle = 10;

}
