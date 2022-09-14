RightNow.namespace('Custom.Widgets.sponsorship.SponsorshipPayment');
Custom.Widgets.sponsorship.SponsorshipPayment = RightNow.Widgets.extend({     /**
     * Widget constructor.
     */
    constructor: function() {
    },
    /**
     * Sample widget method.
     */
    methodName: function() {
    },    /**
     * Renders the `view.ejs` JavaScript template.
     */
    renderView: function() {
        // JS view:
        var content = new EJS({text: this.getStatic().templates.view}).render({
            // Variables to pass to the view
            // display: this.data.attrs.display
        });
    }
});