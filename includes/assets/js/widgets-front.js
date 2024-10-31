jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	
	Recombee = {
		ajaxProcess : null,
		ajaxed_widgets: $('.recombeeRe-ajaxed-widget'),
		
		consolLog : function(data, type){
			
			type = type || 'error';
			
			for (var i = 0; i < data.length; i++) {
				console[type]('Recombee: ' + data[i]);
			}
		},
		
		removeSpinners : function(widget_containers){
			
			for(var i = 0; i < widget_containers.length; i++){
				$(widget_containers[i]).find('.recombee-spinner').remove();
			}
		},
		
		insertWidgetContent : function(widget, content){
			
			var content_obj = $($.parseHTML(content));
			widget.animate({
				transform: 'scale(0)',
			}, 200, function(){
				$(this).replaceWith(content_obj);
			})
		},
		
		showWidgetContent : function(e){
			
			var target = $(e.target);
			var targetHeight = target.height();
			target.css({
				'display'	: 'none',
				'height'	: '0',
			}).find('.widget-title, section > *').css('display', 'none');
			
			target.animate({
				width: 'toggle',
			}, 300, function(){
				$(this).animate({
					height: targetHeight,
				}, 300, function(){
					
					var delay = 100;
					var items = $(this).find('.widget-title, section > *');
					
					for(var i = 0; i < items.length; i++){
						$(items[i]).delay(delay * i).show( 'fade', { direction: 'left' }, 500 );
					};
					setTimeout(function(){ target.css('height', ''); }, 500 + i * delay);
				});
			});
		},

		ajaxCall : function(action, nonce, post_data){
			
			Recombee.ajaxProcess = $.ajax({
				type: 'POST',
				url:  recombee_do_ajax_widgets.ajaxUrl,
				data: {
					action	: action,
					nonce	: nonce,
					data	: post_data,
				},
				success: function(server){
					
					if( server.data.statusCode === 200 ){
						
						if( server.data.widgets !== null){
							for(var widget_id in server.data.widgets){
								var target_widget	= Recombee.ajaxed_widgets.filter('[data-rawids="' + widget_id + '"]');
								var new_content		= server.data.widgets[widget_id]['html'];
								Recombee.insertWidgetContent(target_widget, new_content);
							}
						}
						else{
							Recombee.removeSpinners(Recombee.ajaxed_widgets);
							Recombee.consolLog( ['Server responded with empty widgets IDs'], 'log' );
						}
					}
					else{
						if( server.data.statusCode === 400 && server.data.message !== '' ){
							Recombee.consolLog( [server.data.message], 'log' );
						}
						Recombee.ajaxed_widgets.remove();
					}
				},
				error: function(MLHttpRequest, textStatus, errorThrown){
					
					if ( Recombee.ajaxProcess.status === 0 && Recombee.ajaxProcess.statusText == 'abort' ){
						return;
					}					
					if(MLHttpRequest.responseText){
						Recombee.consolLog( JSON.parse(MLHttpRequest.responseText) );
					}
				}
			});
		},
	}
	
	if(typeof('recombee_do_ajax_widgets') == 'undefined'){
		return;
	}
	
	$('body').on('DOMNodeInserted', '.recombee-recommends-widget', Recombee.showWidgetContent);
	
	if( Recombee.ajaxed_widgets.length > 0 ){
		
		var post_data = {
			'AJAX_Marker'	: recombee_do_ajax_widgets.AJAX_Marker,
			'do_widgets'	: recombee_do_ajax_widgets.do_widgets,
		};
		
		Recombee.ajaxCall(recombee_do_ajax_widgets.action, recombee_do_ajax_widgets.nonce, post_data);
	}
});