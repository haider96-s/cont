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
     * List all categories, used for gutenberg block.
     *
     * @return void
     */
    public function listCats()
    {
        $categoriesModel = $this->getModel();
        $hierarchy = $categoriesModel->getCategories();
        wp_send_json(array(
            'success' => true,
            'data'    => $hierarchy
        ));
        die();
    }

    /**
     * List tree view
     *
     * @param array|mixed $categoryList Category list
     *
     * @return string|void
     */
    public function listTreeView($categoryList = array())
    {
        Application::getInstance('Wpfd');
        $modelCat = $this->getModel('categories');
        $modelConfig = $this->getModel('config');
        $globalConfig = $modelConfig->getConfig();
        $refresh = Utilities::getInput('refresh', 'POST', 'bool');

        if (empty($categoryList)) {
            $categoryList = $modelCat->getCategories();
        }
        // Update count
        wp_defer_term_counting(true);
        $content = '';
        $previouslevel = 1;
        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
        $categories = is_countable($categoryList) ? count($categoryList) : 0;
        $dropbox_connected = wpfd_dropbox_connected();
        $google_connected = wpfd_google_drive_connected();
        $onedrive_connected = wpfd_onedrive_connected();
        $onedrive_business_connected = wpfd_onedrive_business_connected();
        $aws_connected = wpfd_aws_connected();
        $nextcloud_connected = wpfd_nextcloud_connected();
        for ($index = 0; $index < $categories; $index++) {
            $cloudType = isset($categoryList[$index]->cloudType) ? $categoryList[$index]->cloudType : false;

            if (($cloudType === 'dropbox' && !$dropbox_connected) ||
                ($cloudType === 'googleDrive' && !$google_connected) ||
                ($cloudType === 'onedrive' && !$onedrive_connected) ||
                ($cloudType === 'onedrive_business' && !$onedrive_business_connected) ||
                ($cloudType === 'aws' && !$aws_connected) ||
                ($cloudType === 'nextcloud' && !$nextcloud_connected)
            ) {
                continue;
            }

            if ($index + 1 !== $categories) {
                $nextlevel = (int)$categoryList[$index + 1]->level;
            } else {
                $nextlevel = 0;
            }

            $content .= $this->openItem($categoryList[$index], $index, $globalConfig);
            if ($nextlevel > $categoryList[$index]->level) {
                $content .= $this->openlist($categoryList[$index]);
            } elseif ($nextlevel === (int)$categoryList[$index]->level) {
                $content .= $this->closeItem();
            } else {
                $c = '';
                $c .= $this->closeItem();
                $c .= $this->closeList();
                $content .= str_repeat($c, $categoryList[$index]->level - $nextlevel);
            }
            $previouslevel = (int)$categoryList[$index]->level;
        }

        if ($refresh) {
            wp_send_json(array('success' => true, 'data' => $content));
        }

        return $content;
    }

    /**
     * Create category list
     *
     * @return void
     */
    public function createCategoriesDeep()
    {
        $paths      = Utilities::getInput('paths', 'POST', 'none');
        $categoryId = Utilities::getInt('category_id', 'POST');
        $type       = Utilities::getInput('type', 'POST', 'string');
        $paths      = explode('|', $paths);

        if ($type === 'googledrive') {
            $type = 'googleDrive';
        }

        if ($type === 'googleteamdrive') {
            $type = 'googleTeamDrive';
        }

        if ($categoryId !== 0 && empty($categoryId)) {
            $categoryId = 0;
        }

        if (!is_array($paths)) {
            wp_send_json_error();
        }

        $results = [];
        foreach ($paths as $path) {
            if (empty($path)) {
                // This is file upload to root category
                $results[$path] = (int) $categoryId;
                continue;
            }
            $id = WpfdHelperFolder::createCategoryByPath($path, $categoryId, $type);
            $results[$path] = (int) $id;
        }

        wp_send_json_success([
            'results' => $results
        ]);
    }

    /**
     * Open Item
     *
     * @param object  $category     Category
     * @param integer $key          Key
     * @param array   $globalConfig Config
     *
     * @return string
     */
    public function openItem($category, $key, $globalConfig)
    {
        $iconsClass = '';
        $iconText = '';
        $type = $category->cloudType;
        switch ($type) {
            case 'dropbox':
                $iconsClass = 'dropbox-icon wpfd-folder wpfd-liga';
                $iconText = 'dropbox';
                break;
            case 'googleDrive':
                $iconsClass = 'google-drive-icon wpfd-folder wpfd-liga';
                $iconText = 'google_drive';
                break;
            case 'googleTeamDrive':
                $iconsClass = 'google-team-drive-icon wpfd-folder wpfd-liga';
                $iconText = 'google_drive';
                break;
            case 'onedrive':
                $iconsClass = 'onedrive-icon wpfd-folder wpfd-liga';
                $iconText = 'onedrive';
                break;
            case 'onedrive_business':
                $iconsClass = 'onedrive-business-icon wpfd-folder wpfd-liga business';
                $iconText = 'onedrive';
                break;
            case 'aws':
                $iconsClass = 'wpfd-aws-icon wpfd-folder wpfd-liga';
                $iconText = '';
                break;
            case 'nextcloud':
                $iconsClass = 'wpfd-nextcloud-icon wpfd-folder wpfd-liga';
                $iconText = '';
                break;
        }

        if ($iconsClass === '') {
            $iconsClass = 'material-icons wpfd-folder';
            $iconText = 'folder';
        }

        $iconsCat = '<i';

        if ($category->color) {
            $iconsCat .= ' style="color: ' . $category->color . '"';
        }
        $iconsCat .= ' class="' . $iconsClass . '">' . $iconText . '</i>';

        if (isset($category->disable) && $category->disable) {
            $item_id_disable = 'data-item-disable="' . esc_attr($category->term_id) . '"';
            $dd_handle       = '';
            $category_count  = '';
            $disable         = ' disabled ';
        } else {
            $disable         = ' not_disable ';
            $item_id_disable = '';
            $dd_handle       = ' dd-handle ';
            $category_count  = $category->count;
        }

        $spacing = 10;
        if ($category->level > 0) {
            $padding = $spacing * ($category->level);
        }
        $item = '<li class="' . $disable . ' dd-item dd3-item ' . ($key ? '' : 'active') . '"';
        if (isset($padding) && $padding > 0) {
            $item .= ' style="padding-left: '.$padding.'px"';
        }
        $item .= ' data-color="' . esc_attr($category->color) . '"';
        $item .= ' data-parent-id="' . $category->parent . '"';
        $item .= ' data-id="' . $category->term_id . '" data-id-category="';
        $item .= $category->term_id . '"  ' . $item_id_disable . ' data-level="'.$category->level.'" data-type="'. $type . '">
        <div class="' . $disable . $dd_handle . ' dd3-handle">' . $iconsCat . '</div>';
        $margin = '';
        if (isset($padding) && $padding > 0) {
            $margin = ' style="margin-left: -' . ($padding + 20) . 'px;padding-left: '. ($padding + 67) .'px"';
        }
        $item .= '<div class="dd-content dd3-content' . $disable . $dd_handle . '" '.$margin.'>';
        if ((int) WpfdBase::loadValue($globalConfig, 'file_count', 0) !== 0 && $category_count !== null) {
            $item .= '<span class="countfile"><span class="count_badge">' . esc_html($category_count) . '</span></span>';
        }
        $item .= '<a href="" title="' . esc_html($category->name) . '" class="t' . $disable . '"' . $disable . '>';
        $item .= '<span class="title">' . esc_html($category->name);
        $item .= '</span> </a> </div>';

        return $item;
    }

    /**
     * Close Item
     *
     * @return string
     */
    public function closeItem()
    {
        return '</li>';
    }

    /**
     * Open List
     *
     * @param WP_Term $category Category object
     *
     * @return string
     */
    public function openlist($category = null)
    {
        if (!is_null($category) && $category->level > 0) {
            $margin = ($category->level) * 10;
        }
        $html = '<ol class="dd-list"';
        if (isset($margin) && $margin > 0) {
            $html .= ' style="margin-left: -'.$margin.'px"';
        }
        $html .= '>';

        return $html;
    }

    /**
     * Close List
     *
     * @return string
     */
    public function closelist()
    {
        return '</ol>';
    }
}
