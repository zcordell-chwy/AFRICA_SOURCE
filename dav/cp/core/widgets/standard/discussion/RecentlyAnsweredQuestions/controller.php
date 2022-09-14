<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class RecentlyAnsweredQuestions extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $questionList = $this->CI->model('SocialQuestion')->getRecentlyAskedQuestions($this->getFilters())->result;
        $bestAnswerComments = $this->data['js']['bestAnswerComments'] = $this->data['js']['questions'] = array();
        
        foreach ($questionList as $question) {
            if (!array_key_exists($question->ID, $this->data['js']['questions'])) {
                $question->bestAnswers = array();
                $this->data['js']['questions'][$question->ID] = $question;
            }

            // Question list will contain duplicate items that differ only by the type of best
            // answer attached onto each one. Keep a single question in the list but with
            // an array of best answer comments, keyed by their type.
            if($this->data['attrs']['questions_with_answers'] && $this->data['attrs']['display_answers'] &&
                $question->BestSocialQuestionAnswers->BestAnswerType && $bestAnswerCommentID = intval($question->BestSocialQuestionAnswers->SocialQuestionComment)) {
                $this->data['js']['questions'][$question->ID]->bestAnswers[$question->BestSocialQuestionAnswers->BestAnswerType] = $bestAnswerCommentID;
                $bestAnswerComments[] = $bestAnswerCommentID;
            }
        }

        if(count($bestAnswerComments) > 0) {
            $bestAnswerComments = array_unique(array_filter($bestAnswerComments));
            $this->data['js']['bestAnswerComments'] = $this->CI->model('SocialComment')->getFromList($bestAnswerComments)->result;
        }
    }

    protected function getFilters() {
        $filters = array(
            'maxQuestions'    => $this->data['attrs']['maximum_questions'],
            'includeChildren' => $this->data['attrs']['include_children'],
            'answerType'      => $this->data['attrs']['answer_type'],
            'questionsFilter' => ($this->data['attrs']['questions_with_answers'] === true) ? 'with' : 'without'
        );

        if ($this->data['attrs']['category_filter'])
            $filters['category'] = $this->data['attrs']['category_filter'];

        if ($this->data['attrs']['product_filter'])
            $filters['product'] = $this->data['attrs']['product_filter'];

        return $filters;
    }
}
