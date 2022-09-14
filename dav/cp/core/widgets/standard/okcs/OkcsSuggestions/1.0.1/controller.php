<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Libraries\Search;

class OkcsSuggestions extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this) || !($this->CI->session->canSetSessionCookies())) {
            return false;
        }
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $filters = array('docIdRegEx' => array('value' => $this->data['attrs']['document_id_reg_ex']),
                        'docIdNavigation' => array('value' => $this->data['attrs']['doc_id_navigation'],
                        'truncate' => array('value' => $this->data['attrs']['truncate_size'])));
        $this->data['js'] = array(
                                'filter' => $search->getFilters(),
                                'sources' => $search->getSources(),
                                'truncateSize' => $this->data['attrs']['truncate_size']);
    }
}