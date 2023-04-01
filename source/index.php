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
	if(is_numeric(_CSRF_VALID_LIMIT_TIME_)) { $csrf = new x_class_csrf(_COOKIES_, _CSRF_VALID_LIMIT_TIME_); }
		else { $csrf = new x_class_csrf(_COOKIES_, 300); } ?>
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
		<a href="./?site=users" <?php if(@$_GET["site"] == "users") { echo 'id="nav_active"'; } ?>>Users</a> - 
		<a href="./?site=status" <?php if(@$_GET["site"] == "status") { echo 'id="nav_active"'; } ?>>Status</a> -  
		<a href="./?site=logs" <?php if(@$_GET["site"] == "logs") { echo 'id="nav_active"'; } ?>>Log</a><br />
		<a href="./?site=domains" <?php if(@$_GET["site"] == "domains") { echo 'id="nav_active"'; } ?>>Domains</a> - 
		<a href="./?site=server" <?php if(@$_GET["site"] == "server") { echo 'id="nav_active"'; } ?>>Server</a> - 
		<a href="./?site=conflict" <?php if(@$_GET["site"] == "conflict") { echo 'id="nav_active"'; } ?>>Conflicts</a> - 
		<a href="./?site=profile" <?php if(@$_GET["site"] == "profile") { echo 'id="nav_active"'; } ?>>Profile</a> - 
		<a href="./?site=logout">Logout</a>		
	</div>	
<?php } 
	# Load Content
	if($user->loggedIn) {			
		switch($_GET["site"]) {
			case "logout": $user->logout(); Header("Location: ./"); exit(); break;
			case "apidomains": require_once("./_instance/apidomains.php"); break;
			case "binddomains": require_once("./_instance/binddomains.php"); break;
			case "userdomains": require_once("./_instance/userdomains.php"); break;
			case "admindomains": require_once("./_instance/admindomains.php"); break;
			case "records": require_once("./_instance/records.php"); break;
			case "logs": require_once("./_instance/logs.php"); break;
			case "server": require_once("./_instance/server.php"); break;
			case "users": require_once("./_instance/users.php"); break;
			case "conflict": require_once("./_instance/conflict.php"); break;
			case "status": require_once("./_instance/status.php"); break;
			case "domains": require_once("./_instance/domains.php"); break;
			case "profile": require_once("./_instance/profile.php"); break;
			default: Header("Location: ./?site=domains"); exit();				
		};
	} else {
		if(isset($_POST["auth"])) {
			if(@$_SESSION[_COOKIES_."captcha_default"] == @$_POST["captcha"] AND isset($_SESSION[_COOKIES_."captcha_default"])) {
				if($csrf->check(@$_POST["csrf"])) {
					if(!$ipbl->isblocked()) {
						if(isset($_POST["username"]) AND isset($_POST["password"])) {
							$x = $user->login_request(@$_POST["username"], @$_POST["password"]);
								if ($x == 1) { x_eventBoxPrep("Login successfull!", "ok", _COOKIES_); Header("Location: ".htmlspecialchars(@$_SERVER['REQUEST_URI'])); exit();}
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
				<input type="submit" 	value="Authenticate" name="auth" class="primary_button">
			</form>
		</div>
	<?php	
	}
	
	# Display Event Boxes
	x_eventBoxShow(_COOKIES_);
 ?></div><div id="footer">dnsHTTP v3 | Made by <a href="https://bugfish.eu" target="_blank" rel="noopeener">Bugfish</a> | <a href="https://www.patreon.com/bugfish" target="_blank" rel="noopeener">Patreon</a></div> 
  </body>
</html>