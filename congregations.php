<?php
   /*
   Plugin Name: Disciples of Christ Congregation Search
   Plugin URI: http://disciples.org
   Description: A plugin to search for Disciples of Christ Congregations.
   Version: 1.0.0
   Author: Christopher G. Higgins, Dir. of Digital Media and General Assembly Production
   Author URI: http://disciples.org
   License: GPL2
   
   VERISON HISTORY:
   1.0.0 - Initial Launch
   1.0.1 - Fixed "congregation_search_form" shortcode problem
   
   */
   
// Some Initial setup variables.
$CONGREGATION_SEARCH_ADMIN_PAGE = '?page=congregation-search/admin.php';
$add_my_script = false;
 
require_once 'include/database.php';
require_once 'include/classes.php';
require_once 'include/geocoding.php';
require_once 'include/updateservice.php';
require_once 'include/ajax.php';
require_once 'congregations-admin.php';
require_once 'settings.php';



// ******************
// *** ACTIVATION ***
// ******************
register_activation_hook(__FILE__, 'congregation_search_activate');
function congregation_search_activate() {
	
	// set the initial update configuration 
	register_setting( 'congregation_search_settings', 'congregation_search_settings' );

	$config_options = array();
	$config_options['congregation_search_setting_APIKey'] = '';
	$config_options['congregation_search_EnableUpdateService'] = false;
	$config_options['congregation_search_QueryLength'] = '-3 months';
	$config_options['congregation_search_UpdateFileLocation'] = plugins_url( 'congregation-search/data/initialdata.csv');
	$config_options['congregation_search_MarkerImage'] = plugins_url( 'congregation-search/images/Chalice-marker.png' );
	$config_options['congregation_search_UpdateFileColumns'] = 'PIN
Yearbook_Year
CongregationName
EIN
Address1
Address2
PostalCode
City
State
Mailing1
Mailing2
MailingZip
MailingCity
MailingState
County
Country
Phone
Fax
Email
Website
Region
District
UpdateCode
MinisterEmail
Code
LastUpdated
Latitude
Longitude
GeocodeConfidence';
		$config_options['congregation_search_UpdateFrequency'] = 'weekly';
	
	add_option('congregation_search_settings', $config_options);
	
	// checks to see if the acongregation data tables was created
	// and creates one if it hasn't
	create_congregation_search_table();

}



// **************************
// REGISTER STYLE AND SCRIPTS
// **************************
// Thanks to Scribu for demonstrating
// the Jedi Knight's way of doing this so the scripts are only
// loaded on pages using the shortcode
add_action( 'init', 'register_congregation_search_scripts' );
add_action( 'wp_footer', 'print_congregation_scripts');
function register_congregation_search_scripts() {
	wp_register_style( 'congregation-search', plugins_url( 'congregation-search/css/style.css' ) );
	wp_enqueue_style( 'congregation-search' );
	wp_register_script('congregation-js', plugins_url('/congregation-search/js/congregations.js') );
		
	// register google map assets
	wp_register_script('markerclusterer-js', plugins_url('/congregation-search/js/markercluster.js') );


}



// *************************************
// ADD GOOGLE API AS GLOBAL JAVASCRIPT VALUES
// *************************************
add_action( 'wp_head', 'add_Congregation_Search_Google_API');
function add_Congregation_Search_Google_API() {

	$options = get_option( 'congregation_search_settings' );

	?>
	<script type="text/javascript">
		var GoogleAPIKey = '<?php echo $options['congregation_search_setting_APIKey'] ?>';
		var GoogleMapMarker = '<?php echo $options['congregation_search_MarkerImage'] ?>';
	</script>
	<?php
}



// *************************************
// ENQUEUE SCRIPTS WHEN PLUGIN IS LOADED
// *************************************
function print_congregation_scripts() {
	global $add_my_script;

	if ( ! $add_my_script )
		return;

	wp_enqueue_script('congregation-js');
	wp_enqueue_script('markerclusterer-js');
}




