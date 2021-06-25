<?php

// Find our position in the file tree
if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

/************* Agent Authentication ***************/

// Set up and call the AgentAuthenticator
require_once (DOCROOT . '/include/services/AgentAuthenticator.phph');
$session = AgentAuthenticator::authenticateCookie();

use RightNow\Connect\v1_3 as RNCPHP;

class paymentapi {

    const LOG_DIR = '/tmp/';
    const LOG_FILE_BASE_NAME = 'PledgeDriveErrors_';

    function logMessage($msg){
        // Put into unique file per day
        $fileName = self::LOG_DIR . self::LOG_FILE_BASE_NAME . date('Y-m-d') . '.txt';
        $timestamp = date('H:i:s') . ': ';
        $result = file_put_contents($fileName, $timestamp . $msg . "\n\n", FILE_APPEND);
        if($result === false) throw new \Exception("Failed to write to log file. File name = $fileName. Timestamp = $timestamp. Msg = $msg.");
    }

    function createpledge() {
        
        $formData = $_POST['formData'];
        
        try{
            $pledge = new RNCPHP\donation\pledge;
            
            //print_r($formData);
          
            $contactData = self::_getContact($formData['first'], $formData['last'], $formData['email'], $formData['street'], $formData['city'], $formData['state'], $formData['postal'], $formData['phone'], intval($formData['emailpref']));
            $pledge -> Contact = $contactData[0];
            $pledge -> StartDate = strtotime($formData['start']);
            $pledge -> NextTransaction = strtotime($formData['start']);
            if($formData['stop'] && $formData['stop'] !== "0"){
                $pledge -> StopDate = strtotime($formData['stop']);
            }
            
            $formData['amount'] = str_replace(',', '', $formData['amount']);
            $formData['amount'] = str_replace('$', '', $formData['amount']);
            $pledge -> PledgeAmount = number_format($formData['amount'], 2, '.', '');
            $status = ($formData['check'] == 'true') ? 43 : 1; //manual pay/active 
            $pledge -> PledgeStatus = RNCPHP\donation\PledgeStatus::fetch($status);//active
            $pledge -> Fund = RNCPHP\donation\fund::fetch(cfg_get(CUSTOM_CFG_pledge_entry_fund));
            $appeal = RNCPHP\donation\Appeal::fetch(intval($formData['appeal']));
            $pledge -> Appeals = $appeal;
            $pledge -> Descr = $appeal->Descriptions[0]->LabelText;
            $freqID = ($formData['frequency'] == "1") ? 9 : 5; //monthly or one time
            $pledge -> Frequency = RNCPHP\donation\DonationPledgeFreq::fetch($freqID);
            $type = ($formData['frequency'] == "1") ? 3 : 2; //recurring or one time
            $pledge -> Type1 = RNCPHP\donation\Type::fetch($type);
            if ($formData['notes'] != ""){
                $pledge->Notes = new RNCPHP\NoteArray();
                $pledge->Notes[0] = new RNCPHP\Note();
                $pledge->Notes[0]->Text = $formData['notes'];
            }
            
            foreach($contactData[1] as $mismatch){
                if($mismatch != ""){
                    $noteString .= $mismatch . "\n";
                }
            }
            
            if ($noteString){
                $noteCount = count($pledge->Notes);
                if($noteCount == 0){
                    $pledge->Notes = new RNCPHP\NoteArray();
                }
                $pledge->Notes[$noteCount] = new RNCPHP\Note();
                $pledge->Notes[$noteCount]->Text = $noteString;
            }
                    
            $pledge -> save(RNCPHP\RNObject::SuppressAll);
        }catch(\Exception $e){
            $this->logMessage($e -> getMessage());
            throw $e;
        }
        //if the start date is in the future we are going to 
        //not create the donation/transaction
        //instead charge a nominal amount to the card
        //save the payment method on the pledge and let the nightly cron process the pledge
        //reverse the nominal charge
        $createData['futureDate'] = ( strtotime($formData['start']) == strtotime(date("m/d/Y")) ) ? false : true;//08/04/2016
        
        if ($formData['check'] == 'false' && !$createData['futureDate']){
            $transaction = self::create_transaction($pledge -> Contact -> ID, $formData['amount'], "Pledge Drive Agent Initiated Transaction", null);
            $donationId = self::createDonation($formData['amount'], $pledge -> Contact, $transaction, null, $pledge);
        }

        $createData['Contact'] = $pledge -> Contact -> ID;
        $createData['Pledge'] = $pledge -> ID;
        $createData['Transaction'] = ($createData['futureDate']) ? "NewPM-".$pledge -> ID : $transaction -> ID;
        $createData['isCheck'] = $formData['check'];
        
        return $createData;
        
    }


