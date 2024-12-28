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
use Joomunited\WPFramework\v1_0_6\Form;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class WpfdControllerFile
 */
class WpfdControllerFile extends Controller
{
    /**
     * Download file
     *
     * @return void
     */
    public function download()
    {
        Application::getInstance('Wpfd');
        $model = $this->getModel();
        $id = Utilities::getInt('id');
        $version = Utilities::getInput('version', 'GET', 'string');
        $catid = Utilities::getInt('catid');
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catid);

        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            $id_file = Utilities::getInput('id', 'GET', 'string');
            $vid = Utilities::getInput('vid', 'GET', 'string');
            if ($version) {
                /**
                 * Filters to download version
                 *
                 * @param string File id
                 * @param string Version id
                 * @param string Category from
                 *
                 * @ignore
                 *
                 * @internal
                 *
                 * @return void
                 */
                if ($categoryFrom === 'aws') {
                    $version = apply_filters('wpfdAddonAwsDownloadVersion', $id_file, $vid, $catid);
                    exit();
                } else {
                    $version = apply_filters('wpfd_addon_download_version', $id_file, $vid, $categoryFrom);
                }
                if ($version) {
                    // todo: apply download large file
                    header('Content-Description: File Transfer');
                    header('Content-Type: ' . $version['filetype']);
                    header('Content-Disposition: attachment; filename="' . $version['filename'] . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . $version['filesize']);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Print file content as is
                    echo $version['content'];
                }
            }
            exit();
        } else {
            $file = $model->getFile($id);
            $remote_url = false;
            $url = '';
            if (!$version) {
                $file = $model->getFile($id);
                $file_meta = get_post_meta($id, '_wpfd_file_metadata', true);
                $remote_url = isset($file_meta['remote_url']) ? $file_meta['remote_url'] : false;
                $url = $file_meta['file'];
            } else {
                $vid = Utilities::getInt('vid');
                $version = $model->getVersion($vid);
                if ($version) {
                    $file = array_merge($file, $version);
                    if ($version['remote_url']) {
                        $remote_url = true;
                        $url = $version['file'];
                    }
                }
            }

            //todo : verifier les droits d'acces à la catéorgie du fichier
            if (!WpfdHelperFile::checkAccess($file)) {
                exit();
            }
            if (!empty($file) && $file['ID']) {
                $filename = WpfdHelperFile::santizeFileName($file['title']);
                if ($filename === '') {
                    $filename = 'download';
                }
                if ($remote_url) {
                    header('Location: ' . $url);
                } else {
                    $sysfile = WpfdBase::getFilesPath($file['catid']) . '/' . $file['file'];
                    WpfdHelperFile::sendDownload(
                        $sysfile,
                        basename($filename . '.' . $file['ext']),
                        $file->ext
                    );
                }
            }
            exit();
        }
    }

    /**
     * Restore file
     *
     * @return void
     */
    public function restore()
    {
        $id_file = Utilities::getInt('id');
        $vid = Utilities::getInt('vid');
        $catid = Utilities::getInt('catid');
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catid);
        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            $id_file = Utilities::getInput('id', 'GET', 'string');
            $vid = Utilities::getInput('vid', 'GET', 'string');
            /**
             * Filter to restore addon version
             *
             * @param string File id
             * @param string Version id
             *
             * @return string Version
             *
             * @internal
             */
            $version = apply_filters('wpfd_addon_restore_version', $id_file, $vid, $categoryFrom);
            $this->exitStatus($version);
        } else {
            Application::getInstance('Wpfd');
            /* @var WpfdModelFile $model */
            $model = $this->getModel();
            $file = $model->getFile($id_file);
            $version = $model->getVersion($vid);

            $updateData = array(
                'title' => $file['title'],
                'file' => $version['file'],
                'ext' => $version['ext'],
                'size' => $version['size'],
                'version' => $version['version'],
                'remote_url' => $version['remote_url']
            );

            if ($version) {
                $model->updateFile($id_file, $updateData);

                $model->deleteVersion($vid);
                // Reindex new version file
                Application::getInstance('Wpfd');
                /* @var WpfdModelGeneratepreview $generatePreviewModel */
                $generatePreviewModel = $this->getModel('generatepreview');
                $ftsModel = $this->getModel('fts');
                $ftsModel->wpfdPostReindex($id_file);

                $generatePreviewModel->removeFileFromQueue($id_file);
                $generatePreviewModel->addFileToQueue($id_file);
                $this->exitStatus(true, array('version' => $version['version']));
            }
            $this->exitStatus(false);
        }
    }

    /**
     * Delete file version
     *
     * @return void
     */
    public function deleteVersion()
    {

        $idCategory = Utilities::getInt('catid');
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $idCategory);
        if ($categoryFrom === 'googleDrive') {
            $id_file = Utilities::getInput('id_file', 'GET', 'none');
            $vid = Utilities::getInput('vid', 'GET', 'none');
            /**
             * Filter to delete a cloud version
             *
             * @param integer Term id
             * @param string  File id
             * @param string  Version id
             *
             * @internal
             *
             * @return string
             */
            if (apply_filters('wpfdAddonDeleteVersion', $idCategory, $id_file, $vid)) {
                $this->exitStatus(true, array());
            } else {
                $this->exitStatus('error validating');
            }
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $id_file = Utilities::getInput('id_file', 'GET', 'none');
            $vid     = Utilities::getInput('vid', 'GET', 'none');

            /**
             * Filter to delete a cloud team drive version
             *
             * @param integer Term id
             * @param string  File id
             * @param string  Version id
             *
             * @internal
             *
             * @return string
             */
            if (apply_filters('wpfdAddonGoogleTeamDriveDeleteVersion', $idCategory, $id_file, $vid)) {
                $this->exitStatus(true, array());
            } else {
                $this->exitStatus('Error validating');
            }
        } elseif ($categoryFrom === 'aws') {
            $id_file = Utilities::getInput('id_file', 'GET', 'none');
            $vid = Utilities::getInput('vid', 'GET', 'none');
            /**
             * Filter to delete a cloud version
             *
             * @param integer Term id
             * @param string  File id
             * @param string  Version id
             *
             * @internal
             *
             * @return string
             */
            if (apply_filters('wpfdAddonAwsDeleteVersion', $id_file, $vid)) {
                $this->exitStatus(true, array());
            } else {
                $this->exitStatus('error validating');
            }
        } elseif ($categoryFrom === 'nextcloud') {
            $id_file = Utilities::getInput('id_file', 'GET', 'none');
            $vid = Utilities::getInput('vid', 'GET', 'none');
            /**
             * Filter to delete a cloud version
             *
             * @param integer Term id
             * @param string  File id
             * @param string  Version id
             *
             * @internal
             *
             * @return string
             */
            if (apply_filters('wpfdAddonNextcloudDeleteVersion', $id_file, $vid)) {
                $this->exitStatus(true, array());
            } else {
                $this->exitStatus('error validating');
            }
        } else {
            $vid = Utilities::getInt('vid');
            Application::getInstance('Wpfd');
            $model = $this->getModel();
            $id_file = Utilities::getInput('id_file', 'GET', 'none');
            $file = $model->getFile($id_file);
            $version = $model->getVersion($vid);
            $file_dir = WpfdBase::getFilesPath($file['catid']) . '/' . $version['file'];
            $result = (bool)$model->deleteVersion($vid);
            if ($result) {
                if (file_exists($file_dir)) {
                    unlink($file_dir);
                }
            }
            $this->exitStatus($result);
        }
    }

    /**
     * Save file
     *
     * @return void
     */
    public function save()
    {
        Application::getInstance('Wpfd');
        $model = $this->getModel();
        $modelCat = $this->getModel('category');
        $modelNotify = $this->getModel('notification');
        $configNotify = $modelNotify->getNotificationsConfig();
        $modelConfig = $this->getModel('config');
        $config = $modelConfig->getConfig();
        $dateFormat = $config['date_format'];
        $modelTokens = $this->getModel('tokens');
        $token = '';
        $modelFrFile = $this->getModel('filefront');
        // File multi category
        $file_multi_category_input = Utilities::getInput('file_multi_category', 'POST', 'none');
        if (!isset($file_multi_category_input) || empty($file_multi_category_input) || is_null($file_multi_category_input)) {
            $file_multi_category = array();
        } else {
            if (strstr($file_multi_category_input, ',')) {
                $file_multi_category = explode(',', $file_multi_category_input);
            } else {
                $file_multi_category = array($file_multi_category_input);
            }
        }

        $id_file = Utilities::getInt('id');
        $form = new Form();

        if (!$form->load('file')) {
            $this->exitStatus('error');
        }

        $data = $form->sanitize();

        // Correct file remote URL
        if (isset($data['remoteurl'])) {
            $remoteUrl = Utilities::getInput('remoteurl', 'POST', 'none');
            if (!is_null($remoteUrl) && !empty($remoteUrl) && $remoteUrl !== 'none') {
                $data['remoteurl'] = $remoteUrl;
            }
        }

        // Correct file description
        if (isset($data['description'])) {
            $description = Utilities::getInput('description', 'POST', 'none');
            if (!is_null($description) && !empty($description) && $description !== 'none') {
                $description = str_replace('\\', '', $description);
                $data['description'] = $description;
            }
        }

        // Publish date only for local file
        if ($data['publish'] !== '' && $data['publish'] !== '0000-00-00 00:00:00') {
            $data['publish'] = WpfdBase::validateDate($data['publish'], $dateFormat);
        }
        // Expiration date only for local file
        if ($data['expiration'] !== '' && $data['expiration'] !== '0000-00-00 00:00:00') {
            $data['expiration'] = WpfdBase::validateDate($data['expiration'], $dateFormat);
        }
        $idCategory = Utilities::getInt('idCategory');
        $idRefCategory = Utilities::getInt('idRefCategory') ? Utilities::getInt('idRefCategory') : 0;
        $category = $modelCat->getCategory($idCategory);
        $fileDownloadUrl = '#';
        $fileUploadDate = '';
        $fileType = '';
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $idCategory);
        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            $fileId = Utilities::getInput('id', 'GET', 'none');
            $data['id'] = $fileId;
            /**
             * Filters to save addon file info
             *
             * @param array File data
             *
             * @internal
             *
             * @return boolean
             */
            $actionSaveFileResult = apply_filters('wpfd_addon_save_file_info', $data, $categoryFrom, $idCategory);
            if ($actionSaveFileResult) {
                wpfdSetFileMultiCategories($fileId, $file_multi_category, $idCategory, $idRefCategory);

                switch ($categoryFrom) {
                    case 'dropbox':
                        if (has_filter('wpfdAddonGetDropboxFile', 'wpfdAddonGetDropboxFile')) {
                            $dropboxFileObj  = apply_filters('wpfdAddonGetDropboxFile', $fileId, $idCategory, $token);
                            $fileDownloadUrl = isset($dropboxFileObj['linkdownload']) ? $dropboxFileObj['linkdownload'] : '#';
                            $fileUploadDate  = isset($dropboxFileObj['created']) ? $dropboxFileObj['created'] : '';
                            $fileType        = isset($data['ext']) ? $data['ext'] : $dropboxFileObj['ext'];
                        }
                        break;
                    case 'onedrive':
                        if (has_filter('wpfdAddonGetOneDriveFile', 'wpfdAddonGetOneDriveFile')) {
                            $oneDriveFileObj = apply_filters('wpfdAddonGetOneDriveFile', $fileId, $idCategory, $token);
                            $fileDownloadUrl = isset($oneDriveFileObj['linkdownload']) ? $oneDriveFileObj['linkdownload'] : '#';
                            $fileUploadDate  = isset($oneDriveFileObj['created']) ? $oneDriveFileObj['created'] : '';
                            $fileType        = isset($data['ext']) ? $data['ext'] : $oneDriveFileObj['ext'];
                        }
                        break;
                    case 'onedrive_business':
                        if (has_filter('wpfdAddonGetOneDriveBusinessFile', 'wpfdAddonGetOneDriveBusinessFile')) {
                            $oneDriveBusinessFileObj = apply_filters('wpfdAddonGetOneDriveBusinessFile', $fileId, $idCategory, $token);
                            $fileDownloadUrl         = isset($oneDriveBusinessFileObj['linkdownload']) ? $oneDriveBusinessFileObj['linkdownload'] : '#';
                            $fileUploadDate          = isset($oneDriveBusinessFileObj['created']) ? $oneDriveBusinessFileObj['created'] : '';
                            $fileType                = isset($data['ext']) ? $data['ext'] : $oneDriveBusinessFileObj['ext'];
                        }
                        break;
                    case 'aws':
                        if (has_filter('wpfdAddonGetAwsFile', 'wpfdAddonGetAwsFile')) {
                            $awsFileObj      = apply_filters('wpfdAddonGetAwsFile', $fileId, $idCategory, $token);
                            $fileDownloadUrl = isset($awsFileObj['linkdownload']) ? $awsFileObj['linkdownload'] : '#';
                            $fileUploadDate  = isset($awsFileObj['created']) ? $awsFileObj['created'] : '';
                            $fileType        = isset($data['ext']) ? $data['ext'] : $awsFileObj['ext'];
                        }
                        break;
                    case 'nextcloud':
                        if (has_filter('wpfdAddonGetNextcloudFile', 'wpfdAddonGetNextcloudFile')) {
                            $nextcloudFileObj= apply_filters('wpfdAddonGetNextcloudFile', $fileId, $idCategory, $token);
                            $fileDownloadUrl = isset($nextcloudFileObj['linkdownload']) ? $nextcloudFileObj['linkdownload'] : '#';
                            $fileUploadDate  = isset($nextcloudFileObj['created']) ? $nextcloudFileObj['created'] : '';
                            $fileType        = isset($data['ext']) ? $data['ext'] : $nextcloudFileObj['ext'];
                        }
                        break;
                    case 'googleTeamDrive':
                        if (has_filter('wpfdAddonGetGoogleTeamDriveFile', 'wpfdAddonGetGoogleTeamDriveFile')) {
                            $fileObj         = apply_filters('wpfdAddonGetGoogleTeamDriveFile', $data['id'], $idCategory, $token);
                            $fileDownloadUrl = isset($fileObj['linkdownload']) ? $fileObj['linkdownload'] : '#';
                            $fileUploadDate  = isset($fileObj['created']) ? $fileObj['created'] : '';
                            $fileType        = isset($data['ext']) ? $data['ext'] : $fileObj['ext'];
                        }
                        break;
                    case 'googleDrive':
                    default:
                        if (has_filter('wpfdAddonGetGoogleDriveFile', 'wpfdAddonGetGoogleDriveFile')) {
                            $fileObj         = apply_filters('wpfdAddonGetGoogleDriveFile', $data['id'], $idCategory, $token);
                            $fileDownloadUrl = isset($fileObj['linkdownload']) ? $fileObj['linkdownload'] : '#';
                            $fileUploadDate  = isset($fileObj['created']) ? $fileObj['created'] : '';
                            $fileType        = isset($data['ext']) ? $data['ext'] : $fileObj['ext'];
                        }
                        break;
                }

                $this->sendEmail(
                    'edited',
                    null,
                    $category->params['category_own'],
                    $configNotify,
                    $category->name,
                    $data['title'],
                    $category->term_id,
                    $fileDownloadUrl,
                    $fileType,
                    $fileUploadDate
                );
                $data['catid'] = $idCategory;
                $data['icons'] = $this->getFileIcons($data);
                $data['title'] = stripslashes(htmlspecialchars_decode(wp_slash_strings_only($data['title'])));

                // Index cloud tags
                if (isset($data['file_tags']) && $data['file_tags'] !== '') {
                    $cloudTags = get_option('wpfd_cloud_available_tags', array());

                    if (is_array($cloudTags) && !empty($cloudTags)) {
                        $exists = array_map(function ($cloudTag) {
                            return $cloudTag->value;
                        }, $cloudTags);
                    } else {
                        $cloudTags = array();
                        $exists = array();
                    }

                    $newTags = explode(',', $data['file_tags']);

                    foreach ($newTags as $newTag) {
                        if (in_array($newTag, $exists)) {
                            continue;
                        }

                        $currentCloudTag = new stdClass();
                        $currentCloudTag->id = $idCategory;
                        $currentCloudTag->value = esc_html($newTag);
                        $currentCloudTag->label = esc_attr($newTag);
                        $cloudTags[] = $currentCloudTag;
                        $exists[] = esc_attr($newTag);
                    }

                    update_option('wpfd_cloud_available_tags', $cloudTags);
                }

                $this->exitStatus(true, $data);
            } else {
                $this->exitStatus('error saving');
            }
        } else {
            $data['id'] = $id_file;
            $data['description'] = Utilities::getInput('description', 'POST', 'none');
            $isPending = apply_filters('wpfd_file_upload_pending', (int)$id_file, $idCategory);
            if ($isPending === true && (int)$data['state'] === 1) {
                delete_post_meta($id_file, '_wpfd_file_meta_uploaded_by');
            }
            if (!$model->save($data)) {
                $this->exitStatus('error saving');
            }
            wpfdSetFileMultiCategories($id_file, $file_multi_category, $idCategory, $idRefCategory);
            $file            = $model->getFile($data['id']);
            $fileObj         = $modelFrFile->getFile($data['id']);
            $fileDownloadUrl = isset($fileObj->linkdownload) ? $fileObj->linkdownload : '#';
            $fileUploadDate  = isset($fileObj->created) ? $fileObj->created : '';
            $fileType        = isset($file['ext']) ? $file['ext'] : $fileObj->ext;

            Application::getInstance('Wpfd');
            $ftsModel = $this->getModel('fts');
            $ftsModel->wpfdPostReindex($data['id']);
            $this->sendEmail(
                'edited',
                $file['post_author'],
                $category->params['category_own'],
                $configNotify,
                $category->name,
                $file['post_title'],
                $category->term_id,
                $fileDownloadUrl,
                $fileType,
                $fileUploadDate
            );
            $file['icons'] = $this->getFileIcons($file);
            $this->exitStatus(true, $file);
        }
    }

    /**
     * Generate file icons HTML
     *
     * @param array|object $file File instance
     *
     * @return string
     */
    public function getFileIcons($file)
    {
        // Quick convert file to object
        $file = json_decode(json_encode($file));
        $httpcheck = isset($file->guid) ? $file->guid : '';
        $classes = preg_match('(http://|https://)', $httpcheck) ? ' is-remote-url' : '';
        /**
         * Check if file has linked to a product
         *
         * @param WP_Post
         *
         * @internal
         */
        $classes .= apply_filters('wpfd_addon_has_products', false, $file) ? ' isWoocommerce' : '';

        if (isset($file->ID) || isset($file->id)) {
            $file->ID = isset($file->ID) ? $file->ID : $file->id;
            $isExpired = WpfdHelperFile::wpfdIsExpired($file->ID);
        } else {
            $isExpired = true;
        }

        if ($isExpired === true) {
            $classes .= ' is-expired ';
        } elseif ($isExpired > 0) {
            $classes .= ' is-expiry-set ';
        }

        $classes .= apply_filters('wpfd_file_enable_state', $file) ? ' unpublished' : '';

        $classes .= apply_filters('wpfd_file_upload_pending', $file->ID, $file->catid) ? ' isPending' : '';
        $iconsHtml = '';
        if (strpos($classes, 'isWoocommerce') !== false) {
            $iconsHtml .= sprintf('<i title="%s" class="wpfd-svg-icon-woocommerce"></i>', esc_html__('WooCommerce', 'wpfd'));
        }

        if (strpos($classes, 'unpublished') !== false) {
            $iconsHtml .= sprintf('<i title="%s" class="wpfd-svg-icon-visibility-off"></i>', esc_html__('Unpublished', 'wpfd'));
        }

        if (strpos($classes, 'is-remote-url') !== false) {
            $iconsHtml .= sprintf('<i title="%s" class="wpfd-svg-icon-link"></i>', esc_html__('Remote URL', 'wpfd'));
        }


        if (strpos($classes, 'is-expired') !== false) {
            $iconsHtml .= sprintf('<i title="%s" class="wpfd-svg-icon-expired"></i>', esc_html__('Expired & Unpublished', 'wpfd'));
        }

        if (strpos($classes, 'is-expiry-set') !== false) {
            $iconsHtml .= sprintf('<i title="%s" class="wpfd-svg-icon-expiry-set"></i>', esc_html__('Expired date set', 'wpfd'));
        }

        return $iconsHtml;
    }

    /**
     * Delete file
     *
     * @return void
     */
    public function delete()
    {
        $idCategory = Utilities::getInt('id_category');
        $catIdFileRef = Utilities::getInt('catid_file_ref');
        Application::getInstance('Wpfd');
        $modelCat = $this->getModel('category');
        $category = $modelCat->getCategory($idCategory);
        $modelNotify = $this->getModel('notification');
        $configNotify = $modelNotify->getNotificationsConfig();
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $idCategory);

        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            $id_file = Utilities::getInput('id_file', 'GET', 'string');
            /**
             * Filter to get addon file info
             *
             * @param string  File id
             * @param integer Category term id
             * @param string  Category from
             *
             * @internal
             *
             * @return array
             */
            if ($categoryFrom === 'aws' || $categoryFrom === 'nextcloud') {
                $id_file = Utilities::getInput('id_file', 'GET', 'none');
                $id_file = rawurldecode(stripslashes(htmlspecialchars_decode($id_file)));
            }

            $file = apply_filters('wpfd_addon_get_file_info', $id_file, $idCategory, $categoryFrom);
            if (!empty($file)) {
                if ($catIdFileRef === $idCategory) {
                    $file_multi_category = null;
                    if (isset($file['file_multi_category'])) {
                        $file_multi_category = gettype($file['file_multi_category']) === 'string' ? explode(',', $file['file_multi_category']) : (array) $file['file_multi_category'];
                    }
                    if ($file_multi_category) {
                        foreach ($file_multi_category as $value) {
                            $modelCat->deleteRefToFiles($value, $id_file, $idCategory);
                        }
                    }
                } else {
                    $modelCat->deleteRefToFiles($idCategory, $id_file, $catIdFileRef);
                    if (isset($file['file_multi_category'])) {
                        $file_multi_category = gettype($file['file_multi_category']) === 'string' ? explode(',', $file['file_multi_category']) : (array) $file['file_multi_category'];
                        foreach ($file_multi_category as $key => $val) {
                            if ($idCategory === (int)$val) {
                                unset($file_multi_category[$key]);
                            }
                        }
                        $file['file_multi_category'] = implode(',', $file_multi_category);
                        $file['file_multi_category_old'] = implode(',', $file_multi_category);
                        /**
                         * Filters to save addon file info
                         *
                         * @param array File data
                         *
                         * @internal
                         *
                         * @return boolean
                         */
                        apply_filters('wpfd_addon_save_file_info', $file, $categoryFrom, $idCategory);
                    }
                }
            }

            $fileDownloadUrl = (!empty($file) && isset($file['linkdownload'])) ? $file['linkdownload'] : '#';
            $fileUploadDate  = (!empty($file) && isset($file['created'])) ? $file['created'] : '';
            $fileType        = (!empty($file) && isset($file['ext'])) ? $file['ext'] : '';

            $this->sendEmail(
                'delete',
                null,
                $category->params['category_own'],
                $configNotify,
                $category->name,
                $file['title'],
                $category->term_id,
                $fileDownloadUrl,
                $fileType,
                $fileUploadDate
            );
            /**
             * Filter delete addon files
             *
             * @param integer Category id
             * @param string  File id
             *
             * @internal
             *
             * @return boolean
             */
            if (apply_filters('wpfd_addon_delete_file', $idCategory, $id_file, $categoryFrom)) {
                /**
                 * Action fire after a file deleted
                 *
                 * @param array   Deleted file info
                 * @param WP_Term Category the file was deleted from
                 * @param array   Additional information
                 */
                do_action('wpfd_file_deleted', $file, $category, array('source' => $categoryFrom));
                $this->exitStatus(true);
            } else {
                $this->exitStatus(false);
            }
        } else {
            $id_file = Utilities::getInt('id_file');
            $model = $this->getModel();
            $modelFile = $this->getModel('filefront');
            $versions = $model->getVersions($id_file, $idCategory);
            $file = $model->getFile($id_file);
            $fileObj = $modelFile->getFile($id_file);
            $fileDownloadUrl = (!empty($fileObj) && isset($fileObj->linkdownload)) ? $fileObj->linkdownload : '#';
            $fileUploadDate = (!empty($fileObj) && isset($fileObj->created)) ? $fileObj->created : '';
            $fileType = (!empty($fileObj) && isset($fileObj->ext)) ? $fileObj->ext : '';

            if (!empty($versions)) {
                foreach ($versions as $key => $value) {
                    $version = $model->getVersion($value['meta_id']);
                    $file_dir = WpfdBase::getFilesPath($file['catid']) . '/' . $version['file'];
                    $result = (bool)$model->deleteVersion($value['meta_id']);
                    if ($result) {
                        if (file_exists($file_dir)) {
                            unlink($file_dir);
                        }
                    }
                }
            }
            if (!empty($file)) {
                if ($catIdFileRef === $idCategory) {
                    $file_multi_category = null;
                    if (isset($file['file_multi_category'])) {
                        $file_multi_category = $file['file_multi_category'];
                    }
                    if ($file_multi_category) {
                        foreach ($file_multi_category as $value) {
                            $modelCat->deleteRefToFiles($value, $id_file, $idCategory);
                        }
                    }
                    if ($model->delete($id_file)) {
                        $file_dir = WpfdBase::getFilesPath($file['catid']);
                        if (file_exists($file_dir . $file['file'])) {
                            unlink($file_dir . $file['file']);
                            $this->sendEmail(
                                'delete',
                                $file['post_author'],
                                $category->params['category_own'],
                                $configNotify,
                                $category->name,
                                $file['post_title'],
                                $category->term_id,
                                $fileDownloadUrl,
                                $fileType,
                                $fileUploadDate
                            );
                            // Full Text Search Index When Delete File
                            $ftsModel = Model::getInstance('fts');
                            $ftsModel->removeIndexRecordForPost($id_file);
                            // Delete preview file if exists
                            /* @var WpfdModelGeneratepreview $generatePreviewModel */
                            $generatePreviewModel = Model::getInstance('generatepreview');
                            $generatePreviewModel->removeFileFromQueue($id_file);
                            /**
                             * Action fire after a file deleted
                             *
                             * @param array   Deleted file info
                             * @param WP_Term Category the file was deleted from
                             * @param array   Additional information
                             *
                             * @ignore
                             */
                            do_action('wpfd_file_deleted', $file, $category, array('source' => 'local'));
                            $this->exitStatus(true);
                        }
                    }
                } else {
                    $del = $modelCat->deleteRefToFiles($idCategory, $id_file, $catIdFileRef);
                    $metadata = get_post_meta($file['ID'], '_wpfd_file_metadata', true);
                    if (isset($metadata['file_multi_category'])) {
                        $file_multi_category = $metadata['file_multi_category'];
                        foreach ($file_multi_category as $key => $val) {
                            if ($idCategory === (int)$val) {
                                unset($file_multi_category[$key]);
                            }
                        }
                        $metadata['file_multi_category'] = $file_multi_category;
                        $metadata['file_multi_category_old'] = implode(',', $file_multi_category);
                        update_post_meta($file['ID'], '_wpfd_file_metadata', $metadata);
                        $this->exitStatus(true);
                    }

                    // Del on searching
                    if (!is_null($file) && !empty($file) && isset($file['catid']) && intval($file['catid']) === intval($catIdFileRef)) {
                        if ($model->delete($id_file)) {
                            $file_dir = WpfdBase::getFilesPath($file['catid']);
                            if (file_exists($file_dir . $file['file'])) {
                                unlink($file_dir . $file['file']);
                                $this->sendEmail(
                                    'delete',
                                    $file['post_author'],
                                    $category->params['category_own'],
                                    $configNotify,
                                    $category->name,
                                    $file['post_title'],
                                    $category->term_id,
                                    $fileDownloadUrl,
                                    $fileType,
                                    $fileUploadDate
                                );

                                // Full Text Search Index When Delete File
                                $ftsModel = Model::getInstance('fts');
                                $ftsModel->removeIndexRecordForPost($id_file);

                                // Delete preview file if exists
                                /* @var WpfdModelGeneratepreview $generatePreviewModel */
                                $generatePreviewModel = Model::getInstance('generatepreview');
                                $generatePreviewModel->removeFileFromQueue($id_file);

                                /**
                                 * Action fire after a file deleted
                                 *
                                 * @param array   Deleted file info
                                 * @param WP_Term Category the file was deleted from
                                 * @param array   Additional information
                                 *
                                 * @ignore
                                 */
                                do_action('wpfd_file_deleted', $file, $category, array('source' => 'local'));
                                $this->exitStatus(true);
                            }
                        }
                    }
                }
            }
        }
        $this->exitStatus('error while deleting');
    }

    /**
     * Send email
     *
     * @param string       $action          Action to send email
     * @param integer|null $user_id         Current user id
     * @param string       $cat_userid      Category owner id
     * @param array        $configNotify    Email configurations
     * @param string       $cat_name        Category had action
     * @param string       $file_title      File name in action
     * @param string|mixed $term_id         Term id
     * @param string|mixed $fileDownloadUrl File download url
     * @param string|mixed $fileExt         File type
     * @param string|mixed $fileUploadDate  File upload date
     *
     * @return void
     */
    public function sendEmail($action, $user_id, $cat_userid, $configNotify, $cat_name, $file_title, $term_id = 0, $fileDownloadUrl = '#', $fileExt = '', $fileUploadDate = '')
    {
        $send_mail_active = array();
        $cat_user_id[] = $cat_userid;
        $list_superAdmin = WpfdHelperFiles::getListIDSuperAdmin();
        $emailPerCategoryListing = get_option('wpfd_email_per_category_listing', array());
        if (is_null($emailPerCategoryListing) || !$emailPerCategoryListing) {
            $emailPerCategoryListing = array();
        }
        if ((int)$configNotify['notify_file_owner'] === 1 && $user_id !== null) {
            $user = get_userdata($user_id)->data;
            array_push($send_mail_active, $user->user_email);
            WpfdHelperFiles::sendMail($action, $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
        }
        if ((int)$configNotify['notify_category_owner'] === 1) {
            foreach ($cat_user_id as $item) {
                $user = get_userdata($item)->data;
                if (!in_array($user->user_email, $send_mail_active)) {
                    array_push($send_mail_active, $user->user_email);
                    WpfdHelperFiles::sendMail($action, $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                }
            }
        }
        if ($configNotify['notify_add_event_email'] !== '') {
            $emails = explode(',', $configNotify['notify_add_event_email']);
            foreach ($emails as $item) {
                $obj_user = new stdClass;
                $obj_user->display_name = '';
                $obj_user->user_email = $item;
                if (!in_array($item, $send_mail_active)) {
                    array_push($send_mail_active, $item);
                    WpfdHelperFiles::sendMail($action, $obj_user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                }
            }
        }
        if ((int)$configNotify['notify_super_admin'] === 1) {
            foreach ($list_superAdmin as $items) {
                $user = get_userdata($items)->data;
                if (!in_array($user->user_email, $send_mail_active)) {
                    array_push($send_mail_active, $user->user_email);
                    WpfdHelperFiles::sendMail($action, $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                }
            }
        }
        if (isset($configNotify['notify_per_category']) && intval($configNotify['notify_per_category']) === 1) {
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
                        WpfdHelperFiles::sendMail($action, $obj_user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                    }
                }
            }
        }
    }

    /**
     * Call file shortcode
     *
     * @throws Exception Return if error
     *
     * @return void
     */
    public function callFileShortcode()
    {
        $app = Application::getInstance('Wpfd');
        $id_file = Utilities::getInput('file_id', 'GET', 'none');
        $id_category = Utilities::getInt('category_id');
        $path_wpfdhelper = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'helpers';
        $path_wpfdhelper .= DIRECTORY_SEPARATOR . 'WpfdHelperShortcodes.php';
        require_once $path_wpfdhelper;

        $helperShortcode = new WpfdHelperShortcodes();
        $singleFile = $helperShortcode->callSingleFile($id_file, $id_category);
        wp_send_json(array(
            'success' => true,
            'data'    => $singleFile
        ));
        die();
    }

    /**
     * Preview file block
     *
     * @return void
     */
    public function preview()
    {
        if (Utilities::getInput('wpfd_file_id', 'GET', 'string') !== null && Utilities::getInput('wpfd_cat_id', 'GET', 'string') !== null) {
            $html = '';
            $catModel             = $this->getModel();
            $fileId               = Utilities::getInput('wpfd_file_id', 'GET', 'string');
            $catId                = Utilities::getInput('wpfd_cat_id', 'GET', 'string');
            $app                  = Application::getInstance('Wpfd');
            $path_helper          = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'WpfdHelperShortcodes.php';
            require_once $path_helper;
            $helper               = new WpfdHelperShortcodes();
            $atts                 = array();
            $atts['id']           = (isset($fileId)) ? $fileId : '';
            $atts['catid']        = (isset($catId)) ? $catId : '';
            $singleFileShortcode  = $helper->singleFileShortcode($atts);

            wp_send_json(array('status' => true, 'html' => $singleFileShortcode, 'id' => $fileId));
        }

        wp_send_json(array('status' => false));
    }
}
