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
 * Class Ordering
 */
class Ordering extends Field
{
    /**
     * Display ordering
     *
     * @param array $field Fields
     * @param array $data  Data
     *
     * @return string
     */
    public function getfield($field, $data)
    {
        $attributes = $field['@attributes'];
        if (!isset($attributes['name']) || $attributes['name'] === '') {
            return '';
        }
        $globalConfig = get_option('_wpfd_global_config', array());
        $totalVal = (isset($globalConfig[$attributes['name'] . '_all']) && intval($globalConfig[$attributes['name'] . '_all']) === 1) ? true : false;

        $html = '<div class="ju-settings-option">';
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
        $tooltip = isset($attributes['tooltip']) ? __($attributes['tooltip'], 'wpfd') : '';
        if (empty($attributes['hidden']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            if (!empty($attributes['label']) && $attributes['label'] !== '' &&
                !empty($attributes['name']) && $attributes['name'] !== '') {
                $html .= '<label title="' . $tooltip . '" class="ju-setting-label" for="' . $attributes['name'] . '">';
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
            }
        }

        $html .= '<select';
        if (!empty($attributes)) {
            foreach ($attributes as $attribute => $value) {
                if (in_array($attribute, array('id', 'class', 'onchange', 'name')) && isset($value)) {
                    $html .= ' ' . $attribute . '="' . $value . '"';
                }
            }
        }
        $html       .= ' >';
        $cleanfield = $field;
        unset($cleanfield['@attributes']);
        if (!empty($cleanfield[0])) {
            $attributearray = array('type', 'id', 'class', 'name', 'onchange', 'value');
            foreach ($cleanfield[0] as $child) {
                if (!empty($child['option']['@attributes'])) {
                    $html .= '<option ';
                    foreach ($child['option']['@attributes'] as $childAttribute => $childValue) {
                        if (in_array($childAttribute, $attributearray) && isset($childValue)) {
                            $html .= ' ' . $childAttribute . '="' . $childValue . '"';
                            if ($childAttribute === 'value' && isset($attributes['value']) &&
                                $attributes['value'] === $childValue) {
                                $html .= ' selected="selected"';
                            }
                        }
                    }
                    $html .= '>';
                    // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                    $html .= esc_html__($child['option'][0], 'wpfd');
                    $html .= '</option>';
                }
            }
        }
        $html .= '</select>';

        $html .= '<div class="ordering-checkbox-section">';
        $html .= '<input class="ju-checkbox" type="checkbox" rel="'. $attributes['name'] .'_all"';
        $html .= ' onChange="jQuery(\'input[name=' . $attributes['name'] . '_all]\').val(jQuery(this).is(\':checked\') ? 1 : 0)"';
        $html .= $totalVal ? ' checked' : '';
        $html .= '>' . esc_html__('Apply to existing categories', 'wpfd');
        $html .= '</div>';

        if (!empty($attributes['help']) && $attributes['help'] !== '') {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
            $html .= '<div class="ju-settings-help">' . __($attributes['help'], 'wpfd') . '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}