var DEVMODE = false;

var Severity = Object.freeze({ SUCCESS: 1, INFO: 2, WARNING: 3, ERROR: 4 });

var appName = 'BUIExtension';   // will be set by the main js file
var appVersion = '1.0';
var tokenObj = {};
var customConfigs = {};

var extensionProviderPromise = null;
var globalContextPromise = null;
var extensionContextPromise = null;
var sessionPromise = null;
var workspaceExtensionPromise = null;
var modalWindowPromise = null;
var statusBarPromise = null;
var globalHeaderPromise = null;
var attachmentsPromise = null;
var navigationSetPromise = null;
var contentPanePromise = null;

var modalWindowMap = {};

/* ---------------- Extensibility Framework Helper Functions ---------------- */
/**
 * 
 * @returns 
 */
function getExtensionProvider() {

    if (!extensionProviderPromise) {

        extensionProviderPromise = ORACLE_SERVICE_CLOUD.extension_loader.load(appName, appVersion);
    }
    return extensionProviderPromise;
}

/**
 * 
 * @returns 
 */
function getGlobalContext() {

    if (globalContextPromise) {

        return globalContextPromise;
    } else {

        var globalContextPromise_1 = new ORACLE_SERVICE_CLOUD.ExtensionPromise();
        globalContextPromise = globalContextPromise_1;
        getExtensionProvider().then(function (extensionProvider) {

            extensionProvider.getGlobalContext().then(function (globalContext) {

                globalContextPromise_1.resolve(globalContext);
            });
        });
        return globalContextPromise_1;
    }
}

/**
 * 
 * @returns 
 */
function getSessionToken() {

    return new Promise((resolve, reject) => {
        getGlobalContext().then(function (globalCtx) {

            globalCtx.getSessionToken().then(function (sessionToken) {

                tokenObj = {
                    sessionToken: sessionToken,
                    url: globalCtx.getInterfaceServiceUrl('REST'),
                    interfaceUrl: globalCtx.getInterfaceUrl(),
                    acctID: globalCtx.getAccountId().toString(),
                    login: globalCtx.login,
                    profile: globalCtx.profileName
                };
                resolve(tokenObj);
            });
        });
    });
}

/**
 * 
 * @returns 
 */
function getExtensionContext() {

    if (extensionContextPromise) {

        return extensionContextPromise;
    } else {

        var extensionContextPromise_1 = new ORACLE_SERVICE_CLOUD.ExtensionPromise();
        extensionContextPromise = extensionContextPromise_1;
        getGlobalContext().then(function (globalContext) {

            globalContext.getExtensionContext(appName).then(function (extensionContext) {

                extensionContextPromise_1.resolve(extensionContext);
            });
        });
        return extensionContextPromise_1;
    }
}

/**
 * 
 * @returns 
 */
function getRegisteredWorkspaceRecord() {

    if (workspaceExtensionPromise) {

        return workspaceExtensionPromise;
    } else {

        var workspaceExtensionPromise_1 = new ORACLE_SERVICE_CLOUD.ExtensionPromise();
        workspaceExtensionPromise = workspaceExtensionPromise_1;
        getExtensionProvider().then(function (extensionProvider) {

            extensionProvider.registerWorkspaceExtension(function (WRecord) {

                workspaceExtensionPromise_1.resolve(WRecord);
            });
        });
        return workspaceExtensionPromise_1;
    }
}

/**
 * 
 * @returns 
 */
function getModalWindowContext() {
    if (modalWindowPromise) {

        return modalWindowPromise;
    } else {

        var modalWindowPromise_1 = new ORACLE_SERVICE_CLOUD.ExtensionPromise();
        modalWindowPromise = modalWindowPromise_1;
        getExtensionProvider().then(function (extensionProvider) {

            extensionProvider.registerUserInterfaceExtension(function (IUserInterfaceContext) {
                IUserInterfaceContext.getModalWindowContext().then(function (IModalWindowContext) {
                    modalWindowPromise_1.resolve(IModalWindowContext);
                });
            });
        });
        return modalWindowPromise_1;
    }
}

/**
 * 
 * @returns 
 */
function getGlobalHeaderContext() {
    if (globalHeaderPromise) {

        return globalHeaderPromise;
    } else {

        var globalHeaderPromise_1 = new ORACLE_SERVICE_CLOUD.ExtensionPromise();
        globalHeaderPromise = globalHeaderPromise_1;
        getExtensionProvider().then(function (extensionProvider) {

            extensionProvider.registerUserInterfaceExtension(function (IUserInterfaceContext) {
                IUserInterfaceContext.getGlobalHeaderContext().then(function (IGlobalHeaderContext) {
                    globalHeaderPromise_1.resolve(IGlobalHeaderContext);
                });
            });
        });
        return globalHeaderPromise_1;
    }
}

/**
 * 
 * @returns 
 */
