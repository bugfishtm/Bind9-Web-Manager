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

	########################################################################
	// Button SQL Execution
	########################################################################
	function x_executionButton($db, $name, $url, $query, $get, $msgerr = "Fehler!", $msgok = "Erfolgreich!", $break = false, $style = ""){
		if(strpos(trim($url ?? ''), "?") > 2) { $xurl = trim($url ?? '')."&".$get."=x"; } else {$xurl = trim($url ?? '')."?".$get."=x";} print "<a href='".$xurl."' class='x_executionButton' style='".$style."'>".$name."</a>";if($break) {echo "<br />";} if(@$_GET[$get] == "x") { if($db->query($query)) { return true; } else {return false;}  $url = str_replace("?".$get."=x&", "?", $url); $url = str_replace("&".$get."=x", "", $url);  print '<meta http-equiv="refresh" content="0; url='.$url.'">';} return false;}	
	
	########################################################################
	// Button without SQL Execution
	########################################################################
	function x_button($name, $url, $break = false, $style = "", $reacttourl = true){  if($reacttourl AND strpos($url."&", $_SERVER["REQUEST_URI"]."&") > -1) {$style .= ";background: grey !important;";} print "<a href='".$url."' class='x_button' style='".$style."'>".$name."</a>"; if($break) {echo "<br />";}}
