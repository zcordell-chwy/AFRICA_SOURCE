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

/*Application Constants TST3*/
// define('STM_FUND_ID', 89);
// define('PAY_SOURCE', 89);
// define('PLEDGE_DESC', "Managed Missions payment for");
// define('TRANSACTION_SALE_SUCCESS_STATUS_ID', 3);
// define('STM_APPEAL_ID', 2223);
// define('MANUAL_PAY_STATUS', 43);

/*Application Constants Prod*/
define('STM_FUND_ID', 89);
define('PAY_SOURCE', 93);
define('PLEDGE_DESC', "Managed Missions payment for");
define('TRANSACTION_SALE_SUCCESS_STATUS_ID', 3);
define('STM_APPEAL_ID', 4580);
define('MANUAL_PAY_STATUS', 43);

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
        //$this->getTransactionsEndpoint = str_replace('{DATE}', '2021-08-01', RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_API_ENDPOINT')->Value);
        $this->getTransactionsEndpoint = str_replace('{DATE}', date('Y-m-d', strtotime('August 1, 2022')), RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_API_ENDPOINT')->Value);
        $this->getTripMemberEndpoint = RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_PERSON_API_ENDPOINT')->Value;
        $this->getTripEndpoint = RNCPHP\Configuration::fetch('CUSTOM_CFG_MANAGED_MISSIONS_TRIP_API_ENDPOINT')->Value;
        $this->executionSummary[] = $this->getTransactionsEndpoint;
        
    }

    public function beginExecution(){


        $response = network_utilities\runCurl($this->getTransactionsEndpoint, "GET", null, array());

        //TESTING SPECIAL DONATION
        // $response = '{
        //     "status": 0,
        //     "statusMessage": "Success",
        //     "data": [
        //         {
        //             "MissionTripId": 54449,
        //             "PersonId": 479212,
        //             "IsGeneralContribution": false,
        //             "MissionTripImportExportKey": "54449",
        //             "PersonImportExportKey": "479212",
        //             "Anonymous": false,
        //             "ConfirmationCode": null,
        //             "ContributionAmount": 1.00,
        //             "GrossAmount": 1.00,
        //             "NetAmount": null,
        //             "FeeAmount": null,
        //             "DepositDate": "/Date(1626393600000)/",
        //             "DonorId": 3002228,
        //             "DonorName": "Scott Stuart",
        //             "DonorOrganization": null,
        //             "DonorSuffix": null,
        //             "DonorTitle": null,
        //             "ImportExportKey": null,
        //             "Notes": null,
        //             "ReferenceNumber": "REFUNDCHECK",
        //             "RegularAttender": false,
        //             "TaxDeductible": true,
        //             "TransactionType": null,
        //             "Address1": null,
        //             "Address2": null,
        //             "City": null,
        //             "State": null,
        //             "PostalCode": null,
        //             "PhoneNumber": null,
        //             "EmailAddress": "sfstuart1952@yahoo.com",
        //             "Id": 989815,
        //             "MissionTripName": "08-2021 Pastor Trip with Jordan",
        //             "PersonName": "Scott Stuart",
        //             "CreatedDate": "/Date(1626465516987)/",
        //             "ModifiedDate": null,
        //             "Deleted": false
        //         }
        //     ]
        // }';
    
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

        if($mmDonation->GrossAmount <= 0){
            $errors[] = "Donation not a positive amount";
        }

        if($mmDonation->ReferenceNumber == "Refund"){
            $errors[] = "Refund expected";
        }

        if($mmDonation->ReferenceNumber == "EXCEPTION"){
            $errors[] = "Exception: Skipping";
        }


        return $errors;

    }
    /**
     * 
     * Process Results: Create Donor(Contact), TeamTrips.TripMember(Contact), TeamTrips.Trip, one time pledge, donation, transaction
     * 
     */
    private function processDonation($mmDonation){

        $fundId = null;
        $names = explode(" ", $mmDonation->DonorName);
        $count = count($names) - 1;//don't include the last name
        $donorFirstName = implode(" ",array_slice($names,0,$count));
        $donorLastNme = $names[count($names) - 1];

        $tripMemberNames = explode(" ", $mmDonation->PersonName);

        //Doing this first to determine if its a custom fund
        $tripResponse = network_utilities\runCurl( str_replace('{ID}', $mmDonation->MissionTripId, $this->getTripEndpoint), "GET", null, array());
        // $tripResponse = '{
        //     "status": 0,
        //     "statusMessage": "Success",
        //     "data": {
        //         "HideContributionAmountFromParticipants": false,
        //         "PartneringOrganization": "None",
        //         "TripDescription": "Get to know Africa New Life Ministrys work in Rwanda first-hand. Visit and participate with the Africa College of Theology. See Sponsorship in action and meet sponsored students.  ",
        //         "TripDestination": "Kigali",
        //         "TripName": "test payment due auto pull",
        //         "PublicTripName": "test payment due auto pull",
        //         "DepartureDate": "/Date(1621468800000)/",
        //         "ReturnDate": "/Date(1621641600000)/",
        //         "Id": 54471,
        //         "TripMemberGoal": 5.00,
        //         "EnableBudget": true,
        //         "EnableExpense": true,
        //         "EnableContribution": true,
        //         "ContributionExtendedDetails": false,
        //         "AccountNumberIncome": "202108PSTRMIX01",
        //         "AccountNumberExpense": "202108PSTRMIX01",
        //         "UseBudgetAsTotalGoal": true,
        //         "PurposeCode": "321234234",
        //         "ImportExportKey": "54471",
        //         "DisablePublicProfiles": true,
        //         "PublicProfilesRequireApproval": false,
        //         "Country": "Rwanda",
        //         "Qualifications": null,
        //         "GroupId": null,
        //         "GroupName": null,
        //         "Deleted": false,
        //         "Cancelled": null,
        //         "Postponed": null,
        //         "DonationsPaused": null,
        //         "ApplicationsPaused": null,
        //         "MissionApplications": [],
        //         "Contributions": [],
        //         "TripMembers": []
        //     }
        // }';

        //find trip
        if(!$tripResponse){
            outputResponse($this->executionSummary, 'Failed to get response from MM Trip Api url:'.str_replace('{ID}', $mmDonation->MissionTripId, $this->getTripEndpoint), '500');
        }else{
            $tripResults = json_decode($tripResponse);
        }

        $this->executionSummary[] = "Purpose code: ".$tripResults->data->PurposeCode;
        if(strpos($tripResults->data->PurposeCode, 'CustomFund-') !== false){
            $this->executionSummary[] = "Creating a custom Fund Donation";
            $stringArr = explode('-', $tripResults->data->PurposeCode);
            $fundId = $stringArr[1];
            $this->executionSummary[] = "Creating a custom Fund Donation for fund ".$fundId;
            $customFundDonation = true;
        }

        //donor info
        $donor = $this->getContact($mmDonation->DonorId, null, $donorFirstName, $donorLastNme, $mmDonation->Address1, $mmDonation->City, $mmDonation->State, $mmDonation->PostalCode, $mmDonation->PhoneNumber, $mmDonation->EmailAddress);

        
        //trip member info
        $tripMemberResponse = network_utilities\runCurl( str_replace('{ID}', $mmDonation->PersonId, $this->getTripMemberEndpoint), "GET", null, array());
        if(!$tripMemberResponse){
            outputResponse($this->executionSummary, 'Failed to get response from MM Person Api url:'.str_replace('{ID}', $mmDonation->PersonId, $this->getTripMemberEndpoint), '500');
        }else{
            $tripMemberResults = json_decode($tripMemberResponse);
        }

        if(!$customFundDonation){
            $tripContact = $this->getContact(null, $mmDonation->PersonId, $tripMemberResults->data->FirstName, $tripMemberResults->data->LastName, $tripMemberResults->data->Address1, $tripMemberResults->data->City, $tripMemberResults->data->State, $tripMemberResults->data->PostalCode, $tripMemberResults->data->PhoneNumber, $tripMemberResults->data->EmailAddress);

            //create trip
            $trip = $this->getTrip($mmDonation->MissionTripId, $mmDonation->MissionTripName, $this->cleanDate($tripResults->data->DepartureDate), $this->cleanDate($tripResults->data->ReturnDate));

            // //find/create TripMember
            $tripMember = $this->getTripMember($mmDonation->PersonId, $tripContact, $trip);
        }
        
        // //create Donation
        $donation = $this->createDonation($mmDonation->Id, $mmDonation->GrossAmount, $donor, $tripMember, $mmDonation->TaxDeductible, $mmDonation->ReferenceNumber, $mmDonation->CreatedDate);

        // //create one time pledge
        $pledge = $this->createPledge($donation, $mmDonation->GrossAmount, $donor, $fundId, $tripMemberResults->data->FirstName." ".$tripMemberResults->data->LastName);

        // //created completed transaction
        //todo make sure this doesn't send a receipt
        $transaction = $this->createTransaction($donor, $mmDonation->GrossAmount, null, $donation );

        return true;

    }

    /**
     * 
     * 
     * 
     */
    private function createPledge($donation, $amt, $contact, $fund = null, $traveler = null){
        
        $this->executionSummary[] = "Beginning create pledge";
        try {
            $fundId = ($fund) ? intval($fund) : STM_FUND_ID;
            $pledge = new RNCPHP\donation\pledge();
            
            if (!$donation instanceof RNCPHP\donation\Donation) {
                $this->executionSummary[] = "returning false on donation ";
                return false;
            }

            $pledge = new RNCPHP\donation\pledge();
            $pledge->PledgeAmount = number_format(intval($amt), 2, '.', '');
            $pledge->Frequency = RNCPHP\donation\DonationPledgeFreq::fetch(9);
            $pledge->Type1 = RNCPHP\donation\Type::fetch(3);//One time
            $pledge->Contact = $contact;
            $pledge->NextTransaction = time();
            $pledge->Balance = 0;
            $pledge->Fund = RNCPHP\donation\fund::fetch($fundId);
            $pledge->Appeals = RNCPHP\donation\Appeal::fetch(STM_APPEAL_ID);

            //for special purpose donations the traveller won't be available
            $desc = PLEDGE_DESC." ".$traveler;
            $pledge->Descr = $desc;
            $pledge->PledgeStatus = RNCPHP\donation\PledgeStatus::fetch(MANUAL_PAY_STATUS);//Manual Pay
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

    private function createDonation($mmId, $amt, $contact, $tripMember, $non_charitable = true, $referenceNumber = null, $createdDate = null){
        
        //its the opposite in oracle vs mm
        $non_charitable_val = ($non_charitable) ? false : true;
        try {

            $donation = new RNCPHP\donation\Donation();
            $donation->Contact = $contact;
            $donation->DonationDate = ($createdDate) ? $this->cleanDate($createdDate) : time();
            $donation->Amount = number_format($amt, 2, '.', '');
            $donation->PaymentSource = RNCPHP\donation\paymentSourceMenu::fetch(PAY_SOURCE);
            $donation->managedMissionsId = $mmId; 
            $donation->TripMember = $tripMember;
            $donation->Type = RNCPHP\donation\Type::fetch(1);//always a pledge
            $donation->Non_Charitable = $non_charitable_val;
            if(strpos($referenceNumber, 'Check-') === 0){
                $donation->isCheck = true;
                $donation->checkNumberTXT = str_replace('Check-', '', $referenceNumber);
            }else{
                $donation->isCheck = false;
            }
            

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
     * @param @mmPersonId: id of the person record in MM (trip goer)
     * 
     */
    private function getContact($mmId = null, $mmPersonId = null, $firstName, $lastName, $street, $city, $state, $zip, $phone, $email){
        $currentContact = null;

        //we don't want to update contact if traveller exists, we do if donor exists. 
        $contactType = (!is_null($mmPersonId)) ? 'traveller' : 'donor';
        $newContact = false;

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
            }
            
            $newContact = true;
        }

        //if its an existing traveller, just update the mmperson id and return
        if($contactType == 'traveller' && $newContact == false){

            if(!empty($mmPersonId))
                $currentContact->CustomFields->CO->managedMissionsPersonId = strval($mmPersonId);
            
            try {
                $currentContact->save();
            } catch (\Exception $e) {
                $this->executionSummary[] = "Error Creating/Updating Contact:".$currentContact->ID." ".$e->getMessage();
            }

            return $currentContact;
        }

        //email address
        //if MM has an email and The contact in oracle does not
        if (!empty($email) && $currentContact->Emails[0]->Address == null){

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
