<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class TopicWords extends \RightNow\Libraries\Widget\Base {

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['appendedParameters'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . \RightNow\Utils\Url::sessionParameter();
        $this->data['topicWords'] = $this->CI->model('Report')->getTopicWords(\RightNow\Utils\Url::getParameter('kw'))->result;

        for($i = 0; $i < count($this->data['topicWords']); $i++) {
            if (!(\RightNow\Utils\Url::isExternalUrl($this->data['topicWords'][$i]['url']))) {
                $this->data['topicWords'][$i]['url'] .= $this->data['appendedParameters'];
            }
        }

        if(count($this->data['topicWords']) === 0)
            return false;
    }

}
