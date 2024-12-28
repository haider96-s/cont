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
class Categorymessage extends Field
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
        $html .= $this->showCategoryMessages($val, $attributes);

        $html .= '</div>';

        return $html;
    }
    /**
     * Category message contents
     *
     * @param string $show       Show message or not by default
     * @param string $attributes Attributes of element
     *
     * @return string
     */
    public function showCategoryMessages($show, $attributes)
    {
        $style = !$show ? 'display:none' : '';
        $prefix = 'wpfd_';
        $name = $attributes['name'] . '_val';
        $globalConfig = get_option('_wpfd_global_config');
        $accessMessage = (!empty($globalConfig) && isset($globalConfig['access_message_val'])
            && $globalConfig['access_message_val'] !== '') ? $globalConfig['access_message_val']
            : esc_html__('This file category is not accessible to your account', 'wpfd');
        $emptyMessage = (!empty($globalConfig) && isset($globalConfig['empty_message_val'])
            && $globalConfig['empty_message_val'] !== '') ? $globalConfig['empty_message_val']
            : esc_html__('This file category has no files to display', 'wpfd');
        switch ($name) {
            case 'access_message_val':
                $message = $accessMessage;
                break;
            case 'empty_message_val':
                $message = $emptyMessage;
                break;
            default:
                $message = '';
                break;
        }
        $html = '<div class="wpfd-category-massage ' . $prefix . $attributes['name'] . '" style="' . $style . '">';
        $html .= '<input id="' . $prefix . $attributes['name'] . '" type="text" onChange="jQuery(\'input[name=' . $name . ']\').val(jQuery(this).val())" ';
        $html .= 'class="inputbox input-block-level ju-input" value="' . esc_attr($message) . '">';
        $html .= '</input>';
        $html .= '</div>';

        return $html;
    }
}
