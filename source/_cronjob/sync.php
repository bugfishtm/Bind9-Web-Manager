<?php
	// Configurations Include
		require_once(dirname(__FILE__) ."/../settings.php");
		
	// Create needed Folders with Permissions
	$log_output = "";	
	function internal_cronlog($text) {
		global $log_output;
		$finaltext = $text;
		echo $text;
		while(strpos($finaltext, "\r\n") != false) { $finaltext = str_replace("\r\n", "<br />", $finaltext); }
		if(substr($finaltext, 0, 2) == "OK") { $finaltext = "<font color='lime'>".$finaltext."</font>"; }
		elseif(substr($finaltext, 0, 2) == "FI") { $finaltext = "<font color='yellow'>".$finaltext."</font>"; }
		elseif(substr($finaltext, 0, 2) == "ER") { $finaltext = "<font color='red'>".$finaltext."</font>"; }
		elseif(substr($finaltext, 0, 2) == "WA") { $finaltext = "<font color='red'>".$finaltext."</font>"; }
		elseif(substr($finaltext, 0, 2) == "IN") { $finaltext = "<font color='lightblue'>".$finaltext."</font>"; }
		elseif(substr($finaltext, 0, 2) == "ST") { $finaltext = "<font color='yellow'>".$finaltext."</font>"; }
		$log_output .= $finaltext;}
		
	######################################################### GET THE LOCAL DOMAINS TO BIND TABLE FROM FILES AND NAMED FILE
	$internaldidrun	=	false; if(defined("_CRON_BIND_FILE_FETCHED_") AND defined("_CRON_FILES_FOLDER_FETCH_") AND defined("_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_") AND defined("_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_")) { if(_CRON_FILES_FOLDER_FETCH_ !== false) {	
		internal_cronlog("OK: Starting the Cronjob\r\n\r\n");
		internal_cronlog("START: Fetching Local Template Files and Write Zone Listing File\r\n");
		internal_cronlog("INFO: Fetching Local File Domains if Hosting Panel does not Update named.conf.local, but template Files Exists with Domain as Filename.\r\n");
		internal_cronlog("INFO: '_CRON_FILES_FOLDER_FETCH_' is set to '"._CRON_FILES_FOLDER_FETCH_."' \r\n");
		internal_cronlog("INFO: '_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_' is set to '"._CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_."' \r\n");
		internal_cronlog("INFO: '_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_' is set to '"._CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_."' \r\n");
		$output = "";
		foreach (glob(_CRON_FILES_FOLDER_FETCH_."/*") as $filename){ 
			$internaldidrun	=	true;	
			internal_cronlog("Processing File: $filename \r\n");
			$realname = substr(basename($filename), _CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_);
			$realname = substr(basename($realname), 0, strlen($realname) - _CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_);
            $output .= "
zone \"".$realname."\" {
        type master;
        file \"".$filename."\";
};
";}		
		// Write the Named.Conf.Local File
		$myfile = fopen(_CRON_BIND_FILE_FETCHED_, "w");
		if(fwrite($myfile, $output)) {
			@fclose($myfile); internal_cronlog("FINISHED: Zone Configuration File List has been Written To: "._CRON_BIND_FILE_FETCHED_." \r\n\r\n");
		} else {
			@fclose($myfile); internal_cronlog("ERROR: Zone Configuration File List could not been Written To: "._CRON_BIND_FILE_FETCHED_." \r\n\r\n");
		}
	}}
	##################################################################################################################
	$all_domains = array();	// All Domains Fetched to check with Cleanup, will be deleted after cleanup rune (values content)
	$real_all_domains = array(); // Real All Local Domains after Cleanup	
	$bind_all_domains = array(); // Real All Local Domains after Cleanup	
	##################################################################################################################
	internal_cronlog("START: Domain List File will \r\nnow be transfered into local bind storage on database with content.\r\n");
	internal_cronlog("INFO: '_CRON_BIND_FILE_' is set to '"._CRON_BIND_FILE_."' \r\n");
	#########################################################
	$handle = fopen(_CRON_BIND_FILE_, "r"); if ($handle) {
			while (($line = fgets($handle)) !== false) { failrestart: 
				if(strpos($line, "zone ") > -1 AND strpos($line, ".in-addr.arpa") === false AND strpos($line, "localhost") === false) {					
					preg_match('/"(.*?)"/', $line, $match); $found = false; $foundline = false;
					$domain = strtolower(trim($match[1]));
					while(($line = fgets($handle)) !== false) { if($line == false) { internal_cronlog("ERROR: No File Path in List for $domain\r\n"); goto endthis;} if(strpos($line, "file") > -1) { $found = true; $foundline = $line; goto isnowok;} else { if(strpos($line, "zone ") > -1) { internal_cronlog("ERROR: No File Path in List for $domain\r\n"); goto failrestart; } } }
					isnowok: 
					$realpathnow =substr($foundline, strpos($foundline, "\"") + 1);
					$realpathnow =substr($realpathnow, 0, strpos($realpathnow, "\"") );
					
					if($domain != "") {
						array_push($all_domains, $domain );
						array_push($bind_all_domains, strtolower($domain) );
						$realcontentfilename = basename($realpathnow);
						$realcontentfilewithpath = $realpathnow;
						if($x = dnshttp_bind_domain_name_exists($mysql, $domain) AND file_exists($realcontentfilewithpath)) {	
							$gg = fopen($realcontentfilewithpath, 'r');
							if($gg) { 
								$readtext = fread($gg, filesize($realcontentfilewithpath));	
								$bind[0]["type"] = "s";
								$bind[0]["value"] = $readtext;									
								$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET content = ?, modification = CURRENT_TIMESTAMP() WHERE id = '".$x."';", $bind);			
								internal_cronlog("OK: Updated Local Domain: $domain\r\n");
							} else {
								$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET content = 'local-fetch-not-found', modification = CURRENT_TIMESTAMP() WHERE id = '".$x."';");	
								internal_cronlog("WARN: Updated Local Domain : $domain : <font color='red'>(But no zone file found - no changes on domain content)</font>\r\n");	
							}
						} else {
							$mysql->query("INSERT INTO "._TABLE_DOMAIN_BIND_."(domain, content) VALUES('".$mysql->escape($domain)."', 'waiting-for-next-cron-run');");
							internal_cronlog("OK: Added Local Domain: $domain\r\n");		
						}	
					}
				}
			} endthis:
			fclose($handle); internal_cronlog("FINISHED: Updated Local Domains to Database From: "._CRON_BIND_FILE_."\r\n\r\n");	
	} else {  internal_cronlog("ERROR: Could not open File: "._CRON_BIND_FILE_."\r\n\r\n");		 } 
	#########################################################
	internal_cronlog("START: Cleanup Local Domains Which has need Added in \r\nprevious Cron Runs, but now are no more in Zone List File.\r\n");
	#########################################################
	$real_all_domains	= $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_."", true);
	if(is_array($real_all_domains)) {
		foreach($real_all_domains as $key => $value) {
			$deleteable = true;
			if(is_array($all_domains)) {
				foreach($all_domains as $x => $y) {
					if(strtolower(trim($y)) == strtolower(trim($value["domain"]))) { $deleteable = false; }
				}
			}
			if(is_numeric($value["fk_user"])) { $deleteable = false; }
			if($deleteable) {
				 internal_cronlog("OK: Removed Local Domain: ".$value["domain"]."\r\n");
				 $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$value["id"]."'"); 
			}
		} internal_cronlog("FINISHED: Local Domain Cleanup in Database\r\n\r\n");	
	} else { internal_cronlog("FINISHED: There are no local domains!\r\n\r\n");	 }
	# --------------------------------------------------------------------------------------
	$all_domains = array(); // Zero Array
	$real_all_domains = array(); // Zero Array
	$all_domains_full = array(); // All Fetched Domains From Slaves with first Domain and Second ID
	if(!is_array($real_all_domains)) { $real_all_domains = array(); }
	# --------------------------------------------------------------------------------------
	internal_cronlog("START: Get Domains From Master Servers\r\n");
	# --------------------------------------------------------------------------------------		
	$servers = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE server_type = 1 OR server_type = 3", true);
	if(is_array($servers)) {
		foreach($servers as $key => $value) {
			internal_cronlog("START: Connecting to Server:  ".$value["api_path"]."\r\n");
			$apipath	=	$value["api_path"]."/_api/list.php";
			$returncurl =   dnshttp_api_getcontent($mysql, $apipath, $value["api_token"]);
			if($newarray = @unserialize($returncurl)) {
				if(isset($newarray[0])) {
					$all_domains = array();
					foreach($newarray as $x => $y) {
						array_push($all_domains_full,array( strtolower(trim($y)),  $value["id"]) );
						array_push($all_domains,strtolower(trim($y)) ); // Current Temp All API Server Domains
						$bind[0]["value"] = trim($y);
						$bind[0]["type"] = "s";						
						$tmp = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE fk_server = ".$value["id"]." AND domain = ?", false, $bind);
						if(is_array($tmp)) {
							$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET fk_server = '".$value["id"]."', modification = CURRENT_TIMESTAMP() WHERE id = '".$tmp["id"]."';");	
							internal_cronlog("OK: Updated Slave Domain: ".$y."\r\n");
						} else {
							$mysql->query("INSERT INTO "._TABLE_DOMAIN_API_."(domain, content, fk_server) VALUES(?, '0', '".$value["id"]."');", $bind);	
							internal_cronlog("OK: Inserted Slave Domain: ".$y."\r\n");
						}
					}
					internal_cronlog("INFO: Cleanup for Server:  ".$value["api_path"]."\r\n");
					$real_all_domains	= $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_."", true);	
					if(is_array($real_all_domains)) {
						foreach($real_all_domains as $key => $value) {
							$deleteable = true;
							if(is_array($all_domains)) {
								foreach($all_domains as $x => $y) {
									if(trim(strtolower($y)) == trim(strtolower($value["domain"]))) { $deleteable = false; }
								}
							}								
							if($deleteable) { 
								internal_cronlog("OK: Deleted Expired Slave Domain: $y\r\n");							
								$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = '".$value["id"]."'");
							}
						}
					}
				} else { internal_cronlog("OK: All Slave Server Domains deleted, The Domain List of Slave Server is empty!\r\n"); }
			} else { internal_cronlog("ERROR: Server Respond is invalid!\r\n"); }
		} 
	} 

	internal_cronlog("FINISHED: LAST OPERATION\r\n\r\n"); 
	# --------------------------------------------------------------------------------------
	internal_cronlog("START: Check for Duplicates in Slave Domains (Conflict)!\r\n");
	foreach($all_domains_full as $key => $value) {
		$domainname_current = strtolower($value[0]);
		$conflict = false;
		$conflictarray = array();
		foreach($all_domains_full as $x => $y) {
			// Check Slave Domain Conflict
			if($x != $key AND strtolower($y[0]) == strtolower($value[0])) { $conflict = true;array_push($conflictarray, $y); } 
		}
		foreach($bind_all_domains as $x => $y) {
			// Check Bind Domain Conflict
			if(strtolower($y) == strtolower($value[0])) { $conflict = true; array_push($conflictarray, array(strtolower($value[0]), "0")); } 
		}
		
		if($conflict AND isset($conflictarray[0])) {
			$serverout = ""; $domainxx = ""; 
			foreach($conflictarray as $keyxxx => $valuexxx) { $domainxx = $valuexxx[0]; $serverout .= "[".$valuexxx[1]."] "; }
			internal_cronlog("ERROR: This Domain has a Conflict: $domainname_current on Servers: $serverout\r\n");
			$bind[0]["value"] = trim($domainname_current);
			$bind[0]["type"] = "s";
			$selector = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE domain = ?", false, $bind);
			if(is_array($selector)) {
				$mysql->query("UPDATE "._TABLE_CONFLICT_." SET servers = '".$mysql->escape(serialize($conflictarray))."' WHERE id = '".$selector["id"]."'");
				$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(domain) = '".$domainxx."';");
			} else {  $mysql->query("INSERT INTO "._TABLE_CONFLICT_." (domain, servers) VALUES(?, '".$mysql->escape(serialize($conflictarray))."')", $bind); 
					  $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(domain) = '".$domainxx."';");}  
		} else { $mysql->query("DELETE FROM "._TABLE_CONFLICT_." WHERE domain = '".$mysql->escape(trim($domainname_current))."'"); $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 0 WHERE LOWER(domain) = '".trim($domainname_current)."';"); internal_cronlog("OK: No conflicts on: ".trim($domainname_current)."\r\n"); 	}		
	}

	internal_cronlog("FINISHED: LAST OPERATION\r\n\r\n"); 
	# --------------------------------------------------------------------------------------
	internal_cronlog("START: Fetching Slave Domain Content From Master Servers!\r\n");
	$domains = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." ORDER BY modification DESC", true);
	if(is_array($domains)) {
		foreach($domains as $key => $value) {
			$apipath	=	dnshttp_server_get($mysql, $value["fk_server"])["api_path"]."/_api/content.php";
			$returncurl =   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $value["fk_server"])["api_token"], $value["domain"]);
			$domain = $value["domain"];
			if($returncurl) {
				if(strpos($returncurl, "SOA") > 5) {
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $returncurl;				
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET content = ?, modification = CURRENT_TIMESTAMP() WHERE id = '".$value["id"]."';", $bind);
					internal_cronlog("OK: Fetched content for: $domain\r\n");			
				} else { internal_cronlog("ERROR: Content has not been added for $domain (Seems malicious - no SAO string inside content file)\r\n"); }
			} else { internal_cronlog("ERROR: Can not get Content for $domain : Invalid Curl Return Data!\r\n");	 }
		} 
	} 
		
	internal_cronlog("FINISHED: LAST OPERATION\r\n\r\n"); 
	# --------------------------------------------------------------------------------------
		internal_cronlog("START: Write Slave Zone Listfile and Zone Files! \r\n");
		internal_cronlog("INFO: Folder to save zone files to: "._CRON_BIND_LIB_."\r\n");
		internal_cronlog("INFO: Extension Name of Zonefiles: "._CRON_BIND_LIB_ENDING_."\r\n");
		internal_cronlog("INFO: Owned User for Zonefiles: "._CRON_BIND_LIB_USER_."\r\n");
		internal_cronlog("INFO: Owned Group for Zonefiles: "._CRON_BIND_LIB_GROUP_."\r\n");
		internal_cronlog("INFO: Owned CHMOD for Zonefiles: "._CRON_BIND_LIB_CODE_."\r\n");
		internal_cronlog("INFO: Zone List File to Slave Domains: "._CRON_BIND_FILE_DNSHTTP_."\r\n");
		$domains = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE content <> '0' AND ( conflict = 0 OR (conflict = 1 AND preferred = 1) ) ORDER BY modification DESC", true);
		if(is_array($domains)) {
			// Write the dnshttp.conf.local FILE
			$conf_buildstring = "";
			foreach($domains as $key => $value) {
				$relay = dnshttp_server_get($mysql, $value["fk_server"]);
				$conf_buildstring .= '
				
zone "'.trim($value["domain"]).'" {
	type slave;
	masters {'.trim($relay["ip"]).'; };
	allow-transfer { '.trim($relay["ip"]).'; };
	file "'._CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_.'";
};	

';			}	
			// Write the Domain Files				
			foreach($domains as $key => $value) {				
				if(file_exists(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_)) { @unlink(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_); } 
				file_put_contents(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_, $value["content"]);	
				internal_cronlog("OK: Written Zone File: "._CRON_BIND_LIB_."/".$value["domain"]._CRON_BIND_LIB_ENDING_."\r\n");
				@chown(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_, _CRON_BIND_LIB_USER_);
				@chgrp(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_, _CRON_BIND_LIB_GROUP_);
				@chmod(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_, _CRON_BIND_LIB_CODE_);
			}	
			internal_cronlog("INFO: All Zone Files written!\r\n");
			internal_cronlog("OK: Written Zone List File: "._CRON_BIND_FILE_DNSHTTP_."\r\n");
			if(file_exists(_CRON_BIND_FILE_DNSHTTP_)) { @unlink(_CRON_BIND_FILE_DNSHTTP_); }
			file_put_contents(_CRON_BIND_FILE_DNSHTTP_, $conf_buildstring);
		}
		
		
		internal_cronlog("INFO: Delete Old ZoneFiles not Used Anymore!\r\n");
		$domains_binded = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BINDED_."", true);
		if(is_array($domains)) {		
			if(is_array($domains_binded)) {
				foreach($domains_binded as $key => $value) {
					$delete = true;
					foreach($domains as $y => $x) {
						if(trim($x["domain"]) == trim($value["domain"]))  { $delete = false; } 
					}
					if($delete) { 	
						if(file_exists(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_)) {
							@unlink(_CRON_BIND_LIB_."/".trim($value["domain"])._CRON_BIND_LIB_ENDING_);		
							internal_cronlog("OK: Deleted expired hostfile for: ".$value["domain"]."\r\n");
						}						
						$bind[0]["type"] = "s";
						$bind[0]["value"] = trim($value["domain"]);							 
						$mysql->query("DELETE FROM "._TABLE_DOMAIN_BINDED_." WHERE domain = ?", $bind);	
					}	
				}	
			}
			foreach($domains as $key => $value) {	
				$inserted = false;
				if(is_array($domains_binded)) {	
					foreach($domains_binded as $y => $x) {
							if(trim($x["domain"]) == trim($value["domain"]))  { $inserted = true; } 
					}
				}
				if(!$inserted) {
					$bind[0]["type"] = "s";
					$bind[0]["value"] = trim($value["domain"]);				
					$mysql->query("INSERT INTO "._TABLE_DOMAIN_BINDED_." (domain) VALUES(?);", $bind);			
				}
			}
		} 
	internal_cronlog("FINISHED: LAST OPERATION\r\n\r\n"); 
	# --------------------------------------------------------------------------------------
	internal_cronlog("START: Set File Permissions and Restart Bind9 \r\n");
	if(file_exists(_CRON_BIND_LIB_)) { 
	
		internal_cronlog("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_LIB_.";\r\n");
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_LIB_.";");

		internal_cronlog("OK: chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_LIB_.";\r\n");
		@shell_exec("chmod "._CRON_BIND_LIB_CODE_." "._CRON_BIND_LIB_.";");
		
		internal_cronlog("OK: systemctl restart bind9;\r\n");
		@shell_exec("systemctl restart bind9; ");
		
	} else { internal_cronlog("ERROR: Executions not Done, "._CRON_BIND_LIB_.": Folder does not exists!\r\n"); }
	
	internal_cronlog("FINISHED: LAST OPERATION\r\n\r\n"); 
	# --------------------------------------------------------------------------------------
	// Logfile Message
	internal_cronlog("OK: Execution Done at ".date("Y-m-d H:m:i")."");
	$log->info($log_output);
?>