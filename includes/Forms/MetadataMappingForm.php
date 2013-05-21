<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Forms;
use Exception,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	IContextSource;


class MetadataMappingForm {


	public static function getForm( IContextSource $Context, array &$user_options = array(), $metadata_selects = null, $metadata_as_table_rows = null, $metadata_select = null ) {

		global $wgArticlePath;
		return
			'<h2>' . wfMessage( 'gwtoolset-metadata-detect-step-2' )->plain() . '</h2>' .
			'<p>' . wfMessage( 'gwtoolset-metadata-detect-step-2-instructions' )->params( $user_options['mediawiki-template-name'] )->parse() . '</p>' .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .

						'<legend>' . wfMessage( 'gwtoolset-metadata-mapping-legend' )->plain() . '</legend>' .

						'<input type="hidden" name="gwtoolset-form" value="metadata-mapping"/>' .
						'<input type="hidden" name="metadata-file-url" value="' . $user_options['metadata-file-url'] . '"/>' .
						'<input type="hidden" name="record-element-name" value="' . $user_options['record-element-name'] . '"/>' .
						'<input type="hidden" name="mediawiki-template-name" id="gwtoolset-mediawiki-template-name" value="' . $user_options['mediawiki-template-name'] . '"/>' .
						'<input type="hidden" name="metadata-mapping" id="gwtoolset-metadata-mapping" value="' . $user_options['metadata-mapping'] . '"/>' .
						'<input type="hidden" name="metadata-mapping-url" value="' . $user_options['metadata-mapping-url'] . '"/>' .
						'<input type="hidden" name="wpEditToken" id="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
						'<input type="hidden" name="MAX_FILE_SIZE"  value="' . FileChecks::gwToolsetMaxUploadSize() . '">' .

						'<h3>' .
							wfMessage( 'gwtoolset-mediawiki-template' )->params( $user_options['mediawiki-template-name'] )->plain() .
							( !empty( $mapping_name['user-name'] ) ? ', ' . $mapping_name['user-name'] : null ) .
							( !empty( $mapping_name['mapping-name'] ) ? ' : ' . $mapping_name['mapping-name'] : null ) .
						'</h3>' .

						'<table id="template-table" style="float:left;margin-right:2%;margin-bottom:1em;">' .
							'<thead>' .
								'<tr><th>' . wfMessage('gwtoolset-template-field')->plain() . '</th><th colspan="2">' . wfMessage('gwtoolset-maps-to')->plain() . '</th></tr>' .
							'</thead>' .
							'<tbody>' .
								$metadata_selects .
							'</tbody>' .
						'</table>' .
						'<table style="float:left; display: inline; width: 60%; overflow: auto;">' .
							'<thead>' .
								'<tr><th colspan="2">' . wfMessage('gwtoolset-example-record')->plain() . '</th></tr>' .
							'</thead>' .
							'<tbody style="vertical-align: top;">' .
								$metadata_as_table_rows .
							'</tbody>' .
						'</table>' .

						'<p style="clear:both;padding-top:2em;"><span class="required">*</span>' . wfMessage( 'gwtoolset-required-field' )->plain() . '</p>' .
						wfMessage( 'copyrightwarning2' )->parseAsBlock() .

						'<p style="left; margin-top:1em;">' .
							'<b>' . wfMessage( 'gwtoolset-metadata-file-url' )->plain() . '</b><br />' .
							$user_options['metadata-file-url'] .
						'</p>' .

						'<h3 style="margin-top:1em;">' . wfMessage( 'categories' )->plain() . '</h3>' .

							'<p>' .
								'<i><u>' . wfMessage( 'gwtoolset-global-categories' )->plain() . '</u></i><br />' .
								wfMessage( 'gwtoolset-global-tooltip' )->plain() .
							'</p>' .

							'<table>' .
								'<tbody>' .
									'<tr>' .
										'<td><label for="gwtoolset-category">' . wfMessage( 'gwtoolset-category' )->plain() . '</label></td>' .
										'<td class="metadata-add"></td>' .
										'<td><input type="text" id="gwtoolset-category" name="category[]"/></td>' .
									'</tr>' .
								'</tbody>' .
							'</table>' .

							'<p style="margin-top:1em;">' .
								'<i><u>' . wfMessage( 'gwtoolset-specific-categories' )->plain() . '</u></i><br />' .
								wfMessage( 'gwtoolset-specific-tooltip' )->plain() .
							'</p>' .

							'<table>' .
								'<thead>' .
									'<th>&nbsp;</th>' .
									'<th>' . wfMessage( 'gwtoolset-phrasing' )->plain(). '</th>' .
									'<th>' . wfMessage( 'gwtoolset-metadata-field' )->plain(). '</th>' .
								'</thead>' .
								'<tbody>' .
									'<tr>' .
										'<td class="category-add"></td>' .
										'<td><input type="text" name="category-phrase[]" placeholder="' . wfMessage('gwtoolset-painted-by')->plain() . '"/></td>' .
										'<td><select name="category-metadata[]">' . $metadata_select . '</select></td>' .
									'</tr>' .
								'</tbody>' .
							'</table>' .

						'<h3 style="margin-top:1em;">' . wfMessage( 'gwtoolset-partner' )->plain() . '</h3>' .
						'<p>' .
							wfMessage( 'gwtoolset-partner-explanation' )->plain() . '<br />' .
							'<label>' .
								wfMessage( 'gwtoolset-partner-template' )->plain() .
								'<input type="text" name="partner-template-url" value="" placeholder="Template:Europeana" class="gwtoolset-url-input"/>' .
							'</label><br />' .
							'<a href="' . str_replace( '$1', 'Category:' . Config::$source_templates, $wgArticlePath ) . '" target="_blank">' . 'Category:' . Config::$source_templates . '</a>' .
						'</p>' .

						'<h3 style="margin-top:1em;">' . wfMessage( 'summary' )->plain() . '</h3>' .
						'<p>' .
							'<input class="mw-summary" id="wpSummary" maxlength="255" spellcheck="true" title="Enter a short summary [ctrl-option-b]" accesskey="b" name="wpSummary">' .
						'</p>' .

						'<p>' .
							'<label><input type="checkbox" name="upload-media" value="true"/> ' . wfMessage( 'gwtoolset-retrieve-media' )->plain() . '</label><br />' .
							wfMessage( 'gwtoolset-retrieve-media-explanation' )->plain() .
						'</p>' .

						'<p>' .
							'<label><input type="checkbox" name="save-as-batch-job" value="true" checked/> ' . wfMessage( 'gwtoolset-add-as-a-job' )->plain() . '</label><br />' .
							wfMessage( 'gwtoolset-add-as-a-job-description' )->plain() .
						'</p>'.

						'<input type="submit" name="submit" value="process file">' .

				'</fieldset>' .

			'</form>';

	}


}