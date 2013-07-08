<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Helpers;
use GWToolset\Config,
	GWToolset\Exception,
	Php\File,
	Php\Filter,
	OutputPage;

/**
 * @todo: examine other checks in baseupload - detectVirus
 * @todo: examine other checks in baseupload - detectScript
 * @todo: examine other checks in baseupload - detectScriptInSvg
 * @todo: examine other checks in baseupload - mJavaDetected
 * @todo: take a look at how MimeMagic.php is working
 */
class FileChecks {

	public static $current_extension;

	/**
	 * @param \Php\File $File
	 * @return boolean
	 */
	public static function fileWasUploaded( File $File ) {
		if ( !$File->is_uploaded_file ) {
			throw new Exception( wfMessage( 'gwtoolset-improper-upload' )->escaped() );
		}

		return true;
	}

	/**
	 * @param array $accepted_types
	 * expected format 'extension' => array('mime/type','mime2/type2')
	 *
	 * @return array
	 */
	public static function getAcceptedExtensions( array &$accepted_types = array() ) {
		return array_keys( $accepted_types );
	}

	/**
	 * @param array $accepted_types
	 * expected format 'extension' => array('mime/type','mime2/type2')
	 *
	 * @return string
	 * a comma delimited list of accepted extensions
	 */
	public static function getAcceptedExtensionsAsList ( array &$accepted_types = array() ) {
		$result = null;

		if ( !empty( $accepted_types ) ) {
			$result = implode( ', ', self::getAcceptedExtensions( $accepted_types ) );
		}

		return $result;
	}

	/**
	 * @param array $accepted_types
	 * expected format 'extension' => array('mime/type','mime2/type2')
	 *
	 * @return array
	 */
	public static function getAcceptedMimeTypes( array &$accepted_types = array() ) {
		return array_unique( \GWToolset\getArraySecondLevelValues( $accepted_types ) );
	}


	/**
	 * returns an input file’s accept attribute with the supplied $accepted_types
	 *
	 * @param array $accepted_types
	 * expected format 'extension' => array('mime/type','mime2/type2')
	 *
	 * @return string
	 * a comma delimited list of accepted mime types
	 */
	public static function getFileAcceptAttribute( array &$accepted_types = array() ) {
		$result = null;

		if ( !empty( $accepted_types ) && Config::$use_file_accept_attribute ) {
			$result = 'accept="' . implode( ', ', self::getAcceptedMimeTypes( $accepted_types ) ) . '"';
		}

		return $result;
	}

	/**
	 * sets the max file upload size to the gwtoolset configured value
	 * if set or to the wiki’s setting
	 *
	 * @param null|string $forType
	 * @return int
	 */
	public static function gwToolsetMaxUploadSize( $forType = null ) {
		global $wgMaxUploadSize;

		if ( !empty( Config::$max_file_upload ) ) {
			return intval( Config::$max_file_upload );
		}

		if ( is_array( $wgMaxUploadSize ) ) {
			if ( $forType !== null && isset( $wgMaxUploadSize[$forType] ) ) {
				return $wgMaxUploadSize[$forType];
			} else {
				return $wgMaxUploadSize['*'];
			}
		} else {
			return intval( $wgMaxUploadSize );
		}
	}

	/**
	 * Validates the file extension based on the accepted extensions provided
	 *
	 * @param string|\Php\File $File
	 * @param array $accepted_extensions
	 * @throws Exception
	 * @return boolean
	 */
	public static function isAcceptedFileExtension( &$File, array $accepted_extensions = array() ) {
		$msg = null;
		$extension = null;

		if ( $File instanceof File ) {
			$extension = Filter::evaluate( strtolower( $File->pathinfo['extension'] ) );
		} else {
			$pathinfo = pathinfo( $File );

			if ( !isset( $pathinfo['extension'] ) ) {
				$msg = wfMessage( 'gwtoolset-unaccepted-extension' )->escaped();
			} else {
				$extension = Filter::evaluate( strtolower( $pathinfo['extension'] ) );
			}
		}

		if ( !isset( $extension ) || empty( $extension ) ) {
			$msg = wfMessage( 'gwtoolset-unaccepted-extension' )->escaped();
		}

		if ( $msg === null && !in_array( $extension, $accepted_extensions ) ) {
			$msg = wfMessage( 'gwtoolset-unaccepted-extension-specific', Filter::evaluate( $extension ) )->escaped();
		}

		if ( $msg !== null ) {
			throw new Exception( $msg );
		}

		self::$current_extension = $extension;
		return true;
	}

