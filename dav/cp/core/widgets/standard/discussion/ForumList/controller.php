<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Utils\Url;

class ForumList extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData() {
        $dataType = $this->data['attrs']['type'] === "product" ? "Product" : "Category";
        $prodCatValue = $this->data['attrs']['show_sub_items_for'] ?: null;

        $this->data['js']['readableProdcatIds'] = $this->data['js']['readableProdcatIdsWithChildren'] = $this->data['js']['permissionedProdcatList'] = $this->data['js']['permissionedProdcatIds'] = array();
        
        $this->data['productHeader'] = $this->data['attrs']['label_forum'];
        foreach ($this->data['attrs']['show_columns'] as $metadata) {
            $this->data['tableHeaders'][$metadata] = $this->data['attrs']['label_'.$metadata];
        }
        
        $defaultHierMap = $this->CI->model('Prodcat')->getFormattedTree($dataType, array($prodCatValue))->result;
        $prodCatHierarchy = $prodCatValue ? $defaultHierMap[$prodCatValue] : $defaultHierMap[0];
        if (!$prodCatHierarchy)
            return false;
        $userProducts = $this->getPermissionedForums($dataType, $prodCatHierarchy);
        $this->data['userProducts'] = array_slice($userProducts, 0, $this->data['attrs']['max_forum_count'], true);
        $result = $this->CI->model('SocialQuestion')->getQuestionCountByProductCategory($dataType, array_keys($this->data['userProducts']), $this->data['attrs']['show_columns'])->result;
        $this->data['question_count'] = in_array('question_count', $this->data['attrs']['show_columns']) ? $result[0] : null;
        $this->data['last_activity'] = in_array('last_activity', $this->data['attrs']['show_columns']) ? $result[1] : null;
        
        if(in_array('comment_count', $this->data['attrs']['show_columns'])) {
            $commentCount = $this->CI->model('SocialComment')->getCommentCountByProductCategory($dataType, array_keys($this->data['userProducts']))->result;
            $this->data['comment_count'] = $commentCount;
        }
    }

    /**
     * Returns the permissioned forums (products or categories) for the logged-in user.
     * @param bool $dataType Type of hierarchy: 'Product' or 'Category'
     * @param array $prodCatHierarchy  Map containing the default products/categories the user will have access to
     * @return array Updated map of products/categories the user will have access to
     */
    function getPermissionedForums($dataType, array $prodCatHierarchy) {
        $isProduct = ($dataType === "Product");
        $userProducts = $userPermissionedForums = array();

        if($this->data['attrs']['specify_forums']) {
            $prodcatList = array_flip(explode(',', $this->data['attrs']['specify_forums']));
        }
        else {
            $prodcatList = array_flip(array_map(function ($item) {
                return $item['id'];
            }, $prodCatHierarchy));
        }

        $permissionedHierarchy = $this->CI->model('Prodcat')->getPermissionedListSocialQuestionRead($isProduct)->result;

        if($permissionedHierarchy) {
            if(is_array($permissionedHierarchy)) {
                $permissionedProducts = array_reduce($permissionedHierarchy, function ($result, $item) {
                    $result[$item['ID']] = $item['Label'];
                    return $result;
                }, array());
                $userProducts = array_intersect_key($prodcatList, $permissionedProducts);
            }
            else {
                $userProducts = $prodcatList;
            }
            $result = $this->CI->model('Prodcat')->getProdCatByIDs($dataType, array_keys($userProducts), $this->data['attrs']['show_forum_description'])->result;
            $userPermissionedForums = $result ? array_replace($userProducts, $result) : $result;
        }
        return $userPermissionedForums;
    }
}
