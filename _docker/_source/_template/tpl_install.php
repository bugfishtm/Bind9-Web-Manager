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
	if(!is_array(@$mysql)) { @http_response_code(404); Header("Location: ../"); exit(); }

	// ─── Step Logic ───────────────────────────────────────────────────────────────
	session_start();
	define('CONFIG_FILE', __DIR__ . '/../_data/settings.php');

	// ─── Step Logic ───────────────────────────────────────────────────────────────
	$step   = 1;
	$errors = [];
	$success= false;
	$old    = [];

	// ─── PHP Modules to Check ─────────────────────────────────────────────────────
	$modules = [
		['name' => 'MySQLi',       'check' => extension_loaded('mysqli'),        'required' => true,  'note' => 'MySQL interface'],
		['name' => 'JSON',         'check' => extension_loaded('json'),          'required' => true,  'note' => 'JSON encode/decode'],
		['name' => 'mbstring',     'check' => extension_loaded('mbstring'),      'required' => true,  'note' => 'Multibyte string support'],
		['name' => 'cURL',         'check' => extension_loaded('curl'),          'required' => true,  'note' => 'HTTP requests'],
		['name' => 'intl',         'check' => extension_loaded('intl'),          'required' => true,  'note' => 'To convert Domains to prefered format'],
		['name' => 'Session',      'check' => extension_loaded('session'),       'required' => true,  'note' => 'User session handling'],
	];
	
	// ─── Configuration Write ─────────────────────────────────────────────────────
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$step = (int)($_POST['step'] ?? 1);

		if ($step === 3) {
			// Final submission: validate MySQL, write config
			$old = [
				'title'     => trim($_POST['title']     ?? ''),
				'impressum' => trim($_POST['impressum'] ?? ''),
				'sql_host'  => trim($_POST['sql_host']  ?? '127.0.0.1'),
				'sql_user'  => trim($_POST['sql_user']  ?? ''),
				'sql_db'    => trim($_POST['sql_db']    ?? ''),
				'method'    => $_POST['method']         ?? 'standalone',
				// custom fields
				'bind_file'         => trim($_POST['bind_file']         ?? '/etc/bind/named.conf.default-zones'),
				'bind_file2'        => trim($_POST['bind_file2']        ?? '/etc/bind/named.conf.local'),
				'bind_lib'          => trim($_POST['bind_lib']          ?? '/etc/bind/dnshttp/'),
				'bind_confname'     => trim($_POST['bind_confname']     ?? '/etc/bind/named.conf'),
				'bind_user'         => trim($_POST['bind_user']         ?? 'bind'),
				'bind_group'        => trim($_POST['bind_group']        ?? 'bind'),
				'bind_code'         => trim($_POST['bind_code']         ?? '770'),
				'bind_service'      => trim($_POST['bind_service']      ?? 'bind9'),
				'folder_fetch'      => trim($_POST['folder_fetch']      ?? ''),
				'folder_rmstart'    => trim($_POST['folder_rmstart']    ?? '4'),
				'folder_rmend'      => trim($_POST['folder_rmend']      ?? '0'),
				'hostname'     		=> trim($_POST['hostname']      	?? '0'),
				'bind_rewrite'      => isset($_POST['bind_rewrite'])    ? 'true' : 'false',
			];
			$sql_pass = trim($_POST['sql_pass'] ?? '');

			// Validate required
			if (empty($old['title']))    $errors[] = 'Website title is required.';
			if (empty($old['sql_user'])) $errors[] = 'MySQL user is required.';
			if (empty($old['sql_db']))   $errors[] = 'MySQL database is required.';

			if (empty($errors)) {
				try {
					$mysqli = new mysqli($old['sql_host'], $old['sql_user'], $sql_pass, $old['sql_db']);
					if ($mysqli->connect_error) {
						throw new Exception($mysqli->connect_error);
					}
					$mysqli->set_charset('utf8');

					$method = $old['method'];
					$cfg = buildConfig($old, $sql_pass, $method);
					$written = writeSettings($cfg);
					if ($written === true) {
						$success = true;
						$step = 99;
					} else {
						$errors[] = 'Could not write settings.php: ' . $written;
					}
				} catch (Exception $e) {
					$errors[] = 'MySQL connection failed: ' . htmlspecialchars($e->getMessage());
				}
			}

			if (!$success) $step = 3; // stay on step 3

		} elseif ($step === 2) {
			$step = 3;
		} elseif ($step === 1) {
			$step = 2;
		}
	}
	
	// ─── Config Builder ───────────────────────────────────────────────────────────
	function buildConfig(array $old, string $sql_pass, string $method): array {
		$base = [
			'_TITLE_'                              => $old['title'],
			'_HELP_'                               => "https://bugfishtm.github.io/Bind9-Web-Manager/",
			'_DNSHTTP_LOGO_'                       => "./_assets/_img/logo_alpha.png",
			'_DNSHTTP_LOGIN_BG_'                   => "./_assets/_img/login_bg.jpg",
			'_DNSHTTP_FAVICON_'                    => "./favicon.ico",
			'_FOOTER_'                   		   => 'Bind9 Web Manager by Bugfish',
			'_COOKIEBANNER_TEXT_'                  => 'This website is using session cookies for critical operations!',
			'_IMPRESSUM_'                          => $old['impressum'],
			'_SQL_HOST_'                           => $old['sql_host'],
			'_SQL_USER_'                           => $old['sql_user'],
			'_SQL_PASS_'                           => $sql_pass,
			'_SQL_DB_'                             => $old['sql_db'],
			'_CRON_BIND_FILE_'                     => '/etc/bind/named.conf.default-zones',
			'_CRON_BIND_FILE_2_'                   => '/etc/bind/named.conf.local',
			'_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_' => '4',
			'_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_'  => '0',
			'_CRON_FILES_FOLDER_FETCH_'            => 'false',
			'_CRON_BIND_FILE_REWRITE_'             => 'false',
			'_CRON_BIND_LIB_USER_'                 => 'bind',
			'_CRON_BIND_LIB_GROUP_'                => 'bind',
			'_CRON_BIND_LIB_CODE_'                 => '770',
			'_CRON_BIND_LIB_ENDING_'               => '.dnshttp',
			'_BIND_SERVICE_NAME_'                  => 'bind9',
			'_BIND_CHECKZONE_COMMAND_'             => '/usr/bin/named-checkzone',
			'_BIND_COMPILEZONE_COMMAND_'           => '/usr/bin/named-compilezone',
			'_CRON_BIND_LIB_'                      => '/etc/bind/dnshttp/',
			'_CRON_BIND_CONFNAME_'                 => '/etc/bind/named.conf',
			'_COOKIES_'                            => 'dnshttp_',
			'_IP_BLACKLIST_DAILY_OP_LIMIT_'        => '10000',
			'_CSRF_VALID_LIMIT_TIME_'              => '10000',
			'_USER_AUTOBLOCK_'             		   => '10000',
			'_USER_DOMAIN_EXPIRE_'             	   => '604800',
			'_USER_DOMAIN_RETRY_'             	   => '540',
			'_USER_DOMAIN_REFRESH_'                => '7200',
			'_USER_DOMAIN_MINIMUM_'                => '3600',
			'_USER_DOMAIN_MAIL_'             	   => 'postmaster@{domain}',
			'_SERVER_HOSTNAME_'             	   => $old['hostname'],
		];

		switch ($method) {
			case 'virtualmin':
				$base['_CRON_BIND_FILE_REWRITE_'] = 'true';
				break;
			case 'ispconfig':
				$base['_CRON_FILES_FOLDER_FETCH_'] = '"/etc/bind/zones/"';
				break;
			case 'plesk':
				$base['_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_']  = '0';
				$base['_CRON_FILES_FOLDER_FETCH_']  = "/var/named/run-root/var";
				break;
			case 'custom':
				$base['_CRON_BIND_LIB_USER_']  = $old['bind_user'];
				$base['_CRON_BIND_LIB_GROUP_'] = $old['bind_group'];
				$base['_CRON_BIND_LIB_CODE_']  = $old['bind_code'];
				$base['_BIND_SERVICE_NAME_']   = $old['bind_service'];
				$base['_CRON_BIND_FILE_REWRITE_']  = $old['bind_rewrite'];
				$base['_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_'] = $old['folder_rmstart'];
				$base['_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_']  = $old['folder_rmend'];
				$folderFetch = trim($old['folder_fetch'], '"\'');
				$base['_CRON_FILES_FOLDER_FETCH_'] = $folderFetch ? '"' . $folderFetch . '"' : 'false';
				$base['_CRON_BIND_FILE_']  = $old['bind_file'];
				$base['_CRON_BIND_FILE_2_']= $old['bind_file2'];
				$base['_CRON_BIND_LIB_']   = $old['bind_lib'];
				$base['_CRON_BIND_CONFNAME_'] = $old['bind_confname'];
				break;
		}

		return $base;
	}
	
	// ─── Settings Writer ──────────────────────────────────────────────────────────
	function writeSettings(array $cfg): bool|string {
		$lines = [];
		$lines[] = '<?php';
		$lines[] = '// Generated by installer.php on ' . date('Y-m-d H:i:s');
		$lines[] = '';

		$intKeys = [
			'_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_',
			'_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_',
			'_CRON_BIND_LIB_CODE_',
			'_IP_BLACKLIST_DAILY_OP_LIMIT_',
			'_CSRF_VALID_LIMIT_TIME_',
		];
		$boolKeys = [
			'_CRON_FILES_FOLDER_FETCH_',
			'_CRON_BIND_FILE_REWRITE_',
		];

		foreach ($cfg as $key => $val) {
			if (in_array($key, $intKeys) && is_numeric($val)) {
				$lines[] = 'define("' . $key . '", ' . (int)$val . ');';
			} elseif (in_array($key, $boolKeys)) {
				// might be false, true, or a quoted path
				if ($val === 'true' || $val === 'false') {
					$lines[] = 'define("' . $key . '", ' . $val . ');';
				} else {
					// It's a path string (ISPConfig folder fetch)
					$clean = trim($val, '"\'');
					$lines[] = 'define("' . $key . '", "' . addslashes($clean) . '");';
				}
			} else {
				$lines[] = 'define("' . $key . '", "' . addslashes($val) . '");';
			}
		}

		$content = implode("\n", $lines) . "\n";
		$result = @file_put_contents(CONFIG_FILE, $content);
		if ($result === false) {
			return 'Permission denied — make sure the web root is writable.';
		}
		return true;
	}

	$criticalFail = false;
	foreach ($modules as $m) {
		if ($m['required'] && !$m['check']) { $criticalFail = true; break; }
	}

	$phpVersion    = phpversion();
	$phpVersionOk  = version_compare($phpVersion, '8.2.0', '>=');
	$configExists  = file_exists(CONFIG_FILE);
	
	$reqFail = false;
	$mem = intval(ini_get('memory_limit'));
	if($mem >= 256) {  }
	elseif($mem >= 128) {  }
	else { $reqFail = true; }

	$exe = intval(ini_get('max_execution_time'));
	if($exe >= 600) { }
	elseif($exe >= 180) { }
	else {  $reqFail = true; }

	$post = intval(ini_get('post_max_size'));
	if($post >= 256) { }
	elseif($post >= 128) {  }
	else { $reqFail = true; }

	function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
	function old(string $key, string $default = ''): string {
		global $old;
		return e($old[$key] ?? $default);
	}
	
