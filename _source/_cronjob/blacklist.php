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
		Not possible to open in public browser
	*************************************************************************/
	if(php_sapi_name() !== 'cli'){
		die('Can only be executed via CLI');
		exit();
	}

	/*************************************************************************
		Include Settings
	*************************************************************************/
	if(file_exists(dirname(__FILE__) ."/../_data/settings.php")) { require_once(dirname(__FILE__) ."/../_data/settings.php"); }
		else { echo "ERROR: _data/settings.php does not exist. Please check your instance configuration!"; exit(); }
	if(file_exists(dirname(__FILE__) ."/../_initialize/initialize.php")) { require_once(dirname(__FILE__) ."/../_initialize/initialize.php"); }
		else { echo "ERROR: _initialize/initialize.php does not exist. Please check your instance configuration!"; exit(); }
		
	/*************************************************************************
		Delete IP Blacklist Table Entries 
	*************************************************************************/		 
	$mysql->query("DELETE FROM "._TABLE_IPBL_." ");
	$mysql->query("ALTER TABLE "._TABLE_IPBL_." AUTO_INCREMENT = 1");
	
	/*************************************************************************
		Log Blacklist Fluch in Protocol
	*************************************************************************/	
	$log->info("Cronbjob blacklist.php has been executed and IP-Blacklist has been flushed.", "cron_blacklist");	