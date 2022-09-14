<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Utils\Config,
    RightNow\Utils\Url;

class AnswerContent extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!Config::getConfig(OKCS_ENABLED)) {
            echo $this->reportError(Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        $this->data = $this->CI->model('Okcs')->getAnswerViewData(Url::getParameter('a_id'), Url::getParameter('loc'), Url::getParameter('s'), Url::getParameter('answer_data'));
    }
}
