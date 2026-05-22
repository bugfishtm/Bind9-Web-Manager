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
		Search Operation
	*************************************************************************/
	$output = '<div class="alert alert-primary" role="alert">You have not started a search operation yet.</div>';
	if(isset($_GET["search_operation"])) {
		if(isset($_GET["search"])) {
			if(strlen(trim($_GET["search"] ?? '') ?? '') > 0) {
				$output = '';
				$output = $output."<table class='table table-striped'>";
				$output = $output."<thead>";
					$output = $output."<tr>";
						$output = $output."<th>";
							$output = $output."Domain";
						$output = $output."</th>";
						$output = $output."<th>";
							$output = $output."Source";
						$output = $output."</th>";
						$output = $output."<th>";
							$output = $output."Status";
						$output = $output."</th>";
						$output = $output."<th>";
							$output = $output."Inspect";
						$output = $output."</th>";
					$output = $output."</tr>";
				$output = $output."</thead>";
				$has_one_entry = false;
				$bindnew = array();
				$bindnew[0]["value"] = "%".strtolower(trim(@$_GET["search"] ?? '') ?? '')."%";			

				 if($permsobj->hasPerm($user->user_id, "domain_admin") OR $user->user_rank == 0) {
					$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE TRIM(LOWER(domain)) LIKE ? ORDER BY domain ASC", true, $bindnew); 
					$res2 = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE TRIM(LOWER(domain)) LIKE ? ORDER BY domain ASC", true, $bindnew); 
				 } else {
					$res = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE TRIM(LOWER(domain)) LIKE ? AND fk_user = '".$user->user_id."' ORDER BY domain ASC", true, $bindnew); 
					$res2 = array(); 
				 }
				
				if(!is_array($res)) { $res = array(); }
				if(!is_array($res2)) { $res2 = array(); }
				$merged = array_merge($res, $res2);
				if(is_array($merged)) { 
					foreach ($merged AS $key => $value) { 
						$has_one_entry = true;
						$output = $output.'<tr class="align-middle">';
							$output = $output."<td>";
								$output = $output. htmlspecialchars($value["domain"] ?? '')." ";

								
							$output = $output."</td>";
							$output = $output."<td>";
								if(@$value["fk_server"] > 0) {
									$output = $output. 'Slave at '.' #'.$value["fk_server"];
								} elseif(@$value["fk_user"] > 0) {
									$output = $output. 'User'.' #'.$value["fk_user"];
								} else {
									$output = $output. 'Master';
								}
							$output = $output."</td>";
							$output = $output."<td>";
								if($value["registered"] == "1") { $output = $output. '<span class="badge text-bg-success" title="Registered and Active in Bind9">R</span> '; }
								if($value["registered"] != "1") { $output = $output. '<span class="badge text-bg-danger" title="Not active for queries">NR</span> '; }
								if($value["conflict"] == "1") { $output = $output. '<span class="badge text-bg-warning" title="Conflicts">C</span> '; }
								if($value["preferred"] == "1") { $output = $output. '<span class="badge text-bg-primary" title="Prefered which may solvesn conflicts">P</span> '; }
								if(@$value["set_no_replicate"] == "1") { $output = $output. '<span class="badge text-bg-danger" title="Replication to other servers Disabled">RD</span> '; }
								if($value["oldzonefallback"] == "1") { $output = $output. '<span class="badge text-bg-danger" title="Fallback to previous stored zone, new zone data seems invalid...">OZF</span> '; }
								if($value["okonce"] == "0") { $output = $output. '<span class="badge text-bg-warning" title="This domain has never been valid before...">NV</span> '; }
								if($value["zonecheck"] == "0") { $output = $output. '<span class="badge text-bg-danger" title="Zone invalidated...">ZE</span> '; }
							$output = $output."</td>";
							$output = $output."<td>";

							 if(@$value["fk_server"] > 0) { $output = $output. "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&api_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; }
									else { $output = $output. "<a class='btn btn-dark btn-sm' title='Inspect this specific Domain' href='./?site=domain_list&bind_id=".trim(strtolower($value["id"] ?? '') ?? '')."'><i class=\"bi bi-search\"></i></a>"; } 
																		
							$output = $output."</td>";
						
						$output = $output.'</tr>';
					}
				} 
				
				$output = $output."</table>";
				if(!$has_one_entry) { 
					$output = '<div class="alert alert-warning" role="alert">No domain has been found.</div>';
				}					
				
			} else {
			}		
		} else {
		}			
	} 
?>

<div class="modal" tabindex="-1" id="dnshttp_search_overlay" style=" <?php if(!is_string(@$_GET["search"] ?? '') OR strlen(@$_GET["search"] ?? '') <= 0) { echo "display: none;"; } else { echo "display: block;"; }  ?>">
  <div class="modal-dialog" style="min-width: 90% !important;">
	<div class="modal-content">
	  <div class="modal-header">
		<h5 class="modal-title">Search Domains</h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onClick="dnshttp_ls_close()"></button>
	  </div>
	  <div class="modal-body">

		<form method="get">
			<div class="input-group mb-1">
			  <div class="form-floating">
				<input id="dnshttp_search_overlay_input" type="text" name="search" maxlength="74" class="form-control" placeholder="" autocomplete="off" value="<?php echo htmlentities(@$_GET["search"] ?? ''); ?>" />
				<label for="loginEmail">Enter Search String</label>
			  </div>
			  <div class="input-group-text">
				<span class="bi bi-search"></span>
			  </div>
			</div>	

			<input type="hidden"	name="site"			value="<?php echo @$_GET["site"]; ?>">
			<input type="hidden"	name="id"			value="<?php if(is_numeric(@$_GET["id"]))  { echo @$_GET["id"]; } ?>">
			<input type="hidden"	name="bind_id"		value="<?php if(is_numeric(@$_GET["bind_id"]))  { echo @$_GET["bind_id"]; } ?>">
			<input type="hidden"	name="api_id"		value="<?php if(is_numeric(@$_GET["api_id"]))  { echo @$_GET["api_id"]; } ?>">
			<input type="hidden"	name="csrf"			value="<?php echo $csrf->get(); ?>">
			<button type="submit"  name="search_operation" class="btn btn-primary">Start Search</button> 	
		</form>
			
		  <div id="" class="tablecard">
			  <div id="dnshttp_search_overlay_result" class="scrolltableinit" style="max-height: 400px; overflow-y: scroll;">
				<?php echo $output; ?>
			  </div>
		  </div>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onClick="dnshttp_ls_close()">Close</button>
	  </div>
	</div>
  </div>
</div>	