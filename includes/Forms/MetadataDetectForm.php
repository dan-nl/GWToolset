<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Forms;
use GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	IContextSource;


class MetadataDetectForm {

	/**
	 * @todo: mediawiki templates need to come from a config setting or
	 * dynamic algorithm that indiactes which mw templates are accepted
	 */
	public static function getForm( IContextSource $Context ) {

		global $wgArticlePath;
		$MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );

		return
			'<h2>' . wfMessage( 'gwtoolset-metadata-detect-step-1' )->plain() . '</h2>' .
			'<p>' . wfMessage( 'gwtoolset-metadata-detect-step-1-instructions' )->parse() . '</p>' .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .

					'<legend>' . wfMessage( 'gwtoolset-upload-legend' )->plain() . '</legend>' .

					'<input type="hidden" name="gwtoolset-form" value="metadata-detect"/>' .
					'<input type="hidden" name="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
					'<input type="hidden" name="MAX_FILE_SIZE" value="' . FileChecks::gwToolsetMaxUploadSize() . '">' .

					'<ol>' .

						'<li>' .
							'<p><label>' .
								wfMessage( 'gwtoolset-record-element-name' )->plain() .
								'<input type="text" name="record-element-name" value="" placeholder="record"/>' .
							'</label> <span class="required">*</span></p>' .
						'</li>' .

						'<li>' .
							'<p><label>' .
								wfMessage( 'gwtoolset-which-mediawiki-template' )->plain() .
									$MediawikiTemplate->getModelKeysAsSelect( 'mediawiki-template-name' ) .
							'</label> <span class="required">*</span></p>' .
						'</li>' .

						'<li>' .
							'<p><label>' .
								wfMessage( 'gwtoolset-which-metadata-mapping' )->plain() .
								'<input type="text" name="metadata-mapping-url" value="" placeholder="User:Gwtoolset\dublin core : Artwork" class="gwtoolset-url-input"/>' .
							'</label><br />' .
							'<a href="' . str_replace( '$1', 'Category:' . Config::$metadata_mapping_category, $wgArticlePath ) . '" target="_blank">' . 'Category:' . Config::$metadata_mapping_category . '</a></p>' .
						'</li>' .

						'<li>' .
							wfMessage( 'gwtoolset-ensure-well-formed-xml' )->parse() . ' <span class="required">*</span><br />' .
							wfMessage( 'gwtoolset-metadata-file-source' )->plain() . '<br />' .
							wfMessage( 'gwtoolset-metadata-file-source-info' )->plain() .
							'<ul>' .
								'<li>' .
									'<label>' .
										wfMessage( 'gwtoolset-metadata-file-url' )->plain() .
										'<input type="text" name="metadata-file-url" value="" placeholder="Two-images.xml" class="gwtoolset-url-input"/>' .
									'</label><br />' .
									'<a href="' . str_replace( '$1', 'Category:' . Config::$metadata_file_category, $wgArticlePath ) . '" target="_blank">' . 'Category:' . Config::$metadata_file_category . '</a>' .
								'</li>' .

								'<li>' .
									'<label>' .
										wfMessage( 'gwtoolset-metadata-file' )->plain() .
										'<input type="file" name="metadata-file-upload" ' . FileChecks::getFileAcceptAttribute( Config::$accepted_types ) . '>' .
									'</label><br />' .

									'<i>' .
										wfMessage( 'gwtoolset-accepted-file-types' )->plain() . ' ' . FileChecks::getAcceptedExtensionsAsList( Config::$accepted_types ) . '<br />' .
										wfMessage( 'upload-maxfilesize' )->params( number_format( FileChecks::gwToolsetMaxUploadSize() / 1024 ) )->plain() . ' kilobytes' .
									'</i>' .
								'</li>' .
							'</ul>' .
						'</li>' .

					'</ol>' .

				'</fieldset>' .

				'<p><span class="required">*</span>' . wfMessage( 'gwtoolset-required-field' )->plain() . '</p>' .
				'<input type="submit" name="submit" value="' . wfMessage( 'emailusernamesubmit' )->plain() . '">' .

			'</form>';

	}


}