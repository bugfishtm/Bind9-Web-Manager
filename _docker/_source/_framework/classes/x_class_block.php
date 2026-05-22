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
	
	class x_class_block {
		// Class Variables
		private $key = false; # Sessionkey
		private $dummy = false; # Sessionkey
		private $maxcount = false; # Max Count Till Blocked
		private $block_time = false; # If Not False, Than Block will be released after Certain Seconds		
		public  $blocked = false; # True if Blocked | False if Not
		// Constructor		
		function __construct($pre_key, $maxcount, $block_time = false, $dummy = false) {
			if($dummy ) { $this->dummy = true; return true; }
			if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
			$this->key = $pre_key."x_class_sessionblock";
			$this->maxcount = $maxcount;
			$this->block_time = $block_time; $this->blocked(); }

		// Check if Blocked	
		function blocked()  { 
			if($this->dummy) { return false; }
			if(is_numeric($this->block_time)) {
				if(!is_numeric(@$_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } 
				if(is_numeric(@$_SESSION[$this->key."_tms"])) {
					$nowstr = time();
					$nowstr = $nowstr - $_SESSION[$this->key."_tms"];
					$nowstr = $nowstr + ($this->block_time * 1000);
					if($nowstr > 0) {
						// Is Expired
						unset($_SESSION[$this->key."_tms"]);
						$_SESSION[$this->key] = 0;
						$this->blocked = false;
						return false;
					} else {
						$this->blocked = true;
						return true;
					}			
					if($_SESSION[$this->key] >= $this->maxcount) {
						if(!$this->blocked) { $_SESSION[$this->key."_tms"] = time(); } $this->blocked = true; return true;
					} else { $this->blocked = false; return false; }					
				} else {
					if($_SESSION[$this->key] >= $this->maxcount) {
						if(!$this->blocked) { $_SESSION[$this->key."_tms"] = time(); } $this->blocked = true; return true;
					} else { $this->blocked = false; return false; }
				}
			} else {
				if(!is_numeric(@$_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } 
				if($_SESSION[$this->key] >= $this->maxcount) {
					$this->blocked = true; return true;
				} else { $this->blocked = false; return false; }
			}
			$this->blocked = true;
			return true;
		}

		// Increase
		function increase($int = 1) {
			if($this->dummy) { return false; }
			if(!is_numeric(@$_SESSION[$this->key])) {
				 $_SESSION[$this->key] = $int; 
			} @$_SESSION[$this->key] = @$_SESSION[$this->key] + $int; $this->blocked(); }
			
		// Decrease
		function decrease($int = 1) { 
			if($this->dummy) { return false; }
			if(!is_numeric($_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } @$_SESSION[$this->key] = $_SESSION[$this->key] - $int; $this->blocked(); return true; 
		}
		
		// Reset
		function reset()    { 
			if($this->dummy) { return false; }
			if(!is_numeric($_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } $_SESSION[$this->key] = 0; unset($_SESSION[$this->key."_tms"]); $this->blocked(); return true;
		}
	}
