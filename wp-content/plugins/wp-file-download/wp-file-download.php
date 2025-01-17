<?php
/**
 * Plugin Name: الفولدر الذكي
 * Plugin URI: https://www.joomunited.com/wordpress-products/wp-file-download
 * Description: WP File Download, a new way to manage files in WordPress
 * Author: Joomunited
 * Version: 6.1.7
 * Text Domain: wpfd
 * Domain Path: /app/languages
 * Author URI: https://www.joomunited.com
 * Update URI: https://www.joomunited.com/juupdater_files/wp-file-download.json
 */

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');

/*
 * Define WP File Download current version
 */
define('WPFD_VERSION', '6.1.7');

// Check plugin requirements
if (version_compare(PHP_VERSION, '5.6', '<')) {
    if (!function_exists('wpfdDisablePlugin')) {
        /**
         * Deactivate plugin
         *
         * @return void
         */
        function wpfdDisablePlugin()
        {
            if (current_user_can('activate_plugins') && is_plugin_active(plugin_basename(__FILE__))) {
                deactivate_plugins(__FILE__);
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Internal function used
                unset($_GET['activate']);
            }
        }
    }

    if (!function_exists('wpfdShowError')) {
        /**
         * Show notice
         *
         * @return void
         */
        function wpfdShowError()
        {
            echo '<div class="error"><p>';
            echo '<strong>WP File Download</strong>';
            echo ' needs at least PHP 5.6 version, please update php before installing the plugin.</p></div>';
        }
    }

    // Add actions
    add_action('admin_init', 'wpfdDisablePlugin');
    add_action('admin_notices', 'wpfdShowError');

    // Do not load anything more
    return;
}

//Include the jutranslation helpers
include_once('jutranslation' . DIRECTORY_SEPARATOR . 'jutranslation.php');
call_user_func(
    '\Joomunited\WPFileDownload\Jutranslation\Jutranslation::init',
    __FILE__,
    'wpfd',
    'WP File Download',
    'wpfd',
    'app' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'wpfd-en_US.mo'
);

if (!class_exists('\Joomunited\WPFileDownload\JUCheckRequirements')) {
    include_once('app/requirements.php');
}
if (class_exists('\Joomunited\WPFileDownload\JUCheckRequirements')) {
    // Plugins name for translate
    $args = array(
        'plugin_name' => esc_html__('WP File Download', 'wpfd'),
        'plugin_path' => 'wp-file-download/wp-file-download.php',
        'plugin_textdomain' => 'wpfd',
        'requirements' => array(
            'php_version' => '5.6',
            'php_modules' => array(
                'xml' => 'error'
            ),
            // Minimum addons version
            'addons_version' => array(
                'wpfdCloudAddons' => '4.5.0'
            )
        ),
    );
    $wpfdCheck = call_user_func('\Joomunited\WPFileDownload\JUCheckRequirements::init', $args);
    if (!$wpfdCheck['success']) {
        // Do not load anything more
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Internal function used
        unset($_GET['activate']);
        return;
    }
}

include_once('framework' . DIRECTORY_SEPARATOR . 'ju-libraries.php');

