var inProgress = false;
var localConfigs = {};
var elements = {};
var dialog = {};
var preFormData = {};
var cardType = '';

// var formLoadedPromise = {};

// var paymentResponsePromise = {};

/**
 * 
 * @returns {undefined}
 */
function initialize() {
    DEVMODE = isDevMode();
    debuglog('initializing');

    new Promise(async (resolve, reject) => {

        loaderfadein(false);

        localConfigs = await readLabelConstants();
        if (!localConfigs) {
            throw new Error('Failed to initialize local configurations for the extension. Please contact your administrator.');
        }

        // set vars for global utilities usage
        appName = localConfigs.paymentFormAppName;
        appVersion = localConfigs.appVersion;

        // register workspace extension and add event handlers
        // subscribeEvent(appName, subscriptionHandler);
    })
        // .then(async (x) => {

        //     await loadConfigs(localConfigs.configsToLoad);
        // })
        .catch(handleError)
        .finally(loaderfadeout);
}

/* -------------------------- Main Logic Functions -------------------------- */
async function loadForm() {

    loaderfadein();
    return await getPreFormData(populateForm);
}

function getPreFormData(callback) {

    let evtObj = createEventObj('evt_payForm_rendered', dialog.id, dialog.parent);
    evtObj.data = {
        status: true
    };
    return fireEvent(localConfigs.parentAppName, evtObj, callback);
}

function populateForm(response) {

    if (response && response.result && response.result[0]) {

        let formData = response.result[0];
        if (formData.contact) {
            if (formData.contact.firstName && elements.iFName) {
                elements.iFName.val(formData.contact.firstName);
            }
            if (formData.contact.lastName && elements.iLName) {
                elements.iLName.val(formData.contact.lastName);
            }
            if (formData.contact.email && elements.iEmail) {
                elements.iEmail.val(formData.contact.email);
            }
            if (formData.contact.street && elements.iStreet) {
                elements.iStreet.val(formData.contact.street);
            }
            if (formData.contact.city && elements.iCity) {
                elements.iCity.val(formData.contact.city);
            }
            if (formData.contact.state && elements.iState) {
                elements.iState.val(formData.contact.state);
            }
            if (formData.contact.postalCode && elements.iZipCode) {
                elements.iZipCode.val(formData.contact.postalCode);
            }
            if (formData.contact.country && elements.iCountry) {
                elements.iCountry.val(formData.contact.country);
            }

            // Trigger the "change" event manually, jquery trigger does not work with card library
            // thsi will refill the values for all fields
            var evt = document.createEvent('HTMLEvents');
            evt.initEvent('keyup', false, true);
            document.getElementById('first-name').dispatchEvent(evt);
        }
    }

    return Promise.resolve(true);
}

async function cancelClicked() {

    loaderfadein();
    let evtObj = createEventObj('evt_payForm_response', dialog.id, dialog.parent);
    evtObj.data = {
        error: (new Error('Cancel')).toString()
    };
    return await fireEvent(localConfigs.parentAppName, evtObj);
}

async function submitClicked() {

    loaderfadein();

    let form = $('form')[0];
    if (form.checkValidity()) {

        let formResponse = {};
        formResponse.pmDetails = {};
        formResponse.pmDetails.pmType = elements.tabs.filter(':checked').val();

        if (formResponse.pmDetails.pmType == 'EFT') {

            formResponse.pmDetails.cardType = elements.iEFTType.val();
            formResponse.pmDetails.routingNum = elements.iRouting.val();
            formResponse.pmDetails.acctNum = elements.iAccount.val();
            formResponse.pmDetails.lastFour = elements.iAccount.val().slice(elements.iAccount.val().length - 5);
        } else {

            formResponse.pmDetails.ccNum = elements.iCCNum.val();
            formResponse.pmDetails.lastFour = elements.iCCNum.val().slice(elements.iCCNum.val().length - 5);
            let exp = $.map(elements.iExpiry.val().split('/'), $.trim);
            formResponse.pmDetails.expMonth = exp[0];
            formResponse.pmDetails.expYear = exp[1];
            formResponse.pmDetails.cvc = elements.iCVC.val();

            // get card type
            formResponse.pmDetails.cardType = cardType;
        }

        formResponse.contact = {};
        formResponse.contact.firstName = elements.iFName.val();
        formResponse.contact.lastName = elements.iLName.val();
        formResponse.contact.street = elements.iStreet.val();
        formResponse.contact.city = elements.iCity.val();
        formResponse.contact.state = elements.iState.val();
        formResponse.contact.country = elements.iCountry.val();
        formResponse.contact.zip = elements.iZipCode.val();

        let evtObj = createEventObj('evt_payForm_response', dialog.id, dialog.parent);
        formResponse.status = true;
        evtObj.data = formResponse;

        return await fireEvent(localConfigs.parentAppName, evtObj);
    } else {

        throw new Warning('Please fix form errors');
    }
}

function toggleRequiredByPMType(pmType) {

    if (pmType == 'EFT') {

        elements.iCCNum.prop('required', false);
        elements.iExpiry.prop('required', false);
        elements.iCVC.prop('required', false);
        elements.iEFTType.prop('required', true);
        elements.iAccount.prop('required', true);
        elements.iRouting.prop('required', true);
    } else {

        elements.iCCNum.prop('required', true);
        elements.iExpiry.prop('required', true);
        elements.iCVC.prop('required', true);
        elements.iEFTType.prop('required', false);
        elements.iAccount.prop('required', false);
        elements.iRouting.prop('required', false);
    }
}

