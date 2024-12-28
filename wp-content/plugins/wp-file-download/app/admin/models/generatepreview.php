<?php
/**
 * WP Framework
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WpfdModelFts
 */
class WpfdModelGeneratepreview extends Model
{
    const RETRY = 200;
    const RETRY_ON_MAX_REQUEST = 429;
    const ABORT_REMOVE = 0;
    const GENERATED_FAILED = -1;
    const MAX_RETRIES = 3;
    const WAIT_MINUTES = 1;
    // phpcs:ignore PHPCompatibility.InitialValue.NewConstantScalarExpressions.constFound -- Keep this size
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * DEBUG
     *
     * @var boolean
     */
    private static $debug = false;

    /**
     * JOOMUNITED TOKEN
     *
     * @var string
     */
    private $juToken = '';

    /**
     * Endpoint
     *
     * @var string
     */
    private $endpoint = 'https://previewer.joomunited.com/file';

    /**
     * Push URL
     *
     * @var string
     */
    private $pushUrl;

    /**
     * Support extensions
     *
     * @var array
     */
    private $supportExtensions = array('ai', 'csv', 'doc', 'docx', 'html', 'json', 'odp', 'ods', 'pdf', 'ppt', 'pptx', 'rtf', 'sketch', 'xd', 'xls', 'xlsx', 'xml', 'jpg', 'png', 'gif', 'jpeg');

    /**
     * Support extensions to generate thumbnail
     *
     * @var array
     */
    private $thumbnailSupportExtensions = array('jpg', 'png', 'gif', 'jpeg');

    /**
     * WpfdModelGeneratepreview constructor.
     */
    public function __construct()
    {
        if (defined('WPFD_DEBUG') && WPFD_DEBUG) {
            self::$debug = true;
        }

        // Add filter support extensions
        $this->supportExtensions = apply_filters('wpfd_generate_preview_support_extensions', $this->supportExtensions);

        $this->thumbnailSupportExtensions = apply_filters('wpfd_generate_preview_thumbnail_support_extensions', $this->thumbnailSupportExtensions);

        if (!class_exists('WpfdHelperFolder')) {
            require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelperFolder.php';
        }
    }
    /**
     * Add file to queue
     *
     * @param integer|string $fileId File id to add to queue
     *
     * @return boolean|mixed
     */
    public function addFileToQueue($fileId)
    {
        if (!$fileId) {
            return false;
        }
        $file = get_post($fileId);
        if (is_wp_error($file)) {
            self::log('File Id not found');
            return false;
        }
        $queueFilesInOption = get_option('_wpfd_previewer_generate_queue_files', array());
        $sourceFilePath = $this->getSourceFilePath($fileId);
        if (!$sourceFilePath) {
            self::log('Source file not exists!');
            return false;
        }
        if (isset($queueFilesInOption[$file->ID])) {
            self::log('File already in queue');
            return false;
        }
        $fileInfo = pathinfo($sourceFilePath);
        $fileSize = filesize($sourceFilePath);
        // Check allow extension
        if (!in_array(strtolower($fileInfo['extension']), $this->supportExtensions)) {
            return false;
        }
        // Check file size
        if (intval($fileSize) > intval(self::MAX_FILE_SIZE)) {
            return false;
        }
        $type = 'server';
        $inProcess = 0;
        if (in_array(strtolower($fileInfo['extension']), $this->thumbnailSupportExtensions)) {
            $type = 'image';
            $inProcess = 1;
        }
        $queueFilesInOption[$file->ID] = array(
            'date_added' => time(),
            'type' => $type,
            'file_id' => $file->ID,
            'file_path' => $sourceFilePath,
            'file_ext' => $fileInfo['extension'],
            'file_last_updated' => $file->post_modified,
            'retries' => 0,
            'in_process' => $inProcess,
            'request_id' => 0,
            'preview_generated' => 0,
        );
        update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);

