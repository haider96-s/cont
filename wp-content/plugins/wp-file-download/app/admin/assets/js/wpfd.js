/**
 * WP File Download
 *
 * @package WP File Download
 * @author Joomunited
 * @version 1.0
 */
// https://gist.github.com/rcmachado/242617
var unserialize = function (serializedString) {
    var str = decodeURI(serializedString);
    var pairs = str.split('&');
    var obj = {}, p, idx, val;
    for (var i=0, n=pairs.length; i < n; i++) {
        p = pairs[i].split('=');
        idx = p[0];

        if (idx.indexOf("[]") == (idx.length - 2)) {
            // Eh um vetor
            var ind = idx.substring(0, idx.length - 2)
            if (obj[ind] === undefined) {
                obj[ind] = [];
            }
            obj[ind].push(p[1]);
        } else {
            obj[idx] = p[1];
        }
    }
    return obj;
};

var availableTags = [];

jQuery.fn.extend({
    toggleText: function (a, b) {
        return this.text(this.text() == b ? a : b);
    },
    outerHTML: function () {
        return (this[0]) ? this[0].outerHTML : '';
    }
});

jQuery(document).ready(function ($) {
    if (typeof (Wpfd) === 'undefined') {
        Wpfd = {};
        Wpfd.filetocat = false;
        Wpfd.catRefTofileId = false;
        Wpfd.directoryUpload = false;
        Wpfd.log = function ($logMessage, $type) {
            if (typeof wpfd_debug !== 'undefined') {
                if (wpfd_debug.debug) {
                    if ($type === undefined || $type === 'log') {
                        console.log('WPFD DEBUG: ' + $logMessage);
                    }
                    if ($type === 'warn') {
                        console.warn('WPFD WARNING: ' + $logMessage);
                    }
                    if ($type === 'error') {
                        console.error('WPFD ERROR: ' + $logMessage);
                    }
                }
            }
        };
        Wpfd.time = function($label) {
            if (wpfd_debug.debug) {
                console.time($label);
            }
        };
        Wpfd.timeEnd = function ($label) {
            if (wpfd_debug.debug) {
                console.timeEnd($label);
            }
        }
    }
    if (typeof wpfd_debug !== 'undefined' && wpfd_debug.debug && wpfd_debug.ajax) {
        $(document).ajaxSend(function (e, jqhxr, settings) {
            if (settings.url.includes('action=wpfd')) {
                Wpfd.time('WPFD AJAX: ' + settings.url);
            }
        });
        $(document).ajaxComplete(function (e, jqhxr, settings) {
            if (settings.url.includes('action=wpfd')) {
                Wpfd.timeEnd('WPFD AJAX: ' + settings.url);
            }
        });
    }
    // Add class for ios
    var isIOS = (function () {
        var iosQuirkPresent = function () {
            var audio = new Audio();

            audio.volume = 0.5;
            return audio.volume === 1;   // volume cannot be changed from "1" on iOS 12 and below
        };

        var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        var isAppleDevice = navigator.userAgent.includes('Macintosh');
        var isTouchScreen = navigator.maxTouchPoints >= 1;   // true for iOS 13 (and hopefully beyond)

        return isIOS || (isAppleDevice && (isTouchScreen || iosQuirkPresent()));
    })();

    if (isIOS) {
        $('#wpfd-core').addClass('wpfd_ios');
    }

    if (typeof (wpfdajaxurl) === 'undefined') {
        wpfdajaxurl = wpfd_var.wpfdajaxurl;
    }

    var wpfdEntityMap = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': '&quot;',
        "'": '&#39;',
        "/": '&#x2F;'
    };

    /**
     * Reload a category preview
     * @param id_category
     * @param id_file
     * @param $ordering
     * @param $ordering_dir
     * @param $ordering
     * @param $ordering_dir
     */
    Wpfd.updatePreview = function(id_category, id_file, $ordering, $ordering_dir) {
        Wpfd.is_search_result = false;
        if (isIOS) {
            $('#wpfd_ios_file_menu').hide();
        }
        if (typeof(id_category) === "undefined" || id_category === null) {
            id_category = checkCateActive(id_category);
            if (typeof(id_category) === 'undefined') {
                $('#insertcategory').hide();
                return;
            }
            $('input[name=id_category]').val(id_category);
        } else {
            id_category = checkCateActive(id_category);
        }

        if ($('li.dd-item[data-id='+id_category+']').data('type') !== '') {
            $('li[data-action="file.create_archive_zip"]').hide();
        } else {
            $('li[data-action="file.create_archive_zip"]').show();
        }

        if ($("#wpreview").length === 0) return;
        loading('#wpreview');

        var url = wpfdajaxurl + "view=files&format=raw&id_category=" + id_category;
        if ($ordering !== null && $ordering !== undefined) {
            url += '&orderCol=' + $ordering;
        }

        if ($ordering_dir === 'asc') {
            url += '&orderDir=desc';
        } else if ($ordering_dir === 'desc') {
            url += url + '&orderDir=asc';
        }
        var oldCategoryAjax = categoryAjax;
        if (oldCategoryAjax !== null) {
            oldCategoryAjax.abort();
        }
        categoryAjax = $.ajax({
            url: url,
            type: "POST",
            data: {}
        }).done(function (data) {
            $('#wpfd_filter_catid').val(id_category);
            $('#preview').contents().remove();
            $(data).hide().appendTo('#preview').fadeIn(200);

            if ($ordering !== null && $ordering !== undefined) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_ordering_file});
            }

            if (wpfd_permissions.can_edit_category) {
                var remote_file = (_wpfd_text('add_remote_file') == '1' && !isCloudCategory()) ? '<a href="" id="add_remote_file" class="ju-button ju-v3-button">' + _wpfd_text('Add remote file') + '</a> ' : '';
                var select_files = '<span id="upload_files_button" class="ju-button ju-v3-button" style="margin-left: 4px">' + _wpfd_text('Select files') + '</span>';
                var select_folders_name = (typeof (_wpfd_text('Select a folder')) !== "undefined") ? _wpfd_text('Select a folder') : 'Select a folder';
                $('<div id="file_dropbox"><span class="message">' + _wpfd_text('Drag & Drop your Document here') + '</span><input class="hide" type="file" id="upload_input" multiple="">' + remote_file + '<span id="upload_button" class="ju-button ju-v3-button">' + select_folders_name + '</span>' + select_files + '</div><div class="clr"></div>').appendTo('#preview');
                $('#add_remote_file').on('click', function (e) {

                    var allowed = wpfd_admin.allowed.split(',');
                    allowed.sort();
                    var allowed_select = '<select id="wpfd-remote-type">';
                    $.each(allowed, function (i, v) {
                        allowed_select += '<option value="' + v + '">' + v + '</option>';
                    });
                    allowed_select += '</select>';
                    bootbox.dialog('<div class="">  ' +
                        '<div class="form-horizontal wpfd-remote-form"> ' +
                        '<div class="control-group"> ' +
                        '<label class=" control-label" for="wpfd-remote-title">'+ _wpfd_text('Title') + '</label> ' +
                        '<div class="controls"> ' +
                        '<input id="wpfd-remote-title" name="wpfd-remote-title" type="text" placeholder="'+ _wpfd_text('Title') + '" class=""> ' +
                        '</div> ' +
                        '</div> ' +
                        '<div class="control-group"> ' +
                        '<label class="control-label" for="wpfd-remote-url">'+ _wpfd_text('Remote URL') + '</label> ' +
                        '<div class="controls">' +
                        '<input id="wpfd-remote-url" name="wpfd-remote-url" type="text" placeholder="'+ _wpfd_text('URL') + '" class=""> ' +
                        '</div> </div>' +
                        '<div class="control-group"> ' +
                        '<label class="control-label" for="wpfd-remote-type">'+ _wpfd_text('File Type') + '</label> ' +
                        '<div class="controls">' +
                        allowed_select +
                        '</div> </div>' +
                        '</div>  </div>',
                        [{
                            "label": _wpfd_text('Save'),
                            "class": "button-primary",
                            "callback": function () {
                                var category_id = $('input[name=id_category]').val();
                                var remote_title = $('#wpfd-remote-title').val();
                                var remote_url = $('#wpfd-remote-url').val();
                                var remote_type = $('#wpfd-remote-type').val();
                                $.ajax({
                                    url: wpfdajaxurl + "task=files.addremoteurl&id_category=" + category_id,
                                    data: {
                                        remote_title: remote_title,
                                        remote_url: remote_url,
                                        remote_type: remote_type
                                    },
                                    type: "POST"
                                }).done(function (data) {
                                    result = $.parseJSON(data);
                                    if (result.response === true) {
                                        updateCatCount(category_id, 1);
                                        Wpfd.updatePreview();
                                    } else {
                                        bootbox.alert(result.response);
                                    }
                                });
                            }
                        }, {
                            "label": _wpfd_text('Cancel'),
                            "class": "s",
                            "callback": function () {

                            }
                        }]
                    );
                    return false;
                });
            }

            $('#preview .restable').restable({
                type: 'hideCols',
                priority: {0: 'persistent', 1: 3, 2: 'persistent'},
                hideColsDefault: [4, 5]
            });

            Wpfd.showhidecolumns();
            $('#preview').sortable('refresh');

            initDeleteBtn();
            $('#preview input[name="restable-toggle-cols"]').click(function (e) {
                setcookie_showcolumns();
            });

            /** Init ordering **/
            $('#preview .wpfd_tbl .head a').click(function (e) {
                e.preventDefault();
                Wpfd.updatePreview(null, null, $(this).data('ordering'), $(this).data('direction'));

                if ($(this).data('direction') === 'asc') {
                    direction = 'desc';
                } else {
                    direction = 'asc';
                }

                $('#ordering option[value="' + $(this).data('ordering') + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                $('#orderingdir option[value="' + direction + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                id_category = $('input[name=id_category]').val();
                $.ajax({
                    url: wpfdajaxurl + "task=category.saveparams&id=" + id_category,
                    type: "POST",
                    data: $('#category_params').serialize()
                }).done(function (data) {
                });
            });

            // initUploadBtn();

            initFiles();

            $('#wpreview').unbind();

            Wpfd.uploader.assignBrowse($('#upload_button'), true);
            Wpfd.uploader.assignBrowse($('#upload_files_button'));
            Wpfd.uploader.assignDrop($('#wpreview'));

            if (typeof(id_file) !== "undefined") {
                $('#preview .file[data-id-file="' + id_file + '"]').addClass('selected');
            } else {
                showCategory();
                if (typeof($ordering) === 'undefined') {
                    loadGalleryParams();
                }
            }
            rloading('#wpreview');
            $('#wpfd-core #preview').trigger('wpfd_preview_updated');

            // Admin pagination
            var admin_pagination_status = localStorage.getItem('wpfd_admin_pagination', false);
            var pagination_category_id  = 0;
            var pagination_page         = 1;
            if (admin_pagination_status) {
                admin_pagination_status = JSON.parse(admin_pagination_status);
                pagination_category_id = parseInt(admin_pagination_status.id_category);
                pagination_page = parseInt(admin_pagination_status.page);
            }

            if (pagination_category_id && (pagination_category_id === id_category) && pagination_page > 1) {
                wpfd_admin_init_pagination($('#wpreview .wpfd-pagination'), pagination_category_id, pagination_page);
            } else {
                wpfd_admin_init_pagination($('#wpreview .wpfd-pagination'), id_category);
            }

            // Admin load more
            wpfd_admin_load_more();
        });
        initEditBtn();
        initDeleteBtn();
    }

    Wpfd.go = function (id_category, id_file = null) {
        resetCategoryParamsCheck();
        Wpfd.updatePreview(id_category, id_file);

        $('#categorieslist li').removeClass('active');
        $('#categorieslist .dd-item[data-id-category=' + id_category + ']').addClass('active');
        var scrollToCategory = $('.dd-item[data-id-category=' + id_category + ']').offset().top;
        $('.scroller_wrapper').animate({scrollTop: scrollToCategory}, "fast");
        var event = $.Event('wpfd_category_click');
        $('#categorieslist .dd-item[data-id-category=' + id_category + ']').find('.dd-content', 0).trigger(event);
    }
    var categoryAjax = null;
    var fileAjax = null;
    var versionAjax = null;
    _wpfd_text = function (text) {
        if (typeof (l10n) !== 'undefined' && l10n.hasOwnProperty(text)) {
            return l10n[text];
        }
        return text;
    };
    var updateCatCount = function (catid, value) {
        var span = $('li[data-id-category="' + catid + '"] .dd-content .countfile .count_badge').first();
        if (typeof span.html() !== 'undefined') {
            var currentValue = span.html().replace(/[^0-9.]/g, "");
            span.html((Number(currentValue) + Number(value)));
        }
    };

    // Load categories col width
    Wpfd.initCategoriesResize = function() {
        $("#wpfd-categories-col").resizable({
            handles: "e",
            minWidth: 230,
        });
    };
    if ($(window).width() > 769) {
        var savedWidth = window.localStorage.getItem('_wpfd_categories_col_width');
        if (savedWidth && $('#wpfd-categories-col').length) {
            $('#wpfd-categories-col').css({width: savedWidth});
            $('#wpfd-categories-col')[0].style['-webkit-flex-basis'] = savedWidth + 'px';
        }
        Wpfd.initCategoriesResize();
    }

    $("#wpfd-categories-col").on('resize', function () {
        var width = parseInt(this.style.width);
        if (width < 230) {
            width = 230;
        }
        // Save the with
        window.localStorage.setItem('_wpfd_categories_col_width', width);

        return this.style['-webkit-flex-basis'] = width + 'px';
    });
    $(window).resize(function() {
        if (($(window).width() <= 769)) {
            if ($('#wpfd-categories-col').hasClass('ui-resizable')) {
                $("#wpfd-categories-col").resizable('destroy');
                $("#wpfd-categories-col").css({width: 230});
                $('#wpfd-categories-col')[0].style['-webkit-flex-basis'] = '230px';
            }
        } else {
            if (!$('#wpfd-categories-col').hasClass('ui-resizable')) {
                Wpfd.initCategoriesResize();
            }
        }
    });
    Wpfd.getCategoriesState = function () {
        var categoriesState = localStorage.getItem('wpfdCategoriesState');
        if (categoriesState) {
            return JSON.parse(categoriesState);
        } else {
            // Get current state then save
            var openCategories = $('li.dd-collapsed');
            var currentState = [];
            $.each(openCategories, function (index, li) {
                currentState.push($(li).attr('data-id-category'));
            });
            localStorage.setItem('wpfdCategoriesState', JSON.stringify(currentState));
            return currentState;
        }
    };
    Wpfd.saveCategoriesState = function() {
        var openCategories = $('li.dd-collapsed');
        var currentState = [];
        $.each(openCategories, function (index, li) {
            currentState.push($(li).attr('data-id-category'));
        });
        localStorage.setItem('wpfdCategoriesState', JSON.stringify(currentState));
    };
    Wpfd.restoreCategoriesState = function() {
        var openCategoriesId = Wpfd.getCategoriesState();
        if (openCategoriesId.length) {
            $.each(openCategoriesId, function (index, catId) {
                $('#categorieslist li[data-id-category="'+catId+'"]').addClass('dd-collapsed');
            });
        }
    };
    Wpfd.deleteCategory = function() {
        var id_category = $('[name=id_category]').val();
        var currentCategory = $('li[data-id-category=' + id_category + ']');
        var typeCloud = currentCategory.data('type') || 'null';
        typeCloud = typeCloud.toLowerCase();
        bootbox.dialog(
            _wpfd_text('Do you want to delete') + ' "' + $('li[data-id-category=' + id_category + '] > .dd-content span.title').text() + '" ?',
            [
                {
                    "label": _wpfd_text('Cancel')
                },
                {
                    "label": _wpfd_text('Confirm'),
                    "callback": function() {
                        var title = $('li[data-id-category=' + id_category + '] span.title');
                        var deleteCatTitle = title.html();
                        var wpfdAjaxurl = wpfdajaxurl + "task=category.delete&id_category=" + id_category;
                        if (typeCloud === 'googledrive') {
                            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDeleteCategory&id_category=" + id_category
                        } else if (typeCloud === 'googleteamdrive') {
                            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDeleteGoogleTeamDriveCategory&id_category=" + id_category
                        } else if (typeCloud === 'dropbox') {
                            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDeleteDropboxCategory&id_category=" + id_category
                        } else if (typeCloud === 'onedrive') {
                            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDeleteOneDriveCategory&id_category=" + id_category
                        } else if (typeCloud === 'onedrive_business') {
                            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDeleteOneDriveBusinessCategory&id_category=" + id_category
                        } else if (typeCloud === 'aws') {
                            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDeleteAwsCategory&id_category=" + id_category
                        } else if (typeCloud === 'nextcloud') {
                            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDeleteNextcloudCategory&id_category=" + id_category
                        }
                        $.ajax({
                            url: wpfdAjaxurl,
                            type: 'POST',
                            data: {security: wpfd_var.wpfdsecurity},
                            beforeSend: function () {
                                title.html(_wpfd_text('Deleting...'));
                            }
                        }).done(function (data) {
                            result = jQuery.parseJSON(data);
                            if (result.response === true) {
                                $('.nested').nestable('remove', id_category, function () {
                                });
                                $('#preview').contents().remove();
                                first = $('#wpfd-categories-col #categorieslist li .dd-content').first();
                                if (first.length > 0) {
                                    first.click();
                                } else {
                                    $('#insertcategory').hide();
                                }
                                $('.gritter-item-wrapper ').remove();

                                // Delete email per category
                                $.ajax({
                                    url: wpfdajaxurl + "task=config.wpfdEmailPerCategoryDeleteRecord&id_category=" + id_category,
                                    type: 'POST',
                                    data: {security: wpfd_var.wpfdsecurity},
                                }).done(function (res) {
                                    $.gritter.add({text: wpfd_admin.msg_remove_category});
                                });
                            } else {
                                title.html(deleteCatTitle);
                                bootbox.alert(result.response);
                            }
                        }).error(function (a, b, c) {
                            bootbox.alert('Error: ' + c + '!! Please check your connection then try again.');
                            title.html(deleteCatTitle);
                        });
                    }
                }
            ]
        );

        return false;
    }
    Wpfd.duplicateCategory = function () {
        var currentCategory = $('#categorieslist li.active');
        var id_category = currentCategory.data('id-category');
        var typeCloud = currentCategory.data('type') || 'null';
        typeCloud = typeCloud.toLowerCase();
        var title = $('li[data-id-category=' + id_category + '] > .dd-content span.title');
        var duplicateCatTitle = title.html();
        if (!id_category) {
            return;
        }
        var wpfdAjaxurl = wpfdajaxurl + "task=category.duplicate&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        if (typeCloud === 'googledrive') {
            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDuplicateCategory&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        } else if (typeCloud === 'googleteamdrive') {
            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDuplicateGoogleTeamDriveCategory&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        } else if (typeCloud === 'dropbox') {
            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDuplicateDropboxCategory&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        } else if (typeCloud === 'onedrive') {
            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDuplicateOneDriveCategory&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        } else if (typeCloud === 'onedrive_business') {
            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDuplicateOneDriveBusinessCategory&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        } else if (typeCloud === 'aws') {
            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDuplicateAwsCategory&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        } else if (typeCloud === 'nextcloud') {
            wpfdAjaxurl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonDuplicateNextcloudCategory&id_category=" + id_category + "&title_category=" + duplicateCatTitle;
        }

        $.ajax({
            url: wpfdAjaxurl,
            type: 'POST',
            data: {security: wpfd_var.wpfdsecurity},
            beforeSend: function () {
                title.html(wpfd_admin.msg_duplicate_category_duplicating);
            }
        }).done(function (data) {
            if (data.success === true) {
                if (data.data.id && $.isNumeric(data.data.id)) {
                    window.localStorage.setItem('wpfdSelectedCatId', data.data.id);
                    window.localStorage.setItem('wpfdDuplicateSuccess', data.data.id);
                    window.location.reload();
                }
            } else {
                $.gritter.add({text: wpfd_admin.msg_duplicate_category_failed});
            }
        }).error(function (a, b, c) {
            bootbox.alert('Error duplicate: ' + c);
            title.html(duplicateCatTitle);
        });
    };
    Wpfd.file_change_icons = function () {
        // Get selected files
        var files = $('#preview .file.selected');

        if (files.length === 0) {
            bootbox.alert(_wpfd_text('Please select file(s)'));
            return false;
        }

        var set = $('#wpfd_icon_set').val();
        var extension = $(files[0]).data('file-ext');
        var iconSet = $('#wpfd_icon_set').val();

        if (iconSet === 'svg') {
            // Load icon params with selected file type
            $.ajax({
                method: 'POST',
                url: wpfdajaxurl + '&task=iconsbuilder.load',
                data: {
                    set: set,
                    extension: extension
                },
                beforeSend: function () {
                },
                success: function (data) {
                    if (typeof data === typeof string) {
                        data = JSON.parse(data);
                    }

                    $('#wpfd_file_change_icons li.wpfd-tab').html(wpfd_admin.quick_file_icon_set_title + ' ' + extension);

                    // Fill editor with current data
                    if (typeof data.datas !== 'undefined') {
                        var supports = ['icon', 'icon-color', 'svg-frame', 'frame-color', 'background-color', 'icon-active', 'frame-active', 'wrapper-active'];
                        Object.keys(data.datas).forEach(function (item) {
                            if ($.inArray(item, supports) !== -1) {
                                if (item === 'icon') {
                                    $('#wpfd_file_change_icons [data-icon-name="' + data.datas[item] + '"]').click();
                                    $('#wpfd_file_change_icons [name=icon]').prop('value', data.datas[item]);
                                } else {
                                    if (item.includes('-active')) {
                                        $('#wpfd_file_change_icons [name=ref_' + item + ']').prop('checked', parseInt(data.datas[item]) === 1 ? true : false);
                                        $('#wpfd_file_change_icons [name=' + item + ']').prop('value', data.datas[item]);
                                    } else {
                                        $('#wpfd_file_change_icons [name=' + item + ']').prop('value', data.datas[item]).trigger('keyup').trigger('change').trigger('input');
                                    }
                                }
                            } else {
                                $('#wpfd_file_change_icons [name=' + item + ']').prop('value', data.datas[item]).trigger('keyup').trigger('change').trigger('input');
                            }
                        });

                        // Init minicolors
                        $('#wpfd_file_change_icons input[name="icon-color"], #wpfd_file_change_icons input[name="frame-color"], #wpfd_file_change_icons input[name="background-color"]').minicolors({
                            position: 'top right',
                            format: 'hex',
                            change: function (value, opacity) {
                            }
                        });

                        Wpfd.initInlineSvg(true);
                        Wpfd.collapse();

                        $('#svg-icons-editor').addClass('first_load');
                        $('#svg-icons-editor.first_load .wpfd-collapse').each(function () {
                            if ($(this).find('.wpfd-collapse--icon').html() === 'expand_less') {
                                $(this).find('.wpfd-card-body').slideToggle();
                                $(this).find('.wpfd-collapse--icon').html('expand_more');
                            }
                        });
                        $('#svg-icons-editor').removeClass('first_load');
                        $('#wpfd_file_change_icons [data-icon-name]').removeClass('selected');
                        $('#wpfd_file_change_icons [data-icon-name='+ data.datas['icon'] +']').addClass('selected').click();
                        $('#wpfd_file_change_icons .wpfd-frame-list li[data-id]').removeClass('selected');
                        $('#wpfd_file_change_icons .wpfd-frame-list li[data-id='+ data.datas['svg-frame'] +']').addClass('selected');

                        $('#wpfd_file_change_icons input[name="ref_icon-active"]').on('change', function () {
                            $('#wpfd_file_change_icons input[name="icon-active"]').val($(this).prop('checked') === true ? 1: 0);
                        });

                        $('#wpfd_file_change_icons input[name="ref_frame-active"]').on('change', function () {
                            $('#wpfd_file_change_icons input[name="frame-active"]').val($(this).prop('checked') === true ? 1: 0);
                        });

                        $('#wpfd_file_change_icons input[name="ref_wrapper-active"]').on('change', function () {
                            $('#wpfd_file_change_icons input[name="wrapper-active"]').val($(this).prop('checked') === true ? 1: 0);
                        });

                        var iconBuilderUrl = $('#wpfd_icons_builder_url').attr('href');
                        var suffix = '&svg_icon_set_editing=' + extension;
                        $('#wpfd_icons_builder_url').attr('href', iconBuilderUrl + suffix);

                        // Cancel editing action
                        $(document).on('click', '.js-modalCancel', function (e) {
                            e.preventDefault();
                            $('#wpfd_file_change_icons').hide();
                        });

                        // Save icon sets
                        $(document).on('click', '.js-saveFileChangeIcons', function (e) {
                            e.preventDefault();

                            $.ajax({
                                method: "POST",
                                url: wpfdajaxurl + 'task=iconsbuilder.saveicon',
                                data: {
                                    icon: btoa(JSON.stringify(Wpfd._getSVGParams())),
                                    extension: extension
                                },
                                success: function (data) {
                                    if (typeof data === typeof string) {
                                        data = JSON.parse(data);
                                    }

                                    $('.gritter-item-wrapper ').remove();
                                    $.gritter.add({text: data.datas.message});
                                    $('#wpfd_file_change_icons').hide();
                                    Wpfd.initInlineSvg(false);
                                    setTimeout(function () {
                                        location.reload();
                                    }, 1500);
                                }
                            });
                        });

                        // Show quick file icon set editing
                        $('#wpfd_file_change_icons').show();
                        $('#svg-icons-editor').fadeIn('fast');
                    }

                    Wpfd.frames();
                },
                complete: function () {
                    // todo: hide loading
                },
            });
        } else {
            // Png quick icon sets
            $('#wpfd_file_change_icons li.wpfd-tab').html(wpfd_admin.quick_file_icon_set_title + ' ' + extension);
            var iconBuilderUrl = $('#wpfd_icons_builder_url').attr('href');
            var suffix = '&png_icon_set_editing=' + extension;
            $('#wpfd_icons_builder_url').attr('href', iconBuilderUrl + suffix);

            if ($('.js-saveFileChangeIcons').length) {
                $('.js-saveFileChangeIcons').remove();
            }

            // Show quick file icon set editing
            $('#png-icons-editor tr[data-extension]').hide();
            $('#wpfd_file_change_icons').show();
            $('#png-icons-editor tr[data-extension='+ extension +']').show();
            $('#png-icons-editor').show();
            // Upload new Png icon
            $(document).on('click', '.js-PngUpload-trigger', {'wrapper': this}, Wpfd._upload);
            // Delete the overide
            $(document).on('click', '.js-PngDelete-trigger', {'wrapper': this}, Wpfd._delete);
            // Close editing model
            $(document).on('click', '.js-modalCancel', function (e) {
                e.preventDefault();
                $('#wpfd_file_change_icons').hide();
            });
        }
        window.Wpfd.destroyContextMenu();

        return false;
    };
    Wpfd._getSVGParams = function () {
        var data = {};
        $('#wpfd_file_change_icons input').each(function () {
            if ($(this).prop('name') !== '' && !$(this).prop('name').includes('ref_')) {
                data[$(this).prop('name')] = $(this).val();
            }
        });
        $('#wpfd_file_change_icons select').each(function () {
            if ($(this).prop('name') !== '' && !$(this).prop('name').includes('ref_')) {
                data[$(this).prop('name')] = $(this).val();
            }
        });

        Object.keys(data).forEach(function (key, index) {
            if (key.match(/(-color|_color|_start|_end|_solid)$/) && (data[key].toString() === '' || data[key].toString() === 'transparent')) {
                data[key] = 'rgba(0,0,0,0)';
            }
        });

        return data;
    };
    Wpfd.frames = function () {
        // Frame change
        $(document).on('click', '.wpfd-frame-list li', function (e) {
            $('[name=svg-frame]').val($(this).data('id'));
            $('li', $(this).parent()).removeClass('selected');
            $(this).addClass('selected');
        });
    };
    Wpfd.icons = function () {
        $('#wpfd_file_change_icons [data-icon-name]').on('click', function (e) {
            var iconName = $(this).data('icon-name');
            var svgIconContent = $(this).html();
            $('#wpfd_file_change_icons [data-icon-name]').removeClass('selected');
            $(this).addClass('selected');
            $('#wpfd_file_change_icons .svg-icon-selected > svg').remove();
            $('#wpfd_file_change_icons .svg-icon-selected > img').remove();
            $('#wpfd_file_change_icons .svg-icon-selected').prepend($(svgIconContent));
            $('#wpfd_file_change_icons .svg-icon-selected > svg').attr('width', '60');
            $('#wpfd_file_change_icons .svg-icon-selected > svg').attr('height', '60');

            // Change icon input
            $('#wpfd_file_change_icons [name=icon]').val(iconName).trigger('change');
        });

        $('#wpfd_file_change_icons .selected[data-icon-name]').click();
    };
    Wpfd.initInlineSvg = function (bind = true) {
        if (typeof (inlineSVG) === 'undefined') {
            console.log('inlineSVG missing! Some svg feature may not working!');
            return false;
        }
        inlineSVG.init({
            svgSelector: 'img.wpfdsvg', // The class attached to all images that should be inlined
            initClass: 'js-inlinesvg', // Class added to <html>
        }, function () {
            console.log('All SVGs inlined!');
            if (bind) {
                Wpfd.icons();
                Wpfd.frames();
            }
        });
    };
    Wpfd.collapse = function () {
        var collapseItems = $('#wpfd_file_change_icons .wpfd-collapse');
        $('#wpfd_file_change_icons .wpfd-collapse--icon, #wpfd_file_change_icons .card-title').unbind('click').on('click', function (e) {
            $(this).parents('.wpfd-collapse').find('.wpfd-card-body').slideToggle('slow', function () {
                $(this).parents('.wpfd-collapse').find('.wpfd-collapse--icon').toggleText('expand_more', 'expand_less');
            });
        });
    };
    Wpfd._delete = function (e) {
        e.preventDefault();
        var $this = $(this);
        var ext = $this.data('extension');
        var iconSet = $this.data('icon-set');
        $('#wpfd_file_change_icons').hide();

        bootbox.confirm(
            '<h2>'+ wpfd_admin.msg_quick_file_icon_set_deleting_title +'</h2><br /><span>'+ wpfd_admin.msg_quick_file_icon_set_deleting_content +'</span>',
            function (result) {
                if (result) {
                    $.ajax({
                        method: 'POST',
                        url: wpfdajaxurl + 'task=iconsbuilder.restore',
                        data: {
                            set: iconSet,
                            extension: ext
                        },
                        success: function (data) {
                            if (typeof data === typeof string) {
                                data = JSON.parse(data);
                            }

                            if (data.response === true) {
                                $('.gritter-item-wrapper').remove();
                                $.gritter.add({text: wpfd_admin.msg_quick_file_icon_set_deleting_success});
                                setTimeout(function () {
                                    location.reload();
                                }, 1500);
                            } else {
                                $('.gritter-item-wrapper').remove();
                                $.gritter.add({text: wpfd_admin.msg_quick_file_icon_set_deleting_failed});
                            }
                        },
                        error: function (error) {
                            $.gritter.add({text: error.datas.message});
                        },
                    });
                } else {
                    $('#wpfd_file_change_icons').show();
                }
            }
        );
    };
    Wpfd._upload = function (e) {
        e.preventDefault();
        var $this = $(this);
        var $wrapper = e.data.wrapper;
        var ext = $this.data('extension');
        var iconSet = $this.data('icon-set');
        $wrapper._openFileDialog(function (files) {
            $wrapper._doUpload(files, ext, iconSet, $this);
        });
    };
    Wpfd._openFileDialog = function (cb) {
        $('<input type="file" accept="image/png" />')
            .on('change', function () {
                cb(this.files);
            }).click();
    };
    Wpfd._doUpload = function (files, ext, set, $button) {
        var formdata = false;
        if (window.FormData) {
            formdata = new FormData();
        }
        formdata.append('image', files[0]);
        formdata.append('extension', ext);
        formdata.append('set', set);

        $.ajax({
            method: 'POST',
            url: wpfdajaxurl + 'task=iconsbuilder.upload',
            data: formdata,
            processData: false,
            contentType: false,
            success: function (data) {
                if (typeof data === typeof string) {
                    data = JSON.parse(data);
                }

                $('#wpfd_file_change_icons').hide();

                if (data.response === true) {
                    $('.gritter-item-wrapper').remove();
                    $.gritter.add({text: wpfd_admin.msg_quick_file_icon_set_upload_success});
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                } else {
                    $('.gritter-item-wrapper').remove();
                    $.gritter.add({text: wpfd_admin.msg_quick_file_icon_set_upload_failed});
                    setTimeout(function () {
                        $('#wpfd_file_change_icons').show();
                    }, 1500);
                }
            },
            error: function (error) {
                $('.gritter-item-wrapper').remove();
                $.gritter.add({text: error.datas.message});
            },
        });
    };
    var wpfd_on_new_category_click = 0;
    /* Title edition */
    Wpfd.initMenu = function() {
        /* Set the active category on menu click */
        $('#categorieslist .dd-content').unbind('click').on('click', function (e) {
            var $this = $(this);

            if (e.button === 0 && !$(this).parent().hasClass('new-category')) {
                e.preventDefault();
                return;
            }
            e.stopPropagation();

            var id_category = $this.parent().data('id-category');
            // Detect double click
            wpfd_on_new_category_click++;
            if (wpfd_on_new_category_click === 1) {
                setTimeout(function () {
                    if (wpfd_on_new_category_click === 1) {
                        // Check file editing is open
                        if ($('.wpfd_row.editor').length) {
                            if (file_params_change) {
                                // Prompt for unsave changes
                                bootbox.confirm(
                                    wpfd_admin.msg_prompt_file_params_changes_not_saved,
                                    function (result) {
                                        if (result) {
                                            loadCategoryOnClick(id_category, true);
                                        }
                                    }
                                );
                            } else {
                                loadCategoryOnClick(id_category, true);
                            }
                        } else {
                            // Close other folder settings
                            if ($('#rightcol').hasClass('opened')) {
                                if (category_params_change) {
                                    // Prompt for unsave changes
                                    bootbox.confirm(
                                        wpfd_admin.msg_prompt_category_params_changes_not_saved,
                                        function (result) {
                                            if (result) {
                                                loadCategoryOnClick(id_category, true);
                                            }
                                        }
                                    );
                                } else {
                                    loadCategoryOnClick(id_category, true);
                                }
                            } else {
                                loadCategoryOnClick(id_category);
                            }
                        }
                    } else {
                        // Open rename prompt
                        Wpfd.renameFolderPrompt(id_category);
                    }
                    wpfd_on_new_category_click = 0;
                }.bind(id_category, category_params_change), 300);
            }

            return false;
        });

        // Restore categories state
        Wpfd.restoreCategoriesState();
        // Init categories state handler
        $('#categorieslist').on('click', 'button', function(e) {
            setTimeout(function() {Wpfd.saveCategoriesState();}, 100);
        });

    };
    // save temp
    Wpfd.saveTemp = function() {
        id_category = $('input[name=id_category]').val();
        $.ajax({
            url: wpfdajaxurl + "task=category.saveparams&id=" + id_category,
            type: "POST",
            data: $('#category_params').serialize()
        }).done(function (data) {
        });
    };
    var selectedFiles = [];
    // file action
    Wpfd.renameFolderPrompt = function (category_id = null) {
        var id_category,
            category_title,
            rename_prompt;
        if (category_id !== null && $('#categorieslist li.dd-item[data-id-category="' + category_id + '"]').length) {
            id_category = category_id;
            category_title = $('#categorieslist li.dd-item[data-id-category="' + category_id + '"] > .dd-content .title').text();
        } else {
            var selectedCategory = $('#categorieslist li.dd-item.active');
            id_category = selectedCategory.data('id-category');
            category_title = $('#categorieslist li.dd-item.active > .dd-content .title').text();
        }

        category_title = wpfdEscapeTitle(category_title);
        rename_prompt = bootbox.prompt('New category name: ' + category_title, 'Cancel', 'Save', function (new_title) {
            if (new_title === '' || new_title === null || typeof new_title === "undefined") {
                return false;
            }

            $.ajax({
                url: wpfdajaxurl + "task=category.setTitle",
                data: {id_category: id_category, title: new_title},
                type: "POST"
            }).done(function (data) {
                result = jQuery.parseJSON(data);
                if (result.response === true) {
                    $('[data-id-category="' + id_category + '"] > .dd-content > .t > .title').text(new_title);
                    $('.gritter-item-wrapper ').remove();
                    $.gritter.add({text: wpfd_admin.msg_edit_category});
                    return true;
                }

                $('[data-id-category="' + id_category + '"] > .dd-content > .t > .title').text(category_title);

                return false;
            });
        }, category_title);
        // Select all input after bootbox shown
        rename_prompt.find("input[type=text]").select();
        // Prevent Enter event
        rename_prompt.find("input[type=text]").on('keypress', function(e) {
            if (e.keyCode === 13) { // Enter
                e.preventDefault();
                rename_prompt.find('.button-primary').trigger('click');
                return false;
            }

            if (e.keyCode === 27) { // Escape
                e.preventDefault();
                rename_prompt.modal('hide');
                return false;
            }
        });
    }
    var wpfdArchiveInitialized = false;
    Wpfd.submitbutton = function ($task) {
        switch ($task) {
            case 'files.copyfile':
            case 'files.movefile':
                if ($('#preview .file.selected').length === 0) {
                    bootbox.alert(_wpfd_text('Please select file(s)'));
                    return;
                }
                lastAction = $task;
                copySourceCat = $('#categorieslist li.active').data('id-category');
                selectedFiles = [];
                $('#preview .file.selected').each(function (index) {
                    selectedFiles.push($(this).data('id-file'));
                });
                if (lastAction === 'files.copyfile') {
                    //do nothing
                } else {
                    $('#preview .file').removeAttr('style');
                    $('#preview .file.selected').addClass('cuted');
                    if ($('#preview .file.selected').prop('tagName').toLowerCase() === 'tr') {
                        $('#preview .file.selected').css('opacity', '.7');
                    } else {
                        $('#preview .file .overlay').remove();
                        $('#preview .file.selected').append('<div class="overlay"></div>');
                    }
                }

                var numberfiles = '(' + $('#preview .file.selected').length + ')';
                var type = 'cut';
                if ($task === 'files.copyfile') {
                    type = 'copy';
                } else if ($task === 'files.movefile') {
                    type = 'cut';
                }
                $('.clipboard-count').html(numberfiles);
                $('[data-action="file.paste"]').removeClass('menu-item-disabled');
                break;
            case 'files.paste':
                if (selectedFiles.length === 0) {
                    bootbox.alert(_wpfd_text('There is no copied/cut files yet'));
                }
                cat_target = $('#categorieslist li.active').data('id-category');
                if (cat_target !== copySourceCat) {
                    countFiles = selectedFiles.length;
                    iFile = 0;
                    while (selectedFiles.length > 0) {
                        id_file = selectedFiles.pop();
                        id_file = encodeURIComponent(id_file);
                        $.ajax({
                            url: wpfdajaxurl + "task=" + lastAction + "&id_category=" + cat_target + '&active_category=' + copySourceCat + '&id_file=' + id_file,
                            type: "POST",
                            data: {}
                        }).done(function (data) {
                            iFile++;
                            if (iFile === countFiles) {
                                if (lastAction === 'files.copyfile') {
                                    $('.gritter-item-wrapper ').remove();
                                    $.gritter.add({text: wpfd_admin.msg_copy_files});
                                } else {
                                    updateCatCount(copySourceCat, 0 - iFile);
                                    $('.gritter-item-wrapper ').remove();
                                    $.gritter.add({text: wpfd_admin.msg_move_files});
                                }
                                updateCatCount(cat_target, iFile);
                                Wpfd.updatePreview(cat_target);
                            }
                        });
                    }
                }
                $('.clipboard-count').html('');
                $('[data-action="file.paste"]').addClass('menu-item-disabled');
                break;
            case 'files.selectall':
                $('.file').addClass('selected');
                break;
            case 'files.uncheck':
                $('.file').removeClass('selected');
                break;
            case 'files.delete':
                bootbox.dialog(
                    wpfd_admin.msg_ask_delete_files,
                    [
                        {
                            "label": _wpfd_text('Cancel')
                        },
                        {
                            "label": _wpfd_text('Confirm'),
                            "callback": function () {
                                sourceCat = $('#categorieslist li.active').data('id-category');
                                selectedFilesInfos = [];
                                $('#preview .file.selected').each(function (index) {
                                    selectedFilesInfos.push({
                                        'fileId': $(this).data('id-file'),
                                        'catIdRef': $(this).data('catid-file'),
                                        'isWoo': $(this).hasClass('isWoocommerce')
                                    });
                                });

                                while (selectedFilesInfos.length > 0) {
                                    filesInfos = selectedFilesInfos.pop();
                                    id_file = encodeURIComponent(filesInfos.fileId);
                                    catIdRef = filesInfos.catIdRef;
                                    confirmDeleteWooFiles = false;

                                    if (filesInfos.isWoo) {
                                        if (confirmDeleteWooFiles || confirm('This file linked to a product, are you sure delete this?')) {
                                            confirmDeleteWooFiles = true;
                                        } else {
                                            continue;
                                        }
                                    }

                                    $.ajax({
                                        url: wpfdajaxurl + "task=file.delete&id_file=" + id_file + "&id_category=" + sourceCat + "&catid_file_ref=" + catIdRef,
                                        type: "POST",
                                        data: {},
                                        success: function (data) {
                                            var res = JSON.parse(data);
                                            if (res.response) {
                                                updateCatCount(sourceCat, -1);
                                            }
                                        }
                                    });

                                    $('.file[data-id-file="' + decodeURIComponent(id_file) + '"]').fadeOut(500, function () {
                                        $(this).remove();
                                    });
                                }
                                $.gritter.add({text: wpfd_admin.msg_remove_files});
                            }
                        }
                    ]);
                break;
            case 'files.download':
                $('#preview .file.selected').each(function (index) {
                    var href = $(this).data('linkdownload');

                    $.ajax({
                        url: href,
                        method: 'OPTIONS',
                        statusCode: {
                            403: function() {
                                $.gritter.add('Not authorized!');
                            }
                        },
                        success: function(html) {
                            var link = document.createElement("a");
                            link.download = '';
                            link.href = href;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            $(link).remove();
                        }
                    });

                });
                break;
            case 'files.publish':
                var workingCategory = $('#categorieslist li.active').data('id-category');
                var fileSelected = $('#preview .file.selected');
                var needPublicSelected   = [];
                if ($('#preview .file.selected').length === 0) {
                    return;
                }
                // Publish files
                fileSelected.each(function () {
                    needPublicSelected.push($(this).data('id-file'));
                });
                if (needPublicSelected.length) {
                    $.ajax({
                        url: wpfdajaxurl + "task=files.publish",
                        type: "POST",
                        data: {
                            wpfd_publish_selected_files: needPublicSelected,
                            wpfd_working_category_id: workingCategory
                        },
                        success: function (data) {
                            if (data.success === true) {
                                $.gritter.add({text: wpfd_admin.msg_published_files});
                                //Wpfd.updatePreview(workingCategory);
                                fileSelected.each(function() {
                                    $(this).removeClass('unpublished');
                                    $(this).removeClass('isPending');
                                    $(this).find('.wpfd-pending-btn').remove();
                                    $(this).find('.wpfd-svg-icon-visibility-off').remove();
                                });
                            }
                        }
                    });
                }
                break;
            case 'files.unpublish':
                var selectedCategory = $('#categorieslist li.active').data('id-category');
                var fileSelected = $('#preview .file.selected');
                var fileList         = [];
                if ($('#preview .file.selected').length === 0) {
                    return;
                }
                // Unpublish files
                fileSelected.each(function () {
                    fileList.push($(this).data('id-file'));
                });
                if (fileList.length) {
                    $.ajax({
                        url: wpfdajaxurl + "task=files.unpublish",
                        type: "POST",
                        data: {
                            wpfd_unpublish_selected_files: fileList,
                            wpfd_selected_category_id: selectedCategory
                        },
                        success: function (data) {
                            if (data.success) {
                                $.gritter.add({text: wpfd_admin.msg_unpublished_files});
                                // Wpfd.updatePreview(selectedCategory);
                                fileSelected.each(function() {
                                    $(this).addClass('unpublished');
                                    if ($('.filestatus-wrapper', $(this)).length) {
                                        $('.filestatus-wrapper', $(this)).append('<i title="Unpublished" class="wpfd-svg-icon-visibility-off"></i>');
                                    } else {
                                        $('.fileicon-wrapper', $(this)).append('<div class="filestatus-wrapper"><i title="Unpublished" class="wpfd-svg-icon-visibility-off"></i></div>');
                                    }
                                });
                            }
                        }
                    });
                }

                break;
            case 'files.edit':
                var fileDiv = $('.file.selected').first();
                // Checking current file
                if (fileDiv.hasClass('editing')) {
                    $('.wpfd_row').removeClass('editing');
                    $('.wpfd_row.editor').slideUp('fast', function() { $(this).remove(); });
                    return;
                }
                // Destroy all other editor
                $('.wpfd_row').removeClass('editing');
                $('.wpfd_row.editor').slideUp('fast', function() { $(this).remove(); });

                $("#preview").sortable("disable");
                $("#preview").disableSelection();

                // Insert params to next row
                $('<div class="wpfd_row editor"><div class="wpfd_cell span_full dploadingcontainer"><div class="dploading"></div></div></div></div>').insertAfter(fileDiv);
                // Load file params?
                id_file = fileDiv.attr('data-id-file'),
                    catid_file = fileDiv.data('catid-file'),
                    title = fileDiv.find('.title').first().text(),
                    is_remoteurl = fileDiv.hasClass('is-remote-url'),
                    linkdownload = fileDiv.data('linkdownload');
                fileDiv.addClass('editing');
                Wpfd.catRefTofileId = false;

                var fileInfo = [];
                fileInfo.push({'fileId': id_file, 'catid': catid_file, 'title': title});

                var oldFileAjax = fileAjax;
                if (oldFileAjax !== null) {
                    oldFileAjax.abort();
                }
                fileAjax = $.ajax({
                    url: wpfdajaxurl + "task=file.display",
                    type: 'POST',
                    data: {fileInfo: fileInfo, security: wpfd_var.wpfdsecurity}
                }).success(function (data) {
                    $('.wpfd_row.editor .span_full').append(data);
                    loadVersions();
                    versionUploaderInit(id_file, catid_file);

                    if (is_remoteurl) {
                        $('.wpfdparams').find('.wpfd-hide').removeClass('wpfd-hide');
                    }
                    fileEditorInit();
                    resetFileParamsCheck();
                    saveFileParamsCheck();
                    rloading('.wpfd_row.editor');

                    if ($('#preview #fileversion').length) {
                        var dropOverlay = $('<div id="wpfd-file-version-drop-overlay" class="wpfd-file-version-drop-overlay" style="display: none; font-size: 14px;"><div class="wpfd-overlay-inner" style="font-size: 14px">' + wpfd_admin.msg_upload_drop_file + '</div></div>');
                        $('#preview #fileversion').prepend(dropOverlay);
                    }
                });
                $(document).trigger('wpfd_context_file_edit');
                break;
            case 'files.create_archive_zip':
                if (!wpfdArchiveInitialized) {
                    wpfd_archive_zip_creation.init();
                    wpfdArchiveInitialized = true;
                } else {
                    wpfd_archive_zip_creation.onCreateZipClick({that: wpfd_archive_zip_creation});
                }
                break;
            case 'files.change_icon':
                Wpfd.file_change_icons();
                break;
            case 'files.tags':
                Wpfd.file_set_tags();
                break;
            case 'folder.new':
                var parent_id = 0;
                var type = '';
                var selectedCategory = $('#categorieslist li.dd-item.active');

                if (!selectedCategory.length) {
                    return;
                }
                parent_id = selectedCategory.data('id-category');
                type = selectedCategory.data('type');

                Wpfd.createCategory(parent_id, type, true);
                break;
            case 'folder.synchronize':
                var selectedCategory = $('#categorieslist li.dd-item.active');
                var id_category = selectedCategory.data('id-category');
                var parent_id = selectedCategory.data('parent-id');
                var type = selectedCategory.data('type');

                if (!type) {
                    return;
                }

                // Send sync request
                $.ajax({
                    url: ajaxurl + '?action=add_synchronize',
                    method: 'POST',
                    data: {id: id_category, parent_id: parent_id, type: type},
                    success: function(response) {
                        $('.gritter-item-wrapper ').remove();
                        $.gritter.add({text: wpfd_admin.msg_add_synchronize});
                    }
                });
                break;
            case 'folder.rename':
                Wpfd.renameFolderPrompt();
                break;
            case 'folder.refresh':
                Wpfd.updatePreview();
                break;
            case 'folder.delete':
                Wpfd.deleteCategory();
                break;
            case 'folder.copy_shortcode':
                var shortcode = '[wpfd_category id="{%s}"]';
                var selectedCategory = $('#categorieslist li.dd-item.active');
                var id_category = selectedCategory.data('id-category');
                if (id_category) {
                    shortcode = shortcode.replace('{%s}', id_category);
                    var inputlink = document.createElement("input");
                    inputlink.setAttribute("value", shortcode);
                    document.body.appendChild(inputlink);
                    inputlink.select();
                    document.execCommand("copy");
                    document.body.removeChild(inputlink);
                    $.gritter.add({text: wpfd_admin.msg_shortcode_copied_to_clipboard});
                }
                break;
            case 'folder.change_color':
                // Show the color selector
                $('.wpfd_color_selection').fadeIn();
                break;
            case 'folder.settings':
                if (!$('#rightcol').hasClass('opened')) {
                    $('#rightcol').addClass('opened');
                }
                break;
            case 'folder.duplicate':
                Wpfd.duplicateCategory();
                break;
            default:
                break;
        }
    };
    // set user value on user modal
    $('.button-select-user').on('click', function () {
        user_name = [];
        user_id = [];
        var type = $('.fieldtype').val();
        var cataction = $('.cataction').val();
        user_id_str = window.parent.jQuery('.' + type).val();
        if (user_id_str) {
            user_id = user_id_str.split(",");
            user_name = window.parent.jQuery('.' + type + '-name').val().split(",");
        }

        var $this = $(this);
        var username = $this.data('name').toString();
        var uservalue = $this.data('user-value').toString();
        if (user_id.indexOf(uservalue) === -1) {
            user_id.push(uservalue);
            user_name.push(username);
        }
        if (!cataction) {
            window.parent.jQuery('.' + type + '-name' + '.file').val(user_name.toString());
            window.parent.jQuery('.' + type + '.file').val(user_id.toString());
        } else {
            window.parent.jQuery('.' + type + '-name').val(username);
            window.parent.jQuery('.' + type).val(uservalue);
        }
        window.parent.tb_remove();
    });

    $('.btn-insert-user').on('click', function () {
        user_name = [];
        user_id = [];
        check_selected = 0;
        $('input:checkbox[name=cb-selected]:checked').each(function () {
            var $this = $(this);
            user_id.push($this.val());
            user_name.push($('a[data-user-value="' + $this.val() + '"]').data('name'));
            check_selected = check_selected + 1;
        });
        if (!check_selected) {
            return;
        }
        var type = $('.fieldtype').val();
        url = 'admin.php?page=wpfd&task=user.display&noheader=true&fieldtype=field-user-input&listCanview=' + user_id.toString() + '&TB_iframe=true&height=100vh&max-height=400px&width=100vw&max-width=800px';
        window.parent.jQuery('.' + type + '-name' + '.file').val(user_name.toString());
        window.parent.jQuery('.field-user-wrapper .button-select.file').attr("href", url);
        window.parent.jQuery('.' + type + '.file').val(user_id.toString());
        window.parent.tb_remove();
    });

    var scrollerTimer;
    /**
     * Init sortable files
     * Save order after each sort
     */
    $('#preview').sortable({
        placeholder: 'wpfd_row span-full highlight file',
        revert: true,
        distance: 5,
        items: ".file",
        tolerance: "pointer",
        appendTo: "body",
        cursorAt: {top: 0, left: 0},
        helper: function (e, item) {
            var fileext = $(item).find('.ext').text();
            var filename = $(item).find('.title').text() + "." + fileext;
            var count = $('#preview').find('.file.selected').length;
            if (count > 1) {
                return $("<span id='file-handle' class='wpfd_draged_file ui-widget-header' ><div class='ext "+fileext+"'><span class='txt'>"+fileext+"</span></div><div class='filename'>" + filename + "...</div><span class='fCount'>" + count + "</span></div>");
            } else {
                return $("<div id='file-handle' class='wpfd_draged_file ui-widget-header' ><div class='ext "+fileext+"'><span class='txt'>"+fileext+"</span></div><div class='filename'>" + filename + "</div></div>");
            }
        },
        sort: function (e, item) {
            if (scrollerTimer) {
                clearInterval(scrollerTimer);
            }

            if (typeof jQuery.fn.mCustomScrollbar == 'undefined') {
                return;
            }

            var wrapper = $('#wpfd-core #pwrapper .wpfd_center');
            var wrapper_height = wrapper.height();
            var sign = '+';
            var triggerScroll = false;
            var itemPositionWithWrapper = item.position.top - wrapper.offset().top;

            if (itemPositionWithWrapper > 0 && itemPositionWithWrapper < 50) {
                sign = '+';
                triggerScroll = true;
            }

            if (itemPositionWithWrapper > 0 && itemPositionWithWrapper < wrapper_height && itemPositionWithWrapper > wrapper_height - 100) {
                sign = '-';
                triggerScroll = true;
            }

            scrollerTimer = setInterval(function() {
                if (triggerScroll) {
                    wrapper.mCustomScrollbar('scrollTo', [sign + '=200', 0], {scrollInertia: 300,scrollEasing: "linear"});
                }
            }, 50);
        },
        update: function (event, ui) {
            if (scrollerTimer) {
                clearInterval(scrollerTimer);
            }
            var json = '';
            var active_category_id = $('input[name=id_category]').val() ? $('input[name=id_category]').val() : 0;
            $.each($('#preview .file'), function (i, val) {
                if (json !== '') {
                    json += ',';
                }
                json += '"' + i + '":"' + $(val).data('id-file') + '"';
            });
            json = '{' + json + '}';
            if (Wpfd.filetocat) {
                // Remove selected files
                ui.item.remove();
                return false;
            }
            $.ajax({
                url: wpfdajaxurl + "task=files.reorder&order=" + json + "&category_id=" + active_category_id,
                type: "POST",
                data: {}
            }).done(function (data) {
                $('.gritter-item-wrapper ').remove();
                if (!Wpfd.filetocat) {
                    $.gritter.add({text: wpfd_admin.msg_ordering_file2});
                }
                if ($('#ordering').val() !== 'ordering') {
                    $('#ordering option[value="ordering"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                    $('#orderingdir option[value="asc"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                    id_category = $('input[name=id_category]').val();
                    $.ajax({
                        url: wpfdajaxurl + "task=category.saveparams&id=" + id_category,
                        type: "POST",
                        data: $('#category_params').serialize()
                    }).done(function (data) {
                        resetCategoryParamsCheck();
                        saveCategoryParamsCheck();
                    });
                }
            });
        },
        /** Prevent firefox bug positionnement **/
        start: function (event, ui) {
            $(ui.helper).css('width', 'auto');

            var userAgent = navigator.userAgent.toLowerCase();
            if (ui.helper !== "undefined" && userAgent.match(/firefox/)) {
                ui.helper.css('position', 'absolute');
            }
            ui.placeholder.html("<td colspan='8'></td>");

        },
        out: function (event, ui) {
            ui.placeholder.hide();
            $('.file.selected').hide();
        },
        over: function(event, ui) {
            ui.placeholder.show();
            if (!Wpfd.filetocat) {
                $('.file.selected').show();
            }
        },
        stop: function (event, ui) {

            $('#file-handle').removeClass('wpfdzoomin');
            if (!Wpfd.filetocat) {
                ui.placeholder.show();
                $('.file.selected').show();
            }
        },
        beforeStop: function (event, ui) {
            if (scrollerTimer) {
                clearInterval(scrollerTimer);
            }
            var userAgent = navigator.userAgent.toLowerCase();
            if (ui.offset !== "undefined" && userAgent.match(/firefox/)) {
                ui.helper.css('margin-top', 0);
            }
        },
        beforeRevert: function (e, ui) {
            if ($('#categorieslist .wpfddropzoom').length > 0) {
                return false; // copy/move file
            }
            $('#file-handle').addClass('wpfdzoomin');
            $('#file-handle').fadeOut();
            return true;
        }
    });
    $('#preview').disableSelection();

    /*Color field*/
    initColor();
    /* Load category */
    Wpfd.updatePreview();

    var wpfd_category_click = 0;

    $(document).on('click', '.category-settings-hide', function(e) {
        // Close other folder settings
        if ($('#rightcol').hasClass('opened')) {
            if (category_params_change) {
                // Prompt for unsave changes
                bootbox.confirm(
                    wpfd_admin.msg_prompt_category_params_changes_not_saved,
                    function (result) {
                        if (result) {
                            $('#rightcol').toggleClass('opened');
                        }
                    }
                );
            } else {
                $('#rightcol').toggleClass('opened');
            }
        }
    }).on('click', '.category-settings-submit-hide', function(e) {
        $('#galleryparams .wpfdparams button[type="submit"]').first().click();
        $('#rightcol').toggleClass('opened');
    });
    function loadCategoryOnClick(id_category, closeRightPanel = false) {
        if (closeRightPanel) {
            // Close without save
            $('#rightcol').removeClass('opened');
        }

        // Reset stored params
        resetCategoryParamsCheck();
        if (Wpfd.catRefTofileId) {
            Wpfd.updatePreview(id_category, Wpfd.catRefTofileId);
            Wpfd.catRefTofileId = false;
        } else {
            Wpfd.updatePreview(id_category);
            Wpfd.catRefTofileId = false;
        }

        $('#categorieslist li').removeClass('active');
        $('#categorieslist .dd-item[data-id-category=' + id_category + ']').addClass('active');
        var event = $.Event('wpfd_category_click');
        $('#categorieslist .dd-item[data-id-category=' + id_category + ']').find('.dd-content', 0).trigger(event);
    }
    /* Load nestable */
    wpfdLoadNestable();

    /* init menu actions */
    Wpfd.initMenu();
    Wpfd.caculateTree = function () {

        var padding = 10;
        // Reset categories level
        var items = $('.nested').nestable('toArray');
        var $categorytree = $('#categorieslist');
        for (var item of items) {
            var $category = $categorytree.find('.dd-item[data-id="' + item.id + '"]');
            var level = parseInt(item.depth - 1);
            $category.data('level', level);
            var liPadding = level * padding;
            var contentMarginLeft = (liPadding + 20) * -1;
            var contentPaddingLeft = liPadding + 67;
            var olMarginLeft = liPadding * -1;

            // Current li
            if (level > 0) {
                $category.css({paddingLeft: liPadding + 'px'});
                $category.children('.dd-content').css({
                    marginLeft: contentMarginLeft + 'px',
                    paddingLeft: contentPaddingLeft + 'px'
                });
                if ($category.children('old').length) {
                    $category.children('old').css({marginLeft: olMarginLeft + 'px'});
                }
            } else if (level === 0) {
                $category.removeAttr('style');
                $category.children('.dd-content').removeAttr('style');
                if ($category.children('old').length) {
                    $category.children('old').removeAttr('style');
                }
            }
        }
    };
    // init categories items
    var runOnce = 0;
    catDroppable = function () {
        $("#categorieslist .dd-handle").droppable({
            accept: '.file',
            revert: 'valid',
            hoverClass: "dd-content-hover",
            tolerance: "pointer",
            //greedy: true,
            over: function (event, ui) {
                $(event.target).closest('li').addClass("wpfddropzoom");
                runOnce = 0;
            },
            out: function (event, ui) {
                $(event.target).closest('li').removeClass("wpfddropzoom");
            },
            drop: function (event, ui) {
                $(this).addClass("ui-state-highlight");
                cat_target = $(event.target).closest('li').data("id-category");
                current_cat = $("#categorieslist .dd-item.active").data('id-category');
                var current_cat_type = $("#categorieslist .dd-item.active").data('type');
                Wpfd.filetocat = true;
                var dropSuccess = false;
                var files = $('#preview').find('.file.selected');
                if (current_cat !== cat_target) {
                    count = files.length;
                    if (count > 0) { //multiple file
                        if(runOnce === 0) {
                            iFile = 0;
                            files.each(function () {
                                id_file = $(this).data("id-file");
                                if (ctrlDown) { //copy file
                                    $.ajax({
                                        url: wpfdajaxurl + "task=files.copyfile&id_category=" + cat_target + '&active_category=' + current_cat + '&id_file=' + id_file,
                                        type: "POST",
                                        data: {}
                                    }).done(function (data) {
                                        iFile++;
                                        if (iFile === count) {
                                            updateCatCount(cat_target, iFile);
                                            $('.gritter-item-wrapper ').remove();
                                            $.gritter.add({text: wpfd_admin.msg_copy_file});
                                        }
                                        dropSuccess = true;
                                    });
                                } else {
                                    $.ajax({
                                        url: wpfdajaxurl + "task=files.movefile&id_category=" + cat_target + '&active_category=' + current_cat + '&id_file=' + id_file,
                                        type: "POST",
                                        data: {},
                                        dataType: "json"
                                    }).done(function (result) {
                                        iFile++;
                                        if (typeof result.datas.id_file !== "undefined") {
                                            if (current_cat_type === 'nextcloud') {
                                                $('[data-id-file="' + encodeURIComponent(result.datas.id_file) + '"]').remove();
                                            } else {
                                                $('[data-id-file="' + result.datas.id_file + '"]').remove();
                                            }
                                        }
                                        if (iFile === count) {
                                            updateCatCount(current_cat, 0 - iFile);
                                            updateCatCount(cat_target, iFile);
                                            $('.gritter-item-wrapper ').remove();
                                            $.gritter.add({text: wpfd_admin.msg_move_file});
                                        }

                                        dropSuccess = true;
                                    });
                                }
                            })
                            runOnce = 1;
                        }
                    }
                    else {  //single file
                        if(runOnce === 0) {
                            id_file = $(ui.draggable).data("id-file");
                            if (ctrlDown) { //copy file
                                $.ajax({
                                    url: wpfdajaxurl + "task=files.copyfile&id_category=" + cat_target + '&active_category=' + current_cat + '&id_file=' + id_file,
                                    type: "POST",
                                    data: {}
                                }).done(function (data) {
                                    updateCatCount(cat_target, 1);
                                    $('.gritter-item-wrapper ').remove();
                                    $.gritter.add({text: wpfd_admin.msg_copy_file});
                                    dropSuccess = true;
                                });
                            } else {
                                $.ajax({
                                    url: wpfdajaxurl + "task=files.movefile&id_category=" + cat_target + '&active_category=' + current_cat + '&id_file=' + id_file,
                                    type: "POST",
                                    data: {}
                                }).done(function (data) {
                                    $('[data-id-file="' + id_file + '"]').remove();
                                    updateCatCount(current_cat, -1);
                                    updateCatCount(cat_target, 1);
                                    $('.gritter-item-wrapper ').remove();
                                    $.gritter.add({text: wpfd_admin.msg_move_file});
                                    dropSuccess = true;
                                });
                            }
                            runOnce = 1;
                        }
                    }
                }

                $(this).removeClass("ui-state-highlight");
                setTimeout(function() {
                    $(event.target).closest('li').removeClass("wpfddropzoom");
                }, 600);
            }
        });
    };
    catDroppable();
    runOnce = 0;

    function showCategory() {
        $('#insertfile').hide();
        $('#insertcategory').show();

        if (typeof gcaninsert !== "undefined" && gcaninsert) {
            $('#insertfiletowoo').hide();
        }
    }

    function showFile(e) {
        $('#insertcategory').hide();
        $('#insertfile').show();

        if (typeof gcaninsert !== "undefined" && gcaninsert) {
            $('#insertfiletowoo').show();
        }
    }

    function checkCateActive(id_category) {
        var listIdDisable = [];
        $('#categorieslist li').each(function (index) {
            if ($(this).hasClass('disabled')) {
                $(this).removeClass('active');
                listIdDisable.push($(this).data('item-disable'));
            }
        });

        let validCat = true;
        if (!id_category || jQuery.inArray(id_category, listIdDisable) >= 0) {
            validCat = false
        } else {
            let activeCat = $('#categorieslist li.active[data-id-category=' + id_category + ']');
            if (typeof(activeCat) == 'undefined' || !activeCat) {
                validCat = false
            }
        }

        if (!validCat) {
            let id_category_ck = $('#categorieslist li.active').data('id-category');
            if (typeof(id_category_ck) == 'undefined') {
                $('#categorieslist li.not-disable-cat:first').addClass('active');
                id_category_ck = $('#categorieslist li.active').data('id-category');
            }
            id_category = id_category_ck;
        }

        $('input[name=id_category]').val(id_category);
        return id_category;
    }

    function isCloudCategory() {
        var currentActive = $('.dd3-item.active');
        if (typeof currentActive !== 'undefined') {
            if ($('.dd3-item.active > .dd3-handle  > i.onedrive-icon').length > 0 ||
                $('.dd3-item.active > .dd3-handle  > i.onedrive-business-icon').length > 0 ||
                $('.dd3-item.active > .dd3-handle  > i.google-drive-icon').length > 0 ||
                $('.dd3-item.active > .dd3-handle  > i.dropbox-icon').length > 0 ||
                $('.dd3-item.active > .dd3-handle  > i.wpfd-aws-icon').length > 0 ||
                $('.dd3-item.active > .dd3-handle  > i.wpfd-nextcloud-icon').length > 0
            ) {
                return true;
            }
        }
        return false;
    }

    $('#versionspurge').on('click', function(e) {
        e.preventDefault();
        var vpmess = $('#versionpurgemessage');
        var securityCode = wpfd_var.wpfdsecurity;

        $.ajax({
            url: wpfdajaxurl + "task=config.prepareVersions",
            method: 'POST',
            data: {'security': securityCode},
            beforeSend: function() {

                vpmess.css('color', 'green');
                vpmess.hide().html('Loading versions...').show(200);
            },
            success: function(response) {
                if (!response.success) {
                    vpmess.css('color', 'red');
                    vpmess.hide().html(response.message).show(200);
                } else {
                    if (confirm(wpfd_admin.msg_purge_versions)) {
                        var keepVersions = $('input[name="versionlimit"]').val();
                        $.ajax({
                            url: wpfdajaxurl + "task=config.purgeVersions",
                            method: 'POST',
                            data: {'security': securityCode, 'keep': keepVersions},
                            beforeSend: function() {
                                vpmess.css('color', 'red');
                                vpmess.hide().html('Deleting versions...').show(200);
                            },
                            success: function(response) {
                                if (!response.success) {
                                    vpmess.css('color', 'red');
                                    vpmess.hide().html(response.message).show(200);
                                } else {
                                    vpmess.css('color', 'green');
                                    vpmess.hide().html('Deleted files revisions!').show(200);
                                }
                            }
                        });
                    }
                }
            }
        });
        setTimeout(function() {vpmess.hide().html('');}, 5000);
        return false;
    });

    function initSearchForm()
    {
        // Init Date picker
        $.datetimepicker.setLocale(wpfd_admin.locale);
        $('[name="search[created_date]"]').datetimepicker({
            timepicker: false,
            format: 'Y/m/d',
            onGenerate: function () {
                // Close create-date's month listing
                if ($('.xdsoft_select.xdsoft_monthselect').length) {
                    $('.xdsoft_select.xdsoft_monthselect').hide();
                }

                // Close create-date's year listing
                if ($('.xdsoft_select.xdsoft_yearselect').length) {
                    $('.xdsoft_select.xdsoft_yearselect').hide();
                }
            }
        });
        $('[name="search[updated_date]"]').datetimepicker({
            timepicker: false,
            format: 'Y/m/d',
            onGenerate: function () {
                // Close update-date's month listing
                if ($('.xdsoft_select.xdsoft_monthselect').length) {
                    $('.xdsoft_select.xdsoft_monthselect').hide();
                }

                // Close update-date's year listing
                if ($('.xdsoft_select.xdsoft_yearselect').length) {
                    $('.xdsoft_select.xdsoft_yearselect').hide();
                }
            }
        });

        // Init selection
        $('[name="search[category_id]"]').chosen({width: '100%', search_contains: true});
        $('[name="search[category_id]"]').on('change', function(e) {
            var categoryId = $(this).val();

            if (!categoryId) {
                $('[name="search[extension]"]').attr('disabled', false);
                $('[name="search[weight][from]"]').attr('disabled', false);
                $('[name="search[weight][from_unit]"]').attr('disabled', false);
                $('[name="search[weight][to]"]').attr('disabled', false);
                $('[name="search[weight][to_unit]"]').attr('disabled', false);
                return;
            }
        });
        $(document).on('click', '.js-search-submit', searchFilesV2);
        $(document).on('keyup', '[name="search[query]"]', function(e) {
            if (e.keyCode === 13) {
                searchFilesV2(null);
            }
        })
    }
    initSearchForm();
    function searchFilesV2(e)
    {
        var data = {
            keyword: $('[name="search[query]"]').val(),
            catid: $('[name="search[category_id]"]').val(),
            created_date: $('[name="search[created_date]"]').val(),
            updated_date: $('[name="search[updated_date]"]').val(),
            file_type: $('[name="search[extension]"]').val().toLowerCase().replace(/\./g,''),
            file_tags: $('[name="search[tags]"]').val(),
            weight_from: $('[name="search[weight][from]"]').val(),
            weight_from_unit: $('[name="search[weight][from_unit]"]').val(),
            weight_to: $('[name="search[weight][to]"]').val(),
            weight_to_unit: $('[name="search[weight][to_unit]"]').val(),
            waiting_for_approval: $('[name="search[waiting_for_approval]"]').is(':checked')
        };

        if (parseInt(data.catid) === 0 && data.created_date === ''
            && data.file_type === '' && data.file_tags === '' && data.keyword === '' && data.updated_date === ''
            && data.weight_from === '' && data.weight_to === '' && !data.waiting_for_approval) {
            bootbox.alert(_wpfd_text('Filter is not empty.'));
            return;
        }

        if (data.weight_from !== '') {
            if (!$.isNumeric(data.weight_from)) {
                bootbox.alert(_wpfd_text('The weight from is not valid.'));
                return;
            }

            if (parseInt(data.weight_from) < 0) {
                bootbox.alert(_wpfd_text('Weight from is positive integer.'));
                return;
            }

            switch (data.weight_from_unit) {
                case 'kb':
                    data.weight_from = parseFloat(data.weight_from) * 1024;
                    break;
                case 'mb':
                    data.weight_from = parseFloat(data.weight_from) * 1024 * 1024;
                    break;
                case 'gb':
                    data.weight_from = parseFloat(data.weight_from) * 1024 * 1024 * 1024;
                    break;
                default:
                    data.weight_from = parseFloat(data.weight_from);
                    break;
            }
        }
        if (data.weight_to !== '') {
            if (!$.isNumeric(data.weight_to)) {
                bootbox.alert(_wpfd_text('The weight to is not valid.'));
                return;
            }

            if (parseInt(data.weight_to) < 0) {
                bootbox.alert(_wpfd_text('Weight to is positive integer.'));
                return;
            }

            switch (data.weight_to_unit) {
                case 'kb':
                    data.weight_to = parseFloat(data.weight_to) * 1024;
                    break;
                case 'mb':
                    data.weight_to = parseFloat(data.weight_to) * 1024 * 1024;
                    break;
                case 'gb':
                    data.weight_to = parseFloat(data.weight_to) * 1024 * 1024 * 1024;
                    break;
                default:
                    data.weight_to = parseFloat(data.weight_to);
                    break;
            }
        }

        if (data.weight_from !== '' && data.weight_to !== ''
            && parseInt(data.weight_from) > parseInt(data.weight_to)) {
            bootbox.alert(_wpfd_text('Weight from must be less than weight to.'));
            return;
        }

        var $fileTypeSupports = $('.wpfd-admin-search-file-types').val().split(',');
        var $fileTypes = data.file_type.split(',');
        $fileTypes.forEach(function ($type) {
            $type = $.trim($type);
            if ($type !== '' && $.inArray($type, $fileTypeSupports) === -1) {
                bootbox.alert(_wpfd_text('This file type not supports.'));
                return;
            }
        });

        // Send request
        $.ajax({
            url: wpfdajaxurl + "task=files.findFiles&format=raw",
            method: 'POST',
            data: data,
            beforeSend: function () {
                $('#preview').empty();
                $('#loader').show();
            },
            success: function(response) {
                $('#loader').hide();
                $('#preview').html($(response));
                Wpfd.is_search_result = true;
                $('#preview .restable').restable({
                    type: 'hideCols',
                    priority: {0: 'persistent', 1: 3, 2: 'persistent'},
                    hideColsDefault: [4, 5]
                });

                $('#preview').sortable('refresh');
                Wpfd.showhidecolumns();
                initDeleteBtn();

                initFiles();

                $('#wpreview').unbind();
                // initDropbox($('#wpreview'));
                Wpfd.uploader.assignBrowse($('#upload_button'));
                Wpfd.uploader.assignBrowse($('#upload_files_button'));
                Wpfd.uploader.assignDrop($('#wpreview'));

                if (typeof(id_file) !== "undefined") {
                    $('#preview .file[data-id-file="' + id_file + '"]').trigger('click');
                } else {
                    showCategory();
                    if (typeof($ordering) === 'undefined') {
                        loadGalleryParams();
                    }
                }

                // Pending file icon
                var $pendingButton = $('<button class="wpfd-pending-btn">'+ wpfd_admin.file_waiting_for_approval +'</button>');
                var row = $('#preview .file.isPending');
                $('.title', row).prepend($pendingButton);

                rloading('#wpreview');
                $('#wpfd-core #wpreview').trigger('wpfd_admin_search');

                var id_category = $('[name="search[category_id]"]').val();
                if ( id_category > 0 && $('li.dd-item[data-id='+id_category+']').data('type') !== '') {
                    $('li[data-action="file.create_archive_zip"]').hide();
                } else {
                    $('li[data-action="file.create_archive_zip"]').show();
                }
            }
        });
    }
    function wpfdValidateAdminSearchFiles() {
        // File type validate
        if ($('.wpfd-admin-search-file-types').length) {
            var $fileTypeSupports = $('.wpfd-admin-search-file-types').val().split(',');
            $('input[name="search[extension]"]').on('input', function () {
                var $fileTypeVal = $(this).val().replace(/\./g, '').split(',');
                var $length = $(this).val().length;

                if ($length >= 2) {
                    $fileTypeVal.forEach(function ($type) {
                        $type = $.trim($type);
                        setTimeout(function () {
                            if($type !== '' && $.inArray($type, $fileTypeSupports) === -1) {
                                var message = '<span class="wpfd-admin-search-message-file-types">'+ wpfd_admin.msg_admin_search_file_type_support +'</span>';
                                $('.wpfd-admin-search-file-type-message').html(message).fadeIn(300);
                            } else {
                                $('.wpfd-admin-search-file-type-message').html('').fadeOut(300);
                            }
                        }, 800);
                    });
                } else {
                    $('.wpfd-admin-search-file-type-message').html('').fadeOut(300);
                }
            });
        }

        // Create date validate
        // $('input[name="search[created_date]"]').on('input change', function (e) {
        //         var $createDateVal = $(this).val();
        //         var $isdate = true;
        //
        //         if ($createDateVal.indexOf('-') !== -1) {
        //             var dates = $createDateVal.split('-');
        //             dates.forEach(function (date) {
        //                 if (date !== '' && !$.isNumeric(date)) {
        //                     $isdate = false;
        //                 }
        //             });
        //         } else if ($createDateVal.indexOf('/')) {
        //             var dates = $createDateVal.split('/');
        //             dates.forEach(function (date) {
        //                 if (date !== '' && !$.isNumeric(date)) {
        //                     $isdate = false;
        //                 }
        //             });
        //         } else {
        //             if ($createDateVal !== '' && !$.isNumeric($createDateVal)) {
        //                 $isdate = false;
        //             }
        //         }
        //
        //         if (!$isdate) {
        //             var message = '<span class="wpfd-admin-search-message-create-date">'+ wpfd_admin.msg_admin_search_date +'</span>';
        //             $('.wpfd-admin-search-create-date-message').html(message).fadeIn(300);
        //         } else {
        //             $('.wpfd-admin-search-create-date-message').html('').fadeOut(300);
        //         }
        // });

        // Update date validate
        // $('input[name="search[updated_date]"]').on('input change', function () {
        //         var $updateDateVal = $(this).val();
        //         var $isdate = true;
        //         if ($updateDateVal.indexOf('-') !== -1) {
        //             var dates = $updateDateVal.split('-');
        //             dates.forEach(function (date) {
        //                 if (date !== '' && !$.isNumeric(date)) {
        //                     $isdate = false;
        //                 }
        //             });
        //         } else if ($updateDateVal.indexOf('/')) {
        //             var dates = $updateDateVal.split('/');
        //             dates.forEach(function (date) {
        //                 if (date !== '' && !$.isNumeric(date)) {
        //                     $isdate = false;
        //                 }
        //             });
        //         } else {
        //             if ($updateDateVal !== '' && !$.isNumeric($updateDateVal)) {
        //                 $isdate = false;
        //             }
        //         }
        //
        //         if (!$isdate) {
        //             var message = '<span class="wpfd-admin-search-message-update-date">'+ wpfd_admin.msg_admin_search_date +'</span>';
        //             $('.wpfd-admin-search-update-date-message').html(message).fadeIn(300);
        //         } else {
        //             $('.wpfd-admin-search-update-date-message').html('').fadeOut(300);
        //         }
        // });

        // Date time validate
        $('input[name="search[created_date]"], input[name="search[updated_date]"]').on('keydown', function (event) {
            var $key = event.keyCode || event.CharCode;

            // Detect backspace or del key
            if ($key != 8 && $key != 46) {
                return false;
            }
        });

        // Weight from validate
        $('input[name="search[weight][from]"]').on('keydown', function (event) {
            var $key = event.keyCode || event.CharCode;
            if ($key === 187 || $key === 189) {
                setTimeout(function () {
                    var message = '<span class="wpfd-admin-search-message-weight-from">'+ wpfd_admin.msg_admin_search_weight +'</span>';
                    $('.wpfd-admin-search-weight-from-message').html(message).show().fadeOut(3000);
                    $('input[name="search[weight][from]"]').val('');
                    return false;
                }, 500);
            } else {
                $('.wpfd-admin-search-weight-from-message').html('').fadeOut(300);
            }
        });

        // Weight to validate
        $('input[name="search[weight][to]"]').on('keydown', function (event) {
            var $key = event.keyCode || event.CharCode;
            if ($key === 187 || $key === 189) {
                setTimeout(function () {
                    var message = '<span class="wpfd-admin-search-message-weight-to">'+ wpfd_admin.msg_admin_search_weight +'</span>';
                    $('.wpfd-admin-search-weight-to-message').html(message).show().fadeOut(3000);
                    $('input[name="search[weight][to]"]').val('');
                    return false;
                }, 500);
            } else {
                $('.wpfd-admin-search-weight-to-message').html('').fadeOut(300);
            }
        });
    }
    wpfdValidateAdminSearchFiles();

    function searchFiles(keyword, filter_catid = 0, ordering, ordering_dir) {
        if (typeof(filter_catid) === "undefined" || filter_catid === null) {
            filter_catid = $('#wpfd_filter_catid').val();
        }
        var url = wpfdajaxurl + "task=files.search&format=raw";
        $.ajax({
            url: url,
            type: "POST",
            data: {
                "s": keyword,
                "cid": filter_catid,
                "orderCol": ordering,
                "orderDir": ordering_dir
            }
        }).done(function (data) {
            $('#preview').html($(data));

            $('#preview .restable').restable({
                type: 'hideCols',
                priority: {0: 'persistent', 1: 3, 2: 'persistent'},
                hideColsDefault: [4, 5]
            });

            $('#preview').sortable('refresh');
            Wpfd.showhidecolumns();
            initDeleteBtn();

            /** Init ordering **/
            $('#preview .wpfd_tbl .head a').click(function (e) {
                e.preventDefault();
                searchFiles(keyword, $(this).data('ordering'), $(this).data('direction'));

                if ($(this).data('direction') === 'asc') {
                    direction = 'desc';
                } else {
                    direction = 'asc';
                }

                $('#ordering option[value="' + $(this).data('ordering') + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                $('#orderingdir option[value="' + direction + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
            });

            // initUploadBtn();

            initFiles();

            $('#wpreview').unbind();
            // initDropbox($('#wpreview'));
            Wpfd.uploader.assignBrowse($('#upload_button'));
            Wpfd.uploader.assignBrowse($('#upload_files_button'));
            Wpfd.uploader.assignDrop($('#wpreview'));

            if (typeof(id_file) !== "undefined") {
                $('#preview .file[data-id-file="' + id_file + '"]').trigger('click');
            } else {
                showCategory();
                if (typeof($ordering) === 'undefined') {
                    loadGalleryParams();
                }
            }
            rloading('#wpreview');
            $('#wpfd-core #wpreview').trigger('wpfd_admin_search');
        })
    }

    $(window).resize(function () {
        hideColumns();
    });

    //hide columns base on window size
    function hideColumns() {
        var w = $(window).width();
        if (w <= 1600 && w > 1440) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0').prop('checked', false);
        } else if (w <= 1440 && w > 1200) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0,#restable-toggle-col-4-0').prop('checked', false);
        } else if (w <= 1200 && w > 1024) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0,#restable-toggle-col-4-0,#restable-toggle-col-3-0').prop('checked', false);
        } else if (w <= 1024) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0,#restable-toggle-col-4-0,#restable-toggle-col-3-0,#restable-toggle-col-2-0').prop('checked', false);
        }
    }

    //show/hide columns base on cookie
    Wpfd.showhidecolumns = function() {
        if (!wpfd_admin.listColumns.length) {
            hideColumns();
            return;
        }
        $('.restable thead th').hide();
        $('.restable tbody td').hide();
        $('input[name="restable-toggle-cols"]').prop('checked', false);
        $.each(wpfd_admin.listColumns, function (i, v) {
            $('#' + v).prop('checked', true);

            var colOrder = parseInt($('#' + v).data('col'));
            if (isNaN(colOrder))  {
                colOrder = 0;
            }
            var col = colOrder + 1;
            $('.restable thead th:nth-child(' + col + ')').show();
            $('.restable tbody td:nth-child(' + col + ')').show();
        });
    }

    function setcookie_showcolumns() {
        var column_show = [];
        $('input[name="restable-toggle-cols"]').each(function (i, v) {
            if ($(v).is(':checked')) {
                column_show.push($(v).attr('id'));
            }
        });

        var url = wpfdajaxurl + "task=files.showcolumn";
        $.ajax({
            url: url,
            type: "POST",
            data: {
                column_show: column_show
            }
        }).done(function (data) {
            wpfd_admin.listColumns = column_show;
        });
    }

    /**
     * Init delete button
     */
    function initDeleteBtn() {
        $('.actions .trash').unbind('click').click(function (e) {
            that = this;
            bootbox.dialog(wpfd_admin.msg_ask_delete_file,
                [
                    {
                        "label": _wpfd_text('Cancel')
                    },
                    {
                        "label": _wpfd_text('Confirm'),
                        "callback": function() {
                            //Delete file
                            id_file = $(that).parents('.file').data('id-file');
                            var id_category = $('li.dd-item.dd3-item.active').data('id-category');
                            $.ajax({
                                url: wpfdajaxurl + "task=file.delete&id_file=" + id_file + "&id_category=" + id_category,
                                type: "POST",
                                data: {}
                            }).done(function (data) {
                                $(that).parents('.file').fadeOut(500, function () {
                                    $(this).remove();
                                    $('.gritter-item-wrapper ').remove();
                                    $.gritter.add({text: wpfd_admin.msg_remove_file});
                                });
                            });
                        }
                    }
                ]
            );
            return false;
        });
    }

    /**
     * Init files
     */
    function initFiles() {
        $(document).off('click.window').on('click.window', function (e) {
            if ($(e.target).is('#rightcol')
                || $(e.target).hasClass('wpfd-flip')
                || $(e.target).parents('#rightcol').length > 0
                || $(e.target).parents('.bootbox.modal').length > 0
                || $(e.target).parents('.tagit-autocomplete').length > 0
                || $(e.target).parents('.mce-container').length > 0
                || $(e.target).parents('.calendar').length > 0
                || $(e.target).parents('.wpfd-btn-toolbar').length > 0
                || $(e.target).parents('.media-modal').length > 0
                || $(e.target).parents('#wp-link-wrap').length > 0
                || $(e.target).parents('.wpfd-dropdown-menu').length > 0
            ) {
                return;
            }
            $('#preview .file').removeClass('selected');
            if (isIOS) {
                $('#wpfd_ios_file_menu').hide();
            }
            // if ($('#preview .file.selected').length === 0) {
            //     showCategory();
            // }
        });
    }
    var ctrlDown = false,
        ctrlKey = 17,
        cmdKey = 91,
        aKey = 65,
        AKey = 97;
    $(document).keydown(function(e) {
        if (e.keyCode === ctrlKey || e.keyCode === cmdKey) ctrlDown = true;
    }).keyup(function(e) {
        if (e.keyCode === ctrlKey || e.keyCode === cmdKey) ctrlDown = false;
    });

    // Document Ctrl + C/V
    $(document).keydown(function(e) {
        if (ctrlDown && (e.keyCode === aKey || e.keyCode === AKey)) {
            e.preventDefault();
            if ($('#preview .file').length) {
                $('#preview .file').addClass('selected');
            }
            return false;
        }
    });
    var file_clicks = 0;
    $(document).on('click', '#preview .file', function (e) {
        if (isIOS) {
            $('#wpfd_ios_file_menu').show();
        }
        if (e.shiftKey) {
            // get index from the start selected
            var startSelection = $('#preview .file.selected').first();
            var lastSelection = $('#preview .file.selected').last();
            var currentSelection = $(this);
            if (startSelection.length || lastSelection.length) {
                var startIndex = startSelection.index();
                var lastIndex = lastSelection.index();
                var currentIndex = currentSelection.index();
                $('#preview .file.selected').removeClass('selected');
                if (currentIndex > startIndex) { // Top to bottom
                    $('#preview .file').each(function() {
                        if ($(this).index() >= startIndex && $(this).index() <= currentIndex) {
                            $(this).addClass('selected');
                        }
                    });
                } else { // Bottom to top
                    $('#preview .file').each(function() {
                        if ($(this).index() <= lastIndex && $(this).index() >= currentIndex) {
                            $(this).addClass('selected');
                        }
                    });
                }

            }
        } else if (e.ctrlKey || e.metaKey) {
            $(this).addClass('selected');
        } else {
            $('#preview .file.selected').removeClass('selected');
            $(this).addClass('selected');
        }
        e.stopPropagation();

        if ($('#preview .file.selected').length === 1) {
            file_clicks++;
            if (file_clicks === 1) {
                setTimeout(function () {
                    if (file_clicks === 1) {
                        showFile();
                    } else {
                        // Open file Edit
                        Wpfd.submitbutton('files.edit');
                    }
                    file_clicks = 0;
                }, 300);
            }
        } else {
            showCategory();
        }

        return false;
    });
    /**
     * Init the file edit btn
     */
    function initEditBtn() {
        $('.wbtn a.edit').off('click').on('click', function (e) {
            that = this;
            id_file = $(that).parents('.wimg').find('img.img').data('id-file');
            $.ajax({
                url: wpfdajaxurl + "view=file&format=raw&id=" + id_file,
                type: "POST",
                data: {}
            }).done(function (data) {
                bootbox.dialog(data, [{
                    'label': _wpfd_text('Save'),
                    'class': 'btn-success',
                    'callback': function () {
                        var p = '';
                        $('#file-form .wpfdinput').each(function (index) {
                            p = p + $(this).attr('name') + '=' + $(this).attr('value') + '&';
                        });
                        $.ajax({
                            url: $('#file-form').attr('action'),
                            type: 'POST',
                            data: p,
                        }).done(function (data) {
                            //do nothing
                        });
                    },
                }, {
                    'label': _wpfd_text('Cancel', 'Cancel'),
                    'class': 'btn-warning',
                }], {header: _wpfd_text('Image parameters', 'Image parameters')});

            });
            return false;
        });
    }

    var category_params_change = false,
        file_params_change = false;

    function resetCategoryParamsCheck() {
        category_params_change = false;
        window.localStorage.removeItem('_wpfd_current_category_param');
    }

    function saveCategoryParamsCheck() {
        var currentSettings = $('#category_params').serialize();
        window.localStorage.setItem('_wpfd_current_category_param', currentSettings);
    }

    function resetFileParamsCheck() {
        file_params_change = false;
        window.localStorage.removeItem('_wpfd_current_file_param');
    }

    function saveFileParamsCheck() {
        var currentFileSettings = $('#fileparams .wpfdparams').first().serialize();
        window.localStorage.setItem('_wpfd_current_file_param', currentFileSettings);
    }

    /**
     * Load category layout params
     */
    function loadGalleryParams() {
        id_category = $('input[name=id_category]').val();
        $.cookie('wpfd_selected_category', id_category);
        loading('#rightcol');
        $.ajax({
            url: wpfdajaxurl + "task=category.edit&layout=form&id=" + id_category
        }).done(function (data) {
            $('#galleryparams').html(data);
            // Clean layouts
            var editLayout = $('#galleryparams .wpfdparams');
            editLayout.each(function (i, layout) {
                if (i !== 0) {
                    $(this).remove();
                }
            });

            $('#galleryparams .wpfdparams #visibility').change(function () {
                if ($(this).val() !== '1') {
                    $('#galleryparams .wpfdparams #visibilitywrap').hide();
                    $('#galleryparams .wpfdparams #visibilitywrap input').prop('checked', false);
                } else {
                    $('#galleryparams .wpfdparams #visibilitywrap').show();
                }
            }).trigger('change');

            $('#wpfd-theme').change(function () {
                changeTheme();
            });
            // Save current categories params
            initColor();
            resetCategoryParamsCheck();
            saveCategoryParamsCheck();
            $('.user-clear.cat').on('click', function () {
                $('.field-user-category-access-name.category').val('');
                $('.field-user-category-access.category').val('');
            });
            $('.user-clear.file').on('click', function () {
                $('.field-user-input-name.file').val('');
                $('.field-user-input.file').val('');
            });
            $('.user-clear-category').on('click', function () {
                $('.field-user-category-own-name').val('');
                $('.field-user-category-own').val('');
            });
            $('#galleryparams .wpfdparams button[type="submit"]').click(function (e) {
                e.preventDefault();
                id_category = $('input[name=id_category]').val();
                $.ajax({
                    url: wpfdajaxurl + "task=category.saveparams&id=" + id_category,
                    type: "POST",
                    data: $('#category_params').serialize()
                }).done(function (data) {

                    result = jQuery.parseJSON(data);
                    if (result.response === true) {
                        $('.gritter-item-wrapper ').remove();
                        $.gritter.add({text: wpfd_admin.msg_save_category});
                        Wpfd.updatePreview();
                        loadGalleryParams();
                    } else {
                        bootbox.alert(result.response);
                    }
                    loadGalleryParams();
                });
                return false;
            });
            var category_control_group = $('#rightcol .categoryblock').find('.control-group');
            if(category_control_group.length) {
                category_control_group.addClass('category-control-group');
            }
            var event = $.Event('wpfd_category_param_loaded');
            $(document).trigger(event);
            rloading('#rightcol');

            changeShowFolderTree();
        });
    }

    // Monitor category settings submit
    $(document).on('input change', '#category_params input, #category_params select, #category_params textarea', function () {
        var savedSettings = window.localStorage.getItem('_wpfd_current_category_param');
        var currentSettings = $('#category_params').serialize();

        if (currentSettings !== savedSettings) {
            // Settings changes
            category_params_change = true;
        }
    });
    // Monitor file settings submit
    $(document).on('input change', '#fileparams .wpfdparams input, #fileparams .wpfdparams select, #fileparams .wpfdparams textarea', function () {
        var savedFileSettings = window.localStorage.getItem('_wpfd_current_file_param');
        var currentFileSettings = $('#fileparams .wpfdparams').serialize();

        if (currentFileSettings !== savedFileSettings) {
            // Settings changes
            file_params_change = true;
        }
    });
    // init change theme for category
    function changeTheme() {
        theme = $('#wpfd-theme').val();
        id_category = $('input[name=id_category]').val();

        $.ajax({
            url: wpfdajaxurl + "task=category.edit&layout=form&theme=" + theme + "&onlyTheme=1&id=" + id_category
        }).done(function (data) {
            $('#category-layout').remove();
            $('#file-layout').remove();
            var is_theme_settings = $('#rightcol .wpfd-is-theme-settings');
            if (is_theme_settings.length) {
                if ($("#permission-settings").length) {
                    $(data).insertAfter('#permission-settings');
                } else {
                    $(data).insertAfter('#main-settings');
                }
            }
            initColor();
            resetCategoryParamsCheck();
            saveCategoryParamsCheck();
            var category_control_group = $('#rightcol .categoryblock').find('.control-group');
            if(category_control_group.length) {
                category_control_group.addClass('category-control-group');
            }

            changeShowFolderTree();
        })
    }

    $(document).on('change', 'select[name*="showfoldertree"]', function() {
        changeShowFolderTree();
    });

    function changeShowFolderTree() {
        if ($('#wpfd-theme').length) {
            var wpfd_showfoldertree = $('select[name*="showfoldertree"]');
            var wpfd_expanded_folder_tree = $('label[for*="expanded_folder_tree"]');
            var wpfd_theme = $('#wpfd-theme').val();
            if (wpfd_theme != 'default') {
                wpfd_showfoldertree = $('select[name*="'+wpfd_theme+'_showfoldertree"]');
                wpfd_expanded_folder_tree = $('label[for*="'+wpfd_theme+'_expanded_folder_tree"]');
            }
            if ($(wpfd_showfoldertree).length && $(wpfd_expanded_folder_tree).length && $(wpfd_showfoldertree).val() == 0) {
                $(wpfd_expanded_folder_tree).closest('.category-control-group').hide();

                $(wpfd_showfoldertree).change(function () {
                    if ($(wpfd_showfoldertree).val() == 0) {
                        $(wpfd_expanded_folder_tree).closest('.category-control-group').hide();
                    } else {
                        $(wpfd_expanded_folder_tree).closest('.category-control-group').show();
                    }
                });
            }
        }
    }

    Wpfd.is_search_result = false;
    Wpfd.loadFileOrSearch = function() {
        // $("#preview").sortable("enable");
        // $("#preview").disableSelection();
        // Insert params to next row
        var table = $('.restable');
        table.find('tr.file').removeClass('selected');
        table.find('tr[data-id-file="' + id_file + '"]').addClass('selected');
        table.find('tfoot').remove();
        table.find('tbody').show();
        table.css({'borderSpacing': '0 8px'});
        if (Wpfd.is_search_result) {
            // Do search again
            searchFilesV2(null);
        } else {
            Wpfd.updatePreview();
        }
    };

    function fileEditorInit() {
        $('.chosen').chosen({width: '100%', search_contains: true});
        $('#filearams .ju-switch-button .switch input[type="checkbox"]').on('change', function(e) {
            var $switch = $(e.target);
            var ref = $switch.attr('name').replace('ref_', '');
            $('input[name="' + ref + '"]').val($switch.prop('checked') ? 1 : 0).trigger('wpfd::switch');
        });
        var bindSwitchChange = function (e) {
            var $switch = $(e.target);
            var ref = $switch.attr('name').replace('ref_', '');
            $('input[name="' + ref + '"]').val($switch.prop('checked') ? 1 : 0);
        };
        $('#fileparams .ju-switch-button .switch input[type="checkbox"]').off('change', bindSwitchChange).on('change', bindSwitchChange);
        $('#fileparams .wpfdparams input[type="submit"]').each(function() {
            $(this).click(function (e) {
                e.preventDefault();
                var idCategory = jQuery('li.dd-item.dd3-item.active').data('id-category');
                var idRefCategory = jQuery('.file[data-id-file="' + id_file + '"]').data('catid-file');
                var fileData = $('#fileparams .wpfdparams').serialize();
                var old_title = title;
                var old_idFile = id_file;
                var fileAws = false;
                var fileNextcloud = false;

                if (wpfd_var && wpfd_var.wpfd_aws_categories && wpfd_var.wpfd_aws_categories[idRefCategory] !== undefined) {
                    fileAws = true;
                }

                if (wpfd_var && wpfd_var.wpfd_nextcloud_categories && wpfd_var.wpfd_nextcloud_categories[idRefCategory] !== undefined) {
                    fileNextcloud = true;
                }

                fileData = unserialize(fileData);
                resetFileParamsCheck();
                var newFileData = '';
                var publishDateField = $('#fileparams .wpfdparams #publish');
                if (publishDateField.length > 0) {
                    var d = publishDateField.data('daterangepicker').startDate.clone();
                    fileData.publish = d.format('YYYY-MM-DD H:mm:ss');
                }

                var expirationDateField = $('#fileparams .wpfdparams #expiration');
                if (expirationDateField.length > 0) {
                    var d = expirationDateField.data('daterangepicker').startDate.clone();
                    if (d.isValid() && $('#fileparams .wpfdparams #expiration').val()) {
                        fileData.expiration = d.format('YYYY-MM-DD H:mm:ss');
                    } else {
                        fileData.expiration = '';
                    }
                }

                $.each(fileData, function (key, value) {
                    newFileData += key + '=' + value + '&';
                });

                // Correct file renaming
                var stopEditing = false;
                if ($('#fileparams .wpfdparams input[name="title"]').length && typeof (wpfd_var.wpfd_check_special_char_editing) !== "undefined" && parseInt(wpfd_var.wpfd_check_special_char_editing) === 1) {
                    var correctFileName = $('#fileparams .wpfdparams input[name="title"]').val();
                    if (!isCloudCategory()) {
                        var excludeChars = new Array('"', '/', '\\');
                    } else {
                        var excludeChars = new Array('"', '*', ':','<', '>', '?', '/', '\\', '|');
                    }

                    if (typeof (wpfd_var.wpfd_include_slash_character_file_title) !== "undefined" && parseInt(wpfd_var.wpfd_include_slash_character_file_title) === 1) {
                        var valueToRemove = '/';
                        excludeChars = excludeChars.filter(function(char) {
                            return char !== valueToRemove;
                        });
                    }

                    excludeChars.forEach(function (char) {
                        if (correctFileName.indexOf(char) !== -1) {
                            stopEditing = true;
                            if (!isCloudCategory()) {
                                bootbox.alert(wpfd_admin.msg_rename_file);
                            } else {
                                bootbox.alert(wpfd_admin.msg_rename_file_cloud);
                            }
                            return false;
                        }
                    });
                }

                if (stopEditing) {
                    return;
                }

                if (fileAws === true || fileNextcloud === true) {
                    id_file = encodeURIComponent(id_file);
                }

                $.ajax({
                    url: wpfdajaxurl + "task=file.save&id=" + id_file + "&idCategory=" + idCategory + "&idRefCategory=" + idRefCategory,
                    method: "POST",
                    //dataType: 'json',
                    data: newFileData
                }).done(function (data) {
                    if (typeof data === 'string') {
                        result = jQuery.parseJSON(data);
                    } else {
                        result = data;
                    }
                    if (result.response === true) {
                        $('.gritter-item-wrapper ').remove();
                        $.gritter.add({text: wpfd_admin.msg_save_file});
                        // Live update the form and file row
                        if (fileAws === true || fileNextcloud === true) {
                            id_file = decodeURIComponent(id_file);
                        }

                        $(document).trigger('wpfd_file_save_success', [result.datas, id_file, idCategory]);
                        $('.wpfd_row').removeClass('editing');
                        $('#preview').sortable('refresh');
                        $('#preview').sortable('enable');
                    } else {
                        bootbox.alert(result.response);
                    }
                });
                return false;
            });
        });
        $('#fileparams .wpfdparams .file-settings-hide').on('click', function(e) {
            e.preventDefault();
            // Ask before save
            if ($('.wpfd_row.editor').length) {
                if (file_params_change) {
                    // Prompt for unsave changes
                    bootbox.confirm(
                        wpfd_admin.msg_prompt_file_params_changes_not_saved,
                        function (result) {
                            if (result) {
                                // Wpfd.loadFileOrSearch();
                                $('.wpfd_row').removeClass('editing');
                                $('.wpfd_row.editor').slideUp('fast', function() {
                                    $(this).remove();
                                });
                            }
                        }
                    );
                } else {
                    // Wpfd.loadFileOrSearch();
                    $('.wpfd_row').removeClass('editing');
                    $('.wpfd_row.editor').slideUp('fast', function() {
                        $(this).remove();
                    });
                }
            }

            return false;
        });
        $('#fileparams .wpfdparams .file-settings-save-hide').on('click', function(e) {
            e.preventDefault();
            $('#fileparams .wpfdparams input[type="submit"]').trigger('click');
            $('.file-settings-hide').trigger('click');

            return false;
        });
        $('.user-clear.cat').on('click', function () {
            $('.field-user-input-name.category').val('');
            $('.field-user-input.category').val('');
        });
        $('.user-clear.file').on('click', function () {
            $('.field-user-input-name.file').val('');
            $('.field-user-input.file').val('');
        });

        $('.media-clear.file').on('click', function () {
            $('#file_custom_icon').val('');
            $('#file_custom_icon_preview').val('');
        });

        $('#file_multi_category_old').parent().hide();

        $('.file_direct_link').val(linkdownload);

        $('.btn_file_direct_link').on('click', function () {
            var linkcopy = $('.file_direct_link').val();
            var inputlink = document.createElement("input");
            inputlink.setAttribute("value", linkcopy);
            document.body.appendChild(inputlink);
            inputlink.select();
            document.execCommand("copy");
            document.body.removeChild(inputlink);
            $.gritter.add({text: wpfd_admin.msg_copied_to_clipboard});
        });

        var select_media;

        $('#select_media_button').click(function (e) {
            e.preventDefault();
            //If the uploader object has already been created, reopen the dialog
            if (select_media) {
                select_media.open();
                return;
            }
            //Extend the wp.media object
            select_media = wp.media.frames.file_frame = wp.media({
                title: 'Choose Image',
                multiple: false,
                library: {
                    type: 'image'
                },
                button: {
                    text: 'Choose Image'
                }
            });
            var contentArr = wpfd_var.contenturl.split('/');
            var contentUrl = '/' + contentArr[contentArr.length - 1];
            //When a file is selected, grab the URL and set it as the text field's value
            select_media.on('select', function () {
                attachment = select_media.state().get('selection').first().toJSON();
                if (typeof (wpfd_var.large_size_custom_icon) !== "undefined" && parseInt(wpfd_var.large_size_custom_icon) === 1) {
                    if (typeof attachment.sizes.large !== 'undefined') {
                        $('#file_custom_icon').val(attachment.sizes.large.url.substring(attachment.sizes.large.url.indexOf(contentUrl)));
                    } else {
                        $('#file_custom_icon').val(attachment.sizes.full.url.substring(attachment.sizes.full.url.indexOf(contentUrl)));
                    }
                } else {
                    if (typeof attachment.sizes.thumbnail !== 'undefined') {
                        $('#file_custom_icon').val(attachment.sizes.thumbnail.url.substring(attachment.sizes.thumbnail.url.indexOf(contentUrl)));
                    } else if (typeof attachment.sizes.medium !== 'undefined') {
                        $('#file_custom_icon').val(attachment.sizes.medium.url.substring(attachment.sizes.medium.url.indexOf(contentUrl)));
                    } else {
                        $('#file_custom_icon').val(attachment.sizes.full.url.substring(attachment.sizes.full.url.indexOf(contentUrl)));
                    }
                }

                // Custom icon for preview file
                $('#file_custom_icon_preview').val(attachment.sizes.full.url.substring(attachment.sizes.full.url.indexOf('uploads')));
            });
            //Open the uploader dialog
            select_media.open();
        });

        var dateFormat = wpfd_var.dateFormat;

        $.datetimepicker.setLocale(wpfd_admin.locale);

        if (typeof (moment()) !== 'undefined') {
            $('#publish').daterangepicker({
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')]
                },
                "singleDatePicker": true,
                "showISOWeekNumbers": true,
                "timePicker": true,
                "timePicker24Hour": true,
                "timePickerIncrement": 1,
                "autoApply": false,
                "locale": {
                    "format": dateFormat
                },
                "cancelButtonClasses": "ju-link-button",
                "applyButtonClasses": "ju-material-button",
                "linkedCalendars": false,
                "showCustomRangeLabel": false,
                "drops": "auto"
            }, function(start, end, label) {
            });
        }

        $('#publish_img').on('click', function () {
            $('#publish').data('daterangepicker').show();
        });
        if (typeof (moment()) !== 'undefined') {
            $('#expiration').daterangepicker({
                ranges: {
                    'Today': [moment(), moment()],
                    'Tomorrow': [moment().add(1, 'days'), moment().add(1, 'days')],
                    'Next 2 days': [moment().add(2, 'days'), moment().add(2, 'days')],
                    'Next 7 days': [moment().add(7, 'days'), moment().add(7, 'days')],
                    'Next Month': [moment().add(1, 'month').startOf('month'),moment().add(1, 'month').startOf('month')]
                },
                "singleDatePicker": true,
                "autoUpdateInput": false,
                "showISOWeekNumbers": true,
                "timePicker": true,
                "timePicker24Hour": true,
                "timePickerIncrement": 1,
                "autoApply": false,
                "locale": {
                    "format": dateFormat,
                    "cancelLabel": "Clear"
                },
                "cancelButtonClasses": "ju-link-button",
                "applyButtonClasses": "ju-material-button",
                "linkedCalendars": false,
                "showCustomRangeLabel": false,
                "drops": "auto",
                "minYear": parseInt(moment().subtract(10, 'years').format('YYYY'), 10),
                "maxYear": parseInt(moment().add(10, 'years').format('YYYY'),10)

            }, function(start, end, label) {
                console.log(start.isValid());
                if (start.isValid()) {
                    $('#expiration').val(start.format(dateFormat));
                }
            });
        }
        $('#expiration_img').on('click', function () {
            $('#expiration').data('daterangepicker').show();
        });
        $('#expiration').on('cancel.daterangepicker', function(ev, picker) {
            $('#expiration').val('');
        });
        if (!$.isNumeric(id_file)) {
            if ($('input#expiration').length && $('input#expiration').is(':visible')) {
                $('input#expiration').parents('.control-group').remove();
            }
        }

        $('.chzn-search-input').attr('placeholder', wpfd_admin.click_to_add_more_categories);
        if ($('input[name="file_password"]').length) {
            $('input[name="file_password"]').attr('placeholder', 'Use a secure password')
        }
    }

    // load file versions
    function loadVersions(id_file = null, idCategory = null) {
        id_category = $('input[name=id_category]').val();
        if (id_file === null) {
            id_file = $('.file.selected').data('id-file');
        }
        if (idCategory === null) {
            idCategory = $('li.dd-item.dd3-item.active').data('id-category');
        }

        var fileInfo = [];
        fileInfo.push({'fileId': id_file, 'catid': idCategory});

        loading('#preview #fileversion');
        var oldVersionAjax = versionAjax;
        if (oldVersionAjax !== null) {
            oldVersionAjax.abort();
        }
        versionAjax = $.ajax({
            url: wpfdajaxurl + "view=file&layout=versions",
            type: 'POST',
            data: {fileInfo: fileInfo, security: wpfd_var.wpfdsecurity}
        }).done(function (data) {
            $('#preview #versions_content').html(data);
            $('#preview #versions_content a.trash').off('click').on('click', function (e) {
                e.preventDefault();
                that = this;
                bootbox.dialog(
                    _wpfd_text('Are you sure remove version') + '?',
                    [
                        {
                            "label": _wpfd_text("Cancel")
                        },
                        {
                            "label": _wpfd_text('Confirm'),
                            "callback": function() {
                                vid = $(that).data('vid');
                                $.ajax({
                                    url: wpfdajaxurl + "task=file.deleteVersion&vid=" + vid + "&id_file=" + id_file + "&catid=" + id_category,
                                    type: "POST",
                                    data: {}
                                }).done(function (data) {
                                    result = $.parseJSON(data);
                                    if (result.response === true) {
                                        $(that).parents('tr').remove();
                                    } else {
                                        bootbox.alert(result.response);
                                    }
                                });
                            }
                        }
                    ]
                );

                return false;
            });
            $('#preview #versions_content a.restore').on('click', function (e) {
                e.preventDefault();
                that = this;
                file_ext = $('.file.selected .txt').text();
                file_title = $('.file.selected .title').text();
                bootbox.dialog(_wpfd_text('Are you sure restore file') + file_title + "." + file_ext + '?',
                    [
                        {
                            "label": _wpfd_text('Cancel')
                        },
                        {
                            "label": _wpfd_text('Confirm'),
                            "callback": function() {
                                vid = $(that).data('vid');
                                fid = $(that).data('id');
                                catid = $(that).data('catid');
                                catfrom = $(that).data('catfrom');
                                $.ajax({
                                    url: wpfdajaxurl + "task=file.restore&vid=" + vid + "&id=" + fid + "&catid=" + catid,
                                    type: "POST",
                                    data: {}
                                }).done(function (data) {
                                    result = $.parseJSON(data);
                                    if (result.response === true) {
                                        if (catfrom !== 'nextcloud') {
                                            $(that).parents('tr').remove();
                                        }

                                        id_file = $('.file.selected').data('id-file');
                                        //Wpfd.updatePreview(null, id_file);
                                        if (typeof (result.datas.version) !== "undefined") {
                                            if (catfrom === 'nextcloud') {
                                                loadVersions(fid, catid);
                                            }
                                            // Update version field
                                            $('[name="version"]').val(result.datas.version);
                                            // Update stored file params
                                            var savedFileSettings = window.localStorage.getItem('_wpfd_current_file_param');
                                            savedFileSettings = unserialize(savedFileSettings);
                                            savedFileSettings.version = result.datas.version;

                                            window.localStorage.setItem('_wpfd_current_file_param', $.param(savedFileSettings));
                                        }
                                    } else {
                                        bootbox.alert(result.response);
                                    }
                                });
                            }
                        }
                    ]);
                return false;
            });

            rloading('#preview #fileversion');
        });
    }

    // init upload button
    function initUploadBtn() {
        $('#upload_button, #upload_files_button').on('click', function () {
            $('#upload_input').trigger('click');
            return false;
        });
    }

    var googleInterval, onedriveInterval, onedriveBusinessInterval, dropboxInterval, googleTeamDriveInterval;
    /**
     * Click to Sync with Google Drive
     */
    Wpfd.syncGoogleDrive = function(callback) {
        wpfd_status.addStatusLine('Synching Google Drive...');
        $.ajax({
            url: wpfd_var.wpfdajaxurl + '?action=googleSync',
            success: function (data) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_google_drive_sync_done});
                //window.location.reload();
                googleInterval = setInterval(function() {
                    $.ajax({
                        url: wpfd_var.wpfdajaxurl + '?action=google_sync_status',
                        success: function(response) {
                            if (response.success) {
                                if (response.total === 0) {
                                    clearInterval(googleInterval);
                                    callback();
                                    // Queue stoped. Prompt user reload page or not?
                                    bootbox.dialog(
                                        wpfd_admin.msg_promtp_sync_reload_page,
                                        [
                                            {
                                                "label": _wpfd_text('Cancel')
                                            },
                                            {
                                                "label": _wpfd_text('Confirm'),
                                                "callback": function () {
                                                    window.location.reload()
                                                }
                                            }
                                        ]
                                    );

                                }
                            }
                        }
                    });
                }, 2000);
            }
        });
    };
    $('#btn-sync-gg').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).button('loading');
        Wpfd.syncGoogleDrive(function() {
            $btn.button('complete');
        });
        return false;
    });

    /**
     * Click to Sync with Google Team Drive
     */
    Wpfd.syncGoogleTeamDrive = function(callback) {
        wpfd_status.addStatusLine('Syncing Google Team Drive...');
        $.ajax({
            url: wpfd_var.wpfdajaxurl + '?action=googleTeamDriveSync',
            success: function (data) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_google_drive_sync_done});
                // window.location.reload();
                googleTeamDriveInterval = setInterval(function() {
                    $.ajax({
                        url: wpfd_var.wpfdajaxurl + '?action=google_team_drive_sync_status',
                        success: function(response) {
                            if (response.success) {
                                if (response.total === 0) {
                                    clearInterval(googleTeamDriveInterval);
                                    callback();
                                    // Queue stopped. Prompt user reload page or not?
                                    bootbox.dialog(
                                        wpfd_admin.msg_promtp_sync_reload_page,
                                        [
                                            {
                                                "label": _wpfd_text('Cancel')
                                            },
                                            {
                                                "label": _wpfd_text('Confirm'),
                                                "callback": function () {
                                                    window.location.reload()
                                                }
                                            }
                                        ]
                                    );

                                }
                            }
                        }
                    });
                }, 2000);
            }
        });
    };

    /**
     * Click to Sync with Dropbox
     */
    Wpfd.syncDropbox = function(callback) {
        wpfd_status.addStatusLine('Synching Dropbox...');
        $.ajax({
            url: wpfd_var.wpfdajaxurl + '?action=dropboxSync',
            success: function (data) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_google_drive_sync_done});
                //window.location.reload();
                dropboxInterval = setInterval(function() {
                    $.ajax({
                        url: wpfd_var.wpfdajaxurl + '?action=dropbox_sync_status',
                        success: function(response) {
                            if (response.success) {
                                if (response.total === 0) {
                                    clearInterval(dropboxInterval);
                                    callback();
                                    // Queue stoped. Prompt user reload page or not?
                                    bootbox.dialog(
                                        wpfd_admin.msg_promtp_sync_reload_page,
                                        [
                                            {
                                                "label": _wpfd_text('Cancel')
                                            },
                                            {
                                                "label": _wpfd_text('Confirm'),
                                                "callback": function () {
                                                    window.location.reload()
                                                }
                                            }
                                        ]
                                    );

                                }
                            }
                        }
                    });
                }, 2000);
            }
        });
    };
    $('#btn-sync-drop').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).button('loading');
        Wpfd.syncDropbox(function() {
            $btn.button('complete');
        });
        return false;
    });

    /**
     * Click to Sync with OneDrive
     */
    Wpfd.syncOnedrive = function(callback) {
        wpfd_status.addStatusLine('Synching Onedrive...');
        $.ajax({
            url: wpfd_var.wpfdajaxurl + '?action=onedriveSync',
            success: function (data) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_google_drive_sync_done});
                //window.location.reload();
                onedriveInterval = setInterval(function() {
                    $.ajax({
                        url: wpfd_var.wpfdajaxurl + '?action=onedrive_sync_status',
                        success: function(response) {
                            if (response.success) {
                                if (response.total === 0) {
                                    clearInterval(onedriveInterval);
                                    callback();
                                    // Queue stoped. Prompt user reload page or not?
                                    bootbox.dialog(
                                        wpfd_admin.msg_promtp_sync_reload_page,
                                        [
                                            {
                                                "label": _wpfd_text('Cancel')
                                            },
                                            {
                                                "label": _wpfd_text('Confirm'),
                                                "callback": function () {
                                                    window.location.reload()
                                                }
                                            }
                                        ]
                                    );

                                }
                            }
                        }
                    });
                }, 2000);
            }
        });
    };
    $('#btn-sync-onedrive').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).button('loading');

        Wpfd.syncOnedrive(function() {
            $btn.button('complete');
        });

        return false;
    });

    /**
     * Click to Sync with OneDrive
     */
    Wpfd.syncOnedriveBusiness = function(callback) {
        wpfd_status.addStatusLine('Synching Onedrive Business...');
        $.ajax({
            url: wpfd_var.wpfdajaxurl + '?action=onedriveBusinessSync',
            success: function (data) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_google_drive_sync_done});
                //window.location.reload();
                onedriveBusinessInterval = setInterval(function() {
                    $.ajax({
                        url: wpfd_var.wpfdajaxurl + '?action=onedrive_business_sync_status',
                        success: function(response) {
                            if (response.success) {
                                if (response.total === 0) {
                                    clearInterval(onedriveBusinessInterval);
                                    callback();
                                    // Queue stoped. Prompt user reload page or not?
                                    bootbox.dialog(
                                        wpfd_admin.msg_promtp_sync_reload_page,
                                        [
                                            {
                                                "label": _wpfd_text('Cancel')
                                            },
                                            {
                                                "label": _wpfd_text('Confirm'),
                                                "callback": function () {
                                                    window.location.reload()
                                                }
                                            }
                                        ]
                                    );

                                }
                            }
                        }
                    });
                }, 2000);
            }
        });
    };
    $('#btn-sync-onedrive-business').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).button('loading');

        Wpfd.syncOnedriveBusiness(function() {
            $btn.button('complete');
        });

        return false;
    });
    /**
     * Click to Sync with AWS
     */
    Wpfd.syncAws = function(callback) {
        wpfd_status.addStatusLine('Synching Amazon S3...');
        $.ajax({
            url: wpfd_var.wpfdajaxurl + '?action=awsSync',
            success: function (data) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_google_drive_sync_done});
                //window.location.reload();
                awsInterval = setInterval(function() {
                    $.ajax({
                        url: wpfd_var.wpfdajaxurl + '?action=aws_sync_status',
                        success: function(response) {
                            if (response.success) {
                                if (response.total === 0) {
                                    clearInterval(awsInterval);
                                    callback();
                                    // Queue stoped. Prompt user reload page or not?
                                    bootbox.dialog(
                                        wpfd_admin.msg_promtp_sync_reload_page,
                                        [
                                            {
                                                "label": _wpfd_text('Cancel')
                                            },
                                            {
                                                "label": _wpfd_text('Confirm'),
                                                "callback": function () {
                                                    window.location.reload()
                                                }
                                            }
                                        ]
                                    );

                                }
                            }
                        }
                    });
                }, 2000);
            }
        });
    };
    $('#btn-sync-aws').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).button('loading');
        Wpfd.syncAws(function() {
            $btn.button('complete');
        });
        return false;
    });

    /**
     * Click to Sync with Nextcloud
     */
    Wpfd.syncNextcloud = function(callback) {
        wpfd_status.addStatusLine('Synching Nextcloud...');
        $.ajax({
            url: wpfd_var.wpfdajaxurl + '?action=nextcloudSync',
            success: function (data) {
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_google_drive_sync_done});
                nextcloudInterval = setInterval(function() {
                    $.ajax({
                        url: wpfd_var.wpfdajaxurl + '?action=nextcloud_sync_status',
                        success: function(response) {
                            if (response.success) {
                                if (response.total === 0) {
                                    clearInterval(nextcloudInterval);
                                    callback();
                                    // Queue stoped. Prompt user reload page or not?
                                    bootbox.dialog(
                                        wpfd_admin.msg_promtp_sync_reload_page,
                                        [
                                            {
                                                "label": _wpfd_text('Cancel')
                                            },
                                            {
                                                "label": _wpfd_text('Confirm'),
                                                "callback": function () {
                                                    window.location.reload()
                                                }
                                            }
                                        ]
                                    );

                                }
                            }
                        }
                    });
                }, 2000);
            }
        });
    };
    $('#btn-sync-nextcloud').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).button('loading');
        Wpfd.syncNextcloud(function() {
            $btn.button('complete');
        });
        return false;
    });

    /**
     * Click on new category btn
     */
    Wpfd.initNewCategory = function() {
        $('#newcategory a').off('click').on('click', function (e) {
            e.preventDefault();
            var $newCategoryButton = $(e.target);
            var parentId = 0;
            var selectedCategory = $('#categorieslist li.dd-item.active');
            // Find parent category
            var selectedParentList = selectedCategory.parent(); // Travel to ol
            if (selectedParentList.attr('id') === 'categorieslist') {
                // Create new category on root
                parentId = 0;
            } else {
                parentId = selectedParentList.parent().data('id-category'); // Travel to parent of selected ol
            }
            var type = null;
            if ($newCategoryButton.hasClass('googleCreate')) {
                type = 'googleDrive';
            } else if ($newCategoryButton.hasClass('googleTeamDriveCreate')) {
                type = 'googleTeamDrive';
            } else if ($newCategoryButton.hasClass('dropboxCreate')) {
                type = 'dropbox';
            } else if ($newCategoryButton.hasClass('onedriveCreate')) {
                type = 'onedrive';
            } else if ($newCategoryButton.hasClass('onedriveBusinessCreate')) {
                type = 'onedrive_business';
            } else if ($newCategoryButton.hasClass('awsCreate')) {
                type = 'aws';
            } else if ($newCategoryButton.hasClass('nextcloudCreate')) {
                type = 'nextcloud';
            } else {
                type = 'wordpress';
            }

            Wpfd.createCategory(parentId, type);
            Wpfd.initNewCategory();
        });
    };
    Wpfd.initNewCategory();

    Wpfd.createCategory = function (parentId, type, context = false) {
        if (!wpfd_permissions.can_create_category) {
            bootbox.alert(wpfd_permissions.translate.wpfd_create_category);
            return false;
        }
        var addCategoryAjaxUrl, icon, default_new_category_name;
        switch (type) {
            case 'googleDrive':
                addCategoryAjaxUrl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonAddCategory&type=" + type;
                icon = '<i class="google-drive-icon wpfd-folder wpfd-liga">google_drive</i> ';
                default_new_category_name = wpfd_admin.new_google_drive_category_name;
                break;
            case 'googleTeamDrive':
                addCategoryAjaxUrl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonAddGoogleTeamDriveCategory&type=" + type;
                icon = '<i class="google-team-drive-icon wpfd-folder wpfd-liga">google_drive</i> ';
                default_new_category_name = wpfd_admin.new_google_team_drive_category_name;
                break;
            case 'dropbox':
                addCategoryAjaxUrl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonAddDropCategory&type=" + type;
                icon = '<i class="dropbox-icon wpfd-folder wpfd-liga">dropbox</i>';
                default_new_category_name = wpfd_admin.new_dropbox_category_name;
                break;
            case 'onedrive':
                addCategoryAjaxUrl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonAddOneDriveCategory&type=" + type;
                icon = '<i class="onedrive-icon wpfd-folder wpfd-liga">onedrive</i>';
                default_new_category_name = wpfd_admin.new_onedrive_category_name;
                break;
            case 'onedrive_business':
                addCategoryAjaxUrl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonAddOneDriveBusinessCategory&type=" + type;
                icon = '<i class="onedrive-business-icon wpfd-folder wpfd-liga">onedrive</i>';
                default_new_category_name = wpfd_admin.new_onedrive_business_category_name;
                break;
            case 'aws':
                addCategoryAjaxUrl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonAddAwsCategory&type=" + type;
                icon = '<i class="wpfd-aws-icon wpfd-folder wpfd-liga"></i>';
                default_new_category_name = wpfd_admin.new_aws_category_name;
                break;
            case 'nextcloud':
                addCategoryAjaxUrl = wpfd_var.wpfdajaxurl + "?action=wpfdAddonAddNextcloudCategory&type=" + type;
                icon = '<i class="wpfd-nextcloud-icon wpfd-folder wpfd-liga"></i>';
                default_new_category_name = wpfd_admin.new_nextcloud_category_name;
                break;
            case 'wordpress':
            default:
                addCategoryAjaxUrl = wpfdajaxurl + "task=category.addCategory";
                icon = '<i class="material-icons wpfd-folder">folder</i>';
                default_new_category_name = wpfd_admin.new_category_name;
                break;
        }
        // Prompt for new category name
        var create_category_prompt = bootbox.prompt('Please input new category name: ', 'Cancel', 'Create', function (new_name) {
            if (new_name === '' || new_name === null || typeof new_name === "undefined") {
                return false;
            }

            $.ajax({
                url: addCategoryAjaxUrl,
                type: 'POST',
                data: {parentId: parentId, name: new_name},
                beforeSend: function () {
                    $.gritter.add({text: wpfd_admin.msg_adding_category});
                }
            }).done(function (data) {
                var result = jQuery.parseJSON(data);
                if (result.response === true) {
                    var selectedCategory = $('#categorieslist li.dd-item.active');
                    // Find parent category
                    var selectedParentList;
                    var level = selectedCategory.data('level') || 0;

                    if (selectedCategory.length === 0) {
                        selectedParentList = $('#categorieslist');
                    } else {
                        if (!context) {
                            selectedParentList = selectedCategory.parent(); // Travel to ol
                        } else {
                            selectedParentList = $('#categorieslist li.dd-item.active > ol');
                            if (!selectedParentList.length) {
                                // Create child ol
                                var list = $('<ol/>').addClass('dd-list');

                                var margin = 0;
                                if (parseInt(level) > 0) {
                                    margin = parseInt(level) * 10;
                                    list.css('margin-left', '-' + margin.toString() + 'px');
                                }
                                selectedCategory.append(list);
                                selectedParentList = list;
                            }
                            if (!selectedCategory.children().first().hasClass('dd-collapse')) {
                                selectedCategory.prepend('<button class="dd-expand" data-action="expand" type="button">Expand</button>');
                                selectedCategory.prepend('<button class="dd-collapse" data-action="collapse" type="button">Collapse</button>');
                            }
                        }
                    }

                    link = '<li class="dd-item dd3-item new-category" data-id="' + result.datas.id_category + '"';
                    if (parseInt(level) > 0 && context) {
                        var padding = parseInt(level + 1) * 10;
                        link += ' style="padding-left: ' + padding + 'px"';
                    } else if (parseInt(level) > 0 && !context) {
                        var padding = parseInt(level) * 10;
                        link += ' style="padding-left: ' + padding + 'px"';
                    } else if (parseInt(level) === 0 && context) {
                        link += ' style="padding-left: 10px"';
                    }
                    var newLevel = level;
                    if (context) {
                        newLevel = level + 1;
                    }
                    link += ' data-level="' + newLevel + '"';
                    link += ' data-type="' + type + '"';

                    link += ' data-id-category="' + result.datas.id_category + '">' +
                        '<div class="dd-handle dd3-handle">' + icon + '</div>' +
                        '<div class="dd-content dd3-content dd-handle"';
                    if (parseInt(level) > 0 && context) {
                        link += ' style="margin-left: -' + (parseInt(level) * 10 + 15) + 'px;padding-left: ' + (parseInt(level) * 10 + 65) + 'px"';
                    } else if (parseInt(level) === 0) {
                        link += ' style="margin-left: -25px;padding-left: 75px"';
                    }

                    link += '>';
                    if ($('.dd3-content .countfile').length) {
                        link += '<span class="countfile"><span class="count_badge">0</span></span>';
                    }
                    link += '<a href="" class="t">' +
                        '<span class="title">' + result.datas.name + '</span>' +
                        '</a>' +
                        '</div>';
                    if (wpfd_var.new_category_position == 'end') {
                        $(link).appendTo(selectedParentList);
                    } else if (wpfd_var.new_category_position == 'top') {
                        $(link).prependTo(selectedParentList);
                    }
                    //initContextMenu($(link).find('.dd-content'), '.wpfd-folder-menu');
                    Wpfd.initMenu();
                    $('#wpfd-categories-col #categorieslist li[data-id-category=' + result.datas.id_category + '] > .dd-content').click();
                    $('#insertcategory').show();
                    $('.gritter-item-wrapper ').remove();
                    $.gritter.add({text: wpfd_admin.msg_add_category});
                    setTimeout(Wpfd.saveTemp, 3000);
                    catDroppable();
                    $('#wpfd-categories-col #categorieslist li[data-id-category=' + result.datas.id_category + '] > .dd-content').trigger('wpfd_category_created');
                } else {
                    bootbox.alert(result.response);
                }
            });
        }, default_new_category_name);
        // Select all input after bootbox shown
        create_category_prompt.find("input[type=text]").select();
        // Prevent Enter event
        create_category_prompt.find("input[type=text]").on('keypress', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                create_category_prompt.find('.button-primary').trigger('click');
                return false;
            }
            if (e.keyCode === 27) { // Escape
                e.preventDefault();
                create_category_prompt.modal('hide');
                return false;
            }
        });
    };

    function toMB(mb) {
        return mb * 1024 * 1024;
    }

    var allowedExt = wpfd_admin.allowed;
    allowedExt = allowedExt.split(',');
    allowedExt.sort();
    // Init the Version Uploader
    var versionUploaderInit = function(fileId, categoryId) {
        var versionUploader = new Resumable({
            target: wpfdajaxurl + 'task=files.version',
            query: {
                id_file: fileId,
                id_category: categoryId
            },
            fileParameterName: 'file_upload',
            simultaneousUploads: 2,
            maxFiles: 1,
            maxFileSize: toMB(wpfd_admin.maxFileSize),
            chunkSize: wpfd_admin.serverUploadLimit - 50 * 1024, // Reduce 50KB to avoid error
            forceChunkSize: true,
            fileType: allowedExt,
            maxFilesErrorCallback: function (file) {
                bootbox.alert(_wpfd_text('Too many files') + '!');
            },
            maxFileSizeErrorCallback: function (file) {
                bootbox.alert(file.name + ' ' + _wpfd_text('is too large, please upload file(s) less than ') + wpfd_admin.maxFileSize + 'Mb!');
            },
            fileTypeErrorCallback: function (file) {
                bootbox.alert(file.name + ' cannot upload!<br/><br/>' + _wpfd_text('This type of file is not allowed to be uploaded. You can add new file types in the plugin configuration'));
            },
            generateUniqueIdentifier: function (file, event) {
                var relativePath = file.webkitRelativePath || file.fileName || file.name;
                var size = file.size;
                var prefix = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                return (prefix + size + '-' + relativePath.replace(/[^0-9a-zA-Z_-]/img, ''));
            }
        });

        if (!versionUploader.support) {
            bootbox.alert(_wpfd_text('Your browser does not support HTML5 file uploads') + '!');
        }

        if (typeof (willUploadVersion) === 'undefined') {
            var willUploadVersion = true;
        }

        versionUploader.on('filesAdded', function (files) {
            if (!wpfd_permissions.can_edit_category) {
                bootbox.alert(wpfd_permissions.translate.wpfd_edit_category);
                return false;
            }

            $('#preview #dropbox_version .upload').addClass('hide');

            files.forEach(function (file) {
                $('#preview #dropbox_version .progress').addClass(file.uniqueIdentifier);
                $('#preview #dropbox_version .progress').removeClass('hide');
            });

            if (files.length > 0) {
                versionUploader.opts.query = {
                    id_file: fileId,
                    id_category: categoryId
                };

                if (willUploadVersion) versionUploader.upload();
            }
        });
        versionUploader.on('fileProgress', function (file) {
            $('#preview #dropbox_version .progress.' + file.uniqueIdentifier)
                .find('.bar').width(Math.floor(file.progress() * 100) + '%');
        });
        versionUploader.on('fileError', function (file, msg) {
            $('#preview #dropbox_version .progress').removeClass(file.uniqueIdentifier);
            $('#preview #dropbox_version .progress').addClass('hide');

            $.gritter.add({
                text: file.fileName + ' ' + _wpfd_text('error while uploading') + '!',
                class_name: 'error-msg'
            });
        });
        versionUploader.on('fileSuccess', function (file, res) {
            $('#preview #dropbox_version .progress').removeClass(file.uniqueIdentifier);
            // $('#dropbox_version .progress').addClass('hide');

            var response = JSON.parse(res);
            if (typeof (response) === 'string') {
                bootbox.alert('<div>' + response + '</div>');
                return false;
            }

            if (response.response !== true) {
                if (typeof (response.response) !== 'undefined') {
                    bootbox.alert(response.response);
                }
                return false;
            }

            if (typeof (response.datas.version) !== "undefined") {
                // Update version field
                $('[name="version"]').val(response.datas.version);
                // Update stored file params
                var savedFileSettings = window.localStorage.getItem('_wpfd_current_file_param');
                savedFileSettings = unserialize(savedFileSettings);
                savedFileSettings.version = response.datas.version;

                window.localStorage.setItem('_wpfd_current_file_param', $.param(savedFileSettings));
            }

            $.gritter.add({
                text: file.fileName + ' ' + _wpfd_text('uploaded successfully') + '!'
            });
        });
        versionUploader.on('complete', function () {
            //$('#dropbox_version .progress').delay(500).fadeIn(500).hide(0, function () {
            $('#preview #dropbox_version .progress').addClass('hide');
            $('#preview #dropbox_version .upload').removeClass('hide');
            $('#preview #dropbox_version .progress .bar')
                .width('0');
            $("#preview #upload_input_version").val('');
            loadVersions(fileId, categoryId);
            //Wpfd.updatePreview(null, fileId);
            //});
        });
        versionUploader.assignBrowse($('#preview #upload_button_version'));
        versionUploader.assignDrop($('#preview #fileversion'));

        $('#fileversion').on('drop', function () {
            $(this).removeClass('hover');
            $('.wpfd_row.editor').removeClass('drag_drop');
        });
        // Show overlay on drag to #preview
        $('#fileversion').on("dragenter", function (e) {
            if (e.target === this) {
                return;
            }

            $('#fileversion').addClass('hover');
            $('.wpfd_row.editor').addClass('drag_drop');
            $('.wpfd_row.editor').addClass('file_version_drag_drop');
            $('#wpfd-drop-overlay').addClass('hide');
            $('#wpfd-file-version-drop-overlay').show();
        });
        $(document).on("dragleave", function (e) {
            // Detect is real dragleave
            if (e.originalEvent.pageX !== 0 || e.originalEvent.pageY !== 0) {
                return false;
            }
            $('#preview #fileversion').removeClass('hover');
            $('.wpfd_row.editor').removeClass('drag_drop');
            $('.wpfd_row.editor').addClass('file_version_drag_drop');
            $('#wpfd-drop-overlay').addClass('hide');
        });
    }
    // Initial update category tree
    Wpfd.reloadCategoryTree = function() {
        // Reload category tree
        var upload_category_id = window.localStorage.getItem('wpfdSelectedCatId');

        $.ajax({
            url: wpfdajaxurl + 'task=categories.listTreeView',
            method: 'POST',
            data: {
                refresh: true
            },
            success: function (content) {
                $('#categorieslist').empty();
                $('#categorieslist').append(content.data);
                /* Load nestable */
                wpfdLoadNestable();
                /* Init menu actions */
                Wpfd.initMenu();
                Wpfd.caculateTree();
                catDroppable();
                let catItems = $('#categorieslist li.dd-item');
                if (catItems.length) {
                    catItems.each(function () {
                        if ( $(this).hasClass('dd-collapsed') || $(this).find('.dd-list').length ) {
                            $(this).prepend('<button class="dd-expand" data-action="expand" type="button">Expand</button>');
                            $(this).prepend('<button class="dd-collapse" data-action="collapse" type="button">Collapse</button>');
                        }
                    });
                    catItems.removeClass('active');
                    $('#categorieslist li.dd-item[data-id="' + upload_category_id + '"]').addClass('active');
                    $(document).trigger('wpfd_context_folder_refresh');
                }
            }
        });
        rloading('#wpreview');

        wpfd_status.close();
        Wpfd.updatePreview();
    }

    // Init the uploader
    Wpfd.uploader = new Resumable({
        target: wpfdajaxurl + 'task=files.upload',
        query: {
            id_category: $('input[name=id_category]').val()
        },
        fileParameterName: 'file_upload',
        simultaneousUploads: 1,
        maxChunkRetries: 1,
        maxFileSize: toMB(wpfd_admin.maxFileSize),
        maxFileSizeErrorCallback: function (file) {
            bootbox.alert(file.name + ' ' + _wpfd_text('is too large, please upload file(s) less than ') + wpfd_admin.maxFileSize + 'Mb!');
        },
        chunkSize: wpfd_admin.serverUploadLimit - 50 * 1024, // Reduce 50KB to avoid error
        forceChunkSize: true,
        fileType: allowedExt,
        fileTypeErrorCallback: function (file) {
            bootbox.alert(file.name + ' cannot upload!<br/><br/>' + _wpfd_text('This type of file is not allowed to be uploaded. You can add new file types in the plugin configuration'));
        },
        generateUniqueIdentifier: function (file, event) {
            if (file.hasOwnProperty('uniqueIdentifier')) {
                return file.uniqueIdentifier;
            }
            var relativePath = file.webkitRelativePath || file.fileName || file.name;
            var size = file.size;
            var prefix = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            var catId = $('input[name=id_category]').val();
            if (file.hasOwnProperty('catId')) {
                catId =  file.catId
            }
            return (catId + '|||' + prefix + size + '-' + relativePath.replace(/[^0-9a-zA-Z_-]/img, ''));
        }
    });

    Wpfd.uploader.on('fileEmpty', function() {
        bootbox.alert(_wpfd_text('Please select single category at once. Made sure you don\'t select empty folder!') + '!');
    });

    if (!Wpfd.uploader.support) {
        bootbox.alert(_wpfd_text('Your browser does not support HTML5 file uploads') + '!');
    }

    if (typeof (willUpload) === 'undefined') {
        var willUpload = true;
    }

    function processFiles(files) {
        // Do not run uploader if no files added or upload same files again
        if (files.length > 0) {
            files.forEach(function (file) {
                if (typeof (file.relativePath) !== 'undefined' && file.relativePath.indexOf('/') !== -1 && !Wpfd.directoryUpload) {
                    Wpfd.directoryUpload = true;
                }
                Wpfd.log(file.uniqueIdentifier + ' added to upload queue!');
                wpfd_status.progressAdd(file.uniqueIdentifier, file.fileName, $('input[name=id_category]').val(), Wpfd.directoryUpload);
            });
        }
    }

    Wpfd.uploader.on('filesAdded', function (files) {
        if (!wpfd_permissions.can_edit_category) {
            bootbox.alert(wpfd_permissions.translate.wpfd_edit_category);
            return false;
        }
        processFiles(files);
    });

    Wpfd.uploader.on('createFolders', function (files) {
        var currentRootCat = $('input[name=id_category]').val();
        // Prepare category tree
        var paths = files.map(function(file) {
            if (file.hasOwnProperty('catId')) {
                currentRootCat = file.catId;
            }
            var filePath = (file.hasOwnProperty('relativePath')) ? file.relativePath : file.webkitRelativePath;
            var namePos = filePath.lastIndexOf(file.name);
            return filePath.substr(0,namePos);
        });
        // get unique value (not empty value)
        paths = paths.filter( function(item, i, ar) { return item && ar.indexOf(item) === i } );
        if (paths.length > 0) {
            var categoryType = $('#categorieslist li[data-id-category="' + currentRootCat + '"]').data('type');
            // Send ajax to initial categories
            $.ajax({
                url: wpfdajaxurl + 'task=categories.createCategoriesDeep',
                data: {
                    paths: paths.join('|'),
                    category_id: currentRootCat,
                    type: categoryType
                },
                method: 'POST',
                success: function (data) {
                    if (data.success) {
                        Wpfd.reloadCategoryTree();
                    }
                }
            });
        }
    });

    Wpfd.uploader.on('fileProgress', function (file) {
        wpfd_status.progressUpdate(file.uniqueIdentifier, Math.floor(file.progress() * 100) + '%');
    });

    Wpfd.uploader.on('fileSuccess', function (file, res) {
        wpfd_status.progressDone(file.uniqueIdentifier);
        try {
            var response = JSON.parse(res);

            if (response.response === false && typeof(response.datas) !== 'undefined') {
                if (typeof(response.datas.code) !== 'undefined' && response.datas.code > 20) {
                    bootbox.alert('<div>' + response.datas.message + '</div>');
                    return false;
                }
            }
            if (typeof(response) === 'string') {
                bootbox.alert('<div>' + response + '</div>');
                return false;
            }

            if (response.response !== true) {
                bootbox.alert(response.response);
                return false;
            }

            var catId = response.datas.id_category || file.uniqueIdentifier.split('|||').slice(0, 1).shift();
            updateCatCount(catId, 1);
            $.gritter.add({
                text: file.fileName + ' ' + _wpfd_text('uploaded successfully') + '!'
            });
        } catch (e) {
            console.log(e);
            console.log(res);
        }
    });

    Wpfd.uploader.on('fileError', function (file, msg) {
        wpfd_status.progressError(file.uniqueIdentifier);

        $.gritter.add({
            text: file.fileName + ' ' + _wpfd_text('error while uploading') + '!',
            class_name: 'error-msg'
        });
    });

    Wpfd.uploader.on('complete', function () {
        if (Wpfd.directoryUpload) {
            //  window.localStorage.removeItem('uploading_paths');
            Wpfd.reloadCategoryTree();
            Wpfd.directoryUpload = false;
        } else {
            rloading('#wpreview');
            wpfd_status.close();
            Wpfd.updatePreview();
        }

    });

    /**
     * Init the dropbox
     **/
    function initDropbox(dropbox) {
        dropbox.filedrop({
            paramname: 'pic',
            fallback_id: 'upload_input',
            maxfiles: 30,
            maxfilesize: Wpfd.maxfilesize,
            queuefiles: 2,
            data: {
                id_category: function () {
                    return $('input[name=id_category]').val();
                }
            },
            url: wpfdajaxurl + 'task=files.upload',

            uploadFinished: function (i, file, response) {
                if (response.response === true) {
                    $.data(file).addClass('done');
                    $.data(file).find('img').data('id-file', response.datas.id_file);
                } else {
                    bootbox.alert(response.response);
                    $.data(file).remove();
                }
            },

            error: function (err, file) {
                switch (err) {
                    case 'BrowserNotSupported':
                        bootbox.alert(_wpfd_text('Your browser does not support HTML5 file uploads', 'Your browser does not support HTML5 file uploads!'));
                        break;
                    case 'TooManyFiles':
                        bootbox.alert(_wpfd_text('Too many files') + '!');
                        break;
                    case 'FileTooLarge':
                        bootbox.alert(file.name + ' ' + _wpfd_text('is too large', 'is too large') + '!');
                        break;
                    default:
                        break;
                }
            },

            // Called before each upload is started
            beforeEach: function (file) {
                if (!wpfd_permissions.can_edit_category) {
                    bootbox.alert(wpfd_permissions.translate.wpfd_edit_category);
                    return false;
                }
            },

            uploadStarted: function (i, file, len) {
                var preview = $('<div class="wpfd_process_full" style="display: block;">' +
                    '<div class="wpfd_process_run" data-w="0" style="width: 0%;"></div>' +
                    '</div>');

                var reader = new FileReader();

                // Reading the file as a DataURL. When finished,
                // this will trigger the onload function above:
                reader.readAsDataURL(file);

                $('#preview .restable').after(preview);
//                        $('#dropbox').before(preview);

                // Associating a preview container
                // with the file, using jQuery's $.data():

                $.data(file, preview);
            },

            progressUpdated: function (i, file, progress) {
                $.data(file).find('.wpfd_process_run').width(progress + '%');
            },

            afterAll: function () {
                $('#preview .progress').delay(300).fadeIn(300).hide(300, function () {
                    $(this).remove();
                });
                $('#preview .uploaded').delay(300).fadeIn(300).hide(300, function () {
                    $(this).remove();
                });
                $('#preview .file').delay(1200).show(1200, function () {
                    $(this).removeClass('done placeholder');
                });
                Wpfd.updatePreview();
                $('.gritter-item-wrapper ').remove();
                $.gritter.add({text: wpfd_admin.msg_upload_file});
            },
            rename: function (name) {
                ext = name.substr(name.lastIndexOf('.'), name.length);
                name = name.substr(0, name.lastIndexOf('.'));

                var uint8array = new TextEncoderLite().encode(name);

                base64 = fromByteArray(uint8array);
                base64 = base64.replace("/", "|");
                return base64 + ext;
            }
        });
    }

    if (_wpfd_text('close_categories') === '1') {
        $('.nested').nestable('collapseAll');
    }

    if (typeof(window.parent.tinyMCE) !== 'undefined') {
        var content = "";
        if (window.parent.tinyMCE.activeEditor !== null && window.parent.tinyMCE.activeEditor.selection) {
            content = window.parent.tinyMCE.activeEditor.selection.getContent();
        }
        var file = content.match('<img.*data\-file="([0-9a-zA-Z_]+)".*?>');
        var category = content.match('<img.*data\-category="([0-9]+)".*?>');
        var file_category = content.match('<img.*data\-category="([0-9]+)".*?>');
        if (window.parent.selectedCatId !== undefined) {
            file_category = [0, window.parent.selectedCatId];
        }
        if (window.parent.selectedFileId !== undefined) {
            file = [0, window.parent.selectedFileId];
        }
        if (window.parent.selectedFileId === undefined && window.parent.selectedCatId !== undefined) {
            category = [0, window.parent.selectedCatId];
        }
        if (file !== null && file_category !== null) {
            $('#categorieslist li').removeClass('active');
            $('#categorieslist li[data-id-category="' + file_category[1] + '"]').addClass('active');
            $('input[name=id_category]').val(file_category[1]);
            Wpfd.updatePreview(file_category[1], file[1]);
        } else if (category !== null) {
            $('#categorieslist li').removeClass('active');
            $('#categorieslist li[data-id-category="' + category[1] + '"]').addClass('active');
            $('input[name=id_category]').val(category[1]);
            Wpfd.updatePreview(category[1]);
            loadGalleryParams();
        } else {
            var cate = $.cookie('wpfd_selected_category');

            if (cate !== null) {
                $('#categorieslist li').removeClass('active');
                $('#categorieslist li[data-id-category="' + cate + '"]').addClass('active');
                $('input[name=id_category]').val(cate);
                setTimeout(function () {
                    Wpfd.updatePreview(cate);
                    loadGalleryParams();
                }, 100);

            } else {
                Wpfd.updatePreview();
                loadGalleryParams();
            }
        }
    }

    /**
     * Init the dropbox
     **/
    function initDropboxVersion(dropbox) {
        dropbox.filedrop({
            paramname: 'pic',
            fallback_id: 'upload_input_version',
            maxfiles: 1,
            maxfilesize: Wpfd.maxfilesize,
            queuefiles: 1,
            data: {
                id_file: function () {
                    return $('.file.selected').data('id-file');
                },
                id_category: function () {
                    return $('input[name=id_category]').val();
                }
            },
            url: wpfdajaxurl + 'task=files.version',

            uploadFinished: function (i, file, response) {

                if (response.response === true) {

                } else {
                    bootbox.alert(response.response);

                    $('#dropbox_version .progress').addClass('hide');
                    $('#dropbox_version .upload').removeClass('hide');
                }
            },

            error: function (err, file) {
                switch (err) {
                    case 'BrowserNotSupported':
                        bootbox.alert(_wpfd_text('Your browser does not support HTML5 file uploads'));
                        break;
                    case 'TooManyFiles':
                        bootbox.alert(_wpfd_text('Too many files') + '!');
                        break;
                    case 'FileTooLarge':
                        bootbox.alert(file.name + ' ' + _wpfd_text('is too large') + '!');
                        break;
                    default:
                        break;
                }
            },

            // Called before each upload is started
            beforeEach: function (file) {
//                        if(!file.type.match(/^image\//)){
//                                bootbox.alert(_wpfd_text('Only images are allowed','Only images are allowed')+'!');
//                                return false;
//                        }
            },

            uploadStarted: function (i, file, len) {

                // Associating a preview container
                // with the file, using jQuery's $.data():
                $('#dropbox_version .upload').addClass('hide');
                $('#dropbox_version .progress').removeClass('hide');
//                        $.data(file,preview);
            },

            progressUpdated: function (i, file, progress) {
                $('#dropbox_version .bar').width(progress + '%');
            },

            afterAll: function () {
                $('#dropbox_version .progress').addClass('hide');
                $('#dropbox_version .upload').removeClass('hide');
                id_file = $('.file.selected').data('id-file');
                $("#upload_input_version").val('');
                Wpfd.updatePreview(null, id_file);
            }
        });
    }

    if (typeof l10n !== 'undefined') {
        $('#wpfd-jao').wpfd_jaofiletree({
            script: wpfdajaxurl + "task=category.listdir",
            usecheckboxes: 'files',
            showroot: '/'
        });
    }

    // init color field
    function initColor() {
        $('.wp-color-field:not(.bgcolor, .bgdownloadlink)').minicolors({position: 'bottom left'});

        // Transparent color option
        $('.wp-color-field.bgcolor, .wp-color-field.bgdownloadlink').minicolors({
            position: 'bottom left',
            opacity: true,
            value: '#fff',
            change: function (hex, opacity) {
                var opacitySaved = $(this).data('opacity').toString();
                if (opacity === opacitySaved && opacitySaved !== '1.00') {
                    opacitySaved = '1.00';
                    opacity = '1.00';
                    $(this).attr('data-opacity', '1.00').trigger('change');
                    $(this).siblings('.minicolors-swatch').find('.minicolors-swatch-color').css({'opacity': '1'});
                    $(this).siblings('.minicolors-panel').find('.minicolors-opacity-slider .minicolors-picker').css({'top': '0'});
                }

                if ((!hex || hex === '') && $(this).hasClass('bgcolor')) {
                    color = 'rgba(255, 255, 255, 0)';
                } else {
                    color = $(this).minicolors('rgbaString');
                }
            },
            hide : function() {
                if (typeof color !== 'undefined') {
                    $(this).val(color);
                }
            },
            show : function() {
            }
        });
    }

    function loading(e) {
        $(e).addClass('dploadingcontainer');
        $(e).append('<div class="dploading"></div>');
    }

    function rloading(e) {
        $(e).removeClass('dploadingcontainer');
        $(e).find('div.dploading').remove();
    }

    // file in category shortcode
    $('#file_cat_id,#file_cat_ordering,#file_cat_ordering_direct,#file_cat_number,#show_categories,#file_cate_theme').on('change', function () {
        shortcode_file_cat_generator();
    });
    // Hide show_categories on load
    var show_categories_wrapper = $('#show_categories').parent().parent();
    if (show_categories_wrapper.length && $('#file_cat_id').length) {
        if ($('#file_cat_id').val().toString() === '0') {
            show_categories_wrapper.show();
        } else {
            show_categories_wrapper.hide();
        }
    }
    function shortcode_file_cat_generator() {
        var file_cat_id = $('#file_cat_id').val(),
            file_cat_ordering = $('#file_cat_ordering').val(),
            file_cat_ordering_direct = $('#file_cat_ordering_direct').val(),
            file_cat_number = $('#file_cat_number').val(),
            file_cat_theme = $('#file_cate_theme').val();

        var shortcode_file_cat = '[wpfd_category ';
        if (parseInt(file_cat_id) !== 0) {
            shortcode_file_cat += 'id="' + file_cat_id + '"';
        }
        if ($('#file_cat_id').val().toString() === '0') {
            show_categories_wrapper.show();
            shortcode_file_cat += ' show_categories="' + $('#show_categories').val() + '"';
        } else {
            show_categories_wrapper.hide();
        }

        shortcode_file_cat += ' order="' + file_cat_ordering +
            '" direction="' + file_cat_ordering_direct + '" number="' + file_cat_number + '" theme="' + file_cat_theme + '"]';

        $('#file_shortcode_generator').empty();
        $('#file_shortcode_generator').val(shortcode_file_cat);
    }

    function wpfdLoadNestable() {
        $('.nested').nestable({
            maxDepth: 16,
            includeContent: true,
            effect: {
                animation: 'fade-up',
                time: 'slow'
            },
            onClick: function (l, e, p) {
                var id_category = $(e).data('id-category');
                // Detect double click
                wpfd_category_click++;
                if (wpfd_category_click === 1) {
                    setTimeout(function () {
                        if (wpfd_category_click === 1) {
                            $('input[name=id_category]').val(id_category);
                            // Check file editing is open
                            if ($('.wpfd_row.editor').length) {
                                if (file_params_change) {
                                    // Prompt for unsave changes
                                    bootbox.confirm(
                                        wpfd_admin.msg_prompt_file_params_changes_not_saved,
                                        function (result) {
                                            if (result) {
                                                loadCategoryOnClick(id_category, true);
                                            }
                                        }
                                    );
                                } else {
                                    loadCategoryOnClick(id_category, true);
                                }
                            } else {
                                // Close other folder settings
                                if ($('#rightcol').hasClass('opened')) {
                                    if (category_params_change) {
                                        // Prompt for unsave changes
                                        bootbox.confirm(
                                            wpfd_admin.msg_prompt_category_params_changes_not_saved,
                                            function (result) {
                                                if (result) {
                                                    loadCategoryOnClick(id_category, true);
                                                }
                                            }
                                        );
                                    } else {
                                        loadCategoryOnClick(id_category, true);
                                    }
                                } else {
                                    loadCategoryOnClick(id_category);
                                }
                            }
                        } else {
                            // Open rename prompt
                            Wpfd.renameFolderPrompt(id_category);
                        }

                        wpfd_category_click = 0;
                    }.bind(id_category, category_params_change), 300);
                }

                return false;
            },
            afterDragStop: function(l, e, p) {
                Wpfd.caculateTree();
            },
            callback: function (event, e) {
                var isCloudItem = $(e).find('div.dd3-handle i.google-drive-icon').length;
                var isGoogleTeamDriveItem = $(e).find('div.dd3-handle i.google-team-drive-icon').length;
                var isDropboxItem = $(e).find('div.dd3-handle i.dropbox-icon').length;
                var isOneDriveItem = $(e).find('div.dd3-handle i.onedrive-icon').length;
                var isOneDriveBusinessItem = $(e).find('div.dd3-handle i.onedrive-business-icon').length;
                var isAwsItem = $(e).find('div.dd3-handle i.wpfd-aws-icon').length;
                var isNextcloudItem = $(e).find('div.dd3-handle i.wpfd-nextcloud-icon').length;
                var itemChangeType = 'default';
                if (isCloudItem > 0) {
                    itemChangeType = 'googleDrive';
                } else if (isGoogleTeamDriveItem > 0) {
                    itemChangeType = 'googleTeamDrive';
                } else if (isDropboxItem > 0) {
                    itemChangeType = 'dropbox';
                } else if (isOneDriveItem > 0) {
                    itemChangeType = 'onedrive';
                } else if (isOneDriveBusinessItem > 0) {
                    itemChangeType = 'onedrive_business';
                } else if (isOneDriveBusinessItem > 0) {
                    itemChangeType = 'onedrive_business';
                } else if (isAwsItem > 0) {
                    itemChangeType = 'aws';
                } else if (isNextcloudItem > 0) {
                    itemChangeType = 'nextcloud';
                }

                pk = $(e).data('id-category');
                if ($(e).prev('li').length === 0) {
                    position = 'first-child';
                    if ($(e).parents('li').length === 0) {
                        //root
                        ref = 0;
                    } else {
                        ref = $(e).parents('li').data('id-category');
                    }
                } else {
                    position = 'after';
                    ref = $(e).prev('li').data('id-category');
                }

                $.ajax({
                    url: wpfdajaxurl + "task=category.changeOrder&pk=" + pk + "&position=" + position + "&ref=" + ref + "&dragType=" + itemChangeType + "&security=" + wpfd_var.wpfdsecurity,
                    type: "POST",
                    data: {},
                    dataType: 'json'
                }).done(function (result) {
                    //result = jQuery.parseJSON(data);
                    if (result.response === true) {
                        $('.gritter-item-wrapper ').remove();
                        $.gritter.add({text: wpfd_admin.msg_move_category});
                    } else {
                        bootbox.alert(result.response);
                    }
                });
            }
        });
    }

    $('#wpfd-container-config').tooltip();

    $(".widefat #select_all").click(function () {
        $('input:checkbox').not(this).prop('checked', this.checked);
    });
    // Close modal on click on backdrop
    $(document).on('click', '.modal-backdrop, .bootbox .button', function() {
        $('.modal-backdrop').remove();
        $(".bootbox").hide().modal("hide");
        bootbox.hideAll();
    });

    function wpfd_admin_init_pagination($this, id_category, pagination_page = 0) {
        pagination_page = parseInt(pagination_page);
        if (!$this.length && pagination_page === 0) {
            return;
        }
        var $                = jQuery;
        var number           = $this.find('a:not(.current)');
        var current_category = (typeof(id_category) === "undefined" && id_category === null) ? $('input[name=id_category]').val() : id_category;
        number.unbind('click').bind('click', function () {
            var page_number         = $(this).attr('data-page');
            var current_sourcecat   = $(this).attr('data-sourcecat');
            if (typeof page_number !== 'undefined') {
                var ordering            = $('#wpfd_file_category_ordering').val() ? $('#wpfd_file_category_ordering').val() : 'title';
                var orderingDirection   = $('#wpfd_file_category_ordering_direction').val() ? $('#wpfd_file_category_ordering_direction').val() : 'asc';
                var page_limit          = $('#wpfd_file_category_pagination_number').val() ? $('#wpfd_file_category_pagination_number').val() : '10';
                var params = $.param({
                    task: 'files.display',
                    view: 'files',
                    id_category: id_category,
                    id: current_category,
                    rootcat: current_sourcecat,
                    page: page_number,
                    orderCol: ordering,
                    orderDir: orderingDirection,
                    page_limit: page_limit
                });

                // Handle pagination
                wpfd_admin_handle_pagination(params, id_category, page_number, true);
            }
        });

        // Refresh pagination
        if (pagination_page > 0 && (parseInt(id_category) === parseInt($('input[name=id_category]').val()))) {
            var refresh_ordering            = $('#wpfd_file_category_ordering').val() ? $('#wpfd_file_category_ordering').val() : 'title';
            var refresh_orderingDirection   = $('#wpfd_file_category_ordering_direction').val() ? $('#wpfd_file_category_ordering_direction').val() : 'asc';
            var refresh_page_limit          = $('#wpfd_file_category_pagination_number').val() ? $('#wpfd_file_category_pagination_number').val() : '10';
            var refresh_params = $.param({
                task: 'files.display',
                view: 'files',
                id_category: id_category,
                id: current_category,
                rootcat: current_category,
                page: pagination_page,
                orderCol: refresh_ordering,
                orderDir: refresh_orderingDirection,
                page_limit: refresh_page_limit
            });

            // Handle pagination
            wpfd_admin_handle_pagination(refresh_params, id_category, pagination_page);
        }
    }

    function wpfd_admin_handle_pagination(params, id_category, page, click = false) {
        if (parseInt(id_category) <= 0) {
            return;
        }

        $('#preview').contents().remove();
        loading('#wpreview');

        // List files
        $.ajax({
            url: wpfdajaxurl + params,
            type: "GET",
            beforeSend: function () {
                $('html, body').animate({scrollTop: $("#wpreview").offset().top}, 'fast');
            }
        }).success(function (content) {
            $('#wpfd_filter_catid').val(id_category);
            $(content).hide().appendTo('#preview').fadeIn(200);

            if (wpfd_permissions.can_edit_category) {
                var remote_file = (_wpfd_text('add_remote_file') == '1' && !isCloudCategory()) ? '<a href="" id="add_remote_file" class="ju-button ju-v3-button">' + _wpfd_text('Add remote file') + '</a> ' : '';
                var select_files = '<span id="upload_files_button" class="ju-button ju-v3-button" style="margin-left: 4px">' + _wpfd_text('Select files') + '</span>';
                var select_folders_name = (typeof (_wpfd_text('Select a folder')) !== "undefined") ? _wpfd_text('Select a folder') : 'Select a folder';
                $('<div id="file_dropbox"><span class="message">' + _wpfd_text('Drag & Drop your Document here') + '</span><input class="hide" type="file" id="upload_input" multiple="">' + remote_file + '<span id="upload_button" class="ju-button ju-v3-button">' + select_folders_name + '</span>' + select_files + '</div><div class="clr"></div>').appendTo('#preview');
                $('#add_remote_file').on('click', function (e) {

                    var allowed = wpfd_admin.allowed.split(',');
                    allowed.sort();
                    var allowed_select = '<select id="wpfd-remote-type">';
                    $.each(allowed, function (i, v) {
                        allowed_select += '<option value="' + v + '">' + v + '</option>';
                    });
                    allowed_select += '</select>';
                    bootbox.dialog('<div class="">  ' +
                        '<div class="form-horizontal wpfd-remote-form"> ' +
                        '<div class="control-group"> ' +
                        '<label class=" control-label" for="wpfd-remote-title">'+ _wpfd_text('Title') + '</label> ' +
                        '<div class="controls"> ' +
                        '<input id="wpfd-remote-title" name="wpfd-remote-title" type="text" placeholder="'+ _wpfd_text('Title') + '" class=""> ' +
                        '</div> ' +
                        '</div> ' +
                        '<div class="control-group"> ' +
                        '<label class="control-label" for="wpfd-remote-url">'+ _wpfd_text('Remote URL') + '</label> ' +
                        '<div class="controls">' +
                        '<input id="wpfd-remote-url" name="wpfd-remote-url" type="text" placeholder="'+ _wpfd_text('URL') + '" class=""> ' +
                        '</div> </div>' +
                        '<div class="control-group"> ' +
                        '<label class="control-label" for="wpfd-remote-type">'+ _wpfd_text('File Type') + '</label> ' +
                        '<div class="controls">' +
                        allowed_select +
                        '</div> </div>' +
                        '</div>  </div>',
                        [{
                            "label": _wpfd_text('Save'),
                            "class": "button-primary",
                            "callback": function () {
                                var category_id = $('input[name=id_category]').val();
                                var remote_title = $('#wpfd-remote-title').val();
                                var remote_url = $('#wpfd-remote-url').val();
                                var remote_type = $('#wpfd-remote-type').val();
                                $.ajax({
                                    url: wpfdajaxurl + "task=files.addremoteurl&id_category=" + category_id,
                                    data: {
                                        remote_title: remote_title,
                                        remote_url: remote_url,
                                        remote_type: remote_type
                                    },
                                    type: "POST"
                                }).done(function (data) {
                                    result = $.parseJSON(data);
                                    if (result.response === true) {
                                        updateCatCount(category_id, 1);
                                        Wpfd.updatePreview();
                                    } else {
                                        bootbox.alert(result.response);
                                    }
                                });
                            }
                        }, {
                            "label": _wpfd_text('Cancel'),
                            "class": "s",
                            "callback": function () {

                            }
                        }]
                    );
                    return false;
                });
            }

            $('#preview .restable').restable({
                type: 'hideCols',
                priority: {0: 'persistent', 1: 3, 2: 'persistent'},
                hideColsDefault: [4, 5]
            });

            Wpfd.showhidecolumns();
            $('#preview').sortable('refresh');

            initDeleteBtn();
            $('#preview input[name="restable-toggle-cols"]').click(function (e) {
                setcookie_showcolumns();
            });

            /** Init ordering **/
            $('#preview .wpfd_tbl .head a').click(function (e) {
                e.preventDefault();
                Wpfd.updatePreview(null, null, $(this).data('ordering'), $(this).data('direction'));

                if ($(this).data('direction') === 'asc') {
                    direction = 'desc';
                } else {
                    direction = 'asc';
                }

                $('#ordering option[value="' + $(this).data('ordering') + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                $('#orderingdir option[value="' + direction + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                id_category = $('input[name=id_category]').val();
                $.ajax({
                    url: wpfdajaxurl + "task=category.saveparams&id=" + id_category,
                    type: "POST",
                    data: $('#category_params').serialize()
                }).done(function (data) {
                });
            });

            initFiles();

            $('#wpreview').unbind();

            Wpfd.uploader.assignBrowse($('#upload_button'), true);
            Wpfd.uploader.assignBrowse($('#upload_files_button'));
            Wpfd.uploader.assignDrop($('#wpreview'));

            showCategory();
            if (typeof(ordering) === 'undefined') {
                loadGalleryParams();
            }
            rloading('#wpreview');
            $('#wpfd-core #preview').trigger('wpfd_preview_updated');

            if (click) {
                var status = {};
                status['id_category'] = id_category;
                status['page'] = page;
                localStorage.setItem('wpfd_admin_pagination', JSON.stringify(status));
            }

            wpfd_admin_init_pagination($('#wpreview .wpfd-pagination'), id_category);
        }).error(function (a, b, c) {
            bootbox.alert('Wrong load pagination: ' + c + '!!');
        });
    }

    function wpfd_admin_load_more() {
        var $ = jQuery;
        var load_more_btn = $('#wpfd_admin_load_more_btn');
        if (!load_more_btn.length) {
            return;
        }
        load_more_btn.unbind('click').bind('click', function () {
            var page = $(this).attr('data-page');
            if (!page) {
                return;
            }
            page = parseInt(page) + 1;
            var active_category = $(this).attr('data-term_id') ? $(this).attr('data-term_id') : $('input[name=id_category]').val();
            if (typeof page !== 'undefined') {
                var ordering            = $('#wpfd_file_category_ordering').val() ? $('#wpfd_file_category_ordering').val() : 'title';
                var orderingDirection   = $('#wpfd_file_category_ordering_direction').val() ? $('#wpfd_file_category_ordering_direction').val() : 'asc';
                var page_limit          = $('#wpfd_file_category_load_more_number').val() ? $('#wpfd_file_category_load_more_number').val() : '10';
                var total               = $('#wpfd_file_category_page_total').val() ? parseInt($('#wpfd_file_category_page_total').val()) : 0;
                var params = $.param({
                    task: 'files.display',
                    view: 'files',
                    id_category: id_category,
                    rootcat: active_category,
                    page: page,
                    orderCol: ordering,
                    orderDir: orderingDirection,
                    page_limit: page_limit
                });

                // List files
                $.ajax({
                    url: wpfdajaxurl + params,
                    type: "GET",
                    beforeSend: function () {
                        loading('#wpreview');
                        $('#wpreview .wpfd_tbl_toolbox').remove();
                    }
                }).success(function (content) {
                    $('#wpfd_filter_catid').val(active_category);
                    $(content).hide().appendTo('#preview .wpfd_tbl').fadeIn(500);
                    $('#file_dropbox').remove();
                    load_more_btn.attr('data-page', page);

                    if (wpfd_permissions.can_edit_category) {
                        var remote_file = (_wpfd_text('add_remote_file') == '1' && !isCloudCategory()) ? '<a href="" id="add_remote_file" class="ju-button ju-v3-button">' + _wpfd_text('Add remote file') + '</a> ' : '';
                        var select_files = '<span id="upload_files_button" class="ju-button ju-v3-button" style="margin-left: 4px">' + _wpfd_text('Select files') + '</span>';
                        var select_folders_name = (typeof (_wpfd_text('Select a folder')) !== "undefined") ? _wpfd_text('Select a folder') : 'Select a folder';
                        $('<div id="file_dropbox"><span class="message">' + _wpfd_text('Drag & Drop your Document here') + '</span><input class="hide" type="file" id="upload_input" multiple="">' + remote_file + '<span id="upload_button" class="ju-button ju-v3-button">' + select_folders_name + '</span>' + select_files + '</div><div class="clr"></div>').appendTo('#preview');
                        $('#add_remote_file').on('click', function (e) {

                            var allowed = wpfd_admin.allowed.split(',');
                            allowed.sort();
                            var allowed_select = '<select id="wpfd-remote-type">';
                            $.each(allowed, function (i, v) {
                                allowed_select += '<option value="' + v + '">' + v + '</option>';
                            });
                            allowed_select += '</select>';
                            bootbox.dialog('<div class="">  ' +
                                '<div class="form-horizontal wpfd-remote-form"> ' +
                                '<div class="control-group"> ' +
                                '<label class=" control-label" for="wpfd-remote-title">'+ _wpfd_text('Title') + '</label> ' +
                                '<div class="controls"> ' +
                                '<input id="wpfd-remote-title" name="wpfd-remote-title" type="text" placeholder="'+ _wpfd_text('Title') + '" class=""> ' +
                                '</div> ' +
                                '</div> ' +
                                '<div class="control-group"> ' +
                                '<label class="control-label" for="wpfd-remote-url">'+ _wpfd_text('Remote URL') + '</label> ' +
                                '<div class="controls">' +
                                '<input id="wpfd-remote-url" name="wpfd-remote-url" type="text" placeholder="'+ _wpfd_text('URL') + '" class=""> ' +
                                '</div> </div>' +
                                '<div class="control-group"> ' +
                                '<label class="control-label" for="wpfd-remote-type">'+ _wpfd_text('File Type') + '</label> ' +
                                '<div class="controls">' +
                                allowed_select +
                                '</div> </div>' +
                                '</div>  </div>',
                                [{
                                    "label": _wpfd_text('Save'),
                                    "class": "button-primary",
                                    "callback": function () {
                                        var category_id = $('input[name=id_category]').val();
                                        var remote_title = $('#wpfd-remote-title').val();
                                        var remote_url = $('#wpfd-remote-url').val();
                                        var remote_type = $('#wpfd-remote-type').val();
                                        $.ajax({
                                            url: wpfdajaxurl + "task=files.addremoteurl&id_category=" + category_id,
                                            data: {
                                                remote_title: remote_title,
                                                remote_url: remote_url,
                                                remote_type: remote_type
                                            },
                                            type: "POST"
                                        }).done(function (data) {
                                            result = $.parseJSON(data);
                                            if (result.response === true) {
                                                updateCatCount(category_id, 1);
                                                Wpfd.updatePreview();
                                            } else {
                                                bootbox.alert(result.response);
                                            }
                                        });
                                    }
                                }, {
                                    "label": _wpfd_text('Cancel'),
                                    "class": "s",
                                    "callback": function () {

                                    }
                                }]
                            );
                            return false;
                        });
                    }

                    $('#preview .restable').restable({
                        type: 'hideCols',
                        priority: {0: 'persistent', 1: 3, 2: 'persistent'},
                        hideColsDefault: [4, 5]
                    });

                    Wpfd.showhidecolumns();
                    $('#preview').sortable('refresh');

                    initDeleteBtn();
                    $('#preview input[name="restable-toggle-cols"]').click(function (e) {
                        setcookie_showcolumns();
                    });

                    /** Init ordering **/
                    $('#preview .wpfd_tbl .head a').click(function (e) {
                        e.preventDefault();
                        Wpfd.updatePreview(null, null, $(this).data('ordering'), $(this).data('direction'));

                        if ($(this).data('direction') === 'asc') {
                            direction = 'desc';
                        } else {
                            direction = 'asc';
                        }

                        $('#ordering option[value="' + $(this).data('ordering') + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                        $('#orderingdir option[value="' + direction + '"]').attr('selected', 'selected').parent().css({'background-color': '#ACFFCD'});
                        id_category = $('input[name=id_category]').val();
                        $.ajax({
                            url: wpfdajaxurl + "task=category.saveparams&id=" + id_category,
                            type: "POST",
                            data: $('#category_params').serialize()
                        }).done(function (data) {
                        });
                    });
                    initFiles();
                    $('#wpreview').unbind();

                    Wpfd.uploader.assignBrowse($('#upload_button'), true);
                    Wpfd.uploader.assignBrowse($('#upload_files_button'));
                    Wpfd.uploader.assignDrop($('#wpreview'));

                    showCategory();
                    if (typeof(ordering) === 'undefined') {
                        loadGalleryParams();
                    }
                    rloading('#wpreview');
                    $('#wpfd-core #preview').trigger('wpfd_preview_updated');
                    wpfd_admin_load_more();
                    if (page >= total) {
                        $('#preview .wpfd-load-more-section').remove();
                    }
                }).error(function (a, b, c) {
                    rloading('#wpreview');
                    bootbox.alert('Wrong load more: ' + c + '!!');
                });
            }
        });
    }

    var wpfd_archive_zip_creation = {
        selectMedia: false,
        selectMultiMedia: false,
        init: function () {
            $(document).on('click', '.wpfd-modal-header .wpfd-tabs .wpfd-tab', {that: this}, this.onTabClick)
                .on('click', '.wpfd-modal-body .wpfd-tabs .wpfd-tab', {that: this}, this.onTabBodyClick)
                .on('click', '.js-modalCancel', {that: this}, this.onCancelZipCreationModal)
                .on('click', '.js-saveArchiveFileOpen', {that: this}, this.onSaveZipOpen)
                .on('keyup', '#zip_title', {that: this}, this.onKeyupTitle)
            ;
            this.onCreateZipClick({that: this});
        },
        _saveZip: function (data) {
            var zipCreationProcess;
            $.ajax({
                url: wpfd_var.wpfdajaxurl + "?action=wpfd&task=files.zipSeletedFiles",
                method: 'POST',
                data: data,
                beforeSend: function () {
                    if (data.zip_files === '') {
                        bootbox.alert(wpfd_admin.msg_not_select_cloud_files);
                        return false;
                    }
                    if (data.zip_title === '') {
                        $('#wpfd_archive_zip_general .wpfd-validate-msg').show();
                        return false;
                    }
                    if (data.zip_title !== '' && data.zip_files !== '' && data.zip_catids !== '') {
                        zipCreationProcess = wpfd_status.addStatusLine('Creating zip ' + data.zip_title + '...', 15);
                    }
                },
                success: function (data) {
                    data = JSON.parse(data);
                    if (data.response) {
                        Wpfd.updatePreview();
                        $('#zip_title').val('');
                    }
                },
                error: function (error) {
                    bootbox.alert(error);
                },
                complete: function () {
                    zipCreationProcess.remove();
                    wpfd_status.close();
                }
            })
        },
        onSaveZipOpen: function (e) {
            e.preventDefault();
            var data = {
                zip_title: $('#zip_title').val(),
                zip_files: $('[name="wpfd_archive_zip_files"]').val(),
                zip_catids: $('[name="wpfd_archive_zip_catids"]').val()
            };
            e.data.that._saveZip(data);
            if (data.zip_title === '') {
                return true;
            } else {
                e.data.that._clearModal();
                e.data.that._closeModal();
            }
        },

        onCreateZipClick: function (e) {
            try {
                // Get selected files
                var files = $('#preview .file.selected');
                if (files.length === 0) {
                    bootbox.alert(wpfd_admin.msg_select_files);
                    return false;
                }

                // Set files row
                var files_table = $('#wpfd-archive-file-table');
                // Empty old values
                files_table.html('');

                // Clone table title
                var clone_header = $('.wpfd_row.head').clone();
                $(clone_header).find('.dashicons').remove();
                $(clone_header).find('.th_created_time').remove();
                $(clone_header).find('.th_modified_time').remove();
                $(clone_header).find('.th_version').remove();
                $(clone_header).find('.th_hits').remove();
                $(clone_header).find('.th_size').remove();
                files_table.append($(clone_header));
                $.each(files, function (i, file) {
                    var row = $(file).clone();
                    var id = $(file).data('id-file');
                    var ext = $(file).data('file-ext');
                    var name = $(file).find('.title').clone().children().remove().end().text();

                    // Clean other elements
                    $(row).find('.filestatus-wrapper').remove();
                    $(row).find('[type="button"]').remove();
                    // Remove no necessary
                    $(row).find('.created').remove();
                    $(row).find('.modified').remove();
                    $(row).find('.version').remove();
                    $(row).find('.hits').remove();
                    $(row).find('.size').remove();

                    if ($(row).data('cat-cloud-type') !== 'none') {
                        $(row).addClass('exclude').removeClass('selected');
                    }

                    files_table.append($(row));
                });

                // Show creation modal
                $('#wpfd_archive_file_creation').show();
                $('#wpfd_archive_zip_general .wpfd-validate-msg').hide();
                e.that._loadSelectedFiles();
                window.Wpfd.destroyContextMenu();
            } catch (e) {
                console.log(e);
            }

            return false;
        },
        onFileSelection: function (e) {
            $(this).toggleClass('selected');
            // Fill input\
            e.data.that._loadSelectedFiles();
        },
        _loadSelectedFiles: function () {
            var ids = [],
                catids = [];

            $('#wpfd-archive-file-table .file.selected').each(function () {
                ids.push($(this).data('id-file'));
                catids.push($(this).data('catid-file'));
            });

            $('[name="wpfd_archive_zip_files"]').val(ids.join('|'));
            $('[name="wpfd_archive_zip_catids"]').val(catids.join('|'));
        },
        onCancelZipCreationModal: function (e) {
            e.preventDefault();
            // Clear fields
            e.data.that._clearModal();
            e.data.that._closeModal();
            return false;
        },
        _clearModal: function () {
            try {
                var files_table = $('#wpfd-archive-file-table');
                // Empty old values
                files_table.html('');
                $('#wpfd-create-archive-zip input').val('');
            } catch (e) {
                console.log(e);
            }
        },
        _closeModal: function () {
            $('#wpfd_archive_file_creation').hide();
        },
        onTabClick: function (e) {
            e.preventDefault();
            $('.wpfd-modal-header ul.wpfd-tabs li').addClass('ju-material-button').removeClass('ju-link-button').removeClass('active');
            $(this).removeClass('ju-material-button').addClass('ju-link-button').addClass('active');
            var id = $(this).data('tab-id');
            $('#' + id).parent().find('.wpfd-tab-content').removeClass('active');
            $('#' + id).addClass('active');
            $(this).insertBefore($(this).prev());
            return false;
        },
        onTabBodyClick: function (e) {
            e.preventDefault();
            $(this).parent().find('li').removeClass('active');
            $(this).addClass('active');
            var id = $(this).data('tab-id');
            $(this).parent().next().find('.wpfd-tab-content').removeClass('active');
            $('#' + id).addClass('active');
            return false;
        },
        onKeyupTitle: function(e) {
            e.preventDefault();
            if ($(this).val() !== '') {
                $('#wpfd_archive_zip_general .wpfd-validate-msg').hide();
            }
        }
    };

    function wpfdEscapeTitle(string) {
        return String(string).replace(/[&<>"'\/]/g, function (s) {
            return wpfdEntityMap[s];
        });
    }

    // Set tag(s) for multiple files
    Wpfd.file_set_tags = function () {
        // Get selected files
        var files = $('#preview .file.selected');
        var defaultTags = [];
        var file_ids = [];
        var selectedList = '';

        if (files.length === 0) {
            bootbox.alert(_wpfd_text('Please select file(s)'));
            return false;
        }

        $.each(files, function (i, file) {
            var id = $(file).data('id-file');
            var title = $(file).find('.title').html();
            var ext = $(file).data('file-ext');
            var suffix = ', ';

            if (i === files.length - 1) {
                suffix = '';
            }

            selectedList += title + '.' + ext + suffix;
            file_ids.push(id);
        });

        $('#wpfd_file_set_tags .tagit.input_tags').val('');
        $('#wpfd_file_set_tags ul.tagit .tagit-choice').remove();
        $('#wpfd_file_set_tags .selected-list').html(selectedList);

        if (availableTags.length === 0) {
            // Get available tags
            $.ajax({
                method: "POST",
                url: wpfdajaxurl + 'task=files.getTags',
                data: {},
                beforeSend: function () {
                    $('#wpfd_file_set_tags .wpfd-modal-header, #wpfd_file_set_tags .wpfd-modal-body, #wpfd_file_set_tags .wpfd-modal-footer').css({'visibility': 'hidden'});
                    $('#wpfd_file_set_tags .wpfd-modal-set-tags-loading').show();
                },
                success: function (res) {
                    $('#wpfd_file_set_tags .wpfd-modal-header, #wpfd_file_set_tags .wpfd-modal-body, #wpfd_file_set_tags .wpfd-modal-footer').css({'visibility': 'unset'});
                    $('#wpfd_file_set_tags .wpfd-modal-set-tags-loading').hide();
                    var availTags = res.availableTags;
                    availableTags = availTags;
                    Wpfd.file_get_tags(availTags, defaultTags);
                    Wpfd.file_save_tags(file_ids);
                }
            });
        } else {
            Wpfd.file_get_tags(availableTags, defaultTags);
            Wpfd.file_save_tags(file_ids);
        }

        // Close model
        $(document).on('click', '.js-modalCancel', function (e) {
            e.preventDefault();
            $('#wpfd_file_set_tags').hide();
        });

        // Show tag selections
        $('#wpfd_file_set_tags').show();
        $('#wpfd_file_set_tags ul.tagit .tagit-new > .ui-widget-content').focus();
    };

    // Get list tags
    Wpfd.file_get_tags = function (availTags, defaultTags) {
        $('#wpfd_file_set_tags .tagit.input_tags').tagit({
            availableTags: availTags,
            allowSpaces: true,
            initialTags: defaultTags,
            placeholderText: wpfd_admin.set_tag_placeholder,
            autocomplete: {
                source: function( request, response ) {
                    var filter = request.term.toLowerCase();
                    var tag = availTags.find(function(t) {
                        return t.label.toLowerCase().includes(filter);
                    });

                    if (tag !== undefined) {
                        response([tag.label]);
                    }
                }},
            beforeTagAdded: function(event, ui) {}
        });

        $('#wpfd_file_set_tags').parents('body').find('.ui-menu.ui-widget').addClass('wpfd-admin-ui-widget');
        $('#wpfd_file_set_tags ul.tagit .tagit-new > .ui-widget-content').focus();
    };

    // Save tags
    Wpfd.file_save_tags = function (file_ids) {
        $(document).on('click', '.js-saveTags', function (e) {
            e.preventDefault();
            var new_tags = $('#wpfd_file_set_tags .tagit.input_tags').val();
            var id_category = jQuery('input[name=id_category]').val();

            if (new_tags === '') {
                bootbox.dialog(
                    _wpfd_text('Please select your tags!'),
                    [
                        {
                            "label": _wpfd_text('OK'),
                            "callback": function() {
                                $('#wpfd_file_set_tags').show();
                                $('#wpfd_file_set_tags ul.tagit .tagit-new > .ui-widget-content').focus();
                            }
                        }
                    ]
                );
                $('#wpfd_file_set_tags').hide();
                return false;
            }

            $.ajax({
                method: 'POST',
                url: wpfdajaxurl + 'task=files.saveTags',
                data: {
                    category_id: id_category,
                    file_ids: file_ids,
                    new_tags: new_tags
                },
                dataType: 'json',
                beforeSend: function () {
                    $('#wpfd_file_set_tags').find('.js-saveTags').html(_wpfd_text('Saving...'));
                },
                success: function (data) {
                    if (typeof data === typeof string) {
                        data = JSON.parse(data);
                    }

                    $('#wpfd_file_set_tags').find('.js-saveTags').html(_wpfd_text('Save'));
                    $('.gritter-item-wrapper').remove();
                    $.gritter.add({text: data.msg});
                    $('#wpfd_file_set_tags').hide();
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                }
            });
        });
    };
});

/**
 * Insert the current category into a content editor
 */
function insertCategory() {
    id_category = jQuery('input[name=id_category]').val();
    code = '<img src="' + dir + '/app/admin/assets/images/t.gif"' +
        'data-wpfdcategory="' + id_category + '"' +
        'style="background: url(' + dir + '/app/admin/assets/images/folder_download.svg) no-repeat scroll center center #fafafa; ' +
        'height: 200px;' +
        'border-radius: 10px;' +
        'width: 99%; background-size: 220px;" data-category="' + id_category + '" />';
    window.parent.tinyMCE.execCommand('mceInsertContent', false, code);
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdmodal', window.parent.document).fadeOut(300);
    return false;
}

/**
 * Insert the current file into a content editor
 */
function insertFile() {
    id_file = jQuery('.file.selected').data('id-file');
    id_category = jQuery('input[name=id_category]').val();
    code = '<img src="' + dir + '/app/admin/assets/images/t.gif"' +
        'data-file="' + id_file + '"' +
        'data-wpfdfile="' + id_file + '"' +
        'data-category="' + id_category + '"' +
        'style="background: url(' + dir + '/app/admin/assets/images/file_download.svg) no-repeat scroll center center #fafafa; ' +
        'height: 100px;' +
        'border-radius: 10px;' +
        'width: 99%; background-size: 120px;" />';
    window.parent.tinyMCE.execCommand('mceInsertContent', false, code);
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdmodal', window.parent.document).fadeOut(300);
    return false;
}

/**
 * Insert the current category into a Elementor content editor
 */
function insertElementorCategory() {
    id_category = jQuery('input[name=id_category]').val();
    name_category = jQuery('#categorieslist li.dd-item.active > .dd-content span.title').text();
    jQuery('.elementor-control.elementor-control-wpfd_selected_category_id input[data-setting="wpfd_selected_category_id"]', window.parent.document).val(id_category);
    jQuery('.elementor-control.elementor-control-wpfd_selected_category_name input[data-setting="wpfd_selected_category_name"]', window.parent.document).val(name_category);
    window.parent.wpfd_category_widget_trigger_controls();
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdelementormodal', window.parent.document).fadeOut(300);
    return false;
}

/**
 * Insert the current file into a Elementor content editor
 */
function insertElementorFile() {
    id_file = jQuery('.file.selected').data('id-file');
    name_file = jQuery('.file.selected .title').text();
    id_category = jQuery('input[name=id_category]').val();
    if (id_category == '' || id_category == 0) {
        id_category = jQuery('.file.selected').data('catid-file');
    }
    jQuery('.elementor-control.wpfd-file-id-controls input[data-setting="wpfd_file_id"]', window.parent.document).val(id_file);
    jQuery('.elementor-control.wpfd-category-id-controls input[data-setting="wpfd_category_id"]', window.parent.document).val(id_category);
    jQuery('.elementor-control.wpfd-file-name-controls input[data-setting="wpfd_file_name"]', window.parent.document).val(name_file);
    window.parent.wpfd_file_widget_trigger_controls();
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdelementormodal', window.parent.document).fadeOut(300);
}

/**
 * Insert the current category into a WPBakery content editor
 */
function insertWPBakeryCategory() {
    id_category     = jQuery('input[name=id_category]').val();
    name_category   = jQuery('#categorieslist li.dd-item.active > .dd-content span.title').text();
    jQuery('input[name="wpfd_category_random"]', window.parent.document).val(Math.random());
    jQuery('input[name="wpfd_selected_category_id"]', window.parent.document).val(id_category);
    jQuery('input.wpfd_category_title[name="wpfd_category_title"]', window.parent.document).val(name_category);
    window.parent.wpfd_wpbakery_category_trigger_controls(name_category);
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdwpbakerymodal', window.parent.document).fadeOut(300);
    return false;
}

/**
 * Insert the current file into a WPBakery content editor
 */
function insertWPBakeryFile() {
    fileId      = jQuery('.file.selected').data('id-file');
    fileName    = jQuery('.file.selected .title').text();
    categoryId  = jQuery('input[name=id_category]').val();
    jQuery('input[name="wpfd_file_random"]', window.parent.document).val(Math.random());
    jQuery('input[name="wpfd_file_id"]', window.parent.document).val(fileId);
    jQuery('input[name="wpfd_file_related_category_id"]', window.parent.document).val(categoryId);
    jQuery('input[name="wpfd_file_title"]', window.parent.document).val(fileName);
    window.parent.wpfd_wpbakery_file_trigger_controls(fileName);
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdwpbakerymodal', window.parent.document).fadeOut(300);
}

/**
 * Insert the current category into a Avada content editor
 */
function insertAvadaCategory() {
    avada_category_id     = jQuery('input[name=id_category]').val();
    avada_category_name   = jQuery('#categorieslist li.dd-item.active > .dd-content span.title').text();
    jQuery('input#wpfd_selected_category_random', window.parent.document).val(Math.random());
    jQuery('input#wpfd_selected_category_id', window.parent.document).val(avada_category_id);
    jQuery('input#wpfd_selected_category_title', window.parent.document).val(avada_category_name);
    jQuery('input#element_content', window.parent.document).val(avada_category_name);
    window.parent.wpfd_avada_category_trigger_controls();
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdavadamodal', window.parent.document).fadeOut(300);
    return false;
}

/**
 * Insert the current file into a Avada content editor
 */
function insertAvadaFile() {
    fileId      = jQuery('.file.selected').data('id-file');
    fileName    = jQuery('.file.selected .title').text();
    categoryId  = jQuery('input[name=id_category]').val();
    jQuery('input#wpfd_selected_file_random', window.parent.document).val(Math.random());
    jQuery('input#wpfd_selected_file_id', window.parent.document).val(fileId);
    jQuery('input#wpfd_selected_category_id_related', window.parent.document).val(categoryId);
    jQuery('input#wpfd_selected_file_title', window.parent.document).val(fileName);
    jQuery('input#element_content', window.parent.document).val(fileName);
    window.parent.wpfd_avada_file_trigger_controls();
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdavadamodal', window.parent.document).fadeOut(300);
}

var insertFileToWoo = function(variationId) {
    var id_file = jQuery('.file.selected').data('id-file');
    var id_category = jQuery('input[name=id_category]').val();
    var file_name = jQuery('.file.selected .wpfd_cell.title').text();

    window.parent.wpfdWooAddonAddFileRow({id: id_file, catid: id_category, name: file_name}, variationId);
    jQuery("#lean_overlay", window.parent.document).fadeOut(300);
    jQuery('#wpfdWoocommerceModal', window.parent.document).fadeOut(300);

    return false;
}
//From http://jquery-howto.blogspot.fr/2009/09/get-url-parameters-values-with-jquery.html
function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

function getUrlVar(v) {
    if (typeof(getUrlVars()[v]) !== "undefined") {
        return getUrlVars()[v];
    }
    return null;
}

function preg_replace(array_pattern, array_pattern_replace, my_string) {
    var new_string = String(my_string);
    for (i = 0; i < array_pattern.length; i++) {
        var reg_exp = new RegExp(array_pattern[i], "gi");
        var val_to_replace = array_pattern_replace[i];
        new_string = new_string.replace(reg_exp, val_to_replace);
    }
    return new_string;
}

//https://gist.github.com/ncr/399624
jQuery.fn.single_double_click = function (single_click_callback, double_click_callback, timeout) {
    return this.each(function () {
        var clicks = 0, self = this;
        jQuery(this).click(function (event) {
            clicks++;
            if (clicks === 1) {
                setTimeout(function () {
                    if (clicks === 1) {
                        single_click_callback.call(self, event);
                    } else {
                        double_click_callback.call(self, event);
                    }
                    clicks = 0;
                }, timeout || 300);
            }
        });
    });
};

//http://stackoverflow.com/questions/11103447/jquery-sortable-cancel-and-revert-not-working-as-expected
//modified by joomunited.com
var _mouseStop = jQuery.ui.sortable.prototype._mouseStop;
jQuery.ui.sortable.prototype._mouseStop = function (event, noPropagation) {
    Wpfd.filetocat = false;

    if (!event) {
        return;
    }
    $ = jQuery;
    //If we are using droppables, inform the manager about the drop
    if ($.ui.ddmanager && !this.options.dropBehaviour) {
        $.ui.ddmanager.drop(this, event);
    }

    var options = this.options;
    var $item = $(this.currentItem);
    var el = this.element[0];
    var ui = this._uiHash(this);
    var current = $item.css(['top', 'left', 'position', 'width', 'height']);
    var cancel = options.revert && $.isFunction(options.beforeRevert) && !options.beforeRevert.call(el, event, ui);

    if (cancel) {
        this.cancel();
        // $item.css(current);
        // $item.animate(this.originalPosition, {
        //     duration: isNaN(options.revert) ? 500 : options.revert,
        //     always: function () {
        //         $('body').css('cursor', '');
        //         $item.css({position: '', top: '', left: '', width: '', height: '', 'z-index': ''});
        //         if ($.isFunction(options.update)) {
        //             options.update.call(el, event, ui);
        //         }
        //     }
        // });
    }

    return !cancel && _mouseStop.call(this, event, noPropagation);
};
