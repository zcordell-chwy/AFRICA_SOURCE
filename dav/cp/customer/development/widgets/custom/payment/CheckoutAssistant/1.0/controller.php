<?php
namespace Custom\Widgets\payment;

class CheckoutAssistant extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

    	// Build labels
    	$this->data['js']['labels'] = array(
    		'payNow' => 'pay now',
    		'back' => 'back',
    		'continue' => 'continue',
    	);

        // Support skipping confirm donor billing info tab
        $skipConfirmDonorBillingTab = \RightNow\Utils\Url::getParameter('skip_confirm_donor');
        if($skipConfirmDonorBillingTab == 1){
            $this->data['js']['skip_confirm_donor_billing_tab'] = true;
        }else{
           $this->data['js']['skip_confirm_donor_billing_tab'] = false; 
        }

        return parent::getData();
    }
}