<?php

namespace Custom\Models;

require_once(get_cfg_var('doc_root') . '/ConnectPHP/Connect_init.php');

use RightNow\Connect\v1_3 as RNCP;
use RightNow\Utils\Config;

class sponsorship_model extends \RightNow\Models\Base
{
    protected static $CLASS_SCOPE = 'Custom/Models/sponsorship_model';

    private $maxQueryLoops = 30;
    private $rndChildIds = array();
    private $count = null;
    private $gender = null;
    private $community = null;
    private $order = null;
    private $page = null;

    function __construct()
    {
        parent::__construct();
        initConnectAPI('cp_082022_user', '$qQJ616xWWJ9lXzb$');
        $this->CI->load->helper('constants');
        $this->CI->load->helper('log');
        $this->CI->load->library('logging');
        $this->CHILD_RECORD_LOCK_DUR = new \DateInterval('PT30M'); // 30 Minutes
        //This model would be loaded by using $this->load->model('custom/Sample_model');

        $this->CLASS_LOG_LEVEL = $this->CI->Logging->LOG_LEVEL_DEBUG_FULL;
    }

    /**
     * Function to check if a child has been sponsored.
     * @param integer $id the ID of the child record to check for sponsorship
     * @return object {isSponsored: true/false}
     */
    public function isChildSponsored($id)
    {
        $this->CI->logging->logFunctionCall(
            self::$CLASS_SCOPE,
            'isChildSponsored',
            array('$id' => $id),
            $this->CLASS_LOG_LEVEL
        );

        $status = new \stdClass();
        $status->isSponsored = false;

        try {
            $child = RNCP\sponsorship\Child::fetch($id);
            $this->CI->logging->logVar('$child->SponsorshipStatus', $child->SponsorshipStatus);
            $this->CI->logging->logVar('$child->SponsorshipStatus->ID', $child->SponsorshipStatus->ID);
            if ($child->SponsorshipStatus->ID === 3) {
                $this->CI->logging->logMsg(
                    'Child has a SponsorshipStatus flag of 3, indicating he/she is sponsored.',
                    $this->CI->Logging->LOG_LEVEL_DEBUG_FULL,
                    $this->CLASS_LOG_LEVEL
                );
                $status->isSponsored = true;
            } else {
                $this->CI->logging->logMsg(
                    'Child DOES NOT have a SponsorshipStatus flag of 3, indicating he/she is NOT sponsored.',
                    $this->CI->Logging->LOG_LEVEL_DEBUG_FULL,
                    $this->CLASS_LOG_LEVEL
                );
            }
        } catch (\Exception $e) {
            $this->CI->logging->logErr($e, self::$CLASS_SCOPE, 'isChildSponsored', $this->CLASS_LOG_LEVEL);
            throw $e;
        }

        $this->CI->logging->logFunctionReturn(self::$CLASS_SCOPE, 'isChildSponsored', $status, '$status', $this->CLASS_LOG_LEVEL);
        return $status;
    }

