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
		Default Listing Area
	*************************************************************************/
	if(!is_numeric(@$_GET["api_id"]) AND !is_numeric(@$_GET["bind_id"]) AND !is_string(@$_GET["domain"]) ) {
		define("_SUB_PAGE_TITLE_", "Domains");
	   if(@$_GET["section"] == "my") {
	   } elseif(@$_GET["section"] == "master") {
		   if(!$permsobj->hasPerm($user->user_id, "domain_admin") AND $user->user_rank != 0) { Header("Location: ../"); exit(); }
	   } elseif(@$_GET["section"] == "slave") {
		   if(!$permsobj->hasPerm($user->user_id, "domain_admin") AND $user->user_rank != 0) { Header("Location: ../"); exit(); }
	   } elseif(@$_GET["section"] == "users") {
		   if(!$permsobj->hasPerm($user->user_id, "domain_admin") AND $user->user_rank != 0) { Header("Location: ../"); exit(); }
	   } else {
			@$_GET["section"] = "my";
	   }		
	}
	
	/*************************************************************************
		Specific Item Listing Area
	*************************************************************************/
	elseif(is_numeric(@$_GET["api_id"]) OR is_numeric(@$_GET["bind_id"])) { 
	
		
		/*************************************************************************
			Check for Permission and Load Generic Data
		*************************************************************************/
		if(!is_numeric(@$_GET["api_id"])) { @$_GET["api_id"] = ""; }
		if(!is_numeric(@$_GET["bind_id"])) { @$_GET["bind_id"] = ""; }
		if(is_numeric(@$_GET["api_id"])) {
			if(!$permsobj->hasPerm($user->user_id, "domain_admin") AND $user->user_rank != 0) { Header("Location: ../"); exit(); }
			$c_domain_id_r = "api_id";
			$c_tpe_raw = "Slave";
			$c_domain_id = $_GET["api_id"];
			$current_user = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE id = ".@$_GET["api_id"]."". false);
			if(!$current_user) { Header("Location: ../"); exit(); }
			define("_SUB_PAGE_TITLE_", htmlspecialchars($current_user["domain"] ?? '')." / Slave Domain");
			$_GET["bind_id"] = "";			
		} else {
			$c_domain_id_r = "bind_id";
			$c_tpe_raw = "Master";
			$c_domain_id = $_GET["bind_id"];
			$current_user = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = ".@$_GET["bind_id"]."". false);
			if(!$current_user) { Header("Location: ../"); exit(); }
			if($current_user["fk_user"] == $user->user_id) {
			} else {
				if(!$permsobj->hasPerm($user->user_id, "domain_admin") AND $user->user_rank != 0) { Header("Location: ../"); exit(); }
			}
			if($current_user["fk_user"] > 0) { define("_SUB_PAGE_TITLE_", htmlspecialchars($current_user["domain"] ?? '')." / User Domain"); }
			else { define("_SUB_PAGE_TITLE_", htmlspecialchars($current_user["domain"] ?? '')." / Master Domain"); }
			$_GET["api_id"] = "";			
		}
	
		/*************************************************************************
			Renewed CSRF Key
		*************************************************************************/
		$csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"]."_id_".@$_GET["bind_id"]."_".@$_GET["api_id"], _CSRF_VALID_LIMIT_TIME_);
	
		/*************************************************************************
			Enable / Disable Replication on Domain
		*************************************************************************/
		if(@$_GET["op"] == "rep") {
			if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) {
				if ($csrf->check(@$_GET['csrf'])) {
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET set_no_replicate = 0 WHERE id = ".$_GET["bind_id"]."");
					x_eventBoxPrep("This domain will now replicate to other servers.", "ok", _COOKIES_);
					Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."");
					exit();
				} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("You need the domain administrator permission to stop and start domain replications.", "error", _COOKIES_); }
		}						
		if(@$_GET["op"] == "norep") {
			if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) {
				if ($csrf->check(@$_GET['csrf'])) {
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET set_no_replicate = 1 WHERE id = ".$_GET["bind_id"]."");
					x_eventBoxPrep("This domain will stop replicating to other servers.", "ok", _COOKIES_);
					Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."");
					exit();
				} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("You need the domain administrator permission to stop and start domain replications.", "error", _COOKIES_); }
		}		
	
		/*************************************************************************
			Prefer / Unprefer  on Domain
		*************************************************************************/
		if(@$_GET["op"] == "prefer") {
			if($permsobj->hasPerm($user->user_id, "domain_conflicts") OR $user->user_rank == 0) {
				if ($csrf->check(@$_GET['csrf'])) {
					
					if(is_numeric($_GET["api_id"])) { $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 1, conflict = 0 WHERE id = ".$_GET["api_id"].""); }
					if(is_numeric($_GET["bind_id"])) { $mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 1, conflict = 0 WHERE id = ".$_GET["bind_id"].""); }
					
					x_eventBoxPrep("This domain will now replicate to other servers.", "ok", _COOKIES_);
					Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."&api_id=".$_GET["api_id"]."");
					exit();
				} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("You need the domain conflicts permission to stop and start domain replications.", "error", _COOKIES_); }
		}						
		if(@$_GET["op"] == "unprefer") {
			if($permsobj->hasPerm($user->user_id, "domain_conflicts") OR $user->user_rank == 0) {
				if ($csrf->check(@$_GET['csrf'])) {
					
					if(is_numeric($_GET["api_id"])) { $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0 WHERE id = ".$_GET["api_id"].""); }
					if(is_numeric($_GET["bind_id"])) { $mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 0 WHERE id = ".$_GET["bind_id"].""); }
					
					x_eventBoxPrep("This domain will stop replicating to other servers.", "ok", _COOKIES_);
					Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."&api_id=".$_GET["api_id"]."");
					exit();
				} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
			} else  { x_eventBoxPrep("You need the domain conflicts permission to stop and start domain replications.", "error", _COOKIES_); }
		}		

		/*************************************************************************
			Delete a Domain
		*************************************************************************/
		if(@$_GET["op"] == "del") {
			if ($csrf->check(@$_GET['csrf'])) {
				
				if(is_numeric($_GET["bind_id"])) { $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = ".$_GET["bind_id"].""); }
				
				x_eventBoxPrep("The domain and its data has been deleted.", "ok", _COOKIES_);
				Header("Location: ./?site=domain_list");
				exit();
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); }
		}		

		/*************************************************************************
			Change Domain Records
		*************************************************************************/	
		if(@$_GET["op"] == "saverecords") {
			if ($csrf->check(@$_GET['csrf'])) {
				if (isset($_POST["zone"])) {
					//echo nl2br($_POST["zone"]); exit();
					$bindcc = array();
					$bindcc[0]["value"] = substr($_POST["zone"], strpos($_POST["zone"], "\n") + 1);;
					if(is_numeric($_GET["bind_id"])) { 
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET content = ? WHERE id = ".$_GET["bind_id"]."", $bindcc); 
					}
					x_eventBoxPrep("The domain has successfully been changed.", "ok", _COOKIES_);
					Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."&api_id=".$_GET["api_id"]."");
					exit();
				} else  { x_eventBoxPrep("Zone Data Error!", "error", _COOKIES_); Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."&api_id=".$_GET["api_id"].""); exit(); }
			} else  { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."&api_id=".$_GET["api_id"].""); exit(); }
			x_eventBoxPrep("Unknown Error", "error", _COOKIES_); Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."&api_id=".$_GET["api_id"].""); exit();
		}		
		
	}
	
	/*************************************************************************
		Specific Domain Listing Area
	*************************************************************************/
	else { 
	
		/*************************************************************************
			Check for Permission
		*************************************************************************/
		define("_SUB_PAGE_TITLE_", "Domain Duplicates");
		if(!$permsobj->hasPerm($user->user_id, "domain_admin") AND $user->user_rank != 0) { Header("Location: ../"); exit(); }

	}

	/*************************************************************************
		Include Header
	*************************************************************************/
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; 
	
	/*************************************************************************
		Default Listing Area
	*************************************************************************/
	if(!is_numeric(@$_GET["api_id"]) AND !is_numeric(@$_GET["bind_id"]) AND !is_string(@$_GET["domain"]) ) {	
		?>

		<?php
			if(@$_GET["section"] == "my") {
				?> <div class="callout callout-info mb-3" role="alert">Here you can see the overview of your created domains.</div> <?php
			}
			if(@$_GET["section"] == "master") {
				?> <div class="callout callout-info mb-3" role="alert">This domains have been locally fetched from bind configuration files or configurated fetching method for domains (According to your use-case).</div> <?php
			}
			if(@$_GET["section"] == "slave") {
				?> <div class="callout callout-info mb-3" role="alert">This domains have been replicated from other master servers to this local server.</div> <?php
			}
			if(@$_GET["section"] == "users") {
				?> <div class="callout callout-info mb-3" role="alert">This domains have been created by users on this platform.</div> <?php
			}
		?>
		
		<div class="col-12 col-sm-12">
            <div class="card card-dark card-tabs">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "my") { echo 'active'; } ?>" id="custom-tabs-one-home-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true" href="./?site=domain_list&section=my" style="color: <?php if(@$_GET["section"] == "my") { echo 'black'; } else { echo 'white'; } ?>;">My Domains</a>
                  </li>
                   <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?><li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "master") { echo 'active'; }?>" id="custom-tabs-one-profile-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-profile" aria-selected="false" href="./?site=domain_list&section=master" style="color: <?php if(@$_GET["section"] == "master") { echo 'black'; } else { echo 'white'; }  ?>;">Master</a>
				  </li><?php } ?>
                   <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?><li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "slave") { echo 'active'; } ?>" id="custom-tabs-one-messages-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-messages" aria-selected="false" href="./?site=domain_list&section=slave" style="color: <?php if(@$_GET["section"] == "slave") { echo 'black'; } else { echo 'white'; }  ?>;">Slave</a>
                  </li><?php } ?>
                   <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?><li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "users") { echo 'active'; } ?>" id="custom-tabs-one-settings-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-settings" aria-selected="false" href="./?site=domain_list&section=users" style="color: <?php if(@$_GET["section"] == "users") { echo 'black'; } else { echo 'white'; }  ?>;">Users</a>
                  </li><?php } ?>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                  <div class="tab-pane fade active show" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">		
		
					<!----------------- Table Element  -------------------->
					  <div class="card-body p-0 tablecard">
						<div class="scrolltableinit">
						<table class="table table-striped">
						
							<?php
								if(@$_GET["section"] == "my") {
									?>
									  <thead>
										<tr>
										  <th>Domain</th>
										  <th style="width: 180px">Status</th>
										  <th style="width: 180px">Inspect</th>
										</tr>
									  </thead>
									<?php
								} 
								if(@$_GET["section"] == "master") {
									?>
									  <thead>
										<tr>
										  <th>Domain</th>
										  <th style="width: 180px">Status</th>
										  <th style="width: 180px">Inspect</th>
										</tr>
									  </thead>
									<?php
								} 
								if(@$_GET["section"] == "slave") {
									?>
									  <thead>
										<tr>
										  <th>Domain</th>
										  <th style="width: 180px">Server</th>
										  <th style="width: 180px">Status</th>
										  <th style="width: 180px">Inspect</th>
										</tr>
									  </thead>
									<?php
								} 
								if(@$_GET["section"] == "users") {
									?>
									  <thead>
										<tr>
										  <th>Domain</th>
										  <th style="width: 180px">User</th>
										  <th style="width: 180px">Status</th>
										  <th style="width: 180px">Inspect</th>
										</tr>
									  </thead>
									<?php
								} 
								
							?>
						  <tbody>	
							<?php
							   if(@$_GET["section"] == "my") {
									$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user = ".$user->user_id." ORDER BY domain ASC", true); 
									if(is_array($res)) { 
										foreach ($res AS $key => $value) { 	
											?>
												<tr class="align-middle">
												  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["domain"] ?? ''); ?> 											  
												  </td>
												  <td><?php
													if($value["registered"] == "1") { echo '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
													if($value["registered"] != "1") { echo '<span class="badge text-bg-danger" title="Not active for queries">NR</span> '; }
													if($value["conflict"] == "1") { echo '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
													if($value["preferred"] == "1") { echo '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
													if($value["set_no_replicate"] == "1") { echo '<span class="badge text-bg-danger" title="Replication to other servers Disabled">RD</span> '; }
													if($value["oldzonefallback"] == "1") { echo '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
													if($value["okonce"] == "0") { echo '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
													if($value["zonecheck"] == "0") { echo '<span class="badge text-bg-danger" title="Zone invalidated...">ZE</span> '; }
												  ?></td><td>
													  <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect all similar Domains' href='./?site=domain_list&domain=".base64_encode(trim(strtolower($value["domain"] ?? '') ?? ''))."'><i class=\"bi bi-copy\"></i></a>"; ?> 
															<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
													  <?php } else { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
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
							   }
							   if(@$_GET["section"] == "master") {
									$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user <= 0 OR fk_user IS NULL OR fk_user = '' ORDER BY domain ASC", true); 
									if(is_array($res)) { 
										foreach ($res AS $key => $value) { 	
											?>
												<tr class="align-middle">
												  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["domain"] ?? ''); ?> 											  
												  </td>
												  <td><?php
													if($value["registered"] == "1") { echo '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
													if($value["registered"] != "1") { echo '<span class="badge text-bg-danger" title="Not active for queries">NR</span> '; }
													if($value["conflict"] == "1") { echo '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
													if($value["preferred"] == "1") { echo '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
													if($value["set_no_replicate"] == "1") { echo '<span class="badge text-bg-danger" title="Replication to other servers Disabled">RD</span> '; }
													if($value["oldzonefallback"] == "1") { echo '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
													if($value["okonce"] == "0") { echo '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
													if($value["zonecheck"] == "0") { echo '<span class="badge text-bg-danger" title="Zone invalidated...">ZE</span> '; }
												  ?></td><td>
													  <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect all similar Domains' href='./?site=domain_list&domain=".base64_encode(trim(strtolower($value["domain"] ?? '') ?? ''))."'><i class=\"bi bi-copy\"></i></a>"; ?> 
															<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
													  <?php } else { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
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
							   }			
							   if(@$_GET["section"] == "slave") {
									$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." ORDER BY domain ASC", true); 
									if(is_array($res)) { 
										foreach ($res AS $key => $value) { 	
											?>
												<tr class="align-middle">
												  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["domain"] ?? ''); ?>											  
												  </td>
												  <td class="pre_before_ip_getter">#<?php echo htmlspecialchars($value["fk_server"] ?? ''); ?></td>
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
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect all similar Domains' href='./?site=domain_list&domain=".base64_encode(trim(strtolower($value["domain"] ?? '') ?? ''))."'><i class=\"bi bi-copy\"></i></a>"; ?> 
															<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&api_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
													  <?php } else { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&api_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
													  <?php }  ?>
													  <?php if($permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this domains server' href='./?site=server_list&id=".trim(strtolower($value["fk_server"] ?? '') ?? '')."'><i class=\"bi bi-server\"></i></a>"; ?> 
													  <?php } ?>	
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
							   }			
							   if(@$_GET["section"] == "users") {
									$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user > 0 ORDER BY domain ASC", true); 
									if(is_array($res)) { 
										foreach ($res AS $key => $value) { 	
											?>
												<tr class="align-middle">
												  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["domain"] ?? ''); ?> 											  
												  </td>
												  <td class="pre_before_ip_getter">#<?php echo htmlspecialchars($value["fk_user"] ?? ''); ?></td>
												  <td><?php
													if($value["registered"] == "1") { echo '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
													if($value["registered"] != "1") { echo '<span class="badge text-bg-danger" title="Not active for queries">NR</span> '; }
													if($value["conflict"] == "1") { echo '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
													if($value["preferred"] == "1") { echo '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
													if($value["set_no_replicate"] == "1") { echo '<span class="badge text-bg-danger" title="Replication to other servers Disabled">RD</span> '; }
													if($value["oldzonefallback"] == "1") { echo '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
													if($value["okonce"] == "0") { echo '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
													if($value["zonecheck"] == "0") { echo '<span class="badge text-bg-danger" title="Zone invalidated...">ZE</span> '; }
												  ?></td><td>
													  <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect all similar Domains' href='./?site=domain_list&domain=".base64_encode(trim(strtolower($value["domain"] ?? '') ?? ''))."'><i class=\"bi bi-copy\"></i></a>"; ?> 
															<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
													  <?php } else { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; ?>
													  <?php }  ?>
													  <?php if($permsobj->hasPerm($user->user_id, "users") OR $user->user_rank == 0) { ?>
														<?php echo "<a class='btn btn-dark btn-sm' title='Inspect this domains user' href='./?site=user_list&id=".trim(strtolower($value["fk_user"] ?? '') ?? '')."'><i class=\"bi bi-person\"></i></a>"; ?> 
													  <?php } ?>	
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
							   }		
							?>	   
						  </tbody>
						</table>
					  </div>
					</div>		
                  </div>
                </div>
              </div>
            </div>
          </div>		
		<?php
	}
	
	/*************************************************************************
		Specific Item Listing Area
	*************************************************************************/
	elseif(is_numeric(@$_GET["api_id"]) OR is_numeric(@$_GET["bind_id"])) { 
		?>
		
			<!----------------- Javascript for API Key Operations -------------------->
			<script>
				function dnshttp_userui_changerecord_save() {
					const form = document.createElement('form');
					form.method = 'POST';
					form.action = './?site=domain_list&op=saverecords&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>';
					const zoneText = window.DNSZoneEditor.getZoneData();
					const field = document.createElement('textarea');
					field.name = 'zone';
					field.style.display = 'none';
					field.value = zoneText;
					form.appendChild(field);
					document.body.appendChild(form);
					form.submit();
					//window.location.href = "./?site=domain_list&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
				function dnshttp_userui_changerecord_close() {
					window.location.href = "./?site=domain_list&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
				function dnshttp_userui_changerecord() {
					window.location.href = "./?site=domain_list&op=changerecord&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
				
				function dnshttp_userui_viewrecord_close() {
					window.location.href = "./?site=domain_list&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
				function dnshttp_userui_viewrecord() {
					window.location.href = "./?site=domain_list&op=viewrecord&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
				function dnshttp_userui_prefer() {
					window.location.href = "./?site=domain_list&op=prefer&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
				function dnshttp_userui_unprefer() {
					window.location.href = "./?site=domain_list&op=unprefer&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
				function dnshttp_userui_norep() {
					window.location.href = "./?site=domain_list&op=norep&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
				function dnshttp_userui_rep() {
					window.location.href = "./?site=domain_list&op=rep&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
				
				function dnshttp_userui_del() {
					window.location.href = "./?site=domain_list&op=del&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}
			
			</script>				
		
			<!----------------- Modals -------------------->
			<div class="modal" id="cpage_prefer_item_modal" tabindex="-1">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Prefer Domain</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				  </div>
				  <div class="modal-body">
					<p>Solve conflicts by prefering this domain and ignoring other conflicting domains witht he same domain name in DNS Queries?</p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-success" onClick="dnshttp_userui_prefer()">Prefer</button>
				  </div>
				</div>
			  </div>
			</div>	
			
			<div class="modal" id="cpage_unprefer_item_modal" tabindex="-1">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Unprefer Domain</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				  </div>
				  <div class="modal-body">
					<p>If you undo the prefer of this domain, it may will cause domain conflicts which needs to be solved manually by prefering another domain.</p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-danger" onClick="dnshttp_userui_unprefer()">Unprefer</button>
				  </div>
				</div>
			  </div>
			</div>			
		
			<div class="modal" id="cpage_norep_item_modal" tabindex="-1">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Disable Replication</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				  </div>
				  <div class="modal-body">
					<p>Do you want to disable replication of this domain to other master servers?</p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-danger" onClick="dnshttp_userui_norep()">Disable Replication</button>
				  </div>
				</div>
			  </div>
			</div>	
			
			<div class="modal" id="cpage_rep_item_modal" tabindex="-1">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Enable Replication</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				  </div>
				  <div class="modal-body">
					<p>Do you want to enable this domains replication to other master servers?</p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-warning" onClick="dnshttp_userui_rep()">Replicate</button>
				  </div>
				</div>
			  </div>
			</div>			

			<div class="modal" id="cpage_del_item_modal" tabindex="-1">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Remove Domain</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				  </div>
				  <div class="modal-body">
					<p>Do you want to delete this domain? This action cannot be undone.</p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-danger" onClick="dnshttp_userui_del()">Delete</button>
				  </div>
				</div>
			  </div>
			</div>			
			
			<?php if(@$_GET["op"] == "viewrecord") {  ?>
			<div class="modal" id="cpage_rvv_item_modal" tabindex="-1" style="<?php if(@$_GET["op"] == "viewrecord") { echo "display: block;"; } ?> background: rgba(0,0,0,0.6);">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Domain Record View</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onClick="dnshttp_userui_viewrecord_close()"></button>
				  </div>
				  <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
					<p><?php echo nl2br(htmlspecialchars($current_user["content"] ?? '')); ?></p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" onClick="dnshttp_userui_viewrecord_close()">Close</button>
				  </div>
				</div>
			  </div>
			</div>		
			<?php } ?>
			
			<?php if(@$_GET["op"] == "changerecord") {  ?>
			<div class="modal" id="cpage_rvsv_item_modal" tabindex="-1" style="<?php if(@$_GET["op"] == "changerecord") { echo "display: block;"; } ?> background: rgba(0,0,0,0.6); ">
			  <div class="modal-dialog"   style="min-width: 90% !important;">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Domain Record Change<br /><small>Serial <?php echo $current_user["serial_c"]; ?>/<?php echo incrementSOASerial($current_user["serial_c"] ?? ''); ?> </small></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onClick="dnshttp_userui_changerecord_close()"></button>
				  </div>
				  <div class="modal-body" id="dns-zone-editor"  data-zone="<?php echo preg_replace('/\b'.$current_user["serial_c"].'\b/', incrementSOASerial($current_user["serial_c"] ?? ''), htmlentities($current_user["content"] ?? ''), 1); ?>">
					
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" onClick="dnshttp_userui_changerecord_close()">Close</button>
					<button type="button" class="btn btn-warning" onClick="dnshttp_userui_changerecord_save()">Save</button>
				  </div>
				</div>
			  </div>
			</div>		
			<script>
			
				(function () {
				'use strict';
				 
				/* ── Config ─────────────────────────────────────────────────────────────── */
				const RECORD_TYPES = ['A','AAAA','NS','MX','CNAME','TXT','SOA','PTR','SRV','CAA'];
				const RDATA_HINTS = {
				  A:    '1.2.3.4',
				  AAAA: '2001:db8::1',
				  NS:   'ns1.example.com.',
				  MX:   '10 mail.example.com.',
				  CNAME:'alias.example.com.',
				  TXT:  '"v=spf1 include:example.com ~all"',
				  PTR:  'hostname.example.com.',
				  SRV:  '10 20 443 target.example.com.',
				  CAA:  '0 issue "letsencrypt.org"',
				  SOA:  'ns1 admin ( 2024010101 3600 900 604800 300 )',
				};
				const TYPE_COLORS = {
				  A:'#2563eb', AAAA:'#7c3aed', NS:'#0891b2', MX:'#d97706',
				  CNAME:'#16a34a', TXT:'#dc2626', SOA:'#6b7280', PTR:'#be185d',
				  SRV:'#0f766e', CAA:'#9333ea',
				};
				 
				/* ── Helpers ─────────────────────────────────────────────────────────────── */
				function uid() { return '_' + Math.random().toString(36).slice(2, 9); }
				 
				function parseTTL(val) {
				  if (!val) return 3600;
				  const s = String(val).trim();
				  if (/^\d+$/.test(s)) return parseInt(s, 10);
				  const m = s.match(/^(\d+)([smhdw])/i);
				  if (m) {
					const u = { s:1, m:60, h:3600, d:86400, w:604800 };
					return parseInt(m[1], 10) * (u[m[2].toLowerCase()] || 1);
				  }
				  return 3600;
				}
				 
				function formatTTL(secs) {
				  if (secs % 86400 === 0 && secs >= 86400) return (secs/86400) + 'd';
				  if (secs % 3600  === 0 && secs >= 3600)  return (secs/3600)  + 'h';
				  if (secs % 60    === 0 && secs >= 60)    return (secs/60)    + 'm';
				  return String(secs);
				}
				 
				/* ── Parser ──────────────────────────────────────────────────────────────── */
				function parseZone(raw) {
				  const zone = { origin: <?php echo json_encode($current_user["domain"]."."); ?>, ttl: 3600, records: [] };
				  if (!raw || !raw.trim()) return zone;
				 
				  let text = raw;
				  /* Collapse parenthesised multi-line blocks (SOA) */
				  text = text.replace(/\([\s\S]*?\)/g, m =>
					'( ' + m.replace(/[()]/g,'').replace(/\s+/g,' ').trim() + ' )');
				  /* Strip comments */
				  text = text.replace(/"[^"]*"|;[^\n]*/g, m => m.startsWith('"') ? m : '');
				 
				  const lines = text.split('\n');
				  let lastName = '@';
				  let defaultTTL = 3600;
				 
				  for (const raw of lines) {
					const line = raw.trim();
					if (!line) continue;
				 
					if (/^\$ORIGIN\s+/i.test(line)) { zone.origin = line.split(/\s+/)[1]; continue; }
					if (/^\$TTL\s+/i.test(line)) { defaultTTL = parseTTL(line.split(/\s+/)[1]); zone.ttl = defaultTTL; continue; }
				 
					const tokens = line.split(/\s+/);
					let i = 0;
					let name;
				 
					if (/^\s/.test(raw)) {
					  name = lastName;
					} else {
					  name = tokens[i++];
					  lastName = name;
					}
				 
					let ttl = defaultTTL, cls = 'IN';
				 
					if (i < tokens.length && /^\d+$/.test(tokens[i])) ttl = parseTTL(tokens[i++]);
					if (i < tokens.length && /^(IN|CH|HS)$/i.test(tokens[i])) cls = tokens[i++].toUpperCase();
					if (i < tokens.length && /^\d+$/.test(tokens[i])) ttl = parseTTL(tokens[i++]);
				 
					const type = tokens[i++]?.toUpperCase();
					if (!type || !RECORD_TYPES.includes(type)) continue;
				 
					const rdata = tokens.slice(i).join(' ');
					zone.records.push({ id: uid(), name, ttl, class: cls, type, rdata });
				  }
				  return zone;
				}
				 
				/* ── Export ──────────────────────────────────────────────────────────────── */
				function exportZone(zone) {
				  const lines = [];
				  if (zone.origin) lines.push('$ORIGIN ' + zone.origin);
				  lines.push('$TTL ' + formatTTL(zone.ttl));
				  lines.push('');
				  for (const r of zone.records) {
					lines.push([r.name, r.ttl, r.class, r.type, r.rdata].join('\t'));
				  }
				  return lines.join('\n');
				}
				 
				/* ── State ───────────────────────────────────────────────────────────────── */
				let zone = { origin: 'example.com.', ttl: 3600, records: [] };
				let newRec = { name: '', ttl: '', type: 'A', rdata: '' };
				 
				/* ── Styles ──────────────────────────────────────────────────────────────── */
				function injectStyles() {
				  if (document.getElementById('dze-style')) return;
				  const s = document.createElement('style');
				  s.id = 'dze-style';
				  s.textContent = `
					.dze { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 14px; color: #111; }
					.dze *, .dze *::before, .dze *::after { box-sizing: border-box; }
					.dze-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
					.dze-header { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid #f0f0ed; flex-wrap: wrap; }
					.dze-header-left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
					.dze-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; flex-shrink: 0; }
					.dze-origin-wrap { display: flex; align-items: center; gap: 6px; }
					.dze-label { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #9ca3af; }
					.dze-origin-inp { font-family: ui-monospace, monospace; font-size: 14px; font-weight: 600; color: #111; border: none; background: transparent; outline: none; min-width: 0; width: 200px; }
					.dze-origin-inp:focus { color: #2563eb; }
					.dze-meta { display: flex; align-items: center; gap: 16px; }
					.dze-ttl-wrap { display: flex; align-items: center; gap: 6px; }
					.dze-ttl-inp { font-family: ui-monospace, monospace; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 3px 7px; width: 64px; color: #374151; background: #f9fafb; outline: none; }
					.dze-ttl-inp:focus { border-color: #2563eb; background: #fff; }
					.dze-count { font-size: 12px; color: #9ca3af; }
					.dze-actions { display: flex; gap: 8px; }
					.dze-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 7px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all .12s; border: none; }
					.dze-btn-sec { background: #f3f4f6; color: #374151; }
					.dze-btn-sec:hover { background: #e5e7eb; }
					.dze-btn-pri { background: #2563eb; color: #fff; }
					.dze-btn-pri:hover { background: #1d4ed8; }
					.dze-btn-ghost { background: transparent; color: #6b7280; padding: 4px 6px; }
					.dze-btn-ghost:hover { background: #f3f4f6; color: #111; }
					.dze-btn-danger:hover { background: #fef2f2; color: #dc2626; }
					.dze-table-wrap { overflow-x: auto; }
					table.dze-table { width: 100%; border-collapse: collapse; font-size: 13px; }
					.dze-table th { text-align: left; padding: 8px 10px; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #9ca3af; background: #fafafa; border-bottom: 1px solid #f0f0ed; white-space: nowrap; }
					.dze-table td { padding: 4px 6px; border-bottom: 1px solid #f7f7f5; vertical-align: middle; }
					.dze-table tr:last-child td { border-bottom: none; }
					.dze-table tr:hover td { background: #f9fafb; }
					.dze-inp { font-family: ui-monospace, monospace; font-size: 12.5px; border: 1px solid transparent; border-radius: 5px; padding: 4px 6px; width: 100%; background: transparent; color: #111; outline: none; transition: border-color .1s, background .1s; }
					.dze-inp:hover { border-color: #e5e7eb; background: #fff; }
					.dze-inp:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 2px rgba(37,99,235,.1); }
					.dze-inp-wide { min-width: 180px; }
					.dze-type-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 700; letter-spacing: .04em; font-family: ui-monospace, monospace; }
					.dze-type-sel { font-family: ui-monospace, monospace; font-size: 12px; font-weight: 700; border: 1px solid transparent; border-radius: 5px; padding: 3px 5px; background: transparent; cursor: pointer; outline: none; }
					.dze-type-sel:focus { border-color: #2563eb; background: #fff; }
					.dze-add-row { display: grid; grid-template-columns: 1fr 80px 110px 1fr auto; gap: 6px; padding: 10px 10px; background: #f9fafb; border-top: 1px solid #e5e7eb; align-items: center; }
					.dze-add-inp { font-family: ui-monospace, monospace; font-size: 12.5px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 5px 8px; width: 100%; background: #fff; color: #111; outline: none; }
					.dze-add-inp:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.1); }
					.dze-add-sel { font-family: ui-monospace, monospace; font-size: 12px; font-weight: 700; border: 1px solid #e5e7eb; border-radius: 6px; padding: 5px 8px; background: #fff; cursor: pointer; outline: none; width: 100%; }
					.dze-add-sel:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.1); }
					.dze-empty { text-align: center; padding: 32px; color: #9ca3af; font-style: italic; }
					.dze-toast { position: fixed; bottom: 20px; right: 20px; background: #111; color: #fff; padding: 10px 16px; border-radius: 8px; font-size: 13px; opacity: 0; transform: translateY(6px); transition: all .2s; pointer-events: none; z-index: 9999; }
					.dze-toast.show { opacity: 1; transform: translateY(0); }
					@media (max-width: 600px) {
					  .dze-add-row { grid-template-columns: 1fr 1fr; }
					  .dze-header { flex-direction: column; align-items: flex-start; }
					}
				  `;
				  document.head.appendChild(s);
				}
				 
				/* ── Toast ────────────────────────────────────────────────────────────────── */
				let toastTimer;
				function toast(msg) {
				  let el = document.getElementById('dze-toast');
				  if (!el) { el = document.createElement('div'); el.id = 'dze-toast'; el.className = 'dze-toast'; document.body.appendChild(el); }
				  el.textContent = msg;
				  el.classList.add('show');
				  clearTimeout(toastTimer);
				  toastTimer = setTimeout(() => el.classList.remove('show'), 2000);
				}
				 
				/* ── Render ──────────────────────────────────────────────────────────────── */
				function typeColor(t) { return TYPE_COLORS[t] || '#6b7280'; }
				 
				function typeOptions(sel) {
				  return RECORD_TYPES.map(t =>
					`<option value="${t}"${t===sel?' selected':''}>${t}</option>`
				  ).join('');
				}
				 
				function render() {
				  const container = document.getElementById('dns-zone-editor');
				  if (!container) return;
				 
				  const rows = zone.records.map(r => {
					const col = typeColor(r.type);
					return `<tr data-id="${r.id}">
					  <td><input class="dze-inp" data-field="name" value="${esc(r.name)}" placeholder="@" spellcheck="false"></td>
					  <td><input class="dze-inp" data-field="ttl" value="${formatTTL(r.ttl)}" placeholder="${formatTTL(zone.ttl)}" style="width:70px" spellcheck="false"></td>
					  <td>
						<select class="dze-type-sel" data-field="type" style="color:${col}">
						  ${typeOptions(r.type)}
						</select>
					  </td>
					  <td><input class="dze-inp dze-inp-wide" data-field="rdata" value="${esc(r.rdata)}" placeholder="${RDATA_HINTS[r.type]||''}" spellcheck="false"></td>
					  <td>
						<button class="dze-btn dze-btn-ghost dze-btn-danger" data-action="delete" data-id="${r.id}" title="Record löschen" aria-label="Record löschen">
						  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
						</button>
					  </td>
					</tr>`;
				  }).join('');
				 
				  const emptyMsg = zone.records.length === 0
					? `<tr><td colspan="5" class="dze-empty">Keine Records. Füge unten einen hinzu.</td></tr>`
					: '';
				 
				  const newTtlHint = formatTTL(zone.ttl);
				 
				  container.innerHTML = `
				<div class="dze">
				  <div class="dze-card">
					<div class="dze-header">
					  <div class="dze-header-left">
						<span class="dze-dot"></span>
						<div class="dze-origin-wrap">
						  <span class="dze-label">Zone</span>
						  <input class="dze-origin-inp" id="dze-origin" value="${esc(zone.origin)}" placeholder="example.com." spellcheck="false" aria-label="Zone Origin">
						</div>
					  </div>
					  <div class="dze-meta">
						<div class="dze-ttl-wrap">
						  <span class="dze-label">TTL</span>
						  <input class="dze-ttl-inp" id="dze-default-ttl" value="${formatTTL(zone.ttl)}" placeholder="3600" aria-label="Standard TTL">
						</div>
						<span class="dze-count">${zone.records.length} Record${zone.records.length !== 1 ? 's' : ''}</span>
					  </div>
					  <div class="dze-actions">
						<button class="dze-btn dze-btn-sec" data-action="copy-zone" title="Zone-Datei kopieren">
						  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
						  Zone kopieren
						</button>
					  </div>
					</div>
					<div class="dze-table-wrap">
					  <table class="dze-table" aria-label="DNS Records">
						<thead>
						  <tr>
							<th>Name</th>
							<th>TTL</th>
							<th>Typ</th>
							<th>Daten (RDATA)</th>
							<th></th>
						  </tr>
						</thead>
						<tbody id="dze-tbody">
						  ${rows}${emptyMsg}
						</tbody>
					  </table>
					</div>
					<div class="dze-add-row" role="group" aria-label="Neuen Record hinzufügen">
					  <input class="dze-add-inp" id="dze-new-name"  value="${esc(newRec.name)}"  placeholder="Name (z.B. www oder @)" spellcheck="false" aria-label="Name">
					  <input class="dze-add-inp" id="dze-new-ttl"   value="${esc(newRec.ttl)}"   placeholder="${newTtlHint}" spellcheck="false" aria-label="TTL">
					  <select style="color: black !important;" class="dze-add-sel" id="dze-new-type"  aria-label="Record-Typ">${typeOptions(newRec.type)}</select>
					  <input class="dze-add-inp" id="dze-new-rdata" value="${esc(newRec.rdata)}" placeholder="${RDATA_HINTS[newRec.type]||''}" spellcheck="false" id="dze-new-rdata" aria-label="RDATA">
					  <button class="dze-btn dze-btn-pri" data-action="add-record">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						Hinzufügen
					  </button>
					</div>
				  </div>
				</div>`;
				 
				  bindEvents(container);
				}
				 
				function esc(s) {
				  return String(s||'')
					.replace(/&/g,'&amp;')
					.replace(/"/g,'&quot;')
					.replace(/</g,'&lt;')
					.replace(/>/g,'&gt;');
				}
				 
				/* ── Events ──────────────────────────────────────────────────────────────── */
				function bindEvents(container) {
				  /* Origin */
				  container.querySelector('#dze-origin').addEventListener('change', e => {
					zone.origin = e.target.value.trim();
				  });
				 
				  /* Default TTL */
				  container.querySelector('#dze-default-ttl').addEventListener('change', e => {
					const v = parseTTL(e.target.value);
					if (v > 0) { zone.ttl = v; e.target.value = formatTTL(v); }
					else e.target.value = formatTTL(zone.ttl);
					container.querySelector('.dze-count').textContent =
					  zone.records.length + ' Record' + (zone.records.length !== 1 ? 's' : '');
				  });
				 
				  /* Inline record edits */
				  container.querySelector('#dze-tbody').addEventListener('change', e => {
					const tr = e.target.closest('tr[data-id]');
					if (!tr) return;
					const id = tr.dataset.id;
					const field = e.target.dataset.field;
					const rec = zone.records.find(r => r.id === id);
					if (!rec || !field) return;
					if (field === 'ttl') {
					  const v = parseTTL(e.target.value);
					  rec.ttl = v > 0 ? v : zone.ttl;
					  e.target.value = formatTTL(rec.ttl);
					} else if (field === 'type') {
					  rec.type = e.target.value;
					  e.target.style.color = typeColor(rec.type);
					} else {
					  rec[field] = e.target.value.trim();
					}
				  });
				 
				  /* New record type → update rdata placeholder */
				  const newTypeSel = container.querySelector('#dze-new-type');
				  const newRdataInp = container.querySelector('#dze-new-rdata');
				  newTypeSel.addEventListener('change', e => {
					newRec.type = e.target.value;
					newRdataInp.placeholder = RDATA_HINTS[newRec.type] || '';
				  });
				 
				  /* Buttons */
				  container.addEventListener('click', e => {
					const btn = e.target.closest('[data-action]');
					if (!btn) return;
					const action = btn.dataset.action;
				 
					if (action === 'delete') {
					  const id = btn.dataset.id;
					  zone.records = zone.records.filter(r => r.id !== id);
					  render();
					}
				 
					if (action === 'add-record') {
					  const name  = container.querySelector('#dze-new-name').value.trim();
					  const ttlRaw= container.querySelector('#dze-new-ttl').value.trim();
					  const type  = container.querySelector('#dze-new-type').value;
					  const rdata = container.querySelector('#dze-new-rdata').value.trim();
					  if (!name || !rdata) {
						toast('Name und Daten (RDATA) sind Pflichtfelder.');
						return;
					  }
					  zone.records.push({
						id: uid(),
						name,
						ttl: ttlRaw ? parseTTL(ttlRaw) : zone.ttl,
						class: 'IN',
						type,
						rdata,
					  });
					  newRec = { name: '', ttl: '', type, rdata: '' };
					  render();
					}
				 
					if (action === 'copy-zone') {
					  const text = exportZone(zone);
					  if (navigator.clipboard) {
						navigator.clipboard.writeText(text).then(() => toast('Zone-Datei kopiert!'));
					  } else {
						const ta = document.createElement('textarea');
						ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
						document.body.appendChild(ta); ta.select();
						document.execCommand('copy');
						document.body.removeChild(ta);
						toast('Zone-Datei kopiert!');
					  }
					}
				  });
				}
				 
				/* ── Init ────────────────────────────────────────────────────────────────── */
				function init() {
				  injectStyles();
				  const container = document.getElementById('dns-zone-editor');
				  if (!container) { console.warn('[dns-zone-editor] #dns-zone-editor not found'); return; }
				 
				  const raw = container.dataset.zone
					|| window.dnsZoneData
					|| '';
				 
				  zone = parseZone(raw);
				  render();
				 
				  /* Public API */
				  window.DNSZoneEditor = {
					getZoneData: () => exportZone(zone),
					getRecords:  () => zone.records.map(r => ({ ...r })),
					setZoneData: (text) => { zone = parseZone(text); render(); },
					addRecord:   (rec) => { zone.records.push({ id: uid(), class:'IN', ttl: zone.ttl, ...rec }); render(); },
				  };
				}
				 
				if (document.readyState === 'loading') {
				  document.addEventListener('DOMContentLoaded', init);
				} else {
				  init();
				}
				 
				})();			
							
			</script>
			<?php } ?>
		
		<!----------------- Information Callout -------------------->
		<div class="callout callout-info mb-3" role="alert">Here you can find details about the domain '<b><?php echo htmlspecialchars($current_user["domain"] ?? ''); ?></b>' with identification number #<?php echo @$_GET[$c_domain_id_r]; ?>.</div>
		<?php if(@$current_user["registered"] == 1) { ?> <div class="callout callout-success mb-3" role="alert">This domain is currently registered and active for queries. </div> <?php } ?>
		<?php if(@$current_user["conflict"] == 1 AND @$current_user["preferred"] != 1) { ?> <div class="callout callout-warning mb-3" role="alert">This domain is in conflict with other duplicated domain names. </div> <?php } ?>
		<?php if(@$current_user["set_no_replicate"] == 1) { ?> <div class="callout callout-danger mb-3" role="alert">This domain has external replication disabled and will not be duplicated to other requesting master servers. </div> <?php } ?>
		<?php if(@$current_user["oldzonefallback"] == 1) { ?> <div class="callout callout-danger mb-3" role="alert">This domains zone data has errors, please fix the domains zone data. Currently the server is using the last working zone data configuration for this server. </div> <?php } ?>
		<?php if(@$current_user["zonecheck"] == 0) { ?> <div class="callout callout-warning mb-3" role="alert">The DNS zone may has errors, or not yet been reinitialized by the background worker (can take up to 2 hours). </div> <?php } ?>
			  		
		<!----------------- Status Boxes -------------------->
		<div class="row">
		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-dark shadow-sm">
				<i class="bi bi-gear-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text">Type</span>
				<span class="info-box-number">
				  <?php echo $c_tpe_raw;   ?>
				</span>
			  </div>
			</div>
		  </div>

		  <div class="col-12 col-sm-6 col-md-6">
			<div class="info-box">
			  <span class="info-box-icon text-bg-dark shadow-sm">
				<i class="bi bi-gear-fill"></i>
			  </span>
			  <div class="info-box-content">
				<span class="info-box-text">Last Update</span>
				<span class="info-box-number">
				  <?php echo htmlspecialchars($current_user["modification"] ?? '');  ?>
				</span>
			  </div>
			</div>
		  </div>
		</div>				  
				  
		<!----------------- User Listing and Details Section -------------------->
		<div class="row">
			<div class="col-md-5">	
				<!----------------- Back to List  -------------------->
				<a href="./?site=domain_list" style="text-decoration: none !important; font-weight: bold;">
					<div class="info-box bg-light">
					  <span class="info-box-icon text-bg-dark shadow-sm">
						<i class="bi bi-arrow-return-left"></i>
					  </span>
					  <div class="info-box-content">
						<span class="info-box-text" style="text-decoration: none !important;">Return to Listing</span>
					  </div>
					</div>				
				</a>	
			</div>			
		
			<!----------------- Server Operation Area  -------------------->
			<div class="col-md-7">
			
				<div class="row">			
					<div class="col-md-12">
						<!----------------- Show Form for User Block Status  -------------------->
						<div class="card card-dark mb-4">
						  <div class="card-header">
							<div class="card-title">Domain Operations</div>
						  </div>
							<div class="card-footer">
							
								  <?php if($c_domain_id_r != "api_id") { ?>
										You can disable replication of this domain to other servers, than other servers will not fetch this domain anymore.<br />
									  <?php if($current_user["set_no_replicate"] == 0) { ?>
										  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cpage_rep_item_modal" disabled>Enable Replication</button>
										  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cpage_norep_item_modal">Disable Replication</button>
									  <?php } else { ?>
										  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cpage_rep_item_modal">Enable Replication</button>
										  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cpage_norep_item_modal" disabled>Disable Replication</button>
									  <?php } ?>
								  <br clear="">
								  <br clear="">
								  <?php } ?>
								  
									You can disable prefer a domain to solve a conflict if a conflict is existant.<br />
							
								  <?php if($current_user["preferred"] == 1) { ?>
									  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cpage_prefer_item_modal" disabled>Prefer Domain</button>
									  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cpage_unprefer_item_modal">Unprefer Domain</button>
								  <?php } else { ?>
									  <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cpage_prefer_item_modal">Prefer Domain</button>
									  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cpage_unprefer_item_modal" disabled>Unprefer Domain</button>
								  <?php } ?>
								  <br clear="">
								  <br clear="">
								  
									View and change (if possible) the current domains record data, you can not change the data on some domain types.<br />
									<button type="button" class="btn btn-dark" onClick="dnshttp_userui_viewrecord()">View Records</button>
									  <?php if(@$current_user["fk_user"] > 0) { ?>
										<button type="button" class="btn btn-warning" onClick="dnshttp_userui_changerecord()">Change Records</button>
									  <?php } ?>
									  
								  <?php if(@$current_user["fk_user"] > 0) { ?>
									  <br clear="">
									  <br clear="">
									CAUTION: You can delete the domain and its data if required..<br />
									<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cpage_del_item_modal">Delete Domain</button>
								  <?php } ?>

							</div>
						</div>
					</div>		
				</div>		
			
				<?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
				<!----------------- Table Element  -------------------->
				<div class="card card-dark mb-4">
				  <!-- /.card-header -->
				  <div class="card-header">
					<h3 class="card-title">Duplications</h3>
				  </div>
				  <div class="card-body p-0">
						<table class="table table-striped">
						  <thead>
							<tr>
							  <th>Domain</th>
							  <th style="width: 180px">Source</th>
							  <th style="width: 120px">Status</th>
							  <th style="width: 120px">Inspect</th>
							</tr>
						  </thead>
						  <tbody>	
							<?php
								$has_one_entry = false;
								$bindnew = array();
								$bindnew[0]["value"] = strtolower(trim(@$current_user["domain"] ?? '') ?? '');							
								$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE TRIM(LOWER(domain)) = ? ORDER BY domain ASC", true, $bindnew); 
								$res2 = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE TRIM(LOWER(domain)) = ? ORDER BY domain ASC", true, $bindnew); 
								if(!is_array($res)) { $res = array(); }
								if(!is_array($res2)) { $res2 = array(); }
								$merged = array_merge($res, $res2);
								if(is_array($merged)) { 
									foreach ($merged AS $key => $value) { 
										$is_bind_dom = true;
										$is_bind_c = false;
										if(isset($value["fk_server"])) { 
											$is_bind_dom = false;
										}
										if(is_numeric(@$_GET["bind_id"])) { $is_bind_c = true; }
									
										$has_one_entry = true;
										?>
											<tr class="align-middle" <?php if(@$value["id"] == $current_user["id"] AND ((!$is_bind_dom AND !$is_bind_c) OR ($is_bind_dom AND $is_bind_c))) { ?> style="background: #424649 !important; color: white;" <?php } ?>>
											  <td class="pre_before_ip_getter" <?php if(@$value["id"] == $current_user["id"] AND ((!$is_bind_dom AND !$is_bind_c) OR ($is_bind_dom AND $is_bind_c))) { ?> style="background: #424649 !important;color: white;" <?php } ?>><?php echo htmlspecialchars($value["domain"] ?? ''); ?></td>
											  <td <?php if(@$value["id"] == $current_user["id"] AND ((!$is_bind_dom AND !$is_bind_c) OR ($is_bind_dom AND $is_bind_c))) { ?> style="background: #424649 !important; color: white; " <?php } ?>>
												<?php 
													
													if(@$value["fk_server"] > 0) {
														echo 'Slave at '.' #'.$value["fk_server"];
													} elseif(@$value["fk_user"] > 0) {
														echo 'User'.' #'.$value["fk_user"];
													} else {
														echo 'Master';
													}
												?>
											  </td>
											  <td <?php if(@$value["id"] == $current_user["id"] AND ((!$is_bind_dom AND !$is_bind_c) OR ($is_bind_dom AND $is_bind_c))) { ?> style="background: #424649 !important; color: white;" <?php } ?>><?php
												if($value["registered"] == "1") { echo '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
												if($value["registered"] != "1") { echo '<span class="badge text-bg-danger" title="Not active for queries">NR</span> '; }
												if($value["conflict"] == "1") { echo '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
												if($value["preferred"] == "1") { echo '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
												if(@$value["set_no_replicate"] == "1") { echo '<span class="badge text-bg-danger" title="Replication to other servers Disabled">RD</span> '; }
												if($value["oldzonefallback"] == "1") { echo '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
												if($value["okonce"] == "0") { echo '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
												if($value["zonecheck"] == "0") { echo '<span class="badge text-bg-danger" title="Zone invalidated...">ZE</span> '; }
											  ?></td>
											  <td <?php if(@$value["id"] == $current_user["id"] AND ((!$is_bind_dom AND !$is_bind_c) OR ($is_bind_dom AND $is_bind_c))) { ?> style="background: #424649 !important; color: white;" <?php } ?>>
												<?php if(@$value["fk_server"] > 0) { if(@$value["id"] != $current_user["id"]) { echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&api_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; } }
														else { if(@$value["id"] != $current_user["id"]) { echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; } } ?>
												<?php if(@$value["fk_server"] > 0) { echo "<a class='btn btn-dark btn-sm' title='Inspect this domains server' href='./?site=server_list&id=".trim(strtolower($value["fk_server"] ?? '') ?? '')."'><i class=\"bi bi-server\"></i></a>"; } ?>
												<?php if(@$value["fk_user"] > 0) { echo "<a class='btn btn-dark btn-sm' title='Inspect this domains user' href='./?site=user_list&id=".trim(strtolower($value["fk_user"] ?? '') ?? '')."'><i class=\"bi bi-person\"></i></a>"; } ?>
											  </td>
											</tr>		
										<?php
									}
								} 
								if(!$has_one_entry) { 
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
				<?php } ?>					
					
			</div>
		</div>	
		
		<?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
		<!----------------- Table Element  -------------------->
		<div class="card card-dark mb-4">
		  <!-- /.card-header -->
		  <div class="card-header">
			<h3 class="card-title">Debugging</h3>
		  </div>
		  <div class="card-body p-0">
				<table class="table table-striped">
				  <thead>
					<tr>
					  <th style="width: 180px">Variable</th>
					  <th>Value</th>
					</tr>
				  </thead>
				  <tbody>	
					<?php
						foreach ($current_user AS $key => $value) { 
							if($key == "") { }
							?>
								<tr class="align-middle">
									<td><?php echo htmlspecialchars($key ?? ''); ?></td>
									<td><?php echo nl2br(htmlspecialchars($value ?? '')); ?></td>
								</tr>		
							<?php
						}
					?>	   
				  </tbody>
				</table>
		  </div>
		</div>		
		<?php } ?>		
		
		<?php
	}
	
	/*************************************************************************
		Specific Domain Listing Area
	*************************************************************************/
	else { 
		?>
		<!----------------- Show Alert Boxes -------------------->
		<div class="callout callout-info mb-3" role="alert">You can currently see all duplicates and entries for the domain: '<b><?php echo htmlspecialchars(base64_decode(@$_GET["domain"] ?? '') ?? ''); ?></b>'.</div>
		
		<div class="col-12 col-sm-12">
            <div class="card card-dark card-tabs">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "my") { echo 'active'; } ?>" id="custom-tabs-one-home-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true" href="./?site=domain_list&section=my" style="color: <?php if(@$_GET["section"] == "my") { echo 'black'; } else { echo 'white'; } ?>;">My Domains</a>
                  </li>
                   <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?><li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "master") { echo 'active'; }?>" id="custom-tabs-one-profile-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-profile" aria-selected="false" href="./?site=domain_list&section=master" style="color: <?php if(@$_GET["section"] == "master") { echo 'black'; } else { echo 'white'; }  ?>;">Master</a>
				  </li><?php } ?>
                   <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?><li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "slave") { echo 'active'; } ?>" id="custom-tabs-one-messages-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-messages" aria-selected="false" href="./?site=domain_list&section=slave" style="color: <?php if(@$_GET["section"] == "slave") { echo 'black'; } else { echo 'white'; }  ?>;">Slave</a>
                  </li><?php } ?>
                   <?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?><li class="nav-item">
                    <a class="nav-link <?php if(@$_GET["section"] == "users") { echo 'active'; } ?>" id="custom-tabs-one-settings-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-settings" aria-selected="false" href="./?site=domain_list&section=users" style="color: <?php if(@$_GET["section"] == "users") { echo 'black'; } else { echo 'white'; }  ?>;">Users</a>
                  </li><?php } ?>
				  <li class="nav-item">
                    <a class="nav-link <?php echo 'active'; ?>" id="custom-tabs-one-settings-tab" data-toggle="pill" role="tab" aria-controls="custom-tabs-one-settings" aria-selected="false" href="#" style="color: <?php echo 'black';  ?>;">Duplicates</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                  <div class="tab-pane fade active show" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">		
		
					<!----------------- Table Element  -------------------->
					  <div class="card-body p-0 tablecard">
						<div class="scrolltableinit">
							<table class="table table-striped">
							  <thead>
								<tr>
								  <th>Domain</th>
								  <th style="width: 180px">Source</th>
								  <th style="width: 180px">Status</th>
								  <th style="width: 180px">Inspect</th>
								</tr>
							  </thead>
							  <tbody>	
								<?php
									$has_one_entry = false;
									$bindnew = array();
									$bindnew[0]["value"] = strtolower(trim(base64_decode(@$_GET["domain"] ?? '') ?? '') ?? '');							
									$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE TRIM(LOWER(domain)) = ? ORDER BY domain ASC", true, $bindnew); 
									$res2 = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE TRIM(LOWER(domain)) = ? ORDER BY domain ASC", true, $bindnew); 
									if(!is_array($res)) { $res = array(); }
									if(!is_array($res2)) { $res2 = array(); }
									$merged = array_merge($res, $res2);
									if(is_array($merged)) { 
										foreach ($merged AS $key => $value) { 
											$has_one_entry = true;
											?>
												<tr class="align-middle">
												  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["domain"] ?? ''); ?> 					
												  </td>
												  <td>
													<?php 
														
														if(@$value["fk_server"] > 0) {
															echo 'Slave at '.' #'.$value["fk_server"];
														} elseif(@$value["fk_user"] > 0) {
															echo 'User'.' #'.$value["fk_user"];
														} else {
															echo 'Master';
														}
													?>
												  </td>
												  <td><?php
													if($value["registered"] == "1") { echo '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
													if($value["registered"] != "1") { echo '<span class="badge text-bg-danger" title="Not active for queries">NR</span> '; }
													if($value["conflict"] == "1") { echo '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
													if($value["preferred"] == "1") { echo '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
													if(@$value["set_no_replicate"] == "1") { echo '<span class="badge text-bg-danger" title="Replication to other servers Disabled">RD</span> '; }
													if($value["oldzonefallback"] == "1") { echo '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
													if($value["okonce"] == "0") { echo '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
													if($value["zonecheck"] == "0") { echo '<span class="badge text-bg-danger" title="Zone invalidated...">ZE</span> '; }
												  ?></td><td>
													<?php if(@$value["fk_server"] > 0) { echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&api_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; }
															else { echo "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; } ?>
													<?php if(@$value["fk_server"] > 0) { echo "<a class='btn btn-dark btn-sm' title='Inspect this domains server' href='./?site=server_list&id=".trim(strtolower($value["fk_server"] ?? '') ?? '')."'><i class=\"bi bi-server\"></i></a>"; } ?>
													<?php if(@$value["fk_user"] > 0) { echo "<a class='btn btn-dark btn-sm' title='Inspect this domains user' href='./?site=user_list&id=".trim(strtolower($value["fk_user"] ?? '') ?? '')."'><i class=\"bi bi-person\"></i></a>"; } ?>												  
												  </td>
												</tr>		
											<?php
										}
									} 
									if(!$has_one_entry) { 
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
            </div>
          </div>		
		<?php
	}	
	
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");
	exit();