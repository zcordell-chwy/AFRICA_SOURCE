 /* Originating Release: February 2019 */
RightNow.Widgets.MobileSimpleSearch = RightNow.Widgets.extend({
    constructor: function() {
        this._searchField = this.Y.one(this.baseSelector + "_SearchField");
        if (!this._searchField) return;

        if (this.data.attrs.label_hint) {
            this._searchField.on("focus", this._onFocus, this);
            this._searchField.on("blur", this._onBlur, this);
        }
        if (this.data.attrs.clear_text_icon_path) {
            this._searchField.on("keyup", this._handleClearTextDisplay, this);
            this._searchField.on("blur", this._handleClearTextDisplay, this);
        }
        this.Y.Event.attach("click", this._onSearch, this.baseSelector + "_Submit", this);
    },

    /**
    * Called when the user searches
    */
    _onSearch: function() {
        var searchString = (this._searchField.get("value") === this.data.attrs.label_hint) ? "" : this._searchField.get("value");
        if(searchString !== ""){
            searchString = RightNow.Url.addParameter(this.data.attrs.report_page_url, "kw", searchString);
            searchString = RightNow.Url.addParameter(searchString, "search", 1);
            searchString = RightNow.Url.addParameter(searchString, "session", RightNow.Url.getSession());
            RightNow.Url.navigate(searchString);
        }
    },
    /**
    * Called when the search field is focused. Removes initial_value text
    */
    _onFocus: function() {
        if (this._searchField.get("value") === this.data.attrs.label_hint)
            this._searchField.set("value", "");
    },
    /**
    * Called when the search field is blurred. Removes initial_value text
    */
    _onBlur: function() {
        if(this._searchField.get("value") === "")
            this._searchField.set("value", this.data.attrs.label_hint);
    },
    /**
    * Facilitates the hiding and showing of the icon that, when clicked, removes the search
    * field text.
    * @param {Object} event The DOM event
    */
    _handleClearTextDisplay: function(event) {
        if (!this._clearIcon) {
            this._clearIcon = this.Y.one(this.baseSelector + "_Clear");
            this._clearIcon.on("click", function() {
                this._searchField.set("value", "").focus();
                RightNow.UI.hide(this._clearIcon);
                this._showing = false;
            }, this);
            this._showing = false;
        }
        if (this._searchField.get("value") === "") {
            RightNow.UI.hide(this._clearIcon);
            this._showing = false;
        }
        else if (!this._showing) {
            RightNow.UI.show(this._clearIcon);
            this._showing = true;
        }
    }
});
