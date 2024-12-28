<?php
$globalConfig = get_option('_wpfd_global_config');
$defaultExtensions = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,'
    . 'ppt,pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,'
    . 'aiff,alac,amr,au,cdda,flac,m3u,m4a,m4p,mid,mp3,mp4,mpa,ogg,pac,ra,wav,wma,3gp,asf,avi,flv,m4v,'
    . 'mkv,mov,mpeg,mpg,rm,swf,vob,wmv,css,img';
$allowedExtStr = isset($globalConfig['allowedext']) ? $globalConfig['allowedext'] : $defaultExtensions;
?>
<div class="wpfd-searchbar_wrapper cosllaped">
    <div class="wpfd-search-inner">
        <input type="hidden" class="wpfd-admin-search-file-types" value="<?php echo esc_attr($allowedExtStr); ?>" />
        <div class="wpfd-search-input">
            <!-- Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#888"><path d="M0 0h24v24H0z" fill="none"/><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <div class="search-label"><?php esc_html_e('Search', 'wpfd'); ?></div>
            <input type="search" name="search[query]" placeholder="<?php esc_html_e('Search files...', 'wpfd'); ?>"/>
            <!-- Coslapped icon -->
            <div id="wpfd_search_expand">
                <svg id="wpfd_icon_down" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
                     width="24px" fill="#888">
                    <path d="M0 0h24v24H0z" fill="none"/>
                    <path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/>
                </svg>
                <svg id="wpfd_icon_up" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"
                     fill="#888">
                    <path d="M0 0h24v24H0z" fill="none"/>
                    <path d="M7 14l5-5 5 5z"/>
                </svg>
            </div>
        </div>
        <div class="wpfd-search-advanced">
            <div class="search-row">
                <div class="search-file_icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#888">
                        <path d="M0 0h24v24H0V0z" fill="none"/>
                        <path d="M8 16h8v2H8zm0-4h8v2H8zm6-10H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                    </svg>
                </div>
                <div class="search-label"><?php esc_html_e('File Types', 'wpfd'); ?></div>
                <div class="search-input search-input-file-type">
                    <input type="text" name="search[extension]" placeholder="<?php esc_html_e('.xlsx, .doc...', 'wpfd'); ?>" />
                    <div class="wpfd-admin-search-file-type-message" style="display: none; color: #dc3545"></div>
                </div>
            </div>
            <div class="search-row search-file-tags">
                <div class="search-file_icon">
                    <span class="dashicons dashicons-tag" style="font-size: 24px; color: #888; margin-top: -5px; margin-right: 5px;"></span>
                </div>
                <div class="search-label"><?php esc_html_e('File Tags', 'wpfd'); ?></div>
                <div class="search-input search-input-file-tags">
                    <input type="text" id="search_file_tags" name="search[tags]" class="tagit input_tags" value="" placeholder="<?php esc_html_e('Input tags here...', 'wpfd'); ?>" style="display: none" />
                </div>
            </div>
            <div class="search-row">
                <div class="search-file_icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#888"><g fill="none"><path d="M0 0h24v24H0V0z"/><path d="M0 0h24v24H0V0z" opacity=".87"/></g><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7zm-4 6h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                </div>
                <div class="search-label"><?php esc_html_e('Category', 'wpfd'); ?></div>
                <div class="search-input">
                    <?php wpfdPrintCategories($this->categories); ?>
                </div>
            </div>
            <div class="search-row half">
                <div class="search-file_icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#888"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/></svg>
                </div>
                <div class="search-label"><?php esc_html_e('Creation date', 'wpfd'); ?></div>
                <div class="search-input search-input-create-date">
                    <input type="text" name="search[created_date]" placeholder="<?php esc_html_e('Select created date', 'wpfd'); ?>" />
                    <div class="wpfd-admin-search-create-date-message" style="display: none; color: #dc3545"></div>
                </div>
            </div>
            <div class="search-row half">
                <div class="search-file_icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#888"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zm-2 5h-5v5h5v-5z"/></svg>
                </div>
                <div class="search-label"><?php esc_html_e('Updated date', 'wpfd'); ?></div>
                <div class="search-input search-input-update-date">
                    <input type="text" name="search[updated_date]" placeholder="<?php esc_html_e('Select updated date', 'wpfd'); ?>" />
                    <div class="wpfd-admin-search-update-date-message" style="display: none; color: #dc3545"></div>
                </div>
            </div>
            <div class="search-row half">
                <div class="search-file_icon">
                    <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#888"><g><rect fill="none" height="24" width="24" x="0" y="0"/></g><g><g><path d="M3,4c0-0.55,0.45-1,1-1h2V1H4C2.34,1,1,2.34,1,4v2h2V4z"/><path d="M3,20v-2H1v2c0,1.66,1.34,3,3,3h2v-2H4C3.45,21,3,20.55,3,20z"/><path d="M20,1h-2v2h2c0.55,0,1,0.45,1,1v2h2V4C23,2.34,21.66,1,20,1z"/><path d="M21,20c0,0.55-0.45,1-1,1h-2v2h2c1.66,0,3-1.34,3-3v-2h-2V20z"/><path d="M19,14.87V9.13c0-0.72-0.38-1.38-1-1.73l-5-2.88c-0.31-0.18-0.65-0.27-1-0.27s-0.69,0.09-1,0.27L6,7.39 C5.38,7.75,5,8.41,5,9.13v5.74c0,0.72,0.38,1.38,1,1.73l5,2.88c0.31,0.18,0.65,0.27,1,0.27s0.69-0.09,1-0.27l5-2.88 C18.62,16.25,19,15.59,19,14.87z M11,17.17l-4-2.3v-4.63l4,2.33V17.17z M12,10.84L8.04,8.53L12,6.25l3.96,2.28L12,10.84z M17,14.87l-4,2.3v-4.6l4-2.33V14.87z"/></g></g></svg>
                </div>
                <div class="search-label"><?php esc_html_e('Weight', 'wpfd'); ?></div>
                <div class="search-input search-input-weight-from">
                    <input type="number" name="search[weight][from]" min="0" step="1" pattern="[0-9]" />
                    <select name="search[weight][from_unit]" style="max-width: 70px;">
                        <option value="b">B</option>
                        <option value="kb">Kb</option>
                        <option value="mb">Mb</option>
                        <option value="gb">Gb</option>
                    </select>
                    <div class="wpfd-admin-search-weight-from-message" style="display: none; color: #dc3545"></div>
                </div>
            </div>
            <div class="search-row half">
                <div class="search-file_icon"></div>
                <div class="search-label"><?php esc_html_e('To', 'wpfd'); ?></div>
                <div class="search-input search-input-weight-to">
                    <input type="number" name="search[weight][to]"  min="0" step="1"/>
                    <select name="search[weight][to_unit]" style="max-width: 70px;">
                        <option value="b">B</option>
                        <option value="kb">Kb</option>
                        <option value="mb">Mb</option>
                        <option value="gb">Gb</option>
                    </select>
                    <div class="wpfd-admin-search-weight-to-message" style="display: none; color: #dc3545"></div>
                </div>
            </div>
            <div class="search-row half">
                <div class="search-file_icon">
                    <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#888"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M20,3H4C2.9,3,2,3.9,2,5v14c0,1.1,0.9,2,2,2h16c1.1,0,2-0.9,2-2V5 C22,3.9,21.1,3,20,3z M20,19H4V5h16V19z" fill-rule="evenodd"/><polygon fill-rule="evenodd" points="19.41,10.42 17.99,9 14.82,12.17 13.41,10.75 12,12.16 14.82,15"/><rect fill-rule="evenodd" height="2" width="5" x="5" y="7"/><rect fill-rule="evenodd" height="2" width="5" x="5" y="11"/><rect fill-rule="evenodd" height="2" width="5" x="5" y="15"/></g></g></svg>
                </div>
                <div class="search-label"><?php esc_html_e('Status', 'wpfd'); ?></div>
                <div class="search-input">
                    <input type="checkbox" class="search-waiting-approval-checkbox" name="search[waiting_for_approval]" />
                    <label class="search-waiting-approval-label"><?php esc_html_e('Waiting for approval', 'wpfd'); ?></label>
                </div>
            </div>
            <div class="search-row center submit">
                <button class="ju-button ju-link-button js-search-clear"><?php esc_html_e('Clear', 'wpfd'); ?></button>
                <button class="ju-button ju-v3-material ju-rect-button js-search-submit"><?php esc_html_e('Search', 'wpfd'); ?></button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    (function ($) {
        $(document).on('ready', function () {
            $(document).on('wpfd_context_file_edit', function () {
                $('.wpfd-searchbar_wrapper').removeClass('expanded').addClass('cosllaped');
            });
            $(document).on('click', '#wpfd_search_expand', function (e) {
                if ($('.wpfd-searchbar_wrapper').hasClass('cosllaped')) {
                    $('.wpfd-searchbar_wrapper').removeClass('cosllaped').addClass('expanded');
                    $(this).parents('#pwrapper').addClass('plus');
                } else {
                    $('.wpfd-searchbar_wrapper').removeClass('expanded').addClass('cosllaped');
                    $(this).parents('#pwrapper').removeClass('plus');
                }
            });
            $('#search_file_tags').tagit({
                availableTags: <?php echo $this->allTagsFiles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in view.php ?>,
                placeholderText: wpfd_admin.set_tag_placeholder,
                afterTagAdded: function (e) {
                    e.preventDefault();
                },
                allowSpaces: true
            });
        });
    })(jQuery);
