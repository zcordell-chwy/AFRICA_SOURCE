<?php
namespace Custom\Widgets\eventus;
use \RightNow\Connect\v1_4 as RNCPHP;
 
class inspectlet extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        

    }

    function getData() {
        
        $this -> data['js']['snippet'] = getConfig(CUSTOM_CFG_inspectlet_snippet);
        $this -> data['js']['transaction'] = $this -> CI -> session -> getSessionData('transId');

    }

}
