<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Status Check API File*/
	
	// Class for Logging
	$log_api	=	new x_class_log($mysql, _TABLE_LOG_, "api");
	//$log_api->message("[IN][status.php][online][IP:".@$_SERVER["REMOTE_ADDR"]."]");
	echo "online";
	exit();
?>