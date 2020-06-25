<?php
namespace Custom\Controllers;

use RightNow\Utils\Framework, RightNow\Libraries\AbuseDetection, RightNow\Utils\Config;

use RightNow\Connect\v1_3 as RNCPHP;
require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI('api_access', 'Password1');

error_reporting(E_ALL);



class AjaxCustom extends \RightNow\Controllers\Base {
    //This is the constructor for the custom controller. Do not modify anything within
    //this function.
    function __construct() {
        parent::__construct();

        $this -> load -> helper('constants');
        $this->load->library('logging');
        //$this->load->helper('cartHandling');
        //for whatever reason this causes the whole thing to fail.  moved
        //all the function to the end of this script


    }

    private $redirectCcChargeSuccessLocation = "/app/payment/successCC/";

    /**
     * AJAX wrapper for Custom/sponsorship_model::isChildSponsored method.
     * @param integer $id the ID of the child record to check for sponsorship
     * @return object {isSponsored: true/false}
     */
    public function isChildSponsored($id){
        $status = $this->model('custom/sponsorship_model')->isChildSponsored($id);
        echo json_encode($status);
    }

    /**
     * AJAX wrapper for Custom/sponsorship_model::isChildRecordLocked method.
     * @param integer $id the ID of the child record to check for a lock
     * @return object {isLocked: true/false, lastOwner: <contact ID of logged in user that last held lock>}
     */
    public function isChildRecordLocked($id){
        $status = $this->model('custom/sponsorship_model')->isChildRecordLocked($id);
        echo json_encode($status);
    }

    /**
     * AJAX wrapper for Custom/sponsorship_model::lockChildRecord method.
     * @param integer $id the ID of the child record to lock
     * @return object If locked successfully -> {status: 'success'}, else {status: 'failure'}
     */
    public function lockChildRecord($id){
        $status = $this->model('custom/sponsorship_model')->lockChildRecord($id);
        echo json_encode($status);
    }

    /****************************************************/
    /*
     * This is for manually running pledge from the my account section of CP.
     * Create Transaction, Donation given a pledge
     * Called from pledgepayment widget
     *
     * ZC Nov 24 2015
     */

    function runManualPayment() {

        echo json_encode("success");


    }

    function deletePayMethods() {
        AbuseDetection::check($this -> input -> post('f_tok'));
        $rawFormDataArr = json_decode($this -> input -> post('form'));

        if (!$rawFormDataArr) {
            header("HTTP/1.1 400 Bad Request");
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
        }


        $cleanFormArray = array();
        foreach ($rawFormDataArr as $rawData) {
            $cleanData = addslashes($rawData -> value);
            $cleanIndex = addslashes($rawData -> name);
            logMessage("name = " . $rawData -> name . "  checked = " . $rawData -> checked . " clean data = " . $cleanData . " clean index = " . $cleanIndex);
            if (($rawData -> name == "paymentMethodId" && $rawData -> checked == 1)) {
                logMessage("in clean array");
                $cleanFormArray[] = $cleanData;
            }
        }

        $this -> load -> model('custom/paymentMethod_model');
        foreach ($cleanFormArray as $index => $val) {
            $msg = $this -> paymentMethod_model -> deletePaymentMethod($val);
            if ($msg != 'success') {
                $error[] = $msg;
            }
        }

        //echo $this -> createResponseObject("Error updating payments ");
        echo $this -> createResponseObject("Success!", array(), "/app/paymentmethods/");
    }

