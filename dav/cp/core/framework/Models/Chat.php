<?php /* Originating Release: February 2019 */

namespace RightNow\Models;

use RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Api,
    RightNow\Internal\Sql\Chat as Sql;

require_once CORE_FILES . 'compatibility/Internal/Sql/Chat.php';

/**
 * Functionality to initiate chat requests and send content between the enduser and the agent. Also contains functionality to
 * check on current chat availability.
 */
class Chat extends Base
{
    //Limit all requests to only take up to 5 seconds before bailing
    const REQUEST_TIMEOUT_LENGTH = 5;

    function __construct()
    {
        parent::__construct();
        $this->CI = get_instance();
    }

    /**
     * Generates a unique hash for the survey ID
     * @param int $surveyID Survey ID
     * @return string Unique hash of the survey ID
     */
    function getSurveyAuthValue($surveyID)
    {
        return sha1("{$surveyID}surVey-*-feeDback");
    }

    /**
     * Gets the status of the current chat queue.
     *
     * @param array $chatRouteValue Result from chatRoute method
     * @param int $availableType Type of avilability to check
     * @param bool $isCacheable Whether to cache the result or not
     * @return array Details about the chat queue.
     */
    public function checkChatQueue(array $chatRouteValue, $availableType = PROACTIVE_CHAT_AVAIL_TYPE_AGENTS, $isCacheable=false)
    {
        $result = array();
        if($chatRouteValue['result_code'] == ROUTE_SUCCESS)
        {
            if($chatRouteValue['rule_acts'])
            {
                foreach($chatRouteValue['rule_acts'] as $rule)
                {
                    switch($rule['action'])
                    {
                        case ACT_ASSIGN_LIVE_Q:
                            $qid = $rule['arg_int1'];
                            break;
                        case ACT_SEND_SURVEY:
                            $result['survey_data']['send_id'] = $rule['arg_int1'];
                            $result['survey_data']['send_delay'] = $rule['arg_int2'];
                            $result['survey_data']['send_auth'] = $this->getSurveyAuthValue($rule['arg_int1']);
                            break;
                        case ACT_POP_SURVEY_CHAT_COMP:
                            if($this->determineShowSurvey($rule['arg_int2'])) {
                                $result['survey_data']['comp_id'] = intval($rule['arg_int1']);
                                $result['survey_data']['comp_auth'] = $this->getSurveyAuthValue($rule['arg_int1']);
                            }
                            else {
                                $result['survey_data']['comp_id'] = 0;
                            }
                            break;
                        case ACT_POP_SURVEY_CHAT_TERM:
                            if($this->determineShowSurvey($rule['arg_int2'])) {
                                $result['survey_data']['term_id'] = intval($rule['arg_int1']);
                                $result['survey_data']['term_auth'] = $this->getSurveyAuthValue($rule['arg_int1']);
                            }
                            else {
                                $result['survey_data']['term_id'] = 0;
                            }
                            break;
                        case ACT_LIVE_REQUEUE:
                            $result['rules']['escalation'] = $rule['arg_int1'];
                            break;
                    }

                    if(isset($chatRouteValue['rule_state']))
                        $result['rules']['state'] = $chatRouteValue['rule_state'];
                }
            }

            if(!isset($qid))
                $qid = 1;
        }
        else if($chatRouteValue['result_code'] == ROUTE_OUTSIDE_OPERATING_HOURS)
        {
            $result['q_id'] = 0;
            $result['out_of_hours'] = true;
            return $this->getResponseObject($result);
        }
        else // If not ROUTE_SUCCESS, assume failure and do not attempt proactive request.
        {
            $result['q_id'] = 0;
            return $this->getResponseObject($result);
        }

        $urlQueryParameters = array(
            'action' => 'PROACTIVE_QUERY',
            'avail_type' => $availableType,
            'p_db_name' => \RightNow\Utils\Config::getConfig(DB_NAME),
            'intf_id' => Api::intf_id());

        if($qid)
            $urlQueryParameters['queue_id'] = $qid;

        $contents = $this->makeChatRequestHelper($this->getChatUrl($urlQueryParameters), null, $isCacheable, true);
        if($contents === false)
            return -1;

        //Find and parse the <queueId> int </queueID> element
        $start = strpos($contents, '<queueId>') + 9;
        $end = strpos($contents, '</queueId>', $start);
        $qid = substr($contents, $start, ($end - $start));

        //Find and parse that <expectedWaitSeconds> int </expectedWaitSeconds>
        $start = strpos($contents, '<expectedWaitSeconds>') + strlen('<expectedWaitSeconds>');
        $end = strpos($contents, '</expectedWaitSeconds>', $start);
        $expectedWaitSeconds = substr($contents, $start, ($end - $start));

        //Find and parse that <availableAgentSessions> int </availableAgentSessions>
        $start = strpos($contents, '<availableAgentSessions>') + strlen('<availableAgentSessions>');
        $end = strpos($contents, '</availableAgentSessions>', $start);
        $availableAgentSessions = substr($contents, $start, ($end - $start));

        $result['q_id'] = $qid;
        $result['stats']['availableSessionCount'] = $availableAgentSessions;
        $result['stats']['expectedWaitSeconds'] = $expectedWaitSeconds;
        return $this->getResponseObject($result);
    }

