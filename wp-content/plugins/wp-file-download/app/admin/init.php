<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();


$app = Application::getInstance('Wpfd');
$path_wpfdbase = $app->getPath() . DIRECTORY_SEPARATOR . $app->getType() . DIRECTORY_SEPARATOR . 'classes';
$path_wpfdbase .= DIRECTORY_SEPARATOR . 'WpfdBase.php';
require_once $path_wpfdbase;

if (!get_option('_wpfd_import_notice_flag', false)) {
    $path_wpfdtool = $app->getPath() . DIRECTORY_SEPARATOR . $app->getType() . DIRECTORY_SEPARATOR . 'classes';
    $path_wpfdtool .= DIRECTORY_SEPARATOR . 'WpfdTool.php';
    require_once $path_wpfdtool;
    $wpfdTool = new WpfdTool;
    add_action('wpfd_admin_notices', array($wpfdTool, 'wpfdImportNotice'), 3);
}

add_action('admin_menu', 'wpfd_menu');
add_action('admin_head', 'wpfd_menu_highlight');
add_action('wp_ajax_wpfd_import', array('WpfdTool', 'wpfdImportCategories'));
add_action('wp_ajax_wpfd', 'wpfd_ajax');
add_action('media_buttons', 'wpfd_button');
add_action('delete_term', 'wpfd_delete_term', 10, 4);
add_action('edit_term', 'wpfd_edit_term', 10, 4);

add_action('init', 'wpfd_register_post_type');
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
        'show_in_nav_menus' => false,
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
        'show_ui' => true,
        'show_admin_column' => false,
        'query_var' => false,
    );

    register_taxonomy('wpfd-tag', 'wpfd_file', $args);

    register_post_type(
        'wpfd_file',
        array(
            'labels' => array(
                'name' => esc_html__('Files', 'wpfd'),
                'singular_name' => esc_html__('File', 'wpfd')
            ),
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_in_nav_menus' => false,
            'show_ui' => false,
            'taxonomies' => array('wpfd-category', 'wpfd-tag'),
            'has_archive' => false,
            'show_in_menu' => false,
            'capability_type' => 'wpfd_file',
            'map_meta_cap' => false,
            'capabilities' => array(
                'wpfd_create_category' => esc_html__('Create categories', 'wpfd'),
                'wpfd_edit_category' => esc_html__('Edit categories', 'wpfd'),
                'wpfd_edit_own_category' => esc_html__('Edit own categories', 'wpfd'),
                'wpfd_delete_category' => esc_html__('Delete categories', 'wpfd'),
                'wpfd_manage_file' => esc_html__('Access WP File Download', 'wpfd'),
                'wpfd_edit_permission' => esc_html__('Edit permissions settings', 'wpfd'),
                'wpfd_download_files' => esc_html__('Download files', 'wpfd'),
                'wpfd_preview_files' => esc_html__('Preview files', 'wpfd'),
                'wpfd_upload_only' => esc_html__('Upload files on frontend', 'wpfd'),
            ),
        )
    );
}


add_action('wp_update_nav_menu_item', 'wpfd_update_custom_nav_fields', 10, 3);
/**
 * Update custom menu item
 *
 * @param integer $menu_id         Menu id
 * @param integer $menu_item_db_id Menu Item id
 * @param array   $args            Attributes
 *
 * @return void
 */
function wpfd_update_custom_nav_fields($menu_id, $menu_item_db_id, array $args)
{
    // Check if element is properly sent
    if ((int) $args['menu-item-db-id'] === 0 && $args['menu-item-object'] === 'wpfd-category') {
        $my_post = array(
            'ID' => $menu_item_db_id,
            'post_content' => '',
        );
        // Update the post into the database
        wp_update_post($my_post);
    }
}

/**
 * Add menus for WP File Download
 *
 * @return void
 */
function wpfd_menu()
{
    Application::getInstance('Wpfd');
    add_menu_page(
        esc_html__('WP File Download', 'wpfd'),
        esc_html__('WP File Download', 'wpfd'),
        'wpfd_manage_file',
        'wpfd',
        'wpfd_call',
        'dashicons-category'
    );
    add_submenu_page(
        'wpfd',
        esc_html__('Statistics', 'wpfd'),
        esc_html__('Statistics', 'wpfd'),
        'manage_options',
        'wpfd-statistics',
        'wpfd_call_statistics'
    );
    add_submenu_page(
        'wpfd',
        esc_html__('Tag', 'wpfd'),
        esc_html__('Tags', 'wpfd'),
        'manage_options',
        'edit-tags.php?taxonomy=wpfd-tag',
        ''
    );
    add_submenu_page(
        'wpfd',
        esc_html__('Icons Builder', 'wpfd'),
        esc_html__('Icons Builder', 'wpfd'),
        'manage_options',
        'wpfd-icons-builder',
        'wpfd_call_icon_builder'
    );
    add_submenu_page(
        'wpfd',
        esc_html__('WP File Download config', 'wpfd'),
        esc_html__('Configuration', 'wpfd'),
        'manage_options',
        'wpfd-config',
        'wpfd_call_config'
    );
}

/**
 * Call ajax
 *
 * @return void
 */
function wpfd_ajax()
{
    define('WPFD_AJAX', true);
    wpfd_call();
}

/**
 * Call task for controller
 *
 * @param null   $ref          Ref
 * @param string $default_task Default task
 *
 * @return void
 */
function wpfd_call($ref = null, $default_task = 'Wpfd.display')
{
    if (!defined('WPFD_AJAX')) {
        wpfd_init();
    }

    $application = Application::getInstance('Wpfd');
    $application->execute($default_task);
}

/**
 * Display config page
 *
 * @return void
 */
function wpfd_call_config()
{
    wpfd_call(null, 'config.display');
}

