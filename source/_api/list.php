<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  API File to Fetch Domain Content */
	require_once("../settings.php");				
	
	// Class for Logging
	$log_api	=	new x_class_log($mysql, _TABLE_LOG_, "api");
	
	// Check if Request is IP-Blocked
	if($ipbl->isblocked()) {
		$log_api->message("[IN][ip-blacklisted][IP:".@$_SERVER["REMOTE_ADDR"]."]");
		echo "ip-blacklisted";
		exit(); 
	}
	
	// Check if Token is Valid
	if(!api_token_check($mysql, @$_POST["token"])) {
		$log_api->message("[IN][token-error][IP:".@$_SERVER["REMOTE_ADDR"]."][TOKEN:".@$_POST["token"]."]");
		$ipbl->raise(); 
		echo "token-error";
		exit();
	}	

	// Display Content List of Local Domains
	$domar	=	array();
	$ar = $mysql->select("SELECT domain FROM "._TABLE_DOMAIN_BIND_." WHERE set_no_replicate = 0", true);	
	if(is_array($ar)) {	
		foreach($ar AS $key => $value) { array_push($domar, trim($value["domain"])); }
		$log_api->message("[IN][OK][IP:".@$_SERVER["REMOTE_ADDR"]."][TOKEN:".@$_POST["token"]."][DOMAINS:".@count($domar)."]");
		echo serialize($domar);
		exit();
	} else { 
		echo serialize(array()); 
		$log_api->message("[IN][OK: 0 Domains][IP:".@$_SERVER["REMOTE_ADDR"]."][TOKEN:".@$_POST["token"]."]");
		exit();
	}	
	
	