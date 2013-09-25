<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset;

class Config {

	public static $name = 'GWToolset';
	public static $author = array( 'dan entous' );
	public static $url = 'https://www.mediawiki.org/wiki/Extension:GWToolset';
	public static $descriptionmsg = 'gwtoolset-desc';
	public static $type = 'media';
	public static $version = '0.0.1';

	/**
	 * @var {array}
	 * which extension/mimetype combinations should the extension accept
	 * for mapping files
	 */
	public static $accepted_mapping_types = array(
		'json' => array( 'application/json' )
	);

	/**
	 * @var {array}
	 * which extension/mimetype combinations should the extension accept
	 * for media files
	 */
	public static $accepted_media_types = array(
		'jpg' => array( 'image/jpeg' ),
		'pdf' => array( 'application/pdf' )
	);

	/**
	 * @var {array}
	 * which extension/mimetype combinations should the extension accept
	 * for metadata files
	 */
	public static $accepted_metadata_types = array(
		'xml' => array( 'text/xml', 'application/xml' )
	);

	/**
	 * @var {array}
	 * which MediaWiki Templates are allowed for mapping
	 */
	public static $allowed_templates = array(
		'Artwork',
		'Book',
		'Musical work',
		'Photograph',
		'Specimen'
	);

	/**
	 * @var {array}
	 */
	public static $autoloader_classes = array(
		'GWToolset\Adapters\DataAdapterInterface' => '/includes/Adapters/DataAdapterInterface.php',

		'GWToolset\Adapters\Php\MappingPhpAdapter' => '/includes/Adapters/Php/MappingPhpAdapter.php',
		'GWToolset\Adapters\Php\MediawikiTemplatePhpAdapter' => '/includes/Adapters/Php/MediawikiTemplatePhpAdapter.php',

		'GWToolset\Exception' => '/includes/Exception.php',

		'GWToolset\Forms\MetadataDetectForm' => '/includes/Forms/MetadataDetectForm.php',
		'GWToolset\Forms\MetadataMappingForm' => '/includes/Forms/MetadataMappingForm.php',
		'GWToolset\Forms\PreviewForm' => '/includes/Forms/PreviewForm.php',

		'GWToolset\Handlers\Ajax\AjaxHandler' => '/includes/Handlers/Ajax/AjaxHandler.php',
		'GWToolset\Handlers\Ajax\MetadataMappingSaveHandler' => '/includes/Handlers/Ajax/MetadataMappingSaveHandler.php',

		'GWToolset\Handlers\Forms\FormHandler' => '/includes/Handlers/Forms/FormHandler.php',
		'GWToolset\Handlers\Forms\MetadataDetectHandler' => '/includes/Handlers/Forms/MetadataDetectHandler.php',
		'GWToolset\Handlers\Forms\MetadataMappingHandler' => '/includes/Handlers/Forms/MetadataMappingHandler.php',

		'GWToolset\Handlers\SpecialPageHandler' => '/includes/Handlers/SpecialPageHandler.php',
		'GWToolset\Handlers\UploadHandler' => '/includes/Handlers/UploadHandler.php',
		'GWToolset\Handlers\Xml\XmlDetectHandler' => '/includes/Handlers/Xml/XmlDetectHandler.php',
		'GWToolset\Handlers\Xml\XmlHandler' => '/includes/Handlers/Xml/XmlHandler.php',
		'GWToolset\Handlers\Xml\XmlMappingHandler' => '/includes/Handlers/Xml/XmlMappingHandler.php',

		'GWToolset\Helpers\FileChecks' => '/includes/Helpers/FileChecks.php',
		'GWToolset\Helpers\FileSystem' => '/includes/Helpers/FileSystem.php',
		'GWToolset\Helpers\WikiChecks' => '/includes/Helpers/WikiChecks.php',
		'GWToolset\Helpers\WikiPages' => '/includes/Helpers/WikiPages.php',

		'GWToolset\Jobs\UploadFromUrlJob' => '/includes/Jobs/UploadFromUrlJob.php',
		'GWToolset\Jobs\UploadMediafileJob' => '/includes/Jobs/UploadMediafileJob.php',
		'GWToolset\Jobs\UploadMetadataJob' => '/includes/Jobs/UploadMetadataJob.php',

		'GWToolset\Models\Mapping' => '/includes/Models/Mapping.php',
		'GWToolset\Models\MediawikiTemplate' => '/includes/Models/MediawikiTemplate.php',
		'GWToolset\Models\Menu' => '/includes/Models/Menu.php',
		'GWToolset\Models\ModelAbstract' => '/includes/Models/ModelAbstract.php',
		'GWToolset\Models\ModelInterface' => '/includes/Models/ModelInterface.php',

		'GWToolset\SpecialGWToolset' => '/includes/Specials/SpecialGWToolset.php',

		'Php\File' => '/includes/Php/File.php',
		'Php\FileException' => '/includes/Php/FileException.php',
		'Php\Filter' => '/includes/Php/Filter.php',
		'Php\FilterException' => '/includes/Php/FilterException.php'
	);

