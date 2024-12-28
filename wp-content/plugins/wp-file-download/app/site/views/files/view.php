<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();

/**
 * Class WpfdViewFiles
 */
class WpfdViewFiles extends View
{

    /**
     * Display files
     *
     * @param string $tpl Template name
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $id_category   = Utilities::getInt('id');
        $root_category = Utilities::getInt('rootcat');
        $page_limit    = Utilities::getInt('page_limit');

        $app           = Application::getInstance('Wpfd');
        $modelCat      = $this->getModel('categoryfront');
        $modelFiles    = $this->getModel('filesfront');
        $modelTokens   = $this->getModel('tokens');
        $modelConfig   = $this->getModel('configfront');
        if ($id_category === 0) {
            $root = new \stdClass;
            $root->name = get_bloginfo('name');
            $root->slug = sanitize_title(get_bloginfo('name'));
            $root->term_id = 'all_0';
            $category = new WP_Term($root);
        } else {
            $category = $modelCat->getCategory($id_category);
        }
        $rootcategory  = $modelCat->getCategory($root_category);

        $path_wpfdhelper = $app->getPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers';
        $path_wpfdhelper .= DIRECTORY_SEPARATOR . 'WpfdHelper.php';
        require_once $path_wpfdhelper;
        if (!WpfdHelper::checkCategoryAccess($category)) {
            $content           = new stdClass();
            $content->files    = array();
            $content->category = new stdClass();
            echo json_encode($content);
            die();
        }

        $token       = $modelTokens->getOrCreateNew();
        $orderCol    = Utilities::getInput('orderCol', 'GET', 'none');
        $orderDir    = Utilities::getInput('orderDir', 'GET', 'none');
        $ordering    = $orderCol !== null ? $orderCol : $category->ordering;
        $orderingdir = $orderDir !== null ? $orderDir : $category->orderingdir;

        $description = json_decode($category->description, true);
        $lstAllFile  = null;
        $filePasswordList = array();
        if (!empty($description) && isset($description['refToFile'])) {
            if (isset($description['refToFile'])) {
                $listCatRef = $description['refToFile'];
                $lstAllFile = $this->getAllFileRef($modelFiles, $listCatRef, $ordering, $orderingdir);
            }
        }

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
        if ($categoryFrom === 'googleDrive') {
            $files             = apply_filters(
                'wpfdAddonGetListGoogleDriveFile',
                $id_category,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $content           = new stdClass();
            $content->files    = $files;
            $content->category = $category;
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $files             = apply_filters(
                'wpfdAddonGetListGoogleTeamDriveFile',
                $id_category,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $content           = new stdClass();
            $content->files    = $files;
            $content->category = $category;
        } elseif ($categoryFrom === 'dropbox') {
            $files             = apply_filters(
                'wpfdAddonGetListDropboxFile',
                $id_category,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $content           = new stdClass();
            $content->files    = $files;
            $content->category = $category;
        } elseif ($categoryFrom === 'onedrive') {
            $files             = apply_filters(
                'wpfdAddonGetListOneDriveFile',
                $id_category,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $content           = new stdClass();
            $content->files    = $files;
            $content->category = $category;
        } elseif ($categoryFrom === 'onedrive_business') {
            $files             = apply_filters(
                'wpfdAddonGetListOneDriveBusinessFile',
                $id_category,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $content           = new stdClass();
            $content->files    = $files;
            $content->category = $category;
        } elseif ($categoryFrom === 'aws') {
            $files             = apply_filters(
                'wpfdAddonGetListAwsFile',
                $id_category,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $content           = new stdClass();
            $content->files    = $files;
            $content->category = $category;
        } elseif ($categoryFrom === 'nextcloud') {
            $files             = apply_filters(
                'wpfdAddonGetListNextcloudFile',
                $id_category,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $content           = new stdClass();
            $content->files    = $files;
            $content->category = $category;
        } else {
            $content           = new stdClass();
            if ($id_category === 0) {
                $content->files = $modelFiles->getFilesAllCat($ordering, $orderingdir);
            } else {
                $content->files    = $modelFiles->getFiles($id_category, $ordering, $orderingdir);
            }

            $content->category = $category;
        }

        if ($lstAllFile && !empty($lstAllFile)) {
            $content->files = array_merge($lstAllFile, $content->files);
        }

        // Sort before cut
        $reverse = strtoupper($orderingdir) === 'DESC' ? true : false;
        if ($ordering === 'size') {
            $content->files = wpfd_sort_by_property($content->files, 'size', 'ID', $reverse);
        } elseif ($ordering === 'version') {
            $content->files = wpfd_sort_by_property($content->files, 'versionNumber', 'ID', $reverse);
        } elseif ($ordering === 'hits') {
            $content->files = wpfd_sort_by_property($content->files, 'hits', 'ID', $reverse);
        } elseif ($ordering === 'ext') {
            $content->files = wpfd_sort_by_property($content->files, 'ext', 'ID', $reverse);
        } elseif ($ordering === 'description') {
            $content->files = wpfd_sort_by_property($content->files, 'description', 'ID', $reverse);
        } elseif ($ordering === 'title') {
            /**
             * Filter to change priority capitalization by name
             *
             * @param boolean Turn on priority capitalization
             *
             * @return boolean
             *
             * @internal
             *
             * @ignore
             */
            $lowercaseSort = apply_filters('wpfd_filter_sort_name_priority_capitalization', true);
            if ($reverse) {
                // Descending order
                if ($lowercaseSort) {
                    usort($content->files, function ($a, $b) {
                        // String comparisons using a "natural order" algorithm
                        return strnatcmp($b->post_title, $a->post_title);
                    });
                } else {
                    usort($content->files, function ($a, $b) {
                        // String comparisons using a "natural order" algorithm
                        return strnatcmp(strtolower($b->post_title), strtolower($a->post_title));
                    });
                }
            } else {
                if ($lowercaseSort) {
                    // Ascending order
                    usort($content->files, function ($a, $b) {
                        // String comparisons using a "natural order" algorithm
                        return strnatcmp($a->post_title, $b->post_title);
                    });
                } else {
                    // Ascending order
                    usort($content->files, function ($a, $b) {
                        // String comparisons using a "natural order" algorithm
                        return strnatcmp(strtolower($a->post_title), strtolower($b->post_title));
                    });
                }
            }
        } elseif ($ordering === 'created_time') {
            usort($content->files, array($this, 'cmpCreated'));
            if ($reverse) {
                $content->files = array_reverse($content->files);
            }
        } elseif ($ordering === 'modified_time') {
            usort($content->files, array($this, 'cmpModified'));
            if ($reverse) {
                $content->files = array_reverse($content->files);
            }
        } elseif ($ordering === 'ordering') {
            if (!empty($lstAllFile)) {
                $orderingList = get_option('wpfd_custom_ordering_list', array());
                $customOrdering = isset($orderingList[$id_category]) ? (array) json_decode($orderingList[$id_category]) : array();
                $orderingFiles = array();
                $currentFiles = array();
                if (!empty($customOrdering)) {
                    foreach ($content->files as $cFile) {
                        $currentFiles[$cFile->ID] = $cFile;
                    }

                    foreach ($customOrdering as $index => $orderFile) {
                        if (array_key_exists((string) $orderFile, $currentFiles)) {
                            $orderingFiles[] = $currentFiles[$orderFile];
                        }
                    }

                    if (!empty($orderingFiles)) {
                        $content->files = $orderingFiles;
                    }
                }
            }
        }
        $show_files = Utilities::getInput('show_files', 'GET', 'none');
        if (intval($show_files) === 0 && $show_files !== null) {
            $content->files = array();
        }
        $global_settings     = $modelConfig->getGlobalConfig();
        $limit               = (isset($page_limit) && intval($page_limit) > 0) ? intval($page_limit) : intval($global_settings['paginationnunber']);
        $page                = Utilities::getInt('page');
        $useGeneratedPreview = isset($global_settings['auto_generate_preview']) && intval($global_settings['auto_generate_preview']) === 1 ? true : false;
        $previewList         = array();
        $total               = ceil(count($content->files) / $limit);
        $page                = $page ? $page : 1;
        $offset              = ($page - 1) * $limit;

        if ($offset < 0) {
            $offset = 0;
        }

        // Crop file titles
        foreach ($content->files as $i => $file) {
            if ((int) $global_settings['restrictfile'] === 1) {
                $user = wp_get_current_user();
                $user_id = $user->ID;
                $canview = isset($file->canview) ? $file->canview : 0;
                $canview = array_map('intval', explode(',', $canview));
                if ($user_id) {
                    if (!(in_array($user_id, $canview) || in_array(0, $canview))) {
                        unset($content->files[$i]);
                        continue;
                    }
                } else {
                    if (!in_array(0, $canview)) {
                        unset($content->files[$i]);
                        continue;
                    }
                }
            }
            $content->files[$i]->crop_title = $file->post_title;
            if ($root_category) {
                $content->files[$i]->crop_title = WpfdBase::cropTitle(
                    $rootcategory->params,
                    isset($rootcategory->params['theme']) ? $rootcategory->params['theme'] : '',
                    $file->post_title
                );
            } else {
                $content->files[$i]->crop_title = WpfdBase::cropTitle(
                    $category->params,
                    isset($category->params['theme']) ? $category->params['theme'] : '',
                    $file->post_title
                );
            }

            if (isset($file->file_custom_icon) && $file->file_custom_icon !== '') {
                if (strpos($file->file_custom_icon, site_url()) !== 0) {
                    $content->files[$i]->file_custom_icon = site_url() . $file->file_custom_icon;
                }
            }

            if (isset($file->state) && (int) $file->state === 0) {
                unset($content->files[$i]);
                continue;
            }

            $replaceHyphenFileTitle = apply_filters('wpfdReplaceHyphenFileTitle', false);
            if ($replaceHyphenFileTitle) {
                $content->files[$i]->crop_title = str_replace('-', ' ', $content->files[$i]->crop_title);
                $content->files[$i]->post_title = str_replace('-', ' ', $content->files[$i]->post_title);
            }

            // File password protection
            if (wpfdPasswordRequired($file, 'file')) {
                $fileTitle = isset($file->post_title) ? $file->post_title : '';
                $passwordFormProtection = '<h3 class="protected-title" title="' . $fileTitle . '">' . esc_html__('Protected: ', 'wpfd') . $fileTitle . '</h3>';
                $passwordFormProtection .= wpfdGetPasswordForm($file, 'file', $file->catid);
                $filePasswordList[$file->ID] = $passwordFormProtection;
            }

            // File preview
            $link = '';
            $imgExists = false;
            $viewImageFilePath = '';
            $type = 'default';
            $containClass = '';
            $viewClass = 'wpfd-view-default';
            $customPreviewPath = '';
            if (is_numeric($file->ID)) {
                $viewImageFilePath = get_post_meta($file->ID, '_wpfd_thumbnail_image_file_path', true);
                $metaData = get_post_meta($file->ID, '_wpfd_file_metadata', true);
                $customPreviewPath = isset($metaData['file_custom_icon_preview']) ? $metaData['file_custom_icon_preview'] : '';
            } else {
                $fileId = ($categoryFrom === 'onedrive') ? str_replace('-', '!', $file->ID) : $file->ID;
                $previewFileInfo = get_option('_wpfdAddon_preview_info_' . md5($fileId), false);
                $previewFilePath = is_array($previewFileInfo) && isset($previewFileInfo['path']) ? $previewFileInfo['path'] : false;
                $previewIcon = get_option('_wpfdAddon_file_custom_icon_preview_' . md5($file->ID), false);
                $customPreviewPath = (isset($previewIcon) && !is_null($previewIcon) && $previewIcon !== false) ? $previewIcon : '';
            }

            if ($useGeneratedPreview && isset($previewFilePath) && $previewFilePath && file_exists(WP_CONTENT_DIR . $previewFilePath)) {
                $imgExists = true;
                $link = WP_CONTENT_URL . $previewFilePath;
                $viewClass = 'wpfd-view-thumbnail';
            } elseif ($useGeneratedPreview && $viewImageFilePath && file_exists(WP_CONTENT_DIR . $viewImageFilePath)) {
                $viewImageClass = get_post_meta($file->ID, '_wpfd_thumbnail_image_file_contain_class', true);
                $link = WP_CONTENT_URL . $viewImageFilePath;
                $imgExists = true;
                $viewClass = 'wpfd-view-image-thumbnail';
                if ($viewImageClass) {
                    $containClass = 'contain';
                }
            } elseif ($useGeneratedPreview && !is_null($customPreviewPath) && !empty($customPreviewPath) && file_exists(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $customPreviewPath)) {
                $viewImageClass = get_post_meta($file->ID, '_wpfd_thumbnail_image_file_contain_class', true);
                $link = WP_CONTENT_URL . DIRECTORY_SEPARATOR . $customPreviewPath;
                $imgExists = true;
                $viewClass = 'wpfd-view-image-thumbnail';
                if ($viewImageClass) {
                    $containClass = 'contain';
                }
            }

            $categoryId = $file->catid;
            $id = isset($file->id) ? $file->id : $file->ID;
            $wmCategoryEnabled = false;
            $lists = get_option('wpfd_watermark_category_listing');
            if (is_array($lists) && !empty($lists)) {
                if (in_array($categoryId, $lists)) {
                    $wmCategoryEnabled = true;
                }
            }
            if ($wmCategoryEnabled) {
                if (is_numeric($id)) {
                    $viewWmInfo = get_option('_wpfdAddon_preview_wm_info_' . $id, false);
                } else {
                    $viewWmInfo = get_option('_wpfdAddon_preview_wm_info_' . md5($id), false);
                }
                if ($viewWmInfo !== false) {
                    $viewFileWmPath = is_array($viewWmInfo) && isset($viewWmInfo['path']) ? $viewWmInfo['path'] : false;
                    if (isset($viewFileWmPath) && $viewFileWmPath && file_exists(WP_CONTENT_DIR . $viewFileWmPath)) {
                        $link  = WP_CONTENT_URL . $viewFileWmPath;
                        $imgExists = true;
                        $viewClass = 'wpfd-view-image-thumbnail';
                    }
                }
            }

            $file->is_preview_image = $imgExists ? true : false;
            $viewClass = $viewClass . ' ' . $containClass;
            $previewFile = array('id' => $file->ID, 'view' => $imgExists, 'link' => $link, 'view_class' => $viewClass);
            $previewList[] = $previewFile;
        }

        if (!$rootcategory || (isset($rootcategory->params['theme']) && $rootcategory->params['theme'] !== 'tree')) {
            $content->files = array_slice($content->files, $offset, $limit);
        }


        $content->pagination = wpfd_category_pagination(
            array(
                'base'    => '',
                'format'  => '',
                'current' => max(1, $page),
                'total'   => $total,
                'sourcecat' => $root_category
            )
        );

        if (wpfd_can_edit_category() || wpfd_can_edit_own_category() || wpfd_can_upload_files()) {
            $prefix = isset($content->category->params['theme']) ? $content->category->params['theme'] . '_' : '_';
            if (isset($content->category->params['theme']) && $content->category->params['theme'] === 'default') {
                $prefix = '';
            }
            $showUploadForm    = wpfdShowUploadForm($content->category);
            /**
             * Filter to change the upload form
             *
             * @param boolean
             */
            $reverseUploadForm = apply_filters('wpfd_show_upload_form_reverse', false);

            if ($reverseUploadForm) {
                if (!$showUploadForm) {
                    $content->uploadform = do_shortcode('[wpfd_upload category_id="'. $content->category->term_id .'"]');
                }
            } else {
                if ($showUploadForm) {
                    $content->uploadform = do_shortcode('[wpfd_upload category_id="'. $content->category->term_id .'"]');
                }
            }
        }

        if (wpfdPasswordRequired($category, 'category')) {
            $categoryName = isset($category->name) ? $category->name : '';
            $categoryPwf  = '<div class="wpfd-category-password-protection-container"><h3 class="protected-title" title="' . $categoryName . '">' . esc_html__('Protected: ', 'wpfd') . $categoryName . '</h3>';
            $categoryPwf .= wpfdGetPasswordForm($category, 'category');
            $categoryPwf .= '</div>';
            $content->categoryPassword = $categoryPwf;
        }

        if (!empty($filePasswordList)) {
            $content->filepasswords = $filePasswordList;
        }

        if (!empty($previewList)) {
            $content->fileview = $previewList;
        }

        echo wp_json_encode($content);
        die();
    }

    /**
     * Get all file referent category
     *
     * @param object $model       Files Model
     * @param array  $listCatRef  List cat ref
     * @param string $ordering    Ordering
     * @param string $orderingdir Ordering direction
     *
     * @return array
     */
    public function getAllFileRef($model, $listCatRef, $ordering, $orderingdir)
    {
        $lstAllFile = array();
        if (is_array($listCatRef) && !empty($listCatRef)) {
            foreach ($listCatRef as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $lstFile    = $model->getFiles($key, $ordering, $orderingdir, $value);
                    $lstAllFile = array_merge($lstFile, $lstAllFile);
                }
            }
        }

        return $lstAllFile;
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
        return (strtotime($a->created_time) <= strtotime($b->created_time)) ? -1 : 1;
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
        return (strtotime($a->created_time) >= strtotime($b->created_time)) ? -1 : 1;
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
}