// ****************************
// SEARCH FORM SHORTCODE
// ****************************
// This places a simple search form on a page or widget
// and redirects the search to a specified page.
add_shortcode("congregation_search_form","Congregation_Search_Form_SC");
function Congregation_Search_Form_SC( $atts, $content ) {
		
	global $add_my_script;
	$add_my_script = true;
	
	$attribs = shortcode_atts( array (
		'results_page' => '',			// the URL to the results page
		'show_distance' => false, 		// indicates if the distance is to be displayed
		'css_prefix'	 => ''				// adds a prefix to teh CSS classes in case custom styling is needed for different instances.
	), $atts );
	
	$results_page = isset( $attribs['results_page'] ) ? $attribs['results_page'] : '';
	$show_distance = isset( $attribs['show_distance'] ) ? $attribs['show_distance'] : "false";
	$css_prefix = isset( $attribs['css_prefix'] ) ? $attribs['css_prefix'] : '';
	
	// setup the form.
	$form_template = '<div class="' . $css_prefix . 'congregation-search-form"><form action="' . $results_page . '" method="post">
			<input class="' . $css_prefix . 'congsearch-searchquery" type="text" name="searchQuery" />';
		
	// if distance is supposed to be show, create a dropdown, else just a hidden field
	if ($show_distance == "true")
	{		
		$form_template .= '<select class="' . $css_prefix . 'congsearch-searchdistance" name="searchDistance">
			<option value="25" selected>25 miles</option>
			<option value="50">50 miles</option>
			<option value="75">75 miles</option>
			<option value="100">100 miles</option>
			<option value="200">200 miles</option>
		</select>';
	}
	else
	{
		$form_template .= '<input type="hidden" name="searchDistance" value="25" />';
	}
	
	$form_template .= '<input type="submit" class="' . $css_prefix . 'congsearch-searchbutton" value="Search" />
		</form></div>';
	
	// show the form
	return $form_template;
	
}



// ****************************
// MAIN SEARCH ENTRY POINT
// ****************************
// entrypoint function for the congregational search
// $content = template to use in displaying the results
add_shortcode("congregation_search","Congregation_Search_fn");
function Congregation_Search_fn( $atts, $content )
{
		
	global $add_my_script;
	$add_my_script = true;

	$attribs = shortcode_atts( array (
		'usemap' => 'true',			// if the map is to be shown
		'paging_limit' => 0			// the result limit of each page. 0 turns paging off
	), $atts );
		
	$UseMap = true;
	$PagingLimit = 0;
				
	if ( isset( $attribs['usemap'] ) ){
		$UseMap = $attribs['usemap'];
	}

	if ( isset( $attribs['paging_limit'] ) ) {
		$PagingLimit = intval($attribs['paging_limit']);
	}

	$results_template = '';
	// Check to make certain the required tokens are provided

	if (strpos($content,'{CONGREGATION_NAME}') !== false && strpos($content,'{LOOPTEMPLATE}') !== false && strpos($content,'{/LOOPTEMPLATE}') !== false) {
		
		// use the shortcode content as the template
		$results_template = $content;
		
	}
	else {
		
		// use the default template
		$results_template = 	'<h2>Found {RESULTS_COUNT} congregations...</h2>
		<table id="congregation-results"><tbody>
		{LOOPTEMPLATE}<tr>
<td class="congregation-name">{CONGREGATION_NAME}</td>
<td class="congregation-address">{ADDRESS1}<br/>{CITY}, {STATE} {POSTALCODE}<br/><a href="http://{WEBSITE}" target="_blank">{WEBSITE}</a></td>
<td class="congregation-distance">{DISTANCE}</td>
</tr>{/LOOPTEMPLATE}
		</tbody></table>';

	}
	
	// initialize search
	$ReturnResult = init_congregation_search(false, $results_template, $UseMap, $PagingLimit);
	return $ReturnResult;
		
}




