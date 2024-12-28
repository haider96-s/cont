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

defined('ABSPATH') || die();

/**
 * Class WpfdModelFiles
 */
class WpfdModelFiles extends Model
{
    /**
     * Search files
     *
     * @param string         $s           Search string
     * @param string|integer $id_category Category to search
     * @param string         $ordering    Ordering
     * @param string         $dir         Ordering Direction
     *
     * @return array
     */
    public function searchfilexx($s, $id_category, $ordering, $dir)
    {
        $modelConfig = $this->getInstance('config');
        $params      = $modelConfig->getConfig();

        $args = array(
            'posts_per_page' => -1,
            'post_type'      => 'wpfd_file'
        );
        if (isset($s) && $s !== '') {
            $args['s'] = $s;
        }
        if (!empty($id_category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy'         => 'wpfd-category',
                    'terms'            => (int) $id_category,
                    'include_children' => true
                )
            );
        }
        $results = get_posts($args);
        $files   = array();
        foreach ($results as $result) {
            $metaData         = get_post_meta($result->ID, '_wpfd_file_metadata', true);
            $result->ext      = isset($metaData['ext']) ? $metaData['ext'] : '';
            $result->hits     = isset($metaData['hits']) ? (int) $metaData['hits'] : 0;
            $result->versionNumber  = isset($metaData['version']) ? $metaData['version'] : '';
            $result->size     = isset($metaData['size']) ? $metaData['size'] : 0;
            $result->created_time = get_date_from_gmt($result->post_date_gmt);
            $result->modified_time = get_date_from_gmt($result->post_modified_gmt);
            $result->created  = mysql2date(
                WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                $result->created_time
            );
            $result->modified = mysql2date(
                WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                $result->modified_time
            );
            $term_list        = wp_get_post_terms($result->ID, 'wpfd-category', array('fields' => 'ids'));
            $wpfd_term        = get_term($term_list[0], 'wpfd-category');
            $result->catname  = sanitize_title($wpfd_term->name);
            if (!is_wp_error($term_list)) {
                $result->catid = $term_list[0];
            } else {
                $result->catid = 0;
            }
            $linkdownload_str     = admin_url('admin-ajax.php') . '?juwpfisadmin=false&action=wpfd&task=file.download';
            $linkdownload_str     .= '&wpfd_category_id=' . $result->catid . '&wpfd_file_id=' . $result->ID;
            $result->linkdownload = $linkdownload_str;
            $files[]              = $result;
        }

        if (in_array($ordering, array('type', 'title', 'created', 'updated', 'size'))) {
            switch ($ordering) {
                case 'type':
                    if ($dir === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpTypeDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpType'));
                    }
                    break;
                case 'created':
                    if ($dir === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpCreatedDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpCreated'));
                    }
                    break;
                case 'updated':
                    if ($dir === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpModifiedDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpModified'));
                    }
                    break;

                case 'size':
                    if ($dir === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpSizeDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpSize'));
                    }
                    break;
                case 'title':
                default:
                    if ($dir === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpTitleDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpTitle'));
                    }
                    break;
            }
        }

//        $limit = 100;
//        if ($limit > 0) {
//            $files = array_slice($files, 0, $limit);
//        }

