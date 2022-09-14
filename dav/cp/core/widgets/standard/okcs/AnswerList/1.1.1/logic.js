 /* Originating Release: February 2019 */
RightNow.Widgets.AnswerList = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._contentName = this.baseSelector + '_Content';
            this._contentDiv = this.Y.one(this._contentName);
            this._data = this.data.js.articles;
            this._isTable = this.data.js.viewType === 'table';
            
            if (this._isTable) {
                this._gridName = this.baseSelector + '_Grid';
                this._dataTable = null;
                this._columns = [];
                this._sortOn = null;
            }

            this.searchSource().setOptions({"history_source_id": this.data.attrs.source_id});
            if(this.searchSource().options.params === undefined){
                this.searchSource().options.params = {};
            }
            this.searchSource().options.params = this.Y.mix(this.searchSource().options.params, {"answerListApiVersion": this.data.js.answerListApiVersion}, true);
            RightNow.Event.subscribe('evt_getFiltersRequest', this._fireSortEvent, this);
            RightNow.Event.subscribe('evt_sortTypeResponse', this._onSortTypeResponse, this);
            this.searchSource().on('response', this._onReportChanged, this)
                                .on('send', this._searchInProgress, this)
                                .on('collect', this.sendOkcsArticlesLimit, this)
                                .on('collect', this.sendOkcsArticlesPageSize, this)
                                .on('collect', this.sendOkcsArticlesCategory, this)
                                .on('collect', this.sendOkcsArticlesTruncateSize, this)
                                .on('collect', this.sendOkcsArticlesSortDirection, this)
                                .on('collect', this.sendOkcsArticlesSortColumn, this);
            this._setFilter();
            if (this._isTable)
                this._generateYUITable(this.data.js.headers);
        }
    },

    /**
    * Sort event handler executed when the column header is clicked
    * @param {Object} evt Event
    */
    _sortAnswerList: function(evt) { 
        if (this.data.js.doNotSortList.indexOf(evt._currentTarget.childNodes[0].data) === -1){
            RightNow.Event.fire("evt_pageLoading");
            var sortTargetNode = evt.target;
            if(evt.target.hasClass('rn_SortIndicator')){
                sortTargetNode = evt.target.get('parentNode');
            }
            this.data.js.sortColumn = this._columns[sortTargetNode._node.cellIndex].value;
            var sortColumnNode = '.yui3-datatable-col-c' + sortTargetNode._node.cellIndex + ' .rn_SortIndicator';
            var headerNode = this.Y.one(sortColumnNode);
            if(headerNode.hasClass('rn_ArticlesSortDesc')) {
                this.data.js.sortDirection = this._directions.asc;
            }
            else if(headerNode.hasClass('rn_ArticlesSortAsc')){
                this.data.js.sortDirection = this._directions.desc;
            }
            else {
                this.data.js.sortDirection = (this.data.js.sortColumn === 'publishDate' || this.data.js.sortColumn === 'createDate' || this.data.js.sortColumn === 'dateModified') 
                    ? this._directions.desc
                    : this._directions.asc;
            }
            this.searchSource().fire('collect');
            this.searchSource().fire('search');
        }
    },

    /**
     * Initializes the data necessary to generate a data table:
     *    this._columns - e.g. [{key: 'Subject', columnID: 1, sortable: true},{key: 'Reference #', columnID: 2, sortable: true}...]
     *    this._data    - e.g. [{'Subject': '<a href="/i_id/148">Droid - Touchscreen</a>', 'Reference #': '120320-000001', 'Status': 'Unresolved'}...]
     *
     * @param {Array} headers An array of header information used to build column data.
     */
    _setTableData: function(headers) {
        var dataTypes = this.data.js.dataTypes,
            sortDirection = this.data.js.sortDirection,
            sortColumnID = this.data.js.sortColumn,
            sortDirectivesPresent = (sortDirection !== null && sortColumnID !== null),
            key, columnID, header, rowObj, rows, i, sortIndicator;

        this._columns = [];
        this._data = [];

        for (i = 0; i < headers.length; i++) {
            header = headers[i];
            columnID = header.columnID;
            key = 'c' + columnID;
            if (sortDirectivesPresent && sortColumnID === header.name) {
                this._sortOn = {key: key, dir: sortDirection};
                sortIndicator = sortDirection == this._directions.desc ? '<span class="rn_SortIndicator rn_ArticlesSortDesc"></span>' : '<span class="rn_SortIndicator rn_ArticlesSortAsc"></span>';
            }
            else {
                sortIndicator = '<span class="rn_SortIndicator"></span>';
            }
            this._columns.push({
                key: key,
                label: header.label + sortIndicator,
                columnID: columnID,
                emptyCellValue: "&nbsp;",
                allowHTML: true,
                value: header.name
            });
        }

        // traverse html table to obtain data
        if (rows = this.Y.all(this._gridName + ' tbody tr')) {
            rows.each(function (row) {
                rowObj = {};
                row.all('td').each(function (td, i) {
                    rowObj[this._columns[i].key] = td.getContent();
                }, this);
                this._data.push(rowObj);
            }, this);
        }
    },

    /**
     * Renders a YUI data table built from supplied headers and the html data table specified in this._gridName.
     *
     * @param {Array} headers An array of header information used to build column data.
     */
    _generateYUITable: function(headers) {
        this._setTableData(headers);
        if (this.data.attrs.hide_when_no_results && !this._data.length) return;

        var type = this.data.attrs.type;
        this._dataTable = new this.Y.DataTable({
            data:    this._data,
            columns: this._columns
        });
        this._dataTable.on('sort', this._onSort, this);
        this._dataTable.after('render', this._afterRenderDataTable, this);
        this._dataTable.after('sort', this._afterRenderDataTable, this);
        if(this.Y.one(this._gridName))
            this.Y.one(this._gridName).remove();
        this._dataTable.render(this._contentName);
        
        if(!this.data.attrs.show_headers) {
            this.Y.one(this.baseSelector + ' thead').addClass('rn_ScreenReaderOnly');
        }
        
        this.Y.one('.yui3-datatable-message').addClass('rn_Hidden');

        if (!this._data.length){
            this.Y.one('.yui3-datatable-message').removeClass('rn_Hidden');
            this._dataTable.showMessage(this.data.attrs.label_no_results);
        }
        RightNow.Event.fire("evt_pageLoaded");
    },
    
    /**
     * Initilization function to set up search filters for report
     */
    _setFilter: function() {
        this._sortFilters = new RightNow.Event.EventObject(this, {
            filters: {
                searchName: this.data.js.searchName,
                report_id: this.data.attrs.report_id,
                data: {}
            }
        });
        this._setSortData();

        var eo = new RightNow.Event.EventObject(this, {filters: {
            report_id: this.data.attrs.report_id,
            allFilters: this.data.js.filters
        }});
    },

    /**
    * initializes sort event object data
    *
    * @param {Integer} columnID
    * @param {Integer} sortDirection
    */
    _setSortData: function(columnID, sortDirection) {
        this._sortFilters.filters.data.col_id  = (columnID || this.data.js.columnID);
        this._sortFilters.filters.data.sort_direction = (sortDirection || this.data.js.sortDirection);
    },

    /**
     * Event handler executed to show progress icon during searches
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
     */
    _searchInProgress: function(evt, args) {
        var params = args[1];

        if(!params || !params.newPage) {
            document.body.setAttribute('aria-busy', 'true');
        }
    },

    /**
     * Event handler executed to display new results when view_type is table
     * @param {Object} args Arguments passed with event
     */
    _onReportChangedTableView: function(args) { 
        var sortData = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id);
        if (sortData)
            this._sortFilters.filters.data = sortData;
        else
            this._setSortData();

        var newdata = args[0].data;
        if(!newdata.headers) 
            newdata.headers = this.data.js.headers;

        var currentPageSize = this.data.attrs.per_page,
            cols = newdata.headers.length,
            data = {
                tableID: this.baseDomID + '_Grid',
                headers: [],
                rows: []
            }, anchor, width, row, i, j, td;

        if (newdata.error) {
            RightNow.UI.Dialog.messageDialog(newdata.error, {"icon": "WARN"});
        }
        this.data.js.sortColumn = newdata.columnID;
        this.data.js.sortDirection = newdata.sortDirection;

        // Build up table data and add the new results to the widget's DOM
        for (i = 0; i < cols; i++) {
            td = {label: newdata.headers[i].label};
            if (width = newdata.headers[i].width) {
                td.style = 'width: "' + width + '%"';
            }
            data.headers.push(td);
        }

        for (i = 0; i < newdata.articles.length; i++) {
            row = [];
            for(j = 0; j < cols; j++) {
                var field = newdata.headers[j].name,
                    value = newdata.articles[i][newdata.headers[j].name],
                    item = [];
                item.name = field;
                if (field === 'contentType') {
                    item.value = value.referenceKey;
                }
                else if (field === 'locale') {
                    item.value = value.recordID;
                }
                else if (field === 'owner' || field === 'lastModifier' || field === 'creator') {
                    item.value = value.name;
                }
                else {
                    item.value = value;
                }
                row.push(item);
            }
            data.rows.push(row);
        }
        
        if (newdata.articles.length === 0 && this.data.attrs.hide_when_no_results) {
            RightNow.UI.hide(this.baseSelector);
        }
        
        this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.table}).render({
                data: data.rows,
                headers: this.data.js.headers,
                articles: newdata.articles,
                tableID: this.baseDomID + '_Grid',
                caption: this.data.attrs.label_caption,
                url: this.data.js.answerUrl,
                type: this.data.attrs.type.charAt(0).toUpperCase(),
                target: this.data.attrs.target
            })
        );

        this._generateYUITable(newdata.headers);

        // now allow expand/contract
        this._contentDiv.setStyle('height', 'auto');
        RightNow.Url.transformLinks(this._contentDiv);
        document.body.setAttribute('aria-busy', 'false');

        if(newdata.articles.length > 0) {
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_success_alert);
            // focus on the first result
            anchor = (this._dataTable)
                ? this._dataTable.getRow(0).one('a')
                : this.Y.one(this._gridName).one('a');
            if (anchor) {
                anchor.focus();
            }
        }
        else {
            //don't focus anywhere, stay where you are so you can perhaps try a new search
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_no_results_alert);
        }
    },
    
    /**
     * Event handler executed to display new results
     *
     * @param {String} type Event type
     * @param {Object} args Arguments passed with event
     */
    _onReportChanged: function(type, args) { 
        var articles = (args[0].data && args[0].data.articles) ? args[0].data.articles : '';
        if (this._isTable) {
            this._onReportChangedTableView(args);
        }
        else {
            if (articles.length > 0) {
                this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.list}).render({
                        data: articles,
                        show_headers: this.data.attrs.show_headers,
                        header: this.data.attrs.label_browse_list_title,
                        url: this.data.js.answerUrl,
                        type: this.data.attrs.type.charAt(0).toUpperCase(),
                        target: this.data.attrs.target
                    })
                );
            }
            else {
                this._contentDiv.set('innerHTML', this.data.attrs.label_no_results);
            }
        }
    },

    /**
     * The sort function for the DataTable.
     * @param  {Object} sortEvent Sort event
     */
    _onSort: function(sortEvent) {
        if (!sortEvent.sortBy) return;

        this.Y.Object.each(sortEvent.sortBy[0], function(value, columnID) {
            this._setSortData(parseInt(RightNow.Text.getSubstringAfter(columnID, 'c'), 10), this._getDirectionToSort(columnID));
        }, this);
    },

    /**
     * Function to be called after rendering datatable. Row headers are added to each datatable cell for accessibility compliance.
     */
    _afterRenderDataTable: function() {
        var tableHeaders = this.Y.all(".yui3-datatable-header");
        for (var i = 0; i < tableHeaders.size(); i++) {
            this.Y.all(".yui3-datatable-cell.yui3-datatable-col-c" + i).setAttribute('headers', this.Y.one(".yui3-datatable-header.yui3-datatable-col-c" + i).getAttribute('id'));
        }
        this.Y.all('th.yui3-datatable-header').on("click", this._sortAnswerList, this); 
    },

    /**
     * Direction defines: what the report model expects
     */
    _directions: { desc: "DESC", asc: "ASC" },

    /**
     * Returns the direction to sort the column based on the direction it's currently
     * been sorted.
     * @param {String} columnID the id of the column (prefixed with 'c')
     * @return {Number} the ascending or descending direction to sort with
     */
    _getDirectionToSort: function(columnID) {
        var yuiPrefix = 'yui3-datatable-',
            header = this.Y.one('#' + this._dataTable.get('id')).one('th.' + yuiPrefix + 'col-' + columnID + '.' + yuiPrefix + 'sortable-column');

        return (header && header.hasClass(yuiPrefix + 'sorted-desc'))
            ? this._directions.asc
            : this._directions.desc;
    },

    /**
     * Sets the sorted CSS class names on the column and removes them
     * from the previously sorted column (if any).
     * @param {String} columnID the id of the column (prefixed with 'c')
     * @param {Number} dir the direction to sort (according to `this._directions`)
     */
    _setSortedColumn: function(columnID, dir) {
        var yuiPrefix = 'yui3-datatable-',
            sortedClass = yuiPrefix + 'sorted',
            descClass = yuiPrefix + 'sorted-desc',
            table = this.Y.one('#' + this._dataTable.get('id')),
            header = table.one('th.' + yuiPrefix + 'col-' + columnID + '.' + yuiPrefix + 'sortable-column');

        if (!this._prevSorted && this.data.js.columnID) {
            this._prevSorted = 'c' + this.data.js.columnID;
        }
        if (this._prevSorted) {
            table.all('.' + yuiPrefix + 'col-' + this._prevSorted).removeClass(sortedClass).removeClass(descClass);
        }

        if (dir === this._directions.asc) {
            header.removeClass(descClass);
        }
        else {
            header.addClass(descClass);
        }
        header.addClass(sortedClass);
        table.all('td.' + yuiPrefix + 'col-' + columnID).addClass(sortedClass);
        this._prevSorted = columnID;
    },

    /**
     * Fire specified event with this._sortFilters.
     *
     * @param {String} eventName Defaults to evt_searchFiltersResponse
     */
    _fireSortEvent: function() {
        RightNow.Event.fire('evt_searchFiltersResponse', new RightNow.Event.EventObject(this, {filters: this._sortFilters}));
    },

    /**
    * Event handler executed when the sort type is changed
    *
    * @param {String} type Event type
    * @param {Object} args Arguments passed with event
    */
    _onSortTypeResponse: function(type, args) {
        var evt = args[0];
        this._setSortData(evt.filters.data.col_id, evt.filters.data.sort_direction);
    },

    /**
     * Updates the text for the ARIA alert div that appears above result listing
     * @private
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    },

    /**
     * Adds the answerlist limit to the filter list
     */
    sendOkcsArticlesLimit: function() {
        if(!this.data.attrs.limit){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.limit, key: 'limit', type: 'limit'}
        });
    },

    /**
     * Adds the answerlist page size to the filter list
     */
    sendOkcsArticlesPageSize: function() {
        if(!this.data.attrs.per_page){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.per_page, key: 'pageSize', type: 'pageSize'}
        });
    },

    /**
     * Adds the answerlist title truncate size to the filter list
     */
    sendOkcsArticlesTruncateSize: function() {
        if(!this.data.attrs.truncate_size){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.truncate_size, key: 'truncate', type: 'truncate'}
        });
    },

    /**
     * Adds the answerlist category to the filter list
     */
    sendOkcsArticlesCategory: function() {
        if(!this.data.attrs.category){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.category, key: 'categoryRecordID', type: 'categoryRecordID'}
        });
    },

    /**
     * Adds the answerlist sort direction to the filter list
     */
    sendOkcsArticlesSortDirection: function() {
        if(!this.data.js.sortDirection){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.js.sortDirection, key: 'sortDirection', type: 'sortDirection'}
        });
    },

    /**
     * Adds the answerlist sort column id to the filter list
     */
    sendOkcsArticlesSortColumn: function() {
        if(!this.data.js.sortColumn){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.js.sortColumn, key: 'sortColumn', type: 'sortColumn'}
        });
    }
});
