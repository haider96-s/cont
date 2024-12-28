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

//-- No direct access
defined('ABSPATH') || die();

/**
 * Class WpfdHelperFile
 */
class WpfdHelperFile
{
    /**
     * Convert bytes to size
     *
     * @param integer $bytes     Bytes
     * @param integer $precision Decimal fraction
     *
     * @return string
     */
    public static function bytesToSize($bytes, $precision = 2)
    {
        $sz     = self::getSupportFileMeasure();
        $factor = floor((strlen($bytes) - 1) / 3);
        if ((int) $factor === -1) {
            return esc_html__('N/A', 'wpfd');
        }

        if (is_numeric($bytes)) {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is not problem
            return sprintf('%.' . $precision . 'f', $bytes / pow(1024, $factor)) . ' ' . esc_html__($sz[$factor], 'wpfd');
        }
    }

    /**
     * Get support file measure list
     *
     * @return array
     */
    public static function getSupportFileMeasure()
    {
        return array(
            esc_html__('B', 'wpfd'),
            esc_html__('KB', 'wpfd'),
            esc_html__('MB', 'wpfd'),
            esc_html__('GB', 'wpfd'),
            esc_html__('TB', 'wpfd'),
            esc_html__('PB', 'wpfd')
        );
    }

    /**
     * Get preview url
     *
     * @param string  $id    File id
     * @param integer $catid Category id
     * @param string  $token Token key
     *
     * @return string
     */
    public static function getViewerUrl($id, $catid, $token = '')
    {
        global $wp;
        $app = Application::getInstance('Wpfd');
        list ($id, $catid, $wpfdLang) = wpfd_correct_wpml_language($id, $catid);
        $generatedPreviewUrl = self::getGeneratedPreviewUrl($id, $catid, $token);
        $current_url = home_url($wp->request);
        $check_wpml_dl = false;
        $lang_code = '';
        if (strpos($current_url, '?lang=')) {
            if (defined('ICL_LANGUAGE_CODE')) {
                $check_wpml_dl = true;
                $lang_code = ICL_LANGUAGE_CODE;
            }
        } else {
            if ($wpfdLang !== '') {
                if (strpos($wpfdLang, '/')) {
                    $wpfdLang = str_replace('/', '', $wpfdLang);
                }
                $check_wpml_dl = true;
                $lang_code = $wpfdLang;
            }
        }

        /**
         * Filter to generate preview url
         *
         * @param boolean Generate preview url
         * @param string File id
         * @param string Category id
         * @param string Token
         *
         * @return boolean
         */
        $generatedPreviewUrl = apply_filters('wpfd_generate_preview_url', $generatedPreviewUrl, $id, $catid, $token);

        if (false !== $generatedPreviewUrl) {
            return $generatedPreviewUrl;
        }

        $url = wpfd_sanitize_ajax_url($app->getAjaxUrl()) . 'task=file.download&wpfd_category_id=' . $catid . '&wpfd_file_id=';
        $url .= $id . '&token=' . $token . '&preview=1';
        if ($check_wpml_dl) {
            $url .= '&lang='.$lang_code;
        }
        /**
         * Filter to change preview service url
         *
         * @param string Preview url with %s placeholder for url
         *
         * @return string
         */
        $previewServiceUrl = apply_filters('wpfd_preview_service_url', 'https://docs.google.com/viewer?url=%s&embedded=true');

        /**
         * Filter to change preview url
         *
         * @param string Output url
         * @param string Preview url with %s placeholder for file encoded url
         * @param string Ajax Url to preview file
         *
         * @return string
         */
        return apply_filters('wpfd_preview_url', sprintf($previewServiceUrl, urlencode($url)), $previewServiceUrl, $url);
    }

    /**
     * Get url to open pdf in browser
     *
     * @param string  $id    File id
     * @param integer $catid Category id
     * @param string  $token Token key
     *
     * @return string
     */
    public static function getPdfUrl($id, $catid, $token = '')
    {
        $app = Application::getInstance('Wpfd');
        list ($id, $catid, $wpfdLang) = wpfd_correct_wpml_language($id, $catid);
        $url = wpfd_sanitize_ajax_url($app->getAjaxUrl()) . 'task=file.download&wpfd_category_id=' . $catid . '&wpfd_file_id=';
        $url .= $id . '&token=' . $token;

        return $url;
    }

    /**
     * Get generated preview file
     *
     * @param string       $id    File id
     * @param integer      $catId Category id
     * @param string       $token Token key
     * @param string|mixed $path  Return path
     *
     * @return boolean|string
     */
    public static function getGeneratedPreviewUrl($id, $catId, $token = '', $path = false)
    {
        $app = Application::getInstance('Wpfd');
        $modelConfig = Model::getInstance('configfront');
        $config = $modelConfig->getGlobalConfig();
        $useGeneratedPreview = isset($config['auto_generate_preview']) && intval($config['auto_generate_preview']) === 1 ? true : false;
        $securePreviewFile = isset($config['secure_preview_file']) && intval($config['secure_preview_file']) === 1 ? true : false;
        $watermarkEnabled = self::isWatermarkEnabled();
        $customPreview = '';
        if (is_numeric($id)) {
            $previewFilePath = get_post_meta($id, '_wpfd_preview_file_path', true);
            $metaData = get_post_meta($id, '_wpfd_file_metadata', true);
            $customPreview = isset($metaData['file_custom_icon_preview']) ? $metaData['file_custom_icon_preview'] : '';
        } else {
            // Fix the id of onedrive
            $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catId);
            $previewFileId = ($categoryFrom === 'onedrive' || $categoryFrom === 'onedrive_business') ? str_replace('-', '!', $id) : $id;
            $previewFileInfo = get_option('_wpfdAddon_preview_info_' . md5($previewFileId), false);
            $previewFilePath = is_array($previewFileInfo) && isset($previewFileInfo['path']) ? $previewFileInfo['path'] : false;
            $previewIcon = get_option('_wpfdAddon_file_custom_icon_preview_' . md5($id), false);
            $customPreview = (isset($previewIcon) && !is_null($previewIcon) && $previewIcon !== false) ? $previewIcon : '';
        }

