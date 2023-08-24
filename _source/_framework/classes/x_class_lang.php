<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Multilang Control Class */
	class x_class_lang {		
		// Class Variables
		private $mysql   = false; 
		private $table   = false; 
		private $section = "none"; 	
		private $lang = false; 	
		private $array = array(); 	
		
		// Table Initialization
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table."` (
												  `id` int(9) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
												  `identificator` varchar(512) NOT NULL COMMENT 'Descriptor for Translation',
												  `lang` varchar(16) NOT NULL COMMENT 'Value for Constant',
												  `translation` text COMMENT 'Description for Constant',
												  `section` varchar(128) DEFAULT '' COMMENT 'Section for Constant (For Multi Site)',
												  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date of Entry | Will be Auto-Set',
												  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date of Entry with Auto-Update on Change',
												  PRIMARY KEY (`id`),
												  UNIQUE KEY `Unique` (`identificator`,`lang`,`section`) USING BTREE);");}
												  
		// Construct the Class		
		private $filemode = false;
		function __construct($mysql = false, $table = false, $lang = "none", $section = "none", $file_name = false) {
			$this->mysql = $mysql;
			$this->table = $table;
			$this->lang = $lang;
			$this->section = $section;
			if(is_object($mysql)) { if(!$this->mysql->table_exists($table)) { $this->create_table(); $this->mysql->free_all();  } 
				$this->init(); } else {
				if($file_name) {
					$this->filemode = true;
					if(file_exists($file_name)) { 
						$file = file($file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
						foreach ($file as $array) {
							if(strpos($array, "=") > 1) { 
								if(substr(trim($array), 0, 2) == "//" OR substr(trim($array), 0, 1) == "#") {  }
								else { 
									$newkey = @substr(trim($array), 0, strpos(trim($array), "=")); 
									$newvalue = @substr(trim($array), strpos(trim($array), "=") + 1); 
									$newval[$newkey] = $newvalue; 
									$this->array = array_merge($this->array, $newval);
								}
							}
						}
					}
				} 
			}
		}
		
		
		// Init the Array to Fetch Translations Without SQL Queries for current Loaded Translation
		private function init() {
			if($this->filemode) { return false; }
			$rres = @$this->mysql->select("SELECT identificator, translation FROM `".$this->table."` WHERE lang = '".$this->mysql->escape($this->lang)."' AND section = '".$this->section."'", true);
			if(is_array($rres)) {
				foreach($rres as $key => $value) {
					$newar = array();
					$newar[$value["identificator"]] = $value;
					array_push($this->array, $newar);
				}
			} 
		}

		// Delete a Key from current Language loaded or From Another Language
		public function delete($key, $lang = false) {
			if($this->filemode) { return false; }
			if(!$lang) {
				return @$this->mysql->query("DELETE FROM `".$this->table."` WHERE lang = '".$this->mysql->escape($this->lang)."' AND section = '".$this->section."' AND identificator = '".$this->mysql->escape($key)."'");
			} else {
				return @$this->mysql->query("DELETE FROM `".$this->table."` WHERE lang = '".$this->mysql->escape($lang)."' AND section = '".$this->section."' AND identificator = '".$this->mysql->escape($key)."'");
			}
		}
		
		// Add a new Translation Key with Text and for loaded Lang // Or another Lang if entered as parameter
		public function add($key, $text, $lang = false) {
			if($this->filemode) { return false; }
			if(!$lang) {
				$b[0]["type"]	=	"s";
				$b[0]["value"]	=	$text;					
				return @$this->mysql->query("INSERT INTO `".$this->table."`(section, lang, identificator, translation) VALUES('".$this->section."', '".$this->mysql->escape($this->lang)."', '".$this->mysql->escape($key)."', ?);", $b);
			} else {
				$b[0]["type"]	=	"s";
				$b[0]["value"]	=	$text;					
				return @$this->mysql->query("INSERT INTO `".$this->table."`(section, lang, identificator, translation) VALUES('".$this->section."', '".$this->mysql->escape($lang)."', '".$this->mysql->escape($key)."', ?);", $b);
			}
		}


		// Translate for the current Loaded Language 
		public function translate($key) {
			if(isset($this->array[$key])) { return $this->array[$key]; } else { return $key; }
		}		
	}
