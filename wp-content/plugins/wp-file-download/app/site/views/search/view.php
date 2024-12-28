<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class WpfdViewSearch
 */
class WpfdViewSearch extends View
{
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
    public $dir;

    /**
     * Filters for searching
     *
     * @var array|mixed
     */
    public $filters;

    /**
     * Global search configuration
     *
     * @var array|mixed
     */
    public $searchConfig;

    /**
     * Files search
     *
     * @var array|mixed
     */
    public $files;

    /**
     * Theme for display search result
     *
     * @var string
     */
    public $theme;

    /**
     * Categories list
     *
     * @var array|mixed
     */
    public $categories;

    /**
     * Global settings
     *
     * @var array|mixed
     */
    public $config;

    /**
     * Display front search
     *
     * @param string|null $tpl Template name
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $filters = array();
        $q       = Utilities::getInput('q', 'POST', 'string');

        /**
         * Filter to replace search hyphen
         *
         * @param boolean Replace
         *
         * @return boolean
         *
         * @internal
         */
        $replaceHyphen = apply_filters('wpfdSearchReplaceHyphen', false);

        if ($replaceHyphen) {
            $q = preg_replace('/[-_]/', ' ', $q);
        }

        if (!empty($q)) {
            $filters['q'] = $q;
        }

        $catid = Utilities::getInput('catid', 'POST', 'string');
        if (!empty($catid)) {
            $filters['catid'] = $catid;
        }

        $exclude = Utilities::getInput('exclude', 'POST', 'string');
        if (!empty($exclude)) {
            $filters['exclude'] = $exclude;
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
        $ext = Utilities::getInput('type', 'POST', 'string');
        if (!empty($ext)) {
            $filters['ext'] = $ext;
        }
        $wfrom = Utilities::getInput('wfrom', 'POST', 'string');
        if (!empty($wfrom) && $wfrom !== 'NaN') {
            $filters['wfrom'] = $wfrom;
        }
        $wto = Utilities::getInput('wto', 'POST', 'string');
        if (!empty($wto) && $wto !== 'NaN') {
            $filters['wto'] = $wto;
        }

        $limit = Utilities::getInput('limit', 'POST', 'string');
        $limit = (string) apply_filters('wpfd_filter_search_limit_pagination', $limit);
        if (!empty($limit)) {
            $filters['limit'] = $limit;
        }

        $showPagination = Utilities::getInput('show_pagination', 'POST', 'string');
        if (!empty($showPagination)) {
            $filters['show_pagination'] = $showPagination;
        }

        $page = Utilities::getInput('paged', 'POST', 'string');
        $page = $page !== '' ? $page : 1;
        if (!empty($page)) {
            $filters['page'] = $page;
        }

        $searchCategoryId = Utilities::getInput('wpfd_search_in_category_id', 'POST', 'string');
        if (!empty($searchCategoryId)) {
            $filters['search_in_category_id'] = $searchCategoryId;
        }

        $theme_column = Utilities::getInput('theme_column', 'POST', 'string');
        if (!empty($theme_column)) {
            $filters['theme_column'] = $theme_column;
        }

        $this->ordering = Utilities::getInput('ordering', 'POST', 'string');
        $this->dir      = Utilities::getInput('dir', 'POST', 'string');
        $fileSuggestion = Utilities::getInput('make_file_suggestion', 'POST', 'string');
        $fileSuggestion = (isset($fileSuggestion) && intval($fileSuggestion) === 1) ? true : false;

        if ($fileSuggestion) {
            $filters['make_file_suggestion'] = true;
        }

        $this->filters = $filters;
        $modelConfig = $this->getModel('configfront');
        $searchConfig = $modelConfig->getSearchConfig();
        $this->searchConfig = $searchConfig;

        if (isset($searchConfig['search_cache']) && $searchConfig['search_cache'] === '1') {
            // Define cache file path and expiration time
            $uid = get_current_user_id();
            $cache_folder = WP_CONTENT_DIR . '/wp-file-download/cache/';
            $cache_file = '';
            if (!empty($filters)) {
                $search_key = implode('_', $filters);
                $cache_file_name = $uid.'-'.md5($search_key);
                $cache_file = $cache_folder.$cache_file_name.'.txt';
            }

            // $searchConfig['search_cache'] = '1';
            if (isset($searchConfig['cache_lifetime']) && is_numeric($searchConfig['cache_lifetime'])) {
                $expiration_time = $searchConfig['cache_lifetime'] * MINUTE_IN_SECONDS;
            } else {
                $expiration_time = 5 * MINUTE_IN_SECONDS;
            }

            if (! file_exists($cache_folder)) {
                mkdir($cache_folder); // Create the new folder if it does not exist
            }

            if (isset($searchConfig['cache_lifetime']) && is_numeric($searchConfig['cache_lifetime'])) {
                $expiration_time = $searchConfig['cache_lifetime'] * MINUTE_IN_SECONDS;
            } else {
                $expiration_time = 5 * MINUTE_IN_SECONDS;
            }

            if (file_exists($cache_file) && $cache_file !== '' && time() - filemtime($cache_file) < $expiration_time) {
                $file_handle = fopen($cache_file, 'r'); // Open the file for reading
                if ($file_handle) {
                    $file_contents = fread($file_handle, filesize($cache_file)); // Read the file contents
                    fclose($file_handle); // Close the file
                    $cache_contents = unserialize($file_contents);
                    $this->files = $cache_contents['files'];
                    $this->theme = $cache_contents['theme'];
                    $this->categories = $cache_contents['categories'];
                    $this->config = $cache_contents['config'];
                }

                parent::render($tpl);
                wp_die();
            }
        }

        $doSearch = false;
        if (!empty($filters)) {
            $doSearch = true;
        }

        $this->ordering    = Utilities::getInput('ordering', 'POST', 'string');
        $this->dir         = Utilities::getInput('dir', 'POST', 'string');
        $this->filters     = $filters;
        $modelCategories   = $this->getModel('categoriesfront');
        $model             = $this->getModel('search');
        $modelConfig       = $this->getModel('configfront');
        $this->categories  = $modelCategories->getLevelCategories();
        $theme             = Utilities::getInput('theme', 'POST', 'string');
        $themes            = $modelConfig->getThemes();
        $this->theme       = '';
        if (!empty($theme) && in_array($theme, $themes)) {
            $this->theme = $theme;
        }
        $this->files       = $model->searchfile($filters, $doSearch);

        /**
         * Filter to sort files based on the relevance of keywords.
         *
         * @param boolean
         *
         * @return boolean
         */
        $sortFileByRelevance = apply_filters('wpfd_search_results_orderby_relevance', true);
        if (!empty($this->files) && isset($this->filters['q']) && $sortFileByRelevance) {
            $keyword = $this->filters['q'];
            $self = $this;
            usort($this->files, function ($a, $b) use ($keyword, $self) {
                return $self->sortFileByRelevance($a, $b, $keyword);
            });
        }

        $this->config = $modelConfig->getGlobalConfig();

        if (isset($searchConfig['search_cache']) && $searchConfig['search_cache'] === '1') {
            $cache_contents = array(
                'files' => $this->files,
                'theme' => $this->theme,
                'categories' => $this->categories,
                'config' => $this->config,
            );
            if (! file_exists($cache_file)) {
                $new_file = fopen($cache_file, 'w'); // Create the new file if it does not exist
                fwrite($new_file, serialize($cache_contents)); // Write some text to the new file
                fclose($new_file); // Close the file
            } else {
                $new_content = serialize($cache_contents);
                $handle = fopen($cache_file, 'w'); // Open the file for writing
                fwrite($handle, $new_content); // Write the new content to the file
                fclose($handle); // Close the file handle
            }
        }

        parent::render($tpl);
        wp_die();
    }

