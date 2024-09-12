<?php
/**
 * LearnDash Settings Metabox for Course Dates and Seats.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Course_Dates_Meta_Box' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Course Dates and Seats.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Course_Dates_Meta_Box extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->settings_screen_id     = 'sfwd-courses';
			$this->settings_metabox_key   = 'learndash-course-date-settings';
			$this->settings_section_label = esc_html__( 'Course Dates and Seats Settings', 'learndash' );
			$this->settings_section_description = esc_html__( 'Manage available dates and seat limits for this course.', 'learndash' );

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			$this->settings_fields_map = array(
                'multiple_dates'       => 'multiple_dates',
                'available_dates'      => 'available_dates',
                'available_seats'      => 'available_seats',
            );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['multiple_dates'] ) ) {
				$this->setting_option_values['multiple_dates'] = '';
			}
			if ( ! isset( $this->setting_option_values['available_dates'] ) ) {
				$this->setting_option_values['available_dates'] = array();
			}
			if ( ! isset( $this->setting_option_values['available_seats'] ) ) {
				$this->setting_option_values['available_seats'] = array();
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
				'multiple_dates' => array(
					'name'    => 'multiple_dates',
					'label'   => esc_html__( 'Make Course Available on Multiple Dates', 'learndash' ),
					'type'    => 'checkbox-switch',
					'child_section_state' => ( 'on' === $this->setting_option_values['multiple_dates'] ) ? 'open' : 'closed',
					'options' => array(
						'on' => '',
					),
					'value'   => $this->setting_option_values['multiple_dates'],
				),
				'available_dates_seats_pair' => array(
					'name'  => 'available_dates_seats_pair',
					'parent_setting' => 'multiple_dates',
					'type'  => 'html',
					'label' => esc_html__( 'Available Dates and Seats', 'learndash' ),
					'value' => '<div class="available-dates-seats-wrap">' . $this->generate_date_seat_fields() . '</div><button type="button" class="button button-secondary add-date-seat-field">'.esc_html__( 'Add More Dates', 'learndash' ).'</button>',
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Generate paired date and seat fields.
		 */
		public function generate_date_seat_fields() {
			$html = '';
			$available_dates = isset($this->setting_option_values['available_dates']) ? $this->setting_option_values['available_dates'] : array();
			$available_seats = isset($this->setting_option_values['available_seats']) ? $this->setting_option_values['available_seats'] : array();
		
			// Ensure both arrays are of the same length
			$count_dates = count($available_dates);
			$count_seats = count($available_seats);
			
			if ($count_dates > $count_seats) {
				$available_seats = array_pad($available_seats, $count_dates, 0);
			} elseif ($count_seats > $count_dates) {
				$available_dates = array_pad($available_dates, $count_seats, '');
			}
		
			// Generate HTML for each date and seat pair
			foreach ($available_dates as $index => $date) {
				$seats = isset($available_seats[$index]) ? $available_seats[$index] : '';
				// Parse date components
				$date_parts = explode('-', $date);
				$month = isset($date_parts[0]) ? $date_parts[0] : '';
				$day = isset($date_parts[1]) ? $date_parts[1] : '';
				$year = isset($date_parts[2]) ? $date_parts[2] : '';
		
				$html .= '<div class="date-seat-pair" style="display: flex; align-items: center; margin-bottom: 10px;">';
				$html .= '<div class="sfwd_option_div" style="margin-right: 10px;">';
				$html .= '<div class="ld_date_selector" style="display: flex; align-items: center;">';
				$html .= '<span class="screen-reader-text">Month</span>';
				$html .= '<select name="available_dates['.$index.'][mm]" class="ld_date_mm" style="margin-right: 5px;">';
				for ($i = 1; $i <= 12; $i++) {
					$value = str_pad($i, 2, '0', STR_PAD_LEFT);
					$selected = ($value == $month) ? 'selected' : '';
					$html .= '<option value="'.$value.'" '.$selected.'>'.esc_html(date('M', mktime(0, 0, 0, $i, 1))).'</option>';
				}
				$html .= '</select>';
				$html .= '<span class="screen-reader-text">Day</span>';
				$html .= '<input type="number" name="available_dates['.$index.'][jj]" value="'.esc_attr($day).'" min="1" max="31" placeholder="DD" style="width: 60px; margin-right: 5px;" />,';
				$html .= '<span class="screen-reader-text">Year</span>';
				$html .= '<input type="number" name="available_dates['.$index.'][aa]" value="'.esc_attr($year).'" min="1900" max="2100" placeholder="YYYY" style="width: 80px;" />';
				$html .= '</div></div>';
				$html .= '<input type="number" name="available_seats[]" value="'.esc_attr($seats).'" placeholder="'.esc_attr__( 'Seatse', 'learndash' ).'" style="width: 60px; margin-left: 16px; height: 35px; margin-bottom: 6px;" />';
				$html .= '<button type="button" class="button-link-delete remove-date-seat-pair" style="margin-left: 10px;">'.esc_html__( 'Remove', 'learndash' ).'</button>';
				$html .= '</div>';
			}
		
			// Default one empty field if none exist
			if (empty($available_dates)) {
				$html .= '<div class="date-seat-pair" style="display: flex; align-items: center; margin-bottom: 10px;">';
				$html .= '<div class="sfwd_option_div" style="margin-right: 10px;">';
				$html .= '<div class="ld_date_selector" style="display: flex; align-items: center;">';
				$html .= '<span class="screen-reader-text">Month</span>';
				$html .= '<select name="available_dates[0][mm]" class="ld_date_mm" style="margin-right: 5px;">';
				for ($i = 1; $i <= 12; $i++) {
					$value = str_pad($i, 2, '0', STR_PAD_LEFT);
					$html .= '<option value="'.$value.'">'.esc_html(date('M', mktime(0, 0, 0, $i, 1))).'</option>';
				}
				$html .= '</select>';
				$html .= '<span class="screen-reader-text">Day</span>';
				$html .= '<input type="number" name="available_dates[0][jj]" placeholder="DD" min="1" max="31" style="width: 60px; margin-right: 5px;" />,';
				$html .= '<span class="screen-reader-text">Year</span>';
				$html .= '<input type="number" name="available_dates[0][aa]" placeholder="YYYY" min="2024" style="width: 80px;" />';
				$html .= '</div></div>';
				$html .= '<input type="number" name="available_seats[]" placeholder="'.esc_attr__( 'Seats', 'learndash' ).'" style="width: 60px; margin-left: 16px; height: 35px; margin-bottom: 6px;" />';
				$html .= '</div>';
			}
		
			return $html;
		}

		/**
		 * Filter settings values for metabox before saving to the database.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 *
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {
				if ( ! isset( $settings_values['multiple_dates'] ) ) {
					$settings_values['multiple_dates'] = '';
				}
		
				// Process available dates and seats
				$available_dates = isset( $_POST['available_dates'] ) ? $_POST['available_dates'] : array();
				$available_seats = isset( $_POST['available_seats'] ) ? $_POST['available_seats'] : array();
		
				$filtered_dates = array();
				$filtered_seats = array();
		
				foreach ( $available_dates as $index => $date_entry ) {
					$month = isset( $date_entry['mm'] ) ? ltrim( $date_entry['mm'], '0' ) : '';
					$day = isset( $date_entry['jj'] ) ? ltrim( $date_entry['jj'], '0' ) : '';
					$year = isset( $date_entry['aa'] ) ? $date_entry['aa'] : '';
		
					$seats = isset( $available_seats[$index] ) ? intval( $available_seats[$index] ) : 0;
		
					// Only add to filtered arrays if all fields are present
					if ( $month && $day && $year && $seats ) {
						$filtered_dates[] = $month . '-' . $day . '-' . $year;
						$filtered_seats[] = $seats;
					}
				}
		
				$settings_values['available_dates'] = $filtered_dates;
				$settings_values['available_seats'] = $filtered_seats;
		
				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}
		
			return $settings_values;
		}
		
	}
}
