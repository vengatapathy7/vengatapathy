<?php
/**
 * Plugin Name: Custom Customer Management
 * Plugin URI: https://example.com
 * Description: A comprehensive customer management system for WordPress
 * Author: Vengatapathy
 * Text Domain: custom-customer-management
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CCM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CCM_PLUGIN_VERSION', '1.0.0');
define('CCM_TABLE_NAME', 'ccm_customers');

/**
 * Main Plugin Class
 */
class CustomCustomerManagement
{

    private static $instance = null;
    public $database;
    public $admin;
    public $frontend;
    public $ajax_handler;

    /**
     * Singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes()
    {
        require_once CCM_PLUGIN_PATH . 'includes/class-database.php';
        require_once CCM_PLUGIN_PATH . 'includes/class-admin.php';
        require_once CCM_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once CCM_PLUGIN_PATH . 'includes/class-ajax-handler.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init_components'));
        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        $database = new CCM_Database();
        $database->create_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clean up if needed
        flush_rewrite_rules();
    }

    /**
     * Initialize components
     */
    public function init_components()
    {
        $this->database = new CCM_Database();
        $this->admin = new CCM_Admin();
        $this->frontend = new CCM_Frontend();
        $this->ajax_handler = new CCM_Ajax_Handler();
    }

    /**
     * Load text domain for internationalization
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'custom-customer-management',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
}

/**
 * Initialize the plugin
 */
function custom_customer_management()
{
    return CustomCustomerManagement::get_instance();
}

// Start the plugin
custom_customer_management();