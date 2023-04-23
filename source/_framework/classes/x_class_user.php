<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Login and Users Control Class */
class x_class_user {    
	/*	__________                                                  .___
		\______   \_____ ____________    _____   _____    ____    __| _/
		 |     ___/\__  \\_  __ \__  \  /     \  \__  \  /    \  / __ | 
		 |    |     / __ \|  | \// __ \|  Y Y  \  / __ \|   |  \/ /_/ | 
		 |____|    (____  /__|  (____  /__|_|  / (____  /___|  /\____ | 
						\/           \/      \/       \/     \/      \/ 
		_________                _____.__        
		\_   ___ \  ____   _____/ ____\__| ____  
		/    \  \/ /  _ \ /    \   __\|  |/ ___\ 
		\     \___(  <_> )   |  \  |  |  / /_/  >
		 \______  /\____/|___|  /__|  |__\___  / 
				\/            \/        /_____/  */
	## Private SQL Informations
	private $mysql=false; // x_class_mysql Object
	private $dt_keys=false; // Table for Keys
	private $dt_users=false; // Table for Users
	
	## Private Key Informations
	private $key_activation = 1; // Activate and Set Pass || Activate
	private $key_session = 2; // Session Keys
	private $key_recover = 3; // Recover Account Password
	private $key_mail_edit = 4;	// Keys for Mail Changes
	
	## Public Function References and User Info // References out of Major Functions for further Processing
	public $ref=false; public $mail_ref_user=false; public $mail_ref_token=false; public $mail_ref_receiver=false;	// References set by Functions
		private function internal_ref_reset() { $this->ref=array();$this->mail_ref_user=false;$this->mail_ref_token=false;$this->mail_ref_receiver=false;  }
		private function internal_ref_set($array) { $this->ref=$array;$this->mail_ref_user=$array["id"];$this->mail_ref_token=@$array["token"];$this->mail_ref_receiver=$array["user_mail"];  }
		
	## More Public Parameters
	public $user_rank = false; public $rank = false;// Current User Rank
	public $user_id = false; public $id = false; // Current User Id
	public $user_name = false; public $name = false; // Current User Name
	public $user_mail = false; public $mail = false; // Current User Mail
	public $loggedin = false; public $loggedIn = false; // Current User Logged In?
	public $user_loggedIn = false; public $user_loggedin = false; // Current User Logged In?
	public $user=false;
	
	## For Compatibility with older Class
	public $login_request_code = false; // Return Code out of Login Functions
	public $rec_request_code = false; // Return Code out of Recover Functions
	public $act_request_code = false; // Return Code out of Activation Functions
	public $mc_request_code = false; // Return Code out of Mail Change Functions
	
	## General Setup
	private $multi_login=false;public function multi_login($bool = false){$this->multi_login=$bool;} // Multi Login Allowed?
	private $login_recover_drop=false;public function login_recover_drop($bool = false){$this->login_recover_drop=$bool;} // Delete Reset Keys after Success Login?
	private $login_field = "user_mail"; 
	public function login_field_user() { $this->login_field = "user_name"; $this->user_unique = true; } // Set User Name as Login Field Reference
	public function login_field_mail() { $this->login_field = "user_mail"; $this->mail_unique = true; } // Set User Mail as Login Field Reference
	private $mail_unique = false; public function mail_unique($bool = false) { $this->mail_unique = $bool; } // Mail dont have to be unique if not in reference
	private $user_unique = false; public function user_unique($bool = false) { $this->user_unique = $bool; } // User dont have to be unique if not in reference
	
	## Logging Setup		
	private $log_ip=false;public function log_ip($bool=false){$this->log_ip = $bool;} // Log IP Adresses?
	private $log_activation=false;public function log_activation($bool=false){$this->log_activation = $bool;} // Delete old Activation Entries?
	private $log_session=false;public function log_session($bool=false){$this->log_session = $bool;} // Delete old Session Entries?
	private $log_recover=false;public function log_recover($bool=false){$this->log_recover = $bool;} // Delete old Recover Entries?
	private $log_mail_edit=false;public function log_mail_edit($bool=false){$this->log_mail_edit = $bool;} // Delete old Mail Change Entries?
	
	## Interval Between new Requests
	private $wait_activation_min = 6;public function wait_activation_min($int = 6){$this->wait_activation_min = $int;} // Wait Minutes before new Activation Request
	private $wait_recover_min = 6;public function wait_recover_min($int = 6){$this->wait_recover_min = $int;} // Wait Minutes before new Recover Request
	private $wait_mail_edit_min = 6;public function wait_mail_edit_min($int = 6){$this->wait_mail_edit_min = $int;} // Wait Minutes before new Mail Change Request
	
	## Token Expire Hours
	private $min_activation = 6;public function min_activation($int = 6){$this->min_activation = $int;} // Token Valid Length in Minutes for Activation
	private $min_recover = 6;public function min_recover($int = 6){$this->min_recover = $int;} // Token Valid Length in Minutes for Recover
	private $min_mail_edit = 6;public function min_mail_edit($int = 6){$this->min_mail_edit = $int;} // Token Valid Length in Minutes for Mail Change
	
	## Auto-Block user after X tries?
	private $autoblock = false; public function autoblock($int = false) { $this->autoblock = $int; } // Activate Autoblock after X fail Logins?
	
	## Sessions Setup
	private $sessions = "x_users";
	private $sessions_days = 7; public function sessions_days($int = 7){$this->sessions_days = $int;} // Set Max Session Use Days	
	
	## Cookie Setup		
	private $cookies = true;private $cookies_use = false;public function cookies_use($bool = true){$this->cookies_use = $bool;$this->cookies = $this->sessions;} // Allow Cookies Use in General
	private $cookies_days = 7;public function cookies_days($int = 7){$this->cookies_days = $int;} // Max Cookie Lifetime in Days	
	
	## Cookies Functions
	private function cookie_set($id, $key){if($this->cookies_use){setcookie($this->cookies."session_userid", $id, time() + $this->cookies_days * 24 * 60 * 60);setcookie($this->cookies."session_key", $key, time() + $this->cookies_days * 24 * 60 * 60);} return true;}
	private function cookie_unset(){if($this->cookies_use){unset($_COOKIE[$this->cookies.'session_key']);@setcookie($this->cookies.'session_key', '', time() - 3600, '/');unset($_COOKIE[$this->cookies.'session_userid']);@setcookie($this->cookies.'session_userid', '', time() - 3600, '/');} return true;}	
	private function cookie_restore(){if($this->cookies_use){if(@is_numeric($_COOKIE[$this->cookies."session_userid"]) OR @isset($_COOKIE[$this->cookies."session_key"])){if(@$this->session_token_valid(@$_COOKIE[$this->sessions."session_userid"], @$_COOKIE[$this->sessions."session_key"])){@$_SESSION[$this->sessions."x_users_stay"] = true;@$_SESSION[$this->sessions."x_users_key"] = @$_COOKIE[$this->sessions."session_key"];@$_SESSION[$this->sessions."x_users_id"] = @$_COOKIE[$this->sessions."session_userid"];@$_SESSION[$this->sessions."x_users_ip"] = @$_SERVER["REMOTE_ADDR"];$this->session_restore();return true;}else{$this->cookie_unset();return false;}}return false;}return true;}	
	
	## Password Functions
	public function password_gen($len = 12, $comb = "abcde12345"){$pass = array(); $combLen = strlen($comb) - 1; for ($i = 0; $i < $len; $i++) { $n = mt_rand(0, $combLen); $pass[] = $comb[$n]; } return implode($pass);}			
	public function password_crypt($var, $hash = PASSWORD_BCRYPT) { return password_hash($var,$hash); }
	public function password_check($cleartext, $crypted) { return password_verify($cleartext,$crypted); }		
	
