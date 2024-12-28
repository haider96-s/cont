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
class Searchcache extends Field
{
    /**
     * Display switch
     *
     * @param array $field Fields
     * @param array $data  Data
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
            if (!empty($attributes['label']) && $attributes['label'] !== '' &&
                !empty($attributes['name']) && $attributes['name'] !== '') {
                $html .= '<label title="' . $tooltip . '" class="ju-setting-label" for="' . $attributes['name'] . '">';
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
            }
        }
        // Switch
        $inputValue = 0;
        $html .= '<div class="ju-switch-button"><label class="switch">';
        $html .= '<input';
        $html .= ' type="checkbox"';

        if (!empty($attributes)) {
            $attribute_array = array('class', 'name', 'value');
            foreach ($attributes as $attribute => $value) {
                if (in_array($attribute, $attribute_array) && isset($value)) {
                    if ($attribute === 'value') {
                        $inputValue = $value;
                        $html .= ' ' . $attribute . '="' . $value . '"';
                        if ((string) $value === '1') {
                            $html .= ' checked';
                        }
                    } elseif ($attribute === 'name') {
                        $html .= ' ' . $attribute . '="ref_' . $value . '"';
                    } else {
                        $html .= ' ' . $attribute . '="' . $value . '"';
                    }
                }
            }
        }
        $html .= ' />';

        $html .= '<span class="slider"></span>';
        $html .= '</label>';
        $val = ($inputValue === '' || (string) $inputValue === '0') ? '0' : '1';
        $html .= '<input type="hidden" id="' . $attributes['name'] . '" name="' . $attributes['name'] . '" value="' . $val . '" />';
        $html .= '</div>';
        $html .= $this->showCacheLifetime($val, $attributes);
        $html .= '</div>';

        return $html;
    }

    /**
     * Display cache lifetime field
     *
     * @param string $show Show cache lifetime or not by default
     *
     * @return string
     */
    public function showCacheLifetime($show)
    {
        $style = !$show ? 'display:none' : '';
        $globalSearchConfig = get_option('_wpfd_global_search_config');
        $cacheLifetime = (!empty($globalSearchConfig) && isset($globalSearchConfig['cache_lifetime'])
            && $globalSearchConfig['cache_lifetime'] !== '') ? $globalSearchConfig['cache_lifetime']
            : 10;
        $name = 'cache_lifetime';
        $html = '<div class="wpfd-cache-lifetime" style="' . $style . '">';
        $html .= '<label title="'.esc_html__('Set the search cache lifetime to control how long search results will be stored in cache', 'wpfd').'" class="ju-setting-label" for="cache_lifetime_val">'.esc_html__('Cache lifetime (minutes)', 'wpfd').'</label>';
        $html .= '<input type="number" min="1" name="cache_lifetime_val" id="cache_lifetime_val" value="'.$cacheLifetime.'" class="inputbox input-block-level croptitle ju-input" onChange="jQuery(\'input[name=' . $name . ']\').val(jQuery(this).val())">';
        $html .= '</div>';
        
        return $html;
    }
}
