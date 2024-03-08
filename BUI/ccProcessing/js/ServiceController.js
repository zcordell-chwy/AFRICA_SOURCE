/* -------------------------------------------------------------------------- */
/*                           API Request Generators                           */
/* -------------------------------------------------------------------------- */
async function readLabelConstants(file = 'localConfigs.json', path = 'js/data/') {

    if (DEVMODE) {
        return {
            "parentAppName": "ccProcessing",
            "paymentFormAppName": "paymentForm",
            "appVersion": "1.0",
            "listOfFieldsToFetch": {
                "ContactID": "Contact.CId",
                "ContactEmail": "Contact.Email.Addr",
                "ContactFirstName": "Contact.Name.First",
                "ContactLastName": "Contact.Name.Last",
                "ContactPhHome": "Contact.ph_home",
                "ContactPhOffice": "Contact.ph_office",
                "ContactPhMobile": "Contact.ph_mobile",
                "ContactPhAsst": "Contact.ph_asst",
                "ContactPhFax": "Contact.ph_fax",
                "ContactLogin": "Contact.Login",
                "ContactCountry": "Contact.Addr.CountryId",
                "ContactState": "Contact.Addr.ProvId",
                "ContactPostalCode": "Contact.Addr.PostalCode",
                "ContactCity": "Contact.Addr.City",
                "ContactStreet": "Contact.Addr.Street",
                "ContactTypeID": "Contact.ctype_id",
                "DonationID": "donation$Donation.ID",
                "DonationIsCheck": "donation$Donation.isCheck",
                "DonationAmount": "donation$Donation.Amount"
            },
            "configsToLoad": [
                "CUSTOM_CFG_ACTIVE_PAY_METHOD_REPORT_ID",
                "CUSTOM_CFG_PROV_BY_COUNTRY_REPORT_ID"
            ],
            "unknownError": "An unknown error occurred. Please contact system administrator.",
            "allowedRefundStatus": [
                "Completed"
            ],
            "allowedChargeStatus": [
                "",
                "Declined"
            ],
            "allowedReversalStatus": [
                "Pending - Agent Initiated"
            ],
            "transType": {
                "FS_SALE_TYPE": "Sale",
                "FS_EFT_SALE_TYPE": "RepeatSale",
                "FS_REFUND_TYPE": "Return",
                "FS_REVERSAL_TYPE": "Reversal",
                "FS_EFT_REVERSAL_TYPE": "Void",
                "FS_AUTH_TYPE": "Auth"
            },
            "transStatus": {
                "Completed": "Completed",
                "Declined": "Declined",
                "Refunded": "Refunded",
                "Reversed": "Reversed"
            },
            "addPaymentTrackingId": "addPayment",
            "makeChargeTrackingId": "makeCharge",
            "paymentPage": "ccProcessing.html",
            "popupWidth": "650",
            "popupHeight": "500",
            "closeTitle": "Create Transaction?",
            "closeMessage": "No completed transaction is associated with this donation.  If this donation is complete, it will not be reported properly.  Would you like to add a completed transaction to this donation before closing the workspace?",
            "paymentConfirmTitle": "Make Payment?",
            "paymentRefConfirmMessage": "Charge $%s to payment method with PN Ref No. %s?",
            "paymentKeyConfirmMessage": "Charge $%s to payment method with Info Key No. %s?"
        };
    }

    return await getJSON(path + file);
}

/**
 * 
 * @param {*} configsToLoad 
 * @returns 
 */
function loadConfigSettings(configsToLoad) {

    debuglog(">>loadConfigSettings");
    return getSessionToken()
        .then(x => getConfigurationsByKey(configsToLoad))
        .then(configValueResponse => {

            let configs = {};
            if (configValueResponse && configValueResponse.items) {
                configs = configValueResponse["items"][0].rows.reduce((accumulator, currentValue) => {
                    accumulator[currentValue[1]] = currentValue[2];
                    return accumulator;
                }, configs);
            }

            return Promise.resolve(configs);
        });
}

/**
 * 
 * @param {*} configKeyArr 
 * @returns 
 */
