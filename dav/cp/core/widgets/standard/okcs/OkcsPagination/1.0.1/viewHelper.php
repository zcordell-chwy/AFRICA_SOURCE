<?

namespace RightNow\Helpers;

use RightNow\Utils\Url;

class OkcsPaginationHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Constructs a pagination href value.
     * @param  int $pageNumber Page number
     * @param  array  $pageFilter Paging filter
     * @return string CP URL
     */
    function pageLink ($pageNumber, array $pageFilter) {
        static $page;
        $page || ($page = \RightNow\Utils\Text::removeTrailingSlash($_SERVER['SCRIPT_URI']));

        return Url::addParameter($page, $pageFilter['key'], $pageNumber) . Url::sessionParameter();
    }
}
