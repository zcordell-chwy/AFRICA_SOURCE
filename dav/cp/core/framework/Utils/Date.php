<?php

namespace RightNow\Utils;

/**
 * Date related utility functions  
 */

final class Date {
    
    private static $dateFormat = 'Y-m-d\TH:i:s\Z';

    /**
     * Get current date and time
     * @return string
     */
    public static function getCurrentDateTime() {
        return gmdate(self::$dateFormat);
    }

    /**
     * Adds to "date" "unit" "interval"'s of time. If "round" is 1 or true, then the result is truncated to the nearest "interval"
     * Interval may be 'second', 'minute', 'hour', 'day', 'week', 'month' or 'year'.Unit can be a negative value via date_sub().
     * same as ROQL: date_add
     * @param string $dateTime String Date time
     * @param int $units Int Unit
     * @param string $interval String Interval
     * @param int $round Int Round(1 or 0)
     * @return string
     */
    public static function add($dateTime, $units, $interval, $round) {
        if(!self::validate($dateTime)) {
                return null;
        }
        $intervalCurrent = $interval;
        if($interval === 'week') {
            $units = $units * 7;
            $intervalCurrent = 'day';
        }
        $dateTimeObject = new \DateTime($dateTime);
        $dateIntervalSpec = ($intervalCurrent === 'day' || $intervalCurrent === 'month' || $intervalCurrent === 'year') ? 'P' : 'PT';

        $dateIntervalObject = new \DateInterval($dateIntervalSpec . abs($units) . strtoupper(substr($intervalCurrent, 0, 1)));
        if($units >= 0) {
            $modifiedDateTime = $dateTimeObject->add($dateIntervalObject)->format(self::$dateFormat);
        }
        else {
            $modifiedDateTime = $dateTimeObject->sub($dateIntervalObject)->format(self::$dateFormat);
        }
        if($round === 1) {
                $modifiedDateTime = self::trunc($modifiedDateTime, $interval);
        }
        return $modifiedDateTime;
    }

    /**
     * Truncate the date to the nearest interval. Interval may be ‘second’, ‘minute’, ‘hour’, ‘day’, ‘week’, ‘month’, or ‘year’.
     * same as ROQL: date_trunc 
     * @param string $dateTime String Date time
     * @param string $interval String Interval
     * @return string
     */
    public static function trunc($dateTime, $interval) {
        if(!self::validate($dateTime)) {
            return null;
        }
        $newDate = new \DateTime($dateTime);
        $newInterval = $interval;
        $newDateTime = $dateTime;
        switch($interval){
            case 'year':
                $newDate->modify('first day of january ' . $newDate->format('Y'));
                $newInterval = 'day';
                $newDateTime = $newDate->format(self::$dateFormat);
                break;
            case 'month':
                $newDate->modify('first day of this month');
                $newInterval = 'day';
                $newDateTime = $newDate->format(self::$dateFormat);
                break;
            case 'week':
                $newDate->modify(('Sunday' == $newDate->format('l')) ? 'today' : 'last sunday');
                $newInterval = 'day';
                $newDateTime = $newDate->format(self::$dateFormat);
                break;
        }
        
        $dateArray = date_parse($newDateTime);
        $flag = 0;
        foreach($dateArray as $key => $val) {
            if($flag === 1){
                $dateArray[$key] = 0;
            }
            if($key === $newInterval) {
                $flag = 1;
            }
            if($key === 'second'){
                break;
            }
        }

        $dateTrunc = date(self::$dateFormat, mktime($dateArray['hour'], $dateArray['minute'], $dateArray['second'], $dateArray['month'], $dateArray['day'], $dateArray['year']));
        return $dateTrunc;
    }

    /**
     * Returns the number of seconds when subtracting dateTimeA from dateTimeB
     * same as ROQl : date_diff
     * @param string $dateTimeA String Start date
     * @param string $dateTimeB String End date
     * @return int
     */
    public static function diff($dateTimeA, $dateTimeB) {
        $timeA = strtotime($dateTimeA);
        $timeB = strtotime($dateTimeB);

        $timeDiff = $timeB - $timeA;

        return $timeDiff;
    }

    /**
     * Validates the date
     * @param string $dateTime String Date time
     * @return boolean
     */
    private static function validate($dateTime) {
        if (preg_match("/([0-9]{4})(-|\/)([0-9]{2})(-|\/)([0-9]{2})(\s|\w+)([0-9]{2})\:([0-9]{2})\:([0-9]{2})(\w)?/", $dateTime, $result)){
            $gDate = sprintf("%s-%s-%s %s:%s:%s", $result[1], $result[3], $result[5], $result[7], $result[8], $result[9]);
            $d = \DateTime::createFromFormat('Y-m-d H:i:s', $gDate);
            return $d->format('Y-m-d H:i:s') == $gDate;
        }
        return false;
    }

    /**
     * Returns a formatted string for the given timestamp.
     * @param string|number $timestamp Timestamp
     * @param string|number $dateFormat Date format
     * @return string Timestamp
     */
    public static function formatTimestamp ($timestamp, $dateFormat) {
        $date = date('m/d/Y', strtotime($timestamp));

        if($date == date('m/d/Y')) {
            $date = date('h:i A', strtotime($timestamp));
        }
        else if($date == date('m/d/Y', time() - (24 * 60 * 60))) {
            $date = Config::getMessage(YESTERDAY_LBL);
        }
        else {
            $date = date($dateFormat, strtotime($timestamp));
        }
        return $date;
    }
    
    /**
     * Returns a formatted string for the given textual representation of date
     * @param string $texualFormat The textual representation of date format
     * @return string Format of the date.
     */
    public static function getDateFormat ($texualFormat) {
        switch($texualFormat){
            case 'full_textual':
                $dateFormat = 'F jS, Y';
                break;
            case 'short_textual':
                $dateFormat = 'M d, Y';
                break;
            case 'numeric':
            default:
                $dateFormat = 'm/d/Y';
        }
        return $dateFormat;
    }
}