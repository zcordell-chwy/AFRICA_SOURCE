<?php /* Originating Release: February 2019 */


namespace RightNow\Widgets;

use RightNow\Utils\Url;

class DiscussionPagination extends \RightNow\Libraries\Widget\Base {
    
    const NO_QUESTION = 0;

    function __construct ($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'get_next_prev_ajax' => array(
                'method' => 'getNextPrev',
            ),
        ));
    }

    function getData() {
        $questionID = Url::getParameter('qid');
        $question = $this->CI->model('SocialQuestion')->get($questionID)->result;
        if (!$question)
            return false;
        
        $dataType = $this->data['attrs']['type'];
        if ($dataType === 'product') {
            if ($question->Product) {
                $this->data['prodcat_id'] = $question->Product->ID;
                $this->data['prodcat_name'] = substr($question->Product->Name, 0, 75);
            }
        } else if ($dataType === 'category') {
            if ($question->Category) {
                $this->data['prodcat_id'] = $question->Category->ID;
                $this->data['prodcat_name'] = substr($question->Category->Name, 0, 75);
            }
        }

        if (!$this->data['prodcat_id'])
            return false;

        $this->data['js']['prodcat_id'] = $this->data['prodcat_id'];
        $returnData = $this->CI->model('SocialQuestion')->getPrevNextQuestionID($question->ID, $this->data['prodcat_id'], $dataType, array('oldestNewestQuestion' => true));

        if (!$returnData->result)
            return false;

        if ($returnData->result['oldestQuestion'] === $questionID) {
            $this->data['isOldestQuestion'] = true;
        }

        if ($returnData->result['newestQuestion'] === $questionID) {
            $this->data['isNewestQuestion'] = true;
        }
    }

    function getNextPrev (array $parameters) {
        $paginatorLink = $parameters['paginatorLink'];
        $key = ($paginatorLink === 'oldestQuestion' || $paginatorLink === 'newestQuestion') ? 'oldestNewestQuestion' : $paginatorLink;

        if (!$question = $this->CI->model('SocialQuestion')->get($parameters['qid'])->result) {
            echo self::NO_QUESTION;
        }
        $returnData = $this->CI->model('SocialQuestion')->getPrevNextQuestionID($parameters['qid'], $parameters['prodcat_id'], $this->data['attrs']['type'], array($key => true));

        if ($returnData->result && $returnData->result[$paginatorLink]) {
            echo $returnData->result[$paginatorLink];
        }
        else {
            echo self::NO_QUESTION;
        }
    }
}
