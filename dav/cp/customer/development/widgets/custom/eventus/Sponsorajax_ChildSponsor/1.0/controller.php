<?php
namespace Custom\Widgets\eventus;

class Sponsorajax_ChildSponsor extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        parent::getData();
        //Perform php logic here
        //Get the url to fetch parameters
        $this -> data['ChildID'] = getUrlParm("ChildID");
        $gender = getUrlParm("Gender");
        $age = getUrlParm("Age");
        $community = getUrlParm("Community");
        //assign values to url parameters

        $this -> data['Gender'] = $gender;
        $this -> data['Age'] = $age;
        $this -> data['Community'] = $community;


        $gender = intval($gender);
        $age = intval($age);
        $community = intval($community);

        $id = -1;
        if (is_null($this -> data['ChildID']) || $this -> data['ChildID'] < 1) {
            $this -> data['ChildList'] = array();
        } else {
            $this -> data['ChildList'] = $this -> CI -> model('custom/sponsorship_model') -> getChild($this -> data['ChildID']);
        }

        $this -> data['ID'] = $this -> data['ChildID'];

    }

}