    public function create_transaction($c_id, $amt, $desc = null, $donationId = null) {
        
        $desc = addslashes($desc);
        if (strlen($desc) > 254) {
            $desc = substr($desc, 0, 251) . "...";
        }
        try {
            $trans = new RNCPHP\financial\transactions;
            $trans -> currentStatus = RNCPHP\financial\transaction_status::fetch("Pending - Web Initiated");

            $trans -> totalCharge = number_format($amt, 2, '.', '');
            $trans -> contact = intval($c_id);
            $trans -> description = is_null($desc) ? "Pledge Drive Agent Initiated Transaction" : $desc;
            if (!is_null($donationId)) {
                $trans -> donation = intval($donationId);
            }
            $trans -> save(RNCPHP\RNObject::SuppressAll);
        } catch(\Exception $e) {
            $this->logMessage($e -> getMessage());
            return $e->getMessage();
        }catch(RNCPHP\ConnectAPIError $e) {
            $this->logMessage($e -> getMessage());
            return $e->getMessage();   
            }
        return $trans;

    }


    private static function _getContact($first, $last, $email, $address, $city, $state, $zip, $phone, $emailpref = null) {
        
        
        $contactMismatches = array();
        $contact = RNCPHP\Contact::first("Contact.Emails.EmailList.Address='$email'");
        
        if (!$contact) {
            try{
                $contact = new RNCPHP\Contact();
            
                $contact->Name = new RNCPHP\PersonName();
                $contact->Name->First = $first;
                $contact->Name->Last = $last;
                
                if(!empty($address) && !empty($city) && !empty($state) && !empty($zip)){
                    $contact->Address = new RNCPHP\Address();
                    $contact->Address->Street = $address;
                    $contact->Address->City = $city;
                    $contact->Address->StateOrProvince = new RNCPHP\NamedIDLabel();
                    $contact->Address->StateOrProvince->ID = intval($state);
                    $contact->Address->Country = RNCPHP\Country::fetch("US");
                    $contact->Address->PostalCode = $zip;
                }
              
                if(!empty($phone)){
                    $contact->Phones = new RNCPHP\PhoneArray();
                    $contact->Phones[0] = new RNCPHP\Phone();
                    $contact->Phones[0]->PhoneType = new RNCPHP\NamedIDOptList();
                    $contact->Phones[0]->PhoneType->LookupName = 'Home Phone';
                    $contact->Phones[0]->Number = preg_replace('/[^0-9,.]+/i', '', $phone);
                }

                if ($email){
                    $contact->Emails = new RNCPHP\EmailArray();
                    $contact->Emails[0] = new RNCPHP\Email();
                    $contact->Emails[0]->AddressType=new RNCPHP\NamedIDOptList();
                    $contact->Emails[0]->AddressType->LookupName = "Email - Primary";
                    $contact->Emails[0]->Address = self::_getGoodEmail($email);
                    $contact->Login = $email;
                }

                if(!empty($emailpref)){
                    $contact->CustomFields->c->preferences = new RNCPHP\NamedIDLabel();
                    $contact->CustomFields->c->preferences->ID = $emailpref;
                }

                $contact->save();
            }catch(\Exception $e){
                $this->logMessage($e -> getMessage());
                throw $e;
            }
        }else{

            $contactMismatches[] = ($contact->Name->First != $first) ? "FIRST NAME CHANGED: ".$contact->Name->First." TO ".$first : "" ;
            $contactMismatches[] = ($contact->Name->Last != $last) ? "LAST NAME CHANGED: ".$contact->Name->Last." TO ".$last : "" ;
            $contactMismatches[] = ($contact->Address->Street != $address) ? "ADDRESS CHANGED: ".$contact->Address->Street." TO ".$address : "" ;
            $contactMismatches[] = ($contact->Address->City != $city) ? "CITY CHANGED: ".$contact->Address->City." TO ".$city : "" ;
            $contactMismatches[] = ($contact->Address->StateOrProvince->ID != $state) ? "STATE CHANGED: ".$contact->Address->StateOrProvince->ID." TO ".$state : "" ;
            $contactMismatches[] = ($contact->Address->PostalCode != $zip) ? "POSTAL CODE CHANGED: ".$contact->Address->PostalCode." TO ".$zip : "" ;
            $contactData[1] = $contactMismatches;

            // Also update the contact in addition to auditing the changes
            try{
                $contact->Name->First = $first;
                $contact->Name->Last = $last;
                if(!empty($phone)){
                    if(count($contact->Phones) == 0){
                        $contact->Phones = new RNCPHP\PhoneArray();
                        $contact->Phones[0] = new RNCPHP\Phone();
                        $contact->Phones[0]->PhoneType = new RNCPHP\NamedIDOptList();
                        $contact->Phones[0]->PhoneType->LookupName = 'Mobile Phone';
                    }
                    $contact->Phones[0]->Number = $phone;
                }
                if(is_null($contact->Address)){
                    $contact->Address = new RNCPHP\Address();
                }
                $contact->Address->Street = $address;
                $contact->Address->City = $city;
                $contact->Address->Country = RNCPHP\Country::fetch("US");
                if(!empty($state)){
                    $contact->Address->StateOrProvince = new RNCPHP\NamedIDLabel();
                    $contact->Address->StateOrProvince->ID = intval($state);
                }
                $contact->Address->PostalCode = $zip;
                if(!empty($emailpref)){
                    $contact->CustomFields->c->preferences = new RNCPHP\NamedIDLabel();
                    $contact->CustomFields->c->preferences->ID = $emailpref;
                }
                $contact->save();
            }catch(\Exception $e){
                $this->logMessage($e -> getMessage());
                throw $e;
            }
        }
        
        $contactData[0] = $contact;
        
        
        return $contactData;
    }



