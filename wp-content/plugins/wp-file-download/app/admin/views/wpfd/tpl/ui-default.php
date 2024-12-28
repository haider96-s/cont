<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0W
 */

use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;

// No direct access.
defined('ABSPATH') || die();

if (!wpfd_can_manage_file()) {
    wp_die(esc_html__('You don\'t have permission to view this page', 'wpfd'));
}

wp_localize_script('wpfd-main', 'l10n', array(
    'Drag & Drop your Document here'                   => esc_html__('Drag & Drop your Document here', 'wpfd'),
    'Add remote file'                                  => esc_html__('Add remote file', 'wpfd'),
    'Allowed extensions'                               => esc_html__('Allowed extensions', 'wpfd'),
    'SEO URL'                                          => esc_html__('SEO URL', 'wpfd'),
    'Show files import'                                => esc_html__('Show files import', 'wpfd'),
    'Max upload file size (Mb)'                        => esc_html__('Max upload file size (Mb)', 'wpfd'),
    'Delete all files on uninstall'                    => esc_html__('Delete all files on uninstall', 'wpfd'),
    'Close categories'                                 => esc_html__('Close categories', 'wpfd'),
    'Theme per categories'                             => esc_html__('Theme per categories', 'wpfd'),
    'Default theme per category'                       => esc_html__('Default theme per category', 'wpfd'),
    'Date format'                                      => esc_html__('Date format', 'wpfd'),
    'Use viewer'                                       => esc_html__('Use viewer', 'wpfd'),
    'Extensions to open with viewer'                   => esc_html__('Extensions to open with viewer', 'wpfd'),
    'GA download tracking'                             => esc_html__('GA download tracking', 'wpfd'),
    'Single user restriction'                          => esc_html__('Single user restriction', 'wpfd'),
    'Use WYSIWYG editor'                               => esc_html__('Use WYSIWYG editor', 'wpfd'),
    'Load the plugin on frontend'                      => esc_html__('Load the plugin on frontend', 'wpfd'),
    'Category owner'                                   => esc_html__('Category owner', 'wpfd'),
    'Search page'                                      => esc_html__('Search page', 'wpfd'),
    'Plain text search'                                => esc_html__('Plain text search', 'wpfd'),
    'Are you sure'                                     => esc_html__('Are you sure', 'wpfd'),
    'Delete'                                           => esc_html__('Delete', 'wpfd'),
    'Edit'                                             => esc_html__('Edit', 'wpfd'),
    'Your browser does not support HTML5 file uploads' => esc_html__('Your browser does not support HTML5 file uploads!', 'wpfd'),
    'Too many files'                                   => esc_html__('Too many files', 'wpfd'),
    'is too large'                                     => esc_html__('is too large', 'wpfd'),
    'Only images are allowed'                          => esc_html__('Only images are allowed', 'wpfd'),
    'Do you want to delete'                            => esc_html__('Do you want to delete', 'wpfd'),
    'Select a folder'                                  => esc_html__('Select a folder', 'wpfd'),
    'Select files'                                     => esc_html__('Select files', 'wpfd'),
    'Image parameters'                                 => esc_html__('Image parameters', 'wpfd'),
    'Close'                                            => esc_html__('Close', 'wpfd'),
    'Cancel'                                           => esc_html__('Cancel', 'wpfd'),
    'Ok'                                               => esc_html__('Ok', 'wpfd'),
    'Confirm'                                          => esc_html__('Confirm', 'wpfd'),
    'Save'                                             => esc_html__('Save', 'wpfd'),
    'Title'                                            => esc_html__('Title', 'wpfd'),
    'Remote URL'                                       => esc_html__('Remote URL', 'wpfd'),
    'URL'                                              => esc_html__('URL', 'wpfd'),
    'File Type'                                        => esc_html__('File Type', 'wpfd'),
    'close_categories'                                 => WpfdBase::loadValue($this->globalConfig, 'close_categories', 0),
    'add_remote_file'                                  => WpfdBase::loadValue($this->globalConfig, 'add_remote_file', 0),
    'Are you sure restore file'                        => esc_html__('Are you sure you want to restore the file: ', 'wpfd'),
    'Are you sure remove version'                      => esc_html__('Are you sure you want to definitively remove this file version', 'wpfd'),
    'Deleting...'                                      => esc_html__('Deleting...', 'wpfd'),
    'Please select file(s)'                            => esc_html__('Please select file(s)', 'wpfd'),
    'There is no copied/cut files yet'                 => esc_html__('There is no copied/cut files yet', 'wpfd'),
    'This type of file is not allowed to be uploaded. You can add new file types in the plugin configuration' => esc_html__('This type of file is not allowed to be uploaded. You can add new file types in the plugin configuration', 'wpfd'),

    'uploaded successfully'                           => esc_html__('uploaded successfully', 'wpfd'),
    'error while uploading'                           => esc_html__('error while uploading', 'wpfd'),
    'files imported'                                  => esc_html__('files imported', 'wpfd'),
    'Are you sure to disconnect'                      => esc_html__('Are you sure to disconnect', 'wpfd'),
    'Checking Authorization Code....'                 => esc_html__('Checking Authorization Code....', 'wpfd'),
    'Something wrong! Check Console Tab for details.' => esc_html__('Something wrong! Check Console Tab for details.', 'wpfd'),
    'Success! Page will reload now...'                => esc_html__('Success! Page will reload now...', 'wpfd'),
    'Pending...'                                      => esc_html__('Pending...', 'wpfd'),
    'Edit Product'                                    => esc_html__('Edit Product', 'wpfd'),
    'New product created successfully!'               => esc_html__('New product created successfully!', 'wpfd'),
    'admin_pagination'                                => isset($this->globalConfig['admin_pagination']) ? $this->globalConfig['admin_pagination'] : 0,
    'admin_pagination_number'                         => isset($this->globalConfig['admin_pagination_number']) ? $this->globalConfig['admin_pagination_number'] : 10,
));

