// Variable to keep track of if autologin has been stopped
stopped = false

// Helper function
function findFirstFilledOutPasswordInput()
{
    input = false;
    arDocuments = new Array();
    
    // add main document
    arDocuments.push(document);
    
    // add frames
    frames = document.getElementsByTagName('frame');
    for(i in frames)
        arDocuments.push(frames[i].contentDocument);
    
    // add iframes
    iframes = document.getElementsByTagName('frame');
    for(i in iframes)
        arDocuments.push(iframes[i].contentDocument);
    
    // iterate over documents looking for password inputs
    for(i in arDocuments)
    {
        result = document.evaluate("//input[@type='password']", document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
        
        // iterate over password inputs looking for filled ones
        for(i=0;i<result.snapshotLength;i++)
        {
            if(result.snapshotItem(i).value != '')
            {
                input = result.snapshotItem(i);
                break;
            }
        }
        
        if(input)
            break;
    } 
    
    return input;
}

// Submits the form, calling event handlers first 
// (if the log in hasn't been stopped)
function submitWithEventHandlers(theForm)
{
    if (!stopped)
    {
        submitForm = true;

        // look for onsubmit handler
        submitFunc = theForm.onsubmit; 
        if (typeof submitFunc == 'function')
        {
            submitForm = submitFunc.call(theForm);
        }
		else // look for inline defined onsubmit
		{
			// nevermind, can't call it because of scope issues
		}

        if (submitForm)
		{
            theForm.submit();
		}
		else
		{
			alert('Form submittal was cancelled by the page.  Check for invalid inputs.  If there are none, AutoLogin may not be compatible with this site.');
			if(overlay = document.getElementById('AutoLoginOverlay'))
				document.body.removeChild(overlay);
		}
    }
}
                
// Respond to requests from the background page
chrome.extension.onRequest.addListener(
    function (request, sender, sendResponse)
    {
        if (!stopped)
        {
            input = findFirstFilledOutPasswordInput();

            // submit form
            if (input)
            {
                if (request.overlay) // display cool graphic
                {
                    var overlay = document.createElement('div');
                    overlay.setAttribute('id', 'AutoLoginOverlay');
                    //overlay.style.height = window.innerHeight + 'px'; 
                    document.body.appendChild(overlay);

                    h1 = document.createElement('h1');
                    h1.innerText = 'Logging in...';
                    h1.style.marginTop = ((window.innerHeight / 2) - (150 / 2)) + 'px';
                    overlay.appendChild(h1);

                    var h2 = document.createElement('h2');
                    h2.innerText = "Cancel";
                    h2.onclick = function ()
                    {
                        window.stop();
                        stopped = true;
                        document.body.removeChild(overlay);
                    };
                    overlay.appendChild(h2);

                    setTimeout(function () { submitWithEventHandlers(input.form) }, request.wait * 1000);
                }
                else
                {
                    submitWithEventHandlers(input.form);
                }
            }
        }
    }
);

// Check if the "page action" icon should be displayed
input = findFirstFilledOutPasswordInput()
chrome.extension.sendRequest({showIcon: (input != false)});