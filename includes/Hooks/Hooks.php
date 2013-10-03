<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @license GNU General Public License 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset;

class Hooks {

	/**
	 * @param $list array
	 * @return bool
	 */
	public static function onCanonicalNamespaces( &$list ) {
		$list[NS_GWTOOLSET] = 'GWToolset';
		$list[NS_GWTOOLSET_TALK] = 'GWToolset_talk';
		return true;
	}
}
