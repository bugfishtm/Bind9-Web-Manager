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
			$current_user = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE id = ".@$_GET["api_id"]."", false);
			if(!$current_user) { Header("Location: ../"); exit(); }
			define("_SUB_PAGE_TITLE_", htmlspecialchars($current_user["domain"] ?? '')." / Slave Domain");
			$_GET["bind_id"] = "";			
		} else {
			$c_domain_id_r = "bind_id";
			$c_tpe_raw = "Master";
			$c_domain_id = $_GET["bind_id"];
			$current_user = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = ".@$_GET["bind_id"]."", false);
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
			Enable / Disable DNSSEC on Domain
		*************************************************************************/
		# Only the local (master/user) side can be signed. A replicated domain carries the signatures
		# of its master along with the rest of the zone, there is nothing to switch on here.
		# The owner of a domain may switch it, same as changing its records - an administrator may
		# do it for every domain.
		if(@$_GET["op"] == "dnssec_on" OR @$_GET["op"] == "dnssec_off") {
			$dnssec_allowed = false;
			if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { $dnssec_allowed = true; }
			if(@$current_user["fk_user"] > 0 AND @$current_user["fk_user"] == $user->user_id) { $dnssec_allowed = true; }

			if(!is_numeric(@$_GET["bind_id"])) {
				x_eventBoxPrep("DNSSEC of a replicated (slave) domain is maintained on its master server.", "error", _COOKIES_);
			} elseif(!$dnssec_allowed) {
				x_eventBoxPrep("You need the domain administrator permission to change DNSSEC on a domain you do not own.", "error", _COOKIES_);
			} elseif(!$csrf->check(@$_GET['csrf'])) {
				x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_);
			} elseif(!dnshttp_dnssec_schema($mysql)) {
				x_eventBoxPrep("The DNSSEC columns are missing from the domain table. Please reload this page once so the database upgrade can run.", "error", _COOKIES_);
			} elseif(@$_GET["op"] == "dnssec_on") {
				if(dnshttp_dnssec_mode() === false) {
					x_eventBoxPrep("DNSSEC cannot be enabled on this server: ".dnshttp_dnssec_unavailable_reason(), "error", _COOKIES_);
				} else {
					$binddnssec = array();
					$binddnssec[0]["value"] = "Waiting for the background worker to generate the key material and write the signed configuration.";
					$binddnssec[0]["type"]  = "s";
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dnssec = 1, dnssec_status = ?, dnssec_update = CURRENT_TIMESTAMP() WHERE id = ".$_GET["bind_id"]."", $binddnssec);
					x_eventBoxPrep("DNSSEC has been requested for this domain. The next cronjob run signs the zone and reads out the DS records for your registrar.", "ok", _COOKIES_);
					Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."");
					exit();
				}
			} else {
				# The keys are deliberately kept. Removing them would make the zone unresolvable for
				# every validating resolver that still has the DS of the parent in cache, and the same
				# keys are needed again if signing is switched back on.
				$binddnssec = array();
				$binddnssec[0]["value"] = "Signing switched off. The existing keys are kept - remove the DS record at the registrar of the parent zone FIRST, otherwise resolvers that validate will stop resolving this domain.";
				$binddnssec[0]["type"]  = "s";
				$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dnssec = 0, dnssec_status = ?, dnssec_update = CURRENT_TIMESTAMP() WHERE id = ".$_GET["bind_id"]."", $binddnssec);
				x_eventBoxPrep("DNSSEC has been switched off for this domain. Remove the DS record at your registrar before the next cronjob run, otherwise validating resolvers will fail on this domain.", "ok", _COOKIES_);
				Header("Location: ./?site=domain_list&bind_id=".$_GET["bind_id"]."");
				exit();
			}
		}

		/*************************************************************************
			Prefer / Unprefer  on Domain
		*************************************************************************/
		if(@$_GET["op"] == "prefer") {
			if($permsobj->hasPerm($user->user_id, "domain_conflicts") OR $user->user_rank == 0) {
				if ($csrf->check(@$_GET['csrf'])) {
					
					// Exactly one entry per domain name may be preferred. With two of them the sync
					// writes two "zone" stanzas for the same name and bind rejects the whole
					// generated config, which takes every zone on this server down.
					// 'conflict' is deliberately left alone here - the cronjob recomputes it, and
					// clearing it on one side only produced an inconsistent pair.
					// Order matters: mark the winner FIRST, then clear the others while excluding it.
					// Clearing first would leave no preferred entry at all if the second statement
					// did not run, and a conflicted domain with nothing preferred stops resolving.
					$bindpref = array();
					$bindpref[0]["value"] = strtolower(trim($current_user["domain"] ?? ''));
					if(is_numeric($_GET["api_id"])) {
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 1 WHERE id = ".$_GET["api_id"]."");
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0 WHERE LOWER(TRIM(domain)) = ? AND id <> ".$_GET["api_id"]."", $bindpref);
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 0 WHERE LOWER(TRIM(domain)) = ?", $bindpref);
					}
					if(is_numeric($_GET["bind_id"])) {
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 1 WHERE id = ".$_GET["bind_id"]."");
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 0 WHERE LOWER(TRIM(domain)) = ? AND id <> ".$_GET["bind_id"]."", $bindpref);
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0 WHERE LOWER(TRIM(domain)) = ?", $bindpref);
					}

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
					// The editor emits "$ORIGIN <name>" as its first line and that is not stored.
					// This used to cut the first line unconditionally, which ate the $TTL line when
					// the origin field had been cleared, and ate the first CHARACTER when the text
					// contained no newline at all (strpos returns false, false + 1 = 1).
					$rawzone = (string)$_POST["zone"];
					$firstbreak = strpos($rawzone, "\n");
					if($firstbreak !== false AND preg_match('/^\s*\$ORIGIN\b/i', substr($rawzone, 0, $firstbreak))) {
						$zoneinput = substr($rawzone, $firstbreak + 1);
					} else {
						$zoneinput = $rawzone;
					}
					// $INCLUDE would make bind read arbitrary local files once the cronjob writes
					// this zone out, so it is removed before the content is stored.
					$zoneinput = dnshttp_zone_input_clean($zoneinput);
					if(is_numeric($_GET["bind_id"])) {
						$bindcc = array();
						$bindcc[0]["value"] = dnshttp_bump_zone_serial($zoneinput);
						$newserial = dnshttp_zone_get_serial($bindcc[0]["value"]);
						if($newserial !== false) {
							$bindcc[1]["value"] = $newserial;
							$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET content = ?, serial_c = ? WHERE id = ".$_GET["bind_id"]."", $bindcc);
						} else {
							$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET content = ? WHERE id = ".$_GET["bind_id"]."", $bindcc);
						}
						x_eventBoxPrep("The domain has successfully been changed.", "ok", _COOKIES_);
					} else {
						// Reached with an api_id, where nothing was ever written. Reporting success
						// here told the user their edit had been saved when it had not.
						x_eventBoxPrep("Zone data of a replicated (slave) domain cannot be changed here - it is maintained on its master server.", "error", _COOKIES_);
					}
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
													// Replicated rows have no DNSSEC column of their own - they inherit whatever
													// their master signed, so the badge only ever appears on local domains.
													if(@$value["dnssec"] == "1") { echo '<span class="badge text-bg-info" title="DNSSEC signing enabled for this zone">DS</span> '; }
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
													// Replicated rows have no DNSSEC column of their own - they inherit whatever
													// their master signed, so the badge only ever appears on local domains.
													if(@$value["dnssec"] == "1") { echo '<span class="badge text-bg-info" title="DNSSEC signing enabled for this zone">DS</span> '; }
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
													// Replicated rows have no DNSSEC column of their own - they inherit whatever
													// their master signed, so the badge only ever appears on local domains.
													if(@$value["dnssec"] == "1") { echo '<span class="badge text-bg-info" title="DNSSEC signing enabled for this zone">DS</span> '; }
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
													// Replicated rows have no DNSSEC column of their own - they inherit whatever
													// their master signed, so the badge only ever appears on local domains.
													if(@$value["dnssec"] == "1") { echo '<span class="badge text-bg-info" title="DNSSEC signing enabled for this zone">DS</span> '; }
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
					if(!window.DNSZoneEditor) { alert("The record editor did not load. Reload the page and try again."); return; }
					// Gives the user a last look at anything that would make bind refuse the zone,
					// and switches off the unsaved-changes guard when they go ahead.
					if(window.DNSZoneEditor.confirmSave && !window.DNSZoneEditor.confirmSave()) { return; }
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

				function dnshttp_userui_dnssec_on() {
					window.location.href = "./?site=domain_list&op=dnssec_on&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
				}

				function dnshttp_userui_dnssec_off() {
					window.location.href = "./?site=domain_list&op=dnssec_off&<?php echo $c_domain_id_r; ?>=<?php echo $_GET[$c_domain_id_r]; ?>&csrf=<?php echo $csrf->get(); ?>";
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

			<div class="modal" id="cpage_dnssec_on_item_modal" tabindex="-1">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Enable DNSSEC</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				  </div>
				  <div class="modal-body">
					<p>Do you want this zone to be signed with DNSSEC? The background worker generates the key material and rewrites the bind configuration on its next run, which can take up to two hours.</p>
					<p class="mb-0"><b>The zone is not validated yet at that point.</b> Once the keys exist, the DS record shown on this page has to be handed to the registrar of the parent zone. Only after the registrar has published it does the chain of trust exist.</p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-success" onClick="dnshttp_userui_dnssec_on()">Enable DNSSEC</button>
				  </div>
				</div>
			  </div>
			</div>

			<div class="modal" id="cpage_dnssec_off_item_modal" tabindex="-1">
			  <div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Disable DNSSEC</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				  </div>
				  <div class="modal-body">
					<p><b>Remove the DS record at the registrar of the parent zone first, and wait for it to expire from the caches.</b> A resolver that still sees a DS record but no signatures treats this domain as broken and stops answering for it entirely.</p>
					<p class="mb-0">The keys themselves are kept, so signing can be switched back on with the same key material.</p>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-danger" onClick="dnshttp_userui_dnssec_off()">Disable DNSSEC</button>
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
			  <div class="modal-dialog" style="min-width: 90% !important;">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Domain Record View<br /><small>Serial <?php echo htmlspecialchars($current_user["serial_c"] ?? ''); ?></small></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onClick="dnshttp_userui_viewrecord_close()"></button>
				  </div>
				  <!-- Same editor as the change view, locked. The zone data of a replicated or
					   locally fetched domain is not ours to write, but it is worth reading with
					   the filters and the checks of the editor instead of as a wall of text. -->
				  <div class="modal-body" id="dns-zone-editor"
					   data-domain="<?php echo htmlentities(strtolower(trim($current_user["domain"] ?? ''))); ?>"
					   data-origin="<?php echo htmlentities(rtrim(strtolower(trim($current_user["domain"] ?? '')), '.').'.'); ?>"
					   data-default-ttl="<?php echo htmlentities(defined("_USER_DOMAIN_MINIMUM_") ? _USER_DOMAIN_MINIMUM_ : '3600'); ?>"
					   data-readonly="1"
					   data-readonly-text="<?php
							if($c_domain_id_r == "api_id") { echo "This domain is replicated from another master server. Its zone data is maintained there."; }
							elseif(@$current_user["fk_user"] > 0) { echo "Use 'Change Records' on this page to edit this zone."; }
							else { echo "This zone was read from the local name server configuration and is maintained there."; }
					   ?>"
					   data-zone="<?php echo htmlentities($current_user["content"] ?? ''); ?>">
					<noscript><pre style="white-space: pre-wrap;"><?php echo htmlspecialchars($current_user["content"] ?? ''); ?></pre></noscript>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" onClick="dnshttp_userui_viewrecord_close()">Close</button>
				  </div>
				</div>
			  </div>
			</div>
			<script src="./_assets/_js/dnshttp_zone_editor.js"></script>
			<?php } ?>
			
			<?php if(@$_GET["op"] == "changerecord") {  ?>
			<div class="modal" id="cpage_rvsv_item_modal" tabindex="-1" style="<?php if(@$_GET["op"] == "changerecord") { echo "display: block;"; } ?> background: rgba(0,0,0,0.6); ">
			  <div class="modal-dialog"   style="min-width: 90% !important;">
				<div class="modal-content">
				  <div class="modal-header">
					<h5 class="modal-title">Domain Record Change<br /><small>Serial <?php echo htmlspecialchars($current_user["serial_c"] ?? ''); ?>/<?php echo incrementSOASerial($current_user["serial_c"] ?? ''); ?> </small></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onClick="dnshttp_userui_changerecord_close()"></button>
				  </div>
				  <div class="modal-body" id="dns-zone-editor"
					   data-domain="<?php echo htmlentities(strtolower(trim($current_user["domain"] ?? ''))); ?>"
					   data-origin="<?php echo htmlentities(rtrim(strtolower(trim($current_user["domain"] ?? '')), '.').'.'); ?>"
					   data-default-ttl="<?php echo htmlentities(defined("_USER_DOMAIN_MINIMUM_") ? _USER_DOMAIN_MINIMUM_ : '3600'); ?>"
					   data-readonly="<?php echo ($c_domain_id_r == "api_id") ? '1' : '0'; ?>"
					   data-zone="<?php
						$c_serial_raw = trim($current_user["serial_c"] ?? '');
						$c_zone_attr = htmlentities($current_user["content"] ?? '');
						if($c_serial_raw !== '') { $c_zone_attr = preg_replace('/\b'.preg_quote($c_serial_raw, '/').'\b/', incrementSOASerial($c_serial_raw), $c_zone_attr, 1); }
						echo $c_zone_attr;
				  ?>">
					<noscript>The record editor needs JavaScript. Without it the zone data of this domain can only be changed through the API.</noscript>
				  </div>
				  <div class="modal-footer">
					<button type="button" class="btn btn-secondary" onClick="dnshttp_userui_changerecord_close()">Close</button>
					<button type="button" class="btn btn-warning" onClick="dnshttp_userui_changerecord_save()">Save</button>
				  </div>
				</div>
			  </div>
			</div>		
			<!----------------- Record Editor -------------------->
			<!-- The editor itself lives in its own file. It reads the zone out of the
			     data attributes above and hands it back through window.DNSZoneEditor. -->
			<script src="./_assets/_js/dnshttp_zone_editor.js"></script>
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
									  
								  <?php
									// DNSSEC is a property of the zone this server is authoritative for, so it is
									// offered on local (master and user) domains only. A replicated domain gets the
									// signatures of its master transferred along with the rest of the zone data.
									$dnssec_may_toggle = false;
									if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { $dnssec_may_toggle = true; }
									if(@$current_user["fk_user"] > 0 AND @$current_user["fk_user"] == $user->user_id) { $dnssec_may_toggle = true; }
									if($c_domain_id_r != "api_id" AND $dnssec_may_toggle) {
										$dnssec_mode_now   = dnshttp_dnssec_mode();
										$dnssec_schema_now = dnshttp_dnssec_schema($mysql);
								  ?>
									  <br clear="">
									  <br clear="">
									  You can have this zone signed with DNSSEC. The signing itself is done by bind, this only writes the configuration and reads out the DS record for your registrar.<br />
									  <?php if(!$dnssec_schema_now) { ?>
										  <span class="text-danger">The DNSSEC columns are missing from the domain table - reload this page once so the database upgrade can run.</span><br />
										  <button type="button" class="btn btn-success" disabled>Enable DNSSEC</button>
										  <button type="button" class="btn btn-danger" disabled>Disable DNSSEC</button>
									  <?php } elseif($dnssec_mode_now === false AND @$current_user["dnssec"] != 1) { ?>
										  <span class="text-danger"><?php echo htmlspecialchars(dnshttp_dnssec_unavailable_reason() ?? ''); ?></span><br />
										  <button type="button" class="btn btn-success" disabled>Enable DNSSEC</button>
										  <button type="button" class="btn btn-danger" disabled>Disable DNSSEC</button>
									  <?php } elseif(@$current_user["dnssec"] == 1) { ?>
										  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#cpage_dnssec_on_item_modal" disabled>Enable DNSSEC</button>
										  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cpage_dnssec_off_item_modal">Disable DNSSEC</button>
									  <?php } else { ?>
										  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#cpage_dnssec_on_item_modal">Enable DNSSEC</button>
										  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cpage_dnssec_off_item_modal" disabled>Disable DNSSEC</button>
									  <?php } ?>
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
			
				<!----------------- DNSSEC  -------------------->
				<?php if($c_domain_id_r == "api_id") { ?>
					<div class="card card-dark mb-4">
					  <div class="card-header">
						<h3 class="card-title">DNSSEC</h3>
					  </div>
					  <div class="card-body">
						This is a replicated domain. If its master server signs the zone, the signatures are transferred
						along with the rest of the zone data and this server answers with them - there is nothing to
						configure here. DNSSEC is switched on for this domain on the master server that owns it.
					  </div>
					</div>
				<?php } else { ?>
					<div class="card card-dark mb-4">
					  <div class="card-header">
						<h3 class="card-title">DNSSEC</h3>
					  </div>
					  <div class="card-body">
						<?php if(!dnshttp_dnssec_schema($mysql)) { ?>
							<div class="callout callout-warning mb-0">The DNSSEC columns are missing from the domain table. Reload this page once so the database upgrade can run.</div>
						<?php } elseif(@$current_user["dnssec"] != 1) { ?>
							<p class="mb-0">This zone is served <b>unsigned</b>.
							<?php if(dnshttp_dnssec_mode() === false) { echo "DNSSEC is not available on this server: ".htmlspecialchars(dnshttp_dnssec_unavailable_reason() ?? ''); } ?>
							</p>
							<?php if(trim((string)@$current_user["dnssec_status"]) != "") { ?>
								<p class="mt-2 mb-0"><small><b>Last message:</b> <?php echo nl2br(htmlspecialchars($current_user["dnssec_status"] ?? '')); ?></small></p>
							<?php } ?>
						<?php } else { ?>
							<table class="table table-sm table-borderless mb-3">
								<tr>
									<td style="width: 180px;"><b>State</b></td>
									<td>
										<?php if(trim((string)@$current_user["dnssec_ds"]) != "" OR trim((string)@$current_user["dnssec_dnskey"]) != "") { ?>
											<span class="badge text-bg-success">Signed</span>
										<?php } else { ?>
											<span class="badge text-bg-warning">Waiting for key material</span>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td><b>Configuration</b></td>
									<td><?php
										$dnssec_mode_shown = trim((string)@$current_user["dnssec_mode"]);
										if($dnssec_mode_shown === "policy") { echo "dnssec-policy \"".htmlspecialchars(_BIND_DNSSEC_POLICY_)."\" (bind 9.16 and newer)"; }
										elseif($dnssec_mode_shown === "legacy") { echo "auto-dnssec maintain (bind up to 9.18)"; }
										else { echo "Not written yet - the background worker decides this on its next run."; }
									?></td>
								</tr>
								<tr>
									<td><b>Last check</b></td>
									<td><?php echo htmlspecialchars((string)(@$current_user["dnssec_update"] ?? '')); ?></td>
								</tr>
							</table>
							<?php if(trim((string)@$current_user["dnssec_status"]) != "") { ?>
								<div class="callout callout-info mb-3"><?php echo nl2br(htmlspecialchars($current_user["dnssec_status"] ?? '')); ?></div>
							<?php } ?>
							<?php if(trim((string)@$current_user["dnssec_ds"]) != "" OR trim((string)@$current_user["dnssec_dnskey"]) != "") { ?>
								<div class="callout callout-warning">
									<b>One last step, and it happens at your registrar - not here.</b><br />
									This zone is signed, but no resolver checks the signatures until the registry one level up
									vouches for the key. Until that is done the domain resolves exactly as it did before, with
									no DNSSEC protection.<br /><br />
									Your registrar's form asks for <b>one</b> of the two formats below - never both. Which one
									depends on the registry, so go by the field labels you see:
									<ul class="mb-0">
										<li>Fields called <b>Key Tag / Algorithm / Digest Type / Digest</b> &rarr; use the <b>DS</b> record.</li>
										<li>Fields called <b>Flags / Protocol / Algorithm / Public Key</b> &rarr; use the <b>DNSKEY</b> record. This is what <b>.de</b> (DENIC) uses.</li>
									</ul>
								</div>

								<?php if(trim((string)@$current_user["dnssec_dnskey"]) != "") { ?>
									<b>DNSKEY &mdash; for .de and other registries that provision by key</b>
									<?php
										// Split into the individual fields: a registrar panel almost never accepts the
										// whole record, it wants one input box per value.
										foreach(preg_split('/\r\n|\r|\n/', (string)$current_user["dnssec_dnskey"]) as $dnskeyline) {
											$dnskeyparts = dnshttp_dnssec_dnskey_parts($dnskeyline);
											if(!$dnskeyparts) { continue; }
									?>
										<table class="table table-sm table-bordered mt-2 mb-2">
											<tr><td style="width: 140px;">Flags</td><td><code><?php echo htmlspecialchars($dnskeyparts["flags"]); ?></code></td></tr>
											<tr><td>Protocol</td><td><code><?php echo htmlspecialchars($dnskeyparts["protocol"]); ?></code></td></tr>
											<tr><td>Algorithm</td><td><code><?php echo htmlspecialchars($dnskeyparts["algorithm"]); ?></code> &ndash; <?php echo htmlspecialchars(dnshttp_dnssec_algorithm_name($dnskeyparts["algorithm"])); ?></td></tr>
											<tr><td>Public Key</td><td style="word-break: break-all;"><code><?php echo htmlspecialchars($dnskeyparts["key"]); ?></code></td></tr>
										</table>
									<?php } ?>
									<small class="text-muted">If the form wants the whole record on one line instead:</small>
									<pre class="p-2 mt-1 mb-3" style="background: #1e1e1e; color: #d4d4d4; white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($current_user["dnssec_dnskey"] ?? ''); ?></pre>
								<?php } ?>

								<?php if(trim((string)@$current_user["dnssec_ds"]) != "") { ?>
									<b>DS &mdash; for .com, .net, .org and most other registries</b>
									<pre class="p-2 mt-2 mb-0" style="background: #1e1e1e; color: #d4d4d4; white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($current_user["dnssec_ds"] ?? ''); ?></pre>
									<small class="text-muted">Read left to right, the numbers after "DS" are Key Tag, Algorithm and Digest Type - the rest is the Digest.</small>
								<?php } ?>
							<?php } ?>
						<?php } ?>
					  </div>
					</div>
				<?php } ?>

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
												// Replicated rows have no DNSSEC column of their own - they inherit whatever
												// their master signed, so the badge only ever appears on local domains.
												if(@$value["dnssec"] == "1") { echo '<span class="badge text-bg-info" title="DNSSEC signing enabled for this zone">DS</span> '; }
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
													// Replicated rows have no DNSSEC column of their own - they inherit whatever
													// their master signed, so the badge only ever appears on local domains.
													if(@$value["dnssec"] == "1") { echo '<span class="badge text-bg-info" title="DNSSEC signing enabled for this zone">DS</span> '; }
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