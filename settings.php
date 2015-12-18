<?php 

// *******************************
// * 
// * CONGREGATIONS SETTINGS 
// *
// *******************************

// *************************************
// ADMIN MENU HOOKS
// *************************************
add_action( 'admin_menu', 'congregation_search_add_admin_menu' );
add_action( 'admin_init', 'congregation_search_settings_init' );

function congregation_search_add_admin_menu(  ) { 
	add_submenu_page( '/congregation-search/admin.php', 'Settings', 'Settings', 'manage_options', 'congregation-search/settings.php', 'congregation_search_options_page' );
}


// *************************************
// CREATE SETTINGS PAGE
// *************************************
function congregation_search_settings_init(  ) { 

	register_setting( 'congregation_search_settings', 'congregation_search_settings' );
	
	add_settings_section(
		'congregation_search_GoogleAPI_section',
		'Geocoding Service',
		'congregation_search_Geocoding_section_callback',
		'congregation_search_settings'
	);

	add_settings_section(
		'congregation_search_UpdateService_section', 
		'Update Service',
		'congregation_search_UpdateService_section_callback', 
		'congregation_search_settings'
	);

	add_settings_field( 
		'congregation_search_setting_APIKey', 
		'Google Maps API Key', 
		'congregation_search_setting_APIKey_render', 
		'congregation_search_settings', 
		'congregation_search_GoogleAPI_section' 
	);

	add_settings_field( 
		'congregation_search_EnableUpdateService', 
		'Enable Auto-Update Service', 
		'congregation_search_EnableUpdateService_render', 
		'congregation_search_settings', 
		'congregation_search_UpdateService_section' 
	);

	add_settings_field( 
		'congregation_search_UpdateFileLocation', 
		'Update File Location', 
		'congregation_search_UpdateFileLocation_render', 
		'congregation_search_settings', 
		'congregation_search_UpdateService_section' 
	);
	
	add_settings_field( 
		'congregation_search_MarkerImage', 
		'Map Marker Image', 
		'congregation_search_MarkerImage_render', 
		'congregation_search_settings', 
		'congregation_search_GoogleAPI_section' 
	);
	
	add_settings_field(
		'congregation_search_UpdateFileColumns',
		'Update File Columns',
		'congregation_search_UpdateFileColumns_render',
		'congregation_search_settings',
		'congregation_search_UpdateService_section'
	);	
	
	add_settings_field(
		'congregation_search_UpdateFrequency',
		'Update Frequency',
		'congregation_search_UpdateFrequency_render',
		'congregation_search_settings',
		'congregation_search_UpdateService_section'
	);
	
	add_settings_field( 
		'congregation_search_QueryHistoryLength', 
		'Query History Cache Length', 
		'congregation_search_QueryHistory_render', 
		'congregation_search_settings', 
		'congregation_search_GoogleAPI_section' 
	);


}



// *************************************
// SETTING REDERING CALLBACKS
// *************************************

function congregation_search_setting_APIKey_render(  ) { 

	$options = get_option( 'congregation_search_settings' );
	?>
	<input type='text' name='congregation_search_settings[congregation_search_setting_APIKey]' style='width:300px;' value='<?php echo $options['congregation_search_setting_APIKey']; ?>'>
	<?php

}


