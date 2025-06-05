<?php
/**
 * Messaging class
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Messaging class
 */
class TCMS_Messaging {
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
        // Initialize messaging system
        add_action('wp_ajax_tcms_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_tcms_get_conversations', array($this, 'ajax_get_conversations'));
        add_action('wp_ajax_tcms_get_messages', array($this, 'ajax_get_messages'));
        add_action('wp_ajax_tcms_mark_as_read', array($this, 'ajax_mark_as_read'));
        add_action('wp_ajax_tcms_delete_message', array($this, 'ajax_delete_message'));
        add_action('wp_ajax_tcms_delete_conversation', array($this, 'ajax_delete_conversation'));
    }

    /**
     * Send message (AJAX handler)
     */
    public function ajax_send_message() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to send messages', 'tcms-messaging')));
        }

        // Get post data
        $sender_id = get_current_user_id();
        $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $attachment_url = isset($_POST['attachment_url']) ? esc_url_raw($_POST['attachment_url']) : '';
        $message_type = isset($_POST['message_type']) ? sanitize_text_field($_POST['message_type']) : 'text';

        // Validate data
        if ($receiver_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid recipient', 'tcms-messaging')));
        }

        if (empty($content) && empty($attachment_url)) {
            wp_send_json_error(array('message' => __('Message cannot be empty', 'tcms-messaging')));
        }

        // Check if sender can send message to receiver
        if (!$this->can_send_message($sender_id, $receiver_id)) {
            wp_send_json_error(array('message' => __('You cannot send messages to this user', 'tcms-messaging')));
        }

        // Send message
        $message_id = $this->send_message($sender_id, $receiver_id, $content, $attachment_url, $message_type);

        if (!$message_id) {
            wp_send_json_error(array('message' => __('Failed to send message', 'tcms-messaging')));
        }

        // Get the message data to return
        $message = $this->get_message($message_id);

        wp_send_json_success(array(
            'message' => __('Message sent successfully', 'tcms-messaging'),
            'message_data' => $message
        ));
    }

    /**
     * Get conversations (AJAX handler)
     */
    public function ajax_get_conversations() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view conversations', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        // Get conversations
        $conversations = $this->get_conversations($user_id, $limit, $offset);

        wp_send_json_success(array(
            'conversations' => $conversations,
            'total' => $this->get_conversations_count($user_id)
        ));
    }

    /**
     * Get messages (AJAX handler)
     */
    public function ajax_get_messages() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view messages', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $other_user_id = isset($_POST['other_user_id']) ? intval($_POST['other_user_id']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        if ($other_user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'tcms-messaging')));
        }

        // Get messages
        $messages = $this->get_conversation_messages($user_id, $other_user_id, $limit, $offset);
        
        // Mark messages as read
        $this->mark_conversation_as_read($user_id, $other_user_id);

        wp_send_json_success(array(
            'messages' => $messages,
            'total' => $this->get_conversation_messages_count($user_id, $other_user_id)
        ));
    }

    /**
     * Mark message as read (AJAX handler)
     */
    public function ajax_mark_as_read() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to update messages', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

        if ($message_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid message ID', 'tcms-messaging')));
        }

        // Mark message as read
        $success = $this->mark_message_as_read($message_id, $user_id);

        if (!$success) {
            wp_send_json_error(array('message' => __('Failed to mark message as read', 'tcms-messaging')));
        }

        wp_send_json_success(array(
            'message' => __('Message marked as read', 'tcms-messaging')
        ));
    }

    /**
     * Delete message (AJAX handler)
     */
    public function ajax_delete_message() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to delete messages', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

        if ($message_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid message ID', 'tcms-messaging')));
        }

        // Delete message
        $success = $this->delete_message($message_id, $user_id);

        if (!$success) {
            wp_send_json_error(array('message' => __('Failed to delete message', 'tcms-messaging')));
        }

        wp_send_json_success(array(
            'message' => __('Message deleted', 'tcms-messaging')
        ));
    }

    /**
     * Delete conversation (AJAX handler)
     */
    public function ajax_delete_conversation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcms_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'tcms-messaging')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to delete conversations', 'tcms-messaging')));
        }

        $user_id = get_current_user_id();
        $other_user_id = isset($_POST['other_user_id']) ? intval($_POST['other_user_id']) : 0;

        if ($other_user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'tcms-messaging')));
        }

        // Delete conversation
        $success = $this->delete_conversation($user_id, $other_user_id);

        if (!$success) {
            wp_send_json_error(array('message' => __('Failed to delete conversation', 'tcms-messaging')));
        }

        wp_send_json_success(array(
            'message' => __('Conversation deleted', 'tcms-messaging')
        ));
    }

    /**
     * Check if a user can send a message to another user
     */
    private function can_send_message($sender_id, $receiver_id) {
        global $wpdb;
        
        // Cannot send message to self
        if ($sender_id === $receiver_id) {
            return false;
        }
        
        // Check if receiver has blocked sender
        $is_blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tcms_user_blocks 
            WHERE (user_id = %d AND blocked_user_id = %d) 
            OR (user_id = %d AND blocked_user_id = %d)",
            $receiver_id, $sender_id, $sender_id, $receiver_id
        ));
        
        if ($is_blocked) {
            return false;
        }
        
        // Check receiver's privacy settings
        $privacy_settings = $wpdb->get_var($wpdb->prepare(
            "SELECT privacy_settings FROM {$wpdb->prefix}tcms_user_profiles WHERE user_id = %d",
            $receiver_id
        ));
        
        if ($privacy_settings) {
            $settings = json_decode($privacy_settings, true);
            $message_privacy = isset($settings['allow_messages']) ? $settings['allow_messages'] : 'everyone';
            
            if ($message_privacy === 'nobody') {
                return false;
            }
            
            if ($message_privacy === 'premium' && !tcms_is_user_premium($sender_id)) {
                return false;
            }
        }
        
        // Check if free user has reached daily message limit
        if (!tcms_is_user_premium($sender_id)) {
            $settings = get_option('tcms_settings', array());
            $free_messages_limit = isset($settings['free_messages_limit']) ? intval($settings['free_messages_limit']) : 10;
            
            if ($free_messages_limit > 0) {
                // Count messages sent today to this receiver
                $today_start = date('Y-m-d 00:00:00');
                $message_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}tcms_messages 
                    WHERE sender_id = %d AND receiver_id = %d AND created_at >= %s",
                    $sender_id, $receiver_id, $today_start
                ));
                
                if ($message_count >= $free_messages_limit) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Send a message
     */
    public function send_message($sender_id, $receiver_id, $content, $attachment_url = '', $message_type = 'text') {
        global $wpdb;
        
        // Check if sending is allowed
        if (!$this->can_send_message($sender_id, $receiver_id)) {
            return false;
        }
        
        // Insert message
        $result = $wpdb->insert(
            $wpdb->prefix . 'tcms_messages',
            array(
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'content' => $content,
                'attachment_url' => $attachment_url,
                'read_status' => 0,
                'created_at' => current_time('mysql'),
                'message_type' => $message_type
            )
        );
        
        if (!$result) {
            return false;
        }
        
        $message_id = $wpdb->insert_id;
        
        // Trigger notification for new message
        do_action('tcms_new_message', $message_id, $sender_id, $receiver_id);
        
        return $message_id;
    }

    /**
     * Get a single message
     */
    public function get_message($message_id) {
        global $wpdb;
        
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, 
            sender.display_name as sender_name, 
            sender.avatar_url as sender_avatar,
            receiver.display_name as receiver_name,
            receiver.avatar_url as receiver_avatar
            FROM {$wpdb->prefix}tcms_messages m
            LEFT JOIN {$wpdb->prefix}tcms_user_profiles sender ON m.sender_id = sender.user_id
            LEFT JOIN {$wpdb->prefix}tcms_user_profiles receiver ON m.receiver_id = receiver.user_id
            WHERE m.id = %d",
            $message_id
        ), ARRAY_A);
        
        return $message;
    }

    /**
     * Get conversations for a user
     */
    public function get_conversations($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                other_user.user_id as other_user_id,
                other_user.display_name,
                other_user.avatar_url,
                last_message.id as last_message_id,
                last_message.content as last_message,
                last_message.sender_id as last_sender_id,
                last_message.created_at as last_message_time,
                last_message.message_type,
                unread.unread_count
            FROM (
                SELECT 
                    CASE 
                        WHEN sender_id = %d THEN receiver_id 
                        ELSE sender_id 
                    END as conversation_with,
                    MAX(id) as max_id
                FROM {$wpdb->prefix}tcms_messages
                WHERE sender_id = %d OR receiver_id = %d
                GROUP BY conversation_with
            ) as conversations
            JOIN {$wpdb->prefix}tcms_messages as last_message ON last_message.id = conversations.max_id
            JOIN {$wpdb->prefix}tcms_user_profiles as other_user ON other_user.user_id = conversations.conversation_with
            LEFT JOIN (
                SELECT receiver_id, COUNT(*) as unread_count
                FROM {$wpdb->prefix}tcms_messages
                WHERE receiver_id = %d AND read_status = 0 AND deleted_by_receiver = 0
                GROUP BY receiver_id
            ) as unread ON 1=1
            ORDER BY last_message.created_at DESC
            LIMIT %d OFFSET %d",
            $user_id, $user_id, $user_id, $user_id, $limit, $offset
        ));
        
        return $conversations;
    }

    /**
     * Get total conversations count
     */
    public function get_conversations_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT 
                CASE 
                    WHEN sender_id = %d THEN receiver_id 
                    ELSE sender_id 
                END
            ) FROM {$wpdb->prefix}tcms_messages
            WHERE sender_id = %d OR receiver_id = %d",
            $user_id, $user_id, $user_id
        ));
    }

    /**
     * Get messages for a conversation
     */
    public function get_conversation_messages($user_id, $other_user_id, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*,
            sender.display_name as sender_name,
            sender.avatar_url as sender_avatar
            FROM {$wpdb->prefix}tcms_messages m
            LEFT JOIN {$wpdb->prefix}tcms_user_profiles sender ON m.sender_id = sender.user_id
            WHERE (
                (m.sender_id = %d AND m.receiver_id = %d AND m.deleted_by_sender = 0)
                OR
                (m.sender_id = %d AND m.receiver_id = %d AND m.deleted_by_receiver = 0)
            )
            ORDER BY m.created_at DESC
            LIMIT %d OFFSET %d",
            $user_id, $other_user_id, $other_user_id, $user_id, $limit, $offset
        ));
        
        return array_reverse($messages);
    }

    /**
     * Get total messages count for a conversation
     */
    public function get_conversation_messages_count($user_id, $other_user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tcms_messages
            WHERE (
                (sender_id = %d AND receiver_id = %d AND deleted_by_sender = 0)
                OR
                (sender_id = %d AND receiver_id = %d AND deleted_by_receiver = 0)
            )",
            $user_id, $other_user_id, $other_user_id, $user_id
        ));
    }

    /**
     * Mark a message as read
     */
    public function mark_message_as_read($message_id, $user_id) {
        global $wpdb;
        
        // Check if user is the receiver
        $is_receiver = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tcms_messages
            WHERE id = %d AND receiver_id = %d",
            $message_id, $user_id
        ));
        
        if (!$is_receiver) {
            return false;
        }
        
        // Update read status
        return $wpdb->update(
            $wpdb->prefix . 'tcms_messages',
            array('read_status' => 1),
            array('id' => $message_id)
        );
    }

    /**
     * Mark all messages in a conversation as read
     */
    public function mark_conversation_as_read($user_id, $other_user_id) {
        global $wpdb;
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}tcms_messages
            SET read_status = 1
            WHERE receiver_id = %d AND sender_id = %d AND read_status = 0",
            $user_id, $other_user_id
        ));
    }

    /**
     * Delete a message for a user
     */
    public function delete_message($message_id, $user_id) {
        global $wpdb;
        
        // Check if user is sender or receiver
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tcms_messages
            WHERE id = %d AND (sender_id = %d OR receiver_id = %d)",
            $message_id, $user_id, $user_id
        ));
        
        if (!$message) {
            return false;
        }
        
        // Update deletion flag
        if ($message->sender_id == $user_id) {
            return $wpdb->update(
                $wpdb->prefix . 'tcms_messages',
                array('deleted_by_sender' => 1),
                array('id' => $message_id)
            );
        } else {
            return $wpdb->update(
                $wpdb->prefix . 'tcms_messages',
                array('deleted_by_receiver' => 1),
                array('id' => $message_id)
            );
        }
    }

    /**
     * Delete entire conversation for a user
     */
    public function delete_conversation($user_id, $other_user_id) {
        global $wpdb;
        
        // Delete sent messages
        $sent_deleted = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}tcms_messages
            SET deleted_by_sender = 1
            WHERE sender_id = %d AND receiver_id = %d AND deleted_by_sender = 0",
            $user_id, $other_user_id
        ));
        
        // Delete received messages
        $received_deleted = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}tcms_messages
            SET deleted_by_receiver = 1
            WHERE sender_id = %d AND receiver_id = %d AND deleted_by_receiver = 0",
            $other_user_id, $user_id
        ));
        
        return ($sent_deleted !== false && $received_deleted !== false);
    }

    /**
     * Get unread messages count
     */
    public function get_unread_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tcms_messages
            WHERE receiver_id = %d AND read_status = 0 AND deleted_by_receiver = 0",
            $user_id
        ));
    }

    /**
     * Check if user has unread messages
     */
    public function has_unread_messages($user_id) {
        return $this->get_unread_count($user_id) > 0;
    }
}