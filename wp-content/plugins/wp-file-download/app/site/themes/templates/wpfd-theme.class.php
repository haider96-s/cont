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
use Joomunited\WPFramework\v1_0_6\Application;

/**
 * Class WpfdTheme
 */
class WpfdTheme
{
    /**
     * Theme name
     *
     * @var string
     */
    public $name = 'default';

    /**
     * Hide empty file info
     *
     * @var booleam
     */
    public static $hideEmpty = true;

    /**
     * Theme param prefix
     *
     * @var string
     */
    public static $prefix = '';

    /**
     * Category theme config
     *
     * @var array
     */
    public $params;

    /**
     * Ajax url
     *
     * @var string
     */
    public $ajaxUrl = '';

    /**
     * Plugin path
     *
     * @var string
     */
    public $path = '';

    /**
     * Config
     *
     * @var array
     */

    public $config = array();

    /**
     * Options
     *
     * @var array
     */
    public $options;

    /**
     * Static theme name
     *
     * @var string
     */
    public static $themeName;

    /**
     * Category files
     *
     * @var mixed
     */
    public $category;

    /**
     * Category source
     *
     * @var string
     */
    public $categoryFrom;

    /**
     * List of categories
     *
     * @var array|mixed
     */
    public $categories;

    /**
     * Categories tree
     *
     * @var array|mixed
     */
    public $categories_tree;

    /**
     * Default open category
     *
     * @var string
     */
    public $default_open;

    /**
     * Check is latest
     *
     * @var boolean|mixed
     */
    public $latest;

    /**
     * Files ordering
     *
     * @var string
     */
    public $ordering;

    /**
     * Files ordering direction
     *
     * @var string
     */
    public $orderingDirection;

    /**
     * Subcategories ordering
     *
     * @var string
     */
    public $subcategoriesOrdering;

    /**
     * WpfdThemeDefault constructor.
     */
    public function __construct()
    {
        if (!class_exists('WpfdBase')) {
            $application = Application::getInstance('Wpfd');
            $path_wpfdbase = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
            $path_wpfdbase .= DIRECTORY_SEPARATOR . 'WpfdBase.php';
            require_once $path_wpfdbase;
        }
    }

    /**
     * Get theme name
     *
     * @return string
     */
    public function getThemeName()
    {
        return $this->name;
    }

    /**
     * Set hideEmpty
     *
     * @param boolean $value Hide empty file info or not
     *
     * @return void
     */
    public function hideEmpty($value)
    {
        self::$hideEmpty = $value;
    }

    /**
     * Set theme name
     *
     * @param string $name Theme name
     *
     * @return void
     */
    public function setThemeName($name)
    {
        self::$themeName = $name;
        self::$prefix    = ($name !== 'default') ? $name . '_' : '';
    }

    /**
     * Set ajax url
     *
     * @param string $url Ajaxurl
     *
     * @return void
     */
    public function setAjaxUrl($url)
    {
        $this->ajaxUrl = $url;
    }

