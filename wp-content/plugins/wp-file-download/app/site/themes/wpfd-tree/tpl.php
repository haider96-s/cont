<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0.3
 */

//-- No direct access
defined('ABSPATH') || die();
/**
 * Filter allow to change the file download link when disable download popup
 *
 * @param boolean
 *
 * @ignore Hook already documented
 */
$isPreviewLink = apply_filters('wpfd_file_replace_download_with_preview', false);
$target        = (isset($config['use_google_viewer']) && $config['use_google_viewer'] === 'tab' && $isPreviewLink) ? '_blank' : '';
?>
<script type="text/x-handlebars-template" id="wpfd-template-tree-box">
    {{#with file}}
    <div class="dropblock">
        <a href="javascript:void(0)" class="wpfd-close"></a>
        <div class="filecontent">
            <?php
            /**
             * Action to show file content in handlebars template
             *
             * @param array Main config
             * @param array Category config
             *
             * @hookname wpfd_{$themeName}_file_content_handlebars
             *
             * @hooked: showIconHandlebars - 10
             * @hooked: showTitleHandlebars - 20
             *
             * @ignore Hook already documented
             */
            do_action('wpfd_' . $name . '_file_content_handlebars', $config, $params);
            ?>

            <div class="wpfd-extra">
                <?php
                /**
                 * Action to show file info in handlebars template
                 *
                 * @param array Main config
                 * @param array Category config
                 *
                 * @hookname wpfd_{$themeName}_file_info_handlebars
                 *
                 * @hooked showDescriptionHandlebars - 10
                 * @hooked showVersionHandlebars - 20
                 * @hooked showSizeHandlebars - 30
                 * @hooked showHitsHandlebars - 40
                 * @hooked showCreatedHandlebars - 50
                 * @hooked showModifiedHandlebars - 60
                 *
                 * @ignore Hook already documented
                 */
                do_action('wpfd_' . $name . '_file_info_handlebars', $config, $params);
                ?>
            </div>
        </div>
        <?php
        /**
         * Action to show buttons in handlebars template
         *
         * @param array Main config
         * @param array Category config
         *
         * @hookname wpfd_{$themeName}_buttons_handlebars
         *
         * @hooked showDownloadHandlebars - 10
         * @hooked showPreviewHandlebars - 20
         *
         * @ignore Hook already documented
         */
        do_action('wpfd_' . $name . '_buttons_handlebars', $config, $params);
        ?>
    {{/with}}
</script>

<?php if ((int) WpfdBase::loadValue($params, 'tree_showsubcategories', 1) === 1) : ?>
    <script type="text/x-handlebars-template" id="wpfd-template-tree-categories">
        {{#if categories}}
        {{#each categories}}
        <li class="directory collapsed">
            <a class="catlink" href="#" data-idcat="{{termID}}">
                <div class="icon-open-close" data-id="{{termID}}"></div>
                <i class="zmdi zmdi-folder wpfd-folder" style="color: {{color}}"></i>
                <span>{{name}}</span>
            </a>
        </li>
        {{/each}}
        {{/if}}
    </script>
<?php endif; ?>

<script type="text/x-handlebars-template" id="wpfd-template-tree-files">
    {{#if files}}
    {{#each files}}
    <li class="ext {{ext}}">
        <?php
        if ((int) $config['download_selected'] === 1 && wpfd_can_download_files()) {
            echo '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="{{ID}}" /><span></span></label>';
        }
        $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
        if ($this->config['custom_icon']) : ?>
            {{#if file_custom_icon}}
            <span class="wpfd-file ext icon-custom"><img src="{{file_custom_icon}}"></span>
            {{else}}
            <i class="wpfd-file ext ext-{{ext}}<?php echo esc_attr($iconSet); ?>"></i>
            {{/if}}
        <?php else : ?>
            <i class="wpfd-file ext ext-{{ext}}<?php echo esc_attr($iconSet); ?>"></i>
        <?php endif; ?>

        <?php $atthref = '#'; ?>
        <?php if ((int) WpfdBase::loadValue($params, 'tree_download_popup', 1) === 0) { ?>
        {{#if openpdflink}}
            <?php $link1 = $isPreviewLink ? '{{openpdflink}}' : '{{linkdownload}}'; ?>
            <a class="wpfd-file-link" data-category_id="{{catid}}" target="<?php echo esc_attr($target); ?>" href="<?php
            echo esc_html($link1); ?>" data-id="{{ID}}"
               title="{{post_title}}">{{{crop_title}}}</a>
        {{else}}
            <?php $link2 = $isPreviewLink ? '{{viewerlink}}' : '{{linkdownload}}'; ?>
            <a class="wpfd-file-link" data-category_id="{{catid}}" target="<?php echo esc_attr($target); ?>" href="<?php
            echo esc_html($link2); ?>" data-id="{{ID}}"
               title="{{post_title}}">{{{crop_title}}}</a>
        {{/if}}
        <?php } else { ?>
            <a class="wpfd-file-link" data-category_id="{{catid}}" href="<?php
            echo esc_html($atthref); ?>" data-id="{{ID}}"
               title="{{post_title}}">{{{crop_title}}}</a>
        <?php } ?>
    </li>
    {{/each}}
    </div>
    {{/if}}
</script>
<?php
/**
 * Action before theme content
 *
 * @param object Current theme params
 * @param array  Category config
 *
 * @hookname wpfd_{$themeName}_before_theme_content
 *
 * @hooked outputContentWrapper - 10 (outputs opening divs for the content)
 * @hooked outputContentHeader - 20 (breadcrumbs and category name)
 *
 * @ignore Hook already documented
 */
do_action('wpfd_' . $name . '_before_theme_content', $this, $params);

/**
 * Action before files loop
 *
 * @param object Current theme params
 * @param array  Category config
 *
 * @hookname wpfd_{$themeName}_before_files_loop
 *
 * @hooked outputCategoriesWrapper - 10 (outputs opening divs for the categories)
 * @hooked showCategoryTitle - 20
 * @hooked showCategories - 30
 * @hooked outputCategoriesWrapperEnd - 90 (outputs closing divs for the categories)
 *
 * @ignore
 */
do_action('wpfd_' . $name . '_before_files_loop', $this, $params);
?>
<ul class="wpfd-tree-categories-files">
    <?php if (count($categories) && (int) WpfdBase::loadValue($params, $name . '_showsubcategories', 1) === 1) : ?>
        <?php if ((int) WpfdBase::loadValue($params, $name . '_expanded_subcategories', 0) === 0) : ?>
            <?php foreach ($categories as $category) : ?>
                <?php $color = intval($category->term_id) !== 0 ? get_term_meta($category->term_id, '_wpfd_color', true) : ''; ?>
                <li class="directory collapsed">
                    <a class="catlink" href="#" data-idcat="<?php echo esc_attr($category->term_id); ?>">
                        <div class="icon-open-close" data-id="<?php echo esc_attr($category->term_id); ?>"></div>
                        <i class="zmdi zmdi-folder wpfd-folder" style="color: <?php echo $color; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Render color only ?>"></i>
                        <span><?php echo esc_html($category->name); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else :
            if ((int) $category->term_id === 0) :
                foreach ($categories as $category) : ?>
                    <li class="directory expanded">
                        <a class="catlink" href="#" data-idcat="<?php echo esc_attr($category->term_id); ?>">
                            <div class="icon-open-close" data-id="<?php echo esc_attr($category->term_id); ?>"></div>
                            <i class="zmdi zmdi-folder wpfd-folder"></i>
                            <span><?php echo esc_html($category->name); ?></span>
                        </a>
                        <ul>
                            <?php echo $this->wpfdBuildTree($config, $params, $name, $categories_tree, $category->term_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text form only ?>    
                        </ul>
                    </li>
                <?php endforeach;
            else :
                echo $this->wpfdBuildTree($config, $params, $name, $categories_tree, $category->term_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text form only
            endif;
        endif; ?>
    <?php endif; ?>
    <?php if (is_array($files) && count($files)) :
        $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
        foreach ($files as $file) : ?>
            <?php  if (wpfdPasswordRequired($file, 'file')) : ?>
                <?php  $this->wpfdTreeDisplayFilePasswordProtectionForm($file, ''); ?>
            <?php  else : ?>
            <li class="ext <?php echo esc_attr(strtolower($file->ext)); ?>">
                <?php
                if ((int) $config['download_selected'] === 1 && wpfd_can_download_files() && is_numeric($file->ID)) {
                    echo '<label class="wpfd_checkbox"><input class="cbox_file_download" type="checkbox" data-id="' . esc_attr($file->ID) . '" data-catid="' . esc_attr($file->catid) . '" /><span></span></label>';
                }
                if ($this->config['custom_icon'] && $file->file_custom_icon) : ?>
                    <i class="wpfd-file"><img src="<?php echo esc_url($file->file_custom_icon); ?>"></i>
                <?php else : ?>
                    <i class="wpfd-file ext ext-<?php echo esc_attr(strtolower($file->ext)) . esc_attr($iconSet); ?>"></i>
                <?php endif; ?>
                <a class="wpfd-file-link" href="<?php $atthref = '#';
                if ((int) WpfdBase::loadValue($params, $name . '_download_popup', 1) === 0) {
                    $viewerlink = isset($file->viewerlink) ? $file->viewerlink : '';
                    $filePreviewLink = isset($file->openpdflink) ? $file->openpdflink : $viewerlink;
                    $atthref = $isPreviewLink ? $filePreviewLink : $file->linkdownload;
                }
                echo esc_url($atthref); ?>" data-category_id="<?php echo esc_attr($file->catid); ?>"
                   data-id="<?php echo esc_attr($file->ID); ?>"
                   title="<?php echo esc_attr($file->post_title); ?>" target="<?php echo esc_attr($target); ?>"><?php echo esc_html($file->crop_title); ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>

<?php if (wpfd_can_edit_category() || wpfd_can_edit_own_category() || wpfd_can_upload_files()) : ?>
    <?php
    /**
     * Filter to change the upload form
     *
     * @param boolean
     *
     * @ignore
     */
    $reverseUploadForm = apply_filters('wpfd_show_upload_form_reverse', false);
    $cate              = $this->category;
    $showUploadForm    = wpfdShowUploadForm($cate, $name);

    // Upload form below file list
    if ($reverseUploadForm) {
        if ($showUploadForm) {
            $uploadStyle = 'display: none; margin: 20px 10px;';
        } else {
            $uploadStyle = 'display: block; margin: 20px 10px;';
        }
    } else {
        $uploadStyle = $showUploadForm ? 'display: block; margin: 20px 10px;' : 'display: none; margin: 20px 10px;';
    }
    ?>
    <div class="wpfd-upload-form" style="<?php echo esc_attr($uploadStyle); ?>">
        <?php echo do_shortcode('[wpfd_upload category_id="' . $cate->term_id . '"]'); ?>
    </div>
<?php endif; ?>
<?php
/**
 * Action before theme content
 *
 * @param object Current theme params
 *
 * @hookname wpfd_{$themeName}_before_theme_content
 *
 * @hooked outputContentWrapperEnd - 10 (outputs closing divs for the content)
 *
 * @ignore Hook already documented
 */
do_action('wpfd_' . $name . '_after_theme_content', $this, $params);
?>
