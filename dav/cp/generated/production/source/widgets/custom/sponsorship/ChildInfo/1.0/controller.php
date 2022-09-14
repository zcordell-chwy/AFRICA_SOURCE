<?php

namespace Custom\Widgets\sponsorship;

use RightNow\Connect\v1_4 as RNCPHP;

class ChildInfo extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }
    function getData()
    {
        $this->data['backURL'] = \RightNow\Utils\Url::getParameter('back');
        $id = \RightNow\Utils\Url::getParameter('id');
        if (!$id) {
            return parent::getData();
        }

        $child = RNCPHP\sponsorship\Child::fetch($id);
        // echo "<pre>";
        // print_r($child);die;
        $this->data['js']['ChildID'] = $child->ID;
        $this->data['js']['Rate'] = $child->Rate;

        if($child->Gender->ID == 2) {
            $gender = 'She';
            $gender2 = 'Her';
        }
        else    {
            $gender = 'He';
            $gender2 = 'His';
        }

        $this->data['Desc'] = 'is part of '. $child->Community->Name . '. '. $gender .' is in ' . $child->Grade->LookupName . ' at school, and her favorite subject is ' . $child->FavoriteSubject->LookupName . '. ' . $child->GivenName . ' favorite hobby is ' . $child->FavoriteHobby->LookupName . '.';
        $this->data['Child'] = $child;
        $this->data['Gender'] = $gender;
        $this->data['Gender2'] = $gender2;
    
        if ($imageLocation = $this->CI->model('custom/sponsorship_model')->getChildImg($child->ChildRef)) {
            $hasImage = true;
        } else {
            $imageLocation = CHILD_IMAGE_URL_DIR . "/" . CHILD_NO_IMAGE_FILENAME;
            $hasImage = false;
        }
        $this->data['image'] = $imageLocation;

        return parent::getData();
    }
}
