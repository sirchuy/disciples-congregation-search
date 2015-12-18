<?php

// Classes Used in the find a congregation plugin


// *******************************************
// *
// * CONGREGATION CLASS
// * Defines the parameters of a congregation and it's data.
// *
// *******************************************

class Congregation {

	var $PIN;
	var $CongregationName;
	var $EIN;
	var $Address1;
	var $Address2;
	var $City;
	var $State;
	var $PostalCode;
	var $Phone;
	var $Email;
	var $Website;
	var $Region;
	var $Latitude;
	var $Longitude;
	var $DateUpdated;
	var $UpdateType;
	var $DoNotAutoUpdate;

	public function Congregation() {
		
	}
	
	// *************************************
	// GET CONGREGATION BY PIN
	// *************************************
	public function get_congregation_by_PIN($SearchForPIN)
	{
				
		// get data from DB
		$CongregationResults = get_congregation_from_db($SearchForPIN);
			
		if ($CongregationResults !== false)
		{
			
			// assign the properties
			$this->PIN = $CongregationResults->PIN;
			$this->CongregationName = $CongregationResults->Church_Name;
			$this->EIN = $CongregationResults->EIN;
			$this->Address1 = $CongregationResults->Yearbook_Address_Line_1;
			$this->Address2 = $CongregationResults->Yearbook_Address_Line_2;
			$this->City = $CongregationResults->Yearbook_City;
			$this->State = $CongregationResults->Yearbook_State;
			$this->PostalCode = $CongregationResults->Yearbook_Zip;
			$this->Phone = $CongregationResults->Phone;
			$this->Email = $CongregationResults->Email_Address;
			$this->Website = $CongregationResults->Web_Address;
			$this->Region = $CongregationResults->Region;
			$this->Latitude = $CongregationResults->Latitude;
			$this->Longitude = $CongregationResults->Longitude;
			$this->DateUpdated = $CongregationResults->Date_Updated;
			$this->DoNotAutoUpdate = $CongregationResults->DoNotAutoUpdate;
					
			// return true if we found data and loaded the class
			return true;
		}
		else
		{
			return false;
		}
	}
		
	// *************************************
	// SAVE CONGREGATION
	// *************************************
	// saves the current congregation object
	public function save_current_congregation()
	{
		
		$SaveResult = save_congregation($this); // final save action result
		return $SaveResult;
		
	}
	
	// *************************************
	// NEW CONGREGATION
	// *************************************
	// inserts a new congregation
	public function save_new_congregation()
	{
		$SaveResult = insert_congregation($this); // final save action result
		return $SaveResult;
	}
	
	// *************************************
	// DELETE CONGREGATION
	// *************************************
	// deletes the current congregation object
	public function delete_current_congregation()
	{
		
		$DeleteResult = delete_congregation($this->PIN);
		return $DeleteResult;
		
	}
		
}

// *******************************************
// *
// * QUERY CLASS
// * Defines the parameters of a query and it's data.
// *
// *******************************************
class CongregationSearchQueryItem {

	var $QID;
	var $Query;
	var $Latitude;
	var $Longitude;
	var $HitCount;
	var $LastUpdated;
	var $IsNew = true;
	
	public function CongregationSearchQueryItem() {
	
	}
	
	
	// ***************
	// GET BY ID
	// ***************
	// gets a query by its ID number
	function get_query_by_id($qID) {
		
		$QueryItem = congregation_search_get_query_by_id($qID);
		
		if ($QueryItem !== false)
		{
			$this->QID = $qID;
			$this->Query = (int)$QueryItem[0]->Query	;
			$this->Latitude = $QueryItem[0]->Latitude;
			$this->Longitude = $QueryItem[0]->Longitude;
			$this->HitCount = (int)$QueryItem[0]->HitCount;
			$this->LastUpdated = new DateTime($QueryItem[0]->LastUpdated);
			$this->IsNew = false;
		}
		else
		{
			$this->QID = -1;
		}
		
	}
	
	
	// ***************
	// GET QUERY
	// ***************
	// look in the db for an existing query in order
	// to save hitting the Google API
	public function get_query($Query) {
	
		$this->Query = $Query;
	
		$QueryItem = congregation_search_get_query_by_string($Query);
				
		if ($QueryItem != false)
		{
			$this->QID = (int)$QueryItem[0]->QID;
			$this->Query = $QueryItem[0]->Query	;
			$this->Latitude = $QueryItem[0]->Latitude;
			$this->Longitude = $QueryItem[0]->Longitude;
			$this->HitCount = (int)$QueryItem[0]->HitCount;
			$this->LastUpdated = date($QueryItem[0]->LastUpdated);
			$this->IsNew = false;
		}
		else
		{
			$this->QID = -1;
		}
		
	}
	
