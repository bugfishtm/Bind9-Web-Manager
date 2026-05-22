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
	
	class x_class_perm_item {
		// Class Variables
		private $mysql     				= false;
		private $tablename 				= false;
		private $section   				= false;
		private $ref 	   			    = false;
		private $permissions 	    	= array(); 
		// Constructor
		function __construct($mysql, $tablename, $section, $ref, $permissions = array()) {
			$this->mysql	= $mysql;
			$this->tablename = $tablename;
			$this->section = @substr(trim($section ?? ''), 0, 127);
			$this->ref = $ref;
			$this->permissions = $permissions; }	

		// Get Permissions to Local Array
		public function refresh() {
				$b[0]["type"]	=	"s"; $b[0]["value"]	=	$this->ref; 
				$b[1]["type"]	=	"s"; $b[1]["value"]	=	$this->section;
				$ar = $this->mysql->select("SELECT * FROM `".$this->tablename."` WHERE ref = ? AND section = ?", false, $b);
				if(is_array($ar)) {
					$newar	= unserialize($ar["content"]);
					if(is_array($newar)) { $this->permissions = $newar; } else {$this->permissions =  array();}
				} $this->permissions =  array(); } 

		// Check if Ref has Perm		
		public function has_perm($permname) {
			$current_perm	=	$this->permissions;
			if(is_array($current_perm)) {
				foreach($current_perm AS $key => $value) {
					if($value == $permname) { return true; }
				}
			} return false;	}	

		// Add Permission to Ref	
		public function add_perm($permname) {
			$current_perm	=	$this->permissions;
			$hasperm = false;
			if(is_array($current_perm)) {
				foreach($current_perm AS $key => $value) {
					if($value == $permname) { $hasperm = true; }
				}
			} else { $current_perm = array(); }
			if(!$hasperm) {array_push($current_perm, $permname);}		
			$this->set_perm($this->ref, $current_perm);		
			return true;}	

		// Check if Ref has multiple Perms at Once and True/False		
		public function check_perm($array, $or = false) {
			$current_perm	=	$this->permissions;
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

		// Remove Single Permissions
		public function remove_perm($permname) {
			$current_perm = $this->permissions;
			$newperm	=	array();
			if(is_array($current_perm)) {
				foreach($current_perm AS $key => $value) {
					if($value != $permname) { array_push($newperm, $value); }
				}
			} return $this->set_perm($this->ref, $newperm);}	

		// Set Ref Permissions		
		private function set_perm($ref, $array) { 	
			$b[0]["type"]	=	"s"; $b[0]["value"]	=	$this->ref; 
			$b[1]["type"]	=	"s"; $b[1]["value"]	=	$this->section;
			$query = $this->mysql->select("SELECT * FROM `".$this->tablename."` WHERE ref = ? AND section = ?", false, $b);
			if ($query) { 
				$b[0]["type"]	=	"s"; $b[0]["value"]	=	serialize($array); 
				$b[1]["type"]	=	"s"; $b[1]["value"]	=	$this->ref; 
				$b[2]["type"]	=	"s"; $b[2]["value"]	=	$this->section;
				$this->mysql->update("UPDATE `".$this->tablename."` SET content = ? WHERE ref = ? AND section = ?  ", $b);
			} else { 
				$b[0]["type"]	=	"s"; $b[0]["value"]	=	$this->ref; 
				$b[1]["type"]	=	"s"; $b[1]["value"]	=	serialize($array); 
				$b[2]["type"]	=	"s"; $b[2]["value"]	=	$this->section;
				$this->mysql->query("INSERT INTO `".$this->tablename."` (ref, content, section) VALUES(?,  ?, ?)", $b); 
			} return true;}
		
		// Remove Ref Permissions	
		public function remove_perms() { return $this->set_perm(array()); }
		// Delete a Ref from Permission Table	
		public function delete_ref() { 
			$b[0]["type"]	=	"s"; $b[0]["value"]	=	$this->ref; 
			$b[1]["type"]	=	"s"; $b[1]["value"]	=	$this->section; 
			return $this->mysql->query("DELETE FROM `".$this->tablename."` WHERE ref = ? AND section = ?", $b);}
	}
