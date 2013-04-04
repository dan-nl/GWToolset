<?php
namespace GWToolset\Adapters;


interface DataAdapterInterface {

	public function create( array $options = array() );
	public function retrieve( array $options = array() );
	public function update( array $options = array() );
	public function delete( array $options = array() );

}