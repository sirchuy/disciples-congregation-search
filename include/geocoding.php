<?php

// *******************************************
// *
// * GEOCODING FUNCTIONS
// * Handles all the functionality in connecting with Google
// * services to retreive geocoding information.
// *
// *******************************************



// *************************************
// GET LATITUDE AND LONGITUDE
// *************************************
// function to get the latitude and longitude of the city/state requested
// returns an array of latitude and logintude values
function get_congregation_city_state_latlong($search_query)
{

	$options = get_option('congregation_search_settings');

    $url = 'https://maps.googleapis.com/maps/api/geocode/json';
    $arg = array('address'=>urlencode($search_query), 'key'=>$options['congregation_search_setting_APIKey']);
    $qry = http_build_query($arg);

    $json = json_decode(congregation_search_url_get_contents( $url . '?' . $qry ) );   //file_get_contents($url.'?'.$qry));

	$result = null;
		
	if ($json) {		
		if ($json->status == 'OK') {
			
			$result = array(
				'location'=>array(
					'lat'=>$json->results[0]->geometry->location->lat,
					'long'=>$json->results[0]->geometry->location->lng
					),
				'northeast'=>array( 
					'lat'=>$json->results[0]->geometry->viewport->northeast->lat,
					'long'=>$json->results[0]->geometry->viewport->northeast->lng
					),
				'southwest'=>array(
					'lat'=>$json->results[0]->geometry->viewport->southwest->lat,
					'long'=>$json->results[0]->geometry->viewport->southwest->lng
					)
				);
			
		}
	}
			
	return $result;	
	
}

function congregation_search_url_get_contents($url) {
	$ch = curl_init();
  curl_setopt($ch, CURLOPT_TIMEOUT, 0);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  if(FALSE === ($retval = curl_exec($ch))) {
    error_log(curl_error($ch));
	return json_encode(array('status','ERROR'));
  } else {
    return $retval;
  }
}