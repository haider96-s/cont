<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Elementor\Controls_Manager;

class Xtra_Elementor_Widget_subscribe extends Widget_Base { 

	protected $id = 'cz_subscribe';

	public function get_name() {
		return $this->id;
	}

	public function get_title() {
		return esc_html__( 'Subscribe', 'codevz-plus' );
	}
	
	public function get_icon() {
		return 'xtra-subscribe';
	}

	public function get_categories() {
		return [ 'xtra' ];
	}

	public function get_keywords() {

		return [

			esc_html__( 'XTRA', 'codevz-plus' ),
			esc_html__( 'Subscribe', 'codevz-plus' ),
			esc_html__( 'Newsletter', 'codevz-plus' ),
			esc_html__( 'Mailchimp', 'codevz-plus' ),
			esc_html__( 'Feed', 'codevz-plus' ),

		];

	}
	
	public function get_style_depends() {
		return [ $this->id, 'cz_parallax' ];
	}

	public function get_script_depends() {
		return [ $this->id, 'cz_parallax' ];
	}
	
	public function register_controls() {

		$free = Codevz_Plus::is_free();

		$this->start_controls_section(
			'section_content',
			[
				'label' => 'Settings',
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'style',
			[
				'label' => esc_html__( 'Form style', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => esc_html__( 'Square', 'codevz-plus' ),
					'cz_subscribe_round' => esc_html__( 'Round', 'codevz-plus' ),
					'cz_subscribe_round_2' => esc_html__( 'Round', 'codevz-plus' ) . ' 2',
					'cz_subscribe_relative' => esc_html__( 'Square,Button next line', 'codevz-plus' ),
					'cz_subscribe_relative cz_subscribe_round' => esc_html__( 'Round,Button next line', 'codevz-plus' ),
				],
			]
		);

		$this->add_control(
			'position',
			[
				'label' => esc_html__( 'Position', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Left', 'codevz-plus' ),
					'center' => esc_html__( 'Center', 'codevz-plus' ),
					'right' => esc_html__( 'Right', 'codevz-plus' ),
				],
			]
		);

		$this->add_control(
			'btn_position',
			[
				'label' => esc_html__( 'Button Position', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Left', 'codevz-plus' ),
					'cz_subscribe_btn_center' => esc_html__( 'Center', 'codevz-plus' ),
					'cz_subscribe_btn_right' => esc_html__( 'Right', 'codevz-plus' ),
				],
				'condition' => [
					'style' => [ 'cz_subscribe_relative', 'cz_subscribe_relative cz_subscribe_round' ]
				],
			]
		);

		$this->add_control(
			'action',
			[
				'label' => esc_html__( 'Action URL', 'codevz-plus' ),
				'type' => Controls_Manager::TEXT
			]
		);

		$this->add_control(
			'placeholder',
			[
				'label' => esc_html__('Placeholder', 'codevz-plus' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__('Enter Your Email...', 'codevz-plus' ),
			]
		);

		$this->add_control(
			'type_attr',
			[
				'label' => esc_html__( 'Type attributes', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'email',
				'options' => [
					'email' => esc_html__( 'Email', 'codevz-plus' ),
					'text' => esc_html__( 'Text', 'codevz-plus' ),
					'number' => esc_html__( 'Number', 'codevz-plus' ),
					'search' => esc_html__( 'Search', 'codevz-plus' ),
					'tel' => esc_html__( 'Tel', 'codevz-plus' ),
					'time' => esc_html__( 'Time', 'codevz-plus' ),
					'date' => esc_html__( 'Date', 'codevz-plus' ),
					'url' => esc_html__( 'url', 'codevz-plus' ),
					'password' => esc_html__( 'Password', 'codevz-plus' ),
				],
			]
		);

		$this->add_control(
			'name_attr',
			[
				'label' => esc_html__('Name attributes', 'codevz-plus' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'MERGE0'
			]
		);

		$this->add_control(
			'method',
			[
				'label' => esc_html__( 'Method', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => [
					'post' => esc_html__( 'Post', 'codevz-plus' ),
					'get' => esc_html__( 'Get', 'codevz-plus' ),
				],
			]
		);

		$this->add_control(
			'target',
			[
				'label' => esc_html__( 'Target', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'default' => '_blank',
				'options' => [
					'_blank' => '_blank',
					'_self' => '_self',
				],
			]
		);

		$this->add_control(
			'btn_title',
			[
				'label' => esc_html__('Button Title', 'codevz-plus' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__('join now', 'codevz-plus' ),
			]
		);

		$this->add_control(
			'icon',
			[
				'label' => esc_html__( 'Icon', 'codevz-plus' ),
				'type' => Controls_Manager::ICONS,
				'skin' => 'inline',
				'label_block' => false
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
			'sk_overall',
			[
				'label' 	=> esc_html__( 'Container', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'width', 'background', 'padding', 'border' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_subscribe_elm' ),
			]
		);

		$this->add_responsive_control(
			'sk_input',
			[
				'label' 	=> esc_html__( 'Input', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'text-align', 'font-size', 'background', 'border' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_subscribe_elm input' ),
			]
		);

		$this->add_responsive_control(
			'sk_button',
			[
				'label' 	=> esc_html__( 'Button', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'font-size', 'background' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_subscribe_elm button', '.cz_subscribe_elm button:hover' ),
			]
		);

		$this->add_responsive_control(
			'sk_icon',
			[
				'label' 	=> esc_html__( 'Icon', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'font-size', 'background' ],
				'condition' => [
					'icon[value]!' => ''
				],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_subscribe_elm button i', '.cz_subscribe_elm button:hover i' ),
			]
		);

	}

	public function render() {

		$settings = $this->get_settings_for_display();

		ob_start();
		Icons_Manager::render_icon( $settings['icon'], [ 'class' => $settings['btn_title'] ? 'mr8' : '' ] );
		$icon = ob_get_clean();

		$btn_title = $icon ? $icon . $settings['btn_title'] : $settings['btn_title'];

		$classes = [];
		$classes[] = 'cz_subscribe_elm clr';
		$classes[] = $settings['style'];
		$classes[] = $settings['btn_position'];
		$classes[] = $settings['position'] ? 'cz_subscribe_elm_' . $settings['position'] : '';

		Xtra_Elementor::parallax( $settings );

		if ( ! $settings['action'] ) {
			$settings['action'] = get_site_url();
		}

		?>

		<form<?php echo wp_kses_post( (string) Codevz_Plus::classes( [], $classes ) ); ?> action="<?php echo esc_url( $settings['action'] ); ?>" method="<?php echo esc_attr( $settings['method'] ); ?>" name="mc-embedded-subscribe-form" target="<?php echo esc_url( $settings['target'] ); ?>">
			<input type="<?php echo esc_attr( $settings['type_attr'] ); ?>" name="<?php echo esc_attr( $settings['name_attr'] ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" required="required">
			<button name="subscribe" type="submit"><?php echo do_shortcode( $btn_title ); ?></button>
		</form>

		<?php
		Xtra_Elementor::parallax( $settings, true );
	}

	protected function content_template() {
		?>
		<#
			var iconHTML = elementor.helpers.renderIcon( view, settings.icon, { 'aria-hidden': true, 'class': settings.btn_title ? 'mr8' : '' }, 'i' , 'object' ),
				btn_title = iconHTML.value ? iconHTML.value + settings.btn_title : settings.btn_title,

				parallaxOpen = xtraElementorParallax( settings ),
				parallaxClose = xtraElementorParallax( settings, true ),

				classes = 'cz_subscribe_elm clr',
				classes = classes + ' ' + settings.style,
				classes = classes + ' ' + settings.btn_position,
				classes = classes + ( settings.position ? ' cz_subscribe_elm_' + settings.position : '' );
		#>

		{{{ parallaxOpen }}}

		<form class="{{{ classes }}}" action="{{{settings.action}}}" method="{{{settings.method}}}" name="mc-embedded-subscribe-form" target="_blank">
			<input type="{{{settings.type_attr}}}" name="{{{settings.name_attr}}}" placeholder="{{{settings.placeholder}}}" required="required">
			<button name="subscribe" type="submit"> {{{btn_title}}} </button>
		</form>
		<div class="clr"></div>

		{{{ parallaxClose }}}
		<?php 

	}
}
