<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*	
*	Class RecombeeAdmin
*	$setting array @see dev_doc.txt | required
*/

class RecombeeReAdmin {
	
	private $param;
	public $menu_page_hook;
	
	public function __construct($setting){
		
		$this->param = $setting;
		
		add_action('admin_init',				array($this, 'registerChild'	), 10 );
		add_action('admin_init',				array($this, 'registerParent'	), 20 );
		add_action('admin_init',				array($this, 'loadTextdomain'	), 30 );
		add_action('rre_ajax_saveSettings',		array($this, 'saveSettings'		), 10 );
		add_action('admin_enqueue_scripts', 	array($this, 'enqueueChild'		), 10 );
		add_action('admin_enqueue_scripts', 	array($this, 'enqueueParent'	), 10 );
		add_filter('admin_body_class',			array($this, 'addClasses'		), 20 );
		add_action('admin_head',				array($this, 'adminHead'		), 10 );
		
		if ( $this->param['menu_type'] == 'network' && is_multisite() ) {
			//add_action( 'network_admin_menu', array($this, 'add_theme_menu_page'), 102);
		}
		else if( $this->param['menu_type'] == 'single' && !is_multisite() ){
			add_action	( 'admin_menu', array($this, 'add_theme_menu_page'), 102);
		}
		else{
			return;
		}
	}
	
	/* Register needed scripts and styles */
	public function registerParent(){

		$data = array(
			'styles' => array(
				array( 'handle' => 'menu_page_css',		'src' => RRE_PLUGIN_URL . '/includes/assets/css/menu-page.css',				'deps' => array() ),
				array( 'handle' => 'menu_tags_css',		'src' => RRE_PLUGIN_URL . '/includes/assets/css/bootstrap-tagsinput.css',	'deps' => array() ),
				array( 'handle' => 'jquery_ui_css',		'src' => RRE_PLUGIN_URL . '/includes/assets/css/jquery-ui.css',				'deps' => array() ),
				array( 'handle' => 'menu_chosen_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/chosen.css',				'deps' => array() ),
				array( 'handle' => 'prettyPhoto_css',	'src' => RRE_PLUGIN_URL . '/includes/assets/css/prettyPhoto.css',			'deps' => array() ),
			),
			'scripts' => array(
				array('handle' => 'menu_page_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/menu-page.js',					'deps' => array() ),
				array('handle' => 'menu_tags_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/bootstrap-tagsinput.js',			'deps' => array() ),
				array('handle' => 'menu_chosen_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/chosen.js',						'deps' => array() ),
				array('handle' => 'prettyPhoto_js',	'src' => RRE_PLUGIN_URL . '/includes/assets/js/prettyPhoto.js',					'deps' => array() ),
			),
		);

		$this->registerJsCss($data);
	}
	
	public function registerJsCss($data){
		
		if( isset($data['scripts']) ){

			foreach( $data['scripts'] as $script ){
				
				$script_args = wp_parse_args( $script, array( 'handle' => false, 'src' => false, 'deps' => array(), 'ver' => false, 'in_footer' => false) );
				
				if( isset($_GET['jscss']) && $_GET['jscss'] == 'dev' ){
					
					wp_register_script(	$script_args['handle'],$script_args['src'],$script_args['deps'],$script_args['ver'],$script_args['in_footer'] );	
				}
				else{

					$minified_url = $this->minify($script_args['src']);
					
					if( $minified_url !== false ){
						
						wp_register_script(	$script_args['handle'],$minified_url,$script_args['deps'],$script_args['ver'],$script_args['in_footer'] );
					}
				}
			}
		}
		
		if( isset($data['styles']) ){
			
			foreach( $data['styles'] as $style ){
				
				$style_args = wp_parse_args( $style, array( 'handle' => false, 'src' => false, 'deps' => array(), 'ver' => false, 'in_footer' => false) );

				if( isset($_GET['jscss']) && $_GET['jscss'] == 'dev' ){
					
					wp_register_style($style_args['handle'],$style_args['src'],$style_args['deps'],$style_args['ver'],$style_args['in_footer'] );
				}
				else{
					
					$minified_url = $this->minify($style_args['src']);
					
					if( $minified_url !== false ){
						
						wp_register_style($style_args['handle'],$minified_url,$style_args['deps'],$style_args['ver'],$style_args['in_footer'] );
					}
				}
			}
		}
	}
	
