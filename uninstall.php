<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$settings_options = get_option( 'rfc_options' );

if ( !isset( $settings_options['rfc_remove_uninstall'] ) || $settings_options['rfc_remove_uninstall'] != '1' ){
    return;
}

global $wpdb;

$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rfc_%';" ); 
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'rfc_%';" ); 