    private static function _getGoodEmail($email){
  
        $pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$"; 
    
         if (eregi($pattern, $email)){ 
            return $email;
         } 
         else { 
            $email = null;
            throw new \Exception('Invalid email address.');
         }    
        
         
        return $email;
    }
    
    function processResult(){
        try{
            $cleanGetData = array();
            foreach ($_GET as $key => $value) {
                $cleanGetData[addslashes($key)] = addslashes($value);
            }
            
            $newpaymeth = explode("-", $cleanGetData['InvoiceNumber']);
            $transactionId = ($newpaymeth[0] == "NewPM") ? null : $cleanGetData['InvoiceNumber'];
            
            //retrieve the transaction
            //if no transaction was created then get the contact to attribute the pay method to
            if($transactionId){
                $transaction = RNCPHP\financial\transactions::fetch($transactionId);
            }else{
                $pledge = RNCPHP\donation\pledge::fetch($newpaymeth[1]);
            }
            
            
            $contactId = ($transaction->ID > 0) ? $transaction->contact->ID : $pledge->Contact->ID;

            
            if (isset($cleanGetData['StatusCode']) && $cleanGetData['StatusCode'] == 0) {
                
                if ($cleanGetData['CardType'] != "Checking") {
                    $newPayment = self::createPaymentMethod($contactId, $cleanGetData['CardType'], $cleanGetData['PNRef'], "Credit Card", $cleanGetData['AccountExpMonth'], $cleanGetData['AccountExpYear'], $cleanGetData['AccountLastFour']);
                    if(!$newPayment){echo "Failed while creating a new payment";}
                } else {
                    $newPayment = self::createPaymentMethod($contactId, $cleanGetData['CardType'], $cleanGetData['PNRef'], "EFT", null, null, $cleanGetData['AccountLastFour']);
                    if(!$newPayment){echo "Failed while creating a new payment";}
                }
                
                if($transaction){
                    $transactionId = self::update_transaction($transaction, 'Completed', $newPayment, $cleanGetData['PNRef']);
                    
                    if($transactionId <= 0){
                        echo "There was a problem with the transaction.  Please try again.";
                    }
                }
                      
                //also have to update pledge w payment info
                //if we did not create a transaction we know this was an approval for a paymethod to attach to a pledge, so, do a reversal
                if($pledge){
                    
                    $pledge->paymentMethod2 = $newPayment;
                    $pledge->save(RNCPHP\RNObject::SuppressAll);
                    $transType = ($cleanGetData['CardType'] == "Checking") ? "check" : "card";
                    $result = self::ReversePayment($cleanGetData['PNRef'], $transType);

                    
                }else{
                    $pledge = self::update_pledge($transaction, $newPayment);
                }
                 
                if($pledge->ID > 0){
                    echo "The transaction is complete.  Please start a new pledge to continue.";
                }else{
                    echo "There was a problem with the transaction.  Please try again.";
                }  
                   
            }else{
                
                echo "There was an error processing the transaction";
                
            }
        }catch(\Exception $e){
            $this->logMessage($e -> getMessage());
            throw $e;
        }
    }

