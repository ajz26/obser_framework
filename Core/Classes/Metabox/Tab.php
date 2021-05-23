<?php
namespace OBSER\Classes\Metabox;

class Tab {
    protected   $ID;
    protected   $label;
    protected   $icons;
    public      $fields = [];

    public function __construct($ID,$label,$icon = null){
        $this->ID       = $ID;
        $this->label    = $label;
        $this->icon     = $icon;
    }

    public function set_field(Field $field){
        $fields = $this->fields;
        if($field instanceof Field){
            $name = $field->get_name();
            $fields[$name] =  $field;
        }
        $this->fields = $fields;
     }

    public function render_nav($current = false) : string {
        $id     = $this->ID;
        $label  = $this->label;
        $current = ($current) ? "current" : null;

        return "<li class='ob-nav-tab-item  {$current}'><a href='#ob-content-tab-{$id}' data-tab='ob-content-tab-{$id}'>{$label}</a></li>";
    }

    public function render_content($content ,$current = false) : string {
        $id     = $this->ID;
        $current = ($current) ? "current" : null;
        return "<div id='ob-content-tab-{$id}' class='ob-nav-tab-content {$current}'>{$content}</div>";
    }

}
