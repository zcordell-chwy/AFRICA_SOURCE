<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class PreviousAnswers extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $previousAnswers = $this->CI->model('Answer')->getPreviousAnswers(\RightNow\Utils\Url::getParameter('a_id'), $this->data['attrs']['limit'], $this->data['attrs']['truncate_size']);
        if($previousAnswers->error || $previousAnswers->result === null || (is_array($previousAnswers->result) && count($previousAnswers->result) === 0))
            return false;
        $this->data['previousAnswers'] = $previousAnswers->result;
        $this->data['appendedParameters'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        if($this->data['attrs']['highlight'] && \RightNow\Utils\Url::getParameter('kw')) {
            for($i = 0; $i < count($this->data['previousAnswers']); $i++)
                $this->data['previousAnswers'][$i][1] = \RightNow\Utils\Text::emphasizeText($this->data['previousAnswers'][$i][1]);
        }
    }
}
