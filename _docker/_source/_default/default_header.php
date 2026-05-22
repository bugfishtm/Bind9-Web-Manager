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
	
?><!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <title><?php echo htmlspecialchars(_SUB_PAGE_TITLE_ ?? ''); ?> / <?php echo htmlspecialchars(_TITLE_ ?? ''); ?></title>
    <meta name="title" content="<?php echo htmlentities(_SUB_PAGE_TITLE_ ?? ''); ?> / <?php echo htmlentities(_TITLE_ ?? ''); ?>" />
	
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta name="robots" content="noindex, nofollow" />
    <meta name="color-scheme" content="light dark" />
	<meta http-equiv="content-Language" content="en" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
	<link rel="icon" type="image/x-icon" href="<?php echo _DNSHTTP_FAVICON_; ?>">
    <!--end::Accessibility Meta Tags-->
	
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="./_assets/_css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->

    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="./_assets/_css/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media = 'all'"
    />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="./_assets/_css/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="./_assets/_css/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->

    <!--begin::DNSHTTP CSS -->
    <link
      rel="stylesheet"
      href="./_assets/_css/custom.css"
      crossorigin="anonymous"
    />
    <!--end::DNSHTTP CSS-->
	
	<script>
	
		function dnshttp_ls_open() {
			document.getElementById("dnshttp_search_overlay").style.display = "block";
		}
		
		function dnshttp_ls_close() {
			document.getElementById("dnshttp_search_overlay").style.display = "none"; 
			document.getElementById("dnshttp_search_overlay_result").innerHTML = '<div class="alert alert-primary" role="alert">You have not started a search operation yet.</div>';
			document.getElementById("dnshttp_search_overlay_input").value = "";
			const url = new URL(window.location.href);
			url.searchParams.delete('search');
			url.searchParams.delete('search_operation');
			url.searchParams.delete('csrf');
			window.history.replaceState({}, '', url);
			
		}
		
	</script>
	
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="./_assets/_css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed fixed-header fixed-footer sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
	
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body" style="background: #121212 !important; color: white !important;">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list" style="color: white;"></i>
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::Navbar Search-->
            <li class="nav-item">
              <a class="nav-link" data-widget="navbar-search" onClick="dnshttp_ls_open()" role="button">
                <i class="bi bi-search" style="color: white;"></i>
              </a>
            </li>
            <!--end::Navbar Search-->

            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
				<a href="./?site=logout" class="btn btn-danger float-end" style="margin-left: 5px;">Logout</a> 
				<a href="./?site=profile" class="btn btn-primary float-end">Profile</a>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->