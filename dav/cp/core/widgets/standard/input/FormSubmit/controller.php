<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

class FormSubmit extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // f_tok is used for ensuring security between data exchanges.
        // Do not remove.
        // If the contact is logged in, the token may need to be refreshed as often as the profile cookie needs to be refreshed.
        // Otherwise, the token may need to be refreshed as often as the sessionID needs to be refreshed.
        if (Framework::isLoggedIn()) {
            $idleLength = $this->CI->session->getProfileCookieLength();
            if ($idleLength === 0)
                $idleLength = PHP_INT_MAX;
        }
        else {
            $idleLength = $this->CI->session->getSessionIdleLength();
        }
        $this->data['js'] = array(
            'f_tok' => Framework::createTokenWithExpiration(0, $this->data['attrs']['challenge_required']),
            //warn of form expiration five minutes (in milliseconds) before the token expires or the profile cookie or sessionID needs to be refreshed
            'formExpiration' => 1000 * (min(60 * \RightNow\Utils\Config::getConfig(SUBMIT_TOKEN_EXP), $idleLength) - 300)
        );
        if ($this->data['attrs']['challenge_required'] && $this->data['attrs']['challenge_location']) {
            $this->data['js']['challengeProvider'] = \RightNow\Libraries\AbuseDetection::getChallengeProvider();
        }
        $this->data['attrs']['add_params_to_url'] = Url::getParametersFromList($this->data['attrs']['add_params_to_url']);

        if ($redirect = Url::getParameter('redirect')) {
            //Check if the redirect location is a fully qualified URL, or just a relative one
            $redirectLocation = urldecode(urldecode($redirect));
            $parsedURL = parse_url($redirectLocation);

            if (!$parsedURL['scheme'] &&
                !Text::beginsWith($parsedURL['path'], '/ci/') &&
                !Text::beginsWith($parsedURL['path'], '/cc/') &&
                !Text::beginsWith($redirectLocation, '/app/')) {
                $redirectLocation = "/app/$redirectLocation";
            }
            $this->data['attrs']['on_success_url'] = $redirectLocation;
        }
    }
}
