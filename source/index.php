<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  Index File for DNS Replication over HTTP*/
	# Include Settings
	if(file_exists("./settings.php")) {  require_once("./settings.php"); } else {echo "No settings.php found!<br />Please change settings.sample.php and rename this file to settings.php after that!"; exit(); }
	
	# Load CSRF Class
	x_cookieBanner_Pre(_COOKIES_);	
	$csrf = new x_class_csrf(_COOKIES_, _CSRF_VALID_LIMIT_TIME_); 
		
	// Logout on Request
	if($user->loggedIn) {			
		switch($_GET["site"]) {
			case "logout": $user->logout(); x_eventBoxPrep("You have been logged out!", "ok", _COOKIES_); Header("Location: ./"); exit(); break;
		};
	}?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html version="-//W3C//DTD XHTML 1.1//EN"
      xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.w3.org/1999/xhtml
                          http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd">
  <head>
	<!-- Meta Tags For Site --> 
	<title><?php echo _TITLE_; ?> | dnsHTTP by Bugfish</title>	 
	<!-- Meta Tags For Site -->
		<meta http-equiv="content-Type" content="text/html; utf-8" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="content-Language" content="en" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" type="image/x-icon" href="./_style/favicon.ico">
		<meta name="audience" content="all" />
		<meta name="robots" content="noindex, nofollow" />
		<link rel="stylesheet" href="./_style/main.css.php">
  </head>
  <body><div id="contentwrapper"></div><div id="content">
 	<?php if($user->user_loggedIn) { $permsobj = new x_class_perm($mysql, _TABLE_PERM_, "dnshttp");?> 
	<div id="nav">
		<?php if($permsobj->hasPerm($user->user_id, "usermgr") OR $user->user_rank == 0) { ?><a href="./?site=users" <?php if(@$_GET["site"] == "users") { echo 'id="nav_active"'; } ?>>Users</a> - <?php } ?>
		<?php if($permsobj->hasPerm($user->user_id, "blocklist") OR $user->user_rank == 0) { ?><a href="./?site=blocks" <?php if(@$_GET["site"] == "blocks") { echo 'id="nav_active"'; } ?>>Blocklist</a> - <?php } ?>
		<a href="./?site=domains" <?php if(@$_GET["site"] == "domains" || @$_GET["site"] == "apidomains" || @$_GET["site"] == "binddomains" || @$_GET["site"] == "conflict") { echo 'id="nav_active"'; } ?>>Domains</a> - 
  		<?php if($user->user_rank == 0 OR $permsobj->hasPerm($user->user_id, "serversmgr")) { ?><a href="./?site=replication" <?php if(@$_GET["site"] == "replication" || @$_GET["site"] == "logs" || @$_GET["site"] == "logsapi" || @$_GET["site"] == "server") { echo 'id="nav_active"'; } ?>>Replication</a> - <?php } ?>
 		<?php if(($permsobj->hasPerm($user->user_id, "debug") OR $user->user_rank == 0) AND _MYSQL_LOGGING_) { ?><a href="./?site=debug" <?php if(@$_GET["site"] == "debug") { echo 'id="nav_active"'; } ?>>Debug</a> -  <?php } ?> 
		<a href="./?site=profile" <?php if(@$_GET["site"] == "profile") { echo 'id="nav_active"'; } ?>>Profile</a> - 
		<a href="./?site=logout">Logout</a>
	</div>
<?php }
	# Load Content
	if($user->loggedIn) {			
		switch($_GET["site"]) {
			case "logout": $user->logout(); x_eventBoxPrep("You have been logged out!", "ok", _COOKIES_); Header("Location: ./"); exit(); break;
			case "apidomains": require_once("./_instance/site_apidomains.php"); break;
			case "binddomains": require_once("./_instance/site_binddomains.php"); break;
			case "logs": require_once("./_instance/site_logs.php"); break;
			case "logsapi": require_once("./_instance/site_logsapi.php"); break;
			case "debug": require_once("./_instance/site_debug.php"); break;
			case "blocks": require_once("./_instance/site_blocks.php"); break;
			case "server": require_once("./_instance/site_server.php"); break;
			case "users": require_once("./_instance/site_users.php"); break;
			case "replication": require_once("./_instance/site_replication.php"); break;
			case "conflict": require_once("./_instance/site_conflict.php"); break;
			//case "repexec": require_once("./_instance/site_repexec.php"); break;
			case "domains": require_once("./_instance/site_domains.php"); break;
			case "profile": require_once("./_instance/site_profile.php"); break;
			default: Header("Location: ./?site=domains"); exit();				
		};
	} else {
		if(isset($_POST["auth"])) {
			if(@$_SESSION[_COOKIES_."captcha_default"] == @$_POST["captcha"] AND isset($_SESSION[_COOKIES_."captcha_default"])) {
				if($csrf->check(@$_POST["csrf"])) {
					if(!$ipbl->isblocked()) {
						if(isset($_POST["username"]) AND isset($_POST["password"])) {
							$x = $user->login_request(@$_POST["username"], @$_POST["password"]);
								if ($x == 1) { x_eventBoxPrep("Login successfull!", "ok", _COOKIES_); @Header("Location: ".htmlspecialchars(@$_SERVER['REQUEST_URI'])); exit();}
								elseif ($x == 2) {x_eventBoxPrep("Wrong Username/Password!", "error", _COOKIES_); $ipbl->raise();}
								elseif ($x == 3) {x_eventBoxPrep("Wrong Username/Password!", "error", _COOKIES_); $ipbl->raise();}
								elseif ($x == 4) {x_eventBoxPrep("This user is blocked!", "error", _COOKIES_);} else { $ipbl->raise(); }} 
					} else { x_eventBoxPrep("IP is currently blocked!", "error", _COOKIES_); } 
				} else { x_eventBoxPrep("CSRF error, please retry!", "error", _COOKIES_); } 
			} else { x_eventBoxPrep("Captcha is wrong!", "error", _COOKIES_); } 
		} 
		?>
		<div class="content_box small_box vcenter">
			<form method="post">
				<input type="hidden"	name="csrf"			value="<?php echo $csrf->get(); ?>">
				<input type="text" 		name="username" 	placeholder="Username" >
				<input type="password"  name="password" 	placeholder="Password">
				<img src="./_style/captcha_default.php"><input type="text"  name="captcha" 	placeholder="Captcha">
				<input type="submit" 	value="Authenticate" name="auth" class="primary_button" style="cursor:pointer;">
			</form>
		</div>
	<?php	
	}
	# Close Div
	echo "</div>";
	# Display Event Boxes
	x_eventBoxShow(_COOKIES_);
	# Display Cookie Banner
	x_cookieBanner(_COOKIES_, false, "This website is using session Cookies!");	
	# Display Footer
	echo _FOOTER_;
 ?>
  </body>
</html>