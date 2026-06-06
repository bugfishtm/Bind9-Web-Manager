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
		Export Table
	*************************************************************************/
	if(@$_GET["op"] == "export") {
		if($csrf->check($_GET['csrf'])) {
			
			
			$mysqli = $mysql->mysqlcon;
			$mysqli->set_charset('utf8mb4');
			$table = _TABLE_LOG_;
			$filename = $table . '_' . date('Y-m-d_H-i-s') . '.csv';

			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Cache-Control: no-cache, no-store, must-revalidate');

			set_time_limit(0);
			ini_set('memory_limit', '-1');

			$output = fopen('php://output', 'w');

			$result = $mysqli->query("SELECT * FROM `$table`");

			$fields = $result->fetch_fields();
			$columns = array_map(fn($f) => $f->name, $fields);
			fputcsv($output, $columns);

			while ($row = $result->fetch_assoc()) {
				fputcsv($output, $row);
			}

			fclose($output);
			$result->free();
		
			exit();
			
		} else { 
			x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); 
			Header("Location: ./?site=".@$_GET["site"].""); exit();
		}
	}	
	
	/*************************************************************************
		Export Table
	*************************************************************************/
	if(@$_GET["op"] == "clear") {
		if($csrf->check($_GET['csrf'])) {
			$mysql->query("TRUNCATE TABLE "._TABLE_LOG_.";");
			$mysql->query("TRUNCATE TABLE "._TABLE_LOG_MYSQL_.";");
			x_eventBoxPrep("The table has been cleared!", "ok", _COOKIES_); 
			Header("Location: ./?site=".@$_GET["site"].""); exit();			
		} else { 
			x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); 
			Header("Location: ./?site=".@$_GET["site"].""); exit();
		}
	}	
	
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Debugging");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>
			
	<!----------------- Javascript for Deletion -------------------->
	<script>
	
		function dnshttp_ui_clear() {
			window.location.href = "./?site=debugging&csrf=<?php echo $csrf->get(); ?>&op=clear";
		}
		function dnshttp_ui_export() {
			window.location.href = "./?site=debugging&csrf=<?php echo $csrf->get(); ?>&op=export";
		}
		
	</script>
	
	<!----------------- Modal -------------------->
	<div class="modal" id="cpage_clearlog_item_modal" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title">Clear Logging Table</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		  </div>
		  <div class="modal-body">
			<p>Are you sure you want to clear the activity log table? This will permanently remove all log entries for servers, cronjobs, and domains. The operation itself is safe, but all historical activity data will be lost and cannot be recovered.</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-danger" onClick="dnshttp_ui_clear()">Clear Table</button>
		  </div>
		</div>
	  </div>
	</div>

	<div class="modal" id="cpage_export_item_modal" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title">Export Logging Table</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		  </div>
		  <div class="modal-body">
			<p>Are you sure you want to export the entire activity log? Depending on the table size, this may take some time. The data will be downloaded as a CSV file. If the export times out, the table is likely too large for PHP to handle — in that case, please export manually using a MySQL client.</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-warning" onClick="dnshttp_ui_export()">Export Table</button>
		  </div>
		</div>
	  </div>
	</div>

	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3">On this page you can view debugging information and logging entries for cronjobs and general system activity. You can clear the log table when it becomes too large, and export entries for complete offline review.</div>

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


	<!----------------- Operations  -------------------->
	<h4>Operations <br /><small style="font-size: 12px;">Use these buttons with caution, every button will return a confirmation and explain a bit more about what this button does.</small></h4>

	<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cpage_clearlog_item_modal">
	  Clear Logging Table
	</button>
	<button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#cpage_export_item_modal">
	  Export Logging Table
	</button>
	
	<br clear="left"><br clear="left">
		 
	<!----------------- Table Element  -------------------->
	<h4>Cronjob: Sync.php <br /><small style="font-size: 12px;"> Latest 10 Activities</small></h4>
	<div class="card mb-4 tablecard">
	  <!-- /.card-header -->
	  <div class="card-body p-0 ">
		  <div class="scrolltableinit">
			<table class="table table-striped">
			  <thead>
				<tr>
				  <th style="width: 180px">Date</th>
				  <th style="width: 100px">Type</th>
				  <th>Message</th>
				</tr>
			  </thead>
			  <tbody>
				<?php		  
					$res = $mysql->select("SELECT * FROM "._TABLE_LOG_." WHERE ref = 'cron_sync' ORDER BY id DESC LIMIT 10", true); 
					if(is_array($res)) { 
						foreach ($res AS $key => $value) { 	
							?>
								<tr class="align-middle">
								  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["creation"] ?? ''); ?></td>
								  <?php if($value["type"] == 1) { ?>
									<td><span class="badge text-bg-danger">ERROR</span></td>
								  <?php } else { ?>
									<td><span class="badge text-bg-dark">INFO</span></td>
								  <?php } ?>
								  <td class="bg-black" style="background: #242424 !important; color: white;"><button type="button" class="btn btn-dark" onClick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? '' : 'none';">Show/Hide</button><section style="display: none;"><?php echo $value["message"]; ?></section></td>
								  
								  
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
		 
	<!----------------- Table Element  -------------------->
	<h4>Cronjob: Blacklist.php <br /><small style="font-size: 12px;"> Latest 10 Activities</small></h4>
	<div class="card mb-4 tablecard">
	  <!-- /.card-header -->
	  <div class="card-body p-0 ">
		  <div class="scrolltableinit">
			<table class="table table-striped">
			  <thead>
				<tr>
				  <th style="width: 180px">Date</th>
				  <th style="width: 100px">Type</th>
				  <th>Message</th>
				</tr>
			  </thead>
			  <tbody>
				<?php		  
					$res = $mysql->select("SELECT * FROM "._TABLE_LOG_." WHERE ref = 'cron_blacklist' ORDER BY id DESC LIMIT 10", true); 
					if(is_array($res)) { 
						foreach ($res AS $key => $value) { 	
							?>
								<tr class="align-middle">
								  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["creation"] ?? ''); ?></td>
								  <?php if($value["type"] == 1) { ?>
									<td><span class="badge text-bg-danger">ERROR</span></td>
								  <?php } else { ?>
									<td><span class="badge text-bg-dark">INFO</span></td>
								  <?php } ?>
								  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["message"] ?? ''); ?></td>
								  
								  
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

	<!----------------- Table Element  -------------------->
	<h4>Settings  <br /><small style="font-size: 12px;"> This settings can only be changed by manually editing './_data/settings.php' on your webserver. Be very cautious when you are doing that.</small></h4>
	<div class="card mb-4 tablecard">
	  <!-- /.card-header -->
	  <div class="card-body p-0 ">
		  <div class="scrolltableinit">
			<table class="table table-striped">
			  <thead>
				<tr>
				  <th style="width: 180px">Constant</th>
				  <th>Description</th>
				  <th style="width: 100px">Value</th>
				</tr>
			  </thead>
			  <tbody>
				<tr class="align-middle">
					<td>_TITLE_</td>
					<td>Website Title</td>
					<td><?php echo htmlspecialchars(_TITLE_ ?? ''); ?></td>
				</tr>	
				<tr class="align-middle">
					<td>_HELP_</td>
					<td>Help URL</td>
					<td><?php echo htmlspecialchars(_HELP_ ?? ''); ?></td>
				</tr>			  
				<tr class="align-middle">
					<td>_DNSHTTP_LOGO_</td>
					<td>Logo Relative URL</td>
					<td><?php echo htmlspecialchars(_DNSHTTP_LOGO_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_DNSHTTP_LOGIN_BG_</td>
					<td>Login Background Relative URL</td>
					<td><?php echo htmlspecialchars(_DNSHTTP_LOGIN_BG_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_DNSHTTP_FAVICON_</td>
					<td>Favicon Relative URL</td>
					<td><?php echo htmlspecialchars(_DNSHTTP_FAVICON_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_FOOTER_</td>
					<td>Footer Text</td>
					<td><?php echo htmlspecialchars(_FOOTER_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_COOKIEBANNER_TEXT_</td>
					<td>Cookiebanner Text</td>
					<td><?php echo htmlspecialchars(_COOKIEBANNER_TEXT_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_IMPRESSUM_</td>
					<td>Impressum URL</td>
					<td><?php echo htmlspecialchars(_IMPRESSUM_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_FILE_</td>
					<td>Primary bind9 config file listing local zones (named.conf.local).</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_FILE_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_FILE_2_</td>
					<td>Secondary bind9 config file for default zones.</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_FILE_2_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_</td>
					<td>Characters to strip from the start of zone filenames to extract the domain name.</td>
					<td><?php echo htmlspecialchars(_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_</td>
					<td>Characters to strip from the end of zone filenames.</td>
					<td><?php echo htmlspecialchars(_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_FILES_FOLDER_FETCH_</td>
					<td>Folder to scan for zone files. Set to /etc/bind/zones/ for ISPConfig, false to disable.</td>
					<td><?php echo htmlspecialchars(_CRON_FILES_FOLDER_FETCH_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_FILE_REWRITE_</td>
					<td>Set to true when using Webmin/Virtualmin to rewrite the zone config file automatically.</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_FILE_REWRITE_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_USER_</td>
					<td>Linux user that owns generated zone files.</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_LIB_USER_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_GROUP_</td>
					<td>Linux group that owns generated zone files.</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_LIB_GROUP_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_CODE_</td>
					<td>chmod permission number for generated zone files (default 770).</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_LIB_CODE_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_ENDING_</td>
					<td>File extension for generated zone files (default .dnshttp).</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_LIB_ENDING_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_BIND_SERVICE_NAME_</td>
					<td>System service name of your nameserver (bind9 or named).</td>
					<td><?php echo htmlspecialchars(_BIND_SERVICE_NAME_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_BIND_CHECKZONE_COMMAND_</td>
					<td>Path to named-checkzone binary.</td>
					<td><?php echo htmlspecialchars(_BIND_CHECKZONE_COMMAND_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_BIND_COMPILEZONE_COMMAND_</td>
					<td>Path to named-compilezone binary.</td>
					<td><?php echo htmlspecialchars(_BIND_COMPILEZONE_COMMAND_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_</td>
					<td>Directory where generated DNS zone files are stored.</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_LIB_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_CONFNAME_</td>
					<td>Path to the main named.conf file.</td>
					<td><?php echo htmlspecialchars(_CRON_BIND_CONFNAME_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_COOKIES_</td>
					<td>Default Prefix for Session Cookies</td>
					<td><?php echo htmlspecialchars(_COOKIES_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_IP_BLACKLIST_DAILY_OP_LIMIT_</td>
					<td>Default Limit Failures of an IP Adress with Token or Login before Block</td>
					<td><?php echo htmlspecialchars(_IP_BLACKLIST_DAILY_OP_LIMIT_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_CSRF_VALID_LIMIT_TIME_</td>
					<td>Default time in seconds CSRF Codes are Valid</td>
					<td><?php echo htmlspecialchars(_CSRF_VALID_LIMIT_TIME_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_USER_AUTOBLOCK_</td>
					<td>Default Limit for Wrong User Passwords in a Row before Auto-Block</td>
					<td><?php echo htmlspecialchars(_USER_AUTOBLOCK_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_SERVER_HOSTNAME_</td>
					<td>Default Entry for SOA Record: Primary Hostname (Master Domains)</td>
					<td><?php echo htmlspecialchars(_SERVER_HOSTNAME_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_EXPIRE_</td>
					<td>Default Entry for SOA Record: Expiry Time</td>
					<td><?php echo htmlspecialchars(_USER_DOMAIN_EXPIRE_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_RETRY_</td>
					<td>Default Entry for SOA Record: Retry Rate</td>
					<td><?php echo htmlspecialchars(_USER_DOMAIN_RETRY_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_REFRESH_</td>
					<td>Default Entry for SOA Record: Refresh Rate</td>
					<td><?php echo htmlspecialchars(_USER_DOMAIN_REFRESH_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_MINIMUM_</td>
					<td>Default Entry for SOA Record: Minimum TTL</td>
					<td><?php echo htmlspecialchars(_USER_DOMAIN_MINIMUM_ ?? ''); ?></td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_MAIL_</td>
					<td>Default Entry for SOA Record: Postmaster Mail (Responsible Party)</td>
					<td><?php echo htmlspecialchars(_USER_DOMAIN_MAIL_ ?? ''); ?></td>
				</tr>
			  </tbody>
			</table>
		  </div>
	  </div>
	</div>		

	<!----------------- Table Element  -------------------->
	<h4>Logging: MySQL  <br /><small style="font-size: 12px;"> Latest 50 Errors</small></h4>
	<div class="card mb-4 tablecard">
	  <!-- /.card-header -->
	  <div class="card-body p-0 ">
		  <div class="scrolltableinit">
			<table class="table table-striped">
			  <thead>
				<tr>
				  <th style="width: 180px">Date</th>
				  <th>Message</th>
				</tr>
			  </thead>
			  <tbody>
				<?php		  
					$res = $mysql->select("SELECT * FROM "._TABLE_LOG_MYSQL_." ORDER BY id DESC LIMIT 50", true); 
					if(is_array($res)) { 
						foreach ($res AS $key => $value) { 	
							?>
								<tr class="align-middle">
								  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["creation"] ?? ''); ?></td>
								  <td class="pre_before_ip_getter">
									<?php
										echo "Initial Query<br />";
										echo "<textarea style='width: 100%;max-width:100%;background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;min-width: 100%;min-height: 20px;' readonly>".htmlspecialchars($value["init"] ?? '')." </textarea><br />";
										echo "Exception<br />";
										echo "<textarea style='width: 100%;max-width:100%;background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;min-width: 100%;min-height: 20px;' readonly>".htmlspecialchars($value["exception"] ?? '')."</textarea><br />";
										echo "SQL Error<br />";
										echo "<textarea style='width: 100%;max-width:100%;background: rgba(0,0,0,0.9); color: white; width: 100%;max-width: 100%;min-width: 100%;min-height: 20px;' readonly>".htmlspecialchars($value["sqlerror"] ?? '')."</textarea><br />";								  
									?>					  
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





		 
	<?php
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");