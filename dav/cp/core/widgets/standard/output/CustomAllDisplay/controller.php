<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class CustomAllDisplay extends CustomAllInput
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->widgetType = 'output';
    }
}
