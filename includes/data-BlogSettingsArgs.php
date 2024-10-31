<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$dbSyncInteractionsOvercome = 0;

if( $this->prodPropSyncSetting['completed'] ){
	$dbSyncInteractionsOvercome++;
}
if( $this->custPropSyncSetting['completed'] ){
	$dbSyncInteractionsOvercome++;
}
if( $this->prodSyncSetting['completed'] ){
	$dbSyncInteractionsOvercome++;
}
if( $this->custSyncSetting['completed'] ){
	$dbSyncInteractionsOvercome++;
}

$blogSettingsArgs = array(
	'page_title'		=> __( 'Recombee Sample Shop', 'recombee-recommendation-engine' ),
	'warning'			=> RRE_WC_IS_ACTIVE,
	'menu_name'			=> __( 'Recombee', 'recombee-recommendation-engine' ),
	'page_slug'			=> RRE_MENU_PAGE_SLUG,
	'page_h2'			=> __( 'Manage Recombee Sample Shop', 'recombee-recommendation-engine' ),
	'capability'		=> 'manage_options',
	'menu_type'			=> 'single', /* or network */
	'setting_preset'	=> RRE_BLOG_SETTINGS_PRESET_NAME,
	'dashicons'			=> RRE_PLUGIN_URL . '/includes/assets/css/images/recombee.png',
	'priority'			=> 110,
	'active_tab'		=> ( $this->blogSetting['invite_init_sync'] ) ? '0' : '1',
	'controls'			=> array(
	__( 'Credentials', 'recombee-recommendation-engine' )	=> array(
			'id'	=> 'one',
			'controls'	=> array(
				array(
					'id'			=> 'api_identifier',
					'name'			=> 'api_identifier',
					'title'			=> __( 'ID of your database at Recombee', 'recombee-recommendation-engine' ),
					'placeholder'	=> sprintf( __( 'Create a database at %s, then type its ID here', 'recombee-recommendation-engine' ), RRE_URL_SERVICE),
					'tip'			=> sprintf( __( 'This is the database which will keep all the information about products, users and interactions. It should be created at %s', 'recombee-recommendation-engine' ), '<span class="solid blue"><a href="'.RRE_URL_SERVICE.'" target="_blank">Recombee.com</a></span>'),
					'disable'		=> (!$this->dbConnectLost && (int)$this->blogSetting['db_connection_code'] === RRE_DB_CONNECTED_CODE) ? true : false,
					'class'			=> array( ( $this->blogSetting['invite_init_sync'] ) ? 'setup-credentials' : '' ),
					'type'			=> 'text',
				),
				array(
					'id'			=> 'api_secret_token',
					'name'			=> 'api_secret_token',
					'title'			=> __( 'Recombee Secret Key', 'recombee-recommendation-engine' ),
					'placeholder'	=> __( 'example: vGFbA4A7g1ubM0hkkMUGtTTCeftjhIAbTfRnA8M7koIyRuhDU3UmKnEkn1P4WUR6', 'recombee-recommendation-engine' ),
					'tip'			=> sprintf( __( 'Copy here the secret key that you received at %s.', 'recombee-recommendation-engine' ), '<span class="solid blue"><a href="'.RRE_URL_SERVICE.'" target="_blank">Recombee.com</a></span>' ),
					'disable'		=> (!$this->dbConnectLost && (int)$this->blogSetting['db_connection_code'] === RRE_DB_CONNECTED_CODE) ? true : false,
					'class'			=> array( ( $this->blogSetting['invite_init_sync'] ) ? 'setup-credentials' : '' ),
					'type'			=> 'text',
				),
				array(
					'id'			=> 'db_connection',
					'name'			=> 'db_connection_code',
					'title'			=> __( 'Recombee Database Status', 'recombee-recommendation-engine' ),
					'disable'		=> ($this->blogSetting['api_identifier'] && $this->blogSetting['api_secret_token']) ? false : true,
					'data'			=> array(
						'status-code'	=> ($this->dbConnectLost) ? 'lost' : $this->blogSetting['db_connection_code'],
						'nonce' 		=> wp_create_nonce( 'dbConnectionAction' ),
						'action' 		=> 'dbConnectionAction',
					),
					'value'			=> ($this->dbConnectLost) ? $this->dbConnectValue(false, true) : $this->dbConnectValue(),
					'type'			=> 'button',
				),
				array(
					'callback'		=> array($this, 'recombeeIframe'),
					'type'			=> 'content',
				),
			),
		),
	__( 'Database', 'recombee-recommendation-engine' )	=> array(
			'id'	=> 'two',
			'controls'	=> array(
				array(
					'id'			=> 'db_sync_product_prop',
					'name'			=> 'db_sync_product_prop',
					'title'			=> __( 'WC Product Properties synchronization', 'recombee-recommendation-engine' ),
					'tip'			=> array($this, 'dbProductSetSyncTip'),
					'disable'		=> ($this->dbConnectLost || $this->blogSetting['db_connection_code'] === RRE_DB_DISCONNECTED_CODE) ? true : false,
					'data'			=> array(
						'nonce' 	=> wp_create_nonce( 'dbSyncWcProductsProp' ),
						'action' 	=> 'dbSyncWcProductsProp',
					),
					'value'			=> __('Launch synchronization', 'recombee-recommendation-engine'),
					'type'			=> 'button',
				),
				array(
					'id'			=> 'db_sync_customer_prop',
					'name'			=> 'db_sync_customer_prop',
					'title'			=> __( 'WC Customer Properties synchronization', 'recombee-recommendation-engine' ),
					'tip'			=> array($this, 'dbCustomerSetSyncTip'),
					'disable'		=> ($this->dbConnectLost || $this->blogSetting['db_connection_code'] === RRE_DB_DISCONNECTED_CODE) ? true : false,
					'data'			=> array(
						'nonce' 	=> wp_create_nonce( 'dbSyncWcCustomersProp' ),
						'action' 	=> 'dbSyncWcCustomersProp',
					),
					'value'			=> __('Launch synchronization', 'recombee-recommendation-engine'),
					'type'			=> 'button',
				),
				array(
					'id'			=> 'db_sync_wc_products',
					'name'			=> 'db_sync_wc_products',
					'title'			=> __( 'WC Products synchronization', 'recombee-recommendation-engine' ),
					'tip'			=> array($this, 'dbProductSyncTip'),
					'disable'		=> ($this->dbConnectLost || $this->blogSetting['db_connection_code'] === RRE_DB_DISCONNECTED_CODE) ? true : false,
					'data'			=> array(
						'nonce' 	=> wp_create_nonce( 'dbSyncWcProducts' ),
						'action' 	=> 'dbSyncWcProducts',
					),
					'value'			=> __('Launch synchronization', 'recombee-recommendation-engine'),
					'type'			=> 'button',
				),
				array(
					'id'			=> 'db_sync_wc_customers',
					'name'			=> 'db_sync_wc_customers',
					'title'			=> __( 'WC Customers synchronization', 'recombee-recommendation-engine' ),
					'tip'			=> array($this, 'dbCustomersSyncTip'),
					'disable'		=> ($this->dbConnectLost || $this->blogSetting['db_connection_code'] === RRE_DB_DISCONNECTED_CODE) ? true : false,
					'data'			=> array(
						'nonce' 	=> wp_create_nonce( 'dbSyncWcCustomers' ),
						'action' 	=> 'dbSyncWcCustomers',
					),
					'value'			=> __('Launch synchronization', 'recombee-recommendation-engine'),
					'type'			=> 'button',
				),
				array(
					'id'			=> 'db_sync_wc_interactions',
					'name'			=> 'db_sync_wc_interactions',
					'title'			=> __( 'WC Interactions synchronization', 'recombee-recommendation-engine' ),
					'tip'			=> array($this, 'dbInteractionsSyncTip'),
					'disable'		=> ($this->dbConnectLost || $this->blogSetting['db_connection_code'] === RRE_DB_DISCONNECTED_CODE) ? true : false,
					'data'			=> array(
						'nonce' 	=> wp_create_nonce( 'dbSyncWcInteractions' ),
						'action' 	=> 'dbSyncWcInteractions',
					),
					'value'			=> __('Launch synchronization', 'recombee-recommendation-engine'),
					'type'			=> 'button',
				),
				array(
					'id'			=> 'db_reset',
					'name'			=> 'db_reset',
					'title'			=> __( 'Recombee Database Reset', 'recombee-recommendation-engine' ),
					'tip'			=> array($this, 'dbResetTip'),
					'disable'		=> ($this->dbConnectLost || $this->blogSetting['db_connection_code'] === RRE_DB_DISCONNECTED_CODE) ? true : false,
					'class'			=> array('inactive'),
					'data'			=> array(
						'nonce' 	=> wp_create_nonce( 'dbReset' ),
						'action' 	=> 'dbReset',
						'overcome' 	=> $dbSyncInteractionsOvercome,
					),
					'value'			=> __('Launch Reset', 'recombee-recommendation-engine'),
					'type'			=> 'button',
				),
			),
		),
	__( 'Settings', 'recombee-recommendation-engine' )	=> array(
			'id'	=> 'three',
			'controls'	=> array(
				array(
					'id'			=> 'db_sync_chunk_size',
					'name'			=> 'db_sync_chunk_size',
					'title'			=> __( 'Synchronization Chunk Size', 'recombee-recommendation-engine' ),
					'placeholder'	=> __( 'Integer greater then zero', 'recombee-recommendation-engine' ),
					'tip'			=> array($this, 'dbSyncPace'),
					'disable'		=> false,
					'min'			=> 1,
					'max'			=> false,
					'step'			=> 1,
					'value'			=> $this->blogSetting['db_sync_chunk_size'],
					'type'			=> 'number',
				),
				array(
					'id'			=> 'debug_mode',
					'name'			=> 'debug_mode',
					'title'			=> __( 'Debug Mode', 'recombee-recommendation-engine' ),
					'tip'			=> __( 'If the block with the recommended goods is empty and you do not understand why - turn on the debugging mode. The lack of recommendations may be due to errors in the requests sent to Recombee Engine. In debug mode, information about the errors will be displayed at the front-end.', 'recombee-recommendation-engine' ),
					'class'			=> array('recombee-toggle'),
					'descr'			=> '',//__( 'Switch ON', 'recombee-recommendation-engine' ),
					'type'			=> 'checkbox',
				),
				array(
					'id'			=> 'log_requests_err',
					'name'			=> 'log_requests_err',
					'title'			=> __( 'Log Requests Errors', 'recombee-recommendation-engine' ),
					'tip'			=> __( 'WIth this option on, all the erroneous responses from Recombee will be logged to ../log/requests-errors.log inside the plugin’s folder on your hosting server.', 'recombee-recommendation-engine' ),
					'class'			=> array('recombee-toggle'),
					'descr'			=>'',// __( 'Switch ON', 'recombee-recommendation-engine' ),
					'type'			=> 'checkbox',
				),
				array(
					'id'			=> 'wc_override_related_products',
					'name'			=> 'wc_override_related_products',
					'title'			=> __( 'WC Related Products Override', 'recombee-recommendation-engine' ),
					'tip'			=> __( 'With this option on, native WC Related Products are replaced with the Recombee recommendations at all the product pages. .The recommendations are requested according to shortcode in WC Related Shortcode field.', 'recombee-recommendation-engine' ),
					'class'			=> array('recombee-toggle'),
					'descr'			=> '',//__( 'Switch ON', 'recombee-recommendation-engine' ),
					'type'			=> 'checkbox',
				),
				array(
					'id'			=> 'wc_override_related_tags',
					'name'			=> 'wc_override_related_tags',
					'title'			=> __( 'WC Related Tags', 'recombee-recommendation-engine' ),
					'placeholder'	=> __( 'Type & press Space', 'recombee-recommendation-engine' ),
					'tip'			=> __( 'Example: "woocommerce_after_single_product_summary" - Storefront Theme, "sf_after_single_product_reviews" - Uplift Theme', 'recombee-recommendation-engine' ),
					'disable'		=> false,
					'type'			=> 'text',
					'data'			=> array(
						'taged'		=> 'true',
					),
				),
				array(
					'id'			=> 'wc_overridden_related_shortcode',
					'name'			=> 'wc_overridden_related_shortcode',
					'title'			=> __( 'WC Related Shortcode', 'recombee-recommendation-engine' ),
					'placeholder'	=> __( 'Type any valid recommendation shortcode. Click Help Tab to get information on how the shortcodes works.', 'recombee-recommendation-engine' ),
					'tip'			=> __( 'Example: [RecombeeRecommendations ajaxMode="on" wTitle="Recommended for You" type="ProductsToProduct" count="3" scenario="related_products"]', 'recombee-recommendation-engine' ),
					'disable'		=> false,
					'type'			=> 'textarea',
				),
				array(
					'id'			=> 'distinct_recomms',
					'name'			=> 'distinct_recomms',
					'title'			=> __( 'Distinct recommendations', 'recombee-recommendation-engine' ),
					'tip'			=> __( 'If you use multiple widgets/shortcodes at a single page, enabling this option will ensure that no item will be recommended in multiple boxes. NOTE! This options applies only for AJAX requested recommendations.', 'recombee-recommendation-engine' ),
					'class'			=> array('recombee-toggle'),
					'descr'			=> '',//__( 'Switch ON', 'recombee-recommendation-engine' ),
					'type'			=> 'checkbox',
				),
			),
		),
	),
);