function getConfigurationsByKey(configKeyArr) {

    let query = 'select id, name, value from configurations where lookupName IN (\'%s\')';
    return getOSCQueryResultsByKeys(query, configKeyArr);
}

/**
 * Gets query results from DB making sure that query string doesn't gets too long
 * Splits the keys into configurable chunks to get the result simultaneously and 
 * joins them before returning
 * @param {type} query
 * @param {type} keyArr
 * @param {type} context
 * @returns {Promise}
 */
function getOSCQueryResultsByKeys(query, keyArr) {

    if (keyArr && keyArr.length) {

        let queryResultPromiseArr = [];
        /** 
         * Divide all keys into chunks of n keys
         * This number should be big enough so calls are minimum
         * and small enough so header size limit is not crossed and we don't get an error
         * If a msgbase/config call fails due to limit, reduce this number and try again
         * NOTE: did not create a config for this value since we need this before we fetch the configs
         */
        let chunkSize = 45;

        let arrCopy = keyArr.slice();
        while (arrCopy.length) {

            let keysChunk = arrCopy.splice(0, chunkSize);
            let chunkQuery = sprintf(query, keysChunk.join("','"));
            queryResultPromiseArr.push(getOSVCQueryResults(chunkQuery));
        }
        if (queryResultPromiseArr.length) {

            return Promise.all(queryResultPromiseArr)
                .then(respObjArr => {

                    let primaryIndex = 0;   // the set that we want to return

                    // if we have more than one result set
                    if (respObjArr && respObjArr.length > 1) {

                        /** 
                         * let's collect all responses into the first one
                         */
                        for (let i = 0; i < respObjArr.length; i++) {

                            // skip the primary one because we don't want to duplicate the results from primary set
                            if (respObjArr[i] && respObjArr[i].items && i != primaryIndex) {

                                // add the count
                                respObjArr[primaryIndex].items[0].count += +respObjArr[i].items[0].count;

                                // combine the data
                                respObjArr[primaryIndex].items[0].rows = respObjArr[primaryIndex].items[0].rows.concat(respObjArr[i].items[0].rows);
                            }
                        }
                    }

                    if (respObjArr && respObjArr[0])
                        return Promise.resolve(respObjArr[0]);
                    else {

                        throw new Error('Something went wrong while getting query results');
                    }
                });
        } else {

            throw new Error('Something went wrong while getting query results');

        }
    } else {

        throw new Error('Something went wrong while getting query results');

    }
}

/**
 * 
 * @returns 
 */
async function getCountryList() {

    let response = await getCountries();
    let countryList = {};

    if (response && response.items) {
        response.items.forEach(element => {
            countryList[element.id] = [element.lookupName, element.name];
        });
    }

    return countryList;
}

async function getProvinceList(newCountry) {

    if (!newCountry) {
        return {};
    }
    if (!+customConfigs.CUSTOM_CFG_PROV_BY_COUNTRY_REPORT_ID) {
        showNotification('Provinces could not be fetched. Report not found', Severity.ERROR);
        return {};
    }

    let filters = [
        {
            name: 'country',
            operator: {
                lookupName: '='
            },
            values: [newCountry]
        }
    ];

    let provinceList = {};
    let results = await getReportResults(+customConfigs.CUSTOM_CFG_PROV_BY_COUNTRY_REPORT_ID, filters);

    if (results && results.count) {

        for (var i = 0; i < results.rows.length; i++) {

            const row = results.rows[i];
            provinceList[row[0]] = row[1];
        }
    }

    return provinceList;
}