    /**
     * Function to check if an unsponsored child record is currently in the process of receiving 
     * sponsorship (active in another user's pending transaction). When a user click's the 'Sponsor Me'
     * link for an unsponsored child, that child record's 'LastRecordLocked' field is updated with the current
     * timestamp and the record is then considered locked for $this->CHILD_RECORD_LOCK_DUR and not available for 
     * sponsorship. If $this->CHILD_RECORD_LOCK_DUR elapses and the transaction to sponsor the child still has 
     * not completed, that record is then considered unlocked again and available for sponsorship. 
     * @param integer $id the ID of the child record to check for an active lock
     * @return object {isLocked: true/false, lastOwner: <contact ID of logged in user that last held lock>}
     */
    public function isChildRecordLocked($id)
    {
        $this->CI->logging->logFunctionCall(
            self::$CLASS_SCOPE,
            'isChildRecordLocked',
            array('$id' => $id),
            $this->CLASS_LOG_LEVEL
        );

        $loggedInContactID = $this->CI->session->getProfileData('contactID');
        $sessionID = $this->CI->session->getSessionData('sessionID');

        $status = new \stdClass();
        $status->isLocked = null;
        $status->lastOwner = null;
        try {
            $child = RNCP\sponsorship\Child::fetch($id);

            // else if($child->LastRecordLockOwner == $loggedInContactID) {//if the logged in user is the person with the lock, then let it go through.
            //     $status->isLocked = false;
            // }
            if (is_null($child->LastRecordLock)) {
                $status->isLocked = false;
            }else if($child->LastRecordLockOwner == $loggedInContactID || $child->RecordLockOwner == $sessionID) {
                $status->isLocked = false;
            }else {
                logMessage('Checking to see if last lock has expired.');
                $now = new \DateTime();
                $this->CI->logging->logVar('$now', $now);
                $lastLock = new \DateTime();
                $lastLock->setTimestamp($child->LastRecordLock);
                $this->CI->logging->logVar('$lastLock', $lastLock);
                $diffInterval = $lastLock->diff($now);
                $this->CI->logging->logVar('$diffInterval', $diffInterval);
                $this->CI->logging->logVar('$this->CHILD_RECORD_LOCK_DUR', $this->CHILD_RECORD_LOCK_DUR);
                $this->CI->logging->logVar('$diffInterval <= $this->CHILD_RECORD_LOCK_DUR', $diffInterval <= $this->CHILD_RECORD_LOCK_DUR);
                $status->isLocked = $diffInterval <= $this->CHILD_RECORD_LOCK_DUR;
                $this->CI->logging->logVar('$status->isLocked', $status->isLocked);
                // If record is locked, return lock owner info (contact ID of logged in user that last held lock)
                if ($status->isLocked) {
                    $status->lastOwner = $child->LastRecordLockOwner;
                    $this->CI->logging->logVar('$status->lastOwner', $status->lastOwner);
                }

                

            }
        } catch (\Exception $e) {
            $this->CI->logging->logErr($e, self::$CLASS_SCOPE, 'isChildRecordLocked', $this->CLASS_LOG_LEVEL);
            throw $e;
        }

        $this->CI->logging->logFunctionReturn(self::$CLASS_SCOPE, 'isChildRecordLocked', $status, '$status', $this->CLASS_LOG_LEVEL);
        return $status;
    }

