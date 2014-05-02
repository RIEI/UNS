/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Adblock Plus for Chrome.
 *
 * The Initial Developer of the Original Code is
 * T. Joseph <tom@adblockplus.org>.
 * Portions created by the Initial Developer are Copyright (C) 2009-2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *  Wladimir Palant
 *
 * ***** END LICENSE BLOCK ***** */

var enabled = false; // Enabled for this particular domain.
var nukeElementsTimeoutID = 0;
var nukeElementsLastTime = 0;

// Click-to-hide stuff
var clickHide_activated = false;
var currentElement = null;
var currentElement_boxShadow = null;
var currentElement_backgroundColor;
var clickHideFilters = null;
var highlightedElementsSelector = null;
var highlightedElementsBoxShadows = null;
var highlightedElementsBGColors = null;
var clickHideFiltersDialog = null;
var lastRightClickEvent = null;

// Highlight elements according to selector string. This would include
// all elements that would be affected by proposed filters.
function highlightElements(selectorString) {
  if(highlightedElementsSelector)
    unhighlightElements();
  
  highlightedElements = document.querySelectorAll(selectorString);
  highlightedElementsSelector = selectorString;
  highlightedElementsBoxShadows = new Array();
  highlightedElementsBGColors = new Array();

  for(var i = 0; i < highlightedElements.length; i++) {
    highlightedElementsBoxShadows[i] = highlightedElements[i].style.getPropertyValue("-webkit-box-shadow");
    highlightedElementsBGColors[i] = highlightedElements[i].style.backgroundColor;
    highlightedElements[i].style.setProperty("-webkit-box-shadow", "inset 0px 0px 5px #fd6738");
    highlightedElements[i].style.backgroundColor = "#f6e1e5";
  }
}

// Unhighlight all elements, including those that would be affected by
// the proposed filters
function unhighlightElements() {
  if(highlightedElementsSelector == null)
    return;
  highlightedElements = document.querySelectorAll(highlightedElementsSelector);
  for(var i = 0; i < highlightedElements.length; i++) {
    highlightedElements[i].style.setProperty("-webkit-box-shadow", highlightedElementsBoxShadows[i]);
    highlightedElements[i].style.backgroundColor = highlightedElementsBGColors[i];
  }
  highlightedElementsSelector = null;
}

// Gets the absolute position of an element by walking up the DOM tree,
// adding up offsets.
// I hope there's a better way because it just seems absolutely stupid
// that the DOM wouldn't have a direct way to get this, given that it
// has hundreds and hundreds of other methods that do random junk.
function getAbsolutePosition(elt) {
  var l = 0;
  var t = 0;
  for(; elt; elt = elt.offsetParent) {
    l += elt.offsetLeft;
    t += elt.offsetTop;
  }
  return [l, t];
}

// Adds an overlay to an element, which is probably a Flash object
function addElementOverlay(elt) {
  // If this element is enclosed in an object tag, we prefer to block that instead
  if(!elt)
    return null;
      
  // If element doesn't have at least one of class name, ID or URL, give up
  // because we don't know how to construct a filter rule for it
  var url = getElementURL(elt);
  if(!elt.className && !elt.id && !url)
    return;
  var thisStyle = getComputedStyle(elt, null);
  var overlay = document.createElement('div');
  overlay.prisoner = elt;
  overlay.prisonerURL = url;
  overlay.className = "__adblockplus__overlay";
  overlay.setAttribute('style', 'opacity:0.4; background-color:#ffffff; display:inline-box; ' + 'width:' + thisStyle.width + '; height:' + thisStyle.height + '; position:absolute; overflow:hidden; -webkit-box-sizing:border-box; z-index: 99999');
  var pos = getAbsolutePosition(elt);
  overlay.style.left = pos[0] + "px";
  overlay.style.top = pos[1] + "px";
  // elt.parentNode.appendChild(overlay, elt);
  document.body.appendChild(overlay);
  return overlay;
}

// Allow dragging of the clickhide dialog box. This is nice to have for blocking elements
// inside small iframes that are too narrow to completely contain the clickhide dialog box.
// This way the user can drag the box over to click one of its buttons.
// Not a perfect solution but better than nothing.
var draggedElement = null;
var dragMouseOffset = null;
var docUserSelect; // Saves value of document-wide -webkit-user-select

function dragEnd(e) {
  if(draggedElement) {
    document.removeEventListener("mouseup", dragEnd, false);
    document.removeEventListener("mousemove", dragMove, false);
    document.documentElement.style.setProperty('-webkit-user-select', docUserSelect);
    draggedElement = null;
  }
}

