<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Cannot access pages directly.

/**
 * Open Carousel Slider
 * 
 * @author Codevz
 * @copyright Codevz
 * @link http://codevz.com/
 */

class Codevz_WPBakery_carousel {

	public $name = false;

	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * Shortcode settings
	 */
	public function in( $wpb = false ) {
		add_shortcode( $this->name, [ $this, 'out' ] );

		$settings = array(
			'category'		=> Codevz_Plus::$title,
			'base'			=> $this->name,
			'name'			=> esc_html__( 'Carousel', 'codevz-plus' ),
			'description'	=> esc_html__( 'Add anything in carousel', 'codevz-plus' ),
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
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_overall',
					"heading"     	=> esc_html__( "Container styling", 'codevz-plus' ),
					'button' 		=> esc_html__( "Container", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'settings' 		=> array( 'background', 'padding', 'border' )
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_overall_tablet' ),
				array( 'type' => 'cz_hidden','param_name' => 'sk_overall_mobile' ),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Slides to show', 'codevz-plus' ),
					'param_name'	=> 'slidestoshow',
					'value'			=> '3',
					'options' 		=> array( 'unit' => '', 'step' => 1, 'min' => 1, 'max' => 10 ),
					'edit_field_class' => 'vc_col-xs-99',
				),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Slides on Tablet', 'codevz-plus' ),
					'param_name'	=> 'slidestoshow_tablet',
					'value'			=> '2',
					'options' 		=> array( 'unit' => '', 'step' => 1, 'min' => 1, 'max' => 10 ),
					'edit_field_class' => 'vc_col-xs-99'
				),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Slides on Mobile', 'codevz-plus' ),
					'options' 		=> array( 'unit' => '', 'step' => 1, 'min' => 1, 'max' => 10 ),
					'param_name'	=> 'slidestoshow_mobile',
					'value'			=> '1',
					'edit_field_class' => 'vc_col-xs-99'
				),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Slides to scroll', 'codevz-plus' ),
					'param_name'	=> 'slidestoscroll',
					'value'			=> '1',
					'options' 		=> array( 'unit' => '', 'step' => 1, 'min' => 1, 'max' => 10 ),
					'edit_field_class' => 'vc_col-xs-99',
				),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Gap between slides', 'codevz-plus' ),
					'param_name'	=> 'gap',
					'value'			=> '10px',
					'options' 		=> array( 'unit' => 'px', 'step' => 1, 'min' => 1, 'max' => 100 ),
					'edit_field_class' => 'vc_col-xs-99',
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Infinite?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'infinite'
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Auto play?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'autoplay'
				),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Autoplay delay (ms)', 'codevz-plus' ),
					'param_name'	=> 'autoplayspeed',
					'value'			=> '4000',
					'options' 		=> array( 'unit' => '', 'step' => 500, 'min' => 1000, 'max' => 6000 ),
					'edit_field_class' => 'vc_col-xs-99',
					'dependency'	=> array(
						'element'		=> 'autoplay',
						'not_empty'		=> true
					),
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Center mode?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'centermode'
				),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Center padding', 'codevz-plus' ),
					'param_name'	=> 'centerpadding',
					'options' 		=> array( 'unit' => 'px', 'step' => 1, 'min' => 1, 'max' => 100 ),
					'edit_field_class' => 'vc_col-xs-99',
					'dependency'	=> array(
						'element'		=> 'centermode',
						'not_empty'		=> true
					),
				),
				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_slides',
					"heading"     	=> esc_html__( "Slides styling", 'codevz-plus' ),
					'button' 		=> esc_html__( "Slides", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'settings' 		=> array( 'grayscale', 'blur', 'background', 'opacity', 'z-index', 'padding', 'margin', 'border', 'box-shadow' )
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_slides_mobile' ),
				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_center',
					"heading"     	=> esc_html__( "Center slide styling", 'codevz-plus' ),
					'button' 		=> esc_html__( "Center slide", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'dependency'	=> array(
						'element'		=> 'centermode',
						'not_empty'		=> true
					),
					'settings' 		=> array( 'grayscale', 'background', 'opacity', 'z-index', 'padding', 'margin', 'border', 'box-shadow' )
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_center_mobile' ),

				// Arrows
				array(
					"type"        	=> "dropdown",
					"heading"     	=> esc_html__("Arrows position", 'codevz-plus' ),
					"param_name"  	=> "arrows_position",
					'edit_field_class' => 'vc_col-xs-99',
					'value'		=> array(
						esc_html__( 'None', 'codevz-plus' ) => 'no_arrows',
						esc_html__( 'Both top left', 'codevz-plus' ) => 'arrows_tl',
						esc_html__( 'Both top center', 'codevz-plus' ) => 'arrows_tc',
						esc_html__( 'Both top right', 'codevz-plus' ) => 'arrows_tr',
						esc_html__( 'Top left / right', 'codevz-plus' ) => 'arrows_tlr',
						esc_html__( 'Middle left / right', 'codevz-plus' ) => 'arrows_mlr',
						esc_html__( 'Bottom left / right', 'codevz-plus' ) => 'arrows_blr',
						esc_html__( 'Both bottom left', 'codevz-plus' ) => 'arrows_bl',
						esc_html__( 'Both bottom center', 'codevz-plus' ) => 'arrows_bc',
						esc_html__( 'Both bottom right', 'codevz-plus' ) => 'arrows_br',
					),
					'std' => 'arrows_mlr',
					'group' => esc_html__( 'Arrows', 'codevz-plus' )
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Arrows inside carousel?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'arrows_inner',
					'group' 		=> esc_html__( 'Arrows', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'arrows_position',
						'value_not_equal_to'	=> array( 'no_arrows' )
					),
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Show on hover?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'arrows_show_on_hover',
					'default'		=> false,
					'group' => esc_html__( 'Arrows', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'arrows_position',
						'value_not_equal_to'	=> array( 'no_arrows' )
					),
				),
				array(
					"type"        	=> "cz_icon",
					"heading"     	=> esc_html__("Previous icon", 'codevz-plus' ),
					"param_name"  	=> "prev_icon",
					'edit_field_class' => 'vc_col-xs-99',
					'value'			=> 'fa fa-chevron-left',
					'group' => esc_html__( 'Arrows', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'arrows_position',
						'value_not_equal_to'	=> array( 'no_arrows' )
					),
				),
				array(
					"type"        	=> "cz_icon",
					"heading"     	=> esc_html__("Next icon", 'codevz-plus' ),
					"param_name"  	=> "next_icon",
					'edit_field_class' => 'vc_col-xs-99',
					'value'			=> 'fa fa-chevron-right',
					'group' => esc_html__( 'Arrows', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'arrows_position',
						'value_not_equal_to'	=> array( 'no_arrows' )
					),
				),
				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_prev_icon',
					'hover_id' 		=> 'sk_prev_icon_hover',
					"heading"     	=> esc_html__( "Previous icon styling", 'codevz-plus' ),
					'button' 		=> esc_html__( "Previous icon", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' => esc_html__( 'Arrows', 'codevz-plus' ),
					'settings' 		=> array( 'color', 'font-size', 'background', 'padding', 'margin', 'border' ),
					'dependency'	=> array(
						'element'				=> 'arrows_position',
						'value_not_equal_to'	=> array( 'no_arrows' )
					),
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_prev_icon_mobile' ),
				array( 'type' => 'cz_hidden','param_name' => 'sk_prev_icon_hover' ),

				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_next_icon',
					'hover_id' 		=> 'sk_next_icon_hover',
					"heading"     	=> esc_html__( "Next icon styling", 'codevz-plus' ),
					'button' 		=> esc_html__( "Next icon", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' => esc_html__( 'Arrows', 'codevz-plus' ),
					'settings' 		=> array( 'color', 'font-size', 'background', 'padding', 'margin', 'border' ),
					'dependency'	=> array(
						'element'				=> 'arrows_position',
						'value_not_equal_to'	=> array( 'no_arrows' )
					),
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_next_icon_mobile' ),
				array( 'type' => 'cz_hidden','param_name' => 'sk_next_icon_hover' ),

				// Counts.
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Counts', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'counts',
					'default'		=> false,
					'group' => esc_html__( 'Counts', 'codevz-plus' )
				),

				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_counts',
					'hover_id' 		=> 'sk_counts_hover',
					"heading"     	=> esc_html__( "Counts", 'codevz-plus' ),
					'button' 		=> esc_html__( "Counts", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' => esc_html__( 'Counts', 'codevz-plus' ),
					'settings' 		=> array( 'color', 'font-size', 'background', 'padding', 'margin', 'border' )
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_counts_mobile' ),
				array( 'type' => 'cz_hidden','param_name' => 'sk_counts_hover' ),

				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_counts_numbers',
					'hover_id' 		=> 'sk_counts_numbers_hover',
					"heading"     	=> esc_html__( "Counts numbers", 'codevz-plus' ),
					'button' 		=> esc_html__( "Counts numbers", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' => esc_html__( 'Counts', 'codevz-plus' ),
					'settings' 		=> array( 'color', 'font-size', 'background', 'padding', 'margin', 'border' )
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_counts_numbers_mobile' ),
				array( 'type' => 'cz_hidden','param_name' => 'sk_counts_numbers_hover' ),

				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_counts_seperator',
					'hover_id' 		=> 'sk_counts_seperator_hover',
					"heading"     	=> esc_html__( "Counts seperator", 'codevz-plus' ),
					'button' 		=> esc_html__( "Counts seperator", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' => esc_html__( 'Counts', 'codevz-plus' ),
					'settings' 		=> array( 'color', 'font-size', 'background', 'padding', 'margin', 'border' )
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_counts_seperator_mobile' ),
				array( 'type' => 'cz_hidden','param_name' => 'sk_counts_seperator_hover' ),

				// Dots
				array(
					"type"        	=> "dropdown",
					"heading"     	=> esc_html__("Dots position", 'codevz-plus' ),
					"param_name"  	=> "dots_position",
					'edit_field_class' => 'vc_col-xs-99',
					'value'		=> array(
						esc_html__( 'None', 'codevz-plus' ) 					=> 'no_dots',
						esc_html__( 'Top left', 'codevz-plus' ) 				=> 'dots_tl',
						esc_html__( 'Top center', 'codevz-plus' ) 			=> 'dots_tc',
						esc_html__( 'Top right', 'codevz-plus' ) 			=> 'dots_tr',
						esc_html__( 'Bottom left', 'codevz-plus' ) 			=> 'dots_bl',
						esc_html__( 'Bottom center', 'codevz-plus' ) 		=> 'dots_bc',
						esc_html__( 'Bottom right', 'codevz-plus' ) 			=> 'dots_br',
						esc_html__( 'Vertical top left', 'codevz-plus' ) 	=> 'dots_vtl',
						esc_html__( 'Vertical middle left', 'codevz-plus' ) 	=> 'dots_vml',
						esc_html__( 'Vertical bottom left', 'codevz-plus' ) 	=> 'dots_vbl',
						esc_html__( 'Vertical top right', 'codevz-plus' ) 	=> 'dots_vtr',
						esc_html__( 'Vertical middle right', 'codevz-plus' ) => 'dots_vmr',
						esc_html__( 'Vertical bottom right', 'codevz-plus' ) => 'dots_vbr',
					),
					'group' => esc_html__( 'Dots', 'codevz-plus' )
				),
				array(
					"type"        	=> "dropdown",
					"heading"     	=> esc_html__("Predefined style", 'codevz-plus' ),
					"param_name"  	=> "dots_style",
					'edit_field_class' => 'vc_col-xs-99',
					'value'		=> array(
						esc_html__( '~ Default ~', 'codevz-plus' ) 		=> '',
						esc_html__( 'Circle', 'codevz-plus' ) 		=> 'dots_circle',
						esc_html__( 'Circle 2', 'codevz-plus' ) 		=> 'dots_circle dots_circle_2',
						esc_html__( 'Circle outline', 'codevz-plus' ) => 'dots_circle_outline',
						esc_html__( 'Square', 'codevz-plus' ) 		=> 'dots_square',
						esc_html__( 'Lozenge', 'codevz-plus' ) 		=> 'dots_lozenge',
						esc_html__( 'Tiny line', 'codevz-plus' ) 	=> 'dots_tiny_line',
						esc_html__( 'Drop', 'codevz-plus' ) 			=> 'dots_drop',
					),
					'group' => esc_html__( 'Dots', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'dots_position',
						'value_not_equal_to'	=> array( 'no_dots' )
					),
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Dots inside carousel?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'dots_inner',
					'default'		=> false,
					'group' => esc_html__( 'Dots', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'dots_position',
						'value_not_equal_to'	=> array( 'no_dots' )
					),
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Show on hover?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'dots_show_on_hover',
					'default'		=> false,
					'group' => esc_html__( 'Dots', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'dots_position',
						'value_not_equal_to'	=> array( 'no_dots' )
					),
				),
				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_dots_container',
					"heading"     	=> esc_html__( "Container", 'codevz-plus' ),
					'button' 		=> esc_html__( "Container", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' => esc_html__( 'Dots', 'codevz-plus' ),
					'settings' 		=> array( 'color', 'background', 'padding', 'margin', 'border' ),
					'dependency'	=> array(
						'element'				=> 'dots_position',
						'value_not_equal_to'	=> array( 'no_dots' )
					),
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_dots_container_mobile' ),
				array(
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_dots',
					'hover_id' 		=> 'sk_dots_hover',
					"heading"     	=> esc_html__( "Dots styling", 'codevz-plus' ),
					'button' 		=> esc_html__( "Dots styling", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' => esc_html__( 'Dots', 'codevz-plus' ),
					'settings' 		=> array( 'color', 'background', 'padding', 'margin', 'border' ),
					'dependency'	=> array(
						'element'				=> 'dots_position',
						'value_not_equal_to'	=> array( 'no_dots' )
					),
				),
				array( 'type' => 'cz_hidden','param_name' => 'sk_dots_mobile' ),
				array( 'type' => 'cz_hidden','param_name' => 'sk_dots_hover' ),
				
				array(
					"type"        	=> "colorpicker",
					"heading"     	=> esc_html__( "Color", 'codevz-plus' ) . ' ' . esc_html__( "[Deprecated]", 'codevz-plus' ),
					"param_name"  	=> "dots_color",
					'edit_field_class' => 'vc_col-xs-99',
					'group' 		=> esc_html__( 'Dots', 'codevz-plus' ),
					'dependency'	=> array(
						'element'				=> 'dots_position',
						'value_not_equal_to'	=> array( 'no_dots' )
					),
				),

				// Advanced
				array(
					'type'			=> 'checkbox',
					'heading'		=> esc_html__('Overflow visible?', 'codevz-plus' ),
					'param_name'	=> 'overflow_visible',
					'edit_field_class' => 'vc_col-xs-99',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Fade mode?', 'codevz-plus' ),
					'description' 	=> esc_html__('Only works when slide to show is 1', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'fade',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('MouseWheel?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'mousewheel',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Disable slides links?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'disable_links',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__('Auto width detection?', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name' 	=> 'variablewidth',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type'			=> 'checkbox',
					'heading'		=> esc_html__('Vertical?', 'codevz-plus' ),
					'param_name'	=> 'vertical',
					'edit_field_class' => 'vc_col-xs-99',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type'			=> 'cz_slider',
					'heading'		=> esc_html__('Number of rows', 'codevz-plus' ),
					'param_name'	=> 'rows',
					'options' 		=> array( 'unit' => '', 'step' => 1, 'min' => 1, 'max' => 5 ),
					'edit_field_class' => 'vc_col-xs-99',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type'			=> 'dropdown',
					'heading'		=> esc_html__('Custom position', 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					'param_name'	=> 'even_odd',
					'value'			=> array(
						'Select' 			=> '',
						'Even / Odd' 		=> 'even_odd',
						'Odd / Even' 		=> 'odd_even'
					),
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					"type"        	=> "textfield",
					"heading"     	=> esc_html__("Sync class", 'codevz-plus' ),
					"description"   => 'e.g. .my_carousel_1',
					'edit_field_class' => 'vc_col-xs-99',
					"param_name"  	=> "selector",
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					"type"        	=> "textfield",
					"heading"     	=> esc_html__("Sync class", 'codevz-plus' ) . ' 2',
					"description"   => esc_html__("Fill with another carousel class, e.g. my_carousel_2, then add my_carousel_1 to second carousel", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99',
					"param_name"  	=> "sync",
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'cz_title',
					'param_name' 	=> 'cz_title',
					'class' 		=> '',
					'content' 		=> esc_html__( 'Responsive', 'codevz-plus' ),
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				),
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__( 'Hide on Desktop?', 'codevz-plus' ),
					'param_name' 	=> 'hide_on_d',
					'edit_field_class' => 'vc_col-xs-99',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				), 
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__( 'Hide on Tablet?', 'codevz-plus' ),
					'param_name' 	=> 'hide_on_t',
					'edit_field_class' => 'vc_col-xs-99',
					'group' 		=> esc_html__( 'Advanced', 'codevz-plus' )
				), 
				array(
					'type' 			=> 'checkbox',
					'heading' 		=> esc_html__( 'Hide on Mobile?', 'codevz-plus' ),
					'param_name' 	=> 'hide_on_m',
					'edit_field_class' => 'vc_col-xs-99',
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
						esc_html__( 'Mouse parallax', 'codevz-plus' )	=> 'mouse',
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
					'type' 			=> 'cz_sk',
					'param_name' 	=> 'sk_brfx',
					"heading"     	=> esc_html__( "Block Reveal", 'codevz-plus' ),
					'button' 		=> esc_html__( "Block Reveal", 'codevz-plus' ),
					'edit_field_class' => 'vc_col-xs-99 hidden',
					'group' 	=> esc_html__( 'Advanced', 'codevz-plus' ),
					'settings' 		=> array( 'background' )
				),
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
		$atts = Codevz_Plus::shortcode_atts( $this, $atts );

		// ID
		if ( ! $atts['id'] ) {
			$atts['id'] = Codevz_Plus::uniqid();
			$public = 1;
		}

		// Slick
		$slick = array(
			'selector'			=> $atts['selector'],
			'slidesToShow'		=> (int) $atts['slidestoshow'], 
			'slidesToScroll'	=> (int) $atts['slidestoscroll'], 
			'rows'				=> $atts['rows'] ? (int) $atts['rows'] : 1,
			'fade'				=> $atts['fade'] ? true : false, 
			'vertical'			=> $atts['vertical'] ? true : false, 
			'verticalSwiping'	=> $atts['vertical'] ? true : false, 
			'infinite'			=> $atts['infinite'] ? true : false, 
			'speed'				=> 1000, 
			'swipeToSlide' 		=> true,
			'centerMode'		=> $atts['centermode'] ? true : false, 
			'centerPadding'		=> $atts['centerpadding'], 
			'variableWidth'		=> $atts['variablewidth'] ? true : false, 
			'autoplay'			=> $atts['autoplay'] ? true : false, 
			'autoplaySpeed'		=> (int) $atts['autoplayspeed'], 
			'dots'				=> true,
			'counts'			=> $atts['counts'] ? true : false,
			'adaptiveHeight'	=> false,
			'responsive'		=> array(
  				array(
					'breakpoint'	=> 769,
					'settings'		=> array(
						'slidesToShow' 		=> $atts['slidestoshow_tablet'] ? (int) $atts['slidestoshow_tablet'] : 2,
						'slidesToScroll' 	=> 1,
						'infinite'			=> true,
						'touchMove' 		=> true
					)
				),
  				array(
					'breakpoint'	=> 481,
					'settings'		=> array(
						'slidesToShow' 		=> $atts['slidestoshow_tablet'] ? (int) $atts['slidestoshow_mobile'] : 1,
						'slidesToScroll' 	=> 1,
						'infinite'			=> true,
						'touchMove' 		=> true
					)
				),
			)
		);

		// Styles
		if ( isset( $public ) || Codevz_Plus::$vc_editable || Codevz_Plus::$is_admin ) {
			$css_id = '#' . $atts['id'];
			$custom = $atts['anim_delay'] ? 'animation-delay:' . $atts['anim_delay'] . ';' : '';

			$css_array = array(
				'sk_overall' 			=> array( $css_id, $custom ),
				'sk_brfx' 				=> $css_id . ':before',
				'sk_prev_icon' 			=> $css_id . ' .slick-prev',
				'sk_prev_icon_hover' 	=> $css_id . ' .slick-prev:hover',
				'sk_next_icon' 			=> $css_id . ' .slick-next',
				'sk_next_icon_hover' 	=> $css_id . ' .slick-next:hover',
				'sk_slides' 			=>  $css_id . ' div.slick-slide',
				( $atts['centermode'] ? 'sk_center' : 'x') =>  $css_id . ' div.slick-center',
				'sk_dots_container' 	=>  $css_id . ' .slick-dots',
				'sk_dots' 				=>  $css_id . ' .slick-dots li button',
				'sk_counts' 					=> $css_id . ' .xtra-slick-counts',
				'sk_counts_hover' 				=> $css_id . ' .xtra-slick-counts:hover',
				'sk_counts_seperator' 			=> $css_id . ' .xtra-slick-seperator',
				'sk_counts_seperator_hover'		=> $css_id . ' .xtra-slick-counts:hover .xtra-slick-seperator',
				'sk_counts_numbers' 			=> $css_id . ' .xtra-slick-counts .xtra-slick-current, .xtra-slick-counts .xtra-slick-all',
				'sk_counts_numbers_hover' 		=> $css_id . ' .xtra-slick-counts:hover .xtra-slick-current, .xtra-slick-counts:hover .xtra-slick-all',
			);

			$css 	= Codevz_Plus::sk_style( $atts, $css_array );
			$css_t 	= Codevz_Plus::sk_style( $atts, $css_array, '_tablet' );
			$css_m 	= Codevz_Plus::sk_style( $atts, $css_array, '_mobile' );

			$css .= $atts['dots_color'] ? $css_id . ' .slick-dots li button {border-color: ' . $atts['dots_color'] . ' !important;background: ' . $atts['dots_color'] . '}' . $css_id . ' .slick-dots li button:before {background: ' . $atts['dots_color'] . '}' : '';

			// Slides Gap
			if ( $atts['gap'] ) {
				$gap = preg_split( '/(?<=[0-9])(?=[^0-9]+)/i', $atts['gap'] );
				$gap_int = ( (int) $gap[0] / 2 );
				$gap_unit = $gap[1];

				$css .= $css_id . ' .slick-list{margin: 0 -' . $gap_int . $gap_unit . ';clip-path:inset(0 ' . $gap_int . $gap_unit . ' 0 ' . $gap_int . $gap_unit . ')}' . $css_id . ' .slick-slide{margin: 0 ' . $gap_int . $gap_unit . '}';
			}
		}

		// Sync to another
		$sync = '';
		if ( $atts['sync'] ) {
			$slick['asNavFor'] = '.' . $atts['sync'];
			$slick['focusOnSelect'] = true;
			$sync = 'is_synced ' . str_replace( '.', '', $atts['selector'] );
		}

		// Classes
		$classes = array();
		$classes[] = $atts['id'];
		$classes[] = 'slick';
		$classes[] = $sync;
		$classes[] = $atts['arrows_position'];
		$classes[] = $atts['dots_position'];
		$classes[] = $atts['dots_style'];
		$classes[] = $atts['even_odd'];
		$classes[] = $atts['dots_inner'] ? 'dots_inner' : '';
		$classes[] = $atts['mousewheel'] ? 'cz_mousewheel' : '';
		$classes[] = $atts['dots_show_on_hover'] ? 'dots_show_on_hover' : '';
		$classes[] = $atts['arrows_inner'] ? 'arrows_inner' : '';
		$classes[] = $atts['arrows_show_on_hover'] ? 'arrows_show_on_hover' : '';
		$classes[] = $atts['overflow_visible'] ? 'overflow_visible' : '';
		$classes[] = $atts['centermode'] ? 'is_center' : '';
		$classes[] = $atts['disable_links'] ? 'cz_disable_links' : '';
		$classes[] = $atts['vertical'] ? 'xtra-slick-vertical' : '';

		// Out
		$out = '<div id="' . $atts['id'] . '"' . Codevz_Plus::classes( $atts, $classes ) . ' data-slick=\'' . wp_json_encode( $slick ) . '\' data-slick-prev="' . esc_attr( $atts['prev_icon'] ) . '" data-slick-next="' . esc_attr( $atts['next_icon'] ) . '">' . do_shortcode( $content ) . '</div><div' . Codevz_Plus::data_stlye( $css, $css_t, $css_m ) . '></div>';

		return Codevz_Plus::_out( $atts, $out, 'slick( true )', $this->name );
	}
}