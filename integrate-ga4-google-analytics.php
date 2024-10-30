<?php
/*
Plugin Name: Integrate GA4 Google Analytics
Plugin URI: http://middleearmedia.com
Description: A simple, lightweight plugin to easily integrate Google Analytics GA4 tracking into your WordPress site.
Author: Obadiah Metivier
Author URI: http://middleearmedia.com/
Version: 1.3.2
 */

// Abort if this file is called directly
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Register the plugin settings.
function iga_register_settings() {
    add_settings_section( 'iga_main', __( 'Connect to Google Analytics', 'integrate-ga-analytics' ), 'iga_render_main_section', 'integrate-ga-analytics' );
    add_settings_field( 'iga_gtag_id', __( 'GA4 Measurement ID:', 'integrate-ga-analytics' ), 'iga_render_gtag_id_field', 'integrate-ga-analytics', 'iga_main' );  
    add_settings_field( 'iga_disable_for_roles', __( 'Disable Tracking for Roles:', 'integrate-ga-analytics' ), 'iga_render_disable_for_roles_field', 'integrate-ga-analytics', 'iga_main' );

    register_setting( 'iga_settings', 'iga_gtag_id', array('sanitize_callback' => 'iga_sanitize_gtag_id', 'validate_callback' => 'iga_validate_gtag_id') );
    register_setting( 'iga_settings', 'iga_disable_for_roles', array('sanitize_callback' => 'iga_sanitize_roles', 'validate_callback' => 'iga_validate_roles') );
}
add_action( 'admin_init', 'iga_register_settings' );

// Enforce capability checks and nonces
function iga_enforce_capability_checks_and_nonces() {
  if ( !current_user_can( 'manage_options' ) ) {
    return;
  }
  if ( !isset( $_POST['iga_nonce']) || !wp_verify_nonce( $_POST['iga_nonce'], 'iga_settings' ) ) {
    return;
  }
  if ( !isset( $_POST['option_page'] ) || $_POST['option_page'] !== 'iga_settings' ) {
    return;
  }
  if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'update' ) {
    return;
  }
  if ( !isset( $_POST['iga_gtag_id'] ) ) {
    return;
  }
}
add_action( 'admin_init', 'iga_enforce_capability_checks_and_nonces' );

// Add a settings page to the WordPress admin menu
function iga_add_settings_page() {
    add_options_page( 
        __( 'Integrate GA4 Google Analytics Settings', 'integrate-ga-analytics' ),
        __( 'Integrate GA4 Google Analytics', 'integrate-ga-analytics' ),
        'manage_options',
        'integrate-ga-analytics',
        'iga_render_settings_page'
    );
}
add_action( 'admin_menu', 'iga_add_settings_page' );

// Add a settings link to the plugins page
function iga_add_settings_link( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=integrate-ga-analytics' ) ) . '">' . __( 'Settings', 'integrate-ga-analytics' ) . '</a>';
    array_push( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'iga_add_settings_link' );

// Render the settings page
function iga_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Integrate GA4 Google Analytics Settings', 'integrate-ga-analytics' ); ?></h1>
        <h4><?php _e( 'A simple plugin to integrate Google Analytics GA4 tracking into your WordPress site.', 'integrate-ga-analytics' ); ?></h4>
        <form method="post" action="options.php">
            <?php wp_nonce_field( 'iga_settings', 'iga_nonce' ); ?>
            <?php settings_fields( 'iga_settings' ); ?>
            <?php do_settings_sections( 'integrate-ga-analytics' ); ?>
            <?php submit_button( __( 'Save Changes', 'integrate-ga-analytics' ) ); ?>
        </form>
        <hr>
        <h3><?php _e( 'How to find your Google Analytics GA4 Measurement ID', 'integrate-ga-analytics' ); ?></h3>
        <p><?php _e( 'Your Google Analytics GA4 Measurement ID can be found by logging into your Google account.', 'integrate-ga-analytics' ); ?></p>
        <ol>
             <li><?php _e( 'Go to Admin Panel in Google Analytics 4.', 'integrate-ga-analytics' ); ?></li>
             <li><?php _e( 'Select the property that you want to get the Measurement ID for.', 'integrate-ga-analytics' ); ?></li>
             <li><?php _e( 'Click on "Data Stream", then click on the Data Stream name.', 'integrate-ga-analytics' ); ?></li>
             <li><?php _e( 'On the next screen, in the top right corner, you will find the Measurement ID that starts with G-', 'integrate-ga-analytics' ); ?></li>
        </ol>
    </div>
    <?php
}

