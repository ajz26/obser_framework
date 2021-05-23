<?php


namespace OBSER\Classes\Metabox;

class Field {
    protected $name;
    protected $title;
    protected $placeholder;
    protected $description;
    protected $type;

    public function get_name(){
        return isset($this->name) ? $this->name : null;
    }

    // 'name'          => 'price',
    // 'title'         => 'Precio desde : ',
    // 'description'   => 'Indíca el precio promedio de este servicio',
    // 'type'          => 'textfield',

    public function __construct($field){
        
        $this->name             = isset($field->name)           ? sanitize_key($field->name)    : null;
        $this->title            = isset($field->title)          ? $field->title                 : null;
        $this->description      = isset($field->description)    ? $field->description           : null;
        $this->type             = isset($field->type)           ? $field->type                  : 'textfield';
        $this->placeholder      = isset($field->placeholder)    ? $field->placeholder           : null;
    }

    public function render($post) : string {
        
        $name               = isset($this->name)            ? $this->name           : false ;
        $type               = isset($this->type)            ? $this->type           : false ;
        $description        = isset($this->description)     ? $this->description    : null ;
        $data               = isset($this->data)            ? $this->data           : false ;
        $placeholder        = isset($this->placeholder)     ? $this->placeholder    : false ;
        
        $value              = get_post_meta($post->ID,$name,true);
        if(!$name OR !$type) return false;

        $id = "obser-field-{$name}";
        switch($type){
            case "textfield" : 
                $field = "<input type='text' id='$id' name='{$name}' value='{$value}' placeholder='{$placeholder}'>";
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

        $description        = ($description) ? "<span class='ob-input-desc'><i>{$description}</i></span>" : null;
        $html = ($field) ? "<div class='form-control'>{$field} {$description}</div>" : null;

        return $this->container($html);
       
    }


    public function container($html){
        $name       =   $this->name;
        $title      =   $this->title;
        $id         =   "obser-field-{$name}";
        if($html){
            $label      = ($title) ? "<label for='$id'>$title</label>" : NULL;
            $html  ="<div class='form-field ob-form-field form-field-{$id}'>{$label} $html</div>";
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