/* -------------------------------------------------------------------------- */
/*                         OSC payment related methods                        */
/* -------------------------------------------------------------------------- */
async function getPayMethodsByContact(contactID = null) {

    if (!contactID) {
        throw new Error('Contact ID not found');
    }
    if (!+customConfigs.CUSTOM_CFG_ACTIVE_PAY_METHOD_REPORT_ID) {
        throw new Error('Payment Methods could not be fetched. Report not found');
    }

    let filters = [
        {
            name: 'contact',
            operator: {
                lookupName: '='
            },
            values: [contactID.toString()]
        }
    ];

    let paymentMethods = [];
    let results = await getReportResults(+customConfigs.CUSTOM_CFG_ACTIVE_PAY_METHOD_REPORT_ID, filters);

    const currYear = new Date().getFullYear();
    const currMonth = new Date().getMonth();
    if (results && results.count) {

        for (var i = 0; i < results.rows.length; i++) {

            const row = results.rows[i];
            const expYear = +row[3];
            const expMonth = +row[2];
            const pmType = row[5];

            if (((expYear > currYear) || (expYear === currYear && expMonth > currMonth)) || pmType == 'EFT') {

                let payMethod = {
                    "cardType": row[1],
                    "id": row[0],
                    "expMonth": expMonth,
                    "expYear": expYear,
                    "lastFour": row[4],
                    "pnRef": row[6],
                    "pmType": pmType,
                    "infoKey": row[7],
                    "created": row[8]
                }

                paymentMethods.push(payMethod);
            }
        }
    }

    return paymentMethods;
}

async function getTransactionStatusbyDonationId(donationID) {

    let trans = await getTransactionByDonationId(donationID);
    if (!trans) {
        return '';
    }
    if (trans.currentStatus) {
        return trans.currentStatus;
    }
    return '';
}

async function getTransactionByDonationId(donationID) {

    let transaction = false;
    let query = 'SELECT id, createdTime, description, currentStatus.lookupName AS currentStatus, paymentMethod, refCode, totalCharge FROM financial.transactions WHERE donation = ' + donationID;
    let results = await getOSVCQueryResults(query);

    if (results && results.items && results.items[0].count) {
        transaction = {};
        for (var i = 0; i < results.items[0].columnNames.length; i++) {
            transaction[results.items[0].columnNames[i]] = results.items[0].rows[0][i];
        }
    }

    return transaction;
}

async function getLastTransactionDetails(donationID, contactID) {

    let trans = await getTransactionByDonationId(donationID);
    let paymentMethods = await getPayMethodsByContact(contactID);
    if (!paymentMethods.length) {
        return false;
    }

    let transPayMethod = paymentMethods.find(pm => pm.id === trans.paymentMethod);
    if (!transPayMethod) {
        return false;
    }

    return { 'transaction': trans, 'paymentMethod': transPayMethod };
}

async function getPledgeIDByDonation(donationID) {

    let pledge = false;
    let query = 'SELECT PledgeRef FROM donation.donationToPledge WHERE DonationRef.ID = ' + donationID + ' ORDER BY ID DESC LIMIT 1';
    let results = await getOSVCQueryResults(query);

    if (results && results.items && results.items[0].count) {
        pledge = results.items[0].rows[0][0];
    }

    return pledge;
}

async function createOrUpdatePaymentMethod(paymentMethod, contactID, suppress = false) {

    try {
        let isCreate = true;
        if (contactID < 1) {
            return false;
        }

        let payMethod = {};
        let recID = 0;
        // figure out if we're create or update
        if (paymentMethod.id) {
            recID = paymentMethod.id;
            isCreate = false;
        }

        if (paymentMethod.pmType) {
            payMethod.PaymentMethodType = {
                lookupName: paymentMethod.pmType
            };
        }

        if (paymentMethod.pmType == 'EFT') {

            if (paymentMethod.cardType) {
                payMethod.EFT_Type = {
                    lookupName: paymentMethod.cardType
                };
            }
        } else {
            if (paymentMethod.cardType) {
                payMethod.CardType = paymentMethod.cardType;
            }
        }

        if (contactID) {
            payMethod.Contact = {
                id: contactID
            };
        }
        if (paymentMethod.lastFour) {
            payMethod.lastFour = paymentMethod.lastFour;
        }
        if (paymentMethod.expMonth) {
            payMethod.expMonth = paymentMethod.expMonth;
        }
        if (paymentMethod.expYear) {
            payMethod.expYear = paymentMethod.expYear;
        }
        if (paymentMethod.routingNum) {
            payMethod.EFTroutingNumber = paymentMethod.routingNum;
        }
        if (paymentMethod.infoKey) {
            payMethod.InfoKey = paymentMethod.infoKey;
        }

        if (paymentMethod.pnRef && paymentMethod.pnRef.length > 0) {
            payMethod.PN_Ref = paymentMethod.pnRef;
        }

        let returnVal = await createOrUpdateOSVCObject('financial.paymentMethod', payMethod, recID, suppress);

        if (isCreate) {
            return returnVal.id;
        } else {
            return recID;
        }
    } catch (e) {
        showNotification(e.message, Severity.WARNING);
        return false;
    }
}

