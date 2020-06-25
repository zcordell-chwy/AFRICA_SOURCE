<?php
namespace Custom\Models;

require_once (get_cfg_var('doc_root') . '/ConnectPHP/Connect_init.php');
use RightNow\Connect\v1_3 as RNCP;

class woman_model extends \RightNow\Models\Base {
    protected static $CLASS_SCOPE = 'Custom/Models/woman_model';

    private $maxQueryLoops = 30;
    private $rndChildIds = array();
    private $count = null;
    private $gender = null;
    private $community = null;
    private $order = null;
    private $page = null;

    function __construct() {
        parent::__construct();
        initConnectAPI('api_access', 'Password1');
        $this -> CI -> load -> helper('constants');
        $this->CI->load->library('logging');
        $this->CLASS_LOG_LEVEL = $this->CI->Logging->LOG_LEVEL_DEBUG_FULL;
    }


    /**
     * Handles building an informational object for end-user presentation
     */
    private function buildWomanObj(RNCP\sponsorship\Woman $woman) {
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj womanObj".print_r($woman, true)." \n", FILE_APPEND);
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj program".print_r($woman -> Program, true)." \n", FILE_APPEND);
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj lookupname".print_r($woman -> Program->LookupName, true)." \n", FILE_APPEND);
        $thiswoman = new \stdClass();
        $thiswoman -> ID = $woman -> ID;
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 34 \n", FILE_APPEND);
        $thiswoman -> WomanRef = $woman -> WomanRef; //keep it as childRef to not have to change view
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 36 \n", FILE_APPEND);
        $thiswoman -> GivenName = $woman -> GivenName;
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 38 \n", FILE_APPEND);
        $thiswoman -> FullName = $woman -> FullName;
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 40 \n", FILE_APPEND);
        $thiswoman -> Rate = $woman->Rate;
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 42 \n", FILE_APPEND);
        $thiswoman -> FamilySituation = $woman -> FamilySituation;
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 44 \n", FILE_APPEND);
        $thiswoman -> Program = $woman -> Program -> LookupName;
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 46 \n", FILE_APPEND);
        $thiswoman -> ActivePledgeTotal = 150 - $this->getActivePledgeTotal($woman -> ID);
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 48 \n", FILE_APPEND);


        if($thiswoman -> imageLocation = $this->getWomanImg($woman -> WomanRef)){
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 52 \n", FILE_APPEND);
            $thiswoman -> hasImage = true;
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 54 \n", FILE_APPEND);
        }else{
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 56 \n", FILE_APPEND);
            $thiswoman -> imageLocation = $this->getWomanImg(CHILD_NO_IMAGE_FILENAME);
            $thiswoman -> hasImage = false;
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 59 \n", FILE_APPEND);
        }
        file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":buildWomanObj 61xs \n", FILE_APPEND);
        
