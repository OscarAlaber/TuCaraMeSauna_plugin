<?php
/**
 * Geolocation class
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Geolocation class
 */
class TCMS_Geolocation {
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
        // Initialize geolocation
        add_action('wp_ajax_tcms_update_location', array($this, 'ajax_update_location'));
        add_action('wp_ajax_tcms_get_location', array($this, 'ajax_get_location'));
        add_action('wp_ajax_tcms_get_nearby_users', array($this, 'ajax_get_nearby_users'));
        add_action('wp_ajax_tcms_get_nearby_saunas', array($this, 'ajax_get_nearby_saunas'));
    }

    /**
     * Update user location (AJAX handler)
     */
    public function ajax_update_location() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to update location', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        $privacy_level = isset($_POST['privacy_level']) ? intval($_POST['privacy_level']) : 1;

        // Validate coordinates
        if ($latitude == 0 && $longitude == 0) {
            wp_send_json_error(array('message' => __('Invalid coordinates', 'tcms-messaging')));
        }

        // Update location
        $success = $this->update_user_location($user_id, $latitude, $longitude, $city, $country, $privacy_level);

        if (!$success) {
            wp_send_json_error(array('message' => __('Failed to update location', 'tcms-messaging')));
        }

        wp_send_json_success(array(
            'message' => __('Location updated successfully', 'tcms-messaging'),
            'location' => array(
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => $city,
                'country' => $country,
                'privacy_level' => $privacy_level
            )
        ));
    }

    /**
     * Get user location (AJAX handler)
     */
    public function ajax_get_location() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to get location', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();

        // Get location
        $location = $this->get_user_location($user_id);

        if (!$location) {
            wp_send_json_error(array('message' => __('Location not found', 'tcms-messaging')));
        }

        wp_send_json_success(array(
            'location' => $location
        ));
    }

    /**
     * Get nearby users (AJAX handler)
     */
    public function ajax_get_nearby_users() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to search users', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
        $distance = isset($_POST['distance']) ? intval($_POST['distance']) : 50;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();

        // If coordinates not provided, use user's saved location
        if ($latitude == 0 && $longitude == 0) {
            $location = $this->get_user_location($user_id);
            
            if ($location) {
                $latitude = $location['latitude'];
                $longitude = $location['longitude'];
            } else {
                wp_send_json_error(array('message' => __('Location not available', 'tcms-messaging')));
            }
        }

        // Get nearby users
        $nearby_users = TCMS_User::get_instance()->get_nearby_users(
            $latitude,
            $longitude,
            $distance,
            $limit,
            $offset,
            $filters
        );

        wp_send_json_success(array(
            'users' => $nearby_users,
            'total' => count($nearby_users), // This is only the count of the current page
            'location' => array(
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance' => $distance
            )
        ));
    }

    /**
     * Get nearby saunas (AJAX handler)
     */
    public function ajax_get_nearby_saunas() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to search saunas', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
        $distance = isset($_POST['distance']) ? intval($_POST['distance']) : 50;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        // If coordinates not provided, use user's saved location
        if ($latitude == 0 && $longitude == 0) {
            $location = $this->get_user_location($user_id);
            
            if ($location) {
                $latitude = $location['latitude'];
                $longitude = $location['longitude'];
            } else {
                wp_send_json_error(array('message' => __('Location not available', 'tcms-messaging')));
            }
        }

        // Get nearby saunas
        $nearby_saunas = $this->get_nearby_saunas(
            $latitude,
            $longitude,
            $distance,
            $limit,
            $offset
        );

        wp_send_json_success(array(
            'saunas' => $nearby_saunas,
            'total' => count($nearby_saunas), // This is only the count of the current page
            'location' => array(
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance' => $distance
            )
        ));
    }

    /**
     * Update user location
     */
    public function update_user_location($user_id, $latitude, $longitude, $city = '', $country = '', $privacy_level = 1) {
        global $wpdb;
        
        // Check if location exists
        $location_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tcms_user_locations WHERE user_id = %d",
            $user_id
        ));
        
        if ($location_exists) {
            // Update existing location
            $result = $wpdb->update(
                $wpdb->prefix . 'tcms_user_locations',
                array(
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'city' => $city,
                    'country' => $country,
                    'privacy_level' => $privacy_level,
                    'last_updated' => current_time('mysql')
                ),
                array('user_id' => $user_id)
            );
            
            return $result !== false;
        } else {
            // Insert new location
            $result = $wpdb->insert(
                $wpdb->prefix . 'tcms_user_locations',
                array(
                    'user_id' => $user_id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'city' => $city,
                    'country' => $country,
                    'privacy_level' => $privacy_level,
                    'last_updated' => current_time('mysql')
                )
            );
            
            return $result !== false;
        }
    }

    /**
     * Get user location
     */
    public function get_user_location($user_id) {
        global $wpdb;
        
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_user_locations WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        
        return $location;
    }

    /**
     * Get nearby saunas
     */
    public function get_nearby_saunas($latitude, $longitude, $distance_km = 50, $limit = 20, $offset = 0) {
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
                s.*,
                ROUND(
                    {$earth_radius} * 2 * ASIN(
                        SQRT(
                            POWER(SIN(RADIANS(%f - s.latitude) / 2), 2) +
                            COS(RADIANS(%f)) * COS(RADIANS(s.latitude)) *
                            POWER(SIN(RADIANS(%f - s.longitude) / 2), 2)
                        )
                    )
                ) AS distance
            FROM {$wpdb->prefix}tcms_saunas s
            WHERE s.latitude BETWEEN %f AND %f
            AND s.longitude BETWEEN %f AND %f
            AND s.is_active = 1
            HAVING distance <= %d
            ORDER BY distance ASC
            LIMIT %d OFFSET %d";
        
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
     * Get geocoding information using OpenStreetMap Nominatim API
     */
    public function reverse_geocode($latitude, $longitude) {
        $url = add_query_arg(
            array(
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'addressdetails' => 1
            ),
            'https://nominatim.openstreetmap.org/reverse'
        );
        
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'TCMS Messaging System WordPress Plugin'
            )
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return false;
        }
        
        return array(
            'city' => isset($data['address']['city']) ? $data['address']['city'] : 
                     (isset($data['address']['town']) ? $data['address']['town'] : 
                     (isset($data['address']['village']) ? $data['address']['village'] : '')),
            'country' => isset($data['address']['country']) ? $data['address']['country'] : '',
            'display_name' => isset($data['display_name']) ? $data['display_name'] : '',
            'address' => isset($data['address']) ? $data['address'] : array()
        );
    }

    /**
     * Calculate distance between two coordinates
     * 
     * @param float $lat1 First latitude
     * @param float $lon1 First longitude
     * @param float $lat2 Second latitude
     * @param float $lon2 Second longitude
     * @param string $unit Unit (K for kilometers, M for miles, N for nautical miles)
     * @return float Distance in the specified unit
     */
    public function calculate_distance($lat1, $lon1, $lat2, $lon2, $unit = 'K') {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }
        
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        
        if ($unit == 'K') {
            return ($miles * 1.609344);
        } else if ($unit == 'N') {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}