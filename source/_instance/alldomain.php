<?php

if(!isset($_GET["record"])) {

if(isset($_POST["exec_edit"])) {
	if(trim(@$_POST["domain"]) != "" AND trim(@$_POST["domainmail"]) != "") {
		if(is_numeric(@$_POST["exec_ref"])) {
			
			
			
			
			$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dns_serial = '".$mysql->escape(trim($_POST["path"]))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dns_refresh = '"._USER_DOMAIN_REFRESH_."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dns_retry = '"._USER_DOMAIN_RETRY_."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dns_expire = '"._USER_DOMAIN_EXPIRE_."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dns_minimum = '"._USER_DOMAIN_MINIMUM_."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dns_mail = '".$mysql->escape(trim($_POST["domainmail"]))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			x_eventBoxPrep("Domain has been updated!", "ok", _COOKIES_);	
		} else {	
			$mysql->query("INSERT INTO "._TABLE_SERVER_." (domain) VALUES ('".$mysql->escape(trim($_POST["domain"]))."');");

			
			x_eventBoxPrep("Relay has been added!", "ok", _COOKIES_);
		}
	} else { x_eventBoxPrep("Error in submitted data!", "error", _COOKIES_);  }
}

if(isset($_POST["exec_del"]) AND dnshttp_server_id_exists($mysql, @$_POST["exec_ref"])) {
	if(is_numeric($_POST["exec_ref"])) {
		$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_." WHERE fk_relay = '".$_POST["exec_ref"]."'", false);
		if(!is_array($res)) {
			$mysql->query("DELETE FROM `"._TABLE_SERVER_."` WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("DELETE FROM `"._TABLE_DOMAIN_API_."` WHERE fk_server = \"".$_POST["exec_ref"]."\";");
			x_eventBoxPrep("Relay has been deleted!", "ok", _COOKIES_);
		} else { x_eventBoxPrep("Relay is in use for different Domains!", "error", _COOKIES_); }
	} 
}
	
	
	echo '<div  class="content_box" style="max-width: 800px;text-align: center;"><a href="./?site=userdomains&edit=add" class="sysbutton">Add new Domain</a></div>';
	
		echo '<div class="content_box">';
				echo "<div style='width: 50%;float:left;'>Domain</div>";
				echo "<div style='width: 20%;float:left;'>Updated</div>";
				echo "<div style='width: 30%;float:left;'>Actions</div><br clear='left' />";

		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user = '".$user->user_id."' ORDER BY id DESC");

		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
				echo "<hr>";
				echo "<div style='width: 50%;float:left;'>".$curissuer["domain"]."</div>";	
				echo "<div style='width: 20%;float:left;'>".$curissuer["modification"]."</div>";

				echo "<div style='width: 30%;float:left;'>";	
					echo "<a href='./?site=binddomains&show=".$curissuer["id"]."'>Content</a> ";
				echo "</div><br clear='left' />";					
		}
	
	echo '</div>';
?>	

<?php if(dnshttp_server_id_exists($mysql, @$_GET["edit"]) OR @$_GET["edit"] == "add") { 
		if(@$_GET["edit"] == "add") { $title = "Add new Domain"; } else { $title = "Edit Relay: ".dnshttp_server_get($mysql, $_GET["edit"])["id"]; } ?>
	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title"><?php echo $title; ?></div>		
			<form method="post" action="./?site=server"><div class="internal_popup_content">	
				Domain: <input type="text" style="max-width: 600px;" placeholder="Api-Token" name="domain" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["api_token"]; ?>"><br />
				Mail-Contact: <input type="text" style="max-width: 550px;" placeholder="Api-Token" name="domainmail" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["api_token"]; ?>"><br />
				Serial: <input type="text"  style="max-width: 200px;"placeholder="Serial" name="path" value="<?php echo @date("Ymd")."01"; ?>" readonly>
				Refresh: <input type="text" style="max-width: 200px;" placeholder="Refresh" name="path" value="<?php echo "7200"; ?>" readonly><br />
				Retry: <input type="text" style="max-width: 200px;" placeholder="Retry" name="path" value="<?php echo "540"; ?>" readonly>
				Expire: <input type="text" style="max-width: 200px;" placeholder="Expire" name="path" value="<?php echo "604800"; ?>" readonly><br />
				Minimum: <input type="text" style="max-width: 200px;"  placeholder="Minimum" name="path" value="<?php echo "3600"; ?>" readonly>
				<?php if(is_numeric(@$_GET["edit"])) { ?><input type="hidden" value="<?php echo @$_GET["edit"]; ?>" name="exec_ref"><?php } ?>
			</div>		
			<div class="internal_popup_submit"><input type="submit" value="Execute" name="exec_edit"><a href="./?site=userdomains">Cancel</a></div></form>
		</div>
	</div>
<?php } 

} else {
	
	
	
	
	
	
	
	
}
?>

