<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Status API with Security Token */
	require_once("../settings.php");

	// Class for Logging
	$log_api	=	new x_class_log($mysql, _TABLE_LOG_, "api");
	
	// Check if Request is IP-Blocked
	if($ipbl->isblocked()) {
		//$log_api->message("[IN][status_token.php][ip-blacklisted][IP:".@$_SERVER["REMOTE_ADDR"]."]");
		echo "ip-blacklisted";
		exit(); 
	}
	
	// Check if Token is Valid
	if(!api_token_check($mysql, @$_POST["token"])) {
		//$log_api->message("[IN][status_token.php][token-error][IP:".@$_SERVER["REMOTE_ADDR"]."][TOKEN:".@$_POST["token"]."]");
		$ipbl->raise(); 
		echo "token-error";
		exit();
	}
	
	// Echo Online if all Okay
	//$log_api->message("[IN][status_token.php][online][IP:".@$_SERVER["REMOTE_ADDR"]."][TOKEN:".@$_POST["token"]."]");
	echo "online"; 
	exit();
?>