    function deletePayMethod(){
            
        AbuseDetection::check($this -> input -> post('f_tok'));
        $rawFormDataArr = json_decode($this -> input -> post('form'));

        if (!$rawFormDataArr) {
            header("HTTP/1.1 400 Bad Request");
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
        }

        $cleanFormArray = array();
        foreach ($rawFormDataArr as $rawData) {
            $cleanData = addslashes($rawData -> value);
            $cleanIndex = addslashes($rawData -> name);
            if (($rawData -> name == "paymentMethodId" && $rawData -> checked == true) || $rawData -> name != "paymentMethodId")
                $cleanFormArray[$cleanIndex] = $cleanData;
        }
        logMessage($cleanFormArray);
        
        $success = false;
        
        if($cleanFormArray['payMethodId']){
            $payMethodObj = RNCPHP\financial\paymentMethod::fetch($cleanFormArray['payMethodId']);    
            $success = $this -> model('custom/paymentMethod_model') -> deletePaymentMethod($cleanFormArray['payMethodId']);
        }
        
        echo $this -> createResponseObject($success, array(), "/app/account/transactions/c_id/".$this -> session -> getProfileData('contactID')."/action/deleteConfirm/", array('confirmMessage'=> getMessage(CUSTOM_MSG_DELETE_PAYMETHOD) ));
    }

    function cancelPledge() {
        AbuseDetection::check($this -> input -> post('f_tok'));

        $pledge_id = $_POST['pledgeID'];
        if ($pledge_id > 0) {
            try {
                $pledge = RNCPHP\donation\pledge::fetch($pledge_id);
                $pledge -> PledgeStatus = RNCPHP\donation\PledgeStatus::fetch(4);
                //cancelled by customer
                $pledge -> StopDate = time();
                //today
                $notes_count = count($pledge -> Notes);
                if ($notes_count == 0) {
                    $pledge -> Notes = new RNCPHP\NoteArray();
                }
                $pledge -> Notes[$notes_count] = new RNCPHP\Note();
                $pledge -> Notes[$notes_count] -> Text = "Customer Cancelled pledge " . date('m/d/Y H:i:s');


                $pledge -> save();
            } catch(Exception $e) {
                echo "Error " . $e -> getMessage();
            }
        }
        echo $this -> createResponseObject("Success!", array(), "/app/pledge_confirm/pledge_id/" . $pledge_id . "/");
    }

    function updatePledge() {
        AbuseDetection::check($this -> input -> post('f_tok'));
        $rawFormDataArr = json_decode($this -> input -> post('form'));

        if (!$rawFormDataArr) {
            header("HTTP/1.1 400 Bad Request");
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
        }

        $cleanFormArray = array();
        foreach ($rawFormDataArr as $rawData) {
            $cleanData = addslashes($rawData -> value);
            $cleanIndex = addslashes($rawData -> name);
            if (($rawData -> name == "paymentMethodId" && $rawData -> checked == true) || $rawData -> name != "paymentMethodId")
                $cleanFormArray[$cleanIndex] = $cleanData;
        }
        logMessage($cleanFormArray);
        if ($cleanFormArray['pledge_id'] > 0) {
            try {
                $pledge = RNCPHP\donation\pledge::fetch($cleanFormArray['pledge_id']);
                //                if($cleanFormArray['freqoptions'] > 0)
                //                    $pledge->Frequency = RNCPHP\donation\DonationPledgeFreq::fetch($cleanFormArray['freqoptions']);
                //                if($cleanFormArray['pledgeamount'] > 0)  //not letting them update the pledge amount any more  //they should cancel and enter new pledge
                //                    $pledge->PledgeAmount = number_format($cleanFormArray['pledgeamount'] , 2, '.', '');
                if ($cleanFormArray['paymethods'] > 0)
                    $pledge -> paymentMethod2 = RNCPHP\financial\paymentMethod::fetch($cleanFormArray['paymethods']);
                    $pledge->UpdatedPayInfoCP = time();//used to report on when a customer updated a pledge.
                    $pledge -> save(RNCPHP\RNObject::SuppressAll);
            } catch(Exception $e) {
                echo $this -> createResponseObject("Error updating pledge " . $e -> getMessage());
            }
        }

        echo $this -> createResponseObject("Success!", array(), "/app/pledge_confirm/pledge_id/" . $cleanFormArray['pledge_id'] . "/");

        return;

    }

