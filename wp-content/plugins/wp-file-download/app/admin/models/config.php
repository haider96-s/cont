<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WpfdModelConfig
 */
class WpfdModelConfig extends Model
{
    /**
     * Get theme config
     *
     * @return string
     */
    public function getThemeConfig()
    {
        $theme = get_option('_wpfd_theme', 'default');

        return $theme;
    }

    /**
     * Get theme param
     *
     * @param string $theme Theme name
     *
     * @return mixed
     */
    public function getThemeParams($theme)
    {
        $default_config = '{"marginleft":"10","marginright":"10", "margintop":"10", "marginbottom":"10",';
        $default_config .= '"showsize":"1","showtitle":"1","croptitle":"0","showdescription":"1","showversion":"1",';
        $default_config .= '"showhits":"1","showdownload":"1","bgcolor":"rgba(255, 255, 255, 0)","bgdownloadlink":"#76bc58",';
        $default_config .= '"showuploadform":"1","displayfilesearch":"0","colordownloadlink":"#ffffff","showdateadd":"1","showdatemodified":"0",';
        $default_config .= '"showsubcategories":"1","showcategorytitle":"1","showbreadcrumb":"1","showfoldertree":"0", "expanded_folder_tree":"0"}';

        $ggd_config = '{"ggd_marginleft":"10","ggd_marginright":"10", "ggd_margintop":"10", "ggd_marginbottom":"10",';
        $ggd_config .= '"ggd_croptitle":"0", "ggd_showsize":"1","ggd_showtitle":"1","ggd_showdescription":"1",';
        $ggd_config .= '"ggd_showversion":"1","ggd_showhits":"1","ggd_showdownload":"1",';
        $ggd_config .= '"ggd_bgcolor":"rgba(255, 255, 255, 0)","ggd_bgdownloadlink":"#76bc58","ggd_showuploadform":"1","ggd_displayfilesearch":"0","ggd_colordownloadlink":"#ffffff","ggd_showdateadd":"1",';
        $ggd_config .= '"ggd_showdatemodified":"0","ggd_showsubcategories":"1","ggd_showcategorytitle":"1",';
        $ggd_config .= '"ggd_showbreadcrumb":"1","ggd_showfoldertree":"0", "ggd_expanded_folder_tree":"0","ggd_download_popup":"1"}';

        $preview_config = '{"preview_marginleft":"10","preview_marginright":"10", "preview_margintop":"10", "preview_marginbottom":"10",';
        $preview_config .= '"preview_croptitle":"0", "preview_showsize":"1","preview_showtitle":"1","preview_showdescription":"1",';
        $preview_config .= '"preview_showversion":"1","preview_showhits":"1","preview_showdownload":"1",';
        $preview_config .= '"preview_bgcolor":"rgba(255, 255, 255, 0)","preview_bgdownloadlink":"#76bc58","preview_showuploadform":"1","preview_displayfilesearch":"0","preview_colordownloadlink":"#ffffff","preview_showdateadd":"1",';
        $preview_config .= '"preview_showdatemodified":"0","preview_showsubcategories":"1","preview_showcategorytitle":"1",';
        $preview_config .= '"preview_showbreadcrumb":"1","preview_showfoldertree":"0", "preview_expanded_folder_tree":"0","preview_download_popup":"1","preview_subcategoriescolor":"#3e3294"}';

        $table_config = '{"table_stylingmenu":"1", "table_showsize":"1", "table_showtitle":"1",';
        $table_config .= '"table_showdescription":"1", "table_showversion":"1", "table_showhits":"1",';
        $table_config .= '"table_croptitle":"0", "table_showdownload":"1", "table_bgcolor":"rgba(255, 255, 255, 0)", "table_bgdownloadlink":"#76bc58",';
        $table_config .= '"table_showuploadform":"1","table_displayfilesearch":"0", "table_colordownloadlink":"#ffffff", "table_showdateadd":"1", "table_showdatemodified":"0",';
        $table_config .= '"table_showsubcategories":"1", "table_showcategorytitle":"1",';
        $table_config .= '"table_showbreadcrumb":"1", "table_showfoldertree":"0", "table_expanded_folder_tree":"0"}';

        $tree_config    = '{"tree_showsize":"1","tree_croptitle":"0",';
        $tree_config    .= '"tree_showtitle":"1","tree_showdescription":"1","tree_showversion":"1","tree_showhits":"1",';
        $tree_config    .= '"tree_showdownload":"1","tree_bgcolor":"rgba(255, 255, 255, 0)","tree_bgdownloadlink":"#76bc58","tree_showuploadform":"1","tree_showbreadcrumb":"1","tree_displayfilesearch":"0", "tree_colordownloadlink":"#ffffff",';
        $tree_config    .= '"tree_showdateadd":"1","tree_showdatemodified":"0","tree_showsubcategories":"1","tree_expanded_subcategories":"0",';
        $tree_config    .= '"tree_showcategorytitle":"1","tree_download_popup":"1"}';

        $custom_config = '{"marginleft":"10","marginright":"10", "margintop":"10", "marginbottom":"10",';
        $custom_config .= '"showsize":"1","showtitle":"1","croptitle":"0","showdescription":"1","showversion":"1",';
        $custom_config .= '"showhits":"1","showdownload":"1","bgdownloadlink":"#76bc58",';
        $custom_config .= '"showuploadform":"1", "displayfilesearch":"0", "colordownloadlink":"#ffffff","showdateadd":"1","showdatemodified":"0",';
        $custom_config .= '"showsubcategories":"1","showcategorytitle":"1","showbreadcrumb":"1","showfoldertree":"0", "expanded_folder_tree":"0",';
        $custom_config .= '"' . $theme . '_showbreadcrumb":"1","' . $theme . '_showfoldertree":"0", "' . $theme . '_expanded_folder_tree":"0",';
        $custom_config .= '"' . $theme . '_show' . $theme . 'border":"1","' . $theme . '_showsize":"1","' . $theme . '_croptitle":"0",';
        $custom_config .= '"' . $theme . '_showtitle":"1","' . $theme . '_showdescription":"1","' . $theme . '_showversion":"1","' . $theme . '_showhits":"1",';
        $custom_config .= '"' . $theme . '_showdownload":"1","' . $theme . '_bgdownloadlink":"#76bc58", "' . $theme . '_showuploadform":"1", "' . $theme . 'displayfilesearch":"0", "' . $theme . '_colordownloadlink":"#ffffff",';
        $custom_config .= '"' . $theme . '_showdateadd":"1","' . $theme . '_showdatemodified":"0","' . $theme . '_showsubcategories":"1",';
        $custom_config .= '"' . $theme . '_showcategorytitle":"1","' . $theme . '_download_popup":"1", "' . $theme . '_styling":"1", "' . $theme . '_stylingmenu":"1",';
        $custom_config .= '"' . $theme . '_marginleft":"10","' . $theme . '_marginright":"10", "' . $theme . '_margintop":"10", "' . $theme . '_marginbottom":"10", "' . $theme . '_subcategoriescolor":"#3e3294"}';

        $default        = array(
            'default' => $default_config,
            'ggd'     => $ggd_config,
            'preview' => $preview_config,
            'table'   => $table_config,
            'tree'    => $tree_config,
        );
        $default_params = isset($default[$theme]) ? $default[$theme] : $custom_config;
        $theme_params   = get_option('_wpfd_' . $theme . '_config', $default_params);
        if (is_string($theme_params)) {
            $theme_params = json_decode($theme_params, true);
        }

        return $theme_params;
    }

