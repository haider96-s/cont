<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

// No direct access.
defined('ABSPATH') || die();

?>
<fieldset id="category-layout">
    <legend><?php esc_html_e('Category layout', 'wpfd'); ?></legend>
    <?php
    $this->renderSwitcher('stylingmenu', esc_html__('Column display', 'wpfd'));
    $this->renderSwitcher('showcategorytitle', esc_html__('Show category title', 'wpfd'));
    $this->renderSwitcher('showsubcategories', esc_html__('Show subcategories', 'wpfd'));
    $this->renderSwitcher('showbreadcrumb', esc_html__('Show Breadcrumb', 'wpfd'));
    $this->renderSelectOptions(
        'showfoldertree',
        esc_html__('Show folder tree', 'wpfd'),
        array('0' => 'No', '1' => 'Left', '2' => 'Top', '3' => 'Right', '4' => 'Bottom')
    );
    $this->renderSwitcher('expanded_folder_tree', esc_html__('Expanded folder tree', 'wpfd'));
    $this->renderSwitcher('showuploadform', esc_html__('Show upload form', 'wpfd'));
    $this->renderSwitcher('displayfilesearch', esc_html__('Display file search', 'wpfd'));
    $this->renderColor('bgcolor', esc_html__('Background color', 'wpfd'));
    ?>
</fieldset>
<fieldset id="file-layout">
    <legend><?php esc_html_e('File block layout', 'wpfd'); ?></legend>
    <?php
    $this->renderSwitcher('showtitle', esc_html__('Show title', 'wpfd'));
    $this->renderSwitcher('showdescription', esc_html__('Show description', 'wpfd'));
    $this->renderSwitcher('showsize', esc_html__('Show file size', 'wpfd'));
    $this->renderSwitcher('showversion', esc_html__('Show version', 'wpfd'));
    $this->renderSwitcher('showhits', esc_html__('Show hits', 'wpfd'));
    $this->renderSwitcher('showdownload', esc_html__('Show download link', 'wpfd'));
    $this->renderSwitcher('showdateadd', esc_html__('Show date added', 'wpfd'));
    $this->renderSwitcher('showdatemodified', esc_html__('Show date modified', 'wpfd'));

    $this->renderText('croptitle', esc_html__('Crop titles', 'wpfd'));
    $this->renderColor('bgdownloadlink', esc_html__('Background download link', 'wpfd'));
    $this->renderColor('colordownloadlink', esc_html__('Color download link', 'wpfd'));
    ?>
</fieldset>

