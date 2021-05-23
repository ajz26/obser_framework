<?php
namespace OBSER\Classes;
use WP_Post;
use _WP_Editors;
class Metabox {
   
    private   $ID;
    private   $title;
    private    $fields;
    private   $screen;
    private   $priority;
    private   $context;
    private   $type;
    private static  $_posts_meta = [];
    
    public  function get_context() {
    return $this->context;
    }
    
    
    public function get_screen() {
    return $this->screen;
    }
    public function get_fields() {
    return $this->fields;
    }
    
    public function get_ID() {
    return $this->ID;
    }

    public function get_title() {
        return $this->title;
    }
        
    function __construct(string $ID , string $title, string $screen = 'posts', array $fields = array(), $type = 'post' ,$context = 'advanced', $priority = 0){
        $this->ID       = $ID;
        $this->screen   = $screen;
        $this->title    = $title;
        $this->fields   = $fields;
        $this->priority = $priority;
        $this->context  = $context;

        if ( is_admin() ) {
            add_action( 'load-edit-tags.php', array( $this, 'enquee_script_and_styles' ) );
            
            if($type == 'post'){
                add_action( 'load-post.php',     array( $this, 'add_post_meta_box' ) );
                add_action( 'load-post-new.php', array( $this, 'add_post_meta_box' ) );
                add_action( 'load-post.php',     array( $this, 'enquee_script_and_styles' ) );
                add_action( 'load-post-new.php', array( $this, 'enquee_script_and_styles' ) );

            }else if('taxonomy'){
                add_action( 'init',     array( $this, 'add_taxonomy_meta_box' ) );
            }
            
            add_action( 'wp_ajax_get_image_url_by_id', array($this,'get_image_url' )  );

        }

        return $this;
    }

    public function enquee_script_and_styles(){
        add_action( 'admin_enqueue_scripts',function(){
            wp_enqueue_media();
            wp_enqueue_style( 'ob_metabox', OBSER_DIR_URL . '/assets/css/metabox.css');
            wp_enqueue_script( 'ob_metabox', OBSER_DIR_URL . '/assets/js/metabox.js', array('jquery') );

        });
    }

    public function add_taxonomy_meta_box(){

        $fields = $this->fields;
        $screen = $this->screen;
        foreach($fields AS $field){
           $name   = (isset($field['name'])    && !empty($field['name']))  ? sanitize_key($field['name'])  : null ;
           $std     = (isset($field['std'])    && !empty($field['std']))     ? $field['std']  : "" ; 
           register_meta( 'term', $name , array( 'default' => $std, 'sanitize_callback' => array('Taxonomy','sanitize_term_meta_value') ));
        }

        // add_action( "{$screen}_add_form_fields", array( $this, 'render_taxonomy_fields' ) );

        add_action( "{$screen}_edit_form_fields", array($this,'render_taxonomy_fields') );

        add_action( "edit_{$screen}",   array($this,'save_term_meta') );
        add_action( "create_{$screen}", array($this,'save_term_meta') );
    }

    


    public function add_post_meta_box(){
        add_action('add_meta_boxes',function(){
            add_meta_box( $this->ID, $this->title, array($this,'render'), $this->screen, $this->context, $this->priority);
        },1000);

        add_action( "save_post_{$this->screen}", array( $this, 'save_metabox' ), 10, 2 );
    }