/**
 * Display statistics page
 *
 * @return void
 */
function wpfd_call_statistics()
{
    wpfd_call(null, 'statistics.display');
}
/**
 * Display icons builder page
 *
 * @return void
 */
function wpfd_call_icon_builder()
{
    wpfd_call(null, 'iconsbuilder.display');
}
/**
 * Init for plugin
 *
 * @return void
 */
function wpfd_init()
{
    $path_languages = dirname(plugin_basename(WPFD_PLUGIN_FILE)) . DIRECTORY_SEPARATOR . 'app';
    $path_languages .= DIRECTORY_SEPARATOR . 'languages';
    load_plugin_textdomain('wpfd', null, $path_languages);
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-migrate');

    $page = Utilities::getInput('page', 'GET', 'string');
    if ($page === 'wpfd-statistics' || $page === 'wpfd-icons-builder') {
        return;
    }

    $jqueryUiDev = array(
        'jquery-ui-core',
        'jquery-ui-resizable',
        'jquery-ui-sortable',
        'jquery-ui-draggable',
        'jquery-ui-droppable',
        'jquery-ui-autocomplete',
        'jquery-ui-tooltip',
    );
    foreach ($jqueryUiDev as $handleName) {
        wp_enqueue_script($handleName);
    }
    wp_enqueue_style('dashicons');

    if (defined('WPFD_VERSION')) {
        $ver = WPFD_VERSION;
    } else {
        $ver = null;
    }



    if ($page !== 'wpfd-config' && $page !== 'wpfd-notification' && $page !== 'wpfd-icons-builder') {
        wp_enqueue_script('wpfd-bootstrap', plugins_url('assets/js/bootstrap.min.js', __FILE__), array(), $ver);
    }
//    wp_enqueue_style('wpfd-bootstrap', plugins_url('assets/css/bootstrap.min.css', __FILE__), array(), $ver);
    wp_enqueue_script('wpfd-bootstrap', plugins_url('assets/js/jquery.ui.touch-punch.min.js', __FILE__), array(), $ver);
    wp_enqueue_script('jquery-customscroll', plugins_url('assets/ui/js/jquery.mCustomScrollbar.min.js', __FILE__), array(), $ver);

    wp_enqueue_style('buttons');
    wp_enqueue_style('wp-admin');
    wp_enqueue_style('colors-fresh');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
    wp_enqueue_style('wpfd-upload', plugins_url('assets/css/upload.min.css', __FILE__), array(), $ver);
    wp_enqueue_style('wpfd-style', plugins_url('assets/css/style.css', __FILE__), array(), $ver);
    wp_enqueue_style('wpfd-core', plugins_url('assets/ui/css/core.css', __FILE__), array('wpfd-style'), $ver);
    wp_enqueue_style('jquery-customscroll', plugins_url('assets/ui/css/jquery.mCustomScrollbar.min.css', __FILE__), array(), $ver);
    wp_enqueue_style('wpfd-chosen', plugins_url('assets/css/chosen.css', __FILE__), array(), $ver);
    wp_enqueue_script('l10n');

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_script('jquery-filedrop', plugins_url('assets/js/jquery.filedrop.min.js', __FILE__), array(), $ver);
    wp_enqueue_script('resumable', plugins_url('assets/js/resumable.js', __FILE__), array(), $ver);
    wp_enqueue_script('jquery-textselect', plugins_url('assets/js/jquery.textselect.min.js', __FILE__), array(), $ver);
    wp_enqueue_script('jquery-nestable', plugins_url('assets/js/jquery.nestable.js', __FILE__), array(), $ver);
    wp_enqueue_style('jquery.restable', plugins_url('assets/css/jquery.restable.css', __FILE__), array(), $ver);
    wp_enqueue_script('jquery.restable', plugins_url('assets/js/jquery.restable.js', __FILE__), array(), $ver);
    wp_enqueue_style('jquery-jaofiletree', plugins_url('assets/css/jaofiletree.css', __FILE__), array(), $ver);
    wp_enqueue_script('jquery-jaofiletree', plugins_url('assets/js/jaofiletree.js', __FILE__), array(), $ver);
    wp_enqueue_script('jquery-minicolors', plugins_url('assets/js/jquery.minicolors.min.js', __FILE__), array(), $ver);
    wp_enqueue_style(
        'jquery-ui-1.9.2',
        plugins_url('assets/css/ui-lightness/jquery-ui-1.9.2.custom.min.css', __FILE__),
        array(),
        $ver
    );
    wp_enqueue_style('jquery-tagit', plugins_url('assets/css/jquery.tagit.css', __FILE__), array(), $ver);
    wp_enqueue_script('jquery-tagit', plugins_url('assets/js/jquery.tagit.js', __FILE__), array('jquery', 'jquery-ui-core'), $ver);
    wp_enqueue_script('jquery-bootbox', plugins_url('assets/js/bootbox.js', __FILE__), array(), $ver);
    wp_enqueue_style('wpfd-gritter', plugins_url('assets/css/jquery.gritter.css', __FILE__), array(), $ver);
    wp_enqueue_script('wpfd-gritter', plugins_url('assets/js/jquery.gritter.min.js', __FILE__), array(), $ver);
    wp_enqueue_script('momentjs', plugins_url('assets/ui/js/moment.min.js', __FILE__), array('jquery'), $ver);
    wp_enqueue_style(
        'wpfd-datetimepicker',
        plugins_url('assets/css/jquery.datetimepicker.min.css', __FILE__),
        array(),
        $ver
    );
    wp_enqueue_script(
        'wpfd-datetimepicker',
        plugins_url('assets/js/jquery.datetimepicker.full.min.js', __FILE__),
        array('jquery'),
        $ver
    );
    wp_enqueue_style(
        'wpfd-daterangepicker-style',
        plugins_url('app/admin/assets/ui/css/daterangepicker.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_enqueue_script(
        'wpfd-daterangepicker',
        plugins_url('app/admin/assets/ui/js/daterangepicker.min.js', WPFD_PLUGIN_FILE),
        array('momentjs'),
        WPFD_VERSION
    );
    wp_enqueue_script(
        'jquery-scrollbar',
        plugins_url('assets/js/jquery.scrollbar.min.js', __FILE__),
        array('jquery'),
        $ver
    );
    wp_enqueue_style(
        'wpfd-scrollbar',
        plugins_url('assets/css/jquery.scrollbar.css', __FILE__),
        array(),
        $ver
    );
    wp_enqueue_script('wpfd-cookie', plugins_url('assets/js/jquery.cookie.js', __FILE__), array(), $ver);
    wp_enqueue_script('wpfd-base64js', plugins_url('assets/js/encodingHelper.js', __FILE__), array(), $ver);
    wp_enqueue_script('wpfd-TextEncoderLite', plugins_url('assets/js/TextEncoderLite.js', __FILE__), array(), $ver);
    wp_enqueue_script('wpfd-status', plugins_url('assets/ui/js/wpfd_status.js', __FILE__), array('jquery', 'wpfd-main'), $ver);
    wp_register_script('wpfd-main-ui-script-inlinesvg', plugins_url('assets/ui/js/inlinesvg.min.js', __FILE__), array_merge(array('jquery'), $jqueryUiDev), $ver);
    wp_enqueue_script('wpfd-main-ui-script-inlinesvg', plugins_url('assets/ui/js/inlinesvg.min.js', __FILE__), array_merge(array('jquery'), $jqueryUiDev), $ver);
    wp_enqueue_script('wpfd-main', plugins_url('assets/js/wpfd.js', __FILE__), array_merge(array('jquery'), $jqueryUiDev), $ver);
    wp_enqueue_script('wpfd-core', plugins_url('assets/ui/js/core.js', __FILE__), array('jquery', 'wpfd-main'), $ver);
    wp_enqueue_script('wpfd-contextmenu', plugins_url('assets/ui/js/contextmenu.js', __FILE__), array('jquery', 'wpfd-main', 'wpfd-core'), $ver);
    wp_localize_script('wpfd-main', 'wpfd_permissions', array(
        'can_create_category' => wpfd_can_create_category(),
        'can_edit_category' => (wpfd_can_edit_category() || wpfd_can_edit_own_category()) ? true : false,
        'can_delete_category' => (wpfd_can_delete_category() || wpfd_can_edit_own_category()) ? true : false,
        'translate' => array(
            'wpfd_create_category' => esc_html__("You don't have permission to create new category", 'wpfd'),
            'wpfd_edit_category' => esc_html__("You don't have permission to edit category", 'wpfd')
        ),
    ));
    Application::getInstance('Wpfd');
    $configModel = Model::getInstance('config');
    $config = $configModel->getConfig();
    $dateFormat = get_option('date_format', 'Y-m-d') . ' ' . get_option('time_format', 'H:i:s');
    /**
     * Filter to select large size file custom icon
     *
     * @param boolean
     */
    $largeSizeCustomIcon = apply_filters('wpfd_file_large_size_custom_icon', false);

    $modelCategories = Model::getInstance('categoriesfront');
    $categories = $modelCategories->getLevelCategories(0);
    $categories_order = array();
    $categories_aws = array();
    $categories_nextcloud = array();
    if (empty($categories)) {
        $categories = array();
    } else {
        foreach ($categories as $key => $value) {
            if (!$value->cloudType) {
                $categories_order[$value->term_id] = $value;
            } elseif ($value->cloudType === 'aws') {
                $categories_aws[$value->wp_category_id] = $value;
            } elseif ($value->cloudType === 'nextcloud') {
                $categories_nextcloud[$value->wp_category_id] = $value;
            }
        }
    }

    $wpfd_watermark_category_listing = get_option('wpfd_watermark_category_listing');
    if (!empty($wpfd_watermark_category_listing) && is_array($wpfd_watermark_category_listing)) {
        foreach ($wpfd_watermark_category_listing as $k => $v) {
            $term = get_term($v);
            if (!$term || is_wp_error($term)) {
                unset($wpfd_watermark_category_listing[$v]);
            }
        }
        update_option('wpfd_watermark_category_listing', $wpfd_watermark_category_listing);
    } else {
        $wpfd_watermark_category_listing = array();
    }

    $email_categories_order = array();
    if (empty($categories)) {
        $categories = array();
    } else {
        foreach ($categories as $key => $value) {
            if (!$value->cloudType) {
                $email_categories_order[$value->term_id] = $value;
            } else {
                $value->term_id = isset($value->wp_term_id) ? $value->wp_term_id : $value->term_id;
                $value->parent = isset($value->wp_parent) ? $value->wp_parent : $value->parent;
                if ($value->parent === false) {
                    $value->parent = 0;
                }

                $email_categories_order[$value->term_id] = $value;
            }

            $value->watermark = false;
            if (in_array($value->term_id, $wpfd_watermark_category_listing)) {
                $value->watermark = true;
            }
            $email_categories_order[$value->term_id] = $value;
        }
    }
    $wm_categories_order = $email_categories_order;
    $include_slash_character_file_title = apply_filters('wpfd_include_slash_character_file_title', false);

    /**
     * Filter to check special chars on editing
     *
     * @param boolean
     */
    $checkSpecialCharEditing = apply_filters('wpfd_check_special_char_editing', true);
    wp_localize_script('wpfd-main', 'wpfd_var', array(
        'abspath' => wpfd_get_abspath(),
        'wpfd_root_site' => rtrim(str_replace(DIRECTORY_SEPARATOR, '/', wpfd_get_abspath()), '/'),
        'adminurl' => admin_url('admin.php'),
        'contenturl' => content_url(),
        'wpfdajaxurl' => admin_url('admin-ajax.php'),
        'wpfdsecurity' => wp_create_nonce('wpfd-security'),
        'dateFormat' => wpfdPhpToMomentDateFormat($dateFormat),
        'new_category_position' => $config['new_category_position'],
        'large_size_custom_icon' => $largeSizeCustomIcon,
        'wpfd_categories' => $categories,
        'wpfd_aws_categories' => $categories_aws,
        'wpfd_nextcloud_categories' => $categories_nextcloud,
        'wpfd_categories_order' => $categories_order,
        'wpfd_email_categories_order' => $email_categories_order,
        'wpfd_wm_categories_order' => $wm_categories_order,
        'wpfd_include_slash_character_file_title' => $include_slash_character_file_title,
        'wpfd_check_special_char_editing' => $checkSpecialCharEditing
    ));
    if (isset($_COOKIE['wpfd_show_columns']) && is_string($_COOKIE['wpfd_show_columns'])) {
        $listColumns = explode(',', $_COOKIE['wpfd_show_columns']);
    } else {
        $listColumns = array();
    }
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
    $locale = substr(get_locale(), 0, 2);
    wp_localize_script('wpfd-main', 'wpfd_admin', array(
        'allowed'                   => $config['allowedext'],
        'maxFileSize'               => $config['maxinputfile'],
        'serverUploadLimit'         => $serverUploadLimit,
        'msg_remove_file'           => esc_html__('Files removed with success!', 'wpfd'),
        'msg_remove_files'          => esc_html__('File(s) removed with success!', 'wpfd'),
        'msg_move_file'             => esc_html__('Files moved with success!', 'wpfd'),
        'msg_move_files'            => esc_html__('File(s) moved with success!', 'wpfd'),
        'msg_copy_file'             => esc_html__('Files copied with success!', 'wpfd'),
        'msg_copy_files'            => esc_html__('File(s) copied with success!', 'wpfd'),
        'msg_published_files'       => esc_html__('File(s) published with success!', 'wpfd'),
        'msg_unpublished_files'     => esc_html__('File(s) unpublished with success!', 'wpfd'),
        'msg_archive_zip_files'     => esc_html__('Please enter the archive name!', 'wpfd'),
        'msg_select_files'          => esc_html__('Please select file(s)!', 'wpfd'),
        'msg_not_select_cloud_files'=> esc_html__('Please do not select cloud files!', 'wpfd'),
        'msg_add_synchronize'       => esc_html__('Category added to synchronize queue!', 'wpfd'),
        'msg_add_category'          => esc_html__('Category created with success!', 'wpfd'),
        'msg_adding_category'       => esc_html__('Adding category...', 'wpfd'),
        'msg_remove_category'       => esc_html__('Category removed with success!', 'wpfd'),
        'msg_move_category'         => esc_html__('New category order saved!', 'wpfd'),
        'msg_edit_category'         => esc_html__('Category renamed with success!', 'wpfd'),
        'msg_edit_category_desc'    => esc_html__('Category description updated with success!', 'wpfd'),
        'msg_save_category'         => esc_html__('Category config saved with success!', 'wpfd'),
        'msg_select_category'       => esc_html__('PLease seleact a category!', 'wpfd'),
        'msg_save_file'             => esc_html__('File config saved with success!', 'wpfd'),
        'msg_rename_file'           => esc_html__('Please enter a name that doesn\'t include any of these characters: " \ /', 'wpfd'),
        'msg_rename_file_cloud'     => esc_html__('Please enter a name that doesn\'t include any of these characters: " * : < > ? / \ |', 'wpfd'),
        'msg_ordering_file'         => esc_html__('File ordering with success!', 'wpfd'),
        'msg_ordering_file2'        => esc_html__('File order saved with success!', 'wpfd'),
        'msg_upload_file'           => esc_html__('New File(s) uploaded with success!', 'wpfd'),
        'msg_ask_delete_file'       => esc_html__('Are you sure you want to delete this file?', 'wpfd'),
        'msg_ask_delete_files'      => esc_html__('Are you sure you want to delete the files you have selected?', 'wpfd'),
        'msg_multi_files_text'      => esc_html__(
            'This file is listed in several categories, settings are available in the original version of the file',
            'wpfd'
        ),
        'msg_multi_files_btn_label' => esc_html__('EDIT ORIGINAL FILE', 'wpfd'),
        'msg_copied_to_clipboard' => esc_html__('File URL copied to clipboard', 'wpfd'),
        'msg_shortcode_copied_to_clipboard' => esc_html__('Shortcode copied', 'wpfd'),
        'msg_purge_versions' => esc_html__(
            "You’re about to force removing your files revisions that exceed this setting.\nAre you sure?",
            'wpfd'
        ),
        'msg_files_index' => esc_html__(
            'You are about to launch a plain text index of all your document. It requires that you let this tab open until the end of the process.Click OK to launch',
            'wpfd'
        ),
        'msg_sync_done' => esc_html__('Synchronization is finished!', 'wpfd'),
        'msg_google_drive_sync_done' => esc_html__('Files will be synced in background', 'wpfd'),
        'msg_promtp_sync_reload_page' => esc_html__('The synchronization process is done! Do you want to reload the page to update categories tree?', 'wpfd'),
        'msg_prompt_category_params_changes_not_saved' => esc_html__('Are you sure you want to close the category edition without saving your change?', 'wpfd'),
        'msg_prompt_file_params_changes_not_saved' => esc_html__('Are you sure you want to close the file edition without saving your change?', 'wpfd'),
        'listColumns' => $listColumns,
        'locale' => $locale,
        'msg_upload_drop_file' => esc_html__('DROP FILE HERE TO UPLOAD', 'wpfd'),
        'msg_import_category_success' => esc_html__('Categories and files imported successfully!', 'wpfd'),
        'msg_import_folder_success' => esc_html__('Folder imported successfully!', 'wpfd'),
        'msg_import_folder_failed' => esc_html__('Failed to import category', 'wpfd'),
        'msg_import_folder_exists' => esc_html__('already exists', 'wpfd'),
        'import_target_category' => esc_html__('Import in WP File Download category', 'wpfd'),
        'import_run_import' => esc_html__('Import', 'wpfd'),
        'import_import_option' => esc_html__('Import option', 'wpfd'),
        'new_category_name' => esc_html__('New Category', 'wpfd'),
        'new_google_drive_category_name' => esc_html__('New Google Drive Category', 'wpfd'),
        'new_google_team_drive_category_name' => esc_html__('New Google Team Drive Category', 'wpfd'),
        'new_onedrive_category_name' => esc_html__('New Onedrive Category', 'wpfd'),
        'new_onedrive_business_category_name' => esc_html__('New Onedrive Business Category', 'wpfd'),
        'new_dropbox_category_name' => esc_html__('New Dropbox Category', 'wpfd'),
        'new_aws_category_name' => esc_html__('New Amazon S3 Category', 'wpfd'),
        'new_nextcloud_category_name' => esc_html__('New Nextcloud Category', 'wpfd'),
        'click_to_add_more_categories' => esc_html__('Click to add more categories', 'wpfd'),
        'msg_no_category_found' => esc_html__('No category found!', 'wpfd'),
        'msg_duplicate_category' => esc_html__('Category duplicate with success!', 'wpfd'),
        'msg_duplicate_category_failed' => esc_html__('Failed to duplicate category', 'wpfd'),
        'msg_duplicate_category_duplicating' => esc_html__('Duplicating...', 'wpfd'),
        'msg_admin_search_select_category' => esc_html__('— Select category —', 'wpfd'),
        'msg_admin_search_file_type_support' => esc_html__('This file type not supports.', 'wpfd'),
        'msg_admin_search_date' => esc_html__('This date is not valid.', 'wpfd'),
        'msg_admin_search_weight' => esc_html__('This weight is not valid.', 'wpfd'),
        'file_waiting_for_approval' => esc_html__('Waiting for approval', 'wpfd'),
        'edit' => esc_html__('Edit', 'wpfd'),
        'delete'                  => esc_html__('Delete', 'wpfd'),
        'add_to_queue'            => esc_html__('Add to queue', 'wpfd'),
        'msg_ask_delete_item'     => esc_html__('Are you sure you want to delete it?', 'wpfd'),
        'msg_ask_delete_email_per_category_record' => esc_html__('Are you sure you want to delete it?', 'wpfd'),
        'label_sync_root_folder' => esc_html__('Server folders', 'wpfd'),
        'label_sync_wpfd' => esc_html__('WP File Download', 'wpfd'),
        'email_per_category_wp_file_download_category_label' => esc_html__('WP File Download Category', 'wpfd'),
        'email_per_category_emails_label' => esc_html__('Emails', 'wpfd'),
        'email_per_category_actions_label' => esc_html__('Actions', 'wpfd'),
        'email_per_category_add' => esc_html__('Add', 'wpfd'),
        'email_per_category_save' => esc_html__('Save', 'wpfd'),
        'email_per_category_delete' => esc_html__('Delete', 'wpfd'),
        'email_per_category_delete_selected' => esc_html__('Delete selected', 'wpfd'),
        'label_watermark_wpfd' => esc_html__('WP File Download', 'wpfd'),
        'msg_quick_file_icon_set' => esc_html__('Your icon saved!', 'wpfd'),
        'msg_quick_file_icon_set_deleting_title' => esc_html__('Are you sure you want to remove this icon design override?', 'wpfd'),
        'msg_quick_file_icon_set_deleting_content' => esc_html__('This action is irreversible, and you will have to do the icon design setup again.', 'wpfd'),
        'msg_quick_file_icon_set_deleting_success' => esc_html__('The overide is deleted with success!', 'wpfd'),
        'msg_quick_file_icon_set_deleting_failed' => esc_html__('Failed to delete the overide.', 'wpfd'),
        'msg_quick_file_icon_set_upload_success' => esc_html__('Upload success!', 'wpfd'),
        'msg_quick_file_icon_set_upload_failed' => esc_html__('Failed to upload.', 'wpfd'),
        'quick_file_icon_set_title' => esc_html__('Quick edit:', 'wpfd'),
        'set_tag_placeholder' => esc_html__('Input tags here...', 'wpfd'),
    ));
    wp_enqueue_script('wpfd-chosen', plugins_url('assets/js/chosen.jquery.min.js', __FILE__));
    wp_enqueue_style('buttons');
    if (Utilities::getInput('noheader', 'GET', 'bool')) {
        //remove script loaded in bottom of page
        wp_dequeue_script('sitepress-scripts');
        wp_dequeue_script('wpml-tm-scripts');
    }
    wp_enqueue_style('wpfd-google-icon', plugins_url('assets/ui/fonts/material-icons.min.css', __FILE__));
    // Fts
    $pid = sha1(time() . uniqid());
    wp_localize_script('wpfd-main', 'wpfd_fts', array(
        'pid' => $pid,
        'pingtimeout' => 30000
    ));

    // Add new icon styles by set
    $iconSet = isset($config['icon_set']) ? trim($config['icon_set']) : 'svg';
    if ($iconSet === 'default') {
        wp_enqueue_style('wpfd-core-icons', plugins_url('app/admin/assets/ui/css/core-icons.css', WPFD_PLUGIN_FILE), array('wpfd-core'), WPFD_VERSION);
    } else {
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
                'wpfd-style-icon-set-' . $iconSet,
                $cssUrl,
                array('wpfd-core'),
                WPFD_VERSION
            );
        }
    }

    // Enqueue style icon sets on main view
    if ($page === 'wpfd') {
        if ($iconSet === 'svg') {
            wp_enqueue_style(
                'wpfd-style-icon-sets',
                WPFD_PLUGIN_URL . 'app/admin/assets/ui/css/iconsbuilder.css',
                array('wpfd-core'),
                WPFD_VERSION
            );
        } else {
            wp_enqueue_style(
                'wpfd-style-png-icon-sets',
                WPFD_PLUGIN_URL . 'app/admin/assets/ui/css/png.css',
                array('wpfd-core'),
                WPFD_VERSION
            );
        }
    }

    // Add debug for javascript
    if (defined('WPFD_DEBUG') && WPFD_DEBUG) {
        wp_localize_script('wpfd-main', 'wpfd_debug', array(
            'debug' => true,
            'ajax' => true
        ));
    }
}

