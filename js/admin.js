// JavaScript Document

// **************
// AJAX FUNCTIONS
// **************

jQuery(document).ready( function($)
	{
	
		// action when clicking on a congregation name in the results
		$(".SingleCongregation").click( function() {
						
			var CongID = jQuery(this).attr("data-pin");
				
			var data = 	{
					'action': 'get_congregation',
					'PIN': CongID,
					'dataType': 'json'
				};
				
			$.post(ajaxurl, data, function(response)  {
					var parsedData = $.parseJSON(response);
					if(parsedData.status == "success") {
						$('#editCongregationForm #CongregationPIN').val(parsedData["congregation"]["PIN"]);
						$('#editCongregationForm #CongregationName').val(parsedData["congregation"]["CongregationName"]);
						$('#editCongregationForm #EIN').val(parsedData["congregation"]["EIN"]);
						$('#editCongregationForm #Address1').val(parsedData["congregation"]["Address1"]);
						$('#editCongregationForm #Address2').val(parsedData["congregation"]["Address2"]);
						$('#editCongregationForm #City').val(parsedData["congregation"]["City"]);
						$('#editCongregationForm #State').val(parsedData["congregation"]["State"]);
						$('#editCongregationForm #PostalCode').val(parsedData["congregation"]["PostalCode"]);
						$('#editCongregationForm #Phone').val(parsedData["congregation"]["Phone"]);
						$('#editCongregationForm #Email').val(parsedData["congregation"]["Email"]);
						$('#editCongregationForm #Website').val(parsedData["congregation"]["Website"]);
						$('#editCongregationForm #Region').val(parsedData["congregation"]["Region"]);
						$('#editCongregationForm #Latitude').val(parsedData["congregation"]["Latitude"]);
						$('#editCongregationForm #Longitude').val(parsedData["congregation"]["Longitude"]);
						if (parsedData["congregation"]["DoNotAutoUpdate"] == "1") {
							$('#editCongregationForm #DoNotAutoUpdate').prop('checked', true);
						}
						
						// show the form
						switchToForm(true);

					}
					else
					{
						showMessage('error','Failed to get congregational information!',false);
					}
				});
		});
				
		// cancel action on edit form
		$('#CancelEdit').click( function () {
			switchToList(true);
		});
		
		// save action on edit form
		$('#SaveEdit').click( function() { 
						
			if (jQuery("#editCongregationForm").valid())
			{		
				var saveType = 'existing';
				
				if ($('#SaveType').val() == "new")
				{
					saveType = 'new';
				}
				
				AutoUpdateChecked = $('#editCongregationForm #DoNotAutoUpdate').prop('checked') ? '1' : '0';
				
				// setup json data to submit
				var data = {
					'action': 'save_congregation',
					'dataType': 'json',
					'savetype': saveType,
					'PIN': $('#CongregationPIN').val(),
					'CongregationName': $('#CongregationName').val(),
					'EIN': $('#EIN').val(),
					'Address1': $('#Address1').val(),
					'Address2': $('#Address2').val(),
					'City': $('#City').val(),
					'State': $('#State').val(),
					'PostalCode': $('#PostalCode').val(),
					'Phone': $('#Phone').val(),
					'Email': $('#Email').val(),
					'Website': $('#Website').val(),
					'Region': $('#Region').val(),
					'Latitude': $('#Latitude').val(),
					'Longitude': $('#Longitude').val(),
					'DoNotAutoUpdate': AutoUpdateChecked
				};
									
				$.post(ajaxurl, data, function(response)  {
					
						var parsedData = $.parseJSON(response);
	
						if (parsedData.result === false)
						{
							showMessage('error','An error occured when attempting to save the congregation\'s information.',false);
						}
						else
						{
							showMessage('updated','The congregation was saved.',true);
							switchToList(true);
						}
						
				});
			}
			
		});
		
		// deletes a congregation 
		$('#DeleteCongregation').click( function () {
			
			var congregationPIN = $('#CongregationPIN').val();
			
			if (confirm('Are you certain you want to delete ' + $('#CongregationName').val() + ' from the online listing?','Confirm Delete'))
			{
				
				var data = { 
					'action': 'delete_congregation', 
					'dataType': 'json', 
					'PIN': congregationPIN 
				}
				
				$.post(ajaxurl, data, function(response) {
					
					var parsedData = $.parseJSON(response);
					if (parsedData.result === false || parsedData.result == 0)
					{
						showMessage('error','An error occured when attempting to delete the congregation.',false);
					}
					else
					{
						showMessage('updated', 'The congregation was deleted.', true);
						switchToList(true);
						
						// delete the entry from the results list
						// this saves us a call back to the DB and to 
						// the Google geocoding service
						$('#congregation-' + congregationPIN).css('opacity','0.2');
						$('#congregation-' + congregationPIN + ' td:eq(1)').html('<strong>DELETED</strong>');
					}
					
				});
				
			}
						
		});
		
		// shows a blank congregation form
		$('#AddNewCongregation').click( function () {
			
			// show a blank form
			switchToForm(false);
			
			$('#SaveType').val('new');
			$('#CongregationPIN').removeAttr('disabled');
			$('#DeleteCongregation').css('display','none');
			$('#EditFormTitle').html('Add Congregation');
			
		});
		
		// hits the Google Geocoding API and retrieve a Latitude and
		// longitude for the address in the form
		$('#GeocodeAddress').click( function () {
			
			// query Google geocoding API and get Lat/Long data.			
			if ($('#Address1').val() != '' && $('#City').val() != '' && $('#State').val() != '')
			{
				
				var geocodingURL = 'https://maps.googleapis.com/maps/api/geocode/json';
				var addressParams = encodeURIComponent($('#Address1').val() + ', ' + $('#City').val() + ', ' + $('#State').val() + ', ' + $('#PostalCode').val());

				// send a get request for the geocoding
				// response will already be JSON parsed
				$.get(geocodingURL + '?address=' + addressParams, {}, function(response) {
										
					if (response.status.toLowerCase() == 'ok')
					{
						// assign latitude and longitude
						$('#Latitude').val(response.results[0].geometry.location.lat);
						$('#Longitude').val(response.results[0].geometry.location.lng);
						
						// load the map
						loadAdminMap();
					}
					else
					{
						showMessage('error','Geocoding failed: ' + response.status, false);
					}
					
				});							
			
			}
			else
			{
				showMessage('error','You must provide an address before a Geocode request can be made.',false);
			}
			
		});
		
		// closes the message box
		$('#DismissLink').click( function() {
			hideMessage();
		});
		
		
		// verifies that the URL provided in the Settings page
		// for the update file is valid.
		$('#VerifyUpdateFileURL').click( function() {
			
			var UpdateFilePath = $('#congregation_search_UpdateFileLocation').val();
			
			if (UpdateFilePath != '')
			{

				$('#CheckUpdateFileLocation #Result').html('<img src="/wp-admin/images/wpspin_light-2x.gif" width="16" height="16" /> <em>Checking... please wait...</em>');
				$('#VerifyUpdateFileURL').hide();

				data = {
					'action': 'verify_congregation_update_file',
					'dataType': 'json',
					'UpdateFilePath': UpdateFilePath
				}

				// we post an ajax request to the server and let the 
				// server confirm it can see the file
				$.post(ajaxurl, data, function(response) {

					var parsedData = $.parseJSON(response);

					if (parsedData.result === true && ( parsedData.contenttype == 'text/csv' || parsedData.contenttype == 'application\/vnd.ms-excel' ) )
					{
						$('#CheckUpdateFileLocation #Result').html('<strong style="color:#006600;">Valid!</strong>');
					}
					else if (parsedData.result === true && parsedData.contenttype != 'text/csv' && parsed.Data.contenttype != 'application/vnd.ms-excel')
					{
						$('#CheckUpdateFileLocation #Result').html('<strong style="color:#990000;">URL is valid but does not appear to be a CSV file. Reported type was &quot;' + parsedData.contenttype + '&quot;</strong>');
					}
					else
					{
						$('#CheckUpdateFileLocation #Result').html('<strong style="color:#990000;">URL Not Valid.</strong>');
					}
					
				});
					
			}
			else
			{
				$('#CheckUpdateFileLocation #Result').html('<em>No URL Provided - please enter a URL and try again.</em><br/><a href="javascript:void(0);" ID="VerifyUpdateFileURL">Verify Path</a>');
				$('#VerifyUpdateFileURL').show();

			}
			
		
		});
		
		// detects if the content of the Update File URL settings field
		// has changed and shows the verify link
		$('#congregation_search_UpdateFileLocation').on("change", function() {
			
			$('#CheckUpdateFileLocation #Result').html('');
			$('#VerifyUpdateFileURL').show();
		
		});
		
		// starts the update service on demand and shows the result
		$('#StartUpdateNow #UpdateLink').click( function() {
			
			$('#StartUpdateNow #UpdateStatus').html('<img src="/wp-admin/images/wpspin_light-2x.gif" width="16" height="16" /> <em>Performing update... please wait...</em>');
			$('#StartUpdateNow #UpdateLink').hide();
			
			var data = {
				'action': 'begin_manual_congregation_update',
				'dataType': 'json'
			}
						
			$.post(ajaxurl, data, function(response) {
			
				var parsedData = $.parseJSON(response);
														
				if (parsedData.result === true)
				{
					$('#StartUpdateNow #UpdateStatus').html('<strong style="color:#006600;">' + parsedData.message + '</strong>');
				}
				else
				{
					$('#StartUpdateNow #UpdateStatus').html('<strong style="color:#990000;">' + parsedData.message + '</strong>');
				}
			
			});
			
		});
	  
	  	// shows the log view
		$('#ShowUpdateLog').click( function () {
			
			$('#LogViewer').show(500);
			$('#ShowUpdateLog').hide(250);
			getLogData($('#LogHighDate').val(), $('#LogLowDate').val(), $('#LogType').val());
			
		});
		
		$('#LogViewerForm #LogButton').click( function () {
			getLogData($('#LogHighDate').val(), $('#LogLowDate').val(), $('#LogType').val());
		});
		
		// hides/shows the distance field
		$('#congregation-searchtype').change( function () {
			if ($('#congregation-searchtype').val() == 'location') {
				$('#congregation-distance').show(500);
			}
			else {
				$('#congregation-distance').hide(500);
			}
		});
	  
});

