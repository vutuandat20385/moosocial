(function (root, factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD
		define(['jquery'], factory);
	} else if (typeof exports === 'object') {
		// Node, CommonJS-like
		module.exports = factory(require('jquery'));
	} else {
		// Browser globals (root is window)
		root.mooApp = factory(require('jquery'));
	}
}(this, function ($) {
	var remove = function()
	{
		$.ajax({
			type: 'POST',
			url: mooConfig.url.base + '/moo_apps/remove',
			success: function (data) {
				$('#mobile_suggest').remove();
			}
		});
	}

	var noThank = function()
	{
		$.ajax({
			type: 'POST',
			url: mooConfig.url.base + '/moo_apps/nothank',
			success: function (data) {
				$('#mobile_suggest').remove();
			}
		});
	}

	var initApp = function(domain)
	{
		$('body').on('click','a',function(event){
			if (ValidURL($(this).attr('href')))
			{
				if ($(this).attr('href').trim().search(location.origin) == -1)
				{
					if (domain != '')
					{
						if ($(this).attr('href').trim().search(domain) !== -1)
						{
							return;
						}
					}
					Android.openUrl($(this).attr('href').trim());
					return false;
				}
			}

			//hack email
			if ($(this).attr('href').trim().search("mailto:") == 0)
			{
				Android.openUrl($(this).attr('href').trim());
				return false;
			}
		});
	}

	var ValidURL = function (str) {
		var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
		return regexp.test(str);
	}

	var initWeb = function()
	{
		$('.moo_app_nothank').click(function(){
			noThank();
		});

		$('.moo_app_remove').click(function(){
			remove();
		});
	}

	return{
		initApp:initApp,
		initWeb: initWeb
	}
}));