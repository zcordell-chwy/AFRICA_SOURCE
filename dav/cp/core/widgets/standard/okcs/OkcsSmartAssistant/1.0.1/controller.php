<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class OkcsSmartAssistant extends \RightNow\Widgets\SmartAssistantDialog {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!(\RightNow\Utils\Config::getConfig(OKCS_ENABLED))) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        parent::getData();
    }
}