// ****************************
// DISPLAY THE SEARCH FORM
// ****************************
// shows the search form. When $IsAdmin is true
// addtional style classes are added. This form always posts
// back to the URI in which the shortcode is used.
function show_congregation_search_form($IsAdmin)
{
	
	$InitialDistance = get_congregation_search_distance();
	
	$search_form_html = '<div class="congregation_search_form'. ($IsAdmin ? ' admin-search' : '') . '"><form action= "' . ( !$IsAdmin ? parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) : $_SERVER['REQUEST_URI'] ) . '" method="post" id="congregation-search-form">
		<input type="text" name="searchQuery" value="' . get_congregation_search_query() . '" size="20" id="congregation-query">';
		
	$searchType = 'location';
		
	// if admin, do a search type drop down
	if ($IsAdmin) {
		
		$searchType = get_congregation_search_type();
				
		$search_form_html .= '<select name="searchType" id="congregation-searchtype">
			<option value="location"' . ($searchType == 'location' ? ' selected' : '') . '>Location</option>
			<option value="pin"' . ($searchType == 'pin' ? ' selected' : '') . '>Congregation PIN</option>
			<option value="name"' . ($searchType == 'name' ? ' selected' : '') . '>Congregation Name</option>
			<option value="city"' . ($searchType == 'city' ? ' selected' : '') . '>City</option>
		</select>';
		
	}
	
	$search_form_html .= '<select name="searchDistance" id="congregation-distance"'. ($searchType != 'location' ? ' style="display:none;"' : '' ) . '>
			<option value="25"' . ($InitialDistance == 25 ? ' selected' : '') . '>25 miles</option>
			<option value="50"' . ($InitialDistance == 50 ? ' selected' : '') . '>50 miles</option>
			<option value="75"' . ($InitialDistance == 75 ? ' selected' : '') . '>75 miles</option>
			<option value="100"' . ($InitialDistance == 100 ? ' selected' : '') . '>100 miles</option>
			<option value="200"' . ($InitialDistance == 200 ? ' selected' : '') . '>200 miles</option>
		</select><input type="submit" name="Submit" class="button" value="Search" id="congregation-submit" /></form></div>';
		
	return $search_form_html;
	
}



