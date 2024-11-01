<?php
function wpcheckr_options_page() {
    settings_errors();

    echo '<h1>' . esc_html__( 'WPCheckr', 'wp-headless-trigger' ) . '</h1>';
    echo '<p>' . esc_html__( 'Set up your API Key to connect this site to WPCheckr.', 'wp-headless-trigger' ) . '</p>';

    // If setting is saved, add the site to WPCheckr
    if ( isset( $_GET['settings-updated'] ) ) {
        $add_site = wpcheckr_plugin_add_site();

        if ( is_wp_error( $add_site ) ) {
            update_option( 'wpcheckr-site-connected', false );
            echo '<div class="notice notice-error settings-error">
            <p><strong>' . esc_html( $add_site->get_error_message() ) . '</strong></p></div>';
        } else {
            update_option( 'wpcheckr-site-connected', true );
        }

    }

    $site_connected = get_option( 'wpcheckr-site-connected' );

    if ( true == $site_connected ) {
        echo '<p class="wpcheckr-connected-badge">Site Connected</p>';
    }

    echo '<form action="options.php" method="post">';
    settings_fields( 'wp_headless_trigger' );
    do_settings_sections( 'wp_headless_trigger' );
    submit_button();
    echo '</form>';
}

function wpcheckr_api_key_render() {
    $options = get_option( 'wpcheckr_settings' );
    echo '<input type="text" name="wpcheckr_settings[wpcheckr_api_key]"
    value="' . $options["wpcheckr_api_key"] . '" class="regular-text code" placeholder="3a38f5d8-aff2-400e-8e4e-4afa1befd72s">';

    echo '<p class="description">' . esc_html__( 'Your API Key from WPCheckr', 'wp-headless-trigger' ) . '</p><br>';
}