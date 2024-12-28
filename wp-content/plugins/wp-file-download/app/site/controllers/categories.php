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

defined('ABSPATH') || die();

/**
 * Class WpfdControllerCategories
 */
class WpfdControllerCategories extends Controller
{
    /**
     * Get subs categories
     *
     * @return void
     */
    public function getSubs()
    {

        $modelCats = $this->getModel('categoriesfront');
        $cats      = $modelCats->getCategories(Utilities::getInt('dir'));
        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
        if (is_countable($cats) && count($cats)) {
            foreach ($cats as $cat) {
                $cat->count_child = $modelCats->getSubCategoriesCount($cat->term_id);
            }
        }
        echo json_encode($cats);
        die();
    }

    /**
     * Get Categories
     *
     * @return void
     */
    public function getCats()
    {
        $term = array();
        $catId = Utilities::getInt('dir');
        $catModel = $this->getModel('categoriesfront');
        $configModel = $this->getModel('configfront');
        if ($catId === 0) {
            $currentCat = new stdClass;
            $currentCat->term_id = 0;
            $currentCat->slug = 'all_0';
            $currentCat->name = esc_html__('All Categories', 'wpfd');
        } else {
            $currentCat = get_term($catId, 'wpfd-category');
        }
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catId);
        $currentCat->cloudType = $categoryFrom;
        $currentCat->children = array();
        $currentCat->color = intval($catId) !== 0 ? get_term_meta($catId, '_wpfd_color', true) : '';
        $config = $configModel->getGlobalConfig();

        /**
         * Filters allow to change ordering direction of categories
         *
         * @param string
         *
         * @return string
         */
        $orderDirection = apply_filters('wpfd_categories_order', 'asc');

        /**
         * Filters allow to change order column of categories
         *
         * @param string
         *
         * @return string
         */
        $orderBy = apply_filters('wpfd_categories_orderby', 'term_group');

        if (!is_wp_error($currentCat)) {
            $hierarchy = $catModel->getCategoriesHierarchy($catId, $orderBy, $orderDirection, $config);
            // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
            if (is_countable($hierarchy) && ($hierarchy)) {
                $currentCat->children = $hierarchy;
            }
            $term[] = $currentCat;
        }

        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
        if (is_countable($term) && count($term) > 0) {
            wp_send_json(array(
                'success' => true,
                'data'    => $term
            ));
        } else {
            wp_send_json(array(
                'success' => false,
                'message' => esc_html__('No category found', 'wpfd')
            ));
        }
    }

    /**
     * Get parents categories
     *
     * @return void
     */
    public function getParentsCats()
    {
        $modelCats = $this->getModel('categoriesfront');
        $cats      = $modelCats->getParentsCat(Utilities::getInt('id'), Utilities::getInt('displaycatid'));
        $cats      = array_reverse($cats);
        echo json_encode($cats);
        die();
    }

    /**
     * Get all file in all categories
     *
     * @return string
     */
    public function contentAllCat()
    {
        $app                  = Application::getInstance('Wpfd');
        $path_helper          = $app->getPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'WpfdHelperShortcodes.php';
        require_once $path_helper;
        $helper               = new WpfdHelperShortcodes();
        $category_shortcode   = '';
        if (isset($_POST['wpfdajaxnone']) && wp_verify_nonce($_POST['wpfdajaxnone'], 'wpfd-ajax-none')) {
            if (isset($_POST['atts_shortcode']) && is_array($_POST['atts_shortcode'])) {
                $atts = $_POST['atts_shortcode'];
                $category_shortcode   = $helper->contentAllCat($atts, true);
            }
        }

        return $category_shortcode;
    }

    /**
     * Get tree categories
     *
     * @return void
     */
    public function getTreeCats()
    {
        $modelCategories = $this->getModel('categoriesfront');
        $modelCategory   = $this->getModel('categoryfront');
        $categories      = $modelCategories->getLevelCategories();

        $categoriesList     = array();
        if (!empty($categories)) {
            $roles = array();
            $user  = wp_get_current_user();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            foreach ($categories as $cate) {
                $termId     = $cate->term_id;
                if (!is_numeric($termId) && isset($cate->wp_term_id)) {
                    $termId = $cate->wp_term_id;
                }
                $category = $modelCategory->getCategory($termId);
                $params   = $category->params;
                $categoryVisibility = wpfd_get_category_visibility($termId);
                $cat_roles       = isset($categoryVisibility['roles']) ? $categoryVisibility['roles'] : array();
                $cat_access      = isset($categoryVisibility['access']) ? $categoryVisibility['access'] : 0;

                if (isset($params['canview']) && $params['canview'] !== '') {
                    if (((int)$params['canview'] !== 0) && (int) $params['canview'] === $user->ID) {
                        $allows_single = true;
                    }
                }

                $category->level  = $cate->level;
                if ((int) $cat_access === 1) {
                    $allows = array_intersect($roles, $cat_roles);
                    if ($allows || $allows_single) {
                        $categoriesList[] = $category;
                    }
                } else {
                    $categoriesList[] = $category;
                }
            }
        }

        $counter = 1;
        $html = '';
        foreach ($categoriesList as $category) {
            $catFrom = apply_filters('wpfdAddonCategoryFrom', $category->term_id);
            if ($counter === 1) {
                $categoryFrom = $catFrom;
            }
            $counter++;
            $html .= '<option value="' . esc_attr($category->term_id) . '" data-type="'.esc_attr($catFrom).'">' . esc_html(str_repeat('-', $category->level - 1)) . esc_html($category->name) . '</option>';
        }

        wp_send_json(array(
            'success' => true,
            'data'    => $html
        ));
    }
}
