<?php
/**
 * Plugin Name: PDF viewer for Elementor & Gutenberg
 * Description: This plugin allows quick and easy embedding of PDF viewer to Elementor & Gutenberg.
 * Plugin URI:  https://github.com/kazbekkadalashvili/pdfjs-viewer-for-elementor
 * Version:     1.3.2
 * Author:      Kazbek Kadalashvili
 * Author URI:  https://kazbek.dev
 * Text Domain: pdfjs-viewer-for-elementor
 * Elementor tested up to: 3.18.3
 * Elementor Pro tested up to: 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once __DIR__ . '/vendor/autoload.php';

/**
 * Main PDFjs viewer for Elementor Class
 *
 * The init class that runs the Hello World plugin.
 * Intended To make sure that the plugin's minimum requirements are met.
 *
 * You should only modify the constants to match your plugin's needs.
 *
 * Any custom code should go inside Plugin Class in the plugin.php file.
 * @since 1.2.0
 */
final class PDFjs_Viewer_For_Elementor {

	/**
	 * Plugin Version
	 *
	 * @since 1.2.1
	 * @var string The plugin version.
	 */
	const VERSION = '1.3.2';

	/**
	 * Minimum Elementor Version
	 *
	 * @since 1.2.0
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 1.2.0
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.1';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		// Init Plugin
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin
	 *
	 * Validates that Elementor is already loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed include the plugin class.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function init() {

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}

		// Once we get here, We have passed all validation checks so we can safely include our plugin
		require_once( 'plugin.php' );
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'pdfjs-viewer-for-elementor' ),
			'<strong>' . esc_html__( 'PDFjs viewer for Elementor', 'pdfjs-viewer-for-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'pdfjs-viewer-for-elementor' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'pdfjs-viewer-for-elementor' ),
			'<strong>' . esc_html__( 'PDFjs viewer for Elementor', 'pdfjs-viewer-for-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'pdfjs-viewer-for-elementor' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'pdfjs-viewer-for-elementor' ),
			'<strong>' . esc_html__( 'PDFjs viewer for Elementor', 'pdfjs-viewer-for-elementor' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'pdfjs-viewer-for-elementor' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}
}

// Instantiate PDFjs_Viewer_For_Elementor.
new PDFjs_Viewer_For_Elementor();

wpify_custom_fields()->create_gutenberg_block( array(
	'name'             => 'wcf/pdfjsblock',
	'title'            => 'PDF Viewer',

	'items'            => array(
		array(
			'type'        => 'attachment',
            'attachment_type' => 'application/pdf',
			'title'       => 'PDF File',
			'id'          => 'pdfjs_viewer_file',
			'label'       => 'Choose PDF file',
			'position' => 'inspector'
		),
		array(
			'type'        => 'number',
			'title'       => 'Width',
			'id'          => 'pdf_width',
			'label'       => 'Width',
			'default'     => 600,
			'position' => 'inspector'
		),
		array(
			'type'        => 'number',
			'title'       => 'Height',
			'id'          => 'pdf_height',
			'label'       => 'Height',
			'default'     => 900,
			'position' => 'inspector'
		),
	),
    'render_callback'  => function ( $attributes ) {

        $html = '<iframe width="' . esc_attr( $attributes['pdf_width'] ) . '" height="' . esc_attr( $attributes['pdf_height'] ) . '" src="' . esc_url( plugin_dir_url( __FILE__ ) . 'assets/js/pdfjs/web/viewer.html?file=' . wp_get_attachment_url($attributes['pdfjs_viewer_file']) ) . '"></iframe>';

        return $html;
    },
) );


