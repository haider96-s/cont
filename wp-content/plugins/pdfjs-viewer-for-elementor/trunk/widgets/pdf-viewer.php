<?php
namespace PDFjsViewerForElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PDFjs viewer for Elementor
 *
 * Elementor widget for hello world.
 *
 * @since 1.0.0
 */
class PDFjs_Viewer extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'pdfjs-viewer';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'PDF Viewer', 'pdfjs-viewer-for-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-document-file';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'basic' ];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends() {
		return [ 'pdfjs-viewer-for-elementor' ];
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'pdfjs-viewer-for-elementor' ),
			]
		);

		$this->add_control(
			'pdf_file',
			[
				'label' => esc_html__( 'Choose PDF file', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'media_types' => ['application/pdf'],
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);	

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', 'pdfjs-viewer-for-elementor' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pdf_dimension',
			[
				'label' => esc_html__( 'PDF viewer Dimension', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::IMAGE_DIMENSIONS,
				'default' => [
					'width' => '',
					'height' => '',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		echo '<iframe width="' . esc_attr($settings['pdf_dimension']['width']) . '" height="' . esc_attr($settings['pdf_dimension']['height']) . '" src="' . esc_url(plugin_dir_url( __DIR__ ) . '/assets/js/pdfjs/web/viewer.html?file=' . $settings['pdf_file']['url']) . '"></iframe>';
	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function content_template() {
		?>
		<iframe src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . '/assets/js/pdfjs/web/viewer.html?file=') ?>{{{ settings.pdf_file.url }}}" height="{{{ settings.pdf_dimension.height }}}" width="{{{ settings.pdf_dimension.width }}}"></iframe>
		<?php
	}
}
