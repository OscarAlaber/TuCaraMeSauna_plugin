<?php
/**
 * AJAX handlers
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX actions
function tcms_register_ajax_handlers() {
    // Geolocation
    add_action('wp_ajax_tcms_update_location', array(TCMS_Geolocation::get_instance(), 'ajax_update_location'));
    add_action('wp_ajax_tcms_get_location', array(TCMS_Geolocation::get_instance(), 'ajax_get_location'));
    add_action('wp_ajax_tcms_get_nearby_users', array(TCMS_Geolocation::get_instance(), 'ajax_get_nearby_users'));
    add_action('wp_ajax_tcms_get_nearby_saunas', array(TCMS_Geolocation::get_instance(), 'ajax_get_nearby_saunas'));
    
    // Messaging
    add_action('wp_ajax_tcms_send_message', array(TCMS_Messaging::get_instance(), 'ajax_send_message'));
    add_action('wp_ajax_tcms_get_conversations', array(TCMS_Messaging::get_instance(), 'ajax_get_conversations'));
    add_action('wp_ajax_tcms_get_messages', array(TCMS_Messaging::get_instance(), 'ajax_get_messages'));
    add_action('wp_ajax_tcms_mark_as_read', array(TCMS_Messaging::get_instance(), 'ajax_mark_as_read'));
    add_action('wp_ajax_tcms_delete_message', array(TCMS_Messaging::get_instance(), 'ajax_delete_message'));
    add_action('wp_ajax_tcms_delete_conversation', array(TCMS_Messaging::get_instance(), 'ajax_delete_conversation'));
    
    // Premium
    add_action('wp_ajax_tcms_subscribe_premium', array(TCMS_Premium::get_instance(), 'ajax_subscribe_premium'));
    add_action('wp_ajax_tcms_cancel_premium', array(TCMS_Premium::get_instance(), 'ajax_cancel_premium'));
    add_action('wp_ajax_tcms_get_premium_info', array(TCMS_Premium::get_instance(), 'ajax_get_premium_info'));
    
    // User profile
    add_action('wp_ajax_tcms_update_profile', 'tcms_ajax_update_profile');
    add_action('wp_ajax_tcms_upload_photo', 'tcms_ajax_upload_photo');
    add_action('wp_ajax_tcms_delete_photo', 'tcms_ajax_delete_photo');
    add_action('wp_ajax_tcms_set_primary_photo', 'tcms_ajax_set_primary_photo');
    add_action('wp_ajax_tcms_block_user', 'tcms_ajax_block_user');
    add_action('wp_ajax_tcms_unblock_user', 'tcms_ajax_unblock_user');
    add_action('wp_ajax_tcms_report_user', 'tcms_ajax_report_user');
}
add_action('init', 'tcms_register_ajax_handlers');

/**
 * Update profile (AJAX handler)
 */
function tcms_ajax_update_profile() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to update your profile', 'tcms-messaging')));
    }

    $user_id = get_current_user_id();
    $profile_data = isset($_POST['profile_data']) ? $_POST['profile_data'] : array();
    
    // Sanitize profile data
    $sanitized_data = array();
    
    if (isset($profile_data['display_name'])) {
        $sanitized_data['display_name'] = sanitize_text_field($profile_data['display_name']);
    }
    
    if (isset($profile_data['bio'])) {
        $sanitized_data['bio'] = sanitize_textarea_field($profile_data['bio']);
    }
    
    if (isset($profile_data['interests']) && is_array($profile_data['interests'])) {
        $sanitized_data['interests'] = array_map('sanitize_text_field', $profile_data['interests']);
    }
    
    if (isset($profile_data['preferences']) && is_array($profile_data['preferences'])) {
        $sanitized_data['preferences'] = array_map('sanitize_text_field', $profile_data['preferences']);
    }
    
    if (isset($profile_data['role'])) {
        $sanitized_data['role'] = tcms_sanitize_user_role($profile_data['role']);
    }
    
    if (isset($profile_data['privacy_settings']) && is_array($profile_data['privacy_settings'])) {
        $sanitized_data['privacy_settings'] = array();
        
        foreach ($profile_data['privacy_settings'] as $key => $value) {
            $sanitized_data['privacy_settings'][$key] = sanitize_text_field($value);
        }
    }
    
    // Update profile
    $success = TCMS_User::get_instance()->update_user_profile($user_id, $sanitized_data);
    
    if (!$success) {
        wp_send_json_error(array('message' => __('Failed to update profile', 'tcms-messaging')));
    }
    
    wp_send_json_success(array(
        'message' => __('Profile updated successfully', 'tcms-messaging')
    ));
}

/**
 * Upload photo (AJAX handler)
 */
function tcms_ajax_upload_photo() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to upload photos', 'tcms-messaging')));
    }

    // Check if file was uploaded
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array('message' => __('No file uploaded or upload error', 'tcms-messaging')));
    }

    $user_id = get_current_user_id();
    $is_primary = isset($_POST['is_primary']) ? (bool) $_POST['is_primary'] : false;
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    
    // Check file type
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
    $file_type = wp_check_filetype(basename($_FILES['photo']['name']));
    
    if (!in_array($_FILES['photo']['type'], $allowed_types) || empty($file_type['ext'])) {
        wp_send_json_error(array('message' => __('Invalid file type. Please upload a JPG, PNG or GIF', 'tcms-messaging')));
    }
    
    // Check file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($_FILES['photo']['size'] > $max_size) {
        wp_send_json_error(array('message' => __('File too large. Maximum size is 5MB', 'tcms-messaging')));
    }
    
    // Get upload directory
    $upload_dir = tcms_get_upload_dir();
    
    // Create user directory if it doesn't exist
    $user_dir = $upload_dir['path'] . '/' . $user_id;
    if (!file_exists($user_dir)) {
        wp_mkdir_p($user_dir);
    }
    
    // Generate unique filename
    $filename = wp_unique_filename($user_dir, $_FILES['photo']['name']);
    $file_path = $user_dir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
        wp_send_json_error(array('message' => __('Failed to upload file', 'tcms-messaging')));
    }
    
    // Generate photo URL
    $photo_url = $upload_dir['url'] . '/' . $user_id . '/' . $filename;
    
    // Add photo to database
    $photo_id = TCMS_User::get_instance()->add_user_photo($user_id, $photo_url, $is_primary, $description);
    
    if (!$photo_id) {
        wp_send_json_error(array('message' => __('Failed to save photo information', 'tcms-messaging')));
    }
    
    wp_send_json_success(array(
        'message' => __('Photo uploaded successfully', 'tcms-messaging'),
        'photo_id' => $photo_id,
        'photo_url' => $photo_url,
        'is_primary' => $is_primary
    ));
}

