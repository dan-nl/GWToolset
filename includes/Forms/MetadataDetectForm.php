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
use 		GWToolset\Config,
			GWToolset\Helpers\FileChecks,
			GWToolset\Models\Mappings,
			GWToolset\Models\MediawikiTemplates,
			IContextSource;


class MetadataDetectForm {

	/**
	 * @todo: mediawiki templates need to come from a config setting or
	 * dynamic algorithm that indiactes which mw templates are accepted
	 */
	public static function getForm( IContextSource $Context ) {

		$mappings = new Mappings();
		$mediawiki_templates = new MediawikiTemplates();

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
							'<label>' .
								wfMessage('gwtoolset-record-element-name') . ' : ' .
								'<input type="text" name="record-element-name" value="" placeholder="record"/>' .
							'</label>' .
						'</li>' .

						'<li>' .
							'<label>' .
								wfMessage('gwtoolset-which-mediawiki-template') . ' : ' .
									$mediawiki_templates->getModelKeysAsSelect( 'mediawiki-template' ) .
							'</label>' .
						'</li>' .

						'<li>' .
							'<label>' .
								wfMessage('gwtoolset-which-metadata-mapping') . ' : ' .
								$mappings->getModelKeysAsSelect( 'metadata-mapping', null, true ) .
							'</label>' .
						'</li>' .

						'<li>' .
							wfMessage('gwtoolset-metadata-file-source') .
							'<ul>' .
								//'<li>' .
								//	'<label>' .
								//		wfMessage('gwtoolset-metadata-file-url') . ' : ' .
								//		'<input type="text" name="metadata-file-url" value="" placeholder="File:Nl.memorix.images.collection.xml"/>' .
								//	'</label>' .
								//'</li>' .
	
								'<li>' .
									'<label>' .
										wfMessage('gwtoolset-metadata-file') . ' : ' .
										'<input type="file" name="uploaded-metadata" ' . FileChecks::getFileAcceptAttribute( Config::$accepted_types ) . '>' .
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

				'<input type="submit" name="submit" value="' . wfMessage('emailusernamesubmit') . '">' .

			'</form>';

	}


}