    /**
     * Pre-routes the chat based on available data in order to determine the queue the chat would enter.
     *
     * @param int $chatProduct Product specified for the chat
     * @param int $chatCategory Category specified for the chat
     * @param int $contactID Contact that is performing the chat
     * @param int $orgID Organization the contact is a member of
     * @param string $contactEmail Email of the contact
     * @param string $contactFirstName Contact's first name
     * @param string $contactLastName Contact's last name
     * @param array|null $customFieldInputArray Custom fields sent with the chat request
     *
     * @return array Result of routing the chat
     */
    public function chatRoute($chatProduct, $chatCategory, $contactID, $orgID, $contactEmail, $contactFirstName, $contactLastName, $customFieldInputArray=array())
    {
        $chatRouteArray = array();

        // If prod/cat data exists, set in chat_route_iv. This could probably eventually be generalized and moved to separate function for DRY purposes.
        if($chatProduct)
            $chatRouteArray['prod_id'] = intval($chatProduct);

        if($chatCategory)
            $chatRouteArray['cat_id'] = intval($chatCategory);

        // Add any contact information, if any exists
        $availableContact = array();

        if($contactID)
        {
            $availableContact['c_id'] = $contactID;
            $chatRouteArray['c_id'] = $contactID;
        }

        if($orgID)
        {
            $availableContact['org_id'] = $orgID;
            $chatRouteArray['org_id'] = $orgID;
        }

        if($contactEmail)
            $availableContact['email'] = array('addr' => $contactEmail);

        if($contactFirstName || $contactLastName)
        {
            $availableContact['name'] = array();

            if($contactFirstName)
                $availableContact['name']['first'] = $contactFirstName;

            if($contactLastName)
                $availableContact['name']['last'] = $contactLastName;
        }

        if(count($availableContact))
            $chatRouteArray['avail_con'] = $availableContact;

        //If the input array is empty this function should just return false thereby
        //leaving no risk that custom field data would get appended for old calls that
        //do not pass custom field data in (for backwards compatibility).
        $customFieldPairData = $this->getCustomFieldPairData($customFieldInputArray);
        if($customFieldPairData)
        {
            $chatRouteArray['custom_field'] = $customFieldPairData;
        }

        return $this->getResponseObject(Api::chat_route($chatRouteArray), 'is_array');
    }