function dragStart(e) {
  draggedElement = e.target;
  var pos = getAbsolutePosition(e.target);
  dragMouseOffset = [e.pageX - pos[0], e.pageY - pos[1]];
  document.addEventListener("mouseup", dragEnd, false);
  document.addEventListener("mousemove", dragMove, false);
  // Make document un-highlightable during drag. Otherwise, if user drags too fast and 
  // the mouse pointer leaves the bounds of the dialog box, text selection on the page 
  // will be triggered, and that is ugly
  docUserSelect = document.documentElement.style.getPropertyCSSValue('-webkit-user-select');
  document.documentElement.style.setProperty('-webkit-user-select', 'none');
}

function dragMove(e) {
  if(draggedElement) {
    draggedElement.style.left = (e.pageX - dragMouseOffset[0]) + "px";
    draggedElement.style.top = (e.pageY - dragMouseOffset[1]) + "px";
  }
}

// Show dialog asking user whether she wants to add the proposed filters derived
// from selected page element
function clickHide_showDialog(left, top, filters) {
  function escapeHTML(str)
  {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
  }

  // Limit the length the filters string shown so it doesn't clip
  var filtersString = "";
  for(var i = 0; i < filters.length; i++) {
    if(filters[i].length > 80)
      filtersString += escapeHTML(filters[i].substring(0, 80)) + "&hellip;";
    else
      filtersString += escapeHTML(filters[i]);
    filtersString += "<br/>";
  }
      
  clickHideFiltersDialog = document.createElement('div');
  clickHideFiltersDialog.setAttribute('style', 'visibility:hidden; -webkit-user-select:none; font-family: Helvetica,Arial,sans-serif !important; font-size: 10pt; color: #505050 !important; position: fixed; -webkit-box-shadow: 5px 5px 20px rgba(0,0,0,0.5); background: #ffffff; z-index: 99999; padding: 10px; border-radius: 5px');
  clickHideFiltersDialog.innerHTML = '<table style="margin:0px;border:0px;"><tr><td style="padding:0; background: #ffffff; padding-right: 5px; border: 0px; vertical-align: middle;"><img src="' + chrome.extension.getURL('icons/abp-32.png') + '"/></td><td style="padding:0; background: #ffffff; text-align: left; vertical-align: middle; border: 0px;">' + chrome.i18n.getMessage('add_filters_msg') + '</td></tr></table><div style="border:1px solid #c0c0c0; padding:3px; min-width: 200px; font-size:8pt !important; line-height: 10pt !important; font-color: #909090 !important; background: #ffffff !important">' + filtersString + '</div>';

  buttonsDiv = document.createElement('div');
  buttonsDiv.setAttribute('style', 'text-align: right');
  function makeButton(id) {
    var b = document.createElement('button');
    b.setAttribute("id", id);
    // Use the jQuery UI style for the button explicitly
    b.setAttribute("style", "padding: 3px; margin-left: 5px; font-size: 8pt; border: 1px solid #d3d3d3; background: #e6e6e6 url(" + chrome.extension.getURL("jquery-ui/css/smoothness/images/ui-bg_glass_75_e6e6e6_1x400.png") + ") 50% 50% repeat-x; color: #555555; -webkit-border-radius: 4px; font-family: Helvetica, Arial, sans-serif;");
    return b;
  }
  var addButton = makeButton("addButton");
  addButton.innerText = chrome.i18n.getMessage('add');
  addButton.onclick = function() {
    // Save the filters that the user created
    chrome.extension.sendRequest({reqtype: "cache-filters", filters: clickHideFilters});
    chrome.extension.sendRequest({reqtype: "apply-cached-filters", filters: filters});
    // Explicitly get rid of currentElement in case removeAdsAgain() doesn't catch it
    if(currentElement.parentNode) {
      currentElement.parentNode.removeChild(currentElement);
      // currentElement may actually be our overlay if right-click element selection was used
      if(currentElement.prisoner && currentElement.prisoner.parentNode)
        currentElement.prisoner.parentNode.removeChild(currentElement.prisoner);
    }
    clickHide_deactivate();
    removeAdsAgain();
    // Tell options.html to refresh its user filters listbox
    chrome.extension.sendRequest({reqtype: "refresh-user-filters-box"});
  };
  var cancelButton = makeButton("cancelButton");
  cancelButton.innerText = chrome.i18n.getMessage('cancel');
  cancelButton.onclick = function() {
    // Tell popup (indirectly) to shut up about easy create filter
    chrome.extension.sendRequest({reqtype: "set-clickhide-active", active: false});
    clickHide_deactivate();
  }
  buttonsDiv.appendChild(addButton);
  buttonsDiv.appendChild(cancelButton);
  
  // Make dialog partly transparent when mouse isn't over it so user has a better
  // view of what's going to be blocked
  clickHideFiltersDialog.onmouseout = function() {
    clickHideFiltersDialog.style.setProperty("opacity", "0.7");
  }
  clickHideFiltersDialog.onmouseover = function() {
    clickHideFiltersDialog.style.setProperty("opacity", "1.0");
  } 
  
  clickHideFiltersDialog.appendChild(buttonsDiv);
  document.body.appendChild(clickHideFiltersDialog);
  // Position in upper-left all the time
  clickHideFiltersDialog.style.left = "50px";
  clickHideFiltersDialog.style.top = "50px";
  clickHideFiltersDialog.style.visibility = "visible";
  
  clickHideFiltersDialog.addEventListener('mousedown', dragStart, false);
}

