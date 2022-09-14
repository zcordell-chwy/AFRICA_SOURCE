<?php
namespace Custom\Widgets\payment;
class SingleDonation extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }
    function getData() {
        // return parent::getData();
        $f_id = getUrlParm('f_id');
        // print_r($f_id);
        $items = $this -> CI -> model('custom/items') -> getSingleDonationItem($f_id);
        // print_r($items);die;
        $this->CI->session->setSessionData(array("appeal" => $items[0]->DonationAppeal ? $items[0]->DonationAppeal : ''));
        $this->CI->session->setSessionData(array("donation_fund" => $items[0]->DonationFund ? $items[0]->DonationFund : ''));
        $this->CI->session->setSessionData(array("fund_id" => $f_id));
        // print_r($items[0]->DonationFund);die;
        $this -> data['Items'] = $items;
        $this->data['DefaultMonthlyAmount'] = $items[0]->DefaultMonthlyAmount ? $items[0]->DefaultMonthlyAmount : '';
        $this->data['DefaultOneTimeAmount'] = $items[0]->DefaultOneTimeAmount ? $items[0]->DefaultOneTimeAmount : '';

        $this->data['js']['DefaultMonthlyAmount'] = $items[0]->DefaultMonthlyAmount ? $items[0]->DefaultMonthlyAmount : '';
        $this->data['js']['DefaultOneTimeAmount'] = $items[0]->DefaultOneTimeAmount ? $items[0]->DefaultOneTimeAmount : '';
        
        $this->data['PhotoURL'] = $items[0]->PhotoURL ? $items[0]->PhotoURL : '';
        $this->data['Description'] = $items[0]->Description ? $items[0]->Description : '';

        $this -> data['showOnetime'] = $items[0]->DefaultOneTimeAmount;
        $this -> data['showMonthly'] = $items[0]->DefaultMonthlyAmount;
        $this -> data['defaultMonthly'] =  ($items[0]->DefaultMonthlyAmount) ? $items[0]->DefaultMonthlyAmount : null;
        $this -> data['defaultOnetime'] =  ($items[0]->DefaultOneTimeAmount) ? $items[0]->DefaultOneTimeAmount : null;

        if($items[0]->CampaignFrequency)
            $this->data['js']['frequency'] = ($items[0]->CampaignFrequency->ID) ? $items[0]->CampaignFrequency->ID : null;

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
        
        if($pageSettings){
            foreach($pageSettings->funds as $fund){
                if($fund->id == $f_id){
                    // $this -> data['showOnetime'] = $fund->onetime;
                    // $this -> data['showMonthly'] = $fund->monthly;
                    // $this -> data['defaultMonthly'] =  ($fund->monthly) ? $fund->defaultmonthly : null;
                    // $this -> data['defaultOnetime'] =  ($fund->onetime) ? $fund->defaultonetime : null;
                }
            }
        }

    }
}