<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

use Codevz_Plus as Codevz_Plus;
use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Elementor\Controls_Manager;

class Xtra_Elementor_Widget_shopping_cart extends Widget_Base {

	protected $id = 'cz_shopping_cart';

	public function get_name() {
		return $this->id;
	}

	public function get_title() {
		return esc_html__( 'Header - Shopping Cart', 'codevz-plus' );
	}
	
	public function get_icon() {
		return 'xtra-shopping-cart';
	}

	public function get_categories() {
		return [ 'xtra' ];
	}

	public function get_keywords() {

		return [

			esc_html__( 'codevz', 'codevz-plus' ),
			esc_html__( 'Shop', 'codevz-plus' ),
			esc_html__( 'Cart', 'codevz-plus' ),
			esc_html__( 'Shopping', 'codevz-plus' ),
			esc_html__( 'Ajax', 'codevz-plus' ),

		];

	}

	public function register_controls() {

		$this->start_controls_section(
			'section_content',
			[
				'label' 	=> esc_html__( 'Settings', 'codevz-plus' ),
				'tab' 		=> Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'shop_plugin',
			[
				'label' 		=> esc_html__( 'Plugin', 'codevz-plus' ),
				'type' 			=> Controls_Manager::SELECT,
				'options' 		=> [
					'woo'			=> esc_html__( 'Woocommerce', 'codevz-plus' ),
					'edd' 			=> esc_html__( 'Easy Digital Download', 'codevz-plus' ),
				],
			]
		);

		$this->add_control(
			'shopcart_icon',
			[
				'label' => esc_html__( 'Icon', 'codevz-plus' ),
				'type' => Controls_Manager::ICONS,
				'skin' => 'inline',
				'label_block' => false,
				'default' 		=> [
					'value' 		=> 'fas fa-shopping-basket',
					'library' 		=> 'fa-solid',
				]
			]
		);

		$this->add_control(
			'shopcart_title',
			[
				'label' => esc_html__( 'Title', 'codevz-plus' ),
				'type' => Controls_Manager::TEXT
			]
		);

		$this->add_control(
			'i_position',
			[
				'label' => esc_html__( 'Position', 'codevz-plus' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' 						=> esc_html__( '~ Default ~', 'codevz-plus' ),
					'cz_helm_pos_left' 		=> esc_html__( 'Left', 'codevz-plus' ),
					//'cz_helm_pos_center' 	=> esc_html__( 'Center', 'codevz-plus' ),
					'cz_helm_pos_right' 	=> esc_html__( 'Right', 'codevz-plus' ),
				]
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
			'sk_shop_icon',
			[
				'label' 	=> esc_html__( 'Icon', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'background', 'border' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.shop_icon i' ),
			]
		);

		$this->add_responsive_control(
			'sk_title',
			[
				'label' 	=> esc_html__( 'Title', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'background', 'border' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.shop_icon span' ),
			]
		);

		$this->add_responsive_control(
			'sk_shop_count',
			[
				'label' 	=> esc_html__( 'Count', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'background', 'border' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_cart_count' ),
			]
		);

		$this->add_responsive_control(
			'sk_shop_content',
			[
				'label' 	=> esc_html__( 'Cart', 'codevz-plus' ),
				'type' 		=> 'stylekit',
				'settings' 	=> [ 'color', 'background', 'border' ],
				'selectors' => Xtra_Elementor::sk_selectors( '.cz_cart_items' ),
			]
		);

		$this->end_controls_section();
	}

	public function render() {

		$settings = $this->get_settings_for_display();

		Xtra_Elementor::parallax( $settings );

		//$icon = empty( $settings['shopcart_icon'] ) ? 'fa fa-shopping-basket' : $settings['shopcart_icon'];

		ob_start();
		Icons_Manager::render_icon( $settings['shopcart_icon'], [ 'class' => 'xtra-search-icon', 'data-xtra-icon' => ( empty( $settings['search_icon'][ 'value' ] ) ? 'fa fa-shopping-basket' : $settings['search_icon'][ 'value' ] ) ] );
		$icon = ob_get_clean();

		$shop_plugin = ( empty( $settings['shop_plugin'] ) || $settings['shop_plugin'] === 'woo' ) ? 'woo' : 'edd';

		$cart_url = $cart_content = '';

		if ( $shop_plugin === 'woo' && function_exists( 'is_woocommerce' ) ) {
			$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#';
			$cart_content = '<div class="cz_cart">' . ( Codevz_Plus::_GET( 'action' ) == 'elementor' ? '<span class="cz_cart_count">2</span><div class="cz_cart_items cz_cart_dummy"><div><div class="cart_list"><div class="item_small"><a href="#"></a><div class="cart_list_product_title cz_tooltip_up"><h3><a href="#">XXX</a></h3><div class="cart_list_product_quantity">1 x <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>32.00</span></div><a href="#" class="remove" data-product_id="1066" data-title="' . esc_html__( 'Remove', 'codevz-plus' ) . '"><i class="fa fa-trash"></i></a></div></div><div class="item_small"><a href="#"></a><div class="cart_list_product_title"><h3><a href="#">XXX</a></h3><div class="cart_list_product_quantity">1 x <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>32.00</span></div><a href="#" class="remove" data-product_id="1066" data-title="' . esc_html__( 'Remove', 'codevz-plus' ) . '"><i class="fa fa-trash"></i></a></div></div></div><div class="cz_cart_buttons clr"><a href="#">XXX, <span><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>64.00</span></span></a><a href="#">XXX</a></div></div></div>' : '' ) . '</div>';
		} else if ( function_exists( 'EDD' ) ) {
			$cart_url = function_exists( 'edd_get_checkout_uri' ) ? edd_get_checkout_uri() : '#';
			$cart_content = '<div class="cz_cart_edd"><span class="cz_cart_count edd-cart-quantity">' . wp_kses_post( (string) edd_get_cart_quantity() ) . '</span><div class="cz_cart_items"><div><div class="cart_list">' . str_replace( "&nbsp;", '', do_shortcode( '[download_cart]' ) ) . '</div></div></div></div>';
		}

		$escaped_title = '';

		if ( $settings[ 'shopcart_title' ] ) {
			$escaped_title = '<span>' . esc_html( $settings[ 'shopcart_title' ] ) . '</span>';
		}

		echo '<div class="' . esc_attr( $settings[ 'i_position' ] ? $settings[ 'i_position' ] . ' ' : '' ) . 'xtra-inline-element elms_shop_cart">';
		echo '<a class="shop_icon noborder" href="' . esc_url( $cart_url ) . '" aria-label="' . esc_html( Codevz_Plus::option( 'woo_cart', 'Cart' ) ) . '">' . do_shortcode( $icon . $escaped_title ) . '</a>';
		echo wp_kses_post( (string) $cart_content );
		echo '</div>';

		Xtra_Elementor::parallax( $settings, true );

	}

}