	private function minify($src){

		$minified = false;
		
		if( !class_exists('PHPWee\Minify', false) ){
			require_once str_replace('\\', '/', dirname(__FILE__)) . '/phpWee/phpwee.php';
		}
		
		if($src !== false){
			
			$url		= parse_url($src);
			$path		= dirname($_SERVER['DOCUMENT_ROOT'] . $url['path']);
			$src_name	= basename($url['path']);
			$min_name	= 'min.' . $src_name;
			
			$src_full_path = $path . '/' . $src_name;
			$min_full_path = $path . '/min/' . $min_name;
			
			@mkdir( $path . '/min' );
			
			if( is_dir($path . '/min') ){
				
				$src_time = filemtime($src_full_path);
				
				if( file_exists($min_full_path) ){
					
					$min_time = filemtime($min_full_path);
					($min_time != $src_time) ? $do_min = true : $do_min = false;
				}
				else{
					$do_min = true;
				}
				
				if($do_min){
					$ext	= pathinfo($src_name,PATHINFO_EXTENSION );
					$to_min = file_get_contents($src_full_path);
					
					if($ext == 'js'){
						$min = PHPWee\Minify::js( $to_min );
					}
					else if($ext == 'css'){
						$min = PHPWee\Minify::css( $to_min );
					}
					
					$min_file = file_put_contents($min_full_path, $min);
					
					if($min !== false && $min_file !== false){
						touch($min_full_path, $src_time);
						$minified = true;
					}
				}
				else{
					$minified = true;
				}
			}
		}
		
		if( $minified !== false ){
			return dirname($src) . '/min/' . $min_name;
		}
		else{
			return false;
		}
	}
	
	/* Enqueue needed scripts and styles */
	public function enqueueParent ($page_hook){
			
		/* STYLES */
		wp_enqueue_style ( 'style_backend_all'	);
		
		add_action( 'admin_print_styles-'  . $this->menu_page_hook, function () { wp_enqueue_style	( array('jquery_ui_css', 'menu_page_css', 'menu_tags_css'	)); }, 10, 1 );
		add_action( 'admin_print_styles-'  . $this->menu_page_hook, function () { wp_enqueue_style	( array('menu_chosen_css'									)); }, 10, 1 );
		add_action( 'admin_print_styles-'  . $this->menu_page_hook, function () { wp_enqueue_style	( array('prettyPhoto_css'									)); }, 10, 1 );
		
		/* SCRIPTS */
		add_action( 'admin_print_scripts-' . $this->menu_page_hook, function () { wp_enqueue_script( array('menu_page_js'										)); }, 10, 1 );
		add_action( 'admin_print_scripts-' . $this->menu_page_hook, function () { wp_enqueue_script( array('prettyPhoto_js'										)); }, 10, 1 );
		add_action( 'admin_print_scripts-' . $this->menu_page_hook, function () { wp_enqueue_script( array('jquery', 'menu_chosen_js', 'menu_tags_js'			)); }, 10, 1 );
		add_action( 'admin_print_scripts-' . $this->menu_page_hook, function () { wp_enqueue_script( array('jquery-ui-tabs'										)); }, 10, 1 );
		add_action( 'admin_print_scripts-' . $this->menu_page_hook, function () { wp_enqueue_script( array('jquery', 'jquery-effects-core', 'jquery-ui-draggable',
																								'jquery-ui-tooltip', 'jquery-effects-fade'));}, 10, 1);
																								
		wp_enqueue_script( array('backend_js'));
		
	}
	
	public function loadTextdomain (){
		
		load_textdomain	 ( 'menu-page', dirname(__FILE__) . '/assets/languages/menu-page-' . get_locale() . '.mo' );
	}
	
