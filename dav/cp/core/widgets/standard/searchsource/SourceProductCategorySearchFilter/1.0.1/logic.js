 /* Originating Release: February 2019 */
RightNow.Widgets.SourceProductCategorySearchFilter = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();

            // Mixing in the methods from RightNow.ProductCategory.
            // The methods that exist in this widget with the same
            // name will override--they're responsible
            // for calling the methods on RightNow.ProductCategory's
            // prototype.
            this.Y.augment(this, RightNow.ProductCategory);

            this.searchSource(this.data.attrs.report_id)
                .on('collect', this.collectFilters, this)
                .on('response', this.onFilterUpdate, this)
                .on('reset', this.onReset, this);

            this.initializeEventObject();

            this.initializeTreeView(this.data.attrs.filter_type);
        }
    },

    /**
    * Displays the hierarchy of the currently selected node up to its root node,
    * hides the panel, and focuses on the selection button (if directed).
    * @param {Boolean} focus Whether or not the button should be focused
    */
    displaySelectedNodesAndClose: function(focus) {
        // event to notify listeners of selection
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
        //static variable
        this.selectNode._selectedWidget = this.data.info.w_id;

        if(((node.expanded || !node.value) && !this.data.js.linkingOn) ||
            (this.data.attrs.search_on_select && !RightNow.Url.isSameUrl(this.data.attrs.search_results_url))) {
            // There's no need to get the sub-level requests if the 'all value' item is selected or
            // the search happens on a new page, so just update the event object data.
            this._eo.data.level = node.depth + 1;
            this._eo.data.label = node.label;
            this._eo.data.value = node.value;
        }
        else {
            this.getSubLevelRequest(node);
            this.tree.collapseAll();
        }

        RightNow.ProductCategory.prototype.selectNode.call(this, node);

        if (this.data.attrs.search_on_select) {
            this.searchSource().fire('collect').fire('search', this._eo);
        }
    },

    /**
     * Called when a node with unloaded children is to be expanded.
     * @param  {object} expandingNode The node that's expanding
     */
    getSubLevelRequest: function (expandingNode) {
        RightNow.ProductCategory.prototype.getSubLevelRequest.call(this, expandingNode);

        // Remove link_map from this._eo so this widget does not misinform the Event Bus
        // or other widgets about the link_map on subsequent requests.
        if(this._eo.data.link_map)
            delete this._eo.data.link_map;
    },

    /**
     * Event handler when a node is expanded.
     * Requests the next sub-level of items from the server.
     * @param {object} expandingNode The node that's expanding
     */
    getSubLevelRequestEventObject: function(expandingNode) {
        if(expandingNode.expanded && !this.data.js.linkingOn) return;

        this._eo.data.level = expandingNode.depth + 1;
        this._eo.data.label = expandingNode.label;
        this._eo.data.value = expandingNode.value;

        //static variable for different widget instances but the same data type
        this.getSubLevelRequestEventObject._origRequest = this.getSubLevelRequestEventObject._origRequest || [];
        this.getSubLevelRequestEventObject._origRequest[this.dataType] = expandingNode.value;

        if(this._eo.data.value < 1 && this._eo.data.linking_on) {
            //prod linking
            this._eo.data.reset = true;
            if(this._eo.data.value === 0 && this.dataType === "Product") {
                //product was set back to all: fire event for categories to re-show all
                this._eo.data.reset = false;
                this.searchSource().fire('reset', new RightNow.Event.EventObject(this, {data: {
                    name: 'c',
                    reset: true
                }}));
                return;
            }
            else {
                this._eo.data.value = 0;
            }
        }
        else {
            this._eo.data.reset = false;
        }

        if(this.data.js.link_map) {
            //pass link map (prod linking) to EventBus for first time
            this._eo.data.link_map = this.data.js.link_map;
            this.data.js.link_map = null;
        }

        if (!expandingNode.loaded || this.data.js.linkingOn) {
            return this._eo;
        }
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    getSubLevelResponse: function(type, args) {
        var evtObj = args[0];

        if (evtObj.data.data_type !== this.dataType) return;

        // delete link_map if we have not already so that we don't send stale data
        if(this.data.js.link_map)
            delete this.data.js.link_map;

        var hierLevel = evtObj.data.level,
            hierData = evtObj.data.hier_data,
            currentRoot;

        this.buildTree();

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
     * Event handler when report has been updated
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    onFilterUpdate: function(type, args) {
        var filterValue = 0;

        this.Y.Object.some(args[0].data.filters, function (filter) {
            if (filter.key === this.data.js.filter.key) {
                filterValue = filter.value;
                return true;
            }
        }, this);

        if (filterValue) {
            this.buildTree();

            if (typeof filterValue === 'string' || typeof filterValue === 'number') {
                filterValue = (filterValue + '').split(',');
            }

            this.tree.expandAndCreateNodes(filterValue);
        }
        else if (this.tree) {
            //going from some selection back to no selection
            this.tree.resetSelectedNode();
            this.displaySelectedNodesAndClose();
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
     * Responds to the filter reset event.
     */
    onReset: function() {
        if (this.tree) {
            // Product was set back to 'Select a Product', reset Category's tree data
            if(this.data.js.linkingOn && this.dataType === "Category") {
                this.buildTree(true);
            }
            this.tree.selectNodeWithValue(this.data.js.initial[this.data.js.initial.length - 1] || 0);
            this.displaySelectedNodesAndClose();
        }
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
