const https = require('https');

let callOracleScriptHandler = function (rawMsg) {
    return new Promise((resolve, reject) => {
        let outputData = JSON.stringify(rawMsg);
        let responseBody = '';
        let options = {
            hostname: process.env.ENDPOINT_HOST,
            port: 443,
            path: process.env.ENDPOINT_PATH,
            method: 'POST',
            headers: {
                'X_CUSTOM_AUTHORIZATION': process.env.X_CUSTOM_AUTHORIZATION_HEADER_VALUE,
                'Content-Type': 'application/json',
                'Content-Length': outputData.length
            }
        };

        let req = https.request(options, (response) => {
            response.on('data', (chunky) => {
                responseBody += chunky.toString();
            })
            response.on('end', () => {
                resolve(responseBody);
            })
        });

        req.on('error', (e) => {
            reject(e.message);
        });
        req.on('end', () => {
            resolve();
        })
        req.write(outputData);
        req.end();
    });
};

let processOracleScriptResults = function (rawResults) {
    console.log(rawResults);
    let parsedResults = JSON.parse(rawResults);
    if ("data" in parsedResults) {
        return parsedResults.data;
    } else if ("errors" in parsedResults) {
        throw new Error(parsedResults.errors);
    }
    throw new Error("Invalid response from Oracle SNS Listener: " + rawResults);

}

exports.handler = async (event, context) => {

    console.log(JSON.stringify(event));
    
    return callOracleScriptHandler(event).then(function (httpsReturnVal) {
        return processOracleScriptResults(httpsReturnVal);
    }, function (rejected) {
        var error = new Error(rejected);
        console.error(error);
        return error;
    });

};
