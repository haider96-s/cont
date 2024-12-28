<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0.3
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;

//-- No direct access
defined('ABSPATH') || die();

/**
 * Class WpfdThemeTree
 */
class WpfdThemeTree extends WpfdTheme
{
    /**
     * Theme name
     *
     * @var string
     */
    public $name = 'tree';

    /**
     * Get tpl path for include
     *
     * @return string
     */
    public function getTplPath()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl.php';
    }

    /**
     * Load template hooks
     *
     * @return void
     */
    public function loadHooks()
    {
        $name = self::$themeName;
        $globalConfig      = get_option('_wpfd_global_config');
        // Theme Content Output
        add_action('wpfd_' . $name . '_before_theme_content', array(__CLASS__, 'outputContentWrapper'), 10, 1);
        add_action('wpfd_' . $name . '_before_theme_content', array(__CLASS__, 'outputContentHeader'), 20, 1);
        // File content
        add_action('wpfd_' . $name . '_file_content_handlebars', array(__CLASS__, 'showIconHandlebars'), 10, 2);
        add_action('wpfd_' . $name . '_file_content_handlebars', array(__CLASS__, 'showTitleHandlebars'), 20, 2);

        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showDescriptionHandlebars'), 10, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showVersionHandlebars'), 20, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showSizeHandlebars'), 30, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showHitsHandlebars'), 40, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showCreatedHandlebars'), 50, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showModifiedHandlebars'), 60, 2);

        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showcategorytitle', 1) === 1 && !$this->latest) {
            add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'showCategoryTitle'), 20, 2);
        }

        // File buttons
        add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'buttonWrapper'), 10);
        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdownload', 1) === 1 && wpfd_can_download_files()) {
            add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'showDownloadHandlebars'), 20, 2);
        }
        if ($this->config['use_google_viewer'] !== 'no' && wpfd_can_preview_files()) {
            add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'showPreviewHandlebars'), 30, 2);
        }
        add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'buttonWrapperEnd'), 90);

        // End Theme Content Output
        add_action('wpfd_' . $name . '_after_theme_content', array(__CLASS__, 'outputContentWrapperEnd'), 10, 1);

        /**
         * Action fire after template hooked
         *
         * @hookname wpfd_{$themeName}_after_template_hooks
         *
         * @ignore
         */
        do_action('wpfd_' . $name . '_after_template_hooks');
        $this->loadCustomHooks();
    }

    /**
     * Load custom hooks and filters
     *
     * @return void
     */
    public function loadCustomHooks()
    {
        $name = self::$themeName;
    }

    /**
     * Print button wrapper open
     *
     * @return void
     */
    public static function buttonWrapper()
    {
        echo '<div class="extra-downloadlink">';
    }

    /**
     * Print button wrapper end
     *
     * @return void
     */
    public static function buttonWrapperEnd()
    {
        echo '</div>';
    }

    /**
     * Show password protection file block
     *
     * @param object  $file  File object
     * @param mixed   $style Style of file
     * @param boolean $echo  Echo or not
     *
     * @return void|mixed
     */
    public static function wpfdTreeDisplayFilePasswordProtectionForm($file, $style, $echo = true)
    {
        if (!$file) {
            return;
        }

        $fileTitle = isset($file->post_title) ? $file->post_title : '';
        $contents  = '<li class="ext wpfd-password-protection-form ' . esc_attr(strtolower($file->ext)) . '"';
        $contents .= ' style="' . esc_html($style) . '">';
        $contents .= '<h3 class="protected-title" title="' . $fileTitle . '">';
        $contents .= esc_html__('Protected: ', 'wpfd') . $fileTitle . '</h3>';
        $contents .= wpfdGetPasswordForm($file, 'file', $file->catid) . '</li>';

        if ($echo) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text form only
            echo $contents;
        } else {
            return $contents;
        }
    }

    /**
     * Build tree category
     *
     * @param array  $config        Config of category
     * @param object $params        Current theme params
     * @param array  $name          Current theme name
     * @param array  $listCategory  List category
     * @param mixed  $parentId      Parent ID of the current node
     * @param mixed  $categoryLevel Category level
     *
     * @return string HTML string representing the tree structure
     */
    public function wpfdBuildTree($config, $params, $name, $listCategory, $parentId = 0, $categoryLevel = 0)
    {
        Application::getInstance('Wpfd');
        $modelCat = Model::getInstance('categoryfront');
        $modelFiles = Model::getInstance('filesfront');
        $isPreviewLink = apply_filters('wpfd_file_replace_download_with_preview', false);
        $target = (isset($config['use_google_viewer']) && $config['use_google_viewer'] === 'tab' && $isPreviewLink) ? '_blank' : '';
        $html = '';
        foreach ($listCategory as $category) {
            $catLevel = $category->level;
            $color = intval($category->wp_term_id) !== 0 ? get_term_meta($category->wp_term_id, '_wpfd_color', true) : '';
            if ((int) $category->parent === (int) $parentId || (isset($category->wp_parent) && (int) $category->wp_parent === (int) $parentId )) {
                if ($category->term_id === 0) {
                    $root = new \stdClass;
                    $root->name = get_bloginfo('name');
                    $root->slug = sanitize_title(get_bloginfo('name'));
                    $root->term_id = 'all_0';
                    $category = new WP_Term($root);
                } else {
                    if (isset($category->cloudType) && $category->cloudType !== false && in_array($category->cloudType, wpfd_get_support_cloud())) {
                        $category = $modelCat->getCategory($category->wp_term_id);
                    } else {
                        $category = $modelCat->getCategory($category->term_id);
                    }
                }
                $ordering = $category->ordering;
                $orderingdir = $category->orderingdir;
                $modelTokens = Model::getInstance('tokens');

                $token = $modelTokens->getOrCreateNew();
                $files = array();
                $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $category->term_id);
                if ($categoryFrom === 'googleDrive') {
                    $files = apply_filters(
                        'wpfdAddonGetListGoogleDriveFile',
                        $category->term_id,
                        $ordering,
                        $orderingdir,
                        $category->slug,
                        $token
                    );
                } elseif ($categoryFrom === 'dropbox') {
                    $files = apply_filters(
                        'wpfdAddonGetListDropboxFile',
                        $category->term_id,
                        $ordering,
                        $orderingdir,
                        $category->slug,
                        $token
                    );
                } elseif ($categoryFrom === 'onedrive') {
                    $files = apply_filters(
                        'wpfdAddonGetListOneDriveFile',
                        $category->term_id,
                        $ordering,
                        $orderingdir,
                        $category->slug,
                        $token
                    );
                } elseif ($categoryFrom === 'onedrive_business') {
                    $files = apply_filters(
                        'wpfdAddonGetListOneDriveBusinessFile',
                        $category->term_id,
                        $ordering,
                        $orderingdir,
                        $category->slug,
                        $token
                    );
                } elseif ($categoryFrom === 'aws') {
                    $files = apply_filters(
                        'wpfdAddonGetListAwsFile',
                        $category->term_id,
                        $ordering,
                        $orderingdir,
                        $category->slug,
                        $token
                    );
                } elseif ($categoryFrom === 'nextcloud') {
                    $files = apply_filters(
                        'wpfdAddonGetListNextcloudFile',
                        $category->term_id,
                        $ordering,
                        $orderingdir,
                        $category->slug,
                        $token
                    );
                } else {
                    $files = $modelFiles->getFiles($category->term_id, $ordering, $orderingdir);
                }
                
                $fileContent = '';
                $classDirectory = 'collapsed';
                if (!empty($files)) {
                    $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
                    foreach ($files as $key => $file) {
                        if (isset($file->state) && (int) $file->state === 0) {
                            continue;
                        }

                        $file->crop_title = WpfdBase::cropTitle($params, $name, $file->post_title);
                        if (isset($file->file_custom_icon) && $file->file_custom_icon !== '') {
                            if (strpos($file->file_custom_icon, site_url()) !== 0) {
                                $file->file_custom_icon = site_url() . $file->file_custom_icon;
                            }
                        }

                        $downloadSelected = '';
                        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files() && is_numeric($file->ID)) {
                            $downloadSelected = '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="' . esc_attr($file->ID) . '" data-catid="' . esc_attr($file->catid) . '" /><span></span></label>';
                        }

                        $classFile = strtolower($file->ext);
                        if ($config['custom_icon'] && $file->file_custom_icon) {
                            $iconContent = '<i class="wpfd-file"><img src="'.esc_url($file->file_custom_icon).'"></i>';
                        } else {
                            $iconContent = '<i class="wpfd-file ext ext-'.esc_attr($classFile).' '.esc_attr($iconSet).'"></i>';
                        }

                        $atthref = '#';
                        if ((int) WpfdBase::loadValue($params, $name . '_download_popup', 1) === 0) {
                            $viewerlink = isset($file->viewerlink) ? $file->viewerlink : '';
                            $filePreviewLink = isset($file->openpdflink) ? $file->openpdflink : $viewerlink;
                            $atthref = $isPreviewLink ? $filePreviewLink : $file->linkdownload;
                        }

                        $fileLink = '<a class="wpfd-file-link" href="'.esc_url($atthref).'" data-category_id="'.esc_attr($file->catid).'" data-id="'.esc_attr($file->ID).'" title="'.esc_attr($file->post_title).'" target="'.esc_attr($target).'">'.esc_html($file->crop_title).'</a>';
                        $fileContent .= '<li class="ext '.esc_attr($classFile).'">';
                        $fileContent .= $downloadSelected;
                        $fileContent .= $iconContent;
                        $fileContent .= $fileLink;
                        $fileContent .= '</li>';
                    }
                }

                $html .= '<li class="directory open expanded">';
                $html .=    '<a class="catlink" href="#" data-idcat="'.esc_attr($category->term_id).'">';
                $html .=        '<div class="icon-open-close" data-id="'.esc_attr($category->term_id).'"></div>';
                $html .=        '<i class="zmdi zmdi-folder wpfd-folder" style="color: '. $color .'"></i>';
                $html .=        '<span>'.esc_html($category->name).'</span>';
                $html .=    '</a>';
                $html .=    '<ul>';
                $html .=    $this->wpfdBuildTree($config, $params, $name, $listCategory, $category->term_id, $catLevel);
                $html .=    $fileContent;
                $html .=    '</ul>';
                $html .= '</li>';
            }
        }

        return $html;
    }
}