// Turn on the choose element to create filter thing
function clickHide_activate() {
  if(document == null)
    return;
  
  // If we already had a selected element, restore its appearance
  if(currentElement) {
    currentElement.style.setProperty("-webkit-box-shadow", currentElement_boxShadow);
    currentElement.style.backgroundColor = currentElement_backgroundColor;
    currentElement = null;
    clickHideFilters = null;
  }
  
  // Add overlays for elements with URLs so user can easily click them
  var elts = document.querySelectorAll('object,embed,img,iframe');
  for(var i=0; i<elts.length; i++)
    addElementOverlay(elts[i]);
  
  clickHide_activated = true;
  document.addEventListener("mouseover", clickHide_mouseOver, false);
  document.addEventListener("mouseout", clickHide_mouseOut, false);
  document.addEventListener("click", clickHide_mouseClick, false);
  document.addEventListener("keyup", clickHide_keyUp, false);
}

// Called when user has clicked on something and we are waiting for confirmation
// on whether the user actually wants these filters
function clickHide_rulesPending() {
  clickHide_activated = false;
  document.removeEventListener("mouseover", clickHide_mouseOver, false);
  document.removeEventListener("mouseout", clickHide_mouseOut, false);
  document.removeEventListener("click", clickHide_mouseClick, false);
  document.removeEventListener("keyup", clickHide_keyUp, false);
}

// Turn off click-to-hide
function clickHide_deactivate() {
  if(clickHideFiltersDialog) {
    clickHideFiltersDialog.setAttribute('style', 'visibility: hidden');
    document.body.removeChild(clickHideFiltersDialog);
    clickHideFiltersDialog = null;
  }

  if(currentElement) {
    unhighlightElements();
    currentElement.style.setProperty("-webkit-box-shadow", currentElement_boxShadow);
    currentElement.style.backgroundColor = currentElement_backgroundColor;
    currentElement = null;
    clickHideFilters = null;
  }
  
  clickHide_activated = false;
  if(!document)
    return; // This can happen inside a nuked iframe...I think
  document.removeEventListener("mouseover", clickHide_mouseOver, false);
  document.removeEventListener("mouseout", clickHide_mouseOut, false);
  document.removeEventListener("click", clickHide_mouseClick, false);
  document.removeEventListener("keyup", clickHide_keyUp, false);
  
  // Remove overlays
  // For some reason iterating over the array returend by getElementsByClassName() doesn't work
  var elt;
  while(elt = document.querySelector('.__adblockplus__overlay'))
    elt.parentNode.removeChild(elt);
}

function clickHide_elementClickHandler(ev) {
  ev.preventDefault();
  ev.stopPropagation();
  clickHide_mouseClick(ev);
}

// Hovering over an element so highlight it
function clickHide_mouseOver(e) {
  if(clickHide_activated == false)
    return;

  if(e.target.id || e.target.className || e.target.src) {
    currentElement = e.target;
    currentElement_boxShadow = e.target.style.getPropertyValue("-webkit-box-shadow");
    currentElement_backgroundColor = e.target.style.backgroundColor;
    e.target.style.setProperty("-webkit-box-shadow", "inset 0px 0px 5px #d6d84b");
    e.target.style.backgroundColor = "#f8fa47";

    // TODO: save old context menu
    e.target.addEventListener("contextmenu", clickHide_elementClickHandler, false);
  }
}

