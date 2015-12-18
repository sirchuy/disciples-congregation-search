// JavaScript Document

// Congregation Search
// Author: Christopher Higgins
// Date Updated: January 6th, 2015
// Version History: 
//		1/6/2014 - initial development


// ******************
// GOOGLE MAPS
// ******************

// google maps functions
function initialize_congregation_map() {
	
	var map;
    var bounds = new google.maps.LatLngBounds();
    var mapOptions = {
        mapTypeId: 'roadmap'
    };
                    
    // Display a map on the page
    map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
  	
                
    // Display multiple markers on a map
    var infoWindow = new google.maps.InfoWindow(), marker, i;
  
  	var markers = [];
    // Loop through our array of markers & place each one on the map  
	for( i = 0; i < congregationmarkers.length; i++ ) {
        var position = new google.maps.LatLng(congregationmarkers[i][1], congregationmarkers[i][2]);
        bounds.extend(position);
        marker = new google.maps.Marker({
            position: position,
            map: map,
			icon: GoogleMapMarker,
            title: congregationmarkers[i][0]
        });
        
		markers.push(marker);
		
        // Allow each marker to have an info window    
        google.maps.event.addListener(marker, 'click', (function(marker, i) {
            return function() {
                infoWindow.setContent(congregationInfoWindow[i][0]);
                infoWindow.open(map, marker);
            }
        })(marker, i));

        // Automatically center the map fitting all markers on the screen
        map.fitBounds(bounds);
    }
	
	// cluster the markers
	var mc = new MarkerClusterer(map, markers);

	// center and zoom the map
	// map.setCenter(bounds.getCenter(), map.fitBounds() ); //map.getBoundsZoomLevel(bounds));
    
	// Override our map zoom level once our fitBounds function runs (Make sure it only runs once)
    //var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
     //   this.setZoom(11);
      //  google.maps.event.removeListener(boundsListener);
    //});
}
	
function loadCongregationMap() {

	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.id = 'google-map-script';
	script.src = 'https://maps.googleapis.com/maps/api/js?' +
		'key=' + GoogleAPIKey + '&v=3.exp&' +
		'callback=initialize_congregation_map';
	document.body.appendChild(script);
	
}
