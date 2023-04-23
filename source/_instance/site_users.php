<?php
	/*
		__________              _____.__       .__     
		\______   \__ __  _____/ ____\__| _____|  |__  
		 |    |  _/  |  \/ ___\   __\|  |/  ___/  |  \ 
		 |    |   \  |  / /_/  >  |  |  |\___ \|   Y  \
		 |______  /____/\___  /|__|  |__/____  >___|  /
				\/     /_____/               \/     \/  User Setup File */

if(!$permsobj->hasPerm($user->user_id, "usermgr") AND $user->user_rank != 0) {  echo "<div class='content_box'>No Permission!</div>"; } else {

if(isset($_POST["exec_edit"]) AND trim(@$_POST["username"]) != "") {
	if(!$csrf->check($_POST["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endofex; }
	if($user->exists(@$_POST["exec_ref"])) {	
		$userid = @$_POST["exec_ref"]; 
		//$user->changeUserName($userid, trim(@$_POST["username"]));
		if(isset($_POST["blocklist"])) { $permsobj->addPerm($userid, "blocklist"); } else { $permsobj->removePerm($userid, "blocklist"); }
		if(isset($_POST["serversmgr"])) { $permsobj->addPerm($userid, "serversmgr"); } else { $permsobj->removePerm($userid, "serversmgr"); }
		if(isset($_POST["debug"])) { $permsobj->addPerm($userid, "debug"); } else { $permsobj->removePerm($userid, "debug"); }
		if(isset($_POST["usermgr"])) { $permsobj->addPerm($userid, "usermgr"); } else { $permsobj->removePerm($userid, "usermgr"); }
		if(isset($_POST["domainmgr"])) { $permsobj->addPerm($userid, "domainmgr"); } else { $permsobj->removePerm($userid, "domainmgr"); }
		x_eventBoxPrep("User has been updated!", "ok", _COOKIES_);	
	} else {											
		$user->addUser(@$_POST["username"], "undefined", @$_POST["password"], 1, 1);
		x_eventBoxPrep("User has been added!", "ok", _COOKIES_);
		$userid = $mysql->insert_id;
		if(isset($_POST["blocklist"])) { $permsobj->addPerm($userid, "blocklist"); } else { $permsobj->removePerm($userid, "blocklist"); }
		if(isset($_POST["serversmgr"])) { $permsobj->addPerm($userid, "serversmgr"); } else { $permsobj->removePerm($userid, "serversmgr"); }
		if(isset($_POST["debug"])) { $permsobj->addPerm($userid, "debug"); } else { $permsobj->removePerm($userid, "debug"); }
		if(isset($_POST["usermgr"])) { $permsobj->addPerm($userid, "usermgr"); } else { $permsobj->removePerm($userid, "usermgr"); }
		if(isset($_POST["domainmgr"])) { $permsobj->addPerm($userid, "domainmgr"); } else { $permsobj->removePerm($userid, "domainmgr"); }
	}
}endofex:

if(isset($_POST["exec_del"]) AND $user->exists(@$_POST["exec_ref"])) {
	if(is_numeric($_POST["exec_ref"])) {
		if(!$csrf->check($_POST["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endof33ex; }
		$x = $user->get($_POST["exec_ref"]);
		if($x["user_rank"] == 0) {  x_eventBoxPrep("Administrator cant be deleted!", "error", _COOKIES_); goto endof33ex; }
		$user->delete($_POST["exec_ref"]);
		$permsobj->delete_ref($_POST["exec_ref"]);
		x_eventBoxPrep("User has been deleted!", "ok", _COOKIES_);
	} 
}endof33ex:

if ($_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_POST["exec_pw"])) {
	if ($csrf->check($_POST['csrf'])) {
		if (@$_POST["password1"] == @$_POST["password2"]) {
			if (trim(@$_POST["password1"]) != "") {
				if (strlen(@$_POST["password1"]) > 8) {
					$user->changeUserPass($_POST["exec_ref"], $_POST["password1"]) ;
					x_eventBoxPrep("Password has been changed!", "ok", _COOKIES_);
				} else  { x_eventBoxPrep("Password needs to be at least 8 signs long!", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("Passwords can not be empty!", "error", _COOKIES_); }
		} else  { x_eventBoxPrep("Passwords are not identical!", "error", _COOKIES_); }
	} else  { x_eventBoxPrep("CSRF Error - Retry!", "error", _COOKIES_); }
} 
	
	echo '<style>.afbasffe3w:hover{color: black !important;}</style><div  class="content_box" style="max-width: 800px;text-align: center;">Here you can edit and manage users! If you grant a user permission to an area, he will also have permission to execute all related actions in that area! Administrators cant be deleted, as there is only one. There is no possibility to reset your password if you lose it! So be cautious and note it if you change it! You can read more about this in the "<a href="'._HELP_.'" rel="noopener" target="_blank">Help</a>" section!<br /><a href="./?site=users&edit=add" class="sysbutton">Add new User</a></div>';		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT *	FROM "._TABLE_USER_."  ORDER BY id DESC"); 
		echo '<div class="content_box">';
				echo "<div style='width: 20%;float:left;'>Username</div>";
				echo "<div style='width: 20%;float:left;'>Permissions</div>";
				echo "<div style='width: 20%;float:left;'>Creation</div>";
				echo "<div style='width: 40%;float:left;'>Actions</div><br clear='left' />";


		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
				echo "<hr >";
				echo "<div style='width: 20%;float:left;'>".@$curissuer["user_name"]."</div>";
				echo "<div style='width: 20%;float:left;'>";
				
					$ar = $permsobj->getPerm($curissuer["id"]);
					if(is_array($ar) AND @count($ar) > 0) {
						foreach($ar AS $key => $value) {
							echo $value."<br />";
						}
					} elseif ($curissuer["user_rank"] == 0) { echo " Administrator"; }	
					else { echo "None";}
				
				echo "</div>";
				echo "<div style='width: 20%;float:left;'>".@$curissuer["created_date"]."</div>";
				echo "<div style='width: 40%;float:left;'>";	

					//echo "<a class='sysbutton' href='./?site=users&session=".$curissuer["id"]."'>Sessions</a> ";
					echo "<a class='sysbutton' href='./?site=users&pass=".$curissuer["id"]."'>Password</a> ";
					echo "<a class='sysbutton' href='./?site=users&edit=".$curissuer["id"]."'>Edit</a> ";
					if(@$curissuer["user_rank"] != 0) { echo "<a  class='sysbutton' href='./?site=users&delete=".$curissuer["id"]."'>Delete</a>"; }
				
				echo "</div><br clear='left' />";			
			
		}
		echo '</div>';
	
?>	
<?php if($user->exists(@$_GET["edit"]) OR @$_GET["edit"] == "add") { 
		if(@$_GET["edit"] == "add") { $title = "Add new User"; } else { $title = "Edit User: ".$user->get($_GET["edit"])["user_name"]; } ?>
	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title"><?php echo $title; ?></div>		
			<form method="post" action="./?site=users"><div class="internal_popup_content">			
		<?php if(@$_GET["edit"] == "add") { ?>		<input type="text" placeholder="Username" name="username" > <?php  ?>
		<?php } else { ?>		<input type="text" placeholder="Username" name="username" value="<?php echo $user->get($_GET["edit"])["user_name"]; ?>" readonly> <?php } ?>
		<?php if(@$_GET["edit"] == "add") { ?> <input type="password" placeholder="Password" name="password" > <?php } ?>
				<div style="float:left">
				<?php if($user->get($_GET["edit"])["user_rank"] != 0 OR @$_GET["edit"] == "add") { $curid = $user->get($_GET["edit"])["id"]; ?>		
					<div style="float:left"><input type="checkbox" name="usermgr" <?php if($permsobj->hasPerm($curid, "usermgr")) { echo "checked"; } ?>> Manage Users</div><br clear="left"/>
					<div style="float:left"><input type="checkbox" name="serversmgr" <?php if($permsobj->hasPerm($curid, "serversmgr")) { echo "checked"; } ?>> Manage Replication / View Replication Informations and Logfiles</div><br clear="left"/>					
					<div style="float:left"><input type="checkbox" name="domainmgr" <?php if($permsobj->hasPerm($curid, "domainmgr")) { echo "checked"; } ?>> Manage Slave / Master Domains and create manual Domains</div><br clear="left"/>
					
					<div style="float:left"><input type="checkbox" name="blocklist" <?php if($permsobj->hasPerm($curid, "blocklist")) { echo "checked"; } ?>> Manage Blocklist</div><br clear="left"/>
					<div style="float:left"><input type="checkbox" name="debug" <?php if($permsobj->hasPerm($curid, "debug")) { echo "checked"; } ?>> View Debug Area (Needs to be activated in settings.php)</div><br clear="left"/>
				<?php } else { echo "This is the Initial User, which does have all Privileges!"; } ?>
				<?php if(is_numeric(@$_GET["edit"])) { ?><input type="hidden" value="<?php echo @$_GET["edit"]; ?>" name="exec_ref"><?php } ?>
			</div>		<input type="hidden" value="<?php echo @$csrf->get(); ?>" name="csrf">
			<div class="internal_popup_submit"><input type="submit"  style="cursor:pointer;" value="Execute" name="exec_edit"><a href="./?site=users" class="afbasffe3w">Cancel</a></div></form>
		</div>
	</div>
<?php } ?>
<?php if($user->exists(@$_GET["delete"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Delete: <?php echo $user->get($_GET["delete"])["user_name"]; ?></div>
			<div class="internal_popup_submit"><form method="post" action="./?site=users"><input type="hidden" name="csrf" value="<?php echo $csrf->get(); ?>"><input type="hidden" value="<?php echo @$_GET["delete"]; ?>" name="exec_ref"><input type="submit" value="Execute"  style="cursor:pointer;" name="exec_del"></form><a href="./?site=users" class="afbasffe3w">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($user->exists(@$_GET["session"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Sessions: <?php echo $user->get($_GET["session"])["user_name"]; ?></div>
			
			<?php
		
		$curissue	=	mysqli_query($mysql->mysqlcon, "SELECT * FROM "._TABLE_USER_SESSION_." WHERE fk_user = '".$_GET["session"]."' ORDER BY id DESC"); 

		while ($curissuer	=	mysqli_fetch_array($curissue) ) { 
			echo '<fieldset><legend>'.@$curissuer["user_name"].'</legend>';
				echo "<div style='width: 40%;float:left;'>";
					echo "ID: ".@$curissuer["id"]."";
				echo "</div>";	
				echo "<div style='width: 60%;float:left;'>";
					echo "Last-Use: ".@$curissuer["refresh_date"]."";
				echo "</div>";	
			echo '</fieldset>';
		} ?>
			
			<div class="internal_popup_submit"><a href="./?site=users" style="cursor:pointer;" class="afbasffe3w">Cancel</a></div>		
		</div>
	</div>
<?php } ?>
<?php if($user->exists(@$_GET["pass"])) { 	
   $query = "SELECT * FROM `"._TABLE_USER_."` WHERE id = ".@$_GET["pass"]."";	 ?>
	<div class="internal_popup">
		<form method="post" action="./?site=users"><div class="internal_popup_inner">
			<div class="internal_popup_title">Change Pass: <?php echo $user->get($_GET["pass"])["user_name"]; ?></div>
			<div class="internal_popup_content">	
				<input type="password" placeholder="Password" name="password1">
				<input type="password" placeholder="Password" name="password2">
				<input type="hidden" name="csrf" value="<?php echo $csrf->get(); ?>">
			</div>
			<div class="internal_popup_submit"><input type="hidden" value="<?php echo @$_GET["pass"]; ?>" name="exec_ref"><input type="submit"  style="cursor:pointer;" value="Execute" name="exec_pw"><a  style="cursor:pointer;"href="./?site=users" class="afbasffe3w">Cancel</a></div>	</form>	
		</div>
	</div>
<?php } 
}?>