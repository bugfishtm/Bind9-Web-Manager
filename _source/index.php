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
		Docker Define Var for installations
	*************************************************************************/	
	define("_DNSHTTP_DOCKERIZED_", true);
	
	/*************************************************************************
		Installation if no Settings.php has been found.
	*************************************************************************/	
	if(!file_exists("./_data/settings.php")) {
		$mysql = array();
		require_once("./_template/tpl_install.php");
		exit();
	}
	
	/*************************************************************************
		Include Required Settings
	*************************************************************************/	
	require_once("./_data/settings.php");
	
	/*************************************************************************
		Include Required Initialization
	*************************************************************************/	
	if(file_exists("./_initialize/initialize.php")) { require_once("./_initialize/initialize.php"); }
		else { echo "ERROR: initialize.php does not exist. Please check your instance configuration!"; exit(); }
		
	/*************************************************************************
		User IP is Blacklisted.
	*************************************************************************/	
	if($ipbl->isblocked()) {
		require_once("./_template/tpl_blocked.php");
		exit();
	}	
	
	/*************************************************************************
		Variables for CSRF and CookieBanner
	*************************************************************************/	
	x_cookieBanner_Pre(_COOKIES_);	
	
	/*************************************************************************
		User is not logged In.
	*************************************************************************/	
	if(!$user->loggedIn) {	
		$csrf = new x_class_csrf(_COOKIES_, _CSRF_VALID_LIMIT_TIME_); 
		require_once("./_template/tpl_login.php");
		exit();
	}
	
	/*************************************************************************
		Default if everything else alright.
	*************************************************************************/	
	$permsobj = new x_class_perm($mysql, _TABLE_PERM_, "dnshttp");
	require_once("./_default/default_loader.php");