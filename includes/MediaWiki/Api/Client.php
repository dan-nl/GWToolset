<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\MediaWiki\Api;
use Exception,
	GWToolset\MediaWiki\Api\Login,
	Php\Curl,
	Php\Filter;

class Client implements ClientInterface {

	/**
	 * @var string
	 */
	public $debug_html;

	/**
	 * @var string
	 */
	public $endpoint;

	/**
	 * @var GWToolset\MediaWiki\Api\Login
	 * potential future reuse
	 */
	public $Login;

	/**
	 * @var string
	 */
	public $useragent;

	/**
	 * @var Php\Curl
	 */
	protected $_Curl;

	/**
	 * @var String
	 * the format of the output
	 * default : xmlfm
	 * valid values : json, jsonfm, php, phpfm, wddx, wddxfm, xml, xmlfm, yaml, yamlfm, rawfm, txt, txtfm, dbg, dbgfm, dump, dumpfm
	 */
	private $valid_formats;

	/**
	 * @var String
	 * The API Module you would like to use in order to perform the a specific API action
	 *
	 * default : help
	 * valid values : login, logout, query, expandtemplates, parse, opensearch, feedcontributions, feedwatchlist, help,
	 * paraminfo, rsd, compare, tokens, purge, rollback, delete, undelete, protect, block, unblock, move,
	 * edit, upload, filerevert, emailuser, watch, patrol, import, userrights, options
	 */
	private $valid_modules;

	public function __construct( $endpoint = null, $user_name = null, array $curl_options = array() ) {
		if ( empty( $endpoint ) ) {
			global $wgServer;
			$endpoint = $wgServer . '/api.php';
		}

		if ( empty( $user_name ) ) {
			$user_name = 'gwtoolset';
		}

		$this->reset();
		$this->endpoint = $endpoint;
		$this->useragent = 'PHPcURL : ' . $user_name;
		$curl_options['useragent'] = $this->useragent;
		$this->_Curl = new Curl( $curl_options );
	}

	private function buildQueryString( array $params = array() ) {
		$query_string = null;

		foreach ( $params as $key => $value ) {
			// @todo: check for valid key/value pairs
			$query_string .= '&' . urlencode( $key ) . '=' . urlencode( $value );
		}

		return $query_string;
	}

	/**
	 * apiCall
	 *
	 * @todo: deal with filtering $result $value that is an array
	 *
	 * @param string $module
	 * @param array $params
	 * @throws Exception
	 * @return $result
	 */
	public function apiCall( $module, array $params = array() ) {
		$msg = null;
		$data = null;
		$result = null;
		$method = 'get';

		$url = $this->endpoint . '?action=' . urlencode( $module ) . '&format=php';
		$this->debug_html .= '<b>API Call - endpoint</b><pre>' . $this->endpoint . '</pre>';
		$this->debug_html .= '<b>API Call - URL</b><pre>' . $url . '</pre>';

		if ( !array_key_exists( $module, $this->valid_modules ) ) {
			throw new Exception( wfMessage( 'mw-api-client-invalid-module' )->rawParams( Filter::evaluate( $module ) )->parse() );
		}

		if ( isset( $this->valid_modules[$module]['method'] ) && $this->valid_modules[$module]['method'] == 'post' ) {
			$method = 'post';
		}

		if ( $method == 'post' ) {
			$this->debug_html .= '<b>API Call - POST</b><pre>module : ' . $module . PHP_EOL . print_r( $params, true ) . '</pre>';
			$data = $this->_Curl->post( $url, $params );
			$this->debug_html .= '<b>API Call - data</b><pre>' . $data . '</pre>';
		} else {
			$this->debug_html .= '<b>API Call - GET</b><pre>module : ' . $module . PHP_EOL . print_r( $params, true ) . '</pre>';
			$data = $this->_Curl->get( $url . $this->buildQueryString( $params ) );
			$this->debug_html .= '<b>API Call - data</b><pre>' . $data . '</pre>';
		}

		// @link <http://stackoverflow.com/questions/1369936/check-to-see-if-a-string-is-serialized#answer-1369946>
		$result = @unserialize( $data );

		if ( $data === 'b:0;' || $result !== false ) {
			if ( !empty( $result['error'] ) ) {
				foreach( $result['error'] as $key => $value ) {
					$key = Filter::evaluate( strval( $key ) );

					if ( !is_array( $value ) ) {
						$value = Filter::evaluate( $value );
					}

					switch( $key ) {
						case 'code':
							$msg .= 'Error Code : ' . $value . '<br />';
							switch ( $value ) {
								case 'internal-error' :
									$msg .=	wfMessage( 'mw-api-client-internal-error' )->parse();
									break;

								case 'permissiondenied' :
									$msg .= wfMessage( 'mw-api-client-permissiondenied' )->parse();
									break;
							}
							break;

						case 'info':
							$msg .= 'Error Info : ' . $value . '<br />';
							break;

						case 'details':
							foreach( $value as $detail ) {
								$msg .= 'Error Detail : ' . $detail . '<br />';
							}
							break;

						default:
							if ( is_array( $value ) ) {
								$msg .= ' Additional Info : <pre>' . print_r( $value, true ) . '</pre>';
							} else {
								$msg .= ' Additional Info : ' . $value . '<br />';
							}
							break;
					}
				}
			}

			$this->debug_html .= '<b>API Call - RESULT</b><pre>' . print_r( $result, true ) . '</pre>';

			if ( !empty( $msg ) ) {
				if ( ini_get('display_errors') ) {
					$msg .= '<pre>' . print_r( $params ,true ) . '</pre>';
				}
				throw new Exception( $msg );
			}

			return $result;
		} else {
			throw new Exception( wfMessage( 'mw-api-client-api-response-is-not-serializable' )->parse() . wfMessage( 'mw-api-client-troubleshooting-tips' )->plain() );
		}
	}


