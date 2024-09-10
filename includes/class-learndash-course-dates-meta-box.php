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
			$this->settings_screen_id = 'sfwd-courses';
			$this->settings_metabox_key = 'learndash-course-date-settings';
			$this->settings_section_label = esc_html__( 'Course Dates and Seats Settings', 'learndash' );
			$this->settings_section_description = esc_html__( 'Manage available dates and seat limits for this course.', 'learndash' );

			$this->settings_fields_map = array(
				'multiple_dates'                 => 'multiple_dates',
				'available_dates'                => 'available_dates',
				'available_seats'                => 'available_seats',
                'add_more_dates'                 => 'add_more_dates',
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
				$this->setting_option_values['available_seats'] = '';
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
					'options' => array(
						'on' => '',
					),
					'value'   => $this->setting_option_values['multiple_dates'],
				),
				'available_dates' => array(
					'name'       => 'available_dates',
					'label'      => esc_html__( 'Available Dates', 'learndash' ),
					'type'       => 'date-entry',
					'class'      => 'learndash-datepicker-field',
					'value'      => $this->setting_option_values['available_dates'],
					'help_text'  => esc_html__( 'Select the dates on which the course will be available.', 'learndash' ),
					'multiple'   => true, // Allow multiple date fields
				),
				'available_seats' => array(
					'name'       => 'available_seats',
					'label'      => esc_html__( 'Total Available Seats', 'learndash' ),
					'type'       => 'number',
					'class'      => 'small-text',
					'value'      => $this->setting_option_values['available_seats'],
					'input_label'=> esc_html__( 'seats', 'learndash' ),
					'help_text'  => esc_html__( 'Set the number of enrollments allowed for each date.', 'learndash' ),
					'multiple'   => true, // Allow multiple seat fields
				),
                'add_more_dates' => array(
                    'name'    => 'add_more_dates',
                    'type'    => 'html',
                    'label'   => '',
                    'value'   => '<button type="button" class="button button-secondary add-date-seat-field">'.esc_html__( 'Add More', 'learndash' ).'</button>',
                ),
			);


			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter settings values for metabox before save to database.
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
				if ( ! is_array( $settings_values['available_dates'] ) ) {
					$settings_values['available_dates'] = array();
				}
				if ( ! is_array( $settings_values['available_seats'] ) ) {
					$settings_values['available_seats'] = '';
				}
				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;
		}
	}
}
