<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ X Function Set	*/
	//////////////////////////////////////////////
	// Param and Check Functions
	//////////////////////////////////////////////
		function x_isset($val) {if(trim(@$val) != '' AND strlen(@$val) > 0 ) {return true;} else {return false;}} ## Check if a value is not null and strlen more than 1
		
	//////////////////////////////////////////////
	// Image Checks
	//////////////////////////////////////////////
		function x_imgValid($url) {if(!isset($url)) {return false;}else {if(is_string(trim($url)) AND strlen($url) > 3) {return @getimagesize($url);} else {return false;}} }
		
	//////////////////////////////////////////////
	// Replacement for HTMLSPECIALCHARS
	//////////////////////////////////////////////	
	function x_hsc($string) {
		return htmlspecialchars(@$string);
	}
	
	//////////////////////////////////////////////
	// Word Filter Functions
	//////////////////////////////////////////////
		function x_contains_cyrillic($val)  ## Check if a String contains cyrillic chars
			{$contains_cyrillic = (bool) preg_match('/[\p{Cyrillic}]/u', $val);if ($contains_cyrillic) { return true; } else {return false;}}
		function x_contains_bad_word($val) { ## Check if String Contains bad Words by Filter
				if(strpos($val, " porn ") !== false){ return false; }
				if(strpos($val, " Porn ") !== false){ return false; }
			  return true;}
		function x_contains_url($val) { ## Check if String Contains URL
				if(strpos($val, "http://") !== false){ return false; }
				if(strpos($val, "https://") !== false){ return false; }
			  return true;}
			  
	//////////////////////////////////////////////
	// Post and Get Parameter Functions
	//////////////////////////////////////////////
		function x_getint($val) { if(is_numeric(@$_GET[$val])) { return @$_GET[$val];} else { return false;}} ## Get a GET value if INT
		function x_postint($val) { if(is_numeric(@$_POST[$val])) { return @$_POST[$val];} else { return false;}} ## Get a POST value if INT
		function x_get($val) {if(isset($_GET[$val])) { return @$_GET[$val];} else { return false;}} ## Get a GET value
		function x_post($val) {if(isset($_POST[$val])) { return @$_POST[$val];} else { return false;}} ## Get a POST value

	//////////////////////////////////////////////
	// Date Validation
	//////////////////////////////////////////////
		function x_datediff_before($d1, $d2, $length)  ## x_datediff_before($d1, $d2, $length) Check if d1 is difference as length with d2 
			{if($d1 == false OR $d2 == false) { return false; } {}$interval = @date_diff(@date_create($d1), @date_create($d2));if( @$interval->format('%a') > $length ) { return true;} return false;}
