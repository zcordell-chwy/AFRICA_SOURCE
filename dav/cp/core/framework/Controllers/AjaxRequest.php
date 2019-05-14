<?php

namespace RightNow\Controllers;

use RightNow\Utils\Framework,
    RightNow\Libraries\AbuseDetection,
    RightNow\Utils\Config,
    RightNow\Utils\Okcs;

/**
* Generic controller endpoint for standard widgets to make requests to retrieve data. Nearly all of the
* methods in this controller echo out their data in JSON so that it can be received by the calling JavaScript.
*/
final class AjaxRequest extends Base
{
    public function __construct()
    {
        parent::__construct();

        parent::_setClickstreamMapping(array(
            "getNewFormToken" => "form_token_update",
            "getReportData" => "report_data_service",
            "emailAnswer" => "email_answer",
            "submitAnswerFeedback" => "answer_feedback",
            "submitAnswerRating" => "answer_rating",
            "addOrRenewNotification" => "notification_update",
            "deleteNotification" => "notification_delete",
            "addSocialSubscription" => "social_subscription_create",
            "deleteSocialSubscription" => "social_subscription_delete",
            "sendForm" => "incident_submit",
            "doLogin" => "account_login",
            "getAnswer" => "answer_view"
        ));
        // Allow account creation, account recovery, and login stuff for users who aren't logged in if CP_CONTACT_LOGIN_REQUIRED is on.
        parent::_setMethodsExemptFromContactLoginRequired(array(
            'getNewFormToken',
            'sendForm',
            'checkForExistingContact', // Part of the account creation process.
            'doLogin',
            'getChatQueueAndInformation',
        ));
    }

    /**
     * Special case to handle requests to getGuidedAssistanceTree when made from
     * the agent console.
     * @internal
     */
    public function _ensureContactIsAllowed()
    {
        if($this->uri->router->fetch_method() === 'getGuidedAssistanceTree' && is_object($this->_getAgentAccount())){
            return true;
        }
        return parent::_ensureContactIsAllowed();
    }

    /**
     * Perform a search action on a report. Expects the report ID to execute, as well as filters and formatting options
     * to apply to the report.
     */
    public function getReportData()
    {
        $filters = $this->input->post('filters');
        $filters = json_decode($filters);
        $filters = (is_object($filters))
            ? get_object_vars($filters)
            : array();

        if(!$this->model('Report')->isValidOrgFilter($filters, $this->session->getProfile(true))) {
            //exit if organization filter is not valid - unauthorized access
            return;
        }

        if($filters['search'] == 1)
            $this->model('Report')->updateSessionforSearch();
        $format = $this->input->post('format');
        $format = json_decode($format);
        $format = is_object($format)
            ? get_object_vars($format)
            : array();

        $reportID = $this->input->post('report_id');
        $reportToken = $this->input->post('r_tok');

        $results = $this->model('Report')->getDataHTML($reportID, $reportToken, $filters, $format)->result;

        $this->_renderJSON($results);
    }

    /**
     * Executes a search against the given search source.
     */
    public function search() {
        $filters = @json_decode($this->input->post('filters'), true) ?: array();

        $filters['limit'] = array('value' => $this->input->request('limit'));
        $sourceID = $this->input->post('sourceID');

        $search = \RightNow\Libraries\Search::getInstance($sourceID);
        $search->addFilters($filters);

        $this->_renderJSON($search->executeSearch()->toArray());
    }

    /**
    * Retrieves an answer (containing all business object fields) specified by the answer id. Returns the answer
    * object with ID, Question, and Solution fields populated.
    */
    public function getAnswer()
    {
        AbuseDetection::check();
        $answerID = $this->input->request('objectID');
        $this->session->setSessionData(array('answersViewed' => $this->session->getSessionData('answersViewed') + 1));
        Framework::sendCachedContentExpiresHeader();
        // This request cannot be cached because of session tracking and conditional sections
        $answer = $this->model('Answer')->get($answerID);
        if($answer->result) {
            $fieldsToReturn = array('ID', 'Question', 'Solution', 'FileAttachments.*.ID', 'FileAttachments.*.FileName', 'FileAttachments.*.Size');
            if($answer->result->AnswerType->ID === ANSWER_TYPE_URL) {
                $fieldsToReturn[] = 'URL';
            }

            if($answer->result->GuidedAssistance->ID) {
                $fieldsToReturn[] = 'GuidedAssistance.ID';
            }

            $this->_echoJSON($answer->toJson($fieldsToReturn));
        }
        else {
            $this->_echoJSON($answer->toJson());
        }
    }