if (!defined('WPFD_PLUGIN_FILE')) {
    define('WPFD_PLUGIN_FILE', __FILE__);
}
if (!defined('WPFD_PLUGIN_DIR_PATH')) {
    define('WPFD_PLUGIN_DIR_PATH', trailingslashit(realpath(dirname(__FILE__))));
}
if (!defined('WPFD_PLUGIN_URL')) {
    define('WPFD_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('WPFD_VENDOR_DIR')) {
    define('WPFD_VENDOR_DIR', WPFD_PLUGIN_DIR_PATH . 'vendor' . DIRECTORY_SEPARATOR);
}
// Define to use new ui
define('WPFD_ADMIN_UI', true);

include_once('app' . DIRECTORY_SEPARATOR . 'autoload.php');
include_once('app' . DIRECTORY_SEPARATOR . 'install.php');
include_once('app' . DIRECTORY_SEPARATOR . 'widget.php');
include_once('app' . DIRECTORY_SEPARATOR . 'functions.php');

//Initialise the application
$app = call_user_func('Joomunited\WPFramework\v1_0_6\Application::getInstance', 'Wpfd', __FILE__);
$app->init();

if (is_admin()) {
    //config section
    if (!defined('JU_BASE')) {
        define('JU_BASE', 'https://www.joomunited.com/');
    }

    $remote_updateinfo = JU_BASE . 'juupdater_files/wp-file-download.json';
    //end config

    require 'juupdater/juupdater.php';
    $UpdateChecker = Jufactory::buildUpdateChecker(
        $remote_updateinfo,
        __FILE__
    );
}

add_action('init', 'wpfd_wizard_setup_include');
add_action('admin_init', 'wpfd_wizard_setup_redirect');

// Load Addons
if (isset($wpfdCheck) && !empty($wpfdCheck['load'])) {
    foreach ($wpfdCheck['load'] as $addonName) {
        if (function_exists($addonName . 'Init')) {
            call_user_func($addonName . 'Init');
        }
    }
}

add_action('init', function () {
// Load schedule tasks
    if (function_exists('wpfd_init_tasks')) {
        wpfd_init_tasks();
    }

// Load queue
    if (! class_exists('\Joomunited\Queue\JuMainQueue')) {
        require_once WPFD_PLUGIN_DIR_PATH . 'queue/JuMainQueue.php';
    }
    /**
     * Translate for queue class.
     * ***** DO NOT REMOVE *****
     * Translate strings in JuMainQueue.php file
     * esc_html__('Some of JoomUnited\'s plugins require to process some task in background (cloud synchronization, file processing, ...).', 'wpfd');
     * esc_html__('To prevent PHP timeout errors during the process, it\'s done asynchronously in the background.', 'wpfd');
     * esc_html__('These settings let you optimize the process depending on your server resources.', 'wpfd'); ?>
     * esc_html__('Show the number of items waiting to be processed in the admin menu bar.', 'wpfd');
     * esc_html__('You can reduce the background task processing by changing this parameter. It could be necessary when the plugin is installed on small servers instances but requires consequent task processing. Default 75%.', 'wpfd');
     * esc_html__('You can reduce the background task ajax calling by changing this parameter. It could be necessary when the plugin is installed on small servers instances or shared hosting. Default 15s.', 'wpfd');
     * esc_html__('Pause queue', 'wpfd');
     * esc_html__('Pause queue', 'wpfd');
     * esc_html__('Start queue', 'wpfd');
     * esc_html__('Enable', 'wpfd');
     *
     * ***** DO NOT REMOVE *****
     * End translate for queue class
     */
    $args      = array(
        'use_queue'        => true,
        'queue_options'    => array(
            'status_menu_bar' => true,
            'mode_debug'      => ( defined('WPFD_DEBUG') && WPFD_DEBUG ) ? true : false,
            'tasks_speed'     => 100,
        ),
        'status_templates' => array(
            'wpfd_sync_google_drive'        => esc_html__('Syncing %d Google Drive folders', 'wpfd'),
            'wpfd_google_drive_remove'      => esc_html__('Comparing %d Google Drive folders', 'wpfd'),
            'wpfd_sync_dropbox'             => esc_html__('Syncing %d Dropbox folders', 'wpfd'),
            'wpfd_dropbox_remove'           => esc_html__('Comparing %d Dropbox folders', 'wpfd'),
            'wpfd_sync_onedrive'            => esc_html__('Syncing %d Onedrive folders', 'wpfd'),
            'wpfd_onedrive_remove'          => esc_html__('Comparing %d Onedrive folders', 'wpfd'),
            'wpfd_sync_onedrive_business'   => esc_html__('Syncing %d Onedrive Business folders', 'wpfd'),
            'wpfd_onedrive_business_remove' => esc_html__('Comparing %d Onedrive Business folders', 'wpfd'),
            'wpfd_sync_aws'                 => esc_html('Syncing %d AWS folders', 'wpfd'),
            'wpfd_aws_remove'               => esc_html('Comparing %d AWS folders', 'wpfd'),
            'wpfd_download_cloud_thumbnail' => esc_html__('Generating %d thumbnails', 'wpfd'),
            'wpfd_sync_ftp_to_category'     => esc_html__('Syncing %d files from FTP', 'wpfd'),
            'wpfd_sync_category_to_ftp'     => esc_html__('Syncing %d folders from WP File Download', 'wpfd')
        ),
    );
    $wpfdQueue = call_user_func('\Joomunited\Queue\JuMainQueue::getInstance', 'wpfd');
    $wpfdQueue->init($args);
});

if (!function_exists('wpfdPluginCheckForUpdates')) {
    /**
     * Plugin check for updates
     *
     * @param object $update      Update
     * @param array  $plugin_data Plugin data
     * @param string $plugin_file Plugin file
     *
     * @return array|boolean|object
     */
    function wpfdPluginCheckForUpdates($update, $plugin_data, $plugin_file)
    {
        if ($plugin_file !== 'wp-file-download/wp-file-download.php') {
            return $update;
        }

        if (empty($plugin_data['UpdateURI']) || !empty($update)) {
            return $update;
        }

        $response = wp_remote_get($plugin_data['UpdateURI']);

        if (is_wp_error($response) || empty($response['body'])) {
            return $update;
        }

        $custom_plugins_data = json_decode($response['body'], true);

        $package = null;
        $token = get_option('ju_user_token');
        if (!empty($token)) {
            $package = $custom_plugins_data['download_url'] . '&token=' . $token . '&siteurl=' . get_option('siteurl');
        }

        return array(
            'version' => $custom_plugins_data['version'],
            'package' => $package
        );
    }
    add_filter('update_plugins_www.joomunited.com', 'wpfdPluginCheckForUpdates', 10, 3);
}

/**
 * Add to the queue
 *
 * @param array   $datas        Datas details
 * @param array   $responses    Responses details
 * @param boolean $check_status Check status
 *
 * @return void
 */
function wpfdAddToQueue($datas = array(), $responses = array(), $check_status = false)
{
    $wpfdQueue = \Joomunited\Queue\JuMainQueue::getInstance('wpfd');
    $row = $wpfdQueue->checkQueueExist(json_encode($datas));
    $exist = false;
    if (!$row) {
        $exist = false;
    } else {
        if (!$check_status) {
            if ((int)$row->status === 0) {
                $exist = true;
            }
        } else {
            $exist = true;
        }
    }

    if (!$exist) {
        $wpfdQueue->addToQueue($datas, $responses);
    }
}
