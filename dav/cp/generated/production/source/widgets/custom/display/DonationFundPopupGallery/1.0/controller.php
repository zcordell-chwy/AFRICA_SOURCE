<?php
namespace Custom\Widgets\display;

class DonationFundPopupGallery extends \Custom\Widgets\display\ItemPopupGallery {
    protected static $WIDGET_SCOPE = 'custom/display/DonationFundPopupGallery';

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->CI->load->library('Logging');
    }

    function getData() {
        return parent::getData();
    }

    /**
     * Gets donation fund metadata for a given page.
     *
     * @param integer $page The page in the gallery to get item metadata for.
     * @param bool $doPreload True if item popup data should be preloaded by this routine, false otherwise.
     */
    protected function getItemMetadataForPage($page, $doPreload = false){
        $this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'getItemMetadataForPage', array('$page' => $page, '$doPreload' => $doPreload));
        
        // Get donation fund items
        $this->CI->load->model('custom/items');
        $donationFundItems = $this->CI ->model('custom/items')->getDonationItems();

        logMessage('Building donation fund gallery items...');
        // Build donation fund gallery items, use a sequence for the ID since we're mixing donation items with missionary members and there 
        // would likely be ID collisions if we just used the ID from the database. Besides, we don't need the item ID or missionary ID to
        // handle the donation.
        $items = array();
        $itemIDSequence = 1;
        foreach($donationFundItems as $donationFundItem){
            $items[] = array(
                'id' => $itemIDSequence++,
                'data' => array(
                    'title' => $donationFundItem->Title,
                    'description' => $donationFundItem->Description,
                    'photoURL' => $this->isValidPhoto($donationFundItem->PhotoURL) ? str_replace('http://', 'https://', $donationFundItem->PhotoURL) : null,
                    'donationFundID' => $donationFundItem->DonationFund,
                    'donationAppealID' => $donationFundItem->DonationAppeal,
                    'type' => 'fund'
                )
            );
        }

        logMessage('Building missionary list gallery item...');
        // Add one additional item to capture all of the mission team members
        $missionaries = $this->CI->model('custom/sponsor_model')->searchwidget("");
        $missionaryList = array();
        foreach($missionaries as $missionary){
            if(is_null($missionary->DisplayName)) continue;
            $missionaryList[] = array(
                'title' => $missionary->DisplayName,
                'donationFundID' => $missionary->Fund,
                'donationAppealID' => $missionary->Appeal
            );
        }

        $missionTeamMembersFundItem = array(
            'id' => $itemIDSequence++,
            'data' => array(
                'title' => getMessage(CUSTOM_MSG_cp_DonationFundPopupGallery_missionary_item_title),
                'description' => getMessage(CUSTOM_MSG_cp_DonationFundPopupGallery_missionary_item_description),
                'photoURL' => $this->isValidPhoto($this->data['attrs']['mission_members_photo_url']) ? $this->data['attrs']['mission_members_photo_url'] : null,
                'missionaries' => $missionaryList,
                'type' => 'missionaryList'
            )
        );
        $this->CI->logging->logVar('$this->data[\'attrs\'][\'mission_members_sort_index\']', 
            $this->data['attrs']['mission_members_sort_index']);
        // if($this->data['attrs']['mission_members_sort_index'] == -1){
        //     $items[] = $missionTeamMembersFundItem;
        // }else{
        //     $sortIndex = intval($this->data['attrs']['mission_members_sort_index']);
        //     array_splice($items, $sortIndex, 0, array($missionTeamMembersFundItem));
        // }

        $this->CI->logging->logVar('$items', $items);
        $this->CI->logging->logVar('$this->data[\'attrs\']', $this->data['attrs']);
        $itemsPerPage = $this->data['attrs']['columns'] * $this->data['attrs']['rows'];

        $metaData['items'] = $this->filterItemsByPage($items, $page, $itemsPerPage, $totalPages);
        $metaData['page'] = $page;
        $metaData['totalPages'] = $totalPages;

        $this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'getItemMetadataForPage', $metaData, '$metaData');
        return $metaData;
    }

    /**
    * Validates a photo resource URL but checking to see if the file exists on the server.
    * @param string $photoURL the URL of the photo resource on the server
    */
    private function isValidPhoto($photoURL){
        $this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'isValidPhoto', array('$photoURL' => $photoURL));
        
        if(empty($photoURL)){
            $this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'isValidPhoto', false);
            return false;
        }

        $photoPathIndex = strpos($photoURL, '/euf/assets/');
        $this->CI->logging->logVar('$photoPathIndex', $photoPathIndex);
        if($photoPathIndex === false){
            $this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'isValidPhoto', false);
            return false;
        }
        $photoPath = HTMLROOT . substr($photoURL, $photoPathIndex);
        $this->CI->logging->logVar('$photoPath', $photoPath);
        $photoFileExists = file_exists($photoPath);

        $this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'isValidPhoto', $photoFileExists);
        return $photoFileExists;
    }
}