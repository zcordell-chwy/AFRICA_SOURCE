<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use \RightNow\Utils\Url,
    \RightNow\Utils\Config,
    RightNow\Utils\Okcs;

class DocumentRating extends \RightNow\Libraries\Widget\Base {
    private $documentRatingApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $answerID = Url::getParameter('a_id');
        if (!is_null($answerID)) {
            $this->data['js'] = array('locale' => Url::getParameter('loc'), 'answerID' => $answerID);
            $ratingData = $this->CI->model('Okcs')->getDocumentRating($answerID, $this->documentRatingApiVersion, $this->data['attrs']['display_to_anonymous']);
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
