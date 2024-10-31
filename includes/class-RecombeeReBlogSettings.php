<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RecombeeReBlogSettings extends RecombeeReAdmin{
	
	private $recombee;
	private $blogSetting;
	private $prodPropSyncSetting;
	private $custPropSyncSetting;
	private $prodSyncSetting;
	private $custSyncSetting;
	private $intrSyncSetting;
	private $resetSyncSetting;
	private $RecombeeBlogSettingsDb;
	
	private static $syncStatistic;
	
	public function __construct(){
		
		
		$this->recombee = RecombeeRe::instance();
		
		$this->RecombeeBlogSettingsDb	= RecombeeReBlogSettingsDb::instance();
		$this->blogSetting				= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		$this->prodPropSyncSetting		= $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME);
		$this->custPropSyncSetting		= $this->recombee->get_blog_setting(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME);
		$this->prodSyncSetting			= $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME);
		$this->custSyncSetting			= $this->recombee->get_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME);
		$this->intrSyncSetting			= $this->recombee->get_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME);
		$this->resetSyncSetting			= $this->recombee->get_blog_setting(RRE_SYNC_DB_RESET_PRESET_NAME);
		$this->dbConnectLost			= $this->recombee->checkDbConnect();
		
		$this->syncUser();
		$this->hooks();
		
		if( RRE_WC_IS_ACTIVE ){
			require( untrailingslashit( plugin_dir_path( RRE_PLUGIN_FILE ) ) . '/includes/data-BlogSettingsArgs.php' );
			parent::__construct($blogSettingsArgs);
		}
	}
	
	/* 
	* Sync current user of any type
	*/
	private function syncUser(){
		
		if( $this->blogSetting['db_connection_code'] == RRE_DB_DISCONNECTED_CODE ){
			return;
		}

		if(is_admin()){
			return;
		}
		
		if( is_user_logged_in() ){
			
			$userid		= get_current_user_id();
			$userProp	= array($this->RecombeeBlogSettingsDb->getCustomerPropertiesValue($userid));
		}
		else{
			$userid = $this->recombee->get_RAUID();

			$userProp = array(
				array(
					'id'			=> $userid,
					'properties'	=> array(
						'wpStatus'	=> 'anonymous'
					),
				),
			);
		}
		$userData = array(
			array(
				'userId' => $userid
			),
		);
		
		$result = array(
			'api_errors'	=> array(),
			'curl_errors'	=> array(),
			'api_success'	=> array(),
		);
		
		/* --- This block add user in right way: check if user exists -> if not - create one and set it props ---
		
			$response = $this->recombee->communicator->reqsListUsers(array('operation_name' => 'Check current user exists at Recombee', 'filter' => '\'userId\' == "' . $userid . '"' ));
			
			if( isset($response['errors']) ){
				$result['api_errors'][] = $response['errors']; 
			}
			if( isset($response['exception_type']) ){
				$result['curl_errors'][] = $response['exception_type']; 
			}
			if( isset($response['success']) ){
				$result['api_success'][] = $response['success'];
				if( count($response['success']) === 0 ){
					
					$batch_stack = array(
						array('method' => 'reqsAddUser',		'properties' => $userData, 'param' => array('cascadeCreate' => false)),
						array('method' => 'reqsSetUserValues',	'properties' => $userProp, 'param' => array('cascadeCreate' => false)),
					);
					
					$response = $this->recombee->communicator->reqsExecuteBatch('Sync user', $batch_stack, true);
				}
			}
			
		------------------------------------------------------------------------------------------------------ */
	
		/* --- To avoid long time listUser send SetUserValues and cascade create it (faster then listUser) --- */
			
			$response = $this->recombee->communicator->reqsSetUserValues( $userProp[0], array('cascadeCreate' => true, 'operation_name' => 'Set user value of current user at Recombee (whether it exists or not)'));
			
			if( isset($response['exception_type']) ){
				$result['curl_errors'][] = $response['exception_type']; 
			}
		
		/* --------------------------------------------------------------------------------------------------- */
			
		if( count($result['curl_errors']) > 0 ){
			/* TO DO queue fault request to DB */
		}
	}
	
	protected function hooks(){
		
		if( !RRE_WC_IS_ACTIVE ){
			return;
		}
		
		if( $this->blogSetting['wc_override_related_products'] == 1 && !is_admin() ){
			
			$tags_to_override = explode (',', $this->blogSetting['wc_override_related_tags']);
			
			foreach($tags_to_override as $tag_to_override){
				
				if( isset($GLOBALS['wp_filter'][$tag_to_override]) ){
					
					$callbacks = &$GLOBALS['wp_filter'][$tag_to_override]->callbacks;
					
					foreach($callbacks as $priority => $priority_data){
						
						if( array_key_exists('woocommerce_output_related_products', $priority_data) ){
							
							unset($callbacks[$priority]['woocommerce_output_related_products']);
						 /* remove_all_actions($tag_to_override, $priority); */
							add_action($tag_to_override, array($this, 'WC_OverrideRelatedProducts' ), $priority);
						}
					}
				}
			}
		}
		
		add_action( 'wp_enqueue_scripts',						array( $this, 'frontScripts'		), 15);
		add_action( 'admin_enqueue_scripts',					array( $this, 'adminScripts' 		));
		add_action( 'RecombeeReBeforeSettingPage',				array( $this, 'getSyncStatistic'	));
		add_action( 'rre_ajax_updateWarningPolicy',				array( $this, 'updateWarningPolicy'	));
		
		/* Init sync */
		add_action( 'rre_ajax_dbConnectionAction',				array( $this->RecombeeBlogSettingsDb, 'dbConnectionAction' 		));
		add_action( 'rre_ajax_dbSyncWcProductsProp',			array( $this->RecombeeBlogSettingsDb, 'dbSyncWcProductsProp'	));
		add_action( 'rre_ajax_dbSyncWcCustomersProp',			array( $this->RecombeeBlogSettingsDb, 'dbSyncWcCustomersProp'	));
		add_action( 'rre_ajax_dbSyncWcProducts',				array( $this->RecombeeBlogSettingsDb, 'dbSyncWcProducts'		));
		add_action( 'rre_ajax_dbSyncWcCustomers',				array( $this->RecombeeBlogSettingsDb, 'dbSyncWcCustomers' 		));
		add_action( 'rre_ajax_dbSyncWcInteractions',			array( $this->RecombeeBlogSettingsDb, 'dbSyncWcInteractions'	));
		add_action( 'rre_ajax_dbReset',							array( $this->RecombeeBlogSettingsDb, 'dbReset'					));
		add_filter( 'before_single_settings_preset_save',		array( $this->RecombeeBlogSettingsDb, 'beforeBlogSettingSave'	));
		
		/* Customers data changes */
		add_action( 'woocommerce_created_customer',				array( $this->RecombeeBlogSettingsDb, 'dbCreateCustomer' ));
		add_action( 'woocommerce_update_customer',				array( $this->RecombeeBlogSettingsDb, 'dbUpdateCustomer' ));
		add_action( 'woocommerce_checkout_update_user_meta',	array( $this->RecombeeBlogSettingsDb, 'dbUpdateCustomer' ));
		add_action( 'delete_user',								array( $this->RecombeeBlogSettingsDb, 'dbUpdateCustomer' ));
		
		/* Product data changes */
		add_action( 'untrash_post',								array( $this->RecombeeBlogSettingsDb, 'dbUpdateProduct'		), 20);
		add_action( 'trashed_post',								array( $this->RecombeeBlogSettingsDb, 'dbUpdateProduct'		), 20);
		add_action( 'save_post',								array( $this->RecombeeBlogSettingsDb, 'dbUpdateProduct'		), 20, 3 );
		add_action( 'deleted_post', 							array( $this->RecombeeBlogSettingsDb, 'dbUpdateProduct'		), 20);
		add_action( 'delete_term',								array( $this->RecombeeBlogSettingsDb, 'dbUpdateTerms'  		), 10, 5 );
		add_action( 'registered_taxonomy',						array( $this->RecombeeBlogSettingsDb, 'dbAddProductsTax'	), 10, 3 );
		add_action( 'woocommerce_attribute_added',				array( $this->RecombeeBlogSettingsDb, 'dbAddProductsAtt'	), 10, 2 );
		add_action( 'woocommerce_attribute_deleted',			array( $this->RecombeeBlogSettingsDb, 'dbDeleteProductsAtt'	), 10, 3 );
		
	 /* add_action( 'woocommerce_new_product_variation',		array( $this->RecombeeBlogSettingsDb, 'dbUpdateProduct'		), 20); */
		
		/* Product interactions */
		add_action( 'rre_ajax_nopriv_setCustomerDetailView',	array( $this->RecombeeBlogSettingsDb, 'setCustomerDetailView'));
		add_action( 'rre_ajax_setCustomerDetailView',			array( $this->RecombeeBlogSettingsDb, 'setCustomerDetailView'));
		
		add_action( 'comment_post',								array( $this->RecombeeBlogSettingsDb, 'setAddRatingNew'		), 10, 3 );
		
		add_action( 'woocommerce_add_to_cart', 					array( $this->RecombeeBlogSettingsDb, 'setAddCartAddition'	), 10, 6 );
		add_action( 'woocommerce_thankyou',						array( $this->RecombeeBlogSettingsDb, 'setAddPurchase'		));
		add_action( 'edit_comment',								array( $this->RecombeeBlogSettingsDb, 'setAddRatingEdit'	));
		add_action( 'comment_unapproved_to_approved',			array( $this->RecombeeBlogSettingsDb, 'setAddRatingStatus'	));
		
		/* Merge anonymous */
		add_action( 'rre_ajax_maybeMergeUsers',					array( $this->RecombeeBlogSettingsDb, 'asyncMergeUsers'));
		add_action( 'wp_login', 								array( $this->RecombeeBlogSettingsDb, 'addLoginRedirectVars'), 10, 2 );
	}
	
	public function getRequestsQueryLog(){
		
		return $this->RecombeeBlogSettingsDb->requestsQueryLog;
	}
	
	public function getSyncStatistic(){
		
		if( is_null(self::$syncStatistic) ){
			
			self::$syncStatistic = array( 
				'wcProductsPropsTotal'	=> $this->blogSetting['db_product_prop_set'],
				'wcCustomersPropsTotal'	=> $this->blogSetting['db_customer_prop_set'],
				'wcProductsTotal'		=> wc_get_products( array(
					'limit'			=> -1,
					'status'		=> $this->recombee->productStatuses,
					'return'		=> 'ids',
				)),
				'wcOrdersPerCustomers'		=> $this->get_orders_per_customer(),
				'wcCommentsPerCustomers'	=> $this->get_comments_per_customer(),
			);
		}
		return self::$syncStatistic;
	}
	
	public function updateWarningPolicy(){
		
		if ( check_ajax_referer( 'modalWarning', 'nonce', true) ){
			
			$warning_close_time = round ( (float)sanitize_text_field($_POST['data']['show_admin_modal_value'])/1000, 0);
			
			if( $warning_close_time === (float)0 ){
				$new_setting['show_admin_modal'] = $warning_close_time;
			}
			else{
				$new_setting['show_admin_modal'] = $warning_close_time + DAY_IN_SECONDS;
			}
			
			$new_setting = $this->recombee->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME, $new_setting);
		}
	}
	
	public function get_orders_per_customer($offset = false, $limit = false){
		
		($limit  !== false)	? $limit	= 'LIMIT '	. $limit	: '';
		($offset !== false)	? $offset	= 'OFFSET ' . $offset	: '';
		
		global $wpdb;
		
		/* Gettting all Shop Orders with '_customer_user' >= 0 */
		/* wc-order-statuses could be: 'wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded','wc-failed' */
		
		$wcCustomers = $wpdb->get_results(
			$wpdb->prepare("
				SELECT count(postmeta.post_id) AS orders_total, postmeta.meta_id, postmeta.meta_key, sub_meta.meta_value AS user_id, postmeta.meta_value AS billing_email, CONCAT('gis-', MD5(postmeta.meta_value)) AS billing_email_hash, SUM(order_items.order_item_id) AS sum_orders_items
				FROM $wpdb->posts AS posts
				INNER JOIN
					(
						$wpdb->postmeta AS postmeta
							INNER JOIN
								(
									SELECT postsubmeta.post_id, postsubmeta.meta_key, postsubmeta.meta_value
									FROM $wpdb->postmeta AS postsubmeta
									WHERE postsubmeta.meta_key = '%s'
								) AS sub_meta ON postmeta.post_id = sub_meta.post_id
					) ON posts.ID = postmeta.post_id
				INNER JOIN
					(
						SELECT {$wpdb->prefix}woocommerce_order_items.order_id, count({$wpdb->prefix}woocommerce_order_items.order_item_id) AS order_item_id
						FROM {$wpdb->prefix}woocommerce_order_items
						WHERE {$wpdb->prefix}woocommerce_order_items.order_item_type = 'line_item'
						GROUP BY {$wpdb->prefix}woocommerce_order_items.order_id
					) AS order_items ON posts.ID = order_items.order_id
				WHERE postmeta.meta_key = '%s' AND posts.post_type = '%s' AND posts.post_status IN ('%s', '%s', '%s')
				GROUP BY postmeta.meta_value
				ORDER BY postmeta.post_id ASC
				$limit $offset",
				'_customer_user', '_billing_email', 'shop_order', 'wc-completed', 'wc-processing', 'wc-on-hold'), ARRAY_A
		);
		
		return $wcCustomers;
	}
	
	public function get_comments_per_customer($offset = false, $limit = false){
		
		($limit  !== false)	? $limit	= 'LIMIT '	. $limit	: '';
		($offset !== false)	? $offset	= 'OFFSET ' . $offset	: '';
		
		global $wpdb;
		
		$wcComments = $wpdb->get_results(
			$wpdb->prepare("
				SELECT Count(comments.comment_ID) AS comments_total, comments.user_id, comments.comment_author_email AS comment_author_email, CONCAT('gis-', MD5(comments.comment_author_email)) AS comment_author_email_hash
				FROM $wpdb->posts AS posts INNER JOIN $wpdb->comments AS comments ON posts.ID = comments.comment_post_ID
				WHERE posts.post_type = '%s' AND comments.comment_approved = '%s'
				GROUP BY comments.user_id, comments.comment_author_email
				ORDER BY comments.comment_ID ASC
				$limit $offset",
				'product', '1'), ARRAY_A
		);
		
		return $wcComments;
	}
	
	public function WC_OverrideRelatedProducts(){
		
		echo do_shortcode( wp_unslash($this->blogSetting['wc_overridden_related_shortcode']) );
	}
	
	public function frontScripts(){
		
		$object_data = array();
		
		/* Detail View */
		if ( is_product() || is_post_type_archive( 'product' ) ){
			
			$object_data['DetailView'] = array(
				'RAUID'			=> $this->recombee->get_RAUID(),
				'AJAX_Marker'	=> RRE_PLUGIN_DIR,
				'ajaxUrl'		=> $this->recombee->rre_ajax_interface->get_virtual_page('ajax'),
				'action'		=> 'setCustomerDetailView',
				'nonce'			=> wp_create_nonce( 'detailView' ),
				'logged'		=> (is_user_logged_in()) ? 'true' : 'false',
			);
			
			if( is_product() ){
				
				$object_data['DetailView']['productID']	= wc_get_product()->get_id();
				wp_enqueue_script( 'product_single_js' );
			}
			else if( is_post_type_archive('product') ){
				wp_enqueue_script( 'product_archive_js' );
			}
		}
		
		/* Merge User */
		if( isset($_GET[md5('customer_logged_in_success')]) ){
			
			$object_data['AfterLogin'] = array(
				'AJAX_Marker'	=> RRE_PLUGIN_DIR,
				'ajaxUrl'		=> $this->recombee->rre_ajax_interface->get_virtual_page('ajax'),
				'action'		=> 'maybeMergeUsers',
				'nonce'			=> wp_create_nonce( 'MergeUsers' ),
				'kill_param'	=> md5('customer_logged_in_success'),
			);
			wp_enqueue_script( 'after_login_js' );
		}

		wp_enqueue_script( 'frontend_js' );
		wp_localize_script( 'frontend_js', 'recombeeRe_vars', $object_data );
		
	}
	
	public function adminScripts($hook){
		
		if( !wp_doing_ajax() ){
			
			if ( $hook == 'widgets.php' ){
				
				get_current_screen()->add_help_tab(	array(
					'id'		=> 'recombee_help',
					'title'		=> __('Recombee parameters', 'recombee-recommendation-engine'),
					'content'	=> '<p>' . __('Recombee engine supports many different parameters. Read their descriptions to use the widget as efficiently as possible:', 'recombee-recommendation-engine') .
											'<ul>
												<li><span class="wparam">' . __('Parent only', 'recombee-recommendation-engine') . '</span> - ' . __('Default “on”. Switch this option ON if you want to exclude varitaions from recommendations. With switched ON only parent products will be shown.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('AJAX mode', 'recombee-recommendation-engine') . '</span> - ' . __('Default “on”. Recommendations are loaded asynchronously with AJAX mode enabled, and therefore do not increase load time of the pages at your site. This mode may increase page speed load, but search engines, probably, will not parse recommendations content.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Follow Theme CSS', 'recombee-recommendation-engine') . '</span> - ' . __('Default “on”. With this option On, the container with the products will be rendered via the active theme WooCommerce templates and CSS rules. Otherwise, the original WooCommerce template and styles will be used. Also if option value is Off plugin does not takes into account parameter "columns".', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Suppress logic', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default “off”. This option allows to enable or disable the output of recommendations on individual pages of the site. It can take a value that consists of two parts: logic (“off”, “everywhere, excluding objects”, “only at objects”) and the object(s) where it will be applied. “off” - the option is disabled and recommendations are displayed everywhere, “everywhere, excluding objects” - recommendations are displayed everywhere, except for the specified object(s), “only at objects” - recommendations are displayed only at the specified object(s).', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Title', 'recombee-recommendation-engine') . '</span> - ' . __('Default empty. Title before widget content. HTML allowed', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Columns', 'recombee-recommendation-engine') . '</span> - ' . __('Default 3 for “followThemeCSS=off” & for “followThemeCSS=on” default will be taken form “WooCommerce Products per raw” value. Integer between 0 & 9 inclusive. The maximum number of columns to which widget content will be divided if possible (depends on free space inside widget container).', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Type', 'recombee-recommendation-engine') . '</span> - ' . __('Required! Can be "ProductsToCustomer" or "ProductsToProduct"', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Count', 'recombee-recommendation-engine') . '</span> - ' . __('Required! Number of items to be recommended', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Scenario', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default empty. Use <span class="code">##PostType##</span> mask to get current WP Post Type value. Scenario defines a particular application of recommendations. It can be for example “homepage”, “cart” or “emailing”. You can see each scenario in the Recombee UI separately, so you can check how well each application performs. The AI which optimizes models in order to get the best results may optimize different scenarios separately, or even use different models in each of the scenarios.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('User Impact', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. For recommendations type "ProductsToProduct" generated items are biased towards the user given. Using userImpact, you may control this bias. For an extreme case of userImpact=0.0, the interactions made by the user are not taken into account at all (with the exception of history-based blacklisting), for userImpact=1.0, you’ll get user-based recommendation.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Filter', 'recombee-recommendation-engine') . '</span> - ' . sprintf( __('Optional. Default empty. Boolean-returning %s expression which allows you to filter recommended items based on the values of their attributes. Predefined value is <span class="code">\'wpStatus\' != "deleted"</span>.', 'recombee-recommendation-engine'), '<a href="https://docs.recombee.com/reql.html">ReQL</a>') . '</li>
												<li><span class="wparam">' . __('Booster', 'recombee-recommendation-engine') . '</span> - ' . sprintf( __('Optional. Default empty. Number-returning %s expression which allows you to boost recommendation rate of some items based on the values of their attributes.', 'recombee-recommendation-engine'), '<a href="https://docs.recombee.com/reql.html">ReQL</a>') . '</li>
												<li><span class="wparam">' . __('Diversity', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Real 0.0 <= number <= 1.0 which determines how much mutually dissimilar should the recommended items be. The default value is 0.0, i.e., no diversification. Value 1.0 means maximal diversification.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Min Relevance', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default "low". For recommendations type "ProductsToProduct" specifies the threshold of how much relevant must the recommended items be to the user. Possible values one of: “low”, “medium”, “high”. The default value is “low”, meaning that the system attempts to recommend number of items equal to count at any cost. If there are not enough data (such as interactions or item properties), this may even lead to bestseller-based recommendations to be appended to reach the full count. This behavior may be suppressed by using “medium” or “high” values. In such case, the system only recommends items of at least the requested relevancy, and may return less than count items when there is not enough data to fulfill it.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Rotation Rate', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default Null. For recommendations type "ProductsToProduct" if your users browse the system in real-time, it may easily happen that you wish to offer them recommendations multiple times. Here comes the question: how much should the recommendations change? Should they remain the same, or should they rotate? Recombee API allows you to control this per-request in backward fashion. You may penalize an item for being recommended in the near past. For the specific user, rotationRate=1 means maximal rotation, rotationRate=0 means absolutely no rotation. You may also use, for example rotationRate=0.2 for only slight rotation of recommended items.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('Rotation Time', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default Null. For recommendations type "ProductsToProduct" taking Rotation Rate into account, specifies how long time it takes to an item to recover from the penalization. For example, rotationTime=7200.0 means that items recommended less than 2 hours ago are penalized.', 'recombee-recommendation-engine') . '</li>
											</ul>
										</p>',
					'callback'	=> '',
					'priority'	=> 40
					)
				);
			}
			
			if ( $hook == $this->menu_page_hook ) {
				
				get_current_screen()->add_help_tab(	array(
					'id'		=> 'recombee_help',
					'title'		=> __('Recombee shortcodes', 'recombee-recommendation-engine'),
					'content'	=> '<p>' . __('Recombee shortcodes emulates the behavior of plugin widget Personal recommendations, getting all the same parameters: Read each one to use shortcode as efficiently as possible:', 'recombee-recommendation-engine') .
											'<ul>
												<li><span class="wparam">' . __('parentsOnly', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default “on”. Can be "on", "off". Switch this option ON if you want to exclude varitaions from recommendations. With switched ON only parent products will be shown.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('ajaxMode', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default “on”. Can be "on", "off". Recommendations are loaded asynchronously with AJAX mode enabled, and therefore do not increase load time of the pages at your site. This mode may increase page speed load, but search engines, probably, will not parse recommendations content.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('followThemeCSS', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default “on”. With this option On, the container with the products will be rendered via the active theme WooCommerce templates and CSS rules. Otherwise, the original WooCommerce template and styles will be used. Also if option value is Off plugin does not takes into account parameter "columns".', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('suppress', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default “off”. This option allows to enable or disable the output of recommendations on individual pages of the site. It can take a value that consists of two parts: suppress logic (“off”, “exclude”, “include”) and the post(s) or post\'s taxonomy terms, where it will be applied (comma separated object\'s id). “off” - the option is disabled and recommendations are displayed everywhere, “exclude” - recommendations are displayed everywhere, except for the specified post\'s ID or posts, that belongs to specified taxonomy term\'s ID, “include” - recommendations are displayed only at the specified post\'s or post\'s, that belongs to specified taxonomy term\'s ID. Examples: <span class="code">suppress="include posts 12, 55, 21"</span> <span class="code">suppress="exclude posts 12, 55, 21"</span> <span class="code">suppress="include terms 45, 32, 11"</span> <span class="code">suppress="exclude terms 67, 2, 9"</span> <span class="code">suppress="off"</span>', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('wTitle', 'recombee-recommendation-engine') . '</span> - ' . __('Default empty. Title before widget content. HTML allowed', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('columns', 'recombee-recommendation-engine') . '</span> - ' . __('Default 3 for “followThemeCSS=off” & for “followThemeCSS=on” default will be taken form “WooCommerce Products per raw” value. Integer between 0 & 9 inclusive. The maximum number of columns to which widget content will be divided if possible (depends on free space inside widget container)', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('type', 'recombee-recommendation-engine') . '</span> - ' . __('Required! Can be "ProductsToCustomer" or "ProductsToProduct"', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('count', 'recombee-recommendation-engine') . '</span> - ' . __('Default 4. Number of items to be recommended', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('scenario', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default empty. Use <span class="code">##PostType##</span> mask to get current WP Post Type value. Scenario defines a particular application of recommendations. It can be for example “homepage”, “cart” or “emailing”. You can see each scenario in the Recombee UI separately, so you can check how well each application performs. The AI which optimizes models in order to get the best results may optimize different scenarios separately, or even use different models in each of the scenarios.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('UserImpact', 'recombee-recommendation-engine') . '</span> - ' . __('Default 0 and only for type = "ProductsToProduct". Generated products are biased towards the user given. Using userImpact, you may control this bias. For an extreme case of userImpact=0.0, the interactions made by the user are not taken into account at all (with the exception of history-based blacklisting), for userImpact=1.0, you’ll get user-based recommendation.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('filter', 'recombee-recommendation-engine') . '</span> - ' . sprintf( __('Optional. Default empty. Boolean-returning %s expression which allows you to filter recommended items based on the values of their attributes. Predefined value is <span class="code">\'wpStatus\' != `deleted`</span>. Attention: if you use your own ReQL expression within shortcode - be sure to use single back quotes instead of double quotes (due to shortcode limitations), like <span class="code">"data" -> `data`</span> Example: <span class="code">filter="`simple` in \'wcProductType\'"</span> and replace <span class="code">[]</span> with <span class="code">{}</span>', 'recombee-recommendation-engine'), '<a href="https://docs.recombee.com/reql.html">ReQL</a>') . '</li>
												<li><span class="wparam">' . __('booster', 'recombee-recommendation-engine') . '</span> - ' . sprintf( __('Optional. Default empty. Number-returning %s expression which allows you to boost recommendation rate of some items based on the values of their attributes. Predefined value is <span class="code">if \'wcRegularPrice\' > context_item{`wcRegularPrice`} then 1.5 else 1"</span>. Attention: if you use your own ReQL expression within shortcode - be sure to use single back quotes instead of double quotes (due to shortcode limitations), like <span class="code">"data" -> `data`</span> Example: <span class="code">filter="`simple` in \'wcProductType\'"</span> and replace <span class="code">[]</span> with <span class="code">{}</span>', 'recombee-recommendation-engine'), '<a href="https://docs.recombee.com/reql.html">ReQL</a>') . '</li>
												<li><span class="wparam">' . __('diversity', 'recombee-recommendation-engine') . '</span> - ' . __('Default Null. Real 0.0 <= number <= 1.0 which determines how much mutually dissimilar should the recommended items be. The default value is 0.0, i.e., no diversification. Value 1.0 means maximal diversification.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('minRelevance', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default "low". For recommendations type "ProductsToProduct" specifies the threshold of how much relevant must the recommended items be to the user. Possible values one of: “low”, “medium”, “high”. The default value is “low”, meaning that the system attempts to recommend number of items equal to count at any cost. If there are not enough data (such as interactions or item properties), this may even lead to bestseller-based recommendations to be appended to reach the full count. This behavior may be suppressed by using “medium” or “high” values. In such case, the system only recommends items of at least the requested relevancy, and may return less than count items when there is not enough data to fulfill it.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('rotationRate', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default Null. For recommendations type "ProductsToProduct" if your users browse the system in real-time, it may easily happen that you wish to offer them recommendations multiple times. Here comes the question: how much should the recommendations change? Should they remain the same, or should they rotate? Recombee API allows you to control this per-request in backward fashion. You may penalize an item for being recommended in the near past. For the specific user, rotationRate=1 means maximal rotation, rotationRate=0 means absolutely no rotation. You may also use, for example rotationRate=0.2 for only slight rotation of recommended items.', 'recombee-recommendation-engine') . '</li>
												<li><span class="wparam">' . __('rotationTime', 'recombee-recommendation-engine') . '</span> - ' . __('Optional. Default Null. For recommendations type "ProductsToProduct" taking Rotation Rate into account, specifies how long time it takes to an item to recover from the penalization. For example, rotationTime=7200.0 means that items recommended less than 2 hours ago are penalized.', 'recombee-recommendation-engine') . '</li>
											</ul>
											<p><i>' . __('Real example: [RecombeeRecommendations wTitle="Recommended for You" type="ProductsToProduct" count="3" userImpact="0" filter="`simple` in \'wcProductType\'"]', 'recombee-recommendation-engine') . '</i></p>
										</p>',
					'callback'	=> '',
					'priority'	=> 10
					)
				);
			}
		}
	}
	
	private function dbConnectValue($all = false, $dbConnectLost = false ){
		
		if($dbConnectLost){

			return __('Lost connection', 'recombee-recommendation-engine' );
		}
		if($all){
			return array(
				RRE_DB_DISCONNECTED_CODE	=> __('Disconnected (Connect)', 'recombee-recommendation-engine' ),
				RRE_DB_CONNECTED_CODE		=> __('Connected (Disconnect)', 'recombee-recommendation-engine' ),
			);
		}
		
		switch ($this->blogSetting['db_connection_code'] ){
		
			case RRE_DB_DISCONNECTED_CODE:
				return __( 'Disconnected (Connect)', 'recombee-recommendation-engine' );
					break;
					
			case RRE_DB_CONNECTED_CODE:
				return __( 'Connected (Disconnect)', 'recombee-recommendation-engine' );
					break;
			
			default:
				return __( 'Unknown DB connection status', 'recombee-recommendation-engine' );

		}
	}
	
	protected function dbProductSetPropTip(){
		
		return __( 'Select which WC product properties will be synchronized. * - required properties - may not be removed.', 'recombee-recommendation-engine' );
	}
	
	protected function dbCustomerSetPropTip(){
		
		return __( 'Select which WC customer properties will be synchronized. * - required properties - may not be removed.', 'recombee-recommendation-engine' );
	}
	
	protected function dbProductSetSyncTip(){
		
		$class	= 'sync-progress';
		$synced = $this->prodPropSyncSetting['current_sync_offset'];
		$of		= count(self::$syncStatistic['wcProductsPropsTotal']);
		
		if($synced !== $of){
			$class .= ' sync-required';
		}
		
		if($this->prodPropSyncSetting['completed'] === false){
			$log = '<span class="action solid transparent">' . __('Never', 'recombee-recommendation-engine') . '</span>';
		}
		else{
			$log = '<span class="action solid green">' . date('d-m-Y H:i:s e', $this->prodPropSyncSetting['completed']/1000) . '</span>';
		}
		
		$return = '<span class="' . $class . '">' . __('Current progress (synced / of / errors): ', 'recombee-recommendation-engine') . '<span class="unit"><span class="items">' . $synced . '</span></span><span class="unit"><span class="total">' . $of . '</span></span><span class="unit"><span class="errors">' . $this->prodPropSyncSetting['loop_errors'] . '</span></span>' . '</span>';
		$return .= sprintf( __( 'Synchronize properties selected in in WC Product Properties field. This will create columns for the product properties in your Recombee database. Also, if you removed some previously synchronized product properties, the respective columns will be irreversibly removed from the Recombee database. Last successful synchronization: %s', 'recombee-recommendation-engine' ), $log );
		
		return $return;
	}

	protected function dbCustomerSetSyncTip(){
		
		$class	= 'sync-progress';
		$synced = $this->custPropSyncSetting['current_sync_offset'];
		$of		= count(self::$syncStatistic['wcCustomersPropsTotal']);
		
		if($synced !== $of){
			$class .= ' sync-required';
		}
		
		if($this->custPropSyncSetting['completed'] === false){
			$log = '<span class="action solid transparent">' . __('Never', 'recombee-recommendation-engine') . '</span>';
		}
		else{
			$log = '<span class="action solid green">' . date('d-m-Y H:i:s e', $this->custPropSyncSetting['completed']/1000) . '</span>';
		}
		
		$return = '<span class="' . $class . '">' . __('Current progress (synced / of / errors): ', 'recombee-recommendation-engine') . '<span class="unit"><span class="items">' . $synced . '</span></span><span class="unit"><span class="total">' . $of . '</span></span><span class="unit"><span class="errors">' . $this->custPropSyncSetting['loop_errors'] . '</span></span>' . '</span>';
		$return .= sprintf( __( 'Synchronize properties selected in WC Customer Properties field. This will create columns for the customer properties in your Recombee database. Also, if you removed some previously synchronized customer properties, the respective columns will irreversibly be removed from the Recombee database. Last successful synchronization: %s', 'recombee-recommendation-engine' ), $log );
		
		return $return;
	}
		
	protected function dbProductSyncTip(){
		
		$class	= 'sync-progress';
		$synced = $this->prodSyncSetting['current_sync_offset'];
		$of		= count(self::$syncStatistic['wcProductsTotal']);
		
		if($synced !== $of){
			$class .= ' sync-required';
		}
		
		if($this->prodSyncSetting['completed'] === false){
			$log = '<span class="action solid transparent">' . __('Never', 'recombee-recommendation-engine') . '</span>';
		}
		else{
			$log = '<span class="action solid green">' . date('d-m-Y H:i:s e', $this->prodSyncSetting['completed']/1000) . '</span>';
		}
		
		$return = '<span class="' . $class . '">' . __('Current progress (synced / of / errors): ', 'recombee-recommendation-engine') . '<span class="unit"><span class="items">' . $synced . '</span></span><span class="unit"><span class="total">' . $of . '</span></span><span class="unit"><span class="errors">' . $this->prodSyncSetting['loop_errors'] . '</span></span>' . '</span>';
		$return .= sprintf( __( 'Synchronize all the products to Recombee along with their attributes & variations. Last successful synchronization: %s', 'recombee-recommendation-engine' ), $log );
		
		return $return;
	}
	
	protected function dbCustomersSyncTip(){
		
		$class	= 'sync-progress';
		$synced = $this->custSyncSetting['current_sync_offset'];
		$of		= count(self::$syncStatistic['wcOrdersPerCustomers']);
		
		if($synced !== $of){
			$class .= ' sync-required';
		}
		
		if($this->custSyncSetting['completed'] === false){
			$log = '<span class="action solid transparent">' . __('Never', 'recombee-recommendation-engine') . '</span>';
		}
		else{
			$log = '<span class="action solid green">' . date('d-m-Y H:i:s e', $this->custSyncSetting['completed']/1000) . '</span>';
		}
		
		$return = '<span class="' . $class . '">' . __('Current progress (synced / of / errors): ', 'recombee-recommendation-engine') . '<span class="unit"><span class="items">' . $synced . '</span></span><span class="unit"><span class="total">' . $of . '</span></span><span class="unit"><span class="errors">' . $this->custSyncSetting['loop_errors'] . '</span></span>' . '</span>';
		$return .= sprintf( __( 'Synchronize all the customers to Recombee. No personal data (such as e-mail) will be synchronized. Keep in mind - your shop customers are being tracked by interactions at your shop (purchases, additions to cart, views…) and not by WP Users! Last successful synchronization: %s', 'recombee-recommendation-engine' ), $log );
		
		return $return;
	}
	
	protected function dbInteractionsSyncTip(){
		
		$synced = $this->intrSyncSetting['current_sync_offset'];
		$of = 0;
		$class	= 'sync-progress';
		
		foreach(self::$syncStatistic['wcOrdersPerCustomers'] as $customer_orders){
			
			$of = $of + ($customer_orders['orders_total'] + $customer_orders['sum_orders_items'] * 2); /* purchase + (one for cart addition + one for deteail view) */
		}
		foreach(self::$syncStatistic['wcCommentsPerCustomers'] as $customer_comments){
			
			$of += $customer_comments['comments_total'];
		}
		
		if($synced > $of){
			$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, array('current_sync_offset' => $of));
			$synced = $new_setting['current_sync_offset'];
		}
		if($synced !== $of){
			$class .= ' sync-required';
		}
		
		if($this->intrSyncSetting['completed'] === false){
			$log = '<span class="action solid transparent">' . __('Never', 'recombee-recommendation-engine') . '</span>';
		}
		else{
			$log = '<span class="action solid green">' .  date('d-m-Y H:i:s e', $this->intrSyncSetting['completed']/1000) . '</span>';
		}
		
		$return = '<span class="' . $class . '">' . __('Current progress (synced / of / errors): ', 'recombee-recommendation-engine') . '<span class="unit"><span class="items">' . $synced . '</span></span><span class="unit"><span class="total">' . $of . '</span></span><span class="unit"><span class="errors">' . $this->intrSyncSetting['loop_errors'] . '</span></span>' . '</span>';
		$return .= sprintf( __( 'Synchronize interactions between customers and products (purchases, ratings, views) to Recombee. Last successful synchronization: %s', 'recombee-recommendation-engine' ), $log );
		return $return;
	}
	
	protected function dbResetTip(){
		
		if($this->resetSyncSetting['completed'] === false){
			$return = '<span class="action solid transparent">' . __('Never', 'recombee-recommendation-engine') . '</span>';
		}
		else{
			$return = '<span class="action solid green">' . date('d-m-Y H:i:s e', $this->resetSyncSetting['completed']/1000) . '</span>';
		}
		
		$return = sprintf( __( 'Completely erase your database at Recombee. This action is irreversible. Last successful reset: %s', 'recombee-recommendation-engine' ), $return );
		return $return;
	}
	
	protected function dbSyncPace(){
		
		return __( 'Number of records in one data chunk sent to Recombee during synchronization. Decrease the chunk size if your server terminates the synchronization script due to exceeding «max_execution_time».', 'recombee-recommendation-engine' );
	}
	
	public function registerChild(){
		
		$data = array(
			'styles' => array(
				array( 'handle' => 'recombee_re_settings_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/settings.css',	'deps' => array() ),
			),
			'scripts' => array(
				array('handle' => 'recombee_re_settings_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/settings.js',	'deps' => array('jquery','jquery-ui-dialog','jquery-effects-fade') ),
			),
		);
		
		$this->registerJsCss($data);
	}
	
	public function enqueueChild ($page_hook){

			add_action( 'admin_print_styles-'  . $this->menu_page_hook, function () {
				wp_enqueue_style	( array('recombee_re_settings_css' ));
			}, 10, 1 );
			
			add_action( 'admin_print_scripts-' . $this->menu_page_hook, function () {
				wp_enqueue_script( array('recombee_re_settings_js'));
			}, 10, 1 );	
			
			if($page_hook == $this->menu_page_hook){
				
				$object_data = array(
					'AJAX_Marker'		=> RRE_PLUGIN_DIR,
					'ajaxUrl'			=> $this->recombee->rre_ajax_interface->get_virtual_page('ajax'),
					'recombeeUrl'		=> RRE_URL_SERVICE, 
					'dialogTitle'		=> __('Recombee Engine', 'recombee-recommendation-engine'),
					'inviteInitSync'	=> $this->blogSetting['invite_init_sync'],
					'inviteInitSyncText'=> ( $this->blogSetting['invite_init_sync'] ) ? __('Your credentials are correct. Do you wish to start the initial synchronization of your WC data to Recombee? (these data include products, customers and interactions between customers and products). Click OK to launch initial synchronization now. Click Cancel if you wish to execute it later manually.|ATTENTION! It is assumed that your database at Recombee is empty now.', 'recombee-recommendation-engine') : '',
					'inviteInitSyncProp'=> sprintf( __('You should start by creating an account at %s. There you get the credentials (ID of your database and corresponding secret key) that you copy to this plugin. Then you can continue with synchronization of data to your Recombee database.|Now we will select all available products and customers properties for you automatically', 'recombee-recommendation-engine'), RRE_URL_SERVICE ),
					'dbConnectLost' 	=> $this->dbConnectLost,
					'dbConnectInit'		=> __( 'Disconnected at start', 'recombee-recommendation-engine' ),
					'dbConnects'		=> $this->dbConnectValue(true),
					'dbConnectedCode'	=> RRE_DB_CONNECTED_CODE,
					'dbDisconnectsCode'	=> RRE_DB_DISCONNECTED_CODE,
					'dbResetTipText'	=> __( 'Reseted', 'recombee-recommendation-engine' ),
					'dbResetPrompt'		=> __( 'You are about to reset your database at Recombee. This action does not remove database itself, but completely erases its content. Click OK to activate the Launch Reset button, then click Launch Reset again to perform the reset. This operation is irreversible.', 'recombee-recommendation-engine' ),
					'dbSyncInteractionsAlert' => __( 'To be able to synchronize shop customers interactions you have to synchronize WC Product Properties & WC Customer Properties & WC Products & WC Customers first.', 'recombee-recommendation-engine' ),					
				);
				wp_localize_script( 'recombee_re_settings_js', 'recombeeRe_vars', $object_data );
			}
	}
	
	/* Add admin body classes */
	public function addClasses($classes){
		
		$classes = $classes . ' custom-scope ';

		return $classes;
	}
	
	/*
	* Method to output content for 'callback' => 'recombee_iframe'
	*/	
	protected  function recombeeIframe(){
		?>
			<div id="recombee_modal"></div>
		<?php
	}
	
	/*
	* Method to output content if settingsArray['warning'] return false
	*/	
	protected function pageWarningCallback(){
			?>
				<h2><?php _e('Recombee Warning!!', 'recombee-recommendation-engine') ?></h2>
				<div id="tabs">
					<div class="info"><?php _e('You need to install and activate WooCommerce plugin before using Recombee Recommendation Engine.', 'recombee-recommendation-engine') ?></div>
				</div>
			<?php
	}
	
	/*
	* Method to output help tabs - optional
	* Should return an array (Tab Name => Tab Content HTML)
	* Return Null - to hide tabs
	*/
 	public function pageContentHelpTabCallback(){
		
		/* return null; */
		
		return array(
			
			__('Database & Key', 'recombee-recommendation-engine' ) =>	'<p>' . sprintf(__('Visit %s to get ID of your database and corresponding secret key', 'recombee-recommendation-engine'), '<a href="https://docs.recombee.com/reql.html">Recombee</a>') . '</p>',
		);
	}
	
	/*
	* Method to output help tabs sidebar - optional
	* Should return Side Bar Content HTML)
	* Return Null - to hide sidebar
	* BE SURE pageContentHelpTabCallback return NOT null
	*/
 	public function pageContentHelpSidebarCallback(){
		
		return null;
		
/* 		ob_start();
			?>
				<script type="text/javascript">(function($){ $(document).ready(function () { $(".prettyPhoto").prettyPhoto({ theme: 'pp_default', show_title: true, allow_resize: true, }); }); })(jQuery);</script>
				<p><strong><?php _e( 'Watch video help:', 'recombee-recommendation-engine' )?></strong></p>
				<p><a class="prettyPhoto" href="https://www.youtube.com/embed/xLsMSXl-6Ts?&autoplay=0&rel=0&iframe=true&width=100%&height=100%" title="<?php _e('How to create a database at Recombee', 'recombee-recommendation-engine')?>"><?php _e('DataBase create', 'recombee-recommendation-engine')?></a></p>
			<?php
		return ob_get_clean(); */
	}
}