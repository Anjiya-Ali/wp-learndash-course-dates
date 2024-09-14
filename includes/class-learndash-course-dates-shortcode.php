<?php
/**
 * Class for handling LearnDash course dates shortcode.
 */

class LearnDash_Course_Dates_Shortcode {
    private $setting_option_values;
    private $product_id;

    public function __construct() {
        add_action( 'woocommerce_single_product_summary', array( $this, 'add_learndash_course_dates_above_add_to_cart' ), 20 );
        add_shortcode( 'available_course_dates', array( $this, 'display_available_course_dates' ) );
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_session_course_date_to_cart_item' ), 10, 2 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_course_date_on_cart_and_checkout' ), 10, 2 );
        add_filter( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_course_date_to_order_meta' ), 10, 4 );
        add_filter( 'woocommerce_email_order_items_meta', array( $this, 'display_course_date_in_order_email' ), 10, 3 );
        add_action( 'wp_ajax_store_selected_course_date', array( $this, 'store_selected_course_date_in_session' ) );
        add_action( 'wp_ajax_nopriv_store_selected_course_date', array( $this, 'store_selected_course_date_in_session' ) );
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_hidden_product_id_field' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'unenroll_user_after_purchase' ), 10, 1 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'unenroll_user_after_purchase' ), 99, 1 );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_course_date_to_order' ), 10, 2 );
        add_action( 'init', array( $this, 'schedule_enrollment_cron' ) );
        add_action( 'enroll_users_on_selected_date', array( $this, 'enroll_users_on_selected_date_function' ) );
        add_filter( 'learndash_payment_button_markup', array( $this, 'custom_filter_take_this_course_button' ), 99, 1 );
        add_shortcode( 'available_course_dates_text', array( $this, 'display_course_dates_on_course_page' ) );

        $this->load_settings();
    }

    /**
     * Load settings from the database.
     */
    private function load_settings() {
        $this->setting_option_values = get_option( 'learndash-course-date-settings', array() );
    }

    /**
     * Display available course dates.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML content.
     */
    public function display_available_course_dates( $atts ) {
        // Extract attributes with default values.
        $atts = shortcode_atts( array(), $atts, 'available_course_dates' );
    
        // Get the product ID from the shortcode attribute or current post.
        $product_id = isset( $atts['product_id'] ) ? intval( $atts['product_id'] ) : get_the_ID();
        $this->product_id =  $product_id;
    
        if ( ! $product_id ) {
            return '<p>Invalid product ID.</p>';
        }
    
        // Fetch the associated LearnDash course ID for the product.
        $related_courses = get_post_meta( $product_id, '_related_course' );
        $course_id       = isset( $related_courses[0][0] ) ? $related_courses[0][0] : 0;
    
        if ( ! $course_id ) {
            return '<p>No associated LearnDash course found for this product.</p>';
        }
    
        // Fetch multiple dates for the specified course.
        $multiple_dates = $this->get_multiple_dates_for_course( $course_id );

        if ( ! $multiple_dates ) {
            return;
        }

        $user_query          = learndash_get_users_for_course( $course_id );

        if ( !is_array( $user_query ) ) {
            $query_results       = $user_query->get_results();

            if ( ! empty( $query_results ) ) {
                $enrolled_user_count = count( $query_results );
            }
        }

        $scheduled_dates = array();

        // Get all users
        $args = array(
            'fields' => 'ID', // Get only user IDs to optimize performance
        );
        $users = get_users( $args );

        // Loop through users to find matching meta
        $matching_users = array(); // Array to store matching users

        foreach ( $users as $user_id ) {
            // Get the scheduled course date for the specific course ID
            $user_course_date = get_user_meta( $user_id, 'scheduled_course_date_' . $course_id, true );
            array_push( $scheduled_dates, $user_course_date );                
        }

        // Fetch available dates and seats for the specified course.
        $available_dates = $this->get_available_dates_for_course( $course_id );
        $available_seats = $this->get_available_seats_for_course( $course_id );
    
        if ( empty( $available_dates ) ) {
            return '<p>No available dates found for this course.</p>';
        }
    
        // Convert dates to DateTime objects and sort them.
        $dates_array = array();
        foreach ( $available_dates as $date ) {
            $date_parts = explode( '-', $date );
            $month      = isset( $date_parts[0] ) ? ltrim( $date_parts[0], '0' ) : '';
            $day        = isset( $date_parts[1] ) ? ltrim( $date_parts[1], '0' ) : '';
            $year       = isset( $date_parts[2] ) ? $date_parts[2] : '';

            if ( ! checkdate( $month, $day, $year ) ) {
                continue;
            }

            $course_date    = DateTime::createFromFormat( 'm-d-Y', sprintf( '%02d-%02d-%04d', $month, $day, $year ) );
            $dates_array[]  = $course_date;
        }
    
        // Combine the dates and available seats into a single array.
        $combined_array = array();
        for ( $i = 0; $i < count( $dates_array ); $i++ ) {
            $combined_array[] = array(
                'date'  => $dates_array[$i],
                'seats' => isset( $available_seats[$i] ) ? $available_seats[$i] : 0, // Default to 0 if index doesn't exist
            );
        }

        // Sort the combined array by the date.
        usort( $combined_array, function( $a, $b ) {
            return $a['date'] <=> $b['date'];
        });
    
        $html  = '<div class="learndash-course-dates-dropdown">';
        $html .= '<label for="course_dates" class="learndash-course-dates-label">Available Course Dates: </label>';
        $html .= '<div class="course_dates_div">';
        $html .= '<select name="course_dates" id="course_dates" class="learndash-course-dates-select" required>';
        $html .= '<option value="" selected>Schedule a date</option>';
        
        $today = new DateTime();
        $index = -1; // Initialize an index to track the current iteration.

        // Now loop through the sorted array to generate the HTML.
        foreach ( $combined_array as $item ) {
            $enrolled_user_count = 0;
            $course_date = $item['date'];
            $available_seats_for_date = $item['seats'];

            // Skip dates in the past.
            if ( $course_date < $today ) {
                continue;
            }

            foreach($scheduled_dates as $scheduled_date){
                if(!empty($scheduled_date)){
                    $date_parts = explode( '-', $scheduled_date );
                    $month      = isset( $date_parts[0] ) ? ltrim( $date_parts[0], '0' ) : '';
                    $day        = isset( $date_parts[1] ) ? ltrim( $date_parts[1], '0' ) : '';
                    $year       = isset( $date_parts[2] ) ? $date_parts[2] : '';
    
                    if ( ! checkdate( $month, $day, $year ) ) {
                        continue;
                    }
    
                    $scheduled_date    = DateTime::createFromFormat( 'm-d-Y', sprintf( '%02d-%02d-%04d', $month, $day, $year ) );
                    
                    if ( $scheduled_date == $course_date ) {
                        $enrolled_user_count++;
                    }
                }
            }
        
            // Check if all seats for this date are booked.
            if ( $available_seats_for_date == $enrolled_user_count ) {
                $disabled = true;
            } else {
                $disabled = false;
            }

            // Add the ordinal suffix to the day number and format the date.
            $day_with_suffix = $this->add_ordinal_suffix( (int) $course_date->format( 'j' ) );
            $formatted_date  = $day_with_suffix . ' ' . $course_date->format( 'F, Y' );

            // Generate the option for this date, disabling it if no seats are available.
            $html .= '<option ' . ( $disabled ? 'disabled' : '' ) . ' value="' . esc_attr( $course_date->format('m-d-Y') ) . '">' . esc_html( $formatted_date ) . '</option>';
        }

        $html .= '</select>';
        $html .= '<i class="fa fa-caret-down"></i>';
        $html .= '</div>';
        $html .= '</div>';
    
        return $html;
    }

    /**
     * Get available dates for the course.
     *
     * @param int $course_id LearnDash course ID.
     * @return array Available dates.
     */
    private function get_available_dates_for_course( $course_id ) {
        return learndash_get_course_meta_setting( $course_id, 'available_dates' );
    }

    /**
     * Get multiple dates setting for the course.
     *
     * @param int $course_id LearnDash course ID.
     * @return bool Whether multiple dates are enabled.
     */
    private function get_multiple_dates_for_course( $course_id ) {
        return learndash_get_course_meta_setting( $course_id, 'multiple_dates' );
    }
    
    /**
     * Get available seats for the course.
     *
     * @param int $course_id LearnDash course ID.
     * @return int Available seats.
     */
    private function get_available_seats_for_course( $course_id ) {
        return learndash_get_course_meta_setting( $course_id, 'available_seats' );
    }

    /**
     * Add ordinal suffix to a day number.
     *
     * @param int $day Day number.
     * @return string Day with ordinal suffix.
     */
    private function add_ordinal_suffix( $day ) {
        if ( ! in_array( ( $day % 100 ), array( 11, 12, 13 ), true ) ) {
            switch ( $day % 10 ) {
                case 1:
                    return $day . 'st';
                case 2:
                    return $day . 'nd';
                case 3:
                    return $day . 'rd';
            }
        }
        return $day . 'th';
    }

    /**
     * Display LearnDash course dates shortcode above the Add to Cart button on the product page.
     */
    public function add_learndash_course_dates_above_add_to_cart() {
        global $product;
    
        // Ensure $product is an instance of WC_Product.
        if ( ! $product || ! ( $product instanceof WC_Product ) ) {
            return;
        }
    
        $shortcode = '[available_course_dates product_id="' . $product->get_id() . '"]';
        echo do_shortcode( $shortcode );
    }

    public function add_session_course_date_to_cart_item( $cart_item_data, $product_id ) {
        if ( WC()->session->__isset( 'selected_course_date' ) ) {
            $cart_item_data['course_date'] = WC()->session->get( 'selected_course_date' );
            WC()->session->__unset( 'selected_course_date' ); // Clear session after adding to cart
        }
        return $cart_item_data;
    }

    public function display_course_date_on_cart_and_checkout( $item_data, $cart_item ) {
        if ( isset( $cart_item['course_date'] ) ) {
            $item_data[] = array(
                'name'  => __( 'Selected Course Date', 'your-text-domain' ),
                'value' => sanitize_text_field( $cart_item['course_date'] ),
            );
        }
        return $item_data;
    }

    public function save_course_date_to_order_meta( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['course_date'] ) ) {
            $item->add_meta_data( __( 'Course Date', 'your-text-domain' ), sanitize_text_field( $values['course_date'] ), true );
        }
    }

    public function display_course_date_in_order_email( $item_id, $item, $order ) {
        $course_date = $item->get_meta( 'Course Date' );
        if ( $course_date ) {
            echo '<p><strong>' . __( 'Course Date:', 'your-text-domain' ) . '</strong> ' . esc_html( $course_date ) . '</p>';
        }
    }

    public function store_selected_course_date_in_session() {
        // Verify the selected course date is present in the POST data
        if ( isset( $_POST['course_date'] ) && isset( $_POST['product_id'] ) ) {
            $course_date = sanitize_text_field( $_POST['course_date'] );
            $product_id  = intval( $_POST['product_id'] );
            
            // Save the selected course date in WooCommerce session
            WC()->session->set( 'selected_course_date', $course_date );
    
            // Add the product to the cart
            WC()->cart->add_to_cart( $product_id );
    
            // Return success response
            wp_send_json_success( array( 'message' => 'Product added to cart with selected course date.' ) );
        } else {
            // Send error response if no course date is provided
            wp_send_json_error( array( 'message' => 'No course date or product ID provided.' ) );
        }
    
        wp_die(); // This is required to terminate immediately and return a proper response
    }    

    public function add_hidden_product_id_field() {
        global $product;
    
        // Ensure $product is available
        if ( $product instanceof WC_Product ) {
            echo '<input type="hidden" id="product_id" name="product_id" value="' . esc_attr( $product->get_id() ) . '" />';
        }
    }

    /**
     * Unenroll the user from the course after purchase.
     */
    public function unenroll_user_after_purchase( $order_id ) {
        $order = wc_get_order( $order_id );
    
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $related_courses  =get_post_meta( $product_id, '_related_course' );
            $course_id       = isset( $related_courses[0][0] ) ? $related_courses[0][0] : 0;
            $selected_date = $item->get_meta( 'Course Date' );

            if ( $course_id ) {
                $multiple_dates = $this->get_multiple_dates_for_course( $course_id );

                if( $multiple_dates ){
                    $user_id = $order->get_user_id();
                    // Unenroll the user from the associated course
                    ld_update_course_access( $user_id, $course_id, true );
                    update_user_meta($user_id, 'scheduled_course_date_' . $course_id, $selected_date);
                }
            }
        }
    }    

    /**
     * Save the selected course date as order meta.
     */
    public function save_course_date_to_order( $order, $data ) {
        if ( isset( WC()->session->selected_course_date ) ) {
            $order->update_meta_data( '_selected_course_date', WC()->session->get( 'selected_course_date' ) );
        }
    }

    /**
     * Schedule a cron job to enroll users on the selected date.
     */
    public function schedule_enrollment_cron() {
        if ( ! as_next_scheduled_action( 'enroll_users_on_selected_date' ) ) {
            as_schedule_recurring_action( time(), DAY_IN_SECONDS, 'enroll_users_on_selected_date' );
        }        
    }

    /**
     * Re-enroll users on their selected course date.
     */
    public function enroll_users_on_selected_date_function() {
        $today = date( 'm-d-Y' );

        $orders = wc_get_orders( array( 'status' => 'completed', 'limit' => -1 ) );

        foreach ( $orders as $order ) {            
            foreach ( $order->get_items() as $item ) {
                $selected_date = $item->get_meta( 'Course Date' );

                if ( $selected_date === $today ) {
                    $product_id = $item->get_product_id();
                    $related_courses = get_post_meta( $product_id, '_related_course' );
                    $course_id       = isset( $related_courses[0][0] ) ? $related_courses[0][0] : 0;

                    if ( $course_id ) {
                        $user_id = $order->get_user_id();
                        ld_update_course_access( $user_id, $course_id, false ); // Re-enroll user
                    }
                }
            }
        }
    }

    /**
     * Conditionally filter the 'Take This Course' button
     */
    public function custom_filter_take_this_course_button( $button ) {
        $user_id = get_current_user_id();
        $course_id = get_the_id();

        // Example condition: Check if the user has specific meta
        $blocked_user_meta = get_user_meta( $user_id, 'scheduled_course_date_' . $course_id, true );
    
        // If user meta exists and matches a condition, prevent button display
        if ( !empty($blocked_user_meta) ) {
            // Return an empty string to hide the button
            return '<span class="ld-text">You have already scheduled this course.</span>';
        }

        // Otherwise, return the default button
        return $button;
    }

    /**
     * Display course dates on course page
     */
    public function display_course_dates_on_course_page() {
        $course_id = get_the_ID();
    
        if (empty($course_id)) {
            return '<p>No course ID found.</p>';
        }
    
        // Get available dates for the course (assuming dates are stored as 'Y-m-d' format in an array)
        $available_dates = $this->get_available_dates_for_course($course_id);
        
        // Start building HTML output
        $html = '';

        if( !empty ( $available_dates ) ){
            // Get the current date for comparison
            $current_date = date('m-d-Y');
                
            // Filter out past dates
            $future_dates = array_filter($available_dates, function($date) use ($current_date) {
                return strtotime($date) >= strtotime($current_date);
            });

            // Check if there are future dates available
            if (!empty($future_dates)) {
                // Sort future dates in ascending order (optional)
                sort($future_dates);

                $html .= '<p><strong>Available Course Dates: </strong>';

                foreach ($future_dates as $date) {
                    // Display each available date
                    $html .= '<span>' . esc_html($date) . ', </span>';
                }

                $html .= '</p>';
            } 
        }else {
            // If no future dates are available, show the fallback message
            $html .= '<p><strong>Date: </strong> Next Course Dates Will Be Published Soon! </p>';
        }
    
        return $html;
    }    
    
}
