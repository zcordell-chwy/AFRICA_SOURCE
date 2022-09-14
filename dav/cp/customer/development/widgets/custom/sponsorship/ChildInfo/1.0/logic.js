RightNow.namespace("Custom.Widgets.sponsorship.ChildInfo");
Custom.Widgets.sponsorship.ChildInfo = RightNow.Widgets.extend({
  /**
   * Widget constructor.
   */
  constructor: function () {
    // let sponsorButton = this.Y.one(
    //   document.getElementById("sponsorshipButton")
    // );
    let sponsorshipButtons = this.Y.one(
      document.getElementById("sponsorshipButton")
    );
    if (sponsorshipButtons)
      sponsorshipButtons.on("click", this.sponsorChildAjax, this);

    // this.destroySponsorButtonEventHandler(sponsorButton);
    // this.setupSponsorButtonEventHandler(sponsorButton);
  },
  /**
   * Sample widget method.
   */
  methodName: function () {},

  // Handles clicking the child sponsor link
  setupSponsorButtonEventHandler: function (sponsorButton) {
    var childID = this.data.js.ChildID,
      childRate = this.data.js.Rate,
      self = this;

    // sponsorButton.on("click", function () {

    //     // Ignore click if we're allowing anonymous advocacy. This link should not be shown
    //     // in this scenario, but de-activate here as a fail-safe.
    //     if (RightNow.Profile.isLoggedIn()) {
    //         self.sponsorChildAjax(childID, childRate, this);
    //     } else {
    //         let url = window.location.pathname + window.location.search;

    //         let newUrl = '/app/utils/login_form/'
    //         newUrl = RightNow.Url.addParameter(newUrl, 'redirect', encodeURIComponent(url));
    //         RightNow.Url.navigate(newUrl);
    //     }
    // });
  },

  // Tears down the child sponsor link event handler
  destroySponsorButtonEventHandler: function (sponsorButton) {
    // sponsorButton.detach('click');
  },

  // Performs the AJAX call to sponsor a child and redirects to the sponsorship page to finalize payment details.
  // But first runs logic to make sure another user has not already begun the process to sponsor this child using a
  // record lock system.
  sponsorChildAjax: function (args) {
    var child_id = this.data.js.ChildID;
    var childAlreadySponsoredMsg =
        "This child has already been sponsored. Please select another child.",
      failedToAcquireLockOnChildMsg =
        "This unsponsored child is currently pending sponsorship from another user. Please select another child. \n\nYour page will refresh to present all unlocked children",
      self = this;

    // Verify child is still unsponsored
    $.when(this.isChildUnsponsored(child_id)).then(
      // Success, child is still unsponsored
      function () {
        $.when(self.isChildRecordLocked(child_id)).then(
          // Success, child record is not locked, so let's lock it
          function () {
            $.when(self.lockChildRecord(child_id)).then(
              // Success, child record has been locked successfully, now let's store the sponsorship item to the cart for this user
              function () {
                var redirectUrl = "/app/child/sponsor/id/" + child_id;
                if (redirectUrl) window.location.replace(redirectUrl);
              },
              // Failure, child record could not be locked successfully, so let's msg user
              function () {
                alert(failedToAcquireLockOnChildMsg);
              }
            );
          },
          // Failure, child record is locked, so let's msg user
          function () {
            alert(failedToAcquireLockOnChildMsg);
          }
        );
      },
      // Failure, child is sponsored
      function () {
        alert(childAlreadySponsoredMsg);
      }
    );
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
        if (status.status == "success") dfd.resolve();
        else dfd.reject();
      },
      dataType: "json",
    });

    return dfd.promise();
  },
});
