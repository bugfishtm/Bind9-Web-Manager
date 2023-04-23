<?php
	/*	__________ ____ ___  ___________________.___  _________ ___ ___  
		\______   \    |   \/  _____/\_   _____/|   |/   _____//   |   \ 
		 |    |  _/    |   /   \  ___ |    __)  |   |\_____  \/    ~    \
		 |    |   \    |  /\    \_\  \|     \   |   |/        \    Y    /
		 |______  /______/  \______  /\___  /   |___/_______  /\___|_  / 
				\/                 \/     \/                \/       \/  Crypt Class */	
	class x_class_crypt {
		private $algo = 'aes-256-cbc'; 
		
		// Constructor and choose algorythm
		function __construct($algo = 'aes-256-cbc') { $this->algo = $algo; }
		
		// Encrypt Data and Return
		public function encrypt($data, $key) {
			$encryption_key = base64_decode($key);
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->algo));
			$encrypted = openssl_encrypt($data, $this->algo, $encryption_key, 0, $iv);
			return base64_encode($encrypted . '::' . $iv);
		}		
		// Decrypt Data and Return
		public function decrypt($data, $key) {
			$encryption_key = base64_decode($key);
			list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
			return openssl_decrypt($encrypted_data, $this->algo, $encryption_key, 0, $iv);
		}		
	}
