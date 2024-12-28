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
 * Class WpfdModelFilesfront
 */
class WpfdModelFilesfront extends Model
{

    /**
     * Get files by ordering
     *
     * @param integer $category     Category id
     * @param string  $ordering     Ordering
     * @param string  $ordering_dir Ordering direction
     * @param array   $listIdFiles  List id files
     * @param integer $refCatId     Ref cat id
     *
     * @return array
     */
    public function getFiles($category, $ordering = 'menu_order', $ordering_dir = 'ASC', $listIdFiles = array(), $refCatId = null)
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $category);
        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            /**
             * Filters to get files from cloud
             *
             * @param integer Category id
             * @param array   List file id
             *
             * @internal
             *
             * @ignore
             *
             * @return array
             */
            $files = apply_filters('wpfd_addon_get_files', $category, $categoryFrom, $listIdFiles);
        } else {
            Application::getInstance('Wpfd');
            $modelCat    = $this->getInstance('categoryfront');
            $modelConfig = $this->getInstance('configfront');
            $modelTokens = $this->getInstance('tokens');

            $categorys   = $modelCat->getCategory($category);
            $params      = $modelConfig->getGlobalConfig();
            $user        = wp_get_current_user();
            $roles       = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $rmdownloadext = (int) WpfdBase::loadValue($params, 'rmdownloadext', 1) === 1;

            $token = $modelTokens->getOrCreateNew();
            if ($ordering === 'ordering') {
                $ordering = 'menu_order';
            } elseif ($ordering === 'created_time') {
                $ordering = 'date';
            } elseif ($ordering === 'modified_time') {
                $ordering = 'modified';
            }

            // WPML
            global $sitepress;
            $wpfd_current_cat_language_code = '';
            if ($sitepress) {
                $wpml_args = array('element_id' => (int) $category, 'element_type' => 'wpfd-category' );
                $wpfd_cat_language_code = apply_filters('wpml_element_language_code', null, $wpml_args);
                if ($wpfd_cat_language_code) {
                    $sitepress->switch_lang($wpfd_cat_language_code);
                    $wpfd_current_cat_language_code = $wpfd_cat_language_code;
                }
            }

            $args    = array(
                'posts_per_page'   => -1,
                'post_type'        => 'wpfd_file',
                'orderby'          => $ordering,
                'order'            => $ordering_dir,
                'tax_query'        => array(
                    array(
                        'taxonomy'         => 'wpfd-category',
                        'terms'            => (int) $category,
                        'include_children' => false
                    )
                )
            );
            // Fix conflict plugin Go7 Pricing Table
            remove_all_filters('posts_fields');
            // remove_all_filters('pre_get_posts');
            $results = get_posts($args);
            $files   = array();

            $viewer_type           = WpfdBase::loadValue($params, 'use_google_viewer', 'lightbox');
            $extension_viewer_list = 'png,jpg,jpeg,gif,pdf,ppt,pptx,doc,docx,xls,xlsx,dxf,ps,eps,xps,psd,tif,tiff,bmp,svg,pages,ai,dxf,ttf,txt,mp3,mp4';
            $images_extension_viewer_list = array('png', 'jpg', 'jpeg', 'gif');
            $extension_viewer      = explode(',', WpfdBase::loadValue($params, 'extension_viewer', $extension_viewer_list));
            $extension_viewer      = array_map('trim', $extension_viewer);
            $user                  = wp_get_current_user();
            $user_id               = $user->ID;
            $site_url              = get_option('siteurl'); // Fix wpml hook to siteurl
            $home_url              = get_option('home');

            // Reduce query
            $ids = array();
            foreach ($results as $result) {
                if (!empty($listIdFiles) && is_array($listIdFiles)) {
                    if (!in_array($result->ID, $listIdFiles)) {
                        continue;
                    }
                }
                $ids[] = intval($result->ID);
            }
            $termLists = array();
            if (count($ids) > 0) {
                global $wpdb;
                $termListsQuery = 'SELECT tr.object_id AS ID, t.term_id, t.name, tt.parent, tt.count, tt.taxonomy
                                FROM ' . $wpdb->terms . ' AS t
                                INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
                                ON t.term_id = tt.term_id
                                INNER JOIN ' . $wpdb->term_relationships . ' AS tr
                                ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                WHERE tt.taxonomy IN (\'wpfd-category\')
                                AND tr.object_id IN (' . implode(',', $ids) . ')
                                ORDER BY t.name ASC';

                $termLists = $wpdb->get_results($termListsQuery, OBJECT_K);
            }
            foreach ($results as $result) {
                if (!empty($listIdFiles) && is_array($listIdFiles)) {
                    if (!in_array($result->ID, $listIdFiles)) {
                        continue;
                    }
                }
                $ob             = new stdClass();
                $metaData       = get_post_meta($result->ID, '_wpfd_file_metadata', true);
                $productLinked  = get_post_meta($result->ID, '_wpfd_products_linked', true);
                if ((WpfdHelperFile::wpfdIsExpired((int)$result->ID) === true && $productLinked === '') ||
                    (WpfdHelperFile::wpfdIsExpired((int)$result->ID) === true && is_array($metaData)
                        && isset($metaData['woo_permission']) && $metaData['woo_permission'] === 'both_woo_and_wpfd_permission')) {
                    continue;
                }
                if ((int) WpfdBase::loadValue($params, 'restrictfile', 0) === 1) {
                    $canview = isset($metaData['canview']) ? $metaData['canview'] : 0;
                    $canview = array_map('intval', explode(',', $canview));
                    if (!in_array($user_id, $canview) && !in_array(0, $canview)) {
                        continue;
                    }
                }
                if (isset($metaData) && isset($metaData['remote_url'])) {
                    $remote_url = $metaData['remote_url'];
                } else {
                    $remote_url = false;
                }

                if (!empty($metaData) && isset($metaData['wpfd_sync_ftp_file']) && (bool) $metaData['wpfd_sync_ftp_file'] === true) {
                    $ftpFile = true;
                } else {
                    $ftpFile = false;
                }

                $ob->ID            = $result->ID;
                /**
                 * Filter to change file title
                 *
                 * @param string  File title
                 * @param integer File id
                 *
                 * @return string
                 *
                 * @ignore
                 */
                $ob->post_title    = apply_filters('wpfd_file_title', $result->post_title, $result->ID);
                $ob->post_name     = $result->post_name;
                $ob->ext           = isset($metaData['ext']) ? $metaData['ext'] : '';
                $ob->hits          = isset($metaData['hits']) ? (int) $metaData['hits'] : 0;
                $ob->versionNumber = isset($metaData['version']) ? $metaData['version'] : '';
                $ob->version       = '';
                $ob->description   = $result->post_excerpt;
                $ob->size          = isset($metaData['size']) ? $metaData['size'] : 0;
                $ob->created_time     = get_date_from_gmt($result->post_date_gmt);
                $ob->modified_time    = get_date_from_gmt($result->post_modified_gmt);
                $ob->created       = mysql2date(
                    WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                    get_date_from_gmt($result->post_date_gmt)
                );
                $ob->modified      = mysql2date(
                    WpfdBase::loadValue($params, 'date_format', get_option('date_format')),
                    get_date_from_gmt($result->post_modified_gmt)
                );
                if (isset($termLists[$result->ID])) {
                    $wpfd_term = $termLists[$result->ID];
                    $ob->catname          = sanitize_title($wpfd_term->name);
                    $ob->cattitle         = $wpfd_term->name;
                    if (!is_null($refCatId)) {
                        $ob->catid = $refCatId;
                    } else {
                        $ob->catid = $wpfd_term->term_id;
                    }
                } else {
                    $ob->catname = '---';
                    $ob->cattitle = '---';
                    $ob->catid = 0;
                }

                $ob->file_custom_icon = isset($metaData['file_custom_icon']) && !empty($metaData['file_custom_icon']) ?
                    $site_url . $metaData['file_custom_icon'] : '';

                if ($viewer_type !== 'no' &&
                    in_array(strtolower($ob->ext), $extension_viewer)
                    && ($remote_url === false || $ftpFile === true)) {
                    $ob->viewer_type = $viewer_type;
                    if (in_array(strtolower($ob->ext), $images_extension_viewer_list)) {
                        $ob->viewerlink  = WpfdHelperFile::getViewerUrl($result->ID, $ob->catid, $token);
                        $lists = get_option('wpfd_watermark_category_listing');
                        if (is_array($lists) && !empty($lists) && intval($ob->catid) !== 0) {
                            if (in_array($ob->catid, $lists)) {
                                if (!class_exists('WpfdHelperFolder')) {
                                    require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelperFolder.php';
                                }

                                $filePath = WpfdBase::getFilesPath($ob->catid) . $metaData['file'];
                                $watermarkedPath = WpfdHelperFolder::getCategoryWatermarkPath();
                                $watermarkedPath = $watermarkedPath . strval($ob->catid) . '_' . strval($result->ID) . '_' . strval(md5($filePath)) . '.png';
                                if (file_exists($watermarkedPath)) {
                                    $ob->viewerlink = wpfd_abs_path_to_url($watermarkedPath);
                                }
                            }
                        }
                    } else {
                        $ob->viewerlink  = WpfdHelperFile::isMediaFile($ob->ext) ?
                            WpfdHelperFile::getMediaViewerUrl(
                                $result->ID,
                                $ob->catid,
                                $ob->ext,
                                $token
                            ) : WpfdHelperFile::getViewerUrl($result->ID, $ob->catid, $token);
                    }
                }

                $open_pdf_in = WpfdBase::loadValue($params, 'open_pdf_in', 0);

                if ((int) $open_pdf_in === 1 && strtolower($ob->ext) === 'pdf') {
                    $ob->openpdflink = WpfdHelperFile::getPdfUrl($result->ID, $ob->catid, $token) . '&preview=1';
                }

                $config = get_option('_wpfd_global_config');
                if (empty($config) || empty($config['uri'])) {
                    $seo_uri = 'download';
                } else {
                    $seo_uri = rawurlencode($config['uri']);
                }
                $ob->seouri    = $seo_uri;
                $perlink       = get_option('permalink_structure');
                $rewrite_rules = get_option('rewrite_rules');

                list ($currentFileId, $currentCatId, $wpfdLang) = wpfd_correct_wpml_language($result->ID, $ob->catid);

                if (wpfd_can_download_files()) {
                    global $wp;
                    $current_url = home_url($wp->request);
                    $check_wpml_dl = false;
                    $lang_code = '';
                    if (strpos($current_url, '?lang=')) {
                        if (defined('ICL_LANGUAGE_CODE')) {
                            $check_wpml_dl = true;
                            $lang_code = ICL_LANGUAGE_CODE;
                        }
                    }

                    if (!empty($rewrite_rules)) {
                        if (strpos($perlink, 'index.php')) {
                            if ($check_wpml_dl) {
                                $linkdownload     = untrailingslashit($site_url) . '/index.php/' . $seo_uri . '/' . $currentCatId;
                            } else {
                                $linkdownload     = untrailingslashit($site_url) . $wpfdLang . '/index.php/' . $seo_uri . '/' . $currentCatId;
                            }
                            $linkdownload     .= '/' . $ob->catname . '/' . $currentFileId . '/' . $result->post_name;
                            $ob->linkdownload = $linkdownload;
                        } else {
                            if ($check_wpml_dl) {
                                $linkdownload     = untrailingslashit($site_url) . '/' . $seo_uri . '/' . $currentCatId . '/' . $ob->catname;
                            } else {
                                $linkdownload     = untrailingslashit($site_url) . $wpfdLang . '/' . $seo_uri . '/' . $currentCatId . '/' . $ob->catname;
                            }
                            $linkdownload     .= '/' . $currentFileId . '/' . $result->post_name;
                            $ob->linkdownload = $linkdownload;
                        }
                        $rewrite_download_link = apply_filters('wpfd_rewrite_download_link', false);
                        if ($rewrite_download_link) {
                            $ob->linkdownload = str_replace(home_url(), home_url().'/index.php', $ob->linkdownload);
                        }
                        if ($ob->ext && !$rmdownloadext) {
                            $ob->linkdownload .= '.' . $ob->ext;
                        }
                        if ($check_wpml_dl) {
                            $ob->linkdownload .= '?lang='.$lang_code;
                        }
                    } else {
                        $linkdownload     = admin_url('admin-ajax.php') . '?juwpfisadmin=false&action=wpfd&task=file.download';
                        $linkdownload     .= '&wpfd_category_id=' . $currentCatId . '&wpfd_file_id=' . $currentFileId;
                        $ob->linkdownload = $linkdownload;
                    }
                } else {
                    $ob->linkdownload = '';
                }

                // Crop file titles
                $ob->crop_title = WpfdBase::cropTitle($categorys->params, $categorys->params['theme'], $result->post_title);
                $ob->remote_file = (isset($metaData['remote_url']) && (int) $metaData['remote_url'] === 1) ? true : false;
                /**
                 * Filter to change file title
                 *
                 * @param string  File title
                 * @param integer File id
                 *
                 * @return string
                 *
                 * @ignore
                 */
                $ob->crop_title = apply_filters('wpfd_file_title', $ob->crop_title, $result->ID);

                /**
                 * Filter file info in front
                 *
                 * @param object File object
                 *
                 * @return object
                 *
                 * @ignore
                 */
                $ob = apply_filters('wpfd_file_info', $ob);

                if ($sitepress) {
                    $wpfdFileLang = str_replace('/', '', $wpfdLang);
                    $default_lang = $sitepress->get_default_language();
                    if ($default_lang === $wpfd_current_cat_language_code) {
                        $wpfdFileLang = $wpfd_current_cat_language_code;
                    }
                    if ($wpfd_current_cat_language_code === $wpfdFileLang) {
                        $files[] = $ob;
                    }
                } else {
                    $files[] = $ob;
                }
            }
            wp_reset_postdata();
        }
        /**
         * Filter files info in front
         *
         * @param array Files object
         *
         * @return object
         *
         * @ignore
         */
        $files = apply_filters('wpfd_files_info', $files);
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
        }

        return $files;
    }

    /**
     * Get files all categories
     *
     * @param string $ordering     Ordering
     * @param string $ordering_dir Ordering direction
     *
     * @return array
     */
    public function getFilesAllCat($ordering = 'menu_order', $ordering_dir = 'ASC')
    {
        Application::getInstance('Wpfd');
        /* @var WpfdModelCategories $categoriesModel */
        $categoriesModel = self::getInstance('categoriesfront');

        $categories = $categoriesModel->getLevelCategories();
        $modelTokens = Model::getInstance('tokens');

        $token = $modelTokens->getOrCreateNew();
        $files = array();
        // Check access and get files
        foreach ($categories as $category) {
            if (WpfdHelper::checkCategoryAccess($category)) {
                $term_id = isset($category->wp_term_id) ? $category->wp_term_id : $category->term_id;
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
                $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $term_id);
                if ($categoryFrom === 'googleDrive') {
                    $google_files = apply_filters(
                        'wpfdAddonGetListGoogleDriveFile',
                        $term_id,
                        $ordering,
                        $ordering_dir,
                        $category->slug,
                        $token
                    );

                    if (is_array($google_files)) {
                        $files = array_merge($files, $google_files);
                    }
                } elseif ($categoryFrom === 'googleTeamDrive') {
                    $google_team_files = apply_filters(
                        'wpfdAddonGetListGoogleTeamDriveFile',
                        $term_id,
                        $ordering,
                        $ordering_dir,
                        $category->slug,
                        $token
                    );

                    if (is_array($google_team_files)) {
                        $files = array_merge($files, $google_team_files);
                    }
                } elseif ($categoryFrom === 'dropbox') {
                    $dropbox_files = apply_filters(
                        'wpfdAddonGetListDropboxFile',
                        $term_id,
                        $ordering,
                        $ordering_dir,
                        $category->slug,
                        $token
                    );
                    if (is_array($dropbox_files)) {
                        $files = array_merge($files, $dropbox_files);
                    }
                } elseif ($categoryFrom === 'onedrive') {
                    $onedrive_files = apply_filters(
                        'wpfdAddonGetListOneDriveFile',
                        $term_id,
                        $ordering,
                        $ordering_dir,
                        $category->slug,
                        $token
                    );
                    if (is_array($onedrive_files)) {
                        $files = array_merge($files, $onedrive_files);
                    }
                } elseif ($categoryFrom === 'onedrive_business') {
                    $onedrive_business_files = apply_filters(
                        'wpfdAddonGetListOneDriveBusinessFile',
                        $term_id,
                        $ordering,
                        $ordering_dir,
                        $category->slug,
                        $token
                    );
                    if (is_array($onedrive_business_files)) {
                        $files = array_merge($files, $onedrive_business_files);
                    }
                } elseif ($categoryFrom === 'nextcloud') {
                    $nextcloud_files = apply_filters(
                        'wpfdAddonGetListNextcloudFile',
                        $term_id,
                        $ordering,
                        $ordering_dir,
                        $category->slug,
                        $token
                    );
                    if (is_array($nextcloud_files)) {
                        $files = array_merge($files, $nextcloud_files);
                    }
                } else {
                    // Get files
                    $categoryFiles = $this->getFiles($category->term_id, $ordering, $ordering_dir);
                    $files = array_merge($files, $categoryFiles);
                }
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
        }

        return $files;
    }
}
