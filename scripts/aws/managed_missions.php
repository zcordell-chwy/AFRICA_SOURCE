<?php

/**Deployment Notes
 * 
 * CBO: Trip.managedMissionsId, TripMember.managedMissionsId, Donation.TripMember, Donation.managedMissionsId, Contact.managedMissionsId, Contact.managedMissionsPersonId
 * 
 * Custom Menu: paymentSourceMenu: Add MangedMissions and put in define
 * 
 * CFG: CUSTOM_CFG_MANAGED_MISSIONS_API_ENDPOINT, CUSTOM_CFG_MANAGED_MISSIONS_TRIP_API_ENDPOINT, CUSTOM_CFG_MANAGED_MISSIONS_PERSON_API_ENDPOINT
 * 
 * AWS: Lambda, cloudwatch event
 * 
 * Account: Create account and update lambda with credentials
 * 
 * Create appeal and update STM_APPEAL_ID
 * 
 * Reports: put in filter for "reciepts" to not send receipts.
 * 
 * Files: network_utilities, managed_missions
 * 
 */

use RightNow\Connect\v1_3 as RNCPHP;

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

if (!defined('SCRIPT_PATH')) {
    $scriptPath  = ($debug) ? DOCROOT . '/custom/src' : DOCROOT . '/custom';
    define('SCRIPT_PATH', $scriptPath);
}

/*Api Constants*/
define('ALLOW_POST', true);
define('ALLOW_GET', false);
define('ALLOW_PUT', false);
define('ALLOW_PATCH', false);

/*Application Constants*/
define('STM_FUND_ID', 89);
define('PAY_SOURCE', 89);
define('PLEDGE_DESC', "Manged Missions payment for");
define('TRANSACTION_SALE_SUCCESS_STATUS_ID', 3);
define('STM_APPEAL_ID', 2223);


require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';
require_once SCRIPT_PATH . '/utilities/network_utilities.php';


try {
    $mm = new managedMissions();
    $mm->beginExecution();

} catch (\Exception $ex) {
    return outputResponse(null, $ex->getMessage());
} catch (RNCPHP\ConnectAPIError $ex) {
    return outputResponse(null, $ex->getMessage());
}
/**
 *
 */
class managedMissions
{

    private $executionSummary;