    /**
     * Forwards a generic request to the chat server and returns the response
     * @return object The contents of the response from the chat server
     */
    public function makeChatRequest()
    {
        $urlQueryParameters = array();

        $action = $_POST['chatAction'];
        if(!isset($action))
            return false;

        $urlQueryParameters['action'] = $action;

        foreach($_POST as $key => $value)
        {
            if($key === 'chatAction')
                continue;
            else if($key === 'jsessionID')
            {
                $jsessionID = $value;
                continue;
            }
            else if($key === 'message')
            {
                $urlQueryParameters['msg'] = $value;
                continue;
            }
            $urlQueryParameters[$key] = $value;
        }

        return $this->makeChatRequestHelper($this->getChatUrl($urlQueryParameters), $jsessionID);
    }

    /**
     * Gets information about the current status of chat
     * @return array Array containing information about chat hours, holidays, etc
     */
    public function getChatHours()
    {
        $currentTime = localtime(time(), true);

        //Transition months from 0-11 to 1-12, years from e.g. 111(2011) to 2011, and weekday 0 (Sunday) to 7
        $currentTime['tm_mon'] += 1;
        $currentTime['tm_year'] += 1900;
        $currentTime['tm_wday'] = ($currentTime['tm_wday'] !== 0) ? ($currentTime['tm_wday']) : (7);

        //Set up the necessary time stamps and determine if today is a holiday
        $now = mktime($currentTime['tm_hour'], $currentTime['tm_min'], $currentTime['tm_sec'], $currentTime['tm_mon'], $currentTime['tm_mday'], $currentTime['tm_year']);
        $nowMinutes = $currentTime['tm_hour'] * 60 + $currentTime['tm_min'];
        $nowDay = $currentTime['tm_wday'];
        $nowtz = $hoursData['time_zone'] = strftime('%Z');
        $weekDays = array(null, MONDAY_LBL, TUESDAY_LBL, WEDNESDAY_LBL, THURSDAY_LBL, FRIDAY_LBL, SATURDAY_LBL, SUNDAY_LBL);

        //Get the work hour intervals and check if 'now' is within those intervals.
        $workHours = $this->getWorkHours($currentTime);
        $inWorkHours = false;
        for ($i = 0, $sz = count($workHours[$nowDay]); $i < $sz; $i++)
        {
            if ($workHours[$nowDay][$i]['start'] <= $nowMinutes && $nowMinutes <= $workHours[$nowDay][$i]['end'])
            {
                $inWorkHours = true;
                break;
            }
        }

        // Set up the chat hours display data
        // workHours contains the hours for each day indexed by day loop through to find matching consecutive days.
        // (this loop deliberately runs one past the populated end of $workHours)
        // k = index into display hours
        for ($k = 0, $i = 2, $rawHoursInt = 0, $firstDay = $lastDay = 1, $same = false, $toggled = false; $i <= 8; $i++, $same = false, $toggled = false)
        {
            $iSize = count($workHours[$i]);
            $iPlusSize = count($workHours[$i - 1]);
            if ($iSize === $iPlusSize)
            {
                if ($iSize === 0)
                {
                    $same = true;
                    $lastDay++;
                }
                else
                {
                    for($j = 0; $j < $iSize; $j++)
                    {
                        if (($workHours[$i][$j]['start'] == $workHours[$i - 1][$j]['start']) && ($workHours[$i][$j]['end'] == $workHours[$i - 1][$j]['end']))
                        {
                            if ($toggled == false) // if we've already detected a difference, don't set $same back to true
                                $same = true;
                        }
                        else
                        {
                            $same = false;
                            $toggled = true; // we've discovered a difference.  don't allow $same to be reset back to true
                        }
                    }
                    if($same)
                        $lastDay++;
                }
            }

            if ($i == 8)
            {
                $lastDay = 7;
                $same = false;
            }

            if (!$same)
            {
                //The raw hours array will contain a days_of_week variable that is an array list of the days of week
                //for this specific set of hours.
                $hoursData['workday_definitions'][$rawHoursInt]['days_of_week'][0] = $firstDay;

                $hours[$k][0] = \RightNow\Utils\Config::getMessage($weekDays[$firstDay]);
                if ($firstDay != $lastDay)
                {
                    //Generate the rest of the array list if the end is not equal to start.
                    for ($dayOfWeek = $firstDay; $dayOfWeek < $lastDay; $dayOfWeek++)
                    {
                        //Count up from start to end. We've already printed the start so we will
                        //tack on the next day and end the loop when $dayOfWeek is 1
                        //less than the end (since we are tacking on the NEXT day).
                        $hoursData['workday_definitions'][$rawHoursInt]['days_of_week'][] = $dayOfWeek + 1;
                    }

                    $hours[$k][0] .= ' - ' . \RightNow\Utils\Config::getMessage($weekDays[$lastDay]);
                }

                //Initialize has_hours to true. We will then set to false if this set of hours actually designates closure.
                $hoursData['workday_definitions'][$rawHoursInt]['has_hours'] = true;
                $count = count($workHours[$firstDay]);
                if ($count === 0)
                {
                    $hoursData['workday_definitions'][$rawHoursInt]['has_hours'] = false;
                    $hours[$k++][1] = \RightNow\Utils\Config::getMessage(BUSINESS_CLOSED_LBL);
                }
                else if($count === 1)
                {
                    $sstr = date('H:i', $workHours[$firstDay][0]['startDttm']);
                    $estr = date('H:i', $workHours[$firstDay][0]['endDttm']);

                    //For the raw hours output just send the start and end strings. Use 24:00 instead of
                    //00:00 for the end.
                    $hoursData['workday_definitions'][$rawHoursInt]['work_intervals'][0]['start'] = $sstr;
                    $hoursData['workday_definitions'][$rawHoursInt]['work_intervals'][0]['end'] = ($estr == '00:00' ? '24:00' : $estr);

                    if (($sstr == '00:00') && ($estr == '00:00'))
                    {
                        $hours[$k++][1] = \RightNow\Utils\Config::getMessage(ALL_DAY_LBL);
                    }
                    else
                    {
                        $rangeStart = Api::date_str(DATEFMT_CLOCK, $workHours[$firstDay][0]['startDttm']);
                        $tzstr = (Text::stringContains($rangeStart, $nowtz) ? "" : " $nowtz");
                        $hours[$k++][1] = sprintf("%s - %s%s", $rangeStart, Api::date_str(DATEFMT_CLOCK, $workHours[$firstDay][0]['endDttm']), $tzstr);
                    }
                }
                else
                {
                    $haveTZ = Text::stringContains(Api::date_str(DATEFMT_CLOCK, $workHours[$firstDay][0]['start']), $nowtz);
                    for($j = 0, $sz = count($workHours[$firstDay]); $j < $sz; $j++)
                    {
                        //For the raw hours output just send the start and end strings. Use 24:00 instead of
                        //00:00 for the end.
                        $hoursData['workday_definitions'][$rawHoursInt]['work_intervals'][$j]['start'] = date('H:i', $workHours[$firstDay][$j]['startDttm']);
                        $rawHoursEndString = date('H:i', $workHours[$firstDay][$j]['endDttm']);
                        $hoursData['workday_definitions'][$rawHoursInt]['work_intervals'][$j]['end'] = ($rawHoursEndString == '00:00' ? '24:00' : $rawHoursEndString);

                        $hours[$k][1] .= sprintf("%s%s - %s", $j > 0 ? ", " : "",
                            Api::date_str(DATEFMT_CLOCK, $workHours[$firstDay][$j]['startDttm']),
                            Api::date_str(DATEFMT_CLOCK, $workHours[$firstDay][$j]['endDttm']));
                    }
                    if (!$haveTZ)
                        $hours[$k][1] .= sprintf(" %s", $nowtz);
                    $k++;
                }

                $rawHoursInt++;
                $firstDay = $lastDay = $i;
            }
        }

        //Gather up all the data and output it.
        $chatInfo = array();
        $chatInfo['hours_data'] = $hoursData;
        $chatInfo['hours'] = $hours;

        $date = Api::date_str(DATEFMT_LONG, $now);
        $time = Api::date_str(DATEFMT_CLOCK, $now);
        $haveTZ = (Text::stringContains($date, $nowtz) || Text::stringContains($time, $nowtz));
        $chatInfo['current_time'] = \RightNow\Utils\Config::getMessage(IT_IS_CURRENTLY_MSG) . " " . $date . " " . $time . (($haveTZ) ? "" : " $nowtz");
        $chatInfo['holiday'] = Sql::isChatHoliday($currentTime['tm_mon'], $currentTime['tm_mday'], $currentTime['tm_year']);
        $chatInfo['inWorkHours'] = $inWorkHours;
        return $this->getResponseObject($chatInfo);
    }