	/**
	 * getEditToken
	 *
	 * @access private
	 */
	public function getEditToken() {
		$result = $this->apiCall( 'tokens', array( 'type' => 'edit' ) );

		if ( !isset( $result['tokens'] )
			|| !isset( $result['tokens']['edittoken'] )
			|| empty( $result['tokens']['edittoken'] )
		) {
			$msg = wfMessage( 'mw-api-client-no-edit-token' )->plain();

			if ( isset( $result['warnings'] ) && isset( $result['warnings']['tokens'] ) && isset( $result['warnings']['tokens']['*'] ) ) {
				$msg = $result['warnings']['tokens']['*'];
			}

			throw new Exception( $msg );
		}

		return $result['tokens']['edittoken'];
	}

	/**
	 * accepts an array with with the following parameters :
	 * filename - Target filename
	 * comment - Upload comment. Also used as the initial page text for new files if text parameter not provided
	 * text - Initial page text for new files.
	 * token - Edit token.
	 * watch - Watch the page.
	 * ignorewarnings - Ignore any warnings.
	 * file - File contents.
	 * url - Url to fetch the file from.
	 * sessionkey - Session key returned by a previous upload that failed due to warnings, or (with httpstatus) The upload_session_key of an asynchronous upload
	 *
	 *
	 * There are three methods of uploading file via the API
	 *
	 * 1. Uploading a local file directly
	 * 2. Uploading a local file in chunks using Firefogg chunked upload protocol
	 *    @link <http://firefogg.org/dev/chunk_post.html>
	 * 3. Uploading a copy of a file elsewhere on the Web given by a URL
	 *
	 * All of these methods require an account with the “upload” right.
	 *
	 * To upload files, an edit token is required and is the same regardless of target filename,
	 * but changes at every login. Unlike other tokens, it cannot be obtained directly,
	 * so one must obtain and use an edit token instead.
	 * @link <http://www.mediawiki.org/wiki/Manual:Edit_token>
	 *
	 * Requirements
	 *   - uploads must be enabled in LocalSettings.php $wgEnableUploads = true;
	 *   - must be logged in
	 *   - must have a valid edit token
	 *   - filename must be specified
	 *   - make sure /core/images directory is owned by the web server so that it can create sub-directories and write files to it
	 *
	 * Notes
	 *   - if you ignorewarnings the upload will add a “newer” version of the upload and mark it as current
	 *   - just adding the ignorewarnings parameter to the parameter array counts as a true value, even if it is set to true or null
	 *
	 * @link <https://www.mediawiki.org/wiki/API:Upload>
	 */
	public function upload( array $params = array() ) {
		return $this->apiCall( 'upload', $params );
	}

	public function query( array $params = array() ) {
		return $this->apiCall( 'query', $params );
	}


	/**
	 * @link <https://www.mediawiki.org/wiki/API:Edit>
	 */
	public function edit( array $params = array() ) {
		return $this->apiCall( 'edit', $params );
	}


	public function logout() {
		// expects an empty array on return so if something else is returned there has been a problem
		if ( $this->apiCall( 'logout' ) ) {
			throw new Exception( wfMessage( 'mw-api-client-no-logout' )->plain() );
		}

		$this->Login = null;
	}


