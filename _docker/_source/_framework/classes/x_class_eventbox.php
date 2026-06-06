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
	
	class x_class_eventbox {
		// Class Variables
		private $cookie   				= ""; 
		
		// Constructor
		function __construct($cookie = "") {
			$this->cookie	= $cookie; 
			if ( session_status() !== PHP_SESSION_ACTIVE ) { @session_start(); }
			$_SESSION[$this->cookie."x_class_eventbox_skip"] = false; 	
			if(!is_array(@$_SESSION[$this->cookie."x_class_eventbox"])) { $_SESSION[$this->cookie."x_class_eventbox"] = array(); }}		
		
		// Quick Add Functions
		public function ok($text) {
			return $this->add($text, "ok");
		}
		public function warning($text) {
			return $this->add($text, "warning");
		}
		public function error($text) {
			return $this->add($text, "error");
		}
		public function info($text) {
			return $this->add($text, "info");
		}
		
		// Add Message to Messages Array
		public function add($text, $type) {
			if(!is_array($_SESSION[$this->cookie."x_class_eventbox"])) { $_SESSION[$this->cookie."x_class_eventbox"] = array(); }
			$value			=	array();
			$value["text"]	=	$text;
			$value["type"]	=	$type;			
			array_push($_SESSION[$this->cookie."x_class_eventbox"], $value);}		
		
		// Get Current Messages Array
		public function get() {
			return @$_SESSION[$this->cookie."x_class_eventbox"];
		}				
		
		// Show a Single Message and Override other Messages
		public function override($text, $type) {
			$_SESSION[$this->cookie."x_class_eventbox"] = array();
			$value			=	array();
			$value["text"]	=	$text;
			$value["type"]	=	$type;
			array_push($_SESSION[$this->cookie."x_class_eventbox"], $value);}		
		
		// Reset all entered Messages in Eventboxes
		public function reset() {
			$_SESSION[$this->cookie."x_class_eventbox_skip"] = false;
			$_SESSION[$this->cookie."x_class_eventbox"] = array();
		}
		
		// Skip Eventbox on next show function execution
		public function skip() {
			$_SESSION[$this->cookie."x_class_eventbox_skip"] = true;
		}
		public function noskip() {
			$_SESSION[$this->cookie."x_class_eventbox_skip"] = false;
		}
		
		// Show Eventbox if Set
		public function show($closebutton = false, $filter = false) {
			if(@$_SESSION[$this->cookie."x_class_eventbox_skip"]) { $_SESSION[$this->cookie."x_class_eventbox_skip"] = false; return true; } 
			if(is_array($_SESSION[$this->cookie."x_class_eventbox"])) {
				$hasitem = false;
				foreach($_SESSION[$this->cookie."x_class_eventbox"] AS $key => $value) {
					$hasitem = true;
				}
				if($hasitem) {
					echo "<div class='x_class_eventbox'>";
						echo "<div class='x_class_eventbox_inner'>";
							foreach($_SESSION[$this->cookie."x_class_eventbox"] AS $key => $value) {
								echo '<div class="x_class_eventbox_msg x_class_eventbox_msg_'.$value["type"].'">';
									echo '<div class="x_class_eventbox_msg_inner">';
										echo '<div class="x_class_eventbox_msg_text">';
											if(!$filter) { echo $value["text"]; } else { echo htmlspecialchars($value["text"] ?? ''); }
										echo '</div>';
										if($closebutton) {
											echo '<div class="x_class_eventbox_msg_close" onclick="this.parentNode.parentNode.remove()">';
												echo $closebutton;
											echo '</div>';	
										}											
									echo '</div>';
								echo '</div>';
							}
						echo "</div>";
					echo "</div>";
				} else {
					unset( $_SESSION[$this->cookie."x_class_eventbox"] );
					unset( $_SESSION[$this->cookie."x_class_eventbox_skip"] );
				}
			}
			unset( $_SESSION[$this->cookie."x_class_eventbox"] );
			unset( $_SESSION[$this->cookie."x_class_eventbox_skip"] );
		}		
	}