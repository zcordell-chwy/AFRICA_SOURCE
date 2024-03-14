RightNow.namespace('Custom.Widgets.input.SelectionInput');
Custom.Widgets.input.SelectionInput = RightNow.Widgets.SelectionInput.extend({     /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
overrides: {
    constructor: function(){
        if (this.data.js.readOnly) return;

        this.parent();

        var attrs = this.data.attrs;

        this.input = (this.data.js.type === "Boolean" && !attrs.display_as_checkbox)
            ? this.Y.all(this._inputSelector + "_1, " + this._inputSelector + "_0")
            : this.Y.one(this._inputSelector);

        if(!this.input) return;

        if(attrs.hint && !attrs.hide_hint && !attrs.always_show_hint) {
            this._initializeHint();
        }

        if(attrs.initial_focus && this.input) {
            if(this.data.js.type === "Boolean" && this.input[0] && this.input[0].focus)
                this.input[0].focus();
            else if(this.input.focus)
                this.input.focus();
        }

        if (attrs.validate_on_blur && attrs.required) {
            this.Y.Event.attach('blur', this.blurValidate, this.input instanceof this.Y.NodeList ? this.input.item(1) : this.input, this);
        }

        this.input.on('change', function() {
            RightNow.Event.fire("evt_formInputDataChanged", null);
            this.fire('change', this);
        }, this);
        this.on("constraintChange", this.constraintChange, this);

        //specific events for specific fields:
        var fieldName = this.data.js.name;
        //province changing
        if(fieldName === "Contact.Address.Country") {
            this.input.on("change", this.countryChanged1, this);
            if(this.input.get('value'))
                this.countryChanged1();
        }
        else if(fieldName === "Contact.Address.StateOrProvince") {
            this.currentState1 = this.input.get('value');
            RightNow.Event.on("evt_provinceResponse1", this.onProvinceResponse1, this);
        }
        //If this is the Status field, subscribe to the change event so we can toggle the Thread field requiredness
        else if(fieldName === 'Incident.StatusWithType.Status'){
            this.on('change', this.onStatusChanged, this);
        }

       
    }
},


/**
 * Event handler executed when country dropdown is changed.
 * Should only be called for the 'contacts.country_id' select field.
 */
countryChanged1: function() {
    var value = this.input.get("value"),
        fireResponse = function(response, eventObject) {
            RightNow.Event.fire("evt_provinceResponse1", response, eventObject);
        };
    if(value) {
        var eventObject = new RightNow.Event.EventObject(this, {data: {country_id: value}});
        if (RightNow.Event.fire("evt_provinceRequest", eventObject)) {
            this._provinces = this._provinces || {};
            if (this._provinces[value]) {
                return fireResponse(this._provinces[value], eventObject);
            }
            RightNow.Ajax.makeRequest("/ci/ajaxRequestMin/getCountryValues", eventObject.data, {
                successHandler: function(response) {
                    this._provinces[value] = response;
                    fireResponse(response, eventObject);
                },
                scope: this,
                json: true,
                type: "GETPOST"
            });
        }
    }
    else {
        fireResponse({ProvincesLength: 0, Provinces: {}});
    }
},

/**
 * Event handler executed when province/state data is returned from the server.
 * Should only be subscribed to by the 'contacts.prov_id' field.
 * @param type String Event name
 * @param args Object Event arguments
 */
onProvinceResponse1: function(type, args) {
    var response = args[0],
        options = '',
        provinces = response.Provinces,
        i, length;
    this.input.set("innerHTML", "");
    if (provinces) {
        if (!this.Y.Lang.isArray(provinces)) {
            // TK - remove when PHP toJSON converts array-like objects into arrays
            var temp = [];
            this.Y.Object.each(provinces, function(val) {
                temp.push(val);
            });
            provinces = temp;
        }

        if (!provinces[0] || (provinces[0].Name !== "--" && !this.data.hideEmptyOption)) {
            provinces.unshift({Name: "--", ID: ""});
        }
        for (i = 0, length = provinces.length; i < length; i++) {
            options += "<option value='" + provinces[i].ID + "'>" + provinces[i].Name + "</option>";
        }
        this.input.append(options);
        this.input.set('value', this.currentState1);
       this.input.setAttribute('required','required');
       this.data.attrs.required=true;
       // this.on("constraintChange", this.constraintChange, this);
    }else{
        this.input.removeAttribute('required');
        this.data.attrs.required=false;

    }
    //Any subsequent province requests should go back to the initial value '--'
    this.currentState1 = '';
},


});