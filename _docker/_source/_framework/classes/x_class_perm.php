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
	
	class x_class_perm {
		// Class Variables
		private $mysql     				= false;
		private $tablename 				= false;
		private $section   				= ""; public function section($section) { $this->section = $section; }
		
		// Table Initialization
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->tablename."` (
										  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID to Identify',
										  `ref` int(10) NOT NULL COMMENT 'Related Reference',
										  `content` text NOT NULL COMMENT 'Permission Array',
										  `section` varchar(128) DEFAULT NULL COMMENT 'Related Section',
										  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation',
										  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification | Auto - Set',
										  PRIMARY KEY (`id`),
										  UNIQUE KEY `".$this->tablename."_unique` (`ref`,`section`) );");}
		
		// Constructor
		function __construct($mysql, $tablename, $section = "") {
			$this->mysql	= $mysql;
			$this->tablename = $tablename;
			$this->section = substr(trim($section ?? ''), 0, 127);
			if(!$this->mysql->table_exists($tablename)) { $this->create_table(); $this->mysql->free_all(); }}

		// Get Permissions to Local Array
		public function get_perm($ref) { return $this->getPerm($ref); }
		public function getPerm($ref) {
			if(is_numeric($ref)) { 
				$b[0]["type"]	=	"s"; $b[0]["value"]	=	$ref; 
				$b[1]["type"]	=	"s"; $b[1]["value"]	=	$this->section; 
				$ar = $this->mysql->select("SELECT * FROM `".$this->tablename."` WHERE ref = ? AND section = ?", false, $b);
				if(is_array($ar)) {
					$newar	= unserialize($ar["content"]);
					if(is_array($newar)) { return $newar; } else {return array();}
				} return array();
			} return array(); }
		
		// Check if Ref has Perm		
		public function has_perm($ref, $permname) {return $this->hasPerm($ref, $permname); } 
		public function hasPerm($ref, $permname) {
			$current_perm	=	$this->getPerm($ref);
			if(is_array($current_perm)) {
				foreach($current_perm AS $key => $value) {
					if($value == $permname) { return true; }
				}
			} return false;	}		
			
		// Add Permission to Ref	
		public function add_perm($ref, $permname) { return $this->addPerm($ref, $permname); }
		public function addPerm($ref, $permname) {
			$current_perm	=	$this->getPerm($ref);
			$hasperm = false;
			if(is_array($current_perm)) {
				foreach($current_perm AS $key => $value) {
					if($value == $permname) { $hasperm = true; }
				}
			} else { $current_perm = array(); }
			if(!$hasperm) {array_push($current_perm, $permname);}		
			$this->setPerm($ref, $current_perm);		
			return true;}			
			
		// Check if Ref has multiple Perms at Once and True/False		
		public function check_perm($ref, $array, $or = false) { return $this->checkPerm($ref, $array, $or); }
		public function checkPerm($ref, $array, $or = false) {
			if(!is_numeric($ref)) { return false; }
			$current_perm	=	$this->getPerm($ref);
			$perms_and = false;
			$perms_andra = array();
			$perms_or = false;
			if(is_array($current_perm) AND is_array($array)) {
				foreach($current_perm AS $key => $value) {
					foreach($array AS $keyc => $valuec) {
						if($value == $valuec) { $perms_or = true; }
					}
				}
				foreach($array AS $key => $value) {
					foreach($current_perm AS $keyc => $valuec) {
						if($value == $valuec) { $perms_andra[$key] = true; }
					}
					if(!isset($perms_andra[$key])) { $perms_andra[$key] = false; }
				}				
				$perms_and = true;
				foreach($perms_andra AS $keyc => $valuec) {
					if($valuec == false) { $perms_and = false; }
				}
				if(!$or) { return $perms_and;}
				else { return $perms_or;}
			} return false;}	

		// Set Ref Permissions		
		private function set_perm($ref, $array) { return $this->setPerm($ref, $array); }
		private function setPerm($ref, $array) {	
			if(is_numeric($ref)) { 
				$b[0]["type"]	=	"s"; $b[0]["value"]	=	$ref; 
				$b[1]["type"]	=	"s"; $b[1]["value"]	=	$this->section; 
				$query = $this->mysql->select("SELECT * FROM `".$this->tablename."` WHERE ref = ? AND section = ?", false, $b);
				if ($query ) { 
					$b[0]["type"]	=	"s"; $b[0]["value"]	=	serialize($array); 
					$b[1]["type"]	=	"s"; $b[1]["value"]	=	$ref; 
					$b[2]["type"]	=	"s"; $b[2]["value"]	=	$this->section; 
					$this->mysql->update("UPDATE `".$this->tablename."` SET content = ? WHERE ref = ? AND section = ?  ", $b);
				} else { 
					$b[0]["type"]	=	"s"; $b[0]["value"]	=	$ref; 
					$b[1]["type"]	=	"s"; $b[1]["value"]	=	serialize($array); 
					$b[2]["type"]	=	"s"; $b[2]["value"]	=	$this->section; 
					$this->mysql->query("INSERT INTO `".$this->tablename."` (ref, content, section) VALUES(?, ?, ?)", $b); 
				}
				return true;
			} return false; 		
		}
		
		// Remove Single Permissions
		public function remove_perm($ref, $permname) { return $this->removePerm($ref, $permname); }
		public function removePerm($ref, $permname) {
			if(!is_numeric($ref)) {return false;}
			$current_perm = $this->getPerm($ref);
			$newperm	=	array();
			if(is_array($current_perm)) {
				foreach($current_perm AS $key => $value) {
					if($value != $permname) { array_push($newperm, $value); }
				}
			}
			return $this->setPerm($ref, $newperm);}	

		// Remove Ref Permissions	
		public function removePerms($ref) { if(is_numeric($ref)) { return $this->setPerm($ref, array()); } return false; }
		public function remove_perms($ref) { if(is_numeric($ref)) { return $this->setPerm($ref, array()); } return false; }
		public function clear_perms($ref) { if(is_numeric($ref)) { return $this->setPerm($ref, array()); } return false; }
		// Delete a Ref from Permission Table	
		public function delete_ref($ref) { if(is_numeric($ref)) { 
			$b[0]["type"]	=	"s"; $b[0]["value"]	=	$ref; 
			$b[1]["type"]	=	"s"; $b[1]["value"]	=	$this->section; 
			return $this->mysql->query("DELETE FROM `".$this->tablename."` WHERE ref = ? AND section = ?", $b); } return false; }
		// Get a Ref Object
		public function item($ref) { $item = new x_class_perm_item($this->mysql, $this->tablename, $this->section, $ref, $this->getPerm($ref)); return $item; }
	}