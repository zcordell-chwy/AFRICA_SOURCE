<?
/*
 * CPMObjectEventHandler: mailinghelper_Create2
 * Package: RN
 * Objects: helpers\mailinghelper
 * Actions: Create
 * Version: 1.3
 */

// This object procedure binds to v1_2 of the Connect PHP API
use \RightNow\Connect\v1_3 as RNCPHP;

// This object procedure binds to the v1 interface of the process
// designer
use \RightNow\CPM\v1 as RNCPM;

/**
 * An Object Event Handler must provide two classes:
 * - One with the same name as the CPMObjectEventHandler tag
 * above that implements the ObjectEventHandler interface.
 * - And one of the same name with a "_TestHarness" suffix
 * that implements the ObjectEventHandler_TestHarness interface.
 *
 * Each method must have an implementation.
 */

class mailinghelper_Create2

implements RNCPM\ObjectEventHandler {
    /**
     * CPM Entry Point
     */
    

    //static $sponsorshipMailingID = 9;//test
    //static $receiptMailingID = 17;//test
    static $sponsorshipMailingID = 4;//prod
    static $receiptMailingID = 5;//prod
    static $giftrecieptMailingID = 6;//prod
    static $updatedNote = array();
    static $emailPreferred = 14;
    static $totalsTable = "";
    static $giftsTotalTable = "";
    static $refundedStatus = 7;
    static $giftsDonationType = 38;
    
    static $tmplDirectory = "/vhosts/africanewlife/euf/assets/Mail_Templates/";
    //mailing templates
    static $sponsorshipTmpl = "newSponsor.html";
    static $refundedTmpl = "refundReceipt.html";
    static $giftsTmpl = "giftsReceipt.html";
    static $donationTmpl = "donationReceipt.html";
    
    //mailings Subject Line
    static $sponsorshipSubj = "Welcome to Africa New Life Sponsorship!";
    static $refundedSubj = "Your refund has been processed.";
    static $giftsSubj = "Thank You for your purchased gifts!";
    static $donationSubj = "Thank You for your Donation to Africa New Life Ministries!";
    static $CHILD_IMAGE_FILESYSTEM_DIR = "/vhosts/africanewlife/euf/assets/childphotos/hashedChildPhotos";
    static $CHILD_IMAGE_URL_DIR = "http://africanewlife.custhelp.com/euf/assets/childphotos/hashedChildPhotos";
    
    
    public static function apply($run_mode, $action, $obj, $n_cycles) {

        self::logToFile("Log", "Beginning Processing for ".$obj->ID);

        $pledges = null;
        if($obj && $obj->transaction && $obj->transaction -> donation && $obj->transaction -> donation -> Type -> ID == static::$giftsDonationType){
            if($obj && $obj->transaction && $obj->transaction->donation){
                $gifts = self::getGifts($obj -> transaction -> donation -> ID);
            }
        }else{
            if($obj && $obj->transaction && $obj->transaction->donation){
                $pledges = self::getPledges($obj->transaction->donation->ID);
            }
        }

        self::_sendCommunications($obj->transaction, $pledges);
        self::logToFile("Log", "------------------------End ".$obj->ID." -----------------------------------");
        return true;
    }
    
    public static function _sendCommunications($trans, $pledges) {
        
        static::$updatedNote[] = "\nStarting " . __FUNCTION__ . " at " . __LINE__;
        self::logToFile("Log", "Starting Send Communications");
        $skipReciept = false;
        
        try {


            if ($pledges) {
                foreach ($pledges as $pledge) {
                    if($pledge->Child){
                        $childNote = " child id = " . $pledge -> Child -> ID . " sent sponsorship = " . $pledge -> SentSponsorEmail;
                    }else{
                        $childNote = $pledge -> ID;
                    }
                    self::logToFile("Pledge id " . $childNote);
                    if ($pledge -> Child && $pledge -> SentSponsorEmail != 1 && !$pledge->copiedFromPledge) {
                        $sponsorshipMailSend = self::sendMailing(static::$sponsorshipTmpl, $trans, $pledges, static::$sponsorshipSubj);
                        self::logToFile( "sponsorship mail send = " . $sponsorshipMailSend);
                        //echo "Sending New sponsor email";
                        $pledge -> SentSponsorEmail = 1;
                        $pledge -> save(RNCPHP\RNObject::SuppressAll);
                        $skipReciept = true;
                    }
                }
            }    

            
            //receipt mailings
            if ($trans 
                    && $trans->donation 
                    && $trans -> donation -> Contact 
                    && $skipReciept == false) {
                
                if($trans->currentStatus->ID == static::$refundedStatus){// refund receipt
                    $refundMailSend = -1;
                    //echo "Sending refund email";
                    $refundMailSend = self::sendMailing(static::$refundedTmpl, $trans, $pledges, static::$refundedSubj);
                    if ($refundMailSend != 1) {
                        self::logToFile( "Couldn't send refund mailing.") ;
                    } else {
                        self::logToFile( "Sent refund mailing to Contact ".$trans -> donation -> Contact->ID);
                    }
                
                    
                }else if($trans -> donation -> Type -> ID == 38){  //gift receipt
                
                    $giftReceiptMailSend = -1;
                    $giftReceiptMailSend = self::sendMailing(static::$giftsTmpl, $trans, null, static::$giftsSubj);
                    if ($giftReceiptMailSend != 1) {
                        self::logToFile( "Couldn't send gift receipt mailing.") ;
                    } else {
                        self::logToFile( "Sent gift receipt mailing with id: " . static::$giftrecieptMailingID . " to Contact ".$trans -> donation -> Contact->ID);
                    }
                }else{  //donation receipt
                
                    $receiptMailSend = -1;
                    //echo "Sending donation email";
                    $receiptMailSend = self::sendMailing(static::$donationTmpl, $trans, $pledges, static::$donationSubj);
                    if ($receiptMailSend != 1) {
                        self::logToFile( "Couldn't send receipt mailing.") ;
                    } else {
                        self::logToFile( "Sent mailing with id: " . static::$receiptMailingID . " to Contact ".$trans -> donation -> Contact->ID);
                    }
                }
                
            }

        } catch(RNCPHP\ConnectAPIError $e) {
                self::logToFile("Log", "Exception: ".$e->getMessage());
            static::$updatedNote[] =  "\n Error in mailings on " . __LINE__ . ": " . $e -> getMessage();
        }catch(\Exception $e){
            self::logToFile("Log", "PHP Exception: ".$e->getMessage());
        }

    }
    
    
    public static function sendMailing($templateName, $trans, $pledges, $subject){
        
        try{
              $filepath = static::$tmplDirectory.$templateName;
              $html = file_get_contents($filepath);
              
              $html = str_replace('[[TotalsTable]]', static::$totalsTable, $html);
              $html = str_replace('[[GiftsTotalsTable]]', static::$giftsTotalTable, $html);
                 
              if ($pledges) {
                  reset($pledges);
                  $first_key = key($pledges);
              }    
              
              if(isset($first_key) && isset($pledges[$first_key]->Child)){
                  $html = str_replace('[[ChildImage]]', self::getChildImg($pledges[$first_key]->Child->ChildRef), $html);
              } 
              preg_match_all("/\[\[(.*?)\]\]/", $html, $matches);

              foreach($matches[0] as $match){
                  
                    $variable = str_replace("[", "", $match);
                    $variable = str_replace("]", "", $variable);  
                    $parts = preg_split("/->/", $variable);

                    //this is ugly and i know it.  If you can fix properly, be my guest.
                    if (count($parts) == 1){
                        $value =  $trans->{"$parts[0]"};
                    }else if (count($parts) == 2){
                        $value = ($parts[0] == "pledge") ? $pledges[$first_key]->{"$parts[1]"} : $trans->{"$parts[0]"}->{"$parts[1]"};
                    }else if (count($parts) == 3){
                        $value = ($parts[0] == "pledge") ? $pledges[$first_key]->{"$parts[1]"}->{"$parts[2]"} : $trans->{"$parts[0]"}->{"$parts[1]"}->{"$parts[2]"};
                    }else if(count($parts) == 4){
                        $value = ($parts[0] == "pledge") ? $pledges[$first_key]->{"$parts[1]"}->{"$parts[2]"}->{"$parts[3]"} : $trans->{"$parts[0]"}->{"$parts[1]"}->{"$parts[2]"}->{"$parts[3]"};
                    }else if(count($parts) == 5){
                        $value = ($parts[0] == "pledge") ? $pledges[$first_key]->{"$parts[1]"}->{"$parts[2]"}->{"$parts[3]"}->{"$parts[4]"} : $trans->{"$parts[0]"}->{"$parts[1]"}->{"$parts[2]"}->{"$parts[3]"}->{"$parts[4]"};
                    }
                    
                    $html = str_replace($match, $value, $html);
              }

              self::logToFile("Log", "Sending Mail: Address:".$trans->donation->Contact->Emails[0]->Address." Communication Pref:".$trans->donation->Contact->CustomFields->c->preferences->ID);

              self::_createContactAttachment($html, $trans->donation->Contact, $subject);

              if($trans->donation->Contact->Emails[0]->Address != ""){
                    $mm = new RNCPHP\MailMessage();
                    $mm -> To -> EmailAddresses = array($trans->donation->Contact->Emails[0]->Address);
                    $mm -> Subject = $subject;
                    $mm -> Body -> Text = "This email contains HTML formatting.  If you wish to view this message online please log in at http://africanewlife.custhelp.com";
                    $mm -> Body -> Html = $html;
                    $mm -> Options -> IncludeOECustomHeaders = false;
                    $mm -> send();
                    unset($mm);
              }
              
              
        }catch(RNCPHP\ConnectAPIError $e) {
                echo $e->getMessage();
                self::logToFile("Log", "Exception: ".$e->getMessage());
                return 0;
        }catch(\Exception $e){
            echo $e->getMessage();
            self::logToFile("Log", "PHP Exception: ".$e->getMessage());
        }
        
        return 1;
            
        
    }
    
    /****************
     * 
     * Receipt needs to account for
     *  1. Donation with multiple Pledges
     *  2. Overpayment of a single pledge (we use internal Transaction for this scenario)
     * 
     * **************/

    public static function getPledges($donationId = 0) {
        self::logToFile( "Starting " . __FUNCTION__ . " at " . __LINE__);
        //pledge table header
        static::$totalsTable = "<table style='width:100%'><tbody><tr style='text-align:left'><th>Date</th><th>Reference</th><th>Fund</th><th>Amount</th></tr>";
        self::logToFile("Log", "Starting Get Pledges New");
        
        try {
            $roql = sprintf("SELECT financial.internalTransaction from financial.internalTransaction as intTrans where intTrans.transactionRef.donation.ID = %d order by intTrans.pledge.ID DESC", $donationId);
            //echo  "\nROQL getPledges = " . $roql;
            $ReturnedInternalTrans = RNCPHP\ROQL::queryObject($roql) -> next();
            
            $pledges = array();
            $pledgeTotalsList = array();
            $pledgeTotal = 0;
            $prevPledgeID = -1;
            $transactionDate = null;
            
            while ($intTrans = $ReturnedInternalTrans -> next()) {
                
                //for underpayments intTrans does not have an associated pledge
                if(!$intTrans->pledge){
                     $intTrans->pledge = self::getSinglePledgeObj($intTrans->transactionRef->donation->ID);
                }

                //echo  "\npledge found pledge id = " . $intTrans->pledge -> ID;
                if($intTrans->pledge->ID != $prevPledgeID && $intTrans->pledge->ID > 0){
                    //echo "\n Creating new line item for pledge -> ".$intTrans->pledge->ID;
                    $arrIndex = $intTrans->pledge->ID;
                    $pledges[$arrIndex] = new RNCPHP\donation\pledge;
                    $pledges[$arrIndex] = $intTrans->pledge;
 
                }
                $prevPledgeID = $intTrans->pledge->ID; 
                
                (isset($pledgeTotalsList[$arrIndex]) ) ? $pledgeTotalsList[$arrIndex] += floatval($intTrans->amount): $pledgeTotalsList[$arrIndex] = floatval($intTrans->amount);

                $pledgeTotal += $intTrans->amount;

                $transactionDate = ($intTrans->transactionRef->donation->DonationDate) ? date("m/d/Y", $intTrans->transactionRef->donation->DonationDate) : date("m/d/Y", $intTrans->CreatedTime);
                
            }
            
            //pledge table detail
            foreach($pledges as $pl){
                print_r($transactionDate);
                $sponReference = ($pl->Child) ? $pl->Child->ChildRef:"";
                if(empty($sponReference )){
                    $sponReference = ($pl->Woman) ? $pl->Woman->WomanRef:"";
                }
                static::$totalsTable .= "<tr><td>".$transactionDate."</td><td>".$sponReference."</td><td>".$pl->Fund->Descriptions[0]->LabelText."</td><td>".number_format($pledgeTotalsList[$pl->ID], 2, ".", "")."</td>";
            }
            
        }catch(RNCPHP\ConnectAPIError $e){
             self::logToFile("Log", "Exception: ".$e->getMessage());
            static::$updatedNote[] =  "\nException on " . __LINE__ . $e -> getMessage();
            return false;
        }catch(\Exception $e){
            self::logToFile("Log", "PHP Exception: ".$e->getMessage());
        }
        //pledge table total
        static::$totalsTable .= "<tr style='line-height: 30px;border-top; 1px solid gray'><td colspan=3>Total:</td><td>".number_format($pledgeTotal, 2, ".", "")."</td></tbody></table>";

        return $pledges;
    }


    private static function getChildImg($childRef){
            $imgPath = false;
            $childPhoto = $childRef.".JPG";
            $hashDir = substr(md5($childPhoto), 0,2);
            
            if (file_exists(static::$CHILD_IMAGE_FILESYSTEM_DIR . "/".$hashDir."/". $childRef . ".JPG")) {
                return static::$CHILD_IMAGE_URL_DIR . "/".$hashDir."/". $childRef . ".JPG";
            }
            return $imgPath;
            
    }
    
    private static function getGifts($donationId = 0) {
            self::logToFile("Log", "Starting Get Gifts");
            $giftsTotal = 0;
            static::$giftsTotalTable = "<table style='width:100%'><tbody><tr style='text-align:left'><th>Date</th><th>Gift</th><th>Quantity</th><th>Child</th><th>Amount</th></tr>";
            try {
                $roql = sprintf("select donation.DonationItem from  donation.DonationItem where donation.DonationItem.DonationId = %d", $donationId);

                $giftReturn = RNCPHP\ROQL::queryObject($roql) -> next();
                $gifts = array();
                while ($gift = $giftReturn -> next()) {
                    $gifts[] = $gift;
                    $giftDate = date("m/d/Y", $gift->CreatedTime);
                    // static::$giftsTotalTable .= "<tr><td>".$giftDate."</td><td>".$gift->Item->LookupName."</td><td>".$gift->Quantity."</td><td>".$gift->Child->FullName."</td><td>$".  number_format(($gift->Quantity * $gift->Item->Amount), 2, ".", "")."</td>";
                    // $giftsTotal += ($gift->Quantity * $gift->Item->Amount);
                    
                    static::$giftsTotalTable .= "<tr><td>".$giftDate."</td><td>".$gift->Item->LookupName."</td><td>".$gift->Quantity."</td><td>".$gift->Child->FullName."</td><td>$".  number_format(($gift->Total), 2, ".", "")."</td>";
                    $giftsTotal += $gift->Total;
                    
                }
            }catch(RNCPHP\ConnectAPIError $e){
                self::logToFile("Log", "API Exception: ".$e->getMessage());
            }catch(\Exception $e){
                self::logToFile("Log", "PHP Exception: ".$e->getMessage());
            }

            $formattedGiftTotal = ($giftsTotal > 0) ? number_format($giftsTotal, 2, ".", "") : "0.00";

            static::$giftsTotalTable .= "<tr style='line-height: 30px;border-top; 1px solid gray'><td colspan=4>Total:</td><td>$".$formattedGiftTotal."</td></tbody></table>";
            return $gifts;
        }

    public static function _createContactAttachment($htmlEmail, $contact, $subject){
        
        try{
            self::logToFile("Log", "Beginning attachment");
            $contact->FileAttachments =new RNCPHP\FileAttachmentCommonArray();
            $fattach = new RNCPHP\FileAttachmentCommon();
            $fattach->ContentType = "text/html";
            $fp = $fattach->makeFile();
            fwrite($fp,$htmlEmail);
            fclose($fp);
            $fattach->FileName = substr($subject, 0, 95).".html";
            $fattach->Name = substr($subject, 0, 40);        
            $contact->FileAttachments[] = $fattach;
            $contact->save(RNCPHP\RNObject::SuppressAll);
            self::logToFile( "Finished Attachment");
        }catch(RNCPHP\ConnectAPIError $e){
            self::logToFile("Log", "API Exception: ".$e->getMessage());
        }catch(\Exception $e){
            self::logToFile("Log", "PHP Exception: ".$e->getMessage());
        }
    }

    public static function getSinglePledgeObj($donationId = 0) {
        try {
            $roql = sprintf("SELECT don.PledgeRef FROM donation.donationToPledge as don where donation.donationToPledge.DonationRef.ID = %d", $donationId);
            $pages = RNCPHP\ROQL::queryObject($roql) -> next();
            $pledges = array();
            while ($pledge = $pages -> next()) {
                return $pledge;
            }
        } catch(\Exception $e) {
            return false;
        }

        return null;
    }

    public static function logToFile($action, $message = null){

        if(isset($message)){

            if (!is_dir('/tmp/mailingLogs')){
                $oldumask = umask(0);
                mkdir('/tmp/mailingLogs', 0775, true);
                umask($oldumask);
            }

            $logFile = fopen("/tmp/mailingLogs/mailingHelperLog_".date("Y_m_d").".log", "a");
            fwrite($logFile, date("Y-m-d H:i:s  "). $message."\n");
            fwrite($logFile, "Memory Usage:".memory_get_usage()."  Peak Memory Usage: ".memory_get_peak_usage()." \n\n");
            fclose($logFile);
        }
        
    }
    
    //recursive functiont to trigger load of values due to lazy loading
    private function _getValues($parent) {
        try {
            // $parent is a non-associative (numerically-indexed) array
            if (is_array($parent)) {

                foreach ($parent as $val) {
                    self::_getValues($val);
                }
            }

            // $parent is an associative array or an object
            elseif (is_object($parent)) {

                while (list($key, $val) = each($parent)) {

                    $tmp = $parent->$key;

                    if ((is_object($parent->$key)) || (is_array($parent->$key))) {
                       self::_getValues($parent->$key);
                    }
                }
            }
        } catch (\Exception $err) {
            // error but continue
        }
    }
    
    
   
    

}

/*
 The Test Harness
 */
class mailinghelper_Create2_TestHarness
implements RNCPM\ObjectEventHandler_TestHarness {
    
    static $mailling_helper = NULL;
    
    public static function setup() {
        
        return;
    }

    /**
     *
     *
     */
    public static function fetchObject($action, $object_type) {
             
        // $mailinghelper = new RNCPHP\helpers\mailinghelper;
        // $mailinghelper -> transaction = 729610;
        // $mailinghelper -> save();
//          
        static::$mailling_helper = RNCPHP\helpers\mailinghelper::fetch(504106);
        return static::$mailling_helper;
       
    }

    /**
     *
     *
     */
    public static function validate($action, $object) {

        

        return true;
    }



    /**
     *
     *
     */
    public static function cleanup() {
        // Destroy every object invented
        // by this test.
        // Not necessary since in test
        // mode and nothing is committed,
        // but good practice if only to
        // document the side effects of
        // this test.

        if (!empty(static::$mailling_helper->ID)) {
          static::$mailling_helper -> destroy();
        }
        static::$mailling_helper = NULL;
        return;
    }

}