        $previewFilePath = ($previewFilePath && is_string($previewFilePath) && !empty($previewFilePath)) ? WP_CONTENT_DIR . $previewFilePath : '';
        $customPreviewFileLink = ($customPreview && is_string($customPreview) && !empty($customPreview)) ? WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $customPreview : '';

        if (($useGeneratedPreview || $watermarkEnabled) && file_exists($previewFilePath)) {
            if (($watermarkEnabled && !$useGeneratedPreview) || $path) {
                return $previewFilePath;
            }
            if (!$securePreviewFile) {
                return wpfd_abs_path_to_url($previewFilePath);
            } else {
                return sprintf(
                    '%stask=file.preview&wpfd_category_id=%s&wpfd_file_id=%s&token=%s',
                    wpfd_sanitize_ajax_url($app->getAjaxUrl()),
                    $catId,
                    $id,
                    $token
                );
            }
        } elseif ($useGeneratedPreview && !is_null($customPreview) && file_exists($customPreviewFileLink)) {
            return wpfd_abs_path_to_url($customPreviewFileLink);
        }

        return false;
    }

    /**
     * Get watermark type
     *
     * @return false|mixed
     */
    public static function getWatermarkType()
    {
        $config = self::getWatermarkConfig();

        return isset($config['wm_apply_on']) ? $config['wm_apply_on'] : false;
    }

    /**
     * Get watermark config
     *
     * @return array|mixed
     */
    public static function getWatermarkConfig()
    {
        $maybeCachedConfig = wp_cache_get('_wpfd_watermark_config', 'wpfd');
        if (false !== $maybeCachedConfig) {
            return $maybeCachedConfig;
        }

        $defaultConfig = array(
            'wm_enabled' => false,
            'wm_path' => '', // Required. This is relative path start with /wp-content, need to convert to absolute path
            'wm_opacity' => 100,
            /*
             * top_left, top_center, top_right,
             * center_left, center_center, center_right
             * bottom_left, bottom_center, bottom_right
             */
            'wm_position' => 'top_left',
            'wm_size' => 100,
            'wm_margin_unit' => '%', // px, %
            'wm_margin_top' => 0,
            'wm_margin_right' => 0,
            'wm_margin_bottom' => 0,
            'wm_margin_left' => 0,
            'wm_apply_on' => 'product',
            'wm_remove_on_bought' => 0
        );
        $savedOptions = get_option('_wpfd_watermark_config', array());

        $config = array_merge($defaultConfig, $savedOptions);

        wp_cache_set('_wpfd_watermark_config', $config, 'wpfd');

        return $config;
    }

    /**
     * Set watermark config
     *
     * @param array $data Data
     *
     * @return boolean
     */
    public static function setWatermarkConfig($data)
    {
        wp_cache_set('_wpfd_watermark_config', $data, 'wpfd');

        return update_option('_wpfd_watermark_config', $data);
    }

    /**
     * Check watermark enabled
     *
     * @return false|mixed
     */
    public static function isWatermarkEnabled()
    {
        $config = self::getWatermarkConfig();

        return isset($config['wm_enabled']) ? $config['wm_enabled'] : false;
    }

    /**
     * Get media viewer url
     *
     * @param string  $id    File id
     * @param integer $catid Category id
     * @param string  $ext   Extension
     * @param string  $token Download token
     *
     * @return string
     */
    public static function getMediaViewerUrl($id, $catid, $ext = '', $token = '')
    {
        $app = Application::getInstance('Wpfd');
        $modelConfig = Model::getInstance('configfront');
        $config = $modelConfig->getGlobalConfig();
        $useGeneratedPreview = isset($config['auto_generate_preview']) && intval($config['auto_generate_preview']) === 1 ? true : false;

        $imagesType = array('jpg', 'png', 'gif', 'jpeg', 'jpe', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz');
        $videoType  = array(
            'mp4',
            'mpeg',
            'mpe',
            'mpg',
            'mov',
            'qt',
            'rv',
            'avi',
            'movie',
            'flv',
            'webm',
            'ogv'
        );//,'3gp'
        $audioType  = array(
            'mid',
            'midi',
            'mp2',
            'mp3',
            'mpga',
            'ram',
            'rm',
            'rpm',
            'ra',
            'wav'
        );  // ,'aif','aifc','aiff'
        if (in_array($ext, $imagesType)) {
            $type = 'image';
        } elseif (in_array($ext, $videoType)) {
            $type = 'video';
        } elseif (in_array($ext, $audioType)) {
            $type = 'audio';
        } else {
            $type = '';
        }
        list ($id, $catid, $wpfdLang) = wpfd_correct_wpml_language($id, $catid);

        $lists = get_option('wpfd_watermark_category_listing');
        $wmCategoryEnabled = false;
        if (is_array($lists) && !empty($lists)) {
            if (in_array($catid, $lists)) {
                $wmCategoryEnabled = true;
            }
        }
        
        if (!class_exists('WpfdHelperFolder')) {
            require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelperFolder.php';
        }
        $watermarkedPath = WpfdHelperFolder::getCategoryWatermarkPath();
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catid);
        if (is_numeric($id) && empty($categoryFrom)) {
            $metaData = get_post_meta($id, '_wpfd_file_metadata', true);
            $customPreview = isset($metaData['file_custom_icon_preview']) ? $metaData['file_custom_icon_preview'] : '';

            $filePath = WpfdBase::getFilesPath($catid) . $metaData['file'];
            $watermarkedPath = $watermarkedPath . strval($catid) . '_' . strval($id) . '_' . strval(md5($filePath)) . '.png';
        } else {
            $previewIcon = get_option('_wpfdAddon_file_custom_icon_preview_' . md5($id), false);
            $customPreview = (isset($previewIcon) && !is_null($previewIcon) && $previewIcon !== false) ? $previewIcon : '';

            $watermarkedPath = $watermarkedPath . strval($catid) . '_' . strval(md5($id)) . '.png';
        }
        $customPreviewFileLink = ($customPreview && is_string($customPreview) && !empty($customPreview)) ? WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $customPreview : '';
        if ($type === 'image' && $useGeneratedPreview && !is_null($customPreview) && file_exists($customPreviewFileLink)) {
            return wpfd_abs_path_to_url($customPreviewFileLink);
        } elseif ($type === 'image' && $wmCategoryEnabled && file_exists($watermarkedPath)) {
            return wpfd_abs_path_to_url($watermarkedPath);
        } else {
            return wpfd_sanitize_ajax_url($app->getAjaxUrl()) . 'task=frontviewer.display&view=frontviewer&id=' . $id . '&catid=' . $catid . '&type=' . $type . '&ext=' . $ext;
        }
    }

    /**
     * Check if it is media file
     *
     * @param string $ext Extension
     *
     * @return boolean
     */
    public static function isMediaFile($ext)
    {
        $media_arr = array(
            'mid',
            'midi',
            'mp2',
            'mp3',
            'mpga',
            'ram',
            'rm',
            'rpm',
            'ra',
            'wav', //,'aif','aifc','aiff'
            'm4a',
            'mp4',
            'mpeg',
            'mpe',
            'mpg',
            'mov',
            'qt',
            'rv',
            'avi',
            'movie',
            'flv',
            'webm',
            'ogv', //'3gp',
            'jpg',
            'png',
            'gif',
            'jpeg',
            'jpe',
            'bmp',
            'ico',
            'tiff',
            'tif',
            'svg',
            'svgz'
        );
        if (in_array(strtolower($ext), $media_arr)) {
            return true;
        }

        return false;
    }


    /**
     * Get mime type
     *
     * @param string $ext Extension
     *
     * @return string
     */
    public static function mimeType($ext)
    {
        $mime_types = array(
            //flash
            'swf'   => 'application/x-shockwave-flash',
            'flv'   => 'video/x-flv',
            // images
            'png'   => 'image/png',
            'jpe'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'jpg'   => 'image/jpeg',
            'gif'   => 'image/gif',
            'bmp'   => 'image/bmp',
            'ico'   => 'image/vnd.microsoft.icon',
            'tiff'  => 'image/tiff',
            'tif'   => 'image/tiff',
            'svg'   => 'image/svg+xml',
            'svgz'  => 'image/svg+xml',

            // audio
            'mid'   => 'audio/midi',
            'midi'  => 'audio/midi',
            'mp2'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'mpga'  => 'audio/mpeg',
            'aif'   => 'audio/x-aiff',
            'aifc'  => 'audio/x-aiff',
            'aiff'  => 'audio/x-aiff',
            'ram'   => 'audio/x-pn-realaudio',
            'rm'    => 'audio/x-pn-realaudio',
            'rpm'   => 'audio/x-pn-realaudio-plugin',
            'ra'    => 'audio/x-realaudio',
            'wav'   => 'audio/x-wav',
            'wma'   => 'audio/wma',
            'm4a'   => 'audio/m4a',

            //Video
            'mp4'   => 'video/mp4',
            'mpeg'  => 'video/mpeg',
            'mpe'   => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'mov'   => 'video/quicktime',
            'qt'    => 'video/quicktime',
            'rv'    => 'video/vnd.rn-realvideo',
            'avi'   => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',
            '3gp'   => 'video/3gpp',
            'webm'  => 'video/webm',
            'ogv'   => 'video/ogg',
            //doc
            'pdf'   => 'application/pdf'
        );

        if (array_key_exists(strtolower($ext), $mime_types)) {
            return $mime_types[strtolower($ext)];
        } else {
            return 'application/octet-stream';
        }
    }

    /**
     * Get mime type
     *
     * @param string $ext     Extenstion
     * @param string $fileExt Extenstion
     *
     * @return string
     */
    public static function isCorrectMimeType($ext, $fileExt)
    {
        $ext = strtolower($ext);
        if (empty($ext)) {
            return false;
        }

        $mime_types_map = array(
            'application/x-msdownload' => 'exe',
            'application/x-dosexec'    => array('exe', 'dll')
        );

        if (isset($mime_types_map[$fileExt])) {
            if ($mime_types_map[$fileExt] === $ext || in_array($ext, $mime_types_map[$fileExt])) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }


    /**
     * Check mime file type
     *
     * @param string $file File
     *
     * @return boolean
     */
    public static function checkMimeType($file)
    {
        if (!function_exists('finfo_open') || !function_exists('finfo_file')) {
            return true;
        }
        $ext          = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $file_info    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeFileInfo = finfo_file($file_info, $file);
        finfo_close($file_info);

        // Always return true for mising mimetype
        // Some server or php version always return application/octet-stream
        if (isset($mimeFileInfo) && $mimeFileInfo !== null) {
            return self::isCorrectMimeType($ext, $mimeFileInfo);
        }

        return true;
    }

    /**
     * Search assets
     *
     * @return void
     */
    public static function wpfdAssets()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_style(
            'jquery-ui-1.9.2',
            plugins_url('app/admin/assets/css/ui-lightness/jquery-ui-1.9.2.custom.min.css', WPFD_PLUGIN_FILE)
        );
        wp_enqueue_style('dashicons');

        wp_enqueue_script(
            'wpfd-videojs',
            plugins_url('app/site/assets/js/video.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-videojs',
            plugins_url('app/site/assets/css/video-js.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-colorbox',
            plugins_url('app/site/assets/css/colorbox.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-viewer',
            plugins_url('app/site/assets/css/viewer.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
    }

    /**
     * Search access
     *
     * @return void
     */
    public static function wpfdAssetsSearch()
    {
        wp_enqueue_style('wpfd-jquery-tagit', plugins_url('app/admin/assets/css/jquery.tagit.css', WPFD_PLUGIN_FILE));
        wp_enqueue_style(
            'wpfd-datetimepicker',
            plugins_url('app/site/assets/css/jquery.datetimepicker.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-search_filter',
            plugins_url('app/site/assets/css/search_filter.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        if (!is_admin()) {
            wp_enqueue_script('wpfd-jquery-tagit', plugins_url('app/admin/assets/js/jquery.tagit.js', WPFD_PLUGIN_FILE));
            wp_enqueue_script(
                'wpfd-datetimepicker',
                plugins_url('app/site/assets/js/jquery.datetimepicker.js', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );
            wp_enqueue_script(
                'wpfd-search_filter',
                plugins_url('app/site/assets/js/search_filter.js', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );
        }
        Application::getInstance('Wpfd');
        $modelConfig  = Model::getInstance('configfront');
        $globalConfig = $modelConfig->getGlobalConfig();
        $searchconfig = $modelConfig->getSearchConfig();
        $locale       = substr(get_locale(), 0, 2);
        wp_localize_script(
            'wpfd-search_filter',
            'wpfdvars',
            array(
                'dateFormat' => $globalConfig['date_format'],
                'locale'     => $locale
            )
        );
    }

    /**
     * Download Large File
     *
     * @param string  $filePath         File path
     * @param boolean $deleteWhenFinish Delete file when finish
     *
     * @return void
     */
    public static function downloadLargeFile($filePath, $deleteWhenFinish = false)
    {
        // phpcs:disable Generic.PHP.NoSilencedErrors.Discouraged,WordPress.Security.EscapeOutput.OutputNotEscaped -- not print any error to file content
        @ini_set('error_reporting', E_ALL & ~E_NOTICE);
        @ini_set('zlib.output_compression', 'Off');

        $chunksize = 1 * (1024 * 1024);
        if (file_exists($filePath)) {
            @set_time_limit(0);
            $size = intval(sprintf('%u', filesize($filePath)));
            if ($size > $chunksize) {
                $handle = fopen($filePath, 'rb');
                while (!feof($handle)) {
                    print(@fread($handle, $chunksize));
                    ob_flush();
                    flush();
                }
                fclose($handle);
            } else {
                readfile($filePath);
            }
            if ($deleteWhenFinish) {
                unlink($filePath);
            }
            exit;
        } else {
            exit(sprintf(esc_html('File "%s" does not exist!'), $filePath));
        }
        // phpcs:enable
    }

    /**
     * Send Download File to the browser
     *
     * @param string  $filePath         Absolute path to the file
     * @param string  $fileName         File name return to Browser
     * @param string  $fileExt          File extension for check it mime
     * @param boolean $preview          Is preview
     * @param boolean $openPdfInBrowser Is open in browser
     *
     *
     * Copyright 2012 Armand Niculescu - media-division.com
     * Redistribution and use in source and binary forms, with or without modification,
     * are permitted provided that the following conditions are met:
     * 1. Redistributions of source code must retain the above copyright notice,
     * this list of conditions and the following disclaimer.
     * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
     * following disclaimer in the documentation and/or other materials provided with the distribution.
     * THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING,
     * BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
     * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
     * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT
     * OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
     * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
     * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
     *
     * @return boolean|void
     */
    public static function sendDownloadFallback($filePath, $fileName, $fileExt, $preview = false, $openPdfInBrowser = false)
    {
        // phpcs:disable Generic.PHP.NoSilencedErrors.Discouraged,WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.Security.NonceVerification.Recommended -- not print any error to file content, output is file content, $_REQUEST['stream'] is checking condition
        @ini_set('error_reporting', E_ALL & ~E_NOTICE);
        @ini_set('zlib.output_compression', 'Off');
        $isAttachment = isset($_REQUEST['stream']) ? false : true;
        if ($openPdfInBrowser && strtolower($fileExt) === 'pdf' && $preview) {
            $isAttachment = false;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        // make sure the file exists on server
        if (is_file($filePath)) {
            $fileSize    = filesize($filePath);
            $fileHandler = @fopen($filePath, 'rb');
            if ($fileHandler) {
                // set the headers, prevent caching
                header('Pragma: public');
                header('Expires: -1');
                /**
                 * Filter to add X-Robots-Tag to download link
                 *
                 * @param boolean
                 */
                if (apply_filters('wpfd_nofollow_noindex_header', true)) {
                    header('X-Robots-Tag: noindex, nofollow', true);
                }
                header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
                // set appropriate headers for attachment or streamed file
                if ($isAttachment) {
                    header('Content-Disposition: attachment; filename="' . $fileName . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
                } else {
                    header('Content-Disposition: inline; filename="' . $fileName . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
                }

                $contentType = self::mimeType($fileExt);
                $contentTypeSetted = false;
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $agent = $_SERVER['HTTP_USER_AGENT'];
                    if (strlen(strstr($agent, 'Firefox')) > 0 && $contentType === 'application/pdf' && !$preview) {
                        header('Content-Type: application/force-download; charset=utf-8');
                        $contentTypeSetted = true;
                    }
                }
                if (!$contentTypeSetted) {
                    header('Content-Type: ' . $contentType);
                }
                // check if http_range is sent by browser (or download manager)
                // todo: Apply multiple ranges
                $range = '0-' . $fileSize;
                if (isset($_SERVER['HTTP_RANGE'])) {
                    list($sizeUnit, $rangeOrig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                    if ($sizeUnit === 'bytes') {
                        // multiple ranges could be specified at the same time,
                        // but for simplicity only serve the first range
                        // http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                        $ranges = explode(',', $rangeOrig, 2);
                        if (is_array($ranges) && count($ranges) === 2) {
                            list($range, $extraRanges) = explode(',', $rangeOrig, 2);
                        }
                    } else {
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');

                        return false;
                    }
                }
                // figure out download piece from range (if set)
                list($seekStart, $seekEnd) = explode('-', $range, 2);
                // set start and end based on range (if set), else set defaults
                // also check for invalid ranges.
                $seekEnd   = (empty($seekEnd)) ? ($fileSize - 1) : min(abs(intval($seekEnd)), ($fileSize - 1));
                $seekStart = (empty($seekStart) || $seekEnd < abs(intval($seekStart))) ? 0 : max(abs(intval($seekStart)), 0);
                // Only send partial content header if downloading a piece of the file (IE workaround)
                if ($seekStart > 0 || $seekEnd < ($fileSize - 1)) {
                    header('HTTP/1.1 206 Partial Content');
                    header('Content-Range: bytes ' . $seekStart . '-' . $seekEnd . '/' . $fileSize);
                    if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false)) {
                        header('Content-Length: ' . ($seekEnd - $seekStart + 1));
                    }
                } else {
                    if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false)) {
                        header('Content-Length: ' . $fileSize);
                    }
                }
                header('Accept-Ranges: bytes');
                @set_time_limit(0);
                fseek($fileHandler, $seekStart);
                while (!feof($fileHandler)) {
                    print(@fread($fileHandler, 1024 * 8));
                    @ob_flush();
                    flush();
                    if (connection_status() !== 0) {
                        @fclose($fileHandler);

                        return true;
                    }
                }
                // File save was a success
                @fclose($fileHandler);

                return true;
            } else {
                // File couldn't be opened
                header('HTTP/1.0 500 Internal Server Error');

                return false;
            }
        } else {
            // File does not exist
            header('HTTP/1.0 404 Not Found');

            return false;
        }
        // phpcs:enable
    }

    /**
     * Send download
     *
     * @param string  $filePath         File path
     * @param string  $fileName         File name
     * @param string  $fileExt          File extension
     * @param boolean $preview          Preview file
     * @param boolean $openPdfInBrowser Open preview type
     *
     * @return void|boolean
     */
    public static function sendDownload($filePath, $fileName, $fileExt, $preview = false, $openPdfInBrowser = false)
    {
        if (!is_file($filePath)) {
            header('HTTP/1.0 404 Not Found');
            return false;
        }

        Application::getInstance('Wpfd');
        $modelConfig  = Model::getInstance('configfront');
        $globalConfig = $modelConfig->getGlobalConfig();
        if ($globalConfig &&
            isset($globalConfig['use_xsendfile']) &&
            (int)$globalConfig['use_xsendfile'] === 1 &&
            function_exists('apache_get_modules') &&
            in_array('mod_xsendfile', apache_get_modules(), true)
        ) {
            self::downloadHeaders($filePath, $fileName, $preview);
            $filepath = apply_filters('wpfd_download_file_xsendfile_file_path', $filePath, $filePath, $fileName);
            header('X-Sendfile: ' . $filepath);
        } else {
            // Fallback.
            self::sendDownloadFallback($filePath, $fileName, $fileExt, $preview, $openPdfInBrowser);
        }

        return true; // DONOT exits or email notification won't work
    }
    /**
     * Set headers for the download.
     *
     * @param string $file_path File path.
     * @param string $filename  File name.
     * @param array  $preview   Inline header for preview files
     *
     * @return void
     */
    private static function downloadHeaders($file_path, $filename, $preview = false)
    {
        self::checkServerConfig();
        self::cleanBuffers();
        /**
         * Filter to add X-Robots-Tag to download link
         *
         * @param boolean
         *
         * @ignore
         */
        if (apply_filters('wpfd_nofollow_noindex_header', true)) {
            header('X-Robots-Tag: noindex, nofollow', true);
        }
        $contentType = self::getDownloadContentType($file_path);
        $contentTypeSetted = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            if (strlen(strstr($agent, 'Firefox')) > 0 && $contentType === 'application/pdf' && !$preview) {
                header('Content-Type: application/force-download; charset=utf-8');
                $contentTypeSetted = true;
            }
        }
        if (!$contentTypeSetted) {
            header('Content-Type: ' . $contentType);
        }
        header('Content-Description: File Transfer');
        if ($preview) {
            header('Content-Disposition: inline; filename="' . $filename . '"; filename*=UTF-8\'\'' . $filename);
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . $filename);
        }

        header('Content-Transfer-Encoding: binary');

        $file_size = @filesize($file_path); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        if (!$file_size) {
            return;
        }
        header('Content-Length: ' . $file_size);
    }
    /**
     * Check and set certain server config variables to ensure downloads work as intended.
     *
     * @return void
     */
    private static function checkServerConfig()
    {
        $limit = 0;
        // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved -- This is for checking
        if (function_exists('set_time_limit') && false === strpos(ini_get('disable_functions'), 'set_time_limit') && !ini_get('safe_mode')) {
            // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- This is for checking
            @set_time_limit($limit);
        }
        
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_apache_setenv
        }
        @ini_set('zlib.output_compression', 'Off'); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
        @session_write_close(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.VIP.SessionFunctionsUsage.session_session_write_close
    }

    /**
     * Clean all output buffers.
     *
     * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
     *
     * @return void
     */
    private static function cleanBuffers()
    {
        if (ob_get_level()) {
            $levels = ob_get_level();
            for ($i = 0; $i < $levels; $i ++) {
                @ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
            }
        } else {
            @ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        }
    }
    /**
     * Get content type of a download.
     *
     * @param string $file_path File path.
     *
     * @return string
     */
    private static function getDownloadContentType($file_path)
    {
        $file_extension = strtolower(substr(strrchr($file_path, '.'), 1));
        $ctype          = 'application/force-download';

        foreach (get_allowed_mime_types() as $mime => $type) {
            $mimes = explode('|', $mime);
            if (in_array($file_extension, $mimes, true)) {
                $ctype = $type;
                break;
            }
        }

        return $ctype;
    }
    /**
     * Santize File Name for download
     *
     * @param string $fileName File name
     *
     * @return string
     */
    public static function santizeFileName($fileName)
    {
        $wpfd_custom_santize_file_name = apply_filters('wpfd_custom_santize_file_name', false);
        if ($wpfd_custom_santize_file_name) {
            return preg_replace('([^\w\s\d\-_~,;\[\]\(\).])', '', $fileName);
        }
        
        if (function_exists('sanitize_file_name')) {
            return sanitize_file_name($fileName);
        } elseif (function_exists('mb_ereg_replace')) {
            return mb_ereg_replace('([^\w\s\d\-_~,;\[\]\(\).])', '', $fileName);
        } else {
            return preg_replace('([^\w\s\d\-_~,;\[\]\(\).])', '', $fileName);
        }
    }

    /**
     * Check access for single file
     *
     * @param array $file File
     *
     * @return boolean
     */
    public static function checkAccess($file)
    {
        $user = wp_get_current_user();
        Application::getInstance('Wpfd');
        //check access
        $modelCategory = Model::getInstance('categoryfront');
        $configModel   = Model::getInstance('configfront');
        $config        = array();
        if (method_exists($configModel, 'getGlobalConfig')) {
            $config = $configModel->getGlobalConfig();
        } elseif (method_exists($configModel, 'getConfig')) {
            $config = $configModel->getConfig();
        }
        $category = $modelCategory->getCategory($file['catid']);

        if (empty($category) || is_wp_error($category)) {
            return false;
        }

        if ((int) $category->access === 1) {
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows        = array_intersect($roles, $category->roles);
            $allows_single = false;

            if (isset($category->params['canview']) && $category->params['canview'] === '') {
                $category->params['canview'] = 0;
            }
            // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
            if (isset($category->params['canview']) && ((int) $category->params['canview'] !== 0) && is_countable($category->roles) &&
                !count($category->roles)) {
                if ((int) $category->params['canview'] === (int) $user->ID) {
                    $allows_single = true;
                }
                if ($allows_single === false) {
                    return false;
                }
                // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
            } elseif (isset($category->params['canview']) && ((int) $category->params['canview'] !== 0) && is_countable($category->roles) &&
                      count($category->roles)) {
                if ((int) $category->params['canview'] === (int) $user->ID) {
                    $allows_single = true;
                }
                if ($allows_single === false && empty($allows)) {
                    return false;
                }
            } else {
                if (empty($allows)) {
                    return false;
                }
            }
        }

        // Check single user permission
        if ((int) WpfdBase::loadValue($config, 'restrictfile', 0) === 1) {
            $canview = isset($file['canview']) ? $file['canview'] : 0;
            $canview = array_map('intval', explode(',', $canview));
            if (!in_array($user->ID, $canview) && !in_array(0, $canview)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add statistics row
     *
     * @param string $fid  File id
     * @param string $type Statistic type
     *
     * @return void
     */
    public static function addStatisticsRow($fid, $type = 'default')
    {
        global $wpdb;
        $date          = date('Y-m-d');
        $currentUserId = 0;
        Application::getInstance('Wpfd');
        $modelConfig = Model::getInstance('configfront');
        if (method_exists($modelConfig, 'getGlobalConfig')) {
            $params = $modelConfig->getGlobalConfig();
        } elseif (method_exists($modelConfig, 'getConfig')) {
            $params = $modelConfig->getConfig();
        }

        if (isset($params)) {
            if (!class_exists('WpfdBase')) {
                include_once WPFD_PLUGIN_DIR_PATH . '/app/admin/classes/WpfdBase.php';
            }
            $trackUserDownload = (int) WpfdBase::loadValue($params, 'track_user_download', 0);

            // Check tracking user downloading
            if ($trackUserDownload === 1) {
                $currentUserId = get_current_user_id();
            }
        }

        $object = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . $wpdb->prefix . 'wpfd_statistics WHERE related_id=%s AND date=%s AND type=%s AND uid=%d',
            $fid,
            $date,
            $type,
            (int) $currentUserId
        ));

        if ($object) {
            $wpdb->query($wpdb->prepare(
                'UPDATE ' . $wpdb->prefix . 'wpfd_statistics SET count=(count+1) WHERE related_id=%s AND date=%s AND type=%s AND uid=%d',
                $fid,
                $date,
                $type,
                (int) $currentUserId
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                'INSERT INTO ' . $wpdb->prefix . 'wpfd_statistics (related_id, uid, type, date, count) VALUES (%s, %d, %s, %s, 1)',
                $fid,
                (int) $currentUserId,
                $type,
                $date
            ));
        }
    }
    /**
     * Check and get icon path if exists
     *
     * @param string $extension Extension to get
     * @param string $set       Set icon type
     *
     * @return boolean|string
     */
    public static function getDefaultIconPath($extension, $set = 'png')
    {
        $siteAssetsPath = WPFD_PLUGIN_DIR_PATH . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        $defaultIconsPath = $siteAssetsPath . 'icons';

        switch ($set) {
            case 'png':
                $iconsPath = $defaultIconsPath . DIRECTORY_SEPARATOR . 'png' . DIRECTORY_SEPARATOR;
                $filePath  = $iconsPath . $extension . '.png';
                if (file_exists($filePath)) {
                    return $filePath;
                } else {
                    $iconsPath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'png' . DIRECTORY_SEPARATOR;
                    $filePath  = $iconsPath . 'unknown.png';
                    if (file_exists($filePath)) {
                        return $filePath;
                    }
                    $iconsPath = $defaultIconsPath . DIRECTORY_SEPARATOR . 'png' . DIRECTORY_SEPARATOR;
                    $filePath  = $iconsPath . 'unknown.png';
                    if (file_exists($filePath)) {
                        return $filePath;
                    }
                }
                break;
            case 'svg':
                $iconsPath = $defaultIconsPath . DIRECTORY_SEPARATOR . 'svg' . DIRECTORY_SEPARATOR;
                $filePath  = $iconsPath . $extension . '.svg';
                if (file_exists($filePath)) {
                    return $filePath;
                }
                break;
//            case 'svg2':
//                $iconsPath = $defaultIconsPath . DIRECTORY_SEPARATOR . 'svg2' . DIRECTORY_SEPARATOR;
//                $filePath  = $iconsPath . $extension . '.svg';
//                if (file_exists($filePath)) {
//                    return $filePath;
//                }
//                break;
            default:
                $iconsPath = $siteAssetsPath . 'images' . DIRECTORY_SEPARATOR. 'theme' . DIRECTORY_SEPARATOR;
                $filePath  = $iconsPath . $extension . '.png';
                if (file_exists($filePath)) {
                    return $filePath;
                }
                return false;
        }
        return false;
    }

    /**
     * Get uploaded icon path
     *
     * @param string  $extension Extension to get
     * @param string  $set       Set icon type
     * @param boolean $url       Retun Url
     *
     * @return boolean|string
     */
    public static function getUploadedIconPath($extension, $set = 'png', $url = true)
    {
        $iconPath = self::getCustomIconPath($set) . $extension . '.' . preg_replace('/[0-9]+/', '', $set);
        if (file_exists($iconPath)) {
            if ($url) {
                return wpfd_abs_path_to_url($iconPath);
            } else {
                return $iconPath;
            }
        }

        $iconPath = self::getDefaultIconPath($extension, $set);
        if (file_exists($iconPath)) {
            if ($url) {
                return wpfd_abs_path_to_url($iconPath);
            } else {
                return $iconPath;
            }
        }

        return false;
    }
    /**
     * Get icons urls
     *
     * @param string $extension Extension to get
     * @param string $set       Set icon type
     *
     * @return array
     */
    public static function getIconUrls($extension, $set = 'png')
    {
        $output = array(
            'default' => '',
            'uploaded' => '',
        );
        $iconPath = self::getCustomIconPath($set) . $extension . '.' . preg_replace('/[0-9]+/', '', $set);
        if (file_exists($iconPath)) {
            $output['uploaded'] = wpfd_abs_path_to_url($iconPath);
        }

        $iconPath = self::getDefaultIconPath($extension, $set);

        if (file_exists($iconPath)) {
            $output['default'] = wpfd_abs_path_to_url($iconPath);
        }
        if ($output['uploaded'] !== '' || $output['default'] !== '') {
            return $output;
        }

        return false;
    }
    /**
     * Get custom icon path
     *
     * @param string $set Icon set name
     *
     * @return string
     */
    public static function getCustomIconPath($set)
    {
        $path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . $set . DIRECTORY_SEPARATOR;
        wpfdCreateSecureFolder($path);
        return $path;
    }

    /**
     * Render Icons set css
     *
     * @return integer
     */
    public static function renderCSS()
    {
        Application::getInstance('Wpfd');
        /* @var WpfdModelConfig $configModel */
        $configModel = Model::getInstance('configfront');
        /* @var WpfdModelIconsBuilder $iconBuilderModel */
        $iconBuilderModel = Model::getInstance('iconsbuilder');
        $svgParams = $iconBuilderModel->getParams('svg');
        $extension = $configModel->getAllowedExt();
        $iconSets = array('png','svg');
        $rebuildTime = time();
        $svgParams = isset($svgParams['icons']) ? $svgParams['icons'] : null;
        foreach ($iconSets as $set) {
            $path = self::getCustomIconPath($set);
            // Remove unused css
            $cssFiles = glob($path . '*.css');
            foreach ($cssFiles as $file) {
                unlink($file);
            }

            $css = '';
            if ($set === 'png') {
                $unknownPng = self::getIconUrls('unknown', 'png');
                if (false !== $unknownPng) {
                    if (isset($unknownPng['uploaded']) && $unknownPng['uploaded'] !== '') {
                        $defaultUrl = $unknownPng['uploaded'];
                    } elseif (isset($unknownPng['default']) && $unknownPng['default'] !== '') {
                        $defaultUrl = $unknownPng['default'];
                    } else {
                        $defaultUrl = wpfd_abs_path_to_url(WPFD_PLUGIN_DIR_PATH . 'app/site/assets/icons/png/default.png');
                    }
                    $css .= '.wpfd-icon-set-' . $set . '.ext{background: url(' . $defaultUrl . ') no-repeat center center}';
                }
            }

            foreach ($extension as $ext) {
                $iconUrl = self::getUploadedIconPath($ext, $set);
                if (false !== $iconUrl) {
                    $css .= '.wpfd-icon-set-' . $set . '.ext.ext-' . $ext . '{background: url(' . $iconUrl . '?version=' . $rebuildTime . ') no-repeat center center;';
                    if ($set === 'svg' && !is_null($svgParams)) {
                        if (isset($svgParams['wpfd-icon-' . $ext])) {
                            $svgParam = $svgParams['wpfd-icon-' . esc_attr($ext)];
                            if (isset($svgParam['wrapper-active']) && intval($svgParam['wrapper-active']) === 1) {
                                // box-shadow
                                $css .= isset($svgParam['border-radius']) && intval($svgParam['border-radius']) > 0 ? 'border-radius: ' . $svgParam['border-radius'] . '%;' : '';
                                // border
                                $css .= 'border: ' . $svgParam['border-size'] . 'px solid ' . $svgParam['border-color'] . ';';
                                $css .= 'box-shadow: ' . $svgParam['horizontal-position'] . 'px ' . $svgParam['vertical-position'] . 'px ' . $svgParam['blur-radius'] . 'px ' . $svgParam['spread-radius'] . 'px ' . $svgParam['shadow-color'] . ';';
                                // background-color
                                $css .= 'background-color: ' . $svgParam['background-color'] . ';';
                            }
                        }
                    }
                    $css .= '}';
                }
            }

            // Save file
            file_put_contents($path . 'styles-' . $rebuildTime . '.css', $css);
        }
        update_option('wpfd_icon_rebuild_time', $rebuildTime);

        return $rebuildTime;
    }

    /**
     * Check expiration date for file.
     * This function can return the number of seconds to the expired date.
     *
     * @param integer $id File id
     *
     * @return boolean|integer  True on file expired.
     *                          False when file expired not set or failed.
     *                          If file expired date set and not expiry yet, the seconds from now to expired date will return
     */
    public static function wpfdIsExpired($id)
    {
        try {
            $expiresGMT = get_post_meta($id, '_wpfd_file_meta_expiration_date', true);
            $format = 'Y-m-d H:i:s';

            if (!empty($expiresGMT)) {
                $currentDate = new DateTime();
                $currentDate->setTimezone(new DateTimeZone('GMT'));

                // Generate Expiration in DateTime
                $expirationDate = DateTime::createFromFormat(
                    $format,
                    $expiresGMT
                );

                $secondsLeft = $expirationDate->getTimestamp() - $currentDate->getTimestamp();

                if ($secondsLeft < 0) {
                    return true;
                }

                // Return seconds left
                return $secondsLeft;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Download limit file handle
     *
     * @param string|integer $id    File id
     * @param string|integer $catid Category id
     *
     * @return boolean
     */
    public static function downloadLimitHandle($id, $catid)
    {
        Application::getInstance('Wpfd');
        $modelConfig = Model::getInstance('configfront');
        $config = $modelConfig->getGlobalConfig();
        $isLimitDownload = (isset($config['limit_the_download']) && intval($config['limit_the_download']) === 1
            && isset($config['track_user_download']) && intval($config['track_user_download']) === 1) ? true : false;
        $userLoggedIn = is_user_logged_in();
        $currentUser = wp_get_current_user();
        $currentUserRoles = isset($currentUser->roles) ? $currentUser->roles : array();
        $downloadLimitSettings = isset($config['download_limit_settings']) ? (array) $config['download_limit_settings'] : array();

        if (empty($id) || empty($catid)) {
            return false;
        }

        if (!$isLimitDownload) {
            return false;
        }

        if (!$userLoggedIn || empty($downloadLimitSettings)) {
            return false;
        }

        $roleName = isset($currentUserRoles[0]) ? $currentUserRoles[0] : '';
        if (!isset($downloadLimitSettings[$roleName])) {
            return false;
        }
        $roleSetting = $downloadLimitSettings[$roleName];
        $limitDownloadNumber = isset($roleSetting['limit_download_number']) ? $roleSetting['limit_download_number'] : '';
        $limitDownloadTimeNumber = isset($roleSetting['limit_download_time_number']) ? $roleSetting['limit_download_time_number'] : '';
        $limitDownloadTimeType = isset($roleSetting['limit_download_time_type']) ? $roleSetting['limit_download_time_type'] : '';

        if ($limitDownloadNumber === '' || $limitDownloadTimeNumber === ''
            || $limitDownloadTimeType === '') {
            return false;
        }

        $userLimitDownload = get_user_meta($currentUser->ID, 'wpfd_user_download_limit', true);
        $currentTime = time();
        $expiredTime = isset($userLimitDownload['time']) ? intval($userLimitDownload['time']) : 0;
        $userLimitTimeSetting = isset($userLimitDownload['limit_download_time_setting']) ? intval($userLimitDownload['limit_download_time_setting']) : 0;
        $userLimitTimeTypeSetting = isset($userLimitDownload['limit_download_time_type_setting']) ? $userLimitDownload['limit_download_time_type_setting'] : 'hour';
        $isChanged = (($userLimitTimeSetting && $userLimitTimeSetting !== intval($limitDownloadTimeNumber))
            || ($userLimitTimeTypeSetting !== $limitDownloadTimeType)) ? true : false;
        $limitTime = self::convertTimer($currentTime, $limitDownloadTimeNumber, $limitDownloadTimeType);

        if (empty($userLimitDownload) || intval($expiredTime) <= intval($currentTime)) {
            $userLimitDownload = array();
            $userLimitDownload['start_download'] = intval($currentTime);
            $userLimitDownload['limit_download_time_setting'] = intval($limitDownloadTimeNumber);
            $userLimitDownload['limit_download_time_type_setting'] = $limitDownloadTimeType;
            $userLimitDownload['file_ids'] = array($id);
            $userLimitDownload['time'] = $limitTime;
            $userLimitDownload['counter'] = 1;
            $userLimitDownload['expired'] = false;
            update_user_meta($currentUser->ID, 'wpfd_user_download_limit', $userLimitDownload);

            return false;
        } else {
            $downloadedFileIds = isset($userLimitDownload['file_ids']) ? (array) $userLimitDownload['file_ids'] : array();
            $counter = isset($userLimitDownload['counter']) ? intval($userLimitDownload['counter']) : 0;
            $userStartDownload = isset($userLimitDownload['start_download']) ? intval($userLimitDownload['start_download']) : 0;
            $userLimitDownload['limit_download_time_setting'] = intval($limitDownloadTimeNumber);
            $userLimitDownload['limit_download_time_type_setting'] = $limitDownloadTimeType;

            if (in_array($id, $downloadedFileIds) || $expiredTime === 0
                || $counter === 0) {
                return false;
            }

            if ($isChanged) {
                $limitTime2 = self::convertTimer($userStartDownload, $limitDownloadTimeNumber, $limitDownloadTimeType);
                $userLimitDownload['time'] = (intval($limitTime2) > intval($currentTime)) ? $limitTime2 : $currentTime;
            }

            $counter = ++$counter;
            if ($counter > intval($limitDownloadNumber)) {
                $expired = true;
            } else {
                $expired = false;
            }

            $userLimitDownload['expired'] = $expired;
            if ($expired) {
                update_user_meta($currentUser->ID, 'wpfd_user_download_limit', $userLimitDownload);

                return true;
            } else {
                $downloadedFileIds[] = $id;
                $userLimitDownload['file_ids'] = $downloadedFileIds;
                $userLimitDownload['counter'] = $counter;
                update_user_meta($currentUser->ID, 'wpfd_user_download_limit', $userLimitDownload);

                return false;
            }
        }
    }

    /**
     * Convert time unit
     *
     * @param string|integer $currentTime Current time
     * @param string|integer $number      Time limit
     * @param string|integer $type        Time type
     *
     * @return integer
     */
    public static function convertTimer($currentTime, $number, $type)
    {
        if (empty($number) || empty($type)) {
            return 0;
        }

        $currentTime = $currentTime ? $currentTime : time();
        switch ($type) {
            case 'day':
                $limitTime = intval($currentTime) + intval($number) * 60 * 60 * 24;
                break;
            case 'month':
                $limitTime = intval($currentTime) + intval($number) * 60 * 60 * 24 * 30;
                break;
            case 'year':
                $limitTime = intval($currentTime) + intval($number) * 60 * 60 * 24 * 30 * 12;
                break;
            case 'hour':
            default:
                $limitTime = intval($currentTime) + intval($number) * 60 * 60;
                break;
        }

        return $limitTime;
    }
}
