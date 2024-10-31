<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RecombeeReRecommsWidget extends WP_Widget{
	
	private $recombee;
	
	private $defaults;
	private $props;
	private $reql_funcs		 		= array();
	private $initBlogSetting 		= null;
	private static $do_ajax_widgets = null;
	private static $script_enqueued	= false;
	
	function __construct(){		
        parent::__construct(
            'recombee_recommends_widget',																																			/*	Base ID	*/
           '&#x1f536; ' . esc_html__( 'Recombee', 'recombee-recommendation-engine' ) . ' &raquo; ' . esc_html__('Personalized recommendations', 'recombee-recommendation-engine'),	/*	Name	*/
		   /* Args */
            array(
				'description'	=> esc_html__('Widget outputs recommended products based on widget parameters.', 'recombee-recommendation-engine'),
				'classname'		=> 'recombee-widget recombee-recommends-widget',
				)
        );
		
		$this->recombee	= RecombeeRe::instance();
		$this->defaults	= $this->recombee->recommsDefault;
		
		if(is_admin()){
			
			add_filter( 'emoji_svg_url',	array($this, 'emojiSvgUrl'));
			add_action( 'admin_head',		array($this, 'queryBuider'	),	10);
			add_action( 'load-widgets.php',	array($this, 'widget_screen'),	30);
		}
		else{
		
			add_action( 'wp_head',			array($this, 'register'		),	10);
			add_action( 'wp_footer',		array($this, 'footer'		),	10);
			add_filter( 'body_class',		array($this, 'bodyClasses'	),	10, 2);
		}
		
		/* AJAXedWIDGETS */
		add_action( 'rre_ajax_nopriv_RecombeeDoAjaxWidgets',	array($this, 'prepareRecommendsBatchy'));
		add_action( 'rre_ajax_RecombeeDoAjaxWidgets',			array($this, 'prepareRecommendsBatchy'));
		
		/* WIDGET settings */
		add_action( 'rre_ajax_RecombeeSearchObjects',			array($this, 'RecombeeSearchObjects'));

	}
	
	public function bodyClasses( $classes, $class ){
	
		$classes[] = 'recombee-scope';
		return $classes;
	}
	
	public function emojiSvgUrl( $url ){
		return RRE_PLUGIN_URL . '/includes/assets/css/images/';
	}
	
	public function queryBuider(){
		
		?>
			<div style="display: none;" id="recombee-query-builder-wrapper">
				<div id="recombee-query-builder"></div>
			</div>
		<?php
		
		wp_localize_script( 'widgets_admin_js', 'RecombeeReQBArgs', $this->props );
	}
	
	public function register(){
		
		/* it will register scripts and styles on every page of site */
	}
	
	private function doPrevent($instance){
		
		$prevented	= false;
		$reason		= null;
		$logic		= $instance['suppressLogic'];
		$subject	= $instance['suppressSubject'];
		$posts		= $instance['suppressPosts'];
		$post		= $this->recombee->realPost;
		
		if( empty($post) ){
			
			return array('status' => false);
		}
		
		if($logic != 'off'){
			if($subject == 'posts'){
				if($logic == 'exclude'){
					
					if( in_array($post->ID, $posts) ){
						
						$prevented = true;
						if( $this->initBlogSetting['debug_mode'] == 1 ){
							$reason .= sprintf( __( 'Recombee prevented due to rule: current post ID "%1$s" was found in processed posts IDs: "%2$s", while the logic is "%3$s" and processed objects types was "%4$s". ', 'recombee-recommendation-engine' ), $post->post_title . ' (ID=' . $post->ID . ')', implode(', ', $posts), $logic, $subject);
						}
					}
				}
				else if($logic == 'include'){
					
					if( !in_array($post->ID, $posts) ){
						
						$prevented = true;
						if( $this->initBlogSetting['debug_mode'] == 1 ){
							$reason .= sprintf( __( 'Recombee prevented due to rule: current post ID "%1$s" was not found in processed posts IDs: "%2$s", while the logic is "%3$s" and processed objects types was "%4$s". ', 'recombee-recommendation-engine' ), $post->post_title . ' (ID=' . $post->ID . ')', implode(', ', $posts), $logic, $subject);
						}
					}
				}
				else{
					$prevented = true;
					if( $this->initBlogSetting['debug_mode'] == 1 ){
						$reason .= sprintf( __( 'Recombee prevented due to undefined rule logic: current post was "%1$s", processed posts IDs was: "%2$s", the undefined logic was "%3$s" and processed objects types was "%4$s". ', 'recombee-recommendation-engine' ), $post->post_title . ' (ID=' . $post->ID . ')', implode(', ', $posts), $logic, $subject);
					}
				}
			}
			else if($subject == 'terms'){
				
				global $wpdb;
				
				$sql_in = $posts;
				array_walk($sql_in, function(&$x) {$x = "'$x'";});
				$sql_in	= implode(',', $sql_in);
					
				if($logic == 'exclude'){
					
					$post_terms = $wpdb->get_results("SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships 
														WHERE term_taxonomy_id IN ($sql_in) AND object_id = $post->ID 
															ORDER BY term_taxonomy_id ASC", ARRAY_A);
					if( count($post_terms) > 0 ){
						
						$prevented = true;
						if( $this->initBlogSetting['debug_mode'] == 1 ){
							$reason .= sprintf( __( 'Recombee prevented due to rule: current post "%1$s" belongs to one or more of processed terms IDs: "%2$s", while the logic was "%3$s" and processed objects types was "%4$s". ', 'recombee-recommendation-engine' ), $post->post_title . ' (ID=' . $post->ID . ')', implode(', ', $posts), $logic, $subject);
						}
					}
				}
				else if($logic == 'include'){
					
					$post_terms = $wpdb->get_results("SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships 
														WHERE term_taxonomy_id IN ($sql_in) AND object_id = $post->ID 
															ORDER BY term_taxonomy_id ASC", ARRAY_A);
					if( count($post_terms) === 0 ){
						
						$prevented = true;
						if( $this->initBlogSetting['debug_mode'] == 1 ){
							$reason .= sprintf( __( 'Recombee prevented due to rule: current post "%1$s" does not belongs to one or more of processed terms IDs: "%2$s", while the logic was "%3$s" and processed objects types was "%4$s". ', 'recombee-recommendation-engine' ), $post->post_title . ' (ID=' . $post->ID . ')', implode(', ', $posts), $logic, $subject);
						}
					}
				}
				else{
					$prevented = true;
					if( $this->initBlogSetting['debug_mode'] == 1 ){
						$reason .= sprintf( __( 'Recombee prevented due to undefined rule logic: current post was "%1$s", processed terms IDs was: "%2$s", the undefined logic was "%3$s" and processed objects types was "%4$s". ', 'recombee-recommendation-engine' ), $post->post_title . ' (ID=' . $post->ID . ')', implode(', ', $posts), $logic, $subject);
					}
				}
			}
			else{
				$prevented = true;
				if( $this->initBlogSetting['debug_mode'] == 1 ){
					$reason .= sprintf( __( 'Recombee prevented due to undefined processed objects types: current post was "%1$s", processed terms IDs was: "%2$s", the logic was "%3$s" and the undefined processed objects types was "%4$s". ', 'recombee-recommendation-engine' ), $post->post_title . ' (ID=' . $post->ID . ')', implode(', ', $posts), $logic, $subject);
				}
			}
		}
		
		if( $prevented ){
			return array(
				'status' => true,
				'reason' => $reason,
			);
		}
		else{
			return array(
				'status' => false,
			);
		}
	}
	
	public function widget( $args, $instance ){
		
		$instance_user		 = $instance;
		$instance['filter']	 = $instance['filter_reql'];
		$instance['booster'] = $instance['booster_reql'];
		
		$this->initBlogSetting = $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		$prevent			   = $this->doPrevent($instance);
		
		/* STYLES */	
		wp_enqueue_style ( 'widgets_front_css' );
		
		/* SCRIPTS */
		wp_enqueue_script ( 'widgets_front_js' ); /* scripts goes only in footer - $do_ajax_widgets will be filled inside body (it will be empty during head) */
		
		$instance = wp_parse_args(
            (array)$instance,
            $this->defaults
		);
		
		$instance = $this->recombee->adjust_recommendations_columns($instance, $instance_user);
		
		$instance['_stuff_data'] = array(
			'is_product'	=> is_product(),
			'post_id'		=> $this->recombee->realPost->ID, //get_the_ID(),
			'prevented'		=> array(
				'status'	=> false,
			),
		);
		
		if($prevent['status']){
			$instance['_stuff_data']['prevented'] = $prevent;
		}
		
		$instance = $this->prepareFilterExpr( $instance );
		$this->prepareRecommendsSequentally($args, $instance);
	}
	
	/* REGULAR WIDGETS */
	public function prepareRecommendsSequentally($args, $instance){
		
		$callback_func	= null;
		$warning		= array();
		$return			= false;
		
		if( count($instance['_illegal_params']) > 0 ){
			
			$text = __('Detected illegal parameter(s) in widget: ', 'recombee-recommendation-engine' );
			
			foreach($instance['_illegal_params'] as $illegal_key => $illegal_value){
				$text .= $illegal_key . ' -> ' . $illegal_value . ', ';
			}
			$text = trim($text, ', ');
			$warning[] = $text;
		}
		
		if( strpos($instance['scenario'], '##PostType##') !== false ){
			
			$post_type = get_post_type( $this->recombee->realPost->ID /* get_the_ID() */ );
			
			if(!$post_type){
				$post_type = 'UnknownPostType';
			}
			
			$instance['scenario'] = preg_replace("/##(PostType)##/", $post_type, $instance['scenario'], -1);
		}
		
		if( $instance['_stuff_data']['prevented']['status'] ){
			
			$warning[] = $instance['_stuff_data']['prevented']['reason'];
		}
		else if( $this->initBlogSetting['db_connection_code'] == RRE_DB_DISCONNECTED_CODE){
			
			$warning[] = __( 'You have disconnected from recombee database, receiving recommendations is impossible.', 'recombee-recommendation-engine' );
			$return = true;
		}
		else{
			
			switch($instance['type']){
				
				case 'ProductsToCustomer':
				
					$callback_func = array($this->recombee->communicator, 'reqsRecommendItemsToUser');
					$callback_args = array($instance, array(
						'operation_name' => 'get recommends Products To Customer'
					));
					
					break;
					
				case 'ProductsToProduct':
					
					if( $instance['_stuff_data']['is_product'] ){
						
						$callback_func = array($this->recombee->communicator, 'reqsRecommendItemsToItem');
						$callback_args = array($instance, array(
							'item_id'		 => $instance['_stuff_data']['post_id'],
							'operation_name' => 'get recommends Products To Product',
						));
					}
					else{
						
						$warning[] = __( 'Widget with setting "Products to Product" can be used only at a product page.', 'recombee-recommendation-engine' );
						$return = true;
					}
						
					break;
					
				case 'CustomersToCustomer':
				
					$callback_func = array($this->recombee->communicator, 'reqsRecommendUsersToUser');
					$callback_args = array($instance, array(
							'operation_name' => 'get recommends Customers To Customer'
					));
					
					break;
				
				case 'CustomersToProduct':
				
					if( $instance['_stuff_data']['is_product'] ){
						
						$callback_func = array($this->recombee->communicator, 'reqsRecommendUsersToItem');
						$callback_args = array($instance, array(
							'item_id'		 => $instance['_stuff_data']['post_id'],
							'operation_name' => 'get recommends Customers To Product',
						));
					}
					else{
						
						$warning[] = __( 'Widget with setting "Customers to Product" has nothing to output anywhere, except product page.', 'recombee-recommendation-engine' );
						$return = true;
					}
					break;
				
				default:
				
					$warning[] = sprintf(__( 'Wrong widget type setting: "%s".', 'recombee-recommendation-engine' ), $instance['type']);
					$return = true;
					
			}
		}
		
		if( count($warning) > 0 && $this->initBlogSetting['debug_mode'] == 1 ){
			$title = sprintf(__('Widget %s says:', 'recombee-recommendation-engine' ), $instance['wTitle'] );
			$this->displayWarning($title, $warning);
		}
		
		if( $return ){
			return;
		}
		
		if($instance['ajaxMode'] == 'on' && !$instance['_stuff_data']['prevented']['status']){
			
			$uniqid = uniqid();
			self::$do_ajax_widgets[] = array(
				'uniqid'	=> $uniqid,
				'args'		=> $args,
				'instance'	=> $instance,
			);

			echo '<div class="recombeeRe-ajaxed-widget" data-rawids="' . $uniqid . '"><div class="recombee-spinner"></div></div>';
			
			return;
		}
		else if(!empty($callback_func)){
			
			$recommends = call_user_func_array($callback_func, $callback_args);
			$this->displayRecommends($args, $instance, $recommends);
		}
	}
	
	/* AJAX WIDGETS */
	public function prepareRecommendsBatchy(){
		
		$this->initBlogSetting = $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		
		if ( check_ajax_referer( 'RecombeeDoAjaxWidgets', 'nonce', false) ){
			
			$warning				= '';
			$widgets_html			= array();
			$batch_stack			= array();
			$widgets_args			= array(); 
			$do_widgets				= wp_unslash(unserialize(base64_decode(sanitize_text_field( $_POST['data']['do_widgets'] ))));
			
			foreach($do_widgets as $index => $do_widget){
				
				if( count($do_widget['instance']) > 0 ){
					
					switch($do_widget['instance']['type']){
						
						case 'ProductsToCustomer':
							
							$batch_stack[] = array(
								'method'		=> 'reqsRecommendItemsToUser',
								'properties'	=> $do_widget['instance'],
								'param'			=> array(
									'operation_name'	=> 'get recommends Products To User'
								)
							);
							break;
							
						case 'ProductsToProduct':
							
							$batch_stack[] = array(
								'method'		=> 'reqsRecommendItemsToItem',
								'properties'	=> $do_widget['instance'],
								'param'			=> array(
									'item_id'			=> $do_widget['instance']['_stuff_data']['post_id'],
									'operation_name'	=> 'get recommends Products To Product'
								)
							);
							break;
							
						case 'CustomersToCustomer':
						
							$batch_stack[] = array(
								'method'		=> 'reqsRecommendUsersToUser',
								'properties'	=> $do_widget['instance'],
								'param'			=> array(
									'operation_name'	=> 'get recommends Users To User'
								)
							);						
							break;
						
						case 'CustomersToProduct':
					
							$batch_stack[] = array(
								'method'		=> 'reqsRecommendUsersToItem',
								'properties'	=> $do_widget['instance'],
								'param'			=> array(
									'item_id'			=> $do_widget['instance']['_stuff_data']['post_id'],
									'operation_name'	=> 'get recommends Users To Item'
								)
							);
							break;
					}
				}
				else{
					
					if( $this->initBlogSetting['debug_mode'] == 1 ){
						$message = __('Temporary data for building content is empty. There is nothing to output.', 'recombee-recommendation-engine' );
						$title = sprintf(__('Widget %s says:', 'recombee-recommendation-engine' ), $do_widget['instance']['wTitle'] );
						$warning = $this->displayWarning($title, array($message), false);
					}
					
					$widgets_html[ $do_widget['uniqid'] ] = array(
						'html' => $warning,
					);
				}
			}
				
			$recomms = $this->recombee->communicator->reqsExecuteBatch('Get united recomms on ajax widgets', $batch_stack, false, true);
			
			if($recomms['reqsExecuteBatch'] instanceof Exception){
				foreach($do_widgets as $do_widget){
					
					if( $this->initBlogSetting['debug_mode'] == 1 ){
						$message = $this->recombee->communicator->formalizeException($recomms['reqsExecuteBatch']);
						$title = sprintf(__('Widget %s says:', 'recombee-recommendation-engine' ), $do_widget['instance']['wTitle'] );
						$warning = $this->displayWarning($title, array($message), false);
					}
					
					$widgets_html[ $do_widget['uniqid'] ] = array(
						'html' => $warning,
					);
				}
			}
			else{
				
				foreach($recomms['reqsExecuteBatch'] as $recomm_index => $recomm_data){
					
					if((int)$recomm_data['code'] !== 200 && (int)$recomm_data['code'] !== 201){
						
						if(is_array($recomm_data['json'])){
							$display_recomms = array('errors' => $recomm_data['json']['error']);
						}
						else{
							$text		= '';
							$is_json	= json_decode($recomm_data['json']);
							
							if(!is_null($is_json)){
								$message = $is_json;
								foreach($message as $key => $info){
									$text .= $key . ': ' . $info . ' -> ';
								}
								trim($text, '-> ');
								$display_recomms = array('errors' => $text);
							}
							else{
								$display_recomms = array('errors' => $text);
							}
						}
					}
					else{
						
						$display_recomms = array(
							'success' => array(
								'recomms' => $recomm_data['json']['recomms']
							)
						);					
					}
					
					$corresponding_widget_id = $do_widgets[$recomm_index];
					
					ob_start();
					
					$this->displayRecommends( $do_widgets[$recomm_index]['args'], $do_widgets[$recomm_index]['instance'], $display_recomms);
				
					$widgets_html[ $do_widgets[$recomm_index]['uniqid'] ] = array(
						'html' => ob_get_clean(),
					);
					
				}
			}
			
			$response = array(
				'statusCode'	=> 200,
				'widgets'		=> $widgets_html
			);
			wp_send_json_success($response);
		}
		else{
			$response = array(
				'statusCode'	=> 400,
				'message'		=> ( $this->initBlogSetting['debug_mode'] == 1 ) ? __( 'Access violation for this page. Reload it & repeat.', 'recombee-recommendation-engine' ) : '',
			);
			wp_send_json_error($response);
		}
	}
		
	public function RecombeeSearchObjects(){
		
		if ( check_ajax_referer( 'RecombeeWidgetSetting', 'nonce', false) ){
			
			$search = sanitize_text_field($_POST['search']);
			
			if( $_POST['suppressSubject'] == 'false' ){
				
				$response = array(
					'statusCode'	=> 200,
					'items'			=> $this->searchPosts($search)
				);
			}
			else if( $_POST['suppressSubject'] == 'true' ){
				
				$response = array(
					'statusCode'	=> 200,
					'items'			=> $this->searchTerms($search)
				);
			}
			
			wp_send_json_success($response);
		}
		else{
			
			$response = array(
				'statusCode'	=> 400,
			);
			wp_send_json_error($response);
		}
	}
	
	private function searchPosts($search_this){
		
		global $wpdb;
		
		$html = array();
		$post_type_results	= array();
		$public_post_types	= get_post_types(array(
			'public' => 1,
		));
		array_walk($public_post_types, function(&$x) {$x = "'$x'";});
		$public_post_types	= implode(',', $public_post_types);
		
		$results = $wpdb->get_results("SELECT ID, post_type, post_title FROM $wpdb->posts 
										WHERE post_type IN ($public_post_types) AND (ID LIKE '%$search_this%' OR post_title LIKE '%$search_this%' OR post_name LIKE '%$search_this%') 
											ORDER BY post_type ASC", ARRAY_A);
		
		/* Group by posts */
		foreach($results as $result){
			
			$post_type_results[ $result['post_type'] ][] = $result;
		}
		foreach($post_type_results as $post_type => $post_type_posts){
			
				$children		 = array();
				$post_type_label = get_post_type_object($post_type)->label;
				
				foreach($post_type_posts as $post_type_post){
					
					$children[] = array(
						'id'	=> $post_type_post['ID'],
						'text'	=> $post_type_post['post_title'],
					);
				}
				
			$html[] = array(
				'text'		=> $post_type_label,
				'children'	=> $children,
			);
		}
		
		return $html;
	}
	
	private function searchTerms($search_this){
		
		global $wpdb;
		
		$html = array();
		$taxonomy_results	= array();
		
		$results = $wpdb->get_results("SELECT term_taxonomy.term_taxonomy_id, terms.term_id, terms.name, term_taxonomy.count, term_taxonomy.taxonomy FROM $wpdb->terms as terms 
										INNER JOIN $wpdb->term_taxonomy as term_taxonomy ON terms.term_id = term_taxonomy.term_id 
											WHERE term_taxonomy.term_taxonomy_id LIKE '%$search_this%' OR terms.name LIKE '%$search_this%' OR terms.slug LIKE '%$search_this%'  
												ORDER BY terms.name ASC", ARRAY_A);
		/* Group by terms */
		foreach($results as $result){
			
			$taxonomy = get_taxonomy($result['taxonomy']);
			
			if( $taxonomy && $taxonomy->public){
				$taxonomy_results[ $result['taxonomy'] ][] = $result;
			}
		}
		foreach($taxonomy_results as $taxonomy => $taxonomy_terms){
			
				$children		 = array();
				$taxonomy_label = get_taxonomy($taxonomy)->label;
				
				foreach($taxonomy_terms as $taxonomy_term){
					
					$children[] = array(
						'id'	=> $taxonomy_term['term_taxonomy_id'],
						'text'	=> $taxonomy_term['name'] . ' (' .  $taxonomy_term['count'] .')',
					);
				}
				
			$html[] = array(
				'text'		=> $taxonomy_label,
				'children'	=> $children,
			);
		}
		
		return $html;
	}
	
	public function form( $instance ){
		
		remove_filter( 'emoji_svg_url', array($this, 'emojiSvgUrl'));
		
		/* STYLES */	
		wp_enqueue_style ( 'widgets_admin_css' );	
		
		/* SCRIPTS */
		wp_enqueue_script( 'widgets_admin_js' );
		
		$instance = wp_parse_args(
            (array)$instance,
            $this->defaults
		);
		
		$parentsOnly			= $instance['parentsOnly'];
		$ajaxMode				= $instance['ajaxMode'];
		$followThemeCss			= $instance['followThemeCss'];
		$suppressLogic			= $instance['suppressLogic'];
		$suppressSubject		= $instance['suppressSubject'];
		$suppressPosts			= $instance['suppressPosts'];
		$wTitle					= $instance['wTitle'];
		$columns				= $instance['columns'];
		$type					= $instance['type'];
		$count					= $instance['count'];
		$scenario				= $instance['scenario'];
		$userImpact 			= $instance['userImpact'];
		$filterJson				= $instance['filter_json'];
		$filterReql				= $instance['filter_reql'];
		$boosterJson			= $instance['booster_json'];
		$boosterReql			= $instance['booster_reql'];		
		$boosterThen			= $instance['booster_then'];		
		$boosterElse			= $instance['booster_else'];		
		$diversity 				= $instance['diversity'];
		$minRelevance 			= $instance['minRelevance'];
		$rotationRate 			= $instance['rotationRate'];
		$rotationTime 			= $instance['rotationTime'];
		
		$recommsTypes = array(
			'ProductsToCustomer'=> array(
					'allowed'	=> true,
					'viewName'	=> __('Products to Customer', 'recombee-recommendation-engine'), //Recommend Products To Custormer					
					'params'	=> array('ajaxMode','parentsOnly','followThemeCss','suppressLogic','suppressSubject','suppressPosts','wTitle','columns','count','scenario','filter','booster','diversity','minRelevance','rotationRate','rotationTime'),
					'filters'	=> 'item_props',
			),
			'ProductsToProduct' => array(
					'allowed'	=> true,
					'viewName'	=> __('Products to Product', 'recombee-recommendation-engine'), //Recommend Products To Product
					'params'	=> array('ajaxMode','parentsOnly','followThemeCss','suppressLogic','suppressSubject','suppressPosts','wTitle','columns','count','scenario','userImpact','filter','booster','diversity','minRelevance','rotationRate','rotationTime'),
					'filters'	=> 'item_props'
			),
			'CustomersToCustomer'=> array(
					'allowed'	=> false,
					'viewName'	=> __('Customers To Customer', 'recombee-recommendation-engine'),
					'params'	=> array('ajaxMode','followThemeCss','wTitle','suppressLogic','suppressSubject','suppressPosts','columns','count','scenario','filter','booster','diversity','minRelevance','rotationRate','rotationTime'),
					'filters'	=> 'user_props'
			),
			'CustomersToProduct'=> array(
					'allowed'	=> false,
					'viewName'	=> __('Customers To Product', 'recombee-recommendation-engine'),
					'params'	=> array('ajaxMode','followThemeCss','wTitle','suppressLogic','suppressSubject','suppressPosts','columns','count','scenario','filter','booster','diversity'),
					'filters'	=> 'user_props'
			),
		);
		
		/* IDs */
		$parentsOnlyID			= $this->get_field_id('parentsOnly');
		$ajaxModeID				= $this->get_field_id('ajaxMode');
		$followThemeCssID		= $this->get_field_id('followThemeCss');
		$suppressLogicOffID		= $this->get_field_id('suppressLogicOff');
		$suppressLogicExcID		= $this->get_field_id('suppressLogicExc');
		$suppressLogicIncID		= $this->get_field_id('suppressLogicInc');
		$suppressSubjectID		= $this->get_field_id('suppressSubject');
		$suppressPostsID		= $this->get_field_id('suppressPosts');
		$wTitleID				= $this->get_field_id('wTitle');
		$columnsID				= $this->get_field_id('columns');
		$typeID					= $this->get_field_id('type');
		$countID				= $this->get_field_id('count');
		$scenarioID				= $this->get_field_id('scenario');
		$userImpactID			= $this->get_field_id('userImpact');
		$filterID				= $this->get_field_id('filter');
		$boosterID				= $this->get_field_id('booster');
		$diversityID			= $this->get_field_id('diversity');
		$minRelevanceID			= $this->get_field_id('minRelevance');
		$rotationRateID			= $this->get_field_id('rotationRate');
		$rotationTimeID			= $this->get_field_id('rotationTime');
		
		/* NAMES - '_stuff_data' & '_illegal_params' key reserved! */
		$parentsOnlyNAME		= $this->get_field_name('parentsOnly');
		$ajaxModeNAME			= $this->get_field_name('ajaxMode');
		$followThemeCssNAME		= $this->get_field_name('followThemeCss');
		$suppressLogicNAME		= $this->get_field_name('suppressLogic');
		$suppressSubjectNAME	= $this->get_field_name('suppressSubject');
		$suppressPostsNAME		= $this->get_field_name('suppressPosts[]');
		$wTitleNAME				= $this->get_field_name('wTitle');
		$columnsNAME			= $this->get_field_name('columns');
		$typeNAME				= $this->get_field_name('type');
		$countNAME				= $this->get_field_name('count');
		$scenarioNAME			= $this->get_field_name('scenario');
		$userImpactNAME			= $this->get_field_name('userImpact');
		$filterJsonNAME			= $this->get_field_name('filter_json');
		$filterReqlNAME			= $this->get_field_name('filter_reql');
		$boosterJsonNAME		= $this->get_field_name('booster_json');
		$boosterReqlNAME		= $this->get_field_name('booster_reql');
		$boosterThenNAME		= $this->get_field_name('booster_then');
		$boosterElseNAME		= $this->get_field_name('booster_else');
		$diversityNAME			= $this->get_field_name('diversity');
		$minRelevanceNAME		= $this->get_field_name('minRelevance');
		$rotationRateNAME		= $this->get_field_name('rotationRate');
		$rotationTimeNAME		= $this->get_field_name('rotationTime');
		
		/* LABELS */
		$parentsOnlyLabelVALUE		= __('Parent products only', 'recombee-recommendation-engine');
		$ajaxModeLabelVALUE			= __('Enable AJAX mode', 'recombee-recommendation-engine');
		$followThemeCssLabelVALUE	= __('Follow Theme CSS', 'recombee-recommendation-engine');
		$suppressLogicOffLabelVALUE	= __(' - off', 'recombee-recommendation-engine');
		$suppressLogicExcLabelVALUE	= __(' - everywhere, excluding objects', 'recombee-recommendation-engine');
		$suppressLogicIncLabelVALUE	= __(' - only at objects', 'recombee-recommendation-engine');
		$wTitleLabelVALUE			= __('Title', 'recombee-recommendation-engine');
		$columnsLabelVALUE			= __('Columns', 'recombee-recommendation-engine');
		$typeLabelVALUE				= __('Type', 'recombee-recommendation-engine');
		$countLabelVALUE			= __('Count', 'recombee-recommendation-engine');
		$scenarioLabelVALUE			= __('Scenario', 'recombee-recommendation-engine');
		$userImpactLabelVALUE		= __('User Impact', 'recombee-recommendation-engine');
		$filterLabelVALUE			= __('Filter (dbl click to unlock)', 'recombee-recommendation-engine');
		$boosterLabelVALUE			= __('Booster (<span class="booster_statement">if this expression is true...</span>)', 'recombee-recommendation-engine');
		$diversityLabelVALUE		= __('Diversity', 'recombee-recommendation-engine');
		$minRelevanceLabelVALUE		= __('Min Relevance', 'recombee-recommendation-engine');
		$rotationRateLabelVALUE		= __('Rotation Rate', 'recombee-recommendation-engine');
		$rotationTimeLabelVALUE		= __('Rotation Time', 'recombee-recommendation-engine');
		
		/* PLACEHOLDERS */
		$suppressPostsPlaceholder	= json_encode(array(
			'posts' => __('Post ID, slug or name... 2 characters min.', 'recombee-recommendation-engine'),
			'terms' => __('Term ID, slug or name... 2 characters min.', 'recombee-recommendation-engine')
		));
		$wtitlePlaceholder			= __('Any text. HTML allowed', 'recombee-recommendation-engine');
		$columnsPlaceholder			= __('Integer between 0 & 9 inclusive', 'recombee-recommendation-engine');
		$countPlaceholder			= __('Any number greater then 0', 'recombee-recommendation-engine');
		$scenarioPlaceholder		= __('Any text within [a-zA-Z0-9_\-#:]', 'recombee-recommendation-engine');
		$userImpactPlaceholder		= __('Number between 0 and 1 inclusive or empty string', 'recombee-recommendation-engine');
		$filterPlaceholder			= __('Any valid ReQL expression or empty string', 'recombee-recommendation-engine');
		$boosterPlaceholder			= __('Any valid ReQL expression or empty string', 'recombee-recommendation-engine');
		$diversityPlaceholder		= __('Number between 0 and 1 inclusive or empty string', 'recombee-recommendation-engine');
		$rotationRatePlaceholder	= __('Number between 0 and 1 inclusive or empty string', 'recombee-recommendation-engine');
		$rotationTimePlaceholder	= __('Number greater then 0 or empty string', 'recombee-recommendation-engine');
		
		/* DISABLED */
		$parentsOnlyDisabled		= disabled( false, in_array('parentsOnly',		$recommsTypes[$type]['params']), false );
		$ajaxModeDisabled			= disabled( false, in_array('ajaxMode',			$recommsTypes[$type]['params']), false );
		$followThemeCssDisabled		= disabled( false, in_array('followThemeCss',	$recommsTypes[$type]['params']), false );
		$suppressLogicOffDisabled	= disabled( false, in_array('suppressLogic',	$recommsTypes[$type]['params']), false );
		$suppressLogicExcDisabled	= disabled( false, in_array('suppressLogic',	$recommsTypes[$type]['params']), false );
		$suppressLogicIncDisabled	= disabled( false, in_array('suppressLogic',	$recommsTypes[$type]['params']), false );
		$suppressSubjectDisabled	= disabled( false, in_array('suppressSubject',	$recommsTypes[$type]['params']), false );
		$suppressPostsDisabled		= disabled( false, in_array('suppressPosts',	$recommsTypes[$type]['params']), false );
		$wTitleDisabled				= disabled( false, in_array('wTitle',			$recommsTypes[$type]['params']), false );
		$columnsDisabled			= disabled( false, in_array('columns',			$recommsTypes[$type]['params']), false );
		$countDisabled				= disabled( false, in_array('count',			$recommsTypes[$type]['params']), false );
		$scenarioDisabled			= disabled( false, in_array('scenario',			$recommsTypes[$type]['params']), false );
		$userImpactDisabled			= disabled( false, in_array('userImpact',		$recommsTypes[$type]['params']), false );
		$filterDisabled				= disabled( false, in_array('filter',			$recommsTypes[$type]['params']), false );
		$boosterDisabled			= disabled( false, in_array('booster',			$recommsTypes[$type]['params']), false );
		$diversityDisabled			= disabled( false, in_array('diversity',		$recommsTypes[$type]['params']), false );
		$minRelevanceDisabled		= disabled( false, in_array('minRelevance',		$recommsTypes[$type]['params']), false );
		$rotationRateDisabled		= disabled( false, in_array('rotationRate',		$recommsTypes[$type]['params']), false );
		$rotationTimeDisabled		= disabled( false, in_array('rotationTime',		$recommsTypes[$type]['params']), false );
		
		/* VISIBLED */
		$parentsOnlyDisplayed		= $this->displayed( 'parentsOnly',		$recommsTypes[ $type ] );
		$ajaxModeDisplayed			= $this->displayed( 'ajaxMode',			$recommsTypes[ $type ] );
		$followThemeCssDisplayed	= $this->displayed( 'followThemeCss',	$recommsTypes[ $type ] );
		$suppressLogicOffDisplayed	= $this->displayed( 'suppressLogic',	$recommsTypes[ $type ] );
		$suppressLogicExcDisplayed	= $this->displayed( 'suppressLogic',	$recommsTypes[ $type ] );
		$suppressLogicIncDisplayed	= $this->displayed( 'suppressLogic',	$recommsTypes[ $type ] );
		$suppressSubjectDisplayed	= $this->displayed( 'suppressSubject',	$recommsTypes[ $type ] );
		$suppressPostsDisplayed		= $this->displayed( 'suppressPosts',	$recommsTypes[ $type ] );
		$wTitleDisplayed			= $this->displayed( 'wTitle',			$recommsTypes[ $type ] );
		$columnsDisplayed			= $this->displayed( 'columns',			$recommsTypes[ $type ] );
		$countDisplayed				= $this->displayed( 'count',			$recommsTypes[ $type ] );
		$scenarioDisplayed			= $this->displayed( 'scenario',			$recommsTypes[ $type ] );
		$userImpactDisplayed		= $this->displayed( 'userImpact',		$recommsTypes[ $type ] );
		$filterDisplayed			= $this->displayed( 'filter',			$recommsTypes[ $type ] );
		$boosterDisplayed			= $this->displayed( 'booster',			$recommsTypes[ $type ] );
		$diversityDisplayed			= $this->displayed( 'diversity',		$recommsTypes[ $type ] );
		$minRelevanceDisplayed		= $this->displayed( 'minRelevance', 	$recommsTypes[ $type ] );
		$rotationRateDisplayed		= $this->displayed( 'rotationRate', 	$recommsTypes[ $type ] );
		$rotationTimeDisplayed		= $this->displayed( 'rotationTime', 	$recommsTypes[ $type ] );
		
		$current_db_setting = $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		
		if( $current_db_setting['db_connection_code'] === RRE_DB_DISCONNECTED_CODE ){
			?>
				<div class="recombee-db-warning"><?php echo sprintf( __('Your site is not connected to a Recombee Database yet. This widget will outputs nothing until it`s true. Setup connection at %s.', 'recombee-recommendation-engine'), '<a href=#">this menu page</a>' ) ?></div>
			<?php
		}
			
		echo '<p>' . $this->widget_options['description'] . ' ' . sprintf( esc_html__('For explanation and understanding each parameter click %s at this page.', 'recombee-recommendation-engine'), '<a class="recombee-help" style="white-space: nowrap;" href=#" onclick="return false;">Help Tab</a>' ) . '</p>';
		
		$ajax = array(
			'AJAX_url'	=> $this->recombee->rre_ajax_interface->get_virtual_page('ajax'),
			'nonce'		=> wp_create_nonce( 'RecombeeWidgetSetting' ),
			'data'		=> array(
				'AJAX_Marker'	=> RRE_PLUGIN_DIR,
			)
		);
		
		?>
		<div class="recombee-widget-parameters" data-ajax='<?php echo json_encode($ajax) ?>'>
			<p class="switch-slider" <?php echo $parentsOnlyDisplayed; ?>>
				<label for="<?php echo $parentsOnlyID; ?>" <?php echo $parentsOnlyDisabled; ?>><?php echo $parentsOnlyLabelVALUE ?></label>
				<input data-parameter-name="parentsOnly" class="widefat recombee-toggle" id="<?php echo $parentsOnlyID ?>" name="<?php echo $parentsOnlyNAME ?>" <?php checked( $parentsOnly, 'on' ); ?> type="checkbox" <?php echo $parentsOnlyDisabled; ?> />
			</p>
			<p class="switch-slider" <?php echo $ajaxModeDisplayed; ?>>
				<label for="<?php echo $ajaxModeID; ?>" <?php echo $ajaxModeDisabled; ?>><?php echo $ajaxModeLabelVALUE ?></label>
				<input data-parameter-name="ajaxMode" class="widefat recombee-toggle" id="<?php echo $ajaxModeID ?>" name="<?php echo $ajaxModeNAME ?>" <?php checked( $ajaxMode, 'on' ); ?> type="checkbox" <?php echo $ajaxModeDisabled; ?> />
			</p>
			<p class="switch-slider" <?php echo $followThemeCssDisplayed; ?>>
				<label for="<?php echo $followThemeCssID; ?>" <?php echo $followThemeCssDisabled; ?>><?php echo $followThemeCssLabelVALUE ?></label>
				<input data-parameter-name="followThemeCss" class="widefat recombee-toggle" id="<?php echo $followThemeCssID ?>" name="<?php echo $followThemeCssNAME ?>" <?php checked( $followThemeCss, 'on' ); ?> type="checkbox" <?php echo $followThemeCssDisabled; ?> />
			</p>
			<div class="divider">• • •</div>
			<p <?php echo $wTitleDisplayed; ?>>
				<label for="<?php echo $wTitleID; ?>"><?php echo $wTitleLabelVALUE ?></label>
				<input data-parameter-name="wTitle" class="widefat" id="<?php echo $wTitleID ?>" name="<?php echo $wTitleNAME ?>" placeholder="<?php echo $wtitlePlaceholder ?>" type="text" value="<?php echo $wTitle; ?>" <?php echo $wTitleDisabled; ?> />
			</p>
			<p <?php echo $columnsDisplayed; ?>>
				<label for="<?php echo $columnsID; ?>" <?php echo $columnsDisabled; ?>><?php echo $columnsLabelVALUE ?></label>
				<input data-parameter-name="columns" class="widefat" id="<?php echo $columnsID ?>" name="<?php echo $columnsNAME ?>" placeholder="<?php echo $columnsPlaceholder ?>" type="number" min="1" max="9" step="1" value="<?php echo $columns; ?>" <?php echo $columnsDisabled; ?> required="required" />
			</p>
			<p>
				<label for="<?php echo $typeID ?>"><?php echo $typeLabelVALUE ?></label>
				<select data-parameter-name="interaction-type" class="widefat" id="<?php echo $typeID; ?>" name="<?php echo $typeNAME; ?>">
					<?php
						if(!$recommsTypes[$type]['allowed']){
							?>
								<option <?php selected( true ) ?>><?php _e('Current value is not available anymore, select another one', 'recombee-recommendation-engine') ?></option>
							<?php
						}
						foreach($recommsTypes as $recommsName => $recommsType){

							if($recommsType['allowed']){
								($type == $recommsName) ? $last_selected = 'last-selected="true"' : $last_selected = 'last-selected="false"';
								?>
									<option data-relevant-parameters='<?php echo json_encode($recommsType['params']) ?>' data-filters-set-key="<?php echo $recommsType['filters']; ?>" <?php selected( $type, $recommsName ) ?> value="<?php echo $recommsName ?>" <?php echo $last_selected ?>><?php echo $recommsType['viewName'] ?></option>
								<?php
							}
						}
					?>
				</select>
			</p>
			<p <?php echo $countDisplayed; ?>>
				<label for="<?php echo $countID; ?>" <?php echo $countDisabled; ?>><?php echo $countLabelVALUE ?></label>
				<input data-parameter-name="count" class="widefat" id="<?php echo $countID ?>" name="<?php echo $countNAME ?>" placeholder="<?php echo $countPlaceholder ?>" type="number" min="1" step="1" value="<?php echo $count; ?>" <?php echo $countDisabled; ?> required="required" />
			</p>
			<p <?php echo $scenarioDisplayed; ?>>
				<label for="<?php echo $scenarioID ?>" <?php echo $scenarioDisabled; ?>><?php echo $scenarioLabelVALUE ?></label>			
				<input data-parameter-name="scenario" class="widefat" id="<?php echo $scenarioID ?>" name="<?php echo $scenarioNAME ?>" placeholder="<?php echo $scenarioPlaceholder ?>" type="text" value="<?php echo $scenario; ?>" <?php echo $scenarioDisabled; ?> />
			</p>
			<div class="suppress-logic-wrapper">
				<p class="suppress-logic-toggler closed">
					<strong><?php _e('Suppress logic', 'recombee-recommendation-engine') ?></strong>
				</p>
				<div id="suppress-block" style="display: none;">
					<p class="r-dummy"></p>
					<div class="switch-radio">
						<label for="<?php echo $suppressLogicOffID; ?>" <?php echo $suppressLogicOffDisabled; ?>><?php echo $suppressLogicOffLabelVALUE ?></label>
						<input data-parameter-name="suppressLogic" type="radio" id="<?php echo $suppressLogicOffID; ?>" name="<?php echo $suppressLogicNAME ?>" value="off" <?php checked( $suppressLogic, 'off' ); ?>>
					</div>
					<div class="switch-radio">
						<label for="<?php echo $suppressLogicExcID; ?>" <?php echo $suppressLogicExcDisabled; ?>><?php echo $suppressLogicExcLabelVALUE ?></label>
						<input data-parameter-name="suppressLogic" type="radio" id="<?php echo $suppressLogicExcID; ?>" name="<?php echo $suppressLogicNAME ?>" value="exclude" <?php checked( $suppressLogic, 'exclude' ); ?>>
					</div>
					<div class="switch-radio">
						<label for="<?php echo $suppressLogicIncID; ?>" <?php echo $suppressLogicIncDisabled; ?>><?php echo $suppressLogicIncLabelVALUE ?></label>
						<input data-parameter-name="suppressLogic" type="radio" id="<?php echo $suppressLogicIncID; ?>" name="<?php echo $suppressLogicNAME ?>" value="include" <?php checked( $suppressLogic, 'include' ); ?>>
					</div>
					<div id="suppress-type-wrapper">
						<span><?php _e('* Toggle objects type:', 'recombee-recommendation-engine'); ?></span>
						<input data-suppress-last-state='{"posts":{"<?php echo $suppressPostsID ?>":[]},"terms":{"<?php echo $suppressPostsID ?>":[]}}' data-suppress-placeholders='<?php echo $suppressPostsPlaceholder ?>' class="tgl tgl-flip" data-parameter-name="suppressSubject" id="<?php echo $suppressSubjectID ?>" name="<?php echo $suppressSubjectNAME ?>" <?php checked( $suppressSubject, 'terms' ); ?> value="terms" type="checkbox" <?php echo $suppressSubjectDisabled; ?> style="display: none;">
						<label class="tgl-btn" data-tg-off="<?php _e('Posts', 'recombee-recommendation-engine') ?>" data-tg-on="<?php _e('Terms', 'recombee-recommendation-engine') ?>" for="<?php echo $suppressSubjectID ?>" <?php echo $suppressSubjectDisabled; ?>></label>
					</div>
					<select multiple data-action="RecombeeSearchObjects" data-parameter-name="suppressPosts" data-language="<?php echo substr(get_locale(), 0, 2) ?>" type="hidden" class="suppress-posts" id="<?php echo $suppressPostsID ?>" name="<?php echo $suppressPostsNAME ?>" <?php echo $suppressPostsDisabled; ?>>
						<?php
							foreach($suppressPosts as $suppressPost){
								
								if( $suppressSubject == 'posts' ){
									$post_data = get_post($suppressPost);
									if($post_data){
										
										$post_type = get_post_type_object($post_data->post_type)->label;
										echo '<option selected="selected" value="' . $suppressPost . '">' . $post_type . ': ' . $post_data->post_title . '</option>';
									}
									else{
										
										echo '<option selected="selected" value="' . $suppressPost . '">Post ID ' . $suppressPost . ' - not found</option>';
									}
								}
								else if( $suppressSubject == 'terms' ){
									$term_data = get_term_by( 'term_taxonomy_id', $suppressPost );
									if(!is_wp_error($term_data)){
										
										$taxonomy = get_taxonomy($term_data->taxonomy)->label;
										echo '<option selected="selected" value="' . $suppressPost . '">' . $taxonomy . ': ' . $term_data->name . '</option>';
									}
									else{
										
										echo '<option selected="selected" value="' . $suppressPost . '">Term ID ' . $suppressPost . ' - not found</option>';
									}
								}
							}
						?>
					</select>
					<p class="note"><?php _e('* - only one type saves', 'recombee-recommendation-engine'); ?></p>
					<p class="r-dummy"></p>
				</div>
			</div>
			<div class="expert-options-wrapper">
				<p class="expert-options-toggler closed"><strong><?php _e('Expert options', 'recombee-recommendation-engine'); ?></strong></p>
				<div class="recombee-expert-parameters" style="display: none;">
					<p <?php echo $userImpactDisplayed; ?>>
						<label for="<?php echo $userImpactID ?>" <?php echo $userImpactDisabled; ?>><?php echo $userImpactLabelVALUE ?></label>
						<input data-parameter-name="userImpact" class="widefat" id="<?php echo $userImpactID; ?>" name="<?php echo $userImpactNAME; ?>" placeholder="<?php echo $userImpactPlaceholder ?>" type="number" min="0" max="1" step="0.05" value="<?php echo $userImpact; ?>" <?php echo $userImpactDisabled; ?> />
					</p>
					<p <?php echo $filterDisplayed; ?>>
						<label class="tooled" for="<?php echo $filterID ?>" <?php echo $filterDisabled; ?>><span><?php echo $filterLabelVALUE ?></span><span class="widget-tools"><span 
						data-dialog-title="<?php _e('Query constructor for filter', 'recombee-recommendation-engine') ?>"
						data-generate-btn-text="<?php _e('Get Filter query expression', 'recombee-recommendation-engine') ?>" 
						data-clear-btn-text="<?php _e('Clear query field', 'recombee-recommendation-engine') ?>" 
						data-result-json-getter-id="<?php echo $filterID . '-json' ?>" 
						data-result-reql-getter-id="<?php echo $filterID . '-reql' ?>" 
						id="<?php echo 'query-'.$filterID ?>" class="qb dashicons dashicons-admin-settings"></span></span></label>
						<input type="hidden" id="<?php echo $filterID . '-json' ?>" name="<?php echo $filterJsonNAME ?>" value='<?php echo $filterJson; ?>'/>
						<textarea readonly="true" data-parameter-name="filter" data-prev-interaction-set='<?php echo json_encode(array('ProductsToCustomer' => '', 'ProductsToProduct' => '', 'CustomersToCustomer' => '', 'CustomersToProduct' => '')) ?>' data-filters-write-warning="<?php _e('You are about to unlock filter input field. Click OK to unlock field and write filter ReQL manually. Keep in mind - Query Builder may stop working correctly.', 'recombee-recommendation-engine') ?>"  class="widefat" id="<?php echo $filterID . '-reql' ?>" name="<?php echo $filterReqlNAME ?>" placeholder="<?php echo $filterPlaceholder ?>" spellcheck="false" <?php echo $filterDisabled; ?>><?php echo $filterReql; ?></textarea>
					</p>
					<p <?php echo $boosterDisplayed; ?>>
						<label class="tooled" for="<?php echo $boosterID ?>" <?php echo $boosterDisabled; ?>><span><?php echo $boosterLabelVALUE ?></span><span class="widget-tools"><span 
						data-dialog-title="<?php _e('Query constructor for booster', 'recombee-recommendation-engine') ?>"
						data-generate-btn-text="<?php _e('Get Booster query expression', 'recombee-recommendation-engine') ?>" 
						data-clear-btn-text="<?php _e('Clear query field', 'recombee-recommendation-engine') ?>" 
						data-result-json-getter-id="<?php echo $boosterID . '-json' ?>" 
						data-result-reql-getter-id="<?php echo $boosterID . '-reql' ?>" 
						id="<?php echo 'query-'.$boosterID ?>" class="qb dashicons dashicons-admin-settings"></span></span></label>
						<input type="hidden" id="<?php echo $boosterID . '-json' ?>" name="<?php echo $boosterJsonNAME ?>" value='<?php echo $boosterJson; ?>'/>
						<textarea readonly="true" data-parameter-name="booster" data-prev-interaction-set='<?php echo json_encode(array('ProductsToCustomer' => '', 'ProductsToProduct' => '', 'CustomersToCustomer' => '', 'CustomersToProduct' => '')) ?>' data-filters-write-warning="<?php _e('You are about to unlock booster input field. Click OK to unlock field and write booster ReQL manually. Keep in mind - Query Builder may stop working correctly.', 'recombee-recommendation-engine') ?>"  class="widefat" id="<?php echo $boosterID . '-reql' ?>" name="<?php echo $boosterReqlNAME ?>" placeholder="<?php echo $boosterPlaceholder ?>" spellcheck="false" <?php echo $boosterDisabled; ?>><?php echo $boosterReql; ?></textarea>
						<div id="booster_statement_wrapper" <?php echo ($boosterReql == '') ? 'style="display: none"' : '' ?>>
							<span class="booster_statement"><?php _e('then', 'recombee-recommendation-engine') ?></span>
							<span class="booster_slider_wrapper">
								<input type="hidden" id="<?php echo $boosterID . '-then' ?>" name="<?php echo $boosterThenNAME ?>" value='<?php echo $boosterThen; ?>'/>
							</span>
							<span class="booster_statement"><?php _e('else', 'recombee-recommendation-engine') ?></span>
							<span class="booster_slider_wrapper">
								<input type="hidden" id="<?php echo $boosterID . '-else' ?>" name="<?php echo $boosterElseNAME ?>" value='<?php echo $boosterElse; ?>'/>
							</span>
						</div>
					</p>
					<p <?php echo $diversityDisplayed; ?>>
						<label for="<?php echo $diversityID ?>" <?php echo $diversityDisabled; ?>><?php echo $diversityLabelVALUE ?></label>
						<input data-parameter-name="diversity" class="widefat" id="<?php echo $diversityID ?>" name="<?php echo $diversityNAME ?>" placeholder="<?php echo $diversityPlaceholder ?>" type="number" min="0" max="1" step="0.05" value="<?php echo $diversity; ?>" <?php echo $diversityDisabled; ?> />
					</p>
					<p <?php echo $minRelevanceDisplayed; ?>>
						<label for="<?php echo $minRelevanceID ?>" <?php echo $minRelevanceDisabled; ?>><?php echo $minRelevanceLabelVALUE ?></label>
						<select data-parameter-name="minRelevance" class="widefat" id="<?php echo $minRelevanceID ?>" name="<?php echo $minRelevanceNAME ?>" <?php echo $minRelevanceDisabled; ?>>
							<option <?php selected( $minRelevance, 'low' ) ?> value="low"><?php _e('Low', 'recombee-recommendation-engine'); ?></option>
							<option <?php selected( $minRelevance, 'medium' ) ?> value="medium"><?php _e('Medium', 'recombee-recommendation-engine'); ?></option>
							<option <?php selected( $minRelevance, 'high' ) ?> value="high"><?php _e('High', 'recombee-recommendation-engine'); ?></option>
						</select>
					</p>
					<p <?php echo $rotationRateDisplayed; ?>>
						<label for="<?php echo $rotationRateID ?>" <?php echo $rotationRateDisabled; ?>><?php echo $rotationRateLabelVALUE ?></label>
						<input data-parameter-name="rotationRate" class="widefat" id="<?php echo $rotationRateID ?>" name="<?php echo $rotationRateNAME ?>" placeholder="<?php echo $rotationRatePlaceholder ?>" type="number" min="0" max="1" step="0.05" value="<?php echo $rotationRate; ?>" <?php echo $rotationRateDisabled; ?> />
					</p>
					<p <?php echo $rotationTimeDisplayed; ?>>
						<label for="<?php echo $rotationTimeID ?>" <?php echo $rotationTimeDisabled; ?>><?php echo $rotationTimeLabelVALUE ?></label>
						<input data-parameter-name="rotationTime" class="widefat" id="<?php echo $rotationTimeID ?>" name="<?php echo $rotationTimeNAME ?>" placeholder="<?php echo $rotationTimePlaceholder ?>" type="number" min="1" step="1" value="<?php echo $rotationTime; ?>" <?php echo $rotationTimeDisabled; ?> />
					</p>
				</div>
			</div>
			<p class="r-dummy"></p>
			<input type="submit" class="recombee-submit-error" hidden="hidden" style="display: none;">
		</div>
		<?php
	}
	
	public function update( $new_instance, $old_instance ){
		
		$instance = [];
		
		( !isset($new_instance['ajaxMode'])			)	? $new_instance['ajaxMode']			= 'off'									: '';
		( !isset($new_instance['followThemeCss'])	)	? $new_instance['followThemeCss']	= 'off'									: '';
		( !isset($new_instance['parentsOnly'])		)	? $new_instance['parentsOnly']		= 'off'									: '';
		( !isset($new_instance['suppressSubject'])	)	? $new_instance['suppressSubject']	= $this->defaults['suppressSubject']	: '';
		( !isset($new_instance['userImpact'])		)	? $new_instance['userImpact']		= null									: '';
		
		$ajaxMode			= wp_strip_all_tags($new_instance['ajaxMode']);
		$followThemeCss		= wp_strip_all_tags($new_instance['followThemeCss']);
		$parentsOnly		= wp_strip_all_tags($new_instance['parentsOnly']);
		$suppressLogic		= $new_instance['suppressLogic'];
		$suppressSubject	= $new_instance['suppressSubject'];
		$suppressPosts		= $new_instance['suppressPosts'];
		$count				= wp_strip_all_tags($new_instance['count']);
		$columns			= wp_strip_all_tags($new_instance['columns']);
		$scenario			= wp_strip_all_tags($new_instance['scenario']);
		$userImpact			= wp_strip_all_tags($new_instance['userImpact']);
		$filter_json		= wp_strip_all_tags($new_instance['filter_json']);
		$filter_reql		= wp_strip_all_tags($new_instance['filter_reql']);
		$booster_json		= wp_strip_all_tags($new_instance['booster_json']);
		$booster_reql		= wp_strip_all_tags($new_instance['booster_reql']);		
		$booster_then		= wp_strip_all_tags($new_instance['booster_then']);		
		$booster_else		= wp_strip_all_tags($new_instance['booster_else']);		
		$diversity			= wp_strip_all_tags($new_instance['diversity']);
		$minRelevance		= wp_strip_all_tags($new_instance['minRelevance']);
		$rotationRate		= wp_strip_all_tags($new_instance['rotationRate']);
		$rotationTime		= wp_strip_all_tags($new_instance['rotationTime']);
		
		if(empty($count) || is_null((bool)$count) || $count <= 0 ){
			$count = $this->defaults['count'];
		}
		if( empty($userImpact) && $userImpact != 0 ){
			$userImpact = $this->defaults['userImpact'];
		}
		else if( is_null((bool)$userImpact) || $userImpact < 0 || $userImpact > 1 ){
			$userImpact = $this->defaults['userImpact'];
		}
		
		$instance['ajaxMode']			= $ajaxMode;
		$instance['followThemeCss']		= $followThemeCss;
		$instance['parentsOnly']		= $parentsOnly;
		$instance['suppressLogic']		= $suppressLogic;
		$instance['suppressSubject']	= $suppressSubject;
		$instance['suppressPosts']		= $suppressPosts;
		$instance['wTitle']				= $new_instance['wTitle'];
		$instance['columns']			= $columns;
		$instance['type']				= wp_strip_all_tags($new_instance['type']);
		$instance['count']				= $count;
		$instance['scenario']			= preg_replace("/[^a-zA-Z0-9_\-#:]/", "_", $scenario, -1);
		$instance['userImpact'] 		= $userImpact;
		$instance['filter_json']		= $filter_json;
		$instance['filter_reql']		= trim( str_replace("`", "\"", $filter_reql) );
		$instance['booster_json']		= $booster_json;
		$instance['booster_reql']		= trim( str_replace("`", "\"", $booster_reql) );		
		$instance['booster_then']		= $booster_then;		
		$instance['booster_else']		= $booster_else;		
		$instance['diversity'] 			= $diversity;
		$instance['minRelevance'] 		= $minRelevance;
		$instance['rotationRate'] 		= $rotationRate;
		$instance['rotationTime'] 		= $rotationTime;
		
		/* service data */
		$instance['cascadeCreate']		= false;
		$instance['returnProperties']	= false;
		$instance['includedProperties']	= array();

        return $instance;
	}
	
	public function footer(){
		
 		if( !empty(self::$do_ajax_widgets) && count(self::$do_ajax_widgets) > 0){
			
			$object_data = array(
				'AJAX_Marker'	=> RRE_PLUGIN_DIR,
				'ajaxUrl'		=> $this->recombee->rre_ajax_interface->get_virtual_page('ajax'),
				'action'		=> 'RecombeeDoAjaxWidgets',
				'nonce'			=> wp_create_nonce( 'RecombeeDoAjaxWidgets' ),
				'do_widgets'	=> base64_encode(serialize(self::$do_ajax_widgets)),
			);
			
			wp_localize_script( 'widgets_front_js', 'recombee_do_ajax_widgets', $object_data );
		}
	}
	
	private function prepareFilterExpr( $instance ){
		
		if( $instance['type'] == 'ProductsToCustomer' || $instance['type'] == 'ProductsToProduct' ){
			/* FILTER */
			$filter = array('\'wpStatus\' != "deleted"');
			
			if( $instance['parentsOnly'] == 'on' ){
				$filter[] = '\'wcProductType\' != "variation"';
			}
			
			if( is_multisite() ){
				$filter[] = '\'wcShopId\' == ' . get_current_blog_id() . '';
			}
			else{
				$filter[] = '\'wcShopId\' == -1';
			}		
			
			if( empty($instance['filter']) ){
				$instance['filter'] .= implode(' AND ', $filter);
			}
			else{
				$instance['filter'] .= ' AND ' . implode(' AND ', $filter);
			}
			
			/* BOOSTER */
			if( !empty($instance['booster']) ){
				if( strpos ( $instance['booster'], '#%shortcoded%#' ) ){
					$instance['booster'] = str_replace("#%shortcoded%#", "", $instance['booster']);
				}
				else{
					$instance['booster'] = 'if (' . $instance['booster'] . ') then ' . $instance['booster_then'] . ' else ' . $instance['booster_else'];
				}
			}
		}

		return $instance;
	}
	
	private function displayRecommends($widgetArgs, $widgetInstance, $requestResult){
		
		echo $widgetArgs['before_widget'];
		
			if( $this->initBlogSetting['debug_mode'] == 1 ){
				if( isset($requestResult['errors']) ){
					
					$text = $requestResult['errors'];
					$title = sprintf(__('Widget %s says:', 'recombee-recommendation-engine' ), $widgetInstance['wTitle'] );
					$this->displayWarning($title, array($text));
				}
				else if( count($requestResult['success']['recomms']) === 0 ){
					
					$text = __('No recommendations were returned.', 'recombee-recommendation-engine');
					$title = sprintf(__('Widget %s says:', 'recombee-recommendation-engine' ), $widgetInstance['wTitle'] );
					$this->displayWarning($title, array($text));
				}
			}
			
			if( isset($requestResult['success']) && count($requestResult['success']['recomms']) > 0 ){
				
				if( !empty($widgetInstance['wTitle']) ){
					?><p class="widget-title"><?php echo $widgetInstance['wTitle'] ?></p>
					<?php }
					
						$section_classes = 'products recombee-products';
						
						if( $widgetInstance['followThemeCss'] == 'on' ){
							$section_classes .= ' follow-theme-css-true';
							wc_set_loop_prop( 'columns', $widgetInstance['columns'] );
						}
						else{
							$section_classes .= ' follow-theme-css-false';
						}
					?>
					<section class="<?php echo $section_classes ?>">
						
						<?php
							if( $widgetInstance['followThemeCss'] == 'on' ){
								woocommerce_product_loop_start();
							}
							else{
								?><ul class="recombee-products-list recomms-columns-<?php echo $widgetInstance['columns'];?>"><?php
							}
						
								$user_logged_in = is_user_logged_in();
								$non_exists_WP_products = array();
								
								add_filter('post_class', array($this, 'addProductClasses') );
								
								if( wp_doing_ajax() ){
									add_filter('woocommerce_product_add_to_cart_url', array($this, 'addToCartUrl'), 10, 2 );
								}
							
								foreach ( $requestResult['success']['recomms'] as $products ){
									
									$post_object = get_post( $products['id'] );
									$product = wc_get_product( $post_object );
									setup_postdata( $GLOBALS['post'] =& $post_object );
									
									if($product){
										
										if( $product->is_visible() ){
											
											if( $widgetInstance['followThemeCss'] == 'on' ){
												
												ob_start();
													wc_get_template_part( 'content', 'product' );
												echo ob_get_clean();
											}
											else{	
												?><div class="product-slot">
													<a href="<?php echo get_permalink() ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
														<?php
															if($product)
																
																echo woocommerce_get_product_thumbnail();
																echo woocommerce_template_loop_product_title();
																if( $product->is_on_sale() ){
																	woocommerce_show_product_loop_sale_flash();
																}
																woocommerce_template_loop_price();
																woocommerce_template_loop_rating();
														?>
													</a>
													<?php
													woocommerce_template_loop_add_to_cart();
												?></div><?php
											}
										}
										else if( $this->initBlogSetting['debug_mode'] == 1 ){
											
											$text = $this->explainHiddenProduct($products['id'], $product);
											
											$title = sprintf(__('Widget %s says:', 'recombee-recommendation-engine' ), $widgetInstance['wTitle'] );
											$this->displayWarning($title, array($text));
										}
									}
									else{
										
										if( $this->initBlogSetting['debug_mode'] == 1 ){
											
											$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it does not exists in your shop and will be deleted at Recombee if it has no parent or parent does not exists in your shop too.', 'recombee-recommendation-engine' ),  $products['id'] );
											
											$title = sprintf(__('Widget %s says:', 'recombee-recommendation-engine' ), $widgetInstance['wTitle'] );
											$this->displayWarning($title, array($text));
										}
									
										$non_exists_WP_products[] = (int)$products['id'];
									}
								}
								
								remove_filter('post_class', array($this, 'addProductClasses') );
								remove_filter('woocommerce_product_add_to_cart_url', array($this, 'addToCartUrl'), 10 );
								
							if( $widgetInstance['followThemeCss'] == 'on' ){
								woocommerce_product_loop_end();
							}
							else{
								?></ul><?php
							}
							?>
					</section>
				<?php
				wp_reset_postdata();
				  
				if( count($non_exists_WP_products) > 0 ){
				  
					$RecombeeBlogSettingsDb = RecombeeReBlogSettingsDb::instance();
					$RecombeeBlogSettingsDb->dbDeleteProduct($non_exists_WP_products);
				}
			}
			
		echo $widgetArgs['after_widget'];
	}
	
	private function explainHiddenProduct($product_id, $product){
		
		$text = null;
		$product_status = $product->get_status();
		
		if ( $product->get_parent_id() ) {
			
			$parent_product = wc_get_product( $product->get_parent_id() );
			
			if ( $parent_product ){
				
				$parent_status	= $parent_product->get_status();
				$parent_id		= $parent_product->get_id();
				
				if( $parent_product->get_catalog_visibility() == 'hidden' ){
					$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s" in status "Hidden" for "Catalog visibility". Product can not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id, $parent_id );
				}
				else if ( $parent_status !== 'publish' && !current_user_can('edit_post', $parent_id) ) {
					
					if ( $parent_status == 'pending' || $parent_status == 'draft' ) {
						$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s" in status "%s". Product can not not be shown to current user due to it\'s access restrictions.', 'recombee-recommendation-engine' ),  $product_id, $parent_id, ucfirst($parent_status) );
					}
					else if ( $parent_status == 'trash' ) {
						$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s" in status "Trash". Product can not not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id, $parent_id );
					}
					else{
						$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s" in unknown status. Product can not not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id, $parent_id );
					}
				}
				else if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes'  ){
					
					if( !$product->is_in_stock() ){
						$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s". Product is out of stock due to variable product settings and can not be shown.', 'recombee-recommendation-engine' ),  $product_id, $parent_id );
					}
					else{
						$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s". Product is in stock according to variable product settings but can not be shown by the unknown reason.', 'recombee-recommendation-engine' ),  $product_id, $parent_id );
					}
				}
				else{
					
					$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s" that is hidden in product catalog by the unknown reason. Product can not not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id, $parent_id );
				}
			}
			else{
				
				$text = sprintf(__( 'Recombee DB returned Product with ID="%s", that has parent product with ID="%s". Parent ID is valid ID, but the attempt to get Product by this ID has failed.', 'recombee-recommendation-engine' ),  $product_id, $parent_id );
			}
		}
		else{

			if( $product->get_catalog_visibility() == 'hidden' ){
				$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it has status "Hidden" for "Catalog visibility" option and can not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id );
			}
			else if ( $product_status !== 'publish' && !current_user_can('edit_post', $product_id) ) {
				
				if ( $product_status == 'pending' || $product_status == 'draft' ) {
					$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it has status "%s" and can not be shown to current user due to it\'s access restrictions.', 'recombee-recommendation-engine' ),  $product_id, ucfirst($product_status) );
				}
				else if ( $product_status == 'trash' ) {
					$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it has status "Trash" and can not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id );
				}
				else{
					$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it has unknown status and can not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id );
				}
			}
			else if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' && ! $product->is_in_stock() ){
				
				$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it is out of stock and can not be shown until current shop configured to hide out of stock products.', 'recombee-recommendation-engine' ),  $product_id );
			}
			else{
				
				$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it is hidden in product catalog by the unknown reason and can not be shown until it\'s true.', 'recombee-recommendation-engine' ),  $product_id );
			}
		}
		
		if( empty($text) ){
			$text = sprintf(__( 'Recombee DB returned Product with ID="%s", but it can not be shown by the unknown reason.', 'recombee-recommendation-engine' ),  $product_id );
		}
		
		return $text;
	}
	
	private function displayWarning($title, $text_array, $echo = true){

		$html = '<div class="widget-warning-holder"><div class="widget-warning"><p>' . $title . '</p><ul>';
		
		foreach($text_array as $text){
			$html .= '<li>' . $text . '</li>';
		}
		
		$html .= '</ul></div></div>';
		
		if($echo){
			echo $html;
		}
		else{
			return $html;
		}
	}
	
	public function get_filters(){
		
		$this->initBlogSetting = $this->recombee->get_blog_setting(RRE_BLOG_SETTINGS_PRESET_NAME);
		
		if(!is_admin()){
			return;
		}
		
		$stack = array(
			array('method' => 'reqsListItemProperties'),
			array('method' => 'reqsListUserProperties'),
		);
		
		$response = $this->recombee->communicator->reqsExecuteBatch('Getting Customer & Product properties on widget form settings', $stack, false, true);
		
		if($response['reqsExecuteBatch'] instanceof Exception){
			$this->props = false;
		}
		else{
			$all_item_props	=  $this->recombee->get_product_sync_prop_all();
			$all_user_props	=  $this->recombee->get_customer_sync_prop_all();
			$item_props		= array();
			$user_props		= array();
			
			foreach($response['reqsExecuteBatch'][0]['json'] as $item_prop){
				
				if( !isset($all_item_props[ $item_prop['name'] ]) ){
					continue;
				}
				
				if( $all_item_props[ $item_prop['name'] ]['queryBuilder'] === false){
					continue;
				}
				
				if( $all_item_props[ $item_prop['name'] ]['innerType'] == 'taxonomy' || $all_item_props[ $item_prop['name'] ]['innerType'] == 'attribute' ){
					
					if( $all_item_props[ $item_prop['name'] ]['taxonomy'] == false ){
						$all_item_props[ $item_prop['name'] ]['queryBuilder']['values'] = array();
						continue;
					}
					
					$clb	= $all_item_props[ $item_prop['name'] ]['queryBuilder']['values']['clb'];
					$args	= array( $all_item_props[ $item_prop['name'] ]['taxonomy'], $all_item_props[ $item_prop['name'] ]['queryBuilder']['values']['args'] );
					$values = call_user_func_array($clb, $args);
					
					if( count($values) > 0 ){
						foreach($values as $id => $name){
							$_values[] = array('value' =>$id, 'label'=> $name, 'optgroup'=> __('Native values', 'recombee-recommendation-engine'));
						}
						$all_item_props[ $item_prop['name'] ]['queryBuilder']['values'] = $_values;
						$_values = [];
					}
					else{
						continue;
					}
				}
				
				if( $item_prop['name'] == 'wpStatus' || $item_prop['name'] == 'wcProductType' ){
					foreach($all_item_props[ $item_prop['name'] ]['queryBuilder']['values'] as $id => $name){
						$_values[] = array('value' =>$id, 'label'=> $name, 'optgroup'=> __('Native values', 'recombee-recommendation-engine'));
					}
					$all_item_props[ $item_prop['name'] ]['queryBuilder']['values'] = $_values;
					$_values = [];
				}
				
				$item_props[] = array_merge( array(
					'id'	=> $item_prop['name'],
				), $all_item_props[ $item_prop['name'] ]['queryBuilder']);			
			}
			
			foreach($response['reqsExecuteBatch'][1]['json'] as $user_prop){
				
				if( $all_user_props[ $user_prop['name'] ]['queryBuilder'] === false){
					continue;
				}
				
				$user_props[] = array_merge( array(
					'id'	=> $user_prop['name'],
				), $all_user_props[ $user_prop['name'] ]['queryBuilder']);
			}
			
			$item_props = $this->extract_functions($item_props);
			$user_props = $this->extract_functions($user_props);
			
			$this->props = array('item_props' => $item_props, 'user_props' => $user_props, 'reql_funcs' => $this->reql_funcs,
				'operators_ref' => array(
					'equal'				=> '== %value%',
					'not_equal'			=> '!= %value%',
					'in'				=> 'IN {%value%}',
					'not_in'			=> 'NOT IN {%value%}',
					'less'				=> '< %value%',
					'less_or_equal'		=> '<= %value%',
					'greater'			=> '> %value%',
					'greater_or_equal'	=> '>= %value%',
					'is_null'			=> 'IS NULL',
					'is_not_null'		=> 'IS NOT NULL',
				),
				'operators'	=> array(
					array( 'type' => 'equal',				'optgroup' => 'comparison',		'nb_inputs' => 1, 'multiple' => false,	'apply_to' => array('string','number','datetime','boolean')),
					array( 'type' => 'not_equal',			'optgroup' => 'comparison', 	'nb_inputs' => 1, 'multiple' => false,	'apply_to' => array('string','number','datetime','boolean')),
					array( 'type' => 'in',					'optgroup' => 'containment', 	'nb_inputs' => 1, 'multiple' => true,	'apply_to' => array('string','number','datetime')),
					array( 'type' => 'not_in',				'optgroup' => 'containment', 	'nb_inputs' => 1, 'multiple' => true,	'apply_to' => array('string','number','datetime')),
					array( 'type' => 'less',				'optgroup' => 'comparison', 	'nb_inputs' => 1, 'multiple' => false,	'apply_to' => array('number','datetime')),
					array( 'type' => 'less_or_equal',		'optgroup' => 'comparison', 	'nb_inputs' => 1, 'multiple' => false,	'apply_to' => array('number','datetime')),
					array( 'type' => 'greater',				'optgroup' => 'comparison', 	'nb_inputs' => 1, 'multiple' => false,	'apply_to' => array('number','datetime')),
					array( 'type' => 'greater_or_equal',	'optgroup' => 'comparison', 	'nb_inputs' => 1, 'multiple' => false,	'apply_to' => array('number','datetime')),
					array( 'type' => 'is_null',				'optgroup' => 'logical',		'nb_inputs' => 0, 'multiple' => false,	'apply_to' => array('string','number','datetime','boolean')),
					array( 'type' => 'is_not_null',			'optgroup' => 'logical',		'nb_inputs' => 0, 'multiple' => false,	'apply_to' => array('string','number','datetime','boolean')),
				),
			);
			return $this->props;
		}
	}
	
	private function extract_functions($prop_set){
		
		$putted_into_filter	= array();
		$prop_key_value		= array();
		$funcs_as_values	= array();
		
		foreach($prop_set as $id => $set){
			
			if( $set['lhs_funcs'] !== false){
				foreach($set['lhs_funcs'] as $lhs_func){
				
					$prop_key_value[$lhs_func][] = array(
						'value'		=> $set['id'],
						'label' 	=> $set['label'],
						'optgroup'	=> __('Native values', 'recombee-recommendation-engine'),
					);
				}
			}
		}
		
		require RRE_ABSPATH . 'includes/data-ReQLSettingsArgs.php';
		
		foreach($reql_funcs as $func_name => $func_data){
			
			if( $func_data != false ){
				
				$funcs_as_values[] = array(
					'value'		=> $func_name,
					'label' 	=> $func_data['label'],
					'optgroup'	=> __('ReQL functions', 'recombee-recommendation-engine'),
				);
				if( !array_key_exists($func_name, $this->reql_funcs) ){
					$this->reql_funcs[ $func_name ] = $func_data;
				}
			}
		}
		
		foreach($prop_set as $id => $set){
			
			if( $set['rhs_funcs'] !== false){
				foreach($set['rhs_funcs'] as $rhs_func_name => $rhs_func_value){
					
					if( array_key_exists($rhs_func_value, $reql_funcs) ){
						$prop_set[$id]['values'][] = array(
							'value'		=> $rhs_func_value,
							'label' 	=> $reql_funcs[$rhs_func_value]['label'],
							'optgroup'	=> __('ReQL functions', 'recombee-recommendation-engine'),
						);
					}
					else if( $this->initBlogSetting['debug_mode'] == 1 ){
						$prop_set[$id]['values'][] = array(
							'value'		=> $rhs_func_value,
							'label' 	=> '_Restricted func: ' . $rhs_func_value,
							'optgroup'	=> __('ReQL functions', 'recombee-recommendation-engine'),
						);
					}
				}
			}
		}
		
		foreach($prop_set as $property){
			
			if( $property['lhs_funcs'] !== false){
				
				foreach($property['lhs_funcs'] as $prop_reql_func){
					
					if( array_key_exists($prop_reql_func, $reql_funcs) ){
						
						if( !in_array($prop_reql_func, $putted_into_filter) ){
							
							$prop_set[] = array(
								'id'			=> $prop_reql_func,
								'field'			=> 'reql_func_lhs',
								'values'		=> array_merge($prop_key_value[$prop_reql_func], $funcs_as_values),
								'type'			=> 'string',
								'input'			=> 'select',
								'multiple'		=> true,
								'plugin'		=> 'selectpicker',
								'plugin_config'	=> array(
									'container' 			=> 'body',
									'width'					=> 'auto',
									'style'					=> 'btn-xs',
									'selectedTextFormat'	=> 'count > 1',
									'noneSelectedText'		=> '----',
									'tickIcon'				=> 'far fa-check-square',
								),
								'label'		=> $reql_funcs[$prop_reql_func]['label'],
								'operators' => $reql_funcs[$prop_reql_func]['operators'],
								'optgroup'	=> $reql_funcs[$prop_reql_func]['optgroup'],
							);
							
							$putted_into_filter[] = $prop_reql_func;
						}
					}
					else{
						if( $this->initBlogSetting['debug_mode'] == 1 ){
							if( !in_array($prop_reql_func, $putted_into_filter) ){
								
								$prop_set[] = array(
									'id'	=> $prop_reql_func,
									'label' => '_Restricted func: ' . $prop_reql_func,
									'operators' => array('is_null'),
									'optgroup'	=> __('1) ReQL functions', 'recombee-recommendation-engine'),
								);
								
								$putted_into_filter[] = $prop_reql_func;
							}
						}
					}
				}
			}
		}
		return $prop_set;
	}
	
	public function widget_screen(){
		
		if($this->get_filters() == false){
			add_action('admin_notices', array($this, 'prop_notice'));
		}
	}
	
	public function prop_notice(){
		echo '<div class="notice notice-error is-dismissible"><p>' . __('Recombee Recommendation Widget could not get properties data from Recombee engine. Query builder is anavailable.', 'recombee-recommendation-engine') . '</p></div>';
	}
	
	private function displayed($param, $recommsType){
		
		if( !$recommsType['allowed'] || !in_array($param, $recommsType['params']) ){
			return ' visible="false" style="display: none;"';
		}
	}
	
	public function addToCartUrl( $url, $product ){ 
		
		if( $product->is_type( 'simple' ) ){
			return get_permalink() . '?' . parse_url($url)['query'];
		}
		else if( $product->is_type( 'external' ) ){
			return $product->get_product_url();
		}
		else{
			return get_permalink();
		}
	}
	
	public function addProductClasses($classes){
		
		/* Compatibility with Uplift CSS rules */
		
		if( !in_array('item-animated', $classes) ){
			$classes[] = 'item-animated';
		}
		return $classes;
	}
}