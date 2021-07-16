<?php

use OBSER\Classes\Helpers;
defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );


class OBSER_SHORTCODES{

    protected   static    $_shortcodes;
    private     static    $instance = [];
                static    $shortcodes_dir       = OBSER_FRAMEWORK_DIR_PATH .'Shortcodes/';
                static    $shortcodes_namespace = 'OBSER\Shortcodes';
    public static function instance() {
        $class = get_called_class();
        if(!isset(self::$instance[$class]) || !self::$instance[$class] instanceof $class){
            self::$instance[$class] = new static();
        }
        return  self::$instance[$class]; // remove this line after testing
    }

    function __construct() {
        add_filter( 'the_content'               ,   array( $this, 'paragraph_fix' ), 0 );
        add_filter( 'ccom_header_the_content'   ,   array( $this, 'paragraph_fix' ), 0 );
        add_filter( 'ccom_get_post_content'     ,   array( $this, 'paragraph_fix' ), 0 );
        
        add_filter( 'ccom_footer_the_content'   ,array( $this, 'paragraph_fix' ), 0 );
        add_action( 'save_post'                 ,array( $this, 'generate_css_on_save'),0,2);

        $this->load_shortcodes();
        $this->register_shortcodes();
    }

    protected  static function load_shortcodes($shortcodes_dir = null){
        $class              = get_called_class();
        $shortcodes_dir     = static::$shortcodes_dir;
        self::$_shortcodes  = self::read_folder($class,$shortcodes_dir);
    }


    protected static function read_folder($class,$shortcodes_dir,$subdir = null) {
        
        $_shortcodes = [];

        if(isset($subdir)){
            $shortcodes_dir .= "$subdir/"; 
        }

        $scan = array_diff(scandir($shortcodes_dir), array('..', '.'));


        foreach($scan as $file) {
            if(preg_match('/(?<filename>[\w\-\d]*)\.php$/',$file, $matches)){
                $class_name = $matches['filename'];
                $shortcodes_namespace = static::$shortcodes_namespace;
                $shortcode  =  !isset($subdir) ? "$shortcodes_namespace\\$class_name" : "$shortcodes_namespace\\$subdir\\$class_name";
                if(!class_exists($shortcode) || !isset($shortcode::$shortcode)) continue;

                $_shortcodes[$class][$shortcode::$shortcode] = $shortcode;

            }else if(preg_match('/(?<foldername>^[\w\-\d]*)$/',$file, $matches)){
                $foldername          = $matches['foldername'];
                $temps               = self::read_folder($class,$shortcodes_dir,$foldername);
                $_shortcodes[$class] = isset($_shortcodes[$class]) ? $_shortcodes[$class] : [];
                $_shortcodes[$class] = array_merge($_shortcodes[$class],$temps[$class]);
            }
        }

        return $_shortcodes;
    }

    function paragraph_fix( $content ) {
        $array = array(
            '<p>[' => '[',
            ']</p>' => ']',
            ']<br />' => ']',
            ']<br>' => ']',
        );

        $content = strtr( $content, $array );

        return $content;
    }

    static function get_shortcodes(){
        $class = get_called_class();
        return isset(self::$_shortcodes[$class]) ? self::$_shortcodes[$class] : null;
    }

    static function get_all_shortcodes(){
        $shortcodes = array();
        foreach(self::$_shortcodes as $_shortcode){
            $shortcodes = array_merge($shortcodes, $_shortcode);
        }
        return $shortcodes;
    }

    public function register_shortcodes(){
        $class = get_called_class();
        $shortcodes = (array) isset(self::$_shortcodes) && isset(self::$_shortcodes[$class]) ? self::$_shortcodes[$class] : array();

        foreach($shortcodes AS $shortcode){
            if($shortcode::$shortcode){
                add_shortcode($shortcode::$shortcode,array($this,$shortcode));

                add_action('wp_enqueue_scripts',function() use ($shortcode){
                    foreach((array)$shortcode::enquee_styles() AS $style => $data){
                        wp_register_style($style, $data['src']);
                    }
                });

                add_action('wp_enqueue_scripts',function() use ($shortcode){
                    foreach((array)$shortcode::enquee_scripts() AS $script => $data){
                        if(!isset($data['src'])) continue;
                        wp_register_script($script, $data['src'], $data['deps'], $data['in_footer']);
                    }
                    foreach((array)$shortcode::localize_script() AS $script => $data){
                        wp_localize_script($script, $data['object_name'],$data['l10n']);
                    }
                },100);
                $shortcode::after_register();
            }
        }
    }

