<?
namespace Custom\Models;

use \RightNow\Connect\v1_3 as RNCPHP;
require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();

class donation_model  extends \RightNow\Models\Base {
    function __construct() {
        parent::__construct();
        $this -> CI -> load -> helper('constants');
        //This model would be loaded by using $this->load->model('custom/frontstream_model');

    }

    /**
     * Returns an array of children associated with a given special gift item as established by one or 
     * more records in online.SpecialGiftItem in the form:
     *
     * array(
     *     array('Child' => <RNCPHP\sponsorship\Child record>, 'AssociationExpired' => false),
     *     array('Child' => <RNCPHP\sponsorship\Child record>, 'AssociationExpired' => false),
     *     array('Child' => <RNCPHP\sponsorship\Child record>, 'AssociationExpired' => true)
     * )
     *
     * @param {integer} $itemID The item ID of the special gift item
     */
    public function getChildrenAssociatedWithSpecialGiftItem($itemID){
        logMessage('Inside getChildrenAssociatedWithSpecialGiftItem');
        if(!is_int($itemID)) throw new \Exception('Invalid item ID');
        $item = $this -> CI -> model('custom/items') -> getitemdetails($itemID);
        if(!isset($item->ID)) throw new \Exception("No item exists with ID = $itemID");

        $profile = $this->CI->session->getProfile();
        $loggedInContactID = !is_null($profile) ? $profile->c_id->value : null;
        if(is_null($loggedInContactID)) throw new \Exception('Expected contact to be logged in');

        $children = array();
        $query = "Contact.ID = $loggedInContactID AND Item.ID = $itemID";
        logMessage("\$query = $query");
        $specialGiftItems = RNCPHP\online\SpecialGiftItem::find($query);
        logMessage($specialGiftItems);
        foreach($specialGiftItems as $specialGiftItem){
            $children[] = array(
                'Child' => $specialGiftItem->Child,
                'AssociationExpired' => !$specialGiftItem->Enabled
            );
        }

        return $children;
    }

    public function disableSpecialGiftItemForChild($itemID, $childID){
        if(!is_int($itemID)) throw new \Exception('Invalid item ID');
        if(!is_int($childID)) throw new \Exception('Invalid child ID');
        $item = $this -> CI -> model('custom/items') -> getitemdetails($itemID);
        if(!isset($item->ID)) throw new \Exception("No item exists with ID = $itemID");
        $children = $this -> CI -> model('custom/sponsorship_model') -> getChild($childID);
        if(count($children) == 0) throw new \Exception("No child exists with ID = $childID");

        $profile = $this->CI->session->getProfile();
        $loggedInContactID = !is_null($profile) ? $profile->c_id->value : null;
        if(is_null($loggedInContactID)) throw new \Exception('Expected contact to be logged in');

        $specialGiftItems = RNCPHP\online\SpecialGiftItem::find("Contact = $loggedInContactID AND Item = $itemID AND Child = $childID");
        foreach($specialGiftItems as $specialGiftItem){
            $specialGiftItem->Enabled = false;
            $specialGiftItem->save();
        }
    }

    public function createDonationAfterTransaction($amt, $c_id, array $items, $trans_id, $paymentMethod) {

        $itemDescs = array();
        if (count($items) < 1) {
$this->_logToFile(76, "No items passed to method");
            return false;
        }

        foreach ($items as $item) {
            $itemDescs[] = $item['itemName'];
        }
        $donationId = $this -> savedonation($amt, $c_id, $items);
$this->_logToFile(84, "Saved Donation:".$donationId); 
        if ($donationId < 1) {
$this->_logToFile(86, "No donation created");
            return false;
        }
        //loop through the items and determine their type.  Add each type to the donation
        foreach ($items as $index => $item) {
            //@todo: optimize this
            //logMessage("starting parsing item in " . __FUNCTION__);
            logMessage($item);
            switch ($item['type']) { 
                case DONATION_TYPE_PLEDGE :
                    $itemId = $this -> addPledgeToDonation($donationId, $item['recurring'], $item['oneTime'], $c_id, $item['fund'], $item['appeal'], $item['childId'], $paymentMethod, $item['pledgeId']);
                    break;
                case DONATION_TYPE_GIFT :
                    $itemId = $this -> addGiftToDonation($donationId, $item['oneTime'], $item['giftId'], $item['qty'], $item['childId']);
                    try{
                        //development never got deployed
                        //$this -> disableSpecialGiftItemForChild($item['giftId'], $item['childId']);
                    }catch(\Exception $e){
                        // This step is not critical to finalizing the donation, so swallow this error 
                        logMessage($e->getMessage());
                    }
                    break;
                case DONATION_TYPE_SPONSOR :
                    $itemId = $this -> addPledgeToDonation($donationId, $item['recurring'], $item['oneTime'], $c_id, $item['fund'], $item['appeal'], $item['childId'], $paymentMethod, null, $item['isWomensScholarship']);
                    break;
            }

            if ($itemId == false || $itemId < 1) {
                //logMessage("Unable to add pledge or gift to donation");
                return false;
            }
        }

        //add donation to transaction
        $this -> CI -> model('custom/transaction_model') -> addDonationToTransaction($trans_id, $donationId);
        return $donationId;
    }