/**
 * 
 * @param {*} eventData 
 */
function subscriptionHandler(eventData) {

    // Perform some logic on eventData.
    debuglog('>>>>received the event: ' + eventData);

    if (eventData.name) {
        switch (eventData.name) {

            default:

                break;
        }
    }
    return false;
}

/* ----------------------------- Helper Methods ----------------------------- */
/**
 * 
 */
function loaderfadein(loadCall = true) {

    if (loadCall) {
        inProgress = true;
    }
    hideNotification();
    $('.extension-loading').show();
    return Promise.resolve(true);
}

/**
 * 
 */
function loaderfadeout() {

    inProgress = false;
    $('.extension-loading').hide();
    return Promise.resolve(true);
}

function confirmation(question, title = 'Confirmation', buttons = ['Yes', 'No', 'Cancel']) {

    return new Promise((resolve, reject) => {

        let dialogButtons = {};
        if (buttons.includes('Yes')) {
            dialogButtons.Yes = function () {
                resolve(1);
                $(this).dialog("close");
            };
        }
        if (buttons.includes('No')) {
            dialogButtons.No = function () {
                resolve(-1);
                $(this).dialog("close");
            };
        }
        if (buttons.includes('Cancel')) {
            dialogButtons.Cancel = function () {
                resolve(0);
                $(this).dialog("close");
            };
        }

        $('<div></div>')
            .html(question)
            .dialog({
                autoOpen: true,
                dialogClass: "no-close",
                modal: true,
                title: 'Confirmation',
                buttons: dialogButtons,
                close: function () {
                    $(this).remove(); //removes this dialog div from DOM
                }
            });
    });
}
/* ----------------------------- Error Handlers ----------------------------- */
/**
 * 
 * @param {type} error
 * @returns {Promise}
 */
function handleError(error) {
    debuglog('--FINAL CATCH');

    if (error && error.type)
        return showNotification(error.message, error.type);
    else
        return showNotification(error, Severity.ERROR);
}

/**
 * 
 * @param {*} msg 
 * @param {*} type 
 */
function showNotification(msg, type) {
    var msgClass = '';
    switch (type) {
        case Severity.ERROR:
            msgClass = 'notify-error';
            break;
        case Severity.WARNING:
            msgClass = 'notify-warning';
            break;
        case Severity.SUCCESS:
            msgClass = 'notify-success';
            break;
        default:
            msgClass = 'notify-info';
            break;
    }
    if (elements.notificationBarMsg) {
        elements.notificationBarMsg.html(msg);
    }
    if (elements.notificationBar) {
        elements.notificationBar.removeClass().addClass(msgClass).slideDown();
        setTimeout(() => {
            hideNotification();
        }, 5000);
    }
}

/**
 * 
 */
function hideNotification() {
    if (elements.notificationBar)
        elements.notificationBar.hide().removeClass();
    if (elements.notificationBarMsg)
        elements.notificationBarMsg.html(null);
}

$(document).ready(function () {

    dialog.parent = getURLParameter('source');
    dialog.id = dialog.parent + '_dialog';

    /* ------------------------------ init elements ----------------------------- */
    elements.iFName = $('#first-name');
    elements.iLName = $('#last-name');
    elements.iCCNum = $('#ccnumber');
    elements.iExpiry = $('#expiry');
    elements.iCVC = $('#cvc');
    elements.iEFTType = $('#eft-type');
    elements.iRouting = $('#routing');
    elements.iAccount = $('#account');

    elements.iStreet = $('#streetaddress');
    elements.iCity = $('#city');
    elements.iState = $('#state');
    elements.iCountry = $('#country');
    elements.iZipCode = $('#zipcode');
    elements.iEmail = $('#email');

    elements.btnCancel = $('#button-cancel');
    elements.btnSubmit = $('#button-submit');

    elements.tabs = $('input[name=tab-control]');

    elements.notificationBar = $('#notification-bar');
    elements.notificationBarMsg = $('#notification-bar-message');

    /* ------------------------------ load card ui ------------------------------ */
    $('form').card({
        container: '.card-wrapper',
        width: 280,

        formSelectors: {
            nameInput: 'input[name="first-name"], input[name="last-name"]',
            numberInput: 'input#ccnumber'
        }
    });

    /* Notify the parent extension that the popup is ready to 
     * accept the data */
    loadForm()
        .catch(handleError)
        .finally(loaderfadeout);

    /* -------------------------------- listeners ------------------------------- */
    elements.btnCancel.on('click', function () {

        cancelClicked()
            .catch(handleError)
            .finally(loaderfadeout);
    });
    elements.btnSubmit.on('click', function () {

        submitClicked()
            .catch(handleError)
            .finally(loaderfadeout);

        // don't submit the form
        return false;
    });

    // * tab change listener
    $('input[type=radio][name=tab-control]').on('change', function () {

        toggleRequiredByPMType($(this).val());
    });

    // * notification bar 'close' button listener
    $('#notification-bar').on('click', '#notification-bar-close-btn', function (event) {

        debuglog('--close notify--' + this);

        event.preventDefault();
        hideNotification();
    });

    // * card type listener
    document.getElementsByName('ccnumber')[0].addEventListener('payment.cardType', function (event) {

        if (event.detail) {
            cardType = event.detail;
        }
    });

    // * set default required fields
    elements.iFName.prop('required', true);
    elements.iLName.prop('required', true);
    // elements.iState.prop('required', true);
    // elements.iCountry.prop('required', true);
    toggleRequiredByPMType(elements.tabs.filter(':checked').val());
});

// Let's start
initialize();
