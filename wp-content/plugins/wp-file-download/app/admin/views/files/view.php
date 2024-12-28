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

defined('ABSPATH') || die();

/**
 * Class WpfdViewFiles
 */
class WpfdViewFiles extends View
{
    /**
     * Files category
     *
     * @var mixed
     */
    public $category;

    /**
     * Category type source
     *
     * @var mixed
     */
    public $category_type;

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
    public $orderingdir;

    /**
     * Files category params
     *
     * @var array
     */
    public $params;

    /**
     * Icon builder settings
     *
     * @var string
     */
    public $iconSet;

    /**
     * Display pagination for files
     *
     * @var boolean
     */
    public $fileListPagination;

    /**
     * Files per page
     *
     * @var integer
     */
    public $filesperpage;

    /**
     * Enable load more option
     *
     * @var boolean
     */
    public $loadMore;

    /**
     * Load more per page
     *
     * @var integer
     */
    public $loadMorePerPage;

    /**
     * Current page
     *
     * @var integer
     */
    public $page;

    /**
     * Files list
     *
     * @var array|mixed
     */
    public $files;

    /**
     * Check is search for files
     *
     * @var boolean
     */
    public $is_search;

    /**
     * Render view all files
     *
     * @param null $tpl Template name
     *
     * @return string
     */
    public function render($tpl = null)
    {
        $tpl = 'flex';
        $id_category = Utilities::getInt('id_category');
        if (empty($id_category)) {
            return '';
        }
        Application::getInstance('Wpfd');

        $model             = $this->getModel();
        $category_model    = $this->getModel('category');
        $orderCol          = Utilities::getInput('orderCol', 'GET', 'none');
        $orderDir          = Utilities::getInput('orderDir', 'GET', 'none');
        $this->category    = $category_model->getCategory($id_category);
        $this->category_type = 'wordpress';
        $this->ordering    = $orderCol !== null ? $orderCol : $this->category->ordering;
        $this->orderingdir = $orderDir !== null ? $orderDir : $this->category->orderingdir;
        $modelConfig       = $this->getModel('config');
        $this->params      = $modelConfig->getConfig();
        $this->iconSet     = isset($this->params['icon_set']) ? $this->params['icon_set'] : 'svg';

        $globalFilesOrdering = isset($this->params['global_files_ordering']) ? $this->params['global_files_ordering'] : 'title';
        $filesOrderingAll = (isset($this->params['global_files_ordering_all']) && intval($this->params['global_files_ordering_all']) === 1) ? true : false;
        $globalFilesOrderingDirection = isset($this->params['global_files_ordering_direction']) ? $this->params['global_files_ordering_direction'] : 'desc';
        $filesOrderingDirectionAll = (isset($this->params['global_files_ordering_direction_all']) && intval($this->params['global_files_ordering_direction_all']) === 1) ? true : false;
        if ($filesOrderingAll) {
            $this->ordering = $globalFilesOrdering;
        }
        if ($filesOrderingDirectionAll) {
            $this->orderingdir = $globalFilesOrderingDirection;
        }

        $fileListPagination       = (isset($this->params['admin_pagination']) && intval($this->params['admin_pagination']) === 1) ? true : false;
        $filesPerPage             = (isset($this->params['admin_pagination_number']) && intval($this->params['admin_pagination_number']) !== 0) ? intval($this->params['admin_pagination_number']) : 10;
        $loadMore                 = (isset($this->params['admin_load_more']) && intval($this->params['admin_load_more']) === 1) ? true : false;
        $loadMorePerPage          = (isset($this->params['admin_load_more_number']) && intval($this->params['admin_load_more_number']) !== 0) ? intval($this->params['admin_load_more_number']) : 10;
        $this->fileListPagination = $fileListPagination;
        $this->filesperpage       = $filesPerPage;
        $this->loadMore           = $loadMore;
        $this->loadMorePerPage    = $loadMorePerPage;
        $this->page               = Utilities::getInput('page', 'GET', 'none') ? intval(Utilities::getInput('page', 'GET', 'none')) : 1;

        $description       = json_decode($this->category->description, true);
        $lstAllFile        = null;
        if (!empty($description) && isset($description['refToFile'])) {
            if (isset($description['refToFile'])) {
                $listCatRef = $description['refToFile'];
                $lstAllFile = $this->getAllFileRef($model, $listCatRef, $this->ordering, $this->orderingdir);
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
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $id_category);
        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            /**
             * Filters to get files from cloud
             *
             * @param integer Category id
             * @param array   List file id
             *
             * @internal
             *
             * @return array
             */
            $this->files = apply_filters('wpfd_addon_get_files', $id_category, $categoryFrom, false);
            $this->category_type = $categoryFrom;
        } else {
            $this->files = $model->getFiles($id_category, $this->ordering, $this->orderingdir);
        }

        if ($lstAllFile && !empty($lstAllFile)) {
            $this->files = array_merge($lstAllFile, $this->files);
        }

        $reverse = strtoupper($this->orderingdir) === 'DESC' ? true : false;
        if (!empty($this->files)) {
            if ($this->ordering === 'size') {
                $this->files = wpfd_sort_by_property($this->files, 'size', 'ID', $reverse);
            } elseif ($this->ordering === 'version') {
                $this->files = wpfd_sort_by_property($this->files, 'versionNumber', 'ID', $reverse);
            } elseif ($this->ordering === 'hits') {
                $this->files = wpfd_sort_by_property($this->files, 'hits', 'ID', $reverse);
            } elseif ($this->ordering === 'ext') {
                $this->files = wpfd_sort_by_property($this->files, 'ext', 'ID', $reverse);
            } elseif ($this->ordering === 'description') {
                $this->files = wpfd_sort_by_property($this->files, 'description', 'ID', $reverse);
            } elseif ($this->ordering === 'title') {
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
                if ($reverse) {
                    if ($lowercaseSort) {
                        usort($this->files, function ($a, $b) {
                            return strnatcmp($b->post_title, $a->post_title);
                        });
                    } else {
                        usort($this->files, function ($a, $b) {
                            return strnatcmp(strtolower($b->post_title), strtolower($a->post_title));
                        });
                    }
                } else {
                    if ($lowercaseSort) {
                        usort($this->files, function ($a, $b) {
                            return strnatcmp($a->post_title, $b->post_title);
                        });
                    } else {
                        usort($this->files, function ($a, $b) {
                            return strnatcmp(strtolower($a->post_title), strtolower($b->post_title));
                        });
                    }
                }
            } elseif ($this->ordering === 'created_time') {
                if ($reverse) {
                    usort($this->files, array($this, 'cmpCreatedDesc'));
                } else {
                    usort($this->files, array($this, 'cmpCreated'));
                }
            } elseif ($this->ordering === 'modified_time') {
                if ($reverse) {
                    usort($this->files, array($this, 'cmpModifiedDesc'));
                } else {
                    usort($this->files, array($this, 'cmpModified'));
                }
            } elseif ($this->ordering === 'ordering' && !empty($lstAllFile)) {
                $orderingList = get_option('wpfd_custom_ordering_list', array());
                $customOrdering = isset($orderingList[$id_category]) ? (array) json_decode($orderingList[$id_category]) : array();
                $orderingFiles = array();
                $currentFiles = array();
                if (!empty($customOrdering) && !empty($this->files) && count($customOrdering) === count($this->files)) {
                    foreach ($this->files as $cFile) {
                        $currentFiles[$cFile->ID] = $cFile;
                    }
                    
                    foreach ($customOrdering as $index => $orderFile) {
                        if (array_key_exists((string) $orderFile, $currentFiles)) {
                            $orderingFiles[] = $currentFiles[$orderFile];
                        }
                    }

                    if (!empty($orderingFiles)) {
                        $this->files = $orderingFiles;
                        if ($reverse) {
                            $this->files = array_reverse($this->files);
                        }
                    }
                }
            }
        }

        if (Utilities::getInput('format', 'GET', 'string') === 'json') {
            $files = $this->files;
            $filesCount = count($files);
            $outputFiles = array();
            for ($i = 0; $i < $filesCount; $i++) {
                $tmpFile = new stdClass;
                $tmpFile->id = $files[$i]->ID;
                $tmpFile->name = html_entity_decode($files[$i]->post_title);
                $tmpFile->title = html_entity_decode($files[$i]->post_title);
                $tmpFile->ext = $files[$i]->ext;
                $tmpFile->created = $files[$i]->created_time;
                $tmpFile->size = WpfdHelperFile::bytesToSize($files[$i]->size);
                $tmpFile->term_id = $files[$i]->catid;
                $tmpFile->categoryFrom = apply_filters('wpfdAddonCategoryFrom', $files[$i]->catid);
                $outputFiles[] = $tmpFile;
            }
            wp_send_json_success($outputFiles);
            die();
        }

        parent::render($tpl);
    }
    /**
     * Get all file referent
     *
     * @param object $model       Files model
     * @param array  $listCatRef  List category
     * @param string $ordering    Ordering
     * @param string $orderingdir Ordering direction
     *
     * @return array
     */
    public function getAllFileRef($model, $listCatRef, $ordering, $orderingdir)
    {
        $lstAllFile = array();
        foreach ($listCatRef as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $lstFile    = $model->getFilesRef($key, $value, $ordering, $orderingdir);
                $lstAllFile = array_merge($lstFile, $lstAllFile);
            }
        }

        return $lstAllFile;
    }

    /**
     * Method compare Create date
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpCreated($a, $b)
    {
        return (strtotime($a->created_time) < strtotime($b->created_time)) ? -1 : 1;
    }

    /**
     * Method compare Create date desc
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpCreatedDesc($a, $b)
    {
        return (strtotime($a->created_time) > strtotime($b->created_time)) ? -1 : 1;
    }

    /**
     * Method compare Modified date
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpModified($a, $b)
    {
        return (strtotime($a->modified_time) < strtotime($b->modified_time)) ? -1 : 1;
    }

    /**
     * Method compare Modified date desc
     *
     * @param object $a First file object
     * @param object $b Second file object
     *
     * @return integer
     */
    private function cmpModifiedDesc($a, $b)
    {
        return (strtotime($a->modified_time) > strtotime($b->modified_time)) ? -1 : 1;
    }
}
