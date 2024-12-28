<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0.3
 */
defined('ABSPATH') || die();

use Joomunited\WPFramework\v1_0_6\Application;

$download_attributes = apply_filters('wpfd_download_data_attributes_handlebars', '');
$globalConfig        = get_option('_wpfd_global_config');
$selectedDownload    = isset($globalConfig['download_selected']) ? (int) $globalConfig['download_selected'] : 0;
if (isset($upload_download_selected)) {
    $selectedDownload = (int) $upload_download_selected;
}
$current_cat_id      = (isset($filters) && isset($filters['catid'])) ? $filters['catid'] : 0;
$isFileSuggestion    = (isset($filters) && isset($filters['make_file_suggestion'])) ? $filters['make_file_suggestion'] : false;
$theme_column_default= array('title', 'category', 'version', 'size', 'hits', 'date added', 'download');
$theme_column        = (isset($filters) && isset($filters['theme_column']) && $filters['theme_column'] !== '') ? explode(',', $filters['theme_column']) : $theme_column_default;
if (!in_array('title', $theme_column)) {
    array_unshift($theme_column, 'title');
}
$mediaMenuOptionHtml = '';
$mediaMenuOptionCount= 0;
$showPagination      = (isset($filters) && isset($filters['show_pagination']) && intval($filters['show_pagination']) === 1) ? true : false;
$result_page         = (isset($filters) && isset($filters['page'])) ? intval($filters['page']) : 1;

$fsgFiles = ($isFileSuggestion && !is_null($files) && is_array($files) && count($files)) ? array_slice($files, 0, 10) : array();
if ($isFileSuggestion && is_array($fsgFiles) && count($fsgFiles)) {
    $files = $fsgFiles;
}

if ($showPagination && is_array($files) && !empty($files) && intval($limit) > 0) {
    $total  = (is_array($files) && !empty($files)) ? ceil(count($files) / intval($limit)) : 0;
    $offset = ($result_page - 1) * intval($limit);
    if ($offset < 0) {
        $offset = 0;
    }

    $files = array_slice($files, $offset, $limit);
}

if (isset($upload_show_file_limit)) {
    $limit = (int) $upload_show_file_limit;
}

$robots_meta_nofollow = isset($globalConfig['robots_meta_nofollow']) ? (int) $globalConfig['robots_meta_nofollow'] : 0;
$rel = '';
if (intval($robots_meta_nofollow) === 1) {
    $rel = ' rel="nofollow" ';
}

