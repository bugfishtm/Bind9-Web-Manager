<?php 
	/* 	
		.........%%%%%...%%..%%...%%%%...%%..%%..%%%%%%..%%%%%%..%%%%%..
		.........%%..%%..%%%.%%..%%......%%..%%....%%......%%....%%..%%.
		.........%%..%%..%%.%%%...%%%%...%%%%%%....%%......%%....%%%%%..
		.........%%..%%..%%..%%......%%..%%..%%....%%......%%....%%.....
		.........%%%%%...%%..%%...%%%%...%%..%%....%%......%%....%%.....
		................................................................
					PHP DNS Software by Jan-Maurice "Bugfish" Dahlmanns
	*/

	#	Copyright (C) 2026 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software: you can redistribute it and/or modify
	#	it under the terms of the GNU General Public License as published by
	#	the Free Software Foundation, either version 3 of the License, or
	#	(at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU General Public License for more details.

	#	You should have received a copy of the GNU General Public License
	#	along with this program.  If not, see <https://www.gnu.org/licenses/>.
	
	/*************************************************************************
		Disable Hardlinking
	*************************************************************************/
	if(!defined("_SQL_USER_")) { @http_response_code(404); Header("Location: ../"); exit(); }
	
	/*************************************************************************
		Get Current User Information
	*************************************************************************/
	$current_user = $user->get();
	
	/*************************************************************************
		Password Change Form Execute Operation
	*************************************************************************/
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if ($csrf->check($_POST['csrf'])) {
			$current_hash = $current_user["user_pass"];
			if ($user->password_check(@$_POST["passwordc"], $current_hash)) {
				if (@$_POST["password1"] == @$_POST["password2"]) {
					if (trim(@$_POST["password1"] ?? '') != "") {
						if (strlen(@$_POST["password1"]) <= 33) {
							if (strlen(@$_POST["password1"]) >= 8) {
								if ((preg_match('/[0-9]/', $_POST["password1"]) && preg_match('/[a-z]/', $_POST["password1"]) && preg_match('/[A-Z]/', $_POST["password1"]))) {
									$user->changeUserPass($user->user_id, $_POST["password1"]) ;
									x_eventBoxPrep("Password has been changed!", "ok", _COOKIES_);
								} else  { x_eventBoxPrep("The password must be between 8 and 32 characters long and include at least one uppercase letter, one lowercase letter, and one numeric digit.", "error", _COOKIES_);  }
								//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has changed his password on the profile page.", "user_".$current_user["id"]);
							} else  { x_eventBoxPrep("The password must be between 8 and 32 characters long and include at least one uppercase letter, one lowercase letter, and one numeric digit.", "error", _COOKIES_); }
						} else  { x_eventBoxPrep("The password must be between 8 and 32 characters long and include at least one uppercase letter, one lowercase letter, and one numeric digit.", "error", _COOKIES_); }
					} else  { x_eventBoxPrep("The password fields cannot be left empty. Please complete all required fields before submitting the form.", "error", _COOKIES_); }
				} else  { x_eventBoxPrep("The new passwords you entered do not match. Please ensure both fields are identical and try again.", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("To change your password, please ensure you enter your current password correctly in the designated field before proceeding.", "error", _COOKIES_); }
		} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
	}  

	/*************************************************************************
		Generate / Regenerate new API Access Token
	*************************************************************************/
	if(@$_GET["op"] == "generate" || @$_GET["op"] == "regenerate") {
		if ($csrf->check(@$_GET['csrf'])) {
			$key = substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 64)), 0, 64);
			$mysql->query("UPDATE "._TABLE_USER_." SET ext_api_key = '".$key."' WHERE id = ".$user->user_id."");
			//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has generated an API Key for external access.", "user_".$current_user["id"]);
			x_eventBoxPrep("The external API Access Token on your account has been regenerated, you can now execute external commands via the api interface!", "ok", _COOKIES_);
			Header("Location: ./?site=profile");
			exit();
		} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
	}
	
	/*************************************************************************
		Clear API Access Token
	*************************************************************************/
	if(@$_GET["op"] == "remove") {
		if ($csrf->check(@$_GET['csrf'])) {
			$mysql->query("UPDATE "._TABLE_USER_." SET ext_api_key = '' WHERE id = ".$user->user_id."");
			//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has removed his API Key for external access.", "user_".$current_user["id"]);
			x_eventBoxPrep("The external API Access Token and possibility for external commands on your user account has been disabled!", "ok", _COOKIES_);
			Header("Location: ./?site=profile");
			exit();
		} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
	}
	
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Profile");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>
	
	<!----------------- Javascript for API Key Operations -------------------->
	<script>
	
		function dnshttp_userui_api_gen() {
			window.location.href = "./?site=profile&op=generate&csrf=<?php echo $csrf->get(); ?>";
		}
	
		function dnshttp_userui_api_regen() {
			window.location.href = "./?site=profile&op=regenerate&csrf=<?php echo $csrf->get(); ?>";
		}
	
		function dnshttp_userui_api_rm() {
			window.location.href = "./?site=profile&op=remove&csrf=<?php echo $csrf->get(); ?>";
		}
	
		function dnshttp_userui_api_show() {
			document.getElementById('api_output_read').type = 'text';
		}
	
		function dnshttp_userui_api_hide() {
			document.getElementById('api_output_read').type = 'password';
		}
	
		function dnshttp_userui_api_copy() {
			navigator.clipboard.writeText(document.getElementById('api_output_read').value);
		}
	
	</script>
	
	<!----------------- Modals -------------------->
	<div class="modal" id="cpage_apigen_item_modal" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title">Create API Access</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		  </div>
		  <div class="modal-body">
			<p>Create an API Access Token to access functionalities based on your account permissions. Never share your token with unauthorized personnel.</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-warning" onClick="dnshttp_userui_api_gen()">Generate</button>
		  </div>
		</div>
	  </div>
	</div>
	
	<div class="modal" id="cpage_apiregen_item_modal" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title">Regenerate API Token</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		  </div>
		  <div class="modal-body">
			<p>Recreate your API Access Token to access functionalities based on your account permissions. Never share your token with unauthorized personnel. This will remove the possibility to execute external commands via the api interface on the previous used API Access Token.</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-warning" onClick="dnshttp_userui_api_regen()">Regenerate</button>
		  </div>
		</div>
	  </div>
	</div>
	
	<div class="modal" id="cpage_apirm_item_modal" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title">Remove API Access</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		  </div>
		  <div class="modal-body">
			<p>Do you really want to delete the external API Access Token, this will remove the possibility to execute external commands via the api interface.</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-danger" onClick="dnshttp_userui_api_rm()">Remove</button>
		  </div>
		</div>
	  </div>
	</div>
			
	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3" role="alert">Welcome '<b><?php echo htmlspecialchars($user->user_name ?? ''); ?></b>', here you can see some information about your profile and permissions. You can also change your password and api access key. </div>
    <?php if($user->user_rank == 0) { ?> <div class="callout callout-warning mb-3" role="alert">You are the administrative super-user, and you always have all privileges. </div> <?php } ?>

	<!----------------- Show Form for Password Change  -------------------->
	<div class="card card-dark mb-4">
	  <!--begin::Header-->
	  <div class="card-header">
		<div class="card-title">Change Account Password</div>
	  </div>
	  <!--end::Header-->
	  <!--begin::Form-->
	  <form method="post">
		<!--begin::Body-->
		<div class="card-body">
		
		  <div class="mb-3">
			<label for="exampleInputPassword1" class="form-label">Current Password</label>
			<input type="password" name='passwordc' class="form-control" placeholder="Current Password" autocomplete="off" required/>
		  </div>
		  
		  <div class="mb-3">
			<label for="exampleInputPassword1" class="form-label">New Password <br /><small>(Min 10/Max 32 Signs)</small></label>
			<input type="password" name='password1' class="form-control" placeholder="New Password" autocomplete="off" required/>
		  </div>
		  
		  <div class="mb-3">
			<label for="exampleInputPassword1" class="form-label">New Password Confirm <br /><small>(Min 10/Max 32 Signs)</small></label>
			<input type="password" name='password2' class="form-control" placeholder="New Password Confirmation" autocomplete="off" required/>
		  </div>
		  
		</div>
		<!--end::Body-->
		<!--begin::Footer-->
		<div class="card-footer">
		  <?php echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>"; ?>
		  <button type="submit" class="btn btn-warning">Change Password</button>
		</div>
		<!--end::Footer-->
	  </form>
	  <!--end::Form-->
	</div>
	
	<!----------------- Show Form for API Access Change  -------------------->
	<?php if($permsobj->hasPerm($user->user_id, "api") OR $user->user_rank == 0) { ?>
		<div class="card card-dark mb-4">
		  <!--begin::Header-->
		  <div class="card-header">
			<div class="card-title">API Access</div>
		  </div>
		  <!--end::Header-->
		  <!--begin::Form-->
		  <form method="post" id="dnshttp_userid_apic_form">
			<!--begin::Body-->
			<?php if(trim($current_user["ext_api_key"] ?? '') != "") { ?>
				<div class="card-body">
				  <div class="mb-3">
					<label for="exampleInputPassword1" class="form-label">Current Access Token</label>
					<input type="password" name='current_api_key' value="<?php echo htmlentities($current_user["ext_api_key"] ?? ''); ?>" id="api_output_read" class="form-control" autocomplete="off" readonly/>
				  </div>
				</div>
			<?php } ?>
			<!--end::Body-->
			<!--begin::Footer--> 
			<div class="card-footer">
			  <?php echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>"; ?>
			  <?php echo "<input name='api_form_request_exec' type='hidden' value='1'>"; ?>
			  <?php if(trim($current_user["ext_api_key"] ?? '') == "") { ?>
				  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cpage_apigen_item_modal">Generate</button>
			  <?php } else { ?>
				  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cpage_apiregen_item_modal">Regenerate</button>
				  <button type="button" class="btn btn-dark" onClick="dnshttp_userui_api_show()">Show</button>
				  <button type="button" class="btn btn-dark" onClick="dnshttp_userui_api_hide()">Hide</button>
				  <button type="button" class="btn btn-dark" onClick="dnshttp_userui_api_copy()">Copy</button>
				  <button type="button" class="btn btn-danger"  data-bs-toggle="modal" data-bs-target="#cpage_apirm_item_modal">Delete</button>
			  <?php } ?>
			</div>
			<!--end::Footer-->
		  </form>
		  <!--end::Form-->
		</div>
	<?php } ?>
		  
	<!----------------- Show Permissions for User -------------------->
	<h5 class="mb-2">Permissions</h5>
	
	<div class="row">
	  
	  <?php $permission_name = "Domain Create"; if($permsobj->hasPerm($user->user_id, "domain_create") OR $user->user_rank == 0) { ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-success shadow-sm">
				<i class="bi bi-hand-thumbs-up-fill"></i>
			  </span>

			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Granted</span>
			  </div>
			</div>
		  </div>
	  <?php } else {  ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-danger shadow-sm">
				<i class="bi bi-hand-thumbs-down-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Denied</span>
			  </div>
			</div>
		  </div>
	  <?php } ?>
	  
	  <?php $permission_name = "Domain Admin"; if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-success shadow-sm">
				<i class="bi bi-hand-thumbs-up-fill"></i>
			  </span>

			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Granted</span>
			  </div>
			</div>
		  </div>
	  <?php } else {  ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-danger shadow-sm">
				<i class="bi bi-hand-thumbs-down-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Denied</span>
			  </div>
			</div>
		  </div>
	  <?php } ?>
		  
	  <?php $permission_name = "Domain Conflicts"; if($permsobj->hasPerm($user->user_id, "domain_conflicts") OR $user->user_rank == 0) { ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-success shadow-sm">
				<i class="bi bi-hand-thumbs-up-fill"></i>
			  </span>

			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Granted</span>
			  </div>
			</div>
		  </div>
	  <?php } else {  ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-danger shadow-sm">
				<i class="bi bi-hand-thumbs-down-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Denied</span>
			  </div>
			</div>
		  </div>
	  <?php } ?>
	  
	  <?php $permission_name = "Servers Section"; if($permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-success shadow-sm">
				<i class="bi bi-hand-thumbs-up-fill"></i>
			  </span>

			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Granted</span>
			  </div>
			</div>
		  </div>
	  <?php } else {  ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-danger shadow-sm">
				<i class="bi bi-hand-thumbs-down-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Denied</span>
			  </div>
			</div>
		  </div>
	  <?php } ?>
		  
	  <?php $permission_name = "Users Section"; if($permsobj->hasPerm($user->user_id, "users") OR $user->user_rank == 0) { ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-success shadow-sm">
				<i class="bi bi-hand-thumbs-up-fill"></i>
			  </span>

			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Granted</span>
			  </div>
			</div>
		  </div>
	  <?php } else {  ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-danger shadow-sm">
				<i class="bi bi-hand-thumbs-down-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Denied</span>
			  </div>
			</div>
		  </div>
	  <?php } ?>
	
	  <?php $permission_name = "System Section"; if($permsobj->hasPerm($user->user_id, "system") OR $user->user_rank == 0) { ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-success shadow-sm">
				<i class="bi bi-hand-thumbs-up-fill"></i>
			  </span>

			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Granted</span>
			  </div>
			</div>
		  </div>
	  <?php } else {  ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-danger shadow-sm">
				<i class="bi bi-hand-thumbs-down-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Denied</span>
			  </div>
			</div>
		  </div>
	  <?php } ?>	
	
	  <?php $permission_name = "API Access"; if($permsobj->hasPerm($user->user_id, "api") OR $user->user_rank == 0) { ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-success shadow-sm">
				<i class="bi bi-hand-thumbs-up-fill"></i>
			  </span>

			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Granted</span>
			  </div>
			</div>
		  </div>
	  <?php } else {  ?>
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-danger shadow-sm">
				<i class="bi bi-hand-thumbs-down-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text"><?php echo $permission_name; ?></span>
				<span class="info-box-number">Denied</span>
			  </div>
			</div>
		  </div>
	  <?php } ?>	
	  
	</div>					
			
	<?php
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");