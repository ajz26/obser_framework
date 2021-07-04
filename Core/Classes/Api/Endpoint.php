<?php namespace OBSER\Classes\Api;

abstract class Endpoint{
    
    protected static $namespace = 'obser/v1';
    protected static $route;
    protected static $method;
    protected static $permission_callback;

    abstract static function callback( \WP_REST_Request $data );

    static function get_namespace(){
        return static::$namespace;
    }

    static function get_route(){
        return static::$route;
    }

    static function get_method(){
        return static::$method ?: 'GET';
    }

    static function permission_callback( \WP_REST_Request $data){
        return "__return_true";
    }

    static function get_permission_callback(){
        return static::$permission_callback;
    }

}