if (Utilities::getInput('caninsert', 'GET', 'bool')) {
    global $hook_suffix;
    _wp_admin_html_begin();
    do_action('admin_enqueue_scripts', $hook_suffix);
    do_action('admin_print_scripts-' . $hook_suffix);
    do_action('admin_print_scripts');
    if ((is_plugin_active('polylang/polylang.php') && class_exists('Polylang')) ||
        (is_plugin_active('wp-fastest-cache/wpFastestCache.php') && class_exists('WpFastestCache'))
    ) {
        echo '<script type="text/javascript">
           var ajaxurl = "' . esc_url(admin_url('admin-ajax.php')) . '";
         </script>';
    }
}

$alone = '';

if (!class_exists('WpfdControllerCategories')) {
    $ds = DIRECTORY_SEPARATOR;
    $categoriesControlClassPath = WPFD_PLUGIN_DIR_PATH . 'app' . $ds . 'admin' . $ds . 'controllers' . $ds . 'categories.php';
    require_once $categoriesControlClassPath;
}

$categoriesColtroller = new WpfdControllerCategories();
$iconModel = Model::getInstance('iconsbuilder');
$svgParams = $iconModel->getParams('svg');
$svgIconParams = reset($svgParams['icons']);
?>
<script type="text/javascript">
    wpfdajaxurl = "<?php echo wpfd_sanitize_ajax_url(Application::getInstance('Wpfd')->getAjaxUrl()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keep this, if not it error on backend?>";
    // Fix conflict with WPML
    if (wpfdajaxurl.substr(-1, 1) !== '&') {
        wpfdajaxurl = wpfdajaxurl + '&';
    }
    dir = "<?php echo esc_url(Application::getInstance('Wpfd')->getBaseUrl()); ?>";
    <?php if (Utilities::getInput('caninsert', 'GET', 'bool')) : ?>
    gcaninsert = true;
        <?php $alone = 'wpfdalone wp-core-ui '; ?>
    <?php else : ?>
    gcaninsert = false;
    <?php endif; ?>
    if (typeof(addLoadEvent) === 'undefined') {
        addLoadEvent = function (func) {
            if (typeof jQuery !== "undefined") {
                jQuery(document).ready(func);
            }
            else if (typeof wpOnload !== 'function') {
                wpOnload = func;
            } else {
                var oldonload = wpOnload;
                wpOnload = function () {
                    oldonload();
                    func();
                }
            }
        };
    }
</script>
<?php if (Utilities::getInput('caninsert', 'GET', 'bool')) : ?>
    <style>
        html.wp-toolbar {
            padding-top: 0 !important
        }
    </style>
<?php endif;
/**
 * Action to write import notice
 */
