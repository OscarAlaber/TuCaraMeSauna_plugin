<?php
/**
 * User management class
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User management class
 */
class TCMS_User {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Hook into user registration
        add_action('user_register', array($this, 'create_user_profile'), 10, 1);
        
        // Track user activity
        add_action('init', array($this, 'track_user_activity'));
        
        // Filter user display name
        add_filter('the_author', array($this, 'filter_display_name'));
        add_filter('get_comment_author', array($this, 'filter_display_name'));
    }

    /**
     * Create user profile on registration
     */
    public function create_user_profile($user_id) {
        global $wpdb;
        
        // Create user profile entry
        $wpdb->insert(
            $wpdb->prefix . 'tcms_user_profiles',
            array(
                'user_id' => $user_id,
                'display_name' => get_user_meta($user_id, 'nickname', true),
                'is_verified' => 0,
                'privacy_settings' => json_encode(array(
                    'show_location' => 'members',
                    'show_online_status' => 'everyone',
                    'show_profile' => 'everyone',
                    'allow_messages' => 'everyone'
                ))
            )
        );
        
        // Add default avatar using Gravatar
        $user_data = get_userdata($user_id);
        $avatar_url = get_avatar_url($user_data->user_email, array('size' => 200));
        
        $wpdb->update(
            $wpdb->prefix . 'tcms_user_profiles',
            array('avatar_url' => $avatar_url),
            array('user_id' => $user_id)
        );
    }

    /**
     * Track user activity
     */
    public function track_user_activity() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Update last active timestamp
            update_user_meta($user_id, 'tcms_last_activity', current_time('mysql'));
            
            // Update user profile last_active
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'tcms_user_profiles',
                array('last_active' => current_time('mysql')),
                array('user_id' => $user_id)
            );
        }
    }

    /**
     * Filter user display name
     */
    public function filter_display_name($display_name) {
        global $wpdb;
        
        $user = get_user_by('login', $display_name);
        
        if ($user) {
            $profile_name = $wpdb->get_var($wpdb->prepare(
                "SELECT display_name FROM {$wpdb->prefix}tcms_user_profiles WHERE user_id = %d",
                $user->ID
            ));
            
            if ($profile_name) {
                return $profile_name;
            }
        }
        
        return $display_name;
    }

    /**
     * Get nearby users
     */
    public function get_nearby_users($latitude, $longitude, $distance_km = 50, $limit = 20, $offset = 0, $filters = array()) {
        global $wpdb;
        
        // Earth radius in kilometers
        $earth_radius = 6371;
        
        // Calculate bounding box to optimize query
        $lat_delta = $distance_km / $earth_radius * (180 / M_PI);
        $lon_delta = $distance_km / ($earth_radius * cos(deg2rad($latitude))) * (180 / M_PI);
        
        $lat_min = $latitude - $lat_delta;
        $lat_max = $latitude + $lat_delta;
        $lon_min = $longitude - $lon_delta;
        $lon_max = $longitude + $lon_delta;
        
        // Start building the query
        $sql = "SELECT 
                p.id, p.user_id, p.display_name, p.bio, p.is_verified, p.last_active, p.role,
                l.latitude, l.longitude, l.city, l.country,
                ph.photo_url,
                ROUND(
                    {$earth_radius} * 2 * ASIN(
                        SQRT(
                            POWER(SIN(RADIANS(%f - l.latitude) / 2), 2) +
                            COS(RADIANS(%f)) * COS(RADIANS(l.latitude)) *
                            POWER(SIN(RADIANS(%f - l.longitude) / 2), 2)
                        )
                    )
                ) AS distance
            FROM {$wpdb->prefix}tcms_user_profiles p
            JOIN {$wpdb->prefix}tcms_user_locations l ON p.user_id = l.user_id
            LEFT JOIN (
                SELECT user_id, photo_url
                FROM {$wpdb->prefix}tcms_user_photos
                WHERE is_primary = 1
                GROUP BY user_id
            ) ph ON p.user_id = ph.user_id
            WHERE l.latitude BETWEEN %f AND %f
            AND l.longitude BETWEEN %f AND %f
            AND l.privacy_level >= 1";
        
        // Add filters
        if (!empty($filters)) {
            if (isset($filters['verified']) && $filters['verified']) {
                $sql .= " AND p.is_verified = 1";
            }
            
            if (isset($filters['role']) && !empty($filters['role'])) {
                $sql .= $wpdb->prepare(" AND p.role = %s", $filters['role']);
            }
            
            if (isset($filters['last_active']) && !empty($filters['last_active'])) {
                $hours = intval($filters['last_active']);
                $sql .= $wpdb->prepare(" AND p.last_active > DATE_SUB(NOW(), INTERVAL %d HOUR)", $hours);
            }
        }
        
        // Exclude blocked users
        $current_user_id = get_current_user_id();
        if ($current_user_id > 0) {
            $sql .= $wpdb->prepare(" AND p.user_id NOT IN (
                SELECT blocked_user_id FROM {$wpdb->prefix}tcms_user_blocks WHERE user_id = %d
                UNION
                SELECT user_id FROM {$wpdb->prefix}tcms_user_blocks WHERE blocked_user_id = %d
            )", $current_user_id, $current_user_id);
        }
        
        // Add having clause for precise distance
        $sql .= " HAVING distance <= %d";
        
        // Add order and limit
        $sql .= " ORDER BY distance ASC LIMIT %d OFFSET %d";
        
        // Prepare the final query
        $prepared_sql = $wpdb->prepare(
            $sql,
            $latitude, $latitude, $longitude,
            $lat_min, $lat_max, $lon_min, $lon_max,
            $distance_km, $limit, $offset
        );
        
        // Execute query
        $results = $wpdb->get_results($prepared_sql);
        
        return $results;
    }

    /**
     * Check if user is online
     */
    public function is_user_online($user_id) {
        $last_activity = get_user_meta($user_id, 'tcms_last_activity', true);
        
        if (!$last_activity) {
            return false;
        }
        
        // Consider user online if active in the last 15 minutes
        $fifteen_minutes_ago = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        return $last_activity > $fifteen_minutes_ago;
    }

    /**
     * Get user profile data
     */
    public function get_user_profile($user_id) {
        global $wpdb;
        
        // Get profile data
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_user_profiles WHERE user_id = %d",
            $user_id
        ));
        
        if (!$profile) {
            return false;
        }
        
        // Get user location
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_user_locations WHERE user_id = %d",
            $user_id
        ));
        
        // Get user photos
        $photos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_user_photos WHERE user_id = %d ORDER BY is_primary DESC",
            $user_id
        ));
        
        // Check if premium
        $is_premium = tcms_is_user_premium($user_id);
        
        // Check if online
        $is_online = $this->is_user_online($user_id);
        
        // Decode privacy settings
        $privacy_settings = json_decode($profile->privacy_settings, true);
        
        // Combine data
        $user_data = array(
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'display_name' => $profile->display_name,
            'bio' => $profile->bio,
            'interests' => json_decode($profile->interests, true),
            'preferences' => json_decode($profile->preferences, true),
            'avatar_url' => $profile->avatar_url,
            'is_verified' => (bool) $profile->is_verified,
            'privacy_settings' => $privacy_settings,
            'last_active' => $profile->last_active,
            'role' => $profile->role,
            'location' => $location ? array(
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'city' => $location->city,
                'country' => $location->country,
                'privacy_level' => $location->privacy_level
            ) : null,
            'photos' => $photos ? array_map(function($photo) {
                return array(
                    'id' => $photo->id,
                    'url' => $photo->photo_url,
                    'is_primary' => (bool) $photo->is_primary,
                    'description' => $photo->description,
                    'uploaded_at' => $photo->uploaded_at
                );
            }, $photos) : array(),
            'is_premium' => $is_premium,
            'is_online' => $is_online
        );
        
        return $user_data;
    }

    /**
     * Update user profile
     */
    public function update_user_profile($user_id, $profile_data) {
        global $wpdb;
        
        // Check if profile exists
        $profile_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tcms_user_profiles WHERE user_id = %d",
            $user_id
        ));
        
        if (!$profile_exists) {
            $this->create_user_profile($user_id);
        }
        
        // Prepare data for update
        $update_data = array();
        
        if (isset($profile_data['display_name'])) {
            $update_data['display_name'] = sanitize_text_field($profile_data['display_name']);
        }
        
        if (isset($profile_data['bio'])) {
            $update_data['bio'] = sanitize_textarea_field($profile_data['bio']);
        }
        
        if (isset($profile_data['interests'])) {
            $update_data['interests'] = json_encode($profile_data['interests']);
        }
        
        if (isset($profile_data['preferences'])) {
            $update_data['preferences'] = json_encode($profile_data['preferences']);
        }
        
        if (isset($profile_data['avatar_url'])) {
            $update_data['avatar_url'] = esc_url_raw($profile_data['avatar_url']);
        }
        
        if (isset($profile_data['role'])) {
            $update_data['role'] = sanitize_text_field($profile_data['role']);
        }
        
        if (isset($profile_data['privacy_settings'])) {
            $update_data['privacy_settings'] = json_encode($profile_data['privacy_settings']);
        }
        
        // Only update if we have data
        if (!empty($update_data)) {
            $wpdb->update(
                $wpdb->prefix . 'tcms_user_profiles',
                $update_data,
                array('user_id' => $user_id)
            );
        }
        
        // Update location if provided
        if (isset($profile_data['location'])) {
            $location = $profile_data['location'];
            
            $location_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tcms_user_locations WHERE user_id = %d",
                $user_id
            ));
            
            $location_data = array(
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'city' => isset($location['city']) ? sanitize_text_field($location['city']) : '',
                'country' => isset($location['country']) ? sanitize_text_field($location['country']) : '',
                'privacy_level' => isset($location['privacy_level']) ? intval($location['privacy_level']) : 1,
                'last_updated' => current_time('mysql')
            );
            
            if ($location_exists) {
                $wpdb->update(
                    $wpdb->prefix . 'tcms_user_locations',
                    $location_data,
                    array('user_id' => $user_id)
                );
            } else {
                $location_data['user_id'] = $user_id;
                $wpdb->insert(
                    $wpdb->prefix . 'tcms_user_locations',
                    $location_data
                );
            }
        }
        
        return true;
    }

    /**
     * Add user photo
     */
    public function add_user_photo($user_id, $photo_url, $is_primary = false, $description = '') {
        global $wpdb;
        
        // If setting as primary, unset any existing primary photos
        if ($is_primary) {
            $wpdb->update(
                $wpdb->prefix . 'tcms_user_photos',
                array('is_primary' => 0),
                array('user_id' => $user_id)
            );
        }
        
        // Insert new photo
        $result = $wpdb->insert(
            $wpdb->prefix . 'tcms_user_photos',
            array(
                'user_id' => $user_id,
                'photo_url' => $photo_url,
                'is_primary' => $is_primary ? 1 : 0,
                'description' => $description,
                'uploaded_at' => current_time('mysql')
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Delete user photo
     */
    public function delete_user_photo($photo_id, $user_id) {
        global $wpdb;
        
        // Check if photo belongs to user
        $photo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_user_photos WHERE id = %d AND user_id = %d",
            $photo_id, $user_id
        ));
        
        if (!$photo) {
            return false;
        }
        
        // Delete photo
        $result = $wpdb->delete(
            $wpdb->prefix . 'tcms_user_photos',
            array('id' => $photo_id)
        );
        
        // If deleted photo was primary, set another photo as primary
        if ($result && $photo->is_primary) {
            $new_primary = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tcms_user_photos WHERE user_id = %d ORDER BY uploaded_at DESC LIMIT 1",
                $user_id
            ));
            
            if ($new_primary) {
                $wpdb->update(
                    $wpdb->prefix . 'tcms_user_photos',
                    array('is_primary' => 1),
                    array('id' => $new_primary)
                );
            }
        }
        
        return $result;
    }

    /**
     * Block a user
     */
    public function block_user($user_id, $blocked_user_id) {
        global $wpdb;
        
        // Check if already blocked
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tcms_user_blocks WHERE user_id = %d AND blocked_user_id = %d",
            $user_id, $blocked_user_id
        ));
        
        if ($exists) {
            return true; // Already blocked
        }
        
        // Add block
        $result = $wpdb->insert(
            $wpdb->prefix . 'tcms_user_blocks',
            array(
                'user_id' => $user_id,
                'blocked_user_id' => $blocked_user_id,
                'created_at' => current_time('mysql')
            )
        );
        
        return $result;
    }

    /**
     * Unblock a user
     */
    public function unblock_user($user_id, $blocked_user_id) {
        global $wpdb;
        
        // Remove block
        $result = $wpdb->delete(
            $wpdb->prefix . 'tcms_user_blocks',
            array(
                'user_id' => $user_id,
                'blocked_user_id' => $blocked_user_id
            )
        );
        
        return $result;
    }

    /**
     * Get blocked users
     */
    public function get_blocked_users($user_id) {
        global $wpdb;
        
        $blocked_users = $wpdb->get_results($wpdb->prepare(
            "SELECT b.id, b.blocked_user_id, b.created_at, p.display_name
            FROM {$wpdb->prefix}tcms_user_blocks b
            LEFT JOIN {$wpdb->prefix}tcms_user_profiles p ON b.blocked_user_id = p.user_id
            WHERE b.user_id = %d
            ORDER BY b.created_at DESC",
            $user_id
        ));
        
        return $blocked_users;
    }

    /**
     * Report a user
     */
    public function report_user($reporter_id, $reported_user_id, $reason, $details = '') {
        global $wpdb;
        
        // Add report
        $result = $wpdb->insert(
            $wpdb->prefix . 'tcms_user_reports',
            array(
                'reporter_id' => $reporter_id,
                'reported_user_id' => $reported_user_id,
                'reason' => $reason,
                'details' => $details,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }
}