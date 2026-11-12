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
		One-Time-Password (OTP) Operations
	*************************************************************************/
	$otp_enabled = (trim($current_user["user_2fa"] ?? '') != "");
	if ($_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_POST["otp_action"])) {
		if ($csrf->check(@$_POST['csrf'])) {
			// Enable OTP with the secret shown on the setup form
			if (@$_POST["otp_action"] == "enable" AND !$otp_enabled) {
				$otp_pending = @$_SESSION[dnshttp_otp_session_setup_name()];
				if (is_string($otp_pending) AND $otp_pending != "") {
					$otp_step = dnshttp_otp_check_code($otp_pending, @$_POST["otp_code"]);
					if ($otp_step !== false) {
						dnshttp_otp_enable($mysql, $user->user_id, $otp_pending, $otp_step);
						unset($_SESSION[dnshttp_otp_session_setup_name()]);
						dnshttp_otp_session_mark();
						x_eventBoxPrep("One-time password protection has been enabled. From now on you need a code from your authenticator app for every login!", "ok", _COOKIES_);
					} else { x_eventBoxPrep("The one-time password is invalid. Please check that the key has been added correctly to your authenticator app and that the time on your device is correct!", "error", _COOKIES_); }
				} else { x_eventBoxPrep("The setup key has expired, please add the new key shown below to your authenticator app and try again!", "error", _COOKIES_); }
			}
			// Generate a new setup secret
			elseif (@$_POST["otp_action"] == "renew" AND !$otp_enabled) {
				unset($_SESSION[dnshttp_otp_session_setup_name()]);
				x_eventBoxPrep("A new setup key has been generated!", "ok", _COOKIES_);
			}
			// Disable OTP - requires current password and a valid code
			elseif (@$_POST["otp_action"] == "disable" AND $otp_enabled) {
				if ($user->password_check(@$_POST["passwordc"], $current_user["user_pass"])) {
					if (dnshttp_otp_verify_user($mysql, $user->user_id, @$_POST["otp_code"])) {
						dnshttp_otp_disable($mysql, $user->user_id);
						x_eventBoxPrep("One-time password protection has been disabled for your account!", "ok", _COOKIES_);
					} else { x_eventBoxPrep("The one-time password is invalid or has already been used. Please wait for the next code of your authenticator app and try again!", "error", _COOKIES_); }
				} else { x_eventBoxPrep("To disable the one-time password protection, please enter your current password correctly!", "error", _COOKIES_); }
			}
		} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		Header("Location: ./?site=profile#card_for_otp");
		exit();
	}

	/*************************************************************************
		OTP Setup Secret (only shown while OTP is not enabled)
	*************************************************************************/
	if (!$otp_enabled) {
		if (!is_string(@$_SESSION[dnshttp_otp_session_setup_name()]) OR trim($_SESSION[dnshttp_otp_session_setup_name()]) == "") {
			$_SESSION[dnshttp_otp_session_setup_name()] = dnshttp_otp_generate_secret();
		}
		$otp_setup_secret = $_SESSION[dnshttp_otp_session_setup_name()];
		$otp_setup_uri    = dnshttp_otp_uri($user->user_name, $otp_setup_secret);
	}

	/*************************************************************************
		Password Change Form Execute Operation
	*************************************************************************/
	if ($_SERVER['REQUEST_METHOD'] === 'POST' AND !isset($_POST["otp_action"])) {
		if ($csrf->check($_POST['csrf'])) {
			$current_hash = $current_user["user_pass"];
			if ($user->password_check(@$_POST["passwordc"], $current_hash)) {
				if (@$_POST["password1"] == @$_POST["password2"]) {
					if (trim(@$_POST["password1"] ?? '') != "") {
						if (strlen(@$_POST["password1"]) <= 32) {
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
			// str_shuffle() uses the non-cryptographic Mt19937 generator seeded with 32 bits, and
			// shuffling a fixed multiset caps the real entropy far below the 64 characters this
			// looks like. dnshttp_api_token_generate() draws from random_int().
			$key = dnshttp_api_token_generate(64);
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
    <div class="callout callout-info mb-3" role="alert">Welcome '<b><?php echo htmlspecialchars($user->user_name ?? ''); ?></b>', here you can see some information about your profile and permissions. You can also change your password, one-time password protection and api access key. </div>
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
			<label for="exampleInputPassword1" class="form-label">New Password <br /><small>(Min 8/Max 32 Signs)</small></label>
			<input type="password" name='password1' class="form-control" placeholder="New Password" autocomplete="off" required/>
		  </div>
		  
		  <div class="mb-3">
			<label for="exampleInputPassword1" class="form-label">New Password Confirm <br /><small>(Min 8/Max 32 Signs)</small></label>
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

	<!----------------- Show Form for One-Time-Password (OTP)  -------------------->
	<div class="card card-dark mb-4" id="card_for_otp">
	  <!--begin::Header-->
	  <div class="card-header">
		<div class="card-title">One-Time Password (Two-Factor Authentication)</div>
	  </div>
	  <!--end::Header-->
	  <?php if($otp_enabled) { ?>
		  <!--begin::Form-->
		  <form method="post">
			<div class="card-body">
			  <div class="callout callout-success mb-3" role="alert"><i class="bi bi-shield-lock-fill"></i> One-time password protection is <b>enabled</b> for your account. Every login requires a code from your authenticator app.</div>
			  <p>To disable the protection, enter your current password and a current code from your authenticator app.</p>
			  <div class="mb-3">
				<label class="form-label">Current Password</label>
				<input type="password" name='passwordc' class="form-control" placeholder="Current Password" autocomplete="off" required/>
			  </div>
			  <div class="mb-3">
				<label class="form-label">One-Time Password</label>
				<input type="text" name='otp_code' class="form-control" placeholder="6-digit code" maxlength="7" inputmode="numeric" pattern="[0-9 ]{6,7}" autocomplete="one-time-code" required/>
			  </div>
			</div>
			<div class="card-footer">
			  <?php echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>"; ?>
			  <input name='otp_action' type='hidden' value='disable'>
			  <button type="submit" class="btn btn-danger">Disable OTP</button>
			</div>
		  </form>
		  <!--end::Form-->
	  <?php } else { ?>
		  <!--begin::Form-->
		  <form method="post">
			<div class="card-body">
			  <div class="callout callout-warning mb-3" role="alert"><i class="bi bi-shield-exclamation"></i> One-time password protection is <b>disabled</b> for your account. Enable it for extra login security.</div>
			  <p>
				1. Add the following key to an authenticator app (time-based / TOTP, 6 digits, 30 seconds), for example Aegis, FreeOTP, Google Authenticator or Microsoft Authenticator.<br />
				2. Enter the code shown by the app below and press <b>Enable OTP</b>.<br />
				<small>If you lose access to your authenticator app, a user with the users permission can reset the one-time password of your account. For the administrative super-user, clear the <i>user_2fa</i> column of the user in the MySQL users table.</small>
			  </p>
			  <div class="mb-3">
				<label class="form-label">Setup Key</label>
				<div class="input-group">
				  <input type="text" id="otp_setup_secret" class="form-control" style="font-family: monospace;" value="<?php echo htmlspecialchars(trim(chunk_split($otp_setup_secret, 4, " "))); ?>" autocomplete="off" readonly/>
				  <button type="button" class="btn btn-dark" onClick="navigator.clipboard.writeText('<?php echo htmlspecialchars($otp_setup_secret); ?>')">Copy</button>
				</div>
				<small><a href="<?php echo htmlspecialchars($otp_setup_uri); ?>">Open in authenticator app</a> (on devices with an installed authenticator app)</small>
			  </div>
			  <div class="mb-3">
				<label class="form-label">One-Time Password</label>
				<input type="text" name='otp_code' class="form-control" placeholder="6-digit code" maxlength="7" inputmode="numeric" pattern="[0-9 ]{6,7}" autocomplete="one-time-code" required/>
			  </div>
			</div>
			<div class="card-footer">
			  <?php echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>"; ?>
			  <button type="submit" name="otp_action" value="enable" class="btn btn-warning">Enable OTP</button>
			  <button type="submit" name="otp_action" value="renew" class="btn btn-dark" formnovalidate>New Key</button>
			</div>
		  </form>
		  <!--end::Form-->
	  <?php } ?>
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