    public function __construct()
    {
        $this->executionSummary[] = "Begin Managed Missions Script ".date('Y-m-d');
        //$this->getTransactionsEndpoint = str_replace('{DATE}', '2021-05-10', RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_API_ENDPOINT')->Value);
        $this->getTransactionsEndpoint = str_replace('{DATE}', date('Y-m-d', strtotime('Midnight today')), RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_API_ENDPOINT')->Value);
        $this->getTripMemberEndpoint = RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_PERSON_API_ENDPOINT')->Value;
        $this->getTripEndpoint = RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_TRIP_API_ENDPOINT')->Value;
        $this->executionSummary[] = $this->getTransactionsEndpoint;
        
    }

    public function beginExecution(){


        $response = network_utilities\runCurl($this->getTransactionsEndpoint, "GET", null, array());
    
        if(!$response){
            outputResponse($this->executionSummary, 'Failed to get response from MM Api', '500');
        }else{
            $parsedResults = json_decode($response);
        }

        foreach($parsedResults->data as $donation){

            $goToNext = false;

            $existingDonation = RNCPHP\donation\Donation::find("donation.Donation.managedMissionsId = '".$donation->Id."'");
            foreach($existingDonation as $key => $val){
                //already exists
                $goToNext = true;
            }

            //if we already have a donation skip it.
            if($goToNext){
                continue;
            }

            $validation = $this->validatePayload($donation);

            if(empty($validation))
                $this->processDonation($donation);
            else{
                $this->executionSummary[] = "Contribution ".$donation->Id." failed to pass validation";
                $this->executionSummary[] = print_r($validation, true);
            }
            
        }

        return outputResponse($this->executionSummary, null);
    }

    /**
     * 
     * Todo fill this in during testing if needed.
     */
    private function validatePayload($mmDonation){
        $errors = array();

        if(!isset($mmDonation->PersonId)){
            $errors[] = "PersonId not set";
        }

        return $errors;

    }
    /**
     * 
     * Process Results: Create Donor(Contact), TeamTrips.TripMember(Contact), TeamTrips.Trip, one time pledge, donation, transaction
     * 
     */
    private function processDonation($mmDonation){

        $names = explode(" ", $mmDonation->DonorName);
        $tripMemberNames = explode(" ", $mmDonation->PersonName);

        //donor info
        $donor = $this->getContact($mmDonation->DonorId, null, $names[0], $names[1], $mmDonation->Address1, $mmDonation->City, $mmDonation->State, $mmDonation->PostalCode, $mmDonation->PhoneNumber, $mmDonation->EmailAddress);

        //trip member info
        $tripMemberResponse = network_utilities\runCurl( str_replace('{ID}', $mmDonation->PersonId, $this->getTripMemberEndpoint), "GET", null, array());
        if(!$tripMemberResponse){
            outputResponse($this->executionSummary, 'Failed to get response from MM Person Api url:'.str_replace('{ID}', $mmDonation->PersonId, $this->getTripMemberEndpoint), '500');
        }else{
            $tripMemberResults = json_decode($tripMemberResponse);
        }
        $tripContact = $this->getContact(null, $mmDonation->PersonId, $tripMemberResults->data->FirstName, $tripMemberResults->data->LastName, $tripMemberResults->data->Address1, $tripMemberResults->data->City, $tripMemberResults->data->State, $tripMemberResults->data->PostalCode, $tripMemberResults->data->PhoneNumber, $tripMemberResults->data->EmailAddress);

        // //find/create Trip 
        $tripResponse = network_utilities\runCurl( str_replace('{ID}', $mmDonation->MissionTripId, $this->getTripEndpoint), "GET", null, array());
        if(!$tripResponse){
            outputResponse($this->executionSummary, 'Failed to get response from MM Trip Api url:'.str_replace('{ID}', $mmDonation->MissionTripId, $this->getTripEndpoint), '500');
        }else{
            $tripResults = json_decode($tripResponse);
        }
        $trip = $this->getTrip($mmDonation->MissionTripId, $mmDonation->MissionTripName, $this->cleanDate($tripResults->data->DepartureDate), $this->cleanDate($tripResults->data->ReturnDate));

        // //find/create TripMember
        $tripMember = $this->getTripMember($mmDonation->PersonId, $tripContact, $trip);

        // //create Donation
        $donation = $this->createDonation($mmDonation->Id, $mmDonation->ContributionAmount, $donor, $tripMember);

        // //create one time pledge
        $pledge = $this->createPledge($donation, $mmDonation->ContributionAmount, $donor, $mmDonation->PersonName);

        // //created completed transaction
        //todo make sure this doesn't send a receipt
        $transaction = $this->createTransaction($donor, $mmDonation->ContributionAmount, null, $donation );

        return true;

    }

    /**
     * 
     * 
     * 
     */
    private function createPledge($donation, $amt, $contact, $tripMember){
        
        $this->executionSummary[] = "Beginning create pledge";
        try {
            $pledge = new RNCPHP\donation\pledge();
            
            if (!$donation instanceof RNCPHP\donation\Donation) {
                $this->executionSummary[] = "returning false on donation ";
                return false;
            }

            $pledge = new RNCPHP\donation\pledge();
            $pledge->PledgeAmount = number_format(intval($amt), 2, '.', '');
            $pledge->Frequency = RNCPHP\donation\DonationPledgeFreq::fetch(9);
            $pledge->Type1 = RNCPHP\donation\Type::fetch(3);
            $pledge->Contact = $contact;
            $pledge->NextTransaction = time();
            $pledge->Balance = 0;
            $pledge->Fund = RNCPHP\donation\fund::fetch(STM_FUND_ID);
            $pledge->Appeal = RNCPHP\donation\Appeal::fetch(STM_APPEAL_ID);
            $pledge->Descr = PLEDGE_DESC." ".$contact->Name->First." ".$contact->Name->Last;
            $pledge->save();
            $this->executionSummary[] = "Created Pledge ".$pledge->ID;
            
            $donation2Pledge = new RNCPHP\donation\donationToPledge();
            $donation2Pledge->PledgeRef = $pledge->ID;
            $donation2Pledge->DonationRef = $donation->ID;
            $donation2Pledge->save();
            $this->executionSummary[] = "Created D2P ".$donation2Pledge->ID;

        } catch (\Exception $e) {
            $this->executionSummary[] = "Error Creating Pledge:".$e->getMessage();
        } catch (RNCPHP\ConnectAPIError $e) {
            $this->executionSummary[] = "Error Creating Pledge:".$e->getMessage();
        }


        $id = $pledge->ID;
        
        return $id;
    

    }

    private function createDonation($mmId, $amt, $contact, $tripMember){
        
        try {

            $donation = new RNCPHP\donation\Donation();
            $donation->Contact = $contact;
            $donation->DonationDate = time();
            $donation->Amount = number_format($amt, 2, '.', '');
            $donation->PaymentSource = RNCPHP\donation\paymentSourceMenu::fetch(PAY_SOURCE);
            $donation->managedMissionsId = $mmId; 
            $donation->TripMember = $tripMember;
            $donation->Type = RNCPHP\donation\Type::fetch(1);//always a pledge

            $donation->save();
            $this->executionSummary[] = "Created Donation ".$donation->ID;

        } catch (\Exception $e) {
            $this->executionSummary[] = "Error Creating Donation:".$e->getMessage();
        } catch (RNCPHP\ConnectAPIError $e) {
            $this->executionSummary[] = "Error Creating Donation:".$e->getMessage();
        }

        return $donation;
    
    }

    private function createTransaction($contact, $amt, $desc = null, $donation) {
        
        $desc = addslashes($desc);
        if (strlen($desc) > 254) {
            $desc = substr($desc, 0, 251) . "...";
        }

        try {
            $trans = new RNCPHP\financial\transactions;
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(TRANSACTION_SALE_SUCCESS_STATUS_ID);
            $trans -> totalCharge = number_format($amt, 2, '.', '');
            $trans -> contact = $contact;
            $trans -> description =  "Managed Missions Initiated Transaction";
            if (isset($donation)) {
                $trans->donation = $donation;
            }
            $trans -> save();
            $this->executionSummary[] = "Created Transaction ".$trans->ID;

        } catch (\Exception $e) {
            $this->executionSummary[] = "Error Creating Transaction:".$e->getMessage();
        } catch (RNCPHP\ConnectAPIError $e) {
            $this->executionSummary[] = "Error Creating Transaction:".$e->getMessage();
        }

        return $trans;

    }

    /**
     * 
     * May be used as a donor or a trip member
     * 
     * Creates/Updates Contacts
     * 
     * @param $mmId: id of the donor record in MM
     * @param @mmPersonId: id of the person record in MM
     * 
     */
    private function getContact($mmId = null, $mmPersonId = null, $firstName, $lastName, $street, $city, $state, $zip, $phone, $email){
        $currentContact = null;

        //lookup email first
        if (!empty($email)) {
            try {
                $contactByEmail = RNCPHP\Contact::first("Contact.Emails.Address = '" . addslashes($email) . "'");
                if (!empty($contactByEmail) && empty($currentContact)) {
                    $currentContact = $contactByEmail;
                }
            } catch (\Exception $th) {
            } catch (RNCPHP\ConnectAPIError $err) {
            }
        }

        //then by mm id
        if($mmId && empty($currentContact)){
            try {
                $contactByMMId = RNCPHP\Contact::first("CustomFields.CO.managedMissionsId = '".$mmId."'");
                if (!empty($contactByMMId) && empty($currentContact)) {
                    $currentContact = $contactByMMId;
                }
            } catch (\Exception $th) {
            } catch (RNCPHP\ConnectAPIError $err) {
            }
        }else if($mmPersonId && empty($currentContact)){
            try {
                $contactByMMId = RNCPHP\Contact::first("CustomFields.CO.managedMissionsPersonId = '".$mmId."'");
                if (!empty($contactByMMId) && empty($currentContact)) {
                    $currentContact = $contactByMMId;
                }
            } catch (\Exception $th) {
            } catch (RNCPHP\ConnectAPIError $err) {
            }
        }

        //finally just a new one is fine
        if(empty($currentContact)){
            $currentContact = new RNCPHP\Contact();

            //need to set these so they don't get the new contact email message template.
            if(!empty($email)){
                $currentContact->Login = $email;
            }else{
                $currentContact->Login = time();
            }
            $currentContact->NewPassword = time();

        }


        //email address
        if (!empty($email)) {
            $emailArrIdx = count($currentContact->Emails);
            if ($emailArrIdx == 0) {
                $currentContact->Emails = new RNCPHP\EmailArray();
            }
            $currentContact->Emails[$emailArrIdx] = new RNCPHP\Email();
            $currentContact->Emails[$emailArrIdx]->AddressType = new RNCPHP\NamedIDOptList();
            $currentContact->Emails[$emailArrIdx]->AddressType->ID = 0;
            $currentContact->Emails[$emailArrIdx]->Address =  $email;
        }

        //name
        if (!empty($firstName))
            $currentContact->Name->First = $firstName;
        if (!empty($lastName))
            $currentContact->Name->Last = $lastName;

        //mmid
        if(!empty($mmId))
            $currentContact->CustomFields->CO->managedMissionsId = strval($mmId);

        //mmPersonid
        if(!empty($mmPersonId))
            $currentContact->CustomFields->CO->managedMissionsPersonId = strval($mmPersonId);

        //phone
        if (!is_null($phone)) {
            $phoneArrIdx  = count($currentContact->Phones);
            $currentContact->Phones[$phoneArrIdx] = new RNCPHP\Phone();
            $currentContact->Phones[$phoneArrIdx]->PhoneType = new RNCPHP\NamedIDOptList();
            $currentContact->Phones[$phoneArrIdx]->PhoneType->LookupName = 'Mobile Phone';
            $currentContact->Phones[$phoneArrIdx]->Number = $phone;
        }

        //address
        if (!is_null($state) && array_key_exists(strtoupper($state), $this->getProvinces())) {

            $provinces = $this->getProvinces();
            try {
                if (!$currentContact->Address) {
                    $currentContact->Address = new RNCPHP\Address();
                }
                $currentContact->Address->StateOrProvince = new RNCPHP\NamedIDLabel();
                $currentContact->Address->StateOrProvince->LookupName = strtoupper($state);
                $currentContact->Address->Country = RNCPHP\Country::fetch($provinces[strtoupper($state)]);

            } catch (\Exception $e) {
                $this->executionSummary[] = "Error Creating/Updating Contact State/Prov:".$currentContact->ID." ".$e->getMessage();
            } catch (RNCPHP\ConnectAPIError $e) {
                $this->executionSummary[] = "Error Creating/Updating Contact State/Prov:".$currentContact->ID." ".$e->getMessage();
            }
        }

        try {
            if (!empty($street)) {
                if (!$currentContact->Address) {
                    $currentContact->Address = new RNCPHP\Address();
                }
                $currentContact->Address->Street = substr($street, 0, 240);
            }
            if (!empty($city)) {
                if (!$currentContact->Address) {
                    $currentContact->Address = new RNCPHP\Address();
                }
                $currentContact->Address->City = substr($city, 0, 80);
            }
            if (!empty($zip)) {
                if (!$currentContact->Address) {
                    $currentContact->Address = new RNCPHP\Address();
                }
                $currentContact->Address->PostalCode = substr($zip, 0, 10);
            }
        } catch (\Exception $e) {
            $this->executionSummary[] = "Error Creating/Updating Contact Address:".$currentContact->ID." ".$e->getMessage();
        }


        try {
            $currentContact->save();
        } catch (\Exception $e) {
            $this->executionSummary[] = "Error Creating/Updating Contact:".$currentContact->ID." ".$e->getMessage();
        }
        
        return $currentContact;

    }

    /***
     * TODO see if MM can get a start date/ end date.
     */
    private function getTrip($tripId, $tripName, $startDate, $endDate){

        $trips = RNCPHP\TeamTrips\Trip::find("TeamTrips.Trip.managedMissionsId = '".$tripId."'");

        foreach($trips as $trip){
            $this->executionSummary[] = "Using Trip ".$trip->ID;
            return $trip;
        }

        //new trip
        $trip = new RNCPHP\TeamTrips\Trip();
        $trip->managedMissionsId = $tripId;
        $trip->TripName = $tripName;
        $trip->Fund = RNCPHP\donation\fund::fetch(STM_FUND_ID);
        $trip->Appeal = RNCPHP\donation\Appeal::fetch(STM_APPEAL_ID);
        $trip->StartDate = intval($startDate);
        $trip->EndDate = intval($endDate);
        $trip->save();

        $this->executionSummary[] = "Created Trip ".$trip->ID;
        return $trip;

    }

    //TBD, not sure how to get the trip goer name yet.
    private function getTripMember($tripMemberId, $contactRecord, $trip){
        $tripMembers = RNCPHP\TeamTrips\TripMember::find("TeamTrips.TripMember.managedMissionsId = '".$tripMemberId."'");

        foreach($tripMembers as $tripMember){
            $this->executionSummary[] = "Using Trip Member ".$tripMember->ID;
            return $tripMember;
        }

        //new trip
        $tripMember = new RNCPHP\TeamTrips\TripMember();
        $tripMember->managedMissionsId = $tripMemberId;
        $tripMember->Contact = $contactRecord;
        $tripMember->Trip = $trip;
        $tripMember->Fund = RNCPHP\donation\fund::fetch(STM_FUND_ID);
        $tripMember->Appeal = RNCPHP\donation\Appeal::fetch(STM_APPEAL_ID);
        $tripMember->save();

        $this->executionSummary[] = "Created Trip Member ".$tripMember->ID;
        return $tripMember;
    }

    private function getProvinces(){
        return array('AK'=>'US','AL'=>'US','AR'=>'US','AS'=>'US','AZ'=>'US','CA'=>'US','CO'=>'US','CT'=>'US','DC'=>'US','DE'=>'US','FL'=>'US','FM'=>'US','GA'=>'US','GU'=>'US','HI'=>'US','IA'=>'US','ID'=>'US','IL'=>'US','IN'=>'US','KS'=>'US','KY'=>'US','LA'=>'US','MA'=>'US','MD'=>'US','ME'=>'US','MH'=>'US','MI'=>'US','MN'=>'US','MO'=>'US','MP'=>'US','MS'=>'US','MT'=>'US','NC'=>'US','ND'=>'US','NE'=>'US','NH'=>'US','NJ'=>'US','NM'=>'US','NV'=>'US','NY'=>'US','OH'=>'US','OK'=>'US','OR'=>'US','PA'=>'US','PR'=>'US','PW'=>'US','RI'=>'US','SC'=>'US','SD'=>'US','TN'=>'US','TX'=>'US','UT'=>'US','VA'=>'US','VI'=>'US','VT'=>'US','WA'=>'US','WI'=>'US','WV'=>'US','WY'=>'US','AP'=>'US','AE'=>'US','AB'=>'US','AF'=>'US','AG'=>'US','AT'=>'US','Ontario'=>'CA','Quebec'=>'CA','British Columbia'=>'CA','Alberta'=>'CA','Manitoba'=>'CA','Saskatchewan'=>'CA','Nova Scotia'=>'CA','New Brunswick'=>'CA','Newfoundland and Labrador'=>'CA','Prince Edward Island'=>'CA','Northwest Territories'=>'CA','Yukon'=>'CA','Nunavut'=>'CA','WILTSHIRE'=>'GB','RE'=>'GB','Jalisco'=>'MX','St. Joseph'=>'TT');
    }

    private function cleanDate($dirtyDate){

        $cleanDate = rtrim($dirtyDate, ")/");
        $cleanDate = ltrim($cleanDate, "/Date(");
        $cleanDate = $cleanDate / 1000; //comes in millis

        return $cleanDate;
    }

    
    
}
