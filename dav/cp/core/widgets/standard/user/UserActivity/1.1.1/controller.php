<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class UserActivity extends \RightNow\Libraries\Widget\Base {
    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        if (!$user = $this->getUser()) return false;

        $activityType = $this->data['attrs']['type'];

        $this->setDataForActivities($user);

        $this->data['user'] = $user;
    }

    /**
     * Retrieves and sets the activity for activity types.
     * @param object $user SocialUser to retrieve the data for
     */
    function setDataForActivities ($user) {
        $allActivities = $activityOrdering = array();

        foreach ($this->data['attrs']['type'] as $activityType) {
            $activity = $this->getDataForType($activityType, $user);
            $startIndex = count($allActivities);

            foreach ($activity as $action) {
                $activityOrdering []= array(
                    'id'    => $action->ID,
                    'index' => $startIndex++,
                    'type'  => $activityType,
                    'date'  => $this->getDate($activityType, $action),
                );
            }
            $allActivities = array_merge($allActivities, $activity);
        }

        usort($activityOrdering, function ($a, $b) {
            if ($a['date'] < $b['date']) return 1;
            if ($a['date'] > $b['date']) return -1;
            return 0;
        });

        $this->data['activityOrdering'] = $this->limitDataSize($activityOrdering);
        $this->data['activity'] = $allActivities;
    }

    /**
     * Calls the SocialUserActivity model to retrieve
     * the activity data of the specified type.
     * @param  string $type One of the `type` attr values
     * @param  object $user SocialUser object
     * @return array       Activity data
     * @throws \Exception If the specified type isn't a valid `type` attribute value
     */
    function getDataForType ($type, $user) {
        static $methods = array(
            'question'                   => 'getQuestions',
            'comment'                    => 'getComments',
            'bestAnswerGivenByUser'      => 'getBestAnswersVotedByUser',
            'bestAnswerGivenToUser'      => 'getBestAnswersAuthoredByUser',
            'commentRatingGivenByUser'   => 'getCommentRatingsGivenByUser',
            'commentRatingGivenToUser'   => 'getCommentRatingsGivenToUser',
            'questionRatingGivenByUser'  => 'getQuestionRatingsGivenByUser',
            'questionRatingGivenToUser'  => 'getQuestionRatingsGivenToUser',
        );

        $method = $methods[$type];

        if (is_null($method)) throw new \Exception("No implementation for type $type");

        return $this->CI->model('SocialUserActivity')->$method($user)->result;
    }

    /**
     * Gets the user specified in the following order:
     *
     * 1. `user_id` widget attribute.
     * 2. `user` URL parameter.
     *
     * @return Object|null SocialUser object, if found
     */
    function getUser () {
        $userID = $this->data['attrs']['user_id'] ?: \RightNow\Utils\Url::getParameter('user');

        if (!\RightNow\Utils\Framework::isValidID($userID)) return;

        $response = $this->CI->model('SocialUser')->get($userID);

        if ($response->errors) {
            $this->reportError($response->errors[0]->externalMessage, false);
        }

        return $response->result;
    }

    /**
     * Gets the appropriate date for the given activity
     * @param  string $activityType One of the `type` attr values
     * @param  object $action Specific activity
     * @return string The correct date
     */
    function getDate ($activityType, $action) {
        $ratingTypes = array(
            'commentRatingGivenByUser',
            'commentRatingGivenToUser',
            'questionRatingGivenToUser',
            'questionRatingGivenByUser'
        );

        return in_array($activityType, $ratingTypes) ? $action->UserRating->CreatedTime : $action->CreatedTime;
    }

    /**
     * Truncates the supplied array if its size exceeds
     * the `limit` attribute value.
     * @param  array $dataset Array to consider
     * @return array          $dataset either untouched or
     *                                 truncated if its size
     *                                 exceeds the `limit` attribute
     */
    protected function limitDataSize (array $dataset) {
        if (count($dataset) > $this->data['attrs']['limit']) {
            $dataset = array_slice($dataset, 0, $this->data['attrs']['limit']);
        }

        return $dataset;
    }
}
