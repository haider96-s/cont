<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class WpfdControllerFiles
 */
class WpfdControllerFiles extends Controller
{
    /**
     * Method to download files in categories
     *
     * @param integer $category_id   Category id
     * @param string  $category_name Category name
     *
     * @return void
     */
    public function download($category_id = null, $category_name = null)
    {
        if ($category_id === null && $category_name === null) {
            $category_id   = Utilities::getInt('wpfd_category_id');
        }
        $term = get_term($category_id, 'wpfd-category');

        if (is_wp_error($term)) {
            wp_die(esc_html__('The category id not valid!', 'wpfd'));
        }

        $category_name = $term->name;
        $wpUploadDir = wp_upload_dir('wpfd');
        $upload_dir  = $wpUploadDir['path'];

        if (strpos($upload_dir, 'wpfd') === false) {
            $upload_dir .= '/wpfd/';
            $upload_dir = str_replace('//', DIRECTORY_SEPARATOR, $upload_dir);
        }

        /**
         * Filter for customizing download load zip folder
         *
         * @param boolean
         */
        $customZipFolder = apply_filters('wpfd_custom_zip_folder_on_server', false);

        // Allow custom zip folder contain all download zip files
        if ($customZipFolder && strpos($upload_dir, 'allzip') === false) {
            $upload_dir .= '/allzip/';
            $upload_dir = str_replace('//', DIRECTORY_SEPARATOR, $upload_dir);

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
                $data = '<html><body bgcolor="#FFFFFF"></body></html>';
                $file = fopen($upload_dir . 'index.html', 'w');
                fwrite($file, $data);
                fclose($file);
                $data = 'deny from all';
                $file = fopen($upload_dir . '.htaccess', 'w');
                fwrite($file, $data);
                fclose($file);
            }
        }

        $modelf    = $this->getModel('filefront');
        $modelConfig = $this->getModel('configfront');
        $config = $modelConfig->getGlobalConfig();
        $listFiles = $this->getAllFiles($category_id);
        if (empty($listFiles) && !$listFiles) {
            wp_die(esc_html__('There is no file found in this category!', 'wpfd'));
        }
        /**
         * Filter for files selected to download
         *
         * @param array
         */
        $listFiles = apply_filters('wpfd_selected_files', $listFiles);

        /**
         * Filter for removing zip file from server
         *
         * @param boolean
         */
        $removeZip = apply_filters('wpfd_remove_download_zip_file_from_server', false);

        // Calculate zip file name
        $zipName      = $upload_dir . $category_id . '-';
        $allFilesName = '';
        $protectedFiles = array();
        $limitedFiles = array();
        $permissionFiles = array();
        $isLimitDownload = (isset($config['limit_the_download']) && intval($config['limit_the_download']) === 1
            && isset($config['track_user_download']) && intval($config['track_user_download']) === 1) ? true : false;
        foreach ($listFiles as $file) {
            // Check access
            if (!WpfdHelperFile::checkAccess((array)$file)) {
                $permissionFiles[] = $file;
                continue;
            }

            // Check file password protected
            if (wpfdPasswordRequired($file, 'file')) {
                $protectedFiles[] = $file;
                continue;
            }

            // Check file download limit
            if ($isLimitDownload && WpfdHelperFile::downloadLimitHandle($file->ID, $category_id)) {
                $limitedFiles[] = $file;
                continue;
            }

            $file         = $modelf->getFullFile($file->ID);
            $allFilesName .= $file->title;
            $allFilesName .= $file->size;
            if ($file->remote_url) {
                if (file_exists($file->file)) {
                    $allFilesName = filemtime($file->file);
                } else {
                    $allFilesName .= $file->name . $file->size . $file->ext . $file->version . $file->modified;
                }
            } else {
                $allFilesName .= filemtime(WpfdBase::getFilesPath($file->catid) . '/' . $file->file);
            }
        }

