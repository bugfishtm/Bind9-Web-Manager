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
		Variables
	*************************************************************************/
	$otp_user_id   = @$_SESSION[_COOKIES_."x_users_id"];
	$otp_max_fails = 5;
	$otp_redirect  = "./?site=".urlencode((string)@$_GET["site"])."&section=".urlencode((string)@$_GET["section"])."&id=".urlencode((string)@$_GET["id"]);

	/*************************************************************************
		Cancel / Logout Operation
	*************************************************************************/
	if(@$_GET["site"] == "logout") {
		dnshttp_otp_session_clear();
		$user->logout();
		x_eventBoxPrep("You have been logged out!", "ok", _COOKIES_);
		Header("Location: ./"); exit();
	}

	/*************************************************************************
		OTP Verification Operation
	*************************************************************************/
	if(isset($_POST["otp_auth"])) {
		if($csrf->check(@$_POST["csrf"])) {
			if(dnshttp_otp_verify_user($mysql, $otp_user_id, @$_POST["otp_code"])) {
				dnshttp_otp_session_mark();
				Header("Location: ".$otp_redirect); exit();
			} else {
				$ipbl->raise();
				$_SESSION[dnshttp_otp_session_fails_name()] = intval(@$_SESSION[dnshttp_otp_session_fails_name()]) + 1;
				if($_SESSION[dnshttp_otp_session_fails_name()] >= $otp_max_fails) {
					dnshttp_otp_session_clear();
					$user->logout();
					x_eventBoxPrep("Too many invalid one-time passwords. You have been logged out, please sign in again!", "error", _COOKIES_);
					Header("Location: ./"); exit();
				}
				x_eventBoxPrep("The one-time password is invalid or has already been used. Please wait for the next code of your authenticator app and try again!", "error", _COOKIES_);
			}
		} else { x_eventBoxPrep("CSRF error, the form has expired or regenerated in another browser tab/window. Please enter your one-time password again!", "error", _COOKIES_); }
		Header("Location: ".$otp_redirect); exit();
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

		#otpCode {
			letter-spacing: 6px;
			font-family: monospace;
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
			Your account '<b><?php echo htmlspecialchars($user->user_name ?? ''); ?></b>' is protected with a one-time password. Please enter the current 6-digit code from your authenticator app to complete the login.
		  </p>
          <form method="post">
            <div class="input-group mb-1">
              <div class="form-floating">
                <input id="otpCode" type="text" name="otp_code" maxlength="7" inputmode="numeric" pattern="[0-9 ]{6,7}" class="form-control" value="" placeholder="" autocomplete="one-time-code" autofocus required/>
                <label for="otpCode">One-Time Password</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-shield-lock-fill"></span>
              </div>
            </div>
            <!--begin::Row-->
            <div class="row">
              <div class="col-8">
                <div class="d-grid gap-2">
				  <input type="hidden"	name="csrf"			value="<?php echo $csrf->get(); ?>">
                  <button type="submit" name="otp_auth" class="btn btn-primary">Verify</button>
                </div>
              </div>
              <div class="col-4">
                <div class="d-grid gap-2">
                  <a href="./?site=logout" class="btn btn-secondary">Cancel</a>
                </div>
              </div>
            </div>
            <!--end::Row-->
			<p class="login-box-msg" style="margin-bottom: 0px; padding-bottom: 0px; padding-top: 10px; font-size: 14px !important;">
				<?php if(_IMPRESSUM_ AND strlen(trim(_IMPRESSUM_ ?? '')) > 5) { ?><a id="imp_text_link" rel="noopener" target="_blank" class="text-black" href="<?php echo htmlentities(_IMPRESSUM_ ?? ''); ?>">Impressum</a> | <?php } ?>
				<?php if(_HELP_ AND strlen(trim(_HELP_ ?? '')) > 5) { ?> <a id="imp_text_link" rel="noopener" target="_blank" class="text-black" href="<?php echo htmlentities(_HELP_ ?? ''); ?>">Help</a> | <?php } ?>
				<?php if(_GITHUB_ AND strlen(trim(_GITHUB_ ?? '')) > 5) { ?> <a id="imp_text_link" rel="noopener" target="_blank" class="text-black" href="<?php echo htmlentities(_GITHUB_ ?? ''); ?>">Github</a><?php } ?><br />
				<?php echo _FOOTER_; ?>
			</p>
          </form>

        </div>
        <!-- /.login-card-body -->
      </div>

    </div>
    <!-- /.login-box -->
    <!--begin::Required Plugin(popperjs for Bootstrap 5)-->
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
    <!--end::Required Plugin(AdminLTE)-->
	<?php
		# Display Event Boxes
		x_eventBoxShow(_COOKIES_);
		# Display Cookie Banner
		x_cookieBanner(_COOKIES_, true, _COOKIEBANNER_TEXT_);
	?>
  </body>
  <!--end::Body-->
</html>
