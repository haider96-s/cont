<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();

/**
 * Class WpfdControllerSearch
 */
class WpfdControllerSearch extends Controller
{
    /**
     * Search query
     *
     * @return void
     */
    public function query()
    {
        $modelConfig  = Model::getInstance('configfront');
        $searchConfig = $modelConfig->getSearchConfig();

        $filters = array();
        $q       = Utilities::getInput('q', 'POST', 'string');

        if (!empty($q)) {
            $filters['q'] = urlencode($q);
        }
        $catid = Utilities::getInput('catid', 'POST', 'string');
        if (!empty($catid)) {
            $filters['catid'] = $catid;
        }

        $ftags = Utilities::getInput('ftags', 'POST', 'none');
        if (is_array($ftags)) {
            $ftags = array_unique($ftags);
            $ftags = implode(',', $ftags);
        } else {
            $ftags = Utilities::getInput('ftags', 'POST', 'string');
        }

        if (!empty($ftags)) {
            $filters['ftags'] = $ftags;
        }
        $cfrom = Utilities::getInput('cfrom', 'POST', 'string');
        if (!empty($cfrom)) {
            $filters['cfrom'] = $cfrom;
        }
        $cto = Utilities::getInput('cto', 'POST', 'string');
        if (!empty($cto)) {
            $filters['cto'] = $cto;
        }
        $ufrom = Utilities::getInput('ufrom', 'POST', 'string');
        if (!empty($ufrom)) {
            $filters['ufrom'] = $ufrom;
        }
        $uto = Utilities::getInput('uto', 'POST', 'string');
        if (!empty($uto)) {
            $filters['uto'] = $uto;
        }
        $doSearch = false;
        if (!empty($filters)) {
            $doSearch = true;
        }
        exit();
    }

