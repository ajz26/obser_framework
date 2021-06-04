<?php 

namespace OBSER\Classes;
use OBSER\Classes\Metabox\Metabox;
use _WP_Editors;

class Settings {

    private     static     $args       = [];
    private     static     $sections   = [];
    private     static     $data       = [];

    private     static          $instance = null;

    public static function instance() {
        if ( null === self::$instance ) self::$instance = new self;
        return self::$instance;
    }

    private     function __construct(){ }

    public static function set_args(string $opt_name ,array $args){
        $opt_name                       = (string) sanitize_title( $opt_name );
        static::$args[$opt_name]        = (object) self::_merge_args($args);
    }

    private static function _merge_args(array &$args){
        return (object)array_merge(array(
            'display_name'  => '',
            'menu_title'    => '',
            'page_icon'     => '',
            'page_parent'   => '',
            'page_slug'     => '',
            'page_priority' => 50
        ),$args);
    }

    private static function get_sections(&$opt_name){
        return isset(self::$sections[$opt_name]) ? self::$sections[$opt_name] : null;
    }

    private static function get_args($opt_name, $key, $default = null){
        return isset(self::$args[$opt_name]->$key) ? self::$args[$opt_name]->$key : $default;
    }

    static function get_wp_editor( $content = '', $editor_id, array $options = array() ) {
        ob_start();
     
        wp_editor( $content, $editor_id, array_merge(array('textarea_name'=>$editor_id),$options) );
     
        $temp = ob_get_clean();
        $temp .= \_WP_Editors::enqueue_scripts();
        // $temp .= print_footer_scripts();
        $temp .= \_WP_Editors::editor_js();
     
        return $temp;
    }

    private static function get_field(array &$field, &$value) : ? string {

        $name               = (isset($field['name'])        && !empty($field['name']))             ? sanitize_key($field['name'])        : false ;
        $type               = (isset($field['type'])        && !empty($field['type']))             ? $field['type']        : false ;

        if(!$name OR !$type) return false;
        $id = "obser-field-{$name}";
        switch($type){
            case "media_link" :
                $preview =  (isset($value)) ? Metabox::get_image_url_by_id((int)$value) : 'a';
                $field = "<div>
                                <input type='hidden' id='$id' name='{$name}' value='{$value}'>
                                <img id='{$id}-preview-image' class='{$id}-preview-image preview-image' src='{$preview}' style='max-width:150px'>
                                <button class='button media_link button-secondary upload_button upload--{$id}' data-field='$id'>Subir a medios</button>
                          </div>";
            break;

            case "textfield" : 
                $field = "<input type='text' id='$id' name='{$name}' value='{$value}'>";
            break;

            case "textarea" : 
                $field = "<textarea  id='$id' name='{$name}'>{$value}</textarea>";
            break;
            case "editor" : 
                $field =  self::get_wp_editor($value,$name);
            break;
            case "checkbox" : 
                $checked = ($value == "1" ) ?"checked='checked'" : false;
                $field = "<input type='checkbox' id='$id' name='{$name}' value='1' {$checked}>";
            break;

            default :
            $field = "";
        }

        return $field;
    }

    public static function add_menu_page(string &$opt_name){
        $display_name       = self::get_args($opt_name,'display_name');
        $menu_title         = self::get_args($opt_name,'menu_title');
        $page_icon          = self::get_args($opt_name,'page_icon');
        $page_parent        = self::get_args($opt_name,'page_parent');
        $page_slug          = self::get_args($opt_name,'page_slug');
        $page_priority      = self::get_args($opt_name,'page_priority');

        if(!$page_parent){
           $page = add_menu_page($menu_title, $display_name , 'manage_options', $page_slug ,function() use($opt_name){
                self::render($opt_name);
            }, $page_icon ,$page_priority);
            $page = add_submenu_page($page_slug, $display_name ,$menu_title, 'manage_options', $page_slug);
        }else{
            $page = add_submenu_page($page_parent, $display_name ,$menu_title, 'manage_options', $page_slug ,function() use($opt_name){
                self::render($opt_name);
            },$page_priority);
        }

        if($page){
            self::load_script($page,$opt_name);
        }

    }



    public static function load_script(&$page, &$opt_name){

        add_action( "load-{$page}",function() use($page, $opt_name){
            add_action( 'admin_enqueue_scripts',function() use($page, $opt_name){
                wp_enqueue_media();
                wp_enqueue_script( 'metaboxes', OBSER_FRAMEWORK_DIR_URL . '/assets/js/metabox.js', array('jquery') );

                wp_enqueue_style( 'ob_settings', OBSER_FRAMEWORK_DIR_URL . '/assets/css/ob_settings.css');
                wp_enqueue_script( 'ob_settings', OBSER_FRAMEWORK_DIR_URL . '/assets/js/admin-settings.js', array( 'jquery-ui-core', 'jquery-ui-tabs' ) );
                wp_localize_script( 'ob_settings', 'ob_settings', array(
                    "ajax_url"  => admin_url( 'admin-ajax.php' ),
                    "opt_name"  => $opt_name,
                    "form"      => "ob_settings-form-{$opt_name}",
                    "fields"    => self::get_field_list($opt_name),
                    "nonce"     => wp_create_nonce( 'ob_settings' ),
                ));
            });
        },10000);
    }


    static function get_field_list($opt_name){
        $fields = [];
        $sections = (array)self::get_sections($opt_name);

        foreach($sections AS $section){
            foreach($section->fields AS $field){
                $fields[] = $field['name'];
            } 
        }
        return $fields;
    }

