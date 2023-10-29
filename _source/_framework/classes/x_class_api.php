<?php 
	/* 	
		@@@@@@@   @@@  @@@   @@@@@@@@  @@@@@@@@  @@@   @@@@@@   @@@  @@@  
		@@@@@@@@  @@@  @@@  @@@@@@@@@  @@@@@@@@  @@@  @@@@@@@   @@@  @@@  
		@@!  @@@  @@!  @@@  !@@        @@!       @@!  !@@       @@!  @@@  
		!@   @!@  !@!  @!@  !@!        !@!       !@!  !@!       !@!  @!@  
		@!@!@!@   @!@  !@!  !@! @!@!@  @!!!:!    !!@  !!@@!!    @!@!@!@!  
		!!!@!!!!  !@!  !!!  !!! !!@!!  !!!!!:    !!!   !!@!!!   !!!@!!!!  
		!!:  !!!  !!:  !!!  :!!   !!:  !!:       !!:       !:!  !!:  !!!  
		:!:  !:!  :!:  !:!  :!:   !::  :!:       :!:      !:!   :!:  !:!  
		 :: ::::  ::::: ::   ::: ::::   ::        ::  :::: ::   ::   :::  
		:: : ::    : :  :    :: :: :    :        :    :: : :     :   : :  
		   ____         _     __                      __  __         __           __  __
		  /  _/ _    __(_)__ / /    __ _____  __ __  / /_/ /  ___   / /  ___ ___ / /_/ /
		 _/ /  | |/|/ / (_-</ _ \  / // / _ \/ // / / __/ _ \/ -_) / _ \/ -_|_-</ __/_/ 
		/___/  |__,__/_/___/_//_/  \_, /\___/\_,_/  \__/_//_/\__/ /_.__/\__/___/\__(_)  
								  /___/                           
		Bugfish Framework Codebase // MIT License
		// Autor: Jan-Maurice Dahlmanns (Bugfish)
		// Website: www.bugfish.eu 
	*/
	class x_class_api {		
		// Class Variables
		private $mysql   = false; 
		private $table   = false; 	
		private $section = false;
	
		// Table Initialization
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table."` (
												  `id` int(9) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
												  `direction` varchar(12) NOT NULL AUTO_INCREMENT COMMENT 'Token Type',
												  `api_token` varchar(512) NOT NULL COMMENT 'Token for API Requests',
												  `section` varchar(16) NOT NULL COMMENT 'Value for Constant',
												  `last_use` datetime NULL COMMENT 'Last Use Date in Check',
												  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date of Entry | Will be Auto-Set',
												  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date of Entry with Auto-Update on Change',
												  PRIMARY KEY (`id`),
												  UNIQUE KEY `Unique` (`outgoing`,`token`,`section`) USING BTREE);");}
	
		// Constructor Functions
		function __construct($mysql, $table, $section = false) {
			if(!$section) { $section = "undefined"; }
			$this->mysql = $mysql;
			$this->table = $table;
			$this->section = $section;
			if(!$this->mysql->table_exists($table)) { $this->create_table(); $this->mysql->free_all();  }}
		
		// Request Function
		function request($url, $payload, $token = false, $section = false) {
			if(!$section) { $section = $this->section; }
			if(!$token) { 
				$res = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE direction = 'out' AND section = '".$this->mysql->escape($section)."'", false, $bind);
				if(is_array($res)) {
					$token = $res["api_token"];
				} else { return "local-error:notokenprovided-noautotokenfound"; }		
			}
			
			// Set Field Data for Post Request
			if(is_string($payload) OR is_numeric($payload)) {
			  $fields = array(
				'token'=>$token,
				'section'=>$section,
				'data'=>@$payload);	
			} elseif(is_array($payload) OR is_object($payload)) {
				$fields = array();
				$fields["data"] = serialize($payload);
				$fields["token"] = $token;
				$fields["section"] = $section;
			} else { return "local-error:payload-data-error"; }
	
		  //url-ify the data for the POST
		  $fields_string = "";
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
		  return $result; }
	
		// Token Functions
		public function token_add_incoming($token, $section = false) {
			if(!$section) { $section = $this->section; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	trim($token);				
			return @$this->mysql->query("INSERT INTO `".$this->table."`(api_token, section, direction) VALUES(?, '".$this->mysql->escape($section)."', 'in');", $b);}
			
		public function token_add_outgoing($token, $section = false) {
			if(!$section) { $section = $this->section; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	trim($token);				
			return @$this->mysql->query("INSERT INTO `".$this->table."`(api_token, section, direction) VALUES(?, '".$this->mysql->escape($section)."', 'out');", $b);}
	
		public function token_generate_incoming($section = false, $len = 32, $comb = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890') {
			$pass = array(); $combLen = strlen($comb) - 1; for ($i = 0; $i < $len; $i++) { $n = mt_rand(0, $combLen); $pass[] = $comb[$n]; } $newtoken = implode($pass);
			if(!$section) { $section = $this->section; }
			$this->token_add_incoming($newtoken, $section);return $newtoken;}		

		public function token_delete_incoming($token, $section = false) {
			if(!$section) { $section = $this->section; }
			$bind[0]["type"]	=	"s";
			$bind[0]["value"]	=	trim($token);
			return $this->mysql->query("DELETE FROM `".$this->table."` WHERE direction = 'in' AND api_token = ? AND section = '".$this->mysql->escape($section)."'", $bind);}	
			
		public function token_delete_outgoing($token, $section = false) {
			if(!$section) { $section = $this->section; }
			$bind[0]["type"]	=	"s";
			$bind[0]["value"]	=	trim($token);
			return $this->mysql->query("DELETE FROM `".$this->table."` WHERE direction = 'out' AND api_token = ? AND section = '".$this->mysql->escape($section)."'", $bind);}	

		public function token_check_incoming($token, $section = false) {
			// Only checking incoming tokens, External cant be checked
			if(!$section) { $section = $this->section; }
			$bind[0]["type"]	=	"s";
			$bind[0]["value"]	=	trim($token);
			$res = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE direction = 'in' AND api_token = ? AND section = '".$this->mysql->escape($section)."'", false, $bind);
			if(is_array($res)) {
				@$this->mysql->query("UPDATE `".$this->table."` SET last_use = CURRENT_TIMESTAMP() WHERE id = '".$res["id"]."'");
				return true;
			} return false;}
	}