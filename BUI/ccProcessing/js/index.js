var inProgress = false;
var localConfigs = {};
var elements = {};
var workspace = {};
var wrIdentifier = null;
var paymentWindow = null;
var preFormData = {};
var paymentMethods = [];

var paymentResponsePromise = {};

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
        appName = localConfigs.parentAppName;
        appVersion = localConfigs.appVersion;

        // register workspace extension and add event handlers
        subscribeEvent(appName, subscriptionHandler);

        // register workspace extension and add event handlers
        getRegisteredWorkspaceRecord().then(function (WorkspaceRecord) {

            workspace.type = WorkspaceRecord.getWorkspaceRecordType();
            workspace.recordID = WorkspaceRecord.getWorkspaceRecordId();

            if (workspace.type != 'donation$Donation') {
                throw new Error('The CC Processing Addin is only available for Donation Workspaces');
            }

            workspace.id = appName + '_' + workspace.type + '_' + workspace.recordID;

            // add dataload listener with prefetched fields
            WorkspaceRecord.addDataLoadedListener(loadAndRefreshHandler);
            WorkspaceRecord.addExtensionLoadedListener(loadAndRefreshHandler);

            // add data saving listener for validation
            WorkspaceRecord.addRecordSavingListener(savingHandler);

            // add closing listener for validation
            WorkspaceRecord.addRecordClosingListener(closingHandler);

            WorkspaceRecord.prefetchWorkspaceFields(Object.values(localConfigs.listOfFieldsToFetch));

            resolve(true);
        });
    })
        .then(async (x) => {

            await getSessionToken();
            await loadConfigs(localConfigs.configsToLoad);
            paymentWindow = await getPaymentWindow();
        })
        .catch(handleError);
    // .finally(loaderfadeout);
}

/* -------------------------- Main Logic Functions -------------------------- */
/**
 * 
 * @param {*} eventData 
 */
function subscriptionHandler(eventData) {

    // Perform some logic on eventData.
    debuglog('>>>>received the event: ' + eventData);

    if (eventData.name) {
        switch (eventData.name) {

            case 'evt_payForm_rendered':
                if (eventData.data && eventData.data.status) {

                    // send data to popup
                    return preFormData;
                }
                break;

            case 'evt_payForm_response':
                if (eventData.data && eventData.data.status) {

                    if (paymentResponsePromise.resolve)
                        paymentResponsePromise.resolve(eventData.data);
                } else if (eventData.data && eventData.data.error) {

                    if (paymentResponsePromise.reject)
                        paymentResponsePromise.reject(eventData.data.error);
                } else {

                    if (paymentResponsePromise.reject)
                        paymentResponsePromise.reject(localConfigs.unknownError);
                }
                break;

            default:

                break;
        }
    }
    return false;
}

function loadAndRefreshHandler(parameter) {
    // Custom implementation goes here.
    debuglog('INSIDE loadAndRefreshHandler ' + JSON.parse(JSON.stringify(parameter.event)));

    if (inProgress) {
        return;
    }

    resetView();
    loaderfadein()
        .then(x => getFieldValuesFromEvent(parameter))
        .then(workspaceFields => {

            workspace.fields = workspaceFields;

            if (!workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label) {
                throw new Error('There is no contact associated with this donation, perhaps you need to save the donation?');
            }
            if (!workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label) {
                throw new Error('Please save the donation first.');
            }
        })
        .then(getSessionToken)
        .then(x => loadConfigs(localConfigs.configsToLoad))
        .then(updatePaymentMethodGrid)
        .then(updateAllowedOperations)
        .catch(error => handleError(error))
        .finally(loaderfadeout);
}

function savingHandler(params) {

}

function closingHandler(parameter) {

    return loaderfadein()
        .then(x => getFieldValuesFromEvent(parameter))
        .then(workspaceFields => {

            workspace.fields = workspaceFields;

            if (!workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label) {
                throw new Error('There is no contact associated with this donation, perhaps you need to save the donation?');
            }
        })
        // .then(x => loadConfigs(localConfigs.configsToLoad))
        .then(closingAddTransaction)
        .then(isSuccessAddTrans => {
            if (!isSuccessAddTrans) {
                parameter.getCurrentEvent().cancel();
            }
        })
        .catch(error => handleError(error))
        .finally(loaderfadeout);
}

async function updatePaymentMethodGrid() {

    // load fresh data
    paymentMethods = await getPayMethodsByContact(workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label);

    // populate data
    populatePaymentMethods(paymentMethods);
}

