<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\MediaWiki\Api;


interface ClientInterface {


	/**
	 * @link <https://www.mediawiki.org/wiki/API:Upload>
	 */
	public function upload( array $UploadParams = array() );


	/**
	 * @link <https://www.mediawiki.org/wiki/API:Edit>
	 */
	public function edit();


	/**
	 * The logout procedure deletes the login tokens and other browser cookies.
	 *
	 * @example api.php?action=logout
	 * @link <https://www.mediawiki.org/wiki/API:Logout>
	 * @return void
	 */
	public function logout();


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
	public function login( $lgname, $lgpassword, $lgdomain, $lgtoken );


	/**
	 * Resets class properties to the default values set within the method
	 * @return void
	 */
	public function reset();


}