    /**
     * Create incident from answer feedback. Returns the ID of the incident created or an error message if it failed
     */
    public function submitAnswerFeedback()
    {
        AbuseDetection::check();
        if(!$this->checkForValidFormToken()) {
            $this->_renderJSON(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
            return;
        }

        $answerID = $this->input->post('a_id');
        if($answerID === 'null')
            $answerID = null;
        $rate = $this->input->post('rate');
        $message = $this->input->post('message');
        $givenEmail = $this->input->post('email');
        $optionsCount = $this->input->post('options_count');
        $threshold = $this->input->post('threshold');

        $incidentResult = $this->model('Incident')->submitFeedback($answerID, $rate, $threshold, null, $message, $givenEmail, $optionsCount);
        if($incidentResult->result){
            $this->_renderJSON(array('ID' => $incidentResult->result->ID));
            return;
        }
        if($incidentResult->error){
            $this->_renderJSON(array('error' => $incidentResult->error->externalMessage));
            return;
        }
        $this->_renderJSON(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
    }

    /**
     * Answer rating request. Takes the answer ID, rating and options count and rates the answer via the Answer model
     */
    public function submitAnswerRating()
    {
        $this->_renderJSON(1); // No need to wait for API call before responding
        $answerID = $this->input->post('a_id');
        $rating = $this->input->post('rate');
        $scale = $this->input->post('options_count');
        if($answerID){
            $this->model('Answer')->rate($answerID, $rating, $scale);
        }
    }

    /**
     * Request to delete a product, category or answer notification. Returns a list of errors that might have occured
     */
    public function deleteNotification()
    {
        AbuseDetection::check();
        if(!$this->checkForValidFormToken()) {
            $this->_renderJSON(array('error' => Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG)));
            return;
        }

        $response = $this->model('Notification')->delete($this->input->post('filter_type'), $this->input->post('id'), $this->input->post('cid'));
        $this->_renderJSON(array('error' => ($response->errors) ? (string) $response->error : ''));
    }


    /**
     * Request to add or renew a product, category or answer notification. Returns a list of remaining notifications
     * as well as any errors that might have occured.
     */
    public function addOrRenewNotification()
    {
        $notifications = array();

        AbuseDetection::check();
        if(!$this->checkForValidFormToken()) {
            $response = $this->model('Notification')->get(array('product', 'category'), $this->input->post('cid'));
            $error = Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG);
        }
        else {
            $response = $this->model('Notification')->add($this->input->post('filter_type'), intval($this->input->post('id')), $this->input->post('cid'));
            $error = $response->errors ? (string) $response->error : null;
        }

        // Build a simple array of notifications to send back
        if (is_array($response->result)) {
            foreach ($response->result as $key => $objects) {
                foreach ($objects as $notification) {
                    $connectName = ucfirst($key);
                    $ID = $notification->$connectName->ID;
                    $chain = $summary = '';
                    if ($connectName === 'Answer') {
                        $summary = $notification->$connectName->Summary;
                        $label = Config::getMessage(ANSWER_LBL);
                    }
                    else {
                        $label = ($connectName === 'Product') ? Config::getMessage(PRODUCT_LBL) : Config::getMessage(CATEGORY_LBL);
                        $hierarchy = "{$connectName}Hierarchy";
                        if(count($notification->$connectName->$hierarchy)) {
                            foreach ($notification->$connectName->$hierarchy as $parent) {
                                $chain .= $parent->ID . ',';
                                $summary .= $parent->LookupName . ' / ';
                            }
                        }
                        $chain .= $ID;
                        $summary .= $notification->$connectName->LookupName;
                    }
                    $notifications[] = array(
                        'id' => $ID,
                        'type' => $key,
                        'label' => $label,
                        'summary' => $summary,
                        'chain' => $chain,
                        'startDate' => Framework::formatDate($notification->StartTime, 'default', null),
                        'expiration' => $notification->ExpireTime,
                        'rawStartTime' => $notification->StartTime
                    );
                }
            }
        }

        $this->_renderJSON(array(
            'error' => $error,
            'notifications' => Framework::sortBy($notifications, true, function($n) { return $n['rawStartTime']; }),
            'action' => $response->action,
        ));
    }

