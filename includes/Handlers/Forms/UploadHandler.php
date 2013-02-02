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
namespace	GWToolset\Handlers\Forms;
use			Exception,
			GWToolset\Config,
			GWToolset\Helpers\FileChecks,
			GWToolset\Helpers\WikiChecks,
			Php\File,
			SpecialPage;


abstract class UploadHandler extends FormHandler {


	/**
	 * @var Php\File
	 */
	protected $File;


	/**
	 * implemented in child definition
	 */
	abstract protected function processUpload();


	public function execute() {

		$result = null;

		try {

			if ( !isset( $_FILES['uploaded-metadata'] ) ) {

				throw new Exception( wfMessage( 'gwtoolset-no-file' ) );

			}

			WikiChecks::doesEditTokenMatch( $this->SpecialPage );
			$result .= $this->processUpload();

		} catch ( Exception $e ) {

			if ( $e->getCode() == 1000 ) {

				throw new Exception( $e->getMessage(), 1000 );

			} else {

				$result .= 
					'<h2>' . wfMessage( 'gwtoolset-file-not-valid' ) . '</h2>' .
					'<p class="error">' . $e->getMessage() . '</p>' .
					'<a href="/Special:GWToolset?gwtoolset-form=' . $this->SpecialPage->module_key . '">' . wfMessage( 'gwtoolset-back-to-form' ) . '</a>';

			}

		}

		return $result;

	}


	public function __construct( SpecialPage &$SpecialPage ) {

		$this->SpecialPage = $SpecialPage;

	}


}