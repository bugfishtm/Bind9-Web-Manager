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
	class x_class_curl {
		// Class Variables
		public $last_info = false;
		
		// Log Errors and Outputs
		private $logging = false; private $logging_settings = false; private $logging_table = false; private $mysql = false;
		public function logging($mysql, $logging, $logging_settings, $logging_table) { $this->logging = $logging;$this->logging_settings = $logging_settings;$this->logging_table = $logging_table;  $this->mysql = $mysql;  
			if(!$this->mysql->table_exists($logging_table)) { $this->create_table(); $this->mysql->free_all();  }
		}
		
		######################################################
		// Table Initialization
		######################################################
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->logging_table."` (
										  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Identificator',
										  `url` text COMMENT 'Remote URL',
										  `request`  varchar(64) COMMENT 'Request Type Name (Function)',
										  `filename` text COMMENT 'Filename if Upload Function',
										  `settings` text COMMENT 'Settings for this Request',
										  `output` text COMMENT 'Output for this Request',
										  `type` varchar(64) COMMENT 'Request Type',
										  `url` text COMMENT 'PHP Server Request URI',
										  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation',
										  PRIMARY KEY (`id`) );");
		}
		
		// Conversions
		public function xml_to_array($xml) {$xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);$json = json_encode($xml);$array = json_decode($json,TRUE);	return $array;}
		public function xml_to_json($xml) {$xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);$json = json_encode($xml);return $json;}
		public function json_to_array($json) {$array = json_decode($json,TRUE);	return $array;}
		public function json_to_xml($json) {$array = json_decode($json,TRUE);$xml = new SimpleXMLElement('<root/>');array_walk_recursive($array, array ($xml, 'addChild'));return $xml->asXML();}
		public function array_to_xml($array) {$xml = new SimpleXMLElement('<root/>');array_walk_recursive($array, array ($xml, 'addChild'));return $xml->asXML();}
		public function array_to_json($array) {$json = json_encode($array);return $json;}
		
		// Request
		public function request($url, $request = "GET", $settings = array()) {
			// Reset Last Info
			$this->last_info = false;

			// Init Curl
			$ch = curl_init();
			
			// Settings for Request
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
			
			// Add Settings to Curl
			if(is_array($settings)) {
				foreach($settings AS $key => $value) {
					curl_setopt($ch, constant($key), $value);
				}
			}
			
			// Exec and Output
			$output = curl_exec($ch); 
			
			// Refresh Last Information
			$this->last_info = curl_getinfo($ch);

			// Close Curl
			curl_close($ch);
			
			// Log if Needed
			if($this->logging) { 
				$bind[0]["type"] = "s";
				$bind[0]["value"] = serialize($output);
				
				$bind[1]["type"] = "s";
				$bind[1]["value"] = $request;

				$bind[2]["type"] = "s";
				if($this->logging_settings) { $bind[2]["value"] = serialize($settings); } else { $bind[2]["value"] = "deactivated"; }

				$bind[3]["type"] = "s";
				$bind[3]["value"] = $url;			
				
				$this->mysql->query("INSERT INTO `".$this->logging_table."`(output, request, settings, url, filename, type) VALUES(?, ?, ?, ?, 'none', 'request');", $bind); 
			}
			
			// Return Output of Request
			return $output;
		}			
		
		// Download a File
		public function download($remote, $local, $request = "GET", $settings = array()) {
			// Reset Last Info
			$this->last_info = false;
			
			// Open File Stream
			$fp = fopen ($local, 'w+');
			
			// Init Curl Request
			$ch = curl_init();
			
			// Settings for File Download
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 600);
			curl_setopt($ch, CURLOPT_FILE, $fp); 
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_URL, $remote);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
			
			// Add Settings to Curl
			if(is_array($settings)) {
				foreach($settings AS $key => $value) {
					curl_setopt($ch, constant($key), $value);
				}
			}	

			// Exec and Output
			$output = curl_exec($ch); 
			
			// Refresh Last Information
			$this->last_info = curl_getinfo($ch);
			
			// Close Curl
			curl_close($ch);
			
			// Close File Object
			fclose($fp);

			// Log if Needed
			if($this->logging) { 
				$bind[0]["type"] = "s";
				$bind[0]["value"] = serialize($output);
				
				$bind[1]["type"] = "s";
				$bind[1]["value"] = $request;

				$bind[2]["type"] = "s";
				if($this->logging_settings) { $bind[2]["value"] = serialize($settings); } else { $bind[2]["value"] = "deactivated"; }

				$bind[3]["type"] = "s";
				$bind[3]["value"] = $remote;			

				$bind[4]["type"] = "s";
				$bind[4]["value"] = $local;	
				
				$this->mysql->query("INSERT INTO `".$this->logging_table."`(output, request, settings, url, filename, type) VALUES(?, ?, ?, ?, ?, 'request');", $bind); 
			}
			
			// Return Output of Request
			return $output;
		}

		// Upload without Authentication
		public function upload($remote, $local, $request = "POST", $settings = array()) {
			// Reset Last Info
			$this->last_info = false;			
			
			// Init Curl Request
			$ch = curl_init();
			
			// Set Curfile
			$args['file'] = new CurlFile($local, mime_content_type($local));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
			
			// Settings for Upload
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $remote);
			curl_setopt($ch, CURLOPT_TIMEOUT, 600);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);

			// Add Settings to Curl
			if(is_array($settings)) {
				foreach($settings AS $key => $value) {
					curl_setopt($ch, constant($key), $value);
				}
			}	

			// Exec and Output
			$output = curl_exec($ch); 
			
			// Refresh Last Information
			$this->last_info = curl_getinfo($ch);
			
			// Close Curl
			curl_close($ch);
			
			// Log if Needed
			if($this->logging) { 
				$bind[0]["type"] = "s";
				$bind[0]["value"] = serialize($output);
				
				$bind[1]["type"] = "s";
				$bind[1]["value"] = $request;

				$bind[2]["type"] = "s";
				if($this->logging_settings) { $bind[2]["value"] = serialize($settings); } else { $bind[2]["value"] = "deactivated"; }

				$bind[3]["type"] = "s";
				$bind[3]["value"] = $remote;			

				$bind[4]["type"] = "s";
				$bind[4]["value"] = $local;	
				
				$this->mysql->query("INSERT INTO `".$this->logging_table."`(output, request, settings, url, filename, type) VALUES(?, ?, ?, ?, ?, 'request');", $bind); 
			}
			
			// Return Output of Request
			return $output;
		}			
	}
	