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
	
	class x_class_2fa {
		private $secretKey;
		private $codeLength;

		public function __construct($secretKey, $codeLength = 6) {
			$this->secretKey = $secretKey;
			$this->codeLength = $codeLength;
		}

		// Generate a random secret key (Base32)
		public static function generateSecretKey($length = 16) {
			$random = random_bytes($length);
			return self::base32_encode($random);
		}

		// Generate a 2FA code (TOTP)
		public function generateCode($forTime = null) {
			$timeStep = 30;
			$codeLength = $this->codeLength;
			$secret = self::base32_decode($this->secretKey);

			// Use current time if not provided
			$time = $forTime ?? time();
			$counter = floor($time / $timeStep);

			// Pack counter as 8-byte big-endian
			$bin_counter = pack('N*', 0, $counter);

			// HMAC-SHA1
			$hash = hash_hmac('sha1', $bin_counter, $secret, true);
			$offset = ord(substr($hash, -1)) & 0x0F;
			$truncatedHash = substr($hash, $offset, 4);
			$value = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;
			$modulo = pow(10, $codeLength);
			return str_pad($value % $modulo, $codeLength, '0', STR_PAD_LEFT);
		}

		// Verify a 2FA code (allow small time drift)
		public function verifyCode($code, $window = 1) {
			$time = time();
			for ($i = -$window; $i <= $window; $i++) {
				if (hash_equals($this->generateCode($time + ($i * 30)), $code)) {
					return true;
				}
			}
			return false;
		}

		// Minimal Base32 encode (RFC 4648, no padding)
		private static function base32_encode($data) {
			$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
			$binary = '';
			foreach (str_split($data) as $char) {
				$binary .= sprintf('%08b', ord($char));
			}
			$base32 = '';
			for ($i = 0; $i < strlen($binary); $i += 5) {
				$chunk = substr($binary, $i, 5);
				$chunk = str_pad($chunk, 5, '0');
				$base32 .= $alphabet[bindec($chunk)];
			}
			return $base32;
		}

		// Minimal Base32 decode (RFC 4648, no padding)
		private static function base32_decode($input) {
			$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
			$input = strtoupper($input);
			$binary = '';
			foreach (str_split($input) as $char) {
				$index = strpos($alphabet, $char);
				if ($index === false) continue;
				$binary .= sprintf('%05b', $index);
			}
			$output = '';
			for ($i = 0; $i < strlen($binary); $i += 8) {
				$byte = substr($binary, $i, 8);
				if (strlen($byte) < 8) break;
				$output .= chr(bindec($byte));
			}
			return $output;
		}
	}
