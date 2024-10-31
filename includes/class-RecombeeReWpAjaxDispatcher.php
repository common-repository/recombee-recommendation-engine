<?php
/* @wordpress-plugin 
 * Plugin Name: Recombee Recommendation Engine Ajax Dispatcher
 * Description: Increases perfomance of Recombee Recommendation Engine plugin on AJAX requests, disabling other unnecessary plugins. WooCommerce installed requires. 
 * Author: Recombee
 * Author URI: https://recombee.com
 * Version: 1.2.0
 * License: GPLv3
 * Text Domain: recombee-recommendation-engine
 * Domain Path: /recombee/languages/
 * WC requires at least: 3.3
 * WC tested up to: 3.5
 */

final class RecombeeReAjaxDispatcher {

	public function __construct() {
		
		if ( isset($_REQUEST['data']['AJAX_Marker']) && $_REQUEST['data']['AJAX_Marker'] == 'recombee-recommendation-engine' ){
				
			add_filter( 'option_active_plugins', array($this, 'filter_plugins'), 90);
		}
	}
	
	public function filter_plugins( $plugins ) {
 
		remove_filter( 'option_active_plugins', array($this, 'filter_plugins'));

		if( in_array('woocommerce/woocommerce.php', $plugins) ){

			return array(
				'woocommerce/woocommerce.php',
				'recombee-recommendation-engine/recombee-re.php'
			);
		}
		else{
			return $plugins;
		}
	}
}

global $recombee_re;
$recombee_re['mu-instance'] = new RecombeeReAjaxDispatcher();
