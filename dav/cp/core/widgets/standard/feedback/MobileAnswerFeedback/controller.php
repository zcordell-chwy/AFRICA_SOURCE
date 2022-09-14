<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class MobileAnswerFeedback extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $answerID = \RightNow\Utils\Url::getParameter('a_id');
        if($answerID) {
            $answerData = $this->CI->model('Answer')->get($answerID);
            if($answerData->error){
                return false;
            }
            $this->data['js'] = array(
                'summary' => $answerData->result->Summary,
                'answerID' => $answerID
            );
        }
        else {
            $this->data['js'] = array(
                'summary' => \RightNow\Utils\Config::getMessage(SITE_FEEDBACK_HDG),
                'answerID' => null
            );
        }

        if($emailAddress = $this->CI->session->getProfileData('email')) {
            $this->data['js']['email'] = $emailAddress;
            $this->data['js']['profile'] = true;
        }
        else if($sessionEmail = $this->CI->session->getSessionData('previouslySeenEmail')) {
            $this->data['js']['email'] = $sessionEmail;
        }

        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }
}
