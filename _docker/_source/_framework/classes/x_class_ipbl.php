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
	
	class x_class_ipbl {
		######################################################
		// Class Variables
		######################################################
		private $mysql  	= false; 
		private $table     	= false; 
		private $ip     	= false; 
		private $max     	= false; 
		private $blocked    = false; 
		private $counter    = false; 
		
		######################################################
		// Table Initialization
		######################################################
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table."` (
												  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique ID to Identify',
												  `fail` int(10) DEFAULT '1' COMMENT 'Address Failures Counter',
												  `ip_adr` varchar(256) NOT NULL COMMENT 'Related IP Address for Failure Counter',
												  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date | Will be Auto Set',
												  PRIMARY KEY (`id`),
												  UNIQUE KEY `".$this->table."_unique` (`ip_adr`) );");}
		
		######################################################
		// Construct
		######################################################
		function __construct($mysql, $tablename, $maxvalue = 50000) { 
			if(!is_numeric($maxvalue)) { $maxvalue = false; }
			$this->mysql = $mysql; $this->table = $tablename; $this->max  = $maxvalue;
			if(!is_numeric($this->max)) { $this->max = 50000; } 
			$this->ip = trim(strtolower(@$_SERVER["REMOTE_ADDR"] ?? '') ?? ''); 
			if(!$this->mysql->table_exists($tablename)) { $this->create_table(); $this->mysql->free_all();  }
			$this->int_block_renew();}

		######################################################
		// Check Current Block Status
		######################################################	
		public function blocked($renew = false) { if(!$renew) { return $this->blocked; } else { return $this->int_block_renew(); } }
		public function banned($renew = false) { if(!$renew) { return $this->blocked; } else { return $this->int_block_renew(); } }
		public function isbanned($renew = false) { if(!$renew) { return $this->blocked; } else { return $this->int_block_renew(); } }
		public function isblocked($renew = false) { if(!$renew) { return $this->blocked; } else { return $this->int_block_renew(); } }
		// Function to Renew Local Blocked Variable for Constructor and Renew
		private function int_block_renew() {
			if($this->max == false OR $this->max == 0) { $this->blocked = false; return $this->blocked; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->ip;
			$r = @$this->mysql->select("SELECT * FROM `".$this->table."` WHERE ip_adr = ? AND fail > ".$this->max.";", false, $b);
			if(is_array($r)) {	
				$this->blocked = true;
				return $this->blocked; 
			}
			$this->blocked = false;
			return $this->blocked;}
			
		######################################################
		// Get Current Ban Table as Array
		######################################################				
		public function get_array() {
			return $this->mysql->select("SELECT * FROM `".$this->table."`", true);
		}
		
		
		######################################################
		// Unblcok an UP Adr
		######################################################			
		function unblock($ip) {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$ip;
			$r = @$this->mysql->query("DELETE FROM `".$this->table."` WHERE ip_adr = ?;", $b);			
		}
		
		######################################################
		// Get Counter for IP
		######################################################	
		public function get_counter($renew = false) { if(!$renew) { return $this->counter; } else { return $this->int_counter_renew(); } }
		public function counter($renew = false) { if(!$renew) { return $this->counter; } else { return $this->int_counter_renew(); } }
		// Function to Renew Local Counter Variable for Constructor and Renew
		private function int_counter_renew() {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->ip;
			$r = @$this->mysql->select("SELECT * FROM `".$this->table."` WHERE ip_adr = ? AND fail > ".$this->max.";", false, $b); 
			if(is_array($r)) {	
				$this->counter = $r["fail"];
				return $this->counter; 
			}
			$this->counter = 0; $this->int_block_renew();
			return $this->counter;}

		######################################################
		// Get Counter for IP
		######################################################	
		public function ip_counter($ip) {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	trim(strtolower($this->ip ?? '') ?? ''); ;
			if(!$ip) { $r = @$this->mysql->select("SELECT * FROM `".$this->table."` WHERE ip_adr = ? AND fail > ".$this->max.";", false, $b); }
			else { $r = @$this->mysql->select("SELECT * FROM `".$this->table."` WHERE ip_adr = ? AND fail > ".$this->max.";", false, $b); }
			if(is_array($r)) {	
				return $r["fail"]; 
			}
			return 0;}		
		
		######################################################
		// Raise Counter for Current IP
		######################################################		
		public function raise($value = 1) { return $this->int_counter_raise($value); }
		public function increase($value = 1) { return $this->int_counter_raise($value); } 
		// Function to Increase and Refresh Counter
		private function int_counter_raise($value = 1) {
			if(!is_int($value)) { $value = 1; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->ip;
			$rres = @$this->mysql->select("SELECT * FROM `".$this->table."` WHERE ip_adr = ?;", false, $b); 
			if(is_array($rres)) {	
				@$this->mysql->update("UPDATE `".$this->table."` SET fail = fail + ".$value." WHERE id = '".$rres["id"]."';");
			} else { @$this->mysql->query("INSERT INTO `".$this->table."`(ip_adr, fail) VALUES(?, 1);", $b); }
			return $this->int_counter_renew();			
		}	
	}
