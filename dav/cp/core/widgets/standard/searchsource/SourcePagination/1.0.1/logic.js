 /* Originating Release: February 2019 */
RightNow.Widgets.SourcePagination = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function () {
            this.parent();
            this.Y.one(this.baseSelector).delegate('click', this.onPageClick, 'a', this);
            this.searchSource().setOptions(this.data.js.sources).on('response', this.onSearchComplete, this);
        }
    },

    /**
     * Triggers a new search when a pagination link is clicked.
     * @param  {object} e Click event
     */
    onPageClick: function (e) {
        e.halt();

        var pageNumber = this.determinePageNumber(e.currentTarget.getAttribute('data-rel'));
        if (pageNumber) {
            this.data.js.filter.value = pageNumber;
            this.triggerSearch();
        }
    },

    /**
     * Initiates a new search.
     */
    triggerSearch: function () {
        this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
            data: {
                page: this.data.js.filter,
            }
        }));
    },

    /**
     * Retrieves the correct page number, given a link's
     * `data-rel` attribute.
     * @param  {string} domPageValue Attribute value
     * @return {number|null}              Page number, null if not found
     *                                         or if already on the page
     *                                         indicated
     */
    determinePageNumber: function (domPageValue) {
        if (!domPageValue || domPageValue == this.data.js.currentPage) return;

        if (domPageValue === 'next') {
            return this.data.js.filter.value + 1;
        }
        if (domPageValue === 'previous') {
            return this.data.js.filter.value - 1;
        }

        return parseInt(domPageValue, 10);
    },

    /**
     * Re-renders when new search results arrive.
     * @param  {string} evt  Event name
     * @param  {object} args Event object
     */
    onSearchComplete: function (evt, args) {
        var result = args[0].data;

        this.data.js.currentPage = result.filters.page.value;

        if (result.filters.page.value !== this.data.js.filter.value) {
            this.data.js.filter.value = result.filters.page.value;
        }

        var previousLink = this.renderPreviousLink(result),
            nextLink = this.renderNextLink(result),
            pages = this.renderPageLinks(result);

        this.Y.one(this.baseSelector + ' ul').setHTML(previousLink + pages + nextLink);
    },

    /**
     * Renders the previous page link.
     * @param  {object} result Search results
     * @return {string} Previous page HTML
     */
    renderPreviousLink: function (result) {
        if (this.data.js.filter.value <= 1 || !result.size) return '';

        var previousPage = this.data.js.filter.value - 1;

        return new EJS({ text: this.getStatic().templates.navigationLink }).render({
            href: this.url(previousPage),
            rel: 'previous',
            label: RightNow.Interface.getMessage('PREVIOUS_LBL'),
            className: 'rn_PreviousPage'
        });
    },

    /**
     * Renders the next page link.
     * @param  {object} result Search results
     * @return {string}        Next page HTML
     */
    renderNextLink: function (result) {
        if (result.total > result.size && result.offset + result.size < result.total) {
            var nextPage = this.data.js.filter.value + 1;
            return new EJS({ text: this.getStatic().templates.navigationLink }).render({
                href: this.url(nextPage),
                rel: 'next',
                label: RightNow.Interface.getMessage('NEXT_LBL'),
                className: 'rn_NextPage'
            });
        }
        return '';
    },

    /**
     * Renders the pagination links.
     * @param  {object} result Search results
     * @return {string}        Pagination HTML
     */
    renderPageLinks: function (result) {
        var numberOfPages = result.size ? Math.ceil(result.total / this.data.js.limit) : 0;
        // TK add truncate logic: 1,2,3,4,...9,10.
        if (numberOfPages <= 1) return '';

        return new EJS({ text: this.getStatic().templates.pageLink }).render({
            numberOfPages: numberOfPages,
            currentPage: this.data.js.filter.value,
            pageLink: this.Y.bind(this.url, this)
        });
    },

    /**
     * Builds up a page link.
     * @param  {number} pageNumber Page number
     * @return {string}            URL for the page link
     */
    url: function (pageNumber) {
        return RightNow.Url.addParameter(window.location.href, this.data.js.filter.key, pageNumber);
    }
});