    /**
     * List all themes inside themes folder
     *
     * @return array
     */
    public function getThemes()
    {
        $app       = Application::getInstance('Wpfd');
        $results   = array();
        $path_wpfd = $app->getPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'themes';
        $path_wpfd .= DIRECTORY_SEPARATOR . 'wpfd-*';
        foreach (glob($path_wpfd, GLOB_ONLYDIR) as $rep) {
            $dir       = explode(DIRECTORY_SEPARATOR, $rep);
            $results[] = substr($dir[count($dir) - 1], 5);
        }
        $dirs         = wp_upload_dir();
        $clonedThemes = $dirs['basedir'] . '/wpfd-themes/';

        if (file_exists($clonedThemes)) {
            foreach (glob($clonedThemes . 'wpfd-*', GLOB_ONLYDIR) as $rep) {
                $results[] = str_replace('wpfd-', '', basename($rep));
            }
        }
        unset($clonedThemes);
        // Additional themes path on wp-content
        $clonedThemes = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . wpfd_get_content_dir() . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
        if (file_exists($clonedThemes)) {
            foreach (glob($clonedThemes . 'wpfd-*', GLOB_ONLYDIR) as $rep) {
                $results[] = str_replace('wpfd-', '', basename($rep));
            }
        }

        return $results;
    }

