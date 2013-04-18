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
use	Exception,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	IContextSource;


class MetadataMappingForm {


	public static function getForm( IContextSource $Context, array &$user_options = array(), $metadata_selects = null, $metadata_as_table_rows = null, $metadata_select = null ) {

		return
			'<h2>' . wfMessage('gwtoolset-metadata-detect-step-2') . '</h2>' .
			wfMessage('gwtoolset-metadata-detect-step-2-instructions')->params( $user_options['mediawiki-template-name'] ) .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .

						'<legend>' . wfMessage('gwtoolset-metadata-mapping-legend') . '</legend>' .

						'<input type="hidden" name="gwtoolset-form" value="metadata-mapping"/>' .
						'<input type="hidden" name="metadata-file-url" value="' . $user_options['metadata-file-url'] . '"/>' .
						'<input type="hidden" name="record-element-name" value="' . $user_options['record-element-name'] . '"/>' .
						'<input type="hidden" name="mediawiki-template-name" id="gwtoolset-mediawiki-template-name" value="' . $user_options['mediawiki-template-name'] . '"/>' .
						'<input type="hidden" name="metadata-mapping" id="gwtoolset-metadata-mapping" value="' . $user_options['metadata-mapping'] . '"/>' .
						'<input type="hidden" name="metadata-mapping-url" value="' . $user_options['metadata-mapping-url'] . '"/>' .
						'<input type="hidden" name="wpEditToken" id="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
						'<input type="hidden" name="MAX_FILE_SIZE"  value="' . FileChecks::gwToolsetMaxUploadSize() . '">' .

						'<h3>' .
							wfMessage('gwtoolset-mediawiki-template')->params( $user_options['mediawiki-template-name'] ) .
							( !empty( $mapping_name['user-name'] ) ? ', ' . $mapping_name['user-name'] : null ) .
							( !empty( $mapping_name['mapping-name'] ) ? ' : ' . $mapping_name['mapping-name'] : null ) .
						'</h3>' .

						'<table id="template-table" style="float:left;margin-right:2%;margin-bottom:1em;">' .
							'<thead>' .
								'<tr><th>template field</th><th colspan="2">maps to</th></tr>' .
							'</thead>' .
							'<tbody>' .
								$metadata_selects .
							'</tbody>' .
						'</table>' .
						'<table style="float:left; display: inline; width: 60%; overflow: auto;">' .
							'<thead>' .
								'<tr><th colspan="2">metadata’s example record’s contents</th></tr>' .
							'</thead>' .
							'<tbody style="vertical-align: top;">' .
								$metadata_as_table_rows .
							'</tbody>' .
						'</table>' .

						'<p style="clear:both;">&nbsp;</p>' .

						'<p>' . wfMessage('gwtoolset-required-field') . '</p>' .

						'<p>' .
							wfMessage('copyrightwarning2') .
						'</p>' .

						'<p style="left; margin-top:1em;">' .
							'<b>' . wfMessage('gwtoolset-metadata-file-url') . '</b><br/>' .
							$user_options['metadata-file-url'] .
						'</p>' .

						//'<p id="catlinks" style="left; margin-top:1em;">' .
						//	'<b>' . wfMessage('categories') . '</b><br/>' .
						//	wfMessage('gwtoolset-categories') . '<br/>' .
						//	'<input type="text" name="categories" maxlength="255" size="100"/><br/>' .
						//	wfMessage('gwtoolset-categories-tooltip') .
						//'</p>' .

						'<h3 style="margin-top:1em;">' . wfMessage('categories') . '</h3>' .

							'<p>' .
								'<i><u>' . wfMessage('gwtoolset-global-categories') . '</u></i><br/>' .
								wfMessage('gwtoolset-global-tooltip') .
							'</p>' .

							'<table>' .
								'<tbody>' .
									'<tr>' .
										'<td><label for="permission">' . wfMessage('gwtoolset-category') . ' :</label></td>' .
										'<td class="metadata-add"></td>' .
										'<td><input type="text" name="category[]"/></td>' .
									'</tr>' .
								'</tbody>' .
							'</table>' .

							'<p style="margin-top:1em;">' .
								'<i><u>' . wfMessage('gwtoolset-specific-categories') . '</u></i><br/>' .
								wfMessage('gwtoolset-specific-tooltip') .
							'</p>' .

							'<table>' .
								'<thead>' .
									'<th>&nbsp;</th>' .
									'<th>' . wfMessage('gwtoolset-phrasing'). '</th>' .
									'<th>' . wfMessage('gwtoolset-metadata-field'). '</th>' .
								'</thead>' .
								'<tbody>' .
									'<tr>' .
										'<td class="category-add"></td>' .
										'<td><input type="text" name="category-phrase[]" placeholder="Painted by"/></td>' .
										'<td><select name="category-metadata[]">' . $metadata_select . '</select></td>' .
									'</tr>' .
								'</tbody>' .
							'</table>' .

						'<h3 style="margin-top:1em;">' . wfMessage('summary') . '</h3>' .
						'<p>' .
							//'<b>' . wfMessage('summary') . '</b><br/>' .
							'<input class="mw-summary" id="wpSummary" maxlength="255" spellcheck="true" title="Enter a short summary [ctrl-option-b]" accesskey="b" name="wpSummary">' .
						'</p>' .

						'<p>' .
							'<label><input type="checkbox" name="upload-media" value="true"/> ' . wfMessage('gwtoolset-retrieve-media') . '</label><br/>' .
							wfMessage('gwtoolset-retrieve-media-explanation') .
						'</p>' .

						'<p>' .
							'<label><input type="checkbox" name="save-as-batch-job" value="true" checked/> ' . wfMessage('gwtoolset-add-as-a-job') . '</label><br/>' .
							wfMessage('gwtoolset-add-as-a-job-description') .
						'</p>'.

						'<input type="submit" name="submit" value="process file">' .

				'</fieldset>' .

			'</form>';

	}


}

