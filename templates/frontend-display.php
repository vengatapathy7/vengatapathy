<?php
/**
 * Frontend display template for customers
 */
if (!defined('ABSPATH')) {
    exit;
}

$per_page = isset($atts['per_page']) ? intval($atts['per_page']) : 10;
?>
<div class="ccm-frontend-container">
    <div class="ccm-search-section">
        <input type="text" id="ccm-search-input"
            placeholder="<?php esc_attr_e('Search customers...', 'custom-customer-management'); ?>"
            class="ccm-search-field">
        <button type="button" id="ccm-search-btn" class="ccm-search-button">
            <?php _e('Search', 'custom-customer-management'); ?>
        </button>
        <button type="button" id="ccm-clear-search" class="ccm-clear-button" style="display: none;">
            <?php _e('Clear', 'custom-customer-management'); ?>
        </button>
    </div>

    <div class="ccm-customers-grid" id="ccm-customers-grid">
        <!-- Customers will be loaded via AJAX -->
        <div class="ccm-loading"><?php _e('Loading customers...', 'custom-customer-management'); ?></div>
    </div>

    <div class="ccm-pagination" id="ccm-pagination">
        <!-- Pagination will be loaded via AJAX -->
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var currentPage = 1;
        var currentSearch = '';
        var perPage = <?php echo $per_page; ?>;

        // Load customers
        function loadCustomers(page, search) {
            $('#ccm-customers-grid').html('<div class="ccm-loading"><?php _e('Loading customers...', 'custom-customer-management'); ?></div>');

            $.ajax({
                url: ccm_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'ccm_get_customers',
                    nonce: ccm_frontend.nonce,
                    page: page,
                    search: search,
                    per_page: perPage
                },
                success: function (response) {
                    if (response.success) {
                        displayCustomers(response.data);
                        currentPage = page;
                        currentSearch = search;

                        // Show/hide clear search button
                        if (search) {
                            $('#ccm-clear-search').show();
                        } else {
                            $('#ccm-clear-search').hide();
                        }
                    } else {
                        $('#ccm-customers-grid').html('<div class="ccm-error"><?php _e('Error loading customers.', 'custom-customer-management'); ?></div>');
                    }
                },
                error: function () {
                    $('#ccm-customers-grid').html('<div class="ccm-error"><?php _e('Error loading customers.', 'custom-customer-management'); ?></div>');
                }
            });
        }

        // Display customers in grid
        function displayCustomers(data) {
            var grid = $('#ccm-customers-grid');
            var pagination = $('#ccm-pagination');

            if (data.customers.length === 0) {
                grid.html('<div class="ccm-no-customers"><?php _e('No customers found.', 'custom-customer-management'); ?></div>');
                pagination.html('');
                return;
            }

            var html = '<div class="ccm-customers-row ccm-customers-header">' +
                '<div class="ccm-customer-name"><?php _e('Name', 'custom-customer-management'); ?></div>' +
                '<div class="ccm-customer-email"><?php _e('Email', 'custom-customer-management'); ?></div>' +
                '<div class="ccm-customer-phone"><?php _e('Phone', 'custom-customer-management'); ?></div>' +
                '<div class="ccm-customer-age"><?php _e('Age', 'custom-customer-management'); ?></div>' +
                '<div class="ccm-customer-city"><?php _e('City', 'custom-customer-management'); ?></div>' +
                '</div>';

            $.each(data.customers, function (index, customer) {
                html += '<div class="ccm-customers-row">' +
                    '<div class="ccm-customer-name">' + customer.name + '</div>' +
                    '<div class="ccm-customer-email">' + customer.email + '</div>' +
                    '<div class="ccm-customer-phone">' + customer.phone + '</div>' +
                    '<div class="ccm-customer-age">' + customer.age + '</div>' +
                    '<div class="ccm-customer-city">' + customer.city + '</div>' +
                    '</div>';
            });

            grid.html(html);

            // Generate pagination
            if (data.pages > 1) {
                var paginationHtml = '<div class="ccm-pagination-links">';

                if (currentPage > 1) {
                    paginationHtml += '<button type="button" class="ccm-page-link ccm-prev-page" data-page="' + (currentPage - 1) + '"><?php _e('Previous', 'custom-customer-management'); ?></button>';
                }

                paginationHtml += '<span class="ccm-page-info">' +
                    '<?php _e('Page', 'custom-customer-management'); ?> ' + currentPage + ' <?php _e('of', 'custom-customer-management'); ?> ' + data.pages +
                    '</span>';

                if (currentPage < data.pages) {
                    paginationHtml += '<button type="button" class="ccm-page-link ccm-next-page" data-page="' + (currentPage + 1) + '"><?php _e('Next', 'custom-customer-management'); ?></button>';
                }

                paginationHtml += '</div>';
                pagination.html(paginationHtml);
            } else {
                pagination.html('');
            }
        }

        // Event handlers
        $('#ccm-search-btn').on('click', function () {
            var search = $('#ccm-search-input').val();
            loadCustomers(1, search);
        });

        $('#ccm-clear-search').on('click', function () {
            $('#ccm-search-input').val('');
            loadCustomers(1, '');
        });

        $('#ccm-search-input').on('keypress', function (e) {
            if (e.which === 13) {
                var search = $(this).val();
                loadCustomers(1, search);
            }
        });

        $(document).on('click', '.ccm-page-link', function () {
            var page = $(this).data('page');
            loadCustomers(page, currentSearch);
        });

        // Initial load
        loadCustomers(1, '');
    });
</script>