    /**
     * Get global config
     *
     * @return array
     */
    public function getConfig()
    {
        $user_token = get_option('ju_user_token', '');
        $defaultPreviewConfig = !empty($user_token) ? 1 : 0;
        $allowedext_str                           = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,'
                                                    . 'ppt,pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,'
                                                    . 'aiff,alac,amr,au,cdda,flac,m3u,m4a,m4p,mid,mp3,mp4,mpa,ogg,pac,ra,wav,wma,3gp,asf,avi,flv,m4v,'
                                                    . 'mkv,mov,mpeg,mpg,rm,swf,vob,wmv,css,img';
        $extension_viewer                         = 'png,jpg,jpeg,pdf,ppt,pptx,doc,docx,xls,xlsx,dxf,ps,eps,xps,psd,tif,tiff,bmp,svg,pages,ai,dxf,ttf,txt,mp3,mp4';
        $defaultConfig                            = array('allowedext' => $allowedext_str);
        $defaultConfig['maxinputfile']            = 32;
        $defaultConfig['deletefiles']             = 0;
        $defaultConfig['catparameters']           = 1;
        $defaultConfig['themesettings']           = 1;
        $defaultConfig['defaultthemepercategory'] = 'default';
        $defaultConfig['date_format']             = 'd-m-Y';
        $defaultConfig['use_google_viewer']       = 'lightbox';
        $defaultConfig['extension_viewer']        = $extension_viewer;
        $defaultConfig['uri']                     = 'download';
        $defaultConfig['rmdownloadext']           = 0;

        if ((function_exists('is_wpe') && is_wpe()) || defined('KINSTAMU_VERSION') || (isset($_SERVER['HTTP_CF_WORKER']) && $_SERVER['HTTP_CF_WORKER'] === 'kinsta.cloud')) {
            $defaultConfig['rmdownloadext'] = 1;
        }

        $defaultConfig['ga_download_tracking']    = 0;
        $defaultConfig['useeditor']               = 0;
        $defaultConfig['restrictfile']            = 0;
        $defaultConfig['categoryown']             = 0;
        $defaultConfig['shortcodecat']            = 1;
        $defaultConfig['paginationnunber']        = 100;
        $defaultConfig['open_pdf_in']             = 0;
        $defaultConfig['custom_icon']             = 1;
        $defaultConfig['file_count']              = 0;
        $defaultConfig['version_pattern_enabled'] = 0;
        $defaultConfig['version_pattern']         = '#';
        $defaultConfig['versionlimit']            = 10;
        $defaultConfig['new_category_position']   = 'end';
        $defaultConfig['track_user_download']     = 0;
        $defaultConfig['show_empty_folder']       = 0;
        $defaultConfig['icon_set']                = 'svg';
        $defaultConfig['auto_generate_preview']   = $defaultPreviewConfig;
        $defaultConfig['secure_preview_file']     = 0;
        $defaultConfig['guest_download_files']    = 1;
        $defaultConfig['guest_preview_files']     = 1;
        $defaultConfig['access_message']          = 0;
        $defaultConfig['empty_message']           = 0;
        $defaultConfig['global_files_ordering']   = 'title';
        $defaultConfig['global_files_ordering_all'] = 0;
        $defaultConfig['global_files_ordering_direction'] = 'desc';
        $defaultConfig['global_files_ordering_direction_all'] = 0;
        $defaultConfig['global_subcategories_ordering'] = 'customorder';
        $defaultConfig['global_subcategories_ordering_all'] = 0;
        $defaultConfig['admin_pagination']        = 0;
        $defaultConfig['admin_pagination_number'] = 10;
        $defaultConfig['admin_load_more']         = 0;
        $defaultConfig['admin_load_more_number']  = 10;
        $defaultConfig['limit_the_download']      = 0;
        $defaultConfig['server_sync']             = 0;
        $defaultConfig['server_sync_ext']         = 0;
        $defaultConfig['server_sync_time']        = 10;

        $config                                   = get_option('_wpfd_global_config', $defaultConfig);
        $config                                   = array_merge($defaultConfig, $config);

        return (array) $config;
    }

