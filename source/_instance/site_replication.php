<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  File with Replication Area Selection */
	if($permsobj->hasPerm($user->user_id, "serversmgr") OR $user->user_rank == 0) {
	$count = $mysql->select("SELECT * FROM "._TABLE_LOG_." WHERE section='replication'", true);
	if(is_array($count)) { $count = count($count); } else { $count = 0; } 
?>	

<div class="content_box" >
	<b>Here you can see the status of replications!</b>
	<div style="text-align: left;">
	Here you can see the status of replications and the logfile for API Requests!
	If you need more help about the replication visit the "<a href="<?php echo _HELP_; ?>"  rel="noopener" target="_blank">Help</a>" section!<br />
	</div>

	<font color="lightgrey"><?php echo $count; ?> Replications so far...</font><br />
	<a href="./?site=logs" class="sysbutton">View Replication Protocols</a> <a href="./?site=logsapi" class="sysbutton">View API Logfile</a>
</div>
<?php
	
	$count = $mysql->select("SELECT * FROM "._TABLE_SERVER_." ", true);
	if(is_array($count)) { $count = count($count); } else { $count = 0; } 
	
	$count1 = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE apiok = 0 OR tokenbadlastreq = 0 OR weblacklisted = 0 ", true);
	if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; } 
?>	
<div class="content_box" >
	<b>Here you can set up Replication Servers!</b><br />
	<div style="text-align: left;">
	Besides that you can see the status of the servers and validate security tokens or more! If you need more information about this see the "<a href="<?php echo _HELP_; ?>" rel="noopener" target="_blank">Help</a>" section! The status of the various DNS servers will be refreshed everytime the cronjob is executed. You can refresh the status by yourself by clicking on the related button at the related server. <br />
	<font color="lightgrey">You have <b><?php echo $count; ?></b> DNS Server/s configured...</font><br />
	<font color="lightgrey">You have <b><?php echo $count1; ?></b> DNS Server/s with errors...</font><br />
	</div>
	<a href="./?site=server" class="sysbutton">Manage DNS Servers</a>
</div>

<!--<div class="content_box" >
	<b>Here you can manually start the replication cronjob sync.php now!</b>
	<div style="text-align: left;">
	If you have a cronjob setup which does reoccurly run, you normally do not need to run this script manually. But in case you need to, you can execute the replication cronjob here. This will refresh all server and domain related status data directly and output the replication results!
	If you need more help about the replication visit the "<a href="<?php echo _HELP_; ?>"  rel="noopener" target="_blank">Help</a>" section!<br />
	</div>

	<a href="./?site=repexec" class="sysbutton">Execute</a>
</div>-->

<?php
	} else { ?>
<div class="content_box" >
	You have no Permission to view this area!
</div>		
		
	<?php } 
?>