function getLogData(highDate, lowDate, logType) {
	
	jQuery('#LogViewerWindow').html('<table><tr><td><strong>fetching data... please wait...</strong></td></tr></table>');
	
	var data = {
		'action' : 'get_congregation_search_log',
		'dataType' : 'json',
		'HighDate' : highDate,
		'LowDate' : lowDate,
		'LogType' : logType
	};
	
	// post data to ajax
	jQuery.post(ajaxurl, data, function(response) {
		
		var parsedData = jQuery.parseJSON(response);
				
		if (parsedData.result === true)
		{
			var finalHTML = '<table><tbody>';

			for(var x=0, len = parsedData.data.length; x<len; ++x)
			{
				var jsonRow = parsedData.data[x];
				finalHTML = finalHTML + '<tr><td>' + jsonRow.LogDate + '</td><td>' + jsonRow.EntryType + '</td><td>' + jsonRow.Message + '</td></tr>';
			}
			finalHTML = finalHTML + '</tbody></table>';
						
			jQuery('#LogViewerWindow').html(finalHTML);
			
		}
		else 
		{
			jQuery('#LogViewerWindow').html('<p style="margin-left:20px;">No log was found.</p>');
		}
		
	});
	
}

// ** DISPLAY MESSAGE **//

function showMessage(messageType, MessageText, autoFade) {
	jQuery('html, body').animate({scrollTop : 0},400);
	jQuery('#message p#Message').html(MessageText);
	jQuery('#message').addClass(messageType).fadeIn(1000);
	if (autoFade)
	{
		jQuery('#message').delay(5000).fadeOut(1000);
	}
}

