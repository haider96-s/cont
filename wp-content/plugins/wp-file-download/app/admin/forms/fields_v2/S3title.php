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

defined('ABSPATH') || die();

/**
 * Class Shortcode
 */
class S3title extends Field
{

    /**
     * Display field config shortcode
     *
     * @param array $field Fields
     * @param array $data  Data
     *
     * @return string
     */
    public function getfield($field, $data)
    {
        $html = '';
        if (!empty($data) && isset($data['s3title'])) {
            $attributes = $field['@attributes'];
            $className = '';
            if (isset($attributes['name'])) {
                $className = ' wpfd-field-'.$attributes['name'];
            }
            $attributes['value'] = $data['s3title'];
            $html       = '<div class="ju-settings-option wpfd-form-field'.$className.'">';
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
            $tooltip    = isset($attributes['tooltip']) ? __($attributes['tooltip'], 'wpfd') : '';
            if (!empty($attributes['type']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
                if (!empty($attributes['label']) && $attributes['label'] !== '' &&
                    !empty($attributes['name']) && $attributes['name'] !== '') {
                    $html .= '<label title="' . $tooltip . '" class="ju-setting-label" for="' . $attributes['name'] . '">';
                    // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                    $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
                }
            }
            $class = 'ju-input ju-large-text ' . esc_html($attributes['class']);
            $html .= '<input class="' . $class . '" type="text" name="' . $attributes['name'] . '" id="' . $attributes['id'];
            $html .= '"  readonly="true" value="' . $attributes['value'] . '" />';


            $html .= '</div>';
        }

        return $html;
    }
}
