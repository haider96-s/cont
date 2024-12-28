<?php
namespace Joomunited\WP_File_Download\Admin\Fields;

wp_enqueue_media();
use Joomunited\WPFramework\v1_0_6\Field;

defined('ABSPATH') || die();

// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- nonce verified on $form->validate()
/**
 * Class Watermarkcategorypathglobal
 */
class Watermarkcategorypathglobal extends Field
{
    /**
     * Display media button
     *
     * @param array $field Fields
     * @param array $data  Data
     *
     * @return string
     */
    public function getfield($field, $data)
    {
        $attributes = $field['@attributes'];

        $html    = '';
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
        $tooltip = isset($attributes['tooltip']) ? __($attributes['tooltip'], 'wpfd') : '';
        $html    .= '<div class="ju-settings-option">';
        if (!empty($attributes['label']) && $attributes['label'] !== '' &&
            !empty($attributes['name']) && $attributes['name'] !== '') {
            $html .= '<label title="' . $tooltip . '" class="ju-setting-label" for="' . $attributes['name'] . '">';
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
            $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
        }
        $html .= '<div class="controls-media-button">';
        $html .= '<input type="text" readonly="true" ';
        if (!empty($attributes)) {
            foreach ($attributes as $attribute => $value) {
                if (in_array($attribute, array('id', 'class', 'name', 'value', 'size')) && isset($value)) {
                    $html .= ' ' . $attribute . '="' . $value . '"';
                }
            }
        }
        $html .= ' />';
        $html .= '<input id="wm_category_select_media_button_global" class="button select-media" type="button"';
        $html .= 'value="'.esc_html('Select', 'wpfd').'" data-editor="content" />';

        $html .= '</div></div>';

        return $html;
    }
}
