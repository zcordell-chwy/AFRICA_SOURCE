<?php
namespace Custom\Widgets\aesthetic;

class ImageBannerTitle extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
    	$this->data['display_attrs'] = array();
    	$this->data['display_attrs']['banner_img_path'] = $this->data['attrs']['banner_img_path'];
    	$this->data['display_attrs']['banner_title'] = $this->data['attrs']['banner_title'];

        return parent::getData();

    }
}