    /**
     * Flags an unsponsored child record as currently in the process of receiving sponsorship. When a user click's the 'Sponsor Me'
     * link for an unsponsored child, that child record's 'LastRecordLocked' field is updated with the current
     * timestamp via this method and the record is then considered locked for $this->CHILD_RECORD_LOCK_DUR and not available for 
     * sponsorship. If $this->CHILD_RECORD_LOCK_DUR elapses and the transaction to sponsor the child still has 
     * not completed, that record is then considered unlocked again and available for sponsorship. 
     * @param integer $id the ID of the child record to lock
     * @return object If locked successfully -> {status: 'success'}, else {status: 'failure'}
     */
    public function lockChildRecord($id)
    {
        $this->CI->logging->logFunctionCall(
            self::$CLASS_SCOPE,
            'lockChildRecord',
            array('$id' => $id),
            $this->CLASS_LOG_LEVEL
        );

        $status = new \stdClass();
        $status->status = 'success';
        try {
            // Fetch the child before calling isChildRecordLocked to reduce interval between checking for a lock and applying
            // a new lock. We want this interval to be as small as possible to reduce the possibility of a race condition where
            // one user secures a lock and then another user overrides that lock. But the chances of this happening should be slim.
            $child = RNCP\sponsorship\Child::fetch($id);
            if ($this->isChildRecordLocked($id)->isLocked) {
                throw new \Exception('Attempted to lock a child record that is already locked');
            }
            $nowTimestamp = time();
            $child->LastRecordLock = $nowTimestamp;
            $loggedInContactID = $this->CI->session->getProfileData('contactID');
            $sessionID = $this->CI->session->getSessionData('sessionID');
            // Sanity check
            if (is_null($loggedInContactID)) throw new \Exception('Attempted to lock a child record without a logged in contact');
            
            if(strlen($loggedInContactID) > 0 ){
                $child->LastRecordLockOwner = $loggedInContactID;
            }
            if(strlen($sessionID) > 0 ) {               
                $child->RecordLockOwner = $sessionID;
            }

            $child->save();

            logMessage('Lock on child record applied.');
            // Convert unix timestamp to date for readibility in log
            $now = new \DateTime();
            $now->setTimestamp($nowTimestamp);
            $this->CI->logging->logVar('Datetime of lock creation: ', $now);
            // Also log owner contact ID
            $this->CI->logging->logVar('Owner Contact ID: ', $loggedInContactID);
        } catch (\Exception $e) {
            // An exception is possible here under a valid use case (one user beat another user to locking a record), so log it and swallow it
            $this->CI->logging->logErr($e, self::$CLASS_SCOPE, 'isChildRecordLocked', $this->CLASS_LOG_LEVEL);
            $status->status = 'failure';
        }

        $this->CI->logging->logFunctionReturn(self::$CLASS_SCOPE, 'lockChildRecord', $status, '$status', $this->CLASS_LOG_LEVEL);
        return $status;
    }

    public function getChild($id)
    {
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);
        if (is_null($id) || $id < 1) {
            return null;
        }
        $children = array();

        $resultSet = RNCP\ROQL::queryObject("select SPONSORSHIP.Child from SPONSORSHIP.Child WHERE ID = " . $id . " ")->next();

