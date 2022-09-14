<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Libraries\Search,
    RightNow\Utils\Config,
    RightNow\Utils\Url;

class SupportedLanguages extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!(Config::getConfig(OKCS_ENABLED))) {
            echo $this->reportError(Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        $userLocale = $this->CI->model('Okcs')->getUserLanguagePreferences();
        if ($userLocale->error === null) {
            if (is_null($userLocale))
            {
                $userLocale = $this->CI->model('Okcs')->getUserLocale();
                $this->data['js']['defaultLocale'] = str_replace("_", "-", $userLocale);
            }
            else
            {   
                if(!is_null($userLocale->key) || !is_null($userLocale->value))
                    $userLocale = $userLocale->value;

                $this->data['js']['defaultLocale'] = str_replace("_", "-", $userLocale);
            }
            $selectedLocale = urldecode(Url::getParameter('loc'));
            $searchText = strlen(Url::getParameter('kw')) === 0 ? Url::getParameter('keyword') : Url::getParameter('kw');
            
            $supportedLanguages = $this->CI->model('Okcs')->getSupportedLanguages(); 
            if ($supportedLanguages !== null) {
                if ($supportedLanguages->errors !== null) {
                    echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($supportedLanguages->errors[0]));
                    return false;
                }
                else {
                    $availableLanguages = array();
                    $selected = !empty($selectedLocale) ? explode(",", $selectedLocale) : (count($userLocale) === 0 ? array($this->data['js']['defaultLocale']) : explode(",", $this->data['js']['defaultLocale']));
                    
                    // If url has keyword and no locale, no language should be checked.
                    if (!is_null($searchText) && strlen($selectedLocale) === 0)
                        $selected = null;

                    // Replace underscore (_) in locale code from IM API as Search API expects hyphen (-) in locale code
                    // then adds a key "selected" in the array to track if a locale code is to be marked as checked in View.
                    foreach ($supportedLanguages->results as $lang) {
                        $item = array('code' => str_replace("_", "-", $lang->localeCode), 'description' => $lang->localeDesc);
                        if (!is_null($selected)) {
                            foreach ($selected as $loc) {
                                if (strcmp($item['code'], $loc) === 0)
                                {
                                    $item['selected'] = true;
                                    break;
                                }
                                else {
                                    $item['selected'] = false;
                                }
                            }
                        }
                        array_push($availableLanguages, $item);
                    }
                    $this->data['availableLanguages'] = $availableLanguages;
                    $search = Search::getInstance($this->data['attrs']['source_id']);
                    $filter = $search->getFilter($this->data['attrs']['filter_type']);
                    if ($filter) {
                        $this->data['js'] = array(
                            'filter'  => $filter
                        );
                    }
                }
            }
        }
        else {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($userLocale->error));
            return false;
        }
    }
}

