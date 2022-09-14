 /* Originating Release: February 2019 */
RightNow.Widgets.OkcsPagination = RightNow.Widgets.SourcePagination.extend({
    overrides: {
        constructor: function () {
            this.parent();
            this._pageDirection = '';
            this._loadingDiv = this.Y.one('#' + this.data.attrs.dom_id_loading_icon);
            this.Y.one(this.baseSelector).delegate('click', this.onPageClick, 'a', this);
            this.searchSource().setOptions(this.data.js.sources).on('response', this.onSearchComplete, this);
            if (this.data.js.okcsAction !== 'browse') {
                RightNow.Event.subscribe("evt_facetResponse", this._selectedFacetResponse, this);
                this.searchSource().on('collect', this.sendOkcsPageDirection, this)
                                   .on('collect', this.sendFilterPage, this);
            }
            if(this.data.js.okcsAction === 'browse')
                this.searchSource().on('collect', this.sendOkcsBrowseAction, this)
                .on('collect', this.sendOkcsBrowsePage, this);

            this.Y.one(this.baseSelector).delegate('mouseup', this._onRightClick, 'li a', this);
        },

        /**
         * Fires an event to fetch list of selected factes.
         * @param  {object} e Click event
        */
        _onRightClick: function (e) {
            if (e.button === 3) {
                RightNow.Event.fire("evt_selectedFacet", new RightNow.Event.EventObject(this, {data : { pageLink : e }}));
            }
        },

        /**
         * Event handler when returning from ajax data request
         * @param {string} type Event name
         * @param {array} args Event arguments
         */
        _selectedFacetResponse: function(type, args) {
            var facet = args[0].data.facet,
                e = args[0].data.pageLink,
                currentPageNumber = parseInt(this.data.js.currentPage, 10) + 1;
            var urlParameters = location.pathname.split("/");
            if(this.data.js.okcsAction !== "browse"){
                if(RightNow.Url.getParameter('searchType') === 'newTab'){
                    currentPageNumber = parseInt(RightNow.Url.getParameter('page'), 10);
                }
            }
            this._pageDirection = e.currentTarget.getAttribute('data-rel');
            var navigatePage = this._pageDirection === 'next' ? (currentPageNumber + 1) : (currentPageNumber - 1);
            var navigationUrl = location.origin + location.pathname.split("/").slice(0, RightNow.Url.getParameterSegment() - 1).join("/");
            for(var i = 1; i < urlParameters.length; i = i + 2) {
                if(urlParameters[i] === 'kw')
                    navigationUrl += '/kw/' + urlParameters[i + 1];
                else if(urlParameters[i] === 'loc')
                    navigationUrl += '/loc/' + urlParameters[i + 1];
            }
            if(facet !== '')
                navigationUrl = RightNow.Url.addParameter(navigationUrl, 'facet', facet);
            navigationUrl += '/page/' + navigatePage + '/searchType/newTab';
            e.currentTarget.setAttribute('href', navigationUrl); 
        },

        /**
         * Triggers a new search when a pagination link is clicked.
         * @param  {object} e Click event
         */
        onPageClick: function (e) {
            e.halt();
            this.searchSource().initialFilters = {};
            RightNow.Event.fire("evt_pageLoading");
            var pageNumber = this.data.js.currentPage;
            var urlParameters = location.pathname.split("/");
            if (this.data.js.okcsAction === "browse") {
                this._pageDirection = e.currentTarget.getAttribute('data-rel');
                var navigatePage = this._pageDirection === 'next' ? (pageNumber + 1) : (pageNumber - 1);
                if (!('pushState' in window.history))
                    this.data.js.currentPage = this.data.js.currentPage - 1;
                this._pageDirection = (this._pageDirection === 'next') ? 'forward' : 'backward';
                this.triggerOkcsBrowsePaginate(navigatePage);
                this.data.js.browsePaginate = false;
                var pageNumber = this.determinePageNumber(e.currentTarget.getAttribute('data-rel'));
                return false;
            }
            if(RightNow.Url.getParameter('searchType') === 'newTab'){
                pageNumber = parseInt(RightNow.Url.getParameter('page'), 10) - 1;
            }
            this._pageDirection = e.currentTarget.getAttribute('data-rel');
            this._pageDirection = (this._pageDirection === 'next') ? 'forward' : 'backward';
            var navigatePage = pageNumber + 1;
            if(navigatePage === 0)
                navigatePage = '';
            this.data.js.filter.value = navigatePage;
            this.searchSource().fire('collect');
            this.triggerSearch();
            return false;
        },
        
        /**
         * Re-renders when new search results arrive.
         * @param  {string} evt  Event name
         * @param  {object} args Event object
         */
        onSearchComplete: function (evt, args) {
            this._pageDirection = '0';
            var result = args[0].data,
                previousLink = ''
                nextLink = ''
                pages = '';

            if (!result.error) {
                if(result.searchResults){
                    var page = result.searchResults.page,
                        pageMore = result.searchResults.pageMore;
                        this.data.js.currentPage = page;
                    if (result.searchResults.page !== this.data.js.filter.value)
                        this.data.js.filter.value = result.searchResults.page;

                    if (page > 0) {
                        var previousPage = this.data.js.filter.value - 1;
                        previousLink = new EJS({ text: this.getStatic().templates.navigationLink }).render({
                            href: this.url(previousPage),
                            iconClass: 'icon-chevron-left',
                            rel: 'previous',
                            label: RightNow.Interface.getMessage('PREVIOUS_LBL'),
                            className: 'rn_PreviousPage'
                        });
                    }
                    if (pageMore > 0) {
                        var nextPage = this.data.js.filter.value + 1;
                        nextLink = new EJS({ text: this.getStatic().templates.navigationLink }).render({
                            href: this.url(nextPage),
                            iconClass: 'icon-chevron-right',
                            rel: 'next',
                            label: RightNow.Interface.getMessage('NEXT_LBL'),
                            className: 'rn_NextPage'
                        });
                    }
                }
                else if(result.articles){
                    var page = result.currentPage,
                        hasMore = result.hasMore;
                    this.data.js.currentPage = page;
                    if (page > 0) {
                        var previousPage = page - 1;
                        previousLink = new EJS({ text: this.getStatic().templates.navigationLink }).render({
                            href: this.url(previousPage),
                            iconClass: 'icon-chevron-left',
                            rel: 'previous',
                            label: RightNow.Interface.getMessage('PREVIOUS_LBL'),
                            className: 'rn_PreviousPage'
                        });
                    }
                    if (hasMore > 0) {
                        var nextPage = page + 1;
                        nextLink = new EJS({ text: this.getStatic().templates.navigationLink }).render({
                            href: this.url(nextPage),
                            iconClass: 'icon-chevron-right',
                            rel: 'next',
                            label: RightNow.Interface.getMessage('NEXT_LBL'),
                            className: 'rn_NextPage'
                        });
                    }
                    RightNow.Event.fire("evt_pageLoaded");
                    this.Y.one("#rn_OkcsAnswerList").show();
                }
            }
            this.Y.one(this.baseSelector + ' ul').setHTML(previousLink + pages + nextLink);
        }
    },
    
    /**
    * This function collects all the filters and perform search.
    * @param navigatePage string requestedPage
    */
    triggerOkcsBrowsePaginate: function (navigatePage){
        this.data.js.browsePaginate = true;
        this.searchSource().fire('collect').fire("search");
    },
    
    /**
    * Collects page direction filter
    * @return {object} Event object
    */
    sendOkcsPageDirection: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._pageDirection, key: 'dir', type: 'direction'}
        });
    },
    
    /**
    * Collects selected channelRecordID filter
    * @return {object} Event object
    */
    sendOkcsBrowseChannel: function() {
        if(!this.data.js.browsePaginate) return;
        return new RightNow.Event.EventObject(this, {
            data: {value: this.Y.one('.rn_ChannelSelected')._node.id, key: 'channelRecordID', type: 'channelRecordID'}
        });
    },
    
    /**
    * Collects browseAction filter
    * @return {object} Event object
    */
    sendOkcsBrowseAction: function() {
        if(!this.data.js.browsePaginate) return;
        return new RightNow.Event.EventObject(this, {
            data: {value: 'paginate', key: 'browseAction', type: 'browseAction'}
        });
    },
    
    /**
    * Collects browsePage filter
    * @return {object} Event object
    */
    sendOkcsBrowsePage: function() {
        if(!this.data.js.browsePaginate) return;
        var currPageNumber = this.data.js.currentPage;
        var navigatePage = this._pageDirection === 'forward' ? (currPageNumber + 1) : (currPageNumber - 1);
        return new RightNow.Event.EventObject(this, {
            data: {value: navigatePage, key: 'browsePage', type: 'browsePage'}
        });
    },

    /**
    * Collects pagedirection filter
    * @return {object} Event object
    */
    sendFilterPage: function() {
        var currPageNumber = this.data.js.currentPage;
        var navigatePage = this._pageDirection === 'forward' ? (currPageNumber + 1) : (currPageNumber - 1);
        return new RightNow.Event.EventObject(this, {
            data: {value: navigatePage, key: 'page', type: 'page'}
        });
    }
});
