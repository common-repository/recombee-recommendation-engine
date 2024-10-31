(function () {

	var Recombee;
	var storage;
	
	Recombee = {
		getStorageData: function(name){
			
			return JSON.parse(localStorage.getItem('Recombee'));
		},
		setStorageData: function(name, value ){
			
			localStorage.setItem(name, JSON.stringify(value));
		},
		setCookie: function (name, value, options) {
			
			options = options || {};
			var expires = options.expires;

			if (typeof expires == "number" && expires) {
				var d = new Date();
				d.setTime(d.getTime() + expires * 1000);
				expires = options.expires = d;
				options.expires = expires.toUTCString()
			}

			value = encodeURIComponent(value);
			var updatedCookie = name + "=" + value;

			for (var propName in options) {
				updatedCookie += "; " + propName;
				var propValue = options[propName];
				if (propValue !== true) {
					updatedCookie += "=" + propValue;
				}
			}
			document.cookie = updatedCookie;
		},
		deleteCookie: function (name) {
			this.setCookie(name, "", {
				'path':'/',
				expires: -1000,
			})
		}
	}

	storage = Recombee.getStorageData('Recombee');
	
	if( typeof(recombeeRe_vars.DetailView) != 'undefined' ){
		
		if(storage === null){
			storage = {'RAUID' : recombeeRe_vars.DetailView.RAUID};
		}
		else if( typeof(storage.RAUID) == 'undefined'){
			storage['RAUID'] = recombeeRe_vars.DetailView.RAUID;
		}
		Recombee.setStorageData('Recombee', storage);

		Recombee.setCookie(
			'RAUID',
			Recombee.getStorageData('Recombee').RAUID,
			{'expires':0, 'path':'/'}
		);
	}
	
})();