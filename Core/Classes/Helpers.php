<?php
namespace OBSER\Classes;

class Helpers {


    protected static $_styles   = [];
    protected static $_scripts  = [];
    protected static $_posts    = [];
    protected static $_data     = [];


    static function saludar(){
        return 'holis';
    }


    public static function get_styles($position = "footer", bool $in_string = true) {
        $styles = self::$_styles[$position];
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
}