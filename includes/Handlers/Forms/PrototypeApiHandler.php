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
use 		Exception,
			GWToolset\Config,
			GWToolset\MediaWiki\Api\Client,
			GWToolset\Templates\Artwork,
			SimpleXMLElement;


class PrototypeApiHandler extends UploadHandler {


	/**
	 * @var GWToolset\MediaWiki\Api\Client
	 */
	protected $MWApiClient;


	/**
	 * @var SimpleXMLElement
	 */
	protected $Metadata;


	public function uploadMetadata() {

		// as long as ignorewarnings is passed in the array they will be ignored; it does not matter value is present
		// once the text is on the page it cannot be altered via an upload - probably has to be altered via a page edit

		$Artwork = new Artwork();
		$Artwork->populate( $this->Metadata->item[0] );

		// eventhough this is coming from a url, a filename is needed
		// $wgAllowCopyUploads needs to be set in LocalSettings.php to true
		$result = $this->MWApiClient->upload(
			array(
				'filename' => $this->getFilename( $Artwork->url_to_the_media_file ),
				//'comment' => 'The Birth of Venus (comment)',
				'text' => $Artwork->getTemplate(),
				'token' => $this->MWApiClient->getEditToken(),
				//'token' => $SpecialPage->getUser()->getEditToken(),
				//'watch' => null,
				'ignorewarnings' => true,
				//'file' => '@' . $File->pathinfo['tmp_name'],
				'url' => $Artwork->url_to_the_media_file,
				//'sessionkey' => null
			)
		);

		if ( empty( $result['upload']['result'] ) && $result['upload']['result'] !== 'Success' ) {
			throw new Exception( wfMessage('mw-api-client-unknown-error') );
		}

		return
			'<h2>Upload Result</h2>' .
			'<p>' .
				'<a ' .
					'href="' . $result['upload']['imageinfo']['descriptionurl'] . '" ' .
					'target="_blank"' .
				'>' .
					$result['upload']['filename'] .
				'</a><br/>' .
			'</p>';

	}


	/**
	 * @throws Exception
	 */
	public function getMetadata() {

		global $wgCanonicalServer;
		$this->Metadata = simplexml_load_file( $this->File->tmp_name );

		if ( count( $this->Metadata ) > 1
			|| empty( $this->Metadata->item )
			|| empty( $this->Metadata->item->title )
		) {

			throw new Exception(
				wfMessage( 'gwtoolset-improper-data-format' )
				->rawParams( '<a href="' . $wgCanonicalServer. '/index.php/Extension:GWToolset">documentation</a>' )
			);

		}

	}


	/**
	 * @return string $result
	 * an html string
	 */
	public function processUpload() {

		$result = null;

		try {

			self::getMetadata();

			$this->MWApiClient = new Client( Config::$api_internal_endpoint, $this->SpecialPage );
			$this->MWApiClient->login( Config::$api_internal_lgname, Config::$api_internal_lgpassword );
			$this->MWApiClient->debug_html .= 'Logged in<br/>' . '<pre>' . print_r( $this->MWApiClient->Login, true ) . '</pre>';

			$result .= self::uploadMetadata();

		} catch( Exception $e ) {

			$result .= '<h1>' . wfMessage( 'gwtoolset-api-error' ) . '</h1>' .
				'<span class="error">' . $e->getMessage() . '</span><br/>';

		}

		if ( Config::$display_debug_output
			&& $this->SpecialPage->getUser()->isAllowed( 'gwtoolset-debug' )
			&& isset( $this->MWApiClient )
		) {

			$result .= $this->MWApiClient->debug_html;

		}

		return $result;

	}


}