	## Passfilter
	private $passfilter = false; private $passfilter_signs = 0; private $passfilter_capital = 0; private $passfilter_small = 0; private $passfilter_special = 0; private $passfilter_number = 0;
	public function passfilter($signs = 6, $capitals = 0, $small = 0, $special = 0, $number = 0) {$this->passfilter_signs = $signs;$this->passfilter_capital = $capitals;$this->passfilter_small = $small;$this->passfilter_special = $special;$this->passfilter_number = $number;}
	public function passfilter_check($passclear) { $isvalid = true;
		if($this->passfilter_signs > 0) { if(@strlen(@$passclear) < $this->passfilter_signs) { $isvalid = false; } }
		if($this->passfilter_capital > 0) { if(preg_match('/[A-Z]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_capital) { $isvalid = false; } } else { $isvalid = false; } }
		if($this->passfilter_small > 0) { if(preg_match('/[a-z]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_small) {  $isvalid = false; } } else { $isvalid = false; } }
		if($this->passfilter_special > 0) { if(preg_match('/[\'^£$%&*()}{@#~?><>,|=_+-]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_special) {  $isvalid = false; } } else { $isvalid = false; } }
		if($this->passfilter_number > 0) { if(preg_match('/[0-9]/', $passclear, $matches)){ if(count($matches) > $this->passfilter_number) {  $isvalid = false; } } else { $isvalid = false; } }
		return $isvalid;}
	
	## Setup Token Generation
	private $token_charset = "0123456789"; public function token_charset($charset = "0123456789") { $this->token_charset = $charset; } // Setup General Token Charset
	private $token_length = 24; public function token_length($length = 24) { $this->token_length = $length; } // Setup General Token Length
	private function token_gen() { return $this->password_gen($this->token_length, $this->token_charset); }
	private $session_charset = "0123456789"; public function session_charset($charset = "0123456789") { $this->session_charset = $charset; } // Setup Session Token Charset
	private $session_length = 24; public function session_length($length = 24) { $this->session_length = $length; }	 // Setup Session Token Length	
	private function session_gen() { return $this->password_gen($this->session_length, $this->session_charset); }		

	### Get ID For Operation Functions
	private function int_opid($id) { if($this->user_loggedIn AND !$id) { return $this->user_id; } elseif(!$this->user_loggedIn AND !$id) { return false; } elseif($this->exists($id)) { return $id; } return false; }
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	## MOSTLY PUBLIC FUNCTIONS  MOSTLY PUBLIC FUNCTIONS  MOSTLY PUBLIC FUNCTIONS  MOSTLY PUBLIC FUNCTIONS  MOSTLY PUBLIC FUNCTIONS  MOSTLY PUBLIC  
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	# Get Array with user Table Fields
	public function get($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} $r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$id."'");if($x=$this->mysql->fetch_array($r)){return $x;} return false; }
	# Exists User
	public function exists($id){if(!is_numeric($id)){return false;}$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$id."'");if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}
	# Delete User
	public function delete($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = '".$id."'"); return $this->mysql->query("DELETE FROM ".$this->dt_users." WHERE id = '".$id."'");}	
	## Session Change for User
	public function disable_user_session($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}return $this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE key_type = '".$this->key_session."' AND fk_user = '".$id."'");}
	public function delete_user_session($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}return $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE '".$this->key_session."' AND fk_user = '".$id."'");}	
	## Change User Password
	public function change_pass($id = false, $new){ return $this->changeUserPass($id = false, $new);}
	public function change_password($id = false, $new){ return $this->changeUserPass($id = false, $new);}
	public function changeUserPass($id = false, $new){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} if(is_numeric($id) AND is_string($new)){return $this->mysql->query("UPDATE ".$this->dt_users." SET user_pass = \"".$this->mysql->escape($this->password_crypt($new))."\" WHERE id = '".$id."'");}return false;}	
	## Change User Rank
	public function change_rank($id = false, $new){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} if(is_numeric($id) AND is_numeric($new)){return $this->mysql->query("UPDATE ".$this->dt_users." SET user_rank = '".$new."' WHERE id = '".$id."'");}return false;}
	## Confirmation
	public function confirmed_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$id."'");if($x = $this->mysql->fetch_array($r)){if($x["user_confirmed"] == 1){return true;}}}return false;}	
	## Block
	public function blocked_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)) {$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$id."'");if($x = $this->mysql->fetch_array($r)){if($x["user_blocked"] != 1){return false;}}}return true;}
	public function block_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){$this->mysql->query("UPDATE ".$this->dt_users." SET last_block = CURRENT_TIMESTAMP() WHERE id = '".$id."'"); return $this->mysql->query("UPDATE ".$this->dt_users." SET user_blocked = 1 WHERE id = '".$id."'");}return false;}		
	public function unblock_user($id = false){if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}if(is_numeric($id)){return $this->mysql->query("UPDATE ".$this->dt_users." SET user_blocked = 0 WHERE id = '".$id."'");}return false;}
	## Logout all Users
	public function logout_all(){return $this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE key_type = '".$this->key_session."'");}
	## Check if Ref Exists
	public function ref_exists($ref){ return $this->refExists($ref); }	
	public function refExists($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE LOWER(".$this->login_field.") = ?", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}	
	public function ref_exists_active($ref){ return $this->refExistsActive($ref); }
	public function refExistsActive($ref) {$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE LOWER(".$this->login_field.") = ? AND user_confirmed = 1", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}	
	## Check if Username Exists
	public function username_exists($ref){ return $this->usernameExists($ref); }
	public function usernameExists($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE LOWER(user_name) = ?", $bind);if($rrx=$this->mysql->fetch_array($r)){return true;}return false;}	
	public function username_exists_active($ref){ return $this->usernameExistsActive($ref); }
	public function usernameExistsActive($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE LOWER(user_name) = ? AND user_confirmed = 1", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}	
	## Check if Mail Exists
	public function mail_exists($ref){ return $this->mailExists($ref); }
	public function mailExists($ref){$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE LOWER(user_mail) = ?", $bind);if($rrx=$this->mysql->fetch_array($r)){return true;}return false;}	
	public function mail_exists_active($ref){ return $this->mailExistsActive($ref); }
	public function mailExistsActive($ref) {$bind[0]["value"] = strtolower(trim($ref));$bind[0]["type"] = "s"; $r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE LOWER(user_mail) = ? AND user_confirmed = 1", $bind);if($rrx = $this->mysql->fetch_array($r)){return true;}return false;}		
	## Change a Username
	public function change_name($id = false, $new = false){ return $this->changeUserName($id , $new);}	
	public function changeUserName($id = false, $new = false){ $bind[0]["value"] = trim($new);$bind[0]["type"] = "s";
		if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}
		if(strlen(trim($new)) > 0) {} else { return false; }
		if(!$this->user_unique){
			// Change Username if not Unique
			return $this->mysql->query("UPDATE ".$this->dt_users." SET user_name = ? WHERE id = '".$id."'", $bind);
		} else {
			$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$id."'");
			if($rrx = $this->mysql->fetch_array($r)){
				// Same username as already?
				if(trim(strtolower($rrx["user_name"])) == trim(strtolower($new))) {return true;}
		    }
		  if($this->usernameExistsActive($new)){return false;}else{$this->mysql->query("UPDATE ".$this->dt_users." SET user_name = ? WHERE id = '".$id."'", $bind); return true;}
		}
		return false;}		
	## Change Users Shadow Mail
	public function change_shadow($id = false, $new = false){ return $this->changeUserShadowMail($id , $new);}	
	public function changeUserShadowMail($id = false, $new = false){ $bind[0]["value"] = trim($new);$bind[0]["type"] = "s";
		if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}
		if(strlen(trim($new)) > 0) {} else { return false; }
		if (!$this->mail_unique) { 
			return $this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = ? WHERE id = '".$id."'", $bind);
		}else{ 
			if($this->mailExistsActive($new)){return false;}else{return $this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = ? WHERE id = '".$id."'", $bind);}
		}
		return false;}		
	## Change Users Mail	
	public function change_mail($id = false, $new = false){ return $this->changeUserMail($id , $new);}	
	public function changeUserMail($id = false, $new = false)  { $bind[0]["value"] = trim($new);$bind[0]["type"] = "s";	
		if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);}
		if(strlen(trim($new)) > 0) {} else { return false; }
		if (!$this->mail_unique) {
			// Direct Add if not Unique
			return $this->mysql->query("UPDATE ".$this->dt_users." SET user_mail = ? WHERE id = '".$id."'", $bind);	
		} else { 
			$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$id."'");
			// User already has this mail
			if($rrx = $this->mysql->fetch_array($r)){ if(trim(strtolower($rrx["user_mail"])) == trim(strtolower($new))) {return true;}}
			if($this->mailExistsActive($new)) {
				// Mail Exists
				return false;
			} else {
				// Delete unconfirmed old user
				$this->mysql->query("DELETE FROM ".$this->dt_users." WHERE user_confirmed = 0 AND LOWER(user_mail) = '".$this->mysql->escape(strtolower(trim($new)))."'");
				// Clear Shadow Mail from Users
				$this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = NULL WHERE LOWER(user_shadow) = '".$this->mysql->escape(strtolower(trim($new)))."'");
				// Update Mail
				$this->mysql->query("UPDATE ".$this->dt_users." SET user_mail = ? WHERE id = '".$id."'", $bind);
				// Return True
				return  true;
			}
		}return false;}	
	## Add a User
	public function add_user($nameref, $mail, $password = false, $rank = false, $activated = false){ return $this->changeUserMail($nameref, $mail, $password, $rank, $activated);}
	public function addUser($nameref, $mail, $password = false, $rank = false, $activated = false) { 		
		// If Reference Exists than false
			// Mail or other ref?
			if($this->login_field == "user_mail") { $ref = $mail; } else { $ref = $nameref;  }
			$bind[0]["value"] = strtolower(trim($ref)); $bind[0]["type"] = "s";
			// Find Ref
			$r = $this->mysql->select("SELECT * FROM ".$this->dt_users." WHERE LOWER(".$this->login_field.") = ? AND user_confirmed = 1", false, $bind);
			// User Exists Active with Ref
			if(is_array($r)){ return false;   }  		
		// Prepare Activated
		if(!$activated) {$activated = 0;} else {$activated = 1;}		
		// Prepare Rank
		if(!$rank) {$rank = "";} else {$rank = $rank;}		
		// Prepare Password
		if(!$password OR trim($password) == "") {$password = "NULL";} else {$password = $this->password_crypt($password);}		
		//Delete Other Unconfirmed
		$this->mysql->query("DELETE FROM ".$this->dt_users." WHERE user_confirmed = 0  AND LOWER(".$this->login_field.") = ?", $bind);
		$this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = NULL WHERE LOWER(user_shadow) = ?", $bind);
		//Query
		$bind[0]["value"] = trim($nameref); $bind[0]["type"] = "s";
		$bind[1]["value"] = trim($mail); $bind[1]["type"] = "s";
		$this->mysql->query("INSERT INTO ".$this->dt_users."(user_name, user_mail, user_pass, user_rank, user_confirmed)
		VALUES(?, ?, '". $password."', '".$rank."', '".$activated."')", $bind);	
		return true;}

	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	# Extradata User Array Field Modifications
	public function get_extra($id = false) {if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} if(is_numeric($id)) {$ar = $this->mysql->select("SELECT * FROM ".$this->dt_users." WHERE id = '".$id."'");if(is_array($ar)) {return unserialize($ar["extradata"]);} return false;} return false;}
	public function set_extra($id = false, $array= false) {if(!$this->int_opid($id)){ return false; } else { $id = $this->int_opid($id);} if(is_numeric($id) AND is_array($array)) {$bind[0]["type"] = "s";$bind[0]["value"] = serialize($array);return $this->mysql->query("UPDATE ".$this->dt_users." SET extradata = ? WHERE id = '".$id."'", $bind);} return false;}		
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	# Extrafield Table Modifications
	public function user_add_field($fieldstring) { return $this->mysql->query("ALTER TABLE ".$this->dt_users." ADD ".$fieldstring." ;");}
	public function user_del_field($fieldname) { return $this->mysql->query("ALTER TABLE ".$this->dt_users." DROP COLUMN ".$fieldname." ;"); }	
	## Check Time Interval Function	
	private function check_interval_value($datetimeref, $strstring) { $new = strtotime($datetimeref) - strtotime($strstring); return $new;}
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	## Get Rrequest Interval Functions
	public function activation_request_time($user) { return $this->request_time($user, $this->key_activation); }
	public function recover_request_time($user) { return $this->request_time($user, $this->key_recover); }
	public function mail_edit_request_time($user) { return $this->request_time($user, $this->key_mail_edit); }
	private function request_time($user, $type) {
		if(is_numeric($user) AND is_numeric($type)){
			if($this->key_mail_edit == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$user."'");
				if($res=$this->mysql->fetch_array($r)){
					if(is_numeric($this->wait_mail_edit_min)) {
						if(isset($res["req_mail_edit"])) { return $this->check_interval_value($res["req_mail_edit"], '-'.$this->wait_mail_edit_min.' minutes');}
					} else { return 0;}
				} 			
			} elseif($this->key_recover == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$user."'");
				if($res=$this->mysql->fetch_array($r)){
					if(is_numeric($this->wait_recover_min)) { 
						if(isset($res["req_reset"])) { return $this->check_interval_value($res["req_reset"], '-'.$this->wait_recover_min.' minutes');}
					} else { return 0;}
				} 					
			} elseif($this->key_activation == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE id = '".$user."'");
				if($res=$this->mysql->fetch_array($r)){
					if(is_numeric($this->wait_activation_min)) {
						if(isset($res["req_activation"])) { return $this->check_interval_value($res["req_activation"], '-'.$this->wait_activation_min.' minutes');}
					} else { return 0;}
				}					
			}
		} return false;}
	## Check Time Difference Function	
	/*private function get_cur_time_dif($datetime) { $datetime = strtotime($datetime); return $datetime; $current = strtotime("Y-m-d H:i:s"); $current = $current - $datetime; return $current;}		
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	## Get Token Interval Functions
	public function activation_token_time($user, $token) { return $this->token_time($user, $token, $this->key_activation); }
	public function recover_token_time($user, $token) { return $this->token_time($user, $token, $this->key_recover); }
	public function mail_edit_token_time($user, $token) { return $this->token_time($user, $token, $this->key_mail_edit); }
	private function token_time($user, $token, $type) {
		if(is_numeric($user) AND isset($token) AND is_numeric($type)){
			$bind[0]["value"] = $token;
			$bind[0]["type"] = "s";
			if($this->key_mail_edit == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE key_type = '".$this->key_mail_edit."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return 0; }
					if(is_numeric($this->min_mail_edit)) {
						if(isset($f["creation"])) { return $this->get_cur_time_dif($f["creation"]); } else { return 0; }
					} else { return 999999999; }					
				} else {return 0;}					
			} elseif($this->key_recover == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE key_type = '".$this->key_recover."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return 0; }
					if(is_numeric($this->min_recover)) {
						if(isset($f["creation"])) { return $this->get_cur_time_dif($f["creation"]); } else { return 0; }
					} else { return 999999999; }			
				} else {return 0;}						
			} elseif($this->key_activation == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE key_type = '".$this->key_activation."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return 0; }
					if(is_numeric($this->min_activation)) {
						if(isset($f["creation"])) { return $this->get_cur_time_dif($f["creation"]); } else { return 0; }
					}  else { return 999999999; }						
				} else {return 0;}						
			}
		} return false;}*/
		
	## ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE key_type = '".$this->key_mail_edit."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return false; }
					if(is_numeric($this->min_mail_edit)) {
						if(isset($res["creation"])) { if(!$this->check_interval($res["creation"], ''.$this->min_mail_edit.' minutes')) {return false;} }
					}					
					return true;
				} else {return false;}					
			} elseif($this->key_session == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE key_type = '".$this->key_session."' AND session_key = ? AND fk_user = '".$user."'", $bind);
				if($res=$this->mysql->fetch_array($r)){
					if($res["is_active"] != 1) { return false; }
					if(is_numeric($this->sessions_days)) {
						 if(isset($res["creation"])) {
							if ($this->check_interval($res["creation"],''.$this->sessions_days.' days')) {
								if($this->log_session) { $this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE id = '".$res["id"]."'"); }
								else { $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE id = '".$res["id"]."'"); }
								return false;
							}
						}
					}
					return true;
				} else {return false;}						
			} elseif($this->key_recover == $type) {
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE key_type = '".$this->key_recover."' AND session_key = ? AND fk_user = '".$user."'", $bind);
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
				$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE key_type = '".$this->key_activation."' AND session_key = ? AND fk_user = '".$user."'", $bind);
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
	### PRIVATE FUNCTIONS PRIVATE FUNCTIONS PRIVATE FUNCTIONS PRIVATE FUNCTIONS PRIVATE FUNCTIONS PRIVATE FUNCTIONS PRIVATE FUNCTIONS PRIVATE FUNCTIONS 
	######################################################################################################################################################
	## Check Time Interval Function	
	private function check_interval($datetimeref, $strstring) {if (strtotime($datetimeref) < strtotime($strstring)) {return false;} return true;}	
	private function check_intervalx($datetimeref, $strstring) {return strtotime($datetimeref)."-".$strstring."-".strtotime($strstring); if (strtotime($datetimeref) < strtotime($strstring)) {return false;} return true;}	
	######################################################################################################################################################
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
				$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE key_type = '".$this->key_mail_edit."' AND fk_user = '".$user."'");
				if(!$this->log_mail_edit) {
					$this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE key_type = '".$this->key_mail_edit."' AND fk_user = '".$user."'");
				} 		
				if($this->log_ip) {$thenewip = @$_SERVER["REMOTE_ADDR"];} else {$thenewip = "hidden";}
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip) VALUES('".$user."', '".$this->key_mail_edit."', ?, '1', '".$this->mysql->escape($thenewip)."')", $bind);
				return true;
			} elseif($this->key_session == $type) {
				if(!$this->multi_login) {
					$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE key_type = '".$this->key_session."' AND fk_user = '".$user."'");
					if(!$this->log_session) {
						$this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE key_type = '".$this->key_session."' AND fk_user = '".$user."'");
					} 
				} else {
					if(!$this->log_session) {
						$this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE key_type = '".$this->key_session."' AND fk_user = '".$user."' AND is_active = 0");
					} 
				}
				if($this->log_ip) { $thenewip = @$_SERVER["REMOTE_ADDR"]; } else { $thenewip = "hidden"; }
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip, refresh_date) VALUES('".$user."', '".$this->key_session."', ?, '1', '".$this->mysql->escape($thenewip)."', CURRENT_TIMESTAMP())", $bind);
				return true;
			} elseif($this->key_recover == $type) {
				$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE key_type = '".$this->key_recover."' AND fk_user = '".$user."'");
				if(!$this->log_recover) {
					$this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE key_type = '".$this->key_recover."' AND fk_user = '".$user."'");
				} 		
				if($this->log_ip) {$thenewip = @$_SERVER["REMOTE_ADDR"];} else {$thenewip = "hidden";}
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip) VALUES('".$user."', '".$this->key_recover."', ?, '1', '".$this->mysql->escape($thenewip)."')", $bind);
				return true;
			} elseif($this->key_activation == $type) {
				$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE key_type = '".$this->key_activation."' AND fk_user = '".$user."'");
				if(!$this->log_activation) {
					$this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE key_type = '".$this->key_activation."' AND fk_user = '".$user."'");
				} 		
				if($this->log_ip) {$thenewip = @$_SERVER["REMOTE_ADDR"];} else {$thenewip = "hidden";}
				$this->mysql->query("INSERT INTO ".$this->dt_keys."(fk_user, key_type, session_key, is_active, request_ip) VALUES('".$user."', '".$this->key_activation."', ?, '1', '".$this->mysql->escape($thenewip)."')", $bind);
				return true;
			}
		} return false;}	
	######################################################################################################################################################
	## Session Function to Logout A Session
	private function session_logout() {
		if(!is_numeric($_SESSION[$this->sessions."x_users_id"])) { return false; }
		if($this->multi_login) { $ext = "AND session_key = '".$this->mysql->escape($_SESSION[$this->sessions."x_users_key"])."' "; } else {$ext = " ";}
		if(!$this->log_session) {$this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE key_type = '".$this->key_session."' AND fk_user = '".@$_SESSION[$this->sessions."x_users_id"]."' ".$ext);
		} else { $this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE key_type = '".$this->key_session."' AND fk_user = '".@$_SESSION[$this->sessions."x_users_id"]."' AND is_active = 1 ".$ext); } }
	######################################################################################################################################################
	## Session Function to Restore
	private function session_restore(){
		if(is_numeric($_SESSION[$this->sessions."x_users_id"])) {
			$r = $this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE user_confirmed = '1' AND user_blocked <> 1 AND id = '".$_SESSION[$this->sessions."x_users_id"]."'");
			if($cr = $this->mysql->fetch_array($r)){
				$this->object_user_set($cr["id"]);
				$this->mysql->query("UPDATE ".$this->dt_keys." SET refresh_date = CURRENT_TIMESTAMP() WHERE fk_user = '".$cr["id"]."' AND session_key = \"".$this->mysql->escape(@$_SESSION[$this->sessions."x_users_key"])."\" AND is_active = 1 AND key_type = '".$this->key_session."'"); 
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
	######################################################################################################################################################
	######################################################################################################################################################
	######################################################################################################################################################
	
	/*	___________     ___.   .__                 
		\__    ___/____ \_ |__ |  |   ____   ______
		  |    |  \__  \ | __ \|  | _/ __ \ /  ___/
		  |    |   / __ \| \_\ \  |_\  ___/ \___ \ 
		  |____|  (____  /___  /____/\___  >____  >
					   \/    \/          \/     \/  Creation and the Class Constructor*/
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
										  `req_reset` datetime DEFAULT NULL COMMENT 'Reset Date Counter for new Requests',
										  `req_activation` datetime DEFAULT NULL COMMENT 'Activation Date Counter for new Requests',
										  `req_mail_edit` datetime DEFAULT NULL COMMENT 'Last Mail Change Request Date',
										  `block_reset` int(1) DEFAULT NULL COMMENT 'Block Resets for this user',
										  `block_activation` int(1) DEFAULT NULL COMMENT 'Block Activation for this User',
										  `block_mail_edit` datetime DEFAULT NULL COMMENT 'Block Mail Edits for this User',
										  `fails_in_a_row` int(10) DEFAULT 1 COMMENT 'Fail Pass Enters without Success Login',
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
			$this->mysql->query("INSERT INTO ".$this->dt_users." (user_name, user_mail, user_confirmed, user_pass, user_rank)
									VALUES('".$this->mysql->escape($initial)."', '".$this->mysql->escape($initial)."', 1, '".$this->password_crypt($initialpass)."', '".$initialrank."');");}}
	
	// Constructor of the Control Class
	function __construct($mysqlcon, $table_users, $table_sessions, $preecokie = "x_users_", $initial_ref = false, $initial_pass = false, $initial_rank = false) {
		// Init Variables for Runtime
		$this->sessions 		=   $preecokie;	
		$this->mysql			=	$mysqlcon;
		$this->dt_users			=	$table_users;
		$this->dt_keys			=	$table_sessions;
		// Start Session if not Exists and Ban Var Initialize if Not Initialized
		if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
		
		if(!$this->mysql->table_exists($table_users)) { $this->create_table($initial_ref, $initial_pass, $initial_rank); $this->mysql->free_all();  }
		if(!$this->mysql->table_exists($table_sessions)) { $this->create_table(); $this->mysql->free_all();  }}
	
	/* . ____                 .__        
		|    |    ____   ____ |__| ____  
		|    |   /  _ \ / ___\|  |/    \ 
		|    |__(  <_> ) /_/  >  |   |  \
		|_______ \____/\___  /|__|___|  /
				\/    /_____/         \/  Login Request Function with all needed things! */
	public function login_request($ref, $password, $stayLoggedIn = false) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = strtolower(trim($ref));
		$r	=	$this->mysql->query("SELECT * FROM ".$this->dt_users." WHERE LOWER(".$this->login_field.") = ?", $bind);
		if( $f = $this->mysql->fetch_array($r) ) {
			if ( $this->password_check($password, $f["user_pass"]) ) {
				// Exit if user not blocked
				if($f["user_blocked"] == 1) { $this->login_request_code = 4; return 4; } 
				// Exit if User not Confirmed
				if($f["user_confirmed"] != 1) { $this->login_request_code = 5; return 5; } 
				// Generate Session Key Unique for this Users Session
				$newtoken	=	$this->session_gen(32);
				while($this->session_token_valid($f["id"], $newtoken)) {$newtoken =	$this->session_gen(32);}
				// Create the Key now as actual Session
				$this->session_token_create($f["id"], $newtoken);
				// Apply Cookies if Set and Activated in Conf
				if($stayLoggedIn) { $this->cookie_add($f["id"], $newtoken); }
				// Update Last Login Date
				$this->mysql->query("UPDATE ".$this->dt_users." SET last_login = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'");
				// Set needed Data
				$_SESSION[$this->sessions."x_users_ip"]  = @$_SERVER["REMOTE_ADDR"];
				$_SESSION[$this->sessions."x_users_key"] = $newtoken;
				$_SESSION[$this->sessions."x_users_id"]  = $f["id"];
				if($stayLoggedIn) { $_SESSION[$this->sessions."x_users_stay"]  = true; } else { $_SESSION[$this->sessions."x_users_stay"] = false; }
				$this->object_user_set($f["id"]);
				// Drop Recover Codes if Setup in Config
				if($this->login_recover_drop) {
					if(!$this->log_recover) {
						 $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_recover."'");
					} else {$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_recover."'"); }
				}
				// Log Activation Keys on Successfull Login
				if(!$this->log_activation) {
					 $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_activation."'");
				} else {$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE fk_user = '".$f["id"]."' AND key_type = '".$this->key_activation."'"); }
				// Set Ref to Logged in User (Senseles)
				// Set Ref Info
				$f["token"] 	= $newtoken;
				$this->internal_ref_set($f);
				// Delete Fails in a Row
				$this->mysql->query("UPDATE ".$this->dt_users." SET fails_in_a_row = 0 WHERE id = '".$f["id"]."'");
				//Return OK
				$this->login_request_code = 1; return 1;
			} else { 
				// Update Fails in a Row Counter and Apply Autoblock
				$this->mysql->query("UPDATE ".$this->dt_users." SET fails_in_a_row = fails_in_a_row + 1 WHERE id = '".$f["id"]."'");  
				// Autoblock and Return Code
				if(is_numeric($this->autoblock)) { if($f["fails_in_a_row"] > $this->autoblock) { $this->block_user($f["id"]); $this->mysql->query("UPDATE ".$this->dt_users." SET last_block = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'"); $this->login_request_code = 6; return 6;}} 
				// Wrong Password Exit
				$this->login_request_code = 3; return 3;}	
		} $this->login_request_code = 2; return 2;} // No-Ref Exit

	## Logout Function
	public function logout() { @$this->session_logout(); @$this->cookie_unset(); @$this->object_user_unset(); return true; }		
	
	## Init Function
	public function init() {
		if($this->login_field == "user_name") { $this->user_unique = true; }
		if($this->login_field == "user_mail") { $this->mail_unique = true; }
		if(@$_SESSION[$this->sessions."x_users_ip"] == @$_SERVER["REMOTE_ADDR"]
			AND isset($_SESSION[$this->sessions."x_users_key"])
			AND is_bool($_SESSION[$this->sessions."x_users_stay"])
			AND is_numeric($_SESSION[$this->sessions."x_users_id"])) {
				if(!$this->session_token_valid($_SESSION[$this->sessions."x_users_id"], $_SESSION[$this->sessions."x_users_key"])) {
					$this->object_user_unset();
					$this->cookie_restore();} 
				else { $this->session_restore(); }
		} else {
			$this->object_user_unset();
			$this->cookie_restore(); }}		
	
	## User Unset All Data
	private function object_user_unset() {
		$this->internal_ref_reset();
		unset($_SESSION[$this->sessions."x_users_ip"]);
		unset($_SESSION[$this->sessions."x_users_key"]);
		unset($_SESSION[$this->sessions."x_users_id"]);
		unset($_SESSION[$this->sessions."x_users_stay"]);
		$this->user_rank = false; $this->rank = false;
		$this->user_id = false; $this->id = false;
		$this->user_name = false; $this->name = false;
		$this->user_mail = false; $this->mail = false;
		$this->loggedIn = false; $this->loggedin = false;
		$this->user_loggedIn = false; $this->user_loggedin = false;
		$this->user = false;
	}	
	
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
		$this->user_name = $tmp["user_name"]; 	$this->name = $tmp["user_name"];
		$this->user_mail = $tmp["user_mail"]; 	$this->mail = $tmp["user_mail"];
		$this->loggedIn = true; $this->loggedin = true;
		$this->user_loggedIn = true; $this->user_loggedin = true;
		$this->user = $tmp;}

	/*      _____          __  .__               __  .__               
		  /  _  \   _____/  |_|__|__  _______ _/  |_|__| ____   ____  
		 /  /_\  \_/ ___\   __\  \  \/ /\__  \\   __\  |/  _ \ /    \ 
		/    |    \  \___|  | |  |\   /  / __ \|  | |  (  <_> )   |  \
		\____|__  /\___  >__| |__| \_/  (____  /__| |__|\____/|___|  /
				\/     \/                    \/                    \/    */
	public function activation_request_id($id) { $this->internal_ref_reset(); // Admin can do Activation here!
		// Check if User ID Numeric
		if(!is_numeric($id)) { $this->act_request_code = 2; return 2; }
		$r = $this->mysql->query("SELECT * FROM ".$this->dt_users."  WHERE id = \"".$id."\"");
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
		$r = $this->mysql->query("SELECT * FROM ".$this->dt_users."  WHERE LOWER(".$this->login_field.") = ?", $bind);
		while($f=$this->mysql->fetch_array($r)){
			if($f["user_confirmed"] == 1) { $this->act_request_code = 4; return 4; }
			// Check if Interval for new Request is Okay
			if(is_numeric($this->wait_activation_min)) {if(isset($f["req_activation"])) { if ($this->check_interval($f["req_activation"], '-'.$this->wait_activation_min.' minutes')) {$this->act_request_code = 3; return 3;}}}
			// Check if Activation is Blocked
			if($f["block_activation"] == 1) {  $this->act_request_code = 5; return 5; }
			// Update Req Activation Date
			$this->mysql->query("UPDATE ".$this->dt_users." SET req_activation = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'");
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
		$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE session_key = ? AND key_type = '".$this->key_activation."' AND fk_user = '".$userid."' AND is_active = 1", $bind);
		if($f= $this->mysql->fetch_array($r)){
			// Blocked for Activation
			if($f["block_activation"] == 1) {  $this->act_request_code = 4; return 4; }
			// Interval not Reached
			if(!$this->activation_token_valid($userid, $token)) { $this->act_request_code = 3; return 3;}
			// Log Activation Token
			if($this->log_activation) {$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");	
			} else { $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");}
			// Set Ref Info
			$f["token"] 	= $token;
			$this->internal_ref_set($f);
			// Update Last Activation
			$this->mysql->query("UPDATE ".$this->dt_users." SET last_activation = CURRENT_TIMESTAMP() WHERE id = '".$userid."'");
			// Confirm the User
			$this->mysql->query("UPDATE ".$this->dt_users." SET user_confirmed = 1 WHERE id = '".$userid."'");
			// Delete the Shadow Mail from users which may have tried to Register that Mail
			if($this->mail_unique) { $this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = NULL WHERE LOWER(user_shadow) = '".strtolower(trim($f["user_mail"]))."'"); }
			// Change User Password
			if($newpass) { $this->changeUserPass($f["fk_user"], $newpass); }
			// All Okay and Return
			$this->act_request_code = 1; return 1;
		} else { $this->act_request_code = 2; return 2; }} // Ref not Found
		
	/* 	__________                                       
		\______   \ ____   ____  _______  __ ___________ 
		 |       _// __ \_/ ___\/  _ \  \/ // __ \_  __ \
		 |    |   \  ___/\  \__(  <_> )   /\  ___/|  | \/
		 |____|_  /\___  >\___  >____/ \_/  \___  >__|   
				\/     \/     \/                \/  	*/
	## Recover Request for Admin
	public function recover_request_id($id) { $this->internal_ref_reset();
		// Not numeric ID
		if(!is_numeric($id)) { $this->internal_ref_set($f); $this->rec_request_code = 2; return 2; }
		$r = $this->mysql->query("SELECT * FROM ".$this->dt_users."  WHERE id = \"".$id."\"");
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
		$r = $this->mysql->query("SELECT * FROM ".$this->dt_users."  WHERE LOWER(".$this->login_field.") = ?", $bind);
		while($f=$this->mysql->fetch_array($r)){
			// Check if Blocked for Reset
			if($f["block_reset"] == 1) {  $this->internal_ref_set($f); $this->rec_request_code = 4; return 4; }
			// Check if Interval for new Reset reached
			if(is_numeric($this->wait_recover_min)) {
				if(isset($f["req_reset"]) AND $f["req_reset"] != NULL) { if ($this->check_interval($f["req_reset"], '-'.$this->wait_recover_min.' minutes')) {$this->internal_ref_set($f); $this->rec_request_code = 3;return 3;}}
			}
			// Recover Request Reset Counter
			$this->mysql->query("UPDATE ".$this->dt_users." SET req_reset = CURRENT_TIMESTAMP() WHERE id = '".$f["id"]."'");
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
		if(!is_numeric($userid)) { $this->internal_ref_set($f); $this->rec_request_code = 2; return false; }
		$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE session_key = ? AND key_type = '".$this->key_recover."' AND fk_user = '".$userid."' AND is_active = 1", $bind);
		if($f= $this->mysql->fetch_array($r)){
			// If Blocked for Reset stop!
			if($f["block_reset"] == 1) {  $this->internal_ref_set($f); $this->rec_request_code = 4; return 4; }
			// Check for Interval
			if(!$this->recover_token_valid($userid, $token)) { $this->rec_request_code = 3; return 3; }
			// Log Recovers
			if($this->log_recover) {$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_recover."'");	
			} else { $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_recover."'");}			
			// Set Ref Info
			$f["token"] 	= $token;
			$this->internal_ref_set($f);
			// Update Last Reset Var
			$this->mysql->query("UPDATE ".$this->dt_users." SET last_reset = CURRENT_TIMESTAMP() WHERE id = '".$userid."'");
			// Last Activation Date Update
			$this->mysql->query("UPDATE ".$this->dt_users." SET last_activation = CURRENT_TIMESTAMP() WHERE id = '".$userid."' AND activation_date IS NULL");
			// Confirm User on Recover Password
			$this->mysql->query("UPDATE ".$this->dt_users." SET user_confirmed = 1 WHERE id = '".$userid."'");
			// Log Activation Token
			if($this->log_activation) {$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");	
			} else { $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_activation."'");}
			// Delete the Shadow Mail from users which may have tried to Register that Mail
			if($this->mail_unique) { $this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = NULL WHERE LOWER(user_shadow) = '".strtolower(trim($f["user_mail"]))."'"); }
			// Change User Password
			$this->changeUserPass($f["fk_user"], $newpass);
			// All Okay
			$this->rec_request_code = 1; return 1;
		} else {$this->rec_request_code = 2; return 2;}} // Ref not Found		
		
	/*      _____         .__.__    ___________    .___.__  __   
		  /     \ _____  |__|  |   \_   _____/  __| _/|__|/  |_ 
		 /  \ /  \\__  \ |  |  |    |    __)_  / __ | |  \   __\
		/    Y    \/ __ \|  |  |__  |        \/ /_/ | |  ||  |  
		\____|__  (____  /__|____/ /_______  /\____ | |__||__|  
				\/     \/                  \/      \/         */		
	# Mail Edit with Confirmation
	public function mail_edit($id, $newmail, $nointervall = false) { $this->internal_ref_reset(); $bind[0]["type"] = "s"; $bind[0]["value"] = trim(strtolower($newmail));	
		// Return if user not numeric
		if(!is_numeric($id)) { $this->mc_request_code = 2;return 2; }				
		// Proceed
		$r = $this->mysql->query("SELECT * FROM ".$this->dt_users."  WHERE id = \"".$id."\"");
		if($f=$this->mysql->fetch_array($r)){
			// Mail Edit Blocked for User
			if($f["block_mail_edit"] == 1) {  $this->internal_ref_set($f); $this->mc_request_code = 5; return 5; }
			// Check if Interval for new Mail Edit is Okay or Deactivated in Function (For Admin)
			if(!$nointervall) { if(is_numeric($this->wait_activation_min)) {if(isset($f["req_mail_edit"])) {if ($this->check_interval($f["req_mail_edit"], '-'.$this->wait_activation_min.' minutes')) {$this->internal_ref_set($f);$this->mc_request_code = 3;return 3; }}} }
			// Change the Users Mail
			if(!$this->changeUserShadowMail($id, trim($newmail))) { $this->mc_request_code = 4; return 4; } // Mail Exists on Once Active User
			// Log Mail Edits
			if($this->log_mail_edit) {$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE fk_user = ".$f["id"]." AND key_type = '".$this->key_mail_edit."'");	
			} else { $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = ".$f["id"]." AND key_type = '".$this->key_mail_edit."'");};
			// Update last Mail Edit
			$this->mysql->query("UPDATE ".$this->dt_users." SET req_mail_edit = CURRENT_TIMESTAMP() WHERE id = '".$userid."'");
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
			$r = $this->mysql->query("SELECT * FROM ".$this->dt_keys." WHERE session_key = ? AND key_type = '".$this->key_mail_edit."' AND fk_user = '".$userid."' AND is_active = 1", $bind);
			if($f= $this->mysql->fetch_array($r)){
				// Stop if Blocked for Mail Edit
				if($f["block_mail_edit"] == 1) {  $this->internal_ref_set($f);$this->mc_request_code = 5; return 5; }
				// Check for Interval (token expire)
				if(!$this->mail_edit_token_valid($userid, $token)) { $this->internal_ref_set($f);$this->mc_request_code = 3; return 3; }
				// Log Activation
				if($this->log_mail_edit) {$this->mysql->query("UPDATE ".$this->dt_keys." SET is_active = 0 WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_mail_edit."'");	
				} else { $this->mysql->query("DELETE FROM ".$this->dt_keys." WHERE fk_user = ".$f["fk_user"]." AND key_type = '".$this->key_mail_edit."'");}				
				// Set Ref Info
				$f["token"] 	= $token;
				$this->internal_ref_set($f);				
				// Change Mail and Initiate Clearing
				$x = $this->mysql->query("SELECT * FROM ".$this->dt_users."  WHERE id = \"".$userid."\"");
				if($xf=$this->mysql->fetch_array($x)) {
					// Another Account has Changed to this mail or registered
					if($xf["user_shadow"] == NULL OR trim($xf["user_shadow"]) == "") { $this->internal_ref_set($f);$this->mc_request_code = 4; return 4;  }										

					// Process Request
					if(!$this->mail_unique) {
						// Mail already Existant otherwhise change
						if(!$this->changeUserMail($f["fk_user"], $xf["user_shadow"])) { $this->internal_ref_set($f);$this->mc_request_code = 6; return 6; } 							
						
						// Update Last Edit Date
						$this->mysql->query("UPDATE ".$this->dt_users." SET last_mail_edit = CURRENT_TIMESTAMP() WHERE id = '".$xf["id"]."'");

						// Set Shadow Mail to Null
						$this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = NULL WHERE id = '".$userid."'");
					} else {
						// Delete Unconfirmed Account if Exists
						$this->mysql->query("DELETE FROM ".$this->dt_users." WHERE LOWER(user_mail) = '".$this->mysql->escape(strtolower(trim($xf["user_shadow"])))."' AND user_confirmed = 0");
						
						// Mail already Existant otherwhise change
						if(!$this->changeUserMail($f["fk_user"], $xf["user_shadow"])) { $this->internal_ref_set($f);$this->mc_request_code = 6; return 6; }							
						
						// Update Edit Counter
						$this->mysql->query("UPDATE ".$this->dt_users." SET last_mail_edit = CURRENT_TIMESTAMP() WHERE id = '".$xf["id"]."'");						
						
						// Reset Other Mails who have this Mail if not Unique!
						$this->mysql->query("UPDATE ".$this->dt_users." SET user_shadow = NULL WHERE LOWER(user_shadow) = '".$this->mysql->escape(strtolower(trim($xf["user_shadow"])))."'");
					}
				}
				// All Okay
				$this->mc_request_code = 1; return 1;
			} else { $this->mc_request_code = 2; return 2;} // Ref not Found
		}
	}

	/*	________  .__               .__                
		\______ \ |__| ____________ |  | _____  ___.__.
		 |    |  \|  |/  ___/\____ \|  | \__  \<   |  |
		 |    `   \  |\___ \ |  |_> >  |__/ __ \\___  |
		/_______  /__/____  >|   __/|____(____  / ____|
				\/        \/ |__|             \/\/    	*/
		// Display Messages
		public $display_return_code	=	false;
		public $display_return_type	=	false;
		
		// Internal Function to set Messages
		private function display_message_set($code, $type) {$this->display_return = $code;}	
		
		// Display Recover Window
		function display_recover($title, $backbuttonurl = false, $reference = "Mail", $buttonstring = "Reset Password", $buttonbackstring = "Back to Login") {
			if (isset($_POST["x_class_user_submit_recover"])) {
				if (!empty($_POST["x_class_user_reference"]) AND trim($_POST["x_class_user_reference"]) != "") {
					if ($_SESSION["x_class_user_csrf"] == $_POST["x_class_user_csrf"] AND trim($_POST["x_class_user_csrf"]) != "" AND isset($_POST["x_class_user_csrf"])) {
						$req_return = $object["user"]->recover_request($_POST["x_class_user_reference"]);		
						if ($this->rec_request_code == 1 AND $req_return == 1) { $this->display_message_set("ok", "ok"); return true; }
						if ($this->rec_request_code == 2 AND $req_return == 2) { $this->display_message_set("unknown", "error"); return true; }
						if ($this->rec_request_code == 3 AND $req_return == 3) { $this->display_message_set("interval", "error"); return true; }
						if ($this->rec_request_code == 4 AND $req_return == 4) { $this->display_message_set("blocked", "error"); return true; }
					} else { $this->display_message_set("expired", "error"); return true; }		
				} else { $this->display_message_set("empty", "error"); return true; }
			}  ?>
			<div class="x_class_user_login"><div class="x_class_user_login_title"><?php $csrf = mt_rand(10000,999999); $_SESSION["x_class_user_csrf"] = $csrf; echo $title; ?></div><div class="x_class_user_login_inner"><form method="post"><input type="hidden" name="x_class_user_csrf" value="<?php echo $csrf; ?>">
				<div class="x_class_user_login_group">
					<label class="x_class_user_login_label"><?php echo $reference; ?></label><br />
					<input type="text" maxlength="255" placeholder="<?php echo $reference; ?>" name="x_class_user_reference" class="x_class_user_input" tabindex="1" autofocus="autofocus">
				</div>				
				<div class="x_class_user_form_group">			
					<input type="submit" class="x_class_user_input" value="<?php echo $buttonstring; ?>" tabindex="2" name="x_class_user_submit_recover">
					<?php if($backbuttonurl) { ?>	<br /><a class="x_class_user_link" href="<?php echo $backbuttonurl; ?>"><?php echo $buttonbackstring; ?></a> <?php } ?>
				</div>	
			</form></div></div><?php
		}	
		
		// Display Login
		public function display_login($registerbuttonurl = false, $registerbuttonstring = "Register", $cookiecheckbox = false, $resetbuttonurl = false, $resetbuttonstring = "Reset",  $title = "Login", $label = "E-Mail") {			
			if (isset($_POST["x_class_user_submit_login"])) {
				if (!empty($_POST["x_class_user_ref"]) AND !empty($_POST["x_class_user_pass"])) {
					if ($_SESSION["x_class_user_csrf"] == $_POST["x_class_user_csrf"] AND trim($_POST["x_class_user_csrf"]) != "" AND isset($_POST["x_class_user_csrf"])) {
						$result = $this->login_request($_POST["x_class_user_ref"], $_POST["x_class_user_pass"], $_POST["x_class_user_submit_login_stay"]);
						if ($result == 1 AND $this->login_request_code = 1 ) { $this->display_message_set("ok", "ok"); return true; }
						if ($result == 2 AND $this->login_request_code = 2 ) { $this->display_message_set("unknown", "error"); return true; }
						if ($result == 3 AND $this->login_request_code = 3 ) { $this->display_message_set("wrongpass", "error"); return true; }
						if ($result == 4 AND $this->login_request_code = 4 ) { $this->display_message_set("blocked", "error"); return true; }
						if ($result == 5 AND $this->login_request_code = 5 ) { $this->display_message_set("unconfirmed", "error"); return true; }
					} else { $this->display_message_set("expired", "error");  return true; }
				} else { $this->display_message_set("empty", "error"); return true;  }
			}?>
			<div class="x_class_user_login"><div class="x_class_user_login_title"><?php $csrf = mt_rand(10000,999999); $_SESSION["x_class_user_csrf"] = $csrf; echo $title; ?></div><div class="x_class_user_login_inner"><form method="post"><input type="hidden" name="x_class_user_csrf" value="<?php echo $csrf; ?>">
				<div class="x_class_user_login_group">
					<label class="x_class_user_login_label"><?php echo $label; ?></label><br />
					<input type="text" maxlength="255" tabindex="1" placeholder="Please enter your <?php echo $label; ?>!" name="x_class_user_ref" autofocus="autofocus"><br />
					<label class="x_class_user_login_label">Password:</label ><br />	
					<input type="password" maxlength="128" tabindex="2" placeholder="Please enter your password!" name="x_class_user_pass" autocomplete="off">	
				</div>					
				<div class="x_class_user_form_group">
					<input type="submit" value="Login" tabindex="3" name="x_class_user_submit_login">
					<?php if($cookiecheckbox) { ?> 	<input type="checkbox" name="x_class_user_submit_login_stay"> <?php } ?>
					<?php if($registerbuttonurl) { ?>	<br /><a class="x_class_user_link" href="<?php echo $registerbuttonurl; ?>"><?php echo $registerbuttonstring; ?></a> <?php } ?>
					<?php if($resetbuttonurl) { ?>	<br /><a class="x_class_user_link" href="<?php echo $resetbuttonurl; ?>"><?php echo $resetbuttonstring; ?></a> <?php } ?>
				</div>
			</form></div></div><?php
		}	
	
		// Display Activation Form With Password Set
		function display_reset($title = "Reset", $backbuttonurl = false, $buttonbackstring = "Back to Login") {	
				if ($this->recover_token_valid($_GET["x_class_user_rec_user"], $_GET["x_class_user_rec_token"])) {
					if(isset($_POST["x_class_user_submit_reset"])) {
						if ($_SESSION["x_class_user_csrf"] == $_POST["x_class_user_csrf"] AND trim($_POST["x_class_user_csrf"]) != "" AND isset($_POST["x_class_user_csrf"])) {
							if ($_POST["x_class_user_pass"] == $_POST["x_class_user_pass_confirm"] and trim($_POST["x_class_user_pass"]) != "") {
								$resq	=	$this->recover_confirm($_POST["x_class_user_rec_user"], $_POST["x_class_user_rec_token"], $_POST["x_class_user_pass"]);
								if ($this->rec_request_code == 1 AND $resq == 1) { $this->display_message_set("ok", "ok"); return true; }
								if ($this->rec_request_code == 2 AND $resq == 2) { $this->display_message_set("unknown", "error"); return true; }
								if ($this->rec_request_code == 3 AND $resq == 3) { $this->display_message_set("interval", "error"); return true; }
								if ($this->rec_request_code == 4 AND $resq == 4) { $this->display_message_set("blocked", "error"); return true; }
							} else { if ($_POST["x_class_user_pass"] != $_POST["x_class_user_pass_confirm"]) { $this->display_message_set("passmatch", "error"); return true; } else { $this->display_message_set("empty", "error"); return true; } } 
						} else { $this->display_message_set("expired", "error"); return true; } 
					}
				} else { $this->display_message_set("interval", "error"); return true; } 
			?>
			<div class="x_class_user_login"><div class="x_class_user_login_title"><?php $csrf = mt_rand(10000,999999); $_SESSION["x_class_user_csrf"] = $csrf; echo $title; ?></div><div class="x_class_user_login_inner"><form method="post"><input type="hidden" name="x_class_user_csrf" value="<?php echo $csrf; ?>">
				<input type="hidden" name="x_class_user_rec_token" value="<?php echo @$_GET["x_class_user_rec_token"]; ?>">
				<input type="hidden" name="x_class_user_rec_user" value="<?php echo @$_GET["x_class_user_rec_user"]; ?>">		
				<div class="x_class_user_login_group">
					<label class="x_class_user_login_label">Password:</label><br />
					<input type="password" maxlength="128" tabindex="1" placeholder="Please enter your password!" name="x_class_user_pass" autocomplete="off">	
					<label class="x_class_user_login_label">Password Confirmation:</label><br />
					<input type="password" maxlength="128" tabindex="2" placeholder="Please enter your password!" name="x_class_user_pass_confirm" autocomplete="off">	
				</div>		
				<div class="x_class_user_form_group">
					<input type="submit" value="Login" tabindex="3" name="x_class_user_submit_reset">
					<?php if($backbuttonurl) { ?>	<br /><a class="x_class_user_link" href="<?php echo $backbuttonurl; ?>"><?php echo $buttonbackstring; ?></a> <?php } ?>
				</div>	
			</form></div></div><?php
		}	
		
		// Display Recover
		function display_register_unique_mail($title = "Register", $backbuttonurl = false, $buttonbackstring = "Back to Login", $needusername = false, $captchaurl = false, $captchakey = false, $rank = 0, $confirmed = 0) {
				if (isset($_POST["x_class_user_submit_register"])) {
					if (!empty($_POST["x_class_user_mail"]) AND !empty($_POST["x_class_user_pass"])) {
						if ($captchakey == $_POST["x_class_user_csrf"] AND trim($_POST["x_class_user_csrf"]) != "" AND isset($_POST["x_class_user_csrf"])) {
							if(($needusername AND isset($_POST["x_class_user_name"]) AND trim($_POST["x_class_user_name"])) != "" OR (!$needusername)) {
								if(!$needusername) { $_POST["x_class_user_name"] = "undefined";}
								if(addUser($name, $_POST["x_class_user_mail"], $_POST["x_class_user_pass"], $rank, $confirmed, true)) { $this->display_message_set("ok", "ok"); return true; }
								else {$this->display_message_set("error", "error"); return true; }
							
							} else { $this->display_message_set("empty", "error"); return true; }
						} else { $this->display_message_set("expired", "error"); return true; }
					} else { $this->display_message_set("empty", "error"); return true; }
				}
			?>
			<div class="x_class_user_login"><div class="x_class_user_login_title"><?php $csrf = mt_rand(10000,999999); $_SESSION["x_class_user_csrf"] = $csrf; echo $title; ?></div><div class="x_class_user_login_inner"><form method="post"><input type="hidden" name="x_class_user_csrf" value="<?php echo $csrf; ?>">
				<div class="x_class_user_login_group">
						<?php if($needusername) { ?><label class="x_class_user_login_label">Username*</label><br />
						<input type="text" maxlength="255" tabindex="1" placeholder="Username" name="x_class_user_name" tabindex="1" autofocus="autofocus"><br />	<?php } ?>				

						<label class="x_class_user_login_label">E-Mail*</label><br />
						<input type="text" maxlength="255" tabindex="1" placeholder="Mail-Adress" name="x_class_user_mail" tabindex="2" autofocus="autofocus"><br />
						
						<label class="x_class_user_login_label">Password*</label><br />
						<input type="password" maxlength="128" tabindex="2" placeholder="Password" name="x_class_user_pass" tabindex="3" autocomplete="off"><br />
						
						<label class="x_class_user_login_label">Confirm Password*</label><br />
						<input type="password" maxlength="128" tabindex="2" placeholder="Confirm Password" name="x_class_user_pass_confirm" tabindex="4" autocomplete="off"><br />	

						<?php if($captchaurl) { ?><label class="x_class_user_login_label">Captcha*</label><br />
						<img src="<?php echo $captchaurl; ?>"><br />
						<input type="text" maxlength="255" tabindex="1" placeholder="Username" name="x_class_user_captcha" tabindex="1" autofocus="autofocus"><br /><?php } ?>
				</div>
				<div class="x_class_user_form_group">
					<input type="submit" value="Login" tabindex="3" name="x_class_user_submit_register">
					<?php if($backbuttonurl) { ?>	<br /><a class="x_class_user_link" href="<?php echo $backbuttonurl; ?>"><?php echo $buttonbackstring; ?></a> <?php } ?>
				</div>	
			</form></div></div><?php
		}	
	
	}