    /**
     * Returns a new blank custom field array.
     *
     * @return array An instance of a CustomField object
     */
    public function getBlankCustomFields()
    {
        $customFields = array();
        //Map old data type defines to new data type defines
        $mapping = array(
            CDT_MENU => EUF_DT_SELECT,
            CDT_BOOL  => EUF_DT_RADIO,
            CDT_INT => EUF_DT_INT,
            CDT_DATETIME => EUF_DT_DATETIME,
            CDT_VARCHAR => EUF_DT_VARCHAR,
            CDT_MEMO => EUF_DT_MEMO,
            CDT_DATE => EUF_DT_DATE,
            CDT_OPT_IN => EUF_DT_RADIO,
        );

        foreach (Framework::getCustomFieldList(TBL_INCIDENTS, VIS_LIVE_CHAT) as $value) {
            $customField = array(
                'attr' => $value['attr'],
                'col_name' => $value['col_name'],
                'custom_field_id' => $value['cf_id'],
                'default_value' => $value['dflt_val'],
                'field_size' => $value['field_size'],
                'group_name' => $value['grp_name'],
                'lang_hint' => $value['lang_hint'],
                'lang_name' => $value['lang_name'],
                'mask' => $value['mask'],
                'max_val' => $value['max_val'],
                'min_val' => $value['min_val'],
                'required' => $value['required'],
                'visibility' => $value['visibility'],
                'value' => $value['dflt_val'],
                'data_type' => $mapping[$value['data_type']]
            );

            if ($customField['data_type'] === EUF_DT_SELECT) {
                //add a menu_items property to the CF object
                $customField['menu_items'] = $this->getMenuItems($value['cf_id']);
            }
            else if ($customField['data_type'] === EUF_DT_RADIO && intval($customField['value']) === -1) {
                //Reset -1 value to null for no item selected
                $customField['value'] = null;
            }

            //array key is the custom field code (cf_id)
            $customFields[$value['cf_id']] = $customField;
        }

        return $this->getResponseObject($customFields, 'is_array');
    }

