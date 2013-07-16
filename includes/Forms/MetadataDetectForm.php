<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Forms;
use GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	GWToolset\Models\MediawikiTemplate,
	Linker,
	Php\Filter,
	SpecialPage,
	Title;

class MetadataDetectForm {

	/**
	 * returns an html form for step 1 : Metadata Detect
	 *
	 * @param {SpecialPage} $SpecialPage
	 *
	 * @return {string}
	 * an html form
	 */
	public static function getForm( SpecialPage $SpecialPage ) {
		$MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );

		return
			wfMessage( 'gwtoolset-step-1-instructions' )->parse() .

			'<form id="gwtoolset-form" action="' . $SpecialPage->getContext()->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

			'<fieldset>' .

			'<legend>' . wfMessage( 'gwtoolset-upload-legend' )->escaped() . '</legend>' .

			'<input type="hidden" name="gwtoolset-form" value="metadata-detect"/>' .
			'<input type="hidden" name="wpEditToken" value="' . $SpecialPage->getUser()->getEditToken() . '">' .
			'<input type="hidden" name="MAX_FILE_SIZE" value="' . FileChecks::getMaxUploadSize() . '">' .

			'<ol>' .

			'<li>' .
			'<label>' .
			wfMessage( 'gwtoolset-record-element-name' )->escaped() .
			'<input type="text" name="record-element-name" value="" placeholder="record"/>' .
			'</label>' .
			' <span class="required">*</span>' .
			'</li>' .

			'<li>' .
			'<label>' .
			wfMessage( 'gwtoolset-which-mediawiki-template' )->escaped() .
			$MediawikiTemplate->getTemplatesAsSelect( 'mediawiki-template-name' ) .
			'</label>' .
			' <span class="required">*</span>' .
			'</li>' .

			'<li>' .
			'<label>' .
			wfMessage( 'gwtoolset-which-metadata-mapping' )->escaped() .
			'<input type="text" name="metadata-mapping-url" value="" placeholder="' . Filter::evaluate( Config::$metadata_namespace ) . Filter::evaluate( Config::$metadata_mapping_subdirectory ) . '/User-name/mapping-name.json" class="gwtoolset-url-input"/>' .
			'</label><br />' .
			Linker::link(
				Title::newFromText( 'Special:PrefixIndex/' . Config::$metadata_namespace . Config::$metadata_mapping_subdirectory ),
				Filter::evaluate( Config::$metadata_namespace ) . Filter::evaluate( Config::$metadata_mapping_subdirectory ),
				array( 'target' => '_blank' )
			) .
			'</li>' .

			'<li>' .
			wfMessage( 'gwtoolset-ensure-well-formed-xml' )->parse() . ' <span class="required">*</span><br />' .
			wfMessage( 'gwtoolset-metadata-file-source' )->escaped() . '<br />' .
			wfMessage( 'gwtoolset-metadata-file-source-info' )->escaped() .
			'<ul>' .
			'<li>' .
			'<label>' .
			wfMessage( 'gwtoolset-metadata-file-url' )->escaped() .
			'<input type="text" name="metadata-file-url" value="" placeholder="Two-images.xml" class="gwtoolset-url-input"/>' .
			'</label><br />' .
			Linker::link(
				Title::newFromText( 'Special:PrefixIndex/' . Config::$metadata_namespace . Config::$metadata_sets_subdirectory ),
				Filter::evaluate( Config::$metadata_namespace ) . Filter::evaluate( Config::$metadata_sets_subdirectory ),
				array( 'target' => '_blank' )
			) .
			'</li>' .

			'<li>' .
			'<label>' .
			wfMessage( 'gwtoolset-metadata-file-upload' )->escaped() .
			'<input type="file" name="metadata-file-upload" ' . FileChecks::getFileAcceptAttribute( Config::$accepted_metadata_types ) . '>' .
			'</label><br />' .

			'<i>' .
			wfMessage( 'gwtoolset-accepted-file-types' )->escaped() . ' ' . FileChecks::getAcceptedExtensionsAsList( Config::$accepted_metadata_types ) . '<br />' .
			wfMessage( 'upload-maxfilesize' )->params( number_format( FileChecks::getMaxUploadSize() / 1024 ) )->escaped() . ' kilobytes' .
			'</i>' .
			'</li>' .
			'</ul>' .
			'</li>' .

			'</ol>' .

			'</fieldset>' .

			'<p><span class="required">*</span>' . wfMessage( 'gwtoolset-required-field' )->escaped() . '</p>' .
			'<input type="submit" name="submit" value="' . wfMessage( 'emailusernamesubmit' )->escaped() . '">' .

			'</form>';
	}
}
