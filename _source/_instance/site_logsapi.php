<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  View Replication Logs File */
if(!$permsobj->hasPerm($user->user_id, "logs") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {	
	echo '<div class="content_box">';
	echo '<span style="font-size: 22px;"><b>API LOG [Limit 1000]</b></span><br /><hr><div style="text-align:left;">';
	echo "Here are API Requests from remote servers to this server logged [IN] There are also going out requests logged made or tried by this server (with the return curl content if necessary) [OUT]. Not really interesting, maybe if you search for replication errors, but the right place to do so would be the \"<a href=\"./?site=logs\">Replication / Logs</a>\" page! You can find more information about this software at my \"<a href=\""._HELP_."\" rel=\"noopener\" target=\"_blank\">Help</a>\" page! Additional Information: Not all API Requests are stored here, to save database storage! <br /></div></div><div class='content_box'><div>";
	$res	=	$mysql->select("SELECT * FROM "._TABLE_LOG_."  WHERE section = 'api' ORDER BY id DESC LIMIT 1000", true); 	
	if(is_array($res)) { 
		
		echo "<div style='max-width: 100%;'>";
			echo "<div style='width: 40%;float:left;'><b>Time</b></div>";
			echo "<div style='width: 60%;float:left;'><b>Action</b></div>";
		echo '</div><br clear="left"/>';	
		
		echo '<style>.hoverdiv345:hover div{background: #363636 !important;}.hoverblackfontas:hover{color: black !important;}</style>';
		
		foreach ($res AS $key => $value) { 	
			//echo "<span style='font-size: 16px;'><b>Cronjob Output Details finished at: ".$value["creation"]."</b></span><br />";
			//echo "<br /> ".$value["message"]."<br /><br />";
			
			echo '<div class="hoverdiv345">';
			echo "<div style='width: 40%;float:left;padding-top: 5px; padding-bottom: 5px;'>".$value["creation"]."</div>";
			echo "<div style='width: 60%;float:left;padding-top: 5px; padding-bottom: 5px;'>".htmlspecialchars($value["message"])."</font></div>"; 				
			echo '</div>';
			echo '<br clear="left"/>';
		}
	} else { echo "No Data Available..."; }
	echo '</div></div>';
}
?>
