<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  SessionSecurity */	
	class x_class_block {
		// Class Variables
		private $key = false;
		private $maxcount = false;
		private $last_time = false;
		private $block_time = false;

		// Constructor
		function __construct($key, $maxcount, $block_time = false) {
			if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
			$this->key = "x_class_sessionblock".$key;
			$this->maxcount = $maxcount;
			$this->last_time = @$_SESSION[$this->key."_tms"];
			$this->block_time = $block_time;}

		// Check if Blocked	
		function blocked()  { 
			if(is_numeric($this->block_time)) {
				if(!is_numeric(@$_SESSION[$this->key])) { $_SESSION[$this->key] = 0; return false; }
				if(is_numeric($this->last_time)) {
					$nowstr = time();
					$nowstr = $nowstr - $this->last_time;
					$nowstr = $nowstr + $this->block_time;
					if($nowstr > 0) {
						// Is Expired
						$_SESSION[$this->key] = 0;
						return false;
					} else {
						if($_SESSION[$this->key] >= $this->maxcount) { return true; } else { return false; }
					}
				} else { if($_SESSION[$this->key] >= $this->maxcount) { return true; } else { return false; } return false; }
			} else {
				if(!is_numeric(@$_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } if($_SESSION[$this->key] >= $this->maxcount) { return true; } return false; 
			}
		}

		// Increase
		function increase() { if(!is_numeric(@$_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } @$_SESSION[$this->key] = @$_SESSION[$this->key] + 1; @$_SESSION[$this->key."_tms"] = time(); $this->last_time = @$_SESSION[$this->key."_tms"];}
		// Decrease
		function decrease() { if(!is_numeric(@$_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } if(@$_SESSION[$this->key] != 0) {@$_SESSION[$this->key] = $_SESSION[$this->key] + 1;}	}
		// Reset
		function reset()    { if(!is_numeric(@$_SESSION[$this->key])) { $_SESSION[$this->key] = 0; } if(@$_SESSION[$this->key] != 0) {@$_SESSION[$this->key] = 0;}	}
	}
?>