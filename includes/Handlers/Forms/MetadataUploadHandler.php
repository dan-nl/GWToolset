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
			LocalFile,
			Php\File,
			UploadBase;


class MetadataUploadHandler extends UploadHandler {


	/**
	 * @var UploadBase
	 */
	protected $UploadBase;


	/**
	 * upload the file
	 */
	protected function uploadFile() {

		$status = $this->UploadBase->performUpload( null, null, null, $this->SpecialPage->getUser() );

		if ( !$status->isGood() ) {

			return $this->SpecialPage->getOutput()->parse( $status->getWikiText() );

		}

		return true;

	}


	protected function processUpload() {

		$result = null;

			// UploadBase requires that $_FILES array to contain the uploaded file in the key wpUploadFile
			$_FILES['wpUploadFile'] = $this->File->original_file_array;

			// UploadBase requires that the WebRequest is a variable
			$WebRequest = $this->SpecialPage->getRequest();

			$this->UploadBase = UploadBase::createFromRequest( $WebRequest );
			$status = $this->uploadFile();

			if ( $status !== true ) {

				$result = $status;

			} else {

				$result = sprintf(
					wfMessage( 'gwtoolset-metadata-upload-successful' )->plain(),
					$this->UploadBase->getTitle()->escapeFullURL(),
					$this->UploadBase->getTitle()
				);

			}

		return $result;

	}



}