// ****************************
// FORMAT THE RESULTS
// ****************************
// Displays the results from the database query.
function display_formatted_congregation_results($congregationsResults, $resultsTemplate, $useMap, $PagingLimit)
{
		
	$FinalResults = '';
		
	// if nothing was found then display an error
	if ( !$congregationsResults )
	{
		return '<p class="congregation-search-error">No results were found for "' . get_congregation_search_query() . '" within ' . get_congregation_search_distance() . ' miles. Please try searching in another area.</p>';
	}
		
	global $wp_query, $pagename;
		
	if ($useMap)
	{
		// setup the javascript marker data
		$marker_data = '<script type="text/javascript">
		var congregationmarkers = [
		';
		$info_window_data = 'var congregationInfoWindow = [
		';
		$leading_comma = false;
		
		$resultsTemplate = str_replace('{MAP}', '<div id="map-canvas"></div>', $resultsTemplate);
		
	}
	else
	{
		// remove the token in case we explicitly state the map should not be used.
		$resultsTemplate = str_replace('{MAP}', '', $resultsTemplate);
	}
	
	
	// setup the template
	$temp_TemplateArrayHeader = explode('{LOOPTEMPLATE}', $resultsTemplate);
	$templateHeader = $temp_TemplateArrayHeader[0];
	$temp_TemplateArrayFooter = explode('{/LOOPTEMPLATE}', $temp_TemplateArrayHeader[1]);
	$templateFooter = $temp_TemplateArrayFooter[1];
	$templateLoop = $temp_TemplateArrayFooter[0];	

	// get the total results count
	$TotalResults = count($congregationsResults);
	
	
	// check for and filter paged results
	$PagingOffset = 0;
	$CurrentPage = 1;
				
	if ( $PagingLimit > 0 ) {
		
		// if the page is set in the querystring, grab it
		if ( isset( $_GET['pg'] ) ) {
			$CurrentPage = (int) $_GET['pg'];
		}
		
		$PagingOffset = ( $CurrentPage * $PagingLimit ) - $PagingLimit;								
	
		// filter paged results, if needed
		$congregationsResults = get_paged_congregation_results($congregationsResults, $PagingLimit, $PagingOffset);
	}
	
	// print the head, replacing the count token with a numeric value
	$FinalResults .= str_replace('{SHOWING_COUNT}', ( ( $PagingOffset + 1 ) . " - " . ( $PagingOffset + count($congregationsResults) ) ), str_replace('{RESULTS_COUNT}', $TotalResults, $templateHeader) );

	// initiate the results loop
	foreach ($congregationsResults as $congregation)
	{ 

		// print result row
		$FinalResults .= format_congregation_result($templateLoop, $congregation);
		
		// if we're using maps...
		if ($useMap)
		{
			// create the javascript for the map
			if ($leading_comma)
			{ 
				$marker_data .= ",\n"; 
				$info_window_data .= ",\n";
			}
			else {
				$leading_comma = true;
			}
			
			$marker_data .= "['" . str_replace("'", "\'", $congregation->Church_Name) . ', ' . str_replace("'", "\'", $congregation->Yearbook_City) . ', ' . $congregation->Yearbook_State . "'," . $congregation->Latitude . ', ' . $congregation->Longitude . "]";
			$info_window_data .= '[\'<div class=\"info-window\"><h3>' . str_replace("'", "&apos;", $congregation->Church_Name) . '</h3><p>' .  
				str_replace("'", "&apos;", $congregation->Yearbook_Address_Line_1) . '<br/>' . 
				str_replace("'", "&apos;", $congregation->Yearbook_City) . ', ' . $congregation->Yearbook_State . ' ' .  $congregation->Yearbook_Zip . '<br/>' . 
				$congregation->Phone . ($congregation->Web_Address != '' ? '<br/><a href="' . (strpos($congregation->Web_Address, 'http://') === false ? 'http://' : '') . $congregation->Web_Address . '" target="_blank">' . $congregation->Web_Address . '</a>' : '') .
				'</p></div>\']';
				
		}
	}
	
	// print the footer
	$FinalResults .= $templateFooter;
		
	$FinalResults = congregation_santize_line_breaks( $FinalResults );
		
	// if were using a map, print the javascript
	// and setup the map canvas
	if ($useMap)
	{
		$marker_data .= "];
		" . $info_window_data . "];
		</script>";
		
		// write the javascript
		$FinalResults .= $marker_data;
		
		$FinalResults .= '	
			<script type="text/javascript">
				jQuery(document).ready( function () {
					loadCongregationMap() 
				});
			</script>
			';
	} // endif useMap
	
	// create the paging section
	if ( $PagingLimit > 0 ) {
	
		$QueryFormat = '?q=' . urlencode( get_congregation_search_query() ) . '&d=' . get_congregation_search_distance() . '&pg=%#%';
		$FinalResults .= create_congregation_paging_links( $TotalResults, $CurrentPage, $PagingLimit, $QueryFormat );
	
	}
		
	return $FinalResults;
	
}



// ****************************
// PAGING GENERATION
// ****************************
// Generates the paging links for the results listing.
function create_congregation_paging_links($TotalResults, $CurrentPage, $PagingLimit, $Query)
{
	
	// first see if we need pages at all.
	// if not, exit without printing anything.
	if ( $TotalResults <= $PagingLimit )
		return;
	
	$BaseURL = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
	
	$FinalHTML = '<div id="congregation-paging-links">'; // start the template
	
	$TotalLinks = ceil( $TotalResults / $PagingLimit );
	
	if ($CurrentPage == 1) {
		// if it's the first page then create a disabled "Previous" link
		$FinalHTML .= '<a href="javscript:void(0);" class="disabled-paging-link">&laquo; Previous</a> ';
	}
	else {
		$FinalHTML .= '<a href="' . $BaseURL . str_replace('%#%', $CurrentPage-1, $Query) . '" class="page-link">&laquo; Previous</a> ';
	}
	
	// now loop through the pages and create appropriate links
	for ($x = 1; $x <= $TotalLinks; $x++)
	{
		$activeCSS = '';
		if ($x == $CurrentPage)
			$activeCSS = ' current-page';
			
		$LinkHTML = '<a href="' . $BaseURL . str_replace('%#%', $x, $Query) . '" class="page-link' . $activeCSS .'">' . $x . '</a> ';
		
		$FinalHTML .= $LinkHTML;
		
	}
		
	// create a next link
	if ($CurrentPage == $TotalLinks) {
		// if it's the last page then create a disabled "Next" link button.
		$FinalHTML .= '<a href="javscript:void(0);" class="disabled-paging-link">Next &raquo;</a> ';
	}
	else {
		$FinalHTML .= '<a href="' . $BaseURL . str_replace('%#%', $CurrentPage+1, $Query) . '" class="page-link">Next &raquo;</a> ';
	}
	
	
	$FinalHTML .= '</div>'; //done
	
	return $FinalHTML;
}



