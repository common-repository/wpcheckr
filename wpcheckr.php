<?php
/**
 * Plugin Name: WPCheckr
 * Plugin URI: https://github.com/iamtimsmith/wp-trigger-netlify-build
 * Description: WPCheckr helps you monitor all your WordPress sites in one place, so you can mantain everything up to date.
 * Version: 1.0.1
 * Author: Quema Labs
 * Author URI: https://wpcheckr.com
 * Text Domain: wpcheckr
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

require plugin_dir_path( __FILE__ ) . 'inc/options-page.php';
require plugin_dir_path( __FILE__ ) . 'inc/rest-api-endpoints.php';

/**
 * Initial Stuff
 */
function wpcheckr_initial() {

    //define( 'WPCHECKR_URL', "http://localhost/WPCheckr/" );
    define( 'WPCHECKR_URL', "https://admin.wpcheckr.com/" );

    $secret_string = get_option( 'wpcheckr-secret-string' );
    if ( empty( $secret_string ) ) {
        $new_secret_string = wp_generate_password( 12, false );
        update_option( 'wpcheckr-secret-string', $new_secret_string );
    }

}
add_action( 'admin_init', 'wpcheckr_initial' );

function wpcheckr_stylesheet() {
    $plugin_url = plugin_dir_url( __FILE__ );

    wp_enqueue_style( 'style', $plugin_url . "/style.css" );
}

add_action( 'admin_print_styles', 'wpcheckr_stylesheet' );

/**
 * Create Admin Panel
 */
function wpcheckr_menu() {
    add_submenu_page( 'tools.php', esc_html__( 'WPCheckr', 'wpcheckr' ), esc_html__( 'WPCheckr', 'wpcheckr' ), 'manage_options', 'wpcheckr', 'wpcheckr_options_page' );
}
add_action( 'admin_menu', 'wpcheckr_menu' );

/**
 * Create Settings
 */
function wpcheckr_settings_init() {
    register_setting( 'wp_headless_trigger', 'wpcheckr_settings' );
    add_settings_section(
        'wpcheckr_section',
        esc_html__( 'Settings', 'wpcheckr' ),
        '',
        'wp_headless_trigger'
    );
    add_settings_field(
        'wpcheckr_api_key',
        esc_html__( 'API Key', 'wpcheckr' ),
        'wpcheckr_api_key_render',
        'wp_headless_trigger',
        'wpcheckr_section'
    );
}
add_action( 'admin_init', 'wpcheckr_settings_init' );

/**
 * Adds a site to WPCheckr
 *
 * @return string/WP_Error
 */
function wpcheckr_plugin_add_site() {
    $site_name = get_bloginfo( 'name' );
    $site_url = get_bloginfo( 'url' );

    $secret_string = get_option( 'wpcheckr-secret-string' );
    $rest_url = get_rest_url() . 'wpcheckr/v1/' . $secret_string . '/check/';

    $wpcheckr_settings = get_option( 'wpcheckr_settings' );
    if (
        ! array_key_exists( 'wpcheckr_api_key', $wpcheckr_settings ) ||
        empty( $wpcheckr_settings['wpcheckr_api_key'] )
    ) {
        return new WP_Error( 'no-api-key', esc_html__( 'There was an error with your API Key', 'wpcheckr' ) );
    }

    $wpcheckr_api_key = sanitize_text_field( $wpcheckr_settings['wpcheckr_api_key'] );

    $post_args = array(
        'body' => array(
            'site_name' => $site_name,
            'site_url'  => $site_url,
            'rest_url'  => $rest_url,
            'api_key'   => $wpcheckr_api_key,
        ),
    );

    $post_response = wp_remote_post( WPCHECKR_URL . 'wp-json/wpcheckr-app/v1/add-user-site/', $post_args );

    if ( is_wp_error( $post_response ) ) {
        return $post_response;
    }

    if ( ! array_key_exists( 'body', $post_response ) ) {
        return new WP_Error( 'no-body', esc_html__( 'There was no body on the response', 'wpcheckr' ) );
    }

    $wpcheckr_json = json_decode( $post_response['body'], true );

    if ( ! array_key_exists( 'success', $wpcheckr_json ) ) {
        return new WP_Error( 'error-success', esc_html__( 'There was an error getting the response', 'wpcheckr' ) );
    }

    if ( false == $wpcheckr_json['success'] ) {
        return new WP_Error( 'no-success', $wpcheckr_json['message'] );
    }

    return true;

}