<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class OkcsEmailAnswerLink extends \RightNow\Widgets\EmailAnswerLink {
    private $answerViewApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }
    
    function getData() {
        $docID = Url::getParameter('a_id');
        $locale = Url::getParameter('loc');
        $searchCacheData = Url::getParameter('s');
        $answerData = Url::getParameter('answer_data');
        $answerID = Text::getSubstringBefore(Url::getParameter('s'), '_');
        $searchSession = Text::getSubstringAfter(Url::getParameter('s'), '_');
        $this->data['attrs']['object_type'] = 'answer';

        if(Url::getParameter('a_id') == ''){
            echo $this->reportError('AnswerID is not available');
            return false;
        }

        $searchData = array('answerId' => $answerID, 'searchSession' => $searchSession, 'prTxnId' => Url::getParameter('prTxnId'), 'txnId' => Url::getParameter('txnId'));
        $answer = $this->CI->model('Okcs')->getAnswerViewData($docID, $locale, $searchData, $answerData, $this->answerViewApiVersion);
        if ($answer['error']) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($answer['error']));
            return false;
        }

         $this->data['js'] = array(
            'emailAnswerToken' => \RightNow\Utils\Framework::createTokenWithExpiration(146),
            'isProfile' => false,
        );
        $this->data['js']['docId'] = $docID;
        $this->data['js']['title'] = $answer['title'];
        if($profile = $this->CI->session->getProfile(true))
        {
            // @codingStandardsIgnoreStart
            $this->data['js']['senderName'] = trim((\RightNow\Utils\Config::getConfig(intl_nameorder)) ? $profile->lastName . ' ' . $profile->firstName : $profile->firstName . ' ' . $profile->lastName);
            // @codingStandardsIgnoreEnd
            $this->data['js']['senderEmail'] = $profile->email;
            $this->data['js']['isProfile'] = true;
        }
        else
        {
            $this->data['js']['senderEmail'] = $this->CI->session->getSessionData('previouslySeenEmail') ?: '';
        }
        
    }
}
