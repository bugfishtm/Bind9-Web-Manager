<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/ XFP Library	*/
	#############################################################################################################
	## Alert Boxes
	#############################################################################################################				
	function xfp_alert_error($text) {  echo "<div>".$text."</div>"; }
	function xfp_alert_warn($text)  {  echo "<div>".$text."</div>"; }
	function xfp_alert_info($text)  {  echo "<div>".$text."</div>"; }
	function xfp_alert_ok($text)    {  echo "<div>".$text."</div>"; }
	#############################################################################################################
	## Event Boxes
	#############################################################################################################		
	function xfp_eb_error($text) { x_eventBoxPrep($text, "error", _XFP_PREFIX_COOKIE_); }
	function xfp_eb_warn($text)  { x_eventBoxPrep($text, "warn", _XFP_PREFIX_COOKIE_); }
	function xfp_eb_info($text)  { x_eventBoxPrep($text, "info", _XFP_PREFIX_COOKIE_); }
	function xfp_eb_ok($text)    { x_eventBoxPrep($text, "ok", _XFP_PREFIX_COOKIE_); }
	#############################################################################################################
	## ONEPAGE
	#############################################################################################################	
		#############################################################################################################
		## FUNCTIONS
		#############################################################################################################		
		function xfp_onepage_captcha($cookie_name = "captcha.xfp") {
			x_captcha(_XFP_PREFIX_COOKIE_.$cookie_name, _XFP_CAPTCHA_WIDTH_, _XFP_CAPTCHA_HEIGHT_, _XFP_CAPTCHA_SQUARES_, _XFP_CAPTCHA_ELIPSE_, _XFP_CAPTCHA_COLOR_, _XFP_CAPTCHA_FONT_, _XFP_CAPTCHA_RANDOM_); }
		function xfp_onepage_captcha_check($cookie_name = "captcha.xfp", $form_value = array()) {
			if(is_array($form_value) OR is_object($form_value)) { return false; }
			if(@$_SESSION[_XFP_PREFIX_COOKIE_.$cookie_name] == $form_value) { return true; }
			return false; }
		function xfp_htaccess_deny($folder) { if(is_dir($folder) AND !file_exists($folder."/.htaccess")) { file_put_contents($folder."/.htaccess", "Deny from all"); } }
		#############################################################################################################
		## INIT
		#############################################################################################################		
		function xfp_onepage($object) {	
			#############################################################################################################
			## CSRF Object for Page
			#############################################################################################################	
			$object["csrf"] = new x_class_csrf(_XFP_PREFIX_COOKIE_, _XFP_MAIN_CSRF_TIME_); 		
			#############################################################################################################
			## Get Start Position
			#############################################################################################################	
			if(@trim(@_XFP_URL_CUR_[0]) == "" AND _XFP_URL_START_[0] == true) {
				if(_XFP_URL_START_[0] != false AND _XFP_URL_GET_[0] != false AND _XFP_URL_SEO_ == false) {
					$startstring = "./?"._XFP_URL_GET_[0]."="._XFP_URL_START_[0];
					if(_XFP_URL_GET_[1] != false  AND _XFP_URL_START_[1] != false) { $startstring .= "&"._XFP_URL_GET_[1]."="._XFP_URL_START_[1]; }
					if(_XFP_URL_GET_[2] != false  AND _XFP_URL_START_[2] != false) { $startstring .= "&"._XFP_URL_GET_[2]."="._XFP_URL_START_[2]; }
					Header("Location: ".$startstring);
					exit();
				} elseif(_XFP_URL_START_[0] != false AND _XFP_URL_SEO_ == true) {
					$startstring = "/"._XFP_URL_START_[0];
					if(_XFP_URL_START_[1] != false) { $startstring .= "/"._XFP_URL_START_[1]; }
					if(_XFP_URL_START_[2] != false) { $startstring .= "/"._XFP_URL_START_[2]; }
					Header("Location: ".$startstring);
					exit();
				}}	
				
				
				
			#############################################################################################################
			## OnePage Header Area
			#############################################################################################################	
			if($object["settings"]["ovr_header"] != false) { 
				if(_XFP_COOKIEBANNER_ != false) { x_cookieBanner_Pre(_XFP_PREFIX_COOKIE_);}
				require_once($object["settings"]["ovr_header"]); 
			} else {		
				if(_XFP_COOKIEBANNER_ != false) { x_cookieBanner_Pre(_XFP_PREFIX_COOKIE_);} ?>
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
					"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
				<html version="-//W3C//DTD XHTML 1.1//EN"
					  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"
					  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					  xsi:schemaLocation="http://www.w3.org/1999/xhtml
										  http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd">		



			

		
		
				<head>
					<title><?php echo $object["settings"]["title"]; ?></title>
					<meta http-equiv="content-Type" content="text/html; utf-8" />
					<meta http-equiv="Pragma" content="no-cache" />
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<meta name="robots" content="noindex, nofollow" />
					<?php if(_XFP_FAVICON_ != false) { ?>
						<link rel="icon" type="image/x-icon" href="<?php echo _XFP_FAVICON_; ?>">
					<?php } ?>
					<style>
						<?php
							echo '@font-face { font-family: xfp_font; src: url('._XFP_PATH_FONT_.'); }';
						?>
					</style>
					<link rel="stylesheet" href="<?php echo _XFP_PATH_FRAMEWORK_REL_; ?>/css/xcss_xfp.css">
					<link rel="stylesheet" href="<?php echo _XFP_PATH_FRAMEWORK_REL_; ?>/css/xcss_xfpe.css">
				</head>
				<body><?php }
				
			#############################################################################################################
			## OnePage Content Area
			#############################################################################################################
			if($object["settings"]["ovr_content"] != false) { 
				require_once($object["settings"]["ovr_content"]); 
			} else {	
				if(!$object["user"]->loggedIn) {
					echo '<div class="xfp_headline xfp_headline_fullwidth"><h1>'.$object["lang"]->translate("xfp_LoginAreaHeaderH1").'</h1><font class="xfp_headline_extender"><span class="xfp_headline_h2">'.$object["lang"]->translate("xfp_LoginAreaHeaderH1Ext").'</span></font></div>';
					echo '<div id="xfp_content_wrapper"></div><div id="xfp_content xfp_content_full">';
					echo '<div class="xfp_content_box_text xfpe_nopadding xfpe_maxwidth300px xfpe_marginauto xfpe_margintop75px_f">';	
						if(_XFP_URL_CUR_[0] == "register") {
							echo '<div class="xfp_content_box_text_title">'.$object["lang"]->translate("xfp_LoginAreaHeaderH1").'</div>';				
								echo '<div class="xfp_content_box_text">';				
								require_once(_XFP_PATH_."/_core/login/register.php");
							echo '</div>'; }
						elseif(_XFP_URL_CUR_[0] == "recover") {
							echo '<div class="xfp_content_box_text_title">'.$object["lang"]->translate("xfp_LoginAreaHeaderH1").'</div>';				
								echo '<div class="xfp_content_box_text">';				
								require_once(_XFP_PATH_."/_core/login/recover.php");
							echo '</div>'; }
						elseif(_XFP_URL_CUR_[0] == "activate") {
							echo '<div class="xfp_content_box_text_title">'.$object["lang"]->translate("xfp_LoginAreaHeaderH1").'</div>';				
								echo '<div class="xfp_content_box_text">';				
								require_once(_XFP_PATH_."/_core/login/activate.php");
							echo '</div>'; }
						else {
							echo '<div class="xfp_content_box_text_title">'.$object["lang"]->translate("xfp_LoginAreaHeaderH1").'</div>';				
								echo '<div class="xfp_content_box_text">';				
								require_once(_XFP_PATH_."/_core/login/login.php");
							echo '</div>'; }
					echo '</div>';
				} else {
					echo '<div class="xfp_headline"><h1>Test</h1><font id="xfp_headline_extender"><span id="xfp_headline_h2">The websites dashboard!</span></font></div>';
					

?>

<div id="xfp_menutopbar">
	<div id="xfp_navigation">
		<?php if(_XFP_NAV_SEARCH_) { ?><form method="get" action="/search"><input type="text" name="tag" placeholder="Search Content" id="xfp_searchboxtop" maxlength="256"></form><?php } else { ?><br /><?php } ?>
		<div id="xfp_scrollnavi">
			<?php foreach($object["nav"] as $key => $value) {
				if(_XFP_URL_SEO_) {  }
				if(!_XFP_URL_SEO_) { $value["nav_loc"][0] = "?"._XFP_URL_GET_[0]."=".$value["nav_loc"][0] ;}
				echo '<a title="Interesting Partner and Website URLs!" href="/'.@$value["nav_loc"][0].'"><div class="xfp_navlink">'.@$value["nav_title"].'</div></a>';
			} ?>
		</div>
	</div>
	<br><br><br><br><br><br><br><br><br><br>
</div>

<div id="xfp_navwrapper"></div>

					

				<?php 
					echo '<div id="xfp_content_wrapper"></div><div id="xfp_content">';	
			foreach($object["nav"] as $key => $value) {
				$nameto = "";
				if(_XFP_URL_SEO_ AND _XFP_URL_CUR_[0] ==  $value["nav_loc"][0]) { require_once($value["nav_inc"]); }
				if(!_XFP_URL_SEO_ AND _XFP_URL_CUR_[0] ==  $value["nav_loc"][0]) { require_once($value["nav_inc"]); }
			} 
				}}	







			#############################################################################################################
			## OnePage Footer Area
			#############################################################################################################
			if($object["settings"]["ovr_footer"] != false) { 
				require_once($object["settings"]["ovr_footer"]); 
			} else {
				# Close Div
				echo "</div>";
				# Display Event Boxes
				if(_XFP_EVENTBOXES_ != false) { echo x_eventBoxShow(_XFP_PREFIX_COOKIE_); }
				# Display Cookie Banner
				if(_XFP_COOKIEBANNER_ != false) { x_cookieBanner(_XFP_PREFIX_COOKIE_, false, _XFP_COOKIEBANNER_); }
				# Display Footer
				echo "<div id='xfp_footer'>";
					echo $object["settings"]["footer"];
				echo "</div>";
				# Display Site End Tags
				  ?></body>
				</html><?php }}
		
