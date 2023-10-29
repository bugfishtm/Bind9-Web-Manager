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
		Bugfish - DNSHTTP Software / MIT License
		// Autor: Jan-Maurice Dahlmanns (Bugfish)
		// Website: www.bugfish.eu 
	*/
if(!$permsobj->hasPerm($user->user_id, "servers") AND $user->user_rank != 0 AND !$permsobj->hasPerm($user->user_id, "serversmgr")) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {
	$log_api	=	new x_class_log($mysql, _TABLE_LOG_, "api");	
	
if((isset($_POST["exec_edit"]) OR isset($_POST["exec_edit_d"])) AND ($permsobj->hasPerm($user->user_id, "serversmgr") OR $user->user_rank == 0)) {
		if(!$csrf->check($_POST["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endofex; }
		if(is_numeric(@$_POST["exec_ref"])) {
			if(strlen(trim($_POST["token"])) < 5) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-Token', an API Token must be at least 5 signs long!", "error", _COOKIES_); goto endofex; }
			if(strlen(trim($_POST["path"])) < 2) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-URL'!", "error", _COOKIES_); goto endofex; }
			if(!@$_POST["master"] AND !@$_POST["slave"] ) { x_eventBoxPrep("You did not choose a server mode (Slave or Master / Both)!", "error", _COOKIES_); goto endofex; }
			if(@$_POST["master"]) { $master = 1; } else  { $master = 2 ;}
			if(@$_POST["master"] AND @$_POST["slave"]) { $master = 3; } 			
			$apipathfinal = trim($_POST["path"]);
			$apipathfinal = str_replace("///", "/", $apipathfinal);
			if(substr($apipathfinal, strlen($apipathfinal) -1, 1) != "/") { $apipathfinal = $apipathfinal."/"; }
			if(substr($apipathfinal, strlen($apipathfinal) -2, 1) == "/") {
				$apipathfinal = substr($apipathfinal, 0, -1);
			}
			if(substr($apipathfinal, 0, 7) != "http://" AND substr($apipathfinal, 0, 8) != "https://") { $apipathfinal = "https://".$apipathfinal; }
			if(filter_var(@$_POST["ip"], FILTER_VALIDATE_IP) === false AND filter_var(@$_POST["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) { x_eventBoxPrep("IPv4 and IPv6 invalid! You must provide at least one valid ip!", "error", _COOKIES_); goto endofex; }
			if(filter_var(@$_POST["ip"], FILTER_VALIDATE_IP) !== false) { $xxip = @$_POST["ip"]; } else { $xxip = ""; }
			if(filter_var(@$_POST["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) { $xxip6 = @$_POST["ip6"]; } else { $xxip6 = ""; }

			$mysql->query("UPDATE "._TABLE_SERVER_." SET api_path = '".$mysql->escape(trim($apipathfinal))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET api_token = '".$mysql->escape(trim($_POST["token"]))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET ip = '".$mysql->escape(trim($xxip))."' WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET ip6 = '".$mysql->escape(trim($xxip6))."' WHERE id = \"".$_POST["exec_ref"]."\";");			
			
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 2 WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 2 WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 2 WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 2 WHERE id = \"".$_POST["exec_ref"]."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 2 WHERE id = \"".$_POST["exec_ref"]."\";");

			$mysql->query("UPDATE "._TABLE_SERVER_." SET server_type = '".$master."' WHERE id = \"".$_POST["exec_ref"]."\";");
			x_eventBoxPrep("Server has been updated! If the API-URL hast not been updated, it may be the case that this URL is a duplicate at another Server which is registered here...", "ok", _COOKIES_);	
		} else {
			if(strlen(trim($_POST["token"])) < 5) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-Token', an API Token must be at least 5 signs long!", "error", _COOKIES_); goto endofex; }
			if(strlen(trim($_POST["path"])) < 2) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-URL'!", "error", _COOKIES_); goto endofex; }
			if(!@$_POST["master"] AND !@$_POST["slave"] ) { x_eventBoxPrep("You did not choose a server mode (Slave or Master / Both)!", "error", _COOKIES_); goto endofex; }
			if(@$_POST["master"]) { $master = 1; } else  { $master = 2 ;}
			if(@$_POST["master"] AND @$_POST["slave"]) { $master = 3; } 			
			$apipathfinal = trim($_POST["path"]);
			$apipathfinal = str_replace("///", "/", $apipathfinal);
			if(substr($apipathfinal, strlen($apipathfinal) -1, 1) != "/") { $apipathfinal = $apipathfinal."/"; }
			if(substr($apipathfinal, strlen($apipathfinal) -2, 1) == "/") {
				$apipathfinal = substr($apipathfinal, 0, -1);
			}
			
			if(substr($apipathfinal, 0, 7) != "http://" AND substr($apipathfinal, 0, 8) != "https://") { $apipathfinal = "https://".$apipathfinal; }
			if(filter_var(@$_POST["ip"], FILTER_VALIDATE_IP) === false AND filter_var(@$_POST["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) { x_eventBoxPrep("IPv4 and IPv6 invalid! You must provide at least one valid ip!", "error", _COOKIES_); goto endofex; }
			if(filter_var(@$_POST["ip"], FILTER_VALIDATE_IP) !== false) { $xxip = @$_POST["ip"]; } else { $xxip = ""; }
			if(filter_var(@$_POST["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) { $xxip6 = @$_POST["ip6"]; } else { $xxip6 = ""; }
			
			if(isset($_POST["exec_edit_d"])) { $enabledbefore = 0; } else { $enabledbefore = 1; }
			
			$mysql->query("INSERT INTO "._TABLE_SERVER_." (api_path, api_token, server_type, fk_user, ip, ip6, enabled, apiok, emptydomains, tokenbadlastreq, weblacklisted) 
														VALUES (\"".$mysql->escape(trim($apipathfinal))."\"
														, '".$mysql->escape(trim($_POST["token"]))."'
														, '".$master."'
														, '".$user->user_id."'
														, '".$mysql->escape(trim($xxip))."'
														, '".$mysql->escape(trim($xxip6))."'
														, '".$enabledbefore."'
														, 2
														, 2
														, 2
														, 2
													);");
			x_eventBoxPrep("Server has been added! If this is not the case, check that the API Domain does not exist twice on this system.", "ok", _COOKIES_);
		}
		
		if(is_numeric(@$_POST["exec_ref"])) { $checkserverid = @$_POST["exec_ref"]; } else { $checkserverid = $mysql->insert_id; }
		server_check_and_set($mysql, $checkserverid);
}
endofex:

// RECHECK A SERVER
if(isset($_GET["recheckserver"])) {
	if(is_numeric($_GET["recheckserver"])) {
		if(!$csrf->check($_GET["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endofex12; }
		$checkserverid = $_GET["recheckserver"];
		server_check_and_set($mysql, $checkserverid);
		x_eventBoxPrep("Server Status has been updated!", "ok", _COOKIES_);
	} 
} endofex12:

// DELETE A SERVER
if(isset($_POST["exec_del"]) AND ($permsobj->hasPerm($user->user_id, "serversmgr") OR $user->user_rank == 0)) {
	if(is_numeric($_POST["exec_ref"])) {
		if(!$csrf->check($_POST["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endofex245; }
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE id = '".$_POST["exec_ref"]."'", false);
		if(is_array($res)) {
			$mysql->query("DELETE FROM `"._TABLE_SERVER_."` WHERE id = \"".$_POST["exec_ref"]."\";");
			x_eventBoxPrep("Server has been deleted!", "ok", _COOKIES_);
		} else { x_eventBoxPrep("Server does not exist and could not be deleted!", "error", _COOKIES_); }
	} 
} endofex245:
endofex25632: ?>
	<?php echo '<div  class="content_box" style="max-width: 800px;text-align: center;"><a href="./?site=server&edit=add" class="sysbutton">Add new Server</a> <a href="#showmethepicture" class="sysbutton">Explanations</a></div>';?>
	<?php
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT *	FROM "._TABLE_SERVER_."  ORDER BY id DESC");
		$run = false;
		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
		echo '<div class="content_box" style="text-align:left;">';
			if($curissuer["server_type"] == 2) { $server_type =  "Slave"; } elseif($curissuer["server_type"] == 1) { $server_type =  "Master"; }
			elseif($curissuer["server_type"] == 3) { $server_type =  "Master/Slave"; } else { $server_type =  "<font color='red'>ERROR</font>"; }
			echo '<div class="label_box">Server-ID: <b>'.@$curissuer["id"].'</b></div>';
			echo '<div class="label_box">API-URL: <b>'.@$curissuer["api_path"].'</b></div>';
			echo '<div class="label_box">IP: <b>'.@$curissuer["ip"].'</b></div> ';
			echo '<div class="label_box">IPv6: <b>'.@$curissuer["ip6"].'</b></div> ';
			echo '<div class="label_box">Type: <b>'.@$server_type.'</b></div>';
			echo '<div class="label_box">Token: <b>'.@$curissuer["api_token"].'</b></div>';
			echo '<div class="label_box">Creator: <b>'.@dnshttp_user_get_name_from_id($mysql, @$curissuer["fk_user"]).'</b></div> ';
			
			if(@$curissuer["apiok"] == 1) {
				echo '<div class="label_box" style="background: lime; color: black;">API-Ping: Ok</b></div>';
			} elseif(@$curissuer["apiok"] == 0) {
				echo '<div class="label_box" style="background: red; color: white;">API-Ping: Fail</b></div>';
			} elseif(@$curissuer["apiok"] == 2) {
				echo '<div class="label_box" style="background: yellow; color: black;">API: Unchecked</b></div>';
			}
			
			if(@$curissuer["weblacklisted"] == 0) {
				echo '<div class="label_box" style="background: red; color: white;">IP-Ban: Banned</b></div>';
			} elseif(@$curissuer["weblacklisted"] == 2) {
				echo '<div class="label_box" style="background: yellow; color: black;">IP-Ban: Unchecked</b></div>';
			} else {
				echo '<div class="label_box" style="background: lime; color: black;">IP-Ban: Ok</b></div>';
			}
			
			if(@$curissuer["emptydomains"] == 1) {
				echo '<div class="label_box" style="background: lime; color: black;">Domains: '.@$curissuer["domains"].'</b></div>';
			} elseif(@$curissuer["emptydomains"] == 2) {
				echo '<div class="label_box" style="background: yellow; color: black;">Domains: Unchecked</b></div>';
			} else {
				echo '<div class="label_box" style="background: lime; color: black;">Domains: '.@$curissuer["domains"].'</b></div>';
			}


			if(@$curissuer["tokenbadlastreq"] == 0) {
				echo '<div class="label_box" style="background: red; color: white;">Token-Status: Invalid</b></div>';
			} elseif(@$curissuer["tokenbadlastreq"] == 2) {
				echo '<div class="label_box" style="background: yellow; color: black;">Token-Status: Unchecked</b></div>';
			} else {
				echo '<div class="label_box" style="background: lime; color: black;">Token-Status: Ok</b></div>';
			}
			
			echo '<br clear="left"/>';
			
			$run = true;	
			echo "<a class='sysbutton' href='./?site=server&recheckserver=".$curissuer["id"]."&csrf=".@$csrf->get()."'>Recheck Server</a> ";
			echo "<a class='sysbutton' href='./?site=server&testc=".$curissuer["id"]."'>External Domains</a> ";
			//echo "<a class='sysbutton' href='./?site=server&localdomain=".$curissuer["id"]."'>Local Slave Domains</a> ";
			echo "<a class='sysbutton' href='./?site=server&edit=".$curissuer["id"]."'>Edit Server</a> ";
			echo "<a class='sysbutton' href='./?site=server&delete=".$curissuer["id"]."'>Delete Server</a> ";
echo "</div>";	
		}
				echo '<style>.hoverdiv345:hover div{background: #363636 !important;}.hoverdiv345:hover{background: #363636 !important;}.hoverblackfontas:hover{color: black !important;}</style>';
		if(!$run) {echo '<div class="content_box">No Servers to display!</div>';}
?>	

		<div  class="content_box" style="max-width: 800px;text-align: left;" id="showmethepicture">
		<b>Some Explanations</b>:<br />
			Here you can manage nameservers and see the status of them. Below you see information what the different status icons mean. If you need more information may take a look at the "<a href="<?php echo _HELP_; ?>"  rel="noopener" target="_blank">Help</a>" Page of this website, you can find in the footer. Otherwhise look for clues and explanations all over this page..
		</div>
		<div  class="content_box" style="max-width: 800px;text-align: left;">
		<b>Status Icon Informations:</b><br />
		The status of a server is updated if the cronjob <b>sync.php</b> does execute. Otherwhise you can update the status icons by pressing "recheck" on the server you want to check. It may be good to take a look at this values from time to time, but more important is to take a look at the replications log. All necessarry informations you need to know about the replication are written there. (The Replication Protocol Section unter the tab Replication).<br />
		<br /><b>API Status Icons:</b><br />
			<div class="label_box" style="background: lime; color: black;float:left;">API-Ping: Ok</b></div> - API is connectable and OK for Replication<br clear="left">
			<div class="label_box" style="background: red; color: white;float:left;">API-Ping: Fail</b></div> - API is not connectable (API Path/URL is Wrong or other instance has errors at the remote server)<br clear="left">
			<div class="label_box" style="background: yellow; color: black;float:left;">API-Ping: Unchecked</b></div> - API connectivity has not been checked yet (will be by cronjob or with button "recheck")<br clear="left">
		<br /><b>IP-Ban Status Icons:</b><br />
			<div class="label_box" style="background: red; color: white;float:left;">IP-Ban: Banned</b></div> - This server is banned in the others server ip blocklist. You need to remove it on the other servers interface or wait until a remote servers daily cronjob may remove this ban... (May caused by to many tries with wrong tokens!)<br clear="left">
			<div class="label_box" style="background: yellow; color: black;float:left;">IP-Ban: Unchecked</b></div> - IP Ban State has not been checked yet (will be by cronjob or with button "recheck")<br clear="left">
			<div class="label_box" style="background: lime; color: black;float:left;">IP-Ban: Ok</b></div> - Server is okay for replication, as this server is not banned remotely...<br clear="left">
		<br /><b>Token Status Icons:</b><br />
			<div class="label_box" style="background: red; color: white;float:left;">Token-Status: Invalid</b></div> - The configured token for the server does not match the token which is on the remote server for this connection... (They need to match, please check on remote site or change this servers key to the same as on the remote site! This error may also occur, is there is another API Error.<br clear="left">
			<div class="label_box" style="background: yellow; color: black;float:left;">Token-Status: Unchecked</b></div> - Token State has not been checked yet (will be by cronjob or with button "recheck")<br clear="left">
			<div class="label_box" style="background: lime; color: black;float:left;">Token-Status: Ok</b></div> - Server is okay for replication, as this server has a valid token handshake...<br clear="left">
		<br /><b>Domain Status Icons:</b><br />
			<div class="label_box" style="background: lime; color: black;float:left;">Domains: [NUMBER]</b></div> - Number of the Domains existand on the external server!<br clear="left">
			<div class="label_box" style="background: yellow; color: black;float:left;">Domains: Unchecked</b></div> - Still not checked for Domains, will be done on cron-run or if you press "recheck"!<br clear="left">
		</div>
		
<?php if(dnshttp_server_get($mysql, @$_GET["edit"]) OR @$_GET["edit"] == "add") { 
		if(@$_GET["edit"] == "add") { $title = "Add new Server"; $newkey = dnshttp_api_token_generate();} else { $title = "Edit Server ID: ".dnshttp_server_get($mysql, $_GET["edit"])["id"]; $newkey = @dnshttp_server_get($mysql, $_GET["edit"])["api_token"]; }  ?>
	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title"><?php echo $title; ?></div>		
			<form method="post" action="./?site=server"><div class="internal_popup_content">	
				API-Path: Enter the URL on the other server where this interface is running!<br />			
				<input type="text" placeholder="https://another-instance/" name="path" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["api_path"]; ?>">
				API-Token: You need to set up the same security token on the other side!<br />
				<input type="text" placeholder="Generated Token" name="token" value="<?php echo $newkey; ?>">
				IPV4 SETUP: You need to enter at least one related ip (v6 or v4) to this server!<br />
				<input type="text" placeholder="Server-IPv4" name="ip" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["ip"]; ?>">
				IPV6 SETUP: You need to enter at least one related ip (v6 or v4) to this server!<br />
				<input type="text" placeholder="Server-IPv6" name="ip6" value="<?php echo @dnshttp_server_get($mysql, $_GET["edit"])["ip6"]; ?>">
				The new server can be both, slave and/or master server! If you activate Master-Server than this Server will try to replicate Domains out of this new added or edited server!<br />	
				<input type="checkbox" name="slave" <?php if(@dnshttp_server_get($mysql, $_GET["edit"])["server_type"] == 2 || @dnshttp_server_get($mysql, $_GET["edit"])["server_type"] == 3) { echo "checked"; } ?>>Slave DNS-Server [Transfer from the local server to a remote server]<br />
				<input type="checkbox" name="master" <?php if(@dnshttp_server_get($mysql, $_GET["edit"])["server_type"] == 1  || @dnshttp_server_get($mysql, $_GET["edit"])["server_type"] == 3) { echo "checked"; } ?>>Master DNS-Server [Transfer from this edited server to the local server]
				<?php if(is_numeric(@$_GET["edit"])) { ?><input type="hidden" value="<?php echo @$_GET["edit"]; ?>" name="exec_ref"><?php } ?>
				<input type="hidden" value="<?php echo @$csrf->get(); ?>" name="csrf">
			</div>		
			<div class="internal_popup_submit"><?php if (!@dnshttp_server_get($mysql, $_GET["edit"])) { ?><input type="submit" style="cursor:pointer;" value="Create and Enable" name="exec_edit"><?php } else { echo '<input type="submit" style="cursor:pointer;" value="Update" name="exec_edit">'; } ?><a href="./?site=server" class="hoverblackfontas">Cancel</a></div></form>
		</div>
	</div>
<?php } ?>

<?php if(dnshttp_server_get($mysql, @$_GET["testc"])) { 
		$output = "<br />";
		$checkserverid = @$_GET["testc"];
		$apipath	=	dnshttp_server_get($mysql, $checkserverid)["api_path"]."/_api/list.php";
		$returncurl =   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $checkserverid)["api_token"]);
		$log_api->message("[OUT][SERVERID:".@$checkserverid."][APIURL:".@$apipath."][TOKEN:".@dnshttp_server_get($mysql, $checkserverid)["api_token"]."]");
		
		if($returncurlarray = @unserialize($returncurl)) { 
		echo "<b>Domain List</b>: ";
			if(is_array($returncurlarray)) {
				$output .= "<br />";
				foreach($returncurlarray AS $key => $value) {
					$output .= "<div class='hoverdiv345'>".$value."</div>";
				}
				$output .= "<br />";
			} else {	$output = "<br /><font color='red'>Error reading external Domain Data.. Output: <br />".htmlspecialchars($returncurl)."</font><br /><br />"; }
		} else {	$output = "<br /><font color='red'>Error fetching external Domain Data.. Output: <br />".htmlspecialchars($returncurl)."</font><br /><br />"; }
?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">DOMAINS ON SERVER ID: <?php echo dnshttp_server_get($mysql, $_GET["testc"])["id"]; ?></div>
			<div class="internal_popup_content"<?php echo $output; ?></div>
			<div class="internal_popup_submit"><a href="./?site=server" class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php } ?>

<!-- REMOTE SERVER DOMAINS -->
<?php if(dnshttp_server_get($mysql, @$_GET["remotedeomains"])) { ?>	
	<div class="internal_popup">
		<form method="post" action="./?site=server"><div class="internal_popup_inner">
			<div class="internal_popup_title">Local Domains of Server ID: <?php echo dnshttp_server_get($mysql, $_GET["remotedeomains"])["id"]; ?></div>
			<div class="internal_popup_content">
				This are the domains which have been replicated from this server and are now stored localy:<br /><br /> <!-----------------------------------------------> 
			<?php
				$result = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE fk_server = ".$_GET["localdomain"]."");
				if($result) {
						foreach($result AS $key => $value) {
							echo '<div style="width: 100%">';
							echo $value["domain"];
							echo '</div>';
						
						}
				}
			
			?>
			
			</div>
			<div class="internal_popup_submit"><a href="./?site=server" class="hoverblackfontas">Cancel</a></div>		
		</div></form>
	</div>
<?php } ?>

<!-- LOCAL SERVER DOMAINS -->
<?php if(dnshttp_server_get($mysql, @$_GET["localdomain"])) { ?>	
	<div class="internal_popup">
		<form method="post" action="./?site=server"><div class="internal_popup_inner">
			<div class="internal_popup_title">Local Domains of Server ID: <?php echo dnshttp_server_get($mysql, $_GET["localdomain"])["id"]; ?></div>
			<div class="internal_popup_content">
				This are the domains which have been replicated from this server and are now stored localy:<br /><br /> <!-----------------------------------------------> 
			<?php
				$result = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE fk_server = ".$_GET["localdomain"]."");
				if($result) {
						foreach($result AS $key => $value) {
							echo '<div style="width: 100%">';
							echo $value["domain"];
							echo '</div>';
						
						}
				}
			
			?>
			
			</div>
			<div class="internal_popup_submit"><a href="./?site=server" class="hoverblackfontas">Cancel</a></div>		
		</div></form>
	</div>
<?php } ?>


<!-- DELETE A SERVER -->
<?php if(dnshttp_server_get($mysql, @$_GET["delete"])) { ?>	
	<div class="internal_popup">
		<form method="post" action="./?site=server"><div class="internal_popup_inner">
			<div class="internal_popup_title">Delete Server ID: <?php echo dnshttp_server_get($mysql, $_GET["delete"])["id"]; ?></div>
			<div class="internal_popup_content">Do you really want to delete this Server and all its local slave domains?</div>
			<div class="internal_popup_submit"><input type="hidden" value="<?php echo @$_GET["delete"]; ?>" name="exec_ref"><input style="cursor:pointer;" type="submit" value="Execute" name="exec_del"><a href="./?site=server" class="hoverblackfontas">Cancel</a></div>		
		</div><input type="hidden" value="<?php echo @$csrf->get(); ?>" name="csrf"></form>
	</div>
<?php } ?>

<?php if(dnshttp_server_get($mysql, @$_GET["testt"])) { 
		$apipath	=	dnshttp_server_get($mysql, $_GET["testt"])["api_path"]."/_api/status_token.php";
		$returncurl =   dnshttp_api_getcontent($mysql, $apipath, dnshttp_server_get($mysql, $_GET["testt"])["api_token"]);
		if($returncurl == "online") { $output 	=	"<font color='lime'>Secure API Connection OK!</font>"; $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$_GET["testc"]."\";"); } else { $output 	=	"<font color='red'>Secure API Connection Failed</font>"; $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$_GET["testc"]."\";");}
		if($returncurl == "token-error") { $output 	=	"<font color='red'>The security Token is wrong!</font>"; $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$_GET["testc"]."\";"); }
		if($returncurl == "ip-blacklisted") { $output 	=	"<font color='red'>We are IP Blacklisted</font>"; $mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$_GET["testc"]."\";"); $mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 1 WHERE id = \"".$_GET["testc"]."\";"); $mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 2 WHERE id = \"".$_GET["testc"]."\";"); }
?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Test: <?php echo dnshttp_server_get($mysql, $_GET["testt"])["id"]; ?></div>
			<div class="internal_popup_content">Path: <?php echo $apipath; ?> <br /> Output: <?php echo $output; ?> </div>
			<div class="internal_popup_submit"><a href="./?site=server" class="hoverblackfontas">Cancel</a></div>		
		</div>
	</div>
<?php }} ?>