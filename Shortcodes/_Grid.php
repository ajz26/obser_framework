<?php

namespace OBSER\Shortcodes;
use OBSER\Classes\Shortcode;

use OBSER\Classes\Helpers;
use function OBSER\Config\Grid\grid_styles;

if (function_exists('vc_path_dir')) {
    require_once vc_path_dir('SHORTCODES_DIR', 'vc-basic-grid.php');
}
class _Grid extends Shortcode{

    static $items;
    static $post_id;
    static $WP_Query;
    static $count_posts;
    static $grid_settings;
    static $attributes_defaults;
    static $element_template;
    static $is_end;
    static $grid_item;
    static $filter_terms;
    // static $shortcode = "obser_grid";
    protected static $grid_id_unique_name = 'vc_gid';

    protected static function postID()
    {
        if (!self::$post_id && is_page(  )) {
            self::$post_id  = get_the_ID();
        }else if(!self::$post_id && is_archive(  )){
            
            $term_id        = get_queried_object()->term_id;
            $taxonomy       = get_queried_object()->taxonomy;
            self::$post_id  = us_get_option("content_tax_{$taxonomy}_id")  !== '__defaults__'  ?:  us_get_option('content_archive_id') ?: get_term_meta( $term_id, 'archive_content_id' ,true);

        }
        return self::$post_id;
    }

    public static function buildGridSettings()
    {
        $shortcode = static::$shortcode;

        self::$grid_settings = array_merge((array)self::$grid_settings,array(
            'page_id' => self::get_atts('page_id'),
            'action'  => "get_{$shortcode}_data",
            'tag'     => $shortcode
        ));


        if($term = get_queried_object()){
            self::$grid_settings = self::$grid_settings ?: array();

            self::$grid_settings['filters'] = isset(self::$grid_settings['filters'])?: array();
            self::$grid_settings['filters'] = array_merge((array)self::$grid_settings['filters'],array(
                $term->taxonomy => $term->slug
            ));

            $filters = self::get_atts('filters',array());

            $filters = array_merge($filters,self::$grid_settings['filters']);
            self::set_att('filters',$filters);

        }

        if (self::get_atts('shortcode_id') !== null) {
            self::$grid_settings['shortcode_id'] = self::get_atts('shortcode_id');
        }
    }

    public static function after_register(){

        $shortcode  = static::$shortcode;
        $class      = \get_called_class();
        add_action("wp_ajax_get_{$shortcode}_data", array($class, 'extract_data'));
        add_action("wp_ajax_nopriv_get_{$shortcode}_data", array($class, 'extract_data'));
    }

    public static function extract_data(){
        $tag                = str_replace('.', '', vc_request_param('tag'));
        $shortcode_fishbone = visual_composer()->getShortCode($tag);
        if (is_object($shortcode_fishbone) && vc_get_shortcode($tag)) {
            $res                = static::renderAjax(vc_request_param('data'));
            $res                = apply_filters( 'vc_get_vc_grid_data_response',  $res);		
            wp_die($res);
        }

        wp_die($shortcode_fishbone);
    }


    public static function findPostShortcodeById($page_id, $grid_id)
    {
        
        if (preg_match('/\"tag\"\:/', urldecode($grid_id))) {
            return json_decode(urldecode($grid_id), true);
        }

        $post_meta = get_post_meta((int) $page_id, '_vc_post_settings');

        $shortcode = false;
        if (is_array($post_meta)) {
            foreach ($post_meta as $meta) {
                if (isset($meta['vc_grid_id']) && !empty($meta['vc_grid_id']['shortcodes']) && isset($meta['vc_grid_id']['shortcodes'][$grid_id])) {
                    $shortcode = $meta['vc_grid_id']['shortcodes'][$grid_id];
                    break;
                }
            }
        }

        $test = apply_filters('vc_basic_grid_find_post_shortcode', $shortcode, $page_id, $grid_id);
        return $test; 
    }

