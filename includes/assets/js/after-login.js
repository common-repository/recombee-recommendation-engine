jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	
	Recombee = {
		ajaxCall : function(action, nonce, post_data){
			
			$.ajax({
				type: 'POST',
				url:  recombeeRe_vars.AfterLogin.ajaxUrl,
				data: {
					action	: action,
					nonce	: nonce,
					data	: post_data,
				},
			});
		},
		setQuery : function(){
			
			var newQuery	= '';
			var query		= new URLSearchParams(window.location.search);
			
			if( query.has( recombeeRe_vars.AfterLogin.kill_param ) ){
				query.delete(recombeeRe_vars.AfterLogin.kill_param);
			}
			
			newQuery = query.toString();
			if( newQuery !== '' ){
				var newRelativePathQuery = window.location.pathname + '?' + query.toString();
			}
			else{
				var newRelativePathQuery = window.location.pathname;
			}
			
			history.pushState(null, '', newRelativePathQuery);
		}
	}
	
	Recombee.setQuery();
	
	$(window).on("load", function(event) {
		var post_data = {
			'AJAX_Marker'	: recombeeRe_vars.AfterLogin.AJAX_Marker,
			'productID' 	: recombeeRe_vars.AfterLogin.productID,
		};
	
		Recombee.ajaxCall(recombeeRe_vars.AfterLogin.action, recombeeRe_vars.AfterLogin.nonce, post_data);
	});
});

//history.pushState(null, null, 'http://javascript.ru/forum/misc')
