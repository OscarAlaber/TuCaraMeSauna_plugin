<?php
/**
 * Database handler class
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database handler class
 */
class TCMS_Database {
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
        // No initialization needed for now
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Messages table
        $table_messages = $wpdb->prefix . 'tcms_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            content longtext NOT NULL,
            attachment_url varchar(255) DEFAULT '',
            read_status tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            deleted_by_sender tinyint(1) DEFAULT 0,
            deleted_by_receiver tinyint(1) DEFAULT 0,
            message_type varchar(20) DEFAULT 'text',
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY read_status (read_status)
        ) $charset_collate;";

        // User locations table
        $table_locations = $wpdb->prefix . 'tcms_user_locations';
        $sql_locations = "CREATE TABLE $table_locations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            city varchar(100) DEFAULT '',
            country varchar(100) DEFAULT '',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            privacy_level tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY location (latitude, longitude)
        ) $charset_collate;";

        // User profile table
        $table_profiles = $wpdb->prefix . 'tcms_user_profiles';
        $sql_profiles = "CREATE TABLE $table_profiles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            display_name varchar(100) DEFAULT '',
            bio text DEFAULT '',
            interests text DEFAULT '',
            preferences text DEFAULT '',
            avatar_url varchar(255) DEFAULT '',
            is_verified tinyint(1) DEFAULT 0,
            privacy_settings text DEFAULT '',
            last_active datetime DEFAULT CURRENT_TIMESTAMP,
            role varchar(20) DEFAULT 'versatile',
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY is_verified (is_verified)
        ) $charset_collate;";

        // User photos table
        $table_photos = $wpdb->prefix . 'tcms_user_photos';
        $sql_photos = "CREATE TABLE $table_photos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            photo_url varchar(255) NOT NULL,
            is_primary tinyint(1) DEFAULT 0,
            description varchar(255) DEFAULT '',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_primary (is_primary)
        ) $charset_collate;";

        // User premium subscriptions table
        $table_premium = $wpdb->prefix . 'tcms_premium_subscriptions';
        $sql_premium = "CREATE TABLE $table_premium (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_name varchar(50) NOT NULL,
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime NOT NULL,
            payment_method varchar(50) DEFAULT '',
            transaction_id varchar(100) DEFAULT '',
            amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'active',
            auto_renew tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY end_date (end_date)
        ) $charset_collate;";

        // User reports table
        $table_reports = $wpdb->prefix . 'tcms_user_reports';
        $sql_reports = "CREATE TABLE $table_reports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            reporter_id bigint(20) NOT NULL,
            reported_user_id bigint(20) NOT NULL,
            reason varchar(50) NOT NULL,
            details text DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            admin_notes text DEFAULT '',
            PRIMARY KEY  (id),
            KEY reporter_id (reporter_id),
            KEY reported_user_id (reported_user_id),
            KEY status (status)
        ) $charset_collate;";

        // User blocks table
        $table_blocks = $wpdb->prefix . 'tcms_user_blocks';
        $sql_blocks = "CREATE TABLE $table_blocks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            blocked_user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_block (user_id, blocked_user_id),
            KEY user_id (user_id),
            KEY blocked_user_id (blocked_user_id)
        ) $charset_collate;";

        // Saunas table
        $table_saunas = $wpdb->prefix . 'tcms_saunas';
        $sql_saunas = "CREATE TABLE $table_saunas (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text DEFAULT '',
            address varchar(255) DEFAULT '',
            city varchar(100) DEFAULT '',
            country varchar(100) DEFAULT '',
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            rating decimal(3,2) DEFAULT 0.00,
            featured_image varchar(255) DEFAULT '',
            opening_hours text DEFAULT '',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY location (latitude, longitude),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Create tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_messages);
        dbDelta($sql_locations);
        dbDelta($sql_profiles);
        dbDelta($sql_photos);
        dbDelta($sql_premium);
        dbDelta($sql_reports);
        dbDelta($sql_blocks);
        dbDelta($sql_saunas);
    }
}