<?php
namespace OBSER\Classes\Metabox;
use WP_Post;
use _WP_Editors;
use Exception;
use OBSER\Classes\Helpers;

class Metabox {
   
    private   $ID;
    private   $title;
    private   $fields;
    private   $screen;
    private   $priority;
    private   $context;
    private   $type;
    private   $tabs;
    private   $post;
    private static  $_posts_meta = [];
    
    public  function get_context() {
    return $this->context;
    }
    
    
    public function get_screen() {
    return $this->screen;
    }
    
    public function get_ID() {
    return $this->ID;
    }

    public function get_title() {
        return $this->title;
    }
        
    function __construct($args){

        $this->ID       = isset($args['ID'])            ? sanitize_key( $args['ID']  )          : null;
        $this->screen   = isset($args['post_types'])    ? $args['post_types']                   : 'posts';
        $this->title    = isset($args['title'])         ? $args['title']                        : null;
        $this->priority = isset($args['priority'])      ? $args['priority']                     : 0;
        $this->context  = isset($args['context'])       ? $args['context']                      : 'advanced';
        $this->type     = isset($args['type'])          ? $args['type']                         : 'post';

        $this->tabs     = isset($args['tabs'])          ? $this->parse_tabs($args['tabs'])      : null;
        $this->fields   = isset($args['fields'])        ? $this->parse_fields($args['fields'])  : array();



        if ( is_admin() ) {
            add_action( 'load-edit-tags.php', array( $this, 'enquee_script_and_styles' ) );
            
            if($this->type == 'post'){
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

    public static function get_image_url_by_id($id, $size = 'medium'){
        $image = \wp_get_attachment_image_src( $id, $size, false);
        return isset($image[0]) ? $image[0] : null;
    }

    public function enquee_script_and_styles(){
        add_action( 'admin_enqueue_scripts',function(){
            wp_enqueue_media();
            wp_enqueue_style( 'ob_metabox', OBSER_FRAMEWORK_DIR_URL . '/assets/css/metabox.css');
            wp_enqueue_script( 'ob_metabox', OBSER_FRAMEWORK_DIR_URL . '/assets/js/metabox.js', array('jquery') );

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

    private function get_fields(){
        $_fields   = [];
        foreach($this->tabs AS $tab){
        $_fields = array_merge($_fields,$tab->fields);
        }
        $_fields = array_merge($_fields,$this->fields);
        return $_fields;
    }

    public function save_metabox($post_id, $post){

         $nonce     = isset( $_POST['obser_mb'] ) ? $_POST['obser_mb'] : '';
         
         if ( ! wp_verify_nonce( $nonce)  || ! current_user_can( 'edit_post', $post_id ) ||  wp_is_post_autosave( $post_id ) ||  wp_is_post_revision( $post_id ) ) {
             return;
         }

         $_fields = $this->get_fields();
         
         foreach($_fields AS $field){
            $name           = $field->get_id();
            $type           = $field->get_att('type');
            $value          = (isset($_POST[$name]) && !empty($_POST[$name]))   ?   $_POST[$name]   : null;

            switch($type){
                case 'taxonomy':
                    $taxonomy = $field->get_att('taxonomy');
                    wp_set_object_terms($post_id,(int)$value,$taxonomy);
                break;
    
                case 'switch':
                $value = (isset($value) && $value !== "") ? 1 : 0;
                default:
                update_post_meta($post_id,$name,$value);

            }

            
         }
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



    private function parse_tabs(array $_tabs = null){
        
        if( !isset($_tabs) || !is_array($_tabs)) return null;

        $tabs = [];

        foreach($_tabs AS $tab => $data){
            $ID     = isset($tab)               ? sanitize_key( $tab )           : null;
            $label  = isset($data['label'])     ? $data['label']                 : null;
            $icon   = isset($data['icon'])      ? $data['icon']                  : null;
            
            if(is_null($ID) || is_null($icon)) continue;

            $tabs[$tab] = new Tab($ID,$label,$icon);
        }

        return $tabs;
    }

    private function parse_fields(array $_fields = null){
        if( !isset($_fields) || !is_array($_fields)) return null;

        $fields = [];

        foreach($_fields AS $field){
            
            $field      = Helpers::array_to_object($field);
            $tab        = isset($field->tab) ? $field->tab : null;
            $field      = new Field($field);

            if(isset($tab) && isset($this->tabs[$tab])){
                $this->tabs[$tab]->set_field($field);
            }else{
                $fields[]   = $field;
            }

        }

        return $fields;
    }


    private function render_tabs(){
       $tabs = $this->tabs;
       $html = "";
       $navs  = null;
       $tabs_content = null;
       $n_tabs = 0;
       foreach($tabs AS $tab){
            $content        = null;
            $fields         = $tab->fields;
            $current        = ($n_tabs <= 0) ? true : false;
            if(count($fields) >= 1){
                $navs            .= $tab->render_nav($current);
                $content          = $this->render_fields($fields);
                $tabs_content    .= $tab->render_content($content,$current);
                $n_tabs++;
            }
       }


        $html ="<div class='ob-tabs-container'>
                    <div class='nav-tabs-container'>
                        <ul class='nav-tabs-ul'>{$navs}</ul>
                    </div>
                    <div class='tabs-content'>
                        {$tabs_content}
                    </div>
                </div>";

       return $html;

    }

    public function render($post){
        $this->post = $post;
        $html       = "";
        $fields     = $this->fields;

        wp_nonce_field(-1, 'obser_mb' );
        $html   .= $this->render_tabs();
        $html   .= $this->render_fields($fields);
        
        echo $html;
    }

    private function render_fields($fields){
        $post = $this->post;
        $html = "";
        foreach($fields as $field){

            $html .= $field->render($post);
        }

        return $html;
    }



    // public function render_taxonomy_fields($term = null){
    //     $fields = $this->fields;
    //     $mb_title  = $this->title;
    //     $html   = "";
    //     wp_nonce_field(-1, 'obser_mb' );

    //     $term_id = isset($term->term_id ) ? $term->term_id : false;
      
    //     foreach($fields as $field) {
            
    //         $name           =   $field['name'];
    //         $title          =   $field['title'];
    //         $id             =   "obser-field-{$name}";
    //         $value          =   Taxonomy::get_term_meta($term_id, $name);
    //         $field_html     =   self::get_field($field,$value);

    //         if($field_html){
    //             $label = ($title) ? "<th><label for='$id'>$title</label></th>" : NULL;
    //             $html .="<tr class='form-field form-field-{$id}'>{$label} <td>$field_html</td></tr>";
    //         }
    //     }

    //     if(strlen($html)){
    //         $html = "<tr class='obser-term-meta-title'>
    //                     <td colspan='2'  style='font-size: 1.5rem; font-weight: bold;margin: 0;padding: 0;'>{$mb_title}</td>
    //                 </tr>{$html}";
    //     }
    //     echo $html;
    // }


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

