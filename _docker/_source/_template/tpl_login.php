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
		Login Operation
	*************************************************************************/
	if(isset($_POST["auth"])) {
		if($csrf->check(@$_POST["csrf"])) {
			if(isset($_POST["username"]) AND isset($_POST["password"])) {
				$x = $user->login_request(@$_POST["username"], @$_POST["password"]);
					if ($x == 1) {  }
					elseif ($x == 2) {x_eventBoxPrep("Wrong Username/Password combination, please ensure you are entering your correct username and password!", "error", _COOKIES_); $ipbl->raise();}
					elseif ($x == 3) {x_eventBoxPrep("Wrong Username/Password combination, please ensure you are entering your correct username and password!", "error", _COOKIES_); $ipbl->raise();}
					elseif ($x == 4) {x_eventBoxPrep("This user is currently blocked, because to many wrong password logins in the past have triggered the auto-block functionality!", "error", _COOKIES_);} else { $ipbl->raise(); }
					Header("Location: ./?site=".@$_GET["site"]."&section=".@$_GET["section"]."&id=".@$_GET["id"].""); exit();
			} else {
				x_eventBoxPrep("Please enter your username and password.", "error", _COOKIES_);
				Header("Location: ./?site=".@$_GET["site"]."&section=".@$_GET["section"]."&id=".@$_GET["id"].""); exit();
			}
		} else { x_eventBoxPrep("CSRF error, the form has expired or regenerated in another browser tab/window. Please enter your username and password again!", "error", _COOKIES_); Header("Location: ./?site=".@$_GET["site"]."&section=".@$_GET["section"]."&id=".@$_GET["id"].""); exit(); } 
		Header("Location: ./?site=".@$_GET["site"]."&section=".@$_GET["section"]."&id=".@$_GET["id"].""); exit();
	} 
?>

<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo htmlspecialchars(_TITLE_ ?? ''); ?></title>

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
	<link rel="icon" type="image/x-icon" href="<?php echo _DNSHTTP_FAVICON_; ?>">
    <!--end::Accessibility Meta Tags-->

    <!--begin::Primary Meta Tags-->
    <meta name="title" content="<?php echo htmlentities(_TITLE_ ?? ''); ?>" />
    <!--end::Primary Meta Tags-->

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

    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="./_assets/_css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
	
	<style>
		#imp_text_link {
			text-decoration: none !important;
		}
		#imp_text_link:hover {
			color: grey !important;
		}
		
		.x_eventBox {
			top: 0px;
		}
		
		body {
			background: #121212 !important;
			background: url(<?php echo _DNSHTTP_LOGIN_BG_; ?>) !important;
			background-size: auto  !important;
			background-position: cover  !important;
		}
	</style>
	
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="login-page bg-body-secondary">
    <div class="login-box">
      <div class="card card-primary">
	  
        <div class="card-header" style="background: #242424 !important; color: white !important;">
          <span
            class="link-dark text-center link-offset-2 link-opacity-100 link-opacity-50-hover"
			style="background: #242424 !important; color: white !important;"
          >
            <h1 class="mb-0" style="font-size: 22px;"><img src="<?php echo _DNSHTTP_LOGO_; ?>" style="width: 30px; margin-right: 5px;"><?php echo htmlspecialchars(_TITLE_ ?? ''); ?></h1>
		  </span>
        </div>
        <div class="card-body login-card-body">
          <p class="login-box-msg" style="font-size: 14px;">
		  Use this page to log in to your DNS server. <b style="color: red;">Do not lose your password</b> — there is no recovery option. If access is lost, you must manually generate a new bcrypt hash and update it directly in the MySQL users table. Ensure your credentials are kept secure at all times.
		</p>
          <form method="post">
            <div class="input-group mb-1">
              <div class="form-floating">
                <input id="loginEmail" type="text" name="username" maxlength="74" class="form-control" value="" placeholder=""  autocomplete="off" required/>
                <label for="loginEmail">Username</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-person"></span>
              </div>
            </div>
            <div class="input-group mb-1">
              <div class="form-floating">
                <input id="loginPassword" type="password" name="password" maxlength="74" class="form-control" placeholder="" autocomplete="off" required/>
                <label for="loginPassword">Password</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
              </div>
            </div>
            <!--begin::Row-->
            <div class="row">
              <!-- /.col -->
              <div class="col-4">
                <div class="d-grid gap-2">
				  <input type="hidden"	name="csrf"			value="<?php echo $csrf->get(); ?>">
                  <button type="submit" name="auth" class="btn btn-primary">Sign In</button>
                </div>
              </div>
              <!-- /.col -->
            </div>
			<p class="login-box-msg" style="margin-bottom: 0px; padding-bottom: 0px; font-size: 14px !important;">
				<?php if(_IMPRESSUM_ AND strlen(_IMPRESSUM_ ?? '' > 5)) { ?><a id="imp_text_link" rel="noopener" target="_blank" class="text-black" href="<?php echo htmlentities(_IMPRESSUM_ ?? ''); ?>">Impressum</a> | <?php } ?>
				<?php if(_IMPRESSUM_ AND strlen(_HELP_ ?? '' > 5)) { ?> <a id="imp_text_link" rel="noopener" target="_blank" class="text-black" href="<?php echo htmlentities(_HELP_ ?? ''); ?>">Help</a> | <?php } ?>
				<?php if(_IMPRESSUM_ AND strlen(_GITHUB_ ?? '' > 5)) { ?> <a id="imp_text_link" rel="noopener" target="_blank" class="text-black" href="<?php echo htmlentities(_GITHUB_ ?? ''); ?>">Github</a><?php } ?><br />
				<?php echo _FOOTER_; ?>
			</p>
            <!--end::Row-->
          </form>
		  
        </div>
        <!-- /.login-card-body -->
      </div>
	  
    </div>
    <!-- /.login-box -->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="./_assets/_js/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="./_assets/_js/popper.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="./_assets/_js/bootstrap.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="./_assets/_js/adminlte.js"></script>
    <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);

        // Disable OverlayScrollbars on mobile devices to prevent touch interference
        const isMobile = window.innerWidth <= 992;

        if (
          sidebarWrapper &&
          OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
          !isMobile
        ) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <!--end::OverlayScrollbars Configure-->
    <!--end::Script-->
	<?php 
		# Display Event Boxes
		x_eventBoxShow(_COOKIES_);
		# Display Cookie Banner
		x_cookieBanner(_COOKIES_, true, _COOKIEBANNER_TEXT_);	
	?>
  </body>
  <!--end::Body-->
</html>
