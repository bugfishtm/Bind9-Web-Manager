<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Bugfish DNS Replication Cronjob */
	#######################################################################################################################################
	// Include Settings File
	if(file_exists(dirname(__FILE__) ."/../settings.php")) { require_once(dirname(__FILE__) ."/../settings.php"); }
		else { echo "ERROR: settings.php does not exist. Please check your instance configuration!<br />(settings.sample.php needs to be set up and renamed to settings.php!)"; }
	full_cron($mysql, true);