/**
 * Delete photo (AJAX handler)
 */
function tcms_ajax_delete_photo() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to delete photos', 'tcms-messaging')));
    }

    $user_id = get_current_user_id();
    $photo_id = isset($_POST['photo_id']) ? intval($_POST['photo_id']) : 0;
    
    if ($photo_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid photo ID', 'tcms-messaging')));
    }
    
    // Delete photo
    $success = TCMS_User::get_instance()->delete_user_photo($photo_id, $user_id);
    
    if (!$success) {
        wp_send_json_error(array('message' => __('Failed to delete photo', 'tcms-messaging')));
    }
    
    wp_send_json_success(array(
        'message' => __('Photo deleted successfully', 'tcms-messaging')
    ));
}

/**
 * Set primary photo (AJAX handler)
 */
function tcms_ajax_set_primary_photo() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to update photos', 'tcms-messaging')));
    }

    $user_id = get_current_user_id();
    $photo_id = isset($_POST['photo_id']) ? intval($_POST['photo_id']) : 0;
    
    if ($photo_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid photo ID', 'tcms-messaging')));
    }
    
    global $wpdb;
    
    // Check if photo belongs to user
    $photo = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tcms_user_photos WHERE id = %d AND user_id = %d",
        $photo_id, $user_id
    ));
    
    if (!$photo) {
        wp_send_json_error(array('message' => __('Photo not found or access denied', 'tcms-messaging')));
    }
    
    // Reset all user photos to non-primary
    $wpdb->update(
        $wpdb->prefix . 'tcms_user_photos',
        array('is_primary' => 0),
        array('user_id' => $user_id)
    );
    
    // Set selected photo as primary
    $success = $wpdb->update(
        $wpdb->prefix . 'tcms_user_photos',
        array('is_primary' => 1),
        array('id' => $photo_id)
    );
    
    if ($success === false) {
        wp_send_json_error(array('message' => __('Failed to set primary photo', 'tcms-messaging')));
    }
    
    wp_send_json_success(array(
        'message' => __('Primary photo updated successfully', 'tcms-messaging'),
        'photo_url' => $photo->photo_url
    ));
}

/**
 * Block user (AJAX handler)
 */
function tcms_ajax_block_user() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to block users', 'tcms-messaging')));
    }

    $user_id = get_current_user_id();
    $blocked_user_id = isset($_POST['blocked_user_id']) ? intval($_POST['blocked_user_id']) : 0;
    
    if ($blocked_user_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid user ID', 'tcms-messaging')));
    }
    
    if ($blocked_user_id === $user_id) {
        wp_send_json_error(array('message' => __('You cannot block yourself', 'tcms-messaging')));
    }
    
    // Block user
    $success = TCMS_User::get_instance()->block_user($user_id, $blocked_user_id);
    
    if (!$success) {
        wp_send_json_error(array('message' => __('Failed to block user', 'tcms-messaging')));
    }
    
    wp_send_json_success(array(
        'message' => __('User blocked successfully', 'tcms-messaging')
    ));
}

/**
 * Unblock user (AJAX handler)
 */
function tcms_ajax_unblock_user() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to unblock users', 'tcms-messaging')));
    }

    $user_id = get_current_user_id();
    $blocked_user_id = isset($_POST['blocked_user_id']) ? intval($_POST['blocked_user_id']) : 0;
    
    if ($blocked_user_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid user ID', 'tcms-messaging')));
    }
    
    // Unblock user
    $success = TCMS_User::get_instance()->unblock_user($user_id, $blocked_user_id);
    
    if (!$success) {
        wp_send_json_error(array('message' => __('Failed to unblock user', 'tcms-messaging')));
    }
    
    wp_send_json_success(array(
        'message' => __('User unblocked successfully', 'tcms-messaging')
    ));
}

/**
 * Report user (AJAX handler)
 */
function tcms_ajax_report_user() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to report users', 'tcms-messaging')));
    }

    $reporter_id = get_current_user_id();
    $reported_user_id = isset($_POST['reported_user_id']) ? intval($_POST['reported_user_id']) : 0;
    $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
    $details = isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '';
    
    if ($reported_user_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid user ID', 'tcms-messaging')));
    }
    
    if (empty($reason)) {
        wp_send_json_error(array('message' => __('Please select a reason for reporting', 'tcms-messaging')));
    }
    
    // Report user
    $report_id = TCMS_User::get_instance()->report_user($reporter_id, $reported_user_id, $reason, $details);
    
    if (!$report_id) {
        wp_send_json_error(array('message' => __('Failed to submit report', 'tcms-messaging')));
    }
    
    wp_send_json_success(array(
        'message' => __('User reported successfully. Our team will review the report.', 'tcms-messaging'),
        'report_id' => $report_id
    ));
}