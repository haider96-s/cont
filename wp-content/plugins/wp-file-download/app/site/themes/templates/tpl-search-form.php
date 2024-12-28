<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0.3
 */
defined('ABSPATH') || die();

$globalConfig = get_option('_wpfd_global_config');
$selectedDownload = isset($globalConfig['download_selected']) ? (int) $globalConfig['download_selected'] : 0;
$defaultExtensions = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,'
. 'ppt,pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,'
. 'aiff,alac,amr,au,cdda,flac,m3u,m4a,m4p,mid,mp3,mp4,mpa,ogg,pac,ra,wav,wma,3gp,asf,avi,flv,m4v,'
. 'mkv,mov,mpeg,mpg,rm,swf,vob,wmv,css,img';
$allowedExtStr = isset($globalConfig['allowedext']) ? $globalConfig['allowedext'] : $defaultExtensions;
$allowedExt = explode(',', $allowedExtStr);

if (!empty($allowedExt) && is_array($allowedExt)) {
    usort($allowedExt, function ($a, $b) {
        return strnatcmp(strtolower($a), strtolower($b));
    });
}

$dropbox_connected = wpfd_dropbox_connected();
$google_connected = wpfd_google_drive_connected();
$google_team_drive_connected = wpfd_google_team_drive_connected();
$onedrive_connected = wpfd_onedrive_connected();
$onedrive_business_connected = wpfd_onedrive_business_connected();
$aws_connected = wpfd_aws_connected();
$nextcloud_connected = wpfd_nextcloud_connected();

/**
 * Filter to show category list as default
 *
 * @return boolean
 *
 * @internal
 */
$openCategories = apply_filters('wpfd_search_engine_show_categories_as_default', false);
$style = $openCategories ? '' : 'display: none';
$curCatId = isset($args['catid']) ? $args['catid'] : 0;
if (!is_numeric($curCatId) && class_exists('WpfdAddonHelper')) {
    $curCatId = WpfdAddonHelper::getCatIdByCloudId($curCatId);
}
if ((int) $args['cat_filter'] === 0) {
    $inputSearchClass = 'wpfd-hide-cat-filter';
} else {
    $inputSearchClass = '';
}
$showFilters = (isset($args['show_filters']) && intval($args['show_filters']) === 1) ? true : false;
$showFiltersClass = $showFilters ? 'display: none;' : '';
$arrowClass = $showFilters ? 'toggle-arrow-down-alt' : '';

/**
 * Filter to clean search
 *
 * @return boolean
 *
 * @internal
 */
$clear = apply_filters('wpfd_search_engine_clear_all', false);
$showClear = $clear ? 'display: block;' : 'display: none;';
?>

