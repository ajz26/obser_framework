<?php
namespace OBSER\Classes;

abstract class Shortcode{
    
    static $shortcode;
    static $atts = [];
    static $general_encoled = false;

    static $wpb_namespace = "OBSER\\WPB_Components";

    static function get_component(){
        $class = static::get_class();
        $wpb_namespace = static::$wpb_namespace;
        $shortcode_class = "$wpb_namespace\\{$class}";
        return $shortcode_class;
    }

    abstract static function generate_css();
    public static function after_register(){}
    public static function enquee_scripts(){}
    public static function enquee_styles(){}
    public static function localize_script(){}
    public static function general_styles(){}

    final static function get_class(){
         return substr(strrchr(static::class, "\\"), 1);
    }

    final static function set_atts(array $atts = array()){
        static::$atts = (array)$atts;
    }

    final static function set_att(string $key,$value) : void {
        static::$atts[$key] = $value;
    }

    final static function get_atts( $key = null, $default = null){
        return isset($key) ? (isset(static::$atts[$key]) ? static::$atts[$key] : $default) : (array)static::$atts;
    }

    static function buildAtts($atts = array(), $content = null){
        self::set_atts($atts);
    }

    abstract static function output($atts, $content);

}