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
		Create a new Server
	*************************************************************************/	
	if(isset($_POST["path"])) {
		if(!$csrf->check($_POST["csrf"])) { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); goto endofex; }
		
		if(strlen(trim($_POST["token"] ?? '')) < 5) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-Token', an API Token must be at least 5 signs long!", "error", _COOKIES_); goto endofex; }
		if(strlen(trim($_POST["path"] ?? '')) < 2) { x_eventBoxPrep("Server could not be created, you provided an invalid 'API-URL'!", "error", _COOKIES_); goto endofex; }
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
		$bindnew[0]["value"] = strtolower(trim($apipathfinal));
		
		$checkexistant = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE TRIM(LOWER(api_path)) = ?", false, $bindnew);
		
		if(!$checkexistant) { 
			$bindnew = array();
			$bindnew[0]["value"] = trim($apipathfinal);
			$bindnew[1]["value"] = trim($_POST["token"]);
			$bindnew[2]["value"] = trim($xxip);
			$bindnew[3]["value"] = trim($xxip6);
			$mysql->query("INSERT INTO "._TABLE_SERVER_." (api_path, api_token, server_type, fk_user, ip, ip6, enabled, apiok, emptydomains, tokenbadlastreq, weblacklisted, domains) 
														VALUES ( ?
														, ?
														, '".$master."'
														, '".$user->user_id."'
														, ?
														, ?
														, '0'
														, 2
														, 2
														, 2
														, 2
														, 0
													);", $bindnew);
													
													
			x_eventBoxPrep("Server has been created!", "ok", _COOKIES_);
			$checkserverid = $mysql->insert_id; 
			dnshttp_server_check_and_set($mysql, $checkserverid);
			
			Header("Location: ./?site=server_list&id=".$checkserverid."");
			exit();
		} else {
			x_eventBoxPrep("Another server with the same api-path already exists!", "error", _COOKIES_); goto endofex;
		}

	}
	endofex:
		
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Server Creation");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>


	<!----------------- Script -------------------->
	<script>
	
		function dnshttp_userui_pw_show() {
			document.getElementById('validationCustomPw22').type = 'text';
		}
	
		function dnshttp_userui_pw_hide() {
			document.getElementById('validationCustomPw22').type = 'password';
		}
	
	</script>		

	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3" role="alert">Create a new server for DNS-Replication operations and define their role.</div>

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
					  value="<?php echo htmlentities(@$_POST["path"] ?? ''); ?>"
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
					  value="<?php echo htmlentities(@$_POST["ip"] ?? ''); ?>"
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
					  value="<?php echo htmlentities(@$_POST["ip6"] ?? ''); ?>"
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
					  value="<?php echo htmlentities(@$_POST["token"] ?? dnshttp_api_token_generate()); ?>"
					  
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
					  <?php if(@$_POST["slave"]) { echo "checked"; } ?>
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
					  <?php if(@$_POST["master"]) { echo "checked"; } ?>
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
			  <button class="btn btn-warning" type="submit">Create Server</button>
			  <button type="button" class="btn btn-dark" onClick="dnshttp_userui_pw_show()">Show Token</button>
			  <button type="button" class="btn btn-dark" onClick="dnshttp_userui_pw_hide()">Hide Token</button>
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
	
