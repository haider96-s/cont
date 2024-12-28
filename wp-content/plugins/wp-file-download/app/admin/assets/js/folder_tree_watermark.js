/**
 * Folder tree for WP File Download
 */
var wpfdFoldersTreeWatermarkListingModule;
(function ($) {
    wpfdFoldersTreeWatermarkListingModule = {
        categories: [], // categories

        /**
         * Retrieve the Jquery tree view element
         * of the current frame
         * @return jQuery
         */
        getTreeElement: function () {
            return $('#wpfd_tree_watermark_category');
        },

        /**
         * Initialize module related things
         */
        initModule: function () {
            // Import categories from wpfd main module
            wpfdFoldersTreeWatermarkListingModule.importCategories();

            // Render the tree view
            wpfdFoldersTreeWatermarkListingModule.loadTreeView();
        },

        getchecked: function (folder_id, button) {
            if ($(button).is(':checked')) {
                $(button).closest('li.directory').find('.media_checkbox').prop('checked', true);
            } else {
                $(button).closest('li.directory').find('.media_checkbox').prop('checked', false);
            }
        },

        /**
         * Import categories from wpfd main module
         */
        importCategories: function () {
            var folders_ordered = wpfd_var.wpfd_categories;

            if (folders_ordered) {
                folders_ordered.forEach(function (ele, i) {
                    // Convert cloud ids
                    if (ele.cloudType !== false && typeof (ele.wp_term_id) !== 'undefined' && typeof (ele.wp_parent) !== 'undefined') {
                        folders_ordered[i].term_id = ele.wp_term_id;
                        folders_ordered[i].parent = ele.wp_parent;
                    }

                    // Correct cloud parent id
                    if (ele.cloudType !== false && ele.parent === false) {
                        folders_ordered[i].parent = 0;
                    }
                });
            }

            // Reorder array based on children
            var folders_ordered_deep = [];
            var processed_ids = [];
            var loadChildren = function (id) {
                if (processed_ids.indexOf(id) < 0) {
                    processed_ids.push(id);
                    for (var ij = 0; ij < folders_ordered.length; ij++) {
                        if (folders_ordered[ij].parent === id) {
                            folders_ordered_deep.push(folders_ordered[ij]);
                            loadChildren(folders_ordered[ij].term_id);
                        }
                    }
                }
            };
            loadChildren(0);
            // Finally save it to the global var
            wpfdFoldersTreeWatermarkListingModule.categories = folders_ordered_deep;
        },

        /**
         * Render tree view inside content
         */
        loadTreeView: function () {
            wpfdFoldersTreeWatermarkListingModule.getTreeElement().html(wpfdFoldersTreeWatermarkListingModule.getRendering());
        },

        /**
         * Get the html resulting tree view
         * @return {string}
         */
        getRendering: function () {
            var ij = 0;
            var content = '<h3>'+wpfd_admin.label_watermark_wpfd+'</h3>'; // Final tree view content
            /**
             * Recursively print list of folders
             * @return {boolean}
             */
            var generateList = function generateList() {
                content += '<ul class="jaofiletree">';
    
                while (ij < wpfdFoldersTreeWatermarkListingModule.categories.length) {
                    var className = '';
                    var cloudType = wpfdFoldersTreeWatermarkListingModule.categories[ij].cloudType;
                    if (wpfdFoldersTreeWatermarkListingModule.categories[ij + 1] && wpfdFoldersTreeWatermarkListingModule.categories[ij + 1].level > wpfdFoldersTreeWatermarkListingModule.categories[ij].level) {
                        className = ' directory-parent';
                    } else {
                        className = ' directory-no-arrow';
                    }

                    if (cloudType != false) {
                        className += ' ' + cloudType;
                    }
                    // Open li tag
                    content += '<li class="' + className + ' directory" data-id="' + wpfdFoldersTreeWatermarkListingModule.categories[ij].term_id + '" >';

                    var a_tag = '<a class="wpfd-watermark-item" data-id="' + wpfdFoldersTreeWatermarkListingModule.categories[ij].term_id + '">';

                    if (wpfdFoldersTreeWatermarkListingModule.categories[ij + 1] && wpfdFoldersTreeWatermarkListingModule.categories[ij + 1].level > wpfdFoldersTreeWatermarkListingModule.categories[ij].level) { // The next element is a sub folder
                        content += '<a class="wpfd-folder-toggle" onclick="wpfdFoldersTreeWatermarkListingModule.toggle(' + wpfdFoldersTreeWatermarkListingModule.categories[ij].term_id + ')"><i class="material-icons wpfd-arrow">keyboard_arrow_down</i></a>';
                        content += a_tag;
                    } else {
                        content += a_tag;
                    }
                    
                    var checked = '';
                    if (wpfdFoldersTreeWatermarkListingModule.categories[ij] && wpfdFoldersTreeWatermarkListingModule.categories[ij].watermark === true) {
                        checked = 'checked';
                    }

                    content += '<input type="checkbox" class="media_checkbox" onclick="wpfdFoldersTreeWatermarkListingModule.getchecked(' + wpfdFoldersTreeWatermarkListingModule.categories[ij].term_id + ',  this)" data-id="' + wpfdFoldersTreeWatermarkListingModule.categories[ij].term_id + '" '+checked+' />';

                    // Add current category name
                    content += '<span class="wpfd-edit-watermark-item wpfd-folder-toggle" data-id="' + wpfdFoldersTreeWatermarkListingModule.categories[ij].term_id + '">' + wpfdFoldersTreeWatermarkListingModule.categories[ij].name + ' <i class="material-icons">edit</i></span>';
                    content += '</a>';
                    // This is the end of the array
                    if (wpfdFoldersTreeWatermarkListingModule.categories[ij + 1] === undefined) {
                        // var's close all opened tags
                        for (var ik = wpfdFoldersTreeWatermarkListingModule.categories[ij].level; ik >= 0; ik--) {
                            content += '</li>';
                            content += '</ul>';
                        }

                        // We are at the end don't continue to process array
                        return false;
                    }

                    if (wpfdFoldersTreeWatermarkListingModule.categories[ij + 1].level > wpfdFoldersTreeWatermarkListingModule.categories[ij].level) { // The next element is a sub folder
                        // Recursively list it
                        ij++;
                        if (generateList() === false) {
                            // We have reached the end, var's recursively end
                            return false;
                        }
                    } else if (wpfdFoldersTreeWatermarkListingModule.categories[ij + 1].level < wpfdFoldersTreeWatermarkListingModule.categories[ij].level) { // The next element don't have the same parent
                        // var's close opened tags
                        for (var ik1 = wpfdFoldersTreeWatermarkListingModule.categories[ij].level; ik1 > wpfdFoldersTreeWatermarkListingModule.categories[ij + 1].level; ik1--) {
                            content += '</li>';
                            content += '</ul>';
                        }

                        // We're not at the end of the array var's continue processing it
                        return true;
                    }

                    // Close the current element
                    content += '</li>';
                    ij++;
                }
            };

            // Start generation
            generateList();
            return content;
        },

        /**
         * Change the selected folder in tree view
         * @param folder_id
         */
        changeFolder: function (folder_id) {
            // Remove previous selection
            wpfdFoldersTreeWatermarkListingModule.getTreeElement().find('li').removeClass('selected');

            // Select the folder
            wpfdFoldersTreeWatermarkListingModule.getTreeElement().find('li[data-id="' + folder_id + '"]').addClass('selected').// Open parent folders
            parents('.wpfd-folder-tree li.closed').removeClass('closed');
        },

        /**
         * Change the selected folder in tree view
         * @param folder_id
         */
        renderCatID: function (folder_id) {
            if (parseInt(folder_id) == 0) {
                $('.watermark_dir_name_category_id').val('');
                $('.watermark_dir_name_categories').val('');
            } else {
                var categories = wpfd_var.wpfd_wm_categories_order;
                var category = categories[folder_id];
                var breadcrumb_content = '';

                // Ascend until there is no more parent
                while (parseInt(category.parent) !== 0) {
                    // Generate breadcrumb element
                    breadcrumb_content = '/' + categories[category.term_id].name + breadcrumb_content;

                    // Get the parent
                    category = categories[categories[category.term_id].parent];
                }

                if (parseInt(category.term_id) !== 0) {
                    breadcrumb_content = categories[category.term_id].name + breadcrumb_content;
                }

                breadcrumb_content = '/' + breadcrumb_content;
                $('.watermark_dir_name_categories').val(breadcrumb_content);
                $('.watermark_dir_name_category_id').val(folder_id);
            }
        },

        /**
         * Toggle the open / closed state of a folder
         * @param folder_id
         */
        toggle: function (folder_id) {
            // Check is folder has closed class
            if (wpfdFoldersTreeWatermarkListingModule.getTreeElement().find('li[data-id="' + folder_id + '"]').hasClass('closed')) {
                // Open the folder
                wpfdFoldersTreeWatermarkListingModule.openFolder(folder_id);
            } else {
                // Close the folder
                wpfdFoldersTreeWatermarkListingModule.closeFolder(folder_id);
                // close all sub folder
                $('li[data-id="' + folder_id + '"]').find('li').addClass('closed');
            }
        },


        /**
         * Open a folder to show children
         */
        openFolder: function (folder_id) {
            wpfdFoldersTreeWatermarkListingModule.getTreeElement().find('li[data-id="' + folder_id + '"]').removeClass('closed');
        },

        /**
         * Close a folder and hide children
         */
        closeFolder: function (folder_id) {
            wpfdFoldersTreeWatermarkListingModule.getTreeElement().find('li[data-id="' + folder_id + '"]').addClass('closed');
        }
    };

    // var's initialize WPfd folder tree features
    $(document).ready(function () {
        wpfdFoldersTreeWatermarkListingModule.initModule();
    });
})(jQuery);