if ($files !== null && is_array($files) && count($files) > 0) : ?>
    <?php if (!$isFileSuggestion) : ?>
    <script type="text/javascript">
        wpfdajaxurl = "<?php echo wpfd_sanitize_ajax_url(Application::getInstance('Wpfd')->getAjaxUrl()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Print only ?>";
        function  initdefaultOption() {
            var $           = jQuery;
            var checkitem   = $('.mediaTableMenu .media-item');
            var showList    = [];
            checkitem.each(function () {
                if ($(this).prop("checked") == true) {
                    showList.push($(this).val());
                }
            });
            if (showList.length > 0) {
                jQuery("#total-media-list").val(showList.join(","));
            } else {
                jQuery("#total-media-list").val("");
            }
            var desc = "";
            var category = "";
            var ver = "";
            var size = "";
            var hits = "";
            var dateadd = "";
            var download = "";
            for(var i = 0; i<showList.length;i++) {
                if(showList[i] == "Description" ) {
                    desc = "Description";
                }
                if(showList[i] == "Category" ) {
                    category = "Category";
                }
                if(showList[i] == "Version") {
                    ver = "Version";
                }
                if(showList[i] == "Size") {
                    size = "Size";
                }
                if(showList[i] == "Hits") {
                    hits = "Hits";
                }
                if(showList[i] == "Date added") {
                    dateadd = "Date added";
                }
                if(showList[i] == "Download") {
                    download = "Download";
                }
            }
            if(desc === "Description") {
                jQuery(".file_desc").removeClass('filehidden');
            } else {
                jQuery(".file_desc").addClass('filehidden');
            }
            if(category === "Category") {
                jQuery(".file_category").removeClass('filehidden');
            } else {
                jQuery(".file_category").addClass('filehidden');
            }
            if (ver === "Version") {
                jQuery(".file_version").removeClass('filehidden');
            } else {
                jQuery(".file_version").addClass('filehidden');
            }
            if (size === "Size") {
                jQuery(".file_size").removeClass('filehidden');
            } else {
                jQuery(".file_size").addClass('filehidden');
            }
            if (hits === "Hits") {
                jQuery(".file_hits").removeClass('filehidden');
            } else {
                jQuery(".file_hits").addClass('filehidden');
            }
            if (dateadd === "Date added") {
                jQuery(".file_created").removeClass('filehidden');
            } else {
                jQuery(".file_created").addClass('filehidden');
            }
            if (download === "Download") {
                jQuery(".file_download").removeClass('filehidden');
            } else {
                jQuery(".file_download").addClass('filehidden');
            }

            var wpfdTable = $('.wpfd-results table.wpfd-search-result');
            wpfdTable.each(function() {
                var visibleRow = $(this).find('tbody tr');
                visibleRow.each(function() {
                    var visibleColumn = $(this).find('td:not(.filehidden)');
                    visibleColumn.each(function() {
                        if ($(this).is(visibleColumn.last())) {
                            $(this).attr('colspan', 2);
                            $(this).css('border-right', '1px solid #ccc');
                        } else {
                            $(this).attr('colspan', 1);
                            $(this).css('border-right', 'none');
                        }
                    })
                })
            })
        }

        function initUploadDefaultOption(container) {
            var $           = jQuery;
            var checkitem   = $(".file-upload-content[data-container='" + container + "'] .mediaTableMenu .media-item");
            var showList    = [];
            checkitem.each(function () {
                if ($(this).prop("checked") == true) {
                    showList.push($(this).val());
                }
            });
            if (showList.length > 0) {
                jQuery(".file-upload-content[data-container='" + container + "'] #total-media-list").val(showList.join(","));
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] #total-media-list").val("");
            }
            var desc        = "";
            var category    = "";
            var ver         = "";
            var size        = "";
            var hits        = "";
            var dateadd     = "";
            var download    = "";
            for(var i = 0; i<showList.length;i++) {
                if(showList[i] == "Description" ) {
                    desc = "Description";
                }
                if(showList[i] == "Category") {
                    category = "Category";
                }
                if(showList[i] == "Version") {
                    ver = "Version";
                }
                if(showList[i] == "Size") {
                    size = "Size";
                }
                if(showList[i] == "Hits") {
                    hits = "Hits";
                }
                if(showList[i] == "Date added") {
                    dateadd = "Date added";
                }
                if(showList[i] == "Download") {
                    download = "Download";
                }
            }
            if(desc === "Description") {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_desc").removeClass('filehidden');
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_desc").addClass('filehidden');
            }
            if(category === "Category") {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_category").removeClass('filehidden');
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_category").addClass('filehidden');
            }
            if (ver === "Version") {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_version").removeClass('filehidden');
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_version").addClass('filehidden');
            }
            if (size === "Size") {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_size").removeClass('filehidden');
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_size").addClass('filehidden');
            }
            if (hits === "Hits") {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_hits").removeClass('filehidden');
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_hits").addClass('filehidden');
            }
            if (dateadd === "Date added") {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_created").removeClass('filehidden');
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_created").addClass('filehidden');
            }
            if (download === "Download") {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_download").removeClass('filehidden');
            } else {
                jQuery(".file-upload-content[data-container='" + container + "'] .file_download").addClass('filehidden');
            }
        }

        function showViewOption() {
            var $         = jQuery;
            var checkitem = $('.mediaTableMenu .media-item');
            $(document).on("click", ".mediaTableMenu", function() {
                $(this).addClass('showlist');
                $(document).on("click", checkitem, function() {
                    if (!$(this).parents('.file-upload-content').length) {
                        initdefaultOption();
                        if($(".list-results .file_desc").hasClass("filehidden") && $(".list-results .file_created").hasClass("filehidden") ) {
                            $(".list-results .file_download").addClass("file_download_inline");
                        } else {
                            $(".list-results .file_download").removeClass("file_download_inline");
                        }
                        var checkall = $(".list-results .table thead th");
                        if(!checkall.hasClass("filehidden")) {
                            $(".list-results .file_title").addClass("adv_file_tt");
                        } else {
                            $(".list-results .file_title").removeClass("adv_file_tt");
                        }
                    } else {
                        var container = $(this).parents('.file-upload-content').data('container');
                        initUploadDefaultOption(container);
                        if($(".file-upload-content[data-container='" + container + "'] .list-results .file_desc").hasClass("filehidden") && $(".file-upload-content[data-container='" + container + "'] .list-results .file_created").hasClass("filehidden") ) {
                            $(".file-upload-content[data-container='" + container + "'] .list-results .file_download").addClass("file_download_inline");
                        } else {
                            $(".file-upload-content[data-container='" + container + "'] .list-results .file_download").removeClass("file_download_inline");
                        }
                        var checkall = $(".file-upload-content[data-container='" + container + "'] .list-results .table thead th");
                        if(!checkall.hasClass("filehidden")) {
                            $(".file-upload-content[data-container='" + container + "'] .list-results .file_title").addClass("adv_file_tt");
                        } else {
                            $(".file-upload-content[data-container='" + container + "'] .list-results .file_title").removeClass("adv_file_tt");
                        }
                    }
                });

                $(document).mouseup(e => {
                    if (!$(".mediaTableMenu").is(e.target) // if the target of the click isn't the container...
                        && $(".mediaTableMenu").has(e.target).length === 0) // ... nor a descendant of the container
                    {
                        $(".mediaTableMenu").removeClass('showlist');
                    }
                });
            });
        }

        function showtbResultonMobile() {
            if(jQuery("#wpfd-results").width() <=420) {
                jQuery(".file_version").css("display", "none");
                jQuery(".file_size").css("display", "none");
                jQuery(".file_hits").css("display", "none");
                jQuery(".file_created").css("display", "none");
            }
        }

        jQuery(document).ready(function () {
            initdefaultOption();
            showViewOption();
            showtbResultonMobile();
            if (jQuery('.wpfd-results-tooltip').length) {
                jQuery('.wpfd-results-tooltip').qtip({
                    content: {
                        attr: 'title',
                    },
                    position: {
                        my: 'bottom left',
                        at: 'top left',
                    },
                    style: {
                        tip: {
                            corner: true,
                        },
                        classes: 'wpfd-qtip qtip-rounded wpfd-qtip-dashboard',
                    },
                    show: 'mouseover',
                    hide: {
                        fixed: true,
                        delay: 10,
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
    <table class="table wpfd-search-result wpfd-table-<?php echo esc_html($current_cat_id); ?>">
        <thead>
            <?php foreach ($theme_column as $key => $value) :
                switch ($value) {
                    case 'date added':
                        $th_class = 'hcreated file_created';
                        $mediaMenuOptionClass = 'media-item-created';
                        break;
                    case 'description':
                        $th_class = 'hdescription file_desc';
                        $mediaMenuOptionClass = 'media-item-desc';
                        break;
                    default:
                        $th_class = 'h'.$value.' file_'.$value;
                        $mediaMenuOptionClass = 'media-item-'.$value;
                        break;
                }
                $value = ucfirst($value);
                if ($value !== 'Title') {
                    $mediaMenuOptionHtml .= '<li>
                        <input type="checkbox" class="media-item '.$mediaMenuOptionClass.'" name="toggle-cols" id="toggle-col-MediaTable-0-'.$mediaMenuOptionCount.'" value="'.$value.'" checked="checked"> <label for="toggle-col-MediaTable-0-'.$mediaMenuOptionCount.'">'.esc_html__($value, 'wpfd').'</label></li>';// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string translate only
                    $mediaMenuOptionCount++;
                }
                ?>
                <th class="<?php echo esc_attr($th_class); ?>">
                    <span><?php echo esc_html__($value, 'wpfd'); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- This is a string translate only ?></span>
                </th>
            <?php endforeach; ?>
            <th class="mediaMenuOption">
                <div class="mediaTableMenu">
                    <a title="Columns"><i class="zmdi zmdi-settings"></i></a>
                    <ul>
                        <?php echo $mediaMenuOptionHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Echo only ?>
                    </ul>
                    <input type="hidden" class="media-list" name="media-list" id="total-media-list" value="" style="visibility: hidden">
                </div>
            </th>
        </thead>
        <tbody>
        <?php
        $iconSet = isset($config['icon_set']) && $config['icon_set'] !== 'default' ? ' wpfd-icon-set-' . $config['icon_set'] : '';
        foreach ($files as $key => $file) :
            $isProduct = isset($file->show_add_to_cart) ? $file->show_add_to_cart : false;
            $wpfdTaxonomy = 'wpfd-category';
            $wpfdTerms = get_term($file->catid, $wpfdTaxonomy);
            if (!empty($wpfdTerms) && !is_wp_error($wpfdTerms)) {
                $breadcrumbs = array();
                $breadcrumbs[] = $wpfdTerms->name;
                $ancestors = get_ancestors($file->catid, $wpfdTaxonomy);
                if ($ancestors) {
                    foreach ($ancestors as $ancestor_id) {
                        $ancestor = get_term($ancestor_id, $wpfdTaxonomy);

                        if ($ancestor && !is_wp_error($ancestor)) {
                            $breadcrumbs[] = $ancestor->name;
                        }
                    }
                }

                $breadcrumbs = array_reverse($breadcrumbs);
                $catTooltip = '';
                if (count($breadcrumbs) > 1) {
                    $catTooltip = implode(' > ', $breadcrumbs);
                }
                $catFile = $wpfdTerms->name;
            } else {
                $catTooltip = '';
                $catFile = '';
            }
            ?>
            <?php  if (wpfdPasswordRequired($file, 'file')) : ?>
            <tr class="wpfd-password-protection-form" style="background: #fff">
                <td class="full-width">
                    <?php
                    $fileTitle = isset($file->post_title) ? $file->post_title : '';
                    $passwordFormProtection = '<h3 class="protected-title" title="' . $fileTitle . '">' . esc_html__('Protected: ', 'wpfd') . $fileTitle . '</h3>';
                    $passwordFormProtection .= wpfdGetPasswordForm($file, 'file', $file->catid);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Print only
                    echo $passwordFormProtection;
                    ?>
                </td>
            </tr>
            <?php  else : ?>
            <tr>
                <?php foreach ($theme_column as $key => $value) : ?>
                    <?php if (count($theme_column) === ($key + 1)) {
                        $colspan = 2;
                    } else {
                        $colspan = 1;
                    } ?>
                    <?php if ($value === 'title') : ?>
                        <td class="file_title title" colspan="<?php echo esc_attr($colspan); ?>">
                            <span class="file-icon">
                            <?php if (isset($config['custom_icon']) && $config['custom_icon']
                                && isset($file->file_custom_icon) && $file->file_custom_icon) : ?>
                                <img class="icon-custom" src="<?php echo esc_url($file->file_custom_icon); ?>">
                            <?php else : ?>
                                <i class="ext ext-<?php echo esc_attr($file->ext) . esc_attr($iconSet); ?>"></i>
                            <?php endif; ?>
                            </span>
                            <?php if ($selectedDownload) : ?>
                            <label class="wpfd_checkbox">
                                <input class="cbox_file_download" type="checkbox" data-id="<?php echo esc_attr($file->ID); ?>" data-catid="<?php echo esc_attr($file->catid); ?>" />
                                <span></span>
                            </label>
                            <?php endif; ?>
                            <a <?php echo $download_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> class="file-item wpfd-file-link" data-id="<?php echo esc_attr($file->ID); ?>"
                                <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?>
                                href="<?php echo esc_url($file->linkdownload); ?>" id="file-<?php echo esc_attr($file->ID); ?>"
                                data-catid="<?php echo esc_attr($file->catid); ?>" title="<?php echo isset($file->title) ? esc_attr($file->title) : esc_attr($file->post_title); ?>"
                                <?php if (!wpfd_can_download_files()) : ?>
                                     onclick="return false;"
                                <?php endif; ?>
                            >
                                <?php
                                    $fileTitle = isset($file->crop_title) ? $file->crop_title : $file->title;
                                    $replaceHyphenFileTitle = apply_filters('wpfdReplaceHyphenFileTitle', false);
                                if ($replaceHyphenFileTitle) {
                                    $fileTitle = str_replace('-', ' ', $fileTitle);
                                }
                                    echo esc_html($fileTitle);
                                ?>
                            </a>
                        </td>
                    <?php endif; ?>
                    <?php if ($value === 'description') : ?>
                        <td class="file_desc" colspan="<?php echo esc_attr($colspan); ?>"><?php echo isset($file->description) ? esc_html($file->description) : ''; ?></td>
                    <?php endif; ?>
                    <?php if ($value === 'category') : ?>
                        <td class="file_category" colspan="<?php echo esc_attr($colspan); ?>"><span class="wpfd-results-tooltip" title="<?php echo esc_attr($catTooltip) ?>"><?php echo isset($catFile) ? esc_html($catFile) : ''; ?></span></td>
                    <?php endif; ?>
                    <?php if ($value === 'version') : ?>
                        <td class="file_version" colspan="<?php echo esc_attr($colspan); ?>"><?php echo isset($file->version) ? esc_html($file->version) : ''; ?></td>
                    <?php endif; ?>
                    <?php if ($value === 'size') : ?>
                        <td class="file_size" colspan="<?php echo esc_attr($colspan); ?>"><?php echo esc_html((strtolower($file->size) === 'n/a' || $file->size <= 0) ? 'N/A' : WpfdHelperFiles::bytesToSize($file->size)); ?></td>
                    <?php endif; ?>
                    <?php if ($value === 'hits') : ?>
                        <td class="file_hits" colspan="<?php echo esc_attr($colspan); ?>"><?php echo isset($file->hits) ? esc_html($file->hits) : ''; ?></td>
                    <?php endif; ?>
                    <?php if ($value === 'date added') : ?>
                        <td class="file_created" colspan="<?php echo esc_attr($colspan); ?>"><?php echo isset($file->created) ? esc_html($file->created) : ''; ?></td>
                    <?php endif; ?>
                    <?php if ($value === 'download') : ?>
                        <td class="file_download viewer" colspan="<?php echo esc_attr($colspan); ?>">
                            <?php if ($isProduct) : ?>
                                <a class="downloadlink wpfd_downloadlink"
                                    <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?>
                                    href="<?php echo esc_html($file->linkdownload); ?>" data-product_id="<?php echo esc_html($file->product_id); ?>">
                                    <i class="zmdi zmdi-shopping-cart-plus wpfd-add-to-cart"></i>
                                </a>
                                <a href="<?php echo esc_url($file->viewerlink); ?>" <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> class="wpfd_previewlink openlink" target="_blank">
                                    <i class="zmdi zmdi-filter-center-focus wpfd-preview"></i>
                                </a>
                            <?php else : ?>
                                <?php if (wpfd_can_download_files()) : ?>
                                <a class="downloadlink wpfd_downloadlink"
                                    <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?>
                                    <?php echo $download_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?>
                                    href="<?php echo esc_html($file->linkdownload); ?>">
                                    <i class="zmdi zmdi-cloud-download wpfd-download"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($viewer !== 'no' && wpfd_can_preview_files()) : ?>
                                    <?php
                                    if (isset($file->openpdflink)) { ?>
                                        <a href="<?php echo esc_url($file->openpdflink); ?>" 
                                            <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?> 
                                            class="wpfd_previewlink openlink" target="_blank">
                                            <i class="zmdi zmdi-filter-center-focus wpfd-preview"></i>
                                        </a>
                                    <?php } elseif (isset($file->viewerlink) && $file->viewerlink) { ?>
                                        <a data-id="<?php echo esc_attr($file->ID); ?>"
                                           data-catid="<?php echo esc_attr($file->catid); ?>"
                                           data-file-type="<?php echo esc_attr($file->ext); ?>"
                                           class="wpfd_previewlink openlink <?php echo esc_attr(($viewer === 'lightbox') ? 'wpfdlightbox' : ''); ?>"
                                            <?php echo esc_attr(($viewer === 'tab') ? 'target="_blank"' : ''); ?>
                                            <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Data attributes ?>
                                           href='<?php echo esc_url($file->viewerlink); ?>'>
                                            <i class="zmdi zmdi-filter-center-focus wpfd-preview"></i>
                                        </a>
                                    <?php } ?>
                                <?php endif; ?>
                            <?php endif;?>
                        </td>
                    <?php endif; ?>
                <?php endforeach;?>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php wpfd_num($limit); ?>
    <?php
    if ($showPagination && is_array($files) && !empty($files) && intval($limit) > 0) {
        echo wpfd_search_pagination( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            array(
                'base' => '',
                'format' => '',
                'current' => max(1, $result_page),
                'total' => $total,
                'source_search' => intval($current_cat_id)
            )
        );
    }
    ?>
<?php else : ?>
    <?php if (!$isFileSuggestion) : ?>
        <p class="text-center">
            <?php esc_html_e("Sorry, we haven't found anything that matches this search query", 'wpfd'); ?>
        </p>
    <?php endif; ?>
<?php endif; ?>
