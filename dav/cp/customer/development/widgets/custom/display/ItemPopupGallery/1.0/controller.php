<?php
namespace Custom\Widgets\display;

class ItemPopupGallery extends \RightNow\Libraries\Widget\Base {
    protected static $WIDGET_SCOPE = 'custom/display/ItemPopupGallery';

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'getItemMetadataForPage' => array(
                'method'      => 'handle_getItemMetadataForPage',
                'clickstream' => 'custom_action',
            ),
            'getItemPopupDataByID' => array(
                'method'      => 'handle_getItemPopupDataByID',
                'clickstream' => 'custom_action',
            ),
        ));

        $this->CI->load->library('Logging');
    }

    function getData() {
        $doPreload = $this->data['attrs']['preload_item_popup_data'];
        $this->data['js']['itemMetadata'] = $this->getItemMetadataForPage(1, $doPreload);
        $this->data['js']['doPreload'] = $doPreload;
        $this->data['js']['rows'] = $this->data['attrs']['rows'];
        $this->data['js']['columns'] = $this->data['attrs']['columns'];
        $profile = $this->CI->session->getProfile();
        $this->data['js']['isLoggedIn'] =  ($profile->c_id->value > 0) ? true : false;

        return parent::getData();

    }

    /**
     * Handles the getItemMetadataForPage AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_getItemMetadataForPage($params){
        
        try{
            $page = intval($params['page']);
            if($page === 0){
                echo json_encode(
                    array(
                        'status' => 'error',
                        'msg' => "Invalid page ({$params['page']}) specified."
                    )
                );
            }else{
                $itemMetadata = $this->getItemMetadataForPage($page);
                $response = array(
                    'status' => 'success',
                    'data' => $itemMetadata
                );
                $this->writeToTmp(json_encode($response));
                echo json_encode($response);
            }
       }catch(\Exception $e) {
           
           $this->writeToTmp($e->getMessage());
       }catch(RNCPHP\ConnectAPIError $e) {
        
           $this->writeToTmp($e->getMessage());
       }
    }

    function writeToTmp($msg){
        if($msg != ""){
            $file = fopen("/tmp/metaDataLogging.txt","a");
            fwrite($file,date("Ymd h:i:s  :  ").$msg."\n");
            fclose($file);
        }
        
    }
    /**
     * Handles the getItemPopupDataByID AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_getItemPopupDataByID($params){
        $id = intval($params['id']);
        if($id === 0){
            echo json_encode(
                array(
                    'status' => 'error',
                    'msg' => "Invalid ID ({$params['id']}) specified."
                )
            );
        }else{
            $itemPopupData = $this->getItemPopupDataByID($id);
            $response = array(
                'status' => 'success',
                'data' => $itemPopupData
            );
            echo json_encode($response);
        }
    }

    /* Private member functions */

    /**
    * Returns the item popup data for a particular item ID. This function should be overridden by the inheriting widget that implements
    * a specific item gallery.
    *
    * NOTE: Popup data is any item data not already included by getItemsForPage when $doPreload is false.
    *
    * @param integer $id The ID of the item to retrieve popup data for.
    */
    protected function getItemPopupDataByID($id){
        $itemPopupData = array(
            'description' => 'Sample Item Description ' . $id
        );

        return $itemPopupData;
    }

    /**
    * Returns the item metadata for a particular page (starting from 1), which includes the item and pagination data for said page. This function
    * should be overridden by the inheriting widget that implements a specific item gallery.
    *
    * NOTE: Item data should be an array with 'id' and 'data' keys populated, as shown below. The value under 'data' should be an array
    * with a 'title' key at minimum. The value under 'title' will be used as the title of the popup dialog. If $doPreload is true, the data 
    * array should include all the popup data for the item as well.
    *
    * @param integer $page The page in the gallery to get item metadata for.
    * @param bool $doPreload True if item popup data should be preloaded by this routine, false otherwise.
    */
    protected function getItemMetadataForPage($page, $doPreload = false){
        try{
            $itemsTotal = $this->data['attrs']['rows'] * $this->data['attrs']['columns'];
            $itemsCnt = 0;
            $metaData = array();
            $items = array();
    
            while(++$itemsCnt <= $itemsTotal){
                $itemNumber = $itemsCnt + $itemsTotal * ($page - 1);
                if($doPreload){
                    $items[] = array(
                        'id' => $itemNumber,
                        'data' => array(
                            'title' => 'Sample Item ' . $itemNumber,
                            'description' => 'Sample Item Description ' . $itemNumber
                        )
                    );
                }else{
                    $items[] = array(
                        'id' => $itemNumber,
                        'data' => array(
                            'title' => 'Sample Item ' . $itemNumber
                        )
                    );
                }
            }
    
            $metaData['items'] = $items;
            $metaData['page'] = $page;
            $metaData['totalPages'] = 5;
    
            return $metaData;
        
        }catch(\Exception $e) {
           $this->writeToTmp($e->getMessage());
       }catch(RNCPHP\ConnectAPIError $e) {
           $this->writeToTmp($e->getMessage());
       }
    }

    /**
    * Returns the subset of the item array that should be displayed for the specified page. Also
    * populates $totalPages.
    * @param array $items the array of items
    * @param integer $page the specified page starting at 1
    * @param integer $itemsPerPage the number of items to display per page
    * @param integer $totalPages passed as reference, will be populated with the total page count on return
    */
    protected function filterItemsByPage($items, $page, $itemsPerPage, &$totalPages){
        $this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'filterItemsByPage', 
            array('$items' => $items, '$page' => $page, '$itemsPerPage' => $itemsPerPage));

        $totalPages = ceil(count($items) / $itemsPerPage);
        $this->CI->logging->logVar('$totalPages', $totalPages);
        $itemsOnPage = array_slice($items, $itemsPerPage * ($page - 1), $itemsPerPage);

        $this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'filterItemsByPage', $itemsOnPage, '$itemsOnPage');
        return $itemsOnPage;
    }
}