/**
 * Highlight tag menu
 *
 * @return void
 */
function wpfd_menu_highlight()
{
    global $parent_file, $submenu_file, $post_type;
    if ((string) $submenu_file === 'edit-tags.php?taxonomy=wpfd-tag') {
        $parent_file = 'wpfd'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Force bold it
    }
}

add_action('init', 'wpfd_mce_button');

/**
 * Add mce button
 *
 * @return void
 */
function wpfd_mce_button()
{
    add_filter('mce_external_plugins', 'wpfd_add_buttons');
}

/**
 * Add button editor
 *
 * @param array $plugin_array Editor plugins array
 *
 * @return array
 */
function wpfd_add_buttons($plugin_array)
{
    $plugin_array['wpfd'] = plugins_url('app/admin/assets/js/editor_plugin.js', dirname(dirname(__FILE__)));
    return $plugin_array;
}

/**
 * Add insert wpfd in editor
 *
 * @return void
 */
function wpfd_button()
{
    $context = '';
    wp_enqueue_style('wpfd-modal', plugins_url('assets/css/leanmodal.css', __FILE__));
    wp_enqueue_script('wpfd-modal', plugins_url('assets/js/jquery.leanModal.min.js', __FILE__));
    wp_enqueue_script('wpfd-modal-init', plugins_url('assets/js/leanmodal.init.js', __FILE__));
    wp_localize_script('wpfd-modal-init', 'wpfdmodalvars', array(
        'wpfd_iframe_title' => esc_html__('WP File Download Iframe', 'wpfd')
    ));
    $context .= "<a href='#wpfdmodal' class='button wpfdlaunch' id='wpfdlaunch' title='WP File Download'>";
    $context .= "<span class='dashicons dashicons-download'>";
    $context .= '</span> ' . esc_html__('WP File Download', 'wpfd') . '</a>';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content escape above
    echo $context;
}

