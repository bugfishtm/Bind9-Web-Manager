<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Bind9 Web Manager Configuration File */
	/* Website Setup */				
	define("_TITLE_", 				"YOUR_TITLE"); # A Imaginary Server Name to show at Title, can be unchanged
	define("_IMPRESSUM_",			"YOUR_IMPRESSUM_URL"); # URL to your Impressum Website
	
	/* Database Setup */				
	define("_SQL_HOST_", 			"127.0.0.1"); # Mysql Hostname
	define("_SQL_USER_", 			"DBUSER"); # Mysqsl User
	define("_SQL_PASS_", 			"DBPASS"); # MysQL Password
	define("_SQL_DB_", 				"DBNAME"); # MySQL Database				
				
	##########################################################################################################################################
	# Files to Read Master Zones from (Can be left unchanged with usual setup of bind and most crm systems)
	# These are the Domain Table files, where the domains and the location of the zone files will be fetched from.
	# You can choose 2 files, this pre-entered settings should mostly be fitting your a default bind9 configuration.
	##########################################################################################################################################								
	define("_CRON_BIND_FILE_",  			"/etc/bind/named.conf.default-zones");
	define("_CRON_BIND_FILE_2_",  			"/etc/bind/named.conf.local");

	##########################################################################################################################################
	# Fetch Local Master Domain Names by Zonesfiles in a Folder, Zonesfiles need to have the domain name included in filename.
	# THIS IS MEANT TO BE A FIX FOR ISPCONFIG, WHICH IS FAILING TO CREATE THE NAMED.CONF.LOCAL
	# THIS IS A FIX FOR ISPCONFIG BUT CAN BE USED FOR OTHER SYSTEMS
	# USE IT IF YOU HAVE NO FILES WITH LOCAL DOMAIN TABLES LIKE NAMED.CONF.LOCAL BUT A FOLDER WITH ZONE FILES.
	# You can leave this untouched if you do not need a fix to create a valid named.conf.local file...
	##########################################################################################################################################	
	define("_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_",  			4); // Offset Filename off Zonefiles at Start (example cut first 5 letters of zone.domain.file) (default for ispconfig is 4)
	define("_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_",  			0); // Offset Filename off Zonefiles at End  (example cut first 5 letters of zone.domain.file)
	// If the constant below is not -false- the function for search folder is activated.
	define("_CRON_FILES_FOLDER_FETCH_",  						false); // Folder to Search Files  (default for ispconfig is "/etc/bind/zones/")

	##########################################################################################################################################
	# Below Settings do not needs to be changed if you are using a default debian bind9/ubuntu system! (and may others)
	# If your service is called "named" and not "bind" you can change the service name below.
	# But as said, with a default bind9 installation it works (bind9)
	##########################################################################################################################################	
	/* Site Security Setup */				
	define("_IP_BLACKLIST_DAILY_OP_LIMIT_", 10000); # Define IP Blacklist Limit for IP Bans (10000 Recommended) // Can be reseted via daily cronjob.
	
	// Will be raised on wrong login and wrong token api requests! # Can be left unchanged	
	define("_CSRF_VALID_LIMIT_TIME_", 	10000); # Define Time for CSRF Validation	(10000 Recommended)	 # Can be left unchanged	
	
	// This is a key for form security!	 # Can be left unchanged	
	define("_COOKIES_",     		"dnshttp_"); # Cookie Prefix // Can be left unchanged
	
	// File Creation Related Settings
	define("_CRON_BIND_LIB_USER_", 			"bind"); // default is bind # Can be left unchanged	 # Created Hosts Files Owner User
	define("_CRON_BIND_LIB_GROUP_", 		"bind"); // default is bind # Can be left unchanged	 # Created Hosts Files Owner Group
	define("_CRON_BIND_LIB_CODE_", 			770); 	 // default is 770 # Can be left unchanged	 # Created Hosts Files Owner Chmod Permission Number
	define("_CRON_BIND_LIB_ENDING_", 		".dnshttp"); // default is ".dnshttp"	 # Can be left unchanged # File Ending of DNSHTTP Created Files, can be leaved unchanged!
	
	## Local Nameserver Service Settings
	define("_BIND_SERVICE_NAME_",  "bind9"); # Can be left unchanged / Name of the nameserver server (bind, named)
	
	# Command Name to Check Zones - Can be left unchagend!
	define("_BIND_CHECKZONE_COMMAND_",  "/usr/sbin/named-checkzone");
	
	# Command to Compile Zones # Can be left unchanged
	define("_BIND_COMPILEZONE_COMMAND_",  "/usr/sbin/named-compilezone");
	
	## Enable MySQL Logging page and MySQL Error Logging in Database at All?
	define("_MYSQL_LOGGING_", true); # Can be left unchanged!
	
	# Where should Files for DNS be stored ? (From master Servers fetched host files)
	define("_CRON_BIND_LIB_", 				"/etc/bind/dnshttp/"); # Can be left unchanged		
	
	## Determine Document Root - Leave unchanged!
	$current_dir = dirname(__FILE__);
	if(!file_exists($current_dir."/settings.php")) { $current_dir = $current_dir."/../";}
	if(!file_exists($current_dir."/settings.php")) { $current_dir = $current_dir."../";}
	if(!file_exists($current_dir."/settings.php")) { $current_dir = $current_dir."../";}
	define('_MAIN_PATH_', $current_dir);
	
	## Include Functions File - Do not Change!
	require_once(_MAIN_PATH_."/_instance/initialize.php");