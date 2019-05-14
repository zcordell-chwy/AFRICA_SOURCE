<?php /* Originating Release: February 2019 */
namespace RightNow\Models;

use RightNow\Connect\v1_3 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\ConnectTabular,
    RightNow\Utils\Config,
    RightNow\Utils\Framework;

/**
 * Methods for retrieving social comments (collections of comments)
 */
class SocialComment extends SocialObjectBase
{
    /**
     * Instantiates the model, optionally accepting a concrete class name
     *
     * @param string $className Name of the class to instantiate.  Defaults to SocialQuestionComment.
     */
    function __construct($className = 'SocialQuestionComment') {
        parent::__construct($className ?: 'SocialQuestionComment');
    }

    /**
     * Returns an empty comment.
     *
     * @return Connect\SocialComment An instance of the Connect comment object
     */
    public function getBlank()
    {
        return $this->getResponseObject(parent::getBlank());
    }

    /**
    * Returns a SocialComment middle layer object from the database based on the comment id.
     *
     * @param int|null $commentID The id of the comment to retrieve.
     * @return Connect\SocialComment An instance of the Connect comment object
     */
    public function get($commentID)
    {
        $comment = parent::get($commentID);
        if(!is_object($comment)){
            return $this->getResponseObject(null, null, $comment);
        }
        if($comment->SocialQuestion->Interface->ID !== $this->interfaceID){
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_COMMENT_LBL));
        }
        \RightNow\Libraries\Decorator::add($comment, array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));

        if ($comment->SocialPermissions->isDeleted()) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_FIND_DELETED_ANOTHER_USER_MSG));
        }

        // ensure the user has permission to view this object
        if (!$comment->SocialPermissions->canRead()) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_COMMENT_LBL));
        }

        return $this->getResponseObject($comment);
    }

    /**
     * Returns a tabular SocialComment from the process cache.
     * @param int $commentID The id of the comment to retrieve.
     * @param boolean $checkCommentReadPermission Flag to indicate whether read permission to be checked or not.
     * @return array Tabular comment data
     */
    public function getTabular($commentID, $checkCommentReadPermission = true) {
        if ($checkCommentReadPermission && ($tabularComment = $this->getCommentFromCache($commentID))) {
            return $this->getResponseObject($tabularComment, 'is_array');
        }

        // Deal with cache miss
        $roqlSelectFrom = $this->getCommentSelectROQL(sprintf('c.ID = %d', $commentID), 1);
        $query = ConnectTabular::query($roqlSelectFrom);
        
        //read permission check needs to be bypassed in question detail page where suspended comments are still shown
        if (($tabularComment = $query->getFirst(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions')))
            && (!$checkCommentReadPermission || $tabularComment->SocialPermissions->canRead())) {
            if($checkCommentReadPermission && ($user = $this->CI->Model('SocialUser')->get()->result)) {
                // Add rating and flagging info
                if($ratingResult = $this->getRatingsForComment($commentID, $user->ID)) {
                    $tabularComment = ConnectTabular::mergeQueryResults($tabularComment, $ratingResult);
                }
                if($flagResult = $this->getFlagsForComment($commentID, $user->ID)) {
                    $tabularComment = ConnectTabular::mergeQueryResults($tabularComment, $flagResult);
                }
                $this->addCommentToCache($tabularComment);
            }
            return $this->getResponseObject($tabularComment);
        }
        return $this->getResponseObject(null, null, $query->error);
    }

    /**
     * Returns a list of tabular SocialComments without ratings or flags.
     * @param array $comments List of IDs to request comment data for.
     * @return array List of comments, keyed off the comment IDs for easy look up
     */
    public function getFromList(array $comments) {
        foreach ($comments as $comment) {
            if (!Framework::isValidID($comment)) {
                return $this->getResponseObject(null, null, Config::getMessage(COMMENT_IDS_MUST_BE_INTEGERS_MSG));
            };
        }

        $roqlSelectFrom = $this->getCommentSelectROQL(sprintf('c.ID in (%s)', implode(',', $comments)));
        $query = ConnectTabular::query($roqlSelectFrom);

        if ($results = $query->getCollection()) {
            $fullComments = array();

            foreach ($results as $comment) {
                $fullComments[$comment->ID] = $comment;
            }

            return $this->getResponseObject($fullComments, 'is_array');
        }

        return $this->getResponseObject(null, null, $query->error);
    }

    /**
     * Creates an comment. In order to create a comment, a contact must be logged-in. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Comment.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param array $formData Form fields to update the comment with. In order to be created successfully, a contact must be logged in
     * @return Connect\SocialComment|null Created comment object or null if there are error messages and the comment wasn't created
     * @throws \Exception All thrown exceptions should be caught within this function
     */
    public function create(array $formData) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        $comment = $this->getBlank()->result;

        $contact = $this->getContact();
        if($contact->Disabled){
            // Disabled contacts can't create comments
            return $this->getResponseObject(null, null, Config::getMessage(SORRY_THERES_ACCT_PLS_CONT_SUPPORT_MSG));
        }

        // set social user for logged in contact and set author
        $comment->CreatedBySocialUser = $socialUser;

        $errors = $warnings = array();
        foreach ($formData as $name => $field) {
            if(!\RightNow\Utils\Text::beginsWith($name, 'SocialQuestionComment')){
                continue;
            }
            $fieldName = explode('.', $name);
            // since SocialComment is an abstract class, we have to replace the first element of the
            // array with the concrete class name specified in the constructor
            $fieldName[0] = $this->objectName;

            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (\Exception $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors) &&
                ($setFieldError = $this->setFieldValue($comment, $name, $field->value, $fieldMetaData->COM_type))) {
                   $errors[] = $setFieldError;
            }
        }

        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        \RightNow\Libraries\Decorator::add($comment, array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));

        try{
            // check the permission after the object is constructed so the check will have the data it needs
            if(!$comment->SocialPermissions->canCreate()) {
                throw new \Exception('User does not have permission to create a comment');
            }
            $comment = parent::createObject($comment, SRC2_EU_AAQ);
        }
        catch(\Exception $e){
            $comment = $e->getMessage();
        }

        if(!is_object($comment)){
            return $this->getResponseObject(null, null, $comment);
        }

        // touch question to have an accurate LastActivityTime
        $touchedQuestion = $this->CI->model('SocialQuestion')->touch($comment->SocialQuestion);
        if ($touchedQuestion->errors) {
            return $touchedQuestion;
        }

        return $this->getResponseObject($comment, 'is_object', null, $warnings);
    }

    /**
     * Updates the specified comment with the given form data. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Comment.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param int $commentID ID of the comment to update
     * @param array $formData Form fields to update the comment with
     * @return Connect\Comment|null Updated comment object or error messages if the comment wasn't updated
     * @throws \Exception If the user does not have appropriate permissions
     */
    public function update($commentID, array $formData) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        $comment = $this->get($commentID);
        if (!$comment->result) {
            // Error: return the ResponseObject
            return $comment;
        }
        $comment = $comment->result;

        $errors = $warnings = array();

        // the user must either be
        // - authorized to edit their own comment AND logged in as the comment's author
        // - authorized to edit other people's comments
        if (!$comment->SocialPermissions->canUpdate()) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_PERMISSION_EDIT_COMMENT_LBL));
        }

        foreach ($formData as $name => $field) {
            // trim any whitespace
            $field->value = trim($field->value);
            $fieldName = explode('.', $name);

            // permission required to update Status field, depending on the value
            // note that setting Status via LookupName only works on create, so we don't need to consider it here
            if (\RightNow\Utils\Text::endsWith($name, 'Status.ID') || \RightNow\Utils\Text::endsWith($name, 'Status')) {
                // get a list of the status IDs that mean Deleted
                $deletedStatuses = $this->getStatusesFromStatusType(STATUS_TYPE_SSS_COMMENT_DELETED)->result;

                // deletions need the delete permission, everything else needs the status change permission
                if (in_array($field->value, $deletedStatuses)) {
                    if (!$comment->SocialPermissions->canDelete()) {
                        throw new \Exception('User does not have permission to delete this comment');
                    }
                }
                else if(!$comment->SocialPermissions->canUpdateStatus()) {
                    throw new \Exception('User does not have permission to set this comment status ' . $field->value);
                }
            }

            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors) &&
                ($setFieldError = $this->setFieldValue($comment, $name, $field->value))) {
                   $errors[] = $setFieldError;
            }
        }
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        try{
            // remove the comment from cache
            $this->removeCommentFromCache($comment->ID);

            $comment = parent::updateObject($comment, SRC2_EU_MYSTUFF_Q);
        }
        catch(\Exception $e){
            $comment = $e->getMessage();
        }
        if(!is_object($comment)){
            return $this->getResponseObject(null, null, $comment);
        }
        return $this->getResponseObject($comment, 'is_object', null, $warnings);
    }

    /**
     * Retrieves a list of flags for the given comment
     * @param Connect\SocialQuestionComment|int $comment Comment instance or ID to flag
     * @param Connect\SocialUser $socialUser User for which to retrieve the flag.  Defaults to current user.
     * @return Connect\SocialQuestionCommentContentFlag|null content flag object for the given user
     */
    public function getUserFlag($comment, $socialUser = null) {
        if (!$socialUser || !($socialUser instanceof Connect\SocialUser)) {
            $socialUserResponseObject = $this->getSocialUser();
            if ($socialUserResponseObject->errors) {
                return $socialUserResponseObject;
            }
            $socialUser = $socialUserResponseObject->result;
        }

        if (is_object($commment)) {
            $comment = $comment->ID;
        }
        try {
            $roql = sprintf("SELECT SocialQuestionCommentContentFlag FROM SocialQuestionCommentContentFlag f WHERE f.SocialQuestionComment.ID = %d AND f.CreatedBySocialUser = %d", $comment, $socialUser->ID);

            // perform the query and gather the results
            $results = Connect\ROQL::queryObject($roql)->next();

            if ($results && ($result = $results->next())) {
                $flag = $result;
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($flag, $flag ? 'is_object' : 'is_null');
    }

    /**
     * Flags a comment as inappropriate, miscategorized, copyrighted, or other reasons
     * @param Connect\SocialQuestionComment|int $comment Comment instance or ID to flag
     * @param int|null $flagType Optional.  Indicates the reason for flagging, defaults to 1 which is "Inappropriate"
     * @return Connect\SocialQuestionCommentContentFlag The newly created content flag
     */
    public function flagComment($comment, $flagType = 1) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        // check for an invalid comment:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social comment
        if ($comment === null ||
            (!is_object($comment) && !Framework::isValidID($comment)) ||
            (is_object($comment) && !($comment instanceof Connect\SocialQuestionComment))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }
        $commentID = is_object($comment) ? $comment->ID : intval($comment);

        // we need the comment object so we can check its permissions
        if (!$comment = $this->get($commentID)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // explicitly check to see if the parent is deleted (at which point we would no longer show this comment in CP)
        if($comment->Parent->ID && $comment->Parent->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_DELETED){
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        try {
            // construct - but don't yet save - the flag object
            $flag = new Connect\SocialQuestionCommentContentFlag();
            $flag->SocialQuestionComment = $commentID;
            $flag->CreatedBySocialUser = $socialUser->ID;
            $flag->Type = intval($flagType);

            // ensure the user is permitted to flag the comment
            if (!$comment->SocialPermissions->canFlag()) {
                return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_PERMISSION_FLAG_COMMENT_LBL));
            }

            if ($existingFlag = $this->getUserFlag($commentID)->result) {
                // ensure the user is permitted to remove the existing flag
                if (!$comment->SocialPermissions->canDeleteFlag($existingFlag)) {
                    return $this->getResponseObject(null, null, Config::getMessage(DOES_PERMISSION_REMOVE_COMMENT_FLAG_LBL));
                }

                $existingFlag->destroy();
            }
            // remove the comment from cache
            $this->removeCommentFromCache($commentID);

            // finally it is OK to save the flag
            $flag->save();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($flag);
    }

    /**
     * Retrieves the comment count based on the product/category
     * @param string $filterType Filtertype can be product or category.
     * @param array $prodCatList List of products/categories for which comment count should be determined.
     * @return array|null If valid arguments are passed, list of array containing comment count is returned. Otherwise null is returned.
     */
    public function getCommentCountByProductCategory($filterType, array $prodCatList) {
        $result = array();
        try {
            $roql = sprintf("SELECT SocialQuestion.{$filterType}, Count(SocialQuestionComments.ID) FROM SocialQuestion WHERE SocialQuestion.{$filterType} IN (" . implode(',', $prodCatList) . ") AND Interface.ID = curInterface() AND SocialQuestionComments.StatusWithType.StatusType = %d AND SocialQuestion.StatusWithType.StatusType = %d GROUP BY {$filterType}", STATUS_TYPE_SSS_COMMENT_ACTIVE, STATUS_TYPE_SSS_QUESTION_ACTIVE);
            $firstLevelObjects = Connect\ROQL::query($roql)->next();
            while($row = $firstLevelObjects->next()){
                $result[$row[$filterType]] = $row['Count(SocialQuestionComments.ID)'];
            }
        }
        catch(Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Retrieves a list of ratings for the given comment
     * @param Connect\SocialQuestionComment|int $comment Comment instance or ID to flag
     * @param Connect\SocialUser $socialUser User for which to retrieve the rating.  Defaults to current user.
     * @return Connect\SocialQuestionCommentContentRating|null content rating object for the given user
     */
    public function getUserRating($comment, $socialUser = null) {
        if (!$socialUser || !($socialUser instanceof Connect\SocialUser)) {
            $socialUserResponseObject = $this->getSocialUser();
            if ($socialUserResponseObject->errors) {
                return $socialUserResponseObject;
            }
            $socialUser = $socialUserResponseObject->result;
        }

        if (!$comment || !($comment instanceof Connect\SocialQuestionComment)) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }

        try {
            $roql = sprintf("SELECT SocialQuestionCommentContentRating FROM SocialQuestionCommentContentRating r WHERE r.SocialQuestionComment = %d AND r.CreatedBySocialUser = %d", $comment->ID, $socialUser->ID);

            // perform the query and gather the results
            $results = Connect\ROQL::queryObject($roql)->next();

            if ($results && ($result = $results->next())) {
                $rating = $result;
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($rating);
    }

    /**
     * Resets the rating on a comment
     * @param Connect\SocialQuestionComment|int $comment Comment instance or ID to rate
     * @return array|null $ratingValue Indicates the rated value, range is from 1 to 100, inclusive
     */
    public function resetCommentRating ($comment) {
        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(false, 'is_bool', $abuseMessage);
        }        

        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }

        // check for an invalid comment:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social comment

        $isObject = is_object($comment);

        if ($comment === null ||
            (!$isObject && !Framework::isValidID($comment)) ||
            ($isObject && !($comment instanceof Connect\SocialQuestionComment) && !Framework::isValidID($comment->ID))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // we need the decorated comment object so we can check its permissions
        $comment = $isObject ? $this->get($comment->ID)->result : $this->get(intval($comment))->result;

        if (!$comment) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_COMMENT_LBL));
        }

        if ($existingRating = $this->getUserRating($comment)->result) {
            // ensure the user is permitted to remove the existing rating
            if (!$comment->SocialPermissions->canDeleteRating($existingRating)) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_REMOVE_RATING_LBL));
            }

            $returnData = $existingRating->RatingValue;

            if ($existingRating->destroy()) {
                $this->removeCommentFromCache($comment->ID);
                return $this->getResponseObject($returnData);
            }
        }

        return $this->getResponseObject(false, 'is_bool', Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG));
    }

    /**
     * Rates a comment on a scale from 1-100
     * @param Connect\SocialQuestionComment|int $comment Comment instance or ID to rate
     * @param int $ratingValue Indicates the rated value, range is from 1 to 100, inclusive
     * @param int $ratingWeight Indicates the relative weight of the rating, range is from 1 to 100, inclusive. Defaults to 100.
     * @return Connect\SocialQuestionCommentContentRating The newly created content rating
     */
    public function rateComment ($comment, $ratingValue, $ratingWeight = 100) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        // check for an invalid comment:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social comment
        if ($comment === null ||
            (!is_object($comment) && !Framework::isValidID($comment)) ||
            (is_object($comment) && !($comment instanceof Connect\SocialQuestionComment))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }
        $comment = is_object($comment) ? $comment : $this->get(intval($comment))->result;

        // we need the decorated comment object so we can check its permissions
        if (!$comment = $this->get($comment->ID)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // explicitly check to see if the parent is deleted (at which point we would no longer show this comment in CP)
        if($comment->Parent->ID && $comment->Parent->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_DELETED){
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // Enforce user can't vote on own comment
        if ($socialUser->ID === $comment->CreatedBySocialUser->ID) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_VOTE_ON_OWN_COMMENT_LBL));
        }

        // Check if user has rated
        if ($this->getUserRating($comment, $socialUser)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_HAS_RATED_ON_THE_CONTENT_LBL));
        }

        try {
            // construct - but don't yet save - the rating object
            $rating = new Connect\SocialQuestionCommentContentRating();
            $rating->SocialQuestionComment = $comment->ID;
            $rating->CreatedBySocialUser = $socialUser->ID;
            $rating->RatingValue = intval($ratingValue);
            $rating->RatingWeight = intval($ratingWeight);

            // ensure the user is permitted to rate the comment
            if (!$comment->SocialPermissions->canRate()) {
                return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_PERMISSION_RATE_COMMENT_LBL));
            }

            // remove the comment from cache
            $this->removeCommentFromCache($comment->ID);

            // finally it is OK to save the new rating
            $rating->save();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($rating);
    }

    /**
     * Fetches all the flag type IDs and their lookup names
     * @return Array Array of Flag IDs and Lookup Names
     */
    public function getFlagTypes () {
        return parent::getFlattenFlagTypes(Connect\SocialQuestionCommentContentFlag::getMetadata());
    }

    /**
     * Updates moderator action on the comment
     * @param int $commentID ID of the comment to update
     * @param array $data Action data to update the comment with
     * @return Connect\SocialQuestionComment SocialQuestionComment object on success else error
     */
    public function updateModeratorAction($commentID, array $data) {
        $socialObject = $this->update($commentID, $data);
        $objectMetadata = $this->getSocialObjectMetadataMapping('SocialComment')->result;
        if ($socialObject->result) {
            $response = $this->resetFlagsIfRequiredForThisAction($data['SocialQuestionComment.StatusWithType.Status.ID']->value, $commentID, 'SocialComment');
            if ($response->result) {
                //since reset flag is successful for this comment, so commit both restore and reset flag operations
                Connect\ConnectAPI::commit();
            }
            else if ($response === false) {
                //reset flag operation has failed
                $rollback = true;
            }
        }
        if ($rollback) {
            Connect\ConnectAPI::rollback();
            return $this->getResponseObject(null, 'is_null', sprintf($objectMetadata['validation_errors']['general_error'], $commentID));
        }
        return $socialObject;
    }

    /**
     * Returns the zero-based index of a comment. If the specified comment id
     * corresponds to a child (i.e. non-top-level) comment, then the index of
     * its top-level parent is returned. If the specified comment id is invalid,
     * the comment specified by the id doesn't exist, or is prohibited from displaying,
     * -1 is returned.
     * @param  int $commentID Comment ID
     * @return int Zero-based index of the specified comment or -1
     */
    public function getIndexOfTopLevelComment($commentID) {
        if (!Framework::isValidID($commentID)
            || !($comment = $this->getTabular($commentID, false)->result))
            return -1;

        $question = $this->CI->model('SocialQuestion')->get($comment->SocialQuestion->ID)->result;

        $sql = $this->buildSelectROQL('count() AS count', 'SocialQuestionComment c', array(
            "c.SocialQuestion = {$comment->SocialQuestion->ID}",
            "c.Parent IS NULL",
            $this->getCommentStatusTypeFilters($question),
            "c.ID <= {$commentID}",
        ));

        $query = ConnectTabular::query($sql);
        $result = $query->getFirst();

        return ((int) $result->count) - 1;
    }

    /**
     * Utility method to set the value on the Comment object. Handles more complex types such as comment entries
     * and file attachments.
     * @param Connect\RNObject $comment Current comment object that is being created/updated
     * @param string $fieldName Name of the field we're setting
     * @param mixed $fieldValue Value of the field.
     * @param string $fieldType Common object model field type
     * @return null|string Returns null upon success or an error message from Connect::setFieldValue upon error.
     */
    protected function setFieldValue(Connect\RNObject $comment, $fieldName, $fieldValue, $fieldType = null){
        if($fieldType === 'Comment'){
            $this->createCommentEntry($comment, $fieldValue);
        }
        else if($fieldType === 'FileAttachmentComment'){
            $this->createAttachmentEntry($comment, $fieldValue);
        }
        else{
            if (strtolower($fieldName) === 'socialquestioncomment.body') {
                // All comments submitted from CP are markdown.
                $contentType = new Connect\NamedIDOptList();
                $contentType->LookupName = 'text/x-markdown';
                parent::setFieldValue($comment, 'SocialQuestionComment.BodyContentType', $contentType);
            }
            return parent::setFieldValue($comment, $fieldName, $fieldValue);
        }
    }

    /**
     * Utility function to create a thread entry object with the specified value. Additionally sets
     * values for the entry type and channel of the thread.
     * @param Connect\Comment $comment Current comment object that is being created/updated
     * @param string $value Comment value
     */
    protected function createCommentEntry(Connect\Comment $comment, $value){
        if($value !== null && $value !== false && $value !== ''){
            $comment = $comment->Comments->fetch(CONNECT_DISCUSSION_ENDUSER);
            $comment->Comments = new Connect\CommentArray();
            $comment = $comment->Comments[] = new Connect\Comment();
            $comment->Body = $value;
            $comment->CreatedBySocialUser = $this->CI->model('SocialUser')->get()->result;
        }
    }
}
