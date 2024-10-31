<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Recombee setup
 *
 * @package  Recombee
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Recombee Class.
 *
 * @class Recombee
 */
final class RecombeeRe {

	/**
	* The single instance of the class.
	*
	* @var object RecombeeRe
	*/
	
	public $communicator	= null;
	public $menu			= null;
	public $productStatuses	= array();
	public $recommsDefault	= array();
	public $orderStatuses	= array();
	public $realPost		= null;
	
	private static $_instance		= null;
	private static $dbConnectLost	= null;
	private static $RAUID			= null;
	
	public $rre_ajax_interface		= null;
	private $setting_blog			= null;
	private $setting_site			= null;
	private $default_blog_setting	= array();
	private $default_site_setting	= array();	
	
	
	private static $sync_wc_products_prop_setting	= null;
	private static $sync_wc_customers_prop_setting	= null;
	
	private static $sync_wc_products_setting		= null;
	private static $sync_wc_customers_setting		= null;
	private static $sync_wc_interactions_setting	= null;
	private static $sync_db_reset_setting			= null;
	
	private static $sync_wc_products_by_prop		= null;
	private static $sync_wc_customers_by_prop		= null;
	private static $doubled_prop_keys				= array();

	
	
	/**
	* Recombee Constructor.
	*/
	private function __construct() {
			
		$this->define_constants();
		$this->setupMuModules();
		
		$this->productStatuses	= array('trash', 'draft', 'pending', 'private', 'publish');
		$this->orderStatuses	= array('trash', 'pending', 'processing', 'on-hold', 'completed', 'refunded', 'failed', 'cancelled'); /* not used for now */
		$this->recommsDefault	= array(
			'ajaxMode'			=> 'on',
			'parentsOnly'		=> 'on',
			'followThemeCss'	=> 'on',
			'suppressLogic'		=> 'off',
			'suppressSubject'	=> 'posts',
			'suppressPosts'		=> array(),
			'wTitle'			=> __( 'Recommended for You', 'recombee-recommendation-engine' ),
			'columns'			=> 3,
			'type'				=> 'ProductsToProduct',
			'count'				=> 4,
			'scenario'			=> 'related_products',
			'userImpact'		=> 0,
			'filter_json'		=>'{"condition":"AND","rules":[{"id":"wcProductIsVisible","field":"wcProductIsVisible","type":"boolean","input":"select","operator":"equal","value":[true]},{"id":"wcIsInStock","field":"wcIsInStock","type":"boolean","input":"select","operator":"equal","value":[true]}],"valid":true}',
			'filter_reql'		=> '\'wcProductIsVisible\' == true AND \'wcIsInStock\' == true',
			'booster_json'		=> '{"condition":"AND","rules":[{"id":"wcRegularPrice","field":"wcRegularPrice","type":"double","input":"select","operator":"greater","value":["context_item"],"data":{"rhs":{"selected":{"rule_id":"recombee-query-builder_rule_0","filter_id":"context_item","items":["wcRegularPrice"]}}}}],"valid":true}',
			'booster_reql'		=> '\'wcRegularPrice\' > context_item["wcRegularPrice"]',
			'booster_then'		=> 1.5,
			'booster_else'		=> 1,
			'cascadeCreate'		=> false,
			'returnProperties'	=> false,
			'includedProperties'=> '',
			'diversity'			=> null,
			'minRelevance'		=> 'low',
			'rotationRate'		=> null,
			'rotationTime'		=> null,
			'_stuff_data'		=> array(),
			'_illegal_params'	=> array(),
		);
		self::$RAUID			= $this->set_RAUID();
		
		$this->includes();
		$this->init_hooks();
	}
	
	private function init_hooks(){
		
		register_activation_hook( RRE_PLUGIN_FILE, array($this, 'on_activation') );
		register_deactivation_hook( RRE_PLUGIN_FILE, array($this, 'on_deactivation') );
			
		add_action('init',								array($this, 'settings'					), 10 );
		add_action('admin_init',						array($this, 'setup_warning_status'		), 10 );
		add_action('admin_init',						array($this, 'set_permalinks'			), 20 );
		add_action('admin_head',						array($this, 'modal_warning'			), 10 );
		add_action('widgets_init',						array($this, 'widgets'					), 10 );
		add_action('admin_notices',						array($this, 'admin_notices'			), 15 );
		add_action('plugins_loaded',					array($this, 'load_translation'			), 10 );
		add_action('wp_enqueue_scripts',				array($this, 'jsCssFront'				), 10 );
		add_action('admin_enqueue_scripts',				array($this, 'jsCssAdmin'				), 10 );
		add_action('recombee_purge_transients_cron',	array($this, 'purge_transients_cron'	));
		
		add_filter('clean_url',							array($this, 'async_attr_add'			), 10, 3 );
		add_filter('plugin_row_meta',					array($this, 'plugin_row_meta'			), 10, 4 );
		
		/* ----NOTE----- */
		add_action( 'wp',								array($this, 'setPost' ),	10);
		
		/* add_action('in_plugin_update_message-recombee-recommendation-engine/recombee-re.php', array( $this, 'in_plugin_update_message' ), 10, 2 ); */
	}
	
	public function setPost() {

		remove_action( 'pre_get_posts', array($this, 'setPost' ), 10);
		
		if( is_archive() ){
			
			global $wp_query;
			
			$archive_slug = $wp_query->get_queried_object()->has_archive;
			$this->realPost = get_page_by_path($archive_slug);
			
		}
		else{
			$this->realPost = get_post();
		}
	}
	