function hideMessage()
{
	jQuery('#message').removeClass().removeAttr('style').addClass('hidden');
	jQuery('#message p#Message').html('');
}

// ** FORM FUNCTIONS **//
function clearForm()
{
	jQuery('#editCongregationForm #CongregationPIN').val('');
	jQuery('#editCongregationForm #CongregationName').val('');
	jQuery('#editCongregationForm #EIN').val('');
	jQuery('#editCongregationForm #Address1').val('');
	jQuery('#editCongregationForm #Address2').val('');
	jQuery('#editCongregationForm #City').val('');
	jQuery('#editCongregationForm #State').val('');
	jQuery('#editCongregationForm #PostalCode').val('');
	jQuery('#editCongregationForm #Phone').val('');
	jQuery('#editCongregationForm #Email').val('');
	jQuery('#editCongregationForm #Website').val('');
	jQuery('#editCongregationForm #Region').val('');
	jQuery('#editCongregationForm #Latitude').val('');
	jQuery('#editCongregationForm #Longitude').val('');
	jQuery('#editCongregationForm #DoNotAutoUpdate').prop('checked', false);
	
	jQuery('#SaveType').val('existing');
	jQuery('#CongregationPIN').attr('disabled','true');
	jQuery('#DeleteCongregation').css('display','inline-block');
	jQuery('#EditFormTitle').html('Edit Congregation');
	jQuery('#map-canvas').css('display','none');

}

