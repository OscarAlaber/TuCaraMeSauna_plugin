<?php
/**
 * Premium features class
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium features class
 */
class TCMS_Premium {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Premium plans
     */
    private $plans = array();

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
        // Define premium plans
        $this->plans = array(
            'monthly' => array(
                'name' => __('Monthly Premium', 'tcms-messaging'),
                'price' => 9.99,
                'period' => 'month',
                'days' => 30,
                'features' => array(
                    'unlimited_messages' => true,
                    'advanced_search' => true,
                    'see_who_liked' => true,
                    'read_receipts' => true,
                    'priority_listing' => true,
                    'ad_free_experience' => true,
                ),
            ),
            'quarterly' => array(
                'name' => __('Quarterly Premium', 'tcms-messaging'),
                'price' => 24.99,
                'period' => 'quarter',
                'days' => 90,
                'features' => array(
                    'unlimited_messages' => true,
                    'advanced_search' => true,
                    'see_who_liked' => true,
                    'read_receipts' => true,
                    'priority_listing' => true,
                    'ad_free_experience' => true,
                ),
                'savings' => 16.7, // Savings percentage
            ),
            'annual' => array(
                'name' => __('Annual Premium', 'tcms-messaging'),
                'price' => 79.99,
                'period' => 'year',
                'days' => 365,
                'features' => array(
                    'unlimited_messages' => true,
                    'advanced_search' => true,
                    'see_who_liked' => true,
                    'read_receipts' => true,
                    'priority_listing' => true,
                    'ad_free_experience' => true,
                    'exclusive_features' => true,
                ),
                'savings' => 33.3, // Savings percentage
                'best_value' => true,
            ),
        );

        // Initialize premium features
        add_action('wp_ajax_tcms_subscribe_premium', array($this, 'ajax_subscribe_premium'));
        add_action('wp_ajax_tcms_cancel_premium', array($this, 'ajax_cancel_premium'));
        add_action('wp_ajax_tcms_get_premium_info', array($this, 'ajax_get_premium_info'));
        
