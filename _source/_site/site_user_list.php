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
		Edit a User
	*************************************************************************/
	if($current_user = $user->get(@$_GET["id"])) {
		
		/*************************************************************************
			Renewed CSRF Key
		*************************************************************************/
		$csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"]."_id_".@$_GET["id"], _CSRF_VALID_LIMIT_TIME_);

		/*************************************************************************
			Permission Change
		*************************************************************************/
		if (trim(@$_GET["toggleperm"] ?? '') != "") {
			if ($csrf->check(@$_GET['csrf'])) {

				if(@$_GET["toggleperm"] == "domain_create") {
					if($permsobj->hasPerm($_GET["id"], "domain_create")) {
						$permsobj->removePerm($_GET["id"], "domain_create");
					} else {
						$permsobj->addPerm($_GET["id"], "domain_create");
					}
				}
				
				if(@$_GET["toggleperm"] == "domain_admin") {
					if($permsobj->hasPerm($_GET["id"], "domain_admin")) {
						$permsobj->removePerm($_GET["id"], "domain_admin");
					} else {
						$permsobj->addPerm($_GET["id"], "domain_admin");
					}
				}				
				
				if(@$_GET["toggleperm"] == "domain_conflicts") {
					if($permsobj->hasPerm($_GET["id"], "domain_conflicts")) {
						$permsobj->removePerm($_GET["id"], "domain_conflicts");
					} else {
						$permsobj->addPerm($_GET["id"], "domain_conflicts");
					}
				}
				
				if(@$_GET["toggleperm"] == "servers") {
					if($permsobj->hasPerm($_GET["id"], "servers")) {
						$permsobj->removePerm($_GET["id"], "servers");
					} else {
						$permsobj->addPerm($_GET["id"], "servers");
					}
				}
			
				if(@$_GET["toggleperm"] == "users") {
					if($permsobj->hasPerm($_GET["id"], "users")) {
						$permsobj->removePerm($_GET["id"], "users");
					} else {
						$permsobj->addPerm($_GET["id"], "users");
					}
				}
			
				if(@$_GET["toggleperm"] == "system") {
					if($permsobj->hasPerm($_GET["id"], "system")) {
						$permsobj->removePerm($_GET["id"], "system");
					} else {
						$permsobj->addPerm($_GET["id"], "system");
					}
				}
			
				if(@$_GET["toggleperm"] == "api") {
					if($permsobj->hasPerm($_GET["id"], "api")) {
						$permsobj->removePerm($_GET["id"], "api");
					} else {
						$permsobj->addPerm($_GET["id"], "api");
					}
				}			
				
				x_eventBoxPrep("Permission has been changed!", "ok", _COOKIES_);
				Header("Location: ./?site=user_list&id=".$_GET["id"]."#card_for_permissions");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}
		
		/*************************************************************************
			Password Change
		*************************************************************************/
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if ($csrf->check($_POST['csrf'])) {
				if (@$_POST["password1"] == @$_POST["password2"]) {
					if (trim(@$_POST["password1"] ?? '') != "") {
						if (strlen(@$_POST["password1"]) <= 33) {
							if (strlen(@$_POST["password1"]) >= 8) {
								if ((preg_match('/[0-9]/', $_POST["password1"]) && preg_match('/[a-z]/', $_POST["password1"]) && preg_match('/[A-Z]/', $_POST["password1"]))) {
									$user->changeUserPass($_GET["id"], $_POST["password1"]) ;
									x_eventBoxPrep("Password has been changed!", "ok", _COOKIES_);
								} else  { x_eventBoxPrep("The password must be between 8 and 32 characters long and include at least one uppercase letter, one lowercase letter, and one numeric digit.", "error", _COOKIES_);  }
								//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has changed his password on the profile page.", "user_".$current_user["id"]);
							} else  { x_eventBoxPrep("The password must be between 8 and 32 characters long and include at least one uppercase letter, one lowercase letter, and one numeric digit.", "error", _COOKIES_); }
						} else  { x_eventBoxPrep("The password must be between 8 and 32 characters long and include at least one uppercase letter, one lowercase letter, and one numeric digit.", "error", _COOKIES_); }
					} else  { x_eventBoxPrep("The password fields cannot be left empty. Please complete all required fields before submitting the form.", "error", _COOKIES_); }
				} else  { x_eventBoxPrep("The new passwords you entered do not match. Please ensure both fields are identical and try again.", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}  		
				
		/*************************************************************************
			Block/Únblock User
		*************************************************************************/
		if(@$_GET["op"] == "unblock") {
			if ($csrf->check(@$_GET['csrf'])) {
				$mysql->query("UPDATE "._TABLE_USER_." SET user_blocked = 0, block_auto = 0 WHERE id = ".$_GET["id"]." AND user_rank <> 0");
				//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has removed his API Key for external access.", "user_".$current_user["id"]);
				x_eventBoxPrep("This users login has been enabled.", "ok", _COOKIES_);
				Header("Location: ./?site=user_list&id=".$_GET["id"]."");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}						
				
		/*************************************************************************
			Block/Únblock User
		*************************************************************************/
		if(@$_GET["op"] == "block") {
			if ($csrf->check(@$_GET['csrf'])) {
				$mysql->query("UPDATE "._TABLE_USER_." SET user_blocked = 1 WHERE id = ".$_GET["id"]."  AND user_rank <> 0");
				//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has removed his API Key for external access.", "user_".$current_user["id"]);
				x_eventBoxPrep("This users login has been disabled.", "ok", _COOKIES_);
				Header("Location: ./?site=user_list&id=".$_GET["id"]."");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}								
				
		/*************************************************************************
			Generate / Regenerate new API Access Token
		*************************************************************************/
		if(@$_GET["op"] == "generate" || @$_GET["op"] == "regenerate") {
			if ($csrf->check(@$_GET['csrf'])) {
				$key = substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 64)), 0, 64);
				$mysql->query("UPDATE "._TABLE_USER_." SET ext_api_key = '".$key."' WHERE id = ".$_GET["id"]."");
				//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has generated an API Key for external access.", "user_".$current_user["id"]);
				x_eventBoxPrep("The external API Access Token on your account has been regenerated, you can now execute external commands via the api interface!", "ok", _COOKIES_);
				Header("Location: ./?site=user_list&id=".$_GET["id"]."");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}
		
		/*************************************************************************
			Clear API Access Token
		*************************************************************************/
		if(@$_GET["op"] == "remove") {
			if ($csrf->check(@$_GET['csrf'])) {
				$mysql->query("UPDATE "._TABLE_USER_." SET ext_api_key = '' WHERE id = ".$_GET["id"]."");
				//$log->info("User <b>#".$user->user_id."</b> (".htmlspecialchars($user->user_name ?? '').") has removed his API Key for external access.", "user_".$current_user["id"]);
				x_eventBoxPrep("The external API Access Token and possibility for external commands on your user account has been disabled!", "ok", _COOKIES_);
				Header("Location: ./?site=user_list&id=".$_GET["id"]."");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}				
					
		/*************************************************************************
			Include Header
		*************************************************************************/
		define("_SUB_PAGE_TITLE_", "User Details for  #".@$_GET["id"]."");
		require_once("./_default/default_header.php");
		require_once("./_default/default_navigation.php");
		echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>
		
		<!----------------- Javascript for API Key Operations -------------------->
		<script>
		
			function dnshttp_userui_api_gen() {
				window.location.href = "./?site=user_list&op=generate&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
			}
		
			function dnshttp_userui_api_regen() {
				window.location.href = "./?site=user_list&op=regenerate&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
			}
		
			function dnshttp_userui_api_rm() {
				window.location.href = "./?site=user_list&op=remove&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
			}
		
			function dnshttp_userui_block() {
				window.location.href = "./?site=user_list&op=block&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
			}
		
			function dnshttp_userui_unblock() {
				window.location.href = "./?site=user_list&op=unblock&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
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
		
		<div class="modal" id="cpage_block_item_modal" tabindex="-1">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title">Block User</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			  </div>
			  <div class="modal-body">
				<p>Do you rally want to block this user? The user will not be able to login, the domains of the user will stay active.</p>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-warning" onClick="dnshttp_userui_block()">Block User</button>
			  </div>
			</div>
		  </div>
		</div>
		
		<div class="modal" id="cpage_unblock_item_modal" tabindex="-1">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title">Unblock User</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			  </div>
			  <div class="modal-body">
				<p>Do you want the user to be able to login again?</p>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-warning" onClick="dnshttp_userui_unblock()">Unblock User</button>
			  </div>
			</div>
		  </div>
		</div>		
		
		<!----------------- Information Callout -------------------->
		<div class="callout callout-info mb-3" role="alert">Here you can find details about the user '<b><?php echo htmlspecialchars($current_user["user_name"] ?? ''); ?></b>' with identification number #<?php echo @$_GET["id"]; ?>.</div>
		<?php if($current_user["user_rank"] == 0) { ?> <div class="callout callout-warning mb-3" role="alert">This is the administrative super-user, and always has all privileges. </div> <?php } ?>
		<?php if($current_user["block_auto"] > 0 OR $current_user["user_blocked"] > 0) { ?> <div class="callout callout-danger mb-3" role="alert">This user is currently blocked and cannot login, the domains of this user will remain active. A user will automatically blocked when he reaches a limit of false logins in a row.</div> <?php } ?>

		<!----------------- User Listing and Details Section -------------------->
		<div class="row">
		
			<div class="col-md-5">	
			
				<a href="./?site=user_list" style="text-decoration: none !important; font-weight: bold;">
					<div class="info-box bg-light">
					  <span class="info-box-icon text-bg-dark shadow-sm">
						<i class="bi bi-arrow-return-left"></i>
					  </span>
					  <div class="info-box-content">
						<span class="info-box-text" style="text-decoration: none !important;">Return to Listing</span>
					  </div>
					</div>				
				</a>
				
				<div class="col-md-12">
					<div class="card card-dark mb-4 collapsed-card">
					  <div class="card-header">
						<div class="card-title">Login and Password</div>
					  </div>
					  <form method="post">
						<div class="card-body">
						  <div class="mb-3">
							<label for="exampleInputPassword1" class="form-label">New Password <br /><small>(Min 10/Max 32 Signs)</small></label>
							<input type="password" name='password1' class="form-control" placeholder="New Password" autocomplete="off" required/>
						  </div>
						  <div class="mb-3">
							<label for="exampleInputPassword1" class="form-label">New Password Confirmation <br /><small>(Min 10/Max 32 Signs)</small></label>
							<input type="password" name='password2' class="form-control" placeholder="New Password Confirmation" autocomplete="off" required/>
						  </div>
						</div>
						<div class="card-footer">
						  <?php echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>"; ?>
						  <?php echo "<input name='user_id' type='hidden' value='".$_GET["id"]."'>"; ?>
						  <button type="submit" class="btn btn-warning">Change Password</button>
						  <?php if($current_user["user_rank"] != 0) { ?>
							  <?php if($current_user["block_auto"] > 0 OR $current_user["user_blocked"] > 0) { ?>
								  <button type="button" class="btn btn-warning btnmarg" data-bs-toggle="modal" data-bs-target="#cpage_unblock_item_modal">Enable Login</button>
							  <?php } else { ?>
								  <button type="button" class="btn btn-warning btnmarg" data-bs-toggle="modal" data-bs-target="#cpage_block_item_modal">Disable Login</button>
							  <?php } ?>
						  <?php } ?>
						</div>
					  </form>
					</div>		
				</div>

				<div class="card card-dark mb-4">
				  <div class="card-header">
					<div class="card-title">API Access Token</div>
				  </div>
				  <form method="post" id="dnshttp_userid_apic_form">
					<?php if(trim($current_user["ext_api_key"] ?? '') != "") { ?>
						<div class="card-body">
						  <div class="mb-3">
							<label for="exampleInputPassword1" class="form-label">Current Access Token</label>
							<input type="password" name='current_api_key' value="<?php echo htmlentities($current_user["ext_api_key"] ?? ''); ?>" id="api_output_read" class="form-control" autocomplete="off" readonly/>
						  </div>
						</div>
					<?php } ?>
					<div class="card-footer">
					  <?php echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>"; ?>
					  <?php echo "<input name='api_form_request_exec' type='hidden' value='1'>"; ?>
					  <?php if(trim($current_user["ext_api_key"] ?? '') == "") { ?>
						  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cpage_apigen_item_modal">Generate</button>
					  <?php } else { ?>
						  <button type="button" class="btn btn-warning btnmarg" data-bs-toggle="modal" data-bs-target="#cpage_apiregen_item_modal">Regenerate</button>
						  <button type="button" class="btn btn-dark btnmarg" onClick="dnshttp_userui_api_show()">Show</button>
						  <button type="button" class="btn btn-dark btnmarg" onClick="dnshttp_userui_api_hide()">Hide</button>
						  <button type="button" class="btn btn-dark btnmarg" onClick="dnshttp_userui_api_copy()">Copy</button>
						  <button type="button" class="btn btn-danger btnmarg"  data-bs-toggle="modal" data-bs-target="#cpage_apirm_item_modal">Delete</button>
					  <?php } ?>
					</div>
				  </form>
				</div>
			</div>		
			<div class="col-md-7">
				<div class="card card-dark mb-4 tablecard">
				  <!-- /.card-header -->
				  <div class="card-header">
					<h3 class="card-title">Domains</h3>
				  </div>
				  <div class="card-body p-0">
					<div class="scrolltableinit">
						<table class="table table-striped">
						  <thead>
							<tr>
							  <th>Domain</th>
							  <th style="width: 180px">Status</th>
							  <th style="width: 180px">Inspect</th>
							</tr>
						  </thead>
						  <tbody>
							<?php		  
								$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user = ".@$_GET["id"]." ORDER BY id DESC LIMIT 50", true); 
								if(is_array($res)) { 
									foreach ($res AS $key => $value) { 	
										?>
											<tr class="align-middle">
											  <td><?php echo htmlspecialchars($value["domain"] ?? ''); ?>
											  </td>
											  <td><?php
												if($value["registered"] == "1") { echo '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
												if($value["registered"] != "1") { echo '<span class="badge text-bg-warning" title="Not active for queries">NR</span> '; }
												if($value["conflict"] == "1") { echo '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
												if($value["preferred"] == "1") { echo '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
												if($value["set_no_replicate"] == "1") { echo '<span class="badge text-bg-danger" title="Replication to other servers Disabled">RD</span> '; }
												if($value["oldzonefallback"] == "1") { echo '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
												if($value["okonce"] == "0") { echo '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
											  ?></td>
											  <td>
												  <?php if($permsobj->hasPerm($current_user["id"], "domain_admin") OR $current_user["user_rank"] == 0) { ?>
													<?php echo " <a class='btn btn-dark btn-sm' title='Duplicates and Conflicts' href='./?site=domain_list&domain=".base64_encode(trim(strtolower($value["domain"] ?? '') ?? ''))."'><i class=\"bi bi-copy\"></i></a>"; ?> 
														<?php echo "<a class='btn btn-dark btn-sm' title='Details' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
												  <?php } else { ?>
													<?php echo "<a class='' title='Details' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
												  <?php }  ?>												  
											  </td>
											</tr>						
										<?php
									}
								} else { 
									?>
										<tr class="align-middle">
										  <td colspan="10">No Data Available</td>
										</tr>
									<?php
								}		 
							?>
						  </tbody>
						</table>
					</div>
				  </div>
				</div>		
			</div>
			
		</div>				
				
		<h4>Permissions</h4>
		<div class="row pb-0 mt-2 mx-1">
		  
		  <?php $permission_name = "Domain Create"; if($permsobj->hasPerm($current_user["id"], "domain_create") OR $current_user["user_rank"] == 0) { ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-success shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-up-fill"></i>
				  </span>

				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=domain_create" class="btn btn-danger btn-sm">Revoke</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } else {  ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-danger shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-down-fill"></i>
				  </span>
				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=domain_create" class="btn btn-success btn-sm">Grant</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } ?>
		  
		  <?php $permission_name = "Domain Admin"; if($permsobj->hasPerm($current_user["id"], "domain_admin") OR $current_user["user_rank"] == 0) { ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-success shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-up-fill"></i>
				  </span>

				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=domain_admin" class="btn btn-danger btn-sm">Revoke</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } else {  ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-danger shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-down-fill"></i>
				  </span>
				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=domain_admin" class="btn btn-success btn-sm">Grant</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } ?>
			  
		  <?php $permission_name = "Domain Conflicts"; if($permsobj->hasPerm($current_user["id"], "domain_conflicts") OR $current_user["user_rank"] == 0) { ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-success shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-up-fill"></i>
				  </span>

				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=domain_conflicts" class="btn btn-danger btn-sm">Revoke</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } else {  ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-danger shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-down-fill"></i>
				  </span>
				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=domain_conflicts" class="btn btn-success btn-sm">Grant</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } ?>
		  
		  <?php $permission_name = "Servers Area"; if($permsobj->hasPerm($current_user["id"], "servers") OR $current_user["user_rank"] == 0) { ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-success shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-up-fill"></i>
				  </span>

				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=servers" class="btn btn-danger btn-sm">Revoke</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } else {  ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-danger shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-down-fill"></i>
				  </span>
				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=servers" class="btn btn-success btn-sm">Grant</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } ?>
			  
		  <?php $permission_name = "Users Area"; if($permsobj->hasPerm($current_user["id"], "users") OR $current_user["user_rank"] == 0) { ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-success shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-up-fill"></i>
				  </span>

				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=users" class="btn btn-danger btn-sm">Revoke</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } else {  ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-danger shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-down-fill"></i>
				  </span>
				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=users" class="btn btn-success btn-sm">Grant</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } ?>
		
		  <?php $permission_name = "System Area"; if($permsobj->hasPerm($current_user["id"], "system") OR $current_user["user_rank"] == 0) { ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-success shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-up-fill"></i>
				  </span>

				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=system" class="btn btn-danger btn-sm">Revoke</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } else {  ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-danger shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-down-fill"></i>
				  </span>
				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=system" class="btn btn-success btn-sm">Grant</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } ?>	
		
		  <?php $permission_name = "API Access"; if($permsobj->hasPerm($current_user["id"], "api") OR $current_user["user_rank"] == 0) { ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-success shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-up-fill"></i>
				  </span>

				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=api" class="btn btn-danger btn-sm">Revoke</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } else {  ?>
			  <div class="col-md-4">
				<div class="info-box bg-light">
				  <span class="info-box-icon text-bg-danger shadow-sm" style="min-width: 70px; width: 70px;">
					<i class="bi bi-hand-thumbs-down-fill"></i>
				  </span>
				  <div class="info-box-content">
					<span class="info-box-text"><?php echo $permission_name; ?></span>
					<?php if($current_user["user_rank"] != 0) { ?><span class="info-box-number"><a href="./?site=user_list&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>&toggleperm=api" class="btn btn-success btn-sm">Grant</a></span><?php } ?>
				  </div>
				</div>
			  </div>
		  <?php } ?>	
		  
			</div>	

		<div class="card card-dark mb-4 tablecard">
		  <div class="card-header">
			<h3 class="card-title">User Sessions (Last 10 Sessions)</h3>
		  </div>
		  <div class="card-body p-0">
			  <div class="scrolltableinit">
				<table class="table table-striped">
				  <thead>
					<tr>
					  <th style="width: 60px">#</th>
					  <th>Status</th>
					  <th>Date</th>
					</tr>
				  </thead>
				  <tbody>
					<?php		  
						$res = $mysql->select("SELECT * FROM "._TABLE_USER_SESSION_." WHERE fk_user = ".@$_GET["id"]." ORDER BY id DESC LIMIT 10", true); 
						if(is_array($res)) { 
							foreach ($res AS $key => $value) { 	
								?>
									<tr class="align-middle">
									  <td><?php echo htmlspecialchars($value["id"] ?? ''); ?></td>
									  <td><?php if($value["is_active"] == "1") { echo '<span class="badge text-bg-success" title="User Login-Session is still active.">Active</span>'; } else { echo '<span class="badge text-bg-warning" title="User Login-Session is expired!">Expired</span>'; } ?></td>
									  <td><?php echo htmlspecialchars($value["refresh_date"] ?? ''); ?></td>
									</tr>						
								<?php
							}
						} else { 
							?>
								<tr class="align-middle">
								  <td colspan="10">No Data Available</td>
								</tr>
							<?php
						}		 
					?>
				  </tbody>
				</table>
			  </div>
		  </div>
		</div>	
				
		<?php
		/*************************************************************************
			Include Footer
		*************************************************************************/
		require_once("./_template/tpl_search.php");
		require_once("./_default/default_footer.php");			
		exit();
	}
	
	/*************************************************************************
		User Delete Operation
	*************************************************************************/
	if(is_numeric(@$_GET["deleteid"])) {
		if($csrf->check($_GET['csrf'])) {
			$cuser = $mysql->select("SELECT * FROM "._TABLE_USER_." WHERE id = ".@$_GET["deleteid"]."", false);
			if($cuser) { 
				if($cuser["user_rank"] != 0) { 
					$mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user = \"".$_GET["deleteid"]."\";");
					$mysql->query("DELETE FROM "._TABLE_USER_SESSION_." WHERE fk_user = \"".$_GET["deleteid"]."\";");
					$mysql->query("DELETE FROM "._TABLE_USER_." WHERE id = \"".$_GET["deleteid"]."\";");
					x_eventBoxPrep("The user and all user related domain and zone information has been removed.", "ok", _COOKIES_);
					Header("Location: ./?site=".@$_GET["site"].""); exit();
				} else {
					x_eventBoxPrep("You cannot delete the administrative user.", "error", _COOKIES_);
					Header("Location: ./?site=".@$_GET["site"].""); exit();
				}
			} else {
				x_eventBoxPrep("Error while trying to delete the user account.", "error", _COOKIES_);
				Header("Location: ./?site=".@$_GET["site"].""); exit();
			}
		} else { 
			x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); 
			Header("Location: ./?site=".@$_GET["site"].""); exit();
		}
	}
	
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Users");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>
			
	<!----------------- Javascript for Deletion -------------------->
	<script>
	
		var current_item_id = false;
		
		function dnshttp_cpage_delete_item_confirm(current_object, current_item_id_param) {
			current_item_id = current_item_id_param;
			current_object = current_object.parentElement;
			let el = current_object;
			while (el = el.previousElementSibling) {
			  if (el.classList.contains('pre_before_ip_getter')) {
				break;
			  }
			}			
			document.getElementById('cpage_delete_item_modal_ip').textContent = el.textContent;
		}
		
		function dnshttp_cpage_delete_item_confirm_exec() {
			window.location.href = "./?site=user_list&csrf=<?php echo $csrf->get(); ?>&deleteid="+current_item_id+"";
		}
		
	</script>
	
	<!----------------- Modal -------------------->
	<div class="modal" id="cpage_delete_item_modal" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title">Confirm Deletion</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		  </div>
		  <div class="modal-body">
			<p>Do you really want to delete the user '<b><span id="cpage_delete_item_modal_ip"></span></b>' and all his domain and record data? This action cannot be undone?</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-danger" onClick="dnshttp_cpage_delete_item_confirm_exec()">Delete</button>
		  </div>
		</div>
	  </div>
	</div>

	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3" role="alert">This page displays a complete list of all users, along with their account status. From here, you can view user details or delete users as needed.</div>

	<!----------------- Table Element  -------------------->
	<div class="card mb-4 tablecard">
	  <!-- /.card-header -->
	  <div class="card-body p-0">
		<table class="table table-striped">
		  <thead>
			<tr>
			  <th style="width: 60px">#</th>
			  <th>Username</th>
			  <th style="width: 110px">Status</th>
			  <th style="width: 180px">Operation</th>
			</tr>
		  </thead>
		  <tbody>
			<?php		  
				$res = $mysql->select("SELECT id, user_name, last_login, block_auto, user_rank, user_blocked FROM "._TABLE_USER_." ORDER BY id DESC", true); 
				if(is_array($res)) { 
					foreach ($res AS $key => $value) { 	
						?>
							<tr class="align-middle">
							  <td><?php echo htmlspecialchars($value["id"] ?? ''); ?></td>
							  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["user_name"] ?? ''); ?></td>
							  <td><?php if($value["user_blocked"] > 0 OR $value["block_auto"] > 0) { echo '<span class="badge text-bg-danger" title="This user cannot login, the domains of the user will still be processed.">Blocked</span>'; } else { if($value["user_rank"] == 0) { echo '<span class="badge text-bg-dark" title="This user is the super-administrator with special privileges.">Administrator</span>'; } else { echo '<span class="badge text-bg-success" title="The user is ready to login.">Active</span>'; } }  ?></td>
							  <td>
								<a class="btn btn-dark btn-sm" href="./?site=user_list&id=<?php echo $value["id"]; ?>">
								  Details
								</a>
								<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cpage_delete_item_modal" onClick="dnshttp_cpage_delete_item_confirm(this, <?php echo $value["id"]; ?>)">
								  Delete
								</button>
							  </td>
							</tr>						
						<?php
					}
				} else { 
					?>
						<tr class="align-middle">
						  <td colspan="10">No Data Available</td>
						</tr>
					<?php
				}		 
			?>
		  </tbody>
		</table>
	  </div>
	</div>
		 
	<?php
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");