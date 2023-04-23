<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Nextcloud API Class */	
	class x_class_nextcloud {
		// Authentication Variables
		private $url = false;
		private $username = false;
		private $password = false;		
		private $x_class_curl = false;		
		private $settings = array();		
		public $last_return = false;

		// Construct and Generate a Session Key
		function __construct($x_class_curl, $nextcloudurl, $username, $password) {
			// Setup Provided Variables
			$this->url = $nextcloudurl;
			$this->username = $username;
			$this->password = $password;
			$this->x_class_curl = $x_class_curl;
			
			// Default Settings Configuration
			$this->settings = array();
			$this->settings["CURLOPT_HTTPAUTH"] = CURLAUTH_BASIC;
			$this->settings["CURLOPT_USERPWD"] = $this->username.":".$this->password;
			$this->settings["CURLOPT_RETURNTRANSFER"] = true;
			$this->settings["CURLOPT_HEADER"] = true;
			$this->settings["CURLOPT_HTTPHEADER"] = array(
				'Content-Type:application/json',
				'Authorization: Basic '. base64_encode($this->username.":".$this->password) // <---
			);	
		}
		
		// Add share ID to File
		public function share_id_create($filename, $username, $sharetype = 0) {			
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
				$settings["CURLOPT_POSTFIELDS"] =  "path=".urlencode(trim($filename))."&shareType=".urlencode(trim($sharetype))."&shareWith=".urlencode(trim($username))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			} else {
				$settings = $this->settings;
				$settings["CURLOPT_POSTFIELDS"] =  "path=".urlencode(trim($filename))."&shareType=".urlencode(trim($sharetype))."&shareWith=".urlencode(trim($username))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			}
			$this->last_return = $this->x_class_curl->request($this->url."/ocs/v2.php/apps/files_sharing/api/v1/shares", "POST", $settings);
			return $this->last_return;}	
		
		// Delete an Account
		public function account_delete($username) {		
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			} else {
				$settings = $this->settings;
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			}
			$this->last_return = $this->x_class_curl->request($this->url."/ocs/v1.php/cloud/users/".urlencode(trim($username)), "DELETE", $settings);
			return $this->last_return;}				
		
		// Revoke Share ID
		public function share_id_revoke($id) {
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			} else {
				$settings = $this->settings;
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/ocs/v2.php/apps/files_sharing/api/v1/shares/".urlencode(trim($id)), "DELETE", $settings);
			return $this->last_return;}		
		
		// Change Account Mail
		function account_mail_change($username, $mail) {
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
					$settings["CURLOPT_POSTFIELDS"] =  "email=".urlencode(trim($mail))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			} else {
				$settings = $this->settings;
					$settings["CURLOPT_POSTFIELDS"] =  "email=".urlencode(trim($mail))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/ocs/v1.php/cloud/users/".urlencode(trim($username)), "PUT", $settings);
			return $this->last_return;}		
			
		// Change Account Display Name
		function account_displayname_change($username, $name) {
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
					$settings["CURLOPT_POSTFIELDS"] =  "displayname=".urlencode(trim($name))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			} else {
				$settings = $this->settings;
					$settings["CURLOPT_POSTFIELDS"] =  "displayname=".urlencode(trim($name))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/ocs/v1.php/cloud/users/".urlencode(trim($username)), "PUT", $settings);
			return $this->last_return;}		
		
		// Change Account Password
		function account_password_change($username, $userpass) {
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
					$settings["CURLOPT_POSTFIELDS"] =  "password=".urlencode(trim($userpass))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			} else {
				$settings = $this->settings;
					$settings["CURLOPT_POSTFIELDS"] =  "password=".urlencode(trim($userpass))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/ocs/v1.php/cloud/users/".urlencode(trim($username)), "PUT", $settings);
			return $this->last_return;}
		
		// Create Nextcloud account
		function account_create($username, $displayname, $usermail, $userpass, $moresettings = false) {
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
					$settings["CURLOPT_POSTFIELDS"] =  "userid=".urlencode(trim($username))."&quota=0&password=".urlencode(trim($userpass))."&displayName=".urlencode(trim($displayname))."&email=".urlencode(trim($usermail))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			} else {
				$settings = $this->settings;
					$settings["CURLOPT_POSTFIELDS"] =  "userid=".urlencode(trim($username))."&quota=0&password=".urlencode(trim($userpass))."&displayName=".urlencode(trim($displayname))."&email=".urlencode(trim($usermail))."";
					$settings["CURLOPT_HTTPHEADER"] = array(
						//'Content-Type:application/json',
						'Authorization: Basic '. base64_encode($this->username.":".$this->password), // <---
						"OCS-APIRequest: true"
					);	
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/ocs/v1.php/cloud/users", "POST", $settings);
			return $this->last_return;}
		
		// Create a Folder
		function folder_create($foldername, $moresettings = false) {
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
			} else {
				$settings = $this->settings;
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/"."remote.php/dav/files/"."/".urlencode($this->username)."/".urlencode($foldername), "MKCOL", $settings);
			return $this->last_return;}
		//Get Folder Items
		function folder_get_items($foldername, $moresettings = false) {	
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
			} else {
				$settings = $this->settings;
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/"."remote.php/dav/files/"."/".urlencode($this->username)."/".urlencode($foldername), "PROPFIND", $settings);
			return $this->last_return;}
		// Upload a file to nextcloud
		function file_upload($localfile, $remotefile, $moresettings = false) {		
			if(is_array) {
				$settings = $this->settings;
				$settings = @array_merge($settings, $moresettings);
				$settings["CURLOPT_POSTFIELDS"] = file_get_contents($localfile);
			} else {
				$settings = $this->settings;
				$settings["CURLOPT_POSTFIELDS"] = file_get_contents($localfile);
			}	
			$this->last_return = $this->x_class_curl->request($this->url."/"."remote.php/dav/files/"."/".urlencode($this->username)."/".urlencode($remotefile), "PUT", $settings);
			return $this->last_return;}	
	}
	