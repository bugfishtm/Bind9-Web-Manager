<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ Dolibarr Function Library Set	*/
	// Check if a Var is Set
	function m_isset($var){if(!empty($var) AND $var != NULL AND trim($var) != "") {return true;}return false;}
	// Get the current rowID of logged in User, if error than false
	function m_login_id($db){ $result = m_db_row($db, 'SELECT * FROM ' . MAIN_DB_PREFIX . 'user WHERE login = "' . @$_SESSION["dol_login"] . '"'); if(!$result) {return false;}return $result["rowid"];}	
	// Get the current name of User by UserID, if error than false
	function m_login_name_from_id($db, $userid){ $result = m_db_row($db, 'SELECT * FROM ' . MAIN_DB_PREFIX . 'user WHERE rowid = "' . $userid . '"'); if(!$result) {return false;} return $result["login"];}
	// Month Number to Name
	function m_month_num_to_name($number) {
		if($number == 1) { return "Januar";}
		if($number == 2) { return "Februar";}
		if($number == 3) { return "März";}
		if($number == 4) { return "April";}
		if($number == 5) { return "Mai";}
		if($number == 6) { return "Juni";}
		if($number == 7) { return "Juli";}
		if($number == 8) { return "August";}
		if($number == 9) { return "September";}
		if($number == 10) { return "Oktober";}
		if($number == 11) { return "November";}
		if($number == 12) { return "Dezember";}
		return "Error !";
	};
