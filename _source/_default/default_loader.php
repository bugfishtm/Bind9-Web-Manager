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
		Load Required Section
	*************************************************************************/
	$tmp_id = 0; 
	switch(@$_GET["site"]) {
		case "dashboard": $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_dashboard.php"); break;
		case "domain_create": if($permsobj->hasPerm($user->user_id, "domain_create") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_domain_create.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "domain_list": $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_domain_list.php"); break;
		case "domain_conflict": if($permsobj->hasPerm($user->user_id, "domain_conflicts") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_domain_conflict.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "server_create": if($permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_server_create.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "server_list": if($permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_server_list.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "user_create": if($permsobj->hasPerm($user->user_id, "users") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_user_create.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "user_list": if($permsobj->hasPerm($user->user_id, "users") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_user_list.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "debugging": if($permsobj->hasPerm($user->user_id, "system") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_debugging.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "blacklist": if($permsobj->hasPerm($user->user_id, "system") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_blacklist.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "about": if($permsobj->hasPerm($user->user_id, "system") OR $user->user_rank == 0) { $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_about.php"); } else { Header("Location: ./?site=dashboard"); exit(); } break;
		case "profile": $csrf = new x_class_csrf(_COOKIES_."_s".@$_GET["site"].$tmp_id, _CSRF_VALID_LIMIT_TIME_); require_once("./_site/site_profile.php"); break;
		case "logout": $user->logout(); x_eventBoxPrep("You have been logged out!", "ok", _COOKIES_); Header("Location: ./"); exit(); break;
		default: Header("Location: ./?site=dashboard"); exit();			
	};