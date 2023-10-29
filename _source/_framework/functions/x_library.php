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
	//////////////////////////////////////////////
	// Button Functions
	//////////////////////////////////////////////
		function x_executionButton($db, $name, $url, $query, $get, $msgerr = "Fehler!", $msgok = "Erfolgreich!", $break = false, $style = ""){
			if(strpos(trim($url), "?") > 2) { $xurl = trim($url)."&".$get."=x"; } else {$xurl = trim($url)."?".$get."=x";} print "<a href='".$xurl."' class='x_executionButton' style='".$style."'>".$name."</a>";if($break) {echo "<br />";} if(@$_GET[$get] == "x") { if($db->query($query)) { return true; } else {return false;}  $url = str_replace("?".$get."=x&", "?", $url); $url = str_replace("&".$get."=x", "", $url);  print '<meta http-equiv="refresh" content="0; url='.$url.'">';} return false;}	
		function x_button($name, $url, $break = false, $style = "", $reacttourl = true){  if($reacttourl AND strpos($url."&", $_SERVER["REQUEST_URI"]."&") > -1) {$style .= ";background: grey !important;";} print "<a href='".$url."' class='x_button' style='".$style."'>".$name."</a>"; if($break) {echo "<br />";}}
	

	function x_captcha($preecookie = "", $width = 550, $height = 250, $square_count = 5, $eclipse_count = 5, $color_ar = false, $font = "", $code = "") {
		if (session_status() === PHP_SESSION_NONE) { session_start(); }
		
		if(!is_array($color_ar)) {
			$color_ar = array();
			$color_ar["squares"] = array();
			$color_ar["squares"]["r"] = 255;
			$color_ar["squares"]["g"] = 255;
			$color_ar["squares"]["b"] = 255;
			$color_ar["eclipse"]["r"] = 244;
			$color_ar["eclipse"]["g"] = 244;
			$color_ar["eclipse"]["b"] = 244;
			$color_ar["background"]["r"] = 24;
			$color_ar["background"]["g"] = 24;
			$color_ar["background"]["b"] = 24;
			$color_ar["text"]["r"] = 255;
			$color_ar["text"]["g"] = 255;
			$color_ar["text"]["b"] = 255;
		}
	
		$im = imagecreatetruecolor($width, $height); 
			
		$c_square  	 = imagecolorallocate($im, $color_ar["squares"]["r"], $color_ar["squares"]["g"], $color_ar["squares"]["b"]);
		$c_ellipse   = imagecolorallocate($im, $color_ar["eclipse"]["r"], $color_ar["eclipse"]["g"], $color_ar["eclipse"]["b"]);
		$background	 = imagecolorallocate($im, $color_ar["background"]["r"], $color_ar["background"]["g"], $color_ar["background"]["b"]);
		$c_txt  	 = imagecolorallocate($im, $color_ar["text"]["r"], $color_ar["text"]["g"], $color_ar["text"]["b"]);
			
		header("Expires: Mon, 21 Jul 2020 05:00:00 GMT");   
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");   
		header("Cache-Control: no-store, no-cache, must-revalidate");   
		header("Cache-Control: post-check=0, pre-check=0", false);  
		header("Pragma: no-cache");   
		   
		imagefilledrectangle($im, 0, 0, 399, 29, $background);
		
		for($i = 0; $i < $square_count; $i++){
			$cx = rand(0,$width);
			$cy = (int)rand(0, $width/2);
			$h  = $cy + (int)rand(0, $height/5);
			$w  = $cx + @(int)rand($width/3, $width);
			imagefilledrectangle($im, $cx, $cy, $w, $h, $c_square);}

		for ($i = 0; $i < $eclipse_count; $i++) {
		  $cx = (int)rand(-1*($width/2), $width + ($width/2));
		  $cy = (int)rand(-1*($height/2), $height + ($height/2));
		  $h  = (int)rand($height/2, 2*$height);
		  $w  = (int)rand($width/2, 2*$width);
		  imageellipse($im, $cx, $cy, $w, $h, $c_ellipse);}
		 
		$_SESSION[$preecookie] = strval($code);  
		imagefttext($im, 40, 20, 10, 60, $c_txt, $font, $_SESSION[$preecookie][0]);
		imagefttext($im, 40, 20, 60, 60, $c_txt, $font, $_SESSION[$preecookie][1]);
		imagefttext($im, 40, 20, 110, 60, $c_txt, $font, $_SESSION[$preecookie][2]);
		imagefttext($im, 40, 20, 150, 60, $c_txt, $font, $_SESSION[$preecookie][3]);
		Header ('Content-type: image/jpeg');  
		imagejpeg($im,NULL,100);  
		ImageDestroy($im);  		
	}

	function x_captcha_key($preecookie = "") { return $_SESSION[$preecookie];	}


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

	function x_cookieBanner($precookie = "", $use_post = false, $text = false, $url_cookies = "") { 
		if (session_status() !== PHP_SESSION_ACTIVE) {@session_start();}
		if(@$_GET["x_cookieBanner"] == "submit")  { $_SESSION[$precookie ."x_cookieBanner"] = true; }
		if(@$_SESSION[$precookie ."x_cookieBanner"] == true) { return false; }
		
		if($text == false AND $url_cookies != "") { $text =  "This Website is using <a href='".$url_cookies."' target='_blank'>Session Cookies</a> for Site Functionality.";} elseif($text == false) {
			$text =  "This Website is using <b>Session Cookies</b> for Site Functionality.";
			
		}
		
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
	// EventBox Functions
	//////////////////////////////////////////////
	function x_eventBoxPrep($text, $type = "x", $precookie = "", $morecss = "", $buttontext = "X", $imgok = false, $imgfail = false, $imgwarn = false, $imgelse = false) {
		if($type == "ok"|| $type == "success") {if($imgok) {$img = "<img src='".$imgok."'>";} else {$img = "";} $morecss = '<div class="x_eventBox" id="x_eventBox_ok_outer"><div id="x_eventBox_ok" class="x_eventBox_inner" style="'.$morecss.'" >'.$img.$text;}
		elseif($type == "warning" || $type == "warn" || $type == "warns") {if($imgwarn) { $img = "<img src='".$imgwarn."'>";}else {$img = "";}$morecss = '<div class="x_eventBox" id="x_eventBox_warning_outer"><div id="x_eventBox_warning" class="x_eventBox_inner" style="'.$morecss.'" >'.$img.$text;}
		elseif($type == "error" || $type == "errors" || $type == "fail") {if($imgfail) {$img = "<img src='".$imgfail."'>";}else {$img = "";}$morecss = '<div class="x_eventBox" id="x_eventBox_error_outer"><div id="x_eventBox_error" class="x_eventBox_inner" style="'.$morecss.'" >'.$img.$text;}
		else {if($imgelse) {$img = "<img src='".$imgelse."'>";}else {$img = "";} $morecss = '<div id="x_eventBox_'.$type.'" class="x_eventBox" style="'.$morecss.'" >'.$img.$text;}				
		$morecss = $morecss."<button class='x_eventBoxButton' onclick='this.parentNode.parentNode.remove()'>".$buttontext."</button></div></div>";
		$_SESSION[$precookie."x_eventbox"] = $morecss;}

	function x_eventBoxShow($precookie = "") { if(@$_SESSION[$precookie."x_eventbox_skip"]) { $_SESSION[$precookie."x_eventbox_skip"] = false; return true; } echo @$_SESSION[$precookie."x_eventbox"]; unset( $_SESSION[$precookie."x_eventbox"] );   }
	function x_eventBoxSet($precookie = "") { if(isset($_SESSION[$precookie."x_eventbox"])) { return true; } else { return false; } }
	function x_eventBoxSkip($precookie = "") { $_SESSION[$precookie."x_eventbox_skip"] = true; }
				
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



	function x_search($mysql, $table, $search_fields = array(), $get_fields = array(), $search_string = "", $uniqueref = "id") {
		// Abort if Search String if not Set
		if(empty($search_string) OR trim(@$search_string) == "" OR @$search_string == false) { return false; }
		
		// Trim Search String
		$search_string = trim($search_string);
		
		// Get Current Search Tag Array
		if(strpos($search_string, " ") > -1) { 
			$search_string = preg_replace('/\s+/', ' ', $search_string); 
			$search_array =  explode(" ", $search_string);
		} else {$search_array[0] = trim($search_string);}
		

		// Prepare Array for Binds for Search Query
		$new_bind_array	=	array();
		$counter = 0; // Search String Counter
		$c_q	=	""; // Serach Query Counter
		$bindcounter = 0;
		
		
		
		// Prepare the Query for Search Results
		while (is_numeric($counter)) {
			if(@$search_array[$counter] != null) {
				if(trim(@$search_array[$counter] != "")) {
					
					if($counter == 0) {
						if($bindcounter == 0) {
							$c_q = "SELECT * FROM `".$table ."` WHERE (title LIKE CONCAT( '%', ?, '%') OR text LIKE CONCAT( '%', ?, '%') OR category = CONCAT( '%', ?, '%') OR sec_category LIKE CONCAT( '%', ?, '%')) ";
							$bindcounter++;
						} else {
							
						}
							
					} else {
						foreach($search_fields AS $tmpkey => $tmpvalue) {
							$c_q .= " OR ".$tmpvalue[0]." LIKE CONCAT( '%', ?, '%') ";	
						}
					}
					
					
					
					$new_ar["type"]	 =	"s";
					$new_ar["value"] =	$search_array[$counter];
					
					foreach($search_fields AS $tmpkey => $tmpvalue) {
						array_push($new_bind_array, $new_ar);				
					}
				}  $counter	= $counter + 1;
			} else {$counter	= "notset";}
		}
		
		// Query and Sorting Variables
		$cur_ar		=	$mysql->select( $c_q." ORDER BY ".$uniqueref." DESC", true, $new_bind_array);
		$ra		=	null;
		$rad	=	null;
		
		// Scoring for Items
		foreach($cur_ar as $key => $score_r){
			// Set Fields available for Score
			$counter = 0;
			foreach($get_fields AS $tmpkey => $tmpvalue) {
				$ra[$score_r[$uniqueref]][$tmpvalue]  	  = $score_r[$tmpvalue];
			}
			
			$rad[0][$score_r[$uniqueref]]["score"]  = 0;
				
			while (is_numeric($counter)) {
				if(@$search_array[$counter] != null) {
					if(trim(@$search_array[$counter] != "")) {
						foreach($search_fields AS $tmpkey => $tmpvalue) {
							$rad[0][$score_r[$uniqueref]]["score"] = @$rad[0][$score_r[$uniqueref]][$tmpvalue[0]] + (substr_count(strtolower($score_r[$tmpvalue[0]]), strtolower($search_array[$counter])) * $tmpvalue[1]);
						}	
					} $counter	= $counter + 1;
				} else { $counter	= "notset"; }
			}
			
			// Add Related IF for later Recognizing
			$rad[0][$score_r[$uniqueref]][$uniqueref] = $score_r[$uniqueref];
		}	
		
		if(@$rad[0]) {
			array_multisort($rad[0], SORT_DESC);
			$output = array();
			foreach (@$rad[0] as &$value) {array_push($output, $ra[$value[$uniqueref]]);}	
			return $output;
		} else { return array(); }
	}



	// Table with Search Function
	function x_table_div($array, $titlelist, $alignarray = false, $percentarray = false, $title = false){
		$colspan	=	count($titlelist);
		print '<div class="x_table_div">';if(is_string($title)) {echo '<div class="x_table_div_title">'.$title.'</div>';}
		$t_r_count	= 0 ;$xcc = 0;
		echo '<div class="x_table_div_titles">';
			foreach( $titlelist as $key => $value ){
				if(is_array($percentarray)) { $t_pct	=	$percentarray[$t_r_count]; } else {$t_pct	=	"10%"; }
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<section class="x_table_div_titles_title" style="float:left;text-align: '.$t_align.';width: '.$t_pct.';">'.$value.'</section>';
				$t_r_count	=	$t_r_count	+ 1;}print "<br clear='left'/></div>";
		if(empty($array)) {print '<div class="x_table_div_row" style="width: 100%;"><i>Keine Daten vorhanden...</i></div>'; } else {
			foreach( $array as $key => $value ){
					$t_r_count	=	0; if($xcc % 2 == 0) { print '<div class="x_table_div_frow x_table_div_frow_even">';} else {print '<div class="x_table_div_frow x_table_div_frow_odd">';}
					foreach( $array[$key] as $key1 => $value1 ){
						if(is_array($percentarray)) { $t_pct	=	$percentarray[$t_r_count]; } else {$t_pct	=	"10%"; }
						if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						print '<div class="x_table_div_row" style="float:left;text-align: '.$t_align.';width: '.$t_pct.';">'.$value1.'</div>';
						$t_r_count	=	$t_r_count	+ 1;$xcc = $xcc  + 1;}print "<br clear='left'/></div>";}}
		print '</div>';}	

	// Table with Search Function
	function x_table_complex($array, $titlelist, $formid = "", $alignarray = false){
		$colspan	=	count($titlelist);
		print '<form method="post"  id="x_table_complex_'.$formid.'"><input type="submit" style="display:none;">';
		print '<table class="x_table_complex"><tr class="x_table_complex_tr"></tr>';
			if(!empty($array)) {
				print '<tr class="x_table_complex_tr">';$tcount	=	0;
				foreach( $array[0] as $key => $value ){
					$tmp_placeholder = $titlelist[$tcount];$tcount = $tcount + 1;
					$tmp_value = @htmlspecialchars($_POST['x_t_c_'.$key]);
					print '<th><input type="text" name="x_t_c_'.$key.'" value="'.@$tmp_value.'" placeholder="'.$tmp_placeholder.'"></th>';}
				print '</tr>';}
		print '<tr class="x_table_complex_tr">';$t_r_count	= 0 ;
			foreach( $titlelist as $key => $value ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<th class="x_table_complex_th" style="text-align: '.$t_align.';">'.$value.'</th>';
				$t_r_count	=	$t_r_count	+ 1;}print '</tr>';
		if(empty($array)) {print '<tr class="x_table_complex_tr"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>'; } else {
			$didfound = false;
			foreach( $array as $key => $value ){
					$search_relevant	=	true;
					foreach( $array[$key] as $key1 => $value1 ){
						if(isset($_POST["x_t_c_".$key1]) AND @trim($_POST["x_t_c_".$key1]) != "") {if(strpos($value1, $_POST["x_t_c_".$key1]) <= -1) {$search_relevant	=	false;}}}							
				if($search_relevant) {
					print '<tr class="x_table_complex_tr">';
					$t_r_count	=	0;
					foreach( $array[$key] as $key1 => $value1 ){
						if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
						print '<td style="text-align: '.$t_align.';">'.$value1.'</td>';
						$t_r_count	=	$t_r_count	+ 1;$didfound = true;}print '</tr>';}}
			if(!$didfound) {print '<tr class="x_table_complex_tr"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>';}}
		print '</table></form>';}	

	/*	Simple Table Function */
	function x_table_simple($array, $titlelist, $tableid = "x_table_simple", $alignarray = false){
		$colspan	=	count($titlelist);
		print '<table class="x_table_simple" id="'.$tableid.'"><tr class="x_table_simple_tr"><td colspan="'.$colspan.'"></td></tr><tr class="x_table_simple_trhead">';
		$t_r_count	=	0;
		foreach( $titlelist as $key => $value ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<th class="x_table_simple_th" style="text-align: '.$t_align.';">'.$value.'</th>';$t_r_count	=	$t_r_count	+ 1;}
		print '</tr>';
		if(empty($array)) {print '<tr class="x_table_simple_tr"><td colspan="'.$colspan.'" style="text-align: center"><i>Keine Daten vorhanden...</i></td></tr>';} else {
			foreach( $array as $key => $value ){
				print '<tr class="x_table_simple_tr">';
				$t_r_count	=	0;
				foreach( $array[$key] as $key1 => $value1 ){
				if($t_r_count == 0) {	if(!$alignarray) { $t_align	=	"left"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				elseif($t_r_count == count($titlelist)-1) {	if(!$alignarray) { $t_align	=	"right"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				else {	if(!$alignarray) { $t_align	=	"center"; } else {$t_align	=	$alignarray[$t_r_count]; }}
				print '<td style="text-align: '.$t_align.';">'.$value1.'</td>';
				$t_r_count	=	$t_r_count	+ 1;}				
			print '</tr>';}}
		print '</table>';}
	
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
		function x_thumbnail_save($url,  $save_path = null, $width = 600, $height = true) {
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