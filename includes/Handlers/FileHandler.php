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


	protected function setMWApiClient() {

		$this->MWApiClient = new Client( Config::$api_internal_endpoint, $this->SpecialPage );
		$this->MWApiClient->login( Config::$api_internal_lgname, Config::$api_internal_lgpassword );
		$this->MWApiClient->debug_html .= '<b>API Client - Logged in</b><br/>' . '<pre>' . print_r( $this->MWApiClient->Login, true ) . '</pre>';

	}


	/**
	 * @param {array} $user_options
	 * @return {string} a reference to the local file path
	 */
	public function retrieveLocalFilePath( $user_options = array(), $expected_key = null ) {

		global $wgServer, $IP;

		if ( empty( $expected_key ) ) {

			throw new Exception( wfMessage('gwtoolset-developer-issue')->params('no metadata-file-url key specified') );

		}

		if ( empty( $user_options[$expected_key] ) ) {

			throw new Exception( wfMessage('gwtoolset-metadata-file-url-not-present') );

		}

		$this->setMWApiClient();

		$api_result = $this->MWApiClient->query(
			array(
				'titles' =>
					'File:' .
					Filter::evaluate(
						str_replace(
							array( $wgServer, 'File:' ),
							'',
							$user_options[$expected_key]
						)
					),
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