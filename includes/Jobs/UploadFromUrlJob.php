<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Jobs;
use Exception,
	Job,
	UploadBase,
	UploadFromUrl,
	User;

class UploadFromUrlJob extends Job {

	/**
	 * @var UploadFromUrl
	 */
	public $Upload;

	/**
	 * @var User
	 */
	protected $_User;

	public function __construct( $title, $params, $id = 0 ) {
		parent::__construct( 'gwtoolsetUploadFromUrlJob', $title, $params, $id );
	}

	public function run() {
		// Initialize this object and the upload object
		$this->Upload = new UploadFromUrl();
		$this->Upload->initialize(
			$this->title->getText(),
			$this->params['url_to_the_media_file'],
			false
		);

		$this->_User = User::newFromName( $this->params['username'] );

		// Fetch the file - returns a Status Object
		$status = $this->Upload->fetchFile();
		if ( !$status->isOk() ) {
			error_log( $status->getWikiText() );
			return true; //@todo: should this return true? when returning false, job stays in queue as orphan
		}

		// Verify upload - returns a Status value
		$result = $this->Upload->verifyUpload();
		if ( $result['status'] != UploadBase::OK ) {
			$status = $this->Upload->convertVerifyErrorToStatus( $result );
			error_log( $status->getWikiText() );
			return true; //@todo: should this return true? when returning false, job stays in queue as orphan
		}

		// Perform the upload - returns FileRepoStatus Object
		$status = $this->Upload->performUpload(
			$this->params['comment'],
			$this->params['text'],
			$this->params['watch'],
			$this->_User
		);

		if ( !$status->isOk() ) {
			error_log( $status->getWikiText() );
			//@todo: should this return true? when returning false, job stays in queue as orphan
		}

		return true;
	}

}
