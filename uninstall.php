<?php
/**
 * Recombee Recommendation Engine Uninstall
 *
 * Uninstalling Recombee Recommendation Engine deletes site and blog options.
 *
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

wp_clear_scheduled_hook('recombee_purge_transients_cron');

delete_option( 'recombee_blog_settings_preset' );
delete_option( 'recombee_sync_wc_products_prop_settings_preset' );
delete_option( 'recombee_sync_wc_customers_prop_settings_preset' );
delete_option( 'recombee_sync_wc_products_settings_preset' );
delete_option( 'recombee_sync_wc_customers_settings_preset' );
delete_option( 'recombee_sync_wc_interactions_settings_preset' );
delete_option( 'recombee_sync_db_reset_settings_preset' );

delete_option( 'widget_recombee_recommends_widget' );
@unlink(WPMU_PLUGIN_DIR . '/class-RecombeeReWpAjaxDispatcher.php');
	
if( is_multisite()){
	
	delete_site_option( 'recombee_site_settings_preset' );
	
	$sites = get_sites();
	foreach( $sites as $site ){
		
		delete_blog_option($site->blog_id, 'recombee_blog_settings_preset');
		delete_blog_option($site->blog_id, 'recombee_sync_wc_products_prop_settings_preset');
		delete_blog_option($site->blog_id, 'recombee_sync_wc_customers_prop_settings_preset');
		delete_blog_option($site->blog_id, 'recombee_sync_wc_products_settings_preset');
		delete_blog_option($site->blog_id, 'recombee_sync_wc_customers_settings_preset');
		delete_blog_option($site->blog_id, 'recombee_sync_wc_interactions_settings_preset');
		delete_blog_option($site->blog_id, 'recombee_sync_db_reset_settings_preset');
		
		delete_blog_option($site->blog_id, 'widget_recombee_recommends_widget');
	}
}