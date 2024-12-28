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
use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();

/**
 * Class WpfdControllerFile
 */
class WpfdControllerFile extends Controller
{

    /**
     * Method to download a file
     *
     * @param integer $id      File id
     * @param integer $catid   Category id
     * @param integer $preview Is preview
     *
     * @return void
     */
    public function download($id = 0, $catid = 0, $preview = 0)
    {
        if (empty($catid)) {
            $catid = Utilities::getInput('wpfd_category_id', 'GET', 'none');
        }
        if (empty($id)) {
            $id = Utilities::getInput('wpfd_file_id', 'GET', 'none');
        }
        if (empty($preview)) {
            $preview = Utilities::getInput('preview', 'GET', 'none');
        }

        if (is_null($preview)) {
            $preview = 0;
        }

        if (empty($id) || empty($catid)) {
            exit();
        }

        $token = Utilities::getInput('token', 'GET', 'string');

        if (!$preview && !wpfd_can_download_files()) {
            /**
             * Action fire when current user not enough permission to download this file.
             *
             * @param string|integer
             * @param string|integer
             * @param integer
             */
            do_action('wpfd_download_file_permission', $id, $catid, $preview);
            exit(esc_html__('You don\'t have permission to download this file.', 'wpfd'));
        }

        if ($preview && !wpfd_can_preview_files() && $token === '') {
            /**
             * Action fire when current user not enough permission to preview this file.
             *
             * @param string|integer
             * @param string|integer
             * @param integer
             */
            do_action('wpfd_preview_file_permission', $id, $catid, $preview);
            exit(esc_html__('You don\'t have permission to preview this file.', 'wpfd'));
        }
        if (WpfdHelperFile::wpfdIsExpired((int)$id) === true) {
            /**
             * Action for the expired download page
             *
             * @param string|integer
             * @param string|integer
             * @param integer
             */
            do_action('wpfd_download_link_expired', $id, $catid, $preview);
            exit();
        }
        Application::getInstance('Wpfd');
        $modelCategory = $this->getModel('categoryfront');
        $modelConfig   = $this->getModel('configfront');
        $model         = $this->getModel('filefront');
        $modelNotify   = $this->getModel('notification');
        $modelTokens   = $this->getModel('tokens');

        $config       = $modelConfig->getGlobalConfig();
        $category     = $modelCategory->getCategory($catid);
        $configNotify = $modelNotify->getNotificationsConfig();

        if (empty($category) || is_wp_error($category)) {
            exit(esc_html__('Category is not correct', 'wpfd'));
        }

        /**
         * Filter to check category source
         *
         * @param integer Term id
         *
         * @return string
         *
         * @internal
         */
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catid);
        if ($categoryFrom === 'googleDrive') {
            /**
             * Filter to check google category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @internal
             *
             * @return string
             */
            $catid = apply_filters('wpfdAddonDownloadCheckGoogleDriveCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($categoryFrom === 'googleTeamDrive') {
            /**
             * Filter to check google team drive category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @internal
             *
             * @return string
             */
            $catid = apply_filters('wpfdAddonDownloadCheckGoogleTeamDriveCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($categoryFrom === 'dropbox') {
            /**
             * Filter to check dropbox category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @internal
             *
             * @return string
             */
            $catid = apply_filters('wpfdAddonDownloadCheckDropboxCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($categoryFrom === 'onedrive') {
            /**
             * Filter to check onedrive category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @internal
             *
             * @return string
             */
            $catid = apply_filters('wpfdAddonDownloadCheckOneDriveCategory', $catid, $id);

            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($categoryFrom === 'onedrive_business') {
            /**
             * Filter to check onedrive business category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @internal
             *
             * @return string
             */
            $catid = apply_filters('wpfdAddonDownloadCheckOneDriveBusinessCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($categoryFrom === 'aws') {
            /**
             * Filter to check AWS category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @internal
             *
             * @return string
             */
            $id = stripslashes(rawurldecode($id));
            $catid = apply_filters('wpfdAddonDownloadCheckAwsCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($categoryFrom === 'nextcloud') {
            /**
             * Filter to check Nextcloud category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @internal
             *
             * @return string
             */
            $id = stripslashes($id);
            $catid = apply_filters('wpfdAddonDownloadCheckNextcloudCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } else {
            $file_catid = $model->getFileCategory($id);
            if ((int) $catid !== (int) $file_catid) {
                // Try to get ref catid
                if (!$model->isValidRefCatId($id, $catid)) {
                    exit(esc_html__('Download url is not correct', 'wpfd'));
                }
            }
        }

        if ((int) $category->access === 1) {
            $user  = wp_get_current_user();
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows = array_intersect($roles, $category->roles);

            if (empty($allows)) {
                $modelTokens->removeTokens();
                $tokenId = $modelTokens->tokenExists($token);
                if ($tokenId) {
                    $modelTokens->updateToken($tokenId);
                } else {
                    if (isset($category->params['canview']) && !empty($category->params['canview'])) {
                        if ((int) $category->params['canview'] !== 0 && (int) $category->params['canview'] !== $user->ID) {
                            /**
                             * Filter to redirect user when they don't have permission to download current file
                             *
                             * @param string
                             */
                            $redirect = apply_filters('wpfd_you_dont_have_permission_redirect_url', false);
                            if ($redirect) {
                                if (!wp_safe_redirect($redirect)) {
                                    header('HTTP/1.0 403 You don\'t have permission');
                                    exit();
                                } else {
                                    exit;
                                }
                            } else {
                                header('HTTP/1.0 403 You don\'t have permission');
                                exit();
                            }
                        }
                    } else {
                        $redirectPageId = isset($config['not_authorized_page']) ? intval($config['not_authorized_page']) : 0;
                        $pageUri = get_permalink($redirectPageId);
                        /**
                         * Filter to redirect user when they not authorized to download current file
                         *
                         * @param string
                         */
                        $redirect = apply_filters('wpfd_not_authorized_redirect_url', $pageUri);
                        if ($redirect) {
                            if (!wp_safe_redirect($redirect)) {
                                header('HTTP/1.0 403 Not authorized');
                                exit();
                            } else {
                                exit;
                            }
                        } else {
                            header('HTTP/1.0 403 Not authorized');
                            exit();
                        }
                    }
                }
            }
        }

//        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//            exit;
//        }

        // Download limit file handle
        $isLimitDownload = (isset($config['limit_the_download']) && intval($config['limit_the_download']) === 1
            && isset($config['track_user_download']) && intval($config['track_user_download']) === 1) ? true : false;
        if ($isLimitDownload && WpfdHelperFile::downloadLimitHandle($id, $catid)) {
            exit(esc_html__('You have exceeded the maximum number of downloads allowed for this period.', 'wpfd'));
        }

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
        $watermarkedPath = $watermarkedPath . strval($catid) . '_' . strval(md5($id)) . '.png';

        /**
         * Download file from WP FileDownload when not exist $fileInfo or wpfdAddon not active
         */
        if ($categoryFrom === 'googleDrive') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);
            /**
             * Filters to get google file info
             *
             * @param string File id
             *
             * @internal
             *
             * @return object
             */
            $file = apply_filters('wpfdAddonDownloadGoogleDriveFile', $id);
            $fileObj = apply_filters('wpfdAddonGetGoogleDriveFile', $id, $category->term_id, $token);
            $fileState = (isset($file->state) && intval($file->state) === 0) ? false : true;
            $fileDownloadUrl = isset($fileObj['linkdownload']) ? $fileObj['linkdownload'] : '#';
            $fileUploadDate = isset($fileObj['created']) ? $fileObj['created'] : '';

            // Do not download unpublished file.
            if (!$fileState) {
                exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
            }

            if ((int) $preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int) $config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }

            /**
             * Action fire right before a file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             */
            do_action('wpfd_file_download', $id, array('source' => 'googledrive'));

            $googleCate = new wpfdAddonGoogleDrive;
            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);

            if ($wmCategoryEnabled && file_exists($watermarkedPath)) {
                $fileSize = filesize($watermarkedPath);
                $this->downloadHeader($file->title . '.' . $file->ext, (int) $fileSize, $contentType, $config, $file, $preview);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                echo file_get_contents($watermarkedPath);
            } else {
                // Serve download for google document
                if (strpos($file->mimeType, 'vnd.google-apps') !== false) { // Is google file
                    // GuzzleHttp\Psr7\Response
                    $fileData = $googleCate->downloadGoogleDocument($file->id, $file->exportMineType);
                    if ($fileData instanceof \GuzzleHttp\Psr7\Response) {
                        $contentLength = $fileData->getHeaderLine('Content-Length');
                        $contentType = $fileData->getHeaderLine('Content-Type');
                        if ($fileData->getStatusCode() === 200) {
                            $this->downloadHeader(
                                $file->title . '.' . $file->ext,
                                (int) $contentLength,
                                $contentType,
                                $config,
                                $file,
                                $preview
                            );
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                            echo $fileData->getBody();
                        }
                    }
                } else {
                    $googleCate->downloadLargeFile($file, $contenType, false, intval($preview));
                }
            }

            $this->sendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
        } elseif ($categoryFrom === 'googleTeamDrive') {
            /**
             * Action fire before get file information from cloud team drive.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
//            do_action('wpfd_before_cloud_team_drive_download_file', $id, $categoryFrom, $category->term_id);
            /**
             * Filters to get google team drive file info
             *
             * @param string File id
             *
             * @internal
             *
             * @return object
             */
            $file            = apply_filters('wpfdAddonDownloadGoogleTeamDriveFile', $id);
            $fileObj         = apply_filters('wpfdAddonGetGoogleTeamDriveFile', $id, $category->term_id, $token);
            $fileState       = (isset($file->state) && intval($file->state) === 0) ? false : true;
            $fileDownloadUrl = isset($fileObj['linkdownload']) ? $fileObj['linkdownload'] : '#';
            $fileUploadDate  = isset($fileObj['created']) ? $fileObj['created'] : '';

            // Do not download unpublished file.
            if (!$fileState) {
                exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
            }

            if ((int) $preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int) $config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }

            /**
             * Action fire right before a file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             */
            do_action('wpfd_file_download', $id, array('source' => 'google_team_drive'));

            $googleTeamDriveCategory = new wpfdAddonGoogleTeamDrive();
            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);
            // Serve download for google document
            if (strpos($file->mimeType, 'vnd.google-apps') !== false) { // Is google team drive file
                // GuzzleHttp\Psr7\Response
                $fileData = $googleTeamDriveCategory->downloadGoogleDocument($file->id, $file->exportMineType);
                if ($fileData instanceof \GuzzleHttp\Psr7\Response) {
                    $contentLength = $fileData->getHeaderLine('Content-Length');
                    $contentType = $fileData->getHeaderLine('Content-Type');
                    if ($fileData->getStatusCode() === 200) {
                        $this->downloadHeader(
                            $file->title . '.' . $file->ext,
                            (int) $contentLength,
                            $contentType,
                            $config,
                            $file,
                            $preview
                        );
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                        echo $fileData->getBody();
                    }
                }
            } else {
                $googleTeamDriveCategory->downloadLargeFile($file, $contenType, false, intval($preview));
            }

            $this->sendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
        } elseif ($categoryFrom === 'dropbox') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);
            /**
             * Filters to get dropbox file info
             *
             * @param string File id
             *
             * @internal
             *
             * @return object
             */
            list($file, $fMeta) = apply_filters('wpfdAddonDownloadDropboxFile', $id);
            $dropboxFileObj = apply_filters('wpfdAddonGetDropboxFile', $id, $category->term_id, $token);
            $fileDownloadUrl = isset($dropboxFileObj['linkdownload']) ? $dropboxFileObj['linkdownload'] : '#';
            $fileUploadDate = isset($dropboxFileObj['created']) ? $dropboxFileObj['created'] : '';

