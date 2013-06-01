<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Models;

class Menu {

	static public function getMenu() {
		return
			'<ul>' .

				'<li><a href="?gwtoolset-form=metadata-detect">Metadata Mapping</a></li>' .

			'</ul>';
	}

}