    /**
     * Sample function for ajaxCustom controller. This function can be called by sending
     * a request to /ci/ajaxCustom/ajaxFunctionHandler.
     */
    function runStoredPayment() {
        //AbuseDetection::check($this -> input -> post('f_tok'));
        
        $rawFormDataArr = json_decode($params['formData']);

        if (!$rawFormDataArr) {
            header("HTTP/1.1 400 Bad Request");
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
        }


        $cleanFormArray = array();
        foreach ($rawFormDataArr as $rawData) {
            $cleanData = addslashes($rawData -> value);
            $cleanIndex = addslashes($rawData -> name);
            if (($rawData -> name == "paymentMethodId" && $rawData -> checked == true) || $rawData -> name != "paymentMethodId")
                $cleanFormArray[$cleanIndex] = $cleanData;
        }

        $sanityCheckMsgs = array();
        $cleanFormArray['paymentMethodId'] = (int)$cleanFormArray['paymentMethodId'];
        if (is_null($cleanFormArray['paymentMethodId']) || !is_int($cleanFormArray['paymentMethodId']) || $cleanFormArray['paymentMethodId'] < 1) {
            $sanityCheckMsgs[] = "Invalid Payment Method";
        }


        $transactionId = $this -> session -> getSessionData('transId');
        if (is_null($transactionId) || strlen($transactionId) < 1) {
            $sanityCheckMsgs[] = "Invalid Transaction";
        }
        $paymentMethodsArr = $this -> model('custom/paymentMethod_model') -> getCurrentPaymentMethodsObjs($c_id);
        if (count($paymentMethodsArr) < 1) {
            $sanityCheckMsgs[] = "Error Processing Payment, unable to access stored payment";
        }

        $thisPayMethod = null;
        foreach ($paymentMethodsArr as $key => $value) {
            if ($cleanFormArray['paymentMethodId'] == $value -> ID) {
                $thisPayMethod = $value;
                break;
            }
        }


        if (is_null($thisPayMethod)) {
            $sanityCheckMsgs[] = "Unable to access stored payment method";
        }

        if (is_null($this -> session -> getSessionData('total')) || !is_numeric($this -> session -> getSessionData('total')) || $this -> session -> getSessionData('total') != $cleanFormArray['PaymentAmount']) {
            $sanityCheckMsgs[] = "Invalid Payment Amount";
        }

        // If this is a sponsorship pledge, verify that the child being sponsored is still locked by the user executing the transaction.
        $transItemType = $this->session->getSessionData('item_type');
        $this->logging->logVar('Item Type for Transaction: ', $transItemType);
        if($transItemType === DONATION_TYPE_SPONSOR){
            logMessage('Running sponsorship transaction. Verifying child record lock is still held by logged in user.');
            //$items = $this->session->getSessionData('items');
            $items = $this -> model('custom/items') -> getItemsFromCart($this->session->getSessionData('sessionID'), 'checkout');
            // I think there can only ever be a single child item here but doing a for loop to make this future proof
            foreach($items as $item){
                $this->logging->logVar('Child sponsorship record: ', $item);
                $status = $this->model('custom/sponsorship_model')->isChildRecordLocked(intval($item['childId']));
                $this->logging->logVar('Is Child Record Locked?: ', $status->isLocked);
                $this->logging->logVar('Lock Owner: ', $status->lastOwner);
                $loggedInContactID = get_instance()->session->getProfileData('contactID');
                $this->logging->logVar('Logged in contact ID: ', $loggedInContactID);
                if(!$status->isLocked || $status->lastOwner !== $loggedInContactID){
                    $sanityCheckMsgs[] = "Lock on child record has expired. Please redo transaction.";
                }
            }
        }

        if (count($sanityCheckMsgs) > 0) {
            echo $this -> createResponseObject("Invalid Input", $sanityCheckMsgs);
            return;
        }

        $frontstreamResp = $this -> model('custom/frontstream_model') -> ProcessPayment($transactionId, $thisPayMethod, intval($this -> model('custom/items') -> getTotalDueNow($this->session->getSessionData('sessionID'))), FS_SALE_TYPE);

        $result = array();

        logMessage("FRONT STREAM RESPONSE");
        logMessage($frontstreamResp);
        if ($frontstreamResp['isSuccess'] === true) {

            $donationId = $this -> afterTransactionDonationCreation($thisPayMethod);

            if ($donationId === false || $donationId < 1) {
                echo $this -> createResponseObject("The payment processed correctly, but your donation may not have been properly credited.  Please contact donor services", $sanityCheckMsgs);
                return;
            }

            //need to update status to complete only after donation is associated.  otherwise CPM will not pick up the donation.
            $this -> model('custom/transaction_model') -> updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $thisPayMethod -> ID, $frontstreamResp['pnRef']);

            logMessage("sending success response");
            echo $this -> createResponseObject("Success!", array(), $this -> redirectCcChargeSuccessLocation . "t_id/" . $transactionId . "/authCode/" . $this -> model('custom/frontstream_model') -> authCode . "/");
            return;
        }
logMessage("sending NOT success response");
        echo $this -> createResponseObject("Error Processing Payment", $this -> model('custom/frontstream_model') -> getEndUserErrorMsg());
        return;
    }

    function changePayMethod(){
            
        AbuseDetection::check($this -> input -> post('f_tok'));
        $rawFormDataArr = json_decode($this -> input -> post('form'));

        if (!$rawFormDataArr) {
            header("HTTP/1.1 400 Bad Request");
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
        }

        $cleanFormArray = array();
        foreach ($rawFormDataArr as $rawData) {
            $cleanData = addslashes($rawData -> value);
            $cleanIndex = addslashes($rawData -> name);
            if (($rawData -> name == "paymentMethodId" && $rawData -> checked == true) || $rawData -> name != "paymentMethodId")
                $cleanFormArray[$cleanIndex] = $cleanData;
        }
        logMessage($cleanFormArray);
        
        $success = false;
        
        if($cleanFormArray['pledgeId'] > 0 && $cleanFormArray['payMethodId']){
            $payMethodObj = RNCPHP\financial\paymentMethod::fetch($cleanFormArray['payMethodId']);    
            $success = $this -> model('custom/donation_model') -> savePayMethodToPledge($cleanFormArray['pledgeId'], $payMethodObj );
            logMessage("success = ".$success);
        }
        
        echo $this -> createResponseObject($success, array(), "/app/account/pledges/c_id/".$this -> session -> getProfileData('contactID')."/action/updateConfirm/p_id/".$cleanFormArray['pledgeId']."/", null);
    }

    function createAdvocacyRelationship(){
        $advocacyNotes = array();
        AbuseDetection::check($this -> input -> post('f_tok'));
        $rawFormDataArr = json_decode($this -> input -> post('form'));
        if (!$rawFormDataArr) {
            header("HTTP/1.1 400 Bad Request");
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
        }
        
        $rawFormDataArr -> items;
        logMessage("advocacy items");
        logMessage($rawFormDataArr -> items);
        $advocacyNotes['childId'] =  $rawFormDataArr -> items[0] -> childId;
        $advocacyNotes['contactId'] = $this -> session -> getProfileData('contactID');
        $advocacyNotes['eventId'] = $rawFormDataArr -> items[0] -> eventId;
        
        $advocate = new RNCPHP\sponsorship\Advocates();
        $advocate->Contact = RNCPHP\Contact::fetch($this -> session -> getProfileData('contactID'));
        $advocate->Child = RNCPHP\sponsorship\Child::fetch($rawFormDataArr -> items[0] -> childId);
        $advocate->Event = RNCPHP\sponsorship\Event::fetch($rawFormDataArr -> items[0] -> eventId);
        $advocate->save();
        
        echo $this -> createResponseObject('Advocacy relationship created', array(), "/app/advocacy/event/". $rawFormDataArr -> items[0] -> eventId , $advocacyNotes);
        
    }

    function storeCartData() {

        try{
            
            $rawFormDataArr = json_decode($this -> input -> post('form'));
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 380 \n", FILE_APPEND);
            if (!$rawFormDataArr) {
                header("HTTP/1.1 400 Bad Request");
                // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
                Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
            }
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 386 \n", FILE_APPEND);

            $this -> clearCartData();
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 389 \n", FILE_APPEND);
            $sessionData = array(
                'items' => $rawFormDataArr -> items,
                'donateValCookieContent' => $rawFormDataArr -> donateValCookieContent
            );
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 394 \n", FILE_APPEND);
            
            logMessage("Setting Session Data:");
            logMessage($rawFormDataArr);
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Session Data:".print_r($sessionData, true)." \n", FILE_APPEND);

            $this -> session -> setSessionData($sessionData);
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 401 \n", FILE_APPEND);
            $this -> parseItemTypes();
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 403 \n", FILE_APPEND);
            $this -> parseChildData();  //also woman data if present
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 405 \n", FILE_APPEND);
            $this -> calculateTotal();
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 407 \n", FILE_APPEND);
            // Temporary hack for getting sponsorship items to be stored in the DB. Need to store the items to DB
            // after parseItemTypes and parseChildData have processed the item data.
            // They will also still be getting stored in the session for the time being. We shouldn't have to worry about overrunning 
            // session though since sponsorships are limited to 1 item per transaction.
            $processedItems = $this->session->getSessionData('items');
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 409 \n", FILE_APPEND);
            $this->model('custom/items')->saveItemsToCart($this -> session -> getSessionData('sessionID'), $processedItems);
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 411 \n", FILE_APPEND);

            return $this -> createResponseObject('Session Cart Updatad', array());
        }catch(Exception $e){
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Exception ".print_r($e->getMessage(), true)." \n", FILE_APPEND);
        }catch (RNCPHP\ConnectAPIError $err) {
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Exception ".print_r($err->getMessage(), true)." \n", FILE_APPEND);
        }
        
    }

    function getCartData() {

        $total = $this -> session -> getSessionData('total');
        //$items = $this -> session -> getSessionData('items');
        $items = $this -> model('custom/items') -> getItemsFromCart($this->session->getSessionData('sessionID'), 'checkout');
        $donateVal = $this -> session -> getSessionData('donateValCookieContent');

        echo $this -> createResponseObject('Session Cart Data', array(), null, array(
            'total' => $total,
            'items' => $items,
            'donateValCookieContent' => html_entity_decode($donateVal)
        ));
    }

    function clearCartData() {
        logMessage("Clearing cart data");
        
        $sessionData = array(
            'total' => null,
            'totalRecurring' => null,
            'items' => null,
            'donateValCookieContent' => null,
            
        );
        $this -> session -> setSessionData($sessionData);

        $this -> model('custom/items') -> clearItemsFromCart($this->session->getSessionData('sessionID'));
    }

    public function getUnsponsoredChildren1() {
        logMessage("inunsponsored1");

        $rawFormDataArr = json_decode($this -> input -> post('form'));
        logMessage("unsponsordchildren1 = " . $_GET['count'] . " - " . $_GET['gender'] . " - " . $_GET['age'] . " - " . $_GET['community'] . " - " . $_GET['order'] . " - " . $_GET['page']);
        try {
            $cdid = "";
            $this -> load -> model('custom/sponsorship_model');
            $cdid = $this -> sponsorship_model -> getUnsponsoredChildren1($_GET['count'], $_GET['gender'], $_GET['age'], $_GET['community'], $_GET['order'], $_GET['page']);
            echo json_encode($cdid);
        } catch(Exception $e) {
            echo json_encode("Fail");
        }
    }

    /**
     *Determines what type of item each is and applies a attribute to the item
     */
    private function parseItemTypes() {
        logMessage("starting " . __FUNCTION__);
        $items = $this -> session -> getSessionData('items');

        foreach ($items as $index => $item) {
            if (isset($item -> giftId) && $item -> giftId > 0) {
                logMessage("is gift");
                $items[$index] -> type = DONATION_TYPE_GIFT;
                $item_type = DONATION_TYPE_GIFT;
            } else if (isset($item -> childId) && $item -> childId > 0) {
                logMessage("is sponsorship");
                $items[$index] -> type = DONATION_TYPE_SPONSOR;
                $item_type = DONATION_TYPE_SPONSOR;
            } else if (isset($item -> fund) && $item -> fund > 0) {
                logMessage("is pledge");
                $items[$index] -> type = DONATION_TYPE_PLEDGE;
                $item_type = DONATION_TYPE_PLEDGE;
            }

            if (is_null($items[$index] -> type)) {
                $items[$index] = null;
            }
        }
        //remove removed items
        $items = array_filter($items);

        $sessionData = array(
            'items' => $items,
            'item_type' => $item_type
        );

        logMessage('$sessionData[\'items\'] = ' . var_export($sessionData['items'], true));
        logMessage('$sessionData[\'item_type\'] = ' . var_export($sessionData['item_type'], true));

        $this -> session -> setSessionData($sessionData);


    }

    /**
     * If a child id is passed in the items array, pull out information on that child
     */
    private function parseChildData() {
        $items = $this -> session -> getSessionData('items');
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 518 \n", FILE_APPEND);
        foreach ($items as $index => $item) {
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 520 \n", FILE_APPEND);
            if (isset($item -> childId) && $item->child_sponsorship) {
                $childData = $this -> model('custom/sponsorship_model') -> getChild(intval($item -> childId));
                file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 523 \n", FILE_APPEND);
                if (isset($childData[0])) {
                    file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 525 \n", FILE_APPEND);
                    $item -> childName = $childData[0] -> GivenName;
                    //only set recurring rate if we're starting a sponsorship
                    if ($item -> type === DONATION_TYPE_SPONSOR) {
                        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 529 \n", FILE_APPEND);
                        $item -> recurring = $childData[0] -> Rate;
                        $item -> itemName = "Sponsor ". $childData[0] -> ChildRef. " ". $childData[0] -> GivenName;
                    }
                    $items[$index] = $item;
                }
            }else if(isset($item -> childId) && $item->isWomensScholarship){
                file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 533 \n", FILE_APPEND);
                $womanData = $this -> model('custom/woman_model') -> getWoman(intval($item -> childId));
                file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 535 \n", FILE_APPEND);
                logMessage("getting woman data");
                if (isset($womanData[0])) {
                    file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 538 \n", FILE_APPEND);
                    $item -> childName = $womanData[0] -> GivenName;
                    //only set recurring rate if we're starting a sponsorship
                    if ($item -> type === DONATION_TYPE_SPONSOR) {
                        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 542 \n", FILE_APPEND);
                        //$item -> recurring = $childData[0] -> Rate;
                        $item -> itemName = "Scholorship ". $childData[0] -> WomanRef. " ". $childData[0] -> GivenName;
                    }
                    file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 546 \n", FILE_APPEND);
                    $items[$index] = $item;
                }
            }
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 550 \n", FILE_APPEND);
        }
        $sessionData = array('items' => $items);
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 553 \n", FILE_APPEND);
        $this -> session -> setSessionData($sessionData);
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').": 555 \n", FILE_APPEND);
        logMessage('$sessionData[\'items\'] (after setting child data) = ' . var_export($sessionData['items'], true));
    }

    private function calculateTotal() {
        $items = $this -> session -> getSessionData('items');

        $totalAmount = 0;
        $totalRecurring = 0;


        foreach ($items as $index => $item) {
            if ($item -> qty < 1)
                $item -> qty = 1;
            if (isset($item -> recurring)) {
                $totalAmount += ($item -> recurring * $item -> qty);
                $totalRecurring += ($item -> recurring * $item -> qty);
            }
            if (isset($item -> oneTime)) {
                $totalAmount += ($item -> oneTime * $item -> qty);
            }
        }
        $sessionData = array(
            'total' => $totalAmount,
            'totalRecurring' => $totalRecurring
        );

        logMessage('$sessionData[\'total\'] = ' . var_export($sessionData['total'], true));
        logMessage('$sessionData[\'totalRecurring\'] = ' . var_export($sessionData['totalRecurring'], true));

        $this -> session -> setSessionData($sessionData);
    }

    /**
     * method to create an ajax response object for cc transactions.  accepts both a text message and an array that will be returned json-encoded
     */
    private function createResponseObject($message, array $errors, $redirectLocation = null, $includeObject = null) {
        $result = array();

        if (count($errors) > 0) {
            $result['errors'] = $errors;
        } else if (!is_null($redirectLocation) && strlen($redirectLocation) > 0) {
            $result['result']['redirectOverride'] = $redirectLocation;
        } else if (is_null($message)) {
            $result['errors'] = array(getConfig(CUSTOM_CFG_general_cc_error_id));
        }

        $result['message'] = $message;
        if (!is_null($includeObject)) {
            $result['data'] = (object)$includeObject;
        } else {
            $result['data'] = (object) array();
        }
        return json_encode((object)$result);

    }

    function afterTransactionDonationCreation($paymethod) {
        try {
            //we've successfully accomplished a transaction, create the donation object
            $c_id = $this -> session -> getProfileData('contactID');
            $amt = intval($this -> model('custom/items') -> getTotalDueNow($this->session->getSessionData('sessionID')));
            //$items = $this -> session -> getSessionData('items');
            $items = $this -> model('custom/items') -> getItemsFromCart($this->session->getSessionData('sessionID'), 'checkout');
            $transactionId = $this -> session -> getSessionData('transId');

            $donationId = $this -> model('custom/donation_model') -> createDonationAfterTransaction($amt, $c_id, $items, $transactionId, $paymethod);
            logMessage(__FUNCTION__ . "@" . __LINE__ . ":  created donation $donationId");

        } catch(Exception $e) {
            logMessage($e -> getMessage());
            $donationId = -1;
        }
        return $donationId;


    }

    function clearSessionData() {
        $sessionData = array(
            'total' => null,
            'totalRecurring' => null,
            'items' => null,
            'donateValCookieContent' => null,
            'payMethod' => null
        );

        $this -> session -> setSessionData($sessionData);
        $sessionData = array('transId' => null);
        $this -> session -> setSessionData($sessionData);

        $this -> model('custom/items') -> clearItemsFromCart($this->session->getSessionData('sessionID'));
        

    }

    function sendStatement() {

        AbuseDetection::check($this -> input -> post('f_tok'));
        $errorMsgs = array();
        $contactid = $this -> input -> post('cid');
        $contact = RNCPHP\Contact::fetch($contactid);
        if ($contact -> ID > 0) {
            //send email
            $statementMailSend = -1;
            $giftReceiptMailSend = RNCPHP\Mailing::SendMailingToContact($contact, null, 11, time());
            if ($giftReceiptMailSend != 1) {
                $errorMsgs[] = "Could Not send mailing to contact. General Mailing Error";
                echo $this -> createResponseObject("Mail Not Sent", $errorMsgs);
            } else {
                echo $this -> createResponseObject("1", array());
            }

        } else {
            $errorMsgs[] = "Contact ID is not valid";
            echo $this -> createResponseObject("Not successful", $errorMsgs);
        }


    }

    function _logToFile($lineNum, $message){
        
        $fp = fopen('/tmp/pledgeLogs_'.date("Ymd").'.log', 'a');
        fwrite($fp,  "AjaxCustom @ $lineNum : ".$message."\n");
        fclose($fp);
        
    }

}