    public static function renderAjax($vc_request_param)
    {   


        self::$items        = array();
        $page_id            = isset($vc_request_param['page_id'])         ? $vc_request_param['page_id']          : null;
        $id                 = isset($vc_request_param['shortcode_id'])    ? $vc_request_param['shortcode_id']     : false;
        $posts_per_page     = isset($vc_request_param['posts_per_page'])  ? $vc_request_param['posts_per_page']   : null;


        if (!isset($page_id)) {
            return;
        }

        $shortcode = (isset($id)) ? self::findPostShortcodeById($page_id, $id) : null;


        if (!is_array($shortcode)) return;
        

        \visual_composer()->registerAdminCss();
        \visual_composer()->registerAdminJavascript();

        self::$post_id = (int) $page_id;

        $shortcode_atts     = $shortcode['atts'];

        foreach($vc_request_param AS $param => $data){
            if($param && $data){
                $shortcode_atts[$param] = $data;
            }
        }
        static::buildAtts($shortcode_atts, $shortcode['content']);
                static::buildItems();
        return  static::renderItems();
    }


    public static function getId($atts, $content)
    {

        if (vc_is_page_editable() || is_preview()) {
            return rawurlencode(wp_json_encode(array(
                'tag'       => self::$shortcode,
                'atts'      => $atts,
                'content'   => $content,
            )));
        }

        $id_pattern = '/' . self::$grid_id_unique_name . '\:([\w\-_]+)/';
        $id_value   = isset($atts['grid_id']) ? $atts['grid_id'] : '';

        preg_match($id_pattern, $id_value, $id_matches);
        $id_to_save = wp_json_encode(array('failed_to_get_id' => esc_attr($id_value)));

        if (!empty($id_matches)) {
            $id_to_save = $id_matches[1];
        }

        return $id_to_save;
    }

    static function set_default_atts(){
        static::$attributes_defaults          = array(
            'el_id'                         => '',
            'advanced_query'                => '',
            'items_gap'                     => 20,
            'el_class'                      => '',
            'vc_id'                         => '',
            'show_filter'                   => '',
            'orderby'                       => 'date',
            'filter_source'                 => 'category',
            'element_width'                 => 4,
            'order'                         => 'DESC',
            'item'                          => null,
            'filters'                       => array(),
            'offset'                        =>  0,
            'max_items'                     => -1,
            'posts_per_page'                => -1,
            'paged'                         => '',
            'show_pagination'               => 'hidden',
            'mx_responsive_1'               => '',
            'mx_responsive_val_1'           => '',
            'mx_responsive_2'               => '',
            'mx_responsive_val_2'           => '',
            'mx_responsive_3'               => '',
            'mx_responsive_val_3'           => '',
            'not_results_page_block'        => '',
            'post__in'                      => '',
            'post_type'                     => '',
            'show_first'                    => '',
            'cclass'                        => 'obser-grid-item'
        );
    }

    static function buildAtts($atts = array(), $content = null){
        static::set_default_atts();

        self::$items                        = array();
        self::$post_id                      = null;
        self::$grid_settings                = array();
        
        $id_to_save = null;
        $arr_keys   = array_keys($atts);
        $count      = count($atts);

        for ($i = 0; $i < $count; $i++) {
            $atts[$arr_keys[$i]] = (!is_array($atts[$arr_keys[$i]])) ?  html_entity_decode($atts[$arr_keys[$i]], ENT_QUOTES, 'utf-8') : $atts[$arr_keys[$i]];
        }


        if (isset($atts['grid_id']) && !empty($atts['grid_id'])) {
            $id_to_save = self::getId($atts, $content);
        }

        $atts           = shortcode_atts(static::$attributes_defaults, $atts);
        self::set_atts($atts);
        if (isset($id_to_save)) {
            self::set_att('shortcode_id', $id_to_save);
        }


        $post_types = self::get_atts('post_type');
        self::set_att('post_type', \explode(',', $post_types));

        $max_items      = self::get_atts('max_items');
        $posts_per_page = self::get_atts('posts_per_page');

        if($posts_per_page == -1 && $max_items !== -1){
            self::set_att('posts_per_page', $max_items);
        }

        self::set_att('page_id', self::postID());
        $item = self::get_atts('item');

        self::set_att('item', $item);
        self::$element_template = $content;


        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            $cookie       = self::get_cookie_data($id_to_save);
            if (isset($cookie['paged'])) {
                self::set_att('paged', $cookie['paged']);
            }
        }

