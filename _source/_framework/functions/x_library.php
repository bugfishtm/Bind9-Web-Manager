<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ X Function Set	*/
				
				
	###################################
	### Get First Image from Text   ###					
	function x_firstimagetext($text, $all = false) {
		@preg_match_all('/<img[^>]+>/i', $text, $result11); 
		@preg_match_all('/(src)=("[^"]*")/i', $result11[0][0], $img);
		if($all) { return $img[0]; }
		$x	=	trim($img[0][0]);
		if (trim($x) != "") { return $x; } 
		return false;
	}
	
	############################ 
	### Check a Connection   ###
	function x_connection_check($host, $port, $timeout = 1) {$f = @fsockopen($host, $port, $errno, $errstr, $timeout);if ($f !== false) {$res = fread($f, 1024) ;if (strlen($res) > 0 && strpos($res,'220') === 0){@fclose($f);return true;}else{@fclose($f);return false;}} return false;}

	//////////////////////////////////////////////
	// Check if Script is run in CLI
	//////////////////////////////////////////////
	function x_inCLI() {
		$sapi_type = php_sapi_name();
		if (substr($sapi_type, 0, 3) == 'cgi') { return true;
		} else { return false; }
	}
	
	############################ 
	### Recursive Delete     ###
	function x_rmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir") {
						x_rmdir($dir . "/" . $object); 
					} else {
						unlink($dir . "/" . $object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	//////////////////////////////////////////////
	// Redirect Functions
	//////////////////////////////////////////////
		function x_html_redirect($url, $seconds = 0) { echo '<meta http-equiv="refresh" content="'.$seconds.'; url='.$url.'">';}