<?php
namespace Custom\Widgets\eventus;

class donateIndividual extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        //return parent::getData();
        $f_id = getUrlParm('f_id');
        $items = $this -> CI -> model('custom/items') -> getSingleDonationItem($f_id);
        $this -> data['Items'] = $items;
        
        if (empty($items))//if the array is completely empty, then redirect users to home page
		{
		    header("Location:" . getConfig(CUSTOM_CFG_DONATE));
		}
		foreach( $this -> data['Items'] as $Item)	{ 
			//the donation fund and the donation appeal feilds are required to display a fund. 
		if ($Item -> DonationFund == "" ||  $Item -> DonationAppeal == ""
                    	|| $Item -> ID == "")
	        {
	        	header("Location:" . getConfig(CUSTOM_CFG_DONATE));
	        }
        }
        
        $pageSettings = json_decode(getConfig(CUSTOM_CFG_SINGLE_DONATION_PAGE_SETTINGS));
        $pageSettings = json_decode('{"funds":[{"id": 54, "onetime":true, "monthly": false, "defaultonetime": 15, "defaultmonthly": 15 }, {"id": 555, "onetime":true, "monthly": false, "defaultonetime": 15, "defaultmonthly": 15 }]}');

        if($pageSettings){
            foreach($pageSettings->funds as $fund){
                if($fund->id == $f_id){
                    $this -> data['showOnetime'] = $fund->onetime;
                    $this -> data['showMonthly'] = $fund->monthly;
                    $this -> data['defaultMonthly'] =  ($fund->monthly) ? $fund->defaultmonthly : null;
                    $this -> data['defaultOnetime'] =  ($fund->onetime) ? $fund->defaultonetime : null;
                }
            }
        }

    }

}
