<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  MySQL Control Class */
	class x_class_mysql {
		/*	___________     ___.   .__                 
			\__    ___/____ \_ |__ |  |   ____   ______
			  |    |  \__  \ | __ \|  | _/ __ \ /  ___/
			  |    |   / __ \| \_\ \  |_\  ___/ \___ \ 
			  |____|  (____  /___  /____/\___  >____  >
						   \/    \/          \/     \/		Create Table for Logging*/
		private function create_table() {
			return $this->query("CREATE TABLE IF NOT EXISTS `".$this->logging_table."` (
								  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Indentificator',
								  `url` varchar(256) DEFAULT NULL COMMENT 'Related URL',
								  `init` text NULL COMMENT 'Init Data if There is some',
								  `exception` text NULL COMMENT 'Error Exception Text',
								  `sqlerror` text NULL COMMENT 'Error MySQL Text',
								  `output` text NULL COMMENT 'Error MySQL Output',
								  `success` int(1) NULL COMMENT '1 - Query OK | 2 - Query Error',
								  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
								  `section` varchar(64) DEFAULT NULL COMMENT 'Related Section',
								  PRIMARY KEY (`id`));");}

		/*	.____                        .__                
			|    |    ____   ____   ____ |__| ____    ____  
			|    |   /  _ \ / ___\ / ___\|  |/    \  / ___\ 
			|    |__(  <_> ) /_/  > /_/  >  |   |  \/ /_/  >
			|_______ \____/\___  /\___  /|__|___|  /\___  / 
					\/    /_____//_____/         \//_____/ 		*/
			private $logfile_messages = false; public function logfile_messages($bool = false) { $this->logfile_messages = $bool; }
			private $logging_active = false; private $logging_table = ""; private $logging_section = ""; private $logging_all = false;
			public function log_disable() { $this->logging_active = false; }
			public function log_enable() { $this->logging_active = true; }
			public function log_config($table = "mysqllogging", $section = "", $logall = false) {
				$this->logging_active = true; 
				$this->logging_all = $logall; 
				$this->logging_table = $table; 
				$this->logging_section = $section;
				if(!$this->table_exists($this->logging_table)) { $this->create_table(); $this->free_all(); } }
			private function log($output, $sqlerror, $exception, $init, $boolsuccess, $nolog = false) { if($this->logging_active AND !$nolog) {
				if(!$this->logging_all AND (!@$exception OR (trim($sqlerror) == "" OR !$sqlerror))) { return false; }
				if(!$this->logging_all AND $boolsuccess != 0) { return false; }
				
				if( $this->logfile_messages) {
					error_log("X_CLASS_MYSQL_ERROR: [INIT] ".@serialize(@$init)." [EXCEPTION] ".@serialize(@$exception)." [SQLERROR] ".@serialize(@$sqlerror)." [OUTPUT] ".@serialize(@$output)." ");
				}
				
				$mysql = $this->log_con();
				$inarray["section"] = $this->logging_section;
				$inarray["url"] = @trim(@$_SERVER["REQUEST_URI"]);
				$inarray["sqlerror"] = @$sqlerror;
				$inarray["exception"] = @serialize(@$exception);
				if($exception) {$inarray["exception"] = "is_exception".$inarray["exception"];}
				$inarray["output"] = @serialize(@$output);
				$inarray["init"] = @$init;
				try {   $b[0]["type"] = "s";
						$b[0]["value"] = $inarray["url"];
						$b[1]["type"] = "s";
						$b[1]["value"] = $inarray["sqlerror"];
						$b[2]["type"] = "s";
						$b[2]["value"] = $inarray["exception"];
						$b[3]["type"] = "s";
						$b[3]["value"] = $inarray["init"];
						$b[4]["type"] = "s";
						$b[4]["value"] = $inarray["output"];
						$x = $mysql->query("INSERT INTO `".$this->logging_table."`(url, sqlerror, exception, init, output, section, success) VALUES(?, ?,?,?,?, \"".$inarray["section"]."\", ".$boolsuccess.");", $b);
						return $x;}
				 catch (Exception $e){ return false; }
				} return false; }
			private function log_con() { return new x_class_mysql($this->auth_host, $this->auth_user, $this->auth_pass, $this->auth_db); }	
				
		/*	__   ____ _ _ __ ___ 
			\ \ / / _` | '__/ __|
			 \ V / (_| | |  \__ \
			  \_/ \__,_|_|  |___/	*/			 
			public  $mysqlcon		  = false; // MySQLI Object
			private $transaction      = false; // Is a Transaction Running?
			public  $lasterror		  = false; // Error out of Last Request
			public  $fullerror	  	  = false; // Error Full out of Last Request
			public  $insert_id	  	  = false; // Last Insert ID
			private $stop_on_error	  = false; public function stop_on_error($bool = false) { $this->stop_on_error  = $bool; } // Stop if Error?
			private $display_on_error = false; public function display_on_error($bool = false) { $this->display_on_error  = $bool; } // Display Errors?				
												  
		/*	___.                        .__                          __    
			\_ |__   ____   ____   ____ |  |__   _____ _____ _______|  | __
			 | __ \_/ __ \ /    \_/ ___\|  |  \ /     \\__  \\_  __ \  |/ /
			 | \_\ \  ___/|   |  \  \___|   Y  \  Y Y  \/ __ \|  | \/    < 
			 |___  /\___  >___|  /\___  >___|  /__|_|  (____  /__|  |__|_ \
				 \/     \/     \/     \/     \/      \/     \/           \/ */								  
			private $bm	  	  = false;	private $bmcookie  = false; 				  
			private function benchmark_raise($raise = 1) {if( $this->bm) {  $_SESSION[$this->bmcookie."x_class_mysql"] = $_SESSION[$this->bmcookie."x_class_mysql"] + 1; } } 
			public function benchmark_get() { if( $this->bm) { return $_SESSION[$this->bmcookie."x_class_mysql"]; } return false;}						
			public function benchmark_config($bool = false, $preecookie = "") {$this->bmcookie = $preecookie;$this->bm  	= $bool;$_SESSION[$this->bmcookie."x_class_mysql"] = 0;}		
				
		/*	  ___ ___                    .___.__                
			 /   |   \_____    ____    __| _/|  |   ___________ 
			/    ~    \__  \  /    \  / __ | |  | _/ __ \_  __ \
			\    Y    // __ \|   |  \/ /_/ | |  |_\  ___/|  | \/
			 \___|_  /(____  /___|  /\____ | |____/\___  >__|   
				   \/      \/     \/      \/           \/        */					
		private function handler($excecution, $exception, $init, $nolog = false) {	
			// Benchmark Raise
			$this->benchmark_raise();
			$this->fullerror = array();
			// Handle Exception
			if(is_object($exception)) {
				// Set Last Error and Full Error
				$this->fullerror["mysql"] = @mysqli_error($this->mysqlcon);
				$this->fullerror["exception"] = @serialize(@$e);
				$this->fullerror["init"] = $init;
				$this->fullerror["output"] = @serialize(@$excecution);
				$this->lasterror = true;
				
				// Log Error
				$this->log($excecution, $this->fullerror["mysql"], $exception, $init, 0, $nolog);
				
				// Display and Stop
				if($this->display_on_error AND $this->lasterror) { echo print_r($this->fullerror); }
				if($this->stop_on_error AND $this->lasterror) { exit(); }
				
				// Return false on Exception
				return false;
			}

			// Set Last Error and Full Error
			$this->fullerror["mysql"] = @mysqli_error($this->mysqlcon);
			$this->fullerror["exception"] = false;
			$this->fullerror["init"] = $init;
			$this->fullerror["output"] = @serialize(@$excecution);
			
			// Set Last Error
			if(!$excecution) {$curer = 1;} else {$curer = 0;} 
			$this->lasterror = $curer;

			// Insert Last Insert ID
			@$this->insert_id = $this->mysqlcon->insert_id; 

			// Log if Needed
			if($this->lasterror) {$curer = 0;} else {$curer = 1;} 
			$this->log($excecution, $this->fullerror["mysql"], false, $init, $curer, $nolog);
			
			// Display and Stop
			if($this->display_on_error AND $this->lasterror) { echo print_r($this->fullerror); }
			if($this->stop_on_error AND $this->lasterror) { exit(); }
					
			// Return Execution if False / True
			return $excecution;}				
				
		/*	___________                           
			\_   _____/_____________  ___________ 
			 |    __)_\_  __ \_  __ \/  _ \_  __ \
			 |        \|  | \/|  | \(  <_> )  | \/
			/_______  /|__|   |__|   \____/|__|   
					\/                                */
		public function displayError($exit = false) {
			http_response_code(503);
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
			"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
			<html version="-//W3C//DTD XHTML 1.1//EN"
				  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"
				  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				  xsi:schemaLocation="http://www.w3.org/1999/xhtml
									  http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd">
				<head>
					<title>Database Error</title>
					<meta http-equiv="content-Type" content="text/html; utf-8" />
					<meta name="robots" content="noindex, nofollow" />
					<meta http-equiv="Pragma" content="no-cache" />
					<meta http-equiv="content-Language" content="en" />
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<style>
					html, body { background: blue; color: white; font-family: Arial; text-align: center; margin: 0 0 0 0; padding: 0 0 0 0; position: absolute; width: 100%; top: 0px; left: 0px; height: 100vh; }
					a { color: black; text-decoration: none; font-weight: bold; background: green; border-radius: 10px; font-size: 16px; padding: 15px; word-break: keep-all; white-space: nowrap; }		
					a:hover { color: black; text-decoration: none; font-weight: bold; background: white; border-radius: 10px; font-size: 16px; padding: 15px; }
					#dberrorwrapper { text-align: center; color: lightblue; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
					</style>
					<meta name="expires" content="0" />	
				</head>
				<body>
					<div id="dberrorwrapper"><font size="+5">Error 503</font><br/><font size="+3">Site under Maintenance...</font><br />Please check in later! x)<br />The MySQL Server Connection has failed!</div>
				</body></html>';if($exit){exit();}}				
				
		/*	___________     ___.   .__                 
			\__    ___/____ \_ |__ |  |   ____   ______
			  |    |  \__  \ | __ \|  | _/ __ \ /  ___/
			  |    |   / __ \| \_\ \  |_\  ___/ \___ \ 
			  |____|  (____  /___  /____/\___  >____  >
						   \/    \/          \/     \/  */
		public function table_exists($tablename){return $this->query("SELECT 1 FROM `".$tablename."` LIMIT 1;"); } 
		public function table_delete($tablename){return $this->update('DROP TABLE `'.$tablename.'`');}
		public function table_create($tablename){return $this->update('CREATE TABLE `'.$tablename.'`');}	
		public function auto_increment($table, $value){ return $this->query('ALTER TABLE `'.$tablename.'`'. " AUTO_INCREMENT = ".$value);}	
		public function table_backup($table, $filepath = false, $withdata = true, $dropstate = false){  $data = ""; $this->lasterror = false;
			// Create and Write Backup to File 
			if($dropstate) { $data.= "DROP TABLE IF EXISTS `{$table}`;\n"; }
			$res = $this->query("SHOW CREATE TABLE `{$table}`");
			$row = mysqli_fetch_row($res);
			$data.= $row[1].";\n";
			$result = $this->query("SELECT * FROM `{$table}`");
			$num_rows = @mysqli_num_rows($result);    
			if($withdata) {
				if($num_rows>0){
				  $vals = Array(); $z=0;
				  for($i=0; $i<$num_rows; $i++){
					$items = mysqli_fetch_row($result);
					$vals[$z]="(";
					for($j=0; $j<count($items); $j++){
					  if (isset($items[$j])) { $vals[$z].= "'".$this->escape( $items[$j] )."'"; } else { $vals[$z].= "NULL"; }
					  if ($j<(count($items)-1)){ $vals[$z].= ","; }
					}
					$vals[$z].= ")"; $z++;
				  }
				  $data.= "INSERT INTO `{$table}` VALUES ";      
				  $data .= "  ".implode(";\nINSERT INTO `{$table}` VALUES ", $vals).";\n";
				}
			  }
		    // Finalize
			if($filepath) { $handle = fopen($filepath,'w+');fwrite($handle,$data);fclose($handle); } return $data;}
		
		/*	________          __        ___.                                
			\______ \ _____ _/  |______ \_ |__ _____    ______ ____   ______
			 |    |  \\__  \\   __\__  \ | __ \\__  \  /  ___// __ \ /  ___/
			 |    `   \/ __ \|  |  / __ \| \_\ \/ __ \_\___ \\  ___/ \___ \ 
			/_______  (____  /__| (____  /___  (____  /____  >\___  >____  >
					\/     \/          \/    \/     \/     \/     \/     \/   */
		public function database_delete($database){return $this->update('DROP DATABASE `'.$database.'`');}
		public function database_create($database){return $this->update('CREATE DATABASE `'.$database.'`');}
		public function database_select($database) { try { return $this->handler(mysqli_select_db($this->mysqlcon, $database), false, "database_select: ".$database, false); } catch (Exception $e){ return $this->handler(false, $e, "database_select: ".$database, false); }}
			
		/*				  .__   __  .__                                     
			  _____  __ __|  |_/  |_|__|   ________ __   ___________ ___.__.
			 /     \|  |  \  |\   __\  |  / ____/  |  \_/ __ \_  __ <   |  |
			|  Y Y  \  |  /  |_|  | |  | < <_|  |  |  /\  ___/|  | \/\___  |
			|__|_|  /____/|____/__| |__|  \__   |____/  \___  >__|   / ____|
				  \/                         |__|           \/       \/        */
		public function multi_query($query) { 
			try { return $this->handler($this->mysqlcon->multi_query($query), false, "multi_query: ".$query, false);
			} catch (Exception $e){ return $this->handler(false, $e, "multi_query: ".$query, false); }}
		public function multi_query_file($file) { 
			if(file_exists($file)) {
				try { $sql = file_get_contents($file);
					return $this->handler($this->mysqlcon->multi_query($sql), false, "multi_query_file: ".$file, false);
				} catch (Exception $e){ return $this->handler(false, $e, "multi_query_file: ".$file, false); }
			} return false;}

		/*	 _                                  _   _                 
			| |                                | | (_)                
			| |_ _ __ __ _ _ __  ___  __ _  ___| |_ _  ___  _ __  ___ 
			| __| '__/ _` | '_ \/ __|/ _` |/ __| __| |/ _ \| '_ \/ __|
			| |_| | | (_| | | | \__ \ (_| | (__| |_| | (_) | | | \__ \
			 \__|_|  \__,_|_| |_|___/\__,_|\___|\__|_|\___/|_| |_|___/*/
		/********************** Start A Transaction if none is Running ****/
		public function transaction($autocommit = false) {try { if(!$this->transaction) { $this->mysqlcon->autocommit($autocommit); $this->transaction = true; return $this->handler(mysqli_begin_transaction($this->mysqlcon), false, "transaction: ".$autocommit, false);} return false; } catch (Exception $e){ return $this->handler(false, $e, "transaction: ".$autocommit, false); }}
		/********************** Rollback A Transaction if is Running ****/
		public function rollback() {try {if($this->transaction) { $this->transaction = false; return $this->handler(mysqli_rollback($this->mysqlcon), false, "rollback", false);}return false; } catch (Exception $e){ return $this->handler(false, $e, "rollback", false); }}
		/********************** Get Transaction Status ****/
		public function transactionStatus() { return $this->transaction; }
		/********************** Commit a Transaction ****/
		public function commit() {try { if($this->transaction) {  $this->transaction = false; return $this->handler(mysqli_commit($this->mysqlcon), false, "commit", false); } return false; } catch (Exception $e){ return $this->handler(false, $e, "commit", false); }}

		/*	____   ____      .__                        
			\   \ /   /____  |  |  __ __   ____   ______
			 \   Y   /\__  \ |  | |  |  \_/ __ \ /  ___/
			  \     /  / __ \|  |_|  |  /\  ___/ \___ \ 
			   \___/  (____  /____/____/  \___  >____  >
						   \/                 \/     \/ 	Decrease / Increase Values Dynamically and SQL Injection Safe! (shall be)*/
		public function row_element_increase($table, $nameidfield, $id, $increasefield, $increasevalue = 1){
			try {  return $this->update("UPDATE ".$table." SET ".$increasefield." = ".$increasefield." + ".$increasevalue." WHERE ".$nameidfield." = '".$id."'");
			} catch (Exception $e){ return $this->handler(false, $e, "row_element_increase: ".$table."; ".$nameidfield."; ".$id."; ".$increasefield.";", false); }}
				
		public function row_element_decrease($table, $nameidfield, $id, $decreasefield, $decreasevalue = 1){
			try{ return $this->update("UPDATE ".$table." SET ".$increasefield." = ".$decreasefield." + ".$decreasevalue." WHERE ".$nameidfield." = '".$id."'");
			} catch (Exception $e){ return $this->handler(false, $e, "row_element_decrease: ".$table."; ".$nameidfield."; ".$id."; ".$decreasefield.";", false); }}	
			
		/**********************  Get Values Dynamically and SQL Injection Safe! (shall be) ****/
		public function row_get($table, $id, $row = "id") { 
			$bindar[0]["value"] = $id;
			$bindar[0]["type"]  = "s";
			return $this->select("SELECT * FROM `".$table."` WHERE ".$row." = ?", false, $bindar); }
		public function row_element_get($table, $id, $elementrow, $fallback = false, $row = "id") { 
			$bindar[0]["value"] = $id;
			$bindar[0]["type"]  = "s";
			$ar =  $this->select("SELECT * FROM `".$table."` WHERE ".$row." = ?", false, $bindar); 
			if(is_array($ar)) {
				if(isset($ar[$elementrow])) {
					return $ar[$elementrow];
				} return $fallback;
			} else { return $fallback; }	}
		public function row_element_change($table, $id, $element, $elementrow, $row = "id") { 
			$bindar[0]["value"] = $element;
			$bindar[0]["type"]  = "s";
			$bindar[1]["value"] = $id;
			$bindar[1]["type"]  = "s";
			return $this->update("UPDATE `".$table."` SET ".$elementrow." = ? WHERE ".$row." = ?", false, $bindar); }		
		public function row_exist($table, $id, $row = "id") {  
			$bindar[0]["value"] = $id;
			$bindar[0]["type"]  = "s";		
			$tmp =  $this->select("SELECT * FROM `".$table."` WHERE ".$row." = ?", false, $bindar); if(is_array($tmp)) {return true;} else {return false;}}
		public function rows_get($table, $id, $row = "id") { 
			$bindar[0]["value"] = $id;
			$bindar[0]["type"]  = "s";
			return $this->select("SELECT * FROM `".$table."` WHERE ".$row." = ?", true, $bindar); 		}
		public function row_del($table, $id, $row = "id") { 
			$bindar[0]["value"] = $id;
			$bindar[0]["type"]  = "s";		
			return $this->query("DELETE FROM `".$table."` WHERE ".$row." = ?", $bindar); }				
			
		/*			   _          
					  (_)         
			 _ __ ___  _ ___  ___ 
			| '_ ` _ \| / __|/ __|
			| | | | | | \__ \ (__ 
			|_| |_| |_|_|___/\___|  Usefull Smart Functions */
		/********************** Destroy Connection ****/	
		function __destruct() { /* Nothing */ }
		/********************** Mysql Filter a Variable ****/	
		public function escape($val) 	{ if(!is_object($val) AND !is_array($val)) { return @mysqli_real_escape_string($this->mysqlcon, $val); } else { $val = serialize($val); return @mysqli_real_escape_string($this->mysqlcon, $val);} }		
		/********************** Return Last Insert ID (may not trustable) ****/	
		public function insert_id() 	{ return $this->mysqlcon->insert_id; }				
		/**************** Next Result */
		public function next_result() { try {  return mysqli_next_result($this->mysqlcon); } catch(Exception $e) { return $this->handler(false, $e, "next_result", false); } }
		/**************** Store Result */
		public function store_result() { try {  return mysqli_store_result($this->mysqlcon); } catch(Exception $e) { return $this->handler(false, $e, "store_result", false); }  }
		/**************** More Results? */
		public function more_results() { try {  return mysqli_more_results($this->mysqlcon); } catch(Exception $e) { return $this->handler(false, $e, "more_results", false); } }
		/**************** Store Result Array */
		public function fetch_array($result) { try { return mysqli_fetch_array($result); } catch(Exception $e) { return $this->handler(false, $e, "fetch_array", false); } }
		/**************** Store Result Object */
		public function fetch_object($result) { try { return mysqli_fetch_object($result); } catch(Exception $e) { return $this->handler(false, $e, "fetch_object", false); } }
		/**************** Free Result */
		public function free_result($result) { try { return mysqli_free_result($result); } catch(Exception $e) { return $this->handler(false, $e, "free_result", false); } }			
		/**************** Free Result */
		public function use_result() { try { return mysqli_use_result($this->mysqlcon); } catch(Exception $e) { return $this->handler(false, $e, "use_result", false); } }			
		/**************** Free All */
		public function free_all() { 
			$results = array();	
			try {
				$x = false;
				try { $x = mysqli_use_result($this->mysqlcon); } catch (Exception $e){  }
				if(is_object($x)) { $y = mysqli_fetch_object($x); array_push($results, $y); mysqli_free_result($x); }
				while ($this->more_results()) {
					if ($this->next_result()) {
						$x = $this->store_result($this->mysqlcon);
						if(is_object($x)) { $y = mysqli_fetch_object($x); array_push($results, $y); mysqli_free_result($x); }
					}
				}	
			} catch (Exception $e){ return $this->handler(false, $e, "free_all", false); }
			return $results;}				
			
		/*	  ____  ____   ____   _______/  |________ __ __   _____/  |_ 
			_/ ___\/  _ \ /    \ /  ___/\   __\_  __ \  |  \_/ ___\   __\
			\  \__(  <_> )   |  \\___ \  |  |  |  | \/  |  /\  \___|  |  
			 \___  >____/|___|  /____  > |__|  |__|  |____/  \___  >__|  
				 \/           \/     \/                          \/       Constructor and Status Functions */
		/********************** Construct Connection ****/	 
		private $auth_user	= false;private $auth_pass	= false;private $auth_host	= false;private $auth_db = false;
		function __construct($hostname, $username, $password, $database) {
			$this->auth_user = $username;
			$this->auth_pass = $password;
			$this->auth_host = $hostname;
			$this->auth_db = $database;
			if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
			try { $this->mysqlcon = @mysqli_connect($hostname, $username, $password, $database); 
				   if(@mysqli_connect_errno()) { $this->lasterror  =  @mysqli_connect_error(); } else { $this->lasterror = false; }
			} catch (Exception $e){ $this->lasterror = true; } }
		/**************** Internal Function to get Class Copy */
		private function construct() { return new x_class_mysql($this->auth_host, $this->auth_user, $this->auth_pass, $this->auth_db); }
		/**************** Internal Function to get Class Copy */
		private function construct_copy() { return $this; }
		/********************** Get Connection Status (ping alias) ****/	
		public function status() 		{ return $this->ping(); }
		/********************** Get Ping MySQL Status ****/	
		public function ping() { try { return $this->handler(mysqli_ping($this->mysqlcon), false, "ping", false); } catch (Exception $e){ return $this->handler(false, $e, "ping", false); }  }	
		
		/*			  _           _   
					 | |         | |  
			 ___  ___| | ___  ___| |_ 
			/ __|/ _ \ |/ _ \/ __| __|
			\__ \  __/ |  __/ (__| |_ 
			|___/\___|_|\___|\___|\__|  Select Statement for Multi Purpose */
		/********************** Multiple = True get Multi Array with different Rows | False get Single Array with Row ****/	 
		/********************** If Bindarray = false -> normal input query | If not deliver Multi Array with Array[X]["value"] = VALUE and Array[X]["type"] = (s/i)****/	 
		/********************** TYPE //Binding parameters. Types: s = string, i = integer, d = double,  b = blob] ****/	 				
		public function select($query, $multiple = false, $bindarray = false, $fetch_type = MYSQLI_ASSOC){
			if(is_array($bindarray)) {
				// Binded Input
				$handler = false;
				try { $handler =  $this->handler($this->mysqlcon->prepare($query), false, "select#prepare: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "select#prepare: ".$query, false); }
				if($handler) {
					$before_prepare	=	"";
					$params	=	array(); 
					foreach ($bindarray as $key => $value) {
						$before_prepare .= $value["type"];
						array_push($params, $value["value"]);
					};		
					array_unshift($params, $before_prepare);
					$tmp = array();
					foreach($params as $key => $value) {$tmp[$key] = &$params[$key];}
					try { $this->handler(call_user_func_array(array($handler, 'bind_param'), $tmp), false, "select#bind: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "select#bind: ".$query, false); }
					try { $execution =  $this->handler($handler->execute(), false, "select#execute: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "select#execute: ".$query, false); }
					if($execution){
						try { $exec_res =  $this->handler($handler->get_result(), false, "select#get_result: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "select#get_result: ".$query, false); }
						if($exec_res){
							if(!$multiple) {
								$row = $exec_res->fetch_array($fetch_type);
							} else {
								$row = $exec_res->fetch_all($fetch_type);  
							}
							$exec_res->free_result();
							$handler->free_result();
							return $row;
						} else { $handler->free_result(); return false;}
					} else {return false;} 
				} else {return false;}
			} else {
				// Default Query
				$result = false;
				try { $result = $this->handler(mysqli_query($this->mysqlcon, $query), false, "select#query: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "select#query: ".$query, false); }
				if(is_object($result)) {
					$rows = 0;
					try { $rows = @mysqli_num_rows($result); } catch (Exception $e) { $rows = 0; }
					if ($rows > 0) {
						if(!$multiple) { 
							// Single Return
							$row = array();
							$row = mysqli_fetch_array($result, $fetch_type);
							// Free and Return
							mysqli_free_result($result);
							return $row;
						} else {
							// Multiple Return
							$row = array();
							for ($i=0; $i<$rows; $i++){ $restmp = mysqli_fetch_array($result, $fetch_type); $row[$i] = $restmp; }							
							// Free and Return
							mysqli_free_result($result);
							return $row;
						}
					} else {return false;}
				} else {return false;} 
			}
		}
			
		/*	  __ _ _   _  ___ _ __ _   _ 
			 / _` | | | |/ _ \ '__| | | |
			| (_| | |_| |  __/ |  | |_| |
			 \__, |\__,_|\___|_|   \__, |
				| |                 __/ |
				|_|                |___/  Select Statement for Multi Purpose  */			
		/********************** Query = The Query ****/	 
		/********************** If Bindarray = false -> normal input query | If not deliver Multi Array with Array[X]["value"] = VALUE and Array[X]["type"] ****/	 
		/********************** TYPE //Binding parameters. Types: s = string, i = integer, d = double,  b = blob] ****/	 	
		public function query($query, $bindarray = false){
			if(is_array($bindarray)) {
				// Binded Input
				$handler = false;		
				$execution = false;
				$exec_res = false;
				
				try { $this->handler($handler = $this->mysqlcon->prepare($query), false, "query#prepare: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "query#prepare: ".$query, false); }
				if($handler) {			
					$before_prepare	=	"";
					$params	=	array(); 
					foreach ($bindarray as $key => $value) {
						$before_prepare .= $value["type"];
						array_push($params, $value["value"]);
					};		
					array_unshift($params, $before_prepare);
					$tmp = array();			
					foreach($params as $key => $value) {$tmp[$key] = &$params[$key];}
					try { $this->handler(call_user_func_array(array($handler, 'bind_param'), $tmp), false, "query#bind: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "query#bind: ".$query, false); }			
					try { $execution =  $this->handler($handler->execute(), false, "query#execute: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "query#execute: ".$query, false); }
					if($execution){
						try { $exec_res =  $this->handler($handler->get_result(), false, "query#get_result: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "query#get_result: ".$query, false); }
						if($exec_res){
							$handler->free_result();
							//$exec_res->free_result();
							return $exec_res;
						} else { $handler->free_result(); return $exec_res;}
					} else {return false;} 
				} else {return false;}
			} else {
				// Default Query
				try { return $this->handler(mysqli_query($this->mysqlcon, $query), false, "query#query: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "query#query: ".$query, false); }
			}
		}
		
		/*					 .___       __          
			 __ ________   __| _/____ _/  |_  ____  
			|  |  \____ \ / __ |\__  \\   __\/ __ \ 
			|  |  /  |_> > /_/ | / __ \|  | \  ___/ 
			|____/|   __/\____ |(____  /__|  \___  >
				  |__|        \/     \/          \/  Update Statement for Multi Purpose Returns Affected Rows */			
		/********************** Query = The Query ****/	 
		/********************** If Bindarray = false -> normal input query | If not deliver Multi Array with Array[X]["value"] = VALUE and Array[X]["type"] ****/	 
		/********************** TYPE //Binding parameters. Types: s = string, i = integer, d = double,  b = blob] ****/	 	
		public function update($query, $bindarray = false){
			if(is_array($bindarray)) {
				// Binded Input
				$handler = false;
				$execution = false;
				$exec_res = false;
				
				try { $this->handler($handler = $this->mysqlcon->prepare($query), false, "update#prepare: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "update#prepare: ".$query, false); }
				if($handler) {			
					$before_prepare	=	"";
					$params	=	array(); 
					foreach ($bindarray as $key => $value) {
						$before_prepare .= $value["type"];
						array_push($params, $value["value"]);
					};		
					array_unshift($params, $before_prepare);
					$tmp = array();			
					foreach($params as $key => $value) {$tmp[$key] = &$params[$key];}
					try { $this->handler(call_user_func_array(array($handler, 'bind_param'), $tmp), false, "update#bind: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "update#bind: ".$query, false); }			
					try { $execution =  $this->handler($handler->execute(), false, "update#execute: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "update#execute: ".$query, false); }
					if($execution){
						try { $exec_res =  $this->handler($handler->get_result(), false, "update#get_result: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "update#get_result: ".$query, false); }
						if($exec_res){
							$exec_res->free_result();
							try { $x = $exec_res->affected_rows(); } catch (Exception $e) { $handler->free_result(); return false; }
							$handler->free_result(); return $x;
						} else { $handler->free_result(); return $handler->affected_rows;}
					} else {return false;} 
				} else {return false;}
			} else {
				// Default Query
				try { $result = $this->handler(mysqli_query($this->mysqlcon, $query), false, "update#query: ".$query, false); } catch (Exception $e) { return $this->handler(false, $e, "update#query: ".$query, false); }
				try { return mysqli_affected_rows($this->mysqlcon); } catch (Exception $e) { return false; }
			}
		}

		/*	 _                     _   
			(_)                   | |  
			 _ _ __  ___  ___ _ __| |_ 
			| | '_ \/ __|/ _ \ '__| __|
			| | | | \__ \  __/ |  | |_ 
			|_|_| |_|___/\___|_|   \__| Insert Statement for Multi Purpose / Returns Inserted ID */
		/********************** Table = Table Name to Insert ****/	 
		/********************** Array = Array to be Inserted Array[fieldname] = value ****/	 
		/********************** If Bindarray = false -> normal input query | If not deliver Multi Array with Array[X]["value"] = VALUE and Array[X]["type"] ****/	 
		/********************** TYPE //Binding parameters. Types: s = string, i = integer, d = double,  b = blob] ****/	
		public function insert($table, $array, $bindarray = false){
			try {  
				if(is_array($bindarray)) { 
					if(!is_array($array)) {return false;}
					$build_first	=	"";$build_second	=	"";$firstrun = true;
					foreach( $array as $key => $value ){if(!$firstrun) {$build_first .= ", ";}
					if(!$firstrun) {$build_second .= ", ";}$build_first .= $key;
					if($value == "?") {$build_second .= $value;} else {$build_second .= "'".$value."'";}$firstrun = false;}
					$newquery = 'INSERT INTO `'.$table.'`('.$build_first.') VALUES('.$build_second.');';
					error_log($newquery);
					if ($stmt =  $this->handler(@$this->mysqlcon->prepare($newquery), false, "insert#prepare: ".$table, false)) { 
						$before_prepare	=	"";
						$params	=	array(); 
						foreach ($bindarray as $key => $value) {
							$before_prepare .= $value["type"];
							array_push($params, $value["value"]);};
						array_unshift($params, $before_prepare);
						$tmp = array();
						foreach($params as $key => $value) {$tmp[$key] = &$params[$key];}
						@call_user_func_array(array($stmt, 'bind_param'), $tmp);
						$x =  $this->handler(@$stmt->execute(), false, "insert#execute: ".$table, false,); } else {return false;}
						if($x) { try { return $this->mysqlcon->insert_id; } catch (Exception $e) { return false; } } else {return false;}
				} else {	
					if(!is_array($array)) {return false;}
					$build_first	=	"";$build_second	=	"";$firstrun = true;
					foreach( $array as $key => $value ){if(!$firstrun) {$build_first .= ", ";}
					if(!$firstrun) {$build_second .= ", ";}$build_first .= $key;
					$build_second .= "'".$this->escape($value)."'";
					$firstrun = false;}
					$nnnnquery	=	'INSERT INTO '.$table.'('.$build_first.') VALUES('.$build_second.');';
					try { $result = $this->handler(mysqli_query($this->mysqlcon, $nnnnquery), false, "insert#query: ".$nnnnquery, false); } catch (Exception $e) { return $this->handler(false, $e, "insert#query: ".$nnnnquery, false); }
					try { return $this->mysqlcon->insert_id; } catch (Exception $e) { return false; }				
				}
			} catch (Exception $e){ return $this->handler(false, $e, "insert#all: ".$table, false); }}
	}