function getNavigationSetContext() {
    if (navigationSetPromise) {

        return navigationSetPromise;
    } else {

        var navigationSetPromise_1 = new ORACLE_SERVICE_CLOUD.ExtensionPromise();
        navigationSetPromise = navigationSetPromise_1;
        getExtensionProvider().then(function (extensionProvider) {

            extensionProvider.registerUserInterfaceExtension(function (IUserInterfaceContext) {
                IUserInterfaceContext.getNavigationSetContext().then(function (INavigationSetContext) {
                    navigationSetPromise_1.resolve(INavigationSetContext);
                });
            });
        });
        return navigationSetPromise_1;
    }
}

/**
 * 
 * @returns 
 */
function getContentPaneContext() {
    if (contentPanePromise) {

        return contentPanePromise;
    } else {

        var contentPanePromise_1 = new ORACLE_SERVICE_CLOUD.ExtensionPromise();
        contentPanePromise = contentPanePromise_1;
        getExtensionProvider().then(function (extensionProvider) {

            extensionProvider.registerUserInterfaceExtension(function (IUserInterfaceContext) {
                IUserInterfaceContext.getContentPaneContext().then(function (IContentPaneContext) {
                    contentPanePromise_1.resolve(IContentPaneContext);
                });
            });
        });
        return contentPanePromise_1;
    }
}

/**
 * 
 * @param {*} recordType 
 * @param {*} recordID 
 * @param {*} callbackFunction 
 * @returns 
 */
function openWorkspaceRecord(recordType, recordID, callbackFunction) {
    return new Promise((resolve, reject) => {
        if (recordID && recordID > 0) {
            getRegisteredWorkspaceRecord().then(function (WorkspaceRecord) {
                WorkspaceRecord.editWorkspaceRecord(recordType, recordID, callbackFunction);
                resolve(true);
            });

        } else {
            reject(new Error('Could not open workspace record'));
        }
    });
}

/**
 * 
 * @param {*} recordType 
 * @param {*} callbackFunction 
 * @returns 
 */
function createWorkspaceRecord(recordType, callbackFunction) {
    return new Promise((resolve, reject) => {
        getRegisteredWorkspaceRecord().then(function (WorkspaceRecord) {
            WorkspaceRecord.createWorkspaceRecord(recordType, callbackFunction);
            resolve(true);
        });
    });
}

/**
 * 
 * @param {*} width 
 * @param {*} height 
 * @param {*} closable 
 * @returns 
 */
function getModalWindow(windowID, width = 300, height = 500, closable = true) {

    if (!windowID) {
        throw new Error('Ivalid value for modal window ID passed');
    }

    if (!modalWindowMap[windowID]) {
        getModalWindowContext()
            .then(function (IModalWindowContext) {
                // create modal popup
                let modalWindow = IModalWindowContext.createModalWindow();
                modalWindow.setWidth(width + 'px');
                modalWindow.setHeight(height + 'px');
                modalWindow.setClosable(closable);     // set false to close programatically

                modalWindowMap[windowID] = modalWindow;
            })
            .catch(e => {
                throw new Error('Error while initializing ' + windowID + ' window');
            });
    } else {
        return modalWindowMap[windowID];
    }
}

/**
 * 
 * @param {*} id 
 * @param {*} name 
 * @param {*} url 
 * @returns 
 */
function getContentPane(id = null, name = null, url = null) {
    return new Promise((resolve, reject) => {
        getContentPaneContext().then(function (IContentPaneContext) {

            new Promise((resolve, reject) => {
                if (id) {
                    IContentPaneContext.createContentPane(id).then(function (IContentPane) {
                        resolve(IContentPane);
                    });
                } else {
                    IContentPaneContext.createContentPane().then(function (IContentPane) {
                        resolve(IContentPane);
                    });
                }
            })
                .then(contentPane => {
                    name && contentPane.setName(name);
                    url && contentPane.setContentUrl(url);

                    resolve(contentPane);
                })
        });
    });
}

/**
 * 
 * @param {*} propertyList 
 * @returns 
 */
function getProperties(propertyList) {

    return new Promise((resolve, reject) => {

        getExtensionContext().then(function (extensionContext) {

            extensionContext.getProperties(propertyList).then(function (collection) {
                propertyList.forEach(propName => {
                    const property = collection.get('PropertyName1');
                    customConfigs.propName = property.getValue();
                });

                resolve(true);
            })
        })
    });
}

/**
 * 
 * @param {*} configsToLoad 
 * @param {*} forcePull 
 * @returns 
 */
function loadConfigs(configsToLoad, forcePull = false) {

    // Don't hit the api unnecessarily when we already have the configs loaded up
    if (!$.isEmptyObject(customConfigs) && !forcePull) {

        return Promise.resolve(true);
    } else {

        return loadConfigSettings(configsToLoad)
            .then(response => {
                customConfigs = response;
                return Promise.resolve(true);
            });
    }
}

function getFieldValuesFromEvent(parameter) {

    return parameter.event.fieldObjects;
}

