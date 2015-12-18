<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

global $wpdb;
	
// delete options
delete_option( 'congregation_search_settings' );


// delete any cron jobs
wp_clear_scheduled_hook( 'Congregation_Data_Update_Schedule' );

// drop custom table

$tablename = $wpdb->prefix . 'DOCChurch_Information';
$wpdb->query( "DROP TABLE " . $tablename );
$wpdb->query( "DROP TABLE " . $tablename . "_Log" );
$wpdb->query( "DROP TABLE " . $tablename . "_Q_History" );