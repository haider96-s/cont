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

defined('ABSPATH') || die();

/**
 * Class WpfdModelCategory
 */
class WpfdModelCategory extends Model
{

    /**
     * Add new category by title
     *
     * @param string  $title    New category title
     * @param integer $parent   Category parent id
     * @param string  $position New category position
     *
     * @return boolean|integer
     */
    public function addCategory($title, $parent = 0, $position = 'end')
    {
        $title = trim(sanitize_text_field($title));
        if ($title === '') {
            return false;
        }

        $sanitizedTitle = sanitize_title($title);

        if ($position === 'end') {
            $inserted = wp_insert_term($title, 'wpfd-category', array('slug' => $sanitizedTitle, 'parent' => $parent));
            if (is_wp_error($inserted)) {
                return false;
            }
            $lastCats = get_terms(
                'wpfd-category',
                'orderby=term_group&order=DESC&hierarchical=0&hide_empty=0&parent=' . $parent . '&number=1'
            );
            if (is_array($lastCats) && count($lastCats)) {
                $this->updateTermOrder((int) $inserted['term_id'], $lastCats[0]->term_group + 1);
            }
        } else {
            // Update all terms term_group +1
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    'UPDATE ' . $wpdb->terms . ' as t SET t.term_group = t.term_group + 1 WHERE term_id IN (SELECT tt.term_id from ' . $wpdb->term_taxonomy . ' as tt WHERE tt.parent = %d)',
                    $parent
                )
            );

