<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

defined('ABSPATH') || die();

/**
 * Class WpfdHelperFolder
 */
class WpfdHelperFolder
{
    /**
     * WP file system
     *
     * @var WP_Filesystem_Direct
     */
    protected static $fileSystem;

    /**
     * Delete path
     *
     * @param string $path Path to delete
     *
     * @return void
     */
    public static function delete($path)
    {
        $files = glob($path . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_link($file)) {
                unlink($file);
            }
            if (substr($file, -1) === '/') {
                delTree($file);
            } else {
                unlink($file);
            }
        }
        if (isset($filename) && file_exists($filename)) {
            rmdir($path);
        }
    }

    /**
     * Delele file
     *
     * @param string $path File path
     *
     * @return boolean|null
     */
    public static function deleteFile($path)
    {
        if (!file_exists($path)) {
            return null;
        }

        return self::getFileSystem()->delete($path);
    }

    /**
     * Get plugin custom template base path
     *
     * @return string
     */
    public static function getBasePath()
    {
        return WP_CONTENT_DIR . '/' . wpfd_get_content_dir();
    }

    /**
     * Get preview storage path
     *
     * @return mixed|void
     */
    public static function getPreviewsPath()
    {
        $path = self::getBasePath() . '/previews';

        if (!file_exists($path)) {
            wpfdCreateSecureFolder($path);
        }

        /**
         * Filter to change file preview path
         *
         * @param $path
         *
         * @ignore
         */
        return apply_filters('wpfd_previews_path', trailingslashit($path));
    }

    /**
     * Get thumbnail storage path
     *
     * @return mixed|void
     */
    public static function getThumbnailsPath()
    {
        $path = self::getBasePath() . '/thumbnails';

        if (!file_exists($path)) {
            wpfdCreateSecureFolder($path);
        }

        /**
         * Filter to change file thumbnails path
         *
         * @param $path
         *
         * @ignore
         */
        return apply_filters('wpfd_thumbnails_path', trailingslashit($path));
    }

    /**
     * Get original image path
     *
     * @return mixed|void
     */
    public static function getOriginalPath()
    {
        $path = self::getBasePath() . '/original';

        if (!file_exists($path)) {
            wpfdCreateSecureFolder($path);
        }

        /**
         * Filter to change file original path
         *
         * @param $path
         *
         * @ignore
         */
        return apply_filters('wpfd_original_path', trailingslashit($path));
    }

    /**
     * Get watermark path
     *
     * @return mixed|void
     */
    public static function getWatermarkPath()
    {
        $path = self::getBasePath() . '/watermark';

        if (!file_exists($path)) {
            wpfdCreateSecureFolder($path);
        }

        return apply_filters('wpfd_watermark_path', trailingslashit($path));
    }

    /**
     * Get category watermark path
     *
     * @return mixed|void
     */
    public static function getCategoryWatermarkPath()
    {
        $path = self::getBasePath() . '/category-watermark';

        if (!file_exists($path)) {
            wpfdCreateSecureFolder($path);
        }

        return apply_filters('wpfd_watermark_path', trailingslashit($path));
    }

    /**
     * Copy file
     *
     * @param string        $source      Path to the source file.
     * @param string        $destination Path to the destination file.
     * @param boolean       $overwrite   Optional. Whether to overwrite the destination file if it exists.
     *                                   Default false.
     * @param integer|false $mode        Optional. The permissions as octal number, usually 0644 for files,
     *                                   0755 for dirs. Default false.
     *
     * @return boolean
     */
    public static function copy($source, $destination, $overwrite = false, $mode = false)
    {
        return self::getFileSystem()->copy($source, $destination);
    }

    /**
     * Moves a file.
     *
     * @param string  $source      Path to the source file.
     * @param string  $destination Path to the destination file.
     * @param boolean $overwrite   Optional. Whether to overwrite the destination file if it exists.
     *                             Default false.
     *
     * @return boolean True on success, false on failure.
     */
    public static function move($source, $destination, $overwrite = false)
    {
        return self::getFileSystem()->move($source, $destination);
    }

    /**
     * Get base name
     *
     * @param string $path File path
     *
     * @return string
     */
    public static function getBaseName($path)
    {
        return basename($path);
    }

    /**
     * Get file directory
     *
     * @param string $path File path
     *
     * @return string
     */
    public static function getFileDir($path)
    {
        return dirname($path);
    }

    /**
     * Get file system
     *
     * @return WP_Filesystem_Base
     */
    public static function getFileSystem()
    {
        /* @var WP_Filesystem_Base $wp_filesystem */
        global $wp_filesystem;

        if (!is_null($wp_filesystem)) {
            return $wp_filesystem;
        }

        if (!class_exists('WP_Filesystem_Base')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        }

        if (!class_exists('WP_Filesystem_Direct')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        }

        if (is_null(self::$fileSystem)) {
            self::$fileSystem = new WP_Filesystem_Direct(null);
        }


        return self::$fileSystem;
    }

    /**
     * Get abspath
     *
     * @param string $path File path
     *
     * @return string
     */
    public static function getAbsolutePath($path)
    {
        $absolutePath = $path;

        $contentPos = strpos($absolutePath, 'wp-content');

        if ($contentPos === false) {
            return $path; // This path not related to wp-content
        }

        $relativePath = substr($absolutePath, $contentPos, strlen($absolutePath) - 1);
        $relativePath = str_replace('wp-content', '', $relativePath);

        return WP_CONTENT_DIR . $relativePath;
    }

    /**
     * Create new category by path
     *
     * @param string         $path           The path
     * @param string|integer $rootCategoryId The root category id
     * @param string         $type           The parent category type
     *
     * @return array|false Deepest category id
     */
    public static function createCategoryByPath($path, $rootCategoryId = null, $type = '')
    {
        if (empty($path)) {
            return false;
        }
        $terms = [];
        $user_id = get_current_user_id();
        if (!empty($path)) {
            // Remove last slash or filename
            $path = untrailingslashit($path);
            $filePathArr = explode('/', $path);
            if (!empty($filePathArr)) {
                $parentTermId = $rootCategoryId;
                while (!empty($filePathArr)) {
                    $categoryName = array_shift($filePathArr);
                    if (empty($categoryName)) {
                        continue;
                    }

                    $term = term_exists($categoryName, 'wpfd-category', $parentTermId);

                    if (is_array($term) && !empty($term['term_id'])) {
                        $parentTermId = $term['term_id'];
                        $terms[] = (int)$term['term_id'];
                    } elseif (is_null($term)) {
                        if (in_array($type, wpfd_get_support_cloud())) {
                            $duplicate = false;
                            $position = 'end';
                            $termId = apply_filters('wpfd_addon_add_category', null, $type, $categoryName, $parentTermId, $position, $duplicate);
                            if ($termId) {
                                $parentTermId = $termId;
                                $terms[] = $termId;

                                if ($user_id) {
                                    $user_categories = get_user_meta($user_id, 'wpfd_user_categories', true);
                                    if (is_array($user_categories)) {
                                        if (!in_array($termId, $user_categories)) {
                                            $user_categories[] = $termId;
                                        }
                                    } else {
                                        $user_categories = array();
                                        $user_categories[] = $termId;
                                    }
                                    update_user_meta($user_id, 'wpfd_user_categories', $user_categories);
                                }
                            }
                        } else {
                            // Term not exists
                            $slug = wp_unique_term_slug(sanitize_title($categoryName), json_decode(json_encode(['taxonomy' => 'wpfd-category', 'parent' => $parentTermId])));
                            $term = wp_insert_term($categoryName, 'wpfd-category', ['parent' => $parentTermId, 'slug' => $slug]);

                            // Retry to add category on term name exists
                            $retries = 0;
                            $maxRetries = 3;

                            while (is_wp_error($term) && $retries < $maxRetries) {
                                $newCatName = $categoryName . '-' . ($retries + 1);
                                $slug = wp_unique_term_slug(sanitize_title($categoryName), json_decode(json_encode(['taxonomy' => 'wpfd-category', 'parent' => $parentTermId])));
                                $term = wp_insert_term($newCatName, 'wpfd-category', ['parent' => $parentTermId, 'slug' => $slug]);
                                $retries++;
                            }

                            if (!is_wp_error($term)) {
                                $parentTermId = $term['term_id'];
                                $terms[] = (int)$term['term_id'];

                                if ($user_id) {
                                    $user_categories = get_user_meta($user_id, 'wpfd_user_categories', true);
                                    if (is_array($user_categories)) {
                                        if (!in_array($term['term_id'], $user_categories)) {
                                            $user_categories[] = $term['term_id'];
                                        }
                                    } else {
                                        $user_categories = array();
                                        $user_categories[] = $term['term_id'];
                                    }
                                    update_user_meta($user_id, 'wpfd_user_categories', $user_categories);
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_pop($terms);
    }

    /**
     * Get category by path
     *
     * @param string         $path           The path
     * @param string|integer $rootCategoryId The root category id
     *
     * @return array|false Deepest category id
     */
    public static function getCategoryByPath($path, $rootCategoryId = null)
    {
        if (empty($path)) {
            return false;
        }
        $terms = [];
        $termExists = true;
        if (!empty($path)) {
            // Remove last slash or filename
            $path = untrailingslashit($path);
            $filePathArr = explode('/', $path);
            if (!empty($filePathArr)) {
                $parentTermId = $rootCategoryId;
                while ($termExists && !empty($filePathArr)) {
                    $categoryName = array_shift($filePathArr);
                    if (empty($categoryName)) {
                        continue;
                    }
                    $term = term_exists($categoryName, 'wpfd-category', $parentTermId);

                    if (is_array($term) && !empty($term['term_id'])) {
                        $parentTermId = $term['term_id'];
                        $terms[] = (int) $term['term_id'];
                    } elseif ($term) {
                        $parentTermId = (int)$term;
                        $terms[] = (int) $term;
                    } else {
                        $termExists = false;
                    }
                }
            }
        }

        return array_pop($terms);
    }
}
