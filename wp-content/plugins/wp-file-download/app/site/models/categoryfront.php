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
 * Class WpfdModelCategoryfront
 */
class WpfdModelCategoryfront extends Model
{

    /**
     * Get a category info
     *
     * @param integer $id     Category id
     * @param integer $rootId Root category id
     *
     * @return boolean
     */
    public function getCategory($id, $rootId = 0)
    {
        $result = get_term($id, 'wpfd-category');
        Application::getInstance('Wpfd', __FILE__);
        $modelConfig = $this->getInstance('configfront');
        $main_config = $modelConfig->getGlobalConfig();
        $themeSettings = (int)$main_config['themesettings'] === 1 ? true : false;
        $globalFileOrdering = isset($main_config['global_files_ordering']) ? $main_config['global_files_ordering'] : 'title';
        $globalFileOrderingAll = (isset($main_config['global_files_ordering_all']) && intval($main_config['global_files_ordering_all']) === 1) ? true : false;
        $globalFileOrderingDirection = isset($main_config['global_files_ordering_direction']) ? $main_config['global_files_ordering_direction'] : 'desc';
        $globalFileOrderingDirectionAll = (isset($main_config['global_files_ordering_direction_all']) && intval($main_config['global_files_ordering_direction_all']) === 1) ? true : false;
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $id);

