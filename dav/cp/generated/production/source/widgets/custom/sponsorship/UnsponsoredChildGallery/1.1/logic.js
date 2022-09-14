RightNow.namespace('Custom.Widgets.sponsorship.UnsponsoredChildGallery');
Custom.Widgets.sponsorship.UnsponsoredChildGallery = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function() {
        // Set child image link height to match width while images are loading so that loading indicator
        // appears centered vertically. We have to do this with javascript since the child image width
        // is set dynamically based on number of columns.
        /*$.each($(this.baseSelector + " a.rn_UnsponsoredChildImageLink"), function(index, childImageLink){
            childImageLink = $(childImageLink);
            childImageLink.css("height", childImageLink.css("width"));
        });*/

        // This widget requires a string replaceAll method
        if(!String.prototype.replaceAll){
            String.prototype.replaceAll = function(search, replacement) {
                var target = this;
                return target.replace(new RegExp(search, 'g'), replacement);
            };
        }

        this.searchFiltersSubmitButton = $(".rn_UnsponsoredChildFiltersForm input[type='submit']");
        var thisObj = this;
        this.searchFiltersSubmitButton.click(function(evt){
            thisObj.onSearchFiltersSubmit(evt);
        });

        // Load child images
        this.imagesTotal = $(".rn_UnsponsoredChildImageLink").length;
        this.imagesLoaded = 0;
        this.loadImages();
        
        this._contentDiv = this.Y.one(this.baseSelector + "_Content");
        this._loadingDiv = this.Y.one(this.baseSelector + "_Loading");
        if(this.data.js.confirmationMessage != "" && typeof(this.data.js.confirmationMessage) !== 'undefined'){
            this.Y.one("#advocateConfirmation").setHTML(this.data.js.confirmationMessage); 
        }
    },

    // Returns the value of a filter in the search filters form given its jQuery selector
    getFilterValue: function(selector, isInt){
        if ( !$(selector).val()) {
            return 0;
        }else{
            isInt = isInt != null ? isInt : true;
            return isInt ? parseInt($(selector).val()) : $(selector).val();
        }
        
    },

    // Handle search filters submission with redirect so that we can translate filter values into "/<key>/<value>" pairs and add those to URL
    onSearchFiltersSubmit: function(evt){
        // Reset current page to 1 for new search
        this.data.js.currentPage = 1;
        var redirectUrl = this.data.attrs.advocacy_page ? "/app/advocacy" : "/app/home";
        
        if(this.data.attrs.data_source == 'woman')
            redirectUrl = "/app/womens_ministry";

        redirectUrl += this.buildSearchFilterURLParamString();
        window.location = redirectUrl;

        // Prevent default form submit action
        evt.preventDefault();
        return false;
    },

    // Builds the search filter URL param string (ex: /community/1/age/2/page/2).
    // If redirectSafe is true, slashes in param string will be replaced with %252F characters so that the param string can be safely used
    // as the value for a CP redirect param
    buildSearchFilterURLParamString: function(redirectSafe){
        redirectSafe = redirectSafe != null ? redirectSafe : false;

        if(this.data.attrs.data_source == 'woman'){
            var rs = ".rn_UnsponsoredChildFiltersForm select.",
                params = [
                    {name: "program", value: this.getFilterValue(rs + "rn_ProgramFilter")},
                ];
        }else{
            var rs = ".rn_UnsponsoredChildFiltersForm select.",
                params = [
                    {name: "priority", value: this.getFilterValue(rs + "rn_PriorityFilter")},
                    {name: "community", value: this.getFilterValue(rs + "rn_CommunityFilter")},
                    {name: "age", value: this.getFilterValue(rs + "rn_AgeFilter")},
                    {name: "gender", value: this.getFilterValue(rs + "rn_GenderFilter")},
                    {name: "page", value: this.data.js.currentPage},
                    {name: "event", value: (this.data.js.eventId) ? this.data.js.eventId : 0},
                    {name: "monthofbirth", value: this.getFilterValue(rs + "rn_MonthFilter")},
                    {name: "yearofbirth", value: this.getFilterValue("#rn_yearofbirth")}

                ];
        }

        // Filter out params set to 0 ("All")
        params = params.filter(function(param){
            return param.value !== 0;
        });

        // Build param string
        var urlParamString = "",
            fwdSlashCharacter = redirectSafe ? "%252F" : "/";
        $.each(params, function(index, param){
                urlParamString += fwdSlashCharacter + param.name + fwdSlashCharacter + param.value;
        });
        return urlParamString;
    },

    // Iterates through the unsponsored child image link a tags and loads a new image based on the data-imgSrc
    // attribute. Upon success, sets the loaded image as the link HTML and increments the imagesLoaded counter. 
    // Once all images have been loaded, calls setupPopupGallery to initialize the magnific popup jQuery plugin
    // which creates the image gallery functionality.
    loadImages: function(){
        var thisObj = this,
            childLinks = $(".rn_UnsponsoredChildImageLink");
        $.each(childLinks, function(index, currLink){
            var link = $(currLink),
                imgSrc = link.attr("data-imgSrc"),
                imgTitle = link.attr("data-imgTitle"),
                imgAlt = link.attr("data-imgAlt"),
                childThumb = $("<img>", {class: "rn_UnsponsoredChildImage", title: imgTitle, alt: imgAlt}),
                imgWidthInPx = parseInt(link.css("width")),
                dynImgHeight = imgWidthInPx * 1.2;

            childThumb.get(0).onload = function(){
                thisObj.imagesLoaded++;
                link.html(childThumb);
                //link.css("height", "inherit");
                // Dynamically scale the link and image height based on the current width to preserve a decent aspect ratio (1:1.2)
                // for the image
                //link.css("height", dynImgHeight + "px");
                //link.css("width", "initial");
                /*childThumb.css("width", imgWidthInPx + "px");
                childThumb.css("height", dynImgHeight + "px");*/
                if(thisObj.allImagesLoaded()){
                    thisObj.setupPopupGallery();
                }
            };
            childThumb.attr("src", imgSrc)
        });
    },

    // Tests if all images have loaded
    allImagesLoaded: function(){
        return this.imagesLoaded === this.imagesTotal;
    },

    // Initialize magnific popup jQuery plugin which creates the image gallery functionality
    setupPopupGallery: function(){
        // Update all image links with the correct src now that they are all loaded. Before,
        // the src was set to 'javascript: void(0);' to prevent the user from clicking the image
        // links before all the images had been loaded and the popup gallery had been setup
        $.each($(".rn_UnsponsoredChildImageLink"), function(index, currImgLink){
            var imgLink = $(currImgLink);
            imgLink.attr("href", imgLink.attr("data-imgSrc"));
        });
        // Setup magnific pop-up jQuery plugin
        // magnific pop-up webpage/documentation: http://dimsemenov.com/plugins/magnific-popup/documentation.html
        // magnific pop-up .js and .css included in templates/standard.php
        var thisObj = this;

        // Note: sponsorLink text is set later when the popup is opened based on login status
        if(this.data.attrs.data_source == 'woman'){
            var sponsorLink = '<div class="rn_WomanSponsorInput">' +
                '$<input type="number" class="womensScholarshipInput" id="womanSponsorRate_##ID##" placeholder="$25 Recommended"         min="0"' +
                'step="1"' + 
                'onfocus="this.previousValue = this.value"' +
                'onkeydown="this.previousValue = this.value"' +
                'oninput="validity.valid || (value = this.previousValue)"/>' +
                '<a class="rn_WomanInfoSponsorLink" ' +
                'href="javascript: void(0);" ' +
                'data-childRate="##RATE##" ' +
                'data-childID="##ID##">Monthly&gt;</a>' + '<p class="amountToFullScholarship">Or</p>' +
                '<p >$<input type="number" class="womensScholarshipInput" id="womanSponsorOneTimeRate_##ID##" placeholder="$50 recommended" min="0"' +
                'step="1"' + 
                'onfocus="this.previousValue = this.value"' +
                'onkeydown="this.previousValue = this.value"' +
                'oninput="validity.valid || (value = this.previousValue)"/>' +
                '<a class="rn_WomanInfoOneTimeSponsorLink" ' +
                'href="javascript: void(0);" ' +
                'data-childRate="##RATE##" ' +
                'data-childID="##ID##">One Time&gt;</a></p>' +
                '<p class="amountToFullScholarship">$##REMAINING##/month left until a full scholarship is met.</p>' +
                '<p class="womansSponsorAdditionalInfo">'+this.data.attrs.donation_additional_info+'</p></div>';
        }else{
            var sponsorLink =
            '<a class="rn_ChildInfoSponsorLink" ' +
            'href="javascript: void(0);" ' +
            'data-childRate="##RATE##" ' +
            'data-childID="##ID##"></a>';
        }
        

        var anonAdvocacySponsorMsg = 
            "Please use the following link to view this child's page, and share it with friends and " +
            "family who may be interested in sponsoring this child (the link will deactivate once the " +
            "child is sponsored):";
        var anonAdvocacySponsorLinkText = this.data.js.childSponsorURL + '##ID##';
        
        if(this.data.attrs.data_source == 'woman'){
            var infoTemplate =
                '<div class="rn_ChildInfoContainer">' +
                '<div class="rn_ChildAttrContainer">' +
                '<p class="rn_ChildNamePara rn_ChildInfoPara">##NAME##</p>' +
                '<p class="rn_ChildRefPara rn_ChildInfoPara"><span class="rn_ChildInfoParaLabel">Reference:</span>&nbsp;##REF##</p>' +
                '<p class="rn_ChildRefPara rn_ChildInfoPara"><span class="rn_ChildInfoParaLabel">Program:</span>&nbsp;##PROGRAM##</p>' +
                '<p class="rn_ChildSponsorLinkPara rn_ChildInfoPara"></p>' +
                '</div>' +
                '<div class="rn_ChildBioContainer">' +
                '<p class="rn_ChildBioPara">##BIO##</p>' +
                '</div>' +
                '<div class="rn_ChildAnonAdvocacyContainer">' +
                    '<div class="rn_ChildAnonAdvocacyHeader">' +
                        anonAdvocacySponsorMsg +
                    '</div>' +
                    '<div class="rn_ChildAnonAdvocacyLinkText">' +
                        '<a href="' + anonAdvocacySponsorLinkText + '" target="_blank">' + anonAdvocacySponsorLinkText + '</a>' +
                    '</div>' +
                '</div>' + sponsorLink +
                '</div>';
        }else{
            var infoTemplate =
                '<div class="rn_ChildInfoContainer">' +
                '<div class="rn_ChildAttrContainer">' +
                '<p class="rn_ChildNamePara rn_ChildInfoPara">##NAME##</p>' +
                '<p class="rn_ChildBirthPara rn_ChildInfoPara"><span class="rn_ChildInfoParaLabel">Birthdate:</span>&nbsp;##BIRTH##</p>' +
                '<p class="rn_ChildGenderPara rn_ChildInfoPara"><span class="rn_ChildInfoParaLabel">Gender:</span>&nbsp;##GENDER##</p>' +
                '<p class="rn_ChildAgePara rn_ChildInfoPara"><span class="rn_ChildInfoParaLabel">Age:</span>&nbsp;##AGE##</p>' +
                '<p class="rn_ChildRefPara rn_ChildInfoPara"><span class="rn_ChildInfoParaLabel">Reference:</span>&nbsp;##REF##</p>' +
                '<p class="rn_ChildRatePara rn_ChildInfoPara"><span class="rn_ChildInfoParaLabel">Sponsorship Rate:</span>&nbsp;$##RATE##.00/mo</p>' +
                '<p class="rn_ChildSponsorLinkPara rn_ChildInfoPara">' + sponsorLink + '</p>' +
                '</div>' +
                '<div class="rn_ChildBioContainer">' +
                '<p class="rn_ChildBioPara">##BIO##</p>' +
                '</div>' +
                '<div class="rn_ChildAnonAdvocacyContainer">' +
                    '<div class="rn_ChildAnonAdvocacyHeader">' +
                        anonAdvocacySponsorMsg +
                    '</div>' +
                    '<div class="rn_ChildAnonAdvocacyLinkText">' +
                        '<a href="' + anonAdvocacySponsorLinkText + '" target="_blank">' + anonAdvocacySponsorLinkText + '</a>' +
                    '</div>' +
                '</div>' +
                '</div>';
        }

        $(".rn_UnsponsoredChildImageGallery").magnificPopup({
            delegate: 'a', // child items selector, by clicking on it popup will open
            type: 'image',
            image: {
                // Popup HTML markup. `.mfp-img` div will be replaced with img tag, `.mfp-close` by close button
                markup: '<div class="mfp-figure">' +
                    '<div class="mfp-close"></div>' +
                    '<div class="mfp-img"></div>' +
                    '<div class="mfp-bottom-bar">' +
                    '<div class="mfp-title"></div>' +
                    '<div class="rn_UnsponsoredChildGallery"></div>' +
                    '<div class="mfp-counter"></div>' +
                    '</div>' +
                    '</div>',
                cursor: 'mfp-zoom-out-cur',
                titleSrc: 'title'
            },
            gallery: {
                enabled: true, // set to true to enable gallery
                preload: [0, 2], // read about this option in next Lazy-loading section
                navigateByImgClick: true,
                arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>', // markup of an arrow button
                tPrev: 'Previous (Left arrow key)', // title for left button
                tNext: 'Next (Right arrow key)', // title for right button
                tCounter: '<span class="mfp-counter">%curr% of %total%</span>' // markup of counter
            },
            // Align popup image to top of screen instead of center
            alignTop: true,
            callbacks: {
                // Called each time a popup is opened and the markup for the popup is parsed
                markupParse: function(template, values, item) {
                    // Replace the placeholder values (ex: ##RATE##) in the child info template with the appropriate attribute value
                    var childInfoContent = infoTemplate;
                    $.each(item.el[0].attributes,
                        function(index, attr) {
                            if (attr.name.substr(0, 5) === "data-") {

                                var appendedValue = attr.value;
                                if(attr.name == 'data-bio' &&  attr.value.length > 200){
                                    appendedValue =  attr.value.substr(0,200) + "<span id='bio_trailing' class='rn_Hidden'>" +  attr.value.substr(200, (attr.value.length - 200)) + "</span><a id='more_info'>...more</a>";
                                }

                                var name = attr.name.substr(5),
                                    value = appendedValue,
                                    placeholder = "##" + name.toUpperCase() + "##"
                                childInfoContent = childInfoContent.replaceAll(placeholder, value);
                            }
                        }
                    );
                    $(template[0]).find(".rn_UnsponsoredChildGallery").html(childInfoContent);

                },
                // Called each time popup HTML is changed (when popup is first opened or next/previous image is loaded in gallery)
                change: function(){
                    thisObj.setSponsorLinkTextBasedOnLoginStatus(this.content);
                    thisObj.destroySponsorLinkEventHandler(this.content);
                    thisObj.setupSponsorLinkEventHandler(this.content);


                    
                    if(thisObj.data.attrs.advocacy_page && thisObj.data.js.allowAnonymousAdvocacy){
                        thisObj.showAnonAdvocacyInfo(this.content);
                    }
                }
            }
        });

        // Support pre-popping the gallery image viewer to a specific child
        if(!!this.data.js.selectedChildID){
            var byIDSelector = ".rn_UnsponsoredChildImageLink[data-id='" + this.data.js.selectedChildID + "']",
                childLink = $(byIDSelector).first();
            if(childLink.length !== 0){ 
                // Valid child ID given, pop up child image in gallery
                childLinkIndex = childLink.attr("data-linkIndex");
                $(".rn_UnsponsoredChildImageGallery").magnificPopup('open', childLinkIndex); 
            } 
        }else if(!!this.data.js.selectedWomanID){
            var byIDSelector = ".rn_UnsponsoredChildImageLink[data-id='" + this.data.js.selectedWomanID + "']",
                childLink = $(byIDSelector).first();
            if(childLink.length !== 0){ 
                // Valid child ID given, pop up child image in gallery
                childLinkIndex = childLink.attr("data-linkIndex");
                $(".rn_UnsponsoredChildImageGallery").magnificPopup('open', childLinkIndex); 

                //need to set the values if we have them.
                if(this.data.js.oneTimeVal && this.data.js.oneTimeVal > 0){
                    let inputNode = $("#womanSponsorOneTimeRate_"+this.data.js.selectedWomanID);
                    inputNode.val(this.data.js.oneTimeVal);
                }
                    
                if(this.data.js.recurringVal && this.data.js.recurringVal > 0){
                    let inputNode = $("#womanSponsorRate_"+this.data.js.selectedWomanID);
                    inputNode.val(this.data.js.recurringVal);
                }
                    

            } 
        }
    },

    // Shows the anonymous advocacy info and hides the sponsor link.
    showAnonAdvocacyInfo: function(popupHTML){
        var anonAdvocContainer = popupHTML.find(".rn_ChildAnonAdvocacyContainer").first(),
            sponsorLink = popupHTML.find(".rn_ChildInfoSponsorLink").first();
            anonAdvocContainer.addClass("Active");
            sponsorLink.addClass("Inactive");
    },

    // Sets the sponsor link text to "Sponsor me" (if logged in) or "Login to Sponsor me" (if not logged in)
    setSponsorLinkTextBasedOnLoginStatus: function(popupHTML){
        var sponsorLink = popupHTML.find(".rn_ChildInfoSponsorLink").first(),
            sponsorLinkText = null, 
            childID = sponsorLink.attr("data-childID");

        if(RightNow.Profile.isLoggedIn()){
            if(this.data.attrs.advocacy_page){
                advocated = jQuery.grep(this.data.js.advocacies, function( a ) { 
                    return ( a.ChildId == childID ); 
                });
                if (advocated.length > 0){
                    sponsorLinkText = "";
                }else{
                    sponsorLinkText = "Become An Advocate For Me &gt;";
                }
            }else{
                sponsorLinkText = "Sponsor Me &gt;";
            }
            
        }else{
            // Apparently we don't want to use verbiage "Login to sponsor" anymore
            sponsorLinkText = "Sponsor Me &gt;";
        }
        sponsorLink.html(sponsorLinkText);
    },

    // Handles clicking the child sponsor link
    setupSponsorLinkEventHandler: function(popupHTML){

        //set up more/less handler too
        var moreInfoLink = popupHTML.find("#more_info").first();
        if(moreInfoLink){
            moreInfoLink.on("click", function(evt, args){
                $("#bio_trailing").toggleClass('rn_Hidden');
                if(moreInfoLink.html() == '...more')
                    moreInfoLink.html("...less");
                else
                    moreInfoLink.html("...more");
            });
        }
        

        if(this.data.attrs.data_source == 'woman'){
            var sponsorLink = popupHTML.find(".rn_WomanInfoSponsorLink").first(),
                childID = sponsorLink.attr("data-childID"),
                //childRate = $("#womanSponsorRate_"+childID).val(),
                childRate = sponsorLink.attr("data-childRate"),
                thisObj = this;
            var sponsorLinkOneTime = popupHTML.find(".rn_WomanInfoOneTimeSponsorLink").first(),
                childID = sponsorLink.attr("data-childID"),
                //childRate = $("#womanSponsorRate_"+childID).val(),
                childRate = sponsorLink.attr("data-childRate"),
                oneTime = true,
                thisObj = this;
        }else{
            var sponsorLink = popupHTML.find(".rn_ChildInfoSponsorLink").first(),
                childID = sponsorLink.attr("data-childID"),
                childRate = sponsorLink.attr("data-childRate"),
                thisObj = this;
        }
       
            
        sponsorLink.on("click", function(){

            
            // Ignore click if we're allowing anonymous advocacy. This link should not be shown
            // in this scenario, but de-activate here as a fail-safe.
            if(thisObj.data.attrs.advocacy_page && thisObj.data.js.allowAnonymousAdvocacy){
                return;
            }else if(RightNow.Profile.isLoggedIn()){
                childRate = $("#womanSponsorRate_"+childID).val();
                if(thisObj.data.attrs.advocacy_page){
                    thisObj._setLoading(true);
                    thisObj.advocateChildAjax(childID, thisObj.data.js.eventId);
                }else{
                    if(thisObj.data.attrs.data_source == 'woman'){
                        var isWomanScholarship = true;
                    }
                    thisObj.sponsorChildAjax(childID, childRate, this, isWomanScholarship, false);
                }
            }else{

                if(thisObj.data.attrs.data_source == 'woman'){
                    let rateSuffix = '';
                    //womanSponsorRate_21
                    if ($("#womanSponsorRate_"+childID).val() > 0) {
                            rateSuffix = "%252Frecurring%252F" + $("#womanSponsorRate_"+childID).val();
                    }
                    var redirectUrl = "/app/utils/login_form/redirect/womens_ministry" + thisObj.buildSearchFilterURLParamString(true) + "%252Fwoman%252F" + childID  + rateSuffix;
                }else{
                    var redirectUrl = "/app/utils/login_form/redirect/home" + thisObj.buildSearchFilterURLParamString(true) + "%252Fchild%252F" + childID;
                }
                
                window.location.replace(redirectUrl);
            }
        });

        if(sponsorLinkOneTime){
            sponsorLinkOneTime.on("click", function(){
                childRate = $("#womanSponsorOneTimeRate_"+childID).val();

                if(RightNow.Profile.isLoggedIn()){
                        if(thisObj.data.attrs.data_source == 'woman'){
                            var isWomanScholarship = true;
                        }
                        thisObj.sponsorChildAjax(childID, childRate, this, isWomanScholarship, true);
                }else{

                    if(thisObj.data.attrs.data_source == 'woman'){
                        let rateSuffix = '';
                        if ($("#womanSponsorOneTimeRate_"+childID).val() > 0) {
                             rateSuffix = "%252Fonetime%252F" + $("#womanSponsorOneTimeRate_"+childID).val();
                        }
                        var redirectUrl = "/app/utils/login_form/redirect/womens_ministry" + thisObj.buildSearchFilterURLParamString(true) + "%252Fwoman%252F" + childID + rateSuffix;
                    }else{
                        var redirectUrl = "/app/utils/login_form/redirect/home" + thisObj.buildSearchFilterURLParamString(true) + "%252Fchild%252F" + childID ;
                    }
                    
                    window.location.replace(redirectUrl);
                }
            });
        }



    },

    // Tears down the child sponsor link event handler
    destroySponsorLinkEventHandler: function(popupHTML){
        var sponsorLink = popupHTML.find(".rn_ChildInfoSponsorLink").first();
        sponsorLink.off();
    },
    
    
    // Performs the AJAX call to sponsor a child and redirects to the sponsorship page to finalize payment details
    advocateChildAjax: function(childId, eventId){
        var itemsInCart = [];
        itemsInCart.push({
            'childId' : childId,
            'eventId' : eventId
        });

        $.ajax({
            type: "POST",
            url: '/ci/AjaxCustom/createAdvocacyRelationship',
            data: "form=" + JSON.stringify({
                'items' : itemsInCart,
            }),
            processData: false,
            success: function(response, status) {
                document.location.replace(response.result.redirectOverride + "/success/" + response.data.childId);
            },
            dataType: 'json'
        });
    },
    

    // Performs the AJAX call to sponsor a child and redirects to the sponsorship page to finalize payment details.
    // But first runs logic to make sure another user has not already begun the process to sponsor this child using a
    // record lock system.
    sponsorChildAjax: function(childID, childRate, sponsorChildLink, isWomanScholarship = false, isOneTime = false){
        var childAlreadySponsoredMsg = 
            "This child has already been sponsored. Please select another child.",
            failedToAcquireLockOnChildMsg = 
            "This unsponsored child is currently pending sponsorship from another user. Please select another child. \n\nYour page will refresh to present all unlocked children",
            thisObj = this,
            sponsorChildLinkObj = $(sponsorChildLink),
            sponsorChildLinkContainerObj = sponsorChildLinkObj.parent(),
            processingMessageObj = $("<p>Processing request...<img src=\"/euf/assets/images/loading.gif\" width=\"20\" height=\"20\" /></p>");

        // Hide 'sponsor me' link while we're checking for/applying lock and show processing message to account for delay.
        sponsorChildLinkObj.hide();
        sponsorChildLinkContainerObj.append(processingMessageObj);

        // Verify child is still unsponsored
        $.when(this.isChildUnsponsored(childID)).then(
            // Success, child is still unsponsored
            function(){
                $.when(thisObj.isChildRecordLocked(childID)).then(
                    // Success, child record is not locked, so let's lock it
                    function(){
                        $.when(thisObj.lockChildRecord(childID)).then(
                            // Success, child record has been locked successfully, now let's store the sponsorship item to the cart for this user
                            function(){
                                var itemsInCart = [];

                                itemsInCart.push({
                                    "itemName" : null,
                                    'oneTime' : (isOneTime) ? childRate: null,
                                    'recurring' : (thisObj.data.attrs.data_source == 'woman' && !isOneTime) ? childRate : null,
                                    'fund' : null,
                                    'appeal' : null,
                                    'childId' : childID,
                                    'child_sponsorship' : (thisObj.data.attrs.data_source == 'woman') ? false : true,
                                    'isWomensScholarship' : (thisObj.data.attrs.data_source == 'woman') ? true : false,
                                });

                                $.ajax({
                                    type: "POST",
                                    url: '/ci/AjaxCustom/storeCartData',
                                    data: "form=" + JSON.stringify({
                                        'total' : childRate,
                                        'items' : itemsInCart,
                                        'donateValCookieContent' : null
                                    }),
                                    processData: false,
                                    success: function() {
                                        document.location.replace("/app/payment/checkout");
                                    },
                                    dataType: 'json'
                                });
                            },
                            // Failure, child record could not be locked successfully, so let's msg user
                            function(){
                                alert(failedToAcquireLockOnChildMsg);
                                // Remove processing msg and reveal the sponsor me link
                                processingMessageObj.remove();
                                sponsorChildLinkObj.show();
                                // Close magnific popup window as convenience to user to get them back to gallery quicker
                                $.magnificPopup.close();
                            }
                        )
                    },
                    // Failure, child record is locked, so let's msg user
                    function(){
                        alert(failedToAcquireLockOnChildMsg);
                        // Remove processing msg and reveal the sponsor me link
                        processingMessageObj.remove();
                        sponsorChildLinkObj.show();
                        // Close magnific popup window as convenience to user to get them back to gallery quicker
                        $.magnificPopup.close();
                        location.reload();
                    }
                );
            },
            // Failure, child is sponsored
            function(){
                alert(childAlreadySponsoredMsg);
                // Remove processing msg and reveal the sponsor me link
                processingMessageObj.remove();
                sponsorChildLinkObj.show();
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
    isChildRecordLocked: function(childID){
        
        
        var dfd = $.Deferred();

        if(!this.data.attrs.check_for_lock){
            dfd.resolve();
            return dfd.promise();
        }

        $.ajax({
            type: "GET",
            url: '/ci/AjaxCustom/isChildRecordLocked/' + childID,
            success: function(status){
                if(status.isLocked) dfd.reject();
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
    isChildUnsponsored: function(childID){
        var dfd = $.Deferred();

        if(!this.data.attrs.check_for_lock){
            dfd.resolve();
            return dfd.promise();
        }
        
        $.ajax({
            type: "GET",
            url: '/ci/AjaxCustom/isChildSponsored/' + childID,
            success: function(status){
                if(status.isSponsored) dfd.reject();
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
    lockChildRecord: function(childID){
        var dfd = $.Deferred();

        if(!this.data.attrs.check_for_lock){
            dfd.resolve() //just say we locked it
            return dfd.promise();
        }

        $.ajax({
            type: "GET",
            url: '/ci/AjaxCustom/lockChildRecord/' + childID,
            success: function(status){
                if(status.status == "success") dfd.resolve();
                else dfd.reject();
            },
            dataType: "json" 
        });

        return dfd.promise();
    },

    /**
    * Changes the loading icon and hides/unhide the data.
    * @param {Boolean} loading Whether to add or remove the loading indicators
    */
    _setLoading: function(loading) {
        
        $(".rn_UnsponsoredChildImageGallery").magnificPopup('close');
        
        
        if (this._contentDiv && this._loadingDiv) {
            var method, toOpacity, ariaBusy;
            if (loading) {
                ariaBusy = true;
                method = "addClass";
                toOpacity = 0;

                //keep height to prevent collapsing behavior
                this._contentDiv.setStyle("height", this._contentDiv.get("offsetHeight") + "px");
            }
            else {
                ariaBusy = false;
                method = "removeClass";
                toOpacity = 1;

                //now allow expand/contract
                this._contentDiv.setStyle("height", "auto");
            }
            document.body.setAttribute("aria-busy", ariaBusy + "");
            //IE rendering: so bad it can't handle eye-candy
            if(this.Y.UA.ie){
                this._contentDiv[method]("rn_Hidden");
            }
            else{
                this._contentDiv.transition({
                    opacity: toOpacity,
                    duration: 0.4
                });
            }
            this._loadingDiv[method]("rn_Loading");
        }
    }
});