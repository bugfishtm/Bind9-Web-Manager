<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Bind9 Web Manager Init File */

	/* Variables */	
	define('_HELP_',    "https://bugfishtm.github.io/Bind9-Web-Manager/"); 
	define("_SLAVE_AS_MASTER_DOMAIN_",  false); // Not Configured DO NEVER CHANGE!	
	define("_FOOTER_", '<div id="footer">DnsHTTPv3.7 by <a href="https://bugfish.eu/aboutme" target="_blank" rel="noopeener">Bugfish</a> | <a href="'._IMPRESSUM_.'" target="_blank" rel="noopeener">Impressum</a> | <a href="'._HELP_.'" target="_blank" rel="noopeener">Help</a>  </div>');
	define("_CRON_DEBUG_", 2);
	define("_CRON_BIND_FILE_CONFIG_DNSHTTP_",  	_CRON_BIND_LIB_);	# Can be left unchanged	/ Path to save Configuration Files to
	# The Initial Bind9 Configuration
	define("_BIND_CONFIG_OPTIONS_",  "options {\r\n\tdirectory \"/var/lib/bind\";\r\n\tdnssec-validation auto;\r\n\tlisten-on-v6 { any; };\r\n\tversion \"None Available\";\r\n\trecursion no;\r\n\tquerylog yes;\r\n\tlisten-on { 0.0.0.0/0; };\r\n\tallow-update { 127.0.0.1;};\r\n\tallow-query { any; };\r\n\tcheck-names master ignore;\r\n\tcheck-names slave ignore;\r\n\tnotify no;\r\n\tallow-transfer { 127.0.0.1; };check-names response ignore;\r\n};\r\n"); # Can be left unchanged // Default Config to Set up in DnsHTTP (Can be changed in file later if needed, which is in te _CRON_BIND_FILE_CONFIG_DNSHTTP_ folder!)
	
	## Defines for User Created Domains // IS NOT IN USE YET
	# single domain zone expire, seconds	
	define('_USER_DOMAIN_EXPIRE_',    604800);	# Can be left unchanged / Useless at the Moment
	# single domain zone retry, seconds
	define('_USER_DOMAIN_RETRY_',    540);# Can be left unchanged / Useless at the Moment
	# single domain zone refresh, seconds
	define('_USER_DOMAIN_REFRESH_',    7200); # Can be left unchanged / Useless at the Moment
	# single domain zone minimum, seconds
	define('_USER_DOMAIN_MINIMUM_',    3600);# Can be left unchanged / Useless at the Moment 	
	# single domain zone minimum, seconds
	define('_USER_DOMAIN_MAIL_',    "postmaster@{domain}");	 # Can be left unchanged / Useless at the Moment 

	/* Constants with Website Table Names to be used */	
	define('_TABLE_PREFIX_',  		"dnshttp_");	
	define('_TABLE_USER_',   		_TABLE_PREFIX_."user");  
	define('_TABLE_USER_SESSION_',	_TABLE_PREFIX_."user_session");
	define('_TABLE_DOMAIN_BIND_',	_TABLE_PREFIX_."bind_domain");
	define('_TABLE_DOMAIN_API_',	_TABLE_PREFIX_."api_domain");
	define('_TABLE_SERVER_',		_TABLE_PREFIX_."server");
	define('_TABLE_IPBL_',			_TABLE_PREFIX_."ipblacklist");
	define('_TABLE_PERM_',			_TABLE_PREFIX_."perms");
	define('_TABLE_CONFLICT_',		_TABLE_PREFIX_."conflict");
	define('_TABLE_LOG_',			_TABLE_PREFIX_."log");	
	define('_TABLE_NOTIFY_',		_TABLE_PREFIX_."notify");	
	define('_TABLE_LOG_MYSQL_',		_TABLE_PREFIX_."mysql_log");		
		
	/* Rename dot.htaccess to .htaccess if Main Path is in Website Folder */		
	if(@file_exists(_MAIN_PATH_."/dot.htaccess") AND !file_exists(_MAIN_PATH_."/.htaccess")) { @unlink(_MAIN_PATH_."/.htaccess"); @rename(_MAIN_PATH_."/dot.htaccess", _MAIN_PATH_."/.htaccess"); }	
	
	/* Settings for Captcha Generation */	
	define('_CAPTCHA_FONT_',   	 _MAIN_PATH_."/_style/font_captcha.ttf");
	define('_CAPTCHA_WIDTH_',    "200"); 
	define('_CAPTCHA_HEIGHT_',   "70");	
	define('_CAPTCHA_SQUARES_',   mt_rand(4, 12));	
	define('_CAPTCHA_ELIPSE_',    mt_rand(4, 12));	
	define('_CAPTCHA_RANDOM_',    mt_rand(1000, 9999));		
	
	/* Config File Name Variables */
	define("_CRON_BIND_FILE_TABLE_",  _CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.named.conf.local"); # Can be left unchanged	
	define("_CRON_BIND_FILE_CONFIG_", _CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.named.conf.options"); # Can be left unchanged	
	define("_CRON_BIND_FILE_TEMP_",   _CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.zone.temp.file"); # Can be left unchanged	
	define("_CRON_BIND_FILE_LOAD_",   _CRON_BIND_FILE_CONFIG_DNSHTTP_."/dnshttp.named.conf"); # Can be left unchanged	
	
	/* Includes of Important Classes and Functions */	
	foreach (glob(_MAIN_PATH_."/_framework/functions/x_*.php") as $filename){require_once $filename;}
	foreach (glob(_MAIN_PATH_."/_framework/classes/x_*.php") as $filename){require_once $filename;}	
	
	/* Init x_class_mysql Class */
	$mysql = new x_class_mysql(_SQL_HOST_, _SQL_USER_, _SQL_PASS_, _SQL_DB_);
	if ($mysql->lasterror != false) { $mysql->displayError(true); } 
	if(_MYSQL_LOGGING_) { $mysql->log_config(_TABLE_LOG_MYSQL_, "log"); }		
		
	/* Rebuild Table Structure	*/
	if(!$mysql->table_exists(_TABLE_DOMAIN_BIND_)) { $mysql->multi_query("CREATE TABLE IF NOT EXISTS `"._TABLE_DOMAIN_BIND_."` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `domain` varchar(260) NOT NULL COMMENT 'Related Domain Name',
		  `domain_type` varchar(260) DEFAULT 0 COMMENT 'file - Fetched from Table File | file2 - Fetched from Secondary Table File | folder - Fetched from Fetch Folder | user - User Domain',
		  `zone_path` text NULL COMMENT 'Path to Zone File if it has been fetched locally!',
		  `content` text NULL COMMENT 'Local Registred Domain Content',
		  `update_message` text NULL COMMENT 'A message just for some updates of sync.php - mostly empty',
		  `last_update` datetime DEFAULT NULL COMMENT 'Domain Update Date',
		  `fk_user` int(9) DEFAULT NULL COMMENT 'If here is an Int, this is ID of User who owns this Domain',
		  `conflict` int DEFAULT 0 COMMENT '1 if Confliced with another Server Master Domain | 0 is Okay',
		  `zonecheck` int DEFAULT 0 COMMENT '0 if current Zone-Config is ERROR | 1 is Okay',
		  `zonecheck_message` text NULL COMMENT 'Output Check Message of the Current Active Zone',
		  `zonecheck_failmessage` text NULL COMMENT 'Output Check Message of the Last Checked Zone',
		  `preferred` int DEFAULT 0 COMMENT '1 is preferred to solve a conflict | 0 is not preferred',
		  `registered` int DEFAULT 0 COMMENT '1 is registered and running in bind local | 0 is not running',
		  `okonce` int DEFAULT 0 COMMENT '1 if has ever been a valid zone | 0 is never been a valid zone',
		  `oldzonefallback` int DEFAULT 0 COMMENT '1 new zone invalid, falling back to old | 0 new zone ok',
		  `set_no_replicate` int(1) DEFAULT 0 COMMENT '1 - No Replication to Slaves | 0 - Replicate to Slaves (default)',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain Entry Date | Auto Set',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Domain Update Date | Auto Set',		  
		PRIMARY KEY (`id`)); ALTER TABLE `"._TABLE_DOMAIN_BIND_."` ADD UNIQUE `unique_index_binddomain`(`domain`, `domain_type`);"); $mysql->free_all();}			
	if(!$mysql->table_exists(_TABLE_SERVER_)) { $mysql->query("CREATE TABLE IF NOT EXISTS `"._TABLE_SERVER_."` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `ip` VARCHAR(255) NULL COMMENT 'Server IPv4',
		  `ip6` VARCHAR(255) NULL DEFAULT '' COMMENT 'Server IPv6',
		  `api_path` varchar(700) NOT NULL COMMENT 'APIs Website URL',
		  `api_token` varchar(512) NOT NULL COMMENT 'APIs Connection Token',
		  `server_type` tinyint(1) NOT NULL COMMENT '1 - Master Server | 2 - Slave Server | 3 = Both',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date | Auto Set',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Modification Date | Auto Set',
		  `fk_user` int(10) DEFAULT NULL COMMENT 'User which created the server!',
		  `enabled` tinyint(1) DEFAULT 1 COMMENT '1 - Enabled | 0 - Disabled',
		  `apiok` tinyint(1) DEFAULT 1 COMMENT '1 - API Connection Found | 0 - API Connection Fail',
		  `domains` int(10) DEFAULT 1 COMMENT 'Number of Domains this Server is serving!',
		  `weblacklisted` tinyint(1) NULL DEFAULT 2 COMMENT '0 - Not Blacklisted | 1 if last Request resulted in we are blacklisted on the other site | 2 - Waiting for Cron',
		  `emptydomains` tinyint(1) NULL DEFAULT 2 COMMENT '0 - Not Empty Domain Table | 1 if last Request resulted in empty Domains | 2 - Waiting for Cron',
		  `tokenbadlastreq` tinyint(1) NULL DEFAULT 2 COMMENT '0 - Token seems ok | 1 if last Request resulted in back Token | 2 - Waiting for Cron',		  
		  PRIMARY KEY (`id`), UNIQUE KEY `api_path` (`api_path`) )"); $mysql->free_all();}	
	if(!$mysql->table_exists(_TABLE_DOMAIN_API_)) { $mysql->multi_query("CREATE TABLE IF NOT EXISTS `"._TABLE_DOMAIN_API_."` (	  
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `domain` varchar(260) NOT NULL COMMENT 'Slave Servers Domain Name',		  
		  `content` text COMMENT 'Slave Servers Domain Content', 
		  `update_message` text NULL COMMENT 'A message just for some updates of sync.php - mostly empty',
		  `last_update` datetime DEFAULT NULL COMMENT 'Last Update Date',
		  `fk_server` int DEFAULT NULL COMMENT 'Related DNS Server',	
		  `conflict` int DEFAULT 0 COMMENT '1 if Confliced with another Server Master Domain | 0 is Okay',
		  `zonecheck` int DEFAULT 0 COMMENT '0 if current Zone-Config is ERROR | 1 is Okay',
		  `zonecheck_message` text NULL COMMENT 'Output Check Message of the Current Active Zone',
		  `zonecheck_failmessage` text NULL COMMENT 'Output Check Message of the Last Checked Zone',
		  `preferred` int DEFAULT 0 COMMENT '1 is preferred to solve a conflict | 0 is not preferred',
		  `registered` int DEFAULT 0 COMMENT '1 is registered and running in bind local | 0 is not running',
		  `okonce` int DEFAULT 0 COMMENT '1 if has ever been a valid zone | 0 is never been a valid zone',
		  `oldzonefallback` int DEFAULT 0 COMMENT '1 new zone invalid, falling back to old | 0 new zone ok',	  
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain First Creation Date',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Update Date',
		PRIMARY KEY (`id`)); ALTER TABLE `"._TABLE_DOMAIN_API_."` ADD UNIQUE `unique_index_apidomain`(`domain`, `fk_server`);"); $mysql->free_all();}		
	$mysql->query("CREATE TABLE IF NOT EXISTS `"._TABLE_CONFLICT_."` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `domain` varchar(256) NOT NULL COMMENT 'Domain Name',
		  `servers` text COMMENT 'Array with Conflicting Servers',
		  `solved` text DEFAULT NULL COMMENT 'Related Relay to Use in Favor',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
		  PRIMARY KEY (`id`))"); $mysql->free_all();				
		
	// Init x_class_ipbl IP Blacklist Class */	
	$ipbl = new x_class_ipbl($mysql, _TABLE_IPBL_, _IP_BLACKLIST_DAILY_OP_LIMIT_);		
	  
	/* Init x_class_user Class */		
	$user = new x_class_user($mysql, _TABLE_USER_, _TABLE_USER_SESSION_, _COOKIES_ , "admin", "changeme", 0);
	$user->multi_login(false);
	$user->login_recover_drop(true);
	$user->login_field_user();
	$user->mail_unique(false);
	$user->user_unique(true);
	$user->log_ip(false);
	$user->log_activation(false);
	$user->log_session(false);
	$user->log_recover(false);
	$user->log_mail_edit(false);
	$user->sessions_days(7);
	$user->init();		
	/* Init x_class_debug Class */
	$debug = new x_class_debug();
	//var_dump( $debug->php_modules());
	$debug->required_php_module("curl", true);
	$debug->required_php_module("mysqli", true);
	$debug->required_php_module("gd", true);
	//////////////////////////////////////////////////////////////////////// FUNCTIONS
	// Get all Informations of a Domain
	function server_check_and_set($mysql, $checkserverid) {
		$log_api	=	new x_class_log($mysql, _TABLE_LOG_, "api");	
		$apipath	=	dnshttp_server_get($mysql, $checkserverid)["api_path"]."/_api/list_count.php";
		$returncurl =   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $checkserverid)["api_token"]);
		//$log_api->message("[OUT][list_count.php][server_check_and_set][SERVERID:".@$checkserverid."][APIURL:".@$apipath."][TOKEN:".@dnshttp_server_get($mysql, $checkserverid)["api_token"]."][RETURNCONTENT:".@$returncurl."]");
		if(is_numeric($returncurl)) { $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$checkserverid."\";"); }
			elseif(trim($returncurl) == "token-error") { $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$checkserverid."\";"); }
			elseif(trim($returncurl) == "ip-blacklisted") { $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$checkserverid."\";"); }
			else { $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";"); }
		if($returncurl == "token-error") { $mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";"); }
			elseif(is_numeric($returncurl)) { $mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 1 WHERE id = \"".$checkserverid."\";"); }
			else { $mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 2 WHERE id = \"".$checkserverid."\";"); }
		if($returncurl == "ip-blacklisted") { $mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";"); }
			elseif(is_numeric($returncurl)) { $mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 1 WHERE id = \"".$checkserverid."\";"); }
			elseif(trim($returncurl) == "token-error") { $mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 1 WHERE id = \"".$checkserverid."\";"); }
			else { $mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 2 WHERE id = \"".$checkserverid."\";"); }	
			
		if(is_numeric($returncurl)) {
			 $mysql->query("UPDATE "._TABLE_SERVER_." SET domains = ".$returncurl." WHERE id = \"".$checkserverid."\";");
			 $mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 0 WHERE id = \"".$checkserverid."\";");
		} else {
			 $mysql->query("UPDATE "._TABLE_SERVER_." SET domains = 0 WHERE id = \"".$checkserverid."\";");
			 $mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 2 WHERE id = \"".$checkserverid."\";");
		}	
	}
	function dnshttp_server_get($mysql, $id) {
		if(is_numeric($id)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE id = '$id'", false);
		while (is_array($x)) { return $x; } } return false; }
	// Check if a Domain Name in Locals Master Exists
	function dnshttp_bind_domain_name_exists($mysql, $domain_name) { if(trim($domain_name) != "") { 
		$bind[0]["value"] = $domain_name;
		$bind[0]["type"] = "s";
		$x = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE domain = ?", false, $bind);
		if (is_array($x)) { return $x["id"]; } } return false; }	
	// Get all Informations of a Local Master Domain
	function dnshttp_bind_get($mysql, $id) { if(is_numeric($id)) { return $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = \"".$id."\"", false); } return false; }	
	function dnshttp_domapi_get($mysql, $id) { if(is_numeric($id)) { return $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE id = \"".$id."\"", false); } return false; }	
	// Get Username From ID
	function dnshttp_user_get_name_from_id($mysql, $userid) { 
		if(is_numeric($userid)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_USER_." WHERE id = '$userid'", false);
		while (is_array($x)) { return $x["user_name"]; } } return false; }	
	// API Functions
	function dnshttp_api_token_generate($len = 32, $comb = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')  
		{$pass = array(); $combLen = strlen($comb) - 1; for ($i = 0; $i < $len; $i++) { $n = mt_rand(0, $combLen); $pass[] = $comb[$n]; } return implode($pass);}			
	// API Functions
	function api_token_check($mysql, $token) {
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token);
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE api_token = ?", false, $bind);
		if(is_array($res)) { return true;}  else { return false; }}
	// API Functions
	function dnshttp_api_getcontent($mysql, $url, $token = "", $domain = "") { return api_request($mysql, $url, $token, $domain); }
	function api_request($mysql, $url, $token = "", $domain = "") {
		  $fields = array(
			'token'=>urlencode($token),
			'domain'=>urlencode(trim($domain)),
			'server'=>@urlencode(trim($server)) );			
		  $fields_string = "";
		  //url-ify the data for the POST
		  foreach($fields as $key=>$value) { @$fields_string .= $key.'='.$value.'&'; }
		  rtrim($fields_string,'&');
		  // Initialize curl
		  $ch = curl_init();
		  //set the url, number of POST vars, POST data
		  curl_setopt($ch,CURLOPT_URL,$url);
		  if(is_string($token)) {curl_setopt($ch,CURLOPT_POST,count($fields));}
		  if(is_string($token)) {curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);}
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
		  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);				
		  //execute post
		  $result = curl_exec($ch);
		  return $result;}	
		  $log_output = "";
		  
	function full_cron($mysql, $xx8734iuasd = false) {
		$log_rm	 =	new x_class_log($mysql, _TABLE_NOTIFY_, "removed");
		$log_add =	new x_class_log($mysql, _TABLE_NOTIFY_, "added");
		global $log_output;
		define("_SAD73UASD_", $xx8734iuasd);
		#######################################################################################################################################
		// Logging Function for better HTML and Echo View
		$log	=	new x_class_log($mysql, _TABLE_LOG_, "replication");	
		$log_output = "";		
		$file_pre_per = "// This is a DnsHTTP Generated BIND Configuration File\r\n// Changes here will be persistent\r\n// The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_noper = "// This is a DnsHTTP Generated BIND Configuration File\r\n// Changes here will NOT be persistent\r\n// The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_zone_per = "; This is a DnsHTTP Generated BIND Configuration File\r\n; Changes here will be persistent\r\n; The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_zone_noper = "; This is a DnsHTTP Generated BIND Configuration File\r\n; Changes here will NOT be persistent\r\n; The bugfish.eu team wished you the best...woof!\r\n";
		function logging_add($text) {
			global $log_output;
			$finaltext = $text;
			if(_SAD73UASD_ != false) { echo $finaltext; }
			while(strpos($finaltext, "\r\n") != false) { $finaltext = str_replace("\r\n", "<br />", $finaltext); }
			if(_SAD73UASD_ != true) { echo $finaltext; }
			if(substr($finaltext, 0, 2) == "OK") { $finaltext = " <font color='lime'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 8) == "FINISHED") { $finaltext = "<font color='yellow'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 5) == "ERROR") { $finaltext = " <font color='red'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 4) == "WARN") { $finaltext = "<font color='orange'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 4) == "INFO") { $finaltext = "<font color='lightblue'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 5) == "START") { $finaltext = "<font color='yellow'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 5) == "DEBUG") { $finaltext = "<font color='lightblue'>".$finaltext."</font>"; }
			$log_output .= $finaltext;}		
		#######################################################################################################################################
		// Initialize Starting Message
		logging_add("INFO: Starting Replication Cronjob at: ");
		logging_add("".date("Y-m-D H:i:s")."!\r\n");	
		#######################################################################################################################################
		// List of Slave Server IPs	
		$localslaveservers = ""; $array = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE server_type = 2 OR server_type = 3", true);
		if(is_array($array)) {
			foreach($array as $key => $value) {
				if(filter_var(@$value["ip"], FILTER_VALIDATE_IP) !== false) { $localslaveservers .= " ".@$value["ip"].";"; } 
				if(filter_var(@$value["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $localslaveservers .= " ".@$value["ip6"].";"; } 
			}
		}
		logging_add("INFO: Slave Server IPs: "); logging_add("$localslaveservers\r\n");
		#######################################################################################################################################
		// List of Master Server IPs
		$localmasterservers = "";$array = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE server_type = 1 OR server_type = 3", true);
		if(is_array($array)) {
			foreach($array as $key => $value) {
				if(filter_var(@$value["ip"], FILTER_VALIDATE_IP) !== false) { $localmasterservers .= " ".@$value["ip"].";"; }
				if(filter_var(@$value["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $localmasterservers .= " ".@$value["ip6"].";"; }
			}
		}	
		logging_add("INFO: Master Server IPs: "); logging_add("$localmasterservers\r\n");
		$localIP = @getHostByName(php_uname('n'));
		$localmasterservers .= " ".@$localIP.";";
		logging_add("INFO: Added Local IP to Master Server IPs: "); logging_add("$localmasterservers\r\n");
		#######################################################################################################################################
		// List of All Server IPs
		$allserverlist = "";$array = $mysql->select("SELECT * FROM "._TABLE_SERVER_."", true);
		if(is_array($array)) {
			foreach($array as $key => $value) {
				if(filter_var(@$value["ip"], FILTER_VALIDATE_IP) !== false) { $allserverlist .= " ".@$value["ip"].";"; }
				if(filter_var(@$value["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $allserverlist .= " ".@$value["ip6"].";"; }
			}
		}	
		logging_add("INFO: Master Server IPs: "); logging_add("$allserverlist\r\n");
		$localIP = @getHostByName(php_uname('n'));
		$allserverlist .= " ".@$localIP.";";
		logging_add("INFO: Added Local IP to Master Server IPs: "); logging_add("$allserverlist\r\n");
		####################################################################################################################################### 
		// Recreate DNS Config File Structure if not Exists
		#--------------------------------------------------------------------------------------------------------------------------------------
		try {
		if(!@file_exists(_CRON_BIND_FILE_CONFIG_DNSHTTP_)) {
			if(@mkdir(_CRON_BIND_FILE_CONFIG_DNSHTTP_, _CRON_BIND_LIB_CODE_, true)) {
				 logging_add("OK: Configuration Folder created: "._CRON_BIND_FILE_CONFIG_DNSHTTP_."\r\n"); 
			} else {  logging_add("ERROR: Configuration Folder creation failed [may a permission problem]: "._CRON_BIND_FILE_CONFIG_DNSHTTP_."\r\n");  }
		} else {  logging_add("OK: Configuration Folder exists: "._CRON_BIND_FILE_CONFIG_DNSHTTP_."\r\n");  }		
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_TABLE_)) {
			$output = $file_pre_noper;
			$myfile = @fopen(_CRON_BIND_FILE_TABLE_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: Domain Table File created: "._CRON_BIND_FILE_TABLE_."\r\n"); 
			} else {  logging_add("ERROR: Domain Table File creation failed: "._CRON_BIND_FILE_TABLE_."\r\n");  }
			@fclose($myfile);
		} else { logging_add("OK: Domain Table File does exist: "._CRON_BIND_FILE_TABLE_."\r\n"); }	
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_CONFIG_)) {
			$output = $file_pre_per;
			$output = $output._BIND_CONFIG_OPTIONS_;
			$myfile = @fopen(_CRON_BIND_FILE_CONFIG_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: DNSHTTP Bind9 Configuration created: "._CRON_BIND_FILE_CONFIG_."\r\n"); 
			} else {  logging_add("ERROR: DNSHTTP Bind9 Configuration creation failed: "._CRON_BIND_FILE_CONFIG_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: DNSHTTP Bind9 Configuration does exist: "._CRON_BIND_FILE_CONFIG_."\r\n");  }	
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_TEMP_)) {
			$output = $file_pre_noper;
			$myfile = @fopen(_CRON_BIND_FILE_TEMP_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: DNSHTTP Temp File created: "._CRON_BIND_FILE_TEMP_."\r\n"); 
			} else {  logging_add("ERROR: DNSHTTP Temp File creation failed: "._CRON_BIND_FILE_TEMP_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: DNSHTTP Temp File does exist: "._CRON_BIND_FILE_TEMP_."\r\n");  }	 
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_LOAD_)) {
			if(!_CRON_BIND_FILE_REWRITE_) { $output = $file_pre_per."include \""._CRON_BIND_FILE_CONFIG_."\";\r\ninclude \""._CRON_BIND_FILE_TABLE_."\";\r\n\r\n"; }
			else { $output = $file_pre_per."include \""._CRON_BIND_FILE_CONFIG_."\";\r\n\r\n"; }
			$myfile = @fopen(_CRON_BIND_FILE_LOAD_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: DNSHTTP Include File created: "._CRON_BIND_FILE_LOAD_."\r\n"); 
			} else {  logging_add("ERROR: DNSHTTP Include File creation failed: "._CRON_BIND_FILE_LOAD_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: DNSHTTP Include File does exist: "._CRON_BIND_FILE_LOAD_."\r\n");  }	 
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_CONFNAME_)) {
			if(!_CRON_BIND_FILE_REWRITE_) { $output = $file_pre_per."include \""._CRON_BIND_FILE_LOAD_."\";\r\n\r\n"; }
			else { $output = $file_pre_per."include \""._CRON_BIND_FILE_LOAD_."\";\r\ninclude \""._CRON_BIND_FILE_."\";\r\n\r\n"; }
			$myfile = @fopen(_CRON_BIND_CONFNAME_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: Bind Main Config Include File created: "._CRON_BIND_CONFNAME_."\r\n"); 
			} else {  logging_add("ERROR: Bind Main Config Include File creation failed: "._CRON_BIND_CONFNAME_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: Bind Main Config Include File does exist: "._CRON_BIND_CONFNAME_."\r\n");  }	 
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_LIB_)) {
			if(@mkdir(_CRON_BIND_LIB_, _CRON_BIND_LIB_CODE_, true)) {
				 logging_add("OK: Zone File Folder created: "._CRON_BIND_LIB_."\r\n"); 
			} else {  logging_add("ERROR: Zone File Folder creation failed: "._CRON_BIND_LIB_."\r\n");  }
		} else {  logging_add("OK: Zone File Folder does exist: "._CRON_BIND_LIB_."\r\n");  }		
		#--------------------------------------------------------------------------------------------------------------------------------------
		} catch(Throwable $e) { logging_add("ERROR: No permission to create or edit filed - run as root!\r\n"); }
		logging_add("........................................................................................................................................\r\n");
		#######################################################################################################################################
		/* Local Fetched Domain Storage */	
		$domain_array = array(); # Array for Fetched Domains and Entry in Database (Master Domains)	
		#######################################################################################################################################
		/* Fetching Domain Names From Folder with Zone Files in it [FIX FOR CRMs]*/			
		if(defined("_CRON_FILES_FOLDER_FETCH_") AND defined("_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_") 
				AND defined("_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_")) {	
			if(_CRON_FILES_FOLDER_FETCH_ !== false ) {
			logging_add("START: Fetching Domain Names From Folder with Zone Files in it...\r\n");
			logging_add("INFO: This is a fix for CMRs failing to create the named.conf.options file...\r\n");
			 logging_add("INFO: Search Folder is "); 
			 logging_add("'"._CRON_FILES_FOLDER_FETCH_."' \r\n"); 		
			 logging_add("INFO: "); 
			 logging_add("Following Settings are set to determine Domain names from File Names "); 
			 logging_add("If there is garbage string in front or after the domain you can use this "); 
			 logging_add("setting to folter chars in front and after the filename, so the domain "); 
			 logging_add("will be determined correctly...  \r\n"); 
			 logging_add("INFO: Filename Start Offset is set to "); 
			 logging_add("'"._CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_."' \r\n"); 
			 logging_add("INFO: Filename End Offset is set to ");	 
			 logging_add("'"._CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_."' \r\n");		
			foreach (glob(_CRON_FILES_FOLDER_FETCH_."/*") as $filename){
				$basenamefile = basename($filename);
				$filenamecleared = str_replace("//", "/", $filename );
				$realname = substr(basename($filename), _CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_);
				$realname = substr(basename($realname), 0, strlen($realname) - _CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_);			
				logging_add("OK: Determined Domain: $realname \r\n");
				array_push($domain_array, array('domain' => strtolower(trim($realname)), 'path' => $filenamecleared, 'type' => 'folder'));	
			}	
			##################################################################################################################
			logging_add("INFO: Now Cleanup Domains not existant anymore from database.\r\n");
			$domains	= $mysql->select("SELECT domain, id, domain_type FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'folder'", true);
			if(is_array($domains)) {
				foreach($domains as $key => $value) {
					$deleteable = true;
					if(is_array($domain_array)) {
						foreach($domain_array as $x => $y) {
							if($y["domain"] == strtolower(trim($value["domain"]))) { $deleteable = false; }
						}
					}									
					if($deleteable) {
						 logging_add("DEBUG: Removed Domain: ".$value["domain"]."\r\n");		 
						 $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$value["id"]."' AND domain_type = 'folder'"); 
					}
				} 
			} 
			logging_add("FINISHED: Fetching Domain Names From Folder with Zone Files in it...\r\n");
		} else {  $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'folder'"); logging_add("INFO: Named.conf.local Creation fix for CRMs like ispconfig is deactivated in settings.php. For more check the documentation. This does not interrupt the replication process.!\r\n");}}else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'folder'"); logging_add("INFO: Named.conf.local Creation fix for CRMs like ispconfig is deactivated in settings.php. For more check the documentation. This does not interrupt the replication process.!\r\n");}  
		logging_add("........................................................................................................................................\r\n");
		#######################################################################################################################################
		/* GET DOMAIN TABLE FROM DOMAIN TABLE FILE */		
		if(_CRON_BIND_FILE_ != false) {	
			logging_add("START: Reading Zone Table File.\r\n");
			
			// Prepare Backup
			if(!file_exists(_CRON_BIND_FILE_TEMP_."file1_backup")) { @copy(_CRON_BIND_FILE_, _CRON_BIND_FILE_TEMP_."file1_backup"); logging_add("INFO: File Backup Created \r\n"); }
			
			// File to Diff
			@unlink(_CRON_BIND_FILE_TEMP_."file1_tmp");
			@copy(_CRON_BIND_FILE_, _CRON_BIND_FILE_TEMP_."file1_tmp");
			
			logging_add("INFO: File to read is '"._CRON_BIND_FILE_."' \r\n");
			$handle = fopen(_CRON_BIND_FILE_, "r"); if ($handle) {
				while (($line = fgets($handle)) !== false) {
					failrestart:
					if(strpos($line, "zone ") > -1) {
						preg_match('/"(.*?)"/', $line, $match);
						$domain = strtolower(trim($match[1]));	
						$foundline = false;		
						if(strpos($line, "file ") > -1) {
							$foundline = $line;
						} else { 
							while(($line = fgets($handle)) !== false) {
								if($line == false) {
									 logging_add("ERROR: No Content File Found for $domain\r\n"); 
									 goto endthis;
								}	
								if(strpos($line, "file ") > -1) {
									$foundline = $line;
									goto isnowok;
								} else {	
									if(strpos($line, "zone ") > -1) { logging_add("ERROR: No Content File Found for $domain\r\n"); goto failrestart; } 
								} 
							}
						}	
						isnowok:
						$foundline =substr($foundline, strpos($foundline, "zone "));
						$foundline =substr($foundline, strpos($foundline, "file "));
						$realpathnow =substr($foundline, strpos($foundline, "\"") + 1);
						$realpathnow =substr($realpathnow, 0, strpos($realpathnow, "\"") );			
						if(trim($domain) != "") {		
							$realcontentfilename = basename($realpathnow);
							$realcontentfilewithpath = $realpathnow;
							$filenamecleared = str_replace("//", "/", $realpathnow );	
							if(file_exists($realcontentfilewithpath)) {
								logging_add("OK: Added Local Domain: $domain\r\n");
								array_push($domain_array, array('domain' => strtolower(trim($domain)), 'path' => $filenamecleared, 'type' => "file"));
							} else { logging_add("ERROR: Content File not Found for: $domain\r\n"); }
						}					
					}
				} endthis: @fclose($handle); 
				logging_add("INFO: Now Cleanup deleted Domains.\r\n");
				$domains	= $mysql->select("SELECT domain, id, domain_type FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file'", true);
				if(is_array($domains)) {
					foreach($domains as $key => $valuex) {
						$deleteable = true;
						if(is_array($domain_array)) {
							foreach($domain_array as $x => $y) {
								if($y["domain"] == strtolower(trim($valuex["domain"]))) { $deleteable = false; }
							}
						}	
						if($deleteable) {
							logging_add("OK: Removed Domain: ".$valuex["domain"]."\r\n");			 
							$mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$valuex["id"]."' AND domain_type = 'file'"); 
							$log_rm->info($valuex["domain"]);
						}
					} 
				} 
			} else { logging_add("ERROR: Could not open File: "._CRON_BIND_FILE_." - No Changes on Related Domains\r\n"); } 		
			logging_add("FINISHED: Last Operation finished!\r\n"); 
		} else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file'"); logging_add("INFO: The First File Descriptor '_CRON_BIND_FILE_' to Fetch Domains out of a file (for example named.conf.local) is false! Related domains will be cleaned up.!\r\n");  }
		logging_add("........................................................................................................................................\r\n");
		#######################################################################################################################################
		/* GET DOMAIN TABLE FROM DOMAIN TABLE FILE */		
		if(_CRON_BIND_FILE_2_ != false) { 
			logging_add("START: Reading Zone Table File (2nd option).\r\n");
			
			// Prepare Backup
			if(!file_exists(_CRON_BIND_FILE_TEMP_."file2_backup")) { @copy(_CRON_BIND_FILE_2_, _CRON_BIND_FILE_TEMP_."file2_backup"); logging_add("INFO: File Backup Created \r\n"); }

			// File to Diff
			@unlink(_CRON_BIND_FILE_TEMP_."file2_tmp");
			@copy(_CRON_BIND_FILE_2_, _CRON_BIND_FILE_TEMP_."file2_tmp");
			
			logging_add("INFO: File to read is '"._CRON_BIND_FILE_2_."' \r\n");	
			$handle = fopen(_CRON_BIND_FILE_2_, "r"); if ($handle) {
				while (($line = fgets($handle)) !== false) {
					failrestart2:
					if(strpos($line, "zone ") > -1) {
						preg_match('/"(.*?)"/', $line, $match);
						$domain = strtolower(trim($match[1]));	
						$foundline = false;		
						if(strpos($line, "file ") > -1) {
							$foundline = $line;
						} else { 
							while(($line = fgets($handle)) !== false) {
								if($line == false) {
									logging_add("ERROR: No Content File Found for $domain\r\n"); 
									 goto endthis2;
								}
								if(strpos($line, "file ") > -1) {
									$foundline = $line;
									goto isnowok2;
								} else {
									if(strpos($line, "zone ") > -1) { logging_add("ERROR: No Content File Found for $domain\r\n"); goto failrestart2; } 
								} 
							}
						}	
						isnowok2:
						$foundline =substr($foundline, strpos($foundline, "zone "));
						$foundline =substr($foundline, strpos($foundline, "file "));
						$realpathnow =substr($foundline, strpos($foundline, "\"") + 1);
						$realpathnow =substr($realpathnow, 0, strpos($realpathnow, "\"") );					
						if(trim($domain) != "") {				
							$realcontentfilename = basename($realpathnow);
							$realcontentfilewithpath = $realpathnow;
							$filenamecleared = str_replace("//", "/", $realpathnow );
							if(file_exists($realcontentfilewithpath)) {
								logging_add("OK: Added Local Domain: $domain\r\n");										
									array_push($domain_array, array('domain' => strtolower(trim($domain)), 'path' => $filenamecleared, 'type' => "file2"));
							} else { logging_add("ERROR: Content File not Found for: $domain\r\n");	 }
						}					
					}
				} endthis2: @fclose($handle); 
				logging_add("INFO: Now Cleanup deleted Domains (2nd Option).\r\n");
				$domains	= $mysql->select("SELECT domain, id, domain_type FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file2'", true);
				if(is_array($domains)) {
					foreach($domains as $key => $valuex) {
						$deleteable = true;
						if(is_array($domain_array)) {
							foreach($domain_array as $x => $y) {
								if($y["domain"] == strtolower(trim($valuex["domain"]))) { $deleteable = false; }
							}
						}									
						if($deleteable) {
							logging_add("OK: Removed Domain: ".$valuex["domain"]."\r\n");			 
							$mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$valuex["id"]."' AND domain_type = 'file2'"); 
							$log_rm->info($valuex["domain"]);
						}
					} 
				} 		
			} else { logging_add("ERROR: Could not open File: "._CRON_BIND_FILE_." - No Changes on Related Domains\r\n"); } 		
			logging_add("FINISHED: Last Operation finished!\r\n");
		} else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file2'"); logging_add("INFO: The Second File Descriptor '_CRON_BIND_FILE_2_' to Fetch Domains out of a file (for example named.conf.local) is false! Related domains will be cleaned up.!\r\n"); }
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Writing/Update all Local Fetched Master Domains to Database	
		logging_add("START: Writing/Update all Local Fetched Domains to Database\r\n");
		if(is_array($domain_array)) {
			foreach($domain_array AS $key => $value) {	
				if($x = dnshttp_bind_domain_name_exists($mysql, $value["domain"])) {	
					$content = "";
					logging_add("OK: Updated Domain: '".$value["domain"]."' | Type: '".$value["type"]."' | Zone-File: '".$value["path"]."' \r\n");
					if(file_exists($value["path"])) { 
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { @unlink(_CRON_BIND_FILE_TEMP_); } 
						@shell_exec(_BIND_COMPILEZONE_COMMAND_." -f raw -F text -o "._CRON_BIND_FILE_TEMP_." ".strtolower($value["domain"])." ".$value["path"]."  ");
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { $pathnow = _CRON_BIND_FILE_TEMP_; } else { $pathnow = $value["path"]; }
						if($content = file_get_contents($pathnow)) {
						} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Opening Error for: '".$value["domain"]."' \r\n"); } 
					} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Does not Exist for: '".$value["domain"]."' \r\n"); }
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $value["path"];
					$bind[1]["type"] = "s";
					$bind[1]["value"] = $value["type"];
					$bind[2]["type"] = "s";
					$bind[2]["value"] = $content;
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zone_path = ?, domain_type = ?, last_update = CURRENT_TIMESTAMP(), content = ? WHERE id = '".$x."';", $bind);unset($bind);
				} else {
					$content = "";
					logging_add("OK: Added Domain: '".$value["domain"]."' | Type: '".$value["type"]."' | Zone-File: '".$value["path"]."' \r\n");
					if(file_exists($value["path"])) { 
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { @unlink(_CRON_BIND_FILE_TEMP_); } 
						@shell_exec(_BIND_COMPILEZONE_COMMAND_." -f raw -F text -o "._CRON_BIND_FILE_TEMP_." ".strtolower($value["domain"])." ".$value["path"]."  ");
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { $pathnow = _CRON_BIND_FILE_TEMP_; } else { $pathnow = $value["path"]; }
						if($content = file_get_contents($pathnow)) { 
						} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Opening Error for: '".$value["domain"]."' \r\n"); } 
					} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Does not Exist for: '".$value["domain"]."' \r\n"); }	
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $value["domain"];	
					$bind[1]["type"] = "s";
					$bind[1]["value"] = $value["path"];	
					$bind[2]["type"] = "s";
					$bind[2]["value"] = $value["type"];	
					$bind[3]["type"] = "s";
					$bind[3]["value"] = $content;					
					$mysql->query("INSERT INTO "._TABLE_DOMAIN_BIND_."(domain, zone_path, domain_type, last_update, content, preferred) VALUES(?, ?, ?, CURRENT_TIMESTAMP(), ?, 1);", $bind);unset($bind);
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0  WHERE LOWER(domain) = '".trim(strtolower($value["domain"]))."'");
					$log_add->info($value["domain"]);
				}
			}	
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Domains Array not Needed anymore, now unset...
		unset($domain_array); # No need anymore, Reset to preserve sprace
		unset($bind);	
		##################################################################################################################
		// Get External Domains	
		logging_add("START: Get Domains From Master Servers (Slave Domains)\r\n");
		# --------------------------------------------------------------------------------------	
		$servers = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE server_type = 1 OR server_type = 3", true);
		if(is_array($servers)) {	
			foreach($servers as $key => $value) {	
				$output = "";
				logging_add("START: Connecting to Server:  ".$value["api_path"]."\r\n");
				$apipath	=	$value["api_path"]."/_api/list.php";
				$returncurl =   dnshttp_api_getcontent($mysql, $apipath, $value["api_token"]);	
				
				$newarray = @unserialize($returncurl);
				if(is_array($newarray)) {
					$all_domains = array();
					foreach($newarray as $x => $y) {
						$bind[0]["value"] = strtolower(trim($y));
						$bind[0]["type"] = "s";	
						array_push($all_domains,strtolower(trim($y)) ); // Current Temp All API Server Domains
						$tmp = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE fk_server = ".$value["id"]." AND LOWER(domain) = ?", false, $bind);
						if(is_array($tmp)) {
							$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET last_update = CURRENT_TIMESTAMP() WHERE id = '".$tmp["id"]."' AND fk_server = ".$value["id"].";");	
							logging_add("OK: Still Existing Slave Domain: ".$y."\r\n");
						} else {						
							$mysql->query("INSERT INTO "._TABLE_DOMAIN_API_."(domain, fk_server) VALUES(?, '".$value["id"]."');", $bind);	
							$log_add->info(strtolower(trim($y)));
							logging_add("OK: Inserted Slave Domain: ".$y."\r\n");
						}
					}
					logging_add("INFO: Now Cleanup deleted domains...\r\n");
					$domains	= $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE fk_server = '".$value["id"]."'", true);	
					if(is_array($domains)) {
						foreach($domains as $keyx => $valuex) {				
							$deleteable = true;
							if(is_array($all_domains)) {
								foreach($all_domains as $x => $y) {
									if(trim(strtolower($y)) == trim(strtolower($valuex["domain"]))) { $deleteable = false; }
								}
							}								
							if($deleteable) { 
								logging_add("OK: Removed Expired Slave Domain: $y\r\n");					
								$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = '".$valuex["id"]."' WHERE fk_server = '".$value["id"]."'");
								$log_rm->info($valuex["domain"]);
							}
						}
					}
					$mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 0 WHERE id = ".$value["id"].";");
					$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 1 WHERE id = ".$value["id"].";");
					$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 1 WHERE id = ".$value["id"].";");
				} elseif($returncurl == "ip-blacklisted") { 
					$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = ".$value["id"].";");	
					logging_add("ERROR: We are IP-Blocked on Server ID-'".$value["id"]."'!\r\n"); 
				} elseif($returncurl == "token-error") { 
					$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = ".$value["id"].";");	
					 logging_add("ERROR: Server with ID-'".$value["id"]."' has Wrong Security Token !\r\n");
				} else { logging_add("ERROR: Bad Slave Server Response!\r\n"); }
			} 
		} else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_API_."");logging_add("OK: There are no Slave Servers, all Remote Domains Cleared!\r\n"); }
		logging_add("FINISHED: LAST OPERATION\r\n");	
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Delete All API Domains not Related to a Server	
		logging_add("START: Cleanup unrelated Remote Domains!\r\n");
		$domains = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." ORDER BY id DESC", true);
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				if(is_array(dnshttp_server_get($mysql, $value["fk_server"]))) {
				} else {
					logging_add("OK: Unrelated Domain Deleted: ".$value["domain"]." from server-id: ".$value["fk_server"]."\r\n");
					$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = '".$value["id"]."';");
					$log_rm->info($value["domain"]);
				}
			} 
		} 
		logging_add("FINISHED: LAST OPERATION\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Get External Domains Content
		logging_add("START: Get (replicate) External Domains Content!\r\n");
		$domains = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." ORDER BY id DESC", true);
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				$apipath	=	dnshttp_server_get($mysql, $value["fk_server"])["api_path"]."/_api/content.php";
				$returncurl =   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $value["fk_server"])["api_token"], $value["domain"]);
				$domain = $value["domain"];

				if($returncurl AND $returncurl != "error-domain-no-exist") { 
					//echo $returncurl;
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $returncurl;
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET content = ?, modification = CURRENT_TIMESTAMP() WHERE id = '".$value["id"]."';", $bind);
					logging_add("OK: Fetched content for: $domain\r\n");	
				} elseif($returncurl == "error-domain-no-exist") {
					$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = ".$value["id"].";");
					$log_rm->info($value["domain"]);
				} else { logging_add("ERROR: Can not get Content for $domain : Invalid Return Data!\r\n"); }
			} 
		} logging_add("FINISHED: LAST OPERATION\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Check for Conflicts		
		logging_add("START: Check for Domain Conflicts!\r\n");
		$domains_full = array();
		$domains_posted = array();
		$domains_bind = $mysql->select("SELECT domain_type, domain, id, preferred, conflict FROM "._TABLE_DOMAIN_BIND_." ORDER BY domain", true);
		$domains_api = $mysql->select("SELECT  domain, id, fk_server, preferred, conflict FROM "._TABLE_DOMAIN_API_."  ORDER BY domain", true);	
		if(is_array($domains_bind)) { foreach($domains_bind as $key => $value) { array_push($domains_full, $value); } }
		if(is_array($domains_api)) { foreach($domains_api as $key => $value) { array_push($domains_full, $value); } }	
		foreach($domains_full as $key => $value) {
			$domainname_current = strtolower($value["domain"]);
			$conflict = false;
			$conflictarray = array();	
			if(is_array($domains_api)) { foreach($domains_api as $x => $y) {
				// Check Slave Domain Conflict
				if(strtolower($y["domain"]) == strtolower($value["domain"])) { $conflict = true; array_push($conflictarray, $y); }
			}}
			if(is_array($domains_bind)) { foreach($domains_bind as $x => $y) {
				// Check Bind Domain Conflict
				if(strtolower($y["domain"]) == strtolower($value["domain"])) { $conflict = true; array_push($conflictarray, $y); } 
			}}
			$preferredhandle = false;
			if($conflict  AND is_array($conflictarray[0]) AND is_array(@$conflictarray[1])) {
				foreach($conflictarray as $keyx => $valueconflict) {
					if($valueconflict["preferred"] == 1) {
						$preferredhandle = $valueconflict;
					}
				}
			}
			$handleinel = $mysql->escape(serialize($conflictarray)); 
			if($conflict AND is_array($conflictarray[0])  AND is_array(@$conflictarray[1])) {
				$bind[0]["value"] = trim(strtolower($domainname_current));
				$bind[0]["type"] = "s";		
				$selector = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE LOWER(domain) = ?", false, $bind);
				if(is_array($selector)) {
					if( !$preferredhandle ) { $mysql->query("UPDATE "._TABLE_CONFLICT_." SET servers = \"".$handleinel."\", solved = 0 WHERE id = '".$selector["id"]."'"); }
					else { $mysql->query("UPDATE "._TABLE_CONFLICT_." SET servers = \"".$handleinel."\" WHERE id = '".$selector["id"]."'"); } 
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(domain) = ?;", $bind);
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 1 WHERE LOWER(domain) = ?;", $bind);
				} else {  $mysql->query("INSERT INTO "._TABLE_CONFLICT_." (domain, servers, solved) VALUES(?, \"".$handleinel."\", 0)", $bind); 
						  $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(domain) = ?;", $bind);
						  $mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 1 WHERE LOWER(domain) = ?;", $bind);}			
				//$aretheremore = false;
				//foreach($domains_full as $keyxys => $valuexys) {
				//	if($keyxys > $key AND strtolower(trim($valuexys["domain"])) == strtolower(trim($domainname_current))) { $aretheremore = true; }
				//}
				if(!$preferredhandle ) { 
					
					$postnow = true;
					foreach($domains_posted AS $keasdf => $afng3uk) {
						if($afng3uk == strtolower(trim($domainname_current))) {
							$postnow = false;
						}
						
					} if($postnow) { logging_add("ERROR: This Domain has a Conflict: $domainname_current\r\n"); }
					
					
					array_push($domains_posted, trim(strtolower($domainname_current)));
				} else { 
					
				if(isset($preferredhandle["fk_server"])) { $asdsas7hfds = $preferredhandle["fk_server"]; } else { $asdsas7hfds = "Local";  }
					$mysql->query("UPDATE "._TABLE_CONFLICT_." SET solved = 'Domain: ".$mysql->escape($valueconflict["domain"])." | Server-ID: ".@$asdsas7hfds."' WHERE LOWER(domain) = '".strtolower(trim($domainname_current))."'");
					
					
					
					$postnow = true;
					foreach($domains_posted AS $keasdf => $afng3uk) {
						if($afng3uk == strtolower(trim($domainname_current))) {
							$postnow = false;
						}
						
					} if($postnow) { logging_add("OK: This Domain has a solved Conflict: $domainname_current\r\n"); }
					array_push($domains_posted, trim(strtolower($domainname_current)));
				}
			} else { $mysql->query("DELETE FROM "._TABLE_CONFLICT_." WHERE domain = '".$mysql->escape(trim($domainname_current))."'"); $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 0 WHERE LOWER(domain) = '".trim($domainname_current)."';");$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 0 WHERE LOWER(domain) = '".trim($domainname_current)."';"); }	
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Write Domain Files and Table File at Once		
		$conf_buildstring = $file_pre_noper."";
		logging_add("START: Writing Slave Zones\r\n");
		$mysql->select("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck = 2, oldzonefallback = 2, zonecheck_failmessage = 'none', zonecheck_message = 'none', registered = 2 "); // Reset Domains State
		$domains = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE ( conflict = 0 OR (conflict = 1 AND preferred = 1) ) ORDER BY id DESC", true);
		$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 0 WHERE ( conflict = 1 AND preferred = 0) ");
		if(is_array($domains)) {	
			$conf_buildstring = "";	
			foreach($domains as $key => $value) {	
				$relay = @dnshttp_server_get($mysql, $value["fk_server"]);
				if(!is_array($relay)) { logging_add("ERROR: Domain Relay not exist, skipped for domain ".$value["domain"]."!\r\n"); $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 0 WHERE id = '".$value["id"]."'"); continue; }
				$filenamecleared = _CRON_BIND_LIB_."/".strtolower(trim($value["domain"]))._CRON_BIND_LIB_ENDING_.".slave";
				$filenamecleared = str_replace("//", "/", $filenamecleared );	
				$newzoneok = false;
				@unlink($filenamecleared);
				if($value["okonce"]) { 
					if(file_exists(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])))) { @unlink(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"]))); }
				
					@file_put_contents(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), $value["content"]);
					@chown(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), _CRON_BIND_LIB_USER_);
					@chgrp(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), _CRON_BIND_LIB_GROUP_);
					@chmod(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), _CRON_BIND_LIB_CODE_);		
					$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".strtolower(trim($value["domain"]))." "._CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])));
					if(strpos(strtolower($cout), ": loaded serial") > -1) { 
						$newzoneok = true;
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET oldzonefallback = 0 WHERE id = '".$value["id"]."'");
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck_failmessage = 'none' WHERE id = '".$value["id"]."'"); 
					} else { 
						$newzoneok = false;
						$bind[0]["value"] = $cout;
						$bind[0]["type"] = "s";	
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck_failmessage = ? WHERE id = '".$value["id"]."'", $bind);
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET oldzonefallback = 1 WHERE id = '".$value["id"]."'");
					} @unlink(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])));
				}
				if($newzoneok == true) { 
					if(file_exists($filenamecleared)) { @unlink($filenamecleared); } 
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, _CRON_BIND_LIB_CODE_);			
				} 
				if($newzoneok != true AND !file_exists($filenamecleared)) { 
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, _CRON_BIND_LIB_CODE_);			
				} 
				
								
				
				$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".strtolower(trim($value["domain"]))." ".$filenamecleared);
				$bind[0]["value"] = $cout;
				$bind[0]["type"] = "s";	
				$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck_message = ? WHERE id = '".$value["id"]."'", $bind);
				$isnowregister = false;
				if(strpos(strtolower($cout), ": loaded serial") > -1) {
					$isnowregister = true;				
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck = 1, okonce = 1, registered = 1 WHERE id = '".$value["id"]."'");
					//if(!_SLAVE_AS_MASTER_DOMAIN_) {
						$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype slave;\r\n\tmasterfile-format text;\r\n\tmasters { ".trim($relay["ip"])."; };\r\n\tallow-transfer { ".$allserverlist." };\r\n\tfile \"".$filenamecleared."\";\r\n};\r\n";	
					//} else {
					//	$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n};";
					//}
				} else {
					$isnowregister = false;		
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck = 0, okonce = 0, registered = 0 WHERE id = '".$value["id"]."'");
				}
				if($isnowregister AND $newzoneok) {
					logging_add("OK: Written Zone File: ".$filenamecleared.""."\r\n");	
				} elseif($isnowregister AND !$newzoneok) {
					logging_add("WARN: Left Rollback Zone File: ".$filenamecleared.""."\r\n");	
				} else {
					logging_add("ERROR: No valid zone data in file: ".$filenamecleared.""."\r\n");
				}
			}
		} 
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("START: Writing Master Zones\r\n");
		$mysql->select("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck = 2, oldzonefallback = 2, zonecheck_failmessage = 'none', zonecheck_message = 'none', registered = 2 "); // Reset Domains State
		$domains = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE ( conflict = 0 OR (conflict = 1 AND preferred = 1) ) ORDER BY id DESC", true);
		$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET registered = 0 WHERE ( conflict = 1 AND preferred = 0) ");
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				$filenamecleared = _CRON_BIND_LIB_."/".strtolower(trim($value["domain"]))._CRON_BIND_LIB_ENDING_.".master";
				$filenamecleared = str_replace("//", "/", $filenamecleared );		
				@unlink($filenamecleared);
				$newzoneok = false;
				if($value["okonce"]) { 
					if(file_exists(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])))) { @unlink(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"]))); } 
					@file_put_contents(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), $value["content"]);
					@chown(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), _CRON_BIND_LIB_USER_);
					@chgrp(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), _CRON_BIND_LIB_GROUP_);
					@chmod(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])), _CRON_BIND_LIB_CODE_);				
					$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".strtolower(trim($value["domain"]))." "._CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])));		
					if(strpos(strtolower($cout), ": loaded serial") > -1) { 
						$newzoneok = true;
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET oldzonefallback = 0 WHERE id = '".$value["id"]."'");
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck_failmessage = 'none' WHERE id = '".$value["id"]."'"); 
					} else { 
						$newzoneok = false;
						$bind[0]["value"] = $cout;
						$bind[0]["type"] = "s";	
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck_failmessage = ? WHERE id = '".$value["id"]."'", $bind);
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET oldzonefallback = 1 WHERE id = '".$value["id"]."'");
					} @unlink(_CRON_BIND_FILE_TEMP_. trim(strtolower($value["domain"])));
				}
				if($newzoneok == true) { 
					if(file_exists($filenamecleared)) { @unlink($filenamecleared); } 
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, _CRON_BIND_LIB_CODE_);			
				} 
				if($newzoneok != true AND !file_exists($filenamecleared)) { 
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, _CRON_BIND_LIB_CODE_);			
				} 
				$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".strtolower(trim($value["domain"]))." ".$filenamecleared);
				$bind[0]["value"] = $cout;
				$bind[0]["type"] = "s";
				$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck_message = ? WHERE id = '".$value["id"]."'", $bind);
				$isnowregister = false;
				if(strpos(strtolower($cout), ": loaded serial") > -1) {
					$isnowregister = true;				
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck = 1, okonce = 1, registered = 1 WHERE id = '".$value["id"]."'");
					//if(!_SLAVE_AS_MASTER_DOMAIN_) {
						//$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype slave;\r\n\tmasterfile-format text;\r\n\tmasters { ".trim($relay["ip"])."; };\r\n\tallow-transfer { ".$localmasterservers." };\r\n\tfile \"".$filenamecleared."\";\r\n};\r\n";	
					//} else {
					//	$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n};";
					//}
					$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n\tallow-transfer { ".$allserverlist." };\r\n\tallow-update { ".$allserverlist." };\r\n};";
				} else {
					$isnowregister = false;		
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck = 0, okonce = 0, registered = 0 WHERE id = '".$value["id"]."'");
				}
				if($isnowregister AND $newzoneok) {
					logging_add("OK: Written Zone File: ".$filenamecleared.""."\r\n");	
				} elseif($isnowregister AND !$newzoneok) {
					logging_add("WARN: Left Rollback Zone File: ".$filenamecleared.""."\r\n");	
				} else {
					logging_add("ERROR: No valid zone data in file: ".$filenamecleared.""."\r\n");
				}
			}
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		if(file_exists(_CRON_BIND_FILE_TABLE_)) { @unlink(_CRON_BIND_FILE_TABLE_); }
		@file_put_contents(_CRON_BIND_FILE_TABLE_, $conf_buildstring);	
		logging_add("OK: Trying to write Zone Table File: "._CRON_BIND_FILE_TABLE_."\r\n");
		if(_CRON_BIND_FILE_REWRITE_ AND _CRON_BIND_FILE_) { 
			logging_add("INFO: _CRON_BIND_FILE_ is set and _CRON_BIND_FILE_REWRITE_ is set!\r\n");
			logging_add("INFO: Now rewriting the _CRON_BIND_FILE_ if nothing has changed in the meantime.!\r\n");
			if(file_exists(_CRON_BIND_FILE_)) { 
				@unlink(_CRON_BIND_FILE_TEMP_."file1_tmp");
				@copy(_CRON_BIND_FILE_, _CRON_BIND_FILE_TEMP_."file1_tmp");
				//check if file same
				if(md5_file(_CRON_BIND_FILE_TEMP_."file1_tmp") === md5_file(_CRON_BIND_FILE_)) {
					@unlink(_CRON_BIND_FILE_);
					@file_put_contents(_CRON_BIND_FILE_, $conf_buildstring);	
					logging_add("OK: Trying to write Zone Table File: "._CRON_BIND_FILE_."\r\n");
				} else {
					logging_add("ERROR: File has been changed, waiting for next cron to reload zone table: "._CRON_BIND_FILE_."\r\n");
				}
			}
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		//  Executions
		logging_add("START: Linux Command Executions \r\n");
		// Chown
		if(file_exists(_CRON_BIND_LIB_)) { 
			logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_LIB_.";\r\n"); 
			@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_LIB_.";");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_LIB_.": Folder does not exists!\r\n"); }	

		if(file_exists(_CRON_BIND_FILE_CONFIG_DNSHTTP_)) { 
			logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";\r\n"); 
			@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_FILE_CONFIG_DNSHTTP_.": Folder does not exists!\r\n"); }	

		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TABLE_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TABLE_.";");
		
		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_.";");
			
		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TEMP_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TEMP_.";");
		
		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_LOAD_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_LOAD_.";");		

		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_CONFNAME_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_CONFNAME_.";");	
		
		// Chmod 
		if(file_exists(_CRON_BIND_LIB_)) {  
			logging_add("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_LIB_.";\r\n"); 
			@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_LIB_.";");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_LIB_.": Folder does not exists!\r\n"); }	
		
		if(file_exists(_CRON_BIND_FILE_CONFIG_DNSHTTP_)) {  
			logging_add("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";\r\n"); 
			@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_FILE_CONFIG_DNSHTTP_.": Folder does not exists!\r\n"); }	

		logging_add("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TABLE_.";\r\n"); 
		@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TABLE_.";");

		logging_add("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_.";\r\n"); 
		@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_.";");
		
		logging_add("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TEMP_.";\r\n"); 
		@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TEMP_.";");

		logging_add("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_LOAD_.";\r\n"); 
		@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_LOAD_.";");

		logging_add("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_CONFNAME_.";\r\n"); 
		@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_CONFNAME_.";");
		
		// Restart Named Service
		logging_add("OK: systemctl restart "._BIND_SERVICE_NAME_.";\r\n"); 
		@shell_exec("systemctl restart "._BIND_SERVICE_NAME_."; ");
		logging_add("FINISHED: LAST OPERATION\r\n"); 
		logging_add("........................................................................................................................................\r\n");
		// Logfile Message
		logging_add("OK: Execution Done at ".date("Y-m-d H:m:i")."");
		$log->info($log_output); exit();
	} 