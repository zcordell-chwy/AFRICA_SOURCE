<?php
namespace RightNow\Libraries;
use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Connect\v1_3 as Connect;

/**
 * Formats data for display within CP.
 */
class Formatter
{
    private static $keywordPhrase = false;
    private static $escapedContentTypes = array(
        'plain'     => 'text/plain',
        'markdown'  => 'text/x-markdown',
    );

    /**
     * Returns array of records with provided fields formatted. Generally used to process tabular ROQL queries.
     * @param array $data Array of records to process
     * @param object $objectMetaData MetaData for object which each row represents
     * @param array $fieldsToFormat Simple array with the names of the fields that should be formatted
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return array Array of records with fields formatted
     */
    public static function formatMultipleFields(array $data, $objectMetaData, array $fieldsToFormat, $highlight) {
        if ($objectMetaData === null) return $data;

        foreach ($data as $index => $record) {
            foreach ($record as $fieldName => $fieldValue) {
                if (in_array($fieldName, $fieldsToFormat)) {
                    // if the field is a datetime, make sure we have the correct format for processing (timestamp)
                    if (strtolower($objectMetaData->{$fieldName}->COM_type) === 'datetime' && $fieldValue !== (string)intval($fieldValue)) {
                        $fieldValue = strtotime($fieldValue);
                    }

                    // if the field is body, handle it a little differently
                    if (strtolower($fieldName) === 'body' && $record['BodyContentType']) {
                        $data[$index][$fieldName] = self::formatBodyEntry($fieldValue, intval($record['BodyContentType']), $objectMetaData, $highlight);
                    }
                    else {
                        $data[$index][$fieldName] = self::formatField($fieldValue, $objectMetaData->{$fieldName}, $highlight);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Returns formatted content for the specified field. Call #formatThreadEntry in order to retrieve formatted HTML content for Incident threads.
     * @param int|bool|string|object $fieldValue Field value
     * @param object $fieldMetaData Metadata for the field
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return string|null Formatted field value
     */
    public static function formatField($fieldValue, $fieldMetaData, $highlight){
        if ($fieldValue === null && $fieldMetaData->is_nillable) return;

        if ($specialType = self::normalizeSpecialTypes($fieldValue)) {
            list($fieldValue, $fieldMetaData) = $specialType;
        }

        $fieldValue = self::formatFieldForDataType($fieldValue, $fieldMetaData);

        return self::highlight($fieldValue, $highlight);
    }

    /**
     * Returns correctly formatted text content depending on the incident's content type.
     * @param object $thread Connect object with some text entry to format. Should be Comment or Thread.
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return string Formatted incident text
     */
    public static function formatThreadEntry($thread, $highlight) {
        $threadText = $thread->Body ?: $thread->Text;
        $contentTypeName = self::getObjectContentType($thread);

        return self::formatTextEntry($threadText, $contentTypeName, $highlight);
    }

    /**
     * Returns correctly formatted text depending on the content type. Generally used with tabular query data.
     * @param string $bodyText Text of the body
     * @param int $contentType ID of the content type for the body
     * @param object $objectMetaData MetaData of object to which the body belongs
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return string Formatted string
     */
    public static function formatBodyEntry($bodyText, $contentType, $objectMetaData, $highlight) {
        $contentTypeName = '';
        $types = Connect\ConnectAPI::getNamedValues($objectMetaData->type_name, 'BodyContentType');
        foreach ($types as $type) {
            if ($contentType === $type->ID) {
                $contentTypeName = $type->LookupName;
            }
        }
        return self::formatTextEntry($bodyText, $contentTypeName, $highlight);
    }

    /**
     * Generic function that is used to format either thread or body entries. Returns formatted text based on the content type.
     * @param string $text Text to be formatted
     * @param string $contentTypeName Name of content type
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return string Formatted string
     */
    public static function formatTextEntry($text, $contentTypeName, $highlight) {
        if ($contentTypeName === self::$escapedContentTypes['markdown']) {
            return self::formatMarkdownEntry($text, $highlight);
        }

        if ($contentTypeName === 'text/html') {
            return self::formatHtmlEntry($text, $highlight);
        }

        $fieldValue = Api::print_text2str($text, self::getFormattingOptions(true));
        return self::highlight($fieldValue, $highlight);
    }

    /**
     * Converts the given markdown into HTML.
     * @param string $fieldValue Markdown
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return string HTML
     */
    public static function formatMarkdownEntry($fieldValue, $highlight = false) {
        require_once CPCORE . 'Libraries/ThirdParty/MarkdownFilter.php';

        // be safe and strip out HTML tags, since they don't make sense in markdown,
        // in case something incorrectly allowed them in
        $fieldValue = Api::print_text2str($fieldValue, OPT_STRIP_HTML_TAGS);

        $html = \RightNow\Libraries\ThirdParty\MarkdownFilter::toHTML($fieldValue);

        return self::highlight($html, $highlight);
    }

    /**
     * Returns $html unchanged, except to highlight content if specified.
     * @param string $html String containing HTML
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return string HTML
     */
    public static function formatHtmlEntry($html, $highlight = false) {
        return self::highlight($html, $highlight);
    }

    /**
     * Produces a date time string.
     * @param string|number $value Date timestamp
     * @return string Formatted string
     */
    public static function formatDateTime($value) {
        return Api::date_str(DATEFMT_DTTM, $value);
    }

    /**
     * Produces a date string.
     * @param string|number $value Date timestamp
     * @return string Formatted string (without time info)
     */
    public static function formatDate($value) {
        return Api::date_str(DATEFMT_SHORT, $value);
    }

    /**
     * Produces a Yes / No label.
     * @param number|string|null $value Booleanish
     * @return string Yes / No label
     */
    public static function formatBoolean($value) {
        // 0, false, null, and any string end up as NO.
        return ($value == 0)
            ? \RightNow\Utils\Config::getMessage(NO_LBL)
            : \RightNow\Utils\Config::getMessage(YES_LBL);
    }

    /**
     * Highlights the passed in content by adding an <em> tag around any words that match the current search term.
     * @param string $fieldValue Value of field. If value is not a string, no modification will occur.
     * @param string|bool $highlight Set to true to highlight current `kw` URL parameter
     *                                  Set to false to skip highlighting
     *                                  Set to a string to force highlighting on the given string instead of the current `kw` URL parameter
     * @return string Value with keywords highlighted
     */
    public static function highlight($fieldValue, $highlight = true){
        if(is_bool($highlight)) {
            if($highlight) {
                $highlight = self::getKeyword();
            }
            else {
                return $fieldValue;
            }
        }

        if(is_string($fieldValue) && is_string($highlight) && $highlight !== ''){
            return Text::emphasizeText($fieldValue, array('query' => $highlight));
        }
        return $fieldValue;
    }

    /**
     * Creates and returns a mask string based upon the field's value.
     * @param string $value The field's initial value
     * @param string $mask The Mask value
     * @return string The field's initial mask value
     */
    public static function applyMask($value, $mask) {
        if (strlen($value) === 0)
            return $value;

        $j = 0;
        $result = '';
        for ($i = 0; $i < strlen($mask); $i += 2) {
            while ($mask[$i] === 'F') {
                $result .= $mask[$i + 1];
                $i += 2;
            }
            $result .= $value[$j++];
        }
        return $result;
    }

    /**
     * Determines whether the given thread-like
     * object's text should be escaped.
     * @param  object $thread Thread or Comment
     * @return bool           If entry should be escaped
     */
    private static function shouldEscapeThreadEntry($thread) {
        static $mimesToEscape;
        ($mimesToEscape || ($mimesToEscape = array_values(self::$escapedContentTypes)));

        return !($contentType = self::getObjectContentType($thread)) ||
                in_array($contentType, $mimesToEscape);
    }

    /**
     * If a content type exists on the object, returns it.
     * @param  object $thread Thread or Comment
     * @return string|bool Content type or false if there is none
     */
    private static function getObjectContentType($thread) {
        static $contentFields = array('ContentType', 'BodyContentType');

        if (!$thread) return false;

        foreach ($contentFields as $field) {
            if (property_exists($thread, $field)) return $thread->{$field}->LookupName;
        }

        return false;
    }

    /**
     * Returns the ORed together flag value for the specified object type.
     * @param bool $escapeHtml Whether or not to escape HTML in the content
     * @return int ORed together flag value for how the object type should be formatted
     */
    private static function getFormattingOptions($escapeHtml) {
        static $commonFlags, $htmlFlags;
        if (!isset($commonFlags)) {
            $commonFlags = OPT_VAR_EXPAND | OPT_HTTP_EXPAND | OPT_SPACE2NBSP | OPT_ESCAPE_SCRIPT | OPT_SUPPORT_AS_HTML | OPT_REF_TO_URL_PREVIEW;
            $htmlFlags = $commonFlags | OPT_ESCAPE_HTML | OPT_NL_EXPAND;
        }
        if($escapeHtml){
            return $htmlFlags;
        }
        return $commonFlags;
    }

    /**
     * Returns the keyword for the current page.
     * @return string|null Value of keyword or null if one wasn't found
     */
    private static function getKeyword(){
        if(self::$keywordPhrase === false){
            self::$keywordPhrase = \RightNow\Utils\Url::getParameter('kw');
        }
        return self::$keywordPhrase;
    }

    /**
     * Handles named id and country data types.
     * @param object|number|string|bool $field Field value
     * @return array|null If $field is a named id or country object
     *                       then an array of
     *                       [ LookupName (label), metadata of LookupName ]
     *                       is returned
     */
    private static function normalizeSpecialTypes($field) {
        $isSLA = ConnectUtil::isSlaInstanceType($field);
        $isNamedID = !$isSLA && ConnectUtil::isNamedIDType($field);
        $isCountry = !$isSLA && !$isNamedID && ConnectUtil::isCountryType($field);
        $isAsset = !$isSLA && !$isNamedID && ConnectUtil::isAssetType($field);

        if ($isSLA || $isNamedID || $isCountry || $isAsset) {
            if ($isSLA) {
                // For SLA fields, the actual field info is in the `NameOfSLA` subfield.
                $field = $field->NameOfSLA;
                $fieldValue = $field->LookupName;
            }
            else if ($isCountry) {
                $fieldValue = $field->Name;
            }
            else {
                $fieldValue = $field->LookupName;
            }

            return array(
                $fieldValue,
                $field::getMetadata()->LookupName,
            );
        }
    }

    /**
     * Handles formatting of the value according to its data type
     * @param string|number|bool|null $value Field value
     * @param object $metaData Meta data for field
     * @return string Formatted value or $value untouched if the field
     *                          doesn't have any special formatting
     *                          requirements
     */
    private static function formatFieldForDataType($value, $metaData) {
        if ($metaData->is_menu) return $value->Name;

        switch (strtolower($metaData->COM_type)) {
            case 'datetime':
                return self::formatDateTime($value);
            case 'date':
                return self::formatDate($value);
            case 'boolean':
                return self::formatBoolean($value);
            case 'string':
                return self::formatString($value, $metaData);
            default:
                // Field types that don't have special formatting: integer, long, decimal
                return $value;
        }
    }

    /**
     * Produces a formatted string.
     * @param string $value Raw input
     * @param object $metaData Meta data for field
     * @return string Formatted string
     */
    private static function formatString($value, $metaData) {
        if (self::shouldFormatString($metaData)) {
            return Api::print_text2str($value, self::getFormattingOptions(true));
        }

        return $value;
    }

    /**
     * Checks string formatting blacklist of fields
     * @param object $metaData Meta data for field
     * @return bool Whether to format the field or not
     */
    private static function shouldFormatString($metaData) {
        static $blackList;

        ($blackList || ($blackList = array(
            KF_NAMESPACE_PREFIX . '\AnswerContent',
            CONNECT_NAMESPACE_PREFIX . '\Answer',
        )));

        // format all strings expect answer content
        if (!\RightNow\Utils\Framework::inArrayCaseInsensitive($blackList, $metaData->container_class)) {
            return true;
        }
        // if it is answer content, only format the summary field
        else if (Text::stringContains($metaData->container_class, 'AnswerContent') &&
            $metaData->name === "Summary") {
            return true;
        }

        return false;
    }
}