	/**
	 * @var {string}
	 */
	public static $category_separator = '|';

	/**
	 * @var {array}
	 */
	public static $hooks = array();

	/**
	 * @var {int}
	 * 20 minutes, 25 seconds default
	 */
	public static $http_timeout = 1200;

	/**
	 * @var {array}
	 */
	public static $jobs = array(
		'gwtoolsetUploadFromUrlJob' => 'GWToolset\Jobs\UploadFromUrlJob',
		'gwtoolsetUploadMediafileJob' => 'GWToolset\Jobs\UploadMediafileJob',
		'gwtoolsetUploadMetadataJob' => 'GWToolset\Jobs\UploadMetadataJob'
	);

	/**
	 * @var {int}
	 */
	public static $job_throttle = 3;

	/**
	 * @var {int}
	 * 1.25e7 or 12,500,000 default
	 */
	public static $max_image_area = 6.4e7;

	/**
	 * @var {int}
	 * set in bytes
	 * the maximum upload filesize this extension will accept. when set to 0,
	 * the wiki’s $wgMaxUploadSize is used
	 */
	public static $max_upload_filesize = 0;

	/**
	 * @var {array}
	 * http://gwtoolset/api.php?action=templatedata&titles=Template:Artwork
	 */
	public static $mediawiki_templates = array(
		'Artwork' => '{"artist":"","title":"","description":"","date":"","medium":"","dimensions":"","institution":"","location":"","references":"","object history":"","exhibition history":"","credit line":"","inscriptions":"","notes":"","accession number":"","source":"","permission":"","other_versions":""}',
		'Book' => '{"Author":"","Translator":"","Editor":"","Illustrator":"","Title":"","Subtitle":"","Series title":"","Volume":"","Edition":"","Publisher":"","Printer":"","Date":"","City":"","Language":"","Description":"","Source":"","Permission":"","Image":"","Image page":"","Pageoverview":"","Wikisource":"","Homecat":"","Other_versions":"","ISBN":"","LCCN":"","OCLC":""}',
		'Musical work' => '{"composer":"","lyrics_writer":"","performer":"","title":"","description":"","composition_date":"","performance_date":"","notes":"","record_ID":"","image":"","references":"","source":"","permission":"","other_versions":""}',
		'Photograph' => '{"photographer":"","title":"","description":"","depicted people":"","depicted place":"","date":"","medium":"","dimensions":"","institution":"","department":"","references":"","object history":"","exhibition history":"","credit line":"","inscriptions":"","notes":"","accession number":"","source":"","permission":"","other_versions":""}',
		'Specimen' => '{"taxon":"","authority":"","institution":"","accession number":"","sex":"","discovery place":"","cultivar":"","author":"","source":"","date":"","description":"","period":"","depicted place":"","camera coord":"","dimensions":"","institution":"","location":"","object history":"","exhibition history":"","credit line":"","notes":"","references":"","permission":"","other versions":"","photographer":"","source":""}'
	);

	/**
	 * @var {string}
	 * wiki namespace to store metadata mappings and data sets
	 */
	public static $mediafile_namespace = 'File:';

	/**
	 * @var {string}
	 * 128M default
	 */
	public static $memory_limit = '256M';

	/**
	 * @var {array}
	 */
	public static $messages = array(
		'GWToolset' => '/GWToolset.i18n.php',
		'GWToolsetAlias' => '/GWToolset.alias.php'
	);

	/**
	 * @var {string}
	 * the directory where metadata sets are stored on the file system
	 * the actual file system path should be calcualted in the method that uses
	 * the variable
	 */
	public static $metadata_directory = 'gwtoolset';

