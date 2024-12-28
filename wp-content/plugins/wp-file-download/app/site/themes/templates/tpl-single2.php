<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

//-- No direct access
defined('ABSPATH') || die();
$download_attributes = apply_filters('wpfd_download_data_attributes_handlebars', '');
$add_font_family_icon = apply_filters('wpfd_download_single_file_add_font_family_icon', false);
$config = get_option('_wpfd_global_config');
$fileIconSet = (isset($config['icon_set']) && $config['icon_set'] !== 'default') ? ' wpfd-icon-set-' . esc_attr($config['icon_set']) : '';
$robots_meta_nofollow = isset($config['robots_meta_nofollow']) ? (int) $config['robots_meta_nofollow'] : 0;
$rel = '';
if (intval($robots_meta_nofollow) === 1) {
    $rel = ' rel="nofollow" ';
}
?>
<div class="wpfd-single-file {{file.ext}}" data-id="{{file.ID}}" data-catid="{{file.catid}}">
    <input type="hidden" class="wpfd_file_preview_link_download" value="{{file.linkdownload}}" data-filetitle="{{file.post_title}}" data-fileicons="ext ext-{{file.ext}}<?php echo esc_attr($fileIconSet); ?>" />
    <input type="hidden" class="wpfd_file_ext" value="{{file.ext}}" />
    {{#if settings.icon}}
    <div class="wpfd-single-file--icon">
        {{#xunless settings.link_on_icon 'none'}}
            <a href="{{#xif settings.link_on_icon 'preview'}}{{#if file.openpdflink}}{{file.openpdflink}}{{else}}{{file.viewerlink}}{{/if}}{{/xif}}{{#xif settings.link_on_icon 'download'}}{{file.linkdownload}}{{/xif}}" alt="{{file.crop_title}}" class="{{#xif settings.link_on_icon 'preview'}}wpfdlightbox{{else}}noLightbox{{/xif}}" data-file-type="{{file.ext}}" data-id="{{file.ID}}" data-catid="{{file.catid}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?>>
        {{/xunless}}
            <div class="wpfd-icon-placeholder" style="{{file.icon_style}}"></div>
        {{#xunless settings.link_on_icon 'none'}}</a>{{/xunless}}
    </div>
    {{/if}}

    <div class="wpfd-single-file--details wpfd-file-content">
        {{#if settings.file_title}}
            {{#if file.crop_title}}
                <?php if (wpfd_can_download_files()) : ?>
                    {{#if file.linktitle}}
                        <{{settings.title_wrapper_tag}} class="wpfd-file-content--title"><a href="{{file.linktitle}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> style="text-decoration: none">{{{file.crop_title}}}</a></{{settings.title_wrapper_tag}}>
                    {{else}}
                        <{{settings.title_wrapper_tag}} class="wpfd-file-content--title"><a href="{{file.linkdownload}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> style="text-decoration: none">{{{file.crop_title}}}</a></{{settings.title_wrapper_tag}}>
                    {{/if}}
                <?php else : ?>
                    <{{settings.title_wrapper_tag}} class="wpfd-file-content--title">{{{file.crop_title}}}</{{settings.title_wrapper_tag}}>
                <?php endif; ?>
            {{/if}}
        {{/if}}
        {{#if settings.file_description}}
            {{#if file.description}}
                <div class="wpfd-file-content--description">
                    {{{file.description}}}
                </div>
            {{/if}}
        {{/if}}
        {{#if settings.file_information}}
        <div class="wpfd-file-content--meta">
            {{#if settings.file_size}}
                {{#if file.size}}
                    <div><?php esc_html_e('File size', 'wpfd'); ?>: {{file.size}}</div>
                {{/if}}
            {{/if}}
            {{#if settings.file_created_date}}
                {{#if file.created}}
                    <div><?php esc_html_e('Created', 'wpfd'); ?>: {{file.created}}</div>
                {{/if}}
            {{/if}}
            {{#if settings.file_update_date}}
                {{#if file.modified}}
                    <div><?php esc_html_e('Updated', 'wpfd'); ?>: {{file.modified}}</div>
                {{/if}}
            {{/if}}
            {{#if settings.file_download_hit}}
                {{#if file.hits}}
                    <div><?php esc_html_e('Hits', 'wpfd'); ?>: {{file.hits}}</div>
                {{/if}}
            {{/if}}
            {{#if settings.file_version}}
                {{#if file.version}}
                    <div><?php esc_html_e('Version', 'wpfd'); ?>: {{file.version}}</div>
                {{/if}}
            {{/if}}
        </div>
        {{/if}}
    </div>
    <div class="wpfd-single-file--buttons">
        {{#if settings.download_button}}
            {{#if file.show_add_to_cart}}
                <a class="wpfd_single_add_to_cart wpfd-single-file-button wpfd-button-download wpfd_downloadlink" href="{{file.linkdownload}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?>{{#if file.product_id}} data-product_id="{{file.product_id}}"{{/if}}>
                    <i style="font-size: {{settings.download_icon_size}}px;" class="zmdi zmdi-shopping-cart-plus wpfd-add-to-cart"></i>
                    <span><?php esc_html_e('Add to cart', 'wpfd'); ?></span>
                </a>
            {{else}}
                <?php if (wpfd_can_download_files()) : ?>
                <a href="{{file.linkdownload}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> <?php echo $download_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> data-id="{{file.ID}}" title="{{#if file.description}}{{file.description}}{{else}}{{file.title}}{{/if}}" class="noLightbox wpfd_downloadlink wpfd-single-file-button wpfd-button-download">
                    {{#if settings.download_icon_active}}
                    {{#xif settings.download_icon_position 'left'}}
                    {{{svgicon settings.download_icon settings.download_icon_color settings.download_icon_size}}}
                    {{/xif}}
                    <?php if ($add_font_family_icon) : ?>
                    {{else}}
                    {{#xif settings.download_icon_position 'left'}}
                    <i style="font-size: {{settings.download_icon_size}}px;" class="zmdi zmdi-cloud-download wpfd-download"></i>
                    {{/xif}}
                    <?php endif; ?>
                    {{/if}}
                    <span><?php esc_html_e('Download', 'wpfd'); ?></span>
                    {{#if settings.download_icon_active}}
                    {{#xif settings.download_icon_position 'right'}}
                    {{{svgicon settings.download_icon settings.download_icon_color settings.download_icon_size}}}
                    {{/xif}}
                    <?php if ($add_font_family_icon) : ?>
                    {{else}}
                    {{#xif settings.download_icon_position 'right'}}
                    <i style="font-size: {{settings.download_icon_size}}px;" class="zmdi zmdi-cloud-download wpfd-download"></i>
                    {{/xif}}
                    <?php endif; ?>
                    {{/if}}
                </a>
                <?php endif; ?>
            {{/if}}
        {{/if}}
        {{#if settings.preview_button}}
            {{#if file.show_add_to_cart}}
                <a class="wpfd-single-file-button wpfd-button-preview wpfd_single_view_product" href="{{file.viewerlink}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> target="_blank">
                    {{#if settings.preview_icon_active}}
                    {{#xif settings.preview_icon_position 'left'}}
                    {{{svgicon settings.preview_icon settings.preview_icon_color settings.preview_icon_size}}}
                    {{/xif}}
                    {{/if}}
                    <span><?php esc_html_e('View product', 'wpfd'); ?></span>
                    {{#if settings.preview_icon_active}}
                    {{#xif settings.preview_icon_position 'right'}}
                    {{{svgicon settings.preview_icon settings.preview_icon_color settings.preview_icon_size}}}
                    {{/xif}}
                    {{/if}}
                </a>
            {{else}}
                <?php if (wpfd_can_preview_files()) : ?>
                {{#if file.openpdflink}}
                    <a href="{{file.openpdflink}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> class="wpfd-single-file-button wpfd-button-preview openlink{{#if file.open_in_lightbox}} wpfdlightbox{{/if}}"{{#if file.open_in_newtab}} target="_blank"{{/if}}>
                        {{#if settings.preview_icon_active}}
                        {{#xif settings.preview_icon_position 'left'}}
                        {{{svgicon settings.preview_icon settings.preview_icon_color settings.preview_icon_size}}}
                        {{/xif}}
                        <?php if ($add_font_family_icon) : ?>
                        {{else}}
                        {{#xif settings.preview_icon_position 'left'}}
                        <i style="font-size: {{settings.download_icon_size}}px;" class="zmdi zmdi-filter-center-focus wpfd-preview"></i>
                        {{/xif}}
                        <?php endif; ?>
                        {{/if}}
                        <span><?php esc_html_e('Preview', 'wpfd'); ?></span>
                        {{#if settings.preview_icon_active}}
                        {{#xif settings.preview_icon_position 'right'}}
                        {{{svgicon settings.preview_icon settings.preview_icon_color settings.preview_icon_size}}}
                        {{/xif}}
                        <?php if ($add_font_family_icon) : ?>
                        {{else}}
                        {{#xif settings.preview_icon_position 'right'}}
                        <i style="font-size: {{settings.download_icon_size}}px;" class="zmdi zmdi-filter-center-focus wpfd-preview"></i>
                        {{/xif}}
                        <?php endif; ?>
                        {{/if}}
                    </a>
                {{else}}
                    {{#if file.viewerlink}}
                        <a href="{{file.viewerlink}}" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> class="wpfd-single-file-button wpfd-button-preview wpfd_previewlink{{#if file.open_in_lightbox}} wpfdlightbox{{/if}}"{{#if file.open_in_newtab}} target="_blank"{{/if}}
                            data-id="{{file.ID}}" data-catid="{{file.catid}}"
                            data-file-type="{{file.ext}}">
                            {{#if settings.preview_icon_active}}
                            {{#xif settings.preview_icon_position 'left'}}
                            {{{svgicon settings.preview_icon settings.preview_icon_color settings.preview_icon_size}}}
                            {{/xif}}
                            <?php if ($add_font_family_icon) : ?>
                            {{else}}
                            {{#xif settings.preview_icon_position 'left'}}
                            <i style="font-size: {{settings.download_icon_size}}px;" class="zmdi zmdi-filter-center-focus wpfd-preview"></i>
                            {{/xif}}
                            <?php endif; ?>
                            {{/if}}
                            <span><?php esc_html_e('Preview', 'wpfd'); ?></span>
                            {{#if settings.preview_icon_active}}
                            {{#xif settings.preview_icon_position 'right'}}
                            {{{svgicon settings.preview_icon settings.preview_icon_color settings.preview_icon_size}}}
                            {{/xif}}
                            <?php if ($add_font_family_icon) : ?>
                            {{else}}
                            {{#xif settings.preview_icon_position 'right'}}
                            <i style="font-size: {{settings.download_icon_size}}px;" class="zmdi zmdi-filter-center-focus wpfd-preview"></i>
                            {{/xif}}
                            <?php endif; ?>
                            {{/if}}
                        </a>
                    {{/if}}
                {{/if}}
                <?php endif; ?>
            {{/if}}
        {{/if}}
    </div>
</div>
