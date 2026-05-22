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
		Helper Function
	*************************************************************************/
	function generateSoaSerial(): int
	{
		$base = (int) date('Ymd') * 100 + 1;  // today formatted as YYYYMMDD01
		return $base;
	}
			
	/*************************************************************************
		Create a new Server
	*************************************************************************/	
	if(isset($_POST["domain"])) {
		if(!$csrf->check($_POST["csrf"])) { x_eventBoxPrep("CSRF Error - The form has expired or reloaded in a new browsers tab, please try again!", "error", _COOKIES_); goto endofex; }
		$new_domain = strtolower(trim(@$_POST["domain"] ?? '') ?? '');
		if($new_domain == "") { x_eventBoxPrep("Please enter a valid domain name.", "error", _COOKIES_); goto endofex; }
		if(!dnshttp_isValidDomain($new_domain)) { x_eventBoxPrep("Please enter a valid domain name.", "error", _COOKIES_); goto endofex; }
		$disablerep = 0;
		if(@$_POST["disablerep"]) { $disablerep = 1; } else  { $disablerep = 0 ;}
		
		

		$bindnew = array();
		$bindnew[0]["value"] = strtolower(trim(@$_POST["domain"]));
		$checkexistant = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user = '".$user->user_id."' AND TRIM(LOWER(domain)) = ?", false, $bindnew);
		
		if(!$checkexistant) { 		
			
			$new_domain_converted = idn_to_ascii($new_domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
			$newsoa = generateSoaSerial();
			$new_content = "\$TTL "._USER_DOMAIN_MINIMUM_."
@ IN SOA "._SERVER_HOSTNAME_.". hostmaster.".$new_domain_converted.". ( ".$newsoa." "._USER_DOMAIN_REFRESH_." "._USER_DOMAIN_RETRY_." "._USER_DOMAIN_EXPIRE_." 300 )
@ IN NS ns1.".$new_domain_converted.".
mail IN MX 10 mail.".$new_domain_converted.".
ftp IN CNAME www.".$new_domain_converted.".
@ IN TXT \"v=spf1 include:_spf.".$new_domain." ~all\"
_dmarc IN TXT \"v=DMARC1; p=none; rua=mailto:dmarc@".$new_domain."\"";
		
			$bind = array();
			$bind[0]["value"] = $new_domain;
			$bind[1]["value"] = $new_content;
			$mysql->query("INSERT INTO "._TABLE_DOMAIN_BIND_." (domain, domain_type, content, fk_user, set_no_replicate, serial_c) 
														VALUES (?
														, 'user'
														, ?
														, '".$user->user_id."'
														, '".$disablerep."'
														, '".$newsoa."'
													);", $bind);
													
			x_eventBoxPrep("Domain has been created or is already existant on your account!", "ok", _COOKIES_);
			$checkserverid = $mysql->insert_id; 
			Header("Location: ./?site=domain_list&bind_id=".$checkserverid."");
			exit();
		
		} else {
			x_eventBoxPrep("This domain is already registered on your user account!", "error", _COOKIES_); goto endofex;
		}

	}
	endofex:
		
	/*************************************************************************
		Include Header
	*************************************************************************/
	define("_SUB_PAGE_TITLE_", "Domain Creation");
	require_once("./_default/default_header.php");
	require_once("./_default/default_navigation.php");
	echo '<div style="margin: 0px 0px 0px 0px; padding: 10px; 10px; 10px; 10px;"></div>'; ?>

	<!----------------- Show Alert Boxes -------------------->
    <div class="callout callout-info mb-3" role="alert">Create a new domain related to your user account.</div>

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
				  <label for="validationCustomUsername" class="form-label">Domain without www (You can change zone data after creation)</label>
				  <div class="input-group">
					<input
					  type="text"
					  class="form-control"
					  id="validationCustomUsername" 
					  name="domain"
					  maxlength="512"
					  placeholder="your-domain.ending"
					  value="<?php echo htmlentities(@$_POST["domain"] ?? ''); ?>"
					  required
					/>
				  </div>
				</div>
				
				<div class="col-md-12">
				
				  <div class="form-check" style="margin-right: 15px;">
					<input
					  class="form-check-input"
					  type="checkbox"
					  name="disablerep"
					  id="invalidCheck7"
					  <?php if(@$_POST["disablerep"]) { echo "checked"; } ?>
					/>
					<label class="form-check-label" for="invalidCheck7">
					 Disable Replication to external Servers on this domain? (Check to disable replication)
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
			  <button class="btn btn-warning" type="submit">Create Domain</button>
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
	
