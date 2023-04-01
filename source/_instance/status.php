<?php 
if(!$permsobj->hasPerm($user->user_id, "perm_status") AND $user->user_rank != 0) { echo "<div class='content_box'>You do not have Permission!</div>"; } else {
 ?>








	<div class="content_box">
		<h3>Default Settings</h3>
		<?php
			echo 'Servername: <b>'._TITLE_.'</b><br />';
			echo 'Cookiename: <b>'._COOKIES_.'</b><br />';
			echo 'Document-Root: <b>'._MAIN_PATH_.'</b><br />';
		?>
	</div>

	<div class="content_box">
		<h3>Security Settings</h3>
		<?php
			if(_IP_BLACKLIST_DAILY_OP_LIMIT_  == false) { $tmp = "1000"; } else { $tmp = _IP_BLACKLIST_DAILY_OP_LIMIT_; }
			echo 'IP Blacklist Daily Limit: <b>'.$tmp.'</b><br />';
			if(_CSRF_VALID_LIMIT_TIME_  == false) { $tmp = "120"; } else { $tmp = _CSRF_VALID_LIMIT_TIME_; }
			echo 'CSRF Validation Time: <b>'.$tmp.'</b><br />';
		?>
	</div>






	<div class="content_box">
		<h3>Cronjob Settings</h3>
		See settings.php File for Cronjob Settings!
	</div>













<?php } ?>