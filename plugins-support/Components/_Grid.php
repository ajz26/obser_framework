<?php
namespace OBSER\WPB_Components;
use OBSER\Classes\Component;
use function OBSER\Config\Grid\params;
use function OBSER\Config\Grid\get_listables_post_types;
use function OBSER\Config\Grid\get_vc_grid_items;



abstract class _Grid extends Component {

    public static function map(): array {
        return array(
            'name'                      => "Grid",
            'params' => array(
                array(
                    'type'                  => 'vc_grid_id',
                    'param_name'            => 'grid_id',
                ),
                array(
                    'type'              => 'checkbox',
                    'heading'           => esc_html__( 'Mostrar :', 'ccom' ),
                    'param_name'        => 'post_type',
                    'edit_field_class'  => 'vc_col-sm-6',
                    'value'             => get_listables_post_types(),
                    'save_always'       => true,
                    'admin_label'       => true,
                ),
                array(
                    'heading'               => esc_html__( 'Total items', 'js_composer' ),
                    'edit_field_class'      => 'vc_col-sm-6',
                    'type'                  => 'textfield',
                    'param_name'            => 'max_items',
                    'value'                 => -1,
                ),
                array(
                    'type'                  => 'dropdown',
                    'heading'               => esc_html__( 'Order by', 'js_composer' ),
                    'param_name'            => 'orderby',
                    'edit_field_class'      => 'vc_col-sm-6',
                    'value'                 => array(
                        esc_html__( 'Nombre ASC', 'ccom' )                  => 'post_title_asc',
                        esc_html__( 'Nombre DESC', 'ccom' )                 => 'post_title_desc',
                        esc_html__( 'Fecha de publicación DESC', 'ccom' )   => 'date_desc',
                        esc_html__( 'Fecha de publicación ASC', 'ccom' )    => 'date_asc',
                    ),
                    'save_always'       => true
                ),
                array(                  
                    'type'                  => 'dropdown',
                    "heading"               => __("Paginación", "ccom"),
                    "edit_field_class"      => "vc_col-xs-6",
                    "param_name"            => "show_pagination",
                    'save_always'           => true,
                    'dependency'            => array(
                        'element'           => 'max_items',
                        'value'             => array('-1'),
                    ),
                    'value'                 => array(
                        esc_html__( 'Ocultar', 'ccom' )         => 'hidden',
                        esc_html__( 'Solo arriba', 'ccom' )     => 'top',
                        esc_html__( 'Solo abajo', 'ccom' )      => 'bottom',
                        esc_html__( 'Arriba y abajo', 'all' )   => 'all',
                    ),
                ),
                array(
                    'heading'               => esc_html__( 'Elementos por página', 'ccom' ),
                    'type'                  => 'textfield',
                    'param_name'            => 'posts_per_page',
                    'edit_field_class'      => 'vc_col-sm-6',
                    'value'                 => 12,
                    'save_always'           => true,
                    'dependency'            => array(
                        'element'           => 'show_pagination',
                        'value'             => array('top','bottom','all'),
                    ),
                ),
                array(
                    'heading'               => esc_html__( 'Query Avanzada', 'js_composer' ),
                    'edit_field_class'      => 'vc_col-sm-12',
                    'type'                  => 'textfield',
                    'param_name'            => 'advanced_query',
                    'weight'                => 9000
                ),
                array(                  
                    "type"              => "separator",
                    'group'             => __("Opciones Responsive", "mx-plugin"),
                    "edit_field_class"  => "vc_col-xs-12",
                    "param_name"        => "separator",
                ),
                array(                  
                    "type"              => "separator",
                    "heading"           => __("Mostrar por defecto ", "mx-plugin"),
                    'group'             => __("Opciones Responsive", "mx-plugin"),
                    "edit_field_class"  => "vc_col-xs-6",
                    "param_name"        => "separator",
                ),
                array(
                    'heading'           => esc_html__( 'Mostrar', 'ccom' ),
                    'group'             => __("Opciones Responsive", "ccom"),
                    'type'              => 'dropdown',
                    'edit_field_class'  => 'vc_col-xs-6',
                    'param_name'        => 'element_width',
                    'value'             => params('items_per_row'),
                    'std'               => '4',
                    "save_always"       => true,

                ),
                array(                  
                    "type"              => "textfield",
                    "heading"           => __("Por debajo del ancho de la pantalla", "ccom"),
                    'group'             => __("Opciones Responsive", "ccom"),
                    "edit_field_class"  => "vc_col-xs-6",
                    "param_name"        => "mx_responsive_1",
                    "value"             => "1200px",
                    "save_always"       => true,
                ),
                array(
                    'heading'           => esc_html__( 'Mostrar', 'ccom' ),
                    'group'             => __("Opciones Responsive", "ccom"),
                    'type'              => 'dropdown',
                    'edit_field_class'  => 'vc_col-xs-6',
                    'param_name'        => 'mx_responsive_val_1',
                    'value'             => params('items_per_row'),
                    'std'               => '3',
                    "save_always"       => true,
                ),
                array(                  
                    "type"              => "textfield",
                    "heading"           => __("Por debajo del ancho de la pantalla", "ccom"),
                    'group'             => __("Opciones Responsive", "ccom"),
                    "edit_field_class"  => "vc_col-xs-6",
                    "param_name"        => "mx_responsive_2",
                    "value"             => "992px",
                    "save_always"       => true,
                ),
                array(
                    'heading'           => esc_html__( 'Mostrar', 'ccom' ),
                    'group'             => __("Opciones Responsive", "ccom"),
                    'type'              => 'dropdown',
                    'edit_field_class'  => 'vc_col-xs-6',
                    'param_name'        => 'mx_responsive_val_2',
                    'value'             => params('items_per_row'),
                    'std'               => '2',
                    "save_always"       => true,
                ),
                array(                  
                    "type"              => "textfield",
                    "heading"           => __("Por debajo del ancho de la pantalla", "ccom"),
                    'group'             => __("Opciones Responsive", "ccom"),
                    "edit_field_class"  => "vc_col-xs-6",
                    "param_name"        => "mx_responsive_3",
                    "value"             => "768px",
                    "save_always"       => true,
                ),
                array(
                    'heading'           => esc_html__( 'Mostrar', 'ccom' ),
                    'group'             => __("Opciones Responsive", "ccom"),
                    'type'              => 'dropdown',
                    'edit_field_class'  => 'vc_col-xs-6',
                    'param_name'        => 'mx_responsive_val_3',
                    'value'             => params('items_per_row'),
                    'std'               => '1',
                    "save_always"       => true,
                ),
                array(                  
                    "type"              => "separator",
                    'group'             => __( 'Apariencia', 'ccom' ),
                    "edit_field_class"  => "vc_col-xs-12",
                    "param_name"        => "separator",
                ),
                // array(
                //     'type'              => 'dropdown',
                //     'group'             => __( 'Apariencia', 'ccom' ),
                //     'heading'           => esc_html__( 'Diseño de cuadrícula', 'ccom' ),
                //     'param_name'        => 'item',
                //     'edit_field_class'  => 'vc_col-xs-6',
                //     "save_always"       => true,
                //     'value'             => get_vc_grid_items(),
                // ),
                // array(                  
                //     'group'             => __( 'Apariencia', 'ccom' ),
                //     'heading'           => __('Busqueda desierta', 'ccom'),
                //     'edit_field_class'  => 'vc_col-xs-6',
                //     'type'              => 'dropdown',
                //     'value'             => $templates,
                //     'param_name'        => 'template_busqueda_desierta',
                //     'save_always'       => true,
                // ),
                array(
                    'group'             => __( 'Apariencia', 'ccom' ),
                    'heading'           => esc_html__( 'Espacio lateral entre elementos', 'ccom' ),
                    'type'              => 'textfield',
                    'param_name'        => 'items_gap',
                    'save_always'       => true,
                    'edit_field_class'  => 'vc_col-sm-6',
                    'std'               => '10',
                ),
                array(
                    'heading'           => esc_html__('ID', 'ccom' ),
                    'group'             => esc_html__( 'Apariencia', 'ccom' ),
                    'edit_field_class'  => 'vc_col-sm-6',
                    'type'              => 'textfield',
                    'param_name'        => 'el_id',
                ),
                array(
                    'heading'           => esc_html__('Extra Class', 'ccom' ),
                    'group'             => esc_html__( 'Apariencia', 'ccom' ),
                    'edit_field_class'  => 'vc_col-sm-12',
                    'type'              => 'textfield',
                    'param_name'        => 'el_class',
                )
            )               
        );
    }

}
