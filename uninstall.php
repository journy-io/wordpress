<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$pluginName = "journy-io";

delete_option( 'jio_api_key' );
delete_option( 'jio_snippet' );
delete_option( 'jio_cf7_submit_option' );
delete_option( 'jio_cf7_email_id' );
delete_option( 'jio_cf7_first_name_id' );
delete_option( 'jio_cf7_last_name_id' );
delete_option( 'jio_cf7_full_name_id' );
