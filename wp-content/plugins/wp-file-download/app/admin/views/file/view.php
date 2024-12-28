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
use Joomunited\WPFramework\v1_0_6\Form;

defined('ABSPATH') || die();

/**
 * Class WpfdViewFile
 */
class WpfdViewFile extends View
{
    /**
     * File form
     *
     * @var array|mixed
     */
    public $form;

    /**
     * List tag of files
     *
     * @var array|mixed
     */
    public $allTagsFiles;

    /**
     * File id
     *
     * @var string|mixed
     */
    public $file_id;

    /**
     * File version
     *
     * @var string|mixed
     */
    public $versions;

    /**
     * Render view file
     *
     * @param null $tpl Template name
     *
     * @return void
     */
    public function render($tpl = null)
    {
        Application::getInstance('Wpfd');
        /* @var WpfdModelFile $model */
        $model      = $this->getModel('file');
        $idCategory = null;
        $fileId     = null;
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wpfd-security')) {
            wp_die(esc_html__('You don\'t have permission to perform this action!', 'wpfd'));
        }
        if (isset($_POST['fileInfo'][0])) {
            if (isset($_POST['fileInfo'][0]['fileId'])) {
                $fileId = esc_html($_POST['fileInfo'][0]['fileId']);
            }
            if (isset($_POST['fileInfo'][0]['catid'])) {
                $idCategory = (int) $_POST['fileInfo'][0]['catid'];
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $idCategory);
        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            /**
             * Filter to get addon file info
             *
             * @param string  File id
             * @param integer Category term id
             * @param string  Category from
             *
             * @return array
             *
             * @internal
             *
             * @ignore
             */
            if ($categoryFrom === 'aws') {
                $fileId = rawurldecode($fileId);
            }
            $datas = (array) apply_filters('wpfd_addon_get_file_info', $fileId, $idCategory, $categoryFrom);

            // List file multi category on cloud file
            if (isset($datas['file_multi_category']) && !is_array($datas['file_multi_category'])) {
                $datas['file_multi_category'] = (gettype($datas['file_multi_category']) === 'string') ? explode(',', $datas['file_multi_category'])
                 : (array) $datas['file_multi_category'];
            }
        } else {
            $datas = $model->getFile($fileId);
        }

        $layout = Utilities::getInput('layout', 'GET', 'string');
        if ($layout === 'versions') {
            $this->file_id = $datas['ID'];
            if ($categoryFrom === 'dropbox') {
                $this->versions = apply_filters('wpfdAddonDropboxVersionInfo', $datas['ID'], $idCategory);
            } elseif ($categoryFrom === 'googleDrive') {
                $this->versions = apply_filters('wpfdAddonGetListVersions', $datas['ID'], $idCategory);
            } elseif ($categoryFrom === 'googleTeamDrive') {
                $this->versions = apply_filters('wpfdAddonGoogleTeamDriveGetListVersions', $datas['ID'], $idCategory);
            } elseif ($categoryFrom === 'onedrive') {
                $this->versions = apply_filters('wpfdAddonOneDriveListVersions', $datas['ID'], $idCategory);
            } elseif ($categoryFrom === 'onedrive_business') {
                $this->versions = apply_filters('wpfdAddonOneDriveBusinessListVersions', $datas['ID'], $idCategory);
            } elseif ($categoryFrom === 'aws') {
                $this->versions = apply_filters('wpfdAddonAwsListVersions', $datas['ID'], $idCategory);
            } elseif ($categoryFrom === 'nextcloud') {
                $this->versions = apply_filters('wpfdAddonNextcloudListVersions', $datas['ID'], $idCategory);
            } else {
                $this->versions = $model->getVersions($datas['ID'], $idCategory);
            }

            parent::render($layout);
            wp_die();
        }
        // Fix wrong instance
        Application::getInstance('Wpfd');
        $form = new Form();
        $datas['title'] = isset($datas['post_title']) ? stripslashes(htmlspecialchars_decode(wp_slash_strings_only($datas['post_title']))) : stripslashes($datas['title']);

        /**
         * Filter to update data before load to fields
         *
         * @param array Data load to fields
         *
         * @return array
         */
        $datas = apply_filters('wpfd_file_params', $datas);
        if ($form->load('file', $datas)) {
            $this->form = $form->render('link');
        }

        $tags = get_terms('wpfd-tag', array(
            'orderby'    => 'count',
            'hide_empty' => 0,
        ));

        $cloudTags = get_option('wpfd_cloud_available_tags', array());

        if (is_array($cloudTags) && !empty($cloudTags)) {
            $cloudTags = array_map(function ($cloudTag) {
                return $cloudTag->value;
            }, $cloudTags);
        } else {
            $cloudTags = array();
        }

        if ($tags) {
            $allTagsFiles = array();
            foreach ($tags as $tag) {
                $allTagsFiles[] = '' . esc_html($tag->slug);
            }

            $allTagsFiles = array_merge($cloudTags, $allTagsFiles);
            $this->allTagsFiles = '["' . implode('","', $allTagsFiles) . '"]';
        } else {
            if (is_array($cloudTags) && !empty($cloudTags)) {
                $this->allTagsFiles = '["' . implode('","', $cloudTags) . '"]';
            } else {
                $this->allTagsFiles = '[]';
            }
        }

        parent::render($tpl);
        wp_die();
    }
}
