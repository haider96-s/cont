<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

//-- No direct access
defined('ABSPATH') || die();

/**
 * Class WpfdThemePreview
 */
class WpfdThemePreview extends WpfdTheme
{
    /**
     * Theme name
     *
     * @var string
     */
    public $name = 'preview';

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
        $name              = $this->getThemeName();
        $showsubcategories = (int) WpfdBase::loadValue($this->params, self::$prefix . 'showsubcategories', 1) === 1 ? true : false;
        $showcategorytitle = (int) WpfdBase::loadValue($this->params, self::$prefix . 'showcategorytitle', 1) === 1 ? true : false;
        $globalConfig      = get_option('_wpfd_global_config');

        /* File Block */
        // File content
        add_action('wpfd_' . $name . '_file_content_handlebars', array(__CLASS__, 'showIconHandlebars'), 10, 2);
        add_action('wpfd_' . $name . '_file_content_handlebars', array(__CLASS__, 'showTitleHandlebars'), 20, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showDescriptionHandlebars'), 10, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showVersionHandlebars'), 20, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showSizeHandlebars'), 30, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showHitsHandlebars'), 40, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showCreatedHandlebars'), 50, 2);
        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showModifiedHandlebars'), 60, 2);

        // File buttons
        add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'buttonWrapper'), 10);
        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdownload', 1) === 1 && wpfd_can_download_files()) {
            add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'showDownloadHandlebars'), 20, 2);
        }
        if ($this->config['use_google_viewer'] !== 'no' && wpfd_can_preview_files()) {
            add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'showPreviewHandlebars'), 30, 2);
        }
        add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'buttonWrapperEnd'), 90);
        // File info
        add_action('wpfd_' . $name . '_file_block_handlebars', array(__CLASS__, 'fileBlockWrapperHandlebars'), 10, 2);
        add_action('wpfd_' . $name . '_file_block_handlebars', array(__CLASS__, 'showFileBlockIconHandlebars'), 20, 2);
        add_action('wpfd_' . $name . '_file_block_handlebars', array(__CLASS__, 'showFileBlockTitleHandlebars'), 30, 2);
        add_action('wpfd_' . $name . '_file_block_handlebars', array(__CLASS__, 'linkClose'), 90, 1);

        add_action('wpfd_' . $name . '_file_block', array(__CLASS__, 'fileBlockWrapper'), 10, 3);
        add_action('wpfd_' . $name . '_file_block', array(__CLASS__, 'showFileBlockIcon'), 20, 3);
        add_action('wpfd_' . $name . '_file_block', array(__CLASS__, 'showFileBlockTitle'), 30, 3);
        add_action('wpfd_' . $name . '_file_block', array(__CLASS__, 'linkClose'), 90, 1);
        // End file info

        // Before files loop
        add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'outputCategoriesWrapper'), 10, 2);
        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'outputCategoriesWrapper'), 10, 2);

        if ($showcategorytitle && !$this->latest) {
            add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'showCategoryTitle'), 20, 2);
        }

        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
        add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'showCategories'), 30, 2);
        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'showCategoriesHandlebars'), 30, 2);

        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'showCategoryTitleHandlebars'), 20, 2);

        add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'outputCategoriesWrapperEnd'), 90, 2);
        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'outputCategoriesWrapperEnd'), 90, 2);
        // End before files loop

        /* Folder Tree */
        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showfoldertree', 0) !== 0 && !$this->latest) {
            add_action('wpfd_' . $name . '_folder_tree', array(__CLASS__, 'showTree'), 10, 2);
        }

        /* Theme Content Output - USER CAN NOT CHANGE THIS */
        add_action('wpfd_' . $name . '_before_theme_content', array(__CLASS__, 'outputContentWrapper'), 10, 1);
        add_action('wpfd_' . $name . '_before_theme_content', array(__CLASS__, 'outputContentHeader'), 20, 1);
        add_action('wpfd_' . $name . '_after_theme_content', array(__CLASS__, 'outputContentWrapperEnd'), 10, 1);

        /**
         * Action fire after template hooked
         *
         * @hookname wpfd_{$themeName}_after_template_hooks
         *
         * @ignore
         */
        do_action('wpfd_' . $name . '_after_template_hooks');
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
     * Print icon handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showIconHandlebars($config, $params)
    {
        $html = '';
        $name = self::$themeName;
        $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
        $thumbnailPlaceholder = '{{#if thumbnail_url}} style="background: url({{thumbnail_url}}) no-repeat"{{/if}}';
        if ($config['custom_icon']) {
            $html = '{{#if file_custom_icon}}
                    <div class="icon-custom"><img src="{{file_custom_icon}}"></div>
                    {{else}}
                    <div class="ext ext-{{ext}}' . $iconSet . '"' . $thumbnailPlaceholder . '><span class="txt">{{ext}}</span></div>
                    {{/if}}';
        } else {
            $html = '<div class="ext ext-{{ext}}' . $iconSet . '"' . $thumbnailPlaceholder . '><span class="txt">{{ext}}</span></div>';
        }
        /**
         * Filter to change icon html for handlebars template
         *
         * @param string Output html for handlebars template
         * @param array  Main config
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_file_info_icon_hanlebars
         *
         * @return string
         *
         * @ignore
         */
        $output = apply_filters('wpfd_' . $name . '_file_info_icon_hanlebars', $html, $config, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Show file block wrapper handlebars
     *
     * @param array $config Main settings
     * @param array $params Category params
     *
     * @return void
     */
    public static function fileBlockWrapperHandlebars($config, $params)
    {
        $name = self::$themeName;
        $selectFileInput = '';
        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files()) {
            $selectFileInput = '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="{{ID}}" /><span></span></label>';
        }
        $downloadlink = '#';
        /**
         * Filter allow to change the file download link when disable download popup
         *
         * @param boolean
         *
         * @ignore Hook already documented
         */
        $isPreviewLink = apply_filters('wpfd_file_replace_download_with_preview', false);
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'download_popup', 1) === 0) {
            $downloadlink = $isPreviewLink ? '{{viewerlink}}' : '{{linkdownload}}';
        }
        $style = 'margin : ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'margintop', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginright', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginbottom', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginleft', 10) . 'px;';

        /**
         * Filter to change html and arguments of file content wrapper in handlebars template
         *
         * @param array Html array
         * @param array Global config
         * @param array Category config
         *
         * @return array
         *
         * @ignore
         */
        $args = apply_filters(
            'wpfd_' . $name . '_file_content_wrapper_handlebars',
            array(
                'html' => '<div class="file" style="' . $style . '">' . $selectFileInput . '<a class="wpfd-file-link" href="%link$s" data-category_id="%catid$s" data-id="%fileid$s">',
                'args' => array(
                    'link' => $downloadlink,
                    'catid' => '{{catid}}',
                    'fileid' => '{{ID}}'
                )
            ),
            $config,
            $params
        );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render($args['html'], $args['args']);
    }

    /**
     * Show file block wrapper
     *
     * @param object $file   File object
     * @param array  $config Main settings
     * @param array  $params Category params
     *
     * @return void
     */
    public static function fileBlockWrapper($file, $config, $params)
    {
        $name = self::$themeName;
        $selectFileInput = '';
        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files() && is_numeric($file->ID)) {
            $selectFileInput = '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="' . esc_attr($file->ID) . '" data-catid="' . esc_attr($file->catid) . '" /><span></span></label>';
        }
        $downloadlink = '#';
        /**
         * Filter allow to change the file download link when disable download popup
         *
         * @param boolean
         *
         * @ignore Hook already documented
         */
        $isPreviewLink = apply_filters('wpfd_file_replace_download_with_preview', false);
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'download_popup', 1) === 0) {
            $downloadlink = (isset($file->viewerlink) && $isPreviewLink) ? $file->viewerlink : $file->linkdownload;
        }
        /**
         * Filter to change html and arguments of file content wrapper
         *
         * @param array Html array
         * @param array Global config
         * @param array Category config
         *
         * @return array
         *
         * @ignore
         */
        $style = 'margin : ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'margintop', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginright', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginbottom', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginleft', 10) . 'px;';
        $args = apply_filters(
            'wpfd_' . $name . '_file_content_wrapper',
            array(
                'html' => '<div class="file" style="' . $style . '">' . $selectFileInput . '<a class="wpfd-file-link" href="%link$s" data-category_id="%catid$d" data-id="%fileid$s">',
                'args' => array(
                    'link' => $downloadlink,
                    'catid' => esc_attr($file->catid),
                    'fileid' => esc_attr($file->ID)
                )
            ),
            $config,
            $params
        );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render($args['html'], $args['args']);
    }

    /**
     * Print close tag for link
     *
     * @return void
     */
    public static function linkClose()
    {
        echo '</a></div>';
    }

    /**
     * Show file block icon handlebars
     *
     * @param array $config Main settings
     * @param array $params Category params
     *
     * @return void
     */
    public static function showFileBlockIconHandlebars($config, $params)
    {
        echo '<div class="dropblock">';
        $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
        if ($config['custom_icon']) {
            echo '{{#if file_custom_icon}}
                    <div class="icon-custom"><img src="{{file_custom_icon}}" /></div>
                    {{else}}
                    <div class="ext ext-{{ext}}' . esc_attr($iconSet) . '"><span class="txt">{{ext}}</span></div>
                    {{/if}}';
        } else {
            echo '<div class="ext ext-{{ext}}' . esc_attr($iconSet) . '"><span class="txt">{{ext}}</span></div>';
        }
        echo '</div>';
    }

    /**
     * Show file block icon
     *
     * @param object $file   File object
     * @param array  $config Main settings
     * @param array  $params Category params
     *
     * @return void
     */
    public static function showFileBlockIcon($file, $config, $params)
    {
        $useGeneratedView  = isset($config['auto_generate_preview']) && intval($config['auto_generate_preview']) === 1 ? true : false;
        $imgExists         = false;
        $fileView          = '';
        $id                = isset($file->id) ? $file->id : $file->ID;
        $viewImageFilePath = '';
        $containClass      = '';
        $customPreviewPath = '';
        $viewClass         = 'wpfd-view-default';
        $categoryId        = $file->catid;
        $wmCategoryEnabled = false;
        $lists = get_option('wpfd_watermark_category_listing');
        if (is_array($lists) && !empty($lists)) {
            if (in_array($categoryId, $lists)) {
                $wmCategoryEnabled = true;
            }
        }

        if (is_numeric($id)) {
            $viewImageFilePath = get_post_meta($id, '_wpfd_thumbnail_image_file_path', true);
            $metaData = get_post_meta($id, '_wpfd_file_metadata', true);
            $customPreviewPath = isset($metaData['file_custom_icon_preview']) ? $metaData['file_custom_icon_preview'] : '';
        } else {
            $viewFileInfo = get_option('_wpfdAddon_preview_info_' . md5($id), false);
            $viewFilePath = is_array($viewFileInfo) && isset($viewFileInfo['path']) ? $viewFileInfo['path'] : false;
            $previewIcon = get_option('_wpfdAddon_file_custom_icon_preview_' . md5($file->ID), false);
            $customPreviewPath = (isset($previewIcon) && !is_null($previewIcon) && $previewIcon !== false) ? $previewIcon : '';
        }

        if ($useGeneratedView && isset($viewFilePath) && $viewFilePath && file_exists(WP_CONTENT_DIR . $viewFilePath)) {
            $imgExists = true;
            $fileView  = WP_CONTENT_URL . $viewFilePath;
            $viewClass = 'wpfd-view-thumbnail';
        } elseif ($useGeneratedView && isset($viewImageFilePath) && $viewImageFilePath && file_exists(WP_CONTENT_DIR . $viewImageFilePath)) {
            $viewImageClass = get_post_meta($id, '_wpfd_thumbnail_image_file_contain_class', true);
            $fileView  = WP_CONTENT_URL . $viewImageFilePath;
            $imgExists = true;
            $viewClass = 'wpfd-view-image-thumbnail';
            if ($viewImageClass) {
                $containClass = 'contain';
            }
        } elseif ($useGeneratedView && !is_null($customPreviewPath) && !empty($customPreviewPath) && file_exists(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $customPreviewPath)) {
            $viewImageClass = get_post_meta($id, '_wpfd_thumbnail_image_file_contain_class', true);
            $fileView  = WP_CONTENT_URL . DIRECTORY_SEPARATOR . $customPreviewPath;
            $imgExists = true;
            $viewClass = 'wpfd-view-image-thumbnail';
            if ($viewImageClass) {
                $containClass = 'contain';
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
                    $fileView  = WP_CONTENT_URL . $viewFileWmPath;
                    $imgExists = true;
                    $viewClass = 'wpfd-view-image-thumbnail';
                }
            }
        }

        $viewCss = $imgExists ? 'background-image: url(' . $fileView . ');' : '';
        $customIconClass = ($config['custom_icon'] && $file->file_custom_icon && !$imgExists) ? 'wpfd-view-custom-icon' : '';

        echo '<div class="dropblock ' . esc_attr($viewClass) . ' ' . esc_attr($containClass) . ' ' . esc_attr($customIconClass) . '" style="' . esc_attr($viewCss) . '">';
        if ($config['custom_icon'] && $file->file_custom_icon && !$imgExists) {
            echo sprintf(
                '<div class="icon-custom"><img src="%s" /></div>',
                esc_url($file->file_custom_icon)
            );
        } else {
            $iconSet = (isset($config['icon_set']) && $config['icon_set'] !== 'default') ? ' wpfd-icon-set-' . esc_attr($config['icon_set']) : '';
            echo sprintf(
                '<div class="ext ext-%s%s"><span class="txt">%s</span></div>',
                esc_attr(strtolower($file->ext)),
                esc_attr($iconSet),
                esc_html($file->ext)
            );
        }
        echo '</div>';
    }

    /**
     * Show file block title handlebars
     *
     * @param array $config Main settings
     * @param array $params Category params
     *
     * @return void
     */
    public static function showFileBlockTitleHandlebars($config, $params)
    {
        $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
        echo '<div class="dropblock-footer-section" title="{{{crop_title}}}"><div class="ext ext-{{ext}}' . esc_attr($iconSet) . '"><span class="txt"></div><div class="droptitle">{{{crop_title}}}</div></div>';
    }

    /**
     * Show file block title
     *
     * @param object $file   File object
     * @param array  $config Main settings
     * @param array  $params Category params
     *
     * @return void
     */
    public static function showFileBlockTitle($file, $config, $params)
    {
        $iconSet = (isset($config['icon_set']) && $config['icon_set'] !== 'default') ? ' wpfd-icon-set-' . esc_attr($config['icon_set']) : '';
        echo '<div class="dropblock-footer-section" title="' . esc_html($file->crop_title) . '"><div class="ext ext-' . esc_attr($file->ext) . esc_attr($iconSet) . '"><span class="txt"></div><div class="droptitle">' . esc_html($file->crop_title) . '</div></div>';
    }
}
