<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ X Function Set	*/
				
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
