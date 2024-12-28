<?php if ( ! defined( 'ABSPATH' ) ) {exit;} // Exit if accessed directly.

/**
 * Progressive web application.
 * 
 * @since 4.8.0
 */

class Codevz_Progressive_Web_App {

	// Custom cookie name.
	private $cookie_name = null;

	// Instance of this class.
	protected static $instance = null;

	public function __construct() {

		$this->cookie_name = Codevz_Plus::option( 'pwa_cookie_name', 'codevz_pwa_cookie' );

		// Run PWA on init.
		add_action( 'init', [ $this, 'init' ] );

		// Create/modify manifest file on theme options changes.
		add_action( 'customize_save_after', [ $this, 'customize_save_after' ], 10, 2 );

	}

	// Instance of this class.
	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	public function init() {

		if ( Codevz_Plus::option( 'pwa' ) ) {

			// Load manifest in website head.
			add_action( 'wp_head', [ $this, 'wp_head' ] );

			// Check mobile and cookie.
			if ( empty( $_COOKIE[ $this->cookie_name ] ) ) {

				// Assets.
				add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );

				// PWA welcome banner.
				add_action( 'wp_body_open', [ $this, 'wp_body_open' ] );

				// Reenerate manifest file.
				$cache = Codevz_Plus::option( 'pwa_cache' );

				if ( get_option( 'codevz_pwa_cache' ) != $cache ) {

					$this->generate_manifest();

					update_option( 'codevz_pwa_cache', $cache );

				}

			}

		}

	}

	// Load manifest file.
	public function wp_head() {

		$dir = wp_upload_dir();

		echo '<link rel="manifest" href="' . esc_url( trailingslashit( $dir[ 'baseurl' ] ) ) . 'manifest.json' . '">';

		if ( Codevz_Plus::option( 'pwa_icon' ) ) {
			echo '<link rel="apple-touch-icon" sizes="512x512" href="' . esc_url( Codevz_Plus::option( 'pwa_icon' ) ) . '">';
		}

	}

	// Assets.
	public function wp_enqueue_scripts() {

		wp_enqueue_style( 'codevz-plus-pwa', Codevz_Plus::$url . 'assets/css/pwa.css', false, Codevz_Plus::$ver );
		wp_enqueue_script( 'codevz-plus-pwa', Codevz_Plus::$url . 'assets/js/pwa.js', [ 'jquery' ], Codevz_Plus::$ver, true );

	}

	// Add PWA banner in body.
	public function wp_body_open() {

		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$ios = ( strpos( $userAgent, 'iPhone' ) !== false || strpos( $userAgent, 'iPad' ) !== false );

		echo '<div class="codevz-pwa codevz-pwa-android ' . esc_attr( $ios ? 'codevz-pwa-ios' : 'codevz-pwa-android' ) . '" data-cookie="' . esc_attr( $this->cookie_name ) . '"><div>';

		echo '<div class="codevz-pwa-plc"><div><i></i><i></i><i></i><i></i><i></i><i></i></div></div>';
		echo '<div class="codevz-pwa-plc"><div><i></i><i></i><i><img src="' . esc_url( Codevz_Plus::option( 'pwa_icon' ) ) . '" alt="PWA" /></i><i></i><i></i></div></div>';
		echo '<div class="codevz-pwa-plc"><div><i></i><i></i><i></i><i></i><i></i><i></i></div></div>';

		echo '<div class="codevz-pwa-title">' . esc_html( Codevz_Plus::option( 'pwa_title', esc_html__( 'Install XTRA PWA', 'codevz-plus' ) ) ) . '</div>';
		echo '<p>' . esc_html( Codevz_Plus::option( 'pwa_content', esc_html__( "Install our website's web application on your home screen for quick and easy access every time you are on the go", 'codevz-plus' ) ) ) . '</p>';

		echo '<a href="#" class="codevz-pwa-close"><i class="fa czico-close-bold"></i></a>';

		/* translators: 1. image iOS */
		$footer = '<span>' . sprintf( esc_html__( "Just tap %s then 'Add to home screen'", 'codevz-plus' ), '<img src="' . Codevz_Plus::$url . 'assets/img/ios-share.png" alt="Share" />' ) . '</span>';

		/* translators: 1. image android */
		$footer .= '<span>'. sprintf( esc_html__( "Just tap %s then 'Add to home screen'", 'codevz-plus' ), '<img src="' . Codevz_Plus::$url . 'assets/img/android-menu.png" alt="Share" />' ) . '</span>';

		echo '<div class="codevz-pwa-footer">' . do_shortcode( $footer ) . '</div>';

		echo '</div></div>';

	}

	// Create/modify manifest file on theme options save.
	public function customize_save_after() {

		$options = get_option( 'codevz_theme_options' );

		if ( ! empty( $options[ 'pwa' ] ) ) {

			$this->generate_manifest();

		}

	}

	// Generate manifest file function.
	public function generate_manifest() {

		// WP_Filesystem.
		$wpfs = Codevz_Plus::wpfs();

		$dir  = wp_upload_dir();
		$file = trailingslashit( $dir[ 'basedir' ] ) . 'manifest.json';

		$json = wp_json_encode( $this->manifest(), JSON_PRETTY_PRINT );

		$result = $wpfs->put_contents( $file, $json, FS_CHMOD_FILE );

		if ( is_wp_error( $result ) ) {
			error_log( 'Can not generate manifest.json file on your server.' );
		}

	}

	// Manifest JSON.
	public function manifest() {

		$icons = [
			[
				'src' 	=> Codevz_Plus::option( 'pwa_icon' ),
				'sizes' => '512x512',
				'type' 	=> 'image/png',
			]
		];

		return [
			'name' 							=> Codevz_Plus::option( 'pwa_name' ),
			'short_name' 					=> Codevz_Plus::option( 'pwa_short_name' ),
			'description' 					=> Codevz_Plus::option( 'pwa_desc' ),
			'start_url' 					=> esc_url( get_site_url() ),
			'display' 						=> 'standalone',
			'theme_color' 					=> Codevz_Plus::option( 'pwa_theme_color', '#111' ),
			'background_color' 				=> Codevz_Plus::option( 'pwa_background_color', '#fff' ),
			'orientation' 					=> 'portrait',
			'icons' 						=> $icons,
			'categories' 					=> [ 'business' ],
			'permissions' 					=> [ 'push' ],
			'related_applications' 			=> [],
			'prefer_related_applications' 	=> false
		];

	}

}

Codevz_Progressive_Web_App::instance();