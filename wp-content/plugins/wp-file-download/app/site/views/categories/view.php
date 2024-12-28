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

defined('ABSPATH') || die();

/**
 * Class WpfdViewCategories
 */
class WpfdViewCategories extends View
{
    /**
     * Display categories
     *
     * @param string $tpl Template name
     *
     * @return void
     */
    public function render($tpl = null)
    {
        /* @var WpfdModelCategoriesfront $modelCats */
        $modelCats         = $this->getModel('categoriesfront');
        /* @var WpfdModelCategoryfront $modelCat */
        $modelCat          = $this->getModel('categoryfront');
        $app = Application::getInstance('Wpfd');
        if (!class_exists('WpfdHelperShortcodes')) {
            $path_helper = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'WpfdHelperShortcodes.php';
            require_once $path_helper;
        }
        $categoryId = Utilities::getInput('id', 'GET', 'string');
        $content           = new stdClass();
        $content->category = new stdClass();
        if ($categoryId === 'all_0') {
            $categories = $modelCats->getCategories(0);
        } else {
            $categories = $modelCats->getCategories(Utilities::getInt('id'));
        }
        $category = $modelCat->getCategory(Utilities::getInt('id'), Utilities::getInt('top'));

        if (Utilities::getInput('top', 'GET', 'string') === 'all_0') {
            if (empty($category)) {
                $category = new StdClass;
                $category->term_id = 0;
                $category->access = 0;
                $category->parent = 'all_0';
            }
            $category->parent = intval($category->parent) === 0 ? 'all_0' : $category->parent;
        }

        $params = isset($category->params) ? $category->params : array();
        $categoryThemeName = isset($params['theme']) ? $params['theme'] : 'default';
        $categoryThemePrefix = $categoryThemeName === 'default' ? '' : $categoryThemeName . '_';
        $subcategoriesOrdering = isset($params['subcategoriesordering']) ? $params['subcategoriesordering'] : 'customorder';
        $globalConfig = get_option('_wpfd_global_config');
        $globalSubcategoriesOrdering = isset($globalConfig['global_subcategories_ordering']) ? $globalConfig['global_subcategories_ordering'] : 'customorder';
        $globalSubcategoriesOrderingAll = (isset($globalConfig['global_subcategories_ordering_all']) && intval($globalConfig['global_subcategories_ordering_all']) === 1) ? true : false;
        $defaultGlobalSubcategoriesOrdering = array('customorder', 'nameascending', 'namedescending');
        if ($globalSubcategoriesOrderingAll && in_array($globalSubcategoriesOrdering, $defaultGlobalSubcategoriesOrdering)) {
            $subcategoriesOrdering = $globalSubcategoriesOrdering;
        }
        $helper = new WpfdHelperShortcodes();
        if ((string) $subcategoriesOrdering !== 'customorder') {
            $subcategoriesDirection = (string)$subcategoriesOrdering === 'namedescending' ? 'desc' : 'asc';
            $categories = $helper->wpfdCategoriesOrdering($categories, $subcategoriesDirection);
        }

        $content->categories = $categories;
        $app                 = Application::getInstance('Wpfd');
        $path_wpfdhelper     = $app->getPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers';
        $path_wpfdhelper     .= DIRECTORY_SEPARATOR . 'WpfdHelper.php';
        require_once $path_wpfdhelper;
        if (WpfdHelper::checkCategoryAccess($category)) {
            $content->category = $category;
        }
        if (Utilities::getInt('id') === intval(Utilities::getInput('top', 'GET', 'string'))) {
            $content->category->parent = false;
            $content->category->slug = 'top';
            if (Utilities::getInt('id') === 0 && intval(Utilities::getInput('top', 'GET', 'string')) === 0) {
                $content->category->breadcrumbs = esc_html__('All Categories', 'wpfd');
            }
        }

        $content->displayfilesearch = isset($params[$categoryThemePrefix . 'displayfilesearch']) ? intval($params[$categoryThemePrefix . 'displayfilesearch']) : 0;
        echo wp_json_encode($content);
        die();
    }
}
