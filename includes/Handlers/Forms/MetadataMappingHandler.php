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
			GWToolset\Handlers\XmlHandler,
			GWToolset\Models\MediawikiTemplate,
			Php\Filter;


class MetadataMappingHandler extends FormHandler {


	/**
	 * @var GWToolset\Models\MediawikiTemplate
	 */
	protected $MediawikiTemplate;


	/**
	 * @var GWToolset\Handlers\XmlHandler
	 */
	protected $XmlHandler;


	/**
	 * create an array that represents the mapping of the metadata to the mediawiki
	 * template based on the user form input
	 *
	 * @result array
	 */
	protected function getMapping( array &$user_options ) {

		$result = array();
	
			foreach( $this->MediawikiTemplate->template_parameters as $parameter => $value ) {
	
				$parameter_as_id = $this->MediawikiTemplate->getParameterAsId( $parameter );
	
				if ( isset( $_POST[ $parameter_as_id ] ) ) {
	
					$result[ $parameter_as_id ] = Filter::evaluate( array( 'source' => $_POST, 'name' => $parameter_as_id ) );
	
				}
	
			}

		return $result;
	
	}


	/**
	 * @return {string} $result an html string
	 */
	protected function processRequest() {

		$result = null;
		$mapping = null;

		$this->FileHandler = new FileHandler( $this->SpecialPage );
		$this->MediawikiTemplate = new MediawikiTemplate();
		$this->XmlHandler = new XmlHandler( $this->SpecialPage, $this->MediawikiTemplate );

		$user_options = array(
			'record-element-name' => !empty( $_POST['record-element-name'] ) ? Filter::evaluate( $_POST['record-element-name'] ) : 'record',
			'mediawiki-template' => !empty( $_POST['mediawiki-template'] ) ? Filter::evaluate( $_POST['mediawiki-template'] ) : null,
			'metadata-file-url'  => !empty( $_POST['metadata-file-url'] ) ? Filter::evaluate( $_POST['metadata-file-url'] ) : null,
			'record-count' => 0
		);

			$this->checkForRequiredFormFields(
				array(
					'record-element-name',
					'mediawiki-template',
					'metadata-file-url'
				),
				$user_options
			);

			$file_path_local = $this->FileHandler->retrieveLocalFilePath( $user_options, 'metadata-file-url' );
			$this->MediawikiTemplate->getValidMediaWikiTemplate( $user_options['mediawiki-template'] );
			$mapping = $this->getMapping( $user_options );
			$result .= $this->XmlHandler->processDOMElements( $file_path_local, $user_options, $mapping );

		return $result;

	}


}