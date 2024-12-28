<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Form;
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class WpfdViewConfig
 */
class WpfdViewConfig extends View
{
    /**
     * Theme name
     *
     * @var string|mixed
     */
    public $theme;

    /**
     * Global configuration
     *
     * @var array|mixed
     */
    public $config;

    /**
     * File configuration
     *
     * @var array|mixed
     */
    public $file_config;

    /**
     * Search configuration
     *
     * @var array|mixed
     */
    public $search_config;

    /**
     * Upload configuration
     *
     * @var array|mixed
     */
    public $upload_config;

    /**
     * File in category configuration
     *
     * @var array|mixed
     */
    public $file_cat_config;

    /**
     * Theme list
     *
     * @var array|mixed
     */
    public $themes;

    /**
     * Theme forms
     *
     * @var array|mixed
     */
    public $themeforms;

    /**
     * Configuration form
     *
     * @var array|mixed
     */
    public $configform;

    /**
     * File configuration form
     *
     * @var array|mixed
     */
    public $file_configform;

    /**
     * Search form
     *
     * @var array|mixed
     */
    public $searchform;

    /**
     * Search shortcode
     *
     * @var string|mixed
     */
    public $search_shortcode;

    /**
     * Clone form
     *
     * @var string|mixed
     */
    public $clone_form;

    /**
     * Upload form
     *
     * @var string|mixed
     */
    public $upload_form;

    /**
     * File in category form
     *
     * @var string|mixed
     */
    public $file_catform;

    /**
     * Translate form
     *
     * @var string|mixed
     */
    public $translate_form;

    /**
     * Notification settings
     *
     * @var array|mixed
     */
    public $notifications_config;

    /**
     * Mail settings
     *
     * @var array|mixed
     */
    public $mail_option_config;

    /**
     * Notification form
     *
     * @var array|mixed
     */
    public $notifications_form;

    /**
     * File access form
     *
     * @var array|mixed
     */
    public $fileaccessform;

    /**
     * Export form
     *
     * @var array|mixed
     */
    public $exportform;

    /**
     * Server sync form
     *
     * @var array|mixed
     */
    public $server_sync_form;

