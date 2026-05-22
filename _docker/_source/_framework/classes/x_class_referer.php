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
	
	class x_class_referer {
		######################################################
		// Class Variables
		######################################################
		private $mysql		=  false;
		private $refurl		=  false;
		private $mysqltable	=  false;
		private $enabled 	=  true; public function enabled($bool = true) {$this->enabled = $bool;} 
		private $urlpath 	=  false;

		######################################################
		// Table Initialization
		######################################################
		private function create_table() {
			return $this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->mysqltable."` (
											  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID to Identify',
											  `full_url` varchar(256) NOT NULL DEFAULT '0' COMMENT 'Related Referer URL',
											  `site_url` varchar(256) NOT NULL DEFAULT '0' COMMENT 'Related Website URL',
											  `hits` int(10) NOT NULL DEFAULT '0' COMMENT 'Hitcounter for Referer URL',
											  `section` varchar(128) NOT NULL DEFAULT '' COMMENT 'Related Multi Site Section',
											  `creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date - Auto Set',
											  `modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date | Auto - Set',
											  PRIMARY KEY (`id`),
											  UNIQUE KEY `".$this->mysqltable."_unique` (`full_url`, `section`, `site_url`) USING BTREE );");		
		}
		
		######################################################
		// Constructor
		######################################################
		function __construct($mysql, $table, $refurlnowww) {
			$this->mysql 		= $mysql;
			$this->refurl 		= $refurlnowww;
			$this->mysqltable 	= $table;
			if(!$this->mysql->table_exists($table)) { $this->create_table(); $this->mysql->free_all();  }}
		
		######################################################
		// Prepare URL for Database
		######################################################
		private function prepareUrl($tmpcode){
			if(@strpos($tmpcode, "?") > -1) 			{$tmpcode = @substr($tmpcode, 0, 	@strpos($tmpcode, "?"));}
			if(@strpos($tmpcode, "&") > -1)			{$tmpcode = @substr($tmpcode, 0, 	@strpos($tmpcode, "&"));} 
			if(@strpos($tmpcode, "https://") > -1)	{$tmpcode = @substr($tmpcode, 		@strpos($tmpcode, "https://"));} 
			if(@strpos($tmpcode, "http://") > -1)	{$tmpcode = @substr($tmpcode, 		@strpos($tmpcode, "http://"));} 
			if(@strpos($tmpcode, "www.") > -1)		{$tmpcode = @substr($tmpcode, 		@strpos($tmpcode, "www."));} 
			$return = @urldecode(trim($tmpcode ?? '') ?? '');
		}	

		######################################################
		// Get current saved Referers in Array
		######################################################		
		public function get_array() {
			return $this->mysql->select("SELECT * FROM `".$this->mysqltable."`", true);
		}
		
		######################################################
		// Execute Function
		######################################################
		public function execute($section = ""){
			if ( $parts = @parse_url( @$_SERVER["HTTP_REFERER"] ) AND $this->enabled) {
				$thecurrentreferer = $this->prepareUrl(@$parts[ "host" ]);
				$b[0]["type"]	=	"s";
				$b[0]["value"]	=	@substr(trim($thecurrentreferer ?? ''), 0, 400);
				$b[1]["type"]	=	"s";
				$b[1]["value"]	=	@substr(trim(@$_SERVER["REQUEST_URI"] ?? ''), 0, 256);
				$b[2]["type"]	=	"s";
				$b[2]["value"]	=	@substr(trim($section ?? ''), 0, 400);
				if(@trim(@$parts[ "host" ] ?? '') != $this->refurl AND @trim(@$parts[ "host" ] ?? '') != "www.".$this->refurl AND @trim(@$parts[ "host" ] ?? '') != "") {
					$query = "SELECT * FROM `".$this->mysqltable."` WHERE full_url = ? AND site_url = ? AND section = ?;";
					$sresult = @$this->mysql->select($query, false, $b);
					if (!is_array($sresult)) { 
						$query = @$this->mysql->query("INSERT INTO `".$this->mysqltable."` (full_url, site_url, section, hits) VALUES (?, ?, ?, 1)", $b);
					} else {
						$query = @$this->mysql->update("UPDATE `".$this->mysqltable."` SET hits = hits + 1 WHERE full_url = ? AND site_url = ? AND section = ?;", $b);
					}				
				}
			} return true;
		} 
	}
