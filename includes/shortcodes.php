<?php
/**
 * Shortcodes
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register shortcodes
 */
add_shortcode('tcms_users_nearby', 'tcms_users_nearby_shortcode');
add_shortcode('tcms_user_profile', 'tcms_user_profile_shortcode');
add_shortcode('tcms_messages', 'tcms_messages_shortcode');
add_shortcode('tcms_premium_membership', 'tcms_premium_membership_shortcode');
add_shortcode('tcms_saunas_nearby', 'tcms_saunas_nearby_shortcode');

/**
 * Users Nearby shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function tcms_users_nearby_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return tcms_get_login_prompt(__('You must be logged in to view nearby users', 'tcms-messaging'));
    }
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'limit' => 20,
        'distance' => 50,
    ), $atts, 'tcms_users_nearby');
    
    // Enqueue scripts and styles
    wp_enqueue_script('tcms-geolocation-script');
    
    // Start output buffer
    ob_start();
    
    // Include template
    include TCMS_PLUGIN_DIR . 'templates/users-nearby.php';
    
    // Return buffer contents
    return ob_get_clean();
}

/**
 * User Profile shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function tcms_user_profile_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return tcms_get_login_prompt(__('You must be logged in to view user profiles', 'tcms-messaging'));
    }
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'user_id' => 0,
    ), $atts, 'tcms_user_profile');
    
    $user_id = intval($atts['user_id']);
    
    // If no user_id provided, check query var
    if ($user_id === 0) {
        $user_id = get_query_var('user_id', 0);
    }
    
    // If still no user_id, show current user's profile
    if ($user_id === 0) {
        $user_id = get_current_user_id();
    }
    
    // Check if user exists
    if (!get_userdata($user_id)) {
        return '<div class="tcms-error">' . __('User not found', 'tcms-messaging') . '</div>';
    }
    
    // Start output buffer
    ob_start();
    
    // Include template
    include TCMS_PLUGIN_DIR . 'templates/user-profile.php';
    
    // Return buffer contents
    return ob_get_clean();
}

/**
 * Messages shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function tcms_messages_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return tcms_get_login_prompt(__('You must be logged in to view messages', 'tcms-messaging'));
    }
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'conversation_with' => 0,
    ), $atts, 'tcms_messages');
    
    $conversation_with = intval($atts['conversation_with']);
    
    // If no conversation_with provided, check query var
    if ($conversation_with === 0) {
        $conversation_with = get_query_var('conversation_with', 0);
    }
    
    // Enqueue scripts and styles
    wp_enqueue_script('tcms-messaging-script');
    
    // Start output buffer
    ob_start();
    
    // Include template
    include TCMS_PLUGIN_DIR . 'templates/messages.php';
    
    // Return buffer contents
    return ob_get_clean();
}

/**
 * Premium Membership shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function tcms_premium_membership_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return tcms_get_login_prompt(__('You must be logged in to access premium features', 'tcms-messaging'));
    }
    
    // Start output buffer
    ob_start();
    
    // Include template
    include TCMS_PLUGIN_DIR . 'templates/premium.php';
    
    // Return buffer contents
    return ob_get_clean();
}

/**
 * Saunas Nearby shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function tcms_saunas_nearby_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return tcms_get_login_prompt(__('You must be logged in to view nearby saunas', 'tcms-messaging'));
    }
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'limit' => 20,
        'distance' => 50,
    ), $atts, 'tcms_saunas_nearby');
    
    // Enqueue scripts and styles
    wp_enqueue_script('tcms-geolocation-script');
    
    // Start output buffer
    ob_start();
    
    // Include template
    include TCMS_PLUGIN_DIR . 'templates/saunas-nearby.php';
    
    // Return buffer contents
    return ob_get_clean();
}

/**
 * Get login prompt HTML
 *
 * @param string $message Message to display
 * @return string Login prompt HTML
 */
function tcms_get_login_prompt($message) {
    $html = '<div class="tcms-login-required">';
    $html .= '<h3>' . __('Login Required', 'tcms-messaging') . '</h3>';
    $html .= '<p>' . $message . '</p>';
    $html .= '<div class="tcms-login-buttons">';
    $html .= '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="tcms-btn tcms-btn-primary">' . __('Login', 'tcms-messaging') . '</a>';
    
    if (get_option('users_can_register')) {
        $html .= '<a href="' . esc_url(wp_registration_url()) . '" class="tcms-btn tcms-btn-secondary">' . __('Register', 'tcms-messaging') . '</a>';
    }
    
    $html .= '</div></div>';
    
    return $html;
}