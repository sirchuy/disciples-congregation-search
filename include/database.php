<?php

// *******************************************
// *
// *  CONGREGATIONAL DATA METHODS
// *
// *******************************************

// ****************************
// TABLE NAME
// ****************************
// get the database prefix and returns the
// appropriate table name
function get_congregation_search_table_name() {
	return 'DOCChurch_Information';	
}



// ****************************
// CREATE TABLES - ACTIVATION
// ****************************
// creates the table upon installation of the plugin
function create_congregation_search_table() {
	
	global $wpdb;
	$table_name = $wpdb->prefix . get_congregation_search_table_name();
	$log_table_name = $wpdb->prefix . get_congregation_search_table_name() . '_Log';
	$query_table_name = $wpdb->prefix . get_congregation_search_table_name() . '_Q_History';
	
	// check to see if the table has already been created
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name ( 
			PIN int NOT NULL,
			Church_Name varchar(75) NOT NULL,
			EIN varchar(15),
			Yearbook_Address_Line_1 varchar(50),
			Yearbook_Address_Line_2 varchar(50),
			Yearbook_Zip varchar(10),
			Yearbook_City varchar(50),
			Yearbook_State varchar(2),
			Mailing_Address_Line_1 varchar(50),
			Mailing_Address_Line_2 varchar(50),
			Mailing_Zip varchar(10),
			Mailing_City varchar(50),
			Mailing_State varchar(2),
			County varchar(50),
			Country varchar(50),
			Phone varchar(20),
			Fax varchar(20),
			Email_Address varchar(75),
			Web_Address varchar(100),
			Region varchar(50),
			District varchar(50),
			Yearbook_Code varchar(1),
			Minister_Email varchar(100),
			Code int,
			Date_Updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			DoNotAutoUpdate tinyint(1),
			Latitude decimal(9,6),
			Longitude decimal(9,6),
			Geocode_Confidence varchar(25),
			Google_Maps_Link varchar(255),
			PRIMARY KEY  (PIN),
			KEY EIN (EIN)
		) ENGINE=MyISAM  $charset_collate ;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		$sql_log_table = "CREATE TABLE $log_table_name (
			LogID mediumint(9) NOT NULL AUTO_INCREMENT,
			LogDate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			EntryType varchar(15),
			Message varchar(255),
			PRIMARY KEY  (LogID),
			KEY LogDate (LogDate) 
		) ENGINE=MyISAM  $charset_collate ;";
		
		dbDelta( $sql_log_table );
		
		$sql_query_table = "CREATE TABLE $query_table_name (
		  QID int NOT NULL AUTO_INCREMENT,
		  Query VARCHAR(80) NOT NULL,
		  Latitude DECIMAL(9,6) NULL,
		  Longitude DECIMAL(9,6) NULL,
		  HitCount INT NULL,
		  LastUpdated DATETIME NULL,
		  PRIMARY KEY  (QID),
		  KEY Query (Query)
		  ) ENGINE=MyISAM  $charset_collate ;";
		
		dbDelta( $sql_query_table );
		
		// run update service to input initial data.
		begin_congregation_update();

		
	}
	
}



// ****************************
// LOG ENTRY
// ****************************
// writes a log entry into the database
function write_congregation_search_log_entry($LogType, $Message) {

	global $wpdb;
		
	$insert_sql = $wpdb->insert( 
		$wpdb->prefix . get_congregation_search_table_name() . '_Log',
		array (
			'LogDate' => date('Y-m-d H:i:s'),
			'EntryType' => $LogType,
			'Message' => $Message
		),
		array (
			'%s',
			'%s',
			'%s'
		) 
	);
		
}


// ****************************
// GET CONGREGATION FROM DATABASE
// ****************************
// get congregation information from the database
// by the provided congregational PIN
function get_congregation_from_db($CongregationID) {
	global $wpdb;
		
	$results = $wpdb->get_row( $wpdb->prepare ( 
		"SELECT PIN,Church_Name,EIN,Yearbook_Address_Line_1,Yearbook_Address_Line_2,Yearbook_Zip,Yearbook_City,Yearbook_State,Country,Phone,Email_Address,Web_Address,Region,Date_Updated,Latitude,Longitude,DoNotAutoUpdate 
			FROM " . $wpdb->prefix . get_congregation_search_table_name() . " 
			WHERE PIN =  %d;", 
		$CongregationID 
		) );
	
	if ($results == null)
	{
		return false;
	}
	else
	{
		return $results;		// return the results
	}
}



