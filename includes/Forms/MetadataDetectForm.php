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
namespace GWToolset\Forms;
use	GWToolset\Adapters\Db\MappingDbAdapter,
	GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
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
		$Mapping = new Mapping( new MappingDbAdapter() );
		$MediawikiTemplate = new MediawikiTemplate( new MediawikiTemplateDbAdapter() );

		return
			'<h2>' . wfMessage('gwtoolset-metadata-detect-step-1') . '</h2>' .
			wfMessage('gwtoolset-metadata-detect-step-1-instructions')->plain() .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .

					'<legend>' . wfMessage('gwtoolset-upload-legend') . '</legend>' .

					'<input type="hidden" name="gwtoolset-form" value="metadata-detect"/>' .
					'<input type="hidden" name="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
					'<input type="hidden" name="MAX_FILE_SIZE"  value="' . FileChecks::gwToolsetMaxUploadSize() . '">' .

					'<ol>' .

						'<li>' .
							'<p><label>' .
								wfMessage('gwtoolset-record-element-name') . ' : ' .
								'<input type="text" name="record-element-name" value="" placeholder="record"/>' .
							'</label> <span class="required">*</span></p>' .
						'</li>' .

						'<li>' .
							'<p><label>' .
								wfMessage('gwtoolset-which-mediawiki-template') . ' : ' .
									$MediawikiTemplate->getModelKeysAsSelect( 'mediawiki-template-name' ) .
							'</label> <span class="required">*</span></p>' .
						'</li>' .

						'<li>' .
							'<p><label>' .
								wfMessage('gwtoolset-which-metadata-mapping') . ' : ' .
								//$Mapping->getModelKeysAsSelect( 'metadata-mapping', null, true ) .
								'<input type="text" name="metadata-mapping-url" value="" placeholder="User:Gwtoolset\dublin core : Artwork" class="gwtoolset-url-input"/>' .
							'</label><br/>' .
							'<a href="' . str_replace( '$1', 'Category:' . Config::$metadata_mapping_category, $wgArticlePath ) . '" target="_blank">' . 'Category:' . Config::$metadata_mapping_category . '</a></p>' .
						'</li>' .

						'<li>' .
							wfMessage('gwtoolset-ensure-well-formed-xml')->plain() . ' <span class="required">*</span><br/>' .
							wfMessage('gwtoolset-metadata-file-source') . '<br/>' .
							wfMessage('gwtoolset-metadata-file-source-info') .
							'<ul>' .
								'<li>' .
									'<label>' .
										wfMessage('gwtoolset-metadata-file-url') . ' : ' .
										'<input type="text" name="metadata-file-url" value="" placeholder="Two-images.xml" class="gwtoolset-url-input"/>' .
									'</label><br/>' .
									'<a href="' . str_replace( '$1', 'Category:' . Config::$metadata_file_category, $wgArticlePath ) . '" target="_blank">' . 'Category:' . Config::$metadata_file_category . '</a>' .
								'</li>' .

								'<li>' .
									'<label>' .
										wfMessage('gwtoolset-metadata-file') . ' : ' .
										'<input type="file" name="metadata-file-upload" ' . FileChecks::getFileAcceptAttribute( Config::$accepted_types ) . '>' .
									'</label><br/>' .

									'<i>' .
										wfMessage( 'gwtoolset-accepted-file-types' ) . ' ' . FileChecks::getAcceptedExtensionsAsList( Config::$accepted_types ) . '<br/>' .
										wfMessage( 'upload-maxfilesize', number_format( FileChecks::gwToolsetMaxUploadSize() / 1024 ) ) . ' kilobytes' .
									'</i>' .
								'</li>' .
							'</ul>' .
						'</li>' .

					'</ol>' .

				'</fieldset>' .

				'<p><span class="required">*</span> denotes required field</p>' .
				'<input type="submit" name="submit" value="' . wfMessage('emailusernamesubmit') . '">' .

			'</form>';

	}


}

