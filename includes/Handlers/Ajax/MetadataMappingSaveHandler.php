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
namespace	GWToolset\Handlers\Ajax;
use			GWToolset\Handlers\Ajax\AjaxHandler,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplates;

class MetadataMappingSaveHandler extends AjaxHandler {


	/**
	 * @todo make sure we're handling various server side errors, thus the ajax request is successful, but not the server side process of the request
	 * @todo send parse error
	 * @todo send server throws an error
	 *
	 * @todo get username
	 * @todo get 
	 */
	protected function processAjax() {

		$mapping = new Mapping();
		$mappings = $mapping->flattenFormFieldArray( $this->SpecialPage->getRequest()->getArray( 'metadata-mappings' ) );

		$result = $mapping->create(
			array(
				'user_name' => $this->SpecialPage->getUser()->getName(),
				'mapping_name' => $this->SpecialPage->getRequest()->getVal( 'mapping-name-to-use' ),
				'mediawiki_template' => $this->SpecialPage->getRequest()->getVal( 'mediawiki-template' ),
				'mapping' => json_encode( $mappings ),
				'created' => date('Y-m-d H:i:s')
			)
		);

		return '{ ' .
			'"status" : "success", ' .
			'"result" : "' . $result . '"' .
		' }';

	}


}

