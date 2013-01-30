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
namespace	GWToolset;
use			DatabaseUpdater,
			GWToolset\Models\Mappings,
			GWToolset\Models\MediawikiTemplates,
			MWException;


class Hooks {


	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 * @throws MWException
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {

		switch ( $updater->getDB()->getType() ) {

			case 'mysql':

				$mappings = new Mappings();
				$mappings->createTable( $updater );

				$mediawiki_templates = new MediawikiTemplates();
				$mediawiki_templates->createTable( $updater );

				break;


			default:

				throw new MWException( wfMessage( 'gwtoolset-db-client-support' ) );
				break;

		}

		return true;

	}


}

