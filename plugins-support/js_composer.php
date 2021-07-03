<?php


add_action( 'init', function () {
	
	if ( ! function_exists( 'vc_mode' ) OR ! function_exists( 'vc_map' ) OR ! function_exists( 'vc_remove_element' ) ) {
		return;
	}

	vc_add_shortcode_param( 'hidden', function ( $settings, $value ) {
		return '<div class="my_param_block">'
		.'<input name="' . esc_attr( $settings['param_name'] ) . '" class="wpb_vc_param_value wpb-textinput ' .
		esc_attr( $settings['param_name'] ) . ' ' .
		esc_attr( $settings['type'] ) . '_field" type="hidden" value="' . esc_attr( $value ) . '" />' .
		'</div>';
	});
	

	global $pagenow;

	$components =  OBSER_SHORTCODES::get_shortcodes() ?: array();
	
	$is_edit_vc_roles = (
		$pagenow === 'admin.php'
		AND $_GET["page"] === 'vc-roles'
	);



	$vc_lean_wpb_ccom_comp = function($component) use($is_edit_vc_roles){

		$shortcode 	= $component::$shortcode;
		$elm 		= $component::get_component();
		


		if(!class_exists($elm)) return false;
		$map 		= $elm::map();


		$vc_elm = array_merge(array(
			'name' 						=> $shortcode,
			'base' 						=> $shortcode,
			'class' 					=> 'elm-' . $shortcode,
			'category'					=> 'Obser',
			'show_settings_on_create'	=> false,
		),$map);

		vc_map( $vc_elm );

		if(isset($vc_elm['is_container']) && $vc_elm['is_container'] == true &&  class_exists( 'WPBakeryShortCodesContainer' ) && !class_exists("WPBakeryShortCode_{$shortcode}")){
			eval(sprintf('class %s extends WPBakeryShortCodesContainer {}', "WPBakeryShortCode_{$shortcode}"));						
		}

		if ( $is_edit_vc_roles ) {
			vc_lean_map(  $shortcode, function() use( $vc_elm ) {
				return $vc_elm;
			} );
		}

	};


	$vc_lean_wpb_grid_ccom_comp = function($component) use($is_edit_vc_roles){

		$shortcode 	= $component::$shortcode;
		$elm 		= $component::get_component();
		if(!class_exists($elm)) return false;

		add_filter( 'vc_grid_item_shortcodes', function($shortcodes) use ($shortcode,$elm){
			$map 		= $elm::map();
			$vc_elm = array_merge(array(
				'name' 						=> $shortcode,
				'base' 						=> $shortcode,
				'post_type' 				=> \Vc_Grid_Item_Editor::postType(),

			),$map);
			$shortcodes[$shortcode] = $vc_elm;

			return $shortcodes;
		},0);

	};

	// Mapping WPBakery Page Builder backend behaviour for used shortcodes
	if ( vc_mode() != 'page' ) {



		/**
		 * If the page for editing roles then the result will be TRUE
		 * @var bool
		 */
		

		// // Receive data only on the edit page or create a record

		foreach ( $components as $component ) {
			if(isset($component::$is_grid_item) && $component::$is_grid_item){
				$vc_lean_wpb_grid_ccom_comp($component);
			}else if(
				wp_doing_ajax()
				OR in_array( $pagenow, array( 'post.php', 'post-new.php' ) )
				OR vc_is_page_editable()
				OR $is_edit_vc_roles
			){
				$vc_lean_wpb_ccom_comp($component);
			}
		}
	}
}, 100 );


add_action( 'current_screen', function () {
	
	if ( function_exists( 'get_current_screen' ) ) {
		global $pagenow;
		// Receive data only on the edit page or create a record
		if ( wp_doing_ajax() OR ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		$screen 	= get_current_screen();
		$components =  OBSER_SHORTCODES::get_shortcodes() ?: array();

		foreach ( $components as $component ) {

			$shortcode 	= $component::$shortcode;
			$elm 		= $component::get_component();

			if(!class_exists($elm)) continue;

			$params =$elm::map();
			
			if ( isset( $params['shortcode_post_type'] ) ) {
				if ( ! empty( $screen->post_type ) AND  is_array($params['shortcode_post_type']) AND !empty($params['shortcode_post_type']) AND ! in_array( $screen->post_type, $params['shortcode_post_type'] ) ) {
					vc_remove_element( $shortcode );
				}
			}
		}
	}
});



