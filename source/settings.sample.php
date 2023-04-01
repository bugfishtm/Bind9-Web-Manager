<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Bind9 Web Manager Configuration File */
	##########################################################################################################################################
	# Below are settings which may be changed to adjust the configuration and enter mysql authentication info!
	##########################################################################################################################################
	/* ########################################## */
	/* Website Setup
	/* ########################################## */				
	define("_TITLE_", 				"TITLE"); # A Imaginary Server Name to show at Title, can be unchanged	 		
	define("_COOKIES_",     		"dnshttp_"); # Cookie Prefix // Can be unchanged			
	define("_MAIN_PATH_",			"/var/www/html/"); # Main Document root for website! Needs to be corrent for website to run!		
	define("_HOSTNMAE_",			"HOSTNAME"); # This Servers DNS Hostname (example: ns1.example ); Needed if Users create manual domains, not needed for replications	
				
	/* ########################################## */
	/* Database Setup
	/* ########################################## */				
	define("_SQL_HOST_", 			"DBHOST"); # Mysql Hostname
	define("_SQL_USER_", 			"DBUSER"); # Mysqsl User
	define("_SQL_PASS_", 			"DBPASS"); # MysQL Password
	define("_SQL_DB_", 				"DBNAME"); # MySQL Database				
				
	/* ########################################## */
	/* Site Security Setup
	/* ########################################## */				
	define("_IP_BLACKLIST_DAILY_OP_LIMIT_", 500); # Define Blacklist Limit for IP Bans (500 Recommended) // Can be reseted via daily cronjob.
	define("_CSRF_VALID_LIMIT_TIME_", 	500); # Define Time for CSRF Validation	(500 Recommended)	 # Can be left unchanged				
				
	##########################################################################################################################################
	# Some Settings about the DNS Manager and User Created Domains
	##########################################################################################################################################						
	##########################################################################################################################################
	# Settings for Permission Follow, this is for the files created by this software (dns zone files and list files)
	##########################################################################################################################################					
	# Created Hosts Files Owner User
	define("_CRON_BIND_LIB_USER_", 			"bind"); // default is bind
	# Created Hosts Files Owner Group
	define("_CRON_BIND_LIB_GROUP_", 		"bind"); // default is bind
	# Created Hosts Files Owner Chmod Permission Number
	define("_CRON_BIND_LIB_CODE_", 			770); 	 // default is 770
	# File Ending of DNSHTTP Created Files, can be leaved unchanged!
	define("_CRON_BIND_LIB_ENDING_", 		".dnshttp"); // default is ".dnshttp"

	##########################################################################################################################################
	# Folder Where Created Slave Zone Files are Stored
	##########################################################################################################################################
	# Where should Files for DNS be stored ? (From master Servers fetched host files)
	define("_CRON_BIND_LIB_", 				_MAIN_PATH_."/_temp/"); # Can be löeft unchanged	
			
	##########################################################################################################################################
	# Settings for DNS Replication follows, this is urgent to be checked before runnig this software!
	##########################################################################################################################################								
	/* ########################################## */
	/* File to Fetch Local Master Domains (default: /var/bind/named.conf.local)	
	/* Path to Domain Listing File (Default is named.conf.local in bind Folder) To Fetch Domains for Slave Servers (can be ignored on slave servers)
	/* If _CRON_FILES_FOLDER_FETCH_ is set to a folder with zone files, this file will be replaced upon cronjob run with fetched data. More see below.
	/* Normally, you would type in your named.conf.local file here, if this is the file where your dns CRM or whatever stores your domain list
	/* If you are not using another crm or dns manager software, leave this to named.conf.local (on a default bind9 installation
	/* ########################################## */
	define("_CRON_BIND_FILE_",  			"/etc/bind/named.conf.local");
	
	/* ########################################## */
	/* File to Write Fetched Slave Domains for Bind Into (Like in named.conf but has to be another file!!!!)
	/* Name of File for DNS Names, has to be included into named.conf.options (see readme!)
	/* ########################################## */		
	define("_CRON_BIND_FILE_DNSHTTP_",  	"/etc/bind/dnshttp.conf.local");		
	
	/* ########################################## */
	/* OPTIONAL OPTIONAL OPTIONAL: DNS FIX IF DNS CRM DOES FAIL TO WRITE LIST OF ZONES TO NAMED.CONF.LOCAL
	/* IF YOU SET FALSE TO THE PATH OF THE TEMPLATE FILES OF YOUR ZONES, THE SCRIPT WILL FETCH LOCAL MASTER FOMAINS
	/* FROM TEMPLATE FILE NAME AND PUT THEM INTO DATABASE, INSTEAD FETCHING FROM THE NAMED.CONF.LOCAL OR CONFIGURATED FILE
	/* ABOVE, THE SCRIPT WILL REPLACE THE FILE WITH THE FETCHED DATA IF THE VALUE IS NOT FALSE BELOW. IT WILL FETCH THE 
	/* DOMAIN INFO FROM FILE INSTEAD, IF OPPOSITE. (i hope you get what i mean, sorry)
	/* ########################################## */	
	#define("_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_",  			4); // Poststring after Hosts File Name (default is ".hosts")
	#define("_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_",  			0); // Poststring after Hosts File Name (default is ".hosts")
	#define("_CRON_FILES_FOLDER_FETCH_",  						"/etc/bind/zones/"); // Poststring after Hosts File Name (default is ".hosts")
	#define("_CRON_BIND_FILE_FETCHED_",  						"/etc/bind/named.conf.ispconfig.local");

	/* ########################################## */
	/* DO NOT CHANGE THE VALUES BELOW!
	/* ########################################## */
	## Include Functions File - Do not Change!
	require_once(_MAIN_PATH_."/functions.php");
	
	/* ########################################## */
	/* Default Values for User Created Domains // IS NOT IN USE
	/* ########################################## */
	# single domain zone expire, seconds	
	define('_USER_DOMAIN_EXPIRE_',    604800);	 # Can be left unchanged / Useless at the Moment
	# single domain zone retry, seconds
	define('_USER_DOMAIN_RETRY_',    540);	 # Can be left unchanged   / Useless at the Moment
	# single domain zone refresh, seconds
	define('_USER_DOMAIN_REFRESH_',    7200);  # Can be left unchanged	  / Useless at the Moment
	# single domain zone minimum, seconds
	define('_USER_DOMAIN_MINIMUM_',    3600);	 # Can be left unchanged	 / Useless at the Moment 	
