 /* Originating Release: February 2019 */
RightNow.Widgets.OkcsSimpleSearch = RightNow.Widgets.SimpleSearch.extend({
    overrides: {
        constructor: function() {
            this.parent();
        },
        /**
        * Called when the user searches
        */
        _onSearch: function() {
            if(this.Y.UA.ie) {
                //since the form is submitted by script, deliberately tell IE to do auto completion of the form data
                var parentForm = this.Y.one(this.baseSelector + "_SearchForm");
                if(parentForm && window.external && "AutoCompleteSaveForm" in window.external) {
                    window.external.AutoCompleteSaveForm(parentForm);
                }
            }
            var searchString = this._searchField.get("value").trim();
            if(searchString !== '') {
                searchString = RightNow.Url.addParameter(this.data.attrs.report_page_url, "kw", searchString);
                searchString = RightNow.Url.addParameter(searchString, "session", RightNow.Url.getSession());
                if(this.data.js.prodCatFacet !== '') {
                    searchString = RightNow.Url.addParameter(searchString, "facet", this.data.js.prodCatFacet);
                }
                if(RightNow.Url.getParameter('p')) {
                    searchString = RightNow.Url.addParameter(searchString, "p", RightNow.Url.getParameter('p'));
                }
                if(RightNow.Url.getParameter('c')) {
                    searchString = RightNow.Url.addParameter(searchString, "c", RightNow.Url.getParameter('c'));
                }
                if(this.data.js.filterName) {
                    searchString = RightNow.Url.addParameter(searchString, "filterName", this.data.js.filterName);
                }
                RightNow.Url.navigate(searchString);
            }
            else {
                RightNow.UI.displayBanner(this.data.attrs.label_enter_search_keyword, {
                    type: 'WARNING',
                    focusElement: this._searchField
                });
            }
        }
    }
});
