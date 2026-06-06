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
	// Recursive Copy a Directory
	########################################################################
	function x_copy_directory($src, $dst) { 
		$dir = opendir($src);  
		@mkdir($dst);  
		while( $file = readdir($dir) ) {  
			if (( $file != '.' ) && ( $file != '..' )) {  
				if ( is_dir($src . '/' . $file) )  
				{  
					x_copy_directory($src . '/' . $file, $dst . '/' . $file);  
				}  
				else {  
					copy($src . '/' . $file, $dst . '/' . $file);  
				}  
			}  
		}  
		closedir($dir); 
	}  
			
	########################################################################
	// Deny Folder Access by Creating HTAccess File in it
	########################################################################
	function x_htaccess_secure($path) {
		if(!file_exists($path."/.htaccess")) {
			file_put_contents($path."/.htaccess", "# Deny a Folders Access\r\nDeny from all");
		}}
		
	########################################################################
	// Recursive Delete a Folder
	########################################################################
	function x_rmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir") {
						x_rmdir($dir . "/" . $object); 
					} else {
						unlink($dir . "/" . $object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}}		