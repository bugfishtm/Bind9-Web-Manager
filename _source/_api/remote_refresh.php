<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  API File to Fetch List of a Server Domains */
	require_once("../settings.php");
	
	// Class for Logging
	$log_api	=	new x_class_log($mysql, _TABLE_LOG_, "api");
	
	// Check if Request is IP-Blocked
	if($ipbl->isblocked()) {
		echo "ip-blacklisted";
		exit(); 
	}
	
	// Check if Token is Valid
	if(!api_token_check($mysql, @$_POST["token"])) {
		$ipbl->raise(); 
		echo "token-error";
		exit();
	}	
	
	// Display Count of Local Domains
	$domar	=	array();
	$ar = $mysql->select("SELECT domain FROM "._TABLE_DOMAIN_BIND_." WHERE set_no_replicate = 0", true);	
	if(is_array($ar)) {	
		echo count($ar); 
		exit();
	} else {
		echo "0"; 
		exit();
	}
?>