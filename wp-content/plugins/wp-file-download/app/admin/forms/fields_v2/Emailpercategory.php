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
class Emailpercategory extends Field
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
        // Check option is turn on
        $wpfdOptions = get_option('_wpfd_notifications', array());
        $isEnabled   = (isset($wpfdOptions['notify_per_category']) && intval($wpfdOptions['notify_per_category']) === 1) ? true : false;
        $html        = '<div class="ju-settings-option">';
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
                            if (!$isEnabled) {
                                $html .= '';
                            } else {
                                $html .= ' checked';
                            }
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
        if (intval($val) && !$isEnabled) {
            $val = '0';
        }
        $html .= '<input type="hidden" id="' . $attributes['name'] . '" name="' . $attributes['name'] . '" value="' . $val . '" />';
        $html .= '</div>';
        $html .= $this->showEditBtn($val);
        $html .= '</div>';

        return $html;
    }

    /**
     * Email per category editing
     *
     * @param string $show Show edit button
     *
     * @return string
     */
    public function showEditBtn($show)
    {
        $style = !$show ? 'display:none' : '';
        $html  = '<div class="wpfd-email-per-category-section" style="' . $style . '">';
        $html .= '<button id="wpfd_email_per_category_editing" type="button" class="ju-button ju-button orange-outline-button ju-button-inline" style="text-transform: capitalize">';
        $html .= esc_html__('Configuration', 'wpfd');
        $html .= '</button>';
        $html .= '</div>';
        return $html;
    }
}