    /**
      * Get the formatted work hours intervals from the DB.
      * @param array $currentTime Time at the start of the getChatHours
      * @return array Formatted work hours
      */
    protected function getWorkHours(array $currentTime)
    {
        $startOfTodayTimeStamp = mktime(0, 0, 0, $currentTime['tm_mon'], $currentTime['tm_mday'], $currentTime['tm_year']);
        $startOfTomorrowTimeStamp = mktime(0, 0, 0, $currentTime['tm_mon'], $currentTime['tm_mday'] + 1, $currentTime['tm_year']);

        // Determine the chat/phone availability based on response_reqs and rr-intervals.
        // However, if days do not exist in the database, add work_hrs to simulate work_hours.
        for ($i = 0; $i < 7; $i++)
            $workHours[$i] = array();

        foreach(Sql::getChatHoursAvailability() as $hoursInterval)
        {
            // The offset is used because in the database the intervals are stored
            // as timestamps that use the date 1/1/2000, because only the time is relevant
            // However, because the dates get skewed in the db depending on the timezone of the admin interface,
            // sometimes we end up with rr_intervals that are not normalized, i.e they define a range which is effectively
            // something like "Wednesday, -3 am to 4pm". This manifests itself in the datetime's being 12-31-1999 or 1-2-2000.
            //
            // In order to properly define the intervals, we need to normalize these entries by either splitting them into
            // two entries if an interval crosses from one day to the next, or by fixing the day of the week for the interval
            // see QA incidents 090209-000020, and QA 090312-000054

            $startDttm = $hoursInterval['startTime'];
            $endDttm = $hoursInterval['endTime'];
            $weekday = $hoursInterval['weekday'];
            $start = localtime($startDttm, true);
            $end = localtime($endDttm, true);
            $interval = array();

            // normalize intervals
            if ($end['tm_hour'] == 0 && $end['tm_min'] == 0)
            {
                // Handle midnight as hour 24 of the previous day
                $end['tm_hour'] = 24;
                $end['tm_mday'] -= 1;
            }

            // if the start day is the 31st, than the interval extends into the day before the rr_intervals.day field
            if ($start['tm_mday'] == 31)
            {
                if ($end['tm_mday'] == 1)
                {
                    // interval spans two days, so we split it into two
                    // one from [start - end of yesterday]
                    // one from [start of today - end of interval]
                    // the second is created later on
                    $tmpday = ($weekday == 0) ? 6 : $weekday - 1;
                    $interval['start'] = $start['tm_hour'] * 60 + $start['tm_min'];
                    $interval['startDttm'] = $startDttm;
                    $interval['end'] = 1440;

                    $tmpEnd = $end;
                    $tmpEnd['tm_hour'] = $tmpEnd['tm_min'] = 0;
                    $interval['endDttm'] = mktime($tmpEnd['tm_hour'], $tmpEnd['tm_min'], $tmpEnd['tm_sec'], $tmpEnd['tm_mon'] + 1, $tmpEnd['tm_mday'], $tmpEnd['tm_year'] + 1900);
                    $workHours[$tmpday][] = $interval;

                    // fix start time for second half of interval
                    $start['tm_mday'] += 1;
                    $start['tm_hour'] = $start['tm_min'] = 0;
                    $startDttm = mktime($start['tm_hour'], $start['tm_min'], $start['tm_sec'], $start['tm_mon'] + 1, $start['tm_mday'], $start['tm_year'] + 1900);
                }
                else
                {
                    // the entire interval is actually for the day before what is in the db
                    $weekday = ($weekday == 0) ? 6 : $weekday - 1;
                }
            }
            // if the start date is the second, than the interval is actually 1 day after the rr_intervals.day field
            else if($start['tm_mday'] == 2)
            {
                $weekday = ($weekday++) % 7;
            }

            // if the start day is the 1st, and the end the second, then the interval spans two days
            // we create two intervals from as we do for two-day intervals that start on the 31st
            else if($end['tm_mday'] == 2)
            {
                $interval['start'] = $start['tm_hour'] * 60 + $start['tm_min'];
                $interval['startDttm'] = $startDttm;
                $interval['end'] = 1440;

                $tmpEnd = $end;
                $tmpEnd['tm_hour'] = $tmpEnd['tm_min'] = 0;
                $interval['endDttm'] = mktime($tmpEnd['tm_hour'], $tmpEnd['tm_min'], $tmpEnd['tm_sec'], $tmpEnd['tm_mon'] + 1, $tmpEnd['tm_mday'], $tmpEnd['tm_year'] + 1900);

                $workHours[$weekday][] = $interval;

                // fix start time for second half of interval
                $start['tm_mday'] += 1;
                $start['tm_hour'] = $start['tm_min'] = 0;
                $startDttm = mktime($start['tm_hour'], $start['tm_min'], $start['tm_sec'], $start['tm_mon'] + 1, $start['tm_mday'], $start['tm_year'] + 1900);
                $weekday = ($weekday++) % 7;
            }

            // "normal" interval, or the second half of a two part interval
            $interval['start'] = $start['tm_hour'] * 60 + $start['tm_min'];
            $interval['startDttm'] = $startDttm;
            $interval['end'] = $end['tm_hour'] * 60 + $end['tm_min'];
            $interval['endDttm'] = $endDttm;
            $workHours[$weekday][] = $interval;
        }

        // copy the first record to the last (want sunday at the end)
        $workHours[7] = $workHours[0];
        return $workHours;
    }

