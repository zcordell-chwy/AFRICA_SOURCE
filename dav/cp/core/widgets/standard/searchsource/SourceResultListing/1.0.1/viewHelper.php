<?

namespace RightNow\Helpers;

use RightNow\Libraries\Formatter,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

class SourceResultListingHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Adds the given filters as url parameters on $url.
     * @param  string $url     Base page URL
     * @param  array $filters Search filters to add as params
     * @return string          String Built-up url
     */
    function constructMoreLink($url, $filters) {
        foreach ($filters as $filter) {
            if ($filter['key'] && $filter['value']) {
                $url = Url::addParameter($url, $filter['key'], $filter['value']);
            }
        }

        return $url . Url::sessionParameter();
    }

    /**
     * Handles text formatting for the search result's summary.
     * @param string $summary Text content
     * @param string|boolean $highlight Set to true to highlight current 'kw' URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter. Useful when 'kw' param is unavailable
     * @param int $truncateSize Number of characters to begin truncation
     * @return string Formatted text
     */
    function formatSummary ($summary, $highlight, $truncateSize) {
        $truncated = \RightNow\Utils\Text::truncateText($summary, $truncateSize);

        return ($highlight) ? \RightNow\Libraries\Formatter::highlight($truncated) : $truncated;
    }
}