add_action('admin_enqueue_scripts', 'wpfd_heartbeat_enqueue');
add_filter('heartbeat_received', 'wpfd_heartbeat_received', 10, 2);
add_filter('vc_edit_form_enqueue_script', 'wpfd_init_vc_insert_button', 100, 1);

/**
 * Init Wp File Download button for Visual Composer
 *
 * @param array $scripts Scripts
 *
 * @return array
 */
function wpfd_init_vc_insert_button($scripts)
{
    $scripts[] = plugins_url('assets/js/leanmodal.init.js', __FILE__);
    return $scripts;
}

/**
 * Load the heartbeat JS
 *
 * @param string $hook_suffix Hook suffix
 *
 * @return void
 */
function wpfd_heartbeat_enqueue($hook_suffix)
{
    // Make sure the JS part of the Heartbeat API is loaded.
    wp_enqueue_script('heartbeat');
    add_action('admin_print_footer_scripts', 'wpfd_heartbeat_footer_js');

    //fix bootbox conflict
    wp_deregister_script('bootbox');
}

/**
 * Inject our JS into the admin footer
 *
 * @return void
 */
function wpfd_heartbeat_footer_js()
{
    global $pagenow;
    ?>
    <script>
        (function ($) {
            // Hook into the heartbeat-send
            $(document).on('heartbeat-send', function (e, data) {
                data['wpfd_heartbeat'] = 'sync_process';
            });
            // Listen for the custom event "heartbeat-tick" on $(document).
            $(document).on('heartbeat-tick', function (e, data) {
                // Only proceed if our EDD data is present
                if (!data['wpfd_result']) {
                    return false;
                }
            });
        }(jQuery));
    </script>
    <?php
}

