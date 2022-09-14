<?php /* Originating Release: February 2019 */


namespace RightNow\Widgets;

use RightNow\Utils\Framework,
    RightNow\Utils\Url;

class DiscussionSubscriptionIcon extends \RightNow\Libraries\Widget\Base {

    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);

        switch ($this->data['attrs']['subscription_type']) {
            case 'Question':
                $socialQuestionID = Url::getParameter('qid');

                // Get the qid from URL and fetch the social Question, return false if it does not exist or is not active
                if (!($socialQuestion = $this->CI->model('SocialQuestion')->get($socialQuestionID)->result)
                    || !$socialQuestion->SocialPermissions->isActive()) {
                    return false;
                }

                // Fetch the subscription ID
                if(Framework::isSocialUser()) {
                    $userID = $this->CI->session->getProfileData('socialUserID');
                    $this->data['subscriptionID'] = $this->CI->model('SocialSubscription')->getSubscriptionID($socialQuestionID, $userID, "Question")->result;
                    $this->data['js']['prodSubscriptionID'] = $this->CI->model('SocialSubscription')->getSubscriptionID($socialQuestion->Product->ID, $userID, "Product")->result;
                    $this->data['js']['catSubscriptionID'] = $this->CI->model('SocialSubscription')->getSubscriptionID($socialQuestion->Category->ID, $userID, "Category")->result;

                    // reading product name and id for subscribe to <product name> link
                    $this->data['productName'] = $socialQuestion->Product->Name;
                    $this->data['productID'] = $socialQuestion->Product->ID;

                    // reading category name and id for subscribe to <category name> link
                    $this->data['categoryName'] = $socialQuestion->Category->Name;
                    $this->data['categoryID'] = $socialQuestion->Category->ID;
                }

                // Set up constants.
                $this->data['js']['objectID'] = $socialQuestionID;
                $this->data['js']['activeStatusWithTypeID'] = STATUS_TYPE_SSS_QUESTION_ACTIVE;
                break;

            case 'Product':
                $productID = Url::getParameter('p');
                if ($this->_setProductCategoryDetails($productID, "Product") === false)
                    return false;
                break;

            case 'Category':
                $categoryID = Url::getParameter('c');
                if ($this->_setProductCategoryDetails($categoryID, "Category") === false)
                    return false;
                break;
        }

        if (!$this->CI->model('SocialSubscription')->isValidObject($this->data['js']['objectID'], $this->data['attrs']['subscription_type'])->result)
            return false;
    }

    /**
     * This method will be used to set 'subscriptionID' and 'objectID' for respective product or category
     * @param int $id A product or category id
     * @param string $subscriptionType Type of subscription ("Product" or "Category")
     * @return boolean Returns false if no product or category data is found; else returns true
     */
    private function _setProductCategoryDetails($id, $subscriptionType) {
        if (!($this->CI->model('Prodcat')->get($id)->result && $this->CI->model('Prodcat')->isEnduserVisible($id))) {
            return false;
        }

        if(Framework::isSocialUser()) {
            $userID = $this->CI->session->getProfileData('socialUserID');
            $this->data['subscriptionID'] = $this->CI->model('SocialSubscription')->getSubscriptionID($id, $userID, $subscriptionType)->result;
        }

        // Set up constant.
        $this->data['js']['objectID'] = $id;
        return true;
    }
}