    public function createDonation($amt, $contact, $transaction, $paymentMethod, $pledge) {

        
        $donation = self::savedonation($amt, $contact);
        if ($donation -> ID < 1) {
            echo "Failed to create donation";
            return false;
        }

        if(!self::addPledgeToDonation($donation, $pledge) ){
            echo "Failed to add pledge to donation";
        }

        //add donation to transaction
        if(!self::addDonationToTransaction($transaction, $donation)){
            echo "Failed to add donation to transaction";
        }
        
        return $donationId;
    }

    public function savedonation($amt, $contact) {
        try {
            $newDonation = new RNCPHP\donation\Donation;
            $newDonation -> Contact = $contact;
            $newDonation -> DonationDate = time();
            $newDonation -> Amount = number_format($amt, 2, '.', '');
            //Set the donation.Donation.PaymentSource = "EndUser", which is the third menu sel
            $newDonation -> PaymentSource = RNCPHP\donation\paymentSourceMenu::fetch(1); //set it to agent even though its from the web.              
            $newDonation -> Type = RNCPHP\donation\Type::fetch(1);//always a pledge
            //set the type  in th future the cart may accept more than one item type so this may need to be changed.
            $newDonation -> save(RNCPHP\RNObject::SuppressAll);
        } catch(Exception $e) {
            $this->logMessage($e -> getMessage());
            return 0;
        }
        return $newDonation;
    }
    
    public function addPledgeToDonation($donation, $pledge) {

            $donation2Pledge = new RNCPHP\donation\donationToPledge();
            $donation2Pledge -> PledgeRef = $pledge -> ID;
            $donation2Pledge -> DonationRef = $donation -> ID;

            try {
                $donation2Pledge -> save(RNCPHP\RNObject::SuppressAll);
            } catch(Exception $e) {
                $this->logMessage($e -> getMessage());
                return false;
            }

            return true;
    }
    
    public function addDonationToTransaction($transaction, $donation) {

        if ($transaction == false) {
            return false;
        }
        try {
            $transaction -> donation =  $donation;
            $transaction -> save(RNCPHP\RNObject::SuppressAll);
        } catch(Exception $e) {
            $this->logMessage($e -> getMessage());
            return false;
        }
        return true;
    }
    
    private static function createPaymentMethod($c_id, $cardType = null, $pn_ref = null, $paymentMethodType = null, $expMonth = null, $expYear = null, $lastFour = null) {
        
        $pId = -1;

        if (is_null($c_id) || !is_numeric($c_id) || $c_id < 1) {
            return -1;
        }
        try {
            $newPM = new RNCPHP\financial\paymentMethod;
            $newPM -> Contact = $c_id;
            if (!is_null($cardType)) {
                $newPM -> CardType = $cardType;
            }
            if (!is_null($pn_ref)) {
                $newPM -> PN_Ref = $pn_ref;
            }
            if (!is_null($paymentMethodType)) {
                $newPM -> PaymentMethodType = RNCPHP\financial\paymentMethodType::fetch($paymentMethodType);
            }
            if (!is_null($expMonth)) {
                $newPM -> expMonth = $expMonth;
            }
            if (!is_null($expYear)) {
                $newPM -> expYear = $expYear;
            }
            if (!is_null($lastFour)) {
                $newPM -> lastFour = $lastFour;
            }
            $pId = $newPM -> save();
            
            

        } catch(Exception $e) {
            $this->logMessage($e -> getMessage());
            return false;
        }
        
        return $newPM;
    }

