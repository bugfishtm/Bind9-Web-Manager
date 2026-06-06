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
		IP BLacklist Delete IP Operation
	*************************************************************************/
	if(is_numeric(@$_GET["deleteid"])) {
		if($csrf->check($_GET['csrf'])) {
			$current_delete_item = $mysql->select("SELECT * FROM "._TABLE_IPBL_." WHERE id = \"".$_GET["deleteid"]."\";", false);
			$mysql->query("DELETE FROM "._TABLE_IPBL_." WHERE id = \"".$_GET["deleteid"]."\";");
			x_eventBoxPrep("The IP-Adress has been deleted from the blacklist.", "ok", _COOKIES_);
		} else { 
			x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); 
		}
	}
	
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "IP-Blacklist");
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
			window.location.href = "./?site=blacklist&csrf=<?php echo $csrf->get(); ?>&deleteid="+current_item_id+"";
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
			<p>Do you really want to delete the choosen IP-Blacklist entry: '<span id="cpage_delete_item_modal_ip" style="font-weight: bold;"></span>'?</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-danger" onClick="dnshttp_cpage_delete_item_confirm_exec()">Delete</button>
		  </div>
		</div>
	  </div>
	</div>

	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3">The IP addresses listed below are blocked from accessing this web interface, including all API requests. To automatically reset blocked IPs, configure ./_cronjob/daily.php as a periodic task. The blacklist operation limit can be adjusted via the _IP_BLACKLIST_DAILY_OP_LIMIT_ setting in settings.php. IPs are blocked after exceeding the configured threshold of token or login failures.</div>

	<!----------------- Table Element  -------------------->
	<div class="card mb-4 tablecard">
	  <!-- /.card-header -->
	  <div class="card-body p-0 ">
		  <div class="scrolltableinit">
			<table class="table table-striped">
			  <thead>
				<tr>
				  <th>IP</th>
				  <th style="width: 180px">Failures</th>
				  <th style="width: 180px">Status</th>
				  <th style="width: 40px">Deletetion</th>
				</tr>
			  </thead>
			  <tbody>
				<?php		  
					$res = $mysql->select("SELECT * FROM "._TABLE_IPBL_." ORDER BY id DESC", true); 
					if(is_array($res)) { 
						foreach ($res AS $key => $value) { 	
							?>
								<tr class="align-middle">
								  <td class="pre_before_ip_getter"><?php echo htmlspecialchars($value["ip_adr"] ?? ''); ?></td>
								  <td><?php echo htmlspecialchars($value["fail"] ?? ''); ?> / <?php echo _IP_BLACKLIST_DAILY_OP_LIMIT_; ?></td>
								  <?php if($value["fail"] >= _IP_BLACKLIST_DAILY_OP_LIMIT_) { ?>
									<td><span class="badge text-bg-warning" title="This IP is not able to access this page or the API Interface.">BLOCKED</span></td>
								  <?php } else { ?>
									<td><span class="badge text-bg-success" title="This is is registered, but not yet blocked from interacting with this page or API Interface.">FREE</span></td>
								  <?php } ?>
								  <td>
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
	</div>
		 
	<?php
	/*************************************************************************
		Include Footer
	*************************************************************************/
	require_once("./_template/tpl_search.php");
	require_once("./_default/default_footer.php");