    /**
     * Get file config
     *
     * @return array
     */
    public function getFileConfig()
    {
        $defaultConfig = array(
            'singlebg'        => '#444444',
            'singlehover'     => '#888888',
            'singlefontcolor' => '#ffffff',
        );
        $config        = get_option('_wpfd_global_file_config', $defaultConfig);

        return (array) $config;
    }

    /**
     * Get search config
     *
     * @return array
     */
    public function getSearchConfig()
    {
        $defaultConfig = array(
            'plain_text_search' => 0,
            'cat_filter'        => 1,
            'tag_filter'        => 0,
            'display_tag'       => 'searchbox',
            'create_filter'     => 1,
            'update_filter'     => 1,
            'file_per_page'     => 15,
            'search_suggestion' => 1,
            'shortcode'         => '[wpfd_search]'
        );
        $config        = get_option('_wpfd_global_search_config', $defaultConfig);

        return (array) $config;
    }

    /**
     * Get upload config
     *
     * @return array
     */
    public function getUploadConfig()
    {
        $defaultConfig = array(
            'upload_cattegory_id' => 0,
            'upload_shortcode'    => '[wpfd_upload]'
        );
        $config        = get_option('_wpfd_global_upload_config', $defaultConfig);

        return (array) $config;
    }

    /**
     * Get upload config
     *
     * @return array
     */
    public function getFileInCatConfig()
    {
        $defaultConfig = array(
            'file_cat_id'              => 0,
            'file_cat_ordering'        => 'created_time',
            'file_cat_ordering_direct' => 'asc',
            'file_cat_number'          => '10',
            'file_shortcode_generator' => '[wpfd_category  order="created_time" direction="asc" number="10" ]'
        );
        $config        = get_option('_wpfd_global_file_cat_config', $defaultConfig);

        return (array) $config;
    }

    /**
     * Save global config
     *
     * @param array $datas Data
     *
     * @return boolean False if value was not updated and true if value was updated.
     */
    public function save($datas)
    {
        $config = get_option('_wpfd_global_config');

        // Force track user download to use download limit option
        if (isset($datas['track_user_download']) && intval($datas['track_user_download']) === 0
         && isset($config['limit_the_download']) && intval($config['limit_the_download']) === 1) {
            $datas['track_user_download'] = '1';
        }

        foreach ($datas as $key => $value) {
            $config[$key] = $value;
        }
        $result = update_option('_wpfd_global_config', $config);

        return $result;
    }

    /**
     * Save theme params
     *
     * @param string $theme Theme name
     * @param array  $datas Theme config
     *
     * @return boolean False if value was not updated and true if value was updated.
     */
    public function saveThemeParams($theme, $datas)
    {
        $result = update_option('_wpfd_' . $theme . '_config', $datas);

        return $result;
    }

