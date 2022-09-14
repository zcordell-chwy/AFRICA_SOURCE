<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

class DisplayNameInput extends \RightNow\Widgets\TextInput {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['attrs']['name'] = 'Socialuser.DisplayName';
        parent::getData();
        // only pre-populate the displayname field if the user is logged in
        if (!\RightNow\Utils\Framework::isLoggedIn()) {
            $this->data['value'] = '';
        }
        $this->data['attrs']['required'] = $this->data['attrs']['always_required'] || $this->data['socialUserID'];
    }
}
