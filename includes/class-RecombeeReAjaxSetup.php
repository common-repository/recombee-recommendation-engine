<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RecombeeReAjaxSetup {
	
	private static $recombee	= null;
	private static $_instance	= null;

	public static function instance(){
		
		if ( is_null( self::$_instance ) ) {
			
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}

	private function __construct(){
		
		self::$recombee	= RecombeeRe::instance();
		
		add_action('init',				array( __CLASS__, 'add_endpoints'),	20 );
		add_action('shutdown',			array( __CLASS__, 'to_flush_rules'), 20 );
		add_filter('query_vars',		array( __CLASS__, 'add_query_vars'));
		add_filter('wp_doing_ajax',		array( __CLASS__, 'wp_to_rre_is_doing_ajax'	), 10, 1 );
		add_filter('template_redirect',	array( __CLASS__, 'do_virtual_page'));
		
	}
		
	public static function add_endpoints() {
		
		global $wp_rewrite;
		
		add_rewrite_tag('%rre-virtual-page%',	'([^&]+)');
		add_rewrite_rule( 'rre-virtual-page/([^/]*)/?$', 'index.php?rre-virtual-page=$matches[1]', 'top' );		
	}

	public static function to_flush_rules(){
		
		$blog_settings_current = self::$recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		
		if( $blog_settings_current['rewrite_rules_flushed_once'] != 1 ){
			
			$blog_settings_new = self::$recombee->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME, array(
				'rewrite_rules_flushed_once' => 'queued',
			));
		}
	}
	
	public static function add_query_vars($vars){
		
		$vars[] = 'rre-virtual-page';
		$vars[] = 'rre-ajax-action';
		return $vars;
	}
	
	public static function do_virtual_page($template){
		
		global $wp_query;
		
		if (array_key_exists('rre-virtual-page', $wp_query->query_vars)) {
			
			switch ($wp_query->query_vars['rre-virtual-page']) {

				case 'ajax':
					self::do_recombee_ajax();
				
				default:
					wp_die( 'Undefined virtual page value', 400 );
			}
		}
		
		return $template;
	}
	
	public static function wp_to_rre_is_doing_ajax( $wp_doing_ajax ){
		
		global $wp_query;
		
		if( isset($wp_query->query_vars['rre-virtual-page']) ){
			
			switch ($wp_query->query_vars['rre-virtual-page']) {

				case 'ajax':
					return true;
			}
		}
		
		return $wp_doing_ajax;
	}
	
	public static function do_recombee_ajax(){

		if ( empty( $_REQUEST['action'] ) ){
			//wp_die( '0', 400 );
			_ajax_wp_die_handler( '0' );
		}
		else{
			
			if ( ! defined( 'RRE_DOING_AJAX' ) ) {
				define( 'RRE_DOING_AJAX', true );
			}
			if ( ! defined( 'WP_DOING_AJAX' ) ) {
				define( 'WP_DOING_AJAX', true );
			}
			
			RecombeeReAjaxErrors::setupErrHandling();
		}
		
		if ( is_user_logged_in() ) {

			if ( !has_action( 'rre_ajax_' . $_REQUEST['action'] ) ) {
				//wp_die( '0', 400 );
				_ajax_wp_die_handler( '0' );
			}
			self::recombee_ajax_headers();
			do_action( 'rre_ajax_' . $_REQUEST['action'] );
		}
		else {

			if ( !has_action( 'rre_ajax_nopriv_' . $_REQUEST['action'] ) ) {
				//wp_die( '0', 400 );
				_ajax_wp_die_handler( '0' );
			}
			self::recombee_ajax_headers();
			do_action( 'rre_ajax_nopriv_' . $_REQUEST['action'] );
		}
		
		/* Default */
		//wp_die( '0' );
		_ajax_wp_die_handler( '0' );
	}

	private static function recombee_ajax_headers(){
		
		send_origin_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		wc_nocache_headers();
	 /* status_header( 200 ); */
	 
	}

	public static function get_virtual_page( $page ){
		
		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			
			$endpoint = trailingslashit( home_url( '/index.php/rre-virtual-page/' . $page . '/', 'relative' ) );
		}
		else if ( get_option( 'permalink_structure' ) ){
			
			$endpoint = trailingslashit( home_url( '/rre-virtual-page/' . $page . '/', 'relative' ) );
		}
		else{
			
			$endpoint = add_query_arg( 'rre-virtual-page', $page, trailingslashit( home_url( '', 'relative' ) ) );
		}
		
		return esc_url_raw( $endpoint );
	}
}