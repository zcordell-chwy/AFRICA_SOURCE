<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use \RightNow\Utils\Url,
    \RightNow\Utils\Text,
    \RightNow\Utils\Config;

class SubscriptionButton extends \RightNow\Libraries\Widget\Base {
    private $subscriptionApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath())) {
            return false;
        }
        $locale = Url::getParameter('loc');
        $searchCacheData = Url::getParameter('s');
        $answerData = Url::getParameter('answer_data');
        $noTitleLabel = trim($this->data['attrs']['label_no_title']);
        $answerID = Text::getSubstringBefore(Url::getParameter('s'), '_');
        $searchSession = Text::getSubstringAfter(Url::getParameter('s'), '_');
        $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
        $answer = $this->CI->model('Okcs')->getAnswerViewData(Url::getParameter('a_id'), $locale, $searchData, $answerData, $this->subscriptionApiVersion);
        if (is_array($answer) && !empty($answer)) {
            $this->data['js'] = array( 'locale' => Url::getParameter('loc'), 'answerID' => Url::getParameter('a_id'),'docId' => $answer['docID'],'versionID' => $answer['versionID']);
            $subscriptionData = $this->CI->model('Okcs')->getSubscriptionList();
            $this->data['js']['subscriptionData'] = $subscriptionData;
            
            for ($x = 0; $x < count($subscriptionData->items); $x++) {
                $subscriptionContent = $subscriptionData->items[$x];
                $ansID = (string) $subscriptionContent->content->answerId;
                if($this->data['js']['answerID'] === $ansID){
                    $this->data['js']['versionID'] = $subscriptionContent->content->versionId;
                    $this->data['js']['subscriptionID'] = $subscriptionContent->recordId;
                    break;
                }
            }
        }
        else {
            $this->data['js']['subscriptionData'] = null;
        }
    }
}
