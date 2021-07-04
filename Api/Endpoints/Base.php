<?php

namespace OBSER\Api\Endpoints;

use OBSER\Classes\Api\Endpoint;
use WP_REST_Request;

class Base extends Endpoint{
    protected static $route = 'macadamia';
    protected static $method = 'POST';

    static function callback(WP_REST_Request $data){
        
    }
}

