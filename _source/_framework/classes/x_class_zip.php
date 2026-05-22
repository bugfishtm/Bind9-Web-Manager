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
	
	class x_class_zip {
		// Constructor
		function __construct() {}
		
		// ZIP a File
		public function zip($file_source, $file_destination_zip, $x_class_crypt = false, $cryptokey = false, $tempfile = false) {
			if(!file_exists($file_source)) { error_log("x_class_zip: The Requested File to Zip does not exist $file_source!"); return false; }
			if(file_exists($file_destination_zip)) { error_log("x_class_zip: The Destination File can not be written if there is already a file existing $file_destination_zip!"); return false; }
			if(!$tempfile) {$tempfile = $file_destination_zip."cryptzip";} 
			if(file_exists($tempfile)) { error_log("x_class_zip: The Destination Temp File can not be written if there is already a file existing $tempfile!"); return false; }
			if(is_object($x_class_crypt) AND isset($cryptokey)) {
				$source = $file_source;
				$zip = new ZipArchive();
				if (!$zip->open($tempfile, ZIPARCHIVE::CREATE)) {error_log("x_class_zip: Could not Open Destination File to Create Zip $file_destination_zip!"); return false;}
				$source = str_replace('\\', '/', realpath($source));
				if (is_dir($source) === true){
					$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
					foreach ($files as $file){
						$file = str_replace('\\', '/', $file);
						// Ignore "." and ".." folders
						if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) continue;
						$file = realpath($file);
						if (is_dir($file) === true) { $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));}
						else if (is_file($file) === true) { $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file)); }
					}
				} else if (is_file($source) === true) { $zip->addFromString(basename($source), file_get_contents($source));}				
				@$zip->close();
				if(!file_exists($tempfile)) { error_log("x_class_zip: Zipped file not found for encryption! $tempfile!"); return false; }
				$code = file_get_contents($tempfile);
				$encrypted_code = $x_class_crypt->encrypt($code, $cryptokey);
				file_put_contents($file_destination_zip, $encrypted_code);
				@unlink($tempfile); 
				return true;
			} else {
				$source = $file_source;
				$zip = new ZipArchive();
				if (!$zip->open($file_destination_zip, ZIPARCHIVE::CREATE)) {error_log("x_class_zip: Could not Open Destination File to Create Zip $file_destination_zip!"); return false;}
				$source = str_replace('\\', '/', realpath($source));
				if (is_dir($source) === true){
					$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
					foreach ($files as $file){
						$file = str_replace('\\', '/', $file);
						// Ignore "." and ".." folders
						if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) continue;
						$file = realpath($file);
						if (is_dir($file) === true) { $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));}
						else if (is_file($file) === true) { $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file)); }
					}
				} else if (is_file($source) === true) { $zip->addFromString(basename($source), file_get_contents($source));}				
				@$zip->close();
				return true;				
			}
		}	

		// unzip a File
		public function unzip($from, $to, $x_class_crypt = false, $cryptokey = false, $tempfile = false) {		
			if(!is_string($from) OR !is_string($to)) { error_log("x_class_zip: Cant Unzip file, error in to: $to or from: $from!"); return false; }
			if(!file_exists($from)) { error_log("x_class_zip: Zipped file not found $from!"); return false; }
			if(!$tempfile) {$tempfile = $to."cryptzip";}
			if(file_exists($tempfile)) { error_log("x_class_zip: Cant decrypt zipped file if tempfile file exists $tempfile!"); return false; }
			if(file_exists($to)) { error_log("x_class_zip: Cant unzip file if destination already exists $to!"); return false; }
			if(is_object($x_class_crypt) AND isset($cryptokey)) {
				$encrypted_code = file_get_contents($from);
				$decrypted_code = $x_class_crypt->decrypt($encrypted_code, $cryptokey);
				file_put_contents($tempfile, $decrypted_code);
				$zip = new ZipArchive;
				if ($zip->open($tempfile) === TRUE) {
					$zip->extractTo($to);
					$zip->close();
					unlink($tempfile);
					return true;
				} else { error_log("x_class_zip: Error Opening Zipped file $from!"); return false; }
			} else {
				$zip = new ZipArchive;
				if ($zip->open($from) === TRUE) {
					$zip->extractTo($to);
					@$zip->close();
				} else { @$zip->close(); error_log("x_class_zip: Error Opening Zipped file $from!"); return false; }	
				return true;
			}
		}
	}
