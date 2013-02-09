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
use 		Exception,
			UploadBase;


/**
 * a prototype handler used to experiment wiki functionality
 * nb: it may not work at times because of changes to the main code of the extension
 */
class PrototypeUploadHandler extends UploadHandler {


	/**
	 * @var UploadBase
	 */
	protected $UploadBase;


	/**
	 * Show the upload form with error message, but do not stash the file.
	 *
	 * @param string $msg
	 */
	protected function showUploadError( $msg ) {

		if ( is_array( $msg ) ) {

			$msg = 
			'<h3>' . wfMessage( 'uploadwarning' )->parse() . "</h3>" .
			'<pre class="error">' . print_r( $msg, true ) . "</pre>";

		} else {

			$msg = 
			'<h3>' . wfMessage( 'uploadwarning' )->parse() . "</h3>" .
			'<div class="error">' . $msg . "</div>";

		}
		

		throw new Exception( $msg );

	}


	/**
	 * upload the file
	 */
	protected function uploadFile() {

		$status = $this->UploadBase->performUpload( null, null, null, $this->SpecialPage->getUser() );

		if ( !$status->isGood() ) {

			$this->showUploadError( $this->SpecialPage->getOutput()->parse( $status->getWikiText() ) );

		}

	}


	/**
	 * Get the page text if this is not a reupload
	 */
	protected function getPageText() {

		$pageText = false;
		//$pageText = self::getInitialPageText( $this->mComment, $this->mLicense, $this->mCopyrightStatus, $this->mCopyrightSource );
		return $pageText;

	}
	
	/**
	 * Check warnings if necessary
	 */
	protected function checkWarnings() {

		$warnings = $this->UploadBase->checkWarnings();

		if ( !empty( $warnings ) ) {

			//$this->showUploadError( $warnings );

		}

	}


	/**
	 * Verify permissions for this title
	 * @todo: how to handle this when it errors out
	 */
	protected function verifyTitlePermissions() {

		$permErrors = $this->UploadBase->verifyTitlePermissions( $this->SpecialPage->getUser() );

		if ( $permErrors !== true ) {

			$code = array_shift( $permErrors[0] );
			$this->showUploadError( $this->SpecialPage->msg( $code, $permErrors[0] )->parse() );

		}

	}


	protected function overrideWgDisableUploadScriptChecks( $override = true ) {

		global $wgDisableUploadScriptChecks;

		if ( $override && !$wgDisableUploadScriptChecks ) {

			$wgDisableUploadScriptChecks = true;

		}

	}


	/**
	 *
	 */
	protected function verifyUpload() {

		$this->overrideWgDisableUploadScriptChecks(); // cannot upload xml without this

		$details = $this->UploadBase->verifyUpload();
		if ( $details['status'] == UploadBase::OK ) { return; }

		switch( $details['status'] ) {

			/** Statuses that only require name changing **/
			case UploadBase::MIN_LENGTH_PARTNAME:
				$this->showRecoverableUploadError( $this->SpecialPage->msg( 'minlength1' )->escaped() );
				break;

			case UploadBase::ILLEGAL_FILENAME:
				$this->showRecoverableUploadError( $this->SpecialPage->msg( 'illegalfilename', $details['filtered'] )->parse() );
				break;

			case UploadBase::FILENAME_TOO_LONG:
				$this->showRecoverableUploadError( $this->SpecialPage->msg( 'filename-toolong' )->escaped() );
				break;

			case UploadBase::FILETYPE_MISSING:
				$this->showRecoverableUploadError( $this->SpecialPage->msg( 'filetype-missing' )->parse() );
				break;

			case UploadBase::WINDOWS_NONASCII_FILENAME:
				$this->showRecoverableUploadError( $this->SpecialPage->msg( 'windows-nonascii-filename' )->parse() );
				break;

			/** Statuses that require reuploading **/
			case UploadBase::EMPTY_FILE:
				$this->showUploadError( $this->SpecialPage->msg( 'emptyfile' )->escaped() );
				break;

			case UploadBase::FILE_TOO_LARGE:
				$this->showUploadError( $this->SpecialPage->msg( 'largefileserver' )->escaped() );
				break;

			case UploadBase::FILETYPE_BADTYPE:
				global $wgFileExtensions;
				$msg = $this->SpecialPage->msg( 'filetype-banned-type' );
				if ( isset( $details['blacklistedExt'] ) ) {
					$msg->params( $this->SpecialPage->getLanguage()->commaList( $details['blacklistedExt'] ) );
				} else {
					$msg->params( $details['finalExt'] );
				}
				$msg->params( $this->SpecialPage->getLanguage()->commaList( $wgFileExtensions ),
					count( $wgFileExtensions ) );

				// Add PLURAL support for the first parameter. This results
				// in a bit unlogical parameter sequence, but does not break
				// old translations
				if ( isset( $details['blacklistedExt'] ) ) {
					$msg->params( count( $details['blacklistedExt'] ) );
				} else {
					$msg->params( 1 );
				}

				$this->showUploadError( $msg->parse() );
				break;

			case UploadBase::VERIFICATION_ERROR:
				unset( $details['status'] );
				$code = array_shift( $details['details'] );
				$this->showUploadError( $this->SpecialPage->msg( $code, $details['details'] )->parse() );
				break;

			case UploadBase::HOOK_ABORTED:
				if ( is_array( $details['error'] ) ) { # allow hooks to return error details in an array
					$args = $details['error'];
					$error = array_shift( $args );
				} else {
					$error = $details['error'];
					$args = null;
				}

				$this->showUploadError( $this->SpecialPage->msg( $error, $args )->parse() );
				break;

			default:
				throw new Exception( __METHOD__ . ": Unknown value `{$details['status']}`" );

		}

	}


	/**
	 * @todo: SpecialUpload->mCancelUpload with SpecialUpload->unsaveUploadedFile() ?
	 * @todo: SpecialUpload->mUpload->cleanupTempFile() ?
	 */
	protected function processUpload() {

		$result = null;
		$WebRequest = $this->SpecialPage->getRequest();
		
		// UploadBase requires the $_FILES array to contain the uploaded file
		// in the key wpUploadFile
		$_FILES['wpUploadFile'] = $this->File->original_file_array;

		$this->UploadBase = UploadBase::createFromRequest( $WebRequest );
		$this->verifyUpload();
		$this->verifyTitlePermissions();

		$local_file = $this->UploadBase->getLocalFile();

		$this->checkWarnings();
		$page_text = $this->getPageText();

		$this->uploadFile();

		$result .= '<h2>uploading file ...</h2>';
		$result .= '<pre>' . print_r($this->UploadBase, true) . '</pre>';

		return $result;

	}


}


