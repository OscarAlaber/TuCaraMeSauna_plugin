<?php
/**
 * Plugin Name: TCMS Messaging System
 * Plugin URI: https://tucaramesauna.com
 * Description: A comprehensive messaging and social connection system for WordPress
 * Version: 1.0.0
 * Author: TuCaraMeSauna
 * Author URI: https://tucaramesauna.com
 * Text Domain: tcms-messaging
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TCMS_VERSION', '1.0.0');
define('TCMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TCMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TCMS_PLUGIN_FILE', __FILE__);
define('TCMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class TCMS_Messaging_System {
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
        // Include required files
        $this->include_files();
        
        // Initialize the plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Register activation and deactivation hooks
        register_activation_hook(TCMS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(TCMS_PLUGIN_FILE, array($this, 'deactivate'));
    }

    /**
     * Include required files
     */
    private function include_files() {
        // Core functionality
        require_once TCMS_PLUGIN_DIR . 'includes/functions.php';
        require_once TCMS_PLUGIN_DIR . 'includes/class-tcms-database.php';
        require_once TCMS_PLUGIN_DIR . 'includes/class-tcms-user.php';
        require_once TCMS_PLUGIN_DIR . 'includes/class-tcms-messaging.php';
        require_once TCMS_PLUGIN_DIR . 'includes/class-tcms-geolocation.php';
        require_once TCMS_PLUGIN_DIR . 'includes/class-tcms-premium.php';
        require_once TCMS_PLUGIN_DIR . 'includes/shortcodes.php';
        require_once TCMS_PLUGIN_DIR . 'includes/ajax-handlers.php';
        
        // Admin
        if (is_admin()) {
            require_once TCMS_PLUGIN_DIR . 'admin/class-tcms-admin.php';
        }
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('tcms-messaging', false, dirname(TCMS_PLUGIN_BASENAME) . '/languages');

        // Initialize assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Initialize components
        $this->init_components();
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize database handler
        TCMS_Database::get_instance();
        
        // Initialize user management
        TCMS_User::get_instance();
        
        // Initialize messaging system
        TCMS_Messaging::get_instance();
        
        // Initialize geolocation system
        TCMS_Geolocation::get_instance();
        
        // Initialize premium features
        TCMS_Premium::get_instance();
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_assets() {
        // Main CSS
        wp_enqueue_style(
            'tcms-main-style',
            TCMS_PLUGIN_URL . 'assets/css/tcms-main.css',
            array(),
            TCMS_VERSION
        );
        
        // Responsive CSS
        wp_enqueue_style(
            'tcms-responsive-style',
            TCMS_PLUGIN_URL . 'assets/css/tcms-responsive.css',
            array('tcms-main-style'),
            TCMS_VERSION
        );
        
        // Main JavaScript
        wp_enqueue_script(
            'tcms-main-script',
            TCMS_PLUGIN_URL . 'assets/js/tcms-main.js',
            array('jquery'),
            TCMS_VERSION,
            true
        );
        
        // Geolocation JavaScript
        wp_enqueue_script(
            'tcms-geolocation-script',
            TCMS_PLUGIN_URL . 'assets/js/tcms-geolocation.js',
            array('jquery', 'tcms-main-script'),
            TCMS_VERSION,
            true
        );
        
        // Messaging JavaScript
        wp_enqueue_script(
            'tcms-messaging-script',
            TCMS_PLUGIN_URL . 'assets/js/tcms-messaging.js',
            array('jquery', 'tcms-main-script'),
            TCMS_VERSION,
            true
        );
        
        // Localize script with Ajax URL and security nonce
        wp_localize_script('tcms-main-script', 'tcms_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tcms_nonce'),
            'is_logged_in' => is_user_logged_in() ? 'true' : 'false',
            'user_id' => get_current_user_id(),
            'is_premium' => tcms_is_user_premium(get_current_user_id()) ? 'true' : 'false',
            'strings' => array(
                'error' => __('Error occurred. Please try again.', 'tcms-messaging'),
                'success' => __('Operation completed successfully.', 'tcms-messaging'),
                'confirm_delete' => __('Are you sure you want to delete this?', 'tcms-messaging'),
                'loading' => __('Loading...', 'tcms-messaging'),
            )
        ));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        TCMS_Database::create_tables();
        
        // Create required pages
        $this->create_pages();
        
        // Set default options
        $this->set_default_options();
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create required pages on activation
     */
    private function create_pages() {
        $pages = array(
            'users-nearby' => array(
                'title' => __('Users Nearby', 'tcms-messaging'),
                'content' => '[tcms_users_nearby]',
            ),
            'user-profile' => array(
                'title' => __('User Profile', 'tcms-messaging'),
                'content' => '[tcms_user_profile]',
            ),
            'messages' => array(
                'title' => __('Messages', 'tcms-messaging'),
                'content' => '[tcms_messages]',
            ),
            'premium' => array(
                'title' => __('Premium Membership', 'tcms-messaging'),
                'content' => '[tcms_premium_membership]',
            ),
        );
        
        foreach ($pages as $slug => $page_data) {
            $page_exists = get_page_by_path($slug);
            
            if (!$page_exists) {
                wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                ));
            }
        }
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = array(
            'max_distance' => 50, // km
            'free_messages_limit' => 10,
            'require_verification' => true,
            'enable_geolocation' => true,
            'delete_data_on_uninstall' => false,
        );
        
        update_option('tcms_settings', $default_options);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('tcms_daily_cleanup');
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function tcms_messaging_system() {
    return TCMS_Messaging_System::get_instance();
}

// Start the plugin
tcms_messaging_system();