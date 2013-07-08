<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Forms;
use Exception,
	GWToolset\Config,
	GWToolset\Helpers\FileChecks,
	IContextSource,
	Linker,
	Php\Filter,
	Title;

class PreviewForm {

	public static function getPostAsHiddenFields() {
		$result = null;

		foreach( $_POST as $key => $value ) {
			if ( 'submit' == $key
					|| 'wpEditToken' == $key
					|| 'gwtoolset-form' == $key
					|| 'gwtoolset-preview' == $key
			) {
				continue;
			}

			if ( !is_array( $value ) ) {
				$result .= '<input type="hidden" name="' . Filter::evaluate( $key ). '" value="' . Filter::evaluate( $value ) . '"/>';
			} else {
				foreach( $value as $sub_value ) {
					$result .= '<input type="hidden" name="' . Filter::evaluate( $key ). '[]" value="' . Filter::evaluate( $sub_value ) . '"/>';
				}
			}
		}

		return $result;
	}

	/**
	 * @param IContextSource $Context
	 *
	 * @param string $results
	 * an html string that contains links to the results of the preview batch upload
	 */
	public static function getForm( IContextSource $Context, array &$user_options, $results ) {
		$process_button = (int) $user_options['record-count'] > Config::$preview_throttle
			? '<input type="submit" name="submit" value="' . wfMessage( 'gwtoolset-process-batch' )->escaped() . '"/>' . '<br />'
			: wfMessage( 'gwtoolset-no-more-records' )->parse() . '<br />';

		$step1_link = Linker::link( Title::newFromText( 'Special:GWToolset' ), 'Step 1 : Metadata Detect', array(), array( 'gwtoolset-form' => 'metadata-detect' ) ) . '<br />';
		$step2_link = '<span id="step2-link">&nbsp;</span>';

		return
			wfMessage( 'gwtoolset-step-3-instructions' )->params( Config::$preview_throttle )->parse() .
			$results .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post">' .

				'<input type="hidden" name="gwtoolset-form" value="metadata-mapping"/>' .
				'<input type="hidden" name="wpEditToken" id="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
				'<input type="hidden" name="record-begin" value="' . (int) $user_options['record-count'] . '"/>' .
				self::getPostAsHiddenFields() .

				'<p>' . wfMessage( 'gwtoolset-step-3-instructions-2' )->parse() . '</p>' .
				'<p>' . $process_button . '</p>' .
				wfMessage( 'gwtoolset-step-3-instructions-3' )->parse() .

			'</form>' .

			$step1_link .
			$step2_link;
	}

}
