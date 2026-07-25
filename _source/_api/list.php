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
		Include Settings
	*************************************************************************/	
	if(file_exists("../_data/settings.php")) { require_once("../_data/settings.php"); }
		else { echo dnshttp_api_output_install_error(); exit(); }
	if(file_exists("../_initialize/initialize.php")) { require_once("../_initialize/initialize.php"); }
		else { echo dnshttp_api_output_install_error(); exit(); }
	
	/*************************************************************************
		Remove Session Write Lock
	*************************************************************************/	
	session_write_close();
	
	/*************************************************************************
		Check if Request is IP-Blocked
	*************************************************************************/	
	dnshttp_api_blacklist_check($ipbl);
	
	/*************************************************************************
		Check if Token is Valid
	*************************************************************************/	
	dnshttp_api_token_check($mysql, $ipbl, @$_POST["token"]);
	
	/*************************************************************************
		Echo Requested Output
	*************************************************************************/	
	$domar	=	array();
	// This table only holds domains cleared for replication - the sync cronjob applies
	// set_no_replicate when it fills it, so nothing has to be filtered out again here.
	$ar = $mysql->select("SELECT domain FROM "._TABLE_DOMAIN_REG_."", true);
	// The empty result used to be returned gzip+base64 encoded while the populated one was returned
	// plain. The receiving side runs dnshttp_decompress(), which is a passthrough, so the encoded
	// form never unserializes and a peer holding zero replicable domains was reported as a broken
	// connection instead of an empty list. Always answer in the one format the reader understands.
	if(is_array($ar)) {
		foreach($ar AS $key => $value) { array_push($domar, trim($value["domain"])); }
	}
	echo serialize($domar);
	exit();
	
	