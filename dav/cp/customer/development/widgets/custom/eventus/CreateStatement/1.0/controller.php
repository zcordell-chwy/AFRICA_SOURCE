<?php
namespace Custom\Widgets\eventus;

use \RightNow\Connect\v1_2 as RNCPHP;

class CreateStatement extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        
        $pledgeID = getUrlParm("PledgeId");
        $this -> data['contact'] = getUrlParm("c_id");
        
        if($pledgeID <= 0){
            die("Invalid Pledge ID sent to statement");
        }
        
        $pledge = RNCPHP\donation\pledge::fetch($pledgeID);
        
        if($pledge->ID > 0){
            $this -> data['pledge'] = $pledge;
            $this -> data['pledge'] -> AheadBehind = $this->_getAheadBehind($pledge->NextTransaction, $pledge->Balance, $pledge->PledgeAmount, $pledge->Frequency->LookupName);
        }
        

    }
    
    function _getAheadBehind($nextTrans, $currentBalance, $pledgeAmount, $frequency){
        
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

        echo $months. ' month, '. ($date2 - $date1) / (60*60*24). ' days <br/>'; // 120 month, 26 days
        return $months;
        
    }
    
    

}
