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

defined('ABSPATH') || die();

/**
 * Class Text2
 */
class Seourl extends Field
{
    /**
     * Render <input> tag
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
        if (!empty($attributes['type']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            if (!empty($attributes['label']) && $attributes['label'] !== '' &&
                !empty($attributes['name']) && $attributes['name'] !== '') {
                $html .= '<label title="' . $tooltip . '" class="ju-setting-label" for="' . $attributes['id'] . '">';
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
            }
        }
        // Remove download file link extension check box
        $html .= '<div class="ju-settings-toolbox">';
        $html .= '&nbsp;<input class="ju-checkbox" type="checkbox" rel="rmdownloadext" onChange="jQuery(\'input[name=rmdownloadext]\').val(jQuery(this).is(\':checked\') ? 1 : 0)" />&nbsp;' . esc_html__('Remove download file link extension', 'wpfd');
        if ((function_exists('is_wpe') && is_wpe()) || defined('KINSTAMU_VERSION') || (isset($_SERVER['HTTP_CF_WORKER']) && $_SERVER['HTTP_CF_WORKER'] === 'kinsta.cloud')) {
            $warningIcon = '<svg xmlns="http://www.w3.org/2000/svg" style="margin-bottom: -4px;" width="24" height="24" viewBox="0 0 64 64"><defs/><defs><linearGradient id="a" x2="0" y1="45.5" y2="-.6" gradientTransform="matrix(1.31117 0 0 1.30239 737 160)" gradientUnits="userSpaceOnUse"><stop stop-color="#ffc515"/><stop offset="1" stop-color="#ffd55b"/></linearGradient></defs><path fill="url(#a)" d="m797.94 212.01l-25.607-48c-.736-1.333-2.068-2.074-3.551-2.074-1.483 0-2.822.889-3.569 2.222l-25.417 48c-.598 1.185-.605 2.815.132 4 .737 1.185 1.921 1.778 3.404 1.778h51.02c1.483 0 2.821-.741 3.42-1.926.747-1.185.753-2.667.165-4" transform="translate(-627 -131) scale(.85714)"/><path fill="#fff" d="m-26.309 18.07c-1.18 0-2.135.968-2.135 2.129v12.82c0 1.176.948 2.129 2.135 2.129 1.183 0 2.135-.968 2.135-2.129v-12.82c0-1.176-.946-2.129-2.135-2.129zm0 21.348c-1.18 0-2.135.954-2.135 2.135 0 1.18.954 2.135 2.135 2.135 1.181 0 2.135-.954 2.135-2.135 0-1.18-.952-2.135-2.135-2.135z" transform="matrix(.90168 0 0 .90168 56 8)"/></svg>';
            $warningText = esc_html__('Your hosting will not allow to download some files if their file extension exists in the url, click to go to our documentation page.', 'wpfd');
            $warning = '<a href="https://www.joomunited.com/support/wordpress-plugins-documentation" target="_blank" title="'.$warningText.'">' . $warningIcon . '</a>';
            $html .= $warning;
        }
        $html .= '<script>jQuery(document).ready(function() {jQuery(\'input[rel=rmdownloadext]\').prop(\'checked\', jQuery(\'input[name=rmdownloadext]\').val() === \'1\' ? true : false);})</script>';
        $html .= '</div>';
        if (empty($attributes['hidden']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            $html .= '<input';
            $html .= ' type="text"';
        } else {
            $html .= '<hidden';
        }

        if (!empty($attributes)) {
            $attribute_array = array('id', 'class', 'placeholder', 'name', 'value', 'placeholder');
            foreach ($attributes as $attribute => $value) {
                if (in_array($attribute, $attribute_array) && isset($value)) {
                    $html .= ' ' . $attribute . '="' . $value . '"';
                }
            }
        }
        $html .= ' />';
        // Force remove file extension check box

        if (!empty($attributes['help']) && $attributes['help'] !== '') {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
            $html .= '<p class="help-block">' . __($attributes['help'], 'wpfd') . '</p>';
        }
//        if (!empty($attributes['type']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
//            $html .= '</div></div>';
//        }
        $html .= '</div>';
        return $html;
    }
}
