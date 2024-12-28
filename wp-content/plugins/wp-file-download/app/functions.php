<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

if (!function_exists('mb_str_split')) {
    /**
     * Unicode split string. Thanks to info@ensostudio.ru
     * https://www.php.net/manual/en/function.mb-str-split.php
     *
     * @param string  $string       String
     * @param integer $split_length Split length
     * @param string  $encoding     Encoding
     *
     * @return array|false|null
     */
    function mb_str_split($string, $split_length = 1, $encoding = null)
    {
        // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error,WordPress.Security.EscapeOutput.OutputNotEscaped -- it's OK
        if (null !== $string && !\is_scalar($string) && !(\is_object($string) && \method_exists($string, '__toString'))) {
            trigger_error('mb_str_split(): expects parameter 1 to be string, '.\gettype($string).' given', E_USER_WARNING);
            return null;
        }
        if (null !== $split_length && !\is_bool($split_length) && !\is_numeric($split_length)) {
            trigger_error('mb_str_split(): expects parameter 2 to be int, '.\gettype($split_length).' given', E_USER_WARNING);
            return null;
        }
        $split_length = (int) $split_length;
        if (1 > $split_length) {
            trigger_error('mb_str_split(): The length of each segment must be greater than zero', E_USER_WARNING);
            return false;
        }
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        } else {
            $encoding = (string) $encoding;
        }

        if (! in_array($encoding, mb_list_encodings(), true)) {
            static $aliases;
            if ($aliases === null) {
                $aliases = [];
                foreach (mb_list_encodings() as $encoding) {
                    $encoding_aliases = mb_encoding_aliases($encoding);
                    if ($encoding_aliases) {
                        foreach ($encoding_aliases as $alias) {
                            $aliases[] = $alias;
                        }
                    }
                }
            }
            if (! in_array($encoding, $aliases, true)) {
                trigger_error('mb_str_split(): Unknown encoding "'.$encoding.'"', E_USER_WARNING);
                return null;
            }
        }

        $result = [];
        $length = mb_strlen($string, $encoding);
        for ($i = 0; $i < $length; $i += $split_length) {
            $result[] = mb_substr($string, $i, $split_length, $encoding);
        }
        return $result;
        // phpcs: enable
    }
}
if (!function_exists('wpfd_get_content_dir')) {
    /**
     * Get folder for additional custom content
     *
     * @return string
     */
    function wpfd_get_content_dir()
    {
        /**
         * Filter to change the custom content directory. Default: 'wp-file-download'
         *
         * @param string The current custom content dir string.
         *
         * @return boolean
         */
        return apply_filters('wpfd_get_content_dir', 'wp-file-download');
    }
}

if (!function_exists('wpfd_sort_by_property')) {
    /**
     * Sort items by property
     *
     * @param array   $items    Files list
     * @param string  $property Property type
     * @param string  $key      Sort type
     * @param boolean $reverse  Reverse type
     *
     * @return array
     */
    function wpfd_sort_by_property(array $items, $property, $key = '', $reverse = false)
    {
        $sorted = array();
        $items_bk = $items;
        foreach ($items as $item) {
            $sorted[$item->$key] = $item->$property;
            $items_bk[$item->$key] = $item;
        }
        if ($reverse) {
            arsort($sorted);
        } else {
            asort($sorted);
        }
        $results = array();
        foreach ($sorted as $key2 => $value) {
            $results[] = $items_bk[$key2];
        }
        return $results;
    }
}

if (!function_exists('wpfd_getext')) {
    /**
     * Get extension of file
     *
     * @param string $file File name
     *
     * @return boolean|string
     */
    function wpfd_getext($file)
    {
        $dot = strrpos($file, '.') + 1;
        return substr($file, $dot);
    }
}
if (!function_exists('wpfd_remote_file_size')) {
    /**
     * Get size of file with remote url
     *
     * @param string $url Input url
     *
     * @return mixed|string
     */
    function wpfd_remote_file_size($url)
    {
        // Fix file size error on url have space
        $url = str_replace(' ', '%20', $url);
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_NOBODY => 1,
        ));
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_exec($ch);
        $clen = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        if (!$clen || ($clen <= 0)) {
            return esc_html__('N/A', 'wpfd');
        }
        return $clen;
    }
}