// Render the main settings section
function iga_render_main_section() {
    echo '
    <p>' . __( 'Enter your Google Analytics GA4 Measurement ID below:', 'integrate-ga-analytics' ) . '</p>
    <p><strong>' . __( 'NOTE:', 'integrate-ga-analytics' ) . '</strong> <em>' . __( 'Do not enter the entire tracking code script. Only the ID, which looks like this: G-XXXXXXXXXX', 'integrate-ga-analytics' ) . '</em></p>
    ';
}

// Sanitize the measurement ID field
function iga_sanitize_gtag_id( $input ) {
    return sanitize_text_field( $input );
}

// Validate the measurement ID field
function iga_validate_gtag_id( $measurement_id, $option, $args ) {
    $measurement_id = trim( $measurement_id );
    if ( empty( $measurement_id ) ) {
        add_settings_error( 'iga_gtag_id', 'empty_measurement_id', __( 'Measurement ID is required.', 'integrate-ga-analytics' ), 'error' );
        return '';
    } elseif ( !preg_match('/^G-[a-zA-Z0-9]+$/', $measurement_id ) ) {
        add_settings_error( 'iga_gtag_id', 'invalid_measurement_id', __( 'Measurement ID must begin with G- followed by a string of letters and numbers.', 'integrate-ga-analytics' ), 'error' );
        return '';
    }
    return $measurement_id;
}

// Sanitize the roles field
function iga_sanitize_roles( $input ) {
    if ( is_array( $input ) ) {
        return array_map( 'sanitize_text_field', $input );
    }
    return array();
}

// Validate the roles field
function iga_validate_roles( $input, $option, $args ) {
    if ( !is_array( $input ) ) {
        return array();
    }
    
    $all_roles = wp_roles()->roles;
    $valid_roles = array_keys( $all_roles );

    $validated_roles = array();
    foreach ( $input as $role ) {
        if ( in_array( $role, $valid_roles ) ) {
            $validated_roles[] = $role;
        }
    }
    return $validated_roles;
}

// Render the measurement ID field
function iga_render_gtag_id_field() {
    $measurement_id = get_option( 'iga_gtag_id' );
    echo '<input type="text" name="iga_gtag_id" value="' . esc_attr( $measurement_id ) . '" />';
    get_settings_errors( 'iga_gtag_id' );
}

// Render the disable for roles field
function iga_render_disable_for_roles_field() {
    $roles = get_option( 'iga_disable_for_roles', array() );
    $all_roles = wp_roles()->roles;
    foreach ( $all_roles as $role => $details ) {
        echo '<input type="checkbox" name="iga_disable_for_roles[]" value="' . esc_attr( $role ) . '" ' . checked( in_array( $role, $roles ), true, false ) . ' />';
        echo '<label for="iga_disable_for_roles">' . esc_html( $details['name'] ) . '</label><br>';
    }
}

// Insert Google Analytics GA4 script into the footer with user's Measurement ID, if it's not empty and is formatted correctly.
function iga_insert_tracking_script() {
    if ( !is_admin() ) {
        $measurement_id = get_option( 'iga_gtag_id' );
        $disable_for_roles = get_option( 'iga_disable_for_roles', array() );

        if ( !empty( $measurement_id ) && preg_match( '/^G-[a-zA-Z0-9]+$/', $measurement_id ) ) {
            if ( iga_user_has_role( $disable_for_roles ) ) {
                return;
            }

            wp_enqueue_script( 'ga4-tracking', 'https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $measurement_id ), array(), null, true );
            $inline_script = 'window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag(\'js\', new Date()); gtag(\'config\', \'' . esc_attr( $measurement_id ) . '\');';
            wp_add_inline_script( 'ga4-tracking', $inline_script );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'iga_insert_tracking_script' );

// Check if the current user has any of the specified roles
function iga_user_has_role( $roles ) {
    if ( !is_user_logged_in() ) {
        return false;
    }
    $user = wp_get_current_user();
    foreach ( $roles as $role ) {
        if ( in_array( $role, (array) $user->roles ) ) {
            return true;
        }
    }
    return false;
}

// Plugin deactivation
function iga_deactivation() {
    // Code to be executed when the plugin is deactivated
}
register_deactivation_hook( __FILE__, 'iga_deactivation' );

// Plugin uninstall
function iga_uninstall() {
    // Code to be executed when the plugin is deleted
    // Remove options
    delete_option( 'iga_gtag_id' );
    delete_option( 'iga_disable_for_roles' );
}
register_uninstall_hook( __FILE__, 'iga_uninstall' );

?>