    /**
     * Creates a donation object
     */
    public function savedonation($amt, $c_id, $items) {
        try {
            $newDonation = new RNCPHP\donation\Donation;
            $newDonation -> Contact = intval($c_id);
            $newDonation -> DonationDate = time();
            $newDonation -> Amount = number_format($amt, 2, '.', '');
            //Set the donation.Donation.PaymentSource = "EndUser", which is the third menu sel
            $newDonation -> PaymentSource = RNCPHP\donation\paymentSourceMenu::fetch(DONATION_PAYMENT_SOURCE);              

            //set the type  in th future the cart may accept more than one item type so this may need to be changed.
            foreach ($items as $index => $item) {
                switch ($item['type']) {
                    case DONATION_TYPE_PLEDGE :
                        $newDonation -> Type = RNCPHP\donation\Type::fetch(1);
                        break;
                    case DONATION_TYPE_GIFT :
                        $newDonation -> Type = RNCPHP\donation\Type::fetch(38);
                        break;
                    case DONATION_TYPE_SPONSOR :
                        $newDonation -> Type = RNCPHP\donation\Type::fetch(39);
                        break;
                }
            }


            $newDonation -> save(RNCPHP\RNObject::SuppressAll);
            RNCPHP\ConnectAPI::commit();
            $id = $newDonation -> ID;
        } catch(Exception $e) {
$this->_logToFile(159, $e -> getMessage());
            return 0;
        }
        
$this->_logToFile(163, "New Donation Created with Contact:".$newDonation->Contact->ID." DonationDate:".$newDonation -> DonationDate." Amount:".$newDonation->Amount." Type:".$newDonation->Type->LookupName);
        return $id;
    }

    public function addGiftToDonation($don_id, $price, $giftId, $qty = 1, $child_id = null) {
        //logMessage("Starting " . __FUNCTION__);
        try {
            $donationItem = new RNCPHP\donation\DonationItem();
            if (!is_null($child_id) && $child_id != 0) {
                $donationItem -> Child = intval($child_id);
            }
            $donationItem -> DonationId = intval($don_id);
            $donationItem -> Item = intval($giftId);
            $donationItem -> Total = number_format($price, 2, '.', '') * intval($qty);
            $donationItem -> PricePerItem = number_format($price, 2, '.', '');
            $donationItem -> Quantity = $qty;
            $donationItem -> save(RNCPHP\RNObject::SuppressAll);
            RNCPHP\ConnectAPI::commit();
        } catch(Exception $e) {
            //logMessage($e -> getMessage);
            return false;
        }
        $id = $donationItem -> ID;
        return $id;
    }

