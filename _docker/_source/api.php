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
		Helper Function
	*************************************************************************/	
	function helper_api_response(mixed $data = null, string $message = 'OK', int $status = 200): void
	{
		http_response_code($status);
		header('Content-Type: application/json');

		echo json_encode([
			'status'  => $status,
			'message' => $message,
			'data'    => $data,
		]);

		exit;
	}	

	/*************************************************************************
		Helper Function
	*************************************************************************/
	function generateSoaSerial(): int
	{
		$base = (int) date('Ymd') * 100 + 1;  // today formatted as YYYYMMDD01
		return $base;
	}
	
	/*************************************************************************
		Include Settings
	*************************************************************************/	
	if(file_exists("./_data/settings.php")) { require_once("./_data/settings.php"); }
		else { echo helper_api_response(null, "Awaiting Installation on Platform", 503); exit(); }
	if(file_exists("./_initialize/initialize.php")) { require_once("./_initialize/initialize.php"); }
		else { echo helper_api_response(null, "Awaiting Installation on Platform", 503); exit(); }

	/*************************************************************************
		Remove Session Write Lock
	*************************************************************************/	
	session_write_close();
	
	/*************************************************************************
		Check for Blacklist
	*************************************************************************/	
	if($ipbl->isblocked()) { echo helper_api_response(null, "Your IP-Range is currently blacklisted, please try again later or contact hostmaster.", 403); exit(); }
		
	/*************************************************************************
		Include Settings
	*************************************************************************/	
	$userid 			= @$_POST["userid"] ?? (@$_GET["userid"] ?? '');
	$usertoken 			= @$_POST["usertoken"] ?? (@$_GET["usertoken"] ?? '');
	$action 			= @$_POST["action"] ?? (@$_GET["action"] ?? '');
	if(!is_numeric($userid)) { echo helper_api_response(null, "Missing Parameter 'userid', which shall contain the user id of the user making a request.", 401); exit(); }
	
	/*************************************************************************
		Check if API Key fits User
	*************************************************************************/	
	$bind = array();
	$bind[0]["value"] = trim($usertoken);
	$current_user = $mysql->select("SELECT * FROM "._TABLE_USER_." WHERE id = ".$userid." AND TRIM(ext_api_key) = ?", false, $bind);
	if(!$current_user) { echo helper_api_response(null, "Parameter 'userid' and 'usertoken' are not a valid access code and id. Please check the token and userid.", 401); $ipbl->raise(); exit(); }
	
	/*************************************************************************
		Check if User has API Access
	*************************************************************************/	
	$permsobj = new x_class_perm($mysql, _TABLE_PERM_, "dnshttp");
	if(!$permsobj->hasPerm($userid, "api") AND $current_user["user_rank"] != 0) {
		echo helper_api_response(null, "Missing Permissions to use the API Interface.", 401); exit();
	}
	
	/*************************************************************************
		Actions
	*************************************************************************/	
	if($action == "user_domain_create") {
		$domain_name 			= @$_POST["domain_name"] ?? (@$_GET["domain_name"] ?? '');
		if(trim($domain_name ?? '') == "") {
			 echo helper_api_response(null, "Missing Parameter 'domain_name', which shall contain the new domain name to be created for the user.", 401); exit(); 
		}		
		
		$domain_name = strtolower(trim(@$domain_name ?? '') ?? '');
		if(!dnshttp_isValidDomain($domain_name)) { 
			echo helper_api_response(null, "Invalid Parameter 'domain_name', is not a valid Domain Name.", 401); exit(); 
		}
		$disablerep = 0;
		
		$bindnew = array();
		$bindnew[0]["value"] = strtolower(trim(@$domain_name));
		$checkexistant = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user = '".$userid."' AND TRIM(LOWER(domain)) = ?", false, $bindnew);
		
		if(!$checkexistant) { 	

			$new_domain_converted = idn_to_ascii($domain_name, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
			$newsoa = generateSoaSerial();
			$new_content = "\$TTL "._USER_DOMAIN_MINIMUM_."
@ IN SOA "._SERVER_HOSTNAME_.". hostmaster.".$new_domain_converted.". ( ".$newsoa." "._USER_DOMAIN_REFRESH_." "._USER_DOMAIN_RETRY_." "._USER_DOMAIN_EXPIRE_." 300 )
@ IN NS ns1.".$new_domain_converted.".
mail IN MX 10 mail.".$new_domain_converted.".
ftp IN CNAME www.".$new_domain_converted.".
@ IN TXT \"v=spf1 include:_spf.".$domain_name." ~all\"
_dmarc IN TXT \"v=DMARC1; p=none; rua=mailto:dmarc@".$domain_name."\"";
		
			$bind = array();
			$bind[0]["value"] = $domain_name;
			$bind[1]["value"] = $new_content;
			$mysql->query("INSERT INTO "._TABLE_DOMAIN_BIND_." (domain, domain_type, content, fk_user, set_no_replicate, serial_c) 
														VALUES (?
														, 'user'
														, ?
														, '".$userid."'
														, '".$disablerep."'
														, '".$newsoa."'
													);", $bind);
													
													
			echo helper_api_response(null, "OK", 200);										
			exit();
		
		} else {
			echo helper_api_response(null, "Another Domain with the same name for that user already exists.", 409); exit();
		}		
		
	} elseif($action == "user_domain_update") {
		$update_identifier 			= @$_POST["update_identifier"] ?? (@$_GET["update_identifier"] ?? '');
		if(!is_numeric($update_identifier)) {
			 echo helper_api_response(null, "Missing Parameter 'update_identifier', which shall contain the id of the domain to be deleted.", 401); exit(); 
		}
		$zone_data 			= @$_POST["zone_data"] ?? (@$_GET["zone_data"] ?? '');
		if(trim($zone_data ?? '') == "") {
			 echo helper_api_response(null, "Missing Parameter 'zone_data', which shall contain the domain zone file content to be updated.", 401); exit(); 
		}		
		
		$bindcc = array();
		$bindcc[0]["value"] = $zone_data;		
		$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET content = ? WHERE id = ".$update_identifier." AND fk_user = '".$userid."'", $bindcc); 
	
		echo helper_api_response(null, "OK", 200);										
		exit();
	
	} elseif($action == "user_domain_list") {
		
		$output1 = $mysql->select("SELECT id, domain, registered, content FROM "._TABLE_DOMAIN_BIND_." WHERE fk_user = ".$userid."");
		if(!$output1) {
			 $output1 = array();
		}
		
		echo helper_api_response($output1, "OK", 200);
		exit();		
		
	} elseif($action == "user_domain_delete") {
		$delete_identifier 			= @$_POST["delete_identifier"] ?? (@$_GET["delete_identifier"] ?? '');
		if(!is_numeric($delete_identifier)) {
			 echo helper_api_response(null, "Missing Parameter 'delete_identifier', which shall contain the id of the domain to be deleted.", 401); exit(); 
		}
		
		$output1 = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = ".$delete_identifier." AND fk_user = ".$userid."");
		if(!$output1) {
			 echo helper_api_response(null, "Domain with identifier provided by 'delete_identifier' has not been found.", 404); exit(); 
		}
		
		$mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = ".$delete_identifier." AND fk_user = ".$userid."");
		echo helper_api_response(null, "OK", 200);
		exit();
		
	} else {
		echo helper_api_response(null, "Parameter 'action' is invalid, please check the official documentation related to your dnshttp version for valid actions.", 400); exit();
	}