	public function get_RAUID() {
		
		return self::$RAUID;
	}
	
	private function set_RAUID() {
		
		if( is_null(self::$RAUID) && $this->is_frontend() ){
			
			if( isset($_COOKIE['RAUID']) && strpos($_COOKIE['RAUID'], 'rauid-') !== false ){

				self::$RAUID = $_COOKIE['RAUID'];
			}
			else{
				
				$domain = parse_url(home_url(), PHP_URL_HOST);
				$rauid = uniqid('rauid' . '-' . $domain .'-', true);
				
				/*
				*  on very first visit site there are no cookie on client
				*  lets do like they are exists
				*/
				$_COOKIE['RAUID'] = $rauid;
				
				self::$RAUID = $rauid;
			}
		}
		return self::$RAUID;
	}
	
	public function get_blog_setting($preset){
		
		switch( $preset ){
			
			case RRE_BLOG_SETTINGS_PRESET_NAME			: return $this->setting_blog;
			case RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME	: return self::$sync_wc_products_prop_setting;
			case RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME	: return self::$sync_wc_customers_prop_setting;
			case RRE_SYNC_WC_PRODUCTS_PRESET_NAME		: return self::$sync_wc_products_setting;		
			case RRE_SYNC_WC_CUSTOMERS_PRESET_NAME		: return self::$sync_wc_customers_setting;
			case RRE_SYNC_WC_INTERACTIONS_PRESET_NAME	: return self::$sync_wc_interactions_setting;
			case RRE_SYNC_DB_RESET_PRESET_NAME			: return self::$sync_db_reset_setting;
		}
	}
	
	public function set_blog_setting($preset, $new_setting) {
		
		$old_setting = $this->get_blog_setting($preset);
		$new_setting = wp_parse_args( $new_setting, $old_setting );
		
		update_option($preset, $new_setting);
		
		switch( $preset ){
			
			case RRE_BLOG_SETTINGS_PRESET_NAME:
				$this->setting_blog = $new_setting;
				break;
			case RRE_SYNC_WC_PRODUCTS_PRESET_NAME:
				self::$sync_wc_products_setting = $new_setting;
				break;
			case RRE_SYNC_WC_CUSTOMERS_PRESET_NAME:
				self::$sync_wc_customers_setting = $new_setting;
				break;
			case RRE_SYNC_WC_INTERACTIONS_PRESET_NAME:
				self::$sync_wc_interactions_setting = $new_setting;
				break;
			case RRE_SYNC_DB_RESET_PRESET_NAME:
				self::$sync_db_reset_setting = $new_setting;
				break;
		}
		return $new_setting;
	}
	
	public function get_site_setting() {
		
		return $this->setting_site;
	}
	
	public function set_site_setting($new_setting) {
		
		$old_setting = $this->get_site_setting();
		$new_setting = wp_parse_args( $new_setting, $old_setting );
		
		update_site_option(RRE_SITE_SETTINGS_PRESET_NAME, $new_setting);
		return $new_setting;
	}
	
	public function get_default_settings($scope) {
		
		if($scope == 'blog'){
			return $this->default_blog_setting;
		}
		if($scope == 'site'){
			return $this->default_site_setting;
		}
	}
	
