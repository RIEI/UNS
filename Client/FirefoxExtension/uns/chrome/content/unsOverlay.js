window.addEventListener("load", function() {UNS.startup();}, false);
window.addEventListener("unload", function() {UNS.shutdown();}, false); 

var UNS = {
	// Initialize the extension
	startup: function()
	{
		alert("startup");
		worker = new Worker("chrome://uns/content/worker.js");
	}, 
	//Close the extension
	shutdown: function()
	{
		alert("shutdown");
	}, 
	//Test Script
	run: function()
	{
		var prefManager = Components.classes["@mozilla.org/preferences-service;1"].getService(Components.interfaces.nsIPrefBranch);
		var serverurl = prefManager.getCharPref("extensions.uns.serverurl");
		var clientid = prefManager.getCharPref("extensions.uns.clientid");
		var alertxml = serverurl + 'index.php?id=' + clientid + '&out=xml';
		//var alertxml = "http://www.techidiots.net/other/test.xml";
		UNS.loadXML(alertxml);
		UNS.openurl(alertxml);
	},
	loadXML: function(url)
	{
		alert(url);
		let request = new XMLHttpRequest();
		request.open("GET", url);
		request.ContentType = "application/xml"
		request.onload = UNS.ReadXML;
		request.send(null);
	}, 
	ReadXML: function()
	{	
		let xmlDoc = this.responseXML;
		alert("read XML");
        //Using documentElement Properties
        //Output company
        alert("XML Root Tag Name: " + xmlDoc.documentElement.tagName);
 
        //Using nodeValue and Attributes Properties
		//Here both the statement will return you the same result
		//Output 001
		alert("Node Value: " + xmlDoc.documentElement.childNodes[1].attributes[0].nodeValue);
	 
		alert("Node Value: " + xmlDoc.documentElement.childNodes[1].attributes.getNamedItem("id").nodeValue);
	 
		//Using getElementByTagName Properties
		//Here both the statement will return you the same result
		//Output 2000
		alert("getElementsByTagName: " + xmlDoc.getElementsByTagName("year")[0].attributes.getNamedItem("id").nodeValue);
	 
		//Using text Properties
		//Output John
		alert("Text Content for Employee Tag: " + xmlDoc.documentElement.childNodes[1].textContent);
	}, 
	//Open UNS Client Preferences
	openoptions: function()
	{
		window.openDialog('chrome://uns/content/options.xul',"unsOptions", "chrome,centerscreen")
	},
	//Open URL in current tab
	openurl: function(url)
	{
		content.wrappedJSObject.location = url;
		newTabBrowser = gBrowser.selectedBrowser;
		newTabBrowser.addEventListener("load", highlight, true);
	},
	//Get current tab URL
	geturl: function()
	{
		var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"].
		getService(Components.interfaces.nsIWindowMediator);
		var recentWindow = wm.getMostRecentWindow("navigator:browser");
		return recentWindow ? recentWindow.content.document.location : null;
	}
}
