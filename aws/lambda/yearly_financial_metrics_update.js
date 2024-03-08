const https = require('https');

let callRequestHandler = async function(rawMsg, host, path, method, form = false){

    var headersResultsOracle = {
        'OSvC-CREST-Application-Context': "Yearly Metrics",
        'Content-Type' : 'application/json',
        "Authorization": "Basic emNvcmRlbGw6UGFzc3dvcmQx" 
    }

    return new Promise((resolve, reject) => {
        let outputData = JSON.stringify(rawMsg);
        let responseBody = '';
        let options = {
            hostname: host,
            port: 443,
            path: path,
            method: method,
            headers: headersResultsOracle
        };

        let req = https.request(options, (response) => {


            if (response.statusCode < 200 || response.statusCode > 299) {
                //Error handling when non 200-299 status code.
                response.on("data", (chunky) => {
                    reject(chunky.toString());
                })
            } else {
                response.on('data', (chunky) => {
                    responseBody += chunky.toString();
                })
                response.on('end', () => {
                    resolve(responseBody);
                })
            }
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
}

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

let processDonationList = async function(donationStartVal){

    //console.log(donationStartVal);
    var u1000contactArray = [];
    var u5000contactArray = [];
    var u25000contactArray = [];
    var u50000contactArray = [];
    var u100000contactArray = [];
    var o100000contactArray = [];
    //get 1000 donations and process them
    let transactionRoql = "select t.ID, t.contact, t.donation.Amount_n, t.donation from financial.transactions t where t.ID > " + parseInt(donationStartVal) + " AND  t.ID <= " + parseInt(donationStartVal + 1000) + " AND t.CreatedTime >= '2023-01-01 00:00:00' AND t.CreatedTime < '2024-01-01 00:00:00Z' AND t.currentStatus.ID = 3 AND t.donation.Amount_n > '1.00' ORDER BY t.ID ASC LIMIT 1000";
    console.log(transactionRoql);
    var transRes = await callRequestHandler(null, 'africanewlife.custhelp.com', '/services/rest/connect/v1.4/queryResults?query=' + escape(transactionRoql), 'GET' );
    let transResObj = JSON.parse(transRes);
    var totalCharge = 0;
    var largestDonationAmt = 0;
    var largestDonationID;
    //console.log(transResObj.items[0].rows[1]);
    for(let i = 0; i <= transResObj.items[0].count - 1; i += 1){
        //console.log(transResObj.items[0].rows[i]);

        if(transResObj.items[0].rows[i][2] > 1 && transResObj.items[0].rows[i][2] <= 1000){
            u1000contactArray.push(transResObj.items[0].rows[i][1]);
        }else if(transResObj.items[0].rows[i][2] <= 5000){
            u5000contactArray.push(transResObj.items[0].rows[i][1]);
        }else if(transResObj.items[0].rows[i][2] <= 25000){
            u25000contactArray.push(transResObj.items[0].rows[i][1]);
        }else if(transResObj.items[0].rows[i][2] <= 50000){
            u50000contactArray.push(transResObj.items[0].rows[i][1]);
        }else if(transResObj.items[0].rows[i][2] <= 100000){
            u100000contactArray.push(transResObj.items[0].rows[i][1]);
        }else if(transResObj.items[0].rows[i][2] > 100000){
            o100000contactArray.push(transResObj.items[0].rows[i][1]);
        }

        if(parseInt(transResObj.items[0].rows[i][2]) > largestDonationAmt){
            largestDonationAmt = parseInt(transResObj.items[0].rows[i][2]);
            largestDonationID = parseInt(transResObj.items[0].rows[i][3]);
        }

        if(parseInt(transResObj.items[0].rows[i][2])){
            totalCharge += parseInt(transResObj.items[0].rows[i][2]);
        }else{
            console.log("transaction amount not present")
            console.log(transResObj.items[0].rows[i])
        }

    }

    //make them unique
    let cleanU1000 = [...new Set(u1000contactArray)];
    let cleanU5000 = [...new Set(u5000contactArray)];
    let cleanU25000 = [...new Set(u25000contactArray)];
    let cleanU50000 = [...new Set(u50000contactArray)];
    let cleanU100000 = [...new Set(u100000contactArray)];
    let cleanO100000 = [...new Set(o100000contactArray)];

    return [cleanU1000.length, cleanU5000.length, cleanU25000.length, cleanU50000.length, cleanU100000.length, cleanO100000.length, largestDonationAmt, largestDonationID, totalCharge];
}

exports.handler = async (event, context) => {

    var processDonationsPromiseArray = [];

    console.log(JSON.stringify(event));

    let lowerRoql = "select financial.transactions.ID from financial.transactions t where t.CreatedTime >= '2023-01-01 00:00:00' AND t.CreatedTime < '2024-01-01 00:00:00Z' AND t.currentStatus.ID = 3 AND t.donation.Amount_n > '1.00'ORDER BY t.ID ASC LIMIT 1";
    var lowerRes = await callRequestHandler(null, 'africanewlife.custhelp.com', '/services/rest/connect/v1.4/queryResults?query=' + escape(lowerRoql), 'GET' );
    let lowerResObj = JSON.parse(lowerRes);
    let lowerBound = parseInt(lowerResObj.items[0].rows[0][0]);

    let upperRoql = "select financial.transactions.ID from financial.transactions t where t.CreatedTime >= '2023-01-01 00:00:00' AND t.CreatedTime < '2024-01-01 00:00:00Z' AND t.currentStatus.ID = 3 AND t.donation.Amount_n > '1.00'ORDER BY t.ID DESC LIMIT 1";
    var upperRes = await callRequestHandler(null, 'africanewlife.custhelp.com', '/services/rest/connect/v1.4/queryResults?query=' + escape(upperRoql), 'GET' );
    let upperResObj = JSON.parse(upperRes);
    let upperBound = parseInt(upperResObj.items[0].rows[0][0])

    console.log("lower:" + lowerBound + " upper:" + upperBound);

    for(let i = lowerBound; i <= upperBound; i += 1000){
    //for(let i = lowerBound; i <= lowerBound + 5000; i += 1000){
        //console.log(i);
        processDonationsPromiseArray.push(processDonationList(i));
    }
    

    let completedTransactionArray = await Promise.all(processDonationsPromiseArray);

    var u1000Total = 0;
    var u5000Total = 0;
    var u25000Total = 0;
    var u50000Total = 0;
    var u100000Total = 0;
    var o100000Total = 0;

    var largestDonationAmt = 0;
    var largestDonationID;
    var totalDonations = 0;

    for (let z = 0; z < completedTransactionArray.length; z++) {
        console.log(completedTransactionArray[z]);
        u1000Total += completedTransactionArray[z][0];
        u5000Total += completedTransactionArray[z][1];
        u25000Total += completedTransactionArray[z][2];
        u50000Total += completedTransactionArray[z][3];
        u100000Total += completedTransactionArray[z][4];
        o100000Total += completedTransactionArray[z][5];

        totalDonations += completedTransactionArray[z][8];
        console.log("total Donations:" + totalDonations);

        if(parseInt(completedTransactionArray[z][6]) > largestDonationAmt){
            largestDonationAmt = parseInt(completedTransactionArray[z][6]);
            largestDonationID = parseInt(completedTransactionArray[z][7]);
        }
    }

    console.log(u1000Total + " " +u5000Total + " "+u25000Total + " "+u50000Total + " "+u100000Total + " "+o100000Total);

    let postBody = {
        "Year": "2023",
        "NumberUniqueDonors" : u1000Total + u5000Total + u25000Total + u50000Total + u100000Total + o100000Total,
        "NumDonorsUnder1000" : u1000Total,
        "NumDonorsUnder5000" : u5000Total,
        "NumDonorsUnder25000" : u25000Total,
        "NumDonorsUnder50000" : u50000Total,
        "NumDonorsUnder100000" : u100000Total,
        "NumDonorsOver100000" : o100000Total,
        "totalDonationsAmount" : totalDonations.toString() + ".00",
        "largestDonation" : {
            "id" : parseInt(largestDonationID)
        }

    }
    
    console.log(postBody);
    await callRequestHandler(postBody, 'africanewlife.custhelp.com', '/services/rest/connect/v1.4/Metrics.YearlyFinancialStats/1', 'PATCH' );

    //update the record
    return {
        statusCode: 200,
      };

};
