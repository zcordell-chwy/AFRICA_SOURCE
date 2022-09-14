<?php
namespace Custom\Widgets\reports;

use \RightNow\Connect\v1_2 as RNCPHP;

class LetterFaqsMultiline extends \RightNow\Widgets\Multiline {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        parent::getData();

        $this->processReportData($this->data['reportData']['data']);

        logMessage('$this->data[\'reportData\'] = ' . var_export($this->data['reportData'], true));
    }

    /**
     * Overridable methods from Multiline:
     */
    // function showColumn($value, array $header)
    // function getHeader(array $header)

    /**
     * Process report data before passing it off to view.
     * @param array $rows The array of row data from running the report.
     */
    function processReportData(&$rows){
        foreach($rows as $rowIndex => $row){
            $rawSolutionWithHTMLTags = null;
            foreach($row as $colIndex => $column){
                // Re-fetch Answer.Solution column with CPHP to get solution with HTML tags intact. getDataHTML, used by the standard 
                // Multiline widget, strips HTML tags from column values, and I cannot figure out a way to prevent that.
                if($colIndex == 0){ // Answer ID column
                    try{
                        $answerID = intval($column);
                        $answer = RNCPHP\Answer::fetch($answerID);
                        $rawSolutionWithHTMLTags = $answer->Solution;
                    }catch(\Exception $e){
                        // Swallow exception in case report gives bad data. This is not mission critical stuff.
                    }
                }

                if($colIndex == 2 && !is_null($rawSolutionWithHTMLTags)){ // Answer solution
                    $rows[$rowIndex][$colIndex] = $rawSolutionWithHTMLTags;
                }
            }
        }
    }
}