<script>
    wpfdajaxurl = "<?php echo $ajaxUrl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keep this, if not it error ?>";
    var filterData = null;
    var curCatId = "<?php echo $curCatId; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keep this, if not it error ?>";
    if (typeof availTags === 'undefined') {
        var availTags = [];
    }
    var msg_search_box_placeholder = "<?php echo esc_html__('Input tags here...', 'wpfd'); ?>";
    <?php if (isset($availableTags) && count($availableTags)) : ?>
    availTags[curCatId] = <?php echo json_encode($availableTags); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- allready esc in view.php?>;
    <?php endif; ?>
    jQuery(document).ready(function () {
        jQuery('.filter_catid_chzn').removeAttr('style');
        jQuery('.chzn-search input').removeAttr('readonly');

        <?php if ((int) $args['tag_filter'] === 1 && $args['display_tag'] === 'searchbox') : ?>
            var defaultTags = [];

            <?php if (isset($filters) && isset($filters['ftags'])) : ?>
            var ftags = '<?php echo esc_html($filters['ftags']);?>';
            defaultTags = ftags.split(',');
            <?php endif; ?>
            jQuery(".input_tags").each(function () {
                var search_category_id = jQuery(this).parents('.wpfd-adminForm').find('input[name="catid"]').val();
                availTags = (typeof (search_category_id) !== 'undefined' && typeof (availTags[search_category_id]) !== 'undefined') ? availTags[search_category_id] : availTags;
                jQuery(this).tagit({
                    availableTags: availTags,
                    allowSpaces: true,
                    initialTags: defaultTags,
                    autocomplete: { source: function( request, response ) {
                            var filter = request.term.toLowerCase();
                            var tag = availTags.find(function(t) {return t.label.toLowerCase().includes(filter); });
                            if (tag !== undefined) {
                                response([tag.label]);
                            }
                        }},
                    beforeTagAdded: function(event, ui) {
                        var tag = availTags.find(function(t) {return t.label.includes(ui.tagLabel); });
                        if (!tag) {
                            jQuery('span.error-message').css("display", "block").fadeOut(2000);
                            setTimeout(function() {
                                try {
                                    jQuery(".input_tags").tagit("removeTagByLabel", ui.tagLabel, 'fast');
                                } catch (e) {
                                    console.log(e);
                                }
                            }, 100);

                            return;
                        }
                        return true;
                    }
                });
                if (jQuery(".tags-filtering .tagit-new input").length) {
                    jQuery(".tags-filtering .tagit-new input").attr("placeholder", msg_search_box_placeholder);
                } else {
                    setTimeout(function() {
                        if (jQuery(".tags-filtering .tagit-new input").length) {
                            jQuery(".tags-filtering .tagit-new input").attr("placeholder", msg_search_box_placeholder);
                        }
                    }, 1000)
                }
            });
        <?php endif; ?>
        <?php if (!empty($filters)) : ?>
            filterData = <?php echo json_encode($filters);?>;
        <?php endif; ?>
        if (jQuery('select[name="extension"]').length) {
            jQuery('select[name="extension"]').chosen({width: '100%', search_contains: false});
        } else {
            setTimeout(function() {
                if (jQuery('select[name="extension"]').length) {
                    jQuery('select[name="extension"]').chosen({width: '100%', search_contains: true});
                }
            }, 1000)
        }
        if (jQuery('.fusion-builder-live').length || jQuery('.et_divi_theme.et-fb').length) {
            setInterval(function() {
                if (jQuery(".wpfd-adminForm").length) {
                    if (jQuery('select[name="extension"]').length) {
                        jQuery('select[name="extension"]').chosen({width: '100%', search_contains: true});
                    } else {
                        setTimeout(function() {
                            if (jQuery('select[name="extension"]').length) {
                                jQuery('select[name="extension"]').chosen({width: '100%', search_contains: true});
                            }
                        }, 3000);
                    }
                }
            }, 1000);
        }
        window.history.pushState(filterData, '', window.location);
    });
</script>

