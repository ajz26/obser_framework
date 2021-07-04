<?php

function ob_get_logo_url(){
    $logo = OBSER_FRAMEWORK_DIR_URL . '/assets/images/logo-alt.png';
    return apply_filters('obser_framework_logo',$logo );
}
    
require_once OBSER_FRAMEWORK_DIR_PATH ."Config/Grid.php";
require_once OBSER_FRAMEWORK_DIR_PATH ."functions/shortcodes.php";
require_once OBSER_FRAMEWORK_DIR_PATH ."functions/rest_api.php";


