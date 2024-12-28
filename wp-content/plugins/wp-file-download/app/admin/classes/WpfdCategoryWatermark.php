<?php
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Form;
use Joomunited\WPFramework\v1_0_6\Utilities;

/**
 * Class WpfdCategoryWatermark
 */
class WpfdCategoryWatermark
{
    const WM_TYPE_ALL = 'all';

    /**
     * Enable debuging
     *
     * @var boolean
     */
    protected static $debug = false;

    /**
     * Support image type
     *
     * @var string[]
     */
    protected static $imageExt = array('jpg', 'jpeg', 'png');

    /**
     * Enable watermark category
     *
     * @var boolean
     */
    private $enabled = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!class_exists('WpfdHelperFolder')) {
            require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelperFolder.php';
        }

        $this->generatorHooks();
    }

    /**
     * Add hooks
     *
     * @return void
     */
    public function generatorHooks()
    {
        // Ajax
        add_action('wp_ajax_watermark_category_init', array($this, 'ajaxWatermarkCategoryInit'));

        $type = self::WM_TYPE_ALL;
        $fileIds = null;

        add_action('wp_ajax_watermark_category_fileinfo', array($this, 'ajaxWatermarkFileInfo'));
        add_action('wp_ajax_watermark_category_prune', array($this, 'ajaxWatermarkPrune'));
        add_action('wp_ajax_watermark_category_exec', array($this, 'ajaxWatermarkExec'));
    }

    /**
     * AJAX: Init watermark processor
     *
     * @return void
     */
    public function ajaxWatermarkCategoryInit()
    {
        $categoryId = Utilities::getInput('category_id', 'GET', 'none');
        $term_exists = term_exists(intval($categoryId), 'wpfd-category');
        if (!$term_exists && intval($categoryId) !== 0) {
            wp_send_json(array('status' => false, 'msg' => esc_html__('Category no longer exists!', 'wpfd')));
        }

        $lists = get_option('wpfd_watermark_category_listing');
        if (is_array($lists) && !empty($lists) && intval($categoryId) !== 0) {
            if (in_array($categoryId, $lists)) {
                $this->enabled = true;
            }
        }

        if (intval($categoryId) === 0 && !empty($lists)) {
            $this->enabled = true;
        }

        if (!$this->enabled) {
            wp_send_json_error(array('content' => __('Watermarks for categories are empty, please select categories before regenerate them!', 'wpfd')));
        }

        $ids = self::getAllFiles($categoryId);
        $total = count($ids);
        if (isset($total)) {
            wp_send_json_success(array('content' => sprintf(_n('Found %d file will be add watermark. Do you want to process?', 'Found %d files will be add watermark. Do you want to process?', $total, 'wpfd'), $total), 'ids' => $ids, 'total' => $total));
        }

        wp_send_json_error(array('content' => esc_html__('Something went wrong! Make sure your watermark configuration are set!', 'wpfd')));
    }

    /**
     * AJAX: Prune Watermark
     *
     * @return void
     */
    public function ajaxWatermarkPrune()
    {
        $categoryId = Utilities::getInput('category_id', 'GET', 'none');
        $term_exists = term_exists(intval($categoryId), 'wpfd-category');
        if (!$term_exists && intval($categoryId) !== 0) {
            wp_send_json(array('status' => false, 'msg' => esc_html__('Category no longer exists!', 'wpfd')));
        }

        $lists = get_option('wpfd_watermark_category_listing');
        if (is_array($lists) && !empty($lists) && intval($categoryId) !== 0) {
            if (in_array($categoryId, $lists)) {
                $this->enabled = true;
            }
        }

        if (intval($categoryId) === 0 && !empty($lists)) {
            $this->enabled = true;
        }

        if (!$this->enabled) {
            wp_send_json_error(array('content' => __('Watermark was disabled, please enable it before regenerate them!', 'wpfd')));
        }

        if (intval($categoryId) === 0) {
            $deleted = 0;
            foreach ($lists as $key => $catId) {
                $deleted += $this->pruneWatermark(true, $catId);
            }
        } else {
            $deleted = $this->pruneWatermark(true, $categoryId);
        }

        wp_send_json_success(array('content' => sprintf(_n('%d watermark file deleted!', '%d watermark files deleted!', $deleted, 'wpfd'), $deleted)));
    }

    /**
     * AJAX: Get file info before add watermark
     *
     * @return void
     */
    public function ajaxWatermarkFileInfo()
    {
        // Prepare file data before apply watermark
        $fileId = Utilities::getInput('file_id', 'POST', 'none');
        $categoryId = Utilities::getInput('cat_id', 'POST', 'none');

        if (!$fileId || !$categoryId) {
            wp_send_json_error();
        }
        // Check is cloud path
        if (!is_numeric($fileId)) {
            $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $categoryId);
            if ($categoryFrom === 'aws') {
                $fileId = rawurldecode($fileId);
            }
            $datas = (array) apply_filters('wpfd_addon_get_file_info', $fileId, $categoryId, $categoryFrom);
            if (!empty($datas) && isset($datas['post_title'])) {
                wp_send_json_success(array(
                    'content' => sprintf(__('Wartermaking for: %s...', 'wpfd'), $datas['post_title'])
                ));
            }
        }

        $file = get_post($fileId);
        if (!$file) {
            wp_send_json_error(array(
                'content' => __('File not exists...', 'wpfd')
            ));
        }
        wp_send_json_success(array(
            'content' => sprintf(__('Wartermaking for: %s...', 'wpfd'), $file->post_title)
        ));
    }

    /**
     * Execution add watermark
     *
     * @param integer $categoryId      Category id
     * @param string  $fileId          File id
     * @param boolean $ajax            Flag indicating AJAX request
     * @param boolean $isCloudDownload Flag indicating cloud download
     *
     * @return void|boolean
     */
    public function ajaxWatermarkExec($categoryId = 0, $fileId = '', $ajax = true, $isCloudDownload = false)
    {
        if (intval($categoryId) === 0) {
            $categoryId = Utilities::getInput('category_id', 'GET', 'none');
        }

        $term_exists = term_exists(intval($categoryId), 'wpfd-category');
        if (!$term_exists && intval($categoryId) !== 0) {
            if (!$ajax) {
                return false;
            }
            wp_send_json(array('status' => false, 'msg' => esc_html__('Category no longer exists!', 'wpfd')));
        }

        $lists = get_option('wpfd_watermark_category_listing');
        $config = null;
        if (is_array($lists) && !empty($lists)) {
            if (in_array($categoryId, $lists)) {
                $this->enabled = true;
                $config_lists = get_option('wpfd_watermark_category_config_listing');
                $global_config = get_option('wpfd_watermark_category_global_config');
                $default_config = array(
                    'wm_path' => '',
                    'wm_opacity' => 100,
                    'wm_position' => 'top_left',
                    'wm_size' => 100,
                    'wm_margin_unit' => '%',
                    'wm_margin_top' => 0,
                    'wm_margin_right' => 0,
                    'wm_margin_bottom' => 0,
                    'wm_margin_left' => 0
                );
                $config = !$global_config ? $default_config : $global_config;
                if (is_array($config_lists) && !empty($config_lists)) {
                    if (array_key_exists($categoryId, $config_lists)) {
                        $config = $config_lists[$categoryId];
                        if ($config['wm_path'] === '' && $global_config !== false) {
                            $config['wm_path'] = $global_config['wm_path'];
                        }
                    }
                }
            }
        }

        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $categoryId);
        if ($fileId === '') {
            $fileId = Utilities::getInput('file_id', 'POST', 'none');
            if ($categoryFrom === 'aws') {
                $fileId = rawurldecode($fileId);
            }
        }

        if (!$fileId) {
            if (!$ajax) {
                return false;
            }
            wp_send_json_error();
        }
        // Made sure Watermark is enabled
        if (!$this->enabled) {
            if (!$ajax) {
                return false;
            }
            wp_send_json_error(array('content' => __('Watermark disabled!', 'wpfd')));
        }

        $generatorEnabled = self::generatorEnabled();

        if (is_numeric($fileId)) {
            $fileMeta = get_post_meta($fileId, '_wpfd_file_metadata', true);
            $ext = isset($fileMeta['ext']) ? $fileMeta['ext'] : false;

            if (!$ext || !in_array(strtolower($ext), self::$imageExt)) {
                if (!$ajax) {
                    return false;
                }
                wp_send_json_error(array('content' => __('File extension not supported!', 'wpfd')));
            }
        } else {
            if ($isCloudDownload) {
                $generatorEnabled = false;
            } else {
                $generatorEnabled = true;
            }
        }
        self::log('Initial for ' . $fileId);
        // Check generate preview enabled?
        if ($generatorEnabled) {
            if (is_numeric($fileId)) {
                // Yes: Use previewed image to add watermark
                $previewPath = get_post_meta($fileId, '_wpfd_preview_file_path', true);
                self::log(__METHOD__ . ': Use previewed image to add watermark: ' . $previewPath);
                if (!empty($previewPath)) {
                    if (strpos($previewPath, 'wp-content') === false) {
                        $previewPath = WP_CONTENT_DIR . $previewPath;
                    }
                    if (strpos($previewPath, WpfdHelperFolder::getCategoryWatermarkPath()) !== false) {
                        $previewPath = str_replace(WpfdHelperFolder::getCategoryWatermarkPath(), WpfdHelperFolder::getPreviewsPath(), $previewPath);
                    }
                    if (file_exists($previewPath)) {
                        $fileMeta = get_post_meta($fileId, '_wpfd_file_metadata', true);
                        $filePath = WpfdBase::getFilesPath($categoryId) . $fileMeta['file'];
                        $watermarkedPath = WpfdHelperFolder::getCategoryWatermarkPath();
                        $watermarkedPath = $watermarkedPath . strval($categoryId) . '_' . strval($fileId) . '_' . strval(md5($filePath)) . '.png';
                        $watermarkedPath = self::apply($previewPath, $config, $watermarkedPath);
                        $watermarkedPath = str_replace(WP_CONTENT_DIR, '', $watermarkedPath);
                        $watermarkedPathInfo = array(
                            'updated' => current_datetime()->format('Y-m-d\TH:i:s\Z'),
                            'path' => $watermarkedPath
                        );
                        update_option('_wpfdAddon_preview_wm_info_' . $fileId, $watermarkedPathInfo);

                        if (!$ajax) {
                            return true;
                        }
                        wp_send_json_success(array('path' => $watermarkedPath));
                    }
                }
            } else {
                // Maybe cloud file
                $clouds = wpfd_get_support_cloud();
                $isPath = false;
                $cloudType = '';
                foreach ($clouds as $cloud) {
                    if (strpos($fileId, $cloud) !== false) {
                        $isPath = true;
                        $cloudType = $cloud;
                        break;
                    }
                }
                if ($isPath) { // $fileId is full path
                    if (file_exists($fileId)) {
                        preg_match('/'.strval($categoryId).'_([a-z0-9]{32})/', $fileId, $match);
                        $cloudFileId = isset($match[1]) ? $match[1] : false;
                        if (false !== $cloudFileId) {
                            $watermarkedPath = self::apply($fileId);
                            $watermarkedPath = str_replace(WP_CONTENT_DIR, '', $watermarkedPath);
                            $previewInfo = array(
                                'updated' => current_datetime()->format('Y-m-d\TH:i:s\Z'),
                                'path' => $watermarkedPath
                            );
                            update_option('_wpfdAddon_preview_wm_info_' . md5($cloudFileId), $previewInfo);

                            if (!$ajax) {
                                return true;
                            }
                            wp_send_json_success(array('path' => $watermarkedPath));
                        }
                    }
                } else {
                    $token = '';
                    switch ($categoryFrom) {
                        case 'dropbox':
                            if (has_filter('wpfdAddonGetDropboxFile', 'wpfdAddonGetDropboxFile')) {
                                $fileObj = apply_filters('wpfdAddonGetDropboxFile', $fileId, $categoryId, $token);
                                $ext     = isset($fileObj['ext']) ? $fileObj['ext'] : false;
                            }
                            break;
                        case 'onedrive':
                            if (has_filter('wpfdAddonGetOneDriveFile', 'wpfdAddonGetOneDriveFile')) {
                                $fileObj = apply_filters('wpfdAddonGetOneDriveFile', $fileId, $categoryId, $token);
                                $ext     = isset($fileObj['ext']) ? $fileObj['ext'] : false;
                            }
                            break;
                        case 'onedrive_business':
                            if (has_filter('wpfdAddonGetOneDriveBusinessFile', 'wpfdAddonGetOneDriveBusinessFile')) {
                                $fileObj = apply_filters('wpfdAddonGetOneDriveBusinessFile', $fileId, $categoryId, $token);
                                $ext     = isset($fileObj['ext']) ? $fileObj['ext'] : false;
                            }
                            break;
                        case 'aws':
                            if (has_filter('wpfdAddonGetAwsFile', 'wpfdAddonGetAwsFile')) {
                                $fileObj = apply_filters('wpfdAddonGetAwsFile', $fileId, $categoryId, $token);
                                $ext     = isset($fileObj['ext']) ? $fileObj['ext'] : false;
                            }
                            break;
                        case 'googleTeamDrive':
                            if (has_filter('wpfdAddonGetGoogleTeamDriveFile', 'wpfdAddonGetGoogleTeamDriveFile')) {
                                $fileObj = apply_filters('wpfdAddonGetGoogleTeamDriveFile', $fileId, $categoryId, $token);
                                $ext     = isset($fileObj['ext']) ? $fileObj['ext'] : false;
                            }
                            break;
                        case 'googleDrive':
                        default:
                            if (has_filter('wpfdAddonGetGoogleDriveFile', 'wpfdAddonGetGoogleDriveFile')) {
                                $fileObj = apply_filters('wpfdAddonGetGoogleDriveFile', $fileId, $categoryId, $token);
                                $ext     = isset($fileObj['ext']) ? $fileObj['ext'] : false;
                            }
                            break;
                    }
                    
                    if (!$ext || !in_array(strtolower($ext), self::$imageExt)) {
                        if (!$ajax) {
                            return false;
                        }
                        wp_send_json_error(array('content' => __('File extension not supported!', 'wpfd')));
                    }

                    $previewInfo = get_option('_wpfdAddon_preview_info_' . md5($fileId), false);
                    self::log('[CLOUD] Preview info: ' . var_export($previewInfo, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- It's OK

                    if ($previewInfo !== false) {
                        $previewPath = isset($previewInfo['path']) ? $previewInfo['path'] : '';
                        if (!empty($previewPath)) {
                            if (strpos($previewPath, 'wp-content') === false) {
                                $previewPath = WP_CONTENT_DIR . $previewPath;
                            }
                            if (!file_exists($previewPath)) {
                                $previewPath = self::getPreviewPathForCloud($categoryId, $fileId);
                            }
                        }
                    } else {
                        $previewPath = self::getPreviewPathForCloud($categoryId, $fileId);
                    }
                    
                    if (!empty($previewPath)) {
                        if (strpos($previewPath, 'wp-content') === false) {
                            $previewPath = WP_CONTENT_DIR . $previewPath;
                        }

                        if (file_exists($previewPath)) {
                            $watermarkedPath = WpfdHelperFolder::getCategoryWatermarkPath();
                            $watermarkedPath = $watermarkedPath . strval($categoryId) . '_' . strval(md5($fileId)) . '.png';
                            $watermarkedPath = self::apply($previewPath, $config, $watermarkedPath);
                            $watermarkedPath = str_replace(WP_CONTENT_DIR, '', $watermarkedPath);
                            // Update current preview path to cloud
                            $previewInfo['path'] = $watermarkedPath;
                            update_option('_wpfdAddon_preview_wm_info_' . md5($fileId), $previewInfo);

                            if (!$ajax) {
                                return true;
                            }
                            wp_send_json_success(array('path' => $watermarkedPath));
                        }
                    }
                }
            }

            if (!is_numeric($fileId)) {
                if (!$ajax) {
                    return false;
                }
                wp_send_json_error(array('content' => __('Some watermark couldn\'t be generated', 'wpfd')));
            }
        }

        if ($isCloudDownload) {
            $watermarkedPath = WpfdHelperFolder::getCategoryWatermarkPath();
            $imagePath = $watermarkedPath . 'wm_cloud_' . strval(md5($fileId)) . '.png';
            $watermarkedPath = $watermarkedPath . 'wm_cloud_' . strval(md5($fileId)) . '.png';
            $watermarkedPath = self::apply($imagePath, $config, $watermarkedPath);

            return $watermarkedPath;
        }

        // No: Resize image files then watermark it
        if (!in_array(strtolower($ext), self::$imageExt)) {
            if (!$ajax) {
                return false;
            }
            wp_send_json_error(array('content' => __('File extension not support', 'wpfd')));
        }
        // Get category id
        $term_list = wp_get_post_terms($fileId, 'wpfd-category', array('fields' => 'ids'));
        if (!is_wp_error($term_list) && count($term_list) > 0) {
            $catId = $term_list[0];
        }
        if (!isset($catId)) {
            if (!$ajax) {
                return false;
            }
            wp_send_json_error(array('content' => __('File category not found!', 'wpfd')));
        }
        $filePath = WpfdBase::getFilesPath($catId) . $fileMeta['file'];
        $previewPath = WpfdHelperFolder::getPreviewsPath();

        $previewFileName = $previewPath . strval($categoryId) . '_' . strval($fileId) . '_' . strval(md5($filePath)) . '.png';
        if (!file_exists($filePath)) {
            if (!$ajax) {
                return false;
            }
            wp_send_json_error(array('message' => __('File not exists!', 'wpfd')));
        }
        // Try resize first
        $editor = wp_get_image_editor($filePath, array('mime_type' => 'image/png'));
        $editor->set_quality(apply_filters('wpfd_preview_image_quantity', 90));
        $tooSmall = false;
        $originSize = $editor->get_size();
        $imageSize = apply_filters('wpfd_preview_image_size', array('w' => 800, 'h' => 800));
        if ($originSize['width'] < $imageSize['w'] && $originSize['height'] <= $imageSize['h']) {
            // Copy and save
            WpfdHelperFolder::getFileSystem()->copy($filePath, $previewFileName, true);
            $tooSmall = true;
        }

        // Do resize
        if (!$tooSmall) {
            $resized = $editor->resize($imageSize['w'], $imageSize['h'], false);

            if (is_wp_error($resized)) {
                if (!$ajax) {
                    return false;
                }
                wp_send_json_error(array('content' => $resized->get_error_message()));
            }

            $saved = $editor->save($previewFileName);
            if (is_wp_error($saved)) {
                if (!$ajax) {
                    return false;
                }
                wp_send_json_error(array('content' => $saved->get_error_message()));
            }
        }
        // Add watermark to resized image
        if (file_exists($previewFileName)) {
            $watermarkedPath = self::apply($previewFileName, $config);
            $watermarkedPath = str_replace(WP_CONTENT_DIR, '', $watermarkedPath);
            $watermarkedPathInfo = array(
                'updated' => current_datetime()->format('Y-m-d\TH:i:s\Z'),
                'path' => $watermarkedPath
            );
            update_option('_wpfdAddon_preview_wm_info_' . $fileId, $watermarkedPathInfo);

            if (!$ajax) {
                return true;
            }
            wp_send_json_success(array('path' => $watermarkedPath));
        }

        if (!$ajax) {
            return false;
        }
        wp_send_json_error(array('content' => __('Unknown error!', 'wpfd')));
    }

    /**
     * Prune watermark files
     *
     * @param integer $count      Count deleted
     * @param integer $categoryId Category id
     *
     * @return boolean|integer
     */
    public function pruneWatermark($count = false, $categoryId = 0)
    {
        $wmPath = WpfdHelperFolder::getCategoryWatermarkPath();
        $filesPath = glob($wmPath . $categoryId. '_*.[pPjJ][nNpP][gG]');
        $totalDeleted = 0;
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
                $totalDeleted++;
            }
        }

        if ($count) {
            return $totalDeleted;
        }

        return true;
    }

    /**
     * Callback fire before cloud file download
     *
     * @param string $id    File id
     * @param string $type  Cloud type
     * @param string $catId Category id
     *
     * @return void
     */
    public function beforeCloudDownload($id, $type, $catId)
    {
        $preview = Utilities::getInput('preview', 'GET', 'bool');

        if ($preview && WpfdHelperFile::isWatermarkEnabled()) {
            // Generated preview enabled?
            $generatedPreviewUrl = WpfdHelperFile::getGeneratedPreviewUrl($id, $catId, '', true);

            if (false !== $generatedPreviewUrl) {
                $filename = 'preview_' . $id;
                if ($filename === '') {
                    $filename = 'download';
                }
                $filedownload = $filename . '.png';
                WpfdHelperFile::sendDownload(
                    $generatedPreviewUrl,
                    $filedownload,
                    'png',
                    true, // Preview
                    false
                );
                exit();
            }
        }
    }

    /**
     * Callback fire before download file
     *
     * @param object $file File object
     * @param array  $meta File meta
     *
     * @return void
     */
    public function beforeDownload($file, $meta)
    {
        $preview = Utilities::getInput('preview', 'GET', 'bool');

        if ($preview && WpfdHelperFile::isWatermarkEnabled()) {
            // Generated preview enabled?
            $generatedPreviewUrl = WpfdHelperFile::getGeneratedPreviewUrl($file->ID, $meta['catid'], '', true);

            if (false !== $generatedPreviewUrl) {
                $filename = WpfdHelperFile::santizeFileName($file->title);
                if ($filename === '') {
                    $filename = 'download';
                }
                $filedownload = $filename . '.' . $file->ext;
                WpfdHelperFile::sendDownload(
                    $generatedPreviewUrl,
                    $filedownload,
                    $file->ext,
                    true, // Preview
                    false
                );
                exit();
            }
        }
    }

    /**
     * Check generator preview enabled
     *
     * @return boolean
     */
    public static function generatorEnabled()
    {
        $wpfdOptions = get_option('_wpfd_global_config', false);
        if (false === $wpfdOptions) {
            return false;
        }
        $isEnabled = isset($wpfdOptions['auto_generate_preview']) ? $wpfdOptions['auto_generate_preview'] : false;
        if (!$isEnabled) {
            return false;
        }

        return true;
    }

    /**
     * Get all files
     *
     * @param integer $categoryId Category id
     *
     * @return array
     */
    public function getAllFiles($categoryId)
    {
        $allFiles = array();
        $term_exists = term_exists(intval($categoryId), 'wpfd-category');
        if (!$term_exists && intval($categoryId) !== 0) {
            return $allFiles;
        }

        if (intval($categoryId) === 0) {
            $categories = get_option('wpfd_watermark_category_listing');
            foreach ($categories as $key => $categoryId) {
                $allFile1 = self::fileAllCat($categoryId);
                if (!empty($allFile1)) {
                    foreach ($allFile1 as $key => $val) {
                        if (!empty($val)) {
                            $allFiles[] = array(
                                'id' => $val->ID,
                                'categoryId' => $categoryId
                            );
                        }
                    }
                }
            }
        } else {
            $allFiles1 = self::fileAllCat($categoryId);
            if (!empty($allFiles1)) {
                foreach ($allFiles1 as $key => $val) {
                    if (!empty($val)) {
                        $allFiles[] = array(
                            'id' => $val->ID,
                            'categoryId' => $categoryId
                        );
                    }
                }
            }
        }

        return $allFiles;
    }

    /**
     * Get all file in category
     *
     * @param mixed $categoryId Category id
     *
     * @return array|mixed
     */
    public function fileAllCat($categoryId)
    {
        Application::getInstance('Wpfd');
        $modelCategory = Model::getInstance('categoryfront');
        $category = $modelCategory->getCategory($categoryId);
        $files = array();
        if (empty($category)) {
            return $files;
        }
        $ordering = $category->ordering;
        $orderingdir = $category->orderingdir;

        $modelFiles = Model::getInstance('files');
        $modelTokens = Model::getInstance('tokens');
        $token = $modelTokens->getOrCreateNew();

        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $categoryId);
        if ($categoryFrom === 'googleDrive') {
            $files = apply_filters(
                'wpfdAddonGetListGoogleDriveFile',
                $categoryId,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $files = apply_filters(
                'wpfdAddonGetListGoogleTeamDriveFile',
                $categoryId,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'dropbox') {
            $files = apply_filters(
                'wpfdAddonGetListDropboxFile',
                $categoryId,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'onedrive') {
            $files = apply_filters(
                'wpfdAddonGetListOneDriveFile',
                $categoryId,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'onedrive_business') {
            $files = apply_filters(
                'wpfdAddonGetListOneDriveBusinessFile',
                $categoryId,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'aws') {
            $files = apply_filters(
                'wpfdAddonGetListAwsFile',
                $categoryId,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } else {
            $files = $modelFiles->getFiles($categoryId, $ordering, $orderingdir);
        }

        return $files;
    }

    /**
     * Get preview file path
     *
     * @param integer $categoryId Category id
     * @param string  $fileId     File id
     *
     * @return string
     */
    public function getPreviewPathForCloud($categoryId, $fileId)
    {
        $previewPath = '';
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $categoryId);
        if ($categoryFrom === 'googleDrive') {
            $previewPath = apply_filters('wpfdAddonSaveGoogleDriveImg', $fileId, WpfdHelperFolder::getPreviewsPath());
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $previewPath = apply_filters('wpfdAddonSaveGoogleTeamDriveImg', $fileId, WpfdHelperFolder::getPreviewsPath());
        } elseif ($categoryFrom === 'dropbox') {
            $previewPath = apply_filters('wpfdAddonSaveDropboxImg', $fileId, WpfdHelperFolder::getPreviewsPath());
        } elseif ($categoryFrom === 'onedrive') {
            $previewPath = apply_filters('wpfdAddonSaveOneDriveImg', $fileId, WpfdHelperFolder::getPreviewsPath());
        } elseif ($categoryFrom === 'onedrive_business') {
            $previewPath = apply_filters('wpfdAddonSaveOneDriveBusinessImg', $fileId, WpfdHelperFolder::getPreviewsPath());
        } elseif ($categoryFrom === 'aws') {
            $previewPath = apply_filters('wpfdAddonSaveAwsImg', $fileId, WpfdHelperFolder::getPreviewsPath());
        }

        return $previewPath;
    }

    /**
     * Get watermark config
     *
     * @return array|mixed
     */
    public static function getConfig()
    {
        return WpfdHelperFile::getWatermarkConfig();
    }

    /**
     * Set watermark config data
     *
     * @param array $data Watermark config
     *
     * @return void
     */
    public static function setConfig($data)
    {
        WpfdHelperFile::setWatermarkConfig($data);
    }

    /**
     * Get watermark type
     *
     * @return string
     */
    public static function getWatermarkType()
    {
        $config = self::getConfig();

        return 'all';
    }

    /**
     * Check watermark option is enabled
     *
     * @param array $args Pseudo argument
     *
     * @return boolean
     */
    public static function enabled($args = false)
    {
        return WpfdHelperFile::isWatermarkEnabled();
    }

    /**
     * Apply watermark on $imagePath
     *
     * @param string $imagePath         The image path need watermark
     * @param array  $config            Watermark config
     * @param string $watermarkFilePath The watermark path
     *
     * @return boolean
     */
    public static function apply($imagePath, $config = null, $watermarkFilePath = '')
    {
        $watermarkPath = WpfdHelperFolder::getCategoryWatermarkPath();

        if ($watermarkFilePath === '') {
            $watermarkFilePath = $watermarkPath . WpfdHelperFolder::getBaseName($imagePath);
        }
        self::log('Watermarking ' . $imagePath . ' then save to ' . $watermarkFilePath);
        // Reapply content path to image path
        if (strpos($imagePath, 'wp-content') === false) {
            $imagePath = WP_CONTENT_DIR . $imagePath;
        }
        // Copy source image to watermark location
        WpfdHelperFolder::copy($imagePath, $watermarkFilePath, true);

        if (self::watermark($watermarkFilePath, $config)) {
            return $watermarkFilePath;
        }

        return $imagePath;
    }

    /**
     * Check image watermarked
     *
     * @param string $imagePath Image path
     *
     * @return boolean
     */
    public static function watermarked($imagePath)
    {
        $watermarkPath = WpfdHelperFolder::getCategoryWatermarkPath();
        $watermarkFilePath = $watermarkPath . WpfdHelperFolder::getBaseName($imagePath);

        return file_exists($watermarkFilePath);
    }

    /**
     * Backup image
     *
     * @param string  $imagePath Image path
     * @param boolean $overwrite Override exists image
     *
     * @return void
     */
    public static function backup($imagePath, $overwrite = false)
    {
        $backupPath = WpfdHelperFolder::getOriginalPath();

        // Copy source image to original folder, no overwrite to keep the first one
        WpfdHelperFolder::copy($imagePath, $backupPath . WpfdHelperFolder::getBaseName($imagePath), $overwrite);
    }

    /**
     * Restore original image
     *
     * @param string  $imagePath Image path
     * @param boolean $delete    Delete after restore
     *
     * @return boolean
     */
    public static function restore($imagePath, $delete = false)
    {
        // Check file name exists in original path
        $backupPath = WpfdHelperFolder::getOriginalPath();

        // Get file name to search
        $fileName = basename($imagePath);

        $originalName = $backupPath . $fileName;

        if (!$fileName || !file_exists($originalName)) {
            return false;
        }

        // Restore the original file
        $success = WpfdHelperFolder::copy($originalName, $imagePath, true);

        // Delete original
        if ($delete) {
            WpfdHelperFolder::delete($originalName);
        }

        return $success;
    }

    /**
     * Add watermark to image
     *
     * @param string $imagePath Image path
     * @param array  $config    Watermark config
     *
     * @return boolean
     *
     * @throws ImagickException Throw on exception
     */
    public static function watermark($imagePath, $config = null)
    {
        if (!file_exists($imagePath)) {
            self::log('Image Path not exists! ' . $imagePath);

            return false;
        }

        if (is_null($config)) {
            return false;
        }

        // Made sure wm_path is absolute path or Imagick can't work
        $config['wm_path'] = WpfdHelperFolder::getAbsolutePath($config['wm_path']);

        if (!isset($config['wm_path']) || !file_exists($config['wm_path'])) {
            self::log('Watermark Path not exists! ' . $config['wm_path']);
            return false;
        }


        self::log('Watermarking ' . $imagePath . ' with ' . $config['wm_path']);

        $success = false;

        if ($success === false && function_exists('imagecreatefrompng')) {
            self::log('Trying add watermark by GD');
            $success = self::watermarkGD($imagePath, $config);
        }

        if ($success === false && class_exists('Imagick')) {
            self::log('Trying add watermark by Imagick');
            // Fix: Uncaught ImagickException
            try {
                $success = self::watermarkImagick($imagePath, $config);
            } catch (Exception $e) {
                self::log('Error: ' . $e->getMessage());
                return false;
            }
        }

        return $success;
    }

    /**
     * Add watermark via GD
     *
     * @param string $imagePath Image path
     * @param array  $config    Watermark config
     *
     * @return boolean
     */
    private static function watermarkGD($imagePath, $config = array())
    {
        try {
            // Find base image size
            $logoPath = $config['wm_path'];

            $image = self::createImage($imagePath);
            $watermarkImage = self::createImage($logoPath);

            if (!$image || !$watermarkImage) {
                return false;
            }

            // Set opacity for watermark image
            if ((int)$config['wm_opacity'] !== 1) {
                $watermarkImage = self::setOpacity($watermarkImage, $config['wm_opacity']);
            }

            $imageInfo = getimagesize($imagePath);
            list($wm_x, $wm_y) = getimagesize($logoPath);
            list($image_x, $image_y) = $imageInfo;

            if (false === $imageInfo) {
                return false;
            }

            $watermark_margin = self::calculateWatermarkMargin($config, $image_x, $image_y);

            // Set image scaling
            list($new_width, $new_height) = self::calculateScaling($config, $wm_x, $wm_y, $image_x);

            // Calc watermark position
            list($watermark_pos_x, $watermark_pos_y) = self::calculateWatermarkPosition($config, $image_x, $image_y, $new_width, $new_height, $watermark_margin);

            $sampled = imagecopyresampled(
                $image,
                $watermarkImage,
                $watermark_pos_x,
                $watermark_pos_y,
                0,
                0,
                $new_width,
                $new_height,
                $wm_x,
                $wm_y
            );

            if (!$sampled) {
                return false;
            }
            // Save image
            switch (strtolower($imageInfo['mime'])) {
                case 'image/jpeg':
                case 'image/pjpeg':
                    return imagejpeg($image, $imagePath);
                case 'image/png':
                    $background = imagecolorallocate($image, 0, 0, 0);
                    // removing the black from the placeholder
                    imagecolortransparent($image, $background);

                    // turning off alpha blending (to ensure alpha channel information
                    // is preserved, rather than removed (blending with the rest of the
                    // image in the form of black))
                    imagealphablending($image, false);

                    // turning on alpha channel information saving (to ensure the full range
                    // of transparency is preserved)
                    imagesavealpha($image, true);

                    return imagepng($image, $imagePath, 9);
                case 'image/gif':
                    return imagegif($image, $imagePath);
                default:
                    return false; // Not support image type
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create image
     *
     * @param string $image Image path
     *
     * @return false|GdImage|resource|void
     */
    private static function createImage($image)
    {
        $size = getimagesize($image);
        // Load image from file
        switch (strtolower($size['mime'])) {
            case 'image/jpeg':
            case 'image/pjpeg':
                return imagecreatefromjpeg($image);
            case 'image/png':
                return imagecreatefrompng($image);
            case 'image/gif':
                return imagecreatefromgif($image);
        }
    }

    /**
     * Set opacity
     *
     * @param string $imageSrc Image path
     * @param float  $opacity  Opacity
     *
     * @return false|GdImage|resource
     */
    private static function setOpacity($imageSrc, $opacity)
    {
        $width = imagesx($imageSrc);
        $height = imagesy($imageSrc);

        // Duplicate image and convert to TrueColor
        $imageDst = imagecreatetruecolor($width, $height);
        imagealphablending($imageDst, false);
        imagefill($imageDst, 0, 0, imagecolortransparent($imageDst));
        imagecopy($imageDst, $imageSrc, 0, 0, 0, 0, $width, $height);

        // Set new opacity to each pixel
        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                $pixelColor = imagecolorat($imageDst, $x, $y);
                $pixelOpacity = 127 - (($pixelColor >> 24) & 0xFF);
                if ($pixelOpacity > 0) {
                    $pixelOpacity = $pixelOpacity * $opacity;
                    $pixelColor = ($pixelColor & 0xFFFFFF) | ((int)round(127 - $pixelOpacity) << 24);
                    imagesetpixel($imageDst, $x, $y, $pixelColor);
                }
            }
        }

        return $imageDst;
    }

    /**
     * Add watermark via Imagick
     *
     * @param string $imagePath Image th
     * @param array  $config    Watermark config
     *
     * @return boolean
     *
     * @throws ImagickException Throw on exception
     */
    private static function watermarkImagick($imagePath, $config = array())
    {
        $watermarkPath = $config['wm_path'];

        if (!file_exists($imagePath)) {
            return false;
        }

        // Open the source image
        $image = new Imagick();
        if (!$image->readImage($imagePath)) { // This must be absolute path to the image
            return false;
        }
        // Open the watermark image
        $watermark = new Imagick();
        if (!$watermark->readImage($watermarkPath)) {
            return false;
        }

        // Retrieve size of the Images to verify how to print the watermark on the image
        $image_x = $image->getImageWidth();
        $image_y = $image->getImageHeight();
        $wm_x = $watermark->getImageWidth();
        $wm_y = $watermark->getImageHeight();

        if ($image_y < $wm_y || $image_x < $wm_x) {
            // Resize the watermark to be of the same size of the image
            $watermark->scaleImage($image_x, $image_y);

            // Update size of the watermark
            $wm_x = $watermark->getImageWidth();
            $wm_y = $watermark->getImageHeight();
        }

        $watermark_margin = self::calculateWatermarkMargin($config, $image_x, $image_y);

        // Set image scaling
        list($new_width, $new_height) = self::calculateScaling($config, $wm_x, $wm_y, $image_x);

        // Calc watermark position
        list($watermark_pos_x, $watermark_pos_y) = self::calculateWatermarkPosition($config, $image_x, $image_y, $new_width, $new_height, $watermark_margin);

        // Draw the watermark on your image
        $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $watermark_pos_x, $watermark_pos_y);

        return $image->writeImage($imagePath);
    }

    /**
     * Calculate Watermark position
     *
     * @param array   $config           Watermark config
     * @param integer $image_x          Image X axis
     * @param integer $image_y          Image Y axis
     * @param integer $new_width        New watermark width
     * @param integer $new_height       New watermark height
     * @param array   $watermark_margin Watermark margin config
     *
     * @return array
     */
    private static function calculateWatermarkPosition($config, $image_x, $image_y, $new_width, $new_height, array $watermark_margin)
    {
        switch ($config['wm_position']) {
            case 'top_left':
                $watermark_pos_x = (int)$watermark_margin['left'];
                $watermark_pos_y = (int)$watermark_margin['top'];
                break;
            case 'top_center':
                $watermark_pos_x = ($image_x - $new_width) / 2;
                $watermark_pos_y = (int)$watermark_margin['top'];
                break;
            case 'top_right':
                $watermark_pos_x = $image_x - $new_width - (int)$watermark_margin['right'];
                $watermark_pos_y = (int)$watermark_margin['top'];
                break;
            case 'center_left':
                $watermark_pos_x = (int)$watermark_margin['left'];
                $watermark_pos_y = ($image_y - $new_height) / 2;
                break;
            case 'center_right':
                $watermark_pos_x = $image_x - $new_width - (int)$watermark_margin['right'];
                $watermark_pos_y = ($image_y - $new_height) / 2;
                break;
            case 'bottom_left':
                $watermark_pos_x = (int)$watermark_margin['left'];
                $watermark_pos_y = $image_y - $new_height - (int)$watermark_margin['bottom'];
                break;
            case 'bottom_center':
                $watermark_pos_x = ($image_x - $new_width) / 2;
                $watermark_pos_y = $image_y - $new_height - (int)$watermark_margin['bottom'];
                break;
            case 'bottom_right':
                $watermark_pos_x = $image_x - $new_width - (int)$watermark_margin['right'];
                $watermark_pos_y = $image_y - $new_height - (int)$watermark_margin['bottom'];
                break;
            case 'center_center':
            default:
                $watermark_pos_x = ($image_x - $new_width) / 2; // Watermark left
                $watermark_pos_y = ($image_y - $new_height) / 2; // Watermark bottom
                break;
        }

            return array($watermark_pos_x, $watermark_pos_y);
    }

    /**
     * Calculate scaling
     *
     * @param array   $config  Watermark config
     * @param integer $wm_x    Watermark X axis
     * @param integer $wm_y    Watermark Y axis
     * @param integer $image_x Image X axis
     *
     * @return array
     */
    private static function calculateScaling($config, $wm_x, $wm_y, $image_x)
    {
        $r = $wm_x / $wm_y;
        $new_width = $image_x * (int)$config['wm_size'] / 100;
        if ($new_width > $wm_x) {
            $new_width = $wm_x;
        }

        $new_height = $new_width / $r;
        if ($new_height > $wm_y) {
            $new_height = $wm_y;
        }

        return array($new_width, $new_height);
    }

    /**
     * Calculate watermark margin
     *
     * @param array   $config  Image watermark config
     * @param integer $image_x Image x axis
     * @param integer $image_y Image Y axis
     *
     * @return array
     */
    private static function calculateWatermarkMargin($config, $image_x, $image_y)
    {
        $watermark_margin = array(
            'left' => $config['wm_margin_left'],
            'right' => $config['wm_margin_right'],
            'bottom' => $config['wm_margin_bottom'],
            'top' => $config['wm_margin_top'],
        );
        // Get image margin
        if ($config['wm_margin_unit'] === '%') {
            $percent_watermark_margin = array();
            $percent_watermark_margin['left'] = ($image_x / 100) * $config['wm_margin_left'];
            $percent_watermark_margin['right'] = ($image_x / 100) * $config['wm_margin_right'];
            $percent_watermark_margin['top'] = ($image_y / 100) * $config['wm_margin_top'];
            $percent_watermark_margin['bottom'] = ($image_y / 100) * $config['wm_margin_bottom'];
            $watermark_margin = $percent_watermark_margin;
        }

        return $watermark_margin;
    }

    /**
     * Get all cloud preview files
     *
     * @return array
     */
    private static function getAllCloudPreviewFiles()
    {
        $clouds = wpfd_get_support_cloud();
        $previewPaths = array();
        $previewPath = WpfdHelperFolder::getPreviewsPath();

        foreach ($clouds as $cloud) {
            $files = glob($previewPath . $cloud . '_*');
            $previewPaths = array_merge($previewPaths, $files);
        }

        return array_map('trim', $previewPaths);
    }

    /**
     * Log
     *
     * @param mixed $data Log data
     *
     * @return void
     */
    private static function log($data)
    {
        // Do nothing if not enabled
        if (!self::$debug) {
            return;
        }

        if (is_string($data)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Log if enable debug
            error_log($data);
        } else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export  -- Log if enable debug
            error_log(var_export($data, true));
        }
    }
}
