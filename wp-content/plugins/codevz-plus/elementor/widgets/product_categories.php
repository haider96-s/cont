<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Xtra_Elementor_Widget_product_categories extends Widget_Base {

	protected $id = 'cz_product_categories';

	public function get_name() {
		return $this->id;
	}

	public function get_title() {
		return esc_html__( 'Product Categories', 'codevz-plus' );
	}

	public function get_icon() {
		return 'xtra-product-category';
	}


	public function get_categories() {
		return [ 'xtra' ];
	}

	public function get_keywords() {

		return [

			esc_html__( 'XTRA', 'codevz-plus' ),
			esc_html__( 'Grid', 'codevz-plus' ),
			esc_html__( 'category', 'codevz-plus' ),
			esc_html__( 'categories', 'codevz-plus' ),
			esc_html__( 'Product', 'codevz-plus' ),
			esc_html__( 'Woocommerce', 'codevz-plus' ),
			esc_html__( 'Shop', 'codevz-plus' ),
			esc_html__( 'Store', 'codevz-plus' ),

		];

	}

	public function get_product_categories() {

		$args = [
			'taxonomy'     => 'product_cat',
			'orderby'      => 'name',
			'show_count'   => 0,
			'pad_counts'   => 0,
			'hierarchical' => 1,
			'title_li'     => '',
			'hide_empty'   => 0
		];

		$options = [];

		$options[] = esc_html__( '~ Select ~', 'codevz-plus' );

		$all_categories = get_categories( $args );

		foreach( $all_categories as $cat ) {
			if ( $cat->category_parent == 0 ) {
				$options[ $cat->term_id ] = $cat->name;
			}
		}

		return $options;

	}

	public function register_controls() {

		$free = Codevz_Plus::is_free();

		$this->start_controls_section(
			'settings',
			[
				'label' => esc_html__( 'Settings', 'codevz-plus' ),
				'tab' 	=> Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'product_categories',
			[
				'label' 	=> esc_html__( 'Select Product Categories', 'codevz-plus' ),
				'type' 		=> Controls_Manager::SELECT2,
				'multiple' => true,
				'options' 	=> $this->get_product_categories()
			]
		);

		$this->add_control(
			'limit',
			[
				'label' 	=> esc_html__( 'Limit', 'codevz-plus' ),
				'type' 		=> Controls_Manager::NUMBER,
				'default' 	=> 6
			]
		);
	
		$this->add_control(
			'columns',
			[
				'label' 	=> esc_html__( 'Columns', 'codevz-plus' ),
				'type' 		=> Controls_Manager::NUMBER,
				'default' 	=> 4
			]
		);

		$this->add_control(
			'hide_empty',
			[
				'label' 	=> esc_html__( 'Hide Empty', 'codevz-plus' ),
				'type' 		=> $free ? 'codevz_pro' : Controls_Manager::SWITCHER,
				
			]
		);

		$this->add_control(
			'parent',
			[
				'label' 	=> esc_html__( 'Parent', 'codevz-plus' ),
				'type' 		=> $free ? 'codevz_pro' : Controls_Manager::SWITCHER,
			]
		);

		$this->add_control(
			'orderby',
			[
				'label' 	=> esc_html__( 'Orderby', 'codevz-plus' ),
				'type' 		=> $free ? 'codevz_pro' : Controls_Manager::SELECT,
				'default' 	=> 'date',
				'options' 	=> [
					'date' 		=> esc_html__( 'Date', 'codevz-plus' ),
					'ID' 		=> esc_html__( 'ID', 'codevz-plus' ),
					'rand' 		=> esc_html__( 'Menu_order', 'codevz-plus' ),
					'author' 	=> esc_html__( 'Popularity', 'codevz-plus' ),
					'title' 	=> esc_html__( 'Rand', 'codevz-plus' ),
					'name' 		=> esc_html__( 'Rating', 'codevz-plus' ),
					'type' 		=> esc_html__( 'Title', 'codevz-plus' ),

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
				'settings' 	=> [ 'background', 'margin' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.woocommerce ul.products li.product' ),
			]
		);

		$this->add_responsive_control(
			'sk_title',
			[
				'label' 	=> esc_html__( 'Title', 'codevz-plus' ),
				'type' 		=> $free ? 'stylekit_pro' : 'stylekit',
				'settings' 	=> [ 'background', 'margin' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.woocommerce ul.products li.product .woocommerce-loop-category__title, .woocommerce ul.products li.product  .woocommerce ul.products li.product' ),
			]
		);

		$this->add_responsive_control(
			'sk_image',
			[
				'label' 	=> esc_html__( 'Image', 'codevz-plus' ),
				'type' 		=> $free ? 'stylekit_pro' : 'stylekit',
				'settings' 	=> [ 'background', 'margin' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.woocommerce ul.products li.product a img' ),
			]
		);

		$this->end_controls_section();

	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		Xtra_Elementor::parallax( $settings );

		$hide_empty = $settings['hide_empty'] == 'yes' ? 1 : 0;

		echo do_shortcode( '[product_categories ids="' . esc_attr( implode( ',', (array) $settings[ 'product_categories' ] ) ) . '" columns="' . esc_attr( $settings['columns'] ) . '" limit="' . esc_attr( $settings['limit'] ) . '" hide_empty="' . esc_attr( $hide_empty ) . '" parent="' . esc_attr( $settings['parent'] ) . '" orderby="' . esc_attr( $settings['orderby'] ) . '"]' );

		Xtra_Elementor::parallax( $settings, true );

	}

}