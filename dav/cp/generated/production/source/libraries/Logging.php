<?php

namespace Custom\Libraries;

class Logging
{
    // Log levels
    public static $LOG_LEVEL_DEBUG_FULL = 1;
    public static $LOG_LEVEL_DEBUG_WO_FUNC_CALL = 2;
    public static $LOG_LEVEL_WARNING = 3;
    public static $LOG_LEVEL_ERROR = 4;
    public static $LOG_LEVEL_FATAL_ERROR = 5;

    function __construct()
    {
    }

    /**
    * Convenience method for logging function calls.
    * @param string $functionScope the scope of the function call
    * @param string $functionName the name of the function
    * @param array $paramArray an array of parameters, where the key is the parameter name and the value is the
    *                          parameter value
    */
    public function logFunctionCall($functionScope, $functionName, $paramArray=array(), $logLevel=null, $logThreshold=null){
        $logLevel = is_null($logLevel) ? self::$LOG_LEVEL_DEBUG_FULL : $logLevel;
        $logThreshold = is_null($logThreshold) ? self::$LOG_LEVEL_DEBUG_FULL : $logThreshold;
        if($logLevel >= $logThreshold){
            $paramString = count($paramArray) > 0 ? " with:\n" : '';
            foreach($paramArray as $paramName => $paramValue){
                $paramString .= $paramName . ' = ' . $this->getVarString($paramValue) . "\n";
            }
            logMessage("$functionScope::$functionName called$paramString");
        }
    }

    /**
    * Convenience method for logging function returns.
    * @param string $functionScope the scope of the function call
    * @param string $functionName the name of the function
    * @param string $returnValue the return value of the function
    * @param string $returnValueLabel an optional label for the return value
    */
    public function logFunctionReturn($functionScope, $functionName, $returnValue=null, $returnValueLabel=null, $logLevel=null, $logThreshold=null){
        $logLevel = is_null($logLevel) ? self::$LOG_LEVEL_DEBUG_FULL : $logLevel;
        $logThreshold = is_null($logThreshold) ? self::$LOG_LEVEL_DEBUG_FULL : $logThreshold;
        if($logLevel >= $logThreshold){
            if(!is_null($returnValue)){
                if(!empty($returnValueLabel)){
                    $returnValueString = " with $returnValueLabel = ";
                }else{
                    $returnValueString = ' with ';
                }
                $returnValueString .= "\n" . $this->getVarString($returnValue);
            }else{
                $returnValueString = '';
            }
            logMessage("Returning from $functionScope::$functionName$returnValueString");
        }
    }

    /**
    * Convenience method for logging variables.
    * @param string $label the label for the variable
    * @param any $variable the variable to log 
    * @param boolean $recurseExpandObjs true/false, whether to recursively expand subobjects of an object when logging it
    */
    public function logVar($label, $variable, $recurseExpandObjs=true, $logLevel=null, $logThreshold=null){
        $logLevel = is_null($logLevel) ? self::$LOG_LEVEL_DEBUG_FULL : $logLevel;
        $logThreshold = is_null($logThreshold) ? self::$LOG_LEVEL_DEBUG_FULL : $logThreshold;
        if($logLevel >= $logThreshold){
            if(is_object($variable) || is_array($variable)){
                logMessage("$label  =\n" . $this->getVarString($variable, $recurseExpandObjs));
            }else{
                logMessage("$label = " . $this->getVarString($variable, $recurseExpandObjs));
            }
        }
    }

    /**
     * Convenience method for logging an exception.
     * @param object $exception a PHP Exception object
     * @param string $scope the namespace/class of the function that encountered the error
     * @param string $function the name of the function that encountered the error
     */
    public function logErr($exception, $scope=null, $function=null, $logLevel=null, $logThreshold=null){
        $logLevel = is_null($logLevel) ? self::$LOG_LEVEL_FATAL_ERROR : $logLevel;
        $logThreshold = is_null($logThreshold) ? self::$LOG_LEVEL_FATAL_ERROR : $logThreshold;
        if($logLevel >= $logThreshold){
            $errorLine = "-----------------------------------------------------------------------------------\n";
            logMessage($errorLine . $this->exceptionToString($exception, $scope, $function) . $errorLine);
        }
    }

    /**
     * Convenience method for logging message with thresholding.
     * @param string $msg the message to log
     * @param string $scope the namespace/class of the function logMsg was called from
     * @param string $function the name of the function logMsg was called from
     * @param integer $logLevel the debug level of the message being logged
     * @param integer $logThreshold the treshold debug level over which to start logging messages 
     */
    public function logMsg($msg, $logLevel=null, $logThreshold=null, $toFile=false, $fileName="logfile"){
        $logLevel = is_null($logLevel) ? self::$LOG_LEVEL_DEBUG_FULL : $logLevel;
        $logThreshold = is_null($logThreshold) ? self::$LOG_LEVEL_DEBUG_FULL : $logThreshold;
        if($logLevel >= $logThreshold){
            if($toFile){
                $fp = fopen("/tmp/$fileName", 'a');
                fwrite($fp, $msg . "\n");
                fclose($fp);
            }else{
                logMessage($msg);
            }
        }
    }

    /**
     * Extracts the error information from an Exception object and returns it in a string.
     * @param object $exception a PHP Exception object
     * @param string $scope the namespace/class of the function that encountered the error
     * @param string $function the name of the function that encountered the error
     */
    public function exceptionToString($exception, $scope=null, $function=null){
        if(!empty($scope) && !empty($function)){
            return "ERROR encountered in {$exception->getFile()} at line {$exception->getLine()} in function $scope::$function:\n{$exception->getMessage()}\n";
        }else{
            return "ERROR encountered in {$exception->getFile()} at line {$exception->getLine()}:\n{$exception->getMessage()}\n";
        }
    }

    /**
     * Gets the string version of a variable. If var is a CPHP object, will read all its properties to make sure lazyloading
     * fields are populated. 
     * 
     * @param any $var the variable to get the string version of for logging purposes
     * @param boolean $recurseExpandObjs true/false, whether to recursively expand subobjects of an object when generating its string
     */
    private function getVarString($var, $recurseExpandObjs=true){
        if(is_null($var)){
            return 'null';
        }elseif(is_numeric($var) || is_string($var)){
            return $var . '';
        }elseif(is_array($var)){
            if($recurseExpandObjs){
                $this->_getValues($var);
            }
            return var_export($var, true);
        }elseif(is_object($var)){
            if($recurseExpandObjs){
                $this->_getValues($var);
            }
            return var_export($var, true);
        }else{
            return var_export($var, true);
        }
    }

    /**
     * Utility routine to combat lazy loading on CPHP objects. Recursively reads through all properties so that field data
     * will be loaded for logging purposes.
     *
     * @param object/array $parent the object/array to read through fields of 
     */
    private function _getValues($parent) {
        try {
            // $parent is a non-associative (numerically-indexed) array
            if (is_array($parent)) {

                foreach ($parent as $val) {
                    $this -> _getValues($val);
                }
            }

            // $parent is an associative array or an object
            elseif (is_object($parent)) {

                while (list($key, $val) = each($parent)) {

                    $tmp = $parent -> $key;

                    if ((is_object($parent -> $key)) || (is_array($parent -> $key))) {
                        $this -> _getValues($parent -> $key);
                    }
                }
            }
        } catch (exception $err) {
            // error but continue
        }catch(\RightNow\Connect\v1_2\ConnectAPIErrorFatal $err){
            
        }
    }
}