    public function addPledgeToDonation($donationId, $mon = 0, $one = 0, $c_id, $fund = null, $appeal = null, $childId = null, $paymentMethod, $pledgeId = null, $isWomensScholarship = false) {
$this->_logToFile(187, "Adding pledge to donation");
$this->_logToFile(188, "Donation: $donationId, PayMethod: $paymentMethod->ID, Monthly: $mon, One Time: $one, Contact: $c_id, Fund: $fund, Appeal: $appeal, ChildId: $childId");

        try {

            $pledge = new RNCPHP\donation\pledge();
            $donation = RNCPHP\donation\Donation::fetch($donationId);
            if (!$donation instanceof RNCPHP\donation\Donation) {
                return false;
            }
            $mon = intval($mon);
            $one = intval($one);
            if ($mon != 0) {
                //save as monthly donation
                $pledge -> StartDate = time();
                $pledge -> PledgeAmount = number_format($mon, 2, '.', '');
                $pledge -> NextTransaction = time();
                $pledge -> Frequency = RNCPHP\donation\DonationPledgeFreq::fetch(5);//monthly
                $pledge -> Type1 = RNCPHP\donation\Type::fetch(2);
                $pledge-> Balance = 0;
                $pledge -> paymentMethod2 = $paymentMethod;
                $pledge -> PaymentSource = RNCPHP\donation\paymentSourceMenu::fetch(DONATION_PAYMENT_SOURCE);
                $pledge -> Contact = intval($c_id);
                
                if ($childId != null) {
                    //the child id can be for woman or child
                    if($isWomensScholarship){
                        logMessage("Applying Woman to scholarship pledge. Woman ID:" . $childId);
                        $pledge -> Woman = intval($childId);
                        $pledge -> Fund = $this->getSponsorshipFund(intval($childId), true);
                        $pledge -> Appeals = intval(WEB_APPEAL_ID);//WEB
                        $pledge -> pledgefor = RNCPHP\donation\pledgefor::fetch(2);
                    }else{
                        $pledge -> Child = intval($childId);
                        $pledge -> ChildSponsorship = true;
                        //all child sponsorships will have SPON fund WEB appeal
                        $pledge -> Fund = $this->getSponsorshipFund(intval($childId));
                        $pledge -> Appeals = intval(WEB_APPEAL_ID);//WEB
                        $pledge -> pledgefor = RNCPHP\donation\pledgefor::fetch(1);
                        
                        //need to set teh sponsored child as "sponsored"
                        $cdid = $this -> setChildSponsored($childId);
                        logMessage("donation model child id = " . $cdid);
                    }
                }

                //sponsorships may or may not have a fund.  if there is no child, a fund is required.
                if (is_null($fund) || $fund < 1) {
                    if (is_null($childId)) {
                        logMessage(__FUNCTION__ . "@" . __LINE__ . ": Invalid Fund");
                        return false;
                    }
                } else {
                    logMessage("Fund being set after sponsorship to fund ".$fund);
                    $pledge -> Fund = intval($fund);
                }


                if (!is_null($appeal)) {
                    $pledge -> Appeals = intval($appeal);
                }
                

                if ($childId != "") {
                    logMessage("Adding Child to pledge description = " . $descr);
                    $descr .= $pledge -> Child -> ChildRef;
                    logMessage("Adding Child to pledge description = " . $descr);
                }

                if (!empty($pledge -> Fund)) {
                    $descr = $pledge->Fund->Descriptions[0]->LabelText;
                }

                logMessage("iswomanScholarship:".$isWomensScholarship);
                if($isWomensScholarship){
                    
                    logMessage("Setting description:".$pledge -> Woman -> WomanRef);
                    $descr = 'Woman:'.$pledge -> Woman -> WomanRef." ".$pledge->Woman->GivenName;
                }
                
                logMessage("desc:".$descr);
                if ($descr != "") {
                    logMessage("Adding Description to donation = " . $descr);
                    $pledge -> Descr = $descr;
                }
                

                $pledge -> save(RNCPHP\RNObject::SuppressAll);
                RNCPHP\ConnectAPI::commit();

                $donation2Pledge = new RNCPHP\donation\donationToPledge();
                $donation2Pledge -> PledgeRef = $pledge -> ID;
                $donation2Pledge -> DonationRef = $donation -> ID;

                try {
                    $donation2Pledge -> save(RNCPHP\RNObject::SuppressAll);
                    RNCPHP\ConnectAPI::commit();

                } catch(Exception $e) {
                    return false;
                }
            }

            if ($one != 0) {
                
                if(!$pledgeId){//if this ia pledge payment then we'll associate the existing pledge to the donation/transaction

                        $pledge = new RNCPHP\donation\pledge();
                        $pledge -> DonationDate = time();
                        $pledge -> PledgeAmount = number_format($one, 2, '.', '');
                        $pledge -> Frequency = RNCPHP\donation\DonationPledgeFreq::fetch(9);
                        $pledge -> Type1 = RNCPHP\donation\Type::fetch(3);
                        $pledge -> Contact = intval($c_id);
                        $pledge -> NextTransaction = time();
                        $pledge-> Balance = 0;

                        $pledge -> paymentMethod2 = $paymentMethod;
                        
        
        
                        //sponsorships may or may not have a fund.  if there is no child, a fund is required.
                        if (is_null($fund) || $fund < 1) {
                            if (is_null($childId)) {
                                //logMessage(__FUNCTION__ . "@" . __LINE__ . ": Invalid Fund");
                                return false;
                            }
                        } else {
                            $pledge -> Fund = intval($fund);
                        }
                        
                        if ($fund != "")
                            //ASM bug fix on 2016-08-29 to remove leading space from pledge description
                            //$descr = " " . $pledge->Fund->Descriptions[0]->LabelText;
                            $descr = $pledge->Fund->Descriptions[0]->LabelText;
                        if ($descr != "")
                            $pledge -> Descr = $descr;
        
        
                        if (!is_null($appeal)) {
                            $pledge -> Appeals = intval($appeal);
                        }
        
                        if ($childId != null) {
                            if($isWomensScholarship){
                                logMessage("Applying Woman to scholarship pledge. Woman ID:" . $childId);
                                $pledge -> Woman = intval($childId);
                                $pledge -> Fund = $this->getSponsorshipFund(intval($childId), true);
                                $pledge -> Appeals = intval(WEB_APPEAL_ID);//WEB
                                $pledge -> pledgefor = RNCPHP\donation\pledgefor::fetch(2);
                                $pledge -> Descr  = 'Woman:'.$pledge -> Woman -> WomanRef." ".$pledge->Woman->GivenName;
                            }else{
                                $pledge -> Child = intval($childId);
                                $pledge -> ChildSponsorship = true;
                                //need to set teh sponsored child as "sponsored"
                                $cdid = $this -> setChildSponsored($childId);
                            }
        
                        }
        
                        $pledge -> save(RNCPHP\RNObject::SuppressAll);
                        RNCPHP\ConnectAPI::commit();
                        logMessage(__FUNCTION__ . "@" . __LINE__ . ": pledge object");
                        logMessage($pledge -> ID);                                      
                }else{
                    $this->savePayMethodToPledge($pledgeId, $paymentMethod);
                    $pledge = RNCPHP\donation\pledge::fetch($pledgeId);
                }

                $donation2Pledge = new RNCPHP\donation\donationToPledge();
                $donation2Pledge -> PledgeRef = $pledge -> ID;
                $donation2Pledge -> DonationRef = $donation -> ID;

$this->_logToFile(353, ": paymethod ID:" . $paymentMethod -> ID);
$this->_logToFile(354, ": donation ID:" . $donation -> ID);
$this->_logToFile(355, ": pledge  ID:" . $pledge -> ID);
$this->_logToFile(356, ": donation2pledge object");
$this->_logToFile(357, print_r($donation2Pledge, true));

                try {
                    $donation2Pledge -> save(RNCPHP\RNObject::SuppressAll);
                    RNCPHP\ConnectAPI::commit();

                } catch(Exception $e) {
$this->_logToFile(364,$e -> getMessage());
                    return false;
                }
            }


        } catch(Exception $e) {
$this->_logToFile(371, $e -> getMessage());
            return false;
        }


        $id = $pledge -> ID;
$this->_logToFile(377, "donation item id: $id");
        return $id;
    }

