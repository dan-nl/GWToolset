<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Helpers;
use Exception,
	GWToolset\Config,
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
	 * File names may be up to 240 bytes long
	 *
	 * @see https://commons.wikimedia.org/wiki/Commons:File_naming
	 * @see http://en.wikipedia.org/wiki/Wikipedia:Naming_conventions_(technical_restrictions)
	 * @see http://www.mediawiki.org/wiki/Help:Bad_title
	 */
	public static function getValidTitle( $title, $replacement = '-' ) {

		if ( strlen( $title ) > 220 ) {

			throw new Exception( wfMessage( 'gwtoolset-title-too-long', Filter::evaluate( $title ) )->plain() );
			$title = mb_strcut( $title, 0, 220, 'UTF-8' );

		}

		return str_replace( array( '#','<','>','[',']','|','{','}',':','¬','`','!','"','£','$','^','&','*','(',')','+','=','~','?','/',',',Config::$metadata_separator,';',"'",'@' ), $replacement, $title );

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

			if ( !is_null( $forType ) && isset( $wgMaxUploadSize[$forType] ) ) {

				return $wgMaxUploadSize[$forType];

			} else {

				return $wgMaxUploadSize['*'];

			}

		} else {

			return intval( $wgMaxUploadSize );

		}

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

		if ( Config::$use_file_accept_attribute ) {

			return 'accept="' . \GWToolset\getArrayAsList( self::getAcceptedMimeTypes( $accepted_types ) ) . '"';

		}

	}


	/**
	 * @param array $accepted_types
	 * expected format 'extension' => array('mime/type','mime2/type2')
	 *
	 * @return string
	 * a comma delimited list of accepted extensions
	 */
	public static function getAcceptedExtensionsAsList ( array &$accepted_types = array() ) {

		return \GWToolset\getArrayAsList( self::getAcceptedExtensions( $accepted_types ) );

	}


	/**
	 * @param \Php\File $File
	 * @throws Exception
	 * @return boolean
	 */
	public static function mimeTypeAndExtensionMatch( File $File, array $accepted_types = array() ) {

		$msg = null;

		if ( !isset( $File->pathinfo['extension'] ) || empty( $File->pathinfo['extension'] ) ) {

			$msg = wfMessage( 'gwtoolset-unaccepted-extension' )->plain();

		}

		if ( !in_array( $File->mime_type, $accepted_types[ $File->pathinfo['extension'] ] ) ) {

			$msg = wfMessage(
				'gwtoolset-mime-type-mismatch',
				Filter::evaluate( $File->pathinfo['extension'] ),
				Filter::evaluate( $File->mime_type )
			)->plain();

		}

		if ( !is_null( $msg ) ) {

			throw new Exception( $msg );

		}

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

				throw new Exception( wfMessage( 'gwtoolset-unaccepted-mime-type-for-xml', Filter::evaluate( $File->mime_type ) )->plain() );

			} else {

				throw new Exception( wfMessage( 'gwtoolset-unaccepted-mime-type', Filter::evaluate( $File->mime_type ) )->plain() );

			}

		}

		return true;

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

				$msg = wfMessage( 'gwtoolset-unaccepted-extension' )->plain();

			} else {

				$extension = Filter::evaluate( strtolower( $pathinfo['extension'] ) );

			}

		}

		if ( !isset( $extension ) || empty( $extension ) ) {

			$msg = wfMessage( 'gwtoolset-unaccepted-extension' )->plain();

		}

		if ( is_null( $msg ) && !in_array( $extension, $accepted_extensions ) ) {

			$msg = wfMessage( 'gwtoolset-unaccepted-extension-specific', Filter::evaluate( $extension ) )->plain();

		}

		if ( !is_null( $msg ) ) {

			throw new Exception( $msg );

		}

		self::$current_extension = $extension;
		return true;

	}


	/**
	 * @param \Php\File $File
	 * @return boolean
	 */
	public static function fileWasUploaded( File $File ) {

		if ( !$File->is_uploaded_file ) {

			throw new Exception( wfMessage( 'gwtoolset-improper-upload' )->plain() );

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

			case UPLOAD_ERR_OK: break;
			case UPLOAD_ERR_INI_SIZE :  $msg = wfMessage( 'gwtoolset-over-max-ini' )->plain(); break;
			case UPLOAD_ERR_FORM_SIZE : $msg = wfMessage( 'gwtoolset-over-max-file-size' )->plain(); break;
			case UPLOAD_ERR_PARTIAL : $msg = wfMessage( 'gwtoolset-partial-upload' )->plain(); break;
			case UPLOAD_ERR_NO_FILE : $msg = wfMessage( 'gwtoolset-no-file' )->plain(); break;
			case UPLOAD_ERR_NO_TMP_DIR : $msg = wfMessage( 'gwtoolset-missing-temp-folder' )->plain(); break;
			case UPLOAD_ERR_CANT_WRITE : $msg = wfMessage( 'gwtoolset-disk-write-failure' )->plain(); break;
			case UPLOAD_ERR_EXTENSION : $msg = wfMessage( 'gwtoolset-php-extension-error' )->plain(); break;

		}

		if ( !is_null( $msg ) ) {

			throw new Exception( $msg );

		} else {

			return true;

		}

	}


	/**
	 * @param PHP\File $File
	 * @throws Exception
	 * @return boolean
	 */
	public static function isFileEmpty( File &$File ) {

		if ( $File->size === 0 ) {

			throw new Exception( wfMessage( 'gwtoolset-file-is-empty' )->plain() );

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
	 * @param PHP\File $File
	 * @return boolean
	 */
	public static function isUploadedFileValid( File &$File ) {

		self::isFileEmpty( $File );
		self::noFileErrors( $File );
		self::fileWasUploaded( $File );
		self::isAcceptedFileExtension( $File, self::getAcceptedExtensions( Config::$accepted_types ) );
		self::isAcceptedMimeType( $File, self::getAcceptedMimeTypes( Config::$accepted_types ) );
		self::mimeTypeAndExtensionMatch( $File, Config::$accepted_types );

		return true;

	}


}