    public function update_transaction($transaction,  $statusString = null, $paymentMethod, $PNRef = null) {
        
        
        try {
            
            if (!$transaction instanceof RNCPHP\financial\transactions) {
                return false;
            }
            
            $transaction -> currentStatus = RNCPHP\financial\transaction_status::fetch(1);
            
            
            if (!is_null($statusString) && strlen($statusString) > 0) {
                $transaction -> currentStatus = RNCPHP\financial\transaction_status::fetch($statusString);
            }
            
            
            if (!is_null($paymentMethod) && $paymentMethod -> ID > 0) {
                $transaction -> paymentMethod = $paymentMethod;
            }
            
           
            if(!is_null($PNRef) ){
                $transaction -> refCode = $PNRef;
            }
 
            if ($statusString == 'Completed') {
                $transaction -> save();
            } else {
                $transaction -> save(RNCPHP\RNObject::SuppressAll);
            }
            
            
   

        } catch(\Exception $e) {
            $this->logMessage($e -> getMessage());
            echo $e->getMessage();
        }catch(RNCPHP\ConnectAPIError $e) {
            $this->logMessage($e -> getMessage());
            echo $e->getMessage();   
        }
        
        return $transaction -> ID;

    }

    function update_pledge($transaction, $paymentMethod){
        try{
            $roql = "select Donation.donationToPledge.PledgeRef from donation.donationToPledge where Donation.donationToPledge.DonationRef.ID = ".$transaction->donation->ID;
        
            $res = RNCPHP\ROQL::queryObject( $roql )->next();
            
            while($pledge = $res->next()) {
                
                $pledge->paymentMethod2 = $paymentMethod;
                $pledge->save(RNCPHP\RNObject::SuppressAll);
                return $pledge;
            }
        }catch(\Exception $e){
            $this->logMessage($e -> getMessage());
        }
    }

    private static function ReversePayment($pnref, $transType) {
            
        load_curl();
        
        try {
            if ($pnref) {

                if ($transType == 'card') {
                    $submissionVals = array(
                        'Amount' => '',
                        'TransType' => 'Reversal',
                        'PNRef' => $pnref,
                        'op' => "ArgoFire/transact.asmx/ProcessCreditCard",
                        'MagData' => '',
                        'ExtData' => '',
                        'CardNum' => '',
                        'ExpDate' => '',
                        'CVNum' => '',
                        'InvNum' => '',
                        'NameOnCard' => '',
                        'Zip' => '',
                        'Street' => ''
                    );
                } else {
                    $submissionVals = array(
                        'Amount' => '',
                        'TransType' => 'Void',
                        'op' => "ArgoFire/transact.asmx/ProcessCheck",
                        'CheckNum' => '',
                        'TransitNum' => '',
                        'AccountNum' => '',
                        'NameOnCheck' => '',
                        'MICR' => '',
                        'DL' => '',
                        'SS' => '',
                        'DOB' => '',
                        'StateCode' => '',
                        'CheckType' => '',
                        'ExtData' => '<PNRef>' . $pnref . '</PNRef>'
                    );
                }
            } else {
                return false;
            }

           
            $returnVal = self::runTransaction($submissionVals);

            if ($returnVal['isSuccess'] === true) {
                return true;
            } else {
                echo "The Pledge was created correctly but, there was an error refunding the approval amount in front stream.  Please notify joanne@africanewlife.org.";
            }

        } catch(Exception $e) {
            $this->logMessage($e -> getMessage());
        }

    }