	public function add_theme_menu_page() {
		
		if(wp_doing_ajax()){
			return;
		}
		
		$this->menu_page_hook = add_menu_page(
			$this->param['page_title'],
			$this->param['menu_name'],
			$this->param['capability'],
			$this->param['page_slug'],
			array($this, 'page_content_callback'),
			$this->param['dashicons'],
			$this->param['priority']
		);

		
		if ( method_exists($this, 'pageContentHelpTabCallback') ){
						
			$help_tab_content = $this->pageContentHelpTabCallback();
			
			if ( $help_tab_content != null ){

				add_action( 'load-' . $this->menu_page_hook, array($this, 'add_page_help') );
			}
		}
		
		$this->enqueueParent($this->menu_page_hook);
	}
	
	public function adminHead(){
		
		if ( $GLOBALS['hook_suffix'] == $this->menu_page_hook ){
		
			echo '<audio preload="auto" class="menu-beep" id="sound-01">
					<source src="' . RRE_PLUGIN_URL . '/includes/assets/sound-01.mp3">
					<source src="' . RRE_PLUGIN_URL . '/includes/assets/sound-01.wav">
				 </audio>';
		}
	}
	
	public function page_content_callback(){
		
		do_action( 'RecombeeReBeforeSettingPage' );
		
		if(!$this->param['warning']){
			?>
				<div class="wrap warning">
					<?php 
						if ( method_exists($this, 'pageWarningCallback') ){
							echo $this->pageWarningCallback();
						}
					?>
				</div>
			<?php
			
			return;
		}
		
		if ( $this->param['menu_type'] == 'network' ){
			$options = get_site_option( $this->param['setting_preset'], false );
			
			if( empty($options) ){
				$options = false;
			}
		}
		else if ( $this->param['menu_type'] == 'single' ){
			$options = get_option( $this->param['setting_preset'], false );
		}
		
		$html = '';
		?>
			<div class="wrap">
				<h2><?php echo $this->param['page_h2'] ?></h2>
				<div id="tabs">
					<ul>
					<?php
						foreach( $this->param['controls'] as $tab => $content ){
									
							?>
								<li><a href="#<?php echo $content['id'] ?>"><?php echo $tab; ?></a></li>
							<?php
							
							$html .= '<div id="' . $content['id'] . '">';
							
								foreach( $content['controls'] as $control ){
								
									if( isset($control['id']) ){
										if( !empty($control['id']) ){
											$id = 'id="' . $control['id'] . '"';
										}
										else{
											$id = '';
										}
									}
									else{
										$id = '';
									}
										
									if( $control['type'] == 'select' && $control['select_multi'] === true){
										if( isset($control['class']) ){
											$class = 'class="chosen ' . implode(' ', $control['class']) . '"';
										}
										else{
											$class = 'class="chosen"';
										}
									}
									else if( isset($control['class']) ){

										$class = 'class="' . implode(' ', $control['class']) . '"';
									}
									else{
										$class = '';
									}
									
									if( isset($control['data']) && is_array($control['data']) ){
										$data_html = array();
										foreach($control['data'] as $data_name => $data_value){
											$data_value = is_bool($data_value) ? var_export($data_value, 1) : $data_value;
											$data_html[] = 'data-' . $data_name . '="' . $data_value . '"';
										}
										$data_html = implode(' ', $data_html);
									}
									else{
										$data_html = '';
									}
									
									if( isset($control['tip']) ){
										( is_callable($control['tip']) ) ? $tip = call_user_func( $control['tip'] ) : $tip = $control['tip'];
									}
									
									switch($control['type']){
										
										case 'checkbox':
										
										$html .= '<div class="control">' .
													'<span class="title">' . $control['title'] . '</span>' .
													'<label>' .
														'<input ' . $id . ' ' . $data_html . ' ' . $class . ' name="' . $control['name'] . '" type="checkbox"' . $this->get_value( 'checkbox', $options, $control['name'] ) . '/>' . $control['descr'] .
														'<p class="tip">' . $tip . '</p>' .
													'</label>' .
												'</div>';
										break;
										
										case 'button':
										
										$html .= '<div class="control">' .
													'<span class="title">' . $control['title'] . '</span>' .
													'<div>' .
														'<button ' . $id . ' ' . $data_html . ' ' . $class . ' name="' . $control['name'] . '"' . disabled( $control['disable'], true, false ) . '>' . $control['value'] . '</button>' .
														'<p class="tip">' . $tip . '</p>' .
													'</div>' .
													'<p></p>' .
												'</div>';
										break;
										
										case 'text':
										
										$html .= '<div class="control">' .
													'<span class="title">' . $control['title'] . '</span>' .
													'<div class="label">' .
														'<input ' . $id . ' ' . $data_html . ' ' . $class . ' name="' . $control['name'] . '"' . disabled( $control['disable'], true, false ) . 'type="text" spellcheck="false" placeholder="' . $control['placeholder'] . '"' . $this->get_value( 'text', $options, $control['name'] ) . '/>' .
														'<p class="tip">' . $tip . '</p>' .
													'</div>' .
												'</div>';
										break;
										
										case 'number':
										
										$html .= '<div class="control">' .
													'<span class="title">' . $control['title'] . '</span>' .
													'<div class="label">' .
														'<input ' . $id . ' ' . $data_html . ' ' . $class . ' name="' . $control['name'] . '"' . disabled( $control['disable'], true, false ) . 'min="' . $control['min'] . '"' . 'max="' . $control['max'] . '"' . 'step="' . $control['step'] . '"' . 'type="number" spellcheck="false" placeholder="' . $control['placeholder'] . '"' . $this->get_value( 'text', $options, $control['name'] ) . '/>' .
														'<p class="tip">' . $tip . '</p>' .
													'</div>' .
												'</div>';
										break;
										
										case 'hidden':
										
										$html .= '<div class="control hidden">' .
														'<input ' . $id . ' ' . $data_html . ' ' . $class . ' name="' . $control['name'] . '"' . 'type="hidden"' . $this->get_value( 'hidden', $options, $control['name'] ) . '/>' .
												'</div>';
										break;
										
										case 'textarea':
										
										$html .= '<div class="control">' .
													'<span class="title">' . $control['title'] . '</span>' .
													'<div class="label">' .
														'<textarea ' . $id . ' ' . $data_html . ' ' . $class . ' name="' . $control['name'] . '" type="textarea" spellcheck="false" placeholder="' . $control['placeholder'] . '">' . $this->get_value( 'textarea', $options, $control['name'] ) .
														'</textarea>' .
														'<p class="tip">' . $tip . '</p>' .
													'</div>' .
												'</div>';
										break;
										
										case 'select':
										
										if( $control['select_multi'] === true ){
											
											$html .= '<div class="control">' .
														'<span class="title">' . $control['title'] . '</span>' .
														'<div class="label">' .
															'<select ' . $id . ' ' . $data_html . ' ' . $class . ' multiple data-placeholder="' . $control['default'] . '"' . ' name="' . $control['name'] . '"' . disabled( $control['disable'], true, false ) . '>' . $this->get_multi_select_value( $options, $control['name'], $control['options'] ) .
															'</select>' .
															'<p class="tip">' . $tip . '</p>' .
														'</div>' .
													'</div>';
										}
										else{
										
											$html .= '<div class="control">' .
														'<span class="title">' . $control['title'] . '</span>' .
														'<div class="label">' .
															'<select ' . $id . ' ' . $data_html . ' ' . $class . ' name="' . $control['name'] . '">' . $this->get_select_value( $options, $control['name'], $control['options'], $control['default'], $control['default_value'] ) .
															'</select>' .
															'<p class="tip">' . $tip . '</p>' .
														'</div>' .
													'</div>';
										}
										break;
										
										case 'content':
										
										ob_start();
										call_user_func( $control['callback'] );
										$html .= ob_get_clean();
										break;
									}
								}

							$html .= '</div>';
						}
					?>
					</ul>
					<?php echo $html; ?>
				</div>
				<div id="submit">
					<input type="submit" id="save" disabled value="<?php _e('Nothing to save', 'menu-page') ?>"/>
				</div>
			</div>
			<script type="text/javascript">
				(function($){

					$(document).ready(function () {
						$('#tabs').data(
							{'AJAX_Marker'						: '<?php echo RRE_PLUGIN_DIR ?>',
							 'ajaxurl'							: '<?php echo RecombeeRe::instance()->rre_ajax_interface->get_virtual_page('ajax'); ?>',
							 'action'							: '<?php echo 'saveSettings' ?>',
							 'setting_form_nonce'				: '<?php echo wp_create_nonce( 'settings_nonce' ) ?>',
							 'menu_type'						: '<?php echo $this->param['menu_type'] ?>',
							 'setting_preset'					: '<?php echo $this->param['setting_preset'] ?>',
							 'onchange_btn_text'				: '<?php _e('Save changes','menu-page') ?>',
							 'StatusBetweenRequests'			: '<?php _e('Proceeding queue...','menu-page') ?>',
							 'AJAX_error_btn_text'				: '<?php _e('External error - reload page & Try again', 'menu-page') ?>',
							}
						);
					
						$( "#tabs" ).tabs({
							active		: '<?php echo $this->param['active_tab'] ?>',
							hide		: { effect: "fade", direction: "out",	duration: 200 },
							show		: { effect: "fade", direction: "in", 	duration: 200 },
							collapsible	: false
						 /* heightStyle	: "content", */
						});
						
						$('.chosen').chosen({
							width					: "600px",
							inherit_select_classes	: "true",
						});
						
						$('#tabs [data-taged="true"]').tagsinput({
							confirmKeys: [13, 32, 44],
							trimValue: true,
						});
						
						$('.wrap #save').trigger('uiInited');
					});
				})(jQuery);
			</script>
		<?php
	
	}
	