// switch from the form panel to the results panel
function switchToList(ClearTheForm)
{
	jQuery('html, body').animate({scrollTop : 0},400);
	jQuery('#CongregationResult').fadeOut(500);
	if (ClearTheForm)
	{
		clearForm(); // clear the form so it's clean for the next use
	}
	jQuery('#CongregationList').fadeIn(500);
}

// switch from the restuls panel to the form view
function switchToForm(LoadGoogleMap)
{
	jQuery('#CongregationList').fadeOut(500);
	jQuery('html, body').animate({scrollTop : 0},400);
	if (LoadGoogleMap)
	{
		loadAdminMap(); // load the google map
	}
	jQuery('#CongregationResult').fadeIn(500);
}

// ********************
// GOOGLE MAP SCRIPTING
// ********************

var map; // global Google Map objects.
var mapOptions;
var churchLocation;
var marker;
var infowindow;

// google maps functions
function initialize_admin_map() {
	
	jQuery('#map-canvas').css('display','inline-block');
	
	var churchLocation = new google.maps.LatLng(jQuery('#Latitude').val(), jQuery('#Longitude').val());
	var mapOptions = { center: churchLocation, zoom: 15 };
	var contentString = '<div id="content">'+
		'<div id="siteNotice">'+
		'</div>'+
		'<h3 id="firstHeading" class="firstHeading">' + jQuery('#CongregationName').val() + '</h3>'+
		'<div id="bodyContent">'+
		'<p>'+ jQuery('#Address1').val() + '<br/>' + jQuery('#City').val() + ', ' + jQuery('#State').val() + ' ' + jQuery('#PostalCode').val() + '<br/>' + jQuery('#Phone').val() + '<br/>' + jQuery('#Website').val() + '</p>'+
		'</div>'+
		'</div>';
	
	// see if a map is loaded - if so, use it,
	// otherwise create a new map instance
	if (!map)
	{
		// create a new map
		map = new google.maps.Map(document.getElementById('map-canvas'),mapOptions);
	}
	else
	{
		// clear current marker and info window and use the existing map....
		map.setCenter(churchLocation); // set new center location
		marker.setMap(null);
		infowindow.setMap(null);
		
	}

	infowindow = new google.maps.InfoWindow({
		content: contentString
	});

	marker = new google.maps.Marker({
		position: churchLocation,
		map: map,
		title: jQuery('#CongregationName').val(),
		animation: google.maps.Animation.DROP,
		icon: GoogleMapMarker,
		draggable: true
	});
		
	google.maps.event.addListener(marker, 'click', function() {
		infowindow.open(map, marker);
	});

	// when the marker is dragged, change the lat/long in the form.
	google.maps.event.addListener(marker, 'position_changed', function() {
		jQuery('#Latitude').val(marker.position.lat().toFixed(6));
		jQuery('#Longitude').val(marker.position.lng().toFixed(6));
	});
}
	
function loadAdminMap() {
	if (jQuery('#google-map-script').length == 0)
	{
		var script = document.createElement('script');
		script.type = 'text/javascript';
		script.id = 'google-map-script';
		script.src = 'https://maps.googleapis.com/maps/api/js?' +
			'key=' + GoogleAPIKey + '&v=3.exp&' +
			'callback=initialize_admin_map';
		document.body.appendChild(script);
	}
	else
	{		
		// reset the currently loaded map
		initialize_admin_map();
	}
}
