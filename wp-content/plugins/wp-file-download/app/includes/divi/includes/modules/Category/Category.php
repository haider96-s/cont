<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Utilities;

//-- No direct access
defined('ABSPATH') || die();

class DIVI_WpfdCategory extends ET_Builder_Module
{

    public $slug       = 'divi_wpfd_file_category';
    public $vb_support = 'on';

    public function init()
    {
        $this->name = esc_html__('WPFD File Category', 'wpfd');
    }

    public function get_advanced_fields_config()
    {
        return array(
            'button'       => false,
            'link_options' => false
        );
    }

    public function get_fields()
    {
        return array(
             'category_id' => array(
                'label'             => sprintf(esc_html__('Choose Category', 'wpfd'), '#1'),
                'type'              => 'wpfd_category_button',
                'option_category'   => 'configuration',
                'default_on_front'  => 'root',
                'class'             => 'wpfd-category-module'
            ),
            'category_path' => array(
                'type' => 'hidden',
                'option_category'   => 'configuration',
                'class' => 'wpfd-hidden'
            )
        );
    }

    public function render($attrs, $content, $render_slug)
    {
        $categoryParams = $this->props['category_id'];
        if ($categoryParams !== 'root') {
            $categoryParams = json_decode($categoryParams);
            $catId = $categoryParams->categoryId;
            return do_shortcode('[wpfd_category id="'. $catId .'"]');
        }
    }
}

new DIVI_WpfdCategory;
