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
class x_class_user {  
	/*		__________                                     __                       
			\______   \_____ ____________    _____   _____/  |_  ___________  ______
			 |     ___/\__  \\_  __ \__  \  /     \_/ __ \   __\/ __ \_  __ \/  ___/
			 |    |     / __ \|  | \// __ \|  Y Y  \  ___/|  | \  ___/|  | \/\___ \ 
			 |____|    (____  /__|  (____  /__|_|  /\___  >__|  \___  >__|  /____  >
						\/           \/      \/     \/          \/           \/    Parameters used by this class */
	## Private SQL Informations
	private $mysql=false; 		// x_class_mysql Object
	private $dt_keys=false; 	// Table for Sessions
	private $dt_users=false; 	// Table for Users
	private $table_group=false;			// Table for Groups if Activated
	private $table_group_link=false;	// Table for Group Links if Activated
	private $table_ext	=	false; 		// Table for Extrafields if Activated
	## Private Key Informations
	private $key_activation = 1; 	// Activate and Set Pass || Activate
	private $key_session = 2; 		// Session Keys
	private $key_recover = 3; 		// Recover Account Password
	private $key_mail_edit = 4;		// Keys for Mail Changes
	## Public Parameters Set if User is Logged in
	public $user_rank = false; public $rank = false;// Current User Rank
	public $user_id = false; public $id = false; // Current User Id
	public $user_name = false; public $name = false; // Current User Name
	public $user_mail = false; public $mail = false; // Current User Mail
	public $theme = false; public $user_theme = false; // Current User Mail
	public $lang = false; public $user_lang = false; // Current User Mail
	public $loggedin = false; public $loggedIn = false; // Current User Logged In?
	public $user_loggedIn = false; public $user_loggedin = false; // Current User Logged In?
	public $user=array();	
	## For Compatibility with older Class
	public $login_request_code = false; 	// Return Code out of Login Functions
	public $rec_request_code = false; 		// Return Code out of Recover Functions
	public $act_request_code = false; 		// Return Code out of Activation Functions
	public $mc_request_code = false; 		// Return Code out of Mail Change Functions
	
