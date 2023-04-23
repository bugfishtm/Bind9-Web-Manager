<?php
/*
  __        ___.   .__                                    __                
_/  |______ \_ |__ |  |   ____     _____ _____    _______/  |_  ___________ 
\   __\__  \ | __ \|  | _/ __ \   /     \\__  \  /  ___/\   __\/ __ \_  __ \
 |  |  / __ \| \_\ \  |_\  ___/  |  Y Y  \/ __ \_\___ \  |  | \  ___/|  | \/
 |__| (____  /___  /____/\___  > |__|_|  (____  /____  > |__|  \___  >__|   
           \/    \/          \/        \/     \/     \/            \/       
/*

Complex Table (Master)
Only one Table per Site, otherwhise export functions in MITEC will have problems and filtering/search may not work properly.
Filter Table Structure: id int(8) Auto_increment, table_id varchar(255) NOT NULL, user_id varchar(128) NOT NULL, is_default int(1) DEFAULT 0, name, filtername varchar(255) NOT NULL,
Funktions
Funktion -Name ONLY CHRONOLOGICAL!	Beschreibung
__construct($db, $tabletitle, $tableid = "")	$db -> Dolibarr db object
Tabletitle -> titlet of table (random name)
$tableid -> id of table to use in css or js,
enableFilters($table, $user_id, $show = true, $adds = true, $filterid = false)	$table -> SQL Table for Filters
$user_id -> relate current user
$show -> show filters area
$adds -> allow interactions with settings on filter area
$filterid -> to be recognizes in database which filters belong to this
addColumn($fname, $vname, $align = "left", $rights = 1, $search = true, $sort = true)	Fname: Field Name from Result
Vname: View Name for Column
align : text align left/right/center OR left;CSS to add additional css to a column
Rights: true if visible in this session (user right variable for exampe) and false if invisible for this user
Search: allow search on this column
sort: allow sort on this column
init($query, $default_limit, $default_sort_order, $default_sort_field)	Init the table
Query: start query needs to have at least one dummy where
Default_limit: default limit for pages
Default_sort_order: asc/desc
Default_sort_field: fieldname for sort (fname)
prepareArray()	Get array back with results
printFilters()	Print the Filtering Section
printTable($array, $imagefortitle = „generic“, $currenturl = $_SERVER[PHP_SELF])	Print the Table with content array
Imagefortitle is for title image in dolibarr print table function
Currenturl is for url forward on sort arrows or search fields



x_class_mastertable
Only one Table per Site, otherwhise export functions in MITEC will have problems and filtering/search may not work properly.
Filter Table Structure: id int(8) Auto_increment, table_id varchar(255) NOT NULL, user_id varchar(128) NOT NULL, is_default int(1) DEFAULT 0, name, filtername varchar(255) NOT NULL,
Funktions
Funktion -Name ONLY CHRONOLOGICAL!	Beschreibung
__construct($db, $tabletitle, $tableid = "")	$db -> Dolibarr db object
Tabletitle -> titlet of table (random name)
$tableid -> id of table to use in css or js,
	
addColumn($fname, $vname, $align = "left", $rights = 1, $search = true, $sort = true)	Fname: Field Name from Result
Vname: View Name for Column
align : text align left/right/center OR left;CSS to add additional css to a column
Rights: true if visible in this session (user right variable for exampe) and false if invisible for this user
Search: allow search on this column
sort: allow sort on this column
init($query, $default_limit, $default_sort_order, $default_sort_field)	Init the table
Query: start query needs to have at least one dummy where
Default_limit: default limit for pages
Default_sort_order: asc/desc
Default_sort_field: fieldname for sort (fname)
prepareArray()	Get array back with results
	
printTable($array, $imagefortitle = „generic“, $currenturl = $_SERVER[PHP_SELF])	Print the Table with content array
Imagefortitle is for title image in dolibarr print table function
Currenturl is for url forward on sort arrows or search fields


*/

	class m_class_mastertable {
	/////////////////////////////////////////////////
	// Class Vars Default
		private $db				=   false; // Dolibarr DB Object
		private $table_id		=   false; // This Tables ID for Get Requests
		private $table_title	=   false; // This Tables ID for Get Requests
		private $column_array	=	false; // This Tables Array with all Columns available
		private $query			=	false; // Query for Executions
		
		private $conf_sort_order = false; // default_sort_order
		private $conf_paramsadd  = false; // default_sort_order
		private $conf_sort_field = false; // default_sort_field
		private $conf_limit	     = false; // default_limit
		private $conf_page 	     = false; // default_page
		private $result_all_rows		=	false; // default_rowall
		private $result_fetched_rows	=	false; // default_rownumber$image = "", $user_id = false, $user_filters = false

	/////////////////////////////////////////////////
	// Class Vars Filter
		private $filter_active		=	false;
		private $filter_array		=	false;
		private $filter_table	    =	false; // The filter Tables Name
		private $filter_show		=	false; // Activate User adds own filters
		private $filter_adds		=	false; // Activate User adds own filters
		private $filter_user_id 	=	false; // Use Filters from which user?
		private $filter_current_id 	=	false; // Use Filters from which user?

	/////////////////////////////////////////////////
	// Construct the Class
		function __construct($db, $tabletitle, $tableid = "") {	$this->db = $db; $this->column_array = array(); $this->table_id = $tableid; $this->table_title = $tabletitle; }
	/////////////////////////////////////////////////
	// Enable Filters
		public function enableFilters($table, $user_id, $show = true, $adds = true, $filterid = false) {
			if(!$table_id) { $table_id = $this->table_id;  }
			// Set the Needed Variables
			$this->filter_active 	 = true;
			$this->filter_table 	 = $table;
			$this->filter_user_id 	 = $user_id;
			
			$this->filter_show 		 = $show;
			$this->filter_adds 		 = $adds;
			
			if(!$filterid) { if(is_numeric($_GET[$this->table_id."filterid"])) { $this->filter_current_id = $_GET[$this->table_id."filter"]; } else { $this->filter_current_id = false;  }  }
			else { if(is_numeric($_GET[$this->table_id."filterid"])) { $this->filter_current_id = $_GET[$this->table_id."filter"]; } else { $this->filter_current_id = $filterid; }  }
			// Get the Filters Array
			$this->filter_array 	= is_array();
			$sql_res = $this->db->query("SELECT * FROM ".$this->filter_table." WHERE table_id = '".$this->filter_current_id."' AND user_id = '".$this->filter_user_id."' ORDER BY is_default DESC");
			if ($sql_res) { if ($this->db->num_rows($sql_res) > 0) { $count = $this->db->num_rows($sql_res);  for ($i=0; $i<$count; $i++){
				array_push($this->filter_array, get_object_vars($this->db->fetch_object($sql_res))); 
			}}}}
	/////////////////////////////////////////////////
	// Add a Column for the Table with parameters
		public function addColumn($fname, $vname, $align = "left", $rights = 1, $search = true, $sort = true)  {  
			$tmp = array();
			$tmp["fieldname"]	=	$fname;   // Array Key Name of Column
			$tmp["viewname"]	=	$vname;   // Display Name of Column
			$tmp["fieldalign"]	=	$align;	  // Alignement of Column
			$tmp["right"]		=	$rights;  // Set Rights Variable [Permission]
			$tmp["search"]		=	$search;  // Does this Column support Search Function
			$tmp["sort"]		=	$sort;	  // Does this Column support Sorting Function		
			if($rights == true AND $rights != "0") { array_push($this->column_array ,$tmp); } }		
	/////////////////////////////////////////////////
	// Add a Column for the Table with parameters
		public function init($query, $default_limit, $default_sort_order, $default_sort_field) {	 // ONLY PROVIDE QUERY WITHOUT SORT AND LIMIT OFFSET FUNCTIONS		 AND ENDS WITH WHERE X = X AT LEAST WITH ONE WHEE	
			// SORTING DEFAULT FIELD 
			// Check if Get Parameters do have Configurations for that table
			if( GETPOST('sortfield', 'alpha') ) { $this->conf_sort_field = GETPOST("sortfield", 'alpha'); } else { $this->conf_sort_field = $default_sort_field; }
			if(trim(@$this->conf_sort_field) == "" OR !@$this->conf_sort_field) { $this->conf_sort_field = $default_sort_field; }
			$_GET["sortfield"] = $this->conf_sort_field; $_POST["sortfield"] = $this->conf_sort_field;
			// SORTING DEFAULT ORDER 
			$this->conf_sort_order 		= GETPOST("sortorder", 'alpha') ? GETPOST('sortorder', 'alpha') : $default_sort_order; 
			if(trim(@$this->conf_sort_order) != "asc" AND trim(@$this->conf_sort_order) != "desc") {$this->conf_sort_order = $default_sort_order;}
			$_GET["sortorder"] = $this->conf_sort_order; $_POST["sortorder"] = $this->conf_sort_order;		
			// LIMITS
			$this->conf_limit 		= GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $default_limit;	
			if(trim(@$this->conf_limit) == "" OR !@$this->conf_limit) {$this->conf_limit = $default_limit;}
			if(is_numeric(trim(@$_POST["limit"]))) {$this->conf_limit = trim($_POST["limit"]);}
			// PAGES	
			$this->conf_page		= GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
			if (empty($this->conf_page) || $this->conf_page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {$this->conf_page = 0;}
			
			// Extend Query with Search Variables from top if there are some
			foreach($this->column_array as $key => $value) {
				if((is_string($_POST["mct_".$this->table_id.$value["fieldname"]]) OR is_numeric($_POST["mct_".$this->table_id.$value["fieldname"]])) AND trim(@$_POST["mct_".$this->table_id.$value["fieldname"]]) != "") {
					$query .= " AND ".$value["fieldname"]." LIKE '%".$this->db->escape(urldecode($_POST["mct_".$this->table_id.$value["fieldname"]]))."%' ";
				}
			}
			// Get Offset From Limit and Current Page
			$offset = $this->conf_limit * $this->conf_page;
			$qnew	 = $query;
			$qnew 	.= $this->db->order($this->conf_sort_field, $this->conf_sort_order);
			$ewnrs = $this->db->query($qnew);
			$curcount = $this->db->num_rows($qnew);
			if($curcount < $offset) { $offset = 0;}
			$qnew 	.= $this->db->plimit($this->conf_limit, $offset);
			//$ewnrs = $this->db->query($qnew);
			$curcount1 = $this->db->num_rows($qnew);
			
			$this->query = $qnew;
			
			$this->result_all_rows  	= $curcount;
			$this->result_fetched_rows 	= $curcount1;
			
			$this->conf_paramsadd = "";
			$this->conf_paramsadd = '&limit='.$this->conf_limit;		
		}							
	/////////////////////////////////////////////////
	// Get the Results array!
		public function prepareArray() {
			$array = array();
			$sql_res = $this->db->query($this->query);
			if ($sql_res) { if ($this->db->num_rows($sql_res) > 0) { $count = $this->db->num_rows($sql_res);  for ($i=0; $i<$count; $i++){
				array_push($array, get_object_vars($this->db->fetch_object($sql_res))); 
			}}} return $array;
		}











	/////////////////////////////////////////////////
	// Show the Table	
	public function printTable($array, $tableimage = "", $cursiteurl = false) {
		if(!is_string($cursiteurl)) { $cursiteurl = $_SERVER["PHP_SELF"]; }
		print '<div id="div-table-responsive" id="m_cmfull_'.@$this->table_id.'">';
		print '<form method="post"><input type="submit" style="display: none;">';		
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.@$this->conf_sort_field.'">';
		print '<input type="hidden" name="sortorder" value="'.@$this->conf_sort_order.'">';
		print '<input type="hidden" name="limit" value="'.@$this->conf_limit.'">';
		print '<input type="hidden" name="page" value="'.@$this->conf_page.'">';
	
		print_barre_liste($title, // Title of the Table
							@$this->conf_page, // Current Page
							@$cursiteurl, // Current Site URL
							@$this->conf_paramsadd, // Current Params for URL on titles for Sort
							@$this->conf_sort_field,
							@$this->conf_sort_order,
							"", // more HTML center
							@$this->result_fetched_rows, // Records found by Select
							@$this->result_all_rows, // All Found by Select Number
                            @$tableimage, // Image for List
							0, // 1 Is Fullpathimage 0 if not
							"", // more html right
							"", // more CSS
							$this->conf_limit, // Limits for Rows
							0,  // Hide Limit Selection
							0,  // Hide Navigation Tools 
							1); // Do not suggest list of pages
		$this->conf_paramsadd .= '&page='.urlencode($this->conf_page);
		
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		// Filter Delete Columns Which are not Present in Filter
		
		
		print '<table class="tagtable liste">';
	
		// Print Search Fields
		print '<tr class="liste_titre">';	
			foreach( $this->column_array as $keyc => $valuec ){							
				if($valuec["search"] == 1) {
					$tmp_placeholder = $valuec["viewname"];
					$tmp_value = @htmlspecialchars($_POST['mct_'.$this->table_id.$valuec["fieldname"]]);
					print '<th style="text-align: '.$valuec["fieldalign"].';"><input type="text" name="mct_'.$this->table_id.$valuec["fieldname"].'" value=\''.@$tmp_value.'\' placeholder="'.$tmp_placeholder.'"></th>';
				} else {
					print '<th></th>';
				}
			}
		print '</tr>';
			
		// Print Titles //////////////////////////////////////////////////////////////////////////////////////////////////////////
		if(is_array($this->column_array)) {
			print '<tr class="liste_titre">';
				foreach( $this->column_array as $key => $value ){
					if($value["sort"] == 1) {$tmpgetsort	=	$value["fieldname"];} else{$tmpgetsort	=	NULL;}
					print_liste_field_titre($value["viewname"], $cursiteurl, $tmpgetsort, $this->conf_paramsadd, "", "style='text-align: ".$value["fieldalign"].";'", @$this->conf_sort_field, @$this->conf_sort_order);
				}
			print '</tr>';
		}
				
		// Print Data //////////////////////////////////////////////////////////////////////////////////////////////////////////
		$didfound = false;
		if(is_array($array)) {
			foreach( $array as $key => $value ){
				print '<tr class="oddeven">';
				foreach( $array[$key] as $key1 => $value1 ){
					foreach( $this->column_array as $keycc => $valuecc ){
						if($valuecc["fieldname"] == $key1) {
							print '<td style="text-align: '.$valuecc["fieldalign"].';">'.$value1.'</td>';		
						} 						
					}
				}
				print '</tr>'; $didfound = true;
			}
		}
			
		if(!$didfound) {print '<tr class="oddeven"><td colspan="'.count($this->column_array).'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>';}
		print '</table></form></div>';
	}				
		

		
	/////////////////////////////////////////////////
	// Show the Filters
		public function printFilters($formlocations) {
			if($this->filter_show) {
				echo '<div id="m_mtf_'.$this->table_id.'" class="m_masterTable_filter"><select onchange="window.location.href = \''.$formlocations.'\'">'; 
				$tmpdisplay = ""; foreach( $this->filter_array as $key => $value ){
					if( $value["is_default"] == 1 ) { $tmpdisplay = "<option value='".$value["id"]."'>".$value["filter_name"]."</option>"; } else { $tmpdisplay = '<option value="0">Aktuell: Kein Filter</option>'; }
				} echo $tmpdisplay;
				foreach( $this->filter_array as $key => $value ){ if( $value["is_default"] != 1 ) { echo "<option value='".$value["id"]."'>".$value["filter_name"]."</option>"; } } echo '</select>'; 
				if($this->filter_adds) {
					echo '<button onclick=\'$( "#m_mtfs_'.$this->table_id.'" ).toggle();\'>Filtereinstellungen</a>';
					echo '<div id="m_mtfs_'.$this->table_id.'" style="display: none;width: 100%; text-align: right; position: absolute; background: yellow; max-width: 250px;z-index: 1000 !important;right: 2%;">';	
					echo '<form method="post" action="'.$formlocations.'">';
					$isediting  = false; foreach( $this->filter_array as $keyx => $value1 ){ if($value1["id"] == $this->filter_current_id) {$isediting = $value1;} }
					foreach( $this->column_array as $key => $value ){
						if(is_array($isediting)) {
							if (in_array($value["fieldname"],  unserialize($isediting["ar_fields"]))) {
								 echo "<div style='float: left;text-align: left;'><input type='checkbox' name='m_mtc_".$this->table_id."_".$value["fieldname"]."' checked>".$value["viewname"]."</div>";
							} else { echo "<div style='float: left;;text-align: left;'><input type='checkbox' name='m_mtc_".$this->table_id."_".$value["fieldname"]."'>".$value["viewname"]."</div>";	}
						} else { echo "<div style='float: left;;text-align: left;'><input type='checkbox' name='m_mtc_".$this->table_id."_".$value["fieldname"]."'>".$value["viewname"]."</div>";	}	
					}	
					if(is_array($isediting)) { echo '<input type="submit" name="m_mtcse_'.$this->table_id.'" value="Filter Bearbeiten"><input type="hidden" name="m_mtch_'.$this->table_id.'" value="'.$this->filter_current_id.'">';} 
					else {echo '<input type="submit" name="m_mtcsa_'.$this->table_id.'" value="Filter Erstellen"><input type="text" name="m_mtcsn_'.$this->table_id.'" value="'.$isediting["filter_name"].'" placeholder="Filter-Name">';}
					echo '</form></div>';
				} echo '</div>';	
			}
		}
	}		
