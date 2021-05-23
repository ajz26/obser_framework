<?php
/**
 * Framework Name: OBSER
 * Framework URI: https://obser.co/framework
 * Author: A Zambrano | Obser.co
 * Description: Dev
 * Version: 1.0
 */

class OBSER{

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) self::$instance = new self;
        return self::$instance;
    }

    private function __construct() {
        self::define_const();
        self::load_autoload();
        self::load_functions();
    }

    private static function define_const(){

        if ( ! defined( 'OBSER_FRAMEWORK_DIR_PATH' ) OR ! defined( 'INMOOB_CORE_PLUGIN_VERSION' ) ) {
            
            if ( ! defined( 'OBSER_FRAMEWORK_DIR_PATH' ) ) {
                define('OBSER_FRAMEWORK_DIR_PATH',plugin_dir_path( __file__ ) );
            }
    
            if ( ! defined( 'OBSER_FRAMEWORK_DIR_URL' ) ) {
                define('OBSER_FRAMEWORK_DIR_URL',plugin_dir_url( __file__ ) );
            }
            unset($plugin_data);    
        }
    }

    private static function load_autoload(){
        require_once  OBSER_FRAMEWORK_DIR_PATH . '/vendor/autoload.php';
    }

    final public static function get_framework_info(){

        $framework_data = get_file_data(__file__,array(
            'Framework Name'    => 'Framework URI',
            'Framework URI'     => 'Framework URI',
            'Author'            => 'Author',
            'Description'       => 'Description',
            'Version'           => 'Version')
        );

        $framework_data['dir_path'] = OBSER_FRAMEWORK_DIR_PATH;
        $framework_data['dir_url']  = OBSER_FRAMEWORK_DIR_URL;

        return $framework_data;
    }


    private static function load_functions(){
        require_once  OBSER_FRAMEWORK_DIR_PATH . 'functions/functions.php';
    }
}

OBSER::instance();
