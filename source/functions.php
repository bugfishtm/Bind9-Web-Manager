<?php
	##########################################################################################################################################
	# DO NOT CHANGE SETTINGS BELOW!!!
	# Below settings does not need to be changed! They are just for website runtime...
	# DO NOT CHANGE SETTINGS BELOW!!!
	##########################################################################################################################################	
	
	/* ########################################## */
	/* Constants with Website Table Names to be used - Do not Change
	/* ########################################## */	
	define('_TABLE_PREFIX_',  		"dnshttp_");	
	define('_TABLE_USER_',   		_TABLE_PREFIX_."user");  
	define('_TABLE_USER_SESSION_',	_TABLE_PREFIX_."user_session");
	define('_TABLE_DOMAIN_BIND_',	_TABLE_PREFIX_."bind_domain");
	define('_TABLE_DOMAIN_API_',	_TABLE_PREFIX_."api_domain");
	define('_TABLE_SERVER_',		_TABLE_PREFIX_."server");
	define('_TABLE_IPBL_',			_TABLE_PREFIX_."ipblacklist");
	define('_TABLE_PERM_',			_TABLE_PREFIX_."perms");
	define('_TABLE_RECORD_',		_TABLE_PREFIX_."records");
	define('_TABLE_DOMAIN_BINDED_',	_TABLE_PREFIX_."binded_domain");
	define('_TABLE_CONFLICT_',		_TABLE_PREFIX_."conflict");
	define('_TABLE_LOG_',			_TABLE_PREFIX_."log");	
	define('_TABLE_LOG_MYSQL_',		_TABLE_PREFIX_."mysql_log");	
	
	/* ########################################## */
	/* Rename dot.htaccess to .htaccess if Main Path is in Website Folder - Do Not Change
	/* ########################################## */		
	if(@file_exists(_MAIN_PATH_."/dot.htaccess") AND file_exists(_MAIN_PATH_."/_functions/dnshttp_library.php")) { @unlink(_MAIN_PATH_."/.htaccess"); @rename(_MAIN_PATH_."/dot.htaccess", _MAIN_PATH_."/.htaccess"); }
	
	/* ########################################## */
	/* Settings for Captcha Generation - Do Not Change
	/* ########################################## */	
	define('_CAPTCHA_FONT_',   	 _MAIN_PATH_."/_style/font_captcha.ttf");
	define('_CAPTCHA_WIDTH_',    "200"); 
	define('_CAPTCHA_HEIGHT_',   "70");	
	define('_CAPTCHA_SQUARES_',   mt_rand(4, 15));	
	define('_CAPTCHA_ELIPSE_',    mt_rand(4, 15));	
	define('_CAPTCHA_RANDOM_',    mt_rand(1000, 9999));	
	
	##########################################################################################################################################
	# Below Are Initializations of Classes! - Do not Change if you dont know what you do!
	##########################################################################################################################################	
	
	/* ########################################## */
	/* Includes of Important Classes and Functions
	/* ########################################## */	
	foreach (glob(_MAIN_PATH_."/_framework/functions/x_*.php") as $filename){require_once $filename;}
	foreach (glob(_MAIN_PATH_."/_framework/classes/x_*.php") as $filename){require_once $filename;}	
	
	/* ########################################## */
	/* Init x_class_mysql Class
	/* ########################################## */
	$mysql = new x_class_mysql(_SQL_HOST_, _SQL_USER_, _SQL_PASS_, _SQL_DB_);
	if ($mysql->lasterror != false) { $mysql->displayError(true); } 
		$mysql->log_config(_TABLE_LOG_MYSQL_, "log");
		
	/* ########################################## */
	/* Rebuild Table Structure
	/* ########################################## */		
	$mysql->query(" CREATE TABLE IF NOT EXISTS `dnshttp_bind_domain` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `domain` varchar(512) NOT NULL COMMENT 'Related Domain Name',
		  `domain_type` int(1) DEFAULT 0 COMMENT '1 - User Created Domain | 0 - DNS Fetched Domain',
		  `set_prefer_manual` int(1) DEFAULT 1 COMMENT '1 - User Created Domain | 0 - DNS Fetched Domain',
		  `set_no_replicate` int(1) DEFAULT 0 COMMENT '1 - No Replication to Slaves | 0 - Replicate to Slaves (default)',
		  `fk_user` int(9) DEFAULT NULL COMMENT 'If userdoman is 1 than this is user who owns',
		  `dns_serial` varchar(32) DEFAULT NULL COMMENT 'If userdoman is 1 than this is SAO SERIAL Value',
		  `dns_refresh` varchar(24) DEFAULT NULL COMMENT 'If userdoman is 1 than this is Refresh Value',
		  `dns_retry` varchar(24) DEFAULT NULL COMMENT 'If userdoman is 1 than this is Retry Value',
		  `dns_expire` varchar(24) DEFAULT NULL COMMENT 'If userdoman is 1 than this is Expire Value',
		  `dns_minimum` varchar(24) DEFAULT NULL COMMENT 'If userdoman is 1 than this is Minimum Value',
		  `dns_mail` varchar(512) DEFAULT NULL COMMENT 'Minimum',
		  `content` text NULL COMMENT 'Local Registred Domain Content',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain Entry Date',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Domain Update Date',
		  PRIMARY KEY (`id`), UNIQUE KEY `domain` (`domain`) )"); $mysql->free_all();	
	$mysql->query("CREATE TABLE IF NOT EXISTS `dnshttp_server` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `ip` VARCHAR(255) NULL COMMENT 'Server IP',
		  `api_path` varchar(700) NOT NULL COMMENT 'APIs Website URL',
		  `api_token` varchar(512) NOT NULL COMMENT 'APIs Connection Token',
		  `server_type` tinyint(1) NOT NULL COMMENT '1 - Master Server | 2 - Slave Server | 3 = Both',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
		  `last_api_list` datetime DEFAULT NULL COMMENT 'Last API List Fetch Date',
		  `last_api_content` datetime DEFAULT NULL COMMENT 'Last API Content Fetch Date',
		  `last_api_ip` datetime DEFAULT NULL COMMENT 'Last API Slave Server IP',
		  `fk_user` int DEFAULT NULL COMMENT 'Related User',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Modification Date',
		  PRIMARY KEY (`id`), UNIQUE KEY `api_path` (`api_path`), UNIQUE KEY `api_token` (`api_token`) )"); $mysql->free_all();	
	$mysql->query("CREATE TABLE IF NOT EXISTS `dnshttp_api_domain` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `domain` text NOT NULL COMMENT 'Slave Servers Domain Name',
		  `content` text COLLATE utf8mb4_unicode_ci COMMENT 'Slave Servers Domain Content',
		  `fk_server` int DEFAULT NULL COMMENT 'Related DNS Server',
		  `preferred` int DEFAULT 0 COMMENT 'If Conflichted, but preferred, this Domain will be used preferred in Bind Creation',
		  `conflict` int DEFAULT 0 COMMENT '1 if Confliced with another Server Master Domain | 0 is Okay',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Domain First Creation Date',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Update Date',
		  PRIMARY KEY (`id`))"); $mysql->free_all();
	$mysql->query("CREATE TABLE IF NOT EXISTS `dnshttp_binded_domain` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `domain` text NOT NULL COMMENT 'Bind Registered Domain Name',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
		  PRIMARY KEY (`id`), UNIQUE KEY `domain` (`domain`) )"); $mysql->free_all(); /////////////////////////////////
	/*$mysql->query("CREATE TABLE IF NOT EXISTS `dnshttp_records` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `fk_domain` int NOT NULL COMMENT 'Bind Registered Domain Name',
		  `record_type` varchar(32) COMMENT 'Record Type',
		  `record_value` text COMMENT 'Record Value',
		  `record_priority` int(9) COMMENT 'Record Priority',
		  `record_domain` text COMMENT 'Record Domain',
		  `record_ttl` int(9) COMMENT 'Record Domain',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
		  PRIMARY KEY (`id`))"); $mysql->free_all();	*/	  
	$mysql->query("CREATE TABLE IF NOT EXISTS `dnshttp_conflict` (
		  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
		  `domain` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Domain Name',
		  `servers` text COLLATE utf8mb4_unicode_ci COMMENT 'Array with Conflicting Servers',
		  `fk_server_ovr` int DEFAULT NULL COMMENT 'Related Relay to Use in Favor',
		  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
		  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
		  PRIMARY KEY (`id`))"); $mysql->free_all();			  

	/* ########################################## */
	/* Init x_class_user Class
	/* ########################################## */		
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
		
	/* ########################################## */
	/* Init x_class_log Class
	/* ########################################## */
	$log	=	new x_class_log($mysql, _TABLE_LOG_);

	/* ################################### */
	// Init x_class_ipbl IP Blacklist Class
	/* ################################### */	
	if(is_numeric(_IP_BLACKLIST_DAILY_OP_LIMIT_)) { $ipbl = new x_class_ipbl($mysql, _TABLE_IPBL_, _IP_BLACKLIST_DAILY_OP_LIMIT_); } 
		else { $ipbl = new x_class_ipbl($mysql, _TABLE_IPBL_, 1000); }

	##########################################################################################################################################
	# Below Are Functions - DO NOT CHANGE!
	##########################################################################################################################################	
	
	#################################################
	// Check if a Relay with ID Sexists
	#################################################
	function dnshttp_server_id_exists($mysql, $id) { 
		if(is_numeric($id)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE id = '$id'", false);
		while (is_array($x)) { return true; } } return false; }	
	#################################################
	// Get all Informations of a Domain
	#################################################
	function dnshttp_server_get($mysql, $id) {
		if(is_numeric($id)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE id = '$id'", false);
		while (is_array($x)) { return $x; } } return false; }	
	
	#################################################
	// Check if a Domain Name in Locals Master Exists
	#################################################
	function dnshttp_bind_domain_name_exists($mysql, $domain_name) { if(trim($domain_name) != "") { 
		$bind[0]["value"] = $domain_name;
		$bind[0]["type"] = "s";
		$x = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE domain = ?", false, $bind);
		if (is_array($x)) { return $x["id"]; } } return false; }	
	#################################################
	// Get all Informations of a Local Master Domain
	#################################################
	function dnshttp_bind_get($mysql, $id) { if(is_numeric($id)) { return $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = \"".$id."\"", false); } return false; }	
	
	#################################################
	// Get Username From ID
	#################################################
	function dnshttp_user_get_name_from_id($mysql, $userid) { 
		if(is_numeric($userid)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_USER_." WHERE id = '$userid'", false);
		while (is_array($x)) { return $x["user_name"]; } } return false; }	
	
	#################################################
	// API Functions
	#################################################	
	function dnshttp_api_token_generate($len = 32, $comb = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')  
		{$pass = array(); $combLen = strlen($comb) - 1; for ($i = 0; $i < $len; $i++) { $n = mt_rand(0, $combLen); $pass[] = $comb[$n]; } return implode($pass);}			
			
	function dnshttp_api_token_create($mysql, $relay) {
		$token = dnshttp_api_token_generate();
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token);
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE api_token = ?", false, $bind);
		if(is_array($res)) {dnshttp_api_token_create($mysql, $relay); }  else { $mysql->query("UPDATE "._TABLE_SERVER_." SET api_token = '".$token ."' WHERE id = '".$relay."'", false, $bind); return true; }				
	}
	
	function dnshttp_api_token_check($mysql, $token) {
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token);
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE api_token = ?", false, $bind);
		if(is_array($res)) { return true; }  else { return false; }				
	}
	
	function dnshttp_api_token_relay($mysql, $token) {
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token);
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE api_token = ?", false, $bind);
		if(is_array($res)) { return $res["id"];}  else { return false; }	
	}
	
	function dnshttp_api_getcontent($mysql, $url, $token = false, $domain = "") {
		if(is_string($token)) {
		  $fields = array(
			'token'=>urlencode($token),
			'domain'=>urlencode(trim($domain)),
		  );			
			$fields_string = "";
		  //url-ify the data for the POST
		  foreach($fields as $key=>$value) { @$fields_string .= $key.'='.$value.'&'; }
		  rtrim($fields_string,'&');
		}
		  // Initialize curl
		  $ch = curl_init();

		  //set the url, number of POST vars, POST data
		  curl_setopt($ch,CURLOPT_URL,$url);
		  if(is_string($token)) {curl_setopt($ch,CURLOPT_POST,count($fields));}
		  if(is_string($token)) {curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);}
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
		  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);				
		  //execute post
		  $result = curl_exec($ch);

		  return $result;
	}	