        return true;
    }

    /**
     * Remove file from queue
     *
     * @param integer|string $fileId      File id
     * @param boolean        $deleteImage Permanently delete file on disk
     *
     * @return void
     */
    public function removeFileFromQueue($fileId, $deleteImage = true)
    {
        if (!$fileId) {
            return;
        }
        $queueFilesInOption = get_option('_wpfd_previewer_generate_queue_files', array());

        if (isset($queueFilesInOption[$fileId])) {
            // Remove saved file and post meta
            if ($deleteImage) {
                delete_post_meta($fileId, '_wpfd_preview_file_path');
                $this->deletePreviewFiles($fileId);
                $this->deleteThumbnailFiles($fileId);
                $this->deleteLogFile();
            }
            unset($queueFilesInOption[$fileId]);
            update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
        }
    }

    /**
     * Generate queue from beginning
     *
     * @return array|void
     */
    public function generateQueue()
    {
        $this->pushUrl = admin_url('admin-ajax.php?juwpfisadmin=false&action=wpfd&task=file.previewdownload');
        $this->juToken = $this->getJuToken();
        // Get all files - local
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'wpfd_file',
            'post_status' => 'publish'
        );
        $files = get_posts($args);

        if (is_wp_error($files) || (is_array($files) && empty($files))) {
            self::log('no files');
            return;
        }
        $queueFiles = array();
        $queueFilesInOption = get_option('_wpfd_previewer_generate_queue_files', array());
        // Remove not exists post in queue
        $filesId = array_map(function ($file) {
            return $file->ID;
        }, $files);
        foreach ($queueFilesInOption as $fileId => $queueData) {
            if (!in_array($fileId, $filesId)) {
                unset($queueFilesInOption[$fileId]);
            }
        }
        update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);

        // Filter queues
        foreach ($files as $file) {
            $sourceFilePath = $this->getSourceFilePath($file->ID);

            if (!$sourceFilePath) {
                unset($queueFilesInOption[$file->ID]);
                continue;
            }

            // Check file already in queue
            if (isset($queueFilesInOption[$file->ID]) && is_array($queueFilesInOption[$file->ID])) {
                $fileInOption = $queueFilesInOption[$file->ID];
                // Check file in process
                if (isset($fileInOption['in_process']) && $fileInOption['in_process'] === 1) {
                    continue;
                }
                // Check last updated
                if (isset($fileInOption['preview_generated']) && intval($fileInOption['preview_generated']) === 1) {
                    if ($file->post_modified === $fileInOption['file_last_updated']) {
                        continue;
                    }
                    // Check preview file exists
                    $previewFilePath = get_post_meta('_wpfd_preview_file_path', $file->ID, true);
                    if (file_exists($previewFilePath)) {
                        continue;
                    }
                }
            }

            if (isset($queueFilesInOption['ignore']) && intval($queueFilesInOption['ignore']) === 1) {
                continue;
            }
            $fileInfo = pathinfo($sourceFilePath);
            $fileSize = fileSize($sourceFilePath);
            // Check file extension
            if (!in_array(strtolower($fileInfo['extension']), $this->supportExtensions)) {
                continue;
            }
            // Check file size
            if (intval($fileSize) > intval(self::MAX_FILE_SIZE)) {
                continue;
            }
            $type = 'server';
            $inProcess = 0;
            if (in_array(strtolower($fileInfo['extension']), $this->thumbnailSupportExtensions)) {
                $type = 'image';
                $inProcess = 1;
            }

            $queueFile = array(
                'date_added' => time(),
                'type' => $type,
                'file_id' => $file->ID,
                'file_path' => $sourceFilePath,
                'file_ext' => $fileInfo['extension'],
                'file_last_updated' => $file->post_modified,
                'retries' => 0,
                'in_process' => $inProcess,
                'request_id' => 0,
                'preview_generated' => 0,
            );
            $queueFiles[$file->ID] = $queueFile;
        }
        $queueFiles = array_replace_recursive($queueFilesInOption, $queueFiles);

        update_option('_wpfd_previewer_generate_queue_files', $queueFiles, false);

        return $queueFiles;
    }

    /**
     * Run queue
     *
     * @return boolean
     */
    public function runQueue()
    {
        $this->pushUrl = admin_url('admin-ajax.php?juwpfisadmin=false&action=wpfd&task=file.previewdownload');
        $this->juToken = $this->getJuToken();

        if (!$this->juToken || $this->juToken === '') {
            return false;
        }

        /**
         * Priority run queue
         *
         * @param boolean
         *
         * @internal
         */
        $priorityRunning = apply_filters('wpfd_preview_generate_queue_priority_running', false);

        // Check queue is running?
        $isRunning = get_option('_wpfd_preview_generate_queue_running', false);
        if ($isRunning && !$priorityRunning) {
            self::log('Queue is running, abort!');
            return false;
        }
        // Check option is turn on
        $wpfdOptions = get_option('_wpfd_global_config', false);
        if (false === $wpfdOptions) {
            self::log('Global option not found, abort!');
            return false;
        }
        $isEnabled = isset($wpfdOptions['auto_generate_preview']) ? $wpfdOptions['auto_generate_preview'] : false;
        if (!$isEnabled) {
            self::log('Generate preview is disable, abort!');
            return false;
        }
        $queueFilesInOption = get_option('_wpfd_previewer_generate_queue_files');
        if (!is_array($queueFilesInOption) || (is_array($queueFilesInOption) && empty($queueFilesInOption))) {
            $queueFilesInOption = $this->generatequeue();
        }
        if (!is_array($queueFilesInOption) || (is_array($queueFilesInOption) && empty($queueFilesInOption))) {
            self::log('No file to generate preview, abort!');
            return false;
        }
        // Sort queue by date_added
        uasort($queueFilesInOption, function ($a, $b) {
            return $a['date_added'] < $b['date_added'] ? -1 : 1;
        });
        // Mark queue on running
        update_option('_wpfd_preview_generate_queue_running', true, false);

        foreach ($queueFilesInOption as $fileId => &$queue) {
            // Check ignore file
            if (isset($queue['ignore']) && intval($queue['ignore']) === 1 || !isset($queue['type'])) {
                continue;
            }
            if ($queue['type'] === 'server') {
                // Send request if not send yet
                if (isset($queue['in_process']) && $queue['in_process'] === 0 && isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0) {
                    self::log('Current queue before send request: ' . json_encode($queue));
                    $this->writeLog($queue['file_id'], 'sending_file');
                    // Send request
                    $requestId = $this->getRequestId($queue);
                    if ($requestId !== self::ABORT_REMOVE && $requestId !== self::RETRY && $requestId !== self::RETRY_ON_MAX_REQUEST) {
                        $queue['in_process'] = 1;
                        $queue['request_id'] = $requestId;
                        $queue['send_request_time'] = time();
                        self::log('Request send!' . json_encode($queue));
                        $this->writeLog($queue['file_id'], 'server_accepted');
                    } elseif ($requestId === self::RETRY_ON_MAX_REQUEST) {
                        // Stop queue running and update current state
                        $queue['in_process'] = 0;
//                        $queue['retries'] += 1; // Do not update retried value on max request error
                        $queue['date_added'] = time();
                        update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                        update_option('_wpfd_preview_generate_queue_running', false, false);
                        self::log('Max request reached! Abort current schedule!');
                        $this->writeLog($queue['file_id'], 'too_many_request');
                        break;
                    } elseif ($requestId === self::RETRY) {
                        $queue['in_process'] = 0;
                        $queue['retries'] += 1;
                        $queue['date_added'] = time();
                        self::log('Retry!' . json_encode($queue));
                    } elseif ($requestId === self::ABORT_REMOVE) { // Remove queue on other error
                        self::log('Queue need remove!' . json_encode($queue));
                        $queue['in_process'] = 0;
                        $queue['preview_generated'] = 0;
                        $queue['ignore'] = 1;
                        self::log('File Ignore!' . json_encode($queue));
                        $this->writeLog($queue['file_id'], 'ignore');
                        //unset($queueFilesInOption[$fileId]);
                        //update_post_meta($queue['file_id'], '_wpfd_preview_generate_ignore', true);
                    } else {
                        $queue['in_process'] = 0;
                        $queue['retries'] += 1;
                        $queue['date_added'] = time();
                        self::log('Retry On Unknown!' . json_encode($queue));
                    }
                    update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                }
                // Get data if not receive notification yet
                if (isset($queue['in_process']) && $queue['in_process'] === 1 && isset($queue['preview_generated']) && $queue['preview_generated'] === 0) {
                    if (isset($queue['request_id']) && $queue['request_id'] > 0) {
                        // Check request time after 5 minute if not receive notification yet
                        if (isset($queue['send_request_time']) && (time() - intval($queue['send_request_time'])) > self::WAIT_MINUTES * 60) {
                            $pages = $this->checkImages($queue);
                            if ($pages === self::GENERATED_FAILED || is_null($pages) || $pages === false) {
                                // Ignore current queue
                                $queue['in_process'] = 0;
                                $queue['preview_generated'] = 0;
                                $queue['ignore'] = 1;
                                $this->writeLog($queue['file_id'], 'too_many_fail_ignore');
                                update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                            } elseif (is_array($pages) && !empty($pages)) {
                                self::log('Pages received! Pages: ' . count($pages));
                                $this->writeLog($queue['file_id'], 'Received ' . count($pages) . ' document pages, creating the preview/thumbnail...');
                                // Save first page only as preview image.
                                $maxPages = count($pages);
                                /**
                                 * The maximum number of page when generate the preview image
                                 *
                                 * @param integer
                                 */
                                $numPage = apply_filters('wpfd_generate_preview_page_number', 3);
                                if ($maxPages >= $numPage) {
                                    $max = $numPage;
                                } else {
                                    $max = $maxPages;
                                }
                                $urls = array();

                                for ($i = 0; $i <= $max - 1; $i++) {
                                    $urls[] = isset($pages[$i]) && isset($pages[$i]['public_url']) ? $pages[$i]['public_url'] : '';
                                }

                                list($savedFilePath, $thumbnailPath) = $this->savePreviewFile($queue['file_id'], $urls);
                                /**
                                 * Action fire after thumbnail/preview saved
                                 *
                                 * @param string                   $savedFilePath Generated preview file path
                                 * @param string                   $thumbnailPath Generated thumbnail file path
                                 * @param array                    $queue         Current queue information
                                 * @param WpfdModelGeneratepreview $this          GeneratePreview Model
                                 *
                                 * @ignore
                                 */
                                do_action('wpfd_preview_generated', $savedFilePath, $thumbnailPath, $queue, $this);

                                $savedFilePath = apply_filters('wpfd_preview_generated_path', $savedFilePath, $queue, $this);

                                $thumbnailPath = apply_filters('wpfd_thumbnail_generated_path', $thumbnailPath, $queue, $this);
//                                $thumbnailPath = $this->saveThumbnail($queue['file_id'], $urls);
                                if (false !== $savedFilePath) {
                                    // Store generated file path to post meta
                                    $savedFilePath = str_replace(WP_CONTENT_DIR, '', $savedFilePath);
                                    update_post_meta($queue['file_id'], '_wpfd_preview_file_path', addslashes($savedFilePath));
                                    // todo: save generated file path for cloud
                                    $queue['in_process'] = 0;
                                    $queue['preview_generated'] = 1;
                                    self::log('Image saved!' . json_encode($queue));
                                    $this->writeLog($queue['file_id'], 'preview_generated');
                                    update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                                } else {
                                    $queue['in_process'] = 0;
                                    $queue['preview_generated'] = 0;
                                    $queue['ignore'] = 1;
                                    update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                                }

                                if (false !== $thumbnailPath) {
                                    $thumbnailPath = str_replace(WP_CONTENT_DIR, '', $thumbnailPath);
                                    update_post_meta($queue['file_id'], '_wpfd_thumbnail_image_file_path', addslashes($thumbnailPath));
                                    update_post_meta($queue['file_id'], '_wpfd_thumbnail_image_file_contain_class', true);
                                }
                            }
                        } else {
                            self::log('Time chenh lech: ' . (time() - intval($queue['send_request_time'])));
                        }
                    }
                }
            }

            if ($queue['type'] === 'image') {
                if ($queue['retries'] > 4) {
                    $queue['ignore'] = 1;
                    $queue['in_process'] = 0;
                    $queue['date_added'] = time();
                    update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                    $this->writeLog($queue['file_id'], 'too_many_fail_ignore');
                    continue;
                }
                if (isset($queue['in_process']) && $queue['in_process'] === 1 && isset($queue['preview_generated']) && $queue['preview_generated'] === 0) {
                    $this->writeLog($queue['file_id'], 'server_accepted');
                    $filePath = $queue['file_path'];
                    $imageQuality = apply_filters('wpfd_thumbnail_image_quantity', 80);
                    $imageSize = apply_filters('wpfd_thumbnail_image_size', array('w' => 200, 'h' => 200));
                    // Generate thumbnail
                    $saveFilePath = WpfdHelperFolder::getThumbnailsPath();
                    $fileName = $saveFilePath . strval($fileId) . '_' . strval(md5($filePath)) . '.png';

                    if (!file_exists($saveFilePath)) {
                        wpfdCreateSecureFolder($saveFilePath);
                    }
                    if (!file_exists($filePath)) {
                        $queue['ignore'] = 1;
                        $queue['in_process'] = 0;
                        $queue['retries'] = 5;
                        $queue['date_added'] = time();
                        update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                        $this->writeLog($queue['file_id'], 'file_not_exists');
                        continue;
                    }
                    $editor = wp_get_image_editor($filePath, array('mime_type' => 'image/png'));

                    if (is_wp_error($editor)) {
                        $queue['in_process'] = 0;
                        $queue['retries'] += 1;
                        $queue['date_added'] = time();

                        update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                        $this->writeLog($queue['file_id'], 'Error while generating thumbnail for image('.$editor->get_error_message().')');
                        continue;
                    }
                    $editor->set_quality($imageQuality);

                    // Check the original image size with
                    $tooSmall = false;
                    $originSize = $editor->get_size();
                    if ($originSize['width'] < $imageSize['w'] && $originSize['height'] <= $imageSize['h']) {
                        // Copy and save
                        WpfdHelperFolder::getFileSystem()->copy($filePath, $fileName, true);
                        $tooSmall = true;
                    } else {
                        $resized = $editor->resize($imageSize['w'], $imageSize['h'], false);

                        if (is_wp_error($resized)) {
                            $queue['in_process'] = 1;
                            $queue['retries'] += 1;
                            $queue['date_added'] = time();

                            update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                            $this->writeLog($queue['file_id'], 'resize_error');
                            continue;
                        }

                        $saved = $editor->save($fileName);

                        if (is_wp_error($saved)) {
                            // Copy source file to thumbnails
                            WpfdHelperFolder::getFileSystem()->copy($filePath, $fileName, true);
                        }
                    }
                    // Generate preview for this image
                    $previewPath = WpfdHelperFolder::getPreviewsPath();

                    $previewFileName = $previewPath . strval($fileId) . '_' . strval(md5($filePath)) . '.png';
                    if ($tooSmall) {
                        WpfdHelperFolder::getFileSystem()->copy($filePath, $previewFileName, true);
                    } else {
                        $previewImageQuality = apply_filters('wpfd_preiew_image_quantity', 90);
                        $previewImageSize = apply_filters('wpfd_preview_image_size', array('w' => 800, 'h' => 800));


                        $editor = wp_get_image_editor($filePath, array('mime_type' => 'image/png'));

                        $editor->set_quality($previewImageQuality);
                        $resized = $editor->resize($previewImageSize['w'], $previewImageSize['h'], false);

                        if (is_wp_error($resized)) {
                            // Copy source file to thumbnails
                            WpfdHelperFolder::getFileSystem()->copy($filePath, $previewFileName, true);
                        } else {
                            $saved = $editor->save($previewFileName);

                            if (is_wp_error($saved)) {
                                // Copy source file to thumbnails
                                WpfdHelperFolder::getFileSystem()->copy($filePath, $previewFileName, true);
                            }
                        }
                    }


                    /**
                     * Action fire after thumbnail/preview saved
                     *
                     * @param string                   $previewFileName Generated preview file path
                     * @param string                   $fileName        Generated thumbnail file path
                     * @param array                    $queue           Current queue information
                     * @param WpfdModelGeneratepreview $this            GeneratePreview Model
                     *
                     * @ignore
                     */
                    do_action('wpfd_preview_generated', $previewFileName, $fileName, $queue, $this);
                    $previewFileName = apply_filters('wpfd_preview_generated_path', $previewFileName, $queue, $this);
                    $fileName = apply_filters('wpfd_thumbnail_generated_path', $fileName, $queue, $this);

                    $queue['in_process'] = 0;
                    $queue['preview_generated'] = 1;
                    $previewFileName = str_replace(WP_CONTENT_DIR, '', $previewFileName);
                    update_post_meta($queue['file_id'], '_wpfd_preview_file_path', addslashes($previewFileName));
                    update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                    $this->writeLog($queue['file_id'], 'preview_generated');

                    $newFilePath = str_replace(WP_CONTENT_DIR, '', $fileName);
                    update_post_meta($queue['file_id'], '_wpfd_thumbnail_image_file_path', addslashes($newFilePath));
                    update_post_meta($queue['file_id'], '_wpfd_thumbnail_image_file_contain_class', true);
                } elseif (isset($queue['in_process']) && $queue['in_process'] === 0 && isset($queue['preview_generated']) && $queue['preview_generated'] === 0 && !isset($queue['ignore'])) {
                    $queue['in_process'] = 1;
                    $queue['date_added'] = time();

                    update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
                    $this->writeLog($queue['file_id'], 'file_editor_retrieve');
                } else {
                    self::log(json_encode($queue));
                }
            }
        }
        update_option('_wpfd_previewer_generate_queue_files', $queueFilesInOption, false);
        update_option('_wpfd_preview_generate_queue_running', false, false);
    }

    /**
     * Restart the queue from beginning
     *
     * @return boolean
     */
    public function restartQueue()
    {
        // Delete all preview files generated
        $this->deleteAllPreviewFiles();
        // Delete all postmeta
        delete_metadata('wpfd_file', 0, '_wpfd_preview_file_path', false, true);
        // Delete all options?
        delete_option('_wpfd_preview_generate_queue_running');
        delete_option('_wpfd_previewer_generate_queue_files');

        do_action('wpfd_before_generator_restart');

        // Run generate queue
        return $this->generateQueue();
    }

    /**
     * Get current queue status
     *
     * @return array
     */
    public function getStatus()
    {
        $juToken = $this->getJuToken();
        if (!$juToken || $juToken === '') {
            return array('error' => true, 'code' => 'user_not_login', 'message' => esc_html__('Please connect your Joomunited account!', 'wpfd'));
        }
        $defaultStatus = array(
            'p_total' => 0,
            'p_processing' => 0,
            'p_pending' => 0,
            'p_generated' => 0,
            'p_error' => 0,
            'error_files_id' => array(),
            'logs' => '',
        );
        // Get current queue
        $queueFilesInOption = get_option('_wpfd_previewer_generate_queue_files');
        if (!is_array($queueFilesInOption) || (is_array($queueFilesInOption) && empty($queueFilesInOption))) {
            return $defaultStatus;
        }
        // 1. Total files can generate preview
        $defaultStatus['p_total'] = count($queueFilesInOption);

        foreach ($queueFilesInOption as $queue) {
            if (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0 && isset($queue['in_process']) && intval($queue['in_process']) === 0 && !isset($queue['ignore'])) {
                // 2. Total files not generate yet. preview_generated = 0, on_process = 0
                $defaultStatus['p_pending']++;
            } elseif (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 1 && isset($queue['in_process']) && intval($queue['in_process']) === 0) {
                // 3. Generated
                $defaultStatus['p_generated']++;
            } elseif (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0 && isset($queue['in_process']) && intval($queue['in_process']) === 1) {
                // 4. Total files on processing.
                $defaultStatus['p_processing']++;
            } elseif (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0 && isset($queue['in_process']) && intval($queue['in_process']) === 0 && isset($queue['ignore']) && intval($queue['ignore']) === 1) {
                $defaultStatus['p_error']++;
                $defaultStatus['error_files_id'][] = $queue['file_id'];
            }
        }

        if ($defaultStatus['p_error'] > 0) {
            $defaultStatus['error_message'] = sprintf(_n('%d file previews cannot be generated', '%d file previews cannot be generated', $defaultStatus['p_error'], 'wpfd'), $defaultStatus['p_error']);
        }

        // Get logs
        $logs = $this->getLatestLog();

        $defaultStatus['logs'] = $logs ? $logs : '';

        return $defaultStatus;
    }

    /**
     * Download preview file from API
     *
     * @return void
     */
    public function previewDownload()
    {
        $status = 200;
        header('X-PHP-Response-Code: ' . $status);
        header('Status: ' . $status);
        $datas = file_get_contents('php://input');
        self::log($datas);
        if ($datas) {
            $datas = json_decode($datas, true);
        }
        // Get file id from queue by request id
        $queues = get_option('_wpfd_previewer_generate_queue_files', array());
        if (empty($queues)) {
            self::log('Empty queue');
            return;
        }
        $requestFileId = 0;
        $currentQueue = array();
        foreach ($queues as $fileId => $queue) {
            if ($queue['request_id'] === $datas['id']) {
                $requestFileId = $fileId;
                $currentQueue = $queue;
                break;
            }
        }
        if ($requestFileId === 0) {
            return;
        }

        if (isset($datas['id']) && isset($datas['status']) && $datas['status'] === 'success') {
            $pages = $datas['pages'];
            if (!is_array($pages) && count($pages) === 0) {
                // Empty page?
                // Ignore current queue
                $currentQueue['in_process'] = 0;
                $currentQueue['preview_generated'] = 0;
                $currentQueue['ignore'] = 1;
                $queues[$requestFileId] = $currentQueue;
                update_option('_wpfd_previewer_generate_queue_files', $queues, false);
                self::log('Pages received but empty. Ignored!' . json_encode($currentQueue));
                return;
            }
            $maxPages = count($pages);
            /**
             * The maximum number of page when generate the preview image
             *
             * @param integer
             *
             * @ignore
             */
            $numPage = apply_filters('wpfd_generate_preview_page_number', 3);
            if ($maxPages >= $numPage) {
                $max = $numPage;
            } else {
                $max = $maxPages;
            }
            $urls = array();

            for ($i = 0; $i <= $max - 1; $i++) {
                $urls[] = isset($pages[$i]) && isset($pages[$i]['public_url']) ? $pages[$i]['public_url'] : '';
            }
            list($savedFilePath, $thumbnailPath) = $this->savePreviewFile($requestFileId, $urls);

            /**
             * Action fire after thumbnail/preview saved
             *
             * @param string                   $savedFilePath Generated preview file path
             * @param string                   $thumbnailPath Generated thumbnail file path
             * @param array                    $currentQueue  Current queue information
             * @param WpfdModelGeneratepreview $this          GeneratePreview Model
             */
            do_action('wpfd_preview_generated', $savedFilePath, $thumbnailPath, $currentQueue, $this);
            $savedFilePath = apply_filters('wpfd_preview_generated_path', $savedFilePath, $currentQueue, $this);

            $thumbnailPath = apply_filters('wpfd_thumbnail_generated_path', $thumbnailPath, $currentQueue, $this);
            if (false !== $savedFilePath) {
                // Store generated file path to post meta
                $savedFilePath = str_replace(WP_CONTENT_DIR, '', $savedFilePath);
                update_post_meta($requestFileId, '_wpfd_preview_file_path', addslashes($savedFilePath));
                $currentQueue['in_process'] = 0;
                $currentQueue['preview_generated'] = 1;
                $queues[$requestFileId] = $currentQueue;
                update_option('_wpfd_previewer_generate_queue_files', $queues, false);
                self::log('Image saved!' . json_encode($currentQueue));
                $this->writeLog($currentQueue['file_id'], 'preview_generated');
            }

            if (false !== $thumbnailPath) {
                $thumbnailPath = str_replace(WP_CONTENT_DIR, '', $thumbnailPath);
                update_post_meta($currentQueue['file_id'], '_wpfd_thumbnail_image_file_path', addslashes($thumbnailPath));
                update_post_meta($currentQueue['file_id'], '_wpfd_thumbnail_image_file_contain_class', true);
            }
        } elseif (isset($datas['id']) && isset($datas['status']) && $datas['status'] === 'failed') {
            // Ignore current queue
            $currentQueue['in_process'] = 0;
            $currentQueue['preview_generated'] = 0;
            $currentQueue['ignore'] = 1;
            $queues[$requestFileId] = $currentQueue;
            update_option('_wpfd_previewer_generate_queue_files', $queues, false);
            self::log('Failed on generate preview file. Ignored!' . json_encode($currentQueue));
            $this->writeLog($fileId, 'too_many_fail_ignore');
        }
    }

    /**
     * Save preview file generated
     *
     * @param string|integer $fileId File id
     * @param string|array   $urls   URL of generated document
     * @param string         $prefix Prefix for file name. Useful to fast select files
     * @param string         $suffix Suffix for file name. Useful to fast select files
     *
     * @return boolean|string
     */
    public function savePreviewFile($fileId, $urls, $prefix = '', $suffix = '')
    {
        if (empty($fileId)) {
            return false;
        }
        $thumbnailPath = false;
        $filePath = WpfdHelperFolder::getPreviewsPath();
        $fileName = $filePath . $prefix . strval($fileId) . '_' . strval(uniqid()) . $suffix . '.png';

        if (is_array($urls)) {
            $allTempFiles = array();
            // Download all images
            foreach ($urls as $url) {
                // Try to use native wordpress download function
                if (function_exists('download_url')) {
                    $downloadedFile = download_url($url);
                    if (!is_wp_error($downloadedFile)) {
                        $allTempFiles[] = $downloadedFile;
                    }
                } else {
                    $tempFile = tempnam(sys_get_temp_dir(), 'wpfd_');
                    $response = file_get_contents($url);
                    if ($response) {
                        $downloadedFile = file_put_contents($tempFile, $response);
                    }

                    if (false !== $downloadedFile) {
                        $allTempFiles[] = $tempFile;
                    }
                }
            }
            // Save the first preview
            if (isset($allTempFiles[0])) {
                $thumbnailPath = $this->resizeThumbnail($allTempFiles[0], $fileId, $prefix, $suffix);
            }
            // Merge files into one or save the first preview file only
            $this->mergeImageVertical($allTempFiles, $fileName);
        } elseif (gettype($urls) === 'string' && $urls !== '') {
            // Single url
            if (function_exists('download_url')) {
                $downloadedFile = download_url($urls);
                if (!is_wp_error($downloadedFile)) {
                    rename($downloadedFile, $fileName);
                }
            } else {
                $response = file_get_contents($urls);
                if ($response) {
                    file_put_contents($fileName, $response);
                }
            }
            $thumbnailPath = $this->resizeThumbnail($fileName, $fileId, $prefix, $suffix);
        }

        if (file_exists($fileName)) {
            /**
             * Filter allow to do anything with the generated preview file
             *
             * @param string
             */
            $fileName = apply_filters('wpfd_generated_preview_file_real_path', $fileName);
            $fileName = str_replace(WP_CONTENT_DIR, '', $fileName);
            return array(addslashes($fileName), $thumbnailPath);
        }

        return false;
    }

    /**
     * Save thumbnail file generated
     *
     * @param string|integer $fileId File id
     * @param string|array   $urls   URL of generated document
     * @param string         $prefix Prefix for file name. Useful to fast select files
     * @param string         $suffix Suffix for file name. Useful to fast select files
     *
     * @return boolean|string
     */
    public function saveThumbnail($fileId, $urls, $prefix = '', $suffix = '')
    {
        if (empty($fileId)) {
            return false;
        }

        if (is_array($urls)) {
            $urls = array_shift($urls);
        }

        if (function_exists('download_url')) {
            $downloadedFile = download_url($urls);
            if (!is_wp_error($downloadedFile)) {
                $tempFiles = $downloadedFile;
            }
        } else {
            $tempFile = tempnam(sys_get_temp_dir(), 'wpfd_');
            $response = file_get_contents($urls);
            if ($response) {
                $downloadedFile = file_put_contents($tempFile, $response);
            }

            if (false !== $downloadedFile) {
                $tempFiles = $tempFile;
            }
        }

        return $this->resizeThumbnail($tempFiles, $fileId, $prefix, $suffix);
    }

    /**
     * Resize the thumbnail image
     *
     * @param string  $tempFilePath Temp file path
     * @param integer $fileId       File id
     * @param string  $prefix       File name prefix
     * @param string  $suffix       File name suffix
     *
     * @return boolean|string|string[]
     */
    public function resizeThumbnail($tempFilePath, $fileId, $prefix = '', $suffix = '')
    {
        $filePath = WpfdHelperFolder::getThumbnailsPath();

        $fileName = $filePath . $prefix . strval($fileId) . '_' . strval(uniqid()) . $suffix . '.png';
        $imageQuality = apply_filters('wpfd_thumbnail_image_quantity', 80);
        $imageSize = apply_filters('wpfd_thumbnail_image_size', array('w' => 200, 'h' => 200));

        $editor = wp_get_image_editor($tempFilePath, array('mime_type' => 'image/png'));

        if (is_wp_error($editor)) {
            return false;
        }

        $editor->set_quality($imageQuality);

        $resized = $editor->resize($imageSize['w'], $imageSize['h'], false);

        if (is_wp_error($resized)) {
            return false;
        }

        $saved = $editor->save($fileName);

        if (is_wp_error($saved)) {
            return false;
        }

        /**
         * Action file after thumbnail resized
         *
         * @param string $fileName Thumbnail file path
         */
        do_action('wpfd_generate_preview_after_resize_thumbnail', $fileName, $this);

        return str_replace(WP_CONTENT_DIR, '', $fileName);
    }
    /**
     * Merge images
     *
     * @param array  $images          Source image path
     * @param string $destinationPath Save path
     *
     * @return boolean
     */
    public function mergeImageVertical($images, $destinationPath)
    {
        $imgs = array();
        $success = false;
        $error = '';

        /**
         * Action fire before merge image vertical
         *
         * @param array                    $images          Images page array
         * @param string                   $destinationPath Merged image path
         * @param WpfdModelGeneratepreview $this            Current Model
         */
        do_action('wpfd_generate_preview_before_merge_image_vertical', $images, $destinationPath, $this);
        $images = apply_filters('wpfd_generate_preview_before_merge_image_vertical', $images, $destinationPath, $this);
        // ImageMagick extension
        if ($success === false && extension_loaded('imagick')) {
            try {
                $img = new Imagick;
                $lastKey = key(array_slice($images, -1, null, true));
                foreach ($images as $key => $image) {
                    $img->readImage($image);
                    if ($key !== $lastKey) {
                        // Generate new image for seperator
                        $tempImage = new Imagick($image);
                        $geo = $tempImage->getImageGeometry();
                        $sizex = $geo['width'];
                        $img->newImage($sizex, 5, 'none');  // Add seperator
                        $tempImage->destroy();
                    }
                }
                $img->resetIterator();
                $combined = $img->appendImages(true);

                $combined->setImageFormat('png');
                if ($combined->writeImage($destinationPath)) {
                    $success = true;
                }
            } catch (Exception $e) {
                // Imagemagick fails, try with GD
                $error = 'Imagick library error: ' . $e->getMessage();
            }
        }
        // GD extension
        if ($success === false && function_exists('imagecreatefrompng')) {
            try {
                foreach ($images as $image) {
                    $img = array();
                    list($img['width'], $img['height'], $img['type']) = getimagesize($image);
                    if ($img['type'] === 3) { // PNG
                        $img['instance'] = imagecreatefrompng($image);
                    } elseif ($img['type'] === 2) { // JPEG
                        $img['instance'] = imagecreatefromjpeg($image);
                    } else {
                        continue;
                    }

                    $imgs[] = $img;
                }

                // Compute new width/height
                $new_width = 0;
                $new_height = 0;
                foreach ($imgs as $img) {
                    $new_width = ($img['width'] > $new_width) ? $img['width'] : $new_width;
                    $new_height += $img['height'];
                }

                if ($new_width > 0 && $new_height > 0) {
                    // Create new image and merge
                    $new = imagecreatetruecolor($new_width, $new_height);
                    imagesavealpha($new, true);

                    $trans_colour = imagecolorallocatealpha($new, 0, 0, 0, 127);
                    imagefill($new, 0, 0, $trans_colour);
                    $last_top_height = 0;
                    foreach ($imgs as $key => $img) {
                        if ($last_top_height > 0) {
                            $last_top_height += 5; // Add seperator
                        }
                        imagecopy($new, $img['instance'], 0, $last_top_height, 0, 0, $img['width'], $img['height']);
                        $last_top_height += $img['height'];
                    }
                    // Save to file
                    imagepng($new, $destinationPath, 9);

                    $success = true;
                }
            } catch (Exception $e) {
                // GD fails
                $error = 'GD library error: ' . $e->getMessage();
            }
        }
        // Do nothing, return false to save the first image only
        if ($success === false) {
            self::log($error);
            // Save the first image
            reset($images);
            if (!empty(current($images)) && rename(current($images), $destinationPath)) {
                $success = true;
            }
        }

        /**
         * Action fire after merge image vertical
         *
         * @param string                   $destinationPath Merged image path
         * @param WpfdModelGeneratepreview $this            Current Model
         */
        do_action('wpfd_generate_preview_after_merge_image_vertical', $destinationPath, $this);
        return $success;
    }
    /**
     * Delete preview files generated
     *
     * @param string|integer $fileId File id
     *
     * @return boolean
     */
    public function deletePreviewFiles($fileId)
    {
        if (empty($fileId)) {
            return false;
        }
        $filePath = WpfdHelperFolder::getPreviewsPath();
        $filesPath = glob($filePath . strval($fileId) . '_*.png');
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }

        return true;
    }

    /**
     * Delete thumbnail files generated
     *
     * @param string|integer $fileId File Id
     *
     * @return boolean
     */
    public function deleteThumbnailFiles($fileId)
    {
        if (empty($fileId)) {
            return false;
        }
        $filePath = WpfdHelperFolder::getThumbnailsPath();
        $filesPath = glob($filePath . strval($fileId) . '_*.png');
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }

        return true;
    }

    /**
     * Delete log file on reset
     *
     * @return boolean
     */
    public function deleteLogFile()
    {
        $logPath = $this->getLogFilePath();
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        return true;
    }
    /**
     * Copy preview file
     *
     * @param string|integer $fileId Source file id
     * @param string|integer $newId  New file Id
     *
     * @return boolean
     */
    public function copyPreviewFile($fileId, $newId)
    {
        if (empty($fileId) || empty($newId)) {
            return false;
        }
        $filePath = WpfdHelperFolder::getPreviewsPath();
        $filesPath = glob($filePath . strval($fileId) . '_*.png');
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                $newFileName = $filePath . strval($newId) . '_' . strval(uniqid()) . '.png';
                copy($fileName, $newFileName);
                update_post_meta($newId, '_wpfd_preview_file_path', addslashes($newFileName));
                return true;
            }
        }

        return false;
    }

    /**
     * Delete all preview files on disk
     *
     * @return boolean
     */
    public function deleteAllPreviewFiles()
    {
        $filePath = WpfdHelperFolder::getPreviewsPath();
        $thumbnailsPath = WpfdHelperFolder::getThumbnailsPath();
        $filesPath = glob($filePath . '*_*.[pPjJ][nNpP][gG]');
        $thumbnailsFilesPath = glob($thumbnailsPath . '*_*.[pPjJ][nNpP][gG]');
        $filesPath = $filesPath + $thumbnailsFilesPath;
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }

        return true;
    }

    /**
     * Get convert id from server
     *
     * @param array $queue Request params
     *                      ['file_id' => $id_file,
     *                      'file_path' => $file_dir . $newname,
     *                      'file_ext' => $file_ext]
     *
     * @return integer|boolean
     */
    public function getRequestId($queue)
    {
        if (!$this->juToken || $this->juToken === '') {
            self::log('JuToken missing!');
        }

        if (isset($queue['retries']) && $queue['retries'] > self::MAX_RETRIES) {
            self::log('File reach max retries: ' . $queue['file_id']);
            $this->writeLog($queue['file_id'], 'too_many_fail_ignore');
            return self::ABORT_REMOVE;
        }

        $filePath = isset($queue['file_path']) ? $queue['file_path'] : '';
        if (!file_exists($filePath)) {
            self::log('File path not exists: ' . $filePath);
            return self::ABORT_REMOVE;
        }

        $fileExtension = isset($queue['file_ext']) ? $queue['file_ext'] : '';

        if (!in_array(strtolower($fileExtension), $this->supportExtensions)) {
            self::log('Extension not support: ' . $filePath . ' File ext: ' . $queue['file_ext']);
            return self::ABORT_REMOVE;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'file'=> new CURLFILE($filePath), // phpcs:ignore PHPCompatibility.Classes.NewClasses.curlfileFound -- It's Ok, we use php >= 5.6
                'notification' => $this->pushUrl // A push url to be called when the optimization is finished, the submitted content is the same than you can retrieve with the get rest method
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->juToken, // We use the juupdater token as api key
            ),
        ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            self::log($error_msg);
            return self::RETRY;
        }

        self::log($response);

        if ($response !== '' && intval($info['http_code']) === 200) {
            $response = json_decode($response, true);
            return isset($response['id']) ? $response['id'] : self::RETRY;
        } elseif ($response !== '' && intval($info['http_code']) === 429) { // Too many request
            self::log($response);
            return self::RETRY_ON_MAX_REQUEST; // We should try again late
        }

        // For any other error, bypass current file and remove current queue.
        return self::ABORT_REMOVE;
    }

    /**
     * Check API to get image generated
     *
     * @param array $queue Queue
     *
     * @return boolean
     */
    public function checkImages($queue)
    {
        if (!is_array($queue) || (isset($queue['request_id']) && $queue['request_id'] === '')) {
            return false;
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/' . $queue['request_id'], // Replace the file id retrieve from upload
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->juToken, // We use the juupdater token as api key
            ),
        ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            self::log($error_msg);
            return false;
        }
        self::log($response);

        if ($response !== '' && intval($info['http_code']) === 200) {
            $datas = json_decode($response, true);

            if (isset($datas['status'])) {
                switch ($datas['status']) {
                    case 'success':
                        return isset($datas['pages']) ? $datas['pages'] : false;
                    case 'pending':
                        $this->writeLog($queue['file_id'], 'server_not_processed');
                        return false;
                    case 'failed':
                        return self::GENERATED_FAILED;
                }
            }
        }

        return false;
    }


    /**
     * Get source file path
     *
     * @param integer $fileId File id
     *
     * @return boolean|string
     */
    public function getSourceFilePath($fileId)
    {
        $fileMeta = get_post_meta($fileId, '_wpfd_file_metadata', true);

        if (!$fileMeta || empty($fileMeta)) {
            self::log('File meta not exist!');
            return false;
        }

        // Get category id
        $term_list = wp_get_post_terms($fileId, 'wpfd-category', array('fields' => 'ids'));
        if (!is_wp_error($term_list) && count($term_list) > 0) {
            $catId = $term_list[0];
        } else {
            $catId = 0;
        }
        if (!class_exists('WpfdBase')) {
            require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/classes/WpfdBase.php';
        }

        $ftpFile = (isset($fileMeta['wpfd_sync_ftp_file']) && $fileMeta['wpfd_sync_ftp_file']
            && isset($fileMeta['wpfd_sync_ftp_path']) && !empty($fileMeta['wpfd_sync_ftp_path'])) ? true : false;

        if ($ftpFile) {
            $sourceFilePath = $fileMeta['wpfd_sync_ftp_path'];
        } else {
            $sourceFilePath = WpfdBase::getFilesPath($catId) . $fileMeta['file'];
        }

        if (!file_exists($sourceFilePath)) {
            return false;
        }

        return $sourceFilePath;
    }
    /**
     * Get joomunited token
     *
     * @return string|boolean
     */
    private function getJuToken()
    {
        return get_option('ju_user_token', false);
    }

    /**
     * Log into a debug file
     *
     * @param string $msg Message
     *
     * @return void
     */
    public static function log($msg = '')
    {
        // Do nothing if not enabled
        if (!self::$debug) {
            return;
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Log if enable debug
        error_log($msg);
    }

    /**
     * Process log
     */

    /**
     * Write log
     *
     * @param object|mixed $fileId File id
     * @param string       $slug   Message slug
     *
     * @return void
     */
    public function writeLog($fileId, $slug = '')
    {

        $dateString = date('Y-m-d H:i:s');
        $fileAddress = '';

        $logFileName = $this->getLogFilePath();


        if (!empty($fileId)) {
            // Prepare file path
            $fileAddress = $this->getFileAddress($fileId);
        }

        if ($fileAddress) {
            $fileAddress = ' ' . $fileAddress;
        }

        // Prepare log message

        $message = sprintf('[%s]%s: %s', $dateString, $fileAddress, $this->getMessage($slug));

        // Append log to file
        $this->writeMessage($message, $logFileName);
    }

    /**
     * Get log of file path
     *
     * @return mixed
     */
    public function getLogFilePath()
    {
        $ds = DIRECTORY_SEPARATOR;
        $logFilePath = WP_CONTENT_DIR . $ds . wpfd_get_content_dir() . $ds . 'logs';
        wpfdCreateSecureFolder($logFilePath);

        return $logFilePath . $ds. 'preview_generate_process.log';
    }

    /**
     * Get last log
     *
     * @param mixed $linesNumber Number of display rows
     *
     * @return boolean
     */
    public function getLatestLog($linesNumber = 20)
    {
        $logFilePath = $this->getLogFilePath();
        try {
            $logFile = new \SplFileObject($logFilePath, 'r');
            $logFile->seek(PHP_INT_MAX);
            $lastLine = $logFile->key();
            if ($lastLine <= $linesNumber) {
                $offset = 0;
            } else {
                $offset = $lastLine - $linesNumber;
            }
            $lines = new \LimitIterator($logFile, $offset, $lastLine);
            $linesArray = iterator_to_array($lines);

            return implode('', $linesArray);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * WriteMessage
     *
     * @param string $message  Message
     * @param string $fileName Destination file name
     *
     * @return void|mixed
     */
    public function writeMessage($message = '', $fileName = '') // phpcs:ignore PEAR.Functions.ValidDefaultValue.NotAtEnd -- it worked
    {
        $hl = fopen($fileName, 'a');

        flock($hl, LOCK_EX);
        fwrite($hl, $message . "\n");
        flock($hl, LOCK_UN);

        fclose($hl);
    }


    /**
     * Get the message by slug
     *
     * @param string $slug Message slug
     *
     * @return string
     */
    public function getMessage($slug = '')
    {
        $messages = array(
            'sending_file' => esc_html__('Sending file...', 'wpfd'),
            'server_accepted' => esc_html__('Accepted for preview generation, waiting for generation completion', 'wpfd'),
            'server_not_processed' => esc_html__('Not processed by server yet', 'wpfd'),
            'preview_generated' => esc_html__('Preview generated and saved', 'wpfd'),
            'too_many_request' => esc_html__('Too many files sent at the same time, waiting for other files we\'ve submitted to be completed', 'wpfd'),
            'too_many_fail_ignore' => esc_html__('Failed on generate preview file. Ignored!', 'wpfd'),
            'disabled' => esc_html__('Generate preview/thumbnail is disabled, please enable and save the settings first!', 'wpfd'),
            'ignore' => esc_html__('File ignore!', 'wpfd'),
            'file_not_exists' => esc_html__('File not exists on disk! Ignored!', 'wpfd'),
            'resize_error' => esc_html__('Resize file failed, retry later!', 'wpfd'),
            'file_editor_retrieve' => esc_html__('Retrieve file editor failed, retry later!', 'wpfd'),
        );

        if (!isset($messages[$slug])) {
            return $slug;
        }

        return (string) $messages[$slug];
    }

    /**
     * Get File Address
     *
     * @param integer $fileId File id
     *
     * @return boolean|string
     */
    public function getFileAddress($fileId)
    {
        $catId = 0;
        $file = get_post($fileId);

        if (is_wp_error($file)) {
            return false;
        }

        $fileMeta = get_post_meta($fileId, '_wpfd_file_metadata', true);
        $fileExt = isset($fileMeta['ext']) ? '.' . $fileMeta['ext'] : '';

        $term_list = wp_get_post_terms($fileId, 'wpfd-category', array('fields' => 'ids'));
        if (!is_wp_error($term_list) && count($term_list) > 0) {
            $catId = $term_list[0];
        }

        $categoryFilePath = $this->getCategoryParents($catId);

        if (is_wp_error($categoryFilePath)) {
            return false;
        }

        return $categoryFilePath . $file->post_title . $fileExt;
    }
    /**
     * Generate category path
     *
     * @param integer $category_id Category id
     * @param boolean $link        Add link to the category list
     * @param string  $separator   The Separator
     * @param boolean $nicename    Use category nicename (slug)
     *
     * @return string|WP_Error
     */
    public function getCategoryParents($category_id, $link = false, $separator = '/', $nicename = false)
    {
        $format = $nicename ? 'slug' : 'name';

        $args = array(
            'separator' => $separator,
            'link' => $link,
            'format' => $format,
        );

        return get_term_parents_list($category_id, 'wpfd-category', $args);
    }
}
