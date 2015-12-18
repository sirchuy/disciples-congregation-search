<?php

// ******************************
// *
// *  CONGREGATION DATA UPDATE SERVICE
// *
// ******************************


// *************************************
// BEGIN UPDATE
// *************************************
// function to begin the update process.
// returns an array of the results [0]=>true/false of success and [1]=>Message if error
function begin_congregation_update() {
	
	$options = get_option( 'congregation_search_settings' );

	// get the file location	
	ini_set( 'auto_detect_line_endings', TRUE );
		
	// use curl to get around any hosts that disable allow_url_open in PHP
	$RawDataFile = fopen('php://temp', 'w+');
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 	$options['congregation_search_UpdateFileLocation']);
	curl_setopt($curl, CURLOPT_FILE, $RawDataFile);
	curl_exec($curl);
	curl_close($curl);
	
	rewind($RawDataFile);
	
	// check to see if a valid file was received
	if (!$RawDataFile)
	{
		// didn't open - kill the process;
		return array(false, 'Could not open the update file. Aborted.');
		add_congregation_search_update_log('WARNING', 'The congregation update service file could not be opened. The update service did not start.');

	}
	
	
	//Get Column mappings
	$UpdateColumnHeadings = explode( "\n", 
		trim( str_replace( 
			array("\r\n", "\n\r", "\r"), 
			"\n", 
			strtolower( $options['congregation_search_UpdateFileColumns'] ) 
		) )
	);
		
	// make certain the UpdateColumnHeadings contain the required fields
	if (!check_congregation_update_fields($UpdateColumnHeadings))
	{
		add_congregation_search_update_log('ERROR', 'The congregation data update service could not start - required update file field not provided in the settings.');
		
		return array(false, 'All required fields in mappings are not provided in settings.'); // return a message
	}
	
	// start the processes
	// 1. Get the row from the CSV file
	// 2. Iterate throught the columns to create a congregation object
	// 3. determine whether this is an update, insert or delete action
	// 4. perform the action
		
	$WorkArray = array_fill_keys( $UpdateColumnHeadings, null );
	
	// add a log entry to show the service has started.
	add_congregation_search_update_log('UPDATE_START', 'The congregation data update service has started...');

	// start the update process
	$UpdateResults = update_congregation_rows( $UpdateColumnHeadings, $RawDataFile );
	
	if ($UpdateResults['errors'] == 0) {
		// add a log entry to show the service has started.
		add_congregation_search_update_log('UPDATE_COMPLETED_SUCCESS', 'The congregation data update service completed without errors. Updated:' . $UpdateResults['updates'] . ', Deleted: ' . $UpdateResults['deleted'] . ', Ignored: ' . $UpdateResults['ignored']);
		return array(true, 'Process completed without errors. Updated:' . $UpdateResults['updates'] . ', Deleted: ' . $UpdateResults['deleted'] . ', Ignored: ' . $UpdateResults['ignored']); // return a message

	}
	else
	{
		add_congregation_search_update_log('UPDATE_COMPLETED_ERRORS', 'The congregation data update service completed with ' . $UpdateResults['errors'] . ' error(s). Updated:' . $UpdateResults['updates'] . ', Deleted: ' . $UpdateResults['deleted'] . ', Ignored: ' . $UpdateResults['ignored']);
		return array(true, 'Process completed with ' . $UpdateResults['errors'] . ' error(s). Updated:' . $UpdateResults['updates'] . ', Deleted: ' . $UpdateResults['deleted'] . ', Ignored: ' . $UpdateResults['ignored']); // return a message

	}

	
}


