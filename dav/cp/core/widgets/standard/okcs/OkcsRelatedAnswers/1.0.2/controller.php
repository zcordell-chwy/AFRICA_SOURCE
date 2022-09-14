<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class OkcsRelatedAnswers extends \RightNow\Widgets\RelatedAnswers {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->classList = $this->classList->remove("rn_RelatedAnswers");
        if (!$answerID = Url::getParameter('a_id')) {
            return false;
        }

        $this->data['appendedParameters'] = Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . '/related/1' . Url::sessionParameter();

        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $relatedAnswers = $this->CI->model('Okcs')->getRelatedAnswers($answerID, $this->data['attrs']['limit']);
        if($relatedAnswers->error || (is_array($relatedAnswers) && count($relatedAnswers) === 0)) {
            return false;
        }
        $this->data['relatedAnswers'] = $relatedAnswers;

        // if the highlight attribute is enabled but we don't have a search term, turn highlight off
        if ($this->data['attrs']['highlight'] && !Url::getParameter('kw')) {
            $this->data['attrs']['highlight'] = false;
        }
    }
}
