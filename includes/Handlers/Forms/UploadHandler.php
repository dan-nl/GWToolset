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
			Php\Filter,
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


	protected function addAllowedExtensions() {

		global $wgFileExtensions;

		foreach( array_keys( Config::$accepted_types ) as $accepted_extension ) {

			if ( !in_array( $accepted_extension, $wgFileExtensions ) ) {

				$wgFileExtensions[] = Filter::evaluate( $accepted_extension );

			}

		}

	}


	public function execute() {

		$result = null;

			WikiChecks::doesEditTokenMatch( $this->SpecialPage );

			$this->File = new File( 'uploaded-metadata' );
			FileChecks::isUploadedFileValid( $this->File );
			$this->addAllowedExtensions();

			$result .= $this->processUpload();

		return $result;

	}


	public function __construct( SpecialPage &$SpecialPage ) {

		$this->SpecialPage = $SpecialPage;

	}


}