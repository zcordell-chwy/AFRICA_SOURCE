RightNow.namespace("RightNow.Chat.Communicator");
/**
 * This namespace contains the functions related to the Chat module
 * @namespace
 */
RightNow.Chat = RightNow.Chat || {};
/**
 * This namespace contains functions which relate to Chat communications
 * @namespace
 */
RightNow.Chat.Communicator = function(Y)
{
    this.Y = Y;
    this._communicationMethod = RightNow.Chat.Model.CommunicationMethod.AJAX_POST;
    this._baseUrl = '';
    this._baseParameters = null;
    this._javaSessionID = null;
    this._redirectUrl = '/ci/ajaxRequest/doChatRequest';
    this._siteName = '';
    this._clusterPoolID = '';
    this._appendRandomURLValue = false;
    this._lastTransactionID = 0;
};
RightNow.Chat.Communicator.prototype = {
    /**
     * Determines the request type and sets the general communication method to use:
     * If Browser supports Cross domain requests, will use the POST request type
     * Else uses the GET request type
     * @param {Object} connectionData Connection properties
     */
    initialize: function(connectionData)
    {
        var userAgent = navigator.userAgent,
            versionIndex = userAgent.indexOf('Firefox/');

        // Configure communication methods
        if(versionIndex !== -1)
        {
            // Detect version. If Firefox 3.5+, use special XSS communication features.
            if(parseFloat(userAgent.substring(versionIndex + 8)) >= 3.5)
                this._communicationMethod = RightNow.Chat.Model.CommunicationMethod.AJAX_POST;
        }
        else if(this.Y.UA.ie >= 8 && window.XDomainRequest)
        {
            // If IE8+, use XDomainRequest
            this._communicationMethod = RightNow.Chat.Model.CommunicationMethod.XDOMAIN_REQUEST;
        }
        else if(navigator.vendor && navigator.vendor.indexOf('Apple') !== -1 && userAgent.indexOf('iPhone') === -1)
        {
            versionIndex = userAgent.indexOf('Version');

            if(versionIndex !== -1)
            {
                var version = parseFloat(userAgent.substring(versionIndex + 8));

                if(version && version >= 4)
                    this._communicationMethod = RightNow.Chat.Model.CommunicationMethod.AJAX_POST;
            }
        }
        else if(userAgent.indexOf('iPhone') !== -1)
            this._appendRandomURLValue = true;
        else if(userAgent.indexOf('Chrome') !== -1 && window.XMLHttpRequest)
            this._communicationMethod = RightNow.Chat.Model.CommunicationMethod.AJAX_POST;

        var cookieChatSessionId = this.Y.Cookie.get("CHAT_SESSION_ID");
        if(cookieChatSessionId)
            this._javaSessionID = cookieChatSessionId;

        this._baseUrl = 'http' + (connectionData.useHttps ? 's' : '') + '://' + connectionData.chatServerHost + (connectionData.chatServerPort === 80 || connectionData.chatServerPort === 443 ? '' : ':' + connectionData.chatServerPort) + '/Chat/chat/' + connectionData.dbName;
        this._clusterPoolID = RightNow.Interface.getConfig("CHAT_CLUSTER_POOL_ID");
        this._baseParameters = {
            'site_name': connectionData.dbName,
            'responseType': 'JSON'
        };

        this._siteName = connectionData.dbName;
    },
    /**
     * Makes the request
     * @param {Object} config Request properties
     */
    makeRequest: function(config)
    {
        var url,
            data = config.data || {},
            requestMethod = this._communicationMethod,
            failureRetry = config.failureRetry || false,
            scope = config.scope;

        if(config.useTransactionID)
            data.tId = (++this._lastTransactionID);

        if(config.forceRnwRedirect)
        {
            requestMethod = RightNow.Chat.Model.CommunicationMethod.RNW_REDIRECT;
            url = this._redirectUrl;
            data.jsessionID = this._javaSessionID;

            // RightNow's posted variables do not allow 'action' parameter to be a string or the 'msg' parameter to contain more than one line
            data = this.Y.merge(data, {'chatAction': data.action || data.chatAction, 'message': data.msg || data.message, 'action': null, 'msg': null});
        }
        else
        {
            url = this._baseUrl + (this.includeJavaSessionIDInUrl() ? ';jsessionid=' + this._javaSessionID : '');
        }

        url += '?' + (this._clusterPoolID === '' ? '' : 'pool=' + this._clusterPoolID + '&');

        // Prune all undefined/null/blank slots in input data
        for(var key in data)
        {
            if(data[key] === null || data[key] === undefined || data[key] === '')
                delete data[key];
        }
        var postString = this.Y.QueryString.stringify(this.Y.merge(this._baseParameters, data));

        // YUI3 IO (Local and cross-domain XHR/XDR)
        // The maximum that we can store in the chat transcript is 1MB = 1048576 characters.
        // Foreign languages can take up upto 3 bytes per character (1MB/3 = 349525)
        // OTR messages are transmitted via POST and are only subject to the maximum that can
        // be stored in a chat transcript
        if(config.testAllowed)
            return !(data.msg.length > 349525);

        var ioConfig = {
            data: postString,
            method: 'POST',
            on: {
                success: function(transactionId, response, args) {
                    // Only use callback if one was specified on request
                    if(args.success !== null)
                    {
                        try
                        {
                            var jsonData = this.Y.JSON.parse(response.responseText);

                            // Some calls want data passed back in the response object. Add here.
                            if(args.data !== undefined)
                                jsonData = this.Y.merge(jsonData, {data: args.data});
                        }
                        catch(e)
                        {
                            return;
                        }

                        // All callbacks will receive just the JSON response from the server, possibly augmented with the data object above
                        args.success.apply(this, [jsonData]);
                    }
                },
                failure: config.on ? (config.on.failure || null) : null
            },
            // @codingStandardsIgnoreStart
            arguments: {success: (config.on ? (config.on.success || null) : null), data: config.callbackArgument},
            // @codingStandardsIgnoreEnd
            context: scope,
            timeout: failureRetry ? 15000 : 45000
        };

        if(config.synchronous)
            ioConfig.sync = true;

        if(requestMethod !== RightNow.Chat.Model.CommunicationMethod.RNW_REDIRECT)
        {
            // IE10+ and YUI 3.7.3+ use IE10's built in support for XHR instead of XDR, where content-type is set correctly.
            // Skip XDR specific parts for IE10+.
            if(this.Y.UA.ie && this.Y.UA.ie < 10)
            {
                url += "parametersTextInBody=true";

                ioConfig.xdr = {
                    credentials: true,
                    use: 'native'
                };
            }

            this.Y.io.header('X-Requested-With');
        }

        ioConfig.xdr = {
            credentials: true,
            use: 'native'
        };

        if(this._javaSessionID)
            this.Y.io.header('X-JSESSIONID', this._javaSessionID);

        //use xmlhttprequest rather than Y.io() in logoff case.
        //Y.io doesn't work in firefox, several versions of chrome and IE.
        if (data.action === "LOGOFF" && config.synchronous)
        {
            //Safari, Firefox, Chrome and IE 11 needs more special handling
            //Here we are also considering the coming versions of IE( IE 11+)
            if (this.Y.UA.safari || this.Y.UA.ie > 10 || this.Y.UA.gecko || this.Y.UA.chrome)
            {
                this.makeXmlHttpRequest(url, config, postString, false);
            }
            else
            {
                var closer = new Image;
                closer.src = url + "&" + postString;
            }
        }
        else
        {
            this.Y.io(url, ioConfig);
        }
    },
    /**
     * Makes an xml http request
     * @param {string} url XmlHttpRequest.open url
     * @param {Object} config Request properties
     * @param {string} postString XmlHttpRequest post parameter string
     * @param {boolean} async if the request should be handled asynchronously or not.
     */
    makeXmlHttpRequest: function(url, config, postString, async)
    {
        var xmlhttp = new XMLHttpRequest();
        var scope = this;
        xmlhttp.onreadystatechange = function()
        {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
            {
                var jsonData = scope.Y.JSON.parse(xmlhttp.responseText);

                // Some calls want data passed back in the response object. Add here.
                if(config.data !== undefined)
                    jsonData = scope.Y.merge(jsonData, {data: config.data});

                config.on.success.apply(config.scope, [jsonData]);
            }
        }

        if ("withCredentials" in xmlhttp)
        {
            xmlhttp.open('POST', url, async);
            if(this._javaSessionID)
                xmlhttp.setRequestHeader('X-JSESSIONID', this._javaSessionID);
            xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xmlhttp.send(postString);
        }
    },
    /**
     * Sets the java session ID
     * @param {number} javaSessionID
     */
    setJavaSessionID: function(javaSessionID)
    {
        this._javaSessionID = javaSessionID;
    },
    /**
     * Sets the cluster pool ID
     * @param {number} clusterPoolID
     */
    setClusterPoolID: function(clusterPoolID)
    {
        this._clusterPoolID = clusterPoolID;
    },
    /**
     * Obtains the last transaction ID
     * @return {number}
     */
    getLastTransactionID: function()
    {
        return this._lastTransactionID;
    },
    /**
     * Returns true if this is an XDOMAIN request
     * @return {boolean}
     */
    isXDRTransaction: function()
    {
        return this._communicationMethod === RightNow.Chat.Model.CommunicationMethod.XDOMAIN_REQUEST;
    },
    includeJavaSessionIDInUrl: function()
    {
        return this._javaSessionID && this.Y.UA.ie && this.Y.UA.ie < 10;
    }
};