    /**
     * Set path
     *
     * @param string $path Plugin path
     *
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Set config
     *
     * @param array $config Configs
     *
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Get tpl path for include
     *
     * @return string
     */
    public function getTplPath()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl-default.php';
    }

    /**
     * Show category
     *
     * @param array   $options Options
     * @param boolean $search  Search flag
     *
     * @return mixed|string
     */
    public function showCategory($options, $search = false)
    {
        $showUploadForm = (isset($options['params'][self::$prefix . 'showuploadform']) && (int) $options['params'][self::$prefix . 'showuploadform'] === 1) ? true : false;
        $emptyFolderSetting = (isset($this->config['show_empty_folder']) && (int) $this->config['show_empty_folder'] === 1) ? true : false;
        $emptyFolderShowUploadForm = (empty($options['files']) && empty($options['categories'])
            && $showUploadForm && $emptyFolderSetting) ? true : false;

        if (empty($options['files']) && empty($options['categories']) && !$showUploadForm && !isset($options['ajax'])) {
            if (is_admin()) {
                return __('There are no files in this category', 'wpfd');
            } else {
                return '';
            }
        }

        $this->options   = $options;
        self::$themeName = $this->getThemeName();

        $content           = '';
        $theme             = $this;
        $files             = $this->options['files'];
        $category          = $this->options['category'];
        $categories        = $this->options['categories'];
        $categories_tree   = isset($this->options['categories_tree']) ? $this->options['categories_tree'] : array();
        $params            = $this->options['params'];
        $config            = $this->config;
        $padding           = self::getPadding($params);
        $name              = $this->getThemeName();
        if (!isset($params[self::$prefix . 'showcategorytitle'])) {
            $params[self::$prefix . 'showcategorytitle'] = '1';
        }
        $showsubcategories = (int) WpfdBase::loadValue($params, self::$prefix . 'showsubcategories', 1) === 1 ? true : false;
        $showfoldertree    = (int) WpfdBase::loadValue($params, self::$prefix . 'showfoldertree', 0) === 0 ? false : true;
        $showCategoryTitle = (int) WpfdBase::loadValue($params, self::$prefix . 'showcategorytitle', 0) === 1 ? true : false;
        $showBreadcrumb    = (int) WpfdBase::loadValue($params, self::$prefix . 'showbreadcrumb', 0) === 1 ? true : false;
        $folderTreePosition = (int) WpfdBase::loadValue($params, self::$prefix . 'showfoldertree', 0);
        switch ($folderTreePosition) {
            case 0:
                $folderTreePosition = 'folder-tree-none';
                break;
            case 1:
                $folderTreePosition = 'folder-tree-left';
                break;
            case 2:
                $folderTreePosition = 'folder-tree-top';
                break;
            case 3:
                $folderTreePosition = 'folder-tree-right';
                break;
            case 4:
                $folderTreePosition = 'folder-tree-bottom';
                break;
        }
        $catId = $category->term_id;
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catId);

        if ((int) $categoryFrom === (int) $catId) {
            $categoryFrom = false;
        } elseif (in_array($categoryFrom, wpfd_get_support_cloud())) {
            // Not allow category from cloud can use download all/selected feature
            $config['download_category'] = 0;
            $config['download_selected'] = 0;
        }

        $this->params       = $params;
        $this->category     = $category;
        $this->categoryFrom = $categoryFrom;
        $this->categories   = $categories;
        $this->categories_tree = $categories_tree;
        $this->latest       = false;
        $this->ordering     = isset($this->options['ordering']) ? $this->options['ordering'] : 'title';
        $this->orderingDirection = isset($this->options['orderingDirection']) ? $this->options['orderingDirection'] : 'desc';
        $this->subcategoriesOrdering = isset($this->options['subcategoriesOrdering']) ? : 'customorder';
        $this->default_open = isset($this->options['default_open']) ? $this->options['default_open'] : '';

        if (isset($this->options['latest'])) {
            $this->latest = $this->options['latest'];
        }
        if ($showsubcategories && !empty($categories)) {
            $categories = $this->sortCategories($categories, $params);
            $this->categories = $categories;
        }
        if (!empty($categories_tree)) {
            $categories_tree = $this->sortCategories($categories_tree, $params);
            $this->categories_tree = $categories_tree;
        }

        $ajax = false;
        if (isset($this->options['ajax'])) {
            $ajax = $this->options['ajax'];
        }

        // Load css and scripts when have something for showing
        $this->loadAssets();
        $this->loadLightboxAssets();
        $this->loadHooks();
        if (!empty($files) || $showsubcategories || $showfoldertree || $emptyFolderShowUploadForm) {
            if (!empty($category) || $search) {
                if (!empty($files) || !empty($categories) || $emptyFolderShowUploadForm) {
                    ob_start();
                    include $this->getTplPath();
                    $content = ob_get_contents();
                    ob_end_clean();
                    // Fix conflict with wpautop in VC
                    $content = str_replace(array("\n", "\r"), '', $content);
                }
            }
        }
        return $content;
    }

    /**
     * Load theme styles and scripts
     *
     * @return void
     */
    public function loadAssets()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'handlebars',
            plugins_url('app/site/assets/js/handlebars-v4.7.7.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_script(
            'wpfd-frontend',
            plugins_url('app/site/assets/js/frontend.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_localize_script(
            'wpfd-frontend',
            'wpfdfrontend',
            array(
                'pluginurl' => plugins_url('', WPFD_PLUGIN_FILE),
                'wpfdajaxurl' => $this->ajaxUrl
            )
        );
        wp_enqueue_style(
            'wpfd-material-design',
            plugins_url('app/site/assets/css/material-design-iconic-font.min.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_script(
            'wpfd-foldertree',
            plugins_url('app/site/assets/js/jaofiletree.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-foldertree',
            plugins_url('app/site/assets/css/jaofiletree.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-searchfilecategory',
            plugins_url('app/site/assets/css/search_filter.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        $path_foobar = $this->path . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'foobar';
        $path_admin_foobar = $this->path . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'foobar';
        wp_enqueue_script('wpfd-helper', plugins_url('assets/js/helper.js', $path_foobar));
        wp_localize_script('wpfd-helper', 'wpfdHelper', array(
            'fileMeasure' => WpfdHelperFile::getSupportFileMeasure()
        ));

        if (WpfdBase::checkExistTheme($this->name)) {
            $url = plugin_dir_url($this->path . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'wpfd-' . $this->name . DIRECTORY_SEPARATOR . 'foobar');
        } else {
            $url  = wpfd_abs_path_to_url(realpath(dirname(wpfd_locate_theme($this->name, 'theme.php'))) . DIRECTORY_SEPARATOR);
        }

        if (isset($this->options['shortcode_param'])) {
            if (isset($this->options['shortcode_param']['theme'])) {
                $option_theme = $this->options['shortcode_param']['theme'];
            } else {
                $option_theme = '';
            }
            wp_localize_script(
                'wpfd-frontend',
                'wpfdfrontend_'.$option_theme,
                array(
                    'shortcode_param' => $this->options['shortcode_param'],
                    'wpfdajaxnone' => wp_create_nonce('wpfd-ajax-none'),
                    'wpfdstyleurl' => $url . 'css/style.css',
                    'wpfdscripturl' => $url . 'js/script.js'
                )
            );
        }

        wp_enqueue_style('wpfd-theme-' . $this->name, $url . 'css/style.css', array(), WPFD_VERSION);
        $bg_download    = WpfdBase::loadValue($this->params, self::$prefix . 'bgdownloadlink', '');
        $color_download = WpfdBase::loadValue($this->params, self::$prefix . 'colordownloadlink', '');
        $expandedCategoryTree = false;
        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'expanded_folder_tree', 0) !== 0) {
            $expandedCategoryTree = true;
        }
        $style          = '';
        if ($bg_download !== '') {
            $style .= 'background-color:' . esc_html($bg_download) . ';border-color: ' . esc_html($bg_download) . ';';
        }
        if ($color_download !== '') {
            $style .= 'color:' . esc_html($color_download) . ';';
        }
        $css = '.wpfd-content .' .$this->name.'-download-category, .wpfd-content .'.$this->name.'-download-selected {'.$style.'}';
        wp_add_inline_style('wpfd-theme-' . $this->name, $css);
        wp_enqueue_script('wpfd-theme-' . $this->name, $url . 'js/script.js', array(), WPFD_VERSION);
        wp_enqueue_script('wpfd-resumable', plugins_url('assets/js/resumable.js', $path_admin_foobar), array(), WPFD_VERSION);
        $serverUploadLimit = min(
            10 * 1024 * 1024, // Maximum for chunks size is 10MB if other settings is greater than 10MB
            WpfdTool::parseSize(ini_get('upload_max_filesize')),
            WpfdTool::parseSize(ini_get('post_max_size'))
        );

        /**
         * Filters allow to turn on category loading message
         *
         * @param boolean
         *
         * @return boolean
         */
        $allowLoadingMessage = apply_filters('wpfd_category_loading_message', false);

        /**
         * Filters allow to expand category tree on frontend
         *
         * @param boolean
         *
         * @return boolean
         */
        $allowCategoryTreeExpanded = apply_filters('wpfd_category_tree_expanded', false);

        /**
         * Filters allow to expand category tree parent on frontend
         *
         * @param boolean
         *
         * @return boolean
         */
        $allowCategoryTreeParentExpanded = apply_filters('wpfd_category_tree_parent_expanded', false);

        /**
         * The filter allows when clicking on the category tree to automatically scroll up to the top of the element
         *
         * @param boolean
         *
         * @return boolean
         */
        $allowCategoryTreeClickScrollUp = apply_filters('wpfd_category_tree_click_scroll_up', false);

        /**
         * Filters allow to open file in new tab when clicking on file title
         *
         * @param boolean
         *
         * @return boolean
         */
        $allowOpenFileTitle = apply_filters('wpfd_file_title_open_file_in_new_tab', false);

        $offRedirectLinkDownloadImageFile = apply_filters('wpfd_off_redirect_link_download_image_file', false);

        $user = wp_get_current_user();
        $userId = isset($user->ID) ? $user->ID : 0;

        wp_localize_script('wpfd-theme-' . $this->name, 'wpfdparams', array(
            'wpfdajaxurl'          => $this->ajaxUrl,
            'wpfduploadajax'       => admin_url('admin-ajax.php'),
            'allowed'              => isset($this->config['allowedext']) ? $this->config['allowedext'] : '',
            'maxFileSize'          => isset($this->config['maxinputfile']) ? $this->config['maxinputfile'] : 0,
            'serverUploadLimit'    => ((int) $serverUploadLimit === 0) ? 10 * 1024 * 1204 : $serverUploadLimit,
            'ga_download_tracking' => $this->config['ga_download_tracking'],
            'ajaxadminurl'         => admin_url('admin-ajax.php') . '?juwpfisadmin=0',
            'allow_loading_message' => $allowLoadingMessage,
            'allow_category_tree_expanded' => $expandedCategoryTree,
            'allow_category_tree_parent_expanded' => $allowCategoryTreeParentExpanded,
            'allow_category_tree_click_scroll_up' => $allowCategoryTreeClickScrollUp,
            'allow_file_title_open_file_new_tab' => $allowOpenFileTitle,
            'wpfd_plugin_url' => WPFD_PLUGIN_URL,
            'wpfd_user_login_id' => $userId,
            'site_url' => get_site_url(),
            'translates'           => array(
                'download_selected' => esc_html__('Download selected', 'wpfd'),
                'msg_upload_file'   => esc_html__('New File(s) uploaded with success!', 'wpfd'),
                'msg_loading'       => esc_html__('Please wait while your file(s) is uploaded!', 'wpfd'),
                'msg_search_file_category_placeholder' => esc_html__('Search in file category...', 'wpfd'),
                'msg_search_file_category_search' => esc_html__('Search', 'wpfd'),
                'wpfd_all_categories' => esc_html__('All Categories', 'wpfd'),
            ),
            'offRedirectLinkDownloadImageFile' => $offRedirectLinkDownloadImageFile
        ));
    }

    /**
     * Load Lightbox style and scripts
     *
     * @return void
     */
    public function loadLightboxAssets()
    {
        if ($this->config['use_google_viewer'] === 'lightbox') {
            wpfd_enqueue_assets();
        }
    }

    /**
     * Load template hooks
     *
     * @return void
     */
    public function loadHooks()
    {
        $name              = self::$themeName;
        $showcategorytitle = (int) WpfdBase::loadValue($this->params, self::$prefix . 'showcategorytitle', 1) === 1 ? true : false;
        $showsubcategories = (int) WpfdBase::loadValue($this->params, self::$prefix . 'showsubcategories', 1) === 1 ? true : false;
        $globalConfig      = get_option('_wpfd_global_config');

        /**
         * Action fire before templates hooked
         *
         * @hookname wpfd_{$themeName}_before_template_hooks
         */
        do_action('wpfd_' . $name . '_before_template_hooks');

        /* Theme Content Output  */
        add_action('wpfd_' . $name . '_before_theme_content', array(__CLASS__, 'outputContentWrapper'), 10, 1);
        add_action('wpfd_' . $name . '_before_theme_content', array(__CLASS__, 'outputContentHeader'), 20, 1);

        // Before files loop handlebars
        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'outputCategoriesWrapper'), 10, 2);
        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'showCategoryTitleHandlebars'), 20, 2);
        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'showCategoryDescHandlebars'), 25, 2);
        if ($showsubcategories && !$this->latest) {
            add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'showCategoriesHandlebars'), 30, 2);
        }
        add_action('wpfd_' . $name . '_before_files_loop_handlebars', array(__CLASS__, 'outputCategoriesWrapperEnd'), 90, 2);
        // Before files loop
        add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'outputCategoriesWrapper'), 10, 2);
        if ($showcategorytitle && !$this->latest) {
            add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'showCategoryTitle'), 20, 2);
        }
        add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'showCategoryDesc'), 25, 2);
        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
        if (is_countable($this->categories) && count($this->categories) && $showsubcategories && !$this->latest) {
            add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'showCategories'), 30, 2);
        }
        add_action('wpfd_' . $name . '_before_files_loop', array(__CLASS__, 'outputCategoriesWrapperEnd'), 90, 2);

        /* Folder Tree */
        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showfoldertree', 0) !== 0 && !$this->latest) {
            add_action('wpfd_' . $name . '_folder_tree', array(__CLASS__, 'showTree'), 10, 2);
        }
        /* File Block */
        // File content
        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showtitle', 1) === 1) {
            add_action('wpfd_' . $name . '_file_content', array(__CLASS__, 'showTitle'), 20, 3);
            add_action('wpfd_' . $name . '_file_content_handlebars', array(__CLASS__, 'showTitleHandlebars'), 20, 2);
        }

        add_action('wpfd_' . $name . '_file_content_handlebars', array(__CLASS__, 'showIconHandlebars'), 10, 2);
        add_action('wpfd_' . $name . '_file_content', array(__CLASS__, 'showIcon'), 10, 3);

        // File info
        $theme_column = (isset($this->params['theme_column']) && !empty($this->params['theme_column'])) ? $this->params['theme_column'] : array();
        if (!empty($theme_column) && $name === 'table') {
            foreach ($theme_column as $key => $value) {
                switch ($value) {
                    case 'description':
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showDescription'), $col_priority, 3);
                        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showDescriptionHandlebars'), $col_priority, 2);
                        break;
                    case 'category':
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showCategoryTable'), $col_priority, 3);
                        break;
                    case 'version':
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showVersion'), $col_priority, 3);
                        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showVersionHandlebars'), $col_priority, 2);
                        break;
                    case 'size':
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showSize'), $col_priority, 3);
                        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showSizeHandlebars'), $col_priority, 2);
                        break;
                    case 'hits':
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showHits'), $col_priority, 3);
                        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showHitsHandlebars'), $col_priority, 2);
                        break;
                    case 'date added':
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showCreated'), $col_priority, 3);
                        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showCreatedHandlebars'), $col_priority, 2);
                        break;
                    case 'download':
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showColDownloadTable'), $col_priority, 3);
                        break;
                    default:
                        break;
                }
                $col_priority = $col_priority + 10;
            }
        } else {
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdescription', 1) === 1) {
                add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showDescription'), 10, 3);
                add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showDescriptionHandlebars'), 10, 2);
            }

            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showversion', 1) === 1) {
                add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showVersion'), 20, 3);
                add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showVersionHandlebars'), 20, 2);
            }

            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showsize', 1) === 1) {
                add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showSize'), 30, 3);
                add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showSizeHandlebars'), 30, 2);
            }

            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showhits', 1) === 1) {
                add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showHits'), 40, 3);
                add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showHitsHandlebars'), 40, 2);
            }

            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdateadd', 1) === 1) {
                add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showCreated'), 50, 3);
                add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showCreatedHandlebars'), 50, 2);
            }

            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdatemodified', 0) === 1) {
                add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showModified'), 60, 3);
                add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showModifiedHandlebars'), 60, 2);
            }
        }

        // File buttons
        if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdownload', 1) === 1 && wpfd_can_download_files()) {
            add_action('wpfd_' . $name . '_buttons', array(__CLASS__, 'showDownload'), 10, 3);
            add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'showDownloadHandlebars'), 10, 2);
        }

        if ($this->config['use_google_viewer'] !== 'no' && wpfd_can_preview_files()) {
            add_action('wpfd_' . $name . '_buttons_handlebars', array(__CLASS__, 'showPreviewHandlebars'), 20, 2);
            add_action('wpfd_' . $name . '_buttons', array(__CLASS__, 'showPreview'), 20, 3);
        }
        /* End File Block */

        // End theme content
        add_action('wpfd_' . $name . '_after_theme_content', array(__CLASS__, 'outputContentWrapperEnd'), 10, 1);

        /**
         * Action fire after template hooked
         *
         * @hookname wpfd_{$themeName}_after_template_hooks
         */
        do_action('wpfd_' . $name . '_after_template_hooks');

        // Call custom hooks
        $this->loadCustomHooks();

        /**
         * Action fire after custom hooked
         *
         * @hookname wpfd_{$themeName}_after_custom_hooks
         */
        do_action('wpfd_' . $name . '_after_custom_hooks');
    }

    /**
     * Load custom hooks and filters
     *
     * @return void
     */
    public function loadCustomHooks()
    {
    }

    /**
     * Print content wrapper
     *
     * @param object $theme This theme object
     *
     * @return void
     */
    public static function outputContentWrapper($theme)
    {
        $name   = self::$themeName;
        $output = '';
        $installedVersion = get_option('wpfd_version');
        $params = isset($theme->params) ? $theme->params : array();
        $prefix = ($name === 'default') ? '' : $name . '_';
        $bgColor = isset($params[$prefix . 'bgcolor']) ? $params[$prefix . 'bgcolor'] : 'transparent';
        $html   = sprintf(
            '<div class="wpfd-content wpfd-content-' . self::$themeName . ' wpfd-content-multi" data-category="%s" 
            style="background-color: ' . $bgColor . '">',
            (string) esc_attr($theme->category->term_id)
        );
        /**
         * Filter to change content wrapper output
         *
         * @param string Content wrapper
         * @param object Current theme object
         *
         * @hookname wpfd_{$themeName}_content_wrapper
         *
         * @return string
         */
        $output .= apply_filters('wpfd_' . $name . '_content_wrapper', $html, $theme);

        // Print hidden input
        $sc = esc_attr($theme->category->term_id);
        $html = sprintf(
            '<input type="hidden" id="current_category_' . $sc . '" value="%s" />
                    <input type="hidden" id="current_category_slug_' . $sc . '" value="%s" />
                    <input type="hidden" id="current_ordering_' . $sc . '" value="%s" />
                    <input type="hidden" id="current_ordering_direction_' . $sc . '" value="%s" />
                    <input type="hidden" id="page_limit_' . $sc . '" value="%s" />',
            esc_attr($theme->category->term_id),
            esc_attr($theme->category->slug),
            esc_attr($theme->ordering),
            esc_attr($theme->orderingDirection),
            esc_attr($theme->config['paginationnunber'])
        );


        /**
         * Filters to print hidden input below content wrapper
         *
         * @param string Input html
         * @param object Current theme object
         *
         * @hookname wpfd_{$themeName}_content_wrapper_input
         *
         * @return string
         */
        $output .= apply_filters('wpfd_' . $name . '_content_wrapper_input', $html, $theme);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Print content header
     *
     * @param object $theme This theme object
     *
     * @return void
     */
    public static function outputContentHeader($theme)
    {
        $output           = '';
        $showDownloadAll  = (int) $theme->config['download_category'] === 1 ? true : false;
        $display_download = (empty($theme->options['files']) || $showDownloadAll === false) ? 'display-download-category' : '';
        $globalConfig     = get_option('_wpfd_global_config');
        $rootThemeTypes   = get_option('wpfd_root_theme_types', array());
        $cloneThemeType   = (isset($theme->name) && !empty($rootThemeTypes) && array_key_exists($theme->name, $rootThemeTypes)) ? $rootThemeTypes[$theme->name] : 'none';
        $showCategoryTitle = (int) WpfdBase::loadValue($theme->params, self::$prefix . 'showcategorytitle', 1);
        $showBreadcrumb = (int) WpfdBase::loadValue($theme->params, self::$prefix . 'showbreadcrumb', 1);
        $displayFileSearch = (int) WpfdBase::loadValue($theme->params, self::$prefix . 'displayfilesearch', 0);
        $categoryType = apply_filters('wpfdAddonCategoryFrom', $theme->category->term_id);
        $emptyMessage = isset($globalConfig['empty_message']) ? (int) $globalConfig['empty_message'] : 0;
        $emptyMessageVal = isset($globalConfig['empty_message_val']) ? $globalConfig['empty_message_val']
            : esc_html__('This file category has no files to display', 'wpfd');
        $showEmptyFolder = isset($globalConfig['show_empty_folder']) ? (int) $globalConfig['show_empty_folder'] : 0;
        $paginationNumber = isset($globalConfig['paginationnunber']) ? intval($globalConfig['paginationnunber']) : 15;
        $output .= '<input type="hidden" id="wpfd_display_empty_category_message" value="' . esc_attr($emptyMessage) . '" />';
        $output .= '<input type="hidden" id="wpfd_empty_category_message_val" value="' . esc_attr($emptyMessageVal) . '" />';
        $output .= '<input type="hidden" id="wpfd_display_empty_folder" value="' . esc_attr($showEmptyFolder) . '" />';
        $output .= '<input type="hidden" id="wpfd_is_empty_subcategories" value="1" />';
        $output .= '<input type="hidden" id="wpfd_is_empty_files" value="1" />';
        $output .= '<input type="hidden" id="wpfd_root_category_id" class="wpfd_root_category_id" value="'. esc_attr($theme->category->term_id) .'" />';
        $output .= '<input type="hidden" id="wpfd_root_category_theme" class="wpfd_root_category_theme" value="'. esc_attr($theme->name) .'" />';
        $output .= '<input type="hidden" id="wpfd_root_category_clone_theme_type" class="wpfd_root_category_clone_theme_type" value="'. esc_attr($cloneThemeType) .'" />';
        $output .= '<input type="hidden" id="wpfd_root_category_type" class="wpfd_root_category_type" value="'. esc_attr($categoryType) .'" />';
        if (isset($theme->default_open) && !empty($theme->default_open)) {
            $output .= '<input type="hidden" id="wpfd_root_category_default_open" class="wpfd_root_category_default_open" value="'. esc_attr($theme->default_open) .'" />';
        }

        if ($displayFileSearch) { ?>
            <?php
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
            $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $theme->category->term_id);
            $categorySearchFileId = $theme->category->term_id;
            if ($categoryFrom === 'googleDrive' && has_filter('wpfdAddonSearchCloud', 'wpfdAddonSearchCloud')) {
                $categorySearchFileId = WpfdAddonHelper::getGoogleDriveIdByTermId($theme->category->term_id);
            } elseif ($categoryFrom === 'googleTeamDrive' && has_filter('wpfdAddonSearchCloudTeamDrive', 'wpfdAddonSearchCloudTeamDrive')) {
                $categorySearchFileId = WpfdAddonHelper::getGoogleTeamDriveIdByTermId($theme->category->term_id);
            } elseif ($categoryFrom === 'dropbox' && has_filter('wpfdAddonSearchDropbox', 'wpfdAddonSearchDropbox')) {
                $categorySearchFileId = WpfdAddonHelper::getDropboxIdByTermId($theme->category->term_id);
            } elseif ($categoryFrom === 'onedrive' && has_filter('wpfdAddonSearchOneDrive', 'wpfdAddonSearchOneDrive')) {
                $categorySearchFileId = WpfdAddonHelper::getOneDriveIdByTermId($theme->category->term_id);
            } elseif ($categoryFrom === 'onedrive_business' && has_filter('wpfdAddonSearchOneDriveBusiness', 'wpfdAddonSearchOneDriveBusiness')) {
                $categorySearchFileId = WpfdAddonHelper::getOneDriveBusinessIdByTermId($theme->category->term_id);
            }

            if ($categorySearchFileId === 'all_0') {
                $categorySearchFileId = 0;
            }

            $output .= '<input type="hidden" id="wpfd_root_category_display_file_search" class="wpfd_root_category_display_file_search" value="1" />';
            ?>
            <form action="" id="adminForm-<?php echo esc_html($theme->category->term_id); ?>" class="wpfd-adminForm wpfd-form-search-file-category" name="adminForm" method="post">
                <div id="loader" style="display:none; text-align: center">
                    <img src="<?php echo esc_url(WPFD_PLUGIN_URL. '/app/site/assets/images/searchloader.svg'); ?>" style="margin: 0 auto"/>
                </div>
                <div class="box-search-filter wpfd-category-search-section">
                    <div class="searchSection">
                        <div class="only-file input-group clearfix wpfd_search_input" id="Search_container">
                            <img src="<?php echo esc_url(WPFD_PLUGIN_URL . '/app/site/assets/images/search-24.svg'); ?>" class="material-icons wpfd-icon-search wpfd-search-file-category-icon" />
                            <input type="text" class="pull-left required txtfilename" name="q" id="txtfilename" autocomplete="off" placeholder="<?php esc_html_e('Search in file category...', 'wpfd'); ?>" value="" />
                        </div>
                        <button id="btnsearchbelow" class="btnsearchbelow wpfd-btnsearchbelow" type="button"><?php esc_html_e('Search', 'wpfd'); ?></button>
                    </div>
                    <input type="hidden" id="filter_catid" class="chzn-select filter_catid" name="catid" value="<?php echo esc_attr($categorySearchFileId); ?>" data-cattype="" data-slug="" />
                    <input type="hidden" name="theme" value="<?php echo esc_html($theme->name); ?>">
                    <input type="hidden" name="limit" value="<?php echo esc_attr($paginationNumber); ?>">
                    <div id="wpfd-results" class="wpfd-results list-results"></div>
                </div>
            </form>
        <?php }

        if ($showBreadcrumb === 1 && !$theme->latest) {
            if ($theme->config['download_category'] && !$theme->categoryFrom && wpfd_can_download_files()) {
                $output .= sprintf(
                    '<a data-catid="" class="' . self::$themeName . '-download-category %s" href="%s">%s
                                <i class="zmdi zmdi-check-all wpfd-download-category"></i>
                            </a>',
                    esc_attr($display_download),
                    esc_url($theme->category->linkdownload_cat),
                    esc_html__('Download all ', 'wpfd')
                );
            }
            $output .= sprintf(
                '<ul class="breadcrumbs wpfd-breadcrumbs-' . self::$themeName . ' head-category-' . self::$themeName . '">
                            <li class="active">%s</li>
                        </ul>',
                esc_html($theme->category->name)
            );
        } elseif ($showDownloadAll && !$theme->categoryFrom && !$theme->latest) {
            if ($showDownloadAll && !$theme->categoryFrom && wpfd_can_download_files()) {
                $output .= sprintf(
                    '<a data-catid=""
                       class="' . self::$themeName . '-download-category %s"
                       href="%s">%s
                        <i class="zmdi zmdi-check-all wpfd-download-category"></i>
                    </a>',
                    $display_download,
                    esc_url($theme->category->linkdownload_cat),
                    esc_html__('Download all ', 'wpfd')
                );
            }
            if ($showBreadcrumb === 1) {
                $output .= '<ul class="head-category head-category-' . self::$themeName . '"><li>&nbsp;</li></ul>';
            }
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Print file description handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showDescriptionHandlebars($config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdescription', 1) === 1) {
            $name     = self::$themeName;

            $template = array(
                'html' => '{{#if description}}<div class="file-desc">%value$s</div>{{/if}}',
                'args' => array(
                    'value' => '{{{description}}}'
                )
            );

            /**
             * Global filter to change html and arguments of description handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_info_description_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_description_handlebars_args', $template, $config, $params);
            /**
             * Filter to change html and arguments of description handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_description_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_description_handlebars_args', $template, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print file description
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showDescription($file, $config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdescription', 1) === 1) {
            $name = self::$themeName;
            $template = array(
                'html' => '<div class="file-desc">%value$s</div>',
                'args' => array(
                    'value' => isset($file->description) ? wpfd_esc_desc($file->description) : ''
                )
            );
            /**
             * Global filter to change html and arguments of description
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_info_description_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_description_args', $template, $file, $config, $params);
            /**
             * Filter to change html and arguments of description
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_description_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_description_args', $template, $file, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print category of file
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCategoryTable($file, $config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showcategorytable', 1) === 1) {
            $wpfdTaxonomy = 'wpfd-category';
            $wpfdTerms = get_term($file->catid, $wpfdTaxonomy);
            if (!empty($wpfdTerms) && !is_wp_error($wpfdTerms)) {
                $breadcrumbs = array();
                $breadcrumbs[] = $wpfdTerms->name;
                $ancestors = get_ancestors($file->catid, $wpfdTaxonomy);
                if ($ancestors) {
                    foreach ($ancestors as $ancestor_id) {
                        $ancestor = get_term($ancestor_id, $wpfdTaxonomy);

                        if ($ancestor && !is_wp_error($ancestor)) {
                            $breadcrumbs[] = $ancestor->name;
                        }
                    }
                }

                $breadcrumbs = array_reverse($breadcrumbs);
                $catTooltip = '';
                if (count($breadcrumbs) > 1) {
                    $catTooltip = implode(' > ', $breadcrumbs);
                }
                $catFile = $wpfdTerms->name;
            } else {
                $catTooltip = '';
                $catFile = '';
            }

            $name = self::$themeName;
            $template = array(
                'html' => '<td class="file_category"><span class="wpfd-results-tooltip" title="%title$s">%value$s</span></td>',
                'args' => array(
                    'title' => $catTooltip,
                    'value' => $catFile
                )
            );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print version handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showVersionHandlebars($config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showversion', 1) === 1) {
            $name = self::$themeName;

            $template = array(
                'html' => '{{#if versionNumber}}<div class="file-version"><span>%text$s</span> %value$s</div>{{/if}}',
                'args' => array(
                    'text'  => esc_html__('Version:', 'wpfd'),
                    'value' => '{{versionNumber}}'
                )
            );
            /**
             * Global filter to change html and arguments of version handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_info_version_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_version_handlebars_args', $template, $config, $params);
            /**
             * Filter to change html and arguments of version handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_version_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_version_handlebars_args', $template, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print version
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showVersion($file, $config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showversion', 1) === 1) {
            if (trim($file->versionNumber) === '' && static::$hideEmpty === true) {
                echo '';
            } else {
                $name     = self::$themeName;
                $template = array(
                    'html' => '<div class="file-version"><span>%text$s</span> %value$s</div>',
                    'args' => array(
                        'text'  => esc_html__('Version:', 'wpfd'),
                        'value' => esc_html($file->versionNumber)
                    )
                );
                /**
                 * Globa filter to change html and arguments of version
                 *
                 * @param array  Template array
                 * @param object Current file object
                 * @param array  Main config
                 * @param array  Current category config
                 *
                 * @hookname wpfd_file_info_version_args
                 *
                 * @return array
                 */
                $template = apply_filters('wpfd_file_info_version_args', $template, $file, $config, $params);
                /**
                 * Filter to change html and arguments of version
                 *
                 * @param array  Template array
                 * @param object Current file object
                 * @param array  Main config
                 * @param array  Current category config
                 *
                 * @hookname wpfd_{$themeName}_file_info_version_args
                 *
                 * @return array
                 */
                $template = apply_filters('wpfd_' . $name . '_file_info_version_args', $template, $file, $config, $params);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
                echo self::render($template['html'], $template['args']);
            }
        }
    }

    /**
     * Print size handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showSizeHandlebars($config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showsize', 1) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<div class="file-size"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Size:', 'wpfd'),
                    'value' => '{{bytesToSize size}}'
                )
            );
            /**
             * Global filter to change html and arguments of size handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_info_size_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_size_handlebars_args', $template, $config, $params);
            /**
             * Filter to change html and arguments of size handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_size_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_size_handlebars_args', $template, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print size
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showSize($file, $config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showsize', 1) === 1) {
            $name     = self::$themeName;
            $fileSize = (strtolower($file->size) === 'n/a' || $file->size <= 0) ? 'N/A' : WpfdHelperFile::bytesToSize($file->size);
            $template = array(
                'html' => '<div class="file-size"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Size:', 'wpfd'),
                    'value' => esc_html($fileSize)
                )
            );
            /**
             * Global filter to change html and arguments of size
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_info_size_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_size_args', $template, $file, $config, $params);
            /**
             * Filter to change html and arguments of size
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_size_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_size_args', $template, $file, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print hits handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showHitsHandlebars($config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showhits', 1) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<div class="file-hits"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Hits:', 'wpfd'),
                    'value' => '{{hits}}'
                )
            );
            /**
             * Global filter to change html and arguments of hits handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_info_hits_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_hits_handlebars_args', $template, $config, $params);

            /**
             * Filter to change html and arguments of hits handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_hits_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_hits_handlebars_args', $template, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print hits
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showHits($file, $config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showhits', 1) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<div class="file-hits"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Hits:', 'wpfd'),
                    'value' => esc_html($file->hits)
                )
            );
            /**
             * Global filter to change html and arguments of hits
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_info_hits_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_hits_args', $template, $file, $config, $params);
            /**
             * Filter to change html and arguments of hits
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_hits_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_hits_args', $template, $file, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print created date handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showCreatedHandlebars($config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdateadd', 0) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<div class="file-dated"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Date added:', 'wpfd'),
                    'value' => '{{created}}'
                )
            );
            /**
             * Global filter to change html and arguments of created handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_info_created_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_created_handlebars_args', $template, $config, $params);

            /**
             * Filter to change html and arguments of created handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_created_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_created_handlebars_args', $template, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print created date
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCreated($file, $config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdateadd', 0) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<div class="file-dated"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Date added:', 'wpfd'),
                    'value' => esc_html($file->created)
                )
            );
            /**
             * Global filter to change html and arguments of created
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_info_created_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_created_args', $template, $file, $config, $params);
            /**
             * Filter to change html and arguments of created
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_created_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_created_args', $template, $file, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print modified date handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showModifiedHandlebars($config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdatemodified', 0) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<div class="file-dated"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Date modified:', 'wpfd'),
                    'value' => '{{modified}}'
                )
            );
            /**
             * Global filter to change html and arguments of modified handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_info_modified_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_modified_handlebars_args', $template, $config, $params);

            /**
             * Filter to change html and arguments of modified handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_modified_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_modified_handlebars_args', $template, $config, $params);

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print modified date
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showModified($file, $config, $params)
    {
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdatemodified', 0) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<div class="file-dated"><span>%text$s</span> %value$s</div>',
                'args' => array(
                    'text'  => esc_html__('Date modified:', 'wpfd'),
                    'value' => esc_html($file->modified)
                )
            );

            /**
             * Global filter to change html and arguments of modified
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_info_modified_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_modified_args', $template, $file, $config, $params);

            /**
             * Filter to change html and arguments of modified
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_modified_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_modified_args', $template, $file, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
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
        if ($config['custom_icon']) {
            $html = '{{#if file_custom_icon}}
                    <div class="icon-custom"><img src="{{file_custom_icon}}" /></div>
                    {{else}}
                    <div class="ext ext-{{ext}}' . $iconSet . '"><span class="txt">{{ext}}</span></div>
                    {{/if}}';
        } else {
            $html = '<div class="ext ext-{{ext}}' . $iconSet . '"><span class="txt">{{ext}}</span></div>';
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
         */
        $html = apply_filters('wpfd_' . $name . '_file_info_icon_hanlebars', $html, $config, $params);

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
         */
        $html = apply_filters('wpfd_' . $name . '_file_info_icon_hanlebars', $html, $config, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $html;
    }

    /**
     * Print icon
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showIcon($file, $config, $params)
    {
        $html = '';
        $name = self::$themeName;
        if ($config['custom_icon'] && isset($file->file_custom_icon) && $file->file_custom_icon !== '') {
            $html = sprintf(
                '<div class="icon-custom"><img src="%s" /></div>',
                esc_url($file->file_custom_icon)
            );
        } else {
            $html = sprintf(
                '<div class="ext ext-%s%s"><span class="txt">%s</span></div>',
                esc_attr(strtolower($file->ext)),
                (isset($config['icon_set']) && $config['icon_set'] !== 'default') ? ' wpfd-icon-set-' . esc_attr($config['icon_set']) : '',
                esc_html($file->ext)
            );
        }

        /**
         * Global filter to change icon html
         *
         * @param string Output html for handlebars template
         * @param object Current file object
         * @param array  Main config
         * @param array  Current category config
         *
         * @hookname wpfd_file_info_icon_html
         *
         * @return string
         */
        $html = apply_filters('wpfd_file_info_icon_html', $html, $file, $config, $params);

        /**
         * Filter to change icon html
         *
         * @param string Output html for handlebars template
         * @param object Current file object
         * @param array  Main config
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_file_info_icon_html
         *
         * @return string
         */
        $html = apply_filters('wpfd_' . $name . '_file_info_icon_html', $html, $file, $config, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $html;
    }

    /**
     * Print title handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showTitleHandlebars($config, $params)
    {
        $selectFileInput = '';
        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files()) {
            $selectFileInput = '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="{{ID}}" /><span></span></label>';
        }
        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showtitle', 1) === 1) {
            $name     = self::$themeName;
            $template = array(
                'html' => '<h3>' . $selectFileInput . '<a href="%url$s" '.$rel.' %data$s class="wpfd_downloadlink" title="%title$s" target="%target$s">%text$s</a></h3>',
                'args' => array(
                    'url'   => '{{linkdownload}}',
                    'data'  => apply_filters('wpfd_download_data_attributes_handlebars', ''),
                    'title' => '{{post_title}}',
                    'text'  => '{{{crop_title}}}',
                    'target' => '{{#if remote_file}}_blank{{/if}}'
                )
            );
            /**
             * Global filter to change html and arguments of title handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_info_title_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_title_handlebars_args', $template, $config, $params);

            /**
             * Filter to change html and arguments of title handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_title_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_title_handlebars_args', $template, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print title
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showTitle($file, $config, $params)
    {
        $selectFileInput = '';
        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files() && is_numeric($file->ID)) {
            $selectFileInput = '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="' . $file->ID . '" data-catid="' . $file->catid . '" /><span></span></label>';
        }
        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showtitle', 1) === 1) {
            $name     = self::$themeName;
            $attributes = apply_filters('wpfd_download_data_attributes', array(), $file);
            $data = implode(' ', $attributes);
            $template = array(
                'html' => '<h3>' . $selectFileInput . '<a href="%url$s" '.$rel.' %data$s class="wpfd_downloadlink" title="%title$s" target="%target$s">%text$s</a></h3>',
                'args' => array(
                    'url'   => esc_url($file->linkdownload),
                    'data'  => $data,
                    'title' => esc_html($file->post_title),
                    'text'  => esc_html($file->crop_title),
                    'target' => (isset($file->remote_file) && $file->remote_file === true) ? '_blank' : ''
                )
            );
            /**
             * Global filter to change html and arguments of title
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_info_title_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_info_title_args', $template, $file, $config, $params);

            /**
             * Filter to change html and arguments of title
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_info_title_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_info_title_args', $template, $file, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Show column download on table theme
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showColDownloadTable($file, $config, $params)
    {
        $name           = self::$themeName;
        $template = array(
            'html' => '<td class="file_download_tbl col-download">',
            'args' => array()
        );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render($template['html'], $template['args']);
        do_action('wpfd_' . $name . '_buttons', $file, $config, $params);
        $template['html'] = '</td>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render($template['html'], $template['args']);
    }

    /**
     * Print download button handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showDownloadHandlebars($config, $params)
    {
        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdownload', 1) === 1) {
            $name           = self::$themeName;
            $bg_download    = WpfdBase::loadValue($params, self::$prefix . 'bgdownloadlink', '');
            $color_download = WpfdBase::loadValue($params, self::$prefix . 'colordownloadlink', '');
            $bg_download = apply_filters('wpfd_download_button_background_color_handlebars', $bg_download);
            $style          = '';
            if ($bg_download !== '') {
                $style .= 'background-color:' . esc_html($bg_download) . ';';
            }
            if ($color_download !== '') {
                $style .= 'color:' . esc_html($color_download) . ';';
            }
            $template = array(
                'html' => '<a class="%class$s" %data$s href="%url$s" '.$rel.' style="%style$s" target="%target$s">%text$s%icon$s</a>',
                'args' => array(
                    'class' => 'downloadlink wpfd_downloadlink',
                    'data'  => apply_filters('wpfd_download_data_attributes_handlebars', ''),
                    'url'   => '{{linkdownload}}',
                    'style' => $style,
                    'text'  => apply_filters('wpfd_download_text_handlebars', esc_html__('Download', 'wpfd'), '{{post_title}}'),
                    'icon'  => apply_filters('wpfd_download_icon_handlebars', '<i class="zmdi zmdi-cloud-download wpfd-download"></i>'),
                    'target' => '{{#if remote_file}}_blank{{/if}}'
                )
            );
            /**
             * Global filter to change html and arguments of download button handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_file_download_button_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_download_button_handlebars_args', $template, $config, $params);

            /**
             * Filter to change html and arguments of download button handlebars
             *
             * @param array Template array
             * @param array Main config
             * @param array Current category config
             *
             * @hookname wpfd_{$themeName}_file_download_button_handlebars_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_download_button_handlebars_args', $template, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print download button
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showDownload($file, $config, $params)
    {
        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }
        if ((int) WpfdBase::loadValue($params, self::$prefix . 'showdownload', 1) === 1) {
            $name           = self::$themeName;
            $bg_download    = WpfdBase::loadValue($params, self::$prefix . 'bgdownloadlink', '');
            $color_download = WpfdBase::loadValue($params, self::$prefix . 'colordownloadlink', '');
            $bg_download = apply_filters('wpfd_download_button_background_color', $bg_download, $file);
            $style          = '';
            if ($bg_download !== '') {
                $style .= 'background-color:' . esc_html($bg_download) . ';';
            }
            if ($color_download !== '') {
                $style .= 'color:' . esc_html($color_download) . ';';
            }
            $attributes = apply_filters('wpfd_download_data_attributes', array(), $file);
            $data = implode(' ', $attributes);
            $template = array(
                'html' => '<a class="%class$s" %data$s href="%url$s" '.$rel.' style="%style$s" target="%target$s">%text$s%icon$s</a>',
                'args' => array(
                    'class' => 'downloadlink wpfd_downloadlink',
                    'data' => $data,
                    'url'   => esc_url($file->linkdownload),
                    'style' => $style,
                    'text'  => apply_filters('wpfd_download_text', esc_html__('Download', 'wpfd'), $file),
                    'icon'  => apply_filters('wpfd_download_icon', '<i class="zmdi zmdi-cloud-download wpfd-download"></i>', $file),
                    'target' => (isset($file->remote_file) && $file->remote_file === true) ? '_blank' : ''
                )
            );
            /**
             * Global filter to change html and arguments of download button
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_download_button_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_download_button_args', $template, $file, $config, $params);

            /**
             * Filter to change html and arguments of download button
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_download_button_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_download_button_args', $template, $file, $config, $params);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
            echo self::render($template['html'], $template['args']);
        }
    }

    /**
     * Print preview button handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showPreviewHandlebars($config, $params)
    {
        $output      = '';
        $name        = self::$themeName;
        $viewer_attr = 'openlink wpfdlightbox wpfd_previewlink';
        $target      = '';
        if ((string) $config['use_google_viewer'] === 'tab') {
            $viewer_attr = 'openlink wpfd_previewlink';
            $target      = '_blank';
        }

        // Detect if the user is on a mobile device
        if (isset($_SERVER) && is_array($_SERVER) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $isMobile = preg_match('/(android|iphone|ipad|mobile|windows phone)/i', $_SERVER['HTTP_USER_AGENT']);
        } else {
            $isMobile = false;
        }

        if ($isMobile) {
            $viewer_attr .= ' mobile';
        }

        $viewer_attr = apply_filters('wpfd_preview_classes_handlebars', $viewer_attr);
        $output   .= '{{#if openpdflink}}';

        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }

        $template = array(
            'html' => '<a class="%class$s" href="%url$s" '.$rel.' target="%target$s">%text$s
                            %icon$s
                        </a>',
            'args' => array(
                'class'  => esc_html($viewer_attr),
                'url'    => '{{openpdflink}}',
                'target' => esc_html($target),
                'text'   => apply_filters('wpfd_preview_text_handlebars', esc_html__('Preview', 'wpfd')),
                'icon'   => '<i class="zmdi zmdi-filter-center-focus wpfd-preview"></i>'
            )
        );

        /**
         * Global filter to change html and arguments of open pdf button handlebars
         *
         * @param array Template array
         * @param array Main config
         * @param array Current category config
         *
         * @hookname wpfd_file_open_pdf_button_handlebars_args
         *
         * @return array
         */
        $template = apply_filters('wpfd_file_open_pdf_button_handlebars_args', $template, $config, $params);

        /**
         * Filter to change html and arguments of open pdf button handlebars
         *
         * @param array Template array
         * @param array Main config
         * @param array Current category config
         *
         * @hookname wpfd_{$themeName}_file_open_pdf_button_handlebars_args
         *
         * @return array
         */
        $template = apply_filters('wpfd_' . $name . '_file_open_pdf_button_handlebars_args', $template, $config, $params);

        $output   .= self::render($template['html'], $template['args']);
        $output   .= '{{else}}';
        $template = array(
            'html' => '{{#if viewerlink}}<a
                            href="%url$s"
                            '.$rel.'
                            class="%class$s"
                            target="%target$s"
                            data-id="{{ID}}"
                            data-catid="{{catid}}"
                            data-file-type="{{ext}}">%text$s%icon$s
                        </a>{{/if}}',
            'args' => array(
                'url'    => '{{viewerlink}}',
                'class'  => esc_attr($viewer_attr),
                'target' => esc_attr($target),
                'text'   => apply_filters('wpfd_preview_text_handlebars', esc_html__('Preview', 'wpfd')),
                'icon'   => '<i class="zmdi zmdi-filter-center-focus wpfd-preview"></i>'
            )
        );
        /**
         * Global filter to change html and arguments of preview button handlebars
         *
         * @param array Template array
         * @param array Main config
         * @param array Current category config
         *
         * @hookname wpfd_file_preview_button_handlebars_args
         *
         * @return array
         */
        $template = apply_filters('wpfd_file_preview_button_handlebars_args', $template, $config, $params);

        /**
         * Filter to change html and arguments of preview button handlebars
         *
         * @param array Template array
         * @param array Main config
         * @param array Current category config
         *
         * @hookname wpfd_{$themeName}_file_preview_button_handlebars_args
         *
         * @return array
         */
        $template   = apply_filters('wpfd_' . $name . '_file_preview_button_handlebars_args', $template, $config, $params);

        $output .= self::render($template['html'], $template['args']);
        $output .= '{{/if}}';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Print preview button
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showPreview($file, $config, $params)
    {
        $output = '';
        $name   = self::$themeName;

        if (!isset($file->viewerlink) && !isset($file->openpdflink)) {
            return;
        }

        // Detect if the user is on a mobile device
        if (isset($_SERVER) && is_array($_SERVER) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $isMobile = preg_match('/(android|iphone|ipad|mobile|windows phone)/i', $_SERVER['HTTP_USER_AGENT']);
        } else {
            $isMobile = false;
        }

        $viewer_attr = 'openlink wpfdlightbox wpfd_previewlink';
        $target      = '';

        if (isset($file->viewer_type) && $file->viewer_type === 'tab') {
            $viewer_attr = 'openlink wpfd_previewlink';
            $target      = '_blank';
        }

        if ($isMobile) {
            $viewer_attr .= ' mobile';
        }

        $viewer_attr = apply_filters('wpfd_preview_classes', $viewer_attr, $file);
        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }

        if (isset($file->openpdflink)) {
            $template = array(
                'html' => '<a class="%class$s" href="%url$s" '.$rel.' target="%target$s">%text$s
                            %icon$s
                        </a>',
                'args' => array(
                    'class'  => esc_html($viewer_attr),
                    'url'    => esc_url($file->openpdflink),
                    'target' => esc_html($target),
                    'text'   => apply_filters('wpfd_preview_text', esc_html__('Preview', 'wpfd'), $file),
                    'icon'   => '<i class="zmdi zmdi-filter-center-focus wpfd-preview"></i>'
                )
            );

            /**
             * Global filter to change html and arguments of open pdf button
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_open_pdf_button_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_file_open_pdf_button_args', $template, $file, $config, $params);

            /**
             * Filter to change html and arguments of open pdf button
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_open_pdf_button_args
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_file_open_pdf_button_args', $template, $file, $config, $params);
            $output .= self::render($template['html'], $template['args']);
        } else {
            $template = array(
                'html' => '<a
                            href="%url$s"
                            '.$rel.'
                            class="%class$s"
                            target="%target$s"
                            data-id="%id$s"
                            data-catid="%catid$s"
                            data-file-type="%ext$s">%text$s%icon$s
                        </a>',
                'args' => array(
                    'url'    => esc_url(isset($file->viewerlink) ? $file->viewerlink : '#'),
                    'class'  => esc_attr($viewer_attr),
                    'target' => esc_attr($target),
                    'id'     => esc_attr($file->ID),
                    'catid'  => esc_attr($file->catid),
                    'ext'    => esc_attr(strtolower($file->ext)),
                    'text'   => apply_filters('wpfd_preview_text', esc_html__('Preview', 'wpfd'), $file),
                    'icon'   => '<i class="zmdi zmdi-filter-center-focus wpfd-preview"></i>'
                )
            );

            /**
             * Globacl filter to change html and arguments of preview button
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_file_preview_button_args
             *
             * @return array
             */
            $template= apply_filters('wpfd_file_preview_button_args', $template, $file, $config, $params);

            /**
             * Filter to change html and arguments of preview button
             *
             * @param array  Template array
             * @param object Current file object
             * @param array  Main config
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_file_preview_button_args
             *
             * @return array
             */
            $template= apply_filters('wpfd_' . $name . '_file_preview_button_args', $template, $file, $config, $params);
            $output .= self::render($template['html'], $template['args']);
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Print content wrapper closing tag
     *
     * @param object $theme Current theme object
     *
     * @return void
     */
    public static function outputContentWrapperEnd($theme)
    {
        echo '</div>';
    }

    /**
     * Print Categories wrapper opening tag
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function outputCategoriesWrapper($theme, $params)
    {
        $showCategoryTitle = (int) WpfdBase::loadValue($params, self::$prefix . 'showcategorytitle', 0) === 1 ? true : false;
        $showSubcategories = (int) WpfdBase::loadValue($params, self::$prefix . 'showsubcategories', 0) === 1 ? true : false;
        $showDesc = (isset($theme->category->desc) && (string) $theme->category->desc !== '') ? true : false;
        $extendClass = (!$showCategoryTitle && !$showSubcategories && !$showDesc) ? 'hide' : '';
        if (!isset($params['show_categories']) || (isset($params['show_categories']) && (int) $params['show_categories'] === 1)) {
            echo '<div class="wpfd-categories ' . esc_attr($extendClass) . '">';
        }
    }

    /**
     * Print Categories wrapper closing tag
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function outputCategoriesWrapperEnd($theme, $params)
    {
        if (!isset($params['show_categories']) || (isset($params['show_categories']) && (int) $params['show_categories'] === 1)) {
            echo '</div>';
        }
    }

    /**
     * Print Category title handlebars
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCategoryTitleHandlebars($theme, $params)
    {
        $name     = self::$themeName;
        $template = array(
            'html' => '<a class="catlink backcategory" href="#" data-idcat="{{parent}}">
                        %icon$s</i><span>%text$s</span></a>',
            'args' => array(
                'icon' => '<i class="zmdi zmdi-chevron-left">',
                'text' => esc_html__('Back', 'wpfd'),
            )
        );

        /**
         * Global filter to change html and arguments of back button handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_back_button_handlebars
         *
         * @return array
         */
        $template = apply_filters('wpfd_back_button_handlebars', $template, $theme, $params);

        /**
         * Filter to change html and arguments of back button handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_back_button_handlebars
         *
         * @return array
         */
        $template     = apply_filters('wpfd_' . $name . '_back_button_handlebars', $template, $theme, $params);
        $backButtonHtml = self::render('{{#if parent}}' . $template['html'] . '{{/if}}', $template['args']);
        $showcategorytitle = ((int) WpfdBase::loadValue($params, self::$prefix . 'showcategorytitle', 1) === 1) ? true : false;

        $headingType = 'h2';
        $headingSupports = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
        $headingType = apply_filters('wpfd_filter_category_title_heading_type', $headingType);

        if ($headingType && $headingType !== '') {
            $headingType = strtolower($headingType);
        } else {
            $headingType = 'h2';
        }

        if (!in_array($headingType, $headingSupports)) {
            $headingType = 'h2';
        }

        $cateTitle = '<' . $headingType . '>{{name}}</' . $headingType . '>';

        if (!$cateTitle || $cateTitle === '') {
            $cateTitle = '<h2>{{name}}</h2>';
        }

        $template       = array(
            'html' => '%title$s%back$s',
            'args' => array(
                'title' => $showcategorytitle ? $cateTitle : '',
                'back'  => $backButtonHtml
            )
        );

        /**
         * Global filter to change html and arguments of category title handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_category_title_handlebars
         *
         * @return array
         */
        $template = apply_filters('wpfd_category_title_handlebars', $template, $theme, $params);

        /**
         * Filter to change html and arguments of category title handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_category_title_handlebars
         *
         * @return array
         */
        $template = apply_filters('wpfd_' . $name . '_category_title_handlebars', $template, $theme, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render('{{#if category}}{{#with category}}' . $template['html'] . '{{/with}}{{/if}}', $template['args']);
    }

    /**
     * Print Category title
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCategoryTitle($theme, $params)
    {
        $name = self::$themeName;
        $headingType = 'h2';
        $headingSupports = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
        $headingType = apply_filters('wpfd_filter_category_title_heading_type', $headingType);

        if ($headingType && $headingType !== '') {
            $headingType = strtolower($headingType);
        } else {
            $headingType = 'h2';
        }

        if (!in_array($headingType, $headingSupports)) {
            $headingType = 'h2';
        }

        $cateTitle = '<' . $headingType . ' class="wpfd-category-theme-title">%title$s</' . $headingType . '>';

        if (!$cateTitle || $cateTitle === '') {
            $cateTitle = '<h2>%title$s</h2>';
        }

        $template = array(
            'html' => $cateTitle,
            'args' => array(
                'title' => esc_html($theme->category->name)
            )
        );

        /**
         * Global filter to change html and arguments of category title
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_category_title
         *
         * @return array
         */
        $template = apply_filters('wpfd_category_title', $template, $theme, $params);

        /**
         * Filter to change html and arguments of category title
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_category_title
         *
         * @return array
         */
        $template = apply_filters('wpfd_' . $name . '_category_title', $template, $theme, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render($template['html'], $template['args']);
    }

    /**
     * Print Category description handlebars
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCategoryDescHandlebars($theme, $params)
    {
        $name     = self::$themeName;
        $template = array(
            'html' => '<div class="wpfd-category-desc">%desc$s</div>',
            'args' => array(
                'desc' => '{{category.desc}}'
            )
        );

        /**
         * Global filter to change html and arguments of category description handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_category_description_handlebars
         *
         * @return array
         */
        $template = apply_filters('wpfd_category_description_handlebars', $template, $theme, $params);

        /**
         * Filter to change html and arguments of category description handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_category_description_handlebars
         *
         * @return array
         */
        $template = apply_filters('wpfd_' . $name . '_category_description_handlebars', $template, $theme, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render('{{#if category.desc}}' . $template['html'] . '{{/if}}', $template['args']);
    }

    /**
     * Print Category description
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCategoryDesc($theme, $params)
    {
        $name     = self::$themeName;
        $template = array(
            'html' => '<div class="wpfd-category-desc">%desc$s</div>',
            'args' => array(
                'desc' => esc_html($theme->category->desc)
            )
        );

        /**
         * Global filter to change html and arguments of category description
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_category_description
         *
         * @return array
         */
        $template = apply_filters('wpfd_category_description', $template, $theme, $params);

        /**
         * Filter to change html and arguments of category description
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_category_description
         *
         * @return array
         */
        $template = apply_filters('wpfd_' . $name . '_category_description', $template, $theme, $params);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render($template['html'], $template['args']);
    }
    /**
     * Print Categories handlebars
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCategoriesHandlebars($theme, $params)
    {
        $output   = '';
        $rootThemeTypes   = get_option('wpfd_root_theme_types', array());
        $cloneThemeType   = (isset($theme->name) && !empty($rootThemeTypes) && array_key_exists($theme->name, $rootThemeTypes)) ? $rootThemeTypes[$theme->name] : 'none';
        $name     = self::$themeName;
        $themeClass = '';
        if ($name === 'preview' || $cloneThemeType === 'preview') {
            $themeClass = 'preview_category';
        }
        $template = array(
            'html' => '<a class="wpfdcategory catlink %themeClass$s" style="%style$s" href="#" data-idcat="%id$s" title="%title$s">
                                <span>%text$s</span>%icon$s
                            </a>',
            'args' => array(
                'themeClass' => $themeClass,
                'style' => self::getPadding($theme->params),
                'id'    => '{{termID}}',
                'title' => '{{name}}',
                'text'  => '{{name}}',
                'icon'  => '<i class="zmdi zmdi-folder wpfd-folder" style="color: {{color}}"></i>'
            )
        );

        /**
         * Global filter to change html and arguments of categories item handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_category_item_handlebars
         *
         * @return array
         */
        $template   = apply_filters('wpfd_category_item_handlebars', $template, $theme, $params);

        /**
         * Filter to change html and arguments of categories item handlebars
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_category_item_handlebars
         *
         * @return array
         */
        $template   = apply_filters('wpfd_' . $name . '_category_item_handlebars', $template, $theme, $params);

        $style = 'margin : 0 ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginright', 10) . 'px ';
        $style .= '0 ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginleft', 10) . 'px;';

        $folderdefaultholder = '<div class="wpfdcategory_placeholder" style="' . $style . '"></div><div class="wpfdcategory_placeholder" style="' . $style . '"></div><div class="wpfdcategory_placeholder" style="' . $style . '"></div>';
        $output .= self::render('{{#if categories}}{{#each categories}}' . $template['html'] . '{{/each}}{{/if}}' . $folderdefaultholder, $template['args']);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Sort categories by parent category setting
     *
     * @param array $categories List of categories
     * @param array $params     Parent category params
     *
     * @return array|mixed
     */
    public function sortCategories($categories, $params)
    {
        $app        = Application::getInstance('Wpfd');
        $globalConfig = get_option('_wpfd_global_config');
        $subcategoriesOrdering = isset($params['subcategoriesordering']) ? $params['subcategoriesordering'] : 'customorder';
        $globalSubcategoriesOrdering = isset($globalConfig['global_subcategories_ordering']) ? $globalConfig['global_subcategories_ordering'] : 'customorder';
        $globalSubcategoriesOrderingAll = (isset($globalConfig['global_subcategories_ordering_all']) && intval($globalConfig['global_subcategories_ordering_all']) === 1) ? true : false;
        $defaultGlobalSubcategoriesOrdering = array('customorder', 'nameascending', 'namedescending');

        if ($globalSubcategoriesOrderingAll && in_array($globalSubcategoriesOrdering, $defaultGlobalSubcategoriesOrdering)) {
            $subcategoriesOrdering = $globalSubcategoriesOrdering;
        }

        if ((string) $subcategoriesOrdering !== 'customorder') {
            if (!class_exists('WpfdHelperShortcodes')) {
                $path_helper = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'WpfdHelperShortcodes.php';
                require_once $path_helper;
            }
            $helper = new WpfdHelperShortcodes();

            $subcategoriesDirection = (string)$subcategoriesOrdering === 'namedescending' ? 'desc' : 'asc';
            $categories = $helper->wpfdCategoriesOrdering($categories, $subcategoriesDirection);
        }

        return $categories;
    }

    /**
     * Print Categories
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showCategories($theme, $params)
    {
        $output     = '';
        $name       = self::$themeName;
        $categories = $theme->categories;

        foreach ($categories as $category) {
            $color = intval($category->term_id) !== 0 ? get_term_meta($category->term_id, '_wpfd_color', true) : '#b2b2b2';
            $template = array(
                'html' => '<a class="wpfdcategory catlink" style="%style$s" href="#"
                                   data-idcat="%id$s"
                                   title="%title$s">
                                    <span>%text$s</span>
                                    %icon$s
                                </a>',
                'args' => array(
                    'style' => self::getPadding($theme->params),
                    'id'    => esc_attr($category->term_id),
                    'title' => esc_html($category->name),
                    'text'  => esc_html($category->name),
                    'icon'  => '<i class="zmdi zmdi-folder wpfd-folder" style="color: '. $color .'"></i>'
                )
            );

            /**
             * Global filter to change html and arguments of categories item
             *
             * @param array  Template array
             * @param object Current category object
             * @param object Current theme object
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_category_item
             *
             * @return array
             */
            $template = apply_filters('wpfd_category_item', $template, $category, $theme, $params);

            /**
             * Filter to change html and arguments of categories item
             *
             * @param array  Template array
             * @param object Current category object
             * @param object Current theme object
             * @param array  Current category config
             *
             * @hookname wpfd_{$themeName}_category_item
             *
             * @return array
             */
            $template = apply_filters('wpfd_' . $name . '_category_item', $template, $category, $theme, $params);
            $output .= self::render($template['html'], $template['args']);
        }
        $style = 'margin : 0 ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginright', 10) . 'px ';
        $style .= '0 ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginleft', 10) . 'px;';
        $hoverColor = (is_array($params) && isset($params[self::$prefix . 'subcategoriescolor'])) ?
            WpfdBase::loadValue($params, self::$prefix . 'subcategoriescolor', '#3e3294') : '';
        $hoverColorHtml = '<input type="hidden" id="wpfd_subcategories_hover_color" class="wpfd_subcategories_hover_color" value="' . $hoverColor . '" />';

        $folderdefaultholder = '<div class="wpfdcategory_placeholder" style="' . $style . '"></div><div class="wpfdcategory_placeholder" style="' . $style . '"></div><div class="wpfdcategory_placeholder" style="' . $style . '"></div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output . $folderdefaultholder . $hoverColorHtml;
    }

    /**
     * Print Left Tree
     *
     * @param object $theme  Current theme object
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showTree($theme, $params)
    {
        $name     = self::$themeName;
        $classes  = (int) WpfdBase::loadValue($params, self::$prefix . 'showsubcategories', 1) === 1 ? 'foldertree-hide' : '';
        $template = array(
            'html' => '<div class="wpfd-foldertree wpfd-foldertree-' . esc_attr(self::$themeName) . ' %class$s"></div>',
            'args' => array('class' => $classes)
        );

        /**
         * Global filter to change html and arguments of category tree
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_category_tree
         *
         * @return array
         */
        $template = apply_filters('wpfd_category_tree', $template, $theme, $params);

        /**
         * Filter to change html and arguments of category tree
         *
         * @param array  Template array
         * @param object Current theme object
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_category_tree
         *
         * @return array
         */
        $template = apply_filters('wpfd_' . $name . '_category_tree', $template, $theme, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render($template['html'], $template['args']);
    }

    /**
     * Get padding from category config
     *
     * @param array $params Current category config
     *
     * @return string
     */
    public static function getPadding($params)
    {
        $style = 'margin : ' . WpfdBase::loadValue($params, self::$prefix . 'margintop', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginright', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginbottom', 10) . 'px ';
        $style .= WpfdBase::loadValue($params, self::$prefix . 'marginleft', 10) . 'px;';

        return $style;
    }

    /**
     * Render html using $args
     *
     * @param string $str  Html with placeholder
     * @param array  $args Arguments
     *
     * @return string
     */
    public static function render($str, $args)
    {
        if (is_object($args)) {
            $args = get_object_vars($args);
        }
        $map     = array_flip(array_keys($args));
        $new_str = preg_replace_callback(
            '/(^|[^%])%([a-zA-Z0-9_-]+)\$/',
            function ($m) use ($map) {
                return $m[1] . '%' . ($map[$m[2]] + 1) . '$';
            },
            $str
        );

        return vsprintf($new_str, $args);
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
    public static function wpfdDisplayFilePasswordProtectionForm($file, $style, $echo = true)
    {
        if (!$file) {
            return;
        }

        $fileTitle = isset($file->post_title) ? $file->post_title : '';
        $contents  = '<div class="file wpfd-password-protection-form"';
        $contents .= ' style="' . esc_html($style) . '" data-id="' . esc_attr($file->ID) . '" data-catid="' . esc_attr($file->catid) . '">';
        $contents .= '<p class="protected-title" style="font-weight: bold" title="' . $fileTitle . '">';
        $contents .= esc_html__('Protected: ', 'wpfd') . $fileTitle . '</p>';
        $contents .= wpfdGetPasswordForm($file, 'file', $file->catid) . '</div>';

        if ($echo) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text form only
            echo $contents;
        } else {
            return $contents;
        }
    }

    /**
     * WpfdShowUploadFormSubCategories
     *
     * @param string|integer $categoryId Category id
     *
     * @return string
     */
    public static function wpfdShowUploadFormSubCategories($categoryId)
    {
        $result = false;
        if (!$categoryId) {
            return false;
        }

        $modelCategoriesPath = WPFD_PLUGIN_DIR_PATH . '/app/site/models/categoriesfront.php';
        require_once $modelCategoriesPath;

        $modelCategories = new WpfdModelCategoriesfront();
        $subCates = $modelCategories->getLevelCategories($categoryId);

        if (empty($subCates)) {
            return false;
        }

        foreach ($subCates as $cate) {
            $pararms = (isset($cate->description)) ? (array) json_decode($cate->description) : array();
            $theme = isset($pararms['theme']) ? $pararms['theme'] : '';
            $prefix = $theme === 'default' ? '' : $theme . '_';

            if (empty($theme)) {
                $prefix = '';
            }

            if (isset($pararms[$prefix . 'showuploadform']) && (int) $pararms[$prefix . 'showuploadform'] === 1) {
                $result = true;
                return $result;
            }
        }

        return $result;
    }
}