// No longer hovering over this element so unhighlight it
function clickHide_mouseOut(e) {
  if(!clickHide_activated || !currentElement)
    return;
  
  currentElement.style.setProperty("-webkit-box-shadow", currentElement_boxShadow);
  currentElement.style.backgroundColor = currentElement_backgroundColor;
  
  // TODO: restore old context menu
  currentElement.removeEventListener("contextmenu", clickHide_elementClickHandler, false);
}

// Selects the currently hovered-over filter
function clickHide_keyUp(e) {
  // Ctrl+Shift+E
  if(e.ctrlKey && e.shiftKey && e.keyCode == 69)
    clickHide_mouseClick(e);
}

// When the user clicks, the currentElement is the one we want.
// We should have ABP rules ready for when the
// popup asks for them.
function clickHide_mouseClick(e) {
  if(!currentElement || !clickHide_activated)
    return;
      
  var elt = currentElement;
  var url = null;
  if(currentElement.className && currentElement.className == "__adblockplus__overlay") {
    elt = currentElement.prisoner;
    url = currentElement.prisonerURL;
  } else if(elt.src) {
    url = elt.src;
  }

  // Only normalize when the element contains a URL (issue 328.)
  // The URL is not always normalized, so do it here
  if(url)
    url = normalizeURL(relativeToAbsoluteUrl(url));
  
  // Construct filters. The popup will retrieve these.
  // Only one ID
  var elementId = elt.id ? elt.id.split(' ').join('') : null;
  // Can have multiple classes, and there might be extraneous whitespace
  var elementClasses = null;
  if(elt.className) {
    elementClasses = elt.className.replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '').split(' ');
  }
  clickHideFilters = new Array();
  selectorList = new Array();
  if(elementId) {
    clickHideFilters.push(document.domain + "###" + elementId);
    selectorList.push("#" + elementId);
  }
  if(elementClasses) {
    for(var i = 0; i < elementClasses.length; i++) {
      clickHideFilters.push(document.domain + "##." + elementClasses[i]);
      selectorList.push("." + elementClasses[i]);
    }
  }
  if(url) {
    clickHideFilters.push(relativeToAbsoluteUrl(url));
    selectorList.push(elt.localName + '[src="' + url + '"]');
  }
  
  // Show popup
  clickHide_showDialog(e.clientX, e.clientY, clickHideFilters);

  // Highlight the unlucky elements
  // Restore currentElement's box-shadow and bgcolor so that highlightElements won't save those
  currentElement.style.setProperty("-webkit-box-shadow", currentElement_boxShadow);
  currentElement.style.backgroundColor = currentElement_backgroundColor;
  // Highlight the elements specified by selector in yellow
  highlightElements(selectorList.join(","));
  // Now, actually highlight the element the user clicked on in red
  currentElement.style.setProperty("-webkit-box-shadow", "inset 0px 0px 5px #fd1708");
  currentElement.style.backgroundColor = "#f6a1b5";

  // Half-deactivate click-hide so the user has a chance to click the page action icon.
  // currentElement is still set to the putative element to be blocked.
  clickHide_rulesPending();
}

// Called when a new filter is added.
// It would be a click-to-hide filter, so it's only an elemhide filter.
// Since this rarely happens, we can afford to do a full run of ad removal.
function removeAdsAgain()
{
  chrome.extension.sendRequest({reqtype: "get-settings", matcher: true, selectors: true, host: window.location.hostname}, function(response)
  {
    // Retrieve new set of selectors and build selector strings
    if (elemhideElt)
      elemhideElt.innerText = (response.selectors ? generateElemhideCSSString(response.selectors) : "");
    defaultMatcher.clear();
    if (response.matcherData)
      defaultMatcher.fromCache(JSON.parse(response.matcherData));
    nukeElements();
  });
}

// Block ads in nodes inserted by scripts
function handleNodeInserted(e)
{
  // Remove ads relatively infrequently. If no timeout set, set one.
  if(enabled && nukeElementsTimeoutID == 0)
  {
    nukeElementsTimeoutID = setTimeout(nukeElements, (Date.now() - nukeElementsLastTime > 1000) ? 1 : 1000);
  }
}