    public function savePayMethodToPledge($pledgeId, $paymentMethod ){
//$this->_logToFile(__FUNCTION__ . "@" . __LINE__ . ": paymethod ID:" . $paymentMethod -> ID);
        $pledge = RNCPHP\donation\pledge::fetch($pledgeId);
        $pledge -> paymentMethod2 = $paymentMethod;//make the payment method the one they just used.
        $pledge -> save(RNCPHP\RNObject::SuppressAll);
        RNCPHP\ConnectAPI::commit();
    }

    public function getSponsorshipFund($childId, $isWomensScholarship = false){
        
        if ($childId > 0) {
            try {
                $child = ($isWomensScholarship) ?  RNCPHP\sponsorship\Woman::fetch($childId): RNCPHP\sponsorship\Child::fetch($childId);
                if ($child) {
                    $fund = (!$isWomensScholarship && $child->SponsorshipStatus->LookupName == "Co-Sponsor Needed") ? $this->getNewFund($child->Community->Fund->AccountingCode) : $child->Community->Fund;
                    if(!$fund)
                        $fund = $child->Community->Fund;
                    logMessage("got a fund for a child fund id: ".$fund->ID);

                    //if we still haven't gotten a fund get the spon fund
                    if(empty($fund)){
                        $fund = RNCPHP\donation\fund::fetch(intval(SPON_FUND_ID)); //SPON
                    }
                }else{
                    $fund = RNCPHP\donation\fund::fetch(intval(SPON_FUND_ID)); //SPON
                    logMessage("Didn't quite get a child fund");
                }

            } catch (Exception $ex) {
                return false;
            }
        }else{
            $fund = RNCPHP\donation\fund::fetch(intval(SPON_FUND_ID)); //SPON
            logMessage("Child id is not set or negative");
        }
        
        return $fund;
    }
    
