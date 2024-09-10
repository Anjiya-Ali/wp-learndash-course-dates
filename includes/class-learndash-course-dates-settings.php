<?php
class LearnDash_Course_Dates_Settings {

    public static function render() {
        ?>
        <div class="wrap">
            <h1>LearnDash Course Dates Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'learndash_course_dates_settings_group' );
                do_settings_sections( 'learndash-course-dates-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function init() {
        register_setting( 'learndash_course_dates_settings_group', 'learndash_course_dates_settings', [ 'sanitize_callback' => [ self::class, 'sanitize_settings' ] ] );

        add_settings_section(
            'learndash_course_dates_section',
            'Course Dates Settings',
            null,
            'learndash-course-dates-settings'
        );

        add_settings_field(
            'course_dates',
            'Available Dates and Seats',
            [ self::class, 'render_date_fields' ],
            'learndash-course-dates-settings',
            'learndash_course_dates_section'
        );
    }

    public static function render_date_fields() {
        $options = get_option( 'learndash_course_dates_settings' );
        ?>
        <div id="settings-dates-wrapper">
            <?php if ( ! empty( $options['dates'] ) ) : ?>
                <?php foreach ( $options['dates'] as $date_pair ) : ?>
                    <div class="settings-date-pair">
                        <label>Date: <input type="text" class="datepicker" name="learndash_course_dates_settings[dates][]" value="<?php echo esc_attr( $date_pair['date'] ); ?>" /></label>
                        <label>Total Seats: <input type="number" name="learndash_course_dates_settings[seats][]" value="<?php echo esc_attr( $date_pair['seats'] ); ?>" /></label>
                        <button class="remove-date-pair">Remove</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button id="add-date-pair">Add More Dates</button>
        <?php
    }

    public static function sanitize_settings( $input ) {
        $sanitized = [];
        if ( isset( $input['dates'] ) && is_array( $input['dates'] ) ) {
            foreach ( $input['dates'] as $key => $date ) {
                $sanitized['dates'][] = [
                    'date'  => sanitize_text_field( $date ),
                    'seats' => intval( $input['seats'][ $key ] ),
                ];
            }
        }
        return $sanitized;
    }
}

add_action( 'admin_init', [ 'LearnDash_Course_Dates_Settings', 'init' ] );