	/**
	 * Log in and get the authentication tokens. In the event of a successful log-in,
	 * a cookie will be attached to your session. In the event of a failed log-in,
	 * you will not be able to attempt another log-in through this method for 5 seconds.
	 * This is to prevent password guessing by automated password crackers.
	 *
	 * This module only accepts POST requests
	 * In MediaWiki 1.15.3+, you must confirm the login by resubmitting the login request with the token returned.
	 *
	 * @example api.php?action=login&lgname=user&lgpassword=password
	 * @link <https://www.mediawiki.org/wiki/API:Login>
	 * @param {String} $lgname User Name
	 * @param {String} $lgpassword Password
	 * @param {String} $lgdomain Domain (optional)
	 * @param {String} $lgtoken Login token obtained in first request
	 * @return boolean
	 */
	public function login( $lgname, $lgpassword, $lgdomain = null, $lgtoken = null ) {
		$msg = null;
		$result = null;

		$post_values = array(
			'lgname' => $lgname,
			'lgpassword' => $lgpassword,
			'lgdomain' => $lgdomain,
			'lgtoken' => $lgtoken
		);

		$errors = array(
			'NoName' => wfMessage( 'mw-api-client-NoName' )->parse(),
			'Illegal' => wfMessage( 'mw-api-client-Illegal' )->plain(),
			'NotExists' => wfMessage( 'mw-api-client-NotExists' )->parse(),
			'EmptyPass' => wfMessage( 'mw-api-client-EmptyPass' )->parse(),
			'WrongPass' => wfMessage( 'mw-api-client-WrongPass' )->parse(),
			'WrongPluginPass' => wfMessage( 'mw-api-client-WrongPluginPass' )->parse(),
			'CreateBlocked' => wfMessage( 'mw-api-client-CreateBlocked' )->plain(),
			'Throttled' => wfMessage( 'mw-api-client-Throttled' )->parse(),
			'Blocked' => wfMessage( 'mw-api-client-Blocked' )->plain(),
			'mustbeposted' => wfMessage( 'mw-api-client-mustbeposted' )->parse(),
			'NeedToken' => wfMessage( 'mw-api-client-NeedToken' )->parse()
		);

		if ( $this->Login ) {
			throw new Exception('mw-api-client-already-logged-in');
		}

		$result = $this->apiCall( 'login', $post_values );

		if ( empty( $result['login']['result'] ) ) {
			$msg .= wfMessage( 'mw-api-client-could-not-log-in' )->plain() . ' ' . wfMessage( 'mw-api-client-troubleshooting-tips' )->parse();
		} elseif ( empty( $msg ) && $result['login']['result'] == 'NeedToken' ) {
			if ( empty( $result['login']['token'] ) ) {
				$msg .= wfMessage( 'mw-api-client-no login-token-received' )->plain() . ' ' . wfMessage( 'mw-api-client-troubleshooting-tips' )->parse();
			}

			if ( empty( $msg ) ) {
				$this->login( $lgname, $lgpassword, $lgdomain, $result['login']['token'] );
			}
		} elseif ( $result['login']['result'] != 'Success' ) {
			$msg .= 'Login Error Code : ' . $result['login']['result'] . '<br />';

			if ( isset( $errors[$result['login']['result']] ) ) {
				$msg .= ' ' . $errors[$result['login']['result']];
			}

			$msg .= '<br />';
		} else {
			$this->Login = new Login( $result['login'] );
		}

		if ( !empty( $msg ) ) {
			throw new Exception( $msg );
		}

		return true;
	}

	public function reset() {
		$this->_Curl = null;
		$this->debug_html = null;
		$this->endpoint = null;
		$this->Login = null;
		$this->useragent = null;

		$this->valid_formats = array(
			'dbg',
			'dbgfm',
			'dump',
			'dumpfm',
			'json',
			'jsonfm',
			'php',
			'phpfm',
			'rawfm',
			'txt',
			'txtfm',
			'wddx',
			'wddxfm',
			'xml',
			'xmlfm',
			'yaml',
			'yamlfm'
		);

		$this->valid_modules = array(
			'block' => array( 'method' => 'get' ),
			'compare' => array( 'method' => 'get' ),
			'delete' => array( 'method' => 'get' ),
			'edit' => array( 'method' => 'post' ),
			'emailuser' => array( 'method' => 'get' ),
			'expandtemplates' => array( 'method' => 'get' ),
			'feedcontributions' => array( 'method' => 'get' ),
			'feedwatchlist' => array( 'method' => 'get' ),
			'filerevert' => array( 'method' => 'get' ),
			'help' => array( 'method' => 'get' ),
			'import' => array( 'method' => 'get' ),
			'login' => array( 'method' => 'post' ),
			'logout' => array( 'method' => 'get' ),
			'move' => array( 'method' => 'get' ),
			'opensearch' => array( 'method' => 'get' ),
			'options' => array( 'method' => 'get' ),
			'parse' => array( 'method' => 'get' ),
			'patrol' => array( 'method' => 'get' ),
			'paraminfo' => array( 'method' => 'get' ),
			'protect' => array( 'method' => 'get' ),
			'purge' => array( 'method' => 'get' ),
			'query' => array( 'method' => 'get' ),
			'rollback' => array( 'method' => 'get' ),
			'rsd' => array( 'method' => 'get' ),
			'tokens' => array( 'method' => 'get' ),
			'unblock' => array( 'method' => 'get' ),
			'undelete' => array( 'method' => 'get' ),
			'upload' => array( 'method' => 'post' ),
			'userrights' => array( 'method' => 'get' ),
			'watch' => array( 'method' => 'get' )
		);
	}

}
