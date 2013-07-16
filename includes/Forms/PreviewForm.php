<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
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

	/**
	 * returns an html form for step 3 : Batch Preview
	 *
	 * @param IContextSource $Context
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @param {string} $results
	 * an html string that contains links to the results of the preview batch upload
	 * the string should have already been filtered
	 *
	 * @return {string}
	 * an html form that is filtered
	 */
	public static function getForm( IContextSource $Context, array &$user_options, array &$mediafile_titles ) {
		$process_button = (int)$user_options['record-count'] > (int )Config::$preview_throttle
			? '<input type="submit" name="submit" value="' . wfMessage( 'gwtoolset-process-batch' )->escaped() . '"/>' . '<br />'
			: wfMessage( 'gwtoolset-no-more-records' )->parse() . '<br />';

		$step1_link = Linker::link( Title::newFromText( 'Special:GWToolset' ), 'Step 1 : Metadata Detect', array(), array( 'gwtoolset-form' => 'metadata-detect' ) ) . '<br />';
		$step2_link = '<span id="step2-link">&nbsp;</span>';

		return
			wfMessage( 'gwtoolset-step-3-instructions' )->params( (int)Config::$preview_throttle )->parse() .
			wfMessage( 'gwtoolset-results' )->parse() .
			self::getTitlesAsList( $mediafile_titles ) .

			'<form id="gwtoolset-form" action="' . $Context->getTitle()->getFullURL() . '" method="post">' .

			'<input type="hidden" name="gwtoolset-form" value="metadata-mapping"/>' .
			'<input type="hidden" name="wpEditToken" id="wpEditToken" value="' . $Context->getUser()->getEditToken() . '">' .
			'<input type="hidden" name="record-begin" value="' . (int)$user_options['record-count'] . '"/>' .
			self::getPostAsHiddenFields() .

			'<p>' . wfMessage( 'gwtoolset-step-3-instructions-2' )->parse() . '</p>' .
			'<p>' . $process_button . '</p>' .
			wfMessage( 'gwtoolset-step-3-instructions-3' )->parse() .

			'</form>' .

			$step1_link .
			$step2_link;
	}

	/**
	 * a decorator method that creates <input type="hidden"> fields
	 * based on the previous $_POST. this is done to insure that all
	 * fields posted in step 2 : Metadata Mapping are maintained
	 * within this form, so that when this form posts to create the
	 * initial batch job, it has the mapping information from step 2
	 *
	 * @return {string}
	 * the string is filtered
	 */
	public static function getPostAsHiddenFields() {
		$result = null;

		foreach ( $_POST as $key => $value ) {
			if ( 'submit' == $key
				|| 'wpEditToken' == $key
				|| 'gwtoolset-form' == $key
				|| 'gwtoolset-preview' == $key
			) {
				continue;
			}

			if ( !is_array( $value ) ) {
				$result .= '<input type="hidden" name="' . Filter::evaluate( $key ) . '" value="' . Filter::evaluate( $value ) . '"/>';
			} else {
				foreach ( $value as $sub_value ) {
					$result .= '<input type="hidden" name="' . Filter::evaluate( $key ) . '[]" value="' . Filter::evaluate( $sub_value ) . '"/>';
				}
			}
		}

		return $result;
	}

	/**
	 * a decorator method that creates a <ul> with <li>s containing
	 * Title(s), which are the result of processing the metadata file
	 * with the mapping information given in step 2 : Metadata Mapping
	 *
	 * @param {array} $mediafile_titles
	 * @return {string}
	 * the string contains a Title link assumed to be filtered by Title
	 */
	public static function getTitlesAsList( array &$mediafile_titles ) {
		$result = '<ul>';

		foreach ( $mediafile_titles as $Title ) {
			if ( $Title instanceof Title ) {
				$result .= '<li>' .
					Linker::link( $Title, null, array( 'target' => '_blank' ) );
				'</li>';
			}
		}

		$result .= '</ul>';

		return $result;
	}
}
