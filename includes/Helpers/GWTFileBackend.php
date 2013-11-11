<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */

namespace GWToolset\Helpers;
use FSFileBackend,
	FileBackendGroup,
	GWToolset\Jobs\GWTFileBackendCleanupJob,
	GWToolset\Config,
	JobQueueGroup,
	MWException,
	Php\File,
	Php\Filter,
	ReflectionClass,
	ReflectionProperty,
	Status,
	Title,
	User;

class GWTFileBackend {

	/**
	 * @var {string}
	 *
	 * defaults to 'files'
	 */
	public $container;

	/**
	 * @var {string}
	 *
	 * the filesystem path of the math directory.
	 * defaults to 'file-backend'.
	 */
	public $directory;

	/**
	 * @var {FileBackend}
	 */
	public $FileBackend;

	/**
	 * @var {int}
	 *
	 * octal unix file permissions to use on files stored.
	 * defaults to null, which defaults to 0644 in \FSFileBackend::__construct()
	 * also look at \FileBackend::prepare() for directory permissions
	 */
	public $filemode;

	/**
	 * @var {string}
	 *
	 * registered name of a file lock manager to use.
	 * defaults to 'nullLockManager'
	 */
	public $lockmanager;

	/**
	 * @var {string}
	 *
	 * The unique name of this backend.
	 *   - This should consist of alphanumberic, '-', and '_' characters.
	 *   - This name should not be changed after use (e.g. with journaling).
	 *   - Note that the name is *not* used in actual container names.
	 *
	 * defaults to 'file-system-backend'
	 */
	public $name;

	/**
	 * @var {bool}
	 *
	 * defaults to false
	 * whether or not to add an .htaccess file, with the directive Deny from all, to the root of
	 * the container during prepare()
	 */
	public $no_access;

	/**
	 * @var {bool}
	 *
	 * defaults to false
	 * wheter or not to seed new directories with a blank index.html during prepare()
	 * to prevent crawling
	 */
	public $no_listing;

	/**
	 * @var {string}
	 *
	 * derived file directory path based on
	 * $wgUploadDirectory . DIRECTORY_SEPARATOR . $this->directory;
	 */
	protected $_directory_path;

	/**
	 * @var {string}
	 */
	protected $_file_extension;

	/**
	 * @var {string}
	 */
	protected $_hash;

	/**
	 * @var {User}
	 */
	protected $_User;

	/**
	 * @param {array} $params
	 */
	public function __construct( array $params = array() ) {
		$this->init();
		$this->populate( $params );
		$this->setPaths();
		$this->setupFileBackend();
	}

