jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	var post_data = {
		'AJAX_Marker'	: recombeeRe_vars.DetailView.AJAX_Marker,
		'productID' 	: recombeeRe_vars.DetailView.productID,
	};
	
	Recombee = {
		viewStart: new Date(),
		post_data: {
			'AJAX_Marker'	: recombeeRe_vars.DetailView.AJAX_Marker,
			'productID' 	: recombeeRe_vars.DetailView.productID,
		},
		ajaxCall : function(action, nonce, post_data){
			
			$.ajax({
				type: 'POST',
				url:  recombeeRe_vars.DetailView.ajaxUrl,
				data: {
					action	: action,
					nonce	: nonce,
					data	: post_data,
				},
			});
		},
	}
	
	Recombee.ajaxCall(recombeeRe_vars.DetailView.action, recombeeRe_vars.DetailView.nonce, Recombee.post_data);
	
	$(window).on("beforeunload", function(event) {

		var post_data = Object.assign(
			Recombee.post_data, {
				'duration': Math.round( (new Date() - Recombee.viewStart)/1000 ),
			});
		
		Recombee.ajaxCall(recombeeRe_vars.DetailView.action, recombeeRe_vars.DetailView.nonce, post_data);
	});
});