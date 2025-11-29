<?php
/**
 * Admin interface handler
 */
class CCM_Admin {
    
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new CCM_Database();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_ccm_add_customer', array($this, 'handle_add_customer'));
        add_action('admin_post_ccm_edit_customer', array($this, 'handle_edit_customer'));
        add_action('admin_post_ccm_delete_customer', array($this, 'handle_delete_customer'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Customer Management', 'custom-customer-management'),
            __('Customers', 'custom-customer-management'),
            'manage_options',
            'customer-management',
            array($this, 'display_customers_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'customer-management',
            __('All Customers', 'custom-customer-management'),
            __('All Customers', 'custom-customer-management'),
            'manage_options',
            'customer-management',
            array($this, 'display_customers_page')
        );
        
        add_submenu_page(
            'customer-management',
            __('Add New Customer', 'custom-customer-management'),
            __('Add New', 'custom-customer-management'),
            'manage_options',
            'customer-management-add',
            array($this, 'display_add_customer_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'customer-management') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ccm-admin-css',
            CCM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CCM_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'ccm-admin-js',
            CCM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CCM_PLUGIN_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('ccm-admin-js', 'ccm_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ccm_admin_nonce'),
            'confirm_delete' => __('Are you sure you want to delete this customer?', 'custom-customer-management')
        ));
    }
    
    /**
     * Display customers list page
     */
    public function display_customers_page() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $args = array(
            'page' => $current_page,
            'per_page' => 10,
            'search' => $search,
            'status' => $status
        );
        
        $data = $this->database->get_customers($args);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Customers', 'custom-customer-management'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=customer-management-add'); ?>" class="page-title-action">
                <?php _e('Add New', 'custom-customer-management'); ?>
            </a>
            
            <?php if (!empty($search)): ?>
                <span class="subtitle">
                    <?php printf(__('Search results for "%s"', 'custom-customer-management'), esc_html($search)); ?>
                </span>
            <?php endif; ?>
            
            <hr class="wp-header-end">
            
            <!-- Search Form -->
            <div class="ccm-search-box">
                <form method="get">
                    <input type="hidden" name="page" value="customer-management">
                    <?php wp_nonce_field('ccm_search', 'ccm_search_nonce'); ?>
                    <div class="search-box">
                        <label class="screen-reader-text" for="customer-search-input"><?php _e('Search Customers', 'custom-customer-management'); ?></label>
                        <input type="search" id="customer-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by name, email, or CR number...', 'custom-customer-management'); ?>">
                        <input type="submit" id="search-submit" class="button" value="<?php _e('Search Customers', 'custom-customer-management'); ?>">
                    </div>
                </form>
            </div>
            
