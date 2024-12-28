<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class WpfdControllerCategory
 */
class WpfdControllerSearch extends Controller
{
    /**
     * Preview category block
     *
     * @return void
     */
    public function previewBlock()
    {

        $data = json_decode(file_get_contents('php://input'));
        
        if (!empty($data) && is_object($data)) {
            $app                    = Application::getInstance('Wpfd');
            $searchAtts             = array(
                'cat_filter'        => $data->cat_filter ? 1 : 0,
                'tag_filter'        => $data->tag_filter ? 1 : 0,
                'display_tag'       => $data->display_tag,
                'create_filter'     => $data->create_filter ? 1 : 0,
                'update_filter'     => $data->update_filter ? 1 : 0,
                'type_filter'       => $data->type_filter ? 1 : 0,
                'weight_filter'     => $data->weight_filter ? 1 : 0,
                'show_filters'      => $data->show_filters ? 1 : 0,
                'file_per_page'     => $data->file_per_page
            );
            $path_helper          = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'WpfdHelperShortcodes.php';
            require_once $path_helper;
            $helper                 = new WpfdHelperShortcodes();
            $searchShortCode        = $helper->wpfdSearchShortcode($searchAtts);

            wp_send_json(array('status' => true, 'html' => $searchShortCode));
        }

        wp_send_json(array('status' => false));
    }
}
