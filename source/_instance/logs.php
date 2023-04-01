<?php 
if(!$permsobj->hasPerm($user->user_id, "perm_logs") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {	
	echo '<div class="content_box">';
	echo '<span style="font-size: 22px;"><b>IP Blocklist</b></span><br /><hr><br />';	
	$res = $mysql->select("SELECT * FROM "._TABLE_IPBL_." ORDER BY id DESC", true); 
	if(is_array($res)) { 
		echo "<div style='max-width: 100%;'>";
			echo "<div style='width: 70%;float:left;'><b>IP-Address</b></div>";
			echo "<div style='width: 30%;float:left;'><b>Failure-Count</b></div>";
		echo '</div><br clear="left"/>';	
	
		foreach ($res AS $key => $value) { 	
			echo "<div style='max-width: 100%;'>";
				echo "<div style='width: 70%;float:left;'>".$value["ip_adr"]."</div>";
				echo "<div style='width: 30%;float:left;'>".$value["fail"]."</div>";
			echo '</div><br clear="left"/>';
		}
	} else { echo "No Data Available..."; }
	echo '</div>';

	echo '<div class="content_box">';
	echo '<span style="font-size: 22px;"><b>Replication Status</b></span><br /><hr><br /><div style="text-align:left;">';
	$res	=	$mysql->select("SELECT * FROM "._TABLE_LOG_."  ORDER BY id DESC LIMIT 1", true); 	
	if(is_array($res)) { 
		foreach ($res AS $key => $value) { 	
			echo "<span style='font-size: 16px;'><b>Cronjob Output Details finished at: ".$value["creation"]."</b></span><br />";
			echo "<br /> ".$value["message"]."<br /><br />";
		}
	} else { echo "No Data Available..."; }
	echo '</div></div>';
 }
?>	