<form action="" id="adminForm-<?php echo esc_html($curCatId); ?>" class="wpfd-adminForm" name="adminForm" method="post">
    <div id="loader" style="display:none; text-align: center">
        <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/searchloader.svg'); ?>" style="margin: 0 auto"/>
    </div>
    <div class="box-search-filter">
        <div class="searchSection">
            <div class="only-file input-group clearfix wpfd_search_input" id="Search_container">
                <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/search-24.svg'); ?>" class="material-icons wpfd-icon-search" />
                <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/search_loading.gif'); ?>" class="wpfd-icon-search-loading" style="display: none" />
                <input type="text" class="pull-left required txtfilename <?php echo esc_html($inputSearchClass); ?>" name="q" id="txtfilename" autocomplete="off"
                placeholder="<?php esc_html_e('Search files...', 'wpfd'); ?>"
                value="<?php echo esc_html(isset($filters['q']) ? $filters['q'] : ''); ?>"
                />
                <button type="button" id="btnsearch" class="pull-left"><?php esc_html_e('Search', 'wpfd'); ?></button>

                <?php if ((int) $args['cat_filter'] === 1) : ?>
                    <div class="categories-filtering-selection">
                        <div class="categories-filtering">
                            <?php if (is_array($categories) && count($categories) > 0) : ?>
                                <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/arrow_down_dark.svg'); ?>" class="material-icons cateicon"/>
                            <?php endif; ?>
                            <div class="categories-filtering-overlay"></div>
                            <div class="cate-lab"><?php echo esc_html($categoryName); ?></div>
                            <?php $currentCatId = isset($filters['catid']) ? $filters['catid'] : (isset($args['catid']) ? $args['catid'] : 0); ?>
                            <input title="" type="hidden" value="<?php echo esc_html($currentCatId); ?>" id="filter_catid" class="chzn-select filter_catid" name="catid" data-cattype="" data-slug="" />
                            <?php if (is_array($categories) && count($categories) > 0) : ?>
                            <div class="ui-widget wpfd-listCate" style="<?php echo $style;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text only ?>">
                                <ul class="cate-list" id="cate-list">
                                    <?php
                                        $excludes = array();
                                    if (isset($args['exclude']) && $args['exclude'] !== '0') {
                                        $excludes = array_merge($excludes, explode(',', trim($args['exclude'])));
                                    }
                                    ?>
                                        <li class="search-cate" >
                                            <input class="qCatesearch" id="wpfdCategorySearch" data-id="" placeholder="<?php esc_html_e('Search...', 'wpfd'); ?>" />
                                        </li>
                                        <li class="cate-item" data-catid="<?php echo esc_html($currentCatId); ?>">
                                            <span class="wpfd-toggle-expand"></span>
                                            <span class="wpfd-folder-search"></span>
                                            <label><?php echo esc_html($categoryName); ?></label>
                                        </li>
                                        <?php
                                        foreach ($categories as $key => $category) {
                                            $categoryLevel = intval($category->level);
                                            if ($currentCatId !== 0) {
                                                $categoryLevel++;
                                            }
                                            if ($categoryLevel > 1) {
                                                $downicon = '<span class="wpfd-toggle-expand child-cate"></span>';
                                            } else {
                                                $downicon = '<span class="wpfd-toggle-expand"></span>';
                                            }

                                            if (isset($args['exclude']) && $args['exclude'] !== '0' && $args['exclude'] !== '') {
                                            // Remove exclude category and it children
                                                if (in_array((string) $category->term_id, $excludes) || in_array((string) $category->parent, $excludes)) {
                                                // Add it id to excludes array
                                                    $excludes[] = (string) $category->term_id;
                                                    continue;
                                                }
                                            }

                                            if ((string) $currentCatId === '0') {
                                                if ($categoryLevel > 1) {
                                                    $level = esc_html(str_repeat('*', $category->level));
                                                } elseif ($categoryLevel === 1) {
                                                    $level = esc_html('*');
                                                } else {
                                                    $level = '';
                                                }
                                            } else {
                                                if ($categoryLevel > 1) {
                                                    $level = esc_html(str_repeat('*', $categoryLevel));
                                                } elseif ($categoryLevel === 1) {
                                                    $level = esc_html('*');
                                                } else {
                                                    $level = '';
                                                }
                                            }

                                            if (isset($filters['catid']) && (int) $filters['catid'] === $category->term_id) {
                                                $echo = '<li class="cate-item choosed" data-catid="'.esc_attr($category->term_id).'" data-catlevel="'. esc_attr($categoryLevel) .'">'
                                                . '<span class="space-child">' . $level . '</span>'
                                                . $downicon
                                                . '<span class="wpfd-folder-search"></span>'
                                                . '<label>' . esc_html($category->name) .'</label>'
                                                . '</li>';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc above
                                                echo $echo;
                                            } else {
                                                $termId = isset($category->wp_term_id) ? $category->wp_term_id : $category->term_id;
                                                $spClouds = array('googleDrive', 'googleTeamDrive', 'dropbox', 'onedrive', 'onedrive_business', 'aws', 'nextcloud');
                                                $cateType = apply_filters('wpfdAddonCategoryFrom', $termId);
                                                if (empty($cateType) || !in_array($cateType, $spClouds)) {
                                                    $cateType = 'default';
                                                }

                                                if (($cateType === 'dropbox' && !$dropbox_connected) ||
                                                    ($cateType === 'googleDrive' && !$google_connected) ||
                                                    ($cateType === 'googleTeamDrive' && !$google_team_drive_connected) ||
                                                    ($cateType === 'onedrive' && !$onedrive_connected) ||
                                                    ($cateType === 'onedrive_business' && !$onedrive_business_connected) ||
                                                    ($cateType === 'aws' && !$aws_connected) ||
                                                    ($cateType === 'nextcloud' && !$nextcloud_connected)
                                                ) {
                                                    continue;
                                                }

                                                if ($cateType === 'nextcloud') {
                                                    $category->term_id = $termId;
                                                }

                                                $echo = '<li class="cate-item" data-catid="'.esc_attr($category->term_id).'" data-catlevel="'. esc_attr($category->level) .'" 
                                            data-cattype="' . esc_attr($cateType) . '" data-slug="' . $category->slug . '">'
                                                . '<span class="space-child">'. $level .'</span>'
                                                . $downicon
                                                . '<span class="wpfd-folder-search"></span>'
                                                . '<label>' . esc_html($category->name) .'</label>'
                                                . '</li>';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc above
                                                echo $echo;
                                            }
                                        }
                                        ?>
                            </ul>
                        </div>
                            <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($args['catid'] !== '0') : ?>
                <input type="hidden" name="catid" value="<?php echo esc_html($args['catid']); ?>" />
                <?php endif; ?>

                <div id="wpfd_search_file_suggestion" class="wpfd_search_file_suggestion" style="display: none"></div>
            </div>
            <button id="btnsearchbelow" class="btnsearchbelow wpfd-btnsearchbelow" type="button">
                <?php esc_html_e('Search', 'wpfd'); ?>
            </button>
        </div>

    <?php if ((isset($args['tag_filter']) && (int) $args['tag_filter'] === 1) ||
        (isset($args['create_filter']) && (int) $args['create_filter'] === 1) ||
        (isset($args['update_filter']) && (int) $args['update_filter'] === 1) ||
        (isset($args['type_filter']) && (int) $args['type_filter'] === 1) ||
        (isset($args['weight_filter']) && (int) $args['weight_filter'] === 1)) : ?>
        <?php
            $filter_show = true;
        if ((isset($args['tag_filter']) && (int) $args['tag_filter'] === 0) &&
                (isset($args['type_filter']) && (int) $args['type_filter'] === 0) &&
                (isset($args['weight_filter']) && (int) $args['weight_filter'] === 0)) {
            $filter_show = false;
        }
        ?>
        <div class="by-feature feature-border Category_container" id="Category_container">
            <?php if ((isset($args['tag_filter']) && (int) $args['tag_filter'] === 1) &&
                (isset($args['create_filter']) && (int) $args['create_filter'] === 1) &&
                (isset($args['update_filter']) && (int) $args['update_filter'] === 1)) : ?>
                <div class="wpfd_tab">
                    <button class="tablinks active" onclick="openSearchfilter(event, 'Filter')"><?php esc_html_e('FILTER', 'wpfd') ?></button>
                    <button class="tablinks" onclick="openSearchfilter(event, 'Tags')"><?php esc_html_e('TAGS', 'wpfd'); ?></button>
                    <span class="feature-toggle toggle-arrow-up-alt"></span>
                </div>
            <?php endif; ?>
            <?php if ($filter_show) : ?>
                <div class="top clearfix">
                    <div class="pull-left"><p class="filter-lab wpfd-font-600"><?php esc_html_e('Filters', 'wpfd') ?></p></div>
                    <div class="pull-right"><span class="feature-toggle toggle-arrow-up-alt <?php echo esc_attr($arrowClass); ?>"></span></div>
                </div>
            <?php endif; ?>
            <?php
            $span = 'span3';
            if ((int) $args['tag_filter'] === 1 && (int) $args['display_tag'] === 'checkbox') {
                $span = 'span4';
            }
            ?>
            <div class="feature clearfix row-fluid wpfd_tabcontainer" style="<?php echo esc_html($showFiltersClass); ?>">
                <?php if ($filter_show) : ?>
                    <!-- Tab content -->
                    <div id="Filter" class="wpfd-filter wpfd_tabcontent active">
                        <!-- Start of Tags filter -->
                        <?php
                            $tags_class = '';
                        if ((int) $args['tag_filter'] === 1 && $args['display_tag'] === 'searchbox') {
                            $tags_class = 'wpfd-search-section';
                        }
                        ?>
                        <div id="Tags" class="wpfd-tags wpfd_tabcontent <?php echo esc_html($tags_class); ?> mobilecontent-old">
                            <?php if (!empty($allTagsFiles)) : ?>
                                <?php if ((int) $args['tag_filter'] === 1 && $args['display_tag'] === 'searchbox') : ?>
                                    <div class="span12 tags-filtering">
                                        <p class="tags-info"><?php esc_html_e('TAGS', 'wpfd'); ?></p>
                                        <input title="" type="text" name="ftags" class="tagit input_tags"
                                        value="<?php echo esc_attr(isset($filters['ftags']) ? $filters['ftags'] : ''); ?>"/>
                                    </div>
                                    <span class="error-message"><?php esc_html_e('No tag matching the query', 'wpfd'); ?></span>
                                <?php endif; ?>

                                <?php if ((int) $args['tag_filter'] === 1 && $args['display_tag'] === 'checkbox') : ?>
                                    <div class="clearfix row-fluid">
                                        <div class="span12 chk-tags-filtering">
                                            <p class="tags-info" style="text-align:left;"><?php esc_html_e('TAGS', 'wpfd'); ?></p>
                                            <input type="hidden" name="ftags"
                                            class="input_tags"
                                            value="<?php echo esc_attr(isset($filters['ftags']) ? $filters['ftags'] : ''); ?>"/>
                                            <?php
                                            $allTags = str_replace(array('[', ']', '"'), '', $allTagsFiles);
                                            if (!empty($availableTags)) {
                                                echo '<ul>';
                                                foreach ($availableTags as $fileTag) {
                                                    ?>
                                                    <li class="tags-item">
                                                        <span><?php echo esc_html($fileTag->label); ?></span>
                                                        <input type="checkbox" name="chk_ftags[]" value="<?php echo esc_attr($fileTag->label);?>" class="ju-input chk_ftags" id="ftags<?php echo esc_attr($fileTag->id); ?>" />
                                                    </li>
                                                <?php }
                                                echo '</ul>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php if ((int) $args['tag_filter'] === 0) : ?>
                                    <div class="no-tags"></div>
                                <?php else : ?>
                                <div class="no-tags"><?php echo esc_html('No tags in this category found!', 'wpfd'); ?></div>
                                <?php endif; ?>    
                            <?php endif; ?>
                        </div>
                        <!-- End of Tags filter -->

                        <!-- Start of File type filter -->
                        <?php if (intval($args['type_filter'])) : ?>
                            <div id="wpfd_type_filter" class="wpfd-type-filter wpfd-search-section">
                                <input id="wpfd_search_file_type_allowed_ext" type="hidden" value="<?php echo esc_attr($allowedExtStr); ?>" />
                                <p class="label" style="margin-top: 0;"><?php esc_html_e('File types', 'wpfd'); ?></p>
                                <select id="wpfd-search-file-type" class="wpfd-search-file-type" list="wpfd-search-file-type" name="extension" autocomplete="on" data-placeholder="<?php echo esc_html('-- Select type --', 'wpfd'); ?>" multiple>
                                    <?php
                                    if (!empty($allowedExt)) {
                                        foreach ($allowedExt as $ext) {
                                            $lowedExt = strtolower($ext);
                                            echo '<option value="' . esc_attr($lowedExt) . '">'. esc_attr($lowedExt) .'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <!-- End of File type filter -->

                        <?php if (intval($args['weight_filter'])) : ?>
                            <!-- Start of Weight filter -->
                            <div id="wpfd_weight_filter" class="wpfd-weight-filter wpfd-search-section">
                                <p class="label wpfd-label-mb" style="margin-top: 10px;"><?php esc_html_e('File weight', 'wpfd'); ?></p>
                                <div class="weight-filters">
                                    <div class="from-weight-filter-container weight-container">
                                        <p class="label">
                                            <span class="wpfd-label-desk"><?php esc_html_e('File weight, from', 'wpfd'); ?></span>
                                            <span class="wpfd-label-mb"><?php esc_html_e('From', 'wpfd'); ?></span>
                                        </p>
                                        <div>
                                            <input type="number" name="from_weight" value="" min="0" step="1" onkeydown="return wpfdValidateNumberInput(event)" />
                                            <select name="from_weight_unit" style="max-width: 70px;">
                                                <option value="kb">Kb</option>
                                                <option value="mb">Mb</option>
                                                <option value="gb">Gb</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="to-weight-filter-container weight-container">
                                        <p class="label"><?php esc_html_e('To', 'wpfd'); ?></p>
                                        <div>
                                            <input type="number" name="to_weight" value="" min="0" step="1" onkeydown="return wpfdValidateNumberInput(event)" />
                                            <select name="to_weight_unit" style="max-width: 70px;">
                                                <option value="kb">Kb</option>
                                                <option value="mb">Mb</option>
                                                <option value="gb">Gb</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End of Weight filter -->
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Start of Date filter-->
                <?php
                    $date_class = 'date-filter';
                if ((int) $args['create_filter'] === 0 && (int) $args['update_filter'] === 0) {
                    $date_class = 'wpfd-date-hidden';
                }
                if ((int) $args['create_filter'] === 1 xor (int) $args['update_filter'] === 1) {
                    $date_class .= ' wpfd-date-filter-d1';
                }
                ?>
                <div class="<?php echo esc_attr($date_class); ?>">
                    <?php if ((int) $args['create_filter'] === 1) : ?>
                        <div class="creation-date">
                            <p class="date-info"><?php esc_html_e('Creation Date', 'wpfd'); ?></p>
                            <div class="create-date-container">
                                <div class="date-info-from">
                                    <span class="lbl-date"><?php esc_html_e('From', 'wpfd'); ?> </span>
                                    <div class="input-icon-date">
                                        <input title="" class="input-date cfrom" type="text" data-min="cfrom" name="cfrom" onkeydown="return wpfdCheckInput(event)"
                                        value="<?php echo esc_attr(isset($filters['cfrom']) ? $filters['cfrom'] : ''); ?>"
                                        />
                                        <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/calendar_today.svg'); ?>" data-id="cfrom" class="icon-date icon-calendar material-icons wpfd-range-icon"/>
                                    </div>
                                </div>
                                <div class="date-info-to">
                                    <span class="lbl-date"><?php esc_html_e('To', 'wpfd'); ?></span>
                                    <div class="input-icon-date">
                                        <input title="" class="input-date cto" data-min="cfrom" type="text" name="cto" onkeydown="return wpfdCheckInput(event)"
                                        value="<?php echo esc_attr(isset($filters['cto']) ? $filters['cto'] : ''); ?>"/>
                                        <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/calendar_today.svg'); ?>" data-id="cto" data-min="cfrom" class="icon-date icon-calendar material-icons wpfd-range-icon"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ((int) $args['update_filter'] === 1) : ?>
                        <div class="update-date">
                            <p class="date-info"><?php esc_html_e('Update', 'wpfd'); ?></p>
                            <div class="update-date-container">
                                <div class="date-info-from">
                                    <span class="lbl-date"><?php esc_html_e('From', 'wpfd'); ?> </span>
                                    <div class="input-icon-date">
                                        <input title="" class="input-date ufrom" type="text" data-min="ufrom" onkeydown="return wpfdCheckInput(event)"
                                        value="<?php echo esc_attr(isset($filters['ufrom']) ? $filters['ufrom'] : ''); ?>"
                                        name="ufrom" />
                                        <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/calendar_today.svg'); ?>" data-id="ufrom" class="icon-date icon-calendar material-icons wpfd-range-icon"/>
                                    </div>
                                </div>
                                <div class="date-info-to">
                                    <span class="lbl-date"><?php esc_html_e('To', 'wpfd'); ?> </span>
                                    <div class="input-icon-date">
                                        <input title="" class="input-date uto" type="text" data-min="ufrom" onkeydown="return wpfdCheckInput(event)"
                                        value="<?php echo esc_attr(isset($filters['uto']) ? $filters['uto'] : ''); ?>"
                                        name="uto" />
                                        <img src="<?php echo esc_url($baseUrl. '/app/site/assets/images/calendar_today.svg'); ?>" data-id="uto" data-min="ufrom" class="icon-date icon-calendar material-icons wpfd-range-icon"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ((int) $args['create_filter'] === 1 && (int) $args['update_filter'] === 1) : ?>
                    <div class="wpfd-vertical-border"></div>
                    <?php endif; ?>
                    <div class="wpfd-search-date-filter-message-container"></div>
                </div>
                <!-- End of Date filter-->

                <div class="clearfix"></div>
            </div>
            <div class="box-btngroup-below" style="<?php echo esc_attr($showClear); ?>">
                <a href="#" class="btnsearchbelow" type="reset" id="btnReset">
                    <?php esc_html_e('CLEAR ALL', 'wpfd'); ?>
                </a>
            </div>
        </div>
    <?php elseif ($args['catid'] !== '0') : ?>
        <input type="hidden" name="catid" value="<?php echo esc_html($args['catid']); ?>" />
    <?php endif; ?>
    <?php if (isset($filters['exclude']) && $filters['exclude'] !== '0' && !empty($filters['exclude'])) : ?>
        <input type="hidden" name="exclude" value="<?php echo esc_html($filters['exclude']); ?>" />
    <?php endif; ?>
    <?php if (isset($args['file_per_page'])) :?>
        <input type="hidden" name="limit" value="<?php echo esc_html($args['file_per_page']); ?>" />
    <?php endif;
    $themed = '';
    if (isset($args['theme']) && $args['theme'] !== '' && $args['theme'] !== '') :
        $themed = ' themed';
        ?>
        <input type="hidden" name="theme" value="<?php echo esc_html($args['theme']); ?>" />
    <?php endif; ?>
    <?php if (isset($args['theme_column'])) :?>
        <input type="hidden" name="theme_column" value="<?php echo esc_html($args['theme_column']); ?>" />
    <?php endif; ?>
    <?php if (isset($args['show_pagination'])) : ?>
        <input type="hidden" name="show_pagination" value="<?php echo esc_html($args['show_pagination']); ?>" />
        <input type="hidden" name="wpfd_search_page" class="wpfd_search_page" value="1" />
    <?php endif; ?>
    <?php if ($showFilters) : ?>
        <input type="hidden" name="wpfd_search_minimize_filters" class="wpfd_search_minimize_filters" value="1" />
    <?php endif; ?>
    <?php if (isset($args['search_all'])) : ?>
        <input type="hidden" name="wpfd_search_all" value="<?php echo esc_html($args['search_all']); ?>" />
    <?php endif; ?>
        <input type="hidden" name="wpfd_search_in_category_id" class="wpfd_search_in_category_id" value="<?php echo esc_attr($curCatId); ?>" />
        <div id="wpfd-results" class="wpfd-results list-results<?php echo esc_html($themed); ?>">
            <?php if ($show_list_file_tpl !== '') {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc above
                echo $show_list_file_tpl;
            } ?>
        </div>
    </div>
</form>

