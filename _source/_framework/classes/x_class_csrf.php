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
	class x_class_csrf {
		// Settings Variables and Functions
		private $extension 	= false;
		private $valid_time = 300; 
		private $norenewal 	= false; public function disableRenewal($bool = false) {$this->norenewal = $bool;} public function norenewal($bool = false) {$this->norenewal = $bool;} public function isDisabled() {return $this->norenewal;}		
		private $external_action = false; public function external_action($bool = false) {$this->external_action = $bool;}

		// Current Session CSRF Parameters
		private $c_key	= false;  public function get() {return $this->c_key;} 
		private $c_key_time	= false;  public function get_time() {return $this->c_key_time;} 
		
		// Last Session Keys for Actions to Use for Checking
		private $l_key		 		= false; public function get_lkey() { return $this->l_key; }
		private $l_key_time	 		= false; public function get_lkey_time() { return $this->l_key_time; }
	
		// Construct and Generate a Session Key
		function __construct($cookie_extension = "", $second_valid = 300, $external_action = false) {
			if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
			// Generate new Key and Save to Parameters
			$this->c_key			 = mt_rand(100000000,900000000);
			$this->c_key_time		 = @$_SESSION[$this->extension."x_class_csrf_tms"];
			// Cookie Extension
			$this->extension  	 = $cookie_extension;
			// Valid Time for CSRF Key
			$this->valid_time 	 = $second_valid;
			// Disable Renewal for Actions if Parameter Set True
			if($external_action) { $this->disableRenewal(true);  $this->external_action  = true; }
			// Set the Last Session Keys Time and Value to Class Parameters if there is any
			$this->l_key			 = @$_SESSION[$this->extension."x_class_csrf"];
			$this->l_key_time		 = @$_SESSION[$this->extension."x_class_csrf_tms"];
		}
		
		// Get Input Hidden Field with Current Key, Provided ID and Name
		public function getField($name, $id = "") {echo '<input type="hidden" name="'.$name.'" id="'.$id.'" value="'.$this->c_key.'">';}
		
		// Get Key for Purpose (External or Current
		public function crypto() { if($this->external_action) { return $this->l_key; } else { return $this->c_key; }}

		// Get Key for Purpose (External or Current
		public function time() { if($this->external_action) { return $this->l_key_time; } else { return $this->c_key_time; }}
		
		// Check Key for Purpose (External or Current
		public function validate($code, $override_valid_time = false) {
			if($this->external_action) { return $this->check_lkey($code, $override_valid_time); }
			else { return $this->check($code, $override_valid_time); }}		
		
		// Check if Submitted CSRF if Valid with CSRF Before in Session
		public function check($code, $override_valid_time = false) {		
			if(!$override_valid_time) { $override_valid_time = $this->valid_time;}
			$tmp_s	=	@$_SESSION[$this->extension."x_class_csrf"];
			$tmp_st	=	@$_SESSION[$this->extension."x_class_csrf_tms"];
			if(@$code == $tmp_s AND @$code != NULL AND @trim($code) != "") {
				if((time() - $tmp_st) < $override_valid_time AND $tmp_st != NULL AND isset($tmp_st)) {
						return true;
				} return false;
			}			
			return false;
		}

		// Check Last Key for Actions
		public function check_lkey($code, $override_valid_time = false) {
			if(@$this->l_key_time == false) { $cct = "undef"; } else { $cct = @$this->l_key_time; }	
			if(!$override_valid_time) { $override_valid_time = $this->valid_time; }
			if(@$code == @$this->l_key AND @$code != NULL AND @trim($code) != "") {
				if((time() - $cct) < $override_valid_time AND $cct != NULL AND isset($cct)) {
					return true;
				}			
			} 
			return false;
		} 			

		// Deconstruct and Apply new CSRF to Session
		function __destruct() {
			if(!$this->norenewal) {
				$_SESSION[$this->extension."x_class_csrf"] = $this->c_key;
				$_SESSION[$this->extension."x_class_csrf_tms"]  = time();
			}
		}			
	}