        return $thiswoman;
    }


    /**
     * 
     * Old way was to store all images in one directory, but webdav couldn't hang, so we hashed the file name and made a directory with teh first two chars
     * 
     */
     public function getWomanImg($womanRef){
        $imgPath = false;
        $womanPhoto = $womanRef.".JPG";
        if (file_exists(WOMAN_IMAGE_FILESYSTEM_DIR . "/".$womanPhoto)) {
            return WOMAN_IMAGE_URL_DIR . "/".$womanPhoto;
        }
        return $imgPath;
    }

    // Gets unsponsored children based on gender, age and community. Supports pagination via $page and $count. 
    // Previous results will be cached in session data to improve pagination performance.
    public function getUnsponsoredWomen($page, $count, $event = null, $program){
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);

        // Make sure $page and $count are positive 
        $page = max($page, 1);  
        $count = max($count, 1);
        
        if(!empty($event)){
            $res = RNCP\ROQL::query("select sponsorship.Event.Description, sponsorship.Event.DisplayName, sponsorship.Event.Status.LookupName from sponsorship.Event where sponsorship.Event.ID = ".$event)->next();
            while($eventObj = $res->next()) {
                if ($eventObj['LookupName'] != "Expired"){
                    $eventName = $eventObj['DisplayName'];
                    $eventDesc = $eventObj['Description'];
                }else{
                    $result = array(
                        'data' => $matchUnsponChildPageResult,
                        'metadata' => array(
                            'filters' => array(
                                'program' => null
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
            
        }else{
            $event = null;
        }
        
        // Filters have changed, need to query DB to obtain new result
        ////logMessage("Filters have changed, preparing to query DB...");
        try{
            $roqlString = $this->buildUnsponsoredWomanIDRoqlString($event, $program);
            $resultSet = RNCP\ROQL::query($roqlString)->next();
            $matchingUnsponWomanIDs = array();
            while($res = $resultSet->next()){
                try{
                    $womanID = $res['ID'];
                    $woman = RNCP\sponsorship\Woman::fetch($womanID);
                    if( $woman -> imageLocation = $this->getWomanImg($woman -> WomanRef)){
                        $matchingUnsponWomanIDs[] = $womanID;
                    }
                }catch(\Exception $e){
                    logMessage($e->getMessage());
                    throw $e;
                }
            }

            logMessage($matchingUnsponWomanIDs);
            logMessage($roqlString);

            // Cache new result in session data
            $newResult = array(
                'eventName' => $eventName,
                'eventDescription' => $eventDesc, 
                'data' => implode(',', $matchingUnsponWomanIDs),
                'filters' => array(
                    'program' => $program
                )
            );
            get_instance()->session->setSessionData(
                array('lastUnsponsoredChildIDResult' => $newResult)
            );

        }catch(\Exception $e){
            logMessage($e->getMessage());
            throw $e;
        }

        $result = $newResult;

        if(!empty($result['data'])){
            // Transform child ID string into array
            $result['data'] = explode(',', $result['data']);

            // To support pagination, we need to build a subset of the last result based on $page and $count
            //logMessage("Building page result...");
            $resCnt = count($result['data']);
            $start = ($page - 1) * $count;
            $len = min($count, $resCnt - $start);
            
            // logMessage("Total matching unsponsored child ID count: $resCnt");
            // logMessage("Start index: $start");
            // logMessage("Length: $len");
            
            if($start < $resCnt){
                $matchUnsponWomanPageResultIDs = array_slice($result['data'], $start, $len);
            }else{
                $matchUnsponWomanPageResultIDs = array();
            }
        }else{
            $matchUnsponWomanPageResultIDs = array();
        }

        // Transform the child ID array into an array of child objects
        $matchUnsponWomanPageResult = array();
        foreach($matchUnsponWomanPageResultIDs as $womanID){
            try{
                $woman = RNCP\sponsorship\Woman::fetch($womanID);
                $womanObj = $this->buildWomanObj($woman);
                $matchUnsponWomanPageResult[] = $womanObj;
            }catch(\Exception $e){
                logMessage($e->getMessage());
                throw $e;
            }
        }

        $result = array(
            'data' => $matchUnsponWomanPageResult,
            'metadata' => array(
                'filters' => array(
                    'program' => $program
                ),
                'page' => $page,
                'lastPage' => intval(ceil($resCnt / $count)),
                'count' => $count,
                'eventName' => $eventName,
                'eventDescription' => $eventDesc, 
            )
        );

        logMessage($result);
        return $result;
    }

    /* Utility function for building the ROQL queryObject/query string for fetching unsponsored children */
    private function buildUnsponsoredWomanIDRoqlString($event = null, $program) {
        logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);

        $whereClauses = array();
        $whereClauses[] = "SPONSORSHIP.Woman.WomanRef Is Not Null";
        //logMessage("Default WHERE clauses: {$whereClauses}");

        // Build where clauses
        if(!is_null($event)){
            $whereClauses[] = "SPONSORSHIP.Woman.Event.ID = " . $event;
            $whereClauses[] = "SPONSORSHIP.Woman.Event.Status.LookupName != 'Expired'";
            //$whereClauses[] = "SPONSORSHIP.Woman.ChildEventStatus In ( 1 )";//web hold only
        }else{
            $whereClauses[] = "(SPONSORSHIP.Woman.ScholarshipStatus=2 OR SPONSORSHIP.Woman.ScholarshipStatus is null)";
            //$whereClauses[] = "SPONSORSHIP.Child.ChildEventStatus is null";
        }

        if (!is_null($program) && $program != 0) {  //0 value is all priorities
            $whereClause = "SPONSORSHIP.Woman.Program = " . $program;
            $whereClauses[] = $whereClause;
        }


        $roql = "SELECT SPONSORSHIP.Woman.ID `ID` FROM SPONSORSHIP.Woman ";

        if (count($whereClauses) > 0) {
            $roql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        //logMessage("Returned ROQL: " . $roql);
        return $roql;
    }

    
    public function getWoman($id) {
        try{
            logMessage(" Starting: " . __FUNCTION__ . " in " . __CLASS__);
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model 242 \n", FILE_APPEND);
            if (is_null($id) || $id < 1) {
                return null;
            }
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model 246 \n", FILE_APPEND);
            $woman = array();
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model 249 \n", FILE_APPEND);
            $resultSet = RNCP\ROQL::queryObject("select SPONSORSHIP.Woman from SPONSORSHIP.Woman WHERE ID = " . $id . " ") -> next();
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model 251 \n", FILE_APPEND);
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":woman query: select SPONSORSHIP.Woman from SPONSORSHIP.Woman WHERE ID = " . $id . " \n", FILE_APPEND);
            while ($woman = $resultSet -> next()) {
                file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model 254 \n", FILE_APPEND);
                $women[] = $this -> buildWomanObj($woman);
            }
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model 255 \n", FILE_APPEND);
            return $women;
        }catch(Exception $e){
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model Exception ".print_r($e->getMessage(), true)." \n", FILE_APPEND);
        }catch (RNCPHP\ConnectAPIError $err) {
            file_put_contents("/tmp/cartStorage_" . date('Y_m_d') . ".log", date('Y/m/d h:i:s').":Woman Model cx Exception ".print_r($err->getMessage(), true)." \n", FILE_APPEND);
        }
        
    }

    
    public function getActivePledgeTotal($womanId, $active_pledge_status_list = array(1,2,43) ){

        $totalAmt = 0;
        $numTotalPledges = 0;
        
        $roql = "Select donation.pledge.PledgeAmount, donation.pledge.PledgeStatus, donation.pledge.Frequency from donation.pledge where donation.pledge.Woman.ID = ".$womanId;
        $pledgeObj = RNCP\ROQL::query($roql)->next();
        
        while($pledge = $pledgeObj->next()) {
    
            if(intval($pledge['PledgeStatus']) == PLEDGE_SPECIAL_HOLD){
                return false;
            }
    
            if(in_array(intval($pledge['PledgeStatus']), $active_pledge_status_list)){
                
                if($pledge['Frequency'] == 1)//annual
                    $totalAmt += $pledge['PledgeAmount'] / 12;
                else if($pledge['Frequency'] == 7)//quarterly
                    $totalAmt += $pledge['PledgeAmount'] / 3;
                else //monthly
                    $totalAmt += $pledge['PledgeAmount'];
                
                
            }
            $numTotalPledges++;
        }
        
        return $totalAmt;
        
    }
    
    
    

    

}