    /**
     * Randomly determines if we should show a survey based on the provided probability.
     *
     * @param int $probability The 0-99 percent chance to show survey
     * @return bool Whether to show survey or not
     */
    protected function determineShowSurvey($probability)
    {
        return($probability > rand(0, 99));
    }

    /**
     * Uses functionality built into the incident object and custom field model to generate custom
     * field pair data. The input array is keyed on custom field id, and the resulting pair data array
     * will contain only chat visible custom fields that have values passed in through the input array.
     * Method returns false if the input array is null or empty.
     *
     * @param array|null $customFieldInputArray Key/Value array of custom field values being passed in
     * @return array Custom field pair data array or false if no custom field data exists.
     * @internal
     */
    protected function getCustomFieldPairData($customFieldInputArray)
    {
        $pairData = false;

        //We don't want the custom field pair data created at all if there were none passed in (maintains compatibility)
        if($customFieldInputArray !== null && count($customFieldInputArray) > 0)
        {
            $customFields = $this->getBlankCustomFields()->result;
            foreach($customFields as $key => $value)
            {
                if(array_key_exists($key, $customFieldInputArray))
                {
                    $customFields[$key]['value'] = $customFieldInputArray[$key];
                }
                else
                {
                    //Because this functionality didn't exist before AND custom fields can have defaults that will
                    //be set by the custom field model, to be on the paranoid safe side we need to prune custom
                    //fields that have not been passed in.
                    unset($customFields[$key]);
                }
            }
            $pairData = Sql::customFieldToPairData($customFields);
        }

        return $pairData;
    }

