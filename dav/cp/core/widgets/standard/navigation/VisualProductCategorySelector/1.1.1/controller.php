<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class VisualProductCategorySelector extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $hierarchyType = $this->data['attrs']['type'];
        $itemID = $this->data['attrs']['show_sub_items_for'] ?: null;

        if (!$items = $this->CI->model('Prodcat')->getDirectDescendants($hierarchyType, $itemID)->result) return false;

        if ($items = $this->limitItems($items, $this->data['attrs']['top_level_items'], $this->data['attrs']['maximum_items'])) {
            $this->data['js'] = array(
                'items'              => $items,
                'appendedParameters' => Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . Url::sessionParameter(),
            );
            if($item = $this->CI->model('Prodcat')->get($itemID)->result) {
                $this->data['attrs']['label_title'] = $item->Name;
            }
        }
        else {
            return false;
        }
    }

    /**
     * Filters the given items.
     * @param array $items Result of Prodcat model's #getDirectDescendants
     * @param string $limitTo Comma-separated list of item ids to restrict to
     * @param integer $maxLimit Maximum number of items to include
     * @return array filtered items
     */
    function limitItems($items, $limitTo, $maxLimit) {
        if ($limitTo !== '') {
            $items = $this->filterItemIDs($items, $limitTo);
        }
        if (count($items) > $maxLimit) {
            $items = $this->limitToMaxSize($items, $maxLimit);
        }

        return $items;
    }

    /**
     * Filters the items to the comma-separated item ids.
     * @param  array $items Items to filter
     * @param  array $limitTo  Comma-separated list of item ids to restrict to
     * @return array filtered items
     */
    protected function filterItemIDs($items, $limitTo) {
        // Get the comma-separated integer ids specified.
        $limitTo = array_map('intval', explode(',', $limitTo));
        // Filter out any erroneous entries (intval converted them to zero)
        // and make the values the keys for faster lookups.
        $limitTo = array_flip(array_filter($limitTo));

        // Return a proper zero-indexed array with no holes (array_filter leaves holes).
        return array_values(array_filter($items, function($item) use ($limitTo) {
            return !is_null($limitTo[$item['id']]);
        }));
    }

    /**
     * Slices an array to the given size.
     * @param  array   $items Items to resize
     * @param  integer $maxLimit New size
     * @return array   size-limited array
     */
    protected function limitToMaxSize($items, $maxLimit) {
        return array_slice($items, 0, $maxLimit);
    }
}