    static function save_settings_ajax($opt_name){
        
        $update = [];
        $fields = self::get_field_list($opt_name);

        foreach($fields AS $field){
            $update[$field] = $_POST[$field];
        }

        self::save_setting($opt_name, $update);

        wp_die($opt_name);

    }


    private static function save_setting(&$opt_name, &$data){
        $value = maybe_serialize($data);
        update_option( $opt_name, $value);
    }

    protected static function load_first_data($opt_name){

        $settings = get_option( $opt_name, array());
        $settings = is_serialized( $settings ) ? (array)maybe_unserialize($settings) : array();

        self::$data[$opt_name] = $settings;
        
    }

    static function get_setting(string $opt_name , string $key = null){
        
        if(!isset(self::$data[$opt_name])){
            self::load_first_data($opt_name);
        }

        return isset($key) ? (isset(self::$data[$opt_name][$key]) ? self::$data[$opt_name][$key] : null) : self::$data[$opt_name];
        
    }
    

    public static function render(string &$opt_name){
        $sections       = (array)self::get_sections($opt_name);

        // self::save_setting($opt_name, array('hola'=> true));

        function ob_render_nav($sections) {
            $items = null;
            $s = 0;
            foreach($sections AS $section){
                $link       = sanitize_title( $section->title );
                $heading    = $section->heading;
                $current       = ($s == 0) ? 'current' : null;

               $items       .= "<li class='ob_settings-nav-item level_1'>
                                    <a href='#$link' data-id='$link' class='ob_settings-nav-anchor level_1  {$current}'>$heading</a>
                                </li>";
                $s++;
            }
    
               return  " <div class='ob_settings-nav'>
                <div class='ob_settings-nav-bg'></div>
                <ul class='ob_settings-nav-list level_1'>{$items}</div>";
        }

        function render_header($display_name) {
            $logo = ob_get_logo_url();
           return "
            <div class='ob_settings-header'>
                <div class='ob_settings-header-logo'>
                    <img src='$logo'>
                </div>
                <div class='ob_settings-header-title'>
                    <span>{$display_name} —  </span> <h2></h2>
                </div>

                <div class='ob_settings-header-cta'>
                    <button class='btn-ob_settings-header-cta'>Guardar cambios</button>
                </div>
            </div>";
        }


        function render_content($sections) {
        $section_html = "";

        $s = 0;
        foreach($sections AS $section){
            $id            = sanitize_title( $section->title );
            $heading       = ucwords($section->heading);
            $current       = ($s == 0) ? 'current' : null;

            $section_html .= "<section class='ob_settings-section {$current}' data-id='{$id}'>";
            $section_html .= ($heading)          ?   "<div class='ob_settings-section-heading'><h3>{$heading}</h3></div>" : null;
            $section_html .= ($section->fields)  ?   "<div class='ob_settings-section-content'>". Settings::render_fields($section->fields)."</div>": null;
            $section_html .= "</section>";

            $s++;
        }

            return "
             <div class='ob_settings-content'>
                 {$section_html}
             </div>";
         }
            

        $section_html           = "";
        global $current_opt_name;
        $current_opt_name       = $opt_name;
        $display_name           = self::get_args($current_opt_name,'display_name');

        $section_html .=  "<div class='ob_settings-container'><form id='ob_settings-form-{$opt_name}'>";
        $section_html .= render_header($display_name);
        $section_html .= ob_render_nav($sections);
        $section_html .= self::get_section_pre();
        $section_html .= render_content($sections);

        $section_html .=  "</form></div>";

        echo $section_html;
    }


    private static function get_section_pre(){

        global $current_opt_name;
        $page_slug          = self::get_args($current_opt_name,'page_slug');

        ob_start();
        settings_fields( "obser-{$current_opt_name}-group" );
        do_settings_sections( $page_slug );

        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    static function render_fields(&$fields){
        global $current_opt_name;
        $html = "";

        foreach($fields AS $field){
            $name           = $field['name'];
            $title          = $field['title'];
            $id             = "obser-field-{$name}";
            $val            = self::get_setting($current_opt_name,$name);
            $field_html     =  self::get_field($field,$val); 

            if($field_html){
                $label = ($title) ? "<div class='ob_settings-form-row-title' > <label for='$id'>$title</label></div>" : NULL;
                $html .="<div class='ob_settings-form-row'>{$label} <div class='ob_settings-form-row-field'><div class='ob_settings-form-row-control'>$field_html</div></div></div>";
            }

        }

        return $html;
    }


    public static function init($opt_name){
        
        add_action( 'admin_menu',function() use($opt_name){
            self::add_menu_page($opt_name);
        });

        add_action( 'admin_init', function() use($opt_name){
            self::register_setting($opt_name);
        });

        add_action( "wp_ajax_save_{$opt_name}", function() use($opt_name){

            self::save_settings_ajax($opt_name);
        });
    }

    private static function register_setting($opt_name){
        register_setting( "obser-{$opt_name}-group", $opt_name );
    }

    private static function create_section($data) : array {

        return array_merge(array(
                'title'   => '',
                'icon'    => '',
                'heading' => '',
                'desc'    => '',
                'fields'  => ''
        ), $data );

    }

    public static function set_section(string $opt_name , array $data = array()){
        self::$sections[$opt_name][]  = (object)self::create_section($data);
    }
    
}

Settings::instance();
