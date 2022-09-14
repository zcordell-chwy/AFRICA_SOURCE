<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Utils\Connect,
    RightNow\Libraries\Formatter;

class QuestionComments extends \RightNow\Libraries\Widget\Base {
    private $bestAnswerTypes;
    private $commentStatus;
    public $question = null;

    function __construct($attrs){
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'new_comment_ajax' => array(
                'method' => 'newComment',
                'clickstream' => 'comment_create',
                'login_required' => true,
            ),
            'delete_comment_ajax' => array(
                'method' => 'delete',
                'clickstream' => 'comment_delete',
                'login_required' => true,
            ),
            'edit_comment_ajax' => array(
                'method' => 'edit',
                'clickstream' => 'comment_update',
                'login_required' => true,
            ),
            'reply_to_comment_ajax' => array(
                'method' => 'reply',
                'clickstream' => 'comment_reply',
                'login_required' => true,
            ),
            'best_answer_ajax' => array(
                'method' => 'markBestAnswer',
                'clickstream' => 'comment_mark_best',
                'login_required' => true,
            ),
            'paginate_comments_ajax' => array(
                'method'             => 'renderCommentPage'
            ),
            'fetch_page_with_comment_ajax' => array(
                'method'             => 'renderPageWithComment',
            ),
            'check_comment_exists_ajax' => array(
                'method'           => 'checkCommentExists'
            ),
        ));
        $this->bestAnswerTypes = array(
            'author'    => SSS_BEST_ANSWER_AUTHOR,
            'moderator' => SSS_BEST_ANSWER_MODERATOR,
            'community' => SSS_BEST_ANSWER_COMMUNITY,
        );
    }

    function getData() {
        $this->data['js']['questionID'] = $this->data['questionID'] = intval(\RightNow\Utils\Url::getParameter('qid'));

        if (!$this->question = $this->CI->model('SocialQuestion')->get($this->data['js']['questionID'])->result) return false;

        $this->helper->question = $this->question;
        $this->helper->bestAnswerTypes = $this->bestAnswerTypes;

        if (\RightNow\Utils\Framework::isLoggedIn()) {
            $this->data['isLoggedIn'] = true;
            if(\RightNow\Utils\Framework::isSocialUser()){
                $this->data['socialUser'] = $this->CI->model('SocialUser')->get()->result;
                $this->data['js']['displayName'] = $this->data['socialUser']->DisplayName;
                $this->data['js']['bestAnswerTypes'] = $this->bestAnswerTypes;
            }
        }
        $this->highlightContent();
        $this->fetchPaginatedComments();
    }

    /**
     * Handles the new comment AJAX request. Echos JSON or HTML depending upon the
     * request's Accept header
     * @param array $params Post parameters
     */
    function newComment(array $params) {
        $this->highlightContent();
        if (!$this->verifyID('SocialQuestion', $params['questionID'])) {
            return;
        }

        \RightNow\Libraries\AbuseDetection::check();
        $createOperation = $this->CI->model('SocialComment')->create(array(
            'SocialQuestionComment.Body' => (object) array('value' => $params['commentBody']),
            'SocialQuestionComment.SocialQuestion' => (object) array('value' => $params['questionID']),
        ));

        if($createOperation->errors) {
            $response = $createOperation->toJson();
            header('Content-type: application/json');
            header('Content-Length: ' . strlen($response));
            echo $response;
            return;
        }

        if ($comment = $createOperation->result) {
            $params['commentID'] = $createOperation->result->ID;
            return $this->renderPageWithComment($params);
        }

        echo \RightNow\Utils\Config::getMessage(UNABLE_SAVE_YOUR_COMMENT_AT_THIS_TIME_LBL);
    }

    /**
     * Handles the delete comment AJAX request. Echos JSON representation of the comment
     * @param array $params Post parameters
     */
    function delete(array $params) {
        // get the first status with status type = deleted
        $statusResults = $this->CI->model('SocialComment')->getSocialObjectStatuses(STATUS_TYPE_SSS_COMMENT_DELETED);
        if (!$statusResults->result) {
            echo $statusResults->toJson();
            return;
        }

        if (!$this->verifyID('SocialComment', $params['commentID'])) {
            return;
        }

        echo $this->CI->model('SocialComment')->update($params['commentID'], array(
            'SocialQuestionComment.StatusWithType.Status.ID' => (object) array('value' => $statusResults->result[0]->Status->ID),
        ))->toJson();
    }

    /**
     * Handles the edit comment AJAX request. Echos JSON representation of the comment
     * @param array $params Post parameters
     */
    function edit(array $params) {
        if (!$this->verifyID('SocialComment', $params['commentID'])) {
            return;
        }
        $comment = $this->CI->model('SocialComment')->update($params['commentID'], array(
            'SocialQuestionComment.Body' => (object) array('value' => $params['commentBody']),
        ));

        if($comment->errors) {
            $response = $comment->toJson();
            header('Content-type: application/json');
            header('Content-Length: ' . strlen($response));
            echo $response;
            return;
        }

        echo json_encode(array("formattedUpdatedTime" => array($this->helper->formattedTimestamp($comment->result->UpdatedTime), $this->helper->formattedTimestamp($comment->result->UpdatedTime, true)), "comment" => json_decode($comment->toJson())));
    }

    /**
     * Handles the reply to comment AJAX request. Echos JSON or HTML depending upon
     * the request's Accept header
     * @param array $params Post parameters
     */
    function reply(array $params) {
        if (!$this->verifyID('SocialQuestion', $params['questionID']) || !$this->verifyID('SocialComment', $params['commentID'])) {
            return;
        }

        // reply to a suspended comment is not allowed. Even if user is author/moderator
        $comment = $this->CI->model('SocialComment')->get($params['commentID'])->result;
        if ($comment->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_SUSPENDED) {
            $response = new \RightNow\Libraries\ResponseObject(null);
            $response->error = $this->data['attrs']['label_reply_suspended_comment_error'];
            $this->echoJSON($response->toJson());
            return;
        }

        \RightNow\Libraries\AbuseDetection::check();
        $replyOperation = $this->CI->model('SocialComment')->create(array(
            'SocialQuestionComment.Body' => (object) array('value' => $params['commentBody']),
            'SocialQuestionComment.SocialQuestion' => (object) array('value' => $params['questionID']),
            'SocialQuestionComment.Parent' => (object) array('value' => $params['commentID']),
        ));
        $this->renderCommentResponse($replyOperation, $params);
    }

    /**
     * Handles the mark best answer AJAX request.
     * @param array $params Get / Post parameters
     */
    function markBestAnswer(array $params) {
        if(!in_array('none', $this->data['attrs']['best_answer_types']) && in_array(strtolower($params['chosenByType']), $this->data['attrs']['best_answer_types'])) {
            $chosenByType = $this->transformChosenByType($params['chosenByType']);
        }
        else {
            $response = new \RightNow\Libraries\ResponseObject(null);
            $response->error = $this->data['attrs']['label_best_answer_error'];
            $this->echoJSON($response->toJson());
            return;
        }

        if (!$this->verifyID('SocialComment', $params['commentID'])) {
            return;
        }

        $comment = $this->CI->model('SocialComment')->get($params['commentID'])->result;
        if ($comment->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_SUSPENDED) {
            $response = new \RightNow\Libraries\ResponseObject(null);
            $response->error = $this->data['attrs']['label_mark_suspended_comment_best_answer_error'];
            $this->echoJSON($response->toJson());
            return;
        }

        if($params['removeAnswer'] === "true") {
            $bestAnswers = $this->CI->model('SocialQuestion')->unmarkCommentAsBestAnswer($params['commentID'], $chosenByType);
        }
        else {
            $bestAnswers = $this->CI->model('SocialQuestion')->markCommentAsBestAnswer($params['commentID'], $chosenByType);
        }
        if(count($bestAnswers->errors) === 0) {
            $this->renderJSON($this->getSimplifiedBestAnswersList($bestAnswers->result));
        }
        else {
            $this->echoJSON($bestAnswers->toJson());
        }
    }

    /**
     * AJAX handler for fetching the page of comments
     * containing the specified comment id.
     * @param array $params POST params
     */
    function renderPageWithComment(array $params) {
        $params['pageID'] = $this->getPageNumberContainingComment($params['commentID']);
        return $this->renderCommentPage($params);
    }

    /**
     * Fetch comments for AJAX pagination requests. Echoes Text/HTML string containing pagination and comments.
     * @param array $params Post parameters
     */
    function renderCommentPage(array $params = array()) {
        $this->highlightContent();
        if(count($params)) {
            $this->data['socialUser'] = $this->CI->model('SocialUser')->get()->result;
            $this->helper->bestAnswerTypes = $this->bestAnswerTypes;
            $this->data['currentPage'] = intval($params['pageID']);
            $this->question = $this->CI->model('SocialQuestion')->get($params['questionID'])->result;

            $this->setPaginationVariablesAndFetchComments();
            $this->helper->question = $this->question;
            echo $this->render('CommentList', array('comments' => $this->data['comments'], 'questionID' => $params['questionID']));
        }
    }

    /**
     * Determines whether comment is deleted
     * @param object $comment Current comment data
     * @return Boolean Result
     */
    function isCommentDeleted($comment) {
        return ($comment->StatusWithType->ID === STATUS_TYPE_SSS_COMMENT_DELETED);
    }

    /**
     * Verifies the status of a comment and returns the corresponding error message, if any.
     * @param array $params Post parameters
     */
    function checkCommentExists(array $params){
        $comment = $this->CI->model('SocialComment')->get($params['commentID']);
        if($comment->errors){
            echo $comment->toJson();
            return;
        }
        echo true;
    }

    /**
     * Determines whether the given object is valid for the current user (in case the object has become invalid between page render and now).
     * Note that this function will echo any errors, so it is expected that the calling function will immediately return.
     * @param string $type Type of object matching the model to use, either SocialQuestion or SocialComment
     * @param string $ID Object ID
     * @return Boolean Whether the object is still valid for the current user
     */
    protected function verifyID($type, $ID) {
        $object = $this->CI->model($type)->get($ID);
        if (!$object->result) {
            $this->echoJSON($object->toJson());
            return false;
        }
        return true;
    }

    /*
    * Transform chosenByType into the appropriate best answer define
    * @param String|null chosenByType Type of user the best answer is being marked as ("Author", "Moderator", or null)
    * @return int|null Best answer define
    */
    protected function transformChosenByType($chosenByType = null) {
        if($chosenByType) {
            return ($chosenByType === "Moderator") ? SSS_BEST_ANSWER_MODERATOR : SSS_BEST_ANSWER_AUTHOR;
        }
    }

    /**
     * Renders the given comment. Outputs JSON or HTML
     * depending on the requested accept type.
     * @param \RightNow\Libraries\ResponseObject $commentOperation Response object from a comment operation
     * @param array $params Post parameters
     * @param \Closure|null $toHTML Callback to manipulate the extracted comment
     */
    protected function renderCommentResponse($commentOperation, $params, $toHTML = null) {
        if(!$commentOperation->errors && Text::stringContains($_SERVER['HTTP_ACCEPT'], 'text/html')) {
            if ($comment = $commentOperation->result) {
                if (is_callable($toHTML)) $comment = $toHTML($comment);
                $this->helper->question = $this->CI->model('SocialQuestion')->get($params['questionID'])->result;
                $response = $this->renderComment($comment);
            }
            else {
                $response = \RightNow\Utils\Config::getMessage(UNABLE_SAVE_YOUR_COMMENT_AT_THIS_TIME_LBL);
            }
            header('Content-type: text/html');
        }
        else {
            $response = $commentOperation->toJson();
            header('Content-type: application/json');
        }

        header('Content-Length: ' . strlen($response));
        echo $response;
    }

    /**
     * Sets the various instance properties the comment view
     * relies upon and renders the given Comment.
     * @param Object $comment Comment instance
     * @return String Rendered view
     */
    protected function renderComment($comment) {
        $this->highlightContent();
        $this->data['socialUser'] = $this->CI->model('SocialUser')->get()->result;
        return $this->render('Comment', array('comment' => $comment));
    }

    /**
     * Fetches a page of comments. Looks for a 'page' URL parameter first before attempting to find a specific comment for
     * the 'comment' URL parameter.
     */
    protected function fetchPaginatedComments() {
        if (($pageNumber = \RightNow\Utils\Url::getParameter('page')) && is_numeric($pageNumber)) {
            $this->data['currentPage'] = (int) $pageNumber;
        }
        else {
            $this->data['currentPage'] = $this->getPageNumberContainingComment(\RightNow\Utils\Url::getParameter('comment'));
        }

        $this->setPaginationVariablesAndFetchComments();
    }

    /**
     * Retrieves a page of comments for the given page.
     * @param int $forPage Page number
     * @return array Set of comments limited by comments_per_page size
     */
    protected function fetchComments($forPage) {
        return $this->CI->model('SocialQuestion')->getComments($this->question, $this->data['attrs']['comments_per_page'], $forPage)->result;
    }

    /**
     * Sets pagination variables and fetches comments.
     */
    protected function setPaginationVariablesAndFetchComments() {
        $topLevelCommentCount = $this->CI->model('SocialQuestion')->getTopLevelCommentCount($this->question);
        $this->data['displayPagination'] = false;

        if ($topLevelCommentCount < 1) {
            $this->data['currentPage'] = 1;
            $this->data['comments'] = array();
            return;
        }

        $this->data['comments'] = $this->fetchComments($this->data['currentPage']);

        if (!$this->data['comments']) {
            // There's no comments for the requested page. Default back to page 1.
            $this->data['currentPage'] = 1;
            $this->data['comments'] = $this->fetchComments($this->data['currentPage']);
        }

        if ($topLevelCommentCount > $this->data['attrs']['comments_per_page']) {
            $this->data['displayPagination'] = true;
            $this->data['endPage'] = intval(ceil($topLevelCommentCount / $this->data['attrs']['comments_per_page']));
        }
    }

    /**
     * Gets the page number that the specified comment id
     * appears on.
     * @param  int|string $commentID Comment id
     * @return int            The page number; if the comment could
     *                            not be found for whatever reason,
     *                            1 is still returned so as to not
     *                            return an invalid page number
     */
    protected function getPageNumberContainingComment($commentID) {
        $commentIndex = $this->CI->model('SocialComment')->getIndexOfTopLevelComment($commentID);

        return ($commentIndex >= 0) ? (int) floor($commentIndex / $this->data['attrs']['comments_per_page']) + 1 : 1;
    }

    /**
     * Processes the list of best answers into a simplified list that the views can more easily handle.
     * @param Connect\BestSocialQuestionAnswersArray $bestAnswers List of best answers for this question.
     * @return array Array of simplified comments with best answer types
     */
    protected function getSimplifiedBestAnswersList($bestAnswers) {
        $comments = array();
        $defaultTypes = array(
            $this->bestAnswerTypes['author'] => false,
            $this->bestAnswerTypes['moderator'] => false,
            $this->bestAnswerTypes['community'] => false,
        );

        if ($bestAnswers) {
            foreach ($bestAnswers as $answer) {
                $commentID = $answer->SocialQuestionComment->ID;

                if (!array_key_exists($commentID, $comments)) {
                    $comments[$commentID] = array(
                        'commentID' => $commentID,
                        'types' => $defaultTypes,
                    );
                }

                $comments[$commentID]['types'][$answer->BestAnswerType->ID] = true;
            }
            $this->helper->bestAnswerTypes = $this->bestAnswerTypes;

            foreach ($comments as &$bestAnswer) {
                $bestAnswer['label'] = $this->helper->getLabelForBestAnswerTypes($bestAnswer['types']);
            }
        }

        return $comments;
    }

    /**
     * Checks whether we should highlight the author of the comment.
     * If yes, data required for the same is prepared.
     */
    protected function highlightContent() {
        //Passing 0 disables the highlighting feature
        if($this->data['attrs']['author_roleset_callout']) {
            $this->data['author_roleset_callout'] = $this->helper('Social')->filterValidRoleSetIDs($this->data['attrs']['author_roleset_callout']);
            $this->data['author_roleset_styling'] = $this->helper('Social')->generateRoleSetStyles($this->data['author_roleset_callout']);
        }
    }
}
