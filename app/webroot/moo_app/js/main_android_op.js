document.addEventListener("click",function(e) {
	if(e.target && e.target.nodeName == "A") {
		href = e.target.getAttribute("href").trim();
		if (href != '')
		{
			if (ValidURL(href))
			{
				
				if (href.search(location.origin) == -1)
				{
					if (mooConfig.url.domain != '')
					{
						if (href.search(mooConfig.url.domain) !== -1)
						{
							return;
						}
					}
					Android.openUrl(href);
					e.preventDefault();
					return false;
				}
			}
			
			if (href.search("mailto:") == 0)
			{
				Android.openUrl(href);
				return false;
			}
		}
	}
});

var ValidURL = function (str) {
	var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
	return regexp.test(str);
}