<?php 
	/* 	
		@@@@@@@   @@@  @@@   @@@@@@@@  @@@@@@@@  @@@   @@@@@@   @@@  @@@  
		@@@@@@@@  @@@  @@@  @@@@@@@@@  @@@@@@@@  @@@  @@@@@@@   @@@  @@@  
		@@!  @@@  @@!  @@@  !@@        @@!       @@!  !@@       @@!  @@@  
		!@   @!@  !@!  @!@  !@!        !@!       !@!  !@!       !@!  @!@  
		@!@!@!@   @!@  !@!  !@! @!@!@  @!!!:!    !!@  !!@@!!    @!@!@!@!  
		!!!@!!!!  !@!  !!!  !!! !!@!!  !!!!!:    !!!   !!@!!!   !!!@!!!!  
		!!:  !!!  !!:  !!!  :!!   !!:  !!:       !!:       !:!  !!:  !!!  
		:!:  !:!  :!:  !:!  :!:   !::  :!:       :!:      !:!   :!:  !:!  
		 :: ::::  ::::: ::   ::: ::::   ::        ::  :::: ::   ::   :::  
		:: : ::    : :  :    :: :: :    :        :    :: : :     :   : :  
		   ____         _     __                      __  __         __           __  __
		  /  _/ _    __(_)__ / /    __ _____  __ __  / /_/ /  ___   / /  ___ ___ / /_/ /
		 _/ /  | |/|/ / (_-</ _ \  / // / _ \/ // / / __/ _ \/ -_) / _ \/ -_|_-</ __/_/ 
		/___/  |__,__/_/___/_//_/  \_, /\___/\_,_/  \__/_//_/\__/ /_.__/\__/___/\__(_)  
								  /___/                           
		Bugfish - DNSHTTP Software / MIT License
		// Autor: Jan-Maurice Dahlmanns (Bugfish)
		// Website: www.bugfish.eu 
	*/
	if(php_sapi_name() !== 'cli'){ die('Can only be executed via CLI'); }

	// Configurations Include
	require_once(dirname(__FILE__) ."/../settings.php");
	echo "-----------------------------------------------------";
	echo "DNSHTTP Notification Cronjob";
	echo "-----------------------------------------------------";
	echo "\r\n\rAdded Domains:\r\n";
	echo "-----------------------------------------------------\r\n";
	$array = $mysql->select("SELECT * FROM "._TABLE_NOTIFY_." WHERE section = 'added'", true);
	$array = $mysql->query("DELETE FROM "._TABLE_NOTIFY_." WHERE section = 'added'");
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo $value["message"]."\r\n";
		}
	}

	echo "\r\n-----------------------------------------------------";	
	echo "\r\n\rRemoved Domains:\r\n";
	echo "-----------------------------------------------------\r\n";
	$array = $mysql->select("SELECT * FROM "._TABLE_NOTIFY_." WHERE section = 'removed'", true);
	$array = $mysql->query("DELETE FROM "._TABLE_NOTIFY_." WHERE section = 'removed'");
	if(is_array($array)) {
		foreach($array AS $key => $value) {
			echo $value["message"]."\r\n";
		}
	}
	
	echo "\r\n-----------------------------------------------------";		
	echo "\r\n\r\nActive Domains - Fallback:\r\n";
	echo "-----------------------------------------------------\r\n";
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
	
	echo "\r\n-----------------------------------------------------";		
	echo "\r\n\r\nActive Domains - OK:\r\n";
	echo "-----------------------------------------------------\r\n";
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