<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ Dolibarr MySQL Set */
	// Get a Multiple Array with $array[COUNT]["fieldname"] = $value back.
	function m_db_rows($db, $query){ $sql_res = $db->query($query); if ($sql_res) { if ($db->num_rows($sql_res) > 0) { $count = $db->num_rows($sql_res); $row = array(); for ($i=0; $i<$count; $i++){$tmpnow = get_object_vars($db->fetch_object($sql_res)); $row[$i] = $tmpnow;} return $row; } else { return false; }} else { return false; }}	
	// Get a Single Array with $array["fieldname"] = $value back.
	function m_db_row($db, $query){ $sql_res = $db->query($query); if ($sql_res) { if ($db->num_rows($sql_res) > 0) { $tmpnow = get_object_vars($db->fetch_object($sql_res));  $row = $tmpnow; return $row; } else { return false; }} else { return false; }}		
	// Insert into a Database with array ["fieldname"] = $value;
	function m_db_row_insert($db, $table, $array, $filter = true){ if(!is_array($array)) {return false;} $build_first	=	""; $build_second	=	""; $firstrun = true; foreach( $array as $key => $value ){ if(!$firstrun) {$build_first .= ", ";} if(!$firstrun) {$build_second .= ", ";} $build_first .= $key; $valuex = $value; if($filter) {$valuex = str_replace("\\", "\\\\", htmlspecialchars($valuex));} else {$valuex = str_replace("\\", "\\\\", $valuex);} $valuex = str_replace("'", "\\'", $valuex); $build_second .= "'".$valuex."'"; $firstrun = false;} $db->query('INSERT INTO '.$table.'('.$build_first.') VALUES('.$build_second.');');}
	/* Get Array by provising a finished result */
	function m_db_rowsbycleanresult($db, $sql_res){ if ($sql_res) { if ($db->num_rows($sql_res) > 0) { $count = $db->num_rows($sql_res); $row = array(); for ($i=0; $i<$count; $i++){$tmpnow = get_object_vars($db->fetch_object($sql_res)); $row[$i] = $tmpnow;} return $row; } else { return false; }} else { return false; }}