async function updateAllowedOperations() {

    let transStatus = await getTransactionStatusbyDonationId(workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label);
    if (!localConfigs.allowedRefundStatus.includes(transStatus)) {
        elements.btnRefund.prop('disabled', true);
    } else {
        elements.btnRefund.prop('disabled', false);
    }

    const allowChargeForTrans = localConfigs.allowedChargeStatus.includes(transStatus);
    elements.btnCharge.prop('disabled', !allowChargeForTrans);
    elements.btnAdd.prop('disabled', !allowChargeForTrans);
    if (elements.pmDataTable) {
        elements.pmDataTable.column(9).visible(allowChargeForTrans);
    }

    return Promise.resolve(true);
}

function populatePaymentMethods(paymentMethods) {

    if (elements.pmDataTable && paymentMethods.length) {

        elements.pmDataTable.clear();   // clear existing rows

        var actionButton = '<button class="action-button">' + 'Make Payment' + '</button>';
        for (var i = 0; i < paymentMethods.length; i++) {

            const payMethod = paymentMethods[i];

            elements.pmDataTable.row.add([
                i + 1,
                payMethod.lastFour,
                payMethod.expMonth,
                payMethod.expYear,
                (payMethod.pmType == 'EFT') ? payMethod.cardType + ' (' + payMethod.pmType + ')' : payMethod.cardType,
                payMethod.pnRef,
                payMethod.infoKey,
                payMethod.id,
                payMethod.created.slice(1).slice(0, -1),
                actionButton,
                payMethod.pmType
            ]);
        }
        elements.pmDataTable.draw(true);
    }

    return Promise.resolve(true);
}

async function closingAddTransaction() {

    let response = true;
    let transStatus = await getTransactionStatusbyDonationId(workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label);
    const isCheck = workspace.fields[localConfigs.listOfFieldsToFetch.DonationIsCheck].label;
    if (isCheck && !transStatus) {

        let result = await confirmation(localConfigs.closeMessage, localConfigs.closeTitle);
        switch (result) {
            case 0:
                response = false;
                break;
            case -1:
                response = true;
                break;
            case 1:
                const amount = getPaymentAmount();
                const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
                const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
                let transID = await createOrUpdateTransactionObj(donationID, 'Automated create of transaction for manually entered donation.', contactID, amount, localConfigs.transStatus.Completed);
                if (transID) {
                    response = true;
                } else {
                    showNotification('There was an error creating the transaction.  Refresh the workspace and verify that transaction with \'Completed\' status has been created');
                    response = false;
                }
                break;
            default:
                response = false;
                break;
        }
    }

    return response;
}

async function completePaymentTransaction(message, longMessage, status, PNRef, severity = Severity.ERROR) {

    const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
    const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
    if (message == longMessage)//this is where we need the pnref to go.
    {
        await createOrUpdateTransactionObj(donationID, longMessage, contactID, -1, status, null, PNRef);
    }
    else {
        await createOrUpdateTransactionObj(donationID, message + '\n\n' + longMessage, contactID, -1, status, null, PNRef);
    }

    if (severity == Severity.ERROR) {
        throw new Error(message);
    }
    else {
        showNotification(message, severity);
    }
    return true;
}

async function paymentError(message, longMessage) {

    return await completePaymentTransaction(message, longMessage, 'Error', null);
}

function resetView() {

    if (elements.pmDataTable) {
        elements.pmDataTable.clear().draw();
    }

    if (elements.btnAdd) {
        elements.btnAdd.prop('disabled', true);
    }
    if (elements.btnCharge) {
        elements.btnCharge.prop('disabled', true);
    }
    if (elements.btnRefund) {
        elements.btnRefund.prop('disabled', true);
    }
}

async function chargeClicked() {

    loaderfadein();
    if (!+workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label) {
        throw new Error('Donation not found');
    }

    return await displayMakePayment(localConfigs.makeChargeTrackingId);
}

async function addClicked() {

    loaderfadein();
    if (!+workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label) {
        throw new Error('Donation not found');
    }

    return await displayMakePayment(localConfigs.addPaymentTrackingId);
}

