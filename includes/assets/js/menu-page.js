jQuery(document).ready(function($) {
	
	var xhr	= null;
	var select_css_transition = 1000;
	
	$.fn.TabsPluginFrameWork = function(method){
		
		if ( TabsPluginFrameWorkMethods[method] ){
			return TabsPluginFrameWorkMethods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		}
		else if ( typeof method === 'object' || ! method ){
			$.error( 'Argument "Method" required for jQuery.TabsPluginFrameWork' );
		}
		else{
			$.error( 'Method ' +  method + ' does not exist for jQuery.TabsPluginFrameWork' );
		}
	}
  
	var TabsPluginFrameWorkMethods = {
		
		showMessage : function(data, type, caption = 'Notification'){
			
			var append_content = '';
			
			if($.type(data) == 'object'){
				for (var key in data){
					append_content = append_content + '<div>' + key + ": " + data[key] + '</div>';
				}
			}
			else if($.type(data) == 'error'){
				append_content = append_content + '<div>' + data.toString() + '</div>';
			}
			else if($.type(data) == 'array'){
				for (var i = 0; i < data.length; i++){
					append_content = append_content + '<div>' + data[i] + '</div>';
				}
			}
			else if($.type(data) == 'string'){
				append_content = '<div>' + data + '</div>';
			}
			else{
				return;
			}
			
			var active_messages = $('.message-wrapper');
			var bottom	= parseInt(active_messages.first().css('bottom'));
			var right	= parseInt(active_messages.first().css('right'));
			
			var message = $('<div class="message-wrapper" style="display: none;"><div class="menu-page-message rc-' + type + '"><div class="menu-operate"><div class="caption">' + caption + '</div><div class="close"></div></div><div class="content">' + append_content + '</div></div></div>');
			
			if( active_messages.length === 0){
				message = message.appendTo('body');
			}
			else{
				message = message.insertBefore( $(active_messages.first()) );
			}
			
			message.draggable({
				containment 	: 'window',
				cursor			: 'move',
				opacity			: '0.6',
				scroll			: true,
				snap			: 'html',
				snapTolerance	: 10,
			}).css({
				'bottom': bottom + 4 + 'px',
				'right' : right +  4 + 'px',
			}).show({
				effect: "fade",
				direction: 'in',
			}, 200);
			
			var closeTimer = setTimeout(function(e){
				message.find('.menu-operate .close').triggerHandler('click', [1200]);
			}, 3500);
			
			this.TabsPluginFrameWork('closeMessage', message);
			this.TabsPluginFrameWork('stopCloseMessage', message, closeTimer);
		},

		closeMessage : function (message, duration){
			if(typeof(duration) == 'undefined'){
				duration = 200;
			}
			message.find('.menu-operate .close').on('click', function(e, duration){
				if( typeof(e.isTrigger) == 'undefined'){
					message.off('mouseover');
				}
				message.hide({
					effect: "fade",
					direction: 'out',
				}, duration, function(){
					message.draggable( 'destroy' ).remove();
				});
			});
		},
		
		stopCloseMessage : function (message, closeTimer){
			message.on('mouseover', {timerId : closeTimer}, function(e){
				clearTimeout(e.data.timerId);
				message.stop(true).animate({
					'opacity' : '1',
				}, 300, function(){
					message.css('opacity', '');
				});
			});
		},
		
		updateControls : function(newSettings){
			
			for (var key in newSettings){
				if( typeof(newSettings[key]) == 'string' ){
					newSettings[key] = newSettings[key].replace(/\\"/g, '"');
					newSettings[key] = newSettings[key].replace(/\\'/g, '\'');
				}
			}
			
			formControls = $('.wrap #tabs').find('input:not(.chosen-search-input), select, textarea');
			
			for (var i = 0; i < formControls.length; i++){
				
				var controlName = formControls[i].name;
				var controlVal = newSettings[controlName];
				
				if( formControls[i].nodeName == 'INPUT' ){
					
					if( formControls[i].type == 'checkbox' ){
						$(formControls[i]).prop('checked', parseInt(controlVal));
					}
					else{
						$(formControls[i]).val(controlVal);
					}
				}
				else if( formControls[i].nodeName == 'TEXTAREA' ){
					$(formControls[i]).val(controlVal);
				}
				else if( formControls[i].nodeName == 'SELECT' ){
					
					$(formControls[i]).val(false);
					if(controlVal.length > 0){
						for (var z = 0; z < controlVal.length; z++){
							$(formControls[i]).find('option[value="' + controlVal[z] + '"]' ).attr('selected','selected');
						}
					}
					$(formControls[i]).trigger('chosen:updated');
				}
				
				if( formControls[i].nodeName == 'INPUT' || formControls[i].nodeName == 'SELECT' ){
					
					if( typeof( $(formControls[i]).data('tagsinput') ) != 'undefined'){
						
						var elementEvents = $(formControls[i]).data('events');
							
							$(formControls[i]).off();
								$(formControls[i]).tagsinput('removeAll');
								$(formControls[i]).tagsinput('add', controlVal);
							$(formControls[i]).on('itemAdded itemRemoved', save_operate );
					}
				}
			}
		},
		
		playSound : function(volume){
				
			var audio = $('.menu-beep');
			
			audio.get(0).volume = volume;
			audio.get(0).play();
		},
	}
	
	$('#tabs textarea').on('focusin',			select_focusin 	);
	$('#tabs textarea').on('focusout',			select_focusout );
	$('#tabs textarea').on('keyup keypress',	select_keyup );
	
	$('#tabs input, #tabs select, #tabs textarea').each(function(index){
		
		if( (this.nodeName == 'INPUT' || this.nodeName == 'SELECT') && $(this).data('taged') === true ){

			$(this).on('itemAdded itemRemoved', save_operate );
		}
		else{
			$(this).on('input change select', save_operate );
		}
	});

	$('.wrap #save').on('click', save );
	$('#tabs').on('tabsactivate tabscreate', scroll);
	$(window).on('scroll', scroll);
			
	function scroll(event, ui){

		var wrapBottom = $('.wrap').offset().top + $('.wrap').outerHeight(), viewportBottom = $(window).scrollTop() + $(window).height();

		if (viewportBottom <= wrapBottom) {
			$('#submit').addClass('sticky');
		}
		else {
			$('#submit').removeClass('sticky');
		};
	}
	function select_focusin(e){
		
		$(e.target).css('transition', 'all ' + select_css_transition + 'ms cubic-bezier(0.71, -0.01, 0.26, 0.98)').css('width', '100%');
		
		$(e.target).data( 'init_height', $(e.target).outerHeight() );
		$(e.target).css('height', $(e.target).data( 'init_height') );
		
		setTimeout(function(){
			$(e.target).css('height', $(e.target).prop('scrollHeight') + 2 );
		},select_css_transition/2);
		
		setTimeout(function(){
			$(e.target).css('transition', '' );
		},select_css_transition + select_css_transition/4);
	}
	function select_focusout(e){
		
		$(e.target).css('transition', 'all ' + select_css_transition + 'ms cubic-bezier(0.71, -0.01, 0.26, 0.98)').css( 'height', $(e.target).data( 'init_height') );
		
		setTimeout(function(){
			$(e.target).css('width', '');
		},select_css_transition/4);
		
		setTimeout(function(){
			$(e.target).css('transition',	'' );
			$(e.target).css('height',		'' );
		},select_css_transition + select_css_transition/4);
	}
 	function select_keyup(e){
		
		if ( $(e.target).outerHeight() <= $(e.target).prop('scrollHeight') ){
			
			$('#tabs textarea').css('transition', 'all 500ms cubic-bezier(0.71, -0.01, 0.26, 0.98)');
			$(e.target).css('height', $(e.target).prop('scrollHeight') + 2 );
			setTimeout(function(){
				$(e.target).css('transition', '' );
			},500);
		}
	}
	function save_operate(e){
		
		$('.wrap #save').val( $('#tabs').data('onchange_btn_text') ).css({
			'color'	: '#990000 !important',
			'border': '1px solid rgba(153, 0, 0, 0.42)',
		});
		$('.wrap #save').prop('disabled', false);
	}
	function save(e){
		
		$('.wrap #save').prop('disabled', true);
		$('.wrap #save').val( $('#tabs').data('StatusBetweenRequests') ).css({
			'color'			: '',
			'border'		: '',
			'background'	: '',
			'text-shadow'	: '',
		});

		$('body').prepend('<div id="spinner_saving"><div id="spinner_saving_inner"></div></div>');
		$('body').addClass('setting-saving');
		$('body #spinner_saving').animate({
			opacity: 1,
			}, 150, "swing", function(){
				
				setTimeout(function(){
					$('body #spinner_saving_inner').animate({
						opacity: 1,
					}, 200, "swing", function(){
						
						do_ajax(e);
					});
				}, 500);
		});
	}
	function do_ajax(e){
		
		var data		= $('#tabs').data();
		var settings	= $('#tabs input:not(.chosen-search-input), #tabs select, #tabs textarea').not('hidden').filter(function(index) {
			return $(this).parents('.bootstrap-tagsinput').length === 0;
		});
		var preset		= data.setting_preset;
		var setting_arr = {};
		
		for( var i = 0; i < settings.length; i++ ){
			
			var save_value;
			
			if( settings[i].type == 'checkbox' ){
				if( typeof($(settings[i]).data('save-val')) != 'undefined' ){
					save_value = $(settings[i]).data('save-val');
				}
				else{
					(settings[i].checked) ? save_value = 1 : save_value = 0;
				}
			}
			else{
				if( typeof($(settings[i]).data('save-val')) != 'undefined' ){
					save_value = $(settings[i]).data('save-val');
				}
				else if( settings[i].type == 'select-multiple' ){
					
					var val = $(settings[i]).val();
					
					if(val === null){
						save_value = -1;
					}
					else if(val.length === 0){
						save_value = -1;
					}
					else{
						save_value = $(settings[i]).val();
					}
				}
				else{
					save_value = $(settings[i]).val();
				}
			}
			
			setting_arr[ settings[i].name ] = save_value;
		}
		
		xhr = $.ajax({
			type: 'POST',
			url:  data.ajaxurl,
			data: {
				action			: data.action,
				nonce			: data.setting_form_nonce,
				data			:{
					AJAX_Marker	: recombeeRe_vars.AJAX_Marker,
					menu_type	: data.menu_type,
					preset		: preset,
					settings	: setting_arr,
				},
			},
			beforeSend: function() {
				
				if( xhr != null ) {
					xhr.abort();
					xhr = null;
				}
			},
			success: function(server) {
				
				$('body #spinner_saving_inner').animate({
					opacity: 0,
					}, 250, "swing", function(){
						
						setTimeout(function(){
							$('body').removeClass('setting-saving');
							$('body #spinner_saving').animate({
								opacity: 0,
							}, 100, "swing", function(){
								
								$.fn.TabsPluginFrameWork('updateControls', server.data.new_preset);
								
								$('body #spinner_saving').remove();
								if( typeof(server.data.extra_data) != 'undefined' && server.data.extra_data.length > 0 ){
									var extra_data = server.data.extra_data;
								}
								else{
									var extra_data = false;
								}
								$( '#tabs' ).triggerHandler( 'presetSaved', [ server.data.new_preset, extra_data] );
								
							});
						}, 500);
				});
				if(server.data.code === 200 ){
					$('.wrap #save').val( server.data.btn_text ).css({
						'background'	: '',
						'color'			: '',
						'text-shadow'	: '',
						'border'		: '1px solid green',
					});;
				}
				if(server.data.code === 400 ){
					$('.wrap #save').val( server.data.btn_text ).prop('disabled', false).css({
						'background'	: '#ff4545',
						'color'			: 'white',
						'text-shadow'	: 'none',
						'border'		: 'none',
					});
				}
				if(server.data.code === 403 ){
					
					$('.wrap #save').off( 'click' );
					$('.wrap #save').val( server.data.btn_text ).prop('disabled', false).css({
						'background'	: '#ff974b',
						'color'			: 'white',
						'text-shadow'	: 'none',
						'border'		: 'none',
					}).on( 'click', function(){
						location.reload();
					});
				}
			},
			error: function(MLHttpRequest, textStatus, errorThrown){
				
				if ( xhr.status === 0 && xhr.statusText == 'abort' ){
					return;
				}
				else{
					
					$('.wrap #save').off( 'click' );
					$('.wrap #save').val( $('#tabs').data('AJAX_error_btn_text') ).prop('disabled', false).css({
						'background'	: '#d86969',
						'color'			: 'rgb(255, 255, 255)',
						'text-shadow'	: 'none',
						'border'		: '1px solid #e8e8e8',
					}).on( 'click', function(){
						location.reload();
					});
					
					$('body #spinner_saving_inner').animate({
						opacity: 0,
						}, 250, "swing", function(){
							
							setTimeout(function(){
								$('body #spinner_saving').animate({
									opacity: 0,
								}, 100, "swing", function(){
									
									$('body #spinner_saving').remove();
								});
							}, 500);
					});
				}
			}
		});
	}
});