	/**
	 * creates a GWTFileBackendCleanupJob that will delete the mwstore file in the FileBackend
	 *
	 * @param {string} $mwstore_path
	 * @throws {MWException}
	 * @return {bool}
	 */
	public function createCleanupJob( $mwstore_path = null ) {
		$result = false;

		if ( empty( $mwstore_path ) ) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( __METHOD__ . ': ' . wfMessage( 'gwtoolset-no-mwstore' ) )
					->parse()
				);
		}

		$job = new GWTFileBackendCleanupJob(
			Title::newFromText(
				Filter::evaluate( $this->_User->getName() ) . '/' .
				Filter::evaluate( Config::$name ) . '/' .
				'FileBackend Cleanup Job/' .
				uniqid(),
				NS_USER
			),
			array(
				'metadata-file-mwstore' => Filter::evaluate( $mwstore_path )
			)
		);

		$result = JobQueueGroup::singleton()->push( $job );

		if ( $result ) {
			$result = Status::newGood();
		} else {
			$result = Status::newFatal(
				wfMessage( 'gwtoolset-batchjob-creation-failure' )
					->params( 'GWTFileBackendCleanupJob' )
					->parse()
			);
		}

		return $result;
	}

	/**
	 * deletes a file, based on an mwstore path, from the FileBackend
	 *
	 * @param {string} $mwstore_file_path
	 * @return {Status}
	 */
	public function deleteFile( $mwstore_file_path = null ) {
		$result = Status::newGood();

		if ( empty( $mwstore_file_path ) ) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( __METHOD__ . ': ' . wfMessage( 'gwtoolset-no-mwstore' ) )
					->parse()
			);
		}

		$src = array( 'src' => Filter::evaluate( $mwstore_file_path ) );

		if ( $this->FileBackend->fileExists( $src ) ) {
			$result = $this->FileBackend->quickDelete( $src );
		} else {
			$result = Status::newFatal( wfMessage( 'gwtoolset-delete-no-file' ) );
		}

		return $result;
	}

	/**
	 * gets path to store hashes in
	 *
	 * @return {null|string} storage directory
	 */
	protected function getHashPath() {
		$result = null;
		$hash_sub_path = $this->getHashSubPath();

		$result =
			$this->FileBackend->getRootStoragePath() . DIRECTORY_SEPARATOR .
			Filter::evaluate( $this->container );

		if ( !empty( $hash_sub_path ) ) {
			$result .= DIRECTORY_SEPARATOR . $hash_sub_path;
		}

		return $result;
	}

	/**
	 * gets relative directory for this specific hash
	 *
	 * @return {null|string} relative directory
	 */
	protected function getHashSubPath() {
		$result = null;

		if ( !empty( $this->_hash ) ) {
			$result =
				substr( $this->_hash, 0, 1 ) . DIRECTORY_SEPARATOR .
				substr( $this->_hash, 1, 1 ) . DIRECTORY_SEPARATOR .
				substr( $this->_hash, 2, 1 );
		}

		return $result;
	}

	/**
	 * retrieves the mwstore path to the FileBackend file
	 *
	 * @return {string}
	 */
	public function getMWStorePath() {
		return
			$this->getHashPath() . DIRECTORY_SEPARATOR .
			$this->_hash .
			( ( !empty( $this->_file_extension ) )
				? '.' . Filter::evaluate( $this->_file_extension )
				: null );
	}

	protected function init() {
		global $wgUploadPath, $wgUploadDirectory;

		$this->name = Config::$fsbackend_name;
		$this->lockmanager = Config::$fsbackend_lockmanager;
		$this->container = Config::$fsbackend_container;
		$this->directory = Config::$fsbackend_directory;
		$this->filemode = Config::$fsbackend_filemode;
		$this->no_access = Config::$fsbackend_no_access;
		$this->no_listing = Config::$fsbackend_no_listing;
	}

	/**
	 * @param {array} $params
	 */
	protected function populate( array $params ) {
		if ( isset( $params['User'] ) && $params['User'] instanceof User ) {
			$this->_User = $params['User'];
		}
	}

	/**
	 * create any containers/directories as needed
	 *
	 * @return {Status}
	 */
	protected function prepare() {
		$params = array(
			'dir' => $this->getHashPath(),
			'noAccess' => Filter::evaluate( $this->no_access ),
			'noListing' => Filter::evaluate( $this->no_listing )
		);

		return $this->FileBackend->prepare( $params );
	}

	/**
	 * store the file at the final storage path
	 *
	 * @param {string} $tmp_file_path
	 * the temporary file path location of the src file to be stored in the FileBackend
	 *
	 * @return {Status}
	 */
	protected function quickStore( $tmp_file_path = null ) {
		$params = array(
			'src' => Filter::evaluate( $tmp_file_path ),
			'dst' => Filter::evaluate( $this->getMWStorePath() )
		);

		return $this->FileBackend->quickStore( $params );
	}

	/**
	 * retrieves a file, based on an mwstore path, from the FileBackend
	 *
	 * @param {string} $mwstore_file_path
	 * @throws {MWException}
	 * @return {null|FSFile}
	 */
	public function retrieveFile( $mwstore_file_path = null ) {
		$result = null;

		if ( empty( $mwstore_file_path ) ) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( __METHOD__ . ': ' . wfMessage( 'gwtoolset-no-mwstore' ) )
					->parse()
			);
		}

		$src = array( 'src' => Filter::evaluate( $mwstore_file_path ) );

		if ( $this->FileBackend->fileExists( $src ) ) {
			if ( $this->FileBackend->getFileSize( $src ) === 0 ) {
				$this->FileBackend->quickDelete( $src );

				throw new MWException(
					wfMessage( 'gwtoolset-developer-issue' )
						->params( __METHOD__ . ': ' . wfMessage( 'gwtoolset-fsfile-empty' )->parse() )
						->parse()
				);
			}

			$result = $this->FileBackend->getLocalReference( $src );
		}

		return $result;
	}

	/**
	 * saves the file to the FileBackend
	 *
	 * @see http://www.php.net/manual/en/datetime.formats.relative.php
	 *
	 * @param {File} $File
	 * @throws {MWException}
	 * @return {null|string}
	 */
	public function saveFile( File $File ) {
		$result = null;

		if ( empty( $File ) ) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( __METHOD__ . ': ' . wfMessage( 'gwtoolset-no-file' )->parse() )
					->parse()
			);
		}

		$this->setHash( $File->tmp_name );
		$this->setFileExtension( $File->pathinfo['extension'] );
		$Status = $this->prepare();

		if ( !$Status->ok ) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( __METHOD__ . ': ' . $Status->getMessage() )
					->parse()
			);
		}

		$Status = $this->quickStore( $File->tmp_name );

		if ( !$Status->ok ) {
			throw new MWException(
				wfMessage( 'gwtoolset-developer-issue' )
					->params( __METHOD__ . ': ' . $Status->getMessage() )
					->parse()
			);
		}

		$result = $this->getMWStorePath();

		return $result;
	}

	/**
	 * @param {string} $file_extension
	 */
	protected function setFileExtension( $file_extension = null ) {
		$this->_file_extension = $file_extension;
	}

	/**
	 * @param {string} $string
	 */
	protected function setHash( $string ) {
		$this->_hash = md5( $string );
	}

	protected function setPaths() {
		global $wgUploadDirectory;

		$this->_directory_path = $wgUploadDirectory . DIRECTORY_SEPARATOR . $this->directory;
	}

	/**
	 * sets up the file backend
	 *
	 * if $wgGWToolsetFileBackend is not false, assumes that a web admin has set-up a
	 * $wgFileBackend[] for use and $wgGWToolsetFileBackend refers to it; otherwise
	 * defaults to an FSFileBackend instance created with vraibles from GWToolset\Config::$fsbackend_
	 */
	protected function setupFileBackend() {
		global $wgGWToolsetFileBackend;

		if ( $wgGWToolsetFileBackend ) {
			$this->FileBackend = FileBackendGroup::singleton()->get( $wgGWToolsetFileBackend );
		} else {
			$params = array(
				'name'           => Filter::evaluate( $this->name ),
				'lockManager'    => Filter::evaluate( $this->lockmanager ),
				'containerPaths' => array(
					Filter::evaluate( $this->container ) => Filter::evaluate( $this->_directory_path )
				)
			);

			if ( !empty( $this->filemode ) ) {
				$params['fileMode'] = (int)$this->filemode;
			}

			$this->FileBackend = new FSFileBackend( $params );
		}
	}

}