	private function initialize_blog_setting(){
		
		/* BLOG DATA */
		$current_blog_setting = get_option(RRE_BLOG_SETTINGS_PRESET_NAME, array());
		$this->default_blog_setting = array(
			'version'							=> 1,
			'show_admin_modal'					=> 0,
			'last_activated_version'			=> 0,
			'rewrite_rules_flushed_once'		=> 0,
			'invite_init_sync'					=> true,
			'api_identifier'					=> false,
			'api_secret_token'					=> false,
			'db_connection_code'				=> RRE_DB_DISCONNECTED_CODE,
			'db_product_prop_set'				=> $this->get_product_sync_prop_keys(true),
			'db_customer_prop_set'				=> $this->get_customer_sync_prop_keys(true),
			'db_sync_chunk_size'				=> 100,
			'debug_mode'						=> 0,
			'log_requests_err'					=> 0,
			'wc_override_related_products'		=> 1,
			'wc_override_related_tags'			=> 'woocommerce_after_single_product_summary',
			'wc_overridden_related_shortcode'	=> '[RecombeeRecommendations ajaxMode="on" parentsOnly="on" wTitle="Recommended for You" columns="3" type="ProductsToProduct" count="3" scenario="related_products" filter="\'wcProductIsVisible\' == true AND \'wcIsInStock\' == true" booster="if \'wcRegularPrice\' > context_item{`wcRegularPrice`} then 1.5 else 1"]',
			'distinct_recomms'					=> 1,
		);
		
		/* PRODUCTS PROPERTIES SYNC */
		$current_sync_wc_products_prop_setting = get_option(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, array());
		$default_sync_wc_products_prop_setting = array(
			'version'						=> 1,
			'current_sync_offset'			=> 0,
			'loop_errors'					=> 0,
			'completed'						=> false,
		);
		
		/* CUSTOMERS PROPERTIES SYNC */
		$current_sync_wc_customers_prop_setting = get_option(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME, array());
		$default_sync_wc_customers_prop_setting = array(
			'version'						=> 1,
			'current_sync_offset'			=> 0,
			'loop_errors'					=> 0,
			'completed'						=> false,
		);
		
		/* PRODUCTS SYNC */
		$current_sync_wc_products_setting = get_option(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, array());
		$default_sync_wc_products_setting = array(
			'version'						=> 1,
			'current_sync_offset'			=> 0,
			'loop_errors'					=> 0,
			'is_on_sync'					=> 0,
			'completed'						=> false,
		);	
		
		/* CUSTOMERS SYNC */
		$current_sync_wc_customers_setting = get_option(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME, array());
		$default_sync_wc_customers_setting = array(
			'version'						=> 1,
			'current_sync_offset'			=> 0,
			'loop_errors'					=> 0,
			'is_on_sync'					=> 0,
			'completed'						=> false,
		);	
		
		/* INTERACTIONS SYNC */
		$current_sync_wc_interactions_setting = get_option(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, array());
		$default_sync_wc_interactions_setting = array(
			'version'						=> 1,
			'purchases_sync_offset'			=> 0,
			'detail_view_sync_offset'		=> 0,
			'cart_add_sync_offset'			=> 0,
			'rating_sync_offset'			=> 0,
			'loop_errors'					=> 0,
			'current_sync_offset'			=> 0,
			'is_on_sync'					=> 0,
			'completed'						=> false,
		);
	
		/* DB RESET SYNC */
		$current_db_reset_setting = get_option(RRE_SYNC_DB_RESET_PRESET_NAME, array());
		$default_db_reset_setting = array(
			'version'						=> 1,
			'completed'						=> false,
		);

		if( count($current_blog_setting) > 0 && version_compare($current_blog_setting['version'], $this->default_blog_setting['version'], '<')) {
			
			if( isset($current_blog_setting['wc_overriden_related_shortcode']) ){
				unset($current_blog_setting['wc_overriden_related_shortcode']);
			}
			if( isset($current_blog_setting['db_sync_wc_products'])	){
				$default_sync_wc_products_setting['completed'] = $current_blog_setting['db_sync_wc_products'];
				unset($current_blog_setting['db_sync_wc_products']);
			}
			if( isset($current_blog_setting['db_sync_wc_customers'])	){
				$default_sync_wc_customers_setting['completed'] = $current_blog_setting['db_sync_wc_customers'];
				unset($current_blog_setting['db_sync_wc_customers']);
			}
			if( isset($current_blog_setting['db_sync_wc_interactions'])	){
				$default_sync_wc_interactions_setting['completed'] = $current_blog_setting['db_sync_wc_interactions'];
				unset($current_blog_setting['db_sync_wc_interactions']);
			}
			if( isset($current_blog_setting['db_reset'])	){
				$default_sync_product_prop_setting['completed'] = $current_blog_setting['db_reset'];
				unset($current_blog_setting['db_reset']);
			}
			
			$current_blog_setting['version'] = $this->default_blog_setting['version'];
		}
		if( isset($current_blog_setting['db_product_prop_set']) ){
			
			$current_blog_setting['db_product_prop_set'] = (array)$current_blog_setting['db_product_prop_set'];
			
			foreach($current_blog_setting['db_product_prop_set'] as $key => $property){
				if( !array_key_exists($property, (array)self::$sync_wc_products_by_prop ) ){
					unset($current_blog_setting['db_product_prop_set'][ $key ]);
				}
			}
			foreach($this->get_product_sync_prop_keys(true) as $property){
				if(!in_array($property, $current_blog_setting['db_product_prop_set'])){
					$current_blog_setting['db_product_prop_set'][] = $property;
				}
			}
		}
		if( isset($current_blog_setting['db_customer_prop_set']) ){
			
			$current_blog_setting['db_customer_prop_set'] = (array)$current_blog_setting['db_customer_prop_set'];
			
			foreach($current_blog_setting['db_customer_prop_set'] as $key => $property){
				if( !array_key_exists($property, (array)self::$sync_wc_customers_by_prop ) ){
					unset($current_blog_setting['db_customer_prop_set'][ $key ]);
				}
			}
			foreach($this->get_customer_sync_prop_keys(true) as $property){
				if(!in_array($property, $current_blog_setting['db_customer_prop_set'])){
					$current_blog_setting['db_customer_prop_set'][] = $property;
				}
			}
		}
		
		$this->setting_blog						= wp_parse_args( $current_blog_setting, $this->default_blog_setting );
		
		self::$sync_wc_products_prop_setting	= wp_parse_args( $current_sync_wc_products_prop_setting, $default_sync_wc_products_prop_setting );
		self::$sync_wc_customers_prop_setting	= wp_parse_args( $current_sync_wc_customers_prop_setting, $default_sync_wc_customers_prop_setting );
		self::$sync_wc_products_setting			= wp_parse_args( $current_sync_wc_products_setting, $default_sync_wc_products_setting );
		self::$sync_wc_customers_setting		= wp_parse_args( $current_sync_wc_customers_setting, $default_sync_wc_customers_setting );
		self::$sync_wc_interactions_setting 	= wp_parse_args( $current_sync_wc_interactions_setting, $default_sync_wc_interactions_setting );
		self::$sync_db_reset_setting			= wp_parse_args( $current_db_reset_setting, $default_db_reset_setting );
		
		update_option(RRE_BLOG_SETTINGS_PRESET_NAME,		$this->setting_blog	);
		
		update_option(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME,	self::$sync_wc_products_prop_setting );
		update_option(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME,	self::$sync_wc_customers_prop_setting );
		update_option(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, 		self::$sync_wc_products_setting );
		update_option(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME,		self::$sync_wc_customers_setting );
		update_option(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME,		self::$sync_wc_interactions_setting);
		update_option(RRE_SYNC_DB_RESET_PRESET_NAME,			self::$sync_db_reset_setting );
		
	}
	
