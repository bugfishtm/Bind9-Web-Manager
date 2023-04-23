<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ XPF Website Library */	
	########################################################################################################### */
	/* META Functions
	########################################################################################################### */
		function xfp_meta($object, $title, $description, $keywords = false, $robots = false, $cssarray = false, $img = false, $formexpire =  false, $fallbackimage = false, $nocache = true, $canonical = false, $docstart = true, $ext = "", $favicon = false) { $outputvar = "";
			# Print Document Start
			if($docstart) {
				$outputvar .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
				<html version="-//W3C//DTD XHTML 1.1//EN" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					  xsi:schemaLocation="http://www.w3.org/1999/xhtml
										  http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd">
				  <head>';}
			
			# Print Favicon
			if($favicon) { $outputvar .= '<link rel="icon" href="'.$favicon.'"/>'; }		
			
			# Title and Description
			if(defined("_XFP_META_DESC_PRE_")) {	$description =  _XFP_META_DESC_PRE_.xfp_meta_prep($description); }
			if(defined("_XFP_META_DESC_POST_")) {	$description =  xfp_meta_prep($description)._XFP_META_DESC_POST_; }
			if(defined("_XFP_META_DESC_POST_") AND defined("_XFP_META_DESC_PRE_")) {	$description =  _XFP_META_DESC_PRE_.xfp_meta_prep($description)._XFP_META_DESC_POST_; }

			if(defined("_XFP_META_TITLE_PRE_")) {	$title =  _XFP_META_TITLE_PRE_.xfp_meta_prep($title); }
			if(defined("_XFP_META_TITLE_POST_")) {	$title =  xfp_meta_prep($title)._XFP_META_TITLE_POST_; }
			if(defined("_XFP_META_TITLE_POST_") AND defined("_XFP_META_TITLE_PRE_")) {	$title =  _XFP_META_TITLE_PRE_.xfp_meta_prep($title)._XFP_META_TITLE_POST_; }
			
			$outputvar .= "<title>".$title."</title>";	
			$outputvar .= '<meta property="og:title" content="'.$title.'\" />';
			$outputvar .= '<meta name="description" content="'.$description.'" />';
			$outputvar .= '<meta property="og:description" content="'.$description.'" />';
			
			# Canonical
			if($canonical != false) { $outputvar .= "<link rel='canonical' href='".$canonical."' />"; }
			
			# No Cache
			if($nocache != false) { $outputvar .= '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />'; $outputvar .= '<meta http-equiv="Pragma" content="no-cache" />'; }	
			
			# Form Expire
			if($formexpire != false) { $outputvar .= '<meta name="expires" content="'.$formexpire.'" />'; }
			
			# Prepare Meta Image
			if($img == false) {
				if($fallbackimage != false) {  $outputvar .= '<meta property="og:image" content="'.$fallbackimage.'" />'; }
			} else {
				if(file_exists(_XFP_PATH_.$img)) { $outputvar .= '<meta property="og:image" content="'.$img.'" />'; }
				elseif($fallbackimage != false) {  $outputvar .= '<meta property="og:image" content="'.$fallbackimage.'" />'; }
			}
			
			# META UTF8
			$outputvar .= '<meta http-equiv="content-Type" content="text/html; utf-8" />';			
			
			# Robots
			if($robots != false) { $outputvar .= '<meta name="robots" content="'.trim($robots).'" />'; }	
			
			# Prepare CSS
			if(is_array($cssarray)) { foreach($cssarray as $key => $val) { $outputvar .= '<link rel="stylesheet" type="text/css" href="'.$val.'" />';  }
			} elseif($cssarray != false) { $outputvar .= '<link rel="stylesheet" type="text/css" href="'.trim($cssarray).'" />';	 }			

			#Keywords
			if($keywords != false) { $outputvar .= '<meta name="keywords" content="'.trim(htmlspecialchars($keywords)).'" />';	}
			
			# Audience
			$outputvar .= '<meta name="audience" content="all" />';
			
			# Set Viewport
			$outputvar .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
			
			# Set Lang
			if(defined("_XFP_LANG_")) { $outputvar .= '<meta http-equiv="content-Language" content="'._XFP_LANG_.'" />'; }
			
			# Admin Info
			if(defined("_XFP_ADMIN_NAME_")) {$outputvar .= '<meta name="author" content="'._XFP_ADMIN_NAME_.'" />'; 
												$outputvar .= '<meta name="publisher" content="'._XFP_ADMIN_NAME_.'" />'; 
												$outputvar .= '<meta name="copyright" content="'._XFP_ADMIN_NAME_.'" />'; }
			if(defined("_XFP_ADMIN_MAIL_"))  { $outputvar .= '<meta http-equiv="Reply-to" content="'._XFP_ADMIN_MAIL_.'" />'; }			
		
			# External
			$outputvar .= $ext;	 $outputvar .= "</head><body>";	
			return $outputvar;
		}	
	
		function xfp_meta_error($object = false, $code = 404, $image = false, $cssarray, $setcode = true, $ext = "", $docstart = true, $favicon = false) { $outputvar = "";
			# Print Document Start
			if($docstart) {
				$outputvar .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
				<html version="-//W3C//DTD XHTML 1.1//EN" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					  xsi:schemaLocation="http://www.w3.org/1999/xhtml
										  http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd">
				  <head>';}
			
			# Prepare Code and Text/Titles
			switch($code) {
				case "404": if($setcode) { @http_response_code($code); } $text = "This page or content has not been found!"; $code = "Error 404"; break;
				case "403": if($setcode) { @http_response_code($code); } $text = "Forbidden Page!"; $code = "Error 403"; break;
				case "401": if($setcode) { @http_response_code($code); } $text = "Unauthorized!"; $code = "Error 401"; break;
				case "402": if($setcode) { @http_response_code($code); } $text = "Payment Required!"; $code = "Error 402"; break;
				case "405": if($setcode) { @http_response_code($code); } $text = "Method not allowed!"; $code = "Error 405"; break;
				case "500": if($setcode) { @http_response_code($code); } $text = "Internal Server Error!"; $code = "Error 500"; break;
				default: $text = "Website Error!";
			}

			# Prepare CSS
			if(is_array($cssarray)) { foreach($cssarray as $key => $val) { $outputvar .= '<link rel="stylesheet" type="text/css" href="'.$val.'" />';  }
			} elseif($cssarray != false) { $outputvar .= '<link rel="stylesheet" type="text/css" href="'.trim($cssarray).'" />';	 }			
			
			$title = xfp_meta_prep($code, 1000);
			$description = xfp_meta_prep($code, 1000);
			# Title and Description
			if(defined("_XFP_META_DESC_PRE_")) {	$description =  _XFP_META_DESC_PRE_.xfp_meta_prep($code, 1000); }
			if(defined("_XFP_META_DESC_POST_")) {	$description =  xfp_meta_prep($code, 1000)._XFP_META_DESC_POST_; }
			if(defined("_XFP_META_DESC_POST_") AND defined("_XFP_META_DESC_PRE_")) {	$description =  _XFP_META_DESC_PRE_.xfp_meta_prep($code, 1000)._XFP_META_DESC_POST_; }

			if(defined("_XFP_META_TITLE_PRE_")) {	$title =  _XFP_META_TITLE_PRE_.xfp_meta_prep($code, 1000); }
			if(defined("_XFP_META_TITLE_POST_")) {	$title =  xfp_meta_prep($code, 1000)._XFP_META_TITLE_POST_; }
			if(defined("_XFP_META_TITLE_POST_") AND defined("_XFP_META_TITLE_PRE_")) {	$title =  _XFP_META_TITLE_PRE_.xfp_meta_prep($code, 1000)._XFP_META_TITLE_POST_; }
			
			# Title
			$outputvar .= "<title>".$title."</title>";		
			$outputvar .= '<meta property="og:title" content="'.$title.'" />';
			
			# Description
			$outputvar .= '<meta name="description" content="'.$description.'" />';	
			$outputvar .= '<meta property="og:description" content="'.$description.'" />';	
			
			# Set Image
			if($image) { $outputvar .= '<meta property="og:image" content="'.$image.'" />'; }	
			
			# Set Noindex, Nofollow
			$outputvar .= '<meta name="robots" content="noindex, nofollow" />';
			
			# Set Keywords
			$outputvar .= '<meta name="keywords" content="'."error ".$code.'" />';
			
			# Set No Cache
			$outputvar .= '<meta http-equiv="Pragma" content="no-cache" />';
			$outputvar .= '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />';
			
			# Set Text UTF8
			$outputvar .= '<meta http-equiv="content-Type" content="text/html; utf-8" />';
			
			# Set Lang if Set in Constant
			if(defined("_XFP_LANG_")) { $outputvar .= '<meta http-equiv="content-Language" content="'._XFP_LANG_.'" />'; }
			
			# Set Viewport
			$outputvar .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
			
			# Admin Data if Needed
			if(defined("_XFP_ADMIN_NAME_")) { $outputvar .= '<meta name="author" content="'._XFP_ADMIN_NAME_.'" />'; 
						$outputvar .= '<meta name="publisher" content="'._XFP_ADMIN_NAME_.'" />'; 
						$outputvar .= '<meta name="copyright" content="'._XFP_ADMIN_NAME_.'" />'; } 
			if(defined("_XFP_ADMIN_MAIL_")) { $outputvar .= '<meta http-equiv="Reply-to" content="'._XFP_ADMIN_MAIL_.'" />';	}
			
			# External
			$outputvar .= $ext; $outputvar .= "</head><body>";	
			return $outputvar;
		}
	
	########################################################################################################### */
	/* META Prep Functions
	########################################################################################################### */
		function xfp_meta_prep($val, $maxlength = 350) {
			$val = strip_tags($val, '<br>');
			$val = str_replace(array('\n', '\r', '\t') ," ", $val);
			$val = preg_replace('!\s+!', ' ', $val); // Remove All Double Spaces
			$val = str_replace("\\" ,"\\\\", $val); // Escape Backslaches
			$val = htmlspecialchars($val); // Escape
			if(is_numeric($maxlength)) { $val = substr($val, 0,$maxlength); }
			return $val;
		}	
		
	########################################################################################################### */
	/* Multi-Theme Function 
	########################################################################################################### */
		function xfp_theme(){
			$themevar = @$_SESSION[_XFP_COOKIES_."xfp_theme"];
			if(_XFP_THEMESPIN_ == "yes" AND !isset($themevar)) { 
				@$randomfortheme = @mt_rand(0, count(_XFP_THEMEARRAY_));
				@$tmparrayfortheme	=	_XFP_THEMEARRAY_;
				$_SESSION[_XFP_COOKIES_."xfp_theme"] = @$tmparrayfortheme[$randomfortheme];
			}			
			$themevar = @$_SESSION[_XFP_COOKIES_."xfp_theme"];
			if(!isset($themevar)) { $_SESSION[_XFP_COOKIES_."xfp_theme"] = _XFP_THEME_; return _XFP_THEME_; }
			$themevar = @$_SESSION[_XFP_COOKIES_."xfp_theme"];
			if(!in_array($_SESSION[_XFP_COOKIES_."xfp_theme"], _XFP_THEMEARRAY_)) { $_SESSION[_XFP_COOKIES_."xfp_theme"] = _XFP_THEME_; return _XFP_THEME_; }
			if(@$_SESSION[_XFP_COOKIES_."xfp_theme"] == NULL OR @$_SESSION[_XFP_COOKIES_."xfp_theme"] == "") {return _XFP_THEME_;}
			else {return htmlspecialchars($_SESSION[_XFP_COOKIES_."xfp_theme"]);}
		}
		
	########################################################################################################### */
	/* NAVIGATION 
	########################################################################################################### */
		# End of Navigation
		function xfp_navi_end() { echo '</div></div><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /></div><div id="xfp_navwrapper"></div>';}
		
		# Spawn Navigation Start
		function xfp_navi_start($searchpage = false, $searchparam_sc = "", $navi_image = false) {
			echo '<div id="xfp_menutopbar">';
			if($navi_image) { echo '<img alt="Mobile-Menu" src="'.$navi_image.'" width="50" height="50" id="xfp_cenetermenueimg">';}
			echo '<div id="xfp_navigation">';
			if($searchpage) { echo '<form method="get" action="'.$searchpage.'">';
				if($searchparam_sc != false)  { echo '<input type="hidden" name="sc" value="'.$searchparam_sc.'">'; }
				echo '<input type="text" name="tag" placeholder="Search Content" id="xfp_searchboxtop" maxlength="256">'; echo '</form>';  
			}
			echo '<div id="xfp_scrollnavi">'; 
		}
			
		# Spawn a Navigation Item
		function xfp_navi_item($navname, $url, $titlealt, $level = 0, $isonempty = false) { 
			$curloc = xfp_navi_location_seo();$curlocx = explode("/", @$url); if(strpos("/", $url) >= 0 && $level == 0) { $curlocx[$level+1] = $url; }
			if(@$curloc[$level] == @$curlocx[$level+1] OR (empty(@$curloc[$level]) AND $isonempty)) { $active = "id='xfp_navactive'"; } else { $active = ""; } 
			if($level == 0) {$cc = "xfp_navlink";} elseif($level == 1) {$cc = "xfp_navlinksec";} else {$cc = "xfp_navlinksecsec";}
			if($level == 0 AND substr($url, 0, 1) != "/") {$url = "/".$url;}
			echo '<a title="'.$titlealt.'" href="'.$url.'" '.$active.'><div class="'.$cc.'">'.$navname.'</div></a>';}	
		
		// Get SEO Location Array
		function xfp_navi_location_seo($param = false) { if(!$param) { return explode("/", @$_GET[_XFP_MAIN_SEOVAR_]); } else { return explode("/", @$_GET[$param]); } }
		
		// Check if Location Array is in Range of valid
		function xfp_entry($var, $x, $level = 0) { if(@$x[0] == @$var AND count(@$x)-1 <= $level) { return true; }return false;}
		
	########################################################################################################### */
	/* Event Boxes 
	########################################################################################################### */
		function xfp_etb_ok($text)      {  $pre = "/_theme/global/eventbox/";return x_eventBoxPrep($text, "ok", _XFP_COOKIES_, "", "Close", $pre."ok.png", $pre."error.png", $pre."warn.png", $pre."info.png"); }
		function xfp_etb_warning($text) {  $pre = "/_theme/global/eventbox/";return x_eventBoxPrep($text, "warn", _XFP_COOKIES_, "", "Close", $pre."ok.png", $pre."error.png", $pre."warn.png", $pre."info.png"); }
		function xfp_etb_warn($text) {  $pre = "/_theme/global/eventbox/";return x_eventBoxPrep($text, "warn", _XFP_COOKIES_, "", "Close", $pre."ok.png", $pre."error.png", $pre."warn.png", $pre."info.png"); }
		function xfp_etb_error($text)   {  $pre = "/_theme/global/eventbox/";return x_eventBoxPrep($text, "error", _XFP_COOKIES_, "", "Close", $pre."ok.png", $pre."error.png", $pre."warn.png", $pre."info.png"); }	
		function xfp_etb_info($text)   {  $pre = "/_theme/global/eventbox/";return x_eventBoxPrep($text, "info", _XFP_COOKIES_, "", "Close", $pre."ok.png", $pre."error.png", $pre."warn.png", $pre."info.png"); }	

	########################################################################################################### */
	/* Site Init 
	########################################################################################################### */
		// Create a Table if Not Exists
		function xfp_website_create_table($mysql, $tablename, $query) {try {$val = $mysql->query('SELECT 1 FROM `'.$tablename.'`'); if(!$val) { return $mysql->query($query); }} catch (Exception $e){ return $mysql->query($query); }} 
	
		// Display Footer
		function xfp_footer($text) { echo '</div><div id="xfp_footer">'.$text.'</body></html>'; }	
	
		// Display Headline
		function xfp_headline($a, $b) { return '<div id="xfp_headline"><h1>'.htmlspecialchars($a).'</h1><font id="xfp_headline_extender"><span id="xfp_headline_h2">'.htmlspecialchars($b).'</span></font></div>';}
	
		# Print 1. Button Back to Top
		function xfp_top_button($cssclasses = "") { echo '<div id="xfp_top_but_1" class="'.$cssclasses.'"><a title="Back" alt="Back"  href="#xfp_content"> ^Top </a></div>'; }
		
		# Print 2nd Button Return 1 Level
		function xfp_return_button($cssclasses = "") {
			$tmploc	=	xfp_navi_location_seo();
			if(x_isset(@$tmploc[1])) {
				$return_url	=	'/'.x_hsc(@$tmploc[0]);
				if(x_isset(@$tmploc[2]) AND !x_isset(@$tmploc[3])) { $return_url = '/'.x_hsc(@$tmploc[0]).'/'.x_hsc(@$tmploc[1]); }
				if(x_isset(@$tmploc[3]) AND !x_isset(@$tmploc[4])) { $return_url = '/'.x_hsc(@$tmploc[0]).'/'.x_hsc(@$tmploc[1]).'/'.x_hsc(@$tmploc[2]); }
				if(x_isset(@$tmploc[4]) AND !x_isset(@$tmploc[5])) { $return_url = '/'.x_hsc(@$tmploc[0]).'/'.x_hsc(@$tmploc[1]).'/'.x_hsc(@$tmploc[2]).'/'.x_hsc(@$tmploc[3]); }
				if(x_isset(@$tmploc[5])) { $return_url = '/'.x_hsc(@$tmploc[1]).'/'.x_hsc(@$tmploc[2]).'/'.x_hsc(@$tmploc[3]).'/'.x_hsc(@$tmploc[4]); }
				echo '<div id="xfp_top_but_2" class="'.$cssclasses.'"><a title="Back" alt="Back"  href=\''.$return_url.'\'><< Back </a></div>'; return;
			}
		}
		
		# Print 3rd Button Print Option
		function xfp_top_button_print($url, $cssclasses = "") {
			echo '<div id="xfp_top_but_3" class="'.$cssclasses.'"><a title="Print" alt="Print"  '; 
			echo "  onclick=\"MyWindow=window.open('".$url."','Print Item','width=600,height=300'); return false;\" "; 
			echo ' >Print</a></div>'; 
			return;}		
	
	// Init xfp-template Frontpage
	function xfp_website_init($title, $meta_ext, $section) {
		// Rename Uploaded HTAccess to real Htaccess
		if(file_exists(_SITE_PATH_."/dot.htaccess")) { rename(_SITE_PATH_."/dot.htaccess", _SITE_PATH_."/.htaccess"); }
		
		// Init Object Array
		$object = array();

		#####################################################################
		##### XFP Variables      ############################################
		define("_XFP_MAIN_SEOVAR_", "x32rnx"); 
		define("_XFP_COOKIES_", _SITE_COOKIE_PREFIX_);
		define("_XFP_META_DESC_PRE_", "");
		define("_XFP_META_DESC_POST_", " ".$meta_ext);
		define("_XFP_META_TITLE_PRE_", "");
		define("_XFP_META_TITLE_POST_", " - ".$title);
		
		#####################################################################
		##### Captcha Setup      ############################################	
		define('_CAPTCHA_FONT_',   	 _SITE_PATH_."/_style/font_captcha.ttf");
		define('_CAPTCHA_WIDTH_',    "200"); 
		define('_CAPTCHA_HEIGHT_',   "70");	
		define('_CAPTCHA_SQUARES_',   mt_rand(4, 15));	
		define('_CAPTCHA_ELIPSE_',    mt_rand(4, 15));	
		define('_CAPTCHA_RANDOM_',    mt_rand(1000, 9999));

		#####################################################################
		##### TABLES           ##############################################						
		define('_TABLE_USER_',   				_SQL_PREFIX_."user");  
		define('_TABLE_USER_SESSION_',			_SQL_PREFIX_."user_session");
		define('_TABLE_USER_PERM_',				_SQL_PREFIX_."user_perm");
		define('_TABLE_LOG_IPBL_',				_SQL_PREFIX_."ipbl");
		define('_TABLE_LOG_',					_SQL_PREFIX_."log");
		define('_TABLE_LOG_MYSQL_',				_SQL_PREFIX_."log_sql");
		define('_TABLE_LOG_MAIL_',				_SQL_PREFIX_."log_mail");
		define('_TABLE_VAR_',					_SQL_PREFIX_."const");

		#####################################################################
		##### Get Location    ###############################################
		$object["location"] = xfp_navi_location_seo();

		#####################################################################
		##### Create MySQL     ##############################################
		$object["mysql"] = new x_class_mysql(_SQL_HOST_, _SQL_USER_, _SQL_PASS_, _SQL_DB_);
		if ($object["mysql"]->lasterror != false) { $object["mysql"]->displayError(true); } else { $object["mysql"]->loggingSetup(true, _TABLE_LOG_MYSQL_); }
		
		#####################################################################
		##### Create Vars      ##############################################	
		$object["var"] = new x_class_var($object["mysql"], _TABLE_VAR_, "descriptor", "value");
		$object["var"]->sections("section", $section);
		$object["var"]->initAsConstant();		
		
		#####################################################################
		##### Misc Classes     ##############################################						
		$object["csrf"] = new x_class_csrf(_SITE_COOKIE_PREFIX_, 1200);						
		$object["ipbl"] = new x_class_ipbl($object["mysql"], _TABLE_LOG_IPBL_, 10000);					
		$object["curl"] = new x_class_curl();
		$object["log"] = new x_class_log($object["mysql"], _TABLE_LOG_);

		#####################################################################
		##### Create User     ###############################################
		$object["user"] = new x_class_user($object["mysql"], _TABLE_USER_, _TABLE_USER_SESSION_, _SITE_COOKIE_PREFIX_);
		$object["user"]->multi_login(false);
		$object["user"]->login_recover_drop(true);
		$object["user"]->login_field_mail();
		$object["user"]->user_unique(false);
		$object["user"]->log_ip(true);
		$object["user"]->log_activation(true);
		$object["user"]->log_session(true);
		$object["user"]->log_recover(true);
		$object["user"]->log_mail_edit(true);
		$object["user"]->wait_activation_min(24);
		$object["user"]->wait_recover_min(24);
		$object["user"]->wait_mail_edit_min(24);
		$object["user"]->min_activation(24);
		$object["user"]->min_recover(24);
		$object["user"]->autoblock(500);
		$object["user"]->min_mail_edit(24);
		$object["user"]->sessions_days(7);
		$object["user"]->cookies_use(true);
		$object["user"]->cookies_days(7);
		$object["user"]->perm_config($object["mysql"], _TABLE_USER_PERM_);
		$object["user"]->init();
		
		#####################################################################
		##### Return     ####################################################
		return $object;
	}
