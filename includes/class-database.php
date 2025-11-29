<?php
/**
 * Database handler class
 */
class CCM_Database
{

    /**
     * Create custom tables
     */
    public function create_tables()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            date_of_birth date NOT NULL,
            gender enum('male','female','other') NOT NULL,
            cr_number varchar(50) NOT NULL,
            address text NOT NULL,
            city varchar(100) NOT NULL,
            country varchar(100) NOT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            UNIQUE KEY cr_number (cr_number)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add version option to track database schema changes
        add_option('ccm_db_version', '1.0');
    }

    /**
     * Insert customer
     */
    public function insert_customer($data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;

        $defaults = array(
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $data = wp_parse_args($data, $defaults);

        // Validate required fields
        $required = array('name', 'email', 'phone', 'date_of_birth', 'gender', 'cr_number', 'address', 'city', 'country');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field %s is required', 'custom-customer-management'), $field));
            }
        }

        // Validate email format
        if (!is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Invalid email address', 'custom-customer-management'));
        }

        // Validate phone (numeric only)
        if (!preg_match('/^[0-9]+$/', $data['phone'])) {
            return new WP_Error('invalid_phone', __('Phone number must contain only numbers', 'custom-customer-management'));
        }

        // Check if email already exists
        if ($this->email_exists($data['email'])) {
            return new WP_Error('email_exists', __('Email address already exists', 'custom-customer-management'));
        }

        // Check if CR number already exists
        if ($this->cr_number_exists($data['cr_number'])) {
            return new WP_Error('cr_number_exists', __('CR number already exists', 'custom-customer-management'));
        }

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            return new WP_Error('db_error', __('Database error occurred', 'custom-customer-management'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update customer
     */
    public function update_customer($id, $data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;

        $data['updated_at'] = current_time('mysql');

        // Validate email format if provided
        if (isset($data['email']) && !is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Invalid email address', 'custom-customer-management'));
        }

        // Validate phone if provided
        if (isset($data['phone']) && !preg_match('/^[0-9]+$/', $data['phone'])) {
            return new WP_Error('invalid_phone', __('Phone number must contain only numbers', 'custom-customer-management'));
        }

        // Check if email already exists (excluding current customer)
        if (isset($data['email']) && $this->email_exists($data['email'], $id)) {
            return new WP_Error('email_exists', __('Email address already exists', 'custom-customer-management'));
        }

        // Check if CR number already exists (excluding current customer)
        if (isset($data['cr_number']) && $this->cr_number_exists($data['cr_number'], $id)) {
            return new WP_Error('cr_number_exists', __('CR number already exists', 'custom-customer-management'));
        }

        $result = $wpdb->update($table_name, $data, array('id' => $id));

        if ($result === false) {
            return new WP_Error('db_error', __('Database error occurred', 'custom-customer-management'));
        }

        return $result;
    }

    /**
     * Delete customer
     */
    public function delete_customer($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;

        return $wpdb->delete($table_name, array('id' => $id));
    }

    /**
     * Get customer by ID
     */
    public function get_customer($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ));
    }

    /**
     * Get all customers with pagination
     */
    public function get_customers($args = array())
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;

        $defaults = array(
            'page' => 1,
            'per_page' => 10,
            'search' => '',
            'status' => '',
            'orderby' => 'id',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = 'WHERE 1=1';
        $params = array();

        // Search functionality
        if (!empty($args['search'])) {
            $where .= " AND (name LIKE %s OR email LIKE %s OR cr_number LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Status filter
        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }

        // Order by
        $orderby = in_array($args['orderby'], array('id', 'name', 'email', 'created_at')) ? $args['orderby'] : 'id';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        $limit = $wpdb->prepare("LIMIT %d, %d", $offset, $args['per_page']);

        // Main query
        $query = "SELECT * FROM $table_name $where ORDER BY $orderby $order $limit";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $customers = $wpdb->get_results($query);

        // Count query for pagination
        $count_query = "SELECT COUNT(*) FROM $table_name $where";
        if (!empty($params)) {
            $count_query = $wpdb->prepare($count_query, $params);
        }

        $total = $wpdb->get_var($count_query);

        return array(
            'customers' => $customers,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        );
    }

    /**
     * Check if email exists
     */
    private function email_exists($email, $exclude_id = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;

        $query = "SELECT COUNT(*) FROM $table_name WHERE email = %s";
        $params = array($email);

        if ($exclude_id > 0) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $count = $wpdb->get_var($wpdb->prepare($query, $params));

        return $count > 0;
    }

    /**
     * Check if CR number exists
     */
    private function cr_number_exists($cr_number, $exclude_id = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . CCM_TABLE_NAME;

        $query = "SELECT COUNT(*) FROM $table_name WHERE cr_number = %s";
        $params = array($cr_number);

        if ($exclude_id > 0) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $count = $wpdb->get_var($wpdb->prepare($query, $params));

        return $count > 0;
    }

    /**
     * Calculate age from date of birth
     */
    public function calculate_age($date_of_birth)
    {
        $dob = new DateTime($date_of_birth);
        $now = new DateTime();
        $age = $now->diff($dob);
        return $age->y;
    }
}