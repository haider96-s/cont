<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0W
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Utilities;

$app = Application::getInstance('Wpfd');

// Set default value for variables use in search template
$result_limit = Utilities::getInput('limit', 'POST', 'string');
$downloadSelected = ((int) $this->config['download_selected'] === 1) ? true : false;

if ($result_limit === '') {
    $result_limit = isset($this->searchConfig['file_per_page']) ? (int) $this->searchConfig['file_per_page'] : 15;
}

$variables         = array(
    'files'         => isset($this->files) ? $this->files : null,
    'ordering'      => isset($this->ordering) ? $this->ordering : 'type',
    'dir'           => isset($this->dir) ? $this->dir : 'asc',
    'args'          => isset($this->searchConfig) ? $this->searchConfig : array(),
    'config'        => isset($this->config) ? $this->config : null,
    'categories'    => isset($this->categories) ? $this->categories : array(),
    'filters'       => isset($this->filters) ? $this->filters : array(),
    'viewer'        => WpfdBase::loadValue($this->config, 'use_google_viewer', 'no'),
    'limit'         => $result_limit,
    'baseurl'       => $app->getBaseUrl(),
    'show_pagination' => (isset($this->filters['show_pagination']) && intval($this->filters['show_pagination']) === 1) ? true : false,
    'page'          => isset($this->filters['page']) ? intval($this->filters['page']) : 1,
    'theme_column'  => isset($this->theme_column) ? $this->theme_column : ''
);

$fileSuggestion = isset($variables['filters']['make_file_suggestion']) ? $variables['filters']['make_file_suggestion'] : false;
$currentCatId = isset($variables['filters']['catid']) ? $variables['filters']['catid'] : 0;
$showPagination = $variables['show_pagination'];
$wpfd_page = $variables['page'];
$searchResults = array();

if ($variables['viewer'] === 'lightbox') {
    wp_enqueue_script('wpfd-colorbox', plugins_url('app/site/assets/js/colorbox.init.js', WPFD_PLUGIN_FILE), array('jquery'), WPFD_VERSION, true);
}
if (isset($this->theme) && $this->theme !== '' && !$fileSuggestion) {
    if ($this->files !== null && is_array($this->files) && count($this->files) > 0) {
        $modelConfig = Model::getInstance('configfront');
        $params = $modelConfig->getConfig($this->theme);
        if ($this->theme === 'default') {
            $params['showfoldertree'] = 0;
            $params['showsubcategories'] = 0;
            $params['showcategorytitle'] = 0;
            $params['showbreadcrumb'] = 0;
            $params['download_popup'] = 0;
            $params['download_selected'] = $downloadSelected ? 1 : 0;
            $params['download_category'] = 0;
            $params['bgcolor'] = 'rgba(255, 255, 255, 0)';
        } else {
            $params[$this->theme . '_showfoldertree'] = 0;
            $params[$this->theme . '_showsubcategories'] = 0;
            $params[$this->theme . '_showcategorytitle'] = 0;
            $params[$this->theme . '_showbreadcrumb'] = 0;
            $params[$this->theme . '_download_popup'] = 0;
            $params[$this->theme . '_download_selected'] = 0;
            $params[$this->theme . '_download_category'] = 0;
            $params[$this->theme . '_bgcolor'] = 'rgba(255, 255, 255, 0)';
        }
        $this->config['download_selected'] = $downloadSelected ? 1 : 0;
        $this->config['download_category'] = 0;
        // Fix croptitle
        foreach ($this->files as &$file) {
            $file->crop_title = WpfdBase::cropTitle($this->config, $this->theme, $file->post_title);
        }

        if ($this->theme === 'table') {
            $columns = array('title', 'description', 'category', 'version', 'size', 'hits', 'date added', 'download');
            $theme_column = (isset($variables['filters']) && isset($variables['filters']['theme_column']) && $variables['filters']['theme_column'] !== '') ? $variables['filters']['theme_column'] : array();
            if (is_string($theme_column) && $theme_column !== '') {
                $theme_column = explode(',', $theme_column);
                if (!in_array('title', $theme_column)) {
                    array_unshift($theme_column, 'title');
                }
                $params['theme_column'] = $theme_column;
            }

            if (is_array($theme_column) && !empty($theme_column)) {
                foreach ($columns as $key => $value) {
                    $col = $value;
                    if ($value === 'date added') {
                        $col = 'dateadd';
                    }
                    if ($value === 'category') {
                        $col = 'categorytable';
                    }
                    if (in_array($value, $theme_column)) {
                        $params[$this->theme.'_show'.$col] = 1;
                    } else {
                        $params[$this->theme.'_show'.$col] = 0;
                    }
                }
            }
            $params['currentCatId'] = $currentCatId;
        }

        $category = new stdClass;
        $category->term_id = -1;
        $category->name = 'search';
        $category->slug = 'search';
        $options = array(
            'files' => $variables['files'],
            'category' => $category,
            'categories' => array(),
            'ordering' => $variables['ordering'],
            'orderingDirection' => $variables['dir'],
            'params' => $params,
            'tpl' => null
        );
        /**
         * Get theme instance follow priority
         *
         * 1. /wp-content/wp-file-download/themes
         * 2. /wp-content/uploads/wpfd-themes
         * 3. /wp-content/plugins/wp-file-download/app/site/themes
         */
        $themeInstance = wpfd_get_theme_instance($this->theme);

        // Set theme params, separator it to made sure theme can work well
        if (method_exists($themeInstance, 'setAjaxUrl')) {
            $themeInstance->setAjaxUrl(wpfd_sanitize_ajax_url(Application::getInstance('Wpfd')->getAjaxUrl()));
        }

        if (method_exists($themeInstance, 'setConfig')) {
            $themeInstance->setConfig($this->config);
        }

        if (method_exists($themeInstance, 'setPath')) {
            $themeInstance->setPath(Application::getInstance('Wpfd')->getPath());
        }

        if (method_exists($themeInstance, 'setThemeName')) {
            $themeInstance->setThemeName($this->theme);
        }

        $limit  = intval($variables['limit']);
        if ($showPagination && is_array($options['files']) && !empty($options['files'])) {
            $offset = ($wpfd_page - 1) * $limit;
            if ($offset < 0) {
                $offset = 0;
            }

            $searchResults = $options['files'];
            if ($limit > 0) {
                $options['files'] = array_slice($options['files'], $offset, $limit);
            }
        } else {
            if ($limit > 0) {
                $options['files'] = array_slice($options['files'], 0, $limit);
            }
        }

        echo $themeInstance->showCategory($options, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's OK
        echo esc_attr(wpfd_num($variables['limit']));

        if ($showPagination && is_array($searchResults) && !empty($searchResults)) {
            $limit = intval($variables['limit']);
            $total = (is_array($searchResults) && !empty($searchResults)) ? ceil(count($searchResults) / $limit) : 0;

            echo wpfd_search_pagination(// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                array(
                    'base' => '',
                    'format' => '',
                    'current' => max(1, $wpfd_page),
                    'total' => $total,
                    'source_search' => $currentCatId
                )
            );
        }
    } else { ?>
    <p class="text-center">
        <?php esc_html_e("Sorry, we haven't found anything that matches this search query", 'wpfd'); ?>
    </p>
    <?php }
} else {
    // Include search template
    wpfd_get_template('tpl-search-results.php', $variables);
}