// Extracts source URL from an IMG, OBJECT, EMBED, or IFRAME
function getElementURL(elt) {
  // Check children of object nodes for "param" nodes with name="movie" that specify a URL
  // in value attribute
  var url;
  if(elt.localName.toUpperCase() == "OBJECT" && !(url = elt.getAttribute("data"))) {
    // No data attribute, look in PARAM child tags for a URL for the swf file
    var params = elt.querySelectorAll("param[name=\"movie\"]");
    // This OBJECT could contain an EMBED we already nuked, in which case there's no URL
    if(params[0])
      url = params[0].getAttribute("value");
    else {
      params = elt.querySelectorAll("param[name=\"src\"]");
      if(params[0])
        url = params[0].getAttribute("value");
    }
  } else if(!url) {
    url = elt.getAttribute("src") || elt.getAttribute("href"); 
  }
  return url;
}

// Hides/removes image and Flash elements according to the external resources they load.
// (e.g. src attribute)
function nukeElements()
{
  var elts = document.querySelectorAll("img,object,iframe,embed,link");
  for (var i = 0; i < elts.length; i++)
  {
    // The URL is normalized in the background script so we don't need to do it here
    var url = getElementURL(elts[i]);
    // If the URL of the element is the same as the document URI, the user is trying to directly
    // view the ad for some reason and so we won't block it.
    if (url && url != document.baseURI && shouldBlock(url, TagToType[elts[i].localName.toUpperCase()]))
      nukeSingleElement(elts[i]);
  }
  
  nukeElementsTimeoutID = 0;
  nukeElementsLastTime = Date.now();
}

// Content scripts are apparently invoked on non-HTML documents, so we have to
// check for that before doing stuff. |document instanceof HTMLDocument| check
// will fail on some sites like planet.mozilla.org because WebKit creates
// Document instances for XHTML documents, have to test the root element.
if (document.documentElement instanceof HTMLElement)
{
  // Use a contextmenu handler to save the last element the user right-clicked on.
  // To make things easier, we actually save the DOM event.
  // We have to do this because the contextMenu API only provides a URL, not the actual
  // DOM element.
  document.addEventListener('contextmenu', function(e) {
    lastRightClickEvent = e;
  }, false);
  
  chrome.extension.onRequest.addListener(function(request, sender, sendResponse) {
    // background.html might want to know this document's domain
    if(request.reqtype == "get-domain") {
      sendResponse({domain: document.domain});
    } else if(request.reqtype == "clickhide-activate") {
      // So that popup can figure out what it's supposed to show
      chrome.extension.sendRequest({reqtype: "set-clickhide-active", active: true});
      clickHide_activate();
    } else if(request.reqtype == "clickhide-deactivate") {
      chrome.extension.sendRequest({reqtype: "set-clickhide-active", active: false});
      clickHide_deactivate();
    } else if(request.reqtype == "clickhide-new-filter") {
      // The request is received by all frames, so ignore it if we're not the frame the
      // user right-clicked in
      if(!lastRightClickEvent)
        return;
      // This request would have come from the chrome.contextMenu handler, so we
      // simulate the user having chosen the element to get rid of via the usual means.
      clickHide_activated = true;
      // FIXME: clickHideFilters is erased in clickHide_mouseClick anyway, so why set it?
      clickHideFilters = [request.filter];
      // We hope the URL we are given is the same as the one in the element referenced
      // by lastRightClickEvent.target. If not, we just discard
      var target = lastRightClickEvent.target;
      var url = relativeToAbsoluteUrl(target.src);
      // If we don't have the element with a src URL same as the filter, look for it.
      // Chrome's context menu API is terrible. Why can't it give us the friggin' element
      // to start with?
      if(request.filter !== url) {
        // Grab all elements with a src attribute.
        // This won't work for all object/embed tags, but the context menu API doesn't
        // work on those, so we're OK for now.
        var elts = document.querySelectorAll('[src]');
        for(var i=0; i<elts.length; i++) {
          url = relativeToAbsoluteUrl(elts[i].src);
          if(request.filter === url) {
            // This is hopefully our element. In case of multiple elements
            // with the same src, only one will be highlighted.
            target = elts[i];
            break;
          }
        }
      }
      // Following test will be true if we found the element with the filter URL
      if(request.filter === url) {
        // Coerce red highlighted overlay on top of element to remove.
        // TODO: Wow, the design of the clickHide stuff is really dumb - gotta fix it sometime
        currentElement = addElementOverlay(target);
        // clickHide_mouseOver(lastRightClickEvent);
        clickHide_mouseClick(lastRightClickEvent);
      } else {
        console.log("clickhide-new-filter: URLs don't match. Couldn't find that element.", request.filter, url, lastRightClickEvent.target.src);
        // Restore previous state
        clickHide_activated = false;
        clickHideFilters = null;
      }
    } else if(request.reqtype == "remove-ads-again") {
      // Called when a new filter is added
      removeAdsAgain();
    } else
      sendResponse({});
  });

  chrome.extension.sendRequest({reqtype: "get-domain-enabled-state"}, function(response)
  {
    enabled = response.enabled;
    if(enabled)
    {
      if (workaroundBeforeloadMalfunction)
      {
        // Too bad, we cannot block properly - resort to crawling the document
        // for ads.
        nukeElements();
        document.addEventListener("DOMNodeInserted", handleNodeInserted, false);
      }

      // Nuke background if it's an ad
      var bodyBackground = getComputedStyle(document.body).backgroundImage;
      if (bodyBackground && /^url\((.*)\)$/.test(bodyBackground) && shouldBlock(RegExp.$1, "IMAGE"))
        document.body.style.setProperty("background-image", "none", "important");
    }
  });

}
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Adblock Plus for Chrome.
 *
 * The Initial Developer of the Original Code is
 * T. Joseph <tom@adblockplus.org>.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

