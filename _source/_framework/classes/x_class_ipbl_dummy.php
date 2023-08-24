<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  IP Blacklisting Control Class */	
	class x_class_ipbl_dummy {
		######################################################
		// Construct
		######################################################
		function __construct() { }

		######################################################
		// Check Current Block Status
		######################################################	
		public function blocked($renew = false) { return false; }
		public function banned($renew = false) { return false; }
		public function isbanned($renew = false) { return false; }
		public function isblocked($renew = false) { return false;  }
			
		######################################################
		// Get Current Ban Table as Array
		######################################################				
		public function get_array() {
			return array();
		}
		
		######################################################
		// Unblcok an UP Adr
		######################################################			
		function unblock($ip) { return false; }
		
		######################################################
		// Get Counter for IP
		######################################################	
		public function get_counter($renew = false) { return 0; }
		public function counter($renew = false) { return 0; }

		######################################################
		// Get Counter for IP
		######################################################	
		public function ip_counter($ip) {return 0;}		
		
		######################################################
		// Raise Counter for Current IP
		######################################################		
		public function raise($value = 1) { return false; }
		public function increase($value = 1) { return false; } }