async function makePaymentClicked(rowData) {

    loaderfadein();

    let payMethodID = rowData[7];
    if (!payMethodID) {
        throw new Error('Invalid Payment Method ID found on the selected payment method. Please try again.')
    }

    let selectedPayMethod = paymentMethods.find(item => item.id === payMethodID);
    if (!selectedPayMethod.pnRef && !selectedPayMethod.infoKey) {
        throw new Error('Invalid PNRef/InfoKey found on the selected payment method. Please try again.')
    }

    if (!selectedPayMethod.pmType) {
        throw new Error('Invalid Payment Method Type found on the selected payment method. Please try again.')
    }

    const amount = getPaymentAmount();
    if (amount < 1) {
        // return await paymentError('Payment value less than $1');
        throw new Error('Payment value less than $1');
    }

    let message = '';
    if (selectedPayMethod.infoKey) {
        message = sprintf(localConfigs.paymentKeyConfirmMessage, (+amount).toFixed(2), selectedPayMethod.infoKey);
    } else {
        message = sprintf(localConfigs.paymentRefConfirmMessage, (+amount).toFixed(2), selectedPayMethod.pnRef);
    }
    let result = await confirmation(message, localConfigs.paymentConfirmTitle, ['Yes', 'No']);
    if (result < 0) {
        // silently exit
        return Promise.resolve(true);
    }

    let transID = await startOrCancelPaymentTransaction(amount, payMethodID);

    // get contact details
    let contact = getContactDetails();

    // complete payment
    let fsReturn = {};
    if (selectedPayMethod.pmType == 'EFT') {
        fsReturn = await processPayment(selectedPayMethod, contact, amount, transID, localConfigs.transType.FS_EFT_SALE_TYPE);
    } else {
        fsReturn = await processPayment(selectedPayMethod, contact, amount, transID, localConfigs.transType.FS_SALE_TYPE);
    }

    // complete transaction
    if (fsReturn && fsReturn.isSuccess) {

        let message = '';
        if (selectedPayMethod.infoKey) {
            message = 'Charged $' + (+amount).toFixed(2) + ' with Info Key: ' + selectedPayMethod.infoKey;
        } else {
            message = 'Charged $' + (+amount).toFixed(2) + ' with PN Ref. No. ' + selectedPayMethod.pnRef;
        }
        return await completePaymentTransaction(message, fsReturn.rawXml, localConfigs.transStatus.Completed, fsReturn.pnRef, Severity.SUCCESS);
    } else {

        let errorMsg = fsReturn.message || fsReturn.responseMsg;
        let message = 'There was a problem with the transaction.  Message: ' + fsReturn.resultCode + '::' + errorMsg + '. Check the transaction notes for further detail';
        return await completePaymentTransaction(message, fsReturn.rawXml, localConfigs.transStatus.Declined, selectedPayMethod.pnRef);
    }
}

async function refundClicked() {

    loaderfadein();
    if (!+workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label) {
        throw new Error('Donation not found');
    }

    return await displayMakeRefund();
}

function getPaymentAmount() {

    let amount = workspace.fields[localConfigs.listOfFieldsToFetch.DonationAmount].label;
    if (!amount || (+amount) <= 0) {
        showNotification('Total amount of donation not present.  Try saving before processing the payment', Severity.WARNING);
        amount = -1;
    }
    return +amount;
}

