<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Jobs;
use Job,
	GWToolset\Handlers\Forms\MetadataMappingHandler,
	User;

/**
 * runs the MetadataMappingHandler with the original $_POST'ed form fields when
 * the job was created. the $_POST contains one or more of the following :
 *   - mediawiki template to use
 *   - url to the metadata source in the wiki
 *   - the metadata mapping to use
 *   - categories to add to the media files
 *   - partner template to use
 *   - summary to use
 */
class UploadMetadataJob extends Job {

	/**
	 * @var GWToolset\Handlers\Forms\MetadataMappingHandler
	 */
	protected $_MetadataMappingHandler;

	public function __construct( $title, $params, $id = 0 ) {
		parent::__construct( 'gwtoolsetUploadMetadataJob', $title, $params, $id );
	}

	protected function validateParams() {
		$result = true;

		if ( empty( $this->params['username'] ) ) {
			error_log( __METHOD__ . ' : no $this->params[\'user\'] provided' . PHP_EOL );
			$result = false;
		}

		if ( empty( $this->params['post'] ) ) {
			error_log( __METHOD__ . ' : no $this->params[\'post\'] provided' . PHP_EOL );
			$result = false;
		}

		return $result;
	}

	public function run() {
		$result = false;

		if ( !$this->validateParams() ) {
			return $result;
		}

		$_POST = $this->params['post'];
		$this->_MetadataMappingHandler = new MetadataMappingHandler(
			array( 'User' => User::newFromName( $this->params['username'] ) )
		);

		try {
			$result = $this->_MetadataMappingHandler->processRequest();
		} catch( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $result;
	}

}
