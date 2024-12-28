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
class Theme extends Field
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
        if (empty($attributes['hidden']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            if (!empty($attributes['label']) && $attributes['label'] !== '' &&
                !empty($attributes['name']) && $attributes['name'] !== '') {
                $html .= '<label title="' . $tooltip . '" class="ju-setting-label" for="' . $attributes['name'] . '">';
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                $html .= esc_html__($attributes['label'], 'wpfd') . '</label>';
            }
        }
        Application::getInstance('Wpfd');
        $modelConfig = Model::getInstance('config');
        $themes      = $modelConfig->getThemes();
        $rootThemes  = array('default', 'ggd', 'preview', 'table', 'tree');
        $rootThemeTypes = get_option('wpfd_root_theme_types', array());

        $html .= '<select';
        if (!empty($attributes)) {
            foreach ($attributes as $attribute => $value) {
                if (in_array($attribute, array('id', 'class', 'onchange', 'name')) && isset($value)) {
                    $html .= ' ' . $attribute . '="' . $value . '"';
                }
            }
        }
        $html .= ' >';
        if (isset($attributes['topoption']) && $attributes['topoption'] !== '') {
            $topOption = explode('|', $attributes['topoption']);
            if (is_array($topOption) && count($topOption) > 1) {
                $topOption = array_map('trim', $topOption);
                $html .= '<option value="' . $topOption[0] . '">' . $topOption[1] . '</option>';
            }
        }

        foreach ($themes as $theme) {
            $select = '';
            if ($attributes['value'] === $theme) {
                $select = 'selected="selected"';
            }

            if (in_array($theme, $rootThemes)) {
                $rootTheme = $theme;
            } elseif (!empty($rootThemeTypes)) {
                if (array_key_exists($theme, $rootThemeTypes)) {
                    $rootTheme = $rootThemeTypes[$theme];
                }
            } else {
                $rootTheme = 'none';
            }

            $html .= '<option value="' . $theme . '" data-root_theme_type="'. $rootTheme .'" ' . $select . '>' . $theme . '</option>';
        }
        $html .= '</select>';
        if (!empty($attributes['help']) && $attributes['help'] !== '') {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
            $html .= '<p class="help-block">' . __($attributes['help'], 'wpfd') . '</p>';
        }

        $html .= '</div>';

        return $html;
    }
}