/**
 * Modify the data that goes back with the heartbeat-tick
 *
 * @param array $response Response array
 * @param array $data     Heartbeat data array
 *
 * @return array
 */
function wpfd_heartbeat_received($response, $data)
{
    // Make sure we only run our query if the edd_heartbeat key is present
    if (isset($data['wpfd_heartbeat']) && $data['wpfd_heartbeat'] === 'sync_process') {
        Application::getInstance('Wpfd');
        $configModel = Model::getInstance('config');
        $config = $configModel->getConfig();

        // Send back the number of timestamp
        $response['wpfd_result'] = time();
        $lists       = get_option('wpfd_list_folder_sync');
        $server_sync = $config['server_sync'];
        $lastRun     = isset($config['last_run_server_sync_time']) ? $config['last_run_server_sync_time'] : time();
        $time_sync   = $config['server_sync_time'];

        if (!empty($lists) && (int) $time_sync !== 0 && (time() - (int) $lastRun > (int) $time_sync * 60)) {
            /**
             * Action fire to sync FTP folders and files
             *
             * @internal
             */
            do_action('wpfdSyncServerFolder');
        }

        /**
         * Action fire to sync cloud
         *
         * @internal
         */
        do_action('wpfd_addon_auto_sync');
    }
    return $response;
}

