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
	
	class x_class_mail_item {
		// Class Variables
		private $x_class_mail = false;
		// Construct		
		function __construct($x_class_mail) {$this->x_class_mail = $x_class_mail;} 
		// Functions for Send Adjustments
		private $attachment = array();
		public function add_attachment($path, $name) { array_push($this->attachment, array($path,$name)); }
		public function get_attachment() { return $this->attachment; }	
		public function clear_attachment() { $this->attachment = array(); }
		private $receiver = array();
		public function add_receiver($mail, $name) { array_push($this->receiver, array($mail,$name)); }
		public function get_receiver() { return $this->receiver; }	
		public function clear_receiver() { $this->receiver = array(); }
		private $cc = array();
		public function add_cc($mail, $name) { array_push($this->cc, array($mail,$name)); }
		public function get_cc() { return $this->cc; }
		public function clear_cc() { $this->cc = array(); }
		private $bcc = array();
		public function add_bcc($mail, $name) { array_push($this->bcc, array($mail,$name)); }	
		public function get_bcc() { return $this->bcc; }		
		public function clear_bcc() { $this->bcc = array(); }
		private $settings = array();
		public function add_setting($name, $value) { $this->settings[$name] = $value; }	
		public function get_setting() { return $this->settings; }
		public function clear_setting() { $this->settings = array(); }		
		// Send Final Mail
		public function send($subject, $content) {
			return $this->x_class_mail->mail($subject, $content, $this->receiver, $this->cc, $this->bcc, $this->attachment, $this->settings);
		}
	}