        $advanced_query         = self::get_atts('advanced_query');
        if($advanced_query){
            $advanced_query_args    = [];
            $advanced_query         = str_replace(array("`{``}`","`{`","`}`"),array('[]','[',']'),$advanced_query);
            parse_str($advanced_query,$advanced_query_args);
            $atts = self::get_atts();
            $atts = Helpers::merge_advanced_atts($atts,$advanced_query_args);
            

            self::set_atts($atts);

        }

        
    }

    protected static function get_default_att($att = null)
    {
        return isset($att) ? (isset(self::$attributes_defaults[$att]) ? self::$attributes_defaults[$att] : null) : self::$attributes_defaults;
    }

    protected static function set_custom_content_limits()
    {

        $atts = self::get_atts();

        if (self::get_atts('offset') == null) {
            $offset = self::get_default_att('offset');
            self::set_att('offset', $offset);
        }

        if (self::get_atts('max_items') == null) {
            $max_items = self::get_default_att('max_items');
            self::set_att('max_items', $max_items);
        }

        if (self::get_atts('posts_per_page') == null) {
            $posts_per_page = self::get_default_att('posts_per_page');
            self::set_att('posts_per_page', $posts_per_page);
        }


        if (self::get_atts('max_items') <= 1) {
            $max_items = self::get_default_att('max_items');
            self::set_att('max_items', apply_filters('vc_basic_grid_max_items', $max_items));
        }

        self::setPagingAll(self::get_atts('max_items'));
    }

    protected static function setPagingAll($max_items)
    {
        self::set_att('query_posts_per_page', self::get_atts('posts_per_page'));
        self::set_att('query_offset', self::get_atts('offset'));
    }


    public static function filterQuerySettings($args)
    {

        $parsed = wp_parse_args($args, array(
            'numberposts'           => 12,
            'orderby'               => 'date',
            'order'                 => 'DESC',
            'post_type'             => 'any',
            'public'                => true,
            'ignore_sticky_posts'   => true,
            'no_found_rows'         => false,
        ));

        $parsed['posts_per_page'] = !empty($parsed['posts_per_page']) ? $parsed['posts_per_page'] : $parsed['numberposts'];
        return $parsed;
    }

    static function buildQuery(array $atts)
    {
        $meta_query = array('');

        $settings = array(
            'posts_per_page'    => $atts['query_posts_per_page'],
            'orderby'           => $atts['orderby'],
            'meta_query'        => $meta_query,
            'post_type'         => $atts['post_type'],
            'post_status'       => 'publish',
            'paged'             => $atts['paged'],
        );

        return $settings;
    }

    public static function buildItems()
    {

        self::$WP_Query = new \WP_Query();
        self::set_custom_content_limits();

        $atts       = static::get_atts();
        $args       = static::buildQuery($atts);
        $settings   = static::filterQuerySettings($args);

       
        $post_data      = self::$WP_Query->query($settings);
        if (is_object(self::$WP_Query)) {
            self::$count_posts =  self::$WP_Query->found_posts;
        }
        $posts_per_page = self::get_atts('posts_per_page');

        if ($posts_per_page > 0 && count($post_data) > $posts_per_page) {
            $post_data = array_slice($post_data, 0, $posts_per_page);
        }

        foreach ($post_data as $post) {
            $post->filter_terms = wp_get_object_terms($post->ID, self::get_atts('filter_source'), array('fields' => 'ids'));
            self::$filter_terms = wp_parse_args(self::$filter_terms, $post->filter_terms);
            self::$items[] = $post;
        }
    }


    public static function renderItems()
    {

        $output         = $items = '';
        $filter_terms   = self::$filter_terms;
        $atts           = self::get_atts();
        $settings       = self::$grid_settings;
        $is_end         = isset(self::$is_end) && self::$is_end;

        $currentScope = \WPBMap::getScope();

        if (is_array(self::$items) && !empty(self::$items)) {
            \WPBMap::setScope(\Vc_Grid_Item_Editor::postType());
            require_once vc_path_dir('PARAMS_DIR', 'vc_grid_item/class-vc-grid-item.php');
            self::$grid_item = new \Vc_Grid_Item();
            self::$grid_item->setGridAttributes($atts);
            self::$grid_item->setIsEnd($is_end);
            self::$grid_item->setTemplateById($atts['item']);

            $output .= self::$grid_item->addShortcodesCustomCss();

            ob_start();
            // if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) wp_print_styles();
            $output .= ob_get_clean();


            $attributes = array(
                'filter_terms' => $filter_terms,
                'atts'          => $atts,
                'grid_item',
                self::$grid_item,
            );
            $output .= apply_filters('vc_basic_grid_template_filter', vc_get_template('shortcodes/vc_basic_grid_filter.php', $attributes), $attributes);

            global $post;
            $backup = $post;
            foreach (self::$items as $postItem) {
                self::$WP_Query->setup_postdata($postItem);
                $post           = $postItem;
                $mx_item        = self::$grid_item->renderItem($postItem);
                $cclass         = self::get_atts('cclass');
                $items         .= preg_replace('/(?!vc_grid-item ) vc_col.\w{2}.\d{1,2}/'," {$cclass} ", $mx_item);
            }
            wp_reset_postdata();
            $post = $backup;
        } else {
            $shortcode_id               = self::get_atts('shortcode_id');
            $not_results_page_block_id  = self::get_atts('not_results_page_block');
            \WPBMap::addAllMappedShortcodes();

            if($not_results_page_block_id){
                $pb      = get_the_content($not_results_page_block_id);
                $output .= do_shortcode($pb);
            }
 

            
            $output .= "
            <script>
                jQuery(document).ready(function () {
                    obser_grid.delete_cookie('{$shortcode_id}')
                });
            </script>";
        }
        if ($items != "") {
            $output     .= self::renderPagination($settings, $items);
        }

        \WPBMap::setScope($currentScope);
        return $output;
    }


    public static function renderPagination($settings, $content = '', $css_classes = '')
    {
        $output             = '';
        $atts               = self::get_atts();
        $paged              = (isset($atts['paged'])                && !empty($atts['paged']))                                                      ? $atts['paged']                : 1;
        $show_pagination    = (isset($atts['show_pagination'])      && !empty($atts['show_pagination']) &&  $atts['show_pagination'] == 'hidden')     ? false : $atts['show_pagination'];

        if ($show_pagination) {

            $orderby            = $atts["orderby"];
            $posts_per_page     = (int)$atts['posts_per_page'];
            $pages              = $pages_html = "";
            $max_post_per_page  = ($posts_per_page * 4);
            $items_pp_html      = null;
            $current_page_html  = null;
            $items_founds       = null;
            $opt_ipp            = $posts_per_page;
            $max_num_pages      = self::$WP_Query->max_num_pages;
            $found_posts        = self::$WP_Query->found_posts;
            $n_pages            = ceil($found_posts / $posts_per_page);
            $count_posts        = self::$count_posts;
            while ($opt_ipp <= $max_post_per_page) {
                $selected = ($posts_per_page == $opt_ipp) ? "selected='selected'" : null;
                $items_pp_html .= "<option value='$opt_ipp' {$selected}>$opt_ipp</option>";
                $opt_ipp = $opt_ipp + $posts_per_page;
            }

            if ($items_pp_html) {
                $items_pp_html = "<select>{$items_pp_html}</select>";
            }


            for ($p = 1; $p <= $n_pages; $p++) {
                $selected = ($paged == $p) ? "selected" : null;
                $pages_html .= "<span data-page='$p' class='{$selected}'>$p</span>";
            }

            if ($pages_html) {
                $pages_html = "<div class='current-page-index'>$pages_html</div>";
            }


            $current_page_html .= '<div class="grid-nav-info text-center current-index-pages">';
            $current_page_html .= sprintf(__('Página %d de %s'), $paged, $n_pages);
            $current_page_html .= '</div>';

            $items_founds .= '<div class="grid-nav-info text-center items-founds">';
            $items_founds .= sprintf(_n( '%s resultado encontrado.', '%s resultados encontrados.', $count_posts, 'obser' ), number_format_i18n( $count_posts ) );
            
            $items_founds .= '</div>';


            $prev_link = $next_link = "";
            $prev_link_text = __('Anterior', 'obser');
            $next_link_text = __('Siguiente', 'obser');

            $prev_link = ($paged > 1) ?
                sprintf(__("<div class='prev-next-link-container prev-btn prev-next-link prev-btn' data-next_page='%d'>
                            <i class=\"far fa-angle-left\"></i>
                            <span class='prev-next-link-text' >%s</span></div>", 'obser'), ((int)$paged - 1), $prev_link_text, 1) : "<div class='prev-next-link-container prev-btn prev-next-off'><i class=\"far fa-angle-left\"></i><span class='prev-next-link-text'>{$prev_link_text}</span></div>";


            $next_link = (($paged < $n_pages)) ?  sprintf(__("<div class='prev-next-link-container prev-next-link next-btn' data-next_page='%d'><span class='prev-next-link-text' >%s</span><i class=\"far fa-angle-right\"></i></div>", 'obser'), ((int)$paged + 1), $next_link_text) : "<div class='prev-next-link-container prev-next-link next-btn prev-next-off'><span class='prev-next-link-text'>{$next_link_text}</span><i class=\"far fa-angle-right\"></i></div>";
        }




        $html_top       = (\in_array($show_pagination, array('top', 'all')) && $n_pages >= 1) ? "<div class='grid-nav grid-top'>{$prev_link}{$next_link} {$items_founds}</div>" : null;
        $html_bottom    = (\in_array($show_pagination, array('bottom', 'all'))  && $n_pages >= 1 ) ? "<div class='grid-nav grid-bottom'>{$prev_link}{$next_link} {$current_page_html}</div>" : null;

        $output     .= "
                        {$html_top}
                        {$content}
                        {$html_bottom}";



        return $output;
    }
    protected static function parse_json_data(string $json)
    {
        $json = \str_replace("``", '"', $json);
        return (array)json_decode($json);
    }


    public static function generate_css()
    {   
        $style  = '';
        $atts                       = self::get_atts();
        $parent_id                  = self::get_atts('vc_id');
        $element_width              = (int)self::get_atts('element_width', 4);
        $mx_responsive_1            = (int)self::get_atts('mx_responsive_1', 1200);
        $mx_responsive_val_1        = (int)self::get_atts('mx_responsive_val_1', $element_width);

        $mx_responsive_2            = (int)self::get_atts('mx_responsive_2', 1200);
        $mx_responsive_val_2        = (int)self::get_atts('mx_responsive_val_2', $mx_responsive_val_1);

        $mx_responsive_3            = (int)self::get_atts('mx_responsive_3', 1200);
        $mx_responsive_val_3        = (int)self::get_atts('mx_responsive_val_3', $mx_responsive_val_2);
        $items_gap                  = (int)self::get_atts('items_gap', 10);

        $style .= "
        .{$parent_id} .obser-custom-grid-items {
            margin-left: calc(({$items_gap}px / 2) * (-1));
            margin-right: calc(({$items_gap}px / 2) * (-1));
        }
        .{$parent_id} .obser-custom-grid-items .grid-nav ,
        .{$parent_id} .obser-custom-grid-items .obser-grid-item {
            padding-left: calc({$items_gap}px / 2);
            padding-right: calc({$items_gap}px / 2);
        }
        .{$parent_id} .obser-grid-item {
            width: calc(100% / {$element_width});
        }
        ";
        if (!is_null($mx_responsive_1) && $mx_responsive_val_1 > 0) {
            $style .= "@media only screen and (max-width: {$mx_responsive_1}px) {
                .{$parent_id} .obser-grid-item{
                    width: calc(100% / {$mx_responsive_val_1});
                } 
            }";
        }
        if (!is_null($mx_responsive_2) && $mx_responsive_val_2 > 0) {
            $style .= "@media only screen and (max-width: {$mx_responsive_2}px) {
                .{$parent_id} .obser-grid-item{
                    width: calc(100% / {$mx_responsive_val_2});
                }
            }";
        }
        if (!is_null($mx_responsive_3) && $mx_responsive_val_3 > 0) {
            $style .= "@media only screen and (max-width: {$mx_responsive_3}px) {
                .{$parent_id} .obser-grid-item{
                    width: calc(100% / {$mx_responsive_val_3});
                }
            }";
        }

        return $style;
    }


    public static function enquee_scripts()
    {
        return array(
            'obser_grid' => array(
                'src'   => OBSER_FRAMEWORK_DIR_URL . '/assets/js/grid.js',
                'deps'  => array('jquery'),
                'in_footer'   => false,
            ),
        );
    }

    public static function localize_script()
    {
        return array(
            'obser_grid' => array(
                'object_name'   => 'obser_core',
                'l10n'          => array('ajax_url' => admin_url('admin-ajax.php')),
            ),
        );
    }


    protected static function get_cookie_data($shortcode_id)
    {
        $cookie = isset($_COOKIE["wordpress_grid__{$shortcode_id}"]) ? $_COOKIE["wordpress_grid__{$shortcode_id}"] : null;

        if ($cookie) {
            $cookie = stripslashes($cookie);
            $cookie = json_decode($cookie, true);

            return $cookie;
        }

        return null;
    }



    public static function output($atts, $content)
    {
        $element_id     = self::get_atts('vc_id');
        $atts           = self::get_atts();
        $shortcode_id   = self::get_atts('shortcode_id');

        self::buildItems();

        $id                 = self::get_atts('el_id');
        $post_type          = esc_attr(implode(" obser-grid-", self::get_atts('post_type')));
        $current_page_id    =  esc_attr(get_the_ID());
        $el_class           = esc_attr(self::get_atts('el_class'));
        $el_nonce           = esc_attr(vc_generate_nonce('vc-public-nonce'));
        $items              = self::renderItems();
        $json_data          = esc_attr(wp_json_encode(self::$grid_settings));

        $output  = "
        <div  id='{$id}' class='contenedor-obser-list contenedor-obser-grid {$el_class} obser-grid-{$post_type} {$element_id}' data-shortcode_id='$shortcode_id' data-obser-grid-settings='$json_data' data-vc-post-id='{$current_page_id}' data-vc-public-nonce='{$el_nonce}'data-vc-post-id='{$current_page_id}' data-vc-public-nonce='{$el_nonce}'>
            <div class='obser-custom-preloader d-none'></div>
            <div class='obser-custom-grid-items d-flex flex-wrap'>
                {$items}
            </div>        
        </div>";
        return $output;
    }

    public static function general_styles()
    {
        // $nav_color                  = HelpersTheme::get_option('body_color',false,'rgba(52,52,52,1)');
        // $nav_color                  = (is_array($nav_color) && array_key_exists('rgba',$nav_color))? $nav_color['rgba'] : $nav_color['color'];
        // $nav_color_hover            = HelpersTheme::get_option('sch01_main',false,'rgba(153,153,153,1)');
        // $nav_color_hover            = (is_array($nav_color_hover) && array_key_exists('rgba',$nav_color_hover))? $nav_color_hover['rgba'] : $nav_color_hover['color'];

        $general_styles = "

        .prev-next-link-container {
            opacity: 1;
            visibility: visible;
        }

        .prev-next-link-container.prev-next-off {
            opacity: 0;
            visibility: hidden;
        }

        .prev-next-link {
            align-items: center;
        }
        .prev-next-link:not(.prev-next-off):hover {
        }
        .prev-link .prev-next-link:before ,
        .next-link .prev-next-link:after {
        }
        .prev-link .prev-next-link:not(.prev-next-off):hover:before ,
        .next-link .prev-next-link:not(.prev-next-off):hover:after {
        }

        
        ";
        $general_styles .= grid_styles();

        return $general_styles;
    }
}
