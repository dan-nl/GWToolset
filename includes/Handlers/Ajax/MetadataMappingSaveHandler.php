<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Handlers\Ajax;
use GWToolset\Adapters\Php\MappingPhpAdapter,
	GWToolset\Models\Mapping;

class MetadataMappingSaveHandler extends AjaxHandler {

	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;

	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;

	protected function processRequest() {
		$result = json_encode( array( 'status' => 'failed' ) );
		$mapping_result = false;

		$this->_Mapping = new Mapping( new MappingPhpAdapter() );
		$this->_Mapping->mapping_array = $this->SpecialPage->getRequest()->getArray( 'metadata-mappings' );

		// create takes care of new and existing pages
		$mapping_result = $this->_Mapping->create(
			array(
				'created' => date('Y-m-d H:i:s'),
				'mapping-json' => json_encode( $this->_Mapping->mapping_array ),
				'mapping-name' => $this->SpecialPage->getRequest()->getVal( 'mapping-name-to-use' ),
				'mediawiki-template-name' => $this->SpecialPage->getRequest()->getVal( 'mediawiki-template-name' ),
				'user' => $this->SpecialPage->getUser()
			)
		);

		$result = json_encode( $mapping_result );
		return $result;
	}

}