            $ext = strtolower(pathinfo($fMeta['path_display'], PATHINFO_EXTENSION));
            setlocale(LC_ALL, 'en_US.UTF-8');
            $title = pathinfo($fMeta['path_display'], PATHINFO_FILENAME);

            if ((int) $preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($ext));
            } else {
                if (strtolower($ext) === 'pdf' && (int) $config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }

            //incr hits
            $fileInfos = WpfdAddonHelper::getDropboxFileInfos();

            if (!empty($fileInfos)) {
                $fileState  = (isset($fileInfos[$catid][$id])
                    && isset($fileInfos[$catid][$id]['state']) && intval($fileInfos[$catid][$id]['state']) === 0) ? false : true;
                // Do not download unpublished file.
                if (!$fileState) {
                    exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
                }

                if (isset($fileInfos[$catid][$id]) && isset($fileInfos[$catid][$id]['hits'])) {
                    $hits                           = $fileInfos[$catid][$id]['hits'] + 1;
                    $fileInfos[$catid][$id]['hits'] = $hits;
                } else {
                    $fileInfos[$catid][$id] = array('hits' => 1);
                }
            } else {
                $fileInfos = array();
                $fileInfos[$catid][$id]['hits'] = 1;
            }
            WpfdAddonHelper::setDropboxFileInfos($fileInfos);

            $fileObj        = new stdClass();
            $fileObj->ext   = $ext;
            $fileObj->title = $title;
            $fileObj->title = str_replace('&amp;', '&', $fileObj->title);
            $fileObj->title = str_replace('&#039;', '\'', $fileObj->title);
            $this->sendEmail('', $category->params['category_own'], $configNotify, $category->name, $fileObj->title, $category->term_id, $fileDownloadUrl, $ext, $fileUploadDate);

            /**
             * Action fire right before a Dropbox file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'dropbox'));

            if ($wmCategoryEnabled && file_exists($watermarkedPath)) {
                $fileSize = filesize($watermarkedPath);
                $this->downloadHeader($fileObj->title . '.' . $ext, (int) $fileSize, $contenType, $config, $fileObj, $preview);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                echo file_get_contents($watermarkedPath);
            } else {
                $this->downloadHeader(
                    $fileObj->title . '.' . $ext,
                    (int) filesize($file),
                    $contenType,
                    $config,
                    $fileObj,
                    $preview
                );
                readfile($file);
                unlink($file);
            }
        } elseif ($categoryFrom === 'onedrive') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);
            /**
             * Filters to get onedrive file info
             *
             * @param string File id
             *
             * @internal
             *
             * @return object
             */
            $file = apply_filters('wpfdAddonDownloadOneDriveFile', $id);
            $oneDriveFileObj = apply_filters('wpfdAddonGetOneDriveFile', $id, $category->term_id, $token);
            $fileDownloadUrl = isset($oneDriveFileObj['linkdownload']) ? $oneDriveFileObj['linkdownload'] : '#';
            $fileUploadDate  = isset($oneDriveFileObj['created']) ? $oneDriveFileObj['created'] : '';
            $oneDriveConfig  = WpfdAddonHelper::getOneDriveFileInfos();

            // Do not download unpublished file.
            if (!empty($oneDriveConfig) && isset($oneDriveConfig[$category->term_id])
                && isset($oneDriveConfig[$category->term_id][$id])) {
                $fileState = (isset($oneDriveConfig[$category->term_id][$id]['state'])
                    && intval($oneDriveConfig[$category->term_id][$id]['state']) === 0) ? false : true;

                if (!$fileState) {
                    exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
                }
            }

            if ((int) $preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int) $config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }
            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);
            $filedownload = $file->title . '.' . $file->ext;

            /**
             * Action fire right before a Onedrive file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'onedrive', 'catid' => $catid));

            $this->sendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
            if (defined('WPFD_ONEDRIVE_DIRECT') && WPFD_ONEDRIVE_DIRECT) {
                header('Location: ' . $file->datas);
            } else {
                if ($wmCategoryEnabled && file_exists($watermarkedPath)) {
                    $fileSize = filesize($watermarkedPath);
                    $this->downloadHeader($filedownload, (int) $fileSize, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo file_get_contents($watermarkedPath);
                } else {
                    $this->downloadHeader($filedownload, (int) $file->size, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo $file->datas;
                }
            }
        } elseif ($categoryFrom === 'onedrive_business') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);
            /**
             * Filters to get onedrive file info
             *
             * @param string File id
             *
             * @internal
             *
             * @return object
             */
            $file = apply_filters('wpfdAddonDownloadOneDriveBusinessFile', $id);
            $oneDriveBusinessFileObj = apply_filters('wpfdAddonGetOneDriveBusinessFile', $id, $category->term_id, $token);
            $fileDownloadUrl = isset($oneDriveBusinessFileObj['linkdownload']) ? $oneDriveBusinessFileObj['linkdownload'] : '#';
            $fileUploadDate  = isset($oneDriveBusinessFileObj['created']) ? $oneDriveBusinessFileObj['created'] : '';
            $oneDriveBusinessConfig = WpfdAddonHelper::getOneDriveBusinessFileInfos();

            // Do not download unpublished file.
            if (!empty($oneDriveBusinessConfig) && isset($oneDriveBusinessConfig[$category->term_id])
                && isset($oneDriveBusinessConfig[$category->term_id][$id])) {
                $fileState = (isset($oneDriveBusinessConfig[$category->term_id][$id]['state'])
                    && intval($oneDriveBusinessConfig[$category->term_id][$id]['state']) === 0) ? false : true;

                if (!$fileState) {
                    exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
                }
            }

            if ((int) $preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int) $config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }
            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);
            $filedownload = $file->title . '.' . $file->ext;

            /**
             * Action fire right before a Onedrive file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'onedrive_business', 'catid' => $catid));

            $this->sendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
            if (defined('WPFD_ONEDRIVE_BUSINESS_DIRECT') && WPFD_ONEDRIVE_BUSINESS_DIRECT) {
                header('Location: ' . $file->datas);

                // Set Access-Control-Allow-Origin to allow requests from any origin or a specific origin
                header('Access-Control-Allow-Origin: *');

                // Allow certain HTTP methods
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

                // Allow certain headers that may be sent by the client
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

                // Handle preflight (OPTIONS request)
                if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                    // Return a 200 OK response for the preflight request
                    header('HTTP/1.1 200 OK');
                }
            } else {
                if ($wmCategoryEnabled && file_exists($watermarkedPath)) {
                    $fileSize = filesize($watermarkedPath);
                    $this->downloadHeader($filedownload, (int) $fileSize, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo file_get_contents($watermarkedPath);
                } else {
                    $this->downloadHeader($filedownload, (int) $file->size, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo $file->datas;
                }
            }
        } elseif ($categoryFrom === 'aws') {
            /**
             * Action fire right before a AWS file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'aws', 'catid' => $catid));

            $downloadFile = apply_filters('wpfdAddonDownloadAwsFile', $id, $catid);
            if ($downloadFile) {
                $awsFileObj      = apply_filters('wpfdAddonGetAwsFile', $id, $catid, $token);
                $fileDownloadUrl = isset($awsFileObj['linkdownload']) ? $awsFileObj['linkdownload'] : '#';
                $fileUploadDate  = isset($awsFileObj['created']) ? $awsFileObj['created'] : '';
                $fileTitle       = isset($awsFileObj['title']) ? $awsFileObj['title'] : '';
                $fileType        = isset($awsFileObj['ext']) ? $awsFileObj['ext'] : '';

                // Send email notifications
                $this->sendEmail('', $category->params['category_own'], $configNotify, $category->name, $fileTitle, $category->term_id, $fileDownloadUrl, $fileType, $fileUploadDate);
            }
        } elseif ($categoryFrom === 'nextcloud') {
            /**
             * Action fire right before a Nextcloud file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'nextcloud', 'catid' => $catid));

            $downloadFile = apply_filters('wpfdAddonDownloadNextcloudFile', $id, $catid);
            if ($downloadFile) {
                $nextcloudFileObj   = apply_filters('wpfdAddonGetNextcloudFile', $id, $catid, $token);
                $fileDownloadUrl    = isset($nextcloudFileObj['linkdownload']) ? $nextcloudFileObj['linkdownload'] : '#';
                $fileUploadDate     = isset($nextcloudFileObj['created']) ? $nextcloudFileObj['created'] : '';
                $fileTitle          = isset($nextcloudFileObj['title']) ? $nextcloudFileObj['title'] : '';
                $fileType           = isset($nextcloudFileObj['ext']) ? $nextcloudFileObj['ext'] : '';

                // Send email notifications
                $this->sendEmail('', $category->params['category_own'], $configNotify, $category->name, $fileTitle, $category->term_id, $fileDownloadUrl, $fileType, $fileUploadDate);
            }
        } else {
            $file            = $model->getFullFile($id);
            $fileObj         = $model->getFile($id);
            $fileState       = (isset($file->state) && intval($file->state) === 0) ? false : true;
            $fileDownloadUrl = isset($fileObj->linkdownload) ? $fileObj->linkdownload : '#';
            $fileUploadDate  = isset($fileObj->created) ? $fileObj->created : '';
            $file->file      = isset($file->file) ? $file->file : '';
            $file->ext       = isset($file->ext) ? $file->ext : '';

            // Do not download unpublished file.
            if (!$fileState) {
                exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
            }

            $file_meta = get_post_meta($id, '_wpfd_file_metadata', true);
            $file_sync_meta = get_post_meta($id, 'wpfd_sync_file_hash', true);

            /**
             * Action fire before statistic count and a file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   File meta data
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_download_file', $file, $file_meta);
            $remote_url = isset($file_meta['remote_url']) ? $file_meta['remote_url'] : false;
            $model->hit($id);
            //$model->addCountChart($id);

            // New statistics insert
            $statisticsType = ((int) $preview === 1) ? 'preview' : 'default';
            WpfdHelperFile::addStatisticsRow($id, $statisticsType);

            //todo : verifier les droits d'acces à la catéorgie du fichier
            if (!empty($file) && $file->ID) {
                $file->title = str_replace('&amp;', '&', $file->title);
                $file->title = str_replace('&#039;', '\'', $file->title);
                $wpfd_disable_santize_file_name = apply_filters('wpfd_disable_santize_file_name', false);
                if ($wpfd_disable_santize_file_name) {
                    $filename = $file->title;
                } else {
                    $filename = WpfdHelperFile::santizeFileName($file->title);
                }
                if ($filename === '') {
                    $filename = 'download';
                }
                if (!empty($file_sync_meta)) {
                    $remote_url = false;
                }
                if ($remote_url) {
                    $url = $file_meta['file'];
                    header('Location: ' . $url);
                } else {
                    $preview = Utilities::getInput('preview', 'GET', 'none');
                }

                $sysfile = WpfdBase::getFilesPath($file->catid) . $file->file;
                if (!empty($file_sync_meta) && !file_exists($sysfile)) {
                    $sysfile = $file->file;
                }
                if (file_exists($sysfile)) {
                    $filedownload = $filename . '.' . $file->ext;
                    /**
                     * Action fire right before a file download.
                     * Do not echo anything here or file download will corrupt
                     *
                     * @param object  File id
                     * @param array   Source
                     *
                     * @ignore Hook already documented
                     */
                    do_action('wpfd_file_download', $id, array('source' => 'local'));

                    $openPdfBrowser = ((int) $config['open_pdf_in'] === 1) ? true : false;
                    $previewFile = ((int) $preview === 1) ? true : false;

                    if ($openPdfBrowser === true && $previewFile === true && strtolower($file->ext) === 'pdf') {
                        $previewFileDir = WpfdBase::getPreviewFilesPath();
                        $previewFilePath = $previewFileDir . $filename . '(' . md5($id) . ').' . $file->ext;

                        if (!is_writable($previewFileDir)) {
                            chmod($previewFileDir, 0755);
                        }

                        // Detect if the user is on a mobile device
                        if (isset($_SERVER) && is_array($_SERVER) && isset($_SERVER['HTTP_USER_AGENT'])) {
                            $isMobile = preg_match('/(android|iphone|ipad|mobile|windows phone)/i', $_SERVER['HTTP_USER_AGENT']);
                        } else {
                            $isMobile = false;
                        }

                        if ($isMobile) {
                            $result = WpfdHelperFile::sendDownload(
                                $sysfile,
                                $filedownload,
                                $file->ext,
                                ((int) $preview === 1) ? true : false,
                                true
                            );
                        } else {
                            file_put_contents($previewFilePath, file_get_contents($sysfile));

                            if (!is_writable($previewFilePath)) {
                                chmod($previewFilePath, 0755);
                            }

                            $wpUploadDir = wp_upload_dir();
                            $previewFileUrl = $wpUploadDir['baseurl'] . '/wpfd/preview_files/' .  $filename . '(' . md5($id) . ').' . $file->ext;
                            $result = $this->previewPdfFileInBrowser($previewFileUrl, $filename);
                        }
                    } else {
                        $result = WpfdHelperFile::sendDownload(
                            $sysfile,
                            $filedownload,
                            $file->ext,
                            ((int) $preview === 1) ? true : false,
                            false
                        );
                    }

                    if ($result) {
                        $this->sendEmail(
                            $file->author,
                            $category->params['category_own'],
                            $configNotify,
                            $category->name,
                            $file->title,
                            $category->term_id,
                            $fileDownloadUrl,
                            $file->ext,
                            $fileUploadDate
                        );
                    }
                } else {
                    exit(esc_html__('File not found', 'wpfd'));
                }
            }
        }
        exit();
    }

    /**
     * Download header file
     *
     * @param string  $filename   File name
     * @param integer $size       Size
     * @param string  $contenType Content type
     * @param array   $config     Config
     * @param object  $ob         File object
     * @param integer $preview    Preview
     *
     * @return void
     */
    public function downloadHeader($filename, $size, $contenType, $config, $ob, $preview)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        if ((int) $config['open_pdf_in'] === 1 && strtolower($ob->ext) === 'pdf' && (int) $preview === 1) {
            header('Content-Disposition: inline; filename="' . $filename . '"; filename*=UTF-8\'\'' . $filename);
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        }

        $contentTypeSetted = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            if (strlen(strstr($agent, 'Firefox')) > 0 && $contenType === 'application/pdf' && !$preview) {
                header('Content-Type: application/force-download; charset=utf-8');
                $contentTypeSetted = true;
            }
        }
        if (!$contentTypeSetted) {
            header('Content-Type: ' . esc_attr($contenType));
        }
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        if ($size !== 0) {
            header('Content-Length: ' . $size);
        }
        ob_clean();
        flush();
    }

    /**
     * Method send email notification
     *
     * @param integer      $user_id         User id
     * @param integer      $cat_userid      Category owner user id
     * @param array        $configNotifi    Config
     * @param string       $cat_name        Category name
     * @param string       $file_title      File title
     * @param string|mixed $term_id         Term id
     * @param string|mixed $fileDownloadUrl File download url
     * @param string|mixed $fileExt         File type
     * @param string|mixed $fileUploadDate  File update date
     *
     * @return void
     */
    public function sendEmail($user_id, $cat_userid, $configNotifi, $cat_name, $file_title, $term_id = 0, $fileDownloadUrl = '#', $fileExt = '', $fileUploadDate = '')
    {
        $send_mail_active = array();
        $cat_user_id[]    = $cat_userid;
        $list_superAdmin  = WpfdHelperFiles::getListIDSuperAdmin();
        $emailPerCategoryListing = get_option('wpfd_email_per_category_listing', array());
        if (is_null($emailPerCategoryListing) || !$emailPerCategoryListing) {
            $emailPerCategoryListing = array();
        }

        if ((int) $configNotifi['notify_file_owner'] === 1 && $user_id !== null) {
            $user = get_userdata($user_id)->data;
            array_push($send_mail_active, $user->user_email);
            WpfdHelperFiles::sendMail('download', $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
        }
        if ((int) $configNotifi['notify_category_owner'] === 1) {
            foreach ($cat_user_id as $item) {
                $user = get_userdata($item)->data;
                if (!in_array($user->user_email, $send_mail_active)) {
                    array_push($send_mail_active, $user->user_email);
                    WpfdHelperFiles::sendMail('download', $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                }
            }
        }
        if ($configNotifi['notify_download_event_email'] !== '') {
            if (strpos($configNotifi['notify_download_event_email'], ',')) {
                $emails = explode(',', $configNotifi['notify_download_event_email']);
            } else {
                $emails = array($configNotifi['notify_download_event_email']);
            }

            foreach ($emails as $item) {
                $obj_user               = new stdClass;
                $obj_user->display_name = '';
                $obj_user->user_email   = $item;
                if (!in_array($item, $send_mail_active)) {
                    array_push($send_mail_active, $item);
                    WpfdHelperFiles::sendMail('download', $obj_user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                }
            }
        }
        if ((int) $configNotifi['notify_super_admin'] === 1) {
            foreach ($list_superAdmin as $items) {
                $user = get_userdata($items)->data;
                if (!in_array($user->user_email, $send_mail_active)) {
                    array_push($send_mail_active, $user->user_email);
                    WpfdHelperFiles::sendMail('download', $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                }
            }
        }
        if (isset($configNotifi['notify_per_category']) && intval($configNotifi['notify_per_category']) === 1) {
            if (!empty($emailPerCategoryListing) && is_array($emailPerCategoryListing) && array_key_exists($term_id, $emailPerCategoryListing)) {
                $emailListing = isset($emailPerCategoryListing[$term_id]['emails']) ? (array) $emailPerCategoryListing[$term_id]['emails'] : array();
            } else {
                $emailListing = array();
            }

            if (!empty($emailListing)) {
                foreach ($emailListing as $email) {
                    $email                  = trim($email);
                    $obj_user               = new stdClass;
                    $obj_user->display_name = '';
                    $obj_user->user_email   = $email;
                    if (!in_array($email, $send_mail_active)) {
                        array_push($send_mail_active, $email);
                        WpfdHelperFiles::sendMail('download', $obj_user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                    }
                }
            }
        }
    }

    /**
     * AJAX: Preview file
     *
     * @return void
     */
    public function preview()
    {
        $catid = Utilities::getInput('wpfd_category_id', 'GET', 'none');
        $id = Utilities::getInput('wpfd_file_id', 'GET', 'none');
        if (empty($id) || empty($catid)) {
            die(esc_html__('Hard try huh?', 'wpfd'));
        }
        Application::getInstance('Wpfd');
        $modelConfig = Model::getInstance('configfront');
        $config = $modelConfig->getGlobalConfig();
        $useGeneratedPreview = isset($config['auto_generate_preview']) && intval($config['auto_generate_preview']) === 1 ? true : false;
        $restrictFile = isset($config['restrictfile']) && intval($config['restrictfile']) === 1 ? true : false;

        if (is_numeric($id)) {
            $previewFilePath = get_post_meta($id, '_wpfd_preview_file_path', true);
        } else {
            $previewFileInfo = get_option('_wpfdAddon_preview_info_' . md5($id), false);
            $previewFilePath = is_array($previewFileInfo) && isset($previewFileInfo['path']) ? $previewFileInfo['path'] : false;
        }

        $allowPreview = false;
        $allowSingleUser = true;
        if ($useGeneratedPreview && $previewFilePath) {
            $previewFilePath = WP_CONTENT_DIR . $previewFilePath;
            if (file_exists($previewFilePath)) {
                // Secure preview, use same as file permission for the preview file
                $categoryModel = Model::getInstance('categoryfront');
                $category = $categoryModel->getCategory($catid);

                if (!$category || empty($category) || is_wp_error($category)) {
                    die(esc_html__('Category not validate!', 'wpfd'));
                }

                $user = wp_get_current_user();

                if ($category->access === 1) { // Private category
                    $roles = array();
                    foreach ($user->roles as $role) {
                        $roles[] = strtolower($role);
                    }
                    $allows = array_intersect($roles, $category->roles);
                    if (!empty($allows)) {
                        // User allowed
                        $allowPreview = true;
                    }
                } else {
                    // Public category
                    $allowPreview = true;
                }

                if ($restrictFile) {
                    $metadata = get_post_meta($id, '_wpfd_file_metadata', true);
                    $canview = isset($metadata['canview']) ? $metadata['canview'] : 0;
                    if ($canview) {
                        $canview = array_map('intval', explode(',', $canview));
                        if ($user->ID) {
                            if (!in_array($user->ID, $canview)) {
                                $allowSingleUser = false;
                            }
                        }
                    }
                }

                if ($allowPreview && $allowSingleUser) {
                    // Print preview file content
                    $fileInfo = pathinfo($previewFilePath);
                    $contentType = WpfdHelperFile::mimeType($fileInfo['extension']);
                    header('Content-Disposition: inline; filename="' . esc_html($previewFilePath) . '"');
                    header('Content-Type: ' . esc_attr($contentType));
                    header('Content-Description: File Transfer');
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($previewFilePath));
                    readfile($previewFilePath);
                    die;
                }
            }
        }
        die(esc_html__('You don\'t have permission to view this preview file', 'wpfd'));
    }

    /**
     * AJAX: Preview download
     *
     * Webhook to receive information from preview generator API
     *
     * @return void
     */
    public function previewdownload()
    {
        Application::getInstance('Wpfd');
        $generatePreviewModel = $this->getModel('generatepreview');
        $generatePreviewModel->previewDownload();
    }

    /**
     * Process password
     *
     * @return void
     */
    public function processPasswordProtection()
    {
        $passwordId    = Utilities::getInput('wpfd_password_id', 'POST', 'none');
        $passwordType  = Utilities::getInput('wpfd_password_type', 'POST', 'none');
        $password      = Utilities::getInput('wpfd_password_value', 'POST', 'none');
        $categoryId    = Utilities::getInput('wpfd_password_category_id', 'POST', 'none');
        $modelCategory = Model::getInstance('categoryfront');
        $modelTokens   = Model::getInstance('tokens');
        $userId        = get_current_user_id();
        $token         = $modelTokens->getOrCreateNew();

        // Check password
        if (empty($password) || $password === '') {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        if ($passwordType === 'category') {
            $category = $modelCategory->getCategory($passwordId);
            $params   = $category->params;
            if ((string) $params['category_password'] !== (string) $password) {
                $missReferer = wp_get_referer();
                if ($missReferer) {
                    $security = ( 'https' === parse_url($missReferer, PHP_URL_SCHEME) );
                } else {
                    $security = false;
                }
                setcookie('wp-wpfd-category-wrong-password', $passwordId, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $security);
                wp_safe_redirect(wp_get_referer());
                exit;
            }
        } elseif ($passwordType === 'file') {
            if (is_numeric($passwordId)) {
                $row = get_post($passwordId, ARRAY_A);
                $params['file_password'] = $row['post_password'];
                if ((string) $params['file_password'] !== (string) $password) {
                    $missReferer = wp_get_referer();
                    if ($missReferer) {
                        $security = ( 'https' === parse_url($missReferer, PHP_URL_SCHEME) );
                    } else {
                        $security = false;
                    }
                    setcookie('wp-wpfd-file-wrong-password', $passwordId, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $security);
                    wp_safe_redirect(wp_get_referer());
                    exit;
                }
            } else {
                $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $categoryId);
                if ($categoryFrom === 'googleDrive') {
                    $params = apply_filters('wpfdAddonGetGoogleDriveFile', $passwordId, $categoryId, $token);
                    if ((string) $params['file_password'] !== (string) $password) {
                        $missReferer = wp_get_referer();
                        if ($missReferer) {
                            $security = ( 'https' === parse_url($missReferer, PHP_URL_SCHEME) );
                        } else {
                            $security = false;
                        }
                        setcookie('wp-wpfd-file-wrong-password', $passwordId, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $security);
                        wp_safe_redirect(wp_get_referer());
                        exit;
                    }
                } elseif ($categoryFrom === 'dropbox') {
                    $params = apply_filters('wpfdAddonGetDropboxFile', $passwordId, $categoryId, $token);
                    if ((string) $params['file_password'] !== (string) $password) {
                        $missReferer = wp_get_referer();
                        if ($missReferer) {
                            $security = ( 'https' === parse_url($missReferer, PHP_URL_SCHEME) );
                        } else {
                            $security = false;
                        }
                        setcookie('wp-wpfd-file-wrong-password', $passwordId, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $security);
                        wp_safe_redirect(wp_get_referer());
                        exit;
                    }
                } elseif ($categoryFrom === 'onedrive') {
                    $params = apply_filters('wpfdAddonGetOneDriveFile', $passwordId, $categoryId, $token);
                    if ((string) $params['file_password'] !== (string) $password) {
                        $missReferer = wp_get_referer();
                        if ($missReferer) {
                            $security = ( 'https' === parse_url($missReferer, PHP_URL_SCHEME) );
                        } else {
                            $security = false;
                        }
                        setcookie('wp-wpfd-file-wrong-password', $passwordId, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $security);
                        wp_safe_redirect(wp_get_referer());
                        exit;
                    }
                } elseif ($categoryFrom === 'onedrive_business') {
                    $params = apply_filters('wpfdAddonGetOneDriveBusinessFile', $passwordId, $categoryId, $token);
                    if ((string) $params['file_password'] !== (string) $password) {
                        $missReferer = wp_get_referer();
                        if ($missReferer) {
                            $security = ( 'https' === parse_url($missReferer, PHP_URL_SCHEME) );
                        } else {
                            $security = false;
                        }
                        setcookie('wp-wpfd-file-wrong-password', $passwordId, time() + 10 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $security);
                        wp_safe_redirect(wp_get_referer());
                        exit;
                    }
                }
            }
        } else {
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        require_once ABSPATH . WPINC . '/class-phpass.php';
        $hasher = new PasswordHash(8, true);

        /**
         * Filters the life span of the post password cookie.
         *
         * By default, the cookie expires 10 days from creation. To turn this
         * into a session cookie, return 0.
         *
         * @param int $expires The expiry time, as passed to setcookie().
         */
        $expire  = apply_filters('wpfd_password_expires', time() + 10 * DAY_IN_SECONDS);
        $referer = wp_get_referer();

        if ($referer) {
            $secure = ( 'https' === parse_url($referer, PHP_URL_SCHEME) );
        } else {
            $secure = false;
        }

        if ($passwordType === 'category') {
            setcookie('wp-wpfd-password-category-' . $passwordId . '_' . COOKIEHASH, $hasher->HashPassword(wp_unslash($password)), $expire, COOKIEPATH, COOKIE_DOMAIN, $secure);
            setcookie('wp-wpfd-user-login-category-'. $passwordId, $userId, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure);
        } elseif ($passwordType === 'file') {
            setcookie('wp-wpfd-password-file-' . $passwordId . '_' . COOKIEHASH, $hasher->HashPassword(wp_unslash($password)), $expire, COOKIEPATH, COOKIE_DOMAIN, $secure);
            setcookie('wp-wpfd-user-login-file-'. $passwordId, $userId, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure);
        }

        // Clear all missing password
        setcookie('wp-wpfd-file-wrong-password', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie('wp-wpfd-category-wrong-password', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);

        wp_safe_redirect(wp_get_referer());
        exit;
    }

    /**
     * Method preview PDFs on browser with correct it's metadata
     *
     * @param string $src       File source
     * @param string $fileTitle File title
     *
     * @return mixed|void
     */
    public function previewPdfFileInBrowser($src = '#', $fileTitle = 'WPFD Preview File')
    {
        $style = 'position: fixed; top: 0; right: 0; bottom: 0; left: 0; height: 100vh; overflow: scroll;';
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_attr($fileTitle); ?></title>
        </head>
        <body style="margin: 0; padding: 0;">
        <iframe src="<?php echo $src; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It work exactly! ?>" width="100%" frameborder="0" allowfullscreen style="<?php echo esc_attr($style); ?>" type="application/pdf">
            <?php esc_html__('This browser does not support PDFs. Please download the PDF to view it.', 'wpfd'); ?>
        </iframe>
        </body>
        </html>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It work exactly!

        return true;
    }
}
