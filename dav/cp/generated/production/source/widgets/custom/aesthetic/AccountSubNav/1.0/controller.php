<?php
namespace Custom\Widgets\aesthetic;

class AccountSubNav extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
    	$this -> data['contactId'] = $this -> CI -> session -> getProfileData('c_id');
        return parent::getData();
        
    }
}