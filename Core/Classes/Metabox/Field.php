<?php


namespace OBSER\Classes\Metabox;

class Field {
    protected $name;
    protected $id;
    protected $placeholder;
    protected $description;
    protected $type;
    protected $columns;
    protected $taxonomy;
    protected $add_button;
    protected $clone;
    public function get_name(){
        return isset($this->name) ? $this->name : null;
    }

    public function get_id(){
        return isset($this->id) ? $this->id : null;
    }

    public function is_cloneable(){
        return isset($this->clone) ? $this->clone : false;
    }

    public function get_att($att){
        return isset($this->$att) ? $this->$att : null; 
    }



    public function __construct($field){
        
        $this->name             = isset($field->name)           ? $field->name                      : null;
        $this->id               = isset($field->id)             ? sanitize_key($field->id)          : null;
        $this->description      = isset($field->description)    ? $field->description               : null;
        $this->type             = isset($field->type)           ? $field->type                      : 'text';
        $this->taxonomy         = isset($field->taxonomy)       ? sanitize_key($field->taxonomy)    : null;
        $this->placeholder      = isset($field->placeholder)    ? $field->placeholder               : null;
        $this->std              = isset($field->std)            ? $field->std                       : null;
        $this->columns          = isset($field->columns)        ? $field->columns                   : null;
        $this->add_button       = isset($field->add_button)     ? $field->add_button                : __("Añadir otro",'obser');
        $this->clone            = isset($field->clone)          ? $field->clone                     : null;
        

    }

    public function render($post) : string {
        
        $id                 = isset($this->id)                      ? $this->id             : false ;
        $type               = isset($this->type)                    ? $this->type           : false ;
        $taxonomy           = isset($this->taxonomy)                ? $this->taxonomy       : false ;
        $description        = isset($this->description)             ? $this->description    : null  ;
        $placeholder        = isset($this->placeholder)             ? $this->placeholder    : false ;
        $std                = isset($this->std)                     ? $this->std            : false ;
        $name               = isset($this->id)                      ? $this->id             : null  ;
        

        $value_group   = (array)get_post_meta($post->ID,$name,true)   ?   : (array)$std;
        
        if($this->is_cloneable()){
            $name = "{$name}[]";
        }

        $value  = (count($value_group) <= 1) ? $value_group[0] : $value_group;
        
        if($type == 'taxonomy' && isset($taxonomy)){
            $this->data = get_terms(array('taxonomy' =>$taxonomy, 'hide_empty' => false, 'fields' => 'id=>name' )) ?: array();
        }

        if(!$name OR !$type) return false;

        $id     = "obser-field-{$id}";
        $field  = null;
      
        switch($type){
            case "text" : 
                $i = 0;
                foreach($value_group AS $value){ 
                    $clone   = ($i >=1) ? 'clone' : null; 
                    $field  .= "<div class='obser-field-container {$clone}' data-ref='{$id}'>";
                    $field  .= "<input type='text'  class='obser-field {$id}' name='{$name}' value='{$value}' placeholder='{$placeholder}'>";
                    $field  .= ($this->is_cloneable() && $i >= 1) ? "<button class=\"cloneable-field-button remove\"><span class=\"dashicons dashicons-no-alt\"></span></button>" : null;
                    $field  .= "</div>";

                    $i++;
                }
            break;

        //     <label class="switch">
        //     <input type="checkbox" checked>
        //     
        //   </label>
            case "switch" : 
                $checked = ($value == "1" ) ?"checked='checked'" : false;
                $value   = ($value == "1" ) ? 1 : 0;
                $field  .= "<div class='obser-field-container' data-ref='{$id}'>
                                <label class=\"switch\">
                                <input type='checkbox'  class='obser-field {$id}' name='{$name}' value='$value' $checked><span class=\"slider round\"></span></label>
                                </div>";
            break;

            case "media_link" :
                $preview =  (isset($value)) ? self::get_image_url_by_id((int)$value) : 'a';
                $field = "<div>
                                <input type='hidden' name='{$name}' value='{$value}'>
                                <img id='{$id}-preview-image' class='{$id}-preview-image preview-image' src='{$preview}' width='150'>
                                <button class='button media_link button-secondary upload_button upload--{$id}' data-field='$id'>Subir a medios</button>
                          </div>";
            break;
            case "textarea" : 
                $i = 0;
                foreach($value_group AS $value){ 
                    $clone   = ($i >=1) ? 'clone' : null; 
                    $field  .= "<div class='obser-field-container {$clone}' data-ref='{$id}'>";
                    $field  .= "<textarea type='text'  class='obser-field {$id}' name='{$name}' placeholder='{$placeholder}'>{$value}</textarea>";
                    $field  .= ($this->is_cloneable() && $i >= 1) ? "<button class=\"cloneable-field-button remove\"><span class=\"dashicons dashicons-no-alt\"></span></button>" : null;
                    $field  .= "</div>";

                    $i++;
                }


            break;
            case "editor" : 
                $field =  self::get_wp_editor($value,$name);
            break;
            case "checkbox" : 
                $checked = ($value == "1" ) ?"checked='checked'" : false;
                $field = "<input type='checkbox' id='$id' name='{$name}' value='1' {$checked}>";
            break;
            case "taxonomy" :
            case "dropdown" :
                $options = null;
                
                foreach((array)$this->data as $val => $label){
                    $selected = ($val == $value) ? "selected='selected'" : null;
                    $options .= "<option value='$val' $selected > $label </option>";
                }
                $field = "<select id='$id' name='{$name}' > $options</select>";
            break;
            default :
            $field = "";
        }

        $cloneable_button = $this->is_cloneable() ? "<button class='clone-button button button-primary' data-input='{$id}'>{$this->add_button}</button>" : null;
        $classes            = $this->is_cloneable() ? 'cloneable-field' : null;
        $description        = ($description) ? "<span class='ob-input-desc'><i>{$description}</i></span>" : null;
        $html               = ($field) ? "<div class='form-control {$classes}'>{$field} {$description} {$cloneable_button}</div>" : "";

        return $this->container($html);
       
    }


    public function container($html){
        $id         =   $this->id;
        $name       =   $this->name;
        $columns    =   $this->columns ? "ob-col col-{$this->columns}" : null ;
        $id         =   "obser-field-{$id}";
        if($html){
            $label      = ($name) ? "<label for='$id'>$name</label>" : NULL;
            $html  ="<div class='{$columns} form-field ob-form-field form-field-{$id}'>{$label} $html</div>";
        }

        return $html;

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


}
