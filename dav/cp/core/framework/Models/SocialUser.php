<?php /* Originating Release: February 2019 */

namespace RightNow\Models;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Utils\Validation,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\ConnectTabular,
    RightNow\Utils\Url,
    RightNow\Utils\Date,
    RightNow\Connect\v1_3 as Connect;

/**
 * Performs operations with the SocialUser Connect object.
 */
class SocialUser extends SocialObjectBase {
    /**
     * Returns an empty SocialUser object.
     *
     * @return Connect\SocialUser SocialUser
     */
    public function getBlank() {
        $blankUser = parent::getBlank();
        \RightNow\Libraries\Decorator::add($blankUser, array('class' => 'Permission/SocialUserPermissions', 'property' => 'SocialPermissions'));
        return $this->getResponseObject($blankUser);
    }

    /**
     * Returns a Connect SocialUser for the given id.
     *
     * @param  int $id SocialUser id. If unspecified and user is logged in,
     * the logged-in user's record is returned
     * @return Connect\SocialUser|null SocialUser or null if there are error messages and the user wasn't retrieved
     */
    public function get($id = null) {
        if($id === null){
            if(Framework::isSocialUser()){
                $id = $this->CI->session->getProfileData('socialUserID');
            }
            else{
                return $this->getResponseObject(null, null, Config::getMessage(SOCIAL_SPECIFIED_SOCIAL_LOGGED_IN_MSG));
            }
        }
        $user = parent::get($id);
        if(!is_object($user)){
            return $this->getResponseObject(null, null, $user ?: sprintf(Config::getMessage(SOCIAL_USER_WITH_ID_S_DOES_NOT_EXIST_LBL), $id));
        }

        \RightNow\Libraries\Decorator::add($user, array('class' => 'Permission/SocialUserPermissions', 'property' => 'SocialPermissions'));

        if (!$user->SocialPermissions->canRead()) {
            return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_READ_PERMISSI_SOCIAL_USER_LBL));
        }
        return $this->getResponseObject($user);
    }

    /**
     * Gets the SocialUser attached to the specified Contact.
     * @param  int $contactID Contact id
     * @return Connect\SocialUser|null SocialUser or null if there are error messages and the user wasn't retrieved
     */
    public function getForContact($contactID) {
        if (!Framework::isValidID($contactID)) {
            return $this->getResponseObject(null, null, "Invalid ID: $contactID");
        }
        // get the contact, then attempt to fetch the SocialUser (will be null if nonexistent)
        try {
            if ($contact = Connect\Contact::fetch($contactID)) {
                $user = $contact->SocialUser;
            }
        }
        catch (\Exception $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($user, null, ($user) ? null : sprintf(Config::getMessage(SOCIAL_USER_NOT_FOUND_CONTACT_ID_S_LBL), $contactID));
    }

    /**
     * Creates a SocialUser.
     *
     * @param  array $formData Form data to create the SocialUser with
     * @param  Boolean $updateSession Whether to update the logged-in session profile
     * @return Connect\SocialUser|null Created Social or null if there are error messages and
     *              the user wasn't created
     */
    public function create(array $formData, $updateSession = false) {
        if ($formData['Socialuser.Contact']->value && $this->getForContact($formData['Socialuser.Contact']->value)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(THERE_SOCIAL_ALREADY_TH_CONTACT_LBL));
        }

        if ($formData['Socialuser.DisplayName']->value) {
            // create a blank social user and attach to the contact; DisplayName is required
            $newSocialUser = $this->getBlank()->result;

            $errors = $warnings = array();
            $this->assignFormFieldValues($newSocialUser, $formData, $errors, $warnings, false, array('SocialUser.id'));
            if ($errors) {
                return $this->getResponseObject(null, null, $errors);
            }

            if (!$newSocialUser->SocialPermissions->canCreate()) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_CREATE_SOCIAL_USER_LBL));
            }

            return $this->createOrUpdateUser($newSocialUser, __FUNCTION__, $updateSession, $warnings);
        }
    }

    /**
     * Updates a SocialUser.
     *
     * @param  int     $id            ID of the SocialUser to update
     * @param  array   $formData      Form data to update the SocialUser with
     * @param  Boolean $updateSession Whether to update the logged-in session profile
     * @param  Boolean $isOpenLogin   Whether the caller is OpenLogin
     * @return Connect\SocialUser|null Updated SocialUser or null if there are error
     *                                                   messages and the user wasn't update
     */
    public function update($id, array $formData, $updateSession = false, $isOpenLogin = false) {
        if(!$isOpenLogin) {
            $socialUserResponseObject = $this->getSocialUser();
            if($socialUserResponseObject->errors) {
                return $socialUserResponseObject;
            }
        }

        $user = $this->get($id);
        if (!$user->result) {
            return $user;
        }

        $errors = $warnings = array();
        // Skip checking permissions when the update request comes from OpenLogin
        $this->assignFormFieldValues($user->result, $formData, $errors, $warnings, !$isOpenLogin);
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        return ($user->result) ? $this->createOrUpdateUser($user->result, __FUNCTION__, $updateSession, $warnings) : $user;
    }

    /**
     * Updates moderator action on the user
     * @param int $userID ID of the user to update
     * @param array $data Action data to update the user with
     * @return RightNow\Libraries\ResponseObject Connect\SocialUser on success else error response object
     */
    public function updateModeratorAction($userID, array $data) {
        return $this->update($userID, $data);
    }

    /**
     * Performs the create or update on the user.
     *
     * @param  Connect\SocialUser      $user      SocialUser
     * @param  string                  $operation Either 'create' or 'update'
     * @param  Boolean $updateSession Whether to update the logged-in session profile
     * @param  array $warnings Any warnings accumulated by the calling code
     * @return Connect\SocialUser|null Updated user or null if there was an error
     */
    protected function createOrUpdateUser(Connect\SocialUser $user, $operation, $updateSession, array $warnings) {
        $operation = "{$operation}Object";
        $source = ($operation === 'create') ? SRC2_EU_NEW_CONTACT : SRC2_EU_CONTACT_EDIT;

        try {
            $user = parent::$operation($user, $source);
        }
        catch (\Exception $e) {
            $user = $e->getMessage();
        }
        if (!is_object($user)) {
            return $this->getResponseObject(null, null, $user);
        }

        if ($updateSession) {
            $this->CI->session->writePersistentProfileData('socialUserID', $user->ID);
        }

        return $this->getResponseObject($user, 'is_object', null, $warnings);
    }

    /**
     * Processes form fields and assigns values on the SocialUser.
     *
     * @param Connect\SocialUser $user             SocialUser
     * @param array              $fields           Form fields
     * @param array              &$errors          Populated with error messages
     * @param array              &$warnings        Populated with warning messages
     * @param boolean            $checkPermissions If true and an update is present, check user has permission to perform the update.
     * @param array              $fieldsToIgnore   A list of field names to ignore.
     */
    protected function assignFormFieldValues(Connect\SocialUser $user, array $fields, array &$errors, array &$warnings, $checkPermissions = true, array $fieldsToIgnore = array('SocialUser.id', 'SocialUser.contact')) {
        foreach ($fields as $name => $field) {
            if (!Text::beginsWithCaseInsensitive($name, 'socialuser') || in_array($name, $fieldsToIgnore)) {
                continue;
            }

            $fieldComponents = explode('.', $name);
            try {
                list($currentValue, $metaData) = ConnectUtil::getObjectField($fieldComponents, $user);
            }
            catch (\Exception $e) {
                $warnings []= $e->getMessage();
                continue;
            }

            if ($currentValue !== $field->value) {
                if ($checkPermissions && !\RightNow\Utils\Permissions\Social::userCanEdit(implode('.', array_slice($fieldComponents, 1)), $user->ID, $field->value, $errors)) {
                    continue;
                }

                // make sure displayname has any special chars html encoded
                if (Text::stringContainsCaseInsensitive($name, 'displayname')) {
                    $field->value = htmlspecialchars($field->value);
                }

                if (Validation::validate($field, $name, $metaData, $errors)) {
                    $field->value = ConnectUtil::castValue($field->value, $metaData);
                    if ($error = parent::setFieldValue($user, $name, $field->value)) {
                        $errors []= $error;
                    }
                }
            }
        }
    }

    /**
     * Retrieves a list of recent social users.
     * @param int $count The number of recent users to be fetched.
     * @param string $interval The time period to consider for determining the recency.
     * @return array Array containing the list of user metadata
     */
    public function getRecentlyActiveUsers($count, $interval) {
        $result = $users = $comments = $questions = array();
        $timePeriod = Date::add(Date::getCurrentDateTime(), -1, $interval, 0);
        try {
            $commentRoql = sprintf("SELECT CreatedBySocialUser, MAX(CreatedTime) FROM SocialQuestionComment WHERE CreatedTime > '" . $timePeriod . "' AND ParentCreatedBySocialUser.StatusWithType.StatusType = %d GROUP BY CreatedbySocialUser ORDER BY MAX(CreatedTime) DESC LIMIT " . $count, STATUS_TYPE_SSS_USER_ACTIVE);
            $commentObjects = Connect\ROQL::query($commentRoql)->next();
            while($row = $commentObjects->next()){
                $comments[] = array('user' => $row['CreatedBySocialUser'], 'createdTime' => $row['MAX(CreatedTime)']);
            }

            $questionRoql = sprintf("SELECT CreatedBySocialUser, MAX(CreatedTime) FROM SocialQuestion WHERE CreatedTime > '" . $timePeriod . "' AND ParentCreatedBySocialUser.StatusWithType.StatusType = %d GROUP BY CreatedbySocialUser ORDER BY MAX(CreatedTime) DESC LIMIT " . $count, STATUS_TYPE_SSS_USER_ACTIVE);
            $questionObjects = Connect\ROQL::query($questionRoql)->next();
            while($row = $questionObjects->next()){
                $questions[] = array('user' => $row['CreatedBySocialUser'], 'createdTime' => $row['MAX(CreatedTime)']);
            }
            $result = array_merge($comments, $questions);

            $recentUsers = array_reduce($result, function ($result, $item) {
                if($item['user']) {
                    if (array_key_exists($item['user'], $result)) {
                        $createdTime = (Date::diff($result[$item['user']]['createdTime'], $item['createdTime']) > 0) ? $item['createdTime'] : $result[$item['user']]['createdTime'];
                        $result[$item['user']] = array('id' => $item['user'], 'createdTime' => $createdTime);
                    }
                    else{
                        $result[$item['user']] = array('id' => $item['user'], 'createdTime' => $item['createdTime']);
                    }
                }
                return $result;
            }, array());

            $userList = array_filter(array_keys($recentUsers));
            $userData = $this->getUsersByIDs($userList)->result;
            $users = array_replace_recursive($userData, $recentUsers);

            usort($users, function ($a, $b) {
                return ($a['createdTime'] < $b['createdTime']);
            });
        }
        catch(Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($users, 'is_array');
    }

    /**
     * Fetches the metadata for the social users based on the user ID provided.
     * @param array $userIDs List of users whose metadata should be retrieved.
     * @return array Array containing the list of user metadata
     */
    public function getUsersByIDs(array $userIDs = array()) {
        $users = array();
        if(!empty($userIDs)) {
            try {
                $roql = "SELECT ID, DisplayName, AvatarURL, StatusWithType.StatusType FROM SocialUser WHERE ID IN (" . implode(',', $userIDs) . ")";
                $query = ConnectTabular::query($roql, false);
                $results = $query->getCollection();
                foreach ($results as $result) {
                    $users[$result->ID] = array('user' => $result);
                }
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, $e->getMessage());
            }
        }
        return $this->getResponseObject($users, 'is_array', null, null);
    }

    /**
     * Retrieves a list of Community Self Service users along with relevant data such as count of questions, comments or best answers.
     * @param string $contentType The type of content to be fetched. Possible values are: questions, comments, best_answers
     * @param int $count The number of users to be fetched.
     * @param string $interval The time period to consider for fetching results.
     * @return array Array containing the list of user metadata
     */
    public function getListOfUsers($contentType, $count, $interval) {
        $results = $users = $comments = $questions = array();
        $timePeriod = $interval ? Date::add(Date::getCurrentDateTime(), -1, $interval, 0) : null;
        try {
            if($contentType === 'questions') {
                $roql = sprintf("SELECT CreatedBySocialUser, Count(*) as Count FROM SocialQuestion WHERE " . ($timePeriod ? "CreatedTime > '" . $timePeriod . "' AND " : null) . "ParentCreatedBySocialUser.StatusWithType.StatusType = %d AND StatusWithType.StatusType = %d GROUP BY CreatedbySocialUser ORDER BY Count(*) DESC LIMIT " . $count, STATUS_TYPE_SSS_USER_ACTIVE, STATUS_TYPE_SSS_QUESTION_ACTIVE);
            }
            else if ($contentType === 'comments'){
                $roql = sprintf("SELECT SocialQuestionComments.CreatedBySocialUser, Count(SocialQuestionComments.ID) as Count FROM SocialQuestion WHERE " . ($timePeriod ? "CreatedTime > '" . $timePeriod . "' AND " : null) . "SocialQuestionComments.ParentCreatedBySocialUser.StatusWithType.StatusType = %d AND SocialQuestionComments.StatusWithType.StatusType = %d AND StatusWithType.StatusType = %d GROUP BY SocialQuestionComments.CreatedBySocialUser ORDER BY Count(SocialQuestionComments.ID) DESC LIMIT " . $count, STATUS_TYPE_SSS_USER_ACTIVE, STATUS_TYPE_SSS_COMMENT_ACTIVE, STATUS_TYPE_SSS_QUESTION_ACTIVE);
            }
            else {
                $roql = sprintf("SELECT BestSocialQuestionAnswers.BestSocialQuestionAnswerList.ParentSocialQuestionComment.CreatedBySocialUser as CreatedBySocialUser, COUNT(BestSocialQuestionAnswers.SocialQuestionComment) as Count FROM SocialQuestion WHERE " . ($timePeriod ? "CreatedTime > '" . $timePeriod . "' AND " : null) . "BestSocialQuestionAnswers.BestSocialQuestionAnswerList.ParentSocialQuestionComment.ParentCreatedBySocialUser.StatusWithType.StatusType = %d AND StatusWithType.StatusType = %d AND BestSocialQuestionAnswers.BestSocialQuestionAnswerList.ParentSocialQuestionComment.StatusWithType.StatusType = %d GROUP BY BestSocialQuestionAnswers.BestSocialQuestionAnswerList.ParentSocialQuestionComment.CreatedBySocialUser ORDER BY COUNT(BestSocialQuestionAnswers.SocialQuestionComment) DESC LIMIT " . $count, STATUS_TYPE_SSS_USER_ACTIVE, STATUS_TYPE_SSS_QUESTION_ACTIVE, STATUS_TYPE_SSS_COMMENT_ACTIVE);
            }
            $objects = Connect\ROQL::query($roql)->next();
            while($row = $objects->next()){
                $results[$row['CreatedBySocialUser']] = array('count' => $row['Count']);
            }
            $userList = array_keys($results);
            $userData = $this->getUsersByIDs($userList)->result;
            $users = array_replace_recursive($userData, $results);

            usort($users, function ($a, $b) {
                return ($a['count'] < $b['count']);
            });
        }
        catch(Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($users, 'is_array');
    }
    
    /**
     * Framing gravatar avatar url
     * @param type $emailId User Email
     * @return type string
     */
    public function getGravatarUrl($emailId){
        $gravatarUrl = sprintf('https://www.gravatar.com/avatar/%s?d=404&s=256', md5(strtolower(trim($emailId))));
        return $gravatarUrl;
    }
}
