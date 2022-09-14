<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class UserList extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (strpos($this->data['attrs']['activity_time_period'], "past_") === false) {
            $interval = null;
        }
        else {
            $interval = str_replace("past_", "", $this->data['attrs']['activity_time_period']);
        }

        $this->data['user_data'] = $this->CI->model('SocialUser')->getListOfUsers($this->data['attrs']['content_type'], $this->data['attrs']['max_user_count'], $interval)->result;
    }
}