    /**
     * Save file params
     *
     * @param array $datas File params
     *
     * @return boolean False if value was not updated and true if value was updated.
     */
    public function saveFileParams($datas)
    {
        $result = update_option('_wpfd_global_file_config', $datas);

        return $result;
    }

    /**
     * Save search params
     *
     * @param array $datas File params
     *
     * @return boolean
     */
    public function saveSearchParams($datas)
    {
        update_option('_wpfd_global_search_config', $datas);

        return true;
    }

    /**
     * Save theme params
     *
     * @param array $datas Params
     *
     * @return boolean
     */
    public function saveTheme($datas)
    {
        update_option('_wpfd_theme', $datas);

        return true;
    }

    /**
     * Save notifications params
     *
     * @param array $datas Params
     *
     * @return boolean
     */
    public function saveNotifications($datas)
    {
        update_option('_wpfd_notifications', $datas);

        return true;
    }

    /**
     * Save upload params
     *
     * @param array $datas Params
     *
     * @return boolean
     */
    public function saveUploadParams($datas)
    {
        update_option('_wpfd_global_upload_config', $datas);

        return true;
    }

    /**
     * Save file in cate params
     *
     * @param array $datas Params
     *
     * @return boolean
     */
    public function saveFileInCatParams($datas)
    {
        update_option('_wpfd_global_file_cat_config', $datas);

        return true;
    }

    /**
     * Get allowed ext for uploading file
     *
     * @return array
     */
    public function getAllowedExt()
    {
        $params = $this->getConfig();
        $allowedExtensions = explode(',', $params['allowedext']);
        return array_map('trim', $allowedExtensions);
    }

    /**
     * Get allowed ext for server sync
     *
     * @return array
     */
    public function getSyncAllowedExt()
    {
        $params = $this->getConfig();
        $server_sync_allowedext = (isset($params['server_sync_allowedext'])) ? $params['server_sync_allowedext'] : $params['allowedext'];
        $allowedExtensions = explode(',', $server_sync_allowedext);
        return array_map('trim', $allowedExtensions);
    }

