<?php /* Originating Release: February 2019 */
  

namespace RightNow\Widgets;

class ChatRequestEmailResponseButton extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['js']['baseUrl'] = \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage');
    }
}
