<?php
/**
 * The Forminator_Assets_Enqueue class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Assets_Enqueue
 *
 * @since 1.11
 */
abstract class Forminator_Assets_Enqueue {
	/**
	 * Model data
	 *
	 * @var Forminator_Base_Form_Model
	 */
	public $model = null;

	/**
	 * Is form loaded with AJAX
	 *
	 * @var bool
	 */
	public $is_ajax_load = false;

	/**
	 * Forminator_Render_Form constructor.
	 *
	 * @param mixed $model Model.
	 * @param bool  $is_ajax_load Is ajax load.
	 *
	 * @since 1.11
	 */
	public function __construct( $model, $is_ajax_load ) {
		$this->model        = $model;
		$this->is_ajax_load = $is_ajax_load;
	}

	/**
	 * Return Form Design
	 *
	 * @since 1.0
	 * @return mixed|string
	 */
	public function get_module_design() {
		$form_settings = $this->get_settings();

		if ( ! isset( $form_settings['form-style'] ) ) {
			return 'default';
		}

		return $form_settings['form-style'];
	}

	/**
	 * Get CSS upload.
	 *
	 * @param int    $id Id.
	 * @param string $type Type.
	 * @param bool   $force Force.
	 * @return string
	 */
	public static function get_css_upload( $id, $type = 'url', $force = false ) {
		$filename = 'style-' . $id . '.css';
		$css_dir  = forminator_get_upload_path( $id, 'css' );
		$css_url  = forminator_get_upload_url( $id, 'css' );
		if ( ! is_dir( $css_dir ) ) {
			wp_mkdir_p( $css_dir );
		}

		// Create Index file.
		Forminator_Field::forminator_upload_index_file( $id, $css_dir );

		$fullname = $css_dir . '/' . $filename;
		if ( $force && ! file_exists( $fullname ) ) {
			Forminator_Render_Form::regenerate_css_file( $id );
		}

		if ( ! empty( $type ) && 'dir' === $type ) {
			$return = $fullname;
		} else {
			$return = $css_url . '/' . $filename;
		}

		return $return;
	}

	/**
	 * Return Form Settins
	 *
	 * @since 1.11
	 * @return mixed
	 */
	public function get_settings() {
		return $this->model->settings;
	}

	/**
	 * Enqueue module styles
	 *
	 * @since 1.11
	 */
	public function load_base_styles() {
		$this->load_module_css();

		// Forminator UI - Icons font.
		wp_enqueue_style(
			'forminator-icons',
			forminator_plugin_url() . 'assets/forminator-ui/css/forminator-icons.min.css',
			array(),
			FORMINATOR_VERSION
		);

		// Forminator UI - Utilities.
		wp_enqueue_style(
			'forminator-utilities',
			forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-utilities.min.css',
			array(),
			FORMINATOR_VERSION
		);
	}

	/**
	 * Load relevant module CSS
	 */
	protected function load_module_css() {
		if ( ! empty( $this->model->id ) && ! is_admin() ) {
			$id        = $this->model->id;
			$timestamp = ! empty( $this->model->raw->post_modified_gmt )
					? strtotime( $this->model->raw->post_modified_gmt )
					: wp_unique_id();

			// Module styles.
			wp_enqueue_style(
				'forminator-module-css-' . $id,
				self::get_css_upload( $id, 'url', true ),
				array(),
				$timestamp
			);
		}
	}

	/**
	 * Load base scripts
	 *
	 * @since 1.11
	 */
	public function load_base_scripts() {
		// LOAD: Forminator validation scripts.
		wp_enqueue_script( 'forminator-jquery-validate', forminator_plugin_url() . 'assets/js/library/jquery.validate.min.js', array( 'jquery' ), FORMINATOR_VERSION, false );

		$slug = 'quiz' !== static::$module_slug ? static::$module_slug : 'ui';
		// LOAD: Forminator UI JS.
		wp_enqueue_script(
			'forminator-' . $slug,
			forminator_plugin_url() . 'assets/forminator-ui/js/forminator-' . $slug . '.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		);

		// LOAD: Forminator front scripts.
		wp_enqueue_script(
			'forminator-front-scripts',
			forminator_plugin_url() . 'build/front/front.multi.min.js',
			array( 'jquery', 'forminator-' . $slug, 'forminator-jquery-validate' ),
			FORMINATOR_VERSION,
			false
		);

		// Localize front script.
		wp_localize_script( 'forminator-front-scripts', 'ForminatorFront', forminator_localize_data() );
	}
}