    /**
     * Sort files by title and content that are most relevant to the keyword.
     * Prioritize by title > description.
     *
     * @param array  $a       The file with title and description.
     * @param array  $b       The file with title and description.
     * @param string $keyword The keyword.
     *
     * @return array  The sorted list of files.
     */
    public function sortFileByRelevance($a, $b, $keyword)
    {
        // Calculate relevance scores for title and description
        $relevanceA = $this->calculateRelevance($a, $keyword);
        $relevanceB = $this->calculateRelevance($b, $keyword);

        // Sort by title relevance first
        if ($relevanceA['title'] !== $relevanceB['title']) {
            return $relevanceB['title'] - $relevanceA['title'];
        }

        // If title relevance is the same, sort by description relevance
        return $relevanceB['content'] - $relevanceA['content'];
    }

    /**
     * Calculate the relevance score for a given file.
     *
     * @param array  $file    The file with a title and description.
     * @param string $keyword The keyword.
     *
     * @return array  The relevance scores for title and description.
     */
    public function calculateRelevance($file, $keyword)
    {
        $titleRelevance = $this->calculateKeywordRelevance($file->title, $keyword);

        $fileModel = $this->getModel('filefront');
        $ftsFile = $fileModel->getFtsFile($file->ID);
        $contentRelevance = 0;
        if (!empty($ftsFile)) {
            $contentRelevance = $this->calculateKeywordRelevance($ftsFile->content, $keyword);
        }

        $result = array(
            'title' => $titleRelevance,
            'content' => $contentRelevance
        );
        return $result;
    }

    /**
     * Calculate the relevance of an file to the keyword.
     *
     * @param string $text    The text (title or description) of the file.
     * @param array  $keyword The keyword.
     *
     * @return integer  The relevance score.
     */
    public function calculateKeywordRelevance($text, $keyword)
    {
        if (empty($keyword)) {
            return 0;
        }
        $text = strtolower($text);
        $relevance = substr_count($text, strtolower($keyword));

        return $relevance;
    }
}