	// ***************
	// INCREMENT HIT
	// ***************
	// adds a hit to the query for tracking purposes
	public function increment_usage() {
	
		$this->HitCount = $this->HitCount + 1;
		
		congregation_search_update_query($this);
	
	}
	
	// ***************
	// SAVE QUERY
	// ***************
	// saves the current instance of teh query
	public function save_query() {
		
		$result = false;
		
		if ($this->IsNew)
		{
			$this->HitCount = 1;
			$result = congregation_search_save_new_query($this);
			if ($result)
			{
				$this->QID = (int)$result;
				$this->IsNew = false;
			}
		}
		else
		{
			$result = congregation_search_update_query($this);
		}
		
		return $result;
	}
	
	// ***************
	// DELETE QUERY
	// ***************
	// deletes the current instance of the query
	public function delete_query() {
		
		$result = false;
		
		if (!$this->IsNew)
		{
			
			$result = congregation_search_delete_query($this->QID);
			
		}
		
		return $result;
		
	}
	
}


// *************************************
// GENERATE A STATE DROPDOWN
// *************************************
function congregation_get_state_dropdown($ElementID, $SelectedState, $CssClass) {

	$renderedList = '		<select id="' . $ElementID . '" name="' . $ElementID . '" class="' . $CssClass . '">
		<option value="">UNITED STATES</option>
		<option value="AL"' . ($SelectedState == 'AL' ? ' selected' : '') .'>Alabama</option>
		<option value="AK"' . ($SelectedState == 'AK' ? ' selected' : '') .'>Alaska</option>
		<option value="AZ"' . ($SelectedState == 'AZ' ? ' selected' : '') .'>Arizona</option>
		<option value="AR"' . ($SelectedState == 'AR' ? ' selected' : '') .'>Arkansas</option>
		<option value="CA"' . ($SelectedState == 'CA' ? ' selected' : '') .'>California</option>
		<option value="CO"' . ($SelectedState == 'CO' ? ' selected' : '') .'>Colorado</option>
		<option value="CT"' . ($SelectedState == 'CT' ? ' selected' : '') .'>Connecticut</option>
		<option value="DE"' . ($SelectedState == 'DE' ? ' selected' : '') .'>Delaware</option>
		<option value="DC"' . ($SelectedState == 'DC' ? ' selected' : '') .'>District Of Columbia</option>
		<option value="FL"' . ($SelectedState == 'FL' ? ' selected' : '') .'>Florida</option>
		<option value="GA"' . ($SelectedState == 'GA' ? ' selected' : '') .'>Georgia</option>
		<option value="HI"' . ($SelectedState == 'HI' ? ' selected' : '') .'>Hawaii</option>
		<option value="ID"' . ($SelectedState == 'ID' ? ' selected' : '') .'>Idaho</option>
		<option value="IL"' . ($SelectedState == 'IL' ? ' selected' : '') .'>Illinois</option>
		<option value="IN"' . ($SelectedState == 'IN' ? ' selected' : '') .'>Indiana</option>
		<option value="IA"' . ($SelectedState == 'IA' ? ' selected' : '') .'>Iowa</option>
		<option value="KS"' . ($SelectedState == 'KS' ? ' selected' : '') .'>Kansas</option>
		<option value="KY"' . ($SelectedState == 'KY' ? ' selected' : '') .'>Kentucky</option>
		<option value="LA"' . ($SelectedState == 'LA' ? ' selected' : '') .'>Louisiana</option>
		<option value="ME"' . ($SelectedState == 'ME' ? ' selected' : '') .'>Maine</option>
		<option value="MD"' . ($SelectedState == 'MD' ? ' selected' : '') .'>Maryland</option>
		<option value="MA"' . ($SelectedState == 'MA' ? ' selected' : '') .'>Massachusetts</option>
		<option value="MI"' . ($SelectedState == 'MI' ? ' selected' : '') .'>Michigan</option>
		<option value="MN"' . ($SelectedState == 'MN' ? ' selected' : '') .'>Minnesota</option>
		<option value="MS"' . ($SelectedState == 'MS' ? ' selected' : '') .'>Mississippi</option>
		<option value="MO"' . ($SelectedState == 'MO' ? ' selected' : '') .'>Missouri</option>
		<option value="MT"' . ($SelectedState == 'MT' ? ' selected' : '') .'>Montana</option>
		<option value="NE"' . ($SelectedState == 'NE' ? ' selected' : '') .'>Nebraska</option>
		<option value="NV"' . ($SelectedState == 'NV' ? ' selected' : '') .'>Nevada</option>
		<option value="NH"' . ($SelectedState == 'NH' ? ' selected' : '') .'>New Hampshire</option>
		<option value="NJ"' . ($SelectedState == 'NJ' ? ' selected' : '') .'>New Jersey</option>
		<option value="NM"' . ($SelectedState == 'NM' ? ' selected' : '') .'>New Mexico</option>
		<option value="NY"' . ($SelectedState == 'NY' ? ' selected' : '') .'>New York</option>
		<option value="NC"' . ($SelectedState == 'NC' ? ' selected' : '') .'>North Carolina</option>
		<option value="ND"' . ($SelectedState == 'ND' ? ' selected' : '') .'>North Dakota</option>
		<option value="OH"' . ($SelectedState == 'OH' ? ' selected' : '') .'>Ohio</option>
		<option value="OK"' . ($SelectedState == 'OK' ? ' selected' : '') .'>Oklahoma</option>
		<option value="OR"' . ($SelectedState == 'OR' ? ' selected' : '') .'>Oregon</option>
		<option value="PA"' . ($SelectedState == 'PA' ? ' selected' : '') .'>Pennsylvania</option>
		<option value="RI"' . ($SelectedState == 'RI' ? ' selected' : '') .'>Rhode Island</option>
		<option value="SC"' . ($SelectedState == 'SC' ? ' selected' : '') .'>South Carolina</option>
		<option value="SD"' . ($SelectedState == 'SD' ? ' selected' : '') .'>South Dakota</option>
		<option value="TN"' . ($SelectedState == 'TN' ? ' selected' : '') .'>Tennessee</option>
		<option value="TX"' . ($SelectedState == 'TX' ? ' selected' : '') .'>Texas</option>
		<option value="UT"' . ($SelectedState == 'UT' ? ' selected' : '') .'>Utah</option>
		<option value="VT"' . ($SelectedState == 'VT' ? ' selected' : '') .'>Vermont</option>
		<option value="VA"' . ($SelectedState == 'VA' ? ' selected' : '') .'>Virginia</option>
		<option value="WA"' . ($SelectedState == 'WA' ? ' selected' : '') .'>Washington</option>
		<option value="WV"' . ($SelectedState == 'WV' ? ' selected' : '') .'>West Virginia</option>
		<option value="WI"' . ($SelectedState == 'WI' ? ' selected' : '') .'>Wisconsin</option>
		<option value="WY"' . ($SelectedState == 'WY' ? ' selected' : '') .'>Wyoming</option>
		<option value="">CANADA</option>
		<option value="AB"' . ($SelectedState == 'AB' ? ' selected' : '') .'>Alberta</option>
		<option value="BC"' . ($SelectedState == 'BC' ? ' selected' : '') .'>British Columbia</option>
		<option value="MB"' . ($SelectedState == 'MB' ? ' selected' : '') .'>Manitoba</option>
		<option value="NB"' . ($SelectedState == 'NB' ? ' selected' : '') .'>New Brunswick</option>
		<option value="NL"' . ($SelectedState == 'NL' ? ' selected' : '') .'>Newfoundland and Labrador</option>
		<option value="NS"' . ($SelectedState == 'NS' ? ' selected' : '') .'>Nova Scotia</option>
		<option value="ON"' . ($SelectedState == 'ON' ? ' selected' : '') .'>Ontario</option>
		<option value="PE"' . ($SelectedState == 'PE' ? ' selected' : '') .'>Prince Edward Island</option>
		<option value="QC"' . ($SelectedState == 'QC' ? ' selected' : '') .'>Quebec</option>
		<option value="SK"' . ($SelectedState == 'SK' ? ' selected' : '') .'>Saskatchewan</option>
		<option value="NT"' . ($SelectedState == 'NT' ? ' selected' : '') .'>Northwest Territories</option>
		<option value="NU"' . ($SelectedState == 'NU' ? ' selected' : '') .'>Nunavut</option>
		<option value="YT"' . ($SelectedState == 'YT' ? ' selected' : '') .'>Yukon</option>	
	</select>';			

	echo $renderedList;
	
}