    public function save_metabox($post_id, $post){

         $nonce   = isset( $_POST['obser_mb'] ) ? $_POST['obser_mb'] : '';

         // Check if nonce is valid.
         if ( ! wp_verify_nonce( $nonce)  || ! current_user_can( 'edit_post', $post_id ) ||  wp_is_post_autosave( $post_id ) ||  wp_is_post_revision( $post_id ) ) {
             return;
         }
  
         $fields = $this->fields;

         foreach($fields AS $field){
            $name   = (isset($field['name'])    && !empty($field['name']))  ? sanitize_key($field['name'])  : null ;
            $value  = (isset($_POST[$name])     && !empty($_POST[$name]))   ?  $_POST[$name]    : null ; 
            
            if(isset($_POST[$name])){
                update_post_meta($post_id,$name,$value);
            }

         }

        

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



    function save_term_meta( $term_id ) {

        $nonce   = isset( $_POST['obser_mb'] ) ? $_POST['obser_mb'] : '';

         // Check if nonce is valid.
         if ( ! wp_verify_nonce( $nonce) ) {
             return;
         }

         $fields = $this->fields;

         foreach($fields AS $field){
            $name       = (isset($field['name'])    && !empty($field['name']))  ? sanitize_key($field['name'])  : null ;
            $value      = (isset($_POST[$name])     && !empty($_POST[$name]))   ? sanitize_text_field( $_POST[$name] )   : null ; 
            $old_value  = Taxonomy::get_term_meta($term_id, $name);

            if ( $old_value && '' === $value ){
                delete_term_meta( $term_id, $name );
            }else if ( $old_value !== $value ){
                update_term_meta( $term_id, $name, $value );
            }
         }

    }

    private static function get_field(array &$field, &$value) : ? string {

        $name               = (isset($field['name'])        && !empty($field['name']))             ? sanitize_key($field['name'])        : false ;
        $type               = (isset($field['type'])        && !empty($field['type']))             ? $field['type']        : false ;
        $data               = (isset($field['data'])        && !empty($field['data']))             ? $field['data']        : false ;

        if(!$name OR !$type) return false;
        $id = "obser-field-{$name}";
        switch($type){
            case "textfield" : 
                $field = "<input type='text' id='$id' name='{$name}' value='{$value}'>";
            break;
            case "media_link" :
                $preview =  (isset($value)) ? self::get_image_url_by_id((int)$value) : 'a';
                $field = "<div>
                                <input type='hidden' id='$id' name='{$name}' value='{$value}'>
                                <img id='{$id}-preview-image' class='{$id}-preview-image preview-image' src='{$preview}' width='150'>
                                <button class='button media_link button-secondary upload_button upload--{$id}' data-field='$id'>Subir a medios</button>
                          </div>";
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

            case "dropdown" :
                $options = null;
                foreach((array)$data as $val => $label){
                    $selected = ($val == $value) ? "selected='selected'" : null;
                    $options .= "<option value='$val' $selected > $label </option>";
                }
                $field = "<select id='$id' name='{$name}' > $options</select>";
            break;

            default :
            $field = "";
        }

        return ($field) ? "<div class='form-control'>{$field}</div>" : null;
    }

    private static function get_post_meta($post_id, $key, bool $multiple = false){
        $post_meta  = self::$_posts_meta;
        $data       = isset($post_meta[$post_id][$key]) ? $post_meta[$post_id][$key] : NULL;
        return (!$multiple AND isset($data)) ? $data[0] : $data;
    }

    public function render($post){
        self::$_posts_meta[$post->ID] = get_post_meta($post->ID);

        $fields = $this->fields;
        $html = "";
        wp_nonce_field(-1, 'obser_mb' );

        foreach($fields as $field){
            $name   =   $field['name'];
            $title  =   $field['title'];
            $id     =   "obser-field-{$name}";
            $value  =   static::get_post_meta($post->ID, $name) ?: "";
            $field_html = self::get_field($field,$value);

            if($field_html){
                $label = ($title) ? "<label for='$id'>$title</label>" : NULL;
                $html .="<div class='form-field ob-form-field form-field-{$id}'>{$label} $field_html</div>";
            }
        
        }
        echo $html;
    }

    function get_image_url() {

        if(!isset($_GET['id']) ){ wp_send_json_error();}
        $id = $_GET['id'];
        $url = self::get_image_url_by_id($id, $size = 'medium');
        $data  = array(
            'image' => $url
        );
       
        wp_send_json_success( $data );

    }

    public static function get_image_url_by_id($id, $size = 'medium'){
        $image = \wp_get_attachment_image_src( $id, $size, false);
        return isset($image[0]) ? $image[0] : null;
    }


    public function render_taxonomy_fields($term = null){
        $fields = $this->fields;
        $mb_title  = $this->title;
        $html   = "";
        wp_nonce_field(-1, 'obser_mb' );

        $term_id = isset($term->term_id ) ? $term->term_id : false;
      
        foreach($fields as $field) {
            
            $name           =   $field['name'];
            $title          =   $field['title'];
            $id             =   "obser-field-{$name}";
            $value          =   Taxonomy::get_term_meta($term_id, $name);
            $field_html     =   self::get_field($field,$value);

            if($field_html){
                $label = ($title) ? "<th><label for='$id'>$title</label></th>" : NULL;
                $html .="<tr class='form-field form-field-{$id}'>{$label} <td>$field_html</td></tr>";
            }
        }

        if(strlen($html)){
            $html = "<tr class='obser-term-meta-title'>
                        <td colspan='2'  style='font-size: 1.5rem; font-weight: bold;margin: 0;padding: 0;'>{$mb_title}</td>
                    </tr>{$html}";
        }
        echo $html;
    }


    public static function remove( $metabox, $screen = null,  $context = null ){

        if( is_object($metabox)){
            $screen     = $metabox->get_screen();
            $context    = $metabox->get_context();
            $metabox    = $metabox->get_ID();
        }

        add_action('add_meta_boxes',function() use ($metabox,$screen,$context ){
            remove_meta_box($metabox,$screen,$context);
        },1000000);
    }


}

