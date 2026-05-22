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
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Dashboard");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>
	
	<?php if($permsobj->hasPerm($user->user_id, "system") OR $permsobj->hasPerm($user->user_id, "domain_admin") OR $permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { ?>
	<?php
		$res = $mysql->select("SELECT * FROM "._TABLE_LOG_." WHERE ref = 'cron_sync' ORDER BY id DESC LIMIT 1", false); 
		if(is_array($res)) { 
			$lastRun = new DateTime($res['creation']); // adjust column name
			$now = new DateTime();
			$diff = $now->diff($lastRun);
			$minutesAgo = ($diff->h * 60) + $diff->i;
			if ($minutesAgo > 180) {
				?><div class="callout callout-danger mb-3">The cronjob has not executed in the last 3 hours. Please verify the cronjob configuration on your server as outlined in the Bind9 Web Manager documentation.</div><?php
			} else {
				?><div class="callout callout-success mb-3">The cronjob seems to be fine.</div><?php
			}
			
		} else {
			?><div class="callout callout-danger mb-3">The cronjob has not executed in the last 3 hours. Please verify the cronjob configuration on your server as outlined in the Bind9 Web Manager documentation.</div> <?php
		}
	?>
	<?php } ?>
	
	<?php if($permsobj->hasPerm($user->user_id, "system") OR $permsobj->hasPerm($user->user_id, "domain_conflicts") OR $permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { ?>
		<?php
			$count = $mysql->select("SELECT id FROM "._TABLE_CONFLICT_." WHERE solved = '0'", true);
			if(is_array($count)) { $count = count($count); } else { $count = 0; } 
			
			$count1 = $mysql->select("SELECT id FROM "._TABLE_CONFLICT_." WHERE solved <> '0'", true);
			if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; } 
		if($count > 0) {
			?><div class="callout callout-warning mb-3">You have <?php echo $count; ?> unsolved domain conflicts! (This info may be outdated, as the conflicted domain list only updated on cronjob execution)</div> <?php
		}
		
	} ?>

	<div class="row">
		<div class="col-md-12">
			<h4>Welcome, <b><?php echo htmlspecialchars($user->user_name ?? ''); ?></b>!</h4>
			<div class="card mb-4 tablecard">
				<div class="card-body">
				  <p>
					Welcome to the Bind9 Web Manager (DNSHTTP) Dashboard. This is an open-source web interface for managing BIND9 DNS servers. From here you can manage your domains and DNS records, configure master and slave replication servers, monitor replication status, and control user access and permissions. The project is built around making BIND9 replication over HTTP straightforward to set up and operate. Kindly, Bugfish.			  
				  </p>
				  <a href="https://bugfishtm.github.io" target="_blank" rel="noopener" class="btn btn-dark">Visit my Websites</a>
				  <a href="https://bugfishtm.github.io/Bind9-Web-Manager" target="_blank" rel="noopener" class="btn btn-dark">Documentation</a>
				  <a href="https://www.youtube.com/playlist?list=PL6npOHuBGrpChSvani3MESZnzuKwwxz4o" target="_blank" rel="noopener" class="btn btn-dark">Videos</a>
				  <a href="https://github.com/bugfishtm/Bind9-Web-Manager" target="_blank" rel="noopener" class="btn btn-dark">GitHub</a>
				</div>	
			</div>	
		</div>	
	</div>	



	<?php if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) { ?>
	<!----------------- Table Element  -------------------->
	<div class="row">
		<div class="col-md-12">
			<h4>Domain Status</h4>
			<div class="card mb-4 tablecard">
			  <!-- /.card-header -->
			  <div class="card-body p-0">
				  <div class="scrolltableinit">
					<table class="table table-striped">
					  <thead>
						<tr>
						  <th>Description</th>
						  <th>Value</th>
						</tr>
					  </thead>
					  <tbody>
					  
						<tr class="align-middle">
						  <td>Zone Errors (Total)</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_BIND_." WHERE zonecheck = 0", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									$count2 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_API_." WHERE zonecheck = 0 ", true);
									if(is_array($count2)) { $count2 = count($count2); } else { $count2 = 0; }  
									echo $count1 + $count2;
								?>			  
						  </td>
						</tr>
						<tr class="align-middle">
						  <td>Zone Errors (Active)</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_BIND_." WHERE registered = 1 AND zonecheck = 0 ", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									$count2 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_API_." WHERE registered = 1 AND zonecheck = 0 ", true);
									if(is_array($count2)) { $count2 = count($count2); } else { $count2 = 0; }  
									echo $count1 + $count2;
								?>			  
						  </td>
						</tr>
					  
						<tr class="align-middle">
						  <td>Master Domains (Total)</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_BIND_."", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									echo $count1;
								?>			  
						  </td>
						</tr>
						<tr class="align-middle">
						  <td>Master Domains (Registered)</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_BIND_." WHERE registered = 1", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									echo $count1;
								?>			  
						  </td>
						</tr>
						<tr class="align-middle">
						  <td>Slave Domains (Total)</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_API_."", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									echo $count1;
								?>			  
						  </td>
						</tr>
						<tr class="align-middle">
						  <td>Master Domains (Registered)</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_API_." WHERE registered = 1", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									echo $count1;
								?>			  
						  </td>
						</tr>
						
						<tr class="align-middle">
						  <td>Registered Domains (Total)</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_REG_."", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									echo $count1;
								?>			  
						  </td>
						</tr>
						<tr class="align-middle">
						  <td>Resolved Conflicts</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_CONFLICT_." WHERE solved <> '0'", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									echo $count1;
								?>			  
						  </td>
						</tr>
						<tr class="align-middle">
						  <td>Unsolved Conflicts</td>
						  <td>
								<?php		  
									$count1 = $mysql->select("SELECT id FROM "._TABLE_CONFLICT_." WHERE solved = '' or solved IS NULL or solved = '0'", true);
									if(is_array($count1)) { $count1 = count($count1); } else { $count1 = 0; }  
									echo $count1;
								?>			  
						  </td>
						</tr>
						
					  </tbody>
					</table>
				  </div>
			  </div>
			</div>	
		</div>	
	</div>	
	<?php } ?>



	<?php if($permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { ?>
	<!----------------- Table Element  -------------------->
	<div class="row">
		<div class="col-md-12">
			<h4>Server Status</h4>
			<div class="card mb-4 tablecard">
			  <!-- /.card-header -->
			  <div class="card-body p-0">
				  <div class="scrolltableinit">
					<table class="table table-striped">
					  <thead>
						<tr>
						  <th style="width: 60px">#</th>
						  <th>Status</th>
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
										  <td><?php if($value["enabled"] == "1") { if($value["apiok"] == "1") { echo '<span class="badge text-bg-success" title="This Server is reachable and running the DNSHTTP version < 4.0.0">Legacy</span>'; } elseif($value["apiok"] == "2") { echo '<span class="badge text-bg-success" title="This Server is reachable and running the DNSHTTP version >= 4.0.0">Compressed</span>'; } else { echo '<span class="badge text-bg-danger" title="This server has critical connection errors.">Error</span>'; } } else { echo '<span class="badge text-bg-danger" title="This server has been disabled by an administrator.">Disabled</span>'; } ?>
											  <span class="badge text-bg-dark">Domains: <?php echo htmlspecialchars($value["domains"] ?? ''); ?></span></td>
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
	<?php } ?>
	
	<?php if($permsobj->hasPerm($user->user_id, "users") OR $user->user_rank == 0) { ?>
	<!----------------- Table Element  -------------------->
	<div class="row">
		<div class="col-md-12">
			<h4>Blocked Users</h4>
			<div class="card mb-4 tablecard">
			  <!-- /.card-header -->
			  <div class="card-body p-0">
				  <div class="scrolltableinit">
					<table class="table table-striped">
					  <thead>
						<tr>
						  <th style="width: 60px">#</th>
						  <th>Username</th>
						</tr>
					  </thead>
					  <tbody>
						<?php		  
							$res = $mysql->select("SELECT * FROM "._TABLE_USER_." WHERE user_blocked = 1 OR block_auto = 1 ORDER BY id DESC", true); 
							if(is_array($res)) { 
								foreach ($res AS $key => $value) { 	
									?>
										<tr class="align-middle">
										  <td><?php echo htmlspecialchars($value["id"] ?? ''); ?></td>
										  <td><?php echo htmlspecialchars($value["user_name"] ?? ''); ?></td>
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
	<?php } ?>
	
	<?php if($permsobj->hasPerm($user->user_id, "system") OR $user->user_rank == 0) { ?>
	<!----------------- Table Element  -------------------->
	<div class="row">
		<div class="col-md-12">
			<h4>Blocked IPs</h4>
			<div class="card mb-4 tablecard">
			  <!-- /.card-header -->
			  <div class="card-body p-0">
				  <div class="scrolltableinit">
					<table class="table table-striped">
					  <thead>
						<tr>
						  <th>IP</th>
						</tr>
					  </thead>
					  <tbody>
						<?php		  
							$res = $mysql->select("SELECT * FROM "._TABLE_IPBL_." WHERE fail >= "._IP_BLACKLIST_DAILY_OP_LIMIT_." ORDER BY id DESC", true); 
							if(is_array($res)) { 
								foreach ($res AS $key => $value) { 	
									?>
										<tr class="align-middle">
										  <td><?php echo htmlspecialchars($value["ip_adr"] ?? ''); ?></td>
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
	<?php } ?>

	<?php
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");