async function displayMakePayment(trackingID = localConfigs.makeChargeTrackingId) {

    let amount = -1;
    if (trackingID == localConfigs.makeChargeTrackingId) {
        amount = getPaymentAmount();
        if (amount < 1) {
            // return await paymentError('Payment value less than $1');
            throw new Error('Payment value less than $1');
        }
    } else {
        amount = 1.00;
    }

    let data = {
        trackingID: trackingID,
        // transID: transID,
        amount: amount
    };

    let transID = 0;
    let paymentResponse = {};
    try {

        // open payment popup and collect payment data
        await Promise.all[openPaymentForm(), preparePayFormData(data)];
        paymentResponse = await waitFor(paymentResponsePromise);

        // close popup
        let paymentWindow = await getPaymentWindow();
        paymentWindow.close();
    } catch (error) {

        // close popup
        let paymentWindow = await getPaymentWindow();
        paymentWindow.close();

        // rethrow for down the road error handling
        if (error == 'Error: Cancel')
            throw new Warning('Activity cancelled by User');
        else
            throw error;
    }

    if (!paymentResponse.pmDetails) {
        throw new Error('Invalid payment method details');
    }
    if (!paymentResponse.contact) {
        throw new Error('Invalid contact details');
    }

    transID = await startOrCancelPaymentTransaction(amount);

    // get contact details
    let contact = getContactDetails(paymentResponse.contact.firstName, paymentResponse.contact.lastName, paymentResponse.contact.street, paymentResponse.contact.zip);

    // complete payment
    let fsReturn = {};
    // * first sale with payment details, use 'Sale' for both
    // if (paymentResponse.pmDetails.pmType == 'EFT') {
    //     fsReturn = await processPayment(paymentResponse.pmDetails, contact, amount, transID, localConfigs.transType.FS_EFT_SALE_TYPE);
    // } else {
    fsReturn = await processPayment(paymentResponse.pmDetails, contact, amount, transID, localConfigs.transType.FS_SALE_TYPE);
    // }

    // check status
    if (!fsReturn || !fsReturn.isSuccess) {

        let errorMsg = fsReturn.message || fsReturn.responseMsg;
        const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
        let message = 'There was an issue adding this payment, check the transaction for further detail. ERROR: ' + fsReturn.resultCode + '::' + errorMsg;
        await createOrUpdateTransactionObj(donationID, message);
        throw new Error(message);
    }

    // get pnRef
    if (fsReturn.pnRef) {
        paymentResponse.pmDetails.pnRef = fsReturn.pnRef;
    }

    // get infokey
    // ! guess this is not mandatory
    // let infoKey = await getInfoKey(paymentResponse.pmDetails, contact);
    // if (infoKey) {
    //     paymentResponse.pmDetails.infoKey = infoKey;
    // }

    // create payment method record
    const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
    let payMethodID = await createOrUpdatePaymentMethod(paymentResponse.pmDetails, contactID);
    updatePaymentMethodGrid();  // no need to wait on this, can update in background

    const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
    await createOrUpdateTransactionObj(donationID, 'Added payment method: ' + payMethodID.toString() + ' to transaction', contactID, 0, null, payMethodID, paymentResponse.pmDetails.pnRef);

    if (payMethodID < 1) {
        showNotification('The transaction was successful, but unable to store payment information for recurring use.', Severity.WARNING);
    }

    // * add paymentMethod to Pledge
    await createOrUpdatePledgeObj(donationID, payMethodID);

    // if only adding payment, refund transaction
    if (trackingID == localConfigs.addPaymentTrackingId) {

        // * reverse the payment
        let reversed = await initiateChargeReversal(paymentResponse.pmDetails);
        if (reversed.success) {
            showNotification('Successfully added new payment method', Severity.SUCCESS);
        } else {
            throw new Error('There may have been an issue adding this payment method. Check the transaction for details and verify no charge has occured with merchant. ERROR: ' + reversed.error);
        }
    } else {

        return await completePaymentTransaction('Payment Completed', fsReturn.rawXml, localConfigs.transStatus.Completed, fsReturn.pnRef, Severity.SUCCESS);
    }

    return Promise.resolve(true);
}

async function getPaymentWindow() {

    return await getModalWindow('paymentForm', localConfigs.popupWidth, localConfigs.popupHeight);
}

async function openPaymentForm(title) {

    paymentWindow = await getPaymentWindow();
    paymentWindow.setTitle(title);
    paymentWindow.setContentUrl(localConfigs.paymentPage + '?source=' + workspace.id);
    paymentWindow.render();
}

function preparePayFormData(data = {}) {

    preFormData = {}; // reset
    Object.assign(preFormData, data);

    // set contact details
    preFormData.contact = {
        firstName: workspace.fields[localConfigs.listOfFieldsToFetch.ContactFirstName].label,
        lastName: workspace.fields[localConfigs.listOfFieldsToFetch.ContactLastName].label,
        email: workspace.fields[localConfigs.listOfFieldsToFetch.ContactEmail].label,
        street: workspace.fields[localConfigs.listOfFieldsToFetch.ContactStreet].label,
        city: workspace.fields[localConfigs.listOfFieldsToFetch.ContactCity].label,
        state: workspace.fields[localConfigs.listOfFieldsToFetch.ContactState].value,
        postalCode: workspace.fields[localConfigs.listOfFieldsToFetch.ContactPostalCode].label,
        country: workspace.fields[localConfigs.listOfFieldsToFetch.ContactCountry].value
    }
}

