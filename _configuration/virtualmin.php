<?php 
	/* 	
		.........%%%%%...%%..%%...%%%%...%%..%%..%%%%%%..%%%%%%..%%%%%..
		.........%%..%%..%%%.%%..%%......%%..%%....%%......%%....%%..%%.
		.........%%..%%..%%.%%%...%%%%...%%%%%%....%%......%%....%%%%%..
		.........%%..%%..%%..%%......%%..%%..%%....%%......%%....%%.....
		.........%%%%%...%%..%%...%%%%...%%..%%....%%......%%....%%.....
		................................................................
					PHP DNS Software by Jan-Maurice "Bugfish" Dahlmanns
	*/

	#	Copyright (C) 2026 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software: you can redistribute it and/or modify
	#	it under the terms of the GNU General Public License as published by
	#	the Free Software Foundation, either version 3 of the License, or
	#	(at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU General Public License for more details.

	#	You should have received a copy of the GNU General Public License
	#	along with this program.  If not, see <https://www.gnu.org/licenses/>.
	
	##########################################################################################################################################
	# Website Title and Impressum URL
	##########################################################################################################################################			
	define("_TITLE_", 				"NS1"); 				# A Imaginary Server Name to show at Title, can be unchanged
	define("_IMPRESSUM_",			"https://impressum"); 	# URL to your Impressum Website
	
	##########################################################################################################################################
	# MySQL Access Data
	##########################################################################################################################################	
	define("_SQL_HOST_", 			"127.0.0.1"); 	# Mysql Hostname
	define("_SQL_USER_", 			""); 			# Mysqsl User
	define("_SQL_PASS_", 			""); 			# MysQL Password
	define("_SQL_DB_", 				""); 			# MySQL Database			
			
	##########################################################################################################################################
	# Needed and fitting config for most Systems
	# Files to Read Master Zones from (Can be left unchanged with usual setup of bind and most crm systems)
	# These are the Domain Table files, where the domains and the location of the zone files will be fetched from.
	# You can choose 2 files, this pre-entered settings should mostly be fitting your a default bind9 configuration.
	##########################################################################################################################################		
	define("_CRON_BIND_FILE_2_",  			"/etc/bind/named.conf.default-zones");
	define("_CRON_BIND_FILE_",  			"/etc/bind/named.conf.local");

	##########################################################################################################################################
	# If you use ISPConfig change _CRON_FILES_FOLDER_FETCH_ to "/etc/bind/zones/"
	# Otherwhise this can be left unchanged, except if you need to fetch domain named by zone file names
	# Fetch Local Master Domain Names by Zonesfiles in a Folder, Zonesfiles need to have the domain name included in filename.
	# THIS IS MEANT TO BE A FIX FOR ISPCONFIG, WHICH IS FAILING TO CREATE THE NAMED.CONF.LOCAL
	# THIS IS A FIX FOR ISPCONFIG BUT CAN BE USED FOR OTHER SYSTEMS
	# USE IT IF YOU HAVE NO FILES WITH LOCAL DOMAIN TABLES LIKE NAMED.CONF.LOCAL BUT A FOLDER WITH ZONE FILES.
	# You can leave this untouched if you do not need a fix to create a valid named.conf.local file...
	##########################################################################################################################################	
	define("_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_",  			4); 		# Offset Filename off Zonefiles at Start (example cut first 5 letters of zone.domain.file) (default for ispconfig is 4)
	define("_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_",  			0); 		# Offset Filename off Zonefiles at End  (example cut first 5 letters of zone.domain.file)
	define("_CRON_FILES_FOLDER_FETCH_",  						false); 	# Folder to Search Files (default for ispconfig is "/etc/bind/zones/") // Set to false to deactivate

	##########################################################################################################################################
	# If you are using Webmin/Virtualmin set this both to true, otherwhise to false!
	# Set this to true, if your CMS updates the zone file you have given in the bind config included and not theire own
	# This habit is only known for virtualmin/webmin, so if you are using that set this to true.
	# The software will then use the _CRON_BIND_FILE_ File to load domains.
	# The software will then use the _CRON_BIND_FILE_2_ too File to load domains.
	# All domains will be written then to _CRON_BIND_FILE_
	# If this is true, you need to include the _CRON_BIND_FILE_ in named.conf of bind! (and dnshttp.named.conf)
	##########################################################################################################################################
	define("_CRON_BIND_FILE_REWRITE_",  	true); 

	##########################################################################################################################################	
	## File System Bind Software Settings, can be left unchanged! (In a default bind environment with ubuntu/debian and may others)
	##########################################################################################################################################	
	define("_CRON_BIND_LIB_USER_", 			"bind"); 		# default is bind # Can be left unchanged	 # Created Hosts Files Owner User
	define("_CRON_BIND_LIB_GROUP_", 		"bind"); 		# default is bind # Can be left unchanged	 # Created Hosts Files Owner Group
	define("_CRON_BIND_LIB_CODE_", 			770); 	 		# default is 770 # Can be left unchanged	 # Created Hosts Files Owner Chmod Permission Number
	define("_CRON_BIND_LIB_ENDING_", 		".dnshttp"); 	# default is ".dnshttp"	 # Can be left unchanged # File Ending of DNSHTTP Created Files, can be leaved unchanged!	

	##########################################################################################################################################	
	## Local Bind Service Settings, can be left unchanged! (In a default bind environment with ubuntu/debian and may others)
	##########################################################################################################################################		
	define("_BIND_SERVICE_NAME_",  			"bind9"); 						# Local Nameserver Service Settings # Can be left unchanged / Name of the nameserver server (bind, named)
	define("_BIND_CHECKZONE_COMMAND_",  	"/usr/bin/named-checkzone"); 	# Command Name to Check Zones - Can be left unchagend!
	define("_BIND_COMPILEZONE_COMMAND_",  	"/usr/bin/named-compilezone"); 	# Command to Compile Zones # Can be left unchanged
	define("_CRON_BIND_LIB_", 				"/etc/bind/dnshttp/"); 			# Where should Files for DNS be stored ? (From master Servers fetched host files) # Can be left unchanged		
	define("_CRON_BIND_CONFNAME_", 			"/etc/bind/named.conf");		# Can be left unchanged		

	##########################################################################################################################################	
	## Various Website Settings
	##########################################################################################################################################	
	define("_COOKIES_",     					"dnshttp_"); 				# Cookie Prefix // Can be left unchanged	
	define("_IP_BLACKLIST_DAILY_OP_LIMIT_", 	10000);  					# Define IP Blacklist Limit for IP Bans (10000 Recommended) // Can be reseted via daily cronjob.		
	define("_CSRF_VALID_LIMIT_TIME_", 			10000); 					# Define Time for CSRF Validation	(10000 Recommended)	 # Can be left unchanged	
	define("_USER_AUTOBLOCK_", 					1000000); 					# Tries with wrong password before user gets locked	(1000000 Recommended)	 # Can be left unchanged	
	define("_HELP_",							"https://bugfishtm.github.io/Bind9-Web-Manager/");		# URL used for the "Help" Link in Footer Area
	define("_DNSHTTP_LOGO_", 					"./_assets/_img/logo_alpha.png");						# Relative Website Logo URL (You can store your own logo in _data and link it here.
	define("_DNSHTTP_FAVICON_", 				"./favicon.ico");										# Relative Website Favicon URL (You can store your own logo in _data and link it here.
	define("_FOOTER_", 							"Bind9 Web Manager by Bugfish");						# Extra text string to display in websites footer area.
	define("_COOKIEBANNER_TEXT_", "This website is using session cookies for critical operations!");	# Text to display in the simple non-dsgvo conform cookie-banner (website only uses session cookies which is fine)
	
	##########################################################################################################################################	
	## Custom User Domain Creation Setup (Can be left unchanged in most cases)
	##########################################################################################################################################	
	define("_USER_DOMAIN_EXPIRE_", 		"604800");				# Default Domain Expire Value for Custom User Domains
	define("_USER_DOMAIN_RETRY_", 		"540");					# Default Domain Retry Value for Custom User Domains
	define("_USER_DOMAIN_REFRESH_", 	"7200");				# Default Domain Refresh Value for Custom User Domains
	define("_USER_DOMAIN_MINIMUM_", 	"3600");				# Default Domain Minimum Value for Custom User Domains
	define("_USER_DOMAIN_MAIL_", 		"postmaster@{domain}");	# Default Domain Contact Value for Custom User Domains
	
	##########################################################################################################################################	
	## Other Variables
	##########################################################################################################################################	
	define("_DNSHTTP_LOGIN_BG_", "./_assets/_img/login_bg.jpg"); # You can overwrite the login background path to your own
	define("_SERVER_HOSTNAME_", "your.server.fqdn"); # Your Servers Hostname without www and http/https
