<?php
namespace OBSER\Shortcodes;
use OBSER\Classes\Shortcode;
class Test extends Shortcode {

    static $shortcode = 'test';

    static function generate_css(){
        return ;
    }
    
    static function output($atts, $content){
        return "holis 2222  ";
    }

}