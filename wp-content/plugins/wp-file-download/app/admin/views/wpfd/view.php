<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WpfdViewWpfd
 */
class WpfdViewWpfd extends View
{
    /**
     * Categories list
     *
     * @var array|mixed
     */
    public $categories;

    /**
     * Global configuration
     *
     * @var array|mixed
     */
    public $globalConfig;

    /**
     * Custom color settings
     *
     * @var array|mixed
     */
    public $custom_colors;

    /**
     * Extensions
     *
     * @var array|mixed
     */
    public $extensions = array();

    /**
     * Available tags
     *
     * @var array|mixed
     */
    public $allTagsFiles = array();

    /**
     * Render view wpfd
     *
     * @param null $tpl Template name
     *
     * @return void
     */
    public function render($tpl = null)
    {
        Application::getInstance('Wpfd');
        $modelCat            = $this->getModel('categories');
        $modelConfig         = $this->getModel('config');
        $iconModel           = $this->getModel('iconsbuilder');
        $svgParams           = $iconModel->getParams('svg');
        $this->categories    = $modelCat->getCategories();
        $this->globalConfig  = $modelConfig->getConfig();
        $this->custom_colors = get_option('_wpfd_custom_folder_colors', array());

        // Set a default if the list is empty
        $defaultExtensions = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,'
            . 'ppt,pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,'
            . 'aiff,alac,amr,au,cdda,flac,m3u,m4a,m4p,mid,mp3,mp4,mpa,ogg,pac,ra,wav,wma,3gp,asf,avi,flv,m4v,'
            . 'mkv,mov,mpeg,mpg,rm,swf,vob,wmv,css,img';
        $defaultExtensionsArr = array_map('trim', explode(',', $defaultExtensions));

        if (!isset($this->globalConfig['allowedext']) || (isset($this->globalConfig['allowedext']) && $this->globalConfig['allowedext'] === '')) {
            $this->globalConfig['allowedext'] = $defaultExtensions;
        }

        $allowedExtensions = array_map('trim', explode(',', $this->globalConfig['allowedext']));

        // Made sure additional extension in last of list
        $additionalExtensionsSet = array_diff($allowedExtensions, $defaultExtensionsArr);
        $defaultExtensionsSet = array_diff($allowedExtensions, $additionalExtensionsSet);
        $allowedExtensions = array_replace($defaultExtensionsSet, $additionalExtensionsSet);

        // Load icons background for png set
        $extensions = array();
        $missingExtension = array();
        foreach ($allowedExtensions as $extension) {
            foreach (array('png', 'svg') as $type) {
                $icon = WpfdHelperFile::getIconUrls($extension, $type);
                if (false !== $icon) {
                    $extensions[$type][$extension] = $icon;
                    if ($type === 'svg' && isset($svgParams['icons']['wpfd-icon-' . $extension])) {
                        $customCss = '';
                        if (intval($svgParams['icons']['wpfd-icon-' . $extension]['wrapper-active']) === 1) {
                            $customCss = ' style="';
                            $customCss .= isset($svgParams['icons']['wpfd-icon-' . $extension]['border-radius']) && intval($svgParams['icons']['wpfd-icon-' . $extension]['border-radius']) > 0 ? 'border-radius: ' . $svgParams['icons']['wpfd-icon-' . $extension]['border-radius'] . '%;' : '';
                            $customCss .= 'box-shadow: ' . $svgParams['icons']['wpfd-icon-' . $extension]['horizontal-position'] . 'px ' . $svgParams['icons']['wpfd-icon-' . $extension]['vertical-position'] . 'px ' . $svgParams['icons']['wpfd-icon-' . $extension]['blur-radius'] . 'px ' . $svgParams['icons']['wpfd-icon-' . $extension]['spread-radius'] . 'px ' . $svgParams['icons']['wpfd-icon-' . $extension]['shadow-color'] . ';';
                            $customCss .= 'background-color: ' . $svgParams['icons']['wpfd-icon-' . $extension]['background-color'] . ';';
                            $customCss .= 'border: ' . $svgParams['icons']['wpfd-icon-' . $extension]['border-size'] . 'px solid ' . $svgParams['icons']['wpfd-icon-' . $extension]['border-color'] . ';';
                            $customCss .= '"';
                        }
                        $extensions[$type][$extension]['css'] = $customCss;
                    }
                } else {
                    if ($type === 'svg') {
                        // Copy a svg icon for missing extension
                        // Select a random ready icon
                        $extensionRand = array_rand($extensions[$type], 1);
                        $option = $iconModel->getIconParams($type, $extensionRand);
                        // Replace icon extension name
                        $sourceIconPath = WpfdHelperFile::getUploadedIconPath($extensionRand, $type, false);
                        $sourceIconContent = file_get_contents($sourceIconPath);
                        $sourceIconContent = str_replace('>' . $extensionRand . '<', '>' . $extension . '<', $sourceIconContent);
                        $option['icon-text'] = $extension;
                        // Save file
                        $savePath = WpfdHelperFile::getCustomIconPath($type);
                        $newIconPath = $savePath . $extension . '.' . preg_replace('/[0-9]+/', '', $type);
                        file_put_contents($newIconPath, $sourceIconContent);
                        // Save the settings
                        $iconModel->saveIconParams($extension, $type, $option);
                        $extensions[$type][$extension]['uploaded'] = wpfd_abs_path_to_url($newIconPath);
                        $extensions[$type][$extension]['default'] = '';
                    } else {
                        $missingExtension[$type][] = $extension;
                    }
                }
            }
        }

        $this->extensions = $extensions;

        if ((int) WpfdBase::loadValue($this->globalConfig, 'file_count', 0) !== 0) {
            if ($this->categories && !empty($this->categories)) {
                $this->categories = $this->countFileRefCat($this->categories);
            }
        }

        $tags = get_terms('wpfd-tag', array(
            'orderby'    => 'count',
            'hide_empty' => 0,
        ));

        $cloudTags = get_option('wpfd_cloud_available_tags', array());

        if (is_array($cloudTags) && !empty($cloudTags)) {
            $cloudTags = array_map(function ($cloudTag) {
                return trim($cloudTag->value);
            }, $cloudTags);
        } else {
            $cloudTags = array();
        }

        if ($tags) {
            $allTagsFiles = array();
            foreach ($tags as $tag) {
                $allTagsFiles[] = '' . isset($tag->name) ? esc_html($tag->name) : esc_html($tag->slug);
            }

            $allTagsFiles = array_merge($cloudTags, $allTagsFiles);
            $allTagsFiles = array_map('trim', array_unique($allTagsFiles));
            $this->allTagsFiles = '["' . implode('","', $allTagsFiles) . '"]';
        } else {
            if (is_array($cloudTags) && !empty($cloudTags)) {
                $this->allTagsFiles = '["' . implode('","', $cloudTags) . '"]';
            } else {
                $this->allTagsFiles = '[]';
            }
        }

        if (defined('WPFD_ADMIN_UI') && WPFD_ADMIN_UI === true) {
            $tpl = 'ui-default';
        }

        parent::render($tpl);
    }

    /**
     * Count file referent to category
     *
     * @param array $categories Categories
     *
     * @return array
     */
    public function countFileRefCat($categories)
    {
        $modelCategory = $this->getModel('category');
        foreach ($categories as $keycat => $category) {
            $description        = ( isset($category->description) ) ? json_decode($category->description, true) : array();
            $fileCount   = 0;
            if (isset($description['refToFile']) && !empty($description['refToFile'])) {
                $listCatRef = $description['refToFile'];
                foreach ($listCatRef as $key => $lst) {
                    $cat = $modelCategory->getCategory($key);
                    if ($cat && !empty($cat)) {
                        $lstFile = $modelCategory->checkListFiles($key, $lst, $category->term_id);
                        if (!empty($lstFile)) {
                            $fileCount = $fileCount + count($lstFile);
                        }
                    }
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
            $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $category->term_id);
            if (in_array($categoryFrom, wpfd_get_support_cloud())) {
                $categories[$keycat]->count = null;
            } else {
                $categories[$keycat]->count = $category->count + (int) $fileCount;
            }
        }

        return $categories;
    }
}
