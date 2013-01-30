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


class PrototypeApiForm {


	public static function getForm( IContextSource $Context ) {

		return
			'<h2>Prototype : API</h2>' .

			'<p>' .
				'this is a prototype form used to test metadata uploading and api interaction. the form accepts an xml file in a specific format. the extension will then do the following :' .
			'</p>' .

			'<ul>' .
				'<li>do its best to make sure the uploaded file is an xml file</li>' .
				'<li>perform a light validation on the xml schema</li>' .
				'<li>pull in the given filename via the specified url</li>' .
				'<li>save the file as a wiki page</li>' .
				'<li>populate a “custom” artwork template with the provided metadata</li>' .
			'</ul>' .

			'<form action="' . $Context->getTitle()->getFullURL() . '" method="post" enctype="multipart/form-data">' .

				'<fieldset>' .

					'<legend>' . wfMessage('gwtoolset-upload-legend') . '</legend>' .

					'<input type="hidden" name="gwtoolset-form" value="prototype-api"/>' .
					'<input type="hidden" name="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
					'<input type="hidden" name="MAX_FILE_SIZE"  value="' . FileChecks::gwToolsetMaxUploadSize() . '">' .

					'<label>' .
						wfMessage('gwtoolset-metadata-file') . ' : ' .
						'<input type="file" name="uploaded-metadata"' . FileChecks::getFileAcceptAttribute( Config::$accepted_types ) . '>' .
					'</label>' .

					'<p><i>' .
						wfMessage( 'gwtoolset-accepted-file-types' ) . ' ' . FileChecks::getAcceptedExtensionsAsList( Config::$accepted_types ) . '<br/>' .
						wfMessage( 'upload-maxfilesize', number_format( FileChecks::gwToolsetMaxUploadSize() ) ) . ' bytes' .
					'</i></p>' .

				'</fieldset>' .

				'<input type="submit" name="submit" value="' . wfMessage('emailusernamesubmit') . '">' .

			'</form>' .

			'<p>an example of the expected format</p>' .
			'<pre style="overflow:auto;">' .
				'&lt;?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
				'&lt;items>' . PHP_EOL .
				'  &lt;item>' . PHP_EOL .
				'    &lt;title>The Birth of Venus it|La nascita di Venere&lt;/title>' . PHP_EOL .
				'    &lt;medium>Oil on canvas&lt;/medium>' . PHP_EOL .
				'    &lt;dimensions>Length: 278.5 cm (109.6 in). Height: 172.5 cm (67.9 in).&lt;/dimensions>' . PHP_EOL .
				'    &lt;location>Florence&lt;/location>' . PHP_EOL .
				'    &lt;source>From Google Art Project|uffizi/the-birth-of-venus|zoom=6/8|compression_quality=Photoshop level 8&lt;/source>' . PHP_EOL .
				'    &lt;date>1469&lt;/date>' . PHP_EOL .
				'    &lt;artist>Creator:Sandra Botticelli&lt;/artist>' . PHP_EOL .
				'    &lt;url>http://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Sandro_Botticelli_-_La_nascita_di_Venere_-_Google_Art_Project.jpg/1280px-Sandro_Botticelli_-_La_nascita_di_Venere_-_Google_Art_Project.jpg&lt;/url>' . PHP_EOL .
				'  &lt;/item>' . PHP_EOL .
				'&lt;/items>' . PHP_EOL .
			'</pre>';

	}


}

