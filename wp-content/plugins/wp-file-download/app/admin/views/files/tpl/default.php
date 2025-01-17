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

$items_thead = array(
    'ext'           => esc_html__('Ext', 'wpfd'),
    'title'         => esc_html__('Title', 'wpfd'),
    'size'          => esc_html__('File size', 'wpfd'),
    'created_time'  => esc_html__('Date added', 'wpfd'),
    'modified_time' => esc_html__('Date modified', 'wpfd'),
    'version'       => esc_html__('Version', 'wpfd'),
    'hits'          => esc_html__('Hits', 'wpfd')
);
?>
<?php if ($this->files) : ?>
    <table class="restable">
        <thead>
        <tr>
            <?php
            foreach ($items_thead as $thead_key => $thead_text) {
                $icon = '';
                if ($thead_key === $this->ordering) {
                    $icon = '<span 
                    class="dashicons dashicons-arrow-' . ($this->orderingdir === 'asc' ? 'up' : 'down') . '"></span>';
                }
                ?>
                <th data-key="<?php echo esc_attr($thead_key); ?>" class="<?php echo esc_attr($thead_key); ?>">
                    <?php if ($thead_key === 'actions') { ?>
                        <?php echo esc_html($thead_text); ?>
                    <?php } else { ?>
                        <a href="#" class="<?php echo($this->ordering === $thead_key ? 'currentOrderingCol' : ''); ?>"
                           data-ordering="<?php echo esc_attr($thead_key); ?>"
                           data-direction="<?php echo esc_attr($this->orderingdir); ?>">
                            <?php echo esc_html($thead_text); ?><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nothing need to escape ?>
                        </a>
                    <?php } ?>
                </th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php if (is_array($this->files) || is_object($this->files)) : ?>
            <?php foreach ($this->files as $file) :
                $httpcheck = isset($file->guid) ? $file->guid : '';
                $classes = preg_match('(http://|https://)', $httpcheck) ? ' is-remote-url' : '';
                /**
                 * Check if file has linked to a product
                 *
                 * @param WP_Post
                 *
                 * @internal
                 */
                $classes .= apply_filters('wpfd_addon_has_products', false, $file) ? ' isWoocommerce' : '';

                $isExpired = WpfdHelperFile::wpfdIsExpired($file->id);
                if ($isExpired === true) {
                    $classes .= ' is-expired ';
                } elseif ($isExpired > 0) {
                    $classes .= ' is-expiry-set ';
                }

                $classes .= apply_filters('wpfd_file_enable_state', $file) ? ' unpublished' : '';

                $classes .= apply_filters('wpfd_file_upload_pending', $file->id, $file->catid) ? ' isPending' : '';

                $data = array(
                    'id-file'      => esc_attr($file->id),
                    'catid-file'   => esc_attr($file->catid),
                    'linkdownload' => esc_url($file->linkdownload),
                );
                /**
                 * Init data for file row
                 *
                 * @param array
                 * @param WP_Post
                 *
                 * @internal
                 */
                $data = apply_filters('wpfd_admin_file_row_data', $data, $file);

                $dataHtml = '';
                foreach ($data as $key => $dataRow) {
                    $dataHtml .= 'data-' . esc_attr($key) . '="' . $dataRow . '" ';
                }
                $dataHtml = rtrim($dataHtml);
                ?>
                <tr class="file<?php echo esc_attr($classes); ?>" <?php echo $dataHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above
                ?>>
                    <td class="fileicon">
                        <div class="fileicon-wrapper">
                            <div class="<?php echo ($this->iconSet !== 'default') ? 'wpfd-icon-set-' . esc_attr($this->iconSet) : ''; ?> ext ext-<?php echo esc_attr($file->ext); ?>">
                                <span class="txt"><?php echo esc_html($file->ext); ?></span></div>
                            <div class="filestatus-wrapper">
                                <?php if (strpos($classes, 'isWoocommerce') !== false) : ?>
                                    <i title="<?php esc_html_e('WooCommerce', 'wpfd'); ?>"
                                       class="wpfd-svg-icon-woocommerce"></i>
                                <?php endif; ?>

                                <?php if (strpos($classes, 'unpublished') !== false) : ?>
                                    <i title="<?php esc_html_e('Unpublished', 'wpfd'); ?>"
                                       class="wpfd-svg-icon-visibility-off"></i>
                                <?php endif; ?>

                                <?php if (strpos($classes, 'isPending') !== false) : ?>
                                <?php endif; ?>

                                <?php if (strpos($classes, 'is-remote-url') !== false) : ?>
                                    <i title="<?php esc_html_e('Remote URL', 'wpfd'); ?>"
                                       class="wpfd-svg-icon-link"></i>
                                <?php endif; ?>

                                <?php if (strpos($classes, 'is-expired') !== false) : ?>
                                    <i title="<?php esc_html_e('Expired & Unpublished', 'wpfd'); ?>"
                                       class="wpfd-svg-icon-expired"></i>
                                <?php endif; ?>
                                <?php if (strpos($classes, 'is-expiry-set') !== false) : ?>
                                    <i title="<?php esc_html_e('Expired date set', 'wpfd'); ?>"
                                       class="wpfd-svg-icon-expiry-set"></i>
                                <?php endif; ?>

                                <?php if ($this->category->term_id !== $file->catid && !$this->is_search) : ?>
                                    <i onclick="Wpfd.go(<?php echo esc_html($file->catid . ', ' . $file->id); ?>);"
                                       title="<?php esc_html_e('This file belongs to other categori(es). Click here to edit the original file in the category source.', 'wpfd'); ?>"
                                       class="wpfd-svg-icon-multiple-categories"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="title"><?php echo esc_html($file->post_title); ?></td>
                    <td class="size">
                        <?php echo esc_html((strtolower($file->size) === 'n/a' || $file->size <= 0) ? 'N/A' : WpfdHelperFiles::bytesToSize($file->size)); ?>
                    </td>
                    <td class="created">
                        <?php echo esc_html($file->created); ?>
                    </td>
                    <td class="modified">
                        <?php echo esc_html($file->modified); ?>
                    </td>
                    <td class="version"><?php echo esc_html((isset($file->versionNumber)) ? $file->versionNumber : ''); ?></td>
                    <td class="hits"><?php echo esc_html($file->hits) . ' ' . esc_html__('hits', 'wpfd'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