?><!DOCTYPE html>
	<html lang="en">
	<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bind9 Web Manager — Installer</title>
	<meta name="robots" content="noindex, nofollow" />
	<style>
	  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

	  :root {
		--bg:        #07090c;
		--bg2:       #0d1117;
		--bg3:       #141b24;
		--border:    #1e2d3d;
		--border2:   #2a3f55;
		--accent:    #FF5707;
		--accent2:   #fa651e;
		--danger:    #ff4560;
		--warn:      #f5a623;
		--muted:     #4a6070;
		--text:      #c9d8e8;
		--text2:     #7a9bb5;
		--white:     #eef4fb;
		--mono:      'JetBrains Mono', monospace;
		--head:      'Syne', sans-serif;
	  }

	  html, body {
		background: var(--bg);
		color: var(--text);
		font-family: var(--mono);
		font-size: 14px;
		min-height: 100vh;
		line-height: 1.6;
	  }

	  /* Background grid */
	  body::before {
		content: '';
		position: fixed; inset: 0; z-index: 0;
		background-image:
		  linear-gradient(rgba(0,229,176,.03) 1px, transparent 1px),
		  linear-gradient(90deg, rgba(0,229,176,.03) 1px, transparent 1px);
		background-size: 40px 40px;
		pointer-events: none;
	  }

	  .wrap {
		position: relative; z-index: 1;
		max-width: 780px;
		margin: 0 auto;
		padding: 48px 24px 80px;
	  }

	  /* Header */
	  .logo {
		font-family: var(--head);
		font-size: 13px;
		font-weight: 700;
		letter-spacing: .18em;
		text-transform: uppercase;
		color: var(--accent);
		margin-bottom: 48px;
		display: flex;
		align-items: center;
		gap: 10px;
	  }
	  .logo::before {
		content: '';
		display: block;
		width: 8px; height: 8px;
		background: var(--accent);
		border-radius: 50%;
		box-shadow: 0 0 8px var(--accent);
		animation: pulse 2s ease-in-out infinite;
	  }
	  @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }

	  /* Steps indicator */
	  .steps {
		display: flex;
		align-items: center;
		gap: 0;
		margin-bottom: 48px;
	  }
	  .step-item {
		display: flex; align-items: center; gap: 10px;
		font-size: 11px; letter-spacing: .12em; text-transform: uppercase;
		color: var(--muted);
		font-weight: 500;
	  }
	  .step-item.active  { color: var(--accent); }
	  .step-item.done    { color: var(--text2); }
	  .step-num {
		width: 26px; height: 26px; border-radius: 50%;
		display: flex; align-items: center; justify-content: center;
		border: 1px solid var(--border2);
		font-size: 11px; font-weight: 700;
		background: var(--bg2);
		transition: all .3s;
	  }
	  .step-item.active .step-num {
		border-color: var(--accent);
		color: var(--accent);
	  }
	  .step-item.done .step-num {
		border-color: var(--border2);
		background: var(--bg3);
	  }
	  .step-sep {
		flex: 1; height: 1px;
		background: linear-gradient(90deg, var(--border2), var(--border));
		margin: 0 14px;
	  }

	  /* Card */
	  .card {
		background: var(--bg2);
		border: 1px solid var(--border);
		border-radius: 4px;
		overflow: hidden;
	  }
	  .card-head {
		padding: 28px 32px 24px;
		border-bottom: 1px solid var(--border);
		background: linear-gradient(135deg, var(--bg3) 0%, var(--bg2) 100%);
	  }
	  .card-head h1 {
		font-family: var(--head);
		font-size: 26px; font-weight: 800;
		color: var(--white);
		line-height: 1.2;
		margin-bottom: 8px;
	  }
	  .card-head p {
		color: var(--text2);
		font-size: 13px;
		max-width: 540px;
		line-height: 1.7;
	  }
	  .card-body { padding: 32px; }

	  /* Accent line on card */
	  .card::after {
		content: none;
	  }
	  .card-head::before {
		content: '';
		display: block;
		width: 40px; height: 2px;
		background: linear-gradient(90deg, var(--accent), var(--accent2));
		margin-bottom: 16px;
	  }

	  /* Feature bullets (step 1) */
	  .features {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 12px;
		margin-top: 20px;
	  }
	  .feature {
		display: flex; align-items: flex-start; gap: 10px;
		padding: 14px 16px;
		border: 1px solid var(--border);
		border-radius: 3px;
		background: var(--bg3);
		transition: border-color .2s;
	  }
	  .feature:hover { border-color: var(--border2); }
	  .feature-icon { color: var(--accent); font-size: 16px; margin-top: 1px; flex-shrink: 0; }
	  .feature-text strong { display: block; color: var(--white); font-size: 12px; font-weight: 700; margin-bottom: 2px; }
	  .feature-text span { color: var(--text2); font-size: 11px; }

	  /* Warning banner */
	  .banner {
		padding: 12px 16px;
		border-radius: 3px;
		border: 1px solid;
		margin-bottom: 20px;
		font-size: 12px;
		display: flex; align-items: flex-start; gap: 10px;
	  }
	  .banner.warn  { border-color: var(--warn); background: lime; color: var(--warn); }
	  .banner.error { border-color: var(--danger); background: red; color: white; }
	  .banner.info  { border-color: var(--accent); border: none; background: var(--bg2); color: lime; }
	  .banner-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }
	  .banner ul { list-style: none; padding: 0; margin: 4px 0 0; }
	  .banner ul li::before { content: '→ '; }

	  /* Module table */
	  .mod-table { width: 100%; border-collapse: collapse; }
	  .mod-table th {
		text-align: left; font-size: 10px; letter-spacing: .14em;
		text-transform: uppercase; color: var(--muted);
		padding: 8px 12px; border-bottom: 1px solid var(--border);
		font-weight: 500;
	  }
	  .mod-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
	  .mod-table tr:last-child td { border-bottom: none; }
	  .mod-table tr:hover td { background: rgba(255,255,255,.02); }
	  .mod-name { color: var(--white); font-weight: 500; font-size: 13px; }
	  .mod-note { color: var(--muted); font-size: 11px; }
	  .badge {
		display: inline-flex; align-items: center; gap: 5px;
		padding: 3px 9px; border-radius: 2px;
		font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
	  }
	  .badge.ok       { background: lime;  color: black;  border: 1px solid rgba(0,229,176,.2); }
	  .badge.fail     { background: red;  color: white;  border: 1px solid rgba(255,69,96,.2); }
	  .badge.optional { background: rgba(245,166,35); color: black;    border: 1px solid rgba(245,166,35,.2); }
	  .badge::before { content: '●'; font-size: 7px; }

	  /* PHP version row */
	  .php-info {
		display: flex; gap: 16px; flex-wrap: wrap;
		margin-bottom: 24px;
	  }
	  .php-chip {
		padding: 6px 14px; border: 1px solid var(--border);
		border-radius: 3px; background: var(--bg3);
		font-size: 12px; color: var(--text2);
	  }
	  .php-chip span { color: var(--white); font-weight: 700; }

	  /* Form */
	  .form-group { margin-bottom: 20px; }
	  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
	  label {
		display: block; margin-bottom: 6px;
		font-size: 10px; letter-spacing: .12em; text-transform: uppercase;
		color: var(--text2); font-weight: 500;
	  }
	  label .req { color: var(--danger); margin-left: 3px; }
	  input[type=text], input[type=password], input[type=number] {
		width: 100%;
		background: var(--bg);
		border: 1px solid var(--border);
		border-radius: 3px;
		padding: 10px 14px;
		color: var(--white);
		font-family: var(--mono);
		font-size: 13px;
		outline: none;
		transition: border-color .2s, box-shadow .2s;
	  }
	  input:focus {
		border-color: var(--accent);
	  }
	  input::placeholder { color: var(--muted); }

	  /* Method selector */
	  .methods {
		display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;
		margin-bottom: 20px;
	  }
	  .method-opt { position: relative; }
	  .method-opt input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
	  .method-label {
		display: flex; flex-direction: column; align-items: center;
		padding: 14px 8px; border: 1px solid var(--border);
		border-radius: 3px; background: var(--bg3);
		cursor: pointer; transition: all .2s;
		text-align: center; gap: 6px;
	  }
	  .method-label .ml-icon { font-size: 20px; }
	  .method-label .ml-name {
		font-size: 10px; font-weight: 700; letter-spacing: .1em;
		text-transform: uppercase; color: var(--text2);
		transition: color .2s;
	  }
	  .method-opt input:checked + .method-label {
		border-color: var(--accent);
	  }
	  .method-opt input:checked + .method-label .ml-name { color: var(--accent); }
	  .method-label:hover { border-color: var(--border2); }

	  /* Custom fields panel */
	  .custom-panel {
		display: none;
		background: var(--bg);
		border: 1px solid var(--border);
		border-radius: 3px;
		padding: 24px;
		margin-bottom: 20px;
	  }
	  .custom-panel.visible { display: block; }
	  .custom-panel h3 {
		font-size: 10px; letter-spacing: .15em; text-transform: uppercase;
		color: var(--accent); margin-bottom: 20px; font-weight: 700;
	  }

	  /* Section divider */
	  .sect {
		font-size: 10px; letter-spacing: .15em; text-transform: uppercase;
		color: var(--muted); margin: 28px 0 16px;
		display: flex; align-items: center; gap: 10px;
	  }
	  .sect::after { content: ''; flex: 1; height: 1px; background: var(--border); }

	  /* Toggle */
	  .toggle-row {
		display: flex; align-items: center; justify-content: space-between;
		padding: 10px 0;
		border-bottom: 1px solid var(--border);
	  }
	  .toggle-row:last-child { border-bottom: none; }
	  .toggle-info { font-size: 12px; color: var(--text); }
	  .toggle-info small { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }
	  .toggle {
		position: relative; width: 40px; height: 22px; flex-shrink: 0;
	  }
	  .toggle input { opacity: 0; width: 0; height: 0; }
	  .toggle-track {
		position: absolute; inset: 0; border-radius: 11px;
		background: var(--border2); cursor: pointer; transition: background .2s;
	  }
	  .toggle-track::after {
		content: ''; position: absolute;
		top: 3px; left: 3px;
		width: 16px; height: 16px; border-radius: 50%;
		background: var(--muted); transition: all .2s;
	  }
	  .toggle input:checked + .toggle-track { background: rgba(0,229,176,.3); }
	  .toggle input:checked + .toggle-track::after {
		transform: translateX(18px);
		background: var(--accent);
	  }

	  /* Buttons */
	  .btn-row { display: flex; justify-content: flex-end; gap: 10px; margin-top: 32px; }
	  .btn {
		padding: 11px 26px; border: none; border-radius: 3px;
		font-family: var(--mono); font-size: 12px; font-weight: 700;
		letter-spacing: .1em; text-transform: uppercase;
		cursor: pointer; transition: all .2s;
	  }
	  .btn-primary {
		background: var(--accent); color: var(--bg);
	  }
	  .btn-primary:hover { background: #FFFFFF; }
	  .btn-ghost {
		background: transparent; color: var(--text2);
		border: 1px solid var(--border);
	  }
	  .btn-ghost:hover { border-color: var(--border2); color: var(--text); }

	  /* Success */
	  .success-wrap { text-align: center; padding: 32px 0; }
	  .success-icon { font-size: 52px; margin-bottom: 20px; }
	  .success-wrap h2 {
		font-family: var(--head); font-size: 24px; font-weight: 800;
		color: var(--white); margin-bottom: 10px;
	  }
	  .success-wrap p { color: var(--text2); font-size: 13px; max-width: 440px; margin: 0 auto 24px; }
	  .code-badge {
		display: inline-block; padding: 8px 18px;
		background: var(--bg); border: 1px solid var(--accent);
		border-radius: 3px; color: var(--accent); font-size: 13px;
		margin-bottom: 28px; letter-spacing: .05em;
	  }
	  .note-box {
		background: rgba(255,69,96,.07); border: 1px solid rgba(255,69,96,.2);
		border-radius: 3px; padding: 14px 18px;
		color: var(--danger); font-size: 12px; max-width: 500px; margin: 0 auto;
		text-align: left; line-height: 1.7;
	  }

	  @media (max-width: 600px) {
		.form-row { grid-template-columns: 1fr; }
		.features { grid-template-columns: 1fr; }
		.methods  { grid-template-columns: repeat(3, 1fr); }
		.card-body { padding: 20px; }
		.card-head { padding: 20px; }
	  }
	  
	</style>
	</head>
	<body>
	<div class="wrap">

	  <div class="logo">Bind9 Web Manager &nbsp;·&nbsp; Setup Wizard  &nbsp;·&nbsp; by <a href="https://bugfishtm.github.io" style="color: white;" rel="noopener" target="_blank">Bugfish</a></div>

	  <?php if ($step !== 99): ?>
	  <div class="steps">
		<?php
		  $steps_info = [1 => 'Welcome', 2 => 'Requirements', 3 => 'Configure'];
		  foreach ($steps_info as $n => $label):
			$cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
		?>
		<div class="step-item <?php echo  $cls ?>">
		  <div class="step-num"><?php echo  $n < $step ? '✓' : $n ?></div>
		  <?php echo  $label ?>
		</div>
		<?php if ($n < 3): ?><div class="step-sep"></div><?php endif; ?>
		<?php endforeach; ?>
	  </div>
	  <?php endif; ?>

	  <?php /* ══════════════════════════════ STEP 1 ══════════════════════════════ */ ?>
	  <?php if ($step === 1): ?>
	  <div class="card">
		<div class="card-head">
		  <h1>Bind9 Web Manager</h1>
		  <p>An open-source, fully operational DNS panel by <strong style="color:var(--accent)">bugfish</strong>. Its primary focus is BIND9 DNS replication over HTTP — offering transparency, control, and seamless integration with your existing hosting stack.</p>
		</div>
		<div class="card-body">
		  <?php if ($configExists): ?>
		  <div class="banner warn">
			<span class="banner-icon">⚠</span>
			<div>A <strong>settings.php</strong> already exists. Running this installer will overwrite your existing configuration.</div>
		  </div>
		  <?php endif; ?>

		  <div style="color:var(--text2);font-size:12px;line-height:1.8;margin-bottom:20px;padding:16px;border:1px solid var(--border);border-radius:3px;background:var(--bg3)">
			Designed to streamline and enhance DNS replication, this software works harmoniously with
			<span style="color:var(--white)">Plesk</span>,
			<span style="color:var(--white)">Virtualmin</span>,
			<span style="color:var(--white)">ISPConfig</span>,
			and as a <span style="color:var(--white)">standalone</span> solution.
			Tested on Debian 8&ndash;11/12 and Ubuntu 16&ndash;22/24 with multiple BIND versions.
			Built with the <span style="color:var(--accent)">Bugfish Framework</span> &middot; License: GPLv3
		  </div>

		  <div class="features">
			<div class="feature">
			  <div class="feature-icon">⬡</div>
			  <div class="feature-text">
				<strong>Slave Server Replication</strong>
				<span>Real-time replication control between Master &amp; Slave servers with live status</span>
			  </div>
			</div>
			<div class="feature">
			  <div class="feature-icon">⬡</div>
			  <div class="feature-text">
				<strong>Master / Slave Hybrid</strong>
				<span>A single server can act as both Master and Slave for flexible DNS architecture</span>
			  </div>
			</div>
			<div class="feature">
			  <div class="feature-icon">⬡</div>
			  <div class="feature-text">
				<strong>User &amp; Rights Management</strong>
				<span>Granular per-user permissions with domain jailing for operational security</span>
			  </div>
			</div>
			<div class="feature">
			  <div class="feature-icon">⬡</div>
			  <div class="feature-text">
				<strong>IP Blacklisting</strong>
				<span>Automatic ban on failed logins and bad API tokens, resettable via daily cronjob</span>
			  </div>
			</div>
			<div class="feature">
			  <div class="feature-icon">⬡</div>
			  <div class="feature-text">
				<strong>Multi-Panel Support</strong>
				<span>Native presets for ISPConfig, Virtualmin/Webmin, Plesk, and standalone BIND9</span>
			  </div>
			</div>
			<div class="feature">
			  <div class="feature-icon">⬡</div>
			  <div class="feature-text">
				<strong>MySQL Backed</strong>
				<span>All logging, API tokens, and replication data stored in your database</span>
			  </div>
			</div>
		  </div>

		  <form method="POST">
			<input type="hidden" name="step" value="1">
			<div class="btn-row">
			  <button type="submit" class="btn btn-primary">Begin Setup →</button>
			</div>
		  </form>
		</div>
	  </div>

	  <?php /* ══════════════════════════════ STEP 2 ══════════════════════════════ */ ?>
	  
	  <?php elseif ($step === 2): ?>
	  <div class="card">
		<div class="card-head">
		  <h1>System Requirements</h1>
		  <p>Checking your PHP environment. Required extensions must be enabled before proceeding.</p>
		</div>
		<div class="card-body">

		  <div class="php-info">
			<div class="php-chip">PHP Version: <span><?php echo  e($phpVersion) ?></span></div>
			<div class="php-chip">Status:
			  <span style="color:<?php echo  $phpVersionOk ? 'var(--accent)' : 'var(--danger)' ?>">
				<?php echo  $phpVersionOk ? 'OK (≥ 8.2.0)' : 'Requires PHP 8.2+' ?>
			  </span>
			</div>
		  </div>

		  <?php if ($criticalFail) { ?>
			  <div class="banner error">
				<span class="banner-icon">✕</span>
				<div>One or more <strong>required</strong> extensions are missing. Please install them and reload this page.</div>
			  </div>
		  <?php } elseif(!$phpVersionOk) { ?>
			  <div class="banner error">
				<span class="banner-icon">✕</span>
				<div>Please check the PHP Version on your webserver.</div>
			  </div>
		  <?php } elseif($reqFail) { ?>
			  <div class="banner error">
				<span class="banner-icon">✕</span>
				<div>Please check the PHP Requirements on your webserver.</div>
			  </div>
		  <?php } else { ?>
			  <div class="banner info">
				<span class="banner-icon">✓</span>
				<div>All required extensions are present. You may proceed.</div>
			  </div>
		  <?php } ?>	  
		  
		  <table class="mod-table">
			<thead>
			  <tr>
				<th>Extension</th>
				<th>Description</th>
				<th>Status</th>
			  </tr>
			</thead>
			<tbody>
			  <tr>
				<td class="mod-name">PHP Memory Limit</td>
				<td class="mod-note">Minimum 128mb / Recommendation 256mb <br />Be sure the cronjob running under<br /> root is running with that setup</td>
				<td>
					<?php
						if($mem >= 256) {  echo '<span class="badge ok">OK</span>'; }
						elseif($mem >= 128) { echo '<span class="badge optional">PASSED</span>'; }
						else { echo '<span class="badge fail">FAIL</span>'; }
					?>
				</td>
			  </tr>			
			  <tr>
				<td class="mod-name">PHP Max Execution Time</td>
				<td class="mod-note">Minimum 180s / Recommendation 600s <br />Be sure the cronjob running under<br /> root is running with that setup</td>
				<td>
					<?php
						if($exe >= 600) {  echo '<span class="badge ok">OK</span>'; }
						elseif($exe >= 180) { echo '<span class="badge optional">PASSED</span>'; }
						else { echo '<span class="badge fail">FAIL</span>'; }
					?>
				</td>
			  </tr>		
			  <tr>
				<td class="mod-name">PHP Post Max Size</td>
				<td class="mod-note">Minimum 128mb / Recommendation 256mb <br />Be sure the cronjob running under<br /> root is running with that setup</td>
				<td>
					<?php
						if($post >= 256) {  echo '<span class="badge ok">OK</span>'; }
						elseif($post >= 128) { echo '<span class="badge optional">PASSED</span>'; }
						else { echo '<span class="badge fail">FAIL</span>'; }
					?>
				</td>
			  </tr>		
			  <?php foreach ($modules as $m): ?>
			  <tr>
				<td class="mod-name"><?php echo  e($m['name']) ?></td>
				<td class="mod-note"><?php echo  e($m['note']) ?></td>
				<td>
				  <?php if ($m['check']): ?>
					<span class="badge ok">Loaded</span>
				  <?php elseif ($m['required']): ?>
					<span class="badge fail">Missing</span>
				  <?php else: ?>
					<span class="badge optional">Optional</span>
				  <?php endif; ?>
				</td>
			  </tr>
			  <?php endforeach; ?>
			</tbody>
		  </table>

		  <form method="POST">
			<input type="hidden" name="step" value="2">
			<div class="btn-row">
			  <?php if (!$criticalFail && $phpVersionOk && !$reqFail):  ?>
			  <button type="submit" class="btn btn-primary">Continue →</button>
			  <?php else: ?>
			  <button type="button" onclick="location.reload()" class="btn btn-ghost">Recheck</button>
			  <?php endif; ?>
			</div>
		  </form>
		</div>
	  </div>
	  
	  <?php /* ══════════════════════════════ STEP 3 ══════════════════════════════ */ ?>
	  <?php elseif ($step === 3): ?>
	  <div class="card">
		<div class="card-head">
		  <h1>Configuration</h1>
		  <p>Enter your database credentials and choose your server setup. A <code>settings.php</code> will be written to the web root.</p>
		</div>
		<div class="card-body">

		  <?php if (!empty($errors)): ?>
		  <div class="banner error">
			<span class="banner-icon">✕</span>
			<div>
			  <strong>Please correct the following:</strong>
			  <ul>
				<?php foreach ($errors as $err): ?>
				<li><?php echo  e($err) ?></li>
				<?php endforeach; ?>
			  </ul>
			</div>
		  </div>
		  <?php endif; ?>

		  <form method="POST" id="install-form">
			<input type="hidden" name="step" value="3">

			<div class="sect">Site Settings</div>

			<div class="form-row">
			  <div class="form-group">
				<label>Website Title <span class="req">*</span></label>
				<input type="text" name="title" value="<?php echo  old('title') ?>" placeholder="My DNS Server" required>
			  </div>
			  <div class="form-group">
				<label>Impressum URL</label>
				<input type="text" name="impressum" value="<?php echo  old('impressum') ?>" placeholder="https://example.com/impressum">
			  </div>
			</div>

			<div class="form-row">
			  <div class="form-group">
				<label>Server FQDN (Hostname) <span class="req">*</span></label>
				<input type="text" name="hostname" value="<?php echo old('hostname') ?>" placeholder="server.my.domain" required>
			  </div>
			</div>
			
			<div class="sect">MySQL Database</div>

			<div class="form-row">
			  <div class="form-group">
				<label>Host <span class="req">*</span></label>
				<input type="text" name="sql_host" value="<?php echo getenv('sf_db_host'); ?>" placeholder="<?php echo getenv('sf_db_host'); ?>" required>
			  </div>
			  <div class="form-group">
				<label>Database Name <span class="req">*</span></label>
				<input type="text" name="sql_db" value="<?php echo getenv('sf_db_db'); ?>" placeholder="<?php echo getenv('sf_db_db'); ?>" required>
			  </div>
			</div>
			<div class="form-row">
			  <div class="form-group">
				<label>Username <span class="req">*</span></label>
				<input type="text" name="sql_user" value="<?php echo getenv('sf_db_user'); ?>" placeholder="<?php echo getenv('sf_db_user'); ?>" required>
			  </div>
			  <div class="form-group">
				<label>Password</label>
				<input type="password" name="sql_pass" placeholder="<?php echo getenv('sf_db_pass'); ?>" value="<?php echo getenv('sf_db_pass'); ?>">
			  </div>
			</div>

			<div class="sect">Server Setup</div>
			
			<p class="sect"><b>Standalone</b>Use DNSHTTP as standalone version, without other hosting systems as Master, Slave or Hybrid.</p>
			<p class="sect"><b>Virtualmin</b>Use DNSHTTP on top of Virtualmin to fetch domains and records from Virtualmin as Master, Slave or Hybrid.</p>
			<p class="sect"><b>ISPConfig</b>Use DNSHTTP on top of ISPConfig to fetch domains and records from ISPConfig as Master, Slave or Hybrid.</p>
			<p class="sect"><b>Plesk</b>Use DNSHTTP on top of Plesk to fetch domains and records from Plesk as Master, Slave or Hybrid.</p>
			<p class="sect"><b>Custom</b>Use a custom configuration, some settings may only be changed by manually editing the settings.php file.</p>

			<div class="methods" id="method-group">
			  <?php
				$methods = [
				  'standalone' => ['icon' => '◈', 'label' => 'Standalone'],
				];
				$selMethod = old('method', 'standalone');
				foreach ($methods as $val => $m):
			  ?>
			  <label class="method-opt">
				<input type="radio" name="method" value="<?php echo  $val ?>"
				  <?php echo  $selMethod === $val ? 'checked' : '' ?>
				  onchange="toggleCustom()">
				<div class="method-label">
				  <span class="ml-icon"><?php echo  $m['icon'] ?></span>
				  <span class="ml-name"><?php echo  $m['label'] ?></span>
				</div>
			  </label>
			  <?php endforeach; ?>
			</div>

			<div class="custom-panel <?php echo  $selMethod === 'custom' ? 'visible' : '' ?>" id="custom-panel">
			  <h3>⚙ Custom Bind Configuration</h3>
			  <div class="form-row">
				<div class="form-group">
				  <label>Named.conf Primary File</label>
				  <input type="text" name="bind_file" value="<?php echo  old('bind_file', '/etc/bind/named.conf.default-zones') ?>">
				</div>
				<div class="form-group">
				  <label>Named.conf Secondary File</label>
				  <input type="text" name="bind_file2" value="<?php echo  old('bind_file2', '/etc/bind/named.conf.local') ?>">
				</div>
			  </div>
			  <div class="form-row">
				<div class="form-group">
				  <label>DNSHTTP Storage Path</label>
				  <input type="text" name="bind_lib" value="<?php echo  old('bind_lib', '/etc/bind/dnshttp/') ?>">
				</div>
				<div class="form-group">
				  <label>named.conf Path</label>
				  <input type="text" name="bind_confname" value="<?php echo  old('bind_confname', '/etc/bind/named.conf') ?>">
				</div>
			  </div>
			  <div class="form-row">
				<div class="form-group">
				  <label>File Owner (User)</label>
				  <input type="text" name="bind_user" value="<?php echo  old('bind_user', 'bind') ?>">
				</div>
				<div class="form-group">
				  <label>File Owner (Group)</label>
				  <input type="text" name="bind_group" value="<?php echo  old('bind_group', 'bind') ?>">
				</div>
			  </div>
			  <div class="form-row">
				<div class="form-group">
				  <label>Chmod (e.g. 770)</label>
				  <input type="number" name="bind_code" value="<?php echo  old('bind_code', '770') ?>">
				</div>
				<div class="form-group">
				  <label>Bind Service Name</label>
				  <input type="text" name="bind_service" value="<?php echo  old('bind_service', 'bind9') ?>">
				</div>
			  </div>
			  <div class="form-row">
				<div class="form-group">
				  <label>Zone Folder Fetch Path <small style="text-transform:none;letter-spacing:0">(leave blank to disable)</small></label>
				  <input type="text" name="folder_fetch" value="<?php echo  old('folder_fetch') ?>" placeholder="/etc/bind/zones/">
				</div>
				<div class="form-group">
				  <label style="visibility:hidden">–</label>
				  <div style="display:flex;gap:10px">
					<div style="flex:1">
					  <label style="margin-bottom:4px">Strip Start</label>
					  <input type="number" name="folder_rmstart" value="<?php echo  old('folder_rmstart', '4') ?>">
					</div>
					<div style="flex:1">
					  <label style="margin-bottom:4px">Strip End</label>
					  <input type="number" name="folder_rmend" value="<?php echo  old('folder_rmend', '0') ?>">
					</div>
				  </div>
				</div>
			  </div>

			  <div class="toggle-row">
				<div class="toggle-info">
				  Rewrite Bind Config File
				  <small>Enable for Webmin/Virtualmin — writes all zones back to primary named.conf</small>
				</div>
				<label class="toggle">
				  <input type="checkbox" name="bind_rewrite" <?php echo  old('bind_rewrite') === 'true' ? 'checked' : '' ?>>
				  <span class="toggle-track"></span>
				</label>
			  </div>
			</div>

			<div class="btn-row">
			  <button type="submit" class="btn btn-primary">Install &amp; Create Config →</button>
			</div>
		  </form>
		  
		  <br clear="left">Below you can find constants, you can set this constants manually by editing the ./_data/settings.php file which is created after this installation. Be very cautious when doing so.
		  <br clear="left">
			<table class="table table-striped">
			  <thead>
				<tr>
				  <th style="width: 180px">Constant</th>
				  <th>Description</th>
				  <th style="width: 100px">Value</th>
				</tr>
			  </thead>
			  <tbody>
				<tr class="align-middle">
					<td>_TITLE_</td>
					<td>Website Title</td>
				</tr>	
				<tr class="align-middle">
					<td>_HELP_</td>
					<td>Help URL</td>
				</tr>			  
				<tr class="align-middle">
					<td>_DNSHTTP_LOGO_</td>
					<td>Logo Relative URL</td>
				</tr>
				<tr class="align-middle">
					<td>_DNSHTTP_LOGIN_BG_</td>
					<td>Login Background Relative URL</td>
				</tr>
				<tr class="align-middle">
					<td>_DNSHTTP_FAVICON_</td>
					<td>Favicon Relative URL</td>
				</tr>
				<tr class="align-middle">
					<td>_FOOTER_</td>
					<td>Footer Text</td>
				</tr>
				<tr class="align-middle">
					<td>_COOKIEBANNER_TEXT_</td>
					<td>Cookiebanner Text</td>
				</tr>
				<tr class="align-middle">
					<td>_IMPRESSUM_</td>
					<td>Impressum URL</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_FILE_</td>
					<td>Primary bind9 config file listing local zones (named.conf.local).</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_FILE_2_</td>
					<td>Secondary bind9 config file for default zones.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_</td>
					<td>Characters to strip from the start of zone filenames to extract the domain name.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_</td>
					<td>Characters to strip from the end of zone filenames.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_FILES_FOLDER_FETCH_</td>
					<td>Folder to scan for zone files. Set to /etc/bind/zones/ for ISPConfig, false to disable.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_FILE_REWRITE_</td>
					<td>Set to true when using Webmin/Virtualmin to rewrite the zone config file automatically.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_USER_</td>
					<td>Linux user that owns generated zone files.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_GROUP_</td>
					<td>Linux group that owns generated zone files.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_CODE_</td>
					<td>chmod permission number for generated zone files (default 770).</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_ENDING_</td>
					<td>File extension for generated zone files (default .dnshttp).</td>
				</tr>
				<tr class="align-middle">
					<td>_BIND_SERVICE_NAME_</td>
					<td>System service name of your nameserver (bind9 or named).</td>
				</tr>
				<tr class="align-middle">
					<td>_BIND_CHECKZONE_COMMAND_</td>
					<td>Path to named-checkzone binary.</td>
				</tr>
				<tr class="align-middle">
					<td>_BIND_COMPILEZONE_COMMAND_</td>
					<td>Path to named-compilezone binary.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_LIB_</td>
					<td>Directory where generated DNS zone files are stored.</td>
				</tr>
				<tr class="align-middle">
					<td>_CRON_BIND_CONFNAME_</td>
					<td>Path to the main named.conf file.</td>
				</tr>
				<tr class="align-middle">
					<td>_COOKIES_</td>
					<td>Default Prefix for Session Cookies</td>
				</tr>
				<tr class="align-middle">
					<td>_IP_BLACKLIST_DAILY_OP_LIMIT_</td>
					<td>Default Limit Failures of an IP Adress with Token or Login before Block</td>
				</tr>
				<tr class="align-middle">
					<td>_CSRF_VALID_LIMIT_TIME_</td>
					<td>Default time in seconds CSRF Codes are Valid</td>
				</tr>
				<tr class="align-middle">
					<td>_USER_AUTOBLOCK_</td>
					<td>Default Limit for Wrong User Passwords in a Row before Auto-Block</td>
				</tr>
				<tr class="align-middle">
					<td>_SERVER_HOSTNAME_</td>
					<td>Default Entry for SOA Record: Primary Hostname (Master Domains)</td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_EXPIRE_</td>
					<td>Default Entry for SOA Record: Expiry Time</td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_RETRY_</td>
					<td>Default Entry for SOA Record: Retry Rate</td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_REFRESH_</td>
					<td>Default Entry for SOA Record: Refresh Rate</td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_MINIMUM_</td>
					<td>Default Entry for SOA Record: Minimum TTL</td>
				</tr>
				<tr class="align-middle">
					<td>_USER_DOMAIN_MAIL_</td>
					<td>Default Entry for SOA Record: Postmaster Mail (Responsible Party)</td>
				</tr>
			  </tbody>
			</table>		  
		  
		  
		</div>
	  </div>
	  
	  <?php /* ══════════════════════════════ SUCCESS ══════════════════════════════ */ ?>
	  <?php elseif ($step === 99): ?>
	  <div class="card">
		<div class="card-body">
		  <div class="success-wrap">
			<div class="success-icon">◈</div>
			<h2>Installation Complete</h2>
			<p style="margin-bottom:16px">Your configuration has been written successfully.<br />
			DNSHTTP is ready. Log in to the panel to continue setup.</p>
			<a href="./" class="btn btn-primary">Click here to Login</a>
		  </div>
		</div>
	  </div>
	  <?php endif; ?>

	</div><!-- .wrap -->

	<script>
	function toggleCustom() {
	  const checked = document.querySelector('input[name="method"]:checked');
	  const panel   = document.getElementById('custom-panel');
	  if (!checked || !panel) return;
	  panel.classList.toggle('visible', checked.value === 'custom');
	}
	// Init on load (in case of error repopulation)
	document.addEventListener('DOMContentLoaded', toggleCustom);
	</script>
	</body>
	</html>