	private function initialize_site_setting(){
		
		/* SITE DATA */
		$current_site_setting = get_option(RRE_SITE_SETTINGS_PRESET_NAME, array());
		
		if( is_multisite() ){
			$this->setting_site = wp_parse_args( $current_site_setting, $this->default_site_setting );
			update_option(RRE_SITE_SETTINGS_PRESET_NAME, $this->setting_site);
		}
		else{
			$this->setting_site = new WP_Error( 'not_multisite', __('This site is standalone, no multisite') );;
		}
	}
	
	public function set_permalinks(){
		
		if ( isset($_GET['rre_activated']) && $_GET['rre_activated'] == true ) {
			flush_rewrite_rules();
		}
	}
	
	public function setup_warning_status(){
		
		/*
		*  approach via GET not work due to WP allows user update plugins
		*  via AJAX on plugin's page
		*
		*  if ( isset($_GET['rre_activated']) && $_GET['rre_activated'] == true ) {}
		*/
		
		/* 
		*  This hook linked to admin_init event due to use get_plugin_data()
		*  So it will not fire in frontend!!
		*/

		$blog_setting	= $this->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		$plugin_data	= get_plugin_data(RRE_PLUGIN_FILE, false,false);
		
		if( version_compare($plugin_data['Version'], $blog_setting['last_activated_version'], '>') ){
			
			global $wp_rewrite;
			
			if( $plugin_data['Version'] == '2.2.4' || $plugin_data['Version'] == '2.6.0' ){
				$this->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME, array(
					'show_admin_modal' => 1
				));
			}
		}
		
		if( $blog_setting['rewrite_rules_flushed_once'] == 'queued' ){
			
			$this->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME, array(
				'rewrite_rules_flushed_once' => 1
			));
			
