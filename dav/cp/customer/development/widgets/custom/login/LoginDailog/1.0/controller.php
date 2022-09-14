<?php
namespace Custom\Widgets\login;
class LoginDailog extends \RightNow\Widgets\LoginDialog {
    function __construct($attrs) {
        parent::__construct($attrs);
    }
    function getData() {
        return parent::getData();
    }    /**
     * Overridable methods from LoginDialog:
     */    // function getCreateAccountFields($fieldString)    // function hasSocialUser()    // protected function getRedirectOverride()    // protected function getRedirectPage()    // protected function makeRedirectSecure($redirect)
}