// Explicitly removes inline text ads, in case we were unable to block the ad script itself
// in the beforeload handler.
function removeTextAdFromElement(elt) {
  // The DOMNodeInserted hooks means we get called for #text nodes, which means localName is null.
  // We don't touch those
  if(!elt.localName) return;
  var keepNode;
  switch(elt.localName.toUpperCase()) {
    // AdBrite
    case 'ISPAN':
      if(elt.id.indexOf('AdBriteInlineAd_') >= 0) {
        keepNode = document.createTextNode(elt.id.substr('AdBriteInlineAd_'.length));
      }
      break;
      
    // Chitika and InfoLinks
    case 'SPAN':
      var fc = elt.firstChild;
      if(!fc) break;
      if(elt.className == 'IL_AD') {
        keepNode = fc;
      } else if(fc.localName && fc.localName.toUpperCase() == 'A' && fc.className.indexOf('lx-link') >= 0) {
        keepNode = fc.firstChild;
      }
      break;
      
    // EchoTopic and ResultLinks
    case 'NOBR':
      var fc = elt.firstChild;
      if(fc && fc.nodeName != '#text' && (fc.className == 'tfTextLink' || fc.id.indexOf('RLLINK') >= 0)) {
        keepNode = fc.firstChild;
      }
      break;

    case 'A':
      // Some other ones, including LinkWorth, Kontera, Affinity
      switch(elt.className) {
        case 'IL_LINK_STYLE':
        case 'contextual':
        case 'lw_cad_link':
        case 'cm_word':
          keepNode = elt.firstChild;
          break;
        
        // Kontera really mangles the original text
        case 'kLink':
          var textNodes = elt.querySelectorAll('font > span'), text = "";
          for(var i = 0; i < textNodes.length; i++) text += textNodes[i].innerHTML;
          keepNode = document.createTextNode(text);
          break;
          
        default:
          // IntelliTxt
          if(elt.hasAttribute('itxtdid')) {
            keepNode = elt.firstChild;
            break;
          }

          // Not sure if this AdBrite check is still necessary
          if(elt.id.indexOf('AdBriteInlineAd_') >= 0) {
            keepNode = document.createTextNode(elt.id.substr('AdBriteInlineAd_'.length));
            break;
          }
      }
    break; // case 'A'
  }
  
  // Replace the offending node with the original content that was inside it
  if(keepNode) elt.parentNode.replaceChild(keepNode, elt);
}

chrome.extension.sendRequest({reqtype: "get-domain-enabled-state"}, function(response) {
  if(response.enabled && response.disableInlineTextAds) {
    // Listen for inserted nodes and process them as they come in
    document.addEventListener('DOMNodeInserted', function(event) {
      // Delay our handler to work around Chrome issue 81530
      window.setTimeout(function() {
        removeTextAdFromElement(event.target);
      }, 0);
    }, true);

    // However, our event handler above may not have been inserted in time, so we also scan the document.
    // We use setTimeout here because there is no way to ensure that we are running after the ad scripts have run.
    // So we hope that the delay is long enough.
    setTimeout(function() {
      var elts = document.querySelectorAll("a.IL_LINK_STYLE, a.lw_cad_link, a.cm_word, a.contextual, a.kLink, a[itxtdid], nobr, ispan, span.IL_AD");
      for (var i=0; i<elts.length; i++) removeTextAdFromElement(elts[i]);
    }, 50);
  }
});
