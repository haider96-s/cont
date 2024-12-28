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

defined('ABSPATH') || die();

$app = Application::getInstance('Wpfd');

load_plugin_textdomain(
    'wpfd',
    null,
    dirname(plugin_basename(WPFD_PLUGIN_FILE)) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'languages'
);

/**
 * Check is rest api call
 *
 * @return boolean
 */
function wpfd_is_rest_api_request()
{
    if (empty($_SERVER['REQUEST_URI'])) {
        // Probably a CLI request
        return false;
    }

    $rest_prefix         = trailingslashit(rest_get_url_prefix());
    $is_rest_api_request = ( false !== strpos($_SERVER['REQUEST_URI'], $rest_prefix) );

    return apply_filters('wpfd_is_rest_api_request', $is_rest_api_request);
}

add_action('init', 'wpfd_session_start', -1);
/**
 * Start new or resume existing session
 *
 * @return void
 */
function wpfd_session_start()
{
    if (!wpfd_is_rest_api_request() && is_user_logged_in()) {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
}

add_action('wp_ajax_nopriv_wpfd', 'wpfd_ajax');
add_action('wp_ajax_wpfd', 'wpfd_ajax');
add_action('init', 'wpfd_register_post_type');
add_filter('woocommerce_prevent_admin_access', 'wpfd_disable_woo_login', 10, 1);
add_filter('posts_where', 'wpfd_files_query', 100, 2);
add_action('media_buttons', 'wpfd_button');
// Enable shortcodes in text widgets
add_filter('widget_text', 'do_shortcode');

add_shortcode('wpfd_upload', 'wpfd_upload_shortcode');
add_action('wp_enqueue_scripts', 'wpfd_register_assets');
add_action('init', 'wpfdDownloadFile');
/**
 * Method execute ajax
 *
 * @return void
 */
function wpfd_ajax()
{
    $application = Application::getInstance('Wpfd');
    $path_wpfdbase = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
    $path_wpfdbase .= DIRECTORY_SEPARATOR . 'WpfdBase.php';
    require_once $path_wpfdbase;
    $application->execute('file.download');
}

if (!get_option('_wpfd_import_notice_flag', false)) {
    $application = Application::getInstance('Wpfd');
    $path_wpfdtool = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
    $path_wpfdtool .= DIRECTORY_SEPARATOR . 'WpfdTool.php';
    require_once $path_wpfdtool;
}


/**
 * Search query
 *
 * @param string $where Where
 * @param object $ob    Ob
 *
 * @return string
 */
function wpfd_files_query($where, $ob)
{
    global $wpdb;
    $postTypes = $ob->get('post_type');
    if (is_array($postTypes) && !empty($postTypes) && in_array('wpfd_file', $postTypes)) {
        $where .= ' AND ' . $wpdb->prefix . "posts.post_date <= '" . current_time('mysql') . "'";
    }
    return $where;
}

/**
 * Register post type
 *
 * @return void
 */
function wpfd_register_post_type()
{
    $labels = array(
        'label' => esc_html__('WP File Download', 'wpfd'),
        'rewrite' => array('slug' => 'wp-file-download'),
        'menu_name' => esc_html__('WP File Download', 'wpfd'),
        'hierarchical' => true,
        'show_in_nav_menus' => true,
        'show_ui' => false
    );

    register_taxonomy('wpfd-category', 'wpfd_file', $labels);
    $labels = array(
        'name' => _x('Tags', 'wpfd'), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Domain is optional
        'singular_name' => _x('Tag', 'wpfd'), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Domain is optional
        'search_items' => esc_html__('Search Tags', 'wpfd'),
        'popular_items' => esc_html__('Popular Tags', 'wpfd'),
        'all_items' => esc_html__('All Tags', 'wpfd'),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => esc_html__('Edit Tag', 'wpfd'),
        'update_item' => esc_html__('Update Tag', 'wpfd'),
        'add_new_item' => esc_html__('Add New Tag', 'wpfd'),
        'new_item_name' => esc_html__('New Tag Name', 'wpfd'),
        'separate_items_with_commas' => esc_html__('Separate tags with commas', 'wpfd'),
        'add_or_remove_items' => esc_html__('Add or remove tags', 'wpfd'),
        'choose_from_most_used' => esc_html__('Choose from the most used tags', 'wpfd'),
        'not_found' => esc_html__('No tags found.', 'wpfd'),
        'menu_name' => esc_html__('Tags', 'wpfd'),
    );

    $args = array(
        'public' => false,
        'rewrite' => false,
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => false,
        'show_admin_column' => false,
        'query_var' => false,
    );

    register_taxonomy('wpfd-tag', 'wpfd_file', $args);

    $publicWpfdFile = true;
    $config = get_option('_wpfd_global_search_config', null);
    if (!is_null($config) && isset($config['include_global_search']) && intval($config['include_global_search']) === 0) {
        $publicWpfdFile = false;
    }
    register_post_type(
        'wpfd_file',
        array(
            'labels' => array(
                'name' => esc_html__('Files', 'wpfd'),
                'singular_name' => esc_html__('File', 'wpfd')
            ),
            'public' => $publicWpfdFile,
            'show_ui' => false,
            'show_in_nav_menu' => false,
            'exclude_from_search' => true,
            'taxonomies' => array('wpfd-category'),
            'has_archive' => false,
            'rewrite' => array('slug' => 'wpfd_file', 'with_front' => true),
        )
    );
}

/**
 * Disable woocommerce login when downloading a file
 *
 * @param boolean $bool Return value
 *
 * @return boolean
 */
function wpfd_disable_woo_login($bool)
{
    return false;
}


/**
 * Display category
 *
 * @return void
 */
function wpfd_detail_category()
{

    $term = get_queried_object();
    if ((string)$term->taxonomy !== 'wpfd-category') {
        return;
    }

    wp_enqueue_style(
        'wpfd-front',
        plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );

    $application = Application::getInstance('Wpfd');
    $path_wpfdbase = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
    $path_wpfdbase .= DIRECTORY_SEPARATOR . 'WpfdBase.php';
    require_once $path_wpfdbase;

    $modelFiles = Model::getInstance('filesfront');
    $modelCategories = Model::getInstance('categoriesfront');
    $modelCategory = Model::getInstance('categoryfront');
    $category = $modelCategory->getCategory($term->term_id);

    $orderCol = Utilities::getInput('orderCol', 'GET', 'none');
    $orderDir = Utilities::getInput('orderDir', 'GET', 'none');
    $ordering = $orderCol !== null ? $orderCol : $category->ordering;
    $orderingdir = $orderDir !== null ? $orderDir : $category->orderingdir;
    $files = $modelFiles->getFiles($term->term_id, $ordering, $orderingdir);
    $categories = $modelCategories->getCategories($term->term_id);
    $themename = $category->params['theme'];
    $params = $category->params;
    $themefile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'wpfd-';
    $themefile .= strtolower($themename) . DIRECTORY_SEPARATOR . 'theme.php';

    if (file_exists($themefile)) {
        include_once $themefile;
    }

    $class = 'WpfdTheme' . ucfirst(str_replace('_', '', $themename));
    $theme = new $class();
    $options = array('files' => $files, 'category' => $category, 'categories' => $categories, 'params' => $params);

    echo $theme->showCategory($options); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escape in showCategory
}

/**
 * View file
 *
 * @return void
 */
function wpfd_file_viewer()
{

    $post_type = get_query_var('post_type');
    if ($post_type !== 'wpfd_file') {
        return;
    }

    wp_enqueue_style(
        'wpfd-front',
        plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );

    $id = get_the_ID();
    $catid = Utilities::getInt('catid');
    $ext = Utilities::getInput('ext', 'GET', 'string');
    $mediaType = Utilities::getInput('type', 'GET', 'string');

    $app = Application::getInstance('Wpfd');
    $downloadLink = wpfd_sanitize_ajax_url($app->getAjaxUrl()). '&task=file.download&wpfd_file_id=' . $id . '&wpfd_category_id=';
    $downloadLink .= $catid . '&preview=1';
    $mineType = WpfdHelperFile::mimeType(strtolower($ext));
    wp_enqueue_script('jquery');
    wp_enqueue_style(
        'wpfd-mediaelementplayer',
        plugins_url('app/site/assets/css/mediaelementplayer.min.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_enqueue_script(
        'wpfd-mediaelementplayer',
        plugins_url('app/site/assets/js/mediaelement-and-player.js', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );


    $themefile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'frontviewer';
    $themefile .= DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'default.php';
    if (file_exists($themefile)) {
        include_once $themefile;
    }
}

// Add assets to front
add_action('wp_enqueue_scripts', function () {
    Application::getInstance('Wpfd');
    $modelConfig = Model::getInstance('configfront');
    $config = $modelConfig->getGlobalConfig();

    if ((int)$config['enablewpfd'] !== 1 || is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
        return;
    }

    wp_enqueue_script('wpfd-mediaTable', plugins_url('app/site/themes/wpfd-table/js/jquery.mediaTable.js', WPFD_PLUGIN_FILE), array('jquery'));
    wp_enqueue_style('wpfd-modal', plugins_url('app/admin/assets/css/leanmodal.css', WPFD_PLUGIN_FILE));
    wp_enqueue_script('wpfd-modal', plugins_url('app/admin/assets/js/jquery.leanModal.min.js', WPFD_PLUGIN_FILE), array('jquery'));
    wp_enqueue_script('wpfd-modal-init', plugins_url('app/site/assets/js/leanmodal.init.js', WPFD_PLUGIN_FILE), array('jquery'));
    wp_localize_script('wpfd-modal-init', 'wpfdmodalvars', array(
        'adminurl' => admin_url(),
        'wpfd_iframe_title' => esc_html__('WP File Download Iframe', 'wpfd')
    ));
    wp_enqueue_style(
        'wpfd-viewer',
        plugins_url('app/site/assets/css/viewer.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );

    wp_localize_script('wpfd-mediaTable', 'wpfd_var', array(
        'adminurl' => admin_url('admin.php'),
        'wpfdajaxurl' => admin_url('admin-ajax.php').'?juwpfisadmin=false&action=wpfd&',
    ));
});

/**
 * Display insert wpfd button
 *
 * @return void
 */
function wpfd_button()
{
    Application::getInstance('Wpfd');
    $modelConfig = Model::getInstance('configfront');
    $config = $modelConfig->getGlobalConfig();
    if ((int)$config['enablewpfd'] === 1) {
        $context = "<a href='#wpfdmodal' class='button wpfdlaunch' id='wpfdlaunch' title='WP File Download'>";
        $context .= "<span class='dashicons dashicons-download' style='line-height: inherit;'></span> ";
        $context .= esc_html__('WP File Download', 'wpfd') . '</a>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content escape above
        echo $context;
    }
}

/**
 * Upload access
 *
 * @return void
 */
function wpfd_assets_upload()
{
    wp_enqueue_script('jquery.filedrop', plugins_url('app/admin/assets/js/jquery.filedrop.min.js', WPFD_PLUGIN_FILE));
    wp_enqueue_script('wpfd.bootbox.upload', plugins_url('app/admin/assets/js/bootbox.js', WPFD_PLUGIN_FILE));
    wp_enqueue_script('wpfd-base64js', plugins_url('app/admin/assets/js/encodingHelper.js', WPFD_PLUGIN_FILE));
    wp_enqueue_script('wpfd-TextEncoderLite', plugins_url('app/admin/assets/js/TextEncoderLite.js', WPFD_PLUGIN_FILE));
    wp_enqueue_style('wpfd-jquery-qtip-style', plugins_url('app/admin/assets/ui/css/jquery.qtip.css', WPFD_PLUGIN_FILE), array(), WPFD_VERSION);
    wp_enqueue_script(
        'wpfd-jquery-qtip',
        plugins_url('app/admin/assets/ui/js/jquery.qtip.min.js', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_enqueue_script('resumable', plugins_url('app/admin/assets/js/resumable.js', WPFD_PLUGIN_FILE));
    wp_enqueue_script(
        'wpfd-upload',
        plugins_url('app/site/assets/js/wpfd.upload.js', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_enqueue_style(
        'wpfd-search_filter',
        plugins_url('app/site/assets/css/search_filter.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_localize_script('wpfd-upload', 'wpfd_permissions', array(
        'can_create_category' => wpfd_can_create_category(),
        'can_edit_category' => (wpfd_can_edit_category() || wpfd_can_edit_own_category()) ? true : false,
        'can_delete_category' => (wpfd_can_delete_category() || wpfd_can_edit_own_category()) ? true : false,
        'translate' => array(
            'wpfd_create_category' => esc_html__("You don't have permission to create new category", 'wpfd'),
            'wpfd_edit_category' => esc_html__("You don't have permission to edit category", 'wpfd')
        ),
    ));
    wp_localize_script('wpfd-upload', 'wpfd_var', array(
        'adminurl' => admin_url('admin.php'),
        'wpfdajaxurl' => admin_url('admin-ajax.php'),
    ));
    Application::getInstance('Wpfd');
    $configModel = Model::getInstance('configfront');
    $config = $configModel->getGlobalConfig();
    if (!class_exists('WpfdTool')) {
        $application   = Application::getInstance('Wpfd');
        $path_wpfdtool = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
        $path_wpfdtool .= DIRECTORY_SEPARATOR . 'WpfdTool.php';
        require_once $path_wpfdtool;
    }
    $serverUploadLimit = min(
        10 * 1024 * 1024, // Maximum for chunks size is 10MB if other settings is greater than 10MB
        WpfdTool::parseSize(ini_get('upload_max_filesize')),
        WpfdTool::parseSize(ini_get('post_max_size'))
    );
    wp_localize_script('wpfd-upload', 'wpfd_admin', array(
        'allowed' => $config['allowedext'],
        'maxFileSize' => $config['maxinputfile'],
        'serverUploadLimit' => ((int) $serverUploadLimit === 0) ? 10 * 1024 * 1204 : $serverUploadLimit,
        'msg_remove_file' => esc_html__('Files removed with success!', 'wpfd'),
        'msg_remove_files' => esc_html__('File(s) removed with success!', 'wpfd'),
        'msg_move_file' => esc_html__('Files moved with success!', 'wpfd'),
        'msg_move_files' => esc_html__('File(s) moved with success!', 'wpfd'),
        'msg_copy_file' => esc_html__('Files copied with success!', 'wpfd'),
        'msg_copy_files' => esc_html__('File(s) copied with success!', 'wpfd'),
        'msg_add_category' => esc_html__('Category created with success!', 'wpfd'),
        'msg_remove_category' => esc_html__('Category removed with success!', 'wpfd'),
        'msg_move_category' => esc_html__('New category order saved!', 'wpfd'),
        'msg_edit_category' => esc_html__('Category renamed with success!', 'wpfd'),
        'msg_edit_category_desc' => esc_html__('Category description updated with success!', 'wpfd'),
        'msg_save_category' => esc_html__('Category config saved with success!', 'wpfd'),
        'msg_save_file' => esc_html__('File config saved with success!', 'wpfd'),
        'msg_ordering_file' => esc_html__('File ordering with success!', 'wpfd'),
        'msg_ordering_file2' => esc_html__('File order saved with success!', 'wpfd'),
        'msg_upload_file' => esc_html__('New File(s) uploaded with success!', 'wpfd'),
        'msg_ask_delete_file' => esc_html__('Are you sure you want to delete this file?', 'wpfd'),
        'msg_ask_delete_files' => esc_html__('Are you sure you want to delete the files you have selected?', 'wpfd'),
        'msg_multi_files_text' => esc_html__(
            'This file is listed in several categories, settings are available in the original version of the file',
            'wpfd'
        ),
        'msg_multi_files_btn_label' => esc_html__('EDIT ORIGINAL FILE', 'wpfd'),
        'msg_copied_to_clipboard' => esc_html__('File URL copied to clipboard', 'wpfd'),
        'msg_add_synchronize'       => esc_html__('Category added to synchronize queue!', 'wpfd'),
        'msg_duplicate_category' => esc_html__('Category duplicate with success!', 'wpfd'),
        'msg_duplicate_category_failed' => esc_html__('Failed to duplicate category', 'wpfd'),
        'msg_duplicate_category_duplicating' => esc_html__('Duplicating...', 'wpfd')
    ));

    wp_localize_script('wpfd-upload', 'wpfdUploadParams', wpfdUploadParams());
}

/**
 * Search shortcode
 *
 * @param string $atts Shortcode Attributes
 *
 * @return string
 */
function wpfd_upload_shortcode($atts)
{
    static $alreadyRun = false;

    wp_enqueue_style(
        'wpfd-upload',
        plugins_url('app/site/assets/css/upload.min.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    // Show login form if user not logged in
    if (!is_user_logged_in()) {
        if ($alreadyRun === true) {
            return '';
        }
        $html = '<div class="wpfd_upload_login_form">';
        $html .= '<h3>' . esc_html__('You need to login to be able to upload file!', 'wpfd') . '</h3>';
        $html .= wp_login_form(array('echo' => false));
        $html .= '</div>';
        $alreadyRun = true;
        return $html;
    }
    $canUploadFiles  = false;
    if (wpfd_can_edit_category() || wpfd_can_edit_own_category()
        || wpfd_can_upload_files()) {
        $canUploadFiles = true;
    }

    if ($canUploadFiles === false) {
        return '';
    }

    $app                = Application::getInstance('Wpfd');
    $modelConfig        = Model::getInstance('configfront');
    $modelFiles         = Model::getInstance('filesfront');
    $modelTokens        = Model::getInstance('tokens');
    $modelCategories    = Model::getInstance('categoriesfront');
    $modelCategory      = Model::getInstance('categoryfront');
    $global_settings    = $modelConfig->getGlobalConfig();
    $categories         = $modelCategories->getLevelCategories();

    if (!class_exists('WpfdBase')) {
        include_once WPFD_PLUGIN_DIR_PATH . '/app/admin/classes/WpfdBase.php';
    }

    if ((!is_array($atts) && $atts === '')
        || (is_array($atts) && !isset($atts['category_id']))) {
        // Load assets
        wp_enqueue_script('wpfd-bootstrap', plugins_url('app/admin/assets/js/bootstrap.min.js', WPFD_PLUGIN_FILE));
        wp_enqueue_style('wpfd-bootstrap', plugins_url('app/admin/assets/css/bootstrap.min.css', WPFD_PLUGIN_FILE));
        wpfd_enqueue_assets();
        wpfd_assets_upload();
        wp_enqueue_style(
            'wpfd-front',
            plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
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

        if (!isset($args)) {
            $args = array();
        }
        // Random upload form id
        $args['formId']         = rand();
        $args['categoriesList'] = $categoriesList;

        return wpfd_get_template_html('tpl-upload.php', $args);
    } else {
        Application::getInstance('Wpfd');
        $args               = shortcode_atts(array('category_id' => 0), $atts);
        $category           = $modelCategory->getCategory($args['category_id']);
        if (!$category) {
            return '';
        }

        if (isset($atts['display_files'])) {
            $categoryName   = isset($category->name) ? esc_attr($category->name) : '';
            $filesList      = array();
            $token          = $modelTokens->getOrCreateNew();
            $result_limit   = isset($result_limit) ? (int) $result_limit : 25;
            $variables      = array(
                'files'      => array(),
                'ordering'   => 'type',
                'dir'        => 'asc',
                'viewer'     => WpfdBase::loadValue($global_settings, 'use_google_viewer', 'no'),
                'limit'      => $result_limit,
                'baseurl'    => $app->getBaseUrl(),
                'upload_download_selected' => false
            );
            $categoryFrom   = apply_filters('wpfdAddonCategoryFrom', $args['category_id']);
            if ($categoryFrom === 'googleDrive') {
                $filesList = apply_filters(
                    'wpfdAddonGetListGoogleDriveFile',
                    $args['category_id'],
                    $category->ordering,
                    $category->orderingdir,
                    $category->slug,
                    $token
                );
            } elseif ($categoryFrom === 'dropbox') {
                $filesList = apply_filters(
                    'wpfdAddonGetListDropboxFile',
                    $args['category_id'],
                    $category->ordering,
                    $category->orderingdir,
                    $category->slug,
                    $token
                );
            } elseif ($categoryFrom === 'onedrive') {
                $filesList = apply_filters(
                    'wpfdAddonGetListOneDriveFile',
                    $args['category_id'],
                    $category->ordering,
                    $category->orderingdir,
                    $category->slug,
                    $token
                );
            } elseif ($categoryFrom === 'onedrive_business') {
                $filesList = apply_filters(
                    'wpfdAddonGetListOneDriveBusinessFile',
                    $args['category_id'],
                    $category->ordering,
                    $category->orderingdir,
                    $category->slug,
                    $token
                );
            } else {
                $filesList = $modelFiles->getFiles($args['category_id'], 'created_time', 'asc');
            }

            if (!empty($filesList)) {
                foreach ($filesList as $key => $file) {
                    if (isset($file->state) && (int) $file->state === 0) {
                        unset($filesList[$key]);
                    }
                }
                $variables['files'] = $filesList;
            }

            $args['display_files'] = 1;
            $args['variables']     = $variables;
            $args['categoryName']  = $categoryName;
        }

        if (wpfd_can_edit_category()) {
            $params = $category->params;
            if ((int)$category->access === 1) {
                $user = wp_get_current_user();
                $roles = array();
                foreach ($user->roles as $role) {
                    $roles[] = strtolower($role);
                }
                $allows = array_intersect($roles, $category->roles);

                $singleuser = false;

                if (isset($params['canview']) && (string)$params['canview'] === '') {
                    $params['canview'] = 0;
                }

                $canview = isset($params['canview']) ? $params['canview'] : 0;
                if ((int)$global_settings['restrictfile'] === 1) {
                    $user = wp_get_current_user();
                    $user_id = $user->ID;

                    if ($user_id) {
                        if ((int)$canview === (int)$user_id || (int)$canview === 0) {
                            $singleuser = true;
                        } else {
                            $singleuser = false;
                        }
                    } else {
                        if ((int)$canview === 0) {
                            $singleuser = true;
                        } else {
                            $singleuser = false;
                        }
                    }
                }

                if ((int)$canview !== 0 && !count($category->roles)) {
                    if ($singleuser === false) {
                        return '';
                    }
                } elseif ((int)$canview !== 0 && count($category->roles)) {
                    if (!(!empty($allows) || ($singleuser === true))) {
                        return '';
                    }
                } else {
                    if (empty($allows)) {
                        return '';
                    }
                }
            }
            // Everything seem ok load assets
            wpfd_enqueue_assets();
            wpfd_assets_upload();
            wp_enqueue_style(
                'wpfd-front',
                plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
                array(),
                WPFD_VERSION
            );

            if (!isset($args)) {
                $args = '';
            }
            // Random upload form id
            $args['formId'] = rand();

            return wpfd_get_template_html('tpl-upload.php', $args);
        } else {
            if (wpfd_can_edit_own_category()) {
                $params                 = $category->params;
                $isVisibilityCategory   = true;

                if ((int)$category->access === 1) {
                    $user = wp_get_current_user();
                    $roles = array();
                    foreach ($user->roles as $role) {
                        $roles[] = strtolower($role);
                    }
                    $allows = array_intersect($roles, $category->roles);

                    $singleuser = false;

                    if (isset($params['canview']) && (string)$params['canview'] === '') {
                        $params['canview'] = 0;
                    }

                    $canview = isset($params['canview']) ? $params['canview'] : 0;
                    if ((int)$global_settings['restrictfile'] === 1) {
                        $user = wp_get_current_user();
                        $user_id = $user->ID;

                        if ($user_id) {
                            if ((int)$canview === (int)$user_id || (int)$canview === 0) {
                                $singleuser = true;
                            } else {
                                $singleuser = false;
                            }
                        } else {
                            if ((int)$canview === 0) {
                                $singleuser = true;
                            } else {
                                $singleuser = false;
                            }
                        }
                    }
                    if ((int)$canview !== 0 && !count($category->roles)) {
                        if ($singleuser === false) {
                            $isVisibilityCategory = false;
                        }
                    } elseif ((int)$canview !== 0 && count($category->roles)) {
                        if (!(!empty($allows) || ($singleuser === true))) {
                            $isVisibilityCategory = false;
                        }
                    } else {
                        if (empty($allows)) {
                            $isVisibilityCategory = false;
                        }
                    }
                }

                if ($isVisibilityCategory === false) {
                    return '';
                }

                // Everything seem ok load assets
                wp_enqueue_script('wpfd-bootstrap', plugins_url('app/admin/assets/js/bootstrap.min.js', WPFD_PLUGIN_FILE));
                wp_enqueue_style('wpfd-bootstrap', plugins_url('app/admin/assets/css/bootstrap.min.css', WPFD_PLUGIN_FILE));
                wpfd_enqueue_assets();
                wpfd_assets_upload();
                wp_enqueue_style(
                    'wpfd-front',
                    plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
                    array(),
                    WPFD_VERSION
                );

                if (!isset($args)) {
                    $args = '';
                }
                // Random upload form id
                $args['formId'] = rand();

                return wpfd_get_template_html('tpl-upload.php', $args);
            } else {
                $params                 = $category->params;
                $isVisibilityCategory   = true;

                if ((int)$category->access === 1) {
                    $user   = wp_get_current_user();
                    $roles  = array();
                    foreach ($user->roles as $role) {
                        $roles[] = strtolower($role);
                    }
                    $allows = array_intersect($roles, $category->roles);

                    $singleuser = false;

                    if (isset($params['canview']) && (string)$params['canview'] === '') {
                        $params['canview'] = 0;
                    }

                    $canview = isset($params['canview']) ? $params['canview'] : 0;
                    if ((int)$global_settings['restrictfile'] === 1) {
                        $user = wp_get_current_user();
                        $user_id = $user->ID;

                        if ($user_id) {
                            if ((int)$canview === (int)$user_id || (int)$canview === 0) {
                                $singleuser = true;
                            } else {
                                $singleuser = false;
                            }
                        } else {
                            if ((int)$canview === 0) {
                                $singleuser = true;
                            } else {
                                $singleuser = false;
                            }
                        }
                    }

                    if ((int)$canview !== 0 && !count($category->roles)) {
                        if ($singleuser === false) {
                            $isVisibilityCategory = false;
                        }
                    } elseif ((int)$canview !== 0 && count($category->roles)) {
                        if (!(!empty($allows) || ($singleuser === true))) {
                            $isVisibilityCategory = false;
                        }
                    } else {
                        if (empty($allows)) {
                            $isVisibilityCategory = false;
                        }
                    }
                }

                if ($isVisibilityCategory === false) {
                    return '';
                }

                // Everything seem ok load assets
                wp_enqueue_script('wpfd-bootstrap', plugins_url('app/admin/assets/js/bootstrap.min.js', WPFD_PLUGIN_FILE));
                wp_enqueue_style('wpfd-bootstrap', plugins_url('app/admin/assets/css/bootstrap.min.css', WPFD_PLUGIN_FILE));
                wpfd_enqueue_assets();
                wpfd_assets_upload();
                wp_enqueue_style(
                    'wpfd-front',
                    plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
                    array(),
                    WPFD_VERSION
                );

                if (!isset($args)) {
                    $args = '';
                }
                // Random upload form id
                $args['formId'] = rand();

                return wpfd_get_template_html('tpl-upload.php', $args);
            }
        }
    }
}

/**
 * Print single file content
 *
 * @return void
 */
function wpfdTheContent()
{
    include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'filters.php');
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allow html here
    echo wpfdFilter::wpfdFileContent();
}

/**
 * Method to download a file
 *
 * @return void
 */
function wpfdDownloadFile()
{
    Application::getInstance('Wpfd');
    $doAction       = Utilities::getInput('wpfd_action', 'GET', 'none');
    $catid          = Utilities::getInput('wpfd_category_id', 'GET', 'none');
    $id             = Utilities::getInput('wpfd_file_id', 'GET', 'none');
    $preview        = Utilities::getInput('preview', 'GET', 'none');
    $cloudActivate  = is_plugin_active('wp-file-download-cloud-addon/wp-file-download-addon.php') ? true : false;

    if (is_null($preview)) {
        $preview = 0;
    }

    if (!empty($doAction) && $doAction === 'wpfd_download_file') {
        if (empty($id) || empty($catid)) {
            exit();
        }

        $token = Utilities::getInput('token', 'GET', 'string');

        if (!$preview && !wpfd_can_download_files()) {
            /**
             * Action fire when current user not enough permission to download this file.
             *
             * @param string|integer
             * @param string|integer
             * @param integer
             */
            do_action('wpfd_download_file_permission', $id, $catid, $preview);
            exit(esc_html__('You don\'t have permission to download this file.', 'wpfd'));
        }

        if ($preview && !wpfd_can_preview_files() && $token === '') {
            /**
             * Action fire when current user not enough permission to preview this file.
             *
             * @param string|integer
             * @param string|integer
             * @param integer
             */
            do_action('wpfd_preview_file_permission', $id, $catid, $preview);
            exit(esc_html__('You don\'t have permission to preview this file.', 'wpfd'));
        }
        if (WpfdHelperFile::wpfdIsExpired((int)$id) === true) {
            /**
             * Action for the expired download page
             *
             * @param string|integer
             * @param string|integer
             * @param integer
             */
            do_action('wpfd_download_link_expired', $id, $catid, $preview);
            exit();
        }
        $app = Application::getInstance('Wpfd');
        $modelCategory = Model::getInstance('categoryfront');
        $modelConfig = Model::getInstance('configfront');
        $model = Model::getInstance('filefront');
        $modelNotify = Model::getInstance('notification');
        $modelTokens = Model::getInstance('tokens');

        $config = $modelConfig->getGlobalConfig();
        $category = $modelCategory->getCategory($catid);
        $configNotify = $modelNotify->getNotificationsConfig();

        if (empty($category) || is_wp_error($category)) {
            exit(esc_html__('Category is not correct', 'wpfd'));
        }

        if ($cloudActivate && !class_exists('WpfdAddonHelper')) {
            require_once WPFDA_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelper.php';
        }

        if (!class_exists('WpfdHelper')) {
            $wpfdHelperPath = $app->getPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers';
            $wpfdHelperPath .= DIRECTORY_SEPARATOR . 'WpfdHelper.php';

            require_once($wpfdHelperPath);
        }
        $categoryFrom = WpfdHelper::wpfdAddonCategoryFrom($catid);
        if ($cloudActivate && $categoryFrom === 'googleDrive') {
            /**
             * Filter to check google category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @return   string
             * @internal
             */
            $catid = apply_filters('wpfdAddonDownloadCheckGoogleDriveCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($cloudActivate && $categoryFrom === 'googleTeamDrive') {
            /**
             * Filter to check google team drive category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @return   string
             * @internal
             */
            $catid = apply_filters('wpfdAddonDownloadCheckGoogleTeamDriveCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($cloudActivate && $categoryFrom === 'dropbox') {
            /**
             * Filter to check dropbox category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @return   string
             * @internal
             */
            $catid = apply_filters('wpfdAddonDownloadCheckDropboxCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($cloudActivate && $categoryFrom === 'onedrive') {
            /**
             * Filter to check onedrive category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @return   string
             * @internal
             */
            $catid = apply_filters('wpfdAddonDownloadCheckOneDriveCategory', $catid, $id);

            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($cloudActivate && $categoryFrom === 'onedrive_business') {
            /**
             * Filter to check onedrive business category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @return   string
             * @internal
             */
            $catid = apply_filters('wpfdAddonDownloadCheckOneDriveBusinessCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($cloudActivate && $categoryFrom === 'aws') {
            /**
             * Filter to check aws category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @return   string
             * @internal
             */
            $id = stripslashes(rawurldecode($id));
            $catid = apply_filters('wpfdAddonDownloadCheckAwsCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } elseif ($cloudActivate && $categoryFrom === 'nextcloud') {
            /**
             * Filter to check nextcloud category
             *
             * @param integer Term id
             * @param string  File id
             *
             * @return   string
             * @internal
             */
            $id = stripslashes(rawurldecode($id));
            $catid = apply_filters('wpfdAddonDownloadCheckNextcloudCategory', $catid, $id);
            if (empty($catid)) {
                exit(esc_html__('Download url is not correct', 'wpfd'));
            }
        } else {
            $file_catid = $model->getFileCategory($id);
            if ((int)$catid !== (int)$file_catid) {
                // Try to get ref catid
                if (!$model->isValidRefCatId($id, $catid)) {
                    exit(esc_html__('Download url is not correct', 'wpfd'));
                }
            }
        }

        if ((int)$category->access === 1) {
            $user = wp_get_current_user();
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows = array_intersect($roles, $category->roles);

            if (empty($allows)) {
                $modelTokens->removeTokens();
                $tokenId = $modelTokens->tokenExists($token);
                if ($tokenId) {
                    $modelTokens->updateToken($tokenId);
                } else {
                    if (isset($category->params['canview']) && !empty($category->params['canview'])) {
                        if ((int)$category->params['canview'] !== 0 && (int)$category->params['canview'] !== $user->ID) {
                            /**
                             * Filter to redirect user when they don't have permission to download current file
                             *
                             * @param string
                             */
                            $redirect = apply_filters('wpfd_you_dont_have_permission_redirect_url', false);
                            if ($redirect) {
                                if (!wp_safe_redirect($redirect)) {
                                    header('HTTP/1.0 403 You don\'t have permission');
                                    exit();
                                } else {
                                    exit;
                                }
                            } else {
                                header('HTTP/1.0 403 You don\'t have permission');
                                exit();
                            }
                        }
                    } else {
                        $redirectPageId = isset($config['not_authorized_page']) ? intval($config['not_authorized_page']) : 0;
                        $pageUri = get_permalink($redirectPageId);
                        /**
                         * Filter to redirect user when they not authorized to download current file
                         *
                         * @param string
                         */
                        $redirect = apply_filters('wpfd_not_authorized_redirect_url', $pageUri);
                        if ($redirect) {
                            if (!wp_safe_redirect($redirect)) {
                                header('HTTP/1.0 403 Not authorized');
                                exit();
                            } else {
                                exit;
                            }
                        } else {
                            header('HTTP/1.0 403 Not authorized');
                            exit();
                        }
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }

        // Download limit file handle
        $isLimitDownload = (isset($config['limit_the_download']) && intval($config['limit_the_download']) === 1
            && isset($config['track_user_download']) && intval($config['track_user_download']) === 1) ? true : false;
        if ($isLimitDownload && WpfdHelperFile::downloadLimitHandle($id, $catid)) {
            exit(esc_html__('You have exceeded the maximum number of downloads allowed for this period.', 'wpfd'));
        }

        if (!class_exists('WpfdHelperFolder')) {
            require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelperFolder.php';
        }
        $watermarkedPath = WpfdHelperFolder::getCategoryWatermarkPath();
        $wmImageExt = array('jpg', 'jpeg', 'png');
        /**
         * Download file from WP FileDownload when not exist $fileInfo or wpfdAddon not active
         */
        if ($cloudActivate && $categoryFrom === 'googleDrive') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);

            if (!class_exists('WpfdAddonGoogleDrive')) {
                require_once WPFDA_PLUGIN_DIR_PATH . 'app/admin/classes/WpfdAddonGoogle.php';
            }

            $googleCate = new WpfdAddonGoogleDrive;
            $googleCate->incrHits($id);
            $file = $googleCate->download($id);
            $fileObj = apply_filters('wpfdAddonGetGoogleDriveFile', $id, $category->term_id, $token);
            $fileState = (isset($file->state) && intval($file->state) === 0) ? false : true;
            $fileDownloadUrl = isset($fileObj['linkdownload']) ? $fileObj['linkdownload'] : '#';
            $fileUploadDate  = isset($fileObj['created']) ? $fileObj['created'] : '';
            $fileExt = strtolower($file->ext);
            // Do not download unpublished file.
            if (!$fileState) {
                exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
            }

            if ((int)$preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int)$config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }

            /**
             * Action fire right before a file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             */
            do_action('wpfd_file_download', $id, array('source' => 'googledrive'));

            $googleCate = new wpfdAddonGoogleDrive;
            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);

            $wmCategoryEnabled = checkWatermarkCategoryEnabled($category->term_id);
            if ($wmCategoryEnabled && in_array($fileExt, $wmImageExt)) {
                $application   = Application::getInstance('Wpfd');
                $path_WpfdCategoryWatermark = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
                $path_WpfdCategoryWatermark .= DIRECTORY_SEPARATOR . 'WpfdCategoryWatermark.php';
                require_once $path_WpfdCategoryWatermark;
                $wpfdCategoryWatermark = new WpfdCategoryWatermark();

                $filePath = $watermarkedPath . 'wm_cloud_' . strval(md5($id)) . '.png';

                $ggService = $googleCate->getGoogleService();
                $content = $ggService->files->get($id, ['alt' => 'media']);

                $fs = fopen($filePath, 'wb');
                $result = fwrite($fs, $content->getBody()->getContents());
                fclose($fs);

                $watermarkedFilePath = $wpfdCategoryWatermark->ajaxWatermarkExec($category->term_id, $id, false, true);
                if (file_exists($watermarkedFilePath)) {
                    $fileSize = filesize($watermarkedFilePath);
                    wpfdDownloadHeader($file->title . '.' . $file->ext, (int)$fileSize, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo file_get_contents($watermarkedFilePath);

                    unlink($watermarkedFilePath);
                }
            } else {
                // Serve download for google document
                if (strpos($file->mimeType, 'vnd.google-apps') !== false) { // Is google file
                    // GuzzleHttp\Psr7\Response
                    $fileData = $googleCate->downloadGoogleDocument($file->id, $file->exportMineType);
                    if ($fileData instanceof \GuzzleHttp\Psr7\Response) {
                        $contentLength = $fileData->getHeaderLine('Content-Length');
                        $contentType = $fileData->getHeaderLine('Content-Type');
                        if ($fileData->getStatusCode() === 200) {
                            wpfdDownloadHeader(
                                $file->title . '.' . $file->ext,
                                (int)$contentLength,
                                $contentType,
                                $config,
                                $file,
                                $preview
                            );
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                            echo $fileData->getBody();
                        }
                    }
                } else {
                    $googleCate->downloadLargeFile($file, $contenType, false, intval($preview));
                }
            }

            wpfdSendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
        } elseif ($cloudActivate && $categoryFrom === 'googleTeamDrive') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
//            do_action('wpfd_before_cloud_team_drive_download_file', $id, $categoryFrom, $category->term_id);

            if (!class_exists('WpfdAddonGoogleTeamDrive')) {
                require_once WPFDA_PLUGIN_DIR_PATH . 'app/admin/classes/WpfdAddonGoogleTeamDrive.php';
            }

            $googleTeamDriveCategory = new WpfdAddonGoogleTeamDrive();
            $googleTeamDriveCategory->incrHits($id);
            $file            = $googleTeamDriveCategory->download($id);
            $fileObj         = apply_filters('wpfdAddonGetGoogleTeamDriveFile', $id, $category->term_id, $token);
            $fileState       = (isset($file->state) && intval($file->state) === 0) ? false : true;
            $fileDownloadUrl = isset($fileObj['linkdownload']) ? $fileObj['linkdownload'] : '#';
            $fileUploadDate  = isset($fileObj['created']) ? $fileObj['created'] : '';

            // Do not download unpublished file.
            if (!$fileState) {
                exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
            }

            if ((int)$preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int)$config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }

            /**
             * Action fire right before a file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             */
            do_action('wpfd_file_download', $id, array('source' => 'google_team_drive'));

            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);
            $fileExt     = strtolower($file->ext);
            $wmCategoryEnabled = checkWatermarkCategoryEnabled($category->term_id);
            if ($wmCategoryEnabled && in_array($fileExt, $wmImageExt)) {
                $application   = Application::getInstance('Wpfd');
                $path_WpfdCategoryWatermark = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
                $path_WpfdCategoryWatermark .= DIRECTORY_SEPARATOR . 'WpfdCategoryWatermark.php';
                require_once $path_WpfdCategoryWatermark;
                $wpfdCategoryWatermark = new WpfdCategoryWatermark();

                $filePath = $watermarkedPath . 'wm_cloud_' . strval(md5($id)) . '.png';

                $ggService = $googleTeamDriveCategory->getGoogleService();
                $content = $ggService->files->get($id, ['alt' => 'media', 'supportsTeamDrives' => true]);

                $fs = fopen($filePath, 'wb');
                $result = fwrite($fs, $content->getBody()->getContents());
                fclose($fs);

                $watermarkedFilePath = $wpfdCategoryWatermark->ajaxWatermarkExec($category->term_id, $id, false, true);
                if (file_exists($watermarkedFilePath)) {
                    $fileSize = filesize($watermarkedFilePath);
                    wpfdDownloadHeader($file->title . '.' . $file->ext, (int)$fileSize, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo file_get_contents($watermarkedFilePath);

                    unlink($watermarkedFilePath);
                }
            } else {
                // Server download for google document
                if (strpos($file->mimeType, 'vnd.google-apps') !== false) { // Is google file
                    // GuzzleHttp\Psr7\Response
                    $fileData = $googleTeamDriveCategory->downloadGoogleDocument($file->id, $file->exportMineType);
                    if ($fileData instanceof \GuzzleHttp\Psr7\Response) {
                        $contentLength = $fileData->getHeaderLine('Content-Length');
                        $contentType = $fileData->getHeaderLine('Content-Type');
                        if ($fileData->getStatusCode() === 200) {
                            wpfdDownloadHeader(
                                $file->title . '.' . $file->ext,
                                (int)$contentLength,
                                $contentType,
                                $config,
                                $file,
                                $preview
                            );
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                            echo $fileData->getBody();
                        }
                    }
                } else {
                    $googleTeamDriveCategory->downloadLargeFile($file, $contenType, false, intval($preview));
                }
            }

            wpfdSendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
        } elseif ($cloudActivate && $categoryFrom === 'dropbox') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);

            if (!class_exists('WpfdAddonDropbox')) {
                require_once WPFDA_PLUGIN_DIR_PATH . 'app/admin/classes/WpfdAddonDropbox.php';
            }

            $dropboxCategory    = new WpfdAddonDropbox;
            list($file, $fMeta) = $dropboxCategory->downloadDropbox($id);
            $dropboxFileObj     = apply_filters('wpfdAddonGetDropboxFile', $id, $category->term_id, $token);
            $fileDownloadUrl    = isset($dropboxFileObj['linkdownload']) ? $dropboxFileObj['linkdownload'] : '#';
            $fileUploadDate     = isset($dropboxFileObj['created']) ? $dropboxFileObj['created'] : '';
            $ext                = strtolower(pathinfo($fMeta['path_display'], PATHINFO_EXTENSION));
            setlocale(LC_ALL, 'en_US.UTF-8');
            $title              = pathinfo($fMeta['path_display'], PATHINFO_FILENAME);

            if ((int)$preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($ext));
            } else {
                if (strtolower($ext) === 'pdf' && (int)$config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }

            //incr hits
            $fileInfos = WpfdAddonHelper::getDropboxFileInfos();

            if (!empty($fileInfos)) {
                $fileState = (isset($fileInfos[$catid][$id])
                    && isset($fileInfos[$catid][$id]['state']) && intval($fileInfos[$catid][$id]['state']) === 0) ? false : true;
                // Do not download unpublished file.
                if (!$fileState) {
                    exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
                }

                if (isset($fileInfos[$catid][$id]) && isset($fileInfos[$catid][$id]['hits'])) {
                    $hits = $fileInfos[$catid][$id]['hits'] + 1;
                    $fileInfos[$catid][$id]['hits'] = $hits;
                } else {
                    $fileInfos[$catid][$id] = array('hits' => 1);
                }
            } else {
                $fileInfos[$catid][$id]['hits'] = 1;
            }
            WpfdAddonHelper::setDropboxFileInfos($fileInfos);

            $fileObj = new stdClass();
            $fileObj->ext = $ext;
            $fileObj->title = $title;
            $fileObj->title = str_replace('&amp;', '&', $fileObj->title);
            $fileObj->title = str_replace('&#039;', '\'', $fileObj->title);
            wpfdSendEmail('', $category->params['category_own'], $configNotify, $category->name, $fileObj->title, $category->term_id, $fileDownloadUrl, $ext, $fileUploadDate);

            /**
             * Action fire right before a Dropbox file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'dropbox'));

            $wmCategoryEnabled = checkWatermarkCategoryEnabled($category->term_id);
            if ($wmCategoryEnabled && in_array(strtolower($ext), $wmImageExt)) {
                $application   = Application::getInstance('Wpfd');
                $path_WpfdCategoryWatermark = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
                $path_WpfdCategoryWatermark .= DIRECTORY_SEPARATOR . 'WpfdCategoryWatermark.php';
                require_once $path_WpfdCategoryWatermark;
                $wpfdCategoryWatermark = new WpfdCategoryWatermark();

                $filePath = $watermarkedPath . 'wm_cloud_' . strval(md5($id)) . '.png';

                $fs = fopen($filePath, 'wb');
                $content = file_get_contents($file);
                $result = fwrite($fs, $content);
                fclose($fs);
                unlink($file);

                $watermarkedFilePath = $wpfdCategoryWatermark->ajaxWatermarkExec($category->term_id, $id, false, true);
                if (file_exists($watermarkedFilePath)) {
                    $fileSize = filesize($watermarkedFilePath);
                    wpfdDownloadHeader($fileObj->title . '.' . $ext, (int)$fileSize, $contenType, $config, $fileObj, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo file_get_contents($watermarkedFilePath);

                    unlink($watermarkedFilePath);
                }
            } else {
                wpfdDownloadHeader(
                    $fileObj->title . '.' . $ext,
                    (int)filesize($file),
                    $contenType,
                    $config,
                    $fileObj,
                    $preview
                );
                readfile($file);
                unlink($file);
            }
        } elseif ($cloudActivate && $categoryFrom === 'onedrive') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);

            if (!class_exists('WpfdAddonOneDrive')) {
                require_once WPFDA_PLUGIN_DIR_PATH . 'app/admin/classes/WpfdAddonOneDrive.php';
            }

            $onedriveCate    = new WpfdAddonOneDrive();
            $file            = $onedriveCate->downloadFile($id);
            $oneDriveFileObj = apply_filters('wpfdAddonGetOneDriveFile', $id, $category->term_id, $token);
            $fileDownloadUrl = isset($oneDriveFileObj['linkdownload']) ? $oneDriveFileObj['linkdownload'] : '#';
            $fileUploadDate  = isset($oneDriveFileObj['created']) ? $oneDriveFileObj['created'] : '';
            $oneDriveConfig  = WpfdAddonHelper::getOneDriveFileInfos();

            // Do not download unpublished file.
            if (!empty($oneDriveConfig) && isset($oneDriveConfig[$category->term_id])
                && isset($oneDriveConfig[$category->term_id][$id])) {
                $fileState = (isset($oneDriveConfig[$category->term_id][$id]['state'])
                    && intval($oneDriveConfig[$category->term_id][$id]['state']) === 0) ? false : true;

                if (!$fileState) {
                    exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
                }
            }

            if ((int)$preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int)$config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }
            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);
            $filedownload = $file->title . '.' . $file->ext;

            /**
             * Action fire right before a Onedrive file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'onedrive', 'catid' => $catid));

            wpfdSendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
            if (defined('WPFD_ONEDRIVE_DIRECT') && WPFD_ONEDRIVE_DIRECT) {
                header('Location: ' . $file->datas);
            } else {
                $wmCategoryEnabled = checkWatermarkCategoryEnabled($category->term_id);
                if ($wmCategoryEnabled && in_array(strtolower($file->ext), $wmImageExt)) {
                    $application   = Application::getInstance('Wpfd');
                    $path_WpfdCategoryWatermark = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
                    $path_WpfdCategoryWatermark .= DIRECTORY_SEPARATOR . 'WpfdCategoryWatermark.php';
                    require_once $path_WpfdCategoryWatermark;
                    $wpfdCategoryWatermark = new WpfdCategoryWatermark();

                    $filePath = $watermarkedPath . 'wm_cloud_' . strval(md5($id)) . '.png';

                    $fs = fopen($filePath, 'wb');
                    $result = fwrite($fs, $file->datas);
                    fclose($fs);

                    $watermarkedFilePath = $wpfdCategoryWatermark->ajaxWatermarkExec($category->term_id, $id, false, true);
                    if (file_exists($watermarkedFilePath)) {
                        $fileSize = filesize($watermarkedFilePath);
                        wpfdDownloadHeader($filedownload, (int)$fileSize, $contenType, $config, $file, $preview);
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                        echo file_get_contents($watermarkedFilePath);

                        unlink($watermarkedFilePath);
                    }
                } else {
                    wpfdDownloadHeader($filedownload, (int)$file->size, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo $file->datas;
                }
            }
        } elseif ($cloudActivate && $categoryFrom === 'onedrive_business') {
            /**
             * Action fire before get file information from cloud.
             *
             * @param object File id
             * @param string Cloud type
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_cloud_download_file', $id, $categoryFrom, $category->term_id);

            if (!class_exists('WpfdAddonOneDriveBusiness')) {
                require_once WPFDA_PLUGIN_DIR_PATH . 'app/admin/classes/WpfdAddonOneDriveBusiness.php';
            }

            $onedriveBusinessCategory = new WpfdAddonOneDriveBusiness();
            $file                     = $onedriveBusinessCategory->downloadFile($id);
            $oneDriveBusinessFileObj  = apply_filters('wpfdAddonGetOneDriveBusinessFile', $id, $category->term_id, $token);
            $fileDownloadUrl          = isset($oneDriveBusinessFileObj['linkdownload']) ? $oneDriveBusinessFileObj['linkdownload'] : '#';
            $fileUploadDate           = isset($oneDriveBusinessFileObj['created']) ? $oneDriveBusinessFileObj['created'] : '';
            $oneDriveBusinessConfig   = WpfdAddonHelper::getOneDriveBusinessFileInfos();

            // Do not download unpublished file.
            if (!empty($oneDriveBusinessConfig) && isset($oneDriveBusinessConfig[$category->term_id])
                && isset($oneDriveBusinessConfig[$category->term_id][$id])) {
                $fileState = (isset($oneDriveBusinessConfig[$category->term_id][$id]['state'])
                    && intval($oneDriveBusinessConfig[$category->term_id][$id]['state']) === 0) ? false : true;

                if (!$fileState) {
                    exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
                }
            }

            if ((int)$preview === 1) {
                $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
            } else {
                if (strtolower($file->ext) === 'pdf' && (int)$config['open_pdf_in'] === 1) {
                    $contenType = WpfdHelperFile::mimeType(strtolower($file->ext));
                } else {
                    $contenType = 'application/octet-stream';
                }
            }
            $file->title = str_replace('&amp;', '&', $file->title);
            $file->title = str_replace('&#039;', '\'', $file->title);
            $filedownload = $file->title . '.' . $file->ext;

            /**
             * Action fire right before a Onedrive file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   Source
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_file_download', $id, array('source' => 'onedrive_business', 'catid' => $catid));

            wpfdSendEmail('', $category->params['category_own'], $configNotify, $category->name, $file->title, $category->term_id, $fileDownloadUrl, $file->ext, $fileUploadDate);
            if (defined('WPFD_ONEDRIVE_BUSINESS_DIRECT') && WPFD_ONEDRIVE_BUSINESS_DIRECT) {
                header('Location: ' . $file->datas);
            } else {
                $wmCategoryEnabled = checkWatermarkCategoryEnabled($category->term_id);
                if ($wmCategoryEnabled && in_array(strtolower($file->ext), $wmImageExt)) {
                    $application   = Application::getInstance('Wpfd');
                    $path_WpfdCategoryWatermark = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
                    $path_WpfdCategoryWatermark .= DIRECTORY_SEPARATOR . 'WpfdCategoryWatermark.php';
                    require_once $path_WpfdCategoryWatermark;
                    $wpfdCategoryWatermark = new WpfdCategoryWatermark();

                    $filePath = $watermarkedPath . 'wm_cloud_' . strval(md5($id)) . '.png';

                    $fs = fopen($filePath, 'wb');
                    $result = fwrite($fs, $file->datas);
                    fclose($fs);

                    $watermarkedFilePath = $wpfdCategoryWatermark->ajaxWatermarkExec($category->term_id, $id, false, true);
                    if (file_exists($watermarkedFilePath)) {
                        $fileSize = filesize($watermarkedFilePath);
                        wpfdDownloadHeader($filedownload, (int)$fileSize, $contenType, $config, $file, $preview);
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                        echo file_get_contents($watermarkedFilePath);

                        unlink($watermarkedFilePath);
                    }
                } else {
                    wpfdDownloadHeader($filedownload, (int)$file->size, $contenType, $config, $file, $preview);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                    echo $file->datas;
                }
            }
        } elseif ($categoryFrom === 'aws') {
            $wmCategoryEnabled = checkWatermarkCategoryEnabled($category->term_id);
            $watermarkedPath = $watermarkedPath . strval($category->term_id) . '_' . strval(md5($id)) . '.png';
            if ($wmCategoryEnabled && file_exists($watermarkedPath)) {
                $fileSize = filesize($watermarkedPath);
                $infoWmFile = pathinfo($watermarkedPath);
                $filedownload = basename($id);
                $awsFilesInfo = get_option('_wpfdAddon_aws_fileInfo');
                if (!empty($awsFilesInfo) && isset($awsFilesInfo[$catid]) && isset($awsFilesInfo[$catid][$id])) {
                    if (isset($awsFilesInfo[$catid][$id]['title'])) {
                        $fileTitle = $awsFilesInfo[$catid][$id]['title'];
                        $info = pathinfo($id);
                        $filedownload = $fileTitle .'.'. $info['extension'];
                    }
                }
                $file = new stdClass();
                $file->size = $fileSize;
                $file->ext = $infoWmFile['extension'];

                $contenType = 'application/octet-stream';
                wpfdDownloadHeader($filedownload, (int)$fileSize, $contenType, $config, $file, $preview);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                echo file_get_contents($watermarkedPath);
            } else {
                $awsCate = new WpfdAddonAws;
                $downloadFile = $awsCate->downloadAws($id, $catid);
                if ($downloadFile) {
                    $awsFileObj      = $awsCate->getAwsFileInfos($id, getAwsPathByTermId($catid), $catid, $token);
                    $fileDownloadUrl = isset($awsFileObj['linkdownload']) ? $awsFileObj['linkdownload'] : '#';
                    $fileUploadDate  = isset($awsFileObj['created']) ? $awsFileObj['created'] : '';
                    $fileTitle       = isset($awsFileObj['title']) ? $awsFileObj['title'] : '';
                    $fileType        = isset($awsFileObj['ext']) ? $awsFileObj['ext'] : '';

                    // Send email notifications
                    wpfdSendEmail('', $category->params['category_own'], $configNotify, $category->name, $fileTitle, $category->term_id, $fileDownloadUrl, $fileType, $fileUploadDate);
                }
            }
        } elseif ($categoryFrom === 'nextcloud') {
            $wmCategoryEnabled = checkWatermarkCategoryEnabled($category->term_id);
            $watermarkedPath = $watermarkedPath . strval($category->term_id) . '_' . strval(md5($id)) . '.png';
            if ($wmCategoryEnabled && file_exists($watermarkedPath)) {
                $fileSize = filesize($watermarkedPath);
                $infoWmFile = pathinfo($watermarkedPath);
                $filedownload = basename($id);
                $nextcloudFilesInfo = get_option('_wpfdAddon_nextcloud_fileInfo');
                $hashID = md5($id);
                if (!empty($nextcloudFilesInfo) && isset($nextcloudFilesInfo[$catid]) && isset($nextcloudFilesInfo[$catid][$hashID])) {
                    if (isset($nextcloudFilesInfo[$catid][$hashID]['title'])) {
                        $fileTitle = $nextcloudFilesInfo[$catid][$hashID]['title'];
                        $info = pathinfo($id);
                        $filedownload = $fileTitle .'.'. $info['extension'];
                    }
                }
                $file = new stdClass();
                $file->size = $fileSize;
                $file->ext = $infoWmFile['extension'];

                $contenType = 'application/octet-stream';
                wpfdDownloadHeader($filedownload, (int)$fileSize, $contenType, $config, $file, $preview);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file content output
                echo file_get_contents($watermarkedPath);
            } else {
                if (!class_exists('WpfdAddonNextcloud')) {
                    require_once WPFDA_PLUGIN_DIR_PATH . 'app/admin/classes/WpfdAddonNextcloud.php';
                }
                $nextcloudCate = new WpfdAddonNextcloud;
                $downloadFile = $nextcloudCate->downloadNextcloud($id, $catid);
                if ($downloadFile) {
                    $nextcloudFileObj      = $nextcloudCate->getNextcloudFileInfos($id, WpfdAddonHelper::getNextcloudPathByTermId($catid), $catid, $token);
                    $fileDownloadUrl = isset($nextcloudFileObj['linkdownload']) ? $nextcloudFileObj['linkdownload'] : '#';
                    $fileUploadDate  = isset($nextcloudFileObj['created']) ? $nextcloudFileObj['created'] : '';
                    $fileTitle       = isset($nextcloudFileObj['title']) ? $nextcloudFileObj['title'] : '';
                    $fileType        = isset($nextcloudFileObj['ext']) ? $nextcloudFileObj['ext'] : '';

                    // Send email notifications
                    wpfdSendEmail('', $category->params['category_own'], $configNotify, $category->name, $fileTitle, $category->term_id, $fileDownloadUrl, $fileType, $fileUploadDate);
                }
            }
        } else {
            $file            = $model->getFullFile($id);
            $fileObj         = $model->getFile($id);
            $fileState       = (isset($file->state) && intval($file->state) === 0) ? false : true;
            $fileDownloadUrl = isset($fileObj->linkdownload) ? $fileObj->linkdownload : '#';
            $fileUploadDate  = isset($fileObj->created) ? $fileObj->created : '';

            // Do not download unpublished file.
            if (!$fileState) {
                exit(esc_html__('You can\'t download unpublished file(s).', 'wpfd'));
            }

            $file_meta = get_post_meta($id, '_wpfd_file_metadata', true);

            /**
             * Action fire before statistic count and a file download.
             * Do not echo anything here or file download will corrupt
             *
             * @param object  File id
             * @param array   File meta data
             *
             * @internal
             * @ignore
             */
            do_action('wpfd_before_download_file', $file, $file_meta);
            $remote_url = isset($file_meta['remote_url']) ? $file_meta['remote_url'] : false;
            $model->hit($id);
            //$model->addCountChart($id);

            // New statistics insert
            $statisticsType = ((int)$preview === 1) ? 'preview' : 'default';
            WpfdHelperFile::addStatisticsRow($id, $statisticsType);


            //todo : verifier les droits d'acces à la catéorgie du fichier
            if (!empty($file) && $file->ID) {
                $file->title = str_replace('&amp;', '&', $file->title);
                $file->title = str_replace('&#039;', '\'', $file->title);
                $filename = WpfdHelperFile::santizeFileName($file->title);
                if ($filename === '') {
                    $filename = 'download';
                }
                if ($remote_url) {
                    if (!isset($file_meta['wpfd_sync_ftp_file'])) {
                        $url = $file_meta['file'];
                        header('Location: ' . $url);
                    }
                } else {
                    $preview = Utilities::getInput('preview', 'GET', 'none');
                }

                $lists = get_option('wpfd_watermark_category_listing');
                $wmCategoryEnabled = false;
                if (is_array($lists) && !empty($lists)) {
                    if (in_array($category->term_id, $lists)) {
                        $wmCategoryEnabled = true;
                    }
                }
                $filePath = WpfdBase::getFilesPath($file->catid) . $file->file;
                $watermarkedPath = $watermarkedPath . strval($category->term_id) . '_' . strval($id) . '_' . strval(md5($filePath)) . '.png';
                if ($wmCategoryEnabled && file_exists($watermarkedPath)) {
                    $sysfile = $watermarkedPath;
                } else {
                    $sysfile = WpfdBase::getFilesPath($file->catid) . $file->file;
                    if (isset($file_meta['wpfd_sync_ftp_file'])) {
                        $sysfile = $file_meta['file'];
                    }
                }

                if (file_exists($sysfile)) {
                    $filedownload = $filename . '.' . $file->ext;
                    /**
                     * Action fire right before a file download.
                     * Do not echo anything here or file download will corrupt
                     *
                     * @param object  File id
                     * @param array   Source
                     *
                     * @ignore Hook already documented
                     */
                    do_action('wpfd_file_download', $id, array('source' => 'local'));
                    $result = WpfdHelperFile::sendDownload(
                        $sysfile,
                        $filedownload,
                        $file->ext,
                        ((int)$preview === 1) ? true : false,
                        ((int)$config['open_pdf_in'] === 1) ? true : false
                    );
                    if ($result) {
                        wpfdSendEmail(
                            $file->author,
                            $category->params['category_own'],
                            $configNotify,
                            $category->name,
                            $file->title,
                            $category->term_id,
                            $fileDownloadUrl,
                            $file->ext,
                            $fileUploadDate
                        );
                    }
                } else {
                    exit(esc_html__('File not found', 'wpfd'));
                }
            }
        }
        exit();
    }
}

/**
 * Download header file
 *
 * @param string  $filename   File name
 * @param integer $size       Size
 * @param string  $contenType Content type
 * @param array   $config     Config
 * @param object  $ob         File object
 * @param integer $preview    Preview
 *
 * @return void
 */
function wpfdDownloadHeader($filename, $size, $contenType, $config, $ob, $preview)
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    if ((int)$config['open_pdf_in'] === 1 && strtolower($ob->ext) === 'pdf' && (int)$preview === 1) {
        header('Content-Disposition: inline; filename="' . $filename . '"; filename*=UTF-8\'\'' . $filename);
    } else {
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    }

    $contentTypeSetted = false;
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (strlen(strstr($agent, 'Firefox')) > 0 && $contenType === 'application/pdf' && !$preview) {
            header('Content-Type: application/force-download; charset=utf-8');
            $contentTypeSetted = true;
        }
    }
    if (!$contentTypeSetted) {
        header('Content-Type: ' . esc_attr($contenType));
    }
    header('Content-Description: File Transfer');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    if ($size !== 0) {
        header('Content-Length: ' . $size);
    }
    ob_clean();
    flush();
}

/**
 * Method send email notification
 *
 * @param integer      $user_id         User id
 * @param integer      $cat_userid      Category owner user id
 * @param array        $configNotifi    Config
 * @param string       $cat_name        Category name
 * @param string       $file_title      File title
 * @param string|mixed $term_id         Term id
 * @param string|mixed $fileDownloadUrl File download url
 * @param string|mixed $fileExt         File type
 * @param string|mixed $fileUploadDate  File upload date
 *
 * @return void
 */
function wpfdSendEmail($user_id, $cat_userid, $configNotifi, $cat_name, $file_title, $term_id = 0, $fileDownloadUrl = '#', $fileExt = '', $fileUploadDate = '')
{
    $send_mail_active = array();
    $cat_user_id[] = $cat_userid;
    $list_superAdmin = WpfdHelperFiles::getListIDSuperAdmin();
    $emailPerCategoryListing = get_option('wpfd_email_per_category_listing', array());
    if (is_null($emailPerCategoryListing) || !$emailPerCategoryListing) {
        $emailPerCategoryListing = array();
    }

    if ((int)$configNotifi['notify_file_owner'] === 1 && $user_id !== null) {
        $user = get_userdata($user_id)->data;
        array_push($send_mail_active, $user->user_email);
        WpfdHelperFiles::sendMail('download', $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
    }
    if ((int)$configNotifi['notify_category_owner'] === 1) {
        foreach ($cat_user_id as $item) {
            $user = get_userdata($item)->data;
            if (!in_array($user->user_email, $send_mail_active)) {
                array_push($send_mail_active, $user->user_email);
                WpfdHelperFiles::sendMail('download', $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
            }
        }
    }
    if ($configNotifi['notify_download_event_email'] !== '') {
        if (strpos($configNotifi['notify_download_event_email'], ',')) {
            $emails = explode(',', $configNotifi['notify_download_event_email']);
        } else {
            $emails = array($configNotifi['notify_download_event_email']);
        }

        foreach ($emails as $item) {
            $obj_user = new stdClass;
            $obj_user->display_name = '';
            $obj_user->user_email = $item;
            if (!in_array($item, $send_mail_active)) {
                array_push($send_mail_active, $item);
                WpfdHelperFiles::sendMail('download', $obj_user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
            }
        }
    }
    if ((int)$configNotifi['notify_super_admin'] === 1) {
        foreach ($list_superAdmin as $items) {
            $user = get_userdata($items)->data;
            if (!in_array($user->user_email, $send_mail_active)) {
                array_push($send_mail_active, $user->user_email);
                WpfdHelperFiles::sendMail('download', $user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
            }
        }
    }
    if (isset($configNotifi['notify_per_category']) && intval($configNotifi['notify_per_category']) === 1) {
        if (!empty($emailPerCategoryListing) && is_array($emailPerCategoryListing) && array_key_exists($term_id, $emailPerCategoryListing)) {
            $emailListing = isset($emailPerCategoryListing[$term_id]['emails']) ? (array) $emailPerCategoryListing[$term_id]['emails'] : array();
        } else {
            $emailListing = array();
        }

        if (!empty($emailListing)) {
            foreach ($emailListing as $email) {
                $email                  = trim($email);
                $obj_user               = new stdClass;
                $obj_user->display_name = '';
                $obj_user->user_email   = $email;
                if (!in_array($email, $send_mail_active)) {
                    array_push($send_mail_active, $email);
                    WpfdHelperFiles::sendMail('download', $obj_user, $cat_name, get_site_url(), $file_title, $fileDownloadUrl, $fileExt, $fileUploadDate);
                }
            }
        }
    }
}

/**
 * Get AWS path by termID
 *
 * @param integer $termId Term id
 *
 * @return boolean|string
 */
function getAwsPathByTermId($termId)
{
    $result = get_term_meta($termId, 'wpfd_drive_path', true);
    $type = get_term_meta($termId, 'wpfd_drive_type', true);

    if ($result && $type === 'aws') {
        return $result;
    }

    return false;
}

/**
 * Check Watermark Category Enabled
 *
 * @param integer $termId Term id
 *
 * @return boolean
 */
function checkWatermarkCategoryEnabled($termId)
{
    $lists = get_option('wpfd_watermark_category_listing');
    $wmCategoryEnabled = false;
    if (is_array($lists) && !empty($lists)) {
        if (in_array($termId, $lists)) {
            $wmCategoryEnabled = true;
        }
    }

    return $wmCategoryEnabled;
}

/**
 * Add meta tags for wpfd_file post type
 *
 * @return void|mixed
 */
function addRobotsMetaWpfdFile()
{
    if (is_singular('wpfd_file')) {
        $globalConfig = get_option('_wpfd_global_config');
        if (isset($globalConfig['robots_meta_noindex']) && isset($globalConfig['robots_meta_nofollow'])) {
            $meta = '';
            if (intval($globalConfig['robots_meta_noindex']) === 1 || intval($globalConfig['robots_meta_nofollow']) === 1) {
                // Remove any existing meta tags that the SEO plugin might add
                remove_action('wp_head', 'wpseo_head', 1); // This is for Yoast SEO
                remove_action('wp_head', 'aiosp_head'); //All in One SEO Pack
                remove_action('wp_head', 'rank_math/frontend/robots'); //Rank Math SEO
                remove_action('wpmsseo_head', 'wpmsopengraph', 30); // WP Meta SEO
            }
            if (intval($globalConfig['robots_meta_noindex']) === 1) {
                $meta = '<meta name="robots" content="noindex" />' . "\n";
            }
            if (intval($globalConfig['robots_meta_nofollow']) === 1) {
                $meta = '<meta name="robots" content="nofollow" />' . "\n";
            }
            if (intval($globalConfig['robots_meta_noindex']) === 1 && intval($globalConfig['robots_meta_nofollow']) === 1) {
                $meta = '<meta name="robots" content="noindex, nofollow" />' . "\n";
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allow html
            echo $meta;
        }
    }
}
add_action('wp_head', 'addRobotsMetaWpfdFile', 1);
