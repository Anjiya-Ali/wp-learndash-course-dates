jQuery(document).ready(function($) {
    // Initialize datepicker
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd'
    });

    // Add new date pair
    $('#add-date-pair').on('click', function(e) {
        e.preventDefault();
        var newDatePair = '<div class="settings-date-pair">' +
            '<label>Date: <input type="text" class="datepicker" name="learndash_course_dates_settings[dates][]" /></label>' +
            '<label>Total Seats: <input type="number" name="learndash_course_dates_settings[seats][]" /></label>' +
            '<button class="remove-date-pair">Remove</button>' +
            '</div>';

        $('#settings-dates-wrapper').append(newDatePair);
        $('.datepicker').datepicker({ dateFormat: 'yy-mm-dd' });
    });

    // Remove date pair
    $(document).on('click', '.remove-date-pair', function(e) {
        e.preventDefault();
        $(this).parent().remove();
    });
});
