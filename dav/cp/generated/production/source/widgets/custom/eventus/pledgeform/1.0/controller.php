<?php
namespace Custom\Widgets\eventus;
use \RightNow\Connect\v1_2 as RNCPHP;

class pledgeform extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

    }

    function getData() {

        try {
            $pledge_id = $this -> data['attrs']['pledge_id'];
            //get the pledge
            if ($pledge_id > 0) { 
                $this -> data['pledge'] = $this -> CI -> model('custom/pledge_model') -> get($pledge_id);
                $this -> data['pledgeBalance'] = $this -> CI -> model('custom/donation_model') -> GetAheadBehind($this -> data['pledge']);
            } else {
                logMessage("no pledge");
            }

            $this -> data['Child'] = $this -> CI -> model('custom/sponsorship_model') -> getChild($this -> data['pledge'] -> Child -> ID);

            //get all the values in the frequency table
            $roql = "SELECT donation.DonationPledgeFreq FROM donation.DonationPledgeFreq ";
            $results = RNCPHP\ROQL::queryObject($roql) -> next();
            while ($freq = $results -> next()) {
                $this -> data['freq'][$freq -> ID] = $freq -> LookupName;
            }

            //get all the payment methods to display
            $this -> data['paymentMethods'] = $this -> CI -> model('custom/paymentMethod_model') -> getCurrentPaymentMethodsObjs($this -> CI -> session -> getProfileData('c_id'));


        } catch(Exception $e) {
            logMessage("Caught Error");
            logMessage($e -> getMessage());
        }


    }

    function _getValues($parent) {
        try {
            // $parent is a non-associative (numerically-indexed) array
            if (is_array($parent)) {

                foreach ($parent as $val) {
                    $this -> _getValues($val);
                }
            }

            // $parent is an associative array or an object
            elseif (is_object($parent)) {

                while (list($key, $val) = each($parent)) {

                    $tmp = $parent -> $key;

                    if ((is_object($parent -> $key)) || (is_array($parent -> $key))) {
                        $this -> _getValues($parent -> $key);
                    }
                }
            }
        } catch (exception $err) {
            // error but continue
        }
    }

}
