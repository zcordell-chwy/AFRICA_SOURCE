<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class OkcsSmartAssistant extends \RightNow\Widgets\SmartAssistantDialog {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath())) {
            return false;
        }
        parent::getData();
    }
}