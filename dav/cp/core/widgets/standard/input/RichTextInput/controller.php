<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class RichTextInput extends \RightNow\Widgets\TextInput {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // TK - verify the specified Connect field is a longtext and has a content type that includes MD.

        // TK - Need to implement better (low key) way to include these scripts.
        // Patrick nixed my grand, ideal solution that adds some pretty powerful JS code reuse to the framework.
        $this->CI->clientLoader->addJavaScriptInclude(\RightNow\Utils\Url::getCoreAssetPath('thirdParty/js/reMarked.min.js'));
        $this->CI->clientLoader->addJavaScriptInclude(\RightNow\Utils\Url::getCoreAssetPath('thirdParty/js/Markdown.Converter.min.js'));

        $parent = parent::getData();

        if ($parent === false) return false;

        if ($this->data['value']) {
            $this->data['js']['initialValue'] = \RightNow\Libraries\Formatter::formatMarkdownEntry($this->data['value']);
        }

        return $parent;
    }
}
