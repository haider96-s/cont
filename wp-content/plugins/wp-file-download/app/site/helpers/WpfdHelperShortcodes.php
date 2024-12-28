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
use Joomunited\WPFramework\v1_0_6\Utilities;

//-- No direct access
defined('ABSPATH') || die();

/**
 * Class WpfdHelperFile
 */
class WpfdHelperShortcodes
{
    /**
     * Global config
     *
     * @var array
     */
    public $globalConfig;

    /**
     * Initializing the helper Shortcodes class.
     *
     * @access public
     *
     * @throws Exception If arguments are missing when initializing a full widget instance.
     */
    public function __construct()
    {
        add_shortcode('wpfd_category', array($this, 'categoryShortcode'));
        add_shortcode('wpfd_single_file', array($this, 'singleFileShortcode'));
        add_shortcode('wpfd_files', array($this, 'filesShortcode'));
        add_shortcode('wpfd_search', array($this, 'wpfdSearchShortcode'));
        Application::getInstance('Wpfd');
        $configModel = Model::getInstance('configfront');
        if (method_exists($configModel, 'getGlobalConfig')) {
            $this->globalConfig = $configModel->getGlobalConfig();
        } elseif (method_exists($configModel, 'getConfig')) {
            $this->globalConfig = $configModel->getConfig();
        }
    }

    /**
     * Category shortcode
     *
     * @param array $atts Attribute
     *
     * @return string
     */
    public function categoryShortcode($atts)
    {
        if (isset($atts['id']) && $atts['id']) {
            add_action('wp_footer', array($this, 'wpfdFooter'));
            return $this->callTheme($atts['id'], $atts);
        } else {
            add_action('wp_footer', array($this, 'wpfdFooter'));
            $theme = isset($atts['theme'])? $atts['theme'] : '';
            return '<div class="wpfd-all-file-category" data-theme="'.esc_attr($theme).'">'.$this->contentAllCat($atts).'</div>';
        }
    }

