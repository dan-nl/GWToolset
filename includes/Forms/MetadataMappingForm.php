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


	public static function getForm( IContextSource $Context, array &$user_options = array(), $metadata_selects = null, $metadata_as_table_rows = null ) {

		$mapping_name = json_decode( str_replace( "`", '"', $user_options['metadata-mapping'] ), true );
		
		return
			'<h2>' . wfMessage('gwtoolset-metadata-detect-step-2') . '</h2>' .
			wfMessage('gwtoolset-metadata-detect-step-2-instructions')->params( $user_options['mediawiki-template-name'] ) .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .
	
						'<legend>' . wfMessage('gwtoolset-metadata-mapping-legend') . '</legend>' .

						'<input type="hidden" name="gwtoolset-form" value="metadata-mapping"/>' .
						'<input type="hidden" name="metadata-file-url" value="' . $user_options['metadata-file-url'] . '"/>' .
						//'<input type="hidden" name="record-element-name" value="' . $user_options['record-element-name'] . '"/>' .
						'<input type="hidden" name="mediawiki-template-name" id="gwtoolset-mediawiki-template-name" value="' . $user_options['mediawiki-template-name'] . '"/>' .
						'<input type="hidden" name="metadata-mapping" id="gwtoolset-metadata-mapping" value="' . $mapping_name['mapping-name'] . '"/>' .
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

						'<p>&nbsp;</p>' .

						'<p>' .
							'<b>' . wfMessage('gwtoolset-metadata-file-url') . '</b> : ' . $user_options['metadata-file-url'] .
						'</p>' .
						
						'<p>' .
							wfMessage('summary') . ' <b>(tbd)</b><input class="mw-summary" id="wpSummary" maxlength="255" tabindex="1" size="60" spellcheck="true" title="Enter a short summary [ctrl-option-b]" accesskey="b" name="wpSummary">' .
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

