<?php
namespace Custom\Models;

use RightNow\Connect\v1 as RNCP;

require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();

class sponsor_model extends  \RightNow\Models\Base {

    function __construct() {
        parent::__construct();
        //This model would be loaded by using $this->load->model('custom/Sample_model');
        $this -> CI -> load -> helper('constants');
    }

    public function getSponsoredChildren($cid) {
         
        
        $children = array();
        $roql = 'select donation.pledge from donation.pledge where donation.pledge.Child is not null and donation.pledge.Contact = ' . intval($cid) . ' and (donation.pledge.PledgeStatus.ID = 1 or donation.pledge.PledgeStatus.ID = 43)';
        $resultSet = RNCP\ROQL::queryObject($roql) -> next();

        while ($sponsorships = $resultSet -> next()) {

            $child = $sponsorships -> Child;
            if ($child -> ID != null) {
                $thischild = new \stdClass();
                $thischild -> PledgeId = $sponsorships->ID; 
                $thischild -> ID = $child -> ID;
                $thischild -> Gender = $child -> Gender -> LookupName;
                $thischild -> ChildRef = $child -> ChildRef;
                $thischild -> GivenName = $child -> GivenName;
                $thischild -> FullName = $child -> FullName;
                $thischild -> Age = $child -> Age;
                $thischild -> Balance = $sponsorships -> Balance;
                $thischild -> BirthDay = $child -> MonthOfBirth."/".$child->DayOfBirth."/".$child->YearOfBirth;
                $thischild -> Grade = $child -> Grade -> LookupName;
                $thischild -> FamilyName = $child -> FamilyName;
                $thischild -> RecurringPaymentAmount = $sponsorships -> RecurringPaymentAmount;
                $thischild -> StartDate = gmdate("m-d-Y", $sponsorships -> StartDate);
                $thischild -> Frequency = $sponsorships -> Frequency;
                $thischild -> isBoarding = $child->isBoarding;
                if($thischild -> imageLocation = $this->CI->model('custom/sponsorship_model')->getChildImg($child -> ChildRef)){
                    $thischild -> hasImage = true;
                } else {
                    $thischild -> imageLocation = CHILD_IMAGE_URL_DIR . "/" . CHILD_NO_IMAGE_FILENAME;
                    $thischild -> hasImage = false;
                }

                $thischild->ExcludedItems = $this->_getExcludedItems($child->Community->ID, $child->SchoolLevel->ID, $child->Gender->ID);
                $children[] = $thischild;
            }
        }
        
        $thischild = new \stdClass();
        $thischild -> ID = NEEDY_CHILDREN_ID;
        $thischild -> Gender = '';
        $thischild -> ChildRef = 'NeedyChild';
        $thischild -> GivenName = "Any Student in Need";
        $thischild -> Age = 1;
        $thischild -> Balance = "";
        $thischild -> RecurringPaymentAmount = "";
        $thischild -> StartDate = "";
        $thischild -> Frequency = "";
        $thischild -> imageLocation = CHILD_IMAGE_URL_DIR . "/" . CHILD_NO_IMAGE_FILENAME;
        $thischild -> hasImage = false;
        $thischild -> ExcludedItems = array();
        $thischild -> ExcludedItems[] = 0; 
        
        ////logMessage($thischild->ExcludedItems);
         
        
        $children[] = $thischild;
        
        
        

        return $children;
    }

    public function _getExcludedItems($communityId, $schoolLevelId, $genderID){
        
        $excludedList = array();
        $excludedList[] = 0;  //load one up with 0 in case we don't return anything
        //
        $roql = 'select online.ExcludedItmPerCommun from online.ExcludedItmPerCommun where online.ExcludedItmPerCommun.Community  = ' . intval($communityId);
        //logMessage($roql);
        $resultSet = RNCP\ROQL::queryObject($roql) -> next();

        while ($excludedObj = $resultSet -> next()) {
            $excludedList[] = $excludedObj->Item->ID;
        }
        
        // Now add items exluded by school level
        $roql = 'select online.ExcludedItmPerCommun from online.ExcludedItmPerCommun where online.ExcludedItmPerCommun.ChildSchoolLevel  = ' . intval($schoolLevelId);
        
        $resultSet = RNCP\ROQL::queryObject($roql) -> next();

        while ($excludedObj = $resultSet -> next()) {
            $excludedList[] = $excludedObj->Item->ID;
        }
        
        // Now add items excluded by gender
        $roql = 'select online.ExcludedItmPerCommun from online.ExcludedItmPerCommun where online.ExcludedItmPerCommun.ChildGender  = ' . intval($genderID);
        
        $resultSet = RNCP\ROQL::queryObject($roql) -> next();

        while ($excludedObj = $resultSet -> next()) {
            $excludedList[] = $excludedObj->Item->ID;
        }
        
        return $excludedList;
    }

    public function searchwidget($key) {
        $sql = "";
        if ($key == "") {
            $sql = "Select TeamTrips.TripMember from TeamTrips.TripMember Order by TeamTrips.TripMember.Contact.Name.Last ASC;";
        } else {
            $sql = "Select TeamTrips.TripMember from TeamTrips.TripMember Where TeamTrips.TripMember.DisplayName like '$key%' or TeamTrips.TripMember.Contact.Name.First like '$key%' or TeamTrips.TripMember.Contact.Name.Last like '$key%' Order by TeamTrips.TripMember.Contact.Name.Last ASC;";
        }

        $resultSet = RNCP\ROQL::queryObject($sql) -> next();
        $res = array();
        $i = 0;
        $baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();
        while ($sponsorships = $resultSet -> next()) {
            $thismem = new \stdClass();
            $thismem -> PhotoUrl = $sponsorships -> PhotoUrl;
            $thismem -> DisplayName = $sponsorships -> DisplayName;
            $thismem -> Trip = $sponsorships -> Trip -> ID;
            $thismem -> ID = $sponsorships -> ID;
            $thismem -> PhotoUrl = $sponsorships -> PhotoUrl;
            //13-06-2014 change
            $thismem -> Fund = $sponsorships -> Fund -> ID;
            $thismem -> Appeal = $sponsorships -> Appeal -> ID;

            if (strpos($thismem -> PhotoUrl, '.') !== FALSE) {
                if (strpos($thismem -> PhotoUrl, 'images/') == FALSE) {
                    $thismem -> PhotoUrl = $baseURL . "/euf/assets/themes/africa/images/TeamTripIcon.png";
                }
            } else {
                $thismem -> PhotoUrl = $baseURL . "/euf/assets/themes/africa/images/TeamTripIcon.png";
            }
            if (($thismem -> Trip != null || $thismem -> Trip != "") && $thismem->DisplayName != "") {
                $res[$i] = $thismem;
                $i++;
            }
        }
        return $res;
    }
    
    

}
