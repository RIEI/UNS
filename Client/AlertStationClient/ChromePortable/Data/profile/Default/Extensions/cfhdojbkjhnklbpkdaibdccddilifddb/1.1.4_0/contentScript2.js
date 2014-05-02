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
 * Wladimir Palant.
 * Portions created by the Initial Developer are Copyright (C) 2009-2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *  T. Joseph <tom@adblockplus.org>.
 *
 * ***** END LICENSE BLOCK ***** */

function ElemHidePatch()
{
  /**
   * Returns a list of selectors to be applied on a particular domain. With
   * specificOnly parameter set to true only the rules listing specific domains
   * will be considered.
   */
  ElemHide.getSelectorsForDomain = function(/**String*/ domain, /**Boolean*/ specificOnly)
  {
    var result = [];
    for (var key in filterByKey)
    {
      var filter = Filter.knownFilters[filterByKey[key]];
      if (specificOnly && !filter.includeDomains)
        continue;

      if (filter.isActiveOnDomain(domain))
        result.push(filter.selector);
    }
    return result;
  }
}

function FilterListenerPatch()
{
  /**
   * Triggers subscription observer "manually", temporary hack until that can
   * be done properly (via FilterStorage).
   */
  FilterListener.triggerSubscriptionObserver = function(action, subscriptions)
  {
    onSubscriptionChange(action, subscriptions);
  }

  /**
   * Triggers filter observer "manually", temporary hack until that can
   * be done properly (via FilterStorage).
   */
  FilterListener.triggerFilterObserver = function(action, filters)
  {
    onFilterChange(action, filters);
  }
}

function MatcherPatch()
{
  // Very ugly - we need to rewrite _checkEntryMatch() function to make sure
  // it calls Filter.fromText() instead of assuming that the filter exists.
  var origFunction = Matcher.prototype._checkEntryMatch.toString();
  var newFunction = origFunction.replace(/\bFilter\.knownFilters\[(.*?)\];/g, "Filter.fromText($1);");
  eval("Matcher.prototype._checkEntryMatch = " + newFunction);
}

var Components =
{
  interfaces: {},
  classes: {},
  results: {},
  utils: {},
  manager: null,
  ID: function()
  {
    return null;
  }
};
const Cc = Components.classes;
const Ci = Components.interfaces;
const Cr = Components.results;
const Cu = Components.utils;

var Utils =
{
  systemPrincipal: null,
  getString: function(id)
  {
    return id;
  }
};

var XPCOMUtils =
{
  generateQI: function() {}
};
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
 * The Original Code is Adblock Plus.
 *
 * The Initial Developer of the Original Code is
 * Wladimir Palant.
 * Portions created by the Initial Developer are Copyright (C) 2006-2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

//
// This file has been generated automatically from Adblock Plus source code
//

