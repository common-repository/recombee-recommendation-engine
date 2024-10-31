<?php
/* @wordpress-plugin 
 * Plugin Name: Recombee Recommendation Engine
 * Description: Increase your customer satisfaction and spending with Amazon-like AI powered recommendations on your home page, product detail or emailing campaigns. WooCommerce installed requires. 
 * Author: Recombee
 * Author URI: https://recombee.com
 * Version: 2.8.1
 * License: GPLv3
 * Text Domain: recombee-recommendation-engine
 * Domain Path: /recombee/languages/
 * WC requires at least: 3.3
 * WC tested up to: 3.9
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define RRE_PLUGIN_FILE.
if ( ! defined( 'RRE_PLUGIN_FILE' ) ) {
	define( 'RRE_PLUGIN_FILE', __FILE__ );

	// Define RRE_PLUGIN_DIR.
	if ( ! defined( 'RRE_PLUGIN_DIR' ) ) {
		define( 'RRE_PLUGIN_DIR',plugin_basename( dirname( RRE_PLUGIN_FILE ) ) );
	}
}

// Include the main Recombee class.
if ( ! class_exists( 'RecombeeRe' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-RecombeeRe.php';
}

/**
 * Main instance of Recombee.
 *
 * Returns the main instance of Recombee to prevent the need to use globals.
 *
 * @return object Recombee
 */
function recombee_re() {
		
	if( empty($_GET['wc-ajax']) ){
	
		return RecombeeRe::instance();	
	}
}

global $recombee_re;
$recombee_re['instance'] = recombee_re();	