	/**
	 * @param \Php\File $File
	 * @param array $accepted_mime_types
	 * @throws Exception
	 * @return boolean
	 */
	public static function isAcceptedMimeType( File &$File, array $accepted_mime_types = array() ) {
		if ( !in_array( $File->mime_type, $accepted_mime_types ) ) {
			if ( 'xml' == self::$current_extension ) {
				throw new Exception( wfMessage( 'gwtoolset-unaccepted-mime-type-for-xml', Filter::evaluate( $File->mime_type ) )->parse() );
			} else {
				throw new Exception( wfMessage( 'gwtoolset-unaccepted-mime-type', Filter::evaluate( $File->mime_type ) )->escaped() );
			}
		}

		return true;
	}

	/**
	 * @param PHP\File $File
	 * @throws Exception
	 * @return boolean
	 */
	public static function isFileEmpty( File &$File ) {
		if ( $File->size === 0 ) {
			throw new Exception( wfMessage( 'gwtoolset-file-is-empty' )->escaped() );
		}

		return false;
	}

	/**
	 * test cases
	 *  - no file sent with the post
	 *  - empty file
	 *  - unaccepted extension
	 *  - bad extension
	 *  - js posing as xml
	 *
	 * @param {Php\File} $File
	 * @param {array} $accepted_types
	 * @throws Exception
	 * @return boolean
	 */
	public static function isUploadedFileValid( File $File, array $accepted_types = array() ) {
		if ( empty( $accepted_types ) ) {
			throw new Exception( wfMessage( 'gwtoolset-developer-issue' )->params( wfMessage( 'gwtoolset-no-accepted-types' )->escaped( 'gwtoolset-no-accepted-types-provided' ) )->parse() );
		}

		self::isFileEmpty( $File );
		self::noFileErrors( $File );
		self::fileWasUploaded( $File );
		self::isAcceptedFileExtension( $File, self::getAcceptedExtensions( $accepted_types ) );
		self::isAcceptedMimeType( $File, self::getAcceptedMimeTypes( $accepted_types ) );
		self::mimeTypeAndExtensionMatch( $File, $accepted_types );

		return true;
	}

	/**
	 * @param \Php\File $File
	 * @throws Exception
	 * @return boolean
	 */
	public static function mimeTypeAndExtensionMatch( File $File, array $accepted_types = array() ) {
		$msg = null;

		if ( !isset( $File->pathinfo['extension'] ) || empty( $File->pathinfo['extension'] ) ) {
			$msg = wfMessage( 'gwtoolset-unaccepted-extension' )->escaped();
		}

		if ( !in_array( $File->mime_type, $accepted_types[ $File->pathinfo['extension'] ] ) ) {
			$msg = wfMessage(
				'gwtoolset-mime-type-mismatch',
				Filter::evaluate( $File->pathinfo['extension'] ),
				Filter::evaluate( $File->mime_type )
			)->escaped();
		}

		if ( $msg !== null ) {
			throw new Exception( $msg );
		}

		return true;
	}

	/**
	 * @param Php\File $File
	 * @throws Exception
	 * @return boolean
	 */
	public static function noFileErrors( File &$File ) {
		$msg = null;

		switch ( $File->error ) {
			case UPLOAD_ERR_OK:
				break;

			case UPLOAD_ERR_INI_SIZE :
				$msg = wfMessage( 'gwtoolset-over-max-ini' )->escaped();
				break;

			case UPLOAD_ERR_FORM_SIZE :
				$msg = wfMessage( 'gwtoolset-over-max-file-size' )->escaped();
				break;

			case UPLOAD_ERR_PARTIAL :
				$msg = wfMessage( 'gwtoolset-partial-upload' )->escaped();
				break;

			case UPLOAD_ERR_NO_FILE :
				$msg = wfMessage( 'gwtoolset-no-file' )->escaped();
				break;

			case UPLOAD_ERR_NO_TMP_DIR :
				$msg = wfMessage( 'gwtoolset-missing-temp-folder' )->escaped();
				break;

			case UPLOAD_ERR_CANT_WRITE :
				$msg = wfMessage( 'gwtoolset-disk-write-failure' )->escaped();
				break;

			case UPLOAD_ERR_EXTENSION :
				$msg = wfMessage( 'gwtoolset-php-extension-error' )->escaped();
				break;
		}

		if ( $msg !== null ) {
			throw new Exception( $msg );
		}

		return true;
	}

}
