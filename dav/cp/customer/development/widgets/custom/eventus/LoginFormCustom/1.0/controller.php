<?php
namespace Custom\Widgets\eventus;

class LoginFormCustom extends \RightNow\Widgets\LoginForm {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        if(\RightNow\Utils\Framework::isLoggedIn()){
            $this->data['js']['isLoggedIn'] = true;
        }
            
        \RightNow\Utils\Url::redirectIfPageNeedsToBeSecure();
        if(\RightNow\Utils\Url::getParameter('redirect'))
        {
            //We need to check if the redirect location is a fully qualified URL, or just a relative one
            $redirectLocation = urldecode(urldecode(\RightNow\Utils\Url::getParameter('redirect')));
            $parsedURL = parse_url($redirectLocation);

            if($parsedURL['scheme'] || (\RightNow\Utils\Text::beginsWith($parsedURL['path'], '/ci/') || \RightNow\Utils\Text::beginsWith($parsedURL['path'], '/cc/')))
            {
                $this->data['js']['redirectOverride'] = $redirectLocation;
            }
            else
            {
                $this->data['js']['redirectOverride'] = \RightNow\Utils\Text::beginsWith($redirectLocation, '/app/') ? $redirectLocation : "/app/$redirectLocation";
            }
            
            if(\RightNow\Utils\Url::getParameter('event') != "" ){
                $this->data['js']['redirectOverride']  .= "/event/".\RightNow\Utils\Url::getParameter('event');
            }
        }

        //honor: (1) attribute's value (2) config
        $this->data['attrs']['disable_password'] = $this->data['attrs']['disable_password'] ?: !\RightNow\Utils\Config::getConfig(EU_CUST_PASSWD_ENABLED);
        $this->data['username'] = \RightNow\Utils\Url::getParameter('username');
        if($this->CI->agent->browser() === 'Internet Explorer')
            $this->data['isIE'] = true;

    }
}