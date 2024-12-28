<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WpfdViewFrontviewer
 */
class WpfdViewFrontviewer extends View
{
    /**
     * Display front viewer
     *
     * @param string $tpl Template name
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $id              = Utilities::getInput('id', 'GET', 'string');
        $catid           = Utilities::getInt('catid');
        $ext             = Utilities::getInput('ext', 'GET', 'string');
        $this->mediaType = Utilities::getInput('type', 'GET', 'string');

        $lists = get_option('wpfd_watermark_category_listing');
        $wmCategoryEnabled = false;
        if (is_array($lists) && !empty($lists)) {
            if (in_array($catid, $lists)) {
                $wmCategoryEnabled = true;
            }
        }

        $app = Application::getInstance('Wpfd');
        if ($wmCategoryEnabled) {
            if (!class_exists('WpfdHelperFolder')) {
                require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelperFolder.php';
            }
            if (!class_exists('WpfdBase')) {
                include_once WPFD_PLUGIN_DIR_PATH . '/app/admin/classes/WpfdBase.php';
            }
            $watermarkedPath = WpfdHelperFolder::getCategoryWatermarkPath();
            if (is_numeric($id)) {
                $metaData = get_post_meta($id, '_wpfd_file_metadata', true);
                $filePath = WpfdBase::getFilesPath($catid) . $metaData['file'];
                $downloadlink = $watermarkedPath . strval($catid) . '_' . strval($id) . '_' . strval(md5($filePath)) . '.png';
            } else {
                $downloadlink = $watermarkedPath . strval($catid) . '_' . strval(md5($id)) . '.png';
            }
            $downloadlink = wpfd_abs_path_to_url($downloadlink);
        } else {
            $downloadlink = wpfd_sanitize_ajax_url($app->getAjaxUrl()) . '&task=file.download&wpfd_file_id=' . $id;
            $downloadlink .= '&wpfd_category_id=' . $catid . '&preview=1';
        }

        $this->downloadLink = $downloadlink;
        $this->mineType     = WpfdHelperFile::mimeType(strtolower($ext));

        wp_enqueue_style(
            'wpfd-mediaelementplayer',
            plugins_url('app/site/assets/css/mediaelementplayer.min.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_script(
            'wpfd-mediaelementplayer',
            plugins_url('app/site/assets/js/mediaelement-and-player.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        parent::render($tpl);
        die();
    }
}
