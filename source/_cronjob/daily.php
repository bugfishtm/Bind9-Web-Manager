<?php
	// Configurations Include
		require_once(dirname(__FILE__) ."/../settings.php");

	// Delete IP Blacklist Table Entries 
		$mysql->query("DELETE FROM "._TABLE_IPBL_." ");
		
	// Output Message
		echo "Daily Cronjob: IP Blacklist has been cleared!";
	
	// Log Message
		$log->info("<font color='lime'>Daily Cronjob: IP Blacklist has been cleared!</font>");
?>