do_action('wpdf_admin_notices');
?>
<?php include 'ui-contextmenu.php'; ?>
<?php do_action('wpfd_before_core', $this); ?>
<div id="wpfd-core" class="<?php echo esc_attr($alone); ?>">
    <div id="wpfd-categories-col" class="wpfd-column">
        <?php if (wpfd_can_create_category()) :
            $class = '';
            $isCloud = wpfd_is_cloud_exists();
            if ($isCloud) {
                $class = 'hasCloud';
            }
            ?>
            <div id="newcategory" class="ju-dropdown-wrapper <?php echo $isCloud ? 'hasCloud' : ''; ?>">
                <a class="ju-button ju-v3-button wpfd_add_new" href="#">
                    <i class="material-icons">add</i>
                    <?php esc_html_e('Add Category', 'wpfd'); ?>
                </a>
                <ul class="ju-dropdown-menu">
                    <?php
                    /**
                     * Action fire for display Dropdown
                     *
                     * @internal
                     */
                    do_action('wpfd_addon_dropdown');
                    ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php include 'ui-categories-filter.php'; ?>
        <div class="wpfd-pseudo-top-cat">
            <img src="data:image/png;base64,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"
                 width="26" height="26" alt="<?php esc_html_e('WP File Download', 'wpfd'); ?>">
            <span class="wpfd-pseudo-label"><?php esc_html_e('WP File Download', 'wpfd'); ?></span>
        </div>
        <!-- display button connect to cloud -->
        <div class="scroller_wrapper_inner">
            <?php if (!wpfd_can_create_category() && empty($this->categories)) : ?>
                <?php esc_html_e('Sorry, your user role does not have access to any files category', 'wpfd'); ?>
            <?php endif; ?>

            <?php  ?>
            <div class="nested dd">
                <ol id="categorieslist" class="dd-list nav bs-docs-sidenav2">
                    <?php

                    if (!empty($this->categories)) :
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Show result only
                        echo $categoriesColtroller->listTreeView($this->categories);
                    endif;

                    ?>
                </ol>
                <input type="hidden" id="categoryToken" name=""/>
            </div>
        </div>
        <div id="wpfd-hamburger">
            <a href="javascript: void();" class="dashicons dashicons-leftright"></a>
            <span><?php esc_html_e('Categories', 'wpfd'); ?></span>
        </div>
    </div>

    <div id="pwrapper" class="wpfd-column">
        <div id="wpreview">
            <div class="preview-top">
                <?php include 'ui-search.php'; ?>
                <?php include 'ui-insert-buttons.php'; ?>
            </div>
            <?php
            $class = (wpfd_can_edit_category() || wpfd_can_edit_own_category()) ? 'has-wpfd' : 'no-wpfd';
            ?>
            <div class="wpfd_center">
                <div id="loader" style="display:none; text-align: center">
                    <img src="<?php echo esc_url(WPFD_PLUGIN_URL . '/app/site/assets/images/searchloader.svg'); ?>" style="margin: 0 auto"/>
                </div>
                <div id="preview" class="<?php echo esc_attr($class); ?>">
                </div>
            </div>
        </div>
        <?php if (wpfd_can_edit_category() || wpfd_can_edit_own_category() || Utilities::getInput('caninsert', 'GET', 'bool')) { ?>
            <div id="rightcol" class="wpfd-column">
                <?php if (wpfd_can_edit_category() || wpfd_can_edit_own_category()) { ?>
                    <div class="categoryblock">
                        <div class="well">
                            <!--                        <h4>--><?php //esc_html_e('Parameters', 'wpfd'); ?><!--</h4>-->
                            <div id="galleryparams">
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
        <input type="hidden" name="id_category" value=""/>
    </div>


    <div id="wpfd_status">
        <div class="wpfd_status_header">
            <span class="header_title"><?php esc_html_e('File upload status', 'wpfd'); ?></span>
            <span class="toolbox minimize"></span>
        </div>
        <div class="wpfd_status_body">
        </div>
        <div class="wpfd_status_footer"></div>
    </div>
    <div id="wpfd_ios_category_menu">
        <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 0 24 24" width="30px" fill="#fff"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </div>
    <div id="wpfd_ios_file_menu">
        <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 0 24 24" width="30px" fill="#fff"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M8 16h8v2H8zm0-4h8v2H8zm6-10H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
    </div>
</div>
<?php do_action('wpfd_after_core', $this); ?>
<div id="wpfd_archive_file_creation" style="position: absolute;top:0;left:0;right:0;bottom:0;display:none;">
    <div class="wpfd-modal-backdrop fade in"></div>
    <div class="wpfd-modal">
        <div class="wpfd-modal-header">
            <ul class="wpfd-tabs">
                <li class="wpfd-tab ju-button ju-rect-button ju-link-button active" data-tab-id="wpfd-create-archive-zip">
                    <?php esc_html_e('Create archive ZIP file', 'wpfd'); ?>
                </li>
            </ul>
        </div>
        <div class="wpfd-modal-body">
            <div class="wpfd-tabs-wrapper">
                <div class="wpfd-tabs-content">
                    <div class="wpfd-tab-content active" id="wpfd-create-archive-zip">
                        <div class="wpfd-flex">
                            <div id="wpfd_archive_zip_general">
                                <div class="wpfd_archive_zip_files_wrapper" style="float: left;width: 100%">
                                    <input type="hidden" name="wpfd_archive_zip_files" value="" />
                                    <input type="hidden" name="wpfd_archive_zip_catids" value="" />
                                    <div class="wpfd-files-table" id="wpfd-archive-file-table" style="flex: 1;"></div>
                                </div>
                                <div class="archive_zip_general_main">
                                    <div class="ju-settings-option">
                                        <label class="ju-setting-label" for="zip_title"><?php esc_html_e('Archive Name', 'wpfd'); ?></label>
                                        <input type="text" name="zip_title" id="zip_title" class="ju-input"/>
                                        <span class="wpfd-validate-msg"><?php esc_html_e('Please enter the archive name!', 'wpfd'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wpfd-modal-footer">
            <button type="button" class="ju-button ju-rect-button ju-link-button js-modalCancel"><?php esc_html_e('Cancel', 'wpfd') ?></button>
            <button type="button" class="ju-button ju-rect-button ju-v3-material js-saveArchiveFileOpen"><?php esc_html_e('Create', 'wpfd'); ?></button>
        </div>
    </div>
</div>
<div id="wpfd_file_change_icons" style="position: absolute;top:0;left:0;right:0;bottom:0;display:none;">
    <div class="wpfd-modal-backdrop fade in"></div>
    <div class="wpfd-modal">
        <div class="wpfd-modal-header">
            <ul class="wpfd-tabs" style="vertical-align: middle; margin: 10px 0;">
                <li class="wpfd-tab ju-button ju-rect-button ju-link-button active" style="font-size: 25px; margin: 0; line-height: 1"
                    data-tab-id="wpfd-file-change-icons">
                    <?php esc_html_e('Quick edit:', 'wpfd'); ?>
                </li>
            </ul>
            <div class="wpfd-edit-details" style="min-width: 250px; margin: 10px 25px 10px 0;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpfd-icons-builder')); ?>" id="wpfd_icons_builder_url"
                   style="font-size: 14px; width: auto; line-height: 26px; box-sizing: border-box; margin: 0; vertical-align: middle; display: block; padding: 5px 20px; float: right; max-width: unset;" target="_blank">
                    <?php esc_html_e('Edit details', 'wpfd'); ?>
                </a>
            </div>
        </div>
        <div class="wpfd-modal-body">
            <div class="wpfd-tabs-wrapper">
                <div class="wpfd-tabs-content">
                    <div class="wpfd-tab-content active" id="wpfd-file-change-icons">
                        <div class="wpfd-flex" style="width: 100%; flex-direction: column;">
                            <div id="svg-icons-editor" class="svg-icons-editor" style="display: none;">
                                <div class="wpfd-icons-loading"></div>
                                <div class="wpfd-svg-editor-wrapper">
                                    <!-- Preview -->
                                    <div class="wpfd-editor-preview wpfd-card wpfd-card-nobg wpfd-flex" style="display: none">
                                        <div class="svg_placeholder"></div>
                                        <div class="wpfd-editor-actions wpfd-flex wpfd-flex-column"></div>
                                    </div>
                                    <!-- .Preview -->
                                    <div class="wpfd-scroll-wrapper">
                                        <!-- Icon -->
                                        <div class="wpfd-card wpfd-collapse" style="margin-top: 0"
                                             data-close-group="extension_name,frame_settings,wrapper"
                                             data-collapse-name="icon">
                                            <div class="wpfd-card-header">
                                                <span class="material-icons wpfd-collapse--icon">expand_more</span>
                                                <span class="wpfd-flex wpfd-flex-column">
                                                    <span class="card-title"><?php esc_html_e('ICON', 'wpfd'); ?></span>
                                                    <span class="card-description"><?php esc_html_e('Change the icon inside the icon', 'wpfd'); ?></span>
                                                </span>
                                                <div class="ju-switch-button">
                                                    <?php esc_html_e('Visible', 'wpfd'); ?>
                                                    <label class="switch">
                                                        <?php $checked = intval($svgIconParams['icon-active']) === 1 ? 'checked="checked"' : ''; ?>
                                                        <input type="checkbox"
                                                               name="ref_icon-active" <?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's OK ?>
                                                        />
                                                        <span class="slider"></span>
                                                    </label>
                                                    <input type="hidden" name="icon-active"
                                                           value="<?php echo esc_attr($svgIconParams['icon-active']); ?>"/>
                                                </div>
                                            </div>
                                            <div class="wpfd-card-body">
                                                <div class="wpfd-flex">
                                                    <div class="wpfd-col-flex flex-half">
                                                        <?php wpfdRenderColor('icon-color', $svgIconParams['icon-color'], 'Icon color'); ?>
                                                        <div class="wpfd-col-flex icon-size" style="display: none">
                                                            <?php wpfdRenderSlider('icon-size', esc_html__('Icon size', 'wpfd'), 'px', esc_attr($svgIconParams['icon-size']), 0, 400); ?>
                                                        </div>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half">
                                                        <?php wpfdRenderIconBox($svgIconParams['icon']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- .Icon -->

                                        <!-- Extension name -->
                                        <div class="wpfd-card wpfd-collapse" data-close-group="icon,frame_settings,wrapper" data-collapse-name="extension_name" style="display: none">
                                            <div class="wpfd-card-header">
                                                <span class="wpfd-flex wpfd-flex-column">
                                                    <span class="card-title"><?php esc_html_e('Extension name', 'wpfd'); ?></span>
                                                    <span class="card-description"><?php esc_html_e('Set the style of the extension name', 'wpfd'); ?></span>
                                                </span>
                                                <div class="ju-switch-button">
                                                    <?php esc_html_e('Visible', 'wpfd'); ?>
                                                    <label class="switch">
                                                        <?php $checked = intval($svgIconParams['extension-name-active']) === 1 ? 'checked="checked"' : ''; ?>
                                                        <input type="checkbox" name="ref_extension-name-active" <?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's OK ?>/>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <input type="hidden" name="extension-name-active" value="<?php echo esc_attr($svgIconParams['extension-name-active']); ?>"/>
                                                </div>
                                            </div>
                                            <div style="display:none" class="wpfd-card-body">
                                                <div class="wpfd-flex">
                                                    <div class="wpfd-col-flex flex-half">
                                                        <?php wpfdRenderText('icon-text', $svgIconParams['icon-text'], esc_html__('Extension', 'wpfd'), esc_html__('Extension', 'wpfd')); ?>
                                                        <div class="ju-option-group">
                                                            <label class="wpfd-label" for="font-family"><?php esc_html_e('Font family', 'wpfd'); ?></label>
                                                            <select data-element-id="font-family" name="font-family" class="ju-input full-width mb10">
                                                                <option value="arial" <?php $svgIconParams['font-family'] === 'arial' ? 'selected="selected"' : ''; ?>>Arial</option>
                                                                <option value="serif" <?php $svgIconParams['font-family'] === 'serif' ? 'selected="selected"' : ''; ?>>Serif</option>
                                                                <option value="sans-serif" <?php $svgIconParams['font-family'] === 'sans-serif' ? 'selected="selected"' : ''; ?>>Sans-serif</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half">
                                                        <?php wpfdRenderSlider('font-size', esc_html__('Font size', 'wpfd'), 'px', esc_attr($svgIconParams['font-size'])); ?>
                                                        <?php wpfdRenderColor('text-color', $svgIconParams['text-color'], 'Text color'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- .Extension name -->

                                        <!-- Frame setting -->
                                        <div class="wpfd-card wpfd-collapse"
                                             data-close-group="icon,extension_name,wrapper"
                                             data-collapse-name="frame_settings">
                                            <div class="wpfd-card-header">
                                                <span class="material-icons wpfd-collapse--icon">expand_more</span>
                                                <span class="wpfd-flex wpfd-flex-column">
                                                    <span class="card-title"><?php esc_html_e('Frame setting', 'wpfd'); ?></span>
                                                    <span class="card-description"><?php esc_html_e('This is a shape around the icon and extension name', 'wpfd'); ?></span>
                                                </span>
                                                <div class="ju-switch-button">
                                                    <?php esc_html_e('Visible', 'wpfd'); ?>
                                                    <label class="switch">
                                                        <?php $checked = intval($svgIconParams['frame-active']) === 1 ? 'checked="checked"' : ''; ?>
                                                        <input type="checkbox"
                                                               name="ref_frame-active" <?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's OK ?>
                                                        />
                                                        <span class="slider"></span>
                                                    </label>
                                                    <input type="hidden" name="frame-active"
                                                           value="<?php echo esc_attr($svgIconParams['frame-active']); ?>"/>
                                                </div>
                                            </div>
                                            <div class="wpfd-card-body">
                                                <div class="wpfd-flex">
                                                    <div class="wpfd-col-flex flex-full">
                                                        <ul class="wpfd-frame-list"
                                                            style="display: flex;flex: auto;flex-wrap: nowrap;">
                                                            <li data-id="0"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 0) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg width="100" height="100" viewBox="0 0 400 400"
                                                                     xmlns="http://www.w3.org/2000/svg">
                                                                    <text x="50" y="325" stroke-width="0"
                                                                          font-family="sans-serif" font-size="100"
                                                                          style="line-height:1.25" fill="currentColor">
                                                                        <tspan x="50" y="325">NONE</tspan>
                                                                    </text>
                                                                </svg>
                                                            </li>
                                                            <li data-id="1"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 1) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg width="100" height="100" viewBox="0 0 400 400"
                                                                     version="1.1" xmlns="http://www.w3.org/2000/svg">
                                                                    <g transform="translate(74 240)">
                                                                        <rect width="240" height="100" rx="20" fill="none"
                                                                              stroke="currentColor" stroke-miterlimit="0"
                                                                              stroke-width="5"/>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                            <li data-id="2"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 2) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg width="100" height="100" viewBox="0 0 400 400"
                                                                     version="1.1" xmlns="http://www.w3.org/2000/svg">
                                                                    <g transform="translate(74 44)">
                                                                        <rect width="240" height="300" rx="20"
                                                                              transform="translate(0.606)" fill="none"
                                                                              stroke="currentColor" stroke-miterlimit="10"
                                                                              stroke-width="5"></rect>
                                                                        <line x2="240" transform="translate(0 200)"
                                                                              fill="none" stroke="currentColor"
                                                                              stroke-linecap="butt" stroke-linejoin="round"
                                                                              stroke-width="5"></line>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                            <li data-id="3"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 3) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg width="100" height="100" version="1.1"
                                                                     viewBox="0 0 400 400"
                                                                     xmlns="http://www.w3.org/2000/svg">
                                                                    <g transform="translate(74 44)" fill="none"
                                                                       stroke="currentColor" stroke-width="5">
                                                                        <path d="m20,0a20,20 0 0 0 -20,20l0,260a20,20 0 0 0 20,20l200,0a20,20 0 0 0 20,-20l0,-220l-60,-60zm0,0m160,0l60,60l-60,0l0,-60z"
                                                                              data-path-raw="m20,0a20,20 0 0 0 -20,20l0,260a20,20 0 0 0 20,20l{frame-bottom-width},0a20,20 0 0 0 20,-20l0,-220l-60,-60zm0,0m{frame-top-width},0l60,60l-60,0l0,-60z"
                                                                              stroke-linecap="round"
                                                                              stroke-linejoin="round"/>
                                                                        <line x2="240" transform="translate(0 200)"
                                                                              fill="none" stroke="currentColor"
                                                                              stroke-linecap="butt" stroke-linejoin="round"
                                                                              stroke-width="5"></line>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                            <li data-id="4"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 4) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg width="100" height="100" viewBox="0 0 400 400"
                                                                     version="1.1" xmlns="http://www.w3.org/2000/svg">
                                                                    <g transform="translate(74 44)">
                                                                        <rect width="240" height="300" rx="0"
                                                                              transform="translate(0.606)" fill="none"
                                                                              stroke="currentColor" stroke-miterlimit="10"
                                                                              stroke-width="5"></rect>
                                                                        <line x2="240" transform="translate(0 200)"
                                                                              fill="none" stroke="currentColor"
                                                                              stroke-linecap="butt" stroke-linejoin="round"
                                                                              stroke-width="5"></line>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                            <li data-id="5"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 5) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="100"
                                                                     height="100" viewBox="0 0 400 400">
                                                                    <g fill="none" stroke-width="5" stroke-dasharray="60">
                                                                        <circle cx="200" cy="200" r="140" fill="none"
                                                                                stroke="currentColor"/>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                            <li data-id="6"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 6) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="100"
                                                                     height="100" viewBox="0 0 400 400">
                                                                    <g fill="none" stroke-width="5">
                                                                        <circle cx="200" cy="200" r="140" fill="none"
                                                                                stroke="currentColor"/>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                            <li data-id="7"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 7) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="100"
                                                                     height="100" viewBox="0 0 400 400">
                                                                    <g fill="none" stroke-width="5"
                                                                       transform="translate(60 240)">
                                                                        <rect x="0" y="0" width="280" height="80" rx="40"
                                                                              fill="none" stroke="currentColor"/>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                            <li data-id="8"
                                                                <?php echo ((intval($svgIconParams['svg-frame']) === 8) ? 'class="selected"' : ''); ?>
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="100"
                                                                     height="100" viewBox="0 0 400 400">
                                                                    <g transform="translate(60 240)">
                                                                        <line x2="280" fill="none" stroke="currentColor"
                                                                              stroke-width="5"/>
                                                                    </g>
                                                                </svg>
                                                            </li>
                                                        </ul>
                                                        <input type="hidden" name="svg-frame"
                                                               value="<?php echo esc_attr($svgIconParams['svg-frame']); ?>"/>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half">
                                                        <?php wpfdRenderColor('frame-color', $svgIconParams['frame-color'], esc_html__('Frame color', 'wpfd')); ?>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half" style="display: none">
                                                        <?php wpfdRenderSlider('frame-width', esc_html__('Size', 'wpfd'), 'px', esc_attr($svgIconParams['frame-width']), 0, 400); ?>
                                                        <?php wpfdRenderSlider('frame-stroke', esc_html__('Thickness', 'wpfd'), 'px', esc_attr($svgIconParams['frame-stroke'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- .Frame setting -->

                                        <!-- Wrapper -->
                                        <div class="wpfd-card wpfd-collapse" data-close-group="icon,extension_name,frame_settings" data-collapse-name="wrapper">
                                            <div class="wpfd-card-header">
                                                <span class="material-icons wpfd-collapse--icon">expand_more</span>
                                                <span class="wpfd-flex wpfd-flex-column">
                                                    <span class="card-title"><?php esc_html_e('Wrapper', 'wpfd'); ?></span>
                                                    <span class="card-description"><?php esc_html_e('Content of the icon', 'wpfd'); ?></span>
                                                </span>
                                                <div class="ju-switch-button">
                                                    <?php esc_html_e('Visible', 'wpfd'); ?>
                                                    <label class="switch">
                                                        <?php $checked = intval($svgIconParams['wrapper-active']) === 1 ? 'checked="checked"' : ''; ?>
                                                        <input type="checkbox" name="ref_wrapper-active" <?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's OK ?>/>
                                                        <span class="slider"></span>
                                                    </label>
                                                    <input type="hidden" name="wrapper-active" value="<?php echo esc_attr($svgIconParams['wrapper-active']); ?>"/>
                                                </div>
                                            </div>
                                            <div class="wpfd-card-body">
                                                <div class="wpfd-flex">
                                                    <div class="wpfd-col-flex flex-full" style="display: none">
                                                        <h3 class="wpfd-sub-title"><?php esc_html_e('Border', 'wpfd'); ?></h3>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half" style="display: none">
                                                        <?php wpfdRenderSlider('border-radius', esc_html__('Border radius', 'wpfd'), '%', esc_attr($svgIconParams['border-radius'])); ?>
                                                        <?php wpfdRenderColor('border-color', esc_attr($svgIconParams['border-color']), esc_html__('Border color', 'wpfd')); ?>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half" style="display: none">
                                                        <?php wpfdRenderSlider('border-size', esc_html__('Border size', 'wpfd'), 'px', esc_attr($svgIconParams['border-size']), 0, 30); ?>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-full">
                                                        <h3 class="wpfd-sub-title"><?php esc_html_e('Background', 'wpfd'); ?></h3>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half">
                                                        <?php wpfdRenderColor('background-color', esc_attr($svgIconParams['background-color']), esc_html__('Background color', 'wpfd')); ?>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-full" style="display: none">
                                                        <h3 class="wpfd-sub-title"><?php esc_html_e('Shadow', 'wpfd'); ?></h3>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half" style="display: none">
                                                        <?php wpfdRenderSlider('vertical-position', esc_html__('Vertical position', 'wpfd'), 'px', esc_attr($svgIconParams['vertical-position'])); ?>
                                                        <?php wpfdRenderSlider('blur-radius', esc_html__('Blur radius', 'wpfd'), 'px', esc_attr($svgIconParams['blur-radius']), 0, 20); ?>
                                                        <?php wpfdRenderColor('shadow-color', esc_attr($svgIconParams['blur-radius']), esc_html__('Shadow color', 'wpfd')); ?>
                                                    </div>
                                                    <div class="wpfd-col-flex flex-half" style="display: none">
                                                        <?php wpfdRenderSlider('horizontal-position', esc_html__('Horizontal position', 'wpfd'), 'px', esc_attr($svgIconParams['horizontal-position'])); ?>
                                                        <?php wpfdRenderSlider('spread-radius', esc_html__('Spread radius', 'wpfd'), 'px', esc_attr($svgIconParams['spread-radius'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- .Wrapper -->
                                    </div>
                                </div>
                            </div>
                            <div id="png-icons-editor" class="png-icons-editor" style="display: none;">
                                <table data-icon-set="png" data-search-ref="png-table" class="wpfd-icons-table">
                                    <thead>
                                        <tr>
                                            <th class="wpfd-extension"><?php esc_html_e('TYPE', 'wpfd'); ?></th>
                                            <th class="wpfd-default-icon"><?php esc_html_e('ICON', 'wpfd'); ?></th>
                                            <th class="wpfd-uploaded-icon"><?php esc_html_e('OVERIDE ICON', 'wpfd'); ?></th>
                                            <th class="wpfd-icon-size" style="display: none"><?php esc_html_e('SIZE', 'wpfd'); ?></th>
                                            <th class="wpfd-actions"><?php esc_html_e('ACTION', 'wpfd'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (is_array($this->extensions) && isset($this->extensions['png']) && count($this->extensions['png'])) :
                                        foreach ($this->extensions['png'] as $extension => $icon) : ?>
                                            <?php if ($icon) : ?>
                                                <tr data-extension="<?php echo esc_attr($extension); ?>" style="display: none;">
                                                    <td class="wpfd-extension">.<?php echo esc_attr($extension); ?></td>
                                                    <td class="wpfd-default-icon">
                                                        <?php if (isset($icon['default']) && $icon['default'] !== '') : ?>
                                                            <img width="70" src="<?php echo esc_url($icon['default']); ?>" />
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="wpfd-uploaded-icon">
                                                        <?php if (isset($icon['uploaded']) && $icon['uploaded'] !== '') : ?>
                                                            <img width="70" src="<?php echo esc_url($icon['uploaded']); ?>" />
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="wpfd-icon-size" style="display: none">N/A</td>
                                                    <td class="wpfd-actions">
                                                        <button data-action="upload" data-icon-set="png" data-extension="<?php echo esc_attr($extension); ?>" class="js-PngUpload-trigger ju-button ju-button-sm gray-outline-button">
                                                            <?php esc_html_e('Upload Icon', 'wpfd'); ?>
                                                        </button>
                                                        <?php
                                                        $style = '';
                                                        if (isset($icon['uploaded']) && $icon['uploaded'] === '') :
                                                            $style = ' style="display:none"';
                                                        endif; ?>
                                                        <button<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's OK ?> data-action="delete" data-icon-set="png" data-extension="<?php echo esc_attr($extension); ?>" class="js-PngDelete-trigger ju-button ju-button-sm gray-outline-button">
                                                            <?php esc_html_e('Delete overide', 'wpfd'); ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach;
                                    endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wpfd-modal-footer">
            <button type="button" class="ju-button ju-rect-button ju-link-button js-modalCancel">
                <?php esc_html_e('Cancel', 'wpfd') ?>
            </button>
            <button type="button" class="ju-button ju-rect-button ju-v3-material js-saveFileChangeIcons">
                <?php esc_html_e('Save', 'wpfd'); ?>
            </button>
        </div>
    </div>
</div>
<div id="wpfd_file_set_tags" style="position: absolute;top:0;left:0;right:0;bottom:0;display:none;">
    <div class="wpfd-modal-backdrop fade in"></div>
    <div class="wpfd-modal">
        <div class="wpfd-modal-set-tags-loading" style="display: none; position: relative;">
            <div class="dploading" style="top: 20px; min-width: 80px; min-height: 30px; padding: 20px; box-sizing: border-box;"></div>
        </div>
        <div class="wpfd-modal-header" style="padding: 30px 30px 15px 30px;">
            <ul class="wpfd-tabs" style="vertical-align: middle; margin: 0; padding: 0">
                <li class="wpfd-tab ju-button ju-rect-button ju-link-button active" style="font-size: 20px; margin: 0; line-height: 1; text-align: left; border: unset;" data-tab-id="wpfd-file-change-icons">
                    <?php esc_html_e('Add Tags:', 'wpfd'); ?>
                </li>
            </ul>
        </div>
        <div class="wpfd-modal-body" style="padding: 0 30px">
            <div class="wpfd-tabs-wrapper">
                <div class="wpfd-tabs-content" style="padding: 0">
                    <div class="wpfd-tab-content active" id="wpfd-file-change-icons">
                        <div class="wpfd-flex" style="width: 100%; flex-direction: column;">
                            <div id="wpfd_set_tag_for_files" class="wpfd_set_tag_for_files wpfd-tags">
                                <div id="wpfd_set_tag_selected_files" style="display: block; width: 100%; margin: 10px 0; font-weight: bold;">
                                    <span class="wpfd-lable" style="font-size: 16px; line-height: 1.2;"><?php esc_html_e('For selected file(s): ', 'wpfd'); ?></span>
                                    <span class="selected-list" style="font-style: italic; line-height: 1.2; font-size: 14px;"></span>
                                </div>
                                <div class="span12 tags-filtering">
                                    <input type="text" name="ftags" class="tagit input_tags" value="" style="display: none" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wpfd-modal-footer" style="padding: 15px 30px">
            <button type="button" class="ju-button ju-rect-button ju-link-button js-modalCancel">
                <?php esc_html_e('Cancel', 'wpfd') ?>
            </button>
            <button type="button" class="ju-button ju-rect-button ju-v3-material js-saveTags">
                <?php esc_html_e('Save', 'wpfd'); ?>
            </button>
        </div>
    </div>
</div>
<?php
/**
 * Content Item
 *
 * @param object $category Category
 *
 * @return string
 */
function itemContent($category)
{
    if (isset($category->disable) && $category->disable) {
        $disable   = ' disabled ';
        $dd_handle = '';
    } else {
        $disable   = '';
        $dd_handle = ' dd-handle ';
    }
    $item = '<div class="' . $disable . $dd_handle . ' dd3-handle">
                <i class="material-icons wpfd-folder">folder</i>
             </div>
             <div class="dd-content dd3-content"
             <i class="icon-chevron-right"></i>';
    if (wpfd_can_edit_category() || wpfd_can_edit_own_category()) {
        $item .= '<a class="edit"><i class="icon-edit"></i></a>';
    }
    $item .= '<a href="" class="t"> <span class="title">' . esc_html($category->name) . '</span> </a>
            </div>';

    return $item;
}
?>