    /**
     * Display wpfd scripts in footer
     *
     * @return void
     */
    public function wpfdFooter()
    {
        echo '<div id="wpfd-loading-wrap"><div class="wpfd-loading"></div></div>';
        echo '<div id="wpfd-loading-tree-wrap"><div class="wpfd-loading-tree-bg"></div></div>';
    }
    /**
     * Files shortcode
     *
     * Use: [wpfd_files catids="1,2,3" order="id|title|date|modified|rand" direction="asc|desc" users="<user_id>" limit="<total_display_file>" style="1" download="1" showhits="1"]
     * Params:
     * catids: list category or use 'all' for all categories. Default 'all'
     * order: Order of file accept id,title,date,modified and rand value. Default 'id'
     * direction: Ordering direction. Accept asc or desc. Default 'desc'
     * limit: limit of file will showing, max 100 files. Default '5'
     * download: Allow download or not. Accept 1 or 0. Default 1
     * preview: Allow preview or not. Accept 1 or 0. Default 1
     * showhits: Showing download count or not. Accept 1 or 0. Default 1
     * liststyle: Style for listing. Accept all value for list-style-type css properties. Default 'none'
     * width: Width of the list in pixel. Default '500'
     *
     * @param array $atts Attribute
     *
     * @return string
     */
    public function filesShortcode($atts)
    {
        $user = wp_get_current_user();

        if (isset($atts['limit'])) {
            // Cast limit to number for security reason
            $limit = (int) $atts['limit'];
        } else {
            $limit = 5;
        }

        // Check for limit
        if ($limit === 0) {
            return '';
        }

        // Setup default value for missing attribute
        if (isset($atts['catids']) && $atts['catids'] !== '') {
            // Filter category id in number only
            $categories = preg_split('/[\D]+/', $atts['catids']);

            // Check for sure there is a valid category id
            // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
            if (is_countable($categories) && count($categories) === 0) {
                $categories = 'all';
            }
        } else {
            $categories = 'all';
        }

        if (isset($atts['cat_operator']) && in_array($atts['cat_operator'], array('IN', 'AND', 'NOT IN'))) {
            $categoryOperator = $atts['cat_operator'];
        } else {
            $categoryOperator = 'IN';
        }

        if (isset($atts['order']) && in_array(strtolower($atts['order']), array('id', 'title', 'date', 'modified', 'rand'))) {
            $fileOrder = (strtolower($atts['order']) === 'id') ? strtoupper($atts['order']) : strtolower($atts['order']);
        } else {
            $fileOrder = 'ID';
        }

        if (isset($atts['direction']) && in_array(strtolower($atts['direction']), array('asc', 'desc'))) {
            $orderDirection = strtoupper($atts['direction']);
        } else {
            $orderDirection = 'DESC';
        }

        if (isset($atts['users']) && $atts['users'] !== '') {
            // Filter category id in number only
            $userIds = preg_split('/[\D]+/', $atts['users']);
        }

        if (!isset($atts['style'])) {
            $style = 1;
        } else {
            $style = (int) $atts['style'];
        }

        if (!isset($atts['download'])) {
            $download = 1;
        } else {
            $download = (int) $atts['download'];
        }

        if (!isset($atts['showhits'])) {
            $showhits = 1;
        } else {
            $showhits = (int) $atts['showhits'];
        }

        if (!isset($atts['preview'])) {
            $preview = 1;
        } else {
            $preview = (int) $atts['preview'];
        }

        if (!isset($atts['width'])) {
            $width = 500;
        } else {
            $width = (int) $atts['width'];
        }

        $startList = 'ol';
        if (isset($atts['liststyle']) && in_array($atts['liststyle'], array('disc','armenian','circle','cjk-ideographic','decimal','decimal-leading-zero','georgian','hebrew','hiragana','hiragana-iroha','katakana','katakana-iroha','lower-alpha','lower-greek','lower-latin','lower-roman','none','square','upper-alpha','upper-greek','upper-latin','upper-roman','initial','inherit'))) {
            switch ($atts['liststyle']) {
                case 'disk':
                case 'circle':
                case 'square':
                    $startList = 'ul';
                    break;
                default:
                    $startList = 'ol';
                    break;
            }
            $liststyle = $atts['liststyle'];
        } else {
            $liststyle = 'none';
        }

        // Check permission on categories
        if ($categories === 'all' || $categoryOperator === 'NOT IN') {
            $allCats = array();
            $allCat = get_terms(
                array(
                    'taxonomy' => 'wpfd-category',
                    'hide_empty' => 1
                )
            );
            if (!is_wp_error($allCat)) {
                foreach ($allCat as $cat) {
                    $allCats[] = $cat->term_id;
                }
            }

            // If not have any category, return
            if (empty($allCats)) {
                return '';
            }
        }

        $args = array(
            'post_type' => 'wpfd_file',
            'post_status' => array('publish'),
            'posts_per_page' => -1,
            'order_by' => $fileOrder,
            'order' => $orderDirection
        );

        if (isset($userIds) && !empty($userIds)) {
            $args['author__in'] = $userIds;
        }
        // Get categories and check current user have permission to see the files
        if ($categoryOperator === 'NOT IN') {
            $taxQuery = array(
                array (
                    'taxonomy' => 'wpfd-category',
                    'fields' => 'term_id',
                    'terms' => $categories,
                    'operator' => $categoryOperator
                )
            );
        } else {
            $taxQuery = array(
                array (
                    'taxonomy' => 'wpfd-category',
                    'fields' => 'term_id',
                    'terms' => isset($allCats) ? $allCats : $categories,
                    'operator' => $categoryOperator
                )
            );
        }
        $args['relation'] = 'AND';
        $args['tax_query'] = $taxQuery;

        // Fix conflict plugin Go7 Pricing Table
        remove_all_filters('posts_fields');
        remove_filter('the_posts', array($this, 'wpfdGetMeta'), 0);
        $query = new WP_Query($args);
        $posts = $query->get_posts();

        if (is_wp_error($posts)) {
            return '';
        }

        $latestFiles = array();
        $countPost = 0;

        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
        if (is_countable($posts) && count($posts)) {
            $totalFiles = count($posts);
            foreach ($posts as $post) {
                if ($totalFiles === $countPost) {
                    break;
                }
                if ($countPost < $limit) {
                    $file = $this->wpfdCheckAccess($post, $user);
                    if (false !== $file) {
                        $latestFiles[] = $file;
                        $countPost++;
                    }
                } else {
                    break;
                }
            }
            wp_reset_postdata();
        } else {
            return '';
        }
        //$latestFiles = array_reverse($latestFiles);


        if ($style === 1) {
            wp_enqueue_style('wpfd-google-icon', plugins_url('app/admin/assets/ui/fonts/material-icons.min.css', WPFD_PLUGIN_FILE));
            wp_enqueue_style(
                'wpfd-material-design',
                plugins_url('app/site/assets/css/material-design-iconic-font.min.css', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );
        }
        $content = '<' . $startList . ' style="list-style-type: ' . $liststyle . '; width: ' . $width . 'px" class="wpfd_files">';

        foreach ($latestFiles as $file) {
            // Download button
            $dHtml = '';
            if ($download) {
                $dHtml .= '<a style="float: right;box-shadow: 0 0 0 0;" class="wpfd_files_download" href="' . $file->linkdownload . '">';
                $dHtml .= '&nbsp;<i class="zmdi zmdi-cloud-download"></i></a>';
            }

            // Preview button
            $pHtml = '';
            if ($preview) {
                if (isset($file->openpdflink)) {
                    $pHtml .= '<a style="float: right;box-shadow: 0 0 0 0;width:16px;" class="wpfd_files_preview" target="_blank" href="' . $file->openpdflink . '">';
                    $pHtml .= '<img style="display:inline;margin-right: 5px;" src="' . plugins_url('/app/site/assets/images/open_242.png', WPFD_PLUGIN_FILE) . '" title="' . esc_html__('Open', 'wpfd') . '"/></a>';
                } else {
                    $pHtml .= '<a style="float: right;box-shadow: 0 0 0 0;width:16px;" class="wpfd_files_preview" target="_blank" href="' . $file->viewerlink . '">';
                    $pHtml .= '<img style="display:inline;margin-right: 5px;" src="' . plugins_url('/app/site/assets/images/open_242.png', WPFD_PLUGIN_FILE) . '" title="' . esc_html__('Open', 'wpfd') . '"/></a>';
                }
            }

            $hHtml = '';
            if ($showhits) {
                $hHtml .= '(' . sprintf(esc_html__('Download %d times', 'wpfd'), $file->hits) . ')';
            }

            // Content
            $content .= '<li class="' . strtolower($file->ext) . '">';
            if ($download) {
                $content .= '<a class="wpfd_files_download" href="' . $file->linkdownload . '" style="box-shadow: 0 0 0 0;">';
            }
            $content .= $file->title . '.' . $file->ext;
            if ($download) {
                $content .= '</a>';
            }
            if ($showhits) {
                $content .= $hHtml;
            }

            if ($download) {
                $content .= $dHtml;
            }
            if ($preview) {
                $content .= $pHtml;
            }

            $content .=  '</li>';
        }
        $content .= '</' . $startList . '>';

        return $content;
    }

    /**
     * Single file shortcode
     *
     * @param array $atts Attribute
     *
     * @return string
     */
    public function singleFileShortcode($atts)
    {
        if (isset($atts['id']) && $atts['id']) {
            if (isset($atts['catid'])) {
                $catid = $atts['catid'];
            } else {
                $term_list = wp_get_post_terms((int)$atts['id'], 'wpfd-category', array('fields' => 'ids'));
                if (empty($term_list)) {
                    return '';
                }
                $catid = $term_list[0];
            }
            $diplayName = false;
            if (isset($atts['name']) && $atts['name']) {
                $diplayName = $atts['name'];
            }
            return $this->callSingleFile($atts['id'], $catid, $diplayName);
        }
        return '';
    }

    /**
     * Get content of a single file
     *
     * @param mixed $file_id     File Id
     * @param mixed $catid       Category Id
     * @param null  $nameDisplay Name Display
     *
     * @return string
     */
    public function callSingleFile($file_id, $catid, $nameDisplay = null)
    {
        $ds = DIRECTORY_SEPARATOR;
        wp_enqueue_style(
            'wpfd-front',
            plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_script(
            'wpfd-frontend',
            plugins_url('app/site/assets/js/frontend.js', WPFD_PLUGIN_FILE),
            array('jquery'),
            WPFD_VERSION
        );
        wp_localize_script('wpfd-frontend', 'wpfdfrontend', array('pluginurl' => plugins_url('', WPFD_PLUGIN_FILE)));

        wp_enqueue_style(
            'wpfd-theme-default',
            plugins_url('app/site/themes/wpfd-default/css/style.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-colorbox-viewer',
            plugins_url('app/site/assets/css/viewer.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        wp_enqueue_style('wpfd-google-icon', plugins_url('app/admin/assets/ui/fonts/material-icons.min.css', WPFD_PLUGIN_FILE));
        wp_enqueue_style(
            'wpfd-material-design',
            plugins_url('app/site/assets/css/material-design-iconic-font.min.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        $global_settings = $this->globalConfig;
        $iconSet = (isset($global_settings['icon_set'])) ? $global_settings['icon_set'] : 'svg';
        $lastRebuildTime = get_option('wpfd_icon_rebuild_time', false);
        if (false === $lastRebuildTime) {
            // Icon CSS was never build, build it
            $lastRebuildTime = WpfdHelperFile::renderCss();
        }
        if ($iconSet !== 'default' && in_array($iconSet, array('png', 'svg'))) {
            $path = WpfdHelperFile::getCustomIconPath($iconSet);
            $cssPath = $path . 'styles-' . $lastRebuildTime . '.css';
            if (file_exists($cssPath)) {
                $cssUrl = wpfd_abs_path_to_url($cssPath);
            } else {
                $lastRebuildTime = WpfdHelperFile::renderCss();
                $cssPath = $path . 'styles-' . $lastRebuildTime . '.css';
                if (file_exists($cssPath)) {
                    $cssUrl = wpfd_abs_path_to_url($cssPath);
                } else {
                    // Use default css pre-builed
                    $cssUrl = WPFD_PLUGIN_URL . 'app/site/assets/icons/' . $iconSet . '/icon-styles.css';
                }
            }
            // Include file
            wp_enqueue_style(
                'wpfd-single-file-style-icon-set-' . $iconSet,
                $cssUrl,
                array(),
                WPFD_VERSION
            );
        }

        wpfd_enqueue_assets();

        $app = Application::getInstance('Wpfd');

        $path_wpfdbase = $app->getPath() . $ds . 'admin' . $ds . 'classes';
        $path_wpfdbase .= $ds . 'WpfdBase.php';
        require_once $path_wpfdbase;

        Application::getInstance('Wpfd');
        $modelConfig = Model::getInstance('configfront');
        /* @var WpfdModelIconsBuilder $modelIconsBuilder */
        $modelIconsBuilder = Model::getInstance('iconsbuilder');
        $modelCategory = Model::getInstance('categoryfront');
        $modelFile = Model::getInstance('filefront');
        $modelTokens = Model::getInstance('tokens');

        $token = $modelTokens->getOrCreateNew();
        $category = $modelCategory->getCategory((int)$catid);
        if (!$category) {
            return '';
        }
        if ((int) $category->access === 1) {
            $user = wp_get_current_user();
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows = array_intersect($roles, $category->roles);
            if (empty($allows)) {
                return '';
            }
        }

        $params = $modelConfig->getConfig();
        $globalConfig = $modelConfig->getGlobalConfig();
        $file_params = $modelConfig->getFileConfig();
        $config = $this->globalConfig;
        $singleParams = $modelIconsBuilder->getSingleButtonParams();
        $idFile = $file_id;
        wp_localize_script('wpfd-frontend', 'wpfdparams', array(
            'ga_download_tracking' => $globalConfig['ga_download_tracking']
        ));
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catid) ;
        if ($categoryFrom === 'googleDrive') {
            $file = apply_filters('wpfdAddonGetGoogleDriveFile', $idFile, $catid, $token);
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $file = apply_filters('wpfdAddonGetGoogleTeamDriveFile', $idFile, $catid, $token);
        } elseif ($categoryFrom === 'dropbox') {
            $file = apply_filters('wpfdAddonGetDropboxFile', $idFile, $catid, $token);
        } elseif ($categoryFrom === 'onedrive') {
            $file = apply_filters('wpfdAddonGetOneDriveFile', $idFile, $catid, $token);
        } elseif ($categoryFrom === 'onedrive_business') {
            $file = apply_filters('wpfdAddonGetOneDriveBusinessFile', $idFile, $catid, $token);
        } elseif ($categoryFrom === 'aws') {
            $file = apply_filters('wpfdAddonGetAwsFile', $idFile, $catid, $token);
        } elseif ($categoryFrom === 'nextcloud') {
            $file = apply_filters('wpfdAddonGetNextcloudFile', $idFile, $catid, $token);
        } else {
            $file = $modelFile->getFile($idFile, $catid);
        }
        if (!$file) {
            return '';
        }
        
        $file = (object)$file;
        $filePwName = isset($file->title) ? $file->title : $nameDisplay;
        $passwordFormProtection = '<div class="wpfd-file-password-protection-container"><h2 class="protected-title">' . esc_html__('Protected: ', 'wpfd') . $filePwName . '</h2>';
        if (wpfdPasswordRequired($file, 'file')) {
            $passwordFormProtection .= wpfdGetPasswordForm($file, 'file', $catid);
            $passwordFormProtection .= '</div>';
            return $passwordFormProtection;
        }
        if (isset($file->state) && (int) $file->state === 0) {
            return '';
        }
        if ((int) $config['restrictfile'] === 1) {
            $user = wp_get_current_user();
            $user_id = $user->ID;
            $canview = isset($file->canview) ? $file->canview : 0;
            $canview = array_map('intval', explode(',', $canview));
            if ($user_id) {
                if (!(in_array($user_id, $canview) || in_array(0, $canview))) {
                    return '';
                }
            } else {
                if (!in_array(0, $canview)) {
                    return '';
                }
            }
        }

        $file->social = isset($file->social) ? $file->social : 0;

        if (!isset($file->crop_title) || (isset($file->crop_title) && strlen($file->crop_title) === 0)) {
            $file->crop_title = $file->post_title;
        }

        $replaceHyphenFileTitle = apply_filters('wpfdReplaceHyphenFileTitle', false);
        if ($replaceHyphenFileTitle) {
            $file->crop_title = str_replace('-', ' ', $file->crop_title);
            $file->post_title = str_replace('-', ' ', $file->post_title);
        }

        if (defined('WPFD_OLD_SINGLE_FILE') && WPFD_OLD_SINGLE_FILE) {
            $bg_color    = WpfdBase::loadValue($file_params, 'singlebg', '#444444');
            $hover_color = WpfdBase::loadValue($file_params, 'singlehover', '#888888');
            $font_color  = WpfdBase::loadValue($file_params, 'singlefontcolor', '#ffffff');
            $showsize    = ((int) WpfdBase::loadValue($params, 'showsize', 1) === 1) ? true : false;
            $singleCss   = '.wpfd-single-file .wpfd_previewlink {margin-top: 10px;display: block;font-weight: bold;}';
            if ($bg_color !== '') {
                $singleCss .= '.wpfd-single-file .wpfd-file-link {background-color: ' . esc_html($bg_color) . ' !important;}';
            }
            if ($font_color !== '') {
                $singleCss .= '.wpfd-single-file .wpfd-file-link {color: ' . esc_html($font_color) . ' !important;}';
            }
            if ($hover_color !== '') {
                $singleCss .= '.wpfd-single-file .wpfd-file-link:hover {background-color: ' . esc_html($hover_color) . ' !important;}';
            }

            if (!$nameDisplay) {
                $nameDisplay = $file->title;
            }

            $variables = array(
                'file' => $file,
                'nameDisplay' => $nameDisplay,
                'showsize' => $showsize,
                'previewType' => WpfdBase::loadValue($config, 'use_google_viewer', 'lightbox'),
            );
            $html = wpfd_get_template_html('tpl-single.php', $variables);
            $html .= '<style>' . $singleCss . '</style>';
        } else {
            // New style using icon builder
            // Load customized CSS file
            $customizeCssPath = WP_CONTENT_DIR . $ds . wpfd_get_content_dir() . $ds . 'wpfd-single-file-button.css';
            wp_enqueue_style(
                'wpfd-single-file-css',
                plugins_url('app/admin/assets/ui/css/singlefile.css', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );
            if (file_exists($customizeCssPath)) {
                // Using hash to reload file
                $hash = get_option('wpfd_single_file_css_hash', WPFD_VERSION);
                wp_enqueue_style(
                    'wpfd-single-file-button',
                    wpfd_abs_path_to_url($customizeCssPath),
                    array('wpfd-single-file-css'),
                    $hash
                );
                wp_add_inline_style('wpfd-single-file-button', $singleParams['custom_css']);
            } else {
                wp_enqueue_style(
                    'wpfd-single-file-button',
                    plugins_url('app/site/assets/css/wpfd-single-file-button.css', WPFD_PLUGIN_FILE),
                    array('wpfd-single-file-css'),
                    WPFD_VERSION
                );
            }

            // Get current file icon url
            $baseIconSet = isset($singleParams['base_icon_set']) ? $singleParams['base_icon_set'] : 'png';
            $file->icon_style = '';
            $iconUrl = WpfdHelperFile::getUploadedIconPath($file->ext, $baseIconSet);

            $isCustomIcon = false;
            if (isset($file->file_custom_icon) && $file->file_custom_icon !== '') {
                $iconUrl = site_url() . $file->file_custom_icon;
                $isCustomIcon = true;
            }

            if ($baseIconSet === 'default') {
                $backgroundSize = 'background-size: contain;background-position: center center;';
            } else {
                $backgroundSize = 'background-size: 100%;';
            }

            $useGeneratedView = isset($config['auto_generate_preview']) && intval($config['auto_generate_preview']) === 1 ? true : false;
            $existPreviewImage = false;
            $fileView = '';
            $viewImageFilePath = '';
            $customPreviewPath = '';
            if (is_numeric($file->ID)) {
                $viewImageFilePath = get_post_meta($file->ID, '_wpfd_thumbnail_image_file_path', true);
                $metaData = get_post_meta($file->ID, '_wpfd_file_metadata', true);
                $customPreviewPath = isset($metaData['file_custom_icon_preview']) ? $metaData['file_custom_icon_preview'] : '';
                $viewWmInfo = get_option('_wpfdAddon_preview_wm_info_' . $file->ID, false);
            } else {
                // Fix the id of OneDrive
                $previewFileId = ($categoryFrom === 'onedrive' || $categoryFrom === 'onedrive_business') ? str_replace('-', '!', $file->ID) : $file->ID;
                $viewFileInfo = get_option('_wpfdAddon_preview_info_' . md5($previewFileId), false);
                $viewFilePath = is_array($viewFileInfo) && isset($viewFileInfo['path']) ? $viewFileInfo['path'] : false;
                $previewIcon = get_option('_wpfdAddon_file_custom_icon_preview_' . md5($file->ID), false);
                $customPreviewPath = (isset($previewIcon) && !is_null($previewIcon) && $previewIcon !== false) ? $previewIcon : '';
                $viewWmInfo = get_option('_wpfdAddon_preview_wm_info_' . md5($file->ID), false);
            }

            if ($useGeneratedView && isset($viewFilePath) && $viewFilePath && file_exists(WP_CONTENT_DIR . $viewFilePath)) {
                $existPreviewImage = true;
                $fileView = WP_CONTENT_URL . $viewFilePath;
            } elseif ($useGeneratedView && isset($viewImageFilePath) && $viewImageFilePath && file_exists(WP_CONTENT_DIR . $viewImageFilePath)) {
                $existPreviewImage = true;
                $fileView = WP_CONTENT_URL . $viewImageFilePath;
            }

            $existJUPreviewIcon = isset($singleParams['ju_preview_icon']) ? $singleParams['ju_preview_icon'] : false;
            if (!$existJUPreviewIcon) {
                $existPreviewImage = false;
            }

            $wmCategory = false;
            $lists = get_option('wpfd_watermark_category_listing');
            if (is_array($lists) && !empty($lists)) {
                if (in_array($catid, $lists)) {
                    $wmCategory = true;
                }
            }
            $wmImageExt = array('jpg', 'jpeg', 'png');

            if ($wmCategory && in_array(strtolower($file->ext), $wmImageExt) && $viewWmInfo !== false) {
                $viewFileWmPath = is_array($viewWmInfo) && isset($viewWmInfo['path']) ? $viewWmInfo['path'] : false;
                if (isset($viewFileWmPath) && $viewFileWmPath && file_exists(WP_CONTENT_DIR . $viewFileWmPath)) {
                    $fileView  = WP_CONTENT_URL . $viewFileWmPath;
                    $existPreviewImage = true;
                }
            }

            $file->icon_style .= $existPreviewImage ? 'background-image: url("' . esc_url($fileView) . '");' : 'background-image: url("' . esc_url($iconUrl) . '");';
            if ($baseIconSet === 'svg' && !$isCustomIcon && !$existPreviewImage) {
                $iconParam = $modelIconsBuilder->getIconParams($baseIconSet, $file->ext);
                if (false !== $iconParam) {
                    if (intval($iconParam['wrapper-active']) === 1) {
                        $customCss = isset($iconParam['border-radius']) && intval($iconParam['border-radius']) > 0 ? 'border-radius: ' . $iconParam['border-radius'] . '%;' : '';
                        $customCss .= 'box-shadow: ' . $iconParam['horizontal-position'] . 'px ' . $iconParam['vertical-position'] . 'px ' . $iconParam['blur-radius'] . 'px ' . $iconParam['spread-radius'] . 'px ' . $iconParam['shadow-color'] . ';';
                        $customCss .= 'background-color: ' . $iconParam['background-color'] . ';';
                        $customCss .= 'border: ' . $iconParam['border-size'] . 'px solid ' . $iconParam['border-color'] . ';';
                        $file->icon_style .= $customCss;
                    }
                }
            }
            $file->icon_style .= $backgroundSize;
            $file->size = WpfdHelperFile::bytesToSize($file->size);
            $previewType = WpfdBase::loadValue($config, 'use_google_viewer', 'lightbox');
            if ($previewType === 'lightbox') {
                $file->open_in_lightbox = true;
            } elseif ($previewType === 'tab') {
                $file->open_in_newtab = true;
            } else {
                $classes[] = 'noLightbox';
            }
            // Hide preview link on no link available
            if (isset($file->openpdflink) && $file->openpdflink === '' && $file->viewerlink === '') {
                $singleParams['link_on_icon'] = 'none';
            }

            $template = wpfd_get_template_html('tpl-single2.php');
            $data = array(
                'settings' => $singleParams,
                'file' => json_decode(json_encode($file), true),
                'config' => $config
            );

            $html = wpfdHandlerbarsRender($template, $data, 'singlefile');
        }

        if ((int) $file->social === 1 && defined('WPFDA_VERSION')) {
            return do_shortcode('[wpfdasocial]' . $html . '[/wpfdasocial]');
        } else {
            return $html;
        }
    }

    /**
     * Call category theme
     *
     * @param mixed   $param           Category id
     * @param boolean $shortcode_param Shortcode Param
     *
     * @return string
     */
    public function callTheme($param, $shortcode_param = false)
    {

        global $sitepress;
        if (!empty($sitepress)) {
            $language_negotiation_type = $sitepress->get_setting('language_negotiation_type');
            // Different languages in directories
            if ((int)$language_negotiation_type === 1) {
                $current_lang = apply_filters('wpml_current_language', null);
                $default_lang = $sitepress->get_default_language();
                if ($current_lang !== $default_lang) {
                    $translated_category_id = apply_filters('wpml_object_id', $param, 'wpfd-category', true, $current_lang);
                    if ($translated_category_id) {
                        $param = $translated_category_id;
                    }
                }
            }
        }

        wp_enqueue_style(
            'wpfd-front',
            plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        $app = Application::getInstance('Wpfd');

        $path_wpfdbase = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
        $path_wpfdbase .= DIRECTORY_SEPARATOR . 'WpfdBase.php';
        require_once $path_wpfdbase;

        $modelConfig     = Model::getInstance('configfront');
        $modelFiles      = Model::getInstance('filesfront');
        $modelCategories = Model::getInstance('categoriesfront');
        $modelCategory   = Model::getInstance('categoryfront');
        $modelTokens     = Model::getInstance('tokens');
        $global_settings = $this->globalConfig;
        $isThemeShortCodeParam = false;
        $themeParams = array();
        $themes      = $modelConfig->getThemes();

        if ($shortcode_param && isset($shortcode_param['number']) && !empty($shortcode_param['number']) &&
            (is_numeric($shortcode_param['number']) && (int)$shortcode_param['number'] > 0)
        ) {
            $global_settings['paginationnunber'] = (int) $shortcode_param['number'];
        }

        // Check and generate missing SVG icons
        $category = $modelCategory->getCategory($param);
        if (empty($category)) {
            return '';
        }

        // Using theme parameter in shortcode
        if (isset($shortcode_param['theme']) && !empty($shortcode_param['theme']) && in_array($shortcode_param['theme'], $themes)) {
            $isThemeShortCodeParam = true;
            $themename = $shortcode_param['theme'];
        } else {
            $themename = $category->params['theme'];
        }

        if ($isThemeShortCodeParam && $themename !== $category->params['theme']) {
            $defaultThemeParams = $modelConfig->getConfig($themename);
            $themeParams = get_option('_wpfd_' . $themename . '_config', $defaultThemeParams);
            $themeParams['theme'] = $themename;
        }

        $showPagination = (isset($shortcode_param['enable_pagination']) && intval($shortcode_param['enable_pagination']) === 0) ? false : true;
        $lastRebuildTime = get_option('wpfd_icon_rebuild_time', false);

        if (false === $lastRebuildTime) {
            // Icon CSS was never build, build it
            $lastRebuildTime = WpfdHelperFile::renderCss();
        }

        $iconSet = (isset($global_settings['icon_set'])) ? $global_settings['icon_set'] : 'svg';
        if ($iconSet !== 'default' && in_array($iconSet, array('png', 'svg'))) {
            $path = WpfdHelperFile::getCustomIconPath($iconSet);
            $cssPath = $path . 'styles-' . $lastRebuildTime . '.css';
            if (file_exists($cssPath)) {
                $cssUrl = wpfd_abs_path_to_url($cssPath);
            } else {
                $lastRebuildTime = WpfdHelperFile::renderCss();
                $cssPath = $path . 'styles-' . $lastRebuildTime . '.css';
                if (file_exists($cssPath)) {
                    $cssUrl = wpfd_abs_path_to_url($cssPath);
                } else {
                    // Use default css pre-builed
                    $cssUrl = WPFD_PLUGIN_URL . 'app/site/assets/icons/' . $iconSet . '/icon-styles.css';
                }
            }
            // Include file
            wp_enqueue_style(
                'wpfd-style-icon-set-' . $iconSet,
                $cssUrl,
                array('wpfd-theme-' . $themename),
                WPFD_VERSION
            );
        }

        $params = $category->params;
        $themeSettings = (int) $global_settings['themesettings'];

        if ((int) $global_settings['catparameters'] === 1) {
            if (!$themeSettings) {
                $defaultParams = $modelConfig->getConfig($params['theme']);

                foreach ($params as $key => $value) {
                    if (isset($defaultParams[$key])) {
                        $params[$key] = $defaultParams[$key];
                    }
                }

                // When a category never save the setting before, there params missing on db.
                foreach ($defaultParams as $k => $v) {
                    if (!isset($params[$k])) {
                        $params[$k] = $v;
                    }
                }
            }
        } else {
            $defaultTheme    = $global_settings['defaultthemepercategory'];
            $defaultParams   = $modelConfig->getConfig($defaultTheme);
            $defaultSettings = true;
            if ($themeSettings && isset($params['theme']) && $params['theme'] === $defaultTheme) {
                $defaultSettings = false;
            }

            if ($defaultSettings) {
                foreach ($params as $key => $value) {
                    if (isset($defaultParams[$key])) {
                        $params[$key] = $defaultParams[$key];
                    }
                }
            }

            // Add default setting on missing param.
            // When a category never save the setting before, there params missing on db.
            foreach ($defaultParams as $key => $value) {
                if (!isset($params[$key])) {
                    $params[$key] = $value;
                }
            }
        }

        $params['social'] = isset($params['social']) ? $params['social'] : 0;
        $accessMessage = (isset($global_settings['access_message']) && (int) $global_settings['access_message'] === 1) ? true : false;
        $accessMessageVal = isset($global_settings['access_message_val']) ? $global_settings['access_message_val']
            : esc_html__('This file category is not accessible to your account', 'wpfd');
        $accessMessageOutput = '<div class="wpfd-access-category-message-section">';
        $accessMessageOutput .= '<p class="wpfd-access-category-message">';
        $accessMessageOutput .= $accessMessageVal;
        $accessMessageOutput .= '</p>';
        $accessMessageOutput .= '</div>';

        if ((int) $category->access === 1) {
            $user = wp_get_current_user();
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows = array_intersect($roles, $category->roles);
            $singleuser = false;

            if (isset($params['canview']) && $params['canview'] === '') {
                $params['canview'] = 0;
            }

            $canview = isset($params['canview']) ? (int) $params['canview'] : 0;

            if ((int) $global_settings['restrictfile'] === 1) {
                $user = wp_get_current_user();
                $user_id = $user->ID;

                if ($user_id) {
                    if ($canview === $user_id || $canview === 0) {
                        $singleuser = true;
                    } else {
                        $singleuser = false;
                    }
                } else {
                    if ($canview === 0) {
                        $singleuser = true;
                    } else {
                        $singleuser = false;
                    }
                }
            }

            if ($canview !== 0 && !count($category->roles)) {
                if ($singleuser === false) {
                    if ($accessMessage) {
                        return $accessMessageOutput;
                    } else {
                        return '';
                    }
                }
            } elseif ($canview !== 0 && count($category->roles)) {
                if (!(!empty($allows) || ($singleuser === true))) {
                    if ($accessMessage) {
                        return $accessMessageOutput;
                    } else {
                        return '';
                    }
                }
            } else {
                if (empty($allows)) {
                    if ($accessMessage) {
                        return $accessMessageOutput;
                    } else {
                        return '';
                    }
                }
            }
        }

        $passwordFormProtection = '<div class="wpfd-category-password-protection-container"><h2 class="protected-title">' . esc_html__('Protected: ', 'wpfd') . $category->name . '</h2>';
        if (wpfdPasswordRequired($category, 'category')) {
            $passwordFormProtection .= wpfdGetPasswordForm($category, 'category');
            $passwordFormProtection .= '</div>';
            return $passwordFormProtection;
        }

        if (isset($global_settings['use_google_viewer']) && $global_settings['use_google_viewer'] === 'lightbox') {
            wp_enqueue_script('wpfd-colorbox', plugins_url('app/site/assets/js/jquery.colorbox-min.js', WPFD_PLUGIN_FILE), array('jquery'));
            wp_enqueue_script(
                'wpfd-colorbox-init',
                plugins_url('app/site/assets/js/colorbox.init.js', WPFD_PLUGIN_FILE),
                array('jquery', 'wpfd-colorbox'),
                WPFD_VERSION
            );
            wp_localize_script(
                'wpfd-colorbox-init',
                'wpfdcolorboxvars',
                array(
                    'preview_loading_message' => esc_html__('The preview is still loading, you can cancel it at any time...', 'wpfd') . '<span class="wpfd-loading-close">' . esc_html__('cancel', 'wpfd') . '</span>'
                )
            );
            wp_enqueue_style(
                'wpfd-colorbox',
                plugins_url('app/site/assets/css/colorbox.css', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );
            wp_enqueue_style(
                'wpfd-viewer',
                plugins_url('app/site/assets/css/viewer.css', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );
        }
        /**
         * Get theme instance follow priority
         *
         * 1. /wp-content/wp-file-download/themes
         * 2. /wp-content/uploads/wpfd-themes
         * 3. /wp-content/plugins/wp-file-download/app/site/themes
         */
        $theme = wpfd_get_theme_instance($themename);

        // Set theme params, separator it to made sure theme can work well
        if (method_exists($theme, 'setAjaxUrl')) {
            $theme->setAjaxUrl(wpfd_sanitize_ajax_url(Application::getInstance('Wpfd')->getAjaxUrl()));
        }

        if (method_exists($theme, 'setConfig')) {
            $theme->setConfig($global_settings);
        }

        if (method_exists($theme, 'setPath')) {
            $theme->setPath(Application::getInstance('Wpfd')->getPath());
        }

        if (method_exists($theme, 'setThemeName')) {
            $theme->setThemeName($themename);
        }

        $token = $modelTokens->getOrCreateNew();

        $tpl = null;
        $category = $modelCategory->getCategory($param);

        $orderCol = Utilities::getInput('orderCol', 'GET', 'none');
        $ordering = $orderCol !== null ? $orderCol : $category->ordering;
        $orderDir = Utilities::getInput('orderDir', 'GET', 'none');
        $orderingdir = $orderDir !== null ? $orderDir : $category->orderingdir;
        $subcategoriesCol = Utilities::getInput('subcategoriesOrdering', 'GET', 'none');
        $subcategoriesOrdering = $subcategoriesCol !== null ? $subcategoriesCol : $category->subcategoriesordering;
        $globalSubcategoriesOrdering = isset($global_settings['global_subcategories_ordering']) ? $global_settings['global_subcategories_ordering'] : 'customorder';
        $globalSubcategoriesOrderingAll = (isset($global_settings['global_subcategories_ordering_all']) && intval($global_settings['global_subcategories_ordering_all']) === 1) ? true : false;
        $defaultGlobalSubcategoriesOrdering = array('customorder', 'nameascending', 'namedescending');
        if ($globalSubcategoriesOrderingAll && in_array($globalSubcategoriesOrdering, $defaultGlobalSubcategoriesOrdering)) {
            $subcategoriesOrdering = $globalSubcategoriesOrdering;
        }
        $categories = $modelCategories->getCategories($param);
        $description = json_decode($category->description, true);
        $lstAllFile = null;

        if (!empty($description) && isset($description['refToFile'])) {
            if (isset($description['refToFile'])) {
                $listCatRef = $description['refToFile'];
                $lstAllFile = $this->getAllFileRef($modelFiles, $listCatRef, $ordering, $orderingdir);
            }
        }

        if ($shortcode_param && isset($shortcode_param['order']) && !empty($shortcode_param['order'])) {
            $ordering = $shortcode_param['order'];
        }
        if ($shortcode_param && isset($shortcode_param['direction']) && !empty($shortcode_param['direction'])) {
            $orderingdir = $shortcode_param['direction'];
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

        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $param);
        if ($categoryFrom === 'googleDrive') {
            $tpl = 'googleDrive';
            $files = apply_filters(
                'wpfdAddonGetListGoogleDriveFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );

            $categories = $modelCategories->getCategories($param);
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $tpl = 'googleTeamDrive';
            $files = apply_filters(
                'wpfdAddonGetListGoogleTeamDriveFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $categories = $modelCategories->getCategories($param);
        } elseif ($categoryFrom === 'dropbox') {
            $tpl = 'dropbox';
            $files = apply_filters(
                'wpfdAddonGetListDropboxFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $categories = $modelCategories->getCategories($param);
        } elseif ($categoryFrom === 'onedrive') {
            $tpl = 'onedrive';
            $files = apply_filters(
                'wpfdAddonGetListOneDriveFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
            $categories = $modelCategories->getCategories($param);
        } elseif ($categoryFrom === 'onedrive_business') {
            $tpl = 'onedrive_business';
            $files = apply_filters(
                'wpfdAddonGetListOneDriveBusinessFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );

            $categories = $modelCategories->getCategories($param);
        } elseif ($categoryFrom === 'aws') {
            $tpl = 'aws';
            $files = apply_filters(
                'wpfdAddonGetListAwsFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );

            $categories = $modelCategories->getCategories($param);
        } elseif ($categoryFrom === 'nextcloud') {
            $tpl = 'nextcloud';
            $files = apply_filters(
                'wpfdAddonGetListNextcloudFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );

            $categories = $modelCategories->getCategories($param);
        } else {
            $files = $modelFiles->getFiles($param, $ordering, $orderingdir);
            if (!empty($files) && ((int) $global_settings['restrictfile'] === 1)) {
                foreach ($files as $key => $file) {
                    $metadata = get_post_meta($file->ID, '_wpfd_file_metadata', true);
                    $canview = isset($metadata['canview']) ? $metadata['canview'] : 0;
                    $files[$key]->canview = $canview;
                }
            }
        }

        // Check permission for User allow to access file feature
        if (is_array($files) && !empty($files) && ((int) $global_settings['restrictfile'] === 1)) {
            $user    = wp_get_current_user();
            $user_id = $user->ID;
            foreach ($files as $key => $file) {
                if (!isset($file->canview)) {
                    continue;
                }
                $canview = array_map('intval', explode(',', $file->canview));
                if ($user_id) {
                    if (!(in_array($user_id, $canview) || in_array(0, $canview))) {
                        unset($files[$key]);
                    }
                } else {
                    if (!in_array(0, $canview)) {
                        unset($files[$key]);
                    }
                }
            }
        }
        // $files maybe false when get from cloud folder
        if (!is_array($files)) {
            $files = array();
        }
        if ($lstAllFile && !empty($lstAllFile)) {
            $files = array_merge($lstAllFile, $files);
        }

        // Check and show empty message
        if (is_array($categories) && empty($categories) &&
            is_array($files) && empty($files)) {
            if (isset($global_settings['empty_message']) && (int) $global_settings['empty_message'] === 1) {
                $emptyMessage = isset($global_settings['empty_message_val']) ? $global_settings['empty_message_val'] :
                    esc_html__('This file category has no files to display', 'wpfd');
                $emptyMessageHtml = '<div class="wpfd-empty-category-message-section">';
                $emptyMessageHtml .= '<p class="wpfd-empty-category-message">';
                $emptyMessageHtml .= $emptyMessage;
                $emptyMessageHtml .= '</p>';
                $emptyMessageHtml .= '</div>';

                return $emptyMessageHtml;
            }
        }

        // Reorder for correct ordering
        $ordering_array = array(
            'created_time', 'modified_time', 'hits', 'size', 'ext', 'version', 'title', 'description', 'ordering');
        if (is_array($files) && in_array($ordering, $ordering_array)) {
            switch ($ordering) {
                case 'created_time':
                    usort($files, array('WpfdHelperShortcodes', 'cmpCreated'));
                    break;
                case 'modified_time':
                    usort($files, array('WpfdHelperShortcodes', 'cmpUpdated'));
                    break;
                case 'hits':
                    usort($files, array('WpfdHelperShortcodes', 'cmpHits'));
                    break;
                case 'size':
                    usort($files, array('WpfdHelperShortcodes', 'cmpSize'));
                    break;
                case 'ext':
                    usort($files, array('WpfdHelperShortcodes', 'cmpExt'));
                    break;
                case 'version':
                    usort($files, array('WpfdHelperShortcodes', 'cmpVersionNumber'));
                    break;
                case 'description':
                    usort($files, array('WpfdHelperShortcodes', 'cmpDescription'));
                    break;
                case 'ordering':
                    if (!empty($lstAllFile)) {
                        $orderingList = get_option('wpfd_custom_ordering_list', array());
                        $customOrdering = isset($orderingList[$param]) ? (array) json_decode($orderingList[$param]) : array();
                        $orderingFiles = array();
                        $currentFiles = array();
                        $listUnsortFile = array();
                        if (!empty($customOrdering)) {
                            foreach ($files as $cFile) {
                                $currentFiles[$cFile->ID] = $cFile;
                            }
                            $listUnsortFile = $currentFiles;

                            foreach ($customOrdering as $index => $orderFile) {
                                if (array_key_exists((string) $orderFile, $currentFiles)) {
                                    $orderingFiles[] = $currentFiles[$orderFile];
                                    unset($listUnsortFile[$orderFile]);
                                }
                            }

                            if (!empty($orderingFiles)) {
                                $files = $orderingFiles;
                            }

                            if (!empty($listUnsortFile)) {
                                $files = array_merge($files, $listUnsortFile);
                            }
                        }
                    }
                    break;
                case 'title':
                default:
                    usort($files, array('WpfdHelperShortcodes', 'cmpTitle'));
                    break;
            }
            if (strtoupper($orderingdir) === 'DESC') {
                $files = array_reverse($files);
            }
        }

        $limit = $global_settings['paginationnunber'];
        $limit = (string) apply_filters('wpfd_filter_category_limit_pagination', $limit);
        $total = (is_array($files)) ? ceil(count($files) / $limit) : 0;

        $page = Utilities::getInput('paged', 'POST', 'string');
        $page = $page !== '' ? $page : 1;
        $offset = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        $filesx = array();
        // Crop file titles
        if (is_array($files) && !empty($files)) {
            foreach ($files as $i => $file) {
                if (isset($file->state) && (int) $file->state === 0) {
                    continue;
                }
                $filesx[$i]             = $file;
                $filesx[$i]->crop_title = WpfdBase::cropTitle($params, $theme->getThemeName(), $file->post_title);
                if (isset($file->file_custom_icon) && $file->file_custom_icon !== '') {
                    if (strpos($file->file_custom_icon, site_url()) !== 0) {
                        $filesx[$i]->file_custom_icon = site_url() . $file->file_custom_icon;
                    }
                }

                $filesx[$i]->iconset = (isset($global_settings['icon_set']) && $global_settings['icon_set'] !== 'default') ? ' wpfd-icon-set-' . $global_settings['icon_set'] : '';

                $replaceHyphenFileTitle = apply_filters('wpfdReplaceHyphenFileTitle', false);
                if ($replaceHyphenFileTitle) {
                    $filesx[$i]->crop_title = str_replace('-', ' ', $filesx[$i]->crop_title);
                    $filesx[$i]->post_title = str_replace('-', ' ', $filesx[$i]->post_title);
                }
            }
            unset($files);
            $files = $filesx;
        }

        $isTreeThemeInShortCode = ($isThemeShortCodeParam && isset($shortcode_param['theme']) && $shortcode_param['theme'] === 'tree') ? true : false;
        $rootCloneThemeTypes    = get_option('wpfd_root_theme_types', array());
        if (!empty($rootCloneThemeTypes) && array_key_exists($category->params['theme'], $rootCloneThemeTypes)
            && $rootCloneThemeTypes[$category->params['theme']] === 'tree') {
            $isTreeThemeInShortCode = true;
        }

        if ($theme->getThemeName() !== 'tree' && !$isTreeThemeInShortCode) {
            $files = (is_array($files)) ? array_slice($files, $offset, $limit) : array();
        }

        if ($shortcode_param && isset($shortcode_param['number']) &&
            !empty($shortcode_param['number']) &&
            (is_numeric($shortcode_param['number']) &&
                (int)$shortcode_param['number'] > 0) && !$isTreeThemeInShortCode) {
            $files = array_slice($files, 0, $shortcode_param['number']);
        }

        if ($isThemeShortCodeParam) {
            $themeParams['ordering'] = $ordering;
            $themeParams['orderingdir'] = $orderingdir;
            $themeParams['subcategoriesordering'] = $subcategoriesOrdering;
        }

        $categories_tree = $modelCategories->getLevelCategories($category->term_id);

        $options = array(
            'files'                 => $files,
            'category'              => $category,
            'categories'            => $categories,
            'ordering'              => $ordering,
            'orderingDirection'     => $orderingdir,
            'subcategoriesOrdering' => $subcategoriesOrdering,
            'params'                => $isThemeShortCodeParam ? $themeParams : $params,
            'tpl'                   => $tpl,
            'categories_tree'       => $categories_tree
        );

        if (isset($shortcode_param['default_open'])) {
            $options['default_open'] = (int) $shortcode_param['default_open'];
        }

        if ((int) $params['social'] === 1 && defined('WPFDA_VERSION')) {
            $content = do_shortcode(
                '[wpfdasocial]' . $theme->showCategory($options) . (($category->params['theme'] !== 'tree' && !$isTreeThemeInShortCode && $showPagination) ?
                    wpfd_category_pagination(
                        array('base' => '', 'format' => '', 'current' => max(1, $page), 'total' => $total, 'sourcecat' => $param)
                    ) : ''
                ) . '[/wpfdasocial]'
            );
        } else {
            $content = $theme->showCategory($options) . (($category->params['theme'] !== 'tree' && !$isTreeThemeInShortCode && $showPagination) ?
                    wpfd_category_pagination(
                        array('base' => '', 'format' => '', 'current' => max(1, $page), 'total' => $total, 'sourcecat' => $param)
                    ) : ''
                );
        }
        return $content;
    }

    /**
     * Get content all Category
     *
     * @param boolean $shortcode_param Shortcode params
     * @param boolean $ajax            Ajax params
     *
     * @return string
     */
    public function contentAllCat($shortcode_param = false, $ajax = false)
    {
        wp_enqueue_style(
            'wpfd-front',
            plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        $app = Application::getInstance('Wpfd');
        $allFiles = array();
        $files = array();
        $show_categories = false;
        if ($shortcode_param && isset($shortcode_param['number']) && !empty($shortcode_param['number']) &&
            (is_numeric($shortcode_param['number']) && (int)$shortcode_param['number'] > 0)
        ) {
            $param_number = $shortcode_param['number'];
        }
        $path_wpfdbase = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
        $path_wpfdbase .= DIRECTORY_SEPARATOR . 'WpfdBase.php';
        require_once $path_wpfdbase;
        $modelCategories = Model::getInstance('categoriesfront');
        $categories = $modelCategories->getCategories(0);
        $modelConfig = Model::getInstance('configfront');
        $global_settings = $this->globalConfig;
        $globalCategoriesOrdering = isset($global_settings['global_files_ordering_direction']) ? $global_settings['global_files_ordering_direction'] : 'customorder';
        $globalCategoriesOrderingAll = (isset($global_settings['global_subcategories_ordering_all']) && intval($global_settings['global_subcategories_ordering_all']) === 1) ? true : false;

        if ($globalCategoriesOrderingAll && (string) $globalCategoriesOrdering !== 'customorder') {
            $subcategoriesDirection = (string)$globalCategoriesOrdering === 'namedescending' ? 'desc' : 'asc';
            $categories = $this->wpfdCategoriesOrdering($categories, $subcategoriesDirection);
        }

        // Global theme parameter
        $themes = $modelConfig->getThemes();

        // Using theme parameter in shortcode
        if (isset($shortcode_param['theme']) && !empty($shortcode_param['theme']) && in_array($shortcode_param['theme'], $themes)) {
            $defaultTheme = $shortcode_param['theme'];
        } else {
            $defaultTheme = $global_settings['defaultthemepercategory'];
        }

        $rootThemeTypes = get_option('wpfd_root_theme_types', array());
        $cloneThemeType = (!empty($rootThemeTypes) && array_key_exists($defaultTheme, $rootThemeTypes)) ? $rootThemeTypes[$defaultTheme] : 'none';

        $isTreeTheme = $defaultTheme === 'tree' ? true : false;
        if ($cloneThemeType === 'tree') {
            $isTreeTheme = true;
        }

        if (isset($shortcode_param['show_categories']) && ((int) $shortcode_param['show_categories'] === 1)) {
            $show_categories = true;
        }

        if ($ajax) {
            if ($isTreeTheme && $show_categories) {
                $allFiles = array();
            } else {
                foreach ($categories as $keyCat => $category) {
                    $termId = $category->term_id;
                    if (!is_numeric($termId) && isset($category->wp_term_id)) {
                        $termId = $category->wp_term_id;
                    }
                    $allFile1 = $this->fileAllCat($termId, $shortcode_param);
                    if (!empty($allFile1)) {
                        foreach ($allFile1 as $key => $val) {
                            if (!empty($val)) {
                                $allFiles[] = $val;
                            }
                        }
                    }

                    $childTerms = get_term_children($termId, 'wpfd-category');
                    if (!empty($childTerms)) {
                        foreach ($childTerms as $key => $value) {
                            $allFile1 = $this->fileAllCat($value, $shortcode_param);
                            if (!empty($allFile1)) {
                                foreach ($allFile1 as $key => $val) {
                                    if (!empty($val)) {
                                        $allFiles[] = $val;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $globalFilesOrdering = isset($global_settings['global_files_ordering']) ? $global_settings['global_files_ordering'] : 'title';
        $globalFilesOrderingAll = (isset($global_settings['global_files_ordering_all']) && intval($global_settings['global_files_ordering_all']) === 1) ? true : false;
        $globalFilesOrderingDirection = isset($global_settings['global_files_ordering_direction']) ? $global_settings['global_files_ordering_direction'] : 'desc';
        $globalFilesOrderingDirectionAll = (isset($global_settings['global_files_ordering_direction_all']) && intval($global_settings['global_files_ordering_direction_all']) === 1) ? true : false;
        $ordering = $globalFilesOrderingAll ? $globalFilesOrdering : 'created_time';
        $orderingdir = $globalFilesOrderingDirectionAll ? $globalFilesOrderingDirection : 'desc';

        if ($shortcode_param && isset($shortcode_param['order']) && !empty($shortcode_param['order'])) {
            $ordering = $shortcode_param['order'];
        }

        if ($shortcode_param && isset($shortcode_param['direction']) && !empty($shortcode_param['direction'])) {
            $orderingdir = $shortcode_param['direction'];
        }

        $showPagination = (isset($shortcode_param['enable_pagination']) && intval($shortcode_param['enable_pagination']) === 0) ? false : true;
        $ordering_array = array(
            'created_time', 'modified_time', 'hits', 'size', 'ext', 'version', 'title', 'description', 'ordering');
        if (in_array($ordering, $ordering_array)) {
            switch ($ordering) {
                case 'created_time':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpCreated'));
                    break;
                case 'modified_time':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpUpdated'));
                    break;
                case 'hits':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpHits'));
                    break;
                case 'size':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpSize'));
                    break;
                case 'ext':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpExt'));
                    break;
                case 'version':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpVersionNumber'));
                    break;
                case 'description':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpDescription'));
                    break;
                case 'ordering':
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpTitle'));
                    break;
                default:
                    usort($allFiles, array('WpfdHelperShortcodes', 'cmpTitle'));
                    break;
            }
            if (strtoupper($orderingdir) === 'DESC') {
                $allFiles = array_reverse($allFiles);
            }
        }

        $modelCategory = Model::getInstance('categoryfront');
        if (is_array($categories) && is_countable($categories) && count($categories) === 0) {
            return '';
        }

        $params = $modelConfig->getConfig($defaultTheme);

        // Show categories or not on all categories
        $params['show_categories'] = '0';
        if ($show_categories) {
            $categories = array_filter($categories, function ($category) {
                if ($category->parent === 0) {
                    return true;
                }
            });
            $categories = array_values($categories);
            $params['show_categories'] = '1';
        } else {
            $categories = array();
        }

        $lastRebuildTime = get_option('wpfd_icon_rebuild_time', false);
        if (false === $lastRebuildTime) {
            // Icon CSS was never build, build it
            $lastRebuildTime = WpfdHelperFile::renderCss();
        }

        $iconSet = (isset($global_settings['icon_set'])) ? $global_settings['icon_set'] : 'svg';
        if ($iconSet !== 'default' && in_array($iconSet, array('png', 'svg'))) {
            $path = WpfdHelperFile::getCustomIconPath($iconSet);
            $cssPath = $path . 'styles-' . $lastRebuildTime . '.css';
            if (file_exists($cssPath)) {
                $cssUrl = wpfd_abs_path_to_url($cssPath);
            } else {
                $lastRebuildTime = WpfdHelperFile::renderCss();
                $cssPath = $path . 'styles-' . $lastRebuildTime . '.css';
                if (file_exists($cssPath)) {
                    $cssUrl = wpfd_abs_path_to_url($cssPath);
                } else {
                    // Use default css pre-builed
                    $cssUrl = WPFD_PLUGIN_URL . 'app/site/assets/icons/' . $iconSet . '/icon-styles.css';
                }
            }
            // Include file
            wp_enqueue_style(
                'wpfd-style-icon-set-' . $iconSet,
                $cssUrl,
                array('wpfd-theme-' . $defaultTheme),
                WPFD_VERSION
            );
        }

        $prefix = '';
        if ($defaultTheme !== 'default') {
            $prefix = $defaultTheme . '_';
        }
        // Disable category name
        $params[$prefix . 'showcategorytitle'] = '0';
        // Remove wpfd-categories element

        if (!class_exists('WpfdTheme')) {
            $themeclass = realpath(dirname(WPFD_PLUGIN_FILE)) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'templates';
            $themeclass .= DIRECTORY_SEPARATOR . 'wpfd-theme.class.php';
            require_once $themeclass;
        }
        /**
         * Get theme instance follow priority
         *
         * 1. /wp-content/wp-file-download/themes
         * 2. /wp-content/uploads/wpfd-themes
         * 3. /wp-content/plugins/wp-file-download/app/site/themes
         */
        $theme = wpfd_get_theme_instance($defaultTheme);

        // Set theme params, separator it to made sure theme can work well
        if (method_exists($theme, 'setAjaxUrl')) {
            $theme->setAjaxUrl(wpfd_sanitize_ajax_url(Application::getInstance('Wpfd')->getAjaxUrl()));
        }

        $global_settings['download_category'] = 0;

        if (isset($param_number) && $param_number) {
            $global_settings['paginationnunber'] = (int) $param_number;
        }

        if (method_exists($theme, 'setConfig')) {
            $theme->setConfig($global_settings);
        }

        if (method_exists($theme, 'setPath')) {
            $theme->setPath(Application::getInstance('Wpfd')->getPath());
        }

        if (method_exists($theme, 'setThemeName')) {
            $theme->setThemeName($defaultTheme);
        }

        $files = $this->wpfdArrayUnique($allFiles);
        $countFiles = count($files);
        $limit = $global_settings['paginationnunber'];
        $total = ceil($countFiles / $limit);


        $page = Utilities::getInput('paged', 'POST', 'string');
        $page = $page !== '' ? $page : 1;

        $offset = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        if (!$isTreeTheme) {
            $files = array_slice($files, $offset, $limit);
        }

        $filesx = array();
        // Crop file titles
        if (is_array($files) && !empty($files)) {
            foreach ($files as $i => $file) {
                if (isset($file->state) && (int) $file->state === 0) {
                    continue;
                }
                $filesx[$i]             = $file;
                $filesx[$i]->crop_title = WpfdBase::cropTitle($params, $theme->getThemeName(), $file->post_title);

                $replaceHyphenFileTitle = apply_filters('wpfdReplaceHyphenFileTitle', false);
                if ($replaceHyphenFileTitle) {
                    $filesx[$i]->crop_title = str_replace('-', ' ', $filesx[$i]->crop_title);
                    $filesx[$i]->post_title = str_replace('-', ' ', $filesx[$i]->post_title);
                }
            }
            unset($files);
            $files = $filesx;
        }

        $category = new stdClass();
        $category->name = esc_html__('All Categories', 'wpfd');
        $category->slug = sanitize_title($category->name);
        $category->term_id = 'all_0';
        $category->desc = '';
        $category->params = $params;
        $category->params['theme'] = $defaultTheme;

        // Action to show files
        $showFiles = (isset($shortcode_param['show_files']) && intval($shortcode_param['show_files']) === 0) ? false : true;
        if (!$showFiles) {
            $files = array();
        }

        $categories_tree = $modelCategories->getLevelCategories(0);

        $options = array(
            'files' => $files,
            'category' => $category,
            'categories' => $categories,
            'categories_tree' => $categories_tree,
            'ordering' => $ordering,
            'orderingDirection' => $orderingdir,
            'subcategoriesOrdering' => 'customorder',
            'params' => $params,
            'tpl' => null,
            'shortcode_param' => $shortcode_param,
            'ajax' => $ajax,
            'latest' => false // True: Disable show categories
        );
        $pagination = '';

        if (!$isTreeTheme && $showPagination && $showFiles) {
            $pagination = wpfd_category_pagination(
                array('base' => '', 'format' => '', 'current' => max(1, $page), 'total' => $total, 'sourcecat' => 0)
            );
        }
        // We need to disable pagination on content all cat so temporary
        // todo: fix pagination for content all cat
        
        $content = $theme->showCategory($options) . $pagination;

        if ($ajax) {
            wp_send_json(array('success' => true, 'content' => $content), 200);
        } else {
            return $content;
        }
    }

    /**
     * Remove duplicate values in array
     *
     * @param array  $array    List values
     * @param string $key_name Key name of array
     *
     * @return array
     */
    public function wpfdArrayUnique($array, $key_name = 'ID')
    {
        $duplicate_keys = array();
        $tmp = array();

        foreach ($array as $key => $val) {
            if (is_object($val)) {
                $val = (array)$val;
            }

            if (!in_array($val[$key_name], $tmp)) {
                $tmp[] = $val[$key_name];
            } else {
                $duplicate_keys[] = $key;
            }
        }

        foreach ($duplicate_keys as $key) {
            unset($array[$key]);
        }

        return array_values($array);
    }

    /**
     * Get files all cat
     *
     * @param mixed   $param           Category id
     * @param boolean $shortcode_param Shortcode param
     *
     * @return array|mixed
     */
    public function fileAllCat($param, $shortcode_param = false)
    {
        Application::getInstance('Wpfd');

        $modelCategory = Model::getInstance('categoryfront');
        $global_settings = $this->globalConfig;
        $category = $modelCategory->getCategory($param);
        $globalFileOrdering = isset($global_settings['global_files_ordering']) ? $global_settings['global_files_ordering'] : 'title';
        $globalFileOrderingAll = (isset($global_settings['global_files_ordering_all']) && intval($global_settings['global_files_ordering_all']) === 1) ? true : false;
        $globalFileOrderingDirection = isset($global_settings['global_files_ordering_direction']) ? $global_settings['global_files_ordering_direction'] : 'desc';
        $globalFileOrderingDirectionAll = (isset($global_settings['global_files_ordering_direction_all']) && intval($global_settings['global_files_ordering_direction_all']) === 1) ? true : false;

        if (empty($category)) {
            return '';
        }

        //$themename = $category->params['theme'];
        $params = $category->params;
        $params['social'] = isset($params['social']) ? $params['social'] : 0;
        if ((int) $category->access === 1) {
            $user = wp_get_current_user();
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows = array_intersect($roles, $category->roles);

            $singleuser = false;

            if (isset($params['canview']) && $params['canview'] === '') {
                $params['canview'] = 0;
            }

            $canview = isset($params['canview']) ? (int) $params['canview'] : 0;

            if ((int) $global_settings['restrictfile'] === 1) {
                $user = wp_get_current_user();
                $user_id = (int) $user->ID;

                if ($user_id) {
                    if ($canview === $user_id || $canview === 0) {
                        $singleuser = true;
                    } else {
                        $singleuser = false;
                    }
                } else {
                    if ($canview === 0) {
                        $singleuser = true;
                    } else {
                        $singleuser = false;
                    }
                }
            }

            if ($canview !== 0 && !count($category->roles)) {
                if ($singleuser === false) {
                    return '';
                }
            } elseif ($canview !== 0 && count($category->roles)) {
                if (empty($allows) && !$singleuser) {
                    return '';
                }
            } else {
                if (empty($allows)) {
                    return '';
                }
            }
        }

        $modelFiles = Model::getInstance('filesfront');
        $modelTokens = Model::getInstance('tokens');

        $token = $modelTokens->getOrCreateNew();
        $tpl = null;
        $orderCol = Utilities::getInput('orderCol', 'GET', 'none');
        $ordering = $orderCol !== null ? $orderCol : $category->ordering;
        $orderDir = Utilities::getInput('orderDir', 'GET', 'none');
        $orderingdir = $orderDir !== null ? $orderDir : $category->orderingdir;

        $description = json_decode($category->description, true);
        $lstAllFile = null;
        if ($shortcode_param && isset($shortcode_param['order']) && !empty($shortcode_param['order'])) {
            $ordering = $shortcode_param['order'];
        }
        if ($shortcode_param && isset($shortcode_param['direction']) && !empty($shortcode_param['direction'])) {
            $orderingdir = $shortcode_param['direction'];
        }

        // Apply global ordering for files
        if ($globalFileOrderingAll) {
            $ordering = $globalFileOrdering;
        }

        // Apply global ordering direction for files
        if ($globalFileOrderingDirectionAll) {
            $orderingdir = $globalFileOrderingDirection;
        }

        if (!empty($description) && isset($description['refToFile'])) {
            if (isset($description['refToFile'])) {
                $listCatRef = $description['refToFile'];
                $lstAllFile = $this->getAllFileRef($modelFiles, $listCatRef, $ordering, $orderingdir, $param);
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $param);
        if ($categoryFrom === 'googleDrive') {
            $files = apply_filters(
                'wpfdAddonGetListGoogleDriveFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'googleTeamDrive') {
            $files = apply_filters(
                'wpfdAddonGetListGoogleTeamDriveFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'dropbox') {
            $files = apply_filters(
                'wpfdAddonGetListDropboxFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'onedrive') {
            $files = apply_filters(
                'wpfdAddonGetListOneDriveFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } elseif ($categoryFrom === 'onedrive_business') {
            $files = apply_filters(
                'wpfdAddonGetListOneDriveBusinessFile',
                $param,
                $ordering,
                $orderingdir,
                $category->slug,
                $token
            );
        } else {
            $files = $modelFiles->getFiles($param, $ordering, $orderingdir);

            if (!empty($files) && ((int) $global_settings['restrictfile'] === 1)) {
                $user = wp_get_current_user();
                $user_id = $user->ID;
                foreach ($files as $key => $file) {
                    $metadata = get_post_meta($file->ID, '_wpfd_file_metadata', true);
                    $canview = isset($metadata['canview']) ? $metadata['canview'] : 0;
                    $canview = array_map('intval', explode(',', $canview));
                    if ($user_id) {
                        if (!(in_array($user_id, $canview) || in_array(0, $canview))) {
                            unset($files[$key]);
                        }
                    } else {
                        if (!in_array(0, $canview)) {
                            unset($files[$key]);
                        }
                    }
                }
            }
        }

        if ($lstAllFile && !empty($lstAllFile)) {
            $files = array_merge($lstAllFile, $files);
        }

        return $files;
    }

    /**
     * Get all files reference category
     *
     * @param object  $model             File model
     * @param array   $listCatRef        List Categories
     * @param string  $ordering          Ordering
     * @param string  $orderingDirection Ordering direction
     * @param integer $refCatId          Ref cat id
     *
     * @return array
     */
    public function getAllFileRef($model, $listCatRef, $ordering, $orderingDirection, $refCatId = null)
    {
        $lstAllFile = array();
        foreach ($listCatRef as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $lstFile    = $model->getFiles($key, $ordering, $orderingDirection, $value, $refCatId);
                $lstAllFile = array_merge($lstFile, $lstAllFile);
            }
        }
        return $lstAllFile;
    }

    /**
     * Method to compare by property
     *
     * @param object $a        First object
     * @param object $b        Second object
     * @param string $property Property to sort
     * @param string $type     Type
     *
     * @return boolean|integer
     */
    public function compareByProperty($a, $b, $property, $type = 'string')
    {
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
        switch ($type) {
            case 'datetime':
                $result = (strtotime($a->{$property}) <= strtotime($b->{$property})) ? -1 : 1;
                break;
            case 'number':
                $result = ($a->{$property} >= $b->{$property}) ? 1 : -1;
                break;
            case 'string':
            default:
                if ($lowercaseSort) {
                    $result = strnatcmp($a->{$property}, $b->{$property});
                } else {
                    $result = strnatcmp(strtolower($a->{$property}), strtolower($b->{$property}));
                }
                break;
        }
        return $result;
    }

    /**
     * Method to compare category by property
     *
     * @param object $a        First object
     * @param object $b        Second object
     * @param string $property Property to sort
     * @param string $type     Type
     *
     * @return boolean|integer
     */
    public function categoryCompareByProperty($a, $b, $property, $type = 'string')
    {
        switch ($type) {
            case 'datetime':
                $result = (strtotime($a->{$property}) <= strtotime($b->{$property})) ? -1 : 1;
                break;
            case 'number':
                $result = ($a->{$property} >= $b->{$property}) ? 1 : -1;
                break;
            case 'string':
            default:
                $result = strnatcmp(strtolower($a->{$property}), strtolower($b->{$property}));
                break;
        }
        return $result;
    }

    /**
     * Method to compare created
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpCreated($a, $b)
    {
        return $this->compareByProperty($a, $b, 'created_time', 'datetime');
    }

    /**
     * Method to compare updated
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpUpdated($a, $b)
    {
        return $this->compareByProperty($a, $b, 'modified_time', 'datetime');
    }

    /**
     * Method to compare hits
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpHits($a, $b)
    {
        return $this->compareByProperty($a, $b, 'hits', 'number');
    }

    /**
     * Method to compare size
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpSize($a, $b)
    {
        return $this->compareByProperty($a, $b, 'size', 'number');
    }

    /**
     * Method to compare ext
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpExt($a, $b)
    {
        return $this->compareByProperty($a, $b, 'ext', 'string');
    }

    /**
     * Method to compare versions
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpVersionNumber($a, $b)
    {
        return $this->compareByProperty($a, $b, 'versionNumber', 'string');
    }


    /**
     * Method to compare Description
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpDescription($a, $b)
    {
        return $this->compareByProperty($a, $b, 'description', 'string');
    }

    /**
     * Method to compare title
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpTitle($a, $b)
    {
        return $this->compareByProperty($a, $b, 'post_title', 'string');
    }

    /**
     * Method to compare title
     *
     * @param array  $categories    Categories list
     * @param string $categoryOrder Order method
     *
     * @return array|mixed
     */
    public function wpfdCategoriesOrdering($categories = array(), $categoryOrder = 'asc')
    {
        if (!$categories || empty($categories)) {
            return $categories;
        }

        usort($categories, array('WpfdHelperShortcodes', 'cmpCategoryTitle'));

        if ((string) $categoryOrder === 'desc') {
            $categories = array_reverse($categories);
        }

        return $categories;
    }

    /**
     * Method to compare title
     *
     * @param object $a First object
     * @param object $b Second object
     *
     * @return boolean|integer
     */
    public function cmpCategoryTitle($a, $b)
    {
        return $this->categoryCompareByProperty($a, $b, 'name', 'string');
    }
    /**
     * Check permission for single post
     *
     * @param mixed $post Post object
     * @param mixed $user Current user object
     *
     * @return boolean
     */
    public function wpfdCheckAccess($post, $user)
    {
        $app = Application::getInstance('Wpfd');
        $fileModel = Model::getInstance('filefront');
        $categoryModel = Model::getInstance('categoryfront');

        $file = $fileModel->getFile($post->ID);

        if (!$file) {
            return false;
        }

        $category = $categoryModel->getCategory($file->catid);
        if (empty($category) || is_wp_error($category)) {
            return false;
        }

        if ((int) $category->access === 1) {
            $roles = array();

            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }

            $allows = array_intersect($roles, $category->roles);
            $allows_single = false;

            if (isset($category->params['canview']) && $category->params['canview'] === '') {
                $category->params['canview'] = 0;
            }
            if (isset($category->params['canview']) &&
                ((int) $category->params['canview'] !== 0) &&
                is_countable($category->roles) && // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
                !count($category->roles)
            ) {
                if ((int) $category->params['canview'] === (int) $user->ID) {
                    $allows_single = true;
                }
                if ($allows_single === false) {
                    return false;
                }
            } elseif (isset($category->params['canview']) &&
                ((int) $category->params['canview'] !== 0) &&
                is_countable($category->roles) && // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
                count($category->roles)
            ) {
                if ((int) $category->params['canview'] === (int) $user->ID) {
                    $allows_single = true;
                }

                if (!($allows_single === true || !empty($allows))) {
                    return false;
                }
            } else {
                if (empty($allows)) {
                    return false;
                }
            }
        }
        return $file;
    }
    /**
     * Search shortcode
     *
     * @param string $atts Shortcode attributes
     *
     * @return string
     */
    public function wpfdSearchShortcode($atts)
    {
        wpfd_enqueue_assets();
        wpfd_assets_search();
        wp_enqueue_style(
            'wpfd-front',
            plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        $variables = array(
            'args' => array(),
            'filters' => array(),
            'categories' => array(),
            'allTagsFiles' => '',
            'TagLabels' => array(),
            'availableTags' => array()
        );
        $variables['args'] = shortcode_atts(array(
            'catid' => '0',
            'exclude' => '0',
            'cat_filter' => 1,
            'tag_filter' => 0,
            'display_tag' => 'searchbox',
            'create_filter' => 1,
            'update_filter' => 1,
            'type_filter' => 0,
            'weight_filter' => 0,
            'file_per_page' => 15,
            'show_filters' => 1,
            'show_pagination' => 1,
            'theme' => '',
            'theme_column' => '',
            'show_files' => 0,
            'search_all' => 1
        ), $atts);

        /**
         * Filter to check login for search file(s)
         *
         * @return boolean
         */
        $loginRequired = apply_filters('wpfd_search_login_required', false);
        if ($loginRequired) {
            $user = wp_get_current_user();
            if (isset($user) && intval($user->ID) === 0) {
                $loginMessageVal = esc_html__('Please login to search file(s).', 'wpfd');
                $loginMessage = '<div class="wpfd-search-login-category-message-section">';
                $loginMessage .= '<p class="wpfd-search-login-category-message">';
                $loginMessage .= $loginMessageVal;
                $loginMessage .= '</p>';
                $loginMessage .= '</div>';
                return $loginMessage;
            }
        }

        $q = Utilities::getInput('q', 'GET', 'string');
        if (!empty($q)) {
            $variables['filters']['q'] = $q;
        }
        $catid = Utilities::getInput('catid', 'GET', 'string');
        $categoryName = esc_html__('All Categories', 'wpfd');

        if (!empty($catid)) {
            $variables['filters']['catid'] = $catid;
        }

        // Use default catid in shortcode param
        $rootCategoryId = 0;
        $rootTermId = 0;
        if ((int) $variables['args']['cat_filter'] === 0 && (string) $variables['args']['catid'] !== '0') {
            $variables['filters']['catid'] = (string) $variables['args']['catid'];
        }

        if ((int) $variables['args']['cat_filter'] !== 0 && (string) $variables['args']['catid'] !== '0') {
            $rootCategoryId = $variables['args']['catid'];
            $rootCategory = get_term($rootCategoryId, 'wpfd-category');
            $variables['catname'] = isset($rootCategory->name) ? $rootCategory->name : '';
            $categoryName = $variables['catname'];
        }

        if ($variables['args']['exclude'] !== '') {
            $variables['filters']['exclude'] = $variables['args']['exclude'];
        }

        $ftags = Utilities::getInput('ftags', 'GET', 'none');
        if (is_array($ftags)) {
            $ftags = array_unique($ftags);
            $ftags = implode(',', $ftags);
        } else {
            $ftags = Utilities::getInput('ftags', 'GET', 'string');
        }

        if (!empty($ftags)) {
            $variables['filters']['ftags'] = $ftags;
        }

        $cfrom = Utilities::getInput('cfrom', 'GET', 'string');
        if (!empty($cfrom)) {
            $variables['filters']['cfrom'] = $cfrom;
        }
        $cto = Utilities::getInput('cto', 'GET', 'string');
        if (!empty($cto)) {
            $variables['filters']['cto'] = $cto;
        }
        $ufrom = Utilities::getInput('ufrom', 'GET', 'string');
        if (!empty($ufrom)) {
            $variables['filters']['ufrom'] = $ufrom;
        }
        $uto = Utilities::getInput('uto', 'GET', 'string');
        if (!empty($uto)) {
            $variables['filters']['uto'] = $uto;
        }
        $limit = Utilities::getInput('limit', 'GET', 'string');
        if (empty($limit)) {
            $limit = $variables['args']['file_per_page'];
        }
        $variables['filters']['limit'] = $limit;
        $variables['ordering'] = Utilities::getInput('ordering', 'GET', 'string');
        $variables['dir'] = Utilities::getInput('dir', 'GET', 'string') === null ? 'asc' : 'desc';

        $app = Application::getInstance('Wpfd');
        $modelCategories = Model::getInstance('categoriesfront');
        $modelConfig = Model::getInstance('configfront');
        $modelTokens = Model::getInstance('tokens');
        $token = $modelTokens->getOrCreateNew();
        $theme = Utilities::getInput('theme', 'GET', 'string');
        $themes = $modelConfig->getThemes();
        $categories = $modelCategories->getLevelCategories(0);
        $googleCategoryIds = array();
        $googleSwitchTermIds = array();
        $googleTags = array();
        $googleTeamDriveCategoryIds = array();
        $googleTeamDriveSwitchTermIds = array();
        $googleTeamDriveTags = array();
        $dropboxCategoryIds = array();
        $dropboxSwitchTermIds = array();
        $dropboxTags = array();
        $onedriveCategoryIds = array();
        $onedriveSwitchTermIds = array();
        $onedriveTags = array();
        $onedriveBusinessCategoryIds = array();
        $onedriveBusinessSwitchTermIds = array();
        $onedriveBusinessTags = array();
        $awsCategoryIds = array();
        $awsSwitchTermIds = array();
        $awsTags = array();
        $nextcloudCategoryIds = array();
        $nextcloudSwitchTermIds = array();
        $nextcloudTags = array();

        if ($theme !== '' && in_array($theme, $themes)) {
            $variables['args']['theme'] = $theme;
        }
        $theme = isset($variables['args']['theme']) ? $variables['args']['theme'] : '';
        if ($theme !== '') {
            $params = $modelConfig->getConfig($theme);
            $config = $modelConfig->getGlobalConfig();
            if ($theme === 'default') {
                $params['showfoldertree'] = 0;
                $params['showsubcategories'] = 0;
                $params['showcategorytitle'] = 0;
                $params['showbreadcrumb'] = 0;
                $params['download_popup'] = 0;
                $params['download_selected'] = 0;
                $params['download_category'] = 0;
            } else {
                $params[$theme . '_showfoldertree'] = 0;
                $params[$theme . '_showsubcategories'] = 0;
                $params[$theme . '_showcategorytitle'] = 0;
                $params[$theme . '_showbreadcrumb'] = 0;
                $params[$theme . '_download_popup'] = 0;
                $params[$theme . '_download_selected'] = 0;
                $params[$theme . '_download_category'] = 0;

                if ($theme === 'table') {
                    wp_enqueue_script('wpfd-theme-table-mediatable', plugins_url('app/site/themes/wpfd-table/js/jquery.mediaTable.js', WPFD_PLUGIN_FILE), array('jquery'), WPFD_VERSION, true);
                    wp_enqueue_style('wpfd-theme-table-mediatable', plugins_url('app/site/themes/wpfd-table/css/jquery.mediaTable.css', WPFD_PLUGIN_FILE));
                } else {
                    $ds             = DIRECTORY_SEPARATOR;
                    $wpfdContentDir = wpfd_get_content_dir();
                    $themeCustomUrl = WP_CONTENT_URL . $ds . $wpfdContentDir . $ds . 'themes' . $ds;
                    $cssFilePath    = WP_CONTENT_DIR . $ds . $wpfdContentDir . $ds . 'themes' . $ds . 'wpfd-' . $theme . $ds . 'css' . $ds . 'jquery.mediaTable.css';
                    $jsFilePath     = WP_CONTENT_DIR . $ds . $wpfdContentDir . $ds . 'themes' . $ds . 'wpfd-' . $theme . $ds . 'js' . $ds . 'jquery.mediaTable.js';

                    if (file_exists($cssFilePath)) {
                        wp_enqueue_style(
                            'wpfd-theme-'. $theme .'-mediatable',
                            $themeCustomUrl . 'wpfd-' . $theme . $ds . 'css' . $ds . 'jquery.mediaTable.css',
                            array(),
                            WPFD_VERSION,
                            'all'
                        );
                    }

                    if (file_exists($jsFilePath)) {
                        wp_enqueue_script(
                            'wpfd-theme-' . $theme . 'mediatable',
                            $themeCustomUrl . 'wpfd-' . $theme . $ds . 'js' . $ds . 'jquery.mediaTable.js',
                            array(),
                            WPFD_VERSION,
                            'all'
                        );
                    }
                }
            }
            /**
             * Get theme instance follow priority
             *
             * 1. /wp-content/wp-file-download/themes
             * 2. /wp-content/uploads/wpfd-themes
             * 3. /wp-content/plugins/wp-file-download/app/site/themes
             */
            $themeInstance = wpfd_get_theme_instance($theme);
            // Set theme params, separator it to made sure theme can work well
            if (method_exists($themeInstance, 'setAjaxUrl')) {
                $themeInstance->setAjaxUrl(wpfd_sanitize_ajax_url(Application::getInstance('Wpfd')->getAjaxUrl()));
            }

            if (method_exists($themeInstance, 'setConfig')) {
                $themeInstance->setConfig($config);
            }

            if (method_exists($themeInstance, 'setPath')) {
                $themeInstance->setPath(Application::getInstance('Wpfd')->getPath());
            }

            if (method_exists($themeInstance, 'setThemeName')) {
                $themeInstance->setThemeName($theme);
            }
            wp_enqueue_style(
                'wpfd-front',
                plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );
            $themeInstance->loadAssets();
            $themeInstance->loadLightboxAssets();
        }

        if (!empty($categories)) {
            foreach ($categories as $cate) {
                if (!isset($cate->cloudType) || !$cate->cloudType) {
                    continue;
                }

                if ($cate->cloudType === 'googleDrive') {
                    $currentGoogleCategory = array();
                    $currentGoogleCategory['term_id'] = $cate->wp_term_id;
                    $currentGoogleCategory['google_id'] = $cate->term_id;
                    $currentGoogleCategory['slug'] = $cate->slug;
                    $googleCategoryIds[] = $currentGoogleCategory;
                    $googleSwitchTermIds[$cate->term_id] = $cate->wp_category_id;
                }

                if ($cate->cloudType === 'googleTeamDrive') {
                    $currentGoogleTeamDriveCategory = array();
                    $currentGoogleTeamDriveCategory['term_id'] = $cate->wp_term_id;
                    $currentGoogleTeamDriveCategory['google_team_drive_id'] = $cate->term_id;
                    $currentGoogleTeamDriveCategory['slug'] = $cate->slug;
                    $googleTeamDriveCategoryIds[] = $currentGoogleTeamDriveCategory;
                    $googleTeamDriveSwitchTermIds[$cate->term_id] = $cate->wp_category_id;
                }

                if ($cate->cloudType === 'dropbox') {
                    $currentDropboxCategory = array();
                    $currentDropboxCategory['term_id'] = $cate->wp_term_id;
                    $currentDropboxCategory['dropbox_id'] = $cate->term_id;
                    $currentDropboxCategory['slug'] = $cate->slug;
                    $dropboxCategoryIds[] = $currentDropboxCategory;
                    $dropboxSwitchTermIds[$cate->term_id] = $cate->wp_category_id;
                }

                if ($cate->cloudType === 'onedrive') {
                    $currentOnedriveCategory = array();
                    $currentOnedriveCategory['term_id'] = $cate->wp_term_id;
                    $currentOnedriveCategory['onedrive_id'] = $cate->term_id;
                    $currentOnedriveCategory['slug'] = $cate->slug;
                    $onedriveCategoryIds[] = $currentOnedriveCategory;
                    $onedriveSwitchTermIds[$cate->term_id] = $cate->wp_category_id;
                }

                if ($cate->cloudType === 'onedrive_business') {
                    $currentOnedriveBusinessCategory = array();
                    $currentOnedriveBusinessCategory['term_id'] = $cate->wp_term_id;
                    $currentOnedriveBusinessCategory['onedrive_business_id'] = $cate->term_id;
                    $currentOnedriveBusinessCategory['slug'] = $cate->slug;
                    $onedriveBusinessCategoryIds[] = $currentOnedriveBusinessCategory;
                    $onedriveBusinessSwitchTermIds[$cate->term_id] = $cate->wp_category_id;
                }

                if ($cate->cloudType === 'aws') {
                    $currentAwsCategory = array();
                    $currentAwsCategory['term_id'] = $cate->wp_term_id;
                    $currentAwsCategory['aws_id'] = $cate->term_id;
                    $currentAwsCategory['slug'] = $cate->slug;
                    $awsCategoryIds[] = $currentAwsCategory;
                    $awsSwitchTermIds[$cate->term_id] = $cate->wp_category_id;
                }

                if ($cate->cloudType === 'nextcloud') {
                    $currentNextcloudCategory = array();
                    $currentNextcloudCategory['term_id'] = $cate->wp_term_id;
                    $currentNextcloudCategory['nextcloud_id'] = $cate->term_id;
                    $currentNextcloudCategory['slug'] = $cate->slug;
                    $nextcloudCategoryIds[] = $currentNextcloudCategory;
                    $nextcloudSwitchTermIds[$cate->term_id] = $cate->wp_category_id;
                }
            }
        }

        $catType = '';
        if (is_numeric($rootCategoryId)) {
            $rootTermId = $rootCategoryId;
        } else {
            if (array_key_exists($rootCategoryId, $googleSwitchTermIds) && has_filter('wpfdAddonSearchCloud', 'wpfdAddonSearchCloud')) {
                $rootTermId = WpfdAddonHelper::getTermIdGoogleDriveByGoogleId($rootCategoryId);
                $catType = 'googleDrive';
                if ($rootTermId && is_numeric($rootTermId)) {
                    $googleTerm = get_term($rootTermId, 'wpfd-category');
                    if (!is_wp_error($googleTerm) && !is_null($googleTerm)) {
                        $categoryName = $googleTerm->name;
                    }
                }
            } elseif (array_key_exists($rootCategoryId, $googleTeamDriveSwitchTermIds) && has_filter('wpfdAddonSearchCloudTeamDrive', 'wpfdAddonSearchCloudTeamDrive')) {
                $rootTermId = WpfdAddonHelper::getTermIdByGoogleTeamDriveId($rootCategoryId);
                $catType = 'googleTeamDrive';
                if ($rootTermId && is_numeric($rootTermId)) {
                    $googleTerm = get_term($rootTermId, 'wpfd-category');
                    if (!is_wp_error($googleTerm) && !is_null($googleTerm)) {
                        $categoryName = $googleTerm->name;
                    }
                }
            } elseif (array_key_exists($rootCategoryId, $dropboxSwitchTermIds) && has_filter('wpfdAddonSearchDropbox', 'wpfdAddonSearchDropbox')) {
                $rootTermId =  WpfdAddonHelper::getTermIdDropBoxByDropBoxId($rootCategoryId);
                $catType = 'dropbox';
                if ($rootTermId && is_numeric($rootTermId)) {
                    $dropboxTerm = get_term($rootTermId, 'wpfd-category');
                    if (!is_wp_error($dropboxTerm) && !is_null($dropboxTerm)) {
                        $categoryName = $dropboxTerm->name;
                    }
                }
            } elseif (array_key_exists($rootCategoryId, $onedriveSwitchTermIds) && has_filter('wpfdAddonSearchOneDrive', 'wpfdAddonSearchOneDrive')) {
                $rootTermId = WpfdAddonHelper::getTermIdOneDriveByOneDriveId($rootCategoryId);
                if ($rootTermId && is_numeric($rootTermId)) {
                    $onedriveTerm = get_term($rootTermId, 'wpfd-category');
                    if (!is_wp_error($onedriveTerm) && !is_null($onedriveTerm)) {
                        $categoryName = $onedriveTerm->name;
                    }
                }
            } elseif (array_key_exists($rootCategoryId, $onedriveBusinessSwitchTermIds) && has_filter('wpfdAddonSearchOneDriveBusiness', 'wpfdAddonSearchOneDriveBusiness')) {
                $rootTermId = WpfdAddonHelper::getTermIdOneDriveBusinessByOneDriveId($rootCategoryId);
                if ($rootTermId && is_numeric($rootTermId)) {
                    $onedriveBusinessTerm = get_term($rootTermId, 'wpfd-category');
                    if (!is_wp_error($onedriveBusinessTerm) && !is_null($onedriveBusinessTerm)) {
                        $categoryName = $onedriveBusinessTerm->name;
                    }
                }
            } elseif (array_key_exists($rootCategoryId, $awsSwitchTermIds) && has_filter('wpfdAddonSearchAws', 'wpfdAddonSearchAws')) {
                $rootTermId = $rootCategoryId;
                if ($rootTermId && is_numeric($rootTermId)) {
                    $awsTerm = get_term($rootTermId, 'wpfd-category');
                    if (!is_wp_error($awsTerm) && !is_null($awsTerm)) {
                        $categoryName = $awsTerm->name;
                    }
                }
            } elseif (array_key_exists($rootCategoryId, $nextcloudSwitchTermIds) && has_filter('wpfdAddonSearchNextcloud', 'wpfdAddonSearchNextcloud')) {
                $rootTermId = $rootCategoryId;
                if ($rootTermId && is_numeric($rootTermId)) {
                    $nextcloudTerm = get_term($rootTermId, 'wpfd-category');
                    if (!is_wp_error($nextcloudTerm) && !is_null($nextcloudTerm)) {
                        $categoryName = $nextcloudTerm->name;
                    }
                }
            } else {
                $rootTermId = $rootCategoryId;
            }
        }

        $variables['categories'] = $modelCategories->getLevelCategories($rootTermId);
        $variables['categoryName'] = $categoryName;
        $variables['config'] = $this->globalConfig;
        $existsTags = array();
        if ((int)$variables['args']['tag_filter']) {
            if ($rootCategoryId === 0) {
                $tags = get_terms(array(
                    'taxonomy' => 'wpfd-tag',
                    'orderby' => 'name',
                    'order' => 'ASC',
                    'hide_empty' => 0,
                ));
            } else {
                $tags = self::wpfdRetrieveIdentifyCategoryTags($rootCategoryId);
            }

            if ($tags) {
                foreach ($tags as $tag) {
                    if ($tag->count > 0) {
                        $TagsFiles[$tag->term_id] = '' . esc_attr($tag->slug);
                        $variables['TagLabels'][$tag->term_id] = esc_html($tag->name);
                        $currentTag = new \stdClass;
                        $currentTag->id = $tag->term_id;
                        $currentTag->value = esc_attr($tag->slug);
                        $currentTag->label = esc_html($tag->name);
                        $variables['availableTags'][] = $currentTag;
                        $existsTags[] = $currentTag->label;
                    }
                }
                if (!isset($TagsFiles)) {
                    $TagsFiles = array();
                }
                $variables['allTagsFiles'] = '["' . implode('","', $TagsFiles) . '"]';
                $variables['TagsFiles'] = $TagsFiles;
            }

            // Cloud tags
            if (has_filter('wpfdAddonSearchCloud', 'wpfdAddonSearchCloud')) {
                $googleCategoryList = array();
                if (!empty($googleCategoryIds)) {
                    if ($rootCategoryId !== 0) {
                        $rootGoogleTermCategory = WpfdAddonHelper::getTermIdGoogleDriveByGoogleId($rootCategoryId);
                        $selectedGoogleCategories = $modelCategories->getLevelCategories($rootGoogleTermCategory);
                        if (!empty($selectedGoogleCategories)) {
                            $googleCategoryList = array_map(function ($selectedGoogleCate) {
                                return $selectedGoogleCate->term_id;
                            }, $selectedGoogleCategories);
                        }
                        $googleCategoryList[] = $rootCategoryId;
                    }

                    foreach ($googleCategoryIds as $googleVal) {
                        if ($rootCategoryId !== 0 && !empty($googleCategoryList) &&
                            !in_array($googleVal['google_id'], $googleCategoryList)) {
                            continue;
                        }
                        $resultTags = self::wpfdAddonGoogleGetFileTags($googleVal, $token, $existsTags, $googleTags);
                        $googleTags = array_merge($googleTags, $resultTags);
                        $variables['availableTags'] = array_merge($variables['availableTags'], $googleTags);
                    }

                    if (empty($tag) && !empty($googleTags)) {
                        $googleTagsFiles = array_map(function ($googleTagFile) {
                            return $googleTagFile->label;
                        }, $googleTags);
                        $variables['allTagsFiles'] = '["' . implode('","', $googleTagsFiles) . '"]';
                        $variables['TagsFiles'] = $googleTags;
                    }
                }
            }
            if (has_filter('wpfdAddonSearchCloudTeamDrive', 'wpfdAddonSearchCloudTeamDrive')) {
                $googleTeamDriveCategoryList = array();
                if (!empty($googleTeamDriveCategoryIds)) {
                    if ($rootCategoryId !== 0) {
                        $rootGoogleTeamDriveTermCategory = WpfdAddonHelper::getTermIdByGoogleTeamDriveId($rootCategoryId);
                        $selectedGoogleTeamDriveCategories = $modelCategories->getLevelCategories($rootGoogleTeamDriveTermCategory);
                        if (!empty($selectedGoogleTeamDriveCategories)) {
                            $googleTeamDriveCategoryList = array_map(function ($selectedGoogleTeamDriveCate) {
                                return $selectedGoogleTeamDriveCate->term_id;
                            }, $selectedGoogleTeamDriveCategories);
                        }
                        $googleTeamDriveCategoryList[] = $rootCategoryId;
                    }

                    foreach ($googleTeamDriveCategoryIds as $googleVal) {
                        if ($rootCategoryId !== 0 && !empty($googleTeamDriveCategoryList) &&
                            !in_array($googleVal['google_team_drive_id'], $googleTeamDriveCategoryList)) {
                            continue;
                        }
                        $resultTags = self::wpfdAddonGoogleTeamDriveGetFileTags($googleVal, $token, $existsTags, $googleTeamDriveTags);
                        $googleTeamDriveTags = array_merge($googleTeamDriveTags, $resultTags);
                        $variables['availableTags'] = array_merge($variables['availableTags'], $googleTeamDriveTags);
                    }

                    if (empty($tag) && !empty($googleTeamDriveTags)) {
                        $googleTeamDriveTagsFiles = array_map(function ($googleTeamDriveTagFile) {
                            return $googleTeamDriveTagFile->label;
                        }, $googleTeamDriveTags);
                        $variables['allTagsFiles'] = '["' . implode('","', $googleTeamDriveTagsFiles) . '"]';
                        $variables['TagsFiles'] = $googleTeamDriveTags;
                    }
                }
            }

            if (has_filter('wpfdAddonSearchDropbox', 'wpfdAddonSearchDropbox')) {
                $dropboxCategoryList = array();
                if (!empty($dropboxCategoryIds)) {
                    if ($rootCategoryId !== 0) {
                        $rootDropboxTermCategory = WpfdAddonHelper::getTermIdDropBoxByDropBoxId($rootCategoryId);
                        $selectedDropboxCategories = $modelCategories->getLevelCategories($rootDropboxTermCategory);
                        if (!empty($selectedDropboxCategories)) {
                            $dropboxCategoryList = array_map(function ($selectedDropboxCate) {
                                return $selectedDropboxCate->term_id;
                            }, $selectedDropboxCategories);
                        }
                        $dropboxCategoryList[] = $rootCategoryId;
                    }
                    foreach ($dropboxCategoryIds as $dropboxVal) {
                        if ($rootCategoryId !== 0 && !empty($dropboxCategoryList) &&
                            !in_array($dropboxVal['dropbox_id'], $dropboxCategoryList)) {
                            continue;
                        }
                        $resultTags = self::wpfdAddonDropboxGetFileTags($dropboxVal, $token, $existsTags, $dropboxTags);
                        $dropboxTags = array_merge($dropboxTags, $resultTags);
                        $variables['availableTags'] = array_merge($variables['availableTags'], $dropboxTags);
                    }

                    if (empty($tag) && !empty($dropboxTags)) {
                        $dropBoxTagsFiles = array_map(function ($dropBoxTagFile) {
                            return $dropBoxTagFile->label;
                        }, $dropboxTags);
                        $variables['allTagsFiles'] = '["' . implode('","', $dropBoxTagsFiles) . '"]';
                        $variables['TagsFiles'] = $dropboxTags;
                    }
                }
            }

            // OneDrive tags
            if (has_filter('wpfdAddonSearchOneDrive', 'wpfdAddonSearchOneDrive')) {
                $odFileInfos = get_option('_wpfdAddon_onedrive_fileInfo', false);
                $onedriveCategoryList = array();
                if ($rootCategoryId !== 0) {
                    $rootOnedriveTermCategory = WpfdAddonHelper::getTermIdOneDriveByOneDriveId($rootCategoryId);
                    $selectedOnedriveCategories = $modelCategories->getLevelCategories($rootOnedriveTermCategory);
                    if (!empty($selectedOnedriveCategories)) {
                        $onedriveCategoryList = array_map(function ($selectedOnedriveCate) {
                            return $selectedOnedriveCate->wp_term_id;
                        }, $selectedOnedriveCategories);
                    }
                    $onedriveCategoryList[] = $rootOnedriveTermCategory;
                }
                if (is_array($odFileInfos) && is_countable($odFileInfos)) {
                    $odTagsFiles = array();
                    foreach ($odFileInfos as $odKey => $odValues) {
                        foreach ($odValues as $odId => $odValue) {
                            if (isset($odValue['file_tags']) && $odValue['file_tags'] !== '' && intval($odValue['state']) === 1) {
                                $odTagList = explode(',', $odValue['file_tags']);
                                foreach ($odTagList as $odTag) {
                                    if ($rootCategoryId !== 0 && !empty($onedriveCategoryList)
                                        && !in_array($odKey, $onedriveCategoryList)) {
                                        continue;
                                    }
                                    $odTagsFiles[$odId] = '' . esc_attr($odTag);
                                    $variables['TagLabels'][$odId] = esc_html($odTag);
                                    $currentOdTag = new \stdClass;
                                    $currentOdTag->id = $odId;
                                    $currentOdTag->value = esc_attr($odTag);
                                    $currentOdTag->label = esc_html($odTag);
                                    if (!empty($existsTags) && in_array($currentOdTag->label, $existsTags)) {
                                        continue;
                                    }
                                    $existsTags[] = $currentOdTag->label;
                                    $variables['availableTags'][] = $currentOdTag;
                                }
                            }
                        }
                    }

                    if (empty($tag) && !empty($odTagsFiles)) {
                        $variables['allTagsFiles'] = '["' . implode('","', $odTagsFiles) . '"]';
                        $variables['TagsFiles'] = $odTagsFiles;
                    }
                }
            }

            if (has_filter('wpfdAddonSearchOneDriveBusiness', 'wpfdAddonSearchOneDriveBusiness')) {
                $oneDriveBusinessFileInfos = get_option('_wpfdAddon_onedrive_business_fileInfo', false);
                $onedriveBusinessCategoryList = array();
                if ($rootCategoryId !== 0) {
                    $rootOnedriveBusinessTermCategory = WpfdAddonHelper::getTermIdOneDriveBusinessByOneDriveId($rootCategoryId);
                    $selectedOnedriveBusinessCategories = $modelCategories->getLevelCategories($rootOnedriveBusinessTermCategory);
                    if (!empty($selectedOnedriveBusinessCategories)) {
                        $onedriveBusinessCategoryList = array_map(function ($selectedOnedriveBusinessCate) {
                            return $selectedOnedriveBusinessCate->wp_term_id;
                        }, $selectedOnedriveBusinessCategories);
                    }
                    $onedriveBusinessCategoryList[] = $rootOnedriveBusinessTermCategory;
                }
                if (is_array($oneDriveBusinessFileInfos) && is_countable($oneDriveBusinessFileInfos)) {
                    $oneDriveBusinessFileTags = array();
                    foreach ($oneDriveBusinessFileInfos as $oneDriveBusinessKey => $oneDriveBusinessValues) {
                        foreach ($oneDriveBusinessValues as $oneDriveBusinessId => $oneDriveBusinessValue) {
                            if (isset($oneDriveBusinessValue['file_tags']) && $oneDriveBusinessValue['file_tags'] !== '' && intval($oneDriveBusinessValue['state']) === 1) {
                                $oneDriveBusinessTagList = explode(',', $oneDriveBusinessValue['file_tags']);
                                foreach ($oneDriveBusinessTagList as $oneDriveBusinessTag) {
                                    if ($rootCategoryId !== 0 && !empty($onedriveBusinessCategoryList)
                                        && !in_array($oneDriveBusinessKey, $onedriveBusinessCategoryList)) {
                                        continue;
                                    }
                                    $oneDriveBusinessFileTags[$oneDriveBusinessId] = '' . esc_attr($oneDriveBusinessTag);
                                    $variables['TagLabels'][$oneDriveBusinessId] = esc_html($oneDriveBusinessTag);
                                    $currentOneDriveBusinessTag = new stdClass();
                                    $currentOneDriveBusinessTag->id = $oneDriveBusinessId;
                                    $currentOneDriveBusinessTag->value = esc_attr($oneDriveBusinessTag);
                                    $currentOneDriveBusinessTag->label = esc_html($oneDriveBusinessTag);
                                    if (!empty($existsTags) && in_array($currentOneDriveBusinessTag->label, $existsTags)) {
                                        continue;
                                    }
                                    $existsTags[] = $currentOneDriveBusinessTag->label;
                                    $variables['availableTags'][] = $currentOneDriveBusinessTag;
                                }
                            }
                        }
                    }

                    if (empty($tag) && !empty($oneDriveBusinessFileTags)) {
                        $variables['allTagsFiles'] = '["' . implode('","', $oneDriveBusinessFileTags) . '"]';
                        $variables['TagsFiles'] = $oneDriveBusinessFileTags;
                    }
                }
            }

            // AWS tag
            if (has_filter('wpfdAddonSearchAws', 'wpfdAddonSearchAws')) {
                $awsCategoryList = array();
                if (!empty($awsCategoryIds)) {
                    if ($rootCategoryId !== 0) {
                        $rootAwsTermCategory = $rootCategoryId;
                        $selectedAwsCategories = $modelCategories->getLevelCategories($rootAwsTermCategory);
                        if (!empty($selectedAwsCategories)) {
                            $awsCategoryList = array_map(function ($selectedAwsCate) {
                                return $selectedAwsCate->term_id;
                            }, $selectedAwsCategories);
                        }
                        $awsCategoryList[] = $rootCategoryId;
                    }
                    foreach ($awsCategoryIds as $awsVal) {
                        if ($rootCategoryId !== 0 && !empty($awsCategoryList) &&
                            !in_array($awsVal['aws_id'], $awsCategoryList)) {
                            continue;
                        }
                        $resultTags = self::wpfdAddonAwsGetFileTags($awsVal, $token, $existsTags, $awsTags);
                        $awsTags = array_merge($awsTags, $resultTags);
                        $variables['availableTags'] = array_merge($variables['availableTags'], $awsTags);
                    }

                    if (empty($tag) && !empty($awsTags)) {
                        $awsTagsFiles = array_map(function ($awsTagFile) {
                            return $awsTagFile->label;
                        }, $awsTags);
                        $variables['allTagsFiles'] = '["' . implode('","', $awsTagsFiles) . '"]';
                        $variables['TagsFiles'] = $awsTags;
                    }
                }
            }

            // Nextcloud tag
            if (has_filter('wpfdAddonSearchNextcloud', 'wpfdAddonSearchNextcloud')) {
                $nextcloudCategoryList = array();
                if (!empty($nextcloudCategoryIds)) {
                    if ($rootCategoryId !== 0) {
                        $rootNextcloudTermCategory = $rootCategoryId;
                        $selectedNextcloudCategories = $modelCategories->getLevelCategories($rootNextcloudTermCategory);
                        if (!empty($selectedNextcloudCategories)) {
                            $nextcloudCategoryList = array_map(function ($selectedNextcloudCate) {
                                return $selectedNextcloudCate->term_id;
                            }, $selectedNextcloudCategories);
                        }
                        $nextcloudCategoryList[] = $rootCategoryId;
                    }
                    foreach ($nextcloudCategoryIds as $nextcloudVal) {
                        if ($rootCategoryId !== 0 && !empty($nextcloudCategoryList) &&
                            !in_array($nextcloudVal['nextcloud_id'], $nextcloudCategoryList)) {
                            continue;
                        }
                        $resultTags = self::wpfdAddonNextcloudGetFileTags($nextcloudVal, $token, $existsTags, $nextcloudTags);
                        $nextcloudTags = array_merge($nextcloudTags, $resultTags);
                        $variables['availableTags'] = array_merge($variables['availableTags'], $nextcloudTags);
                    }

                    if (empty($tag) && !empty($nextcloudTags)) {
                        $nextcloudTagsFiles = array_map(function ($nextcloudTagFile) {
                            return $nextcloudTagFile->label;
                        }, $nextcloudTags);
                        $variables['allTagsFiles'] = '["' . implode('","', $nextcloudTagsFiles) . '"]';
                        $variables['TagsFiles'] = $nextcloudTags;
                    }
                }
            }

            if (!empty($variables['availableTags'])) {
                foreach ($variables['availableTags'] as $iTag => $vTag) {
                    if (strval($vTag->value) === '') {
                        unset($variables['availableTags'][$iTag]);
                    }
                }

                $variables['availableTags'] = $this->wpfdArrayUnique($variables['availableTags'], 'value');
            }
        }

        $variables['baseUrl'] = $app->getBaseUrl();
        $variables['ajaxUrl'] = wpfd_sanitize_ajax_url($app->getAjaxUrl());
        $lastRebuildTime = get_option('wpfd_icon_rebuild_time', false);
        if (false === $lastRebuildTime) {
            // Icon CSS was never build, build it
            $lastRebuildTime = WpfdHelperFile::renderCss();
        }

        $iconSet = (isset($variables['config']['icon_set'])) ? $variables['config']['icon_set'] : 'svg';
        if ($iconSet !== 'default' && in_array($iconSet, array('png', 'svg'))) {
            $path = WpfdHelperFile::getCustomIconPath($iconSet);
            $cssPath = $path . 'styles-' . $lastRebuildTime . '.css';
            if (file_exists($cssPath)) {
                $cssUrl = wpfd_abs_path_to_url($cssPath);
            } else {
                // Use default css pre-builed
                $cssUrl = WPFD_PLUGIN_URL . 'app/site/assets/icons/' . $iconSet . '/icon-styles.css';
            }
            // Include file
            wp_enqueue_style(
                'wpfd-style-icon-set-' . $iconSet,
                $cssUrl,
                array('wpfd-front'),
                WPFD_VERSION
            );
        }

        $variables['show_list_file_tpl'] = '';
        // phpcs:ignore
        if ($variables['args']['show_files'] && empty($_GET)) {
            $url = $variables['ajaxUrl'].'task=search.display';
            $params = [
                'catid' => $variables['args']['catid'],
                'show_pagination' => $variables['args']['show_pagination'],
                'paged' => 1,
                'limit' => $variables['args']['file_per_page'],
                'theme_column' => $variables['args']['theme_column']
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode === 200 && $response !== false) {
                $variables['show_list_file_tpl'] = $response;
            }

            curl_close($ch);
        }

        $shortcodeHtml = wpfd_get_template_html('tpl-search-form.php', $variables);
        $css = file_get_contents(plugin_dir_path(WPFD_PLUGIN_FILE) . 'app/site/assets/css/search_filter.css');
        $loadingCss = file_get_contents(plugin_dir_path(WPFD_PLUGIN_FILE) . 'app/site/assets/css/placeholder-loading.min.css');
        if ($css) {
            // Replace fonts, images path
            $css = str_replace('../fonts', plugin_dir_url(WPFD_PLUGIN_FILE) . 'app/site/assets/fonts', $css);
            $css = str_replace('../images', plugin_dir_url(WPFD_PLUGIN_FILE) . 'app/site/assets/images', $css);
            $css = str_replace('../../../admin', plugin_dir_url(WPFD_PLUGIN_FILE) . 'app/admin', $css);
            $shortcodeHtml = '<style>' . $css . '</style>' . $shortcodeHtml;
        }

        if ($loadingCss) {
            $shortcodeHtml = '<style>' . $loadingCss . '</style>' . $shortcodeHtml;
        }

        return $shortcodeHtml;
    }

    /**
     * Google file tags
     *
     * @param array $googleCategory Short google category
     * @param mixed $token          Token
     * @param array $existsTags     Tag existing
     * @param array $googleTags     Google tag list
     *
     * @return array
     */
    public function wpfdAddonGoogleGetFileTags($googleCategory = array(), $token = '', $existsTags = array(), $googleTags = array())
    {
        if (empty($googleCategory)) {
            return $googleTags;
        }

        $files = apply_filters('wpfdAddonGetListGoogleDriveFile', $googleCategory['term_id'], 'title', 'asc', $googleCategory['slug'], $token);

        if (empty($files)) {
            return $googleTags;
        }

        foreach ($files as $file) {
            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                $fileTags = explode(',', $file->file_tags);
                foreach ($fileTags as $tag) {
                    if (!in_array($tag, $googleTags) && !in_array($tag, $existsTags)) {
                        $currentGoogleTag = new stdClass();
                        $currentGoogleTag->id = $googleCategory['term_id'];
                        $currentGoogleTag->value = esc_html($tag);
                        $currentGoogleTag->label = esc_attr($tag);
                        $googleTags[] = $currentGoogleTag;
                        $existsTags[] = esc_attr($tag);
                    }
                }
            }
        }

        return $googleTags;
    }

    /**
     * Google team drive file tags
     *
     * @param array $googleTeamDriveCategory Short google team drive category
     * @param mixed $token                   Token
     * @param array $existsTags              Tag existing
     * @param array $googleTeamDriveTags     Google tag list
     *
     * @return array
     */
    public function wpfdAddonGoogleTeamDriveGetFileTags($googleTeamDriveCategory = array(), $token = '', $existsTags = array(), $googleTeamDriveTags = array())
    {
        if (empty($googleTeamDriveCategory)) {
            return $googleTeamDriveTags;
        }

        $files = apply_filters('wpfdAddonGetListGoogleTeamDriveFile', $googleTeamDriveCategory['term_id'], 'title', 'asc', $googleTeamDriveCategory['slug'], $token);

        if (empty($files)) {
            return $googleTeamDriveTags;
        }

        foreach ($files as $file) {
            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                $fileTags = explode(',', $file->file_tags);
                foreach ($fileTags as $tag) {
                    if (!in_array($tag, $googleTeamDriveTags) && !in_array($tag, $existsTags)) {
                        $currentGoogleTeamDriveTag = new stdClass();
                        $currentGoogleTeamDriveTag->id = $googleTeamDriveCategory['term_id'];
                        $currentGoogleTeamDriveTag->value = esc_html($tag);
                        $currentGoogleTeamDriveTag->label = esc_attr($tag);
                        $googleTeamDriveTags[] = $currentGoogleTeamDriveTag;
                        $existsTags[] = esc_attr($tag);
                    }
                }
            }
        }

        return $googleTeamDriveTags;
    }

    /**
     * Dropbox file tags
     *
     * @param array $dropboxCategory Short dropbox category
     * @param mixed $token           Token
     * @param array $existsTags      Tag existing
     * @param array $dropboxTags     Dropbox tag list
     *
     * @return array
     */
    public function wpfdAddonDropboxGetFileTags($dropboxCategory = array(), $token = '', $existsTags = array(), $dropboxTags = array())
    {
        if (empty($dropboxCategory)) {
            return $dropboxTags;
        }

        $termId = $dropboxCategory['term_id'];
        $files = apply_filters('wpfdAddonGetListDropboxFile', $termId, 'title', 'asc', $dropboxCategory['slug'], $token);

        if (empty($files)) {
            return $dropboxTags;
        }

        foreach ($files as $file) {
            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                $fileTags = explode(',', $file->file_tags);
                foreach ($fileTags as $tag) {
                    if (!in_array($tag, $dropboxTags) && !in_array($tag, $existsTags)) {
                        $currentDropboxTag = new stdClass();
                        $currentDropboxTag->id = $dropboxCategory['term_id'];
                        $currentDropboxTag->value = esc_html($tag);
                        $currentDropboxTag->label = esc_attr($tag);
                        $dropboxTags[] = $currentDropboxTag;
                        $existsTags[] = esc_attr($tag);
                    }
                }
            }
        }

        return $dropboxTags;
    }

    /**
     * AWS file tags
     *
     * @param array $awsCategory Short AWS category
     * @param mixed $token       Token
     * @param array $existsTags  Tag existing
     * @param array $awsTags     AWS tag list
     *
     * @return array
     */
    public function wpfdAddonAwsGetFileTags($awsCategory = array(), $token = '', $existsTags = array(), $awsTags = array())
    {
        if (empty($awsCategory)) {
            return $awsTags;
        }

        $termId = $awsCategory['term_id'];
        $files = apply_filters('wpfdAddonGetListAwsFile', $termId, 'title', 'asc', $awsCategory['slug'], $token);

        if (empty($files)) {
            return $awsTags;
        }

        foreach ($files as $file) {
            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                $fileTags = explode(',', $file->file_tags);
                foreach ($fileTags as $tag) {
                    if (!in_array($tag, $awsTags) && !in_array($tag, $existsTags)) {
                        $currentAwsTag = new stdClass();
                        $currentAwsTag->id = $awsCategory['term_id'];
                        $currentAwsTag->value = esc_html($tag);
                        $currentAwsTag->label = esc_attr($tag);
                        $awsTags[] = $currentAwsTag;
                        $existsTags[] = esc_attr($tag);
                    }
                }
            }
        }

        return $awsTags;
    }

    /**
     * Nextcloud file tags
     *
     * @param array $nextcloudCategory Short Nextcloud category
     * @param mixed $token             Token
     * @param array $existsTags        Tag existing
     * @param array $nextcloudTags     Nextcloud tag list
     *
     * @return array
     */
    public function wpfdAddonNextcloudGetFileTags($nextcloudCategory = array(), $token = '', $existsTags = array(), $nextcloudTags = array())
    {
        if (empty($nextcloudCategory)) {
            return $nextcloudTags;
        }

        $termId = $nextcloudCategory['term_id'];
        $files = apply_filters('wpfdAddonGetListNextcloudFile', $termId, 'title', 'asc', $nextcloudCategory['slug'], $token);

        if (empty($files)) {
            return $nextcloudTags;
        }

        foreach ($files as $file) {
            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                $fileTags = explode(',', $file->file_tags);
                foreach ($fileTags as $tag) {
                    if (!in_array($tag, $nextcloudTags) && !in_array($tag, $existsTags)) {
                        $currentNextcloudTag = new stdClass();
                        $currentNextcloudTag->id = $nextcloudCategory['term_id'];
                        $currentNextcloudTag->value = esc_html($tag);
                        $currentNextcloudTag->label = esc_attr($tag);
                        $nextcloudTags[] = $currentNextcloudTag;
                        $existsTags[] = esc_attr($tag);
                    }
                }
            }
        }

        return $nextcloudTags;
    }
    
    /**
     * Get all tags on identify category
     *
     * @param integer|string $catId Category id
     *
     * @return array
     */
    public function wpfdRetrieveIdentifyCategoryTags($catId)
    {
        global $wpdb;
        $result = array();
        if (is_null($catId) || empty($catId)) {
            return $result;
        }

        if (is_numeric($catId)) {
            $term  = get_term($catId, 'wpfd-category', OBJECT);

            if (!is_wp_error($term) && !is_null($term)) {
                $cats = get_term_children($term->term_id, 'wpfd-category');

                if (!is_wp_error($cats) && !empty($cats)) {
                    $cats[] = $term->term_id;
                    $terms = implode(',', esc_sql($cats));
                } else {
                    $terms = (string) esc_sql($term->term_id);
                }

                if (empty($terms)) {
                    return $result;
                }

                // Query get tags
                $tags = $wpdb->get_results(
                    'SELECT DISTINCT t.*, x.count from ' . $wpdb->terms . ' as t
                    INNER JOIN ' . $wpdb->term_relationships . ' as s on t.term_id = s.term_taxonomy_id
                    INNER JOIN ' . $wpdb->term_taxonomy . ' as x on x.term_taxonomy_id = s.term_taxonomy_id
                    WHERE s.object_id IN (SELECT p.ID from ' . $wpdb->posts . ' as p
                    INNER JOIN ' . $wpdb->term_relationships . ' as r on p.ID = r.object_id
                    WHERE r.term_taxonomy_id IN (' . $terms . '))
                    AND x.taxonomy = \'wpfd-tag\'
                    ORDER BY t.name ASC;'
                );

                if ($tags) {
                    $result = $tags;
                }
            }
        }

        return $result;
    }
}
