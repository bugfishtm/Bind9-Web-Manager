<?php
	
	if(!$permsobj->hasPerm($user->user_id, "perm_domains_api") AND $user->user_rank != 0) {  echo "<div class='content_box'>No Permission!</div>"; } else {
	echo '<div class="content_box">';

		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_CONFLICT_." ORDER BY id DESC");
		$run = false;
		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
			$run = true;
			$string = "";
			echo '<fieldset><legend>'.@$curissuer["domain"].'</legend>';
				echo 'Please solve this domain conflict <a style="background: none; border: none;" href="./?site=apidomains">here</a>';	
			echo '</fieldset>';	
		}
		if(!$run) {echo "No data to display!";}
	echo '</div>';}
?>	