<?php

// *******************************
// * 
// * CONGREGATIONS ADMIN 
// *
// ********************************


// *************************************
// ADMIN MENU ITEMS
// *************************************
add_action( 'admin_menu', 'congregation_admin_panel' );
function congregation_admin_panel() {
	add_menu_page( 'Congregations', 'Congregations', 'manage_options', 'congregation-search/admin.php', 'congregation_admin', 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCAzNiAzNiIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMzYgMzYiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwb2x5Z29uIGZpbGw9IiM5Mzk1OTgiIHBvaW50cz0iMjEuNCwzNiAyOSwzNiAyOSwyMC40IDcsMjAuNCA3LDM2IDE0LjcsMzYgMTQuNywyOC4yIDIxLjQsMjguMiAiLz48cG9seWdvbiBmaWxsPSIjOTM5NTk4IiBwb2ludHM9IjEuNiwxOSAxOCw4LjMgMzQuNCwxOSAiLz48Zz48Zz48bGluZSBmaWxsPSJub25lIiB4MT0iMTgiIHkxPSI5LjIiIHgyPSIxOCIgeTI9IjAuMSIvPjxyZWN0IHg9IjE3IiB5PSIwLjEiIGZpbGw9IiM5Mzk1OTgiIHdpZHRoPSIyIiBoZWlnaHQ9IjkuMSIvPjwvZz48Zz48bGluZSBmaWxsPSJub25lIiB4MT0iMTQuNSIgeTE9IjMuNiIgeDI9IjIxLjUiIHkyPSIzLjYiLz48cmVjdCB4PSIxNC41IiB5PSIyLjYiIGZpbGw9IiM5Mzk1OTgiIHdpZHRoPSI2LjkiIGhlaWdodD0iMi4xIi8+PC9nPjwvZz48L3N2Zz4=', 21 );
}



// *************************************
// ADMIN SCRIPTS AND STYLE SHEETS
// *************************************
add_action( 'admin_enqueue_scripts', 'congregation_admin_scripts' );
function congregation_admin_scripts() {
	$current_screen = get_current_screen();
	if ($current_screen->id == 'toplevel_page_congregation-search/admin' || $current_screen->id == 'congregations_page_congregation-search/settings')
	{
		wp_register_style( 'congregation_search_admin_css', plugins_url( '/congregation-search/css/admin.css' ), false, '1.0.0' );
		wp_enqueue_style( 'congregation_search_admin_css' );
		wp_enqueue_script( 'jQuery' );
		wp_register_script('validation', plugins_url('/congregation-search/js/jquery.validate.min.js') );
		wp_enqueue_script('validation');
		wp_register_script('admin-js', plugins_url('/congregation-search/js/admin.js') );
		wp_enqueue_script('admin-js');
		
		$options = get_option( 'congregation_search_settings' );
		if ($options['congregation_search_setting_APIKey'] != '' && $options['congregation_search_MarkerImage'] != '' )
		{
			?>
			<script type="text/javascript">
				var GoogleAPIKey = '<?php echo $options['congregation_search_setting_APIKey'] ?>';
				var GoogleMapMarker = '<?php echo $options['congregation_search_MarkerImage'] ?>';
			</script>
			<?php
		}
		else if($options['congregation_search_MarkerImage'] == '')
		{
			?>
			<script type="text/javascript">
				var GoogleAPIKey = '<?php echo $options['congregation_search_setting_APIKey'] ?>';
				var GoogleMapMarker = '/wp-content/plugins/congregation-search/images/Chalice-marker.png';
			</script>
			<?php
		}
		
	}
}




// *************************************
// ADMIN MAIN FUNCTION
// *************************************
// main entry function for the congregation search admin
function congregation_admin() {
	?>
<div class="wrap">
	<h2>Disciples Congregational Search
	<a href="#" class="add-new-h2" id="AddNewCongregation">Add New Congregation</a></h2>
	<div id="message" class="updated hidden"><p id="Message"></p><p id="Dismiss">[<a href="javascript:void(0);" id="DismissLink">Dismiss</a>]</p><div style="clear:both;"> </div> </div>
	<?php
	
	// check for Google API
	$options = get_option( 'congregation_search_settings' );
	if ( $options['congregation_search_setting_APIKey'] == '' ) {
		?>
			<div id="message" class="error"><p id="Message">A Google API Key for the Geolocation and the Google Mapping Javascript API (v3) is required. Please aquire a key and provide it in the "Settings" section before continuing.</p><div style="clear:both;"> </div> </div>
		<?php
		return;
	}
	
	// check for an edit request
	$AdminAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	
	if ($AdminAction == 'edit')
	{
		$CongregationQueryStringPIN = intval($_GET['PIN']);
				
		if (is_int($CongregationQueryStringPIN))
		{
			// show the edit form
			edit_congregation($CongregationQueryStringPIN); 
		}
	}
	elseif ($AdminAction == 'save_congregation')
	{
		// save the posted congregational data
		save_congregation_form();
	}
	else
	{
		// show the basic search form
		echo init_congregation_search(true, '', false, 0);
	}
	
	?>

	<div id="CongregationResult" style="display:none;">
		<h3 id="EditFormTitle">Edit Congregation</h3>
		<div class="congregation-edit-form">
			<div class="edit-form" style="width:49%;display:inline-block;">
				<form method="post" action="" id="editCongregationForm">
					<input type="hidden" id="SaveType" value="existing" />
					<input type="hidden" id="PIN" />
					<div class="congregation-pin">
						<label>PIN</label>
						<input type="text" disabled="true" id="CongregationPIN" required/>
					</div>
					<div class="congregation-name">
						<label for="CongregationName">Congregation Name</label>
						<input type="text" id="CongregationName" name="CongregationName" required/>
					</div>
					<div class="congregation-ein">
						<label for="EIN">EIN</label>
						<input type="text" id="EIN" name="EIN" />
					</div>
					<div class="congregation-address1">
						<label for="Address1">Address 1</label>
						<input type="text" id="Address1" name="Address1" required/>
					</div>
					<div class="congregation-address2">
						<label for="Address2">Address 2</label>
						<input type="text" id="Address2"  />
					</div>
					<div class="congregation-city">
						<label for="City">City</label>
						<input type="text" id="City" name="City" required />
					</div>
					<div class="congregation-state">
						<label for="State">State/Province</label>
						<?php congregation_get_state_dropdown('State','','') ?>
					</div>
					<div class="congregation-postalcode">
						<label for="PostalCode">Postal Code</label>
						<input type="text" name="PostalCode" id="PostalCode" required />
					</div>
					<div class="congregation-phone">
						<label for="Phone">Phone</label>
						<input type="text" id="Phone"  />
					</div>
					<div class="congregation-email">
						<label for="Email">Email</label>
						<input type="text" id="Email" name="email" />
					</div>
					<div class="congregation-website">
						<label for="Website">Website</label>
						<input type="text" id="Website"  />
					</div>
					<div class="congregation-region">
						<label for="Region">Region</label>
						<input type="text" id="Region" />
					</div>
					<div><a href="javascript:void(0);" id="GeocodeAddress">Geocode the Address</a></div>
					<div class="congregation-latitude">
						<label for="Latitude">Latitude</label>
						<input type="text" id="Latitude"  />
					</div>
					<div class="congregation-longitude">
						<label for="Longitude">Longitude</label>
						<input type="text" id="Longitude"  />
					</div>
					<div class="congregation-donotautoupdate">
						<label for="DoNotAutoUpdate">Do not auto update</label>
						<input type="checkbox" id="DoNotAutoUpdate" />
						<span>When checked this will prevent automatic data updates from overwriting or deleting all current information about this congregation.</span>
					</div>
					<div class="congregation-savecancel-buttons">
						<a href="#" id="SaveEdit" class="button button-primary">Save Congregation</a>
						<a href="#" id="CancelEdit" class="secondary-button">Cancel</a> | 
						<a href="#" id="DeleteCongregation" class="secondary-button delete-button">Delete</a>
					</div>
				</form>
			</div>
			<div id="map-canvas" style="width:600px; height: 580px; display:inline-block;box-shadow: 3px 5px 10px rgba(0,0,0,0.2);"></div>
		</div>
		<script type="text/javascript">
		
			jQuery(document).ready(function(){ 
				jQuery("#editCongregationForm").validate({
					rules: {
						CongregationPIN: {
							required: true,
							number: true
						},
						CongregationName: "required",
						Address1: "required",
						City: "required",
						State: "required",
						PostalCode: "required",
						Email: {
							email: true
						}
					}
				}); 
			});
		</script> 
	</div>

</div>
	<?php
}




// *************************************
// DISPLAY SEARCH RESULTS
// *************************************
// formats the results from the database query for the admin interface
function display_admin_formatted_congregation_results($congregationsResults)
{
	
	$FinalHTML = '<div id="CongregationList">
<h2>Showing ' . (!$congregationsResults ? '0' : count($congregationsResults) ) . ' results...</h2>
<p>Click on a congregation\'s name to edit.</p>

<table ID="CongregationList-Table" class="wp-list-table widefat fixed congregation-results">
<thead>
	<tr>
		<th scope="col" id="PIN" class="manage-column" style="width:8%;">PIN</th>
		<th scope="col" id="Church_Name" class="manage-column" style="width:40%;">Church Name</th>
		<th scope="col" id="Address" class="manage-column" style="width:40%;">Address</th>
		<th scope="col" id="Last_Update" class="manage-column" style="width:12%;">Last Updated</th>
	</tr>
</thead>
<tfoot>
	<tr>
		<th scope="col" id="PIN" class="manage-column">PIN</th>
		<th scope="col" id="Church_Name" class="manage-column" >Church Name</th>
		<th scope="col" id="Address" class="manage-column">Address</th>
		<th scope="col" id="Last_Update" class="manage-column">Last Updated</th>
	</tr>
</tfoot>
<tbody id="the-list">
	';
	
	$alt = ' alternate';
	if (!$congregationsResults)
	{
		$FinalHTML .= '<tr><td colspan="4"><strong>No congregations found... please try another search.</strong></td></tr>';
	}
	else
	{
		// since our loop needs an array, check to make certain the object
		// is an array and if not, just sned it to the formatting function directly.
		if ( !is_array( $congregationsResults ) ) {
			$FinalHTML .= create_congregation_admin_row( $congregationsResults, ' alternate' );
		}
		else {	
		
			// inserts a class style to create alternating rows
			$alt = ' alternate';
			
			// loop through the congregations
			foreach ( $congregationsResults as $congregation ) { 
				$FinalHTML .= create_congregation_admin_row( $congregation,  $alt );	

				// alternate row styles
				if ( $alt == ' alternate' ) { $alt = ''; }
					else { $alt = ' alternate'; }
		
				}
			}
	}
	
	$FinalHTML .= '</tbody>
</table>
</div>';
	
	return $FinalHTML;
	
}



// *************************************
// DISPLAY SEARCH RESULTS ROW
// *************************************
// formats the row from the database query for the admin interface
function create_congregation_admin_row($congregation, $alt) {
		
	// if the record is locked then we style the row to indcate
	$LockedCongregation = '';
	if ($congregation->DoNotAutoUpdate)
	{
		$LockedCongregation = ' locked-congregation';
	}
	
	$FinalHTML = '<tr id="congregation-' . $congregation->PIN . '" class="congregation-item format-standard' . $alt . $LockedCongregation . '">
		<td><strong>' . $congregation->PIN . '</strong></td>
		<td><strong><a class="SingleCongregation" style="font-size:1.2em;" data-pin="' . $congregation->PIN . '">' . $congregation->Church_Name . '</a></strong></td>
		<td>' . $congregation->Yearbook_Address_Line_1 . ', ' . $congregation->Yearbook_City . ', ' . $congregation->Yearbook_State . ' ' . $congregation->Yearbook_Zip . '</td>
		<td>' . $congregation->Date_Updated . '</td>
	</tr>';

	return $FinalHTML;
			
}