async function createOrUpdateTransactionObj(donationID, newNote, contactID, amount = 0, status = null, payMethod = null, pnRef = null, suppress = false, description=null) {

    let isCreate = true;
    if (donationID < 1) {
        return false;
    }

    // figure out if we're create or update
    let transaction = await getTransactionByDonationId(donationID);
    let trans = {};
    let recID = 0;
    if (transaction) {
        isCreate = false;
        recID = transaction.id;
    } else {
        if (+contactID) {
            trans.contact = {
                id: +contactID
            };
        }
        trans.donation = {
            id: donationID
        };
    }

    //only do this on create
    if(!transaction && description){
        trans.description = description;
    }

    if (pnRef && pnRef.length > 0) {
        trans.refCode = pnRef;
    }

    //add a new note to the transaction
    if (newNote) {

        trans.Notes = [{
            "text": newNote
        }];
    }

    //update amount
    if (amount > 0) {
        trans.totalCharge = (+amount).toFixed(2);
    }

    //update status
    if (status) {
        trans.currentStatus = {
            "lookupName": status
        };
    }

    //update payment Method
    if (+payMethod) {
        trans.paymentMethod = {
            "id": +payMethod
        };
    }

    let returnVal = await createOrUpdateOSVCObject('financial.transactions', trans, recID, suppress);

    if (isCreate) {
        return returnVal.id;
    } else {
        return recID;
    }
}

async function createOrUpdatePledgeObj(donationID, payMethod = null, suppress = false, isCreate = false) {

    if (donationID < 1) {
        return false;
    }

    // figure out if we're create or update
    let pledgeID = await getPledgeIDByDonation(donationID);
    let pledge = {};
    if (pledgeID) {
        isCreate = false;
    }

    //update payment Method
    if (+payMethod) {
        pledge.paymentMethod2 = {
            "id": +payMethod
        };
    }

    let returnVal = await createOrUpdateOSVCObject('donation.pledge', pledge, pledgeID, suppress);

    if (isCreate) {
        return returnVal.id;
    } else {
        return pledgeID;
    }
}

/* -------------------------------------------------------------------------- */
/*                           payment api controllers                          */
/* -------------------------------------------------------------------------- */
async function processPayment(paymentMethod = null, contact = null, amount = 0, transID = null, transType = localConfigs.transType.FS_SALE_TYPE) {

    try {

        if (!paymentMethod || !paymentMethod.pmType) {
            throw new Error('Invalid payment method found');
        }

        let postData = {
            contact: contact,
            paymentMethod: paymentMethod,
            amount: amount,
            transID: transID,
            transType: transType
        };

        if (DEVMODE) {
            postData.DEVMODE = true;
        }

        return await sendPayment(postData);
    } catch (e) {
        let message = (e.statusCode ? e.statusCode + ': ' : '') + e.message;
        showNotification(message, Severity.WARNING);
        return {
            message: message,
            rawXml: 'XML not found'
        };
    }
}

async function getInfoKey(paymentMethod = null, contact = null) {

    try {

        if (!paymentMethod || !paymentMethod.pmType) {
            throw new Error('Invalid payment method found');
        }

        let postData = {
            contact: contact,
            paymentMethod: paymentMethod
        };

        return await getPaymentToken(postData);
    } catch (e) {
        showNotification(e.message, Severity.WARNING);
        return {
            message: e.message,
            rawXml: 'XML not found'
        };
    }
}