function congregation_search_EnableUpdateService_render(  ) { 

	$options = get_option( 'congregation_search_settings' );
	
	$update_service_enabled = 0;
	if ( isset($options['congregation_search_EnableUpdateService']) )
	{
		$update_service_enabled = $options['congregation_search_EnableUpdateService'];
	}
	
	?>
	<input type='checkbox' name='congregation_search_settings[congregation_search_EnableUpdateService]' <?php checked( $update_service_enabled, 1 ); ?> value='1'>
	<?php
	if ($update_service_enabled == '1') {
		
		// check to see if the cron job exists. If so, delete it so we can recreate it with the new values.
		if ( wp_next_scheduled( 'Congregation_Data_Update_Schedule' ) ) {
			wp_clear_scheduled_hook( 'Congregation_Data_Update_Schedule' );
		}
		
		$time_to_start = time();
	
		// set the start time - if it's not hourly then set it for 
		// early the next morning
		if ( $options['congregation_search_UpdateFrequency'] != 'hourly')
		{
			$time_to_start = strtotime("tomorrow 3:30 am");
		}
		
		if ( wp_schedule_event( $time_to_start, $options['congregation_search_UpdateFrequency'], 'Congregation_Data_Update_Schedule') === false )
		{
			echo '<p style="color:#990000;font-weight:bold;">An error occured when attempting to schedule the update service.</p>';
		}
		else
		{
			echo "<span style=\"color:rgb(153, 153, 153)\">Next execution time: " . date( "F j, Y, g:i a", wp_next_scheduled('Congregation_Data_Update_Schedule') ) . "</span>";
		}
		
	}
	else
	{
		// check to see if the cron job exists. If so, delete it.
		if ( wp_next_scheduled( 'Congregation_Data_Update_Schedule' ) ) {
			wp_clear_scheduled_hook( 'Congregation_Data_Update_Schedule' );
		}	
	}

}


function congregation_search_UpdateFileLocation_render(  ) { 

	$options = get_option( 'congregation_search_settings' );
	?>
	<input type='text' id='congregation_search_UpdateFileLocation' name='congregation_search_settings[congregation_search_UpdateFileLocation]' style='width:600px;' value='<?php echo $options['congregation_search_UpdateFileLocation']; ?>'> <div id="CheckUpdateFileLocation"><div id="Result"></div><a href="javascript:void(0);" ID="VerifyUpdateFileURL">Verify Path</a></div>
	<?php

}


function congregation_search_MarkerImage_render(  ) { 

	$options = get_option( 'congregation_search_settings' );
	?>
	<input type='text' name='congregation_search_settings[congregation_search_MarkerImage]' style='width:300px;' value='<?php echo $options['congregation_search_MarkerImage']; ?>'>
	<?php

}

function congregation_search_QueryHistory_render(  ) { 

	$options = get_option( 'congregation_search_settings' );
	?>
	<select name='congregation_search_settings[congregation_search_QueryLength]'>
	<option value='disabled'<?php echo ($options['congregation_search_QueryLength'] == 'disabled' ? ' selected' : '') ?>>Disabled</option>
	<option value='-1 week'<?php echo ($options['congregation_search_QueryLength'] == '-1 week' ? ' selected' : '') ?>>Once a Week</option>
	<option value='-1 month'<?php echo ($options['congregation_search_QueryLength'] == '-1 month' ? ' selected' : '') ?>>Once a Month</option>
	<option value='-3 months'<?php echo ($options['congregation_search_QueryLength'] == '-3 months' ? ' selected' : '') ?>>Every 3 Months</option>
	<option value='-6 months'<?php echo ($options['congregation_search_QueryLength'] == '-6 months' ? ' selected' : '') ?>>Every 6 Months</option>
	<option value='-1 year'<?php echo ($options['congregation_search_QueryLength'] == '-1 year' ? ' selected' : '') ?>>Once a Year</option>
	</select>
	<p>The Congregation Search will cache search queries so that calls to the Google Geocoding service are minimized. Query history can be disabled (not recommended) or can save query information for the length of time specified above.</p>
	<?php

}


