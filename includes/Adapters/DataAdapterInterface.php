<?php
/**
 * GWToolset
 *
 * @file
 * @ingroup Extensions
 * @version 0.0.1
 * @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html
 */
namespace GWToolset\Adapters;


interface DataAdapterInterface {

	public function create( array $options = array() );
	public function retrieve( array $options = array() );
	public function update( array $options = array() );
	public function delete( array $options = array() );

}