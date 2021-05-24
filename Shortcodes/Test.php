<?php
namespace OBSER\Shortcodes;
use OBSER\Classes\Shortcode;
class Test extends Shortcode {

    static $shortcode = 'test';

    static function generate_css(){
        return  "body{background-color:red}";
    }
    
    static function output($atts, $content){



        return "miguel es un cabrito";
    }

}