// ****************************
// GET CONGREGATION BY NAME
// ****************************
// get congregation information from the database
// by the provided congregations name
function get_congregation_by_name($CongregationName) {
	global $wpdb;
		
	$results = $wpdb->get_results( $wpdb->prepare ( 
		"SELECT PIN,Church_Name,EIN,Yearbook_Address_Line_1,Yearbook_Address_Line_2,Yearbook_Zip,Yearbook_City,Yearbook_State,Country,Phone,Email_Address,Web_Address,Region,Date_Updated,Latitude,Longitude,DoNotAutoUpdate 
			FROM " . $wpdb->prefix . get_congregation_search_table_name() . " 
			WHERE Church_Name LIKE %s;", 
		'%' . $CongregationName . '%'
		) );
	
	if ($results == null)
	{
		return false;
	}
	else
	{
		return $results;		// return the results
	}
}


// ****************************
// GET CONGREGATION BY CITY
// ****************************
// get congregation information from the database
// by the provided congregations city
function get_congregation_by_city($CongregationCity) {
	global $wpdb;
		
	$results = $wpdb->get_results( $wpdb->prepare ( 
		"SELECT PIN,Church_Name,EIN,Yearbook_Address_Line_1,Yearbook_Address_Line_2,Yearbook_Zip,Yearbook_City,Yearbook_State,Country,Phone,Email_Address,Web_Address,Region,Date_Updated,Latitude,Longitude,DoNotAutoUpdate 
			FROM " . $wpdb->prefix . get_congregation_search_table_name() . " 
			WHERE Yearbook_City LIKE %s;", 
		$CongregationCity 
		) );
	
	if ($results == null)
	{
		return false;
	}
	else
	{
		return $results;		// return the results
	}
}


