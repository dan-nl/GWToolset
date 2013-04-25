<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @datetime 2012-12-01 11:05 gmt +1
 * @author dan entous pennlinepublishing.com
 * @copyright © 2012 dan entous
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 * based on Curl class developed by Chris G - http://en.wikipedia.org/wiki/User:Chris_G
 */
namespace	Php;
use Exception;


/**
 * This class is designed to provide a simplified interface to cURL which maintains cookies.
 * @author Chris
 **/
class Curl {


	protected $curl;
	protected $curl_timeout;
	protected $curl_connect_timeout;
	protected $max_redirects;

	protected $cookiejar;
	protected $cookie_directory;
	protected $cookie_extension;
	protected $cookie_name;

	protected $curl_info;
	protected $curl_error;
	protected $curl_errno;

	protected $debug_on;
	public $useragent;
	public $raw_header;


	public function rawHeaders( $ch, $header ) {

		$this->raw_header .= $header;
		return strlen( $header);

	}


	public function getCurlInfo() {

		return curl_getinfo( $this->curl );

	}


	protected function executeCurl() {

		$result = curl_exec( $this->curl );

			$this->curl_info = curl_getinfo( $this->curl );
			$this->curl_error = curl_error( $this->curl );
			$this->curl_errno = curl_errno( $this->curl );

			if ( $this->curl_errno != 0 ) {

				$msg = 'cURL Error: ' . $this->curl_error . ' (' . $this->curl_errno . ')';
				if ( $this->debug_on ) { $msg .= '<pre>' . print_r( $this->curl_info, true ) . '</pre>'; }
				throw new Exception( $msg );

			}

		return $result;

	}


	protected function setCurlOption( $option, $value ) {

		if ( !curl_setopt( $this->curl, $option, $value ) ) {

			throw new Exception('could not set cURL option [' . Filter::evaluate( $option ) . '] to value [' . Filter::evaluate( $value ) . ']');

		}

	}
	
	
	protected function isUrlValid( &$url ) {

		if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {

			throw new Exception('invalid url : [' . Filter::evaluate( $url ) . ']');

		}

		return true;

	}


	/**
	 * Sends a GET request
	 * @param string $url is the address of the page you are looking for
	 * @returns string the page you asked for
	 **/
	public function get( $url ) {

		$this->isUrlValid( $url );

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, true );
		$this->setCurlOption( CURLOPT_MAXREDIRS, $this->max_redirects );
		$this->setCurlOption( CURLOPT_HEADER, false );
		$this->setCurlOption( CURLOPT_HTTPGET, true );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, true );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );

		return $this->executeCurl();

	}


	/**
	 * Sends a GET request
	 * @param string $url is the address of the page you are looking for
	 * @returns string the page you asked for
	 **/
	public function getHeadersOnly( $url ) {

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, true );
		$this->setCurlOption( CURLOPT_MAXREDIRS, $this->max_redirects );
		$this->setCurlOption( CURLOPT_HEADER, true );
		$this->setCurlOption( CURLOPT_HEADERFUNCTION, array( $this, 'rawHeaders' ) );
		$this->setCurlOption( CURLOPT_NOBODY, true );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, true );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );

		return $this->executeCurl();

	}


	/**
	 * Sends a POST request
	 * @param string $url is the address of the page you are looking for
	 * @param array $data is the post data.
	 * @returns string the page you asked for
	 **/
	public function post( $url, array $data = array() ) {

		$this->isUrlValid( $url );

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, true );
		$this->setCurlOption( CURLOPT_MAXREDIRS, $this->max_redirects );
		$this->setCurlOption( CURLOPT_HEADER, false );
		$this->setCurlOption( CURLOPT_POST, true );
		$this->setCurlOption( CURLOPT_POSTFIELDS, $data );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, true );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );
		$this->setCurlOption( CURLOPT_HTTPHEADER, array( 'Expect:' ) );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );

		return $this->executeCurl();

	}


	protected function createCookie() {

		if ( !file_exists( $this->cookie_directory ) ) {

			throw new Exception( wfMessage('mw-api-client-curl-no-cookie-directory')->plain() );

		}

		$this->cookiejar = $this->cookie_directory . '/' . $this->cookie_name . '.' . dechex( rand( 0,99999999 ) ) . $this->cookie_extension;

		if ( !touch( $this->cookiejar ) ) {

			throw new Exception( wfMessage('mw-api-client-curl-no-cookie-create')->plain() );

		}

		chmod( $this->cookiejar, 0600 );
		curl_setopt( $this->curl, CURLOPT_COOKIEJAR, $this->cookiejar );
		curl_setopt( $this->curl, CURLOPT_COOKIEFILE, $this->cookiejar );

	}


	/**
	 * @todo: validate options array
	 */
	protected function setClassProperties( array &$options ) {

		if ( empty( $options ) ) { return; }

		if ( isset( $options['useragent'] ) ) { $this->useragent = $options['useragent']; }
		if ( isset( $options['cookie-directory'] ) ) { $this->cookie_directory = $options['cookie-directory']; }
		if ( isset( $options['cookie-extension'] ) ) { $this->cookie_extension = $options['cookie-extension']; }
		if ( isset( $options['cookie-name'] ) ) { $this->cookie_name = $options['cookie-name']; }
		if ( isset( $options['curl-timeout'] ) ) { $this->curl_timeout = (int) $options['curl-timeout']; }
		if ( isset( $options['curl-connect-timeout'] ) ) { $this->curl_connect_timeout = (int) $options['curl-connect-timeout']; }
		if ( isset( $options['max-redirects'] ) ) { $this->max_redirects = (int) $options['max-redirects']; }
		if ( isset( $options['debug-on'] ) ) { $this->debug_on = $options['debug-on']; }

	}


	public function reset() {

		$this->curl = null;
		$this->curl_timeout = 60;
		$this->curl_connect_timeout = 30;
		$this->max_redirects = 10;

		$this->cookiejar = null;
		$this->cookie_directory = '/tmp';
		$this->cookie_extension = '.dat';
		$this->cookie_name = 'http.cookie';

		$this->curl_info = array();
		$this->curl_error = null;
		$this->curl_errno = 0;

		$this->debug_on = false;
		$this->useragent = 'PHPcURL';

	}


	public function __destruct () {

		if ( is_resource( $this->curl ) ) {

			curl_close( $this->curl );

		}

		if ( file_exists( $this->cookiejar ) ) {

			unlink( $this->cookiejar );

		}

	}


	/**
	 * @param array $options
	 * useragent, cookie_directory, cookie_extension, cookie_name
	 */
	public function __construct( array $options = array() ) {

		$this->reset();
		$this->setClassProperties( $options );
		$this->curl = curl_init();

		if ( !$this->curl ) {

			throw new Exception( wfMessage('mw-api-client-curl-no-handle-create') );

		}

		$this->createCookie();

	}


}