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
namespace	GWToolset\Forms;
use			Exception,
			GWToolset\Config,
			GWToolset\Helpers\FileChecks,
			IContextSource;


class MetadataMappingForm {


	public static function getForm( IContextSource $Context, array &$user_options = array(), &$metadata_selects = null, &$metadata_as_table_rows = null ) {

		$mapping_name = json_decode( str_replace( "`", '"', $user_options['metadata-mapping'] ), true );
		
		return
			'<h2>' . wfMessage('gwtoolset-metadata-detect-step-2') . '</h2>' .
			wfMessage('gwtoolset-metadata-detect-step-2-instructions')->params( $user_options['mediawiki-template'] ) .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .
	
						'<legend>' . wfMessage('gwtoolset-metadata-mapping-legend') . '</legend>' .

						'<input type="hidden" name="gwtoolset-form" value="metadata-mapping"/>' .
						'<input type="hidden" name="metadata-file-url" value="' . $user_options['metadata-file-url'] . '"/>' .
						//'<input type="hidden" name="record-element-name" value="' . $user_options['record-element-name'] . '"/>' .
						//'<input type="hidden" name="mediawiki-template" id="gwtoolset-mediawiki-template" value="' . $user_options['mediawiki-template'] . '"/>' .
						//'<input type="hidden" name="metadata-mapping" id="gwtoolset-metadata-mapping" value="' . $mapping_name['mapping-name'] . '"/>' .
						//'<input type="hidden" name="wpEditToken" id="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
						'<input type="hidden" name="MAX_FILE_SIZE"  value="' . FileChecks::gwToolsetMaxUploadSize() . '">' .

						'<h3>' .
							wfMessage('gwtoolset-mediawiki-template')->params( $user_options['mediawiki-template'] ) .
							( !empty( $mapping_name['user-name'] ) ? ', ' . $mapping_name['user-name'] : null ) .
							( !empty( $mapping_name['mapping-name'] ) ? ' : ' . $mapping_name['mapping-name'] : null ) .
						'</h3>' .
						
						'<table id="template-table" style="float:left;width:33%;margin-right:2%;margin-bottom:1em;">' .
							'<thead>' .
								'<tr><th>template field</th><th>maps to</th></tr>' .
							'<thead>' .
							'<tbody>' .
								$metadata_selects . 
							'</tbody>' .
						'</table>' .
						'<table style="float:left; display: inline; width: 65%; overflow: auto;">' .
							'<thead>' .
								'<tr><th colspan="2">metadata’s example record’s contents</th></tr>' .
							'<thead>' .
							'<tbody style="vertical-align: top;">' .
								$metadata_as_table_rows . 
							'</tbody>' .
						'</table>' .

						//'<p style="clear:both;">' .
						//	'<label>' .
						//		wfMessage('gwtoolset-metadata-file') . ' : ' .
						//		'<input type="file" name="uploaded-metadata"' . FileChecks::getFileAcceptAttribute( Config::$accepted_types ) . '/>' .
						//	'</label><br/>' .
						//
						//	'<i>' .
						//		wfMessage( 'gwtoolset-accepted-file-types' ) . ' ' . FileChecks::getAcceptedExtensionsAsList( Config::$accepted_types ) . '<br/>' .
						//		wfMessage( 'upload-maxfilesize', number_format( FileChecks::gwToolsetMaxUploadSize() ) ) . ' bytes' .
						//	'</i>' .
						//'</p>' .

						'<p style="clear:both;">' . wfMessage('gwtoolset-metadata-file-url') . ' : ' . $user_options['metadata-file-url'] . '<br/><input type="submit" name="submit" value="process file"></p>' .

				'</fieldset>' .

			'</form>';

	}


}