$prod_prop_set = array(
	array(
		'id'			=> 'db_product_prop_set',
		'name'			=> 'db_product_prop_set',
		'title'			=> '<div>' . __( 'WC Product Properties', 'recombee-recommendation-engine' ) . '</div><button id="toggle_all_prod_prop">' . __( 'Toggle All', 'recombee-recommendation-engine' ) . '</button>',
		'default'		=> __( 'Choose properties', 'recombee-recommendation-engine' ),
		'default_value'	=> 0,
		'tip'			=> array($this, 'dbProductSetPropTip'),
		'disable'		=> false,
		'class'			=> array( ($this->blogSetting['invite_init_sync'] ) ? 'setup-prop' : ''),
		'type'			=> 'select',
		'select_multi'	=> true,
		'options'		=> $this->recombee->get_product_sync_prop_select(),
	),
);
$cust_prop_set = array(
	array(
		'id'			=> 'db_customer_prop_set',
		'name'			=> 'db_customer_prop_set',
		'title'			=> '<div>' . __( 'WC Customer Properties', 'recombee-recommendation-engine' ) . '</div><button id="toggle_all_cust_prop">' . __( 'Toggle All', 'recombee-recommendation-engine' ) . '</button>',
		'default'		=> __( 'Choose properties', 'recombee-recommendation-engine' ),
		'default_value'	=> 0,
		'tip'			=> array($this, 'dbCustomerSetPropTip'),
		'disable'		=> false,
		'class'			=> array( ($this->blogSetting['invite_init_sync'] ) ? 'setup-prop' : ''),
		'type'			=> 'select',
		'select_multi'	=> true,
		'options'		=> $this->recombee->get_customer_sync_prop_select(),
	),
);
$keys = array_keys($blogSettingsArgs['controls']);

if( (int)$this->blogSetting['invite_init_sync'] === 1 ){
	array_splice( $blogSettingsArgs['controls'][$keys[0]]['controls'], 2, 0, $prod_prop_set);
	array_splice( $blogSettingsArgs['controls'][$keys[0]]['controls'], 3, 0, $cust_prop_set);
}
else{
	array_splice( $blogSettingsArgs['controls'][$keys[1]]['controls'], 0, 0, $prod_prop_set);
	array_splice( $blogSettingsArgs['controls'][$keys[1]]['controls'], 1, 0, $cust_prop_set);
}
unset($prod_prop_set);
unset($cust_prop_set);
unset($keys);

$blogSettingsArgs['controls'] = apply_filters( 'RecombeeReBlogSettingsControls', $blogSettingsArgs['controls'] );