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
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();

/**
 * Class Theme
 */
class Themesetting extends Field
{
    /**
     * Display theme config
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
        $id = isset($attributes['id']) ? $attributes['id'] : '';
        $columns = array('title', 'description', 'category', 'version', 'size', 'hits', 'date added', 'download');
        if (empty($attributes['hidden']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            if (!empty($attributes['label']) && $attributes['label'] !== '' &&
                !empty($attributes['name']) && $attributes['name'] !== '') {
                $html .= '<label title="' . $tooltip . '" class="ju-setting-label" for="' . $attributes['name'] . '">';
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
            }
        }

        $html .= '<div id="'.$id.'">';
        $html .= '<ul id="wpfd-sortable-list">';
        foreach ($columns as $key => $value) {
            $serial = $key + 1;
            $inputVal = 1;
            $inputAttr = 'checked=""';
            if ($value === 'category') {
                $inputVal = 0;
                $inputAttr = '';
            }
            $html .= '<li class="wpfd-sortable-item" data-serial="'.$serial.'" data-value="'.esc_html($value).'">';
            $html .= '<label>'.esc_html($value).'</label>';
            if ($value === 'title') {
                $html .= '<input type="hidden" id="update_theme_setting_'.esc_html(str_replace(' ', '_', $value)).'" name="update_theme_setting_'.esc_html(str_replace(' ', '_', $value)).'" value="1">';
            } else {
                $html .= '<div class="ju-switch-button">
                <label class="switch"><input type="checkbox" name="ref_update_theme_setting_'.esc_html(str_replace(' ', '_', $value)).'" value="'.esc_attr($inputVal).'" '.esc_attr($inputAttr).' class="inputbox input-block-level"><span class="slider"></span></label>
                <input type="hidden" id="update_theme_setting_'.esc_html(str_replace(' ', '_', $value)).'" name="update_theme_setting_'.esc_html(str_replace(' ', '_', $value)).'" value="'.esc_attr($inputVal).'">
                </div>';
            }
            $html .= '</li>';
        }
        $html .= '<input type="hidden" id="wpfd-sorted-list" value="'.esc_html(implode(',', $columns)).'">';
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
