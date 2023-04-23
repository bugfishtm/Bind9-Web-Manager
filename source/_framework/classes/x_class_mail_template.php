<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Mail Templates Class */		
	// Class for Handling Mail Templates and Substitutions to them, eventually directly send with x_class_mail
	class x_class_mail_template {
		// Class Variables
		private $mysql = false; // MySQL for Templates
		private $table = false; // Table for Templates
		private $section = false; // Section for Templates		
		private $substitute = array(); // Section for Templates	
		// Table Init
		private function create_table() {
			$this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table."` (
								  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Identificator',
								  `name` varchar(256) NOT NULL COMMENT 'Template Identifier',
								  `subject` text NULL COMMENT 'Template Subject',
								  `content` text DEFAULT NULL COMMENT 'Template Content',
								  `section` VARCHAR(128) DEFAULT NULL COMMENT 'Related Section',
								  PRIMARY KEY (`id`),
								  UNIQUE KEY `Unique` (`name`));");}
		// Construct		
		function __construct($mysql, $table, $section = "") {
			$this->mysql = $mysql;
			$this->table = @substr(trim($table), 0, 256);
			$this->section = @substr(trim($section), 0, 127);		
			if(!$this->mysql->table_exists($table)) { $this->create_table(); $this->mysql->free_all(); }} 
		######################################################################################################################################
		// Send a Template		
		public function send($x_class_mail, $mail_subject, $receiver, $template, $cc = false, $bcc = false, $attach = false, $substitute = true, $header = false, $footer = false, $settings = array()) {
			$content = $this->get($template, $substitute, $header , $footer);
			return $this->x_class_mail->mail($mail_subject, $content, $receiver, $cc, $bcc, $attach, $settings); }
		// Get a Template
		public function get($name, $substitute = true, $header = false, $footer = false) {
			$output = "";
			if($header !== false) { $output .= $header; }
			else {  $output .= $this->header;  }
			if($footer !== false) { $output .= $footer; }
			else {  $output .= $this->footer;  }
			$ar = $this->mysql->select("SELECT * FROM ".$this->table." WHERE name = '".$this->mysql->escape($name)."' AND section = '".$this->section."'", false);
			if(is_array($ar)) {
					$output .= $ar["content"];
					foreach($substitute as $key => $value) {
						$output = str_replace($value[0], $value[1], $output);
					}
			} else { $output .= "Error generating Mail Content<br />Please contact technical support!"; }	
			return $output; }
		// Get a Template
		public function get_subject($name, $substitute = true) {
			$output = "";
			$ar = $this->mysql->select("SELECT * FROM ".$this->table." WHERE name = '".$this->mysql->escape($name)."' AND section = '".$this->section."'", false);
			if(is_array($ar)) {
					$output .= $ar["subject"];
					foreach($substitute as $key => $value) {
						$output = str_replace($value[0], $value[1], $output);
					}
			} else { $output .= "Error generating Mail Subject!"; }	
			return $output; }
		// Reset Substitutions
		public function reset_substitution() { $this->substitute = array(); }
		// Add Substitutions
		public function add_substitution($name, $replace) { 
			$substitute = $this->substitute; 
			array_push($substitute, array(array($name, $replace)));
			$this->substitute = $substitute;}
		// Do Substitutions on Text
		public function do_substitute($text) {
			if(is_array($this->substitute)) {
				foreach($this->substitute as $key => $value) { $text = str_replace($value[0], $value[1], $text); }
				return $text;
			}			
		}	
		// Set Template / Overwrites set_content
		public $content	=	"";
		public function set_template($name, $substitute = true, $header = false, $footer = false) { $this->content = $this->get($name, $substitute, $header, $footer); }
		// Set Content / Overwrites set_template
		public function set_content($content) { $this->content = $content; }
		// Set Header
		public $header	=	"";
		public function set_header($header) { $this->header = $header; }
		// Set Footer
		public $footer	=	"";
		public function set_footer($footer) { $this->footer = $footer; }
	} 
	
	