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
use 		Exception;


/**
 * This class is designed to provide a simplified interface to cURL which maintains cookies.
 * @author Chris
 **/
class Curl {


	protected $curl;
	protected $curl_timeout;
	
	protected $cookiejar;
	protected $cookie_directory;
	protected $cookie_extension;
	protected $cookie_name;
	
	protected $debug_on;
	public $useragent;


	public function getCurlInfo() {

		return curl_getinfo( $this->curl );

	}


	protected function executeCurl() {

		$result = curl_exec( $this->curl );

		if ( curl_errno( $this->curl ) != 0 ) {

			$msg = 'cURL Error: ' . curl_error( $this->curl ) . ' (' . curl_errno( $this->curl ) . ')';
			if ( $this->debug_on ) { $msg .= '<pre>' . print_r( curl_getinfo( $this->curl ), true ) . '</pre>'; }
			throw new Exception( $msg );

		}

		return $result;
	
	}


	protected function setCurlOption( $option, $value ) {

		if ( !curl_setopt( $this->curl, $option, $value ) ) {

			throw new Exception('could not set cURL option [' . Filter::evaluate( $option ) . '] to value [' . Filter::evaluate( $value ) . ']');

		}

	}


	/**
	 * Sends a GET request
	 * @param string $url is the address of the page you are looking for
	 * @returns string the page you asked for
	 **/
	public function get( $url ) {

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, true );
		$this->setCurlOption( CURLOPT_MAXREDIRS, 10 );
		$this->setCurlOption( CURLOPT_HEADER, false );
		$this->setCurlOption( CURLOPT_HTTPGET, true );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, true );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, 15 );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );

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
		$this->setCurlOption( CURLOPT_MAXREDIRS, 10 );
		$this->setCurlOption( CURLOPT_HEADER, true );
		$this->setCurlOption( CURLOPT_NOBODY, true );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, true );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, 15 );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );

    //curl_setopt( $ch, CURLOPT_ENCODING, "" );
    //curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
    //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls

		return $this->executeCurl();

	}


	/**
	 * Sends a POST request
	 * @param string $url is the address of the page you are looking for
	 * @param array $data is the post data.
	 * @returns string the page you asked for
	 **/
	public function post( $url, $data ) {

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, true );
		$this->setCurlOption( CURLOPT_MAXREDIRS, 10 );
		$this->setCurlOption( CURLOPT_HEADER, false );
		$this->setCurlOption( CURLOPT_POST, true );
		$this->setCurlOption( CURLOPT_POSTFIELDS, $data );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, true );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, 15 );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );
		$this->setCurlOption( CURLOPT_HTTPHEADER, array( 'Expect:' ) );

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
		if ( isset( $options['debug-on'] ) ) { $this->debug_on = $options['debug-on']; }

	}


	public function reset() {

		$this->curl = null;
		$this->curl_timeout = 40;
		
		$this->cookiejar = null;
		$this->cookie_directory = '/tmp';
		$this->cookie_extension = '.dat';
		$this->cookie_name = 'http.cookie';
		
		$this->debug_on = false;
		$this->useragent = 'PHPcURL';

	}


	public function __destruct () {

		curl_close( $this->curl );

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

