<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

namespace Joomunited\WP_File_Download\Admin\Fields;

use Joomunited\WPFramework\v1_0_6\Field;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class Switcher
 */
class Robotsmeta extends Field
{
    /**
     * Get field
     *
     * @param array $field Field meta
     * @param array $data  Field data
     *
     * @return string
     */
    public function getfield($field, $data)
    {
        $attributes = $field['@attributes'];

        $html       = '<div class="ju-settings-option">';
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
        $tooltip    = isset($attributes['tooltip']) ? __($attributes['tooltip'], 'wpfd') : '';
        if (empty($attributes['hidden']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            if (!empty($attributes['label']) && $attributes['label'] !== '') {
                $html .= '<label title="' . $tooltip . '" class="ju-setting-label">';
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
            }
        }

        $html .= '<div class="robots-meta-checkbox-section">';
        $html .= $this->showItems();
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
    /**
     * Category message contents
     *
     * @return string
     */
    public function showItems()
    {
        $globalConfig = get_option('_wpfd_global_config', array());
        $noindexChecked = (isset($globalConfig['robots_meta_noindex']) && intval($globalConfig['robots_meta_noindex']) === 1) ? ' checked' : '';
        $nofollowChecked = (isset($globalConfig['robots_meta_nofollow']) && intval($globalConfig['robots_meta_nofollow']) === 1) ? ' checked' : '';

        $html = '<ul class="robots-meta-checkbox-list no-select-all robots-meta-list">
            <li>
                <input type="checkbox" class="robots-meta-option" value="1" id="wpfd-robots-meta-noindex"'.$noindexChecked.'>
                <label for="wpfd-robots-meta-noindex">'.esc_html__('No Index', 'wpfd').' <span class="wpfd-tooltip">
                        <em class="dashicons-before dashicons-editor-help"></em>
                        <span>'.esc_html__('Prevents file URLs from being indexed and displayed in search engine result pages', 'wpfd').'</span>
                    </span>
                </label>
            </li>
            <li>
                <input type="checkbox" class="robots-meta-option" value="1" id="wpfd-robots-meta-nofollow"'.$nofollowChecked.'>
                <label for="wpfd-robots-meta-nofollow">'.esc_html__('No Follow', 'wpfd').' <span class="wpfd-tooltip">
                        <em class="dashicons-before dashicons-editor-help"></em>
                        <span>'.esc_html__('Prevents search engines from following links on the pages', 'wpfd').'</span>
                    </span>
                </label>
            </li>
        </ul>';

        return $html;
    }
}