// *************************************
// UPDATE CONGREGATION ROWS
// *************************************
// performs the update process. 
// Returns an array that includes number of record errors, updates, ignored and deleted.
function update_congregation_rows($KeyArray, $RawDataFile)
{
	
	$UpdateResults['errors'] = 0;
	$UpdateResults['updates'] = 0;
	$UpdateResults['ignored'] = 0;
	$UpdateResults['deleted'] = 0;
	
	while (!feof($RawDataFile)) {

		$newRow = fgetcsv($RawDataFile);
		
		// loop through the arrays to create a new
		// associative array
		
		$newArray = array();
		
		$LoopCount = count($KeyArray);
		for ($loop = 0; $loop < $LoopCount; $loop++) {
			$newArray = array_push_assoc($newArray, $KeyArray[$loop], $newRow[$loop]);
		}
		
		
		// create the congregation class
		$UpdateCongregation = new Congregation();
		

		// check to see if we need to delete it first
		if (strtolower($newArray['updatecode']) == 'd')
		{
			// check to see if it even exists, otherwise, ignore it.
			if ($UpdateCongregation->get_congregation_by_PIN($newArray['pin']))
			{
				// check to see if it has been flagged for no auto updates
				if ($UpdateCongregation->DoNotAutoUpdate) {
					// marked to not update - log it and stop.
					add_congregation_search_update_log('INFO', 'Congregation ' . $newArray['pin'] . ' is locked to not allow updates. Delete request ignored.');
					$UpdateResults['ignored']++;
				}
				elseif (delete_congregation($newArray['pin'])) {
					add_congregation_search_update_log('DELETE', 'Congregation ' . $newArray['pin'] . ' was deleted.');
					$UpdateResults['deleted']++;
				}
				else {
					add_congregation_search_update_log('ERROR', 'Congregation ' . $newArray['pin'] . ' could not be deleted.');
					$UpdateResults['errors']++;
				}
			}


		}
		else
		{
						
			// first see if the congregation exists and make certain we d
			if (!$UpdateCongregation->get_congregation_by_PIN($newArray['pin'])) {
				// if false, this is a new congregation - lets create it	
				
				$UpdateCongregation->PIN = $newArray['pin'];
				$UpdateCongregation->CongregationName = $newArray['congregationname'];
				$UpdateCongregation->EIN = $newArray['ein'];
				$UpdateCongregation->Address1 = $newArray['address1'];
				$UpdateCongregation->Address2 = $newArray['address2'];
				$UpdateCongregation->City = $newArray['city'];
				$UpdateCongregation->State = $newArray['state'];
				$UpdateCongregation->PostalCode = $newArray['postalcode'];
				$UpdateCongregation->Phone = $newArray['phone'];
				$UpdateCongregation->Email = $newArray['email'];
				$UpdateCongregation->Website = $newArray['website'];	
				$UpdateCongregation->Region = $newArray['region'];		
				
				if (array_key_exists( 'latitude', $newArray ) && array_key_exists( 'longitude', $newArray ) ) {

					// if latitude and longitude were provided then add them	
					$UpdateCongregation->Latitude = $newArray['latitude'];
					$UpdateCongregation->Longitude = $newArray['longitude'];
				}
				else if ($UpdateCongregation->Address1 != '' && $UpdateCongregation->City != '' && $UpdateCongregation->State !='')
				{

					//geocode the address and add it to the record
					$newLatLong = get_congregation_city_state_latlong($UpdateCongregation->Address1 . ', ' . $UpdateCongregation->City . ', ' . $UpdateCongregation->State);
					if ($newLatLong != null)
					{
						$UpdateCongregation->Latitude = $newLatLong['location']['lat'];
						$UpdateCongregation->Longitude = $newLatLong['location']['long'];
						add_congregation_search_update_log('GEOCODE_SUCCESS', 'Congregation ' . $newArray['pin'] . ' was successfully geocoded.');
					}
					else
					{
						add_congregation_search_update_log('GEOCODE_FAILURE', 'Congregation ' . $newArray['pin'] . ' could not be geocoded.');
					}
				}
				
				// save and log the result
				$SavedCongregationResults = $UpdateCongregation->save_new_congregation();
				if ($SavedCongregationResults)
				{
					add_congregation_search_update_log('ADDED', 'Congregation ' . $newArray['pin'] . ' was added.');
					$UpdateResults['updates']++;
				}
				else
				{
					add_congregation_search_update_log('ERROR', 'Congregation ' . $newArray['pin'] . ' could not be added: ' . $SavedCongregationResults);
					$UpdateResults['errors']++;
				}
				
			}
			else
			{
				// first checked to see if it's been flagged to not update,
				// then check to see if the updated data is more recent
				// than the current data in the database
				// We do this because update file contain updated for the last 7 days,
				// in case a previous update execution created an error that
				// precented some data from being updated.
				
				// check for no auto updates to this congregation
				if ($UpdateCongregation->DoNotAutoUpdate) {
					add_congregation_search_update_log('INFO', 'Congregation ' . $newArray['pin'] . ' is locked to not allow updated. Update request ignored.');
					$UpdateResults['ignored']++;
				}
				// check for date updated
				elseif (strtotime($UpdateCongregation->DateUpdated) < strtotime($newArray['lastupdated']))
				{
					$UpdateCongregation->CongregationName = $newArray['congregationname'];
					$UpdateCongregation->EIN = $newArray['ein'];
					$UpdateCongregation->Address1 = $newArray['address1'];
					$UpdateCongregation->Address2 = $newArray['address2'];
					$UpdateCongregation->City = $newArray['city'];
					$UpdateCongregation->State = $newArray['state'];
					$UpdateCongregation->PostalCode = $newArray['postalcode'];
					$UpdateCongregation->Phone = $newArray['phone'];
					$UpdateCongregation->Email = $newArray['email'];
					$UpdateCongregation->Website = $newArray['website'];		
					$UpdateCongregation->Region = $newArray['region'];	
										
					if (array_key_exists( 'latitude', $newArray ) && array_key_exists( 'longitude', $newArray ) ) {
						// now check to see if they exist in the database. If not, don't overwrite the data
						// as site-side lat/long data might be overwritten
						if ($newArray['latitude'] != '' && $newArray['longitude'] != '') {
							$UpdateCongregation->Latitude = $newArray['latitude'];
							$UpdateCongregation->Longitude = $newArray['longitude'];
						}
					}
					else if ($UpdateCongregation->Latitude == 0.0 || $UpdateCongregation->Longitude == 0.0)
					{

						//geocode the address and add it to the record
						$newLatLong = get_congregation_city_state_latlong($UpdateCongregation->Address1 . ', ' . $UpdateCongregation->City . ', ' . $UpdateCongregation->State);
						if ($newLatLong != null)
						{
							$UpdateCongregation->Latitude = $newLatLong['location']['lat'];
							$UpdateCongregation->Longitude = $newLatLong['location']['long'];
							add_congregation_search_update_log('GEOCODE_SUCCESS', 'Congregation ' . $newArray['pin'] . ' was successfully geocoded.');
							
						}
						else
						{
							add_congregation_search_update_log('GEOCODE_FAILURE', 'Congregation ' . $newArray['pin'] . ' could not be geocoded.');
						}
					}
					
					$UpdateCongregationResults = $UpdateCongregation->save_current_congregation();
					if ($UpdateCongregationResults)
					{
						add_congregation_search_update_log('UPDATED', 'Congregation ' . $newArray['pin'] . ' was updated.');
						$UpdateResults['updates']++;
					}
					else
					{
						add_congregation_search_update_log('ERROR', 'Congregation ' . $newArray['pin'] . ' could not be updated:' . $UpdateCongregationResults);
						$UpdateResults['errors']++;
					}
				
				}

	
			}
		}
		
	} // end while
	
	
	// close the data file
	fclose($RawDataFile);
	
	return $UpdateResults;
}



