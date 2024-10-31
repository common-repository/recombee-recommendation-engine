jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	
	Recombee = {
		parameters			: $('.recombee-widget-parameters input, .recombee-widget-parameters select'),
		extra_options		: $('.recombee-widget-parameters .recombee-expert-parameters'),
		booster_wrapper		: $('.recombee-widget-parameters #booster_statement_wrapper'),		
		booster_ranger		: $('.recombee-widget-parameters .booster_slider_wrapper'),		
		suppress_posts		: $('.recombee-widget-parameters .suppress-posts'),
		select2_conf		: function(element){
			
			var placeholder;
			var suppressSubject = $(element).parent().find('[data-parameter-name="suppressSubject"]');
			
			if( suppressSubject.prop('checked') ){
				placeholder = $(element).parent().find('[data-parameter-name="suppressSubject"]').data('suppressPlaceholders').terms;
			}
			else{
				placeholder = $(element).parent().find('[data-parameter-name="suppressSubject"]').data('suppressPlaceholders').posts;
			}
			
			return {
				containerCssClass	: 'recombee-suppress-posts-container',
				dropdownCssClass	: 'recombee-suppress-posts-dropdown',
				width				: '100%',
				multiple			: true,
				dropdownAutoWidth	: true,
				minimumInputLength	: 2,
				closeOnSelect		: false,
				amdLanguageBase		: './select2-i18n/',
				placeholder			: placeholder,
				ajax: {
					url		: $(element).parents('.recombee-widget-parameters').data('ajax').AJAX_url,
					type	: 'POST',
					delay	: 250,
					data	: function (params) {
						
						var data = $(this).parents('.recombee-widget-parameters').data('ajax');
						var suppressSubject = $(this).parent().find('#suppress-type-wrapper input').prop('checked');
						
						return {
							search			: params.term,
							action			: $(this).data('action'),
							nonce			: data.nonce,
							data			: {'AJAX_Marker' : data.data.AJAX_Marker},
							suppressSubject : suppressSubject,
						};
					},
					processResults: function (response, params) {
						return {
							results: response.data.items,
						};
					},
					error: function(MLHttpRequest, textStatus, errorThrown){
						
					},
					cache: true
				},
			}
		},
		
		adjustSuppressPosts : function(event){
			
			/* 
			*  Save current select into suppress Object Input
			*  and get prev selection from it if exists
			*/
			
			var suppressObjectTrigger	= $(this);
			var selectObject			= suppressObjectTrigger.parents('#suppress-block').find('select.suppress-posts');
			
			if( suppressObjectTrigger.prop('checked') ){
				
				for (var i = 0; i < selectObject.length; i++){
					
					var currentSelect = $(selectObject[i]).val();
					suppressObjectTrigger.data('suppressLastState').posts[ $(selectObject[i]).attr('id') ] = currentSelect;
					$(selectObject[i]).val( suppressObjectTrigger.data('suppressLastState').terms[$(selectObject[i]).attr('id')] ).select2( Recombee.select2_conf(selectObject[i]) );;
				}
			}
			else{
				
				for (var i = 0; i < selectObject.length; i++){
					
					var currentSelect = $(selectObject[i]).val();
					suppressObjectTrigger.data('suppressLastState').terms[ $(selectObject[i]).attr('id') ] = currentSelect;
					$(selectObject[i]).val( suppressObjectTrigger.data('suppressLastState').posts[$(selectObject[i]).attr('id')] ).select2( Recombee.select2_conf(selectObject[i]) );;
				}
			}
		},
		
		initSelect2 : function(target){
			$(target).select2( Recombee.select2_conf(target) );
		},
		
		restoreWidgetSelect2 : function(event){
			
			var select = $(this).find('select.suppress-posts');
			select.select2( Recombee.select2_conf(select) );
		},
		
		toggleContainer	: function(event){
			
			$(this).toggleClass('closed').next().animate({
				height: 'toggle',
				opacity: 'toggle'
				}, 400, function() {
					/* $(this).next().toggle('slide', { direction: 'left', duration: 400 }); */
			});
			
		},
		
		openHelpTab : function(event){
			$('#contextual-help-link').trigger('click');
			$('.contextual-help-tabs a[href="#tab-panel-recombee_help"]').trigger('click');
		},
		
		validateInputs : function(event){
			if( !$(this).parents('form').get(0).checkValidity() ){
				$(this).parents('.recombee-widget-parameters').find('input[type="submit"].recombee-submit-error').trigger('click');
				
			};
		},
		
		lockParameters : function(event){
			var enableThis = $(this).find(':selected').data('relevantParameters');
			var accrosThat = $(this).parents('.recombee-widget-parameters').find('input:not([type="hidden"]):not([type="submit"]):not([type="radio"]), textarea, select:not([data-parameter-name="interaction-type"])');
			
			for(var q=0; q<accrosThat.length; q++){
				
				if( enableThis.indexOf( $(accrosThat[q]).data('parameterName') ) != -1 ){
					$(accrosThat[q]).prop('disabled', false).prev('label').attr('disabled', false).parents('p').css('display', '');
				}
				else{
					$(accrosThat[q]).prop('disabled', true).prev('label').attr('disabled', true).parents('p');
				};
			}
		},
		
		listenSave : function(event){
			
			if($(this).parents('form').find('.expert-options-toggler').hasClass('closed')){
				var extraClosed = true;
			}
			else{
				var extraClosed = false;
			}
			
			if($(this).parents('form').find('.suppress-logic-toggler').hasClass('closed')){
				var suppressClosed = true;
			}
			else{
				var suppressClosed = false;
			}
			
			
			
			$('.widget-liquid-right').one('DOMNodeInserted', '.widget', {'suppressClosed': suppressClosed, 'extraClosed' : extraClosed}, Recombee.restoreExtraState);
			$('.widget-liquid-right').one('DOMNodeInserted', '.widget', Recombee.restoreBoosterSlider);
			$('.widget-liquid-right').one('DOMNodeInserted', '.widget', Recombee.restoreWidgetSelect2);
		},
		
		restoreExtraState : function(event){

			if(!event.data.extraClosed){
				$(this).find('.expert-options-toggler').removeClass('closed').next().css('display', '');
			}
			else{
				$(this).find('.expert-options-toggler').addClass('closed').next().css('display', 'none');
			}
			
			if(!event.data.suppressClosed){
				$(this).find('.suppress-logic-toggler').removeClass('closed').next().css('display', '');
			}
			else{
				$(this).find('.suppress-logic-toggler').addClass('closed').next().css('display', 'none');
			}
		},
		
		makeBoosterSlider : function(target){
			
			$(target).slider({
				value	: parseFloat($(target).find('input').val()),
				min		: 0.1,
				max		: 10,
				step	: 0.1,
				animate	: "fast",
				range	: "min",
				create	: function( event, ui ){
					$(event.target).find('.ui-slider-handle').text( $(target).slider( 'value' ) );
				},
				slide	: function( event, ui ) {
					$(ui.handle).text( ui.value );
					$(event.target).find('input').val( ui.value ).trigger('change');
				}
			});
		},
		
		restoreBoosterSlider : function(event){
			
			$.each($(event.currentTarget).find('.booster_slider_wrapper'), function( index, value ) {
				Recombee.makeBoosterSlider(value);
			});
		},
		
		listenBooster : function(event){
			if( $(event.target).val() == '' ){
				Recombee.booster_wrapper.hide('drop', { direction: 'up', duration: 400 })
			}
			else{
				Recombee.booster_wrapper.show('drop', { direction: 'down', duration: 400 })
			}
		},
	}
		
	$('body').on('click',	'.recombee-widget-parameters .suppress-logic-toggler',					Recombee.toggleContainer);
	$('body').on('click',	'.recombee-widget-parameters .expert-options-toggler',					Recombee.toggleContainer);
	$('body').on('click',	'.widget .recombee-help',												Recombee.openHelpTab);
	$('body').on('input',	'.recombee-widget-parameters input[type="number"]',						Recombee.validateInputs);
	$('body').on('input',	'.recombee-widget-parameters [data-parameter-name="interaction-type"]', Recombee.lockParameters);
	$('body').on('click',	'[id*=recombee_recommends_widget] [name="savewidget"]',					Recombee.listenSave );
	$('body').on('change',	'.recombee-widget-parameters [data-parameter-name="booster"]',			Recombee.listenBooster );
	$('body').on('click',	'.recombee-widget-parameters [data-parameter-name="suppressSubject"]',	Recombee.adjustSuppressPosts );
	
	$.each(Recombee.booster_ranger, function( index, value ) {
		Recombee.makeBoosterSlider(value);
	});
	$.each(Recombee.suppress_posts, function( index, value ) {
		Recombee.initSelect2(this);
	});

});