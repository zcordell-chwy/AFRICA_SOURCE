<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Config;

class VirtualAssistantAvatar extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if (Config::getConfig(MOD_VA_ENABLED) !== true)
        {
            $this->reportError(Config::getMessage(MOD_VAENABLED_CFG_ENABLED_WIDGET_MSG), false);
            return false;
        }

        $this->classList->add('rn_Hidden');
    }
}
