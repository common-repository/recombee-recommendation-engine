jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	
	Recombee = {
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
		
	$(window).on("load", function(event) {
		var post_data = {
			'AJAX_Marker'	: recombeeRe_vars.DetailView.AJAX_Marker,
			'productID' 	: recombeeRe_vars.DetailView.productID,
		};
		
		$('body').on('click', 'li.product-type-external', function(){
			Recombee.ajaxCall(recombeeRe_vars.DetailView.action, recombeeRe_vars.DetailView.nonce, post_data);
		});
	});
});