<?php
if(!$permsobj->hasPerm($user->user_id, "perm_domains_api") AND $user->user_rank != 0) {  echo "<div class='content_box'>No Permission!</div>"; } else {
if(isset($_GET["pref"])) {
	if(is_numeric($_GET["pref"])) {
		$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE id = '".$_GET["pref"]."'", false);
		if(is_array($res)) {
			$mysql->query("UPDATE `"._TABLE_DOMAIN_API_."` SET preferred = 0 WHERE LOWER(domain) = \"".strtolower($res["domain"])."\";");
			$mysql->query("UPDATE `"._TABLE_DOMAIN_API_."` SET preferred = 1 WHERE id = \"".$_GET["pref"]."\";");
			
			x_eventBoxPrep("Domain is now prefered, conflict solved!", "ok", _COOKIES_);
		} else { x_eventBoxPrep("Domain does not exist!", "error", _COOKIES_); }
	} 
}	
	
	echo '<div class="content_box">';
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_DOMAIN_API_." ORDER BY id DESC");
		$run = false;
		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
		if(@$curissuer["preferred"] != 1 AND @$curissuer["conflict"] == 1) { 	echo '<fieldset style="background: darkred; border-color: #242424; margin-bottom: 10px;">'; }
		elseif(@$curissuer["preferred"] == 1 AND @$curissuer["conflict"] == 1) {echo '<fieldset style="background: darkblue; border-color: #242424; margin-bottom: 10px;">';  }
		else {echo '<fieldset style="background: darkgreen; border-color: #242424; margin-bottom: 10px;">';  }
				echo "<div style='width: 85%;float:left;'>";
				echo '<div class="label_box" style="background:#242424;">Domain: <b>'.@$curissuer["domain"].'</b></div> ';
				echo '<div class="label_box" style="background:#242424;">Server: <b>'.dnshttp_server_get($mysql, $curissuer["fk_server"])["api_path"].'</b></div> ';
				if(@$curissuer["preferred"] != 1 AND @$curissuer["conflict"] == 1) { echo "<a class='sysbutton' href='./?site=apidomains&pref=".$curissuer["id"]."'>Prefer</a> "; }
					echo "<textarea style='background: rgba(0,0,0,0.9); color: white; width: 100%; max-width: 100%; height: 25px; border-radius: 10px;' readonly>".$curissuer["content"]."</textarea><br />";
				echo "</div>";
				echo "<div style='width: 15%;float:left;'>".$curissuer["modification"]."</div>";	
			echo '</fieldset>';	 $run = true;
		}
	if(!$run) {echo "No data to display!";}
echo '</div>';}
?>	