function ScrollyFox() {
	
	var thisClass = this;
	
	this.IntervalHandle = -1;
	this.Interval = 50;
	this.Step = 1;
	this.PageEndAction = "reverse";
	this.PageEndCount = 0;
	this.PageEndPause = 100;
	
	var prefManager = Components.classes["@mozilla.org/preferences-service;1"].getService(Components.interfaces.nsIPrefBranch);
	
	var blnAutoRun = prefManager.getBoolPref("extensions.scrollyfox.autorun");
	if (blnAutoRun)
	{
		setTimeout(function() {thisClass.Start();}, 250);
	};
	
	this.click = function(e) {
		if (e.button == 0)	//left-click: toggle the scrolling
		{
			this.scrollingStartStop();
		}
		else if (e.button == 2)	//right-click: open options panel
		{
			this.showPrefs();
		}
		else if (e.button == 1)	//middle-click: reverse scroll direction (if 'reverse' mode is enabled)
		{
			if (this.PageEndAction == "reverse")
			{
				this.Step = (this.Step * -1);
			}
		}
	};
	
	this.refreshPrefs = function() {
		var intOldInterval = this.Interval;
		this.Interval = (101 - prefManager.getIntPref("extensions.scrollyfox.scrollspeed"));
		
		if ((this.Interval != intOldInterval) && (this.IntervalHandle != -1))
		{
			this.Stop();
			this.Start();
		}
		
		this.PageEndAction = prefManager.getCharPref("extensions.scrollyfox.page-end-action");
		if (this.Step < 1)
		{
			if (this.PageEndAction == "top")
			{
				this.Step = -100;
			}
			else if (this.PageEndAction == "reverse")
			{
				this.Step = -1;
			}
			else
			{
				this.Step = 1;
			}				
		}
	};
	
	this.scrollingStartStop = function() {
		if (this.IntervalHandle == -1)
		{
			this.Start();
		}
		else
		{
			this.Stop();
		}
	};
	
	this.Start = function() {
		this.refreshPrefs();
		
		if (this.PageEndAction == "top")
		{
			this.Step = 1;
		}
		
		this.IntervalHandle = setInterval(function(e) {thisClass.scroll()}, this.Interval);
		document.getElementById("scrollyfox_icon").src = "chrome://scrollyfox/content/scrollyfox_active.png";
		prefManager.setBoolPref("extensions.scrollyfox.autorun", true);
	};
	
	this.Stop = function() {
		clearInterval(this.IntervalHandle);
		this.IntervalHandle = -1;
		document.getElementById("scrollyfox_icon").src = "chrome://scrollyfox/content/scrollyfox_inactive.png";
		prefManager.setBoolPref("extensions.scrollyfox.autorun", false);
	};
	
	this.showPrefs = function() {
		window.openDialog('chrome://scrollyfox/content/scrollyfoxPrefs.xul', '', 'centerscreen');
	};
	
	this.scroll = function() {
		this.refreshPrefs();
		
		var objDocument = window.content.document;
		
		var intPageHeight = Math.max(	objDocument.body.scrollHeight, 
										objDocument.body.offsetHeight, 
										objDocument.documentElement.scrollHeight, 
										objDocument.documentElement.offsetHeight	);
		
		if (intPageHeight <= window.content.innerHeight)
		{
			document.getElementById("scrollyfox_icon").src = "chrome://scrollyfox/content/scrollyfox_active.png";
		}
		else
		{
			if (this.Step > 0)
			{
				document.getElementById("scrollyfox_icon").src = "chrome://scrollyfox/content/scrollyfox_down.png";
			}
			else
			{
				document.getElementById("scrollyfox_icon").src = "chrome://scrollyfox/content/scrollyfox_up.png";
			}
			window.content.scrollBy(0, this.Step);
			
			var blnAtTop = (window.content.pageYOffset <= 0);
			var blnAtEnd = (window.content.pageYOffset >= (intPageHeight - window.content.innerHeight));
			
			if (blnAtTop || blnAtEnd)
			{
				document.getElementById("scrollyfox_icon").src = "chrome://scrollyfox/content/scrollyfox_active.png";
				this.PageEndCount++;
				if (this.PageEndCount >= this.PageEndPause)
				{
					if (blnAtTop)
					{
						this.Step = 1;
						this.PageEndCount = 0;
					}
					else
					{
						if (this.PageEndAction == "reverse")
						{
							this.Step = -1;
							this.PageEndCount = 0;
						}
						else if (this.PageEndAction == "top")
						{
							this.Step = -100;
							this.PageEndCount = 0;
						}
						else if (this.PageEndAction == "refresh")
						{
							this.Step = -100;
							window.content.scrollTo(0, 0);
							window.content.location.reload(true);
						}
					}
				}
			}
			else
			{
				this.PageEndCount = 0;
			}
		}
	};
}

var scrollyfox = new ScrollyFox();