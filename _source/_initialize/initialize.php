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
	
	/*************************************************************************
		Disable Hardlinking
	*************************************************************************/
	if(!defined("_SQL_USER_")) { @http_response_code(404); Header("Location: ../"); exit(); }
	
	/*************************************************************************
		General Site Variables
	*************************************************************************/
	define('_GITHUB_',   			"https://github.com/bugfishtm/"); 										# URL to Documentation
	define("_VERSION_", 			'4.1.1');																# Current Actual Code Version
	
	/*************************************************************************
		Docker Define Var for installations
	*************************************************************************/	
	if(!defined("_DNSHTTP_DOCKERIZED_")) { define("_DNSHTTP_DOCKERIZED_", true); }
	
	/*************************************************************************
		Extended Sync Cron Configuration Variables
	*************************************************************************/
	define("_CRON_BIND_FILE_CONFIG_DNSHTTP_",  	_CRON_BIND_LIB_);	# Can be left unchanged	/ Path to save Configuration Files to
	define("_CRON_BIND_FILE_TABLE_", 			_CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.named.conf.local"); # Can be left unchanged	
	define("_CRON_BIND_FILE_CONFIG_", 			_CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.named.conf.options"); # Can be left unchanged	
	define("_CRON_BIND_FILE_TEMP_",  			_CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.zone.temp.file"); # Can be left unchanged	
	define("_CRON_BIND_FILE_LOAD_",   			_CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.named.conf"); # Can be left unchanged	
	define("_BIND_CONFIG_OPTIONS_",  "options {\r\n\tdirectory \"/var/lib/bind\";\r\n\tdnssec-validation auto;\r\n\tlisten-on-v6 { any; };\r\n\tversion \"None Available\";\r\n\trecursion no;\r\n\tquerylog yes;\r\n\tlisten-on { 0.0.0.0/0; };\r\n\tallow-update { 127.0.0.1;};\r\n\tallow-query { any; };\r\n\tcheck-names master ignore;\r\n\tcheck-names slave ignore;\r\n\tnotify no;\r\n\tallow-transfer { 127.0.0.1; };check-names response ignore;\r\n};\r\n"); # Can be left unchanged // Default Config to Set up in DnsHTTP (Can be changed in file later if needed, which is in te _CRON_BIND_FILE_CONFIG_DNSHTTP_ folder!)
	
	/*************************************************************************
		Determine Main Path
	*************************************************************************/
	$current_dir = dirname(__FILE__);
	if(!file_exists($current_dir."/_initialize/t78giuaedfUbi389ASDbu2awsdad4tz.php")) { $current_dir = $current_dir."/../";}
	if(!file_exists($current_dir."/_initialize/t78giuaedfUbi389ASDbu2awsdad4tz.php")) { $current_dir = $current_dir."/../";}
	if(!file_exists($current_dir."/_initialize/t78giuaedfUbi389ASDbu2awsdad4tz.php")) { $current_dir = $current_dir."/../";}
	define('_MAIN_PATH_', $current_dir);
	
	/*************************************************************************
		Include DNSHTTP Function Library
	*************************************************************************/
	require_once(_MAIN_PATH_."/_initialize/library.php");
	
	/*************************************************************************
		Include important Classes and Functions
	*************************************************************************/
	foreach (glob(_MAIN_PATH_."/_framework/functions/x_*.php") as $filename){require_once $filename;}
	foreach (glob(_MAIN_PATH_."/_framework/classes/x_*.php") as $filename){require_once $filename;}	
	
	/*************************************************************************
		Check for Required PHP Modules
	*************************************************************************/
	$debug = new x_class_debug();
	$debug->required_php_module("mysqli", true);
	$debug->required_php_module("json", true);
	$debug->required_php_module("mbstring", true);
	$debug->required_php_module("curl", true);
	$debug->required_php_module("intl", true);
	$debug->required_php_module("session", true);
	
	/*************************************************************************
		Table Constant Name Definitions
	*************************************************************************/
	define('_TABLE_PREFIX_',  		"dnshttp_");	
	define('_TABLE_LOG_MYSQL_',		_TABLE_PREFIX_."mysql_log");		
	define('_TABLE_USER_',   		_TABLE_PREFIX_."user");  			
	define('_TABLE_USER_SESSION_',	_TABLE_PREFIX_."user_session");		
	define('_TABLE_LOG_',			_TABLE_PREFIX_."log");		
	define('_TABLE_DOMAIN_REG_',	_TABLE_PREFIX_."reg_domain");
	define('_TABLE_DOMAIN_BIND_',	_TABLE_PREFIX_."bind_domain");
	define('_TABLE_DOMAIN_API_',	_TABLE_PREFIX_."api_domain");
	define('_TABLE_DOMAIN_REC_',	_TABLE_PREFIX_."record");
	define('_TABLE_SERVER_',		_TABLE_PREFIX_."server");
	define('_TABLE_IPBL_',			_TABLE_PREFIX_."ipblacklist");
	define('_TABLE_PERM_',			_TABLE_PREFIX_."perms");
	define('_TABLE_CONFLICT_',		_TABLE_PREFIX_."conflict");
	
	/*************************************************************************
		Restore .htaccess if not existant
	*************************************************************************/	
	if(!file_exists(_MAIN_PATH_."/.htaccess")) {
		copy(_MAIN_PATH_."/_default/default.htaccess", _MAIN_PATH_."/.htaccess");
	}
	
	/*************************************************************************
		Restore Logs Folder and Redirectors in Data Folder
	*************************************************************************/	
	if (!is_dir(_MAIN_PATH_.'/_data/_logs')) mkdir('folder', 0770, true);
	if(!file_exists(_MAIN_PATH_."/_data/index.php")) {
		copy(_MAIN_PATH_."/_default/default_redirector.php", _MAIN_PATH_."/_data/index.php");
	}
	if(!file_exists(_MAIN_PATH_."/_data/_logs/index.php")) {
		copy(_MAIN_PATH_."/_default/default_redirector.php", _MAIN_PATH_."/_data/_logs/index.php");
	}
	if(!file_exists(_MAIN_PATH_."/_data/_logs/.htaccess")) {
		copy(_MAIN_PATH_."/_default/deny.htaccess", _MAIN_PATH_."/_data/_logs/.htaccess");
	}
	
	/*************************************************************************
		Build MySQL Connection
	*************************************************************************/
	$mysql = new x_class_mysql(_SQL_HOST_, _SQL_USER_, _SQL_PASS_, _SQL_DB_);
	if ($mysql->lasterror != false) { $mysql->displayError(true); } 
	$mysql->log_config(_TABLE_LOG_MYSQL_, "log"); 
	
	/*************************************************************************
		Check for Required PHP Modules
	*************************************************************************/
	$log = new x_class_log($mysql, _TABLE_LOG_, "site");
	
	/*************************************************************************
		IP Blacklisting Class
	*************************************************************************/
	$ipbl = new x_class_ipbl($mysql, _TABLE_IPBL_, _IP_BLACKLIST_DAILY_OP_LIMIT_);		
	
	/*************************************************************************
		User Class
	*************************************************************************/
	$user = new x_class_user($mysql, _TABLE_USER_, _TABLE_USER_SESSION_, _COOKIES_ , "admin", "changeme", 0);
	$user->multi_login(false);
	$user->login_recover_drop(true);
	$user->login_field_user();
	$user->mail_unique(false);
	$user->user_unique(true);
	$user->log_ip(true);
	$user->log_activation(false);
	$user->log_session(false);
	$user->log_recover(false);
	$user->log_mail_edit(false);
	$user->sessions_days(7);
	$user->autoblock(_USER_AUTOBLOCK_);
	$user->init();			
	
	/*************************************************************************
		Add field for User API Key
	*************************************************************************/
	$user->user_add_field(" ext_api_key varchar(128) NULL COMMENT 'External API Key for Requests'");
	$user->user_add_field(" last_activity datetime NULL COMMENT 'Upgrade Required'");
	
	/*************************************************************************
		Add field for outdated Log Class
	*************************************************************************/
	mysqli_report(MYSQLI_REPORT_OFF);
	$mysql->query("ALTER TABLE "._TABLE_LOG_." ADD COLUMN ref VARCHAR(255); ");
	$mysql->query("ALTER TABLE "._TABLE_DOMAIN_BIND_." ADD COLUMN serial_c VARCHAR(24) DEFAULT NULL; ");
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	
	/*************************************************************************
		Build MySQL Databases
	*************************************************************************/
	
		/*********************************************************************
			Domain Conflict Table
		*********************************************************************/
		$mysql->query("CREATE TABLE IF NOT EXISTS `"._TABLE_CONFLICT_."` (
			  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
			  `domain` varchar(256) NOT NULL COMMENT 'Domain Name',
			  `servers` longtext COMMENT 'Array with Conflicting Servers and Users',
			  `solved` longtext DEFAULT NULL COMMENT 'Related Relay or User to Use in Favor',
			  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
			  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
			  PRIMARY KEY (`id`))"); $mysql->free_all();			
			
		/*********************************************************************
			Server Table
		*********************************************************************/
		if(!$mysql->table_exists(_TABLE_SERVER_)) { $mysql->query("CREATE TABLE IF NOT EXISTS `"._TABLE_SERVER_."` (
			  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
			  `ip` VARCHAR(255) NULL COMMENT 'Server IPv4',
			  `ip6` VARCHAR(255) NULL DEFAULT '' COMMENT 'Server IPv6',
			  `api_path` varchar(700) NOT NULL COMMENT 'APIs Website URL',
			  `api_token` varchar(512) NOT NULL COMMENT 'APIs Connection Token',
			  `server_type` tinyint(1) NOT NULL COMMENT '1 - Master Server | 2 - Slave Server | 3 = Both',
			  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date | Auto Set',
			  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Modification Date | Auto Set',
			  `fk_user` int(10) DEFAULT NULL COMMENT 'User which created the server!',
			  `enabled` tinyint(1) DEFAULT 1 COMMENT '1 - Enabled | 0 - Disabled',
			  `apiok` tinyint(1) DEFAULT 1 COMMENT '1 - API Connection Found | 2 - API Connection Found with GZip | 0 - API Connection Fail',
			  `domains` int(10) DEFAULT 1 COMMENT 'Number of Domains this Server is serving!',
			  `weblacklisted` tinyint(1) NULL DEFAULT 2 COMMENT '0 - Not Blacklisted | 1 if last Request resulted in we are blacklisted on the other site | 2 - Waiting for Cron',
			  `emptydomains` tinyint(1) NULL DEFAULT 2 COMMENT '0 - Not Empty Domain Table | 1 if last Request resulted in empty Domains | 2 - Waiting for Cron',
			  `tokenbadlastreq` tinyint(1) NULL DEFAULT 2 COMMENT '0 - Token seems ok | 1 if last Request resulted in back Token | 2 - Waiting for Cron',		  
			  PRIMARY KEY (`id`), UNIQUE KEY `api_path` (`api_path`) )"); $mysql->free_all();}	
			  
		/*********************************************************************
			External Domains Table
		*********************************************************************/	
		if(!$mysql->table_exists(_TABLE_DOMAIN_API_)) { $mysql->multi_query("CREATE TABLE IF NOT EXISTS `"._TABLE_DOMAIN_API_."` (	  
			  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
			  `domain` varchar(260) NOT NULL COMMENT 'Slave Servers Domain Name',		  
			  `content` longtext COMMENT 'Slave Servers Domain Content', 
			  `update_message` longtext NULL COMMENT 'A message just for some updates of sync.php - mostly empty',
			  `last_update` datetime DEFAULT NULL COMMENT 'Last Update Date',
			  `fk_server` int DEFAULT NULL COMMENT 'Related DNS Server',	
			  `conflict` int DEFAULT 0 COMMENT '1 if Confliced with another Server Master or User Domain | 0 is Okay',
			  `zonecheck` int DEFAULT 0 COMMENT '0 if current Zone-Config is ERROR | 1 is Okay',
			  `zonecheck_message` longtext NULL COMMENT 'Output Check Message of the Current Active Zone',
			  `zonecheck_failmessage` longtext NULL COMMENT 'Output Check Message of the Last Checked Zone',
			  `preferred` int DEFAULT 0 COMMENT '1 is preferred to solve a conflict | 0 is not preferred',
			  `registered` int DEFAULT 0 COMMENT '1 is registered and running in bind local | 0 is not running',
			  `okonce` int DEFAULT 0 COMMENT '1 if has ever been a valid zone | 0 is never been a valid zone',
			  `oldzonefallback` int DEFAULT 0 COMMENT '1 new zone invalid, falling back to old | 0 new zone ok',	  
			  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain First Creation Date',
			  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Update Date',
			PRIMARY KEY (`id`)); ALTER TABLE `"._TABLE_DOMAIN_API_."` ADD UNIQUE `unique_index_apidomain`(`domain`, `fk_server`);"); $mysql->free_all();}		
			
		/*********************************************************************
			Internal Active Domains Table
		*********************************************************************/	
		if(!$mysql->table_exists(_TABLE_DOMAIN_BIND_)) { $mysql->multi_query("CREATE TABLE IF NOT EXISTS `"._TABLE_DOMAIN_BIND_."` (
			  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
			  `domain` varchar(260) NOT NULL COMMENT 'Related Domain Name',
			  `domain_type` varchar(260) DEFAULT 0 COMMENT 'file - Fetched from Table File | file2 - Fetched from Secondary Table File | folder - Fetched from Fetch Folder | user - User Domain',
			  `zone_path` text NULL COMMENT 'Path to Zone File if it has been fetched locally!',
			  `content` longtext NULL COMMENT 'Local Registred Domain Content',
			  `update_message` longtext NULL COMMENT 'A message just for some updates of sync.php - mostly empty',
			  `last_update` datetime DEFAULT NULL COMMENT 'Domain Update Date',
			  `fk_user` int(9) DEFAULT NULL COMMENT 'If here is an Int, this is ID of User who owns this Domain',
			  `serial_c` varchar(24) DEFAULT NULL COMMENT 'Current Serial SOA',
			  `conflict` int DEFAULT 0 COMMENT '1 if Confliced with another Server Master Domain | 0 is Okay',
			  `zonecheck` int DEFAULT 0 COMMENT '0 if current Zone-Config is ERROR | 1 is Okay',
			  `zonecheck_message` longtext NULL COMMENT 'Output Check Message of the Current Active Zone',
			  `zonecheck_failmessage` longtext NULL COMMENT 'Output Check Message of the Last Checked Zone',
			  `preferred` int DEFAULT 0 COMMENT '1 is preferred to solve a conflict | 0 is not preferred',
			  `registered` int DEFAULT 0 COMMENT '1 is registered and running in bind local | 0 is not running',
			  `okonce` int DEFAULT 0 COMMENT '1 if has ever been a valid zone | 0 is never been a valid zone',
			  `oldzonefallback` int DEFAULT 0 COMMENT '1 new zone invalid, falling back to old | 0 new zone ok',
			  `set_no_replicate` int(1) DEFAULT 0 COMMENT '1 - No Replication to Slaves | 0 - Replicate to Slaves (default)',
			  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain Entry Date | Auto Set',
			  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Domain Update Date | Auto Set',		  
			PRIMARY KEY (`id`)); ALTER TABLE `"._TABLE_DOMAIN_BIND_."` ADD UNIQUE `unique_index_binddomain`(`domain`, `domain_type`);"); $mysql->free_all();}		
			
		/*********************************************************************
			Registered Domains List
		*********************************************************************/	
		if(!$mysql->table_exists(_TABLE_DOMAIN_REG_)) { $mysql->multi_query("CREATE TABLE IF NOT EXISTS `"._TABLE_DOMAIN_REG_."` (
			  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
			  `domain` varchar(260) NOT NULL COMMENT 'Related Domain Name',
			  `content` longtext NULL COMMENT 'Local Registred Domain Content',
			  `time_data_removal` varchar(256) DEFAULT 0 COMMENT 'Value for Sync Removal of Old Domains',
			  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain Entry Date | Auto Set',
			  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Domain Update Date | Auto Set',		  
			PRIMARY KEY (`id`)); ALTER TABLE `"._TABLE_DOMAIN_REG_."` ADD UNIQUE `unique_index_regdomain`(`domain`);"); $mysql->free_all();}	
			
		/*********************************************************************
			Registered Domains List
		*********************************************************************/	
		if(!$mysql->table_exists(_TABLE_DOMAIN_REC_)) { $mysql->multi_query("CREATE TABLE IF NOT EXISTS `"._TABLE_DOMAIN_REC_."` (
			`id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`domain_name`  VARCHAR(255)    NOT NULL,
			`record_name`  VARCHAR(255)    NOT NULL,
			`record_type`  VARCHAR(64) NOT NULL,
			`record_data`  TEXT            NOT NULL,
			`ttl`          INT UNSIGNED    NOT NULL DEFAULT 3600,
			`priority`     SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'MX, SRV priority',
			`weight`       SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'SRV weight',
			`port`         SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'SRV port',
			`target`       VARCHAR(255)      NULL DEFAULT NULL COMMENT 'SRV target domain',
			`enabled`      TINYINT(1)      NOT NULL DEFAULT 1,
			`fk_domain` int(11) DEFAULT 0 COMMENT 'The External Domain',
			`creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain Entry Date | Auto Set',
			`modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Domain Update Date | Auto Set'); "); $mysql->free_all();}	