    /**
     * Render view config
     *
     * @param null|string $tpl Template name
     *
     * @return void
     */
    public function render($tpl = null)
    {
        Application::getInstance('Wpfd');
        $modelConf   = $this->getModel('config');
        $this->theme = $modelConf->getThemeConfig();
        if ($this->theme === '') {
            $this->theme = 'default';
        }
        $this->config          = $modelConf->getConfig();
        $this->file_config     = $modelConf->getFileConfig();
        $this->search_config   = $modelConf->getSearchConfig();
        $this->upload_config   = $modelConf->getUploadConfig();
        $this->file_cat_config = $modelConf->getFileInCatConfig();
        $this->themes          = $modelConf->getThemes();
        $form                  = new Form();

        /**
         * Filters to update the preview queue running
         *
         * @return boolean
         */
        $updatePreviewRuning = apply_filters('_wpfd_preview_generate_queue_running_updating', false);
        if ($updatePreviewRuning) {
            update_option('_wpfd_preview_generate_queue_running', false, false);
        }

        foreach ($this->themes as $themName) {
            if (WpfdBase::checkExistTheme($themName)) {
                $formfile = Application::getInstance('Wpfd')->getPath() . DIRECTORY_SEPARATOR . 'site';
                $formfile .= DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'wpfd-' . $themName;
                $formfile .= DIRECTORY_SEPARATOR . 'form.xml';
            } else {
                $formfile = wpfd_locate_theme($themName, 'form.xml');
            }
            $themeConfig = $modelConf->getThemeParams($themName);
            if ($form->load($formfile, $themeConfig)) {
                $this->themeforms[$themName] = $form->render();
            } else {
                $this->themeforms[$themName] = '';
            }
        }

        $adminForm = new Form();
        if ($adminForm->load('config_admin', $this->config)) {
            $this->configform['admin'] = $adminForm->render();
            $this->configform['admin'] = str_replace('text2', 'text', $this->configform['admin']);
        }
        $frontendForm = new Form();
        if ($frontendForm->load('config_frontend', $this->config)) {
            $this->configform['frontend'] = $frontendForm->render();
            $this->configform['frontend'] = str_replace('text2', 'text', $this->configform['frontend']);
        }
        $statisticsForm = new Form();
        if ($statisticsForm->load('config_statistics', $this->config)) {
            $this->configform['statistics'] = $statisticsForm->render();
            $this->configform['statistics'] = str_replace('text2', 'text', $this->configform['statistics']);
        }

        $config_watermark_content = wpfd_admin_ui_config_watermark();
        $watermarkForm = new Form();
        if ($watermarkForm->load('config_watermark_global', $this->config)) {
            $config_watermark_content .= str_replace('text2', 'text', $watermarkForm->render());
        }
        if ($watermarkForm->load('config_watermark', $this->config)) {
            $config_watermark_content .= str_replace('text2', 'text', $watermarkForm->render());
        }
        $this->configform['watermark_category']  = array('title'=> 'Watermark', 'content' => $config_watermark_content);

        $file_form = new Form();
        if ($file_form->load('file_config', $this->file_config)) {
            $this->file_configform = $file_form->render();
        }
        $search_form = new Form();
        if ($search_form->load('search', $this->search_config)) {
            $this->searchform = $search_form->render();
        }
        $search_shortcode = new Form();
        if ($search_shortcode->load('search_shortcode', $this->search_config)) {
            $this->search_shortcode = $search_shortcode->render();
        }
        Application::getInstance('Wpfd');
        $clone_form = new Form();
        if ($clone_form->load('clone', array())) {
            $this->clone_form = $clone_form->render();
        }
        Application::getInstance('Wpfd');
        $upload_form = new Form();
        if ($upload_form->load('upload', $this->upload_config)) {
            $this->upload_form = $upload_form->render();
        }
        Application::getInstance('Wpfd');
        $file_cat_form = new Form();
        if ($file_cat_form->load('file_cat_sortcode', $this->file_cat_config)) {
            $this->file_catform = $file_cat_form->render();
        }

        if (defined('WPFD_ADMIN_UI') && WPFD_ADMIN_UI === true) {
            // juTranslate tab content
            $juTranslateContent = '';

            ob_start();
            \Joomunited\WPFileDownload\Jutranslation\Jutranslation::getInput();
            $juTranslateContent = ob_get_contents();
            ob_end_clean();

            $this->translate_form = $juTranslateContent;

            // Notification
            Application::getInstance('Wpfd');
            $modelNotify                = $this->getModel('notification');
            $this->notifications_config = $modelNotify->getNotificationsConfig();
            $this->mail_option_config   = $modelNotify->getMailOptionConfig();
            $notifications_form         = new Form();
            if ($notifications_form->load('notifications', $this->notifications_config)) {
                $this->notifications_form['email_notification_editor'] = $notifications_form->render();
            }

            Application::getInstance('Wpfd');
            $mailoption_form = new Form();
            if ($mailoption_form->load('mail_option', $this->mail_option_config)) {
                $this->notifications_form['mail_option'] = $mailoption_form->render();
            }

            // User roles
            $this->fileaccessform['user_roles'] = wpfd_admin_ui_user_roles_content();

            // Download limit
            $downloadLimitForm = new Form();
            if ($downloadLimitForm->load('download_limit', $this->config)) {
                $this->fileaccessform['download_limit'] = $downloadLimitForm->render();
                $this->fileaccessform['download_limit'] = str_replace('text2', 'text', $this->fileaccessform['download_limit']);
            }

            // Export
            $this->exportform = wpfd_admin_ui_export_content();

            // Server sync
            $this->server_sync_form = wpfd_admin_ui_server_sync_content();

            add_action('wpfd_admin_ui_configuration_content', array($this, 'buildConfigContents'), 10, 1);

            $tpl = 'ui-default';
        }
        parent::render($tpl);
    }

    /**
     * Build config content
     *
     * @return void
     */
    public function buildConfigContents()
    {
        $html = '';
        $menuItems = wpfd_admin_ui_configuration_menu_get_items();
        $success = Utilities::getInput('msg', 'GET', 'string');
        $message = '';

        if ($success === 'success') {
            $message  = '<div class="save-message">';
            $message .= '<p>' . esc_html__('Saved successfully!', 'wpfd') . '</p>';
            $message .= '<a type="button" class="cancel-btn"></a>';
            $message .= '</div>';
        }

        if ($success === 'sender_email_empty') {
            $message  = '<div class="save-message-error">';
            $message .= '<p>' . esc_html__('You need to provide a valid email for the sender field', 'wpfd') . '</p>';
            $message .= '<a type="button" class="cancel-btn"></a>';
            $message .= '</div>';
        }
        foreach ($menuItems as $key => $item) {
            if (isset($item[1]) && trim($item[1]) !== '') {
                $multiHtml = '';
                $forms     = explode(',', $item[1]);
                $isTab = false;
                foreach ($forms as $form) {
                    if (is_array($this->{$form})) {
                        $multiHtml .= wpfd_admin_ui_configuration_build_tabs($this->{$form}, $message);
                        $isTab = true;
                    } else {
                        $multiHtml .= $this->{$form};
                    }
                }
                if ($isTab) {
                    $html .= wpfd_admin_ui_configuration_build_content($key, $multiHtml);
                } else {
                    $html .= wpfd_admin_ui_configuration_build_content($key, $multiHtml, $message);
                }
            }
        }
        // phpcs:ignore -- escaped
        echo $html;
    }
}
