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
 * Class limit the download
 */
class Limitthedownload extends Field
{
    /**
     * Display limit download settings
     *
     * @param array $field Fields
     * @param array $data  Data
     *
     * @return string
     */
    public function getfield($field, $data)
    {
        $attributes     = $field['@attributes'];
        $fullWidthClass = (isset($attributes['value']) && intval($attributes['value']) === 1) ? 'full-width' : '';
        $html           = '<div class="limit-download-settings-container '. $fullWidthClass .'">';
        $html          .= '<div class="ju-settings-option ju-setting-limit-download">';
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
        $tooltip        = isset($attributes['tooltip']) ? __($attributes['tooltip'], 'wpfd') : '';
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
        $val   = ($inputValue === '' || (string)$inputValue === '0') ? '0' : '1';
        $html .= '<input type="hidden" id="' . $attributes['name'] . '" name="' . $attributes['name'] . '" value="' . $val . '" />';
        $html .= '</div>';

        if (!empty($attributes['help']) && $attributes['help'] !== '') {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Possibility to translate by our deployment script
            $html .= '<p class="help-block">' . __($attributes['help'], 'wpfd') . '</p>';
        }

        $html .= '</div>';
        $html .= $this->getFullLimitDownloadSettings($val);
        $html .= '</div>';

        return $html;
    }

    /**
     * Display setting table
     *
     * @param string|integer $val Limit download option value
     *
     * @return string
     */
    public function getFullLimitDownloadSettings($val = '0')
    {
        $output       = '';
        $c_roles      = wpfd_admin_ui_user_roles_get_roles();
        $roles        = $c_roles->role_objects;
        $roles_name   = $c_roles->role_names;
        $style        = intval($val) !== 1 ? 'display: none;' : '';
        $output      .= '<div class="limit-download-table-list" style="'. $style .'">';

        if (is_countable($roles) && !empty($roles)) {
            $output .= '<table>';
            $output .= '<tr>';
            $output .= '<th class="role"><label class="ju-setting-label">'. esc_html__('Role', 'wpfd') .'</label></th>';
            $output .= '<th><label class="ju-setting-label" title="'. esc_html__('If filled, this will limit the number of download for each file per user in this user group', 'wpfd') .'">';
            $output .= esc_html__('Download limit', 'wpfd') .'</label></th>';
            $output .= '<th><label class="ju-setting-label" title="'. esc_html__('If filled, this will limit the delay during which the files are available per user in this user group', 'wpfd') .'">';
            $output .= esc_html__('Time limit', 'wpfd') .'</label></th>';
            $output .= '</tr>';

            foreach ($roles as $name => $role) {
                if (!isset($role->name) || $role->name === '') {
                    continue;
                }
                $readableName = $roles_name[$role->name];
                $output .= '<tr>';
                $output .= '<td class="role"><h4>' . $readableName . '</h4></td>';
                $output .= '<td>' . $this->getLimitDownloadNumber($role) . '</td>';
                $output .= '<td>' . $this->getLimitDownloadTime($role) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</table>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * Display the limit download setting
     *
     * @param string $role Role object
     *
     * @return string
     */
    public function getLimitDownloadNumber($role)
    {
        $globalConfig = get_option('_wpfd_global_config', array());
        $name = $role->name;
        $downloadLimitSetting = isset($globalConfig['download_limit_settings']) ? (array) $globalConfig['download_limit_settings'] : array();
        $roleSetting = isset($downloadLimitSetting[$name]) ? (array) $downloadLimitSetting[$name] : array();
        $val = isset($roleSetting['limit_download_number']) ? $roleSetting['limit_download_number'] : '';
        $output = '<input type="number" name="'. $name .'[limit_download_number]" class="inputbox input-block-level ju-input limit-download-input" value="'. $val .'" maxlength = "3" min = "1" max = "999" />';

        return $output;
    }

    /**
     * Display the time delay setting
     *
     * @param string $role Role object
     *
     * @return string
     */
    public function getLimitDownloadTime($role)
    {
        $globalConfig = get_option('_wpfd_global_config', array());
        $downloadLimitSetting = isset($globalConfig['download_limit_settings']) ? (array) $globalConfig['download_limit_settings'] : array();
        $roleSetting = isset($downloadLimitSetting[$role->name]) ? (array) $downloadLimitSetting[$role->name] : array();
        $val      = isset($roleSetting['limit_download_time_number']) ? $roleSetting['limit_download_time_number'] : '';
        $output   = '<input type="number" name="'. $role->name .'[limit_download_time_number]" class="inputbox input-block-level ju-input limit-download-input" value="'. $val .'" maxlength = "3" min = "1" max = "999" />';
        $output  .= '<select name="'. $role->name .'[limit_download_time_type]">';
        $timeType = array('Hour', 'Day', 'Week', 'Month', 'Year');
        foreach ($timeType as $type) {
            $key = strtolower($type);
            $selected = (isset($roleSetting['limit_download_time_type']) && (string) $roleSetting['limit_download_time_type'] === (string) $key) ? 'selected="selected"' : '';
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is not problem
            $output  .= '<option value="'. $key .'" '. $selected .'>' . esc_html__($type, 'wpfd') . '</option>';
        }
        $output  .= '</select>';

        return $output;
    }
}
