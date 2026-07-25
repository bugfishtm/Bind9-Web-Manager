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
		Single Run Lock
	*************************************************************************/
	# A run writes shared state: the named-compilezone scratch file, the generated
	# dnshttp.named.conf.local and the zone files themselves. Two overlapping runs interleave
	# those writes and can leave bind with a half-written configuration, so a second run exits.
	$sync_lock_file   = dirname(__FILE__)."/../_data/sync.lock";
	$sync_lock_handle = @fopen($sync_lock_file, "c");
	if($sync_lock_handle) {
		if(!@flock($sync_lock_handle, LOCK_EX | LOCK_NB)) {
			echo "ERROR: Another sync.php run is still in progress, exiting without changes.\r\n";
			$log->warning("Cronjob sync.php aborted, a previous run is still in progress.", "cron_sync");
			exit();
		}
	} else {
		# Only an actual conflicting run may stop a sync. A lock file that cannot be created is an
		# environment problem and must not halt DNS replication, so carry on and just record it.
		echo "WARN: Lock file could not be created, running without overlap protection.\r\n";
		$log->warning("Cronjob sync.php could not create its lock file at ".$sync_lock_file." - running without overlap protection.", "cron_sync");
	}

	/*************************************************************************
		Start Message
	*************************************************************************/
	$log->info("Cronbjob sync.php has been started.", "cron_sync");
	
	/*************************************************************************
		Run the Cronjob to Execute all operations
	*************************************************************************/			
	dnshttp_cron_sync($mysql, $log);
		
	/*************************************************************************
		End Message
	*************************************************************************/
	$log->info("Cronbjob sync.php has been finished.", "cron_sync");

	/*************************************************************************
		Release Single Run Lock
	*************************************************************************/
	# flock()/fclose() on a false handle is a TypeError in PHP 8, which @ does not suppress.
	if($sync_lock_handle) {
		@flock($sync_lock_handle, LOCK_UN);
		@fclose($sync_lock_handle);
	}