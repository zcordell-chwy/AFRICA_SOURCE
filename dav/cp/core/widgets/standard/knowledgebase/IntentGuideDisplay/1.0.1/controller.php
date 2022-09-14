<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class IntentGuideDisplay extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(!\RightNow\Utils\Config::getConfig(INTENT_GUIDE_ENABLED, 'RNW')) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_CONFIG_SET_ENABLED_ORDER_MSG), '<m4-ignore>INTENT_GUIDE_ENABLED</m4-ignore>'));
            return false;
        }
        if($guideID = ($this->data['attrs']['intent_guide_id'] ?: \RightNow\Utils\Url::getParameter('ig_id'))) {
            if($guide = $this->data['intentGuide'] = $this->CI->model('Intentguide')->getIntentGuide($guideID, array('highlight' => $this->data['attrs']['highlight']))) {
                $guideType = $guide->question->type;
                if (\RightNow\Utils\Text::stringContains($guideID, '_') && ($guideType === 'DropDown' || $guideType === 'MultiAnswer')) {
                    $subQuestion = end(explode('_', $guideID));
                    if ($guideType === 'DropDown') {
                        $questionText = $guide->question->similar[$subQuestion - 1];
                    }
                    else {
                        $questionText = $guide->question->text;
                    }
                    $answer = $guide->answers[$subQuestion - 1];
                }
                else {
                    $questionText = $guide->question->text;
                    $answer = $guide->answers[0];
                }
                \RightNow\ActionCapture::record('intentGuideAnswer', 'view', $answer->resultID);
                $this->data['question'] = $questionText;
                $this->data['category'] = $guide->question->categoryName;
                if($answer->type === 'Answer') {
                    if($answerObject = $this->CI->model('Answer')->get($answer->answerID)) {
                        if(!$answerObject->error){
                            $answerSummary = ($this->data['attrs']['highlight']) ? \RightNow\Libraries\Formatter::highlight($answerObject->result->Summary) : $answerObject->result->Summary;
                            $this->data['answer'] = "<a href='{$answer->URL}" . \RightNow\Utils\Url::sessionParameter() . "'>{$answerSummary}</a>";
                        }
                    }
                }
                else if($answer->type === 'Url') {
                    $this->data['answer'] = "<a href='{$answer->url}'>{$answer->url}</a>";
                }
                else {
                    $this->data['answer'] = ($answer->type === 'Html') ? htmlspecialchars_decode($answer->text, ENT_QUOTES) : $answer->text;
                }
            }
        }
        else {
            return false;
        }
    }
}
