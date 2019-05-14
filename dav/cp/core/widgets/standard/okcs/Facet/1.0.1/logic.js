 /* Originating Release: February 2019 */
RightNow.Widgets.Facet = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            if(this.data.attrs.toggle_title) {
                this.toggleEvent = true;
                this._addToggle();
                if (this.data.attrs.toggle_state === 'collapsed' && this._toggle !== null) {                    
                    this._toggle.addClass(this.data.attrs.collapsed_css_class);
                    this._onToggle(this);
                }
            }

            if (this.Y.one('.rn_ClearFacets')) {
                this.Y.one(this.baseSelector).delegate('click', this._onFacetClick, 'a.rn_FacetLink', this);
                this.Y.one(this.baseSelector).delegate('click', this._onFacetResetClick, 'a.rn_ClearFacets', this);
            }
            this.Y.all(".rn_ToggleExpandCollapse").on('click', this.toggleExpandCollapse, this);
            this._newSearch = true;
            this._facet = '';
            this._searchType = 'PAGE';
            RightNow.Event.subscribe("evt_selectedFacet", this._getFacetRequest, this);

            this.searchSource().setOptions(this.data.js.filter)
                .on('collect', this.sendOkcsNewSearch, this)
                .on('collect', this.updateFacet, this)
                .on('collect', this.updateSearchType, this)
                .on('send', this._searchInProgress, this)
                .on('response', this._onResultChanged, this);
        }
    },

    /**
    * Event handler to pass list of all selected facets.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
    _getFacetRequest: function(evt, args) 
    {
        var facetNodes = this.Y.all('.rn_ActiveFacet');
        var facet = '';
        if(facetNodes.size() > 0) {
            for (var i = 0; i < facetNodes.size(); ++i) {
                facet = facetNodes.pop().getAttribute('id') + ',';
            }
            facet = facet.substring(0, facet.length - 1);
        }
        RightNow.Event.fire("evt_facetResponse", new RightNow.Event.EventObject(this, {data : { facet : facet, pageLink : args[0].data.pageLink }}));
    },

    /**
    * Toggles the display of the element.
    */
    _addToggle: function() {
        this._toggle = this.Y.one(".rn_FacetsTitle");
        if (this._toggle !== null ) {
            this._toggle.appendChild(this.Y.Node.create("<span class='rn_Expand'></span>"));
            var current = this._toggle.next();
            if(current)
                this._itemToToggle = current;
            else
                return;
            this._currentlyShowing = this._toggle.hasClass(this.data.attrs.expanded_css_class) ||
                this._itemToToggle.getComputedStyle("display") !== "none";

            //trick to get voiceover to announce state to screen readers.
            this._screenReaderMessageCarrier = this._toggle.appendChild(this.Y.Node.create(
                "<img style='opacity: 0;' src='/euf/core/static/whitePixel.png' alt='" +
                    (this._currentlyShowing ? this.data.attrs.label_expanded : this.data.attrs.label_collapsed) + "'/>"));

            if(this.toggleEvent) {
                this.Y.one(this.baseSelector).delegate('click', this._onToggle, 'div.rn_FacetsTitle', this);
                this.toggleEvent = false;
            }
        }
    },
 
    /**
    * Toggles the display of the element.
    * @param clickEvent Event Click event
    */
    _onToggle: function(clickEvent) {
        var target = clickEvent.target, cssClassToAdd, cssClassToRemove;
        if(this._currentlyShowing) {
            cssClassToAdd = this.data.attrs.collapsed_css_class;
            cssClassToRemove = this.data.attrs.expanded_css_class;
            this._itemToToggle.setStyle("display", "none");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_collapsed);
        }
        else {
            cssClassToAdd = this.data.attrs.expanded_css_class;
            cssClassToRemove = this.data.attrs.collapsed_css_class;
            this._itemToToggle.setStyle("display", "block");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_expanded);
        }
        if(target) {
            target.addClass(cssClassToAdd)
                .removeClass(cssClassToRemove);
        }
        this._currentlyShowing = !this._currentlyShowing;
    },
    
    /**
    * Event handler received when search data is changing.
    * Clears the content during searches.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
    _searchInProgress: function(evt, args) 
    {
        var params = args[0],
            newSearch = (params.allFilters.facet === undefined);
        if (params && newSearch)
            this.Y.one(this.baseSelector + "_Content").get('childNodes').remove();
    },

   /**
    * Event Handler fired when a Clear facet Link is Clicked
    * @param {Object} evt Event object
    */
    _onFacetResetClick: function(evt)
    {
        evt.preventDefault();
        RightNow.Event.fire("evt_pageLoading");
        this._newSearch = false;
        this._facet = '';
        this._searchType = 'clearFacet';
        this.searchSource().fire('collect').fire('search');
    },
    
    /**
    * Event Handler fired when a facet is selected
    * @param {Object} evt Event object
    */
    _onFacetClick: function(evt)
    {
        evt.preventDefault();
        
        var selectedFacet = evt.target.get('id');
         /**
        * If user clicks on More link instead of actual facet.
        * More link has format of F:<parentFacetId>
        * In this case, we display children of <parentFacet> on the UI.
        * otherwise in else section, we just append facet filter and fire 'search' event to pull search results.
        */
        if (selectedFacet.indexOf('F:') !== -1) {
            var facets = RightNow.JSON.parse(this.data.js.facets);
            selectedFacet = selectedFacet.substring(2);
            selectedFacet = selectedFacet.indexOf('.') !== -1 ? selectedFacet.substr(0, selectedFacet.indexOf('.')) : selectedFacet;
            
            var list = this.Y.Node.create("<div class='rn_FacetsList'></div>");
            var title = this.Y.Node.create("<div class='rn_FacetsTitle'></div>");
            title.append(this.data.attrs.label_filter);
            var clearLink = this.Y.Node.create("<span class='rn_ClearContainer'>[<a class='rn_ClearFacets'><span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_clear_screenreader + "</span>" + this.data.attrs.label_clear + "</a>]</span>"); 
            title.append(clearLink);

            for (var i = 0; facets[i]; i++) {
                if (facets[i].id === selectedFacet) {
                    var parentLi = null,
                        item = parentLi = this.Y.Node.create("<li>" + facets[i].desc + "</li>"),
                        ul = this.Y.Node.create("<ul></ul>");
                    ul.append(item);
                    if (facets[i].children.length !== 0)
                        this._findChildren(facets[i], parentLi);
                }
            }
            list.append(ul);
            this.Y.one(this.baseSelector + "_Content").get('childNodes').remove();
            this.Y.one(this.baseSelector + "_Content").append(title).append(list);

            if(this.data.attrs.toggle_title)
                this._addToggle();
        }
        else 
        {
            RightNow.Event.fire("evt_pageLoading");
            this._facet = selectedFacet;
            this._searchType = 'FACET';
            this.searchSource().fire('collect').fire('search');
        }
    },

    /** 
    * This method is called when response event is fired..
    * @param {object} filter object
    * @param {object} event object
    */
    _onResultChanged: function(type, args) {
        this._searchType = 'PAGE';
        this.Y.one(this.baseSelector + "_Content").get('childNodes').remove();
        if (!args[0] || !args[0].data.searchResults) return;
        if (args[0].data.error === undefined || args[0].data.error.length === 0) {
            if (args[0] && (args[0].data.searchResults === null || args[0].data.searchResults.results === null || args[0].data.searchResults.results.facets === null || args[0].data.searchResults.results.facets.length === 0)) return;

            var facets = args[0].data.searchResults.results.facets;
            var title = this.Y.Node.create("<div class='rn_FacetsTitle'></div>");
            title.append(this.data.attrs.label_filter);
            var clearLink = this.Y.Node.create("<span class='rn_ClearContainer'>[<a class='rn_ClearFacets'><span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_clear_screenreader + "</span>" + this.data.attrs.label_clear + "</a>]</span>"); 
            title.append(clearLink);
            var list = this.Y.Node.create("<div class='rn_FacetsList'></div>");
            var ul = this.Y.Node.create("<ul></ul>"),
                parentLi = null,
                item = null;

            for (var i = 0; i < facets.length; i++) {
                var currentFacet = facets[i],
                    displayText = currentFacet.desc,
                    facetChildren = currentFacet.children.length;

                if (facetChildren !== 0) {
                    item = this.Y.Node.create("<li>" + displayText + "</li>");
                    if (displayText) {
                        parentLi = this.Y.Node.create("<ul></ul>");
                        item = item.append(parentLi);
                    }
                    ul.append(item);
                    this._findChildren(currentFacet, parentLi, this.data.attrs.max_sub_facet_size);
                }
            }

            list.append(ul);
            this.Y.one(this.baseSelector + "_Content").append(title).append(list);
            this.Y.all(".rn_ToggleExpandCollapse").on('click', this.toggleExpandCollapse, this);
            RightNow.Event.fire("evt_pageLoaded");

            if(this.data.attrs.toggle_title)
                this._addToggle();
        }
        else {
            var error = args[0].data.error,
                errorMessage = '<div id="' + this.baseDomID + '_Error" class="rn_ErrorMessage">' + error.errorCode + ': ' + error.externalMessage + ' - ' + error.source + '</div>';
            this.Y.one(this.baseSelector + "_Content").append(errorMessage);
        }
    },

    /** 
    *   This method renders child facets
    *   @param {Object} current selected facet
    *   @param {boolean} true if current facet has child facet.
    */
    _processChildren : function(currentFacet, hasChildren) {
        var facetLinkClass = currentFacet.inEffect ? 'rn_FacetLink rn_ActiveFacet' : 'rn_FacetLink',
            selectedFacetText = " <span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_active_filter_screenreader + "</span>",
            currentFacetDescription = (facetLinkClass === 'rn_FacetLink rn_ActiveFacet') ? currentFacet.desc + selectedFacetText : currentFacet.desc,
            item = this.Y.Node.create("<li></li>"),
            spanExp = hasChildren ? "<span class='rn_ToggleExpandCollapse rn_CategoryExplorerExpanded'></span>" : "";

        item.append(this.Y.Node.create("<a class='" + facetLinkClass + "' id='" + currentFacet.id + "' href='javascript:void(0)'>" + spanExp + currentFacetDescription + "</a>"));

        return item;
    },

    /** 
    *   This method iterates the child facets recursively
    *   @param {Object} current selected facet
    *   @param {String} parent facet list node
    *   @param {int} maximum depth of facet to be looked
    */	
    _findChildren : function(currentFacet, parentLi, maxFacetLength) {
        var currFacet, len = currentFacet.children.length;
        len = (maxFacetLength !== undefined && maxFacetLength !== '') ? maxFacetLength : len;
        for (var i = 0; i < len; ++i) {
            currFacet = currentFacet.children[i];
            if (currFacet !== undefined) {
                if (currFacet.children.length !== 0) {
                    var childLi = this._processChildren(currFacet, true);
                    parentLi.append(childLi);
                    var childUl = this.Y.Node.create("<ul nodeid='" + currFacet.id + "' class='rn_FacetTreeIndent'></ul>");
                    childLi.append(childUl);
                    this._findChildren(currFacet, childUl, len);
                } else {
                    parentLi.append(this._processChildren(currFacet, false));
                }
            }
        }
        if (currentFacet.children.length > maxFacetLength) {
            var item = this.Y.Node.create("<ul></ul>").append(this.Y.Node.create("<li></li>")
                    .append(new EJS({text: this.getStatic().templates.facetLink}).render({currentFacet: currentFacet, attrs: this.data.attrs})));
            parentLi.append(item);
        }
    },

    /**
    * Collects newSearch filter
    * @return {object} Event object
    */
    sendOkcsNewSearch: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._newSearch, key: 'okcsNewSearch', type: 'okcsNewSearch'}
        });
    },

    /**
    * Collects facet filter
    * @return {object} Event object
    */
    updateFacet: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._facet, key: 'facet', type: 'facet'}
        });
    },

    /**
    * Collects searchType filter
    * @return {object} Event object
    */
    updateSearchType: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._searchType, key: 'searchType', type: 'searchType'}
        });
    },

    /**
    * Toggles the expand/collapse view for facet hierarchy
    */
    toggleExpandCollapse: function(e) {
        e.preventDefault();
        e.stopPropagation();
        if(e.target.hasClass('rn_CategoryExplorerExpanded')) {
            this.Y.one("ul[nodeid='" + e.target.get('parentNode').get('id') + "']").hide();
            e.target.removeClass('rn_CategoryExplorerExpanded');
            e.target.addClass('rn_CategoryExplorerCollapsed');
        }
        else if(e.target.hasClass('rn_CategoryExplorerCollapsed')) {
            this.Y.one("ul[nodeid='" + e.target.get('parentNode').get('id') + "']").show();
            e.target.removeClass('rn_CategoryExplorerCollapsed');
            e.target.addClass('rn_CategoryExplorerExpanded');
        }
    }
});