        while ($child = $resultSet->next()) {
            $children[] = $this->buildChilObj($child);
        }
        return $children;
    }

    /**
     * Handles building an informational object for end-user presentation
     */
    private function buildChilObj(RNCP\sponsorship\Child $child)
    {
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);
        $thischild = new \stdClass();
        $thischild->ID = $child->ID;
        $thischild->ChildRef = $child->ChildRef;
        $thischild->GivenName = $child->GivenName;
        $thischild->FullName = $child->FullName;
        $thischild->Age = $child->Age;
        $thischild->MonthOfBirth = $child->MonthOfBirth;
        $thischild->DayOfBirth = $child->DayOfBirth;
        $thischild->YearOfBirth = $child->YearOfBirth;
        $thischild->Gender = $child->Gender->LookupName;
        // if ($child -> Rate == null) {
        // $thischild -> Rate = 0;
        // } else {
        // if ($child -> NumberOfSponsors == null) {
        // $thischild -> Rate = $child -> Rate;
        // } else {
        // $thischild -> Rate = $child -> Rate / $child -> NumberOfSponsors;
        // }
        // }
        $thischild->Rate = $child->DisplayedRate;
        $thischild->FavoriteHobby = $child->FavoriteHobby->LookupName;
        $thischild->FavoriteSubject = $child->FavoriteSubject->LookupName;
        $thischild->Community = $child->Community->LookupName;
        $thischild->Grade = $child->Grade->LookupName;
        $thischild->sdesc = $child->SponsorshipDescription;

        //logMessage("child sponsorship status = ".$child->SponsorshipStatus->ID);
        if ($child->ChildEventStatus->ID == WEBHOLDID) {
            $thischild->WebHold = true;
        }


        if ($child->SponsorshipStatus->ID == 4) {
            $desc = "<p class='ChildDescEmphasis'>As " . $child->GivenName . "’s co-sponsor, you and another sponsor are helping ";
            $desc .= ($child->Gender->LookupName == "Female") ? "her" : "him";
            $desc .= " in ";
            $desc .= ($child->Gender->LookupName == "Female") ? "her" : "his";
            $desc .= " last year(s) of secondary (high) school to finish well. </p>";
        }

        $desc .= "<p>" . $child->GivenName . " is part of the " . $child->Community->LookupName . " community. ";
        if ($child->Gender->LookupName == "Male") {
            $desc = $desc . "He " . " is in " . $child->Grade->LookupName . " at school, and his favorite subject is " . $child->FavoriteSubject->LookupName . ". " . $child->GivenName . "'s favorite hobby is " . $child->FavoriteHobby->LookupName . ". ";
        } else if ($child->Gender->LookupName == "Female") {
            $desc = $desc . "She " . " is in " . $child->Grade->LookupName . " at school, and her favorite subject is " . $child->FavoriteSubject->LookupName . ". " . $child->GivenName . "'s favorite hobby is " . $child->FavoriteHobby->LookupName . ". ";
        } else {
            $desc = $desc . " " . " is in " . $child->Grade->LookupName . " at school, and favorite subject is " . $child->FavoriteSubject->LookupName . ". " . $child->GivenName . "'s favorite hobby is " . $child->FavoriteHobby->LookupName . ". ";
        }
        $desc .= "</p>";
        $thischild->Description = $desc;

        if ($thischild->imageLocation = $this->getChildImg($child->ChildRef)) {
            $thischild->hasImage = true;
        } else {
            $thischild->imageLocation = $this->getChildImg(CHILD_NO_IMAGE_FILENAME);
            $thischild->hasImage = false;
        }

        return $thischild;
    }


    /**
     * 
     * Old way was to store all images in one directory, but webdav couldn't hang, so we hashed the file name and made a directory with teh first two chars
     * 
     */
    public function getChildImg($childRef)
    {
        $imgPath = false;
        $childPhoto = $childRef . ".JPG";
        $hashDir = substr(md5($childPhoto), 0, 2);
        if (file_exists(CHILD_IMAGE_FILESYSTEM_DIR . "/" . $hashDir . "/" . $childRef . ".JPG")) {
            return CHILD_IMAGE_URL_DIR . "/" . $hashDir . "/" . $childRef . ".JPG";
        }

        return $imgPath;
    }

    // Gets unsponsored children based on gender, age and community. Supports pagination via $page and $count. 
    // Previous results will be cached in session data to improve pagination performance.
    public function getUnsponsoredChildren($gender, $age, $community, $page, $count, $event, $priority, $monthofbirth, $yearofbirth)
    {
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);

        // Make sure $page and $count are positive 
        $page = max($page, 1);
        $count = max($count, 1);

        if ($event > 0) {
            $res = RNCP\ROQL::query("select sponsorship.Event.Description, sponsorship.Event.DisplayName, sponsorship.Event.Status.LookupName from sponsorship.Event where sponsorship.Event.ID = " . $event)->next();
            while ($eventObj = $res->next()) {
                if ($eventObj['LookupName'] != "Expired") {
                    $eventName = $eventObj['DisplayName'];
                    $eventDesc = $eventObj['Description'];
                } else {
                    $result = array(
                        'data' => $matchUnsponChildPageResult,
                        'metadata' => array(
                            'filters' => array(
                                'gender' => null,
                                'age' => null,
                                'community' => null,
                                'priority' => null,
                                'monthofbirth' => null,
                                'yearofbirth' => null
                            ),
                            'page' => null,
                            'lastPage' => null,
                            'count' => null,
                            'eventName' => "This event has expired",
                            'eventDescription' => "",
                        )
                    );

                    return $result;
                }
            }
        } else {
            $event = null;
        }


        //logMessage("Filters: gender = $gender, age = $age, community = $community, page = $page, count = $count");

        // Previous results will be cached in session data. If the filters haven't changed, use the previous result
        // to build the current result. If the filters have changed, query DB and build new result and cache it. The
        // result will be a list of all child IDs that match the filter criteria.
        $cachedResult = get_instance()->session->getSessionData('lastUnsponsoredChildIDResult');
        $cachedResult = false; //i hate this caching, it messes up the priority and doesn't add hardly any value.
        if (
            $cachedResult === false || $cachedResult['filters']['event'] !== $event || $cachedResult['filters']['gender'] !== $gender || $cachedResult['filters']['age'] !== $age || $cachedResult['filters']['community'] !== $community
        ) {
            // Filters have changed, need to query DB to obtain new result
            ////logMessage("Filters have changed, preparing to query DB...");
            try {
                $roqlString = $this->buildUnsponsoredChildIDRoqlString($gender, $age, $community, $event, $priority, $monthofbirth, $yearofbirth);
                $resultSet = RNCP\ROQL::query($roqlString)->next();
                $matchingUnsponChildIDs = array();
                while ($res = $resultSet->next()) {
                    // Filter result to children with images
                    try {
                        $childID = $res['ID'];
                        $child = RNCP\sponsorship\Child::fetch($childID);
                        if (
                            strtoupper($child->ChildRef) !== 'NOCHILD' &&
                            $child->imageLocation = $this->getChildImg($child->ChildRef) &&
                            !$this->isChildRecordLocked($childID)->isLocked
                        ) {
                            $matchingUnsponChildIDs[] = $childID;
                        }
                    } catch (\Exception $e) {
                        logMessage($e->getMessage());
                        throw $e;
                    }
                }

                // Randomize result order to promote equality of discovery for unsponsored children
                //shuffle($matchingUnsponChildIDs);

                // Cache new result in session data
                $newResult = array(
                    'eventName' => $eventName,
                    'eventDescription' => $eventDesc,
                    'data' => implode(',', $matchingUnsponChildIDs),
                    'filters' => array(
                        'gender' => $gender,
                        'age' => $age,
                        'community' => $community,
                        'priority' => $priority,
                        'monthofbirth' => $monthofbirth,
                        'yearofbirth' => $yearofbirth,
                        'event' => $event
                    )
                );
                get_instance()->session->setSessionData(
                    array('lastUnsponsoredChildIDResult' => $newResult)
                );
            } catch (\Exception $e) {
                logMessage($e->getMessage());
                throw $e;
            }

            $result = $newResult;
            logMessage("Showing New results");
        } else {
            $result = $cachedResult;
            logMessage("showing cached");
        }

        //logMessage('Unsponsored child result = ' . var_export($result, true));

        if (!empty($result['data'])) {
            // Transform child ID string into array
            $result['data'] = explode(',', $result['data']);

            // To support pagination, we need to build a subset of the last result based on $page and $count
            //logMessage("Building page result...");
            $resCnt = count($result['data']);
            $start = ($page - 1) * $count;
            $len = min($count, $resCnt - $start);

            //logMessage("Total matching unsponsored child ID count: $resCnt");
            //logMessage("Start index: $start");
            //logMessage("Length: $len");

            if ($start < $resCnt) {
                $matchUnsponChildPageResultIDs = array_slice($result['data'], $start, $len);
            } else {
                $matchUnsponChildPageResultIDs = array();
            }
        } else {
            $matchUnsponChildPageResultIDs = array();
        }

        //logMessage("Page result child IDs: {$matchUnsponChildPageResultIDs}");

        // Transform the child ID array into an array of child objects
        $matchUnsponChildPageResult = array();
        foreach ($matchUnsponChildPageResultIDs as $childID) {
            try {
                $child = RNCP\sponsorship\Child::fetch($childID);
                $childObj = $this->buildChilObj($child);
                $matchUnsponChildPageResult[] = $childObj;
            } catch (\Exception $e) {
                logMessage($e->getMessage());
                throw $e;
            }
        }

        $result = array(
            'data' => $matchUnsponChildPageResult,
            'metadata' => array(
                'filters' => array(
                    'gender' => $gender,
                    'age' => $age,
                    'community' => $community,
                    'priority' => $priority,
                    'monthofbirth' => $monthofbirth,
                    'yearofbirth' => $yearofbirth
                ),
                'page' => $page,
                'lastPage' => intval(ceil($resCnt / $count)),
                'count' => $count,
                'eventName' => $eventName,
                'eventDescription' => $eventDesc,
            )
        );
        return $result;
    }

    /* Utility function for building the ROQL queryObject/query string for fetching unsponsored children */
    private function buildUnsponsoredChildIDRoqlString($gender = null, $age = null, $community = null, $event = null, $priority = null, $monthofbirth = null, $yearofbirth = null)
    {
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);

        $whereClauses = array();
        $whereClauses[] = "SPONSORSHIP.Child.ChildRef Is Not Null";
        //logMessage("Default WHERE clauses: {$whereClauses}");

        // Build where clauses
        if (!is_null($event)) {
            $whereClauses[] = "SPONSORSHIP.Child.Event.ID = " . $event;
            $whereClauses[] = "SPONSORSHIP.Child.Event.Status.LookupName != 'Expired'";
            $whereClauses[] = "SPONSORSHIP.Child.ChildEventStatus In ( 1 )"; //web hold only
        } else {
            $whereClauses[] = "SPONSORSHIP.Child.SponsorshipStatus In ( 5 ,2, 4 )";
            $whereClauses[] = "SPONSORSHIP.Child.ChildEventStatus is null";
        }


        if (!is_null($gender)) {
            $whereClause = "SPONSORSHIP.Child.Gender = " . $gender;
            $whereClauses[] = $whereClause;
            //logMessage("Gender WHERE clause: $whereClause");
        }
        if (!is_null($age)) {
            if ($age == 16) {
                $whereClause = "SPONSORSHIP.Child.Age>=" . $age;
                $whereClauses[] = $whereClause;
            } else {
                $age1 = $age + 1;
                $age2 = $age + 2;
                $whereClause = "SPONSORSHIP.Child.Age In(" . $age . "," . $age1 . "," . $age2 . ")";
                $whereClauses[] = $whereClause;
            }
            //logMessage("Age WHERE clause: $whereClause");
        }
        if (!is_null($community)) {
            $whereClause = "SPONSORSHIP.Child.Community = " . $community;
            $whereClauses[] = $whereClause;
            //logMessage("Gender WHERE clause: $whereClause");
        }

        if (!is_null($priority) && $priority != 0) {  //0 value is all priorities
            $whereClause = "SPONSORSHIP.Child.Priority = " . $priority;
            $whereClauses[] = $whereClause;
            //logMessage("Gender WHERE clause: $whereClause");
        }

        if (!is_null($monthofbirth) && $monthofbirth != 0) {  //0 value is all priorities
            $whereClause = "(SPONSORSHIP.Child.MonthOfBirth = '" . $monthofbirth . "' OR SPONSORSHIP.Child.MonthOfBirth = '0" . $monthofbirth . "')";
            $whereClauses[] = $whereClause;
            //logMessage("Gender WHERE clause: $whereClause");
        }

        if (!is_null($yearofbirth) && $yearofbirth != 0) {  //0 value is all priorities
            $whereClause = "SPONSORSHIP.Child.YearOfBirth = '" . $yearofbirth . "'";
            $whereClauses[] = $whereClause;
            //logMessage("Gender WHERE clause: $whereClause");
        }



        $roql = "SELECT SPONSORSHIP.Child.ID `ID` FROM SPONSORSHIP.Child ";

        if (count($whereClauses) > 0) {
            $roql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $roql .= " order by SPONSORSHIP.Child.Priority ASC";
        logMessage("Returned ROQL: " . $roql);
        return $roql;
    }

    public function getCommunities()
    {
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);

        $excludeList = getConfig(CUSTOM_CFG_EXCLUDE_COMMUNITIES_CSV);

        $communities = array();
        $excludeQuery = (!empty($excludeList)) ? "WHERE sponsorship.Community.ID NOT IN (" . $excludeList . ")" : '';
        $resultSet = RNCP\ROQL::queryObject("select SPONSORSHIP.Community from SPONSORSHIP.Community $excludeQuery ORDER BY Name  ")->next();

        while ($Community = $resultSet->next()) {
            $thisCommunity = new \stdClass();
            $thisCommunity->ID = $Community->ID;
            $thisCommunity->Name = $Community->Name;
            $communities[] = $thisCommunity;
        }

        return $communities;
    }

    public function getAdvocacies($event)
    {

        $advocacies = array();
        $resultSet = RNCP\ROQL::queryObject("select sponsorship.Advocates from sponsorship.Advocates  WHERE sponsorship.Advocates.Event.ID = $event")->next();

        while ($advocate = $resultSet->next()) {
            $thisAdvocate = new \stdClass();
            $thisAdvocate->ChildId = $advocate->Child->ID;
            $thisAdvocate->ContactName = $advocate->Contact->Name->First . " " . $advocate->Contact->Name->Last;
            $advocacies[] = $thisAdvocate;
        }

        logMessage($advocacies);
        return $advocacies;
    }

    public function getAdvocateConfirmation($childId)
    {
        if ($childId > 0) {
            $message = "";
            $child = RNCP\sponsorship\Child::fetch($childId);
            $baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();
            $url = "<strong><a href='$baseURL/app/childSponsor/ChildID/" . $childId . "'>$baseURL/app/childSponsor/ChildID/" . $childId . "</a><strong>";
            $message = "Thank you for Advocating for " . $child->FullName . "! Please use the following link to view this child's page, and share it with friends and family who may be interested in sponsoring this child. The link will deactivate once  " . $child->FullName . " is sponsored. Until then, it will be available in <a href='/app/account/overview'>Your Account </a> for future reference.";
            $message .= "<br/></br>" . $url;
        } else {
            $message = "There was an error creating this advocacy entry.  Please contact Customer Support at 866.979.0393.";
        }

        //logMessage("Message = ".$message);

        return $message;
    }

    public function getbatchrecord($ctype)
    {
        //$this->deletenewbatches();
        $obj = "";
        if ($ctype == "cc") {
            $today_date = strtotime(date('d-m-Y'));
            $batch = "";
            $batch_cnt = 0;
            $d_query = RNCP\ROQL::queryObject("select Donation.DonationBatch from Donation.DonationBatch where Donation.DonationBatch.BatchName='WEBSITE CC' order by Donation.DonationBatch.ID DESC limit 0,1;")->next();
            while ($d_query_each = $d_query->next()) {
                $stdate_st = date('d/m/Y', $d_query_each->Posted);
                $td_date = date('d/m/Y');
                if ($stdate_st == $td_date) {
                    $batch = $d_query_each;
                    $batch_cnt++;
                } else {
                    $batch = "";
                    $batch_cnt = 0;
                }
            }
            if ($batch_cnt > 0) {
                //Already Created
                //$Supp->Batch=$batch;
                $obj = $batch;
            } else {
                //Create a new Batch
                $Batch_new = new RNCP\DONATION\DonationBatch();
                $Batch_new->BatchName = 'WEBSITE CC';
                $Batch_new->Posted = $today_date;
                $Batch_new->save(RNCPHP\RNObject::SuppressAll);
                //$Supp->Batch=$Batch_new;
                $obj = $Batch_new;
            }
        } else {
            $today_date = strtotime(date('d-m-Y'));
            $batch = "";
            $batch_cnt = 0;
            $d_query = RNCP\ROQL::queryObject("select Donation.DonationBatch from Donation.DonationBatch where Donation.DonationBatch.BatchName='WEBSITE EFT' order by Donation.DonationBatch.ID DESC limit 0,1;")->next();
            while ($d_query_each = $d_query->next()) {
                $stdate_st = date('d/m/Y', $d_query_each->Posted);
                $td_date = date('d/m/Y');
                if ($stdate_st == $td_date) {
                    $batch = $d_query_each;
                    $batch_cnt++;
                } else {
                    $batch = "";
                    $batch_cnt = 0;
                }
            }
            if ($batch_cnt > 0) {
                //Already Created
                //$Supp->Batch=$batch;
                $obj = $batch;
            } else {
                //Create a new Batch
                $Batch_new = new RNCP\DONATION\DonationBatch();
                $Batch_new->BatchName = 'WEBSITE EFT';
                $Batch_new->Posted = $today_date;
                $Batch_new->save(RNCPHP\RNObject::SuppressAll);
                //$Supp->Batch=$Batch_new;
                $obj = $Batch_new;
            }
        }
        return $obj;
    }

    public function deletenewbatches()
    {
        $d_query = RNCP\ROQL::queryObject("select Donation.DonationBatch from Donation.DonationBatch where Donation.DonationBatch.ID>=215936;")->next();
        while ($d_query_each = $d_query->next()) {
            $d_query_each->destroy();
        }
    }

    public function getcontactdetails($cid)
    {
        $con = new \stdClass();
        //get the cardnumber,expdate,cvv,nmeoncard,zip,street from the contact record
        $cust = 0;
        if (is_numeric($cid) == true) {
            $cust = intval($cid);
            $resp = $cust;
        }
        $resultSet = RNCP\ROQL::queryObject("SELECT Contact FROM Contact where Contact.ID=$cust;")->next();
        while ($contacts = $resultSet->next()) {
            $monthlyAmtthsarr = array();
            $monthsarr["15"] = "01";
            $monthsarr["16"] = "02";
            $monthsarr["17"] = "03";
            $monthsarr["18"] = "04";
            $monthsarr["19"] = "05";
            $monthsarr["20"] = "06";
            $monthsarr["21"] = "07";
            $monthsarr["22"] = "08";
            $monthsarr["23"] = "09";
            $monthsarr["24"] = "10";
            $monthsarr["13"] = "11";
            $monthsarr["14"] = "12";
            //email
            //$con->email=$contacts->Emails[0]->Address;
            //name on card
            $con->card_name = $contacts->CustomFields->card_name;
            //card number
            $newstring = substr($contacts->CustomFields->card_number, -4);
            $con->card_number = $newstring;

            //card exp month
            $con->card_expirationmonth = $monthsarr[$contacts->CustomFields->card_expirationmonth->ID];
            //card exp year
            $con->card_expirationyear = $contacts->CustomFields->card_expirationyear->LookupName;
            //card cvv
            $con->card_cvv = $contacts->CustomFields->card_cvv;
            //postal code
            $con->postal_code = $contacts->postal_code;
            //street
            $con->street = $contacts->street;
        }
        return $con;
    }

    public function getcontactdetailsdd($cid)
    {
        $con = new \stdClass();
        //get the cardnumber,expdate,cvv,nmeoncard,zip,street from the contact record
        $cust = 0;
        if (is_numeric($cid) == true) {
            $cust = intval($cid);
            $resp = $cust;
        }
        $resultSet = RNCP\ROQL::queryObject("SELECT Contact FROM Contact where Contact.ID=$cust;")->next();
        while ($contacts = $resultSet->next()) {
            //email
            //$con->email=$contacts->Emails[0]->Address;
            //name on card
            $con->accountname = $contacts->CustomFields->eft_accountname;
            //card number
            $newstring = substr($contacts->CustomFields->eft_accountnumber, -4);

            $con->accountnumber = $newstring;
            //card exp month
            $con->sortcode = $contacts->CustomFields->eft_routingnumber;
        }
        return $con;
    }
}
