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
	
	class x_class_crypt {
		private $algo = 'aes-256-cbc'; 
		
		// Constructor and choose algorythm
		function __construct($algo = 'aes-256-cbc') { $this->algo = $algo; }
		
		// Is the selected algorithm an authenticated (AEAD) cipher?
		private function is_aead() { return (stripos($this->algo, 'gcm') !== false OR stripos($this->algo, 'ccm') !== false); }

		// Encrypt Data and Return (authenticated encryption)
		public function encrypt($data, $key) {
			$encryption_key = base64_decode($key);
			$ivlen = openssl_cipher_iv_length($this->algo);
			if($ivlen === false) { return false; }
			$iv = openssl_random_pseudo_bytes($ivlen);
			if($this->is_aead()) {
				// AEAD ciphers (e.g. aes-256-gcm) authenticate via an auth tag
				$tag = '';
				$encrypted = openssl_encrypt($data, $this->algo, $encryption_key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
				if($encrypted === false) { return false; }
				return base64_encode($iv . $tag . $encrypted);
			} else {
				// Non-AEAD ciphers (e.g. aes-256-cbc): Encrypt-then-HMAC to prevent tampering / padding-oracle attacks
				$encrypted = openssl_encrypt($data, $this->algo, $encryption_key, OPENSSL_RAW_DATA, $iv);
				if($encrypted === false) { return false; }
				$mac_key = hash_hmac('sha256', 'x_class_crypt_mac', $encryption_key, true);
				$mac = hash_hmac('sha256', $iv . $encrypted, $mac_key, true);
				return base64_encode($iv . $mac . $encrypted);
			}
		}
		// Decrypt Data and Return (verifies authenticity, returns false on tampering)
		public function decrypt($data, $key) {
			$encryption_key = base64_decode($key);
			$raw = base64_decode($data, true);
			if($raw === false) { return false; }
			$ivlen = openssl_cipher_iv_length($this->algo);
			if($ivlen === false OR strlen($raw) < $ivlen) { return false; }
			$iv = substr($raw, 0, $ivlen);
			if($this->is_aead()) {
				$taglen = 16;
				if(strlen($raw) < $ivlen + $taglen) { return false; }
				$tag = substr($raw, $ivlen, $taglen);
				$encrypted = substr($raw, $ivlen + $taglen);
				return openssl_decrypt($encrypted, $this->algo, $encryption_key, OPENSSL_RAW_DATA, $iv, $tag);
			} else {
				$maclen = 32;
				if(strlen($raw) < $ivlen + $maclen) { return false; }
				$mac = substr($raw, $ivlen, $maclen);
				$encrypted = substr($raw, $ivlen + $maclen);
				$mac_key = hash_hmac('sha256', 'x_class_crypt_mac', $encryption_key, true);
				$calc = hash_hmac('sha256', $iv . $encrypted, $mac_key, true);
				if(!hash_equals($calc, $mac)) { return false; }
				return openssl_decrypt($encrypted, $this->algo, $encryption_key, OPENSSL_RAW_DATA, $iv);
			}
		}
	}
