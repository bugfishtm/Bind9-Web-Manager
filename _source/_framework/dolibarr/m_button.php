<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ Dolibarr Button Set	*/
	// Add a Default Button Linked to another Page
	function m_button_link($name, $url, $break = false, $style = "", $reacttourl = true){ if($reacttourl AND strpos($url."&", $_SERVER["REQUEST_URI"]."&") > -1) {$style .= ";background: grey !important;";} print "<a href='".$url."' class='butAction' style='".$style."'>".$name."</a>"; if($break) {echo "<br />";}}
		
	// Add a Button Able to Execute a Simple SQL Function
	function m_button_sql($db, $name, $url, $query, $get, $msgerr = "Fehler!", $msgok = "Erfolgreich!", $break = false, $style = ""){
		if(strpos(trim($url), "?") > 2) { $xurl = trim($url)."&".$get."=x"; } else {$xurl = trim($url)."?".$get."=x";}
		print "<a href='".$xurl."' class='butAction' style='".$style."'>".$name."</a>";if($break) {echo "<br />";}
		if(@$_GET[$get] == "x") {
			if($db->query($query)) { setEventMessage($msgok, "mesgs"); } else { setEventMessage($msgerr, "mesgs"); } 
			$url = str_replace("?".$get."=x&", "?", $url); $url = str_replace("&".$get."=x", "", $url); 
			print '<meta http-equiv="refresh" content="0; url='.$url.'">';exit();}}	
