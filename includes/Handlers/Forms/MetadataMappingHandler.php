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
namespace	GWToolset\Handlers\Forms;
use			GWToolset\Handlers\FileHandler,
			GWToolset\Handlers\Xml\XmlMappingHandler,
			GWToolset\Models\Mapping,
			GWToolset\Models\MediawikiTemplate,
			Php\Filter;


class MetadataMappingHandler extends FormHandler {


	/**
	 * GWToolset\Handlers\FileHandler
	 */
	protected $_FileHandler;


	/**
	 * @var GWToolset\Models\Mapping
	 */
	protected $_Mapping;


	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $_MediawikiTemplate;


	/**
	 * @var GWToolset\Handlers\Xml\XmlMappingHandler
	 */
	protected $_XmlMappingHandler;


	/**
	 * @return {string} $result an html string
	 */
	protected function processRequest() {

		$result = null;
		$file_path_local = null;
		$this->_FileHandler = null;
		$this->_Mapping = null;
		$this->_MediawikiTemplate = null;
		$this->_XmlMappingHandler = null;

			$user_options = array(
				'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
				'mediawiki-template-name' => !empty( $_POST['mediawiki-template-name'] ) ? Filter::evaluate( $_POST['mediawiki-template-name'] ) : null,
				'metadata-file-url'  => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
				'record-count' => 0
			);
			

			$this->checkForRequiredFormFields(
				array(
					'record-element-name',
					'mediawiki-template-name',
					'metadata-file-url',
					'record-count'
				),
				$user_options
			);

			$this->_FileHandler = new FileHandler( $this->SpecialPage );
			$file_path_local = $this->_FileHandler->retrieveLocalFilePath( $user_options );

			$this->_MediawikiTemplate = new MediawikiTemplate();
			$this->_MediawikiTemplate->getValidMediaWikiTemplate( $user_options );

			$this->_Mapping = new Mapping();
			$this->_Mapping->mapping_array = $this->_MediawikiTemplate->getMappingFromArray();
			$this->_Mapping->setTargetElements();
			$this->_Mapping->reverseMap();

			$this->_XmlMappingHandler = new XmlMappingHandler( $this->SpecialPage, $this->_Mapping, $this->_MediawikiTemplate );
			$result = $this->_XmlMappingHandler->processXml( $user_options, $file_path_local );

		return $result;

	}


}