	/*   _________                _____.__        
		\_   ___ \  ____   _____/ ____\__| ____  
		/    \  \/ /  _ \ /    \   __\|  |/ ___\ 
		\     \___(  <_> )   |  \  |  |  / /_/  >
		 \______  /\____/|___|  /__|  |__\___  / 
				\/            \/        /_____/	and Functions belonging to some Areas like groups and extrafields  / passwordfiltering	*/
	## Public Function References and User Info // References out of Major Functions for further Processing
	public $ref=false; public $mail_ref_user=false; public $mail_ref_token=false; public $mail_ref_receiver=false;	// References set by Functions
		private function internal_ref_reset(){$this->ref=array();$this->mail_ref_user=false;$this->mail_ref_token=false; $this->mail_ref_receiver=false;}
		private function internal_ref_set($array){$this->ref=$array;$this->mail_ref_user=@$array["id"];$this->mail_ref_token=@$array["token"];$this->mail_ref_receiver=@$array["user_mail"];  }
	## General Setup
	private $multi_login=false; // Multi Login Allowed?
		public function multi_login($bool = false){$this->multi_login=$bool;} 
	private $login_recover_drop=false;  // Delete Reset Keys after Success Login?
		public function login_recover_drop($bool = false){$this->login_recover_drop=$bool;}
	private $login_field = "user_mail"; 
		public function login_field_user() { $this->login_field = "user_name"; $this->user_unique = true; } // Set User Name as Login Field Reference
		public function login_field_mail() { $this->login_field = "user_mail"; $this->mail_unique = true; } // Set User Mail as Login Field Reference
	private $mail_unique = false;  // Mail dont have to be unique if not in reference
		public function mail_unique($bool = false) { $this->mail_unique = $bool; } 
	private $user_unique = false; // User dont have to be unique if not in reference
		public function user_unique($bool = false) { $this->user_unique = $bool; } 
	## Logging Setup		
	private $log_ip=false; // Log IP Adresses?
		public function log_ip($bool=false){$this->log_ip = $bool;} 
	private $log_activation=false; // Delete old Activation Entries?
		public function log_activation($bool=false){$this->log_activation = $bool;} 
	private $log_session=false; // Delete old Session Entries?
		public function log_session($bool=false){$this->log_session = $bool;} 
	private $log_recover=false; // Delete old Recover Entries?
		public function log_recover($bool=false){$this->log_recover = $bool;} 
	private $log_mail_edit=false; // Delete old Mail Change Entries?
		public function log_mail_edit($bool=false){$this->log_mail_edit = $bool;} 
	## Interval Between new Requests
	private $wait_activation_min = 6; // Wait Minutes before new Activation Request
		public function wait_activation_min($int = 6){$this->wait_activation_min = $int;} 
	private $wait_recover_min = 6; // Wait Minutes before new Recover Request
		public function wait_recover_min($int = 6){$this->wait_recover_min = $int;} 
	private $wait_mail_edit_min = 6; // Wait Minutes before new Mail Change Request
		public function wait_mail_edit_min($int = 6){$this->wait_mail_edit_min = $int;} 
	## Token Expire Hours
	private $min_activation = 6; // Token Valid Length in Minutes for Activation
		public function min_activation($int = 6){$this->min_activation = $int;} 
	private $min_recover = 6; // Token Valid Length in Minutes for Recover
		public function min_recover($int = 6){$this->min_recover = $int;} 
	private $min_mail_edit = 6;  // Token Valid Length in Minutes for Mail Change
		public function min_mail_edit($int = 6){$this->min_mail_edit = $int;}
	## Auto-Block user after X tries?
	private $autoblock = false;  // Activate Autoblock after X fail Logins?
		public function autoblock($int = false) { $this->autoblock = $int; } 		
	## Sessions Setup
	private $sessions = "x_users"; private $sessions_days = 7; 
		public function sessions_days($int = 7){$this->sessions_days = $int;} // Set Max Session Use Days	
	## Cookie Setup		
	private $cookies = true;private $cookies_use = false; // Allow Cookies Use in General
		public function cookies_use($bool = true){$this->cookies_use = $bool;$this->cookies = $this->sessions;} 
	private $cookies_days = 7; // Max Cookie Lifetime in Days	
		public function cookies_days($int = 7){$this->cookies_days = $int;} 	
	## Setup Token Generation
	private $token_charset = "0123456789";  // Setup General Token Charset
		public function token_charset($charset = "0123456789") { $this->token_charset = $charset; }
	private $token_length = 24; // Setup General Token Length
		public function token_length($length = 24) { $this->token_length = $length; } 
		private function token_gen() { return $this->password_gen($this->token_length, $this->token_charset); }
	private $session_charset = "0123456789";   // Setup Session Token Charset
		public function session_charset($charset = "0123456789") { $this->session_charset = $charset; }
	private $session_length = 24;  // Setup Session Token Length	
		public function session_length($length = 24) { $this->session_length = $length; }	 
		private function session_gen() { return $this->password_gen($this->session_length, $this->session_charset); }
	## Edit User Table Fields
	public function user_add_field($fieldstring) { $x = $this->mysql->log_status(); $this->mysql->log_disable(); $y =   $this->mysql->query("ALTER TABLE `".$this->dt_users."` ADD COLUMN ".$fieldstring." ;");  if($x) {$this->mysql->log_enable();} return $y; }
	public function user_del_field($fieldname) { $x = $this->mysql->log_status(); $this->mysql->log_disable(); $y =   $this->mysql->query("ALTER TABLE `".$this->dt_users."` DROP COLUMN ".$fieldname." ;"); $this->log_disable(); if($x) {$this->mysql->log_enable();} return $y; }
	## Groups Setup and Table Creation
	public function groups($table_group, $table_group_link) {
		$this->table_group = $table_group;
		$this->table_group_link = $table_group_link;
		$this->groups_createtable();}	
	public function groups_add_field($fieldstring) { $x = $this->mysql->log_status(); $this->mysql->log_disable(); $y =   $this->mysql->query("ALTER TABLE `".$this->table_group."` ADD COLUMN ".$fieldstring." ;");  if($x) {$this->mysql->log_enable();} return $y;}
	public function groups_del_field($fieldname) { $x = $this->mysql->log_status(); $this->mysql->log_disable(); $y =   $this->mysql->query("ALTER TABLE `".$this->table_group."` DROP COLUMN ".$fieldname." ;"); if($x) {$this->mysql->log_enable();} return $y;}		
	private function groups_createtable() {
		if(!$this->mysql->table_exists($this->table_group_link)) {
			$this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table_group_link."` (
							`id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique Link ID',
							`fk_user` int(10) NOT NULL COMMENT 'Related User ID',
							`fk_group` int(10) NOT NULL COMMENT 'Related Group ID',
							`creation` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
							PRIMARY KEY (`id`), CONSTRAINT x_class_user_glink UNIQUE (`fk_user`,`fk_group`));");}
		if(!$this->mysql->table_exists($this->table_group)) {
			$this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table_group."` (
							`id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique Group ID',
							`group_name` varchar(255) NOT NULL COMMENT 'Group Name',
							`group_description` TEXT DEFAULT NULL COMMENT 'Group Description',
							`creation` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
							`modification` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
							PRIMARY KEY (`id`));");}}	
	## Group Functions
	public function group_add($name, $description = "") {$bind[0]["value"] = trim($name); $bind[0]["type"] = "s";$bind[1]["value"] = trim($description); $bind[1]["type"] = "s";$this->mysql->query("INSERT INTO ".$this->table_group."(group_name, group_description) VALUES(?, ?)", $bind); return true;}
	public function group_del($id) { if(is_numeric($id)) { $this->mysql->query("DELETE FROM `".$this->table_group."` WHERE id = ".$id.""); $this->mysql->query("DELETE FROM `".$this->table_group_link."` WHERE fk_group = ".$id."");} return true; }
	public function group_users($groupid) {if(is_numeric($groupid)) {return $this->mysql->select("SELECT * FROM `".$this->table_group_link."` WHERE fk_group = ".$groupid."", true); return true;} return false;}
	public function user_groups($userid) {if(is_numeric($userid)) {return $this->mysql->select("SELECT * FROM `".$this->table_group_link."` WHERE fk_user = ".$userid."", true); return true;} return false;}
	public function group_adduser($groupid, $userid) {if(is_numeric($groupid) AND is_numeric($userid)) {return $this->mysql->query("INSERT INTO ".$this->table_group_link."(fk_group, fk_user)VALUES($groupid, $userid)"); return true;} return true;}
	public function group_deluser($groupid, $userid) {if(is_numeric($groupid) AND is_numeric($userid)) {return $this->mysql->query("DELETE FROM `".$this->table_group_link."` WHERE fk_group = ".$groupid." AND fk_user = ".$userid."");} return true;}
	## Extrafields Setup
	public function extrafields($table_ext) {$this->table_ext = $table_group;$this->ext_createtable();}	
	private function ext_createtable() {
		if(!$this->mysql->table_exists($this->table_ext)) {
			$this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->table_ext."` (
							`id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique Extrafield ID',
							`fk_user` int(10) NOT NULL COMMENT 'User Relation',
							`creation` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
							`modification` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
							PRIMARY KEY (`id`));");	}}	
	public function extrafield_add_field($string) { $x = $this->mysql->log_status(); $this->mysql->log_disable(); $y =   $this->mysql->query("ALTER TABLE `".$this->table_ext."` ADD COLUMN ".$string." ;"); if($x) {$this->mysql->log_enable();}return $y; }
	public function extrafield_del_field($fieldname) { $x = $this->mysql->log_status(); $this->mysql->log_disable(); $y =  $this->mysql->query("ALTER TABLE `".$this->table_ext."` DROP COLUMN ".$fieldname." ;"); if($x) {$this->mysql->log_enable();} return $y; }	
	## Passfilter
	private $passfilter = false; private $passfilter_signs = 0; private $passfilter_capital = 0; 
	private $passfilter_small = 0; private $passfilter_special = 0; private $passfilter_number = 0;
	public function passfilter($signs = 6, $capitals = 0, $small = 0, $special = 0, $number = 0) {$this->passfilter_signs = $signs;$this->passfilter_capital = $capitals;$this->passfilter_small = $small;$this->passfilter_special = $special;$this->passfilter_number = $number;}
	public function passfilter_check($passclear) { $isvalid = true;
		if($this->passfilter_signs > 0) { if(@strlen(@$passclear) < $this->passfilter_signs) { $isvalid = false; } }
		if($this->passfilter_capital > 0) { if(preg_match('/[A-Z]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_capital) { $isvalid = false; } } else { $isvalid = false; } }
		if($this->passfilter_small > 0) { if(preg_match('/[a-z]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_small) {  $isvalid = false; } } else { $isvalid = false; } }
		if($this->passfilter_special > 0) { if(preg_match('/[\'^£$%&*()}{@#~?><>,|=_+-]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_special) {  $isvalid = false; } } else { $isvalid = false; } }
		if($this->passfilter_number > 0) { if(preg_match('/[0-9]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_number) {  $isvalid = false; } } else { $isvalid = false; } }
		return $isvalid;}	
	
	/* 	.___        __                             .__   
		|   | _____/  |_  ___________  ____ _____  |  |  
		|   |/    \   __\/ __ \_  __ \/    \\__  \ |  |  
		|   |   |  \  | \  ___/|  | \/   |  \/ __ \|  |__
		|___|___|  /__|  \___  >__|  |___|  (____  /____/
				 \/          \/           \/     \/      Private Functions only used internaly in this class*/
	## Cookies Functions
	private function cookie_set($id, $key){if($this->cookies_use){setcookie($this->cookies."session_userid", $id, time() + $this->cookies_days * 24 * 60 * 60);setcookie($this->cookies."session_key", $key, time() + $this->cookies_days * 24 * 60 * 60);} return true;}
	private function cookie_unset(){if($this->cookies_use){unset($_COOKIE[$this->cookies.'session_key']);@setcookie($this->cookies.'session_key', '', time() - 3600, '/');unset($_COOKIE[$this->cookies.'session_userid']);@setcookie($this->cookies.'session_userid', '', time() - 3600, '/');} return true;}	
	private function cookie_restore(){if($this->cookies_use){if(@is_numeric($_COOKIE[$this->cookies."session_userid"]) OR @isset($_COOKIE[$this->cookies."session_key"])){if(@$this->session_token_valid(@$_COOKIE[$this->sessions."session_userid"], @$_COOKIE[$this->sessions."session_key"])){@$_SESSION[$this->sessions."x_users_stay"] = true;@$_SESSION[$this->sessions."x_users_key"] = @$_COOKIE[$this->sessions."session_key"];@$_SESSION[$this->sessions."x_users_id"] = @$_COOKIE[$this->sessions."session_userid"];@$_SESSION[$this->sessions."x_users_ip"] = @$_SERVER["REMOTE_ADDR"];$this->session_restore();return true;}else{$this->cookie_unset();return false;}}return false;}return true;}			
	## Check Time Interval Function	
	private function check_interval_value($datetimeref, $strstring) { $new = strtotime($datetimeref) - strtotime($strstring); return $new;}
	### Get ID For Operation Functions
	private function int_opid($id) { if($this->user_loggedIn AND !$id) { return $this->user_id; } elseif(!$this->user_loggedIn AND !$id) { return false; } elseif($this->exists($id)) { return $id; } return false; }	
	private function check_interval($datetimeref, $strstring) { if (strtotime($datetimeref) < strtotime($strstring)) {return false;} return true;}	
	private function check_intervalx($datetimeref, $strstring) {return strtotime($datetimeref)."-".$strstring."-".strtotime($strstring); if (strtotime($datetimeref) < strtotime($strstring)) {return false;} return true;}	
	## Token Creations and Singing
	private function activation_token_create($user, $token) { return $this->token_create($user, $token, $this->key_activation); }
	private function recover_token_create($user, $token) { return $this->token_create($user, $token, $this->key_recover); }
	private function mail_edit_token_create($user, $token) { return $this->token_create($user, $token, $this->key_mail_edit); }
	private function session_token_create($user, $token) { return $this->token_create($user, $token, $this->key_session); }
	private function token_create($user, $token, $type) {
		if(is_numeric($user) AND isset($token) AND is_numeric($type)){
			$bind[0]["value"] = $token;
			$bind[0]["type"] = "s";
			if($this->key_mail_edit == $type) {
				$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE key_type = '".$this->key_mail_edit."' AND fk_user = '".$user."'");
				if(!$this->log_mail_edit) {
					$this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_mail_edit."' AND fk_user = '".$user."'");
				} 		
				if($this->log_ip) {$thenewip = @$_SERVER["REMOTE_ADDR"];} else {$thenewip = "hidden";}
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip) VALUES('".$user."', '".$this->key_mail_edit."', ?, '1', '".$this->mysql->escape($thenewip)."')", $bind);
				return true;
			} elseif($this->key_session == $type) {
				if(!$this->multi_login) {
					$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE key_type = '".$this->key_session."' AND fk_user = '".$user."'");
					if(!$this->log_session) {
						$this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_session."' AND fk_user = '".$user."'");
					} 
				} else {
					if(!$this->log_session) {
						$this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_session."' AND fk_user = '".$user."' AND is_active = 0");
					} 
				}
				if($this->log_ip) { $thenewip = @$_SERVER["REMOTE_ADDR"]; } else { $thenewip = "hidden"; }
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip, refresh_date) VALUES('".$user."', '".$this->key_session."', ?, '1', '".$this->mysql->escape($thenewip)."', CURRENT_TIMESTAMP())", $bind);
				return true;
			} elseif($this->key_recover == $type) {
				$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE key_type = '".$this->key_recover."' AND fk_user = '".$user."'");
				if(!$this->log_recover) {
					$this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_recover."' AND fk_user = '".$user."'");
				} 		
				if($this->log_ip) {$thenewip = @$_SERVER["REMOTE_ADDR"];} else {$thenewip = "hidden";}
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip) VALUES('".$user."', '".$this->key_recover."', ?, '1', '".$this->mysql->escape($thenewip)."')", $bind);
				return true;
			} elseif($this->key_activation == $type) {
				$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE key_type = '".$this->key_activation."' AND fk_user = '".$user."'");
				if(!$this->log_activation) {
					$this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_activation."' AND fk_user = '".$user."'");
				} 		
				if($this->log_ip) {$thenewip = @$_SERVER["REMOTE_ADDR"];} else {$thenewip = "hidden";}
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip) VALUES('".$user."', '".$this->key_activation."', ?, '1', '".$this->mysql->escape($thenewip)."')", $bind);
				return true;
			}
		} return false;}	
	## Session Function to Logout A Session
	private function session_logout() {
		if(!is_numeric($_SESSION[$this->sessions."x_users_id"])) { return false; }
		if($this->multi_login) { $ext = "AND session_key = '".$this->mysql->escape($_SESSION[$this->sessions."x_users_key"])."' "; } else {$ext = " ";}
		if(!$this->log_session) {$this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_session."' AND fk_user = '".@$_SESSION[$this->sessions."x_users_id"]."' ".$ext);
		} else { $this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE key_type = '".$this->key_session."' AND fk_user = '".@$_SESSION[$this->sessions."x_users_id"]."' AND is_active = 1 ".$ext); } }
	## Session Function to Restore
	private function session_restore(){
		if(is_numeric($_SESSION[$this->sessions."x_users_id"])) {
				/////// SHADOW LOGIN EXTENSIONS
				if(!is_numeric(@$_SESSION[$this->sessions."x_users_login_shadow"])) { 
					$checkuser = $_SESSION[$this->sessions."x_users_id"]; 
				} else { 
				if($this->exists($_SESSION[$this->sessions."x_users_id"])) { 
					$checkuser = $_SESSION[$this->sessions."x_users_login_shadow"]; 
					} else { 
					$_SESSION[$this->sessions."x_users_id"] = $_SESSION[$this->sessions."x_users_login_shadow"];
					$_SESSION[$this->sessions."x_users_login_shadow"] = false;
					$checkuser = $_SESSION[$this->sessions."x_users_id"]; } 
				} 
				
			$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE user_confirmed = '1' AND user_blocked <> 1 AND id = '".$checkuser."'");
			if($cr = $this->mysql->fetch_array($r)){
				$this->object_user_set($cr["id"]);
				$this->mysql->query("UPDATE `".$this->dt_keys."` SET refresh_date = CURRENT_TIMESTAMP() WHERE fk_user = '".$cr["id"]."' AND session_key = \"".$this->mysql->escape(@$_SESSION[$this->sessions."x_users_key"])."\" AND is_active = 1 AND key_type = '".$this->key_session."'"); 
				return true;
			} else {
				$this->object_user_unset();
				$this->cookie_unset();
				return false;
			}
		} else {
			$this->object_user_unset();
			$this->cookie_unset();
			return false;		
		}}
	## User Unset All Data
	private function object_user_unset() {
		$this->internal_ref_reset();
		unset($_SESSION[$this->sessions."x_users_ip"]);
		unset($_SESSION[$this->sessions."x_users_key"]);
		unset($_SESSION[$this->sessions."x_users_id"]);
		unset($_SESSION[$this->sessions."x_users_stay"]);
		unset($_SESSION[$this->sessions."x_users_login_shadow"]);
		$this->user_rank = false; $this->rank = false;
		$this->user_id = false; $this->id = false;
		$this->user_theme = false; $this->theme = false;
		$this->user_lang = false; $this->lang = false;
		$this->user_name = false; $this->name = false;
		$this->user_mail = false; $this->mail = false;
		$this->loggedIn = false; $this->loggedin = false;
		$this->user_loggedIn = false; $this->user_loggedin = false;
		$this->user = false;}	
	## Restore
	private function object_user_set($userid) {
		if(!$this->exists($userid)) { return false; }
		$tmp = $this->get($userid);
		$tmp["x_users_key"]		=	@$_SESSION[$this->sessions."x_users_key"];
		$tmp["x_users_stay"]	=	@$_SESSION[$this->sessions."x_users_stay"];
		$tmp["x_users_ip"]		=	@$_SESSION[$this->sessions."x_users_ip"];
		$tmp["x_users_id"]		=	@$userid;
		$tmp["loggedIn"] = true;				$tmp["loggedin"]		=	true;
		$tmp["user_loggedIn"] = true;			$tmp["user_loggedin"] = true;
		$this->user_rank = $tmp["user_rank"]; 	$this->rank = $tmp["user_rank"];
		$this->user_id = $userid; 				$this->id = $userid;
		$this->user_lang = @$tmp["user_lang"]; 	$this->lang = @$tmp["user_lang"];
		$this->user_theme = @$tmp["user_theme"]; $this->theme = @$tmp["user_theme"];
		$this->user_name = $tmp["user_name"]; 	$this->name = $tmp["user_name"];
		$this->user_mail = $tmp["user_mail"]; 	$this->mail = $tmp["user_mail"];
		$this->loggedIn = true; $this->loggedin = true;
		$this->user_loggedIn = true; $this->user_loggedin = true;
		$this->user = $tmp;}
		
	/*      						  __                        __   
		  ____  ____   ____   _______/  |________ __ __   _____/  |_ 
		_/ ___\/  _ \ /    \ /  ___/\   __\_  __ \  |  \_/ ___\   __\
		\  \__(  <_> )   |  \\___ \  |  |  |  | \/  |  /\  \___|  |  
		 \___  >____/|___|  /____  > |__|  |__|  |____/  \___  >__|  
			 \/           \/     \/                          \/     Constructor Function  for this class */
	function __construct($mysqlcon, $table_users, $table_sessions, $preecokie = "x_users_", $initial_ref = false, $initial_pass = false, $initial_rank = false) {
		// Init Variables for Runtime
		$this->sessions 		=   $preecokie;	
		$this->mysql			=	$mysqlcon;
		$this->dt_users			=	$table_users;
		$this->dt_keys			=	$table_sessions;
		if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
		if(!$this->mysql->table_exists($table_users)) { $this->create_table($initial_ref, $initial_pass, $initial_rank); $this->mysql->free_all();  }
		if(!$this->mysql->table_exists($table_sessions)) { $this->create_table(); $this->mysql->free_all();  }}
	private function create_table($initial = false, $initialpass = "changeme", $initialrank = 0) {
		$this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->dt_users."` (
										  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
										  `user_name` varchar(512) DEFAULT 'undefined' COMMENT 'Users Name for Login if Ref',
										  `user_pass` varchar(512) DEFAULT NULL COMMENT 'Users Pass for Login',
										  `user_mail` varchar(512) NULL COMMENT 'Users Mail for Login if Ref',
										  `user_shadow` varchar(512) DEFAULT NULL COMMENT 'Users Store for Mail if Renew',
										  `user_rank` int(9) NULL DEFAULT NULL COMMENT 'Users Rank',
										  `user_confirmed` tinyint(1) DEFAULT '0' COMMENT 'User Activation Status',
										  `user_blocked` tinyint(1) DEFAULT '0' COMMENT 'User Blocked/Disabled Status',
										  `created_date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date',
										  `modify_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
										  `last_reset` datetime DEFAULT NULL COMMENT 'Reset Date Counter for new Requests',
										  `last_activation` datetime DEFAULT NULL COMMENT 'Activation Date Counter for new Requests',
										  `last_mail_edit` datetime DEFAULT NULL COMMENT 'Last Mail Change Request Date',
										  `last_block` datetime DEFAULT NULL COMMENT 'Block Date for this user',
										  `last_login` datetime DEFAULT NULL COMMENT 'Last Login Date',
										  `user_lang` varchar(24) DEFAULT NULL COMMENT 'User Default Language',
										  `user_theme` varchar(24) DEFAULT NULL COMMENT 'User Default Theme',
										  `req_reset` datetime DEFAULT NULL COMMENT 'Reset Date Counter for new Requests',
										  `req_activation` datetime DEFAULT NULL COMMENT 'Activation Date Counter for new Requests',
										  `req_mail_edit` datetime DEFAULT NULL COMMENT 'Last Mail Change Request Date',
										  `block_reset` int(1) DEFAULT NULL COMMENT 'Block Resets for this user',
										  `block_auto` int(1) DEFAULT 0 COMMENT 'Has this user been automatically blocked?',
										  `block_activation` int(1) DEFAULT NULL COMMENT 'Block Activation for this User',
										  `block_mail_edit` datetime DEFAULT NULL COMMENT 'Block Mail Edits for this User',
										  `fails_in_a_row` int(10) DEFAULT 1 COMMENT 'Fail Pass Enters without Success Login',
										  `user_disabled` int(1) DEFAULT 0 COMMENT '1 - User is Disabled',
										  `extradata` TEXT DEFAULT NULL COMMENT 'Additional Data',
										  PRIMARY KEY (`id`));");
		$this->mysql->query("CREATE TABLE IF NOT EXISTS `".$this->dt_keys."` (
										`id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Unique Session ID',
										`fk_user` int(10) NOT NULL COMMENT 'Related User ID',
										`key_type` tinyint(1) NULL DEFAULT '0' COMMENT ' 1 - activate 2 - session 3 - recover 4 - mailchange',
										`creation` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Date for max Session Days',
										`modification` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Modification Date',
										`refresh_date` datetime DEFAULT NULL COMMENT 'Last Use Date set by session_restore!',
										`session_key` varchar(128) NULL COMMENT 'Session Authentification Token and Key!',
										`is_active` tinyint(1) DEFAULT '0' COMMENT '1 - Active 0 - Expired!',
										`request_ip` varchar(128) DEFAULT NULL COMMENT 'Requested IP if enabled set at creation!',
										`execute_ip` varchar(128) DEFAULT NULL COMMENT 'Executed IP if enabled set at Invalidation!',
										PRIMARY KEY (`id`));");
		if($initial AND $initialpass AND is_numeric($initialrank)) {
			$bind[0]["type"] = "s";
			$bind[0]["value"] = $initial;
			$bind[1]["type"] = "s";
			$bind[1]["value"] = $initial;
			$bind[2]["type"] = "s";
			$bind[2]["value"] = $this->password_crypt($initialpass);
			$this->mysql->query("INSERT INTO `".$this->dt_users."` (user_name, user_mail, user_confirmed, user_pass, user_rank)
									VALUES(?, ?, 1, ?, '".$initialrank."');", $bind);}}	
									
	/*	___________    ______ ______ ____   ____   ____  
		\____ \__  \  /  ___//  ___// ___\_/ __ \ /    \ 
		|  |_> > __ \_\___ \ \___ \/ /_/  >  ___/|   |  \
		|   __(____  /____  >____  >___  / \___  >___|  /
		|__|       \/     \/     \/_____/      \/     \/ 	Password generation functions */
	public function password_gen($len = 12, $comb = "abcde12345"){$pass = array(); $combLen = strlen($comb) - 1; for ($i = 0; $i < $len; $i++) { $n = mt_rand(0, $combLen); $pass[] = $comb[$n]; } return implode($pass);}			
	public function password_crypt($var, $hash = PASSWORD_BCRYPT) { return password_hash($var,$hash); }
	public function password_check($cleartext, $crypted) { return password_verify($cleartext,$crypted); }			
	
	/*								   __  .__               
		  ____ ______   ________________ _/  |_|__| ____   ____  
		 /  _ \\____ \_/ __ \_  __ \__  \\   __\  |/  _ \ /    \ 
		(  <_> )  |_> >  ___/|  | \// __ \|  | |  (  <_> )   |  \
		 \____/|   __/ \___  >__|  (____  /__| |__|\____/|___|  /
			   |__|        \/           \/                    \/ 	Operation User Based Functions  */
	# Get Array with user Table Fields
	public function get($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} $r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");if($x=$this->mysql->fetch_array($r)){return $x;} return false; }	
	# Exists User
	public function exists($id){if(!is_numeric($id)){return false;}$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}
	# Delete User
	public function delete($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = '".$id."'"); return $this->mysql->query("DELETE FROM `".$this->dt_users."` WHERE id = '".$id."'");}		
	## Session Change for User
	public function disable_user_session($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}return $this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE key_type = '".$this->key_session."' AND fk_user = '".$id."'");}
	public function delete_user_session($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}return $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE '".$this->key_session."' AND fk_user = '".$id."'");}	
	## Logout all Users
	public function logout_all(){return $this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE key_type = '".$this->key_session."'");}		
	## Confirmation
	public function confirmed_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");if($x = $this->mysql->fetch_array($r)){if($x["user_confirmed"] == 1){return true;}}}return false;}		
	## Add a User
	public function add_user($nameref, $mail, $password = false, $rank = false, $activated = false){ return $this->addUser($nameref, $mail, $password, $rank, $activated);}
	public function addUser($nameref, $mail, $password = false, $rank = false, $activated = false) { 
		// If Reference Exists than false
			// Mail or other ref?
			if($this->login_field == "user_mail") { $ref = $mail; } else { $ref = $nameref;  }
			$bind[0]["value"] = strtolower(trim($ref)); $bind[0]["type"] = "s";
			// Find Ref
			$r = $this->mysql->select("SELECT * FROM `".$this->dt_users."` WHERE LOWER(".$this->login_field.") = ? AND user_confirmed = 1", false, $bind);
			// User Exists Active with Ref
			if(is_array($r)){ return false;   }  		
		// Prepare Activated
		if(!$activated) {$activated = 0;} else {$activated = 1;}		
		// Prepare Rank
		if(!$rank) {$rank = 0;} else {$rank = $rank;}		
		// Prepare Password
		if(!$password OR trim($password) == "") {$password = "NULL";} else {$password = $this->password_crypt($password);}		
		//Delete Other Unconfirmed
		$this->mysql->query("DELETE FROM `".$this->dt_users."` WHERE user_confirmed = 0  AND LOWER(".$this->login_field.") = ?", $bind);
		$this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = NULL WHERE LOWER(user_shadow) = ?", $bind);
		//Query
		$bind[0]["value"] = trim($nameref); $bind[0]["type"] = "s";
		$bind[1]["value"] = trim($mail); $bind[1]["type"] = "s";
		$this->mysql->query("INSERT INTO ".$this->dt_users."(user_name, user_mail, user_pass, user_rank, user_confirmed)
		VALUES(?, ?, '". $password."', '".$rank."', '".$activated."')", $bind);	
		return true;}	
	## Block
	public function blocked_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)) {$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");if($x = $this->mysql->fetch_array($r)){if($x["user_blocked"] != 1){return false;}}}return true;}
	public function block_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){$this->mysql->query("UPDATE `".$this->dt_users."` SET last_block = CURRENT_TIMESTAMP() WHERE id = '".$id."'"); return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_blocked = 1, block_auto = 0 WHERE id = '".$id."'");}return false;}		
	public function unblock_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_blocked = 0, block_auto = 0 WHERE id = '".$id."'");}return false;}		
	## Block
	public function disabled_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)) {$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");if($x = $this->mysql->fetch_array($r)){if($x["user_disabled"] != 1){return false;}}}return true;}
	public function disable_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){ return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_disabled = 1 WHERE id = '".$id."'");}return false;}		
	public function enable_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_disabled = 0 WHERE id = '".$id."'");}return false;}	
	## Change User Password
	public function change_pass($id = false, $new = false){ return $this->changeUserPass($id, $new);}
	public function change_password($id = false, $new =false){ return $this->changeUserPass($id, $new);}
	public function changeUserPass($id = false, $new = false){if(!$new) { return false;} if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}
		$bind[0]["type"] = "s";
		$bind[0]["value"] = $this->password_crypt($new);
		if(is_numeric($id) AND isset($new)){return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_pass = ? WHERE id = '".$id."'", $bind);
		}return false;}	
	## Change User Rank
	public function change_rank($id = false, $new = false){ if(!$new) { return false;} if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} if(is_numeric($id) AND is_numeric($new)){return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_rank = '".$new."' WHERE id = '".$id."'");}return false;}
	## Change a Username
	public function change_name($id = false, $new = false){  return $this->changeUserName($id , $new);}	
	public function changeUserName($id = false, $new = false){ if(!$new) { return false;}  $bind[0]["value"] = trim($new);$bind[0]["type"] = "s";
		if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}
		if(strlen(trim($new)) > 0) {} else { return false; }
		if(!$this->user_unique){
			return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_name = ? WHERE id = '".$id."'", $bind);
		} else {
			$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");
			if($rrx = $this->mysql->fetch_array($r)){
				if(trim(strtolower($rrx["user_name"])) == trim(strtolower($new))) {return true;} }
		  if($this->usernameExistsActive($new)){return false;}else{$this->mysql->query("UPDATE `".$this->dt_users."` SET user_name = ? WHERE id = '".$id."'", $bind); return true;}}return false;}		
	## Change Users Shadow Mail
	public function change_shadow($id = false, $new = false){ return $this->changeUserShadowMail($id , $new);}	
	public function changeUserShadowMail($id = false, $new = false){ if(!$new) { return false;}  $bind[0]["value"] = trim($new);$bind[0]["type"] = "s";
		if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}
		if(strlen(trim($new)) > 0) { } else { return false; }
		if (!$this->mail_unique) { 
			$this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = ? WHERE id = '".$id."'", $bind);
			return true;
		}else{ 
			if($this->mailExistsActive($new)){return false;}else{$this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = ? WHERE id = '".$id."'", $bind); return true;}}
		return false;}		
	## Change Users Mail	
	public function change_mail($id = false, $new = false){ return $this->changeUserMail($id , $new);}	
	public function changeUserMail($id = false, $new = false)  { if(!$new) { return false;}  $bind[0]["value"] = trim($new);$bind[0]["type"] = "s";	
		if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}
		if(strlen(trim($new)) > 0) {} else { return false; }
		if (!$this->mail_unique) {
			return $this->mysql->query("UPDATE `".$this->dt_users."` SET user_mail = ? WHERE id = '".$id."'", $bind);	
		} else { 
			$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");
			if($rrx = $this->mysql->fetch_array($r)){ if(trim(strtolower($rrx["user_mail"])) == trim(strtolower($new))) {return true;}}
			if($this->mailExistsActive($new)) {return false;} else {
				$this->mysql->query("DELETE FROM `".$this->dt_users."` WHERE user_confirmed = 0 AND LOWER(user_mail) = '".$this->mysql->escape(strtolower(trim($new)))."'");
				$this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = NULL WHERE LOWER(user_shadow) = '".$this->mysql->escape(strtolower(trim($new)))."'");
				$this->mysql->query("UPDATE `".$this->dt_users."` SET user_mail = ? WHERE id = '".$id."'", $bind);
				return  true;}
		}return false;}	
	## Check if Ref Exists
	public function ref_exists($ref){ return $this->refExists($ref); }	
	public function refExists($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE LOWER(".$this->login_field.") = ?", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}	
	public function ref_exists_active($ref){ return $this->refExistsActive($ref); }
	public function refExistsActive($ref) {$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE LOWER(".$this->login_field.") = ? AND user_confirmed = 1", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}	
	## Check if Username Exists
	public function username_exists($ref){ return $this->usernameExists($ref); }
	public function usernameExists($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE LOWER(user_name) = ?", $bind);if($rrx=$this->mysql->fetch_array($r)){return true;}return false;}	
	public function username_exists_active($ref){ return $this->usernameExistsActive($ref); }
	public function usernameExistsActive($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE LOWER(user_name) = ? AND user_confirmed = 1", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}	
	## Check if Mail Exists
	public function mail_exists($ref){ return $this->mailExists($ref); }
	public function mailExists($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE LOWER(user_mail) = ?", $bind);if($rrx=$this->mysql->fetch_array($r)){return true;}return false;}	
	public function mail_exists_active($ref){ return $this->mailExistsActive($ref); }
	public function mailExistsActive($ref) {$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE LOWER(user_mail) = ? AND user_confirmed = 1", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}	
	# Extradata User Array Field Modifications
	public function get_extra($id = false) {if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} if(is_numeric($id)) {$ar = $this->mysql->select("SELECT * FROM `".$this->dt_users."` WHERE id = '".$id."'");if(is_array($ar)) {return unserialize($ar["extradata"]);} return false;} return false;}
	public function set_extra($id = false, $array = false) { if(!$array) { return false;} if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} if(is_numeric($id) AND is_array($array)) {$bind[0]["type"] = "s";$bind[0]["value"] = serialize($array);return $this->mysql->query("UPDATE `".$this->dt_users."` SET extradata = ? WHERE id = '".$id."'", $bind);} return false;}	
	## Login as another user
	public function login_as($id) {
		// Only Logged in Users can Log In as Another User!
		if($this->user_loggedin != true) { return false; }
		// Do not allow Login as same user
		if($this->user_id == trim($id)) { return false; }
		// Already Shadow Logged in as another User?
		if(is_numeric($_SESSION[$this->sessions."x_users_login_shadow"])) { return false; }
		// Does the user exist?
		if(!$this->exists($id)) { return false; }
		// Loggin as the other User
		$_SESSION[$this->sessions."x_users_login_shadow"] = $this->user_id;
		$_SESSION[$this->sessions."x_users_id"] = $id;}
	// Check if Logged In As Another User
	public function login_as_is() {
		if(is_numeric($_SESSION[$this->sessions."x_users_login_shadow"])) { return true;
		} else { return false; } }
	// Go back to old User State
	public function login_as_return() {
		// If no shadow Login do nothing
		if(!is_numeric($_SESSION[$this->sessions."x_users_login_shadow"])) { return false; }
		// Remove Login
		$_SESSION[$this->sessions."x_users_id"] = $_SESSION[$this->sessions."x_users_login_shadow"];
		$_SESSION[$this->sessions."x_users_login_shadow"] = false; }	
		
	/*	___________     __                   ________ __________       
		\__    ___/___ |  | __ ____   ____   \_____  \\______   \______
		  |    | /  _ \|  |/ // __ \ /    \   /   |   \|     ___/  ___/
		  |    |(  <_> )    <\  ___/|   |  \ /    |    \    |   \___ \ 
		  |____| \____/|__|_ \\___  >___|  / \_______  /____|  /____  >
							\/    \/     \/          \/             \/   */
	## Get Rrequest Interval Functions
	public function activation_request_time($user) { return $this->request_time($user, $this->key_activation); }
	public function recover_request_time($user) { return $this->request_time($user, $this->key_recover); }
	public function mail_edit_request_time($user) { return $this->request_time($user, $this->key_mail_edit); }
	private function request_time($user, $type) {
		if(is_numeric($user) AND is_numeric($type)){
			if($this->key_mail_edit == $type) { 
				$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$user."'");
				if($res=$this->mysql->fetch_array($r)){
					if(is_numeric($this->wait_mail_edit_min)) {
						if(isset($res["req_mail_edit"])) { return $this->check_interval_value($res["req_mail_edit"], '-'.$this->wait_mail_edit_min.' minutes');}
					} else { return 99999; }
				} 			
			} elseif($this->key_recover == $type) {  
				$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$user."'");
				if($res=$this->mysql->fetch_array($r)){
					if(is_numeric($this->wait_recover_min)) { 
						if(isset($res["req_reset"])) { return $this->check_interval_value($res["req_reset"], '-'.$this->wait_recover_min.' minutes');}
					} else { return 99999;}
				} 					
			} elseif($this->key_activation == $type) { 
				$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE id = '".$user."'");
				if($res=$this->mysql->fetch_array($r)){
					if(is_numeric($this->wait_activation_min)) {
						if(isset($res["req_activation"])) { return $this->check_interval_value($res["req_activation"], '-'.$this->wait_activation_min.' minutes');}
					} else { return 99999;}
				}					
			}  
		return false;} return false; }
	## Token Validation Functions	
	public function activation_token_valid($user, $token) { return $this->token_valid($user, $token, $this->key_activation); }
	public function recover_token_valid($user, $token) { return $this->token_valid($user, $token, $this->key_recover); }
	public function mail_edit_token_valid($user, $token) { return $this->token_valid($user, $token, $this->key_mail_edit); }
	public function session_token_valid($user, $token) { return $this->token_valid($user, $token, $this->key_session); }
	private function token_valid($user, $token, $type) {
		if(is_numeric($user) AND isset($token) AND is_numeric($type)){
			$bind[0]["value"] = $token;
			$bind[0]["type"] = "s";
			if($this->key_mail_edit == $type) {
				$r = $this->mysql->query("SELECT * FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_mail_edit."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return false; }
					if(is_numeric($this->min_mail_edit)) {
						if(isset($res["creation"])) { if(!$this->check_interval($res["creation"], '- '.$this->min_mail_edit.' minutes')) {return false;} }
					}					
					return true;
				} else {return false;}					
			} elseif($this->key_session == $type) {
				$r = $this->mysql->query("SELECT * FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_session."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return false; }
					if(is_numeric($this->sessions_days)) {
						 if(isset($res["creation"])) {
							if ($this->check_interval($res["creation"],''.$this->sessions_days.' days')) {
								if($this->log_session) { $this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE id = '".$res["id"]."'"); }
								else { $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE id = '".$res["id"]."'"); }
								return false;
							}
						}
					}
					return true;
				} else {return false;}						
			} elseif($this->key_recover == $type) {
				$r = $this->mysql->query("SELECT * FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_recover."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return false; }
					if(is_numeric($this->min_recover)) {
						if(isset($res["creation"])) { if (!$this->check_interval($res["creation"],'-'.$this->min_recover.' minutes')) {					
							 return false;
						}}
					}					
					return true;
				} else { return false;}						
			} elseif($this->key_activation == $type) {
				$r = $this->mysql->query("SELECT * FROM `".$this->dt_keys."` WHERE key_type = '".$this->key_activation."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return false; }
					if(is_numeric($this->min_activation)) {
						if(isset($res["creation"])) { if (!$this->check_interval($res["creation"],'-'.$this->min_activation.' minutes')) {					
							return false;
						}}
					}					
					return true;
				} else {return false;}						
			}
		} return false;}	
		
	######################################################################################################################################################
	/* . ____                 .__        
		|    |    ____   ____ |__| ____  
		|    |   /  _ \ / ___\|  |/    \ 
		|    |__(  <_> ) /_/  >  |   |  \
		|_______ \____/\___  /|__|___|  /
				\/    /_____/         \/  Login Request Function with all needed things! */
	######################################################################################################################################################	
	## Logout Function
	public function logout() { @$this->session_logout(); @$this->cookie_unset(); 
		unset($_SESSION[$this->sessions."x_users_login_shadow"]); @$this->object_user_unset(); return true; }	
	## Init Function
	public function init() {
		if($this->login_field == "user_name") { $this->user_unique = true; }
		if($this->login_field == "user_mail") { $this->mail_unique = true; }
		if(@$_SESSION[$this->sessions."x_users_ip"] == @$_SERVER["REMOTE_ADDR"]
			AND isset($_SESSION[$this->sessions."x_users_key"])
			AND is_bool($_SESSION[$this->sessions."x_users_stay"])
			AND is_numeric($_SESSION[$this->sessions."x_users_id"])) {
				/////// SHADOW LOGIN EXTENSIONS
				if(!is_numeric(@$_SESSION[$this->sessions."x_users_login_shadow"])) { 
					$checkuser = $_SESSION[$this->sessions."x_users_id"]; 
				} else { 
				if($this->exists($_SESSION[$this->sessions."x_users_id"])) { 
					$checkuser = $_SESSION[$this->sessions."x_users_login_shadow"]; 
					} else { 
					$_SESSION[$this->sessions."x_users_id"] = $_SESSION[$this->sessions."x_users_login_shadow"];
					$_SESSION[$this->sessions."x_users_login_shadow"] = false;
					$checkuser = $_SESSION[$this->sessions."x_users_id"]; } 
				} 
				if(!$this->session_token_valid($checkuser, $_SESSION[$this->sessions."x_users_key"])) {
					$this->object_user_unset();
					$this->cookie_restore();} 
				else { $this->session_restore(); }
		} else {
			$this->object_user_unset();
			$this->cookie_restore(); }}		
	public function login_request($ref, $password, $stayLoggedIn = false) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = strtolower(trim($ref));
		$r	=	$this->mysql->query("SELECT * FROM `".$this->dt_users."` WHERE LOWER(".$this->login_field.") = ?", $bind);
		if( $f = $this->mysql->fetch_array($r) ) {
			if ( $this->password_check($password, $f["user_pass"]) ) {
				// Exit if user Disabled
				if(@$f["user_disabled"] == 1) { $this->login_request_code = 7; return 7; } 
				// Exit if user not blocked
				if(@$f["user_blocked"] == 1) { $this->login_request_code = 4; return 4; } 
				// Exit if User not Confirmed
				if(@$f["user_confirmed"] != 1) { $this->login_request_code = 5; return 5; } 
				// Generate Session Key Unique for this Users Session
				$newtoken	=	$this->session_gen(32);
				while($this->session_token_valid($f["id"], $newtoken)) {$newtoken =	$this->session_gen(32);}
				// Create the Key now as actual Session
				$this->session_token_create($f["id"], $newtoken);
				// Apply Cookies if Set and Activated in Conf
				if($stayLoggedIn) { $this->cookie_set($f["id"], $newtoken); }
				// Update Last Login Date
				$this->mysql->query("UPDATE `".$this->dt_users."` SET last_login = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'");
				// Set needed Data
				$_SESSION[$this->sessions."x_users_ip"]  = @$_SERVER["REMOTE_ADDR"];
				$_SESSION[$this->sessions."x_users_key"] = $newtoken;
				$_SESSION[$this->sessions."x_users_id"]  = $f["id"];
				if($stayLoggedIn) { $_SESSION[$this->sessions."x_users_stay"]  = true; } else { $_SESSION[$this->sessions."x_users_stay"] = false; }
				$this->object_user_set($f["id"]);
				// Drop Recover Codes if Setup in Config
				if($this->login_recover_drop) {
					if(!$this->log_recover) {
						 $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_recover."'");
					} else {$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_recover."'"); }
				}
				// Log Activation Keys on Successfull Login
				if(!$this->log_activation) {
					 $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_activation."'");
				} else {$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_activation."'"); }
				// Set Ref to Logged in User (Senseles)
				// Set Ref Info
				$f["token"] 	= $newtoken;
				$this->internal_ref_set($f);
				// Delete Fails in a Row
				$this->mysql->query("UPDATE `".$this->dt_users."` SET fails_in_a_row = 0 WHERE id = '".$f["id"]."'");
				//Return OK
				$this->login_request_code = 1; return 1;
			} else { 
				// Update Fails in a Row Counter and Apply Autoblock
				$this->mysql->query("UPDATE `".$this->dt_users."` SET fails_in_a_row = fails_in_a_row + 1 WHERE id = '".$f["id"]."'");  
				// Autoblock and Return Code
				if(is_numeric($this->autoblock)) { if($f["fails_in_a_row"] > $this->autoblock) { $this->block_user($f["id"]); $this->mysql->query("UPDATE `".$this->dt_users."` SET block_auto = 1, last_block = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'"); $this->login_request_code = 6; return 6;}} 
				// Wrong Password Exit
				$this->login_request_code = 3; return 3;}	
		} $this->login_request_code = 2; return 2;} // No-Ref Exit	

	######################################################################################################################################################
	/*      _____          __  .__               __  .__               
		  /  _  \   _____/  |_|__|__  _______ _/  |_|__| ____   ____  
		 /  /_\  \_/ ___\   __\  \  \/ /\__  \\   __\  |/  _ \ /    \ 
		/    |    \  \___|  | |  |\   /  / __ \|  | |  (  <_> )   |  \
		\____|__  /\___  >__| |__| \_/  (____  /__| |__|\____/|___|  /
				\/     \/                    \/                    \/    */
	######################################################################################################################################################
	public function activation_request_id($id) { $this->internal_ref_reset(); // Admin can do Activation here!
		// Check if User ID Numeric
		if(!is_numeric($id)) { $this->act_request_code = 2; return 2; }
		$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."`  WHERE id = \"".$id."\" ");
		while($f=$this->mysql->fetch_array($r)){
			// Stop if already confirmed
			if($f["user_confirmed"] == 1) { $this->act_request_code = 3; return 3; }
			// Request Activation Token
			$token = $this->token_gen();
			$this->activation_token_create($f["id"], $token);
			// Set Ref Info
			$f["token"] 	= $token;
			$this->internal_ref_set($f);
			// All Done
			$this->act_request_code = 1; return 1;
		} $this->act_request_code = 2;return 2;} // Ref not Found 

	### User makes Request for new Activation
	public function activation_request($ref) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = strtolower(trim($ref));
		$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."`  WHERE LOWER(".$this->login_field.") = ? ", $bind);
		while($f=$this->mysql->fetch_array($r)){
			// Exit if user Disabled
			if(@$f["user_disabled"] == 1) { $this->login_request_code = 6; return 6; } 
			if($f["user_confirmed"] == 1) { $this->act_request_code = 4; return 4; }
			// Check if Interval for new Request is Okay
			if(is_numeric($this->wait_activation_min)) {if(isset($f["req_activation"])) { if ($this->check_interval($f["req_activation"], '-'.$this->wait_activation_min.' minutes')) {$this->act_request_code = 3; return 3;}}}
			// Check if Activation is Blocked
			if($f["block_activation"] == 1) {  $this->act_request_code = 5; return 5; }
			// Update Req Activation Date
			$this->mysql->query("UPDATE `".$this->dt_users."` SET req_activation = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'");
			// Generate Token for Activation
			$token = $this->token_gen();
			$this->activation_token_create($f["id"], $token);
			// Set Ref Info
			$f["token"] 	= $token;
			$this->internal_ref_set($f);
			// All Done
			$this->act_request_code = 1;return 1;
		} $this->act_request_code = 2; return 2;} // Ref not Found	
	
	## Confirm Requested Activation by User
	public function activation_confirm($userid, $token, $newpass = false) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = $token;
		// Return if User not Numeric
		if(!is_numeric($userid)) { $this->act_request_code = 2; return 2; }
		$r = $this->mysql->query("SELECT * FROM `".$this->dt_keys."` WHERE session_key = ? AND key_type = '".$this->key_activation."' AND fk_user = '".$userid."' AND is_active = 1", $bind);
		if($f= $this->mysql->fetch_array($r)){
			// Blocked for Activation
			if($f["block_activation"] == 1) {  $this->act_request_code = 4; return 4; }
			// Interval not Reached
			if(!$this->activation_token_valid($userid, $token)) { $this->act_request_code = 3; return 3;}
			// Log Activation Token
			if($this->log_activation) {$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");	
			} else { $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");}
			// Get User Data
			$x  = $this->get($userid);
			// Set Ref Info
			$f["token"] 	= $token;
			$this->internal_ref_set($f);
			// Update Last Activation
			$this->mysql->query("UPDATE `".$this->dt_users."` SET last_activation = CURRENT_TIMESTAMP() WHERE id = '".$userid."'");
			// Confirm the User
			$this->mysql->query("UPDATE `".$this->dt_users."` SET user_confirmed = 1 WHERE id = '".$userid."'");
			// Delete the Shadow Mail from users which may have tried to Register that Mail
			if($this->mail_unique) { $this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = NULL WHERE LOWER(user_shadow) = '".@strtolower(@trim($x["user_mail"]))."'"); }
			// Change User Password
			if($newpass) { $this->changeUserPass($f["fk_user"], $newpass); }
			// All Okay and Return
			$this->act_request_code = 1; return 1;
		} else { $this->act_request_code = 2; return 2; }} // Ref not Found	

	######################################################################################################################################################
	/* 	__________                                       
		\______   \ ____   ____  _______  __ ___________ 
		 |       _// __ \_/ ___\/  _ \  \/ // __ \_  __ \
		 |    |   \  ___/\  \__(  <_> )   /\  ___/|  | \/
		 |____|_  /\___  >\___  >____/ \_/  \___  >__|   
				\/     \/     \/                \/  	*/
	######################################################################################################################################################
	## Recover Request for Admin
	public function recover_request_id($id) { $this->internal_ref_reset();
		// Not numeric ID
		if(!is_numeric($id)) { $this->internal_ref_set($f); $this->rec_request_code = 2; return 2; }
		$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."`  WHERE id = \"".$id."\"");
		while($f=$this->mysql->fetch_array($r)){
			// Create Recover Token
			$token = $this->token_gen();
			$this->recover_token_create($f["id"], $token);
			// Set Ref Info
			$f["token"] 	= $token;
			$this->internal_ref_set($f);
			// All Okay
			$this->rec_request_code = 1; return 1;
		} $this->rec_request_code = 2; return 2;} // Ref not Found		
		
	## Recover Request for User Ref
	public function recover_request($ref) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = strtolower(trim($ref));
		$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."`  WHERE LOWER(".$this->login_field.") = ? ", $bind);
		while($f=$this->mysql->fetch_array($r)){
			// Exit if user Disabled
			if(@$f["user_disabled"] == 1) { $this->login_request_code = 5; return 5; } 
			// Check if Blocked for Reset
			if($f["block_reset"] == 1) {  $this->internal_ref_set($f); $this->rec_request_code = 4; return 4; }
			// Check if Interval for new Reset reached
			if(is_numeric($this->wait_recover_min)) {
				if(isset($f["req_reset"]) AND $f["req_reset"] != NULL) { if ($this->check_interval($f["req_reset"], '-'.$this->wait_recover_min.' minutes')) {$this->internal_ref_set($f); $this->rec_request_code = 3;return 3;}}
			}
			// Recover Request Reset Counter
			$this->mysql->query("UPDATE `".$this->dt_users."` SET req_reset = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'");
			// Create Recover Token
			$token = $this->token_gen();
			$this->recover_token_create($f["id"], $token);
			// Set Ref Info
			$f["token"] 	= $token;
			$this->internal_ref_set($f);
			// All Okay
			$this->rec_request_code = 1; return 1;
		} $this->rec_request_code = 2;return 2;} // Ref not Found		
		
	## Confirm Recover Request by User with new Password
	public function recover_confirm($userid, $token, $newpass) { $this->internal_ref_reset(); $bind[0]["value"] = $token; $bind[0]["type"] = "s";
		// Check if Userid is numeric
		if(!is_numeric($userid)) { $this->rec_request_code = 2; return false; }
		$r = $this->mysql->query("SELECT * FROM `".$this->dt_keys."` WHERE session_key = ? AND key_type = '".$this->key_recover."' AND fk_user = '".$userid."' AND is_active = 1", $bind);
		if($f= $this->mysql->fetch_array($r)){
			// Check for Interval
			if(!$this->recover_token_valid($userid, $token)) { $this->rec_request_code = 3; return 3; }
			// Log Recovers
			if($this->log_recover) {$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_recover."'");	
			} else { $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_recover."'");}			
			// Set Ref Info
			$f["token"] 	= $token;
			// Update Last Reset Var
			$this->mysql->query("UPDATE `".$this->dt_users."` SET last_reset = CURRENT_TIMESTAMP() WHERE id = '".$userid."'");
			// Last Activation Date Update
			$this->mysql->query("UPDATE `".$this->dt_users."` SET last_activation = CURRENT_TIMESTAMP() WHERE id = '".$userid."' AND activation_date IS NULL");
			// Confirm User on Recover Password
			$this->mysql->query("UPDATE `".$this->dt_users."` SET user_confirmed = 1 WHERE id = '".$userid."'");
			// Log Activation Token
			if($this->log_activation) {$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");	
			} else { $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");}
			// Get User Data
			$x  = $this->get($userid);
			// Delete the Shadow Mail from users which may have tried to Register that Mail
			if($this->mail_unique) { $this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = NULL WHERE LOWER(user_shadow) = '".strtolower(trim($x["user_mail"]))."'"); }
			// Change User Password
			$this->changeUserPass($f["fk_user"], $newpass);
			// All Okay
			$this->rec_request_code = 1; return 1;
		} else {$this->rec_request_code = 2; return 2;}} // Ref not Found	

	######################################################################################################################################################	
	/*      _____         .__.__    ___________    .___.__  __   
		  /     \ _____  |__|  |   \_   _____/  __| _/|__|/  |_ 
		 /  \ /  \\__  \ |  |  |    |    __)_  / __ | |  \   __\
		/    Y    \/ __ \|  |  |__  |        \/ /_/ | |  ||  |  
		\____|__  (____  /__|____/ /_______  /\____ | |__||__|  
				\/     \/                  \/      \/         */		
	######################################################################################################################################################
	# Mail Edit with Confirmation
	public function mail_edit($id, $newmail, $nointervall = false) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = trim(strtolower($newmail));	
		// Return if user not numeric
		if(!is_numeric($id)) { $this->mc_request_code = 2;return 2; }				
		// Proceed
		$r = $this->mysql->query("SELECT * FROM `".$this->dt_users."`  WHERE id = \"".$id."\" ");
		if($f=$this->mysql->fetch_array($r)){
			// Exit if user Disabled
			if(@$f["user_disabled"] == 1) { $this->login_request_code = 6; return 6; } 
			// Mail Edit Blocked for User
			if($f["block_mail_edit"] == 1) {  $this->internal_ref_set($f); $this->mc_request_code = 5; return 5; }
			// Check if Interval for new Mail Edit is Okay or Deactivated in Function (For Admin)
			if(!$nointervall) { if(is_numeric($this->wait_activation_min)) {if(isset($f["req_mail_edit"])) {if ($this->check_interval($f["req_mail_edit"], '-'.$this->wait_activation_min.' minutes')) {$this->internal_ref_set($f);$this->mc_request_code = 3;return 3; }}} }
			// Change the Users Mail
			if(!$this->changeUserShadowMail($id, trim($newmail))) { $this->mc_request_code = 4; return 4; } // Mail Exists on Once Active User
			// Log Mail Edits
			if($this->log_mail_edit) {$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE fk_user = ".$f["id"]." AND key_type = '".$this->key_mail_edit."'");	
			} else { $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = ".$f["id"]." AND key_type = '".$this->key_mail_edit."'");};
			// Update last Mail Edit
			$this->mysql->query("UPDATE `".$this->dt_users."` SET req_mail_edit = CURRENT_TIMESTAMP() WHERE id = '".$userid."'");
			// Create the Token
			$token	=	$this->token_gen();
			$this->mail_edit_token_create($f["id"], $token);
			// Set Ref Info
			$f["token"] 	= $token;
			$f["user_shadow"] 	= $newmail;
			$this->internal_ref_set($f);
			// All is okay
			$this->mc_request_code = 1; return 1;
		} $this->mc_request_code = 2; return 2;} // Ref not Found		
		
	# Confirm Mail Edit (Can be placed anywhere)
	public function mail_edit_confirm($userid, $token, $run = true) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = $token;
		if($run) {
			// Return if user not numeric
			if(!is_numeric($userid)) { $this->internal_ref_set($f);$this->mc_request_code = 2;return 2; }	
			// Proceed
			$r = $this->mysql->query("SELECT * FROM `".$this->dt_keys."` WHERE session_key = ? AND key_type = '".$this->key_mail_edit."' AND fk_user = '".$userid."' AND is_active = 1", $bind);
			if($f= $this->mysql->fetch_array($r)){
				// Stop if Blocked for Mail Edit
				if($f["block_mail_edit"] == 1) {  $this->internal_ref_set($f);$this->mc_request_code = 5; return 5; }
				// Check for Interval (token expire)
				if(!$this->mail_edit_token_valid($userid, $token)) { $this->internal_ref_set($f);$this->mc_request_code = 3; return 3; }
				// Log Activation
				if($this->log_mail_edit) {$this->mysql->query("UPDATE `".$this->dt_keys."` SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_mail_edit."'");	
				} else { $this->mysql->query("DELETE FROM `".$this->dt_keys."` WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_mail_edit."'");}				
				// Set Ref Info
				$f["token"] 	= $token;
				$this->internal_ref_set($f);				
				// Change Mail and Initiate Clearing
				$x = $this->mysql->query("SELECT * FROM `".$this->dt_users."`  WHERE id = \"".$userid."\"");
				if($xf=$this->mysql->fetch_array($x)) {
					// Another Account has Changed to this mail or registered
					if($xf["user_shadow"] == NULL OR trim($xf["user_shadow"]) == "") { $this->internal_ref_set($f);$this->mc_request_code = 4; return 4;  }										

					// Process Request
					if(!$this->mail_unique) {
						// Mail already Existant otherwhise change
						if(!$this->changeUserMail($f["fk_user"], $xf["user_shadow"])) { $this->internal_ref_set($f);$this->mc_request_code = 6; return 6; } 							
						
						// Update Last Edit Date
						$this->mysql->query("UPDATE `".$this->dt_users."` SET last_mail_edit = CURRENT_TIMESTAMP() WHERE id = '".$xf["id"]."'");

						// Set Shadow Mail to Null
						$this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = NULL WHERE id = '".$userid."'");
					} else {
						// Delete Unconfirmed Account if Exists
						$this->mysql->query("DELETE FROM `".$this->dt_users."` WHERE LOWER(user_mail) = '".$this->mysql->escape(strtolower(trim($xf["user_shadow"])))."' AND user_confirmed = 0");
						
						// Mail already Existant otherwhise change
						if(!$this->changeUserMail($f["fk_user"], $xf["user_shadow"])) { $this->internal_ref_set($f);$this->mc_request_code = 6; return 6; }							
						
						// Update Edit Counter
						$this->mysql->query("UPDATE `".$this->dt_users."` SET last_mail_edit = CURRENT_TIMESTAMP() WHERE id = '".$xf["id"]."'");						
						
						// Reset Other Mails who have this Mail if not Unique!
						$this->mysql->query("UPDATE `".$this->dt_users."` SET user_shadow = NULL WHERE LOWER(user_shadow) = '".$this->mysql->escape(strtolower(trim($xf["user_shadow"])))."'");
					}
				}
				// All Okay
				$this->mc_request_code = 1; return 1;
			} else { $this->mc_request_code = 2; return 2;} // Ref not Found
		}
	}

	######################################################################################################################################################
	/*	________  .__               .__                
		\______ \ |__| ____________ |  | _____  ___.__.
		 |    |  \|  |/  ___/\____ \|  | \__  \<   |  |
		 |    `   \  |\___ \ |  |_> >  |__/ __ \\___  |
		/_______  /__/____  >|   __/|____(____  / ____|
				\/        \/ |__|             \/\/    	*/
	######################################################################################################################################################
	public $display_return_code = false;
	// Display Login
	public function display_login($spawn_register_button = array("url" => "", "label" => "Register Now"), $spawn_cookie_checkbox = "Stay Logged In?", $spawn_reset_button = array("url" => "", "label" => "Reset Account"), $login_button_label = "Login", $label = array("ref_placeholder" => "Please enter your E-Mail", "ref_label" => "E-Mail", "pass_label" => "Password", "pass_placeholder" => "Please enter your password!"), $captcha = array("url" => "captcha.jpg", "code" => "243fsdfsfds")) {	
		$this->display_return_code = false;
		if (isset($_POST["x_class_user_submit_login"])) {
			if (@$_SESSION["x_class_user_csrf_login"] == @$_POST["x_class_user_csrf"] AND trim(@$_POST["x_class_user_csrf"]) != "" AND isset($_POST["x_class_user_csrf"])) {
				if(!is_array($captcha) OR (is_array($captcha) AND $captcha["code"] == @$_POST["x_class_user_captcha"] AND is_numeric($captcha["code"]))) {
					$result = $this->login_request(@$_POST["x_class_user_ref"], @$_POST["x_class_user_pass"], @$_POST["x_class_user_submit_login_stay"]);
					$this->display_return_code = $result;		
				} else { $this->display_return_code = "captcha_error"; }
			} else { $this->display_return_code = "csrf_error"; }
		} $csrf = mt_rand(10000,999999); $_SESSION["x_class_user_csrf_login"] = $csrf; ?>
		<div class="x_class_user" id="x_class_user_login">
			<div class="x_class_user_inner">
				<form method="post">
					<input type="hidden" name="x_class_user_csrf" value="<?php echo $csrf; ?>">
					<div class="x_class_user_form">
						<?php if(isset($label["ref_label"])) { ?><label class="x_class_user_label"><?php echo $label["ref_label"]; ?></label><br /><?php } ?>
						<input type="text" maxlength="255" placeholder="<?php echo $label["ref_placeholder"]; ?>" name="x_class_user_ref" autofocus="autofocus" autocomplete="off"><br />
						<?php if(isset($label["pass_label"])) { ?><label class="x_class_user_label"><?php echo $label["pass_label"]; ?></label ><br />	<?php } ?>
						<input type="password" maxlength="255" placeholder="<?php echo $label["pass_placeholder"]; ?>" name="x_class_user_pass" autocomplete="off"><br />
						<?php if(is_array($captcha)) { ?>
						<label class="x_class_user_label">Captcha*</label><br />
						<img src="<?php echo $captcha["url"]; ?>"><br />
						<input type="text" maxlength="255" placeholder="Captcha" name="x_class_user_captcha" autofocus="autofocus" autocomplete="off"><br />
						<?php } ?>			
					</div>	
					<div class="x_class_user_button">
						<?php if($spawn_cookie_checkbox) {  echo $spawn_cookie_checkbox; ?> 	<input type="checkbox" name="x_class_user_submit_login_stay"><br /> <?php } ?>
						<?php if(is_array($spawn_register_button)) { ?>	<a href="<?php echo $spawn_register_button["url"]; ?>"><?php echo $spawn_register_button["label"]; ?></a> <?php } ?>
						<?php if(is_array($spawn_reset_button)) { ?> <a href="<?php echo $spawn_reset_button["url"]; ?>"><?php echo $spawn_reset_button["label"]; ?></a> <?php } ?>
						<input type="submit" value="<?php echo $login_button_label; ?>" name="x_class_user_submit_login">
					</div>
				</form>
			</div>
		</div><?php }		
	}
