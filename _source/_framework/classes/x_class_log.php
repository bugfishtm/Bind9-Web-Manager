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
												  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID to Identify',
												  `type` int(10) DEFAULT '0' COMMENT '0 - Unspecified | 1 - Error | 2 - Warning | 3 - Notification',
												  `message` text COMMENT 'Message Text',
												  `ref` text COMMENT 'Message Reference',
												  `section` VARCHAR(128) NULL COMMENT 'Multi Section',
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
		public function post($message, $type = 3, $ref = false) { return $this->message($message, $type, $ref); } 
		public function send($message, $type = 3, $ref = false) { return $this->message($message, $type, $ref); }
		public function write($message, $type = 3, $ref = false) { return $this->message($message, $type, $ref); }		
		public function message($message, $type = 3, $ref = false) {
			if(is_numeric($type)) { 
				$b[0]["type"]	=	"s";
				$b[0]["value"]	=	$message;
				$b[1]["type"]	=	"s";
				$b[1]["value"]	=	$this->section;
				if(!$ref) { 
					return $this->mysql->query("INSERT INTO `".$this->table."` (type, message, section) VALUES (\"".$type."\", ?, ?)", $b); 
				} else { 
					$b[2]["type"]	=	"s";
					$b[2]["value"]	=	$ref;
					return $this->mysql->query("INSERT INTO `".$this->table."` (type, message, section, ref) VALUES (\"".$type."\", ?, ?, ?)", $b); 
				}
			} else { return false; }			
		}	
		
		######################################################
		// Send Notification
		######################################################		
		public function info($message, $ref = false) { return $this->notify($message, $ref); }
		public function notify($message, $ref = false) { $this->message($message, 3, $ref); }		
		
		######################################################
		// Send Warning
		######################################################		
		public function warn($message, $ref = false) { return $this->warning($message, $ref); }
		public function warning($message, $ref = false) { $this->message($message, 2, $ref); }		
		
		######################################################
		// Send Error
		######################################################
		public function err($message, $ref = false) { return $this->error($message, $ref); }
		public function failure($message, $ref = false) { return $this->error($message, $ref); }
		public function fail($message, $ref = false) { return $this->error($message, $ref); }
		public function error($message, $ref = false) { $this->message($message, 1, $ref); }		

		######################################################
		// Get Log Table Entries as Array
		######################################################	
		public function list_get($limit = 50) { 
			if(is_numeric($limit)) {} else { $limit = 50; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->section;
			return @$this->mysql->select("SELECT * FROM `".$this->table."` WHERE section = ? ORDER BY id DESC LIMIT ".$limit.";", true); 
		}	

		######################################################
		// Delete Entries in Logtable List and reset Auto Increment
		######################################################	
		public function list_flush_section() { 
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->section;
			@$this->mysql->query("DELETE FROM `".$this->table."` WHERE section = ?;", $b); 
			@$this->mysql->auto_increment($this->table, 1); 
			return true;
		}	
		
		public function list_flush() { 
			@$this->mysql->query("DELETE FROM `".$this->table."`"); 
			@$this->mysql->auto_increment($this->table, 1); 
			return true;
		}
	}