            <!-- Customers Table -->
            <div class="ccm-customers-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'custom-customer-management'); ?></th>
                            <th><?php _e('Name', 'custom-customer-management'); ?></th>
                            <th><?php _e('Email', 'custom-customer-management'); ?></th>
                            <th><?php _e('Phone', 'custom-customer-management'); ?></th>
                            <th><?php _e('Age', 'custom-customer-management'); ?></th>
                            <th><?php _e('CR Number', 'custom-customer-management'); ?></th>
                            <th><?php _e('City', 'custom-customer-management'); ?></th>
                            <th><?php _e('Status', 'custom-customer-management'); ?></th>
                            <th><?php _e('Actions', 'custom-customer-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['customers'])): ?>
                            <tr>
                                <td colspan="9" class="no-items"><?php _e('No customers found.', 'custom-customer-management'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['customers'] as $customer): ?>
                                <tr>
                                    <td><?php echo esc_html($customer->id); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($customer->name); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($customer->email); ?></td>
                                    <td><?php echo esc_html($customer->phone); ?></td>
                                    <td><?php echo esc_html($this->database->calculate_age($customer->date_of_birth)); ?></td>
                                    <td><?php echo esc_html($customer->cr_number); ?></td>
                                    <td><?php echo esc_html($customer->city); ?></td>
                                    <td>
                                        <span class="ccm-status ccm-status-<?php echo esc_attr($customer->status); ?>">
                                            <?php echo esc_html(ucfirst($customer->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=customer-management-add&id=' . $customer->id); ?>" class="button button-small">
                                            <?php _e('Edit', 'custom-customer-management'); ?>
                                        </a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=ccm_delete_customer&id=' . $customer->id), 'ccm_delete_customer_' . $customer->id); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('<?php _e('Are you sure you want to delete this customer?', 'custom-customer-management'); ?>');">
                                            <?php _e('Delete', 'custom-customer-management'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($data['pages'] > 1): ?>
                    <div class="ccm-pagination tablenav">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php printf(_n('%s item', '%s items', $data['total'], 'custom-customer-management'), $data['total']); ?></span>
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $data['pages'],
                                'current' => $current_page
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display add/edit customer page
     */
    public function display_add_customer_page() {
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $customer = $customer_id ? $this->database->get_customer($customer_id) : null;
        $is_edit = !empty($customer);
        
        if ($is_edit && !$customer) {
            wp_die(__('Customer not found.', 'custom-customer-management'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __('Edit Customer', 'custom-customer-management') : __('Add New Customer', 'custom-customer-management'); ?></h1>
            
            <div class="ccm-customer-form">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="action" value="ccm_edit_customer">
                        <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="ccm_add_customer">
                    <?php endif; ?>
                    
                    <?php wp_nonce_field($is_edit ? 'ccm_edit_customer_' . $customer_id : 'ccm_add_customer'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name"><?php _e('Name', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="name" id="name" class="regular-text" 
                                       value="<?php echo $is_edit ? esc_attr($customer->name) : ''; ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="email"><?php _e('Email', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <input type="email" name="email" id="email" class="regular-text" 
                                       value="<?php echo $is_edit ? esc_attr($customer->email) : ''; ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="phone"><?php _e('Phone Number', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <input type="tel" name="phone" id="phone" class="regular-text" 
                                       value="<?php echo $is_edit ? esc_attr($customer->phone) : ''; ?>" 
                                       pattern="[0-9]+" title="<?php _e('Only numbers are allowed', 'custom-customer-management'); ?>" required>
                                <p class="description"><?php _e('Numeric only, no spaces or special characters', 'custom-customer-management'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="date_of_birth"><?php _e('Date of Birth', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="regular-text" 
                                       value="<?php echo $is_edit ? esc_attr($customer->date_of_birth) : ''; ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="gender"><?php _e('Gender', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <select name="gender" id="gender" required>
                                    <option value=""><?php _e('Select Gender', 'custom-customer-management'); ?></option>
                                    <option value="male" <?php echo $is_edit && $customer->gender === 'male' ? 'selected' : ''; ?>>
                                        <?php _e('Male', 'custom-customer-management'); ?>
                                    </option>
                                    <option value="female" <?php echo $is_edit && $customer->gender === 'female' ? 'selected' : ''; ?>>
                                        <?php _e('Female', 'custom-customer-management'); ?>
                                    </option>
                                    <option value="other" <?php echo $is_edit && $customer->gender === 'other' ? 'selected' : ''; ?>>
                                        <?php _e('Other', 'custom-customer-management'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="cr_number"><?php _e('CR Number', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="cr_number" id="cr_number" class="regular-text" 
                                       value="<?php echo $is_edit ? esc_attr($customer->cr_number) : ''; ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="address"><?php _e('Address', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <textarea name="address" id="address" class="large-text" rows="3" required><?php echo $is_edit ? esc_textarea($customer->address) : ''; ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="city"><?php _e('City', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="city" id="city" class="regular-text" 
                                       value="<?php echo $is_edit ? esc_attr($customer->city) : ''; ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="country"><?php _e('Country', 'custom-customer-management'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="country" id="country" class="regular-text" 
                                       value="<?php echo $is_edit ? esc_attr($customer->country) : ''; ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status"><?php _e('Status', 'custom-customer-management'); ?></label>
                            </th>
                            <td>
                                <select name="status" id="status">
                                    <option value="active" <?php echo $is_edit && $customer->status === 'active' ? 'selected' : ''; ?>>
                                        <?php _e('Active', 'custom-customer-management'); ?>
                                    </option>
                                    <option value="inactive" <?php echo $is_edit && $customer->status === 'inactive' ? 'selected' : ''; ?>>
                                        <?php _e('Inactive', 'custom-customer-management'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button($is_edit ? __('Update Customer', 'custom-customer-management') : __('Add Customer', 'custom-customer-management')); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle add customer form submission
     */
    public function handle_add_customer() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ccm_add_customer')) {
            wp_die(__('Security check failed.', 'custom-customer-management'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'custom-customer-management'));
        }
        
        // Sanitize and validate data
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'gender' => sanitize_text_field($_POST['gender']),
            'cr_number' => sanitize_text_field($_POST['cr_number']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'country' => sanitize_text_field($_POST['country']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        // Insert customer
        $result = $this->database->insert_customer($data);
        
        if (is_wp_error($result)) {
            $redirect_url = add_query_arg('message', 'error&error=' . urlencode($result->get_error_message()), admin_url('admin.php?page=customer-management-add'));
        } else {
            // Create WordPress user if email doesn't exist
            $this->create_wordpress_user($data['email'], $data['phone'], $data['name']);
            
            $redirect_url = add_query_arg('message', 'success', admin_url('admin.php?page=customer-management'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Handle edit customer form submission
     */
    public function handle_edit_customer() {
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ccm_edit_customer_' . $customer_id)) {
            wp_die(__('Security check failed.', 'custom-customer-management'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'custom-customer-management'));
        }
        
        // Sanitize and validate data
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'gender' => sanitize_text_field($_POST['gender']),
            'cr_number' => sanitize_text_field($_POST['cr_number']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'country' => sanitize_text_field($_POST['country']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        // Update customer
        $result = $this->database->update_customer($customer_id, $data);
        
        if (is_wp_error($result)) {
            $redirect_url = add_query_arg(
                array(
                    'message' => 'error',
                    'error' => urlencode($result->get_error_message()),
                    'id' => $customer_id
                ), 
                admin_url('admin.php?page=customer-management-add')
            );
        } else {
            $redirect_url = add_query_arg('message', 'updated', admin_url('admin.php?page=customer-management'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Handle delete customer
     */
    public function handle_delete_customer() {
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ccm_delete_customer_' . $customer_id)) {
            wp_die(__('Security check failed.', 'custom-customer-management'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'custom-customer-management'));
        }
        
        // Delete customer
        $result = $this->database->delete_customer($customer_id);
        
        if ($result) {
            $redirect_url = add_query_arg('message', 'deleted', admin_url('admin.php?page=customer-management'));
        } else {
            $redirect_url = add_query_arg('message', 'error', admin_url('admin.php?page=customer-management'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Create WordPress user when adding customer
     */
    private function create_wordpress_user($email, $phone, $name) {
        // Check if user already exists
        if (email_exists($email)) {
            return false;
        }
        
        // Create username from email
        $username = sanitize_user(current(explode('@', $email)), true);
        
        // Ensure username is unique
        $counter = 1;
        $original_username = $username;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Create user
        $user_id = wp_create_user($username, $phone, $email);
        
        if (!is_wp_error($user_id)) {
            // Set user role to contributor
            $user = new WP_User($user_id);
            $user->set_role('contributor');
            
            // Update display name
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name
            ));
            
            return $user_id;
        }
        
        return false;
    }
}