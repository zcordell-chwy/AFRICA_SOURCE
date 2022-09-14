RightNow.namespace('Custom.Widgets.sponsorship.UnsponsoredChildCarousel');
Custom.Widgets.sponsorship.UnsponsoredChildCarousel = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function () {
        let searchParams = new URLSearchParams(window.location.search);
        this.carousel = this.Y.one(this.baseSelector + '_Carousel');
        this.start = Number(searchParams.get('start') ? searchParams.get('start') : 0);
        this.limit = Math.floor(this.carousel.get('offsetWidth') / 250);
        window.addEventListener('resize', ((self) => (e) => {
            clearTimeout(self.timeOutFunctionId);
            self.timeOutFunctionId = setTimeout(() => {
                self.limit = Math.floor(self.carousel.get('offsetWidth') / 250);
                self.next.setAttribute('disabled');
                self.back.setAttribute('disabled');
                self.getRefresh_data(self.start, self.limit);
            }, 500);
        })(this));
        this.getRefresh_data(this.start, this.limit);

        this.back = this.Y.one(this.baseSelector + '_Back');
        if (this.start < 1) this.back.setAttribute('disabled');
        this.back.on('click', ((self) => (e) => {
            self.limit = Math.floor(self.carousel.get('offsetWidth') / 250);
            self.start -= self.limit;
            if (self.start < 0) {
                self.start = 0;
            }
            self.next.setAttribute('disabled');
            self.back.setAttribute('disabled');
            self.getRefresh_data(self.start, self.limit);
        })(this));

        this.next = this.Y.one(this.baseSelector + '_Next');
        this.next.on('click', ((self) => (e) => {
            self.limit = Math.floor(self.carousel.get('offsetWidth') / 250);
            self.start += self.limit;
            self.next.setAttribute('disabled');
            self.back.setAttribute('disabled');
            self.getRefresh_data(self.start, self.limit);
        })(this));
    },
    /**
     * Sample widget method.
     */
    methodName: function () {
    },
    /**
     * Makes an AJAX request for `refresh_data`.
     */
    getRefresh_data: function () {
        this.carousel.setHTML('<img src="/euf/assets/images/loading.gif">');

        if (history.pushState) {
            let searchParams = new URLSearchParams(window.location.search);
            searchParams.set("start", this.start);
            let newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + searchParams.toString();
            window.history.pushState({ path: newurl }, '', newurl);
        }

        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {
            data: {
                w_id: this.data.info.w_id,
                // Parameters to send
                start: this.start,
                limit: this.limit,
            }
        });
        RightNow.Ajax.makeRequest(this.data.attrs.refresh_data, eventObj.data, {
            successHandler: this.refresh_dataCallback,
            scope: this,
            data: eventObj,
            json: true
        });
    },
    /**
     * Handles the AJAX response for `refresh_data`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getRefresh_data
     */
    refresh_dataCallback: function (response, originalEventObj) {
        // Handle response
        this.carousel.setHTML('');
        response.forEach(element => {
            this.renderView(element);
        });
        if (this.start > 0) {
            this.back.removeAttribute('disabled');
        }
        this.next.removeAttribute('disabled');

    },
    /**
     * Renders the `view.ejs` JavaScript template.
     */
    renderView: function (data) {
        // JS view:
        var content = new EJS({ text: this.getStatic().templates.view }).render({
            // Variables to pass to the view
            // display: this.data.attrs.display
            data: data
        });
        var div = document.createElement('div');
        div.innerHTML = content;
        this.Y.one(div.querySelector('.childInfo')).on('click', () => {
            let url = window.location.pathname + window.location.search;
            let newUrl = '/app/child_info/';
            newUrl = RightNow.Url.addParameter(newUrl, 'id', data['ID']);
            newUrl = RightNow.Url.addParameter(newUrl, 'back', encodeURIComponent(url));
            RightNow.Url.navigate(newUrl);
        })


        let sponsorButton = this.Y.one(div.querySelector('.sponsorshipButton'));

        this.destroySponsorButtonEventHandler(sponsorButton);
        this.setupSponsorButtonEventHandler(sponsorButton);

        // sponsorButton.on('click', () => {
        //     console.log("hi");
        // });

        this.carousel.append(div);
    },

    // Handles clicking the child sponsor link
    setupSponsorButtonEventHandler: function (sponsorButton) {

        var childID = sponsorButton.getAttribute("data-childID"),
            childRate = sponsorButton.getAttribute("data-childRate"),
            self = this;


        sponsorButton.on("click", function () {

            // Ignore click if we're allowing anonymous advocacy. This link should not be shown
            // in this scenario, but de-activate here as a fail-safe.
            if (RightNow.Profile.isLoggedIn()) {
                self.sponsorChildAjax(childID, childRate, this);
            } else {
                let url = window.location.pathname + window.location.search;
                let childUrl = '/app/child_info/';
                childUrl = RightNow.Url.addParameter(childUrl, 'id', childID);
                childUrl = RightNow.Url.addParameter(childUrl, 'back', encodeURIComponent(url));
                let newUrl = '/app/utils/login_form/'
                newUrl = RightNow.Url.addParameter(newUrl, 'redirect', encodeURIComponent(childUrl));
                RightNow.Url.navigate(newUrl);
            }
        });
    },

    // Tears down the child sponsor link event handler
    destroySponsorButtonEventHandler: function (sponsorButton) {
        sponsorButton.detach('click');
    },

    // Performs the AJAX call to sponsor a child and redirects to the sponsorship page to finalize payment details.
    // But first runs logic to make sure another user has not already begun the process to sponsor this child using a
    // record lock system.
    sponsorChildAjax: function (childID, childRate, sponsorButton, isWomanScholarship = false, isOneTime = false) {
        var childAlreadySponsoredMsg =
            "This child has already been sponsored. Please select another child.",
            failedToAcquireLockOnChildMsg =
                "This unsponsored child is currently pending sponsorship from another user. Please select another child. \n\nYour page will refresh to present all unlocked children",
            self = this
            // , sponsorButtonContainerObj = sponsorButton.parent()
            //, processingMessageObj = $("<p>Processing request...<img src=\"/euf/assets/images/loading.gif\" width=\"20\" height=\"20\" /></p>")
            ;

        // Hide 'sponsor me' link while we're checking for/applying lock and show processing message to account for delay.
        sponsorButton.setAttribute('disabled');
        // sponsorButtonContainerObj.append(processingMessageObj);

        // Verify child is still unsponsored
        $.when(this.isChildUnsponsored(childID)).then(
            // Success, child is still unsponsored
            function () {
                $.when(self.isChildRecordLocked(childID)).then(
                    // Success, child record is not locked, so let's lock it
                    function () {
                        $.when(self.lockChildRecord(childID)).then(
                            // Success, child record has been locked successfully, now let's store the sponsorship item to the cart for this user
                            function () {
                                var itemsInCart = [];

                                itemsInCart.push({
                                    "itemName": null,
                                    'oneTime': null,
                                    'recurring': null,
                                    'fund': null,
                                    'appeal': null,
                                    'childId': childID,
                                    'child_sponsorship': !isWomanScholarship,
                                    'isWomensScholarship': isWomanScholarship,
                                });

                                $.ajax({
                                    type: "POST",
                                    url: '/ci/AjaxCustom/storeCartData',
                                    data: "form=" + JSON.stringify({
                                        'total': childRate,
                                        'items': itemsInCart,
                                        'donateValCookieContent': null
                                    }),
                                    processData: false,
                                    success: function () {
                                        document.location.replace("/app/payment/checkout");
                                    },
                                    dataType: 'json'
                                });
                            },
                            // Failure, child record could not be locked successfully, so let's msg user
                            function () {
                                alert(failedToAcquireLockOnChildMsg);
                                // Remove processing msg and reveal the sponsor me link
                                // processingMessageObj.remove();
                                sponsorButton.removeAttribute('disabled');
                                // Close magnific popup window as convenience to user to get them back to gallery quicker
                                $.magnificPopup.close();
                            }
                        )
                    },
                    // Failure, child record is locked, so let's msg user
                    function () {
                        alert(failedToAcquireLockOnChildMsg);
                        // Remove processing msg and reveal the sponsor me link
                        // processingMessageObj.remove();
                        sponsorButton.removeAttribute('disabled');
                        // Close magnific popup window as convenience to user to get them back to gallery quicker
                        $.magnificPopup.close();
                        location.reload();
                    }
                );
            },
            // Failure, child is sponsored
            function () {
                alert(childAlreadySponsoredMsg);
                // Remove processing msg and reveal the sponsor me link
                // processingMessageObj.remove();
                sponsorButton.removeAttribute('disabled');
                // Close magnific popup window as convenience to user to get them back to gallery quicker
                $.magnificPopup.close();
                location.reload();
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
            url: '/ci/AjaxCustom/isChildRecordLocked/' + childID,
            success: function (status) {
                if (status.isLocked) dfd.reject();
                else dfd.resolve();
            },
            dataType: "json"
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
            url: '/ci/AjaxCustom/isChildSponsored/' + childID,
            success: function (status) {
                if (status.isSponsored) dfd.reject();
                else dfd.resolve();
            },
            dataType: "json"
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
            dfd.resolve() //just say we locked it
            return dfd.promise();
        }

        $.ajax({
            type: "GET",
            url: '/ci/AjaxCustom/lockChildRecord/' + childID,
            success: function (status) {
                if (status.status == "success") dfd.resolve();
                else dfd.reject();
            },
            dataType: "json"
        });

        return dfd.promise();
    }
});