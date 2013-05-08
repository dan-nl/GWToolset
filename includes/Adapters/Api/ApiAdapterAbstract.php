<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Adapters\Api;
use GWToolset\Adapters\DataAdapterInterface,
	GWToolset\MediaWiki\Api\Client;


abstract class ApiAdapterAbstract implements DataAdapterInterface {


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $_MWApiClient;


	public function reset() {

		$this->_MWApiClient = null;

	}


	public function __construct( Client $MWApiClient ) {

		$this->reset();
		$this->_MWApiClient = $MWApiClient;

	}


}