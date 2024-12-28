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
 * Switcher multi selectbox class
 */
class Switchermultiselect extends Field
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
        $html .= $this->showSelectOptions($val, $attributes);

        $html .= '</div>';

        return $html;
    }
    /**
     * Select box options
     *
     * @param string $show       Show message or not by default
     * @param string $attributes Attributes of element
     *
     * @return string
     */
    public function showSelectOptions($show, $attributes)
    {
        $style = !$show ? 'display:none' : '';
        $name = isset($attributes['name']) ? $attributes['name'] : '';
        $listNumbers = array('10', '15', '20', '30', '50', '100', '150', '200');
        $globalConfig = get_option('_wpfd_global_config', array());
        switch ($name) {
            case 'admin_pagination':
                $val = (!empty($globalConfig) && isset($globalConfig['admin_pagination_number'])) ? $globalConfig['admin_pagination_number'] : '10';
                break;
            case 'admin_load_more':
                $val = (!empty($globalConfig) && isset($globalConfig['admin_load_more_number'])) ? $globalConfig['admin_load_more_number'] : '10';
                break;
            default:
                $val = '10';
                break;
        }
        $html = '<div class="wpfd-file-category-list-number ' . $name . '" style="' . $style . '">';
        $html .= '<label class="ju-setting-label" for="' . $name . '_number">';
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
        $html .= esc_html__('Files per page', 'wpfd') . '</label>';
        $html .= '<select id="wpfd_' . $name . '" name="' . $name . '_number" onChange="jQuery(\'input[name=' . $name . '_number]\').val(jQuery(this).val())" ';
        $html .= 'class="inputbox input-block-level ju-input" value="' . esc_attr($val) . '">';

        foreach ($listNumbers as $number) {
            if (intval($val) === intval($number)) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }

            $html .= '<option value="'. $number .'" '. $selected .'>'. $number .'</option>';
        }

        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }
}
