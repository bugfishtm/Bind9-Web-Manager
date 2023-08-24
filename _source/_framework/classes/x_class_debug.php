<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Debug and Benchmark Class */	
	class x_class_debug {
		private $microtime_start = false;
		
		function __construct() {
			$this->microtime_start = microtime(true);
		}

		public function error_screen($text) {
			http_response_code(503);
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
			"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
			<html version="-//W3C//DTD XHTML 1.1//EN"
				  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"
				  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				  xsi:schemaLocation="http://www.w3.org/1999/xhtml
									  http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd">
				<head>
					<title>Database Error</title>
					<meta http-equiv="content-Type" content="text/html; utf-8" />
					<meta name="robots" content="noindex, nofollow" />
					<meta http-equiv="Pragma" content="no-cache" />
					<meta http-equiv="content-Language" content="en" />
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<style>
					html, body { background: blue; color: white; font-family: Arial; text-align: center; margin: 0 0 0 0; padding: 0 0 0 0; position: absolute; width: 100%; top: 0px; left: 0px; height: 100vh; }
					a { color: black; text-decoration: none; font-weight: bold; background: green; border-radius: 10px; font-size: 16px; padding: 15px; word-break: keep-all; white-space: nowrap; }		
					a:hover { color: black; text-decoration: none; font-weight: bold; background: white; border-radius: 10px; font-size: 16px; padding: 15px; }
					#dberrorwrapper { text-align: center; color: lightblue; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
					</style>
					<meta name="expires" content="0" />	
				</head>
				<body>
					<div id="dberrorwrapper"><font size="+5">Error 503</font><br/><font size="+3">Site under Maintenance...</font><br />Please check in later! x)<br /><br /><b>Error</b>: '.htmlspecialchars($text).'</div>
				</body></html>';}
				
		public function required_php_modules($array = array(), $errorscreen = false) {
			$ar = $this->php_modules();
			$notfoundarray = array();
			foreach($array AS $key => $value) {
				$found = false;
				foreach($ar AS $keyx => $valuex) {
					if($value == $valuex) { $found = true;}
				}
				if(!$found) { array_push($notfoundarray, $value); } 
			}
			
			if($errorscreen AND count($notfoundarray) > 0) { $this->error_screen("Missing PHP Module: <br />".@serialize(@$notfoundarray)); exit(); } else { return $notfoundarray;}
		}
		public function required_php_module($name, $errorscreen = false) {
			$ar = $this->php_modules();
			foreach($ar AS $key => $value) {
				if($value == $name) { return true;}
			} 
			
			if($errorscreen) { $this->error_screen("Missing Module: ".$name); exit(); } else { return false;}
		}
		public function php_modules() { return get_loaded_extensions(); }
		public function memory_usage() { return round(memory_get_usage() / 1000)."KB"; }
		public function memory_limit() { return ini_get('memory_limit'); }
		public function cpu_load() { return sys_getloadavg()[0]; }
		public function upload_max_filesize() { return ini_get('upload_max_filesize'); }
		public function timer() { $endtime = microtime(true); $newstart = $endtime - $this->microtime_start; $newstart = round($newstart, 3); return $newstart; }
	}
