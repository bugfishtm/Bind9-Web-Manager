<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  SessionSecurity */	
	class x_class_block {
		// Class Variables
		private $key = false; # Sessionkey
		private $maxcount = false; # Max Count Till Blocked
		private $block_time = false; # If Not False, Than Block will be released after Certain Seconds		
		public $blocked = false; # True if Blocked | False if Not
		// Constructor		
		function __construct($pre_key, $maxcount, $block_time = false) {
			if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
			$this->key = $pre_key."x_class_sessionblock";
			$this->maxcount = $maxcount;
			$this->block_time = $block_time; $this->blocked(); }

		// Check if Blocked	
		function blocked()  { 
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
		function increase() { 
			if(!is_numeric(@$_SESSION[$this->key])) {
				 $_SESSION[$this->key] = 1; 
			} @$_SESSION[$this->key] = @$_SESSION[$this->key] + 1; $this->blocked(); }
		// Decrease
		function decrease() { if(!is_numeric($_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } @$_SESSION[$this->key] = $_SESSION[$this->key] - 1; $this->blocked(); return true; }
		// Reset
		function reset()    { if(!is_numeric($_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } $_SESSION[$this->key] = 0; unset($_SESSION[$this->key."_tms"]); $this->blocked(); return true;}
	}