(function (_patchFunc5) {
  function _extend0(baseClass, props) {
    var dummyConstructor = function () { };
    dummyConstructor.prototype = baseClass.prototype;
    var result = new dummyConstructor();
    for (var k in props)
      result[k] = props[k];
    return result;
  }
  function Filter(text) {
    this.text = text;
    this.subscriptions = [];
  }
  Filter.prototype = {
    text: null,
    subscriptions: null,
    serialize: function (buffer) {
      buffer.push("[Filter]");
      buffer.push("text=" + this.text);
    }
    ,
    toString: function () {
      return this.text;
    }
    
  };
  Filter.knownFilters = {
    
  };
  Filter.elemhideRegExp = /^([^\/\*\|\@"!]*?)#(?:([\w\-]+|\*)((?:\([\w\-]+(?:[$^*]?=[^\(\)"]*)?\))*)|#([^{}]+))$/;
  Filter.regexpRegExp = /^(@@)?\/.*\/(?:\$~?[\w\-]+(?:=[^,\s]+)?(?:,~?[\w\-]+(?:=[^,\s]+)?)*)?$/;
  Filter.optionsRegExp = /\$(~?[\w\-]+(?:=[^,\s]+)?(?:,~?[\w\-]+(?:=[^,\s]+)?)*)$/;
  Filter.fromText = (function (text) {
    if (text in Filter.knownFilters)
      return Filter.knownFilters[text];
    if (!/\S/.test(text))
      return null;
    var ret;
    if (Filter.elemhideRegExp.test(text))
      ret = ElemHideFilter.fromText(text, RegExp["$1"], RegExp["$2"], RegExp["$3"], RegExp["$4"]);
     else
      if (text[0] == "!")
        ret = new CommentFilter(text);
       else
        ret = RegExpFilter.fromText(text);
    Filter.knownFilters[ret.text] = ret;
    return ret;
  }
  );
  Filter.fromObject = (function (obj) {
    var ret = Filter.fromText(obj.text);
    if (ret instanceof ActiveFilter) {
      if ("disabled" in obj)
        ret.disabled = (obj.disabled == "true");
      if ("hitCount" in obj)
        ret.hitCount = parseInt(obj.hitCount) || 0;
      if ("lastHit" in obj)
        ret.lastHit = parseInt(obj.lastHit) || 0;
    }
    return ret;
  }
  );
  Filter.normalize = (function (text) {
    if (!text)
      return text;
    text = text.replace(/[^\S ]/g, "");
    if (/^\s*!/.test(text)) {
      return text.replace(/^\s+/, "").replace(/\s+$/, "");
    }
     else
      if (Filter.elemhideRegExp.test(text)) {
        /^(.*?)(#+)(.*)$/.test(text);
        var domain = RegExp["$1"];
        var separator = RegExp["$2"];
        var selector = RegExp["$3"];
        return domain.replace(/\s/g, "") + separator + selector.replace(/^\s+/, "").replace(/\s+$/, "");
      }
       else
        return text.replace(/\s/g, "");
  }
  );
  function InvalidFilter(text, reason) {
    Filter.call(this, text);
    this.reason = reason;
  }
  InvalidFilter.prototype = _extend0(Filter, {
    reason: null,
    serialize: function (buffer) { }
  });
  function CommentFilter(text) {
    Filter.call(this, text);
  }
  CommentFilter.prototype = _extend0(Filter, {
    serialize: function (buffer) { }
  });
  function ActiveFilter(text, domains) {
    Filter.call(this, text);
    if (domains) {
      this.domainSource = domains;
      this.__defineGetter__("includeDomains", this._getIncludeDomains);
      this.__defineGetter__("excludeDomains", this._getExcludeDomains);
    }
  }
  ActiveFilter.prototype = _extend0(Filter, {
    disabled: false,
    hitCount: 0,
    lastHit: 0,
    domainSource: null,
    domainSeparator: null,
    includeDomains: null,
    excludeDomains: null,
    _getIncludeDomains: function () {
      this._generateDomains();
      return this.includeDomains;
    }
    ,
    _getExcludeDomains: function () {
      this._generateDomains();
      return this.excludeDomains;
    }
    ,
    _generateDomains: function () {
      var domains = this.domainSource.split(this.domainSeparator);
      delete this.domainSource;
      delete this.includeDomains;
      delete this.excludeDomains;
      if (domains.length == 1 && domains[0][0] != "~") {
        this.includeDomains = {
          
        };
        this.includeDomains[domains[0]] = true;
      }
       else {
        for (var _loopIndex1 = 0;
        _loopIndex1 < domains.length; ++ _loopIndex1) {
          var domain = domains[_loopIndex1];
          if (domain == "")
            continue;
          var hash = "includeDomains";
          if (domain[0] == "~") {
            hash = "excludeDomains";
            domain = domain.substr(1);
          }
          if (!this[hash])
            this[hash] = {
              
            };
          this[hash][domain] = true;
        }
      }
    }
    ,
    isActiveOnDomain: function (docDomain) {
      if (!docDomain)
        return (!this.includeDomains);
      if (!this.includeDomains && !this.excludeDomains)
        return true;
      docDomain = docDomain.replace(/\.+$/, "").toUpperCase();
      while (true) {
        if (this.includeDomains && docDomain in this.includeDomains)
          return true;
        if (this.excludeDomains && docDomain in this.excludeDomains)
          return false;
        var nextDot = docDomain.indexOf(".");
        if (nextDot < 0)
          break;
        docDomain = docDomain.substr(nextDot + 1);
      }
      return (this.includeDomains == null);
    }
    ,
    isActiveOnlyOnDomain: function (docDomain) {
      if (!docDomain || !this.includeDomains)
        return false;
      docDomain = docDomain.replace(/\.+$/, "").toUpperCase();
      for (var domain in this.includeDomains)
        if (domain != docDomain && (domain.length <= docDomain.length || domain.indexOf("." + docDomain) != domain.length - docDomain.length - 1))
          return false;
      return true;
    }
    ,
    serialize: function (buffer) {
      if (this.disabled || this.hitCount || this.lastHit) {
        Filter.prototype.serialize.call(this, buffer);
        if (this.disabled)
          buffer.push("disabled=true");
        if (this.hitCount)
          buffer.push("hitCount=" + this.hitCount);
        if (this.lastHit)
          buffer.push("lastHit=" + this.lastHit);
      }
    }
    
  });
  function RegExpFilter(text, regexpSource, contentType, matchCase, domains, thirdParty) {
    ActiveFilter.call(this, text, domains);
    if (contentType != null)
      this.contentType = contentType;
    if (matchCase)
      this.matchCase = matchCase;
    if (thirdParty != null)
      this.thirdParty = thirdParty;
    if (regexpSource[0] == "/" && regexpSource[regexpSource.length - 1] == "/") {
      this.regexp = new RegExp(regexpSource.substr(1, regexpSource.length - 2), this.matchCase ? "" : "i");
    }
     else {
      this.regexpSource = regexpSource;
      this.__defineGetter__("regexp", this._generateRegExp);
    }
  }
  RegExpFilter.prototype = _extend0(ActiveFilter, {
    domainSeparator: "|",
    regexpSource: null,
    regexp: null,
    contentType: 2147483647,
    matchCase: false,
    thirdParty: null,
    _generateRegExp: function () {
      var source = this.regexpSource.replace(/\*+/g, "*");
      if (source[0] == "*")
        source = source.substr(1);
      var pos = source.length - 1;
      if (pos >= 0 && source[pos] == "*")
        source = source.substr(0, pos);
      source = source.replace(/\^\|$/, "^").replace(/\W/g, "\\$&").replace(/\\\*/g, ".*").replace(/\\\^/g, "(?:[\\x00-\\x24\\x26-\\x2C\\x2F\\x3A-\\x40\\x5B-\\x5E\\x60\\x7B-\\x80]|$)").replace(/^\\\|\\\|/, "^[\\w\\-]+:\\/+(?!\\/)(?:[^\\/]+\\.)?").replace(/^\\\|/, "^").replace(/\\\|$/, "$");
      var regexp = new RegExp(source, this.matchCase ? "" : "i");
      delete this.regexp;
      delete this.regexpSource;
      return (this.regexp = regexp);
    }
    ,
    matches: function (location, contentType, docDomain, thirdParty) {
      if (this.regexp.test(location) && (RegExpFilter.typeMap[contentType] & this.contentType) != 0 && (this.thirdParty == null || this.thirdParty == thirdParty) && this.isActiveOnDomain(docDomain)) {
        return true;
      }
      return false;
    }
    
  });
  RegExpFilter.fromText = (function (text) {
    var constructor = BlockingFilter;
    var origText = text;
    if (text.indexOf("@@") == 0) {
      constructor = WhitelistFilter;
      text = text.substr(2);
    }
    var contentType = null;
    var matchCase = null;
    var domains = null;
    var thirdParty = null;
    var collapse = null;
    var options;
    if (Filter.optionsRegExp.test(text)) {
      options = RegExp["$1"].toUpperCase().split(",");
      text = RegExp.leftContext;
      for (var _loopIndex2 = 0;
      _loopIndex2 < options.length; ++ _loopIndex2) {
        var option = options[_loopIndex2];
        var value;
        var _tempVar3 = option.split("=", 2);
        option = _tempVar3[0];
        value = _tempVar3[1];
        option = option.replace(/-/, "_");
        if (option in RegExpFilter.typeMap) {
          if (contentType == null)
            contentType = 0;
          contentType |= RegExpFilter.typeMap[option];
        }
         else
          if (option[0] == "~" && option.substr(1) in RegExpFilter.typeMap) {
            if (contentType == null)
              contentType = RegExpFilter.prototype.contentType;
            contentType &= ~RegExpFilter.typeMap[option.substr(1)];
          }
           else
            if (option == "MATCH_CASE")
              matchCase = true;
             else
              if (option == "DOMAIN" && typeof value != "undefined")
                domains = value;
               else
                if (option == "THIRD_PARTY")
                  thirdParty = true;
                 else
                  if (option == "~THIRD_PARTY")
                    thirdParty = false;
                   else
                    if (option == "COLLAPSE")
                      collapse = true;
                     else
                      if (option == "~COLLAPSE")
                        collapse = false;
      }
    }
    if (constructor == WhitelistFilter && (contentType == null || (contentType & RegExpFilter.typeMap.DOCUMENT)) && (!options || options.indexOf("DOCUMENT") < 0) && !/^\|?[\w\-]+:/.test(text)) {
      if (contentType == null)
        contentType = RegExpFilter.prototype.contentType;
      contentType &= ~RegExpFilter.typeMap.DOCUMENT;
    }
    try {
      return new constructor(origText, text, contentType, matchCase, domains, thirdParty, collapse);
    }
    catch (e){
      return new InvalidFilter(text, e);
    }
  }
  );
  RegExpFilter.typeMap = {
    OTHER: 1,
    SCRIPT: 2,
    IMAGE: 4,
    STYLESHEET: 8,
    OBJECT: 16,
    SUBDOCUMENT: 32,
    DOCUMENT: 64,
    XBL: 512,
    PING: 1024,
    XMLHTTPREQUEST: 2048,
    OBJECT_SUBREQUEST: 4096,
    DTD: 8192,
    MEDIA: 16384,
    FONT: 32768,
    BACKGROUND: 4,
    DONOTTRACK: 536870912,
    ELEMHIDE: 1073741824
  };
  RegExpFilter.prototype.contentType &= ~(RegExpFilter.typeMap.ELEMHIDE | RegExpFilter.typeMap.DONOTTRACK);
  function BlockingFilter(text, regexpSource, contentType, matchCase, domains, thirdParty, collapse) {
    RegExpFilter.call(this, text, regexpSource, contentType, matchCase, domains, thirdParty);
    this.collapse = collapse;
  }
  BlockingFilter.prototype = _extend0(RegExpFilter, {
    collapse: null
  });
  function WhitelistFilter(text, regexpSource, contentType, matchCase, domains, thirdParty) {
    RegExpFilter.call(this, text, regexpSource, contentType, matchCase, domains, thirdParty);
  }
  WhitelistFilter.prototype = _extend0(RegExpFilter, {
    
  });
  function ElemHideFilter(text, domains, selector) {
    ActiveFilter.call(this, text, domains ? domains.toUpperCase() : null);
    if (domains)
      this.selectorDomain = domains.replace(/,~[^,]+/g, "").replace(/^~[^,]+,?/, "").toLowerCase();
    this.selector = selector;
  }
  ElemHideFilter.prototype = _extend0(ActiveFilter, {
    domainSeparator: ",",
    selectorDomain: null,
    selector: null
  });
  ElemHideFilter.fromText = (function (text, domain, tagName, attrRules, selector) {
    if (!selector) {
      if (tagName == "*")
        tagName = "";
      var id = null;
      var additional = "";
      if (attrRules) {
        attrRules = attrRules.match(/\([\w\-]+(?:[$^*]?=[^\(\)"]*)?\)/g);
        for (var _loopIndex4 = 0;
        _loopIndex4 < attrRules.length; ++ _loopIndex4) {
          var rule = attrRules[_loopIndex4];
          rule = rule.substr(1, rule.length - 2);
          var separatorPos = rule.indexOf("=");
          if (separatorPos > 0) {
            rule = rule.replace(/=/, "=\"") + "\"";
            additional += "[" + rule + "]";
          }
           else {
            if (id)
              return new InvalidFilter(text, Utils.getString("filter_elemhide_duplicate_id"));
             else
              id = rule;
          }
        }
      }
      if (id)
        selector = tagName + "." + id + additional + "," + tagName + "#" + id + additional;
       else
        if (tagName || additional)
          selector = tagName + additional;
         else
          return new InvalidFilter(text, Utils.getString("filter_elemhide_nocriteria"));
    }
    return new ElemHideFilter(text, domain, selector);
  }
  );
  if (typeof _patchFunc5 != "undefined")
    eval("(" + _patchFunc5.toString() + ")()");
  window.Filter = Filter;
  window.InvalidFilter = InvalidFilter;
  window.CommentFilter = CommentFilter;
  window.ActiveFilter = ActiveFilter;
  window.RegExpFilter = RegExpFilter;
  window.BlockingFilter = BlockingFilter;
  window.WhitelistFilter = WhitelistFilter;
  window.ElemHideFilter = ElemHideFilter;
}
)(window.FilterClassesPatch);
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
 * The Original Code is Adblock Plus.
 *
 * The Initial Developer of the Original Code is
 * Wladimir Palant.
 * Portions created by the Initial Developer are Copyright (C) 2006-2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

//
// This file has been generated automatically from Adblock Plus source code
//

(function (_patchFunc0) {
  function Matcher() {
    this.clear();
  }
  Matcher.prototype = {
    filterByKeyword: null,
    keywordByFilter: null,
    clear: function () {
      this.filterByKeyword = {
        
      };
      this.keywordByFilter = {
        
      };
    }
    ,
    add: function (filter) {
      if (filter.text in this.keywordByFilter)
        return ;
      var keyword = this.findKeyword(filter);
      switch (typeof this.filterByKeyword[keyword]) {
        case "undefined": {
          this.filterByKeyword[keyword] = filter.text;
          break;
        }
        case "string": {
          this.filterByKeyword[keyword] = [this.filterByKeyword[keyword], filter.text];
          break;
        }
        default: {
          this.filterByKeyword[keyword].push(filter.text);
          break;
        }
      }
      this.keywordByFilter[filter.text] = keyword;
    }
    ,
    remove: function (filter) {
      if (!(filter.text in this.keywordByFilter))
        return ;
      var keyword = this.keywordByFilter[filter.text];
      var list = this.filterByKeyword[keyword];
      if (typeof list == "string")
        delete this.filterByKeyword[keyword];
       else {
        var index = list.indexOf(filter.text);
        if (index >= 0) {
          list.splice(index, 1);
          if (list.length == 1)
            this.filterByKeyword[keyword] = list[0];
        }
      }
      delete this.keywordByFilter[filter.text];
    }
    ,
    findKeyword: function (filter) {
      var defaultResult = (filter.contentType & RegExpFilter.typeMap.DONOTTRACK ? "donottrack" : "");
      var text = filter.text;
      if (Filter.regexpRegExp.test(text))
        return defaultResult;
      if (Filter.optionsRegExp.test(text))
        text = RegExp.leftContext;
      if (text.substr(0, 2) == "@@")
        text = text.substr(2);
      var candidates = text.toLowerCase().match(/[^a-z0-9%*][a-z0-9%]{3,}(?=[^a-z0-9%*])/g);
      if (!candidates)
        return defaultResult;
      var hash = this.filterByKeyword;
      var result = defaultResult;
      var resultCount = 16777215;
      var resultLength = 0;
      for (var i = 0, l = candidates.length;
      i < l; i++) {
        var candidate = candidates[i].substr(1);
        var count;
        switch (typeof hash[candidate]) {
          case "undefined": {
            count = 0;
            break;
          }
          case "string": {
            count = 1;
            break;
          }
          default: {
            count = hash[candidate].length;
            break;
          }
        }
        if (count < resultCount || (count == resultCount && candidate.length > resultLength)) {
          result = candidate;
          resultCount = count;
          resultLength = candidate.length;
        }
      }
      return result;
    }
    ,
    hasFilter: function (filter) {
      return (filter.text in this.keywordByFilter);
    }
    ,
    getKeywordForFilter: function (filter) {
      if (filter.text in this.keywordByFilter)
        return this.keywordByFilter[filter.text];
       else
        return null;
    }
    ,
    _checkEntryMatch: function (keyword, location, contentType, docDomain, thirdParty) {
      var list = this.filterByKeyword[keyword];
      if (typeof list == "string") {
        var filter = Filter.knownFilters[list];
        return (filter.matches(location, contentType, docDomain, thirdParty) ? filter : null);
      }
       else {
        for (var i = 0, l = list.length;
        i < l; i++) {
          var filter = Filter.knownFilters[list[i]];
          if (filter.matches(location, contentType, docDomain, thirdParty))
            return filter;
        }
        return null;
      }
    }
    ,
    matchesAny: function (location, contentType, docDomain, thirdParty) {
      var candidates = location.toLowerCase().match(/[a-z0-9%]{3,}/g);
      if (candidates === null)
        candidates = [];
      if (contentType == "DONOTTRACK")
        candidates.unshift("donottrack");
       else
        candidates.push("");
      for (var i = 0, l = candidates.length;
      i < l; i++) {
        var substr = candidates[i];
        if (substr in this.filterByKeyword) {
          var result = this._checkEntryMatch(substr, location, contentType, docDomain, thirdParty);
          if (result)
            return result;
        }
      }
      return null;
    }
    ,
    toCache: function (cache) {
      cache.filterByKeyword = this.filterByKeyword;
    }
    ,
    fromCache: function (cache) {
      this.filterByKeyword = cache.filterByKeyword;
      this.filterByKeyword.__proto__ = null;
      delete this.keywordByFilter;
      this.__defineGetter__("keywordByFilter", function () {
        var result = {
          __proto__: null
        };
        for (var k in this.filterByKeyword) {
          var list = this.filterByKeyword[k];
          if (typeof list == "string")
            result[list] = k;
           else
            for (var i = 0, l = list.length;
            i < l; i++)
              result[list[i]] = k;
        }
        return this.keywordByFilter = result;
      }
      );
      this.__defineSetter__("keywordByFilter", function (value) {
        delete this.keywordByFilter;
        return this.keywordByFilter = value;
      }
      );
    }
    
  };
  function CombinedMatcher() {
    this.blacklist = new Matcher();
    this.whitelist = new Matcher();
    this.resultCache = {
      
    };
  }
  CombinedMatcher.maxCacheEntries = 1000;
  CombinedMatcher.prototype = {
    blacklist: null,
    whitelist: null,
    resultCache: null,
    cacheEntries: 0,
    clear: function () {
      this.blacklist.clear();
      this.whitelist.clear();
      this.resultCache = {
        
      };
      this.cacheEntries = 0;
    }
    ,
    add: function (filter) {
      if (filter instanceof WhitelistFilter)
        this.whitelist.add(filter);
       else
        this.blacklist.add(filter);
      if (this.cacheEntries > 0) {
        this.resultCache = {
          
        };
        this.cacheEntries = 0;
      }
    }
    ,
    remove: function (filter) {
      if (filter instanceof WhitelistFilter)
        this.whitelist.remove(filter);
       else
        this.blacklist.remove(filter);
      if (this.cacheEntries > 0) {
        this.resultCache = {
          
        };
        this.cacheEntries = 0;
      }
    }
    ,
    findKeyword: function (filter) {
      if (filter instanceof WhitelistFilter)
        return this.whitelist.findKeyword(filter);
       else
        return this.blacklist.findKeyword(filter);
    }
    ,
    hasFilter: function (filter) {
      if (filter instanceof WhitelistFilter)
        return this.whitelist.hasFilter(filter);
       else
        return this.blacklist.hasFilter(filter);
    }
    ,
    getKeywordForFilter: function (filter) {
      if (filter instanceof WhitelistFilter)
        return this.whitelist.getKeywordForFilter(filter);
       else
        return this.blacklist.getKeywordForFilter(filter);
    }
    ,
    isSlowFilter: function (filter) {
      var matcher = (filter instanceof WhitelistFilter ? this.whitelist : this.blacklist);
      if (matcher.hasFilter(filter))
        return !matcher.getKeywordForFilter(filter);
       else
        return !matcher.findKeyword(filter);
    }
    ,
    matchesAnyInternal: function (location, contentType, docDomain, thirdParty) {
      var candidates = location.toLowerCase().match(/[a-z0-9%]{3,}/g);
      if (candidates === null)
        candidates = [];
      if (contentType == "DONOTTRACK")
        candidates.unshift("donottrack");
       else
        candidates.push("");
      var blacklistHit = null;
      for (var i = 0, l = candidates.length;
      i < l; i++) {
        var substr = candidates[i];
        if (substr in this.whitelist.filterByKeyword) {
          var result = this.whitelist._checkEntryMatch(substr, location, contentType, docDomain, thirdParty);
          if (result)
            return result;
        }
        if (substr in this.blacklist.filterByKeyword && blacklistHit === null)
          blacklistHit = this.blacklist._checkEntryMatch(substr, location, contentType, docDomain, thirdParty);
      }
      return blacklistHit;
    }
    ,
    matchesAny: function (location, contentType, docDomain, thirdParty) {
      var key = location + " " + contentType + " " + docDomain + " " + thirdParty;
      if (key in this.resultCache)
        return this.resultCache[key];
      var result = this.matchesAnyInternal(location, contentType, docDomain, thirdParty);
      if (this.cacheEntries >= CombinedMatcher.maxCacheEntries) {
        this.resultCache = {
          
        };
        this.cacheEntries = 0;
      }
      this.resultCache[key] = result;
      this.cacheEntries++;
      return result;
    }
    ,
    toCache: function (cache) {
      cache.matcher = {
        whitelist: {
          
        },
        blacklist: {
          
        }
      };
      this.whitelist.toCache(cache.matcher.whitelist);
      this.blacklist.toCache(cache.matcher.blacklist);
    }
    ,
    fromCache: function (cache) {
      this.whitelist.fromCache(cache.matcher.whitelist);
      this.blacklist.fromCache(cache.matcher.blacklist);
    }
    
  };
  var defaultMatcher = new CombinedMatcher();
  if (typeof _patchFunc0 != "undefined")
    eval("(" + _patchFunc0.toString() + ")()");
  window.Matcher = Matcher;
  window.CombinedMatcher = CombinedMatcher;
  window.defaultMatcher = defaultMatcher;
}
)(window.MatcherPatch);
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

var TagToType = {
  "SCRIPT": "SCRIPT",
  "IMG": "IMAGE",
  "LINK": "STYLESHEET",
  "OBJECT": "OBJECT",
  "EMBED": "OBJECT",
  "IFRAME": "SUBDOCUMENT"
};

// Merely listening to the beforeload event messes up various websites (see
// http://code.google.com/p/chromium/issues/detail?id=56204#c10 and
// https://bugs.webkit.org/show_bug.cgi?id=45586). So for these cases we avoid
// listening to beforeload and instead depend on handleNodeInserted() in
// blocker.js to get rid of ads by element src URL.
// Unfortunately we can't do this with filter rules because we would need to query the backend to
// check our domain, which cannot respond in time due to the lack of synchronous message passing.
var BEFORELOAD_MALFUNCTION_DOMAINS = {
  "t.sina.com.cn": true,
  "prazsketramvaje.cz": true,
  "xnachat.com": true,
  "www.tuenti.com": true,
  "www.nwjv.de": true,
  "www.redfin.com": true,
  "www.nubert.de": true,
  "shop.ww.kz": true,
  "www.shop.ww.kz": true,
  "www.meinvz.net": true,
  "www.studivz.net": true,
  "www.schuelervz.net": true,
  "www.wien.gv.at": true,
  "rezitests.ro": true,
  "www.rezitests.ro": true,
  "www.lojagloboesporte.com": true,
  "www.netshoes.com.br": true,
  "victorinox.com": true,
  "www.victorinox.com": true,
  "www.edmontonjournal.com": true,
  "www.timescolonist.com": true,
  "www.theprovince.com": true,
  "www.vancouversun.com": true,
  "www.calgaryherald.com": true,
  "www.leaderpost.com": true,
  "www.thestarphoenix.com": true,
  "www.windsorstar.com": true,
  "www.ottawacitizen.com": true,
  "www.montrealgazette.com": true,
  "shop.advanceautoparts.com": true,
  "www.clove.co.uk": true,
  "www.e-shop.gr": true,
  "www.ebuyer.com": true,
  "www.satchef.de": true,
  "www.brueckenkopf-online.com": true,
  "bestrepack.net": true,
  "www.bestrepack.net": true
};
var workaroundBeforeloadMalfunction = document.domain in BEFORELOAD_MALFUNCTION_DOMAINS;

var SELECTOR_GROUP_SIZE = 20;

var savedBeforeloadEvents = new Array();

// Makes a string containing CSS rules for elemhide filters
function generateElemhideCSSString(selectors)
{
  if (!selectors)
    return "";

  // WebKit apparently chokes when the selector list in a CSS rule is huge.
  // So we split the elemhide selectors into groups.
  var result = [];
  for (var i = 0; i < selectors.length; i += SELECTOR_GROUP_SIZE)
  {
    selector = selectors.slice(i, i + SELECTOR_GROUP_SIZE).join(", ");
    result.push(selector + " { display: none !important; }");
  }
  return result.join(" ");
}

// Hides a single element
function nukeSingleElement(elt) {
  if(elt.innerHTML)
    elt.innerHTML = "";
  if(elt.innerText)
    elt.innerText = "";
  elt.style.display = "none";
  elt.style.visibility = "hidden";
  // If this is a LINK tag, it's probably a stylesheet, so disable it. Actually removing
  // it seems to intermittently break page rendering.
  if(elt.localName && elt.localName.toUpperCase() == "LINK")
    elt.setAttribute("disabled", "");
}

// This function Copyright (c) 2008 Jeni Tennison, from jquery.uri.js
// and licensed under the MIT license. See jquery-*.min.js for details.
function removeDotSegments(u) {
  var r = '', m = [];
  if (/\./.test(u)) {
    while (u !== undefined && u !== '') {
      if (u === '.' || u === '..') {
        u = '';
      } else if (/^\.\.\//.test(u)) { // starts with ../
        u = u.substring(3);
      } else if (/^\.\//.test(u)) { // starts with ./
        u = u.substring(2);
      } else if (/^\/\.(\/|$)/.test(u)) { // starts with /./ or consists of /.
        u = '/' + u.substring(3);
      } else if (/^\/\.\.(\/|$)/.test(u)) { // starts with /../ or consists of /..
        u = '/' + u.substring(4);
        r = r.replace(/\/?[^\/]+$/, '');
      } else {
        m = u.match(/^(\/?[^\/]*)(\/.*)?$/);
        u = m[2];
        r = r + m[1];
      }
    }
    return r;
  } else {
    return u;
  }
}

// Does some degree of URL normalization
function normalizeURL(url)
{
  var components = url.match(/(.+:\/\/.+?)\/(.*)/);
  if(!components)
    return url;
  var newPath = removeDotSegments(components[2]);
  if(newPath.length == 0)
    return components[1];
  if(newPath[0] != '/')
    newPath = '/' + newPath;
  return components[1] + newPath;
}

// Converts relative to absolute URL
// e.g.: foo.swf on http://example.com/whatever/bar.html
//  -> http://example.com/whatever/foo.swf 
function relativeToAbsoluteUrl(url) {
  // If URL is already absolute, don't mess with it
  if(!url || url.match(/^http/i))
    return url;
  // Leading / means absolute path
  if(url[0] == '/') {
    return document.location.protocol + "//" + document.location.host + url;
  }
  // Remove filename and add relative URL to it
  var base = document.baseURI.match(/.+\//);
  if(!base)
    return document.baseURI + "/" + url;
  return base[0] + url;
}

// Extracts a domain name from a URL
function extractDomainFromURL(url)
{
  if(!url)
    return "";

  var x = url.substr(url.indexOf("://") + 3);
  x = x.substr(0, x.indexOf("/"));
  x = x.substr(x.indexOf("@") + 1);
  colPos = x.indexOf(":");
  if(colPos >= 0)
    x = x.substr(0, colPos);
  return x;
}

// Primitive third-party check, needs to be replaced by something more elaborate
// later.
function isThirdParty(requestHost, documentHost)
{
  // Remove trailing dots
  requestHost = requestHost.replace(/\.+$/, "");
  documentHost = documentHost.replace(/\.+$/, "");

  // Extract domain name - leave IP addresses unchanged, otherwise leave only
  // the last two parts of the host name
  var documentDomain = documentHost
  if (!/^\d+(\.\d+)*$/.test(documentDomain) && /([^\.]+\.[^\.]+)$/.test(documentDomain))
    documentDomain = RegExp.$1;
  if (requestHost.length > documentDomain.length)
    return (requestHost.substr(requestHost.length - documentDomain.length - 1) != "." + documentDomain);
  else
    return (requestHost != documentDomain);
}

// This beforeload handler is used before we hear back from the background process about
// whether we're enabled etc. It saves the events so we can replay them to the normal
// beforeload handler once we know whether we're enabled - to catch ads that might have
// snuck by.
function saveBeforeloadEvent(e) {
  savedBeforeloadEvents.push(e);
}

/**
 * Tests whether a request needs to be blocked.
 */
function shouldBlock(/**String*/ url, /**String*/ type)
{
  var url = relativeToAbsoluteUrl(url);
  var requestHost = extractDomainFromURL(url);
  var documentHost = window.location.hostname;
  var thirdParty = isThirdParty(requestHost, documentHost);
  var match = defaultMatcher.matchesAny(url, type, documentHost, thirdParty);
  return (match && match instanceof BlockingFilter);
}

/**
 * Responds to beforeload events by preventing load and nuking the element if
 * it's an ad.
 */
function beforeloadHandler(/**Event*/ e)
{
  if (shouldBlock(e.url, TagToType[e.target.localName.toUpperCase()]))
  {
    e.preventDefault();
    if (e.target)
      nukeSingleElement(e.target);
  }
}

if (!workaroundBeforeloadMalfunction)
{
  document.addEventListener("beforeload", saveBeforeloadEvent, true);
}

var elemhideElt = null;

// Make sure this is really an HTML page, as Chrome runs these scripts on just about everything
if (document.documentElement instanceof HTMLElement)
{
  chrome.extension.sendRequest({reqtype: "get-settings", matcher: true}, function(response)
  {
    document.removeEventListener("beforeload", saveBeforeloadEvent, true);

    if (response.enabled)
    {
      defaultMatcher.fromCache(JSON.parse(response.matcherData));

      if (!workaroundBeforeloadMalfunction)
      {
        document.addEventListener("beforeload", beforeloadHandler, true);

        // Replay the events that were saved while we were waiting to learn whether we are enabled
        for(var i = 0; i < savedBeforeloadEvents.length; i++)
          beforeloadHandler(savedBeforeloadEvents[i]);
        delete savedBeforeloadEvents;
      }
    }
  });

  chrome.extension.sendRequest({reqtype: "get-settings", selectors: true, host: window.location.hostname}, function(response)
  {
    if (response.selectors)
    {
      // Add a style element for elemhide selectors.
      elemhideElt = document.createElement("style");
      elemhideElt.innerText = generateElemhideCSSString(response.selectors);
      document.documentElement.appendChild(elemhideElt);
    }
  });
}
