<?php
/**
 * Plugin Name: LearnDash Course Dates
 * Description: Adds the functionality to add multiple available dates and seats for LearnDash courses.
 * Version: 1.0
 * Author: WooNinjas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class LearnDash_Course_Dates {

    public function __construct() {
        // Register activation and deactivation hooks
        register_activation_hook( __FILE__, [ $this, 'on_plugin_activation' ] );
        register_deactivation_hook( __FILE__, [ $this, 'on_plugin_deactivation' ] );

        // Initialize plugin functionality
        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
        add_action( 'admin_init', [ $this, 'monitor_learndash_status' ] );
        add_filter( 'learndash_header_data', [ $this, 'modify_learndash_tabs_meta_boxes' ], 10, 3 );
    }

    /**
     * Runs on plugin activation to check if LearnDash is installed.
     */
    public function on_plugin_activation() {
        if ( ! $this->is_learndash_active() ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die(
                esc_html__( 'LearnDash Course Dates requires LearnDash to be installed and active. This plugin has been deactivated.', 'learndash-course-dates' ),
                esc_html__( 'Plugin Dependency Check', 'learndash-course-dates' ),
                [ 'back_link' => true ]
            );
        }
    }

    /**
     * Handle plugin deactivation.
     */
    public function on_plugin_deactivation() {
        // Add deactivation logic if needed
    }

    /**
     * Initialize plugin functionality after ensuring LearnDash is active.
     */
    public function init_plugin() {
        if ( ! $this->is_learndash_active() ) {
            add_action( 'admin_notices', [ $this, 'show_learndash_missing_notice' ] );
            add_action( 'admin_init', [ $this, 'deactivate_plugin' ] );
        } else {
            // Enqueue admin scripts and styles
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

            // Register meta boxes
            add_filter( 'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ), [ $this, 'register_metabox' ] );
        }
    }

    /**
     * Check if LearnDash is active.
     */
    private function is_learndash_active() {
        return class_exists( 'SFWD_LMS' );
    }

    /**
     * Monitor LearnDash status and deactivate the plugin if LearnDash is deactivated.
     */
    public function monitor_learndash_status() {
        if ( ! $this->is_learndash_active() && is_admin() ) {
            add_action( 'admin_notices', [ $this, 'show_learndash_missing_notice' ] );
            $this->deactivate_plugin();
        }
    }

    /**
     * Deactivate the plugin.
     */
    public function deactivate_plugin() {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

    /**
     * Show an admin notice if LearnDash is not installed.
     */
    public function show_learndash_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'LearnDash is required for the "LearnDash Course Dates" plugin to function. This plugin has been deactivated.', 'learndash-course-dates' );
        echo '</p></div>';
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script( 'learndash-course-dates-admin', plugins_url( 'assets/js/admin.js', __FILE__ ), [ 'jquery' ], null, true );
        wp_enqueue_script( 'learndash-datepicker', plugins_url( 'assets/js/date-picker.js', __FILE__ ), [ 'jquery', 'jquery-ui-datepicker' ], null, true );
        wp_enqueue_style( 'jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
        wp_enqueue_style( 'learndash-course-dates-admin-style', plugins_url( 'assets/css/admin.css', __FILE__ ) );
    }

    /**
     * Register the meta box for LearnDash course dates.
     */
    public function register_metabox( $metaboxes ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-learndash-course-dates-meta-box.php';
        if ( ! isset( $metaboxes['LearnDash_Course_Dates_Meta_Box'] ) && class_exists( 'LearnDash_Course_Dates_Meta_Box' ) ) {
            $metaboxes['LearnDash_Course_Dates_Meta_Box'] = LearnDash_Course_Dates_Meta_Box::add_metabox_instance();
        }
        return $metaboxes;
    }

    public function modify_learndash_tabs_meta_boxes($header_data, $menu_tab_key, $admin_tab) {
        // Find the tab you want to modify
        if (isset($header_data['tabs'])) {
            foreach ($header_data['tabs'] as &$tab) {
                // Check if this is the Settings tab
                if ($tab['id'] === 'sfwd-courses-settings') {
                    // Add the learndash-course-date-settings meta box
                    $tab['metaboxes'][] = 'learndash-course-date-settings';
                }
            }
        }
        return $header_data;
    }

}

new LearnDash_Course_Dates();
