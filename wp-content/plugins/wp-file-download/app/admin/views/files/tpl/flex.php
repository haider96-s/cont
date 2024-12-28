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
    <?php
    $theLastPage  = false;
    $loadFileOnly = false;
    $isLimited    = false;
    $total        = 0;
    if ($this->fileListPagination) {
        // Admin pagination
        if (count($this->files) > $this->filesperpage) {
            $isLimited = true;
        }
        $total  = (is_array($this->files)) ? ceil(count($this->files) / $this->filesperpage) : 0;
        $offset = ($this->page - 1) * $this->filesperpage;
        $this->files = array_slice($this->files, $offset, $this->filesperpage);
    } elseif ($this->loadMore) {
        // Admin load more
        if (count($this->files) > $this->loadMorePerPage) {
            $isLimited = true;
        }
        $total  = (is_array($this->files)) ? ceil(count($this->files) / $this->loadMorePerPage) : 0;
        $length = $this->page * $this->loadMorePerPage;
        $offset = ($this->page - 1) * $this->loadMorePerPage;
        if ($length >= count($this->files)) {
            $length = count($this->files);
            $theLastPage = true;
        }
        $this->files = array_slice($this->files, $offset, $this->loadMorePerPage);

        if ($this->page > 1) {
            $loadFileOnly = true;
        }
    }
    ?>
    <?php if (!$loadFileOnly) : ?>
        <input type="hidden" id="wpfd_file_category_ordering" value="<?php echo esc_attr($this->ordering); ?>" />
        <input type="hidden" id="wpfd_file_category_ordering_direction" value="<?php echo esc_attr($this->orderingdir); ?>" />
        <input type="hidden" id="wpfd_file_category_pagination" value="<?php echo esc_attr($this->fileListPagination); ?>" />
        <input type="hidden" id="wpfd_file_category_pagination_number" value="<?php echo esc_attr($this->filesperpage); ?>" />
        <input type="hidden" id="wpfd_file_category_load_more" value="<?php echo esc_attr($this->loadMore); ?>" />
        <input type="hidden" id="wpfd_file_category_load_more_number" value="<?php echo esc_attr($this->loadMorePerPage); ?>" />
        <input type="hidden" id="wpfd_file_category_slug" value="<?php echo isset($this->category->slug) ? esc_attr($this->category->slug) : ''; ?>" />
        <input type="hidden" id="wpfd_file_category_page" value="<?php echo esc_attr($this->page); ?>" />
        <input type="hidden" id="wpfd_file_category_page_total" value="<?php echo $total ? esc_attr($total) : 0; ?>" />
        <input type="hidden" id="wpfd_icon_set" value="<?php echo esc_attr($this->iconSet); ?>" />
        <div class="wpfd_tbl">
        <div class="wpfd_row head">
            <?php foreach ($items_thead as $thead_key => $thead_text) :
                $icon = '';
                if ($thead_key === $this->ordering) {
                    $icon = '<span class="dashicons dashicons-arrow-' . ($this->orderingdir === 'asc' ? 'up' : 'down') . '"></span>';
                }
                ?>
                <div data-key="<?php echo esc_attr($thead_key); ?>" class="wpfd_cell th_<?php echo esc_attr($thead_key); ?>">
                    <?php if ($thead_key === 'actions') { ?>
                        <span><?php echo esc_html($thead_text); ?></span>
                    <?php } else { ?>
                        <a href="#" class="<?php echo($this->ordering === $thead_key ? 'currentOrderingCol' : ''); ?>"
                           data-ordering="<?php echo esc_attr($thead_key); ?>"
                           data-direction="<?php echo esc_attr($this->orderingdir); ?>">
                            <span><?php echo esc_html($thead_text); ?></span><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nothing need to escape ?>
                        </a>
                    <?php } ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (is_array($this->files) || is_object($this->files)) : ?>
        <?php foreach ($this->files as $file) :
            $file_meta = get_post_meta($file->ID, '_wpfd_file_metadata', true);
            $remote_url = isset($file_meta['remote_url']) ? $file_meta['remote_url'] : false;
            if ($remote_url) {
                $httpcheck = isset($file->guid) ? $file->guid : '';
                $classes = preg_match('(http://|https://)', $httpcheck) ? ' is-remote-url' : '';
            } else {
                $classes = '';
            }
            /**
             * Check if file has linked to a product
             *
             * @param WP_Post
             *
             * @internal
             */
            $classes .= apply_filters('wpfd_addon_has_products', false, $file) ? ' isWoocommerce' : '';
            $isExpired = WpfdHelperFile::wpfdIsExpired($file->ID);

            if ($isExpired === true) {
                $classes .= ' is-expired ';
            } elseif ($isExpired > 0) {
                $classes .= ' is-expiry-set ';
            }

            $classes .= apply_filters('wpfd_file_enable_state', $file) ? ' unpublished' : '';
            $classes .= apply_filters('wpfd_file_upload_pending', $file->ID, $file->catid) ? ' isPending' : '';

            if (isset($this->is_search) && $this->is_search && isset($file->multiplefile) && $file->multiplefile === true) {
                $classes .= ' multiple';
            }
            $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $file->catid);
            if (!$categoryFrom) {
                $categoryFrom = 'none';
            }
            $data = array(
                'id-file'      => esc_attr($file->ID),
                'catid-file'   => esc_attr($file->catid),
                'file-ext'     => esc_attr($file->ext),
                'linkdownload' => esc_url($file->linkdownload),
                'cat-cloud-type'=> esc_attr($categoryFrom)
            );

            /**
             * Init data for file row
             *
             * @param array
             * @param WP_Post
             *
             * @internal
             */
            $data     = apply_filters('wpfd_admin_file_row_data', $data, $file);
            $dataHtml = '';
            foreach ($data as $key => $dataRow) {
                $dataHtml .= 'data-' . esc_attr($key) . '="' . $dataRow . '" ';
            }
            $dataHtml = rtrim($dataHtml);
            ?>
            <div class="wpfd_row file<?php echo esc_attr($classes); ?>" <?php echo $dataHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above
            ?>>
                <div class="wpfd_cell fileicon">
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
                            <?php if (isset($this->is_search) &&$this->is_search && strpos($classes, 'multiple') !== false) : ?>
                                <i onclick="Wpfd.go(<?php echo esc_html($file->catid . ', ' . $file->ID); ?>);"
                                   title="<?php esc_html_e('This file belongs to other categori(es). Click here to edit the original file in the category source.', 'wpfd'); ?>"
                                   class="wpfd-svg-icon-multiple-categories"></i>
                            <?php endif; ?>
                            <?php if (isset($this->category->term_id) && $this->category->term_id !== $file->catid && (!isset($this->is_search) || !$this->is_search)) : ?>
                                <i onclick="Wpfd.go(<?php echo esc_html($file->catid . ', ' . $file->ID); ?>);"
                                   title="<?php esc_html_e('This file belongs to other categori(es). Click here to edit the original file in the category source.', 'wpfd'); ?>"
                                   class="wpfd-svg-icon-multiple-categories"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="wpfd_cell title"><?php echo esc_html($file->post_title); ?></div>
                <div class="wpfd_cell size">
                    <?php echo esc_html((strtolower($file->size) === 'n/a' || $file->size <= 0) ? 'N/A' : WpfdHelperFiles::bytesToSize($file->size)); ?>
                </div>
                <div class="wpfd_cell created">
                    <?php echo esc_html($file->created); ?>
                </div>
                <div class="wpfd_cell modified">
                    <?php echo esc_html($file->modified); ?>
                </div>
                <div class="wpfd_cell version">&nbsp;<?php echo esc_html((isset($file->versionNumber)) ? $file->versionNumber : ''); ?>&nbsp;</div>
                <div class="wpfd_cell hits">&nbsp;<?php echo esc_html($file->hits) . ' ' . esc_html__('hits', 'wpfd'); ?>&nbsp;</div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
    <?php if (!$loadFileOnly) : ?>
        </div>
    <?php endif; ?>
    <?php
    if ($this->fileListPagination && $isLimited) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Echo only
        echo wpfd_category_pagination(
            array(
                'base'      => '',
                'format'    => '',
                'current'   => max(1, $this->page),
                'total'     => $total,
                'sourcecat' => $this->category->term_id
            )
        );
    } elseif ($this->loadMore && $isLimited && !$loadFileOnly) {
        $loadMoreContent  = '<div class="wpfd-load-more-section">';
        $loadMoreContent .= '<button type="button" id="wpfd_admin_load_more_btn" class="ju-button ju-v3-button" data-term_id="'. $this->category->term_id .'" data-page="'. $this->page .'">';
        $loadMoreContent .= esc_html('Load more', 'wpfd') .'</button>';
        $loadMoreContent .= '</div>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Echo only
        echo $loadMoreContent;
    }
    ?>
<?php endif; ?>
