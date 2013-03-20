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
use GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	IContextSource;


class BaseUploadForm {


	public static function getForm( IContextSource $Context ) {

		return
			'<h2>Upload Form</h2>' .
			'<p>testing the results of uploading certiain file formats directly; e.g., xml, csv' .
			
			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .

					'<legend>' . wfMessage('gwtoolset-upload-legend') . '</legend>' .

					'<input type="hidden" name="gwtoolset-form" value="base-upload"/>' .
					'<input type="hidden" name="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
					'<input type="hidden" name="MAX_FILE_SIZE"  value="' . FileChecks::gwToolsetMaxUploadSize() . '">' .

					'<ol>' .

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

					'</ol>' .

				'</fieldset>' .

				'<input type="submit" name="submit" value="' . wfMessage('emailusernamesubmit') . '">' .

			'</form>';

	}


}