        // Return when all files are protected files
        if (!empty($protectedFiles) && count($protectedFiles) > 0) {
            if (count($protectedFiles) === count($listFiles) || count($protectedFiles) > count($listFiles)) {
                wp_die(esc_html__('There is no file found in this category!', 'wpfd'));
            }
        }

        // Return if all files are limited files
        if (!empty($limitedFiles) && count($limitedFiles) > 0) {
            if (count($limitedFiles) === count($listFiles) || count($limitedFiles) > count($listFiles)) {
                wp_die(esc_html__('There is no file found in this category!', 'wpfd'));
            }
        }

        // Return when all files are permission files
        if (!empty($permissionFiles) && count($permissionFiles) > 0) {
            if (count($permissionFiles) === count($listFiles) || count($permissionFiles) > count($listFiles)) {
                wp_die(esc_html__('There is no file found in this category!', 'wpfd'));
            }
        }

        $zipName .= md5($allFilesName) . '.zip';

        if (!file_exists($zipName)) {
            // Remove all old files with same category id
            $files = glob($upload_dir . $category_id . '-*.zip');
            if (!empty($files) && count($files) > 0) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if ($ext === 'zip') {
                            unlink($file);
                        }
                    }
                }
            }

            // Start zip new file
            $zipFiles = new ZipArchive();
            $zipFiles->open($zipName, ZipArchive::CREATE);
            if (!empty($listFiles) && count($listFiles) > 0) {
                foreach ($listFiles as $key => $filevl) {
                    // Check access
                    if (!WpfdHelperFile::checkAccess((array)$filevl)) {
                        continue;
                    }

                    // Check file password protected
                    if (wpfdPasswordRequired($filevl, 'file')) {
                        continue;
                    }

                    // Check file download limit
                    if ($isLimitDownload && WpfdHelperFile::downloadLimitHandle($filevl->ID, $category_id)) {
                        continue;
                    }

                    $file      = $modelf->getFullFile($filevl->ID);
                    $sysfile = WpfdBase::getFilesPath($filevl->catid) . '/' . $file->file;
                    if (!file_exists($sysfile)) {
                        if (file_exists($file->file)) {
                            $sysfile = $file->file;
                        }
                    }

                    $file->title = str_replace('&amp;', '&', $file->title);
                    $file->title = str_replace('&#039;', '\'', $file->title);
                    $wpfd_disable_santize_file_name = apply_filters('wpfd_disable_santize_file_name', false);
                    if ($wpfd_disable_santize_file_name) {
                        $file_name = $file->title;
                    } else {
                        $file_name = WpfdHelperFile::santizeFileName($file->title);
                    }

                    $count = 0;
                    for ($i = 0; $i < $zipFiles->numFiles; $i++) {
                        if ($zipFiles->getNameIndex($i) === $file_name . '.' . $file->ext) {
                            $count++;
                        }
                    }
                    if ($count > 0) {
                        $file_name = $file_name . '(' . $count . ')';
                    }
                    $zipFiles->addFile($sysfile, $file_name . '.' . $file->ext);
                }
            }
            $zipFiles->close();
        }
        WpfdHelperFile::SendDownload($zipName, $category_name . '.zip', 'zip');

        // Remove zip file from server
        if ($removeZip && file_exists($zipName)) {
            unlink($zipName);
        }

        exit();
    }

    /**
     * Zip file
     *
     * @param null|string  $filesId     Files id
     * @param null|integer $category_id Category id
     *
     * @return void
     */
    public function zipSeletedFiles($filesId = null, $category_id = null)
    {
//        if (!wp_verify_nonce(Utilities::getInput('wpfd_nonce', 'GET', 'string'), 'wpfd_download_selected_files')) {
//            return;
//        }
        if (is_null($category_id)) {
            $category_id   = Utilities::getInt('wpfd_category_id');
        }

        if (is_null($filesId)) {
            $filesId   = Utilities::getInput('filesId', 'GET', 'string');
        }

        if (empty($filesId) || trim($filesId) === '' || empty($category_id) || trim($category_id) === '') {
            wp_send_json_error(array('message' => esc_html__('Missing files id or category id wrong!', 'wpfd')));
            die();
        }
        // Check category for sure it not come from cloud
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $category_id);
        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            wp_send_json_error(array('message' => esc_html__('Sorry, something went wrong! Please contact administrator for more information.', 'wpfd')));
            die();
        }

        // Get files info
        $files = explode(',', $filesId);

        // Clean file id
        $files = array_map(
            function ($f) {
                return intval(trim($f));
            },
            $files
        );

        Application::getInstance('Wpfd');
        $fileModel = $this->getModel('filefront');
        $modelConfig = $this->getModel('configfront');
        $config = $modelConfig->getGlobalConfig();
        $isLimitDownload = (isset($config['limit_the_download']) && intval($config['limit_the_download']) === 1
            && isset($config['track_user_download']) && intval($config['track_user_download']) === 1) ? true : false;

        $filesObj    = array();
        $wpUploadDir = wp_upload_dir('wpfd');
        $upload_dir  = $wpUploadDir['path'];
        $zipName     = $upload_dir . $category_id . '.selected-';
        $allFilesName = '';

        foreach ($files as $fileId) {
            $file = $fileModel->getFullFile($fileId);
            // Check access
            if (!WpfdHelperFile::checkAccess((array)$file)) {
                continue;
            }

            // Check file download limit
            if ($isLimitDownload && WpfdHelperFile::downloadLimitHandle($file->ID, $category_id)) {
                continue;
            }

            /**
             * Filter of file selected to download
             *
             * @param array
             */
            $file = apply_filters('wpfd_selected_file', $file);

            if (!$file) {
                continue;
            }
            // Add file
            $filesObj[] = $file;

            // Calculate zip file name to made a hash
            $allFilesName .= $file->title;
            $allFilesName .= $file->size;
            if ($file->remote_url) {
                if (file_exists($file->file)) {
                    $allFilesName .= filemtime($file->file);
                } else {
                    $allFilesName .= $file->name . $file->size . $file->ext . $file->version . $file->modified;
                }
            } else {
                $allFilesName .= filemtime(WpfdBase::getFilesPath($file->catid) . '/' . $file->file);
            }
        }
        // Create a hash with all files name
        $hash = md5($allFilesName);
        $zipName .= $hash . '.zip';
        if (file_exists($zipName)) {
            wp_send_json_success(array('hash' => $hash));
            die();
        }

        // Zip it
        if (!empty($filesObj) && count($filesObj) > 0) {
            $zipFiles = new ZipArchive();
            $zipFiles->open($zipName, ZipArchive::CREATE);
            foreach ($filesObj as $file) {
                // Check file download limit
                if ($isLimitDownload && WpfdHelperFile::downloadLimitHandle($file->ID, $category_id)) {
                    continue;
                }

                $sysfile   = WpfdBase::getFilesPath($file->catid) . '/' . $file->file;
                if (!file_exists($sysfile)) {
                    if (file_exists($file->file)) {
                        $sysfile = $file->file;
                    }
                }
                $file->title = str_replace('&amp;', '&', $file->title);
                $file->title = str_replace('&#039;', '\'', $file->title);
                $wpfd_disable_santize_file_name = apply_filters('wpfd_disable_santize_file_name', false);
                if ($wpfd_disable_santize_file_name) {
                    $file_name = $file->title;
                } else {
                    $file_name = WpfdHelperFile::santizeFileName($file->title);
                }

                $count = 0;
                for ($i = 0; $i < $zipFiles->numFiles; $i++) {
                    if ($zipFiles->getNameIndex($i) === $file_name . '.' . $file->ext) {
                        $count++;
                    }
                }
                if ($count > 0) {
                    $file_name = $file_name . '(' . $count . ')';
                }
                $zipFiles->addFile($sysfile, $file_name . '.' . $file->ext);
            }
            $zipFiles->close();
        } else {
            wp_send_json_error(array('message' => esc_html__('There is no file to download!', 'wpfd')));
            die();
        }

        // Return hashed information
        wp_send_json_success(array('hash' => $hash));
        die();
    }

    /**
     * Download ziped file
     *
     * @param null|string  $hash          File hash
     * @param null|integer $category_id   Category id
     * @param null|string  $category_name Category name
     *
     * @return void
     */
    public function downloadZipedFile($hash = null, $category_id = null, $category_name = null)
    {
        if (is_null($category_id)) {
            $category_id   = Utilities::getInt('wpfd_category_id');
        }

        if (is_null($category_name)) {
            $category_name   = Utilities::getInput('wpfd_category_name', 'GET', 'string');
        }

        if (empty($category_name) || $category_name === '') {
            $category_name = time() . '-category-' . $category_id;
        }

        if (is_null($hash)) {
            $hash   = Utilities::getInput('hash', 'GET', 'string');
        }
        if (empty($hash) || trim($hash) === '' || empty($category_id)) {
            die(esc_html__('Missing hash or wrong category id!', 'wpfd'));
        }

        // Check hash
        $wpUploadDir = wp_upload_dir('wpfd');
        $upload_dir  = $wpUploadDir['path'];
        $zipName     = $upload_dir . $category_id . '.selected-' . $hash . '.zip';

        if (!file_exists($zipName)) {
            die(esc_html__('The file you request does not exists!', 'wpfd'));
        }
        // Send ziped file if it exists
        WpfdHelperFile::SendDownload($zipName, $category_name . '.zip', 'zip');
        // Remove file after download
        unlink($zipName);
        exit();
    }


    /**
     * Zip file with many categories
     *
     * @return void
     */
    public function zipSeletedFilesMultipleCategories()
    {
        $filesId = Utilities::getInput('filesId', 'POST', 'none');
        $selectedFileWithCategoryIds = Utilities::getInput('selectedFileWithCategoryIds', 'POST', 'none');

        if (empty($filesId) || trim($filesId) === '' || empty($selectedFileWithCategoryIds)) {
            wp_send_json_error(array('message' => esc_html__('Missing files id or category id wrong!', 'wpfd')));
            die();
        }

        // Get files info
        $files = explode(',', $filesId);

        // Clean file id
        $files = array_map(
            function ($f) {
                return intval(trim($f));
            },
            $files
        );

        Application::getInstance('Wpfd');
        $fileModel = $this->getModel('filefront');

        $filesObj    = array();
        $wpUploadDir = wp_upload_dir('wpfd');
        $upload_dir  = $wpUploadDir['path'];
        $zipName     = $upload_dir . 'wp-file-download-search.selected-';
        $allFilesName = '';

        foreach ($files as $fileId) {
            $file = $fileModel->getFullFile($fileId);
            // Check access
            if (!WpfdHelperFile::checkAccess((array)$file)) {
                continue;
            }

            // Check category for sure it not come from cloud
            $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $file->catid);
            if (in_array($categoryFrom, wpfd_get_support_cloud())) {
                continue;
            }

            /**
             * Filter of file selected to download
             *
             * @param array
             */
            $file = apply_filters('wpfd_selected_file', $file);

            if (!$file) {
                continue;
            }
            // Add file
            $filesObj[] = $file;

            // Calculate zip file name to made a hash
            $allFilesName .= $file->title;
            $allFilesName .= $file->size;
            if ($file->remote_url) {
                $allFilesName .= $file->name . $file->size . $file->ext . $file->version . $file->modified;
            } else {
                $allFilesName .= filemtime(WpfdBase::getFilesPath($file->catid) . '/' . $file->file);
            }
        }
        // Create a hash with all files name
        $hash = md5($allFilesName);
        $zipName .= $hash . '.zip';
        if (file_exists($zipName)) {
            wp_send_json_success(array('hash' => $hash));
            die();
        }

        // Zip it
        if (!empty($filesObj) && count($filesObj) > 0) {
            $zipFiles = new ZipArchive();
            $zipFiles->open($zipName, ZipArchive::CREATE);
            $count = 0;
            foreach ($filesObj as $file) {
                $sysfile   = WpfdBase::getFilesPath($file->catid) . '/' . $file->file;

                $file->title = str_replace('&amp;', '&', $file->title);
                $file->title = str_replace('&#039;', '\'', $file->title);
                $wpfd_disable_santize_file_name = apply_filters('wpfd_disable_santize_file_name', false);
                if ($wpfd_disable_santize_file_name) {
                    $file_name = $file->title;
                } else {
                    $file_name = WpfdHelperFile::santizeFileName($file->title);
                }

                for ($i = 0; $i < $zipFiles->numFiles; $i++) {
                    if ($zipFiles->getNameIndex($i) === $file_name . '.' . $file->ext) {
                        $count++;
                    }
                }
                if ($count > 0) {
                    $file_name = $file_name . '(' . $count . ')';
                }
                $zipFiles->addFile($sysfile, $file_name . '.' . $file->ext);
            }
            $zipFiles->close();
        } else {
            wp_send_json_error(array('message' => esc_html__('There is no file to download!', 'wpfd')));
            die();
        }

        // Return hashed information
        wp_send_json_success(array('hash' => $hash));
        die();
    }

    /**
     * Download ziped file with many categories
     *
     * @return void
     */
    public function downloadZipedFileMultipleCategories()
    {
        $searchName = Utilities::getInput('wpfd_search_name', 'GET', 'string');
        $hash       = Utilities::getInput('hash', 'GET', 'string');
        if (empty($hash) || trim($hash) === '') {
            die(esc_html__('Missing hash or wrong category id!', 'wpfd'));
        }

        // Check hash
        $wpUploadDir = wp_upload_dir('wpfd');
        $upload_dir  = $wpUploadDir['path'];
        $zipName     = $upload_dir . 'wp-file-download-search.selected-' . $hash . '.zip';

        if (!file_exists($zipName)) {
            die(esc_html__('The file you request does not exists!', 'wpfd'));
        }
        // Send ziped file if it exists
        WpfdHelperFile::SendDownload($zipName, $searchName . '.zip', 'zip');
        // Remove file after download
        unlink($zipName);
        exit();
    }

    /**
     * Download header file
     *
     * @param string  $filename File name
     * @param integer $size     File size
     *
     * @return void
     */
    public function downloadHeader($filename, $size)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        header('Content-Disposition: attachment; filename="' . esc_html($filename));
        header('Content-Type:  application/zip');
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        if ((int) $size !== 0) {
            header('Content-Length: ' . $size);
        }
        ob_clean();
        flush();
    }

    /**
     * Get all files in category
     *
     * @param integer $catid Category id
     *
     * @return array|string
     */
    private function getAllFiles($catid)
    {
        $app           = Application::getInstance('Wpfd');
        $path_wpfdbase = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
        $path_wpfdbase .= DIRECTORY_SEPARATOR . 'WpfdBase.php';
        require_once $path_wpfdbase;
        $modelConfig     = Model::getInstance('configfront');
        $modelCategory = Model::getInstance('categoryfront');
        $modelFiles    = Model::getInstance('filesfront');
        $modelTokens  = Model::getInstance('tokens');
        $token = '';
        $global_settings = $modelConfig->getGlobalConfig();
        $category      = $modelCategory->getCategory($catid);
        if (empty($category)) {
            return '';
        }

        $params           = $category->params;
        $params['social'] = isset($params['social']) ? $params['social'] : 0;
        if ((int) $category->access === 1) {
            $user  = wp_get_current_user();
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows = array_intersect($roles, $category->roles);

            $singleuser = false;

            if (isset($params['canview']) && $params['canview'] === '') {
                $params['canview'] = 0;
            }

            $canview = isset($params['canview']) ? $params['canview'] : 0;

            if ((int) $global_settings['restrictfile'] === 1) {
                $user    = wp_get_current_user();
                $user_id = $user->ID;

                if ($user_id) {
                    if ((int) $canview === $user_id || (int) $canview === 0) {
                        $singleuser = true;
                    } else {
                        $singleuser = false;
                    }
                } else {
                    if ((int) $canview === 0) {
                        $singleuser = true;
                    } else {
                        $singleuser = false;
                    }
                }
            }
            // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
            if ((int) $canview !== 0 && is_countable($category->roles) && !count($category->roles)) {
                if ($singleuser === false) {
                    return '';
                }
            } elseif ((int) $canview !== 0 && is_countable($category->roles) && count($category->roles)) { // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
                if (!(!empty($allows) || ($singleuser === true))) {
                    return '';
                }
            } else {
                if (empty($allows)) {
                    return '';
                }
            }
        }


        if (is_user_logged_in()) {
            $sessionToken = isset($_SESSION['wpfdToken']) ? $_SESSION['wpfdToken'] : null;
            if ($sessionToken === null) {
                $token = $modelTokens->createToken();
                $_SESSION['wpfdToken'] = $token;
            } else {
                $tokenId = $modelTokens->tokenExists($sessionToken);
                if ($tokenId) {
                    $modelTokens->updateToken($tokenId);
                    $token = $sessionToken;
                    $_SESSION['wpfdToken'] = $token;
                } else {
                    $token = $modelTokens->createToken();
                    $_SESSION['wpfdToken'] = $token;
                }
            }
        }
        $category = $modelCategory->getCategory($catid);
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
        if ($categoryFrom === 'googleDrive') {
            $files = array();
        } elseif ($categoryFrom === 'dropbox') {
            $files = array();
        } elseif ($categoryFrom === 'onedrive') {
            $files = array();
        } elseif ($categoryFrom === 'onedrive_business') {
            $files = array();
        } elseif ($categoryFrom === 'aws') {
            $files = array();
        } elseif ($categoryFrom === 'nextcloud') {
            $files = array();
        } else {
            $files       = $modelFiles->getFiles($catid, 'created_time', 'asc');
            $description = json_decode($category->description, true);
            $lstAllFile  = null;
            if (!empty($description) && isset($description['refToFile'])) {
                if (isset($description['refToFile'])) {
                    $listCatRef = $description['refToFile'];
                    $lstAllFile = $this->getAllFileRef($modelFiles, $listCatRef, 'created_time', 'asc');
                }
            }
            if ($lstAllFile && !empty($lstAllFile)) {
                $files = array_merge($lstAllFile, $files);
            }
            if (!empty($files) && ((int) $global_settings['restrictfile'] === 1)) {
                $user    = wp_get_current_user();
                $user_id = $user->ID;
                foreach ($files as $key => $file) {
                    $metadata = get_post_meta($file->ID, '_wpfd_file_metadata', true);
                    $canview  = isset($metadata['canview']) ? $metadata['canview'] : 0;
                    $canview  = array_map('intval', explode(',', $canview));
                    if ($user_id) {
                        if (!(in_array($user_id, $canview) || in_array(0, $canview))) {
                            unset($files[$key]);
                        }
                    } else {
                        if (!in_array(0, $canview)) {
                            unset($files[$key]);
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Get all file referent to categories
     *
     * @param object $model       Model
     * @param array  $listCatRef  List categories
     * @param string $ordering    Ordering
     * @param string $orderingdir Ordering dir
     *
     * @return array
     */
    public function getAllFileRef($model, $listCatRef, $ordering, $orderingdir)
    {
        $lstAllFile = array();
        foreach ($listCatRef as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $lstFile    = $model->getFiles($key, $ordering, $orderingdir, $value);
                $lstAllFile = array_merge($lstFile, $lstAllFile);
            }
        }

        return $lstAllFile;
    }

    /**
     * DisplayFilesAccordingToEachUploadActions
     *
     * @return void
     */
    public function displayFilesAccordingToEachUploadActions()
    {
        $app                = Application::getInstance('Wpfd');
        $modelCategory      = Model::getInstance('categoryfront');
        $modelConfig        = Model::getInstance('configfront');
        $global_settings    = $modelConfig->getGlobalConfig();
        $modelFiles         = Model::getInstance('filesfront');
        $modelTokens        = Model::getInstance('tokens');
        $category_id        = Utilities::getInput('id_category', 'POST', 'none');
        $container          = Utilities::getInput('upload_form_container', 'POST', 'none');
        $file_limit         = Utilities::getInput('file_limit', 'POST', 'none');
        $results            = '';
        if (!$category_id) {
            return;
        }

        $categoryUpload = $modelCategory->getCategory($category_id);
        $files          = array();
        $limitedFiles   = array();
        $token          = $modelTokens->getOrCreateNew();
        $result_limit   = isset($result_limit) ? (int) $result_limit : 25;
        $variables      = array(
            'files'      => array(),
            'ordering'   => 'type',
            'dir'        => 'asc',
            'viewer'     => WpfdBase::loadValue($global_settings, 'use_google_viewer', 'no'),
            'limit'      => $result_limit,
            'baseurl'    => $app->getBaseUrl()
        );
        $categoryFrom   = apply_filters('wpfdAddonCategoryFrom', $category_id);
        if ($categoryFrom === 'googleDrive') {
            $files = apply_filters(
                'wpfdAddonGetListGoogleDriveFile',
                $category_id,
                $categoryUpload->ordering,
                $categoryUpload->orderingdir,
                $categoryUpload->slug,
                $token
            );
        } elseif ($categoryFrom === 'dropbox') {
            $files = apply_filters(
                'wpfdAddonGetListDropboxFile',
                $category_id,
                $categoryUpload->ordering,
                $categoryUpload->orderingdir,
                $categoryUpload->slug,
                $token
            );
        } elseif ($categoryFrom === 'onedrive') {
            $files = apply_filters(
                'wpfdAddonGetListOneDriveFile',
                $category_id,
                $categoryUpload->ordering,
                $categoryUpload->orderingdir,
                $categoryUpload->slug,
                $token
            );
        } elseif ($categoryFrom === 'onedrive_business') {
            $files = apply_filters(
                'wpfdAddonGetListOneDriveBusinessFile',
                $category_id,
                $categoryUpload->ordering,
                $categoryUpload->orderingdir,
                $categoryUpload->slug,
                $token
            );
        } elseif ($categoryFrom === 'aws') {
            $files = apply_filters(
                'wpfdAddonGetListAwsFile',
                $category_id,
                $categoryUpload->ordering,
                $categoryUpload->orderingdir,
                $categoryUpload->slug,
                $token
            );
        } elseif ($categoryFrom === 'nextcloud') {
            $files = apply_filters(
                'wpfdAddonGetListNextcloudFile',
                $category_id,
                $categoryUpload->ordering,
                $categoryUpload->orderingdir,
                $categoryUpload->slug,
                $token
            );
        } else {
            $files = $modelFiles->getFiles($category_id, 'created_time', 'asc');
        }

        if (!empty($files)) {
            foreach ($files as $key => $file) {
                if (isset($file->state) && (int) $file->state === 0) {
                    unset($files[$key]);
                }
            }
            if ($file_limit && (int) $file_limit > 0 && !empty($files)) {
                $files  = array_slice($files, 0, (int) $file_limit);
                $variables['files'] = $files;
            } else {
                $variables['files'] = $files;
            }
            $variables['upload_show_file_limit'] = $file_limit;
            $variables['upload_download_selected'] = false;
            $results = wpfd_get_template_html('tpl-search-results.php', $variables);
            wp_send_json(array('success' => true, 'data' => $results, 'container' => $container));
        } else {
            wp_send_json(array('success' => false, 'data' => $results, 'container' => $container));
        }
    }
}
