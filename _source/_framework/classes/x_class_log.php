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
	class x_class_log {
		######################################################
		// Class Variables
		######################################################
		private $mysql   		= false; 
		private $table     		= false; 
		private $section     	= false; 

		######################################################
		// Table Initialization
		######################################################
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table."` (
												  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Identificator',
												  `type` int(10) DEFAULT '0' COMMENT '0 - Unspecified | 1 - Error | 2 - Warning | 3 - Notification',
												  `message` text COMMENT 'Logged Text',
												  `section` VARCHAR(128) NULL COMMENT 'Logged Category',
												  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date | Will be Auto-Set',
												  PRIMARY KEY (`id`) );");
		}
		
		######################################################
		// Constructor
		######################################################
		function __construct($mysql, $tablename, $section = "") { 
			$this->mysql  	= $mysql; 
			$this->table    = $tablename; 
			$this->section 	= $section; 
			if(!$this->mysql->table_exists($tablename)) { $this->create_table(); $this->mysql->free_all();  }
		}

		######################################################
		// Get Current Ban Table as Array
		######################################################				
		public function get_array() {
			return $this->mysql->select("SELECT * FROM `".$this->table."`", true);
		}
		
		######################################################
		// Send a Messge / Notification
		######################################################
		public function post($message, $type = 3) { return $this->message($message, $type); } 
		public function send($message, $type = 3) { return $this->message($message, $type); }
		public function write($message, $type = 3) { return $this->message($message, $type); }		
		public function message($message, $type = 3) {
			if(is_numeric($type)) { 
				$b[0]["type"]	=	"s";
				$b[0]["value"]	=	$message;
				return $this->mysql->query("INSERT INTO `".$this->table."` (type, message, section) VALUES (\"".$type."\", ?, '".$this->section."')", $b);}
			else { return false; }			
		}	
		
		######################################################
		// Send Notification
		######################################################		
		public function info($message) { return $this->notify($message); }
		public function notify($message) {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$message;
			return $this->mysql->query("INSERT INTO `".$this->table."` (type, message, section) VALUES (3, ?, '".$this->section."')", $b);
		}		
		
		######################################################
		// Send Warning
		######################################################		
		public function warn($message) { return $this->warning($message); }
		public function warning($message) {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$message;
			return $this->mysql->query("INSERT INTO `".$this->table."` (type, message, section) VALUES (2, ?, '".$this->section."')", $b);
		}		
		
		######################################################
		// Send Error
		######################################################
		public function err($message) { return $this->error($message); }
		public function failure($message) { return $this->error($message); }
		public function fail($message) { return $this->error($message); }
		public function error($message) {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$message;
			return $this->mysql->query("INSERT INTO `".$this->table."` (type, message, section) VALUES (1, ?,'".$this->section."')", $b);
		}		

		######################################################
		// Get Log Table Entries as Array
		######################################################	
		public function list_get($limit = 50) { 
			return @$this->mysql->select("SELECT * FROM `".$this->table."` WHERE section = '".$this->section."' ORDER BY id DESC LIMIT ".$this->mysql->escape($limit).";", true); 
		}	

		######################################################
		// Delete Entries in Logtable List and reset Auto Increment
		######################################################	
		public function list_flush_section() { 
			@$this->mysql->query("DELETE FROM `".$this->table."` WHERE section = '".$this->section."';"); 
			@$this->mysql->auto_increment($this->table, 1); 
			return true;
		}	
		
		public function list_flush() { 
			@$this->mysql->query("DELETE FROM `".$this->table."`"); 
			@$this->mysql->auto_increment($this->table, 1); 
			return true;
		}
	}