        return $files;
    }

    /**
     * Search file in local storage
     *
     * @param string         $file_type          File type
     * @param string         $file_tags          File tags
     * @param integer        $weight_from        Min file weight
     * @param integer        $weight_to          Max file weight
     * @param array          $args               Post arguments
     * @param array          $params             Search params
     * @param string         $created_date       Created date
     * @param string         $updated_date       Updated date
     * @param boolean        $waitingForApproval Pending status
     * @param integer|string $catId              Category id
     *
     * @throws Exception Fire if errors
     *
     * @return array|boolean
     */
    public function searchLocal($file_type, $file_tags, $weight_from, $weight_to, $args, $params, $created_date = '', $updated_date = '', $waitingForApproval = false, $catId = 0)
    {
        $keySearch = isset($args['s']) ? $args['s'] : '';
        $dateFormat = empty($params['date_format']) ? get_option('date_format') : $params['date_format'];
        if (($keySearch !== '' && strpos($keySearch, '&') !== false)
            || ($keySearch !== '' && strpos($keySearch, '\'') !== false)) {
            unset($args['s']);
            $results = get_posts($args);
            $keySearch = str_replace('\\', '', $keySearch);
            $newKeySearch = str_replace('&', '&amp;', $keySearch);
            $newKeySearch = str_replace('\'', '&#039;', $newKeySearch);
            $list = array();

            if (!empty($results)) {
                foreach ($results as $post) {
                    if (strpos($post->post_title, $keySearch) !== false || strpos($post->post_title, $newKeySearch) !== false) {
                        $list[] = $post;
                    }
                }
            }

            $results = $list;
        } else {
            $results = get_posts($args);
        }

        if (is_wp_error($results)) {
            return false;
        }

        // File multiple category
        $lstAllFiles = array();
        $fileMultiCategoryIds = array();
        $filteredIds = array();
        if (is_numeric($catId) && intval($catId) !== 0) {
            // Check list ref file in category children
            $children = get_term_children($catId, 'wpfd-category');
            if (!empty($children) && !is_wp_error($children)) {
                $children[] = $catId;
            } else {
                $children = array($catId);
            }
            $multiCatFiles = array();

            foreach ($children as $childID) {
                // Get list file ref to this category
                $term = get_term($childID, 'wpfd-category');
                if (!is_wp_error($term)) {
                    $description   = json_decode($term->description, true);
                    if (!empty($description) && isset($description['refToFile'])) {
                        $refFiles = $description['refToFile'];
                    }

                    if (isset($refFiles) && count($refFiles)) {
                        foreach ($refFiles as $refCat => $refFileIds) {
                            foreach ($refFileIds as $refFileId) {
                                $multiCatFiles[] = array('refCatId' => $childID, 'ID' => $refFileId);
                                $fileMultiCategoryIds[] = $refFileId;
                            }
                        }
                        // Get file multi categories in category on searching
                        $lstAllFile = $this->getAllFileRef($refFiles, 'created_time', 'asc');
                        if (!empty($lstAllFile)) {
                            $lstAllFiles = array_merge($lstAllFile, $lstAllFiles);
                        }
                        unset($refFiles);
                    }
                }
            }
        }

        if (!empty($lstAllFiles)) {
            foreach ($lstAllFiles as $mtfIndex => $mtfFile) {
                if (isset($args['s']) && $args['s'] !== '' && isset($mtfFile->post_title)
                    && strpos(strtolower($mtfFile->post_title), strtolower($args['s'])) === false) {
                    unset($lstAllFiles[$mtfIndex]);
                }

                // Search created date for multiple category files
                if ($created_date !== '') {
                    $mtfFile->created = $this->getDate($dateFormat, $mtfFile->created, true);
                    $created_date = $this->getDate($dateFormat, $created_date, true);
                    if (strtotime($mtfFile->created) < strtotime($created_date)) {
                        unset($lstAllFiles[$mtfIndex]);
                    }
                }

                // Search updated date for multiple category files
                if ($updated_date !== '') {
                    $mtfFile->modified = $this->getDate($dateFormat, $mtfFile->modified, true);
                    $updated_date = $this->getDate($dateFormat, $updated_date, true);
                    if (strtotime($mtfFile->modified) < strtotime($updated_date)) {
                        unset($lstAllFiles[$mtfIndex]);
                    }
                }
            }

            $results = array_merge($lstAllFiles, $results);
        }

        $files = array();
        $file_type = !empty($file_type) ? explode(',', $file_type) : array();
        $file_type_list = array_map(function ($type) {
            return trim($type);
        }, $file_type);

        foreach ($results as $result) {
            // Filter by meta
            $metaData = get_post_meta($result->ID, '_wpfd_file_metadata', true);
            $ext = isset($metaData['ext']) ? $metaData['ext'] : '';

            // Extension check
            if (!empty($file_type_list)) {
                if (!in_array($ext, $file_type_list)) {
                    continue;
                }
            }
            // File size check
            $size = isset($metaData['size']) ? intval($metaData['size']) : 0;
            $fileSize = $size;
            $factor = floor((strlen($size) - 1) / 3);
            $tSize = sprintf('%.' . 2 . 'f', $size / pow(1024, $factor));
            $fSize = floatval($tSize);
            $sz = WpfdHelperFiles::getSupportFileMeasure();
            $sizeType = strtolower($sz[$factor]);
            switch ($sizeType) {
                case 'kb':
                    $size = $fSize * 1024;
                    break;
                case 'mb':
                    $size = $fSize * 1024 * 1024;
                    break;
                case 'gb':
                    $size = $fSize * 1024 * 1024 * 1024;
                    break;
                default:
                    $size = $fSize;
                    break;
            }

            if (!empty($weight_from) && !empty($weight_to)) {
                if ($size < $weight_from || $size > $weight_to) {
                    continue;
                }
            } elseif (!empty($weight_from) && empty($weight_to)) {
                if ($size < $weight_from) {
                    continue;
                }
            } elseif (empty($weight_from) && !empty($weight_to)) {
                if ($size > $weight_to) {
                    continue;
                }
            }

            // Assign file metadata
            $result->ext = $ext;
            $result->hits = isset($metaData['hits']) ? (int)$metaData['hits'] : 0;
            $result->versionNumber = isset($metaData['version']) ? $metaData['version'] : '';
            $result->size = $fileSize;
            $result->created_time = get_date_from_gmt($result->post_date_gmt);
            $result->modified_time = get_date_from_gmt($result->post_modified_gmt);
            $result->created = mysql2date(
                WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                $result->created_time
            );
            $result->modified = mysql2date(
                WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                $result->modified_time
            );
            $term_list = wp_get_post_terms($result->ID, 'wpfd-category', array('fields' => 'ids'));
            $wpfd_term = get_term($term_list[0], 'wpfd-category');
            $result->catname = sanitize_title($wpfd_term->name);
            if (!is_wp_error($term_list)) {
                $result->catid = $term_list[0];
            } else {
                $result->catid = 0;
            }

            if ($waitingForApproval) {
                $isPending = apply_filters('wpfd_file_upload_pending', intval($result->ID), $result->catid);
                if (!$isPending) {
                    continue;
                }
            }

            list($fileId, $catId, $lang) = wpfd_correct_wpml_language($result->ID, $result->catid);
            $linkdownload_str = admin_url('admin-ajax.php') . '?juwpfisadmin=false&action=wpfd&task=file.download';
            $linkdownload_str .= '&wpfd_category_id=' . $catId . '&wpfd_file_id=' . $fileId;
            $result->linkdownload = $linkdownload_str;
            $files[] = $result;
        }

        return $files;
    }

    /**
     * Search file(s)
     *
     * @param string  $keyword            Keyword
     * @param integer $catId              Category Id
     * @param string  $ordering           Ordering
     * @param string  $dir                Ordering direction
     * @param string  $file_type          File type
     * @param string  $file_tags          File tags
     * @param string  $created_date       Create date
     * @param string  $updated_date       Updated date
     * @param string  $weight_from        File size from
     * @param string  $weight_to          File size to
     * @param boolean $waitingForApproval Pending status
     *
     * @throws Exception Fire if errors
     *
     * @return array|boolean
     */
    public function searchFilesV2($keyword, $catId = 0, $ordering = 'title', $dir = 'ASC', $file_type = '', $file_tags = '', $created_date = '', $updated_date = '', $weight_from = '', $weight_to = '', $waitingForApproval = false)
    {
        Application::getInstance('Wpfd');
        $modelConfig = $this->getInstance('config');
        $modelCategories = $this->getInstance('categories');
        $params = $modelConfig->getConfig();
        $categories = $modelCategories->getCategories();
        $ownCategories = array_map(function ($category) {
            return $category->term_id;
        }, $categories);
        $dateFormat = empty($params['date_format']) ? get_option('date_format') : $params['date_format'];
        $args = array(
            'posts_per_page' => -1,
            'post_type'      => 'wpfd_file',
            'post_status'    => 'any'
        );
        $cloud_cond = array();
        $cloud_cond[] = "mimeType != 'application/vnd.google-apps.folder' and trashed = false";

        if (isset($keyword) && $keyword !== '') {
            $args['s'] = $keyword;
            $cloud_cond[] = "fullText contains '\"" . $keyword . "\"'";
        }
        $categoryFrom = false;
        $searchAllCategories = false;

        if (!is_null($catId) && $catId > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy'         => 'wpfd-category',
                    'terms'            => (int) $catId,
                    'include_children' => true
                )
            );
            $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catId);
        } else {
            $args['tax_query'] = array(
                array(
                    'taxonomy'         => 'wpfd-category',
                    'terms'            => $ownCategories,
                    'include_children' => false
                )
            );
            $searchAllCategories = true;
        }
        // Add Date query
        $dateArgs = array();

        if (count($dateArgs)) {
            $args['date_query'] = array(
                'relation' => 'AND',
                $dateArgs
            );
        }

        // Search for file(s) created date on normal categories
        if ($created_date !== '') {
            $args['date_query'][] = array(
                'after' => $this->getDate($dateFormat, $created_date, true),
                'column' => 'post_date'
            );
        }

        // Search for file(s) updated date on normal categories
        if ($updated_date !== '') {
            $args['date_query'][] = array(
                'after' => $this->getDate($dateFormat, $updated_date, true),
                'column' => 'post_modified'
            );
        }

        if (!empty($file_tags)) {
            $file_tags = explode(',', $file_tags);
        } else {
            $file_tags = array();
        }

        // Search tags
        if (!empty($file_tags)) {
            /**
             * Filter allow to change relation of tags
             *
             * @param string
             */
            $tagRelation = strtoupper(apply_filters('wpfd_search_tags_relation', 'OR'));
            if ($tagRelation === 'AND') {
                $tagsArgs = array(
                    'relation' => 'AND',
                );
                foreach ($file_tags as $tag) {
                    $tagsArgs[] = array(
                        'taxonomy' => 'wpfd-tag',
                        'field'    => 'name',
                        'terms'    => $tag,
                        'operator' => 'IN',
                    );
                }
            } else {
                $tagsArgs = array(
                    'relation' => $tagRelation,
                    array(
                        'taxonomy' => 'wpfd-tag',
                        'field'    => 'name',
                        'terms'    => $file_tags,
                        'operator' => 'IN',
                    )
                );
            }

            $args['tax_query'][] = $tagsArgs;
        }

        if (!$searchAllCategories) {
            switch ($categoryFrom) {
                case 'googleDrive':
                    if (has_filter('wpfdAddonSearchCloud', 'wpfdAddonSearchCloud')) {
                        $filters = array(
                            'catid' => WpfdAddonHelper::getGoogleDriveIdByTermId($catId),
                            'exclude' => '',
                            'q' => $keyword,
                            'isAdminSearch' => true,
                            'waitingForApproval' => $waitingForApproval,
                            'ext' => $file_type,
                            'wfrom' => $weight_from,
                            'wto' => $weight_to,
                            'cfrom' => $created_date,
                            'ufrom' => $updated_date
                        );

                        /**
                         * Filters to search in google drive
                         *
                         * @param array Google search condition
                         * @param array Search condition
                         *
                         * @return array
                         *
                         * @internal
                         */
                        $files = apply_filters('wpfdAddonSearchCloud', $cloud_cond, $filters);

                        // Search multiple category files on GoogleDrive
                        $refFiles = $this->wpfdSearchCloudMultipleCategoryFiles($catId, $ordering, $dir, $filters);

                        if (!empty($refFiles)) {
                            $files = array_merge($files, $refFiles);
                        }

                        // Search tags
                        if (!empty($file_tags)) {
                            $files = $this->searchFileTags($files, $file_tags);
                        }
                    }
                    break;
                case 'googleTeamDrive':
                    if (has_filter('wpfdAddonSearchCloudTeamDrive', 'wpfdAddonSearchCloudTeamDrive')) {
                        $filters = array(
                            'catid'              => WpfdAddonHelper::getGoogleTeamDriveIdByTermId($catId),
                            'exclude'            => '',
                            'q'                  => $keyword,
                            'isAdminSearch'      => true,
                            'waitingForApproval' => $waitingForApproval,
                            'ext'                => $file_type,
                            'wfrom'              => $weight_from,
                            'wto'                => $weight_to,
                            'cfrom'              => $created_date,
                            'ufrom'              => $updated_date
                        );

                        /**
                         * Filters to search in google team drive
                         *
                         * @param array Google team drive search condition
                         * @param array Search condition
                         *
                         * @return array
                         *
                         * @internal
                         */
                        $files = apply_filters('wpfdAddonSearchCloudTeamDrive', $cloud_cond, $filters);

                        // Search multiple category files in google team drive
                        $refFiles = $this->wpfdSearchCloudMultipleCategoryFiles($catId, $ordering, $dir, $filters);

                        if (!empty($refFiles)) {
                            $files = array_merge($files, $refFiles);
                        }

                        // Search tags
                        if (!empty($file_tags)) {
                            $files = $this->searchFileTags($files, $file_tags);
                        }
                    }
                    break;
                case 'onedrive':
                    if (has_filter('wpfdAddonSearchOneDrive', 'wpfdAddonSearchOneDrive')) {
                        $filters = array(
                            'catid' => WpfdAddonHelper::getOneDriveIdByTermId($catId),
                            'exclude' => '',
                            'q' => $keyword,
                            'isAdminSearch' => true,
                            'waitingForApproval' => $waitingForApproval,
                            'ext' => $file_type,
                            'wfrom' => $weight_from,
                            'wto' => $weight_to,
                            'cfrom' => $created_date,
                            'ufrom' => $updated_date
                        );

                        /**
                         * Filters to search in onedrive
                         *
                         * @param array Search condition
                         *
                         * @return array
                         *
                         * @internal
                         */
                        $files = apply_filters('wpfdAddonSearchOneDrive', $filters);

                        // Search multiple category files on OneDrive
                        $refFiles = $this->wpfdSearchCloudMultipleCategoryFiles($catId, $ordering, $dir, $filters);

                        if (!empty($refFiles)) {
                            $files = array_merge($files, $refFiles);
                        }

                        // Search tags
                        if (!empty($file_tags)) {
                            $files = $this->searchFileTags($files, $file_tags);
                        }
                    }

                    break;
                case 'onedrive_business':
                    if (has_filter('wpfdAddonSearchOneDriveBusiness', 'wpfdAddonSearchOneDriveBusiness')) {
                        $filters = array(
                            'catid' => WpfdAddonHelper::getOneDriveBusinessIdByTermId($catId),
                            'exclude' => '',
                            'q' => $keyword,
                            'isAdminSearch' => true,
                            'waitingForApproval' => $waitingForApproval,
                            'ext' => $file_type,
                            'wfrom' => $weight_from,
                            'wto' => $weight_to,
                            'cfrom' => $created_date,
                            'ufrom' => $updated_date
                        );

                        /**
                         * Filters to search in onedrive business
                         *
                         * @param array Search condition
                         *
                         * @return array
                         *
                         * @internal
                         */
                        $files = apply_filters('wpfdAddonSearchOneDriveBusiness', $filters);

                        // Search multiple category files on OneDrive Business
                        $refFiles = $this->wpfdSearchCloudMultipleCategoryFiles($catId, $ordering, $dir, $filters);

                        if (!empty($refFiles)) {
                            $files = array_merge($files, $refFiles);
                        }

                        // Search tags
                        if (!empty($file_tags)) {
                            $files = $this->searchFileTags($files, $file_tags);
                        }
                    }
                    break;
                case 'dropbox':
                    if (has_filter('wpfdAddonSearchDropbox', 'wpfdAddonSearchDropbox')) {
                        $filters = array(
                            'catid' => WpfdAddonHelper::getDropboxIdByTermId($catId),
                            'q' => $keyword,
                            'isAdminSearch' => true,
                            'waitingForApproval' => $waitingForApproval,
                            'ext' => $file_type,
                            'wfrom' => $weight_from,
                            'wto' => $weight_to,
                            'cfrom' => $created_date,
                            'ufrom' => $updated_date
                        );

                        if (isset($filters['q']) && $filters['q'] === '') {
                            unset($filters['q']);
                        }

                        /**
                         * Filters to search in dropbox
                         *
                         * @param array Search condition
                         *
                         * @return array
                         *
                         * @internal
                         */
                        $files = apply_filters('wpfdAddonSearchDropbox', $filters);

                        // Search multiple category files on Dropbox
                        $refFiles = $this->wpfdSearchCloudMultipleCategoryFiles($catId, $ordering, $dir, $filters);

                        if (!empty($refFiles)) {
                            $files = array_merge($files, $refFiles);
                        }

                        // Search tags
                        if (!empty($file_tags)) {
                            $files = $this->searchFileTags($files, $file_tags);
                        }
                    }
                    break;
                case 'aws':
                    if (has_filter('wpfdAddonSearchAws', 'wpfdAddonSearchAws')) {
                        $filters = array(
                            'catid' => WpfdAddonHelper::getAwsIdByTermId($catId),
                            'q' => $keyword,
                            'isAdminSearch' => true,
                            'waitingForApproval' => $waitingForApproval,
                            'ext' => $file_type,
                            'wfrom' => $weight_from,
                            'wto' => $weight_to,
                            'cfrom' => $created_date,
                            'ufrom' => $updated_date
                        );

                        if (isset($filters['q']) && $filters['q'] === '') {
                            unset($filters['q']);
                        }

                        /**
                         * Filters to search in Aws
                         *
                         * @param array Search condition
                         *
                         * @return array
                         *
                         * @internal
                         */
                        $files = apply_filters('wpfdAddonSearchAws', $filters);

                        if (!empty($refFiles)) {
                            $files = array_merge($files, $refFiles);
                        }

                        // Search tags
                        if (!empty($file_tags)) {
                            $files = $this->searchFileTags($files, $file_tags);
                        }
                    }
                    break;
                case 'nextcloud':
                    if (has_filter('wpfdAddonSearchNextcloud', 'wpfdAddonSearchNextcloud')) {
                        $filters = array(
                            'catid' => WpfdAddonHelper::getNextcloudPathByTermId($catId),
                            'q' => $keyword,
                            'isAdminSearch' => true,
                            'waitingForApproval' => $waitingForApproval,
                            'ext' => $file_type,
                            'wfrom' => $weight_from,
                            'wto' => $weight_to,
                            'cfrom' => $created_date,
                            'ufrom' => $updated_date
                        );

                        if (isset($filters['q']) && $filters['q'] === '') {
                            unset($filters['q']);
                        }

                        /**
                         * Filters to search in Nextcloud
                         *
                         * @param array Search condition
                         *
                         * @return array
                         *
                         * @internal
                         */
                        $files = apply_filters('wpfdAddonSearchNextcloud', $filters);

                        if (!empty($refFiles)) {
                            $files = array_merge($files, $refFiles);
                        }

                        // Search tags
                        if (!empty($file_tags)) {
                            $files = $this->searchFileTags($files, $file_tags);
                        }
                    }
                    break;
                default:
                    $files = $this->searchLocal($file_type, $file_tags, $weight_from, $weight_to, $args, $params, $created_date, $updated_date, $waitingForApproval, $catId);
                    break;
            }
        } else {
            $filters = array(
                'catid' => 0,
                'exclude' => '',
                'q' => $keyword
            );
            $arr1 = array();
            $arr2 = array();
            $arr3 = array();
            $arr4 = array();
            $arr5 = array();
            $arr6 = array();
            $arr7 = array();

            if (has_filter('wpfdAddonSearchDropbox', 'wpfdAddonSearchDropbox')) {
                /**
                 * Filters to search in dropbox
                 *
                 * @param array Search condition
                 *
                 * @return array
                 *
                 * @internal
                 */
                $arr1 = apply_filters('wpfdAddonSearchDropbox', $filters);
            }
            if (has_filter('wpfdAddonSearchCloud', 'wpfdAddonSearchCloud')) {
                /**
                 * Filters to search in google drive
                 *
                 * @param array Google search condition
                 * @param array Search condition
                 *
                 * @return array
                 *
                 * @internal
                 */
                $arr2 = apply_filters('wpfdAddonSearchCloud', $cloud_cond, $filters);
            }
            if (has_filter('wpfdAddonSearchOneDrive', 'wpfdAddonSearchOneDrive')) {
                /**
                 * Filters to search in onedrive
                 *
                 * @param array Search condition
                 *
                 * @return array
                 *
                 * @internal
                 */
                $arr3 = apply_filters('wpfdAddonSearchOneDrive', $filters);
            }
            if (has_filter('wpfdAddonSearchOneDriveBusiness', 'wpfdAddonSearchOneDriveBusiness')) {
                /**
                 * Filters to search in onedrive
                 *
                 * @param array Search condition
                 *
                 * @return array
                 *
                 * @internal
                 */
                $arr4 = apply_filters('wpfdAddonSearchOneDriveBusiness', $filters);
            }
            if (has_filter('wpfdAddonSearchAws', 'wpfdAddonSearchAws')) {
                /**
                 * Filters to search in Aws
                 *
                 * @param array Search condition
                 *
                 * @return array
                 *
                 * @internal
                 */
                $arr5 = apply_filters('wpfdAddonSearchAws', $filters);
            }
            if (has_filter('wpfdAddonSearchCloudTeamDrive', 'wpfdAddonSearchCloudTeamDrive')) {
                /**
                 * Filters to search in google team drive
                 *
                 * @param array Google team drive search condition
                 * @param array Search condition
                 *
                 * @return array
                 *
                 * @internal
                 */
                $arr6 = apply_filters('wpfdAddonSearchCloudTeamDrive', $cloud_cond, $filters);
            }
            if (has_filter('wpfdAddonSearchNextcloud', 'wpfdAddonSearchNextcloud')) {
                /**
                 * Filters to search in Nextcloud
                 *
                 * @param array Nextcloud search condition
                 * @param array Search condition
                 *
                 * @return array
                 *
                 * @internal
                 */
                $arr7 = apply_filters('wpfdAddonSearchNextcloud', $filters);
            }
            $array1 = array_merge($arr1, $arr2, $arr3, $arr4, $arr5, $arr6, $arr7);

            // Search tags
            if (!empty($file_tags)) {
                $array1 = $this->searchFileTags($array1, $file_tags);
            }

            $array2 = $this->searchLocal($file_type, $file_tags, $weight_from, $weight_to, $args, $params, $created_date, $updated_date, $waitingForApproval, $catId);

            if (is_array($array1) && is_array($array2)) {
                $files = array_merge($array1, $array2);
            } elseif (count($array1) > 0 && !is_array($array2)) {
                $files = $array1;
            } elseif (!is_array($array1) && count($array2) > 0) {
                $files = $array2;
            } else {
                $files = array();
            }
        }

        if (in_array($ordering, array('type', 'title', 'created', 'updated', 'size'))) {
            switch ($ordering) {
                case 'type':
                    if (strtolower($dir) === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpTypeDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpType'));
                    }
                    break;
                case 'created':
                    if (strtolower($dir) === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpCreatedDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpCreated'));
                    }
                    break;
                case 'updated':
                    if (strtolower($dir) === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpModifiedDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpModified'));
                    }
                    break;

                case 'size':
                    if (strtolower($dir) === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpSizeDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpSize'));
                    }
                    break;
                case 'title':
                default:
                    if (strtolower($dir) === 'desc') {
                        usort($files, array('WpfdModelFiles', 'cmpTitleDesc'));
                    } else {
                        usort($files, array('WpfdModelFiles', 'cmpTitle'));
                    }
                    break;
            }
        }

        return $files;
    }

    /**
     * Method compare type
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpType($a, $b)
    {
        if (strtolower($a->ext) === strtolower($b->ext)) {
            return strcmp($a->title, $b->title);
        }

        return strcmp($a->ext, $b->ext);
    }

    /**
     * Method compare type DESC
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpTypeDesc($a, $b)
    {
        if (strtolower($a->ext) === strtolower($b->ext)) {
            return strcmp($a->title, $b->title);
        }

        return strcmp($b->ext, $a->ext);
    }

    /**
     * Get file referent to category
     *
     * @param integer|string $id_category   Category id
     * @param array          $list_id_files List files id
     * @param string         $ordering      Ordering
     * @param string         $ordering_dir  Order direction
     *
     * @return array
     */
    public function getFilesRef($id_category, $list_id_files, $ordering = 'menu_order', $ordering_dir = 'ASC')
    {
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $id_category);
        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            /**
             * Filters to get files from google drive
             *
             * @param integer Category id
             * @param array   List file id
             *
             * @internal
             *
             * @return array
             */
            $files = apply_filters('wpfd_addon_get_files', $id_category, $categoryFrom, $list_id_files);
        } else {
            Application::getInstance('Wpfd');
            $modelConfig = $this->getInstance('config');
            $params      = $modelConfig->getConfig();
            $rmdownloadext = (int) WpfdBase::loadValue($params, 'rmdownloadext', 1) === 1;
            if ($ordering === 'ordering') {
                $ordering = 'menu_order';
            } elseif ($ordering === 'created_time') {
                $ordering = 'date';
            } elseif ($ordering === 'modified_time') {
                $ordering = 'modified';
            }
            $args    = array(
                'posts_per_page' => -1,
                'post_type'      => 'wpfd_file',
                'post_status'    => 'any',
                'orderby'        => $ordering,
                'order'          => $ordering_dir,
                'tax_query'      => array(
                    array(
                        'taxonomy'         => 'wpfd-category',
                        'terms'            => (int) $id_category,
                        'include_children' => false
                    )
                )

            );
            $results = get_posts($args);
            $files   = array();

            $config = get_option('_wpfd_global_config');
            if (empty($config) || empty($config['uri'])) {
                $seo_uri = 'download';
            } else {
                $seo_uri = rawurlencode($config['uri']);
            }
            $perlink       = get_option('permalink_structure');
            $rewrite_rules = get_option('rewrite_rules');

            foreach ($results as $result) {
                if (!in_array($result->ID, $list_id_files)) {
                    continue;
                }
                $metaData = get_post_meta($result->ID, '_wpfd_file_metadata', true);

                $result->ext      = isset($metaData['ext']) ? $metaData['ext'] : '';
                $result->hits     = isset($metaData['hits']) ? (int) $metaData['hits'] : 0;
                $result->version  = isset($metaData['version']) ? $metaData['version'] : '';
                $result->size     = isset($metaData['size']) ? $metaData['size'] : 0;
                $result->created_time = get_gmt_from_date($result->post_date_gmt);
                $result->modified_time = get_gmt_from_date($result->post_modified_gmt);
                $result->created  = mysql2date(
                    WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                    $result->created_time
                );
                $result->modified = mysql2date(
                    WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                    $result->modified_time
                );
                $term_list        = wp_get_post_terms($result->ID, 'wpfd-category', array('fields' => 'ids'));
                $wpfd_term        = get_term($term_list[0], 'wpfd-category');
                $result->catname  = sanitize_title($wpfd_term->name);
                if (!is_wp_error($term_list)) {
                    $result->catid = $term_list[0];
                } else {
                    $result->catid = 0;
                }
                $result->seouri = $seo_uri;
                list($fileId, $catId, $lang) = wpfd_correct_wpml_language($result->ID, $result->catid);
                if (!empty($rewrite_rules)) {
                    if (strpos($perlink, 'index.php')) {
                        $linkdownload         = get_site_url() . '/index.php' . $lang . '/' . $seo_uri . '/' . $catId . '/';
                        $linkdownload         .= $result->catname . '/' . $fileId . '/' . $result->post_name;
                        $result->linkdownload = $linkdownload;
                    } else {
                        $linkdownload         = get_site_url() . $lang . '/' . $seo_uri . '/' . $catId . '/';
                        $linkdownload         .= $result->catname . '/' . $fileId . '/' . $result->post_name;
                        $result->linkdownload = $linkdownload;
                    }
                    if ($result->ext && !$rmdownloadext) {
                        $result->linkdownload .= '.' . $result->ext;
                    }
                } else {
                    $linkdownload         = admin_url('admin-ajax.php') . '?juwpfisadmin=false&action=wpfd&task=file.download';
                    $linkdownload         .= '&wpfd_category_id=' . $catId . '&wpfd_file_id=' . $fileId;
                    $result->linkdownload = $linkdownload;
                }

                $files[] = $result;
            }
        }
        $reverse = strtoupper($ordering_dir) === 'DESC' ? true : false;

        if ($ordering === 'size') {
            $files = wpfd_sort_by_property($files, 'size', 'ID', $reverse);
        } elseif ($ordering === 'version') {
            $files = wpfd_sort_by_property($files, 'version', 'ID', $reverse);
        } elseif ($ordering === 'hits') {
            $files = wpfd_sort_by_property($files, 'hits', 'ID', $reverse);
        } elseif ($ordering === 'ext') {
            $files = wpfd_sort_by_property($files, 'ext', 'ID', $reverse);
        } elseif ($ordering === 'description') {
            $files = wpfd_sort_by_property($files, 'description', 'ID', $reverse);
        } elseif ($ordering === 'title') {
            $files = wpfd_sort_by_property($files, 'post_name', 'ID', $reverse);
        }

        return $files;
    }

    /**
     * Method compare Create date
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpCreated($a, $b)
    {
        return (strtotime($a->created_time) < strtotime($b->created_time)) ? -1 : 1;
    }

    /**
     * Method compare Create date desc
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpCreatedDesc($a, $b)
    {
        return (strtotime($a->created_time) > strtotime($b->created_time)) ? -1 : 1;
    }

    /**
     * Method compare Modified date
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpModified($a, $b)
    {
        return (strtotime($a->modified_time) < strtotime($b->modified_time)) ? -1 : 1;
    }

    /**
     * Method compare Modified date desc
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpModifiedDesc($a, $b)
    {
        return (strtotime($a->modified_time) > strtotime($b->modified_time)) ? -1 : 1;
    }

    /**
     * Method compare size
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpSize($a, $b)
    {
        return ($b->size > $a->size) ? -1 : 1;
    }

    /**
     * Method compare size desc
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpSizeDesc($a, $b)
    {
        return ($a->size > $b->size) ? -1 : 1;
    }

    /**
     * Method compare title
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpTitle($a, $b)
    {
        return strcmp($a->title, $b->title);
    }

    /**
     * Method compare title desc
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpTitleDesc($a, $b)
    {
        return strcmp($b->title, $a->title);
    }

    /**
     * Get file by ordering
     *
     * @param integer|string $id_category  Category id
     * @param string         $ordering     Ordering
     * @param string         $ordering_dir Order direction
     *
     * @return array
     */
    public function getFiles($id_category, $ordering = 'menu_order', $ordering_dir = 'ASC')
    {
        Application::getInstance('Wpfd');
        $modelConfig = $this->getInstance('config');
        $params      = $modelConfig->getConfig();
        $rmdownloadext = (int) Wpfdbase::loadValue($params, 'rmdownloadext', 1) === 1;
        if ($ordering === 'ordering') {
            $ordering = 'menu_order';
        } elseif ($ordering === 'created_time') {
            $ordering = 'date';
        } elseif ($ordering === 'modified_time') {
            $ordering = 'modified';
        }
        // WPML
        global $sitepress;
        if ($sitepress) {
            $wpml_args = array('element_id' => (int) $id_category, 'element_type' => 'wpfd-category' );
            $wpfd_cat_language_code = apply_filters('wpml_element_language_code', null, $wpml_args);
            if ($wpfd_cat_language_code) {
                $sitepress->switch_lang($wpfd_cat_language_code);
            }
        }
        $args    = array(
            'posts_per_page' => -1,
            'post_type'      => 'wpfd_file',
            'post_status'    => 'any',
            'orderby'        => $ordering,
            'order'          => $ordering_dir,
            'tax_query'      => array(
                array(
                    'taxonomy'         => 'wpfd-category',
                    'terms'            => (int) $id_category,
                    'include_children' => false
                )
            )

        );
        $results = get_posts($args);
        $files   = array();
        $config  = get_option('_wpfd_global_config');
        if (empty($config) || empty($config['uri'])) {
            $seo_uri = 'download';
        } else {
            $seo_uri = rawurlencode($config['uri']);
        }
        $perlink       = get_option('permalink_structure');
        $rewrite_rules = get_option('rewrite_rules');

        foreach ($results as $result) {
            $metaData = get_post_meta($result->ID, '_wpfd_file_metadata', true);
            $result->id       = $result->ID;
            $result->ext      = isset($metaData['ext']) ? $metaData['ext'] : '';
            $result->hits     = isset($metaData['hits']) ? (int) $metaData['hits'] : 0;
            $result->versionNumber  = isset($metaData['version']) ? $metaData['version'] : '';
            $result->size     = isset($metaData['size']) ? $metaData['size'] : 0;
            $result->created_time = get_date_from_gmt($result->post_date_gmt);
            $result->modified_time = get_date_from_gmt($result->post_modified_gmt);
            $result->created  = mysql2date(
                WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                $result->created_time
            );
            $result->modified = mysql2date(
                WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                $result->modified_time
            );
            $term_list        = wp_get_post_terms($result->ID, 'wpfd-category', array('fields' => 'ids'));
            $wpfd_term        = get_term($term_list[0], 'wpfd-category');
            $result->catname  = sanitize_title($wpfd_term->name);
            if (!is_wp_error($term_list)) {
                $result->catid = $term_list[0];
            } else {
                $result->catid = 0;
            }
            $result->seouri = $seo_uri;
            list($fileId, $catId, $lang) = wpfd_correct_wpml_language($result->ID, $result->catid);
            $check_wpml_dl = false;
            $lang_code = '';
            if ($lang === '') {
                if (defined('ICL_LANGUAGE_CODE')) {
                    $check_wpml_dl = true;
                    $lang_code = ICL_LANGUAGE_CODE;
                }
            }
            if (!empty($rewrite_rules)) {
                if (strpos($perlink, 'index.php')) {
                    if ($check_wpml_dl) {
                        $linkdownload         = get_site_url() . '/index.php/' . $seo_uri . '/' . $catId . '/';
                    } else {
                        $linkdownload         = get_site_url() . '/index.php/' . $lang . $seo_uri . '/' . $catId . '/';
                    }
                    $linkdownload         .= $result->catname . '/' . $fileId . '/' . $result->post_name;
                    $result->linkdownload = $linkdownload;
                } else {
                    if ($check_wpml_dl) {
                        $linkdownload         = get_site_url() . '/' . $seo_uri . '/' . $catId . '/' . $result->catname;
                    } else {
                        $linkdownload         = get_site_url() . $lang . '/' . $seo_uri . '/' . $catId . '/' . $result->catname;
                    }
                    $linkdownload         .= '/' . $fileId . '/' . $result->post_name;
                    $result->linkdownload = $linkdownload;
                }
                if ($result->ext && !$rmdownloadext) {
                    $result->linkdownload .= '.' . $result->ext;
                };
                if ($check_wpml_dl) {
                    $result->linkdownload .= '?lang='.$lang_code;
                }
            } else {
                $linkdownload         = admin_url('admin-ajax.php') . '?juwpfisadmin=false&action=wpfd&task=file.download';
                $linkdownload         .= '&wpfd_category_id=' . $catId . '&wpfd_file_id=' . $fileId;
                $result->linkdownload = $linkdownload;
            }
            $files[] = $result;
        }
        $reverse = strtoupper($ordering_dir) === 'DESC' ? true : false;
        if ($ordering === 'size') {
            $files = wpfd_sort_by_property($files, 'size', 'ID', $reverse);
        } elseif ($ordering === 'version') {
            $files = wpfd_sort_by_property($files, 'versionNumber', 'ID', $reverse);
        } elseif ($ordering === 'hits') {
            $files = wpfd_sort_by_property($files, 'hits', 'ID', $reverse);
        } elseif ($ordering === 'ext') {
            $files = wpfd_sort_by_property($files, 'ext', 'ID', $reverse);
        } elseif ($ordering === 'description') {
            $files = wpfd_sort_by_property($files, 'description', 'ID', $reverse);
        } elseif ($ordering === 'title') {
            $files = wpfd_sort_by_property($files, 'post_name', 'ID', $reverse);
        }

        /**
         * Filter admin files
         *
         * @param array
         *
         * @internal
         */
        return apply_filters('wpfd_admin_files', $files);
    }

    /**
     * Get extension file
     *
     * @param string $fileName File name
     *
     * @return array|null Returns the last value of array. If array is empty (or is not an array), NULL will be returned.
     */
    public function fileExt($fileName)
    {
        $pieces = explode('.', $fileName);
        return array_pop($pieces);
    }

    /**
     * Method to add a file into database
     *
     * @param array   $data       File data
     * @param boolean $remote_url Is the file or remote file
     *
     * @return integer|WP_Error The post ID on success. The value 0 or WP_Error on failure.
     */
    public function addFile($data, $remote_url = false)
    {
        global $wpdb;

        // Remove file guid
        $fileGuid = $data['file'];
        unset($data['file']);
        $userId = get_current_user_id();
        /**
         * Filter before upload file
         *
         * @param array   File data
         * @param integer Current user id
         *
         * @return array
         */
        $data = apply_filters('wpfd_before_upload_file', $data, $userId);

        // Revert guid to file data
        $data['file'] = $fileGuid;

        // Get the path to the upload directory.
        $wp_upload_dir = wp_upload_dir();
        if ($remote_url) {
            $filename = $data['file'];
        } else {
            $filename = $wp_upload_dir['basedir'] . '/wpfd/' . $data['id_category'] . '/' . $data['file'];
        }
        if (isset($data['file_sync']) && $data['file_sync'] === true) {
            $filename = $data['file'];
        }

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype(basename($filename), null);

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           => $filename,
            'post_type'      => 'wpfd_file',
            'post_mime_type' => $filetype['type'],
            'post_title'     => $data['title'],
            'post_content'   => '',
            'post_status'    => 'publish',
            'post_excerpt'   => (isset($data['post_excerpt'])) ? $data['post_excerpt'] : ''
        );
        $attach_id  = wp_insert_post($attachment);
        if ($attach_id) {
            // Generate the metadata for the attachment, and update the database record.
            //$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
            //wp_update_attachment_metadata( $attach_id, $attach_data );

            $metadata               = array();
            $metadata['ext']        = $data['ext'];
            $metadata['size']       = $data['size'];
            $metadata['hits']       = 0;
            $metadata['version']    = '';
            $metadata['file']       = $data['file'];
            $metadata['remote_url'] = $remote_url;
            update_post_meta($attach_id, '_wpfd_file_metadata', $metadata);

            // $wpfd_sync_category_to_ftp = get_term_meta($data['id_category'], 'wpfd_sync_category_to_ftp', true);
            // if ($wpfd_sync_category_to_ftp !== '') {
            //     $sync_data = array(
            //         'folder_ftp' => $wpfd_sync_category_to_ftp,
            //         'folder_category' => $data['id_category'],
            //         'file_id' => $attach_id
            //     );
            //     apply_filters('wpfd_sync_category_to_ftp', false, $sync_data, null);
            // }

            // WPML
            global $sitepress;
            if ($sitepress) {
                $wpml_args = array('element_id' => (int) $data['id_category'], 'element_type' => 'wpfd-category' );
                $wpfd_cat_language_code = apply_filters('wpml_element_language_code', null, $wpml_args);
                if ($wpfd_cat_language_code) {
                    $sitepress->switch_lang($wpfd_cat_language_code);
                }
            }
            wp_set_post_terms($attach_id, $data['id_category'], 'wpfd-category');
        }
        /**
         * Action fire after file uploaded
         *
         * @param integer|WP_Error The file ID on success. The value 0 or WP_Error on failure.
         * @param array            Additional information
         */
        do_action('wpfd_file_uploaded', $attach_id, $data['id_category'], array('source' => 'local'));

        return $attach_id;
    }

    /**
     * Methode to retrieve the next file ordering for a category
     *
     * @param integer $id_category Category id
     *
     * @return integer Next ordering
     */
    private function getNextPosition($id_category)
    {
        global $wpdb;
        $result = $wpdb->query(
            $wpdb->prepare(
                'SELECT ordering FROM ' . $wpdb->prefix . 'wpfd_files WHERE catid=%d ORDER BY ordering DESC LIMIT 0,1',
                (int) $id_category
            )
        );
        if ($result === false) {
            return false;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.NotPrepared -- nothing need escape
        $ordering = $wpdb->get_var(null);
        if ($ordering > 0) {
            return $ordering + 1;
        }

        return 0;
    }

    /**
     * Reorder file
     *
     * @param array $files Files
     *
     * @return boolean
     */
    public function reorder($files)
    {
        global $wpdb;
        foreach ($files as $key => $file) {
            $wpdb->update($wpdb->posts, array('menu_order' => $key), array('ID' => intval($file)));
        }

        return true;
    }

    /**
     * Search pending files
     *
     * @param array $files Files
     *
     * @return array
     */
    public function wpfdSearchPendingFiles($files = array())
    {
        if (empty($files)) {
            return array();
        }

        $result = array();
        foreach ($files as $file) {
            $fileId = isset($file->ID) ? $file->ID : 0;
            $categoryId = isset($file->catid) ? $file->catid : 0;

            if (intval($fileId) === 0 && intval($categoryId) === 0) {
                continue;
            }

            $isPending = wpfd_file_upload_pending_status($fileId, $categoryId);
            if ($isPending) {
                $result[] = $file;
            }
        }

        return $result;
    }

    /**
     * Get all file referent category
     *
     * @param array  $listCatRef  List cat ref
     * @param string $ordering    Ordering
     * @param string $orderingdir Ordering direction
     *
     * @return array
     */
    public function getAllFileRef($listCatRef, $ordering, $orderingdir)
    {
        $lstAllFile = array();
        if (is_array($listCatRef) && !empty($listCatRef)) {
            foreach ($listCatRef as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $lstFile = $this->getFiles($key, $ordering, $orderingdir);
                    foreach ($lstFile as $mutipleIndex => $multipleCategoryFile) {
                        if (isset($multipleCategoryFile->ID) && !in_array($multipleCategoryFile->ID, $value)) {
                            unset($lstFile[$mutipleIndex]);
                            continue;
                        }
                        $multipleCategoryFile->multiplefile = true;
                    }
                    $lstAllFile = array_merge($lstFile, $lstAllFile);
                }
            }
        }

        return $lstAllFile;
    }

    /**
     * Get all ref files in category
     *
     * @param string|integer $termId   Term id
     * @param string         $ordering Files ordering
     * @param string         $dir      Files direction
     * @param array          $filters  Search filters
     *
     * @throws Exception Fire if error
     *
     * @return array
     */
    public function wpfdSearchCloudMultipleCategoryFiles($termId, $ordering, $dir, $filters)
    {
        $result = array();
        if (!$termId) {
            return $result;
        }
        $lstAllFile           = null;
        $lstAllFiles          = array();
        $fromUpdateDateFilter = (!empty($filters) && isset($filters['ufrom']) && $filters['ufrom'] !== '') ? true : false;
        $toUpdateDateFilter   = (!empty($filters) && isset($filters['uto']) && $filters['uto'] !== '') ? true : false;
        $isUpdateDateFilter   = ($fromUpdateDateFilter || $toUpdateDateFilter) ? true : false;
        $fromCreateDateFilter = (isset($filters['cfrom']) && $filters['cfrom'] !== '') ? true : false;
        $toCreateDateFilter   = (isset($filters['cto']) && $filters['cto'] !== '') ? true : false;
        $isCreateDateFilter   = ($fromCreateDateFilter || $toCreateDateFilter) ? true : false;
        $term                 = get_term($termId, 'wpfd-category');
        $ordering             = $ordering ? $ordering : 'created_time';
        $dir                  = $dir ? $dir : 'asc';

        if (!is_wp_error($term)) {
            $description = json_decode($term->description, true);
            $lstAllFile  = null;
            if (!empty($description) && isset($description['refToFile'])) {
                if (isset($description['refToFile'])) {
                    $listCatRef = $description['refToFile'];
                    $lstAllFile = $this->getAllFileReferent($this, $listCatRef, $ordering, $dir);
                }
            }

            if (isset($lstAllFile) && !is_null($lstAllFile) && !empty($lstAllFile)) {
                if (!empty($filters) && isset($filters['q']) && $filters['q'] !== '') {
                    $keywords = isset($filters['q']) ? explode(',', $filters['q']) : array(0 => '');

                    // Key search in file multi categories
                    foreach ($keywords as $keyword) {
                        $key = trim($keyword);
                        foreach ($lstAllFile as $refFile) {
                            if (!isset($refFile->post_title)) {
                                continue;
                            }

                            // Update date filter
                            if ($fromUpdateDateFilter && (strtotime($refFile->modified) < strtotime($filters['ufrom']))) {
                                continue;
                            }

                            // Create date filter
                            if ($isCreateDateFilter && (strtotime($refFile->created) < strtotime($filters['cfrom']))) {
                                continue;
                            }

                            // Key file search
                            if (str_contains(strtolower($refFile->post_title), strtolower($key))) {
                                $lstAllFiles[] = $refFile;
                            }

                            // Pending file
                            if (isset($filters['waitingForApproval']) && $filters['waitingForApproval']) {
                                $isPending = apply_filters('wpfd_file_upload_pending', $refFile->ID, $refFile->catid);
                                if (!$isPending) {
                                    continue;
                                }
                            }
                        }
                    }

                    $result = $lstAllFiles;
                } elseif ($isUpdateDateFilter || $isCreateDateFilter) {
                    foreach ($lstAllFile as $findex => $multiFile) {
                        // Update date filter
                        if ($fromUpdateDateFilter && (strtotime($multiFile->modified) < strtotime($filters['ufrom']))) {
                            unset($lstAllFile[$findex]);
                        }
                        // Create date filter
                        if ($isCreateDateFilter && (strtotime($multiFile->created) < strtotime($filters['cfrom']))) {
                            unset($lstAllFile[$findex]);
                        }

                        // Pending file
                        if (isset($filters['waitingForApproval']) && $filters['waitingForApproval']) {
                            $isPending = apply_filters('wpfd_file_upload_pending', $multiFile->ID, $multiFile->catid);
                            if (!$isPending) {
                                unset($lstAllFile[$findex]);
                            }
                        }
                    }

                    $result = $lstAllFile;
                } else {
                    foreach ($lstAllFile as $findex => $multiFile) {
                        // Pending file
                        if (isset($filters['waitingForApproval']) && $filters['waitingForApproval']) {
                            $isPending = apply_filters('wpfd_file_upload_pending', $multiFile->ID, $multiFile->catid);
                            if (!$isPending) {
                                unset($lstAllFile[$findex]);
                            }
                        }
                    }
                    $result = $lstAllFile;
                }

                // Filter file type
                if (!empty($filters['ext'])) {
                    $types = explode(',', $filters['ext']);
                    $temp = array();
                    foreach ($result as $file) {
                        if (empty($file->ext) || !in_array($file->ext, $types)) {
                            continue;
                        }
                        $temp[] = $file;
                    }
                    $result = $temp;
                }

                // Filter file size
                if (!empty($filters['wfrom']) || !empty($filters['wto'])) {
                    $temp = array();
                    foreach ($result as $file) {
                        if (!empty($filters['wfrom']) && $file->size < floatval($filters['wfrom'])) {
                            continue;
                        }
                        if (!empty($filters['wto']) && $file->size > floatval($filters['wto'])) {
                            continue;
                        }

                        $temp[] = $file;
                    }
                    $result = $temp;
                }
            }
        }

        return $result;
    }

    /**
     * Get all file referent
     *
     * @param object $model       Files model
     * @param array  $listCatRef  List category
     * @param string $ordering    Ordering
     * @param string $orderingdir Ordering direction
     *
     * @return array
     */
    public function getAllFileReferent($model, $listCatRef, $ordering, $orderingdir)
    {
        $lstAllFile = array();
        foreach ($listCatRef as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $lstFile    = $model->getFilesRef($key, $value, $ordering, $orderingdir);
                $lstAllFile = array_merge($lstFile, $lstAllFile);
            }
        }

        if (!empty($lstAllFile)) {
            foreach ($lstAllFile as $refFile) {
                $refFile->multiplefile = true;
            }
        }

        return $lstAllFile;
    }

    /**
     * Get date by format
     *
     * @param string  $format  Date format
     * @param string  $dateStr Date
     * @param boolean $from    Is start date?
     *
     * @return string
     */
    public function getDate($format, $dateStr, $from = false)
    {
        $date = date_create_from_format($format, $dateStr);
        $time = $from === false ? ' 23:59:59' : ' 00:00:00';
        if ($date) {
            return $date->format('Y/m/d') . $time;
        }

        return mysql2date('Y/m/d', $dateStr) . $time;
    }

    /**
     * Search file tags
     *
     * @param array|mixed $files       File list
     * @param array|mixed $search_tags Search tags
     *
     * @return string
     */
    public function searchFileTags($files, $search_tags)
    {
        if (!empty($files) && !empty($search_tags)) {
            $searchFiles = array();
            $insertedFiles = array();

            foreach ($files as $k => $file) {
                $file_tags = explode(',', $file->file_tags);
                foreach ($file_tags as $searchTag) {
                    if (in_array($searchTag, $search_tags) && !in_array($file->ID, $insertedFiles)) {
                        $searchFiles[] = $file;
                        $insertedFiles[] = $file->ID;
                    }
                }
            }
            $files = $searchFiles;
        }

        return $files;
    }
}