    public function __call( $shortcode, $args ) {


        foreach((array)$shortcode::enquee_scripts() AS $script => $data){
            \wp_enqueue_script($script);
        }

        foreach((array)$shortcode::enquee_styles() AS $script => $data){
            \wp_enqueue_style($script);
        }
		$_output        = '';
		$shortcode_base = $shortcode::$shortcode;
		$atts           = isset( $args[0] ) ? (array)$args[0] : array();
		$content        = isset( $args[1] ) ? $args[1] : '';

        if(isset($shortcode::$is_grid_item) && $shortcode::$is_grid_item){
            add_filter( "vc_gitem_template_attribute_{$shortcode_base}", function ($value, $data ) use($shortcode) {
            extract(array_merge( array( 'post' => null,'data' => ''), $data ));
            $atts = array();
            parse_str( $data, $atts );
            $shortcode::set_atts($atts);
            return $shortcode::render($atts,$post);
            }, 10, 2 );
        }

		// Preserving VC before hook
		if ( substr( $shortcode_base, 0, 3 ) == 'vc_' AND defined( 'VC_SHORTCODE_BEFORE_CUSTOMIZE_PREFIX' ) ) {
			$custom_output_before = VC_SHORTCODE_BEFORE_CUSTOMIZE_PREFIX . $shortcode_base;
			if ( function_exists( $custom_output_before ) ) {
				$_output .= $custom_output_before( $atts, $content );
			}
			unset( $custom_output_before );
		}

        $attsString             = Helpers::string_atts((array)$atts);
        $design_css_class       = Helpers::get_design_css_class($attsString);
        $styles                 = (string) $shortcode::general_styles();
        Helpers::set_style($styles);
        $atts['vc_id']          = $design_css_class;
        $atts['uniqid']         = uniqid();
        $shortcode::buildAtts((array)$atts,$content);
		$_output .= $shortcode::output($atts,$content);
      

		// Preserving VC after hooks
		if ( substr( $shortcode_base, 0, 3 ) == 'vc_' ) {
			if ( defined( 'VC_SHORTCODE_AFTER_CUSTOMIZE_PREFIX' ) ) {
				$custom_output_after = VC_SHORTCODE_AFTER_CUSTOMIZE_PREFIX . $shortcode_base;
				if ( function_exists( $custom_output_after ) ) {
					$_output .= $custom_output_after( $atts, $content );
				}
			}
			$this->_settings = array(
				'base' => $shortcode_base,
			);

           

			$_output = apply_filters( 'vc_shortcode_output', $_output, $this, isset( $args[0] ) ? $args[0] : array(), $shortcode );
		}
        
		return "$_output";
	}


    public static function generate_css_on_save( $post_id, $post){
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        
        $ccom_shortcodes    = self::get_all_shortcodes();
        $ccom_custom_css    = NULL; // Default value, record checked but no data
    
        foreach($ccom_shortcodes AS $shortcode => $class ){
            if ( $post instanceof WP_Post AND ! empty( $post->post_content ) ) {
                
                $regex    = get_shortcode_regex( (array)$shortcode );
    
                if ( preg_match_all( "/$regex/" , $post->post_content, $matches, PREG_SET_ORDER) ) {
                    foreach($matches AS $match => $short){
                        if($atts = shortcode_parse_atts( $short[3] )){
                            $attsString        = Helpers::string_atts($atts);
                            $design_css_class  = Helpers::get_design_css_class($attsString);
                            $atts['vc_id']     = $design_css_class;
                            $class::buildAtts($atts,null);
                            $ccom_custom_css  .= $class::generate_css();
                        }
                    }
                }
            }
        }
        
        if($ccom_custom_css){
            $ccom_custom_css = Helpers::minify_css($ccom_custom_css);
            update_post_meta( $post->ID, '_obser_custom_css', $ccom_custom_css );
        }

        return $ccom_custom_css;

    }

}

add_action('wp_head',function(){
    global $post;
    $post_id        = null;
    $template_zone  = [];


    if (isset($post) && $post_id  = $post->ID) {
        $template_zone[] = $post_id;
    }

    if(function_exists('us_get_page_area_id')){
        $template_zone[] = us_get_page_area_id('header');
        $template_zone[] = us_get_page_area_id('content');
        $template_zone[] = us_get_page_area_id('footer');
    }



    $css = null;
    foreach( $template_zone AS $zone){
        $css   .= (isset($zone))? get_post_meta($zone,'_obser_custom_css',true) : null;
    }

    Helpers::set_style($css,"header");
    $styles     = Helpers::get_styles("header");
    echo $styles;
});


add_action('wp_footer',function(){
    $styles     = Helpers::get_styles("footer");
    echo $styles;
});

OBSER_SHORTCODES::instance();



