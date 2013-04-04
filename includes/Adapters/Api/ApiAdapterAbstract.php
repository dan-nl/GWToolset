<?php
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