// ****************************
// TEMPLATE FORMATTING
// ****************************
// replaces the tmeplate tokens with the appropriate content. 
function format_congregation_result($resultTemplate, $congregation)
{
	
	$TokenArray = array (
		'{CONGREGATION_NAME}',
		'{EIN}',
		'{ADDRESS1}',
		'{ADDRESS2}',
		'{CITY}',
		'{STATE}',
		'{POSTALCODE}',
		'{PHONE}',
		'{EMAIL}',
		'{WEBSITE}',
		'{REGION}',
		'{DATE_UPDATED}',
		'{LATITUDE}',
		'{LONGITUDE}',
		'{PIN}',
		'{DISTANCE}'
	);
	
	$CongregationResultsArray = array (
		$congregation->Church_Name,
		$congregation->EIN,
		$congregation->Yearbook_Address_Line_1,
		$congregation->Yearbook_Address_Line_2,
		$congregation->Yearbook_City,
		$congregation->Yearbook_State,
		$congregation->Yearbook_Zip,
		$congregation->Phone,
		$congregation->Email_Address,
		$congregation->Web_Address,
		$congregation->Region,
		$congregation->Latitude,
		$congregation->Longitude,
		$congregation->Date_Updated,
		$congregation->PIN,
		round($congregation->distance,1)
	);
	
	// replace the tokens
	$ResultRow = str_replace($TokenArray, $CongregationResultsArray, $resultTemplate);
	
	// return the result
	return $ResultRow;
	
}




// ****************************
// MAIN SEARCH FUNCTION
// ****************************
// this is the main search function entry point. This will
// catch postbacks and display results, if needed.
function init_congregation_search($isAdmin, $resultsTemplate, $useMap, $PagingLimit)
{
	
	$FinalHTML = '';
	
	// show the search form
	$FinalHTML .= show_congregation_search_form($isAdmin);
	
	// determine if there is a query
	if ( get_congregation_search_query() != '' )
	{
		// get the search parameters
		$searchQuery = get_congregation_search_query();
		$searchDistance = get_congregation_search_distance();
		$searchType = 'location';
		if ($isAdmin) {
			$searchType = get_congregation_search_type();
		}
		
		// keep showing the form, pre-popped with search query
		
		if ($searchQuery)
		{

			$congregationResults = null;
			
			if ($searchType == 'pin' && $isAdmin) {
				$congregationResults = get_congregation_from_db($searchQuery);
			} elseif ($searchType == 'name' && $isAdmin) {
				$congregationResults = get_congregation_by_name($searchQuery);
			} elseif ($searchType == 'city' && $isAdmin) {
				$congregationResults = get_congregation_by_city($searchQuery);
			} else {
				$congregationResults = get_congregations_by_location_search($searchQuery, $searchDistance);
			}

				
			if ($isAdmin)
			{
				$FinalHTML .= display_admin_formatted_congregation_results($congregationResults); //congregations-admin.php
			}
			else
			{
				$FinalHTML .= display_formatted_congregation_results($congregationResults, $resultsTemplate, $useMap, $PagingLimit);
			}
			
		}		
		
	}
	
	return $FinalHTML;

}


