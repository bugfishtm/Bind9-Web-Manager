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
	// Class for Handling Mail Templates and Substitutions to them, eventually directly send with x_class_mail
	class x_class_mail_template {
		// Class Variables
		private $mysql = false; // MySQL for Templates
		private $table = false; // Table for Templates
		private $section = false; // Section for Templates		
		
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
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE name = '".$this->mysql->escape($name)."' AND section = '".$this->section."'", false);
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
								  `section` VARCHAR(128) DEFAULT NULL COMMENT 'Related Section',
								  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation',
								  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification | Auto - Set',
								  PRIMARY KEY (`id`),
								  UNIQUE KEY `Unique` (`name`, `section`));");}

		// Construct		
		function __construct($mysql, $table, $section = "") {
			$this->mysql = $mysql;
			$this->table = @substr(trim($table), 0, 256);
			$this->section = @substr(trim($section), 0, 127);		
			if(!$this->mysql->table_exists($table)) { $this->create_table(); $this->mysql->free_all(); }} 
					
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
		
		// Setup new Mail template 
		public function setup($name, $subject, $content, $description = "", $overwrite = false) {
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE name = '".$this->mysql->escape($name)."' AND section = '".$this->section."'", false);
			if(is_array($ar)) {
				if($overwrite) { 
					$bind[0]["value"] = $subject;
					$bind[0]["type"] = "s";
					$bind[1]["value"] = $content;
					$bind[1]["type"] = "s";
					$bind[2]["value"] = $description;
					$bind[2]["type"] = "s";
					$this->mysql->query("UPDATE `".$this->table."` SET name = '".$name."', subject = ?, content = ?, description = ? WHERE name = '".$this->mysql->escape($name)."' AND section = '".$this->section."'", $bind);
				}
			} else { 
				$bind[0]["value"] = $subject;
				$bind[0]["type"] = "s";
				$bind[1]["value"] = $content;
				$bind[1]["type"] = "s";
				$bind[2]["value"] = $description;
				$bind[2]["type"] = "s";
				$this->mysql->query("INSERT IGNORE INTO `".$this->table."` (name, subject, content, description, section) VALUES('".$name."', ?, ?, ?,'".$this->section."');", $bind);
				return $this->mysql->insert_id;
			}			
		}
		
		// Setup new Mail template 
		public function change($id, $name, $subject, $content, $description = "") {
			if(!is_numeric($id)) { return false; }
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = '".$this->section."'", false);
			if(is_array($ar)) {
				$bind[0]["value"] = $subject;
				$bind[0]["type"] = "s";
				$bind[1]["value"] = $content;
				$bind[1]["type"] = "s";
				$bind[2]["value"] = $description;
				$bind[2]["type"] = "s";
				$this->mysql->query("UPDATE `".$this->table."` SET name = '".$name."', subject = ?, content = ?, description = ? WHERE id = '".$id."' AND section = '".$this->section."'", $bind);
			}		
		}
		
		public function name_exists($name) {
			$bind[0]["value"] = $name;
			$bind[0]["type"] = "s";
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE name = ? AND section = '".$this->section."'", false, $bind);
			if(is_array($ar)) {
				return true;
			} else { 
				return false;
			}			
		}

		public function get_name_by_id($id) {
			if(!is_numeric($id)) { return false; }
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = '".$this->section."'", false);
			if(is_array($ar)) {
				return $ar["name"];
			} else { 
				return false;
			}			
		}
		
		public function id_exists($id) {
			if(!is_numeric($id)) { return false; }
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = '".$this->section."'", false);
			if(is_array($ar)) {
				return true;
			} else { 
				return false;
			}			
		}
		
		public function id_delete($id) {
			if(!is_numeric($id)) { return false; }
			return $this->mysql->query("DELETE FROM `".$this->table."` WHERE id = '".$id."' AND section = '".$this->section."'");
		}
		
		public function get_full($id) {
			if(!is_numeric($id)) { return false; }
			$ar = $this->mysql->select("SELECT * FROM `".$this->table."` WHERE id = '".$id."' AND section = '".$this->section."'", false);
			if(is_array($ar)) {
				return $ar;
			} else { 
				return false;
			}			
		}
	} 
	
	