<?php

// ***********************************8
// *
// *  AJAX FUNCTIONS
// *
// ***********************************8


// *************************************
// ENQUEUE SCRIPTS
// *************************************
add_action( 'admin_enqueue_scripts', 'admin_scripts' );
function admin_scripts($hook) {
    if( 'index.php' != $hook ) {
		// Only applies to dashboard panel
		return;
    }
        
	wp_enqueue_script( 'ajax-script', plugins_url( '/congregation-search/js/admin.js', __FILE__ ), array('jquery') );

	// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
	wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}


// *************************************
// GET CONGREGATION
// *************************************
add_action("wp_ajax_get_congregation", "get_congregation_callback");
function get_congregation_callback() {
	$CongregationPIN = $_POST['PIN'];
		
	$Congregation = new Congregation();
	
	$Congregation->get_congregation_by_PIN($CongregationPIN);
	
	if ($Congregation)
	{
		
		$jsonData["congregation"]["CongregationName"] = $Congregation->CongregationName;
		$jsonData["congregation"]["EIN"] = $Congregation->EIN;
		$jsonData["congregation"]["Address1"] = $Congregation->Address1;
		$jsonData["congregation"]["Address2"] = $Congregation->Address2;
		$jsonData["congregation"]["City"] = $Congregation->City;
		$jsonData["congregation"]["State"] = $Congregation->State;
		$jsonData["congregation"]["PostalCode"] = $Congregation->PostalCode;
		$jsonData["congregation"]["Phone"] = $Congregation->Phone;
		$jsonData["congregation"]["Email"] = $Congregation->Email;
		$jsonData["congregation"]["Website"] = $Congregation->Website;
		$jsonData["congregation"]["Region"] = $Congregation->Region;
		$jsonData["congregation"]["Latitude"] = $Congregation->Latitude;
		$jsonData["congregation"]["Longitude"] = $Congregation->Longitude;
		$jsonData["congregation"]["DoNotAutoUpdate"] = $Congregation->DoNotAutoUpdate;
		$jsonData["congregation"]["PIN"] = $Congregation->PIN;
		$jsonData["status"] = 'success';		
		
	}
	else
	{
		$jsonData["status"] = 'fail';
	}

	echo json_encode($jsonData);

	die();
	
}


// *************************************
// SAVE CONGREGATION
// *************************************
add_action("wp_ajax_save_congregation", "save_congregation_callback");
function save_congregation_callback() {
	
	// determine if this a save existing or save new situation
	$CongregationPIN = $_POST['PIN'];
	$Congregation = new Congregation();

	$saveType = $_POST['savetype'];

	// populate the object with the posted data
	$Congregation->PIN = $CongregationPIN;
	$Congregation->CongregationName = trim($_POST['CongregationName']);
	$Congregation->EIN = trim($_POST['EIN']);
	$Congregation->Address1 = trim($_POST['Address1']);
	$Congregation->Address2 = trim($_POST['Address2']);
	$Congregation->City = trim($_POST['City']);
	$Congregation->State = trim($_POST['State']);
	$Congregation->PostalCode = trim($_POST['PostalCode']);
	$Congregation->Phone = trim($_POST['Phone']);
	$Congregation->Email = trim($_POST['Email']);
	$Congregation->Website = trim($_POST['Website']);
	$Congregation->Region = trim($_POST['Region']);
	$Congregation->Latitude = trim($_POST['Latitude']);
	$Congregation->Longitude = trim($_POST['Longitude']);
	$Congregation->DoNotAutoUpdate = $_POST['DoNotAutoUpdate'];
	
	if ($saveType == 'existing')
	{
		$SavedResult = $Congregation->save_current_congregation();
	}
	else
	{
		$SavedResult = $Congregation->save_new_congregation();
	}

	$ResultResponse['result'] = $SavedResult;
	
	echo json_encode($ResultResponse);

	die();
	
}


// *************************************
// DELETE CONGREGATION
// *************************************
add_action("wp_ajax_delete_congregation", "delete_congregation_callback");
function delete_congregation_callback() {

	// determine if this a save existing or save new situation
	$CongregationPIN = $_POST['PIN'];
	
	$ResultResponse['result'] = delete_congregation($CongregationPIN);
	
	echo json_encode($ResultResponse);

	die();
	
}



// *************************************
// CSV FILE VERIFICATION
// *************************************
// verifies the server can retrieve the CSV file 
// provided via the settings screen
add_action("wp_ajax_verify_congregation_update_file", "verify_congregation_update_file");
function verify_congregation_update_file() {

	$UpdateFilePath = $_POST['UpdateFilePath'];
		
	$ch = curl_init( $UpdateFilePath );

    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_HEADER, true );
    curl_setopt( $ch, CURLOPT_NOBODY, true );

    $header_info = curl_exec( $ch );

    curl_close( $ch );

	if ( $header_info ) {
		$header_info_array = explode("\r", $header_info);
	}

	$content_type = '';

	// find content type in response
	for ( $x = 0; $x < count( $header_info_array ); $x++ ) { 
		if ( strpos( $header_info_array[$x], 'Content-Type:' ) !== false) {
			$content_type = trim( substr( $header_info_array[$x], 14, strlen( $header_info_array[$x] ) - 13 ) );
		}
	
	}

	$ResultResponse = '';
	
	if (strpos($header_info_array[0], '200') !== false)
	{
		$ResultResponse['result'] = true;
		$ResultResponse['contenttype'] = $content_type;
	}
	else
	{
		$ResultResponse['result'] = false;
	}

	echo json_encode($ResultResponse);

	die();	
	
}


// *************************************
// MANUAL UPDATE START
// *************************************
// starts the update process via user request
add_action("wp_ajax_begin_manual_congregation_update", "begin_manual_congregation_update");
function begin_manual_congregation_update() {

	// start the update
	// will return an array [0]=>true|false of success and [1]=>status message to display.
	$UpdateResult = begin_congregation_update();
	
	$ResultResponse = '';
	
	if ($UpdateResult != null)
	{
		$ResultResponse['result'] = $UpdateResult[0];
		$ResultResponse['message'] = $UpdateResult[1];
	}
	else
	{
		$ResultResponse['result'] = false;
		$ResultResponse['message'] = 'Unknown error occured when attempting to update congregations. Check the log file for details.';
	}
	
	echo json_encode($ResultResponse);
	
	die();
	
}


// *************************************
// GET LOG
// *************************************
// get's the most recent log entries and json_econdes them to the client
add_action("wp_ajax_get_congregation_search_log", "get_congregation_search_log");
function get_congregation_search_log() {
	
	$HighDate = $_POST['HighDate'];
	$LowDate = $_POST['LowDate'];
	$LogType = $_POST['LogType'];
	
	$LogResult = congregation_search_get_log($HighDate, $LowDate, $LogType);
	
	$ResultResponse = '';
	
//	if ($LogResult != null)
//	{
		$ResultResponse['result'] = true;
		$ResultResponse['data'] = $LogResult;
//	}
//	else
//	{
//		$ResultResponse['result'] = false;
//		$ResultResponse['data'] = "Unknown error occured when attempting to retrieve the log.";
//	}
	
	echo json_encode($ResultResponse);

	die();
}