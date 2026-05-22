<?php                                                  
	#	@@@@@@@  @@@  @@@  @@@@@@@  @@@@@@@@ @@@  @@@@@@ @@@  @@@ 
	#	@@!  @@@ @@!  @@@ !@@       @@!      @@! !@@     @@!  @@@ 
	#	@!@!@!@  @!@  !@! !@! @!@!@ @!!!:!   !!@  !@@!!  @!@!@!@! 
	#	!!:  !!! !!:  !!! :!!   !!: !!:      !!:     !:! !!:  !!! 
	#	:: : ::   :.:: :   :: :: :   :       :   ::.: :   :   : : 						
	#		 ______  ______   ______   _________   ______  _   _   _   ______   ______   _    __ 
	#		| |     | |  | \ | |  | | | | | | | \ | |     | | | | | | / |  | \ | |  | \ | |  / / 
	#		| |---- | |__| | | |__| | | | | | | | | |---- | | | | | | | |  | | | |__| | | |-< <  
	#		|_|     |_|  \_\ |_|  |_| |_| |_| |_| |_|____ |_|_|_|_|_/ \_|__|_/ |_|  \_\ |_|  \_\ 
																							 
	#	Copyright (C) 2025 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software; you can redistribute it and/or
	#	modify it under the terms of the GNU Lesser General Public License
	#	as published by the Free Software Foundation; either version 2.1
	#	of the License, or (at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU Lesser General Public License for more details.

	#	You should have received a copy of the GNU Lesser General Public License
	#	along with this program; if not, see <https://www.gnu.org/licenses/>.
	
	class x_class_debug {
		private $microtime_start = false;
		
		function __construct() {
			$this->microtime_start = microtime(true);
		}

		public function error_screen($text) {
			http_response_code(503);
			echo '<!DOCTYPE html><html><head><title>Missing Requirements</title><meta name="robots" content="noindex, nofollow"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" href="data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjIuODggMTIyLjg4Ij48ZGVmcz48c3R5bGU+LmNscy0xe2ZpbGw6I2ZmNDE0MTtmaWxsLXJ1bGU6ZXZlbm9kZDt9PC9zdHlsZT48L2RlZnM+PHRpdGxlPmNyb3NzPC90aXRsZT48cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik02LDZINmEyMC41MywyMC41MywwLDAsMSwyOSwwbDI2LjUsMjYuNDlMODcuOTMsNmEyMC41NCwyMC41NCwwLDAsMSwyOSwwaDBhMjAuNTMsMjAuNTMsMCwwLDEsMCwyOUw5MC40MSw2MS40NCwxMTYuOSw4Ny45M2EyMC41NCwyMC41NCwwLDAsMSwwLDI5aDBhMjAuNTQsMjAuNTQsMCwwLDEtMjksMEw2MS40NCw5MC40MSwzNSwxMTYuOWEyMC41NCwyMC41NCwwLDAsMS0yOSwwSDZhMjAuNTQsMjAuNTQsMCwwLDEsMC0yOUwzMi40Nyw2MS40NCw2LDM0Ljk0QTIwLjUzLDIwLjUzLDAsMCwxLDYsNloiLz48L3N2Zz4=" type="image/svg+xml"><style type="text/css">body,html{padding:0;margin:0;font-family:Quicksand,sans-serif;font-weight:400;overflow:hidden;overflow-y:auto;}p{font-family:Oxygen,sans-serif}.subtitle{font-size:14px;color:#b3b3b3}#server-network-connector{background-color:#9e9e9e;transition:width 3s;position:relative;width:0;height:25px;top:50%;left:70%}#errorTag{display:none;transition:width .6s;margin-top:-11px;width:26%;padding-left:27%}.writing{width:320px;height:200px;background-color:#3f3f3f;border:1px solid #bbb;border-radius:6px 6px 4px 4px;position:relative}.errorLine,.writing .code ul li{background-color:#9e9e9e;width:0;height:7px;border-radius:6px;margin:10px 0}.errorLine{animation:2.5s infinite unloadError}@keyframes unloadError{0%,100%{--linelength:10%}25%{--linelength:70%}50%{--linelength:46%}75%{--linelength:68%}}.writing .topbar{position:absolute;width:100%;height:12px;background-color:#f1f1f1;border-top-left-radius:4px;border-top-right-radius:4px}.writing .topbar div{height:6px;width:6px;border-radius:50%;margin:3px;float:left}.writing .topbar div.green{background-color:#60d060}.writing .topbar div.red{background-color:red}.writing .topbar div.yellow{background-color:#e6c015}.writing .code{padding:15px}.writing .code ul{list-style:none;margin:0;padding:0}.container{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;height:100vh;width:100%;-webkit-transition:-webkit-transform .5s;transition:transform .5s;transition:transform .5s,-webkit-transform .5s}.stack-container{position:relative;width:420px;height:210px;-webkit-transition:width 1s,height 1s;transition:width 1s,height 1s}.pokeup{-webkit-transition:.3s;transition:.3s}.pokeup:hover{-webkit-transform:translateY(-10px);transform:translateY(-10px);-webkit-transition:.3s;transition:.3s}.error{width:400px;padding:80px;text-align:center}.error h1{font-size:125px;padding:0;margin:0;font-weight:700}.error h2{margin:-30px 0 0;padding:0;font-size:47px;letter-spacing:12px}.perspec{-webkit-perspective:1000px;perspective:1000px}.writeLine{-webkit-animation:.4s linear forwards writeLine;animation:.4s linear forwards writeLine}.explode{-webkit-animation:.5s ease-in-out forwards explode;animation:.5s ease-in-out forwards explode}.card{-webkit-animation:.5s ease-in-out 1s forwards tiltcard;animation:.5s ease-in-out 1s forwards tiltcard;position:absolute}@-webkit-keyframes tiltcard{0%{-webkit-transform:rotateY(0);transform:rotateY(0)}100%{-webkit-transform:rotateY(-30deg);transform:rotateY(-30deg)}}@keyframes tiltcard{0%{-webkit-transform:rotateY(0);transform:rotateY(0)}100%{-webkit-transform:rotateY(-30deg);transform:rotateY(-30deg)}}@-webkit-keyframes explode{0%{-webkit-transform:translate(0,0) scale(1);transform:translate(0,0) scale(1)}100%{-webkit-transform:translate(var(--spreaddist),var(--vertdist)) scale(var(--scaledist));transform:translate(var(--spreaddist),var(--vertdist)) scale(var(--scaledist))}}@keyframes explode{0%{-webkit-transform:translate(0,0) scale(1);transform:translate(0,0) scale(1)}100%{-webkit-transform:translate(var(--spreaddist),var(--vertdist)) scale(var(--scaledist));transform:translate(var(--spreaddist),var(--vertdist)) scale(var(--scaledist))}}@-webkit-keyframes writeLine{0%{width:0}100%{width:var(--linelength)}}@keyframes writeLine{0%{width:0}100%{width:var(--linelength)}}@media screen and (max-width:1000px){.container{-webkit-transform:scale(.85);transform:scale(.85)}}@media screen and (max-width:850px){.container{-webkit-transform:scale(.75);transform:scale(.75)}}@media screen and (max-width:775px){.container{-ms-flex-wrap:wrap-reverse;flex-wrap:wrap-reverse;-webkit-box-align:inherit;-ms-flex-align:inherit;align-items:inherit}}@media screen and (max-width:370px){.container{-webkit-transform:scale(.6);transform:scale(.6)}} a { color: #FF5707; text-decoratioN: none; } a:hover { color: #121212; } p.subbutton { font-size:18px; } @media (max-width: 1000px) { .stack-container { display: none;}}</style></head><body><div class="container"><div class="error"><h1>PHP</h1><h2>ERROR</h2><p>Please install the PHP-Module: \'<b>'.htmlspecialchars($text ?? '').'</b>\'.</p><p class="subtitle">Class Exception: x_class_debug</p></div><div class="stack-container"><div class="card-container"><div class="perspec" style="--spreaddist: 25px; --scaledist: .95; --vertdist: -5px;"><div class="card"><div class="writing"><div class="topbar"><div class="red"></div><div class="yellow"></div><div class="green"></div></div><div class="code"><ul></ul></div></div></div></div></div><div class="card-container"><div class="perspec" style="--spreaddist: 0px; --scaledist: 1; --vertdist: 0px;"><div class="card"><div class="writing"><div class="topbar"><div class="red"></div><div class="yellow"></div><div class="green"></div></div><div class="code"><ul></ul></div></div></div></div></div></div></div><script>const stackContainer = document.querySelector(\'.stack-container\');const cardNodes = document.querySelectorAll(\'.card-container\');const consoleNodes = document.querySelectorAll(\'.writing\');const perspecNodes = document.querySelectorAll(\'.perspec\');const perspec = document.querySelector(\'.perspec\');const card = document.querySelector(\'.card\');let counter = stackContainer.children.length;function randomIntFromInterval(min, max) { return Math.floor(Math.random() * (max - min + 1) + min);}card.addEventListener(\'animationend\', function () { perspecNodes.forEach(function (elem, index) { elem.classList.add(\'explode\'); }); });perspec.addEventListener(\'animationend\', function (e) { if (e.animationName === \'explode\') { cardNodes.forEach(function (elem, index) { elem.classList.add(\'pokeup\'); let numLines = randomIntFromInterval(5, 6); for (let index = 0; index < numLines; index++) { let lineLength = randomIntFromInterval(25, 97);var node = document.createElement("li");node.classList.add(\'node-\' + index); elem.querySelector(\'.code ul\').appendChild(node).setAttribute(\'style\', \'--linelength: \' + lineLength + \'%;\'); if (index == 0) { elem.querySelector(\'.code ul .node-\' + index).classList.add(\'writeLine\'); } else { elem.querySelector(\'.code ul .node-\' + (index - 1)).addEventListener(\'animationend\', function (e) { if(index == numLines-1){ node.classList.add(\'errorLine\');elem.querySelector(\'.code ul .node-\' + index).classList.add(\'writeLine\');setTimeout(function() {consoleNodes.forEach(function (elem, index) {elem.classList.add(\'writing-error\');}); }, 2000);}else{elem.querySelector(\'.code ul .node-\' + index).classList.add(\'writeLine\');}});}}});}});</script></body></html>';}
				
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
			
			if($errorscreen AND count($notfoundarray) > 0) { $this->error_screen(@serialize(@$notfoundarray)); exit(); } else { return $notfoundarray;}
		}
		public function required_php_module($name, $errorscreen = false) {
			$ar = $this->php_modules();
			foreach($ar AS $key => $value) {
				if($value == $name) { return true;}
			} 
			
			if($errorscreen) { $this->error_screen($name); exit(); } else { return false;}
		}
		public function php_modules() { return get_loaded_extensions(); }
		public function memory_usage() { return round(memory_get_usage() / 1000)."KB"; }
		public function memory_limit() { return ini_get('memory_limit'); }
		public function cpu_load() { if(function_exists("sys_getloadavg")) { return sys_getloadavg()[0]; } else { return "intl-mod-missing"; } }
		public function upload_max_filesize() { return ini_get('upload_max_filesize'); }
		public function timer() { $endtime = microtime(true); $newstart = $endtime - $this->microtime_start; $newstart = round($newstart, 3); return $newstart; }
		
		
		public function js_error_script($action_url) { 
			echo '
				window.onerror = function(error, url, line) {
					$.post("'.$action_url.'", 
					{ urlstring: window.location.href, errortext: \'File: \'+url+\' Line: \'+line+\' Error: \'+error }, function (data) {});	
				}
				';
		}
		
		public function js_error_action($x_class_mysql, $table, $current_user_id = 0, $section = "") { 
			if(!$x_class_mysql->table_exists($table)) { $this->js_error_create_db($x_class_mysql, $table); $x_class_mysql->free_all(); }
			$into_array = array();
			$into_array["fk_user"] 		= $current_user_id;
			$into_array["errormsg"] 	= @$_POST["errortext"];
			$into_array["urlstring"] 	= @$_POST["urlstring"];
			$bind[0]["value"] = $into_array["urlstring"];
			$bind[0]["type"] = "s"; 
			$bind[1]["value"] = $into_array["errormsg"];
			$bind[1]["type"] = "s";	
			$bind[2]["value"] = $section;
			$bind[2]["type"] = "s";	
			$x_class_mysql->query("INSERT INTO ".$table."(urlstring, fk_user, errormsg, section) VALUES(?, '".$into_array["fk_user"]."', ?, ?);", $bind);
		}
		
		public function js_error_create_db($x_class_mysql, $table) {
			if(!$x_class_mysql->table_exists($table)) {
			return $x_class_mysql->query("CREATE TABLE IF NOT EXISTS `".$table."` (
										  `id` int(11) NOT NULL AUTO_INCREMENT,
										  `fk_user` int(11) NOT NULL DEFAULT 0,
										  `creation` datetime DEFAULT current_timestamp(),
										  `errormsg` longtext DEFAULT NULL,
										  `urlstring` varchar(512) DEFAULT NULL,
										  `section` varchar(128) DEFAULT NULL,
										  PRIMARY KEY (`id`)
										);");
			}
		}
	}