<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  API File to Fetch Content of a Domain */
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
	
	// Check if Token is Valid
	if($x = dnshttp_bind_domain_name_exists($mysql, @$_POST["domain"])) {	
		$ar = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = ".$x." AND (conflict = 0 OR (conflict = 1 AND preferred = 1))", false);
		if(is_array($ar)) {
			echo $ar["content"];
		} else { 	
			echo "error-domain-no-exist";
		}
		exit();
	} else { 
		//$log_api->message("[IN][content.php][error-domain-no-exist][IP:".@$_SERVER["REMOTE_ADDR"]."][TOKEN:".@$_POST["token"]."][DOMAIN:".@$_POST["domain"]."]");
		echo "error-domain-no-exist";
		exit();
	}
?>