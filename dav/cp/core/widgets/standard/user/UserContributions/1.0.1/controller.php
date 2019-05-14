<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Connect\v1_2 as Connect;

class UserContributions extends \RightNow\Libraries\Widget\Base {
    /**
     * Widget constructor
     */
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    /**
     * Populate widget data
     */
    function getData() {
        $userID = \RightNow\Utils\Url::getParameter('user');
        if (!\RightNow\Utils\Framework::isValidID($userID)) {
            return false;
        }
        $this->data['userID'] = (int) $userID;

        $types = $this->data['attrs']['contribution_types'];
        if (!is_array($types)) {
            $types = explode(',', $types);
        }

        $this->data['contributions'] = array();
        foreach($types as $type) {
            $this->data['contributions'][$type] = $this->getContributions($type);
        }

        if (!$this->data['contributions']) {
            return false;
        }

    }

    /**
     * Constructs an array of contribution data
     * @param string $type One of the specified contribution types
     * @return array An associated array of contribution data
     */
    function getContributions($type) {
        return array(
            'label' => $this->data['attrs']["label_{$type}"],
            'count' => $this->getCount($type)
        );
    }

    /**
     * Calculates the user's total contribution count for the specified type.
     * Uses $this->{$type}Count() function if exists, else returns 0.
     * @param string $type One of the specified contribution types
     * @return int The total contribution count for $type
     */
    function getCount($type) {
        $method = "{$type}Count";
        return method_exists($this, $method) ? $this->$method() : 0;
    }

    /**
     * Calculates total questions count
     * @return int The total questions count
     */
    protected function questionsCount() {
        $questions = $this->getRoqlResults('SocialQuestion');
        $count = 0;
        while($question = $questions->next()) {
            $count++;
        }

        return $count;
    }

    /**
     * Calculates the total number of comments created by the specified user that were designated as the best answer.
     * @return int The total answers count
     */
    protected function answersCount() {
        list(, $count) = $this->getCommentAndBestAnswerCounts();

        return $count;
    }

    /**
     * Calculates total comments count
     * @return int The total comments count
     */
    protected function commentsCount() {
        list($count, ) = $this->getCommentAndBestAnswerCounts();

        return $count;
    }

    /**
     * Retrieve results from ROQL for 'SocialQuestion' or 'SocialQuestionComment'.
     * This is temporary until we either not limit results from the SocialUserActivity model or can get this from the KFAPI.
     * @param string $objectName One of 'SocialQuestion' or 'SocialQuestionComment'
     * @return object ROQL results
     */
    private function getRoqlResults($objectName = 'SocialQuestion') {
        return Connect\ROQL::queryObject(sprintf(
            "SELECT $objectName FROM $objectName WHERE CreatedBySocialUser = %d
            AND {$objectName}%s.Interface.ID = %d AND StatusWithType.StatusType NOT IN (%s) ORDER BY ID",
            $this->data['userID'],
            ($objectName === 'SocialQuestionComment' ? '.ParentSocialQuestion' : ''),
            \RightNow\Api::intf_id(),
            implode(',', $this->getExcludedStatuses($objectName))
        ))->next();
    }

    /**
     * Returns excluded status types for 'SocialQuestion' or 'SocialQuestionComment'
     * @param string $objectName One of 'SocialQuestion' or 'SocialQuestionComment'
     * @return array An array of excluded status types
     */
    private function getExcludedStatuses($objectName = 'SocialQuestion') {
        static $statuses;

        $statuses = $statuses ?: array(
            'SocialQuestion'        => array(STATUS_TYPE_SSS_QUESTION_SUSPENDED, STATUS_TYPE_SSS_QUESTION_DELETED, STATUS_TYPE_SSS_QUESTION_PENDING),
            'SocialQuestionComment' => array(STATUS_TYPE_SSS_COMMENT_SUSPENDED, STATUS_TYPE_SSS_COMMENT_DELETED, STATUS_TYPE_SSS_COMMENT_PENDING),
        );

        return $statuses[$objectName];
    }

    /**
     * Calculates total comments and best answer count
     * This is temporary until we either not limit results from the SocialUserActivity model or can get this from the KFAPI.
     * @return array A two element array containing the comment and answer total
     */
    private function getCommentAndBestAnswerCounts() {
        static $totals;
        if (!$totals) {
            $comments = $this->getRoqlResults('SocialQuestionComment');
            $commentCount = $bestAnswerCount = 0;
            while ($comment = $comments->next()) {
                if (!in_array($comment->SocialQuestion->StatusWithType->StatusType->ID, $this->getExcludedStatuses('SocialQuestion'))
                    && !in_array($comment->Parent->StatusWithType->StatusType->ID, $this->getExcludedStatuses('SocialQuestionComment'))) {
                    $commentCount++;
                    $bestAnswerCount += $this->getBestAnswerCount($comment);
                }
            }
            $totals = array($commentCount, $bestAnswerCount);
        }

        return $totals;
    }

    /**
     * Calculates the best answer count from $comment
     * This is temporary until we either not limit results from the SocialUserActivity model or can get this from the KFAPI.
     * @param object $comment A SocialQuestionComment object
     * @return integer The number of best answers associated with $comment and the specified user ID
     */
    private function getBestAnswerCount($comment) {
        $count = 0;
        foreach($comment->SocialQuestion->BestSocialQuestionAnswers ?: array() as $bestAnswer) {
            if ($comment->ID === $bestAnswer->SocialQuestionComment->ID
                && $this->data['userID'] === $bestAnswer->SocialQuestionComment->CreatedBySocialUser->ID
                && !in_array($bestAnswer->SocialQuestionComment->StatusWithType->StatusType->ID, $this->getExcludedStatuses('SocialQuestionComment'))) {
                    $count++;
            }
        }

        return $count;
    }
}