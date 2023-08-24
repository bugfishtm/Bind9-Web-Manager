<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ X Function Set	*/
				
	//////////////////////////////////////////////
	// Button Functions
	//////////////////////////////////////////////
		function x_executionButton($db, $name, $url, $query, $get, $msgerr = "Fehler!", $msgok = "Erfolgreich!", $break = false, $style = ""){
			if(strpos(trim($url), "?") > 2) { $xurl = trim($url)."&".$get."=x"; } else {$xurl = trim($url)."?".$get."=x";} print "<a href='".$xurl."' class='x_executionButton' style='".$style."'>".$name."</a>";if($break) {echo "<br />";} if(@$_GET[$get] == "x") { if($db->query($query)) { return true; } else {return false;}  $url = str_replace("?".$get."=x&", "?", $url); $url = str_replace("&".$get."=x", "", $url);  print '<meta http-equiv="refresh" content="0; url='.$url.'">';} return false;}	
		function x_button($name, $url, $break = false, $style = "", $reacttourl = true){  if($reacttourl AND strpos($url."&", $_SERVER["REQUEST_URI"]."&") > -1) {$style .= ";background: grey !important;";} print "<a href='".$url."' class='x_button' style='".$style."'>".$name."</a>"; if($break) {echo "<br />";}}
	