<?php
namespace Custom\Widgets\eventus;

class donateIndividual extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        //return parent::getData();
        $f_id = getUrlParm('f_id');
        logMessage($f_id);
        $items = $this -> CI -> model('custom/items') -> getSingleDonationItem($f_id);
        logMessage($items);
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
	        	logMessage("redirecting, no Donation Fund or Donation Appeal field value provided");
	        	header("Location:" . getConfig(CUSTOM_CFG_DONATE));
	        }
        }
        
        logMessage('getting page settings');
        $pageSettings = json_decode(getConfig(CUSTOM_CFG_SINGLE_DONATION_PAGE_SETTINGS));
        logMessage($pageSettings);

        if($pageSettings){
            foreach($pageSettings->funds as $fund){
                logMessage("Compare:".$fund->id.":".$f_id);
                if($fund->id == $f_id){
                    $this -> data['showSingle'] = $fund->showSingle;
                    $this -> data['defaultAmount'] =  $fund->defaultAmount;
                }
            }
        }

        logMessage($this -> data['showSingle'] );
                    logMessage($this -> data['defaultAmount']);

    }

}
