<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Api,
    RightNow\Utils\Config;

class AnswerField extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(!is_null($this->data['attrs']['answer_key']) && empty($this->data['attrs']['label'])){
            $this->getLabel($this->data['attrs']['answer_key']);
        }
        if($this->data['attrs']['type'] === 'FILE'){
            $file = Text::getSubstringBefore($this->data['attrs']['value'], '#');
            $this->data['fileName'] = Text::getSubstringAfter($this->data['attrs']['value'], '#');
            $this->data['value'] = Url::getParameter('a_id') . '_' . $file;
        }
        if($this->data['attrs']['path'] !== '') {
            $className = ucwords(strtolower($this->data['attrs']['path']));
            $index = 0;
            foreach(array('_', '/') as $delimiter) {
                if(strpos($className, $delimiter))
                    $className = implode('_', array_map('ucfirst', explode($delimiter, $className)));
                $index++;
            }
            $this->classList->add('rn_AnswerField_' . $className);
        }

        $this->data['fieldData'] = array(
            'label' => $this->data['attrs']['label'],
            'value' => $this->data['attrs']['value'],
            'type' => $this->data['attrs']['type'],
            'answer_key' => $this->data['attrs']['answer_key'],
            'className' => $className,
            'index' => $index
        );
    }

    /**
    * Updates the widget attribute label with the config message value.
    * @param string $fieldName Field Name
    */
    private function getLabel($fieldName){
        switch($fieldName) {
            case 'docID':
                $this->data['attrs']['label'] = Config::getMessage(DOCUMENT_ID_LBL);
                break;
            case 'version':
                $this->data['attrs']['label'] = Config::getMessage(VERSION_LBL);
                break;
            case 'status':
                $this->data['attrs']['label'] = Config::getMessage(STATUS_LBL);
                break;
            case 'publishedDate':
                $this->data['attrs']['label'] = Config::getMessage(PUBLISHED_DATE_LBL);
        }
    }
}
