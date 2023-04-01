<?php
if(!$permsobj->hasPerm($user->user_id, "perm_server") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {
if(isset($_POST["exec_edit"])) {
	if(trim(@$_POST["path"]) != "" AND trim(@$_POST["token"]) != "" AND trim(@$_POST["ip"]) != "") {
		if(is_numeric(@$_POST["exec_ref"])) {
			$mysql->query("UPDATE "._TABLE_SERVER_." SET api_path = '".$mysql->escape(trim($_POST["path"]))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET api_token = '".$mysql->escape(trim($_POST["token"]))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET ip = '".$mysql->escape(trim($_POST["ip"]))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			if(@$_POST["master"]) { $master = 1; } else  { $master = 0 ;}
			if(@$_POST["master"] AND @$_POST["slave"]) { $master = 3; } 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET server_type = '".$master."' WHERE id = \"".$_POST["exec_ref"]."\";");
			x_eventBoxPrep("Server has been updated!", "ok", _COOKIES_);	
		} else {											
			if(@$_POST["master"]) { $master = 1; } else  { $master = 0 ;}
			if(@$_POST["master"] AND @$_POST["slave"]) { $master = 3; } 
			$mysql->query("INSERT INTO "._TABLE_SERVER_." (api_path, api_token, server_type, fk_user, ip) 
														VALUES (\"".$mysql->escape(trim($_POST["path"]))."\"
														, '".$mysql->escape(trim($_POST["token"]))."'
														, '".$master."'
														, '".$user->user_id."'
														, '".$mysql->escape(trim($_POST["ip"]))."');");
			x_eventBoxPrep("Server has been added!", "ok", _COOKIES_);
		}
	} else { x_eventBoxPrep("Error in submitted data!", "error", _COOKIES_);  }
}

if(isset($_POST["exec_del"])) {
	if(is_numeric($_POST["exec_ref"])) {
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE id = '".$_POST["exec_ref"]."'", false);
		if(is_array($res)) {
			$mysql->query("DELETE FROM `"._TABLE_SERVER_."` WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("DELETE FROM `"._TABLE_DOMAIN_API_."` WHERE fk_server = \"".$_POST["exec_ref"]."\";");
			x_eventBoxPrep("Server has been deleted!", "ok", _COOKIES_);
		} else { x_eventBoxPrep("Server does not exist!", "error", _COOKIES_); }
	} 
}
	
	
	
	echo '<div  class="content_box" style="max-width: 800px;text-align: center;"><a href="./?site=server&edit=add" class="sysbutton">Add new Server</a></div>';
		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT *	FROM "._TABLE_SERVER_."  ORDER BY id DESC");
		$run = false;
		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
		echo '<div class="content_box" style="text-align:left;">';
			if($curissuer["server_type"] != 1) { $server_type =  "Slave"; } else { $server_type =  "Master"; }
			if($curissuer["server_type"] == 3) { $server_type =  "Master/Slave"; } 
			echo '<div class="label_box">Relay-ID: <b>'.@$curissuer["id"].'</b></div>';
			echo '<div class="label_box">API-URL: <b>'.@$curissuer["api_path"].'</b></div>';
			echo '<div class="label_box">IP: <b>'.@$curissuer["ip"].'</b></div> ';
			echo '<div class="label_box">Type: <b>'.@$server_type.'</b></div>';
			echo '<div class="label_box">Token: <b>'.@$curissuer["api_token"].'</b></div>';
			echo '<div class="label_box">Owner: <b>'.@dnshttp_user_get_name_from_id($mysql, @$curissuer["fk_user"]).'</b></div> <br clear="left"/>';
			$run = true;	
			echo "<a class='sysbutton' href='./?site=server&testc=".$curissuer["id"]."'>Check Status</a> ";
			echo "<a class='sysbutton' href='./?site=server&testt=".$curissuer["id"]."'>Check Token</a> ";
			echo '<a class="sysbutton" target="_blank" href="'.@$curissuer["api_path"].'/_api/list.php?token='.$curissuer["api_token"].'">View Domains</a> ';
			echo "<a class='sysbutton' href='./?site=server&edit=".$curissuer["id"]."'>Edit</a> ";
			echo "<a class='sysbutton' href='./?site=server&delete=".$curissuer["id"]."'>Delete</a> ";
echo "</div>";	
		}
		
		if(!$run) {echo '<div class="content_box">No data to display!</div>';}
?>	
<?php if(dnshttp_server_id_exists($mysql, @$_GET["edit"]) OR @$_GET["edit"] == "add") { 
		if(@$_GET["edit"] == "add") { $title = "Add new Server"; } else { $title = "Edit Relay: ".dnshttp_server_get($mysql, $_GET["edit"])["id"]; } ?>
	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title"><?php echo $title; ?></div>		
			<form method="post" action="./?site=server"><div class="internal_popup_content">			
				<input type="text" placeholder="Api-Path" name="path" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["api_path"]; ?>">
				<input type="text" placeholder="Api-Token" name="token" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["api_token"]; ?>">
				<input type="text" placeholder="Server-IP" name="ip" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["ip"]; ?>">
				<input type="checkbox" name="slave" <?php if(@dnshttp_server_get($mysql, $_GET["edit"])["server_type"] != 1 || @dnshttp_server_get($mysql, $_GET["edit"])["server_type"] == 3) { echo "checked"; } ?>>Slave DNS-Server
				<input type="checkbox" name="master" <?php if(@dnshttp_server_get($mysql, $_GET["edit"])["server_type"] == 1  || @dnshttp_server_get($mysql, $_GET["edit"])["server_type"] == 3) { echo "checked"; } ?>>Master DNS-Server
				<?php if(is_numeric(@$_GET["edit"])) { ?><input type="hidden" value="<?php echo @$_GET["edit"]; ?>" name="exec_ref"><?php } ?>
			</div>		
			<div class="internal_popup_submit"><input type="submit" value="Execute" name="exec_edit"><a href="./?site=server">Cancel</a></div></form>
		</div>
	</div>
<?php } ?>
<?php if(dnshttp_server_id_exists($mysql, @$_GET["delete"])) { ?>	
	<div class="internal_popup">
		<form method="post" action="./?site=server"><div class="internal_popup_inner">
			<div class="internal_popup_title">Delete: <?php echo dnshttp_server_get($mysql, $_GET["delete"])["id"]; ?></div>
			<div class="internal_popup_submit"><input type="hidden" value="<?php echo @$_GET["delete"]; ?>" name="exec_ref"><input type="submit" value="Execute" name="exec_del"><a href="./?site=server">Cancel</a></div>		
		</div></form>
	</div>
<?php } ?>
<?php if(dnshttp_server_id_exists($mysql, @$_GET["testc"])) { 
		$apipath	=	dnshttp_server_get($mysql, $_GET["testc"])["api_path"]."/_api/status.php";
		$returncurl =   dnshttp_api_getcontent($mysql, $apipath);
		if($returncurl == "online") { $output 	=	"SUCCESS"; } else { $output 	=	"ERROR"; }
		if($returncurl == "tokenerror") { $output 	=	"TOKEN ERROR"; } 
			
?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Test: <?php echo dnshttp_server_get($mysql, $_GET["testc"])["id"]; ?></div>
			<div class="internal_popup_content">Path: <?php echo $apipath; ?> <br /> Output: <?php echo $output; ?> </div>
			<div class="internal_popup_submit"><a href="./?site=server">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if(dnshttp_server_id_exists($mysql, @$_GET["testt"])) { 
		$apipath	=	dnshttp_server_get($mysql, $_GET["testt"])["api_path"]."/_api/status_token.php";
		$returncurl =   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $_GET["testt"])["api_token"]);
		if($returncurl == "online") { $output 	=	"SUCCESS"; } else { $output 	=	"ERROR"; }
?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Test: <?php echo dnshttp_server_get($mysql, $_GET["testt"])["id"]; ?></div>
			<div class="internal_popup_content">Path: <?php echo $apipath; ?> <br /> Output: <?php echo $output; ?> </div>
			<div class="internal_popup_submit"><a href="./?site=server">Cancel</a></div>		
		</div>
	</div>
<?php }} ?>