            $inserted = wp_insert_term($title, 'wpfd-category', array('slug' => $sanitizedTitle, 'parent' => $parent));
            if (is_wp_error($inserted)) {
                return false;
            }
            $this->updateTermOrder((int) $inserted['term_id'], 0);
        }

        return $inserted['term_id'];
    }


    /**
     * Update term order
     *
     * @param integer $term_id Term id to update
     * @param integer $order   New term order
     *
     * @return void
     */
    public function updateTermOrder($term_id, $order)
    {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . $wpdb->terms . ' SET term_group = %d WHERE term_id = %d',
                $order,
                $term_id
            )
        );
    }

    /**
     * Change order tree categories
     *
     * @param integer $pk       Current term id
     * @param integer $ref      Referent term id
     * @param integer $position Position of the order
     *
     * @return boolean|array|WP_Error
     */
    public function changeOrder($pk, $ref, $position)
    {
        $pk = (int) $pk;
        $ref = (int) $ref;
        $position = (string) $position;

        $pkTerm = get_term($pk, 'wpfd-category', OBJECT);

        if (is_wp_error($pkTerm)) {
            return false;
        }

        if ($ref === 0) { // Top root category and Position is after already
            if ((int) $pkTerm->parent === 0) { // Same category with root
                // Step 1: Update all other with new order
                $results = $this->getTermByParent(0);
                $id = 1;
                if ((int) $pkTerm->term_group === 0) {
                    $id = 0;
                    foreach ($results as $result) {
                        wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                        $id++;
                    }
                    return true;
                }

                foreach ($results as $result) {
                    if ((int) $result->term_id !== (int) $pkTerm->term_id) {
                        wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                        $id++;
                    }
                }
                // Step 2: Clear memory
                unset($results);
                unset($id);
                // Step 3: Update pk term
                wp_update_term($pkTerm->term_id, 'wpfd-category', array('parent' => 0, 'term_group' => 0));
                return true;
            } else { // Move from other parent
                // Step 1: Update categories in same cat with pk
                $results = $this->getTermByParent($pkTerm->parent);

                $id = 0;
                foreach ($results as $result) {
                    if ((int) $result->term_id !== (int) $pkTerm->term_id) {
                        wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                        $id++;
                    }
                }

                // Step 2: Clear memory
                unset($results);
                unset($id);

                // Step 3: Update order for root categories
                $results = $this->getTermByParent(0);
                foreach ($results as $result) {
                    wp_update_term($result->term_id, 'wpfd-category', array('term_group' => ($result->term_group + 1)));
                }
                unset($results);
                // Step 4: Update pk term_group and parent
                wp_update_term($pkTerm->term_id, 'wpfd-category', array('term_group' => 0, 'parent' => 0));

                return true;
            }
        } else { // Other ref category
            $refTerm = get_term($ref, 'wpfd-category', OBJECT);

            if (is_wp_error($refTerm)) {
                return false;
            }

            if ($position === 'after') {
                if ((int) $pkTerm->parent === (int) $refTerm->parent) { // Move from same category
                    // Step 1: Update term with term_group > ref order
                    $results = $this->getTermByParent($pkTerm->parent);

                    $terms = array();
                    foreach ($results as $result) {
                        $item = $result;
                        if ((int) $pkTerm->term_id !== (int) $result->term_id && $result->term_group > $pkTerm->term_group) {
                            $item->term_group = $result->term_group - 1;
                        }
                        $terms[] = $item;
                        unset($item);
                    }
                    unset($results);

                    // Prepare term_group order for insert pk
                    $terms2 = array();
                    $moveup = false;
                    foreach ($terms as $term) {
                        $item = $term;
                        if ((int) $pkTerm->term_group > (int) $refTerm->term_group) { // Move up ref not change
                            $moveup = true;
                            if ($term->term_group > $refTerm->term_group) {
                                $item->term_group = $term->term_group + 1;
                            }
                        } else { // Move down ref -1
                            if ((int) $term->term_group > ((int) $refTerm->term_group - 1)) {
                                $item->term_group = $term->term_group + 1;
                            }
                        }
                        $terms2[] = $item;
                        unset($item);
                    }
                    // Insert Pk
                    if ($moveup) {
                        $pkTerm->term_group = $refTerm->term_group + 1;
                    } else {
                        $pkTerm->term_group = $refTerm->term_group;
                    }


                    $terms2[] = $pkTerm;

                    foreach ($terms2 as $term) {
                        wp_update_term($term->term_id, 'wpfd-category', array('term_group' => $term->term_group));
                    }
                    unset($terms);
                    unset($term2);
                    unset($moveup);

                    return true;
                } else { // Move from other category
                    // Step 1: Update pk parent terms
                    $results = $this->getTermByParent($pkTerm->parent);

                    $id = 0;
                    foreach ($results as $result) {
                        if ((int) $pkTerm->term_id !== (int) $result->term_id) {
                            wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                            $id++;
                        }
                    }
                    unset($results);
                    unset($id);

                    // Step 2: Prepare ref terms
                    $results = $this->getTermByParent($refTerm->parent);

                    $id = $refTerm->term_group + 2;
                    foreach ($results as $result) {
                        if ($result->term_group > $refTerm->term_group) {
                            wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                            $id++;
                        }
                    }
                    unset($results);
                    unset($id);

                    // Step 3: Insert pk term to new position
                    wp_update_term($pkTerm->term_id, 'wpfd-category', array('parent' => $refTerm->parent, 'term_group' => ($refTerm->term_group + 1)));
                    return true;
                }
            } elseif ($position === 'first-child') {
                if ((int) $pkTerm->parent === (int) $refTerm->term_id) { // Move from same category - only move up
                    // Step 1: Update terms in same level except pk
                    $results = $this->getTermByParent($refTerm->term_id);

                    $id = 1;
                    if ((int) $pkTerm->term_group === 0) {
                        $id = 0;
                        foreach ($results as $result) {
                            wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                            $id++;
                        }
                        return true;
                    }
                    foreach ($results as $result) {
                        if ((int) $result->term_id !== (int) $pkTerm->term_id) {
                            wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                            $id++;
                        }
                    }
                    unset($results);
                    unset($id);

                    // Step 2: Update pk to top of current
                    wp_update_term($pkTerm->term_id, 'wpfd-category', array('parent' => $refTerm->term_id, 'term_group' => 0));
                    return true;
                } else { // Move from other category
                    // Step 1: Update same category with pk
                    $results = $this->getTermByParent($pkTerm->parent);
                    $id = 0;
                    foreach ($results as $result) {
                        if ((int) $result->term_id !== (int) $pkTerm->term_id) {
                            wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                            $id++;
                        }
                    }
                    unset($results);
                    unset($id);

                    // Step 2: Prepare target
                    $results = $this->getTermByParent($refTerm->term_id);
                    $id = 1;
                    foreach ($results as $result) { // Update all category to order after 0
                        wp_update_term($result->term_id, 'wpfd-category', array('term_group' => $id));
                        $id++;
                    }
                    unset($results);
                    unset($id);

                    // Step 3: Update pk term
                    wp_update_term($pkTerm->term_id, 'wpfd-category', array('parent' => $refTerm->term_id, 'term_group' => 0));
                    return true;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * Get Term with same parent id
     *
     * @param integer $parentId Term Parent id
     *
     * @return array|null|object
     */
    private function getTermByParent($parentId)
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT DISTINCT t.* FROM ' . $wpdb->terms . ' AS t 
                            INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt ON (tt.term_id = t.term_id)
                            WHERE tt.taxonomy = \'wpfd-category\' and tt.parent = %d
                            ORDER BY t.term_group ASC;',
                (int) $parentId
            )
        );

        return $results;
    }

    /**
     * Delete category
     *
     * @param integer $id_category Category id to delete
     *
     * @return boolean|integer|WP_Error True on success, false if term does not exist. Zero on attempted
     *                           deletion of default Category. WP_Error if the taxonomy does not exist.
     */
    public function delete($id_category)
    {
        //delete custom post
        $args    = array(
            'posts_per_page' => -1,
            'post_type'      => 'wpfd_file',
            'tax_query'      => array(
                array(
                    'taxonomy'         => 'wpfd-category',
                    'terms'            => (int) $id_category,
                    'include_children' => false
                )
            )
        );
        $results = get_posts($args);
        if (count($results)) {
            foreach ($results as $result) {
                wp_delete_post($result->ID, true);
            }
        }
        //before delete term
        $result            = get_term($id_category, 'wpfd-category');
        $list_category_own = array();
        if ($result && !empty($result)) {
            $description = json_decode($result->description, true);
        }
        $list_category_own[] = isset($description['category_own']) ? $description['category_own'] : 0;
        $list_category_own[] = isset($description['category_own_old']) ? $description['category_own_old'] : 0;

        //delete term
        $result = wp_delete_term($id_category, 'wpfd-category');
        if ($result) {
            $this->delCatInUserMeta($id_category, $list_category_own);
        }

        return $result;
    }

    /**
     * Get child categories
     *
     * @param integer $id Category id
     *
     * @return array
     */
    public function getChildren($id)
    {
        $results = array();
        $this->getChildrenRecursive($id, $results);

        return $results;
    }

    /**
     * Get children recursive
     *
     * @param integer $catid   Category id
     * @param array   $results Result to return
     *
     * @return void
     */
    public function getChildrenRecursive($catid, &$results)
    {
        if (!is_array($results)) {
            $results = array();
        }
        $categories = get_terms('wpfd-category', 'orderby=term_group&hierarchical=1&hide_empty=0&parent=' . $catid);
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $results[] = $category->term_id;
                $this->getChildrenRecursive($category->term_id, $results);
            }
        }
    }

    /**
     * Get category by ID
     *
     * @param integer $id Category id
     *
     * @return array|boolean
     */
    public function getCategory($id)
    {
        Application::getInstance('Wpfd');
        $result        = get_term($id, 'wpfd-category');
        $modelConfig   = $this->getInstance('config');
        $main_config   = $modelConfig->getConfig();
        $themeSettings = (int) $main_config['themesettings'] === 1 ? true : false;

        if (!empty($result) && !is_wp_error($result)) {
            $term_meta = get_option('taxonomy_' . $id);
            //$result->params = isset($term_meta['params'])? $term_meta['params']: array();
            if ($result->description === 'null' || $result->description === '') {
                $result->params = array();
            } else {
                $result->params = json_decode($result->description, true);
            }
            if (!isset($result->params['theme'])) {
                $result->params['theme'] = $main_config['defaultthemepercategory'];
            }
            $canview = 0;
            if (!isset($result->params['canview'])) {
                $result->params['canview'] = 0;
            } else {
                $canview = $result->params['canview'];
            }

            if (!isset($result->params['category_own'])) {
                $currentUserId                  = get_current_user_id();
                $categoryOwn                    = $currentUserId;
                $result->params['category_own'] = $currentUserId;
            } else {
                $categoryOwn = $result->params['category_own'];
            }

            $globalFilesOrdering = isset($main_config['global_files_ordering']) ? $main_config['global_files_ordering'] : 'title';
            $globalFilesOrderingDirection = isset($main_config['global_files_ordering_direction']) ? $main_config['global_files_ordering_direction'] : 'desc';
            $globalSubcategoriesOrdering = isset($main_config['global_subcategories_ordering']) ? $main_config['global_subcategories_ordering'] : 'customorder';
            $defaultParams = array(
                'order' => $globalFilesOrderingDirection,
                'orderby' => $globalFilesOrdering,
                'subcategoriesorderby' => $globalSubcategoriesOrdering,
                'roles' => array(),
                'private' => -1
            );
            /**
             * Filters allow setup default params for new category
             *
             * @param array Default values: order, orderby, roles, private
             *
             * @return array
             */
            $defaultParams = apply_filters('wpfd_default_category_params', $defaultParams);
            $ordering      = isset($result->params['ordering']) ? $result->params['ordering'] : $defaultParams['orderby'];
            $orderingdir   = isset($result->params['orderingdir']) ? $result->params['orderingdir'] : $defaultParams['order'];
            $subcategoriesOrdering = isset($result->params['subcategoriesordering']) ? $result->params['subcategoriesordering'] : $defaultParams['subcategoriesorderby'];
            $globalSubcategoriesOrderingAll = (isset($main_config['global_subcategories_ordering_all']) && intval($main_config['global_subcategories_ordering_all']) === 1) ? true : false;
            $defaultGlobalSubcategoriesOrdering = array('customorder', 'nameascending', 'namedescending');

            if ($globalSubcategoriesOrderingAll && in_array($globalSubcategoriesOrdering, $defaultGlobalSubcategoriesOrdering)) {
                $subcategoriesOrdering = $globalSubcategoriesOrdering;
            }

            if ((int) $main_config['catparameters'] === 0) {
                $categoryPassword = isset($result->params['category_password']) ? $result->params['category_password'] : '';
                $defaultSettings  = true;

                if ($themeSettings && isset($result->params['theme']) && $result->params['theme'] === $main_config['defaultthemepercategory']) {
                    $defaultSettings = false;
                }

                if ($defaultSettings) {
                    $result->params = $modelConfig->getThemeParams($main_config['defaultthemepercategory']);
                }

                $result->params['theme']             = $main_config['defaultthemepercategory'];
                $result->params['canview']           = $canview;
                $result->params['category_own']      = $categoryOwn;
                $result->params['category_password'] = $categoryPassword;
            }
            $result->cloudType = get_term_meta($result->term_id, 'cloudType', true);
            $result->roles       = isset($term_meta['roles']) ? (array) $term_meta['roles'] : $defaultParams['roles'];
            $result->access      = isset($term_meta['access']) ? (int) $term_meta['access'] : $defaultParams['private'];
            $result->ordering    = $ordering;
            $result->orderingdir = $orderingdir;
            $result->subcategoriesordering = $subcategoriesOrdering;

            // Load description
            $result->desc = get_term_meta($result->term_id, '_wpfd_description', true);
        } else {
            return false;
        }

        return $result;
    }

    /**
     * Save category param
     *
     * @param integer $id     Category id
     * @param array   $params Parameters
     *
     * @return boolean
     */
    public function saveParams($id, $params)
    {
        // Get list file ref to this category
        $result      = get_term($id, 'wpfd-category');
        $description = isset($result->description) ? json_decode($result->description, true) : array();

        if (!empty($description) && isset($description['refToFile'])) {
            $params['refToFile'] = $description['refToFile'];
        }

        if (!isset($params['category_own'])) {
            $categoryOwn = get_current_user_id();
            $params['category_own'] = $categoryOwn;
        }

        $datas = json_encode($params);
        if (isset($params['category_own']) && $params['category_own'] !== '' && wpfd_can_edit_permission()) {
            //$user_id = get_current_user_id();
            if ($params['category_own']) {
                $user_categories = get_user_meta($params['category_own'], 'wpfd_user_categories', true);
                if (is_array($user_categories)) {
                    if (!in_array($id, $user_categories)) {
                        $user_categories[] = $id;
                    }
                } else {
                    $user_categories = array();
                    $user_categories[] = $id;
                }
                if (isset($params['category_own_old']) && $params['category_own_old'] !== '' && $params['category_own_old'] !== $params['category_own']) {
                    $user_categories_old = get_user_meta($params['category_own_old'], 'wpfd_user_categories', true);
                    if (is_array($user_categories_old)) {
                        $user_categories_old = array_diff($user_categories_old, array($id));
                        $user_categories_old = array_values($user_categories_old);
                        update_user_meta($params['category_own_old'], 'wpfd_user_categories', $user_categories_old);
                    }
                }

                update_user_meta($params['category_own'], 'wpfd_user_categories', $user_categories);
            }
        } elseif (isset($params['category_own']) && empty($params['category_own'])
            && isset($params['category_own_old']) && $params['category_own_old'] !== '' && wpfd_can_edit_permission()) {
            $user_categories_old = get_user_meta($params['category_own_old'], 'wpfd_user_categories', true);
            if (is_array($user_categories_old)) {
                $user_categories_old = array_diff($user_categories_old, array($id));
                $user_categories_old = array_values($user_categories_old);
                update_user_meta($params['category_own_old'], 'wpfd_user_categories', $user_categories_old);
            } else {
                update_user_meta($params['category_own_old'], 'wpfd_user_categories', array());
            }
        }

        $updated = wp_update_term($id, 'wpfd-category', array('description' => $datas));
        if (is_wp_error($updated)) {
            return false;
        }

        return true;
    }

    /**
     * Save access, roles category
     *
     * @param integer $id     Category id
     * @param array   $params Params
     *
     * @return boolean
     */
    public function save($id, $params)
    {
//        $term_meta = get_option( "taxonomy_$id" );
        $visibility = $params['visibility'];

        $params['access'] = $visibility;
        if (!isset($params['roles'])) {
            $roles = array();
        } else {
            $roles = $params['roles'];
        }
        if ((int) $visibility === 1) {
            $params['roles'] = $roles;
        }
        update_option('taxonomy_' . $id, $params);

        return true;
    }

    /**
     * Save category by ID
     *
     * @param integer|WP_Term|object $id        If integer, term data will be fetched from the database, or from the cache if
     *                                          available. If stdClass object (as in the results of a database query), will apply
     *                                          filters and return a `WP_Term` object corresponding to the `$term` data. If `WP_Term`,
     *                                          will return `$term`.
     * @param integer                $fileId    File id
     * @param string                 $catFileId Category File id
     *
     * @return array|WP_Error Returns Term ID and Taxonomy Term ID
     */
    public function saveRefToFiles($id, $fileId, $catFileId)
    {
        $result = get_term($id, 'wpfd-category');

        if (is_wp_error($result)) {
            return array();
        }

        $description = json_decode($result->description, true);
        $listFileref = null;
        if (empty($description) || !isset($description['refToFile'])) {
            $description['refToFile'] = array();
        }

        $description = $this->checkListCatRef($description, $id);
        if (isset($description['refToFile'][$catFileId]) && !empty($description['refToFile'][$catFileId])) {
            $listFileref = $description['refToFile'][$catFileId];
        } else {
            $listFileref = array();
        }

        if (!in_array($fileId, $listFileref)) {
            array_push($listFileref, $fileId);
        }
        $description['refToFile'][$catFileId] = $listFileref;

        return wp_update_term($id, 'wpfd-category', array('description' => json_encode($description)));
    }

    /**
     * Check list category referent
     *
     * @param array                  $description    Category params
     * @param integer|WP_Term|object $current_cat_id Current category id
     *
     * @return array
     */
    public function checkListCatRef($description, $current_cat_id)
    {
        $listFileref              = $description['refToFile'];
        $description['refToFile'] = array();
        if (!empty($listFileref) && $listFileref) {
            foreach ($listFileref as $key => $lst) {
                $category = $this->getCategory($key);
                if ($category && !empty($category)) {
                    $lstFile                        = $this->checkListFiles($key, $lst, $current_cat_id);
                    $description['refToFile'][$key] = $lstFile;
                }
            }
        }

        return $description;
    }

    /**
     * Check list file referent category
     *
     * @param string                 $catid          Category id
     * @param array                  $listFileRef    List category ref
     * @param integer|WP_Term|object $current_cat_id Current category id
     *
     * @return array
     */
    public function checkListFiles($catid, $listFileRef, $current_cat_id)
    {
        $lstFile    = array();
        $file_model = $this->getInstance('file');
        if (!empty($listFileRef) && $listFileRef) {
            foreach ($listFileRef as $key => $val) {
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
                $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $catid);
                if (in_array($categoryFrom, wpfd_get_support_cloud())) {
                    /**
                     * Filters to get addon file info
                     *
                     * @param string File id
                     * @param string Category id
                     *
                     * @internal
                     *
                     * @ignore
                     *
                     * @return array
                     */
                    $file = apply_filters('wpfd_addon_get_file_info', $val, $catid, $categoryFrom);
                } else {
                    $file = $file_model->getFile($val);
                }

                if ($file && !empty($file) && isset($file['file_multi_category'])) {
                    $file_multi_category = (gettype($file['file_multi_category']) === 'string') ? explode(',', $file['file_multi_category']) : (array) $file['file_multi_category'];
                    if ((int) $catid === (int) $file['catid']) {
                        if (is_array($file_multi_category) && in_array($current_cat_id, $file_multi_category)) {
                            $lstFile[] = $val;
                        }
                    }
                }
            }
        }

        return $lstFile;
    }

    /**
     * Check move file and referent to category
     *
     * @param string|integer $currentCatId Current category id
     * @param array          $file         File
     * @param integer        $toCatId      Target category id
     *
     * @return array
     */
    public function checkMoveFileRefToCat($currentCatId, $file, $toCatId)
    {
        $this->getInstance('file');
        if (!empty($file) && isset($file['file_multi_category'])) {
            $file_multi_category = $file['file_multi_category'];
            if ($file_multi_category) {
                foreach ($file_multi_category as $key => $val) {
                    $cat_ref = (int) $val;
                    if ($cat_ref === (int) $toCatId) {
                        unset($file_multi_category[$key]);
                        $this->deleteRefToFiles($cat_ref, $file['ID'], $currentCatId);
                    } else {
                        $this->deleteRefToFiles($cat_ref, $file['ID'], $currentCatId);
                        $this->saveRefToFiles($cat_ref, $file['ID'], $toCatId);
                    }
                }
                $file['file_multi_category'] = $file_multi_category;
            }
            $file_multi_category_old         = implode(',', $file_multi_category);
            $file['file_multi_category_old'] = $file_multi_category_old;
        }
        $metadata                            = get_post_meta($file['ID'], '_wpfd_file_metadata', true);
        $metadata['file_multi_category']     = isset($file['file_multi_category']) ? $file['file_multi_category'] : array();
        $metadata['file_multi_category_old'] = isset($file['file_multi_category_old']) ? $file['file_multi_category_old'] : '';
        update_post_meta($file['ID'], '_wpfd_file_metadata', $metadata);

        return $file;
    }

    /**
     * Delete config reference to files
     *
     * @param string|integer $id        Category id
     * @param integer        $fileId    File id
     * @param string|integer $catFileId Category file id
     *
     * @return array|WP_Error
     */
    public function deleteRefToFiles($id, $fileId, $catFileId)
    {
        $result = get_term($id, 'wpfd-category');

        if (is_wp_error($result)) {
            return array();
        }

        $description = json_decode($result->description, true);
        $listFileref = null;
        if (!empty($description) && isset($description['refToFile'])) {
            if (isset($description['refToFile'][$catFileId])) {
                $lstfile = $description['refToFile'][$catFileId];
                $key     = array_search($fileId, $lstfile);
                if ($key !== false) {
                    unset($lstfile[$key]);
                }
                $description['refToFile'][$catFileId] = $lstfile;
            }
            if (empty($description['refToFile'][$catFileId])) {
                unset($description['refToFile'][$catFileId]);
            }
        }

        return wp_update_term($id, 'wpfd-category', array('description' => json_encode($description)));
    }

    /**
     * Save category title
     *
     * @param integer        $category Category id
     * @param string|boolean $title    Title
     *
     * @return boolean
     */
    public function saveTitle($category, $title)
    {
        if (false === $title) {
            return false;
        }
        // Avoid max character length in database
        if (mb_strlen($title) > 190) {
            $title = mb_substr($title, 0, 190);
        }

        $oldTerm = get_term($category, 'wpfd-category', OBJECT);
        $currentName = (is_object($oldTerm) && isset($oldTerm->name)) ? $oldTerm->name : '';

        $result = wp_update_term($category, 'wpfd-category', array(
            'name' => $title,
            'slug' => sanitize_title($title),
        ));
        if (is_wp_error($result)) { //try again with other slug
            $result = wp_update_term($category, 'wpfd-category', array(
                'name' => $title,
                'slug' => sanitize_title($title) . '-' . time(),
            ));
        }
        if (is_wp_error($result)) {
            return false;
        }

        // Update email per category
        $emailPerCategoryListing = get_option('wpfd_email_per_category_listing', array());

        if (is_null($emailPerCategoryListing) || !$emailPerCategoryListing) {
            $emailPerCategoryListing = array();
        }

        if (!empty($emailPerCategoryListing) && is_array($emailPerCategoryListing) && array_key_exists($category, $emailPerCategoryListing)) {
            $record = $emailPerCategoryListing[$category];
            $recordLocation = isset($record['location']) ? $record['location'] : '';
            $newRecordLocation = str_replace($currentName, $title, $recordLocation);

            $emailPerCategoryListing[$category]['location'] = $newRecordLocation;

            update_option('wpfd_email_per_category_listing', $emailPerCategoryListing);
        }

        return true;
    }

    /**
     * Save category description
     *
     * @param interger $categoryId Category id
     * @param string   $desc       Description
     *
     * @return boolean
     */
    public function saveDescription($categoryId, $desc)
    {
        if (!term_exists($categoryId, 'wpfd-category')) {
            return false;
        }

        // Update category description. Currently escape any html
        $result = update_term_meta($categoryId, '_wpfd_description', wp_kses($desc, 'post'));

        if (is_wp_error($result) || false === $result) {
            // False on failure or if the value passed to the function
            // is the same as the one that is already in the database.
            // WP_Error when term_id is ambiguous between taxonomies.
            return false;
        }

        return true;
    }

    /**
     * Save custom category color
     *
     * @param integer $categoryId Category id
     * @param string  $color      Category color (#ffffff)
     *
     * @return array
     */
    public function saveColor($categoryId, $color)
    {
        if (!term_exists($categoryId, 'wpfd-category')) {
            return false;
        }
        // Update category color
        update_term_meta($categoryId, '_wpfd_color', $color);
        // Update custom colors
        $default_colors = wpfd_default_folder_colors();
        $wpfd_colors = get_option('_wpfd_custom_folder_colors', array());
        if ($wpfd_colors === '') {
            $wpfd_colors = array();
        }
        if (is_array($wpfd_colors) && !in_array($color, $default_colors)) {
            array_unshift($wpfd_colors, $color);
            $wpfd_colors = array_slice(array_unique($wpfd_colors), 0, 5);
            update_option('_wpfd_custom_folder_colors', $wpfd_colors);
        }

        return $wpfd_colors;
    }
    /**
     * Delete category in user meta
     *
     * @param integer $cat_id            Category id
     * @param array   $list_category_own List categories own
     *
     * @return void
     */
    private function delCatInUserMeta($cat_id, $list_category_own)
    {
        $user_id = get_current_user_id();
        if (!in_array($user_id, $list_category_own)) {
            $list_category_own[] = $user_id;
        }

        foreach ($list_category_own as $key => $own) {
            $user_categories = (array) get_user_meta($own, 'wpfd_user_categories', true);
            if (is_array($user_categories)) {
                foreach ($user_categories as $uc_key => $uc_cat_id) {
                    if ((int) $cat_id === (int) $uc_cat_id) {
                        unset($user_categories[$uc_key]);
                    }
                }
                update_user_meta($own, 'wpfd_user_categories', $user_categories);
            }
        }
    }
}
