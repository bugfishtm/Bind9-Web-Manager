<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Bind9 Web Manager Configuration File */
				//
				
	// This is a sample Configuration File for a Slave Server with bind9 installed [Only for Slave DNS Purpose]
				
	
	##########################################################################################################################################
	# Website Title and Impressum URL
	##########################################################################################################################################			
	define("_TITLE_", 				"NS2"); # A Imaginary Server Name to show at Title, can be unchanged
	define("_IMPRESSUM_",			"https://impressum"); # URL to your Impressum Website
	
	##########################################################################################################################################
	# MySQL Access Data
	##########################################################################################################################################	
	define("_SQL_HOST_", 			"127.0.0.1"); # Mysql Hostname
	define("_SQL_USER_", 			""); # Mysqsl User
	define("_SQL_PASS_", 			""); # MysQL Password
	define("_SQL_DB_", 				""); # MySQL Database					
			
	##########################################################################################################################################
	# Needed and fitting config for most Systems
	# Files to Read Master Zones from (Can be left unchanged with usual setup of bind and most crm systems)
	# These are the Domain Table files, where the domains and the location of the zone files will be fetched from.
	# You can choose 2 files, this pre-entered settings should mostly be fitting your a default bind9 configuration.
	##########################################################################################################################################		
	define("_CRON_BIND_FILE_",  			"/etc/bind/named.conf.default-zones");
	define("_CRON_BIND_FILE_2_",  			"/etc/bind/named.conf.local");

	##########################################################################################################################################
	# If you use ISPConfig change _CRON_FILES_FOLDER_FETCH_ to "/etc/bind/zones/"
	# Otherwhise this can be left unchanged, except if you need to fetch domain named by zone file names
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
	# If you are using Webmin/Virtualmin set this both to true, otherwhise to false!
	// Set this to true, if your CMS updates the zone file you have given in the bind config included and not theire own
	// This habit is only known for virtualmin/webmin, so if you are using that set this to true.
	// The software will then use the _CRON_BIND_FILE_ File to load domains.
	// The software will then use the _CRON_BIND_FILE_2_ too File to load domains.
	// All domains will be written then to _CRON_BIND_FILE_
	// If this is true, you need to include the _CRON_BIND_FILE_ in named.conf of bind! (and dnshttp.named.conf)
	define("_CRON_BIND_FILE_REWRITE_",  	false); 

	##########################################################################################################################################
	# Below Settings do not needs to be changed if you are using a default debian bind9/ubuntu system! (and may others)
	# If your service is called "named" and not "bind" you can change the service name below.
	# But as said, with a default bind9 installation it works (bind9)
	##########################################################################################################################################	
	## Bind Software Settings, can be left unchanged! (In a default bind environment with ubuntu/debian and may others)
	// File Creation Related Settings
	define("_CRON_BIND_LIB_USER_", 			"bind"); // default is bind # Can be left unchanged	 # Created Hosts Files Owner User
	define("_CRON_BIND_LIB_GROUP_", 		"bind"); // default is bind # Can be left unchanged	 # Created Hosts Files Owner Group
	define("_CRON_BIND_LIB_CODE_", 			770); 	 // default is 770 # Can be left unchanged	 # Created Hosts Files Owner Chmod Permission Number
	define("_CRON_BIND_LIB_ENDING_", 		".dnshttp"); // default is ".dnshttp"	 # Can be left unchanged # File Ending of DNSHTTP Created Files, can be leaved unchanged!	
	## Local Nameserver Service Settings
	define("_BIND_SERVICE_NAME_",  			"bind9"); # Can be left unchanged / Name of the nameserver server (bind, named)
	# Command Name to Check Zones - Can be left unchagend!
	define("_BIND_CHECKZONE_COMMAND_",  	"/usr/bin/named-checkzone");
	# Command to Compile Zones # Can be left unchanged
	define("_BIND_COMPILEZONE_COMMAND_",  	"/usr/bin/named-compilezone");
	## Enable MySQL Logging page and MySQL Error Logging in Database at All?
	define("_MYSQL_LOGGING_", 				true); # Can be left unchanged!
	# Where should Files for DNS be stored ? (From master Servers fetched host files)
	define("_CRON_BIND_LIB_", 				"/etc/bind/dnshttp/"); # Can be left unchanged		
	define("_CRON_BIND_CONFNAME_", 			"/etc/bind/named.conf"); # Can be left unchanged		
	
	## Some Site Settings - Can be left unchanged!
	// This is a key for form security!	 # Can be left unchanged	
	define("_COOKIES_",     		"dnshttp_"); # Cookie Prefix // Can be left unchanged	
	# Define IP Blacklist Limit for IP Bans (10000 Recommended) // Can be reseted via daily cronjob.		
	define("_IP_BLACKLIST_DAILY_OP_LIMIT_", 10000); 
	// Will be raised on wrong login and wrong token api requests! # Can be left unchanged	
	define("_CSRF_VALID_LIMIT_TIME_", 	10000); # Define Time for CSRF Validation	(10000 Recommended)	 # Can be left unchanged	
	
	##########################################################################################################################################
	# Do not change code Below, it is to load neeed Libraries and determine Site Root Path
	##########################################################################################################################################		
	## Determine Document Root - Leave unchanged!
	$current_dir = dirname(__FILE__);
	if(!file_exists($current_dir."/settings.php")) { $current_dir = $current_dir."/../";}
	if(!file_exists($current_dir."/settings.php")) { $current_dir = $current_dir."../";}
	if(!file_exists($current_dir."/settings.php")) { $current_dir = $current_dir."../";}
	define('_MAIN_PATH_', $current_dir);
	## Include Functions File - Do not Change!
	require_once(_MAIN_PATH_."/_instance/initialize.php");