    function runTransaction(array $postVals) {
       //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912
       $host = cfg_get(CUSTOM_CFG_frontstream_endpoint);
       $pass = cfg_get(CUSTOM_CFG_frontstream_pass);
       $user = cfg_get(CUSTOM_CFG_frontstream_user);
    
       $host .= "/" . $postVals['op'];
    
       $mybuilder = array();
       foreach ($postVals as $key => $value) {
           $mybuilder[] = $key . '=' . $value;
       }
    
       $mybuilder[] = 'username=' . $user;
       $mybuilder[] = 'password=' . $pass;
        
       $result = self::runCurl($host, $mybuilder);
       
       if ($result == false) {
           //_output("121 - Unable to run transaction");
           return false;
       }

    
       $transType = ($postVals['op'] == "/ArgoFire/transact.asmx/ProcessCheck")? "check" : "credit";
       
       return self::parseFrontStreamRespOneTime($result, $transType);
    
    
    }  
    
    function runCurl($host, array $postData) {
       try {
           // Initialize Curl and send the request
           $ch = curl_init();
           curl_setopt($ch, CURLOPT_URL, $host);
           curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
           curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
           curl_setopt($ch, CURLOPT_POST, 1);
           curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $postData));
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
           $result = curl_exec($ch);
    
