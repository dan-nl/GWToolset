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
namespace GWToolset\Models;


class Menu {


	static public function getMenu() {

		return
			'<ul>' .

				'<li><a href="?gwtoolset-form=metadata-upload">Metadata Upload</a></li>' .
				'<li><a href="?gwtoolset-form=metadata-detect">Metadata Mapping</a></li>' .

			'</ul>';

	}


}

