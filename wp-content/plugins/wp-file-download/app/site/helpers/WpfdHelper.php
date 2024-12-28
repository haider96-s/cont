<?php
/**
 * WP File Download
 *
 * @package WP File Download
 * @author  Joomunited
 * @version 1.0
 */

defined('ABSPATH') || die();

/**
 * Class WpfdHelper
 */
class WpfdHelper
{
    /**
     * Method check category access
     *
     * @param object $category Category
     *
     * @return boolean
     */
    public static function checkCategoryAccess($category)
    {
        if ((int) $category->access === 1) {
            $user  = wp_get_current_user();
            $roles = array();
            foreach ($user->roles as $role) {
                $roles[] = strtolower($role);
            }
            $allows        = array_intersect($roles, $category->roles);
            $params        = json_decode($category->description, true);
            $allows_single = false;
            if (isset($params['canview']) && $params['canview'] !== '') {
                if (((int) $params['canview'] !== 0) && (int) $params['canview'] === $user->ID) {
                    $allows_single = true;
                }
            }
            if ($allows || $allows_single) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Get google drive id by term id
     *
     * @param integer $termId Term id
     *
     * @return boolean
     */
    public static function getGoogleDriveIdByTermId($termId)
    {
        $result = get_term_meta($termId, 'wpfd_drive_id', true);
        $type = get_term_meta($termId, 'wpfd_drive_type', true);

        if ($result && $type === 'googleDrive') {
            return $result;
        }

        return false;
    }

    /**
     * Get google team drive id by term id
     *
     * @param integer $termId Term id
     *
     * @return boolean
     */
    public static function getGoogleTeamDriveIdByTermId($termId)
    {
        $result = get_term_meta($termId, 'wpfd_drive_id', true);
        $type = get_term_meta($termId, 'wpfd_drive_type', true);

        if ($result && $type === 'googleTeamDrive') {
            return $result;
        }

        return false;
    }

    /**
     * Get id by termID
     *
     * @param integer $termId Term id
     *
     * @return boolean|string
     */
    public static function getDropboxIdByTermId($termId)
    {
        $result = get_term_meta($termId, 'wpfd_drive_id', true);
        $type = get_term_meta($termId, 'wpfd_drive_type', true);

        if ($result && $type === 'dropbox') {
            return $result;
        }

        return false;
    }

    /**
     * Get onedrive by term id
     *
     * @param integer $termId Term id
     *
     * @return boolean
     */
    public static function getOneDriveIdByTermId($termId)
    {
        $result = get_term_meta($termId, 'wpfd_drive_id', true);
        $type = get_term_meta($termId, 'wpfd_drive_type', true);

        if ($result && $type === 'onedrive') {
            $result = self::replaceIdOneDrive($result, false);

            return $result;
        }

        return false;
    }

    /**
     * Get onedrive by term id
     *
     * @param integer $termId Term id
     *
     * @return boolean
     */
    public static function getOneDriveBusinessIdByTermId($termId)
    {
        $result = get_term_meta($termId, 'wpfd_drive_id', true);
        $type = get_term_meta($termId, 'wpfd_drive_type', true);

        if ($result && $type === 'onedrive_business') {
            return $result;
        }

        return false;
    }

    /**
     * Get AWS path by termID
     *
     * @param integer $termId Term id
     *
     * @return boolean|string
     */
    public static function getAwsPathByTermId($termId)
    {
        $result = get_term_meta($termId, 'wpfd_drive_path', true);
        $type = get_term_meta($termId, 'wpfd_drive_type', true);

        if ($result && $type === 'aws') {
            return $result;
        }

        return false;
    }

    /**
     * Get Nextcloud path by termID
     *
     * @param integer $termId Term id
     *
     * @return boolean|string
     */
    public static function getNextcloudPathByTermId($termId)
    {
        $result = get_term_meta($termId, 'wpfd_drive_path', true);
        $type = get_term_meta($termId, 'wpfd_drive_type', true);

        if ($result && $type === 'nextcloud') {
            return $result;
        }

        return false;
    }

    /**
     * Replace replace id special characters
     *
     * @param string  $id         Item id
     * @param boolean $rplSpecial Replace from special to -
     *
     * @return string
     */
    public static function replaceIdOneDrive($id, $rplSpecial = true)
    {
        if ($rplSpecial) {
            return str_replace('!', '-', $id);
        } else {
            return str_replace('-', '!', $id);
        }
    }

    /**
     * Check where category come from
     *
     * @param integer $termId Term Id
     *
     * @return boolean|string
     */
    public static function wpfdAddonCategoryFrom($termId)
    {
        if (self::getGoogleDriveIdByTermId($termId)) {
            return 'googleDrive';
        } elseif (self::getGoogleTeamDriveIdByTermId($termId)) {
            return 'googleTeamDrive';
        } elseif (self::getDropboxIdByTermId($termId)) {
            return 'dropbox';
        } elseif (self::getOneDriveIdByTermId($termId)) {
            return 'onedrive';
        } elseif (self::getOneDriveBusinessIdByTermId($termId)) {
            return 'onedrive_business';
        } elseif (self::getAwsPathByTermId($termId)) {
            return 'aws';
        } elseif (self::getNextcloudPathByTermId($termId)) {
            return 'nextcloud';
        } else {
            return false;
        }
    }
}
