var yesScriptBrowserOverlay = {

	sites: [],
	panel: null,

	init: function() {
		yesScriptBrowserOverlay.panel = document.getElementById("yesscript-panel");
		//update the status...
		//when a document begins loading
		gBrowser.addProgressListener(yesScriptBrowserOverlay, Components.interfaces.nsIWebProgress.NOTIFY_STATE_DOCUMENT); 
		//when the active tab changes
		gBrowser.tabContainer.addEventListener("TabSelect", yesScriptBrowserOverlay.updateStatus, false);
		//when the pref changes
		yesScriptCommon.updateCallbacks.push(yesScriptBrowserOverlay.updateStatus);
		//right now
		yesScriptBrowserOverlay.updateStatus();
	},

	//nsIWebProgress stuff
	QueryInterface: function(aIID) {
		if (aIID.equals(Components.interfaces.nsIWebProgressListener) ||
		    aIID.equals(Components.interfaces.nsISupportsWeakReference) ||
		    aIID.equals(Components.interfaces.nsISupports))
			return this;
		throw Components.results.NS_NOINTERFACE; 
	},
	onLocationChange: function(progress, request, uri) {
		//if it's the current tab that changed, update the status
		if (uri && uri.spec == content.document.location.href) {
			yesScriptBrowserOverlay.updateStatus();
		}
	},
	onStateChange: function() {},
  onProgressChange: function() {},
	onStatusChange: function() {},
	onSecurityChange: function() {},
	onLinkIconAvailable: function() {},

  onPageLoad: function(aEvent) {
		if (aEvent.originalTarget == content.document) {
			yesScriptBrowserOverlay.updateStatus();
		}
  },

	updateStatus: function() {
		var blacklisted = yesScriptCommon.isBlacklisted(content.document.location.href) != null;
		var key = blacklisted ? "blacklisted" : "notBlacklisted";
		yesScriptBrowserOverlay.panel.setAttribute("src", blacklisted ? "chrome://yesscript/skin/black.png" : "chrome://yesscript/skin/ok.png");
		yesScriptBrowserOverlay.panel.setAttribute("tooltiptext", yesScriptCommon.strings.getFormattedString(key, [yesScriptCommon.getSiteString(content.document.location.href)]));
		yesScriptBrowserOverlay.panel.setAttribute("blacklisted", blacklisted);
	},

	toggle: function() {
		yesScriptCommon.blacklist(content.document.location.href, !(yesScriptBrowserOverlay.panel.getAttribute("blacklisted") == "true"));
	}

}

window.addEventListener("load", yesScriptBrowserOverlay.init, false);
