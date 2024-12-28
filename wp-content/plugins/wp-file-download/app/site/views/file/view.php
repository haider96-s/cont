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

defined('ABSPATH') || die();

/**
 * Class WpfdViewFile
 */
class WpfdViewFile extends View
{
    /**
     * Display a file
     *
     * @param string $tpl Template name
     *
     * @return mixed|string|void
     */
    public function render($tpl = null)
    {
        $categoryid  = Utilities::getInput('categoryid', 'GET', 'none');
        $idFile      = Utilities::getInput('id', 'GET', 'none');

        $model       = $this->getModel('filefront');
        $modelTokens = $this->getModel('tokens');
        $modelCat    = $this->getModel('categoryfront');
        $file        = null;
        $token       = $modelTokens->getOrCreateNew();

        /**
         * Filter to check category source
         *
         * @param integer Term id
         *
         * @return string
         *
         * @internal
         *
         * @ignore
         */
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $categoryid);
        if ($categoryFrom === 'googleDrive') {
            $file = apply_filters('wpfdAddonGetGoogleDriveFile', $idFile, $categoryid, $token);
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $file = apply_filters('wpfdAddonGetGoogleTeamDriveFile', $idFile, $categoryid, $token);
        } elseif ($categoryFrom === 'dropbox') {
            $file = apply_filters('wpfdAddonGetDropboxFile', $idFile, $categoryid, $token);
        } elseif ($categoryFrom === 'onedrive') {
            $file = apply_filters('wpfdAddonGetOneDriveFile', $idFile, $categoryid, $token);
        } elseif ($categoryFrom === 'onedrive_business') {
            $file = apply_filters('wpfdAddonGetOneDriveBusinessFile', $idFile, $categoryid, $token);
        } elseif ($categoryFrom === 'aws') {
            $file = apply_filters('wpfdAddonGetAwsFile', $idFile, $categoryid, $token);
        } elseif ($categoryFrom === 'nextcloud') {
            $file = apply_filters('wpfdAddonGetNextcloudFile', $idFile, $categoryid, $token);
        }
        if (!$file || ($file === $idFile)) {
            $file  = $model->getFile(Utilities::getInt('id'), Utilities::getInt('rootcat'));
        }

        // Crop file titles
        $rootcat      = Utilities::getInt('rootcat');
        $categorys    = $modelCat->getCategory($categoryid);
        $rootcategory = $modelCat->getCategory($rootcat);
        $file         = (object) ($file);
        if ($rootcat) {
            $file->crop_title = WpfdBase::cropTitle(
                $rootcategory->params,
                $rootcategory->params['theme'],
                $file->post_title
            );
        } else {
            $file->crop_title = WpfdBase::cropTitle($categorys->params, $categorys->params['theme'], $file->post_title);
        }

        if (isset($file->file_custom_icon) && $file->file_custom_icon !== '') {
            if (strpos($file->file_custom_icon, site_url()) !== 0) {
                $file->file_custom_icon = site_url() . $file->file_custom_icon;
            }
        }

        if (!$file || ($file === $idFile)) {
            return wp_json_encode(new stdClass());
        }

        $modelConfig = $this->getModel('configfront');
        $config = $modelConfig->getGlobalConfig();
        $useGeneratedPreview = isset($config['auto_generate_preview']) && intval($config['auto_generate_preview']) === 1 ? true : false;
        $wmCategoryEnabled = false;
        $viewWmInfo = false;
        $lists = get_option('wpfd_watermark_category_listing');
        if (is_array($lists) && !empty($lists)) {
            if (in_array($categoryid, $lists)) {
                $wmCategoryEnabled = true;
            }
        }
        if (is_numeric($file->ID)) {
            $thumbnailFilePath = get_post_meta($file->ID, '_wpfd_thumbnail_image_file_path', true);
            if ($wmCategoryEnabled) {
                $viewWmInfo = get_option('_wpfdAddon_preview_wm_info_' . $file->ID, false);
            }
        } else {
            $id = $idFile;
            if ($categoryFrom === 'onedrive') {
                // Fix the id of onedrive
                $id = str_replace('-', '!', $file->ID);
            }
            $thumbnailFileInfo = get_option('_wpfdAddon_preview_info_' . md5($id), false);
            
            if ($wmCategoryEnabled) {
                $viewWmInfo = get_option('_wpfdAddon_preview_wm_info_' . md5($file->ID), false);
            }

            $thumbnailFilePath = is_array($thumbnailFileInfo) && isset($thumbnailFileInfo['path']) ? $thumbnailFileInfo['path'] : false;
        }

        if ($useGeneratedPreview && $thumbnailFilePath) {
            $thumbnailFilePath = WP_CONTENT_DIR . $thumbnailFilePath;
            if (file_exists($thumbnailFilePath)) {
                $file->thumbnail_url = wpfd_abs_path_to_url($thumbnailFilePath);
            }
        }

        if ($viewWmInfo !== false) {
            $thumbnailFilePath = is_array($viewWmInfo) && isset($viewWmInfo['path']) ? $viewWmInfo['path'] : false;
            $thumbnailFilePath = WP_CONTENT_DIR . $thumbnailFilePath;
            if (file_exists($thumbnailFilePath)) {
                $file->thumbnail_url = wpfd_abs_path_to_url($thumbnailFilePath);
            }
        }

        $file->remote_file = (wpfdRemoteFile($file->ID) === true) ? true : false;

        //fix : access check
        $content       = new stdClass();
        $content->file = $file;

        echo wp_json_encode($content);
        exit();
    }
}