// ****************************
// GET CONGREGATION RESULTS FROM LOCATION
// ****************************
function get_congregations_by_location_search($searchQuery, $searchDistance) {

	$options = get_option('congregation_search_settings');
			
	// first see if the search query already exisit in the history
	// this is to avoid hitting the Google geocoding API, thus increasing performance
	$QueryItem = new CongregationSearchQueryItem();
	$QueryItem->get_query($searchQuery);
	
	$Latitude = 0.0;
	$Longitude = 0.0;
				
	// if the query is not new and the caching is up to date...
	if (!$QueryItem->IsNew && get_congregation_cache_backdate() <= $QueryItem->LastUpdated)
	{			
		
		// use the lat/long info from the query
		$Latitude = $QueryItem->Latitude;
		$Longitude = $QueryItem->Longitude;
		
		// increment the usage count
		$QueryItem->increment_usage();
	}
	else
	{
													
		// hit the Geocoding service for lat/long information
		$searchResults = get_congregation_city_state_latlong($searchQuery);
		
		if ($searchResults)
		{
			
			$Latitude = $searchResults['location']['lat'];
			$Longitude = $searchResults['location']['long'];
			
			// update the query history
			$QueryItem->Latitude = $Latitude;
			$QueryItem->Longitude = $Longitude;
			$QueryItem->LastUpdated = date('Y-m-d H:i:s');
			
			if ($QueryItem->IsNew) {
				$QueryItem->save_query();
			}
			else
			{
				$QueryItem->increment_usage(); // also saves the query
			}
								
		}
		
	}
	
	$congregationResults = null;

	if ($Latitude != 0.0 && $Longitude != 0.0)
	{					
		// get congregations that match the results								
		$congregationResults =  get_congregations_by_latlong(
			$Latitude, 
			$Longitude, 
			$searchDistance
		);
		
	}	
	
	return $congregationResults;
	
}



// ****************************
// REQUEST THE MAIN SEARCH QUERY
// ****************************
// returns the search query either from a 
// querystring or posted form
function get_congregation_search_query() {

	$searchQuery = '';

	if ( isset( $_GET['q'] ) ) {
		$searchQuery = urldecode(trim($_GET['q']));
	}
		
	if ( isset( $_POST['searchQuery'] ) ) {
		$searchQuery = trim($_POST['searchQuery']);	
	}

	return $searchQuery;
		
}



// ****************************
// REQUEST SEARCH DISTANCE
// ****************************
// returns a distance to search
function get_congregation_search_distance() {
	
	$searchDistance = 25;
	
	if ( isset( $_POST['searchDistance'] ) ) {
		$searchDistance = (int)$_POST['searchDistance'];
	} elseif ( isset( $_GET['d'] ) ) {
		$searchDistance = (int)$_GET['d'];
	}

	return $searchDistance;
	
}



// ****************************
// REQUEST SEARCH TYPE
// ****************************
// returns a type of search to perform for admin searches
function get_congregation_search_type() {
	
	$searchType = 'location';
	
	if ( isset( $_POST['searchType'] ) ) {
		$searchType = $_POST['searchType'];
	}

	return $searchType;
	
}



// ****************************
// PAGING FILTER
// ****************************
// returns the rows specified by the paging parameters
function get_paged_congregation_results($congregations, $PagingLimit, $PagingOffset) {

	$TotalRows = count($congregations);
	
	$FilteredResults = array();
	
	for ($i = $PagingOffset; $i < $PagingOffset + $PagingLimit && $i < count($congregations); $i++)
	{
		array_push($FilteredResults, $congregations[$i]);
	}
	
	return $FilteredResults;
	
}



// ****************************
// CACHING BACKDATE
// ****************************
// Get's the date that indicates if a cached search
// query should be updated based on the config.
function get_congregation_cache_backdate() {
	
	$options = get_option('congregation_search_settings');	
	
	$dateInterval = $options['congregation_search_QueryLength'];
	
	$date = new DateTime();
	$backDate = null;
		
	if ($dateInterval != 'disabled') {
		$backDate = $date->modify($dateInterval);
	}
	else
	{
		$backDate = $date->modify('+1 years');
	}
		
	return $backDate->format("Y-m-d h:m:s");
	
}
	
	
// ****************************
// SANITIZE LINE BREAKS
// ****************************
// removes all line breaks to auto formatting doesn't
// stick it's ugly html into places it shouldn't **facepalm**.
function congregation_santize_line_breaks($tosanitize) {
	$result = str_replace( array ( "<p>", "</p>", "<br>", "<br />" ), array ( '', '', '', '' ), $tosanitize );
	return $result;
}