if (!function_exists('wpfd_num')) {
    /**
     * Display select pages number
     *
     * @param integer $paged Page number
     *
     * @return void
     */
    function wpfd_num($paged = 5)
    {
        ?>
        <div class="wpfd-num">
            <?php
            $p_number = array(5, 10, 15, 20, 25, 30, 50, 100, -1);
            ?>
            <div class="limit pull-right">
                <?php esc_html_e('Display #', 'wpfd'); // phpcs:ignore ?>
                <select title="" id="limit" name="limit" class="" size="1">
                    <?php
                    foreach ($p_number as $num) {
                        $selected = $num === (int)$paged ? ' selected="selected"' : '';
                        ?>
                        <option value="<?php echo $num; // phpcs:ignore ?>"
                            <?php echo $selected; // phpcs:ignore ?>><?php echo $num === -1 ? esc_html__('All', 'wpfd') : $num; ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('wpfd_select')) {
    /**
     * Render a select html
     *
     * @param array   $options  Options array
     * @param string  $name     Name
     * @param string  $select   Select
     * @param string  $attr     Attr
     * @param boolean $disabled Disable
     *
     * @return string
     */
    function wpfd_select(array $options = array(), $name = '', $select = '', $attr = '', $disabled = false)
    {
        $html = '';
        $html .= '<select';
        if ($name !== '') {
            $html .= ' name="' . esc_attr($name) . '"';
        }
        if ($attr !== '') {
            $html .= ' ' . $attr;
        }
        $html .= '>';
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $select_option = '';
                if (is_array($select)) {
                    if (in_array($key, $select)) {
                        $select_option = 'selected="selected"';
                    } elseif ((string)$key === (string)$disabled) {
                        $select_option = disabled($disabled, $key, false);
                    } else {
                        $select_option = '';
                    }
                } else {
                    if ($disabled) {
                        $select_option = disabled($disabled, $key, false);
                    } else {
                        $select_option = selected($select, $key, false);
                    }
                }
                $html .= '<option value="' . esc_attr($key) . '" ' . $select_option . '>' . $value . '</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }
}
if (!function_exists('wpfd_pagination')) {
    /**
     * Display a custom pagination
     *
     * @param array  $args      Options args
     * @param string $form_name Form name
     *
     * @return array|string|boolean
     */
    function wpfd_pagination(array $args = array(), $form_name = '')
    {
        global $wp_query, $wp_rewrite;
        // Setting up default values based on the current URL.
        $pagenum_link = html_entity_decode(get_pagenum_link());
        $url_parts = explode('?', $pagenum_link);
        // Get max pages and current page out of the current query, if available.
        $total = isset($wp_query->max_num_pages) ? $wp_query->max_num_pages : 1;
        $current = get_query_var('paged') ? intval(get_query_var('paged')) : 1;
        // Append the format placeholder to the base URL.
        $pagenum_link = trailingslashit($url_parts[0]) . '%_%';
        // URL base depends on permalink settings.
        $pagination_base = user_trailingslashit($wp_rewrite->pagination_base . '/%#%', 'paged');
        $format = $wp_rewrite->using_index_permalinks() && !strpos($pagenum_link, 'index.php') ? 'index.php/' : '';
        $format .= $wp_rewrite->using_permalinks() ? $pagination_base : '?paged=%#%';

        $defaults = array(
            'base' => $pagenum_link,
            // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
            'format' => $format,
            // ?page=%#% : %#% is replaced by the page number
            'total' => $total,
            'current' => $current,
            'show_all' => false,
            'prev_next' => true,
            'prev_text' => esc_html__('&laquo; Previous', 'wpfd'),
            'next_text' => esc_html__('Next &raquo;', 'wpfd'),
            'end_size' => 1,
            'mid_size' => 2,
            'type' => 'plain',
            'add_args' => array(),
            // array of query args to add
            'add_fragment' => '',
            'before_page_number' => '',
            'after_page_number' => ''
        );

        $args = wp_parse_args($args, $defaults);
        if (!is_array($args['add_args'])) {
            $args['add_args'] = array();
        }
        // Merge additional query vars found in the original URL into 'add_args' array.
        if (isset($url_parts[1])) {
            // Find the format argument.
            $format = explode('?', str_replace('%_%', $args['format'], $args['base']));
            $format_query = isset($format[1]) ? $format[1] : '';
            wp_parse_str($format_query, $format_args);
            // Find the query args of the requested URL.
            wp_parse_str($url_parts[1], $url_query_args);
            // Remove the format argument from the array of query arguments, to avoid overwriting custom format.
            foreach ($format_args as $format_arg => $format_arg_value) {
                unset($url_query_args[$format_arg]);
            }
            $args['add_args'] = array_merge($args['add_args'], urlencode_deep($url_query_args));
        }
        // Who knows what else people pass in $args
        $total = (int)$args['total'];
        if ($total < 2) {
            return false;
        }
        $current = (int)$args['current'];
        $end_size = (int)$args['end_size']; // Out of bounds?  Make it the default.
        if ($end_size < 1) {
            $end_size = 1;
        }
        $mid_size = (int)$args['mid_size'];
        if ($mid_size < 0) {
            $mid_size = 2;
        }
        $add_args = $args['add_args'];
        $r = '';
        $page_links = array();
        $dots = false;
        if ($args['prev_next'] && $current && 1 < $current) :
            $link = str_replace('%_%', 2 === $current ? '' : $args['format'], $args['base']);
            $link = str_replace('%#%', $current - 1, $link);
            if ($add_args) {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];
            /**
             * Filter the paginated links for the given archive pages.
             *
             * @since 3.0.0
             *
             * param string $link The paginated link URL.
             */
            $page_link = "<a class='prev page-numbers' onclick='document." . esc_attr($form_name) . '.paged.value=';
            $page_link .= ($current - 1) . '; document.' . esc_attr($form_name) . ".submit();'>" . $args['prev_text'] . '</a>';
            $page_links[] = $page_link;
        endif;
        for ($n = 1; $n <= $total; $n++) :
            if ($n === $current) :
                $page_link = "<span class='page-numbers current'>" . $args['before_page_number'];
                $page_link .= number_format_i18n($n) . $args['after_page_number'] . '</span>';
                $page_links[] = $page_link;
                $dots = true;
            else :
                if ($args['show_all'] ||
                    ($n <= $end_size || ($current && $n >= $current - $mid_size && $n <= $current + $mid_size)
                        || $n > $total - $end_size)) :
                    $link = str_replace('%_%', 1 === $n ? '' : $args['format'], $args['base']);
                    $link = str_replace('%#%', $n, $link);
                    if ($add_args) {
                        $link = add_query_arg($add_args, $link);
                    }
                    $link .= $args['add_fragment'];
                    /**
                     * This filter is documented in wp-includes/general-template.php
                     */
                    $page_link = "<a class='page-numbers' onclick='document." . esc_attr($form_name) . '.paged.value=';
                    $page_link .= $n . '; document.' . esc_attr($form_name) . ".submit();'>" . $args['before_page_number'];
                    $page_link .= number_format_i18n($n) . $args['after_page_number'] . '</a>';
                    $page_links[] = $page_link;
                    $dots = true;
                elseif ($dots && !$args['show_all']) :
                    $page_links[] = '<span class="page-numbers dots">' . esc_html__('&hellip;', 'wpfd') . '</span>';
                    $dots = false;
                endif;
            endif;
        endfor;
        if ($args['prev_next'] && $current && ($current < $total || -1 === $total)) :
            $link = str_replace('%_%', $args['format'], $args['base']);
            $link = str_replace('%#%', $current + 1, $link);
            if ($add_args) {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            /**
             * This filter is documented in wp-includes/general-template.php
             */
            $page_link = "<a class='next page-numbers' onclick='document." . esc_attr($form_name) . '.paged.value=';
            $page_link .= ($current + 1) . '; document.' . esc_attr($form_name) . ".submit();'>" . $args['next_text'] . '</a>';
            $page_links[] = $page_link;
        endif;
        switch ($args['type']) {
            case 'array':
                return $page_links;
            case 'list':
                $r .= "<ul class='page-numbers'>\n\t<li>";
                $r .= join("</li>\n\t<li>", $page_links);
                $r .= "</li>\n</ul>\n";
                break;
            default:
                $r = join("\n", $page_links);
                break;
        }
        return $r;
    }
}

if (!function_exists('wpfd_category_pagination')) {
    /**
     * Display a custom pagination
     *
     * @param array  $args      Option args
     * @param string $form_name Form name
     *
     * @return array|string|boolean
     */
    function wpfd_category_pagination(array $args = array(), $form_name = '')
    {
        global $wp_query, $wp_rewrite;
        // Setting up default values based on the current URL.
        $pagenum_link = html_entity_decode(get_pagenum_link());
        $url_parts = explode('?', $pagenum_link);
        // Get max pages and current page out of the current query, if available.
        $total = isset($wp_query->max_num_pages) ? $wp_query->max_num_pages : 1;
        $current = get_query_var('paged') ? intval(get_query_var('paged')) : 1;
        // Append the format placeholder to the base URL.
        $pagenum_link = trailingslashit($url_parts[0]) . '%_%';
        // URL base depends on permalink settings.
        $pagination_base = user_trailingslashit($wp_rewrite->pagination_base . '/%#%', 'paged');
        $format = $wp_rewrite->using_index_permalinks() && !strpos($pagenum_link, 'index.php') ? 'index.php/' : '';
        $format .= $wp_rewrite->using_permalinks() ? $pagination_base : '?paged=%#%';
        $defaults = array(
            'base' => $pagenum_link,
            // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
            'format' => $format,
            // ?page=%#% : %#% is replaced by the page number
            'total' => $total,
            'current' => $current,
            'show_all' => false,
            'prev_next' => true,
            'prev_text' => esc_html__('&laquo; Previous', 'wpfd'),
            'next_text' => esc_html__('Next &raquo;', 'wpfd'),
            'end_size' => 1,
            'mid_size' => 2,
            'type' => 'plain',
            'add_args' => array(),
            // array of query args to add
            'add_fragment' => '',
            'before_page_number' => '',
            'after_page_number' => ''
        );
        $args = wp_parse_args($args, $defaults);
        if (!is_array($args['add_args'])) {
            $args['add_args'] = array();
        }
        // Merge additional query vars found in the original URL into 'add_args' array.
        if (isset($url_parts[1])) {
            // Find the format argument.
            $format = explode('?', str_replace('%_%', $args['format'], $args['base']));
            $format_query = isset($format[1]) ? $format[1] : '';
            wp_parse_str($format_query, $format_args);
            // Find the query args of the requested URL.
            wp_parse_str($url_parts[1], $url_query_args);
            // Remove the format argument from the array of query arguments, to avoid overwriting custom format.
            foreach ($format_args as $format_arg => $format_arg_value) {
                unset($url_query_args[$format_arg]);
            }
            $args['add_args'] = array_merge($args['add_args'], urlencode_deep($url_query_args));
        }
        // Who knows what else people pass in $args
        $total = (int)$args['total'];
        if ($total < 2) {
            return false;
        }
        $current = (int)$args['current'];
        $end_size = (int)$args['end_size']; // Out of bounds?  Make it the default.
        if ($end_size < 1) {
            $end_size = 1;
        }
        $mid_size = (int)$args['mid_size'];
        if ($mid_size < 0) {
            $mid_size = 2;
        }
        $add_args = $args['add_args'];
        $r = '';
        $page_links = array();
        $dots = false;
        if (isset($args['sourcecat']) && intval($args['sourcecat']) === 0) {
            $args['sourcecat'] = 'all_0';
        }
        if ($args['prev_next'] && $current && 1 < $current) :
            $link = str_replace('%_%', 2 === $current ? '' : $args['format'], $args['base']);
            $link = str_replace('%#%', $current - 1, $link);
            if ($add_args) {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            /**
             * Filter the paginated links for the given archive pages.
             *
             * @since 3.0.0
             *
             * param string $link The paginated link URL.
             */
            $page_link = "<a class='prev page-numbers' data-page='" . ($current - 1) . "' data-sourcecat='" . $args['sourcecat'] . "'>";
            $page_link .= $args['prev_text'] . '</a>';
            $page_links[] = $page_link;
        endif;
        for ($n = 1; $n <= $total; $n++) :
            if ($n === $current) :
                $page_link = "<span class='page-numbers current'>" . $args['before_page_number'];
                $page_link .= number_format_i18n($n) . $args['after_page_number'] . '</span>';
                $page_links[] = $page_link;
                $dots = true;
            else :
                if ($args['show_all'] ||
                    ($n <= $end_size || ($current && $n >= $current - $mid_size && $n <= $current + $mid_size) ||
                        $n > $total - $end_size)) :
                    $link = str_replace('%_%', 1 === $n ? '' : $args['format'], $args['base']);
                    $link = str_replace('%#%', $n, $link);
                    if ($add_args) {
                        $link = add_query_arg($add_args, $link);
                    }
                    $link .= $args['add_fragment'];

                    /**
                     * This filter is documented in wp-includes/general-template.php
                     */
                    $page_link = "<a class='page-numbers' data-page='" . $n . "' data-sourcecat='" . $args['sourcecat'] . "'>" . $args['before_page_number'];
                    $page_link .= number_format_i18n($n) . $args['after_page_number'] . '</a>';
                    $page_links[] = $page_link;
                    $dots = true;
                elseif ($dots && !$args['show_all']) :
                    $page_links[] = '<span class="page-numbers dots">' . esc_html__('&hellip;', 'wpfd') . '</span>';
                    $dots = false;
                endif;
            endif;
        endfor;
        if ($args['prev_next'] && $current && ($current < $total || -1 === $total)) :
            $link = str_replace('%_%', $args['format'], $args['base']);
            $link = str_replace('%#%', $current + 1, $link);
            if ($add_args) {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            /**
             * This filter is documented in wp-includes/general-template.php
             */
            $page_link = "<a class='next page-numbers' data-page='" . ($current + 1) . "' data-sourcecat='" . $args['sourcecat'] . "'>";
            $page_link .= $args['next_text'] . '</a>';
            $page_links[] = $page_link;
        endif;
        switch ($args['type']) {
            case 'array':
                return $page_links;
            case 'list':
                $r .= "<ul class='page-numbers'>\n\t<li>";
                $r .= join("</li>\n\t<li>", $page_links);
                $r .= "</li>\n</ul>\n";
                break;
            default:
                $r .= "<div class='wpfd-pagination'>\n\t";
                $r .= join("\n", $page_links);
                $r .= "\n</div>\n";
                break;
        }
        return $r;
    }
}

if (!function_exists('wpfd_search_pagination')) {
    /**
     * Display a custom pagination
     *
     * @param array  $args      Option args
     * @param string $form_name Form name
     *
     * @return array|string|boolean
     */
    function wpfd_search_pagination(array $args = array(), $form_name = '')
    {
        global $wp_query, $wp_rewrite;
        // Setting up default values based on the current URL.
        $pagenum_link = html_entity_decode(get_pagenum_link());
        $url_parts = explode('?', $pagenum_link);
        // Get max pages and current page out of the current query, if available.
        $total = isset($wp_query->max_num_pages) ? $wp_query->max_num_pages : 1;
        $current = get_query_var('paged') ? intval(get_query_var('paged')) : 1;
        // Append the format placeholder to the base URL.
        $pagenum_link = trailingslashit($url_parts[0]) . '%_%';
        // URL base depends on permalink settings.
        $pagination_base = user_trailingslashit($wp_rewrite->pagination_base . '/%#%', 'paged');
        $format = $wp_rewrite->using_index_permalinks() && !strpos($pagenum_link, 'index.php') ? 'index.php/' : '';
        $format .= $wp_rewrite->using_permalinks() ? $pagination_base : '?paged=%#%';
        $defaults = array(
            'base' => $pagenum_link,
            // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
            'format' => $format,
            // ?page=%#% : %#% is replaced by the page number
            'total' => $total,
            'current' => $current,
            'show_all' => false,
            'prev_next' => true,
            'prev_text' => esc_html__('&laquo; Previous', 'wpfd'),
            'next_text' => esc_html__('Next &raquo;', 'wpfd'),
            'end_size' => 1,
            'mid_size' => 2,
            'type' => 'plain',
            'add_args' => array(),
            // array of query args to add
            'add_fragment' => '',
            'before_page_number' => '',
            'after_page_number' => ''
        );
        $args = wp_parse_args($args, $defaults);
        if (!is_array($args['add_args'])) {
            $args['add_args'] = array();
        }
        // Merge additional query vars found in the original URL into 'add_args' array.
        if (isset($url_parts[1])) {
            // Find the format argument.
            $format = explode('?', str_replace('%_%', $args['format'], $args['base']));
            $format_query = isset($format[1]) ? $format[1] : '';
            wp_parse_str($format_query, $format_args);
            // Find the query args of the requested URL.
            wp_parse_str($url_parts[1], $url_query_args);
            // Remove the format argument from the array of query arguments, to avoid overwriting custom format.
            foreach ($format_args as $format_arg => $format_arg_value) {
                unset($url_query_args[$format_arg]);
            }
            $args['add_args'] = array_merge($args['add_args'], urlencode_deep($url_query_args));
        }
        // Who knows what else people pass in $args
        $total = (int)$args['total'];
        if ($total < 2) {
            return false;
        }
        $current = (int)$args['current'];
        $end_size = (int)$args['end_size']; // Out of bounds?  Make it the default.
        if ($end_size < 1) {
            $end_size = 1;
        }
        $mid_size = (int)$args['mid_size'];
        if ($mid_size < 0) {
            $mid_size = 2;
        }
        $add_args = $args['add_args'];
        $r = '';
        $page_links = array();
        $dots = false;
        if (isset($args['source_search']) && intval($args['source_search']) === 0) {
            $args['source_search'] = 'all_0';
        }
        if ($args['prev_next'] && $current && 1 < $current) :
            $link = str_replace('%_%', 2 === $current ? '' : $args['format'], $args['base']);
            $link = str_replace('%#%', $current - 1, $link);
            if ($add_args) {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            /**
             * Filter the paginated links for the given archive pages.
             *
             * @since 3.0.0
             *
             * param string $link The paginated link URL.
             */
            $page_link = "<a class='prev page-numbers' data-page='" . ($current - 1) . "' data-source_search='" . $args['source_search'] . "'>";
            $page_link .= $args['prev_text'] . '</a>';
            $page_links[] = $page_link;
        endif;
        for ($n = 1; $n <= $total; $n++) :
            if ($n === $current) :
                $page_link = "<span class='page-numbers current'>" . $args['before_page_number'];
                $page_link .= number_format_i18n($n) . $args['after_page_number'] . '</span>';
                $page_links[] = $page_link;
                $dots = true;
            else :
                if ($args['show_all'] ||
                    ($n <= $end_size || ($current && $n >= $current - $mid_size && $n <= $current + $mid_size) ||
                        $n > $total - $end_size)) :
                    $link = str_replace('%_%', 1 === $n ? '' : $args['format'], $args['base']);
                    $link = str_replace('%#%', $n, $link);
                    if ($add_args) {
                        $link = add_query_arg($add_args, $link);
                    }
                    $link .= $args['add_fragment'];

                    /**
                     * This filter is documented in wp-includes/general-template.php
                     */
                    $page_link = "<a class='page-numbers' data-page='" . $n . "' data-source_search='" . $args['source_search'] . "'>" . $args['before_page_number'];
                    $page_link .= number_format_i18n($n) . $args['after_page_number'] . '</a>';
                    $page_links[] = $page_link;
                    $dots = true;
                elseif ($dots && !$args['show_all']) :
                    $page_links[] = '<span class="page-numbers dots">' . esc_html__('&hellip;', 'wpfd') . '</span>';
                    $dots = false;
                endif;
            endif;
        endfor;
        if ($args['prev_next'] && $current && ($current < $total || -1 === $total)) :
            $link = str_replace('%_%', $args['format'], $args['base']);
            $link = str_replace('%#%', $current + 1, $link);
            if ($add_args) {
                $link = add_query_arg($add_args, $link);
            }
            $link .= $args['add_fragment'];

            /**
             * This filter is documented in wp-includes/general-template.php
             */
            $page_link = "<a class='next page-numbers' data-page='" . ($current + 1) . "' data-source_search='" . $args['source_search'] . "'>";
            $page_link .= $args['next_text'] . '</a>';
            $page_links[] = $page_link;
        endif;

        switch ($args['type']) {
            case 'array':
                return $page_links;
            case 'list':
                $r .= "<ul class='page-numbers'>\n\t<li>";
                $r .= join("</li>\n\t<li>", $page_links);
                $r .= "</li>\n</ul>\n";
                break;
            default:
                $r .= "<div class='wpfd-pagination'>\n\t";
                $r .= join("\n", $page_links);
                $r .= "\n</div>\n";
                break;
        }
        return $r;
    }
}

if (!function_exists('wpfd_esc_desc')) {
    /**
     * Escaping for HTML description blocks.
     *
     * @param string $text Text
     *
     * @return string
     */
    function wpfd_esc_desc($text)
    {
        $safe_text = wp_check_invalid_utf8($text);
        // Remove <script>
        $safe_text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $safe_text);
        return apply_filters('wpfd_esc_desc', $safe_text, $text);
    }
}

if (PHP_VERSION_ID < 70300) {
    if (!function_exists('is_countable')) {
        /**
         * Check countable variables from php version 7.3.0
         *
         * @param mixed $var Variable to check
         *
         * @return boolean
         */
        function is_countable($var)
        {
            return is_array($var) || $var instanceof Countable || $var instanceof ResourceBundle || $var instanceof SimpleXmlElement;
        }
    }
}

if (!function_exists('wpfd_sanitize_ajax_url')) {
    /**
     * Sanitize wp file download ajax url
     *
     * @param string $url Ajax url
     *
     * @return string
     */
    function wpfd_sanitize_ajax_url($url)
    {
        if (preg_match('/Wpfd/i', $url)) {
            $url = str_replace('action=Wpfd', 'action=wpfd', $url);
        }

        return apply_filters('wpfd_sanitize_ajax_url', $url);
    }
}
if (!function_exists('wpfd_locate_template')) {
    /**
     * Locate a template and return the path for inclusion.
     *
     * Loader order:
     *
     * wp-content/$content_path/$template_name
     * $default_path/app/site/themes/$template_name
     *
     * @param string $template_name Template name
     * @param string $content_path  Template path dir
     * @param string $default_path  Default path dir
     *
     * @return string
     */
    function wpfd_locate_template($template_name, $content_path = '', $default_path = '')
    {

        if (!$content_path) {
            $content_path = WP_CONTENT_DIR . '/wp-file-download/templates/';
        }

        if (!$default_path) {
            $default_path = WPFD_PLUGIN_DIR_PATH . '/app/site/themes/templates/';
        }

        // Look into wp-content directory for template file - this is priority.
        $template = file_exists(trailingslashit($content_path) . $template_name) ? trailingslashit($content_path) . $template_name : '';

        // Get default template.
        if (!$template) {
            $template = trailingslashit($default_path) . $template_name;
        }

        /**
         * Filter on return found template path
         *
         * @param string Template path
         * @param string Template name
         * @param string Template path dir
         */
        return apply_filters('wpfd_locate_template', $template, $template_name, $content_path);
    }
}
if (!function_exists('wpfd_get_template')) {
    /**
     * Get templates
     *
     * @param string $template_name Template name.
     * @param array  $args          Template arguments
     * @param string $content_path  Template path dir
     * @param string $default_path  Default path dir
     *
     * @return void
     */
    function wpfd_get_template($template_name, $args = array(), $content_path = '', $default_path = '')
    {
        if (!empty($args) && is_array($args)) {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- use extract is ok
            extract($args);
        }

        $located = wpfd_locate_template($template_name, $content_path, $default_path);

        if (!file_exists($located)) {
            return;
        }

        /**
         * Allow 3rd party plugin filter
         *
         * @param string Template path
         * @param string Template name
         * @param array  Template variables
         * @param string Template path dir
         * @param string Default path dir
         *
         * @return string
         */
        $located = apply_filters('wpfd_get_template', $located, $template_name, $args, $content_path, $default_path);

        /**
         * Action fire before a template part called
         *
         * @param string Template name
         * @param string Template path dir
         * @param string Template path
         * @param array  Template variables
         */
        do_action('wpfd_before_template_part', $template_name, $content_path, $located, $args);

        include $located;

        /**
         * Action fire after a template part called
         *
         * @param string Template name
         * @param string Template path dir
         * @param string Template path
         * @param array  Template variables
         */
        do_action('wpfd_after_template_part', $template_name, $content_path, $located, $args);
    }
}

if (!function_exists('wpfd_get_template_html')) {
    /**
     * Get templates and return html
     *
     * @param string $template_name Template name
     * @param array  $args          Template arguments
     * @param string $content_path  Template path dir
     * @param string $default_path  Default path dir
     *
     * @return false|string
     */
    function wpfd_get_template_html($template_name, $args = array(), $content_path = '', $default_path = '')
    {
        ob_start();
        wpfd_get_template($template_name, $args, $content_path, $default_path);
        return ob_get_clean();
    }
}
/**
 * Locate theme file path
 *
 * @param string $theme        Theme name
 * @param string $file         Theme file to locate
 * @param string $content_path Template path dir
 * @param string $default_path Template default path dir
 * @param string $upload_path  Template old path dir
 *
 * @return mixed
 */
function wpfd_locate_theme($theme, $file, $content_path = '', $default_path = '', $upload_path = '')
{
    $ds = DIRECTORY_SEPARATOR;
    if ($content_path === '') {
        $content_path = WP_CONTENT_DIR . $ds . wpfd_get_content_dir() . $ds . 'themes' . $ds . 'wpfd-' . $theme . $ds;
    }
    if (!$upload_path) {
        $dir = wp_upload_dir();
        $upload_path = $dir['basedir'] . $ds . 'wpfd-themes' . $ds . 'wpfd-' . $theme . $ds;
    }
    if (!$default_path) {
        $default_path = WPFD_PLUGIN_DIR_PATH . $ds . 'app' . $ds . 'site' . $ds . 'themes' . $ds . 'wpfd-' . $theme . $ds;
    }

    if (file_exists(trailingslashit($content_path) . $file)) {
        $template = trailingslashit($content_path) . $file;
    } elseif (file_exists(trailingslashit($upload_path) . $file)) {
        $template = trailingslashit($upload_path) . $file;
    }

    // Get default template.
    if (!isset($template)) {
        $template = trailingslashit($default_path) . $file;
    }

    /**
     * Filter on return found template path
     *
     * @param string Template path
     * @param string Template name
     * @param string Template path dir
     */
    return apply_filters('wpfd_locate_theme', $template, $theme, $file, $content_path);
}

/**
 * Get theme instance by name
 *
 * @param string $theme        Theme name
 * @param string $content_path Template path dir
 * @param string $default_path Template default path dir
 * @param string $upload_path  Template old path dir
 *
 * @return WpfdTheme{NAME}
 */
function wpfd_get_theme_instance($theme, $content_path = '', $default_path = '', $upload_path = '')
{
    $file = 'theme.php';

    if (!class_exists('wpfdTheme')) {
        $wpfdTheme = WPFD_PLUGIN_DIR_PATH . '/app/site/themes/templates/wpfd-theme.class.php';
        include_once $wpfdTheme;
    }

    if (!class_exists('WpfdThemeDefault')) {
        $defaultTheme = WPFD_PLUGIN_DIR_PATH . '/app/site/themes/wpfd-default/theme.php';
        include_once $defaultTheme;
    }

    $located = wpfd_locate_theme($theme, $file, $content_path, $default_path, $upload_path);

    if (file_exists($located)) {
        include_once $located;
    } else {
        $themefile = WPFD_PLUGIN_DIR_PATH . '/app/site/themes/wpfd-default/theme.php';
        include_once $themefile;
        $theme = 'default';
    }
    $class = 'WpfdTheme' . ucfirst(str_replace('_', '', $theme));

    if (class_exists($class)) {
        $instance = new $class();
    } else {
        $instance = new WpfdThemeDefault();
    }

    return $instance;
}

/**
 * Get supported cloud platform
 *
 * @return array
 */
function wpfd_get_support_cloud()
{
    /**
     * Filter return supported cloud platform
     * Require to detect where categories/files from
     *
     * @param array Cloud platform list
     *
     * @return array
     */
    return apply_filters('wpfd_get_support_cloud', array('googleDrive', 'googleTeamDrive', 'dropbox', 'onedrive', 'onedrive_business', 'aws', 'nextcloud'));
}

/**
 * Check is cloud enable and one of them connected
 *
 * @return boolean
 */
function wpfd_is_cloud_exists()
{
    if (has_filter('wpfdAddon_check_cloud_exist', 'check_cloud_exist')) {
        if (apply_filters('wpfdAddon_check_cloud_exist', false)) {
            return true;
        }
    }

    return false;
}

/**
 * Check is dropbox connected
 *
 * @return boolean
 */
function wpfd_dropbox_connected()
{
    return apply_filters('wpfd_addon_dropbox_connected', false);
}

/**
 * Check is Google Drive connected
 *
 * @return boolean
 */
function wpfd_google_drive_connected()
{
    return apply_filters('wpfd_addon_google_drive_connected', false);
}

/**
 * Check is Google Team Drive connected
 *
 * @return boolean
 */
function wpfd_google_team_drive_connected()
{
    return apply_filters('wpfd_addon_google_team_drive_connected', false);
}

/**
 * Check is Onedrive connected
 *
 * @return boolean
 */
function wpfd_onedrive_connected()
{
    return apply_filters('wpfd_addon_onedrive_connected', false);
}

/**
 * Check is Onedrive Business connected
 *
 * @return boolean
 */
function wpfd_onedrive_business_connected()
{
    return apply_filters('wpfd_addon_onedrive_business_connected', false);
}

/**
 * Check is Amazon S3 connected
 *
 * @return boolean
 */
function wpfd_aws_connected()
{
    return apply_filters('wpfd_addon_aws_connected', false);
}

/**
 * Check is Nextcloud connected
 *
 * @return boolean
 */
function wpfd_nextcloud_connected()
{
    return apply_filters('wpfd_addon_nextcloud_connected', false);
}

/**
 * Get file url from real path
 *
 * @param string $path Real path
 *
 * @return string
 */
function wpfd_abs_path_to_url($path = '')
{
    // phpcs:ignore PHPCompatibility.LanguageConstructs.NewEmptyNonVariable.Found -- This is for check only
    $siteUrl = defined('WP_SITEURL') && !empty(WP_SITEURL) ? WP_SITEURL : site_url();
    $abspath = wpfd_get_abspath();

    $pInfo = null;
    if (defined('WPFD_PLUGIN_DIR_PATH') && WPFD_PLUGIN_DIR_PATH) {
        $pInfo = pathinfo(WPFD_PLUGIN_DIR_PATH);
        $pInfo = explode(DIRECTORY_SEPARATOR, $pInfo['dirname']);
        array_splice($pInfo, count($pInfo) - 2, 2);
        $pInfo = implode(DIRECTORY_SEPARATOR, $pInfo) . DIRECTORY_SEPARATOR;
    }

    if ($pInfo !== null) {
        $pInfo = str_replace('\\', '/', $pInfo);
        $abspath = str_replace('\\', '/', $abspath);
        if ($pInfo !== $abspath && defined('WP_CONTENT_DIR') && WP_CONTENT_DIR) {
            $abspath = WP_CONTENT_DIR;
        }
    }

    if (defined('WP_SITEURL') && WP_SITEURL && defined('WP_CONTENT_URL') && WP_CONTENT_URL) {
        $WP_SITEURL = preg_replace('/([^:])(\/{2,})/', '$1/', str_replace('\\', '/', WP_SITEURL));
        $WP_CONTENT_URL = preg_replace('/([^:])(\/{2,})/', '$1/', str_replace('\\', '/', WP_CONTENT_URL));
        $WP_SITE_CONTENT_URL = preg_replace('/([^:])(\/{2,})/', '$1/', $WP_SITEURL . '/wp-content');
        if ($WP_SITEURL !== $WP_CONTENT_URL && $WP_SITE_CONTENT_URL !== $WP_CONTENT_URL) {
            $siteUrl = $WP_CONTENT_URL;
        }
    }

    if (defined('FLYWHEEL_PLUGIN_DIR') && FLYWHEEL_PLUGIN_DIR) {
        $siteUrl = content_url();
    }

    if (defined('WP_SITEURL') && WP_SITEURL && strpos($siteUrl, 'wp-content') === false) {
        $siteUrl = $siteUrl . '/wp-content';
    }

    /**
     * Filter to fix wrong ABSPATH in some hosting/server
     *
     * @param string
     */
    $realAbsPath = apply_filters('wpfd_real_abs_path', $abspath);

    if ($realAbsPath === '//') {
        return esc_url_raw($siteUrl . wp_normalize_path($path));
    }

    $url = str_replace(
        wp_normalize_path(untrailingslashit($realAbsPath)),
        $siteUrl,
        wp_normalize_path($path)
    );
    $url = str_replace('/wp-content/wp-content', '/wp-content', $url);
    
    /**
     * Filter to change finally url
     *
     * @param string
     */
    return apply_filters('wpfd_abs_path_to_url', esc_url_raw($url));
}
/**
 * Check capability of current user to manage file
 *
 * @return boolean
 */
function wpfd_can_manage_file()
{
    /**
     * Filter check capability of current user to manage file
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     */
    return apply_filters('wpfd_user_can', current_user_can('wpfd_manage_file'), 'manage_file');
}
/**
 * Check capability of current user to edit permissions settings
 *
 * @return boolean
 */
function wpfd_can_edit_permission()
{
    /**
     * Filter check capability of current user to edit permissions settings
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     */
    return apply_filters('wpfd_user_can', current_user_can('wpfd_edit_permission'), 'edit_permission');
}
/**
 * Check provided user is owner for a category
 *
 * @param object      $category Category
 * @param object|null $owner    User to check
 *
 * @return boolean
 */
function wpfd_user_is_owner_of_category($category, $owner = null)
{
    if ($owner === null) {
        $owner = wp_get_current_user();
    }

    if (!isset($owner->ID) || (isset($owner->ID) && !$owner->ID)) {
        // User is not login
        return false;
    }

    if (isset($category->params['category_own']) && intval($owner->ID) === intval($category->params['category_own'])) {
        return true;
    }

    return false;
}
/**
 * Check capability of current user to edit category
 *
 * @return boolean
 */
function wpfd_can_edit_category()
{
    /**
     * Filter check capability of current user to edit category
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     *
     * @ignore Hook already documented
     */
    return apply_filters('wpfd_user_can', current_user_can('wpfd_edit_category'), 'edit_category');
}

/**
 * Check capability of current user to edit own category
 *
 * @return boolean
 */
function wpfd_can_edit_own_category()
{
    /**
     * Filter check capability of current user to edit own category
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     *
     * @ignore Hook already documented
     */
    return apply_filters('wpfd_user_can', current_user_can('wpfd_edit_own_category'), 'edit_own_category');
}

/**
 * Check capability of current user to delete category
 *
 * @return boolean
 */
function wpfd_can_delete_category()
{
    /**
     * Filter check capability of current user to delete category
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     *
     * @ignore Hook already documented
     */
    return apply_filters('wpfd_user_can', current_user_can('wpfd_delete_category'), 'delete_category');
}

/**
 * Check capability of current user to create category
 *
 * @return boolean
 */
function wpfd_can_create_category()
{
    /**
     * Filter check capability of current user to create category
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     *
     * @ignore Hook already documented
     */
    return apply_filters('wpfd_user_can', current_user_can('wpfd_create_category'), 'create_category');
}

/**
 * Check capability of current user to download files
 *
 * @return boolean
 */
function wpfd_can_download_files()
{

    $canDownloadFiles   = false;
    $globalConfig       = get_option('_wpfd_global_config');
    $currentUser        = wp_get_current_user();
    if ((int)$currentUser->ID === 0) {
        $canDownloadFiles = (!isset($globalConfig['guest_download_files']) || (isset($globalConfig['guest_download_files']) && (int)$globalConfig['guest_download_files'] === 1));
    } else {
        if (current_user_can('wpfd_download_files')) {
            $canDownloadFiles = true;
        } else {
            // Set default value to true
            $roles = $currentUser->roles;
            if (is_countable($roles)) {
                foreach ($roles as $role) {
                    if (isset($globalConfig[$role . '_download_files']) && (int) $globalConfig[$role . '_download_files'] === 1) {
                        $canDownloadFiles = true;
                    } elseif (isset($globalConfig[$role . '_download_files']) && (int) $globalConfig[$role . '_download_files'] === 0) {
                        $canDownloadFiles = false;
                    } else {
                        $canDownloadFiles = true;
                    }
                    // This user can download file, stop checking other role
                    if ($canDownloadFiles) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Filter check capability of current user to download files
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     *
     * @ignore Hook already documented
     */
    return apply_filters('wpfd_user_can', $canDownloadFiles, 'download_files');
}

/**
 * Check capability of current user to preview files
 *
 * @return boolean
 */
function wpfd_can_preview_files()
{
    $canPreviewFiles   = false;
    $globalConfig       = get_option('_wpfd_global_config');
    $currentUser        = wp_get_current_user();
    if ((int)$currentUser->ID === 0) {
        $canPreviewFiles = (!isset($globalConfig['guest_preview_files']) || (isset($globalConfig['guest_preview_files']) && (int)$globalConfig['guest_preview_files'] === 1));
    } else {
        if (current_user_can('wpfd_preview_files')) {
            $canPreviewFiles = true;
        } else {
            // Set default value to true
            $roles = $currentUser->roles;
            if (is_countable($roles)) {
                foreach ($roles as $role) {
                    if (isset($globalConfig[$role . '_preview_files']) && (int) $globalConfig[$role . '_preview_files'] === 1) {
                        $canPreviewFiles = true;
                    } elseif (isset($globalConfig[$role . '_preview_files']) && (int) $globalConfig[$role . '_preview_files'] === 0) {
                        $canPreviewFiles = false;
                    } else {
                        $canPreviewFiles = true;
                    }

                    // This user can preview file, stop checking other role
                    if ($canPreviewFiles) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Filter check capability of current user to preview files
     *
     * @param boolean The current user has the given capability
     * @param string  Action name
     *
     * @return boolean
     *
     * @ignore Hook already documented
     */
    return apply_filters('wpfd_user_can', $canPreviewFiles, 'preview_files');
}

/**
 * Check capability of current user to upload and publish files
 *
 * @return boolean
 */
function wpfd_can_upload_files()
{
    $canUploadFiles = false;

    if (current_user_can('wpfd_upload_only')) {
        $canUploadFiles = true;
    } else {
        $canUploadFiles = false;
    }

    return apply_filters('wpfd_user_can', $canUploadFiles, 'upload_only_files');
}


if (!function_exists('wpfd_gutenberg_integration')) {
    /**
     * WP File Download gutenberg integration
     *
     * @return void
     */
    function wpfd_gutenberg_integration()
    {
        include_once WPFD_PLUGIN_DIR_PATH . 'app/admin/helpers/WpfdHelperFile.php';
        \Joomunited\WPFramework\v1_0_6\Application::getInstance('Wpfd', WPFD_PLUGIN_FILE, 'admin');
        $modelConfig = \Joomunited\WPFramework\v1_0_6\Model::getInstance('config');
        $globalConfig = $modelConfig->getConfig();
        $iconSet = isset($globalConfig['icon_set']) ? $globalConfig['icon_set'] : 'svg';

        wp_enqueue_script(
            'wpfd-blocks',
            WPFD_PLUGIN_URL . 'app/admin/assets/blocks/wpfd-blocks.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
            WPFD_VERSION
        );
        wp_localize_script('wpfd-blocks', 'wpfd_block_vars', array(
            'iconSet' => $iconSet
        ));
        wp_enqueue_style(
            'wpfd-category-style',
            WPFD_PLUGIN_URL . 'app/admin/assets/css/wpfd-blocks.css',
            array('wp-edit-blocks'),
            WPFD_VERSION
        );

        wp_enqueue_style(
            'wpfd-front',
            plugins_url('app/site/assets/css/front.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        wp_enqueue_style(
            'wpfd-single-file-css',
            plugins_url('app/admin/assets/ui/css/singlefile.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-single-file-button',
            plugins_url('app/site/assets/css/wpfd-single-file-button.css', WPFD_PLUGIN_FILE),
            array('wpfd-single-file-css'),
            WPFD_VERSION
        );
        $customSingleFilePath   = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'wpfd-single-file-button.css';
        $customSingleFileUrl    = WP_CONTENT_URL . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'wpfd-single-file-button.css';
        if (file_exists($customSingleFilePath)) {
            wp_enqueue_style(
                'wpfd-custom-file-buttons-style',
                $customSingleFileUrl,
                array(),
                WPFD_VERSION,
                'all'
            );
        }

        wp_enqueue_style(
            'wpfd-material-design',
            plugins_url('app/site/assets/css/material-design-iconic-font.min.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style(
            'wpfd-search_filter',
            plugins_url('app/site/assets/css/search_filter.css', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_style('jquery-jaofiletree', plugins_url('app/site/assets/css/jaofiletree.css', WPFD_PLUGIN_FILE), array(), WPFD_VERSION);
        wp_enqueue_script('jquery-jaofiletree', plugins_url('app/site/assets/js/jaofiletree.js', WPFD_PLUGIN_FILE), array(), WPFD_VERSION);
        wp_enqueue_script(
            'handlebars',
            plugins_url('app/site/assets/js/handlebars-v4.7.7.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );

        wp_enqueue_style('wpfd-jquery-tagit', plugins_url('app/admin/assets/css/jquery.tagit.css', WPFD_PLUGIN_FILE));
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('wpfd-jquery-tagit', plugins_url('app/admin/assets/js/jquery.tagit.js', WPFD_PLUGIN_FILE), array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-autocomplete'));
        wp_register_script('wpfd-chosen', plugins_url('app/admin/assets/js/chosen.jquery.min.js', WPFD_PLUGIN_FILE), array('jquery'), WPFD_VERSION);
        wp_enqueue_style('wpfd-chosen-style', plugins_url('app/admin/assets/css/chosen.css', WPFD_PLUGIN_FILE), array(), WPFD_VERSION);
        wp_enqueue_script(
            'wpfd-search_block',
            plugins_url('app/admin/assets/js/search_block.js', WPFD_PLUGIN_FILE),
            array('wpfd-chosen'),
            WPFD_VERSION
        );
        wp_localize_script(
            'wpfd-search_block',
            'wpfd_search_block',
            array(
                'msg_search_box_placeholder'     => esc_html__('Input tags here...', 'wpfd')
            )
        );

        if ($iconSet === 'default') {
            wp_enqueue_style(
                'wpfd-blocks-icon-style',
                WPFD_PLUGIN_URL . 'app/admin/assets/css/wpfd-blocks-icon.css',
                array('wp-edit-blocks'),
                WPFD_VERSION
            );
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
                    array('wpfd-category-style'),
                    WPFD_VERSION
                );
            }
        }
    }
}

/**
 * Add WP File Download categories to blocks categories
 *
 * @param array   $categories Categories array
 * @param WP_Post $post       Post object
 *
 * @return array
 */
function wpfd_blocks_categories($categories, $post)
{
    // Display wpfd blocks in all post type
    return array_merge(
        $categories,
        array(
            array(
                'slug'  => 'wp-file-download',
                'title' => __('الفولدر الذكي', 'wpfd'),
            ),
        )
    );
}

/**
 * Install clean statistics job
 *
 * @param string $task     Task name
 * @param string $interval Interval name
 *
 * @return void
 */
function wpfd_install_job($task, $interval)
{
    if (empty($interval) || empty($task)) {
        return;
    }

    if (!wp_next_scheduled($task)) {
        wp_schedule_event(time(), $interval, $task);
    }
}

/**
 * Destroy clean statistics job
 *
 * @param string $task Task name
 *
 * @return void
 */
function wpfd_destroy_job($task)
{
    if (empty($task)) {
        return;
    }

    $timestamp = wp_next_scheduled($task);
    wp_unschedule_event($timestamp, $task);
}

/**
 * Reinstall clean statistics job
 *
 * @param string $task     Task name
 * @param string $interval Interval name
 *
 * @return void
 */
function wpfd_reinstall_job($task, $interval)
{
    if (empty($interval) || empty($task)) {
        return;
    }

    wpfd_destroy_job($task);
    wpfd_install_job($task, $interval);
}

/**
 * Schedules
 *
 * @return void
 */
function wpfd_schedules()
{
    add_filter('cron_schedules', 'wpfd_get_schedules');
}

/**
 * Get all wpfd schedules
 *
 * @param array $schedules Schedules list
 *
 * @return array
 */
function wpfd_get_schedules($schedules)
{
    /**
     * Filter to add wpfd schedules task
     *
     * @param array $schedule An array for schedule task
     *
     * @internal
     *
     * @return array
     */
    $schedule = apply_filters('wpfd_get_schedules', array());

    if (!is_array($schedule)) {
        return $schedules;
    }

    $schedules = array_merge($schedules, $schedule);

    return $schedules;
}

/**
 * Get wpfd_remove_statistics interval
 *
 * @return integer
 */
function wpfd_get_remove_statistics_interval()
{
    $interval = 0;

    $config = get_option('_wpfd_global_config');

    if (!is_array($config) && !isset($config['statistics_storage_duration'])) {
        return $interval;
    }

    $duration = isset($config['statistics_storage_duration']) ? $config['statistics_storage_duration'] : 'forever';
    $times = isset($config['statistics_storage_times']) ? (int) $config['statistics_storage_times'] : 0;

    // Calculate interval
    if ($times !== 0) {
        switch ($duration) {
            case 'years':
                $interval = $times * 31104000;
                break;
            case 'months':
                $interval = $times * 2592000;
                break;
            case 'days':
                $interval = $times * 86400;
                break;
            case 'forever':
            default:
                break;
        }
    }

    return (int) $interval;
}

/**
 * Get wpfd_remove_statistics schedule
 *
 * @param array $schedule Schedule list
 *
 * @return array|boolean
 */
function wpfd_get_remove_statistics_schedule($schedule)
{
    $interval = wpfd_get_remove_statistics_interval();

    if ($interval === 0) {
        return false;
    }

    $schedule['wpfd_remove_statistics'] = array(
        'interval' => $interval,
        'display'  => esc_html__('WPFD Clean Statistics', 'wpfd'),
    );

    return $schedule;
}

/**
 * Remove statistics
 *
 * @return void
 */
function wpfd_remove_statistics()
{
    global $wpdb;

    // Check last time statistics is run to prevent it clear in first time.
    $lastCleanTime = get_option('wpfd_remove_statistics_time', 0);
    $interval = wpfd_get_remove_statistics_interval();

    // Do not clear in first running and update
    if ($lastCleanTime === 0 || ($lastCleanTime > 0 && (time() - $lastCleanTime) < $interval)) {
        if ($lastCleanTime === 0) {
            update_option('wpfd_remove_statistics_time', time());
        }
        return;
    }

    $ts = $lastCleanTime + $interval;
    $deletePoint = date('Y-m-d', $ts);
    $wpdb->query('DELETE FROM ' . $wpdb->prefix . 'wpfd_statistics WHERE `date` < "' . $deletePoint . '"');

    update_option('wpfd_remove_statistics_time', time());
}

/**
 * Reinstall remove statistics task
 *
 * @return void
 */
function wpfd_after_main_setting_save()
{
    wpfd_reinstall_job('wpfd_remove_statistics_tasks', 'wpfd_remove_statistics');
}

/**
 * Clean expiry tokens
 *
 * @return void
 */
function wpfd_remove_expiry_tokens()
{
    global $wpdb;

    /**
     * Filter to change token live time
     *
     * @param int Token live time in seconds
     *
     * @return int
     *
     * @ignore
     */
    $time = time() - apply_filters('wpfd_token_live_time', 3600);

    $wpdb->query(
        $wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'wpfd_tokens WHERE created_at < %d', $time)
    );
}

/**
 * Get clean junks schedule
 *
 * @param array $schedule Schedule list
 *
 * @return array
 */
function wpfd_get_clean_junks_schedule($schedule)
{
    $interval = (defined('WPFD_CLEAN_INTERVAL') && WPFD_CLEAN_INTERVAL > 0) ? WPFD_CLEAN_INTERVAL : 43200; // 12 hours
    /**
     * Filter to change clean junks time, this will override WPFD_CLEAN_INTERVAL constance
     *
     * @param $interval Time in second
     */
    $interval = apply_filters('wpfd_clean_interval', $interval);

    if (!$schedule) {
        $schedule = array();
    }

    $schedule['wpfd_clean_junks'] = array(
        'interval' => $interval,
        'display'  => esc_html__('WPFD Clean Junks', 'wpfd'),
    );
    return $schedule;
}

/**
 * Clean junks
 *
 * @return void
 */
function wpfd_clean_junks()
{
    $wp_upload_dir = wp_upload_dir();
    $dr = DIRECTORY_SEPARATOR;
    $uploadDir     = $wp_upload_dir['basedir'] . $dr . 'wpfd';
    $interval = (defined('WPFD_CLEAN_INTERVAL') && WPFD_CLEAN_INTERVAL > 0) ? WPFD_CLEAN_INTERVAL : 43200; // 12 hours

    // Check if upload dir exists. This folder was not created until a file uploaded
    if (file_exists($uploadDir)) {
        // Clean file upload junks
        $categories = glob($uploadDir . $dr . '*', GLOB_ONLYDIR);
        if (is_array($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $junkFolder = glob($category . $dr . '*', GLOB_ONLYDIR);
                // Check how old is this dir with WPFD_CLEAN_INTERVAL
                foreach ($junkFolder as $folderPath) {
                    $createdTime = filectime($folderPath);
                    if (!$createdTime || ($createdTime + $interval) > time()) {
                        continue;
                    }

                    rrmdir($folderPath);
                }
            }
        }

        // Clean category zip junks
        $objects = scandir($uploadDir);
        if (false !== $objects) {
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $filePath = $uploadDir . $dr . $object;
                    if (filetype($filePath) === 'file' && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'zip') {
                        // Check how old is this file with WPFD_CLEAN_INTERVAL
                        $createdTime = filectime($filePath);
                        if (!$createdTime || ($createdTime + $interval) > time()) {
                            continue;
                        }

                        unlink($filePath);
                    }
                }
            }
        }
    }

    // Clean expiry tokens
    wpfd_remove_expiry_tokens();
}

if (!function_exists('rrmdir')) {
    /**
     * Remove a dir
     *
     * @param string $dir Directory path
     *
     * @return void
     */
    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($dir . '/' . $object) === 'dir') {
                        rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
/**
 * Convert php date format to moment.js date format
 *
 * @param string $format Php date format
 *
 * @return string
 */
function wpfdPhpToMomentDateFormat($format)
{
    $replacements = array(
        'd' => 'DD',
        'D' => 'ddd',
        'j' => 'D',
        'l' => 'dddd',
        'N' => 'E',
        'S' => 'o',
        'w' => 'e',
        'z' => 'DDD',
        'W' => 'W',
        'F' => 'MMMM',
        'm' => 'MM',
        'M' => 'MMM',
        'n' => 'M',
        't' => '', // no equivalent
        'L' => '', // no equivalent
        'o' => 'YYYY',
        'Y' => 'YYYY',
        'y' => 'YY',
        'a' => 'a',
        'A' => 'A',
        'B' => '', // no equivalent
        'g' => 'h',
        'G' => 'H',
        'h' => 'hh',
        'H' => 'HH',
        'i' => 'mm',
        's' => 'ss',
        'u' => 'SSS',
        'e' => 'zz', // deprecated since version 1.6.0 of moment.js
        'I' => '', // no equivalent
        'O' => '', // no equivalent
        'P' => '', // no equivalent
        'T' => '', // no equivalent
        'Z' => '', // no equivalent
        'c' => '', // no equivalent
        'r' => '', // no equivalent
        'U' => 'X',
    );
    $momentFormat = strtr($format, $replacements);

    return $momentFormat;
}


/**
 * Enqueue assets
 *
 * @return void
 */
function wpfd_enqueue_assets()
{
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_style('dashicons');
//    wp_enqueue_script('jquery-ui-1.11.4');
    wp_enqueue_script('wpfd-colorbox');
    wp_enqueue_script('wpfd-colorbox-init');
    wp_enqueue_script('wpfd-videojs');
    wp_enqueue_style('wpfd-videojs');
    wp_enqueue_style('wpfd-colorbox');
    wp_enqueue_style('wpfd-viewer');
}

/**
 * Search assets
 *
 * @return void
 */
function wpfd_register_assets()
{
    $config = get_option('_wpfd_global_config', array());
    $fileIconSet = (isset($config['icon_set']) && $config['icon_set'] !== 'default') ? ' wpfd-icon-set-' . esc_attr($config['icon_set']) : '';
    $user = wp_get_current_user();
    $userId = isset($user->ID) ? $user->ID : 0;
    wp_enqueue_script('jquery');
    wp_enqueue_style('dashicons');
    wp_register_style(
        'jquery-ui-1.9.2',
        plugins_url('app/admin/assets/css/ui-lightness/jquery-ui-1.9.2.custom.min.css', WPFD_PLUGIN_FILE)
    );
    wp_register_script('wpfd-colorbox', plugins_url('app/site/assets/js/jquery.colorbox-min.js', WPFD_PLUGIN_FILE));
    wp_register_script(
        'wpfd-colorbox-init',
        plugins_url('app/site/assets/js/colorbox.init.js', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_localize_script(
        'wpfd-colorbox-init',
        'wpfdcolorboxvars',
        array(
            'preview_loading_message' => esc_html__('The preview is still loading, you can cancel it at any time...', 'wpfd') . '<span class="wpfd-loading-close">' . esc_html__('cancel', 'wpfd') . '</span>'
        )
    );
    wp_register_script(
        'wpfd-videojs',
        plugins_url('app/site/assets/js/video.js', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_localize_script(
        'wpfd-colorbox',
        'wpfdcolorbox',
        array(
            'wpfdajaxurl' => wpfd_sanitize_ajax_url(\Joomunited\WPFramework\v1_0_6\Application::getInstance('Wpfd')->getAjaxUrl()),
            'wpfd_download_file_attached_preview' => esc_html__('Download file attached to this preview', 'wpfd'),
            'wpfd_file_icon_set' => $fileIconSet,
            'wpfd_user_login_id' => $userId
        )
    );

    wp_register_style(
        'wpfd-videojs',
        plugins_url('app/site/assets/css/video-js.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_register_style(
        'wpfd-colorbox',
        plugins_url('app/site/assets/css/colorbox.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    wp_register_style(
        'wpfd-viewer',
        plugins_url('app/site/assets/css/viewer.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
}


/**
 * Search access
 *
 * @return void
 */
function wpfd_assets_search()
{
    wp_enqueue_style('wpfd-jquery-tagit', plugins_url('app/admin/assets/css/jquery.tagit.css', WPFD_PLUGIN_FILE));

    wp_enqueue_style(
        'wpfd-daterangepicker-style',
        plugins_url('app/admin/assets/ui/css/daterangepicker.css', WPFD_PLUGIN_FILE),
        array(),
        WPFD_VERSION
    );
    if (!is_admin()) {
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script(
            'wpfd-jquery-tagit',
            plugins_url('app/admin/assets/js/jquery.tagit.js', WPFD_PLUGIN_FILE),
            array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-autocomplete'),
            WPFD_VERSION
        );
        wp_enqueue_script(
            'wpfd-moment',
            plugins_url('app/admin/assets/ui/js/moment.min.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_enqueue_script(
            'wpfd-daterangepicker',
            plugins_url('app/admin/assets/ui/js/daterangepicker.min.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
        wp_register_script('wpfd-chosen', plugins_url('app/admin/assets/js/chosen.jquery.min.js', WPFD_PLUGIN_FILE), array('jquery'), WPFD_VERSION);
        wp_enqueue_style('wpfd-chosen-style', plugins_url('app/admin/assets/css/chosen.css', WPFD_PLUGIN_FILE), array(), WPFD_VERSION);
        wp_enqueue_script(
            'wpfd-search_filter',
            plugins_url('app/site/assets/js/search_filter.js', WPFD_PLUGIN_FILE),
            array('wpfd-chosen'),
            WPFD_VERSION
        );
        wp_add_inline_script('wpfd-search_filter', 'var wpfdajaxurl = "'.admin_url('admin-ajax.php').'?juwpfisadmin=false&action=wpfd&"');
        wp_enqueue_style('wpfd-jquery-qtip-style', plugins_url('app/admin/assets/ui/css/jquery.qtip.css', WPFD_PLUGIN_FILE), array(), WPFD_VERSION);
        wp_enqueue_script(
            'wpfd-jquery-qtip',
            plugins_url('app/admin/assets/ui/js/jquery.qtip.min.js', WPFD_PLUGIN_FILE),
            array(),
            WPFD_VERSION
        );
    }
    \Joomunited\WPFramework\v1_0_6\Application::getInstance('Wpfd', WPFD_PLUGIN_FILE, 'site');
    $modelConfig = \Joomunited\WPFramework\v1_0_6\Model::getInstance('configfront');
    if (method_exists($modelConfig, 'getGlobalConfig')) {
        $globalConfig = $modelConfig->getGlobalConfig();
    } elseif (method_exists($modelConfig, 'getConfig')) {
        $globalConfig = $modelConfig->getConfig();
    }
    $searchconfig = $modelConfig->getSearchConfig();
    $searchFileSuggestion = isset($searchconfig['search_suggestion']) ? intval($searchconfig['search_suggestion']) : 0;
    $locale = substr(get_locale(), 0, 2);

    // Add translable for search form daterangepicker
    wp_add_inline_script('wpfd-search_filter', 'var wpfdLocaleSettings = {
            "format": "' . wpfdPhpToMomentDateFormat($globalConfig['date_format']) . '",
            "separator": " - ",
            "applyLabel": "' . esc_html__('Apply', 'wpfd') . '",
            "cancelLabel": "' . esc_html__('Cancel', 'wpfd') . '",
            "fromLabel": "' . esc_html__('From', 'wpfd') . '",
            "toLabel": "' . esc_html__('To', 'wpfd') . '",
            "customRangeLabel": "' . esc_html__('Custom', 'wpfd') . '",
            "weekLabel": "' . esc_html__('W', 'wpfd') . '",
            "daysOfWeek": [
                "' . esc_html__('Su', 'wpfd') . '",
                "' . esc_html__('Mo', 'wpfd') . '",
                "' . esc_html__('Tu', 'wpfd') . '",
                "' . esc_html__('We', 'wpfd') . '",
                "' . esc_html__('Th', 'wpfd') . '",
                "' . esc_html__('Fr', 'wpfd') . '",
                "' . esc_html__('Sa', 'wpfd') . '",
            ],
            "monthNames": [
                "' . esc_html__('January', 'wpfd') . '",
                "' . esc_html__('February', 'wpfd') . '",
                "' . esc_html__('March', 'wpfd') . '",
                "' . esc_html__('April', 'wpfd') . '",
                "' . esc_html__('May', 'wpfd') . '",
                "' . esc_html__('June', 'wpfd') . '",
                "' . esc_html__('July', 'wpfd') . '",
                "' . esc_html__('August', 'wpfd') . '",
                "' . esc_html__('September', 'wpfd') . '",
                "' . esc_html__('October', 'wpfd') . '",
                "' . esc_html__('November', 'wpfd') . '",
                "' . esc_html__('December', 'wpfd') . '",
            ],
            "firstDay": 1,
        }', 'before');

    wp_localize_script(
        'wpfd-search_filter',
        'wpfdvars',
        array(
            'downloadSelected'                  => (int) $globalConfig['download_selected'] === 1 ? true : false,
            'dateFormat'                        => wpfdPhpToMomentDateFormat($globalConfig['date_format']),
            'locale'                            => $locale,
            'msg_search_box_placeholder'        => esc_html__('Input tags here...', 'wpfd'),
            'msg_file_category'                 => esc_html__('FILES CATEGORY', 'wpfd'),
            'msg_filter_by_tags'                => esc_html__('Filter by Tags', 'wpfd'),
            'msg_no_tag_in_this_category_found' => esc_html__('No tags in this category found!', 'wpfd'),
            'msg_to_date_greater_than_from_date' => esc_html__('The To date must be greater than the From date.', 'wpfd'),
            'msg_overlap_date'                  => esc_html__('You have selected a date that has been overlapped with the previous date.', 'wpfd'),
            'search_file_suggestion'            => $searchFileSuggestion,
            'translates'                        => wpfdTranslateString()
        )
    );
}

/**
 * Create wpfdparams variable
 *
 * @return array
 */
function wpfdUploadParams()
{
    /**
     * Filters allow to turn on category loading message
     *
     * @param boolean
     *
     * @return boolean
     */
    $allowLoadingMessage = apply_filters('wpfd_category_loading_message', false);

    /**
     * Filters allow to expand category tree on frontend
     *
     * @param boolean
     *
     * @return boolean
     */
    $allowCategoryTreeExpanded = apply_filters('wpfd_category_tree_expanded', false);

    /**
     * Filters allow to expand category tree parent on frontend
     *
     * @param boolean
     *
     * @return boolean
     */
    $allowCategoryTreeParentExpanded = apply_filters('wpfd_category_tree_parent_expanded', false);

    /**
     * The filter allows when clicking on the category tree to automatically scroll up to the top of the element
     *
     * @param boolean
     *
     * @return boolean
     */
    $allowCategoryTreeClickScrollUp = apply_filters('wpfd_category_tree_click_scroll_up', false);

    /**
     * Filters allow to open file in new tab when clicking on file title
     *
     * @param boolean
     *
     * @return boolean
     */
    $allowOpenFileTitle = apply_filters('wpfd_file_title_open_file_in_new_tab', false);

    $offRedirectLinkDownloadImageFile = apply_filters('wpfd_off_redirect_link_download_image_file', false);

    $user = wp_get_current_user();
    $userId = isset($user->ID) ? $user->ID : 0;

    $serverUploadLimit = min(
        10 * 1024 * 1024, // Maximum for chunks size is 10MB if other settings is greater than 10MB
        WpfdTool::parseSize(ini_get('upload_max_filesize')),
        WpfdTool::parseSize(ini_get('post_max_size'))
    );

    \Joomunited\WPFramework\v1_0_6\Application::getInstance('Wpfd', WPFD_PLUGIN_FILE, 'site');
    $modelConfig = \Joomunited\WPFramework\v1_0_6\Model::getInstance('configfront');
    if (method_exists($modelConfig, 'getGlobalConfig')) {
        $globalConfig = $modelConfig->getGlobalConfig();
    } elseif (method_exists($modelConfig, 'getConfig')) {
        $globalConfig = $modelConfig->getConfig();
    }

    $wpfdparams = array(
        'wpfdajaxurl'          => admin_url('admin-ajax.php').'?juwpfisadmin=false&action=wpfd&',
        'wpfduploadajax'       => admin_url('admin-ajax.php'),
        'allowed'              => isset($globalConfig['allowedext']) ? $globalConfig['allowedext'] : '',
        'maxFileSize'          => isset($globalConfig['maxinputfile']) ? $globalConfig['maxinputfile'] : 0,
        'serverUploadLimit'    => ((int) $serverUploadLimit === 0) ? 10 * 1024 * 1204 : $serverUploadLimit,
        'ga_download_tracking' => $globalConfig['ga_download_tracking'],
        'ajaxadminurl'         => admin_url('admin-ajax.php') . '?juwpfisadmin=0',
        'allow_loading_message' => $allowLoadingMessage,
        'allow_category_tree_expanded' => $allowCategoryTreeExpanded,
        'allow_category_tree_parent_expanded' => $allowCategoryTreeParentExpanded,
        'allow_category_tree_click_scroll_up' => $allowCategoryTreeClickScrollUp,
        'allow_file_title_open_file_new_tab' => $allowOpenFileTitle,
        'wpfd_plugin_url' => WPFD_PLUGIN_URL,
        'wpfd_user_login_id' => $userId,
        'site_url' => get_site_url(),
        'translates'           => wpfdTranslateString(),
        'offRedirectLinkDownloadImageFile' => $offRedirectLinkDownloadImageFile
    );

    return $wpfdparams;
}

/**
 * Translate string
 *
 * @return array
 */
function wpfdTranslateString()
{
    $translates = array(
        'download_selected' => esc_html__('Download selected', 'wpfd'),
        'msg_upload_file'   => esc_html__('New File(s) uploaded with success!', 'wpfd'),
        'msg_loading'       => esc_html__('Please wait while your file(s) is uploaded!', 'wpfd'),
        'msg_search_file_category_placeholder' => esc_html__('Search in file category...', 'wpfd'),
        'msg_search_file_category_search' => esc_html__('Search', 'wpfd'),
        'wpfd_all_categories' => esc_html__('All Categories', 'wpfd'),
    );

    return $translates;
}


/**
 * Create secure folder
 *
 * @param string $path Path to folder need to created
 *
 * @return boolean
 */
function wpfdCreateSecureFolder($path)
{
    // Correct the path
    $path = trailingslashit($path);

    if (file_exists($path . 'index.html') && file_exists($path . '.htaccess')) {
        return true;
    }

    if (!file_exists($path)) {
        mkdir($path, 0777, true);
        $data = '<html><body bgcolor="#FFFFFF"></body></html>';
        $file = fopen($path . DIRECTORY_SEPARATOR . 'index.html', 'w');
        fwrite($file, $data);
        fclose($file);
        $data = "Options -Indexes\nOrder deny,allow\nDeny from all\n<Files ~ \".(jpg|png|svg|css)$\">\nAllow from all\n</Files>";
        $file = fopen($path . DIRECTORY_SEPARATOR . '.htaccess', 'w');
        fwrite($file, $data);
        fclose($file);

        return true;
    }

    return false;
}

/**
 * Generate PHP Handlebars Renderer
 *
 * @param string $template   Handlebars template
 * @param array  $data       Template data
 * @param string $renderName Template renderer file name
 *
 * @return mixed
 */
function wpfdHandlerbarsRender($template, $data, $renderName)
{
    // Load zorudis/lightncandy
    $ds = DIRECTORY_SEPARATOR;
    require_once WPFD_PLUGIN_DIR_PATH . 'app'. $ds . 'site' . $ds . 'classes' . $ds . 'packages' . $ds . 'autoload.php';
    $php = LightnCandy\LightnCandy::compile($template, array(
        'flags' => LightnCandy\LightnCandy::FLAG_HANDLEBARS,
        'helpers' => array(
            'svgicon' => function ($name, $color, $size) {
                $icons = array();
                $icons['download-icon1'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M400 200c0 112-89 200-201 200C88 400 0 311 0 199S89 0 201 0c111 0 199 89 199 200zm-179-8l-3-1V89c0-15-7-24-18-23-13 1-18 9-18 22v107l-34-35c-8-8-18-11-27-2-8 8-7 18 2 26l63 63c10 11 18 10 28 0l63-62c8-8 10-17 2-27-7-8-17-7-27 2l-31 32zm-21 113h82c13 0 24-4 32-14 10-14 8-29 6-44-1-4-8-9-12-8-5 0-9 6-12 10-2 3-1 8 0 13 1 13-5 17-18 17H131c-25 0-25 0-26-25-1-3 0-6-2-7-3-4-8-8-12-8s-10 5-11 9c-11 30 8 57 40 57h80z"/></g></svg>';
                $icons['download-icon2'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M45 355h310v-65c1-16 15-27 29-22 9 3 16 11 16 20v91c0 12-10 21-24 21H26c-17 0-26-9-26-26v-85a22 22 0 0143-8 31 31 0 011 11l1 57z"/><path data-name="Path 1270" d="M222 235l5-5 45-45c9-9 23-9 32 0s9 22-1 32l-86 86c-10 10-23 10-34 0l-86-86a22 22 0 1131-31l45 44a55 55 0 013 4l2-1V24c0-13 8-23 20-24 13-1 24 9 24 23v212z"/></g></svg>';
                $icons['download-icon3'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M200 400H26c-18 0-26-9-26-27v-73c0-16 9-25 24-25h105a14 14 0 018 4l28 28c21 21 48 21 69 0l28-29a13 13 0 018-3h106c14 0 24 9 24 23v79c0 14-10 23-25 23H200zm155-63c-9 0-17 8-17 16a15 15 0 0015 16c9 0 17-7 17-16a16 16 0 00-15-16zm-47 16c0-9-7-16-16-16a16 16 0 00-15 16c0 9 6 16 15 16s16-7 16-16zM245 127h55a56 56 0 017 0c7 0 11 4 13 10 3 6 1 11-3 16l-43 45-62 63c-8 9-17 9-25 1L83 154c-5-5-6-11-4-18 3-7 9-9 15-9h61v-9-99c0-14 5-19 17-19h55c13 0 18 5 18 19v99z"/></g></svg>';
                $icons['download-icon4'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M178 234v-7V24c0-13 8-23 20-24 13-1 24 9 24 23v212l5-5 44-44c10-9 23-10 32-1s9 23-1 33l-85 85c-10 11-23 11-34 0l-85-86a22 22 0 0123-37 28 28 0 018 6l44 44a31 31 0 013 5zM200 400H24c-17 0-28-14-23-29 3-10 12-15 23-16h351c12 0 21 6 24 16 5 15-6 29-22 29H200z"/></g></svg>';
                $icons['preview-icon1'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M200 325c-36 0-70-11-101-30a356 356 0 01-92-80c-9-11-9-19 0-29 37-44 79-79 131-99 51-20 102-14 151 12 41 22 76 52 106 89 6 8 7 16 1 23-39 48-85 85-141 105a167 167 0 01-55 9zm0-47c41 0 75-36 75-81s-34-81-75-81c-42 0-75 36-75 81s34 81 75 81z"/><path d="M200 159c21 0 38 17 38 38 0 20-17 37-38 37s-38-17-38-37c0-21 17-38 38-38z"/></g></svg>';
                $icons['preview-icon2'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M0 195c2-10 9-18 16-26 26-28 54-52 89-70a203 203 0 01202 6c33 20 61 46 86 75 3 4 5 10 7 15v10c-2 10-9 18-16 26-24 26-51 50-84 67a206 206 0 01-207-3c-31-18-57-42-81-69-5-6-10-13-12-21zm199 99c30 0 57-8 82-21 33-18 60-43 84-70 2-2 2-4 0-6-21-24-45-47-74-64-27-16-56-26-88-27-28 0-54 6-79 19-35 17-64 43-89 72-2 2-2 4 0 6 21 24 45 47 74 64 27 17 58 27 90 27z"/><path d="M202 276c-45 1-82-32-84-73-2-42 34-77 79-79s83 31 85 74c1 42-34 76-80 78zm-2-30c27-1 49-21 49-46s-22-46-49-45c-27 0-49 20-49 45s22 46 49 46z"/></g></svg>';
                $icons['preview-icon3'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M201 0l12 12 80 76a9 9 0 012 6v98c0 3 1 5 3 6a83 83 0 0133 122l-2 3 61 59-19 18-60-59-15 8-1 3v19H10v-5V4 0zm68 108h-86V25H36v321h176l-2-2a83 83 0 01-33-38c-1-3-3-4-6-4H66v-25h103l5-33H66v-25h121a8 8 0 005-2c19-19 41-29 68-28h9zm-11 225c34 0 62-27 62-59 0-34-28-61-62-61s-62 27-62 59c0 34 27 61 62 61zm-9-250l-40-39v39z"/></g></svg>';
                $icons['preview-icon4'] = '<svg xmlns="http://www.w3.org/2000/svg" width="45" height="45" viewBox="0 0 400 400"><g fill="#9a9999"><path d="M0 200V55C0 28 16 8 41 2a63 63 0 0115-2h289c32 0 55 23 55 54v248c0 10-6 16-15 16-8 0-14-6-14-16V56c0-16-11-27-28-27H57c-18 0-28 9-28 28v286c0 19 10 28 28 28h243c11 0 18 6 18 15s-7 14-18 14H56c-28 0-49-16-55-41a67 67 0 01-1-15V200z"/><path d="M314 302l15 15 52 51c7 7 7 14 2 20-6 6-13 5-20-2l-67-65a11 11 0 00-1-1 104 104 0 01-149-26c-29-43-20-99 20-133 39-33 99-31 137 4 38 34 45 93 11 137zm-159-64c-1 42 34 77 77 77 42 1 78-33 78-75 1-42-34-77-75-77-44-1-79 32-80 75z"/></g></svg>';

                $icon = isset($icons[$name]) ? $icons[$name] : '';
                $icon = preg_replace('/fill=\"#[a-z0-9]{3,6}\"/', 'fill="' . $color . '"', $icon);
                $icon = preg_replace('/width=\"[0-9]+\"/', 'width="' . $size . '"', $icon);
                $icon = preg_replace('/height=\"[0-9].\"/', 'height="' . $size . '"', $icon);

                return $icon;
            },
            'xif' => function ($param, $second, $options) {
                if ($param === $second) {
                    return $options['fn']();
                } else {
                    return $options['inverse']();
                }
            },
            'xunless' => function ($param, $second, $options) {
                if ($param !== $second) {
                    return $options['fn']();
                } else {
                    return $options['inverse']();
                }
            },
        )
    ));

    $allow_include_url = ini_get('allow_url_include'); // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.allow_url_includeDeprecated -- It will return false if not exists
    if (function_exists('file_put_contents') && (strval($allow_include_url) === '0' || strtolower(strval($allow_include_url)) === 'off' || strval($allow_include_url) === '')) {
        $rendererPath = WP_CONTENT_DIR . $ds . wpfd_get_content_dir() . $ds . 'renderer';
        wpfdCreateSecureFolder($rendererPath);
        $rendererFilePath = $rendererPath . $ds . $renderName . '.php';
        if ($renderName === 'singlefile' && !file_exists($rendererFilePath)) {
            file_put_contents($rendererFilePath, '<?php ' . $php . '?>');
        } elseif ($renderName === 'singlefilecss' && !file_exists($rendererFilePath)) {
            file_put_contents($rendererFilePath, '<?php ' . $php . '?>');
        }

        if (file_exists($rendererFilePath)) {
            $renderer = include($rendererFilePath);
        } else {
            return '';
        }
    } else {
        /**
         * Deprecated
         * This method require php.allow_url_include enable.
         * Unfortunately, almost hosting provider disable this setting for security problem.
         */
        $renderer = LightnCandy\LightnCandy::prepare($php);
    }

    if (is_callable($renderer)) {
        return $renderer($data);
    }

    return '';
}

/**
 * Initialize the plugin
 *
 * Load the plugin only after Elementor (and other plugins) are loaded.
 * Checks for basic plugin requirements, if one check fail don't continue,
 * if all check have passed load the files required to run the plugin.
 *
 * Fired by `plugins_loaded` action hook.
 *
 * @since 1.0.0
 *
 * @access public
 *
 * @return void
 */
function wpfd_elementor_init()
{
    // Add Plugin actions
    if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
        add_action('elementor/widgets/widgets_registered', 'wpfd_elementor_widgets');
    } else {
        add_action('elementor/widgets/register', 'wpfd_elementor_widgets');
    }
    // Register Widget Scripts
    add_action('elementor/frontend/after_register_scripts', 'wpfd_elementor_widget_scripts');
    // Blacklist default search widget
    add_filter('elementor/widgets/black_list', function ($blacklists) {
        $blacklists[] = 'WPFDWidgetSearch';
        return $blacklists;
    });
}
add_action('plugins_loaded', 'wpfd_elementor_init');

/**
 * Elementor widget
 *
 * @return void
 */
function wpfd_elementor_widgets()
{
    // Add backend js
    wpfd_elementor_scripts();
    // Load widget icon style by theme mod
    $ui_theme = \Elementor\Core\Settings\Manager::get_settings_managers('editorPreferences')->get_model()->get_settings('ui_theme');

    if ('auto' === $ui_theme) {
        wp_enqueue_style(
            'wpfd-elementor-widget-dark-style',
            WPFD_PLUGIN_URL . 'app/includes/elementor/assets/css/elementor.dark.css',
            array(
                'wpfd-elementor-widget-style'
            ),
            ELEMENTOR_VERSION,
            '(prefers-color-scheme: dark)'
        );
        wp_enqueue_style(
            'wpfd-elementor-widget-light-style',
            WPFD_PLUGIN_URL . 'app/includes/elementor/assets/css/elementor.light.css',
            array(
                'wpfd-elementor-widget-style'
            ),
            ELEMENTOR_VERSION,
            '(prefers-color-scheme: light)'
        );
    } elseif ('dark' === $ui_theme) {
        wp_enqueue_style(
            'wpfd-elementor-widget-dark-style',
            WPFD_PLUGIN_URL . 'app/includes/elementor/assets/css/elementor.dark.css',
            array(
                'wpfd-elementor-widget-style'
            ),
            ELEMENTOR_VERSION,
            'all'
        );
    } else { // Light mode
        wp_enqueue_style(
            'wpfd-elementor-widget-light-style',
            WPFD_PLUGIN_URL . 'app/includes/elementor/assets/css/elementor.light.css',
            array(
                'wpfd-elementor-widget-style'
            ),
            ELEMENTOR_VERSION,
            'all'
        );
    }
    //Include wpfd file widgets
    require_once WPFD_PLUGIN_DIR_PATH . 'app'. DIRECTORY_SEPARATOR . 'includes'. DIRECTORY_SEPARATOR . 'elementor'. DIRECTORY_SEPARATOR . 'wpfd_file_widget.php';
    require_once WPFD_PLUGIN_DIR_PATH . 'app'. DIRECTORY_SEPARATOR . 'includes'. DIRECTORY_SEPARATOR . 'elementor'. DIRECTORY_SEPARATOR . 'wpfd_category_widget.php';
    require_once WPFD_PLUGIN_DIR_PATH . 'app'. DIRECTORY_SEPARATOR . 'includes'. DIRECTORY_SEPARATOR . 'elementor'. DIRECTORY_SEPARATOR . 'wpfd_search_widget.php';

    //Create new widgets
    if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new WpfdSingleFileWidget\ElementorSingleFileWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new WpfdCategoryWidget\ElementorCategoryWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new WpfdSearchWidget\ElementorSearchWidget());
    } else {
        \Elementor\Plugin::instance()->widgets_manager->register(new WpfdSingleFileWidget\ElementorSingleFileWidget());
        \Elementor\Plugin::instance()->widgets_manager->register(new WpfdCategoryWidget\ElementorCategoryWidget());
        \Elementor\Plugin::instance()->widgets_manager->register(new WpfdSearchWidget\ElementorSearchWidget());
    }
}

/**
 * Elementor assets
 *
 * @return void
 */
function wpfd_elementor_scripts()
{
    // Add Js to wpfd elementor
    wp_enqueue_style('wpfd-modal', WPFD_PLUGIN_URL . 'app/admin/assets/css/leanmodal.css');
    wp_enqueue_script('wpfd-modal', WPFD_PLUGIN_URL . 'app/admin/assets/js/jquery.leanModal.min.js', array('jquery'));
    wp_enqueue_script(
        'wpfd-elementor',
        WPFD_PLUGIN_URL . 'app/includes/elementor/assets/js/jquery.elementor.js',
        array( 'jquery', 'wpfd-modal' )
    );

    wp_localize_script('wpfd-elementor', 'wpfd_elemetor_vars', array(
        'dir' => WPFD_PLUGIN_URL
    ));
    // Add css to wpfd elementor
    wp_enqueue_style(
        'wpfd-elementor-widget-style',
        WPFD_PLUGIN_URL . 'app/includes/elementor/assets/css/elementor.widgets.css',
        array(),
        WPFD_VERSION
    );
    wpfd_assets_search();
}
/**
 * Elementor widgets enqueue scripts.
 *
 * @since 1.0.0
 *
 * @access public
 *
 * @return void
 */
function wpfd_elementor_widget_scripts()
{
    wp_enqueue_script(
        'jquery-elementor-widgets',
        WPFD_PLUGIN_URL . 'app/includes/elementor/assets/js/jquery.elementor.widgets.js',
        array('jquery'),
        WPFD_VERSION
    );
}

/**
 * Get generate preview queue schedule
 *
 * @param integer $schedule Schedule interval time in second. Default 5min
 *
 * @return mixed
 */
function wpfd_get_generate_preview_queue_schedule($schedule)
{
    $interval = (defined('WPFD_GENERATE_PREVIEW_QUEUE_INTERVAL') && WPFD_GENERATE_PREVIEW_QUEUE_INTERVAL > 0) ? WPFD_GENERATE_PREVIEW_QUEUE_INTERVAL : 5 * 60;
    /**
     * Filter to change generate preview interval, this will override WPFD_GENERATE_PREVIEW_QUEUE_INTERVAL constance
     *
     * @param $interval Time in second
     */
    $interval = apply_filters('wpfd_generate_preview_queue_interval', $interval);
    $schedule['wpfd_generate_preview_queue_interval'] = array(
        'interval' => $interval,
        'display'  => esc_html__('WPFD Generate Preview Queue', 'wpfd'),
    );
    return $schedule;
}

/**
 * Get category visibility
 *
 * @param WP_Term|integer $category Category instance or id
 *
 * @return array|boolean
 */
function wpfd_get_category_visibility($category)
{
    if ($category === 0) {
        // This is root category, return public;
        return array('roles' => array(), 'access' => 0);
    }
    if (!$category instanceof WP_Term && is_numeric($category)) {
        $category = get_term($category, 'wpfd-category');
    }

    if (is_wp_error($category)) {
        return false;
    }

    if (is_null($category) || $category && !isset($category->term_id)) {
        return false;
    }

    $termMeta = get_option('taxonomy_' . $category->term_id, false);

    if (!$termMeta) {
        // Category never saved before, return Inherited;
        return wpfd_get_category_visibility($category->parent);
    }

    /**
     * Filters allow setup default visibility
     *
     * @param array Default values: roles, private
     *
     * @return array
     */
    $defaultParams = apply_filters('wpfd_default_category_visibility', array('roles' => array(), 'access' => -1));
    $categoryRoles = isset($termMeta['roles']) ? (array) $termMeta['roles'] : $defaultParams['roles'];
    $categoryAccess = isset($termMeta['access']) ? (int) $termMeta['access'] : $defaultParams['access'];

    if ($categoryAccess === -1 && $category->parent > 0) {
        return wpfd_get_category_visibility($category->parent);
    }

    return array('roles' => $categoryRoles, 'access' => $categoryAccess);
}

/**
 * Handle redirects to setup/welcome page after install and updates.
 *
 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
 *
 * @return void
 */
function wpfd_wizard_setup_redirect()
{
    // Setup wizard redirect
    if (is_null(get_option('_wpfd_installed', null)) || get_option('_wpfd_installed') === 'false') {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
        if ((!empty($_GET['page']) && in_array($_GET['page'], array('wpfd-setup')))) {
            return;
        }

        wp_safe_redirect(admin_url('index.php?page=wpfd-setup'));
        exit;
    }
}

/**
 * Includes WP File Download setup
 *
 * @return void
 */
function wpfd_wizard_setup_include()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
    if (!empty($_GET['page'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
        switch ($_GET['page']) {
            case 'wpfd-setup':
                require_once WPFD_PLUGIN_DIR_PATH . 'app/admin/classes/install-wizard/install-wizard.php';
                break;
        }
    }
}

/**
 * Generate preview queue task
 *
 * @return void
 */
function wpfd_generate_preview_queue_tasks()
{
    $modelPath = WPFD_PLUGIN_DIR_PATH . 'app/admin/models/generatepreview.php';
    if (file_exists($modelPath)) {
        include_once WPFD_PLUGIN_DIR_PATH . 'app/admin/models/generatepreview.php';
        $model = new WpfdModelGeneratepreview();
        $model->runqueue();
    }
}

/**
 * Init schedule tasks
 *
 * @return void
 */
function wpfd_init_tasks()
{
    // Setup cron interval for Statistics Storage be clear
    add_filter('wpfd_get_schedules', 'wpfd_get_remove_statistics_schedule');
    add_filter('wpfd_get_schedules', 'wpfd_get_clean_junks_schedule');
    add_filter('wpfd_get_schedules', 'wpfd_get_generate_preview_queue_schedule');

    wpfd_schedules();

    // Add hook for cronjob
    add_action('wpfd_remove_statistics_tasks', 'wpfd_remove_statistics');
    add_action('wpfd_remove_junks_tasks', 'wpfd_clean_junks');
    add_action('wpfd_generate_preview_queue_tasks', 'wpfd_generate_preview_queue_tasks');

    // Install cronjob
    wpfd_install_job('wpfd_remove_statistics_tasks', 'wpfd_remove_statistics');
    wpfd_install_job('wpfd_remove_junks_tasks', 'wpfd_clean_junks');
    wpfd_install_job('wpfd_generate_preview_queue_tasks', 'wpfd_generate_preview_queue_interval');

    // Reload schedule after main config saved
    add_action('wpfd_after_main_setting_save', 'wpfd_after_main_setting_save');
}

/**
 * Init Divi Modules
 *
 * @return void
 */
if (! function_exists('diviInitializeExtension')) :
    /**
     * Creates the extension's main class instance.
     *
     * @since 1.0.0
     *
     * @return void
     */
    function diviInitializeExtension()
    {
        $config                 = WPFD_PLUGIN_DIR_PATH . '/app/admin/models/config.php';
        include_once $config;
        $configModel            = new WpfdModelConfig();
        $themeList              = $configModel->getThemes();
        require_once WPFD_PLUGIN_DIR_PATH . '/app/includes/divi/includes/Divi.php';
        include_once WPFD_PLUGIN_DIR_PATH . '/app/admin/helpers/WpfdHelperFile.php';
        $themeCustomPath        = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        $themeCustomUrl         = WP_CONTENT_URL . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        $customSingleFilePath   = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'wpfd-single-file-button.css';
        $customSingleFileUrl    = WP_CONTENT_URL . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'wpfd-single-file-button.css';
        $lastRebuildTime        = get_option('wpfd_icon_rebuild_time', false);
        if (false === $lastRebuildTime) {
            $lastRebuildTime = WpfdHelperFile::renderCss();
        }
        $iconsetPngPath         = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'png' . DIRECTORY_SEPARATOR . 'styles-'. $lastRebuildTime .'.css';
        $iconsetPngUrl          = WP_CONTENT_URL . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'png' . DIRECTORY_SEPARATOR . 'styles-'. $lastRebuildTime .'.css';
        $iconsetSvgPath         = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'svg' . DIRECTORY_SEPARATOR . 'styles-'. $lastRebuildTime .'.css';
        $iconsetSvgUrl          = WP_CONTENT_URL . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'svg' . DIRECTORY_SEPARATOR . 'styles-'. $lastRebuildTime .'.css';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is for check only
        if (isset($_GET['et_fb']) && current_user_can('edit_posts')) {
            if (!empty($themeList)) {
                foreach ($themeList as $theme) {
                    if ($theme !== 'default' && $theme !== 'ggd' && $theme !== 'table' && $theme !== 'tree') {
                        if (file_exists($themeCustomPath . 'wpfd-' . $theme)) {
                            wp_enqueue_style(
                                'wpfd-divi-custom-'. $theme .'-theme-style',
                                $themeCustomUrl . 'wpfd-' . $theme . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css',
                                array(),
                                WPFD_VERSION,
                                'all'
                            );
                        }
                    }
                }
            }

            if (file_exists($customSingleFilePath)) {
                wp_enqueue_style(
                    'wpfd-divi-custom-file-buttons-style',
                    $customSingleFileUrl,
                    array(),
                    WPFD_VERSION,
                    'all'
                );
            } else {
                wp_enqueue_style(
                    'wpfd-divi-file-buttons-style',
                    WPFD_PLUGIN_URL . 'app/site/assets/css/wpfd-single-file-button.css',
                    array(),
                    WPFD_VERSION,
                    'all'
                );
            }

            if (file_exists($iconsetPngPath)) {
                wp_enqueue_style(
                    'wpfd-divi-iconset-png-style',
                    $iconsetPngUrl,
                    array(),
                    WPFD_VERSION,
                    'all'
                );
            }

            if (file_exists($iconsetSvgPath)) {
                wp_enqueue_style(
                    'wpfd-divi-iconset-svg-style',
                    $iconsetSvgUrl,
                    array(),
                    WPFD_VERSION,
                    'all'
                );
            }
            wp_enqueue_style(
                'wpfd-divi-modules-style',
                WPFD_PLUGIN_URL . 'app/admin/assets/css/divi-module-style.css',
                array(),
                WPFD_VERSION,
                'all'
            );

            wp_enqueue_style(
                'wpfd-divi-category-style',
                WPFD_PLUGIN_URL . 'app/admin/assets/css/divi-category-style.css',
                array(),
                WPFD_VERSION,
                'all'
            );

            wp_enqueue_style(
                'wpfd-divi-file-style',
                WPFD_PLUGIN_URL . 'app/admin/assets/css/divi-file-style.css',
                array(),
                WPFD_VERSION,
                'all'
            );

            wp_enqueue_style(
                'wpfd-divi-search-style',
                WPFD_PLUGIN_URL . 'app/admin/assets/css/divi-search-style.css',
                array(),
                WPFD_VERSION,
                'all'
            );

            wpfd_assets_search();
        }
    }
    add_action('divi_extensions_init', 'diviInitializeExtension');
endif;


if (!function_exists('wpfd_initializeWPBakery')) {
    /**
     * Creates the extension's main class instance.
     *
     * @since 1.0.0
     *
     * @return void
     */
    function wpfd_initializeWPBakery()
    {

        if (!defined('WPB_VC_VERSION')) {
            return;
        }

        require_once(WPFD_PLUGIN_DIR_PATH . '/app/includes/wpbakery/category.php');
        require_once(WPFD_PLUGIN_DIR_PATH . '/app/includes/wpbakery/file.php');
        require_once(WPFD_PLUGIN_DIR_PATH . '/app/includes/wpbakery/search.php');

        if (is_admin()) {
            //backend enqueue
            add_action('vc_backend_editor_enqueue_js_css', 'wpfd_wpbakery_enqueue_assets');

            //frontend enqueue
            add_action('vc_frontend_editor_enqueue_js_css', 'wpfd_wpbakery_enqueue_assets');
        }

        wp_enqueue_style(
            'wpfd-wpbakery-style',
            WPFD_PLUGIN_URL . 'app/includes/wpbakery/assets/css/wpbakery.css',
            array(),
            WPFD_VERSION,
            'all'
        );
    }
    add_action('init', 'wpfd_initializeWPBakery');
}

/**
 * WPBakery enqueue assets
 *
 * @return void
 */
function wpfd_wpbakery_enqueue_assets()
{

    wp_enqueue_style(
        'wpfd-wpbakery-category-style',
        WPFD_PLUGIN_URL . 'app/includes/wpbakery/assets/css/category.css',
        array(),
        WPFD_VERSION,
        'all'
    );

    wp_enqueue_style(
        'wpfd-wpbakery-file-style',
        WPFD_PLUGIN_URL . 'app/includes/wpbakery/assets/css/file.css',
        array(),
        WPFD_VERSION,
        'all'
    );

    wp_enqueue_style(
        'wpfd-wpbakery-search-style',
        WPFD_PLUGIN_URL . 'app/includes/wpbakery/assets/css/search.css',
        array(),
        WPFD_VERSION,
        'all'
    );

    wp_enqueue_style('wpfd-wpbakery-lean-modal', WPFD_PLUGIN_URL . 'app/admin/assets/css/leanmodal.css');

    wp_enqueue_script('wpfd-wpbakery-init-modal', WPFD_PLUGIN_URL . 'app/admin/assets/js/jquery.leanModal.min.js', array('jquery'));

    wp_enqueue_script(
        'wpfd-wpbakery-modal',
        WPFD_PLUGIN_URL . 'app/includes/wpbakery/assets/js/jquery.wpbakery.js',
        array( 'jquery', 'wpfd-wpbakery-init-modal' )
    );

    wp_localize_script('wpfd-wpbakery-modal', 'wpfd_wpbakery_vars', array(
        'dir' => WPFD_PLUGIN_URL
    ));
}

/**
 * Get ABSPATH
 *
 * @return string|null
 */
function wpfd_get_abspath()
{
    if (defined('ABSPATH') && ABSPATH) {
        return ABSPATH;
    }

    $pInfo = null;
    if (defined('WPFD_PLUGIN_DIR_PATH') && WPFD_PLUGIN_DIR_PATH) {
        $pInfo = pathinfo(WPFD_PLUGIN_DIR_PATH);
        $pInfo = explode(DIRECTORY_SEPARATOR, $pInfo['dirname']);
        array_splice($pInfo, count($pInfo) - 2, 2);
        $pInfo = implode(DIRECTORY_SEPARATOR, $pInfo);
    }

    return $pInfo;
}

if (!function_exists('wpfd_initializeAvada')) {

    /**
     * Wpfd_initializeAvada
     *
     * @return void
     */
    function wpfd_initializeAvada()
    {

        if (!defined('AVADA_VERSION') || !defined('FUSION_BUILDER_VERSION')) {
            return;
        }

        require_once(WPFD_PLUGIN_DIR_PATH . '/app/includes/avada/category.php');
        require_once(WPFD_PLUGIN_DIR_PATH . '/app/includes/avada/file.php');
        require_once(WPFD_PLUGIN_DIR_PATH . '/app/includes/avada/search.php');

        if (fusion_is_builder_frame()) {
            add_action('wp_enqueue_scripts', 'wpfd_avada_enqueue_assets', 999);
        }

        if (class_exists('Fusion_App')) {
            $fusion_app_instance = Fusion_App::get_instance();
            if ($fusion_app_instance->is_builder) {
                add_action('wp_enqueue_scripts', 'wpfd_avada_live_scripts', 997);
            }
        }

        if (is_admin()) {
            add_action('fusion_builder_admin_scripts_hook', 'wpfd_avada_live_scripts');
        }
    }
    add_action('init', 'wpfd_initializeAvada');
}

/**
 * Wpfd_avada_enqueue_assets
 *
 * @return void
 */
function wpfd_avada_enqueue_assets()
{

    wp_enqueue_style(
        'wpfd-avada-style',
        WPFD_PLUGIN_URL . 'app/includes/avada/assets/css/avada.css',
        array(),
        WPFD_VERSION,
        'all'
    );
}

/**
 * Wpfd_avada_live_scripts
 *
 * @return void
 */
function wpfd_avada_live_scripts()
{

    wp_enqueue_style('wpfd-avada-lean-modal', WPFD_PLUGIN_URL . 'app/admin/assets/css/leanmodal.css');

    wp_enqueue_script('wpfd-avada-lean-modal', WPFD_PLUGIN_URL . 'app/admin/assets/js/jquery.leanModal.min.js', array('jquery'));

    wp_enqueue_script(
        'wpfd-avada-modal',
        WPFD_PLUGIN_URL . 'app/includes/avada/assets/js/jquery.avada.js',
        array( 'jquery', 'wpfd-avada-lean-modal' )
    );

    wp_localize_script('wpfd-avada-modal', 'wpfd_avada_vars', array(
        'dir'       => WPFD_PLUGIN_URL,
        'adminurl'  => admin_url()
    ));
}

/**
 * Wpfd_file_upload_pending_status
 *
 * @param string|integer $fileId     File id
 * @param string|integer $categoryId Category id
 *
 * @return boolean
 */
function wpfd_file_upload_pending_status($fileId, $categoryId)
{
    $isPending = false;
    if (is_numeric($fileId)) {
        $fileMetaUploaded = get_post_meta($fileId, '_wpfd_file_meta_uploaded_by', true);
        $fileStatus = get_post_status($fileId);
        if (isset($fileMetaUploaded) && $fileMetaUploaded !== '' && (int)$fileMetaUploaded !== 0 && $fileStatus !== 'publish') {
            $isPending = true;
        } else {
            $isPending = false;
        }
    } else {
        $cloudPlace = apply_filters('wpfdAddonCategoryFrom', $categoryId);
        if (in_array($cloudPlace, wpfd_get_support_cloud())) {
            switch ($cloudPlace) {
                case 'googleDrive':
                    $fileUploaded = get_option('_wpfdAddon_google_file_user_upload');
                    if (is_array($fileUploaded) && !empty($fileUploaded)) {
                        if (array_key_exists((string) $fileId, $fileUploaded)) {
                            $isPending = true;
                        } else {
                            $isPending = false;
                        }
                    }
                    break;
                case 'googleTeamDrive':
                    $fileUploaded = get_option('_wpfdAddon_google_team_drive_file_user_upload');
                    if (is_array($fileUploaded) && !empty($fileUploaded)) {
                        if (array_key_exists((string) $fileId, $fileUploaded)) {
                            $isPending = true;
                        } else {
                            $isPending = false;
                        }
                    }
                    break;
                case 'dropbox':
                    $dropboxFileUserUpload = get_option('_wpfdAddon_dropbox_file_user_upload');
                    if (is_array($dropboxFileUserUpload) && !empty($dropboxFileUserUpload)) {
                        if (array_key_exists((string) $fileId, $dropboxFileUserUpload)) {
                            $isPending = true;
                        } else {
                            $isPending = false;
                        }
                    } else {
                        $isPending = false;
                    }
                    break;
                case 'onedrive':
                    $onedriveFileUserUpload = get_option('_wpfdAddon_onedrive_file_user_upload');
                    if (is_array($onedriveFileUserUpload) && !empty($onedriveFileUserUpload)) {
                        if (array_key_exists((string) $fileId, $onedriveFileUserUpload)) {
                            $isPending = true;
                        } else {
                            $isPending = false;
                        }
                    } else {
                        $isPending = false;
                    }
                    break;
                case 'onedrive_business':
                    $onedriveBusinessFileUserUpload = get_option('_wpfdAddon_onedrive_business_file_user_upload');
                    if (is_array($onedriveBusinessFileUserUpload) && !empty($onedriveBusinessFileUserUpload)) {
                        if (array_key_exists((string) $fileId, $onedriveBusinessFileUserUpload)) {
                            $isPending = true;
                        } else {
                            $isPending = false;
                        }
                    } else {
                        $isPending = false;
                    }
                    break;
                case 'aws':
                    $awsFileUserUpload = get_option('_wpfdAddon_aws_file_user_upload');
                    if (is_array($awsFileUserUpload) && !empty($awsFileUserUpload)) {
                        if (array_key_exists((string) $fileId, $awsFileUserUpload)) {
                            $isPending = true;
                        } else {
                            $isPending = false;
                        }
                    } else {
                        $isPending = false;
                    }
                    break;
                default:
                    $isPending = false;
                    break;
            }
        }
    }

    return $isPending;
}

/**
 * Wpfd_file_enable_status
 *
 * @param obj $file File obj
 *
 * @return boolean
 */
function wpfd_file_enable_status($file)
{
    $disable = false;

    if ((isset($file->post_status) && $file->post_status === 'private')
        || (isset($file->state) && (int) $file->state === 0)) {
        $disable = true;
    }

    return $disable;
}

/**
 * Default folder colors
 *
 * @return array
 */
function wpfd_default_folder_colors()
{
    return array('#b2b2b2', '#ac725e', '#d06b64', '#f83a22', '#fa573c', '#ff7537', '#ffad46', '#42d692', '#16a765', '#7bd148', '#b3dc6c', '#fbe983', '#fad165', '#92e1c0', '#9fe1e7', '#9fc6e7', '#4986e7', '#9a9cff', '#b99aff', '#8f8f8f', '#cabdbf', '#cca6ac', '#f691b2', '#cd74e6', '#a47ae2', '#a47ae2');
}

/**
 * Whether category, file requires password and correct password has been provided.
 *
 * @param integer|mixed|null $object An optional post. Global $object used if not provided.
 * @param integer|mixed|null $type   Type of object.
 *
 * @return boolean False if a password is not required or the correct password cookie is present, true otherwise.
 */
function wpfdPasswordRequired($object = null, $type = 'category')
{

    $param = array();
    $id    = 0;
    $userId = get_current_user_id();

    if ($type === 'category') {
        $id    = $object->term_id;
        $param = $object->params;

        if (empty($param['category_password'])) {
            return false;
        }

        if (!isset($_COOKIE['wp-wpfd-user-login-category-' . $id])
            || (int) $_COOKIE['wp-wpfd-user-login-category-' . $id] !== (int) $userId) {
            return true;
        }

        if (! isset($_COOKIE[ 'wp-wpfd-password-category-' . $id . '_' . COOKIEHASH ])) {
            return true;
        }

        require_once ABSPATH . WPINC . '/class-phpass.php';
        $hasher = new PasswordHash(8, true);

        $hash = wp_unslash($_COOKIE[ 'wp-wpfd-password-category-' . $id . '_' . COOKIEHASH ]);

        if (0 !== strpos($hash, '$P$B')) {
            $required = true;
        } else {
            $required = ! $hasher->CheckPassword($param['category_password'], $hash);
        }

        return $required;
    } elseif ($type === 'file') {
        if (is_object($object) && isset($object->ID)) {
            $id = $object->ID;
        } elseif (is_array($object) && isset($object['ID'])) {
            $id = $object['ID'];
        } else {
            $id = 0;
        }

        if (is_numeric($id)) {
            $row = get_post($object->ID, ARRAY_A);
            $param['file_password'] = isset($row['post_password']) ? $row['post_password'] : '';
        } else {
            $param = (array) $object;
        }

        if (empty($param['file_password'])) {
            return false;
        }

        if (!isset($_COOKIE['wp-wpfd-user-login-file-' . $id])
            || (int) $_COOKIE['wp-wpfd-user-login-file-' . $id] !== (int) $userId) {
            return true;
        }

        if (! isset($_COOKIE[ 'wp-wpfd-password-file-' . $id . '_' . COOKIEHASH ])) {
            return true;
        }

        require_once ABSPATH . WPINC . '/class-phpass.php';
        $hasher = new PasswordHash(8, true);

        $hash = wp_unslash($_COOKIE[ 'wp-wpfd-password-file-' . $id . '_' . COOKIEHASH ]);

        if (0 !== strpos($hash, '$P$B')) {
            $required = true;
        } else {
            $required = ! $hasher->CheckPassword($param['file_password'], $hash);
        }

        return $required;
    } else {
        return false;
    }
}

/**
 * Retrieve protected wpfd password form content.
 *
 * @param integer|mixed $object     Optional. Post ID or WP_Post object.
 * @param integer|mixed $type       Type of object.
 * @param integer|mixed $categoryId Category id.
 *
 * @return string HTML content for password form for password protected post.
 */
function wpfdGetPasswordForm($object, $type = 'category', $categoryId = 0)
{
    if (!isset($object)) {
        return '';
    }

    $cookies = $_COOKIE;
    $wrongPw = false;
    $message = false;

    if ($type === 'category') {
        $id = (isset($object->term_id)) ? $object->term_id : 0;
    } elseif ($type === 'file') {
        if (gettype($object) === 'object') {
            $id = (isset($object->ID)) ? $object->ID : 0;
        } elseif (gettype($object) === 'array') {
            $id = (isset($object['ID'])) ? $object['ID'] : 0;
        }
    } else {
        $id = 0;
    }

    if (isset($cookies) && !empty($cookies)) {
        if (array_key_exists('wp-wpfd-file-wrong-password', $cookies)) {
            $missFileId = $cookies['wp-wpfd-file-wrong-password'];
            $wrongPw = true;
        }

        if (array_key_exists('wp-wpfd-category-wrong-password', $cookies)) {
            $missCategoryId = $cookies['wp-wpfd-category-wrong-password'];
            $wrongPw = true;
        }
    }

    if ($wrongPw) {
        if (isset($missFileId) && (string) $missFileId === (string) $id) {
            $message = true;
        }

        if (isset($missCategoryId) && (int) $missCategoryId === (int) $id) {
            $message = true;
        }
    }

    $echo = ($message === true) ? '<p class="password-message" style="color: #dc3545; margin: 2px 0;" >' . __('Password is not matched.', 'wpfd') . '</p>' : '';
    $label = 'pwbox-' . ( empty($id) ? rand() : $id );
    $output = '<form action="' . admin_url('admin-ajax.php') . '?juwpfisadmin=false&action=wpfd&task=file.processPasswordProtection" class="wpfd-password-form" method="post">
    <p class="message-info">' . __('This content is password protected. To view it please enter your password below:', 'wpfd') . '</p>' . $echo . '
    <div class="password-type-and-submit-section" data-type="' . $type . '" data-id="' . $id . '">
    <input name="wpfd_password_value" class="wpfd-password-value" id="' . $label . '" type="password" placeholder="Password..." size="20" />
    <input type="submit" name="submit" class="wpfd-password-submit btn btn-primary" value="' . esc_html__('Enter', 'wpfd') . '" />
    <input type="hidden" name="wpfd_password_id" class="wpfd-password-id" value="' . $id . '"/>
    <input type="hidden" name="wpfd_password_type" class="wpfd-password-type" value="' . $type . '"/>
    <input type="hidden" name="wpfd_password_category_id" class="wpfd-password-category-id" value="' . $categoryId . '"/></div></form>';

    /**
     * Filters the HTML output for the protected post password form.
     *
     * If modifying the password field, please note that the core database schema
     * limits the password field to 20 characters regardless of the value of the
     * size attribute in the form input.
     *
     * @param string  $output The password form HTML output.
     * @param WP_Post $object   Object.
     */
    return apply_filters('wpfd_password_form', $output, $object);
}

if (!function_exists('wpfdClearAuthCookies')) {
    /**
     * WpfdClearAuthCookies
     *
     * @return void
     */
    function wpfdClearAuthCookies()
    {
        if (!class_exists('WpfdModelCategoriesfront')) {
            $categoriesPath = WPFD_PLUGIN_DIR_PATH . '/app/site/models/categoriesfront.php';
            include_once $categoriesPath;
        }
        $categoriesModel = new WpfdModelCategoriesfront();
        $categories      = $categoriesModel->getLevelCategories();

        if (!empty($categories)) {
            foreach ($categories as $key => $category) {
                $cookiesCategoryPasswordKey = 'wp-wpfd-password-category-' . (string) $category->term_id . '_' . COOKIEHASH;
                if (!empty($_COOKIE) && array_key_exists($cookiesCategoryPasswordKey, $_COOKIE)) {
                    setcookie($cookiesCategoryPasswordKey, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                }
            }
        }

        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $cookieKey => $cookieValue) {
                if (strpos($cookieKey, 'wp-wpfd-password-file') !== false) {
                    $cookiesFilePasswordKey = $cookieKey;
                    setcookie($cookiesFilePasswordKey, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                } else {
                    if (strpos($cookieKey, 'wp-wpfd-user-login') !== false) {
                        $cookiesUserPasswordKey = $cookieKey;
                        setcookie($cookiesUserPasswordKey, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                    }
                }
            }

            if (array_key_exists('wp-wpfd-file-wrong-password', $_COOKIE)) {
                setcookie('wp-wpfd-file-wrong-password', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            }

            if (array_key_exists('wp-wpfd-category-wrong-password', $_COOKIE)) {
                setcookie('wp-wpfd-category-wrong-password', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
    }
    add_action('wp_logout', 'wpfdClearAuthCookies');
}

if (!function_exists('wpfdRemoteFile')) {
    /**
     * Check is remote file
     *
     * @param integer|string $fileId File id
     *
     * @return boolean
     */
    function wpfdRemoteFile($fileId)
    {
        $remoteFile = false;

        if (!is_int($fileId)) {
            return false;
        }

        $metadata = get_post_meta($fileId, '_wpfd_file_metadata', true);
        if (isset($metadata['remote_url']) && (int) $metadata['remote_url'] === 1) {
            $remoteFile = true;
        }

        return $remoteFile;
    }
}

if (!function_exists('wpfdShowUploadForm')) {
    /**
     * Check show upload form
     *
     * @param object|mixed $cate      Category params
     * @param string       $themeName Theme name
     *
     * @return boolean
     */
    function wpfdShowUploadForm($cate, $themeName = '')
    {
        if (!$cate) {
            return 0;
        }

        $modelConfigPath = WPFD_PLUGIN_DIR_PATH . '/app/site/models/configfront.php';
        require_once $modelConfigPath;
        $globalConfig = get_option('_wpfd_global_config');
        $modelConfig = new WpfdModelConfigfront();
        $showUploadForm = 0;

        if (isset($cate->params['theme'])) {
            $prefix = ($cate->params['theme'] === 'default') ? '' : $cate->params['theme'] . '_';
        } else {
            $prefix = '';
        }

        // Method to load upload form correctly
        if (isset($globalConfig['catparameters']) && (int) $globalConfig['catparameters'] === 1) {
            if (isset($globalConfig['themesettings']) && (int) $globalConfig['themesettings'] === 1) {
                if (!$themeName || $cate->params['theme'] === $themeName) {
                    $showUploadForm = (isset($cate->params[$prefix . 'showuploadform']) && intval($cate->params[$prefix . 'showuploadform']) === 0) ? 0 : 1;
                } else {
                    $prefix = ($themeName === 'default') ? '' : $themeName . '_';
                    $defaultParams  = $modelConfig->getConfig($themeName);
                    $showUploadForm = (isset($defaultParams[$prefix . 'showuploadform']) && intval($defaultParams[$prefix . 'showuploadform']) === 0) ? 0 : 1;
                }
            } else {
                if (!$themeName) {
                    $themeName = $cate->params['theme'];
                } else {
                    $prefix = ($themeName === 'default') ? '' : $themeName . '_';
                }
                $defaultParams  = $modelConfig->getConfig($cate->params['theme']);
                $showUploadForm = (isset($defaultParams[$prefix . 'showuploadform']) && intval($defaultParams[$prefix . 'showuploadform']) === 0) ? 0 : 1;
            }
        } else {
            $defaultTheme = isset($globalConfig['defaultthemepercategory']) ? $globalConfig['defaultthemepercategory'] : 'default';
            $defaultParams = $modelConfig->getConfig($defaultTheme);
            $prefix = ($defaultTheme === 'default') ? '' : $defaultTheme . '_';
            if (isset($globalConfig['themesettings']) && (int) $globalConfig['themesettings'] === 1) {
                $showUploadForm = (isset($cate->params[$prefix . 'showuploadform']) && intval($cate->params[$prefix . 'showuploadform']) === 0) ? 0 : 1;
            } else {
                $showUploadForm = (isset($defaultParams[$prefix . 'showuploadform']) && intval($defaultParams[$prefix . 'showuploadform']) === 0) ? 0 : 1;
            }
        }

        return $showUploadForm;
    }
}

if (!function_exists('wpfdSetFileMultiCategories')) {
    /**
     * Save multiple category to file meta
     *
     * @param string  $id_file             File id
     * @param array   $file_multi_category Category list
     * @param integer $idCategory          Category id
     * @param integer $idRefCategory       Category ref id
     *
     * @return void
     */
    function wpfdSetFileMultiCategories($id_file, $file_multi_category, $idCategory, $idRefCategory = 0)
    {
        if (!isset($id_file) || !$id_file || $id_file === '') {
            return;
        }

        if (is_null($file_multi_category)) {
            $file_multi_category = array();
        }

        if (!is_array($file_multi_category) || empty($file_multi_category)) {
            return;
        }

        $filePath     = WPFD_PLUGIN_DIR_PATH . '/app/admin/models/file.php';
        $categoryPath = WPFD_PLUGIN_DIR_PATH . '/app/admin/models/category.php';

        if (!class_exists('WpfdModelFile') && file_exists($filePath)) {
            include_once $filePath;
        }

        if (!class_exists('WpfdModelCategory') && file_exists($categoryPath)) {
            include_once $categoryPath;
        }

        $modelCat     = new WpfdModelCategory();
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $idCategory);

        if (in_array($categoryFrom, wpfd_get_support_cloud())) {
            /**
             * Filters to set addon file multi categories
             *
             * @param string|integer|mixed File id
             *
             * @internal
             *
             * @return boolean
             */
            $cloudResult = apply_filters('wpfd_addon_set_file_multi_categories', $id_file, $file_multi_category, $categoryFrom, $idCategory);
            if ($cloudResult['success']) {
                $file_multi_category_old = isset($cloudResult['file_multi_category_old']) ? $cloudResult['file_multi_category_old'] : array();
                wpfdSaveRefToFiles($modelCat, $id_file, $file_multi_category, $file_multi_category_old, $idCategory, $idRefCategory);
            }
        } else {
            $file_multi_category_old = wpfdGetFileMultiCategories($id_file);
            $metadata = get_post_meta($id_file, '_wpfd_file_metadata', true);
            $metadata['file_multi_category'] = $file_multi_category;
            $metadata['file_multi_category_old'] = implode(',', $file_multi_category);
            update_post_meta($id_file, '_wpfd_file_metadata', $metadata);
            wpfdSaveRefToFiles($modelCat, $id_file, $file_multi_category, $file_multi_category_old, $idCategory, $idRefCategory);
        }
    }
}

if (!function_exists('wpfdGetFileMultiCategories')) {
    /**
     * Get multiple category list
     *
     * @param string $id_file File id
     *
     * @return void|array|mixed
     */
    function wpfdGetFileMultiCategories($id_file)
    {
        if (!isset($id_file) || !$id_file || $id_file === '') {
            return;
        }

        $metadata = get_post_meta($id_file, '_wpfd_file_metadata', true);
        $file_multi_category = array();

        if (isset($metadata['file_multi_category'])) {
            $file_multi_category = isset($metadata['file_multi_category']) ? $metadata['file_multi_category'] : array();
        }

        if (gettype($file_multi_category) !== 'array') {
            $file_multi_category = (array) $file_multi_category;
        }

        return $file_multi_category;
    }
}

if (!function_exists('wpfdSaveRefToFiles')) {
    /**
     * Save global multiple category to file meta
     *
     * @param mixed   $modelCat                Category model
     * @param array   $id_file                 File id
     * @param array   $file_multi_category     Category list
     * @param string  $file_multi_category_old Old category list
     * @param integer $idCategory              Category id
     * @param integer $idRefCategory           Category ref id
     *
     * @return void
     */
    function wpfdSaveRefToFiles($modelCat, $id_file, $file_multi_category, $file_multi_category_old, $idCategory, $idRefCategory)
    {
        if ((int) $idRefCategory !== 0 && (int) $idCategory !== (int) $idRefCategory) {
            $idCategory = $idRefCategory;
        }

        $lst_catRef_del = array();

        if ((!empty($file_multi_category_old) && $file_multi_category) && $file_multi_category_old) {
            $lst_catRef_del = array_diff($file_multi_category_old, $file_multi_category);
        }

        if (!empty($file_multi_category) && $file_multi_category) {
            foreach ($file_multi_category as $value) {
                if (!is_null($value) && !empty($value) && trim($value) !== '') {
                    $modelCat->saveRefToFiles($value, $id_file, $idCategory);
                }
            }

            if (!empty($lst_catRef_del) && $lst_catRef_del) {
                foreach ($lst_catRef_del as $value) {
                    if (trim($value) !== '') {
                        $modelCat->deleteRefToFiles($value, $id_file, $idCategory);
                    }
                }
            }
        } elseif (!empty($file_multi_category_old)) {
            foreach ($file_multi_category_old as $value) {
                if (trim($value) !== '') {
                    $modelCat->deleteRefToFiles($value, $id_file, $idCategory);
                }
            }
        }
    }
}

if (!function_exists('wpfd_correct_wpml_language')) {
    /**
     * Correct wpml language
     *
     * @param string|integer $fileId Id of file
     * @param string|integer $catId  Id of category
     *
     * @return array
     */
    function wpfd_correct_wpml_language($fileId, $catId)
    {
        global $sitepress;
        $wpfdLang = '';
        $currentFileId = $fileId;
        $currentCatId = $catId;
        if (!empty($sitepress)) {
            $language_negotiation_type = $sitepress->get_setting('language_negotiation_type');
            // Different languages in directories
            if ((int)$language_negotiation_type === 1) {
                /**
                 * Filters to get current language from WP Multi Lang
                 *
                 * @return string
                 *
                 * @ignore
                 */
                $current_lang = apply_filters('wpml_current_language', null);
                $default_lang = $sitepress->get_default_language();
                $setting_urls = $sitepress->get_setting('urls');
                $directory_for_default_language = $setting_urls['directory_for_default_language'];

//                    if ((!empty($current_lang) && $current_lang !== $default_lang) || ($current_lang === $default_lang && !empty($setting_urls['directory_for_default_language']))) {
//                        $wpfdLang = '/' . $current_lang;
//                        if ($site_url !== $home_url) {
//                            $wpfdLang = '';
//                        }
//                    }

                $catTrid = apply_filters('wpml_element_trid', null, $catId, 'tax_wpfd-category');
                $fileTrid = apply_filters('wpml_element_trid', null, $fileId, 'post_wpfd_file');

                $translatedCat = apply_filters('wpml_get_element_translations', null, $catTrid, 'tax_wpfd-category');
                $translatedFile = apply_filters('wpml_get_element_translations', null, $fileTrid, 'post_wpfd_file');

                // When wpfd_file translation set to fall back to default language, we need remove language suffix from download url too.
                $custom_posts_sync_option = $sitepress->get_setting('custom_posts_sync_option');
                // 1: Use translated version only
                // 2: Use fallback version
                // 3: Do not translate
                if (!empty($custom_posts_sync_option['wpfd_file']) && (int)$custom_posts_sync_option['wpfd_file'] === 1) {
                    if (!empty($translatedFile[$default_lang]) && !empty($translatedFile[$current_lang])) {
                        // File was translated to current language
                        if (isset($translatedCat[$default_lang]) && isset($translatedCat[$default_lang]->element_id)) {
                            $currentCatId = $translatedCat[$default_lang]->element_id;
                        }

                        if (isset($translatedFile[$default_lang]) && isset($translatedFile[$default_lang]->element_id)) {
                            $currentFileId = $translatedFile[$default_lang]->element_id;
                        }
                    }

                    if (!empty($directory_for_default_language) && $current_lang === $default_lang) {
                        $wpfdLang = '/' . $default_lang;
                    }

                    if (empty($directory_for_default_language) && $current_lang !== $default_lang) {
                        $wpfdLang = '/' . $current_lang;
                    }
                }
                if (!empty($custom_posts_sync_option['wpfd_file']) && (int)$custom_posts_sync_option['wpfd_file'] === 2) {
                    // Fallback enabled for wpfd_file, remove language suffix.
                    // This applies for the untranslated file only
                    if (!empty($translatedFile[$default_lang]) && empty($translatedFile[$current_lang])) {
                        // Fallback id to default_lang
                        if (!empty($directory_for_default_language)) {
                            $wpfdLang = '/' . $default_lang;
                        }

                        if (isset($translatedCat[$default_lang]) && isset($translatedCat[$default_lang]->element_id)) {
                            $currentCatId = $translatedCat[$default_lang]->element_id;
                        }

                        if (isset($translatedFile[$default_lang]) && isset($translatedFile[$default_lang]->element_id)) {
                            $currentFileId = $translatedFile[$default_lang]->element_id;
                        }
                    } elseif (empty($translatedFile[$default_lang]) && !empty($translatedFile[$current_lang])) {
                        // File uploaded to this language only
                        $wpfdLang = '/' . $current_lang;

                        if (isset($translatedCat[$current_lang]) && isset($translatedCat[$current_lang]->element_id)) {
                            $currentCatId = $translatedCat[$current_lang]->element_id;
                        }

                        if (isset($translatedFile[$current_lang]) && isset($translatedFile[$current_lang]->element_id)) {
                            $currentFileId = $translatedFile[$current_lang]->element_id;
                        }
                    } elseif (!empty($translatedFile[$default_lang]) && !empty($translatedFile[$current_lang])) {
                        // File was translated to current language
                        $wpfdLang = '';
                        if (!empty($directory_for_default_language)) {
                            $wpfdLang = '/' . $default_lang;
                        }

                        if (isset($translatedCat[$default_lang]) && isset($translatedCat[$default_lang]->element_id)) {
                            $currentCatId = $translatedCat[$default_lang]->element_id;
                        }

                        if (isset($translatedFile[$default_lang]) && isset($translatedFile[$default_lang]->element_id)) {
                            $currentFileId = $translatedFile[$default_lang]->element_id;
                        }
                    }
                }
            }
        }

        if (empty($currentCatId)) {
            $currentCatId = $catId;
        }

        return [$currentFileId, $currentCatId, $wpfdLang];
    }
}

add_action('wpfdSyncServerFolder', 'wpfdSyncServerFolder');
/**
 * Sync server folder
 *
 * @param boolen $sync_now Optional
 *
 * @return void
 */
function wpfdSyncServerFolder($sync_now = false)
{
    set_time_limit(0);
    $lists = get_option('wpfd_list_folder_sync');
    $config = get_option('_wpfd_global_config');
    $config['last_run_server_sync_time'] = time();
    update_option('_wpfd_global_config', $config);

    if (!class_exists('\Joomunited\Queue\JuMainQueue')) {
        require_once WPFD_PLUGIN_DIR_PATH . 'queue/JuMainQueue.php';
    }

    $sync = $config['server_sync'];
    if ($sync) {
        \Joomunited\WPFramework\v1_0_6\Application::getInstance('Wpfd', WPFD_PLUGIN_FILE, 'admin');
        $wpfdQueue = \Joomunited\Queue\JuMainQueue::getInstance('wpfd');
        if ($sync_now) {
            $wpfdQueue->proceedQueueAsync();
        } else {
            $allowed = explode(',', $config['server_sync_allowedext']);
            $allowed = array_map('trim', $allowed);
            foreach ($lists as $folderId => $v) {
                if (file_exists($v['folder_ftp'])) {
                    // add to queue
                    if (!empty($sync)) {
                        $directory = $v['folder_ftp'];
                        $dir_files = glob($directory . '*');
                        foreach ($dir_files as $dir_file) {
                            if (!is_readable($dir_file)) {
                                continue;
                            }

                            $validate_path = str_replace('//', '/', $dir_file);
                            $name = basename($validate_path);
                            $datas = array(
                                'path' => $dir_file,
                                'server_parent' => $directory,
                                'folder_parent' => $folderId,
                                'action' => 'wpfd_sync_ftp_to_category'
                            );
                            if (is_dir($dir_file)) {
                                $datas['name'] = $name;
                                $datas['type'] = 'folder';
                            } else {
                                $info_file = wp_check_filetype($dir_file);
                                if (!in_array(strtolower($info_file['ext']), $allowed)) {
                                    continue;
                                }

                                if (filesize($datas['path']) === 0) {
                                    continue;
                                }

                                $datas['name'] = $name;
                                $datas['hash'] = md5($dir_file);
                                $datas['type'] = 'file';
                            }

                            $row = $wpfdQueue->checkQueueExist(json_encode($datas));
                            if (!$row) {
                                $wpfdQueue->addToQueue($datas);
                            } else {
                                $controllerConfigPath = WPFD_PLUGIN_DIR_PATH . '/app/admin/controllers/config.php';
                                require_once $controllerConfigPath;
                                $configController = new WpfdControllerConfig();

                                $responses = json_decode($row->responses, true);
                                if (is_dir($dir_file)) {
                                    if (isset($responses['folder_id'])) {
                                        \Joomunited\WPFramework\v1_0_6\Application::getInstance('Wpfd', WPFD_PLUGIN_FILE, 'admin');
                                        $categoryModel = \Joomunited\WPFramework\v1_0_6\Model::getInstance('category');
                                        if ($categoryModel->getCategory($responses['folder_id']) === false) {
                                            wpfdAddToQueue($datas);
                                        } else {
                                            $configController->doAddSyncFtpQueue($datas['path'] . DIRECTORY_SEPARATOR, (int)$responses['folder_id']);
                                        }
                                    }
                                } else {
                                    wpfdAddToQueue($datas);
                                }

                                if (isset($responses['folder_id'])) {
                                    $configController->doAddExternalSyncFtpQueue((int)$responses['folder_id'], $datas['path']);
                                }
                            }
                        }

                        $wpfdQueue->proceedQueueAsync();
                    }
                }
            }
        }

        $meta_query = array(
            array(
                'key' => 'wpfd_category_sync_ftp_path',
                'compare' => 'EXISTS',
            ),
        );

        $term_args = array(
            'taxonomy' => 'wpfd-category',
            'hide_empty' => false,
            'meta_query' => $meta_query
        );

        $categories_with_meta = get_terms($term_args);
        foreach ($categories_with_meta as $category) {
            $category_id = $category->term_id;
            $wpfd_category_sync_ftp_path = get_term_meta($category_id, 'wpfd_category_sync_ftp_path', true);
            if (!empty($wpfd_category_sync_ftp_path)) {
                if (!file_exists($wpfd_category_sync_ftp_path)) {
                    $categoryModel = \Joomunited\WPFramework\v1_0_6\Model::getInstance('category');
                    $children = $categoryModel->getChildren($category_id);
                    if ($categoryModel->delete($category_id)) {
                        $children[] = $category_id;
                        foreach ($children as $child) {
                            if ($child === $category_id) {
                                continue;
                            }
                            $categoryModel->delete($child);
                        }
                    }
                }
            }
        }


        $posts_with_meta = array();
        $args = array(
            'post_type' => 'wpfd_file',
            'meta_key' => 'wpfd_sync_file_hash',
            'meta_compare' => 'EXISTS',
            'posts_per_page' => -1
        );

        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $posts_with_meta[] = get_the_ID();
            }
            wp_reset_postdata();
        }

        $fileModel = \Joomunited\WPFramework\v1_0_6\Model::getInstance('file');
        foreach ($posts_with_meta as $key => $value) {
            $metaData = get_post_meta($value, '_wpfd_file_metadata', true);
            if (isset($metaData['wpfd_sync_ftp_path']) && !file_exists($metaData['wpfd_sync_ftp_path'])) {
                $fileModel->delete($value);
            }
        }

        $global_config = get_option('_wpfd_global_config');
        $global_config['last_run_server_sync_time'] = time();
        update_option('_wpfd_global_config', $global_config);
    }
}

if (!function_exists('wpfd_tags_enqueue_assets')) {
    /**
     * Wpfd_tags_enqueue_assets
     *
     * @return void
     */
    function wpfd_tags_enqueue_assets()
    {
        wp_enqueue_style(
            'wpfd-tags-style',
            WPFD_PLUGIN_URL . 'app/admin/assets/ui/css/tags.css',
            array(),
            WPFD_VERSION,
            'all'
        );
    }
}
add_action('admin_init', 'wpfd_tags_enqueue_assets', 1);