    /**
     * Adds Subscription for Social Question Object
     */
    public function addSocialSubscription()
    {
        AbuseDetection::check();
        if(!$this->checkForValidFormToken()) {
            $this->_renderJSON(array('errors' => Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG)));
            return;
        }

        $response = $this->model('SocialSubscription')->addSubscription(intval($this->input->post('id')), $this->input->post('type'));
        if($response->result) {
            $this->_renderJSON(array('success' => Config::getMessage(YOU_HAVE_BEEN_SUBSCRIBED_SUCCESSFULLY_LBL)));
        }
        else {
            $this->_echoJSON($response->toJson());
        }
    }

    /**
     * Deletes Subscription for Social Question Object
     */
    public function deleteSocialSubscription()
    {
        AbuseDetection::check();
        if(!$this->checkForValidFormToken()) {
            $this->_renderJSON(array('errors' => Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG)));
            return;
        }

        $response = $this->model('SocialSubscription')->deleteSubscription(intval($this->input->post('id')), $this->input->post('type'));
        if($response->result) {
            $this->_renderJSON(array('success' => Config::getMessage(HAVE_BEEN_UNSUBSCRIBED_SUCCESSFULLY_LBL)));
        }
        else {
            $this->_echoJSON($response->toJson());
        }
    }

    /**
     * Generic form submission handler for submitting contact and incident data. Returns details about the
     * form submission, including errors, IDs of records created, or SA results if an incident is being submitted.
     */
    public function sendForm()
    {
        AbuseDetection::check($this->input->post('f_tok'));
        $data = json_decode($this->input->post('form'));
        if(!$data)
        {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512), 'application/json');
        }
        if($listOfUpdateRecordIDs = json_decode($this->input->post('updateIDs'), true)){
            $listOfUpdateRecordIDs = array_filter($listOfUpdateRecordIDs);
        }
        $smartAssistant = $this->input->post('smrt_asst');
        if($flashMessage = $this->input->post('flash_message')){
            $this->session->setFlashData('info', $flashMessage);
        }
        $this->_echoJSON($this->model('Field')->sendForm($data, $listOfUpdateRecordIDs ?: array(), ($smartAssistant === 'true'))->toJson());
    }

    /**
     * Retrieves a new form token that can be used to submit a contact or incident form.
     */
    public function getNewFormToken()
    {
        if($formToken = $this->input->post('formToken'))
        {
            $identifier = $this->input->post('tokenIdentifier') ? (int) $this->input->post('tokenIdentifier') : 0;
            $this->_renderJSON(array(
                'newToken' => Framework::createTokenWithExpiration($identifier, Framework::doesTokenRequireChallenge($formToken))
            ));
        }
    }

    /**
     * Checks that a contact doesn't already exist with the specified email or login. Returns either an error
     * message if the contact exists, or false if they don't.
     */
    public function checkForExistingContact()
    {
        // This usually gets called from a blur handler when the user tabs out of a form field.
        // That'd be a really awkward time to show a CAPTCHA dialog. Instead, I just report that the
        // contact doesn't exist. Server-side validation will report the error when the form is
        // submitted. This approach not only avoids annoying users, but also limits the ability of a
        // bad guy to launch a dictionary attack to determine the content of our contacts database. The
        // scenario where this is called is during the modified AAQ workflow. In that case, we really do
        // want a real answer, and are willing to show a CAPTCHA to get it. To do that, we post an
        // additional field to say we really want an abuse check to be returned.
        if($this->input->post('checkForChallenge')){
            AbuseDetection::check();
        }
        else if (AbuseDetection::isAbuse()) {
            Framework::writeContentWithLengthAndExit(json_encode(false), 'application/json');
        }
        $token = $this->input->post('contactToken');
        if(Framework::isValidSecurityToken($token, 1) === false){
            $this->_renderJSON(false);
            return;
        }
        $pwReset = $this->input->post('pwReset');
        if($email = $this->input->post('email'))
        {
            $paramType = 'email';
            $param = $email;
        }
        else if(!is_null($login = $this->input->post('login')))
        {
            $paramType = 'login';
            $param = $login;
        }
        $results = $this->model('Contact')->contactAlreadyExists($paramType, $param, $pwReset)->result;
        $this->_renderJSON($results);
    }

    /**
     * Perform the login of a user given their username/password. Returns the result from the
     * login. Either additional redirect information, or an error message.
     */
    public function doLogin()
    {
        AbuseDetection::check();
        if(!$this->checkForValidFormToken()) {
            $this->_renderJSON(array('message' => Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), 'showLink' => false));
            return;
        }

        $userID = $this->input->post('login');
        $password = $this->input->post('password');
        $sessionID = $this->session->getSessionData('sessionID');
        $widgetID  = $this->input->post('w_id');
        $url = $this->input->post('url');
        $result = $this->model('Contact')->doLogin($userID, $password, $sessionID, $widgetID, $url)->result;
        $this->_renderJSON($result);
    }

    /**
     * Redirects a chat request to the chat server and returns the response. Returns the result
     * from the chat server.
     */
    public function doChatRequest()
    {
        $result = $this->model('Chat')->makeChatRequest();
        if($result)
            echo $result;
    }

    /**
     * AJAX request handler for chat queue retrieval. Fetches queue ID and availability information.
     */
    public function getChatQueueAndInformation()
    {
        if($this->input->request['test'])
            return;

        $chatProduct = $this->input->request('prod');
        $chatCategory = $this->input->request('cat');
        $contactID = intval($this->input->request('c_id'));
        $orgID = intval($this->input->request('org_id'));
        $contactEmail = $this->input->request('contact_email');
        $contactFirstName = $this->input->request('contact_fname');
        $contactLastName = $this->input->request('contact_lname');
        $availType = $this->input->request('avail_type');
        $isCacheable = $this->input->request('cacheable');
        $callback = $this->input->request('callback');
        $interfaceID = \RightNow\Api::intf_id();

        $cacheKey = implode('|', array($chatProduct, $chatCategory, $contactID, $orgID, $contactEmail, $contactFirstName, $contactLastName, $interfaceID));
        $cache = new \RightNow\Libraries\Cache\Memcache(60);

        if (($chatRouteRV = $cache->get($cacheKey)) === false)
        {
            $chatRouteRV = $this->model('Chat')->chatRoute($chatProduct, $chatCategory, $contactID, $orgID, $contactEmail, $contactFirstName, $contactLastName)->result;
            $cache->set($cacheKey, $chatRouteRV);
        }

        $result = $this->model('Chat')->checkChatQueue($chatRouteRV, $availType, $isCacheable)->result;
        $this->sendCORSHeaders();

        // QA ID 121210-000179. If there's a callback, it's going to be in JSON. Send the correct header for the response.
        if($callback)
        {
            header("Content-Type: text/javascript;charset=UTF-8");
            echo "$callback(" . json_encode($result) . ")";
        }
        else
        {
            $this->_renderJSON($result);
        }
    }

    /**
    * Inserts into the widget_stats table
    * @internal
    */
    public function insertWidgetStats()
    {
        $type = $this->input->post('type');
        $widget = $this->input->post('widget');
        $column = $this->input->post('column');
        $action = (object)array('w' => $widget . '', $column => 1);
        $this->model('Clickstream')->insertWidgetStats($type, $action);
    }

    /**
     * Creates a SocialUser to go with the logged-in contact.
     * Echos a string JSON array with values for 'success', 'socialUserID',
     * and 'errors'/'warnings' (if any)
     */
    public function createSocialUser()
    {
        $errors = array();
        $warnings = array();
        $socialUserID = null;

        // ensure the user is logged in
        if (!Framework::isLoggedIn())
        {
            $errors[] = Config::getMessage(CONTACT_IS_NOT_LOGGED_IN_MSG);
        }
        else
        {
            \RightNow\Libraries\AbuseDetection::check();

            // grab contact from the session
            $contact = $this->model('Contact')->get()->result;

            // contacts already associated with SocialUsers shouldn't be calling this method
            if ($this->model('SocialUser')->getForContact($contact->ID)->result)
            {
                $errors[] = Config::getMessage(CONTACT_ALREADY_ASSOCIATED_SOCIAL_USER_MSG);
            }
            else
            {
                $input = array(
                    'Socialuser.DisplayName' => (object) array('value' => $this->input->post('displayName')),
                    'Socialuser.Contact' => (object) array('value' => $contact->ID),
                );

                $response = $this->model('SocialUser')->create($input);
                $errors = $response->errors;
                $warnings = $response->warnings;
                if ($response->result)
                {
                    $socialUserID = $response->result->ID;
                    $profile = $this->session->getProfile(true);
                    $profile->socialUserID = $socialUserID;
                    $this->session->createProfileCookie($profile);
                }
            }
        }

        // construct the output array - we don't need the whole SocialUser object
        echo json_encode(array(
            'success' => $socialUserID && empty($errors) && empty($warnings),
            'socialUserID' => $socialUserID,
            'errors' => $errors,
            'warnings' => $warnings
        ));
    }

    /**
    * Checks whether contact is logged in
    */
    public function validateChatForm()
    {
        AbuseDetection::check($this->input->post('formToken'));
        $this->_renderJSON($this->model('Chat')->chatValidate());
    }

    /**
     * Function to return the social discussion populated with ID, Subject, Body, Best Answers and Author fields
     */
    public function getDiscussion () {
        AbuseDetection::check();
        $questionID = $this->input->request('objectID');
        Framework::sendCachedContentExpiresHeader();
        $socialQuestion = $this->model('SocialQuestion')->get($questionID);
        if ($socialQuestion->result) {
            $fieldsToReturn = array('ID', 'Subject', 'Body', 'CreatedBySocialUser.AvatarURL', 'CreatedBySocialUser.DisplayName', 'CreatedBySocialUser.StatusWithType.Status.ID');
            foreach ($socialQuestion->result->BestSocialQuestionAnswers as $bestAnswer) {
                if ($bestAnswer->SocialQuestionComment->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_ACTIVE) {
                    $bestAnswer->SocialQuestionComment->Body = \RightNow\Libraries\Formatter::formatTextEntry($bestAnswer->SocialQuestionComment->Body, 'text/x-markdown', false);
                    $bestAnswer->SocialQuestionComment->CreatedBySocialUser->AvatarURL;
                    $bestAnswer->SocialQuestionComment->CreatedBySocialUser->DisplayName;
                    $bestAnswer->SocialQuestionComment->CreatedBySocialUser->StatusWithType->Status->ID;
                }
            }
            $socialQuestion->result->Body = \RightNow\Libraries\Formatter::formatTextEntry($socialQuestion->result->Body, $socialQuestion->result->BodyContentType->LookupName, false);
            $this->_echoJSON($socialQuestion->toJson($fieldsToReturn));
        }
        else {
            $this->_echoJSON($socialQuestion->toJson());
        }
    }

    /**
    * Sends the appropriate response headers for a CORS requests.
    * @param int $cacheTime The total seconds an actual response
    * for a GET/POST request should be cached for
    */
    private function sendCORSHeaders($cacheTime = 12)
    {
        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
        {
            //cache OPTIONS requests for 24 hours
            header("Access-Control-Max-Age: " . 86400);
            header("Access-Control-Allow-Headers: RNT_REFERRER,X-Requested-With");
            header("Access-Control-Allow-Methods: GET,POST");
        }
        else if($cacheTime !== 0)
        {
            //cache GET/POST requests for the given amount of time
            header("Expires: " . gmdate('D, d M Y H:i:s', time() + $cacheTime) . "GMT");
        }
        header("Access-Control-Allow-Origin: ".\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest'));
        header("Access-Control-Allow-Credentials: true");
    }

    /**
    * Method to fetch data through OKCS APIs
    * @internal
    */
    public function getOkcsData() {
        $filters = json_decode($this->input->post('filters'), true);
        if (strlen($this->input->post('doc_id')) !== 0)
        {
            $this->getIMContent();
        }
        // Click Through request if clickthroughLink is not null
        else if (strlen($this->input->post('clickThroughLink')) !== 0)
        {
            $this->clickThrough();
        }
        else if($filters['channelRecordID']['value'] !== null || $filters['currentSelectedID']['value'] !== null)
        {
            $this->browseArticles();
        }
        else if (strlen($this->input->post('deflected')) !== 0)
        {
            $this->getContactDeflectionResponse();
        }
        else if (strlen($this->input->post('categoryId')) !== 0)
        {
            $this->getChildCategories();
        }
        else if (strlen($this->input->post('surveyRecordID')) !== 0)
        {
            $this->submitRating();
        }
        else if (strlen($this->input->post('rating')) !== 0)
        {
            $this->submitSearchRating();
        }
    }

    /**
    * Method to call clickthrough OKCS API.
    */
    private function clickThrough()
    {
        $isUnstructured = $this->input->post('type');
        $answerID = $this->input->post('answerID');
        $docID = $this->input->post('docID');
        $trackedURL = $this->input->post('clickThroughLink');
        $resultLocale = $this->input->post('locale');
        $iqAction = $this->input->post('iqAction');
        $clickThroughInput = array(
            'isUnstructured' => $isUnstructured,
            'answerID' => $answerID,
            'docID' => $docID,
            'trackedURL' => $trackedURL,
            'resultLocale' => $resultLocale,
            'iqAction' => $iqAction
        );
        $result = $this->model('Okcs')->clickThrough($clickThroughInput);
        echo $result;
    }

    /**
    * Method to call browseArticles OKCS API.
    */
    private function browseArticles()
    {
        $filters = json_decode($this->input->post('filters'), true);
        $contentType = $filters['channelRecordID']['value'];
        $currentSelectedID = $filters['currentSelectedID']['value'];
        $productRecordID = $filters['productRecordID']['value'];
        $categoryRecordID = $filters['categoryRecordID']['value'];
        $isProductSelected = $filters['isProductSelected']['value'];
        $isCategorySelected = $filters['isCategorySelected']['value'];
        $categoryFetchFlag = $isProductSelected !== null || $isCategorySelected !== null ? false : true;
        $browsePage = $filters['browsePage']['value'] !== null ? $filters['browsePage']['value'] : 0;
        $pageSize = $filters['pageSize']['value'] !== null ? $filters['pageSize']['value'] : 10;
        $limit = $filters['limit']['value'];
        $truncateSize = $filters['truncate']['value'];

        if($productRecordID === null)
            $isProductSelected = null;
        if($categoryRecordID === null)
            $isCategorySelected = null;

        $isSelected = $currentSelectedID === $productRecordID ? $isProductSelected : $isCategorySelected;

        if($isProductSelected)
            $category = $productRecordID;
        if ($isCategorySelected) {
            if($category !== null)
                $category .= ':' . $categoryRecordID;
            else
                $category = $categoryRecordID;
        }

        $filter = array(
            'type' => '',
            'limit' => $limit,
            'contentType' => $contentType,
            'category' => $category,
            'pageNumber' => $browsePage,
            'pageSize' => $pageSize,
            'truncate' => $truncateSize
        );

        $articleResult = $this->model('Okcs')->getArticlesSortedBy($filter);
        $response = array(
            'error' => ($articleResult->errors) ? $articleResult->error->errorCode . ': ' .
                        $articleResult->error->externalMessage : null,
            'articles'      => $articleResult->results,
            'filters'       => '',
            'columnID'      => $columnID = 0,
            'sortDirection' => $sortDirection = 0,
            'selectedChannel' => $contentType,
            'hasMore'       => $articleResult->hasMore,
            'currentPage'   => $browsePage
        );

        if (strlen($category) === 0 && strlen($currentSelectedID) === 0 && $categoryFetchFlag){
            $categoryResult = $this->model('Okcs')->getChannelCategories($contentType);
            $response["category"] = $categoryResult->results;
        }
        else
            $response["categoryRecordID"] = $currentSelectedID;

        if($isSelected)
            $response["isCategorySelected"] = $isSelected;

        echo json_encode($response);
    }

    /**
    * Method to fetch details of an OKCS IM content
    */
    private function getIMContent()
    {
        $docID = $this->input->post('doc_id');
        $highlightedLink = $this->input->post('highlightedLink');
        $answerType = $this->input->post('answerType');

        //If highlighting is enabled
        if(strlen($highlightedLink) !== 0) {
            $response = $this->model('Okcs')->processIMContent($docID, $highlightedLink, $answerType);
        }
        else {
            $response = $this->model('Okcs')->processIMContent($docID);
        }

        if ($answerType !== 'HTML' && $response['content'] !== null) {
            $contentTypeSchema = $this->model('Okcs')->getIMContentSchema($response['contentType']->referenceKey, $response['locale']);
            if ($contentTypeSchema->error === null) {
                $okcs = new \RightNow\Utils\Okcs();
                $channelData = $okcs->getAnswerView($response['content'], $contentTypeSchema['contentSchema'], "CHANNEL", $response['resourcePath']);
                $response['content'] = $channelData;
                if($contentTypeSchema['metaSchema'] !== null) {
                    $metaData = $okcs->getAnswerView($response['metaContent'], $contentTypeSchema['metaSchema'], "META", $response['resourcePath']);
                    $response['metaContent'] = $metaData;
                }
            }
            else {
                return false;
            }
        }
        echo json_encode(array(
            'error' => ($response->errors) ? (string) $response->error : null,
            'id' => $docID,
            'contents' => $response
        ));
    }

    /**
    * Method to call getContactDeflectionResponse OKCS API.
    */
    private function getContactDeflectionResponse(){
        $priorTransactionID = $this->input->post('priorTransactionID');
        $deflected = $this->input->post('deflected');
        $session = $this->input->post('okcsSearchSession');
        $response = $this->model('Okcs')->getContactDeflectionResponse($priorTransactionID, $deflected, $session);
        echo json_encode($response);
    }

    /**
    * Method to call getChildCategories OKCS API to pull children of a parent category.
    */
    private function getChildCategories(){
        $categoryID = $this->input->post('categoryId');
        $response = $this->model('Okcs')->getChildCategories($categoryID);
        if($response->results !== null)
            echo json_encode($response->results);
        else
            echo json_encode(array('error' => $response->error));
    }

    /**
    * Method to submit Info Manager document rating.
    */
    private function submitRating(){
        $surveyRecordID = $this->input->post('surveyRecordID');
        $answerRecordID = $this->input->post('answerRecordID');
        $contentRecordID = $this->input->post('contentRecordID');
        $localeRecordID = $this->input->post('localeRecordID');
        $response = $this->model('Okcs')->submitRating($surveyRecordID, $answerRecordID, $contentRecordID, $localeRecordID);
        echo json_encode($response->results);
    }

    /**
    * Method to submit search rating.
    */
    private function submitSearchRating()
    {
        $rating = $this->input->post('rating');
        $feedback = $this->input->post('feedback');
        $priorTransactionID = $this->input->post('priorTransactionID');
        $okcsSearchSession = $this->input->post('okcsSearchSession');
        $response = $this->model('Okcs')->submitSearchRating($rating, $feedback, $priorTransactionID, $okcsSearchSession);
        echo json_encode($response->results);
    }

    /**
     * Looks for a form token in the post parameters and verifies its validity.
     * @return Boolean Whether the form token exists and is valid
     */
    private function checkForValidFormToken() {
        $formToken = $this->input->post('f_tok');
        return count($_POST) && $formToken && Framework::isValidSecurityToken($formToken, 0);
    }
}
