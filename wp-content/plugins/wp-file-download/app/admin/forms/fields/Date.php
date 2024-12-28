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
 * Class Date
 */
class Date extends Field
{

    /**
     * Display field date
     *
     * @param array $field Fields
     * @param array $data  Data
     *
     * @return string
     */
    public function getfield($field, $data)
    {
        $attributes = $field['@attributes'];
        $className = '';
        if (isset($attributes['name'])) {
            $className = ' wpfd-field-'.$attributes['name'];
        }
        if ($attributes['name'] === 'publish' && ($attributes['value'] === '' || $attributes['value'] === '0000-00-00 00:00:00')) {
            return '';
        }
        $html    = '';
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
        $tooltip = isset($attributes['tooltip']) ? __($attributes['tooltip'], 'wpfd') : '';
        $html    .= '<div class="control-group wpfd-form-field'.$className.'">';
        if (!empty($attributes['label']) && $attributes['label'] !== '' &&
            !empty($attributes['name']) && $attributes['name'] !== '') {
            $html .= '<label title="' . $tooltip . '" class="control-label" for="' . $attributes['name'] . '">';
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
            $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
        }
        $html         .= '<div class="controls">';
        Application::getInstance('Wpfd');
        $configModel  = Model::getInstance('config');
        $globalConfig = $configModel->getConfig();
        $html         .= '<div class="input-append">
                    <input type="text" name="' . $attributes['name'] . '" id="' . $attributes['name'] . '" 
                        value="' . $attributes['value'] . '"
                        maxlength="45"
                        autocomplete="off"
                        class="' . $attributes['class'] . '">
                    <button type="button" class="btn" id="' . $attributes['name'] . '_img">
                        <span class="icon-calendar"></span>
                     </button>
                </div>';
        $html         .= '</div></div>';

        return $html;
    }
}
