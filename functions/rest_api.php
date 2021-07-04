<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

class OBSER_REST_API{

    protected   static    $server;
    protected   static    $_endpoints;
    private     static    $instance             = [];
                static    $_endpoints_dir       = OBSER_FRAMEWORK_DIR_PATH .'/Api/Endpoints/';
                static    $shortcodes_namespace = 'OBSER\Api\Endpoints';

    public static function instance($server) {
        self::$server = $server;
        $class = get_called_class();
        if(!isset(self::$instance[$class]) || !self::$instance[$class] instanceof $class){
            self::$instance[$class] = new static();
        }
        return  self::$instance[$class]; // remove this line after testing
    }

    function __construct() {
        $this->load_endpoints();
        $this->register_endpoints();
    }


    protected  static function load_endpoints(){
        $class              = get_called_class();
        $_endpoints_dir     = static::$_endpoints_dir;
        self::$_endpoints   = self::read_folder($class,$_endpoints_dir);

    }

    protected static function read_folder($class,$_endpoints_dir,$subdir = null) {
        
        $_endpoints = [];

        if(isset($subdir)){
            $_endpoints_dir .= "$subdir/"; 
        }

        $scan = array_diff(scandir($_endpoints_dir), array('..', '.'));


        foreach($scan as $file) {
            if(preg_match('/(?<filename>[\w\-\d]*)\.php$/',$file, $matches)){

                $class_name             = $matches['filename'];
                $shortcodes_namespace   = static::$shortcodes_namespace;

                $endpoint               =  !isset($subdir) ? "$shortcodes_namespace\\$class_name" : "$shortcodes_namespace\\$subdir\\$class_name";

                if(!class_exists($endpoint)) continue;
                $_endpoints[$class][$endpoint::get_route()] = $endpoint;

            }else if(preg_match('/(?<foldername>^[\w\-\d]*)$/',$file, $matches)){
                $foldername          = $matches['foldername'];
                $temps               = self::read_folder($class,$_endpoints_dir,$foldername);
                $_endpoints[$class]  = isset($_endpoints[$class]) ? $_endpoints[$class] : [];
                $_endpoints[$class]  = array_merge($_endpoints[$class],$temps[$class]);
            }
        }

        return $_endpoints;
    }


    public function register_endpoints(){
        $class      = get_called_class();
        $_endpoints = (array) isset(self::$_endpoints) && isset(self::$_endpoints[$class]) ? self::$_endpoints[$class] : array();
        foreach($_endpoints AS $_endpoint){
            $namespace  = $_endpoint::get_namespace();
            $route      = $_endpoint::get_route();
            $method     = $_endpoint::get_method();

            register_rest_route($namespace, $route, array(
                'methods'               => $method,
                'callback'              => array($_endpoint,'callback'),
                'permission_callback'   => array($_endpoint,'permission_callback')
            ));

        }

       
    }

}

add_action('rest_api_init',array('OBSER_REST_API','instance'),1000);
