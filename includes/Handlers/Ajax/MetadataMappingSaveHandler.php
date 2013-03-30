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
namespace GWToolset\Handlers\Ajax;
use	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate;


class MetadataMappingSaveHandler extends AjaxHandler {


	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;


	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;


	/**
	 * @todo make sure we're handling various server side errors, thus the ajax request is successful, but not the server side process of the request
	 * @todo send parse error
	 * @todo send server throws an error
	 * @todo: sanitize the incoming form fields from ajac, e.g. metadata-mappings, mapping-name-to-use, etc.
	 */
	protected function processRequest() {

		$this->_Mapping = new Mapping();
		$this->_Mapping->mapping_array = $this->_SpecialPage->getRequest()->getArray( 'metadata-mappings' );

		$result = $this->_Mapping->create(
			array(
				'user_name' => $this->_SpecialPage->getUser()->getName(),
				'mapping_name' => $this->_SpecialPage->getRequest()->getVal( 'mapping-name-to-use' ),
				'mediawiki_template_name' => $this->_SpecialPage->getRequest()->getVal( 'mediawiki-template-name' ),
				'mapping_json' => json_encode( $this->_Mapping->mapping_array ),
				'created' => date('Y-m-d H:i:s')
			)
		);

		return '{ ' .
			'"status" : "success", ' .
			'"result" : "' . $result . '"' .
		' }';

	}


}