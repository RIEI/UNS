/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
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
 * The Original Code is Sage.
 *
 * The Initial Developer of the Original Code is
 * Peter Andrews <petea@jhu.edu>.
 * Portions created by the Initial Developer are Copyright (C) 2005
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Peter Andrews <petea@jhu.edu>
 * Erik Arvidsson <erik@eae.net>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

Components.utils.import("resource://gre/modules/XPCOMUtils.jsm");

const sageIFeedParserFactory = Components.interfaces.sageIFeedParserFactory;

/******************************************************************************
 * sageFeedParserFactory Component
 ******************************************************************************/
function sageFeedParserFactory() {};
sageFeedParserFactory.prototype = {
		
	classDescription: "Sage Feed Parser Factory Component",
	classID: Components.ID("{9C464FBF-590A-4BD0-A0F9-D72A44A505BB}"),
	contractID: "@sage.mozdev.org/sage/feedparserfactory;1",

	createFeedParser: function(feedDocument)
	{
		if (!feedDocument) {
			throw "Feed document empty";
		}
		
		var parsers = new Array();
		parsers.push("@sage.mozdev.org/sage/rssparser;1");
		parsers.push("@sage.mozdev.org/sage/atomparser;1");
		parsers.push("@sage.mozdev.org/sage/atom03parser;1");
		
		
		var FeedParser;
		var feedParser;
		var found = false;
		for (var parser in parsers) {
			FeedParser = new Components.Constructor(parsers[parser], "sageIFeedParser");
			feedParser = new FeedParser();
			if (feedParser.discover(feedDocument)) {
				found = true;
				break;
			}
		}

		if (!found) {
			throw "No matching parser found";
		}

		return feedParser;
	},
	
	// nsISupports
	QueryInterface: XPCOMUtils.generateQI([Components.interfaces.sageIFeedParserFactory])

};

if (XPCOMUtils.generateNSGetFactory) {
    var NSGetFactory = XPCOMUtils.generateNSGetFactory([sageFeedParserFactory]);
} else {
	var NSGetModule = XPCOMUtils.generateNSGetModule([sageFeedParserFactory]);
}