    /**
     * Clone theme data
     *
     * @param array $data Params
     *
     * @return boolean
     */
    public function clonetheme($data)
    {
        $app                = Application::getInstance('Wpfd');
        $ds = DIRECTORY_SEPARATOR;
        $data['theme_name'] = str_replace(' ', '_', $data['theme_name']);
        $data['theme_name'] = preg_replace('/[^a-zA-Z0-9_]+/', '', $data['theme_name']);
        $data['theme_name'] = strtolower($data['theme_name']);
        $rootThemeTypes     = get_option('wpfd_root_theme_types', array());
        $themepath          = $app->getPath() . $ds . 'site' . $ds . 'themes';
        $themepath          .= $ds . 'wpfd-' . $data['theme'];

        $wpfdthemes = WP_CONTENT_DIR . $ds . wpfd_get_content_dir() . $ds . 'themes' . $ds;

        if (!file_exists($wpfdthemes)) {
            mkdir($wpfdthemes, 0777, true);
        }

        $themepath_new = $wpfdthemes . 'wpfd-' . strtolower($data['theme_name']);
        if ($data['theme'] !== $data['theme_name']) {
            if (!is_dir($themepath)) {
                $themepath = $wpfdthemes . 'wpfd-' . strtolower($data['theme']);
            }
            $this->copyfolder($themepath, $themepath_new);
        }
        $this->copytheme($themepath_new, $data);

        // Root clone theme type
        if (isset($data['clone_theme_root_theme_type'])) {
            $rootThemeTypes[$data['theme_name']] = $data['clone_theme_root_theme_type'];
            update_option('wpfd_root_theme_types', $rootThemeTypes);
        }

        // add theme in admin
        $themefile = $app->getPath() . $ds . 'admin' . $ds . 'views'. $ds . 'category' . $ds . 'tpl' . $ds;
        $themefile .= 'theme-' . $data['theme'] . '.php';
        $themetpl  = $themepath_new . '/tpl/';
        if (!file_exists($themetpl)) {
            mkdir($themetpl, 0777);
        }
        $themefile_new = $themetpl . 'theme-' . $data['theme_name'] . '.php';
        if (is_file($themefile)) {
            if (copy($themefile, $themefile_new)) {
                $file = $themefile_new;
                if (is_file($file)) {
                    $file_contents = file_get_contents($file);
                    $file_contents = str_replace(ucfirst($data['theme']), ucfirst($data['theme_name']), $file_contents);
                    $file_contents = str_replace($data['theme'], $data['theme_name'], $file_contents);
                    file_put_contents($file, $file_contents);
                }
            } else {
                return false;
            }
        } else {
            $themefile_new2 = $themetpl . 'theme-' . $data['theme'] . '.php';
            if (is_file($themefile_new2)) {
                $file = $themetpl . 'theme-' . $data['theme_name'] . '.php';
                rename($themefile_new2, $file);
                if (is_file($file)) {
                    $file_contents = file_get_contents($file);
                    $file_contents = str_replace(ucfirst($data['theme']), ucfirst($data['theme_name']), $file_contents);
                    $file_contents = str_replace($data['theme'], $data['theme_name'], $file_contents);
                    file_put_contents($file, $file_contents);
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Copy theme
     *
     * @param string $dst  Destination folder path
     * @param array  $data Theme data
     *
     * @return void
     */
    public function copytheme($dst, $data)
    {
        $directory = opendir($dst);
        // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Loop for each $file in directory
        while (($file = readdir($directory)) !== false) {
            if (($file !== '.') && ($file !== '..')) {
                $file_path = $dst . '/' . $file;
                if (is_dir($file_path)) {
                    $this->copytheme($file_path, $data);
                } else {
                    $ext           = pathinfo($file, PATHINFO_EXTENSION);
                    $file_contents = file_get_contents($file_path);
                    if ($data['theme'] === 'table') {
                        if (strtolower($ext) === 'css') {
                            // Add placeholder to theme name
                            $theme_name = str_replace('table', 'themenameplaceholder', $data['theme_name']);
                            $file_contents = str_replace('-table', '-' . $theme_name, $file_contents);
                            $file_contents = str_replace('.table-download-category', '.' . $theme_name . '-download-category', $file_contents);
                        } elseif (strtolower($ext) === 'js') {
                            $file_contents = str_replace('_table', '_xxx', $file_contents);
                            $file_contents = str_replace('table_', 'xxx_', $file_contents);
                            $file_contents = str_replace('table-', 'xxx-', $file_contents);
                            $file_contents = str_replace('-table', '-xxx', $file_contents);
                            $file_contents = str_replace('xxx', $data['theme_name'], $file_contents);
                        } elseif (strtolower($ext) === 'php') {
                            if ($file === 'tpl.php') {
                                $file_contents = str_replace('_table', '_xxx', $file_contents);
                                $file_contents = str_replace('table_', 'xxx_', $file_contents);
                                $file_contents = str_replace('table-', 'xxx-', $file_contents);
                                $file_contents = str_replace('-table', '-xxx', $file_contents);
                                $file_contents = str_replace('xxx', $data['theme_name'], $file_contents);
                            } else {
                                $file_contents = str_replace('table', $data['theme_name'], $file_contents);
                                $file_contents = str_replace(
                                    $data['theme_name'] . 'class',
                                    'tableclass',
                                    $file_contents
                                );
                                $file_contents = str_replace($data['theme_name'] . '-', 'table-', $file_contents);
                                $file_contents = str_replace($data['theme_name'] . ' ', 'table ', $file_contents);
                                $file_contents = str_replace(
                                    'WpfdThemeTable',
                                    'WpfdTheme' . ucfirst(str_replace('_', '', $data['theme_name'])),
                                    $file_contents
                                );
                            }
                        } elseif (strtolower($ext) === 'xml') {
                            $file_contents = str_replace('table', $data['theme_name'], $file_contents);
                        }
                    } else {
                        if ($data['theme'] === 'preview' && strtolower($ext) === 'php' && $file === 'theme.php') {
                            $file_contents = str_replace(
                                'showPreviewHandlebars',
                                'showThemeNameYYYYYHandlebars',
                                $file_contents
                            );
                        }
                        $file_contents = str_replace($data['theme'], $data['theme_name'], $file_contents);
                        $file_contents = str_replace(
                            ucfirst($data['theme']),
                            ucfirst(str_replace('_', '', $data['theme_name'])),
                            $file_contents
                        );
                        if ($data['theme'] === 'default' && strtolower($ext) === 'xml') {
                            $file_contents = str_replace('name="', 'name="' . $data['theme_name'] . '_', $file_contents);
                        }
                        if ($data['theme'] === 'default' && strtolower($ext) === 'js' && $file === 'script.js') {
                            $file_contents = str_replace(
                                'prevent' . ucfirst($data['theme_name']) . '()',
                                'preventDefault()',
                                $file_contents
                            );
                        }
                        if ($data['theme'] === 'preview' && strtolower($ext) === 'js' && $file === 'script.js') {
                            $file_contents = str_replace(
                                '#' . strtolower(ucfirst($data['theme_name'])),
                                '#preview',
                                $file_contents
                            );
                            $file_contents = str_replace(
                                'w' . strtolower(ucfirst($data['theme_name'])),
                                'wpreview',
                                $file_contents
                            );
                        }
                        if ($data['theme'] === 'preview' && strtolower($ext) === 'php' && $file === 'theme.php') {
                            $file_contents = str_replace(
                                'showThemeNameYYYYYHandlebars',
                                'showPreviewHandlebars',
                                $file_contents
                            );
                            $file_contents = str_replace(
                                'wpfd_can_' . $data['theme_name'] . '_files',
                                'wpfd_can_preview_files',
                                $file_contents
                            );
                            $file_contents = str_replace(
                                'auto_generate_' . $data['theme_name'],
                                'auto_generate_preview',
                                $file_contents
                            );
                            $file_contents = str_replace(
                                '_wpfd_' . $data['theme_name'] . '_file_path',
                                '_wpfd_preview_file_path',
                                $file_contents
                            );
                            $file_contents = str_replace(
                                '_wpfdAddon_' . $data['theme_name'] . '_info_',
                                '_wpfdAddon_preview_info_',
                                $file_contents
                            );
                        }
                    }

                    if (in_array(strtolower($ext), array('css', 'scss'))) {
                        // Add placeholder to theme name
                        $theme_name = str_replace('table', 'themenameplaceholder', $data['theme_name']);
                        $file_contents = str_replace(': table', 'wpfdplaceholderdisplay', $file_contents);
                        $file_contents = str_replace('table-layout', 'wpfdplaceholdertablelayout', $file_contents);
                        $file_contents = str_replace('table', $theme_name, $file_contents);

                        if ($data['theme'] === 'table') {
                            $file_contents = str_replace('-' . $theme_name . '-', '-table-', $file_contents);
                        }
                        $file_contents = str_replace(
                            '../../..',
                            '../../../../plugins/wp-file-download/app/site',
                            $file_contents
                        );
                        // Revert placeholder
                        $file_contents = str_replace('wpfdplaceholderdisplay', ': table', $file_contents);
                        $file_contents = str_replace('wpfdplaceholdertablelayout', 'table-layout', $file_contents);
                        $file_contents = str_replace('themenameplaceholder', 'table', $file_contents);
                    }

                    file_put_contents($file_path, $file_contents);
                }
            }
        }
    }

    /**
     * Copy folder
     *
     * @param string $src Path
     * @param string $dst Destination path
     *
     * @return void
     */
    public function copyfolder($src, $dst)
    {
        $dir = opendir($src);
        if (mkdir($dst)) {
            // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Loop for each folder in $dir
            while (false !== ($file = readdir($dir))) {
                if (($file !== '.') && ($file !== '..')) {
                    if (is_dir($src . '/' . $file)) {
                        $this->copyfolder($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }
    }
}