function getContactDetails(fName = null, lName = null, street = null, zip = null) {

    let contact = {};
    if (fName !== null) {
        contact.firstName = fName;
    } else {
        contact.firstName = workspace.fields[localConfigs.listOfFieldsToFetch.ContactFirstName].label;
    }

    if (lName !== null) {
        contact.lastName = lName;
    } else {
        contact.lastName = workspace.fields[localConfigs.listOfFieldsToFetch.ContactLastName].label;
    }

    if (street !== null) {
        contact.street = street;
    } else {
        contact.street = workspace.fields[localConfigs.listOfFieldsToFetch.ContactStreet].label;
    }

    if (zip !== null) {
        contact.postalCode = zip;
    } else {
        contact.postalCode = workspace.fields[localConfigs.listOfFieldsToFetch.ContactPostalCode].label;
    }

    return contact;
}


async function displayMakeRefund() {

    let transID = await initiateRefundOnTransaction();
    if (!transID) {
        return Promise.resolve(true);
    }

    const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
    const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
    let details = await getLastTransactionDetails(donationID, contactID);
    if (!details) {
        return await paymentError('Unable to access transaction information');
    }
    const pmType = details.paymentMethod ? details.paymentMethod.pmType : null;

    // get contact details
    let contact = getContactDetails();

    // use this pnRef here
    details.paymentMethod.pnRef = details.transaction.refCode;

    // complete payment
    let fsReturn = await processPayment(details.paymentMethod, contact, details.transaction.totalCharge, details.transaction.id, localConfigs.transType.FS_REFUND_TYPE);

    // complete transaction
    if (fsReturn && fsReturn.isSuccess) {
        let message = 'Refunded $' + details.transaction.totalCharge + ' to payment method with PR Ref. No. ' + details.transaction.refCode;
        return await completePaymentTransaction(message, fsReturn.rawXml, localConfigs.transStatus.Refunded, null, Severity.SUCCESS);
    }
    else {
        let errorMsg = fsReturn.message || fsReturn.responseMsg;
        return await paymentError('There was a problem with the transaction.  Message: ' + fsReturn.resultCode + '::' + errorMsg + '. Check the transaction notes for further detail', fsReturn.rawXml);
    }
}

async function startOrCancelPaymentTransaction(amount, paymentMethodId = null) {

    const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
    if (!+donationID) {
        throw new Error('Donation not found');
    }

    let transStatus = await getTransactionStatusbyDonationId(donationID);
    if (!localConfigs.allowedChargeStatus.includes(transStatus)) {
        let message = 'Unable to add payment to donation with ' + transStatus + ' transaction status';
        return await completePaymentTransaction(message, message, transStatus, null);
    }

    const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
    // create transaction object
    let transID = await createOrUpdateTransactionObj(donationID, 'Request to initiate payment started', contactID, amount, 'Pending - Agent Initiated', paymentMethodId);
    if (!transID) {
        throw new Error('Unable to create transaction');
    }

    return transID;
}

async function initiateChargeReversal(paymentMethod) {

    let response = { success: false };
    const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
    if (!+donationID) {
        response.error = 'Donation not found';
        return response;
    }

    let transID = await initiateReversalOnTransaction();
    if (!transID) {
        response.error = 'Transaction could not be reversed';
        return response;
    }

    const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
    let details = await getLastTransactionDetails(donationID, contactID);
    if (!details) {
        // return await paymentError('Unable to access transaction information');
        // throw new Error('Unable to access transaction information');
        response.error = 'Unable to access transaction information';
        return response;
    }

    // use this pnRef here
    paymentMethod.pnRef = details.transaction.refCode;

    // complete payment
    let fsReturn = {};
    if (paymentMethod.pmType == 'EFT') {
        fsReturn = await processPayment(paymentMethod, null, null, null, localConfigs.transType.FS_EFT_REVERSAL_TYPE);
    } else {
        fsReturn = await processPayment(paymentMethod, null, null, null, localConfigs.transType.FS_REVERSAL_TYPE);
    }

    if (fsReturn && fsReturn.isSuccess) {
        let message = 'Reversed Charge with PN Ref. No. ' + details.transaction.refCode;
        await createOrUpdateTransactionObj(donationID, message + '\n\n' + fsReturn.rawXml, contactID, -1, localConfigs.transStatus.Reversed, null, details.transaction.refCode, true);
        response.success = true;
        return response;
    }
    else {
        let errorMsg = fsReturn.message || fsReturn.responseMsg;
        return await paymentError('There was a problem with the transaction.  Message: ' + fsReturn.resultCode + '::' + errorMsg + '. Check the transaction notes for further detail', fsReturn.rawXml);
    }
}

