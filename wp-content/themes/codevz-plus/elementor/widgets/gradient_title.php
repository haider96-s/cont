<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Xtra_Elementor_Widget_gradient_title extends Widget_Base { 

	protected $id = 'cz_gradient_title';

	public function get_name() {
		return $this->id;
	}

	public function get_title() {
		return esc_html__( 'Gradient Title', 'codevz-plus' );
	}

	public function get_icon() {
		return 'xtra-gradient-title';
	}

	public function get_categories() {
		return [ 'xtra' ];
	}

	public function get_keywords() {

		return [

			esc_html__( 'XTRA', 'codevz-plus' ),
			esc_html__( 'Title', 'codevz-plus' ),
			esc_html__( 'Gradient', 'codevz-plus' ),
			esc_html__( 'Text', 'codevz-plus' ),

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
				'label' => esc_html__( 'Settings', 'codevz-plus' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'content',
			[
				'label' => esc_html__('Text', 'codevz-plus' ),
				'type' => Controls_Manager::WYSIWYG,
				'default' => '<h1 style="font-size: 60px; font-weight: bold; text-align: center;"><strong>TITLE</strong></h1>',
			]
		);

		$this->add_control(
			'text_center',
			[
				'label' 	=> esc_html__( 'Center on mobile?', 'codevz-plus' ),
				'type' 		=> $free ? 'codevz_pro' : Controls_Manager::SWITCHER
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
			'sk_css',
			[
				'label' 	=> esc_html__( 'Background', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'background' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_gradient_title' ),
			]
		);

		$this->end_controls_section();
	}

	public function render() {

		$settings = $this->get_settings_for_display();
		
		// Classes
		$classes = array();
		$classes[] = 'cz_gradient_title';
		$classes[] = $settings['text_center'] ? 'cz_mobile_text_center' : '';
		if ( Codevz_Plus::contains( $settings['content'], array( ': center;', ':center;' ) ) ) {
			$classes[] = 'cz_gradient_title_center';
		}

		Xtra_Elementor::parallax( $settings );

		?>
		<div<?php echo wp_kses_post( (string) Codevz_Plus::classes( [], $classes ) );  ?>>
			<div class="cz_wpe_content"><?php echo do_shortcode( Codevz_Plus::fix_extra_p( $settings['content'] ) ); ?></div>
		</div>
		<?php

		Xtra_Elementor::parallax( $settings, true );
	}

	protected function content_template() {
	?>
	<#
		var content = settings.content,
			classes = 'cz_gradient_title',
			classes = classes + ( settings.text_center ? ' cz_mobile_text_center' : '' ),
			classes = classes + ( content.indexOf( 'center' ) >= 0 ? ' cz_gradient_title_center' : '' ),
			parallaxOpen = xtraElementorParallax( settings ),
			parallaxClose = xtraElementorParallax( settings, true );
	#>

	{{{ parallaxOpen }}}
	<div class="{{{classes}}}">
		<div class="cz_wpe_content">{{{content}}}</div>
	</div>

	{{{ parallaxClose }}}
	<?php
	}
}
	