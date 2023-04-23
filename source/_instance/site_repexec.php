<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  View Replication Execution Output */
if(!$permsobj->hasPerm($user->user_id, "serversmgr") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {	
	echo '<div class="content_box">';
	echo '<span style="font-size: 22px;"><b>Replication Execution</b></span><br />If you see no output here, than the php execution handler has no rights to execute the needed operations.<br /><hr><div style="text-align:left;">';
	full_cron($mysql);
	echo '</div></div>';
  }?>
