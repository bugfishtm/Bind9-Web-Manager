<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Cronjob to get notifications with latest news! */
	if(php_sapi_name() !== 'cli'){ die('Can only be executed via CLI'); }

	// Configurations Include
	require_once(dirname(__FILE__) ."/../settings.php");

	echo "\r\n\rAdded Domains:\r\n";
	$array = $mysql->select("SELECT * FROM "._TABLE_NOTIFY_." WHERE section = 'added'", true);
	$array = $mysql->query("DELETE FROM "._TABLE_NOTIFY_." WHERE section = 'added'");
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo $value["message"]."\r\n";
		}
	}
	
	echo "\r\n\rRemoved Domains:\r\n";
	$array = $mysql->select("SELECT * FROM "._TABLE_NOTIFY_." WHERE section = 'removed'", true);
	$array = $mysql->query("DELETE FROM "._TABLE_NOTIFY_." WHERE section = 'removed'");
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo $value["message"]."\r\n";
		}
	}
	
	echo "\r\n\r\nActive Domains - Fallback:\r\n";
	$array = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE registered = 1 AND oldzonefallback = 1", true);
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo "Slave:". $value["domain"]."\r\n";
		}
	}
	$array = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE registered = 1 AND oldzonefallback = 1", true);
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo "Master:". $value["domain"]."\r\n";
		}
	}
	
	echo "\r\n\r\nActive Domains - OK:\r\n";
	$array = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE registered = 1 AND oldzonefallback = 0", true);
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo "Slave:".$value["domain"]."\r\n";
		}
	}
	$array = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE registered = 1 AND oldzonefallback = 0", true);
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo "Master:".$value["domain"]."\r\n";
		}
	}
?>