    function getNewFund($fundRef){
        
        $fundRef = $fundRef.'2';
        $roql = "SELECT donation.fund FROM donation.fund WHERE donation.fund.AccountingCode = '$fundRef'";
    
        $fundObj = RNCPHP\ROQL::queryObject($roql)->next();
        while($fund = $fundObj->next()) {
            return $fund;
        }
        
        return null;
        
    }

    public function setChildSponsored($childID) {
        //logMessage("sponsormodel child = ".$childID);
        if ($childID > 0) {
            try {

                $child = RNCPHP\sponsorship\Child::fetch($childID);
                //logMessage("sponsormodel child ID = ".$child -> ID);
                if ($child) {
                    //logMessage("in child if");
                    $child -> SponsorshipStatus = RNCPHP\sponsorship\SponsorshipStatus::fetch(3);
                    //3 = sponsored
                    $child -> save();
                    RNCPHP\ConnectAPI::commit();
                }
                //logMessage("after child save");

            } catch (Exception $ex) {
                return false;
            }

            return $childID;

        }

    }

    public function GetAheadBehind(RNCPHP\donation\pledge $pledge){
        
        $nextTrans = $pledge->NextTransaction;
        $currentBalance = $pledge->Balance;
        $pledgeAmount = $pledge->PledgeAmount;
        $frequency = $pledge->Frequency->LookupName;
        
        
        if($nextTrans < time()){
            $numberOfMonths = $this->_getNumberMonths($nextTrans, time());
            if($frequency == 'Monthly'){
                    $numberOfMonths = ($numberOfMonths + 1) * -1; 
            }else if($frequency == 'Annually'){
                    $numberOfMonths = ($numberOfMonths + 12) * -1; 
            }else if($frequency == "Quarterly"){
                    $numberOfMonths = ($numberOfMonths + 3) * -1; 
            }
            //at the nexttrans date the charge is incurred so we have to add the #month increment and its late so it should be negative.
            
        }else{
            $numberOfMonths = $this->_getNumberMonths(time(), $nextTrans);
        }  
        
        if($frequency == 'Monthly'){
                $newBalance = ($numberOfMonths * $pledgeAmount) + $currentBalance;
        }else if($frequency == 'Annually'){
                $numberOfYears = ($numberOfMonths / 12); 
                $numberOfYears = intval($numberOfYears);
                $newBalance = ($numberOfYears * $pledgeAmount)  + $currentBalance;
        }else if($frequency == "Quarterly"){
                $numberOfQuarters = $numberOfMonths / 3;
                $numberOfQuarters = intval($numberOfQuarters);
                $newBalance = ($numberOfQuarters * $pledgeAmount) + $currentBalance;
        }

        $aheadBehind = strval(number_format($newBalance, 2, '.', '')) ;

        
        return $aheadBehind;
    }
    
    public function _getNumberMonths($date1, $date2)
    {
        $months = 0;

        while (strtotime('+1 MONTH', $date1) < $date2) {
            $months++;
            $date1 = strtotime('+1 MONTH', $date1);
        }

        //echo $months. ' month, '. ($date2 - $date1) / (60*60*24). ' days <br/>'; // 120 month, 26 days
        return $months;
        
    }
    
    private function _logToFile($lineNum, $message){
        
        $hundredths = ltrim(microtime(), "0");
        
        $fp = fopen('/tmp/pledgeLogs_'.date("Ymd").'.log', 'a');
        fwrite($fp,  date('H:i:s.').$hundredths.": Donation Model @ $lineNum : ".$message."\n");
        fclose($fp);
        
    }
}