	private function get_select_value( $options, $name, $select_options, $select_default, $select_default_val ){
		
		$html = '<option value="' . $select_default_val . '">' . $select_default . '</option>';
		
		if( !$options ){
			
			foreach( $select_options as $key => $value ){

				$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
			}
		
			return $html;
		}
		else{
			
			if ( array_key_exists( $name, $options) ){
				
				foreach( $select_options as $key => $value ){

					$html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $options[$name], $key, false ) . '>' . esc_html( $value ) . '</option>';
				}
			
				return $html;
			}
			else{
				
				foreach( $select_options as $key => $value ){

					$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
				}
			
				return $html;
			}
		}
	}
	
	private function get_multi_select_value( $options, $name, $select_options ){
		
		$html= '';
		
		if( !$options ){
			
			foreach( $select_options as $key => $value ){

				$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
			}
		
			return $html;
		}
		else{
			
			if ( array_key_exists( $name, $options) ){
				
				foreach( $select_options as $key => $value ){
					
					/* multiselect */
					if( is_array($options[$name]) ){
						
						if($value == 'optgroup'){
							$html .= '<optgroup label="'.$key.'">';
							continue;
						}
						
						$selected = '';
						
						foreach( $options[$name] as $multi_value ){
							
							if( $multi_value == $key ){
								$selected = selected( $multi_value, $key, false );
								break;
							}
						}
						
						$html .= '<option value="' . esc_attr( $key ) . '" ' . $selected . disabled( preg_match("/\*/", esc_attr( $value ), $matches, null, 0), 1, false ) . '>' . esc_html( $value ) . '</option>';
					}
					/* select */
					else{
						
						$html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $options[$name], $key, false ) . '>' . esc_html( $value ) . '</option>';
					}
				}
			
				return $html;
			}
			else{
				
				foreach( $select_options as $key => $value ){

					$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
				}
			
				return $html;
			}
		}
	}
	
	private function get_value( $control_type, $options, $name, $predefined = false ){
		
		if( is_array($options) ){
			
			switch( $control_type ){
				
				case 'text':
			
					if ( array_key_exists( $name, $options) ){
						
						return 'value="' . $options[$name] . '"';
					}
						break;
						
				case 'textarea':
			
					if ( array_key_exists( $name, $options) ){
						
						return wp_unslash( $options[$name] );
					}
						break;
				
				case 'checkbox':
				
					if ( array_key_exists( $name, $options) && $options[$name] == 1 ){
					
						return 'checked="checked"';
					}
						break;
						
				case 'hidden':
			
					if ( array_key_exists( $name, $options) ){
						
						return 'value="' . $options[$name] . '"';
					}
						break;
			}
		}
	}
	
	public function add_page_help (){
		
		if ( $GLOBALS['hook_suffix'] == $this->menu_page_hook ) {
			
			$priority = 10;
			
			$tabs = $this->pageContentHelpTabCallback();
			
			foreach( $tabs as $name => $content ){
				
				get_current_screen()->add_help_tab(	array(
					'id'		=> $name . '_help',
					'title'		=> $name,
					'content'	=> $content,
					'priority'	=> $priority )
				);
				
				$priority++;
			}
			
			if ( method_exists($this, 'pageContentHelpSidebarCallback') ){
				
				$sidebar_content = $this->pageContentHelpSidebarCallback();
				
				if ( $sidebar_content != null ){
					get_current_screen()->set_help_sidebar( $sidebar_content );
				}
			}
		}
	}
	
	public function saveSettings() {
		
		if ( check_ajax_referer( 'settings_nonce', 'nonce', false) ){
			
			if ( isset($_POST['data']['menu_type']) && isset($_POST['data']['preset']) && is_array($_POST['data']['settings']) ){
				
				if( $_POST['data']['menu_type'] == 'network' ){
					
					$to_save = apply_filters( 'before_network_settings_preset_save', $_POST['settings'] );
					$options = get_site_option( $this->param['setting_preset'], false );
					if( $options == $to_save ){
						
						wp_send_json_success( array(
								'code'		=> 200,
								'btn_text'	=> __( 'Changes saved', 'menu-page' ),
								'new_preset'=> $options,
								'extra_data'=> apply_filters( 'network_save_message_extra_data', array() ),
							)
						);
					}
					
					$update = update_site_option( $_POST['data']['preset'], $to_save );
					$new_preset = get_site_option( $_POST['data']['preset'], false );
					
					if( $update === true ){
						
						wp_send_json_success( array(
								'code'		=> 200,
								'btn_text'	=> __( 'Changes saved', 'menu-page' ),
								'new_preset'=> $new_preset,
								'extra_data'=> apply_filters( 'network_save_message_extra_data', array() ),
							)
						);
					}
					else{
						
						wp_send_json_success( array(
								'code'		=> 400,
								'btn_text'	=> __( 'Saving error - repeat', 'menu-page' ),
							)
						);
					}
				}
				elseif( $_POST['data']['menu_type'] == 'single' ){
					
					$to_save = apply_filters( 'before_single_settings_preset_save', $_POST['data']['settings'] );
					$options = get_option( $this->param['setting_preset'], false );
					if( $options == $to_save ){
						
						wp_send_json_success( array(
								'code'		=> 200,
								'btn_text'	=> __( 'Changes saved', 'menu-page' ),
								'new_preset'=> $options,
								'extra_data'=> apply_filters( 'single_save_message_extra_data', array() ),
							)
						);
					}
					
					$update = update_option( $_POST['data']['preset'], $to_save );
					$new_preset = get_option( $_POST['data']['preset'], false );
				}
				
				if( $update === true ){
					
					wp_send_json_success( array(
							'code'		=> 200,
							'btn_text'	=> __( 'Changes saved', 'menu-page' ),
							'new_preset'=> $new_preset,
							'extra_data'=> apply_filters( 'single_save_message_extra_data', array() ),
						)
					);
				}
				else{
					
					wp_send_json_success( array(
							'code'		=> 400,
							'btn_text'	=> __( 'Saving error - repeat', 'menu-page' ),
						)
					);
				}
			}
		}
		else{
			
			wp_send_json_error( array(
					'code'		=> 403,
					'btn_text'	=> __( 'Permission error - reload page & Try again', 'menu-page' ),
				)
			);
		}
	}
}