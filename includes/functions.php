<?php
/**
 * Helper functions
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin settings
 *
 * @return array Plugin settings
 */
function tcms_get_settings() {
    $default_options = array(
        'max_distance' => 50, // km
        'free_messages_limit' => 10,
        'require_verification' => true,
        'enable_geolocation' => true,
        'delete_data_on_uninstall' => false,
    );
    
    $settings = get_option('tcms_settings', $default_options);
    
    return array_merge($default_options, $settings);
}

/**
 * Format distance
 *
 * @param float $distance Distance in kilometers
 * @return string Formatted distance
 */
function tcms_format_distance($distance) {
    if ($distance < 1) {
        return sprintf(__('%.0f m', 'tcms-messaging'), $distance * 1000);
    } elseif ($distance < 10) {
        return sprintf(__('%.1f km', 'tcms-messaging'), $distance);
    } else {
        return sprintf(__('%.0f km', 'tcms-messaging'), $distance);
    }
}

/**
 * Format time elapsed
 *
 * @param string $datetime MySQL datetime string
 * @return string Formatted time elapsed
 */
function tcms_time_elapsed($datetime) {
    $time = strtotime($datetime);
    $now = current_time('timestamp');
    $diff = $now - $time;
    
    if ($diff < 60) {
        return __('just now', 'tcms-messaging');
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return sprintf(_n('%s min ago', '%s mins ago', $mins, 'tcms-messaging'), $mins);
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return sprintf(_n('%s hour ago', '%s hours ago', $hours, 'tcms-messaging'), $hours);
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return sprintf(_n('%s day ago', '%s days ago', $days, 'tcms-messaging'), $days);
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return sprintf(_n('%s week ago', '%s weeks ago', $weeks, 'tcms-messaging'), $weeks);
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return sprintf(_n('%s month ago', '%s months ago', $months, 'tcms-messaging'), $months);
    } else {
        $years = floor($diff / 31536000);
        return sprintf(_n('%s year ago', '%s years ago', $years, 'tcms-messaging'), $years);
    }
}

/**
 * Get user online status
 *
 * @param int $user_id User ID
 * @return string User status (online, away, offline)
 */
function tcms_get_user_status($user_id) {
    $last_activity = get_user_meta($user_id, 'tcms_last_activity', true);
    
    if (!$last_activity) {
        return 'offline';
    }
    
    $fifteen_minutes_ago = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $thirty_minutes_ago = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    
    if ($last_activity > $fifteen_minutes_ago) {
        return 'online';
    } elseif ($last_activity > $thirty_minutes_ago) {
        return 'away';
    } else {
        return 'offline';
    }
}

/**
 * Get user status display text
 *
 * @param int $user_id User ID
 * @return string User status text
 */
function tcms_get_user_status_text($user_id) {
    $status = tcms_get_user_status($user_id);
    
    switch ($status) {
        case 'online':
            return __('Online now', 'tcms-messaging');
        case 'away':
            return __('Away', 'tcms-messaging');
        case 'offline':
            $last_activity = get_user_meta($user_id, 'tcms_last_activity', true);
            if ($last_activity) {
                return sprintf(__('Last seen %s', 'tcms-messaging'), tcms_time_elapsed($last_activity));
            } else {
                return __('Offline', 'tcms-messaging');
            }
    }
}

/**
 * Get user avatar URL
 *
 * @param int $user_id User ID
 * @return string Avatar URL
 */
function tcms_get_user_avatar($user_id) {
    global $wpdb;
    
    // Check for primary photo
    $primary_photo = $wpdb->get_var($wpdb->prepare(
        "SELECT photo_url FROM {$wpdb->prefix}tcms_user_photos 
        WHERE user_id = %d AND is_primary = 1",
        $user_id
    ));
    
    if ($primary_photo) {
        return $primary_photo;
    }
    
    // Check for avatar in profile
    $avatar_url = $wpdb->get_var($wpdb->prepare(
        "SELECT avatar_url FROM {$wpdb->prefix}tcms_user_profiles 
        WHERE user_id = %d",
        $user_id
    ));
    
    if ($avatar_url) {
        return $avatar_url;
    }
    
    // Fall back to Gravatar
    $user_data = get_userdata($user_id);
    return get_avatar_url($user_data->user_email, array('size' => 200));
}