           if (curl_errno($ch) > 0) {
               curl_close($ch);
               return false;
           } else if (strpos($result, "HTTP Error") !== false) {
               curl_close($ch);
               return false;
           }
           
           
       } catch(Exception $e) {
            $this->logMessage($e -> getMessage());
           curl_close($ch);
           return false;
       }catch(RNCPHP\ConnectAPIError $e) {
            $this->logMessage($e -> getMessage());
            return false;  
        }
       curl_close($ch);
       
       return $result;
    }  
    
    function parseFrontStreamRespOneTime($result, $transType) {
            
        $xmlparser = xml_parser_create();
        xml_parse_into_struct($xmlparser, $result, $values, $indices);
        xml_parser_free($xmlparser);

        $frontStreamResponse = array();
        $frontStreamResponse['resultCode'] = $values[$indices['RESULT'][0]]['value'];
        $frontStreamResponse['responseMsg'] = $values[$indices['RESPMSG'][0]]['value'];
        $frontStreamResponse['message'] = $values[$indices['MESSAGE'][0]]['value'];
        $frontStreamResponse['pnRef'] = $values[$indices['PNREF'][0]]['value'];

        $frontStreamResponse['rawXml'] = $result;
        $frontStreamResponse['parsedXml'] = $values;

        if ($frontStreamResponse['resultCode'] == 0) {
            $frontStreamResponse['isSuccess'] = true;
        } else {
            $frontStreamResponse['isSuccess'] = false;
        }

        return $frontStreamResponse;
    }    
    
    function getContactList(){
        $firstName = $_POST['first'];
        if(empty($firstName)) $firstName = '';
        $lastName = $_POST['last'];
        if(empty($lastName)) $lastName = '';
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $street = $_POST['street'];

        $query = "SELECT Contact FROM Contact";
        $whereCreated = false;
        if(!empty($firstName)){
            if(!$whereCreated){
                $query .= " WHERE Name.First = '$firstName'";
                $whereCreated = true;
            }else{
                $query .= " AND Name.First = '$firstName'";
            }
        }
        if(!empty($lastName)){
            if(!$whereCreated){
                $query .= " WHERE Name.Last = '$lastName'";
                $whereCreated = true;
            }else{
                $query .= " AND Name.Last = '$lastName'";
            }
        }
        if(!empty($email)){
            if(!$whereCreated){
                $query .= " WHERE Emails.Address = '$email'";
                $whereCreated = true;
            }else{
                $query .= " AND Emails.Address = '$email'";
            }
        }
        if(!empty($phone)){
            if(!$whereCreated){
                $query .= " WHERE Phones.Number LIKE '%$phone%'";
                $whereCreated = true;
            }else{
                $query .= " AND Phones.Number LIKE '%$phone%'";
            }
        }
        if(!empty($street)){
            if(!$whereCreated){
                $query .= " WHERE Address.Street LIKE '%$street%'";
                $whereCreated = true;
            }else{
                $query .= " AND Address.Street LIKE '%$street%'";
            }
        }

        // Construct ORDER BY clause
        if(!empty($firstName) && empty($lastName)){
            $orderby = ' ORDER BY Name.Last ASC';
        }elseif(!empty($lastName) && empty($firstName)){
            $orderby = ' ORDER BY Name.First ASC';
        }elseif(!empty($lastName) && !empty($firstName)){
            $orderby = ' ORDER BY Name.Last ASC, Name.First ASC';
        }else{
            $orderby = ' ORDER BY Name.Last ASC, Name.First ASC';
        }
        $query .= $orderby;

        $contacts = array();
        $seenContacts = array();

        try{
            if(!$whereCreated) throw new \Exception('No filter criteria was specified.');
            if(!empty($phone) && strlen($phone) < 3) throw new \Exception('Please specify at least 3 digits of the phone number.');
            if(!empty($street) && strlen($street) < 3) throw new \Exception('Please specify at least 3 characters of the street name/number.');

            $result = RNCPHP\ROQL::queryObject($query)->next();

            while($contact = $result->next()){
                if(!array_key_exists($contact->ID, $seenContacts)){
                    $seenContacts[$contact->ID] = 1;
                    $contacts[] = array(
                        'ID' => $contact->ID,
                        'FirstName' => $contact->Name->First,
                        'LastName' => $contact->Name->Last,
                        'Email' => $contact->Emails[0]->Address,
                        'Phone' => count($contact->Phones) > 0 ? $contact->Phones[0]->Number : '',
                        'Street' => $contact->Address->Street,
                        'City' => $contact->Address->City,
                        'StateOrProvince' => $contact->Address->StateOrProvince->ID,
                        'PostalCode' => $contact->Address->PostalCode,
                        'EmailPref' => $contact->CustomFields->c->preferences->ID
                    );
                }
            }
        }catch(\Exception $e){
            $this->logMessage($e -> getMessage());
            return array('status' => 'error', 'msg' => $e->getMessage());
        }

        if(count($contacts) == 0){
            if(!empty($email)){
                try{
                    $contactInfo = self::_getContact($firstName, $lastName, $email, null, null, null, null, $phone);
                    $newContact = $contactInfo[0];

                    $contacts[] = array(
                        'ID' => $newContact->ID,
                        'FirstName' => $newContact->Name->First,
                        'LastName' => $newContact->Name->Last,
                        'Email' => $newContact->Emails[0]->Address,
                        'Phone' => count($newContact->Phones) > 0 ? $newContact->Phones[0]->Number : '',
                        'Street' => $newContact->Address->Street,
                        'City' => $newContact->Address->City,
                        'StateOrProvince' => $newContact->Address->StateOrProvince->ID,
                        'PostalCode' => $newContact->Address->PostalCode,
                        'EmailPref' => $contact->CustomFields->c->preferences->ID
                    );
                }catch(\Exception $e){
                    $this->logMessage($e -> getMessage());
                    return array('status' => 'error', 'msg' => $e->getMessage());
                }
            }else{
                return array('status' => 'error', 'msg' => 'No matching contacts and insufficient data for creating new contact. Email required.');
            }
        }

        return array('status' => 'success', 'contacts' => $contacts);
    }                  
}

$response = new \stdClass;


switch(strip_tags(strtolower($_GET['action']))) {
    case 'createpledge' :
        try {
            $response -> data = paymentapi::createpledge();
        } catch (\Exception $error) {
            $response -> error = $error -> getMessage();
        }
        break;
    case 'transactionreply' :
        paymentapi::processResult();
        break;
    case 'getcontactlist' :
        $response = paymentapi::getContactList();
        break;
    case 'errorlocaion' :
        echo "there was an error with the transaction.  Please start over.";
        break;
    case 'logmessage':
        try{
            paymentapi::logMessage($_POST['msg']);
        }catch(\Exception $e){
            $response->error = $e->getMessage();
        }
        break;
    default :
        $response -> error = sprintf("Unkown Action '%s'", strip_tags($_GET['action']));
        break;
}

echo json_encode($response);
