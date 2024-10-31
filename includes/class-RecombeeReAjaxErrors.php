<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RecombeeReAjaxErrors {
	
	private static $recombee;
	private static $ajaxErrors = array();
	
	public static function setupErrHandling(){
		
		self::$recombee = RecombeeRe::instance();
		
 	    if( !self::$recombee->is_doing_ajax() ){
			return;
		}
		
		if( (
			isset($_POST['data']['AJAX_Marker']) && $_POST['data']['AJAX_Marker'] == RRE_PLUGIN_DIR)	|| 
			isset($_POST['preset']) && $_POST['preset'] == RRE_BLOG_SETTINGS_PRESET_NAME				||
			isset($_POST['preset']) && $_POST['preset'] == RRE_SITE_SETTINGS_PRESET_NAME				){
				
			if( WP_DEBUG ){
		
				register_shutdown_function( array(__CLASS__, '_shutdown_function'));
				set_error_handler( array(__CLASS__, '_console_error_message'));
			}
		}
	}
	
	public static function _shutdown_function(){

		self::_console_error_message( error_get_last() );
	}
	
	public static function _console_error_message( $args ) {
		
		// error_get_last() has NO error
		if( $args === null ) return;
		
		static $once;
		
		if( ! $once ){
			$once = 1;
			self::_static_console( 'PHP errors goes next:' ); // title for errors
		}
		
		$err_names = array(
		
			// fatal errors
			E_ERROR				=> 'Fatal error',
			E_PARSE				=> 'Parse Error',
			E_CORE_ERROR		=> 'Core Error',
			E_CORE_WARNING		=> 'Core Warning',
			E_COMPILE_ERROR		=> 'Compile Error',
			E_COMPILE_WARNING	=> 'Compile Warning',
			
			// other errors
			E_WARNING			=> 'Warning',
			E_NOTICE			=> 'Notice',
			E_STRICT			=> 'Strict Notice',
			E_RECOVERABLE_ERROR	=> 'Recoverable Error',
			E_DEPRECATED		=> 'Deprecated',
			E_USER_DEPRECATED	=> 'User Deprecated',
			
			// user type errors
			E_USER_ERROR		=> 'User Error',
			E_USER_WARNING		=> 'User Warning',
			E_USER_NOTICE		=> 'User Notice',
		);

		// error_get_last() HAS error
		if( is_array($args) ){
			list( $errno, $errstr, $errfile, $errline ) = array_values( $args );

			// Fatal errors
			if( ! in_array( $errno, array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING) ) ){
				 return;
			}
			else{
								
				self::_static_console( "- $err_names[$errno]: $errstr in $errfile on line $errline\n", 'fatal error' );
				echo wp_json_encode( self::$ajaxErrors );
			}
		}
		else {
			list( $errno, $errstr, $errfile, $errline ) = func_get_args();
		}
		
		// for @suppress
		$errno = $errno & error_reporting();
		if( $errno == 0 ) return;

		if( ! defined('E_STRICT') )            define('E_STRICT', 2048);
		if( ! defined('E_RECOVERABLE_ERROR') ) define('E_RECOVERABLE_ERROR', 4096);
		
		$err_name = "Unknown error ($errno)";
		if( isset($err_names[ $errno ]) )
			$err_name = $err_names[ $errno ];

		if( empty($console_type) ){
			$console_type = 'log';
		}
		elseif( in_array( $errno, array(E_WARNING, E_USER_WARNING) ) ){
			$console_type = 'warn';
		}

		self::_static_console( "- $err_name: $errstr in $errfile on line $errline\n", $console_type );

		return true;
	}
	
	private static function _static_console( $data, $type = 'log' ){
		
		if( is_array($data) || is_object($data) ){
			$data = print_r( $data, true );
		}
		self::$ajaxErrors[] = array( $data, $type );
	}
}