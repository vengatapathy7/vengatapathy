<?php
/**
 * AJAX request handler
 */
class CCM_Ajax_Handler
{

    private $frontend;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->frontend = new CCM_Frontend();

        add_action('wp_ajax_ccm_get_customers', array($this, 'handle_get_customers'));
        add_action('wp_ajax_nopriv_ccm_get_customers', array($this, 'handle_get_customers'));
    }

    /**
     * Handle AJAX request for customers
     */
    public function handle_get_customers()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccm_frontend_nonce')) {
            wp_die(__('Security check failed.', 'custom-customer-management'));
        }

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;

        $data = $this->frontend->get_frontend_customers($page, $search, $per_page);

        // Calculate age for each customer
        foreach ($data['customers'] as $customer) {
            $customer->age = $this->calculate_age($customer->date_of_birth);
        }

        wp_send_json_success($data);
    }

    /**
     * Calculate age from date of birth
     */
    private function calculate_age($date_of_birth)
    {
        $dob = new DateTime($date_of_birth);
        $now = new DateTime();
        $age = $now->diff($dob);
        return $age->y;
    }
}