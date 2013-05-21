<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\MediaWiki\Api;

use MWException,
	ReflectionClass,
	ReflectionProperty;


class UploadParams {

	/**
	 * Target filename
	 */
	public $filename;


	/**
	 * Upload comment. Also used as the initial page text for new files if text parameter not provided
	 */
	public $comment;


	/**
	 * Initial page text for new files.
	 */
	public $text;


	/**
	 * Edit token
	 */
	public $token;


	/**
	 * Watch the page
	 */
	public $watch;


	/**
	 * Ignore any warnings
	 */
	public $ignorewarnings;


	/**
	 * File contents
	 */
	public $file;


	/**
	 * Url to fetch the file from
	 */
	public $url;


	/**
	 * Session key returned by a previous upload that failed due to warnings, or (with httpstatus) The upload_session_key of an asynchronous upload
	 */
	public $sessionkey;


	protected $allowed_params;


	private function setAllowedParams() {

		$reflect = new ReflectionClass( $this );
		$reflect_properties = $reflect->getProperties( ReflectionProperty::IS_PUBLIC );

		foreach( $reflect_properties as $property ) {

			$this->allowed_params[] = $property->name;

		}

	}


	public function populate( array $params = array() ) {

		$msg = null;

		if ( empty( $params ) ) {

			$msg .= wfMessage( 'mw-api-client-no-params' )->plain();
			throw new MWException( $msg );

		}

		foreach( $params as $key => $value ) {

			if ( !in_array( $key, $this->allowed_params ) ) {

				$msg .= wfMessage( 'mw-api-client-not-valid-param' )->params( $key )->parse();
				throw new MWException( $msg );

			}

			$this->$key = $value;

		}

	}


	public function reset() {

		$this->filename = null;
		$this->comment = null;
		$this->text = null;
		$this->token = null;
		$this->watch = null;
		$this->ignorewarnings = null;
		$this->file = null;
		$this->url = null;
		$this->sessionkey = null;

		$this->setAllowedParams();

	}


	public function __construct( array $params = array() ) {

		$this->reset();
		$this->populate( $params );

	}


}