        // Check for expired subscriptions daily
        add_action('tcms_daily_cleanup', array($this, 'check_expired_subscriptions'));
    }

    /**
     * Subscribe to premium (AJAX handler)
     */
    public function ajax_subscribe_premium() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to subscribe', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $plan = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : '';
        $auto_renew = isset($_POST['auto_renew']) ? (bool) $_POST['auto_renew'] : false;

        // Validate plan
        if (!isset($this->plans[$plan])) {
            wp_send_json_error(array('message' => __('Invalid subscription plan', 'tcms-messaging')));
        }

        // Subscribe user to premium
        $subscription_id = $this->subscribe_user($user_id, $plan, $payment_method, $transaction_id, $auto_renew);

        if (!$subscription_id) {
            wp_send_json_error(array('message' => __('Failed to process subscription', 'tcms-messaging')));
        }

        wp_send_json_success(array(
            'message' => __('Subscription successful', 'tcms-messaging'),
            'subscription_id' => $subscription_id,
            'plan' => $plan,
            'end_date' => $this->get_subscription_end_date($user_id)
        ));
    }

    /**
     * Cancel premium subscription (AJAX handler)
     */
    public function ajax_cancel_premium() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to cancel subscription', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();

        // Cancel subscription
        $success = $this->cancel_subscription($user_id);

        if (!$success) {
            wp_send_json_error(array('message' => __('Failed to cancel subscription', 'tcms-messaging')));
        }

        wp_send_json_success(array(
            'message' => __('Subscription cancelled successfully', 'tcms-messaging')
        ));
    }

    /**
     * Get premium information (AJAX handler)
     */
    public function ajax_get_premium_info() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view premium info', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();

        // Get premium info
        $premium_info = $this->get_user_premium_info($user_id);

        wp_send_json_success(array(
            'premium_info' => $premium_info,
            'available_plans' => $this->plans
        ));
    }

    /**
     * Subscribe user to premium
     */
    public function subscribe_user($user_id, $plan, $payment_method, $transaction_id = '', $auto_renew = false) {
        global $wpdb;
        
        // Check if plan exists
        if (!isset($this->plans[$plan])) {
            return false;
        }
        
        $plan_data = $this->plans[$plan];
        
        // Calculate end date
        $end_date = date('Y-m-d H:i:s', strtotime('+' . $plan_data['days'] . ' days'));
        
        // Check if user already has an active subscription
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_premium_subscriptions 
            WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        if ($existing) {
            // Update existing subscription
            $result = $wpdb->update(
                $wpdb->prefix . 'tcms_premium_subscriptions',
                array(
                    'plan_name' => $plan,
                    'end_date' => $end_date,
                    'payment_method' => $payment_method,
                    'transaction_id' => $transaction_id,
                    'amount' => $plan_data['price'],
                    'auto_renew' => $auto_renew ? 1 : 0
                ),
                array('id' => $existing->id)
            );
            
            if ($result !== false) {
                do_action('tcms_premium_renewed', $user_id, $plan, $end_date);
                return $existing->id;
            }
            
            return false;
        }
        
        // Create new subscription
        $result = $wpdb->insert(
            $wpdb->prefix . 'tcms_premium_subscriptions',
            array(
                'user_id' => $user_id,
                'plan_name' => $plan,
                'start_date' => current_time('mysql'),
                'end_date' => $end_date,
                'payment_method' => $payment_method,
                'transaction_id' => $transaction_id,
                'amount' => $plan_data['price'],
                'status' => 'active',
                'auto_renew' => $auto_renew ? 1 : 0
            )
        );
        
        if (!$result) {
            return false;
        }
        
        $subscription_id = $wpdb->insert_id;
        
        // Trigger subscription event
        do_action('tcms_premium_subscribed', $user_id, $plan, $end_date);
        
        return $subscription_id;
    }

    /**
     * Cancel user subscription
     */
    public function cancel_subscription($user_id) {
        global $wpdb;
        
        // Update subscription status
        $result = $wpdb->update(
            $wpdb->prefix . 'tcms_premium_subscriptions',
            array(
                'status' => 'cancelled',
                'auto_renew' => 0
            ),
            array(
                'user_id' => $user_id,
                'status' => 'active'
            )
        );
        
        if ($result !== false) {
            do_action('tcms_premium_cancelled', $user_id);
            return true;
        }
        
        return false;
    }

    /**
     * Check if a user is premium
     */
    public function is_user_premium($user_id) {
        global $wpdb;
        
        $subscription = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tcms_premium_subscriptions 
            WHERE user_id = %d AND status = 'active' AND end_date > %s",
            $user_id, current_time('mysql')
        ));
        
        return !empty($subscription);
    }

    /**
     * Get user's subscription end date
     */
    public function get_subscription_end_date($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT end_date FROM {$wpdb->prefix}tcms_premium_subscriptions 
            WHERE user_id = %d AND status = 'active' 
            ORDER BY end_date DESC LIMIT 1",
            $user_id
        ));
    }

    /**
     * Get user premium information
     */
    public function get_user_premium_info($user_id) {
        global $wpdb;
        
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_premium_subscriptions 
            WHERE user_id = %d AND status = 'active' 
            ORDER BY end_date DESC LIMIT 1",
            $user_id
        ));
        
        if (!$subscription) {
            return array(
                'is_premium' => false,
                'plan' => null,
                'end_date' => null,
                'days_left' => 0,
                'auto_renew' => false
            );
        }
        
        // Calculate days left
        $end_date = new DateTime($subscription->end_date);
        $now = new DateTime();
        $interval = $now->diff($end_date);
        $days_left = $interval->days;
        
        // Get plan details
        $plan_details = isset($this->plans[$subscription->plan_name]) 
            ? $this->plans[$subscription->plan_name] 
            : null;
        
        return array(
            'is_premium' => true,
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan_name,
            'plan_details' => $plan_details,
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'days_left' => $days_left,
            'auto_renew' => (bool) $subscription->auto_renew,
            'payment_method' => $subscription->payment_method
        );
    }

    /**
     * Check for expired subscriptions
     */
    public function check_expired_subscriptions() {
        global $wpdb;
        
        // Update expired subscriptions
        $expired = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}tcms_premium_subscriptions 
            SET status = 'expired' 
            WHERE status = 'active' AND end_date < %s AND auto_renew = 0",
            current_time('mysql')
        ));
        
        // Process auto-renewals
        $auto_renew_subscriptions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_premium_subscriptions 
            WHERE status = 'active' AND end_date < %s AND auto_renew = 1",
            current_time('mysql')
        ));
        
        foreach ($auto_renew_subscriptions as $subscription) {
            // Implement auto-renewal logic here
            // This would typically involve:
            // 1. Charging the customer using their saved payment method
            // 2. Extending their subscription end date
            
            // For now, we'll just mark them as needing renewal
            $wpdb->update(
                $wpdb->prefix . 'tcms_premium_subscriptions',
                array('status' => 'pending_renewal'),
                array('id' => $subscription->id)
            );
            
            // Notify admin about pending renewals
            $admin_email = get_option('admin_email');
            wp_mail(
                $admin_email,
                __('Subscription pending renewal', 'tcms-messaging'),
                sprintf(
                    __('User ID %d subscription needs manual renewal. Plan: %s', 'tcms-messaging'),
                    $subscription->user_id,
                    $subscription->plan_name
                )
            );
        }
    }

    /**
     * Get available premium plans
     */
    public function get_plans() {
        return $this->plans;
    }
}

/**
 * Helper function to check if a user is premium
 */
function tcms_is_user_premium($user_id) {
    return TCMS_Premium::get_instance()->is_user_premium($user_id);
}

/**
 * Helper function to get user's premium info
 */
function tcms_get_user_premium_info($user_id) {
    return TCMS_Premium::get_instance()->get_user_premium_info($user_id);
}