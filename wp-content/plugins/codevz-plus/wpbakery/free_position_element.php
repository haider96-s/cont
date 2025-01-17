<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.

/**
 * Free Position Element (draggable)
 * 
 * @author Codevz
 * @link http://codevz.com/
 */

class Codevz_WPBakery_free_position_element {

	public $name = false;

	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * Shortcode settings ( vc_map )
	 */
	public function in( $wpb = false ) {
		add_shortcode( $this->name, [ $this, 'out' ] );

		$settings = array(
			'category'		=> Codevz_Plus::$title,
			//'deprecated' 	=> '4.6',
			'base'			=> $this->name,
			'name'			=> esc_html__( 'Free Position Element', 'codevz-plus' ),
			'description'	=> esc_html__( 'Draggable container', 'codevz-plus' ),
			'icon'			=> 'czi',
			'is_container' 	=> true,
			'js_view'		=> 'VcColumnView',
			'content_element'=> true,
			'params'		=> array(
				array(
					'type' 			=> 'cz_sc_id',
					'param_name' 	=> 'id',
					'save_always' 	=> true
				),
				array(
					'type' => 'cz_slider',
					'heading' => esc_html__( 'Top offset', 'codevz-plus' ),
					'description' => 'e.g. 20px or 20%',
					'edit_field_class' => 'vc_col-xs-99',
					'options' 		=> array( 'unit' => '%', 'step' => 1, 'min' => 1, 'max' => 100 ),
					'param_name' => 'css_top',
					'admin_label' 	=> true
				),
				array(
					'type' 				=> 'cz_slider',
					'heading' 			=> esc_html__( 'Left offset', 'codevz-plus' ),
					'edit_field_class' 	=> 'vc_col-xs-99',
					'options' 		=> array( 'unit' => '%', 'step' => 1, 'min' => 1, 'max' => 100 ),
					'param_name' 		=> 'css_left',
					'admin_label' 		=> true
				),
				array(
					'type' 				=> 'cz_slider',
					'heading' 			=> esc_html__( 'Custom width', 'codevz-plus' ),
					'edit_field_class' 	=> 'vc_col-xs-99',
					'options' 			=> array( 'unit' => 'px', 'step' => 10, 'min' => 0, 'max' => 500 ),
					'param_name' 		=> 'css_width'
				),
				array(
					'type' => 'cz_slider',
					'heading' => esc_html__( 'Custom Rotate', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'options' 		=> array( 'unit' => 'deg', 'step' => 1, 'min' => 0, 'max' => 360 ),
					'param_name' => 'css_transform'
				),
				array(
					'type' 			=> 'dropdown',
					'heading' 		=> esc_html__('Layer Priority', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'css_z-index',
					'value'		=> array(
						'-2' => '-2',
						'-1' => '-1',
						'0' => '0',
						'1'	=> '1',
						'2' => '2',
						'3'	=> '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
						'7' => '7',
						'8' => '8',
						'9' => '9',
						'10' => '10',
						'99' => '99',
						'999' => '999',
					),
					'std'			=> '0'
				),
				array(
					'type' 			=> 'dropdown',
					'heading' 		=> esc_html__('Hover', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'onhover',
					'value'		=> array(
						esc_html__( 'Select', 'codevz-plus' ) 						=> '',
						esc_html__( 'Hide on parent hover', 'codevz-plus' ) 			=> 'cz_hide_onhover',
						esc_html__( 'Show on parent hover FadeIn', 'codevz-plus' ) 	=> 'cz_show_onhover',
						esc_html__( 'Show on parent hover FadeUp', 'codevz-plus' ) 	=> 'cz_show_onhover cz_show_fadeup',
						esc_html__( 'Show on parent hover FadeDown', 'codevz-plus' ) => 'cz_show_onhover cz_show_fadedown',
						esc_html__( 'Show on parent hover FadeLeft', 'codevz-plus' ) => 'cz_show_onhover cz_show_fadeleft',
						esc_html__( 'Show on parent hover FadeRight', 'codevz-plus' ) => 'cz_show_onhover cz_show_faderight',
						esc_html__( 'Show on parent hover ZoomIn', 'codevz-plus' ) 	=> 'cz_show_onhover cz_show_zoomin',
						esc_html__( 'Show on parent hover ZoomOut', 'codevz-plus' ) 	=> 'cz_show_onhover cz_show_zoomout',
					)
				),
				array(
					'type' 			=> 'dropdown',
					'heading' 		=> esc_html__('Loop animation', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'animation',
					'value'		=> array(
						esc_html__('Select', 'codevz-plus' ) 		=> '',
						esc_html__('Animation', 'codevz-plus' ) . ' 1' 	=> 'cz_infinite_anim_1',
						esc_html__('Animation', 'codevz-plus' ) . ' 2' 	=> 'cz_infinite_anim_2',
						esc_html__('Animation', 'codevz-plus' ) . ' 3' 	=> 'cz_infinite_anim_3',
						esc_html__('Animation', 'codevz-plus' ) . ' 4' 	=> 'cz_infinite_anim_4',
						esc_html__('Animation', 'codevz-plus' ) . ' 5' 	=> 'cz_infinite_anim_5',
					)
				),

				// Advanced
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__( 'Hide on Desktop?', 'codevz-plus' ),
					'param_name' 	=> 'hide_on_d',
					'edit_field_class' => 'vc_col-xs-4',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				), 
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__( 'Hide on Tablet?', 'codevz-plus' ),
					'param_name' 	=> 'hide_on_t',
					'edit_field_class' => 'vc_col-xs-4',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				), 
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__( 'Hide on Mobile?', 'codevz-plus' ),
					'param_name' 	=> 'hide_on_m',
					'edit_field_class' => 'vc_col-xs-4',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'cz_title',
					'param_name' 	=> 'cz_title',
					'class' 		=> '',
					'content' 		=> esc_html__( 'Parallax', 'codevz-plus' ),
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					"type"        	=> "dropdown",
					"heading"     	=> esc_html__( "Parallax", 'codevz-plus' ),
					"param_name"  	=> "parallax_h",
					'edit_field_class' => 'vc_col-xs-99',
					'value'		=> array(
						esc_html__( 'Select', 'codevz-plus' )					=> '',
						
						esc_html__( 'Vertical', 'codevz-plus' )					=> 'v',
						esc_html__( 'Vertical + Mouse parallax', 'codevz-plus' )		=> 'vmouse',
						esc_html__( 'Horizontal', 'codevz-plus' )				=> 'true',
						esc_html__( 'Horizontal + Mouse parallax', 'codevz-plus' )	=> 'truemouse',
						esc_html__( 'Mouse parallax', 'codevz-plus' )				=> 'mouse',
					),
					"group"  		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					"type"        	=> "cz_slider",
					"heading"     	=> esc_html__( "Parallax speed", 'codevz-plus' ),
					"description"   => esc_html__( "Parallax is according to page scrolling", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					"param_name"  	=> "parallax",
					"value"  		=> "0",
					'options' 		=> array( 'unit' => '', 'step' => 1, 'min' => -50, 'max' => 50 ),
					'dependency'	=> array(
						'element'		=> 'parallax_h',
						'value'			=> array( 'v', 'vmouse', 'true', 'truemouse' )
					),
					"group"  		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__( 'Stop when done', 'codevz-plus' ),
					'param_name' 	=> 'parallax_stop',
					'edit_field_class' => 'vc_col-xs-99',
					'dependency'	=> array(
						'element'		=> 'parallax_h',
						'value'			=> array( 'v', 'vmouse', 'true', 'truemouse' )
					),
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				), 
				array(
					"type"        	=> "cz_slider",
					"heading"     	=> esc_html__("Mouse speed", 'codevz-plus' ),
					"description"   => esc_html__( "Mouse parallax is according to mouse move", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					"param_name"  	=> "mparallax",
					"value"  		=> "0",
					'options' 		=> array( 'unit' => '', 'step' => 1, 'min' => -30, 'max' => 30 ),
					'dependency'	=> array(
						'element'		=> 'parallax_h',
						'value'			=> array( 'vmouse', 'truemouse', 'mouse' )
					),
					"group"  		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'cz_title',
					'param_name' 	=> 'cz_title',
					'class' 		=> '',
					'content' 		=> esc_html__( 'Animation & Class', 'codevz-plus' ),
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				Codevz_Plus::wpb_animation_tab( false ),
				array(
					"type"        	=> "cz_slider",
					"heading"     	=> esc_html__("Animation Delay", 'codevz-plus' ),
					"description" 	=> 'e.g. 500ms',
					"param_name"  	=> "anim_delay",
					'options' 		=> array( 'unit' => 'ms', 'step' => 100, 'min' => 0, 'max' => 5000 ),
					'edit_field_class' => 'vc_col-xs-6',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					"type"        	=> "textfield",
					"heading"     	=> esc_html__( "Extra Class", 'codevz-plus' ),
					"param_name"  	=> "class",
					'edit_field_class' => 'vc_col-xs-6',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),

			)

		);

		return $wpb ? vc_map( $settings ) : $settings;
	}

	/**
	 *
	 * Shortcode output
	 * 
	 * @return string
	 * 
	 */
	public function out( $atts, $content = '' ) {

		// Settings.
		$atts = Codevz_Plus::shortcode_atts( $this, $atts );

		// Styles
		$css = $atts['css_top'] ? 'top:' . $atts['css_top'] . ';' : '';
		$css .= $atts['css_left'] ? 'left:' . $atts['css_left'] . ';' : '';
		$css .= $atts['css_width'] ? 'width:' . $atts['css_width'] . ';' : '';
		$css .= $atts['css_z-index'] ? 'z-index:' . $atts['css_z-index'] . ';' : '';
		$css .= $atts['anim_delay'] ? 'animation-delay:' . $atts['anim_delay'] . ';' : '';
		$css = $css ? ' style="' . $css . '"' : '';
		$in_css = $atts['css_transform'] ? ' style="transform:rotate(' . $atts['css_transform'] . ');"' : '';

		// Classes
		$classes = array();
		$classes[] = 'cz_free_position_element';
		$classes[] = $atts['animation'];
		$classes[] = $atts['onhover'];

		// Data
		$data = Codevz_Plus::$vc_editable ? ' data-top="' . $atts['css_top'] . '" data-left="' . $atts['css_left'] . '"' : '';

		// Out
		$out = '<div' . Codevz_Plus::classes( $atts, $classes ) . $data . $css . '><div' . $in_css . '>' . do_shortcode( $content ) . '</div></div>';

		return Codevz_Plus::_out( $atts, $out, 'free_position_element( ".vc_cz_free_position_element" )', $this->name );
	}

}