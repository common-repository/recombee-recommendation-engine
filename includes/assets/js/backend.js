jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	
	Recombee = {
		dialog_object: $('#recombee_modal_warning'),
		dialog_arg		: {
			autoOpen	: false,
			modal		: true,
			dialogClass	: 'recombee-dialog-warning',
			appendTo	: 'body',
			show		: {
				effect: "drop",
				direction: 'up',
				duration: 300
			},
			hide		: {
				effect: "drop",
				direction: 'down',
				duration: 200
			},
			beforeClose: function( event, ui ){
			
				var hide_duration = $(this).dialog('instance').options.hide.duration;
				$('#wpwrap').animate({
					'opacity' : '1'
				}, hide_duration, function(){
					$(this).css('opacity', '');
				});
				
			},
			open : function( event, ui ){
				var show_duration = Recombee.dialog_object.dialog('instance').options.show.duration;
				$('#wpwrap').animate({
					'opacity' : '0.6'
				}, show_duration/2);
			},
			close : function( event, ui ){
				
				var show_admin_modal = $( this ).data('show_admin_modal')
				var hide_duration = $(this).dialog('instance').options.hide.duration;
				
				if( typeof(show_admin_modal) == 'undefined' ){
					show_admin_modal = Date.now();
				}
				
				$( this ).dialog( 'destroy' );
				
				try{
					Recombee.update_warning_policy(show_admin_modal);				
				}
				catch (err) {
					console.log(err);
				}
			},
			position: {
				my: 'center center',
				at: 'center center', 
				of: window
			},
			maxHeight	: $(window).height() - 64,
			maxWidth	: 1300,
			height		: 'auto',
			width		: 900,
		},
		dialogOpen : function(dialogObject){
			
			dialogObject.dialog( Recombee.dialog_arg );
			dialogObject.dialog( 'option',{
				buttons		: [{
					text: $(this).get(0).dialog_object.data('settings').button_Text,
					click: function() {
						$( this ).data('show_admin_modal', 0);
						$( this ).dialog( "close" );
					},
				}],
			});
			dialogObject.dialog( 'widget' ).draggable( 'option',{
				'scroll'		: false,
				'containment'	: 'window',
			});
			dialogObject.dialog( 'open' );
			$('.custom-scope .ui-widget-overlay').css({
				'background': '#000000',
			});
		},
		update_warning_policy(show_admin_modal_value){
			
			var dataset = Recombee.dialog_object.data('settings');
			
			$.ajax({
				type: 'POST',
				url:  dataset.ajaxUrl,
				data: {
					action			: dataset.action,
					nonce			: dataset.nonce,
					data			:{
						AJAX_Marker				: dataset.AJAX_Marker,
						show_admin_modal_value	: show_admin_modal_value,
					},
				},
				success: function(server) {
					
				},
			});
		},
	}
	
	$(window).on("load", function(event){
		
		if( Recombee.dialog_object.length > 0){
			Recombee.dialogOpen(Recombee.dialog_object);
		}
	});
});