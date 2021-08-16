/* -------------------------------------------------------------------------- */
/*                                  API Model                                 */
/* -------------------------------------------------------------------------- */
async function getPaymentToken(data = null) {

    return await callPaymentEngine('TOKEN', data);
}

async function sendPayment(data = null) {

    return await callPaymentEngine('PAYMENT', data);
}

/**
 * 
 * @param {*} authStr 
 */
async function callPaymentEngine(action = 'PAYMENT', postData = null, dataType = null, options = {}) {

    let reqData = null;

    let reqJson = {};
    reqJson.action = action;
    dataType && (reqJson.type = dataType);
    postData && (reqJson.data = postData);
    reqData = JSON.stringify(reqJson);

    return await callCustomScript('payment_engine.php', reqData, dataType, options);
}

async function getReportResults(reportID, filters = null) {

    let postData = {
        id: reportID
    }
    if (filters) {
        postData.filters = filters;
    };

    return await callOSCRestAPI('analyticsReportResults', 'POST', postData);
}

/**
 * 
 * @param {*} query 
 * @returns 
 */
async function getOSVCQueryResults(query) {

    let restQuery = 'queryResults/?query=' + query;
    return await callOSCRestAPI(restQuery);
}

async function createOrUpdateOSVCObject(resource, postData = null, recID = 0, suppress = false) {

    let method = 'POST';
    let dataType = 'json';
    if (recID) {
        resource += '/' + recID;
        method = 'PATCH';
        dataType = 'text' //because response returns null and using JSON here throws error even with status 200
    }
    return await callOSCRestAPI(resource, method, postData, suppress, dataType);
}

/**
 * 
 * @param {*} restQuery 
 * @param {*} method 
 * @param {*} postData 
 * @returns 
 */
async function callOSCRestAPI(restQuery, method = 'GET', postData = null, suppress = false, dataType = 'json') {

    let url = tokenObj.url + '/connect/v1.4/' + restQuery;
    let headers = {
        'Authorization': 'Session ' + tokenObj.sessionToken,
        'OSvC-CREST-Application-Context': appName,
    };
    if (method == 'PATCH') {
        headers['X-HTTP-Method-Override'] = 'PATCH';
        method = 'POST';
    }
    if (suppress) {
        headers['OSvC-CREST-Suppress-All'] = true;
    }
    if (postData) {
        postData = JSON.stringify(postData);
    }

    return await makeAjaxRequest(url, method, postData, headers, dataType);
}

/**
 * 
 * @param {*} scriptPath 
 * @param {*} postData 
 * @returns 
 */
async function callCustomScript(scriptPath, postData, dataType = null, additionalOptions = null) {

    let url = tokenObj.interfaceUrl + '/php/custom/' + scriptPath;
    let headers = {
        'X_AGENT_SESSION_TOKEN': tokenObj.sessionToken
    };

    let result = await makeAjaxRequest(url, 'POST', postData, headers, dataType, additionalOptions, true);
    if (result.errors) {
        throw new Error(errors);
    } else if (result.data) {
        return result.data;
    } else {
        throw new Error('Failed to get error details');
    }
}

function getJSON(url) {

    return Promise.resolve(
        $.getJSON(url)
            .catch(jqXHR => handleAjaxError(jqXHR)));
}

/**
 * 
 * @param {*} url 
 * @param {*} method 
 * @param {*} postData 
 * @param {*} headers 
 * @param {*} dataType 
 * @param {*} additionalOptions 
 * @returns 
 */
function makeAjaxRequest(url, method = 'GET', postData = null, headers = null, dataType = null, additionalOptions = null, jApi = false) {

    let ajaxObj = {};
    if (!url) {
        throw new Error('Ajax call URL missing');
    }
    else {
        ajaxObj.url = url;
    }

    ajaxObj.method = method;
    if (postData) {
        ajaxObj.data = postData;
    }
    if (headers) {
        ajaxObj.headers = headers;
    }
    if (dataType) {
        ajaxObj.dataType = dataType;
    }

    if (additionalOptions) {
        Object.assign(ajaxObj, additionalOptions);
    }

    return Promise.resolve(
        $.ajax(ajaxObj)
            .catch(jqXHR => handleAjaxError(jqXHR, jApi)));
}

function handleAjaxError(jqXHR, jApi = false) {

    // show error
    let err = new Error();
    if (jApi) {

        // JSON API spec response, like from our custom scripts
        err.statusCode = jqXHR.status;
        err.message = (jqXHR.responseJSON && jqXHR.responseJSON.errors) ? jqXHR.responseJSON.errors : (jqXHR.statusText ? jqXHR.statusText : 'Unknown Error');
    } else {

        if (jqXHR.responseJSON) {
            err.statusCode = jqXHR.responseJSON.status;
            err.message += jqXHR.responseJSON.status + ': ' + jqXHR.responseJSON.detail + ' (' + jqXHR.responseJSON["o:errorCode"] + ')';
        }
        else {
            err.statusCode = jqXHR.status;
            err.message += jqXHR.status + ": " + jqXHR.statusText;
        }
    }
    throw new AjaxError(err.message, err.statusCode);
}