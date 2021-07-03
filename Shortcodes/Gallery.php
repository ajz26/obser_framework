<?php

namespace OBSER\Shortcodes;

use OBSER\Classes\Shortcode;

class Gallery extends Shortcode{

    static $shortcode = 'obser_gallery';

    public static function generate_css(){

    }

    public static function enquee_scripts(){

    }

    public static function enquee_styles(){

    }

    public static function output($atts,$content){
        $element_id     = self::get_atts('vc_id');
    }
}