async function initiateReversalOnTransaction() {

    const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
    let transStatus = await getTransactionStatusbyDonationId(donationID);

    if (!localConfigs.allowedReversalStatus.includes(transStatus)) {
        const message = 'Unable to reverse transaction with ' + transStatus + ' transaction status';
        return await completePaymentTransaction(message, message, transStatus, null);
    }

    const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
    //start the transaction
    let transID = await createOrUpdateTransactionObj(donationID, 'Request to initiate reversal started', contactID, -1, 'Processing');
    if (!transID) {
        throw new Error('Unable to access transaction');
    }

    return transID;
}

async function initiateRefundOnTransaction() {

    const donationID = workspace.fields[localConfigs.listOfFieldsToFetch.DonationID].label;
    let transStatus = await getTransactionStatusbyDonationId(donationID);

    if (!localConfigs.allowedRefundStatus.includes(transStatus)) {
        const message = 'Unable to refund transaction with ' + transStatus + ' transaction status';
        return await completePaymentTransaction(message, message, transStatus, null);
    }

    const contactID = workspace.fields[localConfigs.listOfFieldsToFetch.ContactID].label;
    //start the transaction
    let transID = await createOrUpdateTransactionObj(donationID, 'Request to initiate refund started', contactID, -1, 'Pending - Agent Initiated');
    if (!transID) {
        throw new Error('Unable to access transaction');
    }

    return transID;
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
    // elements.progress && elements.progress.val(0);
    // showProgress(20, Speed.SLOW);
    return Promise.resolve(true);
}

/**
 * 
 */
function loaderfadeout() {

    inProgress = false;
    $('.extension-loading').hide();
    // showProgress(elements.progress.prop('max'), Speed.INSTANT);
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
        }, 20000);
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

    /* ------------------------------ init elements ----------------------------- */
    elements.btnAdd = $('#add-button');
    elements.btnRefund = $('#refund-button');
    elements.btnCharge = $('#charge-button');
    // elements.treeContainer = $('#kbuploader-tree-container');
    // elements.treeDiv = $('#kbuploader-tree');
    // elements.imagePreview = $('#kbuploader-preview-image');
    // elements.treeContainer = $('#kbuploader-info-container');
    // elements.infoLabel = $('#kbuploader-info-label');
    // elements.progress = $('#kbuploader-progress');
    // elements.folderInput = $('#kbuploader-folder-input');
    // elements.fileInput = $('#kbuploader-file-input');
    elements.notificationBar = $('#notification-bar');
    elements.notificationBarMsg = $('#notification-bar-message');

    // initialize datatable
    elements.pmDataTable = $('#pay-method-table').DataTable({
        "processing": true,
        "columns": [
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        ],
        "columnDefs": [
            {
                "targets": [0, 9],
                "searchable": false
            },
            {
                "targets": [10],
                "visible": false,
                "searchable": false
            }
        ],
        "createdRow": function (row, data, dataIndex) {
            $(row).attr('id', 'row_' + dataIndex);
        },
        "order": [[8, 'desc']]
    });

    /* -------------------------------- listeners ------------------------------- */
    // * table listener
    $('#pay-method-table tbody').on('click', 'tr', function () {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        }
        else {
            elements.pmDataTable.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });

    // handler for row action button
    $('#pay-method-table tbody').on('click', 'tr .action-button', function () {

        event.preventDefault();

        var data = elements.pmDataTable.row($(this).parents('tr')).data();
        debuglog(data);

        makePaymentClicked(data)
            .catch(handleError)
            .finally(async x => {
                await updateAllowedOperations();
                loaderfadeout();
            });
    });

    // * button listeners
    elements.btnAdd.on('click', function () {

        addClicked()
            .catch(handleError)
            .finally(async x => {
                await updateAllowedOperations();
                loaderfadeout();
            });
    });
    elements.btnRefund.on('click', function () {

        refundClicked()
            .catch(handleError)
            .finally(async x => {
                await updateAllowedOperations();
                loaderfadeout();
            });
    });
    elements.btnCharge.on('click', function () {

        chargeClicked()
            .catch(handleError)
            .finally(async x => {
                await updateAllowedOperations();
                loaderfadeout();
            });
    });

    // * notification bar 'close' button listener
    $('#notification-bar').on('click', '#notification-bar-close-btn', function (event) {

        debuglog('--close notify--' + this);

        event.preventDefault();
        hideNotification();
    });
});

// Let's start
initialize();
