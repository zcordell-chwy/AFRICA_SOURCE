 /* Originating Release: February 2019 */
RightNow.Widgets.OkcsProductCategorySearchFilter = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._source_id = this.data.attrs.source_id;
            this._resetOkcsFilterRequest();
            if(this.data.js.viewType === 'explorer') {
                this._currentSelectedID = '';
                this._requestInProgress = false;
                this.Y.one(this.baseSelector).delegate('click', this._expandCategory, 'a.rn_CategoryExplorerCollapsed', this);
                this.Y.one(this.baseSelector).delegate('click', this._collapseCategory, 'a.rn_CategoryExplorerExpanded', this);
                this.Y.one(this.baseSelector).delegate('click', this._categoryClick, 'a.rn_CategoryExplorerLink', this);
                this.Y.one(this.baseSelector).delegate('click', this._categoryClick, 'a.rn_CategoryExplorerLinkSelected', this);
            }
            else {
                this.Y.augment(this, RightNow.ProductCategory);
                this._displayFieldVisibleText = this.Y.one(this.baseSelector + "_ButtonVisibleText");
                this.initializeEventObject();
                this.initializeTreeView(this.data.js.hierData, this.data.attrs.filter_type);
            }
            this.searchSource().on('response', this._handleResponse, this);
            this.searchSource().on('reset', this._resetOkcsFilterRequest, this);
            this.searchSource().on('collect', this.collectFilters, this);

            var productParam = RightNow.Url.getParameter('productRecordID');
            if(this.Y.one('[data-id="' + productParam + '"][data-type="Product"]')){
                this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').removeClass('rn_CategoryExplorerLink');
                this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').addClass('rn_CategoryExplorerLinkSelected');
            }
            var categoryParam = RightNow.Url.getParameter('categoryRecordID');
            if(this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]')){
                this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').removeClass('rn_CategoryExplorerLink');
                this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').addClass('rn_CategoryExplorerLinkSelected');
            }

            this._selectedProductRecordID = RightNow.Url.getParameter('productRecordID');
            this._selectedCategoryRecordID = RightNow.Url.getParameter('categoryRecordID');
            this._isCategorySelected = RightNow.Url.getParameter('isCategorySelected');
            this._isProductSelected = RightNow.Url.getParameter('isProductSelected');
            this._currentSelectedID = RightNow.Url.getParameter('currentSelectedID');

            if(this.data.attrs.filter_type === 'Product') {
                this.searchSource().on('collect', this._updateProductRecordID, this)
                               .on('collect', this._updateIsProductSelected, this)
                               .on('collect', this._updateCurrentSelectedID, this);
            } 
            else if(this.data.attrs.filter_type === 'Category') {
                this.searchSource().on('collect', this._updateCategoryRecordID, this)
                               .on('collect', this._updateIsCategorySelected, this)
                               .on('collect', this._updateCurrentSelectedID, this);
            }

            if (this.data.attrs.toggle_selection) {
                this._toggle = this.Y.one('#' + this.data.attrs.toggle);
                this._itemToToggle = this.Y.one('#' + this.data.attrs.item_to_toggle);
                this._itemToToggle.addClass(this.data.attrs.toggle_state === 'collapsed' ? this.data.attrs.collapsed_css_class : this.data.attrs.expanded_css_class);

                //trick to get voiceover to announce state to screen readers.
                this._screenReaderMessageCarrier = this._itemToToggle.appendChild(this.Y.Node.create(
                    "<img style='opacity: 0;' src='/euf/core/static/whitePixel.png' alt='" +
                    (this._currentlyShowing ? this.data.attrs.label_expanded : this.data.attrs.label_collapsed) + "'/>"));

                this._toggle.on("click", this._onToggle, this);
            }
        }
    },

    /**
    * Toggles the display of the element.
    * @param clickEvent Event Click event
    */
    _onToggle: function(clickEvent) {
        var target = clickEvent.target, cssClassToAdd, cssClassToRemove;
        this._currentlyShowing = this._itemToToggle.hasClass(this.data.attrs.expanded_css_class) ||
            this._itemToToggle.getComputedStyle("display") !== "none";

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
        var spanElement = this._toggle.one('.' + this.data.attrs.expand_icon_collapsed_css);
        if(spanElement) {
            spanElement.addClass(this.data.attrs.expand_icon_expanded_css);
            spanElement.removeClass(this.data.attrs.expand_icon_collapsed_css);
        }
        else{
            spanElement = this._toggle.one('.' + this.data.attrs.expand_icon_expanded_css);
            spanElement.addClass(this.data.attrs.expand_icon_collapsed_css);
            spanElement.removeClass(this.data.attrs.expand_icon_expanded_css);
        }
        this._currentlyShowing = !this._currentlyShowing;
    },

    /**
    * Constructs an Explorerview widget for the first time with initial data returned
    * from the server.
    * @param {Object} category data
    */
    _buildExplorer : function(categoryData) {
        var explorerDiv = this.Y.one(this.baseSelector + "_Tree"),
            categoryType = this.data.attrs.filter_type === 'Product' ? 'PRODUCT' : 'CATEGORY',
            parentCount = 0,
            parentCategory,
            category = [];

        if (categoryData !== undefined) {
            for (var key in categoryData) {
                if (categoryData[key].externalType === categoryType) {
                    categoryData[key].depth = 0;
                    categoryData[key].type = this.data.attrs.filter_type;
                    category[category.length] = categoryData[key];
                    parentCategory = categoryData[key];
                    parentCount++;
                }
            }
            if(parentCount === 1) {
                this._renderFirstLevelChildren(parentCategory, categoryType, explorerDiv);
            }
            else {
                explorerDiv.get('childNodes').remove();
                var explorer = this._createCategoryListNode(category, this.data.attrs.filter_type);
                if(explorer){
                    explorerDiv.append(explorer);
                    explorerDiv.addClass('rn_HiddenExplorer');
                    explorer.replaceClass('rn_CategoryExplorerListHidden', 'rn_CategoryExplorerList');
                }
            }
        }
        return false;
    },

    /** This method renders first level children
    *   @param {Object} parentCategory parent category
    *   @param {String} categoryType category type
    *   @param {Object} explorerDiv explorer container
    */
    _renderFirstLevelChildren: function(parentCategory, categoryType, explorerDiv) {
        parentCategory.name = this.Y.Escape.html(parentCategory.name);
        var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: parentCategory.referenceKey }});
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response, args){
                var children = [];
                for (var key in response) {
                    if (response[key].externalType === categoryType) {
                        response[key].depth = 0;
                        response[key].name = this.Y.Escape.html(response[key].name);
                        response[key].type = this.data.attrs.filter_type;
                        children[children.length] = response[key];
                    }
                }
                var newContent = new EJS({text: this.getStatic().templates.explorerView}).render({children: children, widgetInstanceID: this.baseDomID, parentCategory: parentCategory, expandLevelIcon: this.data.attrs.label_expand_icon, collapseLevelIcon: this.data.attrs.label_collapse_icon});
                explorerDiv.set("innerHTML", newContent);
            },
            json: true,
            scope: this
        });
    },

    /** This method return a list of category nodes
    *   @param {list} categories category list
    *   @param {String} categoryType Product or Category
    *   @param {int} depth maximum depth of product or category
    */
    _createCategoryListNode : function(categories, categoryType, depth) {
        if(categories === undefined || categories === null) {
            return null;
        }
        return this.Y.Node.create('<ul class="rn_CategoryExplorerListHidden"></ul>').append(new this.Y.NodeList(this.Y.Array.map(categories, function (category) {
            return this._createCategoryNode(category, categoryType, depth);
        }, this)));
    },

    /** This method category a node element.
    * @param {object} category object
    * @param {String} categoryType Product or Category
    * @param {int} depth maximum depth of product or category
    */
    _createCategoryNode : function(category, categoryType, depth) {
        var currentCategoryType = (category.externalType === 'PRODUCT' ? 'Product' : 'Category');
        var item = this.Y.Node.create('<b>');
        if(currentCategoryType === categoryType) {
            if (!category.hasChildren)
                var id = this.baseDomID + "_" + category.referenceKey,
                    categoryLink = this.Y.Node.create('<a href="javascript:void(0)" class="rn_LeafNode rn_CategoryExplorerLink" id="' + id + '" data-id="' + category.referenceKey + '" data-depth="' + depth + '" data-type="' + categoryType + '">' + category.name + '</a>');
            else
                var id = this.baseDomID + "_" + category.referenceKey,
                    categoryLink = this.Y.Node.create('<a href="javascript:void(0)" class="rn_CategoryExplorerLink" id="' + id + '" data-id="' + category.referenceKey + '" data-depth="' + depth + '" data-type="' + categoryType + '">' + category.name + '</a>');
            if (!category.hasChildren)
                item = this.Y.Node.create('<li class="rn_CategoryExplorerItem"><div class="rn_CategoryExplorerLeaf"></div><a id="' + id + '_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_expand_icon + '</span></a></li>');
            else
                item = this.Y.Node.create('<li class="rn_CategoryExplorerItem"><a id="' + id + '_Expanded" class="rn_CategoryExplorerExpandedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_expand_icon + '</span></a><a id="' + id + '_Collapsed" class="rn_CategoryExplorerCollapsed" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_collapse_icon + '</span></a></li>');
            item.append(categoryLink);
        }
        return category.children ? item.append(this._createCategoryListNode(category.children)) : item;
    },

    /**
     * Event handler when a node is expanded.
     * Requests the next sub-level of items from the server.
     * @param {object} event Event
     * @param {object} category The node that's expanding
     */
    _expandCategory: function(event, category) {
        var collapsedLink = event.target,
            selectedCategoryId = collapsedLink.getAttribute('id').replace("_Collapsed", ""),
            expandedLink = this.Y.one("#" + selectedCategoryId + "_Expanded"),
            categoryLink = this.Y.one("#" + selectedCategoryId);
        
        collapsedLink.setAttribute('class', 'rn_CategoryExplorerCollapsedHidden');
        expandedLink.setAttribute('class', 'rn_CategoryExplorerExpanded');
        if (categoryLink.next()) {
            categoryLink.next().setAttribute('class', 'rn_CategoryExplorerList');
            this.Y.all('.rn_CategoryExplorerExpanded').on("click", this._collapseCategory, this);
            return false;
        }
        //only allow one node at-a-time to be expanded
        if (this._nodeBeingExpanded || (categoryLink.expanded && !this.data.js.linkingOn)) return;
        this._nodeBeingExpanded = true;
        if (!categoryLink.dynamicLoadComplete || this.data.js.linkingOn) {
            var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: categoryLink.getAttribute('data-id') }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: function(response,args){
                    if(this.Y.one(this.baseSelector + "_Error"))
                        this.Y.one(this.baseSelector + "_Tree").removeChild(this.Y.one(this.baseSelector + "_Error"));
                    if(response.error) {
                        var error = response.error.errorCode + ': ' + response.error.externalMessage + ' - ' + response.error.source;
                        this.Y.one(this.baseSelector + "_Tree").prepend('<div id="rn_' + this.instanceID + '_Error">' + error + '</div>');
                        return;
                    }
                    response = [{"data": {"data_type" : categoryLink.getAttribute('data-type').toString(), "hier_data" : response, "label" : categoryLink.getHTML().toString(), "linking_on" : 0, "linkingProduct" : 0, "value" : categoryLink.getAttribute('data-id').toString(), "reset" : false, "level" : parseInt(categoryLink.getAttribute('data-depth').toString(), 10) + 1}}];
                    this._getSubLevelResponse(null,response);
                    if(categoryLink.getAttribute('data-type').toString() === 'Product'){
                        var productParam = RightNow.Url.getParameter('productRecordID');
                        if(this.Y.one('[data-id="' + productParam + '"][data-type="Product"]')){
                            this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').removeClass('rn_CategoryExplorerLink');
                            this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').addClass('rn_CategoryExplorerLinkSelected');
                        }
                    }
                    else if(categoryLink.getAttribute('data-type').toString() === 'Category'){
                        var categoryParam = RightNow.Url.getParameter('categoryRecordID');
                        if(this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]')){
                            this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').removeClass('rn_CategoryExplorerLink');
                            this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').addClass('rn_CategoryExplorerLinkSelected');
                        }
                    }
                },
                json: true,
                scope: this
            });
        }
        this._nodeBeingExpanded = false;
        return false;
    },

    /** Method called to collapse a category
    * @param {object} event object
    * @param {object} category object
    */
    _collapseCategory: function(event, category) {
        var expandedLink = event.target,
            categoryId = expandedLink.getAttribute('id').replace("_Expanded", ""),
            categoryLink = this.Y.one("#" + categoryId),
            collapsedLink = this.Y.one("#" + categoryId + "_Collapsed");
        expandedLink.setAttribute('class', 'rn_CategoryExplorerExpandedHidden');
        collapsedLink.setAttribute('class', 'rn_CategoryExplorerCollapsed');
        categoryLink.next().setAttribute('class', 'rn_CategoryExplorerListHidden');
    },

    /** Method called to apply or remove category filter.
    * @param {object} event object
    * @param {object} category object
    * @param {string} categoryType product or category
    */
    _categoryClick: function(event, category, categoryType) {
        this.searchSource().initialFilters = {};
        RightNow.Event.fire("evt_pageLoading");
        var productParam = RightNow.Url.getParameter('productRecordID');
        var categoryParam = RightNow.Url.getParameter('categoryRecordID');
        this._selectedProductRecordID = '';
        this._selectedCategoryRecordID = '';
        if (categoryType !== undefined) {
            if(categoryType === 'Product') {
                this._isProductSelected = true;
                this._selectedProductRecordID = category === 0 ? '' : category;
            }
            else {
                this._isCategorySelected = true;
                this._selectedCategoryRecordID = category === 0 ? '' : category;
            }
            this._currentSelectedID = category === 0 ? '' : category;

            RightNow.ActionCapture.record('okcsa-browse', categoryType === 'Product' ? 'product' : 'category', category);
            return false;
        }

        var node = event.target,
            categorySelected = node.hasClass('rn_CategoryExplorerLink') ? true : false,
            categoryType = node.getAttribute('data-type'),
            categoryNodes = this.Y.all("a[data-type='" + categoryType + "']"),
            categoryId = node.getAttribute('data-id');

        categoryNodes.removeClass('rn_CategoryExplorerLinkSelected');
        categoryNodes.addClass('rn_CategoryExplorerLink');

        if (categoryId !== 0) {
            if(categoryType === 'Product') {
                this.productSelected = categorySelected ? true : false;
                this.categorySelected = false;
                if(productParam === null) {
                    this._isProductSelected = categorySelected;
                    this._selectedProductRecordID = categoryId;
                }
                else if(productParam !== categoryId && productParam !== null) {
                    productParam = categoryId;
                    this._isProductSelected = categorySelected;
                    this._selectedProductRecordID = categoryId;
                } 
                else {
                    productParam = null;
                    this._selectedProductRecordID = '';
                    this._isProductSelected = false;
                }
            }
            else {
                this.productSelected = false;
                this.categorySelected = categorySelected ? true : false;
                if(categoryParam === null) {
                    this._isCategorySelected = categorySelected;
                    this._selectedCategoryRecordID = categoryId;
                }
                else if(categoryParam !== categoryId && categoryParam !== null) {
                    categoryParam = categoryId;
                    this._isCategorySelected = categorySelected;
                    this._selectedCategoryRecordID = categoryId;
                }
                else {
                    categoryParam = null;
                    this._selectedCategoryRecordID = '';
                    this._isCategorySelected = false;
                }
            }
            this._currentSelectedID = categoryId;
            if(this._source_id === 'OKCSBrowse')
            {
                if(productParam !== null) {
                    RightNow.Url.addParameter(window.location.href,'productRecordID',productParam);
                    RightNow.Url.addParameter(window.location.href,'isProductSelected','true');
                    if (!('pushState' in window.history))
                        this._selectedProductRecordID = productParam;
                    this._isProductSelected = true;
                }
                if(categoryParam !== null) {
                    RightNow.Url.addParameter(window.location.href,'categoryRecordID',categoryParam);
                    RightNow.Url.addParameter(window.location.href,'isCategorySelected','true');
                    this._isCategorySelected = true;
                    if (!('pushState' in window.history))
                        this._selectedCategoryRecordID = categoryParam;
                }

                this.searchSource().fire("collect").fire("search");
            }
        }
        if(this._source_id === 'OKCSSearch') {
            var node = this.Y.one("a[data-id='" + categoryId + "']"),
                selectedClass = node.hasClass('rn_CategoryExplorerLinkSelected') ? 'rn_CategoryExplorerLink' : 'rn_CategoryExplorerLinkSelected';
            node.setAttribute('class', selectedClass);
        }
        if (this.data.attrs.toggle_selection)
            this._onToggle(this);

        return false;
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _getSubLevelResponse: function(type, args) {
        var evtObj = args[0];
        if(evtObj.data.value !== undefined) {
            var selectedNode = this.Y.one("a[data-id='" + evtObj.data.value + "']"),
                categoryType = selectedNode.getAttribute('data-type'),
                parentNode = selectedNode.get('parentNode'),
                hierarchicalData = evtObj.data.hier_data,
                depth = evtObj.data.level - 1;
            if (hierarchicalData !== undefined) {
                var categoryListNode = this._createCategoryListNode(hierarchicalData, categoryType, depth);
                if(categoryListNode){
                    categoryListNode.setAttribute('class', 'rn_CategoryExplorerList');
                    parentNode.appendChild(categoryListNode);
                }
            }
            return false;
        }
         if (this._restorationHierArray) {
            //If this._restorationHierArray is set, then prod/cat linking and history management are both in use.
            //Use this._restorationHierArray to restore the value and select the node.
            var hierArray = this._restorationHierArray;
            this._restorationHierArray = null;
            this._expandAndCreateNodes(hierArray);
            tempNode = this._tree.getNodeByProperty("hierValue", parseInt(hierArray[hierArray.length - 1], 10));
            if (tempNode)
                this._selectNode({node: tempNode});
        }
    },

    /**
     * Event handler when report has been updated
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _onReportResponse: function(type, args) {
        var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName);
        this._getFiltersRequest.cachedWidgetHier = {};
        if(data[0] && data[0].length) {
            if(typeof data[0] === "string")
                data[0] = data[0].split(",");
            var finalData = RightNow.Lang.arrayFilter(data[0]);
            var fromHistoryManager = args && args[0] && args[0].data && args[0].data.fromHistoryManager;
            this._expandAndCreateNodes(finalData, fromHistoryManager);
            this._eo.filters.data[0] = finalData;
            this._lastSearchValue = finalData.slice(0);
            if(this._eo.filters.data.reconstructData) {
                this._eo.filters.data.level = this._eo.filters.data.reconstructData.level;
                this._eo.filters.data.label = this._eo.filters.data.reconstructData.label;
            }
        }
        else {
            //always set back to empty array since search eventbus may have inadvertantly set it to null...
            this._eo.filters.data[0] = [];
            if(this._tree) {
                //going from some selection back to no selection
                this._currentIndex = this._noValueNodeIndex;
                this._displaySelectedNodesAndClose();
            }
        }
    },
    
    
    /**
    * Displays the hierarchy of the currently selected node up to its root node,
    * hides the panel, and focuses on the selection button (if directed).
    * @param {Boolean} focus Whether or not the button should be focused
    */
    displaySelectedNodesAndClose: function(focus) {
        this._eo.data.hierChain = this.tree.get('valueChain');
        RightNow.Event.fire("evt_productCategoryFilterSelected", this._eo);
        delete this._eo.data.hierChain;

        RightNow.ProductCategory.prototype.displaySelectedNodesAndClose.call(this, focus);
    },

    /**
    * Selected a node by clicking on its label
    * (as opposed to expanding it via the expand image).
    * @param {object} node The node
    */
    selectNode: function(node) {
        this.selectNode._selectedWidget = this.data.info.w_id;
        this.tree.collapseAll();
        RightNow.ProductCategory.prototype.selectNode.call(this, node);
        var nodeValue = this._source_id === 'OKCSSearch' ? node.valueChain.join('.') : node.value;
        if (this.data.attrs.search_on_select || this._source_id === 'OKCSSearch')
            this._categoryClick(null, nodeValue, this.data.attrs.filter_type);
    },

    /**
     * Called when a node with unloaded children is to be expanded.
     * @param  {object} expandingNode The node that's expanding
     */
    getSubLevelRequest: function (expandingNode) {
        var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: expandingNode.value }});
        var data_type = this._dataType;
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response,args){
                response = this._convertData(response);
                response = [{"data": {"data_type" : data_type, "current_root" : expandingNode.value, "hier_data" : response, "label" : expandingNode.label, "linking_on" : 0, "linkingProduct" : 0, "value" : expandingNode.hierValue, "reset" : false, "level" : expandingNode.depth + 1}}];
                this.getSubLevelResponse(null, response);
            },
            json: true,
            scope: this
        });
        // Remove link_map from this._eo so this widget does not misinform the Event Bus
        // or other widgets about the link_map on subsequent requests.
        if(this._eo.data.link_map)
            delete this._eo.data.link_map;
        return false;
    },
    
    /** This method is called when response event is fired..
    * @param {object} obj response object
    * @param {object} evt Event
    */
    _handleResponse: function(obj, evt) {
        if (this.data.js.viewType === 'explorer') {
            if (evt[0].data.category !== undefined) {
                this._buildExplorer(evt[0].data.category);
            }
            else {
                var categoryReferenceKey = evt[0].data.categoryRecordID
                    categorySelected = evt[0].data.isCategorySelected;
                if (this.categorySelected) {
                    var node = this.Y.one("a[data-id='" + this._selectedCategoryRecordID + "']");
                    if(node.getAttribute('data-type') === 'Category'){
                        node.removeClass('rn_CategoryExplorerLink');
                        node.addClass('rn_CategoryExplorerLinkSelected');
                    }
                    this._setScreenReaderProductCategory(node);
                    RightNow.Event.fire("evt_pageLoaded");
                    this._requestInProgress = false;
                }
                else if (this.productSelected) {
                    var node = this.Y.one("a[data-id='" + this._selectedProductRecordID + "']");
                    if(node.getAttribute('data-type') === 'Product'){
                        node.removeClass('rn_CategoryExplorerLink');
                        node.addClass('rn_CategoryExplorerLinkSelected');
                    }
                    this._setScreenReaderProductCategory(node);
                    RightNow.Event.fire("evt_pageLoaded");
                    this._requestInProgress = false;
                }
                else if (categorySelected === undefined) {
                    this.Y.all('.rn_CategoryExplorerLinkSelected[data-type="Category"]').removeClass('rn_CategoryExplorerLinkSelected');
                }
            }
        }
        else if (evt[0].data.category !== undefined) {
            var categories = this._convertData(evt[0].data.category),
                category = { id : 0, label : this.data.attrs.label_all_values };
            categories.unshift(category);
            this.data.js.hierData[0] = categories;
            this._displayFieldVisibleText.setHTML(this.data.attrs.label_nothing_selected);
            this.tree = false;
            this.buildTree(true);
        }
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    getSubLevelResponse: function(type, args) {
        var evtObj = args[0];

        // delete link_map if we have not already so that we don't send stale data
        if(this.data.js.link_map)
            delete this.data.js.link_map;

        var hierLevel = evtObj.data.level,
            hierData = evtObj.data.hier_data,
            currentRoot = evtObj.data.current_root;

        if(!evtObj.data.reset_linked_category && this.getSubLevelRequestEventObject._origRequest &&
            this.getSubLevelRequestEventObject._origRequest[this.dataType]) {
            currentRoot = this.getSubLevelRequestEventObject._origRequest[this.dataType];
        }
        else if(evtObj.data.reset_linked_category) {
            //prod linking : data's being completely reset
            this.tree.clear(this.data.attrs.label_all_values);
            this.dialog = null;

            //since the data's being reset, reset the button's label
            this.dropdown.set('triggerText', this.data.attrs.label_nothing_selected);
            this.Y.all(this.baseSelector + "_TreeDescription").setHTML(this.data.attrs.label_nothing_selected);
        }

        if (hierLevel < 7) {
            //add the new nodes to the currently selected node
            this.insertChildrenForNode(hierData, currentRoot);
        }
        if (hierData.length === 0) {
            //leaf node was expanded : display and close
            this.displaySelectedNodesAndClose();
        }
        if (this._restorationHierArray) {
            //If this._restorationHierArray is set, then prod/cat linking and history management are both in use.
            //Use this._restorationHierArray to restore the value and select the node.
            var hierArray = this._restorationHierArray;
            this._restorationHierArray = null;
            this.tree.expandAndCreateNodes(hierArray);
            var tempNode = this.tree.getNodeByValue(hierArray[hierArray.length - 1]);
            if (tempNode)
                this.selectNode({node: tempNode});
        }
    },

    /**
     * Returns the filter's value in response to a collect event.
     * @return {object} EventObject
     */
    collectFilters: function() {
        return this._eo;
    },

    /**
     * Function for setting screenreader label for selected product/category
     * @param {object} node DOM node to set screen reader label
     */
    _setScreenReaderProductCategory: function(node) {
        var categoryType = node.getAttribute('data-type');
        this.Y.all("a[type='" + categoryType + "'] .rn_ScreenReadProductSelected").remove();
        this.Y.all("a[type='" + categoryType + "'] .rn_ScreenReadCategorySelected").remove();
        var categoryName = node.getHTML();

        if(node.hasClass('rn_CategoryExplorerLinkSelected')){
            if(categoryType === 'Category')
                node.append('<span class="rn_ScreenReadCategorySelected rn_ScreenReaderOnly">' + RightNow.Text.sprintf(this.data.attrs.label_category_selected, categoryName) + '</span>');
            else if(categoryType === 'Product')
                node.append('<span class="rn_ScreenReadProductSelected rn_ScreenReaderOnly">' + RightNow.Text.sprintf(this.data.attrs.label_product_selected, categoryName) + '</span>');
        }
    },

    /**
    * This funciton converts okcs data into tree structure
    * @param {object} results response object
    * @return {object} Category data into tree structure
    */
    _convertData: function(results) {
        var convertedObject = [];
        for(var i = 0, j = 0; i < results.length; i++) {
            var validCategoryFlag = ((results[i].externalType === 'PRODUCT' && this.data.attrs.filter_type === "Product") ||
                                    ((!results[i].externalType || results[i].externalType === 'CATEGORY') && this.data.attrs.filter_type === 'Category')) ? true : false;
            if(validCategoryFlag) {
                var category = {
                    id : results[i].referenceKey,
                    label : results[i].name,
                    hasChildren : results[i].hasChildren,
                    selected : false
                };
                convertedObject[ j++ ] = category;
            }
        }
        return convertedObject;
    },

    /**
    * This funciton reset okcs filters.
    */
    _resetOkcsFilterRequest: function() {
        this.searchSource().initialFilters = {};
        this._isProductSelected = false;
        this._isCategorySelected = false;
        this._selectedProductRecordID = '';
        this._selectedCategoryRecordID = '';
        this._currentSelectedID = '';
    },

    /**
    * Collects isProductSelected filter
    * @return {object} Event object
    */
    _updateIsProductSelected: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._isProductSelected, key: 'isProductSelected', type: 'isProductSelected'}
        });
    },

    /**
    * Collects isCategorySelected filter
    * @return {object} Event object
    */
    _updateIsCategorySelected: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._isCategorySelected, key: 'isCategorySelected', type: 'isCategorySelected'}
        });
    },

    /**
    * Collects productRecordID filter
    * @return {object} Event object
    */
    _updateProductRecordID: function() {
        var productKey = this._source_id === 'OKCSSearch' ? 'product' : 'productRecordID';
        return new RightNow.Event.EventObject(this, {
            data: {value: this._selectedProductRecordID, key: productKey, type: productKey}
        });
    },

    /**
    * Collects categoryRecordID filter
    * @return {object} Event object
    */
    _updateCategoryRecordID: function() {
        var categoryKey = this._source_id === 'OKCSSearch' ? 'category' : 'categoryRecordID';
        return new RightNow.Event.EventObject(this, {
            data: {value: this._selectedCategoryRecordID, key: categoryKey, type: categoryKey}
        });
    },

    /**
    * Collects currentSelectedID filter
    * @return {object} Event object
    */
    _updateCurrentSelectedID: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._currentSelectedID, key: 'currentSelectedID', type: 'currentSelectedID'}
        });
    },
    
    /**
     * Sets the event object for subsequent searches.
     */
    initializeEventObject: function() {
        this._eo = new RightNow.Event.EventObject(this, {
            data: this.Y.merge(this.data.js.filter, {
                data_type:      this.dataType = this.data.attrs.filter_type,
                new_page:       this.data.attrs.search_results_url,
                linking_on:     this.data.js.linkingOn,
                hm_type:        this.data.js.hm_type,
                cache:          []
            })
        });

        if (this.dataType === "Product") {
            // Set namespace global for hier menu list linking display
            RightNow.UI.Form.currentProduct = this.data.js.initial[this.data.js.initial.length - 1];
        }
    }
});
