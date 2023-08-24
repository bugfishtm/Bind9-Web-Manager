<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  View Replication Logs File */
if(!$permsobj->hasPerm($user->user_id, "serversmgr") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {	
	echo '<div class="content_box">';
	echo '<span style="font-size: 22px;"><b>Replication Status [Limit 1000]</b></span><br /><hr><div style="text-align:left;">';
	echo "Here you can view the logfiles of the replication cronjob <b>(sync.php)</b> which handles all the replication executions. This file is useful to look what exactly happened at the Execution. You can see errors here and more. The entrie of interest is on the top, as it represents the latest replication execution of <b>sync.php</b>. More info on the \"<a href=\"./?site=replication\" >Replication</a>\" and \"<a href=\""._HELP_."\"  rel='noopener' target='_blank'>Help</a>\" page!<br /></div></div><div class='content_box'><div>";
	$res	=	$mysql->select("SELECT * FROM "._TABLE_LOG_."  WHERE section = 'replication' ORDER BY id DESC LIMIT 1000", true); 	
	if(is_array($res)) { 
		
		echo "<div style='max-width: 100%;'>";
			echo "<div style='width: 60%;float:left;'><b>Time</b></div>";
			echo "<div style='width: 40%;float:left;'><b>Action</b></div>";
		echo '</div><br clear="left"/>';	
		
		echo '<style>.hoverdiv345:hover div{background: #363636 !important;}.hoverblackfontas:hover{color: black !important;}</style>';
		
		foreach ($res AS $key => $value) { 	
			//echo "<span style='font-size: 16px;'><b>Cronjob Output Details finished at: ".$value["creation"]."</b></span><br />";
			//echo "<br /> ".$value["message"]."<br /><br />";
			
			echo '<div class="hoverdiv345">';
			echo "<div style='width: 60%;float:left;padding-top: 5px; padding-bottom: 5px;'>".$value["creation"]."</div>";
			echo "<div style='width: 40%;float:left;padding-top: 5px; padding-bottom: 5px;'><a class='sysbutton' href='./?site=logs&showcontent=".$value["id"]."'>Content</a></font></div>"; 				
			echo '</div>';
			echo '<br clear="left"/>';
		}
	} else { echo "No Data Available..."; }
	echo '</div></div>';
 
 if(is_numeric(@$_GET["showcontent"])) {
	 $asd = $mysql->select("SELECT * FROM  "._TABLE_LOG_." WHERE id = ".@$_GET["showcontent"]." AND section='replication'");
 }

 if( @$asd) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">REPLICATION: <?php echo htmlspecialchars($asd["creation"]); ?></div>
			
			<?php echo "<div style='text-align: left;font-size: 14px;'>".$asd["message"]."</div><br />";?>
			
			<div class="internal_popup_submit"><a class="hoverblackfontas" href="./?site=logs">Cancel</a></div>		
		</div>
	</div>
<?php } }?>