	/**
	 * @var {string}
	 * category automatically assigned to metadata files uploaded by GWToolset
	 */
	public static $metadata_file_category = 'GWToolset Metadata Sets';

	/**
	 * @var {string}
	 * note that this mapping tag has a hardcoded pregmatch in
	 * GWToolset/includes/Adapters/Api/MappingApiAdapter.php
	 * that needs to change if this value changes
	 */
	public static $metadata_mapping_open_tag = '<mapping_json>';

	/**
	 * @var {string}
	 * category automatically assigned to saved metadata mappings
	 */
	public static $metadata_mapping_category = 'GWToolset Metadata Mappings';

	/**
	 * @var {string}
	 */
	public static $metadata_mapping_close_tag = '</mapping_json>';

	/**
	 * @var {string}
	 * wiki namespace to store metadata mappings and data sets
	 */
	public static $metadata_namespace = 'GWToolset:';

	/**
	 * @var {string}
	 * sub directory used to place saved metadata mappings
	 */
	public static $metadata_mapping_subdirectory = 'Metadata Mappings';

	/**
	 * @var {string}
	 */
	public static $metadata_separator = '; ';

	/**
	 * @var {string}
	 * sub directory used to place saved metadata sets
	 */
	public static $metadata_sets_subdirectory = 'Metadata Sets';

	/**
	 * @var {string}
	 * category automatically added to items uploaded by GWToolset
	 */
	public static $mediawiki_template_default_category = 'GWToolset Batch Upload';

	/**
	 * @var {int}
	 */
	public static $preview_throttle = 3;

	/**
	 * @var {array}
	 */
	public static $resources = array(
		'scripts' => array(
			'resources/js/ext.gwtoolset.js'
		),
		'styles' => array(
			'resources/css/ext.gwtoolset.css'
		),
		'messages' => array(
			'gwtoolset-developer-issue',
			'gwtoolset-loading',
			'gwtoolset-save-mapping',
			'gwtoolset-save-mapping-name',
			'gwtoolset-save-mapping-failed',
			'gwtoolset-save-mapping-succeeded',
			'gwtoolset-step-2-heading',
			'gwtoolset-back-link-option',
			'gwtoolset-save',
			'gwtoolset-cancel'
		),
		'dependencies' => array(
			'jquery.spinner',
			'jquery.ui.widget',
			'jquery.ui.button',
			'jquery.ui.draggable',
			'jquery.ui.mouse',
			'jquery.ui.position',
			'jquery.ui.resizable',
			'jquery.ui.dialog'
		)
	);

	/**
	 * @var {string}
	 * Category:Source_templates is the category on commons for partner templates
	 */
	public static $source_templates = 'Source templates';

	/**
	 * @see SpecialPage __constructor
	 * @var {string}
	 * name of the special page, as seen in links and URLs
	 */
	public static $special_page_name = 'GWToolset';

	/**
	 * @see SpecialPage __constructor
	 * @var {string}
	 * user right required, e.g. "block" or "delete"
	 */
	public static $special_page_restriction = 'upload_by_url';

	/**
	 * @see SpecialPage __constructor
	 * @var {boolean}
	 * whether the page is listed in Special:Specialpages
	 */
	public static $special_page_listed = true;

	/**
	 * @var {array}
	 */
	public static $special_pages = array(
		'GWToolset' => array(
			'class_name' => 'GWToolset\SpecialGWToolset',
			'group' => 'media'
		)
	);

	/**
	 * @var {string}
	 */
	public static $title_separator = '-';

	/**
	 * @var {boolean}
	 * tells the upload form to place the $accepted_mime_types in a comma
	 * delimited list in the input file’s accept attribute
	 */
	public static $use_file_accept_attribute = true;

	/**
	 * @var {string}
	 * the user group the user must be a member of in order to be able to use this extension
	 * @see GWToolset\Helpers\WikiChecks\checkUserWikiGroups
	 */
	public static $user_group = 'gwtoolset';

	/**
	 * @var {array}
	 * user permissions required in order to be able to use this extension
	 * @see GWToolset\Helpers\WikiChecks\checkUserPermissions
	 */
	public static $user_permissions = array(
		'upload',
		'upload_by_url',
		'edit'
	);

}
