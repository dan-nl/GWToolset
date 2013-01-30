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
namespace GWToolset\MediaWiki\Api;


class Login {

	protected $lguserid;
	protected $lgusername;
	protected $lgtoken;
	protected $cookieprefix;
	protected $sessionid;


	public function reset() {

		$this->lguserid = null;
		$this->lgusername = null;
		$this->lgtoken = null;
		$this->cookieprefix = null;
		$this->sessionid = null;

	}


	public function __construct( array $login ) {

		$this->reset();

		$this->lguserid = $login['lguserid'];
		$this->lgusername = $login['lgusername'];
		$this->lgtoken = $login['lgtoken'];
		$this->cookieprefix = $login['cookieprefix'];
		$this->sessionid = $login['sessionid'];

	}


}

