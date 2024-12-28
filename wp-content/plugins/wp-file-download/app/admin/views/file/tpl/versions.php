<?php

/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

defined('ABSPATH') || die();

$content = '';

if (!empty($this->versions)) {
    $content .= '<table>';
    foreach ($this->versions as $meta_id => $file) {
        if (isset($file['size']) && !$file['size']) {
            continue;
        }
        $version = '1';
        $categoryFrom = apply_filters('wpfdAddonCategoryFrom', $file['catid']);
        $content .= '<tr>';
        $content .= '<td><a href="admin-ajax.php?action=wpfd&task=file.download&version=' . (int)$version . '&id=';
        $content .= (string)$this->file_id . '&vid=' . (string)$file['meta_id'] . '&catid=' . (int)$file['catid'] . '" >';
        $content .= isset($file['version']) && !empty($file['version']) ? $file['version'] : date('Y M d', strtotime($file['created_time'])) . ' ';
        $content .= '</a></td>';
        $content .= '<td>' . WpfdHelperFiles::bytesToSize((int)$file['size']) . '</td>';
        $content .= '<td>';
        if (!in_array($categoryFrom, wpfd_get_support_cloud())) {
            $content .= '<a data-id="' . (string)$this->file_id . '" data-vid="' . (string)$file['meta_id'] . '" data-catid="' . (int)$file['catid'] . '" href="#" class="rename"><i class="icon-edit"></i></a>';
        }

        $content .= '<a data-id="' . (string)$this->file_id . '" data-vid="' . (string)$file['meta_id'] . '" data-catid="' . (int)$file['catid'] . '" data-catfrom="' . (string)$categoryFrom . '" href="#" class="restore"><i class="icon-restore"></i></a>';

        if ($categoryFrom !== 'dropbox' && $categoryFrom !== 'onedrive' && $categoryFrom !== 'onedrive_business') {
            $content .= '<a data-id="' . (string) $this->file_id . '" data-vid="' . (string) $file['meta_id'] . '" data-catid="' . (int) $file['catid'] . '" href="#" class="trash"><i class="icon-trash"></i></a></td>';
        }
        $content .= '</tr>';
    }
    $content .= '</table>';
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cast above
echo $content;
