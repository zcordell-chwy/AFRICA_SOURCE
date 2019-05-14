RightNow.namespace('Custom.Widgets.display.ItemPopupGallery');
Custom.Widgets.display.ItemPopupGallery = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function(){
        this.prevLink = null;
        this.nextLink = null;
        this.lastSelectedPageLink = null;
        this.lastSelectedPage = null;
        this.itemsByID = null;
        this.itemMetadataByPage = null;

        this.toggleLoadingIndicator("show");
        var itemMetadata = this.data.js.itemMetadata;
        this.cacheItemMetadata(itemMetadata);
        if(itemMetadata.totalPages > 1){
            this.buildPaginationControl(itemMetadata);
        }
        this.loadPage(itemMetadata.page);
    },

    /**
    * Caches item metadata associated with a particular page. There are two indexes for retrieving items from the cache: itemsByID 
    * (used to retrieve item data by id) and itemMetadataByPage (used to retrieve item metadata by page number).
    * @param {object} itemMetadata the item metadata for the current page, which includes the list of item data, 
    *                              the current page number and the total number of pages
    */
    cacheItemMetadata: function(itemMetadata){
        if(this.itemsByID === null) this.itemsByID = {};
        if(this.itemMetadataByPage === null) this.itemMetadataByPage = {};
        this.Y.each(itemMetadata.items, function(item){
            this.cacheItemData(item);
        }, this);
        this.itemMetadataByPage[itemMetadata.page] = itemMetadata;
    },

    cacheItemData: function(item){
        if(this.itemsByID === null) this.itemsByID = {};
        this.itemsByID[item.id] = item;
    },

    /**
    * Loads a page of items given a page number, trying first to load item metadata data for the page from the item cache, and failing
    * that calling an AJAX routine to load the item metadata.
    * @param {integer} page the page number to load
    */
    loadPage: function(page){
        // Check if we have item data for page cached
        var itemMetadata = this.itemMetadataByPage[page];
        if(itemMetadata != null){ // We have item data for page cached, build items and update pagination
            this.toggleLoadingIndicator("show");
            this.buildItems(itemMetadata.items);
            this.toggleLoadingIndicator("hide");
        }else{ // Item data for this page not in cache, need to fetch it via AJAX
            this.toggleLoadingIndicator("show");
            this.getItemMetadataForPage(page);
        }
        if(this.itemMetadataByPage[1].totalPages > 1){
            this.updatePaginationControl(page, this.itemMetadataByPage[1].totalPages);
        }
        // On mobile, scroll to top of gallery each time we load a new page for maximum usability
        if($(window).width() < 700){
            $(window).scrollTop(300);
        }
    },

    /**
     * Delegates building the DOM content for the collection of items on the current page.
     * @param {array} items an array of items
     */
    buildItems: function(items){
        var itemsContainerNode = this.Y.one(this.baseSelector + " .rn_ItemGalleryItemsContainer"),
            rowStartIndex = 0;

        // Reset items container node HTML
        itemsContainerNode.setHTML("");

        while(rowStartIndex < items.length){
            rowEndIndex = Math.min(rowStartIndex + this.data.js.columns - 1, items.length - 1);
            this.buildItemRow(items.slice(rowStartIndex, rowEndIndex + 1), itemsContainerNode);
            rowStartIndex += this.data.js.columns;
        }
    },

    /**
    * Delegates building the DOM content for a row of items on the current page.
    * @param {array} items the items in the row
    * @param {object} container the YUI DOM node acting as the container for the row
    */
    buildItemRow: function(items, container){
        var itemRowNode = this.Y.Node.create("<div class=\"rn_ItemGalleryItemRow\"></div>");

        this.Y.each(items, function(item){
            var itemNode = this.buildItem(item),
                dynamicWidth = Math.floor(100 / this.data.js.columns);
            itemNode.setStyle("width", dynamicWidth + "%");
            itemRowNode.append(itemNode);
        }, this);

        container.append(itemRowNode);
    },

    /**
    * Builds the YUI DOM node for an item and registers the click handler.
    * @param {object} item the item data object
    */
    buildItem: function(item){
        var itemHTML = new EJS({text: this.getStatic().templates.item}).render({
            id: item.id,
            data: item.data
        });
        
        var itemNode = this.Y.Node.create(itemHTML);

        itemNode.on("click", function(evt){
            var itemNode = evt.currentTarget,
                itemID = parseInt(itemNode.getAttribute("data-id")),
                item = this.itemsByID[itemID];

            if(this.data.js.doPreload){
                this.showItemPopup(item);
            }else{
                if(item.isPopupItemDataCached){
                    this.showItemPopup(item);
                }else{
                    this.getItemPopupDataByID(item.id);
                }
            }
        }, this);

        return itemNode;
    },

    /**
    * Builds the DOM content for the pagination control.
    * @param {object} itemMetadata the item metadata for the current page, which includes the list of item data, 
    *                              the current page number and the total number of pages
    */
    buildPaginationControl: function(itemMetadata){
        var page = itemMetadata.page,
            totalPages = itemMetadata.totalPages,
            container = this.Y.one(this.baseSelector + " .rn_ItemGalleryPaginationContainer"),
            pageLinkHTML = "<a class=\"rn_ItemGalleryPaginationPageLink\" href=\"javascript: void(0);\"></a>";

        // Build previous link
        this.prevLink = this.Y.Node.create(pageLinkHTML);
        this.prevLink.addClass("rn_ItemGalleryPaginationPreviousLink");
        this.prevLink.setHTML("Previous");
        this.prevLink.on("click", this.handlePageLinkClick, this);
        container.append(this.prevLink);

        // Build page links
        for(var i = 1; i <= totalPages; i++){
            var pageLink = null;
            if(i === page){
                pageLink = this.Y.Node.create(pageLinkHTML);
                pageLink.addClass("rn_ItemGalleryPaginationSelectedPageLink");
            }else{
                pageLink = this.Y.Node.create(pageLinkHTML);
            }
            pageLink.setHTML(i);
            pageLink.setAttribute("data-page", i);
            pageLink.on("click", this.handlePageLinkClick, this);
            container.append(pageLink);
        }

        // Build next link
        this.nextLink = this.Y.Node.create(pageLinkHTML);
        this.nextLink.addClass("rn_ItemGalleryPaginationNextLink");
        this.nextLink.setHTML("Next");
        this.nextLink.on("click", this.handlePageLinkClick, this);
        container.append(this.nextLink);
    },

    /**
    * Updates the pagination control DOM content when a new page is loaded.
    * @param {integer} page the current page number
    * @param {integer} totalPages the total number of pages
    */
    updatePaginationControl: function(page, totalPages){
        var selectedPageLink = this.Y.one(
                this.Y.Selector.query(this.baseSelector + " a.rn_ItemGalleryPaginationPageLink[data-page=" + page + "]")[0]
            );

        // Update current page link
        if(this.lastSelectedPage !== page){
            if(this.lastSelectedPage !== null) this.lastSelectedPageLink.removeClass("rn_ItemGalleryPaginationSelectedPageLink");
            selectedPageLink.addClass("rn_ItemGalleryPaginationSelectedPageLink");
            this.lastSelectedPageLink = selectedPageLink;
            this.lastSelectedPage = page;
        }

        // Show/hide previous link
        if(page > 1){
            this.showPageLink(this.prevLink);
        }else{
            this.hidePageLink(this.prevLink);
        }

        // Show/hide next link
        if(page < totalPages){
            this.showPageLink(this.nextLink);
        }else{
            this.hidePageLink(this.nextLink);
        }
    },

    /**
    * Executes the logic for when a page link is clicked.
    * @param {object} evt the event object for the browser click event
    */
    handlePageLinkClick: function(evt){
        var pageLink = evt.currentTarget;

        if(pageLink.hasClass("rn_ItemGalleryPaginationPreviousLink")){ // Next link, load next page
            this.loadPage(this.lastSelectedPage - 1);
        }else if(pageLink.hasClass("rn_ItemGalleryPaginationNextLink")){ // Prev link, load prev page
            this.loadPage(this.lastSelectedPage + 1);
        }else{ // Normal page link 
            var page = parseInt(pageLink.getAttribute("data-page"));
            // If page link clicked different from last selected page, load page
            if(this.lastSelectedPage !== page){
                this.loadPage(page);
            }
        }
    },

    /**
    * Shows the popup dialog for an item.
    * @param {object} item the item data to display in the dialog
    */
    showItemPopup: function(item){
        var itemPopupDialog = this.buildItemPopupDialog(item);
        itemPopupDialog.show();
    },

    /**
    * Builds the YUI Panel to be used for the popup dialog.
    * @param {object} item the item data to display in the dialog
    */
    buildItemPopupDialog: function(item){
    	
    	//we need to know in the .ejs file if this is a gift page
    	isGiftPage = (this.data.info.controller_name == "GiftPopupGallery") ? true : false;
    	
        var itemPopupHTML = new EJS({text: this.getStatic().templates.itemPopup}).render({
                id: item.id,
                data: item.data,
                giftPage: isGiftPage, 
                loggedIn : this.data.js.isLoggedIn
            }),
            itemPopupNode = this.Y.Node.create(itemPopupHTML),
            container = this.Y.one(this.baseSelector + " .rn_ItemGalleryPopupDialogContainer");

        container.setHTML("");

        var itemPopupDialog = new this.Y.Panel({
            srcNode: itemPopupNode, 
            centered: true,
            render: container,
            modal: false,
            visible: false,
            zIndex: 9999,
            hideOn: [
                {
                    eventName: 'clickoutside'
                }
            ]
        });

        itemPopupDialog.removeButton("close");

        // Fire hook on popup dialog created, passing along the dialog content and dialog object itself, thus allowing inheriting class
        // to do custom event registration and manipulate the dialog
        this.onItemPopupDialogCreated(itemPopupNode, itemPopupDialog);

        return itemPopupDialog;
    },

    /**
    * Hook method, to be overridden by inheriting class, allowing for event registration on popup dialog content.
    * @param {object} itemPopupContent the YUI node for the outermost item popup dialog content container 
    * @param {object} itemPopupDialog the YUI panel object representing the dialog
    */
    onItemPopupDialogCreated: function(itemPopupContent, itemPopupDialog){
        // To be overridden by inheriting class
    },

    /**
    * Hides a page link in the pagination control.
    * @param {object} pageLink the page link to hide
    */
    hidePageLink: function(pageLink){
        pageLink.setStyle("visibility", "hidden");
    },

    /**
    * Shows a page link in the pagination control.
    * @param {object} pageLink the page link to show
    */
    showPageLink: function(pageLink){
        pageLink.setStyle("visibility", "visible");
    },

    /**
    * Toggles showing/hiding the loading indicator. If the loading indicator is shown, the item container will be hidden and
    * vice versa.
    * @param {string} showOrHide pass "show" to show the loading indicator or "hide" to hide it
    */
    toggleLoadingIndicator: function(showOrHide){
        var loadingIndicatorContainer = this.Y.one(this.baseSelector + " .rn_ItemGalleryLoadingIndicatorContainer"),
            itemContainer = this.Y.one(this.baseSelector + " .rn_ItemGalleryItemsContainer"),
            containerHeight = itemContainer.getStyle("height");

        if(showOrHide === "show"){
            loadingIndicatorContainer.setStyle("height", containerHeight);
            loadingIndicatorContainer.show();
            itemContainer.hide();
        }else{
            loadingIndicatorContainer.hide();
            itemContainer.show();
        }
    },

    /**
     * Makes an AJAX request for `getItemMetadataForPage`.
     * @param {integer} page the page number to request item metadata for
     */
    getItemMetadataForPage: function(page) {
        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            page: page
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.getItemMetadataForPage, eventObj.data, {
            successHandler: this.getItemMetadataForPageCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `getItemMetadataForPage`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getItemMetadataForPage
     */
    getItemMetadataForPageCallback: function(response, originalEventObj) {
        if(response.status === "success"){
            this.cacheItemMetadata(response.data);
            this.loadPage(response.data.page);
        }else{
            RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage('ERROR_PAGE_PLEASE_S_TRY_MSG'), {icon : "WARN"});
        }
    },

    /**
     * Makes an AJAX request for `getItemPopupDataByID`.
     * @param {integer} id the id of item to get popup data for
     */
    getItemPopupDataByID: function(id) {
        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            id: id
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.getItemPopupDataByID, eventObj.data, {
            successHandler: this.getItemPopupDataByIDCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `getItemPopupDataByID`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getItemPopupDataByID
     */
    getItemPopupDataByIDCallback: function(response, originalEventObj) {
        if(response.status === "success"){
            // Merge popup data into cached item data and set flag indicating popup data has been cached
            var popupItem = this.itemsByID[originalEventObj.data.id];
            this.Y.mix(popupItem.data, response.data);
            popupItem.isPopupItemDataCached = true;

            this.showItemPopup(popupItem);
        }else{
            RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage('ERROR_PAGE_PLEASE_S_TRY_MSG'), {icon : "WARN"});
        }
    }
});