<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset;
use DatabaseUpdater,
	GWToolset\Adapters\Db\MediawikiTemplateDbAdapter,
	GWToolset\Models\Mapping,
	GWToolset\Models\MediawikiTemplate,
	MWException;


/**
 * placeholder class not yet used
 */
class Hooks {


	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 * based on core/includes/installer/MysqlUpdater.php::doUserGroupsUpdate
	 *
	 * @param DatabaseUpdater $updater
	 * @throws MWException
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {

		switch ( $updater->getDB()->getType() ) {

			case 'mysql':

				$MediawikiTemplateDbAdapter = new MediawikiTemplateDbAdapter();
				$MediawikiTemplateDbAdapter->createTable( $updater );
				break;


			default:

				throw new MWException( wfMessage( 'gwtoolset-db-client-support' )->plain() );
				break;

		}

		return true;

	}


}