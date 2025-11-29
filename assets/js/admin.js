(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Phone number validation
        $('#phone').on('input', function() {
            var value = $(this).val();
            // Remove any non-numeric characters
            var numericValue = value.replace(/[^0-9]/g, '');
            $(this).val(numericValue);
        });
        
        // Date of birth validation
        $('#date_of_birth').on('change', function() {
            var dob = new Date($(this).val());
            var today = new Date();
            
            if (dob >= today) {
                alert('Date of birth cannot be in the future.');
                $(this).val('');
            }
        });
        
        // Confirm delete actions
        $('.button-link-delete').on('click', function(e) {
            if (!confirm(ccm_admin.confirm_delete)) {
                e.preventDefault();
            }
        });
    });
    
})(jQuery);