// *************************************
// CUSTOM ARRAY PUSH
// *************************************
// function to push new associate key elements into an array
// so we can accurate reference the array to populate
// a congregation class.
function array_push_assoc($array, $key, $value){
	$array[$key] = $value;
	return $array;
}



// *************************************
// VERIFICATION
// *************************************
// checks to make certain the required field
// mappings were provided in the settings
function check_congregation_update_fields($UpdateFieldSettings) {
	
	if (
		array_search('pin', $UpdateFieldSettings) !== false &&
		array_search('congregationname', $UpdateFieldSettings) !== false &&
		array_search('ein', $UpdateFieldSettings) !== false &&
		array_search('address1', $UpdateFieldSettings) !== false &&
		array_search('address2', $UpdateFieldSettings) !== false &&
		array_search('city', $UpdateFieldSettings) !== false &&
		array_search('state', $UpdateFieldSettings) !== false &&
		array_search('postalcode', $UpdateFieldSettings) !== false &&
		array_search('phone', $UpdateFieldSettings) !== false &&
		array_search('email', $UpdateFieldSettings) !== false &&
		array_search('website', $UpdateFieldSettings) !== false &&
		array_search('region', $UpdateFieldSettings) !== false &&
		array_search('lastupdated', $UpdateFieldSettings) !== false &&
		array_search('updatecode', $UpdateFieldSettings) !== false
	)
	{
		return true;
	}
	else
	{
		return false;
	}
}


// *************************************
// SCHEDULING
// *************************************
// Scheduleing HOOK action and function 
add_action( 'Congregation_Data_Update_Schedule', 'CongregationUpdateScheduling' );
function CongregationUpdateScheduling() {
	begin_congregation_update();
}



// *************************************
// SCHEDULING INTERVALS
// *************************************
// add a weekly and monthly schedule interval
add_filter( 'cron_schedules', 'cron_congregations_add_weekly_and_monthly' );
function cron_congregations_add_weekly_and_monthly( $schedules ) {
 	// Adds once weekly to the existing schedules.
 	$schedules['weekly'] = array(
 		'interval' => 604800,
 		'display' => __( 'Once Weekly' )
 	);
	$schedules['monthly'] = array (
		'interval' => 2592000,
		'display' => __ ( 'Once Monthly' )
	);
 	return $schedules;
}



// *************************************
// ADDS A LOG ENTRY
// *************************************
// adds a log entry to the file
function add_congregation_search_update_log($LogType, $Message) {
	write_congregation_search_log_entry($LogType, $Message);
}