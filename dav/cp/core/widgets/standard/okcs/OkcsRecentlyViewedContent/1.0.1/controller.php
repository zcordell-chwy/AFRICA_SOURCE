<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Utils\Url;

class OkcsRecentlyViewedContent extends \RightNow\Widgets\RecentlyViewedContent {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['js']['previousContent'] = $this->CI->model('Okcs')->getRecentlyViewedAnswers();
        $this->data['js']['cpAnswerView'] = \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL);
        $this->data['js']['currentAnswerId'] = Url::getParameter('a_id');
    }
}
