<?php /* Originating Release: February 2019 */


namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

class OkcsProductCategorySearchFilter extends \RightNow\Widgets\ProductCategorySearchFilter {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $sourceID = $this->data['attrs']['source_id'];
        if(Config::getConfig(OKCS_ENABLED) && ($sourceID === 'OKCSBrowse' || $sourceID === 'OKCSSearch')) {
            $this->data['attrs']['filter_type'] = $this->normalizeFilterType();
            $this->setLabelDefaults();
            $this->data['js']['initial'] = array();
            $this->data['js']['viewType'] = "explorer";
            $this->data['js']['hierData'] = $this->getTreeData(array(), $this->data['attrs']['filter_type']);
        }
        else {
            parent::getData();
        }
    }

    /**
    * Method to populate product category tree data
    * @param array $chainOfValues Search results
    * @param string $dataType Silter type product or category
    * @return object Product category tree object
    */
    function getTreeData (array $chainOfValues, $dataType) {
        $sourceID = $this->data['attrs']['source_id'];
        if(Config::getConfig(OKCS_ENABLED) && ($sourceID === 'OKCSBrowse' || $sourceID === 'OKCSSearch'))
        {
            $channelRecordID = Url::getParameter('channelRecordID');
            $defaultChannel = ($channelRecordID !== null) ? $channelRecordID : $defaultChannel = $this->CI->model('Okcs')->getDefaultChannel();
            $this->data['productVal'] = Url::getParameter('productRecordID');
            $this->data['categoryVal'] = Url::getParameter('categoryRecordID');
            $categories = $this->CI->model('Okcs')->getChannelCategories($defaultChannel);
            if (!($categories->result) && $categories->errors) {
                echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($categories->error));
                return false;
            }
            $this->data['attrs']['filter_type'] = $this->normalizeFilterType();
            if ($categories !== null) {
                $this->data['results'] = $this->getCategoriesByType($categories, strtoupper($this->data['attrs']['filter_type']));
                if($this->data['js']['viewType'] === 'explorer')
                {
                    $explorerViewClass = 'rn_' . Text::getSubstringBefore($this->instanceID, '_') . 'ExplorerView';
                    $this->classList->add($explorerViewClass);
                    if(count($this->data['results']) === 1) {
                        foreach ($this->data['results']->results as $key => $category) {
                            $childCategories = $this->CI->model('Okcs')->getChildCategories($category->referenceKey)->results;
                            if (count($childCategories) > 0) {
                                $category->selectedClass = 'rn_CategoryExplorerLink';
                                foreach ($childCategories as $childCategory) {
                                    $childCategory->selectedClass = 'rn_CategoryExplorerLink';
                                    $childCategory->depth = 0;
                                    $childCategory->name = htmlspecialchars($childCategory->name);
                                    $childCategory->type = $this->data['attrs']['filter_type'];
                                    $this->setCssClassForParentAndChildCategory($category, $childCategory);
                                }
                            }
                            $this->data['results']->results[$key]->children = $childCategories;
                        }
                    }
                    return $this->data['results'];
                }
                else
                {
                    $treeMap = $this->convertExplorerDataToTree($this->data['results']->results);
                    array_unshift($treeMap, array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
                    $this->data['js']['hierData'][0] = $treeMap;
                    return $this->data['js']['hierData'];
                }
            }
        }
        else {
            return parent::getTreeData($chainOfValues, $dataType);
        }
    }

    /**
    * Method to set css class for the parent level product or category
    * @param object $category Parent product or category object
    * @param string $childCategory Child product or category object
    */
    private function setCssClassForParentAndChildCategory($category, $childCategory) {
        if($childCategory->parent->referenceKey === $this->data['productVal'] || $childCategory->parent->referenceKey === $this->data['categoryVal']) {
            $category->selectedClass = 'rn_CategoryExplorerLinkSelected';
        }
        else if($childCategory->referenceKey === $this->data['productVal'] || $childCategory->referenceKey === $this->data['categoryVal']) {
            $childCategory->selectedClass = 'rn_CategoryExplorerLinkSelected';
        }
    }

    /**
    * Method to convert the explorer data object to tree object
    * @param object $results Category explorer object
    * @return object category tree object
    */
    protected function convertExplorerDataToTree($results) {
        $convertedObject = array();
        foreach ($results as $categoryValue) {
            array_push($convertedObject, array('id' => $categoryValue->referenceKey, 'label' => $categoryValue->name, 'selected' => false, 'hasChildren' => $categoryValue->hasChildren));
        }
        return $convertedObject;
    }

    /**
     * Method to filter categories based on the filter type, Product or Category.
     * @param array $categories Category list
     * @param string $categoryType Filter type
     * @return array category list
     */
    protected function getCategoriesByType($categories, $categoryType) {
        $index = 0;
        foreach($categories->results as $category){
            if($categoryType === 'PRODUCT')
            {
                if($category->externalType === 'PRODUCT'){
                    $categories->results[$index]->type = 'Product';
                    $categories->results[$index]->depth = 0;
                    $categories->results[$index]->name = htmlspecialchars($categories->results[$index]->name);
                }
                else
                    unset($categories->results[$index]);
            }
            else
            {
                if(!$category->externalType || $category->externalType === 'CATEGORY') {
                    $categories->results[$index]->type = 'Category';
                    $categories->results[$index]->depth = 0;
                    $categories->results[$index]->name = htmlspecialchars($categories->results[$index]->name);
                }
                else
                    unset($categories->results[$index]);
            }
            $index++;
        }
        return $categories;
    }
}