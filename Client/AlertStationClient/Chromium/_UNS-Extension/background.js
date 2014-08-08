	var alertxml = "http://your.uns.server/uns/index.php?id=5786bc3007879fa2e1d015c35c5b2478&out=xml";
	var i=1;
	var intrv=1;
	var refreshId = setInterval(function() {
		if(!(i%intrv)) {
			chrome.tabs.getSelected(null, function(tab) {
				if (tab.url != "chrome://extensions/")
					{
						LoadPage(alertxml,tab.id);
					}
			});
	  }
	  i++;
	}, 1000);

	
	function LoadPage(url,tabid){
		var xhReq = new XMLHttpRequest();
		xhReq.open("GET", url, true);
		xhReq.onreadystatechange = function () {
			if (xhReq.readyState == 4) 
			{
				if (xhReq.status == 200) 
				{
					xmlDoc=xhReq.responseXML;
					var unsurl = xmlDoc.getElementsByTagName("url")[0].childNodes[0].nodeValue;
					var unsrefresh = xmlDoc.getElementsByTagName("refresh")[0].childNodes[0].nodeValue;
					var unsemerg = xmlDoc.getElementsByTagName("emerg")[0].childNodes[0].nodeValue;
					chrome.tabs.update(tabid, {url: unsurl});
					intrv = unsrefresh;
				}
			}
		};
		xhReq.send();
	}