function congregation_search_UpdateFrequency_render(  ) {
	$options = get_option( 'congregation_search_settings' );

	?>
	<select name='congregation_search_settings[congregation_search_UpdateFrequency]'>
	<option value='hourly'<?php echo ($options['congregation_search_UpdateFrequency'] == 'hourly' ? ' selected' : '') ?>>Hourly</option>
	<option value='daily'<?php echo ($options['congregation_search_UpdateFrequency'] == 'daily' || $options['congregation_search_UpdateFrequency'] == '' ? ' selected' : '') ?>>Daily</option>
	<option value='weekly'<?php echo ($options['congregation_search_UpdateFrequency'] == 'weekly' ? ' selected' : '') ?>>Weekly</option>
	<option value='monthly'<?php echo ($options['congregation_search_UpdateFrequency'] == 'monthly' ? ' selected' : '') ?>>Monthly</option>
	</select><div id="StartUpdateNow"><div id="UpdateStatus"></div><a id="UpdateLink" href="javascript:void(0);">Start Update Now</a></div>
	<?php
}

function congregation_search_UpdateFileColumns_render(  ) {
	$option = get_option( 'congregation_search_settings' );
	?>
	
	<textarea name='congregation_search_settings[congregation_search_UpdateFileColumns]' style="width:300px;height:130px;font-size:.8em;"><?php echo $option['congregation_search_UpdateFileColumns'] ?></textarea>
	<p>This defines the columns that are provided in the update file. Required columns that are saved to the search database are 'PIN', 'CongregationName', 'Address1', 'Address2', 'City', 'State', 'PostalCode', 'Phone', 'Email', 'Website', 'LastUpdated', and 'UpdateCode'. Optional columns are 'Latitude' and 'Longitude'. 'UpdateCode' contains the flag to determine if the record should be deleted or maintained. Each column name must be on a separate line. All other column names will be ignored but it is recommended that they are included in this configuration for descriptive purposes.</p>
	
	<?php
}

function congregation_search_UpdateService_section_callback(  ) { 

	echo 'The Update Service automatically gathers data about congregations that have been updated through the office of the Year Book and Directory. This file is a preformatted CSV file uploaded to a server location every evening. The update file location must be a valid http URL.';

}
function congregation_search_Geocoding_section_callback(  ) {

	echo 'The Google Geocoding service provides mapping capability. A valid API key will be required to use this service.';

}


// *************************************
// MAIN SETTINGS PAGE GENERATION
// *************************************
function congregation_search_options_page(  ) { 

	?>
<div class="wrap">
	<h2>Congregation Search Settings</h2>
	
	<?php 
	$settings_update = isset($_GET['settings-updated']) ? $_GET['settings-updated'] : false;
	if($settings_update) { ?>
	<div id="message" class="updated"><p>Settings Updated</p></div>
	
	<?php } ?>
	
	<form action='options.php' method='post'>
		
		<?php
		settings_fields( 'congregation_search_settings' );
		do_settings_sections( 'congregation_search_settings' );
		submit_button();
		?>
		
	</form>
		
<h2>Update Log</h2>
<a href="javascript:void(0);" id="ShowUpdateLog">Click to View the Log</a>
<div id="LogViewer" style="display:none;">

<?php

$date = date("Y-m-d");
$lowdate = date("Y-m-d", strtotime(date("Y-m-d", strtotime($date)) . " -1 week"));

?>

	<div id="LogViewerToolbar">
		<form id="LogViewerForm">
			<label id="LogHighDateLabel">High Date:</label>
			<input type="text" ID="LogHighDate" value="<?php echo $date ?>"/>
			<label id="LogLowDateLabel">Low Date:</label>
			<input type="text" ID="LogLowDate" value="<?php echo $lowdate ?>"/>
			<label id="LogHighDateLabel">Log Type:</label>
			<select id="LogType">
				<option value="All">All</option>
				<option value="UPDATE">Updates</option>
				<option value="INFO">Informational</option>
				<option value="ADDED">Added</option>
				<option value="DELETE">Deleted</option>
				<option value="ERROR">Errors</option>
			</select>
			<a href="javascript:void(0);" id="LogButton" class="button button-primary">View Log</a>
		</form>
		
		</div>
	<div id="LogViewerWindow">
		
	</div>



</div>


</div>

	<?php

}




