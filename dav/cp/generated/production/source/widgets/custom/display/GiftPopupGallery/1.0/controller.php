<?php
namespace Custom\Widgets\display;

class GiftPopupGallery extends \Custom\Widgets\display\ItemPopupGallery {
    protected static $WIDGET_SCOPE = 'custom/display/GiftPopupGallery';

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->CI->load->library('Logging');
    }

    function getData(){
        return parent::getData();

    }

    /**
     * Gets gift metadata for a given page.
     *
     * @param integer $page The page in the gallery to get item metadata for.
     * @param bool $doPreload True if item popup data should be preloaded by this routine, false otherwise.
     */
    protected function getItemMetadataForPage($page, $doPreload = false){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'getItemMetadataForPage',  array('$page' => $page, '$doPreload' => $doPreload));

        // Get gifts
        $this->CI->load->model('custom/items');
        $gifts = $this->CI->items->getGiftItems();

        // Get sponsored children for logged in contact
        $this->CI->load->model('custom/sponsor_model');
        $profile = $this->CI->session->getProfile();
        $children = $this->CI->sponsor_model->getSponsoredChildren($profile->c_id->value);

        // Build gift gallery item data
        $items = array();
        foreach($gifts as $gift){
            $childrenEligibleForGift = $this->getChildrenEligibleForGift($children, $gift);
            $items[] = array(
                'id' => $gift->ID,
                'data' => array(
                    'title' => $gift->Title,
                    'description' => $gift->Description,
                    'amount' => $gift->Amount,
                    'photoURL' => str_replace('http://', 'https://', $gift->PhotoURL),
                    'eligibleChildren' => $childrenEligibleForGift
                )
            );
        }

        //$this->CI->logging->logVar('$items', $items);
        //$this->CI->logging->logVar('$this->data[\'attrs\']', $this->data['attrs']);
        $itemsPerPage = $this->data['attrs']['columns'] * $this->data['attrs']['rows'];

        $metaData['items'] = $this->filterItemsByPage($items, $page, $itemsPerPage, $totalPages);
        $metaData['page'] = $page;
        $metaData['totalPages'] = $totalPages;

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'getItemMetadataForPage', $metaData, '$metaData');
        return $metaData;
    }

    /**
    * Searches an array of children and returns the children eligible to receive a particular gift.
    * 
    * @param array $children The array of children to search
    * @param object $gift The gift in question
    */
    private function getChildrenEligibleForGift($children, $gift){
        $eligibleChildren = array();

        foreach($children as $child){
            if(!empty($child->ExcludedItems) && in_array($gift->ID, $child->ExcludedItems)) continue;
            $eligibleChildren[] = array(
                'id' => $child->ID,
                'name' => $child->ID == 8793 ? $child->GivenName : $child->FullName,
                'imgURL' => $child->imageLocation
            );
        }

        return $eligibleChildren;
    }
}