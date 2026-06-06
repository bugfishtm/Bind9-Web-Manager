<?php                                                  
	#	@@@@@@@  @@@  @@@  @@@@@@@  @@@@@@@@ @@@  @@@@@@ @@@  @@@ 
	#	@@!  @@@ @@!  @@@ !@@       @@!      @@! !@@     @@!  @@@ 
	#	@!@!@!@  @!@  !@! !@! @!@!@ @!!!:!   !!@  !@@!!  @!@!@!@! 
	#	!!:  !!! !!:  !!! :!!   !!: !!:      !!:     !:! !!:  !!! 
	#	:: : ::   :.:: :   :: :: :   :       :   ::.: :   :   : : 						
	#		 ______  ______   ______   _________   ______  _   _   _   ______   ______   _    __ 
	#		| |     | |  | \ | |  | | | | | | | \ | |     | | | | | | / |  | \ | |  | \ | |  / / 
	#		| |---- | |__| | | |__| | | | | | | | | |---- | | | | | | | |  | | | |__| | | |-< <  
	#		|_|     |_|  \_\ |_|  |_| |_| |_| |_| |_|____ |_|_|_|_|_/ \_|__|_/ |_|  \_\ |_|  \_\ 
																							 
	#	Copyright (C) 2025 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software; you can redistribute it and/or
	#	modify it under the terms of the GNU Lesser General Public License
	#	as published by the Free Software Foundation; either version 2.1
	#	of the License, or (at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU Lesser General Public License for more details.

	#	You should have received a copy of the GNU Lesser General Public License
	#	along with this program; if not, see <https://www.gnu.org/licenses/>.
	
	class x_class_hitcounter {
		######################################################
		// Class Variables
		######################################################
		private $mysql			=  false;
		private $mysqltable		=  false;
		private $precookie 		=  "";
		private $section 		=  "";
		public $urlpath 		=  false;
		public $urlmd5 		=  false;		
		
		private $enabled 		=  true; 	public function enabled($bool = true) {$this->enabled = $bool;}	
		private $clearget 		=  true; 	public function clearget($bool = true) {
			$this->clearget = $bool;
			if($bool) { 
				$this->urlpath = $this->prepareUrl(@$_SERVER['HTTP_HOST'].@$_SERVER['REQUEST_URI']); 
				$this->urlmd5 = md5(@$this->urlpath);						
			} else {

				$this->urlpath = $this->prepareUrl(@$_SERVER['HTTP_HOST'].@$_SERVER['REQUEST_URI']); 
				$this->urlmd5 = md5(@$this->urlpath);						
			}				
			$this->refresh_counters();
		}	

		######################################################
		// Public Class Variables
		######################################################		
		public $switches	=	0;
		public $arrivals	=	0;
		public $summarized	=	0;

		######################################################
		// Get current saved Referers in Array
		######################################################		
		public function get_array() {
			return $this->mysql->select("SELECT * FROM `".$this->mysqltable."`", true);
		}
		
		######################################################
		// Table Initialization
		######################################################
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->mysqltable."` (
												  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
												  `full_url` varchar(512) NOT NULL DEFAULT '0' COMMENT 'Related Domain',
												  `switches` int(10) DEFAULT '0' COMMENT 'Changes to this Site',
												  `arrivals` int(10) NOT NULL DEFAULT '0' COMMENT 'Arrivals at this Site',
												  `section` varchar(128) NOT NULL DEFAULT '' COMMENT 'Related Section',
												  `summarized` int(10) NOT NULL DEFAULT '0' COMMENT 'All Hits for this URL',
												  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation',
												  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification',
												  PRIMARY KEY (`id`),
												  UNIQUE KEY `".$this->mysqltable."_unique` (`full_url`, `section`) USING BTREE ) ;	");	}
		
		######################################################
		// Constructor
		######################################################
		function __construct($thecon, $table, $precookie = "", $section = "") {
			if ( session_status() !== PHP_SESSION_ACTIVE ) { @session_start(); }
			$this->mysql = $thecon; $this->mysqltable = $table;  $this->precookie = $precookie; $this->section = $section; 
			$this->urlpath = $this->prepareUrl(@$_SERVER['HTTP_HOST'].@$_SERVER['REQUEST_URI']); 
			$this->urlmd5 = md5(@$this->urlpath);			
			if(!$this->mysql->table_exists($table)) { $this->create_table(); $this->mysql->free_all();  } 
			$this->refresh_counters();}

		######################################################
		// Refresh the Counters Function
		######################################################		
		public function refresh_counters() {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->urlpath;
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$this->section;
			$res = $this->mysql->select("SELECT * FROM `".$this->mysqltable."` WHERE full_url = ? AND section = ?;",false, $b);
			if(is_array($res)) {
				$this->switches		=	$res["switches"];
				$this->arrivals		=	$res["arrivals"];
				$this->summarized	=	$this->arrivals + $this->switches;				
				return true;
			}	
			$this->switches		=	0;
			$this->arrivals		=	0;
			$this->summarized	=	0;
			return true;}

		######################################################
		// Prepare URL for Database
		######################################################
		public function prepareUrl($tmpcode) { 
			if(strpos($tmpcode, "?") > -1  AND $this->clearget) {$tmpcode = substr($tmpcode, 0, strpos($tmpcode, "?"));}
			if(strpos($tmpcode, "&") > -1 AND $this->clearget){$tmpcode = substr($tmpcode, 0, strpos($tmpcode, "&"));} 
			if(strpos($tmpcode, "https://") > -1){$tmpcode = substr($tmpcode, strpos($tmpcode, "https://"));} 
			if(strpos($tmpcode, "http://") > -1){$tmpcode = substr($tmpcode, strpos($tmpcode, "http://"));} 
			if(strpos($tmpcode, "www.") > -1){$tmpcode = substr($tmpcode, strpos($tmpcode, "www."));} 
			return urldecode(trim(@$tmpcode ?? ''));}	
		
		######################################################
		// Execute Function
		######################################################
		function execute() { 
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->urlpath;
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$this->section;
			if($this->enabled) { 
				// Count Arrivals
				$isarrival = false;	
				if(@$_SESSION[$this->precookie."x_class_hitcounter"] != "ok") { 
					$isarrival = true;
					$ar = $this->mysql->select("SELECT * FROM `".$this->mysqltable."` WHERE full_url = ? AND section = ?;",false, $b);
					if(is_array($ar)) {
						$this->mysql->update("UPDATE `".$this->mysqltable."` SET arrivals = arrivals + 1, summarized = switches + arrivals WHERE full_url = ? AND section = ?;", $b);
						$_SESSION[$this->precookie."x_class_hitcounter"] = "ok";	
					} else {
						$this->mysql->query("INSERT INTO `".$this->mysqltable."` (full_url, switches, arrivals, section) VALUES (?, \"0\", \"1\", ?)", $b);
					}
					
				}		
				// Count Switches	
				$ishittedarray = false;
				$current_switches_ar	=	@$_SESSION[$this->precookie."x_class_hitcounter_s"];
				$current_switches_ar = @unserialize($current_switches_ar);
				if(!is_array($current_switches_ar)) { $current_switches_ar = array(); }
				foreach($current_switches_ar as $key => $value) { if($value == $this->urlmd5) { $ishittedarray = true; } }
				if(!$ishittedarray AND !$isarrival) {
					$ar = $this->mysql->select("SELECT * FROM `".$this->mysqltable."` WHERE full_url = ?  AND section = ?;",false, $b);
					if(is_array($ar)) {
						$this->mysql->update("UPDATE ".$this->mysqltable." SET switches = switches + 1, summarized = switches + arrivals WHERE full_url = ?  AND section = ?;", $b);
					} else {
						$this->mysql->query("INSERT INTO `".$this->mysqltable."` (full_url, switches, arrivals, section) VALUES (?, \"1\", \"0\", ?)", $b);
					}
					array_push($current_switches_ar, $this->urlmd5);
				}
				$current_switches_ar = @serialize($current_switches_ar);
				$_SESSION[$this->precookie."x_class_hitcounter_s"] = $current_switches_ar;
				return true;
			}
		}
	}
