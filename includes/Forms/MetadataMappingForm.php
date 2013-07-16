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
	GWToolset\Handlers\Forms\FormHandler,
	GWToolset\Helpers\FileChecks,
	IContextSource,
	Linker,
	Php\Filter,
	Title;

class MetadataMappingForm {

	/**
	 * returns an html form for step 2 : Metadata Mapping
	 *
	 * @param {FormHandler} $Handler
	 *
	 * @param {array} $user_options
	 * an array of user options that was submitted in the html form
	 *
	 * @return {string}
	 * an html form
	 */
	public static function getForm( FormHandler $Handler, array &$user_options ) {

		$template_link = '[[Template:' . Filter::evaluate( $user_options['mediawiki-template-name'] ) . ']]';

		return
			wfMessage( 'gwtoolset-step-2-heading' )->params( $template_link )->parse() .

			wfMessage( 'gwtoolset-metadata-file' )->parse() .
			'<p>' .
			Linker::link( $user_options['Metadata-Title'], null, array( 'target' => '_blank' ) ) . '<br />' .
			wfMessage( 'gwtoolset-record-count' )->params( (int)$user_options['record-count'] )->escaped() .
			'</p>' .

			wfMessage( 'gwtoolset-step-2-instructions' )->params( $template_link )->parse() .

			'<form id="gwtoolset-form" action="' . $Handler->SpecialPage->getContext()->getTitle()->getFullURL() . '" method="post">' .

			'<fieldset>' .

			'<legend>' . wfMessage( 'gwtoolset-metadata-mapping-legend' )->escaped() . '</legend>' .

			'<input type="hidden" name="gwtoolset-form" value="metadata-mapping"/>' .
			'<input type="hidden" name="gwtoolset-preview" value="true"/>' .
			'<input type="hidden" name="record-count" value="' . (int)$user_options['record-count'] . '"/>' .
			'<input type="hidden" name="record-element-name" value="' . Filter::evaluate( $user_options['record-element-name'] ) . '"/>' .
			'<input type="hidden" name="mediawiki-template-name" id="mediawiki-template-name" value="' . Filter::evaluate( $user_options['mediawiki-template-name'] ) . '"/>' .
			'<input type="hidden" name="metadata-file-url" value="' . Filter::evaluate( $user_options['metadata-file-url'] ) . '"/>' .
			'<input type="hidden" name="metadata-mapping-url" value="' . Filter::evaluate( $user_options['metadata-mapping-url'] ) . '"/>' .
			'<input type="hidden" name="metadata-mapping-name" id="metadata-mapping-name" value="' . Filter::evaluate( $user_options['metadata-mapping-name'] ) . '"/>' .
			'<input type="hidden" name="wpEditToken" id="wpEditToken" value="' . $Handler->User->getEditToken() . '">' .

			wfMessage( 'gwtoolset-mediawiki-template' )->params( Filter::evaluate( $user_options['mediawiki-template-name'] ) )->parse() .

			'<table id="template-table" style="float:left;margin-right:2%;margin-bottom:1em;">' .
			'<thead>' .
			'<tr><th>' . wfMessage( 'gwtoolset-template-field' )->escaped() . '</th><th colspan="2">' . wfMessage( 'gwtoolset-maps-to' )->escaped() . '</th></tr>' .
			'</thead>' .
			'<tbody>' .
			$Handler->getMetadataAsHtmlSelectsInTableRows( $user_options ) .
			'</tbody>' .
			'</table>' .
			'<table style="float:left; display: inline; width: 60%; overflow: auto;">' .
			'<thead>' .
			'<tr><th colspan="2">' . wfMessage( 'gwtoolset-example-record' )->escaped() . '</th></tr>' .
			'</thead>' .
			'<tbody style="vertical-align: top;">' .
			$Handler->XmlDetectHandler->getMetadataAsHtmlTableRows( $user_options ) .
			'</tbody>' .
			'</table>' .

			'<p style="clear:both;padding-top:2em;"><span class="required">*</span>' . wfMessage( 'gwtoolset-required-field' )->escaped() . '</p>' .
			wfMessage( 'copyrightwarning2' )->parseAsBlock() .

			'<h3 style="margin-top:1em;">' . wfMessage( 'categories' )->escaped() . '</h3>' .

			'<p>' .
			'<i><u>' . wfMessage( 'gwtoolset-global-categories' )->escaped() . '</u></i><br />' .
			wfMessage( 'gwtoolset-global-tooltip' )->escaped() .
			'</p>' .

			'<table>' .
			'<tbody>' .
			'<tr>' .
			'<td><label for="gwtoolset-category">' . wfMessage( 'gwtoolset-category' )->escaped() . '</label></td>' .
			'<td class="metadata-add"></td>' .
			'<td><input type="text" id="gwtoolset-category" name="category[]"/></td>' .
			'</tr>' .
			'</tbody>' .
			'</table>' .

			'<p style="margin-top:1em;">' .
			'<i><u>' . wfMessage( 'gwtoolset-specific-categories' )->escaped() . '</u></i><br />' .
			wfMessage( 'gwtoolset-specific-tooltip' )->escaped() .
			'</p>' .

			'<table>' .
			'<thead>' .
			'<th>&nbsp;</th>' .
			'<th>' . wfMessage( 'gwtoolset-phrasing' )->escaped() . '</th>' .
			'<th>' . wfMessage( 'gwtoolset-metadata-field' )->escaped() . '</th>' .
			'</thead>' .
			'<tbody>' .
			'<tr>' .
			'<td class="category-add"></td>' .
			'<td><input type="text" name="category-phrase[]" placeholder="' . wfMessage( 'gwtoolset-painted-by' )->escaped() . '"/></td>' .
			'<td><select name="category-metadata[]">' . $Handler->XmlDetectHandler->getMetadataAsOptions() . '</select></td>' .
			'</tr>' .
			'</tbody>' .
			'</table>' .

			'<h3 style="margin-top:1em;">' . wfMessage( 'gwtoolset-partner' )->escaped() . '</h3>' .
			'<p>' .
			wfMessage( 'gwtoolset-partner-explanation' )->escaped() . '<br />' .
			'<label>' .
			wfMessage( 'gwtoolset-partner-template' )->escaped() .
			'<input type="text" name="partner-template-url" value="" placeholder="Template:Europeana" class="gwtoolset-url-input"/>' .
			'</label><br />' .
			Linker::link( Title::newFromText( 'Category:' . Config::$source_templates ), null, array( 'target' => '_blank' ) ) .
			'</p>' .

			'<h3 style="margin-top:1em;">' . wfMessage( 'summary' )->escaped() . '</h3>' .
			'<p>' .
			'<input class="mw-summary" id="wpSummary" maxlength="255" spellcheck="true" title="Enter a short summary [ctrl-option-b]" accesskey="b" name="wpSummary">' .
			'</p>' .

			'<p>' .
			'<label><input type="checkbox" name="upload-media" value="true"/> ' . wfMessage( 'gwtoolset-reupload-media' )->escaped() . '</label><br />' .
			wfMessage( 'gwtoolset-reupload-media-explanation' )->escaped() .
			'</p>' .

			'<input type="submit" name="submit" value="' . wfMessage( 'gwtoolset-preview' ) . '">' .

			'</fieldset>' .

			'</form>';
	}
}
