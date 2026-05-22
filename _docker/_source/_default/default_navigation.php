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
?>

      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark" style="background: #121212 !important;">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="./?site=dashboard" class="brand-link">
            <!--begin::Brand Image-->
            <img
              src="<?php echo _DNSHTTP_LOGO_; ?>"
              alt="Logo"
              class="brand-image opacity-75 shadow"
            />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light"><?php if(strlen(_TITLE_) > 17) { $toptitleext = "..."; } else { $toptitleext = ""; } echo htmlspecialchars(substr(_TITLE_ ?? '', 0, 17) ?? '').$toptitleext; ?></span>
            <!--end::Brand Text-->
          </a>
		  
          <!--end::Brand Link-->
        </div>
        <!--end::Sidebar Brand-->
        <!--begin::Sidebar Wrapper-->
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              role="navigation"
              aria-label="Main navigation"
              data-accordion="false"
              id="navigation"
            >
			  
			  <li class="nav-header">DASHBOARD</li>
			  
              <li class="nav-item">
                <a href="./?site=dashboard" class="nav-link <?php if(@$_GET["site"] == "dashboard") { echo "active"; } ?>">
                  <i class="nav-icon bi bi-speedometer2"></i>
                  <p>Dashboard</p>
                </a>
              </li>
			  
			  <li class="nav-header">DOMAINS</li>

			  <?php if($permsobj->hasPerm($user->user_id, "domain_create") OR $user->user_rank == 0) { ?>
			  
              <li class="nav-item">
                <a href="./?site=domain_create" class="nav-link <?php if(@$_GET["site"] == "domain_create") { echo "active"; } ?>">
                  <i class="nav-icon bi bi-plus-lg"></i>
                  <p>Create Domain</p>
                </a>
              </li>
			  
			  <?php } ?>		  	

              <li class="nav-item">
                <a href="./?site=domain_list" class="nav-link <?php if(@$_GET["site"] == "domain_list") { echo "active"; } ?>">
                  <i class="nav-icon bi bi-globe-europe-africa"></i>
                  <p>List Domains</p>
                </a>
              </li>	
			  
			  <?php if($permsobj->hasPerm($user->user_id, "domain_conflicts") OR $user->user_rank == 0) { ?>
			  
				  <li class="nav-item">
					<a href="./?site=domain_conflict" class="nav-link <?php if(@$_GET["site"] == "domain_conflict") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-copy"></i>
					  <p>List Conflicts</p>
					</a>
				  </li>		
				  
			  <?php } ?>		  		  
					 
			  <?php if($permsobj->hasPerm($user->user_id, "servers") OR $user->user_rank == 0) { ?>
			  
				  <li class="nav-header">SERVERS</li>
				  
				  <li class="nav-item">
					<a href="./?site=server_create" class="nav-link <?php if(@$_GET["site"] == "server_create") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-plus-lg"></i>
					  <p>Create Server</p>
					</a>
				  </li>
				  
				  <li class="nav-item">
					<a href="./?site=server_list" class="nav-link <?php if(@$_GET["site"] == "server_list") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-hdd-rack"></i>
					  <p>List Servers</p>
					</a>
				  </li>
			  
			  <?php } ?>
			  
			  <?php if($permsobj->hasPerm($user->user_id, "users") OR $user->user_rank == 0) { ?>
			  
				  <li class="nav-header">USERS</li>
				  
				  <li class="nav-item">
					<a href="./?site=user_create" class="nav-link <?php if(@$_GET["site"] == "user_create") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-plus-lg"></i>
					  <p>Create User</p>
					</a>
				  </li>
				  
				  <li class="nav-item">
					<a href="./?site=user_list" class="nav-link <?php if(@$_GET["site"] == "user_list") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-person-square"></i>
					  <p>List Users</p>
					</a>
				  </li>
				  
			  <?php } ?>
			  
			  <?php if($permsobj->hasPerm($user->user_id, "system") OR $user->user_rank == 0) { ?>
			  
				  <li class="nav-header">SYSTEM</li>
				  
				  <li class="nav-item">
					<a href="./?site=debugging" class="nav-link <?php if(@$_GET["site"] == "debugging") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-list-columns"></i>
					  <p>Debugging</p>
					</a>
				  </li>
				  
				  <li class="nav-item">
					<a href="./?site=blacklist" class="nav-link <?php if(@$_GET["site"] == "blacklist") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-shield-exclamation"></i>
					  <p>IP-Blacklist</p>
					</a>
				  </li>
				  
				  <li class="nav-item">
					<a href="./?site=about" class="nav-link <?php if(@$_GET["site"] == "about") { echo "active"; } ?>">
					  <i class="nav-icon bi bi-info-square"></i>
					  <p>About</p>
					</a>
				  </li>
				  
			  <?php } ?>

            </ul>
            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>
      <!--end::Sidebar-->
	  
      <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header" style="background: #181818 !important; color: white !important; font-size: 18px !important;">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6">
                <h3 class="mb-0" style="background: #181818 !important; color: white !important; font-size: 18px !important;"><?php echo _SUB_PAGE_TITLE_; ?></h3>
              </div>
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content Header-->
        <!--begin::App Content-->
        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">