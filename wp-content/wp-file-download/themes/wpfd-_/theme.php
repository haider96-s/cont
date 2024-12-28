<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

//-- No direct access
defined('ABSPATH') || die();

/**
 * Class WpfdTheme
 */
class WpfdTheme extends WpfdTheme
{
    /**
     * Theme name
     *
     * @var string
     */
    public $name = '_';
    /**
     * Get tpl.php path for include
     *
     * @return string
     */
    public function getTplPath()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl.php';
    }
}