			$wp_rewrite->flush_rules();
		}
		
		$this->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME, array(
			'last_activated_version' => $plugin_data['Version']
		));
	}
	
	private function set_product_sync_prop(){
		
		require RRE_ABSPATH . 'includes/data-ProductSettingsArgs.php';
		
		foreach($product_prop as $slug => $tax_data){
			
			$reSlug = $this->product_prop_name_to_recombee_format($slug);
			
			$product_prop[$reSlug] = $tax_data;
			unset($product_prop[$slug]);
		}
		
		$united_props = array_merge($static_prop, $product_prop);
		
		foreach($united_props as $prop => $united_prop){
			
			if($united_props[$prop]['queryBuilder']){
				
				$united_props[$prop]['queryBuilder']['plugin'] ='selectpicker';
				$united_props[$prop]['queryBuilder']['plugin_config'] = array(
					'container' 			=> 'body',
					'width'					=> 'auto',
					'style'					=> 'btn-xs',
					'selectedTextFormat'	=> 'count > 1',
					'noneSelectedText'		=> '----',
					'tickIcon'				=> 'far fa-check-square',
				);
			}
		}
		self::$sync_wc_products_by_prop = $united_props;
	}
	
	private function set_customer_sync_prop(){
		
		require RRE_ABSPATH . 'includes/data-CustomerSettingsArgs.php';
		
		foreach($customer_prop as $id => $name){
			
			if($customer_prop[$id]['queryBuilder']){

				$customer_prop[$id]['queryBuilder']['plugin'] ='selectpicker';
				$customer_prop[$id]['queryBuilder']['plugin_config'] = array(
					'container' 			=> 'body',
					'width'					=> 'auto',
					'style'					=> 'btn-xs',
					'selectedTextFormat'	=> 'count > 1',
					'noneSelectedText'		=> '----',
					'tickIcon'				=> 'far fa-check-square',
				);
			}
		}
		self::$sync_wc_customers_by_prop = $customer_prop;
	}
	
	public function get_product_sync_prop_all(){
		
		return self::$sync_wc_products_by_prop;
	}
	
	public function get_customer_sync_prop_all(){
		
		return self::$sync_wc_customers_by_prop;
	}
	
	public function get_product_sync_prop_select(){
		
		$groups_array = array();
		$groups_flat  = array();
		
		foreach(self::$sync_wc_products_by_prop as $prop_name => $prop_data){
			$groups_array[ $prop_data['optgroup'] ][$prop_name] = $prop_data['view_name'];
		}
		foreach($groups_array as $group_name => $group_data){
			$groups_flat[ $group_name ] = 'optgroup';
			foreach($group_data as $val => $select){
				$groups_flat[ $val  ] = $select;
			}
		}
		
		return $groups_flat;
	}
	
	public function get_customer_sync_prop_select(){
		
		$groups_array = array();
		$groups_flat  = array();
		
		foreach(self::$sync_wc_customers_by_prop as $prop_name => $prop_data){
			$groups_array[ $prop_data['optgroup'] ][$prop_name] = $prop_data['view_name'];
		}
		foreach($groups_array as $group_name => $group_data){
			$groups_flat[ $group_name ] = 'optgroup';
			foreach($group_data as $val => $select){
				$groups_flat[ $val  ] = $select;
			}
		}
		
		return $groups_flat;
	}
	
	public function get_product_sync_prop_keys($builtinOnly = false){
		
		$return = array();
		
		if(!$builtinOnly){
			foreach(self::$sync_wc_products_by_prop as $prop_name => $prop_data){
				$return[] = $prop_name;
			}
		}
		else{
			foreach(self::$sync_wc_products_by_prop as $prop_name => $prop_data){
				
				if($prop_data['builtin'] === true){
					$return[] = $prop_name;
				}
			}
		}
		return $return;
	}
	
	public function get_customer_sync_prop_keys($builtinOnly = false){
		
		$return = array();
		
		if(!$builtinOnly){
			foreach(self::$sync_wc_customers_by_prop as $prop_name => $prop_data){
				$return[] = $prop_name;
			}
		}
		else{
			foreach(self::$sync_wc_customers_by_prop as $prop_name => $prop_data){
				
				if($prop_data['builtin'] === true){
					$return[] = $prop_name;
				}
			}
		}
		return $return;
	}
	
	public function product_prop_name_to_recombee_format($slug){
		
		$reSlug = ucwords($slug, '_');
		$reSlug = str_replace('_', '', $reSlug);
		$reSlug = str_replace('::', '_', $reSlug);
		$reSlug = substr_replace ($reSlug, 'wc' . $reSlug, 0);
		
		return $reSlug;
	}
	
	/**
	* Main Recombee Instance.
	*
	* Ensures only one instance of Recombee is loaded or can be loaded.
	*
	* @since 2.1
	* @static
	* @see recombee()
	* @return object RecombeeRe - Main instance.
	*/
	public static function instance(){
		
		if(isset($_POST['action']) && $_POST['action'] === 'heartbeat'){
			return;
		}
		
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	* Define Recombee Constants.
	*/
	private function define_constants() {
		
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'RRE_ABSPATH', dirname( RRE_PLUGIN_FILE ) . '/' );
		$this->define( 'PRE_PLUGIN_BASENAME', plugin_basename( RRE_PLUGIN_FILE ) );
		$this->define( 'RRE_WC_IS_ACTIVE', in_array('woocommerce/woocommerce.php', get_option('active_plugins')) ? true : false );
		$this->define( 'RRE_URL_SERVICE', 'https://www.recombee.com/' );
		$this->define( 'RRE_PLUGIN_URL', untrailingslashit( plugins_url( '/', RRE_PLUGIN_FILE )) );
		$this->define( 'RRE_DB_CONNECTED_CODE',200 );	
		$this->define( 'RRE_MENU_PAGE_SLUG', 'recombee_blog_settings' );
		$this->define( 'RRE_DB_DISCONNECTED_CODE',500 );
		$this->define( 'RRE_BLOG_SETTINGS_PRESET_NAME','recombee_blog_settings_preset' );	
		$this->define( 'RRE_SITE_SETTINGS_PRESET_NAME','recombee_site_settings_preset' );
		$this->define( 'RRE_SYNC_WC_PRODUCTS_PRESET_NAME','recombee_sync_wc_products_settings_preset' );
		$this->define( 'RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME','recombee_sync_wc_products_prop_settings_preset' );
		$this->define( 'RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME','recombee_sync_wc_customers_prop_settings_preset' );
		$this->define( 'RRE_SYNC_WC_CUSTOMERS_PRESET_NAME','recombee_sync_wc_customers_settings_preset' );		
		$this->define( 'RRE_SYNC_WC_INTERACTIONS_PRESET_NAME','recombee_sync_wc_interactions_settings_preset' );		
		$this->define( 'RRE_SYNC_DB_RESET_PRESET_NAME','recombee_sync_db_reset_settings_preset' );	
		
	}

	/**
	* Define constant if not already set.
	*
	* @param string      $name  Constant name.
	* @param string|bool $value Constant value.
	*/	
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
	
	/**
	* Include required core files used in admin and on the frontend.
	*/	
	private function includes(){
		
		include_once RRE_ABSPATH . 'includes/class-RecombeeReAutoloader.php';
	}
	
	public function async_attr_add($good_protocol_url, $original_url, $_context){
		
		if ( strpos( $good_protocol_url, '#rre_asyncload') === false ){
			
			return $good_protocol_url;
		}
		else if ( is_admin() ){
			
			return str_replace( '#rre_asyncload', '', $good_protocol_url );
		}
		else{
		
			return str_replace( '#rre_asyncload', '', $good_protocol_url )."' async='async";
		}
    }

	public function adjust_recommendations_columns($parsed_args, $user_args) {
		
		if( $parsed_args['followThemeCss'] == 'on' ){
			if( isset($user_args['columns'] ) ){
				$parsed_args['columns'] = $user_args['columns'];
			}
			else{
				$parsed_args['columns'] = wc_get_default_products_per_row();
			}
		}
		return $parsed_args;
	}
	
	public function in_plugin_update_message($plugin_data, $response){
		
		if( version_compare($plugin_data['Version'], $response->new_version , '>=')){
			return;
		}
		
		?>
			<div>
				<div class="recombee_plugin_upgrade_notice"><?php echo sprintf( __( "<strong>Heads up!</strong> New plugin version (%s) contains changes to widget. To make it work correct - just resave all Recombee widget instances.", 'recombee-recommendation-engine' ), $response->new_version ); ?></div>
			</div>
			<p class="dummy"></p>
		<?php
	}
	
	public function on_activation(){
		
		if (!wp_next_scheduled('recombee_purge_transients_cron')) {		
			wp_schedule_event( time(), 'hourly', 'recombee_purge_transients_cron');
		}
		add_filter( 'wp_redirect', array($this, 'plugin_activated_query_var_true') );
	}
	
	public function on_deactivation(){
		@unlink(WPMU_PLUGIN_DIR . '/class-RecombeeReWpAjaxDispatcher.php');
		flush_rewrite_rules();
	}
	
	private function setupMuModules(){
		
		$to_update = false;
		
		if( file_exists(WPMU_PLUGIN_DIR . '/class-RecombeeReWpAjaxDispatcher.php') ){

			$ajaxModuleInfoMU = get_file_data(WPMU_PLUGIN_DIR . '/class-RecombeeReWpAjaxDispatcher.php',	 array('ver'=>'Version'));
			$ajaxModuleInfoSO = get_file_data(RRE_ABSPATH . 'includes/class-RecombeeReWpAjaxDispatcher.php', array('ver'=>'Version'));
			
			if( version_compare($ajaxModuleInfoMU['ver'], $ajaxModuleInfoSO['ver'], '<') ){
				
				$to_update = true;
			}
		}
		else{
			$to_update = true;
		}
		
		if( $to_update ){
			
			$dir = true;
			
			if( !is_dir(WPMU_PLUGIN_DIR) ){
				
				$dir = mkdir(WPMU_PLUGIN_DIR);
				
			}
			
			if($dir){
				
				copy(RRE_ABSPATH . 'includes/class-RecombeeReWpAjaxDispatcher.php', WPMU_PLUGIN_DIR . '/class-RecombeeReWpAjaxDispatcher.php');
			}
		}
	}
	
	public function plugin_activated_query_var_true($location){
		
		remove_filter( 'wp_redirect', array($this, 'plugin_activated_query_var_true') );
		return add_query_arg( array( 'rre_activated' => true ), $location );
	}
	
	public function purge_transients_cron($older_than = '1 hours'){

		global $wpdb;
		$older_than_time = strtotime('-' . $older_than);
		
		$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d";
		$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient__rawids_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', $older_than_time ) );
			

		return absint( $rows );
	}
	
	public function is_frontend() {
		
		if ((!is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')) )	|| 
			$this->is_doing_ajax_action('RecombeeDoAjaxWidgets')										|| 
			$this->is_doing_ajax_action('setCustomerDetailView')										||
			$this->is_doing_ajax_action('maybeMergeUsers')												){
			
			return true;
		}
		else{
			return false;
		}
	}
	
	public function is_doing_ajax() {
		
		return defined( 'RRE_DOING_AJAX' ) && RRE_DOING_AJAX;
	}
	
	private function is_doing_ajax_action($action) {
		
		if( $this->is_doing_ajax()		&& 
			isset($_REQUEST['data'])	&& 
			(!isset($_REQUEST['data']['AJAX_Marker']) || $_REQUEST['data']['AJAX_Marker'] != RRE_PLUGIN_DIR) ){
			
			return false;
		}
		
		if( isset($_REQUEST['action']) && $_REQUEST['action'] == $action ){
			
			return true;
		}
		else{
			return false;
		}
	}
	
	public function load_translation(){
		
		$local = get_locale();
		
		$wp_translated_plugin_files = array(
			WP_LANG_DIR . '/plugins/recombee-recommendation-engine' . '-' . $local . '.po',
			WP_LANG_DIR . '/plugins/recombee-recommendation-engine' . '-' . $local . '.mo',
		);
		
		foreach($wp_translated_plugin_files as $file){
			
			if( file_exists($file) ){
				unlink($file);
			}
		}
		
		load_plugin_textdomain( 'recombee-recommendation-engine',  FALSE,  RRE_PLUGIN_DIR . '/languages' );
	}
	
	public function settings(){
		
		if( RRE_WC_IS_ACTIVE ){
			
			$this->set_product_sync_prop();
			$this->set_customer_sync_prop();
			$this->initialize_blog_setting();
			$this->initialize_site_setting();
		}
		
		$this->setupCommunicator();
		$this->checkDbConnect();
		
		(object)$this->rre_ajax_interface = RecombeeReAjaxSetup::instance();
		(object)$this->menu = new RecombeeReBlogSettings();
		(new RecombeeReShortcodes())->init();
	}
	
	public function modal_warning(){
		
		/* 'show_admin_modal' configures  in 'setup_warning_status' */
		
		$blog_setting = $this->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);

		if( $blog_setting['show_admin_modal'] == 0 ){
			$show_form = false;
		}
		else if( $blog_setting['show_admin_modal'] == 1 ){
			$show_form = true;
		}
		else if( time() >= (float)$blog_setting['show_admin_modal']){
			$show_form = true;
		}
		else{
			$show_form = false;
		}
		
		if( $show_form ){
			
			$plugin_data = get_plugin_data(RRE_PLUGIN_FILE, false,false);
		
			if( version_compare($plugin_data['Version'], '2.2.2', '=')){
				 $this->modal_warning_content($plugin_data['Name'], '2.2.2');
			}
			else if( version_compare($plugin_data['Version'], '2.2.4', '=')){
				 $this->modal_warning_content($plugin_data['Name'], '2.2.4');
			}
			else if( version_compare($plugin_data['Version'], '2.6.0', '=')){
				 $this->modal_warning_content($plugin_data['Name'], '2.6.0');
			}
		}
	}
	
	private function modal_warning_content($title, $version){

		$settings = array(
			'AJAX_Marker'	=> RRE_PLUGIN_DIR,
			'ajaxUrl'		=> $this->rre_ajax_interface->get_virtual_page('ajax'),
			'button_Text'	=> __('Hide warning forever', 'recombee-recommendation-engine'),
			'action'		=> 'updateWarningPolicy',
			'nonce'			=> wp_create_nonce( 'modalWarning' ),
		);
	
		?><div id="recombee_modal_warning" title="<?php echo $title ?>" style="display: none;" data-settings='<?php echo json_encode($settings) ?>'><?php

			switch($version){
				
				case '2.2.2':
			
					echo sprintf( '<p>%1$s <span class="wparam">%2$s</span><br>%3$s </p><ul><li>%4$s</li><li>%5$s</li></ul>',
						__('In the current version of the plug-in, the set of synchronized properties of the products is expanded. The new property is - ', 'recombee-recommendation-engine'),
						__('Product Parent','recombee-recommendation-engine'),
						sprintf( __('It is highly recommended, but not necessarily, to synchronize new property with Recombee DB. Synchronization of this property will solve the problem of a non-stable number of recommended products, displayed by the widget.<br><br>To do this - go to the %1$s and perform two actions:','recombee-recommendation-engine'), '<a href="' . menu_page_url(RRE_MENU_PAGE_SLUG, false) . '">' . _x('plugin settings page', 'warning 2.2.2','recombee-recommendation-engine') . '</a>'),
						__('find <span class="wparam">WC Product Properties synchronization</span> section and synchronize the set of properties with the Recombee database.','recombee-recommendation-engine'),
						__('find <span class="wparam">WC Products synchronization</span> section and re-synchronize all products.','recombee-recommendation-engine')
					);
					
					break;
					
				case '2.2.4':
			
					echo sprintf( __('<p>In this version, a new option has appeared, it allows to replace the content of the related product section with Recombee recommendations, even in cases when this section generates not by WooCommerce itself, but, for example, via the code of the active theme. To do this, you need to determine the hook name, attached to the event "woocommerce_after_single_product_summary" and specify it in the "WC Related Tags" field on the %1$s. The default hook name is %2$s, and for example, for the theme "Uplift" the tag name will be %3$s</p>','recombee-recommendation-engine'),
					'<a href="' . menu_page_url(RRE_MENU_PAGE_SLUG, false) . '">' . _x('plugin settings page', 'warning 2.2.4', 'recombee-recommendation-engine') . '</a>',
					'<span class="wparam">woocommerce_after_single_product_summary</span>',
					'<span class="wparam">sf_after_single_product_reviews</span>' );
					
					break;
					
				case '2.6.0':
			
					echo sprintf( '<p>%1$s <span class="wparam">%2$s</span><br>%3$s </p><ul><li>%4$s</li><li>%5$s</li></ul> <p>%6$s</p>',
						__('In the current version of the plug-in, the set of synchronized properties of the products is expanded. The new property is - ', 'recombee-recommendation-engine'),
						__('Product is Visible','recombee-recommendation-engine'),
						sprintf( __('It is highly recommended, but not necessarily, to synchronize new property with Recombee DB. Synchronization of this property will solve the problem of a non-stable number of recommended products, displayed by the widget.<br><br>To do this - go to the %1$s and perform two actions:','recombee-recommendation-engine'), '<a href="' . menu_page_url(RRE_MENU_PAGE_SLUG, false) . '">' . _x('plugin settings page', 'warning 2.2.2','recombee-recommendation-engine') . '</a>'),
						__('find <span class="wparam">WC Product Properties synchronization</span> section and synchronize the set of properties with the Recombee database.','recombee-recommendation-engine'),
						__('find <span class="wparam">WC Products synchronization</span> section and re-synchronize all products.','recombee-recommendation-engine'),
						__('After you have done with it, do not forget to modify WC Related Override shortcode and widget instances replacing <span class="wparam">\'wpStatus\' == true</span> with <span class="wparam">\'wcProductIsVisible\' == true</span>.','recombee-recommendation-engine')
					);
					
					break;
			}
		
		?></div><?php
	}
	
	public function jsCssFront(){
		
		$data = array(
			'styles' => array(
				array( 'handle' => 'widgets_front_css', 'src' => RRE_PLUGIN_URL . '/includes/assets/css/widgets-front.css', 'deps' => array() ),
			),
			'scripts' => array(
				array('handle' => 'frontend_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/frontend.js',					'deps' => array()),
				array('handle' => 'widgets_front_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/widgets-front.js#rre_asyncload',	'deps' => array('jquery-effects-fade', 'jquery')),
				array('handle' => 'after_login_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/after-login.js',					'deps' => array('frontend_js', 'jquery')),
				array('handle' => 'product_single_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/product-single.js',				'deps' => array('frontend_js', 'jquery')),
				array('handle' => 'product_archive_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/product-archive.js',				'deps' => array('frontend_js', 'jquery')),
			),
		);
		$this->menu->registerJsCss($data);		
	}
	
	public function jsCssAdmin(){
		
		$data = array(
			'styles' => array(
				array( 'handle' => 'style_backend_all',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/style-backend-all.css',				'deps' => array('jquery_ui_css') ),
				array( 'handle' => 'select_2_css',		'src' => RRE_PLUGIN_URL . '/includes/assets/css/select2.css',						'deps' => array() ),
				array( 'handle' => 'selectize_css',		'src' => RRE_PLUGIN_URL . '/includes/assets/css/selectize.bootstrap2.css',			'deps' => array() ),
				array( 'handle' => 'bootstrap_css',		'src' => RRE_PLUGIN_URL . '/includes/assets/css/bootstrap.css',						'deps' => array() ),
				array( 'handle' => 'bootstrap_aws_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/awesome-bootstrap-checkbox.css',	'deps' => array() ),
				array( 'handle' => 'bootstrap_sel_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/bootstrap-select.css',				'deps' => array() ),
				array( 'handle' => 'jquery_ui_css',		'src' => RRE_PLUGIN_URL . '/includes/assets/css/jquery-ui.css',						'deps' => array() ),
				array( 'handle' => 'menu_chosen_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/chosen.css',						'deps' => array() ),
				array( 'handle' => 'query_builder_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/query-builder-default.css',			'deps' => array() ),
				array( 'handle' => 'reql_builder_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/reql-builder.css',					'deps' => array() ),
				array( 'handle' => 'widgets_admin_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/widgets-admin.css',					'deps' => array('query_builder_css', 'bootstrap_css', 'bootstrap_sel_css', 'bootstrap_aws_css', 'jquery_ui_css', 'selectize_css', 'reql_builder_css', 'select_2_css') ),
			),
			'scripts' => array(
				array('handle' => 'backend_js',			'src' => RRE_PLUGIN_URL . '/includes/assets/js/backend.js',						'deps' => array('jquery-ui-dialog', 'jquery-effects-drop', 'jquery')),
				array('handle' => 'select_2_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/select2.full.js',				'deps' => array()),
				array('handle' => 'selectize_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/selectize.js',					'deps' => array()),
				array('handle' => 'bootstrap_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/bootstrap.js',					'deps' => array()),
				array('handle' => 'bootstrap_sel_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/bootstrap-select.js',			'deps' => array()),
				array('handle' => 'interact_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/interact.js',					'deps' => array()),
			 /* array('handle' => 'sql_parser_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/sql-parser.js',					'deps' => array()), */
				array('handle' => 'query_builder_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/query-builder.standalone.js',	'deps' => array()),
				array('handle' => 'reql_builder_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/reql-builder.js',				'deps' => array()),
				array('handle' => 'menu_chosen_js',		'src' => RRE_PLUGIN_URL . '/includes/assets/js/chosen.js',						'deps' => array('jquery')),
				array('handle' => 'widgets_admin_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/widgets-admin.js',				'deps' => array('query_builder_js', 'jquery', 'jquery-ui-dialog', 'jquery-effects-drop', 'jquery-effects-fade', 'jquery-ui-slider', 'interact_js', 'bootstrap_js', 'bootstrap_sel_js', 'selectize_js', 'reql_builder_js', 'select_2_js' /* , 'sql_parser_js' */)),
			),
		);

		/* wp_deregister_script('heartbeat'); */ //@only for debug mode
		
		$this->menu->registerJsCss($data);
	}
	
	private function setupCommunicator(){
				
		$db_name	= $this->setting_blog['api_identifier'];
		$secret_key = $this->setting_blog['api_secret_token'];
		
		$this->communicator = RecombeeReCommunicator::instance($db_name, $secret_key);
	}
	
	public function checkDbConnect(){
		
		if( is_null(self::$dbConnectLost) && !wp_doing_ajax() && is_admin() ){
			
			self::$dbConnectLost = false;
			
			if($this->setting_blog['db_connection_code'] !== RRE_DB_DISCONNECTED_CODE){
				
				$response = $this->communicator->reqsListItems(array('operation_name' => 'db connect/disconnect', 'count' => 1, 'force_log' => true));
				if( isset($response['errors']) ){
					self::$dbConnectLost = true;
				}
			}
		}
		return self::$dbConnectLost;
	}
	
	public function widgets (){
		
		register_widget( 'RecombeeReRecommsWidget' );
	}
	
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ){
		
		if ( PRE_PLUGIN_BASENAME == $plugin_file ) {
			$row_meta = array(
				'docs'    => '<a href="' . 'https://docs.recombee.com/' . '">' . __( 'API Docs', 'recombee-recommendation-engine' ) . '</a>',
			);
			return array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}
	
	public function admin_notices(){

		$echo = '';
		$blog_setting = $this->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		
		if( !RRE_WC_IS_ACTIVE ){
			$echo .= '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . __('Recombee Recommendation Engine requires WooCommerce installed & activated to work.', 'recombee-recommendation-engine') . '</p></div>';
		}
		if( self::$dbConnectLost ){
			$echo .= '<div id="recombee-lost-coonect-notice" class="notice notice-error is-dismissible"><p>' . __('Recombee Recommendation Engine plugin lost connect to the Recombee Database. Check Your connection setting.', 'recombee-recommendation-engine') . '</p></div>';
		}
		if(!empty($echo)){
			echo $echo;
		}
		if ( isset($_GET['customer_update_err']) && $blog_setting['log_requests_err'] == 1 ){
			echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('There was an error in WC Customer update request. See <i>%s</i> for extra information.'), RRE_PLUGIN_DIR.'/log/requests-errors.log') . '</p></div>';
		}
		if ( isset($_GET['product_update_err']) && $blog_setting['log_requests_err'] == 1 ){
			echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('There was an error in WC product update request. See <i>%s</i> for extra information.'), RRE_PLUGIN_DIR.'/log/requests-errors.log') . '</p></div>';
		}
		if ( isset($_GET['add_new_taxonomy_err']) ){
			echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('There was an error on adding new product taxonomy to Recombee. See <i>%s</i> for extra information.'), RRE_PLUGIN_DIR.'/log/requests-errors.log') . '</p></div>';
		}
		if ( isset($_GET['get_recombee_prop_err']) ){
			echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('There was an error in Recombee getting product properties request. See <i>%s</i> for extra information.'), RRE_PLUGIN_DIR.'/log/requests-errors.log') . '</p></div>';
		}
		if ( count(self::$doubled_prop_keys) > 0 ){
			echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('Some WC Product Taxonomies have the same names as WC Product meta fields. Recombee Plugin is unable to handle these taxonomies. Here are the doubled names: %s'), implode(', ',self::$doubled_prop_keys)) . '</p></div>';
		}
	}
	

}