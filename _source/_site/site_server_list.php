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
	if($current_user = dnshttp_server_get($mysql, @$_GET["id"])) {
		
		/*************************************************************************
			Renewed CSRF Key
		*************************************************************************/
		$csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"]."_id_".@$_GET["id"], _CSRF_VALID_LIMIT_TIME_);
	
		/*************************************************************************
			Edit a Server
		*************************************************************************/
		if(isset($_POST["token"])) {
				if(!$csrf->check($_POST["csrf"])) { x_eventBoxPrep("CSRF Error - Try again!", "error", _COOKIES_); goto endofex; }
				@$_POST["exec_ref"] = $current_user["id"];
				if(is_numeric(@$_POST["exec_ref"])) {
					if(strlen(trim($_POST["token"])) < 5) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-Token', an API Token must be at least 5 signs long!", "error", _COOKIES_); goto endofex; }
					if(strlen(trim($_POST["path"])) < 2) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-URL'!", "error", _COOKIES_); goto endofex; }
					if(!@$_POST["master"] AND !@$_POST["slave"] ) { x_eventBoxPrep("You did not choose a server mode (Slave or Master / Both)!", "error", _COOKIES_); goto endofex; }
					if(@$_POST["master"]) { $master = 1; } else  { $master = 2 ;}
					if(@$_POST["master"] AND @$_POST["slave"]) { $master = 3; } 			
					$apipathfinal = trim($_POST["path"]);
					$apipathfinal = str_replace("///", "/", $apipathfinal);
					if(substr($apipathfinal, strlen($apipathfinal) -1, 1) != "/") { $apipathfinal = $apipathfinal."/"; }
					if(substr($apipathfinal, strlen($apipathfinal) -2, 1) == "/") {
						$apipathfinal = substr($apipathfinal, 0, -1);
					}
					if(substr($apipathfinal, 0, 7) != "http://" AND substr($apipathfinal, 0, 8) != "https://") { $apipathfinal = "https://".$apipathfinal; }
					if(filter_var(@$_POST["ip"], FILTER_VALIDATE_IP) === false AND filter_var(@$_POST["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) { x_eventBoxPrep("IPv4 and IPv6 invalid! You must provide at least one valid ip!", "error", _COOKIES_); goto endofex; }
					if(filter_var(@$_POST["ip"], FILTER_VALIDATE_IP) !== false) { $xxip = @$_POST["ip"]; } else { $xxip = ""; }
					if(filter_var(@$_POST["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) { $xxip6 = @$_POST["ip6"]; } else { $xxip6 = ""; }


					$bindnew = array();
					$bindnew[0]["value"] = trim($apipathfinal);
					$mysql->query("UPDATE "._TABLE_SERVER_." SET api_path = ? WHERE id = \"".$_POST["exec_ref"]."\";", $bindnew);
					
					$bindnew = array();
					$bindnew[0]["value"] = trim($_POST["token"]);
					$mysql->query("UPDATE "._TABLE_SERVER_." SET api_token = ? WHERE id = \"".$_POST["exec_ref"]."\";", $bindnew);
					
					$bindnew = array();
					$bindnew[0]["value"] = trim($xxip);
					$mysql->query("UPDATE "._TABLE_SERVER_." SET ip = ? WHERE id = \"".$_POST["exec_ref"]."\";", $bindnew);
					
					$bindnew = array();
					$bindnew[0]["value"] = trim($xxip6);
					$mysql->query("UPDATE "._TABLE_SERVER_." SET ip6 = ? WHERE id = \"".$_POST["exec_ref"]."\";", $bindnew);		
					
					$mysql->query("UPDATE "._TABLE_SERVER_." SET server_type = '".$master."' WHERE id = \"".$_POST["exec_ref"]."\";");
					x_eventBoxPrep("Server has been updated! If the API-URL hast not been updated, it may be the case that this URL is a duplicate at another Server which is registered here...", "ok", _COOKIES_);	
				}
				
				server_check_and_set($mysql, @$_POST["exec_ref"]);
				Header("Location: ./?site=server_list&id=".$_GET["id"]."");
				exit();
		}
		endofex:

		/*************************************************************************
			Recheck a Server
		*************************************************************************/
		if(@$_GET["op"] == "recheck") {
			if(!$csrf->check($_GET["csrf"])) { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); goto endofex12; }
			server_check_and_set($mysql, $current_user["id"]);
			x_eventBoxPrep("Server Status has been updated!", "ok", _COOKIES_);
			Header("Location: ./?site=server_list&id=".$_GET["id"]."");
			exit();
		} endofex12:		
	
		/*************************************************************************
			Enable / Disable Server
		*************************************************************************/
		if(@$_GET["op"] == "enable") {
			if ($csrf->check(@$_GET['csrf'])) {
				$mysql->query("UPDATE "._TABLE_SERVER_." SET enabled = 1 WHERE id = ".$_GET["id"]."");
				x_eventBoxPrep("This server has been enabled.", "ok", _COOKIES_);
				server_check_and_set($mysql, @$_GET["id"]);
				Header("Location: ./?site=server_list&id=".$_GET["id"]."");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}						
		if(@$_GET["op"] == "disable") {
			if ($csrf->check(@$_GET['csrf'])) {
				$mysql->query("UPDATE "._TABLE_SERVER_." SET enabled = 0 WHERE id = ".$_GET["id"]."");
				x_eventBoxPrep("This server has been disabled.", "ok", _COOKIES_);
				Header("Location: ./?site=server_list&id=".$_GET["id"]."");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}									
				
		/*************************************************************************
			Include Header
		*************************************************************************/
		define("_SUB_PAGE_TITLE_", "Server Details for  #".@$_GET["id"]."");
		require_once("./_default/default_header.php");
		require_once("./_default/default_navigation.php");
		echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>

		<!----------------- Javascript for API Key Operations -------------------->
		<script>
		
			function dnshttp_userui_recheck() {
				window.location.href = "./?site=server_list&op=recheck&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
			}
		
			function dnshttp_userui_enable() {
				window.location.href = "./?site=server_list&op=enable&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
			}
		
			function dnshttp_userui_disable() {
				window.location.href = "./?site=server_list&op=disable&id=<?php echo $_GET["id"]; ?>&csrf=<?php echo $csrf->get(); ?>";
			}
		
			function dnshttp_userui_pw_show() {
				document.getElementById('validationCustomPw22').type = 'text';
			}
		
			function dnshttp_userui_pw_hide() {
				document.getElementById('validationCustomPw22').type = 'password';
			}
			
		</script>		
			
		<!----------------- Modals -------------------->
		<div class="modal" id="cpage_recheck_item_modal" tabindex="-1">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title">Re-Check Server</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			  </div>
			  <div class="modal-body">
				<p>Do you want to check the server connection status and update the satus info bubbles?</p>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-dark" onClick="dnshttp_userui_recheck()">Re-Check Now</button>
			  </div>
			</div>
		  </div>
		</div>	
		
		<div class="modal" id="cpage_disable_item_modal" tabindex="-1">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title">Disable Server</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			  </div>
			  <div class="modal-body">
				<p>Do you want to disable this server?</p>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-danger" onClick="dnshttp_userui_disable()">Disable Server</button>
			  </div>
			</div>
		  </div>
		</div>	
		
		<div class="modal" id="cpage_enable_item_modal" tabindex="-1">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title">Enable Server</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			  </div>
			  <div class="modal-body">
				<p>Do you want to enable this server?</p>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-warning" onClick="dnshttp_userui_enable()">Enable Server</button>
			  </div>
			</div>
		  </div>
		</div>		

		<!----------------- Information Callout -------------------->
		<div class="callout callout-info mb-3" role="alert">Here you can find details about the server '<b><?php echo htmlspecialchars($current_user["api_path"] ?? ''); ?></b>' with identification number #<?php echo @$_GET["id"]; ?>.</div>
		<?php if($current_user["enabled"] == 0) { ?> <div class="callout callout-danger mb-3" role="alert">This server is currently disabled. </div> <?php } ?>
		<?php if($current_user["apiok"] == 0) { ?> <div class="callout callout-danger mb-3" role="alert">This server is currently facing replication issues. </div> <?php } ?>

		<!----------------- Status Boxes -------------------->
		<div class="row">
		  <div class="col-12 col-sm-6 col-md-3">
			<div class="info-box">
			  <span class="info-box-icon text-bg-dark shadow-sm">
				<i class="bi bi-gear-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text">Type</span>
				<span class="info-box-number">
				  <?php if($current_user["server_type"] == "1") { echo '<span class="badge text-bg-dark">Master</span>'; } elseif($current_user["server_type"] == "2") { echo '<span class="badge text-bg-dark">Slave</span>'; } else { echo '<span class="badge text-bg-dark">Hybrid</span>'; }   ?>
				</span>
			  </div>
			</div>
		  </div>
		  <div class="col-12 col-sm-6 col-md-3">
			<div class="info-box">
			  <span class="info-box-icon text-bg-dark shadow-sm">
				<i class="bi bi-gear-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text">IPv4</span>
				<span class="info-box-number">
				  <?php echo htmlspecialchars($current_user["ip"] ?? ''); if(trim($current_user["ip"] ?? '') == "")  echo "None";{ }  ?>
				</span>
			  </div>
			</div>
		  </div>
		  <div class="col-12 col-sm-6 col-md-3">
			<div class="info-box">
			  <span class="info-box-icon text-bg-dark shadow-sm">
				<i class="bi bi-gear-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text">IPv6</span>
				<span class="info-box-number">
				  <?php echo htmlspecialchars($current_user["ip6"] ?? ''); if(trim($current_user["ip6"] ?? '') == "")  echo "None";{ }  ?>
				</span>
			  </div>
			</div>
		  </div>

		  <div class="col-12 col-sm-6 col-md-3">
			<div class="info-box">
			  <span class="info-box-icon text-bg-dark shadow-sm">
				<i class="bi bi-gear-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text">Domains</span>
				<span class="info-box-number">
				  <?php echo htmlspecialchars($current_user["domains"] ?? '');  ?>
				</span>
			  </div>
			</div>
		  </div>
		</div>				  
				  
		<!----------------- User Listing and Details Section -------------------->
		<div class="row">
			<div class="col-md-5">	
			
				<!----------------- Back to List  -------------------->
				<a href="./?site=server_list" style="text-decoration: none !important; font-weight: bold;">
					<div class="info-box bg-light">
					  <span class="info-box-icon text-bg-dark shadow-sm">
						<i class="bi bi-arrow-return-left"></i>
					  </span>
					  <div class="info-box-content">
						<span class="info-box-text" style="text-decoration: none !important;">Return to Listing</span>
					  </div>
					</div>				
				</a>

				<!----------------- Operations  -------------------->
				<div class="col-md-12">
					<div class="card card-dark mb-4">
					  <div class="card-header">
						<div class="card-title">Server Operations</div>
					  </div>
						<div class="card-footer">
						
							  <?php if($current_user["enabled"] > 0) { ?>
								  <button type="button" class="btn btn-warning btnmarg" data-bs-toggle="modal" data-bs-target="#cpage_enable_item_modal" disabled>Enable Server</button>
								  <button type="button" class="btn btn-danger btnmarg" data-bs-toggle="modal" data-bs-target="#cpage_disable_item_modal">Disable Server</button>
							  <?php } else { ?>
								  <button type="button" class="btn btn-warning btnmarg" data-bs-toggle="modal" data-bs-target="#cpage_enable_item_modal">Enable Server</button>
								  <button type="button" class="btn btn-danger btnmarg" data-bs-toggle="modal" data-bs-target="#cpage_disable_item_modal" disabled>Disable Server</button>
							  <?php } ?>
							  
							<button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#cpage_recheck_item_modal">Status Re-Check</button>
						  
						</div>
					</div>
				</div>	
				
				<!----------------- Show Status Boxes -------------------->
				<div class="card card-dark mb-4" id="card_for_permissions">
				  <!-- /.card-header -->
				  <div class="card-header">
					<h3 class="card-title"> Connection Status</h3>
				  </div>
				  <div class="card-body p-0">		
				<div class="row mx-1 mt-2">
				  
					  <?php $permission_name = "Enabling Status"; if($current_user["enabled"] == "1") { ?>
						  <div class="col-12">
							<div class="info-box bg-light">
							  <span class="info-box-icon text-bg-success shadow-sm">
								<i class="bi bi-hand-thumbs-up-fill"></i>
							  </span>

							  <div class="info-box-content">
								<span class="info-box-text"><?php echo $permission_name; ?></span>
								<span class="info-box-number">Enabled</span>
							  </div>
							</div>
						  </div>
					  <?php } else {  ?>
						  <div class="col-12">
							<div class="info-box bg-light">
							  <span class="info-box-icon text-bg-danger shadow-sm">
								<i class="bi bi-hand-thumbs-down-fill"></i>
							  </span>
							  <div class="info-box-content">
								<span class="info-box-text"><?php echo $permission_name; ?></span>
								<span class="info-box-number">Disabled</span>
							  </div>
							</div>
						  </div>
					  <?php } ?>
					  
					  <?php if($current_user["enabled"] == "1") { ?>
						  <?php $permission_name = "Blacklist Status"; if($current_user["weblacklisted"] == "1") { ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-danger shadow-sm">
									<i class="bi bi-hand-thumbs-up-fill"></i>
								  </span>

								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Blacklisted</span>
								  </div>
								</div>
							  </div>
						  <?php } elseif($current_user["weblacklisted"] == "2") {  ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-warning shadow-sm">
									<i class="bi bi-hand-thumbs-down-fill"></i>
								  </span>
								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Waiting for Cronjob</span>
								  </div>
								</div>
							  </div>
						  <?php } else {  ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-success shadow-sm">
									<i class="bi bi-hand-thumbs-down-fill"></i>
								  </span>
								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Clear</span>
								  </div>
								</div>
							  </div>
						  <?php } ?>
					  <?php } ?>
					  
					  <?php if($current_user["enabled"] == "1") { ?>
						  <?php $permission_name = "Connection Status"; if($current_user["apiok"] == "1") { ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-primary shadow-sm">
									<i class="bi bi-hand-thumbs-up-fill"></i>
								  </span>

								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Legacy</span>
								  </div>
								</div>
							  </div>
						  <?php } elseif($current_user["apiok"] == "2") {  ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-success shadow-sm">
									<i class="bi bi-hand-thumbs-down-fill"></i>
								  </span>
								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Compressed</span>
								  </div>
								</div>
							  </div>
						  <?php } else {  ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-danger shadow-sm">
									<i class="bi bi-hand-thumbs-down-fill"></i>
								  </span>
								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Failed</span>
								  </div>
								</div>
							  </div>
						  <?php } ?>
					  <?php } ?>
					  
					  <?php if($current_user["enabled"] == "1") { ?>
						  <?php $permission_name = "Token Status"; if($current_user["tokenbadlastreq"] == "1") { ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-danger shadow-sm">
									<i class="bi bi-hand-thumbs-up-fill"></i>
								  </span>

								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Wrong Token Provided</span>
								  </div>
								</div>
							  </div>
						  <?php } elseif($current_user["tokenbadlastreq"] == "2") {  ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-warning shadow-sm">
									<i class="bi bi-hand-thumbs-down-fill"></i>
								  </span>
								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Waiting for Cronjob</span>
								  </div>
								</div>
							  </div>
						  <?php } else {  ?>
							  <div class="col-12">
								<div class="info-box bg-light">
								  <span class="info-box-icon text-bg-success shadow-sm">
									<i class="bi bi-hand-thumbs-down-fill"></i>
								  </span>
								  <div class="info-box-content">
									<span class="info-box-text"><?php echo $permission_name; ?></span>
									<span class="info-box-number">Clear</span>
								  </div>
								</div>
							  </div>
						  <?php } ?>					  
					  <?php } ?>					  
				  
						</div>					
					</div>					
				</div>			
		
				<div class="col-md-12">
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
								  <label for="validationCustomUsername" class="form-label">API-Path: Enter the URL of the server you want to create!</label>
								  <div class="input-group">
									<input
									  type="text"
									  class="form-control"
									  id="validationCustomUsername" 
									  name="path"
									  maxlength="512"
									  placeholder="https://another-instance/"
									  value="<?php echo htmlentities(@$current_user["api_path"] ?? ''); ?>"
									  required
									/>
								  </div>
								</div>
								
								<div class="col-md-12">
								  <label for="validationCustomPw1" class="form-label">IPV4 SETUP: You need to enter at least one related ip (v6 or v4) to this server!</label>
								  <div class="input-group">
									<input
									  type="text"
									  class="form-control"
									  id="validationCustomPw1" 
									  name="ip"
									  placeholder="0.0.0.0"
									  value="<?php echo htmlentities(@$current_user["ip"] ?? ''); ?>"
									/>
								  </div>
								</div>

								<div class="col-md-12">
								  <label for="validationCustomPw2" class="form-label">IPV6 SETUP: You need to enter at least one related ip (v6 or v4) to this server!</label>
								  <div class="input-group">
									<input
									  type="text"
									  class="form-control"
									  id="validationCustomPw2" 
									  name="ip6"
									  placeholder="::"
									  value="<?php echo htmlentities(@$current_user["ip6"] ?? ''); ?>"
									/>
								  </div>
								</div>
								
								<div class="col-md-12">
								  <label for="validationCustomPw22" class="form-label">Security-DNS-Token: You need to set up the same security token on the other side for this local server!</label>
								  <div class="input-group">
									<input
									  type="password"
									  class="form-control"
									  id="validationCustomPw22" 
									  name="token"
									  placeholder="Security DNS Token"
									  value="<?php echo htmlentities(@$current_user["api_token"]); ?>"
									  
									/>
								  </div>
								</div>	

								<div class="col-md-12">
								
								The new server can be both, slave and/or master server! If you activate Master-Server than this Server will try to replicate Domains out of this new added or edited server!<br />	

								  <div class="form-check" style="margin-right: 15px;">
									<input
									  class="form-check-input"
									  type="checkbox"
									  name="slave"
									  id="invalidCheck7"
									  <?php if(@dnshttp_server_get($mysql, $_GET["id"])["server_type"] == 2 || @dnshttp_server_get($mysql, $_GET["id"])["server_type"] == 3) { echo "checked"; } ?>
									/>
									<label class="form-check-label" for="invalidCheck7">
									 Slave DNS-Server [Transfer from the local server to a remote server]
									</label>
								  </div>

								  <div class="form-check" style="margin-right: 15px;">
									<input
									  class="form-check-input"
									  type="checkbox"
									  name="master"
									  id="invalidCheck6"
									  <?php if(@dnshttp_server_get($mysql, $_GET["id"])["server_type"] == 1  || @dnshttp_server_get($mysql, $_GET["id"])["server_type"] == 3) { echo "checked"; } ?>
									/>
									<label class="form-check-label" for="invalidCheck6">
									  Master DNS-Server [Transfer from the created server to the local server]
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
							  <button class="btn btn-warning btnmarg" type="submit">Change Server</button>
							  <button type="button" class="btn btn-dark btnmarg" onClick="dnshttp_userui_pw_show()">Show Token</button>
							  <button type="button" class="btn btn-dark btnmarg" onClick="dnshttp_userui_pw_hide()">Hide Token</button>
							</div>
							<!--end::Footer-->
						  </form>	  
						  
					  </div>
					</div>
				</div>			
		
			</div>		
			<div class="col-md-7">
				<div class="card card-dark mb-4 ">
				  <!-- /.card-header -->
				  <div class="card-header">
					<h3 class="card-title">Domains</h3>
				  </div>
				  <div class="card-body p-0 tablecard">
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
								$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE fk_server = ".@$_GET["id"]." ORDER BY id DESC LIMIT 50", true); 
								if(is_array($res)) { 
									foreach ($res AS $key => $value) { 	
										?>
											<tr class="align-middle">
											  <td><?php echo htmlspecialchars($value["domain"] ?? ''); ?> 								  
											  </td>
											  <td><?php
												if($value["registered"] == "1") { echo '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
												if($value["registered"] != "1") { echo '<span class="badge text-bg-danger" title="Not active for queries">NR</span> '; }
												if($value["conflict"] == "1") { echo '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
												if($value["preferred"] == "1") { echo '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
												if($value["oldzonefallback"] == "1") { echo '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
												if($value["okonce"] == "0") { echo '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
												if($value["zonecheck"] == "0") { echo '<span class="badge text-bg-danger" title="Zone invalidated...">ZE</span> '; }
											  ?></td>
											  <td>

											  <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
												<?php echo "<a class='btn btn-dark btn-sm' title='Duplicates and Conflicts' href='./?site=domain_list&domain=".base64_encode(trim(strtolower($value["domain"] ?? '') ?? ''))."'><i class=\"bi bi-copy\"></i></a>"; ?> 
													<?php echo "<a class='btn btn-dark btn-sm' title='Details' href='./?site=domain_list&api_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
											  <?php } else { ?>
												<?php echo "<a class='btn btn-dark btn-sm' title='Details' href='./?site=domain_list&api_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
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
						
		<?php
		/*************************************************************************
			Include Footer
		*************************************************************************/
		require_once("./_template/tpl_search.php");
		require_once("./_default/default_footer.php");			
		exit();
	}
	
	/*************************************************************************
		Server Deletion Operation
	*************************************************************************/
	if(is_numeric(@$_GET["deleteid"])) {
		if($csrf->check($_GET['csrf'])) {
			$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE fk_server = \"".$_GET["deleteid"]."\";");
			$mysql->query("DELETE FROM "._TABLE_SERVER_." WHERE id = \"".$_GET["deleteid"]."\";");
			x_eventBoxPrep("The server and all server related domain and zone information has been removed.", "ok", _COOKIES_);
			Header("Location: ./?site=".@$_GET["site"].""); exit();
		} else { 
			x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); 
			Header("Location: ./?site=".@$_GET["site"].""); exit();
		}
	}
	
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Servers");
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
			window.location.href = "./?site=server_list&csrf=<?php echo $csrf->get(); ?>&deleteid="+current_item_id+"";
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
			<p>Do you really want to delete the server '<b><span id="cpage_delete_item_modal_ip"></span></b>' connection and all his domain and record data? This action cannot be undone?</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-danger" onClick="dnshttp_cpage_delete_item_confirm_exec()">Yes</button>
		  </div>
		</div>
	  </div>
	</div>

	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3" role="alert">This page displays a complete list of all servers for replication, along with their status. From here, you can view server details or delete servers as needed.</div>

	<!----------------- Table Element  -------------------->
	<div class="card mb-4 tablecard">
	  <!-- /.card-header -->
	  <div class="card-body p-0">
		<table class="table table-striped">
		  <thead>
			<tr>
			  <th style="width: 60px">#</th>
			  <th>Location</th>
			  <th style="width: 80px">Type</th>
			  <th style="width: 80px">Status</th>
			  <th style="width: 180px">Operation</th>
			</tr>
		  </thead>
		  <tbody>
			<?php		  
				$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." ORDER BY id DESC", true); 
				if(is_array($res)) { 
					foreach ($res AS $key => $value) { 	
						?>
							<tr class="align-middle">
							  <td><?php echo htmlspecialchars($value["id"] ?? ''); ?></td>
							  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["api_path"] ?? ''); ?><br /><small>
							      <?php echo htmlspecialchars($value["ip"] ?? ''); ?><br /><?php echo htmlspecialchars($value["ip6"] ?? ''); ?></small></td>
							  <td><small><?php if($value["server_type"] == "1") { echo '<span class="badge text-bg-dark">Master</span>'; } elseif($value["server_type"] == "2") { echo '<span class="badge text-bg-dark">Slave</span>'; } else { echo '<span class="badge text-bg-dark">Hybrid</span>'; } ?></small></td>
							  <td><?php if($value["enabled"] == "1") { if($value["apiok"] == "1") { echo '<span class="badge text-bg-success" title="This Server is reachable and running the DNSHTTP version < 4.0.0">Legacy</span>'; } elseif($value["apiok"] == "2") { echo '<span class="badge text-bg-success" title="This Server is reachable and running the DNSHTTP version >= 4.0.0">Compressed</span>'; } else { echo '<span class="badge text-bg-danger" title="This server has critical connection errors.">Error</span>'; } } else { echo '<span class="badge text-bg-danger" title="This server has been disabled by an administrator.">Disabled</span>'; } ?></td>
							  <td>
								<a class="btn btn-dark btn-sm" href="./?site=server_list&id=<?php echo $value["id"]; ?>">
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