        if (!empty($result) && !is_wp_error($result)) {
            $result->name = html_entity_decode($result->name);
            if ($result->description === 'null' || $result->description === '') {
                $result->params = array();
            } else {
                $result->params = json_decode($result->description, true);
            }

            if (!isset($result->params['theme'])) {
                $result->params['theme'] = $main_config['defaultthemepercategory'];
            }

            if (!isset($result->params['category_own'])) {
                $categoryOwn = get_current_user_id();
                $result->params['category_own'] = $categoryOwn;
            } else {
                $categoryOwn = $result->params['category_own'];
            }

            $globalFilesOrdering = isset($main_config['global_files_ordering']) ? $main_config['global_files_ordering'] : 'title';
            $globalFilesOrderingDirection = isset($main_config['global_files_ordering_direction']) ? $main_config['global_files_ordering_direction'] : 'desc';
            $globalSubcategoriesOrdering = isset($main_config['global_subcategories_ordering']) ? $main_config['global_subcategories_ordering'] : 'customorder';
            $defaultParams = array(
                'order' => $globalFilesOrderingDirection,
                'orderby' => $globalFilesOrdering,
                'subcategoriesorderby' => $globalSubcategoriesOrdering
            );
            /**
             * Filters allow setup default params for new category
             *
             * @param array Default values: order, orderby
             *
             * @return array
             * @ignore
             */
            $defaultParams = apply_filters('wpfd_default_category_params', $defaultParams);
            $ordering = isset($result->params['ordering']) ? $result->params['ordering'] : $defaultParams['orderby'];
            $orderingdir = isset($result->params['orderingdir']) ? $result->params['orderingdir'] : $defaultParams['order'];
            $subcategoriesOrdering = isset($result->params['subcategoriesordering']) ? $result->params['subcategoriesordering'] : $defaultParams['subcategoriesorderby'];
            $globalSubcategoriesOrderingAll = (isset($main_config['global_subcategories_ordering_all']) && intval($main_config['global_subcategories_ordering_all']) === 1) ? true : false;
            $defaultGlobalSubcategoriesOrdering = array('customorder', 'nameascending', 'namedescending');

            if ($globalSubcategoriesOrderingAll && in_array($globalSubcategoriesOrdering, $defaultGlobalSubcategoriesOrdering)) {
                $subcategoriesOrdering = $globalSubcategoriesOrdering;
            }

            // Apply global ordering for files
            if ($globalFileOrderingAll) {
                $ordering = $globalFileOrdering;
            }

            // Apply global ordering direction for files
            if ($globalFileOrderingDirectionAll) {
                $orderingdir = $globalFileOrderingDirection;
            }

            if ((int)$main_config['catparameters'] === 0) {
                $defaultSettings = true;
                if ($themeSettings && isset($result->params['theme'])
                    && $result->params['theme'] === $main_config['defaultthemepercategory']) {
                    $defaultSettings = false;
                }
                if ($defaultSettings) {
                    $result->params = array_merge(
                        $result->params,
                        $modelConfig->getConfig($main_config['defaultthemepercategory'])
                    );
                }
                $result->params['theme'] = $main_config['defaultthemepercategory'];
                $result->params['category_own'] = $categoryOwn;
            } elseif ((int)$main_config['catparameters'] === 1) {
                if ($result->description === 'null' || $result->description === '') {
                    $result->params = array_merge(
                        $result->params,
                        $modelConfig->getConfig($main_config['defaultthemepercategory'])
                    );
                }
            }
            if (!empty($result->parent)) {
                $parentCat = get_term($result->parent, 'wpfd-category');
                if (!empty($parentCat) && !is_wp_error($parentCat)) {
                    $result->parent_title = $parentCat->name;
                }
            }
            $categoryVisibility = wpfd_get_category_visibility($id);
            $result->roles = isset($categoryVisibility['roles']) ? $categoryVisibility['roles'] : array();
            $result->access = isset($categoryVisibility['access']) ? $categoryVisibility['access'] : 0;
            $result->ordering = $ordering;
            $result->orderingdir = $orderingdir;
            $result->subcategoriesordering = $subcategoriesOrdering;
            $result->linkdownload_cat = $this->urlBtnDownloadCat($result->term_id, sanitize_title($result->name));
            $showbreadcrumb = (isset($result->params['showbreadcrumb']) && intval($result->params['showbreadcrumb']) === 1) ? true : false;
            if ($result->term_id !== $rootId && $showbreadcrumb) {
                $result->breadcrumbs = $this->generateBreadcrumb($result->term_id, $rootId) . '<li><span>' . $result->name . '</span></li>';
            }
            $result->color = get_term_meta($result->term_id, '_wpfd_color', true);
            $result->desc = get_term_meta($result->term_id, '_wpfd_description', true);
            $correctConvertCategoryId = $id;
            if ($categoryFrom === 'googleDrive' && has_filter('wpfdAddonSearchCloud', 'wpfdAddonSearchCloud')) {
                $correctConvertCategoryId = WpfdAddonHelper::getGoogleDriveIdByTermId($id);
            } elseif ($categoryFrom === 'dropbox' && has_filter('wpfdAddonSearchDropbox', 'wpfdAddonSearchDropbox')) {
                $correctConvertCategoryId = WpfdAddonHelper::getDropboxIdByTermId($id);
            } elseif ($categoryFrom === 'onedrive' && has_filter('wpfdAddonSearchOneDrive', 'wpfdAddonSearchOneDrive')) {
                $correctConvertCategoryId = WpfdAddonHelper::getOneDriveIdByTermId($id);
            } elseif ($categoryFrom === 'onedrive_business' && has_filter('wpfdAddonSearchOneDriveBusiness', 'wpfdAddonSearchOneDriveBusiness')) {
                $correctConvertCategoryId = WpfdAddonHelper::getOneDriveBusinessIdByTermId($id);
            } elseif ($categoryFrom === 'aws' && has_filter('wpfdAddonSearchAws', 'wpfdAddonSearchAws')) {
                $correctConvertCategoryId = WpfdAddonHelper::getAwsIdByTermId($id);
            }
            $result->correctConvertCategoryId = $correctConvertCategoryId;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Generate breadcrumb
     *
     * @param integer $catId  Category Id
     * @param integer $rootId Root category id
     *
     * @return string
     */
    public function generateBreadcrumb($catId, $rootId)
    {
        $output = '';
        $term = get_term_by('id', $catId, 'wpfd-category');

        // Create a list of all the term's parents
        $parent = $term->parent;
        while ($parent) {
            $parents[] = $parent;
            if ($parent === $rootId) {
                break;
            }
            $new_parent = get_term_by('id', $parent, 'wpfd-category');
            $parent = $new_parent->parent;
        }

        if (!empty($parents)) {
            $parents = array_reverse($parents);

            // For each parent, create a breadcrumb item
            foreach ($parents as $parent) {
                $item = get_term_by('id', $parent, 'wpfd-category');
                $output .= '<li><a class="catlink" data-idcat="' . $item->term_id . '" href="javascript:void(0);">' . $item->name . '</a><span class="divider"> &gt; </span></li>';
            }
        }

        return $output;
    }

    /**
     * Get url download cat
     *
     * @param integer $catid   Category id
     * @param string  $catname Category name
     *
     * @return string
     */
    public function urlBtnDownloadCat($catid, $catname)
    {
        $perlink = get_option('permalink_structure');
        $config = get_option('_wpfd_global_config');
        $rewrite_rules = get_option('rewrite_rules');

        if (empty($config) || empty($config['uri'])) {
            $seo_uri = 'download/wpfdcat';
        } else {
            $seo_uri = rawurlencode($config['uri']) . '/wpfdcat';
        }

        if (!empty($rewrite_rules)) {
            if (strpos($perlink, 'index.php')) {
                $linkdownloadCat = get_site_url() . '/index.php/' . $seo_uri . '/' . $catid . '/' . $catname;
            } else {
                $linkdownloadCat = get_site_url() . '/' . $seo_uri . '/' . $catid . '/' . $catname;
            }
        } else {
            $linkdownloadCat = admin_url('admin-ajax.php') . '?juwpfisadmin=false&action=wpfd&task=files.download';
            $linkdownloadCat .= '&wpfd_category_id=' . $catid . '&wpfd_cat_name=' . $catname;
        }

        return $linkdownloadCat;
    }
}
