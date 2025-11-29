<?php
/**
 * Frontend display handler
 */
class CCM_Frontend
{

    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->database = new CCM_Database();

        add_shortcode('display_customers', array($this, 'display_customers_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts()
    {
        wp_enqueue_style(
            'ccm-frontend-css',
            CCM_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CCM_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ccm-frontend-js',
            CCM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            CCM_PLUGIN_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('ccm-frontend-js', 'ccm_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ccm_frontend_nonce')
        ));
    }

    /**
     * Display customers shortcode
     */
    public function display_customers_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'per_page' => 10
        ), $atts, 'display_customers');

        ob_start();
        include CCM_PLUGIN_PATH . 'templates/frontend-display.php';
        return ob_get_clean();
    }

    /**
     * Get customers for frontend display (used by AJAX)
     */
    public function get_frontend_customers($page = 1, $search = '', $per_page = 10)
    {
        $args = array(
            'page' => $page,
            'per_page' => $per_page,
            'search' => $search,
            'status' => 'active' // Only show active customers on frontend
        );

        return $this->database->get_customers($args);
    }
}