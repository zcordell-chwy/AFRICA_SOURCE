RightNow.namespace("Custom.Widgets.sponsorship.UnsponsoredChildMultiline");
Custom.Widgets.sponsorship.UnsponsoredChildMultiline =
  RightNow.Widgets.Multiline.extend({
    overrides: {
      constructor: function () {
        // Call into parent's constructor
        this.parent();
        this.sponsorshipButtons = this.Y.all(".sponsorshipButton");
        this.sponsorshipButtons.on("click", this.sponsorshipClick, this);
      },
      /**
       * Event handler received when report data is changed.
       * @param {String} type Event name
       * @param {Array} args Arguments passed with event
       */
      _onReportChanged: function (type, args) {
        var newdata = args[0].data,
          ariaLabel,
          firstLink,
          newContent = "";

        this._displayDialogIfError(newdata.error);

        if (!this._contentDiv) return;

        if (newdata.total_num > 0) {
          ariaLabel = this.data.attrs.label_screen_reader_search_success_alert;
          newdata.hide_empty_columns = this.data.attrs.hide_empty_columns;
          newdata.hide_columns = this.data.js.hide_columns;
          var eventObj = new RightNow.Event.EventObject(this, {
            data: {
              w_id: this.data.info.w_id,
              // Parameters to send
              data: JSON.stringify(newdata),
            },
          });
          RightNow.Ajax.makeRequest(this.data.attrs.fix_data, eventObj.data, {
            successHandler: this.fix_dataCallback,
            scope: this,
            data: eventObj,
            json: true,
          });
          this._updateAriaAlert(ariaLabel);
        } else {
          ariaLabel =
            this.data.attrs.label_screen_reader_search_no_results_alert;
          this._updateAriaAlert(ariaLabel);
          this._contentDiv.set("innerHTML", newContent);

          if (this.data.attrs.hide_when_no_results) {
            this.Y.one(this.baseSelector)[
              newContent ? "removeClass" : "addClass"
            ]("rn_Hidden");
          }

          this._setLoading(false);
          RightNow.Url.transformLinks(this._contentDiv);

          if (newdata.total_num && (firstLink = this._contentDiv.one("a"))) {
            // focus on the first link whenever report data is loaded.
            firstLink.focus();
          }
        }
      },
      /**
       * Overridable methods from Multiline:
       *
       * Call `this.parent()` inside of function bodies
       * (with expected parameters) to call the parent
       * method being overridden.
       */ // _setFilter: function()        // _searchInProgress: function(evt, args)        // _setLoading: function(loading)        // _onReportChanged: function(type, args)        // _displayDialogIfError: function(error)        // _updateAriaAlert: function(text)
    },

    fix_dataCallback: function (response, originalEventObj) {
      newdata = JSON.parse(originalEventObj.data.data);
      newContent = new EJS({ text: this.getStatic().templates.view }).render(
        response
      );
      this._contentDiv.set("innerHTML", newContent);

      this.sponsorshipButtons = this.Y.all(".sponsorshipButton");
      this.sponsorshipButtons.on("click", this.sponsorshipClick, this);

      if (this.data.attrs.hide_when_no_results) {
        this.Y.one(this.baseSelector)[newContent ? "removeClass" : "addClass"](
          "rn_Hidden"
        );
      }

      this._setLoading(false);
      RightNow.Url.transformLinks(this._contentDiv);

      if (newdata.total_num && (firstLink = this._contentDiv.one("a"))) {
        // focus on the first link whenever report data is loaded.
        firstLink.focus();
      }
    },
    sponsorshipClick: function (args) {
      var thisObj = this;
      var child_id = args._currentTarget.getAttribute("data-ChildID");

      // Verify child is still unsponsored
      $.when(this.isChildUnsponsored(child_id)).then(
        // Success, child is still unsponsored
        function () {
          $.when(thisObj.isChildRecordLocked(child_id)).then(
            // Success, child record is not locked, so let's lock it
            function () {
              $.when(thisObj.lockChildRecord(child_id)).then(
                // Success, child record has been locked successfully, now let's store the sponsorship item to the cart for this user
                function () {
                  // let url = window.location.pathname + window.location.search;
                  // let newUrl = "/app/child/sponsor";
                  // newUrl = RightNow.Url.addParameter(newUrl, "id", child_id);
                  // RightNow.Url.navigate(newUrl);
                },
                // Failure, child record could not be locked successfully, so let's msg user
                function () {
                  alert(
                    "This unsponsored child is currently pending sponsorship from another user. Please select another child. \n\nYour page will refresh to present all unlocked children"
                  );
                  location.reload();
                }
              );
            },
            function () {
              alert(
                "This unsponsored child is currently pending sponsorship from another user. Please select another child. \n\nYour page will refresh to present all unlocked children"
              );
              location.reload();
            }
          );
        },
        function () {
          alert(
            "This child has already been sponsored. Please select another child."
          );
          location.reload();
        }
      );
    },

    // redirectToChildPage: function (id) {
    //   let url = window.location.pathname + window.location.search;
    //   let newUrl = "/app/child_info/";
    //   newUrl = RightNow.Url.addParameter(newUrl, "id", id);
    //   newUrl = RightNow.Url.addParameter(
    //     newUrl,
    //     "back",
    //     encodeURIComponent(url)
    //   );
    //   RightNow.Url.navigate(newUrl);
    // },

    /**
     * Deferred method that Performs an AJAX call to determine if the child is still unsponsored.
     * @param {integer} childID the ID of the child to check for sponsorship
     * @param deferred resolve, if the child record is unsponsored, otherwise a deferred reject
     */
    isChildUnsponsored: function (childID) {
      var dfd = $.Deferred();

      if (!this.data.attrs.check_for_lock) {
        dfd.resolve();
        return dfd.promise();
      }

      $.ajax({
        type: "GET",
        url: "/ci/AjaxCustom/isChildSponsored/" + childID,
        success: function (status) {
          if (status.isSponsored) dfd.reject();
          else dfd.resolve();
        },
        dataType: "json",
      });

      return dfd.promise();
    },

    /**
     * Deferred method that performs an AJAX call to lock a child record (reserve it for a single user's sponsorship transaction).
     * The purpose of this function is to prevent the scenario where two user's unknowingly sponsor the same child.
     * @param {integer} childID the ID of the child to lock
     * @param deferred resolve, if the child record was locked successfully, otherwise a deferred reject
     */
    lockChildRecord: function (childID) {
      var dfd = $.Deferred();

      if (!this.data.attrs.check_for_lock) {
        dfd.resolve(); //just say we locked it
        return dfd.promise();
      }

      $.ajax({
        type: "GET",
        url: "/ci/AjaxCustom/lockChildRecord/" + childID,
        success: function (status) {
          if (status.status == "success") {
            let newUrl = "/app/child/sponsor";
            newUrl = RightNow.Url.addParameter(newUrl, "id", childID);
            RightNow.Url.navigate(newUrl);
            dfd.resolve();
          } else dfd.reject();
        },
        dataType: "json",
      });

      return dfd.promise();
    },

    /**
     * Deferred method that Performs an AJAX call to determine if the unsponsored child record is locked (already in another user's
     * transaction for sponsorship). The purpose of this function is to prevent the scenario where two user's
     * unknowingly sponsor the same child.
     * @param {integer} childID the ID of the child to check for a record lock on
     * @param deferred resolve, if the child record is not locked, otherwise a deferred reject
     */
    isChildRecordLocked: function (childID) {
      var dfd = $.Deferred();

      if (!this.data.attrs.check_for_lock) {
        dfd.resolve();
        return dfd.promise();
      }

      $.ajax({
        type: "GET",
        url: "/ci/AjaxCustom/isChildRecordLocked/" + childID,
        success: function (status) {
          if (status.isLocked) dfd.reject();
          else dfd.resolve();
        },
        dataType: "json",
      });

      return dfd.promise();
    },
  });
