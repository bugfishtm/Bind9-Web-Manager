<?php

if(!$permsobj->hasPerm($user->user_id, "perm_users") AND $user->user_rank != 0) {  echo "<div class='content_box'>No Permission!</div>"; } else {

if(isset($_POST["exec_edit"]) AND trim(@$_POST["username"]) != "") {
	if($user->exists(@$_POST["exec_ref"])) {	
		$userid = @$_POST["exec_ref"]; 
		//$user->changeUserName($userid, trim(@$_POST["username"]));
		if(isset($_POST["perm_logs"])) { $permsobj->addPerm($userid, "perm_logs"); } else { $permsobj->removePerm($userid, "perm_logs"); }
		if(isset($_POST["perm_status"])) { $permsobj->addPerm($userid, "perm_status"); } else { $permsobj->removePerm($userid, "perm_status"); }
		if(isset($_POST["perm_server"])) { $permsobj->addPerm($userid, "perm_server"); } else { $permsobj->removePerm($userid, "perm_server"); }
		if(isset($_POST["perm_users"])) { $permsobj->addPerm($userid, "perm_users"); } else { $permsobj->removePerm($userid, "perm_users"); }
		if(isset($_POST["perm_domains_my"])) { $permsobj->addPerm($userid, "perm_domains_my"); } else { $permsobj->removePerm($userid, "perm_domains_my"); }
		if(isset($_POST["perm_domains_bind"])) { $permsobj->addPerm($userid, "perm_domains_bind"); } else { $permsobj->removePerm($userid, "perm_domains_bind"); }
		if(isset($_POST["perm_domains_api"])) { $permsobj->addPerm($userid, "perm_domains_api"); } else { $permsobj->removePerm($userid, "perm_domains_api"); }
		if(isset($_POST["perm_conflict"])) { $permsobj->addPerm($userid, "perm_conflict"); } else { $permsobj->removePerm($userid, "perm_conflict"); }
		x_eventBoxPrep("User has been updated!", "ok", _COOKIES_);	
	} else {											
		$user->addUser(@$_POST["username"], "undefined", @$_POST["password"], 1, 1);
		x_eventBoxPrep("User has been added!", "ok", _COOKIES_);
		$userid = $mysql->insert_id;
		if(isset($_POST["perm_logs"])) { $permsobj->addPerm($userid, "perm_logs"); } else { $permsobj->removePerm($userid, "perm_logs"); }
		if(isset($_POST["perm_status"])) { $permsobj->addPerm($userid, "perm_status"); } else { $permsobj->removePerm($userid, "perm_status"); }
		if(isset($_POST["perm_server"])) { $permsobj->addPerm($userid, "perm_server"); } else { $permsobj->removePerm($userid, "perm_server"); }
		if(isset($_POST["perm_users"])) { $permsobj->addPerm($userid, "perm_users"); } else { $permsobj->removePerm($userid, "perm_users"); }
		if(isset($_POST["perm_domains_my"])) { $permsobj->addPerm($userid, "perm_domains_my"); } else { $permsobj->removePerm($userid, "perm_domains_my"); }
		if(isset($_POST["perm_domains_bind"])) { $permsobj->addPerm($userid, "perm_domains_bind"); } else { $permsobj->removePerm($userid, "perm_domains_bind"); }
		if(isset($_POST["perm_domains_api"])) { $permsobj->addPerm($userid, "perm_domains_api"); } else { $permsobj->removePerm($userid, "perm_domains_api"); }
		if(isset($_POST["perm_conflict"])) { $permsobj->addPerm($userid, "perm_conflict"); } else { $permsobj->removePerm($userid, "perm_conflict"); }
	}
}

if(isset($_POST["exec_del"]) AND $user->exists(@$_POST["exec_ref"])) {
	if(is_numeric($_POST["exec_ref"])) {
		$user->delete($_POST["exec_ref"]);
		$permsobj->delete_ref($_POST["exec_ref"]);
		x_eventBoxPrep("User has been deleted!", "ok", _COOKIES_);
	} 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_POST["exec_pw"])) {
	if ($csrf->check($_POST['csrf'])) {
		if (@$_POST["password1"] == @$_POST["password2"]) {
			if (trim(@$_POST["password1"]) != "") {
				$user->changeUserPass($_POST["exec_ref"], $_POST["password1"]) ;
				x_eventBoxPrep("Password has been changed!", "ok", _COOKIES_);
			} else  { x_eventBoxPrep("Passwords can not be empty!", "error", _COOKIES_); }
		} else  { x_eventBoxPrep("Passwords are not identical!", "error", _COOKIES_); }
	} else  { x_eventBoxPrep("CSRF Error - Retry!", "error", _COOKIES_); }
} 
	
	echo '<div  class="content_box" style="max-width: 800px;text-align: center;"><a href="./?site=users&edit=add" class="sysbutton">Add new User</a></div>';		
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
							echo $value.",";
						}
					} elseif ($curissuer["user_rank"] == 0) { echo " Administrator"; }	
					else { echo "Keine";}
				
				echo "</div>";
				echo "<div style='width: 20%;float:left;'>".@$curissuer["created_date"]."</div>";
				echo "<div style='width: 40%;float:left;'>";	

					echo "<a class='sysbutton' href='./?site=users&session=".$curissuer["id"]."'>Sessions</a> ";
					echo "<a class='sysbutton' href='./?site=users&pass=".$curissuer["id"]."'>Password</a> ";
					echo "<a class='sysbutton' href='./?site=users&edit=".$curissuer["id"]."'>Edit</a> ";
					if(@$curissuer["user_rank"] != 0) { echo "<a href='./?site=users&delete=".$curissuer["id"]."'>Delete</a>"; }
				
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
					<div style="float:left"><input type="checkbox" name="perm_logs" <?php if($permsobj->hasPerm($curid, "perm_logs")) { echo "checked"; } ?>> View Log	</div>
					<div style="float:left"><input type="checkbox" name="perm_status" <?php if($permsobj->hasPerm($curid, "perm_status")) { echo "checked"; } ?>> View Status	</div>
					<div style="float:left"><input type="checkbox" name="perm_server" <?php if($permsobj->hasPerm($curid, "perm_server")) { echo "checked"; } ?>> Manage Servers	</div>
					<div style="float:left"><input type="checkbox" name="perm_users" <?php if($permsobj->hasPerm($curid, "perm_users")) { echo "checked"; } ?>> Manage Users</div>
					<!--<div style="float:left"><input type="checkbox" name="perm_domains_my" > Create and Mange own Domains	</div>-->
					<div style="float:left"><input type="checkbox" name="perm_domains_bind" <?php if($permsobj->hasPerm($curid, "perm_domains_bind")) { echo "checked"; } ?>> View Local Master Domains	</div>
					<div style="float:left"><input type="checkbox" name="perm_domains_api" <?php if($permsobj->hasPerm($curid, "perm_domains_api")) { echo "checked"; } ?>> View Slave Domains and Conflicts	</div>
					<!-- <div style="float:left"><input type="checkbox" name="perm_conflict" <?php if($permsobj->hasPerm($curid, "perm_conflict")) { echo "checked"; } ?>> View Conflict Area	</div> -->
				<?php } else { echo "This is the Initial User, which does have all Privileges!"; } ?>
				<?php if(is_numeric(@$_GET["edit"])) { ?><input type="hidden" value="<?php echo @$_GET["edit"]; ?>" name="exec_ref"><?php } ?>
			</div>		
			<div class="internal_popup_submit"><input type="submit" value="Execute" name="exec_edit"><a href="./?site=users">Cancel</a></div></form>
		</div>
	</div>
<?php } ?>
<?php if($user->exists(@$_GET["delete"])) { ?>	
	<div class="internal_popup">
		<div class="internal_popup_inner">
			<div class="internal_popup_title">Delete: <?php echo $user->get($_GET["delete"])["user_name"]; ?></div>
			<div class="internal_popup_submit"><form method="post" action="./?site=users"><input type="hidden" value="<?php echo @$_GET["delete"]; ?>" name="exec_ref"><input type="submit" value="Execute" name="exec_del"></form><a href="./?site=users">Cancel</a></div>		
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
			
			<div class="internal_popup_submit"><a href="./?site=users">Cancel</a></div>		
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
			<div class="internal_popup_submit"><input type="hidden" value="<?php echo @$_GET["pass"]; ?>" name="exec_ref"><input type="submit" value="Execute" name="exec_pw"><a href="./?site=users">Cancel</a></div>	</form>	
		</div>
	</div>
<?php } 
}?>