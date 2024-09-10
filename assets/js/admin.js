jQuery(document).ready(function($) {
    // Function to add a new date-seat pair
    function addDateSeatPair() {
        var index = $('.date-seat-pair').length;
        var newPairHtml = '<div class="date-seat-pair" style="display: flex; align-items: center; margin-bottom: 10px;">';
        newPairHtml += '<div class="sfwd_option_div" style="margin-right: 10px;">';
        newPairHtml += '<div class="ld_date_selector" style="display: flex; align-items: center;">';
        newPairHtml += '<span class="screen-reader-text">Month</span>';
        newPairHtml += '<select class="ld_date_mm learndash-section-field learndash-section-field-date-entry learndash-datepicker-field" name="available_dates[' + index + '][mm]" id="dp_date_mm_' + index + '">';
        newPairHtml += '<option value="">MM</option>';
        for (var i = 1; i <= 12; i++) {
            var value = i.toString().padStart(2, '0');
            var text = new Date(0, i - 1).toLocaleString('default', { month: 'short' });
            newPairHtml += '<option value="' + value + '">' + text + '</option>';
        }
        newPairHtml += '</select>';
        newPairHtml += '<span class="screen-reader-text">Day</span>';
        newPairHtml += '<input type="number" placeholder="DD" min="1" max="31" name="available_dates[' + index + '][jj]" style="width: 60px; margin-right: 5px;" value="" size="2" maxlength="2" autocomplete="off" id="dp_date_dd_' + index + '"> ,';
        newPairHtml += '<span class="screen-reader-text">Year</span>';
        newPairHtml += '<input type="number" placeholder="YYYY" min="2024"  name="available_dates[' + index + '][aa]" value="" size="4" maxlength="4" style="width: 80px;" autocomplete="off" id="dp_date_yy_' + index + '">';
        newPairHtml += '</div></div>';
        newPairHtml += '<input type="number" name="available_seats[]" class="small-text" value="" placeholder="Seats" style="width: 60px; margin-left: 16px; height: 35px; margin-bottom: 6px;">';
        newPairHtml += '<button type="button" class="button-link-delete remove-date-seat-pair" style="margin-left: 10px;">Remove</button>';
        newPairHtml += '</div>';

        $('.available-dates-seats-wrap').append(newPairHtml);
    }

    // Add a new date-seat pair when the "Add More Dates" button is clicked
    $(document).on('click', '.add-date-seat-field', function(e) {
        e.preventDefault();
        addDateSeatPair();
    });

    // Remove a date-seat pair when the "Remove" button is clicked
    $(document).on('click', '.remove-date-seat-pair', function(e) {
        e.preventDefault();
        $(this).closest('.date-seat-pair').remove();
    });

    // Initialize datepicker fields if needed
    $(document).on('focus', '.ld_date_selector input', function() {
        // Here you could initialize a datepicker if needed
        // Example: $(this).datepicker();
    });
});
