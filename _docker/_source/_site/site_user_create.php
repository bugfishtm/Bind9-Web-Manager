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
		Create the new User
	*************************************************************************/	
	if(trim(@$_POST["username"] ?? '') != "") {
		if(!$csrf->check($_POST["csrf"])) { 
			x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_);
		} else {
			if($user->username_exists(@$_POST["username"])) {	
				x_eventBoxPrep("A user with this name is already existant, please select another name for the new user!", "error", _COOKIES_);
			} else {		
			
				$valid_username = true;
				if (strlen(@$_POST["username"]) <= 20) {
					if (strlen(@$_POST["username"]) >= 4) {
					} else  { $valid_username = false;  }
				} else  { $valid_username = false; }
			
				$valid_password = true;
				if (@$_POST["password1"] == @$_POST["password2"]) {
					if (trim(@$_POST["password1"]) != "") {
						if (strlen(@$_POST["password1"]) <= 33) {
							if (strlen(@$_POST["password1"]) >= 8) {
								if ((preg_match('/[0-9]/', $_POST["password1"]) && preg_match('/[a-z]/', $_POST["password1"]) && preg_match('/[A-Z]/', $_POST["password1"]))) {
								} else  { $valid_password = false;  }
							} else  { $valid_password = false;  }
						} else  { $valid_password = false;   }
					} else  { $valid_password = false;  }
				} else  { $valid_password = false; }
				if($valid_password AND $valid_username) {
					$user->addUser(@$_POST["username"], "undefined", @$_POST["password1"], 1, 1);
					x_eventBoxPrep("You have successfully created the new user account!", "ok", _COOKIES_);
					$userid = $mysql->insert_id;
					if(isset($_POST["domain_create"])) 		{ $permsobj->addPerm($userid, "domain_create"); } else { $permsobj->removePerm($userid, "domain_create"); }
					if(isset($_POST["domain_admin"])) 		{ $permsobj->addPerm($userid, "domain_admin"); } else { $permsobj->removePerm($userid, "domain_admin"); }
					if(isset($_POST["domain_conflicts"])) 	{ $permsobj->addPerm($userid, "domain_conflicts"); } else { $permsobj->removePerm($userid, "domain_conflicts"); }
					if(isset($_POST["servers"])) 			{ $permsobj->addPerm($userid, "servers"); } else { $permsobj->removePerm($userid, "servers"); }
					if(isset($_POST["users"])) 				{ $permsobj->addPerm($userid, "users"); } else { $permsobj->removePerm($userid, "users"); }
					if(isset($_POST["system"])) 			{ $permsobj->addPerm($userid, "system"); } else { $permsobj->removePerm($userid, "system"); }
					if(isset($_POST["api"])) 				{ $permsobj->addPerm($userid, "api"); } else { $permsobj->removePerm($userid, "api"); }
					Header("Location: ./?site=user_list&id=".$userid."");
					exit();
				} elseif(!$valid_username) {
					x_eventBoxPrep("The username must be between 4 and 16 characters long.", "error", _COOKIES_);
				} else {
					x_eventBoxPrep("The password must be between 8 and 32 characters long and include at least one uppercase letter, one lowercase letter, and one numeric digit.", "error", _COOKIES_);
				}
			}
		}
	}	

	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "User Creation");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>

	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3" role="alert">Create a new user and define their access permissions. Manage what each user can see and do within the system.</div>

	<!----------------- Table Element  -------------------->
	<div class="card mb-4">
	  <div class="card-body p-0">
		  <form method="post">
			<!--begin::Body-->
			<div class="card-body">
			  <!--begin::Row-->
			  <div class="row g-3">
				<!--begin::Col-->
				
				<div class="col-md-12">
				  <label for="validationCustomUsername" class="form-label">Username</label>
				  <div class="input-group">
					<input
					  type="text"
					  class="form-control"
					  id="validationCustomUsername" 
					  name="username"
					  maxlength="19"
					  placeholder="Username"
					  value="<?php echo htmlentities($_POST["username"] ?? ''); ?>"
					  required
					/>
				  </div>
				</div>
				
				<div class="col-md-12">
				  <label for="validationCustomPw1" class="form-label">Password</label>
				  <div class="input-group">
					<input
					  type="password"
					  class="form-control"
					  id="validationCustomPw1" 
					  name="password1"
					  placeholder="Password"
					  required
					/>
				  </div>
				</div>

				<div class="col-md-12">
				  <label for="validationCustomPw2" class="form-label">Password Confirmation</label>
				  <div class="input-group">
					<input
					  type="password"
					  class="form-control"
					  id="validationCustomPw2" 
					  name="password2"
					  placeholder="Password Confirmation"
					  required
					/>
				  </div>
				</div>	

				<div class="col-md-12">
				  <label for="validationCustomPw2" class="form-label">Permissions</label>

				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  value=""
					  name="domain_create"
					  id="invalidCheck7"
					  <?php if(isset($_POST["domain_create"])) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck7">
					 Access to Domain Creation (Can create Domains for himself)
					</label>
				  </div>

				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  value=""
					  name="domain_admin"
					  id="invalidCheck6"
					  <?php if(isset($_POST["domain_admin"])) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck6">
					  Access to Domain Administration (Manage all Domains of Servers/Users)
					</label>
				  </div>

				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  value=""
					  name="domain_conflicts"
					  id="invalidCheck5"
					  <?php if(isset($_POST["domain_conflicts"])) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck5">
					  Access to Domain Conflicts (Investigate Domain Conflicts)
					</label>
				  </div>
				  
				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  value=""
					  name="servers"
					  id="invalidCheck4"
					  <?php if(isset($_POST["servers"])) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck4">
					  Access to Servers Area (Create, List and Edit Servers)
					</label>
				  </div>
				  
				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  value=""
					  name="users"
					  id="invalidCheck3"
					  <?php if(isset($_POST["users"])) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck3">
					  Access to Users Area (Create, List and Edit Users)
					</label>
				  </div>
				  
				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  value=""
					  name="system"
					  id="invalidCheck2"
					  <?php if(isset($_POST["system"])) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck2">
					   Access to System Area (Debugging, About, Blacklist)
					</label>
				  </div>
				  
				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  value=""
					  name="api"
					  id="invalidCheck1"
					  <?php if(isset($_POST["api"])) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck1">
					   Access to External API Interface (Can create and manage own API-Token)
					</label>
				  </div>

				</div>	
				<!--end::Col-->
			  </div>
			  <!--end::Row-->
			</div>
			<!--end::Body-->
			<!--begin::Footer-->
			<div class="card-footer">
			  <?php echo "<input name='csrf' type='hidden' value='".$csrf->get()."'>"; ?>
			  <button class="btn btn-warning" type="submit">Create User</button>
			</div>
			<!--end::Footer-->
		  </form>	  
		  
	  </div>
	</div>
		 
	<?php
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");