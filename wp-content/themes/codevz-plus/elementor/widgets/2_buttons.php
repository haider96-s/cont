<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Elementor\Controls_Manager;

class Xtra_Elementor_Widget_2_buttons extends Widget_Base {

	protected $id = 'cz_2_buttons';

	public function get_name() {
		return $this->id;
	}

	public function get_title() {
		return esc_html__( '2 Buttons', 'codevz-plus' );
	}
	
	public function get_icon() {
		return 'xtra-2-buttons';
	}

	public function get_categories() {
		return [ 'xtra' ];
	}

	public function get_keywords() {

		return [

			esc_html__( 'XTRA', 'codevz-plus' ),
			esc_html__( '2 Buttons', 'codevz-plus' ),
			esc_html__( 'Button', 'codevz-plus' ),
			esc_html__( 'Two', 'codevz-plus' ),

		];

	}

	public function get_style_depends() {

		$array = [ $this->id, 'cz_button', 'cz_parallax' ];

		if ( Codevz_Plus::$is_rtl ) {
			$array[] = $this->id . '_rtl';
			$array[] = 'cz_button_rtl';
		}

		return $array;

	}

	public function get_script_depends() {
		return [ $this->id, 'cz_parallax' ];
	}

	public function register_controls() {

		$free = Codevz_Plus::is_free();

		$this->start_controls_section(
			'section_content',
			[
				'label' 	=> esc_html__( 'Settings', 'codevz-plus' ),
				'tab' 		=> Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'title',
			[
				'label' 	=> esc_html__( 'Button', 'codevz-plus' ) . ' 1',
				'type' 		=> Controls_Manager::TEXT,
				'default' 	=> esc_html__( 'Button', 'codevz-plus' ) . ' 1',
				'placeholder' => esc_html__( 'Button', 'codevz-plus' ) . ' 1',
			]
		);

		$this->add_control(
			'link',
			[
				'label' 	=> esc_html__( 'Link', 'codevz-plus' ) . ' 1',
				'type' 		=> Controls_Manager::URL,
				'show_external' => true
			]
		);

		$this->add_control(
			'title2',
			[
				'label' 	=> esc_html__( 'Button', 'codevz-plus' ) . ' 2',
				'type' 		=> Controls_Manager::TEXT,
				'default' 	=> esc_html__( 'Button', 'codevz-plus' ) . ' 2',
				'placeholder' => esc_html__( 'Button', 'codevz-plus' ) . ' 2',
			]
		);

		$this->add_control(
			'link2',
			[
				'label' 	=> esc_html__( 'Link', 'codevz-plus' ) . ' 2',
				'type' 		=> Controls_Manager::URL,
				'show_external' => true
			]
		);

		$this->add_control(
			'css_position',
			[
				'label' => esc_html__( 'Position', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'cz_2_btn_center',
				'options' => [
					''  				=> esc_html__( '~ Select ~', 'codevz-plus' ),
					'cz_2_btn_left' 	=> esc_html__( 'Left', 'codevz-plus' ),
					'cz_2_btn_center' 	=> esc_html__( 'Center', 'codevz-plus' ),
					'cz_2_btn_right' 	=> esc_html__( 'Right', 'codevz-plus' ),
					'cz_2_btn_left cz_mobile_btn_center' => ( Codevz_Plus::$is_rtl ? esc_html__( "Right", 'codevz-plus' ) : esc_html__( "Left", 'codevz-plus' ) ) . ' ' . esc_html__( '(Center in mobile)', 'codevz-plus' ),
					'cz_2_btn_right cz_mobile_btn_center' => ( Codevz_Plus::$is_rtl ? esc_html__( "Left", 'codevz-plus' ) : esc_html__( "Right", 'codevz-plus' ) ) . ' ' . esc_html__( '(Center in mobile)', 'codevz-plus' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'separator_section',
			[
				'label' => esc_html__( 'Separator', 'codevz-plus' )
			]
		);

		$this->add_control(
			'separator',
			[
				'label' 		=> esc_html__( 'Separator', 'codevz-plus' ),
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'text',
				'options' 		=> [
					'text'  		=> esc_html__( 'Text', 'codevz-plus' ),
					'icon' 			=> esc_html__( 'Icon', 'codevz-plus' ) . ( $free ? ' [' . esc_html__( 'PRO', 'codevz-plus' ) . ']' : '' ),
				],
			]
		);

		$this->add_control(
			'sep_text',
			[
				'label' 		=> esc_html__( 'Text', 'codevz-plus' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> esc_html__( 'OR', 'codevz-plus' ),
				'placeholder' 	=> esc_html__( 'OR', 'codevz-plus' ),
				'condition' 	=> [
					'separator' 	=> 'text'
				],
			]
		);

		$this->add_control(
			'icon',
			[
				'label' 		=> esc_html__( 'Icon', 'codevz-plus' ),
				'type' 			=> Controls_Manager::ICONS,
				'skin' 			=> 'inline',
				'label_block' 	=> false,
				'condition' 	=> [
					'separator' 	=> 'icon'
				]
			]
		);

		$this->add_control(
			'style',
			[
				'label' 		=> esc_html__( 'Style', 'codevz-plus' ),
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'style1',
				'options' 		=> [
					'style1' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 1',
					'style2' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 2',
					'style3' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 3',
					'style4' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 4' . ( $free ? ' [' . esc_html__( 'PRO', 'codevz-plus' ) . ']' : '' ),
					'style5' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 5' . ( $free ? ' [' . esc_html__( 'PRO', 'codevz-plus' ) . ']' : '' ),
					'style6' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 6' . ( $free ? ' [' . esc_html__( 'PRO', 'codevz-plus' ) . ']' : '' ),
					'style7' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 7' . ( $free ? ' [' . esc_html__( 'PRO', 'codevz-plus' ) . ']' : '' ),
					'style8' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 8' . ( $free ? ' [' . esc_html__( 'PRO', 'codevz-plus' ) . ']' : '' ),
					'style9' 		=> esc_html__( 'Style', 'codevz-plus' ) . ' 9' . ( $free ? ' [' . esc_html__( 'PRO', 'codevz-plus' ) . ']' : '' ),
				],
			]
		);

		$this->end_controls_section();

		// Parallax settings.
		Xtra_Elementor::parallax_settings( $this );

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'codevz-plus' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'sk_con',
			[
				'label' 	=> esc_html__( 'Container', 'codevz-plus' ),
				'type' 		=> $free ? 'stylekit_pro' : 'stylekit',
				'settings' 	=> [ 'background' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_2_btn' ),
			]
		);

		$this->add_responsive_control(
			'sk_btn1',
			[
				'label' 	=> esc_html__( 'Button 1', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'font-size', 'background' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_2_btn .btn1', '.cz_2_btn .btn1:hover' ),
			]
		);

		$this->add_responsive_control(
			'sk_btn2',
			[
				'label' 	=> esc_html__( 'Button 2', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'font-size', 'background' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_2_btn .btn2', '.cz_2_btn .btn2:hover' ),
			]
		);

		$this->add_responsive_control(
			'sk_sep',
			[
				'label' 	=> esc_html__( 'Separator container', 'codevz-plus' ),
				'type' 		=> $free ? 'stylekit_pro' : 'stylekit',
				'settings' 	=> [ 'background' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_2_btn .cz_2_btn_sep', '.cz_2_btn:hover .cz_2_btn_sep' ),
			]
		);

		$this->add_responsive_control(
			'sk_icon',
			[
				'label' 	=> esc_html__( 'Separator', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'font-size', 'background' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_2_btn i', '.cz_2_btn:hover i' ),
			]
		);

		$this->end_controls_section();
	}

	public function render() {

		// Settings.
		$settings = $this->get_settings_for_display();

		$this->add_link_attributes( 'link', $settings['link'] );
		$this->add_link_attributes( 'link2', $settings['link2'] );

		// Buttons
		$btn1 = $settings['title'] ? '<a class="cz_btn btn1" ' . $this->get_render_attribute_string( 'link' ) . '><strong>' . do_shortcode( $settings['title'] ) . '</strong> </a>' : '';
		$btn2 = $settings['title2'] ? '<a class="cz_btn btn2" ' . $this->get_render_attribute_string( 'link2' ) . '><strong>' . do_shortcode( $settings['title2'] ) . '</strong> </a>' : '';

		// Separator
		if ( $settings['separator'] === 'icon' ) {
			ob_start();
			Icons_Manager::render_icon( $settings['icon'] );
			$sep = ob_get_clean();
		} else {
			$sep = '<i><span>' . do_shortcode( wp_kses_post( (string) $settings['sep_text'] ) ) . '</span></i>';
		}

		// Classes
		$classes = array();
		$classes[] = 'cz_2_btn';
		$classes[] = $settings['css_position'] ? $settings['css_position'] : '';

		Xtra_Elementor::parallax( $settings );

		?>
		<div<?php echo wp_kses_post( (string) Codevz_Plus::classes( [], $classes ) ); ?>>
			<?php echo wp_kses_post( (string) $btn1 ); ?>
			<div class="cz_2_btn_sep <?php echo esc_attr( $settings['style'] ); ?>">
				<?php echo do_shortcode( $sep ); ?>
			</div>
			<?php echo wp_kses_post( (string) $btn2 ); ?>
		</div>
		<?php

		Xtra_Elementor::parallax( $settings, true );

	}

	public function content_template() {

		?>
		<#

		var btn1 = settings.title ? '<a class="cz_btn btn1"  href="' + settings.link.url + '"> <strong>' + settings.title + '</strong> </a>' : '',
			btn2 = settings.title2 ? '<a class="cz_btn btn2"  href="' + settings.link2.url + '"><strong>' + settings.title2 + '</strong> </a>' : '',

			classes = 'cz_2_btn', 
			classes = classes + ' ' + settings.css_position,

			iconHTML = elementor.helpers.renderIcon( view, settings.icon, { 'aria-hidden': true }, 'i' , 'object' );

		if ( settings.separator === 'icon' ) {
			var sep = iconHTML.value;
		} else {
			var sep = '<i><span>' + settings.sep_text + '</span></i>';
		}

		var parallaxOpen = xtraElementorParallax( settings ),
			parallaxClose = xtraElementorParallax( settings, true );
		
		#>

		{{{ parallaxOpen }}}
		
		<div class="{{{ classes }}}">

			{{{ btn1 }}}

			<div class="cz_2_btn_sep {{{ settings.style }}}">
				{{{ sep }}}
			</div>

			{{{ btn2 }}}

		</div>

		{{{ parallaxClose }}}

		<?php
	}
}