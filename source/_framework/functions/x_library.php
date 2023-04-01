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
	
	###################################
	### Create Thumbnail from URL   ###
		function x_thumbnail($url, $filename, $width = 600, $height = true) {
		 $image = ImageCreateFromString(file_get_contents($url));
		 $height = $height === true ? (ImageSY($image) * $width / ImageSX($image)) : $height;
		 $output = ImageCreateTrueColor($width, $height);
		 ImageCopyResampled($output, $image, 0, 0, 0, 0, $width, $height, ImageSX($image), ImageSY($image));
		 ImageJPEG($output, $filename, 95); 
		 return $output; }
		 
	###########################################
	### Create Thumbnail from URL to File   ###		 
	//create_thumbnail_from_url('https://example.com/image.png', 100, 100, '/path/to/thumbnail.png');
		function x_thumbnail_save($url,  $save_path = null, $width, $height) {
		  $image = imagecreatefrompng($url);
		  $thumbnail = imagecreatetruecolor($width, $height);
		  imagecopyresized($thumbnail, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
		  imagedestroy($image);

		  if ($save_path !== null) {
			imagepng($thumbnail, $save_path);
		  }
		  
		  imagedestroy($thumbnail);
		  return true;
		}
	
	############################ 
	### Check a Connection   ###
	function x_connection_check($host, $port, $timeout = 1) {$f = @fsockopen($host, $port, $errno, $errstr, $timeout);if ($f !== false) {$res = fread($f, 1024) ;if (strlen($res) > 0 && strpos($res,'220') === 0){@fclose($f);return true;}else{@fclose($f);return false;}} return false;}
	
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
	// EventBox Functions
	//////////////////////////////////////////////
	function x_eventBoxPrep($text, $type = "x", $precookie = "", $morecss = "", $buttontext = "X", $imgok = false, $imgfail = false, $imgwarn = false, $imgelse = false) {
		if($type == "ok"|| $type == "success") {if($imgok) {$img = "<img src='".$imgok."'>";} else {$img = "";} $morecss = '<div class="x_eventBox" id="x_eventBox_ok_outer"><div id="x_eventBox_ok" class="x_eventBox_inner" style="'.$morecss.'" >'.$img.$text;}
		elseif($type == "warning" || $type == "warn" || $type == "warns") {if($imgwarn) { $img = "<img src='".$imgwarn."'>";}else {$img = "";}$morecss = '<div class="x_eventBox" id="x_eventBox_warning_outer"><div id="x_eventBox_warning" class="x_eventBox_inner" style="'.$morecss.'" >'.$img.$text;}
		elseif($type == "error" || $type == "errors" || $type == "fail") {if($imgfail) {$img = "<img src='".$imgfail."'>";}else {$img = "";}$morecss = '<div class="x_eventBox" id="x_eventBox_error_outer"><div id="x_eventBox_error" class="x_eventBox_inner" style="'.$morecss.'" >'.$img.$text;}
		else {if($imgelse) {$img = "<img src='".$imgelse."'>";}else {$img = "";} $morecss = '<div id="x_eventBox_'.$type.'" class="x_eventBox" style="'.$morecss.'" >'.$img.$text;}				
		$morecss = $morecss."<button class='x_eventBoxButton' onclick='this.parentNode.parentNode.remove()'>".$buttontext."</button></div></div>";
		$_SESSION[$precookie."x_eventbox"] = $morecss;}

	function x_eventBoxShow($precookie = "") { echo @$_SESSION[$precookie."x_eventbox"]; unset( $_SESSION[$precookie."x_eventbox"] ); }
	function x_eventBoxSet($precookie = "") { if(isset($_SESSION[$precookie."x_eventbox"])) { return true; } else { return false; } }

	//////////////////////////////////////////////
	// RSS Functions
	//////////////////////////////////////////////
	
	function x_rss_list($urltemp, $defaultcover, $limit = 25) {
		$rss = new DOMDocument();$rss->load($urltemp);$feed = array();
		foreach ($rss->getElementsByTagName('item') as $node) {
			$item = array ('title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
				'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
				'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
				'img' => $node->getElementsByTagName('enclosure')->item(0)->getAttribute('url'),);
			array_push($feed, $item);}
		for($x=0;$x<$limit;$x++) {
			if(@$feed[$x]['title']) {
			$title = htmlspecialchars($feed[$x]['title']);
			$link = $feed[$x]['link'];
			$cover = $feed[$x]['img'];
			$date = date('Y/m/d H:i:s', strtotime($feed[$x]['date']));
			if($cover == NULL OR trim($cover) == "") { $cover = $defaultcover;}
			echo '<div class="x_rss_item" onclick="location.href = \''.$link.'\';" >';
			if(is_string($cover)) { if( substr($cover, 0, 4) != "http") { $cover = "https://".$cover; } }
			if(trim($cover) != "" AND $cover != NULL AND strlen($cover) > 7) {echo '<img class="x_rss_image" src="'.$cover.'">';}
			echo '<section class="x_rss_title" title="'.$title.'">'.$title.'</section>';
			echo '<section class="x_rss_date">Posted on '.$date.'</section><br clear="left"></div>';}
		}
	}

	function x_rss_array($urltemp) {
		$rss = new DOMDocument();$rss->load($urltemp);$feed = array();
		foreach ($rss->getElementsByTagName('item') as $node) {
			$item = array ('title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
				'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
				'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
				'img' => $node->getElementsByTagName('enclosure')->item(0)->getAttribute('url'),);
			array_push($feed, $item);}
		return $feed;
	}

	//////////////////////////////////////////////
	// CookieBanner Functions
	//////////////////////////////////////////////
	function x_cookieBanner_Pre($precookie = "", $redirect = true) { 
		if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
		if(@$_SESSION[$precookie ."x_cookieBanner"] == true) { return false; }
		$set = false;
			if(@$_POST["x_cookieBanner"] == "submit") { $_SESSION[$precookie ."x_cookieBanner"] = true; $set = true; }
			if(@$_GET["x_cookieBanner"] == "submit")  { $_SESSION[$precookie ."x_cookieBanner"] = true; $set = true; }
			if($set AND $redirect) { Header("Location: ".@$_SERVER['REQUEST_URI']); exit(); }
	}

	function x_cookieBanner($precookie = "", $use_post = false, $text = false) { 
		if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
		if(@$_GET["x_cookieBanner"] == "submit")  { $_SESSION[$precookie ."x_cookieBanner"] = true; }
		if(@$_SESSION[$precookie ."x_cookieBanner"] == true) { return false; }
		
		if($text == false) { $text =  "This Website is using <a href='/cookies' target='_blank'>Session Cookies</a> for Site Functionality.";}
		
		echo '<div class="x_cookieBanner">';
			echo '<div class="x_cookieBanner_inner">';
				echo $text;
				if(!$use_post) { 
					echo '<form method="get"><input type="submit" value="I Agree" class="x_cookieBanner_close"><input type="hidden" value="submit" name="x_cookieBanner"></form>'; 
				} else { 
					echo '<form method="post"><input type="submit" value="I Agree" class="x_cookieBanner_close"><input type="hidden" value="submit" name="x_cookieBanner"></form>';
				}
			echo '</div>';		
		echo '</div>';
	}
	
	//////////////////////////////////////////////
	// Check if Script is run in CLI
	//////////////////////////////////////////////
	function x_inCLI() {
		$sapi_type = php_sapi_name();
		if (substr($sapi_type, 0, 3) == 'cgi') { return true;
		} else { return false; }
	}

	//////////////////////////////////////////////
	// Replacement for HTMLSPECIALCHARS
	//////////////////////////////////////////////	
	function x_hsc($string) {
		return htmlspecialchars(@$string);
	}
	
	//////////////////////////////////////////////
	// Button Functions
	//////////////////////////////////////////////
		function x_executionButton($db, $name, $url, $query, $get, $msgerr = "Fehler!", $msgok = "Erfolgreich!", $break = false, $style = ""){
			if(strpos(trim($url), "?") > 2) { $xurl = trim($url)."&".$get."=x"; } else {$xurl = trim($url)."?".$get."=x";} print "<a href='".$xurl."' class='x_executionButton' style='".$style."'>".$name."</a>";if($break) {echo "<br />";} if(@$_GET[$get] == "x") { if($db->query($query)) { return true; } else {return false;}  $url = str_replace("?".$get."=x&", "?", $url); $url = str_replace("&".$get."=x", "", $url);  print '<meta http-equiv="refresh" content="0; url='.$url.'">';} return false;}	
		function x_button($name, $url, $break = false, $style = "", $reacttourl = true){  if($reacttourl AND strpos($url."&", $_SERVER["REQUEST_URI"]."&") > -1) {$style .= ";background: grey !important;";} print "<a href='".$url."' class='x_button' style='".$style."'>".$name."</a>"; if($break) {echo "<br />";}}
	
	//////////////////////////////////////////////
	// Redirect Functions
	//////////////////////////////////////////////
		function x_html_redirect($url, $seconds = 0) { echo '<meta http-equiv="refresh" content="'.$seconds.'; url='.$url.'">';}
		
	//////////////////////////////////////////////
	// Param and Check Functions
	//////////////////////////////////////////////
		function x_isset($val) {if(trim(@$val) != '' AND strlen(@$val) > 0 ) {return true;} else {return false;}} ## Check if a value is not null and strlen more than 1
		#### FUNCTION REMOVED START! ###
		function x_datediff_before($d1, $d2, $length)  ## x_datediff_before($d1, $d2, $length) Check if d1 is difference as length with d2 
			{if($d1 == false OR $d2 == false) { return false; } {}$interval = @date_diff(@date_create($d1), @date_create($d2));if( @$interval->format('%a') > $length ) { return true;} return false;}
		#### / FUNCTION REMOVED END! ###
		function x_imgValid($url) {if(!isset($url)) {return false;}else {if(is_string(trim($url)) AND strlen($url) > 3) {return @getimagesize($url);} else {return false;}} }

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
?>