/**
 * Action when delete a term, base on this hook we can modify our tags
 *
 * @param mixed   $term         Term
 * @param integer $term_id      Term Id
 * @param string  $taxonomy     Taxonomy
 * @param mixed   $deleted_term Delete term
 *
 * @return void
 */
function wpfd_delete_term($term, $term_id, $taxonomy, $deleted_term)
{
    if ($taxonomy === 'wpfd-tag') {
        $deleted_slug = $deleted_term->slug;
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'wpfd_file',
            'post_status' => 'any',
        );
        $files = get_posts($args);

        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                $metadata = get_post_meta($file->ID, '_wpfd_file_metadata', true);
                if (isset($metadata['file_tags'])) {
                    $tags = explode(',', $metadata['file_tags']);
                    $tags = array_map(function ($tag) {
                        return trim(strtolower($tag));
                    }, $tags);
                    $tags = array_map('trim', array_unique($tags));
                    if (in_array($deleted_slug, $tags)) {
                        $del_key = array_search($deleted_slug, $tags);
                        unset($tags[$del_key]);
                        $tags = array_values($tags);
                    }
                    $metadata['file_tags'] = implode(',', $tags);
                    update_post_meta($file->ID, '_wpfd_file_metadata', $metadata);
                }
            }
        }
    }
}

