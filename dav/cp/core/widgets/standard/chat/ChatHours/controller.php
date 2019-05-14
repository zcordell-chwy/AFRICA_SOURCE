<?php /* Originating Release: February 2019 */
  

namespace RightNow\Widgets;

class ChatHours extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['chatHours'] = $this->CI->model('Chat')->getChatHours()->result;
        $this->data['show_hours'] = !$this->data['chatHours']['inWorkHours'];
    }
}
