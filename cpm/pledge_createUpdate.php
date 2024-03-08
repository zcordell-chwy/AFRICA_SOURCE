<?
/*
 * CPMObjectEventHandler: pledge_createUpdate
 * Package: RN
 * Objects: donation\pledge
 * Actions: Create, Update
 * Version: 1.2
 */

//ini_set('display_errors', 'On');
error_reporting(0);

use \RightNow\Connect\v1_2 as RNCPHP;
use \RightNow\CPM\v1 as RNCPM;

use \RightNow\Connect\v1_3 as RNCPHPV3;

class pledge_createUpdate implements RNCPM\ObjectEventHandler {

    public static function apply($run_mode, $action, $obj, $n_cycles) {
            
        static $sponsored_id = 3;
        static $co_sponsor_id = 4;
        static $dropped_id = 5;
        static $graduated_id = 8;
        static $departed_id = 7;
        static $death_id = 49;
        static $update_id = 10;

        $childID = (isset($obj->Child))? $obj->Child->ID: null;
        //only need to run this if its a sponsorship pledge
        if(!$childID){
            return;
        }
        
        $previousPledgeStatusID = ($action == RNCPM\ActionUpdate && isset($obj -> prev))? $obj -> prev -> PledgeStatus -> ID : null;
        
        
        try{
            if ($n_cycles < 1 &&  $childID > 0 && 
                        (  ($action == RNCPM\ActionUpdate && $obj -> PledgeStatus -> ID != $previousPledgeStatusID) || $action == RNCPM\ActionCreate)) 
                {
                    
                    $child = RNCPHP\sponsorship\Child::fetch($childID);
                    
                    //fwrite($file,"LookupName = ".$obj->PledgeStatus->LookupName);
                    switch($obj->PledgeStatus->LookupName){
                        
                        case "Active":
                        case "Manual Pay":
                        case "On Hold - Non Payment":
    
                            if ($obj->Child->SponsorshipStatus->ID == $co_sponsor_id){
                                $child->SponsorshipStatus = (self::getPledgesPerChild($obj->Child->ID) >= 2) ? RNCPHP\sponsorship\SponsorshipStatus::fetch($sponsored_id): RNCPHP\sponsorship\SponsorshipStatus::fetch($co_sponsor_id);
                            }else{
                                $child->SponsorshipStatus = $sponsored_id;
                            }
    
                            break;
                            
                        case "Cancelled By Customer":
                        case "Cancelled By ANLM":
                        case "Cancelled By Admin":
                            
                            // if ($obj->Child->coSponsorNeeded){
                                // $child->SponsorshipStatus = (self::getPledgesPerChild($obj->Child->ID) == 0) ? RNCPHP\sponsorship\SponsorshipStatus::fetch($update_id): RNCPHP\sponsorship\SponsorshipStatus::fetch($co_sponsor_id);
                            // }else{
                                // $child->SponsorshipStatus = RNCPHP\sponsorship\SponsorshipStatus::fetch($update_id);
                            // }
                            //fwrite($file,"setting to false = ".$obj->PledgeStatus->LookupName);
                            $child->DoNotAutoUpdate = 0;
                            
                            
                            break;
                            
                        case "Cancelled By Graduated":
                            
                            $child->SponsorshipStatus =  RNCPHP\sponsorship\SponsorshipStatus::fetch($graduated_id);
                            
                            break;
                            
                         case "Cancelled By Departed":
                             
                             $child->SponsorshipStatus = RNCPHP\sponsorship\SponsorshipStatus::fetch($departed_id);
                            
                            break;
                             
                         case "Cancelled By Death":
                            
                            $child->SponsorshipStatus = RNCPHP\sponsorship\SponsorshipStatus::fetch($death_id);
                             
                            break;
                             
                         default:
                         
                            //$child->SponsorshipStatus = RNCPHP\sponsorship\SponsorshipStatus::fetch($update_id);
                             
                             break;
                            
                        
                        
                    }
    
                    //fwrite($file,"Sponsorship Status  = ". $child->SponsorshipStatus->ID."\n" );
    
                    $child->save(RNCPHP\RNObject::SuppressAll);
    
                }
            } catch(RNCPHP\ConnectAPIError $e) {
                echo "in RNT catch";
                print_r($e->getMessage());
            }catch(Exception $e){
                echo "in catch";
                print_r($e->getMessage());
            }
    }

    
    function getPledgesPerChild($childId){
        
        try{
            $roql = "SELECT count() FROM donation.pledge WHERE (donation.pledge.PledgeStatus.LookupName = 'Active' OR donation.pledge.PledgeStatus.LookupName = 'Manual Pay' OR donation.pledge.PledgeStatus.LookupName = 'On Hold - Non Payment') AND donation.pledge.Child.ID = ".intval($childId);
            $res = RNCPHP\ROQL::query( $roql )->next();
            $numPledges = $res->next();
        }catch(Exception $e){

        }

        return $numPledges["count()"];
        
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
        } catch (exception $err) {
            // error but continue
        }
    }
    

    

}


class pledge_createUpdate_TestHarness implements RNCPM\ObjectEventHandler_TestHarness {
    
    static $pledge_invented = null;

    public static function setup() {

        $monthlyPledge = new RNCPHP\donation\pledge;
        $monthlyPledge -> PledgeAmount = "7.00";
        $monthlyPledge -> NextTransaction = strtotime("last Monday");
        $monthlyPledge -> PledgeStatus = RNCPHP\donation\PledgeStatus::fetch(61);
        $monthlyPledge -> save();
        
        static::$pledge_invented = $monthlyPledge;
        
        return;
        
    }

    public static function fetchObject($action, $object_type) {
       
        return (static::$pledge_invented);
    }

    public static function validate($action, $object) {
        
        return true;

    }

    public static function cleanup() {

        static::$pledge_invented -> destroy();
        static::$pledge_invented = NULL;



        return;
    }
    
    

}