/**
 * Get user display name
 *
 * @param int $user_id User ID
 * @return string Display name
 */
function tcms_get_user_display_name($user_id) {
    global $wpdb;
    
    $display_name = $wpdb->get_var($wpdb->prepare(
        "SELECT display_name FROM {$wpdb->prefix}tcms_user_profiles 
        WHERE user_id = %d",
        $user_id
    ));
    
    if ($display_name) {
        return $display_name;
    }
    
    $user_data = get_userdata($user_id);
    return $user_data ? $user_data->display_name : __('Unknown User', 'tcms-messaging');
}

/**
 * Check if user has premium permissions
 *
 * @param int $user_id User ID
 * @param string $permission Permission to check
 * @return bool Has permission
 */
function tcms_user_has_premium_permission($user_id, $permission) {
    if (!tcms_is_user_premium($user_id)) {
        return false;
    }
    
    $premium_info = tcms_get_user_premium_info($user_id);
    
    if (!$premium_info['plan_details'] || !isset($premium_info['plan_details']['features'])) {
        return false;
    }
    
    return isset($premium_info['plan_details']['features'][$permission]) && 
           $premium_info['plan_details']['features'][$permission];
}

/**
 * Sanitize user role
 *
 * @param string $role User role
 * @return string Sanitized role
 */
function tcms_sanitize_user_role($role) {
    $allowed_roles = array('top', 'bottom', 'versatile');
    
    if (in_array($role, $allowed_roles)) {
        return $role;
    }
    
    return 'versatile'; // Default
}

/**
 * Get privacy level label
 *
 * @param int $level Privacy level (0-3)
 * @return string Privacy level label
 */
function tcms_get_privacy_level_label($level) {
    switch ($level) {
        case 0:
            return __('Hidden', 'tcms-messaging');
        case 1:
            return __('Premium members', 'tcms-messaging');
        case 2:
            return __('Members', 'tcms-messaging');
        case 3:
            return __('Everyone', 'tcms-messaging');
        default:
            return __('Unknown', 'tcms-messaging');
    }
}

/**
 * Generate a unique username
 *
 * @param string $base Base for username
 * @return string Unique username
 */
function tcms_generate_unique_username($base) {
    $username = sanitize_user($base, true);
    
    // Ensure username is valid
    if (empty($username)) {
        $username = 'user';
    }
    
    // Check if username exists
    if (!username_exists($username)) {
        return $username;
    }
    
    // Add a number until we find a unique username
    $i = 1;
    $new_username = $username . $i;
    
    while (username_exists($new_username)) {
        $i++;
        $new_username = $username . $i;
    }
    
    return $new_username;
}

/**
 * Get excerpt from longer text
 *
 * @param string $text Text to excerpt
 * @param int $length Maximum length
 * @return string Excerpted text
 */
function tcms_get_excerpt($text, $length = 100) {
    $text = strip_tags($text);
    
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . '...';
}

/**
 * Safe JSON encoding
 *
 * @param mixed $data Data to encode
 * @return string JSON string
 */
function tcms_json_encode($data) {
    return wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Get upload directory for plugin
 *
 * @return array Upload directory info
 */
function tcms_get_upload_dir() {
    $upload_dir = wp_upload_dir();
    $tcms_dir = $upload_dir['basedir'] . '/tcms-messaging';
    
    // Create directory if it doesn't exist
    if (!file_exists($tcms_dir)) {
        wp_mkdir_p($tcms_dir);
        
        // Create index.php file to prevent directory listing
        $index_file = $tcms_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
    
    return array(
        'path' => $tcms_dir,
        'url' => $upload_dir['baseurl'] . '/tcms-messaging',
    );
}