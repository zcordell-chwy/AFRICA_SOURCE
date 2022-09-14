<?
namespace Custom\Models;

use \RightNow\Connect\v1_3 as RNCPHP;
require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();

class pledge_model  extends \RightNow\Models\Base {
    function __construct() { 
        parent::__construct();
        //$this -> CI -> load -> helper('constants');
        //This model would be loaded by using $this->load->model('custom/frontstream_model');
    }

    public function get($pledge_id) {
        try {

            if ($pledge_id > 0) {
                return RNCPHP\donation\pledge::fetch($pledge_id);
            }
        } catch(Exception $e) {
            return null;
        }
 
    }

}
