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
		Function Library
	*************************************************************************/
	function dnshttp_compress(string $text): string {
		return base64_encode(gzcompress($text, 9));
	}
	
	function incrementSOASerial(string $serial): string {
		$today = date('Ymd');
		if (strlen($serial) === 10 && substr($serial, 0, 8) === $today) {
			$seq = (int)substr($serial, 8) + 1;
			if ($seq <= 99) {
				return $today . str_pad($seq, 2, '0', STR_PAD_LEFT);
			}
			// >99 edits today: bump to tomorrow's date so the serial keeps rising
			$next = date('Ymd', strtotime('+1 day')) . '01';
			return (string)max((int)$next, (int)$serial + 1);
		}
		// Different/older format — never go backwards
		$new = $today . '01';
		return (int)$new > (int)$serial ? $new : (string)((int)$serial + 1);
	}

	// Matches the serial field of an SOA record, in both the parenthesised and the bare
	// single-line layout: SOA <mname> <rname> [(] <serial> ...
	// The previous pattern used [^\(]* with the /s modifier, so on a zone whose SOA has no
	// parentheses it walked across newlines to the next "(" anywhere in the file and rewrote
	// whatever number followed it - silently corrupting an unrelated record.
	define("_DNSHTTP_SOA_SERIAL_REGEX_", '/(\bSOA\b[^\S\r\n]+[^\s()]+[^\S\r\n]+[^\s()]+[^\S\r\n]*(?:\([^\d]*)?)(\d{1,10})\b/i');

	// Best-effort auto-increment of the SOA serial inside raw zone text so slaves pick up edits.
	function dnshttp_bump_zone_serial(string $zone): string {
		$out = preg_replace_callback(
			_DNSHTTP_SOA_SERIAL_REGEX_,
			function($m){ return $m[1].incrementSOASerial($m[2]); },
			$zone, 1
		);
		// On a pattern failure keep the zone byte-identical rather than returning null.
		return ($out === null) ? $zone : $out;
	}

	// Is this zone name already served by this instance, in any form? Returns 'local' for an entry
	// in the bind table (a master zone, or another user's domain), 'remote' for one replicated in
	// from a peer, or false.
	// Informational only - creating a duplicate is a supported operation, the conflict handling
	// covers it and an administrator picks the winner on the conflicts page. This exists so the
	// creating user can be told the new entry starts out in conflict, because until that is
	// resolved neither entry is written to the served configuration.
	function dnshttp_domain_served_by($mysql, $domain) {
		$b = array();
		$b[0]["value"] = strtolower(trim($domain ?? ''));
		$b[0]["type"]  = "s";
		if(is_array($mysql->select("SELECT id FROM "._TABLE_DOMAIN_BIND_." WHERE LOWER(TRIM(domain)) = ?", false, $b))) { return "local"; }
		if(is_array($mysql->select("SELECT id FROM "._TABLE_DOMAIN_API_." WHERE LOWER(TRIM(domain)) = ?", false, $b))) { return "remote"; }
		return false;
	}

	// Zone text submitted by a user. $INCLUDE is removed because it makes bind read arbitrary local
	// files on this server when the zone is written out - that is the whole reason this exists.
	// $GENERATE is deliberately left alone: it is a legitimate bind feature with no file access, and
	// removing it would take a capability away from anyone writing zone data through the API.
	// Note the record editor in the web UI does not round-trip $GENERATE, so a zone using it should
	// be maintained through the API rather than opened and saved in the editor.
	function dnshttp_zone_input_clean($zone) {
		return preg_replace('/^[ \t]*\$INCLUDE\b[^\r\n]*/im', '; $INCLUDE removed - not permitted in user zone data', (string)$zone);
	}

	// Read the current SOA serial out of raw zone text (same match as the bump above).
	function dnshttp_zone_get_serial(string $zone) {
		if(preg_match(_DNSHTTP_SOA_SERIAL_REGEX_, $zone, $m)) { return $m[2]; }
		return false;
	}

	function dnshttp_decompress(string $compressed): string {
		// Compression between instances is intentionally disabled - passthrough!
		return $compressed;
		return gzuncompress(base64_decode($compressed));
	}
	
	function dnshttp_api_output_ok()				{ return "online"; }
	
	function dnshttp_api_output_install_error() 	{ return "install-error"; }
	
	function dnshttp_api_output_blacklist_error() 	{ return "ip-blacklisted"; }
	
	function dnshttp_api_blacklist_check($ipbl) 	{ if($ipbl->isblocked()) { echo "ip-blacklisted"; exit(); } }
	
	function dnshttp_api_token_check($mysql, $ipbl, $token) 		{ 
		if(!dnshttp_api_token_valid($mysql, $token, $ipbl)) {
			$ipbl->raise(); 
			echo "token-error";
			exit();
		}
	}
	
	function dnshttp_api_token_valid($mysql, $token, $ipbl) {
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token ?? '');
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE TRIM(api_token) = ?", false, $bind);
		if(is_array($res)) { return true;}  else { return false; }}

	function dnshttp_server_get($mysql, $id) {
		if(is_numeric($id)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE id = '$id'", false);
		while (is_array($x)) { return $x; } } return false; }
		
	function dnshttp_server_check_and_set($mysql, $checkserverid) { server_check_and_set($mysql, $checkserverid); }
	
	function server_check_and_set($mysql, $checkserverid) {
		$checkserverrow	=	dnshttp_server_get($mysql, $checkserverid);
		if(!is_array($checkserverrow)) { return; }
		$apipathroot	=	$checkserverrow["api_path"];
		$sslinsecurenow	=	$checkserverrow["ssl_insecure"] ?? 1;
		$apipath		=	$apipathroot."/_api/list_count.php";
		$returncurl 	=   dnshttp_api_getcontent($mysql, $apipath, $checkserverrow["api_token"], "", $sslinsecurenow);

		if(trim($returncurl ?? '') == "token-error") {
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 1 WHERE id = \"".$checkserverid."\";");
		}

		if(trim($returncurl ?? '') == "ip-blacklisted") {
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 1 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";"); 
		}
		
		if(trim($returncurl ?? '') == "install-error") { 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";"); 
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";"); 
		}
			
		$listcountok = false;
		if(is_numeric($returncurl)) {
			$listcountok = true;
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 1 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET domains = ".$returncurl." WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 1 WHERE id = \"".$checkserverid."\";");
			if($returncurl > 0) {
				$mysql->query("UPDATE "._TABLE_SERVER_." SET emptydomains = 0 WHERE id = \"".$checkserverid."\";");
			}
		} else {
			$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET weblacklisted = 0 WHERE id = \"".$checkserverid."\";");
			$mysql->query("UPDATE "._TABLE_SERVER_." SET tokenbadlastreq = 0 WHERE id = \"".$checkserverid."\";");
		}

		// Only upgrade to "Compressed" status if the authenticated probe above succeeded,
		// status_compress.php answers without a token so it must not overrule a failed check.
		if($listcountok) {
			$apipath		=	$apipathroot."/_api/status_compress.php";
			$returncurl =   dnshttp_api_getcontent($mysql, $apipath, $checkserverrow["api_token"], "", $sslinsecurenow);

			if(trim($returncurl ?? '') == "online") {
				$mysql->query("UPDATE "._TABLE_SERVER_." SET apiok = 2 WHERE id = \"".$checkserverid."\";");
			}
		}
	}
	
	// Lookup for the sync write of locally fetched (file/file2/folder) domains. User created
	// domains are excluded, they own their row and same-named entries are handled as conflicts.
	function dnshttp_bind_domain_name_exists($mysql, $domain_name) { if(trim($domain_name ?? '') != "") {
		$bind[0]["value"] = strtolower(trim($domain_name ?? ''));
		$bind[0]["type"] = "s";
		$x = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE TRIM(LOWER(domain)) = ? AND domain_type <> 'user'", false, $bind);
		if (is_array($x)) { return $x["id"]; } } return false; }
		
	function dnshttp_bind_get($mysql, $id) { if(is_numeric($id)) { return $mysql->select("SELECT * FROM "._TABLE_DOMAIN_BIND_." WHERE id = \"".$id."\"", false); } return false; }	
	
	function dnshttp_domapi_get($mysql, $id) { if(is_numeric($id)) { return $mysql->select("SELECT * FROM "._TABLE_DOMAIN_API_." WHERE id = \"".$id."\"", false); } return false; }	
	
	function dnshttp_user_get_name_from_id($mysql, $userid) { 
		if(is_numeric($userid)) { 
		$x = $mysql->select("SELECT * FROM "._TABLE_USER_." WHERE id = '$userid'", false);
		while (is_array($x)) { return $x["user_name"]; } } return false; }	
		
	function dnshttp_api_token_generate($len = 128, $comb = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
		{$pass = array(); $combLen = strlen($comb) - 1; for ($i = 0; $i < $len; $i++) { $n = random_int(0, $combLen); $pass[] = $comb[$n]; } return implode($pass);}		
	
	function dnshttp_server_api_token_check($mysql, $token) { 
		return api_token_check($mysql, $token);
	}
	
	
	function api_token_check($mysql, $token) {
		$bind[0]["type"]	=	"s";
		$bind[0]["value"]	=	trim($token ?? '');
		$res = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE TRIM(api_token) = ?", false, $bind);
		if(is_array($res)) { return true;}  else { return false; }}
		
	function dnshttp_api_getcontent($mysql, $url, $token = "", $domain = "", $sslinsecure = 1) { return api_request($mysql, $url, $token, $domain, $sslinsecure); }

	function api_request($mysql, $url, $token = "", $domain = "", $sslinsecure = 1) {
		  // Legacy rows without the ssl_insecure field keep the previous behaviour (no verification)
		  if($sslinsecure === null || $sslinsecure === '') { $sslinsecure = 1; }
		  $fields = array(
			'token'=>urlencode($token),
			'domain'=>urlencode(trim($domain)),
			'server'=>"" );
		  $fields_string = "";
		  //url-ify the data for the POST
		  foreach($fields as $key=>$value) { @$fields_string .= $key.'='.$value.'&'; }
		  $fields_string = rtrim($fields_string,'&');
		  // Initialize curl
		  $ch = curl_init();
		  //set the url, number of POST vars, POST data
		  curl_setopt($ch,CURLOPT_URL,$url);
		  if(is_string($token)) {curl_setopt($ch,CURLOPT_POST,count($fields));}
		  if(is_string($token)) {curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);}
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		  if($sslinsecure) {
			  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		  } else {
			  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		  }
		  //execute post
		  $result = curl_exec($ch);
		  return $result;
	}

	function dnshttp_isValidDomain(string $domain): bool {
		// Handle IDN (ö, ü, etc.)
		$ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
		if ($ascii === false) return false;

		return filter_var($ascii, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
			&& substr_count($ascii, '.') >= 1; // must have at least SLD + TLD
	}
	
	function dnshttp_conflicts_helper($srv) {
		$string = "";
		if($newarray = unserialize($srv, ['allowed_classes'=>false]) ) {
			foreach($newarray as $key => $value) {
				if(isset($value["domain_type"])) { $asd = $value["domain_type"]; } else { $asd = "Slave"; }
				if(isset($value["fk_server"])) {if($value["fk_server"] > 0) {  $asasd = " | Server-ID ".$value["fk_server"]; } else { $asasd = ""; } } else { $asasd = ""; }
				if(isset($value["fk_user"])) { if($value["fk_user"] > 0) { $asasd21 = " | User-ID ".$value["fk_user"]; } else { $asasd21 = ""; } } else { $asasd21 = ""; }
				$string .= "Type: ".@$asd.$asasd.$asasd21."<br />";
			}
			return $string;
		} else {
			return "Unknown";
		}
 	}
	
	/*************************************************************************
		DNSSEC Support
	*************************************************************************/
	// Placeholder that marks the spot inside a generated zone stanza where the DNSSEC options go.
	// The stanzas are built while the domains are written, but whether the options may actually be
	// emitted is only decided afterwards (see the named-checkconf fallback in the cronjob), so the
	// decision is kept out of the loop and resolved on the finished string.
	define("_DNSHTTP_DNSSEC_TOKEN_", "@@DNSHTTP-DNSSEC-ZONE-OPTIONS@@");

	// Locate a bind utility that has no setting of its own. The neighbour of the configured
	// named-compilezone is the first guess, but distributions split these tools over /usr/sbin and
	// /usr/bin (the installer itself defaults to different directories for docker and bare metal),
	// so the usual locations are tried after it. An installation that keeps them somewhere else
	// entirely can still name each binary explicitly in settings.php.
	function dnshttp_bind_tool_path($tool) {
		$base = "";
		if(defined("_BIND_COMPILEZONE_COMMAND_")) { $base = trim((string)_BIND_COMPILEZONE_COMMAND_); }
		if($base === "" AND defined("_BIND_CHECKZONE_COMMAND_")) { $base = trim((string)_BIND_CHECKZONE_COMMAND_); }
		$dirs = array();
		if($base !== "") {
			$dir = dirname($base);
			if($dir !== "" AND $dir !== "." AND $dir !== "/") { array_push($dirs, $dir); }
		}
		array_push($dirs, "/usr/sbin", "/usr/bin", "/usr/local/sbin", "/usr/local/bin");
		foreach($dirs as $dir) {
			if(@is_file($dir."/".$tool)) { return $dir."/".$tool; }
		}
		return $dirs[0]."/".$tool;
	}

	// Installed bind version as "major.minor.patch", or false when it cannot be determined.
	// Cached for a day in _data: this is asked on page views as well, and a shell_exec per request
	// would be wasteful - the web SAPI may not even be permitted to run one.
	function dnshttp_bind_version() {
		static $cached = null;
		if($cached !== null) { return $cached; }
		// Kept under _data/_logs rather than _data: the root .htaccess blocks _default, _cronjob,
		// _initialize, _template and _site plus settings.php by name, but NOT the rest of _data -
		// while _data/_logs carries its own deny rule that initialize.php restores when missing.
		// The bind version is exactly what the generated named.conf hides with 'version "None
		// Available";', so it must not be readable over http.
		$cachefile = _MAIN_PATH_."/_data/_logs/bind_version.cache";
		$age = @is_file($cachefile) ? (time() - (int)@filemtime($cachefile)) : false;
		if($age !== false) {
			$c = trim((string)@file_get_contents($cachefile));
			// A successful detection is stable and worth keeping for a day. A failed one is not:
			// the web SAPI is often not allowed to run shell_exec while the cronjob is, and caching
			// that failure for a day would hide the version the cronjob can read perfectly well.
			if($c !== "" AND $c !== "unknown" AND $age < 86400) { $cached = $c; return $cached; }
			if($c === "unknown" AND $age < 300) { $cached = false; return $cached; }
		}
		$raw = "";
		if(function_exists("shell_exec") AND defined("_BIND_CHECKZONE_COMMAND_")) {
			$raw = (string)@shell_exec(_BIND_CHECKZONE_COMMAND_." -v 2>&1");
		}
		$cached = false;
		// "9.18.28-0ubuntu0.22.04.1-Ubuntu", "named-checkzone 9.11.3" and plain "9.20.4" all match.
		if(preg_match('/(\d+)\.(\d+)(?:\.(\d+))?/', $raw, $m)) {
			$cached = ((int)$m[1]).".".((int)$m[2]).".".((int)(isset($m[3]) ? $m[3] : 0));
		}
		@file_put_contents($cachefile, ($cached === false) ? "unknown" : $cached);
		return $cached;
	}

	function dnshttp_bind_version_atleast($major, $minor) {
		$v = dnshttp_bind_version();
		if($v === false) { return false; }
		$p = explode(".", $v);
		$vmaj = (int)@$p[0]; $vmin = (int)@$p[1];
		if($vmaj != $major) { return ($vmaj > $major); }
		return ($vmin >= $minor);
	}

	// Which DNSSEC syntax the installed bind understands:
	//   'policy' - dnssec-policy, bind 9.16 and newer
	//   'legacy' - auto-dnssec maintain, bind 9.9 up to 9.18 (removed in 9.19/9.20)
	//   false    - emit no DNSSEC configuration at all
	// An unknown bind version deliberately yields false. Writing the wrong one of those two into a
	// zone stanza is a configuration error, and named rejects the WHOLE generated file over it -
	// that takes every zone on this server down, which is far worse than a zone staying unsigned.
	// _BIND_DNSSEC_MODE_ pins the syntax explicitly for a setup where detection cannot work.
	function dnshttp_dnssec_mode() {
		if(!defined("_BIND_DNSSEC_") OR !_BIND_DNSSEC_) { return false; }
		$mode = strtolower(trim((string)(defined("_BIND_DNSSEC_MODE_") ? _BIND_DNSSEC_MODE_ : "auto")));
		if($mode === "policy" OR $mode === "legacy") { return $mode; }
		if($mode !== "auto") { return false; }
		if(dnshttp_bind_version() === false) { return false; }
		if(dnshttp_bind_version_atleast(9, 16)) { return "policy"; }
		if(dnshttp_bind_version_atleast(9, 9))  { return "legacy"; }
		return false;
	}

	// Human readable reason why signing is unavailable - shown in the interface so an administrator
	// does not have to guess why the switch does nothing.
	function dnshttp_dnssec_unavailable_reason() {
		if(!defined("_BIND_DNSSEC_") OR !_BIND_DNSSEC_) { return "DNSSEC is switched off for this instance (_BIND_DNSSEC_ in settings.php)."; }
		$mode = strtolower(trim((string)(defined("_BIND_DNSSEC_MODE_") ? _BIND_DNSSEC_MODE_ : "auto")));
		# 'off' is a documented value, so it gets its own answer rather than being reported as a typo.
		if($mode === "off") { return "DNSSEC is switched off for this instance (_BIND_DNSSEC_MODE_ is 'off' in settings.php)."; }
		if($mode !== "auto" AND $mode !== "policy" AND $mode !== "legacy") { return "_BIND_DNSSEC_MODE_ in settings.php is '".$mode."', which is not one of 'auto', 'policy', 'legacy' or 'off'."; }
		if(dnshttp_bind_version() === false) { return "The installed bind version could not be determined by running '".(defined("_BIND_CHECKZONE_COMMAND_") ? _BIND_CHECKZONE_COMMAND_ : "named-checkzone")." -v'. Set _BIND_DNSSEC_MODE_ to 'policy' (bind 9.16+) or 'legacy' (bind up to 9.18) in settings.php to configure it by hand."; }
		if(!dnshttp_bind_version_atleast(9, 9)) { return "Bind ".dnshttp_bind_version()." is too old for the supported DNSSEC configurations."; }
		return "";
	}

	// Directory bind keeps the zone keys in. Created on demand, owned like the rest of the
	// generated configuration so named can read and (with dnssec-policy) write it.
	function dnshttp_dnssec_keydir($create = false) {
		if(!defined("_BIND_DNSSEC_KEY_DIR_")) { return false; }
		$dir = rtrim(str_replace("//", "/", (string)_BIND_DNSSEC_KEY_DIR_), "/");
		if(trim($dir) === "") { return false; }
		if($create AND !@is_dir($dir)) {
			@mkdir($dir, octdec("0"._CRON_BIND_LIB_CODE_), true);
			@chown($dir, _CRON_BIND_LIB_USER_);
			@chgrp($dir, _CRON_BIND_LIB_GROUP_);
			@chmod($dir, octdec("0"._CRON_BIND_LIB_CODE_));
		}
		return $dir;
	}

	// Key files present for a zone. $seponly limits the result to key signing keys (flag 257),
	// which are the ones a DS record for the parent zone is built from.
	function dnshttp_dnssec_key_files($zone, $seponly = false) {
		$out = array();
		$dir = dnshttp_dnssec_keydir(false);
		if($dir === false OR !@is_dir($dir)) { return $out; }
		// Callers hand in the already normalised ASCII name, so there are no glob metacharacters
		// in here - but a name that did not go through that normalisation must not reach glob().
		$zone = rtrim(strtolower(trim((string)$zone)), ".");
		if($zone === "" OR $zone !== preg_replace('/[^a-z0-9.-]/', '', $zone)) { return $out; }
		$files = @glob($dir."/K".$zone.".+*.key");
		if(!is_array($files)) { return $out; }
		foreach($files as $file) {
			if($seponly) {
				$content = (string)@file_get_contents($file);
				// "<name>. IN DNSKEY <flags> <protocol> <algorithm> <key>" - 257 marks a secure
				// entry point, 256 is a zone signing key and has no DS record of its own.
				if(!preg_match('/\bDNSKEY\s+(\d+)\s+\d+\s+\d+/i', $content, $m)) { continue; }
				if((int)$m[1] !== 257) { continue; }
			}
			array_push($out, $file);
		}
		return $out;
	}

	// Legacy mode only. With dnssec-policy bind creates and rolls its own keys and keeps state
	// files next to them, so generating keys by hand there would fight with the policy.
	// Returns true, or a message describing why no usable key material exists.
	function dnshttp_dnssec_generate_keys($zone) {
		$zone = rtrim(strtolower(trim((string)$zone)), ".");
		if($zone === "" OR $zone !== preg_replace('/[^a-z0-9.-]/', '', $zone)) { return "Invalid zone name for key generation."; }
		// Counted separately, because a run that was interrupted (or ran out of disk) between the
		// two dnssec-keygen calls leaves half a pair behind. Treating "some key exists" as done
		// would then accept a zone that has a signing key but no key signing key - it would sign,
		// but there would never be anything to build a DS from and the zone could never be
		// validated. Only the missing half is generated, so re-running does not pile up spares.
		$keys_ksk = count(dnshttp_dnssec_key_files($zone, true));
		$keys_zsk = count(dnshttp_dnssec_key_files($zone)) - $keys_ksk;
		if($keys_ksk > 0 AND $keys_zsk > 0) { return true; }
		$dir = dnshttp_dnssec_keydir(true);
		if($dir === false) { return "No key directory is configured (_BIND_DNSSEC_KEY_DIR_)."; }
		if(!@is_dir($dir)) { return "Key directory ".$dir." could not be created - check the permissions of the generated configuration folder."; }
		if(!function_exists("shell_exec")) { return "shell_exec() is disabled, keys cannot be generated."; }
		$alg    = defined("_BIND_DNSSEC_ALGORITHM_") ? trim((string)_BIND_DNSSEC_ALGORITHM_) : "ECDSAP256SHA256";
		if($alg === "") { $alg = "ECDSAP256SHA256"; }
		$keygen = defined("_BIND_DNSSEC_KEYGEN_COMMAND_") ? (string)_BIND_DNSSEC_KEYGEN_COMMAND_ : dnshttp_bind_tool_path("dnssec-keygen");
		$msg = "";
		if($keys_zsk <= 0) { $msg .= (string)@shell_exec($keygen." -a ".escapeshellarg($alg)." -n ZONE -K ".escapeshellarg($dir)." ".escapeshellarg($zone)." 2>&1"); }
		if($keys_ksk <= 0) { $msg .= (string)@shell_exec($keygen." -a ".escapeshellarg($alg)." -f KSK -n ZONE -K ".escapeshellarg($dir)." ".escapeshellarg($zone)." 2>&1"); }
		dnshttp_dnssec_fix_key_permissions($zone);
		$keys_ksk = count(dnshttp_dnssec_key_files($zone, true));
		$keys_zsk = count(dnshttp_dnssec_key_files($zone)) - $keys_ksk;
		if($keys_ksk <= 0 OR $keys_zsk <= 0) {
			return "Key generation failed using '".$keygen."' (key signing keys: ".$keys_ksk.", zone signing keys: ".$keys_zsk."): ".trim(preg_replace('/\s+/', ' ', $msg));
		}
		return true;
	}

	// named needs to read (and under dnssec-policy write) its own key files.
	function dnshttp_dnssec_fix_key_permissions($zone = false) {
		$dir = dnshttp_dnssec_keydir(false);
		if($dir === false OR !@is_dir($dir)) { return; }
		$pattern = ($zone === false) ? $dir."/K*" : $dir."/K".$zone.".+*";
		$files = @glob($pattern);
		if(!is_array($files)) { return; }
		foreach($files as $file) {
			@chown($file, _CRON_BIND_LIB_USER_);
			@chgrp($file, _CRON_BIND_LIB_GROUP_);
			// The private half of a key pair stays readable by its owner alone. dnssec-keygen
			// creates it that way, but the recursive chmod in the cronjob widens everything under
			// the generated configuration folder to the configured mode (0770 by default, so group
			// readable) - this runs after that sweep and takes the private keys back out of it.
			if(substr($file, -8) === ".private") { @chmod($file, 0600); }
				else { @chmod($file, octdec("0"._CRON_BIND_LIB_CODE_)); }
		}
	}

	// DS records for the parent zone / the registrar, built from the key signing keys on disk.
	function dnshttp_dnssec_ds_records($zone) {
		$keys = dnshttp_dnssec_key_files($zone, true);
		if(count($keys) <= 0 OR !function_exists("shell_exec")) { return ""; }
		$cmd = defined("_BIND_DNSSEC_DSFROMKEY_COMMAND_") ? (string)_BIND_DNSSEC_DSFROMKEY_COMMAND_ : dnshttp_bind_tool_path("dnssec-dsfromkey");
		$out = array();
		foreach($keys as $key) {
			// -2 is SHA-256: understood by every supported bind, and SHA-1 (-1) no longer is.
			$res = trim((string)@shell_exec($cmd." -2 ".escapeshellarg($key)." 2>&1"));
			foreach(explode("\n", $res) as $line) {
				$line = trim($line);
				if($line === "" OR !preg_match('/\sDS\s+\d+\s+\d+\s+\d+\s+/i', $line)) { continue; }
				if(!in_array($line, $out)) { array_push($out, $line); }
			}
		}
		return implode("\r\n", $out);
	}

	// The public key records, for the registries that provision DNSSEC by key instead of by digest.
	// DENIC (.de) is the one most people run into: it takes the DNSKEY and derives the DS itself.
	// Same key signing keys the DS above is built from, handed over in the other accepted format.
	function dnshttp_dnssec_dnskey_records($zone) {
		$keys = dnshttp_dnssec_key_files($zone, true);
		$out = array();
		foreach($keys as $key) {
			$content = (string)@file_get_contents($key);
			foreach(preg_split('/\r\n|\r|\n/', $content) as $line) {
				$line = trim($line);
				// A generated key file opens with a block of ";" comments describing the key.
				if($line === "" OR substr($line, 0, 1) === ";") { continue; }
				if(!preg_match('/DNSKEY\s+\d+\s+\d+\s+\d+\s+\S/i', $line)) { continue; }
				// Presentation format is allowed to break the base64 into whitespace separated
				// chunks, but a registrar form wants it as one contiguous string.
				$line = preg_replace_callback('/(DNSKEY\s+\d+\s+\d+\s+\d+\s+)(.*)$/i',
					function($m) { return $m[1].preg_replace('/\s+/', '', $m[2]); }, $line);
				if($line !== null AND !in_array($line, $out)) { array_push($out, $line); }
			}
		}
		return implode("\r\n", $out);
	}

	// Split a DNSKEY record into the four fields a registrar form asks for, or false if the line
	// is not one. Registrar panels almost never take the whole record, they want it field by field.
	function dnshttp_dnssec_dnskey_parts($line) {
		if(!preg_match('/DNSKEY\s+(\d+)\s+(\d+)\s+(\d+)\s+(\S+)/i', (string)$line, $m)) { return false; }
		return array("flags" => $m[1], "protocol" => $m[2], "algorithm" => $m[3], "key" => $m[4]);
	}

	// Registrar dropdowns list the algorithm by name rather than by its number.
	function dnshttp_dnssec_algorithm_name($number) {
		$names = array(
			5  => "RSASHA1",		7  => "NSEC3RSASHA1",
			8  => "RSASHA256",		10 => "RSASHA512",
			12 => "ECCGOST",		13 => "ECDSAP256SHA256",
			14 => "ECDSAP384SHA384",15 => "ED25519",
			16 => "ED448",
		);
		$number = (int)$number;
		return isset($names[$number]) ? $names[$number] : "Algorithm ".$number;
	}

	// The zone options that turn signing on, in the syntax the given mode uses.
	function dnshttp_dnssec_zone_options($mode) {
		$dir = dnshttp_dnssec_keydir(false);
		$out = "";
		if($mode === "policy") {
			$policy = defined("_BIND_DNSSEC_POLICY_") ? trim((string)_BIND_DNSSEC_POLICY_) : "default";
			if($policy === "") { $policy = "default"; }
			// Written bare, the way the bind documentation and the built-in policy names ('default',
			// 'insecure', 'none') expect. Only a name that is not a plain identifier gets quoted,
			// because that one would not parse otherwise.
			if(!preg_match('/^[A-Za-z0-9_-]+$/', $policy)) { $policy = "\"".dnshttp_conf_escape($policy)."\""; }
			$out  = "\r\n\tdnssec-policy ".$policy.";";
			// Stated explicitly on purpose: whether dnssec-policy implies inline-signing changed
			// between bind releases, and an explicit "yes" is accepted by all of them.
			$out .= "\r\n\tinline-signing yes;";
		} elseif($mode === "legacy") {
			$out  = "\r\n\tauto-dnssec maintain;";
			$out .= "\r\n\tinline-signing yes;";
		} else {
			return "";
		}
		if($dir !== false) { $out .= "\r\n\tkey-directory \"".dnshttp_conf_escape($dir)."\";"; }
		return $out;
	}

	// Paths land inside a quoted named.conf string, so a quote or backslash in one has to be
	// escaped rather than ending the string early.
	function dnshttp_conf_escape($value) {
		return str_replace(array("\\", "\""), array("\\\\", "\\\""), (string)$value);
	}

	// Ask named-checkconf whether the generated configuration is acceptable.
	// true = accepted, false = rejected, null = no usable named-checkconf, no opinion.
	function dnshttp_bind_checkconf(&$output) {
		$output = "";
		$cmd = defined("_BIND_CHECKCONF_COMMAND_") ? trim((string)_BIND_CHECKCONF_COMMAND_) : dnshttp_bind_tool_path("named-checkconf");
		if($cmd === "" OR !@is_file($cmd) OR !function_exists("shell_exec")) { return null; }
		if(!defined("_CRON_BIND_CONFNAME_") OR !@is_file(_CRON_BIND_CONFNAME_)) { return null; }
		// named-checkconf stays silent and exits 0 when it is happy, so any output is a complaint.
		$output = trim((string)@shell_exec($cmd." ".escapeshellarg(_CRON_BIND_CONFNAME_)." 2>&1"));
		return ($output === "");
	}

	// The DNSSEC columns are added by an ALTER in initialize.php, so they are present on every
	// install that has been through it once. Naming a column that is somehow still missing fails
	// the whole statement, and that would take the zone writing down with it - so ask first.
	// Only a positive answer is remembered: a negative one is what initialize.php acts on by
	// running the migration, and caching it would report the old state for the rest of that
	// request. Logging is off for the probe so a pre-migration install does not fill the query
	// log with the failure this function exists to expect.
	function dnshttp_dnssec_schema($mysql) {
		static $ok = false;
		if($ok) { return true; }
		$logstate = $mysql->log_status();
		$mysql->log_disable();
		$ok = ($mysql->query("SELECT dnssec, dnssec_mode, dnssec_ds, dnssec_dnskey, dnssec_status, dnssec_update FROM "._TABLE_DOMAIN_BIND_." LIMIT 1") !== false);
		$mysql->free_all();
		if($logstate) { $mysql->log_enable(); }
		return $ok;
	}

	/*************************************************************************
		Cronjob Execution
	*************************************************************************/
	$log_output = "";
	function dnshttp_cron_sync($mysql, $log, $use_real_line_breaks = false) {
		global $log_output;
		#######################################################################################################################################
		// Logging Function for better HTML and Echo View
		$log_output = "";		
		$file_pre_per = "// This is a DnsHTTP Generated BIND Configuration File\r\n// Changes here will be persistent\r\n// The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_noper = "// This is a DnsHTTP Generated BIND Configuration File\r\n// Changes here will NOT be persistent\r\n// The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_zone_per = "; This is a DnsHTTP Generated BIND Configuration File\r\n; Changes here will be persistent\r\n; The bugfish.eu team wished you the best...woof!\r\n";
		$file_pre_zone_noper = "; This is a DnsHTTP Generated BIND Configuration File\r\n; Changes here will NOT be persistent\r\n; The bugfish.eu team wished you the best...woof!\r\n";
		function logging_add($text) {
			global $log_output;
			$use_real_line_breaks = false;
			if(php_sapi_name() === 'cli'){ $use_real_line_breaks = true; }
			$finaltext = $text;
			if($use_real_line_breaks != false) { echo $finaltext; }
			$finaltext = str_replace("\r\n", "<br />", $finaltext);
			if($use_real_line_breaks != true) { echo $finaltext; }
			if(substr($finaltext, 0, 2) == "OK") { $finaltext = " <font color='lime'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 8) == "FINISHED") { $finaltext = "<font color='yellow'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 5) == "ERROR") { $finaltext = " <font color='red'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 4) == "WARN") { $finaltext = "<font color='orange'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 4) == "INFO") { $finaltext = "<font color='lightblue'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 5) == "START") { $finaltext = "<font color='yellow'>".$finaltext."</font>"; }
			elseif(substr($finaltext, 0, 5) == "DEBUG") { $finaltext = "<font color='lightblue'>".$finaltext."</font>"; }
			$log_output .= $finaltext;}		
		#######################################################################################################################################
		// Initialize Starting Message
		logging_add("INFO: Starting Replication Cronjob at: ");
		logging_add("".date("Y-m-d H:i:s")."!\r\n");
		#######################################################################################################################################
		// List of Slave Server IPs	
		$localslaveservers = ""; $array = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE server_type = 2 OR server_type = 3", true);
		if(is_array($array)) {
			foreach($array as $key => $value) {
				if(filter_var(@$value["ip"], FILTER_VALIDATE_IP) !== false) { $localslaveservers .= " ".@$value["ip"].";"; } 
				if(filter_var(@$value["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $localslaveservers .= " ".@$value["ip6"].";"; } 
			}
		}
		logging_add("INFO: Slave Server IPs: "); logging_add("$localslaveservers\r\n");
		#######################################################################################################################################
		// List of Master Server IPs
		$localmasterservers = "";$array = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE server_type = 1 OR server_type = 3", true);
		if(is_array($array)) {
			foreach($array as $key => $value) {
				if(filter_var(@$value["ip"], FILTER_VALIDATE_IP) !== false) { $localmasterservers .= " ".@$value["ip"].";"; }
				if(filter_var(@$value["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $localmasterservers .= " ".@$value["ip6"].";"; }
			}
		}	
		logging_add("INFO: Master Server IPs: "); logging_add("$localmasterservers\r\n");
		// getHostByName() returns the hostname unchanged when it cannot resolve. An unvalidated
		// value lands inside "allow-transfer { ... };" and makes bind reject the entire config.
		$localIP = @getHostByName(php_uname('n'));
		if(filter_var((string)$localIP, FILTER_VALIDATE_IP) !== false) { $localmasterservers .= " ".$localIP.";"; }
			else { logging_add("WARN: Local IP could not be resolved, not added to Master Server IPs!\r\n"); }
		logging_add("INFO: Added Local IP to Master Server IPs: "); logging_add("$localmasterservers\r\n");
		#######################################################################################################################################
		// List of All Server IPs
		$allserverlist = "";$array = $mysql->select("SELECT * FROM "._TABLE_SERVER_."", true);
		if(is_array($array)) {
			foreach($array as $key => $value) {
				if(filter_var(@$value["ip"], FILTER_VALIDATE_IP) !== false) { $allserverlist .= " ".@$value["ip"].";"; }
				if(filter_var(@$value["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { $allserverlist .= " ".@$value["ip6"].";"; }
			}
		}	
		logging_add("INFO: Master Server IPs: "); logging_add("$allserverlist\r\n");
		// Same as above - an unresolvable hostname must never reach the generated named.conf.
		$localIP = @getHostByName(php_uname('n'));
		if(filter_var((string)$localIP, FILTER_VALIDATE_IP) !== false) { $allserverlist .= " ".$localIP.";"; }
			else { logging_add("WARN: Local IP could not be resolved, not added to Server IP list!\r\n"); }
		logging_add("INFO: Added Local IP to Master Server IPs: "); logging_add("$allserverlist\r\n");
		#######################################################################################################################################
		// DNSSEC mode for this run. Resolved once, because it depends on the installed bind version
		// and every signed zone in this run has to be written in the same syntax.
		$dnssec_schema	= dnshttp_dnssec_schema($mysql);	# false on a database that predates the DNSSEC columns
		$dnssec_mode	= dnshttp_dnssec_mode();			# 'policy' | 'legacy' | false
		$dnssec_used	= false;							# set when at least one zone is signed in this run
		$dnssec_failed	= array();							# zone ids whose key material could not be prepared
		if(!$dnssec_schema) {
			$dnssec_mode = false;
			logging_add("WARN: The DNSSEC columns are missing from the domain table, signing is skipped for this run. Open the web interface once so the schema upgrade can run.\r\n");
		} elseif($dnssec_mode === false) {
			logging_add("INFO: DNSSEC signing is not active: "); logging_add(dnshttp_dnssec_unavailable_reason()."\r\n");
		} else {
			logging_add("INFO: DNSSEC mode is '".$dnssec_mode."' (bind ".(dnshttp_bind_version() === false ? "version unknown" : dnshttp_bind_version()).", key directory '".dnshttp_dnssec_keydir(false)."').\r\n");
		}
		#######################################################################################################################################
		// Recreate DNS Config File Structure if not Exists
		#--------------------------------------------------------------------------------------------------------------------------------------
		try { 
		if(!@file_exists(_CRON_BIND_FILE_CONFIG_DNSHTTP_)) {
			if(@mkdir(_CRON_BIND_FILE_CONFIG_DNSHTTP_, octdec("0"._CRON_BIND_LIB_CODE_), true)) {
				 logging_add("OK: Configuration Folder created: "._CRON_BIND_FILE_CONFIG_DNSHTTP_."\r\n"); 
			} else {  logging_add("ERROR: Configuration Folder creation failed [may a permission problem]: "._CRON_BIND_FILE_CONFIG_DNSHTTP_."\r\n");  }
		} else {  logging_add("OK: Configuration Folder exists: "._CRON_BIND_FILE_CONFIG_DNSHTTP_."\r\n");  }		
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_TABLE_)) {
			$output = $file_pre_noper;
			$myfile = @fopen(_CRON_BIND_FILE_TABLE_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: Domain Table File created: "._CRON_BIND_FILE_TABLE_."\r\n"); 
			} else {  logging_add("ERROR: Domain Table File creation failed: "._CRON_BIND_FILE_TABLE_."\r\n");  }
			@fclose($myfile);
		} else { logging_add("OK: Domain Table File does exist: "._CRON_BIND_FILE_TABLE_."\r\n"); }	
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_CONFIG_)) {
			$output = $file_pre_per;
			$output = $output._BIND_CONFIG_OPTIONS_;
			$myfile = @fopen(_CRON_BIND_FILE_CONFIG_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: DNSHTTP Bind9 Configuration created: "._CRON_BIND_FILE_CONFIG_."\r\n"); 
			} else {  logging_add("ERROR: DNSHTTP Bind9 Configuration creation failed: "._CRON_BIND_FILE_CONFIG_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: DNSHTTP Bind9 Configuration does exist: "._CRON_BIND_FILE_CONFIG_."\r\n");  }	
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_TEMP_)) {
			$output = $file_pre_noper;
			$myfile = @fopen(_CRON_BIND_FILE_TEMP_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: DNSHTTP Temp File created: "._CRON_BIND_FILE_TEMP_."\r\n"); 
			} else {  logging_add("ERROR: DNSHTTP Temp File creation failed: "._CRON_BIND_FILE_TEMP_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: DNSHTTP Temp File does exist: "._CRON_BIND_FILE_TEMP_."\r\n");  }	 
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_FILE_LOAD_)) {
			if(!_CRON_BIND_FILE_REWRITE_) { $output = $file_pre_per."include \""._CRON_BIND_FILE_CONFIG_."\";\r\ninclude \""._CRON_BIND_FILE_TABLE_."\";\r\n\r\n"; }
			else { $output = $file_pre_per."include \""._CRON_BIND_FILE_CONFIG_."\";\r\n\r\n"; }
			$myfile = @fopen(_CRON_BIND_FILE_LOAD_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: DNSHTTP Include File created: "._CRON_BIND_FILE_LOAD_."\r\n"); 
			} else {  logging_add("ERROR: DNSHTTP Include File creation failed: "._CRON_BIND_FILE_LOAD_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: DNSHTTP Include File does exist: "._CRON_BIND_FILE_LOAD_."\r\n");  }	 
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_CONFNAME_)) {
			if(!_CRON_BIND_FILE_REWRITE_) { $output = $file_pre_per."include \""._CRON_BIND_FILE_LOAD_."\";\r\n\r\n"; }
			else { $output = $file_pre_per."include \""._CRON_BIND_FILE_LOAD_."\";\r\ninclude \""._CRON_BIND_FILE_."\";\r\n\r\n"; }
			$myfile = @fopen(_CRON_BIND_CONFNAME_, "w");
			if(@fwrite($myfile, $output)) {
				 logging_add("OK: Bind Main Config Include File created: "._CRON_BIND_CONFNAME_."\r\n"); 
			} else {  logging_add("ERROR: Bind Main Config Include File creation failed: "._CRON_BIND_CONFNAME_."\r\n");  }
			@fclose($myfile);
		} else {  logging_add("OK: Bind Main Config Include File does exist: "._CRON_BIND_CONFNAME_."\r\n");  }	 
		#--------------------------------------------------------------------------------------------------------------------------------------
		if(!@file_exists(_CRON_BIND_LIB_)) {
			if(@mkdir(_CRON_BIND_LIB_, octdec("0"._CRON_BIND_LIB_CODE_), true)) {
				 logging_add("OK: Zone File Folder created: "._CRON_BIND_LIB_."\r\n"); 
			} else {  logging_add("ERROR: Zone File Folder creation failed: "._CRON_BIND_LIB_."\r\n");  }
		} else {  logging_add("OK: Zone File Folder does exist: "._CRON_BIND_LIB_."\r\n");  }		
		#--------------------------------------------------------------------------------------------------------------------------------------
		} catch(Throwable $e) { logging_add("ERROR: No permission to create or edit filed - run as root!\r\n"); }
		logging_add("........................................................................................................................................\r\n");
		#######################################################################################################################################
		/* Local Fetched Domain Storage */	
		$domain_array = array(); # Array for Fetched Domains and Entry in Database (Master Domains)	
		#######################################################################################################################################
		/* Fetching Domain Names From Folder with Zone Files in it [FIX FOR CRMs]*/			
		if(defined("_CRON_FILES_FOLDER_FETCH_") AND defined("_CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_") 
				AND defined("_CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_")) {	
			if(_CRON_FILES_FOLDER_FETCH_ !== false ) {
			logging_add("START: Fetching Domain Names From Folder with Zone Files in it...\r\n");
			logging_add("INFO: This is a fix for CMRs failing to create the named.conf.options file...\r\n");
			 logging_add("INFO: Search Folder is "); 
			 logging_add("'"._CRON_FILES_FOLDER_FETCH_."' \r\n"); 		
			 logging_add("INFO: "); 
			 logging_add("Following Settings are set to determine Domain names from File Names "); 
			 logging_add("If there is garbage string in front or after the domain you can use this "); 
			 logging_add("setting to folter chars in front and after the filename, so the domain "); 
			 logging_add("will be determined correctly...  \r\n"); 
			 logging_add("INFO: Filename Start Offset is set to "); 
			 logging_add("'"._CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_."' \r\n"); 
			 logging_add("INFO: Filename End Offset is set to ");	 
			 logging_add("'"._CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_."' \r\n");		
			foreach (glob(_CRON_FILES_FOLDER_FETCH_."/*") as $filename){
				$basenamefile = basename($filename);
				$filenamecleared = str_replace("//", "/", $filename );
				$realname = substr(basename($filename), _CRON_FILES_FOLDER_FETCH_RMSTARTCOUNT_);
				$realname = substr(basename($realname), 0, strlen($realname) - _CRON_FILES_FOLDER_FETCH_RMSENDCOUNT_);			
				logging_add("OK: Determined Domain: $realname \r\n");
				array_push($domain_array, array('domain' => strtolower(trim($realname)), 'path' => $filenamecleared, 'type' => 'folder'));	
			}	
			##################################################################################################################
			logging_add("INFO: Now Cleanup Domains not existant anymore from database.\r\n");
			$domains	= $mysql->select("SELECT domain, id, domain_type FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'folder'", true);
			if(is_array($domains)) {
				foreach($domains as $key => $value) {
					$deleteable = true;
					if(is_array($domain_array)) {
						foreach($domain_array as $x => $y) {
							if($y["domain"] == strtolower(trim($value["domain"]))) { $deleteable = false; }
						}
					}									
					if($deleteable) {
						 logging_add("DEBUG: Removed Domain: ".$value["domain"]."\r\n");		 
						 $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$value["id"]."' AND domain_type = 'folder'"); 
					}
				} 
			} 
			logging_add("FINISHED: Fetching Domain Names From Folder with Zone Files in it...\r\n");
		} else {  $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'folder'"); logging_add("INFO: Named.conf.local Creation fix for CRMs like ispconfig is deactivated in settings.php. For more check the documentation. This does not interrupt the replication process.!\r\n");}}else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'folder'"); logging_add("INFO: Named.conf.local Creation fix for CRMs like ispconfig is deactivated in settings.php. For more check the documentation. This does not interrupt the replication process.!\r\n");}  
		logging_add("........................................................................................................................................\r\n");
		#######################################################################################################################################
		/* GET DOMAIN TABLE FROM DOMAIN TABLE FILE */		
		if(_CRON_BIND_FILE_ != false) {	
			logging_add("START: Reading Zone Table File.\r\n");
			
			// Prepare Backup
			if(!file_exists(_CRON_BIND_FILE_TEMP_."file1_backup")) { @copy(_CRON_BIND_FILE_, _CRON_BIND_FILE_TEMP_."file1_backup"); logging_add("INFO: File Backup Created \r\n"); }
			
			// File to Diff
			@unlink(_CRON_BIND_FILE_TEMP_."file1_tmp");
			@copy(_CRON_BIND_FILE_, _CRON_BIND_FILE_TEMP_."file1_tmp");
			
			logging_add("INFO: File to read is '"._CRON_BIND_FILE_."' \r\n");
			$handle = fopen(_CRON_BIND_FILE_, "r"); if ($handle) {
				while (($line = fgets($handle)) !== false) {
					failrestart:
					if(strpos($line, "zone ") > -1) {
						preg_match('/"(.*?)"/', $line, $match);
						$domain = strtolower(trim($match[1]));	
						$foundline = false;		
						if(strpos($line, "file ") > -1) {
							$foundline = $line;
						} else { 
							while(($line = fgets($handle)) !== false) {
								if($line == false) {
									 logging_add("ERROR: No Content File Found for $domain\r\n"); 
									 goto endthis;
								}	
								if(strpos($line, "file ") > -1) {
									$foundline = $line;
									goto isnowok;
								} else {	
									if(strpos($line, "zone ") > -1) { logging_add("ERROR: No Content File Found for $domain\r\n"); goto failrestart; } 
								} 
							}
						}	
						isnowok:
						$foundline =substr($foundline, strpos($foundline, "zone "));
						$foundline =substr($foundline, strpos($foundline, "file "));
						$realpathnow =substr($foundline, strpos($foundline, "\"") + 1);
						$realpathnow =substr($realpathnow, 0, strpos($realpathnow, "\"") );			
						if(trim($domain) != "") {		
							$realcontentfilename = basename($realpathnow);
							$realcontentfilewithpath = $realpathnow;
							$filenamecleared = str_replace("//", "/", $realpathnow );	
							if(file_exists($realcontentfilewithpath)) {
								logging_add("OK: Added Local Domain: $domain\r\n");
								array_push($domain_array, array('domain' => strtolower(trim($domain)), 'path' => $filenamecleared, 'type' => "file"));
							} else { logging_add("ERROR: Content File not Found for: $domain\r\n"); }
						}					
					}
				} endthis: @fclose($handle); 
				logging_add("INFO: Now Cleanup deleted Domains.\r\n");
				$domains	= $mysql->select("SELECT domain, id, domain_type FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file'", true);
				if(is_array($domains)) {
					foreach($domains as $key => $valuex) {
						$deleteable = true;
						if(is_array($domain_array)) {
							foreach($domain_array as $x => $y) {
								if($y["domain"] == strtolower(trim($valuex["domain"]))) { $deleteable = false; }
							}
						}	
						if($deleteable) {
							logging_add("OK: Removed Domain: ".$valuex["domain"]."\r\n");			 
							$mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$valuex["id"]."' AND domain_type = 'file'"); 
						}
					} 
				} 
			} else { logging_add("ERROR: Could not open File: "._CRON_BIND_FILE_." - No Changes on Related Domains\r\n"); } 		
			logging_add("FINISHED: Last Operation finished!\r\n"); 
		} else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file'"); logging_add("INFO: The First File Descriptor '_CRON_BIND_FILE_' to Fetch Domains out of a file (for example named.conf.local) is false! Related domains will be cleaned up.!\r\n");  }
		logging_add("........................................................................................................................................\r\n");
		#######################################################################################################################################
		/* GET DOMAIN TABLE FROM DOMAIN TABLE FILE */		
		if(_CRON_BIND_FILE_2_ != false) { 
			logging_add("START: Reading Zone Table File (2nd option).\r\n");
			
			// Prepare Backup
			if(!file_exists(_CRON_BIND_FILE_TEMP_."file2_backup")) { @copy(_CRON_BIND_FILE_2_, _CRON_BIND_FILE_TEMP_."file2_backup"); logging_add("INFO: File Backup Created \r\n"); }

			// File to Diff
			@unlink(_CRON_BIND_FILE_TEMP_."file2_tmp");
			@copy(_CRON_BIND_FILE_2_, _CRON_BIND_FILE_TEMP_."file2_tmp");
			
			logging_add("INFO: File to read is '"._CRON_BIND_FILE_2_."' \r\n");	
			$handle = fopen(_CRON_BIND_FILE_2_, "r"); if ($handle) {
				while (($line = fgets($handle)) !== false) {
					failrestart2:
					if(strpos($line, "zone ") > -1) {
						preg_match('/"(.*?)"/', $line, $match);
						$domain = strtolower(trim($match[1]));	
						$foundline = false;		
						if(strpos($line, "file ") > -1) {
							$foundline = $line;
						} else { 
							while(($line = fgets($handle)) !== false) {
								if($line == false) {
									logging_add("ERROR: No Content File Found for $domain\r\n"); 
									 goto endthis2;
								}
								if(strpos($line, "file ") > -1) {
									$foundline = $line;
									goto isnowok2;
								} else {
									if(strpos($line, "zone ") > -1) { logging_add("ERROR: No Content File Found for $domain\r\n"); goto failrestart2; } 
								} 
							}
						}	
						isnowok2:
						$foundline =substr($foundline, strpos($foundline, "zone "));
						$foundline =substr($foundline, strpos($foundline, "file "));
						$realpathnow =substr($foundline, strpos($foundline, "\"") + 1);
						$realpathnow =substr($realpathnow, 0, strpos($realpathnow, "\"") );					
						if(trim($domain) != "") {				
							$realcontentfilename = basename($realpathnow);
							$realcontentfilewithpath = $realpathnow;
							$filenamecleared = str_replace("//", "/", $realpathnow );
							if(file_exists($realcontentfilewithpath)) {
								logging_add("OK: Added Local Domain: $domain\r\n");										
									array_push($domain_array, array('domain' => strtolower(trim($domain)), 'path' => $filenamecleared, 'type' => "file2"));
							} else { logging_add("ERROR: Content File not Found for: $domain\r\n");	 }
						}					
					}
				} endthis2: @fclose($handle); 
				logging_add("INFO: Now Cleanup deleted Domains (2nd Option).\r\n");
				$domains	= $mysql->select("SELECT domain, id, domain_type FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file2'", true);
				if(is_array($domains)) {
					foreach($domains as $key => $valuex) {
						$deleteable = true;
						if(is_array($domain_array)) {
							foreach($domain_array as $x => $y) {
								if($y["domain"] == strtolower(trim($valuex["domain"]))) { $deleteable = false; }
							}
						}									
						if($deleteable) {
							logging_add("OK: Removed Domain: ".$valuex["domain"]."\r\n");			 
							$mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE id = '".$valuex["id"]."' AND domain_type = 'file2'"); 
						}
					} 
				} 		
			} else { logging_add("ERROR: Could not open File: "._CRON_BIND_FILE_." - No Changes on Related Domains\r\n"); } 		
			logging_add("FINISHED: Last Operation finished!\r\n");
		} else { $mysql->query("DELETE FROM "._TABLE_DOMAIN_BIND_." WHERE domain_type = 'file2'"); logging_add("INFO: The Second File Descriptor '_CRON_BIND_FILE_2_' to Fetch Domains out of a file (for example named.conf.local) is false! Related domains will be cleaned up.!\r\n"); }
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Writing/Update all Local Fetched Master Domains to Database	
		logging_add("START: Writing/Update all Local Fetched Domains to Database\r\n");
		// The name lookup below ignores domain_type, so when the same zone is declared by more than
		// one source (both named.conf files, or a file plus the fetch folder) every entry lands on
		// the SAME row and the last one silently wins - one of the declarations is dropped with no
		// warning anywhere. Which source should win is a configuration decision, so the behaviour is
		// left alone, but it is no longer invisible.
		if(is_array($domain_array)) {
			$seen_local_domains = array();
			foreach($domain_array AS $dupkey => $dupvalue) {
				$dupname = strtolower(trim($dupvalue["domain"]));
				if(isset($seen_local_domains[$dupname])) {
					logging_add("WARN: Domain '".$dupname."' is declared by more than one source ('".$seen_local_domains[$dupname]."' and '".$dupvalue["type"]."'). Only one zone file can win - check your configuration!\r\n");
				}
				$seen_local_domains[$dupname] = $dupvalue["type"];
			}
			unset($seen_local_domains);
		}
		if(is_array($domain_array)) {
			foreach($domain_array AS $key => $value) {	
				if($x = dnshttp_bind_domain_name_exists($mysql, $value["domain"])) {	
					$content = "";
					logging_add("OK: Updated Domain: '".$value["domain"]."' | Type: '".$value["type"]."' | Zone-File: '".$value["path"]."' \r\n");
					if(file_exists($value["path"])) { 
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { @unlink(_CRON_BIND_FILE_TEMP_); } 
						@shell_exec(_BIND_COMPILEZONE_COMMAND_." -f raw -F text -o ".escapeshellarg(_CRON_BIND_FILE_TEMP_)." ".escapeshellarg(strtolower($value["domain"]))." ".escapeshellarg($value["path"])."  ");
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { $pathnow = _CRON_BIND_FILE_TEMP_; } else { $pathnow = $value["path"]; }
						if($content = file_get_contents($pathnow)) {
						} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Opening Error for: '".$value["domain"]."' \r\n"); } 
					} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Does not Exist for: '".$value["domain"]."' \r\n"); }
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $value["path"];
					$bind[1]["type"] = "s";
					$bind[1]["value"] = $value["type"];
					$bind[2]["type"] = "s";
					$bind[2]["value"] = $content;
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zone_path = ?, domain_type = ?, last_update = CURRENT_TIMESTAMP(), content = ? WHERE id = '".$x."';", $bind);unset($bind);
				} else {
					$content = "";
					logging_add("OK: Added Domain: '".$value["domain"]."' | Type: '".$value["type"]."' | Zone-File: '".$value["path"]."' \r\n");
					if(file_exists($value["path"])) { 
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { @unlink(_CRON_BIND_FILE_TEMP_); } 
						@shell_exec(_BIND_COMPILEZONE_COMMAND_." -f raw -F text -o ".escapeshellarg(_CRON_BIND_FILE_TEMP_)." ".escapeshellarg(strtolower($value["domain"]))." ".escapeshellarg($value["path"])."  ");
						if(file_exists(_CRON_BIND_FILE_TEMP_)) { $pathnow = _CRON_BIND_FILE_TEMP_; } else { $pathnow = $value["path"]; }
						if($content = file_get_contents($pathnow)) { 
						} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Opening Error for: '".$value["domain"]."' \r\n"); } 
					} else { $content = "error"; logging_add("-- ");logging_add("WARN: Zone File Does not Exist for: '".$value["domain"]."' \r\n"); }	
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $value["domain"];	
					$bind[1]["type"] = "s";
					$bind[1]["value"] = $value["path"];	
					$bind[2]["type"] = "s";
					$bind[2]["value"] = $value["type"];	
					$bind[3]["type"] = "s";
					$bind[3]["value"] = $content;					
					$mysql->query("INSERT INTO "._TABLE_DOMAIN_BIND_."(domain, zone_path, domain_type, last_update, content, preferred) VALUES(?, ?, ?, CURRENT_TIMESTAMP(), ?, 1);", $bind);unset($bind);
					// This row is created with preferred = 1, so every other entry for the same name
					// has to be un-preferred. Clearing only the slave table left a second preferred
					// row behind whenever the name already existed as a USER domain - the exists()
					// lookup above skips domain_type 'user' on purpose, so it does not see one. Two
					// preferred rows make the writers emit two "zone" stanzas for the same name and
					// bind then refuses the whole generated config.
					// Re-read the id instead of trusting insert_id: on a failed insert that property
					// still holds the id from an earlier row in this same loop, and excluding the
					// wrong id here would un-prefer a legitimately preferred entry.
					$newlocalid = dnshttp_bind_domain_name_exists($mysql, $value["domain"]);
					$bindlocalpref = array();
					$bindlocalpref[0]["value"] = trim(strtolower($value["domain"]));
					$bindlocalpref[0]["type"]  = "s";
					// Bound, not interpolated - this name comes out of a parsed named.conf line or a
					// file name, so a quote in it used to break the statement.
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET preferred = 0 WHERE LOWER(TRIM(domain)) = ?", $bindlocalpref);
					if($newlocalid !== false AND is_numeric($newlocalid)) {
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET preferred = 0 WHERE LOWER(TRIM(domain)) = ? AND id <> ".$newlocalid."", $bindlocalpref);
					}
				}
			}	
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Domains Array not Needed anymore, now unset...
		unset($domain_array); # No need anymore, Reset to preserve sprace
		unset($bind);	
		##################################################################################################################
		// Get External Domains	
		logging_add("START: Get Domains From Master Servers (Slave Domains)\r\n");
		# --------------------------------------------------------------------------------------	
		$servers = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE (server_type = 1 OR server_type = 3) AND enabled = 1", true);
		$error_servers = array();
		if(is_array($servers)) {	
			foreach($servers as $key => $value) {
				$current_server_id = $value["id"];
				server_check_and_set($mysql, $current_server_id);
				logging_add("START: Connecting to Server:  ".$value["api_path"]."\r\n");
				$value = $mysql->select("SELECT * FROM "._TABLE_SERVER_." WHERE (server_type = 1 OR server_type = 3) AND enabled = 1 AND id = '".$current_server_id."' ", false);
				if(!is_array($value)) {
					logging_add("ERROR: Unknown Slave Server Response, please check connection!\r\n"); array_push($error_servers, $current_server_id);
					continue;
				}
				$output = "";
				$apipath	=	$value["api_path"]."/_api/list.php";
				$returncurl =   dnshttp_api_getcontent($mysql, $apipath, $value["api_token"], "", ($value["ssl_insecure"] ?? 1));
				
				if($value["apiok"] == 0) 	{ 
					logging_add("ERROR: Unready Slave Server Response, please check connection!\r\n"); array_push($error_servers, $value["id"]);
					continue;
				}
				if($value["apiok"] == "1") 	{
				}
				if($value["apiok"] == "2") 	{ 
					$returncurl = dnshttp_decompress($returncurl);
				}
				
				$newarray = @unserialize($returncurl, ['allowed_classes'=>false]);
				if(is_array($newarray)) {
					$all_domains = array();
					foreach($newarray as $x => $y) {
						$y = strtolower(trim((string)($y ?? '')));
						// SECURITY + IDN: normalize international names to punycode (ASCII) BEFORE validating,
						// otherwise valid names like müller.de get corrupted/dropped. The resulting ASCII form
						// blocks command injection (shell_exec), path traversal and named.conf injection.
						$ascii = ($y === '.') ? '.' : idn_to_ascii($y, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
						if($ascii === false || $ascii === '' || strlen($ascii) > 253
							|| strpos($ascii, '..') !== false
							|| $ascii !== preg_replace('/[^a-z0-9.-]/', '', $ascii)
							|| !dnshttp_isValidDomain($ascii)) {
							logging_add("WARN: Rejected invalid remote domain from server-id ".$value["id"].": ".htmlspecialchars((string)$y)."\r\n");
							continue;
						}
						$y = $ascii; // store the punycode/ASCII form
						$bind[0]["value"] = $y;
						$bind[0]["type"] = "s";
						array_push($all_domains, $y); // Current Temp All API Server Domains

						$tmp = $mysql->select("SELECT id FROM "._TABLE_DOMAIN_API_." WHERE fk_server = ".$value["id"]." AND LOWER(TRIM(domain)) = ?", false, $bind);
						if(is_array($tmp)) {
							$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET last_update = CURRENT_TIMESTAMP() WHERE id = '".$tmp["id"]."' AND fk_server = ".$value["id"].";");	
							logging_add("OK: Still Existing Slave Domain: ".$y."\r\n");
						} else {						
							$mysql->query("INSERT INTO "._TABLE_DOMAIN_API_."(domain, fk_server) VALUES(?, '".$value["id"]."');", $bind);	
							logging_add("OK: Inserted Slave Domain: ".$y."\r\n");
						}
					}
					logging_add("INFO: Now Cleanup deleted domains...\r\n");
					$domains	= $mysql->select("SELECT domain, id FROM "._TABLE_DOMAIN_API_." WHERE fk_server = '".$value["id"]."'", true);	
					if(is_array($domains)) {
						foreach($domains as $keyx => $valuex) {				
							$deleteable = true;
							if(is_array($all_domains)) {
								foreach($all_domains as $x => $y) {
									if(trim(strtolower($y)) == trim(strtolower($valuex["domain"]))) { $deleteable = false; }
								}
							}								
							if($deleteable) {
								logging_add("OK: Removed Expired Slave Domain: ".$valuex["domain"]."\r\n");
								$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = '".$valuex["id"]."' AND fk_server = '".$value["id"]."'");
							}
						}
					}
					
				} else { logging_add("ERROR: Bad Slave Server Response, please check connection!\r\n"); array_push($error_servers, $value["id"]); }
			} 
		} else {
			// select() returns false both for "no rows" and for "query failed". Wiping every
			// replicated domain on a transient database error would take all slave zones out of
			// service, so only clear when the empty result is positively confirmed by a COUNT.
			$srvcount = $mysql->select("SELECT COUNT(*) AS cnt FROM "._TABLE_SERVER_." WHERE (server_type = 1 OR server_type = 3) AND enabled = 1", false);
			if(is_array($srvcount) AND (int)$srvcount["cnt"] === 0) {
				$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_."");
				logging_add("OK: There are no Slave Servers, all Remote Domains Cleared!\r\n");
			} else {
				logging_add("ERROR: Master Server list could not be read, replicated domains left untouched!\r\n");
			}
		}
		logging_add("FINISHED: LAST OPERATION\r\n");	
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Delete All API Domains not Related to a Server	
		logging_add("START: Cleanup unrelated Remote Domains!\r\n");
		$domains = $mysql->select("SELECT fk_server, domain, id FROM "._TABLE_DOMAIN_API_." ORDER BY id DESC", true);
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				if(is_array(dnshttp_server_get($mysql, $value["fk_server"]))) {
				} else {
					logging_add("OK: Unrelated Domain Deleted: ".$value["domain"]." from server-id: ".$value["fk_server"]."\r\n");
					$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = '".$value["id"]."';");
				}
			} 
		} 
		logging_add("FINISHED: LAST OPERATION\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Get External Domains Content
		logging_add("START: Get (replicate) External Domains Content!\r\n");
		$domains = $mysql->select("SELECT fk_server, domain, id FROM "._TABLE_DOMAIN_API_." ORDER BY id DESC", true);
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				$serverdata = 	dnshttp_server_get($mysql, $value["fk_server"]);
				$domain = $value["domain"];
				if(!$serverdata) 	{
					logging_add("SKIPPED: Server Not Found : $domain\r\n");
					continue;
				}
				$apipath	=	$serverdata["api_path"]."/_api/content.php";
				$returncurl =   dnshttp_api_getcontent($mysql, $apipath, $serverdata["api_token"], $value["domain"], ($serverdata["ssl_insecure"] ?? 1));

				if($serverdata["enabled"] != "1") 	{ 
					logging_add("SKIPPED: Server Disabled : $domain\r\n");
					continue;
				}		
				if($serverdata["apiok"] == "0") 	{ 
					logging_add("SKIPPED: Server Error : $domain\r\n");
					continue;
				}				
				
				if($serverdata["apiok"] == "2") 	{ 
					$returncurl = dnshttp_decompress($returncurl);
				}				
				
				$domain = $value["domain"];
				if(in_array($value["fk_server"], $error_servers)) {
					logging_add("SKIPPED: Server Timeout : $domain\r\n");
					continue;
				}
				if($returncurl AND $returncurl != "error-domain-no-exist") { 
					//echo $returncurl;
					$bind[0]["type"] = "s";
					$bind[0]["value"] = $returncurl;
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET content = ?, modification = CURRENT_TIMESTAMP() WHERE id = '".$value["id"]."';", $bind);
					logging_add("OK: Fetched content for: $domain\r\n");	
				} elseif($returncurl == "error-domain-no-exist") {
					$mysql->query("DELETE FROM "._TABLE_DOMAIN_API_." WHERE id = ".$value["id"].";");
				} else { logging_add("ERROR: Can not get Content for $domain : Invalid Return Data!\r\n"); }
			} 
		} logging_add("FINISHED: LAST OPERATION\r\n");
		logging_add("........................................................................................................................................\r\n");		
		##################################################################################################################
		// Check for Conflicts		
		logging_add("START: Check for Domain Conflicts!\r\n");
		$domains_full = array();
		$domains_posted = array();
		$domains_bind = $mysql->select("SELECT domain_type, domain, id, preferred, conflict, fk_user FROM "._TABLE_DOMAIN_BIND_." ORDER BY domain", true);
		$domains_api = $mysql->select("SELECT  domain, id, fk_server, preferred, conflict FROM "._TABLE_DOMAIN_API_."  ORDER BY domain", true);	
		if(is_array($domains_bind)) { foreach($domains_bind as $key => $value) { array_push($domains_full, $value); } }
		if(is_array($domains_api)) { foreach($domains_api as $key => $value) { array_push($domains_full, $value); } }	
		foreach($domains_full as $key => $value) {
			
			$domainname_current = strtolower($value["domain"]);
			$conflict = false;
			$conflictarray = array();	
			
			if(is_array($domains_api)) { foreach($domains_api as $x => $y) {
				// Check Slave Domain Conflict
				if(strtolower($y["domain"]) == strtolower($value["domain"])) { $conflict = true; array_push($conflictarray, $y); }
			}}
			if(is_array($domains_bind)) { foreach($domains_bind as $x => $y) {
				// Check Bind Domain Conflict
				if(strtolower($y["domain"]) == strtolower($value["domain"])) { $conflict = true; array_push($conflictarray, $y); } 
			}}
			
			$preferredhandle = false;
			if($conflict  AND is_array($conflictarray[0]) AND is_array(@$conflictarray[1])) {
				foreach($conflictarray as $keyx => $valueconflict) {
					if($valueconflict["preferred"] == 1) {
						$preferredhandle = $valueconflict;
					}
				}
			}
			
			if($conflict AND is_array($conflictarray[0])  AND is_array(@$conflictarray[1])) {
				
				$handleinel = serialize($conflictarray); 
				
				$bind12 = array();
				$bind12[0]["value"] = $handleinel;
				
				$bind = array();
				$bind[0]["value"] = trim(strtolower($domainname_current));
				
				$selector = $mysql->select("SELECT * FROM "._TABLE_CONFLICT_." WHERE LOWER(TRIM(domain)) = ?", false, $bind);
				if(is_array($selector)) {
					if( !$preferredhandle ) { $mysql->query("UPDATE "._TABLE_CONFLICT_." SET servers = ?, solved = 0 WHERE id = '".$selector["id"]."'", $bind12); }
					else { $mysql->query("UPDATE "._TABLE_CONFLICT_." SET servers = ? WHERE id = '".$selector["id"]."'", $bind12); } 
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
				} else {  
						$bind12 = array();
						$bind12[0]["value"] = trim(strtolower($domainname_current));
						$bind12[1]["value"] = $handleinel;
						  $mysql->query("INSERT INTO "._TABLE_CONFLICT_." (domain, servers, solved) VALUES(?, ?, 0)", $bind12); 
						  $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
						  $mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 1 WHERE LOWER(TRIM(domain)) = ?;", $bind);
				}			

				if(!$preferredhandle ) { 
					$postnow = true;
					foreach($domains_posted AS $keasdf => $afng3uk) {
						if($afng3uk == strtolower(trim($domainname_current))) {
							$postnow = false;
						}
						
					} if($postnow) { logging_add("ERROR: This Domain has a Conflict: $domainname_current\r\n"); }
					array_push($domains_posted, trim(strtolower($domainname_current)));
				} else { 
					
					if(isset($preferredhandle["fk_server"])) { $asdsas7hfds = $preferredhandle["fk_server"]; } else { $asdsas7hfds = "Local";  }
					if(isset($preferredhandle["fk_user"])) { $asdsas7hfds23 = $preferredhandle["fk_user"]; } else { $asdsas7hfds23 = "None";  }
					
					$bind = array();
					$bind[0]["value"] = "Domain: ".trim(strtolower($domainname_current))." | Server-ID: ".@$asdsas7hfds." | User-ID: ".@$asdsas7hfds23."";
					$bind[1]["value"] = trim(strtolower($domainname_current));
					
					$mysql->query("UPDATE "._TABLE_CONFLICT_." SET solved = ? WHERE LOWER(TRIM(domain)) = ?", $bind);
					
					$postnow = true;
					foreach($domains_posted AS $keasdf => $afng3uk) {
						if($afng3uk == strtolower(trim($domainname_current))) {
							$postnow = false;
						}
						
					} if($postnow) { logging_add("OK: This Domain has a solved Conflict: $domainname_current\r\n"); }
					array_push($domains_posted, trim(strtolower($domainname_current)));
				}
				
			} else { 
				$bind = array();
				$bind[0]["value"] = trim(strtolower($domainname_current));
				$bind[0]["type"] = "s";		
				$mysql->query("DELETE FROM "._TABLE_CONFLICT_." WHERE LOWER(TRIM(domain)) = ?", $bind); 
				$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET conflict = 0 WHERE LOWER(TRIM(domain)) = ?;", $bind);
				$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET conflict = 0 WHERE LOWER(TRIM(domain)) = ?;", $bind); 
			}	
		
		}
		unset($domains_bind);
		unset($domains_api);
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		
		##################################################################################################################
		// Write Domain Files and Table File at Once		
		$conf_buildstring = $file_pre_noper."";
		$safeString = (new DateTimeImmutable())->format('Y-m-d H:i:s');
		logging_add("START: Writing Slave Zones\r\n");
		$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck = 2, oldzonefallback = 2, zonecheck_failmessage = 'none', zonecheck_message = 'none', registered = 2 "); // Reset Domains State
		$domains = $mysql->select("SELECT domain, content, id, fk_server, okonce FROM "._TABLE_DOMAIN_API_." WHERE ( conflict = 0 OR (conflict = 1 AND preferred = 1) ) ORDER BY id DESC", true);
		$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 0 WHERE ( conflict = 1 AND preferred = 0) ");
		if(is_array($domains)) {	
			$conf_buildstring = "";	
			foreach($domains as $key => $value) {
				// Same defensive normalisation the master writer below does. Ingest validates
				// remote names already, but rows written by an older version have not been, and
				// this name is about to become a file path and a shell argument.
				$rawslave = strtolower(trim($value["domain"]));
				$safeslavedomain = ($rawslave === '.') ? '.' : idn_to_ascii($rawslave, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
				if($safeslavedomain === false) { $safeslavedomain = ''; }
				if($safeslavedomain === '' || strlen($safeslavedomain) > 253 || strpos($safeslavedomain, '..') !== false
					|| $safeslavedomain !== preg_replace('/[^a-z0-9.-]/', '', $safeslavedomain)) {
					logging_add("ERROR: Skipped slave domain with invalid name: ".htmlspecialchars($rawslave)."\r\n");
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 0 WHERE id = '".$value["id"]."'");
					continue;
				}
				$relay = @dnshttp_server_get($mysql, $value["fk_server"]);
				if(!is_array($relay)) { logging_add("ERROR: Domain Relay not exist, skipped for domain ".$value["domain"]."!\r\n"); $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 0 WHERE id = '".$value["id"]."'"); continue; }
				// Determine a valid master IP for the zone stanza (IPv4 preferred, IPv6 fallback),
				// an empty/invalid "masters { ; }" entry would break the whole generated bind config!
				$relaymasterip = "";
				if(filter_var(trim($relay["ip"] ?? ''), FILTER_VALIDATE_IP) !== false) { $relaymasterip = trim($relay["ip"]); }
				elseif(filter_var(trim($relay["ip6"] ?? ''), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) { $relaymasterip = trim($relay["ip6"]); }
				if($relaymasterip == "") { logging_add("ERROR: Domain Relay has no valid IP, skipped for domain ".$value["domain"]."!\r\n"); $mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET registered = 0 WHERE id = '".$value["id"]."'"); continue; }
				$filenamecleared = _CRON_BIND_LIB_."/".$safeslavedomain._CRON_BIND_LIB_ENDING_.".slave";
				$filenamecleared = str_replace("//", "/", $filenamecleared );
				$temppathslave = _CRON_BIND_FILE_TEMP_.$safeslavedomain;
				$newzoneok = false;
				// The zone file is rebuilt from the database on every run, so it never becomes a
				// second source of truth and a hand-edited or half-written file cannot survive.
				@unlink($filenamecleared);
				if($value["okonce"]) {
					if(file_exists($temppathslave)) { @unlink($temppathslave); }

					@file_put_contents($temppathslave, $value["content"]);
					@chown($temppathslave, _CRON_BIND_LIB_USER_);
					@chgrp($temppathslave, _CRON_BIND_LIB_GROUP_);
					@chmod($temppathslave, octdec("0"._CRON_BIND_LIB_CODE_));
					$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".escapeshellarg($safeslavedomain)." ".escapeshellarg($temppathslave));
					if(strpos(strtolower($cout), ": loaded serial") > -1) { 
						$newzoneok = true;
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET oldzonefallback = 0 WHERE id = '".$value["id"]."'");
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck_failmessage = 'none' WHERE id = '".$value["id"]."'"); 
					} else { 
						$newzoneok = false;
						// Reset first: the conflict loop above can leave $bind holding two elements,
						// and binding two parameters to this one-placeholder statement fails, so the
						// diagnostic message was silently never stored.
						$bind = array();
						$bind[0]["value"] = $cout;
						$bind[0]["type"] = "s";
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck_failmessage = ? WHERE id = '".$value["id"]."'", $bind);
						$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET oldzonefallback = 1 WHERE id = '".$value["id"]."'");
					} @unlink($temppathslave);
				}
				if($newzoneok == true) {
					if(file_exists($filenamecleared)) { @unlink($filenamecleared); }
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, octdec("0"._CRON_BIND_LIB_CODE_));
				}
				if($newzoneok != true AND !file_exists($filenamecleared)) {
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, octdec("0"._CRON_BIND_LIB_CODE_));
				}

				$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".escapeshellarg($safeslavedomain)." ".escapeshellarg($filenamecleared));
				$bind = array(); // see above - stale elements from an earlier loop break this bind
				$bind[0]["value"] = $cout;
				$bind[0]["type"] = "s";
				$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck_message = ? WHERE id = '".$value["id"]."'", $bind);
				$isnowregister = false;
				if(strpos(strtolower($cout), ": loaded serial") > -1) {
					$isnowregister = true;				
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck = 1, okonce = 1, registered = 1 WHERE id = '".$value["id"]."'");
					

					$bindyd = array();
					$bindyd[0]["value"] = strtolower(trim($value["domain"]));
					$checkregistertable = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_REG_." WHERE LOWER(TRIM(domain)) = ?", false, $bindyd);
					if(is_array($checkregistertable)) {
						$bindyd = array();
						$bindyd[0]["value"] = $value["content"];
						$bindyd[1]["value"] = strtolower(trim($value["domain"]));
						$mysql->query("UPDATE "._TABLE_DOMAIN_REG_." SET content = ?, time_data_removal = '".$safeString."' WHERE LOWER(TRIM(domain)) = ?", $bindyd);
					} else {
						$bindyd = array();
						$bindyd[0]["value"] = strtolower(trim($value["domain"]));
						$bindyd[1]["value"] = $value["content"];
						$mysql->query("INSERT INTO "._TABLE_DOMAIN_REG_."(domain, content, time_data_removal) VALUES(?, ?, '".$safeString."')", $bindyd);
					}

					//if(!REMOVED_CONSTANT) {
						// Use the one already validated name - recomputing it here silently STRIPPED
						// bad characters where the check at the top of the loop REJECTS them, which
						// meant the stanza and the file path could be derived differently.
						$conf_buildstring .= "\r\n\r\nzone \"".$safeslavedomain."\" {\r\n\ttype slave;\r\n\tmasterfile-format text;\r\n\tmasters { ".$relaymasterip."; };\r\n\tallow-transfer { ".$allserverlist." };\r\n\tfile \"".$filenamecleared."\";\r\n};\r\n";
					//} else {
					//	$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n};";
					//}
				} else {
					$isnowregister = false;		
					$mysql->query("UPDATE "._TABLE_DOMAIN_API_." SET zonecheck = 0, okonce = 0, registered = 0 WHERE id = '".$value["id"]."'");
				}
				if($isnowregister AND $newzoneok) {
					logging_add("OK: Written Zone File: ".$filenamecleared.""."\r\n");	
				} elseif($isnowregister AND !$newzoneok) {
					logging_add("WARN: Left Rollback Zone File: ".$filenamecleared.""."\r\n");	
				} else {
					logging_add("ERROR: No valid zone data in file: ".$filenamecleared.""."\r\n");
				}
			}
		} 
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("START: Writing Master Zones\r\n");
		$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck = 2, oldzonefallback = 2, zonecheck_failmessage = 'none', zonecheck_message = 'none', registered = 2 "); // Reset Domains State
		// The DNSSEC column is only named when it exists - see dnshttp_dnssec_schema(). A missing
		// column would fail this statement as a whole and leave every master zone unwritten.
		$domains = $mysql->select("SELECT id, domain, content, okonce, set_no_replicate".($dnssec_schema ? ", dnssec" : "")." FROM "._TABLE_DOMAIN_BIND_." WHERE ( conflict = 0 OR (conflict = 1 AND preferred = 1) ) ORDER BY id DESC", true);
		$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET registered = 0 WHERE ( conflict = 1 AND preferred = 0) ");
		if(is_array($domains)) {
			foreach($domains as $key => $value) {
				// SECURITY (#10) + IDN: normalize the locally-sourced master domain to punycode (ASCII)
				// before it is used in file paths, shell commands or named.conf. Converting first keeps
				// international domains (müller.de -> xn--mller-kva.de) intact instead of corrupting them.
				$raw = strtolower(trim($value["domain"]));
				$safedomain = ($raw === '.') ? '.' : idn_to_ascii($raw, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
				if($safedomain === false) { $safedomain = ''; }
				if($safedomain === '' || strlen($safedomain) > 253 || strpos($safedomain, '..') !== false
					|| $safedomain !== preg_replace('/[^a-z0-9.-]/', '', $safedomain)) {
					logging_add("ERROR: Skipped domain with invalid name: ".htmlspecialchars($raw)."\r\n");
					continue;
				}
				$temppath = _CRON_BIND_FILE_TEMP_.$safedomain;
				$filenamecleared = _CRON_BIND_LIB_."/".$safedomain._CRON_BIND_LIB_ENDING_.".master";
				$filenamecleared = str_replace("//", "/", $filenamecleared );
				// DNSSEC for this zone. The root hint zone is never signed here, and the raw zone
				// file this loop rewrites stays the unsigned source: bind keeps the signed copy and
				// its journal beside it (inline-signing), and neither is touched by this cronjob.
				$dnssec_stanza = "";
				if($dnssec_mode !== false AND !empty(@$value["dnssec"]) AND trim($value["domain"]) != ".") {
					$dnssec_ready = true;
					// BOTH modes need this directory to exist before named is pointed at it, and
					// neither mode gets it for free: legacy writes the keys generated below into
					// it, and dnssec-policy has bind create its own keys there when it loads the
					// zone. named-checkconf does not verify that a key-directory exists, so a
					// missing one is not caught by the verification further down - it just means
					// bind can never write a key and the zone silently stays unsigned.
					$dnssec_keydir_now = dnshttp_dnssec_keydir(true);
					if($dnssec_keydir_now === false) {
						$dnssec_ready = "No key directory is configured (_BIND_DNSSEC_KEY_DIR_ in settings.php).";
					} elseif(!@is_dir($dnssec_keydir_now)) {
						$dnssec_ready = "Key directory ".$dnssec_keydir_now." does not exist and could not be created - check the permissions of the generated configuration folder.";
					} elseif($dnssec_mode === "legacy") {
						// auto-dnssec maintain only maintains keys that already exist, so they are
						// created here. dnssec-policy generates its own when it loads the zone.
						$dnssec_ready = dnshttp_dnssec_generate_keys($safedomain);
					}
					if($dnssec_ready === true) {
						$dnssec_used = true;
						$dnssec_stanza = _DNSHTTP_DNSSEC_TOKEN_;
						logging_add("OK: DNSSEC ('".$dnssec_mode."') enabled for zone: ".$safedomain."\r\n");
					} else {
						logging_add("ERROR: DNSSEC could not be enabled for zone ".$safedomain.": ".$dnssec_ready."\r\n");
						// Remembered so the key readback further down leaves this row alone. It would
						// otherwise overwrite this specific diagnostic with its own generic "no key
						// found yet" message, which is true but says nothing about the actual cause.
						array_push($dnssec_failed, $value["id"]);
						$binddnssec = array();
						$binddnssec[0]["value"] = "Key material could not be prepared: ".$dnssec_ready;
						$binddnssec[0]["type"]  = "s";
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dnssec_status = ?, dnssec_update = CURRENT_TIMESTAMP() WHERE id = '".$value["id"]."'", $binddnssec);
					}
				}
				// Rebuilt from the database every run - see the slave writer above.
				@unlink($filenamecleared);
				$newzoneok = false;
				if($value["okonce"]) {
					if(file_exists($temppath)) { @unlink($temppath); }
					@file_put_contents($temppath, $value["content"]);
					@chown($temppath, _CRON_BIND_LIB_USER_);
					@chgrp($temppath, _CRON_BIND_LIB_GROUP_);
					@chmod($temppath, octdec("0"._CRON_BIND_LIB_CODE_));
					$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".escapeshellarg($safedomain)." ".escapeshellarg($temppath));
					if(strpos(strtolower($cout), ": loaded serial") > -1) {
						$newzoneok = true;
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET oldzonefallback = 0 WHERE id = '".$value["id"]."'");
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck_failmessage = 'none' WHERE id = '".$value["id"]."'");
					} else {
						$newzoneok = false;
						$bind = array(); // see the slave writer above - reset before binding
						$bind[0]["value"] = $cout;
						$bind[0]["type"] = "s";
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck_failmessage = ? WHERE id = '".$value["id"]."'", $bind);
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET oldzonefallback = 1 WHERE id = '".$value["id"]."'");
					} @unlink($temppath);
				}
				if($newzoneok == true) {
					if(file_exists($filenamecleared)) { @unlink($filenamecleared); }
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, octdec("0"._CRON_BIND_LIB_CODE_));
				}
				if($newzoneok != true AND !file_exists($filenamecleared)) {
					@file_put_contents($filenamecleared, $value["content"]);
					@chown($filenamecleared, _CRON_BIND_LIB_USER_);
					@chgrp($filenamecleared, _CRON_BIND_LIB_GROUP_);
					@chmod($filenamecleared, octdec("0"._CRON_BIND_LIB_CODE_));
				}
				$cout = @shell_exec(_BIND_CHECKZONE_COMMAND_." ".escapeshellarg($safedomain)." ".escapeshellarg($filenamecleared));
				$bind = array(); // see the slave writer above - reset before binding
				$bind[0]["value"] = $cout;
				$bind[0]["type"] = "s";
				$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck_message = ? WHERE id = '".$value["id"]."'", $bind);
				$isnowregister = false;
				$nowwritte = false;
				if(strpos(strtolower($cout), ": loaded serial") > -1) {
					$isnowregister = true;				
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck = 1, okonce = 1, registered = 1 WHERE id = '".$value["id"]."'");

					// The registration table is what _api/list.php and _api/content.php hand out, so
					// it must only ever hold domains that are cleared for replication. A domain with
					// set_no_replicate stays active in bind locally but is never published: skipping
					// it here keeps its row out, and the cleanup further down then drops any row it
					// still has, so flagging a domain unpublishes it within this same run.
					if(empty($value["set_no_replicate"])) {
						$bindyd = array();
						$bindyd[0]["value"] = strtolower(trim($value["domain"]));
						$checkregistertable = $mysql->select("SELECT * FROM "._TABLE_DOMAIN_REG_." WHERE LOWER(TRIM(domain)) = ?", false, $bindyd);
						if(is_array($checkregistertable)) {
							$bindyd = array();
							$bindyd[0]["value"] = $value["content"];
							$bindyd[1]["value"] = strtolower(trim($value["domain"]));
							$mysql->query("UPDATE "._TABLE_DOMAIN_REG_." SET content = ?, time_data_removal = '".$safeString."' WHERE LOWER(TRIM(domain)) = ?", $bindyd);
						} else {
							$bindyd = array();
							$bindyd[0]["value"] = strtolower(trim($value["domain"]));
							$bindyd[1]["value"] = $value["content"];
							$mysql->query("INSERT INTO "._TABLE_DOMAIN_REG_."(domain, content, time_data_removal) VALUES(?, ?, '".$safeString."')", $bindyd);
						}
					} else {
						logging_add("INFO: Not published for replication (set_no_replicate): ".$safedomain."\r\n");
					}

					//if(!REMOVED_CONSTANT) {
						//$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype slave;\r\n\tmasterfile-format text;\r\n\tmasters { ".trim($relay["ip"])."; };\r\n\tallow-transfer { ".$localmasterservers." };\r\n\tfile \"".$filenamecleared."\";\r\n};\r\n";	
					//} else {
					//	$conf_buildstring .= "\r\n\r\nzone \"".trim($value["domain"])."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n};";
					//}
					$nowwritte = true;
					if(trim($value["domain"]) != ".") { $conf_buildstring .= "\r\n\r\nzone \"".$safedomain."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n\tallow-transfer { ".$allserverlist." };\r\n\tallow-update { ".$allserverlist." };".$dnssec_stanza."\r\n};";}
				} else {
					$isnowregister = false;		
					// See the slave writer above - okonce must survive a failed check.
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET zonecheck = 0, okonce = 0, registered = 0 WHERE id = '".$value["id"]."'");
				}
				if($isnowregister AND $newzoneok) {
					logging_add("OK: Written Zone File: ".$filenamecleared.""."\r\n");	
				} elseif($isnowregister AND !$newzoneok) {
					logging_add("WARN: Left Rollback Zone File: ".$filenamecleared.""."\r\n");	
				} else {
					logging_add("ERROR: No valid zone data in file: ".$filenamecleared.""."\r\n");
				}
				
				if(!$nowwritte AND @file_exists(@$filenamecleared) AND _CRON_BIND_FILE_REWRITE_) {
					if(trim($value["domain"]) != ".") { $conf_buildstring .= "\r\n\r\nzone \"".$safedomain."\" {\r\n\ttype master;\r\n\tfile \"".$filenamecleared."\";\r\n\tallow-transfer { ".$allserverlist." };\r\n\tallow-update { ".$allserverlist." };".$dnssec_stanza."\r\n};";}
				}
			}
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		if(file_exists(_CRON_BIND_FILE_TABLE_)) { @unlink(_CRON_BIND_FILE_TABLE_); }
		if(file_exists(_CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master")) {
			$rnew = $conf_buildstring."\r\n\r\nzone \".\" {\r\n\ttype hint;\r\n\tfile \""._CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master\";\r\n};";
		} else { $rnew = $conf_buildstring ;}
		// Resolve the DNSSEC placeholder the zone stanzas were built with. This is what the signed
		// zones asked for - it is dropped again further down if named-checkconf refuses it.
		$dnssec_options			= ($dnssec_mode === false) ? "" : dnshttp_dnssec_zone_options($dnssec_mode);
		$rewrite_file_written	= false;
		@file_put_contents(_CRON_BIND_FILE_TABLE_, str_replace(_DNSHTTP_DNSSEC_TOKEN_, $dnssec_options, $rnew));
		logging_add("OK: Trying to write Zone Table File: "._CRON_BIND_FILE_TABLE_."\r\n");
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		//  Update the Public API Listing for Domains and Updates
		logging_add("START: Safely Remove Master Registration Table Expired Domains\r\n");
		$mysql->query("DELETE FROM "._TABLE_DOMAIN_REG_." WHERE time_data_removal <> '".$safeString."'");
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		//  _CRON_BIND_FILE_REWRITE_ foR virtualmin
		if(_CRON_BIND_FILE_REWRITE_ AND _CRON_BIND_FILE_) { 
			logging_add("INFO: _CRON_BIND_FILE_ is set and _CRON_BIND_FILE_REWRITE_ is set!\r\n");
			logging_add("INFO: Now rewriting the _CRON_BIND_FILE_ if nothing has changed in the meantime.!\r\n");
			if(file_exists(_CRON_BIND_FILE_)) { 
				@unlink(_CRON_BIND_FILE_TEMP_."file1_tmp");
				@copy(_CRON_BIND_FILE_, _CRON_BIND_FILE_TEMP_."file1_tmp");
				//check if file same
				if(md5_file(_CRON_BIND_FILE_TEMP_."file1_tmp") === md5_file(_CRON_BIND_FILE_)) {
					@unlink(_CRON_BIND_FILE_);
					if(file_exists(_CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master")) { $rnew = $conf_buildstring."\r\n\r\nzone \".\" {\r\n\ttype hint;\r\n\tfile \""._CRON_BIND_LIB_."/."._CRON_BIND_LIB_ENDING_.".master\";\r\n};"; }
					else { $rnew = $conf_buildstring ;}
					@file_put_contents(_CRON_BIND_FILE_, str_replace(_DNSHTTP_DNSSEC_TOKEN_, $dnssec_options, $rnew));
					$rewrite_file_written = true;
					logging_add("OK: Trying to write Zone Table File: "._CRON_BIND_FILE_."\r\n");
				} else {
					logging_add("ERROR: File has been changed, waiting for next cron to reload zone table: "._CRON_BIND_FILE_."\r\n");
				}
			}
		}
		logging_add("FINISHED: Last Operation finished!\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Verify the generated configuration before named is restarted onto it. A zone option this
		// bind does not know is not a problem for that one zone - named refuses the whole file and
		// every zone on this server stops resolving, so it is worth one check.
		if($dnssec_used AND $dnssec_options !== "") {
			logging_add("START: Verifying the generated configuration with named-checkconf\r\n");
			$checkout = "";
			$checkok  = dnshttp_bind_checkconf($checkout);
			if($checkok === null) {
				logging_add("WARN: named-checkconf ("._BIND_CHECKCONF_COMMAND_.") is not available, the DNSSEC configuration could not be verified before loading!\r\n");
			} elseif($checkok === true) {
				logging_add("OK: The generated configuration including DNSSEC has been accepted.\r\n");
			} else {
				logging_add("WARN: named-checkconf rejected the configuration: ".trim(preg_replace('/\s+/', ' ', $checkout))."\r\n");
				// Write the same configuration without DNSSEC. If that one passes, the syntax does
				// not fit this bind, and serving the zones unsigned beats not serving them at all.
				@file_put_contents(_CRON_BIND_FILE_TABLE_, str_replace(_DNSHTTP_DNSSEC_TOKEN_, "", $rnew));
				if($rewrite_file_written) { @file_put_contents(_CRON_BIND_FILE_, str_replace(_DNSHTTP_DNSSEC_TOKEN_, "", $rnew)); }
				$checkout2 = "";
				if(dnshttp_bind_checkconf($checkout2) === true) {
					$dnssec_options = "";
					logging_add("ERROR: This bind does not accept the '".$dnssec_mode."' DNSSEC syntax. All zones have been written UNSIGNED so they keep resolving. Set _BIND_DNSSEC_MODE_ in settings.php to the syntax your bind understands ('policy' for 9.16 and newer, 'legacy' up to 9.18).\r\n");
					if($dnssec_schema) {
						$binddnssec = array();
						$binddnssec[0]["value"] = "Not applied: named-checkconf rejected the '".$dnssec_mode."' DNSSEC syntax on this server. ".trim(preg_replace('/\s+/', ' ', $checkout));
						$binddnssec[0]["type"]  = "s";
						$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dnssec_status = ?, dnssec_mode = NULL, dnssec_update = CURRENT_TIMESTAMP() WHERE dnssec = 1", $binddnssec);
					}
				} else {
					// Rejected with and without DNSSEC, so the cause lies elsewhere and removing
					// the signing configuration would not fix anything - put it back.
					@file_put_contents(_CRON_BIND_FILE_TABLE_, str_replace(_DNSHTTP_DNSSEC_TOKEN_, $dnssec_options, $rnew));
					if($rewrite_file_written) { @file_put_contents(_CRON_BIND_FILE_, str_replace(_DNSHTTP_DNSSEC_TOKEN_, $dnssec_options, $rnew)); }
					logging_add("WARN: The configuration is rejected with and without DNSSEC, so the cause is somewhere else and the DNSSEC configuration has been kept. Please check the message above.\r\n");
				}
			}
			logging_add("FINISHED: Last Operation finished!\r\n");
			logging_add("........................................................................................................................................\r\n");
		}
		##################################################################################################################
		//  Executions
		logging_add("START: Linux Command Executions \r\n");
		// Chown
		if(file_exists(_CRON_BIND_LIB_)) { 
			logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_LIB_." -R;\r\n"); 
			@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_LIB_." -R;");
			@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_LIB_." -R;");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_LIB_.": Folder does not exists!\r\n"); }	

		if(file_exists(_CRON_BIND_FILE_CONFIG_DNSHTTP_)) { 
			logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";\r\n"); 
			@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_FILE_CONFIG_DNSHTTP_.": Folder does not exists!\r\n"); }	

		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TABLE_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TABLE_.";");
		
		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_CONFIG_.";");
			
		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TEMP_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_TEMP_.";");
		
		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_LOAD_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_FILE_LOAD_.";");		

		logging_add("OK: chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_CONFNAME_.";\r\n"); 
		@shell_exec("chown "._CRON_BIND_LIB_USER_.":"._CRON_BIND_LIB_GROUP_." "._CRON_BIND_CONFNAME_.";");	
		
		// Chmod 
		if(file_exists(_CRON_BIND_LIB_)) {  
			logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_LIB_.";\r\n"); 
			@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_LIB_.";");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_LIB_.": Folder does not exists!\r\n"); }	
		
		if(file_exists(_CRON_BIND_FILE_CONFIG_DNSHTTP_)) {  
			logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";\r\n"); 
			@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_DNSHTTP_.";");
		} else { logging_add("ERROR: Execution of a command skipped, "._CRON_BIND_FILE_CONFIG_DNSHTTP_.": Folder does not exists!\r\n"); }	

		logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TABLE_.";\r\n"); 
		@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TABLE_.";");

		logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_.";\r\n"); 
		@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_CONFIG_.";");
		
		logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TEMP_.";\r\n"); 
		@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_TEMP_.";");

		logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_LOAD_.";\r\n"); 
		@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_FILE_LOAD_.";");

		logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_CONFNAME_.";\r\n"); 
		@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." "._CRON_BIND_CONFNAME_.";");
		
		logging_add("OK: chmod "."0"._CRON_BIND_LIB_CODE_." /etc/bind;\r\n"); 
		@shell_exec("chmod "."0"._CRON_BIND_LIB_CODE_." /etc/bind;");
		
		if(_DNSHTTP_DOCKERIZED_) {
			@shell_exec("chmod "."0777 /etc/bind/named.conf;");
		}
		
		// Restart Named Service
		logging_add("OK: systemctl restart "._BIND_SERVICE_NAME_.";\r\n"); 
		@shell_exec("supervisorctl restart bind9");
		@shell_exec("systemctl restart "._BIND_SERVICE_NAME_."; ");
		if(_DNSHTTP_DOCKERIZED_) {
			@shell_exec("service  "._BIND_SERVICE_NAME_." start");
			@shell_exec("service  "._BIND_SERVICE_NAME_." restart");
		}
		logging_add("FINISHED: LAST OPERATION\r\n");
		logging_add("........................................................................................................................................\r\n");
		##################################################################################################################
		// Read back the DNSSEC key material and build the DS records for the registrar.
		// This runs after the restart on purpose: under dnssec-policy bind creates the keys itself
		// when it loads the zone, so before that there is nothing on disk to read.
		if($dnssec_schema) {
			logging_add("START: Collecting DNSSEC key material\r\n");
			$dnssec_domains = $mysql->select("SELECT id, domain FROM "._TABLE_DOMAIN_BIND_." WHERE dnssec = 1", true);
			if(is_array($dnssec_domains) AND $dnssec_used AND $dnssec_options !== "") {
				// Give the freshly restarted named a moment to write out the keys of a zone that
				// was signed for the first time. Anything still missing is picked up next run.
				@sleep(3);
			}
			if(is_array($dnssec_domains)) {
				foreach($dnssec_domains as $key => $value) {
					$rawdnssec  = strtolower(trim($value["domain"]));
					$safednssec = ($rawdnssec === '.') ? '.' : idn_to_ascii($rawdnssec, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
					if($safednssec === false) { $safednssec = ''; }
					if($safednssec === '' || strlen($safednssec) > 253 || strpos($safednssec, '..') !== false
						|| $safednssec !== preg_replace('/[^a-z0-9.-]/', '', $safednssec)) {
						logging_add("ERROR: Skipped DNSSEC key lookup for invalid domain name: ".htmlspecialchars($rawdnssec)."\r\n");
						continue;
					}
					dnshttp_dnssec_fix_key_permissions($safednssec);
					if(in_array($value["id"], $dnssec_failed)) {
						// The writer above already recorded why this one has no usable key material.
						logging_add("SKIPPED: DNSSEC key material was not prepared for: ".$safednssec."\r\n");
						continue;
					}
					if($dnssec_mode === false OR $dnssec_options === "") {
						// Signing is not being written at the moment, so whatever DS is on record
						// says nothing about what this server actually serves. Left untouched.
						logging_add("SKIPPED: DNSSEC is requested but not active for: ".$safednssec."\r\n");
						continue;
					}
					$dsnow = dnshttp_dnssec_ds_records($safednssec);
					// Both formats are stored, because which one is wanted depends on the registry
					// rather than on anything this server can see: most take the DS digest, DENIC
					// (.de) and a few others take the DNSKEY and derive the digest themselves.
					$dnskeynow = dnshttp_dnssec_dnskey_records($safednssec);
					$binddnssec = array();
					$binddnssec[0]["value"] = $dsnow;
					$binddnssec[0]["type"]  = "s";
					$binddnssec[1]["value"] = $dnskeynow;
					$binddnssec[1]["type"]  = "s";
					if(trim($dsnow) === "" AND trim($dnskeynow) === "") {
						$binddnssec[2]["value"] = "No key signing key found yet in ".dnshttp_dnssec_keydir(false).". With dnssec-policy bind creates it while loading the zone - if this message is still here after the next run, check the permissions of that directory and the named log.";
					} else {
						$binddnssec[2]["value"] = "Signed with '".$dnssec_mode."'. The zone is only validated once the parent zone publishes the key, so hand it to the registrar in whichever of the two formats below their form asks for.";
					}
					$binddnssec[2]["type"]  = "s";
					$binddnssec[3]["value"] = $dnssec_mode;
					$binddnssec[3]["type"]  = "s";
					$mysql->query("UPDATE "._TABLE_DOMAIN_BIND_." SET dnssec_ds = ?, dnssec_dnskey = ?, dnssec_status = ?, dnssec_mode = ?, dnssec_update = CURRENT_TIMESTAMP() WHERE id = '".$value["id"]."'", $binddnssec);
					if(trim($dsnow) === "" AND trim($dnskeynow) === "") { logging_add("WARN: No DNSSEC key signing key found (yet) for: ".$safednssec."\r\n"); }
						else { logging_add("OK: DS and DNSKEY record(s) read for: ".$safednssec."\r\n"); }
				}
			} else { logging_add("INFO: No domain has DNSSEC enabled.\r\n"); }
			logging_add("FINISHED: LAST OPERATION\r\n");
			logging_add("........................................................................................................................................\r\n");
		}
		// Logfile Message
		logging_add("OK: Execution Done at ".date("Y-m-d H:m:i")."");
		$log->info($log_output, "cron_sync");
	} 