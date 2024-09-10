<?php
/**
 * Class for handling LearnDash course dates shortcode.
 */

class LearnDash_Course_Dates_Shortcode {
    // Class properties to store settings or other necessary data
    private $setting_option_values;

    /**
     * Constructor to initialize the class and register the shortcode.
     */
    public function __construct() {
        // Register the shortcode with WordPress
        add_shortcode('available_course_dates', array($this, 'display_available_course_dates'));

        // Load settings or initialize any other required properties
        $this->load_settings();
    }

    /**
     * Load settings or any other required data for the shortcode.
     */
    private function load_settings() {
        // Example: Load settings from the database or other sources
        // This is where you should load your settings to populate $this->setting_option_values
        $this->setting_option_values = get_option('learndash-course-date-settings', array());
    }

    /**
     * Display available course dates as a dropdown.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML for the dropdown.
     */
    public function display_available_course_dates($atts) {
        // Extract attributes with default values
        $atts = shortcode_atts(array(
            // No default value for course_id, it will be fetched dynamically
        ), $atts, 'available_course_dates');

        // Get the current post ID (course ID on course pages)
        $course_id = get_the_ID();

        // Check if the course ID is valid
        if (!$course_id) {
            return '<p>Invalid course ID.</p>';
        }

        // Fetch available dates for the specified course
        $available_dates = $this->get_available_dates_for_course($course_id);

        // Check if there are no dates available
        if (empty($available_dates)) {
            return '<p>No available dates found for this course.</p>';
        }

        // Start building the HTML for the dropdown
        $html = '<select name="course_dates" id="course_dates">';
        $html .= '<option value="">Select a date</option>';

        // Loop through available dates and add them to the dropdown
        foreach ($available_dates as $date) {
            // Parse date components
            $date_parts = explode('-', $date);
            $month = isset($date_parts[0]) ? ltrim($date_parts[0], '0') : '';
            $day = isset($date_parts[1]) ? ltrim($date_parts[1], '0') : '';
            $year = isset($date_parts[2]) ? $date_parts[2] : '';

            // Format the date for display
            $formatted_date = sprintf('%02d-%02d-%04d', $month, $day, $year);
            $html .= '<option value="' . esc_attr($formatted_date) . '">' . esc_html($formatted_date) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Get available dates for a specific course.
     *
     * @param int $course_id The ID of the course.
     * @return array List of available dates in 'mm-dd-yyyy' format.
     */
    private function get_available_dates_for_course($course_id) {
        return learndash_get_course_meta_setting($course_id, 'available_dates');
    }
}
