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
namespace	GWToolset\Handlers;
use			Exception,
			GWToolset\Config,
			GWToolset\Helpers\FileChecks,
			GWToolset\MediaWiki\Api\Client,
			Php\File,
			Php\Filter,
			SpecialPage,
			UploadBase;


class FileHandler {


	/**
	 * @var Php\File
	 */
	protected $File;


	/**
	 * @var UploadBase
	 */
	protected $UploadBase;


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $MWApiClient;


	/**
	 * assumes that this title will be passed to UploadBase, which will validate
	 * the title and append the file extension for us
	 */
	protected function getTitle() {

		$title = $this->File->pathinfo['filename'] .  '-' . $this->SpecialPage->getUser()->getName();
		return $title;

	}


	/**
	 * @param {array} $user_options
	 * @return {string} a reference to the local file path
	 */
	public function retrieveLocalFilePath( $user_options = array(), $expected_key = null ) {

		global $wgServer, $IP;
		$file_name = null;

		if ( empty( $expected_key ) ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('no metadata-file-url key specified') );

		}

		if ( empty( $user_options[$expected_key] ) ) {

			throw new Exception( wfMessage('gwtoolset-metadata-file-url-not-present') );

		}

		FileChecks::isAcceptedFileExtension(
			$user_options[$expected_key],
			FileChecks::getAcceptedExtensions( Config::$accepted_types )
		);

		$this->MWApiClient = \GWToolset\getMWApiClient( $this->SpecialPage );

		$file_name = str_replace(
			array( $wgServer, 'index.php', '/', 'File:' ),
			'',
			$user_options[$expected_key]
		);

		$file_name = 'File:' . Filter::evaluate( $file_name );

		$api_result = $this->MWApiClient->query(
			array(
				'titles' => $file_name,
				'prop' => 'imageinfo',
				'iiprop' => 'url'
			)
		);

		if ( empty( $api_result['query']['pages'] ) || isset( $api_result['query']['pages'][-1] ) ) {

			throw new Exception( wfMessage('gwtoolset-metadata-file-url-invalid') );

		}

		foreach( $api_result['query']['pages'] as $page ) {

			if ( empty( $page['imageinfo'] )
				|| empty( $page['imageinfo'][0] )
				|| empty( $page['imageinfo'][0]['url'] )
			) {

				throw new Exception( wfMessage('gwtoolset-developer-issue')->params('api returned no imageinfo url') );

			}

			$result = $IP . str_replace( $wgServer, '', $page['imageinfo'][0]['url'] );
			break; // should only need to run through this once

		}

		if ( !file_exists( $result ) ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('api resolved file path does not exist') );

		}

		return $result;

	}


	public function getSavedFileName() {

		return $this->UploadBase->getTitle();

	}


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


	/**
	 * attempts to save the uploaded file to the wiki
	 *
	 * @return {array}
	 *   $result['msg'] {string}
	 *   $result['uploaded'] {boolean}
	 */
	public function saveFile() {

		$result = array( 'msg' => null, 'uploaded' => false );

			// UploadBase requires that $_FILES array contains the uploaded file in the key wpUploadFile
			$_FILES['wpUploadFile'] = $this->File->original_file_array;

			// UploadBase requires that the WebRequest is passed as variable
			$WebRequest = $this->SpecialPage->getRequest();

			// UploadBase uses the $_POST['wpDestFile'] value as a proposed filename
			$WebRequest->setVal( 'wpDestFile', $this->getTitle() );

			$this->UploadBase = UploadBase::createFromRequest( $WebRequest );
			$status = $this->uploadFile();

			if ( $status !== true ) {

				$result['msg'] = $status;

			} else {

				$result['msg'] = sprintf(
					wfMessage( 'gwtoolset-metadata-upload-successful' )->plain(),
					$this->UploadBase->getTitle()->escapeFullURL(),
					$this->UploadBase->getTitle()
				);

				$result['uploaded'] = true;

			}

		return $result;

	}


	/**
	 * adds to the wiki’s allowed extensions array, $wgFileExtensions so that
	 * UploadBase will accept them
	 */
	protected function addAllowedExtensions() {

		global $wgFileExtensions;

		foreach( array_keys( Config::$accepted_types ) as $accepted_extension ) {

			if ( !in_array( $accepted_extension, $wgFileExtensions ) ) {

				$wgFileExtensions[] = Filter::evaluate( $accepted_extension );

			}

		}

	}


	/**
	 * attempts to retrieve the filename given from the $_POST'ed form data and
	 * store it as an object in $this->File
	 *
	 * @param {string} $filename
	 */
	public function getUploadedFileFromForm( $filename = null ) {

		$this->File = new File( $filename );
		FileChecks::isUploadedFileValid( $this->File );
		$this->addAllowedExtensions();

	}


	public function __construct( SpecialPage &$SpecialPage ) {

		$this->SpecialPage = $SpecialPage;

	}


}