    /**
     * Selects all menu item options for SELECT custom field types
     *
     * @param int $customFieldID ID of the custom field
     * @return array Collection of menu items
     */
    protected function getMenuItems($customFieldID)
    {
        $cacheKey = 'allCustomFieldMenuItems';
        if (($allMenuItems = Framework::checkCache($cacheKey)) === null) {
            $allMenuItems = Sql::getAllMenuItems();
            Framework::setCache($cacheKey, $allMenuItems);
        }
        return $allMenuItems[$customFieldID];
    }

    /**
     * Replaces previous function called getChatServerHostAndPath. This will
     * build a full url to the chat server and include query parameters that are
     * provided.
     *
     * @param array|null $queryParameters Key/Value array of parameters to include
     * @return string The full url
     */
    private function getChatUrl($queryParameters = array())
    {
        // Create a pool query parameter if pool id has a value
        $poolID = \RightNow\Utils\Config::getConfig(CHAT_CLUSTER_POOL_ID);
        if($poolID)
            $queryParameters['pool'] = $poolID;

        $dbName = \RightNow\Utils\Config::getConfig(DB_NAME);

        // First get the internal host if it exists
        $chatServerHost = \RightNow\Utils\Config::getConfig(SRV_CHAT_INT_HOST);
        // Now create the url base
        if(!$chatServerHost)
        {
            // If SRV_CHAT_INT_HOST was empty then use SRV_CHAT_HOST. That implies the request
            // will go back into the wild in which case we need to ensure we use SSL if the
            // original request was in SSL.
            $chatUrlBase = (\RightNow\Utils\Url::isRequestHttps() ? ('https://') : ('http://')) . \RightNow\Utils\Config::getConfig(SRV_CHAT_HOST);
        }
        else
        {
            $chatUrlBase = "http://$chatServerHost";
        }

        // Now build the url (minus the query string)
        $chatUrl = "$chatUrlBase/Chat/chat/$dbName";

        // And finally add the query parameters
        if(count($queryParameters) > 0)
        {
            $chatUrl = "$chatUrl?" . http_build_query($queryParameters);
        }
        return $chatUrl;
    }

