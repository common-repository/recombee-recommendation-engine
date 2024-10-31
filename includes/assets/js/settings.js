jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	
	Recombee = {
		ajaxProcess			: null,
		inTheSequence		: false,
		sequenceStep		: 0,
		dbConnectTrigger	: $('button#db_connection'),
		dbToggleAll			: $('button#toggle_all_prod_prop, button#toggle_all_cust_prop'),
		dbSyncProps			: $('select#db_product_prop_set, select#db_customer_prop_set'),
		dbSyncTriggers		: $('button#db_sync_wc_products, button#db_sync_wc_customers, button#db_sync_wc_interactions, button#db_sync_product_prop, button#db_sync_customer_prop, select#db_product_prop_set, select#db_customer_prop_set, button#db_reset'),
		apiIdentifier		: $('.wrap #tabs input[name="api_identifier"]'),
		apiSecretToken		: $('.wrap #tabs input[name="api_secret_token"]'),
		serviceFrame 		: function(e, h, w){
			
			e.preventDefault();
			
			var recombee_service_frame = $( "#recombee_modal" );
			var recombee_service_frame_dialog_arg	= {
				title		: recombeeRe_vars.dialogTitle,
				height		: h,
				width		: w,
				autoOpen	: false,
				modal		: true,
				dialogClass	: "recombee-iframe",
				position	: { my: "center center", at: "center center", of: window },
				show		: {
					effect: "fade",
					direction: 'in',
					duration: 300
				},
				hide		: {
					effect: "fade",
					direction: 'out',
					duration: 300
				},
			}
			recombee_service_frame.dialog( recombee_service_frame_dialog_arg );
			recombee_service_frame.dialog( "open" );
			
			if( recombee_service_frame.children('iframe').length === 0){
				recombee_service_frame.addClass('loader');
				recombee_service_frame.append('<iframe src="' + recombeeRe_vars.recombeeUrl + '" frameborder="no" style="width: 100%; height: 100%; border: 0; display: none;"></iframe>');
			}
			
			$(recombee_service_frame.children("iframe").get(0)).load(function(){
			
				$(this).css('display', '');
				recombee_service_frame.removeClass('loader');
			});
		},
		
		dbConnectionOperate : function(){
			
			Recombee
			.dbConnectTrigger
			.removeAttr('data-code')
			.text(recombeeRe_vars.dbConnectInit)
			.prop('disabled', true)
			.off('click', this.dbConnectionAction);
		},
		
		dbConnectionAction : function(e){
			
			var action		= $(this).data('action');
			var nonce		= $(this).data('nonce');
			var post_data	= {
				'AJAX_Marker'		: recombeeRe_vars.AJAX_Marker,
				api_identifier		: e.data.api_identifier,
				api_secret_token	: e.data.api_secret_token
			};
			if( $(this).next('label').length === 0 ){
				$(this).after('<label style="position:relative;" class="ajax-process"></label>');	
			}
			Recombee.ajaxCall(e, action, nonce, post_data, Recombee.dbConnected, Recombee.dbConnectErr, false);
		},
		
		dbConnected : function(target, response){
			
			target.next('.ajax-process').remove();
			var connect_code = response.data.message.data.statusCode;
			
			target.attr('data-status-code', connect_code).text(recombeeRe_vars.dbConnects[connect_code]);

			if( connect_code !== parseInt(recombeeRe_vars.dbConnectedCode) ){
				Recombee.apiIdentifier.prop ('disabled', false);
				Recombee.apiSecretToken.prop('disabled', false);
				$.fn.TabsPluginFrameWork('showMessage', response.data.message.recombee, 'success');
			}
			else{
				Recombee.apiIdentifier.prop ('disabled', true);
				Recombee.apiSecretToken.prop('disabled', true);
				$.fn.TabsPluginFrameWork('showMessage', response.data.message.recombee, 'success');
			}
			Recombee.switchDbSyncTriggers(connect_code);
		},
		
		dbConnectErr : function(target, response){
			
			$.fn.TabsPluginFrameWork('showMessage', '<div>Code: ' + response.status + '</div><div>' + 'Message: ' + response.statusText + '</div>', 'error');
			target.next('.ajax-process').remove();
			Recombee.switchDbSyncTriggers(recombeeRe_vars.dbDisconnectsCode);
		},
		
		ajaxCall : function(e, action, nonce, post_data, onSuccess, onError, multiple){
			
			Recombee.ajaxProcess = $.ajax({
				type: 'POST',
				url:  recombeeRe_vars.ajaxUrl,
				data: {
					action	: action,
					nonce	: nonce,
					data	: post_data,
				},
				beforeSend: function() {
					
					if( Recombee.ajaxProcess != null && !multiple ) {
						Recombee.ajaxProcess.abort();
						Recombee.ajaxProcess = null;
					}
				},
				success: function(server){
					
					$(Recombee).one( 'countersAnimated', function(){
						if(server.data.message.errors){
							Recombee.consolLog( server.data.message.errors );
						}
						
						try{
							
							/* continue sync loop */
							if(server.data.message.data.statusCode === 201){
								Recombee.ajaxCall( e, action, nonce, post_data, onSuccess, onError, false );
								return;
							}
							/* exit sync loop */
							onSuccess($(e.target), server);
						}
						catch (err) {
							$.fn.TabsPluginFrameWork('showMessage', err, 'error');
						}
					});
					Recombee.updateCounters( $(e.target).parent().find('.tip' ), server.data.message.data.itemsPassed, server.data.message.data.loopErrors );
				},
				error: function(MLHttpRequest, textStatus, errorThrown){
					
					if(MLHttpRequest.responseText){
						try {
							Recombee.consolLog( JSON.parse(MLHttpRequest.responseText) );
						} catch (e) {
							Recombee.consolLog( MLHttpRequest.responseText );
						}
					}
					if ( Recombee.ajaxProcess.status === 0 && Recombee.ajaxProcess.statusText == 'abort' ){
						return;
					}
					else{
						onError($(e.target), Recombee.ajaxProcess );
					}
				}
			});
		},
		
		updateCounters : function(operator, items, errors){
			
			if(typeof(items) == 'undefined' || typeof(errors) == 'undefined'){
				$(Recombee).trigger( 'countersAnimated' );
				return;
			}
			
			if( operator.find('.items').text() != items && operator.find('.errors').text() != errors ){
				var counters = operator.find('.items').add(operator.find('.errors'));
			}
			else if( operator.find('.items').text() != items || items === 0 ){
				var counters = operator.find('.items');
			}
			else if( operator.find('.errors').text() != errors ){
				var counters = operator.find('.errors');
			}
			if( typeof(counters) != 'undefined' ){

				counters.animate({
					opacity: 0.3,
				}, 200, 'easeOutBounce', function(){
					operator.find('.items').text( items );
					operator.find('.errors').text( errors );
					
					Recombee.isSyncRequired(operator.find('.sync-progress'));
					$(this).animate({opacity: 1}, 200, 'easeOutBounce', function(){
						$(Recombee).trigger( 'countersAnimated' );
					});
					
					$.fn.TabsPluginFrameWork('playSound', 0.01);
				});
			}
		},
		
		isSyncRequired : function(operator){
			
			if( operator.find('.items').text() != operator.find('.total').text() ){
				operator.addClass('sync-required');
			}
			else{
				operator.removeClass('sync-required');
			}
		},					
					
		presetSaved : function (settings, extra_data){
			
			if(extra_data != false){
				for (var i = 0; i < extra_data.length; i++){
					if(extra_data[i].statusCode === 500){
						$.fn.TabsPluginFrameWork('showMessage', extra_data[i].recombee, 'error');
					}
					else{
						$.fn.TabsPluginFrameWork('showMessage', extra_data[i].recombee, 'success');
					}
				}
			}
			if(typeof(settings) != 'undefined'){
				
				Recombee.dbSyncTriggers.filter('button#db_sync_product_prop').parent().find('.total').text( settings.db_product_prop_set.length );
				Recombee.dbSyncTriggers.filter('button#db_sync_customer_prop').parent().find('.total').text( settings.db_customer_prop_set.length );
				
				Recombee.isSyncRequired( Recombee.dbSyncTriggers.filter('button#db_sync_product_prop').parent().find('.sync-progress') );
				Recombee.isSyncRequired( Recombee.dbSyncTriggers.filter('button#db_sync_customer_prop').parent().find('.sync-progress') );
				
				var connect_code = settings.db_connection_code;
				
				this
				.dbConnectTrigger
				.attr('data-status-code', connect_code)
				.text(recombeeRe_vars.dbConnects[connect_code])
				.prop('disabled', false)
				.off('click', this.dbConnectionAction)
				.on('click', {
					api_identifier	: Recombee.apiIdentifier.val(),
					api_secret_token: Recombee.apiSecretToken.val(),
				}, this.dbConnectionAction);
					
				if(connect_code === parseInt(recombeeRe_vars.dbConnectedCode) ){
					Recombee.apiIdentifier.prop ('disabled', true);
					Recombee.apiSecretToken.prop('disabled', true);
					$('#recombee-lost-coonect-notice button.notice-dismiss').trigger('click');
				}
				this.switchDbSyncTriggers(connect_code);
			}
		},
		
		switchDbSyncTriggers : function(connect_code){
			
			if(recombeeRe_vars.inviteInitSync){
				Recombee.apiIdentifier.removeClass('setup-credentials');
				Recombee.apiSecretToken.removeClass('setup-credentials');
				Recombee.dbSyncProps.removeClass('setup-prop').next('.chosen').removeClass('setup-prop');
			}
			
			if(connect_code === parseInt(recombeeRe_vars.dbConnectedCode) ){
				this.dbSyncTriggers.filter(':not(#db_product_prop_set):not(#db_customer_prop_set)').each(function(index, element){
					if( $(element).data('syncInProgress') === false ){
						$(element).prop('disabled', false);
					}
				});
			}
			else{
				this.dbSyncTriggers.filter(':not(#db_product_prop_set):not(#db_customer_prop_set)').each(function(index, element){
					$(element).prop('disabled', true);
				});
			}
			
			this.dbSyncProps.trigger('chosen:updated');
			
			if(connect_code === parseInt(recombeeRe_vars.dbConnectedCode) && recombeeRe_vars.inviteInitSync ){
				recombeeRe_vars.inviteInitSync = false;
				
				setTimeout(function(){
					var init_sync_start = confirm(recombeeRe_vars.inviteInitSyncText.split('|')[0] + '\n\n' + recombeeRe_vars.inviteInitSyncText.split('|')[1]);
					if(init_sync_start){
						Recombee.inTheSequence = true;
						Recombee.initAutoSyncStart();
					}
				}, 300)
			}
		},
		
		initAutoSyncStart: function(e){
			
			$('#tabs').tabs('option', 'active', 1 );
			$(Recombee).on( 'syncCompleted', Recombee.initAutoSyncStartContinue);
			
			var handler = this.dbSyncTriggers.filter(':not(#db_reset):not(#db_product_prop_set):not(#db_customer_prop_set)').first();
			$('html, body').animate({
				scrollTop: handler.offset().top - 40
			},
			{
			duration: 200,
			complete: function() {},
			}).promise().done(function(){
				handler.trigger('click');
				Recombee.sequenceStep++;
			});
		},
		
		initAutoSyncStartContinue: function(e){
			
			var next_object = Recombee.dbSyncTriggers.filter(':not(#db_reset):not(#db_product_prop_set):not(#db_customer_prop_set)').get(Recombee.sequenceStep);
			
			if( typeof(next_object) != 'undefined' && Recombee.inTheSequence === true ){
				
				$('html, body').animate({
					scrollTop: $(next_object).offset().top - 40
				},
				{
				duration:200,
				complete: function(){},
				}).promise().done(function(){
					$(next_object).trigger('click');
					Recombee.sequenceStep++;
				});
			}
			else{
				Recombee.initAutoSyncEnd();
			}
		},
		
		initAutoSyncEnd: function(e){
			
			Recombee.inTheSequence = false;
			Recombee.sequenceStep = 0;
			$(Recombee).off( 'syncCompleted', Recombee.initAutoSyncStartContinue);
		},
		
		dBSyncActions : function(e){
			
			if( $(e.target).attr('id') == 'db_reset' ){
				if( $(e.target).hasClass('inactive') ){
					var reset = confirm(recombeeRe_vars.dbResetPrompt);
					if(reset){
						$(e.target).removeClass('inactive').css({
							'color': 'red',
							'background': '#ffd3d3',
							'border': '1px solid #ff0000',
						}).focus();
					}
					return false;
				}
				else{
					$(e.target).css({
						'border' : '1px solid #ff9393',
						'opacity' : '.6',
					});
				}
			}
			if( $(e.target).attr('id') == 'db_sync_wc_interactions' ){
				var reset_db = Recombee.dbSyncTriggers.filter('#db_reset');
				if( reset_db.data('overcome') < 4 ){
					alert(recombeeRe_vars.dbSyncInteractionsAlert);
					return false;
				}
			}
			
			var action		= $(this).data('action');
			var nonce		= $(this).data('nonce');
			var post_data	= {
				'AJAX_Marker'	: recombeeRe_vars.AJAX_Marker,
				'actionTime'	: Date.now(),
			};
			
			for (var i = 0; i < Recombee.dbSyncTriggers.length; i++) {
				$(Recombee.dbSyncTriggers[i]).add(Recombee.dbToggleAll).prop('disabled', true).trigger('chosen:updated').data('syncInProgress', true);
				( $(Recombee.dbSyncTriggers[i]).attr('id') !== $(this).attr('id') ) ? $(Recombee.dbSyncTriggers[i]).addClass('proccess') : '';
			}
			
			if( $(this).next('label').length === 0 ){
				$(this).after('<label style="position:relative;" class="ajax-process"></label>');	
			}
			var items		= parseInt($(e.target).parent().find('.tip .items' ).text());
			var itemsTotal	= parseInt($(e.target).parent().find('.tip .total' ).text());
			
			if(items >= itemsTotal){
				$(Recombee).one( 'countersAnimated', function(){
					Recombee.ajaxCall(e, action, nonce, post_data, Recombee.syncComplete, Recombee.syncErr, true);
				});
				Recombee.updateCounters( $(e.target).parent().find('.tip' ), 0, 0 );
			}
			else{
				Recombee.ajaxCall(e, action, nonce, post_data, Recombee.syncComplete, Recombee.syncErr, true);
			}
		},
		
		syncComplete : function(target, response){
			
			if( target.attr('id') == 'db_reset' ){
				target.addClass('inactive').css({
					'color'		: '',
					'background': '',
					'border'	: '',
					'opacity'	: '',
				}).data('overcome', 0);
				
				Recombee.dbSyncTriggers.filter('button#db_sync_customer_prop').parent().find('.total').text( response.data.message.data.new_preset.db_customer_prop_set.length );
				Recombee.dbSyncTriggers.filter('button#db_sync_product_prop').parent().find('.total').text( response.data.message.data.new_preset.db_product_prop_set.length );
				
				Recombee.dbSyncTriggers.each(function(index, element){
	
					Recombee.updateCounters($(element).parent().find('.tip' ), 0, 0);
				});
				if(typeof(response.data.message.data.new_preset) != 'undefined'){
					
					var new_preset = response.data.message.data.new_preset;
					
					for (var key in new_preset){
						if( typeof(new_preset[key]) == 'string' ){
							new_preset[key] = new_preset[key].replace(/\\"/g, '"');
							new_preset[key] = new_preset[key].replace(/\\'/g, '\'');
						}
					}
					$.fn.TabsPluginFrameWork('updateControls', new_preset);
				}
			}

			target.next('.ajax-process').remove();
			Recombee.dbSyncTriggers.add(Recombee.dbToggleAll).each(function(index, element){
				$(element).prop('disabled', false).removeClass('proccess').trigger('chosen:updated').data('syncInProgress', false);
			});

			if( response.data.message.data.statusCode !== 200 && response.data.message.data.statusCode !== 201 ){
				$.fn.TabsPluginFrameWork('showMessage', response.data.message.recombee, 'error');
			}
			else{
				target.parent().find('.action').text( response.data.message.data.actionTime ).addClass('green');
				if( target.attr('id') == 'db_reset' ){
					Recombee.dbSyncTriggers.each(function(index, element){
						$(element).not('#db_reset').parent().find('.action').text(recombeeRe_vars.dbResetTipText + ' ' + response.data.message.data.actionTime).removeClass('green').addClass('transparent');
					});
				}
				if( target.attr('id') == 'db_sync_wc_products' || target.attr('id') == 'db_sync_wc_customers' || target.attr('id') == 'db_sync_product_prop' || target.attr('id') == 'db_sync_customer_prop' ){
					var reset_db = Recombee.dbSyncTriggers.filter('#db_reset');
					reset_db.data('overcome', reset_db.data('overcome') + 1);
				}

				$.fn.TabsPluginFrameWork('showMessage', response.data.message.recombee, 'success');
			}

			$(Recombee).trigger( 'syncCompleted' );
		},
		
		syncErr : function(target, response){
			
			$(target).prop('disabled', false);
			$.fn.TabsPluginFrameWork('showMessage', '<div>Code: ' + response.status + '</div><div>' + 'Message: ' + response.statusText + '</div>', 'error');
			target.next('.ajax-process').remove();
			
			if( target.attr('id') == 'db_reset' ){
				target.addClass('inactive').css({
					'color'		: '',
					'background': '',
					'border'	: '',
					'opacity'	: '',
				});
			}
			
			Recombee.dbSyncTriggers.add(Recombee.dbToggleAll).each(function(index, element){
				$(element).prop('disabled', false).removeClass('proccess').trigger('chosen:updated').data('syncInProgress', false);
			});

			$(Recombee).trigger( 'syncCompleted' );	
		},
		
		consolLog : function(data){
			
			for (var i = 0; i < data.length; i++) {
				console.log(data[i][0]);
			}
		},
		
		toggleProperties : function(e){
			
			var delay	 = 500;
			var dropbox  = $(this).parents('.control').find('select');
			var selected = dropbox.find('option:not(:disabled):selected');
			
			if( selected.length === 0 ){
				
				Recombee.doSelect(dropbox);
				dropbox.trigger('change');
			}
			else{
				selected.removeAttr('selected').trigger('chosen:updated');
				selected.trigger('change');
			}
		},
		
		doSelect : function(dropbox, i = 0){
			
			var options = dropbox.find('option:not(:disabled)');
			$(options[i]).prop('selected', true).trigger('chosen:updated');
			
			if(i < options.length){
				i++;
				setTimeout(function(){
					Recombee.doSelect(dropbox, i);
				}, 30);
			}
		},
	};
	
	$(window).on("load", function(event){
		
		$( '.recombee_service_frame_trigger' ).on('click', function(e){
			
			var h = $(window).height()	- $('#wpadminbar').outerHeight();
			var w = $(window).width()	- $('#adminmenuwrap').outerWidth();
			
			Recombee.serviceFrame(e, h, w);
		});
		
		Recombee.apiIdentifier.on ('input', Recombee.dbConnectionOperate);
		Recombee.apiSecretToken.on('input', Recombee.dbConnectionOperate);
		Recombee.dbToggleAll.on	  ('click', Recombee.toggleProperties);
		
		Recombee.dbSyncTriggers.each(function(index, element){
			if( $(element).attr('id') == 'db_reset' ){
				$(element).on('click', $(this).not(':disabled.inactive'), Recombee.dBSyncActions);
			}
			else{
				$(element).on('click', $(this).not(':disabled'), Recombee.dBSyncActions);
			}
			$(element).data('syncInProgress', false);
		});
		
		if( !recombeeRe_vars.dbConnectLost && !Recombee.dbConnectTrigger.prop('disabled') ){
			Recombee.dbConnectTrigger.on('click', {
				api_identifier	: Recombee.apiIdentifier.val(),
				api_secret_token: Recombee.apiSecretToken.val(),
			}, Recombee.dbConnectionAction);
		}
				
		$('#tabs').on('presetSaved', function(e, settings, extra_data){
			Recombee.presetSaved(settings, extra_data);
		});
		if(recombeeRe_vars.inviteInitSync){
			alert(recombeeRe_vars.inviteInitSyncProp.split('|')[0] + '\n\n' + recombeeRe_vars.inviteInitSyncProp.split('|')[1]);
			Recombee.dbToggleAll.trigger('click');
		}
	});
});