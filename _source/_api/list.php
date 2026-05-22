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
	$ar = $mysql->select("SELECT domain FROM "._TABLE_DOMAIN_REG_."", true);	
	if(is_array($ar)) {	
		foreach($ar AS $key => $value) { array_push($domar, trim($value["domain"])); }
		echo serialize($domar);
		//if(@$_POST["compress"] == "stop")  { echo serialize($domar); }
		//else { echo dnshttp_compress(serialize($domar)); }
		exit();
	} else { 
		echo dnshttp_compress(serialize($domar));
		exit();
	}	
	
	