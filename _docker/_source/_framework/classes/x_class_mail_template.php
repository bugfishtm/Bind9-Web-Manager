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
	
	// Class for Handling Mail Templates and Substitutions to them, eventually directly send with x_class_mail
	class x_class_mail_template {
		// Class Variables
		private $mysql = false; // MySQL for Templates
		private $table = false; // Table for Templates
		private $section = false; // Section for Templates		
		private $lang = false; // Section for Templates		
		
		// Set Header
		public $header	=	"";
		public function set_header($header) { $this->header = $header; }
		
		// Set Footer
		public $footer	=	"";
		public function set_footer($footer) { $this->footer = $footer; }
		
		// Set Content / Overwrites set_template
		public $content	=	"";
		public $subject	=	"";
		public function set_content($content, $subject) { $this->content = $this->header.$content.$this->footer; $this->subject = $subject; }		
		public function set_template($name) { 
			$bind[0]["value"] 	= $name;
			$bind[0]["type"] 	= "s";
			$bind[1]["value"] 	= $this->section;
			$bind[1]["type"] 	= "s";
			$bind[2]["value"] 	= $this->lang;
			$bind[2]["type"] 	= "s";
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE name = ? AND section = ? AND lang = ?", false, $bind);
			if(is_array($ar)) {
				$this->subject = $ar["subject"];
				$this->content = $ar["content"];
				return true;
			} else { return false; }
		}
		
		// Table Init
		private function create_table() {
			$this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table."` (
								  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Identificator',
								  `name` varchar(256) NOT NULL COMMENT 'Template Identifier',
								  `subject` text NULL COMMENT 'Template Subject',
								  `description` text NULL COMMENT 'Template Description',
								  `content` text DEFAULT NULL COMMENT 'Template Content',
								  `lang` VARCHAR(32) DEFAULT '' COMMENT 'Language Key',
								  `section` VARCHAR(128) DEFAULT NULL COMMENT 'Related Section',
								  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation',
								  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification | Auto - Set',
								  PRIMARY KEY (`id`),
								  UNIQUE KEY `".$this->table."_unique` (`name`, `lang`, `section`));");}

		// Construct		
		function __construct($mysql, $table, $section = "", $lang = "") {
			$this->mysql = $mysql;
			$this->table = @substr(trim($table ?? ''), 0, 256);
			$this->section = @substr(trim($section ?? ''), 0, 127);		
			$this->lang = @substr(trim($lang ?? ''), 0, 127);		
			if(!$this->mysql->table_exists($table)) { $this->create_table(); $this->mysql->free_all(); }} 

		// Setup new Mail template 
		public function setup($name, $subject, $content, $description = "", $overwrite = false, $lang = false) {
			if(!$lang) { $lang = $this->lang; }
			$bind[0]["value"] 	= $name;
			$bind[0]["type"] 	= "s";
			$bind[1]["value"] 	= $this->section;
			$bind[1]["type"] 	= "s";
			$bind[2]["value"] 	= $lang;
			$bind[2]["type"] 	= "s";
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE name = ? AND section = ? AND lang = ?", false, $bind);
			if(is_array($ar)) {
				if($overwrite) { 
					$bind[0]["value"] 	= $name;
					$bind[0]["type"] 	= "s";
					$bind[1]["value"] 	= $subject;
					$bind[1]["type"] 	= "s";
					$bind[2]["value"] 	= $content;
					$bind[2]["type"] 	= "s";
					$bind[3]["value"] 	= $description;
					$bind[3]["type"] 	= "s";
					$bind[4]["value"] 	= $name;
					$bind[4]["type"] 	= "s";
					$bind[5]["type"]	= "s";
					$bind[5]["value"]	= $this->section;
					$bind[6]["type"]	= "s";
					$bind[6]["value"]	= $lang;
					$this->mysql->query("UPDATE `".$this->table."` SET name = ?, subject = ?, content = ?, description = ? WHERE name = ? AND section = ? AND lang = ?", $bind);
				}
			} else { 
				$bind[0]["value"] 	= $name;
				$bind[0]["type"] 	= "s";
				$bind[1]["value"] 	= $subject;
				$bind[1]["type"] 	= "s";
				$bind[2]["value"] 	= $content;
				$bind[2]["type"] 	= "s";
				$bind[3]["value"] 	= $description;
				$bind[3]["type"] 	= "s";
				$bind[4]["type"]	= "s";
				$bind[4]["value"]	= $this->section;
				$bind[5]["type"]	= "s";
				$bind[5]["value"]	= $lang;
				$this->mysql->query("INSERT IGNORE INTO `".$this->table."` (name, subject, content, description, section, lang) VALUES(?, ?, ?, ?, ?, ?);", $bind);
				return $this->mysql->insert_id;
			}			
		}
		
		// Substitutions
		private $substitute = array(); // Section for Templates	
		
		// Reset Substitutions
		public function reset_substitution() { $this->substitute = array(); }
		
		// Add Substitutions
		public function add_substitution($name, $replace) { 
			$substitute = $this->substitute; 
			array_push($substitute, array($name, $replace));
			$this->substitute = $substitute;}
			
		// Do Substitutions on Text
		public function do_substitute($text) {
			if(is_array($this->substitute)) {
				foreach($this->substitute as $key => $value) { $text = @str_replace($value[0], $value[1], $text); }
				return $text;
			}			
		}			
		
		// Get The Content for a Prepared Mail
		public function get_content($substitute = false) {
			if($substitute) {
				return $this->do_substitute($this->content);
			} else {
				return $this->content;
			}
		}
		
		// Get The Subject for a Prepared Mail
		public function get_subject($substitute = false) {
			if($substitute) {
				return $this->do_substitute($this->subject);
			} else {
				return $this->subject;
			}
		}		
		
		// Different Set of Functions depending on current set section and language
		public function change($id, $name, $subject, $content, $description = "") {
			if(!is_numeric($id)) { return false; }
			$bind[0]["value"] = $this->section;
			$bind[0]["type"] = "s";
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = ?", false, $bind);
			if(is_array($ar)) {
				$bind[0]["value"]	= $subject;
				$bind[0]["type"] 	= "s";
				$bind[1]["value"] 	= $content;
				$bind[1]["type"] 	= "s";
				$bind[2]["value"] 	= $description;
				$bind[2]["type"] 	= "s";
				$bind[3]["type"]	=	"s";
				$bind[3]["value"]	=	$name;
				$bind[4]["type"]	=	"s";
				$bind[4]["value"]	=	$this->section;
				$bind[5]["type"]	=	"s";
				$bind[5]["value"]	=	$this->lang;
				$this->mysql->query("UPDATE `".$this->table."` SET subject = ?, content = ?, description = ?, name = ? WHERE id = '".$id."' AND section = ? AND lang = ?", $bind);
			}		
		}
		
		public function name_exists($name) {
			$bind[0]["value"] = $name;
			$bind[0]["type"] = "s";
			$bind[1]["type"]	=	"s";
			$bind[1]["value"]	=	$this->section;
			$bind[2]["type"]	=	"s";
			$bind[2]["value"]	=	$this->lang;
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE name = ? AND section = ? AND lang = ?", false, $bind);
			if(is_array($ar)) {
				return $ar["id"];
			} else { 
				return false;
			}			
		}

		public function get_name_by_id($id) {
			if(!is_numeric($id)) { return false; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->section;
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$this->lang;
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = ? AND lang = ?", false, $b);
			if(is_array($ar)) {
				return $ar["name"];
			} else { 
				return false;
			}			
		}
		
		public function id_exists($id) {
			if(!is_numeric($id)) { return false; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->section;
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$this->lang;
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = ? AND lang = ?", false, $b);
			if(is_array($ar)) {
				return true;
			} else { 
				return false;
			}			
		}
		
		public function id_delete($id) {
			if(!is_numeric($id)) { return false; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->section;
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$this->lang;
			return $this->mysql->query("DELETE FROM `".$this->table."` WHERE id = '".$id."' AND section = ? AND lang = ?", $b);
		}
		
		public function name_delete($name) {
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$name;
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$this->section;
			return $this->mysql->query("DELETE FROM `".$this->table."` WHERE name = ? AND id = '".$id."' AND section = ?", $b);
		}
		
		public function get_full($id) {
			if(!is_numeric($id)) { return false; }
			$b[0]["type"]	=	"s";
			$b[0]["value"]	=	$this->section;
			$b[1]["type"]	=	"s";
			$b[1]["value"]	=	$this->lang;
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = ? AND lang = ?", false, $b);
			if(is_array($ar)) {
				return $ar;
			} else { 
				return false;
			}			
		}
	} 
	
	