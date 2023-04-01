<?php
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if ($csrf->check($_POST['csrf'])) {
			if (@$_POST["password1"] == @$_POST["password2"]) {
				if (trim(@$_POST["password1"]) != "") {
					$user->changeUserPass($user->user_id, $_POST["password1"]) ;
					x_eventBoxPrep("Password has been changed!", "ok", _COOKIES_);
				} else  { x_eventBoxPrep("Passwords can not be empty!", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("Passwords are not identical!", "error", _COOKIES_); }
		} else  { x_eventBoxPrep("CSRF Error - Retry!", "error", _COOKIES_); }
	}  

	switch($user->user_rank) {
		case 0: $rank = "Superuser"; break;
		default: $rank = "User"; break; }
	?> 

	<div class="content_box" style="max-width: 500px;text-align: left;">
		<?php
			echo 'Username: <b>'.$user->user_name.'</b><br />';
			echo 'Last Login: <b>'.$user->user["last_login"].'</b><br />';
			echo 'Rank: <b>'.$rank.'</b><br />';
			echo 'IP: <b>'.$_SERVER["REMOTE_ADDR"].'</b><br />';
		?>
	</div>

	
<?php
	echo '<div class="content_box" style="max-width: 500px;">';
		echo '<form method="post">';
			echo "<input name='password1' type='password' placeholder='Password'><br clear='both'/>";
			echo "<input name='password2' type='password' placeholder='Confirm Password'><br clear='both'/>";
			echo "<input name='updatepass' type='submit' value='Change Password'><br clear='both'/>";
			echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>";
		echo '</form>';
	echo '</div>';
?>