function getFieldValuesFromFieldDetails(fieldList, IFieldDetails) {

    let workspaceFields = {};
    for (let key in fieldList) {

        let fieldName = fieldList[key];
        workspaceFields[fieldName] = {};
        workspaceFields[fieldName].label = IFieldDetails.getField(fieldName) ? IFieldDetails.getField(fieldName).getLabel() : '';
        workspaceFields[fieldName].value = IFieldDetails.getField(fieldName) ? IFieldDetails.getField(fieldName).getValue() : '';
    }

    return fields;
}

/* ------------------------------ Event Helper ------------------------------ */
/**
 * 
 * @param {*} name 
 * @param {*} sourceApp 
 * @returns 
 */
function createEventObj(name, sourceApp = null, targetApp = null) {

    return { name: name, source: sourceApp, target: targetApp };
}

/**
 * 
 * @param {*} eventName 
 * @param {*} callback 
 * @returns 
 */
function subscribeEvent(eventName, callback) {

    return new Promise((resolve, reject) => {

        getGlobalContext().then(function (globalCtx) {

            globalCtx.registerAction(eventName, eventData => {
                // check if event was intended for this extension instance
                if (eventData.target && eventData.target != workspace.id) {
                    return;
                }
                return callback(eventData);
            });
            resolve(true);
        });
    });
}

/**
 * 
 * @param {*} eventName 
 * @param {*} eventData 
 * @param {*} callback 
 * @returns 
 */
function fireEvent(eventName, eventData, callback = null) {

    return new Promise((resolve, reject) => {

        getGlobalContext().then(function (globalCtx) {

            if (callback) {
                globalCtx.invokeAction(eventName, eventData)
                    .then(callback)
                    .then(x => {
                        resolve(true);
                    });
            } else {
                globalCtx.invokeAction(eventName, eventData);
                resolve(true);
            }
        });
    });
}

/**
 * 
 * @param {*} waitPromise 
 * @returns 
 */
function waitFor(waitPromise) {

    return new Promise((resolve, reject) => {

        waitPromise.resolve = resolve;
        waitPromise.reject = reject;
    });
}

/* ---------------------------- Helper Functions ---------------------------- */
class Warning extends Error {
    /**
     * 
     * @param {*} message 
     */
    constructor(message) {
        super(message);
        this.name = 'Warning';
        this.type = Severity.WARNING;
    }
}

class Info extends Error {
    /**
     * 
     * @param {*} message 
     */
    constructor(message) {
        super(message);
        this.name = 'Info';
        this.type = Severity.INFO;
    }
}

class AjaxError extends Error {
    /**
     * 
     * @param {*} message 
     * @param {*} code 
     */
    constructor(message, code = null) {
        super(message);
        this.name = 'AjaxError';
        code && (this.statusCode = code);
    }
}

/* ---------------------------- Utility Functions --------------------------- */
/**
 * PHP like sprintf implementation
 * Datatypes are ignored. Default type is %s (can take text/number etc) 
 * @param {type} str string composed of zero or more directives, each of which results in fetching its own parameter. 
 * @param {type} argv list of arguments to replace placeholders
 */
var sprintf = (str, ...argv) => str ? (!argv.length ? str :
    sprintf(str = str.replace(sprintf.token || '%s', argv.shift()), ...argv)) : '';

/**
 * 
 * @param {*} source 
 * @param {*} toReplace 
 * @param {*} replaceWith 
 * @returns 
 */
function replaceEnd(source, toReplace, replaceWith) {
    return source.replace(new RegExp(toReplace + '$'), replaceWith);
}

/**
 * 
 * @param {*} num 
 * @returns 
 */
function getRawNumber(num) {
    return ('' + num).replace(/\D/g, '');
}

/**
 * 
 * @param {*} sParam 
 * @returns 
 */
function getURLParameter(sParam) {

    let value = '';
    let sPageURL = window.location.search.substring(1);
    let sURLVariables = sPageURL.split('&');
    for (let i = 0; i < sURLVariables.length; i++) {
        let sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            value = decodeURIComponent(sParameterName[1]);
            break;
        }
    }

    return value;
}

/**
 * 
 * @returns 
 */
function isDevMode() {

    //local storage operates on strings
    var devLocalStr = window.localStorage.getItem('is_dev_mode');
    //Use API data by default
    if (devLocalStr == null) {
        devLocalStr = DEVMODE;
        window.localStorage.setItem('is_dev_mode', devLocalStr);
    }

    return (devLocalStr == 'true');
}

/**
 * 
 * @param {*} message 
 * @param {*} obj 
 */
function debuglog(message = '', obj = null) {
    if (DEVMODE) {
        var caller_line = (new Error).stack.split('\n')[4];
        var reg = /^.*:(.*):.*$/g;
        var match = reg.exec(caller_line);
        console.log(appName + '::' + (debuglog.caller.name ? debuglog.caller.name : 'Anonymous') + ':' + (match ? match[1] : '') + '>' + message);
        obj && console.log(obj);
    }
}

/**
 * 
 * @param {*} millis 
 * @returns 
 */
const delay = millis => new Promise((resolve, reject) => {
    setTimeout(_ => resolve(), millis)
});