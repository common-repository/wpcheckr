<?php
header( "Access-Control-Allow-Origin: https://wpcheckr.com" );

/**
 * Check
 *
 */
function wpcheckr_check_endpoint( WP_REST_Request $request ) {

    // $params = $request->get_params();

    // if ( ! isset( $params ) || ! is_array( $params ) ) {
    //     return new WP_Error( 'no-data', 'No se recibieron datos.' );
    // }

    // WPCheckr Plugin Version
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $wpcheckr_plugin = plugin_dir_path( __DIR__ ) . 'wpcheckr.php';
    if ( is_file( $wpcheckr_plugin ) ) {
        $plugin_data = get_plugin_data( $wpcheckr_plugin );
        $wpcheckr_version = $plugin_data['Version'];
    } else {
        $wpcheckr_version = false;
    }

    // WP Version
    $wp_version = get_bloginfo( 'version' );
    $site_title = get_bloginfo( 'name' );
    $site_url = get_bloginfo( 'url' );
    $rest_url = get_rest_url();

    $site_icon = get_site_icon_url();

    if ( ! function_exists( 'get_preferred_from_update_core' ) ) {
        require_once ABSPATH . 'wp-admin/includes/update.php';
    }

    // This is to force checks
    // Clear transient
    // delete_site_transient( 'update_core' );
    // delete_site_transient( 'update_themes' );
    delete_site_transient( 'update_plugins' );

    $cur = get_preferred_from_update_core();

    if ( ! isset( $cur->response ) || $cur->response != 'upgrade' ) {
        $wp_update = false;
    } else {
        $wp_update = $cur;
    }

    // Theme Info
    $wp_get_theme = wp_get_theme();
    $theme_slug = $wp_get_theme->get_stylesheet();
    $theme_info = false;

    // Check if the theme needs update
    $updates = false;
    $updates_transient = get_site_transient( 'update_themes' );
    if ( empty( $updates_transient ) ) {
        wp_update_themes();
        $updates_transient = get_site_transient( 'update_themes' );
    }

    if ( isset( $updates_transient->response ) ) {
        if ( array_key_exists( $theme_slug, $updates_transient->response ) ) {
            $updates = $updates_transient->response[$theme_slug];
        }
    }

    if ( $wp_get_theme->exists() ) {
        $theme_info = array(
            'name'    => $wp_get_theme->get( 'Name' ),
            'slug'    => $theme_slug,
            'version' => $wp_get_theme->get( 'Version' ),
            'update'  => $updates,
        );
    }

    // Plugins Info

    // Check if get_plugins() function exists. This is required on the front end of the
    // site, since it is in a file that is normally only loaded in the admin.
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();
    $active_plugins = get_option( 'active_plugins' );
    $plugin_response = array();
    foreach ( $active_plugins as $index => $plugin ) {
        if ( array_key_exists( $plugin, $all_plugins ) ) {

            // Check if the plugin needs update
            $plugins_updates = false;
            $plugins_updates_transient = get_site_transient( 'update_plugins' );

            if ( empty( $plugins_updates_transient ) ) {
                // Force refresh of plugin update information
                wp_update_plugins();
                $plugins_updates_transient = get_site_transient( 'update_plugins' );
            }

            if ( isset( $plugins_updates_transient->response ) ) {
                if ( array_key_exists( $plugin, $plugins_updates_transient->response ) ) {
                    $plugins_updates = $plugins_updates_transient->response[$plugin];
                }
            }

            $this_plugin = array(
                'name'    => $all_plugins[$plugin]['Name'],
                'slug'    => $plugin,
                'version' => $all_plugins[$plugin]['Version'],
                'author'  => $all_plugins[$plugin]['AuthorName'],
                'update'  => $plugins_updates,

            );
            array_push( $plugin_response, $this_plugin );
        }
    }

    $response = array(
        'wp_version'       => $wp_version,
        'wp_update'        => $wp_update,
        'theme_info'       => $theme_info,
        'plugins'          => $plugin_response,
        'site_title'       => $site_title,
        'site_url'         => $site_url,
        'site_icon'        => $site_icon,
        'rest_url'         => $rest_url,
        'wpcheckr_version' => $wpcheckr_version,
        'response_date'    => date( 'c' ),
    );

    $rest_response = new WP_REST_Response( $response, 200 );

    // Set headers.
    $rest_response->set_headers( array( 'Cache-Control' => 'no-cache' ) );

    return $rest_response;

}

/**
 * Register Endpoint
 *
 */
function wpcheckr_add_check_endpoint() {

    $secret_string = get_option( 'wpcheckr-secret-string' );

    register_rest_route( 'wpcheckr/v1', '/' . $secret_string . '/check/', array(
        'methods'       => 'GET',
        'callback'      => 'wpcheckr_check_endpoint',
        'show_in_index' => false,
    ) );
}

add_action( 'rest_api_init', 'wpcheckr_add_check_endpoint' );