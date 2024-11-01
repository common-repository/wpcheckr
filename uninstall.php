<?php
// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

delete_option( 'wpcheckr-site-connected' );
// for site options in Multisite
delete_site_option( 'wpcheckr-site-connected' );

delete_option( 'wpcheckr_settings' );
// for site options in Multisite
delete_site_option( 'wpcheckr_settings' );