/**
 * Action when editing term
 *
 * @param integer $term_id  Term Id
 * @param mixed   $tt_id    Term txm id
 * @param string  $taxonomy Taxonomy
 * @param array   $args     Params
 *
 * @return void
 */
function wpfd_edit_term($term_id, $tt_id, $taxonomy, $args)
{
    if ($taxonomy === 'wpfd-tag') {
        $oldTerm = get_term($term_id, $taxonomy);
        $oldName = isset($oldTerm->name) ? $oldTerm->name : '';
        $newName = is_array($args) && isset($args['name']) ? $args['name'] : '';
        $newSlug = is_array($args) && isset($args['slug']) ? str_replace('-', ' ', $args['slug']) : '';

        if ((strtolower($newName) === strtolower($newSlug)) && $newName !== '' && $oldName !== '') {
            $fileArgs = array(
                'posts_per_page' => -1,
                'post_type' => 'wpfd_file',
                'post_status' => 'any'
            );

            $files = get_posts($fileArgs);

            if (is_array($files) && !empty($files)) {
                foreach ($files as $file) {
                    $metadata = get_post_meta($file->ID, '_wpfd_file_metadata', true);
                    if (isset($metadata['file_tags'])) {
                        $fileTags = explode(',', $metadata['file_tags']);
                        $tags = array_map(function ($tag) {
                            return trim(strtolower($tag));
                        }, $fileTags);
                        $tags = array_map('trim', array_unique($tags));
                        if (in_array(strtolower($oldName), $tags)) {
                            $edi_key = array_search(strtolower($oldName), $tags);
                            $fileTags[$edi_key] = $newName;
                            $fileTags = array_values($fileTags);
                        }
                        $metadata['file_tags'] = implode(',', $fileTags);
                        update_post_meta($file->ID, '_wpfd_file_metadata', $metadata);
                    }
                }
            }
        }
    }
}

add_action('admin_enqueue_scripts', 'wpfd_media_button');
/**
 * Scripts for media button
 *
 * @return void
 */
function wpfd_media_button()
{
    wp_enqueue_media();
}


/********************
 * NEW UX VERSION 4.4
 ********************/
if (defined('WPFD_ADMIN_UI') && WPFD_ADMIN_UI === true) {
    // Include new ui functions
    include_once $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'ui-functions.php';
}
// Icons Builder
include_once $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'icons-builder-functions.php';
// Disable all admin notice for page belong to plugin
add_action('admin_print_scripts', function () {
    global $wp_filter;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
    if ((!empty($_GET['page']) && in_array($_GET['page'], array('wpfd-setup', 'wpfd', 'wpfd-config', 'wpfd-icons-builder')))) {
        if (is_user_admin()) {
            if (isset($wp_filter['user_admin_notices'])) {
                unset($wp_filter['user_admin_notices']);
            }
        } elseif (isset($wp_filter['admin_notices'])) {
            unset($wp_filter['admin_notices']);
        }
        if (isset($wp_filter['all_admin_notices'])) {
            unset($wp_filter['all_admin_notices']);
        }
    }
});

// Gutenberg integration
if (function_exists('wpfd_gutenberg_integration')) {
    add_action('enqueue_block_editor_assets', 'wpfd_gutenberg_integration');
}

if (function_exists('wpfd_blocks_categories')) {
    global $wp_version;
    if (version_compare($wp_version, '5.8', '>=')) {
        add_filter('block_categories_all', 'wpfd_blocks_categories', 10, 2);
    } else {
        add_filter('block_categories', 'wpfd_blocks_categories', 10, 2);
    }
}

if (function_exists('wpfd_file_upload_pending_status')) {
    add_filter('wpfd_file_upload_pending', 'wpfd_file_upload_pending_status', 10, 2);
}

if (function_exists('wpfd_file_enable_status')) {
    add_filter('wpfd_file_enable_state', 'wpfd_file_enable_status', 10, 1);
}

add_filter('wpfd_sync_ftp_to_category', 'syncFtpToWpfd', 10, 3);
/**
 * Sync from FTP to WPFD
 *
 * @param integer|boolean $result     Result
 * @param array           $datas      QUeue datas
 * @param integer         $element_id Queue ID
 *
 * @return boolean
 */