</script>
<?php
/**
 * Render the categories
 *
 * @param array  $categories  Categories array
 * @param string $selected    Selected value
 * @param string $name        Select name
 * @param string $class       Additional class
 * @param string $select_text Placeholder text
 *
 * @return void
 */
function wpfdPrintCategories($categories, $selected = '', $name = 'search[category_id]', $class = '', $select_text = '— Select category —')
{
    $content  = '';
    $content .= '<select name = "' . $name . '" id = "' . $name . '" class="' . $class . '" >';
    $content  .= '<option value ="0">' . $select_text . '</option >';

    if (!empty($categories)) {
        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.is_countableFound -- is_countable() was declared in functions.php
        $catCount = is_countable($categories) ? count($categories) : 0;
        for ($index = 0; $index < $catCount; $index++) {
            $category = $categories[$index];

            if ($index + 1 !== $catCount) {
                $nextlevel = $categories[$index + 1]->level;
            } else {
                $nextlevel = 0;
            }
            $space_str = '';
            if ($nextlevel > $category->level) {
                if (($category->level) > 0) {
                    $space_str = str_repeat('—', $category->level);
                }
            } elseif ($nextlevel === $category->level) {
                $space_str = str_repeat('—', $nextlevel);
            } else {
                if (($category->level) > 0) {
                    $space_str = str_repeat('—', $category->level);
                }
            }
            if ($category->term_id === $selected) {
                $content .= '<option selected ="selected" value = ' .
                            $category->term_id . '>' . $space_str . ' ' . $category->name . '</option >';
            } else {
                $content .= '<option value = ' . $category->term_id . '> ' . $space_str . ' ';
                $content .= $category->name . '</option >';
            }
        }
        $content .= '</select >';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape inside function
        echo $content;
    }
}