// ****************************
// GET CONGREGATION COLLECTION BY LAT/LONG
// ****************************
// gets a collection of congregation by latitude/longitude ranges.
function get_congregations_by_latlong($latitude, $longitude, $distance)
{
		
	global $wpdb;	
	
	$sql = 'SELECT PIN,Church_Name,EIN,Yearbook_Address_Line_1,Yearbook_Address_Line_2,Yearbook_Zip,Yearbook_City,Yearbook_State,Country,Phone,Email_Address,Web_Address,Region,Date_Updated,Latitude,Longitude,DoNotAutoUpdate, 
			ROUND ( ( 3959 * acos( cos( radians( ' . $latitude . ') ) * cos( radians( Latitude ) ) * cos( radians( Longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( Latitude ) ) ) ), 1) AS distance
			FROM ' . $wpdb->prefix . get_congregation_search_table_name() . ' 
			HAVING distance < ' . $distance . '
			ORDER BY distance, Church_Name';
					
	$results = $wpdb->get_results($sql);

	return $results;
	
}



// ****************************
// SAVE CONGREGATION
// ****************************
// save congregation info in provided object
// to the database
function save_congregation($Congregation) {
	global $wpdb;
		
	$update_sql = $wpdb->update( 
		$wpdb->prefix . get_congregation_search_table_name(),
		array (
			'Church_Name' => $Congregation->CongregationName,
			'EIN' => $Congregation->EIN,
			'Yearbook_Address_Line_1' => $Congregation->Address1,
			'Yearbook_Address_Line_2' => $Congregation->Address2,
			'Yearbook_City' => $Congregation->City,
			'Yearbook_State' => $Congregation->State,
			'Yearbook_Zip' => $Congregation->PostalCode,
			'Phone' => $Congregation->Phone,
			'Email_Address' => $Congregation->Email,
			'Web_Address' => $Congregation->Website,
			'Region' => $Congregation->Region,
			'Latitude' => $Congregation->Latitude,
			'Longitude' => $Congregation->Longitude,
			'Date_Updated' => date('Y-m-d H:i:s'),
			'DoNotAutoUpdate' => $Congregation->DoNotAutoUpdate
		),
		array ( 'PIN' => $Congregation->PIN ),
		array (
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%f',
			'%f',
			'%s',
			'%d'
		),
		array ( '%d' )
	);
	
	return $update_sql;		// return the result of the update: false if failed, integer of rows affected if successful
	
}



// ****************************
// DELETE CONGREGATION
// ****************************
// deletes a congregation from the DB via
// provided congregation PIN
function delete_congregation($CongregationPIN) {

	global $wpdb;
	
	$delete_sql = $wpdb->delete( $wpdb->prefix . get_congregation_search_table_name(), array( 'PIN' => $CongregationPIN ), array( '%d' ) );
	
	return $delete_sql;
	
}


// ****************************
// SAVE NEW CONGREGATION
// ****************************
// inserts a new congregation
function insert_congregation($Congregation) {

	global $wpdb;

	$insert_sql = $wpdb->insert (
		$wpdb->prefix . get_congregation_search_table_name(),
		array (
			'PIN' => $Congregation->PIN,
			'Church_Name' => $Congregation->CongregationName,
			'EIN' => $Congregation->EIN,
			'Yearbook_Address_Line_1' => $Congregation->Address1,
			'Yearbook_Address_Line_2' => $Congregation->Address2,
			'Yearbook_City' => $Congregation->City,
			'Yearbook_State' => $Congregation->State,
			'Yearbook_Zip' => $Congregation->PostalCode,
			'Phone' => $Congregation->Phone,
			'Email_Address' => $Congregation->Email,
			'Web_Address' => $Congregation->Website,
			'Region' => $Congregation->Region,
			'Latitude' => $Congregation->Latitude,
			'Longitude' => $Congregation->Longitude,
			'Date_Updated' => date('Y-m-d H:i:s'),
			'DoNotAutoUpdate' => $Congregation->DoNotAutoUpdate
		),
		array (
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%f',
			'%f',
			'%s',
			'%d'
		) );
		
		if (!$insert_sql)
		{
			write_congregation_search_log_entry('DB_ERROR', $wpdb->last_error);		
		}
		
		return $insert_sql;	

}



// ****************************
// GET LOG ENTRIES
// ****************************
// gets a results set for log entries
function congregation_search_get_log($HighDate, $LowDate, $LogType) {
	
	global $wpdb;	
	
	$sql = 'SELECT `LogID`,`LogDate`,`EntryType`,`Message`
			FROM `' . $wpdb->prefix . get_congregation_search_table_name() . '_Log` 
			WHERE `LogDate` <= \'' . $HighDate . ' 23:59:59\' AND `LogDate` >= \'' . $LowDate . ' 00:00:00\'';
			
	if ($LogType == 'UPDATE')
	{
		$sql .= ' AND `EntryType` like \'UPDATE%\' ';

	}
	elseif ($LogType != 'All')
	{
		$sql .= ' AND `EntryType` = \'' . $LogType . '\' ';
	}
	
	$sql .= ' ORDER BY `LogID` DESC LIMIT 1000;';
					
	$results = $wpdb->get_results($sql);

	return $results;
	
}



// ****************************
// GET QUERY HISTORY BY ID
// ****************************
// Gets a specific query entry from the database
function congregation_search_get_query_by_id($qID) {
	
	global $wpdb;
	
	$sql = "SELECT `QID`,`Query`,`Latitude`,`Longitude`,`HitCount`,`LastUpdated`
		FROM `" . $wpdb->prefix . get_congregation_search_table_name() . "_Q_History`;
		WHERE `QID` = $qID";
		
	$results = $wpdb->get_row($sql);

	return $results;
			
}


// ****************************
// GET QUERY HISTORY BY STRING
// ****************************
// Gets a specific query entry from the database
function congregation_search_get_query_by_string($query) {
	
	global $wpdb;
	
	$sql = "SELECT QID,Query,Latitude,Longitude,HitCount,LastUpdated
		FROM " . $wpdb->prefix . get_congregation_search_table_name() . "_Q_History
		WHERE Query LIKE '%s'
		ORDER BY LastUpdated DESC";
				
	$results = $wpdb->get_results( $wpdb->prepare ( $sql, $query ) );
		
	return $results;
			
}


// ****************************
// INSERT NEW QUERY
// ****************************
function congregation_search_save_new_query($SaveQuery) {

	global $wpdb;
	
	$insert_sql = $wpdb->insert (
		$wpdb->prefix . get_congregation_search_table_name() . '_Q_History',
		array (
			'Query' => $SaveQuery->Query,
			'Latitude' => $SaveQuery->Latitude,
			'Longitude' => $SaveQuery->Longitude,
			'HitCount' => $SaveQuery->HitCount,
			'LastUpdated' => date('Y-m-d H:i:s')
		),
		array (
			'%s',
			'%f',
			'%f',
			'%d',
			'%s'
		) );
		
	$lastid = $wpdb->insert_id;
		
	if (!$insert_sql)
	{
		write_congregation_search_log_entry('DB_ERROR', $wpdb->last_error);		
	}
	
	return $lastid;

}


// ****************************
// UPDATE QUERY HISTORY
// ****************************
function congregation_search_update_query($SearchQuery) {
	
	global $wpdb;
	
	$update_sql = $wpdb->update (
		$wpdb->prefix . get_congregation_search_table_name() . '_Q_History',
		array (
			'Query' => $SearchQuery->Query,
			'Latitude' => $SearchQuery->Latitude,
			'Longitude' => $SearchQuery->Longitude,
			'HitCount' => $SearchQuery->HitCount,
			'LastUpdated' => $SearchQuery->LastUpdated
		),
		array ( 'QID' => $SearchQuery->QID ),
		array (
			'%s',
			'%f',
			'%f',
			'%d',
			'%s'
		) );
		
	if (!$update_sql)
	{
		write_congregation_search_log_entry('DB_ERROR', $wpdb->last_error);		
	}
	
	return $update_sql;

}



// ****************************
// DELETE QUERY 
// ****************************
function congregation_search_delete_query($qID) {

	global $wpdb;
	
	$delete_result = $wpdb->delete ( $wpdb->prefix . get_congregation_search_table_name() . '_Q_History', array( 'QID' => $qID ), array( '%d' ) );
	
	return $delete_result;	
	
}
