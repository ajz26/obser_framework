<?php 
namespace OBSER\Config\Grid {
    use OBSER\Classes\Helpers;

    function get_vc_grid_items(){

        $data = [];

            // $vc_grid_items = Helpers::get_posts('vc_grid_item');
            
            // foreach($vc_grid_items as $header){
            //     $label = $header->post_title;
            //     $value = json_encode(array(
            //             'ID'        => $header->ID,
            //             'slug'      => $header->post_name,
            //             'blog_id'   => $header->blog_id,
            //         ));
            //     $data[$label] = $value;
            // }

        return $data;
    }
    
    function get_searchable_post_types(){
        return array();
        return array(
            'Tiendas'       =>  'ccom_stores',
            'Restaurantes'  =>  'ccom_restaurants'
        );
    }

    function get_listables_post_types(){
        
        $post_types = get_post_types();

        return $post_types;
    }

    function params($key){
        $settings = array(
            'items_per_row' => array(
                array(
                    'label' => '1 Item',
                    'value' => 1,
                ),            
                array(
                    'label' => '2 Items',
                    'value' => 2,
                ),
                array(
                    'label' => '3 Items',
                    'value' => 3,
                ),
                array(
                    'label' => '4 Items',
                    'value' => 4,
                ),
                array(
                    'label' => '5 Items',
                    'value' => 5,
                ),
                array(
                    'label' => '6 Items',
                    'value' => 6,
                ),
            ),
        );
        return isset($settings[$key]) ? $settings[$key] : null ;
    }

    function grid_styles(){

        $grid_styles = "";

        return $grid_styles;
    }
}

