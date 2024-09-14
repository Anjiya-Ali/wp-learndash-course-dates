jQuery(function($) {
    $('form.cart').on('submit', function(e) {
        
        if (!$('#course_dates').length) {
            return;
        }

        var selectedDate = $('#course_dates').val();
        var product_id = $('#product_id').val(); // Retrieve the product ID from the hidden field
        
        if (!selectedDate) {
            e.preventDefault();
            alert('Please select a course date before adding the product to the cart.');
            return;
        }

        $.ajax({
            type: 'POST',
            url: wc_add_to_cart_params.ajax_url,
            data: {
                action: 'store_selected_course_date',
                course_date: selectedDate,
                product_id: product_id // Pass the product ID to the server
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to the dynamically generated cart URL
                    window.location.href = wc_cart_params.cart_url; 
                } else {
                    alert(response.data.message);  // Display error message
                }
            }
        });

        e.preventDefault(); // Prevent form submission
    });
});
