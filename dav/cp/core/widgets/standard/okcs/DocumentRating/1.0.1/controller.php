<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use \RightNow\Utils\Url,
    \RightNow\Utils\Config;

class DocumentRating extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!Config::getConfig(OKCS_ENABLED)) {
            echo $this->reportError(Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        $answerID = Url::getParameter('a_id');
        if (!is_null($answerID)) {
            $article = $this->CI->model('Okcs')->getArticleDetails($answerID);
            $this->data['js'] = array( 'locale' => $article['locale'], 'answerID' => $answerID);
            $ratingData = $this->CI->model('Okcs')->getDocumentRating($article['answerID']);
            if($ratingData->error) {
                echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($ratingData->error));
                return false;
            }
            $this->data['ratingData'] = $ratingData->result;
        }
        else {
            $this->data['ratingData'] = null;
        }
    }
    
    /**
    * Method to sort rating answers
    * @param object $answer Rating answers object
    * @return object Sorted rating answers
    */
    function sortAnswer($answer) {
        usort($answer, function($answerA, $answerB) {
            return strcmp($answerA->numberValue, $answerB->numberValue);
        });
        return $answer;
    }
}
