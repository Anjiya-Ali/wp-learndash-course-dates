<?php

/**
 * Class for handling LearnDash course dates shortcode.
 */

 class LearnDash_Course_Dates_Shortcode {
    private $setting_option_values;

    public function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'add_learndash_course_dates_above_add_to_cart'), 20);
        add_shortcode('available_course_dates', array($this, 'display_available_course_dates'));
        $this->load_settings();
    }

    private function load_settings() {
        $this->setting_option_values = get_option('learndash-course-date-settings', array());
    }

    public function display_available_course_dates($atts) {
        // Extract attributes with default values
        $atts = shortcode_atts(array(), $atts, 'available_course_dates');
    
        // Get the product ID from the shortcode attribute or current post
        $product_id = isset($atts['product_id']) ? intval($atts['product_id']) : get_the_ID();
    
        if (!$product_id) {
            return '<p>Invalid product ID.</p>';
        }
    
        // Fetch the associated LearnDash course ID for the product
        $related_courses = get_post_meta($product_id, '_related_course');
        $course_id = $related_courses[0][0];
    
        if (!$course_id) {
            return '<p>No associated LearnDash course found for this product.</p>';
        }
    
        // Fetch multiple dates for the specified course
        $multiple_dates = $this->get_multiple_dates_for_course($course_id);

        if(!$multiple_dates){
            return;
        }

        // Fetch available dates for the specified course
        $available_dates = $this->get_available_dates_for_course($course_id);
    
        if (empty($available_dates)) {
            return '<p>No available dates found for this course.</p>';
        }
    
        // Convert dates to DateTime objects and sort them
        $dates_array = array();
        foreach ($available_dates as $date) {
            $date_parts = explode('-', $date);
            $month = isset($date_parts[0]) ? ltrim($date_parts[0], '0') : '';
            $day = isset($date_parts[1]) ? ltrim($date_parts[1], '0') : '';
            $year = isset($date_parts[2]) ? $date_parts[2] : '';
    
            if (!checkdate($month, $day, $year)) {
                continue;
            }
    
            $course_date = DateTime::createFromFormat('m-d-Y', sprintf('%02d-%02d-%04d', $month, $day, $year));
            $dates_array[] = $course_date;
        }
    
        usort($dates_array, function($a, $b) {
            return $a <=> $b;
        });
    
        wp_enqueue_style('learndash-course-dates-style', plugins_url('../assets/css/learndash-course-dates.css', __FILE__));
    
        $html = '<div class="learndash-course-dates-dropdown">';
        $html .= '<label for="course_dates" class="learndash-course-dates-label">Available Course Dates: </label>';
        $html .= '<div class="course_dates_div">';
        $html .= '<select name="course_dates" id="course_dates" class="learndash-course-dates-select">';
        $html .= '<option value="" selected>Schedule a date</option>';
    
        $today = new DateTime();
        foreach ($dates_array as $course_date) {
            if ($course_date < $today) {
                continue;
            }
    
            $day_with_suffix = $this->add_ordinal_suffix((int) $course_date->format('j'));
            $formatted_date = $day_with_suffix . ' ' . $course_date->format('F, Y');
            
            $html .= '<option value="' . esc_attr($course_date->format('m-d-Y')) . '">' . esc_html($formatted_date) . '</option>';
        }
    
        $html .= '</select>';
        $html .= '<i class="fa fa-caret-down"></i>';
        $html .= '</div>';
        $html .= '</div>';
    
        return $html;
    }
    
    private function get_available_dates_for_course($course_id) {
        return learndash_get_course_meta_setting($course_id, 'available_dates');
    }

    private function get_multiple_dates_for_course($course_id) {
        return learndash_get_course_meta_setting($course_id, 'multiple_dates');
    }

    private function add_ordinal_suffix($day) {
        if (!in_array(($day % 100), array(11, 12, 13))) {
            switch ($day % 10) {
                case 1: return $day . 'st';
                case 2: return $day . 'nd';
                case 3: return $day . 'rd';
            }
        }
        return $day . 'th';
    }

    /**
     * Display LearnDash course dates shortcode above the Add to Cart button on the product page.
     *
     * @param WC_Product $product The product object.
    */
    public function add_learndash_course_dates_above_add_to_cart() {
        global $product;
    
        // Ensure $product is an instance of WC_Product
        if (!$product || !($product instanceof WC_Product)) {
            return;
        }
    
        $shortcode = '[available_course_dates product_id="' . $product->get_id() . '"]';
        echo do_shortcode($shortcode);
    }

}
