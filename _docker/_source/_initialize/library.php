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
		Function Library
	*************************************************************************/
	function dnshttp_compress(string $text): string {
		return base64_encode(gzcompress($text, 9));
	}
	
	function incrementSOASerial(string $serial): string {
		$today = date('Ymd');
		
		// If serial is in YYYYMMDDNN format and date matches today
		if (strlen($serial) === 10 && substr($serial, 0, 8) === $today) {
			$seq = (int)substr($serial, 8) + 1;
			if ($seq > 99) $seq = 99; // max 99 changes per day
			return $today . str_pad($seq, 2, '0', STR_PAD_LEFT);
		}
		
		// Date changed or different format — reset to today with 01
		// But ensure we always increment (serial must never go backwards)
		$new = $today . '01';
		return $new > $serial ? $new : (string)($serial + 1);
	}

	function dnshttp_decompress(string $compressed): string {
		return $compressed;
		return gzdecompress(base64_decode($compressed));
	}
	
	function dnshttp_api_output_ok()				{ return "online"; }
	
	function dnshttp_api_output_install_error() 	{ return "install-error"; }
	
	function dnshttp_api_output_blacklist_error() 	{ return "ip-blacklisted"; }
	
	function dnshttp_api_blacklist_check($ipbl) 	{ if($ipbl->isblocked()) { echo "ip-blacklisted"; exit(); } }
	
	function dnshttp_api_token_check($mysql, $token) 		{ 
		if(!dnshttp_api_token_valid($mysql, $token)) {
			$ipbl->raise(); 
			echo "token-error";
			exit();
		}
	}
	
	function dnshttp_api_token_valid($mysql, $token) {
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token ?? '');
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE TRIM(api_token) = ?", false, $bind);
		if(is_array($res)) { return true;}  else { return false; }}

	function dnshttp_server_get($mysql, $id) {
		if(is_numeric($id)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE id = '$id'", false);
		while (is_array($x)) { return $x; } } return false; }
		
	function dnshttp_server_check_and_set($mysql, $checkserverid) { server_check_and_set($mysql, $checkserverid); }
	
	function server_check_and_set($mysql, $checkserverid) {
		$apipathroot	=	dnshttp_server_get($mysql, $checkserverid)["api_path"];
		$apipath		=	$apipathroot."/_api/list_count.php";
		$returncurl 	=   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $checkserverid)["api_token"]);
		
		if(trim($returncurl ?? '') == "token-error") { 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 1 WHERE id = \"".$checkserverid."\";"); 
		}
		
		if($returncurl == "ip-blacklisted") { 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 1 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";"); 
		}
		
		if(trim($returncurl ?? '') == "install-error") { 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";"); 
		}
			
		if(is_numeric($returncurl)) {
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET domains = ".$returncurl." WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 1 WHERE id = \"".$checkserverid."\";");
			if($returncurl > 0) { 
				$mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 0 WHERE id = \"".$checkserverid."\";");
			}
		} else {
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";"); 
		}	
		
		$apipath		=	$apipathroot."/_api/status_compress.php";
		$returncurl =   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $checkserverid)["api_token"]);		
		

		if(trim($returncurl ?? '') == "online") { 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 2 WHERE id = \"".$checkserverid."\";");
		}		
		
	}
	
	function dnshttp_bind_domain_name_exists($mysql, $domain_name) { if(trim($domain_name ?? '') != "") { 
		$bind[0]["value"] = strtolower(trim($domain_name ?? ''));
		$bind[0]["type"] = "s";
		$x = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE TRIM(LOWER(domain)) = ?", false, $bind);
		if (is_array($x)) { return $x["id"]; } } return false; }	
		
	function dnshttp_bind_get($mysql, $id) { if(is_numeric($id)) { return $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = \"".$id."\"", false); } return false; }	
	
	function dnshttp_domapi_get($mysql, $id) { if(is_numeric($id)) { return $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE id = \"".$id."\"", false); } return false; }	
	
	function dnshttp_user_get_name_from_id($mysql, $userid) { 
		if(is_numeric($userid)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_USER_." WHERE id = '$userid'", false);
		while (is_array($x)) { return $x["user_name"]; } } return false; }	
		
	function dnshttp_api_token_generate($len = 128, $comb = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')  
		{$pass = array(); $combLen = strlen($comb) - 1; for ($i = 0; $i < $len; $i++) { $n = mt_rand(0, $combLen); $pass[] = $comb[$n]; } return implode($pass);}			
	
	function dnshttp_server_api_token_check($mysql, $token) { 
		return api_token_check($mysql, $token);
	}
	
	
	function api_token_check($mysql, $token) {
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token ?? '');
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE TRIM(api_token) = ?", false, $bind);
		if(is_array($res)) { return true;}  else { return false; }}
		
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
		  return $result;
	}	

	function dnshttp_isValidDomain(string $domain): bool {
		// Handle IDN (ö, ü, etc.)
		$ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
		if ($ascii === false) return false;

		return filter_var($ascii, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
			&& substr_count($ascii, '.') >= 1; // must have at least SLD + TLD
	}
	
	function dnshttp_conflicts_helper($srv) {
		$string = "";
		if($newarray = unserialize($srv) ) {
			foreach($newarray as $key => $value) {
				if(isset($value["domain_type"])) { $asd = $value["domain_type"]; } else { $asd = "Slave"; }
				if(isset($value["fk_server"])) {if($value["fk_server"] > 0) {  $asasd = " | Server-ID ".$value["fk_server"]; } else { $asasd = ""; } } else { $asasd = ""; }
				if(isset($value["fk_user"])) { if($value["fk_user"] > 0) { $asasd21 = " | User-ID ".$value["fk_user"]; } else { $asasd21 = ""; } } else { $asasd21 = ""; }
				$string .= "Type: ".@$asd.$asasd.$asasd21."<br />";
			}
			return $string;
		} else {
			return "Unknown";
		}
 	}
	
	/*************************************************************************
		Cronjob Execution
	*************************************************************************/	
	$log_output = "";
	function dnshttp_cron_sync($mysql, $log, $use_real_line_breaks = false) {
		global $log_output;
		#######################################################################################################################################
		// Logging Function for better HTML and Echo View
		$log_output = "";		
		$file_pre_per = "// This is a DnsHTTP Generated BIND Configuration File\r\n// Changes here will be persistent\r\n// The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_noper = "// This is a DnsHTTP Generated BIND Configuration File\r\n// Changes here will NOT be persistent\r\n// The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_zone_per = "; This is a DnsHTTP Generated BIND Configuration File\r\n; Changes here will be persistent\r\n; The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_zone_noper = "; This is a DnsHTTP Generated BIND Configuration File\r\n; Changes here will NOT be persistent\r\n; The bugfish.eu team wished you the best...woof!\r\n";
		function logging_add($text) {
			global $log_output;
			$use_real_line_breaks = false;
			if(php_sapi_name() === 'cli'){ $use_real_line_breaks = true; }
			$finaltext = $text;
			if($use_real_line_breaks != false) { echo $finaltext; }
			while(strpos($finaltext, "\r\n") != false) { $finaltext = str_replace("\r\n", "<br />", $finaltext); }
			if($use_real_line_breaks != true) { echo $finaltext; }
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
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0  WHERE LOWER(TRIM(domain)) = '".trim(strtolower($value["domain"]))."'");
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
		$servers = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE (server_type = 1 OR server_type = 3) AND enabled = 1", true);
		$error_servers = array();
		if(is_array($servers)) {	
			foreach($servers as $key => $value) {	
				server_check_and_set($mysql, $value["id"]);
				logging_add("START: Connecting to Server:  ".$value["api_path"]."\r\n");
				$value = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE (server_type = 1 OR server_type = 3) AND enabled = 1 AND id = '".$value["id"]."' ", false);
				if(!is_array($value)) { 
					logging_add("ERROR: Unknown Slave Server Response, please check connection!\r\n"); array_push($error_servers, $value["id"]);
					continue;
				}
				$output = "";
				$apipath	=	$value["api_path"]."/_api/list.php";
				$returncurl =   dnshttp_api_getcontent($mysql, $apipath, $value["api_token"]);	
				
				if($value["apiok"] == 0) 	{ 
					logging_add("ERROR: Unready Slave Server Response, please check connection!\r\n"); array_push($error_servers, $value["id"]);
					continue;
				}
				if($value["apiok"] == "1") 	{
				}
				if($value["apiok"] == "2") 	{ 
					$returncurl = dnshttp_decompress($returncurl);
				}
				
				$newarray = @unserialize($returncurl);
				if(is_array($newarray)) {
					$all_domains = array();
					foreach($newarray as $x => $y) {
						$bind[0]["value"] = strtolower(trim($y));
						$bind[0]["type"] = "s";	
						array_push($all_domains,strtolower(trim($y)) ); // Current Temp All API Server Domains
						$tmp = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_API_." WHERE fk_server = ".$value["id"]." AND LOWER(TRIM(domain)) = ?", false, $bind);
						if(is_array($tmp)) {
							$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET last_update = CURRENT_TIMESTAMP() WHERE id = '".$tmp["id"]."' AND fk_server = ".$value["id"].";");	
							logging_add("OK: Still Existing Slave Domain: ".$y."\r\n");
						} else {						
							$mysql->query("INSERT INTO "._TABLE_DOMAIN_API_."(domain, fk_server) VALUES(?, '".$value["id"]."');", $bind);	
							logging_add("OK: Inserted Slave Domain: ".$y."\r\n");
						}
					}
					logging_add("INFO: Now Cleanup deleted domains...\r\n");
					$domains	= $mysql->select("SELECT domain, id FROM "._TABLE_DOMAIN_API_." WHERE fk_server = '".$value["id"]."'", true);	
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
							}
						}
					}
					
				} else { logging_add("ERROR: Bad Slave Server Response, please check connection!\r\n"); array_push($error_servers, $value["id"]); }
			} 
		} else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_API_."");logging_add("OK: There are no Slave Servers, all Remote Domains Cleared!\r\n"); }
		logging_add("FINISHED: LAST OPERATION\r\n");	
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Delete All API Domains not Related to a Server	
		logging_add("START: Cleanup unrelated Remote Domains!\r\n");
		$domains = $mysql->select("SELECT fk_server, domain, id FROM "._TABLE_DOMAIN_API_." ORDER BY id DESC", true);
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				if(is_array(dnshttp_server_get($mysql, $value["fk_server"]))) {
				} else {
					logging_add("OK: Unrelated Domain Deleted: ".$value["domain"]." from server-id: ".$value["fk_server"]."\r\n");
					$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = '".$value["id"]."';");
				}
			} 
		} 
		logging_add("FINISHED: LAST OPERATION\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Get External Domains Content
		logging_add("START: Get (replicate) External Domains Content!\r\n");
		$domains = $mysql->select("SELECT fk_server, domain, id FROM "._TABLE_DOMAIN_API_." ORDER BY id DESC", true);
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				$serverdata = 	dnshttp_server_get($mysql, $value["fk_server"]);
				if(!$serverdata) 	{ 
					logging_add("SKIPPED: Server Not Found : $domain\r\n");
					continue;
				}		
				$apipath	=	$serverdata["api_path"]."/_api/content.php";
				$returncurl =   dnshttp_api_getcontent($mysql, $apipath, $serverdata["api_token"], $value["domain"]);

				if($serverdata["enabled"] != "1") 	{ 
					logging_add("SKIPPED: Server Disabled : $domain\r\n");
					continue;
				}		
				if($serverdata["apiok"] == "0") 	{ 
					logging_add("SKIPPED: Server Error : $domain\r\n");
					continue;
				}				
				
				if($serverdata["apiok"] == "2") 	{ 
					$returncurl = dnshttp_decompress($returncurl);
				}				
				
				$domain = $value["domain"];
				if(in_array($value["fk_server"], $error_servers)) {
					logging_add("SKIPPED: Server Timeout : $domain\r\n");
					continue;
				}
				if($returncurl AND $returncurl != "error-domain-no-exist") { 
					//echo $returncurl;
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $returncurl;
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET content = ?, modification = CURRENT_TIMESTAMP() WHERE id = '".$value["id"]."';", $bind);
					logging_add("OK: Fetched content for: $domain\r\n");	
				} elseif($returncurl == "error-domain-no-exist") {
					$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = ".$value["id"].";");
				} else { logging_add("ERROR: Can not get Content for $domain : Invalid Return Data!\r\n"); }
			} 
		} logging_add("FINISHED: LAST OPERATION\r\n");
		logging_add("........................................................................................................................................\r\n");		
		##################################################################################################################
		// Check for Conflicts		
		logging_add("START: Check for Domain Conflicts!\r\n");
		$domains_full = array();
		$domains_posted = array();
		$domains_bind = $mysql->select("SELECT domain_type, domain, id, preferred, conflict, fk_user FROM "._TABLE_DOMAIN_BIND_." ORDER BY domain", true);
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
			
			if($conflict AND is_array($conflictarray[0])  AND is_array(@$conflictarray[1])) {
				
				$handleinel = serialize($conflictarray); 
				
				$bind12 = array();
				$bind12[0]["value"] = $handleinel;
				
				$bind = array();
				$bind[0]["value"] = trim(strtolower($domainname_current));
				
				$selector = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE LOWER(TRIM(domain)) = ?", false, $bind);
				if(is_array($selector)) {
					if( !$preferredhandle ) { $mysql->query("UPDATE "._TABLE_CONFLICT_." SET servers = ?, solved = 0 WHERE id = '".$selector["id"]."'", $bind12); }
					else { $mysql->query("UPDATE "._TABLE_CONFLICT_." SET servers = ? WHERE id = '".$selector["id"]."'", $bind12); } 
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
				} else {  
						$bind12 = array();
						$bind12[0]["value"] = trim(strtolower($domainname_current));
						$bind12[1]["value"] = $handleinel;
						  $mysql->query("INSERT INTO "._TABLE_CONFLICT_." (domain, servers, solved) VALUES(?, ?, 0)", $bind12); 
						  $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
						  $mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
				}			

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
					if(isset($preferredhandle["fk_user"])) { $asdsas7hfds23 = $preferredhandle["fk_user"]; } else { $asdsas7hfds23 = "None";  }
					
					$bind = array();
					$bind[0]["value"] = "Domain: ".trim(strtolower($domainname_current))." | Server-ID: ".@$asdsas7hfds." | User-ID: ".@$asdsas7hfds23."";
					$bind[1]["value"] = trim(strtolower($domainname_current));
					
					$mysql->query("UPDATE "._TABLE_CONFLICT_." SET solved = ? WHERE LOWER(TRIM(domain)) = ?", $bind);
					
					$postnow = true;
					foreach($domains_posted AS $keasdf => $afng3uk) {
						if($afng3uk == strtolower(trim($domainname_current))) {
							$postnow = false;
						}
						
					} if($postnow) { logging_add("OK: This Domain has a solved Conflict: $domainname_current\r\n"); }
					array_push($domains_posted, trim(strtolower($domainname_current)));
				}
				
			} else { 
				$bind = array();
				$bind[0]["value"] = trim(strtolower($domainname_current));
				$bind[0]["type"] = "s";		
				$mysql->query("DELETE FROM "._TABLE_CONFLICT_." WHERE LOWER(TRIM(domain)) = ?", $bind); 
				$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 0 WHERE LOWER(TRIM(domain)) = ?;", $bind);
				$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 0 WHERE LOWER(TRIM(domain)) = ?;", $bind); 
			}	
		
		}
		unset($domains_bind);
		unset($domains_api);
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		
		##################################################################################################################
		// Write Domain Files and Table File at Once		
		$conf_buildstring = $file_pre_noper."";
		$safeString = (new DateTimeImmutable())->format('Y-m-d H:i:s');
		logging_add("START: Writing Slave Zones\r\n");
		$mysql->select("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck = 2, oldzonefallback = 2, zonecheck_failmessage = 'none', zonecheck_message = 'none', registered = 2 "); // Reset Domains State
		$domains = $mysql->select("SELECT domain, content, id, fk_server, okonce FROM "._TABLE_DOMAIN_API_." WHERE ( conflict = 0 OR (conflict = 1 AND preferred = 1) ) ORDER BY id DESC", true);
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
					

					$bindyd = array();
					$bindyd[0]["value"] = strtolower(trim($value["domain"]));
					$checkregistertable = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_REG_." WHERE LOWER(TRIM(domain)) = ?", false, $bindyd);
					if(is_array($checkregistertable)) {
						$bindyd = array();
						$bindyd[0]["value"] = $value["content"];
						$bindyd[1]["value"] = strtolower(trim($value["domain"]));
						$mysql->query("UPDATE "._TABLE_DOMAIN_REG_." SET content = ?, time_data_removal = '".$safeString."' WHERE LOWER(TRIM(domain)) = ?", $bindyd);
					} else {
						$bindyd = array();
						$bindyd[0]["value"] = strtolower(trim($value["domain"]));
						$bindyd[1]["value"] = $value["content"];
						$mysql->query("INSERT INTO "._TABLE_DOMAIN_REG_."(domain, content, time_data_removal) VALUES(?, ?, '".$safeString."')", $bindyd);
					}				
					
					//if(!REMOVED_CONSTANT) {
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
		$domains = $mysql->select("SELECT id, domain, content, okonce FROM "._TABLE_DOMAIN_BIND_." WHERE ( conflict = 0 OR (conflict = 1 AND preferred = 1) ) ORDER BY id DESC", true);
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
				$nowwritte = false;
				if(strpos(strtolower($cout), ": loaded serial") > -1) {
					$isnowregister = true;				
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck = 1, okonce = 1, registered = 1 WHERE id = '".$value["id"]."'");
					
					$bindyd = array();
					$bindyd[0]["value"] = strtolower(trim($value["domain"]));
					$checkregistertable = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_REG_." WHERE LOWER(TRIM(domain)) = ?", false, $bindyd);
					if(is_array($checkregistertable)) {
						$bindyd = array();
						$bindyd[0]["value"] = $value["content"];
						$bindyd[1]["value"] = strtolower(trim($value["domain"]));
						$mysql->query("UPDATE "._TABLE_DOMAIN_REG_." SET content = ?, time_data_removal = '".$safeString."' WHERE LOWER(TRIM(domain)) = ?", $bindyd);
					} else {
						$bindyd = array();
						$bindyd[0]["value"] = strtolower(trim($value["domain"]));
						$bindyd[1]["value"] = $value["content"];
						$mysql->query("INSERT INTO "._TABLE_DOMAIN_REG_."(domain, content, time_data_removal) VALUES(?, ?, '".$safeString."')", $bindyd);
					}
					
					//if(!REMOVED_CONSTANT) {
						//$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype slave;\r\n\tmasterfile-format text;\r\n\tmasters { ".trim($relay["ip"])."; };\r\n\tallow-transfer { ".$localmasterservers." };\r\n\tfile \"".$filenamecleared."\";\r\n};\r\n";	
					//} else {
					//	$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n};";
					//}
					$nowwritte = true;
					if(trim($value["domain"]) != ".") { $conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n\tallow-transfer { ".$allserverlist." };\r\n\tallow-update { ".$allserverlist." };\r\n};";}
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
				
				if(!$nowwritte AND @file_exists(@$filenamecleared) AND _CRON_BIND_FILE_REWRITE_) {
					if(trim($value["domain"]) != ".") { $conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n\tallow-transfer { ".$allserverlist." };\r\n\tallow-update { ".$allserverlist." };\r\n};";}				
				}
			}
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		if(file_exists(_CRON_BIND_FILE_TABLE_)) { @unlink(_CRON_BIND_FILE_TABLE_); }
		if(file_exists(_CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master")) { 
			$rnew = $conf_buildstring."\r\n\r\nzone \".\" {\r\n\ttype hint;\r\n\tfile \""._CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master\";\r\n};"; 
		} else { $rnew = $conf_buildstring ;}
		@file_put_contents(_CRON_BIND_FILE_TABLE_, $rnew);	
		logging_add("OK: Trying to write Zone Table File: "._CRON_BIND_FILE_TABLE_."\r\n");
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		//  Update the Public API Listing for Domains and Updates
		logging_add("START: Safely Remove Master Registration Table Expired Domains\r\n");
		$mysql->query("DELETE FROM "._TABLE_DOMAIN_REG_." WHERE time_data_removal <> '".$safeString."'");
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		//  _CRON_BIND_FILE_REWRITE_ foR virtualmin
		if(_CRON_BIND_FILE_REWRITE_ AND _CRON_BIND_FILE_) { 
			logging_add("INFO: _CRON_BIND_FILE_ is set and _CRON_BIND_FILE_REWRITE_ is set!\r\n");
			logging_add("INFO: Now rewriting the _CRON_BIND_FILE_ if nothing has changed in the meantime.!\r\n");
			if(file_exists(_CRON_BIND_FILE_)) { 
				@unlink(_CRON_BIND_FILE_TEMP_."file1_tmp");
				@copy(_CRON_BIND_FILE_, _CRON_BIND_FILE_TEMP_."file1_tmp");
				//check if file same
				if(md5_file(_CRON_BIND_FILE_TEMP_."file1_tmp") === md5_file(_CRON_BIND_FILE_)) {
					@unlink(_CRON_BIND_FILE_);
					if(file_exists(_CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master")) { $rnew = $conf_buildstring."\r\n\r\nzone \".\" {\r\n\ttype hint;\r\n\tfile \""._CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master\";\r\n};"; }
					else { $rnew = $conf_buildstring ;}
					@file_put_contents(_CRON_BIND_FILE_, $rnew);	
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
		
		logging_add("OK: chmod 777 -R /etc/bind;\r\n"); 
		@shell_exec("chmod 777 -R /etc/bind;");
		
		// Restart Named Service
		logging_add("OK: systemctl restart "._BIND_SERVICE_NAME_.";\r\n"); 
		@shell_exec("systemctl restart "._BIND_SERVICE_NAME_."; ");
		@shell_exec("supervisorctl restart bind9");
		logging_add("FINISHED: LAST OPERATION\r\n"); 
		logging_add("........................................................................................................................................\r\n");
		// Logfile Message
		logging_add("OK: Execution Done at ".date("Y-m-d H:m:i")."");
		$log->info($log_output, "cron_sync");
	} 