    /**
     * Utility function to build up a web request to the chat server
     * @param string $url The URL to make the request to
     * @param string $jsessionID The session ID that identifies a given session
     * @param bool $isCacheable Indicates if the request can be satisfied with cached data and if the response is suitable for caching.
     * @param bool $allowTimeout If true, the request will timeout after 5 seconds.
     * @return string The response from the equest
     */
    private function makeChatRequestHelper($url, $jsessionID = null, $isCacheable = false, $allowTimeout = false)
    {
        $requester = function() use($url, $jsessionID, $allowTimeout) {
            //To avoid 'sleeping processes' occupying a db connection, close the connection prior to making any request to the chat service 
            //Forcing commit before disconnecting
            $hooks = &load_class('Hooks');
            $hooks->_run_hook(array(
                                'class' => 'SqlMailCommit',
                                'function' => 'commit',
                                'filename' => 'SqlMailCommit.php',
                                'filepath' => 'hooks'
                            ));
            //closing the open connection.
            Api::sql_disconnect();
            $CI = get_instance();
            $options = array("User-Agent: {$CI->input->user_agent()}",
                             "X-Forwarded-For: {$CI->input->ip_address()}",
                             $jsessionID !== null ? "Cookie: JSESSIONID=$jsessionID" : "");

            //Use curl if request is over SSL
            $useSSL = Text::beginsWithCaseInsensitive($url, 'https');
            if($useSSL && !@Api::load_curl())
            {
                //failed to load curl. we will log this and proceed request using http
                $useSSL = false;
            }

            if($useSSL)
            {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $options);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                if($allowTimeout)
                    curl_setopt($ch, CURLOPT_TIMEOUT, 4);

                // Issue request to server, handling any potential PHP error by falling back to setting the response to false.
                // Note (here and below in file_get_contents) that we use "or" instead of "||". Apparently this is necessary
                // to properly handle PHP errors. "||" results in the assignment of 1 to $response regardless of success.
                ($response = @curl_exec($ch)) || ($response = false);
            }
            else
            {
                $contextOptions = array('http' => array('header' => implode("\r\n", $options)));

                // For reasons that I do not understand, real-world use of the timeout value is actually double
                // of what's specified in this context option. So this is effectively 4 seconds and not really 2.
                if($allowTimeout)
                    $contextOptions['http']['timeout'] = 2;

                $context = stream_context_get_default($contextOptions);
                ($response = @file_get_contents($url, false, $context)) || ($response = false);
            }
            //Always open database connection since we can only be sure we can keep the database closed with syndicated widget connections which are only in CPv2
            Api::sql_open_db();

            return $response;
        };

        if($isCacheable)
        {
            $cache = new \RightNow\Libraries\Cache\PersistentReadThroughCache(5, $requester);
            try {
                return $cache->get($url);
            }
            catch (\Exception $e) {
                //Cache check fails, no need to do anything special
            }
        }
        return $requester();
    }

    /**
     * Returns boolean for whether or not the user's browser supports chat
     * @return bool Whether the browser supports chat
     */
    public function isBrowserSupported()
    {
        return (get_instance()->agent->browser() !== 'Opera');
    }

    /**
     * Validates contact is still logged in
     * @return array whether the contact is logged in or not
     */
    public function chatValidate()
    {
        $result = array();
        if(Framework::isLoggedIn())
        {
            $result["status"] = true;
        }
        else
        {
            $result["status"] = false;
            $result["error"] = \RightNow\Utils\Config::getMessage(ERR_REQ_SESSION_EXP_PLS_LOG_TRY_MSG);
        }
        return $result;
    }
}
