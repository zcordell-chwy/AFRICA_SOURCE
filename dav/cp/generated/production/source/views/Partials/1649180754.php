<?
        namespace Custom\Libraries\Widgets {
            class CustomSharedViewPartials extends \RightNow\Libraries\Widgets\SharedViewPartials {
                    static function sample_view ($data) {
        extract($data);
        ?>sample custom shared view partial<?
    }
            }
        }