    /**
     * Get tags by category Id
     *
     * @return void
     */
    public function getTagByCatId()
    {
        global $wpdb;

        $catId = Utilities::getInput('catId', 'GET', 'string');
        $catType = Utilities::getInput('catType', 'GET', 'string');
        $modelTokens = $this->getModel('tokens');
        $token = $modelTokens->getOrCreateNew();
        $catSlug = Utilities::getInput('catSlug', 'GET', 'string');

        if (is_numeric($catId) && $catType === 'default') {
            $term  = get_term($catId, 'wpfd-category', OBJECT);

            if (!is_wp_error($term)) {
                $cats = get_term_children($term->term_id, 'wpfd-category');

                if (!is_wp_error($cats) && !empty($cats)) {
                    $cats[] = $term->term_id;
                    $terms = implode(',', esc_sql($cats));
                } else {
                    $terms = (string) esc_sql($term->term_id);
                }
                if (empty($terms)) {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
                // phpcs:disable WordPress.Security.EscapeOutput.NotPrepared -- Esc ready above
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
                // phpcs:enable

                if ($tags) {
                    $tagsArray = array();
                    foreach ($tags as $tag) {
                        if (isset($tag->count)) {
                            if (isset($tag->name) && strval($tag->name) === '') {
                                continue;
                            }
                            $tagsArray[] = array(
                                'name' => $tag->name,
                                'slug' => $tag->slug
                            );
                        }
                    }
                    wp_send_json(array('success' => true, 'tags' => $tagsArray), 200);
                }
            }

            wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
        } else {
            // Get tags for cloud
            if ($catType === 'googleDrive' && has_filter('wpfdAddonSearchCloud')) {
                $id_category = WpfdAddonHelper::getTermIdGoogleDriveByGoogleId($catId);
                $term        = get_term($id_category, 'wpfd-category', OBJECT);
                $googleTags = array();
                $existsTags = array();
                $files = apply_filters('wpfdAddonGetListGoogleDriveFile', $id_category, 'title', 'asc', $catSlug, $token);
                $catSlugs = '';

                if (!is_wp_error($term) && isset($term->slug) && isset($term->term_id)) {
                    $catSlugs = array();
                    $catSlugs[$term->term_id] = $term->slug;
                }

                $childTerms = get_term_children($id_category, 'wpfd-category');
                if (!empty($childTerms)) {
                    foreach ($childTerms as $key => $value) {
                        $childTerm = get_term($value, 'wpfd-category', OBJECT);
                        $catSlugs[$childTerm->term_id] = $childTerm->slug;
                    }
                }

                foreach ($catSlugs as $key => $catSlug) {
                    $files = apply_filters('wpfdAddonGetListGoogleDriveFile', $key, 'title', 'asc', $catSlug, $token);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                                $fileTags = explode(',', $file->file_tags);
                                foreach ($fileTags as $tag) {
                                    if (!in_array($tag, $googleTags) && !in_array($tag, $existsTags)) {
                                        if (strval($tag) === '') {
                                            continue;
                                        }

                                        $currentGoogleTag = array();
                                        $currentGoogleTag['name'] = esc_html($tag);
                                        $currentGoogleTag['slug'] = esc_attr($tag);
                                        $googleTags[] = $currentGoogleTag;
                                        $existsTags[] = esc_attr($tag);
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($googleTags)) {
                    wp_send_json(array('success' => true, 'tags' => $googleTags), 200);
                } else {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
            } elseif ($catType === 'googleTeamDrive' && has_filter('wpfdAddonSearchCloudTeamDrive')) {
                $id_category = WpfdAddonHelper::getTermIdByGoogleTeamDriveId($catId);
                $term        = get_term($id_category, 'wpfd-category', OBJECT);
                $googleTeamDriveTags = array();
                $existsTags = array();
                $files = apply_filters('wpfdAddonGetListGoogleTeamDriveFile', $id_category, 'title', 'asc', $catSlug, $token);
                $catSlugs = '';

                if (!is_wp_error($term) && isset($term->slug) && isset($term->term_id)) {
                    $catSlugs = array();
                    $catSlugs[$term->term_id] = $term->slug;
                }

                $childTerms = get_term_children($id_category, 'wpfd-category');
                if (!empty($childTerms)) {
                    foreach ($childTerms as $key => $value) {
                        $childTerm = get_term($value, 'wpfd-category', OBJECT);
                        $catSlugs[$childTerm->term_id] = $childTerm->slug;
                    }
                }

                foreach ($catSlugs as $key => $catSlug) {
                    $files = apply_filters('wpfdAddonGetListGoogleTeamDriveFile', $key, 'title', 'asc', $catSlug, $token);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                                $fileTags = explode(',', $file->file_tags);
                                foreach ($fileTags as $tag) {
                                    if (!in_array($tag, $googleTeamDriveTags) && !in_array($tag, $existsTags)) {
                                        if (isset($tag->name) && strval($tag->name) === '') {
                                            continue;
                                        }
                                        $currentGoogleTeamDriveTag = array();
                                        $currentGoogleTeamDriveTag['name'] = esc_html($tag);
                                        $currentGoogleTeamDriveTag['slug'] = esc_attr($tag);
                                        $googleTeamDriveTags[] = $currentGoogleTeamDriveTag;
                                        $existsTags[] = esc_attr($tag);
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($googleTeamDriveTags)) {
                    wp_send_json(array('success' => true, 'tags' => $googleTeamDriveTags), 200);
                } else {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
            } elseif ($catType === 'dropbox' && has_filter('wpfdAddonSearchDropbox')) {
                $id_category = WpfdAddonHelper::getTermIdDropBoxByDropBoxId($catId);
                $term        = get_term($id_category, 'wpfd-category', OBJECT);
                $dropBoxTags = array();
                $existsTags = array();
                $catSlugs = '';

                if (!is_wp_error($term) && isset($term->slug) && isset($term->term_id)) {
                    $catSlugs = array();
                    $catSlugs[$term->term_id] = $term->slug;
                }

                $childTerms = get_term_children($id_category, 'wpfd-category');
                if (!empty($childTerms)) {
                    foreach ($childTerms as $key => $value) {
                        $childTerm = get_term($value, 'wpfd-category', OBJECT);
                        $catSlugs[$childTerm->term_id] = $childTerm->slug;
                    }
                }

                foreach ($catSlugs as $key => $catSlug) {
                    $files = apply_filters('wpfdAddonGetListDropboxFile', $key, 'title', 'asc', $catSlug, $token);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                                $fileTags = explode(',', $file->file_tags);
                                foreach ($fileTags as $tag) {
                                    if (!in_array($tag, $dropBoxTags) && !in_array($tag, $existsTags)) {
                                        if (strval($tag) === '') {
                                            continue;
                                        }
                                        $currentDropBoxTag = array();
                                        $currentDropBoxTag['name'] = esc_html($tag);
                                        $currentDropBoxTag['slug'] = esc_attr($tag);
                                        $dropBoxTags[] = $currentDropBoxTag;
                                        $existsTags[] = esc_attr($tag);
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($dropBoxTags)) {
                    wp_send_json(array('success' => true, 'tags' => $dropBoxTags), 200);
                } else {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
            } elseif ($catType === 'onedrive' && has_filter('wpfdAddonSearchOneDrive', 'wpfdAddonSearchOneDrive')) {
                $odFileInfos = get_option('_wpfdAddon_onedrive_fileInfo', true);
                $id_category = WpfdAddonHelper::getTermIdOneDriveByOneDriveId($catId);
                $term        = get_term($id_category, 'wpfd-category', OBJECT);
                $odTags = array();
                $existsTags = array();
                $catSlugs = '';

                if (!is_wp_error($term) && isset($term->slug) && isset($term->term_id)) {
                    $catSlugs = array();
                    $catSlugs[$term->term_id] = $term->slug;
                }

                $childTerms = get_term_children($id_category, 'wpfd-category');
                if (!empty($childTerms)) {
                    foreach ($childTerms as $key => $value) {
                        $childTerm = get_term($value, 'wpfd-category', OBJECT);
                        $catSlugs[$childTerm->term_id] = $childTerm->slug;
                    }
                }

                $fileIDs = array();
                foreach ($catSlugs as $key => $catSlug) {
                    $files = apply_filters('wpfdAddonGetListOneDriveFile', $key, 'ordering', 'asc', $catSlug, $token);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if (isset($file->id) &&  $file->id !== '') {
                                $fileIDs[] = $file->id;
                            }
                        }
                    }
                }

                if (!empty($odFileInfos)) {
                    foreach ($odFileInfos as $odKey => $odValues) {
                        foreach ($odValues as $odId => $odValue) {
                            if (!in_array($odId, $fileIDs)) {
                                continue;
                            }

                            if (isset($odValue['file_tags']) && $odValue['file_tags'] !== '' && intval($odValue['state']) === 1) {
                                $odTagList = explode(',', $odValue['file_tags']);
                                foreach ($odTagList as $odTag) {
                                    if (strval($odTag) === '') {
                                        continue;
                                    }
                                    $currentOdTag = array();
                                    $currentOdTag['name'] = esc_html($odTag);
                                    $currentOdTag['slug'] = esc_attr($odTag);
                                    if (in_array($currentOdTag['name'], $existsTags)) {
                                        continue;
                                    }
                                    $existsTags[] = $currentOdTag['name'];
                                    $odTags[] = $currentOdTag;
                                }
                            }
                        }
                    }
                }

                if (!empty($odTags)) {
                    wp_send_json(array('success' => true, 'tags' => $odTags), 200);
                } else {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
            } elseif ($catType === 'onedrive_business' && has_filter('wpfdAddonSearchOneDriveBusiness')) {
                $id_category = WpfdAddonHelper::getTermIdOneDriveBusinessByOneDriveId($catId);
                $term        = get_term($id_category, 'wpfd-category', OBJECT);
                $oneDriveBusinessFileInfos = get_option('_wpfdAddon_onedrive_business_fileInfo', array());
                $catSlugs = '';

                if (!is_wp_error($term) && isset($term->slug) && isset($term->term_id)) {
                    $catSlugs = array();
                    $catSlugs[$term->term_id] = $term->slug;
                }

                $childTerms = get_term_children($id_category, 'wpfd-category');
                if (!empty($childTerms)) {
                    foreach ($childTerms as $key => $value) {
                        $childTerm = get_term($value, 'wpfd-category', OBJECT);
                        $catSlugs[$childTerm->term_id] = $childTerm->slug;
                    }
                }

                $fileIDs = array();
                foreach ($catSlugs as $key => $catSlug) {
                    $files = apply_filters('wpfdAddonGetListOneDriveBusinessFile', $key, 'title', 'asc', $catSlug, $token);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if (isset($file->id) &&  $file->id !== '') {
                                $fileIDs[] = $file->id;
                            }
                        }
                    }
                }

                $oneDriveBusinessTags = array();
                $existsTags = array();
                if (!empty($oneDriveBusinessFileInfos)) {
                    foreach ($oneDriveBusinessFileInfos as $odKey => $odValues) {
                        foreach ($odValues as $odId => $odValue) {
                            if (!in_array($odId, $fileIDs)) {
                                continue;
                            }

                            if (isset($odValue['file_tags']) && $odValue['file_tags'] !== '' && intval($odValue['state']) === 1) {
                                $odTagList = explode(',', $odValue['file_tags']);
                                foreach ($odTagList as $odTag) {
                                    if (strval($odTag) === '') {
                                        continue;
                                    }
                                    $currentOdTag = array();
                                    $currentOdTag['name'] = esc_html($odTag);
                                    $currentOdTag['slug'] = esc_attr($odTag);
                                    if (in_array($currentOdTag['name'], $existsTags)) {
                                        continue;
                                    }
                                    $existsTags[] = $currentOdTag['name'];
                                    $oneDriveBusinessTags[] = $currentOdTag;
                                }
                            }
                        }
                    }
                }

                if (!empty($oneDriveBusinessTags)) {
                    wp_send_json(array('success' => true, 'tags' => $oneDriveBusinessTags), 200);
                } else {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
            } elseif ($catType === 'aws' && has_filter('wpfdAddonSearchAws')) {
                $id_category = WpfdAddonHelper::getTermIdByAwsPath($catId);
                $term        = get_term($id_category, 'wpfd-category', OBJECT);
                $awsTags = array();
                $existsTags = array();
                $catSlugs = '';

                if (!is_wp_error($term) && isset($term->slug) && isset($term->term_id)) {
                    $catSlugs = array();
                    $catSlugs[$term->term_id] = $term->slug;
                }

                $childTerms = get_term_children($id_category, 'wpfd-category');
                if (!empty($childTerms)) {
                    foreach ($childTerms as $key => $value) {
                        $childTerm = get_term($value, 'wpfd-category', OBJECT);
                        $catSlugs[$childTerm->term_id] = $childTerm->slug;
                    }
                }

                foreach ($catSlugs as $key => $catSlug) {
                    $files = apply_filters('wpfdAddonGetListAwsFile', $key, 'title', 'asc', $catSlug, $token);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                                $fileTags = explode(',', $file->file_tags);
                                foreach ($fileTags as $tag) {
                                    if (!in_array($tag, $awsTags) && !in_array($tag, $existsTags)) {
                                        if (strval($tag) === '') {
                                            continue;
                                        }
                                        $currentAwsTag = array();
                                        $currentAwsTag['name'] = esc_html($tag);
                                        $currentAwsTag['slug'] = esc_attr($tag);
                                        $awsTags[] = $currentAwsTag;
                                        $existsTags[] = esc_attr($tag);
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($awsTags)) {
                    wp_send_json(array('success' => true, 'tags' => $awsTags), 200);
                } else {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
            } elseif ($catType === 'nextcloud' && has_filter('wpfdAddonSearchNextcloud')) {
                $id_category = $catId;
                $term        = get_term($id_category, 'wpfd-category', OBJECT);
                $nextcloudTags = array();
                $existsTags = array();
                $catSlugs = '';

                if (!is_wp_error($term) && isset($term->slug) && isset($term->term_id)) {
                    $catSlugs = array();
                    $catSlugs[$term->term_id] = $term->slug;
                }

                $childTerms = get_term_children($id_category, 'wpfd-category');
                if (!empty($childTerms)) {
                    foreach ($childTerms as $key => $value) {
                        $childTerm = get_term($value, 'wpfd-category', OBJECT);
                        $catSlugs[$childTerm->term_id] = $childTerm->slug;
                    }
                }

                foreach ($catSlugs as $key => $catSlug) {
                    $files = apply_filters('wpfdAddonGetListNextcloudFile', $key, 'title', 'asc', $catSlug, $token);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if (isset($file->file_tags) &&  $file->file_tags !== '' && intval($file->state) === 1) {
                                $fileTags = explode(',', $file->file_tags);
                                foreach ($fileTags as $tag) {
                                    if (!in_array($tag, $nextcloudTags) && !in_array($tag, $existsTags)) {
                                        if (strval($tag) === '') {
                                            continue;
                                        }
                                        $currentNextcloudTag = array();
                                        $currentNextcloudTag['name'] = esc_html($tag);
                                        $currentNextcloudTag['slug'] = esc_attr($tag);
                                        $nextcloudTags[] = $currentNextcloudTag;
                                        $existsTags[] = esc_attr($tag);
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($nextcloudTags)) {
                    wp_send_json(array('success' => true, 'tags' => $nextcloudTags), 200);
                } else {
                    wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
                }
            } else {
                wp_send_json(array('success' => false, 'message' => 'No tags in this category found!'), 200);
            }
        }
    }
}
