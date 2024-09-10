jQuery(document).ready(function($) {
    // Function to clone date and seat fields
    function cloneDateSeatFields() {
        var dateField = $('.learndash-datepicker-field:last').clone();
        var seatField = $('.small-text:last').clone();
        
        // Clear values for the cloned fields
        dateField.val('');
        seatField.val('');

        // Append cloned fields to the container
        dateField.insertAfter($('.learndash-datepicker-field:last'));
        seatField.insertAfter($('.small-text:last'));
    }

    // On click of the 'Add More' button
    $('.add-date-seat-field').click(function() {
        cloneDateSeatFields();
    });
});