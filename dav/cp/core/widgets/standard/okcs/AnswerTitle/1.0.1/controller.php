<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Utils\Url;

class AnswerTitle extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!Config::getConfig(OKCS_ENABLED)) {
            echo $this->reportError(Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        $docID = Url::getParameter('a_id');
        $locale = Url::getParameter('loc');
        $searchCacheData = Url::getParameter('s');
        $answerData = Url::getParameter('answer_data');
        $noTitleLabel = trim($this->data['attrs']['label_no_title']);
        $this->data = $this->CI->model('Okcs')->getAnswerViewData($docID, $locale, $searchCacheData, $answerData);
        if(($this->data['title'] === Config::getMessage(NO_TTLE_LBL)) && $noTitleLabel !== '')
            $this->data['title'] = $noTitleLabel;
    }
}
