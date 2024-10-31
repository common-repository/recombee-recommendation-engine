<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RecombeeReBlogSettingsDb {
	
	public $requestsQueryLog = array();
	
	private $recombee;
	
	protected static $_instance = null;
	
	private function __construct(){
		
		$this->recombee = RecombeeRe::instance();
	}
	
	public static function instance(){
		
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	
	/* INIT SYNCHRONIZATION */
	public function dbConnectionAction(){
		
		if ( check_ajax_referer( 'dbConnectionAction', 'nonce', false) ){
			
			$api_identifier		= sanitize_text_field($_POST['data']['api_identifier']);
			$api_secret_token	= sanitize_text_field($_POST['data']['api_secret_token']);
			
			$this->recombee->communicator->destroy();
			$this->recombee->communicator = RecombeeReCommunicator::instance($api_identifier, $api_secret_token);
			
			$response = $this->recombee->communicator->reqsListItems(array('operation_name' => 'db connect/disconnect', 'count' => 1, 'force_log' => true));

			if( isset($response['success']) ){
				
				$options = get_option( RRE_BLOG_SETTINGS_PRESET_NAME, false );
				
				if($options){
					
					$current_status = (int)$options['db_connection_code'];
					( $current_status === RRE_DB_DISCONNECTED_CODE ) ? $options['db_connection_code'] = RRE_DB_CONNECTED_CODE : $options['db_connection_code'] = RRE_DB_DISCONNECTED_CODE;

					$new_setting = $this->recombee->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME, $options);
					
					if( $new_setting ){
						$this->ajaxAnswer(
							'nonce_true',
							array('statusCode' => $new_setting['db_connection_code']), 
							array(( $current_status === RRE_DB_DISCONNECTED_CODE ) ? __( 'Database successfully connected', 'recombee-recommendation-engine' ) : __( 'Database successfully disconnected', 'recombee-recommendation-engine' )
							)
						);
					}
					else{
						$this->ajaxAnswer('nonce_true', array('statusCode' => 400), array( __( 'Internal operation error', 'recombee-recommendation-engine' )));
					}
				}
				else{
					$this->ajaxAnswer('nonce_true', array('statusCode' => 400), array( __( 'Credentials for connecting to your Recombee database not found', 'recombee-recommendation-engine' )));
				}
			}
			else{
				$this->ajaxAnswer('nonce_true', array('statusCode' => RRE_DB_DISCONNECTED_CODE), array( $response['errors']));
			}
		}
		else {
			$this->ajaxAnswer('nonce_false', array('statusCode' => 400), array( __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' )));
		}
	}
	
	public function dbSyncWcProductsProp(){
		
		if ( check_ajax_referer( 'dbSyncWcProductsProp', 'nonce', false) ){
			
			$actionTime				= sanitize_text_field($_POST['data']['actionTime']);
			$prop_stack				= array();
			$recombee_props			= array();
			$shop_all_props			= $this->recombee->get_product_sync_prop_all();
			$blog_setting			= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
			$prod_prop_sync_setting	= $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME);
			$shop_curr_props		= $blog_setting['db_product_prop_set'];
			
			$limit					= $blog_setting['db_sync_chunk_size'];
			$prod_prop_total		= count( $shop_curr_props );
			
			if( $prod_prop_total > 0 ){

				$response = $this->recombee->communicator->reqsListItemProperties(array('operation_name' => 'Getting Recombee side product properties before sync WP properties', 'force_log' => true));
				
				if( !isset($response['errors']) ){

					/* Properties, that already exists at Recombee */
					foreach($response['success'] as $recombeProp){
						$recombee_props[] = $recombeProp['name'];
					}
					
					/* Properties, that should be added at Recombee */ 
					$shop_add_props = array_diff( $shop_curr_props,  $recombee_props);
					
					/* Properties, that should be deleted at Recombee */
					$shop_del_props = array_diff( $recombee_props, $shop_curr_props);
										
					if( count($shop_add_props) > 0 ){
						foreach($shop_add_props as $shop_add_prop){
							$recombeeNewProps[] = array(
								'name' => $shop_add_prop,
								'type' => $shop_all_props[$shop_add_prop]['recombeeType'],
							);
						}
						$prop_stack[] = array('method' => 'reqsAddItemProperty', 'properties' => $recombeeNewProps, 'param' => array('force_log' => true) );
					}
					if( count($shop_del_props) > 0 ){
						foreach($shop_del_props as $shop_del_prop){
							$recombeeDelProps[] = array(
								'name' => $shop_del_prop,
							);
						}
						$prop_stack[] = array('method' => 'reqsDeleteItemProperty', 'properties' => $recombeeDelProps, 'param' => array('force_log' => true) );
					}
					
					if( count($prop_stack) > 0){
						
						$response = $this->recombee->communicator->reqsExecuteBatch('Sync Product Properties', $prop_stack, true);
						
						if( !isset($response['reqsExecuteBatch']['errors']) ){
							
							$new_setting['completed']	= $actionTime;
							$new_setting['current_sync_offset'] = $prod_prop_total;
							$new_setting['loop_errors'] = 0;
							$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, $new_setting);
							
							$this->ajaxAnswer(
								'nonce_true',
								array(
									'statusCode'	=> 200,
									'itemsPassed'	=> $new_setting['current_sync_offset'],
									'loopErrors'	=> $new_setting['loop_errors'],
									'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
								),
								array( sprintf( __('WC Product Properties for Database <strong>%s</strong> were successfully synchronized.', 'recombee-recommendation-engine' ), strtoupper($blog_setting['api_identifier'])))
							);
						}
						else{
							
							$new_setting['loop_errors'] = count($response['reqsExecuteBatch']['errors']);
							$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, $new_setting);
							
							$this->ajaxAnswer(
								'nonce_true',
								array(
									'statusCode'	=> 500,
									'itemsPassed'	=> $prod_prop_total,
									'loopErrors'	=> $new_setting['loop_errors'],
								),
								array( __('An error occurred during WC Product Properties synchronization. See requests-errors.log fo extra information', 'recombee-recommendation-engine' ))
							);
						}
					}
					else{
						
						$new_setting['completed']	= $actionTime;
						$new_setting['loop_errors'] = 0;
						$new_setting['current_sync_offset'] = $prod_prop_total;
						$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, $new_setting);
						
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 200,
								'itemsPassed'	=> $new_setting['current_sync_offset'],
								'loopErrors'	=> $new_setting['loop_errors'],
								'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
							),
							array( __( 'Everything is already in synced to Recombee', 'recombee-recommendation-engine' ) )
						);
					}
				}
				else{
					
					$new_setting['loop_errors'] = count($response['errors']);
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, $new_setting);
					
					$this->ajaxAnswer(
						'nonce_true',
						array(
							'statusCode'	=> 500,
							'itemsPassed'	=> 0,
							'loopErrors'	=> $new_setting['loop_errors'],
						),
						array( __('An error occurred during getting current Recombee Database properties. See requests-errors.log for extra information.', 'recombee-recommendation-engine' ))
					);
				}
			}
			else{
				
				$new_setting['completed']	= $actionTime;
				$new_setting['loop_errors'] = 0;
				$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, $new_setting);
				
				$this->ajaxAnswer(
					'nonce_true',
					array(
						'statusCode'	=> 200,
						'itemsPassed'	=> $prod_prop_total,
						'loopErrors'	=> $new_setting['loop_errors'],
						'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
					),
					array( __( 'There are no saved product properties. Nothing to sync.', 'recombee-recommendation-engine' ) )
				);
			}
		}
		else {	
			$this->ajaxAnswer('nonce_false', array('statusCode' => 403), array('Error' => __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' )) );
		}
	}
	
	public function dbSyncWcCustomersProp(){
		
		if ( check_ajax_referer( 'dbSyncWcCustomersProp', 'nonce', false) ){
			
			$actionTime				= sanitize_text_field($_POST['data']['actionTime']);
			$prop_stack				= array();
			$recombee_props			= array();
			$shop_all_props			= $this->recombee->get_customer_sync_prop_all();
			$blog_setting			= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
			$prod_prop_sync_setting	= $this->recombee->get_blog_setting(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME);
			$shop_curr_props		= $blog_setting['db_customer_prop_set'];
			
			$limit					= $blog_setting['db_sync_chunk_size'];
			$cust_prop_total		= count( $shop_curr_props );
			
			if( $cust_prop_total > 0 ){

				$response = $this->recombee->communicator->reqsListUserProperties(array('operation_name' => 'Getting customer properties on save settings', 'force_log' => true));
				
				if( !isset($response['errors']) ){

					/* Properties, that already exists at Recombee */
					foreach($response['success'] as $recombeProp){
						$recombee_props[] = $recombeProp['name'];
					}
					
					/* Properties, that should be added at Recombee */ 
					$shop_add_props = array_diff( $shop_curr_props,  $recombee_props);	

					/* Properties, that should be deleted at Recombee */
					$shop_del_props = array();
					foreach($shop_all_props as $prop_name => $shop_all_prop){
						if(!in_array($prop_name, $shop_curr_props) && in_array($prop_name, $recombee_props)){
							$shop_del_props[] = $prop_name;
						}
					}
					
					if( count($shop_add_props) > 0 ){
						foreach($shop_add_props as $shop_add_prop){
							$recombeeNewProps[] = array(
								'name' => $shop_add_prop,
								'type' => $shop_all_props[$shop_add_prop]['recombeeType'],
							);
						}
						$prop_stack[] = array('method' => 'reqsAddUserProperty', 'properties' => $recombeeNewProps, 'param' => array('force_log' => true) );
					}
					if( count($shop_del_props) > 0 ){
						foreach($shop_del_props as $shop_del_prop){
							$recombeeDelProps[] = array(
								'name' => $shop_del_prop,
							);
						}
						$prop_stack[] = array('method' => 'reqsDeleteUserProperty', 'properties' => $recombeeDelProps, 'param' => array('force_log' => true) );
					}
					
					if( count($prop_stack) > 0){
						
						$response = $this->recombee->communicator->reqsExecuteBatch('Sync Customer Properties', $prop_stack, true);
						
						if( !isset($response['reqsExecuteBatch']['errors']) ){
							
							$new_setting['completed']	= $actionTime;
							$new_setting['current_sync_offset'] = $cust_prop_total;
							$new_setting['loop_errors'] = 0;
							$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME, $new_setting);
							
							$this->ajaxAnswer(
								'nonce_true',
								array(
									'statusCode'	=> 200,
									'itemsPassed'	=> $new_setting['current_sync_offset'],
									'loopErrors'	=> $new_setting['loop_errors'],
									'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
								),
								array( sprintf( __('WC Customer Properties for DataBase <strong>%s</strong> were successfully synchronized.', 'recombee-recommendation-engine' ), strtoupper($blog_setting['api_identifier'])))
							);
						}
						else{
							
							$new_setting['loop_errors'] = count($response['reqsExecuteBatch']['errors']);
							$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME, $new_setting);
							
							$this->ajaxAnswer(
								'nonce_true',
								array(
									'statusCode'	=> 500,
									'itemsPassed'	=> $cust_prop_total,
									'loopErrors'	=> $new_setting['loop_errors'],
								),
								array( __('An error occurred during WC Customer Properties synchronization. See requests-errors.log fo extra information.', 'recombee-recommendation-engine' ))
							);
						}
					}
					else{
						
						$new_setting['completed']	= $actionTime;
						$new_setting['loop_errors'] = 0;
						$new_setting['current_sync_offset'] = $cust_prop_total;
						$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME, $new_setting);
						
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 200,
								'itemsPassed'	=> $new_setting['current_sync_offset'],
								'loopErrors'	=> $new_setting['loop_errors'],
								'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
							),
							array( __( 'Everything is already in synced to Recombee', 'recombee-recommendation-engine' ) )
						);
					}
				}
				else{
					
					$new_setting['loop_errors'] = count($response['errors']);
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME, $new_setting);
					
					$this->ajaxAnswer(
						'nonce_true',
						array(
							'statusCode'	=> 500,
							'itemsPassed'	=> 0,
							'loopErrors'	=> $new_setting['loop_errors'],
						),
						array( __('An error occurred during getting current Recombee Database properties. See requests-errors.log for extra information.', 'recombee-recommendation-engine' ))
					);
				}
			}
			else{
				
				$new_setting['completed']	= $actionTime;
				$new_setting['loop_errors'] = 0;
				$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PROP_PRESET_NAME, $new_setting);
				
				$this->ajaxAnswer(
					'nonce_true',
					array(
						'statusCode'	=> 200,
						'itemsPassed'	=> $cust_prop_total,
						'loopErrors'	=> $new_setting['loop_errors'],
						'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
					),
					array( __( 'There are no saved customer properties. Nothing to sync.', 'recombee-recommendation-engine' ) )
				);
			}
		}
		else {	
			$this->ajaxAnswer('nonce_false', array('statusCode' => 403), array('Error' => __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' )) );
		}
	}
	
	public function dbSyncWcProducts(){
				
		if ( check_ajax_referer( 'dbSyncWcProducts', 'nonce', false) ){
			
			$blog_setting		= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
			$prod_sync_setting	= $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME);
			$sync_stat			= $this->recombee->menu->getSyncStatistic();
			
			$productsTotal = count($sync_stat['wcProductsTotal']);
			
			$limit = $blog_setting['db_sync_chunk_size'];
			if( (int)$prod_sync_setting['current_sync_offset'] >= $productsTotal){
				$offset = 0;
				$loop_errors = 0;
				$prod_sync_setting	= $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, array('loop_errors' => 0));
			}
			else{
				$offset			= (int)$prod_sync_setting['current_sync_offset'];
				$loop_errors	= (int)$prod_sync_setting['loop_errors'];
			}
			
			$products = wc_get_products( array(
				'limit'		=> $limit,
				'offset'	=> $offset,
				'status'	=> $this->recombee->productStatuses,
				'orderby'	=> 'ID',
				'order'		=> 'ASC',
				'return'	=> 'objects',
				)
			);
			
			if( $productsTotal > 0 ){
				
				$products_expanded = array();
				$productsToSync = array();
				
				foreach($products as $product){
					$product_data = wc_get_product($product);
					$products_expanded[] = $product_data;
					
					if($product_data->is_type( 'variable' )){
						$variables = wc_get_product( $product_data->get_id() )->get_children();
						
						if(count($variables) > 0){
							foreach($variables as $variable){
								$products_expanded[] = wc_get_product($variable);
							}
						}
					}
				}
				
				foreach($products_expanded as $product_expanded){
					
					$prop_set = $this->getProductPropertiesValue($product_expanded);
					if(isset($prop_set['rejected'])){
						
						foreach($prop_set['rejected'] as $prop_name => $reject_data){
							
							$this->recombee->communicator->logRequestErr( 'Getting WC Product Property "' . $prop_name . '"', $reject_data, true );
							$loop_errors++;
						}
						unset($prop_set['rejected']);
					}
					
					$productsToSync[] = $prop_set;
				}
	
				/* PRODUCTS SYNC */
				$prod_stack = array(
					array('method' => 'reqsSetItemValues', 'properties' => $productsToSync, 'param' => array('cascadeCreate' => true, 'force_log' => true) ),
				);
				
				if( count($prod_stack) > 0 ){
					
					$response = $this->recombee->communicator->reqsExecuteBatch('Sync WC Products', $prod_stack, true);
					if( isset($response['reqsExecuteBatch']['errors']) ){
						$loop_errors += count($response['reqsExecuteBatch']['errors']);
					}
				}

				$new_setting['loop_errors'] = $loop_errors;
				
				/* CONTINUE LOOP */
				if(($offset + $limit) < $productsTotal){
					
					$new_setting['current_sync_offset'] = $offset + $limit;
					$new_setting['is_on_sync']	= 1;
					$new_setting['completed']	= false;
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, $new_setting);
					
					$this->ajaxAnswer(
						'nonce_true',
						array(
							'statusCode'	=> 201,
							'itemsPassed'	=> $new_setting['current_sync_offset'],
							'loopErrors'	=> $new_setting['loop_errors'],
						),
						array( sprintf( __('<strong>%s</strong> WC Products synchronized', 'recombee-recommendation-engine' ), $limit))
					);
				}
				/* EXIT LOOP */
				else{
					
					$actionTime = sanitize_text_field($_POST['data']['actionTime']);
					
					$new_setting['current_sync_offset'] = $productsTotal;
					$new_setting['is_on_sync']	= 0;
					$new_setting['completed']	= $actionTime;
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, $new_setting);
					
					if( (int)$new_setting['loop_errors'] === 0 ){
						
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 200,
								'itemsPassed'	=> $productsTotal,
								'loopErrors'	=> $new_setting['loop_errors'],
								'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
							),
							array( sprintf( __('WC Products for DataBase <strong>%1$s</strong> synchronized. Errors: %2$s', 'recombee-recommendation-engine' ), strtoupper($blog_setting['api_identifier']), $new_setting['loop_errors']))
						);

					}
					else{
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 400,
								'itemsPassed'	=> $productsTotal,
								'loopErrors'	=> $new_setting['loop_errors'],
							),
							array( __( 'An error occurred during WC Products synchronization. See requests-errors.log fo extra information.', 'recombee-recommendation-engine' )) );
					}
				}
			}
			else{
				$this->ajaxAnswer('nonce_true',  array('statusCode' => 200), array( __( 'Your shop have no products yet.', 'recombee-recommendation-engine' )) );
			}
		}
		else {	
			$this->ajaxAnswer('nonce_false', array('statusCode' => 403), array('Error' => __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' )) );
		}
	}
	
	public function dbSyncWcCustomers(){
				
		if ( check_ajax_referer( 'dbSyncWcCustomers', 'nonce', false) ){
			
			$blog_setting		= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
			$cust_sync_setting	= $this->recombee->get_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME);
			$sync_stat			= $this->recombee->menu->getSyncStatistic();
			
			$customersTotal = count($sync_stat['wcOrdersPerCustomers']);
			
			$limit = $blog_setting['db_sync_chunk_size'];
			if( (int)$cust_sync_setting['current_sync_offset'] >= $customersTotal){
				$offset = 0;
				$loop_errors = 0;
				$cust_sync_setting	= $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME, array('loop_errors' => 0));
			}
			else{
				$offset			= (int)$cust_sync_setting['current_sync_offset'];
				$loop_errors	= (int)$cust_sync_setting['loop_errors'];
			}
			
			$customers	= $this->recombee->menu->get_orders_per_customer($offset, $limit);
			$commenters = $this->recombee->menu->get_comments_per_customer($offset, $limit);
			
			if( $customersTotal > 0 ){
		
				$customersToSync = array();
				
				/*
				There could be a users with the same email - one is anonymous,
				second is registered customer - add anonymous
				*/
				
				foreach($customers as $customer){
					foreach($commenters as $commenter){
						if( ($commenter['comment_author_email'] == $customer['billing_email']) && $commenter['user_id'] == 0 && $customer['user_id'] != 0){
							$customers[] = array(
								'user_id' => 0,
								'billing_email_hash' => $commenter['comment_author_email_hash'],
							);
						}
					}
				}
				
				foreach($customers as $customer){
					
					$wp_user = get_user_by( 'id', (int)$customer['user_id']);
					
					( (int)$customer['user_id'] !== 0 && is_object($wp_user) ) ? $prop_set = $this->getCustomerPropertiesValue($customer['user_id']) : $prop_set = $this->getGuestPropertiesValue($customer);
					
					if(isset($prop_set['rejected'])){
						
						foreach($prop_set['rejected'] as $prop_name => $reject_data){
							
							$this->recombee->communicator->logRequestErr( 'Getting WC Customer Property "' . $prop_name . '"', $reject_data, true );
							$loop_errors++;
						}
						unset($prop_set['rejected']);
					}
					
					$customersToSync[] = $prop_set;
				}
				
	
				/* PRODUCTS SYNC */
				$prod_stack = array(
					array('method' => 'reqsSetUserValues', 'properties' => $customersToSync, 'param' => array('cascadeCreate' => true, 'force_log' => true) ),
				);
				
				if( count($prod_stack) > 0 ){
					
					$response = $this->recombee->communicator->reqsExecuteBatch('Sync WC Customers', $prod_stack, true);
					if( isset($response['reqsExecuteBatch']['errors']) ){
						$loop_errors += /* (int)$cust_sync_setting['loop_errors'] + */ count($response['reqsExecuteBatch']['errors']);
					}
				}

				$new_setting['loop_errors'] = $loop_errors;
				
				/* CONTINUE LOOP */
				if(($offset + $limit) < $customersTotal){
					
					$new_setting['current_sync_offset'] = $offset + $limit;
					$new_setting['is_on_sync']	= 1;
					$new_setting['completed'] = false;
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME, $new_setting);
					
					$this->ajaxAnswer(
						'nonce_true',
						array(
							'statusCode'	=> 201,
							'itemsPassed'	=> $new_setting['current_sync_offset'],
							'loopErrors'	=> $new_setting['loop_errors'],
						),
						array( sprintf( __('<strong>%s</strong> WC Customers synchronized', 'recombee-recommendation-engine' ), $limit))
					);
				}
				/* EXIT LOOP */
				else{
					
					$actionTime = sanitize_text_field($_POST['data']['actionTime']);
					
					$new_setting['current_sync_offset'] = $customersTotal;
					$new_setting['is_on_sync']	= 0;
					$new_setting['completed'] = $actionTime;
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME, $new_setting);
					
					if( (int)$new_setting['loop_errors'] === 0 ){
						
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 200,
								'itemsPassed'	=> $customersTotal,
								'loopErrors'	=> $new_setting['loop_errors'],
								'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
							),
							array( sprintf( __('WC Customers for Database <strong>%1$s</strong> synchronized. Errors: %2$s', 'recombee-recommendation-engine' ), strtoupper($blog_setting['api_identifier']), $new_setting['loop_errors']))
						);

					}
					else{
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 400,
								'itemsPassed'	=> $customersTotal,
								'loopErrors'	=> $new_setting['loop_errors'],
							),
							array( __( 'An error occurred during WC Customers synchronization. See requests-errors.log fo extra information.', 'recombee-recommendation-engine' )) );
					}
				}
			}
			else{
				$this->ajaxAnswer('nonce_true',  array('statusCode' => 200), array( __( 'Your shop have no any customers yet (neither registered nor anonymous).', 'recombee-recommendation-engine' )) );
			}
		}
		else {	
			$this->ajaxAnswer('nonce_false', array('statusCode' => 403), array('Error' => __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' )) );
		}
	}
	
	public function dbSyncWcInteractions(){
		
		if ( check_ajax_referer( 'dbSyncWcInteractions', 'nonce', false) ){
			
			$blog_setting		= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
			$intr_sync_setting	= $this->recombee->get_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME);
			$sync_stat			= $this->recombee->menu->getSyncStatistic();
			$current_sync_offset= $intr_sync_setting['current_sync_offset'];
			
			$users_orders		= count($sync_stat['wcOrdersPerCustomers']);
			$users_comments		= count($sync_stat['wcCommentsPerCustomers']);
			
			$purchases_offset	= (int)$intr_sync_setting['purchases_sync_offset'];
			$detail_view_offset = (int)$intr_sync_setting['detail_view_sync_offset'];
			$cart_add_offset	= (int)$intr_sync_setting['cart_add_sync_offset'];
			$rating_offset		= (int)$intr_sync_setting['rating_sync_offset'];
			
			$limit = $blog_setting['db_sync_chunk_size'];
						
			$intrTotal = ($users_orders * 3 + $users_comments);
			
			if( $intrTotal > 0 ){
				
				($purchases_offset > $users_orders)		? $purchases_offset = $users_orders		: '';
				($detail_view_offset > $users_orders)	? $detail_view_offset = $users_orders	: '';
				($cart_add_offset > $users_orders)		? $cart_add_offset = $users_orders		: '';
				($rating_offset > $users_comments)		? $rating_offset = $users_comments		: '';
							
				if( ($purchases_offset + $detail_view_offset + $cart_add_offset + $rating_offset) >= $intrTotal ){
						
						$purchases_offset	= 0;
						$detail_view_offset = 0;
						$cart_add_offset	= 0;
						$rating_offset		= 0;
						$loop_errors		= 0;
						$current_sync_offset= 0;
						
						$intr_sync_setting	= $this->recombee->set_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, array(
							'purchases_sync_offset'		=> $purchases_offset,
							'detail_view_sync_offset'	=> $detail_view_offset,
							'cart_add_sync_offset'		=> $cart_add_offset,
							'rating_sync_offset'		=> $rating_offset,
							'loop_errors'				=> $loop_errors,
							'current_sync_offset'		=> $current_sync_offset,
						));
						$offset_name	= 'purchases_sync_offset';
						$offset_value	= 0;
				}
				else{

					if( $purchases_offset < $users_orders){
						$offset_name	= 'purchases_sync_offset';
						$offset_value	= $purchases_offset;
					}
					else if($detail_view_offset < $users_orders){
						$offset_name	= 'detail_view_sync_offset';
						$offset_value	= $detail_view_offset;
					}
					else if($cart_add_offset < $users_orders){
						$offset_name	= 'cart_add_sync_offset';
						$offset_value	= $cart_add_offset;
					}
					else if($rating_offset < $users_comments){
						$offset_name	= 'rating_sync_offset';
						$offset_value	= $rating_offset;
					}

					$loop_errors = (int)$intr_sync_setting['loop_errors'];
				}
				
				$propToSync			= array();
				$purchasesToSync	= array();
				$viewsToSync		= array();
				$cartAddToSync		= array();
				$ratingsToSync		= array();
				
				switch ($offset_name){
				
					case 'purchases_sync_offset' :
						
						$purchases	= $this->recombee->menu->get_orders_per_customer($offset_value, $limit);
						foreach($purchases as $purchase){
							$current_sync_offset += $purchase['orders_total'];
							$user_purchases = $this->getCustomerPurchaseValuePseudo($purchase);
							if($user_purchases){
								$purchasesToSync[] = $user_purchases;
								( (int)$purchase['user_id'] !== 0 ) ? $propToSync[] = $this->getCustomerPropertiesValue($purchase['user_id']) : $propToSync[] = $this->getGuestPropertiesValue($purchase);
							}
						}
						break;
							
					case 'detail_view_sync_offset':
						
						$detail_views = $this->recombee->menu->get_orders_per_customer($offset_value, $limit);
						foreach($detail_views as $detail_view){
							$current_sync_offset += $detail_view['sum_orders_items'];
							$user_detail_views = $this->getCustomerDetailViewPseudo($detail_view);
							if($user_detail_views){
								$viewsToSync[] = $user_detail_views;
								( (int)$detail_view['user_id'] !== 0 ) ? $propToSync[] = $this->getCustomerPropertiesValue($detail_view['user_id']) : $propToSync[] = $this->getGuestPropertiesValue($detail_view);
							}
						}
						break;
					
					case 'cart_add_sync_offset':
						
						$cart_adds = $this->recombee->menu->get_orders_per_customer($offset_value, $limit);
						foreach($cart_adds as $cart_add){
							$current_sync_offset += $cart_add['sum_orders_items'];
							$user_cart_add = $this->getCustomerCartAddValuePseudo($cart_add);							
							if($user_cart_add){
								$cartAddToSync[] = $user_cart_add;
								( (int)$cart_add['user_id'] !== 0 ) ? $propToSync[] = $this->getCustomerPropertiesValue($cart_add['user_id']) : $propToSync[] = $this->getGuestPropertiesValue($cart_add);
							}
						}
						break;
						
					case 'rating_sync_offset':
						
						$ratings = $this->recombee->menu->get_comments_per_customer($offset_value, $limit);
						foreach($ratings as $rating){
							$current_sync_offset += $rating['comments_total'];
							$user_ratings = $this->getCustomerRatingValueRseudo($rating);
							if($user_ratings){
								$ratingsToSync[] = $user_ratings;
								( (int)$rating['user_id'] !== 0 ) ? $propToSync[] = $this->getCustomerPropertiesValue($rating['user_id']) : $propToSync[] = $this->getGuestPropertiesValue($rating);
							}
						}
						break;

				}
				
				/* INTERACTIONS SYNC */		
				$batch_stack = array();
				
				( count($purchasesToSync)	> 0	) ? $batch_stack[] = array('method' => 'reqsAddPurchase',		'properties' => $purchasesToSync,'param' => array('cascadeCreate' => true, 'force_log' => true)) : '';
				( count($viewsToSync)		> 0	) ? $batch_stack[] = array('method' => 'reqsAddDetailView',		'properties' => $viewsToSync,	 'param' => array('cascadeCreate' => true, 'force_log' => true)) : '';
				( count($cartAddToSync)		> 0	) ? $batch_stack[] = array('method' => 'reqsAddCartAddition',	'properties' => $cartAddToSync,  'param' => array('cascadeCreate' => true, 'force_log' => true)) : '';
				( count($ratingsToSync)		> 0	) ? $batch_stack[] = array('method' => 'reqsAddRating',			'properties' => $ratingsToSync,  'param' => array('cascadeCreate' => true, 'force_log' => true)) : '';
				( count($propToSync)		> 0	) ? $batch_stack[] = array('method' => 'reqsSetUserValues',		'properties' => $propToSync,	 'param' => array('cascadeCreate' => true, 'force_log' => true)) : '';
								
				if( count($batch_stack) > 0 ){
					
					$response = $this->recombee->communicator->reqsExecuteBatch('Sync User Interactions', $batch_stack, true);
					if( isset($response['reqsExecuteBatch']['errors']) ){
						$loop_errors += count($response['reqsExecuteBatch']['errors']);
					}
				}

				$new_setting['current_sync_offset'] = $current_sync_offset;
				$new_setting['loop_errors']			= $loop_errors;
				
				/* EXIT LOOP */
				if( $offset_name == 'rating_sync_offset' && ($purchases_offset + $detail_view_offset + $cart_add_offset + $rating_offset + $limit) >= $intrTotal){
					
					$actionTime = sanitize_text_field($_POST['data']['actionTime']);
					
					$new_setting['purchases_sync_offset']	= $users_orders;
					$new_setting['detail_view_sync_offset']	= $users_orders;
					$new_setting['cart_add_sync_offset']	= $users_orders;
					$new_setting['rating_sync_offset']		= $users_comments;
					$new_setting['completed']				= $actionTime;
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, $new_setting);
					
					if( (int)$new_setting['loop_errors'] === 0 ){
						
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 200,
								'itemsPassed'	=> $current_sync_offset,
								'loopErrors'	=> $new_setting['loop_errors'],
								'actionTime'	=> date('d-m-Y H:i:s e', $actionTime/1000),
							),
							array( sprintf( __('WC Users Interactions (Purchases, Products views, Ratings, Cart Additions) for Database <strong>%s</strong> synchronized', 'recombee-recommendation-engine' ), strtoupper($blog_setting['api_identifier'])))
						);

					}
					else{
						$this->ajaxAnswer(
							'nonce_true',
							array(
								'statusCode'	=> 400,
								'itemsPassed'	=> $current_sync_offset,
								'loopErrors'	=> $new_setting['loop_errors'],
							),
							array( __( 'An error occurred during WC Customers interactions synchronization. See requests-errors.log fo extra information.', 'recombee-recommendation-engine' )) );
					}
				}
				/* CONTINUE LOOP */
				else{
					
					$new_setting[$offset_name] = $offset_value + $limit;
					$new_setting['completed'] = false;
					$new_setting = $this->recombee->set_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, $new_setting);
					
					$this->ajaxAnswer(
						'nonce_true',
						array(
							'statusCode'	=> 201,
							'itemsPassed'	=> $current_sync_offset,
							'loopErrors'	=> $new_setting['loop_errors'],
						),
						array( sprintf( __('<strong>%s</strong> WC Customers interactions synchronized', 'recombee-recommendation-engine' ), $limit))
					);
				}
			}
			else{
				$this->ajaxAnswer('nonce_true',  array('statusCode' => 200), array( __( 'Your Shop have no interactions (no purchases, product views, ratings, additions to cart) yet.', 'recombee-recommendation-engine' )) );
			}
		}
		else {	
			$this->ajaxAnswer('nonce_false', array('statusCode' => 403), array('Error' => __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' )) );
		}
	}
	
	public function dbReset(){

		$return = (object)[];
		
		if ( check_ajax_referer( 'dbReset', 'nonce', false) ){
			
			$response = $this->recombee->communicator->reqsResetDataBase(array('operation_name' => 'DataBase reset', 'force_log' => true) );
			
			if( !isset($response['errors']) ){
				
				$actionTime = sanitize_text_field($_POST['data']['actionTime']);
				
				$blog_setting		= $this->recombee->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME, array(
					'db_product_prop_set'	=> $this->recombee->get_product_sync_prop_keys(true),
					'db_customer_prop_set'	=> $this->recombee->get_customer_sync_prop_keys(true),
				));
				$prod_sync_setting	= $this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, array(
					'current_sync_offset'	=> 0,
					'loop_errors'			=> 0,
					'completed'				=> false,
				));
				$cust_sync_setting	= $this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME, array(
					'current_sync_offset'	=> 0,
					'loop_errors'			=> 0,
					'completed'				=> false,
				));
				$intr_sync_setting	= $this->recombee->set_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, array(
					'current_sync_offset'	=> 0,
					'loop_errors'			=> 0,
					'completed'				=> false,
				));
				$reset_sync_setting	= $this->recombee->set_blog_setting(RRE_SYNC_DB_RESET_PRESET_NAME, array(
					'completed' => $actionTime,
				));
				
				$this->ajaxAnswer(
					'nonce_true',
					array(
						'statusCode' => 200,
						'actionTime' => date('d-m-Y H:i:s e', $actionTime/1000),
						'new_preset' => $blog_setting,
					),
					sprintf( __('Database <strong>%s</strong> reset request queued. Please, wait.', 'recombee-recommendation-engine' ), strtoupper($blog_setting['api_identifier']) )
				);
			}
			else{
				$this->ajaxAnswer(
					'nonce_true',
					array('statusCode' => 400),
					sprintf( __('An error occurred during DataBase reset: <i>%s</i>.<br>See requests-errors.log for extra information.', 'recombee-recommendation-engine' ), implode(', ', $response['reqsResetDataBase']['errors'] ) )
				);
			}
		}
		else {
			$this->ajaxAnswer('nonce_false',  array('statusCode' => 403), array(
				'Error' => __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' )
			));
		}
	}
	
	
	/* PSEUDO DATA */
	private function getCustomerPurchaseValuePseudo($customer_data){
		global $wpdb;
		$return = false;
		( (int)$customer_data['user_id'] === 0 ) ? $customer_id = $customer_data['billing_email_hash'] : $customer_id = (int)$customer_data['user_id'];
		
		$args = array(
			'customer'	=> $customer_data['billing_email'],
			'limit'		=> -1,
			'status'	=> array('completed', 'processing', 'on-hold'),
		);
		$customerOrders = wc_get_orders( $args );
		
		if(count($customerOrders) > 0){
			foreach($customerOrders as $customerOrder){
				
				$order_created = new DateTime($customerOrder->get_date_created());
				$order_items = $customerOrder->get_items();
				
				foreach($order_items as $order_item){
					
					if(!$order_item->get_product()){
						/* this product is deleted */
						continue;
					}
					$return[] = array(
						'id'			=> $customer_id,
						'product'		=> $order_item->get_product()->get_id(),
						'properties'	=> array(
							'timestamp'	=> $order_created->format( DATE_ATOM ),
							'amount'	=> (int)$order_item->get_quantity(),
							'price'		=> (int)$order_item->get_total(),
						),
					);
				}
			} 
		}
		return $return;
	}
	
	private function getCustomerDetailViewPseudo($customer_data){
		
		$return = false;
		( (int)$customer_data['user_id'] === 0 ) ? $customer_id = $customer_data['billing_email_hash'] : $customer_id = (int)$customer_data['user_id'];
		
		$args = array(
			'customer'	=> $customer_data['billing_email'],
			'limit'		=> -1,
			'status'	=> array('trash', 'pending', 'processing', 'on-hold', 'completed', 'refunded', 'failed', 'cancelled'),
		);
		$customerOrders = wc_get_orders( $args );
		
		if(count($customerOrders) > 0){
			foreach($customerOrders as $customerOrder){
				
				$order_created = new DateTime($customerOrder->get_date_created());
				$view_time = $order_created->modify('-15 minute')->format( DATE_ATOM );
				$order_items = $customerOrder->get_items();
				
				foreach($order_items as $order_item){
					
					if(!$order_item->get_product()){
						/* this product is deleted */
						continue;
					}
					$return[] = array(
						'id'			=> $customer_id,
						'product'		=> $order_item->get_product()->get_id(),
						'properties'	=> array(
							'timestamp'	=> $view_time,
							'duration'	=> 60,
						),
					);
				}
			}
		}
		return $return;
	}
	
	private function getCustomerCartAddValuePseudo($customer_data){
		
		$return = false;
		( (int)$customer_data['user_id'] === 0 ) ? $customer_id = $customer_data['billing_email_hash'] : $customer_id = (int)$customer_data['user_id'];
		
		$args = array(
			'customer'	=> $customer_data['billing_email'],
			'limit'		=> -1,
			'status'	=> array('trash', 'pending', 'processing', 'on-hold', 'completed', 'refunded', 'failed', 'cancelled'),
		);
		$customerOrders = wc_get_orders( $args );
		
		if(count($customerOrders) > 0){
			foreach($customerOrders as $customerOrder){
				
				$order_created = new DateTime($customerOrder->get_date_created());
				$order_items = $customerOrder->get_items();
				
				foreach($order_items as $order_item){
					
					if(!$order_item->get_product()){
						/* this product is deleted */
						continue;
					}
					$return[] = array(
						'id'			=> $customer_id,
						'product'		=> $order_item->get_product()->get_id(),
						'properties'	=> array(
							'timestamp'	=> $order_created->format( DATE_ATOM ),
							'amount'	=> (int)$order_item->get_quantity(),
							'price'		=> (int)$order_item->get_total(),
						),
					);
				}
			}
		}
		return $return;
	}
	
	private function getCustomerRatingValueRseudo($customer_data){
		
		$return = false;
		( (int)$customer_data['user_id'] === 0 ) ? $customer_id = $customer_data['comment_author_email_hash'] : $customer_id = (int)$customer_data['user_id'];
			
		$args = array(
			'author_email'				=> $customer_data['comment_author_email'],
			'fields'					=> '',
			'number'					=> '',
			'offset'					=> 0,
			'no_found_rows'				=> true,
			'orderby'					=> 'user_id',
			'order'               		=> 'DESC',
			'post_status'				=> 'publish',
			'post_type'					=> 'product',
			'status'					=> 'all approve hold trash spam post-trashed',
			'type'						=> 'comment',
			'count'						=> false,
			'hierarchical'				=> 'flat',
			'update_comment_meta_cache'	=> true,
			'update_comment_post_cache'	=> false,
		);
		
		if( $comments = get_comments( $args ) ){
			foreach( $comments as $comment ){
				if(!empty($comment)){
					$rating = (int)get_comment_meta($comment->comment_ID, 'rating', true);
					$comment_date = new DateTime($comment->comment_date);
					
					$return[] = array(
						'id'			=> $customer_id,
						'product'		=> $comment->comment_post_ID,
						'rating'		=> ((int)$rating - 3)/2,
						'properties'	=> array(
							'timestamp'	=> $comment_date->format( DATE_ATOM ),
						),
					);
				}
			}
		}
		return $return;
	}
	
	
	/* REGULAR SYNCHRONIZATION */
	public function dbAddProductsTax( $taxonomy, $object_type, $args ){
		
		if(in_array('product', $args['object_type'])){
			
			$shop_all_props	= $this->recombee->get_product_sync_prop_all();
			$response		= $this->recombee->communicator->reqsListItemProperties(array('operation_name' => 'Getting Recombee side product properties before add new taxonomy', 'force_log' => true));
					
			if( !isset($response['errors']) ){
				
				$prop_name = $this->recombee->product_prop_name_to_recombee_format($taxonomy);
				
				if( !array_key_exists($prop_name, $shop_all_props) ){
					
					$properties = array(
						'name' => $prop_name,
						'type' => 'set',
					);
					
					$response = $this->recombee->communicator->reqsAddItemProperty($properties, array('operation_name' => 'Add Product Taxonomy name', 'force_log' => true));
					
					if( !isset($response['errors']) ){
						
						$current_prod_prop		= $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME);
						$current_blog_setting	= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
						
						$current_blog_setting['db_product_prop_set'][] = $prop_name;
		
						$this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, array('current_sync_offset' => $current_prod_prop['current_sync_offset'] + 1 ));
						$this->recombee->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME,		 array('db_product_prop_set' => $current_blog_setting['db_product_prop_set'] ));
					
					}
					else{
						add_filter( 'wp_redirect', array($this, 'new_taxonomy_add_query_var_err') );
					}
				}
			}
			else{
				add_filter( 'wp_redirect', array($this, 'getting_prop_query_var_err') );
			}
		}
	}
	
	public function dbAddProductsAtt( $id, $data ){
		
		$shop_all_props	= $this->recombee->get_product_sync_prop_all();
		$response		= $this->recombee->communicator->reqsListItemProperties(array('operation_name' => 'Getting Recombee side product properties before add new attribute', 'force_log' => true));
				
		if( !isset($response['errors']) ){
			
			$prop_name = 'att_' . '::ID' . $id . '::' . ucwords($data['attribute_name']);
			$prop_name = $this->recombee->product_prop_name_to_recombee_format($prop_name);
			
			if( !array_key_exists($prop_name, $shop_all_props) ){
				
				$properties = array(
					'name' => $prop_name,
					'type' => 'set',
				);
				
				$response = $this->recombee->communicator->reqsAddItemProperty($properties, array('operation_name' => 'Add Product Attribute name', 'force_log' => true));
				
				if( !isset($response['errors']) ){
					
					$current_prod_prop		= $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME);
					$current_blog_setting	= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
					
					$current_blog_setting['db_product_prop_set'][] = $prop_name;
	
					$this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, array('current_sync_offset' => $current_prod_prop['current_sync_offset'] + 1 ));
					$this->recombee->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME,		 array('db_product_prop_set' => $current_blog_setting['db_product_prop_set'] ));
				
				}
				else{
					echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('There was an error on adding new product attribute to Recombee. See <i>%s</i> for extra information.'), RRE_PLUGIN_DIR.'/log/requests-errors.log') . '</p></div>';
				}
			}
		}
		else{
			echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('There was an error in Recombee getting product properties request. See <i>%s</i> for extra information.'), RRE_PLUGIN_DIR.'/log/requests-errors.log') . '</p></div>';
		}
	}

	public function dbDeleteProductsAtt( $id, $name, $taxonomy ){
		
		$prop_name = 'att_' . '::ID' . $id . '::' . ucwords($name);
		$prop_name = $this->recombee->product_prop_name_to_recombee_format($prop_name);
		
		$properties = array(
			'name' => $prop_name,
		);
				
		$response = $this->recombee->communicator->reqsDeleteItemProperty($properties, array('operation_name' => 'Delete Product Attribute name', 'force_log' => true));
				
		if( !isset($response['errors']) ){
			
			$current_prod_prop		= $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME);
			$current_blog_setting	= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
			
			unset($current_blog_setting['db_product_prop_set'][ $prop_name ]);

			$this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PROP_PRESET_NAME, array('current_sync_offset' => $current_prod_prop['current_sync_offset'] - 1 ));
			$this->recombee->set_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME,		 array('db_product_prop_set' => $current_blog_setting['db_product_prop_set'] ));
		
		}
		else{
			echo '<div id="recombee-wc-not-active-notice" class="notice notice-error is-dismissible"><p>' . sprintf(__('There was an error on adding new product property to Recombee. See <i>%s</i> for extra information.'), RRE_PLUGIN_DIR.'/log/requests-errors.log') . '</p></div>';
		}
	}
	
	public function dbUpdateProduct( $postId, $post = false, $update = false ){
		
		$productToSync = array();
		$product_data = wc_get_product($postId);
		
		if(!$product_data){
			return;
		}
		
		if( did_action('deleted_post') ){
			
			$productToSync[] = array(
				'id' => $postId,
				'properties' => array(
					'wpStatus' => 'deleted',
				),
			);
		}
		else if(	did_action('trashed_post')						|| 
					did_action('save_post')							||
					did_action('deleted_term_taxonomy')				||
					did_action('delete_term')						){
						
			if($product_data->is_type( 'variable' )){
				$variables = wc_get_product($product_data->get_id())->get_children();
				
				if(count($variables) > 0){
					foreach($variables as $variable){
						$variable_data = wc_get_product($variable);
						$productToSync[] = $this->getProductPropertiesValue($variable_data);
					}
				}
			}
			
			$productToSync[] = $this->getProductPropertiesValue($product_data);
			
			/* new product */
			if( $update && $post->post_type != 'product_variation' ){
				$this->maybeUpdateSyncOffset(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, 1);
			}
		}
		else{
			return;
		}
		if(count($productToSync) > 0 ){
			
			$result = array(
				'api_errors'	=> array(),
				'curl_errors'	=> array(),
				'api_success'	=> array(),
			);
			
			foreach($productToSync as $Item){
				$response = $this->recombee->communicator->reqsSetItemValues( $Item, array('cascadeCreate' => true, 'operation_name' => 'Update Product'));
				if( isset($response['errors']) ){
					$result['api_errors'][] = $response['errors']; 
				}
				if( isset($response['exception_type']) ){
					$result['curl_errors'][] = $response['exception_type']; 
				}
				if( isset($response['success']) ){
					$result['api_success'][] = $response['success']; 
				}
			}
		}
		
		if( count($result['api_errors']) > 0 ){
			add_filter( 'wp_redirect', array($this, 'product_update_query_var_err') );
		}
		if( count($result['curl_errors']) > 0 ){
			/* TO DO queue fault request to DB */
		}
	}
	
	public function dbDeleteProduct( $postIds = array() ){
		
		$filter		= array();
		$parentIds	= array();
		$itemProps  = array();
		$wcProductParentID = false;
		
		if( count($postIds) === 0 ){
			return;
		}
		/* Find out if the property 'wcProductParentID' is in sync with Recombee */
		$response = $this->recombee->communicator->reqsListItemProperties(array('operation_name' => 'Getting Recombee side product properties before deleting non-existing products'));
		
		if( isset($response['success']) ){
			foreach($response['success'] as $recombeeProp){
				if( $recombeeProp['name'] == 'wcProductParentID' ){
					$wcProductParentID = true;
					break;
				}
			}
		}
		else{
			
			$this->recombee->communicator->logRequestErr( 'Getting Recombee side product properties', 'It was not possible to get properties on the Recombee side. It is impossible to determine whether the property "wcProductParentID" exists. Aborting delete non-existing products.' );
			return;
		}
		/* If YES */
		if( $wcProductParentID ){
			
			foreach($postIds as $postId){
				$filter[] = '\'itemId\' == "' . $postId . '"';
			}
			/* Get 'wcProductParentID' value for every product for deleting */
			$response = $this->recombee->communicator->reqsListItems( array(
				'operation_name'	=> 'Getting non-existing WC Products vaues',
				'filter'			=> implode(' OR ', $filter),
				'returnProperties'	=> true,
				'includedProperties'=> 'wcProductParentID'
				)
			);
			/* Find out uniq parent product Ids */
			if( isset($response['success']) ){
				foreach( $response['success'] as $itemData ){
					if( !empty($itemData['wcProductParentID']) && !in_array($itemData['wcProductParentID'], $parentIds) ){
						$parentIds[] = $itemData['wcProductParentID'];
					}
				}
			}
			else{
				
				$this->recombee->communicator->logRequestErr('Getting non-existing WC Products properies value', 'Failed to get WC Products properies value to see, if some products has parents, aborting delete non-existing products.');
				return;
			}
		}
		/* If NO */
		else{
			
			$this->recombee->communicator->logRequestErr('Getting non-existing WC Products list', 'The property "wcProductParentID" does not exists at Recombee side, so non-existing parent products will not be deleted. Continue.');
		}
		/* Check if parent products really does not exists at WP side */
		if( count($parentIds) > 0 ){
			foreach($parentIds as $parentId){
				
				$product = wc_get_product( $parentId );
				if($product){
					unset($parentIds[ $parentId ]);
				}
			}
		}
		/* All the products to delete */
		$to_delete_products = array_merge($postIds, $parentIds);
		
		foreach($to_delete_products as $to_delete_product){
			
			$productToSync[] = array(
				'id' => $to_delete_product,
				'properties' => array(
					'wpStatus' => 'deleted',
				),
			);
		}
		
		$result = array(
			'api_errors'	=> array(),
			'curl_errors'	=> array(),
			'api_success'	=> array(),
		);
		
		foreach($productToSync as $Item){
			$response = $this->recombee->communicator->reqsSetItemValues( $Item, array('operation_name' => 'Update Product (Deleted)'));
			if( isset($response['errors']) ){
				$result['api_errors'][] = $response['errors']; 
			}
			if( isset($response['exception_type']) ){
				$result['curl_errors'][] = $response['exception_type']; 
			}
			if( isset($response['success']) ){
				$result['api_success'][] = $response['success']; 
			}
		}
		
		if( count($result['curl_errors']) > 0 ){
			/* TO DO queue fault request to DB */
		}
	}
	
	public function dbCreateCustomer( $customerId ){
		
		$this->maybeUpdateSyncOffset(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME, 1);
		$this->dbUpdateCustomer($customerId, true);
	}
	
	public function dbUpdateCustomer( $customerId, $createNew = false ){
		
		$current_action = current_action();
		$customerToSync = array();
		
		if( $createNew ){
			
			$customerToSync[] = $this->getCustomerPropertiesValue($customerId);	
		}
		else if( did_action('delete_user') ){
			$customerToSync[] = array(
				'id' => $customerId,
				'properties' => array(
					'wpStatus' => 'deleted',
				),
			);
		}
		else if( is_user_logged_in() && (did_action('woocommerce_update_customer') || did_action('woocommerce_checkout_update_user_meta')) ){
				
			$customerToSync[] = $this->getCustomerPropertiesValue($customerId);
		}
		else{
			return;
		}
		if(count($customerToSync) > 0 ){
			
			$result = array(
				'api_errors'	=> array(),
				'curl_errors'	=> array(),
				'api_success'	=> array(),
			);
			
			foreach($customerToSync as $Item){
				$response = $this->recombee->communicator->reqsSetUserValues( $Item, array('cascadeCreate' => true, 'operation_name' => 'Update Customer'));
				if( isset($response['errors']) ){
					$result['api_errors'][] = $response['errors']; 
				}
				if( isset($response['exception_type']) ){
					$result['curl_errors'][] = $response['exception_type']; 
				}
				if( isset($response['success']) ){
					$result['api_success'][] = $response['success']; 
				}
				$this->requestsQueryLog[$current_action] = $response;
			}
		}
		
		if( count($result['api_errors']) > 0 ){
			add_filter( 'wp_redirect', array($this, 'customer_update_query_var_err') );
		}
		if( count($result['curl_errors']) > 0 ){
			/* TO DO queue fault request to DB */
		}
	}
	
	public function dbUpdateTerms( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ){
		
		foreach($object_ids as $object_id){
			$this->dbUpdateProduct($object_id);
		}
	}
	
	public function setCustomerDetailView(){
		
		if ( check_ajax_referer( 'detailView', 'nonce', true) ){
			
			$view_time = new DateTime();
			$productID = (int)sanitize_text_field($_POST['data']['productID']);
			
			if( is_user_logged_in() ){
				$user_id	= get_current_user_id();
				$propToSync = array(
					'id'			=> $user_id,
					'properties'	=> array(
						'wpStatus'	=> get_userdata($user_id)->get('user_registered'),
						'wcRole'	=> (new WC_Customer($user_id))->get_role(),
					)
				);
			}
			else{
				$user_id	= $this->recombee->get_RAUID();
				$propToSync = array(
					'id'			=> $user_id,
					'properties'	=> array(
						'wpStatus'	=> 'anonymous',
						'wcRole'	=> 'guest',
					)
				);
			}
		
			$viewToSync = array(
				'id'			=> $user_id,
				'product'		=> $productID,
				'properties'	=> array(
					'timestamp'	=> $view_time->format( DATE_ATOM ),
				),
			);
			if( isset($_POST['data']['duration']) ){
				$duration  = (int)sanitize_text_field($_POST['data']['duration']);
				$viewToSync['properties']['duration'] = $duration;
			}
			
			$result = array(
				'api_errors'	=> array(),
				'curl_errors'	=> array(),
				'api_success'	=> array(),
			);

			$response	= $this->recombee->communicator->reqsAddDetailView( $viewToSync, array('cascadeCreate' => true, 'operation_name' => 'Set Customer DetailView Duration'));
			$update		= $this->recombee->communicator->reqsSetUserValues( $propToSync, array('cascadeCreate' => false, 'operation_name' => 'Update Customer Status on adding detail view duration'));
			
			if( isset($response['errors']) ){
				$result['api_errors'][] = $response['errors']; 
			}
			if( isset($response['exception_type']) ){
				$result['curl_errors'][] = $response['exception_type']; 
			}
			if( isset($response['success']) ){
				$result['api_success'][] = $response['success']; 
			}
			
			if( count($result['curl_errors']) > 0 ){
				/* TO DO queue fault request to DB */
			}
		}
	}
	
	public function setAddCartAddition($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data){
			
		$add_time = new DateTime();
		if($variation_id !== 0 ){
			$product = wc_get_product($variation_id);
		}
		else{
			$product = wc_get_product($product_id);
		}
		
		if( is_user_logged_in() ){
			$user_id	= get_current_user_id();
			$propToSync = array(
				'id'			=> $user_id,
				'properties'	=> array(
					'wpStatus'	=> get_userdata($user_id)->get('user_registered')
				)
			);
		}
		else{
			$user_id	= $this->recombee->get_RAUID();
			$propToSync = array(
				'id'			=> $user_id,
				'properties'	=> array(
					'wpStatus'	=> 'anonymous'
				)
			);
		}
		
		$addCartToSync = array(
			'id'			=> $user_id,
			'product'		=> $product->get_id(),
			'properties'	=> array(
				'timestamp'	=> $add_time->format( DATE_ATOM ),
				'amount'	=> $quantity,
				'price'		=> $product->get_price(),
			),
		);
		
		$result = array(
			'api_errors'	=> array(),
			'curl_errors'	=> array(),
			'api_success'	=> array(),
		);

		$response	= $this->recombee->communicator->reqsAddCartAddition( $addCartToSync, array('cascadeCreate' => true, 'operation_name' => 'Add Cart Addition'));
		$update		= $this->recombee->communicator->reqsSetUserValues( $propToSync, array('cascadeCreate' => false, 'operation_name' => 'Update Customer Status on adding cart addition'));
		
		if( isset($response['errors']) ){
			$result['api_errors'][] = $response['errors']; 
		}
		if( isset($response['exception_type']) ){
			$result['curl_errors'][] = $response['exception_type']; 
		}
		if( isset($response['success']) ){
			$result['api_success'][] = $response['success']; 
		}
		
		if( count($result['curl_errors']) > 0 ){
			/* TO DO queue fault request to DB */
		}
	}
	
	public function setAddPurchase($order_id){
		
		$order = wc_get_order($order_id);
		$order_items = $order->get_items();
		$order_created = new DateTime($order->get_date_created());
		
		if( is_user_logged_in() ){
			$user_id	= get_current_user_id();
			$propToSync = array(
				'id'			=> $user_id,
				'properties'	=> array(
					'wpStatus'	=> get_userdata($user_id)->get('user_registered')
				)
			);
		}
		else{
			$user_id	= $this->recombee->get_RAUID();
			$propToSync = array(
				'id'			=> $user_id,
				'properties'	=> array(
					'wpStatus'	=> 'anonymous'
				)
			);
		}
		
		foreach($order_items as $order_item){
			
			$purchased = $order_item->get_product();
			
			$purchasesToSync[] = array(array(
				'id'		 => $user_id,
				'product'	 => $purchased->get_id(),
				'properties' => array(
					'timestamp'	=> $order_created->format( DATE_ATOM ),
					'amount'	=> (int)$order_item->get_quantity(),
					'price'		=> (int)$order_item->get_total(),
				),
			));
			
			/* emulate parent item  purchased too */
			if( $purchased->is_type( 'variation' ) ){
				
				$parent_purchased = wc_get_product( $purchased->get_parent_id() );
				
				$purchasesToSync[] = array(array(
					'id'		 => $user_id,
					'product'	 => $parent_purchased->get_id(),
					'properties' => array(
						'timestamp'	=> $order_created->format( DATE_ATOM ),
						'amount'	=> (int)$order_item->get_quantity(),
						'price'		=> (int)$order_item->get_total(),
					),
				));
			}
		}
		
		$this->maybeUpdateSyncOffset(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, 1 + count($order_items));
		
		$result = array(
			'api_errors'	=> array(),
			'api_success'	=> array(),
		);
		$batchStack = array(
			array('method' => 'reqsAddPurchase', 'properties' => $purchasesToSync, 'param' => array('cascadeCreate' => true) ),
		);

		$response	= $this->recombee->communicator->reqsExecuteBatch('Add Customer Purchase', $batchStack );
		$update		= $this->recombee->communicator->reqsSetUserValues( $propToSync, array('cascadeCreate' => false, 'operation_name' => 'Update Customer status on adding purchase'));
		
		if( isset($response['reqsExecuteBatch']['errors']) ){
			$result['api_errors'][] = $response['reqsExecuteBatch']['errors']; 
		}
		if( isset($response['reqsExecuteBatch']['success']) ){
			$result['api_success'][] = $response['reqsExecuteBatch']['success']; 
		}
		
		if( count($result['api_errors']) > 0 ){
			/* TO DO queue fault request to DB */
		}
	}
	
	public function setAddRatingEdit($comment_id){
		
		$comment = get_comment($id = $comment_id);
		$post_id = $comment->comment_post_ID;
		$comment_post_type = get_post_type($post_id);
		$comment_created = new DateTime($comment->comment_date);
		
		if( ($comment_post_type == 'product' || $comment_post_type == 'product_variation') && $comment->comment_approved == 1 ){
			
			( $comment->user_id == 0 ) ? $user_id = get_comment_meta($comment_id, 'recombee_cmt_rauid', true) : $user_id = $comment->user_id;
			if( empty($user_id)){
				return;
			}
			$rating = get_comment_meta($comment_id, 'rating', true );
			
			$ratingToSync = array(
				'id'		 => $user_id,
				'product'	 => $post_id,
				'rating'	 => ((int)$rating - 3)/2,
				'properties' => array(
					'timestamp'	=> $comment_created->format( DATE_ATOM ),
				),
			);
			$this->setAddRating($ratingToSync);
		}
	}
	
	public function setAddRatingNew($comment_id, $comment_approved, $commentdata){
		
		$post_id = $commentdata['comment_post_ID'];
		$comment_post_type = get_post_type($post_id);
		$comment_created = new DateTime($commentdata['comment_date']);
		
		if( ($comment_post_type == 'product' || $comment_post_type == 'product_variation') && !is_admin() && $comment_approved === 1 ){
			
			$this->maybeUpdateSyncOffset(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, 1);
			$rating = get_comment_meta($comment_id, 'rating', true );
			
			$ratingToSync = array(
				'id'		 => ( is_user_logged_in() ) ? get_current_user_id() : $this->recombee->get_RAUID(),
				'product'	 => $post_id,
				'rating'	 => ((int)$rating - 3)/2,
				'properties' => array(
					'timestamp'	=> $comment_created->format( DATE_ATOM ),
				),
			);
			update_comment_meta( $comment_id, 'recombee_cmt_rauid', $this->recombee->get_RAUID() );
			$this->setAddRating($ratingToSync);
		}
	}

	public function setAddRatingStatus($comment){
		
		$post_id = $comment->comment_post_ID;
		$comment_post_type = get_post_type($post_id);
		$comment_created = new DateTime($comment->comment_date);
		
		if( $comment_post_type == 'product' || $comment_post_type == 'product_variation'){
			
			$rating = get_comment_meta($comment->comment_ID, 'rating', true );
			
			$ratingToSync = array(
				'id'		 => get_current_user_id(),
				'product'	 => $post_id,
				'rating'	 => ((int)$rating - 3)/2,
				'properties' => array(
					'timestamp'	=> $comment_created->format( DATE_ATOM ),
				),
			);
			$this->setAddRating($ratingToSync);
		}
	}
	
	public function setAddRating($ratingToSync){
		
		$result = array(
			'api_errors'	=> array(),
			'curl_errors'	=> array(),
			'api_success'	=> array(),
		);
		
		if( is_user_logged_in() ){
			$user_id	= $ratingToSync['id'];
			$propToSync = array(
				'id'			=> $user_id,
				'properties'	=> array(
					'wpStatus'	=> get_userdata($user_id)->get('user_registered')
				)
			);
		}
		else{
			$user_id	= $ratingToSync['id'];
			$propToSync = array(
				'id'			=> $user_id,
				'properties'	=> array(
					'wpStatus'	=> 'anonymous'
				)
			);
		}

		$response	= $this->recombee->communicator->reqsAddRating( $ratingToSync, array('cascadeCreate' => true, 'operation_name' => 'Add Rating'));
		$update		= $this->recombee->communicator->reqsSetUserValues( $propToSync, array('cascadeCreate' => false, 'operation_name' => 'Update Customer status on adding comment'));
		
		if( isset($response['errors']) ){
			$result['api_errors'][] = $response['errors']; 
		}
		if( isset($response['exception_type']) ){
			$result['curl_errors'][] = $response['exception_type']; 
		}
		if( isset($response['success']) ){
			$result['api_success'][] = $response['success']; 
		}
		
		if( count($result['curl_errors']) > 0 ){
			/* TO DO queue fault request to DB */
		}
	}
	
	
	/* USER MANIPULATION */
	public function addLoginRedirectVars( $user_login, $user ){
		
		if ( $this->recombee->is_frontend() ){
			add_filter( 'wp_redirect', array($this, 'customer_logged_in_query_var_success') );
		}
	}

	public function asyncMergeUsers(){
		
		if ( check_ajax_referer( 'MergeUsers', 'nonce', true) ){
			
			$user_id = get_current_user_id();
			
			/* add logged in user if it does not exists */
			$response = $this->recombee->communicator->reqsListUsers(array('operation_name' => 'Check current logged in user exists at Recombee', 'filter' => '\'userId\' == "' . $user_id . '"' ));
			if( isset($response['success']) ){
				if( count($response['success']) === 0 ){
					
					$userData = $this->getCustomerPropertiesValue($user_id);
					$response = $this->recombee->communicator->reqsSetUserValues( $userData, array('cascadeCreate' => true, 'operation_name' => 'Add new logged in user'));
				}
			}		
					
			/* merge logged in user if possible */
			$usersData = array(
				'id'		=> $user_id,
				'RAUID'		=> $this->recombee->get_RAUID(),
			);
			
			$result = array(
				'api_errors'	=> array(),
				'curl_errors'	=> array(),
				'api_success'	=> array(),
			);
			
			$response = $this->recombee->communicator->reqsMergeUsers( $usersData, array('cascadeCreate' => false, 'operation_name' => 'maybe Merge Users'));
			if( isset($response['errors']) ){
				$result['api_errors'][] = $response['errors']; 
			}
			if( isset($response['exception_type']) ){
				$result['curl_errors'][] = $response['exception_type']; 
			}
			if( isset($response['success']) ){
				$result['api_success'][] = $response['success']; 
			}
			
			if( count($result['curl_errors']) > 0 ){
				/* TO DO queue fault request to DB */
			}
		}
	}


	/* PROPERTIES DATA GETTERS */
	private function getProductPropertiesValue($product){
		
		$return = array();
		
		$blog_setting	=  $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		$all_props		=  $this->recombee->get_product_sync_prop_all();
		
		$return['id'] = $product->get_id();
		$log_product  = $product;
		
		foreach($all_props as $prop_name => $prop_data){
			
			if($prop_data['builtin'] || in_array($prop_name, $blog_setting['db_product_prop_set'])){
				if( $prop_data['innerType'] == 'wp_meta' ){
					
					$prop_val = call_user_func($prop_data['dataGetterClb']);
					
					if( $prop_name == 'wcShopId' ){
						$return['properties'][ $prop_name ] = ( is_multisite() ) ? call_user_func($prop_data['typeConvClb'], $prop_val) : -1;
					}
					else{
						$return['properties'][ $prop_name ] = call_user_func($prop_data['typeConvClb'], $prop_val);
					}
				}
				else if( $prop_data['innerType'] == 'wc_meta' ){
					
					$prop_val	= call_user_func(array($product, $prop_data['dataGetterClb']));
					$prop_type	= gettype($prop_val);
					
					if( $prop_val === 'parent'){
						$parent_product = wc_get_product($product->get_parent_id());
						$prop_val		= call_user_func(array($parent_product, $prop_data['dataGetterClb']));
						$log_product	= $parent_product;
					}
					
					if( in_array($prop_type, $prop_data['canBeOfType'] )){

						if( empty($prop_val) && $prop_type != 'boolean') {
							$prop_val = null;
						}
						else{
							$prop_val = call_user_func($prop_data['typeConvClb'], $prop_val);
						}
						$return['properties'][ $prop_name ] = $prop_val;
					}
					else{
						$return['rejected'][ $prop_name ] = 'Method ' . get_class($log_product) . '->' . $prop_data["dataGetterClb"] . '()' . ' returned invalid data type. Returned type "' . $prop_type . '" is not among of valid types - [' . implode(' | ', $prop_data['canBeOfType']) . '] for product ID=' . $log_product->get_id();
					}
					
					$return['properties'][ $prop_name ] = $prop_val;
				}
				else if( $prop_data['innerType'] == 'taxonomy' || $prop_data['innerType'] == 'attribute'){
					
					$prop_val	= call_user_func( $prop_data['dataGetterClb'], $product->get_id(), $prop_data['taxonomy'], $prop_data['args']);
					
					if( $product->is_type( 'variation' ) ){
						
						/* extract ID from attr */
						preg_match('/wcAtt_ID(\d+)_/', $prop_name, $matches, null, 0);
						if( isset($matches[1]) ){
							
							$ID = $matches[1];
							$attr_taxes = wc_get_attribute_taxonomies();
							
							/* find attr tax by ID */
							foreach($attr_taxes as $attr_tax){
								if( $attr_tax->attribute_id == $ID ){
									$attrID_slug = $attr_tax->attribute_name;
									break;
								}
							}
							/* find attr term by slug */
							if( !empty($attrID_slug) ){
								$wc_product_attr = $product->get_attributes();
								if( isset($wc_product_attr[ 'pa_'.$attrID_slug ]) ){
									$wp_term = get_term_by( 'slug', $wc_product_attr[ 'pa_'.$attrID_slug ], 'pa_'.$attrID_slug );
									
									/* Variation has ANY value of attr */
									if(!$wp_term){
										$prop_val = get_terms(array('taxonomy' => 'pa_'.$attrID_slug, 'fields' => 'ids'));
									}
									else{
										$prop_val = array($wp_term->term_id);
									}
								}
							}
						}
						else if( $prop_name == 'wcProductAtt' ){
							
							$prop_val		 = array();
							$wc_product_atts = $product->get_attributes();
							$attr_taxes		 = wc_get_attribute_taxonomies();
						
							foreach($attr_taxes as $attr_tax){								
								
								if( isset($wc_product_atts[ 'pa_'.$attr_tax->attribute_name ]) ){
									$wp_term = get_term_by( 'slug', $wc_product_atts[ 'pa_'.$attr_tax->attribute_name ], 'pa_'.$attr_tax->attribute_name );
									
									/* Variation has ANY value of attr */
									if(!$wp_term){
										$prop_val = array_merge($prop_val, get_terms(array('taxonomy' => 'pa_'.$attr_tax->attribute_name, 'fields' => 'ids')) );
									}
									else{
										$prop_val[] = $wp_term->term_id;
									}
								}
							}
						}
					}
					
					$prop_type	= gettype($prop_val);
					
					if( in_array($prop_type, $prop_data['canBeOfType'] ) && !is_wp_error($prop_val) ){
						$prop_val = call_user_func($prop_data['typeConvClb'], array_map('strval', $prop_val));
						$return['properties'][ $prop_name ] = $prop_val;
					}
					else if( is_wp_error($prop_val) ){												
												
						$return['rejected'][ $prop_name ] = 'Function ' . $prop_data["dataGetterClb"] . '()' . ' returned data of type WP_Error for product ID=' . $product->get_id() . '.' .  ' Message was: ' . implode(', ', $prop_val->get_error_messages());
					}
					else{
						$return['rejected'][ $prop_name ] = 'Function ' . $prop_data["dataGetterClb"] . '()' . ' returned invalid data type. Returned type "' . $prop_type . '" is not among of valid types - [' . implode(' | ', $prop_data['canBeOfType']) . '] for product ID=' . $product->get_id();
					}
				}
			}
		}
		
		return $return;
	}
	
	public function getCustomerPropertiesValue($customer_id){
		
		$return = array();
		
		$blog_setting	=  $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		$all_props		=  $this->recombee->get_customer_sync_prop_all();
		
		$customer = new WC_Customer($customer_id);
		$return['id'] = $customer->get_id();
		
		foreach($all_props as $prop_name => $prop_data){
			
			if($prop_data['builtin'] || in_array($prop_name, $blog_setting['db_customer_prop_set'])){
				if( $prop_data['innerType'] == 'wp_meta' ){
					
					if( $prop_name == 'wpStatus' ){
						
						$prop_val = call_user_func($prop_data['dataGetterClb'], $customer->get_id() );
						$return['properties'][ $prop_name ] = $prop_val->get('user_registered');
					}
					else{
						$return['properties'][ $prop_name ] = call_user_func($prop_data['typeConvClb'], $prop_val);
					}
				}
				else if( $prop_data['innerType'] == 'wc_meta' ){
					
					$prop_val	= call_user_func(array($customer, $prop_data['dataGetterClb']));
					$prop_type	= gettype($prop_val);
					
					if( in_array($prop_type, $prop_data['canBeOfType'] )){
						if( empty($prop_val) && $prop_type != 'boolean') {
							$prop_val = null;
						}
						else{
							$prop_val = call_user_func($prop_data['typeConvClb'], $prop_val);
						}
						$return['properties'][ $prop_name ] = $prop_val;
					}
					else{
						$return['rejected'][ $prop_name ] = 'Method ' . get_class($customer) . '->' . $prop_data["dataGetterClb"] . '()' . ' returned invalid data type. Returned type "' . $prop_type . '" is not among of valid types - [' . implode(' | ', $prop_data['canBeOfType']) . '] for customer ID=' . $customer->get_id();
					}
					
					$return['properties'][ $prop_name ] = $prop_val;
				}
			}
		}
		
		return $return;
	}

	private function getGuestPropertiesValue($guest_data){
		
		$return = array();
		
		$blog_setting	=  $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		$all_props		=  $this->recombee->get_customer_sync_prop_all();
		
		( isset($guest_data['billing_email_hash']) ) ? $return['id'] = $guest_data['billing_email_hash'] : $return['id'] = $guest_data['comment_author_email_hash'];
		
		foreach($all_props as $prop_name => $prop_data){
			
			if($prop_data['builtin'] || in_array($prop_name, $blog_setting['db_customer_prop_set'])){
				
				$prop_val	= $prop_data['dataInitGuest'];
				$prop_type	= gettype($prop_val);
				
				if( in_array($prop_type, $prop_data['canBeOfType'] )){
					if( empty($prop_val) && $prop_type != 'boolean') {
						$prop_val = null;
					}
					else{
						$prop_val = call_user_func($prop_data['typeConvClb'], $prop_val);
					}
					$return['properties'][ $prop_name ] = $prop_val;
				}
				else{
					$return['rejected'][ $prop_name ] = 'Data type for guest customer is invalid. Returned type "' . $prop_type . '" is not among of valid types - [' . implode(' | ', $prop_data['canBeOfType']) . '] for user EMAIL=' . $guest_data['billing_email'];
				}
				
				$return['properties'][ $prop_name ] = $prop_data['dataInitGuest'];
			}
		}
		
		return $return;
	}
	
	/* LOGIN REDIRECT HANDLER */
	public function customer_logged_in_query_var_success($location){
		remove_filter( 'wp_redirect', array($this, 'customer_logged_in_query_var_success') );
		return add_query_arg( array( md5('customer_logged_in_success') => true ), $location );
	}	
	
	/* ADMIN NOTICE HELPER */
	public function customer_update_query_var_err($location){
		remove_filter( 'wp_redirect', array($this, 'customer_update_query_var_err') );
		return add_query_arg( array( 'customer_update_err' => true ), $location );
	}
	
	public function product_update_query_var_err($location){
		remove_filter( 'wp_redirect', array($this, 'product_update_query_var_err') );
		return add_query_arg( array( 'product_update_err' => true ), $location );
	}
	
	public function new_taxonomy_add_query_var_err($location){
		remove_filter( 'wp_redirect', array($this, 'add_new_taxonomy_err') );
		return add_query_arg( array( 'add_new_taxonomy_err' => true ), $location );
	}

	public function getting_prop_query_var_err($location){
		remove_filter( 'wp_redirect', array($this, 'add_new_taxonomy_err') );
		return add_query_arg( array( 'get_recombee_prop_err' => true ), $location );
	}
	
	public function beforeBlogSettingSave($new_preset){
		
		$old_preset	= $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		
		/* DB CONNECTION */
		$new_preset = $this->checkDbCredentials($new_preset, $old_preset);
		
		/* BUILTIN PRODUCT PROPERTIES */
		$new_preset = $this->checkBuiltinProdProps($new_preset, $old_preset);
		
		/* BUILTIN CUSTOMER PROPERTIES */
		$new_preset = $this->checkBuiltinCustProps($new_preset, $old_preset);
		
		/* RELATED OVERRIDE TAGS */
		$new_preset = $this->checkRelatedTagsValue($new_preset, $old_preset);
		
		return $new_preset;
	}
	
	private function checkDbCredentials($new_preset, $old_preset){
		
		$this->recombee->communicator->destroy();
		$this->recombee->communicator = RecombeeReCommunicator::instance($new_preset['api_identifier'], $new_preset['api_secret_token']);
		
		$response = $this->recombee->communicator->reqsListItems(array('operation_name' => 'Check db connect on save settings', 'count' => 1));
			
		if( isset($response['success']) ){
			$new_preset['db_connection_code'] = RRE_DB_CONNECTED_CODE;
		}
		else{
			$new_preset['db_connection_code'] = RRE_DB_DISCONNECTED_CODE;
		}
		$old_preset['invite_init_sync'] = false;
		
		return wp_parse_args( $new_preset, $old_preset );
	}
	
	private function checkBuiltinProdProps($new_preset, $old_preset){
		
		if( $new_preset['db_product_prop_set'] == -1 ){
			$new_preset['db_product_prop_set'] = array();
		}
		
		$order = $this->recombee->get_product_sync_prop_keys();
		$builtin_prod_props = $this->recombee->get_product_sync_prop_keys(true);
		$add_prod_props = array_diff( $builtin_prod_props, $new_preset['db_product_prop_set']);
		
		if( count($add_prod_props) > 0){
			$new_preset['db_product_prop_set'] = array_merge($add_prod_props, $new_preset['db_product_prop_set']);
		}

		usort($new_preset['db_product_prop_set'], function($a, $b) use ($order){
			// sort using the numeric index of the second array
			$valA = array_search($a, $order);
			$valB = array_search($b, $order);

			// move items that don't match to end
			if ($valA === false)
				return -1;
			if ($valB === false)
				return 0;

			if ($valA > $valB)
				return 1;
			if ($valA < $valB)
				return -1;
			return 0;
		});
		
		return wp_parse_args( $new_preset, $old_preset );
	}
	
	private function checkBuiltinCustProps($new_preset, $old_preset){
		
		if( $new_preset['db_customer_prop_set'] == -1 ){
			$new_preset['db_customer_prop_set'] = array();
		}
		
		$order = $this->recombee->get_customer_sync_prop_keys();
		$builtin_prod_props = $this->recombee->get_customer_sync_prop_keys(true);
		$add_prod_props = array_diff( $builtin_prod_props, $new_preset['db_customer_prop_set']);
		
		if( count($add_prod_props) > 0){
			$new_preset['db_customer_prop_set'] = array_merge($add_prod_props, $new_preset['db_customer_prop_set']);
		}

		usort($new_preset['db_customer_prop_set'], function($a, $b) use ($order){
			// sort using the numeric index of the second array
			$valA = array_search($a, $order);
			$valB = array_search($b, $order);

			// move items that don't match to end
			if ($valA === false)
				return -1;
			if ($valB === false)
				return 0;

			if ($valA > $valB)
				return 1;
			if ($valA < $valB)
				return -1;
			return 0;
		});
		
		return wp_parse_args( $new_preset, $old_preset );
	}
	
	private function checkRelatedTagsValue($new_preset, $old_preset){

		if( empty($new_preset['wc_override_related_tags']) ){
			
			$defaults = $this->recombee->get_default_settings('blog');
			$new_preset['wc_override_related_tags'] = $defaults['wc_override_related_tags'];
		}
		return wp_parse_args( $new_preset, $old_preset ); 
	}
	
	/**
	* Send AJAX respose succes or error with extra Data,
	* Header Status Code and opearation Log.
	*
	* @param string $type | nonce_true | nonce_false
	* @param array $data
	* @param array $log('success'->array, 'error'->array)
	* @return array
	*/
	private function ajaxAnswer($type, $data, $log = false){
		
		$return = (object)[];
		$return->message = (object)[];
		$return->message->data = $data;
		$return->message->recombee = $log;
		
		if( !empty( RecombeeReAjaxErrors::$ajaxErrors ) ){
			$return->message->errors = RecombeeReAjaxErrors::$ajaxErrors;
		}
		if($type == 'nonce_true'){
			wp_send_json_success( $return );
		}
		if($type == 'nonce_false'){
			wp_send_json_error( $return );
		}
	}
	
	private function maybeUpdateSyncOffset($syncType, $add_to_offset){
	
		switch ($syncType){
		
			case RRE_SYNC_WC_PRODUCTS_PRESET_NAME:
			
				$current_setting = $this->recombee->get_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME);
				
				if($current_setting['is_on_sync'] == 0){
					$this->recombee->set_blog_setting(RRE_SYNC_WC_PRODUCTS_PRESET_NAME, array('current_sync_offset' => $current_setting['current_sync_offset'] + 1 ));
				}
				break;
					
			case RRE_SYNC_WC_CUSTOMERS_PRESET_NAME:
			
				$current_setting = $this->recombee->get_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME);
				
				if($current_setting['is_on_sync'] == 0){
					$this->recombee->set_blog_setting(RRE_SYNC_WC_CUSTOMERS_PRESET_NAME, array('current_sync_offset' => $current_setting['current_sync_offset'] + $add_to_offset ));
				}
				break;
			
			case RRE_SYNC_WC_INTERACTIONS_PRESET_NAME	:			

				$current_setting = $this->recombee->get_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME);
				
				if($current_setting['is_on_sync'] == 0){

					$this->recombee->set_blog_setting(RRE_SYNC_WC_INTERACTIONS_PRESET_NAME, array('current_sync_offset' => $current_setting['current_sync_offset'] + $add_to_offset ));
				}
				break;
			
			case 'rating':

				break;
			
		}
	}
}