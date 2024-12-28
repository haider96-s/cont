<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

/**
 * Multilingual compatibility.
 */

class Codevz_Multilang {

	// Instance of this class.
	protected static $instance = null;

	public function __construct() {

		add_action( 'wpml_loaded', 'wpml' );
		add_action( 'pll_after_languages_register', 'polylang');
		

	}

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	// Hook for WPML
	public function wpml() {

		if ( function_exists( 'icl_register_string' ) ) {

			$theme_options = get_option( 'codevz_theme_options' );

			if ( ! empty( $theme_options ) ) {

				foreach( $theme_options as $key => $value ) {
					icl_register_string( 'Codevz Theme Options', $key, $value );
				}

			}

		}

	}

	// Hook for Polylang
	public function polylang() {

		if ( function_exists( 'pll_register_string' ) ) {

			$theme_options = get_option( 'codevz_theme_options' );

			if ( ! empty( $theme_options ) ) {

				foreach( $theme_options as $key => $value ) {

					pll_register_string( $key, $value, 'Codevz Theme Options' );

				}

			}

		}

	}

}

Codevz_Multilang::instance();