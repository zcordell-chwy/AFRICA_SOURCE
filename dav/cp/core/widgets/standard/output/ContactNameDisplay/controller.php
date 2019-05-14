<?php /* Originating Release: February 2019 */
  

namespace RightNow\Widgets;

class ContactNameDisplay extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(WDGT_DPRCATED_PCT_S_RELEASE_MSG), "November '12"), false);
    }
}
