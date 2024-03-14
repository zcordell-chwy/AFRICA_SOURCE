<?php
namespace Custom\Widgets\input;
class SelectionInput extends \RightNow\Widgets\SelectionInput {
    function __construct($attrs) {
        parent::__construct($attrs);
    }
    function getData() {
        return parent::getData();
    }    /**
     * Overridable methods from SelectionInput:
     */    // function isValidField()    // public function outputSelected($key)    // public function outputChecked($currentIndex)    // protected function getMenuItems()    // protected function isValidSla($slaInstance)
}