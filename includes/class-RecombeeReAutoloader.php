<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RecombeeReAutoloader{
	
	/**
	* Path to the includes directory.
	*
	* @var string
	*/
	private $include_path = '';
	
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( RRE_PLUGIN_FILE ) ) . '/includes/';
	}
	
	/**
	* Auto-load Recombee classes on demand to reduce memory consumption.
	*
	* @param string $class
	*/
	public function autoload( $class ) {

		if ( 0 !== strpos( $class, 'Recombee' ) ) {
			return;
		}
		
		$file  = $this->get_file_name_from_class( $class );
		$this->load_file( $this->include_path . $file );
	}
	
	/**
	* Take a class name and turn it into a file name.
	*
	* @param  string $class
	* @return string
	*/
	private function get_file_name_from_class( $class ) {
		
		if ( false !== strpos( $class, 'RecommApi' ) ){
			return str_replace( array('\\'), array('/'), $class ) . '.php';
		}
		if ( false !== strpos( $class, 'Recombee' ) ){
			return 'class-' . str_replace( array('\\'), array('/'), $class ) . '.php';
		}
		
	}
	
	/**
	* Include a class file.
	*
	* @param  string $path
	* @return bool successful or not
	*/
	private function load_file( $path ) {
		
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			
		}
	}
}

new RecombeeReAutoloader();