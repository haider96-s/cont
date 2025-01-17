<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Xtra_Elementor_Widget_particles extends Widget_Base {
	
	protected $id = 'cz_particles';

	public function get_name() {
		return $this->id;
	}

	public function get_title() {
		return esc_html__( 'Particles', 'codevz-plus' );
	}
	
	public function get_icon() {
		return 'xtra-particles';
	}

	public function get_categories() {
		return [ 'xtra' ];
	}

	public function get_keywords() {

		return [

			esc_html__( 'XTRA', 'codevz-plus' ),
			esc_html__( 'Particles', 'codevz-plus' ),
			esc_html__( 'Row', 'codevz-plus' ),
			esc_html__( 'Animation', 'codevz-plus' ),
			esc_html__( 'Background', 'codevz-plus' ),
			esc_html__( 'Wave', 'codevz-plus' ),
			esc_html__( 'Circles', 'codevz-plus' ),
			esc_html__( 'Stars', 'codevz-plus' ),
			esc_html__( 'Shapes', 'codevz-plus' ),

		];

	}

	public function get_style_depends() {
		return [ $this->id ];
	}

	public function get_script_depends() {
		return [ $this->id ];
	}

	public function register_controls() {

		$free = Codevz_Plus::is_free();

		$this->start_controls_section(
			'settings',
			[
				'label' 	=> esc_html__( 'Settings', 'codevz-plus' ),
				'tab' 		=> Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'xtra_elementor_template',
			[
				'label' 	=> esc_html__( 'Select template', 'codevz-plus' ),
				'type' 		=> Controls_Manager::SELECT,
				'options' 	=> Xtra_Elementor::get_templates()
			]
		);

		$this->add_responsive_control(
			'min_height',
			[
				'label' => esc_html__( 'Minimum Height', 'codevz-plus' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 300,
						'max' => 1000,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .cz-particles' => 'min-height: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'particle_padding',
			[
				'label' => esc_html__( 'Padding', 'codevz-plus' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .cz-particles' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'inner_row',
			[
				'label' => esc_html__( 'Content in Grid?', 'codevz-plus' ),
				'type' => Controls_Manager::SWITCHER
			]
		);

		$this->add_control(
			'shape_type',
			[
				'label' => esc_html__( 'Shape', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::SELECT,
				'default' => 'circle',
				'options' => [
					'circle' => esc_html__( 'Circle', 'codevz-plus' ),
					'edge' => esc_html__( 'Edge', 'codevz-plus' ),
					'triangle' => esc_html__( 'Triangle', 'codevz-plus' ),
					'polygon' => esc_html__( 'Polygon', 'codevz-plus' ),
					'star' => esc_html__( 'Star', 'codevz-plus' ),
				],
			]
		);

		$this->add_control(
			'shapes_color',
			[
				'label' => esc_html__( 'Shapes Color', 'codevz-plus' ),
				'type' => Controls_Manager::COLOR
			]
		);

		$this->add_control(
			'shapes_number',
			[
				'label' => esc_html__( 'Number of shapes', 'codevz-plus' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 10,
				'max' => 200,
				'step' => 10,
			]
		);

		$this->add_control(
			'shapes_size',
			[
				'label' => esc_html__( 'Shapes Size', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::NUMBER,
				'min' => 5,
				'max' => 200,
				'step' => 5,
			]
		);

		$this->add_control(
			'lines_distance',
			[
				'label' => esc_html__( 'Lines Distance', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::NUMBER,
				'min' => 100,
				'max' => 700,
				'step' => 10,
			]
		);

		$this->add_control(
			'lines_color',
			[
				'label' => esc_html__( 'Lines Color', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::COLOR
			]
		);

		$this->add_control(
			'lines_width',
			[
				'label' => esc_html__( 'Lines Width', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 10,
				'step' => 1,
			]
		);

		$this->add_control(
			'move_direction',
			[
				'label' 	=> esc_html__( 'Move Direction', 'codevz-plus' ),
				'type' 		=> Controls_Manager::SELECT,
				'default' 	=> 'none',
				'options' 	=> [
					'none' 		=> esc_html__( '~ Default ~', 'codevz-plus' ),
					'top' 		=> esc_html__( 'Top', 'codevz-plus' ),
					'right' 	=> esc_html__( 'Right', 'codevz-plus' ),
					'bottom' 	=> esc_html__( 'Bottom', 'codevz-plus' ),
					'left' 		=> esc_html__( 'Left', 'codevz-plus' ),
				],
			]
		);
		
		$this->add_control(
			'move_speed',
			[
				'label' => esc_html__( 'Move Speed', 'codevz-plus' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 50,
				'step' => 1,
			]
		);

		$this->add_control(
			'move_out_mode',
			[
				'label' => esc_html__( 'Move Out Mode', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::SELECT,
				'options' => [
					'out' => esc_html__( 'Out', 'codevz-plus' ),
					'bounce' => esc_html__( 'Bounce', 'codevz-plus' ),
				],
			]
		);

		$this->add_control(
			'on_hover',
			[
				'label' => esc_html__( 'On hover', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::SELECT,
				'default' => 'grab',
				'options' => [
					'grab' => esc_html__( 'Grab', 'codevz-plus' ),
					'bubble' => esc_html__( 'Bubble', 'codevz-plus' ),
					'repulse' => esc_html__( 'Repulse', 'codevz-plus' ),
				],
			]
		);

		$this->add_control (
			'on_click',
			[
				'label' => esc_html__( 'On Click', 'codevz-plus' ),
				'type' => $free ? 'codevz_pro' : Controls_Manager::SELECT,
				'default' => 'push',
				'options' => [
					'push' => esc_html__( 'Push', 'codevz-plus' ),
					'remove' => esc_html__( 'Remove', 'codevz-plus' ),
					'bubble' => esc_html__( 'Bubble', 'codevz-plus' ),
					'repulse' => esc_html__( 'Repulse', 'codevz-plus' ),
				],
			]
		);

		$this->end_controls_section();

	}

	public function render() {

		// Settings.
		$settings = $this->get_settings_for_display();

		$id = 'xtra-' . wp_rand( 1111, 9999 );

		$content = Codevz_Plus::get_page_as_element( $settings[ 'xtra_elementor_template' ] );
		$content = $settings['inner_row'] ? '<div class="row clr">' . $content . '</div>' : $content;

		echo '<div id="' . esc_attr( $id ) . '" class="cz-particles">' . do_shortcode( $content ) . '</div>';
		echo '
<script>

	jQuery( function( $ ) {

		var timeout = 2000;

		setTimeout(function() {
			if ( typeof particlesJS != "undefined" ) {

				particlesJS("' . esc_html( $id ) . '", {
				  "particles": {
				    "number": {
				      "value": ' . esc_html( $settings['shapes_number'] ? $settings['shapes_number'] : 100 ) . '
				    },
				    "color": {
				      "value": "' . esc_html( $settings['shapes_color'] ? $settings['shapes_color'] : '#a7a7a7' ) . '"
				    },
				    "shape": {
				      "type": "' . esc_html( $settings['shape_type'] ) . '",
				    },
				    "line_linked": {
				      "enable": ' . esc_html( ( $settings['lines_width'] == 0 ) ? 'false' : 'true' ) . ',
				      "distance": ' . esc_html( $settings['lines_distance'] ? $settings['lines_distance'] : 150 ) . ',
				      "color": "' . esc_html( $settings['lines_color'] ? $settings['lines_color'] : '#a7a7a7' ) . '",
				      "opacity": 0.4,
				      "width": ' . esc_html( $settings['lines_width'] ? $settings['lines_width'] : 1 ) . '
				    },
				    "opacity": {
				      "value": 0.5,
				      "random": true,
				      "anim": {
				        "enable": false,
				        "speed": 1,
				        "opacity_min": 0.1,
				        "sync": false
				      }
				    },
				    "size": {
				      "value": ' . esc_html( $settings['shapes_size'] ? $settings['shapes_size'] : 5 ) . ',
				      "random": true,
				      "anim": {
				        "enable": false,
				        "speed": 40,
				        "size_min": 0.1,
				        "sync": false
				      }
				    },
				    "move": {
				      "enable": true,
				      "speed": ' . esc_html( $settings['move_speed'] ? $settings['move_speed'] : 6 ) . ',
				      "direction": "' . esc_html( $settings['move_direction'] ) . '",
				      "random": false,
				      "straight": false,
				      "out_mode": "' . esc_html( $settings['move_out_mode'] ) . '",
				      "bounce": false,
				      "attract": {
				        "enable": false,
				        "rotateX": 600,
				        "rotateY": 1200
				      }
				    }
				  },
				  "interactivity": {
				    "detect_on": "canvas",
				    "events": {
				      "onhover": {
				        "enable": true,
				        "mode": "' . esc_html( $settings['on_hover'] ) . '"
				      },
				      "onclick": {
				        "enable": true,
				        "mode": "' . esc_html( $settings['on_click'] ) . '"
				      },
				      "resize": true
				    },
				    "modes": {
				      "grab": {
				        "distance": 100,
				        "line_linked": {
				          "opacity": ' . esc_html( ( $settings['lines_width'] == 0 ) ? '0' : '1' ) . '
				        }
				      },
				      "bubble": {
				        "distance": 400,
				        "size": 40,
				        "duration": 2,
				        "opacity": 8,
				        "speed": 3
				      },
				      "repulse": {
				        "distance": 200,
				        "duration": 0.4
				      },
				      "push": {
				        "particles_nb": 4
				      },
				      "remove": {
				        "particles_nb": 2
				      }
				    }
				  },
				  "retina_detect": true
				});
			}
		}, timeout );

	});

</script>';

	}

}