function syncFtpToWpfd($result, $datas, $element_id)
{
    $app = Application::getInstance('Wpfd');
    $fileController = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'config.php';
    if (!file_exists($fileController)) {
        return false;
    }
    include_once $fileController;
    $controller = new WpfdControllerConfig();
    return $controller->syncFtpToWpfd($result, $datas, $element_id);
}

add_filter('wpfd_sync_category_to_ftp', 'syncWpfdToFtp', 10, 3);
/**
 * Sync from WPFD to FTP
 *
 * @param integer|boolean $result     Result
 * @param array           $datas      QUeue datas
 * @param integer         $element_id Queue ID
 *
 * @return boolean
 */
function syncWpfdToFtp($result, $datas, $element_id)
{
    $app = Application::getInstance('Wpfd');
    $fileController = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'config.php';
    if (!file_exists($fileController)) {
        return false;
    }
    include_once $fileController;
    $controller = new WpfdControllerConfig();

    return $controller->syncWpfdToFtp($result, $datas, $element_id);
}

add_action('admin_init', 'WpfdCategoryWatermark');
/**
 * Wpfd Category Watermark
 *
 * @return void
 */
function WpfdCategoryWatermark()
{
    if (!class_exists('WpfdCategoryWatermark')) {
        $application   = Application::getInstance('Wpfd');
        $path_WpfdCategoryWatermark = $application->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes';
        $path_WpfdCategoryWatermark .= DIRECTORY_SEPARATOR . 'WpfdCategoryWatermark.php';
        require_once $path_WpfdCategoryWatermark;
        new WpfdCategoryWatermark();
    }
}

add_action('admin_init', 'WpfdExpiredSearchCacheClearing');
/**
 * WpfdExpiredSearchCacheClearing
 *
 * @return void
 */
function WpfdExpiredSearchCacheClearing()
{
    Application::getInstance('Wpfd');
    $configModel = Model::getInstance('configfront');
    $searchConfig = $configModel->getSearchConfig();

    if (isset($searchConfig['search_cache']) && intval($searchConfig['search_cache']) === 1) {
        $cache_folder = WP_CONTENT_DIR . '/wp-file-download/cache';

        if (file_exists($cache_folder)) {
            if (!is_writable($cache_folder)) {
                chmod($cache_folder, 0755);
            }

            if (isset($searchConfig['cache_lifetime']) && is_numeric($searchConfig['cache_lifetime'])) {
                $expiration_time = $searchConfig['cache_lifetime'] * MINUTE_IN_SECONDS;
            } else {
                $expiration_time = 5 * MINUTE_IN_SECONDS;
            }

            // Clean expired search cache files
            $objects = scandir($cache_folder);
            if (false !== $objects) {
                foreach ($objects as $object) {
                    if ($object !== '.' && $object !== '..') {
                        $filePath = $cache_folder . DIRECTORY_SEPARATOR . $object;
                        if (filetype($filePath) === 'file' && (time() - filemtime($filePath)) >= $expiration_time) {
                            unlink($filePath);
                        }
                    }
                }
            }
        }
    }
}

add_action('admin_init', 'WpfdRemoveExpiredPreviewFiles');
/**
 * WpfdRemoveExpiredPreviewFiles
 *
 * @return void
 */
function WpfdRemoveExpiredPreviewFiles()
{
    $app = Application::getInstance('Wpfd');
    $configModel = Model::getInstance('config');
    $configs = $configModel->getConfig();

    if (!class_exists('WpfdBase')) {
        $path_wpfdbase = $app->getPath() . DIRECTORY_SEPARATOR . $app->getType() . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'WpfdBase.php';
        require_once $path_wpfdbase;
    }

    if (isset($configs['open_pdf_in']) && intval($configs['open_pdf_in']) === 1) {
        $previewFileDir = WpfdBase::getPreviewFilesPath();

        if (!is_writable($previewFileDir)) {
            chmod($previewFileDir, 0755);
        }

        /**
         * Filter to set the default time to remove expired preview files
         *
         * @param integer|mixed
         */
        $expiredTime = apply_filters('wpfd_remove_expired_preview_file_time', 7 * 24 * 60 * MINUTE_IN_SECONDS); // A week

        // Clean expired preview files
        $objects = scandir($previewFileDir);
        if (false !== $objects) {
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $filePath = $previewFileDir . DIRECTORY_SEPARATOR . $object;
                    if (filetype($filePath) === 'file' && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf' && (time() - filemtime($filePath)) >= $expiredTime) {
                        unlink($filePath);
                    }
                }
            }
        }
    }
}

add_action('admin_init', 'wpfd_cloud_available_tags_indexing');
/**
 * Cloud available tag indexing
 *
 * @throws Exception Fire if errors
 *
 * @return void
 */
function wpfd_cloud_available_tags_indexing()
{
    $app = Application::getInstance('Wpfd');
    if (!class_exists('WpfdControllerFiles')) {
        $path_control_files = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'files.php';
        require_once $path_control_files;
    }

    $index = get_option('wpfd_cloud_available_tags_indexing', false);
    $process = get_option('wpfd_cloud_available_tags_index_processing', 0);
    $index = ($index === true || intval($index) === 1) ? true : false;
    $process = (!is_null($process) && is_numeric($process)) ? intval($process) : 0;

    /**
     * Filter to index available tags
     *
     * @param boolean Index
     *
     * @return boolean|mixed
     *
     * @internal
     *
     * @ignore
     */
    $hookIndexing = apply_filters('wpfd_cloud_index_available_tag_processing', false);
    $hookIndexing = (!is_null($hookIndexing) && ((bool)$hookIndexing === true || intval($hookIndexing) === 1)) ? true : false;

    if (($index === true && intval($process) === 0) || $hookIndexing) {
        $filesController = new WpfdControllerFiles();
        $filesController->cloudAvailableTagsIndexing();
    }
}