<?php
if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}
require_once(DOCROOT . '/include/services/AgentAuthenticator.phph');

if (isset($_GET['authFail'])) {
    // http_response_code(401); // does not work with 401 yet
    header('HTTP/1.0 401 Unauthorized', true, 401);
    exit();
}
$authtoken = '';

if (isset($_SERVER['HTTP_X_CUSTOM_AUTHORIZATION'])) {
    $authtoken = $_SERVER['HTTP_X_CUSTOM_AUTHORIZATION'];
} elseif (isset($_SERVER['HTTP_HTTP_X_CUSTOM_AUTHORIZATION'])) {
    //let's not mention this to anyone...
    $authtoken = $_SERVER['HTTP_HTTP_X_CUSTOM_AUTHORIZATION'];
}
if (isset($_SERVER['HTTP_X_CUSTOM_AUTHORIZATION']) || isset($_SERVER['HTTP_HTTP_X_CUSTOM_AUTHORIZATION'])) {
    $credentials = split(":", base64_decode($authtoken));
    AgentAuthenticator::authenticateCredentialsAndProfile($credentials[0], $credentials[1], array(
        "Administrator",
        "API_Access",
        "Eventus - Full Access"
    ), '/custom/utilities/credential_auth.php?authFail', REDIRECT_TO_REDIRECT_PAGE);
} elseif (isset($_SERVER['HTTP_X_AGENT_SESSION_TOKEN']) && strlen($_SERVER['HTTP_X_AGENT_SESSION_TOKEN']) > 0) {
    AgentAuthenticator::authenticateSessionID($_SERVER['HTTP_X_AGENT_SESSION_TOKEN'], '/custom/utilities/credential_auth.php?authFail', REDIRECT_TO_REDIRECT_PAGE);
} elseif (SHOW_LOGIN) {
    $pass = (!empty($_POST['password'])) ? htmlspecialchars(trim($_POST['password'])) : "";
    $user = (!empty($_POST['username'])) ? htmlspecialchars(trim($_POST['username'])) : "";
    return AgentAuthenticator::authenticateCookieOrCredentialsAndProfile($user, $pass, array(
        "Admin", 'Dev - Admin'
    ));
} else {
    header('HTTP/1.0 401 Unauthorized', true, 401);
    exit();
}
