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
namespace	GWToolset\Models;
use	ResultWrapper;


interface ModelInterface {


	public function create( array $options = array() );
	public function retrieve( array $options = array() );
	public function update( array $options = array() );
	public function delete( array $options = array() );


}