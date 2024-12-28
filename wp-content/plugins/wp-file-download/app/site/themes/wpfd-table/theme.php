<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0.3
 */

//-- No direct access
defined('ABSPATH') || die();

/**
 * Class WpfdThemeTable
 */
class WpfdThemeTable extends WpfdTheme
{
    /**
     * Theme name
     *
     * @var string
     */
    public $name = 'table';

    /**
     * Theme classes
     *
     * @var string
     */
    public $additionalClass;

    /**
     * Get tpl path for include
     *
     * @return string
     */
    public function getTplPath()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl.php';
    }

    /**
     * Load template hooks
     *
     * @return void
     */
    public function loadHooks()
    {
        $this->hideEmpty(false);
        parent::loadHooks();
        $this->customAssets();
    }

    /**
     * Load custom hooks and filters
     *
     * @return void
     */
    public function loadCustomHooks()
    {
        $name = $this->getThemeName();

        add_filter('wpfd_' . $name . '_content_wrapper', array(__CLASS__, 'contentWrapper'), 10, 2);
        $theme_column = (isset($this->params['theme_column']) && !empty($this->params['theme_column'])) ? $this->params['theme_column'] : array();
        $col_priority = 10;
        if (!empty($theme_column)) {
            foreach ($theme_column as $key => $value) {
                switch ($value) {
                    case 'title':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thTitle'), $col_priority, 2);
                        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showTitleHandlebars'), 5, 2);
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showTitle'), 5, 3);
                        break;
                    case 'description':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thDesc'), $col_priority, 2);
                        add_filter('wpfd_' . $name . '_file_info_description_handlebars_args', array(__CLASS__,'descriptionHandlebars'), 10, 3);
                        add_filter('wpfd_' . $name . '_file_info_description_args', array(__CLASS__, 'description'), 10, 4);
                        break;
                    case 'category':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thCategory'), $col_priority, 2);
                        break;
                    case 'version':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thVersion'), $col_priority, 2);
                        add_filter('wpfd_' . $name . '_file_info_version_handlebars_args', array(__CLASS__, 'versionHandlebars'), 10, 3);
                        add_filter('wpfd_' . $name . '_file_info_version_args', array(__CLASS__, 'version'), 10, 4);
                        break;
                    case 'size':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thSize'), $col_priority, 2);
                        add_filter('wpfd_' . $name . '_file_info_size_handlebars_args', array(__CLASS__, 'sizeHandlebars'), 10, 3);
                        add_filter('wpfd_' . $name . '_file_info_size_args', array(__CLASS__, 'size'), 10, 4);
                        break;
                    case 'hits':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thHits'), $col_priority, 2);
                        add_filter('wpfd_' . $name . '_file_info_hits_handlebars_args', array(__CLASS__, 'hitsHandlebars'), 10, 3);
                        add_filter('wpfd_' . $name . '_file_info_hits_args', array(__CLASS__, 'hits'), 10, 4);
                        break;
                    case 'date added':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thCreated'), $col_priority, 2);
                        add_filter('wpfd_' . $name . '_file_info_created_handlebars_args', array(__CLASS__, 'createdHandlebars'), 10, 3);
                        add_filter('wpfd_' . $name . '_file_info_created_args', array(__CLASS__, 'created'), 10, 4);
                        break;
                    case 'download':
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thDownload'), $col_priority, 2);
                        break;
                    default:
                        add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thTitle'), $col_priority, 2);
                        add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showTitleHandlebars'), 5, 2);
                        add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showTitle'), 5, 3);
                        break;
                }
                $col_priority = $col_priority + 10;
            }
            add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thMenuOption'), $col_priority, 2);
        } else {
            // Using local title
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showtitle', 1) === 1) {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thTitle'), 10, 2);
                add_action('wpfd_' . $name . '_file_info_handlebars', array(__CLASS__, 'showTitleHandlebars'), 5, 2);
                add_action('wpfd_' . $name . '_file_info', array(__CLASS__, 'showTitle'), 5, 3);
            }
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdescription', 1) === 1) {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thDesc'), 20, 2);
                add_filter('wpfd_' . $name . '_file_info_description_handlebars_args', array(
                    __CLASS__,
                    'descriptionHandlebars'
                ), 10, 3);
                add_filter('wpfd_' . $name . '_file_info_description_args', array(__CLASS__, 'description'), 10, 4);
            }
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showversion', 1) === 1) {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thVersion'), 30, 2);
                add_filter('wpfd_' . $name . '_file_info_version_handlebars_args', array(__CLASS__, 'versionHandlebars'), 10, 3);
                add_filter('wpfd_' . $name . '_file_info_version_args', array(__CLASS__, 'version'), 10, 4);
            }
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showsize', 1) === 1) {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thSize'), 40, 2);
                add_filter('wpfd_' . $name . '_file_info_size_handlebars_args', array(__CLASS__, 'sizeHandlebars'), 10, 3);
                add_filter('wpfd_' . $name . '_file_info_size_args', array(__CLASS__, 'size'), 10, 4);
            }
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showhits', 1) === 1) {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thHits'), 50, 2);
                add_filter('wpfd_' . $name . '_file_info_hits_handlebars_args', array(__CLASS__, 'hitsHandlebars'), 10, 3);
                add_filter('wpfd_' . $name . '_file_info_hits_args', array(__CLASS__, 'hits'), 10, 4);
            }
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdateadd', 1) === 1) {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thCreated'), 60, 2);
                add_filter('wpfd_' . $name . '_file_info_created_handlebars_args', array(__CLASS__, 'createdHandlebars'), 10, 3);
                add_filter('wpfd_' . $name . '_file_info_created_args', array(__CLASS__, 'created'), 10, 4);
            }
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdatemodified', 0) === 1) {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thModified'), 70, 2);
                add_filter('wpfd_' . $name . '_file_info_modified_handlebars_args', array(__CLASS__, 'modifiedHandlebars'), 10, 3);
                add_filter('wpfd_' . $name . '_file_info_modified_args', array(__CLASS__, 'modified'), 10, 4);
            }

            // Show download heading when download or preview enabled
            if ((int) WpfdBase::loadValue($this->params, self::$prefix . 'showdownload', 1) === 1 ||
                $this->config['use_google_viewer'] !== 'no') {
                add_action('wpfd_' . $name . '_columns', array(__CLASS__, 'thDownload'), 80, 2);
            }
        }
    }

    /**
     * Load custom assets
     *
     * @return void
     */
    public function customAssets()
    {
        $themeName = $this->name;
        $classes = array(
            'wpfd-table',
            'wpfd-table-bordered',
            'wpfd-table-striped'
        );
        $classes = array_map(function ($class) use ($themeName) {
            return str_replace('table', $themeName, $class);
        }, $classes);
        /**
         * Additional classes for table
         *
         * @param array
         */
        $this->additionalClass = join(' ', apply_filters('wpfd_' . $this->name . 'additional_classes', $classes));

        // Load additional scripts
        wp_localize_script(
            'wpfd-theme-table',
            'wpfdTableTheme',
            array('wpfdajaxurl' => $this->ajaxUrl, 'columns' => esc_html__('Columns', 'wpfd'))
        );

        if (WpfdBase::checkExistTheme($this->name)) {
            $url = plugin_dir_url($this->path . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'wpfd-' . $this->name . DIRECTORY_SEPARATOR . 'foobar');
        } else {
            $url  = wpfd_abs_path_to_url(realpath(dirname(wpfd_locate_theme($this->name, 'theme.php'))) . DIRECTORY_SEPARATOR);
        }
        wp_enqueue_script('wpfd-theme-table-mediatable', $url . 'js/jquery.mediaTable.js');
        wp_enqueue_style('wpfd-theme-table-mediatable', $url . 'css/jquery.mediaTable.css');
    }

    /**
     * Print content wrapper
     *
     * @param string $wrapper Content wrapper html
     * @param object $theme   Current theme object
     *
     * @return string
     */
    public static function contentWrapper($wrapper, $theme)
    {
        $installedVersion = get_option('wpfd_version');
        $params = isset($theme->params) ? $theme->params : array();
        $bgColor = isset($params[self::$prefix . 'bgcolor']) ? $params[self::$prefix . 'bgcolor'] : 'transparent';
        $wpfdcontentclass = '';
        if (WpfdBase::loadValue($theme->params, self::$prefix . 'stylingmenu', true)) {
            $wpfdcontentclass .= 'colstyle';
        } else {
            $wpfdcontentclass .= 'colstyle-hide';
        }

        return sprintf(
            '<div class="wpfd-content wpfd-content-' . $theme->name . ' wpfd-content-multi %s" data-category="%s" 
             style="background-color: ' . $bgColor . ';">',
            (string) esc_attr($wpfdcontentclass),
            (string) esc_attr($theme->category->term_id)
        );
    }

    /**
     * Print title handlebars
     *
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return void
     */
    public static function showTitleHandlebars($config, $params)
    {
        $name = self::$themeName;
        $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
        if ($config['custom_icon']) {
            $html = '{{#if file_custom_icon}}<span class="icon-custom"><img src="{{file_custom_icon}}"></span>{{else}}<span class="ext ext-{{ext}}' . $iconSet . '"></span>{{/if}}';
        } else {
            $html = '<span class="ext ext-{{ext}}' . $iconSet . '"></span>';
        }
        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }
        /**
         * Filter to change icon html for handlebars template
         *
         * @param string Output html for handlebars template
         * @param array  Main config
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_file_info_icon_hanlebars
         *
         * @return string
         *
         * @ignore
         */
        $html = apply_filters('wpfd_' . $name . '_file_info_icon_hanlebars', $html, $config, $params);
        $fileTitleOpening = apply_filters('wpfd_file_title_open_file_in_new_tab', false);
        $replace = apply_filters('wpfd_file_replace_download_with_preview', false);
        $selectFileInput = '';
        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files()) {
            $selectFileInput = '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="{{ID}}" /><span></span></label>';
        }
        $template = array(
            'html' => $selectFileInput . '<a class="wpfd_downloadlink" href="%link$s" '.$rel.' title="%title$s" target="%target$s"><span class="extcol">%icon$s</span><span class="wpfd-file-crop-title">%croptitle$s</span></a>',
            'args' => array(
                'link'      => $replace ? '{{viewerlink}}' : '{{linkdownload}}',
                'title'     => '{{post_title}}',
                'icon'      => $html,
                'croptitle' => '{{{crop_title}}}',
                'target'    => '{{#if remote_file}}_blank{{/if}}'
            )
        );

        if ($fileTitleOpening && $template['args']['target'] !== '_blank') {
            $template['args']['target'] = '_blank';
        }

        /**
         * Filter to change html and arguments of title handlebars
         *
         * @param array Template array
         * @param array Main config
         * @param array Current category config
         *
         * @hookname wpfd_{$themeName}_file_info_title_handlebars_args
         *
         * @return array
         *
         * @ignore
         */
        $args = apply_filters('wpfd_' . $name . '_file_info_title_handlebars_args', $template, $config, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render('<td class="file_title">' . $args['html'] . '</td>', $args['args']);
    }

    /**
     * Print title
     *
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return void
     */
    public static function showTitle($file, $config, $params)
    {
        $name = self::$themeName;
        if ($config['custom_icon'] && isset($file->file_custom_icon) && $file->file_custom_icon !== '') {
            $args = array(
                'html' => '<span class="icon-custom">
                                <img src="%iconurl$s">
                                <span class="icon-custom-title">%croptitle$s</span>
                            </span>',
                'args' => array(
                    'iconurl'   => esc_url($file->file_custom_icon),
                    'croptitle' => esc_html($file->crop_title)
                )
            );
        } else {
            $args = array(
                'html' => '<span class="extcol"><span class="ext ext-%class$s%iconset$s"></span></span><span class="wpfd-file-crop-title">%croptitle$s</span>',
                'args' => array(
                    'class'     => esc_attr(strtolower($file->ext)),
                    'iconset'   => (isset($config['icon_set']) && $config['icon_set'] !== 'default') ? ' wpfd-icon-set-' . esc_attr($config['icon_set']) : '',
                    'croptitle' => esc_html($file->crop_title)
                )
            );
        }
        $robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
        $rel = '';
        if (intval($robots_meta_nofollow) === 1) {
            $rel = ' rel="nofollow" ';
        }
        /**
         * Filter to change icon html
         *
         * @param array  Template array
         * @param object Current file object
         * @param array  Main config
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_file_info_icon_html
         *
         * @return string
         *
         * @ignore
         */
        $args = apply_filters('wpfd_' . $name . '_file_info_icon_html', $args, $file, $config, $params);
        $fileTitleOpening = apply_filters('wpfd_file_title_open_file_in_new_tab', false);
        $replace = apply_filters('wpfd_file_replace_download_with_preview', false);
        $icon = self::render($args['html'], $args['args']);
        $selectFileInput = '';
        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files() && is_numeric($file->ID)) {
            $selectFileInput = '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="' . $file->ID . '" data-catid="' . $file->catid . '" /><span></span></label>';
        }

        $replaceDownloadLink = isset($file->viewerlink) ? $file->viewerlink : '#';
        $className = '';
        if ($fileTitleOpening) {
            $className = ' wpfd_new_tab';
        }
        if (esc_attr(strtolower($file->ext)) === 'pdf' && isset($file->openpdflink)) {
            $replaceDownloadLink = $file->openpdflink;
        }
        $template = array(
            'html' => $selectFileInput . '<a class="wpfd_downloadlink'.$className.'" href="%link$s" '.$rel.' title="%title$s" target="%target$s">%icon$s</a>',
            'args' => array(
                'link'  => $replace ? esc_url($replaceDownloadLink) : esc_url($file->linkdownload),
                'title' => esc_attr($file->post_title),
                'icon'  => $icon,
                'target' => ((isset($file->remote_file) && $file->remote_file === true) || $fileTitleOpening) ? '_blank' : ''
            )
        );
        /**
         * Filter to change html and arguments of title
         *
         * @param array  Template array
         * @param object Current file object
         * @param array  Main config
         * @param array  Current category config
         *
         * @hookname wpfd_{$themeName}_file_info_title_args
         *
         * @return array
         *
         * @ignore
         */
        $args = apply_filters('wpfd_' . $name . '_file_info_title_args', $template, $file, $config, $params);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo self::render('<td class="file_title">' . $args['html'] . '</td>', $args['args']);
    }

    /**
     * Callback for file description handlebars
     *
     * @param array $args   Arguments
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return array
     */
    public static function descriptionHandlebars($args, $config, $params)
    {
        $args = array(
            'html' => '<td class="file_desc">%value$s</td>',
            'args' => array(
                'value' => '{{{description}}}'
            )
        );

        return $args;
    }

    /**
     * Callback for file description
     *
     * @param array  $args   Arguments
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return array
     */
    public static function description($args, $file, $config, $params)
    {
        $description = '';
        if (!empty($file->description)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Used wpfd_esc_desc to remove <script>
            $description = wpfd_esc_desc($file->description);
        }
        $args = array(
            'html' => '<td class="file_desc">%value$s</td>',
            'args' => array(
                'value' => $description
            )
        );

        return $args;
    }

    /**
     * Callback for file version handlebars
     *
     * @param array $args   Arguments
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return array
     */
    public static function versionHandlebars($args, $config, $params)
    {
        $args = array(
            'html' => '<td class="file_version">%value$s</td>',
            'args' => array(
                'value' => '{{versionNumber}}'
            )
        );

        return $args;
    }

    /**
     * Callback for file version
     *
     * @param array  $args   Arguments
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return array
     */
    public static function version($args, $file, $config, $params)
    {
        $args = array(
            'html' => '<td class="file_version">%value$s</td>',
            'args' => array(
                'value' => esc_html(!empty($file->versionNumber) ? $file->versionNumber : '')
            )
        );

        return $args;
    }

    /**
     * Callback for file size handlebars
     *
     * @param array $args   Arguments
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return array
     */
    public static function sizeHandlebars($args, $config, $params)
    {
        $args = array(
            'html' => '<td class="file_size">%value$s</td>',
            'args' => array(
                'value' => '{{bytesToSize size}}'
            )
        );

        return $args;
    }

    /**
     * Callback for file size
     *
     * @param array  $args   Arguments
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return array
     */
    public static function size($args, $file, $config, $params)
    {
        $fileSize = (strtolower($file->size) === 'n/a' || $file->size <= 0) ? 'N/A' : WpfdHelperFile::bytesToSize($file->size);
        $args     = array(
            'html' => '<td class="file_size">%value$s</td>',
            'args' => array(
                'value' => esc_html($fileSize)
            )
        );

        return $args;
    }

    /**
     * Callback for file hits handlebars
     *
     * @param array $args   Arguments
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return array
     */
    public static function hitsHandlebars($args, $config, $params)
    {
        $args = array(
            'html' => '<td class="file_hits">%value$s</td>',
            'args' => array(
                'value' => '{{hits}}'
            )
        );

        return $args;
    }

    /**
     * Callback for file hits
     *
     * @param array  $args   Arguments
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return array
     */
    public static function hits($args, $file, $config, $params)
    {
        $args = array(
            'html' => '<td class="file_hits">%value$s</td>',
            'args' => array(
                'value' => esc_html($file->hits)
            )
        );

        return $args;
    }

    /**
     * Callback for file created handlebars
     *
     * @param array $args   Arguments
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return array
     */
    public static function createdHandlebars($args, $config, $params)
    {
        $detailDatetime = apply_filters('wpfd_detail_date_time', false);
        $args = array(
            'html' => '<td class="file_created">%value$s</td>',
            'args' => array(
                'value' => $detailDatetime ? '{{created_time}}' : '{{created}}'
            )
        );

        return $args;
    }

    /**
     * Callback for file created
     *
     * @param array  $args   Arguments
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return array
     */
    public static function created($args, $file, $config, $params)
    {
        $detailDatetime = apply_filters('wpfd_detail_date_time', false);
        $args = array(
            'html' => '<td class="file_created">%value$s</td>',
            'args' => array(
                'value' => esc_html(($detailDatetime && $file->created_time) ? $file->created_time : $file->created)
            )
        );

        return $args;
    }

    /**
     * Callback for file modified handlebars
     *
     * @param array $args   Arguments
     * @param array $config Main config
     * @param array $params Current category config
     *
     * @return array
     */
    public static function modifiedHandlebars($args, $config, $params)
    {
        $detailDatetime = apply_filters('wpfd_detail_date_time', false);
        $args = array(
            'html' => '<td class="file_modified">%value$s</td>',
            'args' => array(
                'value' => $detailDatetime ? '{{modified_time}}' : '{{modified}}'
            )
        );

        return $args;
    }

    /**
     * Callback for file modified
     *
     * @param array  $args   Arguments
     * @param object $file   Current file object
     * @param array  $config Main config
     * @param array  $params Current category config
     *
     * @return array
     */
    public static function modified($args, $file, $config, $params)
    {
        $detailDatetime = apply_filters('wpfd_detail_date_time', false);
        $args = array(
            'html' => '<td class="file_modified">%value$s</td>',
            'args' => array(
                'value' => esc_html(($detailDatetime && isset($file->modified_time)) ? $file->modified_time : $file->modified)
            )
        );

        return $args;
    }

    /**
     * Callback for print title column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thTitle($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showTitle = (isset($params[$prefix . 'showtitle']) && intval($params[$prefix . 'showtitle']) === 1 ) ? true : false;

        if ($showTitle) {
            $html = '<th class="essential persist file_title">' . esc_html__('Title', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of title column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_title_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_title_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print description column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thDesc($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showDesc = (isset($params[$prefix . 'showdescription']) && intval($params[$prefix . 'showdescription']) === 1 ) ? true : false;

        if ($showDesc) {
            $html = '<th class="optional file_desc">' . esc_html__('Description', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of description column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_description_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_description_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print description column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thCategory($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showCategory = (isset($params[$prefix . 'showcategorytable']) && intval($params[$prefix . 'showcategorytable']) === 1 ) ? true : false;

        if ($showCategory) {
            $html = '<th class="optional file_category">' . esc_html__('Category', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of description column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_description_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_category_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print version column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thVersion($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showVersion = (isset($params[$prefix . 'showversion']) && intval($params[$prefix . 'showversion']) === 1 ) ? true : false;

        if ($showVersion) {
            $html = '<th class="optional file_version">' . esc_html__('Version', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of version column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_version_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_version_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print size column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thSize($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showSize = (isset($params[$prefix . 'showsize']) && intval($params[$prefix . 'showsize']) === 1 ) ? true : false;

        if ($showSize) {
            $html = '<th class="optional file_size">' . esc_html__('Size', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of size column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_size_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_size_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print hits column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thHits($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showHits = (isset($params[$prefix . 'showhits']) && intval($params[$prefix . 'showhits']) === 1) ? true : false;

        if ($showHits) {
            $html = '<th class="optional file_hits">' . esc_html__('Hits', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of hits column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_hits_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_hits_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print created date column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thCreated($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showCreatedDate = (isset($params[$prefix . 'showdateadd']) && intval($params[$prefix . 'showdateadd']) === 1 ) ? true : false;
        if ($showCreatedDate) {
            $html = '<th class="optional file_created">' . esc_html__('Date added', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of created date column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_created_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_created_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print modified date column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thModified($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showDateModified = (isset($params[$prefix . 'showdatemodified']) && intval($params[$prefix . 'showdatemodified']) === 1) ? true : false;

        if ($showDateModified) {
            $html = '<th class="optional file_modified">' . esc_html__('Date modified', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of modified date column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_modified_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_modified_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print download column header
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thDownload($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $showDownload = (isset($params[$prefix . 'showdownload']) && intval($params[$prefix . 'showdownload']) === 1) ? true : false;
        $preview = $config['use_google_viewer'] !== 'no' ? true : false;

        if ($showDownload || $preview) {
            $html = '<th class="essential file_download file_download_tbl">' . esc_html__('Download', 'wpfd') . '</th>';
        } else {
            $html = '';
        }

        /**
         * Filter to change html header of download column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_download_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_download_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Callback for print menu option on table
     *
     * @param array $config Global settings
     * @param array $params Category settings
     *
     * @return void
     */
    public static function thMenuOption($config = array(), $params = array())
    {
        $name = self::$themeName;
        $prefix = self::$prefix;
        $mediaMenuOptionCount= 0;
        $mediaMenuOptionHtml = '';
        foreach ($params['theme_column'] as $key => $value) {
            switch ($value) {
                case 'date added':
                    $mediaMenuOptionClass = 'media-item-created';
                    break;
                case 'description':
                    $mediaMenuOptionClass = 'media-item-desc';
                    break;
                default:
                    $mediaMenuOptionClass = 'media-item-'.$value;
                    break;
            }
            $value = ucfirst($value);
            if ($value !== 'Title') {
                $mediaMenuOptionHtml .= '<li>
                    <input type="checkbox" class="media-item '.$mediaMenuOptionClass.'" name="toggle-cols" id="toggle-col-MediaTable-0-'.$mediaMenuOptionCount.'" value="'.$value.'" checked="checked"> <label for="toggle-col-MediaTable-0-'.$mediaMenuOptionCount.'">'.esc_html__($value, 'wpfd').'</label></li>';// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string translate only
                $mediaMenuOptionCount++;
            }
        }
        $html = '<th class="mediaMenuOption wpfdMenuOption">';
        $html .=    '<div class="mediaTableMenu wpfdTableMenu mediaTableMenuClosed">';
        $html .=        '<a title="Columns"><i class="zmdi zmdi-settings"></i></a>';
        $html .=        '<ul>';
        $html .=            $mediaMenuOptionHtml;
        $html .=        '</ul>';
        $html .=        '<input type="hidden" class="media-list" name="media-list" id="total-media-list" value="" style="visibility: hidden">';
        $html .=    '</div>';
        $html .='</th>';

        /**
         * Filter to change html header of description column
         *
         * @param string Header html
         *
         * @hookname wpfd_{$themeName}_column_description_header_html
         *
         * @return string
         */
        $output = apply_filters('wpfd_' . $name . '_column_category_header_html', $html);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this escaped
        echo $output;
    }

    /**
     * Show password protection file block
     *
     * @param object  $file  File object
     * @param mixed   $style Style of file
     * @param boolean $echo  Echo or not
     *
     * @return void|mixed
     */
    public static function wpfdTableDisplayFilePasswordProtectionForm($file, $style, $echo = true)
    {
        if (!$file) {
            return;
        }

        $fileTitle = isset($file->post_title) ? $file->post_title : '';
        $contents  = '<tr class="file wpfd-password-protection-form"';
        $contents .= ' style="' . esc_html($style) . '" data-id="' . esc_attr($file->ID) . '" data-catid="' . esc_attr($file->catid) . '">';
        $contents .= '<td class="full-width" style="width: 100%">';
        $contents .= '<h3 class="protected-title" title="' . $fileTitle . '">';
        $contents .= esc_html__('Protected: ', 'wpfd') . $fileTitle . '</h3>';
        $contents .= wpfdGetPasswordForm($file, 'file', $file->catid) . '</td></tr>';

        if ($echo) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text form only
            echo $contents;
        } else {
            return $contents;
        }
    }
}
