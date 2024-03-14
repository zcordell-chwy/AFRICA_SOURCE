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

function checkFluency()
{
  var checkbox = document.getElementById('subscribeToEmailCheckbox');
  var checkbox1 = document.getElementsByName('Contact.MarketingSettings.MarketingOptIn');
  var select1 = document.getElementsByName('Contact.CustomFields.c.preferences');

  if (checkbox.checked !== true)
  {
    select1[0].value=16;
    checkbox1[0].checked=false;
  }
  if (checkbox.checked === true)
  {
    select1[0].value=14;
    checkbox1[0].checked=true;
  }
}