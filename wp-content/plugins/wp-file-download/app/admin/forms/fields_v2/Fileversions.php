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

defined('ABSPATH') || die();

/**
 * Class Fileversions
 */
class Fileversions extends Field
{
    /**
     * Display files file multi category
     *
     * @param array $field Fields
     * @param array $data  Data
     *
     * @return string
     */
    public function getfield($field, $data)
    {
        $html = '<div id="fileversion" class="wpfd-form-field">
            <div class="well">
                <h4>';
        $html .= esc_html__('Send a new file version', 'wpfd');
        $html .= '</h4>
                <div id="versions_content"></div>
                <div id="dropbox_version">
                    <div class="upload">
                        <span class="message">';
                        $html .= esc_html__('Click the button below to select and replace the file', 'wpfd');
                        $html .= '</span>
                        <input class="hide" type="file" id="upload_input_version">
                        <span id="upload_button_version" class="ju-button ju-v3-button">';
        $html .= esc_html__('Select files', 'wpfd');
        $html .= '</span>
                    </div>
                    <div class="progress progress-striped active hide">
                        <div class="bar" style="width: 0;"></div>
                    </div>
                </div>
                <div class="clr"></div>
            </div>
        </div>';

        return $html;
    }
}
