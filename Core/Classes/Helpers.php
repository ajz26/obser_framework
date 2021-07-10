<?php
namespace OBSER\Classes;

use stdClass;

class Helpers {


    protected static $_styles   = [];
    protected static $_scripts  = [];
    protected static $_posts    = [];
    protected static $_data     = [];


    static function saludar(){
        return 'holis';
    }

    static function array_to_object(array $array){
        
        $object = new stdClass();

        foreach($array AS $key => $val){
            $object->$key = !is_array($val) ? $val : Helpers::array_to_object($val);
        }

        return $object;

    }


    public static function get_styles($position = "footer", bool $in_string = true) {
        $styles = isset(self::$_styles[$position]) ? self::$_styles[$position] : array();
        return  ($in_string) ? self::generate_string($styles , "style") : $styles;
    }
    
    public static function set_style(string $style = null, $position = "footer") : void{
    self::$_styles[$position][] = $style;
    }

    public static function set_data(string $key = "" ,string $data = null) : void{
        self::$_data[$key][] = $data;
    }


    public static function get_data($key) {
        return isset(self::$_data[$key]) ? self::$_data[$key] : null;
    }

    public static function generate_string( array &$array , $embbed = "") : string{
        $string = "";

        foreach($array AS $str){
            $string .= "$str \n"  ;
        }

        switch($embbed) {
            case "style":
            $string = "<style>".self::minify_css($string)."</style>";
            break;

            case "script":
                $string = "<script>{$string}</script>";
            break;
        }
        return $string;
    }


    static function string_atts(array $array) : string{
        return \json_encode($array);
    }

    static function get_design_css_class( $str, $class_name = 'obser_custom' ) {
		if ( ! empty( $str ) AND ! empty( $class_name ) ) {
			return $class_name . '_' . hash( 'crc32b', $str );
		}

		return '';
	}

    static function minify_css($css){
		$from   = array(
			'%/\*(?:(?!\*/).)*\*/%s',       // comments:  /*...*/
			'/\s{2,}/',                     // extra spaces
			"/\s*([;{}])[\r\n\t\s]/",       // new lines
			'/\\s*;\\s*/',                  // white space (ws) between ;
			'/\\s*{\\s*/',                  // remove ws around {
			'/;?\\s*}\\s*/',                // remove ws around } and last semicolon in declaration block
		);
		$to     = array(
			'',
			' ',
			'$1',
			';',
			'{',
			'}',
		);
		return preg_replace($from,$to,$css);
	}

    static function merge_advanced_atts($atts , $new_atts){


        array_walk($atts,function(&$att,$key) use ($new_atts){
            $type       = \gettype($att);
            $type       = ($type == 'string' && $att == 'NULL') ? NULL : $type;

            if(array_key_exists($key,$new_atts)){
                switch($type){
                    case 'string':
                    case 'int':
                        $att = $new_atts[$key];
                    break;
                    case 'array':
                        $att = $new_atts[$key];
                    break;
                }
            };
        });

        return $atts;
    }

    static function stripslashes_deep($value){
       $value = str_replace(array('\n','\r'),'',$value);

        $value = is_array($value) ?
                    array_map(array(__CLASS__,'stripslashes_deep'), $value) :
                    stripslashes($value);

        return $value;
    }


    static function get_id_from_guid( $guid ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid ) );
    
    }

    
static function upload_external_media($raw_urls) {

	$urls = array();
    $raw_urls = is_array($raw_urls) ? $raw_urls : (array)$raw_urls;
    $attachment_ids = [];
    $failed_urls    = [];
	foreach ( $raw_urls as $i => $raw_url ) {
		$urls[$i] = esc_url_raw( trim( $raw_url ) );
	}

	foreach ( $urls as $url ) {
   
        $image_size = @getimagesize( $url );
        if ( empty( $image_size ) ) {
            array_push( $failed_urls, $url );
            continue;
        }

        if(!$attachment_id  = self::get_id_from_guid( $url )){
            $width_of_the_image         =   $image_size[0];
            $height_of_the_image        =   $image_size[1];
            $response                   =   wp_remote_head( $url );

            if ( is_array( $response ) && isset( $response['headers']['content-type'] ) ) {
                $mime_type_of_the_image = $response['headers']['content-type'];
            } else {
                continue;
            }
            
            $filename   = wp_basename( $url );
            $attachment = array(
                'guid'              => $url,
                'post_mime_type'    => $mime_type_of_the_image,
                'post_title'        => preg_replace( '/\.[^.]+$/', '', $filename ),
            );
            $attachment_metadata = array(
                'width'     => $width_of_the_image,
                'height'    => $height_of_the_image,
                'file'      => $filename
            );
            $attachment_metadata['sizes']   = array( 'full' => $attachment_metadata );
            $attachment_id                  = wp_insert_attachment( $attachment );

            wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
        
        }

        

		array_push( $attachment_ids, $attachment_id );
	}

	$info['attachment_ids'] = $attachment_ids;
	
    
    $failed_urls_string     = implode( "\n", $failed_urls );
	$info['urls']           = $failed_urls_string;

	if ( ! empty( $failed_urls_string ) ) {
		$info['error'] = 'Failed to get info of the image(s).';
	}
    
	return (count($info['attachment_ids']) == 1) ? $info['attachment_ids'][0] : $info['attachment_ids'];
}


    static function set_term_by_slug($post,$value,$taxonomy){

        $term       = get_term_by('slug', $value ,$taxonomy);

        $term_id    = isset($term->term_id) ? $term->term_id : null;
        
        if(!$term_id){
           $term = wp_insert_term( $value, $taxonomy);
           if ( !is_wp_error( $term ) ) {
                $term_id = $term['term_id'];
            }
        }
        try {
            wp_set_object_terms($post->ID,intval($term_id), $taxonomy);
        } catch (\WP_Error $error) {
            error_log($error->get_error_messages());
        }
    }


}