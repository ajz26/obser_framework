<?php

namespace OBSER\Classes\Mails;

abstract class Controller{

    protected  ?string  $templates_dir = OBSER_FRAMEWORK_DIR_PATH ."/Mails/templates/";
    protected   static  $_instances = array();
    private     string  $notification_key;
    private     array   $from;
    private     array   $replyTo;
    private     array   $to;
    private     string  $subject;
    private     string  $template;
    private     array   $data     = [];
    public      ?string  $html;

    function __construct(string $notification_key, array $from, array $replyTo, array $to, ?string $subject = "",array $data = [], string $template = 'general', ?string $templates_dir = null){
        
        self::$_instances[]     = $this;
        $this->notification_key = $notification_key;
        // $this->templates_dir    = $templates_dir ?: $this->templates_dir;

        $this->before_construct();

        $this->from       = $from;
        $this->replyTo    = $replyTo;
        $this->to         = $to;
        $this->subject    = $subject;
        $this->template   = $template;
        $this->data       = $data;
    
        $this->set_html_content();
        $this->after_construct();
    }

    // public static function getInstances($includeSubclasses = false){
    //     $return = [];

    //     foreach(self::$_instances as $instance) {
    //         if($instance instanceof Controller) {
    //             $return[] = $instance;
    //         }
    //     }

    //     return $return;

    // }

    // public function get_notification_key(){
    //     return $this->notification_key;
    // }


    //HOOKS
    function after_construct(){}
    function before_construct(){}

    function get_template_content($template){

        $template_file  = "{$this->templates_dir}/{$template}.html";
        $content        = file_exists($template_file) ? file_get_contents($template_file) : null;

        return $content;
    }

    function set_html_content(){
          $this->html  = $this->get_template_content($this->template);
    }


    function prepare(){
        if(!$this->html) return false;
        $template_name  = $this->template;
        $this->html     = $this->parse_html_data();
        $this->html     = apply_filters('obser_mail', $this->html ,$this->data,$template_name );
        $this->html     = apply_filters("obser_mail_{$template_name}", $this->html,$this->data,$template_name );

        return $this->html;
    }


    private function parse_html_data(){
        $data       = array_map([$this,'data_to_string'],$this->data);
        $content    = $this->html;


        preg_match_all('/\{{2}(?<group>mail|\@include)\:(?:(?<key>[\w\_\-]+))+(?:(?:\:)+(?<alt>[\s\w\_\-]+))?\}{2}/',
        $content,$matches, PREG_SET_ORDER);

        foreach($matches as $match){
            $group      = isset($match['group'])    ? sanitize_key($match['group'])     : null;
            $key        = isset($match['key'])      ? sanitize_key($match['key'])       : null;
            $alt        = isset($match['alt'])      ? sanitize_key($match['alt'])       : null;
            
            switch($group){
                case 'include':
                    $value  = $this->get_template_content($key) ?: null;
                break;
                default :
                    $value  = isset($data[$key]) ? $data[$key] : ($alt ?: null); 
            }


            $content    = str_replace($match[0],$value,$content);

        }

        $this->html = $content;

        return $this->html;

    }

    function data_to_string($value){
        $string = !is_array($value) ? "{$value}" : implode(',',$value); 
        return  $string;
    }


    function send(){

        $reply_to_name  = $this->replyTo['name'];
        $reply_to_email = $this->replyTo['email'];
        $from_name      = $this->from['name'];
        $from_email     = $this->from['email'];
        $to_email       = $this->to['email'];
        $subject        = $this->subject;
        $headers        = "From: {$from_name} <$from_email> \r\n";
        $headers       .= "Reply-To: {$reply_to_name} <{$reply_to_email}> \r\n";

        $resp           = \wp_mail($to_email, $subject ,$this->html,$headers);
        return $resp;
    }

    static function set_content_type(){
        return "text/html";
    }

}